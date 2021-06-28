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
 * Contains class quizaccess_examity
 *
 * @package   quizaccess_examity
 * @copyright 2021 Ant
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/mod/quiz/backup/moodle2/restore_mod_quiz_access_subplugin.class.php');

/**
 * Class restore_quizaccess_examity_plugin
 *
 * @package   quizaccess_examity
 * @copyright 2021 Ant
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_quizaccess_examity_subplugin extends restore_mod_quiz_access_subplugin {

    /**
     * Returns the paths to be handled by the plugin at module level.
     */
    protected function define_quiz_subplugin_structure() {
        $paths = array();

        // Add own format stuff.
        $elename = 'examitycourse';
        $elepath = $this->get_pathfor('examity_courses/examity_course'); // We used get_recommended_name() so this works.
        $paths[] = new restore_path_element($elename, $elepath);

        $elename = 'examityquiz';
        $elepath = $this->get_pathfor('examity_quizes/examity_quiz'); // We used get_recommended_name() so this works.
        $paths[] = new restore_path_element($elename, $elepath);

        return $paths;

    }

    /**
     * Process the examity course data.
     * @param stdClass $data
     */
    public function process_examitycourse($data) {
        global $DB;

        $data = (object)$data;
        if (empty($this->task->get_moduleid())) {
            return;
        }

        $DB->insert_record('quizaccess_examity_c', $data);
    }

    /**
     * Process the examity quiz data.
     * @param stdClass $data
     */
    public function process_examityquiz($data) {
        global $DB;

        $data = (object)$data;

        $DB->insert_record('quizaccess_examity_e', $data);
    }

}