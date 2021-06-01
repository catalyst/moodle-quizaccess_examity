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

        $examity_user_id = null;
        $examity_course_id = null;
        $examity_exam_id = null;
        $moodle_user_id = (int)$event->userid ?? null;
        $moodle_course_id = (int)$COURSE->id ?? null;
        $moodle_exam_id = $event->other['instanceid'] ?? null;

        // var_dump('gets to here??????????????????????????????????????????');
        // var_dump($event);
        // var_dump('gets to here??????????????????????????????????????????');die;


        //
        // Grab essential DB details 
        // 
        $consumer_username      = $DB->get_record('config_plugins', ['plugin' => 'quizaccess_examity', 'name' => 'consumer_username'], 'value');
        $consumer_password      = $DB->get_record('config_plugins', ['plugin' => 'quizaccess_examity', 'name' => 'consumer_password'], 'value');
        $client_username        = $DB->get_record('config_plugins', ['plugin' => 'quizaccess_examity', 'name' => 'client_username'], 'value');
        $client_password        = $DB->get_record('config_plugins', ['plugin' => 'quizaccess_examity', 'name' => 'client_password'], 'value');
        $client_id              = $DB->get_record('config_plugins', ['plugin' => 'quizaccess_examity', 'name' => 'client_id'], 'value');
        $url                    = $DB->get_record('config_plugins', ['plugin' => 'quizaccess_examity', 'name' => 'examity_url'], 'value');

        // Check whether the user, course or exam is already existing in the db
        $examity_user_id        = $DB->get_record('examity_user', ['moodle_user_id' => $moodle_user_id]);
        if(isset($examity_user_id->examity_user_id)){
            $examity_user_id = (int)$examity_user_id->examity_user_id;

        }
        $examity_course_id      = $DB->get_record('examity_course', ['moodle_course_id' => $moodle_course_id]);
        if(isset($examity_course_id->examity_course_id)){
            $examity_course_id = (int)$examity_course_id->examity_course_id;
        }
        $examity_exam_id        = $DB->get_record('examity_exam', ['moodle_exam_id' => $moodle_exam_id]);
        if(isset($examity_exam_id->examity_exam_id)){
            $examity_exam_id = (int)$examity_exam_id->examity_exam_id;
        }

        //
        // Connect to examity auth
        // 
        $examity_token = helper::get_examity_token($url, $client_id, $consumer_username, $consumer_password);        
        $headers['Authorization'] = ' Bearer '. $examity_token["access_token"];

        // $examity_user = helper::get_examity_user($url, $examity_user_id, $headers);
        // $examity_user = $examity_user['user_id']);

        // $examity_course = helper::get_examity_course($url, $examity_course_id, $headers);
        // $examity_user = $examity_course['course_id']);

        
        // $examity_user = helper::create_examity_user($url, $USER, $headers);

        // $examity_course = helper::create_examity_course($url, $moodle_user_id, $COURSE, $headers);

        // // create_examity_exam
        // $examity_exam = helper::create_examity_exam($url, $moodle_user_id, $moodle_course_id, $moodle_exam_id, $headers);

        // // update_examity_user
        // $examity_user = helper::update_examity_user($url, $moodle_user_id, $headers);

        // // update_examity_course
        // $examity_course = helper::update_examity_course($url, $moodle_user_id, $moodle_course_id, $headers);

        // // update_examity_exam
        // $examity_exam = helper::update_examity_exam($url, $moodle_course_id, $moodle_exam_id, $headers);

        // $examity_course = helper::get_examity_course($url, $examity_course_id, $headers) ?? null;


        // $examity_course = helper::get_examity_course($url, $examity_course_id, $headers);
        // $examity_course_id = $examity_course['course_id'];

        // $examity_course = helper::delete_examity_course($url, $examity_course_id, $headers);

        // // delete_examity_user
        // $examity_user = helper::delete_examity_user($url, $moodle_user_id, $headers);

        // delete_examity_course
        // $examity_course = helper::delete_examity_course($url, $moodle_course_id, $headers);

        // // delete_examity_exam
        // $examity_exam = helper::delete_examity_exam($url, $moodle_exam_id, $headers);

        
        
        switch ($event->eventname) {
            case '\core\event\course_module_created': // Triggers when quiz is selected as a course activity

                    $user = false;
                    $course = false;
                    $exam = false;
                
                    // If examity user doesn't exist create and update custom database
                    if(!$examity_user_id) {

                        $examity_user_id = helper::create_examity_user($url, $USER, $headers);

                        if(isset($examity_user_id)){


                            $data = [
                                'id' => null,
                                'moodle_user_id' => $moodle_user_id,
                                'examity_user_id' => $examity_user_id
                            ];
    
                            $insert = helper::insert($data, 'examity_user');

                            if($insert == false){
                                return null;
                            }

                            $user = true;
                        }
                    } else {
                        $user = true;
                    }
                    
                    //
                    // ask examity to get a course based on moodle_course else create one
                    //
                    if(!$examity_course_id) {

                        $examity_course_id = helper::create_examity_course($url, $examity_user_id, $COURSE, $headers);

                        if(isset($examity_course_id)){

                            $data = [
                                'id' => null,
                                'moodle_course_id' => $moodle_course_id,
                                'examity_course_id' => $examity_course_id
                            ];

    
                            $insert = helper::insert($data, 'examity_course');
                            if($insert == false){
                                return null;
                            }
                            $course = true;
                        }
                    } else {
                        $course = true;
                    }

                    //
                    // ask examity to get a exam based on moodle_exam else create one
                    //
                    if(!$examity_exam_id) {

                        $examity_exam_id = helper::create_examity_exam($url, $moodle_user_id, $moodle_course_id, $moodle_exam_id, $headers);

                        // we've created a new exam in examity
                        if(isset($examity_exam_id)){

                            $data = [
                                'id' => null,
                                'moodle_exam_id' => $moodle_exam_id,
                                'examity_exam_id' => $examity_exam_id
                            ];
    
                            $insert = helper::insert($data, 'examity_exam');
                            if($insert == false){
                                return null;
                            }
                            $exam = true;
                        }
                    } else {
                        $exam = true;
                    }

                    // If all of these have values in the custom one to one tables then update the one to many
                    if($user && $course && $exam) {
                    
                        // examity_user_course
                        $data = [
                            'id' => null,
                            'moodle_user_id' => $moodle_user_id,
                            'moodle_course_id' => $moodle_course_id,
                            'examity_user_id' => $examity_user_id,
                            'examity_course_id' => $examity_course_id,
                        ];

                        $insert = helper::insert($data, 'examity_user_course');

                        // examity_user_exam
                        $data = [
                            'id' => null,
                            'moodle_user_id' => $moodle_user_id,
                            'moodle_exam_id' => $moodle_exam_id,
                            'examity_user_id' => $examity_user_id,
                            'examity_exam_id' => $examity_exam_id,
                        ];

                        $insert = helper::insert($data, 'examity_user_exam');

                        $examity_user   = helper::get_examity_user($url, $examity_user_id, $headers);
                        $examity_course = helper::get_examity_course($url, $examity_course_id, $headers);
                        $examity_exam   = helper::get_examity_exam($url, $examity_exam_id, $headers);
                
                        // examity_course_exam
                        $data = [
                            'id' => null,
                            'moodle_course_id' => $moodle_course_id,
                            'moodle_exam_id' => $moodle_exam_id,
                            'examity_course_id' => $examity_course_id,
                            'examity_exam_id' => $examity_exam_id,
                        ];

                        $insert = helper::insert($data, 'examity_course_exam');
                    }

                break;
            case '\core\event\course_module_updated': // Triggers when course is updated

                    //
                    // ask examity to get a user inferred from the moodle_user_id
                    // if examity finds a user, it updates it's $USER data inside examity
                    //
                    // if($examity_user_id) {
                    //     $examity_user_id = helper::update_examity_user($url, $USER, $headers) ?? null;
                    // }
                    
                    //
                    // ask examity to get a course infered from the moodle_course_id
                    // if examity finds a course, it updates it's $COURSE data inside examity
                    //
                    if($examity_course_id) {
                        $examity_course_id = helper::update_examity_course($url, $examity_user_id, $examity_course_id, $examity_exam_id, $COURSE, $headers) ?? null;
                    }

                    
                    // ask examity to get a course infered from the moodle_course_id
                    // if examity finds a course, it updates it's $COURSE data inside examity
                    //
                    if($examity_exam_id) {
                        $examity_exam_id = helper::update_examity_exam($url, $examity_user_id, $examity_course_id, $examity_exam_id, $headers) ?? null;
                    } 

                break;
            case '\core\event\course_module_deleted':

                    //
                    // delete course
                    //
                    // $exams = helper::select('examity_course_exam', 'examity_course_id', $examity_course_id);
                    // foreach($exams as $exam){

                    //     // Delete exams associated to a couse from examity
                    //     $delete_examity_exam = helper::delete_examity_exam($url, $exam->examity_exam_id, $headers);

                    //     // Delete exams associated to a couse from moodle custom database 
                    //     $delete_examity_exam = helper::delete('examity_exam', 'examity_exam_id', $exam->examity_exam_id);
                    //     $delete_examity_user_exam = helper::delete('examity_user_exam', 'examity_exam_id', $exam->examity_exam_id);
                    //     $delete_examity_course_exam = helper::delete('examity_course_exam', 'examity_exam_id', $exam->examity_exam_id);
                    // }

                    // // Delete course record from examity after deleting the exams 
                    // $delete_examity_course = helper::delete_examity_course($url, $examity_course_id, $headers);

                    // // Delete course record from moodle custom database 
                    // $delete_examity_course = helper::delete('examity_course', 'examity_course_id', $examity_course_id);
                    // $delete_examity_user_course = helper::delete('examity_user_course', 'examity_course_id', $examity_course_id);
                    // $delete_examity_course_exam = helper::delete('examity_course_exam', 'examity_course_id', $examity_course_id);


                    // // Delete course record from examity after deleting the exams 
                    // $delete_examity_course = helper::delete_examity_course($url, $examity_course_id, $headers);

                    // $delete_examity_exam = helper::delete_examity_exam($url, $examity_exam_id, $headers);

                    //
                    // delete exam 
                    // 
                    // examity_exam
                    // examity_user_exam
                    // examity_course_exam



                    if($examity_exam_id){
                        $examity_exam = helper::get_examity_exam($url, $examity_exam_id, $headers);

                        if(isset($examity_exam['exam_id'])){
                            helper::delete_examity_exam($url, $examity_exam_id, $headers);
                            helper::delete('examity_exam', 'examity_exam_id', $examity_exam_id);
                            helper::delete('examity_user_exam', 'examity_exam_id', $examity_exam_id);
                            helper::delete('examity_course_exam', 'examity_exam_id', $examity_exam_id);
                        }
                    }

                break;
            default:
                return;
        }
    }
   
}