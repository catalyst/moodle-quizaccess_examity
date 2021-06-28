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
 * Contains class backup_quizaccess_examity_subplugin
 *
 * @package   quizaccess_examity
 * @copyright 2021 Ant
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/mod/quiz/backup/moodle2/backup_mod_quiz_access_subplugin.class.php');

/**
 * Class backup_quizaccess_examity_subplugin
 *
 * @package   quizaccess_examity
 * @copyright 2021 Ant
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_quizaccess_examity_subplugin extends backup_mod_quiz_access_subplugin {
    /**
     * Stores the data related to the examity quiz access rule settings for a particular quiz.
     *
     * @return backup_subplugin_element
     */
    protected function define_quiz_subplugin_structure() {

        // Create XML elements.
        $subplugin = $this->get_subplugin_element();
        $subpluginwrapper = new backup_nested_element($this->get_recommended_name());
        $subplugin->add_child($subpluginwrapper);

        $examitycourses = new backup_nested_element('examity_courses');
        $examitycourse = new backup_nested_element('examity_course', array('id'), array('course', 'examity_course_id'));
        $subpluginwrapper->add_child($examitycourses);
        $examitycourses->add_child($examitycourse);
        $examitycourse->set_source_table('quizaccess_examity_c', array('course' => backup::VAR_COURSEID));

        $examityquizes = new backup_nested_element('examity_quizes');
        $examityquiz = new backup_nested_element('examity_quiz', array('id'), array('quiz', 'examity_quiz_id'));
        $subpluginwrapper->add_child($examityquizes);
        $examityquizes->add_child($examityquiz);
        $examityquiz->set_source_table('quizaccess_examity_e', array('quiz' => backup::VAR_ACTIVITYID));

        return $subplugin;
    }
}
