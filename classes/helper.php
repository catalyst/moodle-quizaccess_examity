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
 * Helper class to deal with examity api.
 * 
 * post_api 
 * get_examity_token
 * get_examity_user
 * get_examity_course
 * get_examity_exam
 * create_examity_user
 * create_examity_course
 * create_examity_exam
 * update_examity_user
 * update_examity_course
 * update_examity_exam
 * delete_examity_user
 * delete_examity_course
 * delete_examity_exam

 * @package    quizaccess_examity
 * @author     Ant
 * @copyright  2021 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quizaccess_examity;

use curl;
use stdClass;

defined('MOODLE_INTERNAL') || die();

class helper {

    /**
     * Run curl on examity's bridge api.
     *
     * @param string $url The URL for examity bridge.
     * @param string $crud read, write, update, delete in examity.
     * @param string $postdata data to post to examity.
     * @param string $header token details sent here.
     * @param boolean $fullresponse response from api.
     * @param array $options display options.
     * @param int $timeout 
     * @param int $connecttimeout 
     * @param boolean $skipcertverify 
     * @return object
     */
    public static function post_api($url, $crud=null, $postdata=null, $headers=null, $fullresponse=false, $timeout=300, $connecttimeout=20, $skipcertverify=false) {

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

        // create, read, update, delete with curl
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
                $response->status    = '-100'; // mimic snoopy.
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
            // for security reasons we support only true http connections
            $response = new stdClass();
            $response->status        = '0';
            $response->headers       = array();
            $response->response_code = 'Unknown cURL error';
            $response->results       = false; 
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

    /**
     * Get auth token from examity.
     *
     * @return string $examity_token - get auth token from examity.
     */
    public static function get_examity_token($url, $client_id, $username, $password) {

        // var_dump($username);die;

        $validation_data = "{
            \"client_id\": 171,
            \"username\":\"$username->value\",
            \"password\":\"$password->value\"
        }";
        $token = self::post_api($url->value .'/auth', 'create', $validation_data);
        $examity_token = json_decode($token, true);

        return $examity_token;
    }

    /**
     * Get examity user by moodle id.
     *
     * @param object $url - url for the curl request.
     * @param object $moodle_user - moodle user id.
     * @param array $headers - set token in header.
     * @return array $examity_user - get user data stored in examity.
     */
    public static function get_examity_user($url, $moodle_user, $headers) {

            // Check for existing instructor from Examity based on course creator ID eg. 107151
            $examity_user = null;
            $examity_user_id = (int)$moodle_user->examity_user_id ?? null;
            $primary_instructor_id = self::post_api($url->value . '/users' . '/' . $examity_user_id, 'read', null, $headers);
            $primary_instructor_id_arr = json_decode($primary_instructor_id, true);
            $primary_instructor_id = $primary_instructor_id_arr['user_id'];
            $examity_user = $primary_instructor_id_arr;

        return $examity_user;
    }

    /**
     * Get examity course.
     *
     * @param object $url - url for the curl request.
     * @param object $moodle_course - moodle course.
     * @param array $headers set token in header.
     * @return string 
     */
    public static function get_examity_course($url, $moodle_course, $headers) {

        $postdata = "";
        $examity_course_id = (int)$moodle_course->examity_course_id ?? null;
        $url = $url->value . '/courses' . '/' . $examity_course_id;
        $examity_course = self::post_api($url, 'read', $postdata, $headers) ?? null;

        return $examity_course;
    }

    /**
     * Get examity exam.
     *
     * @param object $url - url for the curl request.
     * @param object $moodle_course - moodle course.
     * @param array $headers set token in header.
     * @return string $examity_exam - return exam json.
     */
    public static function get_examity_exam($url, $moodle_exam, $moodle_course, $headers){

        $postdata = "";
        $examity_exam_id = $moodle_exam->id; // TODO: get real exam id from saved values in the database
        $url = $url->value . '/exams' . '/' . $examity_exam_id;
        $examity_exam = self::post_api($url, 'read', $postdata, $headers) ?? null;

        return $examity_exam;
    }

    /**
     * Create examity user.
     *
     * @param object $url - url for the curl request.
     * @param object $USER - moodle user details.
     * @param array $headers set token in header.
     * @return object $examity_user - user details created in examity.
     */
    public static function create_examity_user ($url, $USER, $headers) {

        $country_code = (int)$USER->country;
        $timezone_id = (int)$USER->timezone;
        $url = $url->value . '/users';
        $postdata = "{
                        \"first_name\":\"$USER->firstname\",
                        \"last_name\":\"$USER->lastname\",
                        \"email\":\"$USER->email\",
                        \"role_id\":3,
                        \"id_photo\":\"$USER->picture\",
                        \"phone\":\"$USER->phone2\",
                        \"country_code\":$country_code,
                        \"timezone_id\":$timezone_id,
                        \"metadata\":{},
                        \"username\":\"$USER->username\",
                        \"send_password_reset_email\":true
        }";

