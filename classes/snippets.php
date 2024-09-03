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
     * Definition of builtin snippets.
     * @var array
     */
    const SNIPPETS = [
        'fix_border' => [
            'domain'      => 'theme_boost_union',
            'title'       => 'Fix Borders',
            'description' => 'Those borders are annoying',
            'scope'       => 'global',
            'goal'        => 'bug fix',
            'css'         => '.border {radius: 4px;}',
        ],
        'fix_font_color' => [
            'domain'      => 'theme_boost_union',
            'title'       => 'Fix font color',
            'scope'       => 'login',
            'goal'        => 'eye candy',
            'description' => 'Those borders are annoying',
            'css'         => 'body {color: 4px;}',
        ],
        'bigger_title' => [
            'domain'      => 'theme_boost_union',
            'title'       => 'Bigger title',
            'scope'       => 'course',
            'description' => 'Make the course titles finally big enough!',
            'goal'        => 'eye candy',
            'css'         => 'h1 {font-size: 70px;}',
        ],
    ];

    /**
     * Compose snippets data.
     * @param mixed $snippets
     * @return void
     */
    public static function compose_snippets_data($snippets) {
        foreach ($snippets as $row => $snippet) {
            if ('code' === $snippet->source) {
                $snippet->title = self::SNIPPETS[$snippet->key]['title'];
                $snippet->description = self::SNIPPETS[$snippet->key]['description'];
                $snippet->goal = self::SNIPPETS[$snippet->key]['goal'];
                $snippet->scope = self::SNIPPETS[$snippet->key]['scope'];
            } else {
                unset($snippets[$row]);
            }
        }
        return $snippets;
    }
}
