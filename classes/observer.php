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
 * @copyright  2019 Catalyst IT
 * @author     Ant
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Examity event observer class.
 *
 * Send API requests based on Moodle event
 *
 * @copyright  2019 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quizaccess_examity_observer {

    public static function update(\core\event\base $event) {
        
        global $DB;
        $url = null;
        $postdata  = [];

        // Authenticate with Examity 
        $username = $DB->get_record('config_plugins', ['plugin' => 'quizaccess_examity', 'name' => 'username'], 'value');
        $password = $DB->get_record('config_plugins', ['plugin' => 'quizaccess_examity', 'name' => 'consumer_secret'], 'value');

        $validation_data = "{
                                \"client_id\":171,
                                \"username\":\"$username->value\",
                                \"password\":\"$password->value\"
                            }";

        $postdata['course_id'] = $event->courseid;
        $postdata['cmid']      = $event->objectid;
        $postdata['userid']    = $event->userid;

        $url = 'https://bridge.examity.com/auth';
        var_dump(self::postAPI($url, 'auth', $validation_data));die;

        // create, update, delete course 
        switch ($event->eventname) {
            case '\core\event\course_module_created':
                    $url = 'https://bridge.examity.com/courses';
                    $event = $event->eventname;
                    $postdata = 
                    "{
                        \"course_code\":\"$postdata['course_id']\",
                        \"course_name\":\"$event->courseid\",
                        \"primary_instructor_id\":\"$postdata['userid']\",
                        \"instructor_ids\":[
                            \"$postdata['userid']\",
                        ],
                        \"status_id\": \"$postdata['cmid']\",
                        \"metadata\": {}
                    }";

                    var_dump(self::postAPI($url, $event, $postdata));die;

                break;
            case '\core\event\course_module_updated':
                    $postdata['course_id'] = $event->courseid;
                    $url = 'https://bridge.examity.com/courses/' . $postdata->courseid;
                    $postdata = 
                    "{
                        \"course_code\":\"$postdata['course_id']\",
                        \"course_name\":\"$event->courseid\",
                        \"primary_instructor_id\":\"$postdata['userid']\",
                        \"instructor_ids\":[
                            \"$postdata['userid']\",
                        ],
                        \"status_id\": \"$postdata['cmid']\",
                        \"metadata\": {}
                      }";

                    var_dump(self::postAPI($url, $event, $postdata));die;

                break;
            case '\core\event\course_module_deleted':
                    $url = 'https://bridge.examity.com/courses/' . $postdata->courseid;
                    var_dump(self::postAPI($url, $event, $postdata));die;
                break;
            default:
                return;
        }
    }

    //
    // Run curl on examity's bridge api 
    //
    public static function postAPI($url, $event, $postdata=null, $headers=null, $fullresponse=false, $timeout=300, $connecttimeout=20, $skipcertverify=false) {

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
            default:
                return;
        }
    
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
