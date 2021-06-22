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
 * Examity / Moodle integration
 * @package    quizaccess_examity
 * @copyright  2021 Catalyst IT
 * @author     Ant
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

use quizaccess_examity\helper;

/**
 * Class quizaccess_examity_observer
 *
 * @copyright  2021 Catalyst IT
 * @author     Ant
 */
class quizaccess_examity_observer {
    /**
     * Update observer.
     *
     * @param \core\event\base $event
     * @return void|null
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function update(\core\event\base $event) {
        global $DB, $COURSE, $USER;

        // Get site level config and check if enabled.
        $config = helper::get_config();
        if (empty($config)) {
            return;
        }

        $examityquizenabled = optional_param('examity_enabled', 0, PARAM_INT);

        if (!empty($examityquizenabled)) {
            $moodleuserid = $USER->id;
            $moodlecourseid = (int)$event->get_data()['courseid'] ?? null;
            $moodleexamid = (int)$event->get_data()['other']['instanceid'] ?? null;

            // Check whether the user, course or exam is already existing in the db.
            $examityuserid        = $DB->get_field('quizaccess_examity_u', 'examity_user_id', ['userid' => $moodleuserid]);
            $examitycourseid      = $DB->get_field('quizaccess_examity_c', 'examity_course_id', ['course' => $moodlecourseid]);
            $examityexamid        = $DB->get_field('quizaccess_examity_e', 'examity_exam_id', ['quiz' => $moodleexamid]);

            // Connect to examity auth.
            $examitytoken = helper::get_examity_token($config->examity_url, $config->client_id, $config->consumer_username, $config->consumer_password, $moodlecourseid);
            if (empty($examitytoken)) {
                $message = get_string('error_auth', 'quizaccess_examity');
                \core\notification::add($message, 'error');
                return null;
            }

            // First create user if we have not created one before.
            if (!$examityuserid) {
                $examityuserid = helper::create_examity_user($config->examity_url, $USER, $examitytoken);
            }
            if (!empty($examityuserid)) {
                // Only do this stuff if we have a user to work with.

                if (!$examitycourseid) {
                    // Create a course in examity if we have not created one before.
                    helper::create_examity_course($config->examity_url, $examityuserid, $COURSE, $examitytoken);
                } else {
                    helper::update_examity_course($config->examity_url, $examityuserid, $examitycourseid, $COURSE, $examitytoken);
                }

                if (!$examityexamid) {
                    helper::create_examity_exam($config->examity_url, $moodleuserid, $examitycourseid, $moodleexamid, $examitytoken);
                } else {
                    helper::update_examity_exam($config->examity_url, $moodleuserid, $moodlecourseid, $moodleexamid, $examityexamid, $examitytoken);
                }
            }
        }
    }

    /**
     * Handle when a quiz is deleted.
     *
     * @param \core\event\base $event
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function delete(\core\event\base $event) {
        global $DB;

        // Get site level config and check if enabled.
        $config = helper::get_config();
        if (empty($config)) {
            return;
        }

        // Check to make sure this is actually a quiz activity, and not another module type.
        $cmid = $event['contextinstanceid'];
        $coursemodule = get_coursemodule_from_id('quiz', $cmid);
        if (empty($coursemodule)) {
            return;
        }

        $examityexamid        = $DB->get_field('quizaccess_examity_e', 'examity_exam_id', ['quiz' => $coursemodule->instance]);
        if (empty($examityexamid)) {
            return;
        }

        $examitytoken = helper::get_examity_token($config->examity_url, $config->client_id, $config->consumer_username, $config->consumer_password);
        // delete course
        //
        // $exams = helper::select('examity_course_exam', 'examity_course_id', $examitycourseid);
        // foreach($exams as $exam) {
        //     // Delete exams associated to a couse from examity
        //     $delete_examity_exam = helper::delete_examity_exam($config->examity_url, $exam->examity_exam_id, $examitytoken);
        //     // Delete exams associated to a couse from moodle custom database
        //     $delete_examity_exam = helper::delete('examity_exam', 'examity_exam_id', $exam->examity_exam_id);
        //     $delete_examity_user_exam = helper::delete('examity_user_exam', 'examity_exam_id', $exam->examity_exam_id);
        //     $delete_examity_course_exam = helper::delete('examity_course_exam', 'examity_exam_id', $exam->examity_exam_id);
        // }
        // // Delete course record from examity after deleting the exams
        // $delete_examity_course = helper::delete_examity_course($config->examity_url, $examitycourseid, $examitytoken);
        // // Delete course record from moodle custom database
        // $delete_examity_course = helper::delete('examity_course', 'examity_course_id', $examitycourseid);
        // $delete_examity_user_course = helper::delete('examity_user_course', 'examity_course_id', $examitycourseid);
        // $delete_examity_course_exam = helper::delete('examity_course_exam', 'examity_course_id', $examitycourseid);
        // // Delete course record from examity after deleting the exams
        // $delete_examity_course = helper::delete_examity_course($config->examity_url, $examitycourseid, $examitytoken);
        // $delete_examity_exam = helper::delete_examity_exam($config->examity_url, $examityexamid, $examitytoken);

        if ($examityexamid) {
            $examityexam = helper::get_examity_exam($config->url, $examityexamid, $examitytoken);
            if (isset($examityexam['exam_id'])) {

                $examityexamid = $examityexam['exam_id'];
                helper::delete_examity_exam($config->url, $examityexamid, $examitytoken);
                $DB->delete_records('quizaccess_examity_e', ['examity_exam_id' => $examityexamid]);
                $message = get_string('success_delete_exam', 'quizaccess_examity');
                $messagetype = 'success';
                \core\notification::add($message, $messagetype);
            } else {
                $message = get_string('error_delete_exam', 'quizaccess_examity');
                $messagetype = 'error';
                \core\notification::add($message, $messagetype);
            }
        }
    }
}