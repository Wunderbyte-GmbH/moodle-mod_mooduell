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
 * Privacy provider implementation for mod_mooduell.
 *
 * @package mod_mooduell
 * @copyright 2021 Michael Pollak <moodle@michaelpollak.org>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mooduell\privacy;
defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\deletion_criteria;
use core_privacy\local\request\helper;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * The privacy provider class of mod_mooduell.
 * @package mod_mooduell
 */
class provider implements
        // This plugin stores personal data.
        \core_privacy\local\metadata\provider,

        // This plugin is a core_user_data_provider.
        \core_privacy\local\request\plugin\provider,

        // This plugin is capable of determining which users have data within it.
        \core_privacy\local\request\core_userlist_provider {
    /**
     * Return the fields which contain personal data.
     * @param collection $collection a reference to the collection to use to store the metadata.
     * @return collection the updated collection of metadata items.
     */
    public static function get_metadata(collection $collection) : collection {
        // Stores the mooduell game progress.
        $collection->add_database_table(
            'mooduell_games',
            [
                'mooduellid' => 'privacy:metadata:mooduell_games:mooduellid',
                'playeraid' => 'privacy:metadata:mooduell_games:playeraid',
                'playerbid' => 'privacy:metadata:mooduell_games:playerbid',
                'playeracorrect' => 'privacy:metadata:mooduell_games:playeracorrect',
                'playerbcorrect' => 'privacy:metadata:mooduell_games:playerbcorrect',
                'playeraqplayed' => 'privacy:metadata:mooduell_games:playeraqplayed',
                'playerbqplayed' => 'privacy:metadata:mooduell_games:playerbqplayed',
                'winnerid' => 'privacy:metadata:mooduell_games:winnerid',
                'status' => 'privacy:metadata:mooduell_games:status',
                'victorycoefficient' => 'privacy:metadata:mooduell_games:victorycoefficient',
                'timemodified' => 'privacy:metadata:mooduell_games:timemodified',
                'timecreated' => 'privacy:metadata:mooduell_games:timecreated',
            ],
            'privacy:metadata:mooduell_games'
        );
        // Stores the mooduell highscore table.
        $collection->add_database_table(
            'mooduell_highscores',
            [
                'mooduellid' => 'privacy:metadata:mooduell_highscores:mooduellid',
                'userid' => 'privacy:metadata:mooduell_highscores:userid',
                'ranking' => 'privacy:metadata:mooduell_highscores:ranking',
                'gamesplayed' => 'privacy:metadata:mooduell_highscores:gamesplayed',
                'gameswon' => 'privacy:metadata:mooduell_highscores:gameswon',
                'gameslost' => 'privacy:metadata:mooduell_highscores:gameslost',
                'gamesstarted' => 'privacy:metadata:mooduell_highscores:gamesstarted',
                'gamesfinished' => 'privacy:metadata:mooduell_highscores:gamesfinished',
                'score' => 'privacy:metadata:mooduell_highscores:score',
                'qcorrect' => 'privacy:metadata:mooduell_highscores:qcorrect',
                'qplayed' => 'privacy:metadata:mooduell_highscores:qplayed',
                'qcpercentage' => 'privacy:metadata:mooduell_highscores:qcpercentage',
                'timemodified' => 'privacy:metadata:mooduell_highscores:timemodified',
                'timecreated' => 'privacy:metadata:mooduell_highscores:timecreated',
            ],
            'privacy:metadata:mooduell_highscores'
        );

        // Stores the mooduell pushtokens.
        $collection->add_database_table(
            'mooduell_pushtokens',
            [
                'userid' => 'privacy:metadata:mooduell_pushtokens:userid',
                'identifier' => 'privacy:metadata:mooduell_pushtokens:identifier',
                'model' => 'privacy:metadata:mooduell_pushtokens:model',
                'pushtoken' => 'privacy:metadata:mooduell_pushtokens:pushtoken',
                'numberofnotifications' => 'privacy:metadata:mooduell_pushtokens:numberofnotifications',
            ],
            'privacy:metadata:mooduell_pushtokens'
        );

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid the userid.
     * @return contextlist the list of contexts containing user info for the user.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {

        $contextlist = new contextlist();

        // Look up all mooduell games of a specific user.
        $sql = "SELECT c.id
                  FROM {context} c
                  INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                  INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  INNER JOIN {mooduell} md ON md.id = cm.instance
                  INNER JOIN {mooduell_games} mdg ON mdg.mooduellid = md.id
                  WHERE mdg.playeraid = :userida OR mdg.playerbid = :useridb";

        $params = [
            'modname'      => 'mooduell',
            'contextlevel' => CONTEXT_MODULE,
            'userida'      => $userid,
            'useridb'      => $userid,
        ];
        $contextlist->add_from_sql($sql, $params);

        // Look up all mooduell highscores of a specific user.
        $sql = "SELECT c.id
                  FROM {context} c
                  INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                  INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  INNER JOIN {mooduell} md ON md.id = cm.instance
                  INNER JOIN {mooduell_highscores} mdh ON mdh.mooduellid = md.id
                  WHERE mdh.userid = :userid";

        $params = [
            'modname'       => 'mooduell',
            'contextlevel'  => CONTEXT_MODULE,
            'userid'        => $userid,
        ];
        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Export personal data for the given approved_contextlist. User and context information is contained within the contextlist.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for export.
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;
        if (!count($contextlist)) {
            return;
        }

        $user = $contextlist->get_user();

        // Export general information like introtext.
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel != CONTEXT_MODULE) {
                continue;
            }
            $data = helper::get_context_data($context, $user);
            writer::with_context($context)->export_data([], $data);
            helper::export_context_files($context, $user);
        }

        // Export all duells the user has participated in.
        static::export_all_mooduells($contextlist);

        // Export all highscores.
        static::export_all_highscores($contextlist);

        // Export all pushtokens.
        static::export_all_pushtokens($contextlist);

        // Export all associated entries in mooduell_questions.
        static::export_all_questiondata($contextlist);
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context the context to delete in.
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function delete_data_for_all_users_in_context(\context $context) {

        global $DB;

        if (!$context instanceof \context_module) {
            return;
        }

        if ($cm = get_coursemodule_from_id('mooduell', $context->instanceid)) {
            $DB->delete_records('mooduell_games', ['mooduellid' => $cm->instance]);
            // The mooduell_questions table is linked to games, we delete it therefore.
            $DB->delete_records('mooduell_questions', ['mooduellid' => $cm->instance]);
            $DB->delete_records('mooduell_highscores', ['mooduellid' => $cm->instance]);

            // To delete the pushtokens, we must first find out all users (game players).
            $getallplayerssql = 'SELECT DISTINCT userid
            FROM (
                SELECT playeraid AS userid
                FROM {mooduell_games}
                WHERE mooduellid = :cminstance
                UNION
                SELECT playerbid AS userid
                FROM {mooduell_games}
                WHERE mooduellid = :cminstance
            ) s
            ORDER BY userid ASC
            ';

            $records = $DB->get_records_sql($getallplayerssql, ['cminstance' => $cm->instance]);
            foreach ($records as $record) {
                $DB->delete_records('mooduell_pushtokens', ['userid' => $record->userid]);
            }
        }
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for deletion.
     * @throws \dml_exception
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {

            if (!$context instanceof \context_module) {
                continue;
            }

            $instanceid = $DB->get_field('course_modules', 'instance', ['id' => $context->instanceid]);
            if (!$instanceid) {
                continue;
            }

            // Before deleting mooduell_games we have to delete the associated mooduell_questions data.
            $where = 'gameid in (SELECT id FROM {mooduell_games} WHERE mooduellid = :mooduellid AND ' .
                '(playeraid = :playeraid OR playerbid = :playerbid))';
            $DB->delete_records_select('mooduell_questions', $where, ['mooduellid' => $instanceid,
                'playeraid' => $userid, 'playerbid' => $userid]);

            // Now we can delete the rest.
            $DB->delete_records('mooduell_games', ['mooduellid' => $instanceid, 'playeraid' => $userid]);
            $DB->delete_records('mooduell_games', ['mooduellid' => $instanceid, 'playerbid' => $userid]);
            $DB->delete_records('mooduell_highscores', ['mooduellid' => $instanceid, 'userid' => $userid]);
            $DB->delete_records('mooduell_pushtokens', ['userid' => $userid]);
        }
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param   userlist    $userlist   The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {

        $context = $userlist->get_context();

        if (!$context instanceof \context_module) {
            return;
        }

        // Add all games where user is playera.
        $sql = "SELECT mdg.playeraid
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {mooduell} md ON md.id = cm.instance
                  JOIN {mooduell_games} mdg ON mdg.mooduellid = md.id
                  WHERE cm.id = :cmid";

        $params = [
            'cmid'      => $context->instanceid,
            'modname'   => 'mooduell',
        ];

        $userlist->add_from_sql('userid', $sql, $params);

        // Add all games where user is playerb.
        $sql = "SELECT mdg.playerbid
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {mooduell} md ON md.id = cm.instance
                  JOIN {mooduell_games} mdg ON mdg.mooduellid = md.id
                  WHERE cm.id = :cmid";

        $params = [
            'cmid'      => $context->instanceid,
            'modname'   => 'mooduell',
        ];

        $userlist->add_from_sql('userid', $sql, $params);
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function delete_data_for_users(approved_userlist $userlist) {

        global $DB;

        $context = $userlist->get_context();

        if (!$context instanceof \context_module) {
            return;
        }

        $cm = get_coursemodule_from_id('mooduell', $context->instanceid);

        if (!$cm) {
            return;
        }

        $userids = $userlist->get_userids();
        list($usersql, $userparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);

        $select = "mooduellid = :mooduellid AND playeraid $usersql";
        $params = ['mooduellid' => $cm->instance] + $userparams;
        $DB->delete_records_select('mooduell_games', $select, $params);

        $select = "mooduellid = :mooduellid AND playerbid $usersql";
        $params = ['mooduellid' => $cm->instance] + $userparams;
        $DB->delete_records_select('mooduell_games', $select, $params);
    }

    /**
     * Export all duells that the user participated in.
     *
     * @param approved_contextlist $contextlist List of contexts approved for export.
     * @throws \coding_exception
     * @throws \dml_exception
     */
    protected static function export_all_mooduells(approved_contextlist $contextlist) {
        global $DB;
        $user = $contextlist->get_user();

        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);

        $sql = "SELECT *, cm.id AS cmid
            FROM {course_modules} cm
            JOIN {modules} m ON cm.module = m.id AND m.name = :modname
            JOIN {context} c ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
            JOIN {mooduell} md ON cm.instance = md.id
            JOIN {mooduell_games} mdg ON mdg.mooduellid = md.id AND mdg.playeraid = :userida OR  mdg.playerbid = :useridb
            WHERE c.id {$contextsql}";

        $params = [
            'modname'       => 'mooduell',
            'contextlevel'  => CONTEXT_MODULE,
            'userida'       => $user->id,
            'useridb'       => $user->id,
        ];
        $params += $contextparams;

        $rs = $DB->get_recordset_sql($sql, $params);
        foreach ($rs as $record) {
            \context_helper::preload_from_record($record);
            $context = \context_module::instance($record->cmid);
            $writer = \core_privacy\local\request\writer::with_context($context);
            $subcontext = ['MooDuell games played', $record->id];

            $data = (object) [
                'playeraid' => $record->playeraid,
                'playerbid' => $record->playerbid,
                'timecreated' => \core_privacy\local\request\transform::datetime($record->timecreated),
                'timemodified' => \core_privacy\local\request\transform::datetime($record->timemodified),
                'playeracorrect' => $record->playeracorrect,
                'playerbcorrect' => $record->playerbcorrect,
                'playeraqplayed' => $record->playeraqplayed,
                'playerbqplayed' => $record->playerbqplayed,
                'playeraresults' => $record->playeraresults,
                'playerbresults' => $record->playerbresults,
                'winnerid' => $record->winnerid,
                'status' => $record->status,
            ];

            $writer->export_data($subcontext, $data);
        }

        $rs->close();
    }

    /**
     * Export the highscores this user is mentioned in.
     *
     * @param approved_contextlist $contextlist List of contexts approved for export.
     * @throws \coding_exception
     * @throws \dml_exception
     */
    protected static function export_all_highscores(approved_contextlist $contextlist) {
        global $DB;
        $user = $contextlist->get_user();

        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);

        $sql = "SELECT *, cm.id AS cmid
            FROM {course_modules} cm
            JOIN {modules} m ON cm.module = m.id AND m.name = :modname
            JOIN {context} c ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
            JOIN {mooduell} md ON cm.instance = md.id
            JOIN {mooduell_highscores} mdh ON mdh.mooduellid = md.id AND mdh.userid = :userid
            WHERE c.id {$contextsql}";

        $params = [
            'modname'       => 'mooduell',
            'contextlevel'  => CONTEXT_MODULE,
            'userid'        => $user->id,
        ];
        $params += $contextparams;

        $rs = $DB->get_recordset_sql($sql, $params);
        foreach ($rs as $record) {
            \context_helper::preload_from_record($record);
            $context = \context_module::instance($record->cmid);
            $writer = \core_privacy\local\request\writer::with_context($context);
            $subcontext = ['MooDuell highscore entries', $record->id];

            $data = (object) [
                'ranking' => $record->ranking,
                'gamesplayed' => $record->gamesplayed,
                'gameswon' => $record->gameswon,
                'gameslost' => $record->gameslost,
            ];
            $writer->export_data($subcontext, $data);
        }

        $rs->close();
    }

    /**
     * Export all pushtokens this user requested.
     *
     * @param approved_contextlist $contextlist List of contexts approved for export.
     * @throws \coding_exception
     * @throws \dml_exception
     */
    protected static function export_all_pushtokens(approved_contextlist $contextlist) {
        global $DB;
        $user = $contextlist->get_user();

        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);

        $sql = "SELECT *, cm.id AS cmid
            FROM {course_modules} cm
            JOIN {modules} m ON cm.module = m.id AND m.name = :modname
            JOIN {context} c ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
            JOIN {mooduell} md ON cm.instance = md.id
            JOIN {mooduell_pushtokens} mdp ON mdp.userid = :userid
            WHERE c.id {$contextsql}";

        $params = [
            'modname'       => 'mooduell',
            'contextlevel'  => CONTEXT_MODULE,
            'userid'        => $user->id,
        ];
        $params += $contextparams;

        $rs = $DB->get_recordset_sql($sql, $params);
        foreach ($rs as $record) {
            \context_helper::preload_from_record($record);
            $context = \context_module::instance($record->cmid);
            $writer = \core_privacy\local\request\writer::with_context($context);
            $subcontext = ['MooDuell pushtokens', $record->id];

            $data = (object) [
                'userid' => $record->userid,
                'identifier' => $record->identifier,
                'model' => $record->model,
                'pushtoken' => $record->pushtoken,
                'numberofnotifications' => $record->numberofnotifications
            ];
            $writer->export_data($subcontext, $data);
        }
        $rs->close();
    }

    /**
     * Export all entries in mooduell_questions the user is associated with.
     *
     * @param approved_contextlist $contextlist List of contexts approved for export.
     * @throws \coding_exception
     * @throws \dml_exception
     */
    protected static function export_all_questiondata(approved_contextlist $contextlist) {
        global $DB;
        $user = $contextlist->get_user();

        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);

        $sql = "SELECT *, cm.id AS cmid
            FROM {course_modules} cm
            JOIN {modules} m ON cm.module = m.id AND m.name = :modname
            JOIN {context} c ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
            JOIN {mooduell} md ON cm.instance = md.id
            JOIN (SELECT mq.*, mg.playeraid, mg.playerbid
                    FROM {mooduell_questions} mq
                    JOIN {mooduell_games} mg
                    ON mq.mooduellid = mg.mooduellid AND mq.gameid = mg.id) mdq
                ON mdq.mooduellid = md.id AND (mdq.playeraid = :playeraid OR mdq.playerbid = :playerbid)
            WHERE c.id {$contextsql}";

        $params = [
            'modname' => 'mooduell',
            'contextlevel' => CONTEXT_MODULE,
            'playeraid' => $user->id,
            'playerbid' => $user->id
        ];
        $params += $contextparams;

        $rs = $DB->get_recordset_sql($sql, $params);
        foreach ($rs as $record) {
            \context_helper::preload_from_record($record);
            $context = \context_module::instance($record->cmid);
            $writer = \core_privacy\local\request\writer::with_context($context);
            $subcontext = ['MooDuell question entries', $record->id];

            $data = (object) [
                'mooduellid' => $record->mooduellid,
                'gameid' => $record->gameid,
                'questionid' => $record->questionid,
                'playeraanswered' => $record->playeraanswered,
                'playerbanswered' => $record->playerbanswered
            ];
            $writer->export_data($subcontext, $data);
        }

        $rs->close();
    }
}
