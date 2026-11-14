<?php

  namespace Storage;

  /**
   * Codes emailed for signing in (Auth\EmailCode), for a storage that offers it: a Backend that also implements this.
   * Auth\EmailCode is only offered when the storage in use does.
   *
   * Codes belong to an email address (lower case), not an account, so an address without one can get a code to make it.
   * The account itself is made by Backend::SignIn('email', $Email, $Email, ...), as for every provider. Times are Unix
   * times; values as Backend's are.
   */
  interface EmailCodes
  {
    /** The account that signs in with codes emailed to the address (provider 'email', lower case), or null */
    public function EmailCodeUserID(string $Email): ?int;

    /**
     * The address's code: hash (password_hash() of it), expires, sent (when it was made), failures (wrong codes since the
     * last right one, kept across new codes) and last_failure (or null); null when it has no code and no failures
     */
    public function EmailCode(string $Email): ?array;

    /** A new code for the address, replacing its last one; its failures stay */
    public function SaveEmailCode(string $Email, string $Hash, int $Expires): void;

    /** A wrong code: one more failure for the address (now), and one for the IP address, as Backend::PasswordFailed() */
    public function EmailCodeFailed(string $Email, string $Address): void;

    /** A right code: removes the address's code and its failures, so the code works once */
    public function EmailCodeUsed(string $Email): void;

    /**
     * Records that the account's address works (a code sent to it came back)
     *
     * @return bool True the first time, false when it was known already
     */
    public function EmailVerified(int $UserID): bool;
  }
