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
     *
     * @param mooduell $mooduell
     */
    public function __construct($data = null, $listofanswers = null) {
        // If we have $data, we automatically create all the relevant values for this question...
        global $COURSE;
        global $DB;


        if ($data) {
            $this->questionid = $data->id;
            $this->name = $data->name;
            $this->questiontext = $data->questiontext;
            $this->questiontextformat = $data->questiontextformat;
            $this->questiontype = $data->qtype;
            $this->category = $data->category;
            $this->categoryname = isset($data->categoryname) ? $data->categoryname : null;
            $this->courseid = $COURSE->id;

            // We need the context id, but it might be there already
            $this->contextid = isset($data->contextid) ? $data->contextid : $DB->get_field('question_categories', 'contextid', array('id' => $this->category));

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
     *
     * @return array
     * @throws dml_exception
     */
    public function return_answers($listofanswers = null) {
        global $DB;
        $answers = array();

        if (!$listofanswers || count($listofanswers) === 0) {
            $listofanswers = $DB->get_records('question_answers', [
                    'question' => $this->questionid
            ]);
        }

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


    public function validate_question($answerids, $showcorrectanswer) {

        // If we don't have answers, something went wrong, we return error code -1.
        if (count($this->answers) == 0) {
            return [-1];
        }
        foreach ($this->answers as $answer) {
            if ($answer->fraction > 0) {
                // If this is a correct answer, we want it in our array of correct answers OR we need to find it in our array of given answers.
                if ($showcorrectanswer) {
                    $resultarray[] = $answer->id;
                } else {
                    // If we can't find the correct answer in our answerarray, we return wrong answer.
                    if (!in_array($answer->id, $answerids)) {
                        $resultarray[] = 0;
                        break;
                    }
                }
            } else {
                // If we have on wrong answer in our answer array ...
                // ... and only if we don't want to show the correct answers.
                if (!$showcorrectanswer) {
                    // we check if we have registered a wrong answer
                    if (in_array($answer->id, $answerids)) {
                        $resultarray[] = 0;
                        break;
                    }
                }
            }
        }
        // If we had no reason to add 0 to our result array, we can return 1.
        if (!$showcorrectanswer && count($resultarray) == 0) {
            $resultarray[] = 1;
        }
        return $resultarray;
    }

    /**
     * Add warnings for every problem an set status accordingly.
     */
    private function check_question() {

        // Check for correct number of answers and set status and qtype accordingly.
        $this->check_for_right_number_of_answers();

        // Check for the correct Typ of the question. Should be called AFTER right number of answers.
        $this->check_for_right_type_of_question();

        // Check for the right questiontext length
        $this->check_for_right_length_of_questiontext();

        if (count($this->warnings) == 0) {
            $this->status =  get_string('ok', 'mod_mooduell');
        }


    }

    /**
     * @throws dml_exception
     */
    private function extract_image() {

        if (strpos($this->questiontext, '<img src') === false) {
            return;
        }

        global $PAGE;
        global $DB;
        global $CFG;

        require_once($CFG->libdir . '/filelib.php');

        $context = \context_course::instance($this->courseid);

        // require_once($CFG->libdir . '/coursecatlib.php');
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


        $this->questiontext = file_rewrite_pluginfile_urls($this->questiontext, 'pluginfile.php', $this->contextid, 'question', 'questiontext', $idstring);



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
        // No HTML Text anymore
        $this->questiontext = strip_tags($this->questiontext);
        // But markdown, if there is any. Even if it's not markdown formatted text
        $this->questiontext = format_text($this->questiontext, 4);
        $this->length = strlen($this->questiontext);
        $this->imageurl = $url;

        $this->imagetext = $alttext;
    }

    private function check_for_right_number_of_answers() {
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
            $this->questiontype = 'singlechoice';
        } // Else do nothing;
    }

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

    private function check_for_right_type_of_question() {
        if (!in_array($this->questiontype, ACCEPTEDTYPES)) {
            $this->warnings[] = [
                    'message' => get_string('wrongquestiontype', 'mod_mooduell', $this->questionid)
            ];
            $this->status = get_string('notok', 'mod_mooduell');
        }
    }
}