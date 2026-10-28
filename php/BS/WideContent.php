<?php

  namespace BS;

  use HTML\Element;

  /**
   * Wide Content
   *
   * Content that needs a wider screen, e.g. a table with many columns. Below $MinSize the content is hidden
   * and a notice asks the user to turn their phone or widen the window instead.
   */
  class WideContent extends \HTML\Div
  {
    private \HTML\Div $Content;

    public function __construct(ScreenSize $MinSize = ScreenSize::Small, string $Message = 'Turn your phone or enlarge the window to see this.')
    {
      parent::__construct();
      parent::AddChild($Notice = new AlertBox(Color::Warning, PadSize::Pad3));
      $Notice->Format->HideFrom($MinSize);
      $Notice->AddChild($Icon = Icon::PhoneLandscape->Element());
      $Icon->Format->Font->Size(FontSize::Size3);
      $Icon->Format->Float(Side::Left);
      $Icon->Format->Padding(Side::Right, PadSize::Pad3);
      $Notice->AddChild(new \HTML\Header(5, $Message));

      parent::AddChild($this->Content = new \HTML\Div());
      $this->Content->Format->HideUntil($MinSize);
    }

    public function AddChild(Element|string $Child): Element
    {
      return $this->Content->AddChild($Child);
    }
  }
