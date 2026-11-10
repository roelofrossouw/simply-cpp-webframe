<?php
  require_once __DIR__ . '/_demo.php';

  use BS\
  {Button, Card, Choices, Color, Container, DataInput, DatePicker, DateSelection, DateTimePicker, FileInput, Grid, Icon, Input, MultiSelect,
    NumberInput, OtpInput, PasswordInput, ScreenSize, TimePicker, Side, PadSize, SwitchInput, VerticalInputs};
  use HTML\
  {Form, InputType, Table, Text};

  Config::Init(Access: Config::FrameworkURL(DEMOS));

  $Container = new Container();
  $Container->Format->MaxWidth(900);
  $Container->Format->Padding(Side::All, PadSize::Pad3, ScreenSize::Medium);
  $Container->Format->Padding(Side::All, PadSize::Pad5, ScreenSize::Large);
  Page::AddContent($Container);

  // A form posts all its inputs to the page of the button clicked inside it
  $Container->AddChild($Form = new Form());
  $Form->AddChild($Card = new Card());
  $Card->AddHeader('Form');
  $Card->AddParagraph('Inputs have floating labels. Required and pattern checks run before posting: invalid inputs are listed in a popup and the form isn\'t posted.');

  $Card->AddChild($Grid = new Grid(2));
  $Grid->Columns(3, ScreenSize::Small);
  $Grid->Columns(4, ScreenSize::Medium);

  $Grid->Col($Input = new Input('input1', 'Required'));
  $Input->Required();
  $Input->ShowPostedValue();
  $Grid->Col($Input = new Input('input2', 'Amount', Prefix: 'R'));
  $Input->InputElement()->AddAttribute('pattern', '[0-9]+\.?[0-9]?[0-9]?');
  $Input->ShowPostedValue();
  $Grid->Col($Input = new Input('input3', 'Search', InputType::Search));
  $Input->ShowPostedValue();
  $Grid->Col($Input = new Input('input4', 'Email', InputType::Email));
  $Input->ShowPostedValue();
  $Grid->Col($Input = new Input('input5', 'Date', InputType::Date));
  $Input->ShowPostedValue();
  $Grid->Col($Input = new Input('input6', 'Number', InputType::Number));
  $Input->ShowPostedValue();
  $Grid->Col(new PasswordInput('input7', 'Password'));

  $Grid->NewRow();
  $Col = $Grid->Col($InputGroup = new VerticalInputs(), 9);
  $Col->Format->Columns(4, ScreenSize::Medium);
  $InputGroup->Format->Margin(Side::Top, PadSize::Pad2);
  $InputGroup->AddChild($Input = new Input('input8', 'VerticalInputs, first', Suffix: '%'));
  $Input->ShowPostedValue();
  $InputGroup->AddChild($Input = new Input('input9', 'VerticalInputs, second'));
  $Input->ShowPostedValue();
  $Input->PostOnEnter();

  // Dates with a calendar, and lists to choose several things from
  $Card->AddChild(new HTML\Header(3, 'Dates, times and choices'))->Format->Margin(Side::Top, PadSize::Pad3);
  $Card->AddParagraph('DatePicker opens a calendar (Bootstrap 6\'s Datepicker): it shows the date written out and posts it as YYYY-MM-DD. TimePicker has columns of hours and minutes to click (a 12-hour clock, seconds, steps and limits if asked) and posts HH:MM. MultiSelect shows what\'s chosen as chips, with a search, Select all and groups; it posts name[]. Single() makes it a one-choice search.');
  $Card->AddChild($Grid = new Grid(1));
  $Grid->Columns(2, ScreenSize::Medium);
  $Grid->Col($Date = new DatePicker('due', 'Due date (required, this year)', date('Y-m-d')));
  $Date->Min(date('Y') . '-01-01')->Max(date('Y') . '-12-31')->Required();
  $Date->ShowPostedValue();
  $Grid->Col($Date = new DatePicker('period', 'Period (a range, two months)', Selection: DateSelection::Range));
  $Date->Months(2);
  $Date->ShowPostedValue();
  $Grid->Col($Date = new DatePicker('workdays', 'Work days (several, no weekends)', Selection: DateSelection::Multiple));
  $Date->NoWeekends();
  $Date->ShowPostedValue();
  $Grid->Col($Time = new TimePicker('start', 'Starts at (15 minutes, 07:00 to 18:00)', '08:00'));
  $Time->Step(15)->Min('07:00')->Max('18:00');
  $Time->ShowPostedValue();
  $Grid->Col($Time = new TimePicker('alarm', 'Alarm (12-hour, seconds)'));
  $Time->TwelveHour()->Seconds();
  $Time->ShowPostedValue();
  $Grid->Col($Choice = new MultiSelect('cities', 'Cities (groups)', [
    'Gauteng' => ['jhb' => 'Johannesburg', 'pta' => 'Pretoria', 'sow' => 'Soweto'],
    'Western Cape' => ['cpt' => 'Cape Town', 'stb' => 'Stellenbosch', 'grg' => 'George'],
    'KwaZulu-Natal' => ['dbn' => 'Durban', 'pmb' => 'Pietermaritzburg'],
  ], ['cpt']));
  $Choice->ShowPostedValue();
  $Grid->Col($Choice = new MultiSelect('colours', 'Colours (at most 3, required)', ['red' => 'Red', 'orange' => 'Orange', 'yellow' => 'Yellow',
    'green' => 'Green', 'blue' => 'Blue', 'purple' => 'Purple']));
  $Choice->Max(3)->Required();
  $Choice->ShowPostedValue();
  $Grid->Col($Choice = new MultiSelect('country', 'Country (one, searchable)', ['za' => 'South Africa', 'na' => 'Namibia', 'bw' => 'Botswana',
    'zw' => 'Zimbabwe', 'mz' => 'Mozambique', 'ls' => 'Lesotho', 'sz' => 'Eswatini'], ['za']));
  $Choice->Single();
  $Choice->ShowPostedValue();
  $Grid->Col($Choice = new MultiSelect('flags', 'Flags (a count)', ['a' => 'Urgent', 'b' => 'Follow up', 'c' => 'Waiting', 'd' => 'Done'], ['a', 'b']));
  $Choice->Counter()->NoSearch();
  $Choice->ShowPostedValue();
  $Grid->Col($Choice = new MultiSelect('towns', 'Towns (searched on the server)', ['jhb' => 'Johannesburg'], ['jhb']));
  $Choice->Source(Demo('search.php'))->NoSelectAll();
  $Choice->ShowPostedValue();
  $Grid->Col($Choice = new MultiSelect('labels', 'Labels (type your own)', ['urgent' => 'Urgent', 'invoice' => 'Invoice']));
  $Choice->Creatable()->NoSelectAll();
  $Choice->ShowPostedValue();
  $Grid->Col($When = new DateTimePicker('meeting', 'Meeting (date and time)', date('Y-m-d') . ' 09:30'));
  $When->Step(15);
  $When->ShowPostedValue();

  // Choices, files, numbers, passwords and codes
  $Card->AddChild(new HTML\Header(3, 'Choices, files, numbers, passwords and codes'))->Format->Margin(Side::Top, PadSize::Pad3);
  $Card->AddParagraph('Choices is a group of checkboxes (posting name[]) or radios, stacked, side by side or as toggle buttons; Required() means at least one. FileInput takes files dropped on it or chosen, lists them and checks their size and kind; they arrive as $_FILES. NumberInput has − and + and a unit, shows the number as your language writes it and posts it plain. PasswordInput shows or hides what\'s typed, and Strength() rates it as Bootstrap 6 does. OtpInput takes a one-time code, a box for each character.');
  $Card->AddChild($Grid = new Grid(1));
  $Grid->Columns(2, ScreenSize::Medium);
  $Grid->Col($Pick = new Choices('days', 'Days (at least one)', ['mon' => 'Monday', 'tue' => 'Tuesday', 'wed' => 'Wednesday', 'thu' => 'Thursday',
    'fri' => 'Friday'], ['mon']));
  $Pick->Inline()->Required()->Feedback('Choose at least one day.');
  $Pick->ShowPostedValue();
  $Grid->Col($Pick = new Choices('size', 'Size (one, as buttons)', ['s' => 'Small', 'm' => 'Medium', 'l' => 'Large'], 'm', Single: true));
  $Pick->Buttons();
  $Pick->ShowPostedValue();
  $Grid->Col($Files = new FileInput('scan', 'A document (PDF or a picture, at most 5 MB)'));
  $Files->Accept('.pdf,image/*')->MaxSize(5 * 1024 * 1024);
  $Grid->Col($Files = new FileInput('photos', 'Photos (several, with previews)'));
  $Files->Multiple()->Accept('image/*')->Preview();
  $Grid->Col($Number = new NumberInput('quantity', 'Quantity (1 to 99)', 1));
  $Number->Min(1)->Max(99);
  $Number->ShowPostedValue();
  $Grid->Col($Number = new NumberInput('mass', 'Mass (steps of 0.5)', 1234.5, 'kg'));
  $Number->Decimals(1)->Step(0.5)->Min(0);
  $Number->ShowPostedValue();
  $Grid->Col($Password = new PasswordInput('newpassword', 'New password (with a strength meter)', 'new-password'));
  $Password->Strength(10);
  $Grid->Col($Code = new OtpInput('code', 'Code from the email (6 digits)'));
  $Code->Groups([3, 3]);
  $Code->ShowPostedValue();

  $Card->AddFooter($Buttons = new Grid(2));
  $Buttons->Columns(3, ScreenSize::Small);
  $Buttons->Columns(4, ScreenSize::Medium);
  $Buttons->Columns(5, ScreenSize::Large);
  $Buttons->Col(new Button('Submit', Icon::Check2, Server::Page()));
  $Buttons->Col($Button = new Button('Reset', Icon::ArrowRepeat, Server::Page()));
  $Button->SkipForm();
  $Button->Color(Color::Secondary);

  if (!empty($_POST))
  {
    $Container->AddChild($Card = new Card());
    $Card->AddHeader('Values posted');
    $Card->AddChild(new Text(var_export($_POST, true)));
    if (!empty($_FILES))
    {
      // What came of the files: their names, types and sizes (a real page would move them with move_uploaded_file())
      $Card->AddChild(new HTML\Header(4, 'Files posted'));
      $Card->AddChild(new Text(var_export(array_map(fn($File) => array_intersect_key($File, array_flip(['name', 'type', 'size', 'error'])), $_FILES), true)));
    }
  }

  // Updates without a form: each change posts straight away and the page answers with a toast
  $Container->AddChild($Card = new Card());
  $Card->AddHeader('Inline updates');
  $Card->AddParagraph('DataInput and SwitchInput post each change with OnChange("toast.call(this, url)"), together with their Data() values. The Users page grants access this way.');
  $Card->AddChild($Wrapper = new \BS\ResponsiveTable(ScreenSize::Large));
  $Table = $Wrapper->Table();
  $Row = $Table->Header()->AddRow();
  $Row->AddHeadingCell('Row');
  $Row->AddHeadingCell('Name');
  $Row->AddHeadingCell('Active');
  foreach (['First', 'Second', 'Third'] as $Number => $Name)
  {
    $Row = $Table->Body()->AddRow();
    $Row->AddRowHeadingCell((string)($Number + 1));

    $Input = new DataInput($Name);
    $Input->Data('row', $Number + 1);
    $Input->Data('field', 'name');
    $Input->OnChange("toast.call(this, '" . Demo('toast.php') . "');");
    $Row->AddCell($Input);

    $Switch = new SwitchInput($Number != 1);
    $Switch->Label('Row ' . ($Number + 1) . ' active', false);
    $Switch->Data('row', $Number + 1);
    $Switch->Data('field', 'active');
    $Switch->OnChange("toast.call(this, '" . Demo('toast.php') . "');");
    $Row->AddCell($Switch);
  }
