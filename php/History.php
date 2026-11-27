<?php

  /**
   * A database's change history, as a trigger keeps it in audit.change (since 1.49.0): who changed which row of which
   * table when, from what to what, and putting a change back (audit.undo). The table is
   *
   *   audit.change (id, at, tbl 'schema.table', row_key jsonb, action insert|update|delete, old jsonb, new jsonb,
   *                 changed_by, source, undo_of, about jsonb)
   *
   * where an update holds only the changed columns, a delete the whole old row and an insert the whole new one;
   * changed_by is the setting audit.user (DB sets it to the signed-in user), about (when the column is there) the row's
   * parent ids as of the change. audit.undo(id, by) answers a sentence, what it did or why it couldn't.
   *
   * A screen shows its history with BS\History::Glyph(): a button that opens the history popup (history.php) for the
   * tables it edits, optionally only the rows matching a JSON object. What it may show travels in a signed token
   * (Token(), Scope()), so the popup never shows tables or rows the screen didn't name.
   */
  class History
  {
    // How long a glyph's token works: a page left open over a working day still opens its history
    const int TOKEN_SECONDS = 12 * 3600;
    // The time ranges the popup offers, by days (0: all of it)
    const array RANGES = ['1' => 'Today', '7' => '7 days', '30' => '30 days', '365' => 'A year', '0' => 'All'];
    // How many changes one look shows
    const int LIMIT = 200;

    private static ?bool $HasAbout = null;

    /**
     * The token a glyph's link carries: the page whose rights apply, the tables, and the match per table
     *
     * @param array<string, array> $Match [table => JSON object] or one object for every table; [] for every row
     */
    public static function Token(string $Page, array $Tables, array $Match = [], string $Title = ''): string
    {
      $Tables = array_values(array_unique(array_map('strval', $Tables)));
      return (string)new \Auth\JWT(['purpose' => 'history', 'page' => $Page, 'tables' => $Tables,
        'match' => self::PerTable($Tables, $Match), 'title' => $Title], self::TOKEN_SECONDS);
    }

    /**
     * A token's page, tables, match per table and title, or null when it isn't one of ours (or expired)
     *
     * @return array{page: string, tables: string[], match: array<string, array>, title: string}|null
     */
    public static function Scope(string $Token): ?array
    {
      $Read = \Auth\JWT::FromToken($Token);
      if ($Read === null || $Read->Get('purpose') !== 'history' || $Read->Get('iss') !== Config::Origin()
        || !is_string($Read->Get('page')) || !is_array($Read->Get('tables')) || empty($Read->Get('tables')))
      {
        return null;
      }
      return ['page' => $Read->Get('page'), 'tables' => array_map('strval', $Read->Get('tables')),
        'match' => (array)$Read->Get('match'), 'title' => (string)$Read->Get('title')];
    }

    /**
     * The match as [table => object]: an object given once applies to every table; tables without one match every row
     */
    private static function PerTable(array $Tables, array $Match): array
    {
      if ($Match === [])
      {
        return [];
      }
      $ByTable = !array_is_list($Match) && count(array_filter(array_keys($Match), fn($Key) => in_array($Key, $Tables, true))) > 0;
      $Out = [];
      foreach ($Tables as $Table)
      {
        $Given = $ByTable ? ($Match[$Table] ?? null) : $Match;
        if (is_array($Given) && $Given !== [])
        {
          $Out[$Table] = $Given;
        }
      }
      return $Out;
    }

    /**
     * Whether audit.change has the about column (the row's parent ids), once a request
     */
    private static function HasAbout(): bool
    {
      return self::$HasAbout ??= DB::Exists('select 1 from information_schema.columns where table_schema = \'audit\' and table_name = \'change\' '
        . 'and column_name = \'about\'');
    }

    /**
     * The SQL condition limiting changes to the scope's tables and matches; its parameters are added to $Parameters
     */
    private static function Where(array $Scope, array &$Parameters, ?string $Table = null): string
    {
      $Facts = self::HasAbout() ? 'coalesce(c.about, \'{}\'::jsonb) || c.row_key'
        : 'coalesce(c.old, \'{}\'::jsonb) || coalesce(c.new, \'{}\'::jsonb) || c.row_key';
      $Each = [];
      foreach ($Scope['tables'] as $Each_Table)
      {
        if ($Table !== null && $Table !== $Each_Table)
        {
          continue;
        }
        $Parameters[] = $Each_Table;
        $Condition = 'c.tbl = $' . count($Parameters);
        if (isset($Scope['match'][$Each_Table]))
        {
          $Parameters[] = json_encode($Scope['match'][$Each_Table]);
          $Condition .= " and ($Facts) @> \$" . count($Parameters) . '::jsonb';
        }
        $Each[] = "($Condition)";
      }
      return $Each === [] ? 'false' : '(' . implode(' or ', $Each) . ')';
    }

    private const string COLUMNS = 'c.id, c.at, c.tbl, c.row_key, c.action, c.old, c.new, c.changed_by, c.source, c.undo_of, '
      . '(select min(u.id) from audit.change u where u.undo_of = c.id) as undone_by';

    /**
     * The scope's changes, newest first: in the last $Days days (0: all, 1: since midnight), the text $Search anywhere
     * in the row's key, its values, who or where from, and only $Table's when given
     *
     * @return array<int, array> rows with row_key, old and new decoded
     */
    public static function Changes(array $Scope, int $Days = 7, string $Search = '', ?string $Table = null, int $Limit = self::LIMIT): array
    {
      $Parameters = [];
      $Where = [self::Where($Scope, $Parameters, $Table)];
      if ($Days === 1)
      {
        $Where[] = 'c.at >= current_date';
      }
      elseif ($Days > 1)
      {
        $Parameters[] = $Days;
        $Where[] = 'c.at > now() - make_interval(days => $' . count($Parameters) . '::integer)';
      }
      if (trim($Search) !== '')
      {
        $Parameters[] = '%' . addcslashes(trim($Search), '%_\\') . '%';
        $Where[] = '(c.row_key::text || \' \' || coalesce(c.old::text, \'\') || \' \' || coalesce(c.new::text, \'\') || \' \' '
          . '|| coalesce(c.changed_by, \'\') || \' \' || coalesce(c.source, \'\')) ilike $' . count($Parameters);
      }
      $Parameters[] = max(1, min($Limit, 1000));
      return array_map([self::class, 'Decoded'], DB::Rows('select ' . self::COLUMNS . ' from audit.change c where ' . implode(' and ', $Where)
        . ' order by c.id desc limit $' . count($Parameters) . '::integer', $Parameters));
    }

    /**
     * One change, when it's within the scope
     */
    public static function Change(array $Scope, int $ID): ?array
    {
      $Parameters = [$ID];
      $Row = DB::Row('select ' . self::COLUMNS . ' from audit.change c where c.id = $1 and ' . self::Where($Scope, $Parameters), $Parameters);
      return $Row === null ? null : self::Decoded($Row);
    }

    /**
     * Put a change back, as the signed-in user: audit.undo()'s sentence, and whether it's undone now
     *
     * @return array{message: string, undone: bool}
     */
    public static function Undo(array $Scope, int $ID): array
    {
      if (self::Change($Scope, $ID) === null)
      {
        return ['message' => 'There\'s no such change here.', 'undone' => false];
      }
      return DB::Transaction(function () use ($ID) {
        $Message = (string)DB::Value('select audit.undo($1, $2)', [$ID, \Auth\Base::AuditName()]);
        return ['message' => $Message, 'undone' => DB::Exists('select 1 from audit.change where undo_of = $1', [$ID])];
      });
    }

    private static function Decoded(array $Row): array
    {
      foreach (['row_key', 'old', 'new'] as $Field)
      {
        $Row[$Field] = $Row[$Field] === null ? null : json_decode($Row[$Field], true);
      }
      return $Row;
    }

    /**
     * A row's key as words: "id 29", "menu_id 29, access_id 3"
     */
    public static function KeyText(?array $Key): string
    {
      if (empty($Key))
      {
        return '';
      }
      return implode(', ', array_map(fn($Column, $Value) => $Column . ' ' . self::ValueText($Value), array_keys($Key), $Key));
    }

    /**
     * A value as text, shortened to $Length characters (0: in full)
     */
    public static function ValueText(mixed $Value, int $Length = 0): string
    {
      $Text = match (true)
      {
        $Value === null => 'null',
        is_bool($Value) => $Value ? 'true' : 'false',
        is_array($Value) => json_encode($Value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        default => (string)$Value,
      };
      return $Length > 0 && mb_strlen($Text) > $Length ? mb_substr($Text, 0, $Length - 1) . '…' : $Text;
    }

    /**
     * What a change did, column by column: [column => [before, after]]; an insert's before and a delete's after are null
     */
    public static function Columns(array $Change): array
    {
      $Old = (array)($Change['old'] ?? []);
      $New = (array)($Change['new'] ?? []);
      $Out = [];
      foreach (array_unique(array_merge(array_keys($Old), array_keys($New))) as $Column)
      {
        $Out[$Column] = [$Change['action'] === 'insert' ? null : ($Old[$Column] ?? null), $Change['action'] === 'delete' ? null : ($New[$Column] ?? null)];
      }
      return $Out;
    }
  }
