<?php

  // The start page: the menu and Home are loaded into it by the package's js/app.js
  require_once 'sc-webframe/autoload.php';

  Config::Init(Anonymous: true, Landing: true);
  Page::Landing();
