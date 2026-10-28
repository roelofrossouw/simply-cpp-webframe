<?php

  class Server
  {
    public static function Page(array $Query = []): string
    {
      return self::Get('SCRIPT_NAME') . (!empty($Query) ? '?' . http_build_query($Query) : '');
    }

    public static function Get(string $Key)
    {
      return $_SERVER[strtoupper($Key)] ?? null;
    }

    public static function IsAJAX(): bool
    {
      // js/app.js marks its requests
      return self::Get('HTTP_X_Requested_by') == 'sc-webframe';
    }

  }
