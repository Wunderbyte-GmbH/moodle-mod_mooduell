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
 * The Wunderbyte table class is an extension of the tablelib table_sql class.
 *
 * @package mod_mooduell
 * @copyright 2024 Wunderbyte Gmbh <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mooduell\local\wunderbyte_table\local\settings;

use cache;
use coding_exception;
use mod_mooduell\local\wunderbyte_table\filter;
use mod_mooduell\local\wunderbyte_table\output\table;
use mod_mooduell\local\wunderbyte_table\wunderbyte_table;
use MoodleQuickForm;
use stdClass;

/**
 * Handles the settings of the table.
 * @package mod_mooduell
 */
class tablesettings {
    /**
     * This returns the settings like they were initially programmed for the specific table.
     *
     * @param wunderbyte_table $table
     * @return array
     */
    public static function return_initial_settings(wunderbyte_table $table) {

        $tablesettings['filtersettings'] = $table->subcolumns['datafields'];

        return $tablesettings;
    }

    /**
     * Applies the wb table settings to the output table class.
     * Overrides coded values with manually set values.
     * @param wunderbyte_table $table
     * @return void
     */
    public static function apply_setting(wunderbyte_table $table) {

        global $CFG;

        $lang = filter::current_language();

        $key = $table->tablecachehash . $lang . '_filterjson';

        $jsontablesettings = self::return_jsontablesettings_from_db(0, $key);
        $settingsobject = json_decode($jsontablesettings);

        // Nothing saved?
        if (empty($settingsobject->general)) {
            return;
        }

        $table->showdownloadbutton = $settingsobject->general->showdownloadbutton;
        $table->applyfilterondownload = $settingsobject->general->applyfilterondownload;
        $table->showreloadbutton = $settingsobject->general->showreloadbutton;
        $table->showdownloadbuttonatbottom = $settingsobject->general->showdownloadbuttonatbottom;
        $table->showfilterontop = $settingsobject->general->showfilterontop;
        $table->showcountlabel = $settingsobject->general->showcountlabel;
        $table->gotopage = $settingsobject->general->gotopage ?? false;
        $table->showrowcountselect = $settingsobject->general->showrowcountselect;
        $table->stickyheader = $settingsobject->general->stickyheader;
        $table->addcheckboxes = $settingsobject->general->addcheckboxes;
        $table->pagesize = $settingsobject->general->pagesize;
        $table->filteronloadinactive = $settingsobject->general->filteronloadinactive;
        $table->placebuttonandpageelementsontop = $settingsobject->general->placebuttonandpageelementsontop;
        $table->infinitescroll = $settingsobject->general->infinitescroll;
        $table->showaddfilterbutton = $settingsobject->general->showaddfilterbutton;
    }

    /**
     * Find filterjson for user. If a userid is transmitted...
     * ... we first look for an individual setting for the user.
     * If there is no individual setting for the specific user, we fall back to the general one.
     * If the userid is precisely 0, we always get the general one.
     * @param int $id
     * @param string $hash
     * @param int $userid
     * @return string
     * @throws dml_exception
     */
    public static function return_jsontablesettings_from_db(int $id = 0, string $hash = '', int $userid = -1) {
        // Mooduell does not create the dedicated wunderbyte settings table.
        // Keep DB-backed table settings disabled and return empty settings.
        return '{}';
    }

    /**
     * Add Table settings form elements.
     * @param MoodleQuickForm $mform
     * @param array $formdata
     * @return void
     * @throws coding_exception
     */
    public static function definition(MoodleQuickForm $mform, array $formdata) {

        $mform->addElement('advcheckbox', 'gs_wb_showdownloadbutton', get_string('showdownloadbutton', 'mod_mooduell'));

        $mform->addElement(
            'advcheckbox',
            'gs_wb_applyfilterondownload',
            get_string('applyfilterondownload', 'mod_mooduell')
        );

        $mform->addElement('advcheckbox', 'gs_wb_showreloadbutton', get_string('showreloadbutton', 'mod_mooduell'));

        $mform->addElement('advcheckbox', 'gs_wb_showfilterontop', get_string('showfilterontop', 'mod_mooduell'));

        $mform->addElement('advcheckbox', 'gs_wb_showaddfilterbutton', get_string('showaddfilterbutton', 'mod_mooduell'));

        $mform->addElement(
            'advcheckbox',
            'gs_wb_showdownloadbuttonatbottom',
            get_string('showdownloadbuttonatbottom', 'mod_mooduell')
        );

        $mform->addElement('advcheckbox', 'gs_wb_showcountlabel', get_string('showcountlabel', 'mod_mooduell'));

        $mform->addElement('advcheckbox', 'gs_wb_gotopage', get_string('showgotopage', 'mod_mooduell'));

        $mform->addElement('advcheckbox', 'gs_wb_stickyheader', get_string('stickyheader', 'mod_mooduell'));

        $mform->addElement('advcheckbox', 'gs_wb_showrowcountselect', get_string('showrowcountselect', 'mod_mooduell'));

        $mform->addElement('advcheckbox', 'gs_wb_addcheckboxes', get_string('addcheckboxes', 'mod_mooduell'));

        $mform->addElement(
            'advcheckbox',
            'gs_wb_placebuttonandpageelementsontop',
            get_string('placebuttonandpageelementsontop', 'mod_mooduell')
        );

        $mform->addElement(
            'advcheckbox',
            'gs_wb_filteronloadinactive',
            get_string('filteronloadinactive', 'mod_mooduell')
        );

        $mform->addElement('text', 'gs_wb_pagesize', get_string('pagesize', 'mod_mooduell'));
        $mform->setType('pagesize', PARAM_INT);

        $mform->addElement('text', 'gs_wb_infinitescroll', get_string('infinitescroll', 'mod_mooduell'));
        $mform->setType('infinitescroll', PARAM_INT);
    }

