<?php
/**
 * @license GPL-2.0-only
 *
 * Modified by Kodezen on 03-October-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace Academy\Mpdf;

class PageBox implements \ArrayAccess
{

	private $container = [];

	public function __construct()
	{
		$this->container = [
			'current' => null,
			'outer_width_LR' => null,
			'outer_width_TB' => null,
			'using' => null,
		];
	}

	#[\ReturnTypeWillChange]
	public function offsetSet($offset, $value)
	{
		if (!$this->offsetExists($offset)) {
			throw new \Academy\Mpdf\MpdfException('Invalid key to set for PageBox');
		}

		$this->container[$offset] = $value;
	}

	#[\ReturnTypeWillChange]
	public function offsetExists($offset)
	{
		return array_key_exists($offset, $this->container);
	}

	#[\ReturnTypeWillChange]
	public function offsetUnset($offset)
	{
		if (!$this->offsetExists($offset)) {
			throw new \Academy\Mpdf\MpdfException('Invalid key to set for PageBox');
		}

		$this->container[$offset] = null;
	}

	#[\ReturnTypeWillChange]
	public function offsetGet($offset)
	{
		if (!$this->offsetExists($offset)) {
			throw new \Academy\Mpdf\MpdfException('Invalid key to set for PageBox');
		}

		return $this->container[$offset];
	}

}
