<?php
/**
 * @license GPL-2.0-only
 *
 * Modified by Kodezen on 03-October-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace Academy\Mpdf\Tag;

class Toc extends Tag
{

	public function open($attr, &$ahtml, &$ihtml)
	{
		//added custom-tag - set Marker for insertion later of ToC
		$this->tableOfContents->openTagTOC($attr);
	}

	public function close(&$ahtml, &$ihtml)
	{
	}
}
