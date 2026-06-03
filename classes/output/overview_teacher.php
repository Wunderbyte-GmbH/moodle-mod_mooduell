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

use context_course;
use mod_mooduell\mooduell;
use mod_mooduell\qr_code;
use mod_mooduell\tables\table_games;
use mod_mooduell\tables\table_highscores;
use mod_mooduell\tables\table_questions;
use moodle_url;
use question_bank;
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
     * Constructor for overview_teacher class.
     *
     * @param mooduell|null $mooduell The mooduell instance, can be null.
     */
    public function __construct(?mooduell $mooduell = null) {
        global $CFG;

        $data = [];
        $qrcode = new qr_code();
        $qrcodeimage = $qrcode->generate_qr_code();
        // Create the list of open games we can pass on to the renderer.
        $data['qrimage'] = $qrcodeimage;
        $data['webloginurl'] = $qrcode->generate_web_launch_url();
        $data['webapppreviewurl'] = $qrcode->generate_web_app_launch_url();

        $data['appstorelink'] = get_config('mooduell', 'appstoreurl');
        $data['playstorelink'] = get_config('mooduell', 'playstoreurl');
        $data['launchlogourl'] = $CFG->wwwroot . '/mod/mooduell/app/assets/images/Logo-full-whiteweb.png';

        $launchthemeconfig = $this->get_launch_theme_config();
        $data['launchthemecustom'] = $launchthemeconfig['launchthemecustom'];
        $data['launchthemeinherit'] = $launchthemeconfig['launchthemeinherit'];
        $data['launchstyle'] = $launchthemeconfig['launchstyle'];

        if (!empty($data['appstorelink'])) {
            $data['appstoreqrimage'] = $qrcode->generate_url_qr_code($data['appstorelink']);
        }

        if (!empty($data['playstorelink'])) {
            $data['playstoreqrimage'] = $qrcode->generate_url_qr_code($data['playstorelink']);
        }

        // Check if local_wunderbyte_table is installed with the required version.
        $wbtinfo = \core_plugin_manager::instance()->get_plugin_info('local_wunderbyte_table');
        $data['haswunderbyte'] = ($wbtinfo !== null && $wbtinfo->versiondb >= 2026020300);

        $data['opengames'] = $data['haswunderbyte'] ? $this->render_open_games_table($mooduell) : '';
        $data['finishedgames'] = $data['haswunderbyte'] ? $this->render_finished_games_table($mooduell) : '';
        $data['warnings'] = $mooduell->check_quiz();

        // Add the Name of the instance.
        $data['quizname'] = $mooduell->cm->name;
        $data['mooduellid'] = $mooduell->cm->id;
        // Add the list of questions.
        $data['questions'] = $data['haswunderbyte'] ? $this->render_questions_table($mooduell) : '';
        $data['highscores'] = $data['haswunderbyte'] ? $this->render_highscores_table($mooduell) : '';
        $data['categories'] = $mooduell->return_list_of_categories();
        $data['statistics'] = $data['haswunderbyte'] ? $mooduell->return_list_of_statistics_teacher() : null;
        $data['users_without_capability'] = $this->get_users_without_capability($mooduell);
        $data['questionnewurl'] = $CFG->wwwroot . '/question/bank/editquestion/question.php';
        $data['courseid'] = $mooduell->course->id;
        $data['sesskey'] = sesskey();
        $data['questioncategories'] = $this->build_question_categories($mooduell, $data['categories']);
        $data['questiontypes'] = $this->build_question_types();

        $this->data = $data;
    }

    /**
     * Build selectable catalogue data for opening the question bank.
     *
     * @param mooduell $mooduell
     * @param array $categories
     * @return array
     */
    private function build_question_categories(mooduell $mooduell, array $categories): array {
        $courseid = $mooduell->course->id;
        $defaultcmid = $mooduell->cm->id;

        $modals = [];
        foreach ($categories as $category) {
            $categoryid = (int) $category['catid'];
            $contextid  = (int) $category['contextid'];

            // Use the cmid of the question bank that owns this category's context,
            // so Moodle pre-selects the correct category in the question creation form.
            $catcmid = $defaultcmid;
            $context = \context::instance_by_id($contextid, IGNORE_MISSING);
            if ($context && $context->contextlevel == CONTEXT_MODULE) {
                $catcmid = (int) $context->instanceid;
            }

            $modals[] = [
                'categoryid' => $categoryid,
                'contextid' => $contextid,
                'catname' => $category['catname'],
                'value' => $categoryid . ',' . $contextid,
                'courseid' => $courseid,
                'catcmid' => $catcmid,
            ];
        }

        return $modals;
    }

    /**
     * Build supported question type choices.
     *
     * @return array
     */
    private function build_question_types(): array {
        $allowedqtypes = ['multichoice', 'truefalse', 'numerical', 'ddwtos'];
        $questiontypes = [];

        foreach (question_bank::get_creatable_qtypes() as $qtypename => $qtype) {
            if (!in_array($qtypename, $allowedqtypes, true)) {
                continue;
            }

            $questiontypes[] = [
                'name' => $qtypename,
                'label' => $qtype->menu_name(),
            ];
        }

        return $questiontypes;
    }

    /**
     * Build launch preview theme config for template.
     *
     * @return array
     */
    private function get_launch_theme_config(): array {
        $mode = (string) get_config('mooduell', 'launchthememode');
        $iscustom = ($mode === 'custom');

        if (!$iscustom) {
            return [
                'launchthemecustom' => false,
                'launchthemeinherit' => true,
                'launchstyle' => '',
            ];
        }

        $primary = $this->normalize_hex_color((string) get_config('mooduell', 'launchcolor_primary'), '#007497');
        $secondary = $this->normalize_hex_color((string) get_config('mooduell', 'launchcolor_secondary'), '#ec8600');
        $background = $this->normalize_hex_color((string) get_config('mooduell', 'launchcolor_background'), '#001e2e');
        $surface = $this->normalize_hex_color((string) get_config('mooduell', 'launchcolor_surface'), '#002d40');
        $text = $this->normalize_hex_color((string) get_config('mooduell', 'launchcolor_text'), '#ffffff');
        $textmuted = $this->normalize_hex_color((string) get_config('mooduell', 'launchcolor_textmuted'), '#b4c7d1');

        $style = '--mooduell-launch-primary: ' . $primary . '; '
            . '--mooduell-launch-secondary: ' . $secondary . '; '
            . '--mooduell-launch-bg-base: ' . $background . '; '
            . '--mooduell-launch-bg-surface: ' . $surface . '; '
            . '--mooduell-launch-text: ' . $text . '; '
            . '--mooduell-launch-text-muted: ' . $textmuted . ';';

        return [
            'launchthemecustom' => true,
            'launchthemeinherit' => false,
            'launchstyle' => $style,
        ];
    }

    /**
     * Validate and normalize hex colors from config.
     *
     * @param string $value
     * @param string $default
     * @return string
     */
    private function normalize_hex_color(string $value, string $default): string {
        $color = trim($value);
        if (preg_match('/^#([a-fA-F0-9]{3}|[a-fA-F0-9]{6})$/', $color)) {
            return $color;
        }
        return $default;
    }

    /**
     * Get a list of users who do not have the 'mooduell:viewinstance' capability.
     *
     * @param mooduell $mooduell
     * @return array
     */
    private function get_users_without_capability(mooduell $mooduell): array {
        global $DB;

        // Get the context of the course module.
        $context = context_course::instance($mooduell->course->id);

        // Get all enrolled users in the course.
        $enrolledusers = get_enrolled_users($context);

        $userswithoutcapability = [];

        foreach ($enrolledusers as $user) {
            if (
                !has_capability('webservice/rest:use', $context, $user)
                || !has_capability('moodle/webservice:createmobiletoken', $context, $user)
            ) {
                $userswithoutcapability[] = fullname($user);
            }
        }

        return $userswithoutcapability;
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
    private function render_open_games_table(mooduell $mooduell): string {
        return $this->render_games_table($mooduell, 'opengames');
    }

    /**
     * Render the open games table. This function automatically returns the table for teachers or students.
     *
     * @param mooduell $mooduell
     * @return string
     */
    private function render_finished_games_table(mooduell $mooduell): string {
        return $this->render_games_table($mooduell, 'finishedgames');
    }

    /**
     * Render the open games table. This function automatically returns the table for teachers or students.
     *
     * @param mooduell $mooduell
     * @param string $action
     * @return string
     */
    private function render_games_table(mooduell $mooduell, $action): string {

        $tablename = bin2hex(random_bytes(12));

        $gamestable = new table_games($tablename, $mooduell->cm->id);

        $finishedgames = $action == 'finishedgames' ? true : false;

        [$fields, $from, $where, $params] = $mooduell->return_sql_for_games('teacher', $finishedgames);

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

        [$idstring, $encodedtable, $html] = $gamestable->lazyouthtml(20, true);

        return $html;
    }


    /**
     * Render the highscores table. This function automatically returns the table for teachers or students.
     *
     * @param mooduell $mooduell
     * @return string
     */
    private function render_highscores_table(mooduell $mooduell): string {

        $tablename = bin2hex(random_bytes(12));
        $highscorestable = new table_highscores($tablename, $mooduell->cm->id);
        // Sort the table by descending score by default.
        $highscorestable->sort_default_column = 'score';
        $highscorestable->sort_default_order = SORT_DESC;

        [$fields, $from, $where, $params] = $mooduell->return_sql_for_highscores('teacher');

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

        [$idstring, $encodedtable, $html] = $highscorestable->lazyouthtml(20, true);

        return $html;
    }

    /**
     * Render the questions table.
     *
     * @param mooduell $mooduell
     * @return string
     */
    private function render_questions_table(mooduell $mooduell): string {

        $tablename = bin2hex(random_bytes(12));

        $questionstable = new table_questions($tablename, $mooduell->cm->id);
        // Sort the table by descending score by default.
        $questionstable->sort_default_column = 'id';
        $questionstable->sort_default_order = SORT_ASC;

        [$fields, $from, $where, $params] = $mooduell->return_sql_for_questions();

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

        [$idstring, $encodedtable, $html] = $questionstable->lazyouthtml(40, true);

        return $html;
    }
}
