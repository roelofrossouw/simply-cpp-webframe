<?php

    namespace BS;

    /**
     * An HTML email, built with the same HTML and BS classes as pages
     *
     *   $Email = new \BS\Email();
     *   $Email->AddChild($Card = new \BS\Card());
     *   ...
     *   $Email->Send('Name <name@example.com>', 'Subject');
     *
     * Mail clients ignore stylesheets, so before sending the HTML goes through bootstrap-email, which turns the
     * Bootstrap classes into tables and inline styles: sc-phplib's SC\BootstrapEmail when the sc extension has it, else
     * the bootstrap-email program (simply-cpp-bootstrap-email's, or the gem's). The message has a plain text
     * version too, and is handed to the server's sendmail.
     *
     * Settings in the settings' mail section (see Config):
     *   from     - Sender, default "<app name> <noreply@<this site's host>>"
     *   redirect - When set, every email goes to this address instead (for development servers)
     *   disabled - When true nothing is sent, sysadmins see a preview instead
     *
     * @see https://bootstrapemail.com
     */
    class Email extends \HTML\Element
    {
        private Container $Container;

        public function __construct(string $Lang = 'en')
        {
            parent::__construct('html');
            $this->AddAttribute('lang', $Lang);
            parent::AddChild($Head = new \HTML\Head());
            $Head->AddChild($Meta = new \HTML\Meta());
            $Meta->AddAttribute('http-equiv', 'Content-Type');
            $Meta->AddAttribute('content', 'text/html; charset=utf-8');
            $Head->AddChild($CustomStyles = new \HTML\Style());
            $CustomStyles->AddChild('p {margin-top: 9px;}');
            parent::AddChild($Body = new \HTML\Body());
            $Body->Format->Background(Color::Light);
            $Body->AddChild($this->Container = new Container());
            $this->Container->Format->Margin(Side::Vertical, PadSize::Pad4);
        }

        public function AddChild(\HTML\Element|string $Child): \HTML\Element
        {
            return $this->Container->AddChild($Child);
        }

        public function __toString(): string
        {
            return '<!doctype html>' . PHP_EOL . parent::__toString();
        }

        /**
         * @param string $To      Recipient, e.g. 'Name <name@example.com>'
         * @param string $Subject Plain text, may contain any characters
         * @return bool Whether the mail system accepted the email for delivery
         */
        public function Send(string $To, string $Subject): bool
        {
            if (\Config::Get('mail.disabled'))
            {
                $this->ShowPreview($To, $Subject);
                return false;
            }

            $Recipient = \Config::Get('mail.redirect') ?: $To;
            $From = \Config::Get('mail.from') ?: \Config::AppName() . ' <noreply@' . \Config::Host() . '>';
            $Boundary = 'part_' . bin2hex(random_bytes(12));
            $Headers = [
                'MIME-Version' => '1.0',
                'Content-Type' => "multipart/alternative; boundary=\"$Boundary\"",
                'From' => $From,
            ];
            if ($Recipient !== $To)
            {
                $Headers['X-Original-To'] = $To;
            }
            $Body = $this->Part($Boundary, 'text/plain', $this->PlainText())
                . $this->Part($Boundary, 'text/html', $this->Format() ?? (string)$this)
                . "--$Boundary--\r\n";

            // The envelope sender is the From address, so bounces go there (mail() escapes the parameter itself)
            $Envelope = preg_match('/<([^>]+)>/', $From, $Match) ? $Match[1] : $From;
            $Parameters = filter_var($Envelope, FILTER_VALIDATE_EMAIL) ? '-f' . $Envelope : '';
            return mail($Recipient, self::EncodeHeader($Subject), $Body, $Headers, $Parameters);
        }

        /**
         * Headers may only hold ASCII, other text is sent as an RFC 2047 encoded word
         */
        private static function EncodeHeader(string $Text): string
        {
            return preg_match('/[^\x20-\x7e]/', $Text) ? '=?UTF-8?B?' . base64_encode($Text) . '?=' : $Text;
        }

        private function Part(string $Boundary, string $Type, string $Content): string
        {
            return "--$Boundary\r\n"
                . "Content-Type: $Type; charset=UTF-8\r\n"
                . "Content-Transfer-Encoding: quoted-printable\r\n\r\n"
                . quoted_printable_encode($Content) . "\r\n";
        }

        /**
         * Email-safe HTML from bootstrap-email, or null when it isn't available or fails
         */
        private function Format(): ?string
        {
            // sc-phplib's port (SC\BootstrapEmail, sc-phplib 1.14.0 or later) does it in-process, with the same output
            if (class_exists('SC\BootstrapEmail'))
            {
                try
                {
                    return \SC\BootstrapEmail::format((string)$this);
                }
                catch (\Throwable $Error)
                {
                    trigger_error('SC\BootstrapEmail failed, trying the bootstrap-email program: ' . $Error->getMessage(), E_USER_NOTICE);
                }
            }
            // PHP-FPM clears the environment, so bootstrap-email is looked for in the usual places too (gem installs it in
            // /usr/local/bin), and gets a PATH that finds the ruby it runs with. Quiet when it isn't installed: the email
            // goes out unformatted.
            $Path = array_unique(array_filter([...explode(':', (string)getenv('PATH')), '/usr/local/bin', '/usr/bin', '/bin']));
            $Command = current(array_filter(array_map(fn($Folder) => "$Folder/bootstrap-email", $Path), 'is_executable'));
            if ($Command === false)
            {
                return null;
            }
            $Process = proc_open([$Command], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $Pipes, sys_get_temp_dir(),
                ['PATH' => implode(':', $Path), 'HOME' => sys_get_temp_dir()]);
            if (!is_resource($Process))
            {
                return null;
            }
            fwrite($Pipes[0], (string)$this);
            fclose($Pipes[0]);
            $Result = stream_get_contents($Pipes[1]);
            $Errors = stream_get_contents($Pipes[2]);
            fclose($Pipes[1]);
            fclose($Pipes[2]);
            if (proc_close($Process) !== 0 || empty($Result))
            {
                trigger_error('bootstrap-email failed, sending the email unformatted: ' . trim($Errors), E_USER_NOTICE);
                return null;
            }
            return $Result;
        }

        /**
         * The text of the email for mail clients that don't show HTML, links written out after their text
         */
        public function PlainText(): string
        {
            $Html = (string)$this->Container;
            $Html = preg_replace('#<(script|style)\b.*?</\1>#is', '', $Html);
            $Html = preg_replace('#<a\b[^>]*href="([^"]*)"[^>]*>(.*?)</a>#is', '$2 ($1)', $Html);
            $Html = preg_replace('#<br\s*/?>|</(p|div|h[1-6]|li|tr)>#i', "\n", $Html);
            $Text = html_entity_decode(strip_tags($Html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $Lines = array_map('trim', explode("\n", $Text));
            return trim(preg_replace("/\n{3,}/", "\n\n", implode("\n", $Lines)));
        }

        private function ShowPreview(string $To, string $Subject): void
        {
            \Page::AddContent($Alert = new AlertWarning('Mail disabled', $Message = new \HTML\Paragraph('')));
            $Message->AddChild('The email was not sent because sending email is disabled in the settings (mail.disabled).');
            if (\Auth\Base::SystemAdmin())
            {
                $Message->AddSibling($Paragraph = new \HTML\Paragraph('Email preview'));
                $Paragraph->AddLine('To: ' . $To);
                $Paragraph->AddLine('Subject: ' . $Subject);
                $Message->AddSibling(new \HTML\Raw((string)$this->Container));
            }
        }
    }
