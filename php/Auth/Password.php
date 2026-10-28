<?php

  namespace Auth;

  use BS\{AlertError, Card, Input, PadSize, ScreenSize, Side};
  use Config;
  use Storage\Store;

  /**
   * Sign in with a username (or email address) and password, for users an admin made (Administer → Users)
   *
   * The sign-in form posts to the login page; a right password gives the same sign-in token as the other providers,
   * and a session cookie (auth.password.session_days) that renews it, until the password changes. Wrong passwords lock
   * the user's sign-in for a while (LOCK_AFTER, LOCK_SECONDS), and slow down the address they come from (ADDRESS_LIMIT).
   *
   * Settings in its section (auth.password, see Config):
   *   enabled      - true to offer it
   *   min_length   - the shortest password allowed (default 10)
   *   session_days - how long a sign-in lasts without the password (default 30)
   *   reset        - false to leave out "Forgot your password?", which emails a link (default: on, while mail is)
   */
  class Password extends Base
  {
    const ?string PROVIDER = 'password';
    const string TITLE = 'Password';
    const \BS\Icon ICON = \BS\Icon::Key;
    // Wrong passwords in a row that lock a user's sign-in, and for how long
    const int LOCK_AFTER = 5;
    const int LOCK_SECONDS = 15 * 60;
    // Wrong passwords from one address, across usernames, in LOCK_SECONDS, before it is refused
    const int ADDRESS_LIMIT = 20;
    // Passwords longer than this are refused, hashing them costs time
    const int MAX_LENGTH = 256;
    // How long a reset link works
    const int RESET_SECONDS = 60 * 60;
    // Why the last sign-in didn't work, shown above the form
    private ?string $Problem = null;
    // The posted form is checked once per request, however often the sign-in is asked about
    private static bool $Posted = false;

    public static function Configured(): bool
    {
      return Config::Get('auth.password.enabled') === true;
    }

    public static function MinLength(): int
    {
      return max(1, (int)Config::Get('auth.password.min_length', 10));
    }

    /**
     * Whether "Forgot your password?" is offered: on unless auth.password.reset is false, while mail is on
     */
    public static function CanReset(): bool
    {
      return self::Configured() && Config::Get('auth.password.reset', true) !== false && Config::Get('mail.disabled') !== true;
    }

    /**
     * Why a new password isn't good enough, or null when it is
     */
    public static function Weakness(string $Password, ?string $Username = null): ?string
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
      if ($Username !== null && mb_strtolower(trim($Password)) === mb_strtolower(trim($Username)))
      {
        return 'The password can\'t be the username.';
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
     * The user a username and password sign in, or why not: null for a wrong username or password
     *
     * @return int|string|null The user's id; a sentence for the page when they may not try now; null when it's wrong
     */
    public static function Check(string $Name, string $Password): int|string|null
    {
      $Storage = Store::Get();
      $Address = (string)($_SERVER['REMOTE_ADDR'] ?? '');
      if ($Storage->AddressFailures($Address, self::LOCK_SECONDS) >= self::ADDRESS_LIMIT)
      {
        return 'Too many wrong passwords from here. Please try again in a few minutes.';
      }
      $ID = trim($Name) === '' ? null : $Storage->PasswordUserID($Name);
      $User = $ID === null ? null : $Storage->Password($ID);
      if ($User !== null && self::Locked($User))
      {
        return 'Too many wrong passwords for this user. Please try again in ' . intdiv(self::LOCK_SECONDS, 60) . ' minutes.';
      }
      // An unknown user takes as long as a wrong password, so the time doesn't tell which usernames exist
      $Right = mb_strlen($Password) <= self::MAX_LENGTH && password_verify($Password, $User['hash'] ?? self::Hash(random_bytes(16)));
      if ($User === null || !$Right)
      {
        $Storage->PasswordFailed($User === null ? null : (int)$User['user_id'], $Address);
        return null;
      }
      if (password_needs_rehash($User['hash'], self::Algorithm()) || (int)$User['failures'] > 0)
      {
        $Storage->SetPassword((int)$User['user_id'], password_needs_rehash($User['hash'], self::Algorithm()) ? self::Hash($Password) : $User['hash']);
      }
      return (int)$User['user_id'];
    }

    // Whether the user's wrong passwords lock their sign-in now
    private static function Locked(array $User): bool
    {
      return (int)$User['failures'] >= self::LOCK_AFTER && (int)$User['last_failure'] > time() - self::LOCK_SECONDS;
    }

    /**
     * Sets a password user's password, signing them out everywhere else (but here, when it's your own)
     *
     * @return string|null Why it wasn't set, or null
     */
    public static function Set(int $UserID, string $Password): ?string
    {
      $User = Store::Get()->Password($UserID);
      if (($Weakness = self::Weakness($Password, $User['subject'] ?? null)) !== null)
      {
        return $Weakness;
      }
      if (($Problem = Store::Get()->SetPassword($UserID, self::Hash($Password))) !== null)
      {
        return $Problem;
      }
      // Your own: this browser stays signed in
      if ((string)$UserID === Base::ID() && !empty($_COOKIE['password_session'])
        && ($Session = self::Token($UserID, 'session', 86400 * self::SessionDays())) !== null)
      {
        (new self())->SetCookie('password_session', $Session, $Session->TTL());
      }
      return null;
    }

    /**
     * Whether a password is the user's, for a change that asks for the current one
     */
    public static function Verify(int $UserID, string $Password): bool
    {
      $User = Store::Get()->Password($UserID);
      return $User !== null && !self::Locked($User) && password_verify($Password, $User['hash']);
    }

    /**
     * A token for a link or cookie that works until the user's password changes: $Purpose, the user and their password
     * hash's fingerprint, signed with our own key
     */
    public static function Token(int $UserID, string $Purpose, int $Seconds): ?JWT
    {
      $User = Store::Get()->Password($UserID);
      return $User === null ? null : new JWT(['purpose' => $Purpose, 'uid' => $UserID, 'pv' => self::Fingerprint($User['hash'])], $Seconds);
    }

    /**
     * The user a Token() names, while it's signed by us, not expired, for $Purpose, and their password is the same
     */
    public static function TokenUser(string $Token, string $Purpose): ?int
    {
      $Token = JWT::FromToken($Token);
      if ($Token === null || $Token->Expired() || $Token->Get('iss') !== Config::Origin() || $Token->Get('purpose') !== $Purpose)
      {
        return null;
      }
      $User = Store::Get()->Password((int)$Token->Get('uid'));
      return $User !== null && hash_equals(self::Fingerprint($User['hash']), (string)$Token->Get('pv')) ? (int)$User['user_id'] : null;
    }

    // Changes when the password does, and tells nothing about it
    private static function Fingerprint(string $Hash): string
    {
      return substr(hash_hmac('sha256', 'sc-webframe session', $Hash), 0, 16);
    }

    protected function DoAccessToken(): JWT
    {
      // The sign-in form, posted back with its state
      if (\Post::Get('username') !== null && !self::$Posted)
      {
        self::$Posted = true;
        if (!$this->CheckState())
        {
          $this->Problem = 'The sign-in form expired, please try again.';
          return parent::DoAccessToken();
        }
        $Result = self::Check((string)\Post::Get('username'), (string)\Post::Get('password'));
        if (is_int($Result))
        {
          $Token = $this->SignIn($Result);
          if (!$Token->Expired() && ($Session = self::Token($Result, 'session', 86400 * self::SessionDays())) !== null)
          {
            $this->SetCookie('password_session', $Session, $Session->TTL());
          }
          return $Token;
        }
        sleep(1);
        $this->Problem = $Result ?? 'That username or password isn\'t right.';
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

    private static function SessionDays(): int
    {
      return max(1, (int)Config::Get('auth.password.session_days', 30));
    }

    // The sign-in token for a password user, as the other providers make it (a blocked user gets an expired one)
    private function SignIn(int $UserID): JWT
    {
      return $this->CreateAuthToken(['sub' => Store::Get()->Password($UserID)['subject']]);
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
    }

    /**
     * The sign-in form: username, password, and Forgot your password?; it posts to the login page
     */
    public function Form(): \HTML\Form
    {
      $Form = new \HTML\Form();
      $Form->Method = 'post';
      $Form->Action = Config::FrameworkURL('login.php');
      $Form->Format->Margin(Side::All, PadSize::Pad2);
      if ($this->Problem !== null)
      {
        $Form->AddChild(new AlertError('Not signed in', $this->Problem));
      }
      $Form->AddChild($State = new \HTML\Input('state', \HTML\InputType::Hidden));
      $State->Value($this->NewState());
      $Form->AddChild($Username = new Input('username', 'Username or email'));
      $Username->InputElement()->Required = true;
      $Username->InputElement()->AutoComplete = 'username';
      $Username->Value(\Post::Get('username') ?? '');
      $Username->Format->Margin(Side::Bottom, PadSize::Pad2);
      $Form->AddChild($Password = new Input('password', 'Password', \HTML\InputType::Password));
      $Password->InputElement()->Required = true;
      $Password->InputElement()->AutoComplete = 'current-password';
      $Password->Format->Margin(Side::Bottom, PadSize::Pad3);
      $Form->AddChild($Submit = new \HTML\Button('Sign in'));
      $Submit->Type = 'submit';
      $Submit->AddClasses(['btn', 'btn-primary']);
      if (self::CanReset())
      {
        $Form->AddChild($Forgot = new \HTML\Anchor('Forgot your password?', Config::FrameworkURL('resetpassword.php'), IsLink: true));
        $Forgot->Format->Margin(Side::Start, PadSize::Pad3);
      }
      return $Form;
    }
  }
