<?php
  require_once __DIR__ . '/_demo.php';

  use BS\{ModalDialog, OffCanvas};

  $InPanel = QueryString::Get('in') === 'panel';
  Config::Init(Access: Config::FrameworkURL(DEMOS), Shown: $InPanel ? \BS\Dismiss::OffCanvas : \BS\Dismiss::Modal);

  // What a chart's item opened (charts.php): its placeholders, as a popup or a side panel would show the item's page
  $Values = array_filter(['What' => QueryString::Get('what'), 'Month' => QueryString::Get('label'), 'Month number' => QueryString::Get('month'),
    'Domain' => QueryString::Get('domain'), 'Value' => QueryString::Get('value')], fn($Value) => $Value !== '' && $Value !== null);
  $Title = 'Clicked: ' . (QueryString::Get('what') ?: QueryString::Get('domain') ?: QueryString::Get('label') ?: 'chart item');
  $Panel = $InPanel ? new OffCanvas($Title) : new ModalDialog($Title);
  Page::AddContent($Panel);
  $Panel->AddChild(new \HTML\Paragraph('A real page would show this item: the month\'s mails, the domain\'s recipients. It was given:'));
  $Panel->AddChild($List = new \HTML\DescriptionList());
  foreach ($Values as $Name => $Value)
  {
    $List->AddChild(new \HTML\DescriptionTerm($Name));
    $List->AddChild(new \HTML\DescriptionDetails($Value));
  }
