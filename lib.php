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
 * @package    quizaccess_examity
 * @author     Ant
 * @copyright  2021 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Add ability for the teacher to enable examity within a quiz
 * allows a plugin to inject elements into a coursemodule editing form.
 *
 * @param object $formwrapper
 * @param object $mform
 */
function quizaccess_examity_coursemodule_standard_elements($formwrapper, $mform) {
    $modulename = $formwrapper->get_current()->modulename;

    if ($modulename == 'quiz') {
        $attributes = array(1 => get_string('enable', 'quizaccess_examity'),
                            0 => get_string('disable', 'quizaccess_examity'));
        $mform->addElement('header', 'examity', 'Examity');
        $mform->addElement('select', 'examity_enable_disable', get_string('select_field', 'quizaccess_examity'), $attributes);
        $mform->setDefault('examity_enable_disable', 1);
    }
}

/**
 * Validate the data in the new field when the form is submitted.
 *
 * @param object $data
 * @return array $errors
 */
function quizaccess_examity_coursemodule_validation($mform) {
    $data = $mform->get_submitted_data();

    $errors = [];
    if (!empty($data->examity_enable_disable)) {
        // If examity is enabled, we also need some other quiz settings to be enabled.
        $requiredvars = ['quizpassword', 'timeopen', 'timeclose', 'timelimit'];
        foreach ($requiredvars as $req) {
            if (empty($data->$req)) {
                $errors[$req] = get_string($req.'_required', 'quizaccess_examity');
            }
        }

    }
    return $errors;
}