<?php
 
//  $functions = array(
//     'core_course_get_contents' => array(
//         'classname'    => 'quizaccess_examity_external',
//         'methodname'   => 'get_course_contents',
//         'classpath'    => 'mod/quiz/accessrule/examity/externallib.php',
//         'description'  => 'Get course contents.',
//         'type'         => 'read',
//     ),
//     'core_enrol_get_enrolled_users' => array(
//         'classname'    => 'quizaccess_examity_external',
//         'methodname'   => 'get_enrolled_user',
//         'classpath'    => 'mod/quiz/accessrule/examity/externallib.php',
//         'description'  => 'Get enrolled users.',
//         'type'         => 'read',
//     ),
//     'mod_quiz_get_quizzes_by_course' => array(
//         'classname'    => 'quizaccess_examity_external',
//         'methodname'   => 'get_quiz_by_course',
//         'classpath'    => 'mod/quiz/access/examity/externallib.php',
//         'description'  => 'Get quiz by course.',
//         'type'         => 'read',
//     )
// );

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