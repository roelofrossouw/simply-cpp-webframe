<?php

  /**
   * PostgreSQL, through the pgsql extension, always with parameters ($1, $2, ...)
   *
   * The connection comes from the settings' database section: connection (a libpq connection string, e.g.
   * "host=db.example.com dbname=app user=app password=..."), or its parts host, port, dbname, user, password and sslmode.
   * One persistent connection a process.
   *
   *   $Rows = DB::Rows('select id, name from app.thing where owner = $1 order by name', [$UserID]);
   *   $Name = DB::Value('select name from app.thing where id = $1', [$ID]);
   *   DB::Transaction(function () { DB::Exec(...); DB::Exec(...); });
   *
   * Values come back as the pgsql functions give them: text, 't' or 'f' for booleans, null for null. Parameters may be
   * strings, numbers, booleans (sent as true and false), null, or arrays (sent as PostgreSQL arrays, e.g. for = any($1)).
   */
  class DB
  {
    private static ?\PgSql\Connection $Connection = null;

    public static function Connection(): \PgSql\Connection
    {
      if (self::$Connection === null)
      {
        Config::Load();
        $Connection = @pg_pconnect(self::ConnectionString());
        if ($Connection === false)
        {
          throw new RuntimeException('Could not connect to the database (the settings\' database section)');
        }
        self::$Connection = $Connection;
      }
      return self::$Connection;
    }

    /**
     * The libpq connection string from the settings
     */
    public static function ConnectionString(): string
    {
      $Settings = Config::Get('database');
      if (!is_array($Settings) || empty($Settings))
      {
        throw new RuntimeException('No database section in the settings');
      }
      if (!empty($Settings['connection']))
      {
        return (string)$Settings['connection'];
      }
      $Parts = [];
      foreach (['host', 'port', 'dbname', 'user', 'password', 'sslmode', 'application_name'] as $Key)
      {
        if (isset($Settings[$Key]) && $Settings[$Key] !== '')
        {
          $Parts[] = $Key . "='" . addcslashes((string)$Settings[$Key], "'\\") . "'";
        }
      }
      return implode(' ', $Parts);
    }

    /**
     * Run a statement; throws when the database refuses it
     */
    public static function Query(string $Query, array $Parameters = []): \PgSql\Result
    {
      $Result = @pg_query_params(self::Connection(), $Query, array_map([self::class, 'Parameter'], array_values($Parameters)));
      if ($Result === false)
      {
        throw new RuntimeException('Database: ' . trim(pg_last_error(self::Connection())));
      }
      return $Result;
    }

    /**
     * Every row, as column => value
     */
    public static function Rows(string $Query, array $Parameters = []): array
    {
      return pg_fetch_all(self::Query($Query, $Parameters)) ?: [];
    }

    /**
     * The first row, or null
     */
    public static function Row(string $Query, array $Parameters = []): ?array
    {
      $Row = pg_fetch_assoc(self::Query($Query, $Parameters));
      return $Row === false ? null : $Row;
    }

    /**
     * The first row's first column, or null
     */
    public static function Value(string $Query, array $Parameters = []): ?string
    {
      $Row = pg_fetch_row(self::Query($Query, $Parameters));
      return $Row === false ? null : $Row[0];
    }

    /**
     * The first two columns as key => value
     */
    public static function Assoc(string $Query, array $Parameters = []): array
    {
      $Assoc = [];
      foreach (pg_fetch_all(self::Query($Query, $Parameters), PGSQL_NUM) ?: [] as $Row)
      {
        $Assoc[$Row[0]] = $Row[1] ?? null;
      }
      return $Assoc;
    }

    /**
     * Whether the query gives a row
     */
    public static function Exists(string $Query, array $Parameters = []): bool
    {
      return pg_num_rows(self::Query($Query, $Parameters)) > 0;
    }

    /**
     * Run a statement, answering how many rows it changed
     */
    public static function Exec(string $Query, array $Parameters = []): int
    {
      return pg_affected_rows(self::Query($Query, $Parameters));
    }

    /**
     * Run $Work in a transaction: committed when it returns, rolled back when it throws (and the exception passed on)
     */
    public static function Transaction(callable $Work): mixed
    {
      self::Query('begin');
      try
      {
        $Result = $Work();
        self::Query('commit');
        return $Result;
      }
      catch (Throwable $Error)
      {
        @pg_query(self::Connection(), 'rollback');
        throw $Error;
      }
    }

    /**
     * Add/replaces the field to sort the query results by, for a table heading's link
     *
     * @see DB::Sort()
     */
    public static function SortAction(string $Field): string
    {
      return Server::Page([
        '_order' => $Field,
        '_desc' => empty(QueryString::Get('_desc')) || QueryString::Get('_order') != $Field
      ]);
    }

    /**
     * The order by clause a list page asks for (?_order= and ?_desc=, see HTML\TableHeadingCell::Sort()), or '' when the
     * field isn't one of $ValidFields
     */
    public static function Sort(array $ValidFields): string
    {
      $Field = QueryString::Get('_order');
      if (!in_array($Field, $ValidFields, true))
      {
        return '';
      }
      // Only one of the page's own fields, so it may be an expression such as u.name
      return ' order by ' . $Field . (empty(QueryString::Get('_desc')) ? ' asc' : ' desc');
    }

    private static function Parameter(mixed $Value): ?string
    {
      return match (true)
      {
        $Value === null => null,
        is_bool($Value) => $Value ? 'true' : 'false',
        is_array($Value) => self::ArrayLiteral($Value),
        $Value instanceof DateTimeInterface => $Value->format(DATE_ATOM),
        default => (string)$Value,
      };
    }

    // A PostgreSQL array literal: {"a","b",NULL}
    private static function ArrayLiteral(array $Values): string
    {
      return '{' . implode(',', array_map(fn($Value) => match (true)
        {
          $Value === null => 'NULL',
          is_array($Value) => self::ArrayLiteral($Value),
          is_bool($Value) => $Value ? 'true' : 'false',
          default => '"' . addcslashes((string)$Value, '"\\') . '"',
        }, array_values($Values))) . '}';
    }
  }
