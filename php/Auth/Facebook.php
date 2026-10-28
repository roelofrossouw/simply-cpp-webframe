<?php

  namespace Auth;

  use Config;

  /**
   * Sign in with Facebook
   *
   * Facebook Login for the web has no ID token, so the user is read from the Graph API with the access token. The
   * token is kept in a cookie and checked against the API whenever the sign-in is renewed.
   *
   * Needs these settings in its section of the settings (auth.facebook, see Config):
   *   app_id     - The Meta app's App ID (Base::LoginURI() must be one of its valid OAuth redirect URIs)
   *   app_secret - That app's App secret
   *
   * @see https://developers.facebook.com/docs/facebook-login/guides/advanced/manual-flow
   */
  class Facebook extends Base
  {
    const ?string PROVIDER = 'facebook';
    const string TITLE = 'Facebook';
    const \BS\Icon ICON = \BS\Icon::Facebook;
    private const string GRAPH = 'https://graph.facebook.com/v21.0/';
    // Profile picture URL of the user last read from the Graph API, null for the default silhouette
    private ?string $Picture = null;

    public static function Configured(): bool
    {
      return !empty(Config::Get('auth.facebook.app_id')) && !empty(Config::Get('auth.facebook.app_secret'));
    }

    protected function ShowLogin(): void
    {
      $Parameters = [
        'client_id' => Config::Get('auth.facebook.app_id'),
        'redirect_uri' => $this->LoginURI(),
        'response_type' => 'code',
        'scope' => 'public_profile,email',
        'state' => $this->NewState(),
      ];
      $Target = 'https://www.facebook.com/v21.0/dialog/oauth?' . http_build_query($Parameters, encoding_type: PHP_QUERY_RFC3986);
      Config::LoadPage($Target, false);

      $Button = new \BS\Button('Sign in with Facebook', Href: $Target);
      $Button->Format->Margin(\BS\Side::All, \BS\PadSize::Pad4);
      \Page::AddContent($Button);
    }

    protected function DoLogout(bool $Reload = true): void
    {
      $this->ClearCookie('facebook_access_token');
      parent::DoLogout($Reload);
    }

    protected function DoAccessToken(): JWT
    {
      // Returning from Facebook's sign-in page
      if (!empty($Code = self::Returned('code')) && $this->CheckState())
      {
        $Result = self::Graph('oauth/access_token', [
          'client_id' => Config::Get('auth.facebook.app_id'),
          'client_secret' => Config::Get('auth.facebook.app_secret'),
          'redirect_uri' => $this->LoginURI(),
          'code' => $Code,
        ]);
        if (!empty($AccessToken = $Result['access_token'] ?? null) && !empty($UserInfo = $this->UserInfo($AccessToken)))
        {
          $this->SetCookie('facebook_access_token', $AccessToken, (int)($Result['expires_in'] ?? 60 * 24 * 60 * 60));
          $Token = $this->CreateAuthToken($UserInfo);
          // Use their Facebook profile picture unless they chose their own photo, only on a real sign-in (not every renewal)
          if (!empty($UserID = $Token->Get('userid')) && !empty($this->Picture))
          {
            \User::UseProviderPhoto((int)$UserID, $this->Picture);
          }
          return $Token;
        }
      }

      // Renew the sign-in, the Graph API refuses the token once it expired or the user removed the app
      if (!empty($AccessToken = $_COOKIE['facebook_access_token'] ?? null))
      {
        if (!empty($UserInfo = $this->UserInfo($AccessToken)))
        {
          return $this->CreateAuthToken($UserInfo);
        }
        $this->ClearCookie('facebook_access_token');
      }
      return parent::DoAccessToken();
    }

    /**
     * The Facebook user for an access token, or an empty array
     */
    private function UserInfo(string $AccessToken): array
    {
      $User = self::Graph('me', [
        'fields' => 'id,first_name,last_name,email,picture.width(256).height(256)',
        'access_token' => $AccessToken,
        // Proves the call comes from us, so a token stolen from the browser can't be used elsewhere with our app
        'appsecret_proof' => hash_hmac('sha256', $AccessToken, Config::Get('auth.facebook.app_secret')),
      ]);
      if (empty($User['id']))
      {
        return [];
      }
      // Facebook's default silhouette isn't a photo of them
      $Picture = $User['picture']['data'] ?? [];
      $this->Picture = empty($Picture['is_silhouette']) ? ($Picture['url'] ?? null) : null;
      return [
        'sub' => $User['id'], // App-scoped user ID
        'email' => $User['email'] ?? null, // Missing when the user has no confirmed email or declined it
        'firstname' => $User['first_name'] ?? null,
        'lastname' => $User['last_name'] ?? null,
      ];
    }

    private static function Graph(string $Path, array $Query): array
    {
      $Exec = new \Rest(self::GRAPH . $Path);
      $Result = $Exec->Get($Query);
      return is_array($Result) ? $Result : [];
    }
  }
