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

use core_completion\api;
use mod_mooduell\mooduell;
use mod_mooduell\completion\custom_completion;

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
        case FEATURE_COMPLETION_HAS_RULES:
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

    mod_mooduell\mooduell::update_categories($moduleinstance->id, (object) $mform->get_data());

    // As empty checkboxes are not included in data, we have to make sure they are transmitted to DB.
    // Check for keys and add 0 if they are not present.
    if (!isset($moduleinstance->showcontinuebutton)) {
        $moduleinstance->showcontinuebutton = 0;
    }
    if (!isset($moduleinstance->showcorrectanswer)) {
        $moduleinstance->showcorrectanswer = 0;
    }
    if (!isset($moduleinstance->showgeneralfeedback)) {
        $moduleinstance->showgeneralfeedback = 0;
    }
    if (!isset($moduleinstance->showanswersfeedback)) {
        $moduleinstance->showanswersfeedback = 0;
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

    $DB->delete_records('mooduell', array('id' => $id));
    $DB->delete_records('mooduell_categories', array('mooduellid' => $id));
    $DB->delete_records('mooduell_games', array('mooduellid' => $id));
    $DB->delete_records('mooduell_questions', array('mooduellid' => $id));

    return true;
}

/**
 * Serve the files from the mooduell file areas
 * @param object|null $course
 * @param object $context
 * @param string $component
 * @param string $filearea
 * @param int $qubaid
 * @param int $slot
 * @param array $args
 * @param int $forcedownload
 * @param array $options
 * @return false|void
 * @throws coding_exception
 * @throws moodle_exception
 * @throws require_login_exception
 */
function mooduell_question_pluginfile(
    object $course = null,
    object $context,
    string $component,
    string $filearea,
    int $qubaid,
    int $slot,
    array $args,
    int $forcedownload,
    array $options=array()) {

    // Make sure the filearea is one of those used by the plugin.
    if ($filearea !== 'questiontext') {
        return false;
    }

    // Make sure the user is logged in and has access to the module.
    // Plugins that are not course modules should leave out the 'cm' part.
    require_login($course, true);

    // Leave this line out if you set the itemid to null in make_pluginfile_url (set $itemid to 0 instead).
    $itemid = array_shift($args); // The first item in the $args array.

    // Use the itemid to retrieve any relevant data records and perform any security checks to see if the...
    // ... user really does have access to the file in question.

    // Extract the filename / filepath from the $args array.
    $filename = array_pop($args); // The last item in the $args array.
    if (!$args) {
        $filepath = '/';
    } else {
        $filepath = '/'.implode('/', $args).'/';
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

// The following function only applies to Moodle 3.11 and later.
if ($CFG->version >= 2021051700) {
    /**
     * Add a get_coursemodule_info function in case any MooDuell course module wants to add 'extra' information
     * for the course (see resource).
     *
     * Given a course_module object, this function returns any "extra" information that may be needed
     * when printing this activity in a course listing.  See get_array_of_activities() in course/lib.php.
     *
     * @param stdClass $coursemodule The coursemodule object (record).
     * @return cached_cm_info An object on information that the courses
     *                        will know about (most noticeably, an icon).
     */
    function mooduell_get_coursemodule_info($coursemodule) {
        global $DB;

        $dbparams = ['id' => $coursemodule->instance];
        $fields = 'id, name, intro, introformat, completiongamesplayed, completiongameswon, completionrightanswers';
        
        if (!$mooduellobj = $DB->get_record('mooduell', $dbparams, $fields)) {
            return false;
        }

        $result = new cached_cm_info();
        $result->name = $mooduellobj->name;

        if ($coursemodule->showdescription) {
            // Convert intro to html. Do not filter cached version, filters run at display time.
            $result->content = format_module_intro('mooduell', $mooduellobj, $coursemodule->id, false);
        }

        // Populate the custom completion rules as key => value pairs, but only if the completion mode is 'automatic'.
        if ($coursemodule->completion == COMPLETION_TRACKING_AUTOMATIC) {
            $completionmodes = custom_completion::get_defined_custom_rules();
            foreach ($completionmodes as $completionmode) {
                $result->customdata['customcompletionrules'][$completionmode] = $mooduellobj->{$completionmode};
            }
        }

        return $result;
    }
} else {
    // Deprecated in Moodle 3.11 and later.
    /**
     * Obtains the automatic completion state for this mooduell instance based on any conditions
     * in mooduell settings.
     *
     * @param object $course Course
     * @param object $cm Course-module
     * @param int $userid User ID
     * @param bool $type Type of comparison (or/and; can be used as return value if no conditions)
     * @return bool True if completed, false if not, $type if conditions not set.
     */
    function mooduell_get_completion_state($course, $cm, $userid, $type) {
        global $DB;

        // If completion option is enabled, evaluate it and return true/false.
        $mooduell = $DB->get_record('mooduell', array('id' => $cm->instance), '*', MUST_EXIST);

        $mooduellinstance = mooduell::get_mooduell_by_instance($cm->instance);
        $studentstatistics = $mooduellinstance->return_list_of_statistics_student();
        $completion = true;
        
        // List of completion modes and the according fields in table $studentstatistics.
        $completionmodes = mooduell_get_completion_modes();

        foreach ($completionmodes as $completionmode => $statsfield) {
            if (!empty($mooduell->{$completionmode})) {
                // Check the number of games finished required against the number of games the user has finished.
                if ($studentstatistics[$statsfield] >= $mooduell->{$completionmode}) {
                    $completion = $completion && true;
                } else {
                    $completion = false;
                }
            }
        }

        return $completion;
    }

    /**
     * Helper function to retrieve a list of all completion modes ...
     * ... and their associated field names in student statistics.
     * @return array $completionmodes
     */
    function mooduell_get_completion_modes() {
        // List of completion modes and the according fields in table $studentstatistics.
        $completionmodes = [
            'completiongamesplayed' => 'number_of_games_finished',
            'completiongameswon' => 'number_of_games_won',
            'completionrightanswers' => 'number_of_correct_answers'
        ];

        return $completionmodes;
    }
}
