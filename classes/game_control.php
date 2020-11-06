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

require_once("$CFG->libdir/enrollib.php");

use DateTime;
use dml_exception;
use moodle_exception;
use stdClass;
use user_picture;

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
     * Game_control constructor.
     *
     * We set all the data we have at this moment and make it available to the instance of this class.
     *
     * @param mooduell $mooduell
     */
    public function __construct(mooduell $mooduell, $gameid = null, $gamedata = null) {
        global $USER;
        global $DB;

        $now = new DateTime();
        $nowtime = $now->getTimestamp();

        $this->mooduell = $mooduell;

        // If we construct with a game id from the Webservice, we load all the data.
        if ($gameid && !$gamedata) {

            $data = $DB->get_record('mooduell_games', [
                    'id' => $gameid,
                    'mooduellid' => $this->mooduell->cm->instance
            ]);

            if (!$data->id) {

                // This error will also kick in if we have the gameid...
                // ... but it's not asked for in the right quiz (mooduell instance id).

                throw new moodle_exception('nosuchgame', 'mooduell', null, null,
                        "We couldn't find the game you asked for in our database.");
            }
            $data->gameid = $gameid;

            // If we have already a record and player a or player b are not the user we use here, we throw an error.

            $context = $mooduell->context;

            // A Teacher can access a game where he/she is was not involved
            if (!has_capability('mod/mooduell:managemooduellsettings', $context)
            && ($USER->id != $data->playeraid) && ($USER->id != $data->playerbid)) {
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
            $data->playeraid = (int) $USER->id;
            $data->winnerid = 0;
            $data->timemodified = $nowtime;
            $data->timecreated = $nowtime;
        }

        $this->gamedata = $data;

    }

    /**
     * This fucntion first get_enrolled_users and filteres this list by module visibility of the active module.
     * This is needed to give us a valid list of potential partners for a new game.
     *
     * @return array
     * @throws moodle_exception
     */
    public static function return_users_for_game($mooduell) {

        global $PAGE;

        $context = $mooduell->context;
        $users = get_enrolled_users($context);

        $filteredusers = array();

        foreach ($users as $user) {

            //First we check if the user needs an alternatename and if he has one
            if (!$mooduell->gameData->usefullnames
            && strlen($user->alternatename) == 0) {
                continue;
            }

            // We need to specifiy userid already when calling modinfo.
            $modinfo = get_fast_modinfo($mooduell->course->id, $user->id);
            $cm = $modinfo->get_cm($mooduell->cm->id);

            if ($cm->uservisible) {
                $filteredusers[] = $user;
            }

            $userpicture = new user_picture($user);
            $userpicture->size = 1; // Size f1.
            $user->profileimageurl = $userpicture->get_url($PAGE)->out(false);

        }
        return $filteredusers;
    }

    public static function get_user_stats($userid) {

        global $DB;

        $returnarray = [];

        // Get all the games where player was either Player A or Player B AND game is finished.
        $data = $DB->get_records_sql('SELECT * FROM {mooduell_games} WHERE (playeraid = ' . $userid . ' OR playerbid =' . $userid .
                ') AND status = 3');
        $returnarray['playedgames'] = count($data);
        $data = $DB->count_records_sql('SELECT * FROM {mooduell_games} WHERE winnerid = ' . $userid);
        $returnarray['wongames'] = count($data);
        $returnarray['userid'] = $userid;

        // To find out the id of our nemesis, we first have to get all the records where we lost.
        $data = $DB->get_records_sql('SELECT * FROM {mooduell_games} WHERE (playeraid = ' . $userid . ' OR playerbid =' . $userid .
                ') AND status = 3 AND winnerid !=' . $userid . ' AND winnerid != 0');

        // Now we collect all our enemies in an array and increase the count whenever we stumble upon them again.

        $enemiesarray = [];
        foreach ($data as $entry) {

            // First we have to get adversaryid.
            $adversaryid = $entry->playeraid == $userid ? $entry->playerbid : $entry->playeraid;

            if (!$enemiesarray[$adversaryid]) {
                $enemiesarray[$adversaryid] = 1;
            } else {
                $enemiesarray[$adversaryid] += 1;
            }
        }

        $maxs = array_keys($enemiesarray, max($enemiesarray));
        $returnarray['nemesisuserid'] = $maxs[0];

        // We don't want to return undefined, so we check if we have to fix something.

        if (!$returnarray['nemesisuserid']) {
            $returnarray['nemesisuserid'] = 0;
        }
        if (!$returnarray['playedgames']) {
            $returnarray['playedgames'] = 0;
        }
        if (!$returnarray['wongames']) {
            $returnarray['wongames'] = 0;
        }

        return $returnarray;
    }

    /**
     * Create new game, set random question sequence and write to DB.
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

        // We get exactly nine questions from the right categories.
        // We run this before we save our game...
        // ... because it will throw an error if we don't receive the right number of questions.
        $questions = self::set_random_questions();

        // We collect all the data to save to mooduell_games table.
        $this->gamedata->gameid = $DB->insert_record('mooduell_games', $data);

        // Write all our questions to our DB and link it to our gameID.
        foreach ($questions as $question) {

            // We set data back
            $data = null;
            $data->questionid = $question->id;
            $data->mooduellid = $this->mooduell->cm->instance;
            $data->gameid = $this->gamedata->gameid;

            $DB->insert_record('mooduell_questions', $data);
        }

        return $this->get_questions();
    }

    /**
     * Get all available questions from the right categories in our question bank.
     * We make sure we get them according to weight and number of categories linked to the mooduell instance.
     * Return the questions as instances of question_control.
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

        if (count($categories) == 0) {
            throw new moodle_exception('nocategoriesassociated', null, null,
                    "There are no Categories associated to this quiz. We can't find any questions.");
        }

        // First we calculate the number of question every category gets.
        $setnumberofquestions = 9;
        $sum = 0;
        foreach ($categories as $category) {
            $sum += $category->weight;
        }

        // Now we add the numbersofquestions key to each category.
        $calculatedqnumber = 0;
        foreach ($categories as $category) {
            $category->numberofquestions = (int) round(($category->weight / $sum) * $setnumberofquestions);
            //$calculatedqnumber += $categories[$category->id]->numberofquestions;

            // We get all the available questions.
            $category->availableQuestions = $this->return_playable_questions_for_category($category);
        }

        $emergencybrake = true;
        $bonusmode = false;
        while (count($questions) < $setnumberofquestions) {
            foreach ($categories as $key => $category) {
                if (($category->numberofquestions > 0 || $bonusmode)
                        && count($category->availableQuestions) > 0
                        && count($questions) < $setnumberofquestions) {
                    $emergencybrake = false;
                    $i = array_rand($category->availableQuestions);



                    $question = $category->availableQuestions[$i];
                    $questions[] = $question;
                    unset($categories[$key]->availableQuestions[$i]);
                    --$categories[$key]->numberofquestions;
                }
                // If we run out in one category, we enter bonus mode
                if (count($category->availableQuestions) == 0
                        && $category->numberofquestions > 0) {
                    $bonusmode = true;
                }
            }
            if (!$emergencybrake) {
                $emergencybrake = true;
            } else if (count($questions) != $setnumberofquestions) {
                throw new moodle_exception('wrongnumberofquestions2', null, null,
                        "For some unknown reason we didn't receive the right number of questions");
            }
        }
        // We now have an "ordered" array of questions, categories are not mixed up.
        shuffle($questions);

        // make sure we have no duplicates

        return $questions;
    }

    /**
     * Get all questions and save them to gamedata.
     *
     * @return stdClass
     * @throws moodle_exception
     */
    public function get_questions() {
        global $DB;

        // In order to view image files in mod_mooduell, we have to register mod_mooduell in question_usages.
        self::register_for_question_usage($this->mooduell->context);

        // We have to make sure we have all the questions added to the normal game data.
        $questionsdata = $DB->get_records('mooduell_questions', [
                'gameid' => $this->gamedata->gameid
        ]);

        if (count($questionsdata) != 9) {
            throw new moodle_exception('wrongnumberofquestions1', 'mooduell', null, null,
                    "we received the wrong number of questions linked to our Mooduell game");
        }

        $questions = array();

        if ($questionsdata && count($questionsdata) > 0) {

            foreach ($questionsdata as $questiondata) {

                $data = $DB->get_record('question', [
                        'id' => $questiondata->questionid
                ]);

                $question = new question_control(($data));
                $question->get_results($this->gamedata->gameid);

                $questions[] = $question;

            }
        }

        $this->gamedata->questions = $questions;

        return $this->gamedata;
    }

    public static function register_for_question_usage($context) {
        global $DB;

        $entries = $DB->get_records('question_usages', array('contextid' => $context->id, 'component' => 'mod_mooduell'));
        if ($entries && count($entries) > 0) {
            // Do nothing;
        } else {
            $data = new stdClass();
            $data->contextid = $context->id;
            $data->component = 'mod_mooduell';
            $data->preferredbehaviour = 'deferredfeedback';
            $DB->insert_records('question_usages', [$data]);
        }

    }

    /**
     * Take the question id and the array of answerids and check if we have actually answered a question correctly.
     * Depending on the Instance Setting (showcorrectanswers) we either return an array of the correct answerids...
     * ... (validation will be up to the App)...
     * ... or we return 0 for false and 1 for correctly answered.
     * We count as correctly answered alls questions with a fraction 0 and above, falsly only those below 0.
     *
     * @param $questionid
     * @param $answerids
     * @return array
     * @throws moodle_exception
     */
    public function validate_question($questionid, $answerids) {

        global $USER;

        // Check if it's the right question sequence.
        // First we get our game data.
        $this->get_questions();

        $questions = $this->gamedata->questions;

        if (!$this->is_it_active_users_turn()) {
            throw new moodle_exception('notyourturn', 'mooduell', null, null,
                    "It's not your turn to answer a question");
        }

        // If there are questions, if we have the right number and if we find the specific question with the right id.
        if ($questions && count($questions) == 9) {

            $activequestion = null;

            foreach ($questions as $question) {
                if ($question->questionid == $questionid) {
                    $answers = $question->answers;
                    $activequestion = $question;
                    break;
                }
                // Sequence check to make sure we haven't skipped a question.
                if (($USER->id == $this->gamedata->playeraid && $question->playeraanswered == null) ||
                        ($USER->id == $this->gamedata->playerbid && $question->playerbanswered == null)) {
                    throw new moodle_exception('outofsequence', 'mooduell', null, null,
                            "You tried to answere a question out of sequence");
                }
            }

            // If we want the correct answers, we just return an array of these correct answers to the app...
            // ... which will deal with the rest.
            $showcorrectanswer = $this->mooduell->settings->showcorrectanswer == 1 ? true : false;

            if ($activequestion) {
                $resultarray = $activequestion->validate_question($answerids, $showcorrectanswer);
            } else {
                throw new moodle_exception('noactivquestion', 'mooduell', null, null,
                        "Couldn't find the question you wanted to answer");
            }

        } else {
            $resultarray[] = -1;
        }

        // After having calculated the resultarray, we have to translate the result for the db.
        // There, we don't need the correct answerids, but just if the player has answered correctly (1 is false, 2 is correct).
        if (!$showcorrectanswer) {
            $result = $resultarray[0] == 1 ? 2 : 1;
        } else {
            foreach ($resultarray as $resultitem) {
                if (count($resultarray) != count($answerids) || !in_array($resultitem, $answerids)) {
                    $result = 1;
                    break;
                }
            }
            // If we haven't set result to 1 (which means false), we can set it to 2 (correct).
            $result != 1 ? $result = 2 : null;
        }

        // We write the result of our question check.
        $this->save_result_to_db($this->gamedata->gameid, $questionid, $result);
        // After every answered questions, turn status is updated as well.
        $this->save_my_turn_status();

        return $resultarray;
    }

    /**
     * Check if active player is allowed to answer questions.
     *
     * @return bool
     */
    private function is_it_active_users_turn() {
        global $USER;

        $i = 0;
        $j = 0;
        foreach ($this->gamedata->questions as $question) {

            $i += $question->playeraanswered != null ? 1 : 0;
            $j += $question->playerbanswered != null ? 1 : 0;

        }

        // If we have incomplete packages, we can always go on...
        // ... else we have to have less or equal answered questions.

        // For i playera & j playerb
        if ($i < 3 && $j == 0) {
            // player a
            if ($USER->id == $this->gamedata->playeraid) {
                return true;
            } else {
                return false;
            }
        } else if ($i == 3 && $j < 6) {
            // player b
            if ($USER->id == $this->gamedata->playeraid) {
                return false;
            } else {
                return true;
            }
        } else if ($i < 9 && $j == 6) {
            // player a
            if ($USER->id == $this->gamedata->playeraid) {
                return true;
            } else {
                return false;
            }
        } else if ($i == 9 && $j < 9) {
            // player b
            if ($USER->id == $this->gamedata->playeraid) {
                return false;
            } else {
                return true;
            }
        }
    }

    /**
     * Write result to DB, 1 is false, 2 is correct.
     *
     * @param $gameid
     * @param $questionid
     * @param $result
     * @return bool
     * @throws dml_exception
     * @throws moodle_exception
     */
    private function save_result_to_db($gameid, $questionid, $result) {

        global $DB;
        global $USER;

        // First we fetch the record of this question.
        $question = $DB->get_record('mooduell_questions', ['gameid' => $gameid, 'questionid' => $questionid]);

        // Then we update the content.
        $update = new stdClass();
        $update->id = $question->id;

        // Depending if I am player A or B, we update the right field.
        if ($this->gamedata->playeraid == $USER->id) {

            // We throw an Error if the question is already answered.
            if ($question->playeraanswered != null) {
                throw new moodle_exception('questionalreadyanswered', 'mooduell', null, null,
                        "You just answered a question which was already answered");
            }

            $update->playeraanswered = $result;
            // We update result in live memory as well.
            foreach ($this->gamedata->questions as $question) {
                if ($questionid == $question->questionid) {
                    $question->playeraanswered = $result;
                    break;
                }
            }
        } else {

            // We throw an Error if the question is already answered.
            if ($question->playerbanswered != null) {
                throw new moodle_exception('questionalreadyanswered', 'mooduell', null, null,
                        "You just answered a question which was already answered");
            }

            $update->playerbanswered = $result;
            // We update in live memory as well.
            foreach ($this->gamedata->questions as $question) {
                if ($questionid == $question->questionid) {
                    $question->playerbanswered = $result;
                    break;
                }
            }
        }

        // Check who's turn it is.

        $DB->update_record('mooduell_questions', $update);

        return true;
    }

    /**
     * Save whose turn it is to status in mooduell_games DB (1 Player As turn, 2 Player B turn).
     * This function also check if game is finished and sets status to 3 if so.
     *
     * @throws dml_exception
     */
    private function save_my_turn_status() {
        global $DB;
        global $USER;

        $update = new stdClass();
        $update->id = $this->gamedata->gameid;

        if ($this->is_game_finished()) {
            // We might want to trigger some event here.
            $update->status = 3;

            // Set winnerid
            $update->winnerid = $this->return_winnerid();

        } else if ($this->is_it_active_users_turn()) {
            $update->status = $USER->id == $this->gamedata->playeraid ? 1 : 2;
        } else {
            $update->status = $USER->id == $this->gamedata->playeraid ? 2 : 1;
        }

        $DB->update_record('mooduell_games', $update);

    }

    /**
     * Check if active player is allowed to answer questions.
     *
     * @return bool
     */
    private function is_game_finished() {

        if (count($this->gamedata->questions) != 9) {
            throw new moodle_exception('nottherightnumberofquestions', 'mooduell', null, null,
                    'Not the right number of questions (' . count($this->gamedata->questions) .
                    '), we cant decide if game is finsihed or not');
        }

        foreach ($this->gamedata->questions as $question) {
            if ($question->playeraanswered == null || $question->playerbanswered == null) {
                return false;
            }
        }
        return true;
    }

    /**
     * Determine userid of winner.
     * Returns 0 on draw.
     * @return int
     */
    private function return_winnerid() {
        $playerascore = 0;
        $playerbscore = 0;
        foreach($this->gamedata->questions as $question) {
            if ($question->playeraanswered == 2) {
                ++$playerascore;
            }
            if ($question->playerbanswered == 2) {
                ++$playerbscore;
            }
        }
        if ($playerascore < $playerbscore) {
            return $this->gamedata->playerbid;
        } else if ($playerascore > $playerbscore) {
            return $this->gamedata->playeraid;
        } else {
            return 0;
        }
    }

    /**
     * @return mixed
     */
    public function return_status() {

        // We make sure we already have our questions when we call this function.
        if (!isset($this->gamedata->questions) || count($this->gamedata->questions) == 0) {
            $this->get_questions();
        }

        $playerastring = '';
        $playerbstring = '';

        foreach ($this->gamedata->questions as $question) {

            if ($question->playeraanswered == null) {
                $playerastring .= ' - ';
            } else {
                $playerastring .= $question->playeraanswered == 1 ? '&#10008;' : '&#10003;';
            }

            if ($question->playerbanswered == null) {
                $playerbstring .= ' - ';
            } else {
                $playerbstring .= $question->playerbanswered == 1 ? '&#10008;' : '&#10003;';
            }
        }

        $returnarray[] = $playerastring;
        $returnarray[] = $playerbstring;

        return $returnarray;
    }

    /**
     * Function fetches questions from DB, creating question_control instances to check for status.
     * If status is not ok, question is not returned.
     * @param $category
     * @throws dml_exception
     */
    private function return_playable_questions_for_category($category) {
        global $DB;

        $returnarray = [];

        $questions = $DB->get_records('question', [
                'category' => $category->category
        ]);

        foreach($questions as $question) {
            $newquestion = new question_control($question);

            if ($newquestion->status == get_string('ok', 'mod_mooduell')) {
                $returnarray[] = $question;
            }
        }

        return $returnarray;
    }
}