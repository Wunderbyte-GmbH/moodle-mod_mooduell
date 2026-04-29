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
 * External API implementation for mod_mooduell.
 *
 * @package    mod_mooduell
 * @category   external
 * @copyright  2020 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mooduell\external;

use external_api;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

/**
 * External function implementation.
 */
class update_profile_picture extends external_api {
    /**
     * Describes the parameters for the external function.
     *
     * @return \external_function_parameters
     */
    public static function execute_parameters() {
        return new \external_function_parameters([
                        'filename'  => new \external_value(PARAM_FILE, 'file name'),
                        'filecontent' => new \external_value(PARAM_TEXT, 'file content'),
                ]);
    }
    /**
     * Executes the external function.
     *
     * @param string $filename
     * @param string $filecontent
     * @return mixed
     */
    public static function execute(string $filename, string $filecontent) {
        global $USER, $CFG;

        $fileinfo = self::validate_parameters(self::execute_parameters(), [
                'filename' => $filename,
                'filecontent' => $filecontent,
            ]);

        if (!isset($fileinfo['filecontent'])) {
            throw new \moodle_exception('nofile');
        }

        [$w, $h] = getimagesizefromstring(base64_decode($filecontent));
        if ($w > 500 || $h > 1000) {
            return ['filename' => 'TOOLARGE'];
        }

        $context = \context_system::instance();
        $fs = get_file_storage();

        $fileinfo = [
            'contextid' => $context->id,
            'component' => 'mod_mooduell',
            'filearea' => 'aliasavatar',
            'itemid' => $USER->id,
            'filepath' => '/',
            'filename' => $filename . time() . '.jpg',
        ];

        $files = $fs->get_area_files($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'], $fileinfo['itemid']);
        foreach ($files as $f) {
            $f->delete();
        }

        $dir = make_temp_directory('wsupload') . '/';

        if (empty($fileinfo['filename'])) {
            $filename = uniqid('wsupload', true) . '_' . time() . '.tmp';
        } else {
            $filename = $fileinfo['filename'];
        }

        if (file_exists($dir . $filename)) {
            $savedfilepath = $dir . uniqid('m') . $filename;
        } else {
            $savedfilepath = $dir . $filename;
        }

        $fileinfo['filecontent'] = strtr($filecontent, '._-', '+/=');

        file_put_contents($savedfilepath, base64_decode($fileinfo['filecontent']));

        require_once($CFG->libdir . '/gdlib.php');

        @chmod($savedfilepath, $CFG->filepermissions);
        unset($fileinfo['filecontent']);
        $fs->create_file_from_pathname($fileinfo, $savedfilepath);

        unset($savedfilepath);

        \cache_helper::purge_by_event('setbackuserscache');

        return ['filename' => $fileinfo['filename']];
    }
    /**
     * Describes the return structure for the external function.
     *
     * @return \external_description
     */
    public static function execute_returns() {
        return new \external_single_structure([
                        'filename' => new \external_value(PARAM_TEXT, 'image url'),
                ]);
    }
}
