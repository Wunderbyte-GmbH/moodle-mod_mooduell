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

defined('MOODLE_INTERNAL') || die();

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
     * @var mooduell
     */
    public $mooduell;

    /**
     * @var renderer
     */
    private $renderer;

    /**
     * @var array
     */
    private $questions;

    /**
     * mooduell_table constructor
     * @param string $action
     * @param mooduell $mooduell
     */
    public function __construct($action, mooduell $mooduell = null) {

        global $PAGE;

        parent::__construct($action);

        if ($mooduell) {
            $this->mooduell = $mooduell;
        }
        $this->action = $action;

        $this->renderer = $PAGE->get_renderer('mod_mooduell');
    }

    /**
     * ID column.
     * @param stdClass $question
     * @return int|string|void
     */
    public function col_id(stdClass $question) {

        if (!$this->questions) {
            $this->questions = $this->mooduell->return_list_of_all_questions_in_quiz();
        }

        if (isset($this->questions[$question->id])) {
            $question = $this->questions[$question->id];
        } else {
            return $question->id;
        }
        $id = new list_id($question, $this->mooduell->cm->id);

        $out = $this->renderer->render_list_id($id);
        return $out;
    }

    /**
     * Image column.
     * @param stdClass $question
     * @return string|false|void
     */
    public function col_image(stdClass $question) {

        if (isset($this->questions[$question->id])) {
            $question = $this->questions[$question->id];
        } else {
            return json_encode($question);
        }
        $image = new list_image($question);

        $out = $this->renderer->render_list_image($image);
        return $out;
    }

    /**
     * Text column.
     * @param stdClass $question
     * @return string|false|void
     */
    public function col_text(stdClass $question) {

        if (isset($this->questions[$question->id])) {
            $question = $this->questions[$question->id];
        } else {
            return json_encode($question);
        }
        $image = new list_text($question);
        $out = $this->renderer->render_list_text($image);
        return $out;
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

        if (isset($this->questions[$question->id])) {
            $question = $this->questions[$question->id];
        } else {
            return json_encode($question);
        }
        $warnings = new list_warnings($question);
        $out = $this->renderer->render_list_warnings($warnings);
        return $out;
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
