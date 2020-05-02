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
use coding_exception;
use dml_exception;
use moodle_exception;
use stdClass;
use context_module;

defined('MOODLE_INTERNAL') || die();

/**
 * Class mooduell
 *
 * @package mod_mooduell
 */
class mooduell {

    /**
     * @var stdClass|null fieldset record of mooduell instance
     */
    public $settings = null;

    /**
     * @var bool|false|mixed|stdClass|null course object
     */
    public $course = null;

    /**
     * @var stdClass|null course module
     */
    public $cm = null;

    /**
     * @var stdClass|null context
     */
    public $context = null;

    /**
     * Mooduell constructor. Fetches MooDuell settings from DB.
     *
     * @param int $id course module id
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function __construct(int $id = 0) {
        global $DB;

        if (!$this->cm = get_coursemodule_from_id('mooduell', $id)) {
            throw new moodle_exception('invalidcoursemodule ' . $id, 'mooduell', null, null,
                "Course module id: $id");
        }

        $this->course = get_course($this->cm->course);

        if (!$this->settings = $DB->get_record('mooduell', array('id' => $this->cm->instance))) {
            throw new moodle_exception('invalidmooduell', 'mooduell', null, null,
                "Mooduell id: {$this->cm->instance}");
        }
        $this->context = context_module::instance($this->cm->id);
    }

    /**
     * Get MooDuell object by instanceid (id of mooduell table)
     *
     * @param $instanceid
     * @return mooduell
     * @throws coding_exception
     */
    public static function get_mooduell_by_instance(int $instanceid) {
        $cm = get_coursemodule_from_instance('mooduell', $instanceid);
        return new mooduell($cm->id);
    }

    /**
     * Get the html of the view page.
     *
     * @param bool $inline Display without header and footer?
     * @return string
     */
    public function display(bool $inline = false) {
        global $OUTPUT;
        $out = '';
        if (!$inline) {
            $out .= $OUTPUT->header();
        }

        // TODO: Replace with content.
        $out .= "This is the content";

        if (!$inline) {
            $out .= $OUTPUT->footer();
        }
        return $out;
    }

    /**
     * Set base params for page and trigger module viewed event.
     *
     * @throws coding_exception
     */
    public function setup_page(){
        global $PAGE;
        $event = event\course_module_viewed::create(array(
            'objectid' => $this->cm->instance,
            'context' => $this->context
        ));
        $event->add_record_snapshot('course', $this->course);
        $event->add_record_snapshot('mooduell', $this->settings);
        $event->trigger();

        $PAGE->set_url('/mod/mooduell/view.php', array('id' => $this->cm->id));
        $PAGE->set_title(format_string($this->settings->name));
        $PAGE->set_heading(format_string($this->course->fullname));
        $PAGE->set_context($this->context);
    }

    /**
     * Create a mooduell instance.
     *
     * @param stdClass $formdata
     * @param \mod_mooduell_mod_form $mform
     * @return bool|int
     * @throws dml_exception
     */
    public static function add_instance(stdClass $formdata) {
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
        $data->countdown = $formdata->countdown;
        $data->usefullnames = $formdata->usefullnames;
        $data->showcontinuebutton = $formdata->showcontinuebutton;
        $data->showcorrectanswer = $formdata->showcorrectanswer;
        $data->quizid = (!empty($formdata->quizid) && $formdata->quizid > 0 ) ? $formdata->quizid : null;

        return $DB->insert_record('mooduell', $data);
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
