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

use moodle_recordset;

/**
 * Class snippets
 *
 * @package    theme_boost_union
 * @copyright  2024 University of Graz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class snippets {
    /**
     * Constant for how many a Kilobyte are in bytes.
     *
     * @var int
     */
    const KB_IN_BYTES = 1024;

    /**
     * List of all available Snippet Meta File headers.
     *
     * @var array
     */
    const SNIPPET_HEADERS = [
        'Snippet Title',
        'Goal',
        'Domain',
        'Description',
        'Scope',
    ];

    /**
     * Gets the snippet file based on the meta information.
     * @param mixed $key
     * @param mixed $source
     * @param mixed $domain
     * @return string|null
     */
    public static function get_snippet_file($key, $source, $domain = '') {
        global $CFG;
        if ('theme_boost_union' === $source) {
            $file = $CFG->dirroot . sprintf('/theme/boost_union/snippets/builtin/%s.scss', $key);
        } else {
            return null;
        }
        return is_readable($file) ? $file : null;
    }

    /**
     * TODO
     * @param mixed $key
     * @param mixed $source
     * @param mixed $domain
     * @return bool|string
     */
    public static function get_snippet_css($key, $source, $domain = '') {
        // Get the snippets file, based on the source and domain.s
        $file = self::get_snippet_file($key, $source);

        if (is_null($file)) {
            return;
        }
        $scss = file_get_contents( $file, false, null, 0);

        return $scss;
    }

    /**
     * Get a snippet defined in the code based on key and domain.
     * @param string $key
     * @param string $domain
     * @return mixed
     */
    public static function get_snippet_meta($key, $source) {
        global $CFG;

        // Get the snippets file, based on the source and domain.
        $file = self::get_snippet_file($key, $source);

        if (is_null($file)) {
            return;
        }

        $headers = self::get_snippet_meta_from_file($file);

        if (!array_key_exists('Snippet Title', $headers)) {
            return null;
        }

        $snippet = new \stdClass();
        $snippet->title = $headers['Snippet Title'];
        $snippet->description = $headers['Description'];
        $snippet->scope = $headers['Scope'];
        $snippet->goal = $headers['Goal'];
        $snippet->source = 'theme_boost_union';

        return $snippet;
    }

    /**
     * Compose snippets data.
     * @param mixed $data
     * @return array
     */
    public static function compose_snippets_data($snippetrecordset) {
        $snippets = [];
        foreach ($snippetrecordset as $snippetrecord) {
            $snippet = self::get_snippet_meta($snippetrecord->key, $snippetrecord->source);
            if ($snippet) {
                $snippets[] = (object) array_merge((array) $snippetrecord, (array) $snippet);
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
                FROM {theme_boost_union_snippets}
                WHERE enabled = '1'
                ORDER BY sortorder";

        // Get records.
        $data = $DB->get_recordset_sql($sql);

        $css = '';

        // foreach ($data as $snippet) {
        //     if ($snippet->enabled) {
        //         $css .= self::get_snippet($snippet->key, $snippet->domain)['css'] . ' ';
        //     }
        // }

        return $css;
    }

    /**
     * Strips close comment and close php tags from file headers.
     *
     * @param string $str Header comment to clean up.
     * @return string
     */
    private static function cleanup_header_comment($str) {
        return trim(preg_replace('/\s*(?:\*\/|\?>).*/', '', $str));
    }

    /**
     * Retrieves metadata from a file.
     *
     * Searches for metadata in the first 8 KB of a file, such as a plugin or theme.
     * Each piece of metadata must be on its own line. Fields can not span multiple
     * lines, the value will get cut at the end of the first line.
     *
     * If the file data is not within that first 8 KB, then the author should correct
     * the snippet.
     *
     * @param string $file            Absolute path to the file.
     *
     * @return string[] Array of file header values keyed by header name.
     */
    public static function get_snippet_meta_from_file($file,) {
        // Pull only the first 8 KB of the file in.
        $filedata = file_get_contents( $file, false, null, 0, 8 * self::KB_IN_BYTES );

        if ( false === $filedata ) {
            $filedata = '';
        }

        // Make sure we catch CR-only line endings.
        $filedata = str_replace( "\r", "\n", $filedata );

        $headers = [];

        foreach (self::SNIPPET_HEADERS as $regex) {
            if (preg_match('/^(?:[ \t]*)?[ \t\/*#@]*' . preg_quote($regex, '/') . ':(.*)$/mi', $filedata, $match)
                && $match[1]) {
                $headers[$regex] = self::cleanup_header_comment($match[1]);
            } else {
                $headers[$regex] = '';
            }
        }

        return $headers;
    }

//     /**
//      * Checks if there are any snippets not tracked in the database.
//      *
//      * If they are not they will be added to the database.
//      *
//      * @param moodle_recordset $data The snippets dataset currently present in the DB.
//      *
//      * @return void
//      */
//     public static function check_for_missing_snippets_int_the_database($data) {

//    }

}
