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
 * Contains class mod_questionnaire\output\indexpage
 *
 * @package    mod_mooduell
 * @copyright  2020 Wunderbyte Gmbh <info@wunderbyte.at>
 * @author     Georg Maißer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

namespace mod_mooduell\output;

use mod_mooduell\question_control;
use moodle_url;
use renderable;
use renderer_base;
use templatable;

/**
 * viewpage class to display view.php
 * @package mod_mooduell
 *
 */
class list_id implements renderable, templatable {

    /**
     * An array with all the data.
     *
     * @var array
     */
    protected $data;

    /**
     * Constructor.
     * @param question_control $question
     * @param int $cmid
     */
    public function __construct(question_control $question, int $cmid) {

        global $CFG, $PAGE, $COURSE;

        $returnurl = "/mod/mooduell/view.php?id=$cmid#questions";
        $editquestionurl = new moodle_url('/question/question.php',
                                array(
                                    'id' => $question->questionid,
                                    'courseid' => $COURSE->id,
                                    'sesskey' => sesskey(),
                                    'returnto' => 'url',
                                    'returnurl' => $returnurl
                                ));

        $this->data['id'] = $question->questionid;
        $this->data['questionurl'] = $editquestionurl->out(false);
    }

    /**
     * Prepare data for use in a template
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output) {
        return $this->data;
    }
}
