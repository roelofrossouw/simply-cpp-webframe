<?php

  namespace HTML;

  /**
   * An HTML element
   *
   * Elements follow the rules in HTML\Spec (taken from the w3schools element reference): only attributes and values
   * the element supports, no children in void elements like <br>, and elements that must be placed inside particular
   * parents (e.g. <li> in a list). Breaking a rule throws an \InvalidArgumentException.
   *
   * Attributes are also available as properties, in PascalCase, e.g. $Image->Alt = 'Logo', $Input->Required = true,
   * $Anchor->Href. Assigning null or false removes the attribute.
   *
   * Global attributes, available on all elements:
   * @property string $AccessKey
   * @property string $Class
   * @property string $ContentEditable true|false|plaintext-only
   * @property string $Dir ltr|rtl|auto
   * @property string $Draggable true|false|auto
   * @property string $EnterKeyHint enter|done|go|next|previous|search|send
   * @property bool $Hidden
   * @property string $Id
   * @property bool $Inert
   * @property string $InputMode none|text|decimal|numeric|tel|search|email|url
   * @property string $Lang
   * @property string $Popover auto|manual
   * @property string $Role (WAI-ARIA)
   * @property string $Spellcheck true|false
   * @property string $Style
   * @property int $TabIndex
   * @property string $Title
   * @property string $Translate yes|no
   */
  abstract class Element
  {
    public \BS\Formatter $Format;
    protected bool $AllowEmpty = false;
    protected array $Children = [];
    private string $Tag;
    // Allow siblings rather than having an unnecessary parent.
    private array $Attributes = [];
    private array $Siblings = [];
    private array $PreSiblings = [];
    private ?Element $Parent = null;
    private ?string $ID = null;

    public function __construct(string $Tag, ?string $ID = null)
    {
      // Text and raw HTML have no tag
      if ($Tag !== '')
      {
        Spec::Element($Tag);
      }
      $this->Tag = $Tag;
      if ($ID !== null)
      {
        $this->AddAttribute('id', $ID);
      }
      $this->Format = new \BS\Formatter($this);
    }

    public function __toString(): string
    {
      return $this->PreSiblingString() . $this->OwnString() . $this->SiblingString();
    }

    public function Tag(): string
    {
      return $this->Tag;
    }

    private function PreSiblingString(): string
    {
      return empty($this->PreSiblings) ? '' : (implode('', $this->PreSiblings));
    }

    protected function OwnString(): string
    {
      if ($this->AllowEmpty && empty($this->Attributes) && empty($this->Children))
      {
        return '';
      }
      $this->CheckFirstChildren();
      $this->CheckRequiredAttributes();
      return $this->OpenTag() . $this->Contents() . $this->ClosingTag() . PHP_EOL;
    }

    protected function OpenTag(): string
    {
      return '<' . $this->Tag . $this->AtributeString() . '>';
    }

    private function AtributeString(): string
    {
      if (empty($this->Attributes))
      {
        return '';
      }
      $Retval = '';
      foreach ($this->Attributes as $Name => $Value)
      {
        $Value = is_array($Value) ? implode(' ', $Value) : (string)$Value;
        $Retval .= ' ' . $Name . ($Value === '' ? '' : '="' . self::EncodeAttribute($Value) . '"');
      }
      return $Retval;
    }

    /**
     * Attribute values are plain text, so everything is encoded, also what looks like an entity already: a value of
     * '&lt;b&gt;' must reach the browser as those characters (e.g. HTML for a popover that the caller escaped)
     */
    private static function EncodeAttribute(string $Value): string
    {
      return htmlspecialchars($Value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }

    /**
     * Void elements like <br> and <img> have no closing tag (and can't have children)
     */
    private function IsVoid(): bool
    {
      return $this->Tag !== '' && Spec::Element($this->Tag)['void'];
    }

    protected function Contents(): string
    {
      return implode(' ', $this->Children);
    }

    protected function ClosingTag(): string
    {
      return $this->IsVoid() ? '' : '</' . $this->Tag . '>';
    }

    private function SiblingString(): string
    {
      return empty($this->Siblings) ? '' : (implode('', $this->Siblings));
    }

    /**
     * Add a CSS class
     *
     * Classes sharing a $Family replace each other, e.g. Formatter uses 'p-md' so a second
     * medium padding replaces the first instead of adding a conflicting class.
     */
    public function AddClass(string $Class, ?string $Family = null): static
    {
      $this->CheckAttribute('class', $Class);
      $this->Attributes['class'][$Family ?? $Class] = $Class;
      return $this;
    }

    /**
     * Set an inline style, replacing any earlier value for the same property
     */
    public function AddStyle(string $Style, string $Value): static
    {
      $this->CheckAttribute('style', $Value);
      $this->Attributes['style'][$Style] = $Style . ':' . $Value . ';';
      return $this;
    }

    public function RemoveClass(string $Class): static
    {
      if (isset($this->Attributes['class']))
      {
        $this->Attributes['class'] = array_filter($this->Attributes['class'], fn($Value) => $Value !== $Class);
      }
      return $this;
    }

    public function AddClasses(array $Classes): static
    {
      foreach ($Classes as $Class)
      {
        $this->AddClass($Class);
      }
      return $this;
    }

    public function AddChild(Element|string $Child): Element
    {
      $this->CheckCanHaveChildren();
      $AddedChild = $this->Children[] = $Child instanceof Element ? $Child : new Text($Child);
      $AddedChild->SetParent($this);
      return $AddedChild;
    }

    public function PrependChild(Element $Child): Element
    {
      $this->CheckCanHaveChildren();
      array_unshift($this->Children, $Child);
      $Child->SetParent($this);
      return $Child;
    }

    private function CheckCanHaveChildren(): void
    {
      if ($this->IsVoid())
      {
        throw new \InvalidArgumentException("<{$this->Tag}> is a void element and can't have content");
      }
    }

    /**
     * Siblings render next to this element, so they share its parent.
     */
    private function SetParent(?Element $Parent): void
    {
      if ($Parent !== null)
      {
        Spec::CheckPlacement($this->Tag, $Parent->Tag);
      }
      $this->Parent = $Parent;
      foreach ([...$this->PreSiblings, ...$this->Siblings] as $Sibling)
      {
        $Sibling->SetParent($Parent);
      }
    }

    /**
     * Elements like <caption>, <legend> and <summary> must be the first child of their parent
     */
    private function CheckFirstChildren(): void
    {
      $Position = 0;
      foreach ($this->Children as $Child)
      {
        // Text, raw HTML and elements that render nothing don't count
        if ($Child->Tag === '' || ($Child->AllowEmpty && empty($Child->Attributes) && empty($Child->Children)))
        {
          continue;
        }
        if ($Position > 0 && Spec::Element($Child->Tag)['first'])
        {
          throw new \InvalidArgumentException("<{$Child->Tag}> must be the first child of <{$this->Tag}>");
        }
        $Position++;
      }
    }

    /**
     * Attributes the element must have, e.g. src and alt on <img> (an empty alt is fine)
     */
    private function CheckRequiredAttributes(): void
    {
      if ($this->Tag === '')
      {
        return;
      }
      foreach (Spec::Element($this->Tag)['required'] as $Name)
      {
        if (!array_key_exists($Name, $this->Attributes))
        {
          throw new \InvalidArgumentException("<{$this->Tag}> is missing its required '$Name' attribute");
        }
      }
    }

    /**
     * @return Element[]
     */
    public function Children(): array
    {
      return $this->Children;
    }

    public function Parent(): ?Element
    {
      return $this->Parent;
    }

    public function Action(string $Href, Event $Trigger = Event::Click): static
    {
      // Link handled by app js.
      if (\Config::AppPage())
      {
        $jHref = json_encode($Href);
        $this->Trigger($Trigger, "loadPage.call(this, $jHref);");
      }
      elseif (Spec::Attribute($this->Tag, 'href') !== null)
      {
        $this->Link($Href);
      }
      else
      {
        // Only links can have an href, other elements navigate with a script
        $jHref = json_encode($Href);
        $this->Trigger($Trigger, "window.location = $jHref;");
      }
      return $this;
    }

    /**
     * Link to external pages (Not handled with the app's AJAX js)
     *
     * For use on standalone pages (Landing page) or external apps (Email, etc.)
     *
     * @param string $URL
     * @return void
     */
    public function Link(string $URL): static
    {
      return $this->AddAttribute('href', $URL);
    }

    public function Trigger(Event $Event, string $Script): static
    {
      $this->ExecuteScript('on', ["'{$Event->value}'", "function(e) { $Script }"]);
      return $this;
    }

    private function ExecuteScript($Function, $Arguments)
    {
      $ID = $this->EnsureID();
      $ArgumentList = implode(',', $Arguments);
      $Script = <<<JavaScript
                $('#{$ID}').{$Function}($ArgumentList);
JavaScript;

      $this->AddSibling(new Script($Script));
    }

    private function EnsureID(): string
    {
      if (empty($this->ID))
      {
        $this->AddAttribute('id', uniqid('e_'));
      }
      return $this->ID;
    }

    public function TabIndex(int $TabIndex = 1): static
    {
      return $this->AddAttribute('tabindex', $TabIndex);
    }

    public function Focus(): static
    {
      $ID = $this->EnsureID();
      $this->TabIndex(1);
      $Script = <<<JavaScript
                $('#{$ID}')[0].focus();
JavaScript;

      return $this->AddSibling(new Script($Script));
    }

    /**
     * Set an attribute, checked against what the element supports
     *
     * Leave $Value null for boolean attributes like 'required'. 'class' and 'style' replace any classes or
     * styles added before.
     */
    public function AddAttribute(string $Name, string|int|float|null $Value = null): static
    {
      $Name = strtolower($Name);
      $Value = $Value === null ? null : (string)$Value;
      $this->CheckAttribute($Name, $Value);
      match ($Name)
      {
        'class' => $this->Attributes['class'] = array_filter(explode(' ', $Value ?? '')),
        'style' => $this->Attributes['style'] = array_filter([$Value]),
        default => $this->Attributes[$Name] = $Value,
      };
      if ($Name == 'id')
      {
        $this->ID = $Value;
      }
      return $this;
    }

    public function RemoveAttribute(string $Name): static
    {
      $Name = strtolower($Name);
      unset($this->Attributes[$Name]);
      if ($Name == 'id')
      {
        $this->ID = null;
      }
      return $this;
    }

    public function GetAttribute(string $Name): ?string
    {
      $Value = $this->Attributes[strtolower($Name)] ?? null;
      return is_array($Value) ? implode(' ', $Value) : $Value;
    }

    public function HasAttribute(string $Name): bool
    {
      return array_key_exists(strtolower($Name), $this->Attributes);
    }

    private function CheckAttribute(string $Name, ?string $Value): void
    {
      if ($this->Tag === '')
      {
        throw new \InvalidArgumentException("Text can't have attributes ('$Name')");
      }
      Spec::CheckAttribute($this->Tag, $Name, $Value);
    }

    /**
     * Attributes as PascalCase properties, e.g. $Image->Alt, $Form->AcceptCharset, $Div->AriaLabel
     */
    public function __set(string $Property, mixed $Value): void
    {
      $Name = $this->AttributeName($Property);
      if ($Value === null || $Value === false)
      {
        $this->RemoveAttribute($Name);
        return;
      }
      if ($Value === true)
      {
        if (Spec::Attribute($this->Tag, $Name) !== Spec::BOOL)
        {
          throw new \InvalidArgumentException("'$Name' on <{$this->Tag}> isn't a boolean attribute, give it a value");
        }
        $this->AddAttribute($Name);
        return;
      }
      if ($Value instanceof \BackedEnum)
      {
        $Value = $Value->value;
      }
      if (!is_scalar($Value))
      {
        throw new \InvalidArgumentException("'$Name' on <{$this->Tag}> needs a string, number or boolean value");
      }
      $this->AddAttribute($Name, $Value);
    }

    public function __get(string $Property): string|bool|null
    {
      $Name = $this->AttributeName($Property);
      if (Spec::Attribute($this->Tag, $Name) === Spec::BOOL)
      {
        return $this->HasAttribute($Name);
      }
      return $this->GetAttribute($Name);
    }

    public function __isset(string $Property): bool
    {
      return $this->HasAttribute($this->AttributeName($Property));
    }

    public function __unset(string $Property): void
    {
      $this->RemoveAttribute($this->AttributeName($Property));
    }

    /**
     * The attribute a property stands for: 'TabIndex' => 'tabindex', 'HttpEquiv' => 'http-equiv'
     */
    private function AttributeName(string $Property): string
    {
      $Joined = strtolower($Property);
      if ($this->Tag !== '' && Spec::Attribute($this->Tag, $Joined) !== null)
      {
        return $Joined;
      }
      $Hyphenated = strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $Property));
      if ($this->Tag !== '' && Spec::Attribute($this->Tag, $Hyphenated) !== null)
      {
        return $Hyphenated;
      }
      throw new \InvalidArgumentException('<' . ($this->Tag ?: 'text') . "> has no attribute or property '$Property'");
    }

    public function AddSibling(Element|string $Sibling): static
    {
      $this->Siblings[] = $Sibling = $Sibling instanceof Element ? $Sibling : new Text($Sibling);
      $Sibling->SetParent($this->Parent);
      return $this;
    }

    public function OnClick(string $Script): static
    {
      return $this->Trigger(Event::Click, $Script);
    }

    public function OnChange(string $Script): static
    {
      return $this->Trigger(Event::Change, $Script);
    }

    public function Data(string $Key, mixed $Value): static
    {
      $this->ExecuteScript('data', [json_encode($Key), json_encode($Value)]);
      return $this;
    }

    public function PrependSibling(Element|string $Sibling): static
    {
      $this->PreSiblings[] = $Sibling = $Sibling instanceof Element ? $Sibling : new Text($Sibling);
      $Sibling->SetParent($this->Parent);
      return $this;
    }

    /**
     * Change the element, e.g. a button that should be a link. The attributes so far must suit the new tag.
     */
    protected function SetTag($NewTag): void
    {
      Spec::Element($NewTag);
      foreach ($this->Attributes as $Name => $Value)
      {
        Spec::CheckAttribute($NewTag, $Name, is_array($Value) ? implode(' ', $Value) : $Value);
      }
      if (!empty($this->Children) && Spec::Element($NewTag)['void'])
      {
        throw new \InvalidArgumentException("<$NewTag> is a void element and can't have content");
      }
      if ($this->Parent !== null)
      {
        Spec::CheckPlacement($NewTag, $this->Parent->Tag);
      }
      $this->Tag = $NewTag;
    }
  }
