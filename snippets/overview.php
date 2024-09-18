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
 * Theme Boost Union - CSS Snippets overview page
 *
 * @package    theme_boost_union
 * @copyright  2024 Alexander Bias, lern.link GmbH <alexander.bias@lernlink.de>
 * @copyright  2024 André Menrath, University of Graz <andre.menrath@uni-graz.at>
 * @copyright  2024 Bart den Hoed, Avetica <b.denhoed@avetica.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Require config.
require(__DIR__.'/../../../config.php');

// Require plugin libraries.
require_once($CFG->dirroot.'/theme/boost_union/lib.php');
require_once($CFG->dirroot.'/theme/boost_union/locallib.php');

// Require admin library.
require_once($CFG->libdir.'/adminlib.php');

// Get parameters.
$action = optional_param('action', null, PARAM_TEXT);
$snippetid = optional_param('id', null, PARAM_INT);

// Get system context.
$context = context_system::instance();

// Access checks.
admin_externalpage_setup('theme_boost_union_snippets_overview');

// Prepare the page (to make sure that all necessary information is already set even if we just handle the actions as a start).
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/theme/boost_union/snippets/overview.php'));
$PAGE->set_cacheable(false);

// Process actions.
if ($action !== null && confirm_sesskey()) {
    // Every action is based on a snippet, thus the snippet ID param has to exist.
    $snippetid = required_param('id', PARAM_INT);

    // The actions might be done with more than one DB statements which should have a monolithic effect, so we use a transaction.
    $transaction = $DB->start_delegated_transaction();

    // Perform the requested action.
    switch ($action) {
        case 'up':
            // Move the snippet upwards.
            $currentsnippet = $DB->get_record('theme_boost_union_snippets', ['id' => $snippetid]);
            $prevsnippet = $DB->get_record('theme_boost_union_snippets', ['sortorder' => $currentsnippet->sortorder - 1]);
            if ($prevsnippet) {
                $DB->set_field('theme_boost_union_snippets', 'sortorder', $prevsnippet->sortorder,
                        ['id' => $currentsnippet->id]);
                $DB->set_field('theme_boost_union_snippets', 'sortorder', $currentsnippet->sortorder,
                        ['id' => $prevsnippet->id]);

                // Purge the theme cache (as the order has changed and the SCSS has to be re-compiled).
                theme_reset_all_caches();
            }
            break;
        case 'down':
            // Move the snippet downwards.
            $currentsnippet = $DB->get_record('theme_boost_union_snippets', ['id' => $snippetid]);
            $nextsnippet = $DB->get_record('theme_boost_union_snippets', ['sortorder' => $currentsnippet->sortorder + 1]);
            if ($nextsnippet) {
                $DB->set_field('theme_boost_union_snippets', 'sortorder', $nextsnippet->sortorder,
                        ['id' => $currentsnippet->id]);
                $DB->set_field('theme_boost_union_snippets', 'sortorder', $currentsnippet->sortorder,
                        ['id' => $nextsnippet->id]);

                // Purge the theme cache (as the order has changed and the SCSS has to be re-compiled).
                theme_reset_all_caches();
            }
            break;
        case 'disable':
            // Get the snippet record and disable it in the database.
            $currentsnippet = $DB->get_record('theme_boost_union_snippets', ['id' => $snippetid]);
            $currentsnippet->enabled = false;
            $DB->update_record('theme_boost_union_snippets', $currentsnippet);

            // Purge the theme cache (as the list of CSS snippets has changed and the SCSS has to be re-compiled).
            theme_reset_all_caches();

            break;
        case 'enable':
            // Get the snippet record and enable it in the database.
            $currentsnippet = $DB->get_record('theme_boost_union_snippets', ['id' => $snippetid]);
            $currentsnippet->enabled = true;
            $DB->update_record('theme_boost_union_snippets', $currentsnippet);

            // Purge the theme cache (as the list of CSS snippets has changed and the SCSS has to be re-compiled).
            theme_reset_all_caches();

            break;
    }

    // Allow to update the changes to database.
    $transaction->allow_commit();

    // Redirect to the same page.
    redirect($PAGE->url);
}

// Further prepare the page.
$PAGE->set_title(theme_boost_union_get_externaladminpage_title(get_string('snippetssnippets', 'theme_boost_union')));
$PAGE->set_heading(theme_boost_union_get_externaladminpage_heading());

// Build snippets table.
$table = new \theme_boost_union\table\snippets_overview();
$table->define_baseurl($PAGE->url);

// Start page output.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('configtitlesnippets', 'theme_boost_union'));

// Create and render the tab tree.
$tabtree = new \theme_boost_union\admin_externalpage_tabs();
$tabtree->add_tab('snippetsoverview',
        new \moodle_url('/theme/boost_union/snippets/overview.php'),
        get_string('snippetsoverview', 'theme_boost_union'));
$tabtree->add_tab('snippetssettings',
        new \moodle_url('/admin/settings.php', ['section' => 'theme_boost_union_snippets'], 'theme_boost_union_snippets_settings'),
        get_string('snippetssettings', 'theme_boost_union'));
echo $tabtree->render_tabtree('snippetsoverview');

// Show snippets description.
echo get_string('snippetsoverview_desc', 'theme_boost_union');

// Add experimental warning.
$experimentalnotification = new \core\output\notification(get_string('snippetsexperimental', 'theme_boost_union'),
    \core\output\notification::NOTIFY_WARNING);
$experimentalnotification->set_show_closebutton(false);
echo $OUTPUT->render($experimentalnotification);

// Show the table (which may, if it is empty, fall back to the "There aren't any CSS snippets which can be used." notice).
$table->out(0, true);

// Finish page output.
echo $OUTPUT->footer();
