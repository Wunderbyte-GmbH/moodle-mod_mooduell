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
 * Class question_control for mod_mooduell.
 *
 * @package mod_mooduell
 * @copyright 2021 Wunderbyte GmbH <georg.maisser@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mooduell;

use coding_exception;
use dml_exception;

defined('MOODLE_INTERNAL') || die();

/**
 * Class question_control for mod_mooduell.
 *
 * @package mod_mooduell
 */
class question_control {

    /**
     *
     * @var int
     */
    public $questionid;

    /**
     *
     * @var string
     */
    public $name;

    /**
     *
     * @var string
     */
    public $imageurl;

    /**
     *
     * @var string
     */
    public $imagetext;

    /**
     *
     * @var string
     */
    public $questiontext = '';

    /**
     *
     * @var string
     */
    public $questiontype;

    /**
     *
     * @var string
     */
    public $questiontextformat;

    /**
     *
     * @var int
     */
    public $category;

    /**
     *
     * @var string
     */
    public $categoryname;

    /**
     *
     * @var int
     */
    public $courseid;

    /**
     * Context id of the question depends on the category of the question and is fetched upon construction.
     * @var int
     */
    public $contextid;

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
     * @var int
     */
    public $length;

    /**
     *
     * @var int
     */
    public $status;

    /**
     *
     * @var array of answer_control class
     */
    public $answers;

    /**
     * @var array
     */
    public $warnings = [];

    /**
     * question_control constructor.
     * @param null $data
     * @param null $listofanswers
     * @throws dml_exception
     */
    public function __construct($data = null, $listofanswers = null) {
        // If we have $data, we automatically create all the relevant values for this question...
        global $COURSE, $DB;

        if ($data) {
            $this->questionid = $data->id;
            $this->name = $data->name;
            $this->questiontext = $data->questiontext;
            $this->questiontextformat = $data->questiontextformat;
            $this->questiontype = $data->qtype;
            $this->category = $data->category;
            $this->categoryname = isset($data->categoryname) ? $data->categoryname : null;
            $this->courseid = $COURSE->id;

            // TODO: Delete, only for debugging.
            if ($this->questiontype == 'ddwtos') {
                echo 'found it';
            }

            // We need the context id, but it might be there already.
            $this->contextid = $data->contextid ?? $DB->get_field('question_categories',
                    'contextid', array('id' => $this->category));

            // Normally we don't have this information, we use retrieve_result to retrieve it.
            if (isset($data->playeraanswered)) {
                $this->playeraanswered = $data->playeraanswered;
            }
            if (isset($data->playerbanswered)) {
                $this->playerbanswered = $data->playerbanswered;
            }

            $this->extract_image();

            $this->answers = $this->return_answers($listofanswers);

            $this->check_question();
        }
    }

    /**
     * Return array of answers of a given question.
     * @param null $listofanswers
     * @return array
     * @throws dml_exception
     */
    public function return_answers($listofanswers = null): array {
        global $DB;

        if (!$listofanswers || count($listofanswers) === 0) {
            $listofanswers = $DB->get_records('question_answers', [
                    'question' => $this->questionid
            ]);
        }

        $answers = array();
        if ($listofanswers && count($listofanswers) > 0) {
            foreach ($listofanswers as $k => $val) {
                if ($val->question == $this->questionid) {
                    $answer = new answer_control($val);
                    $answers[] = $answer;
                    unset($listofanswers[$k]);
                }
            }
        }

        return $answers;
    }

    /**
     * We fetch the result of the question from DB and add it to this instance.
     *
     * @param int $gameid
     * @throws dml_exception
     */
    public function get_results(int $gameid) {
        global $DB;
        if ($this->questionid) {
            $question = $DB->get_record('mooduell_questions', ['gameid' => $gameid, 'questionid' => $this->questionid]);

            $this->playeraanswered = $question->playeraanswered;
            $this->playerbanswered = $question->playerbanswered;
        }
    }

    /**
     * See if answer was correct.
     * @param array $answerids
     * @param int $showcorrectanswer
     * @return array An array of results.
     * @throws dml_exception
     */
    public function validate_question(array $answerids, int $showcorrectanswer): array {

        switch ($this->questiontype) {
            case 'numerical':
                list($resultarray, $iscorrect) = $this->validate_numerical_question($answerids);
                break;
            case 'singlechoice':
            case 'multichoice':
            case 'truefalse':
                list($resultarray, $iscorrect) =
                    $this->validate_single_multichoice_truefalse_question($answerids, $showcorrectanswer);
                break;
            default:
                $resultarray = [];
                $iscorrect = 0;
        }

        return [$resultarray, $iscorrect];
    }

    /**
     * Private function to validate numerical questions.
     * @param array $answerids
     * @return array An array of results.
     * @throws dml_exception
     */
    private function validate_numerical_question(array $answerids): array {
        global $DB;

        $resultarray = [];
        $iscorrect = 0; // Incorrect on initialization.
        $answergiven = (float) $answerids[0]; // Given answer will always be first value of answerids array.

        // Add all correct answers to the array of correct answers.
        // With numerical questions we have only one correct answer in most (but not all) cases.
        foreach ($this->answers as $answer) {
            if ($answer->correct) {
                $resultarray[] = (float) $answer->answertext;
            }
        }

        // Now loop again through all correct answers and check...
        // ... if the given answer is within the tolerance of one of them.
        foreach ($this->answers as $answer) {
            if ($answer->correct) {
                $tolerance = $DB->get_field('question_numerical', 'tolerance',
                    ['question' => $this->questionid, 'answer' => $answer->id]);

                $min = $answer->answertext - $tolerance;
                $max = $answer->answertext + $tolerance;
                if ($min <= $answergiven && $answergiven <= $max) {
                    $iscorrect = 1;
                    break;
                }
            }
        }

        return [$resultarray, $iscorrect];
    }

