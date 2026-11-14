<?php

  namespace Auth;

  use BS\{AlertError, Card, Icon, PadSize, ScreenSize, Side};
  use Config;
  use Storage\Store;

  /**
   * Sign in with a passkey (WebAuthn): the phone's or computer's lock (fingerprint, face, PIN), or a security key
   *
   * A passkey belongs to an account of any provider: it's added on Your profile once signed in some other way, and signs
   * that account in as itself. On the sign-in page, Sign in with a passkey asks the browser for any passkey it has for
   * this site (discoverable, no email address needed). A session cookie (the account's provider's session_days, else 30)
   * renews the sign-in, as the password's does.
   *
   * The checks are sc-phplib's (SC\Passkey, sc-passkey); the browser side is js/sc-passkey.js with js/passkey.js. The
   * challenge each answer must sign is kept in the PHP session and works once.
   *
   * Settings in its section (auth.passkey, see Config):
   *   enabled - true to offer it (needs SC\Passkey, and a storage implementing Storage\Passkeys; Postgres does)
   *   rp_id   - the relying party: the domain passkeys belong to (default the site's host)
   *   rp_name - its name, as authenticators may show it (default app.name)
   *   origins - where the pages are, e.g. ["https://www.example.com"] or ["https://*.example.com"] (default the site's origin)
   */
  class Passkey extends Base
  {
    const ?string PROVIDER = 'passkey';
    const string TITLE = 'Passkey';
    const \BS\Icon ICON = \BS\Icon::Fingerprint;
    // The PHP session's key for the challenges
    const string SESSION_KEY = 'sc_webframe_passkey';
    // Why the last try didn't work, shown on the sign-in page
    private ?string $Problem = null;
    private static bool $Posted = false;

    public static function Configured(): bool
    {
      return Config::Get('auth.passkey.enabled') === true && class_exists('\SC\Passkey') && Store::Get() instanceof \Storage\Passkeys;
    }

    private static function Storage(): \Storage\Passkeys
    {
      $Storage = Store::Get();
      if (!$Storage instanceof \Storage\Passkeys)
      {
        throw new \RuntimeException('The storage doesn\'t keep passkeys (Storage\Passkeys)');
      }
      return $Storage;
    }

    /**
     * The checker, for the settings' relying party
     */
    public static function Checker(): \SC\Passkey
    {
      $Origins = Config::Get('auth.passkey.origins') ?: [Config::Origin()];
      return new \SC\Passkey((string)(Config::Get('auth.passkey.rp_id') ?: Config::Host()), (string)(Config::Get('auth.passkey.rp_name') ?: Config::AppName()),
        array_values(array_map('strval', (array)$Origins)));
    }

    // Keeps a challenge for one answer of a kind (signin or register), or takes it back (once)
    private static function Remember(string $Kind, string $Challenge): void
    {
      $_SESSION[self::SESSION_KEY][$Kind] = ['challenge' => $Challenge, 'at' => time()];
    }

    private static function Recall(string $Kind): ?string
    {
      $Kept = $_SESSION[self::SESSION_KEY][$Kind] ?? null;
      unset($_SESSION[self::SESSION_KEY][$Kind]);
      return is_array($Kept) && $Kept['at'] > time() - 600 ? $Kept['challenge'] : null;
    }

    /**
     * Options for signing in with any passkey for this site (navigator.credentials.get()), its challenge kept
     */
    public static function SignInOptions(): array
    {
      $Options = self::Checker()->authenticationOptions([]);
      self::Remember('signin', $Options['challenge']);
      return $Options;
    }

    /**
     * The account a passkey's answer (the browser's AuthenticationResponseJSON) signs in, its use recorded
     *
     * @return int|string The user's id, or why not, as a sentence for the page
     */
    public static function CheckSignIn(string $Response): int|string
    {
      $Challenge = self::Recall('signin');
      if ($Challenge === null || $Response === '')
      {
        return 'The passkey request expired, please try again.';
      }
      try
      {
        $Kept = self::Storage()->Passkey(\SC\Passkey::credentialId($Response));
        if ($Kept === null)
        {
          return 'That passkey isn\'t one of this site\'s. Sign in another way, then add it on Your profile.';
        }
        $Used = self::Checker()->verifyAuthentication($Response, $Challenge, $Kept['public_key'], (int)$Kept['sign_count']);
        self::Storage()->PasskeyUsed((int)$Kept['id'], (int)$Used['signCount'], (bool)$Used['backedUp']);
        return (int)$Kept['user_id'];
      }
      catch (\Exception $Error)
      {
        error_log('sc-webframe: a passkey sign-in failed: ' . $Error->getMessage());
        return 'That passkey didn\'t work. Please try again, or sign in another way.';
      }
    }

    /**
     * Options for a new passkey for the user (navigator.credentials.create()), leaving out the ones they have; its
     * challenge kept
     */
    public static function RegistrationOptions(int $UserID, string $Name, string $DisplayName): array
    {
      $Have = array_map(fn($Row) => ['id' => $Row['credential_id'], 'transports' => $Row['transports']], self::Storage()->Passkeys($UserID));
      $Options = self::Checker()->registrationOptions((string)$UserID, $Name, $DisplayName, $Have);
      self::Remember('register', $Options['challenge']);
      return $Options;
    }

    /**
     * Keeps a new passkey from the browser's answer (RegistrationResponseJSON) for the user
     *
     * @return string|null Why it wasn't kept, or null
     */
    public static function Add(int $UserID, string $Response, string $Name): ?string
    {
      $Challenge = self::Recall('register');
      if ($Challenge === null || $Response === '')
      {
        return 'The passkey request expired, please try again.';
      }
      try
      {
        $Made = self::Checker()->verifyRegistration($Response, $Challenge);
      }
      catch (\Exception $Error)
      {
        error_log('sc-webframe: a new passkey was refused: ' . $Error->getMessage());
        return 'That passkey couldn\'t be added. Please try again.';
      }
      return self::Storage()->AddPasskey($UserID, $Made, mb_substr(trim($Name), 0, 100));
    }

    /**
     * The scripts the passkey buttons need: sc-passkey.js and js/passkey.js
     */
    public static function Scripts(): array
    {
      return [new \HTML\Script(Source: Config::FrameworkURL('js/sc-passkey.js')), new \HTML\Script(Source: Config::FrameworkURL('js/passkey.js'))];
    }

    /**
     * Sign in with a passkey: a button that asks the browser for one, and posts its answer to the login page
     */
    public static function AddButton(\HTML\Element $Into): void
    {
      foreach (self::Scripts() as $Script)
      {
        $Into->AddChild($Script);
      }
      $Into->AddChild($Button = new \BS\Button('Sign in with a passkey', self::ICON));
      $Button->Format->Margin(Side::All, PadSize::Pad2);
      $Button->Color(\BS\Color::Secondary);
      $Button->AddClass('passkey-signin');
      $Button->OnClick('passkeySignIn(this, ' . json_encode(Config::FrameworkURL('passkey.php') . '?action=signin', JSON_UNESCAPED_SLASHES) . ', '
        . json_encode(Config::FrameworkURL('login.php') . '?provider=' . self::PROVIDER, JSON_UNESCAPED_SLASHES) . ');');
    }

    /**
     * A token for the session cookie: the account, signed with our own key
     */
    public static function SessionToken(int $UserID, int $Days): JWT
    {
      return new JWT(['purpose' => 'passkey_session', 'uid' => $UserID], 86400 * $Days);
    }

    private static function SessionUser(string $Token): ?int
    {
      $Token = JWT::FromToken($Token);
      if ($Token === null || $Token->Expired() || $Token->Get('iss') !== Config::Origin() || $Token->Get('purpose') !== 'passkey_session')
      {
        return null;
      }
      return (int)$Token->Get('uid') ?: null;
    }

    protected function DoAccessToken(): JWT
    {
      // The browser's answer, posted by js/passkey.js
      if (\Post::Get('passkey') !== null && !self::$Posted)
      {
        self::$Posted = true;
        $Result = self::CheckSignIn((string)\Post::Get('passkey'));
        if (is_int($Result))
        {
          $Token = $this->SignInUser($Result);
          if (!$Token->Expired())
          {
            $Days = max(1, (int)Config::Get('auth.' . $Token->Get('provider') . '.session_days', 30));
            $Session = self::SessionToken($Result, $Days);
            $this->SetCookie('passkey_session', $Session, $Session->TTL());
          }
          return $Token;
        }
        $this->Problem = $Result;
        return parent::DoAccessToken();
      }

      // Renew the sign-in from the session cookie
      if (!empty($Session = $_COOKIE['passkey_session'] ?? null))
      {
        if (($UserID = self::SessionUser($Session)) !== null)
        {
          return $this->SignInUser($UserID);
        }
        $this->ClearCookie('passkey_session');
      }
      return parent::DoAccessToken();
    }

    // The sign-in token for the passkey's account, as its own provider makes it (a blocked user gets an expired one)
    private function SignInUser(int $UserID): JWT
    {
      $User = self::Storage()->UserSignIn($UserID);
      if ($User === null)
      {
        return new JWT([], -1);
      }
      if (\User::Blocked($User['access']))
      {
        self::$Blocked = true;
        $this->DoLogout(false);
        return new JWT([], -1);
      }
      return new JWT(['provider' => $User['provider'], 'userid' => (string)$User['user_id'], 'email' => $User['email'], 'firstname' => $User['first_name'],
        'lastname' => $User['last_name'], 'access' => $User['access']], 1800);
    }

    protected function DoLogout(bool $Reload = true): void
    {
      $this->ClearCookie('passkey_session');
      parent::DoLogout($Reload);
    }

    /**
     * The passkey's page: why the last try didn't work, and the button to try again (or sign in another way)
     */
    protected function ShowLogin(): void
    {
      \Page::AddContent($Card = new Card());
      $Card->Format->Center();
      $Card->Format->MaxWidth(ScreenSize::Small->Width());
      $Card->Format->Margin(Side::Top, PadSize::Pad4);
      $Card->AddHeader('Please sign in');
      if ($this->Problem !== null)
      {
        $Card->AddChild(new AlertError('Not signed in', $this->Problem));
      }
      self::AddButton($Card);
      self::AddHome($Card, Password::StepButton('Sign in another way', Icon::ArrowLeft, Config::FrameworkURL('login.php')));
    }
  }
