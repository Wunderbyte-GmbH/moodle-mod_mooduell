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

    return true;
}
