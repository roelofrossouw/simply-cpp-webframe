<?php

    namespace BS;

    /**
     * A list of short items (e.g. package names) to add to and remove from, posted as one value
     *
     * Each item shows as a tag with a remove button; the field below adds one or more items, separated by spaces. The
     * items are posted under $Name, separated by spaces (app.js keeps that hidden value up to date).
     *
     *   new ListEditor('packages', $Packages, 'Add packages', Pattern: '^[a-z0-9][a-z0-9+.-]*$')
     */
    class ListEditor extends \HTML\ClassDiv
    {
        private \HTML\Div $Items;
        private \HTML\Input $New;
        private \HTML\Div $Note;

        /**
         * @param string[]    $Items       The current items
         * @param string|null $Pattern     A JavaScript regular expression each item must match
         * @param string[]    $Suggestions Offered while typing, e.g. items used elsewhere
         */
        public function __construct(string $Name, array $Items, string $AddLabel = 'Add', ?string $Pattern = null, array $Suggestions = [])
        {
            parent::__construct('list-editor');
            if ($Pattern !== null)
            {
                $this->AddAttribute('data-pattern', $Pattern);
            }

            // The value that is posted
            $this->AddChild($Value = new \HTML\Input($Name, \HTML\InputType::Hidden));
            $Value->RemoveAttribute('id');
            $Value->Value = implode(' ', $Items);

            $this->AddChild($this->Items = new \HTML\Div());
            $this->Items->AddClasses(['list-editor-items', 'd-flex', 'flex-wrap']);
            $this->Items->Format->Gap(PadSize::Pad1)->Margin(Side::Bottom, PadSize::Pad2);
            foreach ($Items as $Item)
            {
                $this->Items->AddChild(self::Item($Item));
            }
            // A blank item for app.js to copy when one is added
            $this->AddChild($Template = new \HTML\Template());
            $Template->AddChild(self::Item(''));

            $this->AddChild($Group = new \HTML\ClassDiv('input-group'));
            $Group->AddClass('input-group-sm');
            $Group->AddChild($this->New = new \HTML\Input('', \HTML\InputType::Text));
            $this->New->RemoveAttribute('name'); // Not posted itself
            $this->New->AddClasses(['form-control', 'list-editor-new']);
            $this->New->Placeholder = $AddLabel . ' (separate several with spaces)';
            $this->New->AddAttribute('aria-label', $AddLabel);
            $this->New->AddAttribute('autocomplete', 'off');
            if (!empty($Suggestions))
            {
                $Group->AddChild($List = new \HTML\DataList(ID: uniqid('suggest_')));
                foreach (array_unique($Suggestions) as $Suggestion)
                {
                    $List->AddChild((new \HTML\Option())->AddAttribute('value', $Suggestion));
                }
                $this->New->AddAttribute('list', $List->GetAttribute('id'));
            }
            $Group->AddChild($Add = new \HTML\Button(Icon::Plus->Element()->AddSibling('Add')));
            $Add->AddAttribute('type', 'button');
            $Add->AddClasses(['btn', 'btn-outline-secondary']);
            $Add->AddAttribute('data-list-add', 'true');

            $this->AddChild($this->Note = new \HTML\Div());
            $this->Note->AddClasses(['list-editor-note', 'small']);
            $this->Note->Format->Color(Color::BodySecondary);
            $this->Note->AddAttribute('aria-live', 'polite');
        }

        /**
         * Items that are there already from elsewhere (e.g. inherited): adding one again is refused, with a note
         *
         * @param string[] $Items
         * @param string   $Why   E.g. 'already inherited'
         */
        public function Existing(array $Items, string $Why): static
        {
            $this->AddAttribute('data-existing', json_encode(array_values($Items)));
            $this->AddAttribute('data-existing-why', $Why);
            return $this;
        }

        private static function Item(string $Text): \HTML\Span
        {
            $Item = new \HTML\Span();
            // Body colours, so the tags follow the light or dark theme
            $Item->AddClasses(['list-editor-item', 'badge', 'rounded-pill', 'border', 'd-inline-flex', 'align-items-center', 'bg-body-tertiary', 'text-body']);
            $Item->Format->Font->Weight(FontWeight::Normal);
            $Item->AddChild((new \HTML\Span($Text))->AddClasses(['list-editor-text', 'font-monospace']));
            $Item->AddChild($Remove = new \HTML\Button());
            $Remove->AddAttribute('type', 'button');
            $Remove->AddClasses(['btn-close', 'ms-1']);
            $Remove->AddStyle('font-size', '0.55em');
            $Remove->AddAttribute('aria-label', 'Remove ' . $Text);
            $Remove->AddAttribute('data-list-remove', 'true');
            return $Item;
        }
    }
