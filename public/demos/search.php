<?php
  require_once __DIR__ . '/_demo.php';

  /**
   * The forms demo's server search (BS\MultiSelect::Source): GET ?q=text, answered with JSON [{value, text}]
   *
   * A real one looks the text up in the database (users, servers ...) and answers at most a page's worth.
   */

  Config::Init(Access: Config::FrameworkURL(DEMOS));

  $Towns = ['abq' => 'Aberdeen', 'alx' => 'Alexandria', 'bfn' => 'Bloemfontein', 'cpt' => 'Cape Town', 'dbn' => 'Durban',
    'els' => 'East London', 'grj' => 'George', 'jhb' => 'Johannesburg', 'kim' => 'Kimberley', 'kny' => 'Knysna',
    'mqp' => 'Mbombela', 'mtt' => 'Mthatha', 'plz' => 'Gqeberha', 'pzb' => 'Pietermaritzburg', 'ptg' => 'Polokwane',
    'pry' => 'Pretoria', 'rbg' => 'Rustenburg', 'stb' => 'Stellenbosch', 'upn' => 'Upington', 'wlk' => 'Welkom'];
  $Typed = mb_strtolower(trim((string)($_GET['q'] ?? '')));
  $Found = [];
  foreach ($Towns as $Value => $Text)
  {
    if ($Typed === '' || str_contains(mb_strtolower($Text), $Typed)) // nothing typed: the first page of them
    {
      $Found[] = ['value' => $Value, 'text' => $Text];
    }
  }
  header('Content-Type: application/json');
  echo json_encode(array_slice($Found, 0, 20));
