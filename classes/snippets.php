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

use stdClass;

/**
 * Library class containing solely static functions for dealing with SCSS snippets.
 *
 * @package    theme_boost_union
 * @copyright  2024 André Menrath, University of Graz <andre.menrath@uni-graz.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class snippets {
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
     *
     * @var string
     */
    const BUILTIN_SNIPPETS_BASE_PATH = '/theme/boost_union/snippets/builtin/';

    /**
     * Gets the snippet file based on the meta information.
     *
     * @param string $path The snippet's path.
     * @param string $source The snippet's source.
     *
     * @return string|null
     */
    public static function get_snippet_file($path, $source): string|null {
        global $CFG;

        // Get the snippet file based on the different sources.
        if ('theme_boost_union' === $source) {
            // Builtin CSS SNippets.
            $file = $CFG->dirroot . self::BUILTIN_SNIPPETS_BASE_PATH . $path;
        } else {
            // Other snippet sources (which are currently not supported).
            return null;
        }
        return is_readable($file) ? $file : null;
    }

    /**
     * Get the SCSS of a snippet based on its path and source.
     *
     * Returns an empty string as a fallback if the snippet is not found.
     *
     * @param string $path The snippet's path.
     * @param string $source The snippet's source.
     *
     * @return string
     */
    public static function get_snippet_scss($path, $source): string {
        // Get the snippets file, based on the source.
        $file = self::get_snippet_file($path, $source);

        // If the file does not exist or is not readable, we simply return an empty string.
        if (is_null($file)) {
            return '';
        }

        // Get and return the whole file content (which will contain the SCSS comments as well).
        $scss = file_get_contents($file);

        // Return the SCSS or an empty string if reading the file has failed.
        return $scss ?: '';
    }

    /**
     * Get the meta data of a snippet defined in the snippet code based on its path and source.
     *
     * @param string $path The snippet's path.
     * @param string $source The snippet's source.
     *
     * @return stdClass|null The snippet metadata object, or null if the snippet was not found.
     */
    public static function get_snippet_meta($path, $source): stdClass|null {
        // Get the snippets file, based on the source.
        $file = self::get_snippet_file($path, $source);

        // If the file does not exist or is not readable, we can not proceed.
        if (is_null($file)) {
            return null;
        }

        // Extract the meta from the SCSS file's top level multiline comment in WordPress style.
        $headers = self::get_snippet_meta_from_file($file);

        // The title is the only required meta-key that actually must be set.
        // If it is not present, we can not proceed.
        if (!array_key_exists('Snippet Title', $headers)) {
            return null;
        }

        // Create an object containing the metadata.
        $snippet = new stdClass();
        $snippet->title = $headers['Snippet Title'];
        $snippet->description = $headers['Description'];
        $snippet->scope = $headers['Scope'];
        $snippet->goal = $headers['Goal'];
        $snippet->source = $source;

        return $snippet;
    }

    /**
     * Combine snippets meta data from the snippets file with the database record.
     *
     * This is currently used for create the view for the settings table.
     *
     * @param \moodle_recordset $snippetrecordset The recordset of snippets which are present in the database.
     *
     * @return array An array of snippet objects.
     */
    public static function compose_snippets_data($snippetrecordset): array {
        // Initialize snippets array for returning later.
        $snippets = [];

        // Iterate over the snippets which are present in the database.
        foreach ($snippetrecordset as $snippetrecord) {
            // Get the meta information from the SCSS files' top multiline comment.
            $snippet = self::get_snippet_meta($snippetrecord->path, $snippetrecord->source);
            // Only if snippet metadata is found, it will be added to the returned snippets array.
            if ($snippet) {
                // Merge the two objects.
                $snippets[] = (object) array_merge((array) $snippetrecord, (array) $snippet);
            }
        }

        return $snippets;
    }

    /**
     * Checks which snippets are active and returns their scss.
     *
     * @return string
     */
    public static function get_enabled_snippet_scss(): string {
        global $DB;

        // Compose SQL base query.
        $sql = "SELECT *
                FROM {theme_boost_union_snippets} s
                WHERE enabled = '1'
                ORDER BY sortorder";

        // Get records.
        $data = $DB->get_recordset_sql($sql);

        // Initialize SCSS code.
        $scss = '';

        // Iterate over all records.
        foreach ($data as $snippet) {
            // And add the SCSS code to the stack.
            $scss .= self::get_snippet_scss($snippet->path, $snippet->source);
        }

        // Close the recordset.
        $data->close();

        // Return the SCSS code.
        return $scss;
    }

    /**
     * Strips close comment and close php tags from file headers.
     *
     * @copyright WordPress https://developer.wordpress.org/reference/functions/_cleanup_header_comment/
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
     * @copyright forked from https://developer.wordpress.org/reference/functions/get_file_data/
     *
     * @param string $file            Absolute path to the file.
     *
     * @return string[] Array of file header values keyed by header name.
     */
    public static function get_snippet_meta_from_file($file): array {
        // Pull only the first 8 KB of the file in.
        $filedata = file_get_contents( $file, false, null, 0, 8192);

        if ( false === $filedata ) {
            $filedata = '';
        }

        // Make sure we catch CR-only line endings.
        $filedata = str_replace( "\r", "\n", $filedata );

        $headers = [];

        // Scan for each snippet header meta information in the files top scss comment.
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
     * Retrieve all builtin CSS snippetsfrom the actual scss files on disk.
     *
     * @return string[]
     */
    private static function get_builtin_snippet_paths(): array {
        global $CFG;
        // Get an array of all .scss files in the directory.
        $files = glob($CFG->dirroot . self::BUILTIN_SNIPPETS_BASE_PATH . '*.scss');

        // If there are files.
        if (is_array($files)) {
            // Return an array of the basenames of the files.
            $basenames = array_map(function($file) {
                return basename($file);
            }, $files);
            return $basenames;

            // Otherwise.
        } else {
            return [];
        }
    }

    /**
     * Make sure that the builtin snippets are in the database.
     *
     * @return void
     */
    public static function add_builtin_snippets(): void {
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
        $sortorder = empty($snippets) ? 0 : intval(reset($snippets)->sortorder);

        // Prepare an array with all the present builtin snippet paths.
        $presentpaths = array_map(function($snippet) {
            return $snippet->path;
        }, $snippets);

        $transaction = $DB->start_delegated_transaction();

        // Iterate over the builtin snippets that are present on disk.
        foreach ($paths as $path) {
            // If the snippet is not in the database yet.
            if (!in_array($path, $presentpaths)) {
                // Add it to the database (raising the sort order).
                $DB->insert_record(
                    'theme_boost_union_snippets',
                    [
                        'path' => $path,
                        'source' => 'theme_boost_union',
                        'sortorder' => $sortorder + 1,
                    ]
                );
            }
        }

        // Note: snippets which exist in the database and do not exist on disk won't be removed from the database in
        // in this process.
        // They will stay there until the theme is removed. This is to make sure that snippet settings do not get lost
        // even if they appear from disk temporarily.
        // But such snippets will be ignored later when the snippet table is processed and when the SCSS is composed.

        $transaction->allow_commit();
    }
}