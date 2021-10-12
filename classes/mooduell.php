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
 * Plugin event observers are registered here.
 *
 * @package mod_mooduell
 * @copyright 2020 Wunderbyte Gmbh <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mooduell;

defined('MOODLE_INTERNAL') || die();

use \moodle_url;
use coding_exception;
use context_module;
use core_customfield\category;
use dml_exception;
use mod_mooduell\game_control;
use mod_mooduell\output\viewpage;
use mod_mooduell\output\viewpagestudents;
use mod_mooduell\output\viewquestions;
use mod_mooduell_mod_form;
use moodle_exception;
use stdClass;



global $CFG;

require_once("{$CFG->dirroot}/mod/mooduell/classes/qr_code.php");

// Question Health constants.
/**
 * @var int MAXLENGTH of question text
 */
const MAXLENGTH = 400;
/**
 * @var int MINLENGTH of question text
 */
const MINLENGTH = 0;
/**
 * @var bool NEEDIMAGE question needs image
 */
const NEEDIMAGE = false;
/**
 * @var array ACCEPTEDTYPES accepted question types
 */
const ACCEPTEDTYPES = [
        'truefalse',
        'multichoice',
        'singlechoice',
        'numerical'
];

/**
 * Class mooduell
 *
 * @package mod_mooduell
 */
class mooduell {

    /**
     * @var stdClass|null fieldset record of mooduell instance
     */
    public $settings = null;

    /**
     * @var bool|false|mixed|stdClass|null course object
     */
    public $course = null;

    /**
     * @var stdClass|null course module
     */
    public $cm = null;

    /**
     * @var stdClass|null context
     */
    public $context = null;


    /**
     * @var array
     */
    public $questions = array();

    /**
     * @var array
     */
    public $usernames = array();

    /**
     * Mooduell constructor.
     * Fetches MooDuell settings from DB.
     * @param int|null $id
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function __construct($id = null) {
        global $DB, $PAGE;

        global $PAGE;

        if (!$this->cm = get_coursemodule_from_id('mooduell', $id)) {
            throw new moodle_exception('invalidcoursemodule ' . $id, 'mooduell', null, null, "Course module id: $id");
        }

        $this->course = get_course($this->cm->course);

        if (!$this->settings = $DB->get_record('mooduell', array(
                'id' => $this->cm->instance
        ))) {
            throw new moodle_exception('invalidmooduell', 'mooduell', null, null, "Mooduell id: {$this->cm->instance}");
        }
        $this->context = context_module::instance($this->cm->id);

        $PAGE->set_context($this->context);
    }

    /**
     * Get MooDuell object by instanceid (id of mooduell table)
     * @param int $instanceid
     * @return mooduell
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public static function get_mooduell_by_instance(int $instanceid) {
        $cm = get_coursemodule_from_instance('mooduell', $instanceid);
        return new mooduell($cm->id);
    }

    /**
     * Create a mooduell instance.
     * @param stdClass $formdata
     * @return bool|int
     * @throws dml_exception
     */
    public static function add_instance(stdClass $formdata) {
        global $DB;
        // Add the database record.
        $data = new stdClass();
        $data->name = $formdata->name;
        $data->timemodified = time();
        $data->timecreated = time();
        $data->course = $formdata->course;
        $data->courseid = $formdata->course;
        $data->intro = $formdata->intro;
        $data->introformat = $formdata->introformat;
        $data->countdown = isset($formdata->countdown) ? $formdata->countdown : 0;
        $data->waitfornextquestion = isset($formdata->waitfornextquestion) ? $formdata->waitfornextquestion : 0;
        $data->usefullnames = isset($formdata->usefullnames) ? $formdata->usefullnames : 0;
        $data->showcontinuebutton = isset($formdata->showcontinuebutton) ? $formdata->showcontinuebutton : 0;
        $data->showcorrectanswer = isset($formdata->showcorrectanswer) ? $formdata->showcorrectanswer : 0;
        $data->quizid = (!empty($formdata->quizid) && $formdata->quizid > 0) ? $formdata->quizid : null;

        $mooduellid = $DB->insert_record('mooduell', $data);

        // Add postprocess function.

        self::update_categories($mooduellid, $formdata);

        return $mooduellid;
    }

