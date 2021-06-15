<?php
/**
 * Post installation procedure to create role 
 */
defined('MOODLE_INTERNAL') || die();
use quizaccess_examity\helper;


function xmldb_quizaccess_examity_install() {

    helper::get_examity_role();

}