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

global $CFG, $PAGE;


if (!$CFG) {
    require_once("../../config.php");
}

require "$CFG->libdir/tablelib.php";


require_once("$CFG->dirroot/mod/mooduell/classes/mooduell_table.php");

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/mooduell_table.php');

$download = optional_param('download', '', PARAM_ALPHA);
$uniqueid = optional_param('action', '', PARAM_ALPHA);
$quizid = optional_param('quizid', '', PARAM_INT);


$table = new mooduell_table($uniqueid);
$table->mooduell = new \mod_mooduell\mooduell($quizid);
$table->is_downloading($download, $uniqueid, $uniqueid);

$mooduellid = $table->mooduell->cm->instance;

if (!$table->is_downloading()) {
    // Only print headers if not asked to download data
    // Print the page header
    $PAGE->set_title('Testing');
    $PAGE->set_heading('Testing table class');
    $PAGE->navbar->add('Testing table class', new moodle_url('/mooduell_table.php'));
    // echo $OUTPUT->header();
}

// Work out the sql for the table.

$fields = "*";
$from = "{mooduell_games}";
$where = "mooduellid = :mooduellid1";
$params = array('mooduellid1' => $mooduellid);

$table->set_sql($fields, $from, $where, $params);

//$columns[]= 'mooduellid';
//$headers[]= get_string('mooduell', 'mooduell');
//$help[] = NULL;

$columns[]= 'timemodified';
$headers[]= get_string('lastplayed', 'mooduell');
$help[] = NULL;

$columns[]= 'playeraid';
$headers[]= get_string('playera', 'mooduell');
$help[] = NULL;

$columns[]= 'playeraresults';
$headers[]= get_string('playeraresults', 'mooduell');
$help[] = NULL;

$columns[]= 'playerbid';
$headers[]= get_string('playerb', 'mooduell');
$help[] = NULL;

$columns[]= 'playerbresults';
$headers[]= get_string('playeraresults', 'mooduell');
$help[] = NULL;


$columns[]= 'action';
$headers[]= get_string('action', 'mooduell');
$help[] = NULL;

$table->define_columns($columns);
$table->define_headers($headers);
$table->define_help_for_headers($help);

$table->define_baseurl("$CFG->wwwroot/mod/mooduell/mooduell_table.php");

$table->out(40, true);

if (!$table->is_downloading()) {
    // echo $OUTPUT->footer();
}

// $mooduell = new \mod_mooduell\mooduell();