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
 * Table SQL class for displaying MooDuell games.
 *
 * @package mod_mooduell
 * @copyright 2020 Wunderbyte Gmbh <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mooduell\tables;

use local_wunderbyte_table\wunderbyte_table;
use mod_mooduell\mooduell;
use mod_mooduell\output\list_action;
use mod_mooduell\output\renderer;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * MooDuell Table sql class.
 */
class table_games extends wunderbyte_table {

    /**
     * Parameter to store the action (what to show in the mooduell_table)
     * @var String action ('opengames'|'finishedgames'|'questions'|'highscores')
     */
    public $action;

    /**
     * Instance of mooduell class.
     *
     * @var mooduell
     */
    public $mooduell;

    /**
     * @var renderer
     */
    private $renderer;

    /**
     * mooduell_table constructor
     * @param int $mooduell
     * @param string $action
     */
    public function __construct($action, mooduell $mooduell = null) {
        global $PAGE;

        parent::__construct($action);

        if ($mooduell) {
            $this->mooduell = $mooduell;
        }
        $this->action = $action;

        $this->renderer = $PAGE->get_renderer('mod_mooduell');
    }

    /**
     * Function to return the players name instead of id
     * @param stdClass $game
     */
    public function col_playeraid(stdClass $game) {
        if ($game->playeraid) {
            $name = $this->mooduell->return_name_by_id($game->playeraid);

            return $name;
        }
    }

    /**
     * Function to return the players name instead of id
     * @param stdClass $game
     */
    public function col_playerbid(stdClass $game) {
        if ($game->playerbid) {
            $name = $this->mooduell->return_name_by_id($game->playerbid);

            return $name;
        }
    }

    /**
     * Function to return the readable date instead of timestamp.
     * @param stdClass $game
     */
    public function col_timemodified(stdClass $game) {
        if ($game->timemodified) {
            if (current_language() === 'de') {
                $monthnamesde = [
                    1 => "Januar",
                    2 => "Februar",
                    3 => "März",
                    4 => "April",
                    5 => "Mai",
                    6 => "Juni",
                    7 => "Juli",
                    8 => "August",
                    9 => "September",
                    10 => "Oktober",
                    11 => "November",
                    12 => "Dezember"
                ];
                // Now build the German date string.
                $name = date("d. ", $game->timemodified);
                $name .= $monthnamesde[date("n", $game->timemodified)];
                $name .= date(" Y, H:i:s", $game->timemodified);
            } else {
                $name = date("F j, Y, g:i:s a", $game->timemodified);
            }

            return $name;
        }
    }

    /**
     * Function to return the MooDuell id.
     * @param stdClass $game
     */
    public function col_mooduellid(stdClass $game) {
        if ($game->mooduellid) {

            $name = $game->mooduellid;

            return $name;
        }
    }

    /**
     * Function to return clickable action links.
     * @param stdClass $game
     */
    public function col_action(stdClass $game) {

        $action = new list_action($game->id, $game, $this->mooduell);
        return $this->renderer->render_list_action($action);
    }
}
