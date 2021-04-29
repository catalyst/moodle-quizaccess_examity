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
 * lib.php - Contains Quiz Access Examity plugin specific functions.
 *
 * @since 2.0
 * @package    quizaccess_plugin_examity
 * @subpackage examity
 * @author     Ant 
 * @copyright  2021
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}


// Get global class.
require_once($CFG->dirroot.'/plagiarism/lib.php');
require_once($CFG->dirroot . '/lib/filelib.php');

/**
 * Add ability for the teacher to enable examity within a quiz.
 * Allows a plugin to inject elements into a coursemodule editing form. 
 *
 * @param stdClass $data
 * @param stdClass $course
 */
        
function quizaccess_examity_coursemodule_standard_elements($formwrapper, $mform) {
    
    global $DB;

    $modulename = $formwrapper->get_current()->modulename;

    if ($modulename == 'quiz') {

        $attributes = array(0 => 'Enable', 1 => 'Disable');
        $mform->addElement('header', 'examity', 'Examity');
        $mform->addElement('select', 'type', get_string('select_field', 'quizaccess_examity'), $attributes);
        $mform->setDefault('type', 1);
    }
}

/**
 * Add a hook to "save" the Examity select to custom table.
 *
 * @param stdClass $data
 * @param stdClass $course
 */
function quizaccess_examity_coursemodule_edit_post_actions($data, $course) {

    global $DB;

    foreach($data as $key => $value) {

        if($key == 'type' && $value == "0") {

            $newelement = new stdClass();
            $newelement->id = $data->id;
            $newelement->cm = $data->coursemodule;
            $newelement->name = $key;
            $newelement->value = $value;

            $DB->insert_record('quizaccess_examity_config', $newelement);

        }
    }
}

/**
 * Validate the data in the new field when the form is submitted
 *
 * @param stdClass $data
 * @return array $errors
 */
function quizaccess_examity_coursemodule_validation($data, $files) {

    $errors = array();

    // Check the quiz and make sure the password is not empty
    foreach($files as $key => $value) {

        if($key == 'quizpassword' && $value == ""){
            $errors['password'] = 'no password';
        }
    }

    //TODO: throw validation message and return if no password has been set

}

