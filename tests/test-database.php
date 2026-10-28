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

  $Storage = Store::Get();
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

  DB::Exec('delete from access.users where user_id = $1', [$ID]);
