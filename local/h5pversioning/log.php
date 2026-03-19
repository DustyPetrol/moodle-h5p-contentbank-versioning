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

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

// Registered admin page id from settings.php.
admin_externalpage_setup('local_h5pversioning_logs');
// Dev-only report access capability.
require_capability('local/h5pversioning:viewreports', context_system::instance());

// Action params from request (GET/POST).
$deleteall = optional_param('deleteall', 0, PARAM_BOOL);
$confirm = optional_param('confirm', '', PARAM_ALPHANUM);

// Delete action guarded by sesskey + confirm token.
if ($deleteall && confirm_sesskey() && $confirm === md5(sesskey())) {
    $DB->delete_records('local_h5pversioning_evtlog');
    redirect(new moodle_url('/local/h5pversioning/log.php'), get_string('logsdeleted', 'local_h5pversioning'));
}

$PAGE->set_url(new moodle_url('/local/h5pversioning/log.php'));
$PAGE->set_title(get_string('viewlogs', 'local_h5pversioning'));
$PAGE->set_heading(get_string('viewlogs', 'local_h5pversioning'));

$baseurl = new moodle_url('/local/h5pversioning/log.php');
$total = $DB->count_records('local_h5pversioning_evtlog');
$page = optional_param('page', 0, PARAM_INT);
$perpage = 50;
$offset = $page * $perpage;

// Event log rows are produced by versioning_service::insert_event_log().
$records = $DB->get_records('local_h5pversioning_evtlog', null, 'id DESC', '*', $offset, $perpage);

$table = new html_table();
$table->head = [
    'ID',
    get_string('time'),
    get_string('eventclass', 'local_h5pversioning'),
    get_string('decision', 'local_h5pversioning'),
    get_string('version', 'local_h5pversioning'),
    get_string('user'),
    get_string('contentid', 'local_h5pversioning'),
    get_string('objectid', 'local_h5pversioning'),
    get_string('contextid', 'local_h5pversioning'),
    get_string('courseid', 'local_h5pversioning'),
    get_string('contenthash', 'local_h5pversioning'),
    get_string('otherdata', 'local_h5pversioning'),
];
$table->data = [];

foreach ($records as $record) {
    $versioncell = '-';
    if (!empty($record->versionid)) {
        // Jump to the linked snapshot row in versions.php.
        $versioncell = html_writer::link(
            new moodle_url('/local/h5pversioning/versions.php', ['highlight' => (int)$record->versionid]),
            '#' . (int)$record->versionid
        );
    }

    $table->data[] = [
        (int)$record->id,
        userdate((int)$record->eventtime),
        s($record->eventclass) . html_writer::empty_tag('br') . html_writer::tag('small', s($record->source)),
        s($record->decision),
        $versioncell,
        s(trim($record->username) !== '' ? $record->username . ' (#' . $record->userid . ')' : (string)$record->userid),
        (int)$record->contentid,
        s($record->objectid),
        (int)$record->contextid,
        (int)$record->courseid,
        html_writer::tag('small', s($record->contenthash)),
        html_writer::tag('pre', s($record->otherdata), ['style' => 'white-space: pre-wrap; max-width: 40rem;']),
    ];
}

echo $OUTPUT->header();

echo $OUTPUT->single_button(
    new moodle_url('/local/h5pversioning/log.php', [
        'deleteall' => 1,
        'confirm' => md5(sesskey()),
        'sesskey' => sesskey(),
    ]),
    get_string('deletealllogs', 'local_h5pversioning'),
    'post',
    ['class' => 'mb-3']
);

echo $OUTPUT->paging_bar($total, $page, $perpage, $baseurl);

echo html_writer::table($table);

echo $OUTPUT->paging_bar($total, $page, $perpage, $baseurl);

echo $OUTPUT->footer();
