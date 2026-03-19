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
 * English language pack for local_h5pversioning.
 *
 * @package    local_h5pversioning
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'H5P versioning';
$string['enabled'] = 'Enable versioning monitor';
$string['enabled_desc'] = 'If disabled, the plugin ignores Content Bank events.';
$string['monitoredcourseid'] = 'Monitored dev course ID';
$string['monitoredcourseid_desc'] = 'Only H5P Content Bank events from this course are tracked and versioned.';
$string['viewlogs'] = 'H5P versioning event log';
$string['viewversions'] = 'H5P version snapshots';
$string['deletealllogs'] = 'Delete all event logs';
$string['logsdeleted'] = 'Event logs deleted.';
$string['eventclass'] = 'Event class';
$string['decision'] = 'Decision';
$string['version'] = 'Version';
$string['contentid'] = 'Content ID';
$string['contentname'] = 'Content name';
$string['contenthash'] = 'Content hash';
$string['objectid'] = 'Object ID';
$string['contextid'] = 'Context ID';
$string['courseid'] = 'Course ID';
$string['otherdata'] = 'Other payload';
$string['h5pversioning:viewreports'] = 'View H5P versioning reports';
$string['privacy:metadata:local_h5pversioning_evtlog'] = 'Stores captured event metadata and versioning decisions.';
$string['privacy:metadata:local_h5pversioning_evtlog:userid'] = 'ID of the user associated with the event.';
$string['privacy:metadata:local_h5pversioning_evtlog:username'] = 'Username resolved at capture time for easier debugging.';
$string['privacy:metadata:local_h5pversioning_evtlog:eventclass'] = 'Event class name.';
$string['privacy:metadata:local_h5pversioning_evtlog:otherdata'] = 'Event other payload in JSON format.';
$string['privacy:metadata:local_h5pversioning_evtlog:decision'] = 'Versioning decision result for the event.';
$string['privacy:metadata:local_h5pversioning_version'] = 'Stores version manifest metadata for archived H5P snapshots.';
$string['privacy:metadata:local_h5pversioning_version:userid'] = 'ID of the user who triggered the snapshot.';
$string['privacy:metadata:local_h5pversioning_version:username'] = 'Username resolved when the snapshot was created.';
$string['privacy:metadata:local_h5pversioning_version:contentid'] = 'Content Bank item ID being versioned.';
