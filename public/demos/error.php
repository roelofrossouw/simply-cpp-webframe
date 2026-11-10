<?php
  require_once __DIR__ . '/_demo.php';

    Config::Init(Access: Config::FrameworkURL(DEMOS));

    trigger_error("Raise user warning before", E_USER_ERROR);

    $Message = new \BS\Card();
    $Message->AddHeader('Welcome', 1);
    $Message->AddParagraph('This page is demonstrate user errors...');
    Page::AddContent($Message);

    trigger_error("Raise user warning after", E_USER_ERROR);
