<?php
namespace AcademyMultiInstructor\Admin;

use Academy\Interfaces\SettingsExtendInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Settings implements SettingsExtendInterface {
	public static function init() {
		$self = new self();
		add_filter( 'academy/admin/settings_default_data', array( $self, 'set_settings_default_data' ) );
	}

	public function set_settings_default_data( $default_settings ) {
		return array_merge($default_settings, array(
			// earning
			'is_enabled_earning' => true,
			'admin_commission_percentage' => 20,
			'instructor_commission_percentage' => 80,
			'is_enabled_fee_deduction' => true,
			'fee_deduction_name' => '',
			'fee_deduction_amount' => '',
			'fee_deduction_type' => 'percent',
			// Withdrawal
			'instructor_minimum_withdraw_amount' => 80,
			'is_enabled_instructor_paypal_withdraw' => true,
			'is_enabled_instructor_echeck_withdraw' => false,
			'is_enabled_instructor_bank_withdraw' => false,
			'instructor_bank_withdraw_instruction' => '',
		));
	}
}
