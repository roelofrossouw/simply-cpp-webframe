<?php

  namespace Auth;

  use Config;

  /**
   * Sign in with Apple
   *
   * Needs these settings in its section of the settings (auth.apple, see Config):
   *   client_id - The Services ID registered for this site (its return URL must be Base::LoginURI())
   *   team_id   - The Apple Developer Team ID
   *   key_id    - The ID of a key with Sign in with Apple enabled
   *   key_file  - File name of that key (.p8) in auth.keys_dir (see JWT)
   *
   * @see https://developer.apple.com/documentation/signinwithapplerestapi
   */
  class Apple extends Base
  {
    const ?string PROVIDER = 'apple';
    const string TITLE = 'Apple';
    const \BS\Icon ICON = \BS\Icon::Apple;
    private const string ISSUER = 'https://appleid.apple.com';
    // Apple throttles token validation to about once a day, so we keep our own proof of the sign-in for that long
    private const int SESSION_TTL = 24 * 60 * 60;
    private const int REFRESH_TTL = 180 * 24 * 60 * 60;

    public static function Configured(): bool
    {
      foreach (['auth.apple.client_id', 'auth.apple.team_id', 'auth.apple.key_id', 'auth.apple.key_file'] as $Setting)
      {
        if (empty(Config::Get($Setting)))
        {
          return false;
        }
      }
      return true;
    }

    protected function ShowLogin(): void
    {
      $Parameters = [
        'response_type' => 'code',
        'response_mode' => 'form_post', // Required when asking for name or email
        'client_id' => Config::Get('auth.apple.client_id'),
        'redirect_uri' => $this->LoginURI(),
        'scope' => 'name email',
        'state' => $this->NewState(),
      ];
      // Apple wants spaces as %20, not +
      $Target = self::ISSUER . '/auth/authorize?' . http_build_query($Parameters, encoding_type: PHP_QUERY_RFC3986);
      Config::LoadPage($Target, false);

      $Button = new \BS\Button('Sign in with Apple', Href: $Target);
      $Button->Format->Margin(\BS\Side::All, \BS\PadSize::Pad4);
      \Page::AddContent($Button);
    }

    protected function DoLogout(bool $Reload = true): void
    {
      $this->ClearCookie('apple_session');
      $this->ClearCookie('apple_refresh_token');
      parent::DoLogout($Reload);
    }

    protected function DoAccessToken(): JWT
    {
      // Returning from Apple's sign-in page
      if (!empty($Code = \Post::Get('code')) && $this->CheckState())
      {
        $Result = $this->Token([
          'grant_type' => 'authorization_code',
          'code' => $Code,
          'redirect_uri' => $this->LoginURI(),
        ]);
        if (!empty($Result['refresh_token']))
        {
          $this->SetCookie('apple_refresh_token', $Result['refresh_token'], self::REFRESH_TTL);
        }
        // Apple only sends the user's name the first time they sign in
        $Name = json_decode(\Post::Get('user') ?? '', true)['name'] ?? [];
        return $this->SignIn($this->UserInfo($Result['id_token'] ?? null, $Name));
      }

      // Signed in with Apple during the last day
      $Session = JWT::FromToken($_COOKIE['apple_session'] ?? 'null');
      if ($Session->Get('typ') == 'apple_session' && !empty($Session->Get('sub')))
      {
        return $this->CreateAuthToken(['sub' => $Session->Get('sub'), 'email' => $Session->Get('email')]);
      }

      // Ask Apple whether the user's sign-in is still valid
      if (!empty($RefreshToken = $_COOKIE['apple_refresh_token'] ?? null))
      {
        $Result = $this->Token(['grant_type' => 'refresh_token', 'refresh_token' => $RefreshToken]);
        if (!empty($UserInfo = $this->UserInfo($Result['id_token'] ?? null)))
        {
          return $this->SignIn($UserInfo);
        }
        $this->ClearCookie('apple_refresh_token'); // Revoked by the user, or expired
      }
      return parent::DoAccessToken();
    }

    private function SignIn(array $UserInfo): JWT
    {
      if (empty($UserInfo))
      {
        return parent::DoAccessToken();
      }
      $Session = new JWT(['typ' => 'apple_session', 'sub' => $UserInfo['sub'], 'email' => $UserInfo['email']], self::SESSION_TTL);
      $this->SetCookie('apple_session', $Session, self::SESSION_TTL);
      return $this->CreateAuthToken($UserInfo);
    }

    /**
     * Details from a validated Apple ID token, or an empty array
     */
    private function UserInfo(?string $IDToken, array $Name = []): array
    {
      $Token = JWT::FromToken($IDToken ?? 'null');
      if ($Token->Get('iss') !== self::ISSUER || $Token->Get('aud') !== Config::Get('auth.apple.client_id') || empty($Token->Get('sub')))
      {
        return [];
      }
      return [
        'sub' => $Token->Get('sub'),
        'email' => $Token->Get('email'), // Can be a private relay address
        'firstname' => $Name['firstName'] ?? null,
        'lastname' => $Name['lastName'] ?? null,
      ];
    }

    private function Token(array $Data): array
    {
      $Exec = new \Rest(self::ISSUER . '/auth/token');
      $Exec->ContentType('application/x-www-form-urlencoded');
      $Result = $Exec->Post($Data + [
          'client_id' => Config::Get('auth.apple.client_id'),
          'client_secret' => $this->ClientSecret(),
        ]);
      return is_array($Result) ? $Result : [];
    }

    /**
     * Apple's client secret is a short-lived ES256 JWT signed with our Sign in with Apple key
     */
    private function ClientSecret(): string
    {
      $Secret = new JWT([
        'iss' => Config::Get('auth.apple.team_id'),
        'iat' => time(),
        'exp' => time() + 300,
        'aud' => self::ISSUER,
        'sub' => Config::Get('auth.apple.client_id'),
      ]);
      return (string)$Secret->SignWith(Config::Get('auth.apple.key_file'), 'ES256', Config::Get('auth.apple.key_id'));
    }
  }
