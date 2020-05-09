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

/**
 * Plugin event observers are registered here.
 *
 * @package     mod_mooduell
 * @copyright   2020 Wunderbyte Gmbh <info@wunderbyte.at>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mooduell;

use moodleform;

//moodleform is defined in formslib.php
require_once "$CFG->libdir/formslib.php";

class mooduell_form extends moodleform
{

    /**
     * @var mooduell
     */
    public $mooduell = null;


    public function __construct($mooduell) {
        //first we set the variable
        $this->mooduell = $mooduell;
        //now we can run the constructor of the base class
        parent::__construct();

        
    }


    //Add elements to form
    public function definition()
    {

        
        $mform = $this->_form; // Don't forget the underscore!
        

        //we only call this if we have a mooduell instance linked
        if ($this->mooduell) {
            $mform->addElement('static', 'games', get_string('foundthesegames', 'mod_mooduell'));
            foreach($this->create_list_of_games() as $game) {

                $mform->addElement('static', 'game', $game->playeraid . " " . $game->playerbid);
    
            }
        }
        else {
            $mform->addElement('static', 'message', get_string('nomessage', 'mod_mooduell'));
        }     
       


    }
    //Custom validation should be added here
    public function validation($data, $files)
    {
        return array();
    }



    public function create_list_of_games() {

        global $DB;

        $games = $DB->get_records('mooduell_games', ['mooduellid' => $this->mooduell->cm->id]);


        



        return $games;


    }
}
