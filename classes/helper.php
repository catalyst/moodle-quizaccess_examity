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

/**
 * Helper class to deal with examity api.
 *
 * @copyright  2021 Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper {

    /**
     * Run curl on examity's bridge api.
     *
     * @param string $url The URL for examity bridge.
     * @param string $crud read, write, update, delete in examity.
     * @param string $postdata data to post to examity.
     * @param string $header token details sent here.
     * @param boolean $fullresponse response from api.
     * @param array $options Display options.
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

    /**
     * Get auth token from examity.
     *
     * @return string
     */
    public static function get_examity_token($url, $username, $password) {

        $validation_data = "{
            \"client_id\":171,
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
     * @param int $moodle_user moodle user id.
     * @param array $headers set token in header.
     * @return object
     */
    public function get_examity_user($moodle_user, $headers) {

        if($moodle_user) {

            // Check for existing instructor from Examity based on course creator ID eg. 107151
            $examity_user_id = (int)$moodle_user->examity_user_id ?? null;
            $primary_instructor_id = self::post_api($url->value .'/users' . '/' . $examity_user_id, 'read', null, $headers);
            $primary_instructor_id_arr = json_decode($primary_instructor_id, true);
            $primary_instructor_id = $primary_instructor_id_arr['user_id'];
            $examity_user = $primary_instructor_id_arr;

        } else {

            $examity_user = null;
        }

        return $examity_user;
    }

    /**
     * get examity course.
     *
     * @param int $moodle_user moodle user id.
     * @param int $moodle_course set token in header.
     * @param array $headers set token in header.
     * @return object
     */
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

        return self::post_api($url, 'create', $postdata, $headers);
    }

    /**
     * get examity exam.
     *
     * @param int $examity_user moodle user id.
     * @param int $moodle_course set token in header.
     * @param array $headers set token in header.
     * @return object
     */
    public function get_examity_exam($examity_user, $moodle_course, $headers){

        $examity_exam = null;
        $examity_exam_id = null;
        //TODO: get exam id

        $url = $url->value . '/exams' . $examity_exam_id;

        $examity_exam = self::post_api($url, 'read', $postdata, $headers);

        return $examity_exam;
    }

    /**
     * Create examity user.
     *
     * @param int $moodle_user moodle user id.
     * @param array $headers set token in header.
     * @return object
     */
    public function create_examity_user ($moodle_user, $headers) {

        $examity_course = null;

        return $examity_user;
    }

    /**
     * Run get examity exam by id.
     *
     * @param int $examity_user moodle user id.
     * @param int $moodle_course moodle course.
     * @param array $headers set token in header.
     * @return object
     */
    public function create_examity_course($examity_course, $examidity_user, $moodle_course, $headers) {

        $examity_course = null;

        return $examity_course;
    }

    /**
     * get examity exam.
     *
     * @param int $moodle_user moodle user id.
     * @param object $examity_course examity course.
     * @param array $headers set token in header.
     * @return object
     */
    public function create_examity_exam($moodle_user, $examity_course, $headers) {

        $examity_exam = null;

        return $examity_exam;
    }

    /**
     * update examity user.
     *
     * @param object $examity_user moodle user id.
     * @param array $headers set token in header.
     * @return object
     */
    public function update_examity_user($examity_user, $headers) {

        $examity_user = null;

        return $examity_user;
    }

    /**
     * update examity course.
     *
     * @param int $examity_user moodle user id.
     * @param object $moodle_course moodle course.
     * @param array $headers set token in header.
     * @return object
     */
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

        return self::post_api($url, 'update', $postdata, $headers) ?? null; 
    }

    /**
     * update examity exam.
     *
     * @param object $examity_exam moodle user id.
     * @param object $examity_course examity course.
     * @param array $headers set token in header.
     * @return object
     */
    public function update_examity_exam($examity_exam, $examity_course, $headers) {

        $examity_exam = null;

        return $examity_exam;
    }

    /**
     * delete examity user.
     *
     * @param object $examity_user examity user.
     * @param array $headers set token in header.
     * @return object
     */
    public function delete_examity_user($examity_user, $headers) {

        $examity_exam = null;

        return $examity_user;
    }

    /**
     * delete examity course.
     *
     * @param object $examity_course examity course.
     * @param array $headers set token in header.
     * @return object
     */
    public function delete_examity_course($examity_course, $headers) {
        $examity_course = null;

        $url = $url->value . '/courses' . '/' . $event->courseid;
        $examity_course = self::post_api($url, 'delete', $postdata);
        
        return $examity_course;
    }

    /**
     * delete examity exam.
     *
     * @param object $examity_exam examity course.
     * @param array $headers set token in header.
     * @return object
     */
    public function delete_examity_exam($examity_exam, $headers) {

        $examity_exam = null;

        return $examity_exam;
    }

}

