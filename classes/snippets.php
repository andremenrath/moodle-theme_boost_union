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
 * Theme Boost Union - CSS snippets
 *
 * @package    theme_boost_union
 * @copyright  2024 André Menrath, University of Graz <andre.menrath@uni-graz.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_boost_union;

/**
 * Class snippets
 *
 * @package    theme_boost_union
 * @copyright  2024 André Menrath, University of Graz <andre.menrath@uni-graz.at>
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
        'Description',
        'Scope',
    ];

    /**
     * Base path CSS snippets that are shipped with boost_union.
     * @var string
     */
    const BUILTIN_SNIPPETS_BASE_PATH = '/theme/boost_union/snippets/builtin/';

    /**
     * Gets the snippet file based on the meta information.
     *
     * @param mixed $path
     * @param mixed $source
     *
     * @return string|null
     */
    public static function get_snippet_file($path, $source) {
        global $CFG;
        // Get the snippet file based on the different sources.
        if ('theme_boost_union' === $source) {
            // Builtin CSS SNippets.
            $file = $CFG->dirroot . self::BUILTIN_SNIPPETS_BASE_PATH . $path;
        } else {
            // Other snippet sources.
            return null;
        }
        return is_readable($file) ? $file : null;
    }

    /**
     * Loads the Snippets SCSS content.
     *
     * @param mixed $path
     * @param mixed $source
     *
     * @return boolean|string
     */
    public static function get_snippet_scss($path, $source) {
        // Get the snippets file, based on the source.
        $file = self::get_snippet_file($path, $source);

        if (is_null($file)) {
            return '';
        }

        $scss = file_get_contents( $file, false, null, 0);
        return $scss;
    }

    /**
     * Get a snippet defined in the code based on path.
     *
     * @param string $path
     * @param string $source
     *
     * @return mixed
     */
    public static function get_snippet_meta($path, $source) {
        // Get the snippets file, based on the source.
        $file = self::get_snippet_file($path, $source);

        // If the file does not exist or is not readable, we can not proceed.
        if (is_null($file)) {
            return null;
        }

        // Extract the meta from the SCSS files top level multiline comment in WordPress style.
        $headers = self::get_snippet_meta_from_file($file);

        // The title is the only required meta-key that actually must be set.
        if (!array_key_exists('Snippet Title', $headers)) {
            return null;
        }

        // Create an object containing the information.
        $snippet = new \stdClass();
        $snippet->title = $headers['Snippet Title'];
        $snippet->description = $headers['Description'];
        $snippet->scope = $headers['Scope'];
        $snippet->goal = $headers['Goal'];
        $snippet->source = 'theme_boost_union';

        return $snippet;
    }

    /**
     * Combine snippets meta data from the snippets file with the database record.
     *
     * This is currently used for create the view for the settings table.
     *
     * @param \moodle_recordset $snippetrecordset
     *
     * @return array
     */
    public static function compose_snippets_data($snippetrecordset) {
        $snippets = [];

        foreach ($snippetrecordset as $snippetrecord) {
            // Get the meta information from the SCSS files top multiline comment.
            $snippet = self::get_snippet_meta($snippetrecord->path, $snippetrecord->source);
            // If snippets meta is not found, it will no be added to the returned snippet array.
            if ($snippet) {
                // Merge the two objects.
                $snippets[] = (object) array_merge((array) $snippetrecord, (array) $snippet);
            }
        }

        return $snippets;
    }

    /**
     * Checks which snippets are active and returns their css.
     *
     * @return string
     */
    public static function get_enabled_snippet_scss() {
        global $DB;

        // Compose SQL base query.
        $sql = "SELECT *
                FROM {theme_boost_union_snippets} s
                WHERE enabled = '1'
                ORDER BY sortorder";

        // Get records.
        $data = $DB->get_recordset_sql($sql);

        $scss = '';

        foreach ($data as $snippet) {
            $scss .= self::get_snippet_scss($snippet->path, $snippet->source);
        }

        $data->close();

        return $scss;
    }

    /**
     * Strips close comment and close php tags from file headers.
     *
     * @param string $str Header comment to clean up.
     *
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
     * @copyright forked from https://developer.wordpress.org/reference/functions/get_file_data/
     * @return string[] Array of file header values keyed by header name.
     */
    public static function get_snippet_meta_from_file($file) {
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

    /**
     * Retrieve all builtin CSS snippets via the actual scss files.
     *
     * @return string[]
     */
    private static function get_builtin_snippet_paths() {
        global $CFG;
        // Get an array of all .scss files in the directory.
        $files = glob($CFG->dirroot . self::BUILTIN_SNIPPETS_BASE_PATH . '*.scss');

        // Get the basenames.
        $basenames = array_map(fn($file) => basename($file), $files);

        return $basenames;
    }

    /**
     * Make sure builtin snippets are in the database.
     *
     * @return void
     */
    public static function add_builtin_snippets() {
        global $DB;

        // Get builtin snippets that are present on disk.
        $paths = self::get_builtin_snippet_paths();

        // Get builtin snippets which are known in the database.
        $snippets = $DB->get_records(
            'theme_boost_union_snippets',
            ['source' => 'theme_boost_union'],
            'sortorder DESC',
            'id,path,sortorder'
        );

        // Get the highest sortorder present.
        $sortorder = empty($snippets) ? 0 : intval(reset($snippets)->sortorder) + 1;

        // Prepare an array with all the present builtin snippet paths.
        $presentpaths = array_map(function($snippet) {
            return $snippet->path;
        }, $snippets);

        foreach ($paths as $path) {
            if (!in_array($path, $presentpaths)) {
                $DB->insert_record(
                    'theme_boost_union_snippets',
                    [
                        'path' => $path,
                        'source' => 'theme_boost_union',
                        'sortorder' => $sortorder,
                    ]
                );
                // We add each record with incrementing sortorder.
                $sortorder += 1;
            }
        }
    }
}
