<?php
namespace Academy\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PromoDiscount {
	private static $option_key = 'academy_hide_promo_offer';
	private static $offer_end_month_and_day = '1215';
	public static function enable_offer() {
		return delete_option( self::$option_key );
	}
	public static function disable_offer() {
		return add_option( self::$option_key, true );
	}
	public static function is_allow_offer() {
		// if user already see then disable it
		if ( (bool) get_option( self::$option_key ) ) {
			return false;
		}
		// if Academy PRO Available then disable it
		if ( \Academy\Helper::is_plugin_installed( 'academy-pro/academy-pro.php' ) ) {
			return false;
		}

		// check Our Offer time
		if ( (int) current_time( 'Ymd' ) < (int) gmdate( 'Y' ) . self::$offer_end_month_and_day ) {
			return false;
		}
		return false;
	}
}
