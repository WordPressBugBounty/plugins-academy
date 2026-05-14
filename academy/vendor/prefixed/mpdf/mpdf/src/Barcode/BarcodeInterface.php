<?php
/**
 * @license GPL-2.0-only
 *
 * Modified by academylms using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace Academy\Mpdf\Barcode;

interface BarcodeInterface
{

	/**
	 * @return string
	 */
	public function getType();

	/**
	 * @return mixed[]
	 */
	public function getData();

	/**
	 * @param string $key
	 *
	 * @return mixed
	 */
	public function getKey($key);

	/**
	 * @return string
	 */
	public function getChecksum();

}
