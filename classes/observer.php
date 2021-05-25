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

class quizaccess_examity_observer {

    public static function update(\core\event\base $event) {

        
        global $DB;
        global $COURSE;
        global $USER;
        global $PAGE;

        $consumer_username = null;
        $consumer_password = null;
        $client_username = null;
        $client_password = null;
        $client_id = null;
        $postdata  = [];
        $url = null;

        $moodle_user_id = (int)$event->userid ?? null;
        $examity_user_id = null;
        $moodle_course_id = (int)$COURSE->id ?? null;
        $examity_course_id = null;
        $moodle_exam_id = $event->other['instanceid'] ?? null;
        $examity_exam_id = null;

        //
        // Grab essential DB details 
        // 
        $consumer_username      = $DB->get_record('config_plugins', ['plugin' => 'quizaccess_examity', 'name' => 'consumer_username'], 'value');
        $consumer_password      = $DB->get_record('config_plugins', ['plugin' => 'quizaccess_examity', 'name' => 'consumer_password'], 'value');
        $client_username        = $DB->get_record('config_plugins', ['plugin' => 'quizaccess_examity', 'name' => 'client_username'], 'value');
        $client_password        = $DB->get_record('config_plugins', ['plugin' => 'quizaccess_examity', 'name' => 'client_password'], 'value');
        $client_id              = $DB->get_record('config_plugins', ['plugin' => 'quizaccess_examity', 'name' => 'client_id'], 'value');
        $url                    = $DB->get_record('config_plugins', ['plugin' => 'quizaccess_examity', 'name' => 'examity_url'], 'value');

        
        $moodle_user_id         = $DB->get_record('quizaccess_examity_data', ['moodle_user_id' => $moodle_user_id]);
        // $examity_user_id        = $DB->get_record('examity_data', ['examity_user_id' => $examity_user_id]);
        $moodle_course_id       = $DB->get_record('quizaccess_examity_data', ['moodle_course_id' => $moodle_course_id]);
        // $examity_course_id      = $DB->get_record('examity_data', ['examity_course_id' => $examity_course_id]);
        $moodle_exam_id         = $DB->get_record('quizaccess_examity_data', ['moodle_exam_id' => $moodle_exam_id]);
        // $examity_exam_id        = $DB->get_record('examity_data', ['examity_exam_id' => $examity_exam_id]);
                
        //
        // Connect to examity auth
        // 
        $examity_token = helper::get_examity_token($url, $client_id, $consumer_username, $consumer_password);        
        $headers['Authorization'] = ' Bearer '. $examity_token["access_token"];



        // get_examity_user
        // $examity_user = helper::get_examity_user($url, $moodle_user, $headers);

        // get_examity_course
        // $examity_course = helper::get_examity_course($url, $moodle_course, $headers);

        // // get_examity_exam
        // $examity_exam = helper::get_examity_exam($url, $moodle_course, $headers);
        // var_dump($examity_exam);die;

        // create_examity_user
        // $examity_user = helper::create_examity_user($url, $USER, $headers);

        // create_examity_course
        // $examity_course = helper::create_examity_course($url, $moodle_user, $COURSE, $headers);

        // // create_examity_exam
        $examity_exam = helper::create_examity_exam($url, $moodle_user, $moodle_course, $moodle_exam, $headers);
        var_dump($examity_exam);die;

        // // update_examity_user
        // $examity_user = helper::update_examity_user($moodle_user, $headers);

        // // update_examity_course
        // $examity_course = helper::update_examity_course($examity_user, $moodle_course, $headers);

        // // update_examity_exam
        // $examity_exam = helper::update_examity_exam($examity_exam, $examity_course, $headers);

        // // delete_examity_user
        // $examity_user = helper::delete_examity_user($examity_user, $headers);

        // delete_examity_course
        // $examity_course = helper::delete_examity_course($url, $moodle_course, $headers);

        // // delete_examity_exam
        // $examity_exam = helper::delete_examity_exam($examity_exam, $headers);

        //
        // Run curl requests based on moodle event, ie create course, update, delete etc
        //
        switch ($event->eventname) {
            case '\core\event\course_module_created': // Triggers when quiz is selected as a course activity

                    //
                    // ask examity to get a user based on the moodle_user else create one
                    //
                    $examity_user = helper::get_examity_user($url, $moodle_user, $headers) ?? null;

                    if($examity_user == null) {
                        $examity_user = helper::create_examity_user($url, $USER, $headers);
                    }

                    //
                    // ask examity to get a course based on moodle_course else create one
                    //
                    $examity_course = helper::get_examity_course($url, $moodle_course, $headers) ?? null;

                    if($examity_course == null) {
                        $examity_course = helper::create_examity_course($url, $moodle_user, $COURSE, $headers);
                    }

                    //
                    // ask examity to get exam based on moodle_exam
                    //
                    $examity_exam = helper::get_examity_exam($url, $moodle_exam, $moodle_course, $headers) ?? null;

                    if($examity_exam == null) {
                        $examity_exam = helper::create_examity_exam($url, $moodle_user, $moodle_course, $moodle_exam, $headers);
                    }

                break;
            case '\core\event\course_module_updated': // Triggers when course is updated

                    //
                    // ask examity to get a user based on the moodle_user
                    //
                    // $examity_user = helper::get_examity_user($url, $moodle_user, $headers) ?? null;

                    // if($examity_user != null) {
                    //     $examity_user = helper::create_examity_user($url, $USER, $headers);
                    // }
                    
                    //
                    // update a course in examity
                    //
                    $examity_course = helper::get_examity_course($url, $moodle_course, $headers);

                    if($examity_course != null) {

                        // TODO: try catch here / update course 
                        $examity_course = helper::update_examity_course($url, $moodle_user, $moodle_course, $headers);

                    }

                    //
                    // ask examity to get exam based on moodle_exam. TODO: create moodle_exam
                    //
                    $examity_exam = helper::get_examity_exam($url, $moodle_course, $headers);

                    if($examity_exam != null) {

                        $examity_exam = helper::update_examity_exam($url, $moodle_exam, $moodle_course, $headers);
                    } 

                break;
            case '\core\event\course_module_deleted':
                    
                    //
                    // delete course in examity
                    //
                    $examity_course = helper::get_examity_course($url, $moodle_course, $headers) ?? null;

                    if($examity_course != null) {

                        // delete_examity_course
                        $examity_course = helper::delete_examity_course($url, $moodle_course, $headers);
                    }

                    //
                    // ask examity to get exam based on moodle_exam. TODO: create moodle_exam
                    //
                    $examity_exam = helper::get_examity_exam($url, $moodle_exam, $moodle_course, $headers) ?? null;

                    if($examity_exam != null) {

                        // update the exam
                        $examity_exam = helper::delete_examity_exam($url, $moodle_exam, $headers);
                    }

                break;
            default:
                return;
        }
    }    
}