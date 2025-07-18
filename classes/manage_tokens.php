<?php
// This file is part of Moodle - http:// moodle.org/
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
 * Class for mod_mooduell to react on the following events: course_module_created, user_enrolment_created.
 *
 * @package mod_mooduell
 * @copyright 2021 Wunderbyte GmbH <georg.maisser@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mooduell;

use coding_exception;
use context_system;
use dml_exception;
use moodle_exception;
use stdClass;

/**
 * Contains functions to execute when the event course_module_created or the event user_enrolment_created is triggered.
 */
class manage_tokens {
    /**
     * @var int|null
     */
    public $mooduellid = null;

    /**
     * Function to generate tokens for all users of a course in which a new MooDuell instance has been created.
     *
     * @param int $cmid The course module id of the MooDuell instance.
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function generate_tokens_for_all_instance_users(int $cmid) {
        global $DB;

        // Currently, ALL users enrolled in the course to which a MooDuell instance has been added, will be selected.
        $sql =
        'SELECT DISTINCT ue.userid
                  FROM {user_enrolments} ue
              LEFT JOIN {enrol} e ON e.id = ue.enrolid
                  WHERE e.courseid IN (
                 SELECT cm.course
                   FROM {course_modules} cm
                  WHERE cm.id = :cmid
        )';

        if ($records = $DB->get_records_sql($sql, ['cmid' => $cmid])) {
            foreach ($records as $record) {
                self::generate_token_for_user($record->userid);
            }
        }
    }

    /**
     * Deletes a user token for a given service.
     *
     * @param  mixed $servicename
     * @return void
     */
    public static function delete_user_token($servicename) {
        // Get Service id.
        global $USER, $DB;
         $service = $DB->get_record('external_services', ['shortname' => $servicename]);
        if (empty($service)) {
            // Will throw exception if no token found.
            return;
        }
        $DB->delete_records('external_tokens', ['userid' => $USER->id, 'externalserviceid' => $service->id]);
    }

    /**
     * Function to generate a token for a specific MooDuell user.
     *
     * @param int $userid The id of the user for which a token should be created.
     * @param string $servicename
     * @param int $duration
     * @throws coding_exception
     * @throws dml_exception
     * @return stdClass
     */
    public static function generate_token_for_user(int $userid, $servicename = 'mod_mooduell_external', $duration = 0) {

        global $DB, $USER, $CFG;

        $mooduellwebservice = $DB->get_record('external_services', ['shortname' => $servicename, 'enabled' => 1]);
        if (empty($mooduellwebservice)) {
            // Will throw an exception if the service can't be found.
            throw new moodle_exception('servicenotavailable', 'webservice');
        }

        // Check if a token has already been created for this user and this service.
        $conditions = [
            'userid' => $userid,
            'externalserviceid' => $mooduellwebservice->id,
            'tokentype' => EXTERNAL_TOKEN_PERMANENT,
        ];
        $tokens = $DB->get_records('external_tokens', $conditions, 'timecreated ASC');

        // Some sanity checks.
        foreach ($tokens as $key => $token) {
            // Checks related to a specific token. (script execution continue).
            $unsettoken = false;
            // If sid is set then there must be a valid associated session no matter the token type.
            if (!empty($token->sid)) {
                if (!\core\session\manager::session_exists($token->sid)) {
                    // This token will never be valid anymore, delete it.
                    $DB->delete_records('external_tokens', ['sid' => $token->sid]);
                    $unsettoken = true;
                }
            }

            // Remove token if it is not valid anymore.
            if (!empty($token->validuntil) && $token->validuntil < time()) {
                $DB->delete_records('external_tokens', ['token' => $token->token, 'tokentype' => EXTERNAL_TOKEN_PERMANENT]);
                $unsettoken = true;
            }

            // Remove token if its IP is restricted.
            if (isset($token->iprestriction) && !address_in_subnet(getremoteaddr(), $token->iprestriction)) {
                $unsettoken = true;
            }

            if ($unsettoken) {
                unset($tokens[$key]);
            }
        }

        // If some valid tokens exist then use the most recent.
        if (count($tokens) > 0) {
            $token = array_pop($tokens);
        } else {
            // Create a new token.
            $token = new stdClass();
            $token->token = md5(uniqid(rand(), 1));
            $token->userid = $userid;
            $token->tokentype = EXTERNAL_TOKEN_PERMANENT;
            $token->contextid = context_system::instance()->id;
            $token->creatorid = $USER->id;
            $token->timecreated = time();
            $token->externalserviceid = $mooduellwebservice->id;
            // Tokens created by this function do not expire.
            if ($duration == 0) {
                $token->validuntil = 0;
            } else {
                $token->validuntil = time() + $duration;
            }
            $token->iprestriction = null;
            $token->sid = null;
            $token->lastaccess = null;
            // Generate the private token, it must be transmitted only via https.
            $token->privatetoken = random_string(64);
            $token->id = $DB->insert_record('external_tokens', $token);

            $eventtoken = clone $token;
            $eventtoken->privatetoken = null;

            // Moodle 4.3 require token name.
            if ($CFG->version >= 2023100900) {
                $eventtoken->name = $servicename;
            }

            $params = [
                'objectid' => $eventtoken->id,
                'relateduserid' => $userid,
                'other' => [
                    'auto' => true,
                ],
            ];
            $event = \core\event\webservice_token_created::create($params);
            $event->add_record_snapshot('external_tokens', $eventtoken);
            $event->trigger();
        }
        return $token;
    }
}
