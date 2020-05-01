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

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

require_once("$CFG->dirroot/mod/mooduell/classes/mooduell.php");



require_once($CFG->dirroot.'/course/moodleform_mod.php');

// Course_module ID, or
$id = optional_param('id', 0, PARAM_INT);

// ... module instance id.
$m  = optional_param('m', 0, PARAM_INT);

if ($id) {
    $cm             = get_coursemodule_from_id('mooduell', $id, 0, false, MUST_EXIST);
    $course         = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $moduleinstance = $DB->get_record('mooduell', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($m) {
    $moduleinstance = $DB->get_record('mooduell', array('id' => $n), '*', MUST_EXIST);
    $course         = $DB->get_record('course', array('id' => $moduleinstance->course), '*', MUST_EXIST);
    $cm             = get_coursemodule_from_instance('mooduell', $moduleinstance->id, $course->id, false, MUST_EXIST);
} else {
    print_error(get_string('missingidandcmid', 'mod_mooduell'));
}

require_login($course, true, $cm);

$modulecontext = context_module::instance($cm->id);

$event = \mod_mooduell\event\course_module_viewed::create(array(
    'objectid' => $moduleinstance->id,
    'context' => $modulecontext
));
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('mooduell', $moduleinstance);
$event->trigger();

$PAGE->set_url('/mod/mooduell/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

echo $OUTPUT->header();


$mooduell = new mod_mooduell\mooduell($moduleinstance->quizid, $course, $cm, $modulecontext);
$mooduell_output = $mooduell->display();
/* $mooduell_output = $mooduell->import_demo_quiz($moduleinstance->quizid); */

echo "----<br>";
print_r($cm->section);

//print_r($id);
echo "<br>Id: ";
print_r($moduleinstance->id);
echo "<br>showcorrectanswer: ";
print_r($moduleinstance->showcorrectanswer);
echo "<br>usecountdown: ";
print_r($moduleinstance->usecountdown);
echo "<br>quizid: ";
print_r($moduleinstance->quizid);

echo "<br> ->: ";
echo "$mooduell_output wollte ich sagen. ";


/*

quiz-table in install.xml

. write quiz id preferences 

-  classes mod_mooduell.php (view datalynx)

- moustache-template -> render -> daten

//mooduell

//mooduell


- insert button





*/


echo $OUTPUT->footer();
