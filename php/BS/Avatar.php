<?php

    namespace BS;

    /**
     * A round profile picture, or the person's initials when there is no photo
     */
    class Avatar extends \HTML\Span
    {
        /**
         * @param string|null $Photo Image URL (or data: URL), null to show initials
         * @param string      $Name  Used for the initials and the image's alt text
         * @param int         $Size  Diameter in pixels
         */
        public function __construct(?string $Photo, string $Name, int $Size = 32)
        {
            parent::__construct();
            $this->AddClasses(['d-inline-flex', 'align-items-center', 'justify-content-center', 'rounded-circle', 'overflow-hidden', 'flex-shrink-0']);
            $this->Format->Width($Size);
            $this->Format->Height($Size);
            if (!empty($Photo))
            {
                $this->AddChild($Image = new \HTML\Image($Photo, $Name));
                $Image->Format->Width($Size);
                $Image->Format->Height($Size);
                $Image->AddStyle('object-fit', 'cover');
            }
            else
            {
                $this->Format->Background(Color::Secondary);
                $this->Format->Color(Color::White);
                $this->AddStyle('font-size', round($Size * 0.4) . 'px');
                $this->AddAttribute('aria-hidden', 'true');
                $this->AddChild(self::Initials($Name));
            }
        }

        private static function Initials(string $Name): string
        {
            $Words = preg_split('/[\s@._-]+/u', trim($Name), -1, PREG_SPLIT_NO_EMPTY);
            $Initials = mb_substr($Words[0] ?? '?', 0, 1) . (count($Words) > 1 ? mb_substr(end($Words), 0, 1) : '');
            return mb_strtoupper($Initials);
        }
    }
