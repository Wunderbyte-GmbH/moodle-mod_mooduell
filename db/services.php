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
 * Quiz external functions and service definitions.
 *
 * @package mod_mooduell
 * @category external
 * @copyright 2020 Wunderbyte GmbH (info@wunderbyte.at)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since Moodle 3.1
 */

defined('MOODLE_INTERNAL') || die();

$services = [
        'Wunderbyte MooDuell Tokens' => [ // Very important, don't rename or will break local_bookingapi plugin!!!
                'functions' => [
                        'mod_mooduell_get_user_token',
                        'core_webservice_get_site_info',
                ],
                'restrictedusers' => 0,
                'shortname' => 'mod_mooduell_tokens',
                'downloadfiles' => 1, // Allow file downloads.
                'uploadfiles'  => 1, // Allow file uploads.
                'enabled' => 1,
        ],
        'Wunderbyte MooDuell external' => [ // Very important, don't rename or will break local_bookingapi plugin!!!
                'functions' => [
                        'mod_mooduell_get_courses_with_caps',
                        'mod_mooduell_get_quizzes_with_caps',
                        'mod_mooduell_get_support',
                        'mod_mooduell_get_purchases',
                        'mod_mooduell_delete_iap',
                        'mod_mooduell_update_iap',
                        'core_webservice_get_site_info',
                        'mod_mooduell_start_attempt',
                        'mod_mooduell_get_game_data',
                        'mod_mooduell_get_quiz_users',
                        'mod_mooduell_get_quizzes_by_courses',
                        'mod_mooduell_get_games_by_courses',
                        'mod_mooduell_answer_question',
                        'mod_mooduell_get_user_stats',
                        'mod_mooduell_get_highscores',
                        'mod_mooduell_set_alternatename',
                        'mod_mooduell_set_pushtokens',
                        'mod_mooduell_giveup_game',
                        'mod_mooduell_update_profile_picture',
                        'core_badges_get_user_badges',
                ],
                'restrictedusers' => 0,
                'shortname' => 'mod_mooduell_external',
                'downloadfiles' => 1, // Allow file downloads.
                'uploadfiles'  => 1, // Allow file uploads.
                'enabled' => 1,
        ],
];


