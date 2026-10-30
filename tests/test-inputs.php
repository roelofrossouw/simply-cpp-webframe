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

