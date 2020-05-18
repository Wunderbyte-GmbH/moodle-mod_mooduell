<?php
// This file is part of Moodle - http:// moodle.org/
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

use DateTime;
use moodle_exception;
use stdClass;

class game_control {

    /**
     *
     * @var stdClass
     */
    public $gamedata;
    /**
     *
     * @var mooduell MooDuell instance
     */
    private $mooduell;

    /**
     * game_control constructor.
     *
     * we set all the data we have at this moment and make it available to the instance of this class
     *
     * @param mooduell $mooduell
     */
    public function __construct(mooduell $mooduell, $gameid = null, $gamedata = null) {
        global $USER;
        global $DB;

        $now = new DateTime();
        $nowtime = $now->getTimestamp();

        $this->mooduell = $mooduell;

        // if we construct with a game id from the Webservice, we load all the data.
        if ($gameid && !$gamedata) {

            $data = $DB->get_record('mooduell_games', [
                    'id' => $gameid
            ]);
            $data->gameid = $gameid;

            // if we have already a record and player a or player b are not the user we use here, we throw an error.
            if (($USER->id != $data->playeraid) && ($USER->id != $data->playerbid)) {
                throw new moodle_exception('notallowedtoaccessthisgame', 'mooduell', null, null,
                        "Your are not participant of this game, you can't access it's data");
            }
        } else if ($gamedata) {
            $data = new stdClass();
            $data->gameid = $gamedata->id;
            $data->playeraid = $gamedata->playeraid;
            $data->playerbid = $gamedata->playerbid;
            $data->playeratime = $gamedata->playeratime;
            $data->playerbtime = $gamedata->playerbtime;
            $data->winnerid = $gamedata->winnerid;
            $data->status = $gamedata->status;
            $data->victorycoefficient = $gamedata->victorycoefficient;
            $data->timemodified = $gamedata->timemodified;
            $data->timecreated = $gamedata->timecreated;
        } else {
            $data = new stdClass();
            $data->playeraid = $USER->id;
            $data->timemodified = $nowtime;
            $data->timecreated = $nowtime;
        }

        $this->gamedata = $data;
    }

    /**
     * Create new game, set random question sequence and write to DB
     *
     * @return integer quizid or 0 when no quizid is set
     * @throws moodle_exception
     */
    public function start_new_game($playerbid) {
        global $DB;

        // First we check if the playerbid provided is valid, if not, we throw and exception.

        if (!$this->mooduell->user_exists($playerbid)) {
            throw new moodle_exception('adversaryiddoesnotexist', 'mooduell', null, null,
                    "You provided a user id which could not be found in our DB");
        }

        $data = $this->gamedata;
        $data->playerbid = $playerbid;
        $data->status = 1; // This means that it's player As turn
        $data->mooduellid = $this->mooduell->cm->instance;

        // We retrieve exactly nine questions from the right categories.
        // We run this before we save our game, because it will throw an error if we don't receive the right number of questions.
        $questions = self::set_random_questions();

        // We collect all the data to safe to mooduell_games table.
        $this->gameid = $DB->insert_record('mooduell_games', $data);

        // Write all our questions to our DB and link it to our gameID.
        foreach ($questions as $question) {

            // We set data back
            $data = null;
            $data->questionid = $question->id;
            $data->mooduellid = $this->mooduell->cm->instance;
            $data->gameid = $this->gameid;

            $DB->insert_record('mooduell_questions', $data);
        }

        return $this->gameid;
    }

