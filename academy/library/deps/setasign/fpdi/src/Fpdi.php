<?php

/**
 * This file is part of FPDI
 *
 * @package   setasign\Fpdi
 * @copyright Copyright (c) 2024 Setasign GmbH & Co. KG (https://www.setasign.com)
 * @license   http://opensource.org/licenses/mit-license The MIT License
 *
 * Modified by Kodezen on 03-October-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace Academy\setasign\Fpdi;

use Academy\setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException;
use Academy\setasign\Fpdi\PdfParser\PdfParserException;
use Academy\setasign\Fpdi\PdfParser\Type\PdfIndirectObject;
use Academy\setasign\Fpdi\PdfParser\Type\PdfNull;

/**
 * Class Fpdi
 *
 * This class let you import pages of existing PDF documents into a reusable structure for FPDF.
 */
class Fpdi extends FpdfTpl
{
    use FpdiTrait;
    use FpdfTrait;

    /**
     * FPDI version
     *
     * @string
     */
    const VERSION = '2.6.1';
}