    /**
     * Function is called on creating or updating MooDuell Quiz Settings.
     * One Quiz can have one or more categories-entries.
     * This function has to make sure creating and updating results in the correct DB entries.
     * @param int $mooduellid
     * @param object $formdata
     * @return void|null
     * @throws dml_exception
     */
    public static function update_categories(int $mooduellid, object $formdata) {
        global $DB;

        $categoriesarray = [];

        $counter = 0;
        $groupname = 'categoriesgroup' . $counter;

        while (isset($formdata->$groupname)) {

            $entry = new stdClass();
            $newrecord = (object) $formdata->$groupname;
            $entry->category = $newrecord->category;
            $entry->weight = $newrecord->weight;
            $categoriesarray[] = $entry;

            $counter++;
            $checkboxname = "addanothercategory" . $counter;
            $groupname = 'categoriesgroup' . $counter;
            if (!isset($formdata->$checkboxname)) {
                break;
            }
        }

        // Write categories to categories table.
        if (count($categoriesarray) > 0) {

            // First we have to check if we have any category entry for our Mooduell Id.
            $foundrecords = $DB->get_records('mooduell_categories', ['mooduellid' => $mooduellid]);
            $newrecords = $categoriesarray;

            // If there is no categoriesgroup in Formdata at all, we abort.
            if (!$newrecords || count($newrecords) == 0) {
                return;
            }

            // Else we determine if we have more new or old records and set $i accordingly.
            $max = count($foundrecords) >= count($newrecords) ? count($foundrecords) : count($newrecords);
            $i = 0;

            while ($i < $max) {

                $foundrecord = count($foundrecords) > 0 ? array_pop($foundrecords) : null;
                $newrecord = count($newrecords) > 0 ? array_pop($newrecords) : null;

                // If we have still a foundrecord left, we update it.
                if ($foundrecord && $newrecord) {
                    $data = new stdClass();
                    $data->id = $foundrecord->id;
                    $data->mooduellid = $mooduellid;
                    $data->category = $newrecord->category;
                    $data->weight = $newrecord->weight;
                    $DB->update_record('mooduell_categories', $data);
                } else if ($foundrecord) {
                    // Else we have more foundrecords than new recors, we delete the found ones.
                    $DB->delete_records('mooduell_categories', array('id' => $foundrecord->id));
                } else {
                    $data = new stdClass();
                    $data->mooduellid = $mooduellid;
                    $data->category = $newrecord->category;
                    $data->weight = $newrecord->weight;
                    $DB->insert_record('mooduell_categories', $data);
                }
                $i++;
            }
        }

        return null;
    }

