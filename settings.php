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
 * Plugin administration pages are defined here.
 *
 * @package     quizaccess_examity
 * @category    admin
 * @copyright   Copyright: \"2021 Catalyst IT\"
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {

    $settings = new admin_settingpage('quizaccess_examity', new lang_string('pluginname', 'quizaccess_examity'));

    // $ADMIN->add('localplugins', new admin_category('local_examity_settings', new lang_string('pluginname', 'local_examity')));

    // $settingspage = new admin_settingpage('managelocalexamity', new lang_string('manage', 'local_examity'));
 
    if ($ADMIN->fulltree) {

        $settings->add(new admin_setting_configcheckbox('quizaccess_examity/pluginname',
        get_string('manage', 'quizaccess_examity'), get_string('manage_help', 'quizaccess_examity'), 1));

        //  
        $settings->add(new admin_setting_configtext('quizaccess_examity/username',
        get_string('examity_username', 'quizaccess_examity'),
        get_string('client_id_help', 'quizaccess_examity'), '15', PARAM_EMAIL, 30));

        $settings->add(new admin_setting_configpasswordunmask('quizaccess_examity/client_secret',
        get_string('examity_password', 'quizaccess_examity'),
        get_string('client_secret_help', 'quizaccess_examity'), '15', PARAM_INT));


        $settings->add(new admin_setting_configtext('quizaccess_examity/consumer_id',
        get_string('consumer_id', 'quizaccess_examity'),
        get_string('consumer_id_help', 'quizaccess_examity'), '15', PARAM_INT, 30));

        $settings->add(new admin_setting_configpasswordunmask('quizaccess_examity/consumer_secret',
        get_string('consumer_secret', 'quizaccess_examity'),
        get_string('consumer_secret_help', 'quizaccess_examity'), '15', PARAM_INT));
    }

    // $ADMIN->add('localplugins', $settings);
}