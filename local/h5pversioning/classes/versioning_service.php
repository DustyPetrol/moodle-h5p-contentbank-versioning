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
use core_contentbank\contentbank;
use context_course;
use context_system;
use stdClass;

/**
 * Handles versioning decisions for H5P Content Bank events.
 *
 * @package    local_h5pversioning
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class versioning_service {
    /** @var bool|null */
    protected static $tablesready = null;

    /**
     * Process a single Content Bank event.
     *
     * @param base $event
     * @return void
     */
    public static function handle_content_event(base $event): void {
        global $DB;

        // Guard rails: skip processing during upgrade windows or when disabled.
        if (!self::tables_ready() || !self::is_enabled()) {
            return;
        }

        // Scope rule: version only one configured dev course bank.
        $monitoredcourseid = self::get_monitored_courseid();
        if ($monitoredcourseid <= 0 || !self::is_in_scope($event, $monitoredcourseid)) {
            return;
        }

        // Start a log row from Moodle event fields (userid, objectid, contextid, other, ...).
        $record = self::build_base_event_record($event, $monitoredcourseid);
        $record->source = 'candidate';

        if ((int)$record->contentid <= 0) {
            $record->decision = 'missing_contentid';
            self::insert_event_log($record);
            return;
        }

        try {
            // Core API: objectid from event => Content Bank content entity.
            $cb = new contentbank();
            $content = $cb->get_content_from_id((int)$record->contentid);
        } catch (\Throwable $e) {
            $record->decision = 'content_not_found';
            self::insert_event_log($record);
            return;
        }

        // We only snapshot H5P content type.
        if ((string)$content->get_content_type() !== 'contenttype_h5p') {
            $record->decision = 'ignored_non_h5p';
            self::insert_event_log($record);
            return;
        }

        // Core API: get current stored_file behind this content item.
        $sourcefile = $content->get_file();
        if (!$sourcefile) {
            $record->decision = 'missing_source_file';
            self::insert_event_log($record);
            return;
        }

        // This hash is our dedupe key for "same bytes => no new snapshot".
        $record->contenthash = (string)$sourcefile->get_contenthash();

        $latestversion = self::get_latest_version((int)$record->contentid);
        if ($latestversion && $latestversion->contenthash === $record->contenthash) {
            $record->decision = 'duplicate_skipped';
            $record->versionid = (int)$latestversion->id;
            self::insert_event_log($record);
            return;
        }

        $versionno = $latestversion ? ((int)$latestversion->versionno + 1) : 1;
        $versionrecord = self::build_version_record(
            (int)$record->contentid,
            (string)$content->get_name(),
            $record,
            $versionno,
            (int)$sourcefile->get_id()
        );

        try {
            // Store snapshot inside Moodle file API (system context, plugin filearea).
            $snapshotfile = self::archive_snapshot_file(
                (int)$record->contentid,
                $versionno,
                (string)$content->get_name(),
                $sourcefile,
                (int)$record->userid,
                (string)$record->username
            );

            $versionrecord->snapshotfileid = (int)$snapshotfile->get_id();
            $versionrecord->snapshotfilename = (string)$snapshotfile->get_filename();
            $versionrecord->filesize = (int)$snapshotfile->get_filesize();
            $versionid = (int)$DB->insert_record('local_h5pversioning_version', $versionrecord);

            $record->decision = 'snapshot_created';
            $record->versionid = $versionid;
            self::insert_event_log($record);
        } catch (\Throwable $e) {
            // Keep the failure visible in event log for post-mortem.
            $record->decision = 'snapshot_failed';
            self::insert_event_log($record);
            throw $e;
        }
    }

    /**
     * Build the base event log row.
     *
     * @param base $event
     * @param int $monitoredcourseid
     * @return stdClass
     */
    protected static function build_base_event_record(base $event, int $monitoredcourseid): stdClass {
        global $DB;

        // Event has userid only; resolve username for readable reports.
        $username = '';
        if (!empty($event->userid)) {
            $user = $DB->get_record('user', ['id' => $event->userid], 'id,username', IGNORE_MISSING);
            if ($user) {
                $username = (string)$user->username;
            }
        }

        $record = new stdClass();
        $record->timecreated = time();
        $record->source = '';
        $record->eventclass = '\\' . ltrim(get_class($event), '\\');
        $record->eventtime = (int)$event->timecreated;
        $record->userid = (int)($event->userid ?? 0);
        $record->username = $username;
        $record->objectid = isset($event->objectid) ? (string)$event->objectid : '';
        $record->contextid = isset($event->contextid) ? (int)$event->contextid : 0;
        $record->courseid = isset($event->courseid) ? (int)$event->courseid : 0;
        $record->component = (string)$event->component;
        $record->action = (string)$event->action;
        $record->target = (string)$event->target;
        $record->crud = (string)$event->crud;
        $record->edulevel = (int)$event->edulevel;
        $record->otherdata = json_encode($event->other, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
        $record->fullpayload = '{}';
        $record->decision = '';
        // objectid from these events is the Content Bank item id we version.
        $record->contentid = isset($event->objectid) ? (int)$event->objectid : 0;
        $record->contenthash = '';
        $record->versionid = 0;
        $record->monitorcourseid = $monitoredcourseid;

        return $record;
    }

    /**
     * Build a new version manifest record.
     *
     * @param int $contentid
     * @param string $contentname
     * @param stdClass $eventrecord
     * @param int $versionno
     * @param int $sourcefileid
     * @return stdClass
     */
    protected static function build_version_record(
        int $contentid,
        string $contentname,
        stdClass $eventrecord,
        int $versionno,
        int $sourcefileid
    ): stdClass {
        $version = new stdClass();
        $version->timecreated = time();
        $version->monitorcourseid = (int)$eventrecord->monitorcourseid;
        $version->contentid = $contentid;
        $version->contentname = $contentname;
        $version->contextid = (int)$eventrecord->contextid;
        $version->courseid = (int)$eventrecord->courseid;
        $version->userid = (int)$eventrecord->userid;
        $version->username = (string)$eventrecord->username;
        $version->eventclass = (string)$eventrecord->eventclass;
        $version->versionno = $versionno;
        $version->contenthash = (string)$eventrecord->contenthash;
        $version->sourcefileid = $sourcefileid;
        $version->snapshotfileid = 0;
        $version->snapshotfilename = '';
        $version->filesize = 0;

        return $version;
    }

    /**
     * Write event row to the log table.
     *
     * @param stdClass $record
     * @return int
     */
    protected static function insert_event_log(stdClass $record): int {
        global $DB;

        // Keep one JSON blob for quick debugging without joining many columns.
        $record->fullpayload = json_encode([
            'source' => $record->source,
            'event_class' => $record->eventclass,
            'time' => userdate($record->eventtime, '%Y-%m-%d %H:%M:%S'),
            'timecreated' => $record->eventtime,
            'userid' => $record->userid,
            'username' => $record->username,
            'objectid' => $record->objectid,
            'contentid' => $record->contentid,
            'contextid' => $record->contextid,
            'courseid' => $record->courseid,
            'component' => $record->component,
            'action' => $record->action,
            'target' => $record->target,
            'crud' => $record->crud,
            'edulevel' => $record->edulevel,
            'decision' => $record->decision,
            'contenthash' => $record->contenthash,
            'versionid' => $record->versionid,
            'monitorcourseid' => $record->monitorcourseid,
            'other' => json_decode($record->otherdata, true),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';

        return (int)$DB->insert_record('local_h5pversioning_evtlog', $record);
    }

    /**
     * Archive an H5P file into plugin-managed storage.
     *
     * @param int $contentid
     * @param int $versionno
     * @param string $contentname
     * @param \stored_file $sourcefile
     * @param int $userid
     * @param string $username
     * @return \stored_file
     */
    protected static function archive_snapshot_file(
        int $contentid,
        int $versionno,
        string $contentname,
        \stored_file $sourcefile,
        int $userid,
        string $username
    ): \stored_file {
        $fs = get_file_storage();
        $systemcontext = context_system::instance();

        $license = (string)$sourcefile->get_license();
        if ($license === '') {
            $license = 'allrightsreserved';
        }

        $filerecord = [
            'contextid' => $systemcontext->id,
            'component' => 'local_h5pversioning',
            'filearea' => 'snapshot',
            // Group snapshots by source Content Bank id.
            'itemid' => $contentid,
            'filepath' => '/',
            'filename' => self::build_snapshot_filename($contentname, $sourcefile->get_filename(), $versionno),
            'userid' => $userid,
            'author' => $username,
            'license' => $license,
            'source' => 'local_h5pversioning',
            'timecreated' => time(),
            'timemodified' => time(),
        ];

        return $fs->create_file_from_storedfile($filerecord, $sourcefile);
    }

    /**
     * Build a stable snapshot filename.
     *
     * @param string $contentname
     * @param string $sourcefilename
     * @param int $versionno
     * @return string
     */
    protected static function build_snapshot_filename(string $contentname, string $sourcefilename, int $versionno): string {
        $base = clean_filename($contentname);
        if ($base === '') {
            $base = clean_filename($sourcefilename);
        }
        if ($base === '') {
            $base = 'content.h5p';
        }

        $path = pathinfo($base);
        $name = $path['filename'] ?? 'content';
        $ext = isset($path['extension']) && $path['extension'] !== '' ? '.' . $path['extension'] : '.h5p';

        return $name . '-v' . $versionno . '-' . gmdate('Ymd-His') . $ext;
    }

    /**
     * Get latest snapshot row for one Content Bank item.
     *
     * @param int $contentid
     * @return stdClass|null
     */
    protected static function get_latest_version(int $contentid): ?stdClass {
        global $DB;

        // Version numbers are monotonic per contentid.
        $records = $DB->get_records(
            'local_h5pversioning_version',
            ['contentid' => $contentid],
            'versionno DESC',
            '*',
            0,
            1
        );
        if (!$records) {
            return null;
        }
        return reset($records) ?: null;
    }

    /**
     * Check if versioning should currently run.
     *
     * @return bool
     */
    protected static function is_enabled(): bool {
        $enabled = get_config('local_h5pversioning', 'enabled');
        if ($enabled === false || $enabled === null) {
            return true;
        }
        return (bool)$enabled;
    }

    /**
     * Read monitored course id.
     *
     * @return int
     */
    protected static function get_monitored_courseid(): int {
        return (int)get_config('local_h5pversioning', 'monitoredcourseid');
    }

    /**
     * Check if event is in configured monitored scope.
     *
     * @param base $event
     * @param int $monitoredcourseid
     * @return bool
     */
    protected static function is_in_scope(base $event, int $monitoredcourseid): bool {
        if ((int)$event->courseid !== $monitoredcourseid) {
            return false;
        }

        // Extra guard: context must be that course's context, not system.
        $coursecontext = context_course::instance($monitoredcourseid, IGNORE_MISSING);
        if (!$coursecontext) {
            return false;
        }

        return (int)$event->contextid === (int)$coursecontext->id;
    }

    /**
     * Ensure required tables exist before processing events.
     *
     * @return bool
     */
    protected static function tables_ready(): bool {
        global $DB;

        if (self::$tablesready !== null) {
            return self::$tablesready;
        }

        try {
            $DB->get_columns('local_h5pversioning_evtlog');
            $DB->get_columns('local_h5pversioning_version');
            self::$tablesready = true;
        } catch (\Throwable $e) {
            self::$tablesready = false;
        }

        return self::$tablesready;
    }
}
