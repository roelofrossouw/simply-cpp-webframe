<?php

  namespace Auth;

  /**
   * A site's say in signing in with emailed codes (Auth\EmailCode): name a class that implements it in the settings,
   * auth.email.hooks, and it loads from the site's classes like any other.
   *
   *   class SiteEmailHooks implements Auth\EmailCodeHooks
   *   {
   *     public function SendCode(string $Email, string $Code, int $Minutes): ?bool
   *     {
   *       return null; // the framework's own email; or send it yourself and say whether it went
   *     }
   *
   *     public function Verified(int $UserID, string $Email, bool $NewAccount): void
   *     {
   *       // the address is shown to work: e.g. mark the person's email as confirmed
   *     }
   *   }
   */
  interface EmailCodeHooks
  {
    /**
     * Sends a sign-in code to an address, e.g. through the site's own letter queue
     *
     * @param int $Minutes How long the code works
     * @return bool|null True when it was sent, false when it couldn't be; null to let the framework send its own email
     */
    public function SendCode(string $Email, string $Code, int $Minutes): ?bool;

    /**
     * An account's address is shown to work: a code sent to it came back right, for the first time. Called once per account,
     * after it signed in (and was made, when $NewAccount).
     */
    public function Verified(int $UserID, string $Email, bool $NewAccount): void;
  }
