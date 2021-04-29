<?php
/**
 * Post installation procedure
 */
defined('MOODLE_INTERNAL') || die();

function xmldb_local_myplugin_install() {
    global $CFG, $DB, $OUTPUT;


    $context = context_system::instance();

    $manager_role = $DB->get_record('role', array('shortname' => 'manager'));
    $mwsync_superadmin_role = $DB->get_record('role', array('shortname' => 'local_myplugin_admin'));

}