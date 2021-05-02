<?php
/**
 * Post installation procedure to create role 
 */
defined('MOODLE_INTERNAL') || die();

function xmldb_quizaccess_examity_install() {
    global $DB;
    create_role('Examity', 'examity', 'Gives access to examity API functions');
    $rolerecord = $DB->get_record('role', array("shortname" => 'examity'), $fields = '*');
    set_role_contextlevels($rolerecord->id, array(CONTEXT_SYSTEM));
    $context = context_system::instance();
    assign_capability('quizzaccessrule/examity:get_course_contents', CAP_ALLOW, $rolerecord->id, $context->id, true);
    assign_capability('quizzaccessrule/examity:get_enrolled_user', CAP_ALLOW, $rolerecord->id, $context->id, true);
    assign_capability('quizzaccessrule/examity:get_quiz_by_course', CAP_ALLOW, $rolerecord->id, $context->id, true);
    assign_capability('quizzaccessrule/examity:validate_parameters', CAP_ALLOW, $rolerecord->id, $context->id, true);

    $context->mark_dirty();
}
