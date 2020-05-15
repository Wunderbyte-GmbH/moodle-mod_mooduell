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

class question_control {

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
        // AND we retrieve the matching answersdata from $DB.
        if ($data) {
            $this->id = $data->id;
            $this->name = $data->name;
            $this->questiontext = $data->questiontext;
            $this->qtype = $data->qtype;
            $this->category = $data->category;

            if ($data->payeraanswered) {
                $this->playeraanswered = $data->playeraanswered;
            }
            if ($data->payerbanswered) {
                $this->playerbanswered = $data->playerbanswered;
            }

            $this->answers = $this->return_answers();

        }
    }

    public function return_answers() {

        global $DB;

        $answers = array();
        $answersdata = $DB->get_records('question_answers', [
                'question' => $this->id
        ]);

        if ($answersdata || count($answersdata) > 0) {
            foreach ($answersdata as $answerdata) {
                $answer = new answer_control($answerdata);
                $answers[] = $answer;
            }
        }
        return $answers;
    }

}