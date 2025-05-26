<?php

namespace AcademyStoreEngine;

class Hooks {

	public static function init() {
		Hooks\Assets::init();
		Hooks\Cart::init();
		Hooks\Order::init();
	}

}
