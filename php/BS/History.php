<?php

    namespace BS;

    /**
     * A screen's change history (\History): a small clock button that opens the history popup (since 1.49.0)
     *
     *   $Heading->AddChild(BS\History::Glyph('/app/templates.php', ['servers.template', 'servers.lxc_setting']));
     *   // One row's: the template's own row, and its LXD settings by their parent id
     *   $Cell->AddChild(BS\History::Glyph('/app/templates.php', ['servers.template', 'servers.lxc_setting'],
     *     ['servers.template' => ['id' => 632], 'servers.lxc_setting' => ['template_id' => 632]], 'Template web1'));
     *
     * $Page is the screen's menu target: who may view it sees the history, who may edit it may also undo a change.
     * $Tables are the tables the screen edits ('schema.table'). $Match limits it to the rows whose key (or parent ids,
     * audit.change's about) contain the object: one object for every table, or [table => object]; a table left out of a
     * per-table match shows all its rows. The popup shows only what the glyph named: it travels signed (\History::Token).
     */
    class History
    {
        public static function Glyph(string $Page, array $Tables, array $Match = [], string $Title = ''): Button
        {
            $Button = new Button(null, Icon::ClockHistory, \Config::FrameworkURL('history.php') . '?'
                . http_build_query(['t' => \History::Token($Page, $Tables, $Match, $Title)]));
            $Button->Modal();
            $Button->SkipForm();
            $Button->AddClasses(['btn-link', 'btn-sm', 'p-0', 'lh-1', 'text-body-secondary']);
            $Label = $Title === '' ? 'History' : 'History of ' . $Title;
            $Button->AddAttribute('title', $Label);
            $Button->AddAttribute('aria-label', $Label);
            return $Button;
        }
    }
