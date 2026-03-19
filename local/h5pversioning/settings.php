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
 * Plugin settings links.
 *
 * @package    local_h5pversioning
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    // Admin-configured scope for "single dev course bank" monitoring.
    $settings = new admin_settingpage(
        'local_h5pversioning_settings',
        get_string('pluginname', 'local_h5pversioning')
    );
    $settings->add(new admin_setting_configcheckbox(
        'local_h5pversioning/enabled',
        get_string('enabled', 'local_h5pversioning'),
        get_string('enabled_desc', 'local_h5pversioning'),
        1
    ));
    $settings->add(new admin_setting_configtext(
        'local_h5pversioning/monitoredcourseid',
        get_string('monitoredcourseid', 'local_h5pversioning'),
        get_string('monitoredcourseid_desc', 'local_h5pversioning'),
        0,
        PARAM_INT
    ));
    $ADMIN->add('localplugins', $settings);

    // Read-only report pages used by dev team.
    $ADMIN->add('localplugins', new admin_externalpage(
        'local_h5pversioning_logs',
        get_string('viewlogs', 'local_h5pversioning'),
        new moodle_url('/local/h5pversioning/log.php'),
        'local/h5pversioning:viewreports'
    ));

    $ADMIN->add('localplugins', new admin_externalpage(
        'local_h5pversioning_versions',
        get_string('viewversions', 'local_h5pversioning'),
        new moodle_url('/local/h5pversioning/versions.php'),
        'local/h5pversioning:viewreports'
    ));
}