    /**
     * Function to display page.
     * @param bool|null $inline
     * @param string|null $pagename
     * @param string $gameid
     * @return string
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function display_page(bool $inline = null, string $pagename = null, $gameid = '') {
        global $PAGE;

        $output = $PAGE->get_renderer('mod_mooduell');
        $data = [];

        $out = '';
        if (!$inline) {
            $out .= $output->header();
        }

        switch ($pagename) {
            case null:
                // Create the list of open games we can pass on to the renderer.
                $data['opengames'] = [];
                $data['finishedgames'] = [];
                $data['warnings'] = $this->check_quiz();

                // Add the Name of the instance.
                $data['quizname'] = $this->cm->name;
                $data['mooduellid'] = $this->cm->id;
                // Add the list of questions.
                $data['questions'] = [];
                $data['highscores'] = [];
                $data['categories'] = $this->return_list_of_categories();
                $data['statistics'] = $this->return_list_of_statistics_teacher();
                // Use the viewpage renderer template.
                $viewpage = new viewpage($data);
                $out .= $output->render_viewpage($viewpage);
                break;
            case 'questions':
                // Create the list of questions  we can pass on to the renderer.
                $mooduellgame = new game_control($this, $gameid);
                $gamedata = $mooduellgame->get_questions();
                $data['questions'] = $gamedata->questions;
                // Use the viewquestions renderer template.
                // Add the Name of the instance.
                $data['quizname'] = $this->cm->name;
                $data['mooduellid'] = $this->cm->id;
                $viewquestions = new viewpage($data);
                $out .= $output->render_viewquestions($viewquestions);
                break;
            case 'studentsview':
                $qrcode = new qr_code();
                $qrcodeimage = $qrcode->generate_qr_code();
                // Create the list of open games we can pass on to the renderer.
                $data['qrimage'] = $qrcodeimage;
                $data['statistics'] = $this->return_list_of_statistics_student();
                $data['opengames'] = [];
                $data['finishedgames'] = [];
                $data['highscores'] = [];
                // Add the Name of the instance.
                $data['opengames'] = [];
                $data['finishedgames'] = [];
                $data['highscores'] = [];
                // Add the name of the instance.
                $data['quizname'] = $this->cm->name;
                $viewpage = new viewpage($data);
                $out .= $output->render_viewpagestudents($viewpage);
                break;
            case 'downloadhighscores':
                $listofhighscores = $this->return_list_of_highscores();
                $headline = [
                        get_string('rank', 'mod_mooduell'),
                        get_string('username', 'mod_mooduell'),
                        get_string('gamesplayed', 'mod_mooduell'),
                        get_string('gameswon', 'mod_mooduell'),
                        get_string('gameslost', 'mod_mooduell'),
                        get_string('score', 'mod_mooduell'),
                        get_string('correctlyanswered', 'mod_mooduell'),
                        get_string('correctlyansweredpercentage', 'mod_mooduell'),
                        get_string('questions_played', 'mod_mooduell')
                        ];
                $this->export_data_as_csv($headline, $listofhighscores);
        }

        if (!$inline) {
            $out .= $output->footer();
        }
        return $out;
    }

    /**
     * We return an array which we can then pass on to our mustache template
     * containing
     * - pageheading (Title on top of the page)
     * - tableheading (heading for the colums of the table)
     * - games (for every game a row with multiple columns)
     * - warning (if necessary)
     * @param false $student
     * @param null $finished
     * @param int $timemodified
     * @return array
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function return_list_of_games($student = false, $finished = null, $timemodified = 0) {

        global $DB;

        $games = $this->return_games_for_this_instance($student, $finished, $timemodified);

        $returngames = [];

        foreach ($games as $game) {

            $returngames[] = [
                    'mooduellid' => $game->mooduellid,
                    'gameid' => $game->id,
                    "playera" => $this->return_name_by_id($game->playeraid),
                    'playerb' => $this->return_name_by_id($game->playerbid),
                    'playeraresults' => $game->playeraresults,
                    'playerbresults' => $game->playerbresults
            ];
        }
        return $returngames;
    }

    /**
     * This function returns all possible questions for this quiz.
     * This is determined by the specified categories.
     * They are not yet linked to a special game.
     * This function is meant for display on browser, not for webservice.
     * It replaces category-id already with category-name.
     * It stores the list of questions in $this->questions for performance.
     * @return array
     * @throws dml_exception
     */
    public function return_list_of_all_questions_in_quiz() {

        if ($this->questions && count($this->questions) > 0) {
            return $this->questions;
            return $this->questions;
        }

        global $DB;

        $questions = array();

        $listofquestions = $this->return_list_of_questions();
        $listofanswers = $this->return_list_of_answers();

        foreach ($listofquestions as $entry) {
            $newquestion = new question_control($entry, $listofanswers);
            $questions[] = $newquestion;
        }

        $this->questions = $questions;

        return $questions;

    }

    /**
     * Returns list of highscores.
     * @return array
     * @throws dml_exception
     */
    public function return_list_of_highscores() {

        $list = self::get_highscores($this->cm->instance);
        $returnarray = [];

        foreach ($list as $entry) {
            $entry = (object) $entry;
            $returnarray[] = [
                    'rank' => $entry->rank,
                    'username' => $this->return_name_by_id($entry->userid),
                    'gamesplayed' => $entry->played,
                    'gameswon' => $entry->won,
                    'gameslost' => $entry->lost,
                    'score' => $entry->score,
                    'correct' => $entry->correct,
                    'correctpercentage' => $entry->correctpercentage,
                    'qplayed' => $entry->qplayed
            ];
        }

        usort($returnarray, $this->build_sorter('score'));

        return $returnarray;
    }

    /**
     * Returns list of question.
     * @return array
     * @throws dml_exception
     */
    private function return_list_of_questions() {

        global $DB;

        $mooduellid = $this->cm->instance;

        $sql = "SELECT DISTINCT q.*, qc.contextid, qc.name AS categoryname
                FROM {mooduell_categories} mc
                JOIN {question_categories} qc
                ON mc.category=qc.id
                JOIN {question} q
                ON q.category=qc.id
                WHERE mc.mooduellid=$mooduellid";

        if (!$listofquestions = $DB->get_records_sql($sql)) {
            return [];
        }
        return $listofquestions;
    }

    /**
     * Function to fetch all answers for this instance, but before runnig through instantiation.
     * @return array
     * @throws dml_exception
     */
    private function return_list_of_answers() {

        global $DB;

        $mooduellid = $this->cm->instance;

        $sql = "SELECT DISTINCT qa.*
                FROM {mooduell_categories} mc
                JOIN {question_categories} qc
                ON mc.category=qc.id
                JOIN {question} q
                ON q.category=qc.id
                JOIN {question_answers} qa
                ON qa.question=q.id
                WHERE mc.mooduellid=$mooduellid";

        if (!$listofanswers = $DB->get_records_sql($sql)) {
            return [];
        }
        return $listofanswers;
    }

