<?php

  namespace Storage;

  use DB;

  /**
   * Users, access and the menu in PostgreSQL: the access and framework schemas of sql/schema.sql, in the settings'
   * database
   */
  class Postgres implements Backend
  {
    // The columns SetMenuField() may set, with their cast
    private const array MENU_FIELDS = ['name' => '', 'icon' => '', 'target' => '', 'code' => '', 'position' => '::integer',
      'parent_id' => '::integer', 'popup' => '::boolean', 'public' => '::boolean', 'shown' => ''];

    /**
     * Also makes the user an admin when nobody is one yet, as the settings' auth.first_admin allows: true (the default),
     * false, or the one email address that may become the first admin
     */
    public function SignIn(string $Provider, string $Subject, ?string $Email, ?string $FirstName, ?string $LastName): array
    {
      return DB::Transaction(function () use ($Provider, $Subject, $Email, $FirstName, $LastName) {
        $User = DB::Row('insert into access.users (provider, subject, email, first_name, last_name, last_login)
            values ($1, $2, nullif($3, \'\'), nullif($4, \'\'), nullif($5, \'\'), now())
          on conflict (provider, subject) do update set
            email = coalesce(excluded.email, users.email),
            first_name = coalesce(excluded.first_name, users.first_name),
            last_name = coalesce(excluded.last_name, users.last_name),
            last_login = now()
          returning user_id, email, first_name, last_name', [$Provider, $Subject, $Email, $FirstName, $LastName]);
        DB::Exec('insert into access.grants (user_id, access_id)
          select $1, access_id from access.levels where code = \'public\'
          on conflict do nothing', [$User['user_id']]);
        if ($this->MayBeFirstAdmin($User['email']))
        {
          // One sign-in at a time decides, so two first sign-ins can't both become admin
          DB::Exec('select pg_advisory_xact_lock(hashtext(\'sc-webframe first admin\'))');
          DB::Exec('insert into access.grants (user_id, access_id)
            select $1, l.access_id from access.levels l
             where l.code = \'admin\'
               and not exists (select 1 from access.grants g where g.access_id = l.access_id and g.revoked is null)
            on conflict (user_id, access_id) do nothing', [$User['user_id']]);
        }
        return $User + ['access' => $this->Access((int)$User['user_id'])];
      });
    }

    // Whether auth.first_admin lets this sign-in become the first admin
    private function MayBeFirstAdmin(?string $Email): bool
    {
      $Setting = \Config::Get('auth.first_admin', true);
      if (is_string($Setting) && $Setting !== '')
      {
        return $Email !== null && strcasecmp(trim($Email), trim($Setting)) === 0;
      }
      return $Setting === true;
    }

    public function Access(int $UserID): array
    {
      return array_column(DB::Rows('select l.code from access.grants g join access.levels l using (access_id)
        where g.user_id = $1 and g.revoked is null order by l.access_id', [$UserID]), 'code');
    }

    public function Levels(): array
    {
      return DB::Assoc('select code, description from access.levels order by access_id');
    }

    public function Users(): array
    {
      $Users = DB::Rows('select u.user_id, u.provider, u.email, u.first_name, u.last_name, u.created, u.last_login,
          coalesce((select json_agg(l.code order by l.access_id) from access.grants g join access.levels l using (access_id)
                     where g.user_id = u.user_id and g.revoked is null), \'[]\') as access
        from access.users u order by u.last_login desc nulls last, u.user_id');
      foreach ($Users as &$User)
      {
        $User['access'] = json_decode($User['access'], true);
      }
      return $Users;
    }

    public function SetAccess(int $UserID, string $Code, bool $Granted): ?string
    {
      if (!DB::Exists('select 1 from access.users where user_id = $1', [$UserID]))
      {
        return 'Unknown user.';
      }
      if (!DB::Exists('select 1 from access.levels where code = $1', [$Code]))
      {
        return 'Unknown access level.';
      }
      if ($Granted)
      {
        DB::Exec('insert into access.grants (user_id, access_id)
          select $1, access_id from access.levels where code = $2
          on conflict (user_id, access_id) do update set granted = now(), revoked = null
          where grants.revoked is not null', [$UserID, $Code]);
      }
      else
      {
        DB::Exec('update access.grants set revoked = now()
          where user_id = $1 and revoked is null
            and access_id = (select access_id from access.levels where code = $2)', [$UserID, $Code]);
      }
      return null;
    }

    public function Profile(int $UserID): ?array
    {
      return DB::Row('select user_id, provider, email, first_name, last_name, display_as, nickname, names_from_user, created,
          last_login, photo_source, replace(encode(photo, \'base64\'), E\'\\n\', \'\') as photo,
          (photo_source is null or (photo_source = \'provider\' and photo_updated < now() - interval \'1 day\')) as wants_provider_photo
        from access.users where user_id = $1', [$UserID]);
    }

    public function SetDisplay(int $UserID, string $DisplayAs, ?string $Nickname): ?string
    {
      return DB::Exec('update access.users set display_as = $2, nickname = coalesce(nullif($3, \'\'), nickname) where user_id = $1',
        [$UserID, $DisplayAs, $Nickname]) > 0 ? null : 'Unknown user.';
    }

    public function SetName(int $UserID, ?string $FirstName, ?string $LastName): ?string
    {
      return DB::Exec('update access.users set first_name = nullif($2, \'\'), last_name = nullif($3, \'\'), names_from_user = true
        where user_id = $1 and (names_from_user or (first_name is null and last_name is null))', [$UserID, $FirstName, $LastName]) > 0
        ? null : 'The sign-in provider gives this user\'s names.';
    }

    public function SetPhoto(int $UserID, string $Jpeg, string $Source): ?string
    {
      return DB::Exec('update access.users set photo = decode($2, \'base64\'), photo_updated = now(),
          photo_source = case when $3 = \'provider\' then \'provider\' else \'user\' end
        where user_id = $1', [$UserID, base64_encode($Jpeg), $Source]) > 0 ? null : 'Unknown user.';
    }

    public function RemovePhoto(int $UserID): ?string
    {
      return DB::Exec('update access.users set photo = null, photo_updated = now(), photo_source = \'none\' where user_id = $1',
        [$UserID]) > 0 ? null : 'Unknown user.';
    }

    public function Menu(): array
    {
      $Items = DB::Rows('with recursive tree as (
            select id, 0 as depth, array[position, id] as path from framework.menu where parent_id is null
            union all
            select m.id, t.depth + 1, t.path || array[m.position, m.id] from framework.menu m join tree t on m.parent_id = t.id)
        select m.*, t.depth,
               coalesce(json_agg(l.code order by l.access_id) filter (where l.code is not null), \'[]\') as access,
               coalesce(json_agg(l.code order by l.access_id) filter (where a.can_edit), \'[]\') as edit
          from tree t
          join framework.menu m using (id)
          left join framework.menu_access a on a.menu_id = m.id
          left join access.levels l using (access_id)
         group by m.id, t.depth, t.path
         order by t.path');
      foreach ($Items as &$Item)
      {
        $Item['access'] = json_decode($Item['access'], true);
        $Item['edit'] = json_decode($Item['edit'], true);
      }
      return $Items;
    }

    public function AddMenuItem(string $Name, ?int $ParentID, string $Code, ?string $Target, string $Icon, string $Section): int|string
    {
      if (DB::Exists('select 1 from framework.menu where code = $1', [$Code]))
      {
        return "Another item already has the ID $Code.";
      }
      return DB::Transaction(function () use ($Name, $ParentID, $Code, $Target, $Icon, $Section) {
        $ID = (int)DB::Value('insert into framework.menu (parent_id, position, name, icon, target, code, section)
          select $2::integer, coalesce(max(position), 0) + 1, $1, $5, nullif($4, \'\'), $3, $6
            from framework.menu where parent_id is not distinct from $2::integer and section = $6
          returning id', [$Name, $ParentID, $Code, $Target, $Icon, $Section]);
        if ($ParentID === null)
        {
          DB::Exec('insert into framework.menu_access (menu_id, access_id, can_edit)
            select $1, access_id, true from access.levels where code = \'admin\'', [$ID]);
        }
        else
        {
          DB::Exec('insert into framework.menu_access (menu_id, access_id, can_edit)
            select $1, access_id, can_edit from framework.menu_access where menu_id = $2', [$ID, $ParentID]);
        }
        return $ID;
      });
    }

    public function SetMenuField(int $ID, string $Field, string|int|bool|null $Value, ?string $Editor): ?string
    {
      if (!array_key_exists($Field, self::MENU_FIELDS))
      {
        return 'Unknown field.';
      }
      if ($Field === 'code' && DB::Exists('select 1 from framework.menu where code = $1 and id <> $2', [$Value, $ID]))
      {
        return "Another item already has the ID $Value.";
      }
      return $this->Change($Editor, function () use ($ID, $Field, $Value) {
        $Cast = self::MENU_FIELDS[$Field];
        return DB::Exec("update framework.menu set $Field = \$2$Cast where id = \$1", [$ID, $Value]) > 0 ? null : 'Unknown menu item.';
      });
    }

    public function SetMenuRight(int $ID, string $Code, string $Right, bool $Allowed, ?string $Editor): ?string
    {
      if (!in_array($Right, ['view', 'edit'], true))
      {
        return 'Unknown right.';
      }
      if (!DB::Exists('select 1 from framework.menu where id = $1', [$ID]))
      {
        return 'Unknown menu item.';
      }
      if (!DB::Exists('select 1 from access.levels where code = $1', [$Code]))
      {
        return 'Unknown access level.';
      }
      return $this->Change($Editor, function () use ($ID, $Code, $Right, $Allowed) {
        $Edit = $Right === 'edit';
        if ($Allowed)
        {
          // Giving Edit also gives View
          DB::Exec($Edit
            ? 'insert into framework.menu_access (menu_id, access_id, can_edit) select $1, access_id, true from access.levels where code = $2
                 on conflict (menu_id, access_id) do update set can_edit = true'
            : 'insert into framework.menu_access (menu_id, access_id) select $1, access_id from access.levels where code = $2
                 on conflict do nothing', [$ID, $Code]);
        }
        else
        {
          // Taking View away also takes Edit
          DB::Exec($Edit
            ? 'update framework.menu_access set can_edit = false where menu_id = $1 and access_id = (select access_id from access.levels where code = $2)'
            : 'delete from framework.menu_access where menu_id = $1 and access_id = (select access_id from access.levels where code = $2)',
            [$ID, $Code]);
        }
        return null;
      });
    }

    public function DeleteMenuItem(int $ID, ?string $Editor): ?string
    {
      if (!DB::Exists('select 1 from framework.menu where id = $1', [$ID]))
      {
        return 'Unknown menu item.';
      }
      if (DB::Exists('select 1 from framework.menu where parent_id = $1', [$ID]))
      {
        return 'Move or remove the items under it first.';
      }
      return $this->Change($Editor, fn() => DB::Exec('delete from framework.menu where id = $1', [$ID]) > 0 ? null : 'Unknown menu item.');
    }

    /**
     * A menu change in a transaction, undone when it fails or admins would no longer reach the menu editor
     */
    private function Change(?string $Editor, callable $Work): ?string
    {
      $Problem = null;
      try
      {
        DB::Transaction(function () use ($Editor, $Work, &$Problem) {
          $Problem = $Work();
          if ($Problem === null && $Editor !== null && !$this->AdminsReach($Editor))
          {
            $Problem = 'Admins would no longer see and edit the page that manages the menu.';
          }
          if ($Problem !== null)
          {
            throw new \RuntimeException($Problem); // Undone
          }
        });
      }
      catch (\RuntimeException $Error)
      {
        if ($Problem === null)
        {
          throw $Error;
        }
      }
      return $Problem;
    }

    // Whether admins still see the editor's item and may edit there: it's in a menu, and it and every item above it allow admin
    private function AdminsReach(string $Editor): bool
    {
      return DB::Exists('with recursive up as (
            select id, parent_id from framework.menu where target = $1
            union all
            select m.id, m.parent_id from framework.menu m join up on m.id = up.parent_id)
        select 1 from framework.menu e
         where e.target = $1 and e.section <> \'page\'
           and exists (select 1 from framework.menu_access a join access.levels l using (access_id)
                        where a.menu_id = e.id and l.code = \'admin\' and a.can_edit)
           and not exists (select 1 from up where not exists (
                 select 1 from framework.menu_access a join access.levels l using (access_id)
                  where a.menu_id = up.id and l.code = \'admin\'))', [$Editor]);
    }
  }
