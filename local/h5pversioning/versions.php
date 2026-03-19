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
admin_externalpage_setup('local_h5pversioning_versions');
require_capability('local/h5pversioning:viewreports', context_system::instance());

$PAGE->set_url(new moodle_url('/local/h5pversioning/versions.php'));
$PAGE->set_title(get_string('viewversions', 'local_h5pversioning'));
$PAGE->set_heading(get_string('viewversions', 'local_h5pversioning'));

$highlight = optional_param('highlight', 0, PARAM_INT);
$baseurl = new moodle_url('/local/h5pversioning/versions.php');
$total = $DB->count_records('local_h5pversioning_version');
$page = optional_param('page', 0, PARAM_INT);
$perpage = 50;
$offset = $page * $perpage;

// Manifest rows are created in versioning_service after snapshot file copy succeeds.
$records = $DB->get_records('local_h5pversioning_version', null, 'id DESC', '*', $offset, $perpage);
$systemcontext = context_system::instance();

$table = new html_table();
$table->head = [
    'ID',
    get_string('time'),
    get_string('contentid', 'local_h5pversioning'),
    get_string('contentname', 'local_h5pversioning'),
    get_string('version', 'local_h5pversioning'),
    get_string('user'),
    get_string('courseid', 'local_h5pversioning'),
    get_string('contenthash', 'local_h5pversioning'),
    get_string('download'),
];
$table->data = [];

foreach ($records as $record) {
    // Pluginfile URL served by local_h5pversioning_pluginfile() in lib.php.
    $downloadurl = moodle_url::make_pluginfile_url(
        $systemcontext->id,
        'local_h5pversioning',
        'snapshot',
        (int)$record->contentid,
        '/',
        (string)$record->snapshotfilename,
        true
    );
    $download = html_writer::link($downloadurl, get_string('download'));

    $idcell = (int)$record->id;
    if ($highlight > 0 && $highlight === (int)$record->id) {
        $idcell = html_writer::tag('strong', '#' . (int)$record->id);
    }

    $table->data[] = [
        $idcell,
        userdate((int)$record->timecreated),
        (int)$record->contentid,
        s($record->contentname),
        (int)$record->versionno,
        s(trim($record->username) !== '' ? $record->username . ' (#' . $record->userid . ')' : (string)$record->userid),
        (int)$record->courseid,
        html_writer::tag('small', s($record->contenthash)),
        $download,
    ];
}

echo $OUTPUT->header();
echo $OUTPUT->paging_bar($total, $page, $perpage, $baseurl);
echo html_writer::table($table);
echo $OUTPUT->paging_bar($total, $page, $perpage, $baseurl);
echo $OUTPUT->footer();