$functions = [
        'mod_mooduell_get_user_token' => [
                'classname' => 'mod_mooduell_external',
                'methodname' => 'get_usertoken',
                'classpath' => 'mod/mooduell/classes/external.php',
                'description' => 'Returns user token for current user',
                'type' => 'write',
                'capabilities' => 'mod/mooduell:viewinstance',
                'ajax' => true,
        ],
        'mod_mooduell_update_iap' => [
                'classname' => 'mod_mooduell_external',
                'methodname' => 'update_iapurchases',
                'classpath' => 'mod/mooduell/classes/external.php',
                'description' => 'Updates the IAP for the user',
                'type' => 'write',
                'capabilities' => 'mod/mooduell:viewinstance',
                'ajax' => true,
        ],
        'mod_mooduell_start_attempt' => [
                'classname' => 'mod_mooduell_external',
                'methodname' => 'start_attempt',
                'classpath' => 'mod/mooduell/classes/external.php',
                'description' => 'Starts a new MooDuell game.',
                'type' => 'write',
                'ajax' => true,
                'capabilities' => 'mod/mooduell:play',
                'services' => [
                        'mod_mooduell_external',
                ],
        ],
        'mod_mooduell_get_game_data' => [
                'classname' => 'mod_mooduell_external',
                'methodname' => 'get_game_data',
                'classpath' => 'mod/mooduell/classes/external.php',
                'description' => 'Loads all the relevant data of the active MooDuell game for the active user.',
                'type' => 'read',
                'ajax' => true,
                'capabilities' => 'mod/mooduell:play',
                'services' => [
                        'mod_mooduell_external',
                ],
        ],
        'mod_mooduell_get_quiz_users' => [
                'classname' => 'mod_mooduell_external',
                'methodname' => 'get_quiz_users',
                'classpath' => 'mod/mooduell/classes/external.php',
                'description' => 'Retrieve all the available co players for the active user in a MooDuell game.',
                'type' => 'read',
                'ajax' => true,
                'capabilities' => 'mod/mooduell:play',
                'services' => [
                        'mod_mooduell_external',
                ],
        ],
        'mod_mooduell_get_quizzes_by_courses' => [
                'classname' => 'mod_mooduell_external',
                'methodname' => 'get_quizzes_by_courses',
                'classpath' => 'mod/mooduell/classes/external.php',
                'description' => 'Retrieves all the available quizzes without the games for a course or for all the courses.',
                'type' => 'read',
                'ajax' => true,
                'capabilities' => 'mod/mooduell:play',
                'services' => [
                        'mod_mooduell_external',
                ],
        ],
        'mod_mooduell_get_games_by_courses' => [
                'classname' => 'mod_mooduell_external',
                'methodname' => 'get_games_by_courses',
                'classpath' => 'mod/mooduell/classes/external.php',
                'description' => 'Retrieves all the games avalable for the active user
                ordered by quizzes within a course or for the whole site.',
                'type' => 'read',
                'ajax' => true,
                'capabilities' => 'mod/mooduell:play',
                'services' => [
                        'mod_mooduell_external',
                ],
        ],
        'mod_mooduell_answer_question' => [
                'classname' => 'mod_mooduell_external',
                'methodname' => 'answer_question',
                'classpath' => 'mod/mooduell/classes/external.php',
                'description' => 'Answers the active question.',
                'type' => 'write',
                'ajax' => true,
                'capabilities' => 'mod/mooduell:play',
                'services' => [
                        'mod_mooduell_external',
                ],
        ],
        'mod_mooduell_get_user_stats' => [
                'classname' => 'mod_mooduell_external',
                'methodname' => 'get_user_stats',
                'classpath' => 'mod/mooduell/classes/external.php',
                'description' => 'Retrieves the stats of the active user.',
                'type' => 'read',
                'ajax' => true,
                'capabilities' => 'mod/mooduell:play',
                'services' => [
                        'mod_mooduell_external',
                ],
        ],
        'mod_mooduell_get_highscores' => [
                'classname' => 'mod_mooduell_external',
                'methodname' => 'get_highscores',
                'classpath' => 'mod/mooduell/classes/external.php',
                'description' => 'Retrieves the highscores visible to the active user.',
                'type' => 'read',
                'ajax' => true,
                'capabilities' => 'mod/mooduell:play',
                'services' => [
                        'mod_mooduell_external',
                ],
        ],
        'mod_mooduell_set_alternatename' => [
                'classname' => 'mod_mooduell_external',
                'methodname' => 'set_alternatename',
                'classpath' => 'mod/mooduell/classes/external.php',
                'description' => 'This updates the custom field "MooDuell Alias" for the active user.',
                'type' => 'write',
                'ajax' => true,
                'capabilities' => 'mod/mooduell:play',
                'services' => [
                        'mod_mooduell_external',
                ],
        ],
        'mod_mooduell_set_pushtokens' => [
                'classname' => 'mod_mooduell_external',
                'methodname' => 'set_pushtokens',
                'classpath' => 'mod/mooduell/classes/external.php',
                'description' => 'This sets a pushtoken for the active user and for the active device.',
                'type' => 'write',
                'ajax' => true,
                'capabilities' => 'mod/mooduell:play',
                'services' => [
                        'mod_mooduell_external',
                ],
        ],
        'mod_mooduell_giveup_game' => [
                'classname' => 'mod_mooduell_external',
                'methodname' => 'giveup_game',
                'classpath' => 'mod/mooduell/classes/external.php',
                'description' => 'Used to allow a user to give up a game.',
                'type' => 'write',
                'ajax' => true,
                'capabilities' => 'mod/mooduell:play',
                'services' => [
                        'mod_mooduell_external',
                ],
        ],
        'mod_mooduell_update_profile_picture' => [
                'classname' => 'mod_mooduell_external',
                'methodname' => 'update_profile_picture',
                'classpath' => 'mod/mooduell/classes/external.php',
                'description' => 'This documentation will be displayed in the generated API documentation
                              (Administration > Plugins > Webservices > API documentation)',
                'type' => 'write',
                'ajax' => true,
                'capabilities' => 'mod/mooduell:play',
                'services' => [
                        'mod_mooduell_external',
                ],
        ],
        'mod_mooduell_get_purchases' => [
                'classname' => 'mod_mooduell_external',
                'methodname' => 'get_mooduell_purchases',
                'classpath' => 'mod/mooduell/classes/external.php',
                'description' => 'Returns a list of relevant purchases.',
                'type' => 'read',
                'capabilities' => 'mod/mooduell:viewinstance',
                'ajax' => true,
        ],
        'mod_mooduell_get_support' => [
                'classname' => 'mod_mooduell_external',
                'methodname' => 'get_mooduell_support',
                'classpath' => 'mod/mooduell/classes/external.php',
                'description' => 'Returns support information.',
                'type' => 'read',
                'capabilities' => 'mod/mooduell:viewinstance',
                'ajax' => true,
        ],
        'mod_mooduell_get_courses_with_caps' => [
                'classname' => 'mod_mooduell_external',
                'methodname' => 'get_courses_with_caps',
                'classpath' => 'mod/mooduell/classes/external.php',
                'description' => 'Returns courses that can be unlocked.',
                'type' => 'read',
                'capabilities' => 'mod/mooduell:canpurchase',
                'ajax' => true,
        ],
        'mod_mooduell_get_quizzes_with_caps' => [
                'classname' => 'mod_mooduell_external',
                'methodname' => 'get_quizzes_with_caps',
                'classpath' => 'mod/mooduell/classes/external.php',
                'description' => 'Returns quizzes that can be unlocked.',
                'type' => 'read',
                'capabilities' => 'mod/mooduell:canpurchase',
                'ajax' => true,
        ],
        'mod_mooduell_delete_iap' => [
                'classname' => 'mod_mooduell_external',
                'methodname' => 'delete_iapurchases',
                'classpath' => 'mod/mooduell/classes/external.php',
                'description' => 'Deletes a single IAP',
                'type' => 'write',
                'capabilities' => 'mod/mooduell:viewinstance',
                'ajax' => true,
        ],
];
