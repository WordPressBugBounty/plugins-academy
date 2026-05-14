<?php
/**
 * @license GPL-2.0-only
 *
 * Modified by academylms using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace Academy\Mpdf\File;

class LocalContentLoader implements \Academy\Mpdf\File\LocalContentLoaderInterface
{

	public function load($path)
	{
		return file_get_contents($path);
	}

}
