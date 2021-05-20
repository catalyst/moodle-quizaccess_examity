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

// postAPI -- change this to be post_api
// get_examity_token
// get_examity_user
// get_examity_course
// get_examity_exam
// create_examity_user
// create_examity_course
// create_examity_exam
// update_examity_user
// update_examity_course
// update_examity_exam
// delete_examity_user
// delete_examity_course
// delete_examity_exam

/**
 * Examity / Moodle integration 
 * @package    quizaccess_examity
 * @copyright  2021 Catalyst IT
 * @author     Ant
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

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
        $moodle_user_id   = (int)$event->userid.'11';

        //
        // Grab essential DB details 
        // 
        $username = $DB->get_record('config_plugins', ['plugin' => 'quizaccess_examity', 'name' => 'username'], 'value');
        $password = $DB->get_record('config_plugins', ['plugin' => 'quizaccess_examity', 'name' => 'consumer_secret'], 'value');
        $url      = $DB->get_record('config_plugins', ['plugin' => 'quizaccess_examity', 'name' => 'url'], 'value');
        $moodle_course   = $DB->get_record('examity_courses', ['moodle_course_id' => $moodle_course_id]);
        $moodle_user     = $DB->get_record('examity_users', ['moodle_user_id' => $moodle_user_id]);
        // $exam_id      = $DB->get_record('examity_exams', ['moodle_exam_id' => $event]);

        //
        // Connect to examity auth
        // 
        $examity_token = self::get_examity_token();
        $headers['Authorization'] = ' Bearer '. $examity_token["access_token"];

        // get_examity_user
        $examity_user = self::get_examity_user($moodle_user, $headers);

        // get_examity_course
        $examity_course = self::get_examity_course($moodle_user, $moodle_course, $headers);

        // get_examity_exam
        $examity_exam = self::get_examity_exam($examity_user, $examity_course, $headers);

        // create_examity_user
        $examity_user = self::create_examity_user($moodle_user, $headers);

        // create_examity_course
        $examity_course = self::create_examity_course($examity_user, $moodle_course, $headers);

        // create_examity_exam
        $examity_exam = self::create_examity_exam($moodle_user, $examity_course, $headers);

        // update_examity_user
        $examity_user = self::update_examity_user($moodle_user, $headers);

        // update_examity_course
        $examity_course = self::update_examity_course($examity_user, $moodle_course, $headers);

        // update_examity_exam
        $examity_exam = self::update_examity_exam($examity_exam, $examity_course, $headers);

        // delete_examity_user
        $examity_user = self::delete_examity_user($examity_user, $headers);

        // delete_examity_course
        $examity_course = self::delete_examity_course($examity_course, $headers);

        // delete_examity_exam
        $examity_exam = self::delete_examity_exam($examity_exam, $headers);


        //
        // Run curl requests based on moodle event, ie create course, update, delete etc
        //
        switch ($event->eventname) {
            case '\core\event\course_module_created': // Triggers when quiz is selected as a course activity

                    //
                    // ask examity to get a user based on the moodle_user else create one
                    //
                    $examity_user = isset(self::get_examity_user($moodle_user, $headers)) ?? null;

                    if($examity_user == null) {
                        $examity_user = self::create_examity_user($moodle_user, $headers);
                    }

                    //
                    // ask examity to get course based on moodle_course else create one
                    //
                    $examity_course = isset(self::get_examity_course($moodle_user, $moodle_course, $headers)) ?? null;

                    if($examity_course == null) {
                        $examity_course = self::create_examity_course($examity_user, $moodle_course, $headers);
                    }

                    //
                    // ask examity to get course based on moodle_course
                    //
                    $examity_exam = isset(self::get_examity_exam($examity_user, $examity_course, $headers)) ?? null;

                    if($examity_exam == null) {
                        $examity_exam = self::create_examity_exam($moodle_user, $examity_course, $headers);
                    }

                break;
            case '\core\event\course_module_updated': // Triggers when course is updated

                    //
                    // ask examity to get a user based on the moodle_user
                    //
                    $examity_user = isset(self::get_examity_user($moodle_user, $headers)) ?? null;

                    if($examity_user == null) {
                        $examity_user = self::create_examity_user($moodle_user, $headers);
                    }
                    
                    //
                    // update a course in examity
                    //
                    $examity_course = isset(self::get_examity_course($moodle_user, $moodle_course, $headers)) ?? null;

                    if($examity_course == null) {

                        // TODO: if there is no existing course in examity return because we can't find course to update 
                        continue;
                        
                    } else {

                        // TODO: try catch here / update course 
                        $examity_course = self::update_examity_course($examity_user, $moodle_course, $headers);

                    }

                    //
                    // ask examity to get exam based on moodle_exam. TODO: create moodle_exam
                    //
                    $examity_exam = isset(self::get_examity_exam($examity_user, $examity_course, $headers)) ?? null;

                    if($examity_exam == null) {

                        // if there is no existing exam in examity create one
                        continue;
                    } else {

                        // update the exam
                        $examity_exam = self::update_examity_exam($examity_exam, $examity_course, $headers);
                    }

                break;
            case '\core\event\course_module_deleted':

                    //
                    // ask examity to get a user based on the moodle_user
                    //
                    $examity_user = isset(self::get_examity_user($moodle_user, $headers)) ?? null;

                    if($examity_user == null) {
                        return null;
                    }
                    
                    //
                    // delete course in examity
                    //
                    $examity_course = isset(self::get_examity_course($moodle_user, $moodle_course, $headers)) ?? null;

                    if($examity_course == null) {

                        // TODO: if there is no existing course in examity return because we can't find course to update 
                        continue;
                        
                    } else {
                        // delete_examity_course
                        $examity_course = self::delete_examity_course($examity_course, $headers);
                    }

                    //
                    // ask examity to get exam based on moodle_exam. TODO: create moodle_exam
                    //
                    $examity_exam = isset(self::get_examity_exam($examity_user, $examity_course, $headers)) ?? null;

                    if($examity_exam == null) {

                        // if there is no existing exam in examity create one
                        continue;
                    } else {

                        // update the exam
                        $examity_exam = self::delete_examity_exam($examity_exam, $headers);
                    }

                break;
            default:
                return;
        }
    }

    //
    // Run curl on examity's bridge api 
    //
    public static function postAPI($url, $crud=null, $postdata=null, $headers=null, $fullresponse=false, $timeout=300, $connecttimeout=20, $skipcertverify=false) {

        global $CFG;
        $options = array();

        // Only http and https links supported
        if (!preg_match('|^https?://|i', $url)) {
            if ($fullresponse) {
                $response = new stdClass();
                $response->status        = 0;
                $response->headers       = array();
                $response->response_code = 'Invalid protocol specified in url';
                $response->results       = '';
                $response->error         = 'Invalid protocol specified in url';
                return $response;
            } else {
                return false;
            }
        }

        $headers2 = array();
        if (is_array($headers)) {
            foreach ($headers as $key => $value) {
                if (is_numeric($key)) {
                    $headers2[] = $value;
                } else {
                    $headers2[] = "$key: $value";
                }
            }
        }
    
        if ($skipcertverify) {
            $options['CURLOPT_SSL_VERIFYPEER'] = false;
        } else {
            $options['CURLOPT_SSL_VERIFYPEER'] = true;
        }
    
        $options['CURLOPT_CONNECTTIMEOUT'] = $connecttimeout;
        $options['CURLOPT_FOLLOWLOCATION'] = 1;
        $options['CURLOPT_MAXREDIRS'] = 5;

        // Start a curl request 
        $curl = new curl();
        $curl->setHeader($headers2);
    
        $options['CURLOPT_RETURNTRANSFER'] = true;
        $options['CURLOPT_NOBODY'] = false;
        $options['CURLOPT_TIMEOUT'] = $timeout;

        // Create, read, update, delete with curl
        switch ($crud) {
            case 'create':
                    $content = $curl->post($url, $postdata, $options);
                break;
            case 'read':
                    $content = $curl->get($url, $postdata, $options);
                break;
            case 'update':
                    $content = $curl->put($url, $postdata, $options);
                break;
            case 'delete':
                    $content = $curl->delete($url, $postdata, $options);
                break;
            default:
                return;
        }
    
        // get error messages if any
        $info       = $curl->get_info();
        $error_no   = $curl->get_errno();
        $rawheaders = $curl->get_raw_response();
    
        if ($error_no) {
            $error = $content;
            if (!$fullresponse) {
                debugging("cURL request for \"$url\" failed with: $error ($error_no)", DEBUG_ALL);
                return false;
            }
    
            $response = new stdClass();
            if ($error_no == 28) {
                $response->status    = '-100'; // Mimic snoopy.
            } else {
                $response->status    = '0';
            }
            $response->headers       = array();
            $response->response_code = $error;
            $response->results       = false;
            $response->error         = $error;
            return $response;
        }
    
        if (empty($info['http_code'])) {
            // For security reasons we support only true http connections
            $response = new stdClass();
            $response->status        = '0';
            $response->headers       = array();
            $response->response_code = 'Unknown cURL error';
            $response->results       = false; // do NOT change this, we really want to ignore the result!
            $response->error         = 'Unknown cURL error';
    
        } else {
            $response = new stdClass();
            $response->status        = (string)$info['http_code'];
            $response->headers       = $rawheaders;
            $response->results       = $content;
            $response->error         = '';
    
            // Encase there's multiple headers on redirect, find the status of the last one.
            $firstline = true;
            foreach ($rawheaders as $line) {
                if ($firstline) {
                    $response->response_code = $line;
                    $firstline = false;
                }
                if (trim($line, "\r\n") === '') {
                    $firstline = true;
                }
            }
        }
    
        if ($fullresponse) {
            return $response;
        }

        // if ($info['http_code'] != 200) {
        //     debugging("cURL request for \"$url\" failed, HTTP response code: ".$response->response_code, DEBUG_ALL);
        //     return false;
        // }

        return $response->results;
    }

    //
    // Connect to examity auth
    //
    public function get_examity_token() {

        $validation_data = "{
            \"client_id\":171,
            \"username\":\"$username->value\",
            \"password\":\"$password->value\"
        }";
        $token = self::postAPI($url->value .'/auth', 'create', $validation_data);
        $examity_token = json_decode($token, true);

        return $examity_token;
    }

    public function get_examity_user($moodle_user, $headers) {

        if($moodle_user) {

            // Check for existing instructor from Examity based on course creator ID eg. 107151
            $examity_user_id = (int)$moodle_user->examity_user_id ?? null;
            $primary_instructor_id = self::postAPI($url->value .'/users' . '/' . $examity_user_id, 'read', null, $headers);
            $primary_instructor_id_arr = json_decode($primary_instructor_id, true);
            $primary_instructor_id = $primary_instructor_id_arr['user_id'];
            $examity_user = $primary_instructor_id_arr;

        } else {

            $examity_user = null;
        }

        return $examity_user;
    }

    public function get_examity_course($moodle_user, $moodle_course, $headers) {
         
        //TODO: if already exists return early with this course id

        $url = $url->value . '/courses';
        $course_code = $examity_course_id;
        $course_name = 'test course name';
        $primary_instructor_id = $primary_instructor_id;
        $instructor_ids = $primary_instructor_id; 
        $status_id = 1;  
        $metadata = '';  

        $postdata = "{
                        \"course_code\":\"$course_code\",
                        \"course_name\":\"$course_name\",
                        \"primary_instructor_id\":$primary_instructor_id,
                        \"instructor_ids\":[$instructor_ids],
                        \"status_id\":$status_id,
                        \"metadata\":{}
                    }";

        return self::postAPI($url, 'create', $postdata, $headers);
    }

    public function get_examity_exam($examity_user, $moodle_course, $headers){

        $examity_exam = null;
        $examity_exam_id = null;
        //TODO: get exam id

        $url = $url->value . '/exams' . $examity_exam_id;

        $examity_exam = self::postAPI($url, 'read', $postdata, $headers);

        return $examity_exam;
    }

    public function create_examity_user ($moodle_user, $headers) {

        $examity_course = null;

        return $examity_user
    }

    public function create_examity_course($examity_course, $examidity_user, $moodle_course, $headers) {

        $examity_course = null;

        return $examity_course;
    }

    public function create_examity_exam($moodle_user, $examity_course, $headers) {

        $examity_exam = null;

        return $examity_exam;
    }

    public function update_examity_user($examity_user, $headers) {

        $examity_user = null;

        return $examity_user;
    }

    public function update_examity_course($examity_user, $moodle_course, $headers) {

        $examity_course = null;

        $url = $url->value . '/courses';
        $course_code = '171';
        $course_name = $COURSE->fullname;
        $primary_instructor_id = $primary_instructor_id;
        $instructor_ids = $primary_instructor_id; 
        $status_id = 1;  
        $metadata = '';  

        $postdata = "{
            \"course_code\":\"$course_code\",
            \"course_name\":\"$course_name\",
            \"primary_instructor_id\":$primary_instructor_id,
            \"instructor_ids\":[$instructor_ids],
            \"status_id\":$status_id,
            \"metadata\":{}
        }";

        return isset(self::postAPI($url, 'update', $postdata, $headers)) ?? null; 
    }

    public function update_examity_exam($examity_exam, $examity_course, $headers) {

        $examity_exam = null;

        return $examity_exam;
    }

    public function delete_examity_user($examity_user, $headers) {

        $examity_exam = null;

        return $examity_user;
    }

    public function delete_examity_course($examity_course, $headers) {
        $examity_course = null;

        $url = $url->value . '/courses' . '/' . $event->courseid;
        $examity_course = self::postAPI($url, 'delete', $postdata);
        
        return $examity_course;
    }

    public function delete_examity_exam($examity_exam, $headers) {

        $examity_exam = null;

        return $examity_exam;
    }
    
}