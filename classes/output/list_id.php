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

        // Code for Moodle > 5.0.
        if ($CFG->version >= 2025040100) {
            global $DB;
            $sql = "SELECT DISTINCT c.instanceid FROM {mooduell_categories} mc
                            JOIN {question_categories} qc
                                ON qc.id = mc.category
                        LEFT JOIN {question_bank_entries} qbe
                                ON qbe.questioncategoryid = qc.id
                            JOIN (
                                        SELECT qv1.questionbankentryid, qv1.questionid, qv1.version
                                        FROM {question_versions} qv1
                                        JOIN (
                                                SELECT questionbankentryid, max(version) maxversion
                                                FROM {question_versions}
                                            GROUP BY questionbankentryid
                                                ) qv2
                                            ON qv1.questionbankentryid = qv2.questionbankentryid
                                            AND qv1.version = qv2.maxversion
                                    ) qv
                                ON qbe.id = qv.questionbankentryid
                            JOIN {question} q
                                ON q.id = qv.questionid
                                JOIN {context} c on c.id = qc.contextid
                                WHERE qc.id = :categoryid and q.id = :questionid";

            $params = [
                'questionid' => $question->questionid,
                'categoryid' => $question->category,
            ];
            // We use count because it gives us just the value.
            $qbankcmid = $DB->count_records_sql($sql, $params);
            $path = '/question/bank/editquestion/question.php';
        } else if ($CFG->version >= 2022041900) {
            // Code for Moodle > 4.0 .
            $path = '/question/bank/editquestion/question.php';
        } else {
            $path = '/question/question.php';
        }

        $returnurl = "/mod/mooduell/view.php?id=$cmid#questions";

        $urlparams = [
            'id' => $question->questionid,
            'courseid' => $COURSE->id,
            'sesskey' => sesskey(),
            'returnto' => 'url',
            'returnurl' => $returnurl,
        ];

        if (!empty($qbankcmid)) {
            $urlparams['cmid'] = $qbankcmid;
        }

        $editquestionurl = new moodle_url(
            $path,
            $urlparams
        );

        $this->data['id'] = $question->questionid;
        $this->data['questionurl'] = $editquestionurl->out(false);
    }

    /**
     * Prepare data for use in a template
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template($output) {
        return $this->data;
    }
}
