<?php

namespace AcademyStoreEngine;

use AcademyStoreEngine\ajax\Product;

class Ajax {

	public static function init() {
		( new Product() )->dispatch_actions();
	}
}
