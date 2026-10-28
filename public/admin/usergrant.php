<?php
  require_once 'sc-webframe/autoload.php'; // On the include_path, see the README

  // Grants or revokes one access level, posted by the switches on users.php (userid, code, val)
  Config::Init(Access: Config::FrameworkURL('admin/users.php'), Shown: \BS\Dismiss::Toast, Edit: true);

  Page::AddContent($Toast = new \BS\Toast('Access'));

  $UserID = filter_var(Post::Get('userid'), FILTER_VALIDATE_INT);
  $Code = (string)Post::Get('code');
  $Granted = Post::Get('val') === 'true';

  if ($UserID === false)
  {
    $Toast->Color(\BS\Color::Danger);
    $Toast->AddChild('Unknown user.');
    return;
  }

  if (!$Granted && in_array($Code, ['admin', 'public'], true) && $UserID == \Auth\Base::ID())
  {
    $Toast->Color(\BS\Color::Warning);
    $Toast->AddChild($Code == 'admin' ? 'You can\'t remove your own admin access.' : 'You can\'t block yourself.');
    return;
  }

  if (!User::SetAccess($UserID, $Code, $Granted))
  {
    $Toast->Color(\BS\Color::Danger);
    $Toast->AddChild('Unknown user or access level.');
    return;
  }

  if ($Code == 'public')
  {
    $Toast->AddChild($Granted ? 'User unblocked, they can sign in again.' : 'User blocked from signing in.');
  }
  else
  {
    $Toast->AddChild(ucfirst($Code) . ' access ' . ($Granted ? 'granted.' : 'revoked.'));
  }
