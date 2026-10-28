<?php

  // The PostgreSQL storage against a real database: SC_WEBFRAME_TEST_DATABASE is a libpq connection string to an empty
  // database it may fill (the schema is loaded into it). Skipped when it isn't set.
  require __DIR__ . '/check.inc';

  use Storage\Store;

  $Connection = getenv('SC_WEBFRAME_TEST_DATABASE');
  if (empty($Connection))
  {
    echo "SC_WEBFRAME_TEST_DATABASE isn't set: skipped\n";
    exit(0);
  }
  Config::Load();
  Config::Set('database', ['connection' => $Connection]);

  // The schema, twice: the second run changes nothing
  foreach ([1, 2] as $Run)
  {
    pg_query(DB::Connection(), file_get_contents(dirname(__DIR__) . '/sql/schema.sql')) or die("FAILED: schema run $Run\n");
  }
  Same('6', DB::Value('select count(*) from framework.menu where code in (\'home\', \'login\', \'logout\', \'privacy\', \'terms\', \'datadeletion\')'),
    'the schema\'s menu items, once');

  Same(['t'], array_column(DB::Rows('select count(*) = 1 as one from pg_constraint where conname = \'users_provider_check\''), 'one'),
    'one provider check, which allows passwords');
  Same('11', DB::Value('select count(*) from framework.menu where code in (\'home\', \'login\', \'logout\', \'privacy\', \'terms\', \'datadeletion\', \'administer\', \'users\', \'menu\', \'config\', \'profile\')'),
    'the schema\'s items, Administer, Users, Menu and the profile among them');
  $Storage = Store::Get();

  // The first admin: nobody is one in a fresh database. With auth.first_admin an email, only that address becomes it;
  // with false nobody; by default the first sign-in
  $Admins = fn() => DB::Value('select count(*) from access.grants g join access.levels l using (access_id) where l.code = \'admin\' and g.revoked is null');
  Same('0', $Admins(), 'no admin yet');
  Config::Set('auth.first_admin', 'boss@example.com');
  Same(['public'], $Storage->SignIn('github', 'first-a', 'someone@example.com', 'Some', 'One')['access'], 'another address doesn\'t become admin');
  Config::Set('auth.first_admin', false);
  Same(['public'], $Storage->SignIn('github', 'first-b', 'boss@example.com', 'The', 'Boss')['access'], 'false: nobody does');
  Config::Set('auth.first_admin', 'Boss@Example.com');
  Same(['public', 'admin'], $Storage->SignIn('github', 'first-b', 'boss@example.com', null, null)['access'], 'the address, any case, does, at once');
  Config::Set('auth.first_admin', true);
  Same(['public'], $Storage->SignIn('github', 'first-c', 'third@example.com', null, null)['access'], 'once there is an admin, nobody else');
  DB::Exec('delete from access.users where subject in (\'first-a\', \'first-b\', \'first-c\')');
  Same(['public', 'admin'], $Storage->SignIn('github', 'first-d', 'any@example.com', null, null)['access'], 'by default the first sign-in');
  DB::Exec('delete from access.users where subject = \'first-d\'');
  Config::Set('auth.first_admin', false); // The checks below start from a user with public access only

  $Subject = 'test-' . bin2hex(random_bytes(4));
  $User = $Storage->SignIn('github', $Subject, 'ann@example.com', 'Ann', null);
  $ID = (int)$User['user_id'];
  Same(['public'], $User['access'], 'a new user gets public access');
  $Again = $Storage->SignIn('github', $Subject, null, null, 'Example');
  Same($User['user_id'], $Again['user_id'], 'signing in again is the same user');
  Same('ann@example.com', $Again['email'], 'an email not given keeps the stored one');
  Same('Example', $Again['last_name'], 'a name given is stored');

  Same(null, $Storage->SetAccess($ID, 'admin', true), 'grant admin');
  Same(['public', 'admin'], $Storage->Access($ID), 'the access, in the levels\' order');
  Same(null, $Storage->SetAccess($ID, 'admin', false), 'revoke admin');
  Same(['public'], $Storage->Access($ID), 'revoked access is gone');
  Same('1', DB::Value('select count(*) from access.grants g join access.levels l using (access_id) where user_id = $1 and code = \'admin\' and revoked is not null', [$ID]),
    'revoking keeps the grant, with when');
  Same('Unknown access level.', $Storage->SetAccess($ID, 'nothing', true), 'an unknown access level');

  Same(null, $Storage->SetDisplay($ID, 'nickname', 'Annie'), 'set a nickname');
  Same('Annie', User::DisplayName($Storage->Profile($ID)), 'addressed by the nickname');
  Same('The sign-in provider gives this user\'s names.', $Storage->SetName($ID, 'X', 'Y'), 'names the provider gave stay');
  Same('t', $Storage->Profile($ID)['wants_provider_photo'], 'no photo yet: the provider\'s is wanted');
  Same(null, $Storage->SetPhoto($ID, "\xff\xd8 not really a JPEG", 'user'), 'store a photo');
  Same(base64_encode("\xff\xd8 not really a JPEG"), $Storage->Profile($ID)['photo'], 'the photo reads back');
  Same(null, $Storage->RemovePhoto($ID), 'remove it');
  Same('none', $Storage->Profile($ID)['photo_source'], 'removed: the provider\'s is not used either');

  // The menu: add under a submenu, rights inherited, the editor kept for admins
  $Code = 'test-' . bin2hex(random_bytes(3));
  $Parent = $Storage->AddMenuItem('Test', null, $Code, null, 'bi-circle', 'main');
  Check(is_int($Parent), 'add a submenu');
  $Child = $Storage->AddMenuItem('Editor', $Parent, "$Code-editor", "/$Code/menu.php", 'bi-list', 'main');
  Check(is_int($Child), 'add a page under it');
  Same("Another item already has the ID $Code.", $Storage->AddMenuItem('Again', null, $Code, null, 'bi-circle', 'main'), 'an ID taken');
  $Items = array_column($Storage->Menu(), null, 'id');
  Same(['admin'], $Items[$Child]['edit'], 'the parent\'s rights, admin at the top');
  Same('1', $Items[$Child]['depth'], 'its depth');
  Same('Admins would no longer see and edit the page that manages the menu.', $Storage->SetMenuRight($Child, 'admin', 'edit', false, "/$Code/menu.php"),
    'taking admin\'s edit right on the editor is refused');
  Same(['admin'], array_column($Storage->Menu(), null, 'id')[$Child]['edit'], 'and undone');
  Same(null, $Storage->SetMenuRight($Child, 'public', 'edit', true, null), 'give public the edit right');
  Same(['public', 'admin'], array_column($Storage->Menu(), null, 'id')[$Child]['access'], 'edit also gives view');
  Same(null, $Storage->SetMenuField($Child, 'popup', true, null), 'set a field');
  Same('t', array_column($Storage->Menu(), null, 'id')[$Child]['popup'], 'the field');
  Same('Move or remove the items under it first.', $Storage->DeleteMenuItem($Parent, null), 'a submenu with items stays');
  Same(null, $Storage->DeleteMenuItem($Child, null), 'remove the page');
  Same(null, $Storage->DeleteMenuItem($Parent, null), 'then the submenu');

  // Password accounts: by email address; invited ones have no password yet
  $Name = 'pw-' . bin2hex(random_bytes(3));
  $Bob = $Storage->AddPasswordUser(" Bob-$Name@Example.com ", 'Bob', '', 'hash one');
  Check(is_int($Bob), 'add a password account');
  Same("There is an account for bob-$Name@example.com already.", $Storage->AddPasswordUser("BOB-$Name@example.com", null, null, 'x'),
    'one per address, any case');
  Same($Bob, $Storage->PasswordUserID("bob-$Name@example.com"), 'found by its address');
  Same(null, $Storage->PasswordUserID('nobody@example.com'), 'not an unknown one');
  Same(null, $Storage->PasswordUserID('ann@example.com'), 'nor someone who signs in with GitHub');
  $Row = $Storage->Password($Bob);
  Same(["bob-$Name@example.com", 'hash one', '0', null], [$Row['email'], $Row['hash'], $Row['failures'], $Row['last_failure']], 'the account');
  Same(['public'], $Storage->Access($Bob), 'with public access');
  Same(null, $Storage->Password($ID), 'a GitHub user has no password');
  Same('t', $Storage->Profile($Bob)['names_from_user'], 'their names are theirs to change');
  $Ann = $Storage->AddPasswordUser("ann-$Name@example.com", 'Ann', null, null);
  Same([null, '0'], [$Storage->Password($Ann)['hash'], $Storage->Password($Ann)['failures']], 'an invited account, without a password');
  $Verified = array_column($Storage->Users(), 'verified', 'user_id');
  Same(['t', 'f', null], [$Verified[$Bob], $Verified[$Ann], $Verified[$ID]], 'Users shows who chose a password');
  Same(null, $Storage->SetPassword($Ann, 'hash ann'), 'choosing one');
  Same('hash ann', $Storage->Password($Ann)['hash'], 'makes the account work');
  $Address = '192.0.2.' . random_int(1, 254);
  $Storage->PasswordFailed($Bob, $Address);
  $Storage->PasswordFailed(null, $Address);
  Same('1', $Storage->Password($Bob)['failures'], 'a wrong password counts for the account');
  Check(abs((int)$Storage->Password($Bob)['last_failure'] - time()) < 60, 'with when');
  Same(2, $Storage->AddressFailures($Address, 60), 'and both for the address');
  $Changed = DB::Value('select changed from access.passwords where user_id = $1', [$Bob]);
  Same(null, $Storage->SetPassword($Bob, 'hash one'), 'the same hash again (a right password)');
  Same(['0', $Changed], [$Storage->Password($Bob)['failures'], DB::Value('select changed from access.passwords where user_id = $1', [$Bob])],
    'clears the count, and isn\'t a change');
  Same(null, $Storage->SetPassword($Bob, 'hash two'), 'a new hash');
  Same('hash two', $Storage->Password($Bob)['hash'], 'is kept');
  Same('This user doesn\'t sign in with a password.', $Storage->SetPassword($ID, 'x'), 'a GitHub user\'s password can\'t be set');
  Same((string)$Bob, $Storage->SignIn('password', "bob-$Name@example.com", null, null, null)['user_id'], 'signing in is the same user');
  DB::Exec('delete from access.sign_in_failures where address = $1', [$Address]);
  DB::Exec('delete from access.users where user_id in ($1, $2)', [$Bob, $Ann]);

  DB::Exec('delete from access.users where user_id = $1', [$ID]);
