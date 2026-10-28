<?php
  require_once 'sc-webframe/autoload.php'; // On the include_path, see the README

  use Auth\Password;
  use BS\{AlertSuccess, AlertWarning, Button, Card, Icon, PadSize, ScreenSize, Side};

  // A new email address for a password account (Your profile): the link Password::SendEmailLink() sent to it makes it
  // the account's, which it signs in with from then on. Open to anyone, as a page of its own: the link is the proof.
  Config::Init(true, true);

  Page::AddContent($Card = new Card());
  $Card->Format->Center();
  $Card->Format->MaxWidth(ScreenSize::Small->Width());
  $Card->Format->Margin(Side::Top, PadSize::Pad4);
  $Card->AddHeader('Your new email address');

  // The link works while the password and the old address are the same, and nobody took the new one meanwhile
  $Account = Password::TokenAccount((string)QueryString::Get('token'), 'email');
  $New = Password::Address((string)($Account['token']['email'] ?? ''));
  if ($Account === null || $New === null || $Account['email'] !== ($Account['token']['old'] ?? null))
  {
    $Card->AddChild(new AlertWarning('This link doesn\'t work any more', 'It expired, or it was used already. Ask for a new one on Your profile.'));
    return;
  }
  if (($Problem = Storage\Store::Get()->SetEmail((int)$Account['user_id'], $New)) !== null)
  {
    $Card->AddChild(new AlertWarning('Not changed', $Problem));
    return;
  }
  $Card->AddChild(new AlertSuccess('Email address changed', "You sign in with $New from now on, with the same password."));
  $Card->AddChild($Open = new Button('Open ' . Config::AppName(), Icon::BoxArrowInRight));
  $Open->Link(Config::Origin());
  $Open->Format->Margin(Side::All, PadSize::Pad2);
