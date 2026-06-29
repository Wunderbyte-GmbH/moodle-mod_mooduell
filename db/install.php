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

    // On installation we add the "MooDuell Alias" custom user profile field, which lets users
    // play under a nickname instead of their real name.
    global $DB;

    if ($DB->record_exists('user_info_field', ['shortname' => 'mooduell_alias'])) {
        // The field already exists, so we do not need to create it again.
        return true;
    }

    // Create (or reuse) the "Mooduell" profile field category. These values mirror exactly what
    // the core data generator used to produce, so the field is identical to existing installs.
    if (!$categoryid = $DB->get_field('user_info_category', 'id', ['name' => 'Mooduell'])) {
        $category = new stdClass();
        $category->name = 'Mooduell';
        $category->sortorder = (int) $DB->get_field_sql('SELECT MAX(sortorder) FROM {user_info_category}') + 1;
        $categoryid = $DB->insert_record('user_info_category', $category);
    }

    // Create the "MooDuell Alias" text profile field, hidden from everyone by default.
    $field = new stdClass();
    $field->shortname = 'mooduell_alias';
    $field->name = 'MooDuell Alias';
    $field->datatype = 'text';
    $field->description = 'An alias name for the users.';
    $field->descriptionformat = 0;
    $field->categoryid = $categoryid;
    $field->sortorder = (int) $DB->get_field_sql(
        'SELECT MAX(sortorder) FROM {user_info_field} WHERE categoryid = ?',
        [$categoryid]
    ) + 1;
    $field->required = 0;
    $field->locked = 0;
    $field->visible = 0; // PROFILE_VISIBLE_NONE: not shown to anybody.
    $field->forceunique = 0;
    $field->signup = 0;
    $field->defaultdata = '';
    $field->defaultdataformat = 0;
    $field->param1 = 30; // Display size (text field default).
    $field->param2 = 2048; // Maximum length (text field default).
    $field->param3 = ''; // Not a password field.
    $field->param4 = '';
    $field->param5 = '';
    $DB->insert_record('user_info_field', $field);

    return true;
}