    /**
     * Retrieve all available questions from the right categories in our question bank
     * We make sure we retrieve them according to weight and number of categories linked to the mooduell instance
     * Return the questions as instances of question_control
     *
     * @return mixed[]
     * @throws moodle_exception
     */
    private function set_random_questions() {
        global $DB;
        $questions = array();

        $categories = $DB->get_records('mooduell_categories', [
                'mooduellid' => $this->mooduell->cm->instance
        ]);

        // First we calculate the number of question every category gets.
        $setnumberofquestions = 9;
        $sum = 0;
        foreach ($categories as $category) {
            $sum += $category->weight;
        }

        // Now we add the numbersofquestions key to each category.
        $calculatednumberofquestions = 0;
        foreach ($categories as $category) {

            $categories[$category->id]->numberofquestions = round(($category->weight / $sum) * $setnumberofquestions);
            $calculatednumberofquestions += $categories[$category->id]->numberofquestions;
        }

        // First we lookup all the categories linked to this Mooduell instance. In our first version, this will return only one record.
        foreach ($categories as $category) {

            // We need a correction of the calculated values to make sure we always add exactly nine questions.
            // (there could be a problem when we have to categories with weight 100, we would only add two times 4).
            // TODO make this random and linked to overall weight. Right now we only add it to the first category.
            if ($calculatednumberofquestions != $setnumberofquestions) {
                $difference = $setnumberofquestions - $calculatednumberofquestions;
                $categories[$category->id]->numberofquestions += $difference;
                $calculatednumberofquestions += $difference;

                if ($calculatednumberofquestions != $setnumberofquestions) {
                    throw new moodle_exception('wrongnumberofquestions ', null, null, "We have the wrong number of questions");
                }
            }

            // We retrieve all the available questions.
            $allavailalbequestions = $DB->get_records('question', [
                    'category' => $category->category
            ]);

            // We have to be sure that the number of available questions for this category is bigger than the number of questions we want from this category.

            if (count($allavailalbequestions) < $category->numberofquestions) {
                throw new moodle_exception('wrongnumberofquestions ', null, null,
                        "There are not enough questions in this category");
            }

            $i = 0;
            $emergencybreak = 0;
            while ($i < $category->numberofquestions) {
                $key = array_rand($allavailalbequestions);

                $question = $allavailalbequestions[$key];

                if ($question != null && !in_array($question, $questions)) {
                    $questions[] = $question;
                    $i++;
                }
                $emergencybreak++;
                /* We have an emergency break here to avoid looping.
                It could kick in if we have less then nine different questions overall
                (three random questions with three times the same category, which all in all only has four questions.
                So enough questions for each category individually, but not together). */

                if ($emergencybreak > 500) {
                    throw new moodle_exception('onlyduplicatequestionsfound ', null, null,
                            "Apparently we have only duplicate questions, we had to abort our search for unqiue questions");
                }
            }
        }

        // We have an error if we don't have count(questions) == 9.

        if (count($questions) != $setnumberofquestions) {
            throw new moodle_exception('wrongnumberofquestions ', null, null,
                    "For some unknown reason we didn't receive the right number of questions");
        }

        return $questions;
    }

    /**
     * Take the question id and the array of answerids and check if we have actually answered a question correctly.
     * Depending on the Instance Setting (showcorrectanswers) we either return an array of the correct answerids (validation will
     * be up to the App)...
     * ... or we return 0 for false and 1 for correctly answered.
     * We count as correctly answered alls questions with a fraction 0 and above, falsly only those below 0.
     *
     * @param $questionid
     * @param $answerids
     * @return array
     * @throws moodle_exception
     */
    public function validate_question($questionid, $answerids) {

        // Check if it's the right question sequence.
        // First we retrieve our game data.
        $this->retrieve_questions();

        $questions = $this->gamedata->questions;

        $resultarray = array();

        // if there are questions, if we have the right number and if we find the specific question with the right id
        if ($questions && count($questions) == 9) {

            $answers = array();
            foreach ($questions as $question) {
                if ($question->id == $questionid) {
                    $answers = $question->answers;
                }
            }

            // If we want the correct answers, we just return an array of these correct answers to the app...
            // ... which will deal with the rest.
            $showcorrectanswer = $this->mooduell->settings->showcorrectanswer == 1 ? true : false;

            foreach ($answers as $answer) {
                if ($answer->fraction >= 0) {
                    // if this is a correct answer, we want it in our array of correct answers OR we need to find it in our array of given answers
                    if ($showcorrectanswer) {
                        $resultarray[] = $answer->id;
                    } else {
                        // if we can't find the correct answer in our answerarray, we return wrong answer
                        if (!in_array($answer->id, $answerids)) {
                            $resultarray[] = 0;
                            break;
                        }
                    }

                } else {
                    // If we have on wrong answer in our answer array ...
                    // ... and only if we don't want to show the correct answers
                    if (!$showcorrectanswer) {
                        // we check if we have registered a wrong answer
                        if (in_array($answer->id, $answerids)) {
                            $resultarray[] = 0;
                            break;
                        }
                    }

                }
            }
            // if we had no reason to add 0 to our result array, we can return 1
            if (count($resultarray) == 0) {
                $resultarray[] = 1;
            }
        } else {
            $resultarray[] = 0;
        }

        return $resultarray;
    }

    /**
     *
     * @return stdClass
     * @throws moodle_exception
     */
    public function retrieve_questions() {
        global $DB;

        // We have to make sure we have all the questions added to the normal game data.

        $questionsdata = $DB->get_records('mooduell_questions', [
                'gameid' => $this->gamedata->gameid
        ]);

        if (count($questionsdata) != 9) {
            throw new moodle_exception('wrongnumberofquestions', 'mooduell', null, null,
                    "we received the wrong number of questions linked to our Mooduell game");
        }

        $questions = array();

        if ($questionsdata && count($questionsdata) > 0) {

            foreach ($questionsdata as $questiondata) {

                $data = $DB->get_record('question', [
                        'id' => $questiondata->questionid
                ]);

                $question = new question_control(($data));

                $questions[] = $question;

            }
        }

        $this->gamedata->questions = $questions;

        return $this->gamedata;
    }

}