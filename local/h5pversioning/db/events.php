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
 * Event observers.
 *
 * @package    local_h5pversioning
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$observers = [
    // Core Content Bank lifecycle events used for versioning decisions.
    // Moodle fires these from core and calls our observer callback directly.
    [
        'eventname' => '\\core\\event\\contentbank_content_created',
        'callback' => '\\local_h5pversioning\\observer::handle_content_event',
        'priority' => 9999,
    ],
    [
        'eventname' => '\\core\\event\\contentbank_content_updated',
        'callback' => '\\local_h5pversioning\\observer::handle_content_event',
        'priority' => 9999,
    ],
    [
        'eventname' => '\\core\\event\\contentbank_content_uploaded',
        'callback' => '\\local_h5pversioning\\observer::handle_content_event',
        'priority' => 9999,
    ],
];
