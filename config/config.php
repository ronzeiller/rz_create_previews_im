<?php
/**
 * rz_create_previews_im Plugin  config file * 
 * @package ResourceSpace
 * by Ronnie Zeiller - www.zeiller.eu
 * 
 * Version 0.1
 * 1.6.2020
 * 
 * 
 */

// If there is an ICC profile embedded, convert small previews (scr, pre, thm, col) to $icc_preview_profile
// Large images hpr, lpr will not convert to another colorspace than the original
$rz_icc_convert_small_previews = true;

// Embed ICC profiles into those images, which are converted above
$rz_embed_icc_profiles = true;

$rz_debug = true;

/** 
 *	–––––––––––––––––––––––––––	SPECIAL  DEBUG  –––––––––––––––––––––––––––––––
 */
if ( $rz_debug ) {

	// error_reporting(E_ALL ^ E_NOTICE);
	// error_reporting(E_ALL);
	@ini_set('error_reporting', E_ALL);
	@ini_set('error_log', dirname(__FILE__) . '/../php-error.log' );
	@ini_set('log_errors', 1);
	@ini_set('ignore_repeated_errors', 1);
	@ini_set('display_errors', TRUE);
	@ini_set('display_startup_errors', TRUE);
	
	// var_dump($request_ip);
}