    /**
     * Sorter function.
     * @param string $key
     * @return \Closure
     */
    public static function build_sorter(string $key) {
        return function ($a, $b) use ($key) {
            return $a[$key] < $b[$key];
        };
    }

    /**
     * Retrieve all games linked to this MooDuell instance from $DB and return them as an array of std.
     * @param false $studentview
     * @param false $finished
     * @param int $timemodified
     * @return array
     * @throws dml_exception
     */
    public function return_games_for_this_instance($studentview = false, $finished = false, $timemodified = -1) {
        global $DB;
        global $USER;

        $instanceid = $this->cm->instance;

        $sql = "SELECT * FROM {mooduell_games} WHERE mooduellid = $instanceid";

        if ($studentview) {
                $sql .= " AND (playeraid = $USER->id OR playerbid = $USER->id)";
        }

        if ($timemodified > 0) {
            $sql .= " AND timemodified > $timemodified";
        };

        if ($finished === null) {
            // Do nothing -> return finished and unfinished games.
            $sql .= "";
        } else if ($finished) {
            $sql .= " AND status = 3";
        } else if ($finished === false) {
            $sql .= " AND status <> 3";
        } // If finished is NULL, then do nothing -> return finished and unfinished games.

        $games = $DB->get_records_sql($sql);

        if ($games && count($games) > 0) {
            return $games;
        } else {
            return [];
        }
    }

    /**
     * Get pushtokens for user.
     * @param int $userid
     * @return array
     * @throws dml_exception
     */
    public static function get_pushtokens(int $userid) {

        global $DB, $USER;

        $data = $DB->get_records('mooduell_pushtokens', array('userid' => $userid));
        $returndata = [];
        if ($data && count($data) > 0) {
            foreach ($data as $entry) {

                $returndata[] = [
                        'identifier' => $entry->identifier,
                        'model' => $entry->model,
                        'pushtoken' => $entry->pushtoken
                ];
            }
        }

        return [
                'userid' => $userid,
                'pushtokens' => $returndata,
                ];
    }

    /**
     * Pushtokens have to be verified for validity.
     * This verification can only be done by other users sending push tokens.
     * If a push token is no longer connected to a device, it return an error.
     * Therefore, this function allows users to set push tokens of other users.
     * This is not great, but it works for the moment.
     * @param int $userid
     * @param string $model
     * @param string $identifier
     * @param string $pushtoken
     * @return int[]
     * @throws dml_exception
     */
    public static function set_pushtoken(int $userid, string $model, string $identifier, string $pushtoken) {

        global $DB, $USER;

        $data = $DB->get_record('mooduell_pushtokens', array('userid' => $userid, 'identifier' => $identifier));

        $updatedata = [
                'userid' => $userid,
                'model' => $model,
                'identifier' => $identifier,
                'pushtoken' => $pushtoken,
                'numberofnotifications' => 0
        ];

        if ($data) {
            $updatedata['id'] = $data->id;
            $DB->update_record('mooduell_pushtokens', $updatedata);
        } else {
            $DB->insert_record('mooduell_pushtokens', $updatedata);
        }

        return ['status' => 1];
    }

