<?php
/**
 * @license GPL-2.0-only
 *
 * Modified by Kodezen on 03-October-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace Academy\Mpdf\Utils;

class NumericString
{

	public static function containsPercentChar($string)
	{
		return strstr($string, '%');
	}

	public static function removePercentChar($string)
	{
		return str_replace('%', '', $string);
	}

}
