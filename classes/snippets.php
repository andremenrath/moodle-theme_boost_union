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
        if ('theme_boost_union' == $domain) {
            return require_once(__DIR__ . sprintf('/theme/boost_union/snippets/builtin/%s.php', $key));
        }
    }

    /**
     * Compose snippets data.
     * @param mixed $snippets
     * @return void
     */
    public static function compose_snippets_data($snippets) {
        foreach ($snippets as $row => $meta) {
            if ('code' === $meta->source) {
                $snippet = self::get_snippet($meta->key, $meta->domain);
                $snippets[$row] = array_merge($meta, $snippet);
            } else {
                unset($snippets[$row]);
            }
        }
        return $snippets;
    }
}
