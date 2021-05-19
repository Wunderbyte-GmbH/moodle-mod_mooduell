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

use mod_mooduell\mooduell;

require_once("../../config.php");

global $CFG, $PAGE;

require("$CFG->libdir/tablelib.php");
require_once("$CFG->dirroot/mod/mooduell/classes/mooduell_table.php");

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/mooduell_table.php');

$download = optional_param('download', '', PARAM_ALPHA);
$action = optional_param('action', '', PARAM_ALPHA);
$quizid = optional_param('quizid', '', PARAM_INT);
$view = optional_param('view', '', PARAM_ALPHA); // values: 'teacher' or 'student'

$mooduellinstance = new mooduell($quizid);

$table = new mooduell_table($mooduellinstance, $action);

$table->is_downloading($download, $action, $action);

$mooduellid = $table->mooduell->cm->instance;

if (!$table->is_downloading()) {
    // Only print headers if not asked to download data
    // Print the page header
    $PAGE->set_title('Testing');
    $PAGE->set_heading('Testing table class');
    $PAGE->navbar->add('Testing table class', new moodle_url('/mooduell_table.php'));
    // echo $OUTPUT->header();
}

switch($action){
    case 'opengames':
        // generate the tabledata for open games
        $tabledata = load_open_games_table_data($mooduellid, $table, $view);
        break;
    case 'finishedgames':
        // generate the tabledata for finished games
        $tabledata = load_finished_games_table_data($mooduellid, $table, $view);
        break;
    case 'highscores':
        // generate the tabledata for highscores
        $tabledata = load_highscores_table_data($mooduellid, $table);

        // sort the table by descending score by default
        $table->sort_default_column = 'score';
        $table->sort_default_order = SORT_DESC;

        // turn off sorting by userid, as it will lead to confusion because real names or nicknames will be shown
        $table->no_sorting('userid');

        break;
    default:
        break;
}

$table->define_columns($tabledata->columns);
$table->define_headers($tabledata->headers);
$table->define_help_for_headers($tabledata->help);

$table->define_baseurl("$CFG->wwwroot/mod/mooduell/mooduell_table.php");

$table->out(40, true);

/**
 * Function to set the SQL and load the data for open games into the mooduell_table
 *
 * @param $mooduellid
 * @param $table
 * @return stdClass an object containing columns, headers and help (for headers)
 */
function load_open_games_table_data($mooduellid, $table, $view) {
    global $USER;
    // Work out the sql for the table.
    $fields = "*";
    $from = "{mooduell_games}";
    switch ($view){
        case 'teacher':
            $where = "mooduellid = :mooduellid1 AND status <> 3";
            $params = array('mooduellid1' => $mooduellid);
            break;
        // student view is the default view
        default:
            // we need to pass 2 userids because each one can only be used once for some strange reason
            $where = "mooduellid = :mooduellid1 AND status <> 3 AND (playeraid = :userid1 OR playerbid = :userid2)";
            $params = array('mooduellid1' => $mooduellid, 'userid1' => $USER->id, 'userid2' => $USER->id);
            break;
    }

    $table->set_sql($fields, $from, $where, $params);

    $columns[] = 'timemodified';
    $headers[] = get_string('lastplayed', 'mooduell');
    $help[] = null;

    $columns[] = 'playeraid';
    $headers[] = get_string('playera', 'mooduell');
    $help[] = null;

    $columns[] = 'playeraresults';
    $headers[] = get_string('playeraresults', 'mooduell');
    $help[] = null;

    $columns[] = 'playerbid';
    $headers[] = get_string('playerb', 'mooduell');
    $help[] = null;

    $columns[] = 'playerbresults';
    $headers[] = get_string('playeraresults', 'mooduell');
    $help[] = null;

    $columns[] = 'action';
    $headers[] = get_string('action', 'mooduell');
    $help[] = null;

    $tabledata = new stdClass();
    $tabledata->columns = $columns;
    $tabledata->headers = $headers;
    $tabledata->help = $help;

    return $tabledata;
}

