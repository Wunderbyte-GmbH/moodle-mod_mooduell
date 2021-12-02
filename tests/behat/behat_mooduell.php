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
 * Behat question-related steps definitions.
 *
 * @package    mod_mooduell
 * @category   test
 * @copyright  2021 Wunderbyte Gmbh <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_mooduell\game_control;
use mod_mooduell\mooduell;
use mod_mooduell\question_control;

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

/**
 * Steps definitions related with the mooduell table management.
 *
 * @package    mod_mooduell
 * @category   test
 * @copyright  2021 Wunderbyte Gmbh <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_mooduell extends behat_base
{

    /**
     * Creates new mooduell games in the database
     * @Given /^I start games in "(?P<mooduellinstancename_string>(?:[^"]|\\")*)" against "(?P<playerbname_string>(?:[^"]|\\")*)"$/
     * @param string $mooduellinstancename
     * @param string $playerbname
     * @return void
     */
    public function i_start_games_against($mooduellinstancename, string $playerbname) {

        global $DB;

        $cm = $this->get_cm_by_mooduell_name($mooduellinstancename);

        $mooduell = new mooduell($cm->id);
        $game = new game_control($mooduell);

        $playerb = $this->get_user_by_name($playerbname);

        // Start 10 games against player b.
        $counter = 0;
        while ($counter < 10) {
            $game->start_new_game($playerb->id);
            $game->get_questions();

            $questioncounter = 0;

            // Play the first three question of game.
            while ($questioncounter < 3) {
                $game->validate_question($game->gamedata->questions[$questioncounter]->questionid, [1]);
                ++$questioncounter;
            }

            ++$counter;
        }
    }

    /**
     * Get a mooduell by name.
     *
     * @param string $name mooduell name.
     * @return stdClass the corresponding DB row.
     */
    protected function get_mooduell_by_name(string $name): stdClass {
        global $DB;
        return $DB->get_record('mooduell', ['name' => $name], '*', MUST_EXIST);
    }

    /**
     * Get a mooduell coursemodule object from the name.
     *
     * @param string $name chat name.
     * @return stdClass cm from get_coursemodule_from_instance.
     */
    protected function get_cm_by_mooduell_name(string $name): stdClass {
        $mooduell = $this->get_mooduell_by_name($name);
        return get_coursemodule_from_instance('mooduell', $mooduell->id, $mooduell->course);
    }

    /**
     * Get a MooDuell user by name.
     *
     * @param string $name
     * @return stdClass
     */
    protected function get_user_by_name(string $name): stdClass {
        global $DB;

        return $DB->get_record('user', ['username' => $name]);
    }
}
