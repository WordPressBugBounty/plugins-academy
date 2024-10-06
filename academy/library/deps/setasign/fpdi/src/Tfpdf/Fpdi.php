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

namespace Academy\setasign\Fpdi\Tfpdf;

use Academy\setasign\Fpdi\FpdfTrait;
use Academy\setasign\Fpdi\FpdiTrait;

/**
 * Class Fpdi
 *
 * This class let you import pages of existing PDF documents into a reusable structure for tFPDF.
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
