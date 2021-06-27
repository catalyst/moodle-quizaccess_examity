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

        $settings->add(new admin_setting_configtext('quizaccess_examity/username',
        get_string('username', 'quizaccess_examity'),
        get_string('username_help', 'quizaccess_examity'), '', PARAM_EMAIL, 30));

        $settings->add(new admin_setting_configpasswordunmask('quizaccess_examity/password',
        get_string('password', 'quizaccess_examity'),
        get_string('password_help', 'quizaccess_examity'), '', PARAM_INT));

        $settings->add(new admin_setting_configtext('quizaccess_examity/client_id',
        get_string('client_id', 'quizaccess_examity'),
        get_string('client_id_help', 'quizaccess_examity'), '', PARAM_INT, 30));

        $settings->add(new admin_setting_configtext('quizaccess_examity/apiurl',
        get_string('apiurl', 'quizaccess_examity'),
        get_string('apiurl_help', 'quizaccess_examity'), '', PARAM_URL));

        $settings->add(new admin_setting_configtext('quizaccess_examity/providerkey',
            get_string('providerkey', 'quizaccess_examity'),
            get_string('providerkey_help', 'quizaccess_examity'), '', PARAM_TEXT));

        $settings->add(new admin_setting_configpasswordunmask('quizaccess_examity/providersecret',
            get_string('providersecret', 'quizaccess_examity'),
            get_string('providersecret_help', 'quizaccess_examity'), '', PARAM_INT));

        $settings->add(new admin_setting_configtext('quizaccess_examity/ltiurl',
            get_string('ltiurl', 'quizaccess_examity'),
            get_string('ltiurl_help', 'quizaccess_examity'), '', PARAM_URL));

        $settings->add(new admin_setting_configselect('quizaccess_examity/defaultstate',
            get_string('defaultstate', 'quizaccess_examity'),
            get_string('defaultstatehelp', 'quizaccess_examity'), '', ['Enabled' , 'Disabled']));
    }
    $ADMIN->add('modsettingsquizcat',
        new admin_externalpage(
            'quizaccess_examity/webservices',
            get_string('web_services', 'quizaccess_examity'),
            new moodle_url('/mod/quiz/accessrule/examity/examity_default.php')
        )
    );
}
