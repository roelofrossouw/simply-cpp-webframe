<?php
  require_once 'sc-webframe/autoload.php'; // On the include_path, see the README

  use BS\{Button, Color, Icon, PadSize, ScreenSize, Side, SwitchInput, Toast};

  Config::Init();

  // Administer → Menu: the menu setup (framework.menu, see sql/schema.sql), a tab per section. Adding and removing post
  // back to this page; changing a field or a right saves through menuupdate.php
  $Update = json_encode(Config::FrameworkURL('admin/menuupdate.php'), JSON_UNESCAPED_SLASHES);
  $Here = Config::FrameworkURL('admin/menu.php');
  $Notice = null;
  $Action = QueryString::Get('action');
  // The menu's Edit right on this page; without it the layout shows, but can't be changed
  $CanEdit = \Auth\Base::CanEdit();
  if ($Action !== null && !$CanEdit)
  {
    $Notice = Toast::Notice('Not saved', 'You may look at the menu, but not change it.', false);
  }
  elseif ($Action == 'add')
  {
    $Parent = filter_var(Post::Get('parent_id'), FILTER_VALIDATE_INT);
    $Problem = MenuLayout::Add((string)Post::Get('name'), $Parent === false ? null : $Parent, (string)Post::Get('code'),
      (string)Post::Get('target'), (string)Post::Get('icon'), (string)QueryString::Get('section'));
    $Notice = $Problem === null ? Toast::Notice('Item added', 'It has its parent\'s view and edit rights; the menu shows it when you next load the page.')
      : Toast::Notice('Not added', $Problem, false);
  }
  elseif ($Action == 'delete')
  {
    $Problem = MenuLayout::Delete((int)QueryString::Get('id'));
    $Notice = $Problem === null ? Toast::Notice('Item removed') : Toast::Notice('Not removed', $Problem, false);
  }

  $Items = MenuLayout::All();
  $Levels = User::Levels();
  // The tab to show: the one just added to or removed from
  $Current = array_key_exists((string)QueryString::Get('section'), MenuLayout::SECTIONS) ? QueryString::Get('section') : 'main';

  Page::AddContent($Result = new HTML\Div());
  $Result->Format->Padding(Side::All, PadSize::Pad3);
  $Result->Format->MaxWidth(ScreenSize::ExtraExtraLarge->Width());
  $Result->Format->Center();
  if ($Notice !== null)
  {
    $Result->AddChild($Notice);
  }
  $Result->AddChild($About = new HTML\Paragraph('Every page is here: the main menu at the top, the status bar at the bottom, '
    . 'and free-standing pages that no menu shows but that still need rights. View lets an access level see an item and open '
    . 'its page (an item without a target shows when an item under it does); Edit also lets it change things there, and '
    . 'includes View. Public items are open to anyone, also without signing in; their rights only give Edit. Shown limits an '
    . 'item to visitors who are signed in or out. Pages check all this themselves, also when opened without the menu. '
    . 'Changes save as you leave a field and show when the page is next loaded; reopen Menu to see a new order.'));
  $About->Format->Color(Color::BodySecondary);

  // A text input that saves one field of an item
  $Input = function (array $Item, string $Field, ?string $Value, \HTML\InputType $Type = \HTML\InputType::Text) use ($Update): \BS\DataInput
  {
    $Input = new \BS\DataInput($Value ?? '', $Type);
    $Input->AddAttribute('aria-label', MenuLayout::FIELDS[$Field] . ' of ' . $Item['name']);
    $Input->Data('id', (int)$Item['id']);
    $Input->Data('field', $Field);
    $Input->OnChange('toast.call(this, ' . $Update . ');');
    return $Input;
  };
  // A switch that saves one yes/no field of an item
  $Toggle = function (array $Item, string $Field, string $Label) use ($CanEdit, $Update): SwitchInput
  {
    $Switch = new SwitchInput($Item[$Field] === 't');
    $Switch->Label($Label, false);
    $Switch->Data('id', (int)$Item['id']);
    $Switch->Data('field', $Field);
    $Switch->OnChange('toast.call(this, ' . $Update . ');');
    if (!$CanEdit)
    {
      $Switch->Disabled();
    }
    return $Switch;
  };
  // A drop-down that saves one field of an item
  $Choice = function (array $Item, string $Field, array $Options, ?string $Empty = null) use ($Update): \HTML\Select
  {
    $Select = new \HTML\Select();
    $Select->AddClasses(['form-select', 'form-select-sm']);
    $Select->AddAttribute('aria-label', MenuLayout::FIELDS[$Field] . ' of ' . $Item['name']);
    if ($Empty !== null)
    {
      $Select->AddChild($Option = new \HTML\Option($Empty));
      $Option->Value = '';
    }
    foreach ($Options as $Value => $Caption)
    {
      $Select->AddChild($Option = new \HTML\Option($Caption));
      $Option->Value = (string)$Value;
      $Option->Selected = (string)$Value === (string)$Item[$Field];
    }
    $Select->Data('id', (int)$Item['id']);
    $Select->Data('field', $Field);
    $Select->OnChange('toast.call(this, ' . $Update . ');');
    return $Select;
  };
  // Without the Edit right every control is shown, but can't be used
  $ReadOnly = function (\HTML\Element $Control) use ($CanEdit): \HTML\Element
  {
    if (!$CanEdit)
    {
      $Control->AddAttribute('disabled', 'disabled');
    }
    return $Control;
  };

  $HasChildren = array_count_values(array_filter(array_column($Items, 'parent_id')));
  $Result->AddChild($Tabs = new \BS\Tabs());
  foreach (MenuLayout::SECTIONS as $Section => $SectionName)
  {
    $InSection = array_filter($Items, fn($Item) => ($Item['section'] ?? 'main') === $Section);
    $Pane = $Tabs->AddTab($SectionName . ' (' . count($InSection) . ')', '', $Section === $Current);

    // Scrolls sideways inside its own box on any screen: its many columns are wider than the page can be, and the page
    // area itself doesn't scroll sideways
    $Pane->AddChild($Wrapper = new \BS\ResponsiveTable(ScreenSize::ExtraSmall));
    $Table = $Wrapper->Table();
    $Table->Striped();
    $Table->Small();

    $Row = $Table->Header()->AddRow();
    $Row->StickyTop();
    foreach (['Name', 'Icon', 'Target', 'ID', 'Popup', 'Public', 'Shown', 'Position', 'Parent'] as $Caption)
    {
      $Row->AddHeadingCell($Caption);
    }
    foreach ($Levels as $Code => $Description)
    {
      // A thin line between the access levels, each has two switches
      $Cell = $Row->AddHeadingCell(ucfirst($Code));
      $Cell->AddAttribute('title', $Description . ': view, edit');
      $Cell->AddClass('border-start');
    }
    $Row->AddHeadingCell('')->AddClass('border-start');

    foreach ($InSection as $ID => $Item)
    {
      $Row = $Table->Body()->AddRow();

      // Indented by depth, so the tree shows
      $Cell = $Row->AddCell($ReadOnly($Name = $Input($Item, 'name', $Item['name'])));
      $Cell->AddStyle('padding-left', (0.25 + 1.5 * $Item['depth']) . 'rem');
      $Name->AddStyle('min-width', '10rem');
      if ($Item['target'] === null)
      {
        $Name->AddClass('fw-bold');
      }

      $Cell = $Row->AddCell($ReadOnly($Icon = $Input($Item, 'icon', $Item['icon'])));
      $Cell->AddClasses(['d-flex', 'align-items-center', 'gap-1']);
      $Cell->PrependChild((Icon::tryFrom($Item['icon']) ?? Icon::Circle)->Element());
      $Icon->AddStyle('min-width', '9rem');

      $Row->AddCell($ReadOnly($Target = $Input($Item, 'target', $Item['target'])));
      $Target->AddAttribute('placeholder', 'Submenu');
      $Target->AddStyle('min-width', '14rem');

      $Row->AddCell($ReadOnly($Input($Item, 'code', $Item['code'])))->AddStyle('min-width', '8rem');
      $Row->AddCell($Toggle($Item, 'popup', 'Open ' . $Item['name'] . ' in the popup'));
      $Row->AddCell($Toggle($Item, 'public', 'Anyone may open ' . $Item['name']));
      $Row->AddCell($ReadOnly($Choice($Item, 'shown', MenuLayout::SHOWN)))->AddStyle('min-width', '8rem');

      $Row->AddCell($ReadOnly($Position = $Input($Item, 'position', $Item['position'], \HTML\InputType::Number)));
      $Position->AddStyle('width', '4.5rem');

      $Row->AddCell($ReadOnly($Choice($Item, 'parent_id', MenuLayout::Parents((int)$ID), '(top)')))->AddStyle('min-width', '10rem');

      // A View and an Edit switch per access level; the toast's reply says what changed, reload to see the other switch
      foreach ($Levels as $Code => $Description)
      {
        $Rights = new HTML\Div();
        $Rights->AddClasses(['d-flex', 'gap-2', 'text-nowrap']);
        foreach (MenuLayout::RIGHTS as $Right => $Caption)
        {
          $Switch = new SwitchInput(in_array($Code, $Item[$Right == 'view' ? 'access' : 'edit'], true));
          $Switch->Label($Caption);
          $Switch->AddAttribute('title', ucfirst($Code) . ' may ' . strtolower($Caption) . ' ' . $Item['name']);
          $Switch->Data('id', (int)$ID);
          $Switch->Data('code', $Code);
          $Switch->Data('right', $Right);
          $Switch->OnChange('toast.call(this, ' . $Update . ');');
          if (!$CanEdit)
          {
            $Switch->Disabled();
          }
          $Rights->AddChild($Switch);
        }
        $Row->AddCell($Rights)->AddClass('border-start');
      }

      if ($CanEdit && empty($HasChildren[$ID]))
      {
        $Cell = $Row->AddCell($Remove = new Button(null, Icon::Trash,
          $Here . '?' . http_build_query(['action' => 'delete', 'id' => $ID, 'section' => $Section])));
        $Remove->Color(Color::Danger);
        $Remove->AddClass('btn-sm');
        $Remove->AddAttribute('aria-label', 'Remove ' . $Item['name']);
      }
      else
      {
        $Cell = $Row->AddCell(null);
      }
      $Cell->AddClass('border-start');
    }

    if (!$CanEdit)
    {
      continue;
    }

    // Add an item to this section
    $Pane->AddChild($Heading = new HTML\Header(5, 'Add an item to the ' . lcfirst($SectionName)));
    $Heading->Format->Margin(Side::Top, PadSize::Pad4);
    $Pane->AddChild($Form = new HTML\Form());
    $Form->AddChild($Grid = new \BS\Grid(1));
    $Grid->Columns(5, ScreenSize::Medium);
    $Grid->Col($Name = new \BS\Input('name', 'Name'));
    $Name->InputElement()->Required = true;
    $Grid->Col(new \BS\Select('parent_id', 'Under', MenuLayout::Parents(null, $Section), null, '(top)'));
    $Grid->Col($Target = new \BS\Input('target', 'Target'));
    $Target->InputElement()->AddAttribute('placeholder', 'Empty for a submenu');
    $Grid->Col($Code = new \BS\Input('code', 'ID'));
    $Code->InputElement()->Required = true;
    $Grid->Col($Icon = new \BS\Input('icon', 'Icon'));
    $Icon->InputElement()->AddAttribute('placeholder', 'e.g. bi-people');
    $Form->AddChild($Hint = new HTML\Small('The target is the script it loads, e.g. /app/orders.php. The ID names the link, e.g. orders: lower case, unique. The icon is a Bootstrap icon class, e.g. bi-people (see icons.getbootstrap.com).'));
    $Hint->AddClass('d-block');
    $Hint->Format->Color(Color::BodySecondary);
    $Form->AddChild($Add = new Button('Add item', Icon::Plus, $Here . '?' . http_build_query(['action' => 'add', 'section' => $Section])));
    $Add->Format->Margin(Side::Top, PadSize::Pad3);
  }
