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

/**
 * Game control class for mod_mooduell.
 *
 * @package mod_mooduell
 * @copyright 2021 Wunderbyte GmbH <georg.maisser@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mooduell;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/enrollib.php");
require_once("$CFG->dirroot/user/lib.php");
require_once("$CFG->dirroot/user/profile/lib.php");

use DateTime;
use dml_exception;
use mod_mooduell\event\game_finished;
use moodle_exception;
use stdClass;
use tool_dataprivacy\context_instance;
use user_picture;

define("EMPTY_RESULT", " -  -  -  -  -  -  -  -  - ");

/**
 * Class to store all the relevant game data and execute game relevant function.
 * @package mod_mooduell
 */
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
     * We set all the data we have at this moment and make it available to the instance of this class.
     * @param mooduell $mooduell
     * @param null $gameid
     * @param null $gamedata
     * @throws \coding_exception
     * @throws dml_exception
     * @throws moodle_exception
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

            // A Teacher can access a game where he/she is was not involved.
            if (!has_capability('mod/mooduell:managemooduellsettings', $context)
            && ($USER->id != $data->playeraid) && ($USER->id != $data->playerbid)) {
                throw new moodle_exception('notallowedtoaccessthisgame', 'mooduell', null, null,
                        "Your are not participant of this game, you can't access it's data");
            }
        } else if ($gamedata) {

            $gameid = $gamedata->id;
            $data = $gamedata;
            $data->gameid = $gameid;
            $data->id = null;
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
     * This function first get_enrolled_users and filters this list by module visibility of the active module.
     * Users who are not allowed to see the current MooDuell instance will be skipped too.
     * This is needed to give us a valid list of potential partners for a new game.
     * @param mooduell $mooduell
     * @param bool $loadprofile
     * @return array
     * @throws \coding_exception
     * @throws moodle_exception
     */
    public static function return_users_for_game(mooduell $mooduell, $loadprofile = true) {

        global $PAGE;

        $context = $mooduell->context;
        $users = get_enrolled_users($context);

        $filteredusers = array();

        foreach ($users as $user) {
            // We need to skip users who are missing the capability ...
            // ... to view the current MooDuell instance (3rd parameter of is_enrolled)
            // Also skip users with no active enrolement status (4th parameter of is_enrolled).
            if (!is_enrolled($context, $user, 'mod/mooduell:viewinstance', true)) {
                continue;
            }

            profile_load_custom_fields($user);

            // We want to make sure we have no disruption in the transition, so we use alternate name...
            // ... when there is no moodle alias name.
            if (!$user->profile['mooduell_alias'] && strlen($user->alternatename) > 0) {
                $user->profile_field_mooduell_alias = $user->alternatename;
                profile_save_data($user);
            }

            // First we check if the user needs an alternatename and if he has one.
            if ($mooduell->settings->usefullnames == 0
            && strlen($user->profile['mooduell_alias']) == 0) {
                continue;
            } else {
                // For backward compatibility we didn't change the "alternatename" key, but we now return ...
                // ...custom profile field mooduell_alias instead of alternatename.
                $user->alternatename = $user->profile['mooduell_alias'];
            }

            // We need to specifiy userid already when calling modinfo.
            $modinfo = get_fast_modinfo($mooduell->course->id, $user->id);
            $cm = $modinfo->get_cm($mooduell->cm->id);

            if ($cm->uservisible) {
                $filteredusers[] = $user;
            }

            if ($loadprofile) {
                $userpicture = new user_picture($user);
                $userpicture->size = 1; // Size f1.
                $user->profileimageurl = $userpicture->get_url($PAGE)->out(false);
            }
        }
        return $filteredusers;
    }

    /**
     * Function to assemble stats of a given user.
     * @param int $userid
     * @param int|null $mooduellid
     * @return array
     * @throws dml_exception
     * @throws moodle_exception
     */
    public static function get_user_stats(int $userid, $mooduellid = null) {

        global $DB;

        $returnarray = [];
        try {
            // Get all the games where player was either Player A or Player B AND game is finished.
            $data = $DB->get_records_sql('SELECT * FROM {mooduell_games} WHERE (playeraid = ' .
                    $userid . ' OR playerbid =' . $userid .
                    ')');

            $wongames = 0;
            $lostgames = 0;
            $playedgames = 0;
            $correctlyanswered = 0;
            $playedquestions = 0;

            // We need moduleid below.
            $moduleid = $DB->get_field('modules', 'id', array('name' => 'mooduell'));

            // To avoid checking visibility for every game, we only do it for every mooduell instance.
            // Therefore, we use this array.
            $visibletouser = [];

            foreach ($data as $entry) {
                // Check if user has the right to access.
                if ($mooduellid != null && $mooduellid !== $entry->mooduellid) {
                    continue;
                }

                // We run the database lookup only if we don't have an entry in our array yet.
                if (!isset($visibletouser[$entry->mooduellid])) {
                    $cm = $DB->get_record('course_modules', array('instance' => $entry->mooduellid, 'module' => $moduleid));

                    $modinfo = get_fast_modinfo($cm->course);
                    $cm = $modinfo->get_cm($cm->id);

                    // We store the visibility for this instance.
                    $visibletouser = [
                        $entry->mooduellid => $cm->uservisible
                    ];
                } else if (!$visibletouser[$entry->mooduellid]) {
                    continue;
                }

                // We count won and lost games only when they are finished.
                if ($entry->status == 3) {
                    ++$playedgames;

                    if ($entry->winnerid == $userid) {
                        ++$wongames;
                    } else if ($entry->winnerid !== 0) {
                        ++$lostgames;
                    }
                }
                if ($entry->playeraid == $userid) {
                    $correctlyanswered += $entry->playeracorrect;
                    // If we updated from the old version, we have null as default at this place...
                    // ... and we have to calculate the qplayed.
                    if ($entry->playeraqplayed === null) {
                        $playedquestions += 9 - substr_count('-', $entry->playeraresults);
                    } else {
                        $playedquestions += $entry->playeraqplayed;
                    }
                } else {
                    $correctlyanswered += $entry->playerbcorrect;

                    // If we updated from the old version, we have null as default at this place...
                    // ... and we have to calculate the qplayed.
                    if ($entry->playerbqplayed === null) {
                        $playedquestions += 9 - substr_count('-', $entry->playerbresults);
                    } else {
                        $playedquestions += $entry->playerbqplayed;
                    }
                }
            }
            $returnarray['playedgames'] = $playedgames;
            $returnarray['wongames'] = $wongames;
            $returnarray['lostgames'] = $lostgames;
            $returnarray['correctlyanswered'] = $correctlyanswered;
            $returnarray['playedquestions'] = $playedquestions;
        } catch (\exception $e) {
            throw new moodle_exception('nomooduellinstance', 'mooduell', null, null,
                    "No MooDuell instance seems to exist on your plattform");
        }

        // We don't want to return undefined, so we check if we have to fix something.
        if (!$returnarray['playedgames']) {
            $returnarray['playedgames'] = 0;
        }
        if (!$returnarray['wongames']) {
            $returnarray['wongames'] = 0;
        }
        if (!$returnarray['playedquestions']) {
            $returnarray['playedquestions'] = 0;
        }

        return $returnarray;
    }

    /**
     * Create new game, set random question sequence and write to DB.
     * @param int $playerbid
     * @return false|mixed|stdClass
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function start_new_game(int $playerbid) {
        global $DB;

        // First we check if the playerbid provided is valid, if not, we throw and exception.

        if (!$this->mooduell->user_exists($playerbid)) {
            throw new moodle_exception('adversaryiddoesnotexist', 'mooduell', null, null,
                    "You provided a user id which could not be found in our DB");
        }

        $data = $this->gamedata;
        $data->playerbid = $playerbid;
        $data->status = 1; // This means that it's player As turn.
        $data->mooduellid = $this->mooduell->cm->instance;

        // Set the result string to their initial value, so we don't get null.
        $data->playeraresults = EMPTY_RESULT;
        $data->playerbresults = EMPTY_RESULT;

        // Also initialize the number of questions played for both players with 0.
        $data->playeraqplayed = 0;
        $data->playerbqplayed = 0;

        // We get exactly nine questions from the right categories.
        // We run this before we save our game...
        // ... because it will throw an error if we don't receive the right number of questions.
        $questions = $this->set_random_questions();

        // We collect all the data to save to mooduell_games table.
        $this->gamedata->gameid = $DB->insert_record('mooduell_games', $data);

        // Write all our questions to our DB and link it to our gameID.
        foreach ($questions as $question) {

            // We set data back.
            $data = new stdClass();
            $data->questionid = $question->questionid;
            $data->mooduellid = $this->mooduell->cm->instance;
            $data->gameid = $this->gamedata->gameid;

            $DB->insert_record('mooduell_questions', $data);
        }

        $this->gamedata->questions = $questions;

        return $this->gamedata;
    }

    /**
     * Get all available questions from the right categories in our question bank and chose 9 of them.
     * We make sure we get them according to weight and number of categories linked to the mooduell instance.
     * Return the questions as instances of question_control.
     * @return array
     * @throws dml_exception
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
        foreach ($categories as $category) {
            $category->numberofquestions = (int) round(($category->weight / $sum) * $setnumberofquestions);

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
                // If we run out in one category, we enter bonus mode.
                if (count($category->availableQuestions) == 0
                        && $category->numberofquestions > 0) {
                    $bonusmode = true;
                }
            }
            if (!$emergencybrake) {
                $emergencybrake = true;
            } else if (count($questions) != $setnumberofquestions) {
                throw new moodle_exception('wrongnumberofquestions', null, null,
                        "For some unknown reason we didn't receive the right number of questions");
            }
        }
        // We now have an "ordered" array of questions, categories are not mixed up.
        shuffle($questions);

        // Make sure we have no duplicates.

        return $questions;
    }

    /**
     * Get all questions and save them to gamedata.
     * @return false|mixed|stdClass
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function get_questions() {
        global $DB;

        // In order to view image files in mod_mooduell, we have to register mod_mooduell in question_usages.
        self::register_for_question_usage($this->mooduell->context);

        // We have to make sure we have all the questions added to the normal game data.
        // Also, we use the mquestions here because we find the results attached to every question.
        // Therefore, we update the question class instances we already have.
        $mquestions = $DB->get_records('mooduell_questions', array('gameid' => $this->gamedata->gameid), 'id');

        // If there is a game with a wrong number of questions, we should clean it right away to avoid further damage.
        if (count($mquestions) != 9) {
            $DB->delete_records('mooduell_games', array('id' => $this->gamedata->id));
            throw new moodle_exception('wrongnumberofquestions1', 'mooduell', null, null,
                    "we received the wrong number of questions linked to our Mooduell game");
        }

        $questions = array();
        // If we have questions in our instance, we can return them right away.
        if ($this->mooduell->questions && count($this->mooduell->questions) > 0) {
            foreach ($mquestions as $mquestion) {
                foreach ($this->mooduell->questions as $item) {
                    if ($mquestion->questionid == $item->questionid) {
                        $item->playeraanswered = $mquestion->playeraanswered;
                        $item->playerbanswered = $mquestion->playerbanswered;
                        $questions[] = $item;
                        break;
                    }
                }
            }
            $this->gamedata->questions = $questions;
            return $this->gamedata;
        }

        $searcharray = '(';
        foreach ($mquestions as $mquestion) {
            $searcharray .= "$mquestion->questionid, ";
        }
        $searcharray = substr($searcharray, 0, -2);
        $searcharray .= ')';

        $sql = "SELECT *
                FROM {question} q
                WHERE q.id IN $searcharray";

        if (!$questionsdata = $DB->get_records_sql($sql)) {
            throw new moodle_exception('wrongnumberofquestions2', 'mooduell', null, null,
                    "we received the wrong number of questions linked to our Mooduell game");
        }

        foreach ($mquestions as $mquestion) {
            $question = new question_control($questionsdata[$mquestion->questionid]);
            $question->playeraanswered = $mquestion->playeraanswered;
            $question->playerbanswered = $mquestion->playerbanswered;
            $questions[] = $question;
        }

        $this->gamedata->questions = $questions;
        return $this->gamedata;
    }

    /**
     * This is necessary to display questions in certain contexts.
     * @param \context $context
     * @return stdClass|void
     * @throws \coding_exception
     * @throws dml_exception
     */
    public static function register_for_question_usage(\context $context) {
        global $DB;

        $entries = $DB->get_records('question_usages', array('contextid' => $context->id, 'component' => 'mod_mooduell'));
        if (empty($entries)) {
            $data = new stdClass();
            $data->contextid = $context->id;
            $data->component = 'mod_mooduell';
            $data->preferredbehaviour = 'deferredfeedback';
            $DB->insert_records('question_usages', [$data]);
            return $data;
        } // Else do nothing.
    }

    /**
     * Take the question id and the array of answerids and check if we have actually answered a question correctly.
     * Depending on the Instance Setting (showcorrectanswers) we either return an array of the correct answerids...
     * ... (validation will be up to the App)...
     * ... or we return 0 for false and 1 for correctly answered.
     * We count as correctly answered alls questions with a fraction 0 and above, falsly only those below 0.
     * @param int $questionid
     * @param array $answerids
     * @return mixed
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function validate_question(int $questionid, array $answerids) {

        global $DB, $USER;

        // Get the question type.
        if (!$questiontype = $DB->get_field('question', 'qtype', ['id' => $questionid])) {
            throw new moodle_exception('missingquestiontype', 'mooduell', null, null,
                "Question without a question type.");
        }

        // Check if it's the right question sequence.
        // First we get our game data.
        $this->get_questions();

        $questions = $this->gamedata->questions;

        if (!$this->is_it_active_users_turn()) {
            throw new moodle_exception('notyourturn', 'mooduell', null, null,
                    "It's not your turn to answer a question");
        }

        $activequestion = null;

        // If there are questions, if we have the right number and if we find the specific question with the right id.
        if ($questions && count($questions) == 9) {
            foreach ($questions as $question) {
                if ($question->questionid == $questionid) {
                    $activequestion = $question;
                    break;
                }
                // Sequence check to make sure we haven't skipped a question.
                if (($USER->id == $this->gamedata->playeraid && $question->playeraanswered == null) ||
                        ($USER->id == $this->gamedata->playerbid && $question->playerbanswered == null)) {
                    throw new moodle_exception('outofsequence', 'mooduell', null, null,
                            "You tried to answer a question out of sequence");
                }
            }

            // If we want the correct answers, we just return an array of these correct answers to the app...
            // ... which will deal with the rest. This will be ignored by numerical questions.
            $showcorrectanswer = $this->mooduell->settings->showcorrectanswer == 1;

            if ($activequestion) {
                list($resultarray, $iscorrect) = $activequestion->validate_question($answerids, $showcorrectanswer);
            } else {
                throw new moodle_exception('noactivquestion', 'mooduell', null, null,
                        "Couldn't find the question you wanted to answer");
            }

        } else {
            $resultarray[] = -1;
            $iscorrect = -1;
        }

        switch ($questiontype) {
            case 'singlechoice':
            case 'multichoice':
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
                break;
            case 'numerical':
                // If correct, we set result to 2, if false, we set result to 1.
                $iscorrect ? $result = 2 : $result = 1;
                break;
            default:
                throw new moodle_exception('wrongquestiontype', 'mooduell', null, null,
                    'Question type ' . $questiontype . ' is not supported right now.');
        }

        // We write the result of our question check.
        $this->save_result_to_db($this->gamedata->gameid, $questionid, $result);
        // After every answered questions, turn status is updated as well.

        if ($activequestion !== null) {
            if ($USER->id == $this->gamedata->playeraid) {
                $activequestion->playeraanswered = $result;
            } else {
                $activequestion->playerbanswered = $result;
            }
        }

        $this->save_my_turn_status();

        // Do we need to send a push notification? If so, we'll do it here.
        $this->send_notifcation_if_necessary();

        return $resultarray;
    }

    /**
     * There are a couple of cases where we have to send different types of messages. Here we check which one we nned.
     */
    private function send_notifcation_if_necessary() {

        $i = 0;
        $j = 0;
        foreach ($this->gamedata->questions as $question) {

            $i += $question->playeraanswered != null ? 1 : 0;
            $j += $question->playerbanswered != null ? 1 : 0;

        }

        if ($this->gamedata->status === 3) {
            // Notify Player A.
            if ($this->gamedata->winnerid === $this->gamedata->playeraid) {
                // You won.
                $this->send_push_notification('youwin');
            } else if ($this->gamedata->winnerid === 0) {
                // You played draw.
                $this->send_push_notification('draw');
            } else if ($this->gamedata->winnerid === $this->gamedata->playerbid) {
                // You lost.
                $this->send_push_notification('youlose');
            }
        } else if ($i === 3 && $j === 0) {
            // Player b is challenged.
            $this->send_push_notification('challenged');
        } else if ($i === 3 && $j === 6) {
            // Player a's turn.
            $this->send_push_notification('YOURTURNA');
        } else if ($i === 9 && $j === 6) {
            // Player b's turn.
            $this->send_push_notification('YOURTURNB');
        }
    }

    /**
     * Prepare all the information we need to send a push notification.
     * @param string $messagetype
     * @return array|null
     * @throws \coding_exception
     * @throws dml_exception
     */
    private function gather_notifcation_data(string $messagetype) {

        $users = self::return_users_for_game($this->mooduell, false);

        // Will be true if usefullnames setting is set to "1" or 1.
        $usefullnames = $this->mooduell->settings->usefullnames == 1;

        foreach ($users as $user) {

            // We don't need this, as the we get users with profile fields.
            // profile_load_custom_fields($user);

            if ($user->id == $this->gamedata->playeraid) {
                if ($usefullnames) {
                    // Full user name.
                    $playeraname = $user->firstname . ' ' . $user->lastname;
                } else {
                    // Nickname.
                    $playeraname = $user->profile['mooduell_alias'];
                }
                $playera = $user;
            }
            if ($user->id == $this->gamedata->playerbid) {
                if ($usefullnames) {
                    // Full user name.
                    $playerbname = $user->firstname . ' ' . $user->lastname;
                } else {
                    // Nickname.
                    $playerbname = $user->profile['mooduell_alias'];
                }
                $playerb = $user;
            }
        }

        // Use "Anonymous" if no nicknames can be found.
        if (empty($playeraname)) {
            $playeraname = get_string('anonymous', 'mod_mooduell');
        }
        if (empty($playerbname)) {
            $playerbname = get_string('anonymous', 'mod_mooduell');
        }

        $recepientid = 0;

        switch ($messagetype) {
            case 'youwin':
                $message = get_string($messagetype, 'mod_mooduell',  $playerbname);
                $recepientid = $playera->id ?? 0;
                break;
            case 'youlose':
                $message = get_string($messagetype, 'mod_mooduell',  $playerbname);
                $recepientid = $playera->id ?? 0;
                break;
            case 'draw':
                $message = get_string($messagetype, 'mod_mooduell',  $playerbname);
                $recepientid = $playera->id ?? 0;
                break;
            case 'YOURTURNA':
                $message = get_string('yourturn', 'mod_mooduell',  $playerbname);
                $recepientid = $playera->id ?? 0;
                break;
            case 'YOURTURNB':
                $message = get_string('yourturn', 'mod_mooduell',  $playeraname);
                $recepientid = $playerb->id ?? 0;
                break;
            case 'challenged':
                $message = get_string($messagetype, 'mod_mooduell',  $playeraname);
                $recepientid = $playerb->id ?? 0;
                break;
        }

        $tokens = $this->return_push_tokens_of_user($recepientid);

        if (!$tokens || count($tokens) === 0) {
            return null;
        }
        $fields = array
        (
                'registration_ids' => $tokens,
                'data' => array(
                        "name" => "xyz",
                        'image' => 'https://www.example.com/images/minion.jpg'
                ),
                'notification' => array(
                        'body' => $message,
                        'title' => $message,
                        'sound' => 'default',
                        'icon' => 'icon',
                        'badge' => 1
                )
        );

        return $fields;
    }

    /**
     * Returns array of pushtoken strings from given user.
     * If there are non, we return empty array
     * @param int $userid
     * @return array
     * @throws dml_exception
     */
    private function return_push_tokens_of_user(int $userid) {

        global $DB;

        $data = $DB->get_records('mooduell_pushtokens', array('userid' => $userid));

        $returnarray = [];

        if ($data && count($data) > 0) {
            foreach ($data as $entry) {
                $returnarray[] = $entry->pushtoken;
            }
        }

        return $returnarray;
    }

    /**
     * Check if active player is allowed to answer questions.
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

        // For i playera & j playerb.
        if ($i < 3 && $j == 0) {
            // Player a.
            if ($USER->id == $this->gamedata->playeraid) {
                return true;
            } else {
                return false;
            }
        } else if ($i == 3 && $j < 6) {
            // Player b.
            if ($USER->id == $this->gamedata->playeraid) {
                return false;
            } else {
                return true;
            }
        } else if ($i < 9 && $j == 6) {
            // Player a.
            if ($USER->id == $this->gamedata->playeraid) {
                return true;
            } else {
                return false;
            }
        } else if ($i == 9 && $j < 9) {
            // Player b.
            if ($USER->id == $this->gamedata->playeraid) {
                return false;
            } else {
                return true;
            }
        }
    }

    /**
     * Write result to DB, 1 is false, 2 is correct.
     * @param int $gameid
     * @param int $questionid
     * @param int $result
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
    public function save_my_turn_status() {
        global $DB;
        global $USER;

        $update = new stdClass();
        $update->id = $this->gamedata->gameid;

        if ($this->is_game_finished()) {
            // Set the status to 3 which means finished.
            $update->status = 3;
            $this->gamedata->status = 3;

            // Set winnerid.
            list($update->winnerid,
                    $update->playeracorrect,
                    $update->playerbcorrect,
                    $update->playeraqplayed,
                    $update->playerbqplayed)
                = $this->return_winnerid_and_correct_answers();
            $this->gamedata->winnerid = $update->winnerid;
        } else {
            if ($this->is_it_active_users_turn()) {
                $update->status = $USER->id == $this->gamedata->playeraid ? 1 : 2;
            } else {
                $update->status = $USER->id == $this->gamedata->playeraid ? 2 : 1;
            }
            // Even if the game is not finished, we still want to update correct (and played) answers for this game.
            list($update->playeracorrect, $update->playerbcorrect, $update->playeraqplayed, $update->playerbqplayed)
                = $this->return_correct_and_played_answers();
        }

        $result = $this->return_status();
        $update->playeraresults = $result[0];
        $update->playerbresults = $result[1];

        $now = new DateTime("now", \core_date::get_server_timezone_object());
        $update->timemodified = $now->getTimestamp();

        $updatestatus = $DB->update_record('mooduell_games', $update);

        // Now the mooduell_games table has been updated.
        // ... so we can trigger the game_finished event.
        if ($updatestatus && $this->is_game_finished()) {
            $event = game_finished::create(array('context' => $this->mooduell->context, 'objectid' => $this->mooduell->cm->id));
            $event->trigger();
        }
    }

    /**
     * Check if active player is allowed to answer questions.
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
    private function return_winnerid_and_correct_answers() {

        list($playeracorrect, $playerbcorrect, $playeraqplayed, $playerbqplayed) = $this->return_correct_and_played_answers();

        $winnerid = 0;

        if ($playeracorrect < $playerbcorrect) {
            $winnerid = $this->gamedata->playerbid;
        } else if ($playeracorrect > $playerbcorrect) {
            $winnerid = $this->gamedata->playeraid;
        }

        return [$winnerid, $playeracorrect, $playerbcorrect, $playeraqplayed, $playerbqplayed];
    }

    /**
     * Returns correct and played answers as ids.
     * @return int[]
     */
    private function return_correct_and_played_answers() {
        $playerascore = 0;
        $playerbscore = 0;
        $playeraqplayed = 0;
        $playerbqplayed = 0;
        foreach ($this->gamedata->questions as $question) {
            if ($question->playeraanswered == 2) {
                ++$playerascore;
            }
            if ($question->playerbanswered == 2) {
                ++$playerbscore;
            }
            if (!empty($question->playeraanswered)) {
                ++$playeraqplayed;
            }
            if (!empty($question->playerbanswered)) {
                ++$playerbqplayed;
            }
        }
        return [$playerascore, $playerbscore, $playeraqplayed, $playerbqplayed];
    }

    /**
     * Returns string to display on website to see played and correct answers.
     * @return array
     * @throws dml_exception
     * @throws moodle_exception
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
     * @param stdClass $category
     * @return array
     * @throws \coding_exception
     */
    private function return_playable_questions_for_category(stdClass $category) {

        $returnarray = [];

        $mooduell = $this->mooduell;

        // We do questions fetching only once. If we have cached questions, we use those.
        if (empty($mooduell->questions)) {
            $mooduell->return_list_of_all_questions_in_quiz();
        } // Else do nothing.

        foreach ($mooduell->questions as $question) {
            if ($question->category == $category->category
                    && $question->status == get_string('ok', 'mod_mooduell')) {
                $returnarray[] = $question;
            }
        }

        return $returnarray;
    }

    /**
     * Sends push notifications to google Firebase.
     * @param string $messagetype
     * @return bool|string|void
     * @throws \coding_exception
     * @throws dml_exception
     */
    private function send_push_notification(string $messagetype) {
        $pushenabled = get_config('mooduell', 'enablepush');

        if ($pushenabled) {

            $fields = $this->gather_notifcation_data($messagetype);

            // If the user has no pushtokens, we abort.
            if (!$fields) {
                return;
            }

            $apiaccesskey = get_config('mooduell', 'pushtoken');

            $headers = array
            (
                    'Authorization: key=' . $apiaccesskey,
                    'Content-Type: application/json'
            );
            $ch = curl_init();
            curl_setopt( $ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send' );
            curl_setopt( $ch, CURLOPT_POST, true );
            curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
            curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
            curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $fields ) );
            $result = curl_exec($ch );
            curl_close( $ch );
            return $result;
        }
        return;
    }
}
