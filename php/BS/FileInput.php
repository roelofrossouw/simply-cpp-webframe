<?php

    namespace BS;

    /**
     * A file field with a drop zone: drop files on it or click it to choose them; the files chosen are listed under it
     * (name and size, a thumbnail for pictures with Preview()), each with a button to take it off again. js/app.js does
     * the drop zone and the list on a plain <input type="file">, which is what the form posts: $_FILES['name'], or with
     * Multiple() $_FILES['name'] with arrays for each key (name[]). A file bigger than MaxSize(), or not of the types
     * Accept() names, makes the field invalid, so the form isn't posted.
     *
     *   $Scan = new BS\FileInput('scan', 'Signed agreement');
     *   $Scan->Accept('.pdf,image/*')->MaxSize(10 * 1024 * 1024)->Required();
     *   $Photos = new BS\FileInput('photos', 'Photos');
     *   $Photos->Multiple()->Accept('image/*')->Preview();
     *
     * The page the form posts to reads them as any upload: is_uploaded_file(), move_uploaded_file().
     */
    class FileInput extends \HTML\ClassDiv
    {
        use FieldHelp;

        private \HTML\Input $InputElement;
        private \HTML\Div $Zone;
        private string $Name;

        public function __construct(string $Name, string $Label, string $Prompt = 'Drop files here or click to choose')
        {
            parent::__construct('file-input');
            $this->Name = $Name;
            $this->InputElement = new \HTML\Input($Name, \HTML\InputType::File);
            $ID = $this->InputElement->GetAttribute('id');
            $this->AddChild((new \HTML\Label($Label, $ID))->AddClass('form-label'));
            $this->AddChild($this->Zone = new \HTML\ClassDiv('file-drop'));
            $this->Zone->AddChild($this->InputElement);
            $this->InputElement->AddClass('form-control');
            $this->InputElement->AddAttribute('data-bs-toggle', 'file-input');
            $this->Zone->AddChild($Text = new \HTML\ClassDiv('file-drop-text'));
            $Text->AddChild(Icon::CloudArrowUp->Element());
            $Text->AddChild(new \HTML\Span($Prompt));
            $this->AddChild((new \HTML\UnorderedList())->AddClasses(['file-list', 'list-unstyled']));
        }

        /**
         * Several files: posted as name[], $_FILES['name']['name'][0] ...
         */
        public function Multiple(): static
        {
            $this->InputElement->AddAttribute('name', $this->Name . '[]');
            $this->InputElement->AddAttribute('multiple');
            return $this;
        }

        /**
         * The kinds of file that can be chosen, as the accept attribute takes them: '.pdf,.docx', 'image/*'
         */
        public function Accept(string $Types): static
        {
            $this->InputElement->AddAttribute('accept', $Types);
            return $this;
        }

        /**
         * The biggest a file may be, in bytes (PHP's upload_max_filesize and post_max_size still apply)
         */
        public function MaxSize(int $Bytes): static
        {
            $this->InputElement->AddAttribute('data-bs-max-size', $Bytes);
            return $this;
        }

        /**
         * A thumbnail of each picture in the list
         */
        public function Preview(): static
        {
            $this->InputElement->AddAttribute('data-bs-preview', 'true');
            return $this;
        }

        public function InputElement(): \HTML\Input
        {
            return $this->InputElement;
        }

        /**
         * A file can't be put back into a field: what was posted is the page's to show
         */
        public function ShowPostedValue(): void
        {
        }

        protected function Field(): \HTML\Element
        {
            return $this->InputElement;
        }

        protected function MessageParent(): \HTML\Element
        {
            return $this->Zone;
        }
    }
