<?php
/**
 * @license GPL-2.0-only
 *
 * Modified by academylms using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace Academy\Mpdf\Tag;

class Th extends Td
{

	public function close(&$ahtml, &$ihtml)
	{
		$this->mpdf->SetStyle('B', false);
		parent::close($ahtml, $ihtml);
	}
}
