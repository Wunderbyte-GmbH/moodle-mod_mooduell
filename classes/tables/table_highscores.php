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
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * MooDuell Table sql class.
 */
class table_highscores extends wunderbyte_table {

    /**
     * Parameter to store the action (what to show in the mooduell_table)
     * @var String action ('opengames'|'finishedgames'|'questions'|'highscores')
     */
    public $action;

    /**
     * @var mooduell
     */
    public $mooduell;

    /**
     * mooduell_table constructor
     * @param string $action
     * @param mooduell $mooduell
     */
    public function __construct($action, mooduell $mooduell = null) {
        parent::__construct($action);

        if ($mooduell) {
            $this->mooduell = $mooduell;
        }
        $this->action = $action;

        $this->define_cache('mod_mooduell', 'tablescache');
    }

    /* COLUMNS for HIGHSCORES */

    /**
     * Function to return the ranking of the player.
     * @param stdClass $highscoreentry
     */
    public function col_ranking(stdClass $highscoreentry) {
        if ($highscoreentry->ranking) {

            $ranking = $highscoreentry->ranking;

            return $ranking;
        }
    }

    /**
     * Function to return the name of the player.
     * @param stdClass $highscoreentry
     */
    public function col_userid(stdClass $highscoreentry) {
        if ($highscoreentry->userid) {

            $username = $this->mooduell->return_name_by_id($highscoreentry->userid);

            return $username;
        }
    }

    /**
     * Function to return the score of the player.
     * @param stdClass $highscoreentry
     */
    public function col_score(stdClass $highscoreentry) {
        if ($highscoreentry->score !== null) {

            $score = $highscoreentry->score;

            return $score;
        }
    }

    /**
     * Function to return the number of games played by the player.
     * @param stdClass $highscoreentry
     */
    public function col_gamesplayed(stdClass $highscoreentry) {
        if ($highscoreentry->gamesplayed !== null) {

            $gamesplayed = $highscoreentry->gamesplayed;

            return $gamesplayed;
        }
    }

    /**
     * Function to return the number of games won by the player.
     * @param stdClass $highscoreentry
     */
    public function col_gameswon(stdClass $highscoreentry) {
        if ($highscoreentry->gameswon !== null) {

            $gameswon = $highscoreentry->gameswon;

            return $gameswon;
        }
    }

    /**
     * Function to return the number of games lost by the player.
     * @param stdClass $highscoreentry
     */
    public function col_gameslost(stdClass $highscoreentry) {
        if ($highscoreentry->gameslost !== null) {

            $gameslost = $highscoreentry->gameslost;

            return $gameslost;
        }
    }

    /**
     * Function to return the number of correctly answered questions by the player.
     * @param stdClass $highscoreentry
     */
    public function col_qcorrect(stdClass $highscoreentry) {
        if ($highscoreentry->qcorrect !== null) {

            $qcorrect = $highscoreentry->qcorrect;

            return $qcorrect;
        }
    }

    /**
     * Function to return the number of questions answered by the player.
     * @param stdClass $highscoreentry
     */
    public function col_qplayed(stdClass $highscoreentry) {
        if ($highscoreentry->qplayed !== null) {

            $qplayed = $highscoreentry->qplayed;

            return $qplayed;
        }
    }

    /**
     * Function to return the percentage of correctly answered questions by the player.
     * @param stdClass $highscoreentry
     */
    public function col_qcpercentage(stdClass $highscoreentry) {
        if ($highscoreentry->qcpercentage !== null) {

            $qcpercentage = number_format($highscoreentry->qcpercentage, 1).' %';

            return $qcpercentage;
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
}
