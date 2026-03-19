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
 * Upgrade script for local_h5pversioning.
 *
 * @package    local_h5pversioning
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Perform plugin upgrade steps.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_h5pversioning_upgrade(int $oldversion): bool {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026031600) {
        // Upgrade from probe-only schema to scoped versioning schema.
        $evtlogtable = new xmldb_table('local_h5pversioning_evtlog');

        $decisionfield = new xmldb_field('decision', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, '', 'fullpayload');
        if (!$dbman->field_exists($evtlogtable, $decisionfield)) {
            $dbman->add_field($evtlogtable, $decisionfield);
        }

        $contentidfield = new xmldb_field('contentid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'decision');
        if (!$dbman->field_exists($evtlogtable, $contentidfield)) {
            $dbman->add_field($evtlogtable, $contentidfield);
        }

        $contenthashfield = new xmldb_field('contenthash', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, '', 'contentid');
        if (!$dbman->field_exists($evtlogtable, $contenthashfield)) {
            $dbman->add_field($evtlogtable, $contenthashfield);
        }

        $versionidfield = new xmldb_field('versionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'contenthash');
        if (!$dbman->field_exists($evtlogtable, $versionidfield)) {
            $dbman->add_field($evtlogtable, $versionidfield);
        }

        $monitorcourseidfield = new xmldb_field(
            'monitorcourseid',
            XMLDB_TYPE_INTEGER,
            '10',
            null,
            XMLDB_NOTNULL,
            null,
            '0',
            'versionid'
        );
        if (!$dbman->field_exists($evtlogtable, $monitorcourseidfield)) {
            $dbman->add_field($evtlogtable, $monitorcourseidfield);
        }

        $contentidindex = new xmldb_index('contentid_idx', XMLDB_INDEX_NOTUNIQUE, ['contentid']);
        if (!$dbman->index_exists($evtlogtable, $contentidindex)) {
            $dbman->add_index($evtlogtable, $contentidindex);
        }

        $versionidindex = new xmldb_index('versionid_idx', XMLDB_INDEX_NOTUNIQUE, ['versionid']);
        if (!$dbman->index_exists($evtlogtable, $versionidindex)) {
            $dbman->add_index($evtlogtable, $versionidindex);
        }

        $monitorcourseidindex = new xmldb_index('monitorcourseid_idx', XMLDB_INDEX_NOTUNIQUE, ['monitorcourseid']);
        if (!$dbman->index_exists($evtlogtable, $monitorcourseidindex)) {
            $dbman->add_index($evtlogtable, $monitorcourseidindex);
        }

        $versiontable = new xmldb_table('local_h5pversioning_version');
        if (!$dbman->table_exists($versiontable)) {
            $versiontable->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $versiontable->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $versiontable->add_field('monitorcourseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $versiontable->add_field('contentid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $versiontable->add_field('contentname', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, '');
            $versiontable->add_field('contextid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $versiontable->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $versiontable->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $versiontable->add_field('username', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, '');
            $versiontable->add_field('eventclass', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, '');
            $versiontable->add_field('versionno', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $versiontable->add_field('contenthash', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, '');
            $versiontable->add_field('sourcefileid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $versiontable->add_field('snapshotfileid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $versiontable->add_field('snapshotfilename', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, '');
            $versiontable->add_field('filesize', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, '0');

            $versiontable->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $versiontable->add_index('contentid_idx', XMLDB_INDEX_NOTUNIQUE, ['contentid']);
            $versiontable->add_index('monitorcourseid_idx', XMLDB_INDEX_NOTUNIQUE, ['monitorcourseid']);
            $versiontable->add_index('contenthash_idx', XMLDB_INDEX_NOTUNIQUE, ['contenthash']);
            $versiontable->add_index('content_version_uix', XMLDB_INDEX_UNIQUE, ['contentid', 'versionno']);

            $dbman->create_table($versiontable);
        }

        upgrade_plugin_savepoint(true, 2026031600, 'local', 'h5pversioning');
    }

    return true;
}
