<?php

  // BS\Input's markup: the input group Bootstrap squares the inner corners of (no has-validation), with a prefix,
  // a suffix and the messages inside .form-floating
  require __DIR__ . '/check.inc';

  $Days = (string) new BS\Input('days', 'Last', HTML\InputType::Number, Suffix: 'days');
  Check(!str_contains($Days, 'has-validation'), 'no has-validation, whose corner rules expect a feedback element last');
  Check((bool) preg_match('~<div class="input-group">\s*<div class="form-floating">\s*<input[^>]*class="form-control".*</div>\s*<span class="input-group-text">days</span>\s*</div>~s', $Days),
        'the suffix follows .form-floating as the group\'s last child');

  $Price = (string) new BS\Input('price', 'Price', HTML\InputType::Number, Prefix: 'R');
  Check((bool) preg_match('~<div class="input-group">\s*<span class="input-group-text">R</span>\s*<div class="form-floating">~', $Price),
        'the prefix comes first');

  $Checked = (string) (new BS\Input('amount', 'Amount', HTML\InputType::Number, 'R', 'each'))->Feedback('Give an amount.');
  Check(!str_contains($Checked, 'has-validation'), 'still none with a message');
  Check((bool) preg_match('~<div class="form-floating">.*<div class="invalid-feedback">Give an amount.</div>\s*</div>\s*<span class="input-group-text">each</span>\s*</div>~s', $Checked),
        'the message stays inside .form-floating, next to the input, the suffix after it');


  // BS\FileInput: the file input in its drop zone, name[] and multiple with Multiple(), the checks js/app.js makes
  $File = (string)(new BS\FileInput('scan', 'Agreement'))->Accept('.pdf,image/*')->MaxSize(1048576)->Preview()->Required();
  Check(str_contains($File, 'class="file-drop"') && (bool)preg_match('~<div class="file-drop">\s*<input[^>]*type="file"~', $File), 'a file input inside the drop zone');
  Check(str_contains($File, 'data-bs-toggle="file-input"') && str_contains($File, 'name="scan"'), 'started by app.js, posted as scan');
  Check(str_contains($File, 'accept=".pdf,image/*"') && str_contains($File, 'data-bs-max-size="1048576"') && str_contains($File, 'data-bs-preview="true"'), 'Accept, MaxSize and Preview as attributes');
  Check(str_contains($File, 'required'), 'Required on the input');
  Check(str_contains($File, 'class="file-list list-unstyled"'), 'a list for the files chosen');
  $Files = (string)(new BS\FileInput('photos', 'Photos'))->Multiple();
  Check(str_contains($Files, 'name="photos[]"') && str_contains($Files, 'multiple'), 'Multiple: name[] and multiple');

  // BS\Choices: checkboxes posting name[], radios with Single, inline, toggle buttons, Required
  $Days = (new BS\Choices('days', 'Days', ['mon' => 'Monday', 'tue' => 'Tuesday', 'wed' => 'Wednesday'], ['tue']))->Inline()->Required();
  $Html = (string)$Days;
  Check(str_starts_with(trim($Html), '<fieldset') && str_contains($Html, '<legend class="form-label fs-6">Days</legend>'), 'a fieldset with the label as its legend');
  Same(3, substr_count($Html, 'name="days[]"'), 'a checkbox each, posting days[]');
  Check((bool)preg_match('~value="tue"[^>]*checked|checked[^>]*value="tue"~', $Html) && !preg_match('~value="mon"[^>]*checked~', $Html), 'the value given ticked, the others not');
  Same(3, substr_count($Html, 'form-check form-check-inline'), 'Inline: side by side');
  Check(str_contains($Html, 'data-bs-required="true"') && !preg_match('~\srequired[\s>/]~', $Html), 'Required: checked by app.js (a required checkbox would need every box ticked)');
  $_POST = ['days' => ['mon', 'wed']];
  $Days->ShowPostedValue();
  $Html = (string)$Days;
  Check((bool)preg_match('~value="wed"[^>]*checked|checked[^>]*value="wed"~', $Html) && !preg_match('~value="tue"[^>]*checked~', $Html), 'ShowPostedValue ticks what was posted');
  $Size = (string)(new BS\Choices('size', 'Size', ['s' => 'Small', 'l' => 'Large'], 's', Single: true))->Buttons()->Required();
  Same(2, substr_count($Size, 'type="radio"'), 'Single: radios');
  Check(str_contains($Size, 'name="size"') && !str_contains($Size, 'size[]'), 'posting one value');
  Check(str_contains($Size, 'class="btn-check"') && str_contains($Size, 'btn btn-outline-primary'), 'Buttons: btn-check toggles');
  Same(1, preg_match_all('~\srequired[\s>/]~', $Size), 'Required on the first radio: the browser checks the group');

  // BS\PasswordInput: the show/hide button in the group, Strength() as Bootstrap 6's meter
  $Password = (new BS\PasswordInput('password', 'Password', 'new-password'))->Strength(10, ['weak' => 'Too short']);
  $Html = (string)$Password;
  Check(str_contains($Html, 'type="password"') && str_contains($Html, 'autocomplete="new-password"'), 'a password field, its autocomplete');
  Check((bool)preg_match('~<div class="input-group">.*</div>\s*<button[^>]*data-bs-toggle="password"~s', $Html), 'the show/hide button ends the input group');
  Check(str_contains($Html, 'class="strength"') && str_contains($Html, 'data-bs-strength') && str_contains($Html, 'data-bs-min-length="10"'), 'Strength: .strength[data-bs-strength] with the minimum length');
  Same(4, substr_count($Html, 'strength-segment'), 'four segments');
  Check(str_contains($Html, 'minlength="10"') && str_contains($Html, 'strength-text'), 'minlength on the field, a place for the words');
  Check(str_contains($Html, 'data-bs-messages="{&quot;weak&quot;:&quot;Too short&quot;}"'), 'the words given as JSON');
  $SignIn = (string)new BS\PasswordInput('password', 'Password');
  Check(str_contains($SignIn, 'autocomplete="current-password"') && !str_contains($SignIn, 'strength'), 'without Strength: no meter');

  // BS\DateTimePicker: a DatePicker and a TimePicker, posting one value
  $When = (new BS\DateTimePicker('meeting', 'Meeting', '2026-11-30 09:30'))->Step(15)->Required();
  $Html = (string)$When;
  Check(str_contains($Html, 'data-bs-toggle="datetimepicker"') && str_contains($Html, 'data-bs-toggle="datepicker"') && str_contains($Html, 'data-bs-toggle="timepicker"'), 'a date and a time picker inside');
  Check((bool)preg_match('~<input[^>]*name="meeting"[^>]*value="2026-11-30 09:30"|<input[^>]*value="2026-11-30 09:30"[^>]*name="meeting"~', $Html), 'posting meeting as YYYY-MM-DD HH:MM');
  Check(str_contains($Html, 'value="2026-11-30"') && str_contains($Html, 'value="09:30"'), 'the two parts filled in');
  Check(str_contains($Html, 'data-bs-minute-step="15"') && substr_count($Html, 'required') == 2, 'Step on the time, Required on both');
  $Bare = (string)new BS\DateTimePicker('when', 'When', '2026-11-30');
  Check((bool)preg_match('~name="when"[^>]*value="2026-11-30 00:00"|value="2026-11-30 00:00"[^>]*name="when"~', $Bare), 'a date alone at 00:00');
  $T = (string)new BS\DateTimePicker('when', 'When', '2026-11-30T14:05');
  Check(str_contains($T, 'value="2026-11-30 14:05"'), 'a datetime-local value taken');

  // BS\NumberInput: − field unit +, posting the plain number in a hidden field
  $Mass = (new BS\NumberInput('mass', 'Mass', 1234.5, 'kg'))->Decimals(1)->Step(0.5)->Min(0)->Max(5000);
  $Html = (string)$Mass;
  Check((bool)preg_match('~<div class="input-group">\s*<button[^>]*data-bs-step-direction="-".*<div class="form-floating">.*<span class="input-group-text">kg</span>\s*<button[^>]*data-bs-step-direction="\+"~s', $Html), '− first, then the field, the unit and +');
  Check((bool)preg_match('~<input[^>]*type="hidden"[^>]*name="mass"[^>]*value="1234.5"|<input[^>]*name="mass"[^>]*type="hidden"[^>]*value="1234.5"~', $Html), 'posted plain in a hidden field');
  Check(!preg_match('~<input[^>]*data-bs-toggle="number"[^>]*name=~', $Html), 'the field shown posts nothing itself');
  Check(str_contains($Html, 'inputmode="decimal"') && str_contains($Html, 'data-bs-decimals="1"') && str_contains($Html, 'data-bs-step="0.5"')
        && str_contains($Html, 'data-bs-min="0"') && str_contains($Html, 'data-bs-max="5000"'), 'the settings as attributes');
  $_POST = ['mass' => '12.5'];
  $Mass->ShowPostedValue();
  Check(str_contains((string)$Mass, 'value="12.5"'), 'ShowPostedValue reads the hidden field\'s name');

  // BS\OtpInput: Bootstrap 6's markup
  $Code = (new BS\OtpInput('code', 'Code', 6))->Groups([3, 3])->Connected()->Required();
  $Html = (string)$Code;
  Check((bool)preg_match('~<div class="otp otp-connected"[^>]*data-bs-otp[^>]*>\s*<input[^>]*class="otp-input"~', $Html), '.otp[data-bs-otp] around .otp-input');
  Check(str_contains($Html, 'maxlength="6"') && str_contains($Html, 'autocomplete="one-time-code"') && str_contains($Html, 'inputmode="numeric"') && str_contains($Html, 'pattern="[0-9]{6}"'), 'length, autofill, the digit keyboard and a pattern');
  Check(str_contains($Html, 'data-bs-groups="[3,3]"') && str_contains($Html, 'required'), 'groups and Required');
  $Letters = (string)(new BS\OtpInput('code', 'Code', 4))->Alphanumeric()->Mask();
  Check(str_contains($Letters, 'data-bs-type="alphanumeric"') && str_contains($Letters, 'inputmode="text"') && str_contains($Letters, 'pattern="[A-Za-z0-9]{4}"') && str_contains($Letters, 'data-bs-mask="true"'), 'Alphanumeric and Mask');

  // BS\MultiSelect: Source and Creatable as attributes; values not among the options kept
  $Owner = (new BS\MultiSelect('owner', 'Owner', [], ['42']))->Source('/users.php?action=search', 2);
  $Html = (string)$Owner;
  Check(str_contains($Html, 'data-bs-source="/users.php?action=search"') && str_contains($Html, 'data-bs-source-min-length="2"'), 'Source: the URL and the shortest search');
  Check((bool)preg_match('~<option[^>]*value="42"[^>]*selected|<option[^>]*selected[^>]*value="42"~', $Html), 'a chosen value not among the options kept, chosen');
  $Labels = (new BS\MultiSelect('labels', 'Labels', ['urgent' => 'Urgent']))->Creatable();
  $_POST = ['labels' => ['urgent', 'invoice']];
  $Labels->ShowPostedValue();
  $Html = (string)$Labels;
  Check(str_contains($Html, 'data-bs-creatable="true"') && (bool)preg_match('~<option[^>]*value="invoice"[^>]*>invoice</option>~', $Html), 'Creatable: a typed value posted back stays');
  $Plain = new BS\MultiSelect('tags', 'Tags', ['a' => 'A']);
  $Plain->Value(['zz']);
  Check(!str_contains((string)$Plain, 'zz'), 'without them, an unknown value isn\'t added');

  // The form post (js/app.js) sends the FormData itself, so name[] keeps every value and files go too
  $App = file_get_contents(__DIR__ . '/../public/js/app.js');
  Check(!str_contains($App, 'Object.fromEntries(new FormData'), 'the form isn\'t flattened into an object');
  Check((bool)preg_match('~data: new FormData\(form\),\s*(//.*\s*)*processData: false,\s*contentType: false~', $App), 'FormData posted as it is');
  Check(str_contains(file_get_contents(__DIR__ . '/../php/Framework.php'), 'bootstrap@5.3.8/') && str_contains(file_get_contents(__DIR__ . '/../public/css/style.css'), 'bootstrap@5.3.8/'), 'Bootstrap 5.3.8, script and stylesheet');
  $_POST = [];