    /**
     * Private function to validate single choice, multiple choice and true/false questions.
     * @param array $answerids
     * @param int $showcorrectanswer
     * @return array An array of results.
     */
    private function validate_single_multichoice_truefalse_question(array $answerids, int $showcorrectanswer): array {

        $resultarray = [];
        $iscorrect = 1; // True on initialization.

        // Loop through all answers.
        foreach ($this->answers as $answer) {
            if ($answer->fraction > 0) {
                // Build array of correct answers.
                $resultarray[] = $answer->id;

                // It's a correct answer, so if it's not among the given answers, we have to mark iscorrect with 0.
                if (!in_array($answer->id, $answerids)) {
                    $iscorrect = 0;
                }
            } else {
                // It's a wrong answer, so if it's among the given answers, we have to mark iscorrect with 0.
                if (in_array($answer->id, $answerids)) {
                    $iscorrect = 0;
                }
            }
        }

        // If setting to show correct answers is turned off, clear the result array.
        if (!$showcorrectanswer) $resultarray = array();

        return [$resultarray, $iscorrect];
    }

    /**
     * Add warnings for every problem and set status accordingly.
     * @throws coding_exception
     */
    private function check_question() {
        // Check for correct number of answers and set status and qtype accordingly.
        $this->check_for_right_number_of_answers();

        // Check for the correct Typ of the question. Should be called AFTER right number of answers.
        $this->check_for_right_type_of_question();

        // Check for the right questiontext length.
        $this->check_for_right_length_of_questiontext();

        if (count($this->warnings) == 0) {
            $this->status = get_string('ok', 'mod_mooduell');
        }
    }

    /**
     * Stores the image parameters in the question_class.
     * @throws coding_exception
     * @throws dml_exception
     */
    private function extract_image() {

        if (strpos($this->questiontext, '<img src') === false) {
            $this->length = strlen($this->questiontext);
            return;
        }

        global $PAGE;
        global $DB;
        global $CFG;

        require_once($CFG->libdir . '/filelib.php');

        $context = \context_course::instance($this->courseid);

        $course = get_course($this->courseid);

        // Retrieve the question usage id from Db.
        // If it's not there before, it's introduced to the dB in game_control.php, before calling question_control.
        $quids = $DB->get_records('question_usages', array('component' => 'mod_mooduell'));

        if ($quids && count($quids) > 0) {
            $quid = array_shift($quids);
        } else {
            $quid = game_control::register_for_question_usage($context);
        }

        $idstring = implode("/", [$quid->id, 1, $this->questionid]);

        $this->questiontext = file_rewrite_pluginfile_urls($this->questiontext,
                'pluginfile.php', $this->contextid, 'question',
                'questiontext', $idstring);

        $dom = new \DOMDocument();
        $dom->loadHTML($this->questiontext);

        $images = $dom->getElementsByTagName('img');
        $url = '';
        $alttext = '';

        foreach ($images as $image) {
            $url = $image->getAttribute('src');
            $alttext = $image->getAttribute('alt');
            break;
        }
        // No HTML Text anymore.
        $this->questiontext = strip_tags($this->questiontext);
        // But markdown, if there is any. Even if it's not markdown formatted text.
        $this->questiontext = format_text($this->questiontext, 4);
        $this->length = strlen($this->questiontext);
        $this->imageurl = $url;
        $this->imagetext = $alttext;
    }

    /**
     * Make sure we have the right number of answers.
     * @throws coding_exception
     */
    private function check_for_right_number_of_answers() {

        $countcorrectanswers = 0;
        foreach ($this->answers as $answer) {
            if ($answer->correct) {
                ++$countcorrectanswers;
            }
        }
        if ($countcorrectanswers < 1) {
            $this->warnings[] = [
                    'message' => get_string('questionhasnocorrectanswers', 'mod_mooduell', $this->questionid)
            ];
            $this->status = get_string('notok', 'mod_mooduell');
        } else if ($countcorrectanswers == 1 && $this->questiontype == 'multichoice') {
            // If there only is one correct answer, convert to singlechoice.
            $this->questiontype = 'singlechoice';
        }
    }

    /**
     * Make sure questiontext is not too long.
     * @throws coding_exception
     */
    private function check_for_right_length_of_questiontext() {
        if (strlen($this->questiontext) < MINLENGTH) {
            $this->warnings[] = [
                    'message' => get_string('questiontexttooshort', 'mod_mooduell', $this->questionid)
            ];
            $this->status = get_string('notok', 'mod_mooduell');
        } else if (strlen($this->questiontext) > MAXLENGTH) {
            $this->warnings[] = [
                    'message' => get_string('questiontexttoolong', 'mod_mooduell', $this->questionid)
            ];
            $this->status = get_string('notok', 'mod_mooduell');
        }
    }

    /**
     * Verify question type.
     * @throws coding_exception
     */
    private function check_for_right_type_of_question() {
        if (!in_array($this->questiontype, ACCEPTEDTYPES)) {
            $this->warnings[] = [
                    'message' => get_string('wrongquestiontype', 'mod_mooduell', $this->questionid)
            ];
            $this->status = get_string('notok', 'mod_mooduell');
        }
    }
}
