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

        // Authenticate with Examity 
        $username = $DB->get_record('config_plugins', ['plugin' => 'quizaccess_examity', 'name' => 'username'], 'value');
        $password = $DB->get_record('config_plugins', ['plugin' => 'quizaccess_examity', 'name' => 'consumer_secret'], 'value');
        $url      = $DB->get_record('config_plugins', ['plugin' => 'quizaccess_examity', 'name' => 'url'], 'value');
        $userid = $event->userid;

        

        $validation_data = "{
                                \"client_id\":171,
                                \"username\":\"$username->value\",
                                \"password\":\"$password->value\"
                            }";

        // Connect to examity auth
        $token = self::postAPI($url->value .'/auth', 'auth', $validation_data);
        $token_arr = json_decode($token, true);
        $headers['Authorization'] = ' Bearer '. $token_arr["access_token"];


        // Get instructor from Examity eg. 107151
        $primary_instructor_id = self::postAPI($url->value .'/users' . '/' . $userid, 'get_user', null, $headers);
        $primary_instructor_id_arr = json_decode($primary_instructor_id, true);

        // Instructor exists in examity already or create instructor
        if(isset($primary_instructor_id_arr['user_id'])){
            $primary_instructor_id = $primary_instructor_id_arr['user_id'];
        } else {

            $first_name = $USER->firstname;
            $last_name = $USER->lastname;
            $email = $USER->email;
            $role_id = 3;
            $id_photo = ''; 
            $phone = $USER->phone1;  
            $country_code = (int)$USER->country;  
            $timezone_id = (int)$USER->timezone;  
            $metadata = '';  
            $username =  $USER->username;  
            $send_password_reset_email = true;  

            $postdata = "{
                            \"first_name\":\"$first_name\",
                            \"last_name\":\"$last_name\",
                            \"email\":\"$email\",
                            \"role_id\":3,
                            \"id_photo\":\"$id_photo\",
                            \"phone\":\"$phone\",
                            \"country_code\":$country_code,
                            \"timezone_id\":$timezone_id,
                            \"metadata\":{},
                            \"username\":\"$username\",
                            \"send_password_reset_email\":$send_password_reset_email
                        }";

            $user = self::postAPI($url->value . '/users', 'create_user', $postdata, $headers);
            $primary_instructor_id = isset($user['user_id']);
        }



        // create, update, delete course 
        switch ($event->eventname) {
            case '\core\event\course_module_created':

                    var_dump($COURSE);die;

                    $url = $url->value . '/courses';
                    $course_code = $COURSE->id;
                    $course_name = 'test course name';
                    $primary_instructor_id = 107448;
                    $instructor_ids = '107448'; 
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

                    var_dump(self::postAPI($url, $event->eventname, $postdata, $headers));die;


                break;
            case '\core\event\course_module_updated':

                    $url = $url->value . '/courses';
                    $course_code = '171';
                    $course_name = 'test course name';
                    $primary_instructor_id = 0;
                    $instructor_ids = '0,1'; 
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

                    // $postdata = "{\"course_code\":\"$course_code\",\"course_name\":\"$course_name\",\"primary_instructor_id\":$instructor_ids,\"instructor_ids\":[$instructor_ids],\"status_id\":$instructor_ids,\"metadata\":{}}";

                    self::postAPI($url, $event->eventname, $postdata, $headers);

                break;
            case '\core\event\course_module_deleted':
                $url = $url->value . '/courses' . '/' . $event->courseid;
                self::postAPI($url, $event, $postdata);
                break;
            default:
                return;
        }
    }

    //
    // Run curl on examity's bridge api 
    //
    public static function postAPI($url, $event=null, $postdata=null, $headers=null, $fullresponse=false, $timeout=300, $connecttimeout=20, $skipcertverify=false) {

        global $CFG;
        $options = array();

        // Only http and https links supported.
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

        switch ($event) {
            case '\core\event\course_module_created':
                    $content = $curl->post($url, $postdata, $options);
                break;
            case '\core\event\course_module_updated':
                    $content = $curl->put($url, $postdata, $options);
                break;
            case '\core\event\course_module_deleted':
                    $content = $curl->delete($url, $postdata, $options);
                break;
            case 'auth':
                    $content = $curl->post($url, $postdata, $options);
                break;
            case 'get_user':
                    $content = $curl->get($url, $postdata, $options);
            case 'create_user':
                    $content = $curl->post($url, $postdata, $options);
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
    
}