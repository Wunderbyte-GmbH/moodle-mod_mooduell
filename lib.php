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
 * @copyright   2020 Georg Maißer <georg.maisser@wunderbyte.at>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_mooduell\mooduell;

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
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_USES_QUESTIONS:
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
    return mooduell::add_instance($data);
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
function mooduell_update_instance($moduleinstance, mod_mooduell_mod_form $mform = null) {
    global $DB;

    $moduleinstance->timemodified = time();
    $moduleinstance->id = $moduleinstance->instance;

    mod_mooduell\mooduell::update_categories($moduleinstance->id, $mform->get_data());

    // As empty checkboxes are not included in data, we have to make sure they are transmitted to DB.
    // Check for keys and add 0 if they are not present.
    if (!isset($moduleinstance->showcontinuebutton)) {
        $moduleinstance->showcontinuebutton = 0;
    }
    if (!isset($moduleinstance->showcorrectanswer)) {
        $moduleinstance->showcorrectanswer = 0;
    }
    if (!isset($moduleinstance->usefullnames)) {
        $moduleinstance->usefullnames = 0;
    }

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

    // TODO: Delete all entries belonging to this instance as well from db

    $DB->delete_records('mooduell', array('id' => $id));
    $DB->delete_records('mooduell_categories', array('mooduellid' => $id));
    $DB->delete_records('mooduell_games', array('mooduellid' => $id));
    $DB->delete_records('mooduell_questions', array('mooduellid' => $id));

    return true;
}

/**
 * Serve the files from the mooduell file areas
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param stdClass $context the context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if the file not found, just send the file otherwise and do not return anything
 */
function mooduell_question_pluginfile($course, $context, $component, $filearea, $qubaid, $slot, $args, $forcedownload, array $options=array()) {

    // Note: We might get different context levels, (System, Course) so we don't check them.
    // Check the contextlevel is as expected - if your plugin is a block, this becomes CONTEXT_BLOCK, etc.
    /*if ($context->contextlevel != COURSE_MODULE) {
        return false;
    }*/

    // Make sure the filearea is one of those used by the plugin.
    if ($filearea !== 'questiontext') {
        return false;
    }

    // Make sure the user is logged in and has access to the module (plugins that are not course modules should leave out the 'cm' part).
    require_login($course, true);

    // Check the relevant capabilities - these may vary depending on the filearea being accessed.
    // if (!has_capability('mod/mooduell:view', $context)) {
        // return false;
    // }

    // Leave this line out if you set the itemid to null in make_pluginfile_url (set $itemid to 0 instead).
    $itemid = array_shift($args); // The first item in the $args array.

    // Use the itemid to retrieve any relevant data records and perform any security checks to see if the
    // user really does have access to the file in question.

    // Extract the filename / filepath from the $args array.
    $filename = array_pop($args); // The last item in the $args array.
    if (!$args) {
        $filepath = '/'; // $args is empty => the path is '/'
    } else {
        $filepath = '/'.implode('/', $args).'/'; // $args contains elements of the filepath
    }

    // Retrieve the file from the Files API.
    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'question', $filearea, $itemid, $filepath, $filename);
    if (!$file) {
        return false; // The file does not exist.
    }

    // We can now send the file back to the browser - in this case with a cache lifetime of 1 day and no filtering.
    send_stored_file($file, 86400, 0, $forcedownload, $options);
}
