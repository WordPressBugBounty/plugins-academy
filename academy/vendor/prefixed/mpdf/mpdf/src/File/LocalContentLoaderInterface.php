<?php
/**
 * @license GPL-2.0-only
 *
 * Modified by academylms using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace Academy\Mpdf\File;

interface LocalContentLoaderInterface
{

	/**
	 * @return string|null
	 */
	public function load($path);

}
