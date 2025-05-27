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

namespace mod_mooduell\output;

use plugin_renderer_base;
use templatable;
use template;

/**
 * Renderer class.
 * @package mod_mooduell
 */
class renderer extends plugin_renderer_base {
    /**
     * Render a mooduell overview page for teachers.
     *
     * @param templatable $overview
     * @return string|boolean
     */
    public function render_overview_teachers(templatable $overview) {
        $data = $overview->export_for_template($this);
        return $this->render_from_template('mod_mooduell/overview_teachers', $data);
    }

    /**
     * Render a mooduell overview page for students.
     *
     * @param templatable $overview
     * @return string|boolean
     */
    public function render_overview_students(templatable $overview) {
        $data = $overview->export_for_template($this);
        return $this->render_from_template('mod_mooduell/overview_students', $data);
    }

    /**
     * render image of question in list.
     *
     * @param templatable $question
     * @return void
     */
    public function render_list_id(templatable $question) {
        $data = $question->export_for_template($this);
        return $this->render_from_template('mod_mooduell/list_id', $data);
    }

    /**
     * render image of question in list.
     *
     * @param templatable $question
     * @return void
     */
    public function render_list_warnings(templatable $question) {
        $data = $question->export_for_template($this);
        return $this->render_from_template('mod_mooduell/list_warnings', $data);
    }

    /**
     * render text & answers of question in list.
     *
     * @param templatable $question
     * @return void
     */
    public function render_list_text(templatable $question) {
        $data = $question->export_for_template($this);
        return $this->render_from_template('mod_mooduell/list_text', $data);
    }

    /**
     * render action column in games list
     *
     * @param templatable $game
     * @return void
     */
    public function render_list_action(templatable $game) {
        $data = $game->export_for_template($this);
        return $this->render_from_template('mod_mooduell/list_action', $data);
    }

    /**
     * Render image of question in list.
     *
     * @param templatable $question
     * @return void
     */
    public function render_list_image(templatable $question) {
        $data = $question->export_for_template($this);
        return $this->render_from_template('mod_mooduell/list_image', $data);
    }

    /**
     * Render a mooduell view page.
     *
     * @param templatable $viewpage
     * @return string|boolean
     */
    public function render_viewpage(templatable $viewpage) {
        $data = $viewpage->export_for_template($this);
        return $this->render_from_template('mod_mooduell/viewpage', $data);
    }

    /**
     * Render viewpage students.
     * @param templatable $viewpage
     * @return bool|string
     * @throws \moodle_exception
     */
    public function render_viewpagestudents(templatable $viewpage) {
        $data = $viewpage->export_for_template($this);
        return $this->render_from_template('mod_mooduell/viewpagestudents', $data);
    }

    /**
     * Render a mooduell list of questions
     * @param templatable $viewpage
     * @return bool|string
     * @throws \moodle_exception
     */
    public function render_viewquestions(templatable $viewpage) {
        $data = $viewpage->export_for_template($this);
        return $this->render_from_template('mod_mooduell/viewquestions', $data);
    }
}
