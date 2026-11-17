<?php

  namespace Chart;

  /**
   * Where a chart item's page opens when it's clicked (Chart::Open())
   */
  enum Open
  {
    case Page;      // in the page's main area (or the menu target the chart is in)
    case Popup;     // in the popup: the page returns a BS\ModalDialog
    case SidePanel; // in the side panel: the page returns a BS\OffCanvas

    // The class app.js goes by (as BS\Button's Modal() and OffCanvas())
    public function LinkClass(): ?string
    {
      return match ($this)
      {
        self::Page => null,
        self::Popup => 'modal-link',
        self::SidePanel => 'oc-link',
      };
    }
  }
