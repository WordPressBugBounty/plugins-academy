<?php
/**
 * @license GPL-2.0-only
 *
 * Modified by Kodezen on 03-October-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace Academy\Mpdf\Tag;

use Academy\Mpdf\Strict;

use Academy\Mpdf\Cache;
use Academy\Mpdf\Color\ColorConverter;
use Academy\Mpdf\CssManager;
use Academy\Mpdf\Form;
use Academy\Mpdf\Image\ImageProcessor;
use Academy\Mpdf\Language\LanguageToFontInterface;
use Academy\Mpdf\Mpdf;
use Academy\Mpdf\Otl;
use Academy\Mpdf\SizeConverter;
use Academy\Mpdf\TableOfContents;

abstract class Tag
{

	use Strict;

	/**
	 * @var \Academy\Mpdf\Mpdf
	 */
	protected $mpdf;

	/**
	 * @var \Academy\Mpdf\Cache
	 */
	protected $cache;

	/**
	 * @var \Academy\Mpdf\CssManager
	 */
	protected $cssManager;

	/**
	 * @var \Academy\Mpdf\Form
	 */
	protected $form;

	/**
	 * @var \Academy\Mpdf\Otl
	 */
	protected $otl;

	/**
	 * @var \Academy\Mpdf\TableOfContents
	 */
	protected $tableOfContents;

	/**
	 * @var \Academy\Mpdf\SizeConverter
	 */
	protected $sizeConverter;

	/**
	 * @var \Academy\Mpdf\Color\ColorConverter
	 */
	protected $colorConverter;

	/**
	 * @var \Academy\Mpdf\Image\ImageProcessor
	 */
	protected $imageProcessor;

	/**
	 * @var \Academy\Mpdf\Language\LanguageToFontInterface
	 */
	protected $languageToFont;

	const ALIGN = [
		'left' => 'L',
		'center' => 'C',
		'right' => 'R',
		'top' => 'T',
		'text-top' => 'TT',
		'middle' => 'M',
		'baseline' => 'BS',
		'bottom' => 'B',
		'text-bottom' => 'TB',
		'justify' => 'J'
	];

	public function __construct(
		Mpdf $mpdf,
		Cache $cache,
		CssManager $cssManager,
		Form $form,
		Otl $otl,
		TableOfContents $tableOfContents,
		SizeConverter $sizeConverter,
		ColorConverter $colorConverter,
		ImageProcessor $imageProcessor,
		LanguageToFontInterface $languageToFont
	) {

		$this->mpdf = $mpdf;
		$this->cache = $cache;
		$this->cssManager = $cssManager;
		$this->form = $form;
		$this->otl = $otl;
		$this->tableOfContents = $tableOfContents;
		$this->sizeConverter = $sizeConverter;
		$this->colorConverter = $colorConverter;
		$this->imageProcessor = $imageProcessor;
		$this->languageToFont = $languageToFont;
	}

	public function getTagName()
	{
		$tag = get_class($this);
		return strtoupper(str_replace('Academy\Mpdf\Tag\\', '', $tag));
	}

	protected function getAlign($property)
	{
		$property = strtolower($property);
		return array_key_exists($property, self::ALIGN) ? self::ALIGN[$property] : '';
	}

	abstract public function open($attr, &$ahtml, &$ihtml);

	abstract public function close(&$ahtml, &$ihtml);

}
