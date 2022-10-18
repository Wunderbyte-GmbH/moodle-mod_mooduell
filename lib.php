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
use mod_mooduell\completion\completion_utils;

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
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        default:
            return null;
    }
}

/**
 * Saves a new instance of mod_mooduell into the database.
 *
 * Given an object containing all the necessary data, (defined by the form
 * in mod_form.php) this function will create a new instance and return the id
 * number of the instance.
 *
 * @param stdClass $formdata
 * @param mod_mooduell_mod_form|null $mform
 * @return bool|int
 * @throws dml_exception
 */
function mooduell_add_instance(stdClass $formdata, mod_mooduell_mod_form $mform = null) {
    global $DB;
    // Add the database record.
    $data = new stdClass();
    $data->name = $formdata->name;
    $data->timemodified = time();
    $data->timecreated = time();
    $data->course = $formdata->course;
    $data->courseid = $formdata->course;
    $data->intro = $formdata->intro;
    $data->introformat = $formdata->introformat;
    $data->countdown = isset($formdata->countdown) ? $formdata->countdown : 0;
    $data->waitfornextquestion = isset($formdata->waitfornextquestion) ? $formdata->waitfornextquestion : 0;
    $data->usefullnames = isset($formdata->usefullnames) ? $formdata->usefullnames : 0;
    $data->showcontinuebutton = isset($formdata->showcontinuebutton) ? $formdata->showcontinuebutton : 0;
    $data->showcorrectanswer = isset($formdata->showcorrectanswer) ? $formdata->showcorrectanswer : 0;
    $data->showgeneralfeedback = isset($formdata->showgeneralfeedback) ? $formdata->showgeneralfeedback : 0;
    $data->showanswersfeedback = isset($formdata->showanswersfeedback) ? $formdata->showanswersfeedback : 0;

    $data->quizid = (!empty($formdata->quizid) && $formdata->quizid > 0) ? $formdata->quizid : null;

    $mooduellid = $DB->insert_record('mooduell', $data);

    // Update MooDuell categories.
    mooduell_update_categories($mooduellid, $formdata);

    // Update MooDuell challenges.
    mooduell_update_challenges($mooduellid, $formdata);

    return $mooduellid;
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

    // Update MooDuell categories.
    mooduell_update_categories($moduleinstance->id, (object) $mform->get_data());

    // Update MooDuell challenges.
    mooduell_update_challenges($moduleinstance->id, (object) $mform->get_data());

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

    // We need to trigger the delete cache event in the observer:
    mod_mooduell_observer::delete_cache();

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
    $DB->delete_records('mooduell_challenges', array('mooduellid' => $id));
    $DB->delete_records('mooduell_games', array('mooduellid' => $id));
    $DB->delete_records('mooduell_questions', array('mooduellid' => $id));

    return true;
}

/**
 * Function is called on creating or updating MooDuell Quiz Settings.
 * One Quiz can have one or more categories-entries.
 * This function has to make sure creating and updating results in the correct DB entries.
 * @param int $mooduellid
 * @param object $formdata
 * @return void|null
 * @throws dml_exception
 */
function mooduell_update_categories(int $mooduellid, object $formdata) {
    global $DB;

    $categoriesarray = [];

    $counter = 0;
    $groupname = 'categoriesgroup' . $counter;

    while (isset($formdata->$groupname)) {

        $entry = new stdClass();
        $newrecord = (object) $formdata->$groupname;
        $entry->category = $newrecord->category;
        $entry->weight = $newrecord->weight;
        $categoriesarray[] = $entry;

        $counter++;
        $checkboxname = "addanothercategory" . $counter;
        $groupname = 'categoriesgroup' . $counter;
        if (!isset($formdata->$checkboxname)) {
            break;
        }
    }

    // Write categories to categories table.
    if (count($categoriesarray) > 0) {

        // First we have to check if we have any category entry for our Mooduell Id.
        $foundrecords = $DB->get_records('mooduell_categories', ['mooduellid' => $mooduellid]);
        $newrecords = $categoriesarray;

        // If there is no categoriesgroup in Formdata at all, we abort.
        if (!$newrecords || count($newrecords) == 0) {
            return;
        }

        // Else we determine if we have more new or old records and set $i accordingly.
        $max = count($foundrecords) >= count($newrecords) ? count($foundrecords) : count($newrecords);
        $i = 0;

        while ($i < $max) {

            $foundrecord = count($foundrecords) > 0 ? array_pop($foundrecords) : null;
            $newrecord = count($newrecords) > 0 ? array_pop($newrecords) : null;

            // If we have still a foundrecord left, we update it.
            if ($foundrecord && $newrecord) {
                $data = new stdClass();
                $data->id = $foundrecord->id;
                $data->mooduellid = $mooduellid;
                $data->category = $newrecord->category;
                $data->weight = $newrecord->weight;
                $DB->update_record('mooduell_categories', $data);
            } else if ($foundrecord) {
                // Else we have more foundrecords than new recors, we delete the found ones.
                $DB->delete_records('mooduell_categories', array('id' => $foundrecord->id));
            } else {
                $data = new stdClass();
                $data->mooduellid = $mooduellid;
                $data->category = $newrecord->category;
                $data->weight = $newrecord->weight;
                $DB->insert_record('mooduell_categories', $data);
            }
            $i++;
        }
    }

    return null;
}

