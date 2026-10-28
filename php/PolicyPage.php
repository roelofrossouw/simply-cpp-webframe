<?php

  use BS\{AlertBox, Card, Color, PadSize, ScreenSize, Side};

  /**
   * A policy document (privacy policy, terms of use, ...) in plain language
   *
   * Shown in the popup when opened from the app, or as a page of its own when opened directly, e.g. from a
   * sign-in provider's consent screen. Sections are numbered, and links to other policy pages stay in the popup.
   */
  class PolicyPage
  {
    private HTML\Element $Body;
    private int $Section = 0;

    public function __construct(string $Title, string $Updated)
    {
      if (Server::IsAJAX())
      {
        Page::AddContent($Dialog = new BS\ModalDialog($Title, ScreenSize::Large));
        $this->Body = $Dialog;
      }
      else
      {
        Page::AddContent($Container = new BS\Container());
        $Container->Format->MaxWidth(ScreenSize::Large->Width());
        $Container->Format->Padding(Side::Vertical, PadSize::Pad4);
        $Container->AddChild($Card = new Card(PadSize::None));
        $Card->AddHeader($Title, 1);
        $this->Body = $Card->Body();
        $this->Body->Format->Padding(Side::All, PadSize::Pad4);
      }
      $this->Body->AddChild($Date = new HTML\Paragraph("Last updated $Updated"));
      $Date->Format->Color(Color::BodySecondary);
      $Date->Format->Font->Size(BS\FontSize::Size6);
    }

    /**
     * The main points in a few lines, for people who don't read the whole document
     */
    public function Summary(array $Points): void
    {
      $this->Body->AddChild($Box = new AlertBox(Color::Info, PadSize::Pad3));
      $Box->AddChild(new HTML\Header(5, 'In short'));
      $Box->AddChild($List = new HTML\UnorderedList());
      $List->Format->Margin(Side::Bottom, PadSize::None);
      foreach ($Points as $Point)
      {
        $List->AddItem($Point);
      }
    }

    /**
     * A numbered section. Each item is a paragraph (string or element), or a bullet list (array).
     */
    public function Section(string $Heading, HTML\Element|string|array ...$Items): void
    {
      $this->Section++;
      $this->Body->AddChild($Header = new HTML\Header(5, "{$this->Section}. $Heading"));
      $Header->Format->Margin(Side::Top, PadSize::Pad4);
      foreach ($Items as $Item)
      {
        if (is_array($Item))
        {
          $this->Body->AddChild($List = new HTML\UnorderedList());
          foreach ($Item as $Point)
          {
            $List->AddItem($Point);
          }
        }
        else
        {
          $this->Body->AddChild($Item instanceof HTML\Element ? $Item : new HTML\Paragraph($Item));
        }
      }
    }

    /**
     * A paragraph of text and links, e.g. Text('See our ', self::Link('Privacy Policy', Config::FrameworkURL('privacypolicy.php')), '.')
     */
    public static function Text(HTML\Element|string ...$Parts): HTML\Paragraph
    {
      // Joined exactly as given (children are normally separated by spaces), so punctuation can follow a link
      $Html = '';
      foreach ($Parts as $Part)
      {
        $Html .= $Part instanceof HTML\Element ? trim((string)$Part) : (string)new HTML\Text($Part);
      }
      return new HTML\Paragraph(new HTML\Raw($Html));
    }

    /**
     * A link to another page: in the popup it opens in the popup, on a page of its own it's a normal link
     */
    /**
     * The sign-in services that are set up, as words: "Google, GitHub or Facebook"
     */
    public static function Services(): string
    {
      $Names = array_map(fn($Class) => $Class::TITLE, array_values(\Auth\Base::Providers()));
      return count($Names) > 1 ? implode(', ', array_slice($Names, 0, -1)) . ' or ' . end($Names) : (implode('', $Names) ?: 'a sign-in service');
    }

    /**
     * When the policies last changed: the settings' app.policies_updated, else the date of the package's text
     */
    public static function Updated(): string
    {
      return (string)Config::Get('app.policies_updated', '28 October 2026');
    }

    public static function Link(string $Caption, string $URL): HTML\Anchor
    {
      if (!Server::IsAJAX())
      {
        return new HTML\Anchor($Caption, $URL, IsLink: true);
      }
      $Link = new HTML\Anchor($Caption, '#', IsLink: true);
      $Link->AddClass('modal-link');
      // An inline handler rather than a script after the link, which would add a space before following punctuation
      $Link->AddAttribute('onclick', 'event.preventDefault(); loadPage.call(this, ' . json_encode($URL) . ');');
      return $Link;
    }

    public static function Mail(string $Address): HTML\Anchor
    {
      return new HTML\Anchor($Address, 'mailto:' . $Address, IsLink: true);
    }

    public static function External(string $Caption, string $URL): HTML\Anchor
    {
      $Link = new HTML\Anchor($Caption, $URL, IsLink: true);
      $Link->Target = '_blank';
      $Link->Rel = 'noopener';
      return $Link;
    }
  }
