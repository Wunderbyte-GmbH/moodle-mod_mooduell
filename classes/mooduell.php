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

use coding_exception;
use context_module;
use dml_exception;
use mod_mooduell\output\viewpage;
use mod_mooduell\output\viewpagestudents;
use mod_mooduell\output\viewquestions;
use mod_mooduell_mod_form;
use moodle_exception;
use stdClass;

defined('MOODLE_INTERNAL') || die();



// Question Health constants

// Define the right length of questiontext
const MAXLENGTH = 400;
const MINLENGTH = 0;

const NEEDIMAGE = false;
const ACCEPTEDTYPES = [
        'truefalse',
        'multichoice',
        'singlechoice'
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
     * Mooduell constructor.
     * Fetches MooDuell settings from DB.
     * @param int $id
     *            course module id
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function __construct(int $id = null) {
        global $DB;

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
    }

    /**
     * Get MooDuell object by instanceid (id of mooduell table)
     *
     * @param
     *            int
     * @return mooduell
     */
    public static function get_mooduell_by_instance(int $instanceid) {
        $cm = get_coursemodule_from_instance('mooduell', $instanceid);
        return new mooduell($cm->id);
    }

    /**
     * Create a mooduell instance.
     *
     * @param stdClass $formdata
     * @param mod_mooduell_mod_form $mform
     * @return bool|int
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
        $data->countdown = $formdata->countdown;
        $data->waitfornextquestion = $formdata->waitfornextquestion;
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
     *
     * @param $mooduellid
     * @param $formdata
     * @return void|null
     * @throws dml_exception
     */
    public static function update_categories($mooduellid, $formdata) {
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

            // First we have to check if we have any category entry for our Mooduell Id
            $foundrecords = $DB->get_records('mooduell_categories', ['mooduellid' => $mooduellid]);
            $newrecords = $categoriesarray;

            // If there is no categoriesgroup in Formdata at all, we abort.
            if (!$newrecords || count($newrecords) == 0) {
                return;
            }

            // Else we determine if we have more new or old records and set $i accordingly;
            $max = count($foundrecords) >= count($newrecords) ? count($foundrecords) : count($newrecords);
            $i = 0;

            while ($i < $max) {

                $foundrecord = count($foundrecords) > 0 ? array_pop($foundrecords) : null;
                $newrecord = count($newrecords) > 0 ? array_pop($newrecords) : null;

                // If we have still a foundrecord left, we update it
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
     * Get the html of the view page.
     *
     * @param bool $inline
     *            Display without header and footer?
     * @return string
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
                $data = $this->return_list_of_games();
                // Add the Name of the instance
                $data['quizname'] = $this->cm->name;
                // Add the list of questions
                $data['questions'] = $this->return_list_of_all_questions_in_quiz();
                $data['highscores'] = $this->return_list_of_highscores();
                // Use the viewpage renderer template
                $viewpage = new viewpage($data);
                $out .= $output->render_viewpage($viewpage);
                break;
            case 'questions':
                // Create the list of questions  we can pass on to the renderer.
                $mooduellgame = new game_control($this, $gameid);
                $gamedata = $mooduellgame->get_questions();
                $data['questions'] = $gamedata->questions;
                // Use the viewquestions renderer template
                // Add the Name of the instance
                $data['quizname'] = $this->cm->name;
                $viewquestions = new viewquestions($data);
                $out .= $output->render_viewquestions($viewquestions);
                break;
            case 'studentsview':
                // Create the list of open games we can pass on to the renderer.
                $data = $this->return_list_of_games(true);
                // Add the Name of the instance
                $data['quizname'] = $this->cm->name;
                $data['highscores'] = $this->return_list_of_highscores();
                $viewpage = new viewpagestudents($data);
                $out .= $output->render_viewpagestudents($viewpage);
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
     *
     * @return array
     */
    public function return_list_of_games($student = false) {

        $returnwarnings = $this->check_quiz();

        $games = $this->return_games_for_this_instance($student);


        foreach ($games as $game) {

            $results = $game->return_status();
            if ($game->gamedata->status != 3) {
                $openreturngames[] = [
                        'mooduellid' => $this->cm->id,
                        'gameid' => $game->gamedata->gameid,
                        "playera" => $this->return_name_by_id($game->gamedata->playeraid),
                        'playerb' => $this->return_name_by_id($game->gamedata->playerbid),
                        'playeraresults' => $results[0],
                        'playerbresults' => $results[1]
                ];
            } else {
                $finishedreturngames[] = [
                        'mooduellid' => $this->cm->id,
                        'gameid' => $game->gamedata->gameid,
                        "playera" => $this->return_name_by_id($game->gamedata->playeraid),
                        'playerb' => $this->return_name_by_id($game->gamedata->playerbid),
                        'playeraresults' => $results[0],
                        'playerbresults' => $results[1]
                ];
            }
        }

        $returnobject = [
                'opengames' => $openreturngames,
                'finishedgames' => $finishedreturngames,
                'warnings' => $returnwarnings
        ];

        return $returnobject;
    }

    /**
     * This function returns all possible questions for this quiz.
     * This is determined by the specified categories.
     * They are not yet linked to a special game.
     * This function is meant for display on browser, not for webservice.
     * It replaces category-id already with category-name.
     */
    public function return_list_of_all_questions_in_quiz() {

        global $DB;

        // First, the added categories have to be determined.
        $categorydata = $DB->get_records('mooduell_categories', array('mooduellid' => $this->cm->instance));

        // Then the SQL query is built from the relevant categories.
        $sql = 'SELECT * FROM {question} WHERE';
        $i = 0;
        foreach ($categorydata as $entry) {
            $sql .= ' category = ' . $entry->category;
            if ($i < count($categorydata) - 1) {
                $sql .= ' OR';
            }
            $i++;
        }
        $sql .= ';';

        // The questions are fetched from DB and built via question class.
        // On the way, we replace category number by category-name.
        $questions = [];
        $data = $DB->get_records_sql($sql);
        foreach ($data as $entry) {
            $newQuestion = new question_control(($entry));
            $questions[] = $newQuestion;
        }

        return $questions;

    }

    /**
     * @return array[]
     * @throws dml_exception
     * @throws moodle_exception
     */
    private function return_list_of_highscores() {

        $list = self::get_highscores($this->cm->id);
        $returnarray = [];

        foreach ($list as $entry) {
            $entry = (object) $entry;
            $returnarray[] = [
                    'username' => $this->return_name_by_id($entry->userid),
                    'gamesplayed' => $entry->played,
                    'gameswon' => $entry->won,
                    'gameslost' => $entry->lost,
                    'score' => $entry->score
            ];
        }

        usort($returnarray, $this->build_sorter('score'));

        return $returnarray;
    }

    /**
     * @param $key
     * @return \Closure
     */
    private function build_sorter($key) {
        return function ($a, $b) use ($key) {
            return $a[$key] < $b[$key];
        };
    }

    /**
     * Retrieve all games linked to this MooDuell instance from $DB and return them as an array of std.
     *
     * @param false $studentview
     * @param int $timemodified
     * @return array
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function return_games_for_this_instance($studentview = false, $timemodified = -1) {
        global $DB;
        global $USER;

        $returnedgames = array();

        $games = $DB->get_records('mooduell_games', [
                'mooduellid' => $this->cm->instance
        ]);

        if ($games && count($games) > 0) {

            foreach ($games as $gamedata) {

                // If we only want to deal with games that were added since the last time we checked.
                if ($timemodified > $gamedata->timemodified) {
                    continue;
                }

                // If we are a student, we only want to include games where the user is active
                // We only want to include games where the active user is involved.
                if ($studentview) {
                    if ($gamedata->playeraid != $USER->id && $gamedata->playerbid != $USER->id) {
                        continue;
                    }
                }

                // First we create a game instance for every game.
                $game = new game_control($this, null, $gamedata);

                $returnedgames[] = $game;
            }
        }

        return $returnedgames;
    }

    public static function get_highscores($quizid) {

        global $DB;

        $temparray = [];

        // Get all the finished games.

        $mooduellrecord = $DB->get_record('course_modules', array('id' => $quizid));

        if (!$mooduellrecord || !$mooduellrecord->instance) {
            throw new moodle_exception('mooduellinstancedoesnotexist', 'mooduell', null, null,
                    "This MooDuell Instance does not exist.");
        }

        $data = $DB->get_records('mooduell_games', array('status' => 3, 'mooduellid' => $mooduellrecord->instance));

        $temparray = [];

        foreach ($data as $entry) {
            // Get the scores.

            $playera = new stdClass();
            $playerb = new stdClass();

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
            if (!array_key_exists($entry->playeraid, $temparray)) {
                $temparray[$entry->playeraid] = $playera;
            } else {
                self::add_score($temparray[$entry->playeraid], $playera);
            }
            if (!array_key_exists($entry->playerbid, $temparray)) {
                $temparray[$entry->playerbid] = $playerb;
            } else {
                self::add_score($temparray[$entry->playerbid], $playerb);
            }
        }
        $returnarray = [];
        foreach ($temparray as $key => $value) {
            $entry = [];
            $entry['userid'] = $key;
            $entry['score'] = $value->score;
            $entry['won'] = $value->won;
            $entry['lost'] = $value->lost;
            $entry['played'] = $value->played;
            $returnarray[] = $entry;
        }

        return $returnarray;

    }

    /**
     * Helper function for get_highscores
     *
     * @param $storedplayer
     * @param $newentry
     */
    private function add_score($storedplayer, $newentry) {
        $storedplayer->score += $newentry->score;
        $storedplayer->won += $newentry->won;
        $storedplayer->lost += $newentry->lost;
        $storedplayer->played += $newentry->played;
    }

    /**
     * Allows us to securely retrieve the (user)name of a user by id.
     *
     * @param int $userid
     * @return string
     * @throws dml_exception
     */
    public function return_name_by_id(int $userid) {
        global $DB;
        // Doesn't work via webservice, no instance?
        // $userarray = \user_get_users_by_id([$userid]);

        // Therefore, we have to do it manually.
        $userarray = $DB->get_records_list('user', 'id', [
                $userid
        ]);

        if ($userarray && $userarray[$userid] && $userarray[$userid]->username) {
            $playeraname = $userarray[$userid]->firstname . " " . $userarray[$userid]->lastname;
        } else {
            $playeraname = "dummyname";
        }
        return $playeraname;
    }

    /**
     * Set base params for page and trigger module viewed event.
     *
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
     *
     * @param
     *            int
     * @return bool
     */
    public function user_exists(int $userid) {
        global $DB;

        // Doesn't work via webservice, no instance?
        // $userarray = \user_get_users_by_id([$userid]);

        // Therefore, we have to do it manually.
        $userarray = $DB->get_records_list('user', 'id', [
                $userid
        ]);

        if ($userarray && $userarray[$userid]) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * This function deals with different actions we can call from settings.
     *
     * @param $action
     * @param $gameid
     * @throws dml_exception
     */
    public function execute_action($action, $gameid) {
        if ($action === 'delete' && $gameid) {
            $this->delete_game_by_id($gameid);
        }
    }

    /**
     * This function allows the teacher to delete games entirely from DB, including randomly selected questions.
     *
     * @param $gameid
     * @throws dml_exception
     */
    private function delete_game_by_id($gameid) {
        global $DB;

        $DB->delete_records('mooduell_games', array('id' => $gameid));
        $DB->delete_records('mooduell_questions', array('gameid' => $gameid));
    }

    public function check_quiz() {

        $returnarray = [];

        // TODO: Check each question individually
        $questions = $this->return_list_of_all_questions_in_quiz();

        // Are there enough questions in the categories added?
        if (count($questions) < 9) {
            $returnarray[] = [
                    'id' => 1,
                    'message' => get_string('notenoughquestions', 'mod_mooduell')];
        }

        // TODO: Add further checks



        return $returnarray;
    }
}
