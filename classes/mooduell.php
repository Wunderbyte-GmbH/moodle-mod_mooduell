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
 * @copyright   2020 Wunderbyte Gmbh <info@wunderbyte.at>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace mod_mooduell;
use coding_exception;
use dml_exception;
use moodle_exception;
use stdClass;
use context_module;
use mod_mooduell\mooduell_form;

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
     * Get the html of the view page.
     *
     * @param bool $inline Display without header and footer?
     * @return string
     */
    public function display(bool $inline = false) {
        global $OUTPUT;


        $mform = new mooduell_form();

        $out = '';
        if (!$inline) {
            $out .= $OUTPUT->header();
        }

        $id = $this->cm->id;

        // TODO: Replace with content.
        //$out .= "This is the content $id";

        $out .= $mform->render();
        
        
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



    public static function update_categories($mooduellid, $formdata) {

        global $DB;

        //write categories to categories table

        if (isset($formdata->categoriesgroup)) {
            foreach ($formdata->categoriesgroup as $category) {

                $data = new stdClass();
                $data->mooduellid = $mooduellid;
                $data->category = $category;
                $data->weight = 100;
    
                $DB->insert_record('mooduell_categories', $data);
    
            }
        }
        
        return null;
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
        $data->usefullnames = isset($formdata->usefullnames) ? $formdata->usefullnames : 0;
        $data->showcontinuebutton = isset($formdata->showcontinuebutton) ? $formdata->showcontinuebutton : 0;
        $data->showcorrectanswer = isset($formdata->showcorrectanswer) ? $formdata->showcorrectanswer : 0;
        $data->quizid = (!empty($formdata->quizid) && $formdata->quizid > 0 ) ? $formdata->quizid : null;

        $mooduellid = $DB->insert_record('mooduell', $data);

        //add postprocess function

        //$mooduell = self::get_mooduell_by_instance($mooduellid);
        self::update_categories($mooduellid, $formdata);

        return $mooduellid;

    }
}