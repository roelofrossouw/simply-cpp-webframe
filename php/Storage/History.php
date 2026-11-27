<?php

  namespace Storage;

  /**
   * A change history, for a storage that keeps one somewhere the site can't query itself (since 1.49.3): a Backend that
   * also implements this answers the history popup (\History) instead of its own queries on audit.change. Without it,
   * \History reads audit.change through DB (the settings' database section).
   *
   * Every call carries the scope a glyph's signed token names: $Tables ('schema.table') and $Match, [table => JSON
   * object] (a table without one: all its rows; an object matches when the row's key and parent ids, audit.change's
   * row_key || about, contain it). Never answer, show or undo a change outside it.
   *
   * A change is [id, at, tbl, row_key, action ('insert', 'update' or 'delete'), old, new, changed_by, source, undo_of,
   * undone_by], with row_key, old and new decoded (arrays, or null) and undone_by the id of the change that undid it, or
   * null.
   */
  interface History
  {
    /**
     * The scope's changes, newest first
     *
     * @param int         $Days   The last $Days days; 1: since midnight, 0: all of them
     * @param string      $Search Text anywhere in the row's key, its values, who or where from ('': any)
     * @param string|null $Table  Only this table of $Tables
     * @return array<int, array> changes
     */
    public function Changes(array $Tables, array $Match, int $Days, string $Search, ?string $Table, int $Limit): array;

    /**
     * One change, or null when there's none in the scope
     */
    public function Change(int $ID, array $Tables, array $Match): ?array;

    /**
     * Put a change back, for $By: audit.undo()'s sentence, and whether it's undone now. A change outside the scope:
     * undone false, with why.
     *
     * @return array{message: string, undone: bool}
     */
    public function Undo(int $ID, ?string $By, array $Tables, array $Match): array;
  }
