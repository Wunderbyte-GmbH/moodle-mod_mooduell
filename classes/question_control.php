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

use dml_exception;

defined('MOODLE_INTERNAL') || die();

class question_control {

    /**
     *
     * @var int
     */
    public $questionid;

    /**
     *
     * @var int
     */
    public $id;

    /**
     *
     * @var string
     */
    public $name;

    /**
     *
     * @var string
     */
    public $questiontext;

    /**
     *
     * @var string
     */
    public $qtype;

    /**
     *
     * @var int
     */
    public $category;

    /**
     *
     * @var int answered (null=no, 1 = falsely, 2 = correctly)
     */
    public $playeraanswered;

    /**
     *
     * @var int answered (null=no, 1 = falsely, 2 = correctly)
     */
    public $playerbanswered;

    /**
     *
     * @var array of answer_control class
     */
    public $answers;

    /**
     * question_control constructor.
     *
     * @param mooduell $mooduell
     */

    public function __construct($data = null) {

        // if we have $data, we automatically create all the relevant values for this question ...
        if ($data) {
            $this->questionid = $data->id;
            $this->name = $data->name;
            $this->questiontext = $data->questiontext;
            $this->qtype = $data->qtype;
            $this->category = $data->category;

            // Normally we don't have this information, we use retrieve_result to retrieve it.
            if ($data->payeraanswered) {
                $this->playeraanswered = $data->playeraanswered;
            }
            if ($data->payerbanswered) {
                $this->playerbanswered = $data->playerbanswered;
            }

            $this->answers = $this->return_answers();
        }
    }

    /**
     * Return array of answers of a given question
     *
     * @return array
     * @throws dml_exception
     */
    public function return_answers() {

        global $DB;

        $answers = array();
        $answersdata = $DB->get_records('question_answers', [
                'question' => $this->questionid
        ]);

        if ($answersdata || count($answersdata) > 0) {
            foreach ($answersdata as $answerdata) {
                $answer = new answer_control($answerdata);
                $answers[] = $answer;
            }
        }
        return $answers;
    }

    /**
     * We fetch the result of the question from DB and add it to this instance.
     *
     * @param $gameid
     * @throws dml_exception
     */
    public function get_results($gameid) {

        global $DB;

        if ($this->questionid) {
            $question = $DB->get_record('mooduell_questions', ['gameid' => $gameid, 'questionid' => $this->questionid]);

            $this->playeraanswered = $question->playeraanswered;
            $this->playerbanswered = $question->playerbanswered;
        }
    }

}