<?php
/**
 * @license MIT
 *
 * Modified by Kodezen on 03-October-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace Academy\Mpdf\PsrLogAwareTrait;

use Academy\Psr\Log\LoggerInterface;

trait MpdfPsrLogAwareTrait
{

	/**
	 * @var \Academy\Psr\Log\LoggerInterface
	 */
	protected $logger;

	public function setLogger(LoggerInterface $logger)
	{
		$this->logger = $logger;
		if (property_exists($this, 'services') && is_array($this->services)) {
			foreach ($this->services as $name) {
				if ($this->$name && $this->$name instanceof \Academy\Psr\Log\LoggerAwareInterface) {
					$this->$name->setLogger($logger);
				}
			}
		}
	}

}
