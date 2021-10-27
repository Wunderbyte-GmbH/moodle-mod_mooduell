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
    if ($oldversion < 2021102205) {
        $table = new xmldb_table('mooduell');
        $field = new xmldb_field('completiongamesplayed', XMLDB_TYPE_INTEGER, '4', null,
        null, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('completiongameswon', XMLDB_TYPE_INTEGER, '4', null,
        null, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('completionrightanswers', XMLDB_TYPE_INTEGER, '4', null,
        null, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, 2021102205, 'mooduell');
    }

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

    return true;
}
