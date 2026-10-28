<?php

  use Storage\Store;

  /**
   * The menu setup: every page in a tree per section, and the access levels that may view or edit each
   * (framework.menu and framework.menu_access in sql/schema.sql, kept by the storage, see Storage\Backend). The package's
   * landing.php builds the main menu and the status bar with Build(); a site's own admin page can manage it with Add(),
   * Update(), SetAccess() and Delete().
   */
  class MenuLayout
  {
    // The fields that can be changed one at a time, with their captions
    const array FIELDS = ['name' => 'Name', 'icon' => 'Icon', 'target' => 'Target', 'code' => 'ID', 'position' => 'Position',
      'parent_id' => 'Parent', 'popup' => 'Popup', 'public' => 'Public', 'shown' => 'Shown'];

    // Where an item is: the menu at the top, the status bar at the bottom, or in no menu (a page opened from elsewhere)
    const array SECTIONS = ['main' => 'Main menu', 'status' => 'Status bar', 'page' => 'Free-standing pages'];

    // When an item shows
    const array SHOWN = ['always' => 'Always', 'signed_in' => 'Signed in', 'signed_out' => 'Signed out'];

    /**
     * The page that manages the menu, which admins must always be able to reach (the settings' menu.editor), or null
     */
    public static function Editor(): ?string
    {
      return Config::Get('menu.editor');
    }

    /**
     * Every item, parents before their children, ordered by position, as id => record with depth, access (the access
     * codes that may view it) and edit (those that may also change things on its page)
     */
    public static function All(): array
    {
      // Every page reads it (the menu, the status bar, who may open the page): once a request, again after a change
      if (self::$All === null)
      {
        self::$All = [];
        foreach (Store::Get()->Menu() as $Item)
        {
          self::$All[$Item['id']] = $Item;
        }
      }
      return self::$All;
    }

    private static ?array $All = null;

    /**
     * Whether anyone may open a page, also without signing in: a public menu item targets it (the query string ignored)
     */
    public static function IsPublic(string $Path): bool
    {
      static $Public = null;
      if ($Public === null)
      {
        $Public = [];
        foreach (self::All() as $Item)
        {
          if (!empty($Item['target']) && ($Item['public'] ?? 'f') === 't')
          {
            $Public[parse_url($Item['target'], PHP_URL_PATH)] = true;
          }
        }
      }
      return isset($Public[parse_url($Path, PHP_URL_PATH)]);
    }

    /**
     * The access codes that may view a page, or change things on it: those of every menu item that targets it,
     * ignoring the query string
     *
     * @param string $Path The page, e.g. /app/users.php
     * @param bool $Edit The codes that may change things, rather than view
     */
    public static function Roles(string $Path, bool $Edit = false): array
    {
      static $Roles = null;
      if ($Roles === null)
      {
        $Roles = [];
        foreach (self::All() as $Item)
        {
          if (!empty($Item['target']))
          {
            $Target = parse_url($Item['target'], PHP_URL_PATH);
            foreach (['access' => false, 'edit' => true] as $Key => $Right)
            {
              $Roles[$Right][$Target] = array_values(array_unique(array_merge($Roles[$Right][$Target] ?? [], $Item[$Key])));
            }
          }
        }
      }
      return $Roles[$Edit][parse_url($Path, PHP_URL_PATH)] ?? [];
    }

    /**
     * Add a section's items the visitor may see to a menu: those that are public or have one of the user's access
     * levels, that show now (signed in or not), and that have a target or a child that shows
     *
     * @param string $Section main or status, see SECTIONS
     */
    public static function Build(\BS\Menu $Menu, string $Section = 'main'): void
    {
      $Children = [];
      foreach (self::All() as $Item)
      {
        if (($Item['section'] ?? 'main') === $Section)
        {
          $Children[$Item['parent_id'] ?? 0][] = $Item;
        }
      }
      self::AddItems($Menu, $Children, 0);
    }

    // Whether the visitor may see an item: public or one of their access levels, and shown now
    private static function Visible(array $Item): bool
    {
      $SignedIn = \Auth\Base::LoggedIn();
      $Shown = match ($Item['shown'] ?? 'always')
      {
        'signed_in' => $SignedIn,
        'signed_out' => !$SignedIn,
        default => true,
      };
      return $Shown && (($Item['public'] ?? 'f') === 't' || array_filter($Item['access'], fn($Code) => \Auth\Base::HasAccess($Code)));
    }

    private static function AddItems(\BS\Menu|\BS\BaseMenuItem $Parent, array $Children, int $ParentID): void
    {
      foreach ($Children[$ParentID] ?? [] as $Item)
      {
        if (!self::Visible($Item) || !self::HasContent($Item, $Children))
        {
          continue;
        }
        $Icon = \BS\Icon::tryFrom($Item['icon']) ?? \BS\Icon::Circle;
        $Added = match ($Item['code'])
        {
          // A few items show more than a link
          'login' => self::AddSignIn($Parent, $Icon, $Item),
          'profile' => self::AddProfile($Parent, $Icon, $Item),
          default => $Parent->AddItem($Icon, $Item['name'], $Item['target'], $Item['code']),
        };
        if ($Added === null)
        {
          continue;
        }
        if ($Item['code'] == 'home')
        {
          $Added->Active(); // Where the app starts
        }
        if ($Item['popup'] === 't')
        {
          $Added->Modal();
        }
        self::AddItems($Added, $Children, (int)$Item['id']);
      }
    }

    // Sign in: a submenu with each sign-in provider that's set up, instead of a link
    private static function AddSignIn(\BS\Menu|\BS\BaseMenuItem $Parent, \BS\Icon $Icon, array $Item): \BS\BaseMenuItem
    {
      $Login = $Parent->AddItem($Icon, $Item['name'], ID: $Item['code']);
      $Login->Submenu()->AlignRight();
      foreach (\Auth\Base::Providers() as $Name => $Class)
      {
        $Login->AddItem($Class::ICON, 'Sign in with ' . $Class::TITLE, $Item['target'] . '?provider=' . $Name, $Item['code'] . '-' . $Name);
      }
      return $Login;
    }

    // Your profile: shows your name, and stays visible on phones when the status bar folds behind its toggle button
    private static function AddProfile(\BS\Menu|\BS\BaseMenuItem $Parent, \BS\Icon $Icon, array $Item): ?\BS\BaseMenuItem
    {
      if (($Profile = \User::Profile((int)\Auth\Base::ID())) === null)
      {
        return null;
      }
      $Name = \User::DisplayName($Profile);
      return $Parent instanceof \BS\Menu ? $Parent->AddPinnedItem($Icon, $Name, $Item['target'], $Item['code'])
        : $Parent->AddItem($Icon, $Name, $Item['target'], $Item['code']);
    }

    // Whether an item has a target, or a child the user may see that has
    private static function HasContent(array $Item, array $Children): bool
    {
      if ($Item['target'] !== null)
      {
        return true;
      }
      foreach ($Children[$Item['id']] ?? [] as $Child)
      {
        if (self::Visible($Child) && self::HasContent($Child, $Children))
        {
          return true;
        }
      }
      return false;
    }

    /**
     * The items that can be a parent, as id => name indented by depth: those without a target in the same section,
     * apart from the item itself and its own children
     *
     * @param string|null $Section Only this section's items, see SECTIONS; the section of $Except when null
     */
    public static function Parents(?int $Except = null, ?string $Section = null): array
    {
      $Parents = [];
      $Skip = null;
      $Items = self::All();
      $Section ??= $Except !== null ? ($Items[$Except]['section'] ?? 'main') : 'main';
      foreach ($Items as $ID => $Item)
      {
        if (($Item['section'] ?? 'main') !== $Section)
        {
          continue;
        }
        if ($Skip !== null && $Item['depth'] > $Skip)
        {
          continue;
        }
        $Skip = $ID === $Except ? (int)$Item['depth'] : null;
        if ($Skip === null && $Item['target'] === null)
        {
          $Parents[$ID] = str_repeat('– ', (int)$Item['depth']) . $Item['name'];
        }
      }
      return $Parents;
    }

    /**
     * @return string|null Why it can't be added, or null when it was
     */
    public static function Add(string $Name, ?int $ParentID, string $Code, string $Target, string $Icon, string $Section = 'main'): ?string
    {
      $Name = trim($Name);
      if ($Name === '')
      {
        return 'Give it a name.';
      }
      if (!array_key_exists($Section, self::SECTIONS))
      {
        return 'Unknown section.';
      }
      if ($ParentID !== null && !array_key_exists($ParentID, self::Parents(null, $Section)))
      {
        return 'It can\'t go under that item.';
      }
      if (($Problem = self::CheckCode($Code) ?? self::CheckIcon($Icon)) !== null)
      {
        return $Problem;
      }
      // Last under its parent, with the parent's view and edit rights to start with (admin at the top); refused when
      // another item has the ID
      self::$All = null;
      $Added = Store::Get()->AddMenuItem($Name, $ParentID, trim($Code), trim($Target) ?: null, trim($Icon) ?: 'bi-circle', $Section);
      return is_int($Added) ? null : $Added;
    }

    /**
     * Change one field (see FIELDS)
     *
     * @return string|null Why it can't be saved, or null when it was
     */
    public static function Update(int $ID, string $Field, string $Value): ?string
    {
      $Value = trim($Value);
      $Problem = match ($Field)
      {
        'name' => $Value === '' ? 'Give it a name.' : null,
        'icon' => self::CheckIcon($Value),
        'code' => self::CheckCode($Value),
        'position' => filter_var($Value, FILTER_VALIDATE_INT) === false ? 'The position is a whole number.' : null,
        'parent_id' => $Value !== '' && !array_key_exists((int)$Value, self::Parents($ID)) ? 'It can\'t go under that item.' : null,
        'shown' => array_key_exists($Value, self::SHOWN) ? null : 'Unknown choice.',
        'popup', 'public', 'target' => null,
        default => 'Unknown field.',
      };
      if ($Problem !== null)
      {
        return $Problem;
      }
      $Value = match ($Field)
      {
        'target' => $Value === '' ? null : $Value,
        'parent_id' => $Value === '' ? null : (int)$Value,
        'position' => (int)$Value,
        'popup', 'public' => $Value === 'true',
        default => $Value,
      };
      // Undone when admins could no longer reach the menu editor; refused when another item has the ID
      self::$All = null;
      return Store::Get()->SetMenuField($ID, $Field, $Value, self::Editor());
    }

    // The rights an access level can have on an item; Edit implies View
    const array RIGHTS = ['view' => 'View', 'edit' => 'Edit'];

    /**
     * Give an access level a right on an item, or take it away. Giving Edit also gives View, taking View away also takes
     * Edit.
     *
     * @param string|bool $Right 'view' or 'edit', see RIGHTS (a bool here is the older call for View: SetAccess($ID, $Code, $Allowed))
     * @return string|null Why it can't be changed, or null when it was
     */
    public static function SetAccess(int $ID, string $Code, string|bool $Right, ?bool $Allowed = null): ?string
    {
      if (is_bool($Right))
      {
        [$Right, $Allowed] = ['view', $Right];
      }
      if (!array_key_exists($Right, self::RIGHTS))
      {
        return 'Unknown right.';
      }
      // Refused for an unknown access level, undone when admins could no longer reach the menu editor
      self::$All = null;
      return Store::Get()->SetMenuRight($ID, $Code, $Right, (bool)$Allowed, self::Editor());
    }

    /**
     * Remove an item without children
     *
     * @return string|null Why it can't be removed, or null when it was
     */
    public static function Delete(int $ID): ?string
    {
      // Refused when items are under it, undone when admins could no longer reach the menu editor
      self::$All = null;
      return Store::Get()->DeleteMenuItem($ID, self::Editor());
    }

    private static function CheckCode(string $Code): ?string
    {
      $Code = trim($Code);
      if (!preg_match('/^[a-z][a-z0-9-]*$/', $Code))
      {
        return 'The ID is lower case letters, digits and dashes, starting with a letter, e.g. users.';
      }
      // An ID another item has: the storage refuses it
      return null;
    }

    private static function CheckIcon(string $Icon): ?string
    {
      return trim($Icon) === '' || \BS\Icon::tryFrom(trim($Icon)) !== null ? null
        : 'Unknown icon: use a Bootstrap icon class, e.g. bi-people (see https://icons.getbootstrap.com).';
    }
  }
