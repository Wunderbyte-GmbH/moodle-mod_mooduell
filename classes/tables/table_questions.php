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
     * mooduell_table constructor
     * @param mooduell $mooduell
     */
    public function __construct($action, mooduell $mooduell = null) {

        global $PAGE;

        parent::__construct($action);

        if ($mooduell) {
            $this->mooduell = $mooduell;
        }
        $this->action = $action;
    }


    public function col_id(stdClass $question) {

        global $PAGE;

        // We can call this fucntion because it caches all the questions and is very fast after the first call.
        $questions = $this->mooduell->return_list_of_all_questions_in_quiz();

        $question = $questions[$question->id];

        $renderer = $PAGE->get_renderer('mod_mooduell');

        $id = new list_id($question, $this->mooduell->cm->id);

        $out = $renderer->render_list_id($id);

        return $out;
    }



    public function col_image(stdClass $question) {

        global $PAGE;

        // We can call this fucntion because it caches all the questions and is very fast after the first call.
        $questions = $this->mooduell->return_list_of_all_questions_in_quiz();

        $question = $questions[$question->id];

        $renderer = $PAGE->get_renderer('mod_mooduell');

        $image = new list_image($question);

        return $renderer->render_list_image($image);
    }

    public function col_text(stdClass $question) {

        global $PAGE;

        $questions = $this->mooduell->return_list_of_all_questions_in_quiz();

        $question = $questions[$question->id];

        $renderer = $PAGE->get_renderer('mod_mooduell');

        $image = new list_text($question);

        return $renderer->render_list_text($image);
    }

    public function col_length(stdClass $question) {

        global $PAGE;

        $questions = $this->mooduell->return_list_of_all_questions_in_quiz();

        $question = $questions[$question->id];

        return $question->length;
    }

    public function col_warnings(stdClass $question) {

        global $PAGE;

        $questions = $this->mooduell->return_list_of_all_questions_in_quiz();

        $question = $questions[$question->id];

        $renderer = $PAGE->get_renderer('mod_mooduell');

        $image = new list_warnings($question);

        return $renderer->render_list_warnings($image);
    }

    public function col_status(stdClass $question) {

        global $PAGE;

        $questions = $this->mooduell->return_list_of_all_questions_in_quiz();

        $question = $questions[$question->id];

        return $question->status;;
    }

}
