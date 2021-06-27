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
 * List of extra configuration steps required by Examity.
 *
 * @package quizaccess_examity
 * @author Ant
 * @copyright 2021
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . "/../../../../config.php");
require_login();

$context = context_system::instance();
$PAGE->set_url($CFG->wwwroot.'/mod/quiz/accessrule/examity_default.php');
$PAGE->set_context($context);
$PAGE->set_title($SITE->fullname);
$PAGE->set_heading($SITE->fullname);
$PAGE->navbar->add(get_string('examity_web_services', 'quizaccess_examity'));


echo $OUTPUT->header();

$return = "";
$brtag = html_writer::empty_tag('br');

$table = new html_table();
$table->head = array(get_string('step', 'webservice'), get_string('status'),
    get_string('description'));
$table->colclasses = array('leftalign step', 'leftalign status', 'leftalign description');
$table->id = 'onesystemcontrol';
$table->attributes['class'] = 'admintable wsoverview generaltable';
$table->data = array();

$return .= $brtag . get_string('onesystemcontrollingdescription', 'webservice')
        . $brtag . $brtag;

// Enable Web Services.
$row = array();
$url = new moodle_url("/admin/search.php?query=enablewebservices");
$row[0] = "1. " . html_writer::tag('a', get_string('enablews', 'webservice'),
                array('href' => $url));
$status = html_writer::tag('span', get_string('no'), array('class' => 'badge badge-danger'));
if ($CFG->enablewebservices) {
    $status = get_string('yes');
}
$row[1] = $status;
$row[2] = get_string('enablewsdescription', 'webservice');
$table->data[] = $row;

// Enable protocols.
$row = array();
$url = new moodle_url("/admin/settings.php?section=webserviceprotocols");
$row[0] = "2. " . html_writer::tag('a', get_string('enablerest', 'quizaccess_examity'),
                array('href' => $url));
$status = html_writer::tag('span', get_string('none'), array('class' => 'badge badge-danger'));
// Retrieve activated protocol.
$activeprotocols = empty($CFG->webserviceprotocols) ?
        array() : explode(',', $CFG->webserviceprotocols);
if (!empty($activeprotocols)) {
    foreach ($activeprotocols as $protocol) {
        if ($protocol == 'rest') {
            $status = get_string('yes');
        }

    }
}
$row[1] = $status;
$row[2] = get_string('enablerestdescription', 'quizaccess_examity');
$table->data[] = $row;

// Create user account.
$row = array();
$url = new moodle_url("/user/editadvanced.php", ['id' => -1]);
$row[0] = "3. " . html_writer::tag('a', get_string('createuser', 'webservice'),
                array('href' => $url));

$userstatus = get_string('no');
$rolestatus = get_string('no');

$examityuser = $DB->record_exists('user', ['deleted' => 0, 'username' => 'developers@examity.com']);
$role = $DB->get_record('role', ['shortname' => 'examity']);
if (!empty($examityuser)) {
    $userstatus = get_string('yes');

    if (!empty($role) && user_has_role_assignment($examityuser->id, $role->id, SYSCONTEXTID)) {
        $rolestatus = get_string('yes');
    }
}
$row[1] = $userstatus;
$row[2] = get_string('createuserdescription', 'quizaccess_examity');
$table->data[] = $row;

// Add capability to users.
$row = array();
$url = new moodle_url("/admin/roles/assign.php", ['contextid' => SYSCONTEXTID, 'roleid' => $role->id]);
$row[0] = "4. " . html_writer::tag('a', get_string('checkusercapability', 'webservice'),
                array('href' => $url));
$row[1] = $rolestatus;
$row[2] = get_string('checkusercapabilitydescription', 'quizaccess_examity');
$table->data[] = $row;

// Create token for the specific user.
$row = array();
$url = new moodle_url("/admin/webservice/tokens.php?sesskey=" . sesskey() . "&action=create");
$row[0] = "5. " . html_writer::tag('a', get_string('createtokenforuser', 'webservice'),
                array('href' => $url));
$row[1] = "";
$row[2] = get_string('createtokenforuserdescription', 'webservice');
$table->data[] = $row;

echo html_writer::table($table);

echo $OUTPUT->footer();