    /**
     * Set data.
     * @param stdClass $data
     * @param wunderbyte_table $table
     * @return void
     */
    public static function set_data(stdClass &$data, wunderbyte_table $table) {

        // We need to localize the filter for every user.
        $lang = filter::current_language();
        $key = $table->tablecachehash . $lang . '_filterjson';

        $jsontablesettings = self::return_jsontablesettings_from_db(0, $key, 0);

        $ts = json_decode($jsontablesettings);

        $data->gs_wb_showdownloadbutton = $ts->general->showdownloadbutton ?? ($table->showdownloadbutton ? 1 : 0);
        $data->gs_wb_applyfilterondownload = $ts->general->applyfilterondownload ?? ($table->applyfilterondownload ? 1 : 0);
        $data->gs_wb_showaddfilterbutton = $ts->general->showaddfilterbutton ?? ($table->showaddfilterbutton ? 1 : 0);
        $data->gs_wb_showreloadbutton = $ts->general->showreloadbutton ?? ($table->showreloadbutton ? 1 : 0);
        $data->gs_wb_showfilterontop = $ts->general->showfilterontop ?? ($table->showfilterontop ? 1 : 0);
        $data->gs_wb_showdownloadbuttonatbottom = $ts->general->showdownloadbuttonatbottom ??
            ($table->showdownloadbuttonatbottom ? 1 : 0);
        $data->gs_wb_showcountlabel = $ts->general->showcountlabel ?? ($table->showcountlabel ? 1 : 0);
        $data->gs_wb_gotopage = $ts->general->gotopage ?? ($table->gotopage ? 1 : 0);
        $data->gs_wb_stickyheader = $ts->general->stickyheader ?? ($table->stickyheader ? 1 : 0);
        $data->gs_wb_showrowcountselect = $ts->general->showrowcountselect ?? ($table->showrowcountselect ? 1 : 0);
        $data->gs_wb_addcheckboxes = $ts->general->addcheckboxes ?? ($table->addcheckboxes ? 1 : 0);
        $data->gs_wb_filteronloadinactive = $ts->general->filteronloadinactive ?? ($table->filteronloadinactive ? 1 : 0);
        $data->gs_wb_placebuttonandpageelementsontop
            = $ts->general->placebuttonandpageelementsontop ?? ($table->placebuttonandpageelementsontop ? 1 : 0);
        $data->gs_wb_pagesize = $ts->general->pagesize ?? $table->pagesize;
        $data->gs_wb_infinitescroll = $ts->general->infinitescroll ?? $table->infinitescroll;
    }

    /**
     * This function runs through all installed field classes and executes the prepare save function.
     * Returns an array of warnings as string.
     * @param stdClass $formdata
     * @param stdClass $newdata
     * @return array
     */
    public static function process_data(stdClass &$formdata, stdClass &$newdata): array {

        // First, we get the original filterjson.

        $originaltablesettings = json_decode($formdata->wb_jsontablesettings);

        $keystoskip = [
            'wb_jsontablesettings',
            'encodedtable',
        ];

        // Now we update with the new values.
        foreach ($formdata as $key => $value) {
            if (in_array($key, $keystoskip)) {
                continue;
            }

            [$columnidentifier, $fieldidentifier] = explode('_wb_', $key);

            // We don't treat the gs column identifier.
            if ($columnidentifier === 'gs') {
                if (!isset($originaltablesettings->general)) {
                    if (empty($originaltablesettings)) {
                        $originaltablesettings = new stdClass();
                    }
                    $originaltablesettings->general = new stdClass();
                }
                $originaltablesettings->general->$fieldidentifier = $value;
                continue;
            }

            if (isset($originaltablesettings->filtersettings->{$columnidentifier})) {
                // The checkbox comes directly like this.
                if (isset($originaltablesettings->filtersettings->{$columnidentifier}->{$key})) {
                    $originaltablesettings->filtersettings->{$columnidentifier}->{$key} = $value;
                } else if (isset($originaltablesettings->filtersettings->{$columnidentifier}->{$fieldidentifier})) {
                    $originaltablesettings->filtersettings->{$columnidentifier}->{$fieldidentifier} = $value;
                }
            }
        }

        $table = wunderbyte_table::instantiate_from_tablecache_hash($formdata->encodedtable);
        // We need to localize the filter for every user.
        $lang = filter::current_language();
        $cachekey = $table->tablecachehash . $lang . '_filterjson';

        filter::save_settings(
            $table,
            $cachekey,
            (array)$originaltablesettings,
            false
        );

        $cache = cache::make($table->cachecomponent, $table->rawcachename);
        $cache->purge();
        return [];
    }
}
