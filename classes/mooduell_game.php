<?php
// This file is part of mod_datalynx for Moodle - http://moodle.org/
//
// It is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// It is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace mod_mooduell;

use stdClass;
use DateTime;

class mooduell_game {

    /**
     * @var mooduell MooDuell instance
     */
    public $mooduell;

    /**
     * @var questions
     */
    private $questions;

    /**
     * @var gamedata
     */
    private $gamedata;

    /**
     * game_control constructor.
     * 
     * we set all the data we have at this moment and make it available to the instance of this class
     *
     * @param mooduell $mooduell
     */
    public function __construct(mooduell $mooduell) {

        global $USER;

        $now = new DateTime();
        $nowtime = $now->getTimestamp();

        $this->mooduell = $mooduell;

        $data = new stdClass();
        $data->playeraid = $USER->id;
        $data->timemodified = $nowtime;
        $data->timecreated = $nowtime;
        
        $this->gamedata = $data;

    }

    /**
     * Create new game, set random question sequence and write to DB
     *
     * @return integer quizid or 0 when no quizid is set
     */
    public function create_new_game($playerbid){

        global $USER;
        global $DB;


        $data = $this->gamedata;
        $data->playerbid = $playerbid;

        //we collect all the data to safe to mooduell_games table

        //$DB->insert_record('mooduell_game', $data);

        //we retrieve all the questions we can get
        $this->get_available_questions();


        return true;
    }


    /**
     * Retrieve all available questions from the question bank, filtered by category, if necessary
     */
    static function get_available_questions($category = null) {

        global $DB;

        //for debugging disabled
        //$DB->insert_record('mooduell_game', $data);

        $questions = $DB->get_records('question');

        return null;        








    }






}