<?php

namespace AcademyStoreEngine;

use AcademyStoreEngine\Ajax\Membership;
use AcademyStoreEngine\ajax\Product;

class Ajax {

	public static function init() {
		( new Product() )->dispatch_actions();
		( new Membership() )->dispatch_actions();
	}
}
