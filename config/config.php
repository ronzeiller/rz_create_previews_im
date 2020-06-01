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

// Embed ICC profiles into those images, which are converted above >>> not implemented yet
$rz_embed_icc_profiles = true;
