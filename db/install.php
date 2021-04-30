<?php
/**
 * Post installation procedure to create role 
 */
defined('MOODLE_INTERNAL') || die();

function xmldb_quizaccess_examity_install() {
    global $DB;
    create_role('', 'examity_auth', '', '');
    $rolerecord = $DB->get_record('role', array("shortname" => 'quizaccess_examityuser'), $fields = '*');
    set_role_contextlevels($rolerecord->id, array(CONTEXT_SYSTEM));
    $context = context_system::instance();
    assign_capability('quizzaccessrule/examity:managedata', CAP_ALLOW, $rolerecord->id, $context->id, true);
    $context->mark_dirty();
}