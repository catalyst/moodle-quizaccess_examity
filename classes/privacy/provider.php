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
 * Privacy Subsystem implementation for quizaccess_examity
 *
 * @package   quizaccess_examity
 * @author    Ant
 * @copyright 2021 Ant
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace quizaccess_examity\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;
use core_privacy\local\request\{writer, helper, contextlist, approved_contextlist, approved_userlist, userlist};
use context;

/**
 * Privacy subsystem for quizaccess_examity.
 *
 * @copyright 2021 Ant
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {

    /**
     * Return the fields which contain personal data.
     *
     * @param collection $collection a reference to the collection to use to store the metadata.
     * @return collection the updated collection of metadata items.
     */
    public static function get_metadata(collection $collection) : collection {
        $collection->add_database_table(
            'quizaccess_examity_u',
            [
                'userid' => 'privacy:metadata:quizaccess_examity_u:userid',
                'examity_user_id' => 'privacy:metadata:quizaccess_examity_u:examity_user_id',
            ], 'privacy:metadata:quizaccess_examity_u'
        );

        return $collection;
    }
    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid the userid.
     * @return contextlist the list of contexts containing user info for the user.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {
        global $DB;
        $examityuserid = $DB->get_field('quizaccess_examity_u', 'examity_user_id', ['userid' => $userid]);
        $contextlist = new contextlist();

        if ($examityuserid) {
            $contextlist->add_system_context();
        }
        return $contextlist;
    }
    /**
     * Export all examity data for the specified userid and context.
     *
     * @param   int         $userid The user to export.
     * @param   \context    $context The context to export.
     * @param   array       $subcontext The subcontext within the context to export this information to.
     * @param   array       $linkarray The weird and wonderful link array used to display information for a specific item
     */
    public static function export_examity_user_data(int $userid, \context $context, array $subcontext, array $linkarray) {
        global $DB;
        $examityuserid = $DB->get_field('quizaccess_examity_u', 'examity_user_id', ['userid' => $userid]);
        $finaldata = (object) ['quizaccess_examity' => ['examityuserid' => $examityuserid]];
        writer::with_context($context)->export_data([], $finaldata);
    }

    /**
     * Delete all user information for the provided user and context.
     *
     * @param  int      $userid    The user to delete
     * @param  \context $context   The context to refine the deletion.
     */
    public static function delete_examity_for_user(int $userid, $context) {
        global $DB;
        if (!$context instanceof \context_system) {
            return;
        }

        // Delete all records in quizaccess_examity_u table for that user.
        $DB->delete_records('quizaccess_examity_u', ['userid' => $userid]);

    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param   userlist    $userlist   The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!$context instanceof \context_system) {
            return;
        }

        $userlist->add_from_sql('userid', 'select userid from {quizaccess_examity_u}', []);
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist  The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;
        $context = $userlist->get_context();

        if (!$context instanceof \context_system) {
            return;
        }
        $DB->delete_records('quizaccess_examity_u');
    }

    /**
     * Delete data for single user.
     *
     * @param approved_contextlist $contextlist  The approved context and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;
        $userid = $contextlist->get_user()->id;

        $DB->delete_records('quizaccess_examity_u', ['userid' => $userid]);
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param context $context The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(context $context) {
        global $DB;
        if (!$context instanceof \context_system) {
            return;
        }

        $DB->delete_records('quizaccess_examity_u');
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
    }
}
