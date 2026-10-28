<?php

  namespace HTML;

  /**
   * <object> Defines a container for an external application
   *
   * @property string $Data url
   * @property string $Form id
   * @property string|int $Height pixels
   * @property string $Name text
   * @property string $Type media type
   * @property string $UseMap #mapname
   * @property string|int $Width pixels
   * @see https://www.w3schools.com/tags/tag_object.asp
   */
  class EmbeddedObject extends Element
  {
    public function __construct(Element|string|null $Content = null, ?string $ID = null)
    {
      parent::__construct('object', $ID);
      if ($Content !== null && $Content !== '')
      {
        $this->AddChild($Content);
      }
    }
  }
