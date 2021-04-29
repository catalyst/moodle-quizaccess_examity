<?php
 
 $functions = array(
    'quizaccess_examity_get_contents' => array(
        'classname'    => 'quizaccess_examity_external',
        'methodname'   => 'get_course_contents_parameters',
        'classpath'    => 'mod/quiz/accessrule/examity/externallib.php',
        'description'  => 'Get course contents.',
        'type'         => 'read',
    ),
    'quizaccess_examity_get_enrolled_users' => array(
        'classname'    => 'quizaccess_examity_external',
        'methodname'   => 'get_enrolled_user',
        'classpath'    => 'mod/quiz/accessrule/examity/externallib.php',
        'description'  => 'Get enrolled users.',
        'type'         => 'read',
    ),
    'quizaccess_examity_get_quizzes_by_course' => array(
        'classname'    => 'quizaccess_examity_external',
        'methodname'   => 'get_quiz_by_course',
        'classpath'    => 'mod/quiz/access/examity/externallib.php',
        'description'  => 'Get quiz by course.',
        'type'         => 'read',
    )
);

// We define the services to install as pre-build services. A pre-build service is not editable by administrator.
$services = array(
    'Examity' => array(
        'functions' => array(
            'quizaccess_examity_get_contents',
            'quizaccess_examity_get_enrolled_users',
            'quizaccess_examity_get_quizzes_by_course'
        ),
        'restrictedusers' => 0,
        'enabled' => 1,
        'shortname' => 'quizaccess_examity'
    )
);
