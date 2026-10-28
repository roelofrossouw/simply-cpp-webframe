<?php

  namespace Storage;

  /**
   * Where users, their access and the menu are kept. User and MenuLayout use it through Store::Get().
   *
   * Postgres keeps them in the database the settings name (sql/schema.sql). A site that keeps them elsewhere (behind its
   * own REST service, say) implements this interface and names its class in the settings: "storage": {"class": "..."}.
   *
   * Values are as the pgsql functions give them: text, 't' or 'f' for booleans, null for null; access and edit in menu
   * items are arrays. A change answers null when it was done, or why it wasn't, as a sentence for the page.
   */
  interface Backend
  {
    /**
     * Records a sign-in: makes the user on the first one, with public access; names or email not given keep what's stored
     *
     * @return array user_id, email, first_name, last_name, access (codes, granted and not revoked)
     */
    public function SignIn(string $Provider, string $Subject, ?string $Email, ?string $FirstName, ?string $LastName): array;

    /** The access codes a user has, granted and not revoked, in the levels' order */
    public function Access(int $UserID): array;

    /** The access levels that can be granted, code => description, in order */
    public function Levels(): array;

    /**
     * Every user: user_id, provider, email, first_name, last_name, created, last_login, access (codes), and verified ('t' or
     * 'f': a password account chose its password through a link; null for the others)
     */
    public function Users(): array;

    /** Grants an access level or revokes it (revoking keeps the grant, with when) */
    public function SetAccess(int $UserID, string $Code, bool $Granted): ?string;

    /**
     * A user's profile: user_id, provider, email, first_name, last_name, display_as, nickname, names_from_user, created,
     * last_login, photo_source, photo (a JPEG as base64, or null) and wants_provider_photo, or null when there's no such user
     */
    public function Profile(int $UserID): ?array;

    /** How the app addresses the user: first, full or nickname; a null nickname keeps the one stored */
    public function SetDisplay(int $UserID, string $DisplayAs, ?string $Nickname): ?string;

    /** Names the sign-in provider didn't give; refused when it gave them */
    public function SetName(int $UserID, ?string $FirstName, ?string $LastName): ?string;

    /** The user's photo, a JPEG; $Source is user or provider */
    public function SetPhoto(int $UserID, string $Jpeg, string $Source): ?string;

    /** Removes the user's photo, and keeps the provider's from coming back */
    public function RemovePhoto(int $UserID): ?string;

    /**
     * The password account with the email address (lower case), or null
     */
    public function PasswordUserID(string $Email): ?int;

    /**
     * A password account: user_id, email, hash (null until a link chose a password: the address isn't shown to work yet),
     * failures (wrong passwords since the last right one) and last_failure (a Unix time, or null); null when the user
     * doesn't sign in with a password
     */
    public function Password(int $UserID): ?array;

    /**
     * Makes an account that signs in with its email address (lower case) and a password, with public access; without a
     * hash it can't sign in until a link chose one
     *
     * @return int|string Its id, or why it wasn't made
     */
    public function AddPasswordUser(string $Email, ?string $FirstName, ?string $LastName, ?string $Hash): int|string;

    /** A password account's new password hash; clears its wrong passwords */
    public function SetPassword(int $UserID, string $Hash): ?string;

    /** Records a wrong password: the user's (when there is one), and the address's */
    public function PasswordFailed(?int $UserID, string $Address): void;

    /** How many wrong passwords came from an address in the last $Seconds */
    public function AddressFailures(string $Address, int $Seconds): int;

    /**
     * Every menu item, parents before their children, ordered by position: the item's fields, depth, access (the codes
     * that may view it) and edit (those that may also change things on its page)
     */
    public function Menu(): array;

    /**
     * Adds a menu item, last under its parent, with the parent's view and edit rights (admin's at the top)
     *
     * @return int|string Its id, or why it wasn't added
     */
    public function AddMenuItem(string $Name, ?int $ParentID, string $Code, ?string $Target, string $Icon, string $Section): int|string;

    /**
     * Sets one of a menu item's fields (see MenuLayout::FIELDS)
     *
     * @param string|null $Editor The page that manages the menu: undone when admins would no longer see it and edit there
     */
    public function SetMenuField(int $ID, string $Field, string|int|bool|null $Value, ?string $Editor): ?string;

    /** Gives an access level the view or edit right on a menu item, or takes it away; see SetMenuField() for $Editor */
    public function SetMenuRight(int $ID, string $Code, string $Right, bool $Allowed, ?string $Editor): ?string;

    /** Removes a menu item without children; see SetMenuField() for $Editor */
    public function DeleteMenuItem(int $ID, ?string $Editor): ?string;
  }
