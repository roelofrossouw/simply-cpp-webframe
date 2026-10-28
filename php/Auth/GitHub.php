<?php

  namespace Auth;

  use Config;

  /**
   * Sign in with GitHub
   *
   * GitHub has no ID token, so the user is read from the API with the access token. OAuth app tokens don't expire, the
   * token is kept in a cookie and checked against the API whenever the sign-in is renewed.
   *
   * Needs these settings in its section of the settings (auth.github, see Config):
   *   client_id     - The OAuth app's Client ID (its callback URL must be Base::LoginURI())
   *   client_secret - A client secret of that OAuth app
   *
   * @see https://docs.github.com/en/apps/oauth-apps/building-oauth-apps/authorizing-oauth-apps
   */
  class GitHub extends Base
  {
    const ?string PROVIDER = 'github';
    const string TITLE = 'GitHub';
    const \BS\Icon ICON = \BS\Icon::Github;
    private const int TOKEN_TTL = 90 * 24 * 60 * 60;
    // Avatar URL of the user last read from the API
    private ?string $Avatar = null;

    public static function Configured(): bool
    {
      return !empty(Config::Get('auth.github.client_id')) && !empty(Config::Get('auth.github.client_secret'));
    }

    protected function ShowLogin(): void
    {
      $Parameters = [
        'client_id' => Config::Get('auth.github.client_id'),
        'redirect_uri' => $this->LoginURI(),
        'scope' => 'read:user user:email',
        'state' => $this->NewState(),
        'allow_signup' => 'false',
      ];
      $Target = 'https://github.com/login/oauth/authorize?' . http_build_query($Parameters, encoding_type: PHP_QUERY_RFC3986);
      Config::LoadPage($Target, false);

      $Button = new \BS\Button('Sign in with GitHub', Href: $Target);
      $Button->Format->Margin(\BS\Side::All, \BS\PadSize::Pad4);
      \Page::AddContent($Button);
    }

    protected function DoLogout(bool $Reload = true): void
    {
      $this->ClearCookie('github_access_token');
      parent::DoLogout($Reload);
    }

    protected function DoAccessToken(): JWT
    {
      // Returning from GitHub's sign-in page
      if (!empty($Code = self::Returned('code')) && $this->CheckState())
      {
        $Exec = new \Rest('https://github.com/login/oauth/access_token');
        $Result = $Exec->Post([
          'client_id' => Config::Get('auth.github.client_id'),
          'client_secret' => Config::Get('auth.github.client_secret'),
          'code' => $Code,
          'redirect_uri' => $this->LoginURI(),
        ]);
        if (!empty($AccessToken = $Result['access_token'] ?? null) && !empty($UserInfo = $this->UserInfo($AccessToken)))
        {
          $this->SetCookie('github_access_token', $AccessToken, self::TOKEN_TTL);
          $Token = $this->CreateAuthToken($UserInfo);
          // Use their GitHub avatar unless they chose their own photo, only on a real sign-in (not every renewal)
          if (!empty($UserID = $Token->Get('userid')) && !empty($this->Avatar))
          {
            \User::UseProviderPhoto((int)$UserID, $this->Avatar . (str_contains($this->Avatar, '?') ? '&' : '?') . 's=256');
          }
          return $Token;
        }
      }

      // Renew the sign-in, the API refuses the token once the user revoked the app
      if (!empty($AccessToken = $_COOKIE['github_access_token'] ?? null))
      {
        if (!empty($UserInfo = $this->UserInfo($AccessToken)))
        {
          return $this->CreateAuthToken($UserInfo);
        }
        $this->ClearCookie('github_access_token');
      }
      return parent::DoAccessToken();
    }

    /**
     * The GitHub user for an access token, or an empty array
     */
    private function UserInfo(string $AccessToken): array
    {
      $User = self::API('user', $AccessToken);
      if (empty($User['id']))
      {
        return [];
      }
      // The profile email is only set when the user made it public, ask for their primary verified address
      $Email = $User['email'] ?? null;
      foreach (self::API('user/emails', $AccessToken) as $Address)
      {
        if (!empty($Address['primary']) && !empty($Address['verified']))
        {
          $Email = $Address['email'];
        }
      }
      $Name = explode(' ', trim((string)($User['name'] ?? '')), 2);
      $this->Avatar = $User['avatar_url'] ?? null;
      return [
        'sub' => (string)$User['id'], // The login can be renamed, the id stays
        'email' => $Email,
        'firstname' => ($Name[0] ?: null) ?? $User['login'],
        'lastname' => $Name[1] ?? null,
      ];
    }

    private static function API(string $Path, string $AccessToken): array
    {
      $Exec = new \Rest('https://api.github.com/' . $Path);
      $Exec->Accept('application/vnd.github+json');
      $Exec->SetHeader('User-Agent', Config::AppName() ?: 'sc-webframe'); // GitHub refuses requests without one
      $Exec->Bearer($AccessToken);
      $Result = json_decode((string)$Exec->Get(), true);
      return is_array($Result) ? $Result : [];
    }
  }
