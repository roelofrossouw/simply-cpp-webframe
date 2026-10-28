<?php

  namespace Storage;

  /**
   * The storage in use: the settings' storage.class (a Backend), Postgres when it isn't set
   */
  final class Store
  {
    private static ?Backend $Backend = null;

    public static function Get(): Backend
    {
      if (self::$Backend === null)
      {
        \Config::Load();
        $Class = (string)\Config::Get('storage.class', Postgres::class);
        $Backend = new $Class();
        if (!$Backend instanceof Backend)
        {
          throw new \RuntimeException("The storage class $Class isn't a Storage\\Backend");
        }
        self::$Backend = $Backend;
      }
      return self::$Backend;
    }

    /**
     * Use this storage instead, e.g. in tests or a site that sets one up in code
     */
    public static function Use(Backend $Backend): void
    {
      self::$Backend = $Backend;
    }
  }