    /**
     * This function takes mooduell or $cmid, depending on the context.
     * @param int|null $mooduellid
     * @param int|null $cmid
     * @return array
     * @throws dml_exception
     * @throws moodle_exception
     */
    public static function get_highscores($mooduellid = null, $cmid = null) {

        global $DB, $USER;

        // If there was no mooduellid, we have to retrieve it here.
        if (!$mooduellid) {
            if (!$mooduellid = $DB->get_field('course_modules', 'instance', array('id' => $cmid))) {
                throw new moodle_exception('mooduellinstancedoesnotexist', 'mooduell', null, null,
                        "This MooDuell Instance does not exist.");
            }
        }

        $temparray = [];

        // Get all the finished games.
        // If we have a quizid, we only get highscore for one special game...
        // ...if there is no quiz id, we get highscore for all the games.
        if ($mooduellid != 0) {
            $data = $DB->get_records('mooduell_games', array('mooduellid' => $mooduellid));
        } else {
            $data = $DB->get_records('mooduell_games');
        }

        $temparray = [];

        foreach ($data as $entry) {
            // Get the scores.

            $playera = new stdClass();
            $playerb = new stdClass();

            // We count correct and played questions even if the game was not finsihed.
            $playera->correct = $entry->playeracorrect;
            $playerb->correct = $entry->playerbcorrect;

            // If we updated from the old version, we have null as default at this place...
            // ... and we have to calculate the qplayed.
            if (!$entry->playeraqplayed) {
                $notplayed = substr_count($entry->playeraresults, '-');
                $playera->qplayed = 9 - $notplayed;
            } else {
                $playera->qplayed = $entry->playeraqplayed;
            }

            // If we updated from the old version, we have null as default at this place...
            // ... and we have to calculate the qplaed.
            // ... and we have to calculate the qplayed.
            if (!$entry->playerbqplayed) {
                $notplayed = substr_count($entry->playerbresults, '-');
                $playerb->qplayed = 9 - $notplayed;
            } else {
                $playerb->qplayed = $entry->playerbqplayed;
            }

            // Player A.
            $playera->played = 0; // Games played.
            $playera->won = 0;
            $playera->lost = 0;
            $playera->score = 0;

            // Player B
            // Player B.
            $playerb->played = 0; // Games played.
            $playerb->won = 0;
            $playerb->lost = 0;
            $playerb->score = 0;

            if ($entry->status == 3) {
                $playera->played = 1;
                $playerb->played = 1;

                switch ($entry->winnerid) {
                    case 0:
                        $playera->won = 0;
                        $playera->lost = 0;
                        $playerb->lost = 0;
                        $playerb->won = 0;
                        $playera->score = 1;
                        $playerb->score = 1;
                        break;
                    case ($entry->winnerid === $entry->playeraid):
                        $playera->won = 1;
                        $playera->lost = 0;
                        $playerb->lost = 1;
                        $playerb->won = 0;
                        $playera->score = 3;
                        $playerb->score = 0;
                        break;
                    case ($entry->winnerid === $entry->playerbid):
                        $playera->won = 0;
                        $playera->lost = 1;
                        $playerb->lost = 0;
                        $playerb->won = 1;
                        $playera->score = 0;
                        $playerb->score = 3;
                        break;
                }
            }

            if (!isset($temparray[$entry->playeraid])) {
                $temparray[$entry->playeraid] = $playera;
            } else {
                self::add_score($temparray[$entry->playeraid], $playera);
            }
            if (!isset($temparray[$entry->playerbid])) {
                $temparray[$entry->playerbid] = $playerb;
            } else {
                self::add_score($temparray[$entry->playerbid], $playerb);
            }
        }
        $arraywithoutranks = [];
        foreach ($temparray as $key => $value) {

            // If quizid = 0, we only return active user, else we return all users.
            if ($mooduellid == 0 && $key != $USER->id) {
                continue;
            }

            $entry = [];
            $entry['quizid'] = $cmid ? $cmid : $mooduellid;
            $entry['userid'] = $key;
            $entry['score'] = $value->score;
            $entry['won'] = $value->won;
            $entry['lost'] = $value->lost;
            $entry['played'] = $value->played;
            $entry['correct'] = $value->correct;

            if (!empty($value->qplayed) and $value->qplayed > 0) {
                // Determine percentage of correctly answered questions by division through played questions.
                $entry['correctpercentage'] = number_format((( $value->correct / $value->qplayed ) * 100), 1);
                $entry['qplayed'] = $value->qplayed;
            } else {
                $entry['correctpercentage'] = 0;
                $entry['qplayed'] = 0;
            }

            $arraywithoutranks[] = $entry;
        }

        usort($arraywithoutranks, self::build_sorter('score'));

        // Now add the correct ranks to the sorted array.
        $arraywithranking = [];
        $previousscore = false;
        $previousrank = false;
        $index = 0;
        foreach ($arraywithoutranks as $record) {
            $index++;
            // Same rank if the scores are the same.
            if ($previousscore == $record['score']) {
                $record['rank'] = $previousrank;
            } else {
                // In all other cases use index as rank.
                $record['rank'] = $index;
            }
            $previousscore = $record['score'];
            $previousrank = $record['rank'];
            $arraywithranking[] = $record;
        }
        return $arraywithranking;
    }

