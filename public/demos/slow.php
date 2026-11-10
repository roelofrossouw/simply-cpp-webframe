<?php
  require_once __DIR__ . '/_demo.php';

    Config::Init(Access: Config::FrameworkURL(DEMOS));
    sleep(1);

    Page::AddContent(new \BS\AlertWarning('Slow Page', 'This page is slow so you can see the UI'));