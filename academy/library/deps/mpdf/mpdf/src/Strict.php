<?php
/**
 * @license GPL-2.0-only
 *
 * Modified by Kodezen on 03-October-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace Academy\Mpdf;

trait Strict
{

	/**
	 * @param string $name method name
	 * @param array $args arguments
	 */
	public function __call($name, $args)
	{
		$class = method_exists($this, $name) ? 'parent' : get_class($this);
		throw new \Academy\Mpdf\MpdfException("Call to undefined method $class::$name()");
	}

	/**
	 * @param string $name lowercase method name
	 * @param array $args arguments
	 */
	public static function __callStatic($name, $args)
	{
		$class = get_called_class();
		throw new \Academy\Mpdf\MpdfException("Call to undefined static function $class::$name()");
	}

	/**
	 * @param string $name property name
	 */
	public function &__get($name)
	{
		$class = get_class($this);
		throw new \Academy\Mpdf\MpdfException("Cannot read an undeclared property $class::\$$name");
	}

	/**
	 * @param string $name property name
	 * @param mixed $value property value
	 */
	public function __set($name, $value)
	{
		$class = get_class($this);
		throw new \Academy\Mpdf\MpdfException("Cannot write to an undeclared property $class::\$$name");
	}

	/**
	 * @param string $name property name
	 * @throws \Kdyby\StrictObjects\\Mpdf\MpdfException
	 */
	public function __isset($name)
	{
		$class = get_class($this);
		throw new \Academy\Mpdf\MpdfException("Cannot read an undeclared property $class::\$$name");
	}

	/**
	 * @param string $name property name
	 * @throws \Kdyby\StrictObjects\\Mpdf\MpdfException
	 */
	public function __unset($name)
	{
		$class = get_class($this);
		throw new \Academy\Mpdf\MpdfException("Cannot unset the property $class::\$$name.");
	}

}