    /**
     * Helper function for get_highscores
     * @param stdClass $storedplayer
     * @param stdClass $newentry
     */
    private static function add_score(stdClass $storedplayer, stdClass $newentry) {
        $storedplayer->score += $newentry->score;
        $storedplayer->won += $newentry->won;
        $storedplayer->lost += $newentry->lost;
        $storedplayer->played += $newentry->played;
        $storedplayer->correct += $newentry->correct;
        $storedplayer->qplayed += $newentry->qplayed;
    }

    /**
     * Allows us to securely retrieve the (user)name of a user by id.
     * @param int $userid
     * @return mixed|string
     * @throws coding_exception
     * @throws dml_exception
     */
    public function return_name_by_id(int $userid) {
        global $DB, $CFG;

        require_once("$CFG->dirroot/user/profile/lib.php");

        // Caching to speed things up significantly.
        if (isset($this->usernames[$userid])) {
            return $this->usernames[$userid];
        }

        $usefullnames = $this->settings->usefullnames;

        // Get user record of user.
        $user = $DB->get_record('user', array('id' => $userid));

        profile_load_custom_fields($user);

        if (!$user->profile['mooduell_alias'] && strlen($user->alternatename) > 0) {
            $user->profile_field_mooduell_alias = $user->alternatename;
            profile_save_data($user);
        }

        $returnstring = '';
        if ($usefullnames != 1) {
            if ($user->profile['mooduell_alias'] && strlen($user->profile['mooduell_alias']) > 0) {
                $returnstring = $user->profile['mooduell_alias'];
            } else {
                $returnstring = get_string('userhasnonickname', 'mod_mooduell');
            }

        } else {
            $returnstring = "$user->firstname $user->lastname";
        }

        // Cache if we have to fetch it again.
        $this->usernames[$userid] = $returnstring;

        return $returnstring;
    }

    /**
     * Set base params for page and trigger module viewed event.
     * @throws coding_exception
     */
    public function view_page() {
        global $PAGE;
        $event = event\course_module_viewed::create(array(
                'objectid' => $this->cm->instance,
                'context' => $this->context
        ));
        $event->add_record_snapshot('course', $this->course);
        $event->add_record_snapshot('mooduell', $this->settings);
        $event->trigger();

        $PAGE->set_url('/mod/mooduell/view.php', array(
                'id' => $this->cm->id
        ));
        $PAGE->set_title(format_string($this->settings->name));
        $PAGE->set_heading(format_string($this->course->fullname));
        $PAGE->set_context($this->context);
    }

    /**
     * Check if user exists.
     * @param int $userid
     * @return bool
     * @throws dml_exception
     */
    public function user_exists(int $userid) {
        global $DB;

        return $DB->record_exists('user', array('id' => $userid));
    }

    /**
     * This function deals with different actions we can call from settings.
     * @param string $action
     * @param int $gameid
     * @throws dml_exception
     */
    public function execute_action(string $action, int $gameid) {
        if ($action === 'delete' && $gameid) {
            $this->delete_game_by_id($gameid);
        }
    }

    /**
     * This function allows the teacher to delete games entirely from DB, including randomly selected questions.
     * @param int $gameid
     * @throws dml_exception
     */
    private function delete_game_by_id(int $gameid) {
        global $DB;

        $DB->delete_records('mooduell_games', array('id' => $gameid));
        $DB->delete_records('mooduell_questions', array('gameid' => $gameid));
    }

    /**
     * Check if quiz is playable.
     * @return array
     * @throws coding_exception
     * @throws dml_exception
     */
    public function check_quiz() {

        $returnarray = [];

        $questions = $this->return_list_of_all_questions_in_quiz();

        // Are there enough questions in the categories added?
        if (count($questions) < 9) {
            $returnarray[] = [
                    'id' => 1,
                    'message' => get_string('notenoughquestions', 'mod_mooduell')];
        }

        return $returnarray;
    }

    /**
     * Returns list of category.
     * @return array
     * @throws dml_exception
     */
    private function return_list_of_categories() {

        global $DB;

        $mooduellcategories = $DB->get_records('mooduell_categories', array('mooduellid' => $this->cm->instance));

        // If we have no categories, we return an empty array.
        if (!($mooduellcategories && is_array($mooduellcategories))) {
            return [];
        }

        $categoriesdata = [];

        foreach ($mooduellcategories as $moodcat) {
            $tempentry = $DB->get_record('question_categories', array('id' => $moodcat->category));
            $entry = [];
            $entry['catid'] = $tempentry->id;
            $entry['contextid'] = $tempentry->contextid;
            $entry['catname'] = $tempentry->name;
            $entry['courseid'] = $this->course->id;
            $categorydata[] = $entry;
        }

        return $categorydata;
    }

