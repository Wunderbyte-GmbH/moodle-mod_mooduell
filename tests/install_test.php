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
 * Tests for the plugin install hook.
 *
 * @package    mod_mooduell
 * @category   test
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mooduell;

use advanced_testcase;

/**
 * Test class for the mod_mooduell install hook.
 *
 * @package    mod_mooduell
 * @category   test
 * @copyright  2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     ::xmldb_mooduell_install
 */
final class install_test extends advanced_testcase {
    /**
     * Tests set up.
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        global $CFG;
        require_once($CFG->dirroot . '/mod/mooduell/db/install.php');
    }

    /**
     * The install hook creates the mooduell_alias profile field with the expected definition.
     */
    public function test_install_creates_mooduell_alias_profile_field(): void {
        global $DB;

        // Start from a clean slate: drop the field/category created during the test-site install.
        $DB->delete_records('user_info_field', ['shortname' => 'mooduell_alias']);
        $DB->delete_records('user_info_category', ['name' => 'Mooduell']);
        $this->assertFalse($DB->record_exists('user_info_field', ['shortname' => 'mooduell_alias']));

        // Run the install hook.
        $this->assertTrue(xmldb_mooduell_install());

        // The field now exists with the expected definition.
        $field = $DB->get_record('user_info_field', ['shortname' => 'mooduell_alias'], '*', MUST_EXIST);
        $this->assertEquals('MooDuell Alias', $field->name);
        $this->assertEquals('text', $field->datatype);
        $this->assertEquals(0, (int) $field->visible);
        $this->assertEquals(30, (int) $field->param1);
        $this->assertEquals(2048, (int) $field->param2);

        // ... and it lives in the "Mooduell" category.
        $category = $DB->get_record('user_info_category', ['id' => $field->categoryid], '*', MUST_EXIST);
        $this->assertEquals('Mooduell', $category->name);
    }

    /**
     * Running the install hook again must not create a duplicate field.
     */
    public function test_install_is_idempotent(): void {
        global $DB;

        // The field already exists from the test-site install; running again must be a no-op.
        $this->assertTrue(xmldb_mooduell_install());
        $this->assertEquals(1, $DB->count_records('user_info_field', ['shortname' => 'mooduell_alias']));
    }
}
