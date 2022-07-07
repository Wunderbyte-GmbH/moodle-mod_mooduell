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
 * Code to be executed after the plugin's database scheme has been installed is defined here.
 *
 * @package     mod_mooduell
 * @category    upgrade
 * @copyright   2020 David Bogner <david.bogner@wunderbyte.at>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Custom code to be run on installing the plugin.
 */
function xmldb_mooduell_install() {

    // On the installation we include new Profile fields to allow user suspension date stamps.
    global $DB;

    $sql = 'SELECT MAX(sortorder) FROM {user_info_field}';

    if (!$maxsortorder = $DB->get_field_sql($sql)) {
        $maxsortorder = 1;
    } else {
        ++$maxsortorder;
    }

    $mooduellalias = [
            'shortname' => 'mooduell_alias',
            'name' => 'MooDuell Alias',
            'datatype' => 'text',
            'description' => 'MooDuell Alias',
            'descriptionformat' => 1,
            'categoryid' => 1,
            'sortorder' => $maxsortorder,
            'required' => 0,
            'locked' => 0,
            'visible' => 0,
            'forceunique' => 0,
            'signup' => 0,
            'defaultdata' => '',
            'defaultdataformat' => 0,
            'param1' => 0,
            'param2' => 2048
    ];

    if (!$DB->record_exists('user_info_field', array('shortname' => $mooduellalias['shortname']))) {
        $DB->insert_record('user_info_field', $mooduellalias);
    }

    return true;
}