    /**
     * Helper function to generate statistical data
     * for tab "Statistics" (teacher view)
     * @return array
     * @throws dml_exception
     */
    private function return_list_of_statistics_teacher() {
        global $DB;

        $mooduellid = $this->cm->instance;

        $listofstatistics = [];
        $listofstatistics['cmid'] = $this->cm->id;
        $listofstatistics['courseid'] = $this->course->id;

        // Number of distinct users who have played a MooDuell game.
        $sql = "select count(*) active_users from (
                    select playeraid playerid from {mooduell_games}
                    where mooduellid = $mooduellid
                    union
                    select playerbid playerid from {mooduell_games}
                    where mooduellid = $mooduellid
                ) s"; // Info: union selects only distinct records.
        $numberofactiveusers = $DB->get_record_sql($sql)->active_users;
        $listofstatistics['number_of_active_users'] = $numberofactiveusers;

        // Number of MooDuell games started.
        $sql = "select count(*) games_played from {mooduell_games} where mooduellid = $mooduellid";
        $numberofgamesstarted = $DB->get_record_sql($sql)->games_played;
        $listofstatistics['number_of_games_started'] = $numberofgamesstarted;

        // Number of MooDuell games played.
        $sql = "select count(*) games_finished from {mooduell_games} where mooduellid = $mooduellid and status = 3";
        $numberofgamesfinished = $DB->get_record_sql($sql)->games_finished;
        $listofstatistics['number_of_games_finished'] = $numberofgamesfinished;

        // Number of answers returned to MooDuell questions.
        $sql = "select sum(s.answers) answers from
                (select count(playeraanswered) answers from {mooduell_questions}
                where playeraanswered is not null and mooduellid = $mooduellid
                union all
                select count(playerbanswered) answers from {mooduell_questions}
                where playerbanswered is not null and mooduellid = $mooduellid) s";
        $numberofanswers = $DB->get_record_sql($sql)->answers;
        $listofstatistics['number_of_answers'] = $numberofanswers;

        // Percentage of correctly answered questions.
        // Step 1: find out the number of correct answers returned to MooDuell questions.
        $sql = "select sum(s.correct_answers) correct_answers from
                (select count(playeraanswered) correct_answers from {mooduell_questions}
                where playeraanswered = 2 and mooduellid = $mooduellid
                union all
                select count(playerbanswered) correct_answers from {mooduell_questions}
                where playerbanswered = 2 and mooduellid = $mooduellid) s";
        $numberofcorrectanswers = $DB->get_record_sql($sql)->correct_answers;

        if (!empty($numberofcorrectanswers)) {
            // Step 2: calculate the percentage.
            $correctanswerspercentage = number_format((( $numberofcorrectanswers / $numberofanswers ) * 100), 1);
            $listofstatistics['percentage_of_correct_answers'] = $correctanswerspercentage;
        } else {
            $listofstatistics['percentage_of_correct_answers'] = false;
        }

        // Easiest question = question which has been answered correctly most often.
        $sql = "select s.questionid, q.questiontext questionname, count(*) correct_count from
                (select * from {mooduell_questions} where playeraanswered = 2 and mooduellid = $mooduellid
                union all
                select * from {mooduell_questions} where playerbanswered = 2 and mooduellid = $mooduellid) s
                inner join {question} q
                on q.id = s.questionid
                group by s.questionid, q.name, q.questiontext
                order by correct_count desc
                limit 1";

        $listofstatistics['eq_id'] = false;
        $listofstatistics['eq_name'] = "";
        $listofstatistics['eq_correct_count'] = 0;

        $entry = $DB->get_record_sql($sql);
        if (!empty($entry)) {
            $listofstatistics['eq_id'] = $entry->questionid;
            // Remove HTML tags and shorten to a maximum of 50 characters.
            if (strlen(strip_tags($entry->questionname)) > 50) {
                $listofstatistics['eq_name'] = substr(strip_tags($entry->questionname), 0, 50) . '... ?';
            } else {
                $listofstatistics['eq_name'] = strip_tags($entry->questionname);
            }
            $listofstatistics['eq_correct_count'] = $entry->correct_count;
        }

        // Hardest question = question which has been answered incorrectly most often.
        $sql = "select s.questionid, q.questiontext questionname, count(*) incorrect_count from
                (select * from {mooduell_questions} where playeraanswered = 1 and mooduellid = $mooduellid
                union all
                select * from {mooduell_questions} where playerbanswered = 1 and mooduellid = $mooduellid) s
                inner join {question} q
                on q.id = s.questionid
                group by s.questionid, q.name, q.questiontext
                order by incorrect_count desc
                limit 1";

