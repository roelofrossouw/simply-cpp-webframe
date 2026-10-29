<?php

  namespace Admin;

  /**
   * A site's own settings on Administer → Config (public/admin/config.php), after the package's, edited and saved like
   * them. The settings name the class: "admin": {"settings": "MySite\\Settings"}; it loads from the site's classes.
   *
   *   class Settings implements \Admin\SiteSettings
   *   {
   *     public static function Groups(): array
   *     {
   *       return ['Agent' => ['agent.url' => ['Where the agent answers', false], 'agent.token' => ['Its token', true]]];
   *     }
   *
   *     public static function AfterGroup(string $Group): ?\HTML\Element
   *     {
   *       return $Group == 'Agent' ? new \HTML\Paragraph('...') : null;
   *     }
   *   }
   */
  interface SiteSettings
  {
    /**
     * The settings by group, as [group => [dotted path => [what it is, secret]]]: a secret is never shown, and saving
     * its field empty keeps it. A group the package has too gets these paths added to it.
     */
    public static function Groups(): array;

    /**
     * Something to show under a group's settings, e.g. a table of what they set up; null for nothing
     */
    public static function AfterGroup(string $Group): ?\HTML\Element;
  }
