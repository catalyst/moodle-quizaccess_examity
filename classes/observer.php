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

        $examityquizenabled = optional_param('examity_enabled', 0, PARAM_INT);
        $examityenabled = get_config('quizaccess_examity', 'examity_manage');

        if (!empty($examityquizenabled) && !empty($examityenabled)) {
            $moodleuserid = (int)$event->get_data()['userid'] ?? null;
            $moodlecourseid = (int)$event->get_data()['courseid'] ?? null;
            $moodleexamid = (int)$event->get_data()['other']['instanceid'] ?? null;

            $consumerusername      = get_config('quizaccess_examity', 'consumer_username');
            $consumerpassword      = get_config('quizaccess_examity', 'consumer_password');
            $clientid              = get_config('quizaccess_examity', 'client_id');
            $url                   = get_config('quizaccess_examity', 'examity_url');

            // Check whether the user, course or exam is already existing in the db.
            $examityuserid        = $DB->get_record('quizaccess_examity_u', ['userid' => $moodleuserid]);

            if (isset($examityuserid->examity_user_id)) {
                $examityuserid = (int)$examityuserid->examity_user_id;
            }
            $examitycourseid      = $DB->get_record('quizaccess_examity_c', ['course' => $moodlecourseid]);
            if (isset($examitycourseid->examity_course_id)) {
                $examitycourseid = (int)$examitycourseid->examity_course_id;
            }
            $examityexamid        = $DB->get_record('quizaccess_examity_e', ['quiz' => $moodleexamid]);
            if (isset($examityexamid->examity_exam_id)) {
                $examityexamid = (int)$examityexamid->examity_exam_id;
            }

            // Connect to examity auth.
            $examitytoken = helper::get_examity_token($url, $clientid, $consumerusername, $consumerpassword, $moodlecourseid);

            if (isset($examitytoken["access_token"])) {
                $headers['Authorization'] = ' Bearer '. $examitytoken["access_token"];

                switch ($event->eventname) {
                    case '\core\event\course_module_created':
                        if (!$examityuserid) {
                            $examityuser = helper::create_examity_user($url, $USER, $headers);
                            if (isset($examityuser['user_id'])) {
                                $examityuserid = $examityuser['user_id'];

                                $data = [
                                    'id' => null,
                                    'userid' => $moodleuserid,
                                    'examity_user_id' => $examityuserid
                                ];

                                $insert = $DB->insert_record('quizaccess_examity_u', $data);

                                if ($insert == false) {
                                    $message = get_string('error_create_exam', 'quizaccess_examity');
                                    $messagetype = 'error';
                                    \core\notification::add($message, $messagetype);
                                    return null;
                                }

                            }
                        }

                        // Ask examity to get a course based on moodle_course else create one.
                        if (!$examitycourseid) {
                            $examitycourse = helper::create_examity_course($url, $examityuserid, $COURSE, $headers);
                            if (isset($examitycourse['course_id'])) {
                                $examitycourseid = $examitycourse['course_id'];

                                $data = [
                                    'id' => null,
                                    'course' => $moodlecourseid,
                                    'examity_course_id' => $examitycourseid
                                ];

                                $insert = $DB->insert_record('quizaccess_examity_c', $data);

                                if ($insert == false) {
                                    $message = get_string('error_create_exam', 'quizaccess_examity');
                                    $messagetype = 'error';
                                    \core\notification::add($message, $messagetype);
                                    return null;
                                }
                            }
                        }

                        // Ask examity to get a exam based on moodle_exam else create one.
                        if (!$examityexamid) {
                            $examityexam = helper::create_examity_exam($url, $moodleuserid, $examitycourseid, $moodleexamid, $headers);
                            if (isset($examityexam['exam_id'])) {
                                $examityexamid = $examityexam['exam_id'];

                                $data = [
                                    'id' => null,
                                    'quiz' => $moodleexamid,
                                    'examity_exam_id' => $examityexamid
                                ];

                                $insert = $DB->insert_record('quizaccess_examity_e', $data);

                                if ($insert == false) {
                                    $message = get_string('error_create_exam', 'quizaccess_examity');
                                    $messagetype = 'error';
                                    \core\notification::add($message, $messagetype);
                                    return null;
                                }

                            }
                        }

                        // Update custom database examity tables in moodle.
                        if ($examityuserid && $examitycourseid && $examityexamid) {

                            $data = [
                                'id' => null,
                                'userid' => $moodleuserid,
                                'course' => $moodlecourseid,
                                'examity_user_id' => $examityuserid,
                                'examity_course_id' => $examitycourseid,
                            ];

                            $insert = $DB->insert_record('quizaccess_examity_u_c', $data);

                            $data = [
                                'id' => null,
                                'userid' => $moodleuserid,
                                'quiz' => $moodleexamid,
                                'examity_user_id' => $examityuserid,
                                'examity_exam_id' => $examityexamid,
                            ];

                            $insert = $DB->insert_record('quizaccess_examity_u_e', $data);

                            // $examityuser   = helper::get_examity_user($url, $examityuserid, $headers);
                            // $examitycourse = helper::get_examity_course($url, $examitycourseid, $headers);
                            // $examityexam   = helper::get_examity_exam($url, $examityexamid, $headers);

                            $data = [
                                'id' => null,
                                'course' => $moodlecourseid,
                                'quiz' => $moodleexamid,
                                'examity_course_id' => $examitycourseid,
                                'examity_exam_id' => $examityexamid,
                            ];

                            $insert = $DB->insert_record('quizaccess_examity_c_e', $data);

                            $message = get_string('success_create_exam', 'quizaccess_examity');
                            $messagetype = 'success';
                            \core\notification::add($message, $messagetype);
                        }

                        break;
                    case '\core\event\course_module_updated': // Triggers when course is updated.
                        // Ask examity to get a course infered from the moodle_course_id
                        // if examity finds a course, it updates it's $COURSE data inside examity.
                        if ($examitycourseid) {
                            $examitycourseid = helper::update_examity_course($url, $examityuserid, $examitycourseid, $COURSE, $headers);
                            $message = get_string('success_update_course', 'quizaccess_examity');
                            $messagetype = 'success';
                            \core\notification::add($message, $messagetype);
                        } else {
                            $message = get_string('error_update_course', 'quizaccess_examity');
                            $messagetype = 'error';
                            \core\notification::add($message, $messagetype);
                        }

                            // Ask examity to get an exam infered from the moodle_exam_id
                            // if examity finds a course, it updates it's $COURSE data inside examity.
                        if ($examityexamid) {
                            $examityexamid = helper::update_examity_exam($url, $moodleuserid, $moodlecourseid, $moodleexamid, $examityexamid, $headers);
                            $message = get_string('success_update_exam', 'quizaccess_examity');
                            $messagetype = 'success';
                            \core\notification::add($message, $messagetype);
                        } else {
                            $message = get_string('error_update_exam', 'quizaccess_examity');
                            $messagetype = 'error';
                            \core\notification::add($message, $messagetype);
                        }
                        break;
                    case '\core\event\course_module_deleted':
                        // delete course
                        //
                        // $exams = helper::select('examity_course_exam', 'examity_course_id', $examitycourseid);
                        // foreach($exams as $exam) {
                        //     // Delete exams associated to a couse from examity
                        //     $delete_examity_exam = helper::delete_examity_exam($url, $exam->examity_exam_id, $headers);
                        //     // Delete exams associated to a couse from moodle custom database
                        //     $delete_examity_exam = helper::delete('examity_exam', 'examity_exam_id', $exam->examity_exam_id);
                        //     $delete_examity_user_exam = helper::delete('examity_user_exam', 'examity_exam_id', $exam->examity_exam_id);
                        //     $delete_examity_course_exam = helper::delete('examity_course_exam', 'examity_exam_id', $exam->examity_exam_id);
                        // }
                        // // Delete course record from examity after deleting the exams
                        // $delete_examity_course = helper::delete_examity_course($url, $examitycourseid, $headers);
                        // // Delete course record from moodle custom database
                        // $delete_examity_course = helper::delete('examity_course', 'examity_course_id', $examitycourseid);
                        // $delete_examity_user_course = helper::delete('examity_user_course', 'examity_course_id', $examitycourseid);
                        // $delete_examity_course_exam = helper::delete('examity_course_exam', 'examity_course_id', $examitycourseid);
                        // // Delete course record from examity after deleting the exams
                        // $delete_examity_course = helper::delete_examity_course($url, $examitycourseid, $headers);
                        // $delete_examity_exam = helper::delete_examity_exam($url, $examityexamid, $headers);

                        if ($examityexamid) {
                            $examityexam = helper::get_examity_exam($url, $examityexamid, $headers);
                            if (isset($examityexam['exam_id'])) {

                                $examityexamid = $examityexam['exam_id'];
                                helper::delete_examity_exam($url, $examityexamid, $headers);
                                $DB->delete_records('quizaccess_examity_e', ['examity_exam_id' => $examityexamid]);
                                $DB->delete_records('quizaccess_examity_u_e', ['examity_exam_id' => $examityexamid]);
                                $DB->delete_records('quizaccess_examity_c_e', ['examity_exam_id' => $examityexamid]);
                                $message = get_string('success_delete_exam', 'quizaccess_examity');
                                $messagetype = 'success';
                                \core\notification::add($message, $messagetype);
                            } else {
                                $message = get_string('error_delete_exam', 'quizaccess_examity');
                                $messagetype = 'error';
                                \core\notification::add($message, $messagetype);
                            }
                        }
                        break;
                    default:
                        return;
                }
            } else {
                $message = get_string('error_auth', 'quizaccess_examity');
                $messagetype = 'error';
                \core\notification::add($message, $messagetype);
                return null;
            }
        }
    }
}