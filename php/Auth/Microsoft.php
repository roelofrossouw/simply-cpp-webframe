<?php

  namespace Auth;

  use Config;

  /**
   * Sign in with Microsoft (Office 365 / Entra ID work and school accounts)
   *
   * Needs these settings in its section of the settings (auth.microsoft, see Config):
   *   client_id     - The app registration's Application (client) ID (its redirect URI must be Base::LoginURI())
   *   client_secret - A client secret of that app registration
   *   tenant        - The Directory (tenant) ID to accept, or 'organizations' for any work or school account
   *
   * @see https://learn.microsoft.com/en-us/entra/identity-platform/v2-protocols-oidc
   */
  class Microsoft extends Base
  {
    const ?string PROVIDER = 'microsoft';
    const string TITLE = 'Microsoft';
    const \BS\Icon ICON = \BS\Icon::Microsoft;
    private const int REFRESH_TTL = 90 * 24 * 60 * 60;
    private const string SCOPE = 'openid profile email offline_access';
    // Interactive sign-ins also ask to read the profile, for the user's photo (Microsoft Graph User.Read)
    private const string SIGN_IN_SCOPE = self::SCOPE . ' User.Read';

    public static function Configured(): bool
    {
      foreach (['auth.microsoft.client_id', 'auth.microsoft.client_secret', 'auth.microsoft.tenant'] as $Setting)
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
        'response_mode' => 'form_post',
        'client_id' => Config::Get('auth.microsoft.client_id'),
        'redirect_uri' => $this->LoginURI(),
        'scope' => self::SIGN_IN_SCOPE,
        'state' => $this->NewState(),
      ];
      $Target = self::Endpoint('authorize') . '?' . http_build_query($Parameters, encoding_type: PHP_QUERY_RFC3986);
      Config::LoadPage($Target, false);

      $Button = new \BS\Button('Sign in with Microsoft', Href: $Target);
      $Button->Format->Margin(\BS\Side::All, \BS\PadSize::Pad4);
      \Page::AddContent($Button);
    }

    protected function DoLogout(bool $Reload = true): void
    {
      $this->ClearCookie('microsoft_refresh_token');
      parent::DoLogout($Reload);
    }

    protected function DoAccessToken(): JWT
    {
      // Returning from Microsoft's sign-in page
      if (!empty($Code = \Post::Get('code')) && $this->CheckState())
      {
        $Result = $this->Token([
          'grant_type' => 'authorization_code',
          'code' => $Code,
          'redirect_uri' => $this->LoginURI(),
        ], self::SIGN_IN_SCOPE);
        $Token = $this->SignIn($Result);
        // The Microsoft 365 profile photo, unless the user has their own
        // @see https://learn.microsoft.com/en-us/graph/api/profilephoto-get
        if (!empty($UserID = $Token->Get('userid')) && !empty($Result['access_token']))
        {
          \User::UseProviderPhoto((int)$UserID, 'https://graph.microsoft.com/v1.0/me/photo/$value', ['Authorization: Bearer ' . $Result['access_token']]);
        }
        return $Token;
      }

      // Renew the sign-in, Microsoft says whether the account is still allowed in
      if (!empty($RefreshToken = $_COOKIE['microsoft_refresh_token'] ?? null))
      {
        $Token = $this->SignIn($this->Token(['grant_type' => 'refresh_token', 'refresh_token' => $RefreshToken]));
        if (empty($Token->Get('userid')))
        {
          $this->ClearCookie('microsoft_refresh_token'); // Revoked, disabled or expired
        }
        return $Token;
      }
      return parent::DoAccessToken();
    }

    private function SignIn(array $Result): JWT
    {
      $UserInfo = $this->UserInfo($Result['id_token'] ?? null);
      if (empty($UserInfo))
      {
        return parent::DoAccessToken();
      }
      // Microsoft rotates refresh tokens, keep the newest
      if (!empty($Result['refresh_token']))
      {
        $this->SetCookie('microsoft_refresh_token', $Result['refresh_token'], self::REFRESH_TTL);
      }
      return $this->CreateAuthToken($UserInfo);
    }

    /**
     * Details from a validated Microsoft ID token, or an empty array
     */
    private function UserInfo(?string $IDToken): array
    {
      $Token = JWT::FromToken($IDToken ?? 'null');
      $Tenant = Config::Get('auth.microsoft.tenant');
      if ($Token->Get('aud') !== Config::Get('auth.microsoft.client_id') || empty($Token->Get('sub'))
        || $Token->Get('iss') !== 'https://login.microsoftonline.com/' . $Token->Get('tid') . '/v2.0'
        || ($Tenant != 'organizations' && $Token->Get('tid') !== $Tenant))
      {
        return [];
      }
      // Work accounts often have no email claim, their sign-in name is their email address
      $Name = explode(' ', (string)$Token->Get('name'), 2);
      return [
        'sub' => $Token->Get('sub'),
        'email' => $Token->Get('email') ?? $Token->Get('preferred_username'),
        'firstname' => $Token->Get('given_name') ?? ($Name[0] ?: null),
        'lastname' => $Token->Get('family_name') ?? ($Name[1] ?? null),
      ];
    }

    private function Token(array $Data, string $Scope = self::SCOPE): array
    {
      $Exec = new \Rest(self::Endpoint('token'));
      $Exec->ContentType('application/x-www-form-urlencoded');
      $Result = $Exec->Post($Data + [
          'client_id' => Config::Get('auth.microsoft.client_id'),
          'client_secret' => Config::Get('auth.microsoft.client_secret'),
          'scope' => $Scope,
        ]);
      return is_array($Result) ? $Result : [];
    }

    private static function Endpoint(string $Name): string
    {
      return 'https://login.microsoftonline.com/' . rawurlencode(Config::Get('auth.microsoft.tenant')) . '/oauth2/v2.0/' . $Name;
    }
  }
