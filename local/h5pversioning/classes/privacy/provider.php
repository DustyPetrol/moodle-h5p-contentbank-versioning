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

namespace local_h5pversioning\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\metadata\provider as metadata_provider;

/**
 * Privacy provider for local_h5pversioning.
 *
 * @package    local_h5pversioning
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements metadata_provider {
    /**
     * Returns metadata about this plugin's data storage.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('local_h5pversioning_evtlog', [
            'userid' => 'privacy:metadata:local_h5pversioning_evtlog:userid',
            'username' => 'privacy:metadata:local_h5pversioning_evtlog:username',
            'eventclass' => 'privacy:metadata:local_h5pversioning_evtlog:eventclass',
            'otherdata' => 'privacy:metadata:local_h5pversioning_evtlog:otherdata',
            'decision' => 'privacy:metadata:local_h5pversioning_evtlog:decision',
        ], 'privacy:metadata:local_h5pversioning_evtlog');

        $collection->add_database_table('local_h5pversioning_version', [
            'userid' => 'privacy:metadata:local_h5pversioning_version:userid',
            'username' => 'privacy:metadata:local_h5pversioning_version:username',
            'contentid' => 'privacy:metadata:local_h5pversioning_version:contentid',
        ], 'privacy:metadata:local_h5pversioning_version');

        return $collection;
    }
}