/**
 * Function to set the SQL and load the data for finished games into the mooduell_table
 *
 * @param $mooduellid
 * @param $table
 * @return stdClass an object containing columns, headers and help (for headers)
 */
function load_finished_games_table_data($mooduellid, $table, $view) {
    global $USER;

    // Work out the sql for the table.
    $fields = "*";
    $from = "{mooduell_games}";

    switch($view){
        case 'teacher':
            $where = "mooduellid = :mooduellid1 AND status = 3";
            $params = array('mooduellid1' => $mooduellid);
            break;
        // Student view is the default view.
        default:
            // We need to pass 2 userids because each one can only be used once for some strange reason.
            $where = "mooduellid = :mooduellid1 AND status = 3 AND (playeraid = :userid1 OR playerbid = :userid2)";
            $params = array('mooduellid1' => $mooduellid, 'userid1' => $USER->id, 'userid2' => $USER->id);
            break;
    }

    $table->set_sql($fields, $from, $where, $params);

    $columns[] = 'timemodified';
    $headers[] = get_string('lastplayed', 'mooduell');
    $help[] = null;

    $columns[] = 'playeraid';
    $headers[] = get_string('playera', 'mooduell');
    $help[] = null;

    $columns[] = 'playeraresults';
    $headers[] = get_string('playeraresults', 'mooduell');
    $help[] = null;

    $columns[] = 'playerbid';
    $headers[] = get_string('playerb', 'mooduell');
    $help[] = null;

    $columns[] = 'playerbresults';
    $headers[] = get_string('playeraresults', 'mooduell');
    $help[] = null;

    $columns[] = 'action';
    $headers[] = get_string('action', 'mooduell');
    $help[] = null;

    $tabledata = new stdClass();
    $tabledata->columns = $columns;
    $tabledata->headers = $headers;
    $tabledata->help = $help;

    return $tabledata;
}


/**
 * Function to set the SQL and load the data for highscores into the mooduell_table
 *
 * @param $mooduellid
 * @param $table
 * @return stdClass an object containing columns, headers and help (for headers)
 */
function load_highscores_table_data($mooduellid, $table) {
    // Generate the SQL for the table.
    $fields = "*";
    $from = "{mooduell_highscores}";
    $where = "mooduellid = :mooduellid1";
    $params = array('mooduellid1' => $mooduellid);

    $table->set_sql($fields, $from, $where, $params);

    $columns[] = 'ranking';
    $headers[] = get_string('rank', 'mooduell');
    $help[] = null;

    $columns[] = 'userid';
    $headers[] = get_string('username', 'mooduell');
    $help[] = null;

    $columns[] = 'score';
    $headers[] = get_string('score', 'mooduell');
    $help[] = null;

    $columns[] = 'gamesplayed';
    $headers[] = get_string('gamesplayed', 'mooduell');
    $help[] = null;

    $columns[] = 'gameswon';
    $headers[] = get_string('gameswon', 'mooduell');
    $help[] = null;

    $columns[] = 'gameslost';
    $headers[] = get_string('gameslost', 'mooduell');
    $help[] = null;

    $columns[] = 'qcorrect';
    $headers[] = get_string('correctlyanswered', 'mooduell');
    $help[] = null;

    $columns[] = 'qplayed';
    $headers[] = get_string('questions_played', 'mooduell');
    $help[] = null;

    $columns[] = 'qcpercentage';
    $headers[] = get_string('correctlyansweredpercentage', 'mooduell');
    $help[] = null;

    $columns[] = 'timemodified';
    $headers[] = get_string('timemodified', 'mooduell');
    $help[] = null;

    $tabledata = new stdClass();
    $tabledata->columns = $columns;
    $tabledata->headers = $headers;
    $tabledata->help = $help;

    return $tabledata;
}