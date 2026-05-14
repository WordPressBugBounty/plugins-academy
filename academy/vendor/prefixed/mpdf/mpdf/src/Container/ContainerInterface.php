<?php
/**
 * @license GPL-2.0-only
 *
 * Modified by academylms using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace Academy\Mpdf\Container;

interface ContainerInterface
{

	public function get($id);

	public function has($id);

}
