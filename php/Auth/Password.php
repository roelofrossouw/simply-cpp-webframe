<?php

  namespace Auth;

  use BS\{AlertError, Button, Card, Icon, Input, PadSize, ScreenSize, Side};
  use Config;
  use Storage\Store;

  /**
   * Sign in with an email address and password
   *
   * The email address is the account: every password is chosen through a link emailed to it, so an account only works
   * once its address is shown to work. Accounts come from an admin (Administer → Users → Add a user, which emails an
   * invitation) or, with auth.password.signup, from people themselves (signup.php). Forgot your password?
   * (resetpassword.php) and Your profile's change of password email a link the same way; a new email address is confirmed
   * by a link sent to it (confirmemail.php).
   *
   * The sign-in form posts to the login page; a right password gives the same sign-in token as the other providers,
   * and a session cookie (auth.password.session_days) that renews it, until the password changes. Wrong passwords lock
   * the account's sign-in for a while (LOCK_AFTER, LOCK_SECONDS), and slow down the address they come from (ADDRESS_LIMIT).
   *
   * Settings in its section (auth.password, see Config); it needs mail (the mail section, not disabled):
   *   enabled      - true to offer it
   *   min_length   - the shortest password allowed (default 10)
   *   session_days - how long a sign-in lasts without the password (default 30)
   *   signup       - true to let anyone with an email address make their own account (default false)
   */
  class Password extends Base
  {
    const ?string PROVIDER = 'password';
    const string TITLE = 'Password';
    const \BS\Icon ICON = \BS\Icon::Key;
    // Wrong passwords in a row that lock an account's sign-in, and for how long
    const int LOCK_AFTER = 5;
    const int LOCK_SECONDS = 15 * 60;
    // Wrong passwords (and emailed links) from one address, across accounts, in LOCK_SECONDS, before it is refused
    const int ADDRESS_LIMIT = 20;
    // Passwords longer than this are refused, hashing them costs time
    const int MAX_LENGTH = 256;
    // How long an emailed link works: one asked for, and an admin's invitation
    const int LINK_SECONDS = 60 * 60;
    const int INVITE_SECONDS = 7 * 24 * 60 * 60;
    // Why the last sign-in didn't work, shown above the form
    private ?string $Problem = null;
    // The posted form is checked once per request, however often the sign-in is asked about
    private static bool $Posted = false;

    public static function Configured(): bool
    {
      return Config::Get('auth.password.enabled') === true;
    }

    /**
     * Whether the links can go out: passwords are on, and mail isn't off
     */
    public static function CanEmail(): bool
    {
      return self::Configured() && Config::Get('mail.disabled') !== true;
    }

    public static function MinLength(): int
    {
      return max(1, (int)Config::Get('auth.password.min_length', 10));
    }

    /**
     * Whether people may make their own account with their email address (auth.password.signup, off by default)
     */
    public static function CanSignUp(): bool
    {
      return self::CanEmail() && Config::Get('auth.password.signup') === true;
    }

    /**
     * An email address as accounts are kept: trimmed, lower case; null when it isn't one
     */
    public static function Address(string $Email): ?string
    {
      $Email = mb_strtolower(trim($Email));
      return filter_var($Email, FILTER_VALIDATE_EMAIL) ? $Email : null;
    }

    /**
     * Why a new password isn't good enough, or null when it is
     */
    public static function Weakness(string $Password, ?string $Email = null): ?string
    {
      $Length = mb_strlen($Password);
      if ($Length < self::MinLength())
      {
        return 'A password needs at least ' . self::MinLength() . ' characters.';
      }
      if ($Length > self::MAX_LENGTH)
      {
        return 'A password can\'t be longer than ' . self::MAX_LENGTH . ' characters.';
      }
      if ($Email !== null && mb_strtolower(trim($Password)) === mb_strtolower(trim($Email)))
      {
        return 'The password can\'t be the email address.';
      }
      return null;
    }

    public static function Hash(string $Password): string
    {
      return password_hash($Password, self::Algorithm());
    }

    // Argon2id when PHP has it (Debian's does), else bcrypt
    private static function Algorithm(): string
    {
      return defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
    }

    /**
     * Whether the address may try again (or be sent another link): fewer than ADDRESS_LIMIT lately. Counts this try.
     */
    public static function MayTry(): bool
    {
      $Address = (string)($_SERVER['REMOTE_ADDR'] ?? '');
      if (Store::Get()->AddressFailures($Address, self::LOCK_SECONDS) >= self::ADDRESS_LIMIT)
      {
        return false;
      }
      Store::Get()->PasswordFailed(null, $Address);
      return true;
    }

    /**
     * The account an email address and password sign in, or why not: null for a wrong address or password
     *
     * @return int|string|null The user's id; a sentence for the page when they may not try now; null when it's wrong
     */
    public static function Check(string $Email, string $Password): int|string|null
    {
      $Storage = Store::Get();
      $Address = (string)($_SERVER['REMOTE_ADDR'] ?? '');
      if ($Storage->AddressFailures($Address, self::LOCK_SECONDS) >= self::ADDRESS_LIMIT)
      {
        return 'Too many wrong passwords from here. Please try again in a few minutes.';
      }
      $ID = ($Email = self::Address($Email)) === null ? null : $Storage->PasswordUserID($Email);
      $User = $ID === null ? null : $Storage->Password($ID);
      if ($User !== null && self::Locked($User))
      {
        return 'Too many wrong passwords for this account. Please try again in ' . intdiv(self::LOCK_SECONDS, 60) . ' minutes.';
      }
      // An unknown address, or an account without a password yet, takes as long as a wrong password, so the time doesn't
      // tell which addresses have accounts
      $Hash = $User['hash'] ?? null;
      $Right = mb_strlen($Password) <= self::MAX_LENGTH && password_verify($Password, $Hash ?? self::Hash(random_bytes(16))) && $Hash !== null;
      if (!$Right)
      {
        $Storage->PasswordFailed($User === null ? null : (int)$User['user_id'], $Address);
        return null;
      }
      if (password_needs_rehash($Hash, self::Algorithm()) || (int)$User['failures'] > 0)
      {
        $Storage->SetPassword((int)$User['user_id'], password_needs_rehash($Hash, self::Algorithm()) ? self::Hash($Password) : $Hash);
      }
      return (int)$User['user_id'];
    }

    // Whether the account's wrong passwords lock its sign-in now
    private static function Locked(array $User): bool
    {
      return (int)$User['failures'] >= self::LOCK_AFTER && (int)$User['last_failure'] > time() - self::LOCK_SECONDS;
    }

    /**
     * Sets an account's password, from a link (see LinkUser()); it ends its other sign-ins
     *
     * @return string|null Why it wasn't set, or null
     */
    public static function Set(int $UserID, string $Password): ?string
    {
      $User = Store::Get()->Password($UserID);
      if (($Weakness = self::Weakness($Password, $User['email'] ?? null)) !== null)
      {
        return $Weakness;
      }
      return Store::Get()->SetPassword($UserID, self::Hash($Password));
    }

    /**
     * A token for a link or cookie that works until the account's password changes: $Purpose, the user, their password
     * hash's fingerprint and anything in $Extra, signed with our own key; null when it isn't a password account
     */
    public static function Token(int $UserID, string $Purpose, int $Seconds, array $Extra = []): ?JWT
    {
      $User = Store::Get()->Password($UserID);
      return $User === null ? null
        : new JWT(['purpose' => $Purpose, 'uid' => $UserID, 'pv' => self::Fingerprint($User['hash'])] + $Extra, $Seconds);
    }

    /**
     * The account a Token() names, while it's signed by us, not expired, for $Purpose, and its password is the same; a
     * session also needs a password
     *
     * @return array|null The account (see Storage\Backend::Password()), with the token's payload as 'token'
     */
    public static function TokenAccount(string $Token, string $Purpose): ?array
    {
      $Token = JWT::FromToken($Token);
      if ($Token === null || $Token->Expired() || $Token->Get('iss') !== Config::Origin() || $Token->Get('purpose') !== $Purpose)
      {
        return null;
      }
      $User = Store::Get()->Password((int)$Token->Get('uid'));
      if ($User === null || !hash_equals(self::Fingerprint($User['hash']), (string)$Token->Get('pv')) || ($Purpose == 'session' && $User['hash'] === null))
      {
        return null;
      }
      return $User + ['token' => $Token->GetPayload()];
    }

    /**
     * The user a Token() names, see TokenAccount()
     */
    public static function TokenUser(string $Token, string $Purpose): ?int
    {
      $User = self::TokenAccount($Token, $Purpose);
      return $User === null ? null : (int)$User['user_id'];
    }

    // Changes when the password does (also from none), and tells nothing about it
    private static function Fingerprint(?string $Hash): string
    {
      return substr(hash_hmac('sha256', 'sc-webframe session', $Hash ?? ''), 0, 16);
    }

    /**
     * A sign-up link's token: the email address it was sent to, signed with our own key, for LINK_SECONDS
     */
    public static function SignUpToken(string $Email): JWT
    {
      return new JWT(['purpose' => 'signup', 'email' => mb_strtolower(trim($Email))], self::LINK_SECONDS);
    }

    /**
     * The email address a SignUpToken() was sent to, while it's signed by us and not expired, and no account has it yet
     * (so each link makes one account)
     */
    public static function SignUpEmail(string $Token): ?string
    {
      $Token = JWT::FromToken($Token);
      if ($Token === null || $Token->Expired() || $Token->Get('iss') !== Config::Origin() || $Token->Get('purpose') !== 'signup')
      {
        return null;
      }
      $Email = self::Address((string)$Token->Get('email'));
      return $Email !== null && !self::Taken($Email) ? $Email : null;
    }

    /**
     * Whether an account has the email address
     */
    public static function Taken(string $Email): bool
    {
      return Store::Get()->PasswordUserID(mb_strtolower(trim($Email))) !== null;
    }

    /**
     * Emails a link to choose a password: the account's invitation ($Invite, from an admin), or a reset (Forgot your
     * password?, Your profile, an admin's key button)
     */
    public static function SendPasswordLink(int $UserID, bool $Invite = false): bool
    {
      $User = Store::Get()->Password($UserID);
      $Token = self::Token($UserID, 'password', $Invite ? self::INVITE_SECONDS : self::LINK_SECONDS);
      if ($User === null || $Token === null)
      {
        return false;
      }
      $AppName = Config::AppName();
      $Hours = intdiv($Invite ? self::INVITE_SECONDS : self::LINK_SECONDS, 3600);
      $Lasts = $Hours >= 48 ? intdiv($Hours, 24) . ' days' : ($Hours == 1 ? 'an hour' : "$Hours hours");
      return self::SendLink($User['email'], $Invite ? "Your $AppName account" : "Your $AppName password",
        $Invite ? "You have an account on $AppName now, for this email address. Choose your password to start; the link works for $Lasts."
          : "Someone asked to change the password of your $AppName account. If it was you, choose a new one here; the link works for $Lasts, once.",
        'Choose a password', Config::FrameworkURL('resetpassword.php') . "?token=$Token",
        $Invite ? 'If you didn\'t expect this, ignore it.' : 'If it wasn\'t you, ignore this email: your password stays as it is.');
    }

    /**
     * Emails a link to the new address, which makes it the account's (confirmemail.php)
     */
    public static function SendEmailLink(int $UserID, string $NewEmail): bool
    {
      $User = Store::Get()->Password($UserID);
      $Token = self::Token($UserID, 'email', self::LINK_SECONDS, ['email' => $NewEmail, 'old' => $User['email'] ?? null]);
      if ($User === null || $Token === null)
      {
        return false;
      }
      $AppName = Config::AppName();
      return self::SendLink($NewEmail, "Your new $AppName email address",
        "Someone asked to use this email address for their $AppName account, instead of {$User['email']}. If it was you, confirm it here; the link works for an hour.",
        'Use this address', Config::FrameworkURL('confirmemail.php') . "?token=$Token", 'If it wasn\'t you, ignore this email.');
    }

    /**
     * An email with one button: $Path is on this site
     */
    public static function SendLink(string $To, string $Subject, string $Text, string $Caption, string $Path, string $Footnote): bool
    {
      $Email = new \BS\Email();
      $Email->AddChild($Card = new Card(PadSize::Pad4));
      $Card->AddChild(new \HTML\Header(3, 'Hi,'));
      $Card->AddParagraph($Text);
      $Card->AddChild($Button = new Button($Caption, Icon::BoxArrowUpRight));
      $Button->Link(Config::Origin() . $Path);
      $Card->AddParagraph($Footnote);
      $Card->AddChild(Config::MailFooter());
      if (!$Email->Send($To, $Subject))
      {
        error_log("sc-webframe: the email \"$Subject\" wasn't sent");
        return false;
      }
      return true;
    }

    protected function DoAccessToken(): JWT
    {
      // The sign-in form, posted back with its state
      if (str_starts_with((string)\Post::Get('state'), self::PROVIDER . '.') && \Post::Get('password') !== null && !self::$Posted)
      {
        self::$Posted = true;
        if (!$this->CheckState())
        {
          $this->Problem = 'The sign-in form expired, please try again.';
          return parent::DoAccessToken();
        }
        $Result = self::Check((string)\Post::Get('email'), (string)\Post::Get('password'));
        if (is_int($Result))
        {
          return $this->StartSession($Result);
        }
        sleep(1);
        $this->Problem = $Result ?? 'That email address or password isn\'t right.';
        return parent::DoAccessToken();
      }

      // Renew the sign-in from the session cookie, until the password changes
      if (!empty($Session = $_COOKIE['password_session'] ?? null))
      {
        if (($UserID = self::TokenUser($Session, 'session')) !== null)
        {
          return $this->SignIn($UserID);
        }
        $this->ClearCookie('password_session');
      }
      return parent::DoAccessToken();
    }

    // A password account's sign-in token, and the session cookie that renews it
    private function StartSession(int $UserID): JWT
    {
      $Token = $this->SignIn($UserID);
      if (!$Token->Expired() && ($Session = self::Token($UserID, 'session', 86400 * self::SessionDays())) !== null)
      {
        $this->SetCookie('password_session', $Session, $Session->TTL());
      }
      return $Token;
    }

    /**
     * Signs an account in on this request, after a link chose its password (signup.php, resetpassword.php)
     *
     * @return bool False when it may not sign in (blocked)
     */
    public static function SignInAs(int $UserID): bool
    {
      $Provider = new self();
      Base::Factory($Provider);
      return $Provider->SetAuthToken($Provider->StartSession($UserID));
    }

    private static function SessionDays(): int
    {
      return max(1, (int)Config::Get('auth.password.session_days', 30));
    }

    // The sign-in token for a password account, as the other providers make it (a blocked user gets an expired one)
    private function SignIn(int $UserID): JWT
    {
      return $this->CreateAuthToken(['sub' => Store::Get()->Password($UserID)['email']]);
    }

    protected function DoLogout(bool $Reload = true): void
    {
      $this->ClearCookie('password_session');
      parent::DoLogout($Reload);
    }

    protected function ShowLogin(): void
    {
      \Page::AddContent($Card = new Card());
      $Card->Format->Center();
      $Card->Format->MaxWidth(ScreenSize::Small->Width());
      $Card->Format->Margin(Side::Top, PadSize::Pad4);
      $Card->AddHeader('Please sign in');
      $Card->AddChild($this->Form());
      self::AddHome($Card);
    }

    /**
     * A form of the password pages (resetpassword.php, signup.php), posting to $Action: its fields by name, as
     * [label, type, autocomplete, required (default true)], and a submit button
     */
    public static function PageForm(Card $Card, string $Action, array $Fields, string $Caption): \HTML\Form
    {
      $Card->AddChild($Form = new \HTML\Form());
      self::PostTo($Form, $Action);
      $Form->Format->Margin(Side::All, PadSize::Pad2);
      foreach ($Fields as $Name => $Field)
      {
        [$Label, $Type, $AutoComplete] = $Field;
        $Form->AddChild($Input = new Input($Name, $Label, $Type));
        $Input->InputElement()->Required = $Field[3] ?? true;
        $Input->InputElement()->AutoComplete = $AutoComplete;
        if ($Type == \HTML\InputType::Password)
        {
          $Input->InputElement()->MinLength = self::MinLength();
        }
        $Input->Format->Margin(Side::Bottom, PadSize::Pad2);
      }
      $Form->AddChild($Submit = new \HTML\Button($Caption));
      $Submit->Type = 'submit';
      $Submit->AddClasses(['btn', 'btn-primary', 'mt-2']);
      return $Form;
    }

    /**
     * Where a form of these pages posts: in the app (opened from the menu), js/app.js posts it and shows the answer in
     * the page, under the menu; on a page of its own (a link from an email), the browser posts it
     */
    public static function PostTo(\HTML\Form $Form, string $Action): void
    {
      if (\Server::IsAJAX())
      {
        $Form->Trigger(\HTML\Event::Submit, 'e.preventDefault(); loadPage.call($(this).find("[type=submit]")[0], ' . json_encode($Action) . ');');
        return;
      }
      $Form->Method = 'post';
      $Form->Action = $Action;
    }

    /**
     * A link to another of these pages: in the app it opens in the page, under the menu, see PostTo()
     */
    public static function PageLink(string $Caption, string $URL): \HTML\Anchor
    {
      if (!\Server::IsAJAX())
      {
        return new \HTML\Anchor($Caption, $URL, IsLink: true);
      }
      $Link = new \HTML\Anchor($Caption, '#', IsLink: true);
      $Link->Trigger(\HTML\Event::Click, 'e.preventDefault(); loadPage.call(this, ' . json_encode($URL) . ');');
      return $Link;
    }

    /**
     * The sign-in form: email, password, Forgot your password? and Create an account; it posts to the login page
     */
    public function Form(): \HTML\Form
    {
      $Form = new \HTML\Form();
      self::PostTo($Form, Config::FrameworkURL('login.php'));
      $Form->Format->Margin(Side::All, PadSize::Pad2);
      if ($this->Problem !== null)
      {
        $Form->AddChild(new AlertError('Not signed in', $this->Problem));
      }
      $Form->AddChild($State = new \HTML\Input('state', \HTML\InputType::Hidden));
      $State->Value($this->NewState());
      $Form->AddChild($Email = new Input('email', 'Email', \HTML\InputType::Email));
      $Email->InputElement()->Required = true;
      $Email->InputElement()->AutoComplete = 'username';
      $Email->Value(\Post::Get('email') ?? '');
      $Email->Format->Margin(Side::Bottom, PadSize::Pad2);
      $Form->AddChild($Password = new Input('password', 'Password', \HTML\InputType::Password));
      $Password->InputElement()->Required = true;
      $Password->InputElement()->AutoComplete = 'current-password';
      $Password->Format->Margin(Side::Bottom, PadSize::Pad3);
      $Form->AddChild($Submit = new \HTML\Button('Sign in'));
      $Submit->Type = 'submit';
      $Submit->AddClasses(['btn', 'btn-primary']);
      if (self::CanEmail())
      {
        $Form->AddChild($Forgot = self::PageLink('Forgot your password?', Config::FrameworkURL('resetpassword.php')));
        $Forgot->Format->Margin(Side::Start, PadSize::Pad3);
      }
      if (self::CanSignUp())
      {
        $Form->AddChild($SignUp = self::PageLink('Create an account', Config::FrameworkURL('signup.php')));
        $SignUp->Format->Margin(Side::Start, PadSize::Pad3);
      }
      return $Form;
    }
  }
