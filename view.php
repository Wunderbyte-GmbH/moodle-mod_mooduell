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
 * @package mod_mooduell
 * @copyright 2020 Wunderbyte GmbH <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_mooduell\mooduell;

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_once("{$CFG->dirroot}/mod/mooduell/classes/mooduell.php");
require_once("{$CFG->dirroot}/course/moodleform_mod.php");

$id = required_param('id', PARAM_INT);
$action = optional_param('action', '', PARAM_RAW);
$gameid = optional_param('gameid', '', PARAM_INT);

$mooduell = new mooduell($id);
require_login($mooduell->course, true, $mooduell->cm);

$pagename = null;
$mooduell->view_page();

$context = $mooduell->context;



// Use the view.php for different actions and views.
switch ($action) {
    case null:
        break;
    case 'delete':
        // this check is not really necessary
        if (has_capability('mod/mooduell:editgames', $context)) {
            $PAGE->set_url('/mod/mooduell/view.php', array('id' => $id));
            $mooduell->execute_action($action, $gameid);
        }
        break;
    case 'viewquestions':
        $pagename = 'questions';
        break;
    case 'populatedb':  // A way to create games in DB without much hassle for performance testing.
        $pagename = 'populatedb';
}

if (!has_capability('mod/mooduell:viewstatistics', $context)) {
    $pagename = 'studentsview';
}

echo $mooduell->display_page(false, $pagename, $gameid);