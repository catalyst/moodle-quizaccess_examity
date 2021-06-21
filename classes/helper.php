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
    public static function post_api($url, $crud=null, $postdata=null, $token=null, $fullresponse=false, $timeout=300, $connecttimeout=20, $skipcertverify=false) {
        $options = array();
        $headers['Authorization'] = ' Bearer '. $token;
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

        return $response->results;
    }

    /**
     * Get auth token from examity.
     *
     * 
     */

    /**
     * Get examity user by examity user_id.
     *
     * @param object $url - url for the curl request.
     * @param int $client_id - client id for examity.
     * @param object $username - username for examity auth.
     * @param object $password - password for examity auth.
     * @return string $extoken - get auth token from examity.
     */
    public static function get_examity_token($url, $clientid, $username, $password) {

        $validationdata = "{
            \"client_id\": $clientid,
            \"username\":\"$username\",
            \"password\":\"$password\"
        }";

        $token = self::post_api($url .'/auth', 'create', $validationdata);
        $extoken = json_decode($token, true);

        if(!isset($extoken['access_token'])) {
            $message = get_string('error_auth','quizaccess_examity');
            $messagetype = 'error';
            \core\notification::add($message, $messagetype);
            return false;
        }

        return $extoken['access_token'];
    }

    /**
     * Get examity user by examity user_id.
     *
     * @param object $url - url for the curl request.
     * @param int $examity_user_id - moodle user id.
     * @param array $token - set token in header.
     * @return string $examity_user - get user data stored in examity.
     */
    public static function get_examity_user($url, $examity_user_id, $token) {

        $examity_user = null;
        $examity_user_id = (int)$examity_user_id ?? null;
        $examity_user = self::post_api($url . '/users' . '/' . $examity_user_id, 'read', null, $token);
        $examity_user = json_decode($examity_user_id, true);

        if(!isset($examity_user['user_id'])){

            $message = get_string('error_get_user','quizaccess_examity');
            $messagetype = 'error';
            \core\notification::add($message, $messagetype);
        }

        return $examity_user;
    }

    /**
     * Get examity course by examity_course_id.
     *
     * @param object $url - url for the curl request.
     * @param int $examity_course_id - moodle course.
     * @param array $token set token in header.
     * @return string $examity_course - json of course data.
     */
    public static function get_examity_course($url, $examity_course_id, $token) {

        $postdata = null;
        $examity_course = null;
        $examity_course_id = (int)$examity_course_id ?? null;
        $url = $url . '/courses' . '/' . $examity_course_id;
        $examity_course = self::post_api($url, 'read', $postdata, $token);
        $examity_course = json_decode($examity_course, true);

        if(!isset($examity_course['course_id'])){
            $message = get_string('error_get_course','quizaccess_examity');
            $messagetype = 'error';
            \core\notification::add($message, $messagetype);
        }

        return $examity_course;
    }

    /**
     * Get examity exam by examity_exam_id.
     *
     * @param object $url - url for the curl request.
     * @param int $examity_exam_id - exam id from examity.
     * @param array $token set token in header.
     * @return string $examity_exam - return exam json.
     */
    public static function get_examity_exam($url, $examity_exam_id, $token) {

        $postdata = null;
        $examity_exam = null;
        $url = $url . '/exams' . '/' . $examity_exam_id;
        $examity_exam = self::post_api($url, 'read', $postdata, $token);
        $examity_exam = json_decode($examity_exam, true);

        if(!isset($examity_exam['exam_id'])){
            $message = get_string('error_get_exam','quizaccess_examity');
            $messagetype = 'error';
            \core\notification::add($message, $messagetype);
        }

        return $examity_exam;
    }

    /**
     * Create examity user.
     *
     * @param object $url - url for the curl request.
     * @param object $user - moodle user details.
     * @param array $token examity token.
     * @return string $examity_user - user details created in examity.
     */
    public static function create_examity_user($url, $user, $token) {
        global $DB;

        $country_code = (int)$user->country;
        $timezone_id = (int)$user->timezone;
        $url = $url . '/users';

        $firstname      = $user->firstname;
        $lastname       = $user->lastname;
        $email          = $user->email;
        $picture        = $user->picture;
        $phone2         = $user->phone2;
        $country        = $user->country;
        $timezone       = $user->timezone;

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
                        \"username\":\"$email\",
                        \"send_password_reset_email\":true
        }";

        $examityuser = self::post_api($url, 'create', $postdata, $token);
        $examityuser = json_decode($examityuser, true);

        if(!isset($examity_user['user_id'])){
            $message = get_string('error_create_user','quizaccess_examity');
            $messagetype = 'error';
            \core\notification::add($message, $messagetype);
        } else {
            $data = [
                    'id' => null,
                    'userid' => $user->id,
                    'examity_user_id' => $examityuser['user_id']
                    ];

            $DB->insert_record('quizaccess_examity_u', $data);
            return $examity_user;
        }
    }

    /**
     * Create course in examity based on moodle course being created.
     *
     * @param object $url - url for the curl request.
     * @param int $examityuserid - moodle user.
     * @param object $course - moodle course.
     * @param array $token set token in header.
     * @return string $examitycourse
     */
    public static function create_examity_course($url, $examityuserid, $course, $examitytoken) {
        global $DB;
        $coursecode = self::get_courseidentifier($course);
        $url = $url . '/courses';

        $postdata = "{
                \"course_code\":\"$coursecode\",
                \"course_name\":\"$course->fullname\",
                \"primary_instructor_id\":$examityuserid,
                \"instructor_ids\":[$examityuserid],
                \"status_id\":1,
                \"metadata\":{}
            }";

        $examitycourse = self::post_api($url, 'create', $postdata, $examitytoken);
        $examitycourse = json_decode($examitycourse, true);

        if (!isset($examitycourse['course_id'])) {
            $message = get_string('error_create_course', 'quizaccess_examity');
            $messagetype = 'error';
            \core\notification::add($message, $messagetype);
        } else {
            $data = [
                'id' => null,
                'course' => $course->id,
                'examity_course_id' => $examitycourse['course_id']
            ];

            $DB->insert_record('quizaccess_examity_c', $data);
        }

        return $examitycourse;
    }

    /**
     * Create examity exam based on moodle_user_id and moodle_course_id.
     *
     * @param object $url - url for the curl request.
     * @param int $moodle_user_id - moodle user.
     * @param int $examity_course_id - moodle course.
     * @param int $moodle_exam_id - moodle exam.
     * @param array $token set token in header.
     * @return string $token
     */
    public static function create_examity_exam($url, $moodle_user_id, $examity_course_id, $moodle_exam_id, $token) {
        global $DB, $CFG;
        $postdata = null;
        $url = $url . '/exams';
        $quiz = $DB->get_record('quiz', ['id' => $moodle_exam_id]);

        if(isset($quiz->id)){

            $course_id        = (int)$examity_course_id;
            $duration         = $quiz->timelimit;
            $exam_end_date    = $quiz->timeclose;
            $rule_id          = 0;
            $rule_description = null;
            $for_student      = true;
            $for_proctor      = true;
            $display_order    = 0;
            $exam_level_id    = 2;
            $exam_name        = $quiz->name;
            $exam_start_date  = $quiz->timeopen;
            $exam_url         = $CFG->wwwroot.'/mod/quiz/view.php?id='.$moodle_exam_id.'&useexamity=1';
            $status_id        = 1;
            $allowed_attempts = (int)$quiz->attempts;
            $exam_code        = $quiz->name;
            $exam_password    = $quiz->password;
            $exam_username    = $quiz->name;
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

        $examityexam = self::post_api($url, 'create', $postdata, $token);
        $examityexam = json_decode($examityexam, true);

        if(!isset($examityexam['exam_id'])) {
            $message = get_string('error_create_exam','quizaccess_examity');
            $messagetype = 'error';
            \core\notification::add($message, $messagetype);
            return false;
        } else {
            $data = [
                    'id' => null,
                    'quiz' => $quiz->id,
                    'examity_exam_id' => $examityexam['exam_id']
                    ];

            $DB->insert_record('quizaccess_examity_e', $data);

            $message = get_string('success_create_exam', 'quizaccess_examity');
            $messagetype = 'success';
            \core\notification::add($message, $messagetype);

            return $examityexam;
        }
    }

    /**
     * update examity course based on examity_course_id.
     *
     * @param int $examityuserid examity user id.
     * @param int $examitycourseid moodle course id.
     * @param object $course moodle course.
     * @param array $token set token.
     * @return string $examity_course examity course data.
     */
    public static function update_examity_course($url, $examityuserid, $examitycourseid, $course, $token) {

        $url = $url . '/courses' . '/' . (int)$examitycourseid;
        $coursename = $course->fullname;
        $coursecode = self::get_courseidentifier($course);
        $primaryinstructorid = (int)$examityuserid;
        $instructorid = (int)$examityuserid;

        $postdata = "{
                        \"course_code\":\"$coursecode\",
                        \"course_name\":\"$coursename\",
                        \"primary_instructor_id\":$primaryinstructorid,
                        \"instructor_ids\":[$instructorid],
                        \"status_id\":1,
                        \"metadata\":{}
                    }";

        $examity_course = self::post_api($url, 'update', $postdata, $token);
        $examity_course = json_decode($examity_course, true);

        return $examity_course; 
    }

    /**
     * update examity exam.
     *
     * @param object $url - url for the curl request.
     * @param int $moodle_user_id moodle user id.
     * @param int $moodle_course_id moodle course id.
     * @param int $moodle_exam_id moodle course id.
     * @param int $examity_exam_id examity exam.
     * @param array $token set token in header.
     * @return string $examity_exam - examity exam data.
     */
    public static function update_examity_exam($url, $moodle_user_id, $moodle_course_id, $moodle_exam_id, $examity_exam_id, $token) {
        global $DB, $CFG;
        $quiz = $DB->get_record('quiz', ['id' => $moodle_exam_id]);
        $url = $url . '/exams' . '/' . (int)$examity_exam_id;
        $postdata = null;

        if(isset($quiz->id)){

            $examity_exam = null;
            $course_id        = (int)$quiz->course;
            $duration         = $quiz->timelimit;
            $exam_end_date    = $quiz->timeclose;
            $rule_id          = 0;
            $rule_description = null;
            $for_student      = true;
            $for_proctor      = true;
            $display_order    = 0;
            $exam_level_id    = 2;
            $exam_name        = $quiz->name;
            $exam_start_date  = $quiz->timeopen;
            $exam_url         = $CFG->wwwroot.'/mod/quiz/view.php?id='.$moodle_exam_id.'&useexamity=1';
            $status_id        = 1;
            $allowed_attempts = (int)$quiz->attempts;
            $exam_code        = $quiz->name;
            $exam_password    = $quiz->password;
            $exam_username    = $quiz->name;
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

        $examity_exam = self::post_api($url, 'update', $postdata, $token);
        $examity_exam = json_decode($examity_exam, true);


        $message = get_string('success_update_exam', 'quizaccess_examity');
        $messagetype = 'success';
        \core\notification::add($message, $messagetype);

        return $examity_exam;
    }

    // /**
    //  * delete examity user.
    //  *
    //  * @param object $examity_user examity user.
    //  * @param array $token set token in header.
    //  * @return object
    //  */
    // public function delete_examity_user($examity_user, $token) {

    //     $examity_exam = null;

    //     return $examity_user;
    // }

    /**
     * delete examity course.
     *
     * @param object $url of the examity api.
     * @param object $examity_course_id examity course id.
     * @param array $token set token in header.
     * @return string $token
     */
    public static function delete_examity_course($url, $examity_course_id, $token) {

        $examity_course = null;
        $postdata = "";
        $url = $url . '/courses' . '/' . $examity_course_id;
        $examity_course = self::post_api($url, 'delete', $postdata, $token);
        $examity_course = json_decode($examity_course, true);
        
        return $examity_course;
    }

    /**
     * delete examity exam.
     *
     * @param object $url of the examity api.
     * @param int $examity_exam_id examity exam id.
     * @param array $token set token in header.
     * @return string $examity_exam examity exam data.
     */
    public static function delete_examity_exam($url, $examity_exam_id, $token) {

        $examity_exam = null;
        $postdata = "";
        $url = $url . '/exams' . '/' . $examity_exam_id;
        $examity_exam = self::post_api($url, 'delete', $postdata, $token);
        $examity_exam = json_decode($examity_exam, true);

        return $examity_exam;
    }

    /**
     * examity single sign on.
     *
     * @param string $moodle_course_id moodle course id.
     * @param string $moodle_exam_id examity exam id.
     * @return object $lti - data send to create single sign on link.
     */
    public static function examity_sso($moodle_course_id, $moodle_exam_id) {

                // create a sso link for this quiz
                $lti = new stdClass();
                $lti->id = 3;
                $lti->course = $moodle_course_id;
                $lti->name = 'examity_sso';
                $lti->intro = '';
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

    /**
     * Create custom examity role and assign capablities.
     */
    public static function get_examity_role() {
        global $DB;

        $role = $DB->get_record('role', array("shortname" => 'examity'));
        if (empty($role)) {
            $roleid = create_role('Examity', 'examity', get_string('examityroledescription', 'quizaccess_examity'));
        } else {
            $roleid = $role->id;
        }
        set_role_contextlevels($roleid, array(CONTEXT_SYSTEM));

        $context = \context_system::instance();

        // This is the list of capabilties required by the webservices used by Examity.
        $requiredcapabilities = ['moodle/course:update',
                                 'moodle/course:viewhiddencourses',
                                 'moodle/user:viewdetails',
                                 'moodle/user:viewhiddendetails',
                                 'moodle/course:useremail',
                                 'moodle/user:update',
                                 'moodle/site:accessallgroups',
                                 'mod/quiz:view'];
        foreach ($requiredcapabilities as $capability) {
            assign_capability($capability, CAP_ALLOW, $roleid, $context->id, true);
        }

        accesslib_clear_role_cache($roleid);
    }

    /**
     * Get unique indentifier for course.
     *
     * @param $course
     * @return string
     */
    public static function get_courseidentifier($course) {
        global $CFG;
        // We use the first 6 chars of the siteidentifier to prevent issues when the same examity account is used on multiple sites.
        return (substr($CFG->siteindentifier, 0, 6)."_".$course->id."_".$course->shortname);
    }

    /**
     * Helper function to return config if enabled at site level.
     *
     * @return false|mixed|object|string|null
     * @throws \dml_exception
     */
    public static function get_config() {
        $config = get_config('quizaccess_examity');
        if (!empty($config->examity_manage) && !empty($config->examity_url) && !empty($config->client_username)
            && !empty($config->client_password)) {
            return $config;
        }
        return false;
    }
}

