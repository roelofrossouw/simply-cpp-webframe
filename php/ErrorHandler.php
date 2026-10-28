<?php

    class ErrorHandler
    {

        /**
         * Create an Error Handler object
         *
         * Catch all errors and handle with this class
         *
         * @see https://www.php.net/manual/en/function.set-exception-handler.php
         */
        public function __construct()
        {
            /**
             * Don't show an error for setting up the error handler
             *
             * @see https://www.php.net/manual/en/language.operators.errorcontrol.php
             */
            set_exception_handler([$this, 'HandleException']);
            set_error_handler([$this, 'HandleError']);
        }

        /**
         * Error handler.
         *
         * Show general message for normal users, and more detailed error details for system admins
         *
         * @param Throwable $Error
         * @return void
         */
        public function HandleException(Throwable $Error): void
        {
            // Always logged, also when an admin sees it in the page
            $this->LogError($Error->getFile(), $Error->getLine(), get_class($Error) . ': ' . $Error->getMessage(), $Error->getTraceAsString());
            if (\Auth\Base::SystemAdmin())
            {
                $ErrorMessage = new \HTML\Div();
                $ErrorMessage->AddHeader($Error->getMessage(), 4);
                $ErrorMessage->AddParagraph($Error->getFile() . ' (Line ' . $Error->getLine() . ')');
                $ErrorMessage->AddHeader('Stack trace:', 4);
                $ErrorMessage->AddParagraph($Error->getTraceAsString());
            }
            else
            {
                $ErrorMessage = new \HTML\Paragraph('An unexpected error has occurred.');
                $ErrorMessage->Lead();
                $ErrorMessage->AddSibling($Description = new \HTML\Paragraph('Our team has been notified and is looking into the issue.'));
                $Description->AddLine('Please try performing the action again later.');
                $Description->AddLine('We apologize for the inconvenience and appreciate your patience.');
            }
            $ErrorDisplay = new \BS\AlertError('Something Went Wrong', $ErrorMessage);
            $ErrorDisplay->Color(\BS\Color::Danger);
            \Page::AddContent($ErrorDisplay);
        }

        /**
         * Into PHP's error log (PHP-FPM's log, or the error_log setting), with the page and the trace when there is one
         */
        private function LogError($File, $Line, $Message, string $Trace = ''): void
        {
            $Page = $_SERVER['REQUEST_URI'] ?? ($_SERVER['PHP_SELF'] ?? '');
            error_log("sc-webframe: $Message in $File($Line)" . ($Page === '' ? '' : " [$Page]") . ($Trace === '' ? '' : "\n$Trace"));
        }

        public function HandleError(int $ErrorNumber, string $Message = '', string $File = '', int $Line = 0, array $Context = []): bool
        {
            $ErrorType = ErrorType::FromError($ErrorNumber);
            if (!$ErrorType->Recoverable())
            {
                throw new Exception('Stopping excecution due to ' . $ErrorType->value);
            }

            // Warnings and deprecations go to the log; notices don't
            if ($ErrorType->IsLogged())
            {
                $this->LogError($File, $Line, $ErrorType->value . ': ' . $Message);
            }

            // Display applicable notices
            if ($ErrorType->UserVisible())
            {
                \Page::AddContent(new \BS\AlertInfo('Please note', $Message));
            }
            elseif (\Auth\Base::SystemAdmin()) // Sysadmins sees warnings
            {
                \Page::AddContent(new \BS\AlertWarning('Please note', "$Message in $File ($Line)"));
            }

            // Mark as handled, don't invoke PHP's internal error handler as well.
            return true;
        }

    }