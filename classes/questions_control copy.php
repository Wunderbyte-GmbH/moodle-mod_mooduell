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

class questions_control {


    


    /**
     * @var list of questions of class question
     */
    public $questions;


    

    /**
     * questions_control constructor.
     *
     * @param 
     */
    public function __construct() {
        
    }

    /**
     * Create new game, set random question sequence and write to DB
     *
     * @return bool status 1 or 0, depending on success
     */
    public function start_new_game($playerbid){

        $newgameinstance = new mooduell_game($this->mooduell);
        $game = $newgameinstance->start_new_game($playerbid);

        return true;
    }
}