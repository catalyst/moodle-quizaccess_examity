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

require_once($CFG->dirroot.'/plagiarism/lib.php');
require_once($CFG->dirroot . '/lib/filelib.php');

/**
 * add ability for the teacher to enable examity within a quiz.
 * allows a plugin to inject elements into a coursemodule editing form. 
 *
 * @param object $formwrapper
 * @param object $mform
 */
function quizaccess_examity_coursemodule_standard_elements($formwrapper, $mform) {
    
    global $DB;
    $modulename = $formwrapper->get_current()->modulename;

    if ($modulename == 'quiz') {

        $attributes = array(0 => 'Enable', 1 => 'Disable');
        $mform->addElement('header', 'examity', 'Examity');
        $mform->addElement('select', 'examity_enable_disable', get_string('select_field', 'quizaccess_examity'), $attributes);
        $mform->setDefault('examity_enable_disable', 1);
    }
}

/**
 * validate the data in the new field when the form is submitted
 *
 * @param object $data
 * @return array $files
 */
function quizaccess_examity_coursemodule_validation($data, $files) {

    $errors = array();
    global $DB;

    $examity_enabled = $DB->get_record('config_plugins', ['plugin' => 'quizaccess_examity', 'name' => 'examity_manage'], 'value');

    // only validate if examity is switched on and enabled.
    if($examity_enabled->value != false && $examity_enabled->value == "1" && $files['examity_enable_disable'] == "0") {

        foreach($files as $key => $value) {

            //
            // examity needs password
            // 
            if($key == 'quizpassword' && $value == ""){
                $errors['password'] = 'Requires password to be set';
                return $errors;
            }

            //
            // examity needs timeopen
            // 
            if($key == 'timeopen' && $value == "0"){
                $errors['timeopen'] = 'Exam open must be greater than zero';
                return $errors;
            }

            //
            // examity needs timeclosed
            // 
            if($key == 'timeclosed' && $value == "0"){
                $errors['timeclosed'] = 'Exam closed must be greater than zero';
                return $errors;
            }

            //
            // examity needs exam duration
            // 
            if($key == 'timelimit' && $value == "0"){
                $errors['timelimit'] = 'Duration must be greater than zero';
                return $errors;
            }

        }
    }
}
