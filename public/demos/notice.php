<?php
  require_once __DIR__ . '/_demo.php';

    Config::Init(Access: Config::FrameworkURL(DEMOS));

    trigger_error("Raise user notice before");

    $Message = new BS\Card();
    $Message->AddHeader('Welcome', 1);
    $Message->AddParagraph('This page is demonstrate user notices...');
    Page::AddContent($Message);

    trigger_error("Raise user notice after");
