<?php
  require_once 'sc-webframe/autoload.php'; // On the include_path, see the README

  use Auth\Passkey;

  /**
   * Passkey options for the browser (js/passkey.js), as JSON, their challenge kept in the session:
   *   ?action=signin   - for signing in with any passkey for this site (anyone)
   *   ?action=register - for a new passkey for the signed in user (Your profile)
   */
  Config::Init(Anonymous: true);
  header('Content-Type: application/json');
  header('Cache-Control: no-store');

  if (!Passkey::Configured())
  {
    http_response_code(404);
    echo json_encode(['error' => 'Passkeys aren\'t on for this site.']);
    return;
  }
  switch (QueryString::Get('action'))
  {
    case 'signin':
      echo json_encode(Passkey::SignInOptions());
      break;
    case 'register':
      if (!Auth\Base::LoggedIn() || empty($UserID = (int)Auth\Base::ID()))
      {
        http_response_code(401);
        echo json_encode(['error' => 'Please sign in first.']);
        break;
      }
      $Profile = User::Profile($UserID);
      echo json_encode(Passkey::RegistrationOptions($UserID, (string)($Profile['email'] ?: "user $UserID"), User::DisplayName($Profile)));
      break;
    default:
      http_response_code(400);
      echo json_encode(['error' => 'Unknown action.']);
  }
