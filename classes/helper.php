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
 * // This file is part of Moodle - http://moodle.org/
 *
 * Moodle is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Moodle is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
 *
 * Helper class to deal with examity api.
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
 * Class helper
 *
 * @package   quizaccess_examity
 * @copyright 2021 Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper
{

    /**
     * Run curl on examity's bridge api.
     *
     * @param string  $url            The URL for examity bridge.
     * @param string  $crud           read, write, update, delete in examity.
     * @param string  $postdata       data to post to examity.
     * @param string  $token         token details sent here.
     * @return object
     */
    public static function post_api($url,
                                    $crud = null,
                                    $postdata = null,
                                    $token = null) {

        $fullresponse = false;
        $timeout = 300;
        $connecttimeout = 20;
        $skipcertverify = false;

        $options = array();
        $headers['Authorization'] = ' Bearer '. $token;
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

        $curl = new curl();
        $curl->setHeader($headers2);

        $options['CURLOPT_RETURNTRANSFER'] = true;
        $options['CURLOPT_NOBODY'] = false;
        $options['CURLOPT_TIMEOUT'] = $timeout;

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

        $info       = $curl->get_info();
        $errorno   = $curl->get_errno();
        $rawheaders = $curl->get_raw_response();

        if ($errorno) {
            $error = $content;
            if (!$fullresponse) {
                debugging("cURL request for \"$url\" failed with: $error ($error_no)", DEBUG_ALL);
                return false;
            }

            $response = new stdClass();
            if ($errorno == 28) {
                $response->status    = '-100';
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
     * Get examity user by examity user_id.
     *
     * @param object $url        url for the curl request.
     * @param int    $clientid   client id for examity.
     * @param object $username   username for examity auth.
     * @param object $password   password for examity auth.
     * @return string $extoken   get auth token from examity.
     */
    public static function get_examity_token($url, $clientid, $username, $password) {

        $validationdata = "{
            \"client_id\": $clientid,
            \"username\":\"$username\",
            \"password\":\"$password\"
        }";

        $token = self::post_api($url .'/auth', 'create', $validationdata);
        $extoken = json_decode($token, true);

        if (!isset($extoken['access_token'])) {
            $message = get_string('error_auth', 'quizaccess_examity');
            $messagetype = 'error';
            \core\notification::add($message, $messagetype);
            return false;
        }

        return $extoken['access_token'];
    }

    /**
     * Get examity user by examity user_id.
     *
     * @param object $url             url for the curl request.
     * @param int    $examityuserid   moodle user id.
     * @param array  $token           set token in header.
     * @return string $examityuser    get user data stored in examity.
     */
    public static function get_examity_user($url, $examityuserid, $token) {

        $examityuser = null;
        $examityuserid = (int)$examityuserid ?? null;
        $examityuser = self::post_api($url . '/users' . '/' . $examityuserid, 'read', null, $token);
        $examityuser = json_decode($examityuserid, true);

        if (!isset($examityuser['user_id'])) {
            $message = get_string('error_get_user', 'quizaccess_examity');
            $messagetype = 'error';
            \core\notification::add($message, $messagetype);
        }

        return $examityuser;
    }

    /**
     * Get examity course by examity_course_id.
     *
     * @param object $url               url for the curl request.
     * @param int    $examitycourseid   moodle course.
     * @param array  $token             set token in header.
     * @return string $examitycourse    json of course data.
     */
    public static function get_examity_course($url, $examitycourseid, $token) {

        $postdata = null;
        $examitycourse = null;
        $examitycourseid = (int)$examitycourseid ?? null;
        $url = $url . '/courses' . '/' . $examitycourseid;
        $examitycourse = self::post_api($url, 'read', $postdata, $token);
        $examitycourse = json_decode($examitycourse, true);

        if (!isset($examitycourse['course_id'])) {
            $message = get_string('error_get_course', 'quizaccess_examity');
            $messagetype = 'error';
            \core\notification::add($message, $messagetype);
        }

        return $examitycourse;
    }

    /**
     * Get examity exam by examity_exam_id.
     *
     * @param object $url             url for the curl request.
     * @param int    $examityexamid   exam id from examity.
     * @param array  $token           set token in header.
     * @return string $examityexam    return exam json.
     */
    public static function get_examity_exam($url, $examityexamid, $token) {

        $postdata = null;
        $examityexam = null;
        $url = $url . '/exams' . '/' . $examityexamid;
        $examityexam = self::post_api($url, 'read', $postdata, $token);
        $examityexam = json_decode($examityexam, true);

        if (!isset($examityexam['exam_id'])) {
            $message = get_string('error_get_exam', 'quizaccess_examity');
            $messagetype = 'error';
            \core\notification::add($message, $messagetype);
        }

        return $examityexam;
    }

    /**
     * Create examity user.
     *
     * @param object $url             url for the curl request.
     * @param object $user            moodle user details.
     * @param array  $token           examity token.
     * @return boolean $examityuserid created in examity.
     */
    public static function create_examity_user($url, $user, $token) {
        global $DB;

        $countrycode = (int)$user->country;
        $timezoneid = (int)$user->timezone;
        $firstname      = $user->firstname;
        $lastname       = $user->lastname;
        $email          = $user->email;
        $picture        = $user->picture;
        $phone2         = $user->phone2;

        $url = $url . '/users';

        $postdata = ['filter_query' => '{"field":"email", "op":"eq", "value":"'.$email.'"}'];
        $request = self::post_api($url, 'read', $postdata, $token);
        $response = json_decode($request, true);
        if (isset($response['content'][0]['username']) && isset($response['content'][0]['user_id']) &&
            $response['content'][0]['username'] == $email) {
            $examityuserid = $response['content'][0]['user_id'];
        } else {
            $postdata = "{
                        \"first_name\":\"$firstname\",
                        \"last_name\":\"$lastname\",
                        \"email\":\"$email\",
                        \"role_id\":3,
                        \"id_photo\":\"$picture\",
                        \"phone\":\"$phone2\",
                        \"country_code\":$countrycode,
                        \"timezone_id\":$timezoneid,
                        \"metadata\":{},
                        \"username\":\"$email\",
                        \"send_password_reset_email\":true
            }";

            $request = self::post_api($url, 'create', $postdata, $token);
            $response = json_decode($request, true);
            if (isset($response['user_id'])) {
                $examityuserid = $response['user_id'];
            }
        }

        if (!isset($examityuserid)) {
            $message = get_string('error_create_user', 'quizaccess_examity');
            $messagetype = 'error';
            \core\notification::add($message, $messagetype);
            return false;
        } else {
            $data = [
                'id' => null,
                'userid' => $user->id,
                'examity_user_id' => $examityuserid
            ];

            $DB->insert_record('quizaccess_examity_u', $data);
            return $examityuserid;
        }
    }

    /**
     * Create course in examity based on moodle course being created.
     *
     * @param object $url             url for the curl request.
     * @param int    $examityuserid   moodle user.
     * @param object $course          moodle course.
     * @param array  $examitytoken    set token in header.
     * @return string $examitycourse
     */
    public static function create_examity_course($url, $examityuserid, $course, $examitytoken) {
        global $DB;
        $coursecode = self::get_courseidentifier($course);
        $courseurl = $url . '/courses';

        $postdata = ['filter_query' => '{"field":"course_code", "op":"eq", "value":"'.$coursecode.'"}'];
        $request = self::post_api($courseurl, 'read', $postdata, $examitytoken);
        $response = json_decode($request, true);
        if (isset($response['content'][0]['course_id']) && $response['content'][0]['course_code'] == $coursecode) {
            // Course already exists - save the id locally.
            $data = [
                'id' => null,
                'course' => $course->id,
                'examity_course_id' => $response['content'][0]['course_id']
            ];

            $DB->insert_record('quizaccess_examity_c', $data);

            return $response['content'][0]['course_id'];

        } else {
            $postdata = "{
                \"course_code\":\"$coursecode\",
                \"course_name\":\"$course->fullname\",
                \"primary_instructor_id\":$examityuserid,
                \"instructor_ids\":[$examityuserid],
                \"status_id\":1,
                \"metadata\":{}
            }";

            $examitycourse = self::post_api($courseurl, 'create', $postdata, $examitytoken);
            $examitycourse = json_decode($examitycourse, true);

            if (!isset($examitycourse['course_id'])) {
                $message = get_string('error_create_course', 'quizaccess_examity');
                $messagetype = 'error';
                \core\notification::add($message, $messagetype);
                return false;
            } else {
                $data = [
                    'id' => null,
                    'course' => $course->id,
                    'examity_course_id' => $examitycourse['course_id']
                ];

                $DB->insert_record('quizaccess_examity_c', $data);
            }

            return $examitycourse['course_id'];
        }
    }

    /**
     * Create examity exam based on moodle_user_id and moodle_course_id.
     *
     * @param object $url                 url for the curl request.
     * @param int    $examitycourseid     moodle course.
     * @param int    $moodleexamid        moodle exam.
     * @param array  $token               set token in header.
     * @return string $examityexam
     */
    public static function create_examity_exam($url, $examitycourseid, $moodleexamid, $token) {
        global $DB, $CFG;
        $postdata = null;
        $url = $url . '/exams';
        $quiz = $DB->get_record('quiz', ['id' => $moodleexamid]);

        if (isset($quiz->id)) {
            $courseid        = (int)$examitycourseid;
            $duration         = $quiz->timelimit / 60;
            $examenddate    = $quiz->timeclose;
            $ruleid          = 0;
            $ruledescription = null;
            $forstudent      = true;
            $forproctor      = true;
            $displayorder    = 0;
            $examlevelid    = 2;
            $examname        = $quiz->name;
            $examstartdate  = $quiz->timeopen;
            $examurl         = $CFG->wwwroot.'/mod/quiz/view.php?id='.$moodleexamid.'&useexamity=1';
            $statusid        = 1;
            $allowedattempts = (int)$quiz->attempts;
            $examcode        = $quiz->name;
            $exampassword    = $quiz->password;
            $examusername    = $quiz->name;
            $isstudentuploadfile = true;
            $userid = null;
            $testtakerurl = null;
            $proctorurl = null;

            $postdata = "{
                            \"course_id\":$courseid,
                            \"duration\":$duration,
                            \"exam_end_date\":\"$examenddate\",
                            \"exam_instructions\":[
                            ],
                            \"exam_level_id\":$examlevelid,
                            \"exam_name\":\"$examname\",
                            \"exam_start_date\":\"$examstartdate\",
                            \"exam_url\":\"$examurl\",
                            \"status_id\":$statusid,
                            \"allowed_attempts\":$allowedattempts,
                            \"exam_code\":\"$examcode\",
                            \"exam_password\":\"$exampassword\",
                            \"exam_username\":\"$examusername\",
                            \"is_student_upload_file\":$isstudentuploadfile,
                            \"metadata\": null,
                            \"unique_exam_urls\":[
                            ]
			}";
        }

        $examityexam = self::post_api($url, 'create', $postdata, $token);
        $examityexam = json_decode($examityexam, true);

        if (!isset($examityexam['exam_id'])) {
            $message = get_string('error_create_exam', 'quizaccess_examity');
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
     * @param object $url             url for the curl request.
     * @param int    $examityuserid   examity user id.
     * @param int    $examitycourseid moodle course id.
     * @param object $course          moodle course.
     * @param array  $token           set token.
     * @return string $examitycourse  examity course data.
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

        $examitycourse = self::post_api($url, 'update', $postdata, $token);
        $examitycourse = json_decode($examitycourse, true);

        return $examitycourse;
    }

    /**
     * Update examity exam.
     *
     * @param object $url              url for the curl request.
     * @param int    $moodleexamid     moodle course id.
     * @param int    $examityexamid    examity exam.
     * @param array  $token            set token in header.
     * @return string $examityexam     examity exam data.
     */
    public static function update_examity_exam($url, $moodleexamid, $examityexamid, $token) {
        global $DB, $CFG;
        $quiz = $DB->get_record('quiz', ['id' => $moodleexamid]);
        $url = $url . '/exams' . '/' . (int)$examityexamid;
        $postdata = null;

        if (isset($quiz->id)) {
            $examityexam = null;
            $courseid        = (int)$quiz->course;
            $duration         = $quiz->timelimit / 60;
            $examenddate    = $quiz->timeclose;
            $ruleid          = 0;
            $ruledescription = null;
            $forstudent      = true;
            $forproctor      = true;
            $displayorder    = 0;
            $examlevelid    = 2;
            $examname        = $quiz->name;
            $examstartdate  = $quiz->timeopen;
            $examurl         = $CFG->wwwroot.'/mod/quiz/view.php?id='.$moodleexamid.'&useexamity=1';
            $statusid        = 1;
            $allowedattempts = (int)$quiz->attempts;
            $examcode        = $quiz->name;
            $exampassword    = $quiz->password;
            $examusername    = $quiz->name;
            $isstudentuploadfile = true;
            $userid = null;
            $testtakerurl = null;
            $proctorurl = null;

            $postdata = "{
                            \"duration\":$duration,
                            \"exam_end_date\":\"$examenddate\",
                            \"exam_instructions\":[],
                            \"exam_level_id\":$examlevelid,
                            \"exam_name\":\"$examname\",
                            \"exam_start_date\":\"$examstartdate\",
                            \"exam_url\":\"$examurl\",
                            \"status_id\":$statusid,
                            \"allowed_attempts\":$allowedattempts,
                            \"exam_code\":\"$examcode\",
                            \"exam_password\":\"$exampassword\",
                            \"exam_username\":\"$examusername\",
                            \"is_student_upload_file\":$isstudentuploadfile,
                            \"metadata\":{},
                            \"unique_exam_urls\":[
                                ]
                            }";
        }

        $examityexam = self::post_api($url, 'update', $postdata, $token);
        $examityexam = json_decode($examityexam, true);

        $message = get_string('success_update_exam', 'quizaccess_examity');
        $messagetype = 'success';
        \core\notification::add($message, $messagetype);

        return $examityexam;
    }

    /**
     * Delete examity course.
     *
     * @param object $url               of the examity api.
     * @param object $examitycourseid   examity course id.
     * @param array  $token             set token in header.
     * @return string $token
     */
    public static function delete_examity_course($url, $examitycourseid, $token) {

        $examitycourse = null;
        $postdata = "";
        $url = $url . '/courses' . '/' . $examitycourseid;
        $examitycourse = self::post_api($url, 'delete', $postdata, $token);
        $examitycourse = json_decode($examitycourse, true);

        return $examitycourse;
    }

    /**
     * Delete examity exam.
     *
     * @param object $url             of the examity api.
     * @param int    $examityexamid   examity exam id.
     * @param array  $token           set token in header.
     * @return string $examityexam    examity exam data.
     */
    public static function delete_examity_exam($url, $examityexamid, $token) {

        $examityexam = null;
        $postdata = "";
        $url = $url . '/exams' . '/' . $examityexamid;
        $examityexam = self::post_api($url, 'delete', $postdata, $token);
        $examityexam = json_decode($examityexam, true);

        return $examityexam;
    }

    /**
     * Examity single sign on.
     *
     * @param string $courseid moodle course id.
     * @param string $cmid examity exam id.
     * @return object $lti - data send to create single sign on link.
     */
    public static function examity_sso($courseid, $cmid) {

        // Create a sso link for this quiz.
        $lti = new stdClass();
        $lti->id = null;
        $lti->resourcekey = get_config('quizaccess_examity', 'providerkey');
        $lti->password = get_config('quizaccess_examity', 'providersecret');
        $lti->toolurl = get_config('quizaccess_examity', 'ltiurl');
        $lti->intro = '';
        $lti->introformat = 1;
        $lti->cmid = $cmid;
        $lti->course = $courseid;
        $lti->name = 'examity_sso';
        $lti->typeid = '1';
        $lti->instructorchoicesendname = 1;
        $lti->instructorchoicesendemailaddr = 1;
        $lti->instructorchoiceallowroster = 0;
        $lti->instructorchoiceallowsetting = 0;
        $lti->instructorchoiceacceptgrades = 1;
        $lti->launchcontainer = 1;
        $lti->debuglaunch = 0;
        $lti->showtitlelaunch = 1;
        $lti->sendname = LTI_SETTING_ALWAYS;
        $lti->sendemailaddr = LTI_SETTING_ALWAYS;
        $lti->acceptgrades = LTI_SETTING_NEVER;
        $lti->allowroster = LTI_SETTING_NEVER;

        return $lti;
    }

    /**
     * Modified version of lti_get_launch_data from mod_lti.
     *
     * @param  stdClass $instance the external tool activity settings
     * @return array the endpoint URL and parameters (including the signature)
     * @since  Moodle 3.0
     */
    public static function lti_get_launch_data($instance) {
        global $PAGE, $USER, $CFG;

        $typeid = null;

        $typeconfig = (array)$instance;
        // Default the organizationid if not specified.
        if (empty($typeconfig['organizationid'])) {
            $urlparts = parse_url($CFG->wwwroot);

            $typeconfig['organizationid'] = $urlparts['host'];
        }
        $toolproxy = null;
        $key = $instance->resourcekey;
        $secret = $instance->password;

        $endpoint = $instance->toolurl;
        $endpoint = trim($endpoint);

        // If the current request is using SSL and a secure tool URL is specified, use it.
        if (lti_request_is_using_ssl() && !empty($instance->securetoolurl)) {
            $endpoint = trim($instance->securetoolurl);
        }

        $orgid = $typeconfig['organizationid'];

        $course = $PAGE->course;

        $allparams = lti_build_request($instance, $typeconfig, $course, $typeid);
        $requestparams = $allparams;

        $requestparams = array_merge($requestparams, lti_build_standard_request($instance, $orgid, false));

        // Always load in current window.
        $launchcontainer = LTI_LAUNCH_CONTAINER_REPLACE_MOODLE_WINDOW;
        $target = 'frame';
        $requestparams['launch_presentation_document_target'] = $target;

        $returnurlparams = array('course' => $course->id,
            'launch_container' => $launchcontainer,
            'id' => $instance->cmid);

        // Add the return URL. We send the launch container along to help us avoid frames-within-frames when the user returns.
        $url = new \moodle_url('/mod/quiz/view.php', $returnurlparams);
        $returnurl = $url->out(false);

        $requestparams['launch_presentation_return_url'] = $returnurl;

        // Add the parameters configured by the LTI services.
        if ($typeid) {
            $services = lti_get_services();
            foreach ($services as $service) {
                $serviceparameters = $service->get_launch_parameters('basic-lti-launch-request',
                    $course->id, $USER->id , $typeid, $instance->id);
                foreach ($serviceparameters as $paramkey => $paramvalue) {
                    $requestparams['custom_' . $paramkey] = lti_parse_custom_parameter($toolproxy,
                                                            null,
                                                            $requestparams,
                                                            $paramvalue,
                                                            false);
                }
            }
        }

        if ((!empty($key) && !empty($secret))) {
            $parms = lti_sign_parameters($requestparams, $endpoint, 'POST', $key, $secret);

            $endpointurl = new \moodle_url($endpoint);
            $endpointparams = $endpointurl->params();

            // Strip querystring params in endpoint url from $parms to avoid duplication.
            if (!empty($endpointparams) && !empty($parms)) {
                foreach (array_keys($endpointparams) as $paramname) {
                    if (isset($parms[$paramname])) {
                        unset($parms[$paramname]);
                    }
                }
            }

        } else {
            // If no key and secret, do the launch unsigned.
            $returnurlparams['unsigned'] = '1';
            $parms = $requestparams;
        }

        return array($endpoint, $parms);
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
     * @param object $course
     * @return string
     */
    public static function get_courseidentifier($course) {
        // Examity needs the moodle courseid at this stage - in future we may try to make this more unique.
        return $course->id;
    }

    /**
     * Helper function to return config if enabled at site level.
     *
     * @return false|mixed|object|string|null
     * @throws \dml_exception
     */
    public static function get_config() {
        $config = get_config('quizaccess_examity');
        if (!empty($config->examity_manage) && !empty($config->apiurl) && !empty($config->username)
            && !empty($config->password)) {
            return $config;
        }
        return false;
    }
}
