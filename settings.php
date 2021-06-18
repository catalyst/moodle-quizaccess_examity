<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin administration configurations are defined here.
 *
 * @package     quizaccess_examity
 * @category    admin
 * @copyright   Copyright: \"2021 Catalyst IT\"
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {

    $settings = new admin_settingpage('quizaccess_examity', new lang_string('pluginname', 'quizaccess_examity'));
 
    if ($ADMIN->fulltree) {

        $settings->add(new admin_setting_configcheckbox('quizaccess_examity/examity_manage',
        get_string('manage', 'quizaccess_examity'), get_string('manage_help', 'quizaccess_examity'), 1));

        $settings->add(new admin_setting_configtext('quizaccess_examity/consumer_username',
        get_string('consumer_username', 'quizaccess_examity'),
        get_string('consumer_username_help', 'quizaccess_examity'), '', PARAM_EMAIL, 30));

        $settings->add(new admin_setting_configpasswordunmask('quizaccess_examity/consumer_password',
        get_string('consumer_password', 'quizaccess_examity'),
        get_string('consumer_password_help', 'quizaccess_examity'), '', PARAM_INT));

        $settings->add(new admin_setting_configtext('quizaccess_examity/client_username',
        get_string('client_username', 'quizaccess_examity'),
        get_string('client_username_help', 'quizaccess_examity'), '', PARAM_EMAIL, 30));

        $settings->add(new admin_setting_configpasswordunmask('quizaccess_examity/client_password',
        get_string('client_password', 'quizaccess_examity'),
        get_string('client_password_help', 'quizaccess_examity'), '', PARAM_INT));

        $settings->add(new admin_setting_configtext('quizaccess_examity/client_id',
        get_string('client_id', 'quizaccess_examity'),
        get_string('client_id_help', 'quizaccess_examity'), '', PARAM_INT, 30));

        $settings->add(new admin_setting_configtext('quizaccess_examity/examity_url',
        get_string('examity_url', 'quizaccess_examity'),
        get_string('examity_url_help', 'quizaccess_examity'), '', PARAM_TEXT, 30));
    }

    $ADMIN->add(
        'root', 
        new admin_externalpage(
            'local_custom_links', 
            'Examity web services', 
            new moodle_url('/mod/quiz/accessrule/examity/examity_default.php'), 
           'moodle/site:config' 
       )
    );
}