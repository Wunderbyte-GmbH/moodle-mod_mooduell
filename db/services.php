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
$functions = array(
        'mod_mooduell_quiz_start_attempt' => array( // ... local_PLUGINNAME_FUNCTIONNAME is the name of the web service function
            // ... that the client will call.
                'classname' => 'mod_mooduell_external', // Create this class in componentdir/classes/external .
                'methodname' => 'start_attempt', // Implement this function into the above class.
                'classpath' => 'mod/mooduell/classes/external.php',
                'description' => 'This documentation will be displayed in the generated API documentation
                (Administration > Plugins > Webservices > API documentation)',
                'type' => 'write', // The value is 'write' if your function does any database change, otherwise it is 'read'.
                'ajax' => true, // True/false if you allow this web service function to be callable via ajax.
                'capabilities' => 'mod/mooduell:view',
            // List the capabilities required by the function (those in a require_capability() call)...
            // ... (missing capabilities are displayed for authorised users and also for manually...
            // ... created tokens in the web interface, this is just informative).

                'services' => array(
                        MOODLE_OFFICIAL_MOBILE_SERVICE
                )
            // Optional, only available for Moodle 3.1 onwards. List of built-in services (by shortname) ...
            // ... where the function will be included. Services created manually via the Moodle interface are not supported.
        ),
        'mod_mooduell_get_quiz_data' => array( // ... local_PLUGINNAME_FUNCTIONNAME is the name of the web service function ...
            // ... that the client will call.
                'classname' => 'mod_mooduell_external', // Create this class in componentdir/classes/external
                'methodname' => 'get_quiz_data', // Implement this function into the above class
                'classpath' => 'mod/mooduell/classes/external.php',
                'description' => 'This documentation will be displayed in the generated API documentation
                                  (Administration > Plugins > Webservices > API documentation)',
                'type' => 'read', // the value is 'write' if your function does any database change, otherwise it is 'read'.
                'ajax' => true, // true/false if you allow this web service function to be callable via ajax
                'capabilities' => 'mod/mooduell:view',
            // List the capabilities required by the function (those in a require_capability() call) ...
            // ... (missing capabilities are displayed for authorised users and also for manually created tokens in the web interface,
            // ... this is just informative).
                'services' => array(
                        MOODLE_OFFICIAL_MOBILE_SERVICE
                )
            // Optional, only available for Moodle 3.1 onwards. List of built-in services (by shortname) ...
            // ... where the function will be included. Services created manually via the Moodle interface are not supported.
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
            // List the capabilities required by the function (those in a require_capability() call) ...
            // ... (missing capabilities are displayed for authorised users and also for manually created tokens in the web interface,
            // ... this is just informative).
                'services' => array(
                        MOODLE_OFFICIAL_MOBILE_SERVICE
                )
            // Optional, only available for Moodle 3.1 onwards. List of built-in services (by shortname) ...
            // ... where the function will be included. Services created manually via the Moodle interface are not supported.
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
            // List the capabilities required by the function (those in a require_capability() call) ...
            // ... (missing capabilities are displayed for authorised users ...
            // ... and also for manually created tokens in the web interface, this is just informative).
                'services' => array(
                        MOODLE_OFFICIAL_MOBILE_SERVICE
                )
            // Optional, only available for Moodle 3.1 onwards.
            // List of built-in services (by shortname) where the function will be included.
            // Services created manually via the Moodle interface are not supported.
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
            // List the capabilities required by the function (those in a require_capability() call) ...
            // ... (missing capabilities are displayed for authorised users ...
            // ... and also for manually created tokens in the web interface, this is just informative).
                'services' => array(
                        MOODLE_OFFICIAL_MOBILE_SERVICE
                )
            // Optional, only available for Moodle 3.1 onwards.
            // List of built-in services (by shortname) where the function will be included.
            // Services created manually via the Moodle interface are not supported.
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
            // List the capabilities required by the function (those in a require_capability() call) ...
            // ... (missing capabilities are displayed for authorised users ...
            // ... and also for manually created tokens in the web interface, this is just informative).
                'services' => array(
                        MOODLE_OFFICIAL_MOBILE_SERVICE
                )
            // Optional, only available for Moodle 3.1 onwards.
            // List of built-in services (by shortname) where the function will be included.
            // Services created manually via the Moodle interface are not supported.
        )
);
