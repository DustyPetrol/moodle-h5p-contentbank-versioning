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

namespace local_h5pversioning;

use core\event\base;

/**
 * Event observer for H5P Content Bank versioning.
 *
 * @package    local_h5pversioning
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {
    /**
     * Process a Content Bank event.
     *
     * @param base $event
     * @return void
     */
    public static function handle_content_event(base $event): void {
        try {
            // Keep observer thin: all business logic lives in versioning_service.
            versioning_service::handle_content_event($event);
        } catch (\Throwable $e) {
            // Never break user save flow if versioning fails; only log diagnostics.
            $message = '[local_h5pversioning] Event processing failed: ' . $e->getMessage();
            debugging($message, DEBUG_DEVELOPER);
            error_log($message);
        }
    }
}
