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
 * Library of interface functions and constants.
 *
 * @package     mod_mooduell
 * @copyright   2020 David Bogner <david.bogner@wunderbyte.at>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Return if the plugin supports $feature.
 *
 * @param string $feature Constant representing the feature.
 * @return true | null True if the feature is supported, null otherwise.
 */
function mooduell_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the mod_mooduell into the database.
 *
 * Given an object containing all the necessary data, (defined by the form
 * in mod_form.php) this function will create a new instance and return the id
 * number of the instance.
 *
 * @param stdClass $data
 * @param mod_mooduell_mod_form|null $mform
 * @return bool|int
 * @throws dml_exception
 */
function mooduell_add_instance(stdClass $data, mod_mooduell_mod_form $mform = null) {
    global $CFG;
    require_once($CFG->dirroot . '/mod/mooduell/classes/mooduell.php');
    return \mod_mooduell\mooduell::add_instance($data);
}

/**
 * Updates an instance of the mod_mooduell in the database.
 *
 * Given an object containing all the necessary data (defined in mod_form.php),
 * this function will update an existing instance with new data.
 *
 * @param object $moduleinstance An object from the form in mod_form.php.
 * @param mod_mooduell_mod_form $mform The form.
 * @return bool True if successful, false otherwise.
 */
function mooduell_update_instance($moduleinstance, $mform = null) {
    global $DB;

    $moduleinstance->timemodified = time();
    $moduleinstance->id = $moduleinstance->instance;

    return $DB->update_record('mooduell', $moduleinstance);
}

/**
 * Removes an instance of the mod_mooduell from the database.
 *
 * @param int $id Id of the module instance.
 * @return bool True if successful, false on failure.
 */
function mooduell_delete_instance($id) {
    global $DB;

    $exists = $DB->get_record('mooduell', array('id' => $id));
    if (!$exists) {
        return false;
    }

    $DB->delete_records('mooduell', array('id' => $id));

    return true;
}
