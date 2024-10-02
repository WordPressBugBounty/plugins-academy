<?php
namespace  Academy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Shortcode {

	public static function init() {
		$self = new self();
		$self->dispatch_shortcode();
	}
	public function dispatch_shortcode() {
		new Shortcode\AcademyDashboard();
		new Shortcode\AcademyCourses();
		new Shortcode\AcademyRegistration();
		new Shortcode\AcademyPDF();
		new Shortcode\AcademyLogin();
		new Shortcode\AcademySearch();
		new Shortcode\AcademyCourseFilters();
		new Shortcode\AcademyEnrollForm();
		new Shortcode\AcademyPasswordReset();
		new Shortcode\AcademyCourseCurriculum();
		new Shortcode\AcademyCourseLearnpage();
		new Shortcode\AcademyTabs();
	}
}
