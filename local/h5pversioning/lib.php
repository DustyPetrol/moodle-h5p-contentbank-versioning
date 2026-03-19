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
 * Pluginfile handler for local_h5pversioning.
 *
 * @package    local_h5pversioning
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Serve snapshot files from plugin-managed filearea.
 *
 * @param stdClass $course
 * @param stdClass|null $cm
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return bool
 */
function local_h5pversioning_pluginfile(
    $course,
    $cm,
    $context,
    $filearea,
    $args,
    $forcedownload,
    array $options = []
): bool {
    require_once(__DIR__ . '/../../lib/filelib.php');

    // Only serve our own snapshot filearea in system context.
    if ($context->contextlevel !== CONTEXT_SYSTEM || $filearea !== 'snapshot') {
        return false;
    }

    require_capability('local/h5pversioning:viewreports', context_system::instance());

    if (count($args) < 2) {
        return false;
    }

    // Pluginfile args format: [itemid, path..., filename].
    $itemid = (int)array_shift($args);
    $filename = array_pop($args);
    $filepath = '/';
    if (!empty($args)) {
        $filepath = '/' . implode('/', $args) . '/';
    }

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'local_h5pversioning', 'snapshot', $itemid, $filepath, $filename);
    if (!$file || $file->is_directory()) {
        return false;
    }

    send_stored_file($file, 0, 0, $forcedownload, $options);
    return true;
}
