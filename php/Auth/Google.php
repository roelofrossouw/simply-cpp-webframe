<?php

  namespace Auth;

  use Config;

  /**
   * Sign in with Google
   *
   * Needs these settings in its section of the settings (auth.google, see Config):
   *   client_id     - The OAuth client ID (its redirect URI must be Base::LoginURI())
   *   client_secret - That client's secret
   *
   * @see https://developers.google.com/identity/openid-connect/openid-connect
   */
  class Google extends Base
  {
    const ?string PROVIDER = 'google';
    const string TITLE = 'Google';
    const \BS\Icon ICON = \BS\Icon::Google;
    private const array ISSUERS = ['accounts.google.com', 'https://accounts.google.com'];

    public static function Configured(): bool
    {
      return !empty(Config::Get('auth.google.client_id')) && !empty(Config::Get('auth.google.client_secret'));
    }

    protected function ShowLogin(): void
    {
      $Scopes = [
        'https://www.googleapis.com/auth/plus.me',
        'https://www.googleapis.com/auth/userinfo.profile',
        'https://www.googleapis.com/auth/userinfo.email'
      ];
      $URL = 'https://accounts.google.com/o/oauth2/v2/auth';
      $Parameters = [
        'response_type' => 'code',
        'response_mode' => 'form_post',
        'client_id' => Config::Get('auth.google.client_id'),
        'redirect_uri' => self::LoginURI(),
        'scope' => implode(' ', $Scopes),
        'access_type' => 'offline',
//        'prompt' => 'select_account',
        'state' => $this->NewState()
      ];
      $Target = $URL . '?' . http_build_query($Parameters);
      \Config::LoadPage($Target, false);

      $Button = new \BS\Button('Sign in with Google', Href: $Target);
      $Button->Format->Margin(\BS\Side::All, \BS\PadSize::Pad4);
      \Page::AddContent($Button);
    }

    protected function DoLogout(bool $Reload = true): void
    {
      if (!empty($AccessToken = ($_COOKIE['google_access_token'] ?? null)))
      {
        $this->ClearCookie('google_access_token');
        $this->ClearCookie('google_id_token');
        $Exec = new \Rest('https://oauth2.googleapis.com/revoke');
        $Exec->Post(['token' => $AccessToken]);
      }
      parent::DoLogout($Reload);
    }

    protected function DoAccessToken(): JWT
    {
      $UserInfo = $this->GetUserInfo();
      if (!empty($UserInfo))
      {
        return $this->CreateAuthToken($UserInfo);
      }
      $IDToken = null;
      if (!empty($AccessCode = \Post::Get('code')) && $this->CheckState())
      {
        $Exec = new \Rest('https://oauth2.googleapis.com/token');
        $Data = [
          'code' => $AccessCode,
          'client_id' => Config::Get('auth.google.client_id'),
          'client_secret' => Config::Get('auth.google.client_secret'),
          'redirect_uri' => self::LoginURI(),
          'grant_type' => 'authorization_code'
        ];
        $Result = $Exec->Post($Data);

        if (!empty($Result['access_token']))
        {
          $this->SetCookie('google_access_token', $Result['access_token'], $Result['expires_in'] ?? 3600);
        }
        if (!empty($Result['id_token']))
        {
          $this->SetCookie('google_id_token', $IDToken = $Result['id_token'], $Result['expires_in'] ?? 3600);
        }
      }
      $Token = $this->CreateAuthToken($this->GetUserInfo($IDToken));
      // A new sign-in: use the Google profile picture (256 pixels rather than the default 96), unless the user has their own
      if ($IDToken !== null && !empty($UserID = $Token->Get('userid')) && is_string($Picture = JWT::FromToken($IDToken)->Get('picture')))
      {
        \User::UseProviderPhoto((int)$UserID, preg_replace('/=s\d+-c$/', '=s256-c', $Picture));
      }
      return $Token;
    }


    public function GetUserInfo($IDToken = null): array
    {
      if (!empty($IDToken = ($IDToken ?? $_COOKIE['google_id_token'] ?? null)))
      {
        // Only accept ID tokens Google issued for this client, not for another app
        $Token = JWT::FromToken($IDToken);
        if (in_array($Token->Get('iss'), self::ISSUERS, true) && $Token->Get('aud') === Config::Get('auth.google.client_id'))
        {
          $Result = $Token->GetPayload();
        }
      }
      if (empty($Result) && !empty($AccessToken = $_COOKIE['google_access_token'] ?? null))
      {
        $Exec = new \Rest('https://openidconnect.googleapis.com/v1/userinfo');
        $Exec->Bearer($AccessToken);
        $Result = $Exec->Get();
      }
      if (!empty($Result))
      {
        return [
          'sub' => $Result['sub'],
          'firstname' => $Result['given_name'],
          'lastname' => $Result['family_name'],
          // "fullname" => $Result['name'],
          // "sub" => $Result['sub'],
          // "picture" => $Result['picture'],
          'email' => $Result['email']
        ];
      }
      return [];
    }

//    protected function CreateRefreshToken(): JWT
//    {
//      $Expiry = 30 * 24 * 60 * 60;
//      return new JWT(['sub' => self::ID(), 'type' => 'refresh'], $Expiry);
//    }
//
//    protected function RefreshAuth($RefreshToken): JWT
//    {
//      // TODO: Check $RefreshToken validity...
//      $Credentials['userid'] = $RefreshToken->Get('sub');
//      // return new JWT($Credentials, 10);
//      return new JWT([], -10);
//    }

  }
