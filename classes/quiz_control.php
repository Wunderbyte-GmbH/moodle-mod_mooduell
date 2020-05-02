<?php
// This file is part of mod_datalynx for Moodle - http://moodle.org/
//
// It is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// It is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace mod_mooduell;
use backup;
use backup_controller;
use restore_controller;

require_once("{$CFG->libdir}/filelib.php");
require_once("{$CFG->dirroot}/mod/quiz/mod_form.php");
require_once("{$CFG->dirroot}/course/modlib.php");

class quiz_control {

    /**
     * @var mooduell MooDuell instance
     */
    public $mooduell;

    /**
     * quiz_control constructor.
     *
     * @param mooduell $mooduell
     */
    public function __construct(mooduell $mooduell) {
        $this->mooduell = $mooduell;
    }

    /**
     * Checks if quiz is already configured.
     *
     * @return integer quizid or 0 when no quizid is set
     */
    public function is_quiz_configured(){
        if ($this->mooduell->settings->quizid == 0 OR is_null($this->mooduell->settings->quizid)){
            $quizid = 0;
        } else {
            $quizid = $this->mooduell->settings->quizid;
        }
        return $quizid;
    }

    /**
     * Check the settings of the quiz. When quiz is OK, then return an empty array else return array with problems.
     *
     * @return array of problems indexed by setting name
     */
    public function check_quiz_settings() {
        $problems = [];
        return $problems;
    }

    /**
     * Creates a quiz instance in the same course of the mooduell instance using a quiz activity backup file.
     *
     * @return string
     * @throws \base_plan_exception
     * @throws \base_setting_exception
     * @throws \file_exception
     * @throws \restore_controller_exception
     * @throws \stored_file_creation_exception
     */
    public function import_demo_quiz() {
        global $CFG, $USER;
        //copy backup to file https://docs.moodle.org/dev/File_API
        //$folder = XX; // as found in: $CFG->dataroot . '/temp/backup/'

        $from_zip_file = $CFG->dirroot . '/mod/mooduell/files/backup_mooduell_demo_quiz.mbz';

        $backuptempdir = $CFG->backuptempdir . '/';

        $fs = get_file_storage();
        $file_record = array(
            'contextid' => $this->context->id, 'component' => 'mooduell', 'filearea' => 'backup',
            'itemid' => 0, 'filepath' => $backuptempdir, 'filename' => "backup_mooduell_demo_quiz.mbz",
            'timecreated' => time(), 'timemodified' => time()
        );

        $file = $fs->create_file_from_pathname($file_record, $from_zip_file);

        $fileexists = file_exists($file->get_filepath());

        $rc = new restore_controller("backup_mooduell_demo_quiz.mbz", $this->course->id, backup::INTERACTIVE_NO,
            backup::MODE_IMPORT, $USER->id, backup::TARGET_CURRENT_ADDING);
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
            $prequizcontrolcheckresults = $rc->get_precheck_results();
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
}