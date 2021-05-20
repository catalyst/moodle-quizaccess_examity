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
 * Examity event observer class.
 *
 * Send API requests based on Moodle event
 *
 * @copyright  2021 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quizaccess_examity_observer {

    public static function update(\core\event\base $event) {
        
        global $DB;
        global $COURSE;
        global $USER;

        $url = null;
        $postdata  = [];
        $moodle_course_id = (int)$COURSE->id;
        $moodle_user_id   = (int)$event->userid;

        //
        // Grab essential DB details 
        // 
        $username       = $DB->get_record('config_plugins', ['plugin' => 'quizaccess_examity', 'name' => 'username'], 'value');
        $password       = $DB->get_record('config_plugins', ['plugin' => 'quizaccess_examity', 'name' => 'consumer_secret'], 'value');
        $url            = $DB->get_record('config_plugins', ['plugin' => 'quizaccess_examity', 'name' => 'url'], 'value');
        $moodle_course  = $DB->get_record('examity_courses', ['moodle_course_id' => $moodle_course_id]);
        $moodle_user    = $DB->get_record('examity_users', ['moodle_user_id' => $moodle_user_id]);
        $exam_id        = $DB->get_record('examity_exams', ['moodle_exam_id' => $event]);

        //
        // Connect to examity auth
        // 
        $examity_token = helper::get_examity_token();
        $headers['Authorization'] = ' Bearer '. $examity_token["access_token"];

        // Testing examity api //

        // get_examity_user
        $examity_user = helper::get_examity_user($moodle_user, $headers);

        // get_examity_course
        $examity_course = helper::get_examity_course($moodle_user, $moodle_course, $headers);

        // get_examity_exam
        $examity_exam = helper::get_examity_exam($examity_user, $examity_course, $headers);

        // create_examity_user
        $examity_user = helper::create_examity_user($moodle_user, $headers);

        // create_examity_course
        $examity_course = helper::create_examity_course($examity_user, $moodle_course, $headers);

        // create_examity_exam
        $examity_exam = helper::create_examity_exam($moodle_user, $examity_course, $headers);

        // update_examity_user
        $examity_user = helper::update_examity_user($moodle_user, $headers);

        // update_examity_course
        $examity_course = helper::update_examity_course($examity_user, $moodle_course, $headers);

        // update_examity_exam
        $examity_exam = helper::update_examity_exam($examity_exam, $examity_course, $headers);

        // delete_examity_user
        $examity_user = helper::delete_examity_user($examity_user, $headers);

        // delete_examity_course
        $examity_course = helper::delete_examity_course($examity_course, $headers);

        // delete_examity_exam
        $examity_exam = helper::delete_examity_exam($examity_exam, $headers);

        //
        // Run curl requests based on moodle event, ie create course, update, delete etc
        //
        switch ($event->eventname) {
            case '\core\event\course_module_created': // Triggers when quiz is selected as a course activity

                    //
                    // ask examity to get a user based on the moodle_user else create one
                    //
                    $examity_user = isset(helper::get_examity_user($moodle_user, $headers)) ?? null;

                    if($examity_user == null) {
                        $examity_user = helper::create_examity_user($moodle_user, $headers);
                    }

                    //
                    // ask examity to get course based on moodle_course else create one
                    //
                    $examity_course = isset(helper::get_examity_course($moodle_user, $moodle_course, $headers)) ?? null;

                    if($examity_course == null) {
                        $examity_course = helper::create_examity_course($examity_user, $moodle_course, $headers);
                    }

                    //
                    // ask examity to get course based on moodle_course
                    //
                    $examity_exam = isset(helper::get_examity_exam($examity_user, $examity_course, $headers)) ?? null;

                    if($examity_exam == null) {
                        $examity_exam = helper::create_examity_exam($moodle_user, $examity_course, $headers);
                    }

                break;
            case '\core\event\course_module_updated': // Triggers when course is updated

                    //
                    // ask examity to get a user based on the moodle_user
                    //
                    $examity_user = isset(helper::get_examity_user($moodle_user, $headers)) ?? null;

                    if($examity_user == null) {
                        $examity_user = helper::create_examity_user($moodle_user, $headers);
                    }
                    
                    //
                    // update a course in examity
                    //
                    $examity_course = isset(helper::get_examity_course($moodle_user, $moodle_course, $headers)) ?? null;

                    if($examity_course == null) {

                        // TODO: if there is no existing course in examity return because we can't find course to update 
                        continue;
                        
                    } else {

                        // TODO: try catch here / update course 
                        $examity_course = helper::update_examity_course($examity_user, $moodle_course, $headers);

                    }

                    //
                    // ask examity to get exam based on moodle_exam. TODO: create moodle_exam
                    //
                    $examity_exam = isset(helper::get_examity_exam($examity_user, $examity_course, $headers)) ?? null;

                    if($examity_exam == null) {

                        // if there is no existing exam in examity create one
                        continue;
                    } else {

                        // update the exam
                        $examity_exam = helper::update_examity_exam($examity_exam, $examity_course, $headers);
                    }

                break;
            case '\core\event\course_module_deleted':

                    //
                    // ask examity to get a user based on the moodle_user
                    //
                    $examity_user = isset(helper::get_examity_user($moodle_user, $headers)) ?? null;

                    if($examity_user == null) {
                        return null;
                    }
                    
                    //
                    // delete course in examity
                    //
                    $examity_course = isset(helper::get_examity_course($moodle_user, $moodle_course, $headers)) ?? null;

                    if($examity_course == null) {

                        // TODO: if there is no existing course in examity return because we can't find course to update 
                        continue;
                        
                    } else {
                        // delete_examity_course
                        $examity_course = helper::delete_examity_course($examity_course, $headers);
                    }

                    //
                    // ask examity to get exam based on moodle_exam. TODO: create moodle_exam
                    //
                    $examity_exam = isset(helper::get_examity_exam($examity_user, $examity_course, $headers)) ?? null;

                    if($examity_exam == null) {

                        // if there is no existing exam in examity create one
                        continue;
                    } else {

                        // update the exam
                        $examity_exam = helper::delete_examity_exam($examity_exam, $headers);
                    }

                break;
            default:
                return;
        }
    }    
}