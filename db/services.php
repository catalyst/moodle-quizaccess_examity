<?php

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