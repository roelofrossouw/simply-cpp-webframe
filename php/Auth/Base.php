<?php

  namespace Auth;

  use BS\PadSize;
  use BS\Side;
  use Config;

  abstract class Base
  {
    const array COMMON_FIELDS = ['firstname', 'lastname', 'email', 'userid'];
    // Sign-in options, by the name used in ?provider= and the state parameter
    const array PROVIDERS = [
      'password' => Password::class,
      'google' => Google::class,
      'microsoft' => Microsoft::class,
      'apple' => Apple::class,
      'github' => GitHub::class,
      'facebook' => Facebook::class,
    ];
    // Name of the provider, users are recorded in the database by it
    const ?string PROVIDER = null;
    const string TITLE = '';
    const \BS\Icon ICON = \BS\Icon::Person;
    private static Base $Shared;
    // True when the request picked a provider (or returns from one), otherwise the login page offers the choice
    private static bool $Chosen = false;
    // True when the user signed in with a provider, but their public access is revoked
    private static bool $Blocked = false;
    protected JWT $AuthToken;
    private array $Data;

    public function __construct()
    {
      $this->AuthToken = JWT::FromHeader();
      foreach (self::COMMON_FIELDS as $Field)
      {
        $this->Data[$Field] = $this->AuthToken->Get($Field);
      }
    }

    public static function Factory(Base $Instance): void
    {
      self::$Shared = $Instance;
    }

    /**
     * The provider for this request: the one being returned from, the one asked for, or the one last signed in with
     */
    public static function ForRequest(): Base
    {
      $Name = (string)(explode('.', self::Returned('state') ?? '')[0] ?: \QueryString::Get('provider'));
      self::$Chosen = isset(self::PROVIDERS[$Name]) && self::PROVIDERS[$Name]::Configured();
      if (!self::$Chosen)
      {
        $Name = $_COOKIE['auth_provider'] ?? array_key_first(self::Providers()) ?? 'google';
      }
      $Class = self::PROVIDERS[$Name] ?? Google::class;
      return new $Class();
    }

    public static function Configured(): bool
    {
      return true;
    }

    /**
     * Sign-in options that are set up, by the name used in ?provider=
     *
     * @return array<string, class-string<Base>>
     */
    public static function Providers(): array
    {
      return array_filter(self::PROVIDERS, fn($Class) => $Class::Configured());
    }

    public static function FirstName(): ?string
    {
      return self::$Shared->GetData('firstname');
    }

    private function GetData($Key, $MissingValue = 'Unknown'): ?string
    {
      return $this->Data[$Key] ?? $this->AuthToken->Get($Key) ?? $MissingValue;
    }

    public static function LastName(): ?string
    {
      return self::$Shared->GetData('lastname');
    }

    public static function Email(): ?string
    {
      return self::$Shared->GetData('email');
    }

    public static function SystemAdmin(): bool
    {
      return self::HasAccess('admin');
    }

    /**
     * The access codes that may use a page, from the menu setup (Administer → Menu)
     *
     * A page that isn't in the menu itself, like a detail page or an update endpoint, names the menu page it belongs
     * to. A page that's in no menu item gets no codes, so nobody may use it.
     *
     * @param string|null $Page The menu page, e.g. /app/users.php; the current page when null
     * @param bool $Edit The codes with the menu's Edit right, rather than View
     */
    public static function RolesAllowed(?string $Page = null, bool $Edit = false): array
    {
      return \MenuLayout::Roles($Page ?? \Server::Page(), $Edit);
    }

    /**
     * Whether the signed in user has one of the access codes
     *
     * @param bool $Current Check the grants in the database instead of the sign-in token, which is up to 30 minutes old.
     *   For pages that change things, so access that was just revoked stops working straight away.
     */
    public static function HasRole(array $Roles, bool $Current = false): bool
    {
      if (!isset(self::$Shared) || empty($ID = self::ID()))
      {
        return false;
      }
      $Access = $Current ? \User::Access((int)$ID) : (array)self::$Shared->AuthToken->Get('access');
      return !empty(array_intersect($Roles, $Access));
    }

    /**
     * Whether the signed in user may use a page, as set up in the menu, see RolesAllowed(). Config::Init() checks it for
     * every page.
     */
    public static function Allowed(?string $Page = null, bool $Current = false): bool
    {
      return \MenuLayout::IsPublic($Page ?? \Server::Page()) || self::HasRole(self::RolesAllowed($Page), $Current);
    }

    /**
     * Whether the signed in user may change things on a page, as set up in the menu (its Edit rights)
     *
     * Reads the grants from the database by default, so a right that was just taken away stops working straight away.
     */
    public static function CanEdit(?string $Page = null, bool $Current = true): bool
    {
      return self::HasRole(self::RolesAllowed($Page, true), $Current);
    }

    /**
     * Whether the signed in user was given any access beyond public, new users only see the home page until then
     */
    public static function Approved(): bool
    {
      return isset(self::$Shared) && !empty(array_diff((array)self::$Shared->AuthToken->Get('access'), ['public']));
    }

    /**
     * Whether the signed in user was granted the access code (see the access and grants tables)
     */
    public static function HasAccess(string $Code): bool
    {
      // Errors can be raised before Config::Init picked a provider
      if (!isset(self::$Shared))
      {
        return false;
      }
      return in_array($Code, (array)self::$Shared->AuthToken->Get('access'), true);
    }

    public static function Logout(): void
    {
      self::$Shared->ShowLogout();
    }

    private function ShowLogout(): void
    {
      if ($this->IsLoggedIn())
      {
        $Header = 'Signing you out...';
        $Container = new \HTML\Paragraph('Signing out...');
        $Container = new \BS\AlertWarning($Header, $Container);
        $Container->Format->Margin(Side::All, PadSize::Pad5);
        $Container->Color(\BS\Color::Light); // Show a slight background
        $Container->AddChild(new \BS\PlaceHolder([3, 4, 1]));
        $Container->AddChild(new \BS\PlaceHolder([5, 3]));
        $Container->AddChild(new \BS\PlaceHolder([4]));
        $this->DoLogout(true);
      }
      else
      {
        $this->DoLogout(false);
        $Container = new \BS\AlertWarning('Signed out', 'You are signed out, refreshing the site...');
        Config::LoadPage(Config::Origin());
      }
      \Page::AddContent($Container);
    }

    private function IsLoggedIn(bool $FirstTry = true): bool
    {
      if (!empty($this->ID()) && !$this->AuthToken->Expired())
      {
        return true;
      }
      if ($FirstTry && \Server::Page() != $this->LogoutURI()) // Retry for SSO
      {
        if (!$this->AccessToken())
        {
          $this->DoRefreshToken();
        }
        return $this->IsLoggedIn(false);
      }
      return false;
    }

    public static function ID(): ?string
    {
      return isset(self::$Shared) ? self::$Shared->GetData('userid', null) : null;
    }

    private function LogoutURI(): string
    {
      return Config::FrameworkURL('logout.php');
    }

    private function AccessToken(): bool
    {
      return $this->SetAuthToken($this->DoAccessToken());
    }

    protected function SetAuthToken(JWT $Token): bool
    {
      if ($Token->Expired())
      {
        return false;
      }
      $this->AuthToken = $Token;
      $this->SendAuthToken();
      return true;
    }

    private function SendAuthToken(): void
    {
      $this->SetCookie('auth_token', $this->AuthToken, $this->AuthToken->TTL());
      if (!empty(static::PROVIDER) && !empty($this->AuthToken->Get('userid')))
      {
        // Remember the provider after the auth token expires, so it can sign the user in again
        $this->SetCookie('auth_provider', static::PROVIDER, 180 * 24 * 60 * 60);
      }
    }

    protected function SetCookie($Name, $Data, $Expiry, string $SameSite = 'Strict'): void
    {
      setcookie($Name, $Data,
        [
          'expires' => time() + $Expiry,
          'path' => '/',
          'domain' => Config::Host(),
          'secure' => true,
          'httponly' => true,
          'samesite' => $SameSite
        ]);
    }

    /**
     * Creates the state parameter for a provider's sign-in page, and remembers it to check on return
     */
    protected function NewState(): string
    {
      $State = static::PROVIDER . '.' . bin2hex(random_bytes(16));
      // The provider posts back cross-site, so the cookie can't be SameSite Strict or Lax
      $this->SetCookie('oauth_state', $State, 600, 'None');
      return $State;
    }

    /**
     * Whether the state posted back by the provider is the one we sent (protects against login CSRF)
     */
    /**
     * A value the provider sent back: posted (response_mode=form_post) or in the query string (plain OAuth redirects)
     */
    protected static function Returned(string $Key): ?string
    {
      $Value = \Post::Get($Key) ?? \QueryString::Get($Key);
      return is_string($Value) ? $Value : null;
    }

    protected function CheckState(): bool
    {
      $Expected = $_COOKIE['oauth_state'] ?? '';
      $State = (string)self::Returned('state');
      if ($Expected === '')
      {
        return false;
      }
      $this->ClearCookie('oauth_state', 'None');
      return hash_equals($Expected, $State);
    }

    public static function TTL(): ?string
    {
      return self::$Shared->GetTTL();
    }

    private function GetTTL(): int
    {
      return $this->AuthToken->TTL();
    }

    protected function DoAccessToken(): JWT
    {
      return new JWT([], -1);
    }

    private function DoRefreshToken(): void
    {
      if (!empty($RefreshToken = $_COOKIE['refresh_token'] ?? null))
      {
        $RefreshToken = JWT::FromToken($RefreshToken);
        $this->SetAuthToken($this->RefreshAuth($RefreshToken));
        if ($this->IsLoggedIn(false))
        {
          $this->SetRefreshToken($this->CreateRefreshToken());
        }
      }
    }

    protected function RefreshAuth($RefreshToken): JWT
    {
      // TODO: Check $RefreshToken validity...
      return new JWT();
    }

    protected function SetRefreshToken(JWT $Token): void
    {
      $this->SetCookie('refresh_token', $Token, $Token->TTL());
    }

    protected function CreateRefreshToken(): JWT
    {
      return new JWT();
    }

    protected function DoLogout(bool $Reload = true): void
    {
      $this->Data = [];
      $this->AuthToken = new JWT();
      $this->ClearCookie('auth_token');
      $this->ClearCookie('refresh_token');
      $this->ClearCookie('auth_provider');
      session_destroy();
      if ($Reload)
      {
        Config::LoadPage($this->LogoutURI(), false, 500);
      }
    }

    protected function ClearCookie($Name, string $SameSite = 'Strict'): void
    {
      setcookie($Name, 'null',
        [
          'expires' => time() - 3600,
          'path' => '/',
          'domain' => Config::Host(),
          'secure' => true,
          'httponly' => true,
          'samesite' => $SameSite
        ]);
    }

    public static function TryRefresh(): void
    {
      self::$Shared->DoRefreshToken();
    }

    public static function LoggedIn(): bool
    {
      return self::$Shared->IsLoggedIn();
    }

    public static function Login()
    {
      self::$Shared->DoLogin();
    }

    private function DoLogin()
    {
      if ($this->IsLoggedIn())
      {
        Config::LoadPage(Config::Origin(), false);
        return;
      }
      if (self::$Blocked)
      {
        // Don't send them back to the provider, it would sign them in again and loop
        \Page::AddContent($Alert = new \BS\AlertError('Sign-in blocked',
          'Your access to ' . Config::AppName() . ' has been revoked. Please contact an administrator.'));
        $Alert->Format->Margin(Side::All, PadSize::Pad5);
        return;
      }
      if (self::$Chosen)
      {
        $this->ShowLogin();
      }
      else
      {
        $this->ShowProviders();
      }
    }

    /**
     * The foot of a sign-in card: Home when it's a page of its own (e.g. back from a provider, or a link from an email),
     * without the menu (in the app the menu is there already), and the card's own button, e.g. Next, on the right
     */
    public static function AddHome(\BS\Card $Card, ?\HTML\Element $Next = null): void
    {
      $Standalone = !\Server::IsAJAX();
      if (!$Standalone && $Next === null)
      {
        return;
      }
      $Card->AddFooter($Row = new \HTML\Div());
      $Row->AddClasses(['d-flex', 'justify-content-between', 'align-items-center', 'gap-2']);
      if ($Standalone)
      {
        $Row->AddChild($Home = new \BS\Button('Home', \BS\Icon::House));
        $Home->Link(Config::Origin());
        $Home->Color(\BS\Color::Secondary);
      }
      if ($Next !== null)
      {
        $Row->AddChild($Next);
        $Next->AddClass('ms-auto');
      }
    }

    private function ShowProviders(): void
    {
      \Page::AddContent($Card = new \BS\Card());
      $Card->Format->Center();
      $Card->Format->MaxWidth(\BS\ScreenSize::Small->Width());
      $Card->AddHeader('Please sign in');
      $Footed = false;
      foreach (self::Providers() as $Name => $Class)
      {
        // The password's first step itself (its Next, with Home, in the foot), the others' buttons below it
        if ($Class === Password::class)
        {
          (new Password())->AddEmailStep($Card);
          $Footed = true;
          continue;
        }
        $Card->AddChild($Button = new \BS\Button('Sign in with ' . $Class::TITLE, $Class::ICON, $this->LoginURI() . '?provider=' . $Name));
        $Button->Format->Margin(\BS\Side::All, \BS\PadSize::Pad2);
      }
      if (!$Footed)
      {
        self::AddHome($Card);
      }
    }

    protected function ShowLogin(): void
    {
      echo "Not implemented";
    }

    public static function Shared(): self
    {
      return self::$Shared;
    }

    public function Verified(): bool
    {
      return true;
    }

    public function LoginURI(): string
    {
      return Config::Origin() . Config::FrameworkURL('login.php');
    }

    protected function ProcessLogin(array $Credentials): void
    {
      $this->SetAuthToken($this->CreateAuthToken($Credentials));
      $this->SetRefreshToken($this->CreateRefreshToken());

      if ($this->IsLoggedIn())
      {
        $Header = 'Signing you in...';
        $Container = new \HTML\Paragraph('Signing in...');
        $Container = new \BS\Alert($Header, $Container);
        $Container->Format->Margin(Side::All, PadSize::Pad5);
        $Container->Color(\BS\Color::Light); // Show a slight background
        $Container->AddChild(new \BS\PlaceHolder([3, 4, 1]));
        $Container->AddChild(new \BS\PlaceHolder([5, 3]));
        $Container->AddChild(new \BS\PlaceHolder([4]));
        \Page::AddContent($Container);
        Config::LoadPage(Config::Origin(), false, 500);
      }
      else
      {
        sleep(2);
        $Header = 'Error';
        $Container = new \HTML\Paragraph('Could not sign you in. Please check your credentials and try again.');
        $Container = new \BS\AlertError($Header, $Container);
        $Container->Format->Margin(Side::All, PadSize::Pad5);
        \Page::AddContent($Container);
        $this->ShowLogin(true);
      }
    }

    protected function CreateAuthToken($Credentials): JWT
    {
      // Providers give their own subject, record the user and use our user ID and access instead
      if (!empty(static::PROVIDER) && !empty($Credentials['sub']))
      {
        $User = \User::Register(static::PROVIDER, $Credentials['sub'], $Credentials['email'] ?? null,
          $Credentials['firstname'] ?? null, $Credentials['lastname'] ?? null);
        if (\User::Blocked($User['access']))
        {
          // Sign out of the provider as well, so its cookies don't sign them in again on every request
          self::$Blocked = true;
          $this->DoLogout(false);
          return new JWT([], -1);
        }
        $Credentials = ['provider' => static::PROVIDER] + $User + $Credentials;
      }
      return new JWT($Credentials, 1800);
    }

  }
