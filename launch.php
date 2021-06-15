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
 * This file contains all necessary code to view a lti activity instance
 *
 * @package quizaccess_examity
 * @copyright  2021
 * @author     Ant
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("../../../../config.php");
require_once($CFG->dirroot.'/mod/lti/lib.php');
require_once($CFG->dirroot.'/mod/lti/locallib.php');
use quizaccess_examity\helper;

$url_params = $_SERVER['QUERY_STRING'];
$exam_id = (int)substr($url_params, strpos($url_params, "=") + 1);  

$exam = $DB->get_record('quiz', ['id' => $exam_id], '*', MUST_EXIST);
$course = $DB->get_record('course', ['id' => $exam->course], '*', MUST_EXIST);
$course_module = $DB->get_record('course_modules', ['instance' => $exam->id], '*', MUST_EXIST);

$lti = helper::examity_sso($course->id, $course_module->id);
$context = context_module::instance($course_module->id);

require_login($course, true, $course_module);
require_capability('mod/lti:view', $context);
lti_launch_tool($lti);


