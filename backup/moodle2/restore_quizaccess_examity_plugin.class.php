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
 * Contains class restore_plagiarism_urkund_plugin
 *
 * @package   quizaccess_examity
 * @copyright 2021 Ant
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Class restore_quizaccess_examity_plugin
 *
 * @package   quizaccess_examity
 * @copyright 2021 Ant
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_quizaccess_examity_plugin extends restore_plagiarism_plugin {
    /**
     * Returns the paths to be handled by the plugin at question level.
     */
    protected function define_course_plugin_structure() {

    }

    /**
     * Process the examity config data.
     * @param stdClass $data
     */
    public function process_examityconfig($data) {

    }

    /**
     * Returns the paths to be handled by the plugin at module level.
     */
    protected function define_module_plugin_structure() {

    }

    /**
     * Process the examity config mod data.
     * @param stdClass $data
     */
    public function process_examityconfigmod($data) {

    }

    /**
     * After restoring the course, make sure the requiresubmission statement setting is correct.
     *
     * @throws dml_exception
     */
    public function after_restore_course() {

    }
}