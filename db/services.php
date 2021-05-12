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
 * @package mod_mooduell
 * @category external
 * @copyright 2020 Wunderbyte GmbH (info@wunderbyte.at)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since Moodle 3.1
 */

defined('MOODLE_INTERNAL') || die();

$functions = array(
        'mod_mooduell_start_attempt' => array( // ... local_PLUGINNAME_FUNCTIONNAME is the name of the web service function
            // ... that the client will call.
                'classname' => 'mod_mooduell_external', // Create this class in componentdir/classes/external .
                'methodname' => 'start_attempt', // Implement this function into the above class.
                'classpath' => 'mod/mooduell/classes/external.php',
                'description' => 'This documentation will be displayed in the generated API documentation
                (Administration > Plugins > Webservices > API documentation)',
                'type' => 'write', // The value is 'write' if your function does any database change, otherwise it is 'read'.
                'ajax' => true, // True/false if you allow this web service function to be callable via ajax.
                'capabilities' => 'mod/mooduell:view',
                'services' => array(
                        MOODLE_OFFICIAL_MOBILE_SERVICE
                )
        ),
        'mod_mooduell_get_game_data' => array( // ... local_PLUGINNAME_FUNCTIONNAME is the name of the web service function ...
            // ... that the client will call.
                'classname' => 'mod_mooduell_external', // Create this class in componentdir/classes/external
                'methodname' => 'get_game_data', // Implement this function into the above class
                'classpath' => 'mod/mooduell/classes/external.php',
                'description' => 'This documentation will be displayed in the generated API documentation
                                  (Administration > Plugins > Webservices > API documentation)',
                'type' => 'read', // the value is 'write' if your function does any database change, otherwise it is 'read'.
                'ajax' => true, // true/false if you allow this web service function to be callable via ajax
                'capabilities' => 'mod/mooduell:view',
                'services' => array(
                        MOODLE_OFFICIAL_MOBILE_SERVICE
                )
        ),
        'mod_mooduell_get_quiz_users' => array( // ... local_PLUGINNAME_FUNCTIONNAME is the name of the web service function ...
            // ... that the client will call.
                'classname' => 'mod_mooduell_external', // Create this class in componentdir/classes/external
                'methodname' => 'get_quiz_users', // Implement this function into the above class
                'classpath' => 'mod/mooduell/classes/external.php',
                'description' => 'This documentation will be displayed in the generated API documentation
                                  (Administration > Plugins > Webservices > API documentation)',
                'type' => 'read', // the value is 'write' if your function does any database change, otherwise it is 'read'.
                'ajax' => true, // true/false if you allow this web service function to be callable via ajax
                'capabilities' => 'mod/mooduell:view',
                'services' => array(
                        MOODLE_OFFICIAL_MOBILE_SERVICE
                )
        ),
        'mod_mooduell_get_quizzes_by_courses' => array( // ... local_PLUGINNAME_FUNCTIONNAME is the name of the web service function...
            // ... that the client will call.
                'classname' => 'mod_mooduell_external', // Create this class in componentdir/classes/external
                'methodname' => 'get_quizzes_by_courses', // Implement this function into the above class
                'classpath' => 'mod/mooduell/classes/external.php',
                'description' => 'This documentation will be displayed in the generated API documentation
                              (Administration > Plugins > Webservices > API documentation)',
                'type' => 'read', // The value is 'write' if your function does any database change, otherwise it is 'read'.
                'ajax' => true, // True/false if you allow this web service function to be callable via ajax.
                'capabilities' => 'mod/mooduell:view',
                'services' => array(
                        MOODLE_OFFICIAL_MOBILE_SERVICE
                )
        ),
        'mod_mooduell_get_games_by_courses' => array( // ... local_PLUGINNAME_FUNCTIONNAME is the name of the ...
            // ... web service function that the client will call.
                'classname' => 'mod_mooduell_external', // Create this class in componentdir/classes/external
                'methodname' => 'get_games_by_courses', // Implement this function into the above class
                'classpath' => 'mod/mooduell/classes/external.php',
                'description' => 'This documentation will be displayed in the generated API documentation
                              (Administration > Plugins > Webservices > API documentation)',
                'type' => 'read', // The value is 'write' if your function does any database change, otherwise it is 'read'.
                'ajax' => true, // True/false if you allow this web service function to be callable via ajax
                'capabilities' => 'mod/mooduell:view',
                'services' => array(
                        MOODLE_OFFICIAL_MOBILE_SERVICE
                )
        ),
        'mod_mooduell_answer_question' => array( // ... local_PLUGINNAME_FUNCTIONNAME is the name of the ...
            // ... web service function that the client will call.
                'classname' => 'mod_mooduell_external', // Create this class in componentdir/classes/external
                'methodname' => 'answer_question', // Implement this function into the above class
                'classpath' => 'mod/mooduell/classes/external.php',
                'description' => 'This documentation will be displayed in the generated API documentation
                              (Administration > Plugins > Webservices > API documentation)',
                'type' => 'write', // The value is 'write' if your function does any database change, otherwise it is 'read'.
                'ajax' => true, // True/false if you allow this web service function to be callable via ajax
                'capabilities' => 'mod/mooduell:view',
                'services' => array(
                        MOODLE_OFFICIAL_MOBILE_SERVICE
                )
        ),
        'mod_mooduell_get_user_stats' => array( // ... local_PLUGINNAME_FUNCTIONNAME is the name of the ...
            // ... web service function that the client will call.
                'classname' => 'mod_mooduell_external', // Create this class in componentdir/classes/external
                'methodname' => 'get_user_stats', // Implement this function into the above class
                'classpath' => 'mod/mooduell/classes/external.php',
                'description' => 'This documentation will be displayed in the generated API documentation
                              (Administration > Plugins > Webservices > API documentation)',
                'type' => 'read', // The value is 'write' if your function does any database change, otherwise it is 'read'.
                'ajax' => true, // True/false if you allow this web service function to be callable via ajax
                'capabilities' => 'mod/mooduell:view',
                'services' => array(
                        MOODLE_OFFICIAL_MOBILE_SERVICE
                )
        ),
        'mod_mooduell_get_highscores' => array( // ... local_PLUGINNAME_FUNCTIONNAME is the name of the ...
            // ... web service function that the client will call.
                'classname' => 'mod_mooduell_external', // Create this class in componentdir/classes/external
                'methodname' => 'get_highscores', // Implement this function into the above class
                'classpath' => 'mod/mooduell/classes/external.php',
                'description' => 'This documentation will be displayed in the generated API documentation
                              (Administration > Plugins > Webservices > API documentation)',
                'type' => 'read', // The value is 'write' if your function does any database change, otherwise it is 'read'.
                'ajax' => true, // True/false if you allow this web service function to be callable via ajax
                'capabilities' => 'mod/mooduell:view',
                'services' => array(
                        MOODLE_OFFICIAL_MOBILE_SERVICE
                )
        ),
        'mod_mooduell_set_alternatename' => array( // ... local_PLUGINNAME_FUNCTIONNAME is the name of the ...
            // ... web service function that the client will call.
                'classname' => 'mod_mooduell_external', // Create this class in componentdir/classes/external
                'methodname' => 'set_alternatename', // Implement this function into the above class
                'classpath' => 'mod/mooduell/classes/external.php',
                'description' => 'This documentation will be displayed in the generated API documentation
                              (Administration > Plugins > Webservices > API documentation)',
                'type' => 'write', // The value is 'write' if your function does any database change, otherwise it is 'read'.
                'ajax' => true, // True/false if you allow this web service function to be callable via ajax
                'capabilities' => 'mod/mooduell:view',
                'services' => array(
                        MOODLE_OFFICIAL_MOBILE_SERVICE
                )
        ),
        'mod_mooduell_set_pushtokens' => array( // ... local_PLUGINNAME_FUNCTIONNAME is the name of the ...
            // ... web service function that the client will call.
                'classname' => 'mod_mooduell_external', // Create this class in componentdir/classes/external
                'methodname' => 'set_pushtokens', // Implement this function into the above class
                'classpath' => 'mod/mooduell/classes/external.php',
                'description' => 'This documentation will be displayed in the generated API documentation
                              (Administration > Plugins > Webservices > API documentation)',
                'type' => 'write', // The value is 'write' if your function does any database change, otherwise it is 'read'.
                'ajax' => true, // True/false if you allow this web service function to be callable via ajax
                'capabilities' => 'mod/mooduell:view',
                'services' => array(
                        MOODLE_OFFICIAL_MOBILE_SERVICE
                )
        ),
        'mod_mooduell_get_pushtokens' => array( // ... local_PLUGINNAME_FUNCTIONNAME is the name of the ...
            // ... web service function that the client will call.
                'classname' => 'mod_mooduell_external', // Create this class in componentdir/classes/external
                'methodname' => 'get_pushtokens', // Implement this function into the above class
                'classpath' => 'mod/mooduell/classes/external.php',
                'description' => 'This documentation will be displayed in the generated API documentation
                              (Administration > Plugins > Webservices > API documentation)',
                'type' => 'read', // The value is 'write' if your function does any database change, otherwise it is 'read'.
                'ajax' => true, // True/false if you allow this web service function to be callable via ajax
                'capabilities' => 'mod/mooduell:view',
                'services' => array(
                        MOODLE_OFFICIAL_MOBILE_SERVICE
                )
        ),
        'mod_mooduell_giveup_game' => array( // ... local_PLUGINNAME_FUNCTIONNAME is the name of the ...
            // ... web service function that the client will call.
                'classname' => 'mod_mooduell_external', // Create this class in componentdir/classes/external
                'methodname' => 'giveup_game', // Implement this function into the above class
                'classpath' => 'mod/mooduell/classes/external.php',
                'description' => 'This documentation will be displayed in the generated API documentation
                              (Administration > Plugins > Webservices > API documentation)',
                'type' => 'write', // The value is 'write' if your function does any database change, otherwise it is 'read'.
                'ajax' => true, // True/false if you allow this web service function to be callable via ajax
                'capabilities' => 'mod/mooduell:view',
                'services' => array(
                        MOODLE_OFFICIAL_MOBILE_SERVICE
                )
        ),
        'mod_mooduell_update_profile_picture' => array( // ... local_PLUGINNAME_FUNCTIONNAME is the name of the ...
            // ... web service function that the client will call.
                'classname' => 'mod_mooduell_external', // Create this class in componentdir/classes/external
                'methodname' => 'update_profile_picture', // Implement this function into the above class
                'classpath' => 'mod/mooduell/classes/external.php',
                'description' => 'This documentation will be displayed in the generated API documentation
                              (Administration > Plugins > Webservices > API documentation)',
                'type' => 'write', // The value is 'write' if your function does any database change, otherwise it is 'read'.
                'ajax' => true, // True/false if you allow this web service function to be callable via ajax
                'capabilities' => 'mod/mooduell:view',
                'services' => array(
                        MOODLE_OFFICIAL_MOBILE_SERVICE
                )
        ),
        'mod_mooduell_load_highscore_data' => array(
                'classname' => 'mod_mooduell_external',
                'methodname' => 'load_highscore_data',
                'classpath' => 'mod/mooduell/classes/external.php',
                'description' => 'Ajax load list of highscores',
                'type' => 'read',
                'capabilities' => 'mod/mooduell:view',
                'ajax' => true,
        ),
        'mod_mooduell_load_questions_data' => array(
                'classname' => 'mod_mooduell_external',
                'methodname' => 'load_questions_data',
                'classpath' => 'mod/mooduell/classes/external.php',
                'description' => 'Ajax load list of questions',
                'type' => 'read',
                'capabilities' => 'mod/mooduell:view',
                'ajax' => true,
        ),
        'mod_mooduell_load_opengames_data' => array(
                'classname' => 'mod_mooduell_external',
                'methodname' => 'load_opengames_data',
                'classpath' => 'mod/mooduell/classes/external.php',
                'description' => 'Ajax load list of opengames',
                'type' => 'read',
                'capabilities' => 'mod/mooduell:view',
                'ajax' => true,
        ),
        'mod_mooduell_load_finishedgames_data' => array(
                'classname' => 'mod_mooduell_external',
                'methodname' => 'load_finishedgames_data',
                'classpath' => 'mod/mooduell/classes/external.php',
                'description' => 'Ajax load list of finishedgames',
                'type' => 'read',
                'capabilities' => 'mod/mooduell:view',
                'ajax' => true,
        ),
);
