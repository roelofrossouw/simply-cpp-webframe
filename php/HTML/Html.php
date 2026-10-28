<?php

  namespace HTML;

  /**
   * <html> Defines the root of an HTML document
   *
   * @property string $Xmlns http://www.w3.org/1999/xhtml
   * @see https://www.w3schools.com/tags/tag_html.asp
   */
  class HTML extends Element
  {
    public function __construct(string $Lang = 'en')
    {
      parent::__construct('html');
      $this->AddAttribute('lang', $Lang);
    }

    public function __toString(): string
    {
      return '<!doctype html>' . PHP_EOL . parent::__toString();
    }
  }
