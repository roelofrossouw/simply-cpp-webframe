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
                $this->LogError($Error->getFile(), $Error->getLine(), $Error->getMessage());
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

        private function LogError($File, $Line, $Message): void
        {
            // TODO: Log error in database, and/or send notification to sysadmins
            // e.g. 'insert into php_error (raised, file, line, message) values (now(), $1, $2, $3)', $File, $Line, $Message
        }

        public function HandleError(int $ErrorNumber, string $Message = '', string $File = '', int $Line = 0, array $Context = []): bool
        {
            $ErrorType = ErrorType::FromError($ErrorNumber);
            if (!$ErrorType->Recoverable())
            {
                throw new Exception('Stopping excecution due to ' . $ErrorType->value);
            }

            if (!$ErrorType->IsLogged())
            {
                $this->LogError($File, $Line, $Message);
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