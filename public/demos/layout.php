<?php
  require_once __DIR__ . '/_demo.php';

    use BS\
    {AlertBox, AlertError, AlertInfo, AlertWarning, Button, Card, Display, Icon, ScreenSize, Side};
    use HTML\Paragraph;

    Config::Init(Access: Config::FrameworkURL(DEMOS));

    $Card = new Card();
    Page::AddContent($Card);
    $Card->AddHeaderElement($Button = new Button('Refresh', Icon::ArrowRepeat, Server::Page()));
    $Button->Format->Float(Side::Right);
    $Card->AddHeader('Sizing sample');

    $Card->AddParagraph(date('H:i:s'));
    $Card->AddChild(new \HTML\Header(5, 'Some layout examples, change the window size (width) to see how it adapts...'));

    $Card->AddChild($Grid = new \BS\Grid());

    $Header = 'Only on Extra Small';
    $Body = new Paragraph('The screen is detected to be extra small (xs).');
    $Body->AddParagraph('Generally the case with most phones in portrait mode');
    $Body->AddParagraph('Expand (Or turn your phone) to see more...');
    $Col = $Grid->Col(new AlertError($Header, $Body));
    // Hide from size small (Everything except Extra Small)
    $Col->Format->Display(Display::None, ScreenSize::Small);

    $Header = 'From Small';
    $Body = 'A simple alert on small screen (Phone on landscape) and up, make the screen smaller to get rid of me. I give up half the screen width up when medium comes in';
    $Col = $Grid->Col(new AlertInfo($Header, $Body));
    $Col->Format->Display(Display::None);
    $Col->Format->Display(Display::Block, ScreenSize::Small);
    // Only 6 wide from medium size...
    $Col->Format->Columns(6, ScreenSize::Medium);

    $Header = 'From Medium';
    $Body = 'I am on a medium screen and up, using half the screen, but will give up 25% for large.';
    $Col = $Grid->Col(new AlertWarning($Header, $Body));
    $Col->Format->Display(Display::None);
    $Col->Format->Display(Display::Block, ScreenSize::Medium);
    // Only 6 wide from medium size...
    $Col->Format->Columns(6);
    $Col->Format->Columns(3, ScreenSize::Large);

    $Header = 'From Large';
    $Body = 'I am on a large screen and up, using 25%...';
    $Col = $Grid->Col(new AlertError($Header, $Body));
    $Col->Format->Display(Display::None);
    $Col->Format->Display(Display::Block, ScreenSize::Large);
    $Col->Format->Columns(3);

    // Grid with flex-rows.
    $Card->AddChild($Container = new \BS\Grid());
    $Container->Format->Display(Display::None);
    $Container->Format->Display(Display::Block, ScreenSize::Small);

    // Flex row to show auto sized items
    $Container->NewFlexRow();
    $Container->Col($Cell = new AlertBox());
    $Cell->AddHeader('This is a flex cell');
    $Cell->AddParagraph('Will fit the content automatically');

    $Container->Col($Cell = new AlertBox());
    $Cell->AddHeader('More...');
    $Cell->AddParagraph('I\'m also flex');

    $Container->Col($Cell = new AlertBox());
    $Cell->AddHeader('Another flex');
    $Cell->AddParagraph('content only');


    // New row to show a cell that grows
    $Container->NewFlexRow();

    $Container->Col($Cell = new AlertBox());
    $Cell->AddHeader('This is a flex cell');
    $Cell->AddParagraph('Will fit the content automatically');


    $Col = $Container->Col($Cell = new AlertBox());
    $Col->Format->FlexGrow();
    $Cell->AddHeader('Stretchy.');
    $Floaty = $Cell->AddParagraph('See?');
    $Floaty->Format->Float(Side::Right);
    $Paragraph = $Cell->AddParagraph('I\'m also flex');
    $Paragraph->AddLine('Stretchy to fill');
    $Paragraph->AddLine('all available space');


    $Container->Col($Cell = new AlertBox());
    $Cell->AddHeader('Another flex');
    $Cell->AddParagraph('content only');

    // New row to demonstrate a greedy cell
    $Container->NewFlexRow();

    $Container->Col($Cell = new AlertBox());
    $Cell->AddHeader('This is a flex cell');
    $Cell->AddParagraph('Will fit the content automatically');

    $Container->Col($Cell = new AlertBox());
    $Cell->AddHeader('Bigger...');
    $Cell->AddParagraph('I\'m also flex, but I am very wide, so columns will be shrunk. So each column will keep its proportion, But that is a bit mean to the smallies...?');

    $Container->Col($Cell = new AlertBox());
    $Cell->AddHeader('Another flex');
    $Cell->AddParagraph('content only');


    // New row to demonstrate a greedy cell and a non shrinking cell
    $Container->NewFlexRow();

    $Container->Col($Cell = new AlertBox());
    $Cell->AddHeader('This is a flex cell');
    $Cell->AddParagraph('Will fit the content automatically');

    $Container->Col($Cell = new AlertBox());
    $Cell->AddHeader('Bigger...');
    $Cell->AddParagraph('I\'m also flex, but I am very wide, so columns will be shrunk. So each column will keep its proportion, But that is a bit mean to the smallies...?');

    $Container->Col($Cell = new AlertBox());
    $Cell->AddHeader('Another flex');
    $Cell->AddParagraph('I refuse to shrink. I want to be big.');
    $Cell->Format->NoShrink();

