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
            $this->contextid = isset($data->contextid) ? $data->contextid : $DB->get_field('question_categories',
                    'contextid', array('id' => $this->category));

            // Normally we don't have this information, we use retrieve_result to retrieve it.
            if (isset($data->playeraanswered)) {
                $this->playeraanswered = $data->playeraanswered;
            }
            if (isset($data->playerbanswered)) {
                $this->playerbanswered = $data->playerbanswered;
            }

            $this->extract_image();

            switch($this->questiontype) {
                // For numerical questions, we do not want to return any answers.
                case 'numerical':
                    $this->answers = array();
                    break;
                // For all other questions, we return the list of answers.
                default:
                    $this->answers = $this->return_answers($listofanswers);
                    break;
            }

            $this->check_question();
        }
    }

    /**
     * Return array of answers of a given question.
     * @param null $listofanswers
     * @return array
     * @throws dml_exception
     */
    public function return_answers($listofanswers = null) {
        global $DB;

        if (!$listofanswers || count($listofanswers) === 0) {
            $listofanswers = $DB->get_records('question_answers', [
                    'question' => $this->questionid
            ]);
        }

        switch ($this->questiontype) {
            case 'singlechoice':
            case 'multichoice':
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
                break;
            case 'numerical':
                // For numerical question we only need the values from DB.
                $answers = $listofanswers;
                break;
            default:
                $answers = array();
                break;
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
                list($resultarray, $iscorrect) = $this->validate_single_and_multichoice_question($answerids, $showcorrectanswer);
                break;
            default:
                $resultarray = [];
                $iscorrect = -1; // Invalid question type.
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

        // Get the numerical answer(s).
        $this->answers = $this->return_answers();

        // If we don't have answers, something went wrong, we return error code -1.
        if (count($this->answers) == 0) {
            return [-1];
        }

        // Add all correct answers to the array of correct answers.
        // With numerical questions we have only one correct answer in most (but not all) cases.
        foreach ($this->answers as $answer) {
            if ($answer->fraction > 0) {
                $resultarray[] = (float) $answer->answer;
            }
        }

        // Now loop again through all correct answers and check...
        // ... if the given answer is within the tolerance of one of them.
        foreach ($this->answers as $answer) {
            if ($answer->fraction > 0) {
                $tolerance = $DB->get_field('question_numerical', 'tolerance',
                    ['question' => $this->questionid, 'answer' => $answer->id]);

                $min = $answer->answer - $tolerance;
                $max = $answer->answer + $tolerance;
                if ($min <= $answergiven && $answergiven <= $max) {
                    $iscorrect = 1;
                    break;
                }
            }
        }

        return [$resultarray, $iscorrect];
    }

    /**
     * Private function to validate single and multiple choice questions.
     * @param array $answerids
     * @param int $showcorrectanswer
     * @return array An array of results.
     */
    private function validate_single_and_multichoice_question(array $answerids, int $showcorrectanswer): array {
        $resultarray = [];
        $iscorrect = 1;

        // If we don't have answers, something went wrong, we return error code -1.
        if (count($this->answers) == 0) {
            // First value is $resultarray, second $iscorrect parameter.
            return [[-1], -1];
        }
        foreach ($this->answers as $answer) {
            if ($answer->fraction > 0) {
                // If this is a correct answer...
                // ... we want it in our array of correct answers OR we need to find it in our array of given answers.
                if ($showcorrectanswer) {
                    $resultarray[] = $answer->id;
                } else {
                    // If we can't find the correct answer in our answerarray, we return wrong answer.
                    if (!in_array($answer->id, $answerids)) {
                        $resultarray[] = 0;
                        $iscorrect = 0;
                        break;
                    }
                }
            } else {
                // If we have one wrong answer in our answer array ...
                // ... and only if we don't want to show the correct answers.
                if (!$showcorrectanswer) {
                    // We check if we have registered a wrong answer.
                    if (in_array($answer->id, $answerids)) {
                        $resultarray[] = 0;
                        $iscorrect = 0;
                        break;
                    }
                }
            }
        }
        // If we had no reason to add 0 to our result array, we can return 1.
        if (!$showcorrectanswer && count($resultarray) == 0) {
            $resultarray[] = 1;
        }
        return [$resultarray, $iscorrect];
    }

    /**
     * Add warnings for every problem and set status accordingly.
     * @throws \coding_exception
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
     * @throws \coding_exception
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
     * @throws \coding_exception
     */
    private function check_for_right_number_of_answers() {

        // For numerical questions we do not need to check the number of answers.
        switch ($this->questiontype) {
            case 'numerical':
                return;
            case 'singlechoice':
            case 'multichoice':
                $countcorrectanswers = 0;
                foreach ($this->answers as $answer) {
                    if ($answer->fraction > 0) {
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
                // Else do nothing.
                return;
        }
    }

    /**
     * Make sure questiontext is not too long.
     * @throws \coding_exception
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
     * @throws \coding_exception
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
