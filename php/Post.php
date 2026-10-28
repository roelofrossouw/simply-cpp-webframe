<?php

    class Post
    {
        public static function Get(string $Key)
        {
            return $_POST[$Key] ?? null;
        }

    }