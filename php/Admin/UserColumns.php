<?php

  namespace Admin;

  /**
   * A site's own columns on Administer → Users (public/admin/users.php), after Last sign-in. The settings name the
   * class: "admin": {"user_columns": "MySite\\UserColumns"}; it loads from the site's classes.
   *
   *   class UserColumns implements \Admin\UserColumns
   *   {
   *     public static function Headings(): array
   *     {
   *       return ['team' => 'Team'];
   *     }
   *
   *     public static function Cell(string $Column, array $User, bool $CanEdit): \HTML\Element|string|null
   *     {
   *       return $User['team'] ?? null; // or a control that saves itself, e.g. a select with OnChange('toast.call(this, ...)')
   *     }
   *   }
   */
  interface UserColumns
  {
    /**
     * The columns, as [key => heading]
     */
    public static function Headings(): array;

    /**
     * One user's cell in a column: $User is the row as User::All() gives it (user_id, first_name, last_name, email,
     * provider, ...); $CanEdit whether the viewer may change users (the page's Edit right).
     */
    public static function Cell(string $Column, array $User, bool $CanEdit): \HTML\Element|string|null;
  }