        $listofstatistics['hq_id'] = false;
        $listofstatistics['hq_name'] = "";
        $listofstatistics['hq_incorrect_count'] = 0;

        $entry = $DB->get_record_sql($sql);
        if (!empty($entry)) {
            $listofstatistics['hq_id'] = $entry->questionid;
            // Remove HTML tags and shorten to a maximum of 50 characters.
            if (strlen(strip_tags($entry->questionname)) > 50) {
                $listofstatistics['hq_name'] = substr(strip_tags($entry->questionname), 0, 50) . '... ?';
            } else {
                $listofstatistics['hq_name'] = strip_tags($entry->questionname);
            }
            $listofstatistics['hq_incorrect_count'] = $entry->incorrect_count;
        }

        return $listofstatistics;
    }

    /**
     * Helper function to generate statistical data
     * for tab "Statistics" (student view)
     * @return array
     * @throws dml_exception
     * @throws moodle_exception
     */
    private function return_list_of_statistics_student() {
        global $DB;
        global $USER;

        $mooduellid = $this->cm->instance;

        $listofstatistics = [];
        $listofstatistics['courseid'] = $this->course->id;

        // Get user statistics.
        $userstats = game_control::get_user_stats( $USER->id, $mooduellid);

        // Number of distinct opponents who have played a MooDuell game...
        // ...against the current user.
        $sql = "select count(*)-1 opponents
                from (
                  select playeraid playerid from {mooduell_games}
                  where mooduellid = $mooduellid
                  and (playeraid = $USER->id or playerbid = $USER->id)
                  union
                  select playerbid playerid from {mooduell_games}
                  where mooduellid = $mooduellid
                  and (playeraid = $USER->id or playerbid = $USER->id)
                ) s"; // Info: union selects only distinct records.
        $numberofopponents = $DB->get_record_sql($sql)->opponents;
        // No game played yet.
        if ($numberofopponents == -1) {
            // This is a small trick, we create an array with an entry...
            // ... this allows us to control information in the mustache-template.
            $listofstatistics['nogames'] = [1];
            $numberofopponents = 0;
        }

        $listofstatistics['number_of_opponents'] = $numberofopponents;

        // Number of unfinished (open) MooDuell games having the current user involved.
        $sql = "select count(*) open_games from {mooduell_games}
                where mooduellid = $mooduellid
                and (playeraid = $USER->id or playerbid = $USER->id)
                and status <> 3";

        if ($data = $DB->get_record_sql($sql)) {
            $numberofopengames = $data->open_games;
        } else {
            $numberofopengames = 0;
        }
        $listofstatistics['number_of_open_games'] = $numberofopengames;

        // Number of finished MooDuell games having the current user involved.
        $listofstatistics['number_of_games_finished'] = $userstats['playedgames'];

        // Number of games won by the user.
        $listofstatistics['number_of_games_won'] = $userstats['wongames'];

        // Number of correct answers (given by the user).
        $listofstatistics['number_of_correct_answers'] = $userstats['correctlyanswered'];

        // Percentage of correctly answered questions.
        $numberofcorrectanswers = $userstats['correctlyanswered'];
        $numberofplayedquestions = $userstats['playedquestions'];
        if ($numberofcorrectanswers != 0) {
            $correctanswerspercentage = number_format((( $numberofcorrectanswers / $numberofplayedquestions ) * 100), 1);
        } else {
            $correctanswerspercentage = 0;
        }

        $listofstatistics['percentage_of_correct_answers'] = $correctanswerspercentage;

        return $listofstatistics;
    }

    /**
     * Function to export Data as CSV
     * It is necessary to add a headline, ie headline & data must have the same amount of columns.
     * @param array $headline
     * @param array $data
     */
    public function export_data_as_csv(array $headline, array $data) {
        global $CFG;

        require_once($CFG->libdir . '/csvlib.class.php');

        // Make sure data is valid.

        $headlinecount = count ($headline);

        $csvexport = new \csv_export_writer( 'semicolon' );
        $filename = $this->cm->name . '_highscores';
        $csvexport->set_filename ($filename, '.csv');

        $csvexport->add_data($headline);

        foreach ($data as $item) {
            if ($headlinecount != count($item)) {
                printf('data of this line is wrong', json_encode($item));
                continue;
            }
            $csvexport->add_data($item);
        }

        $csvexport->download_file();
    }
}
