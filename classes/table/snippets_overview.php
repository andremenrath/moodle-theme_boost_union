<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Theme Boost Union - CSS snippets overview table
 *
 * @package    theme_boost_union
 * @copyright  2024 Alexander Bias, lern.link GmbH <alexander.bias@lernlink.de>
 * @copyright  2024 André Menrath, University of Graz <andre.menrath@uni-graz.at>
 * @copyright  2024 Bart den Hoed, Avetica <b.denhoed@avetica.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_boost_union\table;

use theme_boost_union\snippets;

defined('MOODLE_INTERNAL') || die();

// Require table library.
require_once($CFG->libdir.'/tablelib.php');

/**
 * List of CSS snippets.
 *
 * @package    theme_boost_union
 * @copyright  2024 Alexander Bias, lern.link GmbH <alexander.bias@lernlink.de>
 * @copyright  2024 André Menrath, University of Graz <andre.menrath@uni-graz.at>
 * @copyright  2024 Bart den Hoed, Avetica <b.denhoed@avetica.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class snippets_overview extends \table_sql {
    /**
     * @var int $count Snippets count.
     */
    private $count;

    /**
     * @var int $totalsnippets Total snippets count.
     */
    private $totalsnippets;

    /**
     * Setup table.
     *
     * @throws \coding_exception
     */
    public function __construct() {
        global $DB;

        // Call parent constructor.
        parent::__construct('snippets');

        // Define the headers and columns.
        $headers[] = get_string('snippetstitle', 'theme_boost_union');
        $headers[] = get_string('snippetsdescription', 'theme_boost_union');
        $headers[] = get_string('snippetssource', 'theme_boost_union');
        $headers[] = get_string('snippetsscope', 'theme_boost_union');
        $headers[] = get_string('snippetsgoal', 'theme_boost_union');
        $headers[] = get_string('up') .'/'. get_string('down');
        $headers[] = get_string('actions');
        $columns[] = 'title';
        $columns[] = 'description';
        $columns[] = 'source';
        $columns[] = 'scope';
        $columns[] = 'goal';
        $columns[] = 'updown';
        $columns[] = 'actions';
        $this->sortable(false); // Having a sortable table would be nice, but this would interfere with the up/down feature.
        $this->collapsible(false);
        $this->pageable(false); // Having a pageable table would be nice, but we will keep it simple for now.
        $this->define_columns($columns);
        $this->define_headers($headers);
        $this->define_header_column('title');

        // Initialize values for the updown feature.
        $this->count = 0;
        $this->totalsnippets = $DB->count_records('theme_boost_union_snippets');
    }

    /**
     * Updown column.
     *
     * @param \stdClass $data
     * @return mixed
     */
    public function col_updown($data) {
        global $OUTPUT;

        // Prepare action URL.
        $actionurl = new \moodle_url('/theme/boost_union/snippets/overview.php');

        // Initialize column value.
        $updown = '';

        // Get spacer icon.
        $spacer = $OUTPUT->pix_icon('spacer', '', 'moodle', ['class' => 'iconsmall']);

        // If there is more than one snippet and we do not handle the first (number 0) snippet.
        if ($this->count > 0) {
            // Add the up icon.
            $updown .= \html_writer::link($actionurl->out(false,
                    ['action' => 'up', 'id' => $data->id, 'sesskey' => sesskey()]),
                    $OUTPUT->pix_icon('t/up', get_string('up'), 'moodle',
                            ['class' => 'iconsmall']), ['class' => 'sort-snippet-up-action']);

            // Otherwise, just add a spacer.
        } else {
            $updown .= $spacer;
        }

        // If there is more than one snippet and we do not handle the last snippet.
        if ($this->count < ($this->totalsnippets - 1)) {
            // Add the down icon.
            $updown .= '&nbsp;';
            $updown .= \html_writer::link($actionurl->out(false,
                    ['action' => 'down', 'id' => $data->id, 'sesskey' => sesskey()]),
                    $OUTPUT->pix_icon('t/down', get_string('down'), 'moodle',
                            ['class' => 'iconsmall']), ['class' => 'sort-snippet-down-action']);

            // Otherwise, just add a spacer.
        } else {
            $updown .= $spacer;
        }

        // Increase the snippet counter.
        $this->count++;

        // Return the column value.
        return $updown;
    }

    /**
     * Source column.
     *
     * @param \stdClass $data
     * @return mixed
     */
    public function col_source($data) {
        // Get the badge for the given source.
        return $this->pick_and_build_badge('snippetssource'.$data->source);
    }

    /**
     * Scope column.
     *
     * @param \stdClass $data
     * @return mixed
     */
    public function col_scope($data) {
        // Get the badge for the given scope.
        return $this->pick_and_build_badge('snippetsscope'.$data->scope);
    }

    /**
     * Goal column.
     *
     * @param \stdClass $data
     * @return mixed
     */
    public function col_goal($data) {
        // Get the badge for the given goal.
        return $this->pick_and_build_badge('snippetsgoal'.$data->goal);
    }

    /**
     * Helper function which gets a string from the language pack and builds a Bootstrap badge from it.
     *
     * @param string $identifier The string identifier.
     * @return string The HTML for the Bootstrap bade.
     */
    private function pick_and_build_badge($identifier) {
        // Get the string for the given scope from the language pack.
        $string = get_string($identifier, 'theme_boost_union');

        // Output Bootstrap label, if a string was found.
        if (!empty($string)) {
            return \html_writer::tag('span', $string, ['class' => 'badge bg-primary text-light']);
        } else {
            return '';
        }
    }

    /**
     * Actions column.
     *
     * @param \stdClass $data
     * @return string
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public function col_actions($data) {
        global $OUTPUT;

        // Initialize actions.
        $actions = [];

        // Enable/Disable.
        if ($data->enabled == false) {
            $actions[] = [
                'url' => new \moodle_url('/theme/boost_union/snippets/overview.php',
                        ['action' => 'enable', 'id' => $data->id, 'sesskey' => sesskey()]),
                'icon' => new \pix_icon('t/show', get_string('snippetsenable', 'theme_boost_union')),
                'attributes' => ['class' => 'action-enable'],
            ];
        } else {
            $actions[] = [
                'url' => new \moodle_url('/theme/boost_union/snippets/overview.php',
                        ['action' => 'disable', 'id' => $data->id, 'sesskey' => sesskey()]),
                'icon' => new \pix_icon('t/hide', get_string('snippetsdisable', 'theme_boost_union')),
                'attributes' => ['class' => 'action-disable'],
            ];
        }

        // Compose action icons for all actions.
        $actionshtml = [];
        foreach ($actions as $action) {
            $action['attributes']['role'] = 'button';
            $actionshtml[] = $OUTPUT->action_icon(
                $action['url'],
                $action['icon'],
                ($action['confirm'] ?? null),
                $action['attributes']
            );
        }

        // Return all actions.
        return \html_writer::span(join('', $actionshtml), 'snippets-actions');
    }

    /**
     * Get the snippets for the table.
     *
     * @param int $pagesize
     * @param bool $useinitialsbar
     * @throws \dml_exception
     */
    public function query_db($pagesize, $useinitialsbar = true) {
        global $DB;

        // Compose SQL base query.
        $sql = 'SELECT *
                FROM {theme_boost_union_snippets} s
                ORDER BY sortorder';

        // Get the raw records.
        $data = $DB->get_recordset_sql($sql);

        // Compose the complete snippets data and add set it as raw table data.
        $this->rawdata = snippets::compose_snippets_data($data);
    }

    /**
     * Override the message if the table contains no entries.
     */
    public function print_nothing_to_display() {
        global $OUTPUT;

        // Show notification as html element.
        $notification = new \core\output\notification(
                get_string('snippetsnothingtodisplay', 'theme_boost_union'), \core\output\notification::NOTIFY_INFO);
        $notification->set_show_closebutton(false);
        echo $OUTPUT->render($notification);
    }
}