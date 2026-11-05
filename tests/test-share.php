<?php

  // What a shared link shows (Framework's head): the description, Open Graph's tags as property, a Twitter/X card, the
  // share title, and the image only when the site has it, with its size read from the file or the settings
  require __DIR__ . '/check.inc';

  $_SERVER['HTTP_HOST'] = 'www.example.com';
  $_SERVER['SCRIPT_NAME'] = $_SERVER['PHP_SELF'] = '/home.php';
  $Root = sys_get_temp_dir() . '/sc-webframe-share-' . getmypid();
  mkdir("$Root/img", 0777, true);
  $_SERVER['DOCUMENT_ROOT'] = $Root;
  register_shutdown_function(fn() => array_map('unlink', glob("$Root/img/*")) && rmdir("$Root/img") && rmdir($Root));
  // A PNG's signature and header are all getimagesize() reads: 1200 x 630
  file_put_contents("$Root/img/og-image.png", "\x89PNG\r\n\x1a\n" . pack('N', 13) . 'IHDR' . pack('NNCCCCC', 1200, 630, 8, 6, 0, 0, 0) . pack('N', 0));
  file_put_contents("$Root/img/share.svg", '<svg xmlns="http://www.w3.org/2000/svg"/>');
  Config::Load();

  function Head(): string
  {
    return (string) new Framework();
  }

  function Has(string $Html, string $Tag, string $Value): bool
  {
    $Kind = str_starts_with($Tag, 'og:') ? 'property' : 'name';
    return str_contains($Html, "<meta $Kind=\"$Tag\" content=\"" . htmlspecialchars($Value) . '"');
  }

  Config::Set('app.description', 'Containers & more');
  Config::Set('app.image', '/img/og-image.png');
  $Html = Head();
  Check(Has($Html, 'description', 'Containers & more'), 'a description tag');
  Check(Has($Html, 'og:title', 'Test site') && Has($Html, 'og:description', 'Containers & more'), 'og:title and og:description as property');
  Check(!str_contains($Html, 'name="og:'), 'no Open Graph tag as name');
  Check(Has($Html, 'og:site_name', 'Example Ltd'), 'og:site_name from app.company');
  Check(Has($Html, 'og:image', 'https://www.example.com/img/og-image.png'), 'og:image on the site');
  Check(Has($Html, 'og:image:width', '1200') && Has($Html, 'og:image:height', '630'), 'its size, read from the PNG');
  Check(Has($Html, 'og:image:alt', 'Test site'), 'its text: the title');
  Check(Has($Html, 'twitter:card', 'summary_large_image') && Has($Html, 'twitter:title', 'Test site') &&
        Has($Html, 'twitter:description', 'Containers & more') && Has($Html, 'twitter:image', 'https://www.example.com/img/og-image.png'),
        'a Twitter/X card with the picture');
  Check(str_contains($Html, '<title>Test site</title>'), 'the tab keeps its title');

  Config::Set('app.share_title', '1Web MAN');
  Config::Set('app.image_alt', 'The 1Web logo');
  $Html = Head();
  Check(Has($Html, 'og:title', '1Web MAN') && Has($Html, 'twitter:title', '1Web MAN'), 'app.share_title for what is shared');
  Check(str_contains($Html, '<title>Test site</title>'), 'but not the tab');
  Check(Has($Html, 'og:image:alt', 'The 1Web logo') && Has($Html, 'twitter:image:alt', 'The 1Web logo'), 'app.image_alt');

  Config::Set('app.image', '/img/share.svg');
  Config::Set('app.image_width', '800');
  Config::Set('app.image_height', '400');
  $Html = Head();
  Check(Has($Html, 'og:image:width', '800') && Has($Html, 'og:image:height', '400'), 'not a PNG or JPEG: the size from the settings');

  Config::Set('app.image', '/img/missing.png');
  $Html = Head();
  Check(!str_contains($Html, 'og:image') && !str_contains($Html, 'twitter:image'), 'an image the site doesn\'t have is left out');
  Check(Has($Html, 'twitter:card', 'summary'), 'and the card is a plain summary');

  Config::Set('app.image', 'https://cdn.example.net/share.jpg');
  Check(Has(Head(), 'og:image', 'https://cdn.example.net/share.jpg'), 'an image elsewhere, as it is');

  Config::Set('app.description', '');
  $Html = Head();
  Check(!str_contains($Html, 'name="description"') && !str_contains($Html, 'og:description'), 'no description: no empty tags');
