<?php
  require_once __DIR__ . '/_demo.php';

  /**
   * Toast endpoint for the demos
   *
   * Called with toast.call(this, Demo('toast.php')), which posts the element's data values plus 'val' (the
   * input's value, or true/false for a switch). A real endpoint updates the database here, see usergrant.php.
   */

  Config::Init(Access: Config::FrameworkURL(DEMOS), Shown: \BS\Dismiss::Toast);

  Page::AddContent($Toast = new \BS\Toast('Demo'));

  // Toasts only come in the theme colours, ignore anything else that was posted
  if (($Color = \BS\Color::tryFrom((string)Post::Get('color'))) && $Color->IsTheme())
  {
    $Toast->Color($Color);
  }

  if (!empty($Field = Post::Get('field')))
  {
    $Toast->AddChild("Row " . Post::Get('row') . ": {$Field} would be saved as '" . Post::Get('val') . "'. (Demo, nothing is stored)");
    return;
  }

  $Toast->AddChild(Post::Get('message') ?? 'Toasts show the result of an action without leaving the page.');
