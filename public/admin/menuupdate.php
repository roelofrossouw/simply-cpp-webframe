<?php
  require_once 'sc-webframe/autoload.php'; // On the include_path, see the README

  // Saves one field or right of a menu item, posted by the inputs on menu.php (id, field or code and right, val)
  Config::Init(Access: Config::FrameworkURL('admin/menu.php'), Shown: \BS\Dismiss::Toast, Edit: true);

  Page::AddContent($Toast = new \BS\Toast('Menu'));

  $ID = filter_var(Post::Get('id'), FILTER_VALIDATE_INT);
  $Code = Post::Get('code');
  $Right = (string)(Post::Get('right') ?? 'view');
  $Field = (string)Post::Get('field');
  $Value = (string)Post::Get('val');
  $Problem = match (true)
  {
    $ID === false || !array_key_exists($ID, MenuLayout::All()) => 'Unknown menu item.',
    $Code !== null => MenuLayout::SetAccess($ID, (string)$Code, $Right, $Value === 'true'),
    default => MenuLayout::Update($ID, $Field, $Value),
  };
  if ($Problem !== null)
  {
    $Toast->Color(\BS\Color::Warning);
    $Toast->AddChild($Problem . ' Reload the page to see what is saved.');
    return;
  }
  $Saved = ['view' => [' may now view it.', ' may no longer view or edit it.'], 'edit' => [' may now view and edit it.', ' may now only view it.']];
  $Toast->AddChild(($Code !== null ? ucfirst((string)$Code) . ($Saved[$Right] ?? $Saved['view'])[$Value === 'true' ? 0 : 1]
    : (MenuLayout::FIELDS[$Field] ?? 'Item') . ' saved.') . ' The menu changes when the page is next loaded.');
