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
 * Prints an instance of mod_mooduell.
 *
 * @package     mod_mooduell
 * @copyright   2020 David Bogner <david.bogner@wunderbyte.at>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_mooduell\mooduell;
use mod_mooduell\quiz_control;

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_once("{$CFG->dirroot}/mod/mooduell/classes/mooduell.php");
require_once("{$CFG->dirroot}/course/moodleform_mod.php");

$id = required_param('id', PARAM_INT);
$mooduell = new mooduell($id);
require_login($mooduell->course, true, $mooduell->cm);

$mooduell->setup_page();

// For testing only. TODO: Remove after test.
$quizimporter = new quiz_control($mooduell);
$quizimporter->import_demo_quiz();

echo $mooduell->display();


/*

quiz-table in install.xml

. write quiz id preferences

-  classes mod_mooduell.php (view datalynx)

- moustache-template -> render -> daten

//mooduell

//mooduell


- insert button





*/
