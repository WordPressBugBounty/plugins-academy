<?php
/**
 * @license GPL-2.0-only
 *
 * Modified by academylms using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace Academy\Mpdf\Language;

interface ScriptToLanguageInterface
{

	public function getLanguageByScript($script);

	public function getLanguageDelimiters($language);

}
