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

use mod_mooduell\manage_tokens;
use mod_mooduell\mooduell;
use mod_mooduell\event\game_finished;
use mod_mooduell\output\overview_student;
use mod_mooduell\output\overview_teacher;

require_once(__DIR__ .'/../../config.php');
require_once(__DIR__ . '/lib.php');

defined('MOODLE_INTERNAL') || die();

global $CFG, $PAGE, $USER;

require_once("{$CFG->dirroot}/mod/mooduell/classes/mooduell.php");
require_once("{$CFG->dirroot}/course/moodleform_mod.php");

$id = required_param('id', PARAM_INT);
$action = optional_param('action', '', PARAM_RAW);
$gameid = optional_param('gameid', '', PARAM_INT);
$out = '';
// We don't need it now, but we might in the future.
$inline = false;

$mooduell = new mooduell($id);
require_login($mooduell->course, true, $mooduell->cm);

$context = $mooduell->context;

$pagename = null;

$mooduell->view_page();

// Event debugging - will be triggered by the button in classes/mooduell.php.
// Or by setting the event param in the URL.
$triggeredevent = optional_param('triggered_event', null, PARAM_RAW);
switch ($triggeredevent) {
    case 'game_finished':
        $event = game_finished::create(array('context' => $context, 'objectid' => $mooduell->cm->id));
        $event->trigger();
        break;

    case 'course_module_created':
        manage_tokens::generate_tokens_for_all_instance_users($id);
        break;

    case 'user_enrolment_created':
        $debuguserid = optional_param('debuguserid', $USER->id, PARAM_INT);
        manage_tokens::generate_token_for_user($debuguserid);
        break;
}

// End of event debugging.
$output = $PAGE->get_renderer('mod_mooduell');

if (!$inline) {
    $out .= $output->header();
}

// Use the view.php for different actions and views.
switch ($action) {
    case null:
        break;
    case 'delete':
        // This check is not really necessary.
        if (has_capability('mod/mooduell:editgames', $context)) {
            $PAGE->set_url('/mod/mooduell/view.php', array('id' => $id));
            $mooduell->execute_action($action, $gameid);
        }
        break;
    case 'viewquestions':
        $pagename = 'questions';
        break;
    case 'downloadhighscores':
        $pagename = 'downloadhighscores';
}

if (!$inline) {
    $cminfo = cm_info::create($mooduell->cm, $USER->id);
    $completiondetails = \core_completion\cm_completion_details::get_instance($cminfo, $USER->id); // Fetch completion information. 
    $activitydates = \core\activity_dates::get_dates_for_module($cminfo, $USER->id); // Fetch activity dates.
    $out .= $OUTPUT->heading(format_string($mooduell->cm->name), 2, null);
    $out .= $OUTPUT->activity_information($cminfo, $completiondetails, $activitydates);
}

if (!has_capability('mod/mooduell:viewstatistics', $context)) {
    $pagename = 'studentsview';
    $overview = new overview_student($mooduell);
    $out .= $output->render_overview_students($overview);
} else {
    $overview = new overview_teacher($mooduell);
    $out .= $output->render_overview_teachers($overview);
}

if (!$inline) {
    $out .= $output->footer();
}

echo $out;
