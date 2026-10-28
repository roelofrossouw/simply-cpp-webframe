<?php

  use Storage\Store;

  /**
   * The signed in people: their access, profile and photo, kept by the storage (see Storage\Backend)
   */
  class User
  {
    /**
     * Records a sign-in and returns the user's details for the auth token
     *
     * Creates the user on their first sign-in, with public access. Missing names or email (Apple only sends the name
     * the first time) keep the values already stored. A revoked public grant stays revoked, see Blocked().
     *
     * @param string $Provider Name of the identity provider, e.g. 'google' or 'apple'
     * @param string $Subject The provider's stable identifier for the user ('sub' claim)
     * @return array userid, email, firstname, lastname and access (list of granted access codes)
     */
    public static function Register(string $Provider, string $Subject, ?string $Email = null, ?string $FirstName = null, ?string $LastName = null): array
    {
      // Made or updated, with public access granted, in one go
      $User = Store::Get()->SignIn($Provider, $Subject, $Email, $FirstName, $LastName);
      if (empty($User['user_id']))
      {
        throw new RuntimeException('Could not record the user');
      }

      return [
        'userid' => (string)$User['user_id'],
        'email' => $User['email'],
        'firstname' => $User['first_name'],
        'lastname' => $User['last_name'],
        'access' => $User['access'],
      ];
    }

    /**
     * Access codes granted to a user and not revoked, e.g. ['public', 'admin']
     */
    public static function Access(int $UserID): array
    {
      return Store::Get()->Access($UserID);
    }

    /**
     * Every user, with the access they have, for an admin page
     */
    public static function All(): array
    {
      return Store::Get()->Users();
    }

    /**
     * Whether the user may not sign in, i.e. their public access was revoked
     *
     * @param array $Access The user's granted access codes, see Access()
     */
    public static function Blocked(array $Access): bool
    {
      return !in_array('public', $Access, true);
    }

    /**
     * Access levels that can be granted, by code, e.g. ['public' => 'Any signed in user', 'admin' => ...]
     */
    public static function Levels(): array
    {
      return Store::Get()->Levels();
    }

    /**
     * Grants or revokes an access level
     *
     * Revoking keeps the grant with the time it was revoked, granting again clears that. Revoking public blocks the
     * user from signing in.
     *
     * @return bool False when the user or access code doesn't exist
     */
    public static function SetAccess(int $UserID, string $Code, bool $Granted): bool
    {
      return Store::Get()->SetAccess($UserID, $Code, $Granted) === null;
    }

    // How the app can address a user, see DisplayName()
    const array DISPLAY_AS = ['first' => 'My first name', 'full' => 'My first name and surname', 'nickname' => 'A nickname'];

    /**
     * A user's profile: names, email, provider, display settings and photo (as a data: URL, or null)
     */
    public static function Profile(int $UserID): ?array
    {
      $Profile = Store::Get()->Profile($UserID);
      if (empty($Profile['user_id']))
      {
        return null;
      }
      unset($Profile['wants_provider_photo']);
      $Profile['names_from_user'] = $Profile['names_from_user'] === 't';
      $Profile['photo'] = empty($Profile['photo']) ? null : 'data:image/jpeg;base64,' . $Profile['photo'];
      return $Profile;
    }

    /**
     * The sign-in provider's name as people know it, e.g. 'GitHub' for 'github'
     */
    public static function ProviderTitle(string $Provider): string
    {
      $Class = \Auth\Base::PROVIDERS[$Provider] ?? null;
      return $Class === null ? ucfirst($Provider) : $Class::TITLE;
    }

    /**
     * What the app calls the user, as they chose: first name, full name or nickname (falling back to the email)
     */
    public static function DisplayName(array $Profile): string
    {
      $First = trim($Profile['first_name'] ?? '');
      $Full = trim($First . ' ' . ($Profile['last_name'] ?? ''));
      $Name = match ($Profile['display_as'] ?? 'first')
      {
        'nickname' => trim($Profile['nickname'] ?? ''),
        'full' => $Full,
        default => $First,
      };
      return $Name ?: $Full ?: ($Profile['email'] ?? '') ?: 'there';
    }

    /**
     * Whether the user may edit their name: only when the sign-in provider didn't give one
     */
    public static function CanEditName(array $Profile): bool
    {
      return $Profile['names_from_user'] || (empty($Profile['first_name']) && empty($Profile['last_name']));
    }

    /**
     * A password user's email address (other providers give theirs)
     *
     * @return bool False when it isn't an email address, or the user doesn't sign in with a password
     */
    public static function SetEmail(int $UserID, string $Email): bool
    {
      $Email = trim($Email);
      if ($Email !== '' && !filter_var($Email, FILTER_VALIDATE_EMAIL))
      {
        return false;
      }
      return Store::Get()->SetEmail($UserID, $Email === '' ? null : $Email) === null;
    }

    public static function SetDisplay(int $UserID, string $DisplayAs, ?string $Nickname = null): bool
    {
      if (!array_key_exists($DisplayAs, self::DISPLAY_AS))
      {
        return false;
      }
      $Nickname = trim($Nickname ?? '');
      return Store::Get()->SetDisplay($UserID, $DisplayAs, $Nickname === '' ? null : mb_substr($Nickname, 0, 50)) === null;
    }

    /**
     * Set names the sign-in provider didn't give, see CanEditName()
     */
    public static function SetName(int $UserID, string $FirstName, string $LastName): bool
    {
      $Profile = self::Profile($UserID);
      if ($Profile === null || !self::CanEditName($Profile))
      {
        return false;
      }
      return Store::Get()->SetName($UserID, trim($FirstName) ?: null, trim($LastName) ?: null) === null;
    }

    /**
     * Whether to use the sign-in provider's photo: the user has none of their own (and didn't remove it),
     * or the provider's is a day old
     */
    public static function WantsProviderPhoto(int $UserID): bool
    {
      return (Store::Get()->Profile($UserID)['wants_provider_photo'] ?? 'f') === 't';
    }

    /**
     * Use the sign-in provider's photo, when WantsProviderPhoto() says so. Never fails: a missing photo, slow
     * provider or unreadable image just leaves things as they were.
     *
     * @param string $URL     https address of the image, from the provider (its API or a signed ID token)
     * @param array  $Headers Request headers, e.g. ['Authorization: Bearer ...'] for Microsoft Graph
     */
    public static function UseProviderPhoto(int $UserID, string $URL, array $Headers = []): void
    {
      if (!str_starts_with($URL, 'https://') || !self::WantsProviderPhoto($UserID))
      {
        return;
      }
      $Curl = curl_init($URL);
      curl_setopt_array($Curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 3,
        CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
        CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS,
        CURLOPT_TIMEOUT => 4,
        CURLOPT_HTTPHEADER => $Headers,
        // Stop downloads larger than 5 MB
        CURLOPT_NOPROGRESS => false,
        CURLOPT_XFERINFOFUNCTION => fn($Curl, $Expected, $Received) => $Received > 5 * 1024 * 1024 ? 1 : 0,
      ]);
      $Photo = curl_exec($Curl);
      $Status = curl_getinfo($Curl, CURLINFO_RESPONSE_CODE);
      // e.g. 404 from Microsoft Graph when the account has no photo
      if ($Status === 200 && is_string($Photo) && $Photo !== '')
      {
        self::SetPhoto($UserID, $Photo, 'provider');
      }
    }

    /**
     * Store a photo as a 256x256 JPEG, cropped to a square from the centre
     *
     * @param string $Image  The image file's contents (JPEG, PNG, GIF or WebP)
     * @param string $Source 'user' for an upload, 'provider' for the sign-in provider's photo
     * @return bool False when it isn't an image GD can read
     */
    public static function SetPhoto(int $UserID, string $Image, string $Source = 'user'): bool
    {
      $Original = @imagecreatefromstring($Image);
      if ($Original === false)
      {
        return false;
      }
      $Size = min(imagesx($Original), imagesy($Original));
      $Photo = imagecreatetruecolor(256, 256);
      imagecopyresampled($Photo, $Original, 0, 0, intdiv(imagesx($Original) - $Size, 2), intdiv(imagesy($Original) - $Size, 2), 256, 256, $Size, $Size);
      ob_start();
      imagejpeg($Photo, null, 85);
      $Jpeg = ob_get_clean();
      return Store::Get()->SetPhoto($UserID, $Jpeg, $Source === 'provider' ? 'provider' : 'user') === null;
    }

    public static function RemovePhoto(int $UserID): bool
    {
      // 'none' so the sign-in provider's photo doesn't come back either
      return Store::Get()->RemovePhoto($UserID) === null;
    }
  }
