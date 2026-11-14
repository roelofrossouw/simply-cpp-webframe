<?php

  namespace Auth;

  use BS\{AlertError, Card, Icon, Input, PadSize, ScreenSize, Side};
  use Config;
  use Storage\Store;

  /**
   * Sign in with a code emailed to the address: no password at all
   *
   * The person gives their email address and gets a 6-digit code (it works CODE_MINUTES, once); typing it in signs them
   * in, and shows the address works. With auth.email.signup, an address without an account gets a code too, and the
   * right code makes the account (public access); the site asks for the rest on its own pages. A session cookie
   * (auth.email.session_days) renews the sign-in, as the password's does.
   *
   * Wrong codes lock the address for a while (LOCK_AFTER, LOCK_SECONDS), and wrong codes and codes asked for slow down
   * the IP address they come from, as wrong passwords do (Password::ADDRESS_LIMIT). Another code may be asked for after
   * auth.email.resend_seconds.
   *
   * Settings in its section (auth.email, see Config); it needs mail (the mail section, not disabled), or hooks that send:
   *   enabled        - true to offer it (the storage must implement Storage\EmailCodes; Postgres does)
   *   signup         - true to let an address without an account make one with its code (default false: such an
   *                    address gets the same page, but no email)
   *   session_days   - how long a sign-in lasts without a new code (default 30)
   *   code_minutes   - how long a code works (default 10)
   *   resend_seconds - how long before another code may be sent to the address (default 60)
   *   hooks          - a class implementing Auth\EmailCodeHooks: sends the codes itself, and hears when an address is
   *                    first shown to work
   */
  class EmailCode extends Base
  {
    const ?string PROVIDER = 'email';
    const string TITLE = 'Email code';
    const \BS\Icon ICON = \BS\Icon::Envelope;
    const int DIGITS = 6;
    // Wrong codes in a row that lock an address, and for how long
    const int LOCK_AFTER = 5;
    const int LOCK_SECONDS = 15 * 60;
    // Why the last try didn't work, shown above the form
    private ?string $Problem = null;
    // The posted code is checked once per request
    private static bool $Posted = false;

    public static function Configured(): bool
    {
      return Config::Get('auth.email.enabled') === true && Store::Get() instanceof \Storage\EmailCodes;
    }

    /**
     * Whether an address without an account may make one with its code (auth.email.signup)
     */
    public static function CanSignUp(): bool
    {
      return Config::Get('auth.email.signup') === true;
    }

    public static function Minutes(): int
    {
      return max(1, (int)Config::Get('auth.email.code_minutes', 10));
    }

    public static function ResendSeconds(): int
    {
      return max(0, (int)Config::Get('auth.email.resend_seconds', 60));
    }

    private static function SessionDays(): int
    {
      return max(1, (int)Config::Get('auth.email.session_days', 30));
    }

    private static function Storage(): \Storage\EmailCodes
    {
      $Storage = Store::Get();
      if (!$Storage instanceof \Storage\EmailCodes)
      {
        throw new \RuntimeException('The storage doesn\'t keep emailed codes (Storage\EmailCodes)');
      }
      return $Storage;
    }

    /**
     * The site's hooks (auth.email.hooks), or null
     */
    public static function Hooks(): ?EmailCodeHooks
    {
      $Class = (string)Config::Get('auth.email.hooks', '');
      if ($Class === '')
      {
        return null;
      }
      $Hooks = new $Class();
      if (!$Hooks instanceof EmailCodeHooks)
      {
        throw new \RuntimeException("The class $Class in auth.email.hooks isn't an Auth\\EmailCodeHooks");
      }
      return $Hooks;
    }

    /**
     * Seconds before another code may be sent to the address (0: now)
     */
    public static function WaitSeconds(string $Email): int
    {
      $Code = self::Storage()->EmailCode($Email);
      return $Code === null || $Code['sent'] === null ? 0 : max(0, (int)$Code['sent'] + self::ResendSeconds() - time());
    }

    // Whether the address's wrong codes lock it now
    private static function Locked(?array $Code): bool
    {
      return $Code !== null && (int)$Code['failures'] >= self::LOCK_AFTER && (int)$Code['last_failure'] > time() - self::LOCK_SECONDS;
    }

    // Why the IP address may not try now, or null
    private static function Throttled(): ?string
    {
      $Address = (string)($_SERVER['REMOTE_ADDR'] ?? '');
      return Store::Get()->AddressFailures($Address, Password::LOCK_SECONDS) >= Password::ADDRESS_LIMIT
        ? 'Too many tries from here. Please try again in a few minutes.' : null;
    }

    /**
     * Sends the address a new code: through the site's hooks, or the framework's own email. An address without an
     * account gets none unless people may sign up, but the answer is the same, so it doesn't tell which addresses have
     * accounts.
     *
     * @return string|null Why it wasn't sent, as a sentence for the page; null when it was (or seemed to be)
     */
    public static function SendCode(string $Email): ?string
    {
      if (($Email = Password::Address($Email)) === null)
      {
        return 'That email address doesn\'t look right.';
      }
      if (($Problem = self::Throttled()) !== null)
      {
        return $Problem;
      }
      $Storage = self::Storage();
      if (self::Locked($Storage->EmailCode($Email)))
      {
        return 'Too many wrong codes for this address. Please try again in ' . intdiv(self::LOCK_SECONDS, 60) . ' minutes.';
      }
      if (($Wait = self::WaitSeconds($Email)) > 0)
      {
        return "We sent a code a moment ago. You can ask for another in $Wait seconds.";
      }
      // Each code asked for counts against the IP address, as a wrong one does
      Store::Get()->PasswordFailed(null, (string)($_SERVER['REMOTE_ADDR'] ?? ''));
      if ($Storage->EmailCodeUserID($Email) === null && !self::CanSignUp())
      {
        return null;
      }
      $Code = str_pad((string)random_int(0, 10 ** self::DIGITS - 1), self::DIGITS, '0', STR_PAD_LEFT);
      $Storage->SaveEmailCode($Email, password_hash($Code, PASSWORD_DEFAULT), time() + self::Minutes() * 60);
      $Sent = self::Hooks()?->SendCode($Email, $Code, self::Minutes()) ?? self::MailCode($Email, $Code);
      return $Sent ? null : 'Something went wrong while sending the email. Please try again later.';
    }

    /**
     * The framework's email with a code
     */
    public static function MailCode(string $Email, string $Code): bool
    {
      $AppName = Config::AppName();
      $Mail = new \BS\Email();
      $Mail->AddChild($Card = new Card(PadSize::Pad4));
      $Card->AddChild(new \HTML\Header(3, 'Hi,'));
      $Card->AddParagraph("Your code to sign in to $AppName:");
      $Card->AddChild($Shown = new \HTML\Header(1, $Code));
      $Shown->AddClasses(['text-center', 'font-monospace']);
      $Card->AddParagraph('It works for ' . self::Minutes() . ' minutes, once. If you didn\'t ask for it, ignore this email: nobody can sign in without it.');
      $Card->AddChild(Config::MailFooter());
      if (!$Mail->Send($Email, "Your $AppName code: $Code"))
      {
        error_log('sc-webframe: the sign-in code wasn\'t sent');
        return false;
      }
      return true;
    }

    /**
     * Checks a code typed for an address: right, a code works once
     *
     * @return string|null Why it doesn't sign in, as a sentence for the page; null when it's right
     */
    public static function Check(string $Email, string $Code): ?string
    {
      if (($Problem = self::Throttled()) !== null)
      {
        return $Problem;
      }
      $Storage = self::Storage();
      $Email = Password::Address($Email);
      $Kept = $Email === null ? null : $Storage->EmailCode($Email);
      if (self::Locked($Kept))
      {
        return 'Too many wrong codes for this address. Please try again in ' . intdiv(self::LOCK_SECONDS, 60) . ' minutes.';
      }
      $Code = preg_replace('/\D/', '', $Code);
      $Hash = $Kept['hash'] ?? null;
      // No code takes as long as a wrong one
      $Right = password_verify($Code, $Hash ?? password_hash(random_bytes(8), PASSWORD_DEFAULT)) && $Hash !== null && strlen($Code) == self::DIGITS;
      if ($Right && (int)$Kept['expires'] < time())
      {
        return 'That code has expired. Ask for a new one.';
      }
      if (!$Right)
      {
        $Email !== null ? $Storage->EmailCodeFailed($Email, (string)($_SERVER['REMOTE_ADDR'] ?? ''))
          : Store::Get()->PasswordFailed(null, (string)($_SERVER['REMOTE_ADDR'] ?? ''));
        return 'That code isn\'t right. Check the email and try again.';
      }
      $Storage->EmailCodeUsed($Email);
      return null;
    }

    /**
     * A token for the session cookie: the account, signed with our own key
     */
    public static function SessionToken(int $UserID): JWT
    {
      return new JWT(['purpose' => 'email_session', 'uid' => $UserID], 86400 * self::SessionDays());
    }

    /**
     * The account a SessionToken() names, while it's signed by us and not expired
     */
    public static function SessionUser(string $Token): ?int
    {
      $Token = JWT::FromToken($Token);
      if ($Token === null || $Token->Expired() || $Token->Get('iss') !== Config::Origin() || $Token->Get('purpose') !== 'email_session')
      {
        return null;
      }
      return (int)$Token->Get('uid') ?: null;
    }

    protected function DoAccessToken(): JWT
    {
      // The code, posted back with its state
      if (str_starts_with((string)\Post::Get('state'), self::PROVIDER . '.') && \Post::Get('code') !== null && !self::$Posted)
      {
        self::$Posted = true;
        if (!$this->CheckState())
        {
          $this->Problem = 'The sign-in form expired, please try again.';
          return parent::DoAccessToken();
        }
        $Email = Password::Address((string)\Post::Get('email'));
        $this->Problem = self::Check((string)$Email, (string)\Post::Get('code'));
        if ($this->Problem === null && $Email !== null)
        {
          return $this->Proven($Email);
        }
        sleep(1);
        return parent::DoAccessToken();
      }

      // Renew the sign-in from the session cookie
      if (!empty($Session = $_COOKIE['email_session'] ?? null))
      {
        $UserID = self::SessionUser($Session);
        $Profile = $UserID === null ? null : Store::Get()->Profile($UserID);
        if ($Profile !== null && $Profile['provider'] === self::PROVIDER)
        {
          return $this->CreateAuthToken(['sub' => $Profile['email'], 'email' => $Profile['email']]);
        }
        $this->ClearCookie('email_session');
      }
      return parent::DoAccessToken();
    }

    // A right code: sign in (making the account when there's none), the session cookie, and the address is shown to work
    private function Proven(string $Email): JWT
    {
      $New = self::Storage()->EmailCodeUserID($Email) === null;
      $Token = $this->CreateAuthToken(['sub' => $Email, 'email' => $Email]);
      $UserID = (int)$Token->Get('userid');
      if ($Token->Expired() || $UserID === 0)
      {
        return $Token;
      }
      $Session = self::SessionToken($UserID);
      $this->SetCookie('email_session', $Session, $Session->TTL());
      if (self::Storage()->EmailVerified($UserID))
      {
        self::Hooks()?->Verified($UserID, $Email, $New);
      }
      return $Token;
    }

    protected function DoLogout(bool $Reload = true): void
    {
      $this->ClearCookie('email_session');
      parent::DoLogout($Reload);
    }

    /**
     * The steps: the email address; then the code it was sent (a wrong one asks again). A right code goes on to the site
     * (see DoAccessToken()).
     */
    protected function ShowLogin(): void
    {
      \Page::AddContent($Card = new Card());
      $Card->Format->Center();
      $Card->Format->MaxWidth(ScreenSize::Small->Width());
      $Card->Format->Margin(Side::Top, PadSize::Pad4);
      $Card->AddHeader('Please sign in');
      $Posted = trim((string)\Post::Get('email'));
      $Email = Password::Address($Posted);
      if ($Email === null)
      {
        $this->Problem = $Posted === '' ? null : 'That email address doesn\'t look right.';
        $this->AddEmailStep($Card);
        return;
      }
      if (\Post::Get('code') === null || \Post::Get('resend') !== null)
      {
        // The address, or Send another code: a new code
        $this->Problem = self::SendCode($Email);
        if ($this->Problem !== null && \Post::Get('resend') === null && self::WaitSeconds($Email) == 0)
        {
          $this->AddEmailStep($Card);
          return;
        }
        $this->AddCodeStep($Card, $Email, $this->Problem === null);
        return;
      }
      $this->AddCodeStep($Card, $Email, false);
    }

    /**
     * The first step: the email address, and Next in the foot (with Home on a page of its own)
     */
    public function AddEmailStep(Card $Card): void
    {
      if ($this->Problem !== null)
      {
        $Card->AddChild(new \BS\AlertWarning('Not signed in', $this->Problem));
      }
      $Form = $this->StepForm($Card);
      $Form->AddChild($Email = new Input('email', 'Email', \HTML\InputType::Email));
      $Email->InputElement()->Required = true;
      $Email->InputElement()->AutoComplete = 'username';
      $Email->InputElement()->AutoFocus = true;
      $Email->Value(\Post::Get('email') ?? '');
      $Form->AddChild($Note = new \HTML\Paragraph(self::CanSignUp() ? 'We\'ll email you a code to sign in. No account yet? The code makes one.'
        : 'We\'ll email you a code to sign in.'));
      $Note->AddClasses(['small', 'mt-2', 'mb-0']);
      $Note->Format->Color(\BS\Color::BodySecondary);
      self::AddHome($Card, Password::Submit($Form, 'Next', Icon::ArrowRight));
    }

    // The code: typed into the boxes, Sign in in the foot; Send another code once the wait is over
    private function AddCodeStep(Card $Card, string $Email, bool $JustSent): void
    {
      if ($this->Problem !== null)
      {
        $Card->AddChild(new AlertError('Not signed in', $this->Problem));
      }
      elseif ($JustSent)
      {
        $Card->AddChild(new \BS\AlertInfo('Check your email', "We sent a " . self::DIGITS . "-digit code to $Email. It works for "
          . self::Minutes() . ' minutes; if it isn\'t there, look in your junk mail.', Icon::Envelope));
      }
      $Form = $this->StepForm($Card);
      $Form->AddChild($State = new \HTML\Input('state', \HTML\InputType::Hidden));
      $State->Value($this->NewState());
      $Form->AddChild($Address = new \HTML\Input('email', \HTML\InputType::Hidden));
      $Address->Value($Email);
      $Form->AddChild($Code = new \BS\OtpInput('code', 'Code from the email', self::DIGITS));
      $Code->Groups([3, 3])->Required();
      $Code->InputElement()->AutoFocus = true;

      // Send another code (and Use another address) under it
      $Card->AddChild($Again = new \HTML\Form());
      $Again->Id = uniqid('resend_');
      Password::PostTo($Again, Config::FrameworkURL('login.php') . '?provider=' . self::PROVIDER);
      $Again->AddClasses(['d-flex', 'flex-wrap', 'gap-2', 'mt-3']);
      $Again->AddChild($Resent = new \HTML\Input('email', \HTML\InputType::Hidden));
      $Resent->Value($Email);
      $Again->AddChild($Flag = new \HTML\Input('resend', \HTML\InputType::Hidden));
      $Flag->Value('1');
      $Again->AddChild($Resend = Password::Submit($Again, 'Send another code', Icon::ArrowClockwise));
      $Resend->RemoveClass('btn-primary');
      $Resend->AddClasses(['btn-outline-secondary', 'btn-sm']);
      $Again->AddChild($Other = Password::StepButton('Use another address', Icon::ArrowLeft, Config::FrameworkURL('login.php') . '?provider=' . self::PROVIDER));
      $Other->AddClass('btn-sm');
      if (($Wait = self::WaitSeconds($Email)) > 0)
      {
        // Disabled until the wait is over, counting down: a plain script, as a page of its own loads jQuery last
        $Resend->Disabled = true;
        $Resend->Id = uniqid('resend_button_');
        $Card->AddChild(new \HTML\Script('(function (id, left) {'
          . ' const start = function () { const button = document.getElementById(id); if (!button) return;'
          . ' const label = button.querySelector(".label"); const show = function () { label.textContent = "Send another code in " + left + " s"; }; show();'
          . ' const tick = setInterval(function () { left--; if (left > 0) { show(); return; }'
          . ' clearInterval(tick); label.textContent = "Send another code"; button.disabled = false; }, 1000); };'
          . ' document.readyState === "loading" ? document.addEventListener("DOMContentLoaded", start) : start();'
          . ' })(' . json_encode($Resend->Id) . ', ' . $Wait . ');'));
      }
      self::AddHome($Card, Password::Submit($Form, 'Sign in', Icon::BoxArrowInRight));
    }

    // A step's form, posting to the login page
    private function StepForm(Card $Card): \HTML\Form
    {
      $Card->AddChild($Form = new \HTML\Form());
      $Form->Id = uniqid('signin_');
      Password::PostTo($Form, Config::FrameworkURL('login.php') . '?provider=' . self::PROVIDER);
      return $Form;
    }
  }
