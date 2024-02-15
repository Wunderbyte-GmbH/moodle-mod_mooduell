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
 * @package    mod_mooduell
 * @copyright  2024 Wunderbyte GmbH
 * @author     Chrsitian Badusch
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

use mod_mooduell\mooduell;


$mooduellid = required_param('mooduellid', PARAM_RAW);
$context = \context_system::instance();
$PAGE->set_context($context);
require_login();

$PAGE->set_pagelayout('standard');
$title = "MooDuell Testpage";
$PAGE->set_title($title);
$PAGE->set_url('/test.php');
$PAGE->set_heading($title);

echo $OUTPUT->header();
     // Create mooduell instance.
$mooduell = new mooduell($mooduellid);

$mooduell->update_all_subscriptions();

echo $OUTPUT->footer();