/**
 * Function is called on creating or updating MooDuell settings.
 * Challenges are stored in a separate table: mooduell_challenges.
 * @param int $mooduellid
 * @param object $formdata
 * @return void|null
 * @throws dml_exception
 */
function mooduell_update_challenges(int $mooduellid, object $formdata) {
    global $DB;

    // List of completion modes and the according fields in table $studentstatistics.
    $completionmodes = completion_utils::mooduell_get_completion_modes();

    foreach ($completionmodes as $mode => $value) {

        $challengeobj = new stdClass;
        $challengeobj->mooduellid = $mooduellid;
        $challengeobj->challengetype = $mode;

        // If checkbox for completion mode is set...
        if (!empty($formdata->{$mode . 'enabled'}) && !empty($formdata->{$mode})) {
            $challengeobj->targetnumber = $formdata->{$mode};

            if (!empty($formdata->{$mode . 'name'})) {
                $challengeobj->challengename = $formdata->{$mode . 'name'};
            } else {
                // Use default name, if no name has been set.
                $challengeobj->challengename = get_string('challengename:' . $mode, 'mooduell');
            }
        } else {
            // Else we want to make sure there is no entry in DB anymore.
            $DB->delete_records('mooduell_challenges', ['mooduellid' => $mooduellid, 'challengetype' => $mode]);
            // We do not need to do anything else in this case, so continue with next mode.
            continue;
        }

        // If a record for this quiz and mode already exists...
        if ($existingrecord = $DB->get_record('mooduell_challenges', ['mooduellid' => $mooduellid, 'challengetype' => $mode])) {
            // ... update existing record in mooduell_challenges.
            $challengeobj->id = $existingrecord->id;
            $DB->update_record('mooduell_challenges', $challengeobj);
        } else {
            // If no record exists yet, then insert new record into mooduell_challenges.
            $DB->insert_record('mooduell_challenges', $challengeobj);
        }
    }

    return null;
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
    array $options = array()
) {

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
        $filepath = '/' . implode('/', $args) . '/';
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

        $mooduellid = $coursemodule->instance;

        $dbparams = ['id' => $mooduellid];
        $fields = 'id, name, intro, introformat';

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
                // Get the target number of each completion mode.
                if ($targetnumber = $DB->get_field(
                    'mooduell_challenges',
                    'targetnumber',
                    ['mooduellid' => $mooduellid, 'challengetype' => $completionmode]
                )) {
                    $result->customdata['customcompletionrules'][$completionmode] = $targetnumber;
                }
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
        global $DB, $USER;

        // If completion option is enabled, evaluate it and return true/false.
        $mooduellid = $cm->instance;

        $mooduellinstance = mooduell::get_mooduell_by_instance($mooduellid);
        $studentstatistics = $mooduellinstance->return_list_of_statistics_student();
        $completion = true;

        // List of completion modes and the according fields in table $studentstatistics.
        $completionmodes = completion_utils::mooduell_get_completion_modes();

        foreach ($completionmodes as $completionmode => $statsfield) {

            if ($challenge = $DB->get_record('mooduell_challenges', [
                'mooduellid' => $mooduellid,
                'challengetype' => $completionmode]
            )) {

                // If the challenge is already expired take the result value from the challenge results table.
                if ($cm->completion == 2 && $cm->completionexpected != 0 && time() > $cm->completionexpected) {
                    if (!$actualnumber = (int) $DB->get_field('mooduell_challenge_results', 'result', [
                        'mooduellid' => $mooduellid,
                        'challengeid' => $challenge->id,
                        'userid' => $USER->id
                    ])) {
                        // Error prevention.
                        $actualnumber = 0;
                    }
                } else {
                    // Else retrieve the actual number from the statistics.
                    $actualnumber = (int) $studentstatistics[$statsfield];
                }

                // Check the actual number against the target number.
                if ($actualnumber >= $challenge->targetnumber) {
                    $completion = $completion && true;
                } else {
                    $completion = false;
                }
            }
        }

        return $completion;
    }
}
