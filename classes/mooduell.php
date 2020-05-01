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
 * Plugin event observers are registered here.
 *
 * @package     mod_mooduell
 * @copyright   2020 Georg Maißer <georg.maisser@wunderbyte.at>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace mod_mooduell;

use backup;
use backup_controller;
use course_request;
use moodle_exception;
use restore_controller;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->dirroot/course/lib.php");
require_once("$CFG->dirroot/course/modlib.php");
require_once("$CFG->dirroot/mod/quiz/mod_form.php");

require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
require_once($CFG->libdir . '/filelib.php');


/**
 * Class mooduell
 *
 * @package mod_mooduell
 */
class mooduell
{


    /**
     * @var stdClass fieldset record of mooduell instance
     */
    public $data = null;
    public $quizid = null;

    public $course = null;
    public $cm = null;
    public $context = null;


    protected $_currentview = null;

    public function __construct($quizid, $course, $cm, $context)
    {

        global $DB;

        //we check if wee have a record
        if (!$this->data = $DB->get_records('mooduell', null, '', 'id, usefullnames')) {
            throw new moodle_exception(
                'invalidmooduell',
                'mooduell',
                null,
                null,
                "mooduell id:"
            );
        }

        $this->course = $course;
        $this->cm = $cm;
        $this->context = $context;


        //with no quizid, we create the demo quiz right away
        if (!$quizid) {
            $this->import_demo_quiz($course, $cm->section);
        }



        //print_r($this->data);
        return $this->data;
    }


    /**
     * 
     * 
     */
    public function import_demo_quiz($course, $section)
    {
        global $CFG;
        global $USER;

        //copy backup to file https://docs.moodle.org/dev/File_API
        //$folder = XX; // as found in: $CFG->dataroot . '/temp/backup/'



        $from_zip_file = $CFG->dirroot . '/mod/mooduell/files/backup_mooduell_demo_quiz.mbz';

        $backuptempdir = $CFG->backuptempdir . '/';
        
        $fs = get_file_storage();
        $file_record = array(
            'contextid' => $this->context->id, 'component' => 'quiz', 'filearea' => 'backup',
            'itemid' => 0, 'filepath' => $backuptempdir, 'filename' => "backup_mooduell_demo_quiz.mbz",
            'timecreated' => time(), 'timemodified' => time()
        );
        
        $file = $fs->create_file_from_pathname($file_record, $from_zip_file);

        
        
        $fileexists = file_exists($file->get_filepath());

        $rc = new restore_controller("backup_mooduell_demo_quiz.mbz", $this->course->id, backup::INTERACTIVE_NO, backup::MODE_IMPORT, $USER->id,backup::TARGET_CURRENT_ADDING);
        //$rc = new restore_controller($backupid, $course->id,
        //backup::INTERACTIVE_NO, backup::MODE_IMPORT, $USER->id, backup::TARGET_CURRENT_ADDING);

        // Make sure that the restore_general_groups setting is always enabled when duplicating an activity.
        $plan = $rc->get_plan();
        $groupsetting = $plan->get_setting('groups');
        if (empty($groupsetting->get_value())) {
            $groupsetting->set_value(true);
        }

        $cmcontext = $this->context;
        if (!$rc->execute_precheck()) {
            $precheckresults = $rc->get_precheck_results();
            if (is_array($precheckresults) && !empty($precheckresults['errors'])) {
                if (empty($CFG->keeptempdirectoriesonbackup)) {
                    fulldelete($backupbasepath);
                }
            }
        }

        $rc->execute_plan();





        // This is the way to create a new quiz
        // list($module, $context, $cw, $cm, $data) = prepare_new_moduleinfo_data($course, "quiz", $section);

        // $mformclassname = 'mod_' . $module->name . '_mod_form';
        // $mform = new $mformclassname($data, $cw->section, $cm, $course);

        // $mform->set_data($data);

        // add_moduleinfo($data, $course, $mform);

        //add_moduleinfo()


        return "moduleinfo <br>";
    }

    public function display()
    {

        if (!$this->quizid) {






            return "we had not quiz Id, we created a new one";
        } else {
            return "quizid is $this->quizid";
        }
    }
}






// For more information about the Events API, please visit:
// https://docs.moodle.org/dev/Event_2


/* roadmap 

We have the following choice:

A) ignore Moodle attempts and reports and only work with our own data
B) Use the Moodle attempts structure fully to be able to log valid records to the moodle plattform

No problems with approach A)

Following problems with approach B)
- 1.) we would have to override the way a new attempt creates the entries in the mdl_question_attempts DB (see locallib.php -> quiz_start_new_attempt)
- 2.) We would need to allow multiple open attempts at the same time. Not sure if this is possible, but it might.








We challenge a user:

- mod_mooduell_start_new_game (usertoken, quizID, playerBid)
- trigger mod_quiz_external -> start_attempt   => if error
then => mod_quiz_external -> get_user_attempts
-> we got attempt id
- We save game to our DB


We are challenged by user:

- we don't fetch  our own attempt, but the attempt of the first user by userid (program override to allow this):
-> mod_quiz_external -> get_user_attempts
- fetch our questions by attempt id mod_quiz_external ->  get_attempt_data (usertoken, attemptid, pagenr)





*/
