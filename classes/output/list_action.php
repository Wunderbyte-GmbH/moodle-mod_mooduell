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

use mod_mooduell\mooduell;
use mod_mooduell\question_control;
use mod_mooduell\tables\table_questions;
use moodle_url;
use renderable;
use renderer_base;
use stdClass;
use templatable;

/**
 * viewpage class to display view.php
 * @package mod_mooduell
 *
 */
class list_action implements renderable, templatable {

    /**
     * An array with all the data.
     *
     * @var array
     */
    protected $data;

    /**
     * Constructor.
     * @param int $counter
     * @param stdClass $game
     * @param mooduell $mooduell
     */
    public function __construct(int $counter, stdClass $game, mooduell $mooduell) {

        global $CFG, $PAGE, $COURSE;

        $this->data['counter'] = (int) $counter;
        $this->data['deletelink'] = $mooduell->cm->id;
        list($idstring, $encodedtable, $html) = $this->render_questions_table_for_game($game, $mooduell);
        $this->data['thisgametable'] = $html;
        $this->data['encodedtable'] = $encodedtable;
        $this->data['idstring'] = $idstring;
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

    /**
     * Render the questions table.
     *
     * @param stdClass $game
     * @param mooduell $mooduell
     * @param int $counter
     * @return array
     */
    private function render_questions_table_for_game(stdClass $game, mooduell $mooduell, int $counter = null):array {
        global $PAGE;

        $tablename = bin2hex(random_bytes(12));

        $questionstable = new table_questions($tablename, $mooduell->cm->id);

        $sqldata = $mooduell->return_sql_for_questions_in_game($game);

        $questionstable->set_sql($sqldata['select'], $sqldata['from'], $sqldata['where'], $sqldata['params']);

        $tabledata = $mooduell->return_cols_for_questions_table(true);

        $questionstable->define_columns($tabledata->columns);
        $questionstable->define_headers($tabledata->headers);
        $questionstable->define_help_for_headers($tabledata->help);

        // It's important to have the baseurl defined, we use it as a return url at one point.
        $baseurl = new moodle_url(
            $_SERVER['REQUEST_URI'],
            $_GET
        );

        // $questionstable->use_pages = true;

        $questionstable->define_baseurl($baseurl->out());

        return $questionstable->lazyouthtml(9, true);
    }
}
