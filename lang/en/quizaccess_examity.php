<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin strings are defined here.
 *
 * @package     quizaccess_examity
 * @category    string
 * @copyright   Copyright: \"2021 Catalyst IT\"
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Examity';
$string['manage'] = 'Enable';
$string['manage_help'] = 'If disabled you will need to configure Examity manually';
$string['select_field'] = 'Require the use of examity';
$string['username'] = "API username";
$string['password'] = "API password";
$string['providerkey']   = "LTI provider key";
$string['providersecret']   = "LTI provider secret";
$string['ltiurl']   = "LTI provider url";
$string['client_id']         = "Client ID";
$string['apiurl']       = "API url";
$string['username_help'] = "Username to access Examity webservices api";
$string['password_help'] = "Password to access Examity webservices api";
$string['providerkey_help']   = "LTI Provider key to allow for users to SSO into examity";
$string['providersecret_help']   = "LTI Provider secret to allow for users to SSO into examity";
$string['ltiurl_help']   = "LTI Provider url to allow for users to SSO into examity";
$string['client_id_help']         = "Enter client ID";
$string['apiurl_help']       = "URl for the examity webservices endpoint.";
$string['error_auth'] = 'Sorry Examity could not authenticate your username and password, check these details in the module configuration page.';
$string['success_create_course'] = 'Success, a course has been created in Examity.';
$string['success_create_exam'] = 'Success, an exam has been created inside of Examity.';
$string['error_create_exam'] = 'Sorry, your exam could not be created in Examity.';
$string['success_update_course'] = 'Success, course details have been updated in Examity.';
$string['success_update_exam'] = 'Success, exam details have been updated in Examity.';
$string['error_update_course'] = 'Sorry, course details could not be updated in Examity.';
$string['error_update_exam'] = 'Sorry, exam details could not be updated in Examity.';
$string['success_delete_exam'] = 'Success, your exam has been deleted from Examity.';
$string['error_delete_exam'] = 'Sorry could not find this exam in Examity.';
$string['error_get_user'] = 'Sorry, we could not find this user in Examity.';
$string['error_get_course'] = 'Sorry, we could not find this course in Examity';
$string['error_get_exam'] = 'Sorry, we could not find this exam in Examity';
$string['error_create_user'] = 'Sorry, we could not create this user in examity';
$string['error_create_course'] = 'Sorry, we could not create this course in Examity';
$string['error_create_course_with_user'] = 'Could not find a suitable user to create this course in Examity';
$string['examityroledescription'] = 'Gives access to examity API functions';
$string['examity_web_services'] = 'Examity web services';
$string['quizpassword_required'] = 'Examity requires a quiz password to be set.';
$string['timeopen_required'] = 'Examity requires a quiz open value to be set.';
$string['timeclose_required'] = 'Examity requires a quiz close time to be set.';
$string['timelimit_required'] = 'Examity requires a quiz time limit to be set.';
$string['disable'] = 'Disable';
$string['enable'] = 'Enable';
$string['logintoexamity'] = 'Click here to login into Examity';
$string['web_services'] = 'Examity web services';
$string['defaultstate'] = 'Default quiz state';
$string['defaultstatehelp'] = 'Determines if examity is preconfigured to enabled within a quiz';
$string['privacy:metadata:quizaccess_examity_u:userid'] = 'The Moodle userid';
$string['privacy:metadata:quizaccess_examity_u:examity_user_id'] = 'The user id which the examity system uses';
$string['privacy:metadata:quizaccess_examity_u'] = 'Stores details about the user';
$string['enablerest'] = 'Enable Rest protocol';
$string['enablerestdescription'] = 'Examity requires the "Rest" protocol be enabled on your site.';
$string['createuserdescription'] = 'A user must be created with the username/email "developers@examity.com"';
$string['checkusercapabilitydescription'] = 'The examity user must be added to the site-wide "examity" role';
$string['configureexamity'] = 'Configure examity settings';
$string['configureexamitydescription'] = 'Examity must be configured correctly with keys and urls.';

