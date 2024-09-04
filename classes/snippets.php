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

namespace theme_boost_union;

/**
 * Class snippets
 *
 * @package    theme_boost_union
 * @copyright  2024 University of Graz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class snippets {
    /**
     * Get a snippet defined in the code based on key and domain.
     * @param string $key
     * @param string $domain
     * @return mixed
     */
    public static function get_snippet($key, $domain = 'theme_boost_union') {
        global $CFG;
        if ('theme_boost_union' == $domain) {
            return require_once($CFG->dirroot . sprintf('/theme/boost_union/snippets/builtin/%s.php', $key));
        }
    }

    /**
     * Compose snippets data.
     * @param mixed $data
     * @return array
     */
    public static function compose_snippets_data($snippetrecordset) {
        $snippets = [];
        foreach ($snippetrecordset as $snippetrecord) {
            if ('code' === $snippetrecord->source) {
                $snippetcontent = self::get_snippet($snippetrecord->key, $snippetrecord->domain);
                $snippetrecord->title = $snippetcontent['title'];
                $snippetrecord->description = $snippetcontent['description'];
                $snippetrecord->css = $snippetcontent['css'];
                $snippetrecord->goal = $snippetcontent['goal'];
                $snippetrecord->scope = $snippetcontent['scope'];
                $snippets[] = $snippetrecord;
            }
        }
        return $snippets;
    }

    /**
     * Checks which snippets are active and returns their css.
     * @return string
     */
    public static function get_enabled_snippet_css() {
        global $DB;

        // Compose SQL base query.
        $sql = "SELECT *
                FROM m_theme_boost_union_snippets
                WHERE enabled = '1'
                ORDER BY sortorder";

        // Get records.
        $data = $DB->get_recordset_sql($sql);

        $css = '';

        foreach ($data as $snippet) {
            if ($snippet->enabled) {
                $css .= self::get_snippet($snippet->key, $snippet->domain)['css'] . ' ';
            }
        }

        $data->close();

        return $css;
    }
}
