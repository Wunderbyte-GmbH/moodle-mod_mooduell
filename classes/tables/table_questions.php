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
 * Table SQL class for displaying MooDuell games.
 *
 * @package mod_mooduell
 * @copyright 2020 Wunderbyte Gmbh <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mooduell\tables;

use local_wunderbyte_table\wunderbyte_table;
use mod_mooduell\mooduell;
use mod_mooduell\output\list_id;
use mod_mooduell\output\list_image;
use mod_mooduell\output\list_text;
use mod_mooduell\output\list_warnings;
use mod_mooduell\output\renderer;
use mod_mooduell\question_control;
use stdClass;

/**
 * MooDuell Table sql class.
 */
class table_questions extends wunderbyte_table {

    /**
     * Parameter to store the action (what to show in the mooduell_table)
     * @var String action ('opengames'|'finishedgames'|'questions'|'highscores')
     */
    public $action;

    /**
     * @var int
     */
    public $mooduellid;

    /**
     * @var array
     */
    private $questions;

    /**
     * mooduell_table constructor
     * @param string $action
     * @param mooduell $mooduellid
     */
    public function __construct($action, mooduell $mooduellid) {

        global $PAGE;

        parent::__construct($action);

        if ($mooduellid) {
            $this->mooduellid = $mooduellid;
        }
        $this->action = $action;

        $this->define_cache('mod_mooduell', 'tablescache');
    }

    /**
     * ID column.
     * @param stdClass $question
     * @return int|string|void
     */
    public function col_id(stdClass $question) {

        global $OUTPUT;

        $mooduell = mooduell::get_instance($this->mooduellid);

        if (!$this->questions) {
            $this->questions = $mooduell->return_list_of_all_questions_in_quiz();
        }

        if (isset($this->questions[$question->id])) {
            $question = $this->questions[$question->id];
        } else {
            // If we don't find the question cached, we need to fetch them.
            // The reason is most likely that the category or questions were changed or versioned.

            $question = new question_control($question);

            $this->questions[$question->questionid] = $question;

        }
        $id = new list_id($question, $mooduell->cm->id);

        return $OUTPUT->render_from_template('mod_mooduell/list_id', $id);
    }

    /**
     * Image column.
     * @param stdClass $question
     * @return string|false|void
     */
    public function col_image(stdClass $question) {

        global $OUTPUT;

        if (isset($this->questions[$question->id])) {
            $question = $this->questions[$question->id];
        } else {
            return json_encode($question);
        }
        $image = new list_image($question);

        return $OUTPUT->render_from_template('mod_mooduell/list_image', $image);
    }

    /**
     * Text column.
     * @param stdClass $question
     * @return string|false|void
     */
    public function col_text(stdClass $question) {

        global $OUTPUT;

        if (isset($this->questions[$question->id])) {
            $question = $this->questions[$question->id];
        } else {
            return json_encode($question);
        }
        $image = new list_text($question);

        return $OUTPUT->render_from_template('mod_mooduell/list_text', $image);
    }

    /**
     * Length column.
     * @param stdClass $question
     * @return int|string|false
     */
    public function col_length(stdClass $question) {

        if (isset($this->questions[$question->id])) {
            $question = $this->questions[$question->id];
        } else {
            return json_encode($question);
        }
        $out = $question->length;
        return $out;
    }

    /**
     * Warnings column.
     * @param stdClass $question
     * @return string|false|void
     */
    public function col_warnings(stdClass $question) {

        global $OUTPUT;

        if (isset($this->questions[$question->id])) {
            $question = $this->questions[$question->id];
        } else {
            return json_encode($question);
        }
        $warnings = new list_warnings($question);

        return $OUTPUT->render_from_template('mod_mooduell/list_warnings', $warnings);
    }

    /**
     * Status column.
     * @param stdClass $question
     * @return string|false|void
     */
    public function col_status(stdClass $question) {

        if (isset($this->questions[$question->id])) {
            $question = $this->questions[$question->id];
        } else {
            return json_encode($question);
        }
        $out = $question->status;
        return $out;
    }
}
