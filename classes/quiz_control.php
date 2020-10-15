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
namespace mod_mooduell;

defined('MOODLE_INTERNAL') || die();

use backup;
use base_plan_exception;
use base_setting_exception;
use core\event\course_module_created;
use file_exception;
use restore_controller;
use restore_controller_exception;
use stored_file_creation_exception;

require_once("{$CFG->libdir}/filelib.php");
require_once("{$CFG->dirroot}/mod/quiz/mod_form.php");
require_once("{$CFG->dirroot}/course/modlib.php");
require_once("{$CFG->dirroot}/backup/util/includes/restore_includes.php");

class quiz_control {

    /**
     *
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
    public function is_quiz_configured() {
        if ($this->mooduell->settings->quizid == 0 or is_null($this->mooduell->settings->quizid)) {
            $quizid = 0;
        } else {
            $quizid = $this->mooduell->settings->quizid;
        }
        return $quizid;
    }

    /**
     * Check the settings of the quiz.
     * When quiz is OK, then return an empty array else return array with problems.
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
     * @throws base_plan_exception
     * @throws base_setting_exception
     * @throws file_exception
     * @throws restore_controller_exception
     * @throws stored_file_creation_exception
     */
    public function import_demo_quiz() {
        global $CFG, $USER, $DB;

        $backupfile = $CFG->dirroot . '/mod/mooduell/files/demoquiz.mbz';
        $tmpid = restore_controller::get_tempdir_name($this->mooduell->course->id, $USER->id);
        $filepath = make_backup_temp_directory($tmpid);
        if (!check_dir_exists($filepath, true, true)) {
            throw new restore_controller_exception('cannot_create_backup_temp_dir');
        }
        $fp = get_file_packer('application/vnd.moodle.backup');
        $fp->extract_to_pathname($backupfile, $filepath);

        $rc = new restore_controller($tmpid, $this->mooduell->course->id, backup::INTERACTIVE_NO, backup::MODE_IMPORT, $USER->id,
                backup::TARGET_CURRENT_ADDING);

        // Make sure that the restore_general_groups setting is always enabled when duplicating an activity.
        $plan = $rc->get_plan();
        $groupsetting = $plan->get_setting('groups');
        if (empty($groupsetting->get_value())) {
            $groupsetting->set_value(true);
        }

        if (!$rc->execute_precheck()) {
            $precheckresults = $rc->get_precheck_results();
            if (is_array($precheckresults) && !empty($precheckresults['errors'])) {
                if (empty($CFG->keeptempdirectoriesonbackup)) {
                    fulldelete($filepath);
                }
            }
        }

        $rc->execute_plan();
        $newcmid = null;
        $tasks = $rc->get_plan()->get_tasks();
        foreach ($tasks as $task) {
            if (is_subclass_of($task, 'restore_activity_task')) {
                $newcmid = $task->get_moduleid();
                break;
            }
        }

        $rc->destroy();

        if (empty($CFG->keeptempdirectoriesonbackup)) {
            fulldelete($filepath);
        }

        if ($newcmid) {
            $section = $DB->get_record('course_sections', array(
                    'id' => $this->mooduell->cm->section,
                    'course' => $this->mooduell->cm->course
            ));
            $modarray = explode(",", trim($section->sequence));
            $cmindex = array_search($this->mooduell->cm->id, $modarray);
            if ($cmindex !== false && $cmindex < count($modarray) - 1) {
                $newcm = get_coursemodule_from_id('quiz', $newcmid, $this->mooduell->cm->course);
                moveto_module($newcm, $section, $modarray[$cmindex + 1]);
            }

            // Trigger course module created event. We can trigger the event only if we know the newcmid.
            $newcm = get_fast_modinfo($this->mooduell->cm->course)->get_cm($newcmid);
            $event = course_module_created::create_from_cm($newcm);
            $event->trigger();
        }
        return $newcm;
    }
}