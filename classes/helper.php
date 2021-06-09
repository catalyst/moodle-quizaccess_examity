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
 * select
 * insert
 * delete

 * @package    quizaccess_examity
 * @author     Ant
 * @copyright  2021 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quizaccess_examity;
use curl;
use stdClass;

global $CFG, $DB;

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

        $validation_data = "{
            \"client_id\": $client_id->value,
            \"username\":\"$username->value\",
            \"password\":\"$password->value\"
        }";
        $token = self::post_api($url->value .'/auth', 'create', $validation_data);
        $examity_token = json_decode($token, true);

        if(!isset($examity_token['access_token'])){
            $message = 'Examity could not authenticate your email and password, please check the your Examity configuration details';
            $messagetype = 'error';
            \core\notification::add($message, $messagetype);
        }

        return $examity_token;
    }

    /**
     * Get examity user by examity user_id.
     *
     * @param object $url - url for the curl request.
     * @param object $examity_user_id - moodle user id.
     * @param array $headers - set token in header.
     * @return string $examity_user - get user data stored in examity.
     */
    public static function get_examity_user($url, $examity_user_id, $headers) {

        $examity_user = null;
        $examity_user_id = (int)$examity_user_id ?? null;
        $examity_user = self::post_api($url->value . '/users' . '/' . $examity_user_id, 'read', null, $headers);
        $examity_user = json_decode($examity_user_id, true);

        if(!isset($examity_user['user_id'])){
            $message = 'Sorry, we could not find this user in Examity';
            $messagetype = 'error';
            \core\notification::add($message, $messagetype);
        }

        return $examity_user;
    }

    /**
     * Get examity course by examity_course_id.
     *
     * @param object $url - url for the curl request.
     * @param object $examity_course_id - moodle course.
     * @param array $headers set token in header.
     * @return string $examity_course - json of course data.
     */
    public static function get_examity_course($url, $examity_course_id, $headers) {

        $postdata = null;
        $examity_course = null;
        $examity_course_id = (int)$examity_course_id ?? null;
        $url = $url->value . '/courses' . '/' . $examity_course_id;
        $examity_course = self::post_api($url, 'read', $postdata, $headers);
        $examity_course = json_decode($examity_course, true);

        if(!isset($examity_course['course_id'])){
            $message = 'Sorry, we could not find this course in Examity';
            $messagetype = 'error';
            \core\notification::add($message, $messagetype);
        }

        return $examity_course;
    }

    /**
     * Get examity exam by examity_exam_id.
     *
     * @param object $url - url for the curl request.
     * @param object $examity_exam_id - exam id from examity.
     * @param array $headers set token in header.
     * @return string $examity_exam - return exam json.
     */
    public static function get_examity_exam($url, $examity_exam_id, $headers) {

        $postdata = null;
        $examity_exam = null;
        $url = $url->value . '/exams' . '/' . $examity_exam_id;
        $examity_exam = self::post_api($url, 'read', $postdata, $headers);
        $examity_exam = json_decode($examity_exam, true);

        if(!isset($examity_exam['exam_id'])){
            $message = 'Sorry, we could not find this exam in Examity';
            $messagetype = 'error';
            \core\notification::add($message, $messagetype);
        }

        return $examity_exam;
    }

    /**
     * Create examity user.
     *
     * @param object $url - url for the curl request.
     * @param object $USER - moodle user details.
     * @param array $headers set token in header.
     * @return string $examity_user - user details created in examity.
     */
    public static function create_examity_user($url, $USER, $headers) {

        $examity_user = null;
        $country_code = (int)$USER->country;
        $timezone_id = (int)$USER->timezone;
        $url = $url->value . '/users';

        $firstname      = $USER->firstname;
        $lastname       = $USER->lastname;
        $email          = $USER->email;
        $picture        = $USER->picture;
        $phone2         = $USER->phone2;
        $country        = $USER->country;
        $timezone       = $USER->timezone;
        $username       = $USER->username;

        $postdata = "{
                        \"first_name\":\"$firstname\",
                        \"last_name\":\"$lastname\",
                        \"email\":\"$email\",
                        \"role_id\":3,
                        \"id_photo\":\"$picture\",
                        \"phone\":\"$phone2\",
                        \"country_code\":$country_code,
                        \"timezone_id\":$timezone_id,
                        \"metadata\":{},
                        \"username\":\"$username\",
                        \"send_password_reset_email\":true
        }";

        $examity_user = self::post_api($url, 'create', $postdata, $headers);
        $examity_user = json_decode($examity_user, true);

        if(!isset($examity_user['user_id'])){
            $message = 'Sorry, we could not create this user in examity';
            $messagetype = 'error';
            \core\notification::add($message, $messagetype);
        }

        return $examity_user;
    }

    /**
     * Create course in examity based on moodle course being created.
     *
     * @param object $url - url for the curl request.
     * @param object $examity_user_id - moodle user.
     * @param object $COURSE - moodle course.
     * @param array $headers set token in header.
     * @return string $examity_course
     */
    public static function create_examity_course($url, $examity_user_id, $COURSE, $headers) {

        $examity_course = null;
        $postdata = null;
        $url = $url->value . '/courses';
        $primary_instructor_id = (int)$examity_user_id;

        if($primary_instructor_id){
            $postdata = "{
                \"course_code\":\"$COURSE->id\",
                \"course_name\":\"$COURSE->fullname\",
                \"primary_instructor_id\":$primary_instructor_id,
                \"instructor_ids\":[$primary_instructor_id],
                \"status_id\":1,
                \"metadata\":{}
            }";

            $examity_course = self::post_api($url, 'create', $postdata, $headers);
            $examity_course = json_decode($examity_course, true);

            if(!isset($examity_course['course_id'])){
                $message = 'Sorry, we could not create this course in Examity';
                $messagetype = 'error';
                \core\notification::add($message, $messagetype);
            }

        } else {

            $message = 'Could not find a suitable user to create this course in Examity';
            $messagetype = 'error';
            \core\notification::add($message, $messagetype);
        }

        return $examity_course;
    }

    /**
     * Create examity exam based on moodle_user_id and moodle_course_id.
     *
     * @param object $url - url for the curl request.
     * @param object $moodle_user - moodle user.
     * @param object $examity_course_id - moodle course.
     * @param object $moodle_exam - moodle exam.
     * @param array $headers set token in header. 
     * @return string $examity_exam
     */
    public static function create_examity_exam($url, $moodle_user_id, $examity_course_id, $moodle_exam_id, $headers) {

        global $USER;
        global $COURSE;
        global $DB;
        global $CFG;
        $postdata = null;
        $examity_exam = null;
        $url = $url->value . '/exams';
        $quiz_record = $DB->get_record('quiz', ['id' => $moodle_exam_id]);

        if(isset($quiz_record->id)){

            $course_id        = (int)$examity_course_id;
            $duration         = $quiz_record->timelimit;
            $exam_end_date    = $quiz_record->timeclose; 
            $rule_id          = 0;
            $rule_description = null;
            $for_student      = true;
            $for_proctor      = true;
            $display_order    = 0;
            $exam_level_id    = 2;
            $exam_name        = $quiz_record->name;
            $exam_start_date  = $quiz_record->timeopen;
            $exam_url         = 'https://examity.com'.'/mod/quiz/view.php?id='.$moodle_exam_id.'&useexamity=1'; // TODO: $CFG->wwwroot should be used here.
            $status_id        = 1;
            $allowed_attempts = (int)$quiz_record->attempts;
            $exam_code        = $quiz_record->name;
            $exam_password    = $quiz_record->password;
            $exam_username    = $quiz_record->name;
            $is_student_upload_file = true;
            $userId = null;
            $testtakerUrl = null;
            $proctorUrl = null;
    
            $postdata = "{
                            \"course_id\":$course_id,
                            \"duration\":$duration,
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

        }

        $examity_exam = self::post_api($url, 'create', $postdata, $headers);
        $examity_exam = json_decode($examity_exam, true);

        if(!isset($examity_exam['exam_id'])){
            $message = 'Sorry, we could not create this exam in Examity';
            $messagetype = 'error';
            \core\notification::add($message, $messagetype);
        }

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
     * update examity course based on examity_course_id.
     *
     * @param int $examity_user moodle user id.
     * @param object $moodle_course moodle course.
     * @param array $headers set token in header.
     * @return string $examity_course examity course data.
     */
    public static function update_examity_course($url, $examity_user_id, $examity_course_id, $examity_exam_id, $COURSE, $headers) {

        $examity_course = null;

        $url = $url->value . '/courses' . '/' . (int)$examity_course_id;
        $course_code = (int)$COURSE->id; 
        $course_name = $COURSE->fullname;
        $primary_instructor_id = (int)$examity_user_id;
        $instructor_ids = (int)$examity_user_id;

        $postdata = "{
                        \"course_code\":\"$course_code\",
                        \"course_name\":\"$course_name\",
                        \"primary_instructor_id\":$primary_instructor_id,
                        \"instructor_ids\":[$instructor_ids],
                        \"status_id\":1,
                        \"metadata\":{}
                    }";

        $examity_course = self::post_api($url, 'update', $postdata, $headers);
        $examity_course = json_decode($examity_course, true);

        return $examity_course; 
    }

    /**
     * update examity exam.
     *
     * @param object $url - url for the curl request.
     * @param int $examity_user_id user id.
     * @param int $examity_course_id examity course.
     * @param int $examity_exam_id examity exam.
     * @param array $headers set token in header.
     * @return string $examity_exam - examity exam data.
     */
    public static function update_examity_exam($url, $moodle_user_id, $moodle_course_id, $moodle_exam_id, $examity_exam_id, $headers) {

        global $DB;
        global $CFG;
        $quiz_record = $DB->get_record('quiz', ['id' => $moodle_exam_id]);
        $url = $url->value . '/exams' . '/' . (int)$examity_exam_id;
        $postdata = null;

        if(isset($quiz_record->id)){

            $examity_exam = null;
            $course_id        = (int)$quiz_record->course;
            $duration         = $quiz_record->timelimit;
            $exam_end_date    = $quiz_record->timeclose; 
            $rule_id          = 0;
            $rule_description = null;
            $for_student      = true;
            $for_proctor      = true;
            $display_order    = 0;
            $exam_level_id    = 2;
            $exam_name        = $quiz_record->name;
            $exam_start_date  = $quiz_record->timeopen;
            $exam_url         = 'https://examity.com'.'/mod/quiz/view.php?id='.$moodle_exam_id.'&useexamity=1'; // TODO: $CFG->wwwroot should be used here.
            $status_id        = 1;
            $allowed_attempts = (int)$quiz_record->attempts;
            $exam_code        = $quiz_record->name;
            $exam_password    = $quiz_record->password;
            $exam_username    = $quiz_record->name;
            $is_student_upload_file = true;
            $userId = null;
            $testtakerUrl = null;
            $proctorUrl = null;

            $postdata = "{
                            \"duration\":$duration,
                            \"exam_end_date\":\"$exam_end_date\",
                            \"exam_instructions\":[],
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
                            \"metadata\":{},
                            \"unique_exam_urls\":[
                                ]
                            }";
        }


        // $postdata = "{
        //                 \"course_id\":$course_id,
        //                 \"duration\":25,
        //                 \"exam_end_date\":\"$exam_end_date\",
        //                 \"exam_instructions\":[
        //                     {
        //                         \"rule_id\":$rule_id,
        //                         \"rule_description\":\"$rule_description\",
        //                         \"for_student\":$for_student,
        //                         \"for_proctor\":$for_proctor,
        //                         \"display_order\":$display_order
        //                     }
        //                 ],
        //                 \"exam_level_id\":$exam_level_id,
        //                 \"exam_name\":\"$exam_name\",
        //                 \"exam_start_date\":\"$exam_start_date\",
        //                 \"exam_url\":\"$exam_url\",
        //                 \"status_id\":$status_id,
        //                 \"allowed_attempts\":$allowed_attempts,
        //                 \"exam_code\":\"$examity_exam_id\",
        //                 \"exam_password\":\"$exam_password\",
        //                 \"exam_username\":\"$exam_username\",
        //                 \"is_student_upload_file\":$is_student_upload_file,
        //                 \"metadata\": null,
        //                 \"unique_exam_urls\":[
        //                     {
        //                         \"userId\":$userId,
        //                         \"testtakerUrl\":\"$testtakerUrl\",
        //                         \"proctorUrl\":\"$proctorUrl\",
        //                         \"password\":\"$password\",
        //                         \"duration\":$duration
        //                     }
        //                 ]
        //             }";

        $examity_exam = self::post_api($url, 'update', $postdata, $headers); 
        $examity_exam = json_decode($examity_exam, true);

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
     * @param object $url of the examity api.
     * @param object $examity_course examity course.
     * @param array $headers set token in header.
     * @return string $examity_course
     */
    public static function delete_examity_course($url, $examity_course_id, $headers) {

        $examity_course = null;
        $postdata = "";
        $url = $url->value . '/courses' . '/' . $examity_course_id;
        $examity_course = self::post_api($url, 'delete', $postdata, $headers);
        $examity_course = json_decode($examity_course, true);
        
        return $examity_course;
    }

    /**
     * delete examity exam.
     *
     * @param object $url of the examity api.
     * @param object $examity_exam_id examity exam id.
     * @param array $headers set token in header.
     * @return string $examity_exam examity exam data.
     */
    public static function delete_examity_exam($url, $examity_exam_id, $headers) {

        $examity_exam = null;
        $postdata = "";
        $url = $url->value . '/exams' . '/' . $examity_exam_id;
        $examity_exam = self::post_api($url, 'delete', $postdata, $headers);
        $examity_exam = json_decode($examity_exam, true);

        return $examity_exam;
    }

    /**
     * select from custom moodle database.
     *
     * @param string $table.
     * @param string $column.
     * @param string $value.
     * @return object $values.
     */
    public static function select($table, $column, $value) {
        
        global $DB;
        $values = null;
        $sql = "$column = $value";
        $values = $DB->get_records_select($table, $sql);

        return $values;
    }

    /**
     * insert into custom moodle database.
     *
     * @param array $data.
     * @param string $db_table.
     * @return object $id.
     */
    public static function insert($data, $db_table) {

        global $DB;
        $id = null;

        $data_object = new stdClass();
        foreach($data as $key => $value) {
            $data_object->$key = optional_param($key, $value, PARAM_INT);
        }

        $id = $DB->insert_record($db_table, $data_object, $returnid=true);
        return $id;
    }
    
    // /**
    //  * update custom moodle database.
    //  *
    //  * @param array $data.
    //  * @param string $db_table.
    //  * @return object $id.
    //  */
    // public static function update($data, $db_table) {

    //     global $DB;
    //     $id = null;

    //     $data_object = new stdClass();
    //     foreach($data as $key => $value) {
    //         $data_object->$key = optional_param($key, $value, PARAM_INT);
    //     }

    //     $id = $DB->update_record($db_table, $data_object, $returnid=true);
    //     return $id;
    // } 

    /**
     * delete record in custom moodle database.
     *
     * @param array $data.
     * @param string $column.
     * @param string $value.
     * @return object $id.
     */
    public static function delete($table, $column, $value) {

        global $DB;
        $id = null;
        $id = $DB->delete_records_select($table, "$column = $value");
        return $id;
    } 

    public static function examity_sso($moodle_course_id, $moodle_exam_id) {

                // create a sso link for this quiz
                $lti = new stdClass();
                $lti->id = 3;
                $lti->course = $moodle_course_id;
                $lti->name = 'examity_sso';
                $lti->intro = 'link for single sign on examity';
                $lti->introformat = '1';
                $lti->timecreated = '';
                $lti->timemodified = '';
                $lti->typeid = '1';
                $lti->toolurl = '';
                $lti->securetoolurl = '';
                $lti->instructorchoicesendname = '1';
                $lti->instructorchoicesendemailaddr = '1';
                $lti->instructorchoiceallowroster = NULL;
                $lti->instructorchoiceallowsetting = NULL;
                $lti->instructorcustomparameters = "";
                $lti->instructorchoiceacceptgrades = "1";
                $lti->grade = "";
                $lti->launchcontainer = "1";
                $lti->resourcekey = "";
                $lti->password = "";
                $lti->debuglaunch = "0";
                $lti->showtitlelaunch = "1";
                $lti->servicesalt = "";
                $lti->icon = "";
                $lti->secureicon = "";
                $lti->cmid = $moodle_exam_id;

                return $lti;
    }

}

