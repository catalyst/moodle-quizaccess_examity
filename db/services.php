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

// We define the services to install as pre-build services. A pre-build service is not editable by administrator.
$services = array(
    'Examity' => array(
        'functions' => array(
            'core_course_get_contents',
            'core_enrol_get_enrolled_users',
            'mod_quiz_get_quizzes_by_courses'
        ),
        'restrictedusers' => 0,
        'enabled' => 1,
        'shortname' => 'quizaccess_examity'
    )
);