        $examity_user = self::post_api($url, 'create', $postdata, $headers) ?? null;
        return $examity_user;
    }

    /**
     * Create course in examity based on moodle course being created.
     *
     * @param object $url - url for the curl request.
     * @param object $moodle_user - moodle user.
     * @param object $COURSE - moodle course.
     * @param array $headers set token in header.
     * @return object
     */
    public static function create_examity_course($url, $moodle_user, $COURSE, $headers) {

        $url = $url->value . '/courses';
        $primary_instructor_id = (int)$moodle_user->examity_user_id ?? null;

        $postdata = "{
                        \"course_code\":\"string\",
                        \"course_name\":\"$COURSE->fullname\",
                        \"primary_instructor_id\":$primary_instructor_id,
                        \"instructor_ids\":[$primary_instructor_id],
                        \"status_id\":1,
                        \"metadata\":{}
                    }";

        $examity_course = self::post_api($url, 'create', $postdata, $headers) ?? null;


        return $examity_course;
    }

    /**
     * Create examity exam.
     *
     * @param object $url - url for the curl request.
     * @param object $moodle_user - moodle user.
     * @param object $moodle_course - moodle course.
     * @param object $moodle_exam - moodle exam.
     * @param array $headers set token in header.
     * @return object
     */
    public static function create_examity_exam($url, $moodle_user, $moodle_course, $moodle_exam, $headers) {



        $url = $url->value . '/exams';
        // $examity_exam     = null;

        $course_id        = 4;
        $duration         = 10;
        $exam_end_date    ='2021-05-24T21:39:48.586Z'; 
        $rule_id          = 0;
        $rule_description = null;
        $for_student      = true;
        $for_proctor      = true;
        $display_order    = 0;
        $exam_level_id    = 2;
        $exam_name        = 'test_examity';
        $exam_start_date  = '2021-05-24T21:39:48.586Z';
        $exam_url         = 'https://test.examity.com/onlineexam';
        $status_id        = 1;
        $allowed_attempts = 0;
        $exam_code        = 'string';
        $exam_password    = 'password';
        $exam_username    = 'username';
        $is_student_upload_file = true;
        $userId = null;
        $testtakerUrl = null;
        $proctorUrl = null;
        $password = null;
        $duration = null;

        $postdata = "{
                        \"course_id\":$course_id,
                        \"duration\":25,
                        \"exam_end_date\":\"$exam_end_date\",
                        \"exam_instructions\":[
                        ],
                        \"exam_level_id\":$exam_level_id,
                        \"exam_name\":\"$exam_name\",
                        \"exam_start_date\":\"$exam_start_date\",
                        \"exam_url\":\"$exam_url\",
                        \"status_id\":$status_id,
                        \"allowed_attempts\":$allowed_attempts,
                        \"exam_code\":\"$exam_code\",
                        \"exam_password\":\"$exam_password\",
                        \"exam_username\":\"$exam_username\",
                        \"is_student_upload_file\":$is_student_upload_file,
                        \"metadata\": null,
                        \"unique_exam_urls\":[
                        ]
                    }";

        $examity_exam = self::post_api($url, 'create', $postdata, $headers);

        return $examity_exam;
    }

    // /**
    //  * update examity user.
    //  *
    //  * @param object $examity_user moodle user id.
    //  * @param array $headers set token in header.
    //  * @return object
    //  */
    // public function update_examity_user($examity_user, $headers) {

    //     $examity_user = null;

    //     return $examity_user;
    // }

    /**
     * update examity course.
     *
     * @param int $examity_user moodle user id.
     * @param object $moodle_course moodle course.
     * @param array $headers set token in header.
     * @return object
     */
    public static function update_examity_course($url, $moodle_user, $moodle_course, $headers) {

        $examity_course = null;

        $url = $url->value . '/courses';
        $course_id        = 4;
        $duration         = 10;
        $exam_end_date    ='2021-05-24T21:39:48.586Z'; 
        $rule_id          = 0;
        $rule_description = null;
        $for_student      = true;
        $for_proctor      = true;
        $display_order    = 0;
        $exam_level_id    = 2;
        $exam_name        = 'test_examity';
        $exam_start_date  = '2021-05-24T21:39:48.586Z';
        $exam_url         = 'https://test.examity.com/onlineexam';
        $status_id        = 1;
        $allowed_attempts = 0;
        $exam_code        = 'string';
        $exam_password    = 'password';
        $exam_username    = 'username';
        $is_student_upload_file = true;
        $userId = null;
        $testtakerUrl = null;
        $proctorUrl = null;
        $password = null;
        $duration = null;

        $postdata = "{
                        \"course_id\":$course_id,
                        \"duration\":25,
                        \"exam_end_date\":\"$exam_end_date\",
                        \"exam_instructions\":[
                        ],
                        \"exam_level_id\":$exam_level_id,
                        \"exam_name\":\"$exam_name\",
                        \"exam_start_date\":\"$exam_start_date\",
                        \"exam_url\":\"$exam_url\",
                        \"status_id\":$status_id,
                        \"allowed_attempts\":$allowed_attempts,
                        \"exam_code\":\"$exam_code\",
                        \"exam_password\":\"$exam_password\",
                        \"exam_username\":\"$exam_username\",
                        \"is_student_upload_file\":$is_student_upload_file,
                        \"metadata\": null,
                        \"unique_exam_urls\":[
                        ]
                    }";

        return self::post_api($url, 'update', $postdata, $headers) ?? null; 
    }

    /**
     * update examity exam.
     *
     * @param object $url - url for the curl request.
     * @param object $examity_exam moodle user id.
     * @param object $examity_course examity course.
     * @param array $headers set token in header.
     * @return object
     */
    public static function update_examity_exam($url, $moodle_exam, $moodle_course, $headers) {

        // exam ids 6202, 6203
        $moodle_exam_id = 6202; // TODO: $moodle_exam->id; 
        $url = $url->value . '/exams' . '/' . (int)$moodle_exam_id;
        $examity_exam     = null;

        $course_id        = 1454;//(int)$moodle_exam->course;
        $duration         = 25;//(int)$moodle_exam->timeopen - (int)$moodle_exam->timeclose;
        $exam_end_date    = null; //TODO: $moodle_exam->timeclose;
        $rule_id          = 0;
        $rule_description = null;
        $for_student      = true;
        $for_proctor      = true;
        $display_order    = 0;
        $exam_level_id    = 10;
        $exam_name        = 'test_examity';// $moodle_exam->name;
        $exam_start_date  = '2021-05-01T04:00:00'; //TODO: $moodle_exam->timeopen;
        $exam_url         = 'https://test.examity.com/onlineexam/'; //TODO: get the exam url
        $status_id        = 1;
        $allowed_attempts = 0;
        $exam_code        = null;
        $exam_password    = 'password';// $moodle_exam->password;
        $exam_username    = 'username';
        $is_student_upload_file = true;
        $userId = null;
        $testtakerUrl = null;
        $proctorUrl = null;
        $password = null;
        $duration = null;//0;

        $postdata = "{
                        \"course_id\":$course_id,
                        \"duration\":$duration,
                        \"exam_end_date\":\"$exam_end_date\",
                        \"exam_instructions\":[
                            {
                                \"rule_id\":$rule_id,
                                \"rule_description\":\"$rule_description\",
                                \"for_student\":$for_student,
                                \"for_proctor\":$for_proctor,
                                \"display_order\":$display_order
                            }
                        ],
                        \"exam_level_id\":$exam_level_id,
                        \"exam_name\":\"$exam_name\",
                        \"exam_start_date\":\"$exam_start_date\",
                        \"exam_url\":\"$exam_url\",
                        \"status_id\":$status_id,
                        \"allowed_attempts\":$allowed_attempts,
                        \"exam_code\":\"$exam_code\",
                        \"exam_password\":\"$exam_password\",
                        \"exam_username\":\"$exam_username\",
                        \"is_student_upload_file\":$is_student_upload_file,
                        \"metadata\": null,
                        \"unique_exam_urls\":[
                            {
                                \"userId\":$userId,
                                \"testtakerUrl\":\"$testtakerUrl\",
                                \"proctorUrl\":\"$proctorUrl\",
                                \"password\":\"$password\",
                                \"duration\":$duration
                            }
                        ]
                    }";

        $examity_exam = self::post_api($url, 'update', $postdata, $headers); 

        return $examity_exam;
    }

    // /**
    //  * delete examity user.
    //  *
    //  * @param object $examity_user examity user.
    //  * @param array $headers set token in header.
    //  * @return object
    //  */
    // public function delete_examity_user($examity_user, $headers) {

    //     $examity_exam = null;

    //     return $examity_user;
    // }

    /**
     * delete examity course.
     *
     * @param object $examity_course examity course.
     * @param array $headers set token in header.
     * @return object
     */
    public static function delete_examity_course($url, $moodle_course, $headers) {

        $postdata = "";
        $examity_course_id = (int)$moodle_course->examity_course_id ?? null;
        $url = $url->value . '/courses' . '/' . $examity_course_id;
        $examity_course = self::post_api($url, 'delete', $postdata, $headers);
        
        return $examity_course;
    }

    /**
     * delete examity exam.
     *
     * @param object $examity_exam examity course.
     * @param array $headers set token in header.
     * @return object
     */
    public static function delete_examity_exam($url, $moodle_exam, $headers) {

        $examity_exam = null; 
        $postdata = "";
        $examity_exam_id = 6202;//$moodle_exam->id;
        $url = $url->value . '/exams' . '/' . $examity_exam_id;
        $examity_exam = self::post_api($url, 'delete', $postdata, $headers);
        
        return $examity_exam;
    }

}

