<?php

  // Providers' RS256 ID tokens checked against their published keys (a JWKS), with the keys cached, and the classes'
  // own constants all defined
  require __DIR__ . '/check.inc';

  use Auth\JWT;

  Config::Load();
  $Work = sys_get_temp_dir() . '/sc-webframe-test-' . bin2hex(random_bytes(4));
  mkdir("$Work/cache", 0700, true);
  Config::Set('auth.cache_dir', "$Work/cache");

  function Base64URL(string $Data): string
  {
    return rtrim(strtr(base64_encode($Data), '+/', '-_'), '=');
  }

  // A provider's key pair, and its JWKS as a file
  $Key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
  $Details = openssl_pkey_get_details($Key);
  file_put_contents("$Work/jwks.json", json_encode(['keys' => [['kty' => 'RSA', 'kid' => 'test-key', 'alg' => 'RS256', 'use' => 'sig',
    'n' => Base64URL($Details['rsa']['n']), 'e' => Base64URL($Details['rsa']['e'])]]]));

  function Signed(OpenSSLAsymmetricKey $Key, array $Payload, string $Kid = 'test-key'): array
  {
    $Header = Base64URL(json_encode(['alg' => 'RS256', 'kid' => $Kid, 'typ' => 'JWT']));
    $Body = Base64URL(json_encode($Payload));
    openssl_sign("$Header.$Body", $Signature, $Key, OPENSSL_ALGO_SHA256);
    return [$Header, $Body, Base64URL($Signature), $Kid];
  }

  // ValidateRS256() is what FromToken() calls for a trusted issuer; here it's given the local JWKS
  $Validate = new ReflectionMethod(JWT::class, 'ValidateRS256');
  function Valid(ReflectionMethod $Validate, array $Token, string $JwksURI): bool
  {
    [$Header, $Body, $Signature, $Kid] = $Token;
    $Object = new JWT(['sub' => 'x'], 60);
    (new ReflectionProperty(JWT::class, 'Header'))->setValue($Object, ['alg' => 'RS256', 'kid' => $Kid]);
    return $Validate->invoke($Object, $Header, $Body, $Signature, $JwksURI);
  }

  $Payload = ['iss' => 'https://accounts.google.com', 'sub' => '123', 'exp' => time() + 60];
  $Token = Signed($Key, $Payload);
  Check(Valid($Validate, $Token, "$Work/jwks.json"), 'a token signed with the published key');
  Same(1, count(glob("$Work/cache/*.pem")), 'its key is cached');
  unlink("$Work/jwks.json");
  Check(Valid($Validate, $Token, "$Work/jwks.json"), 'checked again from the cache, the JWKS gone');

  $Other = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
  Check(!Valid($Validate, Signed($Other, $Payload), "$Work/jwks.json"), 'a token signed with another key is refused');
  [$Header, , $Signature] = $Token;
  Check(!Valid($Validate, [$Header, Base64URL(json_encode(['sub' => '1'] + $Payload)), $Signature, 'test-key'], "$Work/jwks.json"),
    'a changed payload is refused');
  Check(!Valid($Validate, Signed($Key, $Payload, 'unknown'), "$Work/jwks.json"), 'an unknown key id is refused');

  // No cache folder PHP may write to: still checked, nothing cached, no warning
  file_put_contents("$Work/jwks2.json", json_encode(['keys' => [['kty' => 'RSA', 'kid' => 'test-key', 'n' => Base64URL($Details['rsa']['n']),
    'e' => Base64URL($Details['rsa']['e'])]]]));
  Config::Set('auth.cache_dir', "$Work/missing");
  Check(Valid($Validate, $Token, "$Work/jwks2.json"), 'checked without a cache folder');
  Check(!is_dir("$Work/missing"), 'and none made');

  array_map('unlink', glob("$Work/cache/*") ?: []);
  @unlink("$Work/jwks2.json");
  @rmdir("$Work/cache");
  @rmdir($Work);

  // Every self::NAME and static::NAME a class uses is a constant (or enum case) it has
  $Root = dirname(__DIR__) . '/php';
  $Missing = [];
  foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($Root, FilesystemIterator::SKIP_DOTS)) as $File)
  {
    if ($File->getExtension() !== 'php' || $File->getFilename() === 'autoload.php')
    {
      continue;
    }
    $Class = str_replace('/', '\\', substr($File->getPathname(), strlen($Root) + 1, -4));
    if (trait_exists($Class))
    {
      continue; // Its constants are its users'
    }
    if (!class_exists($Class) && !interface_exists($Class) && !enum_exists($Class))
    {
      $Missing[] = "$Class doesn't load";
      continue;
    }
    preg_match_all('/\b(?:self|static)::([A-Z][A-Za-z0-9_]*)\b(?!\s*\()/', file_get_contents($File->getPathname()), $Uses);
    $Reflection = new ReflectionClass($Class);
    foreach (array_unique($Uses[1]) as $Name)
    {
      if (!$Reflection->hasConstant($Name))
      {
        $Missing[] = "$Class::$Name";
      }
    }
  }
  Same([], $Missing, 'the constants the classes use');
