-- sc-webframe's tables (PostgreSQL 13 or later): users, their access, and the menu
--
--   access.users        one row per person that signed in, by sign-in provider and the provider's id for them
--   access.levels       the access that can be granted: public (anyone signed in) and admin to start with
--   access.grants       which users have which access; revoking keeps the row, with when
--   framework.menu      the pages, in a tree per section: main (the menu at the top), status (the bar at the bottom),
--                       page (in no menu, opened from elsewhere)
--   framework.menu_access  which access levels may view each item's page, and which may also change things there
--
-- Run it once, in the database the settings' database section names, as a role that may create schemas:
--   psql "host=db.example.com dbname=app user=app" -v ON_ERROR_STOP=1 -f /usr/share/sc-webframe/sql/schema.sql
-- Running it again changes nothing: it only adds what isn't there yet.
--
-- The first person to sign in becomes an admin (the settings' auth.first_admin, see the README). To make someone else one:
--   insert into access.grants (user_id, access_id)
--   select user_id, access_id from access.users, access.levels where email = 'you@example.com' and code = 'admin';

begin;

create schema if not exists framework;
create schema if not exists access;

comment on schema framework is 'Tables sc-webframe itself uses';
comment on schema access is 'Users, access levels and the grants linking them';

create table if not exists access.users
(
  user_id         bigint generated always as identity primary key,
  provider        text        not null check (provider in ('google', 'apple', 'microsoft', 'github', 'facebook')),
  subject         text        not null,                                       -- the provider's id for the user ('sub')
  email           text,
  first_name      text,
  last_name       text,
  created         timestamptz not null default now(),
  last_login      timestamptz,
  display_as      text        not null default 'first' check (display_as in ('first', 'full', 'nickname')),
  nickname        text check (char_length(nickname) <= 50),
  names_from_user boolean     not null default false,                         -- the user typed them, the provider gave none
  photo           bytea,                                                      -- a 256x256 JPEG
  photo_updated   timestamptz,
  photo_source    text check (photo_source in ('provider', 'user', 'none')), -- none: removed, the provider's isn't used either
  unique (provider, subject)
);

create table if not exists access.levels
(
  access_id   smallint primary key,
  code        text not null unique,
  description text not null
);

insert into access.levels (access_id, code, description)
values (1, 'public', 'Any signed in user'),
       (2, 'admin', 'System administrator')
on conflict do nothing;

create table if not exists access.grants
(
  user_id   bigint      not null references access.users on delete cascade,
  access_id smallint    not null references access.levels,
  granted   timestamptz not null default now(),
  revoked   timestamptz,                                                      -- null while it is granted
  primary key (user_id, access_id)
);

create table if not exists framework.menu
(
  id        integer generated always as identity primary key,
  parent_id integer references framework.menu,
  position  integer not null default 0,
  name      text    not null,
  icon      text    not null default 'bi-circle',                            -- a Bootstrap icon class, as in BS\Icon
  target    text,                                                             -- the page it loads, e.g. /app/users.php; null for a submenu
  popup     boolean not null default false,                                   -- open the target in the popup rather than the page
  code      text    not null unique,                                          -- the menu link's element id, e.g. users
  section   text    not null default 'main' check (section in ('main', 'status', 'page')),
  public    boolean not null default false,                                   -- anyone may open the page, also without signing in
  shown     text    not null default 'always' check (shown in ('always', 'signed_in', 'signed_out'))
);

create index if not exists menu_parent on framework.menu (parent_id, position);

create table if not exists framework.menu_access
(
  menu_id   integer  not null references framework.menu on delete cascade,
  access_id smallint not null references access.levels,
  can_edit  boolean  not null default false,                                  -- may also change things on the page
  primary key (menu_id, access_id)
);

-- The items every site has: Home, signing in and out, the profile and the policies. Change them, or add your pages, in
-- the tables (or with MenuLayout::Add() from an admin page). The framework pages are under /framework (app.framework_url):
-- a site that serves them elsewhere changes these targets too.
insert into framework.menu (section, position, name, icon, target, popup, code, public, shown)
values ('main', 0, 'Home', 'bi-house', '/home.php', false, 'home', true, 'always'),
       ('main', 90, 'Sign in', 'bi-person', '/framework/login.php', false, 'login', true, 'signed_out'),
       ('main', 91, 'Sign out', 'bi-person', '/framework/logout.php', false, 'logout', true, 'signed_in'),
       ('status', 2, 'Privacy Policy', 'bi-newspaper', '/framework/privacypolicy.php', true, 'privacy', true, 'always'),
       ('status', 3, 'Terms of Use', 'bi-pen', '/framework/termsofuse.php', true, 'terms', true, 'always'),
       ('page', 1, 'Data Deletion Instructions', 'bi-trash', '/framework/datadeletion.php', false, 'datadeletion', true, 'always')
on conflict (code) do nothing;

-- Administer (admins only): Users, Menu and Config (the settings file); Your profile in the status bar, for anyone signed
-- in (since 1.1.0; Config since 1.2.0)
insert into framework.menu (section, position, name, icon, target, popup, code, public, shown)
values ('main', 80, 'Administer', 'bi-gear', null, false, 'administer', false, 'always'),
       ('status', 1, 'Your profile', 'bi-person-circle', '/framework/profile.php', true, 'profile', false, 'signed_in')
on conflict (code) do nothing;

insert into framework.menu (parent_id, section, position, name, icon, target, popup, code)
select a.id, 'main', s.position, s.name, s.icon, s.target, false, s.code
  from (values (1, 'Users', 'bi-people', '/framework/admin/users.php', 'users'),
               (2, 'Menu', 'bi-menu-button-wide', '/framework/admin/menu.php', 'menu'),
               (3, 'Config', 'bi-filetype-json', '/framework/admin/config.php', 'config')) s(position, name, icon, target, code)
  join framework.menu a on a.code = 'administer'
on conflict (code) do nothing;

-- Admins view and edit every item the schema adds; anyone signed in sees their profile
insert into framework.menu_access (menu_id, access_id, can_edit)
select m.id, l.access_id, true
  from framework.menu m
  join access.levels l on l.code = 'admin'
 where m.code in ('home', 'login', 'logout', 'privacy', 'terms', 'datadeletion', 'administer', 'users', 'menu', 'config', 'profile')
on conflict do nothing;

insert into framework.menu_access (menu_id, access_id)
select m.id, l.access_id from framework.menu m join access.levels l on l.code = 'public' where m.code = 'profile'
on conflict do nothing;

commit;
