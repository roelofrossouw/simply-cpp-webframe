<?php

  namespace HTML;

  /**
   * <article> Defines an article
   *
   * @see https://www.w3schools.com/tags/tag_article.asp
   */
  class Article extends Element
  {
    public function __construct(Element|string|null $Content = null, ?string $ID = null)
    {
      parent::__construct('article', $ID);
      if ($Content !== null && $Content !== '')
      {
        $this->AddChild($Content);
      }
    }
  }
