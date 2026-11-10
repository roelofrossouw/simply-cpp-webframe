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
        // Before the package's own, which adjusts it: the calendar of date fields (BS\DatePicker)
        $this->AddStylesheet('https://cdn.jsdelivr.net/npm/vanilla-calendar-pro@3.4.0/styles/index.css', false);
        $this->AddStylesheet(Config::FrameworkURL('css/style.css'));

        // Add dependencies...
        $this->AddScript('https://ajax.aspnetcdn.com/ajax/jQuery/jquery-3.7.1.js', false);
        $this->AddScript('https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js', false);
        // The calendar of date fields (BS\DatePicker), the library Bootstrap 6's Datepicker uses
        $this->AddScript('https://cdn.jsdelivr.net/npm/vanilla-calendar-pro@3.4.0/index.js', false);

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

    /**
     * What a link to the site shows when it's shared (WhatsApp, Teams, Slack, LinkedIn, X): the description, Open
     * Graph's tags (as property, which Facebook, WhatsApp and LinkedIn read) and a Twitter/X card. The title is
     * app.share_title, else the page's; the image app.image (default /img/logo-1200x900.png), left out when the site
     * doesn't have it, with its size (read from a PNG or JPEG, else app.image_width and app.image_height) so the
     * preview shows at once, and app.image_alt (else the title) as its text.
     */
    private function OpenGraph(HTML\Element $Parent): void
    {
      $Title = (string)Config::Get('app.share_title', '') ?: $this->Title;
      $Description = (string)$this->Description;
      $Image = self::ShareImage();
      if ($Description !== '')
      {
        $Parent->AddChild(new HTML\Meta('description', $Description));
      }
      $OG = [
        'og:title' => $Title,
        'og:type' => 'website',
        'og:url' => Config::Origin(),
        'og:site_name' => Config::CompanyName(),
        'og:description' => $Description,
        'og:locale' => 'en_GB',
      ];
      if ($Image)
      {
        $OG += [
          'og:image' => $Image['url'],
          'og:image:width' => $Image['width'],
          'og:image:height' => $Image['height'],
          'og:image:alt' => (string)Config::Get('app.image_alt', '') ?: $Title,
        ];
      }
      foreach ($OG as $Property => $Value)
      {
        if ((string)$Value !== '')
        {
          $Parent->AddChild(HTML\Meta::Property($Property, (string)$Value));
        }
      }
      $Card = [
        'twitter:card' => $Image ? 'summary_large_image' : 'summary',
        'twitter:title' => $Title,
        'twitter:description' => $Description,
        'twitter:image' => $Image['url'] ?? '',
        'twitter:image:alt' => $Image ? $OG['og:image:alt'] : '',
      ];
      foreach ($Card as $Name => $Value)
      {
        if ($Value !== '')
        {
          $Parent->AddChild(new HTML\Meta($Name, $Value));
        }
      }
    }

    /**
     * The shared image: its URL and size ('' when unknown), or null when the site doesn't have it. An http(s) URL
     * elsewhere is taken as it is, with the size from the settings.
     */
    private static function ShareImage(): ?array
    {
      $Image = (string)Config::Get('app.image', '/img/logo-1200x900.png');
      $Width = (string)Config::Get('app.image_width', '');
      $Height = (string)Config::Get('app.image_height', '');
      if (preg_match('~^https?://~i', $Image))
      {
        return ['url' => $Image, 'width' => $Width, 'height' => $Height];
      }
      if ($Image === '' || !self::SiteHas($Image))
      {
        return null;
      }
      $Size = @getimagesize($_SERVER['DOCUMENT_ROOT'] . $Image);
      if ($Size && in_array($Size[2], [IMAGETYPE_PNG, IMAGETYPE_JPEG], true))
      {
        [$Width, $Height] = [(string)$Size[0], (string)$Size[1]];
      }
      return ['url' => Config::Origin() . $Image, 'width' => $Width, 'height' => $Height];
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
