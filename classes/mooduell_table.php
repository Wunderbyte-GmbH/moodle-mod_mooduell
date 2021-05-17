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

require_once("../../config.php");

global $CFG;

class mooduell_table extends table_sql {

    /**
     * @var stdClass
     */
    public $mooduell;

    /**
     * Parameter to store the action (what to show in the mooduell_table)
     * @var String action ('opengames'|'finishedgames'|'questions'|'highscores')
     */
    public $action;
    // Only reason to extend this class is to make mooduell class instance available.
    public $mooduell = null;

    // Parameter to store the action (what to show in the mooduell_table).
    // Can be ('opengames'|'finishedgames'|'questions'|'highscores').
    public $action = null;

    /**
     * mooduell_table constructor.
     * @param null $mooduell
     */
    public function __construct($mooduell, $action) {
        parent::__construct($action);
        $this->mooduell = $mooduell;
        $this->action = $action;
    }

    /* COLUMNS for OPEN GAMES and FINISHED GAMES */

    public function col_playeraid($game) {
        if ($game->playeraid) {

            $name = $this->mooduell->return_name_by_id($game->playeraid);

            return $name;
        }
    }

    public function col_playerbid($game) {
        if ($game->playerbid) {

            $name = $this->mooduell->return_name_by_id($game->playerbid);

            return $name;
        }
    }

    public function col_timemodified($game) {
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

    public function col_mooduellid($game) {
        if ($game->mooduellid) {

            $name = $game->mooduellid;

            return $name;
        }
    }

    public function col_action($game) {
        $cmid = $this->mooduell->cm->id;

        $link = '<a href="view.php?action=viewquestions&id=' . $cmid . '&gameid=' . $game->id .'" alt="' . get_string('viewgame', 'mod_mooduell') .'">' .
                '<i class="fa fa-info"></i>'
                . '</a>';
        $link .= " ";
        $link .= '<a href="view.php?action=delete&id=' . $cmid . '&gameid=' . $game->id .'" alt="' . get_string('deletegame', 'mod_mooduell') . '"' .
                '<i class="fa fa-trash"></i>'
                . '</a>';

        return $link;
    }

    /* COLUMNS for HIGHSCORES */

    public function col_ranking($highscoreentry) {
        if ($highscoreentry->ranking) {

            $ranking = $highscoreentry->ranking;

            return $ranking;
        }
    }

    public function col_userid($highscoreentry) {
        if ($highscoreentry->userid) {

            $username = $this->mooduell->return_name_by_id($highscoreentry->userid);

            return $username;
        }
    }

    public function col_score($highscoreentry) {
        if ($highscoreentry->score !== null) {

            $score = $highscoreentry->score;

            return $score;
        }
    }

    public function col_gamesplayed($highscoreentry) {
        if ($highscoreentry->gamesplayed !== null) {

            $gamesplayed = $highscoreentry->gamesplayed;

            return $gamesplayed;
        }
    }

    public function col_gameswon($highscoreentry) {
        if ($highscoreentry->gameswon !== null) {

            $gameswon = $highscoreentry->gameswon;

            return $gameswon;
        }
    }

    public function col_gameslost($highscoreentry) {
        if ($highscoreentry->gameslost !== null) {

            $gameslost = $highscoreentry->gameslost;

            return $gameslost;
        }
    }

    public function col_qcorrect($highscoreentry) {
        if ($highscoreentry->qcorrect !== null) {

            $qcorrect = $highscoreentry->qcorrect;

            return $qcorrect;
        }
    }

    public function col_qplayed($highscoreentry) {
        if ($highscoreentry->qplayed !== null) {

            $qplayed = $highscoreentry->qplayed;

            return $qplayed;
        }
    }

    public function col_qcpercentage($highscoreentry) {
        if ($highscoreentry->qcpercentage !== null) {

            $qcpercentage = number_format($highscoreentry->qcpercentage, 1).' %';

            return $qcpercentage;
        }
    }
}
