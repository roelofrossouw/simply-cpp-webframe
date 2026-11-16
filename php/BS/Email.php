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
     *   redirect_except - Recipients that still get their own email while redirect is set: addresses
     *              ("someone@example.com") and domains ("example.com", the whole part after the @), any case;
     *              a list, or one string with commas (see Recipient())
     *   disabled - When true nothing is sent, sysadmins see a preview instead
     *   Branding, each optional (Brand()); with none set the email is as it always was:
     *   logo          - An https URL of a PNG (mail clients don't show SVG), shown 40 x 40 beside the app's name in a header
     *   header_bg     - The header's background colour
     *   header_color  - The header's text colour
     *   header_border - The header's bottom border, e.g. "3px solid #8a6a2f"
     *   button_bg     - Background of btn-primary buttons and boxed text such as the sign-in code (Box())
     *   button_color  - Their text colour
     *   link_color    - Links' colour (MailFooter()'s too)
     *   Colours are #hex, rgb()/rgba() or a CSS colour name; anything else is ignored. The header shows when logo or
     *   any header_ setting is set.
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

            $Recipient = self::Recipient($To);
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
                . $this->Part($Boundary, 'text/html', $this->Html())
                . "--$Boundary--\r\n";

            // The envelope sender is the From address, so bounces go there (mail() escapes the parameter itself)
            $Envelope = preg_match('/<([^>]+)>/', $From, $Match) ? $Match[1] : $From;
            $Parameters = filter_var($Envelope, FILTER_VALIDATE_EMAIL) ? '-f' . $Envelope : '';
            return mail($Recipient, self::EncodeHeader($Subject), $Body, $Headers, $Parameters);
        }

        /**
         * Where an email to $To goes: $To itself, or mail.redirect when that's set and $To isn't one of
         * mail.redirect_except's addresses or domains. A domain matches only the whole part after the @, so
         * "example.com" takes name@example.com and not name@sub.example.com or name@badexample.com.
         *
         * @param string $To Recipient, e.g. 'Name <name@example.com>' or 'name@example.com'
         */
        public static function Recipient(string $To): string
        {
            $Redirect = \Config::Get('mail.redirect');
            if (!is_string($Redirect) || trim($Redirect) === '')
            {
                return $To;
            }
            $Except = \Config::Get('mail.redirect_except') ?? [];
            if (is_string($Except))
            {
                $Except = explode(',', $Except);
            }
            $Address = strtolower(trim(preg_match('/<([^>]*)>\s*$/', $To, $Match) ? $Match[1] : $To));
            $At = strrpos($Address, '@');
            if ($At === false || !is_array($Except))
            {
                return $Redirect;
            }
            $Domain = substr($Address, $At + 1);
            foreach ($Except as $Entry)
            {
                $Entry = is_string($Entry) ? strtolower(trim($Entry)) : '';
                if ($Entry !== '' && $Entry === (str_contains($Entry, '@') ? $Address : $Domain))
                {
                    return $To;
                }
            }
            return $Redirect;
        }

        /**
         * The HTML Send() sends: ForMail(), then bootstrap-email (unformatted when it isn't there), e.g. for a preview
         */
        public function Html(): string
        {
            $Html = $this->Unformatted();
            return $this->Format($Html) ?? $Html;
        }

        /**
         * What goes to bootstrap-email: ForMail(), then the site's branding (Brand())
         */
        public function Unformatted(): string
        {
            return self::Brand(self::ForMail((string)$this));
        }

        // The class Box() marks an element with, taken out again by Brand()
        private const string BOX = 'mail-box';

        /**
         * Marks an element as boxed text, such as the sign-in code: with mail.button_bg / button_color set it's shown
         * in a box of those colours, else as it is
         */
        public static function Box(\HTML\Element $Element): \HTML\Element
        {
            $Element->AddClass(self::BOX);
            return $Element;
        }

        /**
         * The site's mail branding (the mail section's logo, header_*, button_* and link_color settings) as inline
         * styles, the only styling bootstrap-email keeps: a header with the logo and the app's name after <body>,
         * btn-primary buttons and Box()ed elements in the button colours, and links in link_color. Settings that
         * aren't valid are ignored, so none can add CSS or HTML of its own. With none set only Box()'s marker goes.
         */
        public static function Brand(string $Html): string
        {
            $Logo = self::Https(\Config::Get('mail.logo'));
            $HeaderBg = self::Colour(\Config::Get('mail.header_bg'));
            $HeaderColor = self::Colour(\Config::Get('mail.header_color'));
            $HeaderBorder = self::Border(\Config::Get('mail.header_border'));
            $ButtonBg = self::Colour(\Config::Get('mail.button_bg'));
            $ButtonColor = self::Colour(\Config::Get('mail.button_color'));
            $LinkColor = self::Colour(\Config::Get('mail.link_color'));

            $Html = preg_replace_callback('#<(a|[a-z][a-z0-9]*)\b([^>]*)>#i', function ($Tag) use ($ButtonBg, $ButtonColor, $LinkColor) {
                $Attributes = $Tag[2];
                $Classes = preg_match('/\sclass="([^"]*)"/', $Attributes, $Found) ? preg_split('/\s+/', trim($Found[1])) : [];
                $Boxed = in_array(self::BOX, $Classes, true);
                $Css = '';
                if ($Boxed)
                {
                    $Classes = array_values(array_diff($Classes, [self::BOX]));
                    $Attributes = preg_replace('/\sclass="[^"]*"/', $Classes ? ' class="' . implode(' ', $Classes) . '"' : '', $Attributes, 1);
                    $Css = ($ButtonBg !== null ? "background-color: $ButtonBg; padding: 12px 16px; border-radius: 6px;" : '')
                        . ($ButtonColor !== null ? " color: $ButtonColor;" : '');
                }
                elseif (in_array('btn-primary', $Classes, true))
                {
                    $Css = ($ButtonBg !== null ? "background-color: $ButtonBg; border-color: $ButtonBg;" : '')
                        . ($ButtonColor !== null ? " color: $ButtonColor;" : '');
                }
                elseif (strtolower($Tag[1]) === 'a' && !in_array('btn', $Classes, true) && $LinkColor !== null)
                {
                    $Css = "color: $LinkColor;";
                }
                $Css = trim($Css);
                if ($Css === '')
                {
                    return $Boxed ? '<' . $Tag[1] . $Attributes . '>' : $Tag[0];
                }
                // After any style already there, so the branding wins
                $Attributes = preg_match('/\sstyle="([^"]*)"/', $Attributes, $Style)
                    ? str_replace($Style[0], ' style="' . rtrim(trim($Style[1]), ';') . '; ' . $Css . '"', $Attributes)
                    : $Attributes . ' style="' . $Css . '"';
                return '<' . $Tag[1] . $Attributes . '>';
            }, $Html);

            if ($Logo === null && $HeaderBg === null && $HeaderColor === null && $HeaderBorder === null)
            {
                return $Html;
            }
            $Bar = 'width: 100%;' . ($HeaderBg !== null ? " background-color: $HeaderBg;" : '') . ($HeaderBorder !== null ? " border-bottom: $HeaderBorder;" : '');
            $Header = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="' . $Bar . '"><tr>'
                . ($Logo !== null ? '<td style="width: 40px; padding: 12px 0 12px 16px;"><img src="' . htmlspecialchars($Logo, ENT_QUOTES) . '" width="40" height="40" alt="" style="display: block; width: 40px; height: 40px;"></td>' : '')
                . '<td style="padding: 12px 16px; font-size: 22px; font-weight: bold;' . ($HeaderColor !== null ? " color: $HeaderColor;" : '') . '">'
                . htmlspecialchars(\Config::AppName(), ENT_QUOTES) . '</td></tr></table>';
            return preg_replace('#<body\b[^>]*>#i', '$0' . str_replace(['\\', '$'], ['\\\\', '\\$'], $Header), $Html, 1);
        }

        /**
         * A CSS colour as a setting may give it: #hex (3, 4, 6 or 8 digits), rgb()/rgba() or a colour name; else null
         */
        public static function Colour(mixed $Value): ?string
        {
            if (!is_string($Value))
            {
                return null;
            }
            $Value = trim($Value);
            $Number = '\s*\d{1,3}(?:\.\d+)?%?\s*';
            return preg_match('/^#(?:[0-9a-f]{3,4}|[0-9a-f]{6}|[0-9a-f]{8})$/i', $Value)
                || preg_match("/^rgba?\($Number,$Number,$Number(?:,\s*(?:0|1|0?\.\d+|\d{1,3}%)\s*)?\)$/i", $Value)
                || preg_match('/^[a-z]{3,20}$/i', $Value) ? $Value : null;
        }

        /**
         * A CSS border as a setting may give it: up to a width (px), a style and a colour, e.g. "3px solid #8a6a2f"; else null
         */
        public static function Border(mixed $Value): ?string
        {
            if (!is_string($Value) || trim($Value) === '')
            {
                return null;
            }
            $Parts = preg_split('/\s+/', trim($Value));
            $Width = $Style = $Colour = null;
            foreach ($Parts as $Part)
            {
                if ($Width === null && preg_match('/^(?:0|\d{1,2}(?:\.\d+)?px)$/', $Part))
                {
                    $Width = $Part;
                }
                elseif ($Style === null && preg_match('/^(?:none|solid|dashed|dotted|double|groove|ridge|inset|outset)$/i', $Part))
                {
                    $Style = strtolower($Part);
                }
                elseif ($Colour === null && !str_contains($Part, '(') && ($Found = self::Colour($Part)) !== null && !preg_match('/^(?:none|solid|dashed|dotted|double|groove|ridge|inset|outset)$/i', $Part))
                {
                    $Colour = $Found;
                }
                else
                {
                    return null;
                }
            }
            return implode(' ', array_filter([$Width, $Style, $Colour], fn($Part) => $Part !== null));
        }

        /**
         * An https URL a setting gives (e.g. mail.logo), else null
         */
        public static function Https(mixed $Value): ?string
        {
            if (!is_string($Value) || ($Value = trim($Value)) === '' || filter_var($Value, FILTER_VALIDATE_URL) === false
                || strtolower((string)parse_url($Value, PHP_URL_SCHEME)) !== 'https' || (string)parse_url($Value, PHP_URL_HOST) === ''
                || preg_match('/["\'<>\\\\\s]/', $Value))
            {
                return null;
            }
            return $Value;
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
        // Bootstrap 5.3 classes bootstrap-email (1.8) doesn't know, as the ones it does: its older names, and card parts as
        // padded, shaded card bodies
        private const array MAIL_CLASSES = [
            'text-body-secondary' => ['text-muted'], 'text-body-tertiary' => ['text-muted'],
            'text-end' => ['text-right'], 'text-start' => ['text-left'],
            'card-header' => ['card-body', 'bg-light'], 'card-footer' => ['card-body', 'bg-light'],
        ];
        // And the ones it has no class for, as inline styles
        private const array MAIL_STYLES = ['lead' => 'font-size: 20px; font-weight: 300;'];

        /**
         * The page as mail clients can show it, for bootstrap-email:
         * - Bootstrap Icons are left out: mail clients don't have the font (buttons and alerts keep their text)
         * - text-bg-<colour> becomes badge-<colour> on badges, bg-<colour> elsewhere
         * - other Bootstrap 5.3 classes become the ones bootstrap-email knows (MAIL_CLASSES), or inline styles (MAIL_STYLES)
         */
        public static function ForMail(string $Html): string
        {
            $Html = preg_replace('#<i\b[^>]*\bclass="(?:[^"]*\s)?bi(?:\s[^"]*)?"[^>]*>\s*</i>\s?#', '', $Html);
            return preg_replace_callback('#<([a-z][a-z0-9]*)\b([^>]*?)\sclass="([^"]*)"([^>]*)>#i', function ($Tag) {
                $Classes = preg_split('/\s+/', trim($Tag[3]));
                $Badge = in_array('badge', $Classes, true);
                $Styles = '';
                foreach ($Classes as $Class)
                {
                    if (preg_match('/^text-bg-(\w+)$/', $Class, $Colour))
                    {
                        $Classes[] = ($Badge ? 'badge-' : 'bg-') . $Colour[1];
                    }
                    array_push($Classes, ...(self::MAIL_CLASSES[$Class] ?? []));
                    $Styles .= self::MAIL_STYLES[$Class] ?? '';
                }
                $Rest = $Tag[2] . $Tag[4];
                if ($Styles !== '')
                {
                    $Rest = preg_match('/\sstyle="/', $Rest) ? preg_replace('/\sstyle="/', ' style="' . $Styles . ' ', $Rest, 1) : $Rest . ' style="' . $Styles . '"';
                }
                return '<' . $Tag[1] . ' class="' . implode(' ', array_unique($Classes)) . '"' . $Rest . '>';
            }, $Html);
        }

        private function Format(string $Html): ?string
        {
            // sc-phplib's port (SC\BootstrapEmail, sc-phplib 1.14.0 or later) does it in-process, with the same output
            if (class_exists('SC\BootstrapEmail'))
            {
                try
                {
                    return \SC\BootstrapEmail::format($Html);
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
            fwrite($Pipes[0], $Html);
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
