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
use mod_mooduell\tables\table_games;
use mod_mooduell\tables\table_highscores;
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
class overview_teacher implements renderable, templatable {

    /**
     * An object with all the data.
     *
     * @var array
     */
    protected $data;

    /**
     * Constructor.
     * @param mooduell $mooduell
     */
    public function __construct(mooduell $mooduell = null) {

        $data = [];

        $data['opengames'] = $this->render_open_games_table($mooduell);
        $data['finishedgames'] = $this->render_finished_games_table($mooduell);
        $data['warnings'] = $mooduell->check_quiz();

        // Add the Name of the instance.
        $data['quizname'] = $mooduell->cm->name;
        $data['mooduellid'] = $mooduell->cm->id;
        // Add the list of questions.
        $data['questions'] = $this->render_questions_table($mooduell);
        $data['highscores'] = $this->render_highscores_table($mooduell);
        $data['categories'] = $mooduell->return_list_of_categories();
        $data['statistics'] = $mooduell->return_list_of_statistics_teacher();

        $this->data = $data;
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
     * Render the open games table.
     *
     * @param mooduell $mooduell
     * @return string
     */
    private function render_open_games_table(mooduell $mooduell):string {
        return $this->render_games_table($mooduell, 'opengames');
    }

    /**
     * Render the open games table. This function automatically returns the table for teachers or students.
     *
     * @param mooduell $mooduell
     * @return string
     */
    private function render_finished_games_table(mooduell $mooduell):string {
        return $this->render_games_table($mooduell, 'finishedgames');
    }

    /**
     * Render the open games table. This function automatically returns the table for teachers or students.
     *
     * @param mooduell $mooduell
     * @param string $action
     * @return string
     */
    private function render_games_table(mooduell $mooduell, $action):string {

        $tablename = bin2hex(random_bytes(12));

        $gamestable = new table_games($tablename, $mooduell->cm->id);

        $finishedgames = $action == 'finishedgames' ? true : false;

        list($fields, $from, $where, $params) = $mooduell->return_sql_for_games('teacher', $finishedgames);

        $gamestable->set_sql($fields, $from, $where, $params);

        $tabledata = $mooduell->return_cols_for_games_table('teacher');
        $gamestable->define_columns($tabledata->columns);
        $gamestable->define_headers($tabledata->headers);
        $gamestable->define_help_for_headers($tabledata->help);
        $gamestable->define_sortablecolumns($tabledata->columns);

        $gamestable->is_downloading('', 'mooduell_games');

        $gamestable->define_cache('mod_mooduell', 'tablescache');

        $gamestable->stickyheader = false;
        $gamestable->showcountlabel = true;
        $gamestable->pageable(true);

        $gamestable->showdownloadbutton = true;

        list($idstring, $encodedtable, $html) = $gamestable->lazyouthtml(20, true);

        return $html;
    }


    /**
     * Render the highscores table. This function automatically returns the table for teachers or students.
     *
     * @param mooduell $mooduell
     * @return string
     */
    private function render_highscores_table(mooduell $mooduell):string {

        $tablename = bin2hex(random_bytes(12));
        $highscorestable = new table_highscores($tablename, $mooduell->cm->id);
        // Sort the table by descending score by default.
        $highscorestable->sort_default_column = 'score';
        $highscorestable->sort_default_order = SORT_DESC;

        list($fields, $from, $where, $params) = $mooduell->return_sql_for_highscores('teacher');

        $highscorestable->set_sql($fields, $from, $where, $params);

        $tabledata = $mooduell->return_cols_for_highscores_table();

        $highscorestable->define_columns($tabledata->columns);
        $highscorestable->define_headers($tabledata->headers);
        $highscorestable->define_help_for_headers($tabledata->help);

        $highscorestable->is_downloading('', 'mooduell_highscores');

        $highscorestable->define_cache('mod_mooduell', 'tablescache');

        $highscorestable->stickyheader = false;
        $highscorestable->showcountlabel = true;
        $highscorestable->pageable(true);

        $highscorestable->showdownloadbutton = true;

        list($idstring, $encodedtable, $html) = $highscorestable->lazyouthtml(20, true);

        return $html;
    }

    /**
     * Render the questions table.
     *
     * @param mooduell $mooduell
     * @return string
     */
    private function render_questions_table(mooduell $mooduell):string {

        $tablename = bin2hex(random_bytes(12));

        $questionstable = new table_questions($tablename, $mooduell->cm->id);
        // Sort the table by descending score by default.
        $questionstable->sort_default_column = 'id';
        $questionstable->sort_default_order = SORT_ASC;

        list($fields, $from, $where, $params) = $mooduell->return_sql_for_questions();

        $questionstable->set_sql($fields, $from, $where, $params);

        $tabledata = $mooduell->return_cols_for_questions_table();

        $questionstable->define_columns($tabledata->columns);
        $questionstable->define_headers($tabledata->headers);
        $questionstable->define_help_for_headers($tabledata->help);

        $questionstable->is_downloading('', 'mooduell_questions');

        // It's important to have the baseurl defined, we use it as a return url at one point.
        $baseurl = new moodle_url(
            $_SERVER['REQUEST_URI'],
            $_GET
        );

        $questionstable->define_cache('mod_mooduell', 'tablescache');

        $questionstable->infinitescroll = 40;

        list($idstring, $encodedtable, $html) = $questionstable->lazyouthtml(40, true);
        // $html = $questionstable->out(40, true);

        return $html;
    }
}
