<?php

    enum ErrorType: string
    {
        case Error = "System Error";
        case Warning = "System Warning";
        case Parse = "Parse Error";
        case Notice = "System Notice";
        case CoreError = "Core Error";
        case CoreWarning = "Core Warning";
        case CompileError = "Compile Error";
        case CompileWarning = "Compile Warning";
        case UserError = "User Error";
        case UserWarning = "User Warning";
        case UserNotice = "User Notice";
        case Strict = "Strict Error";
        case RecoverableError = "Recoverable Error";
        case Deprecated = "Deprecated Warning";
        case UserDeprecated = "User Deprecated Warning";

        public static function FromError(int $Error): self
        {
            return match ($Error)
            {
                E_ERROR => self::Error,
                E_WARNING => self::Warning,
                E_PARSE => self::Parse,
                E_NOTICE => self::Notice,
                E_CORE_ERROR => self::CoreError,
                E_CORE_WARNING => self::CoreWarning,
                E_COMPILE_ERROR => self::CompileError,
                E_COMPILE_WARNING => self::CompileWarning,
                E_USER_ERROR => self::UserError,
                E_USER_WARNING => self::UserWarning,
                E_USER_NOTICE => self::UserNotice,
                E_STRICT => self::Strict,
                E_RECOVERABLE_ERROR => self::RecoverableError,
                E_DEPRECATED => self::Deprecated,
                E_USER_DEPRECATED => self::UserDeprecated,
            };
        }

        /**
         * Check if script has to stop at this type of error.
         *
         * If it is recoverable the script can continue
         *
         * @return bool
         */
        public function Recoverable(): bool
        {
            return in_array($this, [
                ErrorType::UserWarning,
                ErrorType::Warning,
                ErrorType::UserNotice,
                ErrorType::Notice,
            ]);
        }

        /**
         * Check if script has to display the message to the user for this type of error.
         *
         * @return bool
         */
        public function UserVisible(): bool
        {
            return in_array($this, [
                ErrorType::UserNotice,
                ErrorType::Notice,
            ]);
        }

        /**
         * Check if this error type should be logged
         *
         * @return bool
         */
        public function IsLogged()
        {
            return !in_array($this, [
                ErrorType::UserNotice,
                ErrorType::Notice,
            ]);
        }
    }