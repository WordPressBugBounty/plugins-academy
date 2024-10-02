<?php
namespace  AcademyMultiInstructor\Post;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Academy\Helper;
use Academy\Classes\Sanitizer;
use Academy\Classes\AbstractPostHandler;

class Settings extends AbstractPostHandler {
	protected $namespace = ACADEMY_PLUGIN_SLUG . '_multi_instructor';
	public function __construct() {
		$this->actions = array(
			'save_frontend_dashboard_withdraw_settings' => array(
				'callback' => array( $this, 'save_frontend_dashboard_withdraw_settings' ),
				'capability' => 'manage_academy_instructor'
			),
			'instructor_earning_withdrawal' => array(
				'callback' => array( $this, 'instructor_earning_withdrawal' ),
				'capability' => 'manage_academy_instructor'
			),
		);
	}

	public function save_frontend_dashboard_withdraw_settings() {
			$payload = Sanitizer::sanitize_payload([
				'withdrawMethodType' => 'string',
				'paypalEmailAddress' => 'string',
				'echeckAddress' => 'string',
				'bankAccountName' => 'string',
				'bankAccountNumber' => 'string',
				'bankName' => 'string',
				'bankIBAN' => 'string',
				'bankSWIFTCode' => 'string',
			], $_POST); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		$user_id = get_current_user_id();
		$withdraw_method_type = ( isset( $payload['withdrawMethodType'] ) ? $payload['withdrawMethodType'] : get_user_meta( $user_id, 'academy_instructor_withdraw_method_type', true ) );
			$paypal_email_address = ( isset( $payload['paypalEmailAddress'] ) ? $payload['paypalEmailAddress'] : get_user_meta( $user_id, 'academy_instructor_withdraw_paypal_email', true ) );
			$check_address = ( isset( $payload['echeckAddress'] ) ? $payload['echeckAddress'] : get_user_meta( $user_id, 'academy_instructor_withdraw_echeck_address', true ) );
			$bank_account_name = ( isset( $payload['bankAccountName'] ) ? $payload['bankAccountName'] : get_user_meta( $user_id, 'academy_instructor_withdraw_bank_acocunt_name', true ) );
			$bank_account_number = ( isset( $payload['bankAccountNumber'] ) ? $payload['bankAccountNumber'] : get_user_meta( $user_id, 'academy_instructor_withdraw_bank_acocunt_number', true ) );
			$bank_name = ( isset( $payload['bankName'] ) ? $payload['bankName'] : get_user_meta( $user_id, 'academy_instructor_withdraw_bank_name', true ) );
			$bank_iban = ( isset( $payload['bankIBAN'] ) ? $payload['bankIBAN'] : get_user_meta( $user_id, 'academy_instructor_withdraw_bank_iban', true ) );
			$bank_SWIFT_code = ( isset( $payload['bankSWIFTCode'] ) ? $payload['bankSWIFTCode'] : get_user_meta( $user_id, 'academy_instructor_withdraw_bank_swiftcode', true ) );

			update_user_meta( $user_id, 'academy_instructor_withdraw_method_type', $withdraw_method_type );
			update_user_meta( $user_id, 'academy_instructor_withdraw_paypal_email', $paypal_email_address );
			update_user_meta( $user_id, 'academy_instructor_withdraw_echeck_address', $check_address );
			update_user_meta( $user_id, 'academy_instructor_withdraw_bank_acocunt_name', $bank_account_name );
			update_user_meta( $user_id, 'academy_instructor_withdraw_bank_acocunt_number', $bank_account_number );
			update_user_meta( $user_id, 'academy_instructor_withdraw_bank_name', $bank_name );
			update_user_meta( $user_id, 'academy_instructor_withdraw_bank_iban', $bank_iban );
			update_user_meta( $user_id, 'academy_instructor_withdraw_bank_swiftcode', $bank_SWIFT_code );

			$referer_url = Helper::sanitize_referer_url( wp_get_referer() );
			wp_safe_redirect( $referer_url );
	}

	public function instructor_earning_withdrawal() {
		$payload = Sanitizer::sanitize_payload([
			'withdrawal_type' => 'string',
			'withdrawal_amount' => 'integer',
		], $_POST); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		$user_id = get_current_user_id();

		$withdraw_amount      = ( isset( $payload['withdrawal_amount'] ) ? $payload['withdrawal_amount'] : 0 );
		$withdraw_method_type = ( isset( $payload['withdrawal_type'] ) ? $payload['withdrawal_type'] : '' );
		if ( get_user_meta( $user_id, 'academy_instructor_withdraw_method_type', true ) !== $withdraw_method_type ) {
			wp_die( esc_html__( 'Your Request withdraw method type isn\'t match', 'academy' ) );
		}
		$earning      = (object) \Academy\Helper::get_earning_by_user_id( $user_id );
		$min_withdraw = \Academy\Helper::get_settings( 'instructor_minimum_withdraw_amount' );

		if ( $withdraw_amount < $min_withdraw ) {
			wp_die( esc_html( sprintf( '%s %d', __( 'Minimum withdrawal amount is', 'academy' ), $min_withdraw ) ) );
		}

		if ( $earning->balance < $withdraw_amount ) {
			wp_die( esc_html__( 'Insufficient balance.', 'academy' ) );
		}

		$withdraw_args = apply_filters(
			'academy/frontend/withdraw_data_insert_args',
			array(
				'user_id'     => $user_id,
				'amount'      => $withdraw_amount,
				'method_data' => wp_json_encode( \Academy\Helper::get_user_withdraw_saved_info( $user_id, $withdraw_method_type ) ),
				'status'      => 'pending',
				'created_at'  => current_time( 'mysql' ),
			)
		);

		do_action( 'academy/frontend/before_withdraw_data_insert', $withdraw_args );
		$withdraw_id = \Academy\Helper::insert_withdraw( $withdraw_args );
		do_action( 'academy/frontend/after_withdraw_data_insert', $withdraw_id, $withdraw_args );
		$referer_url = Helper::sanitize_referer_url( wp_get_referer() );
		wp_safe_redirect( $referer_url );
	}
}
