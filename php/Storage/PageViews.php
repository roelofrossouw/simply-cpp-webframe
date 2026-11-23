<?php

  namespace Storage;

  /**
   * Page usage, for a storage that keeps it: a Backend that also implements this. Config::Init() hands it each page a
   * signed-in user is let into, once the page has gone out (see Config::Init()); a storage without it is never asked.
   *
   * Called after the response, so it never slows a page; whatever it throws is dropped, so it never breaks one. Keep it
   * quick all the same (a request with a short timeout, say): PHP-FPM's worker waits for it.
   */
  interface PageViews
  {
    /**
     * A view of a page
     *
     * @param int         $UserID  The signed-in user
     * @param string      $Page    The menu page whose access applied (Config::Init's Access), else the page itself
     * @param string      $Shown   How it was shown: 'page', 'popup', 'panel' or 'toast' (Config::Init's Shown)
     * @param string|null $Request The URL as asked, with its query (REQUEST_URI), or null when there's none
     */
    public function PageView(int $UserID, string $Page, string $Shown, ?string $Request): void;
  }
