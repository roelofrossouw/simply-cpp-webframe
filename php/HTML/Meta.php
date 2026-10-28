<?php

    namespace HTML;

    /**
     * <meta> Defines metadata about an HTML document
     *
     * @property string $Charset character set
     * @property string $Content text
     * @property string $HttpEquiv content-security-policy|content-type|default-style|refresh
     * @property string $Name application-name|author|description|viewport|(other)
     * @see https://www.w3schools.com/tags/tag_meta.asp
     */
    class Meta extends Element
    {
        public function __construct(?string $Name = null, ?string $Content = null)
        {
            parent::__construct('meta');
            if (!is_null($Name))
            {
                $this->addAttribute('name', $Name);
            }
            if (!is_null($Content))
            {
                $this->addAttribute('content', $Content);
            }
        }
    }