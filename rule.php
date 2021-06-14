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
 * Implementaton of the quizaccess_examity.
 *
 * @package    quizaccess
 * @subpackage examity
 * @copyright  2021 Catalyst 
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/quiz/accessrule/accessrulebase.php');


/**
 * A rule implementing examity sso 
 *
 * @copyright  2021 Ant
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quizaccess_examity extends quiz_access_rule_base {

    /**
     * Return an appropriately configured instance of this rule, if it is applicable
     * to the given quiz, otherwise return null.
     * @param quiz $quizobj information about the quiz in question.
     * @param int $timenow the time that should be considered as 'now'.
     * @param bool $canignoretimelimits whether the current user is exempt from
     *      time limits by the mod/quiz:ignoretimelimits capability.
     * @return quiz_access_rule_base|null the rule, if applicable, else null.
     */
    public static function make(quiz $quizobj, $timenow, $canignoretimelimits) {
        return new self($quizobj, $timenow);
    }

    /**
     * Whether or not a user should be allowed to start a new attempt at this quiz now.
     * @param int $numattempts the number of previous attempts this user has made.
     * @param object $lastattempt information about the user's last completed attempt.
     * @return string false if access should be allowed, a message explaining the
     *      reason if access should be prevented.
     */
    public function prevent_new_attempt($numprevattempts, $lastattempt) {
        return false;
    }

    /**
     * Whether the user should be blocked from starting a new attempt or continuing
     * an attempt now.
     * @return string false if access should be allowed, a message explaining the
     *      reason if access should be prevented.
     */
    public function prevent_access() {

        global $PAGE;
        global $CFG;
        global $DB;

        $moodle_exam_id = (int)$PAGE->cm->instance;
        $sso_url = false;
        $root_url = $CFG->wwwroot;
        $examity_exam_id = $DB->get_record('quizaccess_examity_exam', ['quiz' => $moodle_exam_id]);

        if($examity_exam_id) {
            $sso_url = '<a href="'. $root_url .'/mod/quiz/accessrule/examity/launch.php?moodle_exam_id='.$moodle_exam_id.'"><span>Click here to login into Examity</span></a>';
        }

        return $sso_url;
    }

}
