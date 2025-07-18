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
 * Plugin upgrade steps are defined here.
 *
 * @package     mod_mooduell
 * @category    upgrade
 * @copyright   2020 David Bogner <david.bogner@wunderbyte.at>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/upgradelib.php');
require_once(__DIR__ . '/install.php');

/**
 * Execute mod_mooduell upgrade from the given old version.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_mooduell_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2021051000) {
        // Define table mooduell_highscores to be created.
        $table = new xmldb_table('mooduell_highscores');

        // Adding fields to table mooduell_highscores.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('mooduellid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('ranking', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('gamesplayed', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table->add_field('gameswon', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table->add_field('gameslost', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table->add_field('gamesstarted', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table->add_field('gamesfinished', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table->add_field('score', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table->add_field('qcorrect', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table->add_field('qplayed', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table mooduell_highscores.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('fk_mooduellid', XMLDB_KEY_FOREIGN, ['mooduellid'], 'mooduell', ['id']);
        $table->add_key('fk_userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);

        // Conditionally launch create table for mooduell_highscores.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        // Mooduell savepoint reached.
        upgrade_mod_savepoint(true, 2021051000, 'mooduell');
    }

    if ($oldversion < 2021051200) {
        // Define field qcpercentage to be added to mooduell_highscores.
        $table = new xmldb_table('mooduell_highscores');
        $field = new xmldb_field('qcpercentage', XMLDB_TYPE_NUMBER, '10, 2', null, null, null, '0', 'qplayed');

        // Conditionally launch add field qcpercentage.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        // Mooduell savepoint reached.
        upgrade_mod_savepoint(true, 2021051200, 'mooduell');
    }

    if ($oldversion < 2021101800) {
        // Define field showgeneralfeedback to be added to table mooduell.
        $table = new xmldb_table('mooduell');
        $field = new xmldb_field('showgeneralfeedback', XMLDB_TYPE_INTEGER, 1, null, true, null, '0', 'showcorrectanswer');

        // Conditionally launch add field showgeneralfeedback.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        // Mooduell savepoint reached.
        upgrade_mod_savepoint(true, 2021101800, 'mooduell');
    }

    if ($oldversion < 2021102100) {
        // Define field showanswersfeedback to be added to table mooduell.
        $table = new xmldb_table('mooduell');
        $field = new xmldb_field('showanswersfeedback', XMLDB_TYPE_INTEGER, 1, null, true, null, '0', 'showgeneralfeedback');

        // Conditionally launch add field showanswersfeedback.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        // Mooduell savepoint reached.
        upgrade_mod_savepoint(true, 2021102100, 'mooduell');
    }

    // Add fields for completion rules.
    if ($oldversion < 2021102205) {
        $table = new xmldb_table('mooduell');
        $field = new xmldb_field(
            'completiongamesplayed',
            XMLDB_TYPE_INTEGER,
            '4',
            null,
            null,
            null,
            '0'
        );
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field(
            'completiongameswon',
            XMLDB_TYPE_INTEGER,
            '4',
            null,
            null,
            null,
            '0'
        );
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field(
            'completionrightanswers',
            XMLDB_TYPE_INTEGER,
            '4',
            null,
            null,
            null,
            '0'
        );
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Mooduell savepoint reached.
        upgrade_mod_savepoint(true, 2021102205, 'mooduell');
    }

    // Add field challenges to mooduell_highscores table.
    if ($oldversion < 2021110800) {
        // Define field challenges to be added to mooduell_highscores.
        $table = new xmldb_table('mooduell_highscores');
        $field = new xmldb_field('challenges', XMLDB_TYPE_TEXT, null, null, null, null, null, 'qcpercentage');

        // Conditionally launch add field challenges.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Mooduell savepoint reached.
        upgrade_mod_savepoint(true, 2021110800, 'mooduell');
    }

    // Add new table mooduell_challenges.
    if ($oldversion < 2021111900) {
        // Define table mooduell_challenges to be created.
        $table = new xmldb_table('mooduell_challenges');

        // Adding fields to table mooduell_challenges.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('mooduellid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('challengetype', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, null);
        $table->add_field('targetnumber', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, null);
        $table->add_field('challengename', XMLDB_TYPE_CHAR, '255', null, null, null, null, null);

        // Adding keys to table mooduell_challenges.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('fk_mooduellid', XMLDB_KEY_FOREIGN, ['mooduellid'], 'mooduell', ['id']);

        // Conditionally launch create table for mooduell_challenges.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        // Mooduell savepoint reached.
        upgrade_mod_savepoint(true, 2021111900, 'mooduell');
    }

    if ($oldversion < 2021112200) {
        // Define field completiongamesplayed to be dropped from mooduell.
        $table = new xmldb_table('mooduell');

        $completiongamesplayed = new xmldb_field('completiongamesplayed');
        // Conditionally launch drop field completiongamesplayed.
        if ($dbman->field_exists($table, $completiongamesplayed)) {
            $dbman->drop_field($table, $completiongamesplayed);
        }

        $completiongameswon = new xmldb_field('completiongameswon');
        // Conditionally launch drop field completiongameswon.
        if ($dbman->field_exists($table, $completiongameswon)) {
            $dbman->drop_field($table, $completiongameswon);
        }

        $completionrightanswers = new xmldb_field('completionrightanswers');
        // Conditionally launch drop field completiongamesplayed.
        if ($dbman->field_exists($table, $completionrightanswers)) {
            $dbman->drop_field($table, $completionrightanswers);
        }

        // Mooduell savepoint reached.
        upgrade_mod_savepoint(true, 2021112200, 'mooduell');
    }

    if ($oldversion < 2021113000) {
        // Fix mooduell_alias bugs (string contained a binary bit).
        if ($record = $DB->get_record('user_info_field', ['shortname' => 'mooduell_alias'])) {
            $record->defaultdata = '';
            $record->param1 = 0;
            $DB->update_record('user_info_field', $record);
        }

        // Mooduell savepoint reached.
        upgrade_mod_savepoint(true, 2021113000, 'mooduell');
    }

    // Add new table mooduell_challenge_results.
    if ($oldversion < 2021113001) {
        // Define table to be created.
        $table = new xmldb_table('mooduell_challenge_results');

        // Adding fields to table mooduell_challenge_results.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('mooduellid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('challengeid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, null);
        $table->add_field('result', XMLDB_TYPE_INTEGER, '10', null, null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table mooduell_challenge_results.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('fk_mooduellid', XMLDB_KEY_FOREIGN, ['mooduellid'], 'mooduell', ['id']);
        $table->add_key('fk_challengeid', XMLDB_KEY_FOREIGN, ['challengeid'], 'mooduell_challenges', ['id']);
        $table->add_key('fk_userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);

        // Conditionally launch create table for mooduell_challenge_results.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        // Mooduell savepoint reached.
        upgrade_mod_savepoint(true, 2021113001, 'mooduell');
    }

    if ($oldversion < 2022033101) {
          // Define table mooduell_purchase to be created.
          $table = new xmldb_table('mooduell_purchase');

          // Adding fields to table mooduell_purchase.
          $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
          $table->add_field('productid', XMLDB_TYPE_CHAR, '32', null, XMLDB_NOTNULL, null, null);
          $table->add_field('purchasetoken', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
          $table->add_field('receipt', XMLDB_TYPE_TEXT, null, null, null, null);
          $table->add_field('signature', XMLDB_TYPE_TEXT, null, null, null, null);
          $table->add_field('orderid', XMLDB_TYPE_TEXT, null, null, null, null);
          $table->add_field('free', XMLDB_TYPE_INTEGER, '1', null, null, null, null);
          $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
          $table->add_field('mooduellid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
          $table->add_field('platformid', XMLDB_TYPE_CHAR, '30', null, null, null, null);
          $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
          $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
          $table->add_field('store', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, null);
          $table->add_field('ispublic', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
          // Adding keys to table mooduell_purchase.
          $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
          $table->add_key('fk_userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
          $table->add_key('fk_courseid', XMLDB_KEY_FOREIGN, ['courseid'], 'course', ['id']);
          $table->add_key('fk_mooduellid', XMLDB_KEY_FOREIGN, ['mooduellid'], 'mooduell', ['id']);
          // Conditionally launch create table for mooduell_purchase.
        if (!$dbman->table_exists($table)) {
              $dbman->create_table($table);
        }
        // Mooduell savepoint reached.
        upgrade_mod_savepoint(true, 2022033101, 'mooduell');
    }

    if ($oldversion < 2024030600) {
        // Define field validuntil to be added to mooduell_purchase.
        $table = new xmldb_table('mooduell_purchase');
        $field = new xmldb_field('validuntil', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'ispublic');

        // Conditionally launch add field validuntil.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Mooduell savepoint reached.
        upgrade_mod_savepoint(true, 2024030600, 'mooduell');
    }

    return true;
}
