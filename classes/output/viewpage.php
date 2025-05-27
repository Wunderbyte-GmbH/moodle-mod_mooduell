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

use renderable;
use renderer_base;
use templatable;

/**
 * viewpage class to display view.php
 * @package mod_mooduell
 *
 */
class viewpage implements renderable, templatable {
    /**
     * An array of data
     *
     * @var array
     */
    protected $data;

    /**
     * Constructor.
     * @param array $data
     */
    public function __construct(array $data) {
        $this->data = $data;
    }

    /**
     * Prepare data for use in a template
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output) {
        $data = $this->data;

        /*if (isset($data[''questions'])) {
            foreach ($data['questions'] as $question) {
                $question->replace_category_id_by_name();
            }
        }*/

        return $data;
    }
}
