<?php
/**
 * @license GPL-2.0-only
 *
 * Modified by academylms using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace Academy\Mpdf;

interface AssetFetcherInterface
{
	/**
	 * Fetch data from a given path, either local or remote.
	 *
	 * @param string $path The path to fetch data from.
	 * @param string|null $originalSrc The original source path, if applicable.
	 * @return string The fetched data.
	 * @throws \Academy\Mpdf\Exception\AssetFetchingException If fetching fails.
	 */
	public function fetchDataFromPath($path, $originalSrc = null);
}
