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
 * mod_mooduell data generator.
 *
 * @package    mod_mooduell
 * @category   test
 * @copyright  2021 Georg Maisser <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/question/format/xml/format.php');

/**
 * mod_lesson data generator class.
 *
 * @package    mod_mooduell
 * @category   test
 * @copyright  2021 Georg Maisser <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_mooduell_generator extends testing_module_generator {

    /**
     * @var int keep track of how many games have been created.
     */
    protected $gamescount = 0;

    /**
     * To be called from data reset code only,
     * do not use in tests.
     * @return void
     */
    public function reset() {
        $this->gamescount = 0;
        parent::reset();
    }

    /**
     * To create a new instance.
     * @param array|null $record
     * @param array|null $options
     * @return stdClass
     * @throws coding_exception
     */
    public function create_instance($record = null, array $options = null) {
        return parent::create_instance($record, (array) $options);
    }

    /**
     * Import questions to the question bank from Moodle XML file.
     *
     * @param array $data
     * @return void
     */
    public function create_mooduell_questions(array $data) {
        global $CFG;

        $filepath = "{$CFG->dirroot}/{$data['filepath']}";

        if (!file_exists($filepath)) {
            throw new coding_exception("File '{$filepath}' does not exist!");
        }

        if (empty($data['questioncategoryid'])) {
            throw new coding_exception("No question category provided!");
        }
        $questioncategory = $this->get_questioncategory($data['questioncategoryid']);

        $course = get_course($data['courseid']);
        $context = context_course::instance($course->id);

        // Load data into class.
        $qformat = new \qformat_xml();
        $qformat->setCategory($questioncategory);
        $qformat->setContexts([$context]);
        $qformat->setCourse($course);
        $qformat->setFilename($filepath);
        $qformat->setRealfilename($filepath);
        $qformat->setCatfromfile(false);
        $qformat->setContextfromfile(false);
        $qformat->setStoponerror(true);
        // Do anything before that we need to.
        ob_start();
        if (!$qformat->importpreprocess()) {
            $output = ob_get_contents();
            ob_end_clean();
            throw new moodle_exception('Cannot import {$filepath} (preprocessing). Output: {$output}', 'mod_mooduell', '');
        }
        // Process the uploaded file.
        if (!$qformat->importprocess()) {
            $output = ob_get_contents();
            ob_end_clean();
            throw new moodle_exception('Cannot import {$filepath} (processing). Output: {$output}', 'mod_mooduell', '');
        }
        // In case anything needs to be done after.
        if (!$qformat->importpostprocess()) {
            $output = ob_get_contents();
            ob_end_clean();
            throw new moodle_exception('Cannot import {$filepath} (postprocessing). Output: {$output}', 'mod_mooduell', '');
        }
        ob_end_clean();
    }

    /**
     * Get the question category by given ID.
     *
     * @param int $id the question category id.
     * @return stdClass the question category record.
     */
    protected function get_questioncategory($id) {
        global $DB;
        return $DB->get_record('question_categories', ['id' => $id], '*', MUST_EXIST);
    }
}
