<?php

  namespace Auth;

  class JWT
  {
    // Key files are read from the settings' auth.keys_dir, else this folder
    const string KEYS_DIR = '/etc/sc-webframe/keys/';
    // Our own HS256 key: the settings' auth.secret, else this file in the keys folder
    const LOCAL_KEY = 'jwt.key';
    // The providers' public keys are kept in the settings' auth.cache_dir, else this folder (the package makes it, for
    // www-data); without a folder PHP may write to they are fetched for every sign-in
    const string CACHE_DIR = '/var/cache/sc-webframe/jwks';
    // Issuers whose RS256 tokens we accept, pinned to their JWKS endpoint.
    // Any other token must be HS256 signed with our own LOCAL_KEY.
    const TRUSTED_ISSUERS = [
      'accounts.google.com' => 'https://www.googleapis.com/oauth2/v3/certs',
      'https://accounts.google.com' => 'https://www.googleapis.com/oauth2/v3/certs',
      'https://appleid.apple.com' => 'https://appleid.apple.com/auth/keys',
    ];
    // Issuers that differ per tenant, by pattern. Microsoft signs every tenant's tokens with the same keys.
    const TRUSTED_ISSUER_PATTERNS = [
      '#^https://login\.microsoftonline\.com/[0-9a-f-]{36}/v2\.0$#' => 'https://login.microsoftonline.com/common/discovery/v2.0/keys',
    ];
    private array $Header;
    private array $Payload;
    private ?string $Signature;

    public function __construct(array $Payload = [], int $Expiry = 1800)
    {
      $this->Header = [
        'alg' => 'HS256',
        'oku' => self::LOCAL_KEY,
        'typ' => 'JWT',
      ];
      $this->Payload = $Payload;
      if (empty($Payload['exp']))
      {
        $this->Payload += [
          'iss' => \Config::Origin(),
          'iat' => time(),
          'exp' => time() + ($Expiry ?? 1800),
        ];
      }
      $this->Signature = null;
    }

    /**
     * @param $Header    array The header of the token
     * @param $Payload   array The payload of the token
     * @param $Secret    string The private key (Either a file or a string)
     * @return string
     */
    public static function Create(array $Header, array $Payload): string
    {
      $Algorithm = $Header['alg'] ?? 'HS256';
      $Secret = self::KeyFile($Header['oku'] ?? self::LOCAL_KEY);
      $Header = self::Encode($Header);
      $Payload = self::Encode($Payload);
      $Signature = self::Signature($Header, $Payload, $Secret, $Algorithm);
      return "$Header.$Payload.$Signature";
    }

    public static function Encode($Variable, $JsonEncode = true): string
    {
      return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(($JsonEncode ? json_encode($Variable) : $Variable) ?? 'null'));
    }

    private static function Signature($Header, $Payload, $Secret, $Algorithm): string
    {
      $Signature = null;
      switch ($Algorithm)
      {
        case 'ES256':
          openssl_sign($Header . '.' . $Payload, $Signature, openssl_pkey_get_private($Secret), 'sha256');
          $Signature = self::DerToRaw($Signature, 32);
          break;
        case 'RS256':
          openssl_sign($Header . '.' . $Payload, $Signature, openssl_pkey_get_private($Secret), 'sha256');
          break;
        case 'HS256':
          $Signature = hash_hmac('sha256', $Header . '.' . $Payload, $Secret, true);
          break;
      }
      return self::Encode($Signature, false);
    }

    /**
     * OpenSSL gives ECDSA signatures as DER (SEQUENCE of two INTEGERs), JWT needs r and s as fixed size raw bytes
     */
    private static function DerToRaw(string $Der, int $Size): string
    {
      $Position = 2; // Skip the SEQUENCE tag and length (always short for P-256)
      $Raw = '';
      for ($Part = 0; $Part < 2; $Part++)
      {
        $Length = ord($Der[$Position + 1]);
        $Integer = ltrim(substr($Der, $Position + 2, $Length), "\x00");
        $Raw .= str_pad($Integer, $Size, "\x00", STR_PAD_LEFT);
        $Position += 2 + $Length;
      }
      return $Raw;
    }

    /**
     * The login token from the auth_token cookie, or else from an Authorization: Bearer header
     *
     * Only tokens this site issued are accepted, so an ID token from an external provider can't be used as a login.
     */
    public static function FromHeader(): self
    {
      if (empty($Token = ($_COOKIE['auth_token'] ?? '')))
      {
        if (isset($_SERVER['HTTP_AUTHORIZATION']))
        {
          $AuthHeader = $_SERVER['HTTP_AUTHORIZATION'];
        }
        else
        {
          if (function_exists('apache_request_headers'))
          {
            $Headers = apache_request_headers();
            $AuthHeader = $Headers['Authorization'] ?? $Headers['authorization'] ?? null;
          }
        }
        preg_match('/Bearer\s(\S+)/', $AuthHeader ?? '', $Bearer);
        $Token = $Bearer[1] ?? '';
      }
      $Object = self::FromToken($Token);
      if ($Object->Get('iss') !== \Config::Origin())
      {
        return new self([], -1);
      }
      return $Object;
    }

    public static function FromToken(string $Token): self|null
    {
      if (empty($Token))
      {
        return new self([], -1);
      }

      $Parts = explode('.', $Token);
      if (count($Parts) !== 3)
      {
        return new self([], -1);
      }
      $Header = self::Decode($Parts[0]);
      $Payload = self::Decode($Parts[1]);
      if (!is_array($Header) || !is_array($Payload) || empty($Payload['exp']))
      {
        return new self([], -1);
      }
      $Object = new self($Payload);
      $Object->Header = $Header;
      $Object->Signature = $Parts[2];
      $Object->Validate($Parts[0], $Parts[1], $Parts[2]);
      return $Object;
    }

    private static function Decode(string $Encoded, $JsonDecode = true): mixed
    {
      $Decoded = base64_decode(strtr($Encoded, '-_', '+/'));
      return $JsonDecode ? json_decode($Decoded, true) : $Decoded;
    }

    /**
     * The JWKS endpoint of a trusted external issuer, or null for our own (HS256) tokens
     */
    private static function JwksURI(mixed $Issuer): ?string
    {
      if (!is_string($Issuer))
      {
        return null;
      }
      if (isset(self::TRUSTED_ISSUERS[$Issuer]))
      {
        return self::TRUSTED_ISSUERS[$Issuer];
      }
      foreach (self::TRUSTED_ISSUER_PATTERNS as $Pattern => $JwksURI)
      {
        if (preg_match($Pattern, $Issuer))
        {
          return $JwksURI;
        }
      }
      return null;
    }

    private function Validate($Header, $Payload, $Signature)
    {
      if ($this->Expired() || $Signature === null || $Signature === 'bnVsbA')
      {
        $this->Payload = [];
        return false;
      }

      // The issuer, not the token's own header, decides the algorithm and key
      $JwksURI = self::JwksURI($this->Payload['iss'] ?? null);
      $Algorithm = empty($JwksURI) ? 'HS256' : 'RS256';
      if (($this->Header['alg'] ?? null) !== $Algorithm)
      {
        $Verified = false;
      }
      elseif ($Algorithm == 'HS256')
      {
        $Verified = $this->ValidateHS256($Header, $Payload, $Signature);
      }
      else
      {
        $Verified = $this->ValidateRS256($Header, $Payload, $Signature, $JwksURI);
      }
      if (!$Verified)
      {
        $this->Payload = [];
        return false;
      }
      return true;
    }

    public function Expired(): bool
    {
      if (!isset($this->Payload['exp']))
      {
        return true;
      }
      if (time() >= $this->Payload['exp'])
      {
        return true;
      }
      return false;
    }

    private function ValidateHS256($Header, $Payload, $Signature): bool
    {
      // Always our own key, never the key file named in the token
      $CheckSignature = self::Signature($Header, $Payload, self::KeyFile(self::LOCAL_KEY), 'HS256');
      return hash_equals($CheckSignature, $Signature);
    }

    private function Secret(): string
    {
      return self::KeyFile($this->Header['oku'] ?? self::LOCAL_KEY);
    }

    /**
     * Reads a key from the keys folder (auth.keys_dir), only accepting a plain file name; our own key is auth.secret when
     * that is set
     */
    private static function KeyFile(string $Name): string
    {
      if ($Name === self::LOCAL_KEY && !empty($Secret = \Config::Get('auth.secret')))
      {
        return (string)$Secret;
      }
      if (!preg_match('/^[A-Za-z0-9_-][A-Za-z0-9._-]*$/', $Name))
      {
        throw new \InvalidArgumentException('Invalid key file name');
      }
      $Folder = rtrim((string)\Config::Get('auth.keys_dir', self::KEYS_DIR), '/') . '/';
      $Key = @file_get_contents($Folder . $Name);
      if (empty($Key))
      {
        throw new \RuntimeException("Key file {$Name} is missing or empty");
      }
      return $Key;
    }

    private function ValidateRS256($Header, $Payload, $Signature, string $JwksURI): bool
    {
      $Kid = $this->Kid();
      if (!is_string($Kid) || $Kid === '')
      {
        return false;
      }
      // Hash the cache name so the token's kid never becomes part of a path
      $Folder = rtrim((string)\Config::Get('auth.cache_dir', self::CACHE_DIR), '/');
      $LocalPEM = $Folder . '/' . hash('sha256', "$JwksURI|$Kid") . '.pem';
      $PEM = is_readable($LocalPEM) ? file_get_contents($LocalPEM) : false;
      if (empty($PEM))
      {
        $PEM = self::FetchPEM($JwksURI, $Kid);
        if (empty($PEM))
        {
          return false;
        }
        if (is_dir($Folder) && is_writable($Folder))
        {
          // Written whole, then moved into place, so a concurrent sign-in never reads half a key
          $Temporary = $LocalPEM . '.' . bin2hex(random_bytes(4));
          if (@file_put_contents($Temporary, $PEM) !== false)
          {
            @rename($Temporary, $LocalPEM);
          }
        }
      }
      $Signature = self::Decode($Signature, false);
      $Verified = openssl_verify("$Header.$Payload", $Signature, $PEM, OPENSSL_ALGO_SHA256);
      return $Verified === 1;
    }

    private static function FetchPEM(string $JwksURI, string $Kid): ?string
    {
      // A provider that doesn't answer in 5 seconds: the sign-in fails rather than hangs
      $Context = stream_context_create(['http' => ['timeout' => 5]]);
      $Info = json_decode(@file_get_contents($JwksURI, false, $Context) ?: '', true);
      foreach ($Info['keys'] ?? [] as $Value)
      {
        if (($Value['kid'] ?? null) === $Kid && ($Value['kty'] ?? null) === 'RSA' && isset($Value['n'], $Value['e']))
        {
          return self::CreatePEM($Value['n'], $Value['e']);
        }
      }
      return null;
    }

    public function Kid()
    {
      return $this->Header['kid'] ?? null;
    }

    private static function CreatePEM($Modulus, $Exponent): string
    {
      $Modulus = self::Decode($Modulus, false);
      $Exponent = self::Decode($Exponent, false);
      // DER integers are signed, so a leading byte with the high bit set needs a 0x00 in front
      $Modulus = (ord($Modulus[0]) > 0x7f ? "\x00" : '') . $Modulus;
      $Exponent = (ord($Exponent[0]) > 0x7f ? "\x00" : '') . $Exponent;

      $Modulus = '02' . self::EncodeLength(strlen($Modulus)) . bin2hex($Modulus);
      $Exponent = '02' . self::EncodeLength(strlen($Exponent)) . bin2hex($Exponent);
      $Data = $Modulus . $Exponent;
      $Sequence = '30' . self::EncodeLength(strlen($Data) / 2) . $Data;
      $DER = hex2bin($Sequence);

      return "-----BEGIN RSA PUBLIC KEY-----\n" . chunk_split(base64_encode($DER), 64, "\n") . "-----END RSA PUBLIC KEY-----\n";
    }

    private static function EncodeLength($length): string
    {
      if ($length <= 127)
      {
        return sprintf('%02x', $length);
      }
      $hexLength = dechex($length);
      $lengthOfLength = ceil(strlen($hexLength) / 2);
      $padded = str_pad($hexLength, $lengthOfLength * 2, '0', STR_PAD_LEFT);
      return sprintf('%02x', 0x80 | $lengthOfLength) . $padded;
    }

    public function GetPayload()
    {
      return $this->Payload;
    }

    public function SignWith(string $Secret, string $Algorithm = 'HS256', ?string $Kid = null)
    {
      $this->Header['oku'] = $Secret;
      $this->Header['alg'] = $Algorithm;
      if (!empty($Kid))
      {
        $this->Header['kid'] = $Kid;
      }
      return $this;
    }

    public function __toString(): string
    {
      $Header = self::Encode($this->Header);
      $Payload = self::Encode($this->Payload);
      $Signature = $this->Signature ?? self::Signature($Header, $Payload, $this->Secret(), $this->Header['alg']);
      return "$Header.$Payload.$Signature";
    }

    public function TTL()
    {
      return ($this->Get('exp') - time()) ?? 0;
    }

    public function Get(string $Key)
    {
      return $this->Payload[$Key] ?? null;
    }

    private function SetHeader(string $Key, mixed $Value)
    {
      $this->Header[$Key] = $Value;
    }

  }

