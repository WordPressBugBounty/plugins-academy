<?php

add_filter( 'academy/frontend_dashboard_menu_items', 'academy_quizzes_added_frontend_dashboard_menu', 10, 1 );

// Quizzes Endpoint
add_action( 'academy_frontend_dashboard_quizzes_endpoint', 'academy_quizzes_frontend_dashboard_quizzes_page' );

/*
 * load quiz template for php render
 */
add_action( 'academy/templates/curriculum/quiz_content', 'academy_quizzes_curriculum_quiz_content', 10, 2 );
add_action( 'admin_post_academy_quizzes_start_quiz', 'academy_quizzes_start_quiz' );
add_action( 'admin_post_academy_quizzes_submit_quiz', 'academy_quizzes_submit_quiz' );

