<?php

  class Framework
  {
    private string $Title;
    private string $Description;
    private array $Scripts = [];
    private array $Stylesheets = [];
    private HTML\Element $Body;

    // Colour of title bar on mobiles or background when scrolling past the start/end
    // Match the top menu for a seamless look
    private string $ThemeColour = '#e9ecef';

    public function __construct(HTML\Element|string|null $Content = null)
    {
      if (Server::IsAJAX())
      {
        $this->Body = new HTML\Text();
      }
      else
      {
        // Add global items
        $this->Title = Config::AppName();
        $this->Description(Config::AppDescription());
        $this->ThemeColour = (string)Config::Get('app.theme_color', $this->ThemeColour);
        $this->AddStylesheet(Config::FrameworkURL('css/style.css'));

        // Add dependencies...
        $this->AddScript('https://ajax.aspnetcdn.com/ajax/jQuery/jquery-3.7.1.js', false);
        $this->AddScript('https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js', false);

        // Create main containers
        $this->Body = new HTML\Main('main');
        $this->Body->AddSibling(new BS\ToastContainer('main-toast')); // Placeholder for toasts
        $this->Body->AddSibling(new BS\OffCanvasContainer('main-offcanvas')); // Placeholder for bar on the left for e.g. settings.
        $this->Body->AddSibling(new BS\ModalContainer('main-modal')); // Placeholder for popups
      }
      if (!empty($Content))
      {
        $this->AddContent($Content);
      }
    }

    public function Description($Description): void
    {
      $this->Description = $Description;
    }

    public function AddStylesheet(string $Stylesheet, bool $BustCache = true): void
    {
      $this->Stylesheets[] = $Stylesheet . ($BustCache ? self::Version($Stylesheet) : '');
    }

    public function AddScript(string $Script, bool $BustCache = true): void
    {
      $this->Scripts[] = $Script . ($BustCache ? self::Version($Script) : '');
    }

    /**
     * ?v= and a hash of the file a link loads, so browsers fetch it again when it changes: the package's own files (under
     * Config::FrameworkURL()) or the site's (under its document root); nothing when it can't be read
     */
    private static function Version(string $URL): string
    {
      $Path = (string)parse_url($URL, PHP_URL_PATH);
      $Framework = Config::FrameworkURL() . '/';
      $File = str_starts_with($Path, $Framework) ? Config::FrameworkFolder() . '/' . substr($Path, strlen($Framework))
        : ($_SERVER['DOCUMENT_ROOT'] ?? '') . $Path;
      return is_file($File) ? '?v=' . md5_file($File) : '';
    }

    /**
     * Whether the site has this file, e.g. /favicon.ico, under its document root
     */
    private static function SiteHas(string $Path): bool
    {
      return !empty($_SERVER['DOCUMENT_ROOT']) && is_file($_SERVER['DOCUMENT_ROOT'] . $Path);
    }

    public function AddContent(HTML\Element|string $Content)
    {
      $this->Body->AddChild($Content);
    }

    public function __toString(): string
    {
      return Server::IsAJAX() ? $this->Body() : $this->FullHTML();
    }

    private function Body(): HTML\Body
    {
      $Body = new HTML\Body();
      // $Body->AddClass('bg-body-tertiary'); // Light gray background
      $Body->AddChild($this->Body);
      $this->ScriptLinks($Body);
      return $Body;
    }

    private function ScriptLinks(HTML\Element $Parent): void
    {
      foreach ($this->Scripts as $Script)
      {
        $Parent->AddChild(new HTML\Script(Source: $Script));
      }
    }

    public function FullHTML(): string
    {
      $Html = new HTML\Html('en');
      $Html->AddChild($this->Head());
      $Html->AddChild($this->Body());
      return $Html;
    }

    private function Head(): HTML\Head
    {
      $Head = new HTML\Head();
      $this->MetaTags($Head);
      $Head->AddChild(new HTML\Title($this->Title));
      $this->OpenGraph($Head);
      $this->PWA($Head);
      $this->Icons($Head);
      $this->StyleLinks($Head);
      return $Head;
    }

    private function MetaTags(HTML\Element $Parent): void
    {
      $Meta = new HTML\Meta();
      $Meta->AddAttribute('charset', 'utf-8');
      $Meta->AddSibling(new HTML\Meta('viewport', 'width=device-width, initial-scale=1'));
      $Meta->AddSibling(new HTML\Meta('theme-color', $this->ThemeColour));
      $Parent->AddChild($Meta);
    }

    private function OpenGraph(HTML\Element $Parent): void
    {
      $OG = [
        'title' => $this->Title,
        'type' => 'website',
        'url' => Config::Origin(),
        'image' => Config::Origin() . Config::Get('app.image', '/img/logo-1200x900.png'),
        'description' => $this->Description,
        'determiner' => 'the',
        'locale' => 'en_GB'
      ];

      foreach ($OG as $Type => $Value)
      {
        $Parent->addChild(new HTML\Meta('og:' . $Type, $Value));
      };
    }

    // A web app manifest, when the site has one
    private function PWA(HTML\Head $Head)
    {
      if (self::SiteHas('/manifest.webmanifest'))
      {
        $Head->addChild(new HTML\Link('manifest', '/manifest.webmanifest'));
      }
    }

    // The site's icons, those it has
    private function Icons(HTML\Head $Head)
    {
      if (self::SiteHas('/favicon.ico'))
      {
        $Head->addChild(new HTML\Link('icon', '/favicon.ico'))->AddAttribute('sizes', 'any');
      }
      if (self::SiteHas('/img/icon.svg'))
      {
        $Head->addChild(new HTML\Link('icon', '/img/icon.svg'))->AddAttribute('type', 'image/svg+xml');
      }
      if (self::SiteHas('/img/icon.png'))
      {
        $Head->addChild(new HTML\Link('apple-touch-icon', '/img/icon.png'));
      }
    }

    private function StyleLinks(HTML\Element $Parent): void
    {
      foreach ($this->Stylesheets as $Stylesheet)
      {
        $Parent->AddChild(new HTML\Stylesheet($Stylesheet));
      }
    }
  }
