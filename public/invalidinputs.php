<?php
  require_once 'sc-webframe/autoload.php'; // On the include_path, see the README
  Config::Init(true);

  $Dialog = new BS\ModalDialog("Invalid Inputs", \BS\ScreenSize::ExtraSmall);

  $Dialog->AddParagraph('You have some invalid inputs');
  $Dialog->AddChild($List = new \HTML\UnorderedList());

  foreach ($_POST as $key => $value)
  {
    $List->AddItem($Item = new \HTML\Bold($value['name']));
    $Item->AddSibling(new \HTML\Text(': ' . $value['message']));
  }


  Page::AddContent($Dialog);
