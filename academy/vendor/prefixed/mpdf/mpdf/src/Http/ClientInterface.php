<?php
/**
 * @license GPL-2.0-only
 *
 * Modified by academylms using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace Academy\Mpdf\Http;

use Academy\Psr\Http\Message\RequestInterface;

interface ClientInterface
{

	public function sendRequest(RequestInterface $request);

}
