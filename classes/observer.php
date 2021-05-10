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
 * Auto-cohort local plugin for Moodle 3.5+
 * @package    quizaccess_examity
 * @copyright  2019 Catalyst IT
 * @author     Ant
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * User profile event observer class.
 *
 * Delegates profile and cohort processing to lib/local_cohortauto_handler().
 *
 * @copyright  2019 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quizaccess_examity_observer {


    public static function update(\core\event\base $event) {
        // This code works when put inside the corresponding 'store' function in blocks/recent_activity/classes/observer.php
        echo file_put_contents("/var/www/html/mod/quiz/accessrule/examity/classes/lidn.txt",$event->get_data());

    }
}
