<?php
/**
 * @license GPL-2.0-only
 *
 * Modified by Kodezen on 03-October-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace Academy\Mpdf;

use Academy\Mpdf\Color\ColorConverter;
use Academy\Mpdf\Color\ColorModeConverter;
use Academy\Mpdf\Color\ColorSpaceRestrictor;
use Academy\Mpdf\File\LocalContentLoader;
use Academy\Mpdf\Fonts\FontCache;
use Academy\Mpdf\Fonts\FontFileFinder;
use Academy\Mpdf\Http\CurlHttpClient;
use Academy\Mpdf\Http\SocketHttpClient;
use Academy\Mpdf\Image\ImageProcessor;
use Academy\Mpdf\Pdf\Protection;
use Academy\Mpdf\Pdf\Protection\UniqidGenerator;
use Academy\Mpdf\Writer\BaseWriter;
use Academy\Mpdf\Writer\BackgroundWriter;
use Academy\Mpdf\Writer\ColorWriter;
use Academy\Mpdf\Writer\BookmarkWriter;
use Academy\Mpdf\Writer\FontWriter;
use Academy\Mpdf\Writer\FormWriter;
use Academy\Mpdf\Writer\ImageWriter;
use Academy\Mpdf\Writer\JavaScriptWriter;
use Academy\Mpdf\Writer\MetadataWriter;
use Academy\Mpdf\Writer\OptionalContentWriter;
use Academy\Mpdf\Writer\PageWriter;
use Academy\Mpdf\Writer\ResourceWriter;
use Academy\Psr\Log\LoggerInterface;

class ServiceFactory
{

	/**
	 * @var \Academy\Mpdf\Container\ContainerInterface|null
	 */
	private $container;

	public function __construct($container = null)
	{
		$this->container = $container;
	}

	public function getServices(
		Mpdf $mpdf,
		LoggerInterface $logger,
		$config,
		$languageToFont,
		$scriptToLanguage,
		$fontDescriptor,
		$bmp,
		$directWrite,
		$wmf
	) {
		$sizeConverter = new SizeConverter($mpdf->dpi, $mpdf->default_font_size, $mpdf, $logger);

		$colorModeConverter = new ColorModeConverter();
		$colorSpaceRestrictor = new ColorSpaceRestrictor(
			$mpdf,
			$colorModeConverter
		);
		$colorConverter = new ColorConverter($mpdf, $colorModeConverter, $colorSpaceRestrictor);

		$tableOfContents = new TableOfContents($mpdf, $sizeConverter);

		$cacheBasePath = $config['tempDir'] . '/mpdf';

		$cache = new Cache($cacheBasePath, $config['cacheCleanupInterval']);
		$fontCache = new FontCache(new Cache($cacheBasePath . '/ttfontdata', $config['cacheCleanupInterval']));

		$fontFileFinder = new FontFileFinder($config['fontDir']);

		if ($this->container && $this->container->has('httpClient')) {
			$httpClient = $this->container->get('httpClient');
		} elseif (\function_exists('curl_init')) {
			$httpClient = new CurlHttpClient($mpdf, $logger);
		} else {
			$httpClient = new SocketHttpClient($logger);
		}

		$localContentLoader = $this->container && $this->container->has('localContentLoader')
			? $this->container->get('localContentLoader')
			: new LocalContentLoader();

		$assetFetcher = new AssetFetcher($mpdf, $localContentLoader, $httpClient, $logger);

		$cssManager = new CssManager($mpdf, $cache, $sizeConverter, $colorConverter, $assetFetcher);

		$otl = new Otl($mpdf, $fontCache);

		$protection = new Protection(new UniqidGenerator());

		$writer = new BaseWriter($mpdf, $protection);

		$gradient = new Gradient($mpdf, $sizeConverter, $colorConverter, $writer);

		$formWriter = new FormWriter($mpdf, $writer);

		$form = new Form($mpdf, $otl, $colorConverter, $writer, $formWriter);

		$hyphenator = new Hyphenator($mpdf);

		$imageProcessor = new ImageProcessor(
			$mpdf,
			$otl,
			$cssManager,
			$sizeConverter,
			$colorConverter,
			$colorModeConverter,
			$cache,
			$languageToFont,
			$scriptToLanguage,
			$assetFetcher,
			$logger
		);

		$tag = new Tag(
			$mpdf,
			$cache,
			$cssManager,
			$form,
			$otl,
			$tableOfContents,
			$sizeConverter,
			$colorConverter,
			$imageProcessor,
			$languageToFont
		);

		$fontWriter = new FontWriter($mpdf, $writer, $fontCache, $fontDescriptor);
		$metadataWriter = new MetadataWriter($mpdf, $writer, $form, $protection, $logger);
		$imageWriter = new ImageWriter($mpdf, $writer);
		$pageWriter = new PageWriter($mpdf, $form, $writer, $metadataWriter);
		$bookmarkWriter = new BookmarkWriter($mpdf, $writer);
		$optionalContentWriter = new OptionalContentWriter($mpdf, $writer);
		$colorWriter = new ColorWriter($mpdf, $writer);
		$backgroundWriter = new BackgroundWriter($mpdf, $writer);
		$javaScriptWriter = new JavaScriptWriter($mpdf, $writer);

		$resourceWriter = new ResourceWriter(
			$mpdf,
			$writer,
			$colorWriter,
			$fontWriter,
			$imageWriter,
			$formWriter,
			$optionalContentWriter,
			$backgroundWriter,
			$bookmarkWriter,
			$metadataWriter,
			$javaScriptWriter,
			$logger
		);

		return [
			'otl' => $otl,
			'bmp' => $bmp,
			'cache' => $cache,
			'cssManager' => $cssManager,
			'directWrite' => $directWrite,
			'fontCache' => $fontCache,
			'fontFileFinder' => $fontFileFinder,
			'form' => $form,
			'gradient' => $gradient,
			'tableOfContents' => $tableOfContents,
			'tag' => $tag,
			'wmf' => $wmf,
			'sizeConverter' => $sizeConverter,
			'colorConverter' => $colorConverter,
			'hyphenator' => $hyphenator,
			'localContentLoader' => $localContentLoader,
			'httpClient' => $httpClient,
			'assetFetcher' => $assetFetcher,
			'imageProcessor' => $imageProcessor,
			'protection' => $protection,

			'languageToFont' => $languageToFont,
			'scriptToLanguage' => $scriptToLanguage,

			'writer' => $writer,
			'fontWriter' => $fontWriter,
			'metadataWriter' => $metadataWriter,
			'imageWriter' => $imageWriter,
			'formWriter' => $formWriter,
			'pageWriter' => $pageWriter,
			'bookmarkWriter' => $bookmarkWriter,
			'optionalContentWriter' => $optionalContentWriter,
			'colorWriter' => $colorWriter,
			'backgroundWriter' => $backgroundWriter,
			'javaScriptWriter' => $javaScriptWriter,
			'resourceWriter' => $resourceWriter
		];
	}

	public function getServiceIds()
	{
		return [
			'otl',
			'bmp',
			'cache',
			'cssManager',
			'directWrite',
			'fontCache',
			'fontFileFinder',
			'form',
			'gradient',
			'tableOfContents',
			'tag',
			'wmf',
			'sizeConverter',
			'colorConverter',
			'hyphenator',
			'localContentLoader',
			'httpClient',
			'assetFetcher',
			'imageProcessor',
			'protection',
			'languageToFont',
			'scriptToLanguage',
			'writer',
			'fontWriter',
			'metadataWriter',
			'imageWriter',
			'formWriter',
			'pageWriter',
			'bookmarkWriter',
			'optionalContentWriter',
			'colorWriter',
			'backgroundWriter',
			'javaScriptWriter',
			'resourceWriter',
		];
	}

}
