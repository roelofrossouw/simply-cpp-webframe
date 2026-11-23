<?php

  // Page usage: Config::Init() hands a signed-in user's view to a storage that keeps them (Storage\PageViews), after the
  // page has gone out; never for a refused page, an anonymous one, a storage without it or with usage off. Each page in a
  // process of its own, as PHP runs it; the views go to a file (SC_WEBFRAME_TEST_VIEWS).
  require __DIR__ . '/check.inc';
  require __DIR__ . '/memory.inc';

  // A storage that keeps page usage: each view a JSON line, with whether the page was out by then
  class ViewStorage extends MemoryStorage implements Storage\PageViews
  {
    public bool $Fail = false;

    public function PageView(int $UserID, string $Page, string $Shown, ?string $Request): void
    {
      file_put_contents(getenv('SC_WEBFRAME_TEST_VIEWS'), json_encode(['user' => $UserID, 'page' => $Page, 'shown' => $Shown,
          'request' => $Request, 'after_page' => headers_sent()]) . "\n", FILE_APPEND);
      if ($this->Fail)
      {
        throw new RuntimeException('the agent is down');
      }
    }
  }

  $Case = $argv[1] ?? null;
  if ($Case !== null)
  {
    // One page: signed in as user 7, an admin (unless anonymous), Init() called as the case says
    $Checks = -1; // This process only prints the page
    $_SERVER['HTTP_HOST'] = 'www.example.com';
    $_SERVER['HTTP_X_REQUESTED_BY'] = 'sc-webframe';
    $_SERVER['SCRIPT_NAME'] = $_SERVER['PHP_SELF'] = '/app/report.php';
    $_SERVER['REQUEST_URI'] = '/app/report.php?part=totals';
    Config::Load();
    Storage\Store::Use($Storage = in_array($Case, ['plain'], true) ? new MemoryStorage() : new ViewStorage());
    $Storage->Grants[7] = ['public', 'admin'];
    if ($Storage instanceof ViewStorage)
    {
      $Storage->Fail = $Case === 'failing';
    }
    if ($Case === 'off')
    {
      Config::Set('usage', false);
    }
    if ($Case !== 'anonymous')
    {
      $_COOKIE['auth_token'] = (string)new Auth\JWT(['userid' => '7', 'access' => ['public', 'admin'], 'provider' => 'github'], 600);
      $_COOKIE['auth_provider'] = 'github';
    }
    match ($Case)
    {
      'popup' => Config::Init(Access: '/framework/admin/users.php', Shown: BS\Dismiss::Modal),
      'panel' => Config::Init(Access: '/framework/admin/users.php', Shown: BS\Dismiss::OffCanvas),
      'refused' => Config::Init(Access: '/app/nobody.php', Shown: BS\Dismiss::Toast),
      'anonymous' => Config::Init(Anonymous: true),
      default => Config::Init(Access: Config::ANY_USER),
    };
    Page::AddContent(new BS\AlertInfo('Report', 'The report'));
    return;
  }

  $Views = tempnam(sys_get_temp_dir(), 'sc-webframe-views-');
  function Run(string $Case): array
  {
    global $Views;
    file_put_contents($Views, '');
    $Output = (string)shell_exec('SC_WEBFRAME_TEST_VIEWS=' . escapeshellarg($Views) . ' ' . escapeshellarg(PHP_BINARY)
      . ' -d error_reporting=-1 -d display_errors=1 ' . escapeshellarg(__FILE__) . ' ' . escapeshellarg($Case) . ' 2>&1');
    $Lines = array_values(array_filter(explode("\n", (string)file_get_contents($Views))));
    return [$Output, array_map(fn($Line) => json_decode($Line, true), $Lines)];
  }

  [$Output, $Seen] = Run('page');
  Check(str_contains($Output, 'The report'), 'the page is shown');
  Same([['user' => 7, 'page' => '/app/report.php', 'shown' => 'page', 'request' => '/app/report.php?part=totals', 'after_page' => true]], $Seen,
    'a page any signed-in user may open: the page itself, after it went out');

  [, $Seen] = Run('popup');
  Same('/framework/admin/users.php', $Seen[0]['page'] ?? null, 'a popup counts as the menu page whose access applied');
  Same('popup', $Seen[0]['shown'] ?? null, 'shown as a popup');

  [, $Seen] = Run('panel');
  Same('panel', $Seen[0]['shown'] ?? null, 'an off-canvas panel');

  [$Output, $Seen] = Run('refused');
  Check(str_contains($Output, 'No access'), 'a page the user may not open is refused');
  Same([], $Seen, 'and not counted');

  [, $Seen] = Run('anonymous');
  Same([], $Seen, 'an anonymous page isn\'t counted');

  [, $Seen] = Run('off');
  Same([], $Seen, 'nor anything with usage off');

  [$Output, $Seen] = Run('plain');
  Check(str_contains($Output, 'The report') && !preg_match('/Warning|Fatal|Uncaught/', $Output), 'a storage without PageViews: the page as ever');

  [$Output, $Seen] = Run('failing');
  Same(1, count($Seen), 'a storage that throws is asked');
  Check(str_contains($Output, 'The report') && !preg_match('/Warning|Fatal|Uncaught|agent is down/', $Output), 'and the page doesn\'t show it');

  unlink($Views);
