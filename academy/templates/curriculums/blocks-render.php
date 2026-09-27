<?php
/**
 * The block learn page. Its blocks can be changed with the
 * `academy/learn_page/template` filter.
 *
 * @package Academy
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- rendered blocks.
echo do_blocks( \Academy\LearnPage::template() );
