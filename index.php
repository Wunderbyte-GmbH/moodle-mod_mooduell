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
 * Display information about all the mod_mooduell modules in the requested course.
 *
 * @package mod_mooduell
 * @copyright 2020 Georg Maißer <georg.maisser@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("../../config.php");
require_once("locallib.php");

$id = required_param('id', PARAM_INT);
$PAGE->set_url('/mod/quiz/index.php', ['id' => $id]);
if (!$course = $DB->get_record('course', ['id' => $id])) {
    throw new moodle_exception('invalidcourseid');
}
$coursecontext = context_course::instance($id);
require_login($course);
$PAGE->set_pagelayout('incourse');

$params = [
        'context' => $coursecontext,
];

$PAGE->set_url('/mod/mooduell/index.php', [
        'id' => $id,
]);
$PAGE->set_title(format_string($course->fullname));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($coursecontext);

echo $OUTPUT->header();

$modulenameplural = get_string('modulenameplural', 'mod_mooduell');
echo $OUTPUT->heading($modulenameplural);

$mooduells = get_all_instances_in_course('mooduell', $course);

if (empty($mooduells)) {
    notice(get_string('nonewmodules', 'mod_mooduell'), new moodle_url('/course/view.php', [
            'id' => $course->id,
    ]));
}

$table = new html_table();
$table->attributes['class'] = 'generaltable mod_index';

if ($course->format == 'weeks') {
    $table->head = [
            get_string('week'),
            get_string('name'),
    ];
    $table->align = [
            'center',
            'left',
    ];
} else if ($course->format == 'topics') {
    $table->head = [
            get_string('topic'),
            get_string('name'),
    ];
    $table->align = [
            'center',
            'left',
            'left',
            'left',
    ];
} else {
    $table->head = [
            get_string('name'),
    ];
    $table->align = [
            'left',
            'left',
            'left',
    ];
}

foreach ($mooduells as $mooduell) {
    if (!$mooduell->visible) {
        $link = html_writer::link(new moodle_url('/mod/mooduell/view.php', [
                'id' => $mooduell->coursemodule,
        ]), format_string($mooduell->name, true), [
                'class' => 'dimmed',
        ]);
    } else {
        $link = html_writer::link(new moodle_url('/mod/mooduell/view.php', [
                'id' => $mooduell->coursemodule,
        ]), format_string($mooduell->name, true));
    }

    if ($course->format == 'weeks' or $course->format == 'topics') {
        $table->data[] = [
                $mooduell->section,
                $link,
        ];
    } else {
        $table->data[] = [
                $link,
        ];
    }
}

echo html_writer::table($table);
echo $OUTPUT->footer();
