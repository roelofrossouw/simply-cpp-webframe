<?php

    class QueryString
    {
        public static function Get(string $Key)
        {
            return $_GET[$Key] ?? null;
        }

    }