<?php

    namespace HTML;

    /**
     * <form> Defines an HTML form for user input
     *
     * @property string $AcceptCharset character set
     * @property string $Action url
     * @property string $AutoComplete on|off
     * @property string $Enctype application/x-www-form-urlencoded|multipart/form-data|text/plain
     * @property string $Method dialog|get|post
     * @property string $Name text
     * @property bool $NoValidate
     * @property string $Rel external|help|license|next|nofollow|noopener|noreferrer|opener|prev|search
     * @property string $Target _blank|_self|_parent|_top|(other)
     * @see https://www.w3schools.com/tags/tag_form.asp
     */
    class Form extends Element
    {
        public function __construct()
        {
            parent::__construct('form');
            $this->AddAttribute('method', 'dialog');
        }
    }