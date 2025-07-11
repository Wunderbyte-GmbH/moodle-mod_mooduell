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

global $CFG, $PAGE, $COURSE;

require_login($COURSE);

require("$CFG->libdir/tablelib.php");
require_once("$CFG->dirroot/mod/mooduell/classes/mooduell_table.php");

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/mooduell_table.php');

$download = optional_param('download', '', PARAM_ALPHA);
$action = optional_param('action', '', PARAM_ALPHA);
$quizid = optional_param('quizid', '', PARAM_INT);
$view = optional_param('view', '', PARAM_ALPHA); // Values: 'teacher' or 'student'.

$mooduellinstance = new mooduell($quizid);

$table = new mooduell_table($mooduellinstance, $action);

$table->is_downloading($download, $action, $action);

$mooduellid = $table->mooduell->cm->instance;

if (empty($view)) {
    if (has_capability('mod/mooduell:editgames', $context)) {
        $view = 'teacher';
    } else {
        $view = 'student';
    }
}

if (!$table->is_downloading()) {
    // Only print headers if not asked to download data.
    // Print the page header.
    $PAGE->set_title('Testing');
    $PAGE->set_heading('Testing table class');
    $PAGE->navbar->add('Testing table class', new moodle_url('/mooduell_table.php'));
}

switch ($action) {
    case 'opengames':
        // Generate the tabledata for open games.
        $tabledata = load_games_table_data($mooduellid, $table, $view, false);
        break;
    case 'finishedgames':
        // Generate the tabledata for finished games.
        $tabledata = load_games_table_data($mooduellid, $table, $view, true);
        break;
    case 'highscores':
        // Generate the tabledata for highscores.
        $tabledata = load_highscores_table_data($mooduellid, $table);

        // Sort the table by descending score by default.
        $table->sort_default_column = 'score';
        $table->sort_default_order = SORT_DESC;

        // Turn off sorting by userid, as it will lead to confusion because real names or nicknames will be shown.
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
 * Function to set the SQL and load the data for open or finished games into the mooduell_table
 * @param int $mooduellid
 * @param object $table
 * @param string $view
 * @param bool $finished
 * @return stdClass
 * @throws coding_exception
 */
function load_games_table_data(int $mooduellid, object $table, string $view, bool $finished) {
    global $USER;

    // Work out the sql for the table.
    $fields = "*";
    $from = "{mooduell_games}";
    $where = "mooduellid = :mooduellid1";
    if ($finished) {
        $where .= " AND status = 3";
    } else {
        $where .= " AND status <> 3";
    }

    switch ($view) {
        case 'teacher':
            break;
            // Student view is the default view.
        default:
            $where .= " AND (playeraid = :userid1 OR playerbid = :userid2)";
            $params = ['userid1' => $USER->id, 'userid2' => $USER->id];
            break;
    }

    $params['mooduellid1'] = $mooduellid;

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

    if ($view == 'teacher') {
        $columns[] = 'action';
        $headers[] = get_string('action', 'mooduell');
        $help[] = null;
    }

    $tabledata = new stdClass();
    $tabledata->columns = $columns;
    $tabledata->headers = $headers;
    $tabledata->help = $help;

    return $tabledata;
}

/**
 * Function to set the SQL and load the data for highscores into the mooduell_table
 * @param int $mooduellid
 * @param object $table
 * @return stdClass
 * @throws coding_exception
 */
function load_highscores_table_data(int $mooduellid, object $table) {
    // Generate the SQL for the table.
    $fields = "*";
    $from = "{mooduell_highscores}";
    $where = "mooduellid = :mooduellid1";
    $params = ['mooduellid1' => $mooduellid];

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
