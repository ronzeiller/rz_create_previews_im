<?php
/**
It's possible to override an existing function by simply declaring it in either 
 * "hooks/all.php" if you want it to be overridden for every page, 
 * or "hooks/[pagename].php" to override a function for a specific page only.
 * When overriding functions you should check that the function has not already 
 * been declared by a previous plugin first.
 * As an example, to override the 'do_search' function for all pages add the 
 * following to 'hooks/all.php'.

 * if (!function_exists("do_search"))
    {
    function do_search($search,$restypes="",$order_by="relevance",$archive=0,$fetchrows=-1)
        {
        # New function code goes here
        }
    }
 * 
 * To make this work, a function check (like the one above) also needs to be added to the base code. 
 * You can request a new function check be added to the base if you need one by 
 * following the procedure for requesting a new hook.
 * 
 */

/**
 * 
 * @global boolean $keep_for_hpr
 * @global type $imagemagick_path
 * @global type $imagemagick_preserve_profiles
 * @global type $imagemagick_quality
 * @global type $imagemagick_colorspace
 * @global type $default_icc_file
 * @global boolean $autorotate_no_ingest			config.default false
 * @global type $always_make_previews
 * @global type $lean_preview_generation
 * @global type $previews_allow_enlarge
 * @global type $alternative_file_previews
 * @global boolean $imagemagick_mpr
 * @global type $imagemagick_mpr_preserve_profiles
 * @global type $imagemagick_mpr_preserve_metadata_profiles
 * @global type $config_windows
 * @global type $preview_tiles
 * @global type $preview_tiles_create_auto
 * @global type $preview_tile_size
 * @global type $preview_tile_scale_factors
 * @global type $imagemagick_mpr_depth
 * @global type $icc_extraction
 * @global type $icc_preview_profile
 * @global type $icc_preview_options
 * @global type $ffmpeg_supported_extensions
 * @global type $icc_preview_profile_embed
 * @global type $transparency_background
 * @global type $watermark
 * @global type $watermark_single_image
 * @param int $ref
 * @param boolean $thumbonly
 * @param string $extension				tif, jpg, ....
 * @param boolean $previewonly
 * @param boolean $previewbased
 * @param int $alternative
 * @param boolean $ingested
 * @param array $onlysizes
 * @return boolean
 */
function create_previews_using_im($ref, $thumbonly = false, $extension = "jpg", $previewonly = false, $previewbased = false, $alternative = -1, $ingested = false, $onlysizes = array()) {
	global $keep_for_hpr, $imagemagick_path, $imagemagick_preserve_profiles, $imagemagick_quality, $imagemagick_colorspace, $default_icc_file;
	global $autorotate_no_ingest, $always_make_previews, $lean_preview_generation, $previews_allow_enlarge, $alternative_file_previews;
	global $imagemagick_mpr, $imagemagick_mpr_preserve_profiles, $imagemagick_mpr_preserve_metadata_profiles, $config_windows;
	global $preview_tiles, $preview_tiles_create_auto, $preview_tile_size, $preview_tile_scale_factors;
	
	# EXPERIMENTAL CODE TO USE EXISTING ICC PROFILE IF PRESENT
	global $icc_extraction;						// Enable extraction and use of ICC profiles from original images
	global $icc_preview_profile;				// e.g.: 'sRGB_IEC61966-2-1_black_scaled.icc';
	global $icc_preview_profile_embed;		// embed target profile? (target profile will be embedded in all previews, but not in col and thm)
	global $icc_preview_options;				// additional options for profile conversion during preview generation, e.g. '-intent perceptual -black-point-compensation'
	global $ffmpeg_supported_extensions;	// List of extensions that can be processed by ffmpeg

	# PLUGIN CONFIG
	global $rz_icc_convert_small_previews, $rz_embed_icc_profiles;
	
	if (!is_numeric($ref)) {
		trigger_error("Parameter 'ref' must be numeric!");
	}

	debug(basename(__FILE__) . ' ' . __LINE__ . " :: Rondebug: create_previews_using_im() entered");
	
	$icc_transform_complete = false;
	debug("create_previews_using_im(ref=$ref,thumbonly=$thumbonly,extension=$extension,previewonly=$previewonly,previewbased=$previewbased,alternative=$alternative,ingested=$ingested)");
	// create_previews_using_im(ref=121,thumbonly=,extension=tif,previewonly=,previewbased=,alternative=-1,ingested=)
	
	if (is_null($imagemagick_path)) {
		return false;
	}

	//$file = '';	// declaration var
	# ----------------------------------------
	# Use ImageMagick to perform the resize
	# ----------------------------------------
	# For resource $ref, (re)create the various preview sizes listed in the table preview_sizes
	# Set thumbonly=true to (re)generate thumbnails only.
	if ($previewbased || ($autorotate_no_ingest && !$ingested)) {
debug(basename(__FILE__) . ' ' . __LINE__ .' :: if ($previewbased || ($autorotate_no_ingest && !$ingested)) {');
		$file = get_resource_path($ref, true, "lpr", false, "jpg", -1, 1, false, "", -1, 1, false, "", $alternative);
		if (!file_exists($file)) {
			$file = get_resource_path($ref, true, "scr", false, "jpg", -1, 1, false, "", -1, 1, false, "", $alternative);
			if (!file_exists($file)) {
				$file = get_resource_path($ref, true, "pre", false, "jpg", -1, 1, false, "", -1, 1, false, "", $alternative);
				/* staged, but not needed in testing
				  if(!file_exists($file) && $autorotate_no_ingest && !$ingested)
				  {
				  $file=get_resource_path($ref,true,"",false,$extension,-1,1,false,"",$alternative);
				  } */
			}
		}
		if ($autorotate_no_ingest && !$ingested && !$previewonly) {
			# extra check for !previewonly should there also be ingested resources in the system
			$file = get_resource_path($ref, true, "", false, $extension, -1, 1, false, "", $alternative);
		}
	} else if (!$previewonly) {
		$file = get_resource_path($ref, true, "", false, $extension, -1, 1, false, "", $alternative);
	} else {
		# We're generating based on a new preview (scr) image.
		$file = get_resource_path($ref, true, "tmp", false, "jpg");
	}
	$origfile = $file;

	$hpr_path = get_resource_path($ref, true, "hpr", false, "jpg", -1, 1, false, "", $alternative);
	if (file_exists($hpr_path) && !$previewbased) {
		unlink($hpr_path);
	}
	$lpr_path = get_resource_path($ref, true, "lpr", false, "jpg", -1, 1, false, "", $alternative);
	if (file_exists($lpr_path) && !$previewbased) {
		unlink($lpr_path);
	}
	$scr_path = get_resource_path($ref, true, "scr", false, "jpg", -1, 1, false, "", $alternative);
	if (file_exists($scr_path) && !$previewbased) {
		unlink($scr_path);
	}
	$scr_wm_path = get_resource_path($ref, true, "scr", false, "jpg", -1, 1, true, "", $alternative);
	if (file_exists($scr_wm_path) && !$previewbased) {
		unlink($scr_wm_path);
	}

	$prefix = '';
	# Camera RAW images need prefix
	if (preg_match('/^(dng|nef|x3f|cr2|crw|mrw|orf|raf|dcr)$/i', $extension, $rawext)) {
		$prefix = $rawext[0] . ':';
	} elseif (!$config_windows && strpos($file, ':') !== false) {
		$prefix = $extension . ':';
	}

	# Locate imagemagick.
	$identify_fullpath = get_utility_path("im-identify");
	if ($identify_fullpath == false) {
		debug("ERROR: Could not find ImageMagick 'identify' utility at location '$imagemagick_path'.");
		return false;
	}

	// @TODO change command for IM v7
	list($sw, $sh) = getFileDimensions($identify_fullpath, $prefix, $file, $extension);

	if ($extension == "svg") {
		$o_width = $sw;
		$o_height = $sh;
	}

	if ($lean_preview_generation) {
		$all_sizes = false;
		if (!$thumbonly && !$previewonly) {
			// seperate width and height
			$all_sizes = true;
		}
	}

	$sizes = "";
	if ($thumbonly) {
		$sizes = " where id='thm' or id='col'";
	} elseif ($previewonly) {
		$sizes = " where id='thm' or id='col' or id='pre' or id='scr'";
	} elseif (is_array($onlysizes) && count($onlysizes) > 0) {
		$sizefilter = array_filter($onlysizes, function($v) {
			return ctype_lower($v);
		});
		$sizes = " where id in ('" . implode("','", $sizefilter) . "')";
		$all_sizes = false;
	}

	$ps = sql_query("select * from preview_size $sizes order by width desc, height desc");
//debug( basename(__FILE__) . ' ' . __LINE__ .' :: Sizes: ' . var_export($ps, true) );
//debug( basename(__FILE__) . ' ' . __LINE__ .' :: File: ' . $file );
	if ($lean_preview_generation && $all_sizes) {
		$force_make = array("pre", "thm", "col");
		if ($extension != "jpg" || $extension != "jpeg") {
			array_push($force_make, "hpr", "scr");
		}
		$count = count($ps) - 1;
		$oversized = 0;
		for ($s = $count; $s > 0; $s--) {
			if (!in_array($ps[$s]['id'], $force_make) && !in_array($ps[$s]['id'], $always_make_previews) && (isset($o_width) && isset($o_height) && $ps[$s]['width'] > $o_width && $ps[$s]['height'] > $o_height) && !$previews_allow_enlarge) {
				$oversized++;
			}
			if ($oversized > 0) {
				unset($ps[$s]);
			}
		}
		$ps = array_values($ps);
	}

	if ((count($ps) > 0 && $preview_tiles && $preview_tiles_create_auto) || in_array("tiles", $onlysizes)) {
		$o = count($ps);
		// Ensure that scales are in order
		natsort($preview_tile_scale_factors);

		debug("create_previews - adding tiles to generate list: source width: " . $sw . " source height: " . $sh);
		foreach ($preview_tile_scale_factors as $scale) {
			$x = 0;
			$y = 0;
			$fullgenerated = false;
			$tileregion = $preview_tile_size * $scale;
			debug("create_previews - creating tiles at scale: " . $scale . ". Region size=" . $tileregion);
			if ($fullgenerated && $tileregion > $sh || $tileregion > $sw) {
				debug("create_previews scaled tile (" . $scale . ") too large for source. Tile region length: " . $tileregion);
				continue;
			}

			while ($y < $sh) {
				$tileh = $tileregion;
				if (($y + $tileregion) > $sh) {
					debug("create_previews tiles: $y, - tile taller than area, reducing height");
					$tileh = $sh - $y;
				}
				while ($x < $sw) {
					$tilew = $tileregion;
					if (($x + $tileregion) > $sw) {
						debug("create_previews tiles: $x, - tile wider than area, reducing width");
						$tilew = $sw - $x;
					}
					$tileid = (string) ($x) . "_" . (string) ($y) . "_" . (string) ($tilew) . "_" . (string) ($tileh);
					debug("create_previews tiles scale: " . $scale . ", x: " . $x . ", y: " . $y);
					debug("create_previews tiles id: " . $tileid);
					$ps[$o]['id'] = "tile_" . $tileid;
					$ps[$o]['width'] = $preview_tile_size;
					$ps[$o]["height"] = $preview_tile_size;
					$ps[$o]["x"] = $x;
					$ps[$o]["y"] = $y;
					$ps[$o]["w"] = $tilew;
					$ps[$o]["h"] = $tileh;
					$ps[$o]["type"] = "tile";
					$ps[$o]["internal"] = 1;
					$ps[$o]["allow_preview"] = 0;

					$x = $x + $tileregion;
					$o++;
				}
				$x = 0;
				$y = $y + $tileregion;
			}
		}
	}

	if (count($onlysizes) == 1 && substr($onlysizes[0], 0, 8) == "resized_") {
		$o = count($ps);
		$size_req = explode("_", substr($onlysizes[0], 8));
		$customx = $size_req[0];
		$customy = $size_req[1];

		debug("create_previews - creating custom size width: " . $customx . " height: " . $customy);
		$ps[$o]['id'] = $onlysizes[0];
		$ps[$o]['width'] = $customx;
		$ps[$o]["height"] = $customy;
	}

	# Locate imagemagick.
	$convert_fullpath = get_utility_path("im-convert");	// '[path/]convert'

	if ($convert_fullpath == false) {
		debug("ERROR: Could not find ImageMagick 'convert' utility at location '$imagemagick_path'.");
		return false;
	}

	$command_list = '';
	$im_version = null;
	if ($imagemagick_mpr) {
		// need to check that we're using IM and not GM
		$version = run_command($convert_fullpath . " -version");
		if (strpos($version, "GraphicsMagick") !== false) {
			$imagemagick_mpr = false;
			
		} else {	// ImageMagick is used, get version number as for v >= 7 => 'magick convert' and '-alpha off' is used
			global $imagemagick_mpr_depth;
			$command = '';
			$command_parts = array();
			
			$version = get_imagemagick_version();
			if( $version[0] ) { 
				$im_version = $version[0];
				if ( $im_version >= 7 ) {
					// change command for IM version >= 7.0
					$convert_fullpath = str_replace('convert', 'magick convert', $convert_fullpath);
				}
			}
		}
	}
	
	// in original image_processing.php this is in the loop.....
	$iccfound = false;
	if ($imagemagick_mpr && $icc_extraction) {
		$iccpath = get_resource_path($ref, true, '', false, 'icc', -1, 1, false, "", $alternative);	// path to source icc profile

		# extract ICC Profile of Source if one is embedded
		# will be stored under something like: 119_ad6510e390e7d4b.tif.icc
		if (!file_exists($iccpath) && $extension != "pdf" && !in_array($extension, $ffmpeg_supported_extensions)) {
			// extracted profile doesn't exist. Try extracting.
			if (extract_icc_profile($ref, $extension, $alternative)) {
				$iccfound = true;
				//debug(basename(__FILE__) . ' ' . __LINE__ . " :: Rondebug $iccpath");
			}
		}
		// we have an extracted ICC profile, so use it as source
		// get the target profile
		$targetprofile = dirname(__FILE__) . '/../../../iccprofiles/' . $icc_preview_profile;
	}
	
	$created_count = 0;
	for ($n = 0; $n < count($ps); $n++) {
		if ($imagemagick_mpr) {
			$mpr_parts = array();
		}

		# If this is just a jpg resource, we avoid the hpr size because the resource itself is an original sized jpg. 
		# If preview_preprocessing indicates the intermediate jpg should be kept as the hpr image, do that. 
		if ($keep_for_hpr && $ps[$n]['id'] == "hpr") {
			rename($file, $hpr_path); // $keep_for_hpr is switched to false below
		}

		# Fuzzy logic? files $hpr_path, $lpr_path, $scr_path are not existing as they get deleted before new previews are built.
		# Secondly, the magick command is doing all previews at once
		# ??????
		# If we've already made the LPR or SCR then use those for the remaining previews.
		# As we start with the large and move to the small, this will speed things up.
		if ($extension != "png" && $extension != "gif") {
			if (file_exists($hpr_path)) {
				$file = $hpr_path;
			}
			if (file_exists($lpr_path)) {
				$file = $lpr_path;
			}
			if (file_exists($scr_path)) {
				$file = $scr_path;
			}
			//debug(basename(__FILE__) . ' ' . __LINE__ . " :: Rondebug $n : $file");
			// 0 : /var/customers/webs/cdc/include/../filestore/1/2/1_ee0ba95f8660383/121_132027ca86ff40d.tif
			// 1 : /var/customers/webs/cdc/include/../filestore/1/2/1_ee0ba95f8660383/121_132027ca86ff40d.tif
			// usw....

			# Check that source image dimensions are sufficient to create the required size. Unusually wide/tall images can
			# mean that the height/width of the larger sizes is less than the required target height/width
			list($checkw, $checkh) = @getimagesize($file);
			if ((($checkw < $ps[$n]['width'] || $checkh < $ps[$n]['height']) || (isset($ps[$n]['type']) && $ps[$n]['type'] == "tile")) && $file != $hpr_path) {
				$file = file_exists($hpr_path) ? $hpr_path : $origfile;
			}
		}

		// HPR? Use the original image size instead of the hpr.
		if ($ps[$n]["id"] == "hpr") {
			$ps[$n]["width"] = $sw;
			$ps[$n]["height"] = $sh;
		}
		
		/** Useless to have it here again in line 1636 in image_processing.php */
      # Locate imagemagick.
      //$convert_fullpath = get_utility_path("im-convert");
      //if ($convert_fullpath==false) {debug("ERROR: Could not find ImageMagick 'convert' utility at location '$imagemagick_path'."); return false;}


		if ($prefix == "cr2:" || $prefix == "nef:" || $extension == "png" || $extension == "gif" || getval("noflatten", "") != "") {
			$flatten = "";
		} else {
			$flatten = "-flatten";
		}

		// Extensions for which the alpha/ matte channel should not be set to Off (i.e. +matte option) *** '+matte' no longer exists but is the same as '-alpha off'
		$extensions_no_alpha_off = array('png', 'gif', 'tif');

		$preview_quality = get_preview_quality($ps[$n]['id']);

		if (!$imagemagick_mpr) {	// GraphicsMagick
			$command = $convert_fullpath . ' ' . escapeshellarg((!$config_windows && strpos($file, ':') !== false ? $extension . ':' : '') . $file) . (!in_array($extension, $extensions_no_alpha_off) ? '[0] +matte ' : '[0] ') . $flatten . ' -quality ' . $preview_quality;
		}

		# fetch target width and height
		$tw = $ps[$n]["width"];
		$th = $ps[$n]["height"];
		$id = $ps[$n]["id"];

		# Add crop if generating a tile
		$crop = false;
		if (isset($ps[$n]['type']) && $ps[$n]['type'] == "tile") {
			$cropx = $ps[$n]["x"];
			$cropy = $ps[$n]["y"];
			$cropw = $ps[$n]["w"];
			$croph = $ps[$n]["h"];
			$crop = true;
		}

		if ($imagemagick_mpr) {	// ImageMagick
			$mpr_parts['id'] = $id;
			$mpr_parts['quality'] = $preview_quality;
			$mpr_parts['tw'] = ($id == 'hpr' && $tw == 999999 && isset($o_width) ? $o_width : $tw); // might as well pass on the original dimension
			$mpr_parts['th'] = ($id == 'hpr' && $th == 999999 && isset($o_height) ? $o_height : $th); // might as well pass on the original dimension
			# TODO Add support for tiles
			$mpr_parts['flatten'] = ($flatten == '' ? false : true);
			//$mpr_parts['icc_transform_complete'] = $icc_transform_complete;
			// as we always take the original input file to start with, icc transform cannot be finished
			$mpr_parts['icc_transform_complete'] = false;
		}

		# Debug
		debug("Contemplating " . $ps[$n]["id"] . " (sw=$sw, tw=$tw, sh=$sh, th=$th, extension=$extension)");
		// e.g. Contemplating hpr (sw=7795, tw=7795, sh=7826, th=7826, extension=tif)

		# Find the target path
		if ($extension == "png" || $extension == "gif") {
			$target_ext = $extension;
		} else {
			$target_ext = "jpg";
		}
		$path = get_resource_path($ref, true, $ps[$n]["id"], ($imagemagick_mpr ? true : false), $target_ext, -1, 1, false, "", $alternative);

		if ($imagemagick_mpr) {
			$mpr_parts['targetpath'] = $path;	// path for each preview image hpr, lpr, scr, ......
		}

		# Delete any file at the target path. Unless using the previewbased option, in which case we need it.           
		if (!hook("imagepskipdel") && !$keep_for_hpr) {
			if (!$previewbased) {
				if (file_exists($path)) {
					unlink($path);
				}
			}
		}
		if ($keep_for_hpr) {
			$keep_for_hpr = false;
		}

		# Also try the watermarked version.
		$wpath = get_resource_path($ref, true, $ps[$n]["id"], false, $target_ext, -1, 1, true, "", $alternative);
		if (file_exists($wpath)) {
			unlink($wpath);
		}

		# Always make a screen size for non-JPEG extensions regardless of actual image size
		# This is because the original file itself is not suitable for full screen preview, as it is with JPEG files.
		#
		# Always make preview sizes for smaller file sizes.
		#
		# Always make pre/thm/col sizes regardless of source image size.
		if (($id == "hpr" && !($extension == "jpg" || $extension == "jpeg")) || $previews_allow_enlarge || ($id == "scr" && !($extension == "jpg" || $extension == "jpeg")) || ($sw > $tw) || ($sh > $th) || ($id == "pre") || ($id == "thm") || ($id == "col") || in_array($id, $always_make_previews) || hook('force_preview_creation', '', array($ref, $ps, $n, $alternative))) {
			# Debug
			resource_log(RESOURCE_LOG_APPEND_PREVIOUS, LOG_CODE_TRANSFORMED, '', '', '', "Generating preview size " . $ps[$n]["id"]); // log the size being created but not the path
			debug("Generating preview size " . $ps[$n]["id"] . " to " . $path);
			
			/** Doesnt make sense to have it here in the loop
			// e.g. Generating preview size hpr to ......hpr_xxx.jpg# EXPERIMENTAL CODE TO USE EXISTING ICC PROFILE IF PRESENT
			global $icc_extraction;						// Enable extraction and use of ICC profiles from original images
			global $icc_preview_profile;				// e.g.: 'sRGB_IEC61966-2-1_black_scaled.icc';
			global $icc_preview_profile_embed;		// embed target profile? (target profile will be embedded in all previews, but not in col and thm)
			global $icc_preview_options;				// additional options for profile conversion during preview generation, e.g. '-intent perceptual -black-point-compensation'
			global $ffmpeg_supported_extensions;	// List of extensions that can be processed by ffmpeg
			 * 
			 */
			
			// for GraphicsMagick is maybe different.....
			if (!$imagemagick_mpr && $icc_extraction) {
				$iccpath = get_resource_path($ref, true, '', false, 'icc', -1, 1, false, "", $alternative);	// path to source icc profile
				
				# extract ICC Profile of Source if one is embedded
				# will be stored under something like: 119_ad6510e390e7d4b.tif.icc
				if (!file_exists($iccpath) && !isset($iccfound) && $extension != "pdf" && !in_array($extension, $ffmpeg_supported_extensions)) {
					// extracted profile doesn't exist. Try extracting.
					if (extract_icc_profile($ref, $extension, $alternative)) {
						// $iccfound = true;
					}
				}
			}
			$profile = '';
			
			// This is very unclear it enters only if $icc_transform_complete == false && thm or col or pre or scr
			// Because $icc_transform_complete is set to true when $id == 'scr' it will never enter for other $id
			// But, as remarked above: as we always take the original input file to start with, icc transform cannot be finished
			// therefore $icc_transform_complete == false stays for all preview sizes
			if ($icc_extraction && file_exists($iccpath) && !$icc_transform_complete && !$rz_icc_convert_small_previews
				&& (!$imagemagick_mpr 
				|| ($imagemagick_mpr_preserve_profiles && ($id == "thm" || $id == "col" || $id == "pre" || $id == "scr")))
				) {
				//debug(basename(__FILE__) . ' ' . __LINE__ . " :: Rondebug if-Zweig $id");
				
				// we have an extracted ICC profile, so use it as source
				// get the target profile
				$targetprofile = dirname(__FILE__) . '/../../../iccprofiles/' . $icc_preview_profile;
				
				if ($imagemagick_mpr) {
					$mpr_parts['strip_source'] = ($imagemagick_mpr_preserve_profiles ? false : true);
					$mpr_parts['sourceprofile'] = ($imagemagick_mpr_preserve_profiles ? '-profile ' . $iccpath : '') . " " . $icc_preview_options;
					//$mpr_parts['sourceprofile'] = '-profile ' . $iccpath . " " . $icc_preview_options;
					$mpr_parts['strip_target'] = ($icc_preview_profile_embed ? false : true);
					$mpr_parts['targetprofile'] = $targetprofile;
					//$mpr_parts['colorspace']='';
					
				} else {
					$profile = " -strip -profile $iccpath $icc_preview_options -profile $targetprofile" . ($icc_preview_profile_embed ? " " : " -strip ");
				}
				
				// does never reach here if $id == hpr or lpr.......!!!!
				// consider ICC transformation complete, if one of the sizes has been rendered that will be used for the smaller sizes
				// condition is always true as we are here if $id == "scr"

				if ($id == 'hpr' || $id == 'lpr' || $id == 'scr') {
					$icc_transform_complete = true;
					if ($imagemagick_mpr) {
						$mpr_parts['icc_transform_complete'] = $icc_transform_complete;
					}
				}

			} elseif ($imagemagick_mpr && $icc_extraction && $rz_icc_convert_small_previews && ($id == "thm" || $id == "col" || $id == "pre" || $id == "scr") ) {
				//debug(basename(__FILE__) . ' ' . __LINE__ . " :: Rondebug elseif-Zweig $id");
				$mpr_parts['strip_source'] = false;
				$mpr_parts['sourceprofile'] = ( file_exists($iccpath) ? $iccpath : '') . " " . $icc_preview_options;
				$mpr_parts['strip_target'] = false;
				$mpr_parts['targetprofile'] = $targetprofile . ' -type truecolor';
				if ( $id == "thm" || $id == "col" ) { $mpr_parts['targetprofile'].= ' -strip '; }
				
			} else {
				//debug(basename(__FILE__) . ' ' . __LINE__ . " :: Rondebug else-Zweig $id");
				// use existing strategy for color profiles
				# Preserve colour profiles? (omit for smaller sizes)
				if (($imagemagick_preserve_profiles || $imagemagick_mpr_preserve_profiles) && $id != "thm" && $id != "col" && $id != "pre" && $id != "scr") {
					// enters first when $id == hpr and second when $id == lpr
					// image stays in the same color space
					if ($imagemagick_mpr) {
						$mpr_parts['strip_source'] = false;
						$mpr_parts['sourceprofile'] = '';
						$mpr_parts['strip_target'] = false;
						$mpr_parts['targetprofile'] = '';
					} else {
						$profile = "";
					}
				} else if (!empty($default_icc_file)) {
					if ($imagemagick_mpr) {
						$mpr_parts['strip_source'] = false;
						$mpr_parts['sourceprofile'] = $default_icc_file;
						$mpr_parts['strip_target'] = false;
						$mpr_parts['targetprofile'] = '';
					} else {
						$profile = "-profile $default_icc_file ";
					}
				} else {
					if ($imagemagick_mpr) {
						$mpr_parts['strip_source'] = true;
						$mpr_parts['sourceprofile'] = '';
						$mpr_parts['strip_target'] = false;
						$mpr_parts['targetprofile'] = '';
					} else {
						# By default, strip the colour profiles ('+' is remove the profile, confusingly)
						$profile = "-strip -colorspace " . $imagemagick_colorspace;
					}
				}
			}

			if (!$imagemagick_mpr) {
				$runcommand = $command . " " . (($extension != "png" && $extension != "gif") ? " +matte $profile " : "");

				if ($crop) {
					// Add crop argument for tiling
					$runcommand .= " -crop " . $cropw . "x" . $croph . "+" . $cropx . "+" . $cropy;
				}

				if ($id == "thm" || $id == "col" || $id == "pre" || $id == "scr") {
					//$runcommand .= " -set colorspace:auto-grayscale=false ";
				}

				$runcommand .= " -resize " . $tw . "x" . $th . (($previews_allow_enlarge && $id != "hpr") ? " " : "\">\" ") . escapeshellarg($path);
				if (!hook("imagepskipthumb")) {
					$command_list .= $runcommand . "\n";
					$output = run_command($runcommand);
					$created_count++;
					# if this is the first file generated for non-ingested resources check rotation
					if ($autorotate_no_ingest && $created_count == 1 && !$ingested) {
						# first preview created for non-ingested file...auto-rotate
						if ($id == "thm" || $id == "col" || $id == "pre" || $id == "scr") {
							AutoRotateImage($path, $ref);
						} else {
							AutoRotateImage($path);
						}
					}
				}

				// checkerboard - this will have to be integrated into mpr
				if ($extension == "png" || $extension == "gif") {
					global $transparency_background;
					$transparencyreal = dirname(__FILE__) . "/../" . $transparency_background;

					$cmd = str_replace("identify", "composite", $identify_fullpath) . "  -compose Dst_Over -tile " . escapeshellarg($transparencyreal) . " " . escapeshellarg($path) . " " . escapeshellarg(str_replace($extension, "jpg", $path));
					$command_list .= $cmd . "\n";
					$wait = run_command($cmd, true);

					if (file_exists($path)) {
						unlink($path);
					}
					$path = str_replace($extension, "jpg", $path);
				}
			}

			# Add a watermarked image too?
			global $watermark, $watermark_single_image;

			if (!hook("replacewatermarkcreation", "", array($ref, $ps, $n, $alternative, $profile, $command)) && ($alternative == -1 || ($alternative !== -1 && $alternative_file_previews)) && isset($watermark) && ($ps[$n]["internal"] == 1 || $ps[$n]["allow_preview"] == 1)) {
				$wmpath = get_resource_path($ref, true, $ps[$n]["id"], false, "jpg", -1, 1, true, '', $alternative);
				if (file_exists($wmpath)) {
					unlink($wmpath);
				}

				$watermarkreal = dirname(__FILE__) . "/../" . $watermark;

				if ($imagemagick_mpr) {
					$mpr_parts['wmpath'] = $wmpath;
				}

				if (!($extension == "png" || $extension == "gif") && !isset($watermark_single_image)) {
					$runcommand = $command . " +matte $profile -resize " . $tw . "x" . $th . "\">\" -tile " . escapeshellarg($watermarkreal) . " -draw \"rectangle 0,0 $tw,$th\" " . escapeshellarg($wmpath);
				}

				// alternate command for png/gif using the path from above, and omitting resizing
				if ($extension == "png" || $extension == "gif") {
					$runcommand = $convert_fullpath . ' ' . escapeshellarg($path) . (($extension != "png" && $extension != "gif") ? '[0] +matte ' : '') . $flatten . ' -quality ' . $preview_quality . " -tile " . escapeshellarg($watermarkreal) . " -draw \"rectangle 0,0 $tw,$th\" " . escapeshellarg($wmpath);
				}

				// Generate the command for a single watermark instead of a tiled one
				if (isset($watermark_single_image)) {
					if ($id == "hpr" && ($tw > $sw || $th > $sh)) {
						// reverse them as the watermark geometry should be as big as the image itself
						// hpr size is 999999 width/height - the geometry would be huge even if we are scaling it
						// for watermarks
						$temp_tw = $tw;
						$temp_th = $th;

						$tw = $sw;
						$th = $sh;

						$sw = $temp_tw;
						$sh = $temp_th;

						debug("create_previews: reversed sw - sh with tw - th : $sw - $sh with $tw - $th");
					}

					// Work out minimum of target dimensions, by calulating targets dimensions based on actual file ratio to get minimum dimension, essential to calulate correct values based on ratio of watermark
					// Landscape
					if ($sw > $sh) {
						$tmin = min($tw * ($sh / $sw), $th);
					}
					// Portrait
					else if ($sw < $sh) {
						$tmin = min($th * ($sw / $sh), $tw);
					}
					// Square
					else {
						$tmin = min($tw, $th);
					}

					// Get watermark dimensions
					list($wmw, $wmh) = getFileDimensions($identify_fullpath, '', $watermarkreal, 'jpeg');
					$wm_scale = $watermark_single_image['scale'];

					// Landscape
					if ($wmw > $wmh) {
						$wm_scaled_height = ($tmin * ($wmh / $wmw)) * ($wm_scale / 100);
						$wm_scaled_width = $tmin * ($wm_scale / 100);
					}
					// Portrait
					else if ($wmw < $wmh) {
						$wm_scaled_width = ($tmin * ($wmw / $wmh)) * ($wm_scale / 100);
						$wm_scaled_height = $tmin * ($wm_scale / 100);
					}
					// Square
					else {
						$wm_scaled_width = $tmin * ($wm_scale / 100);
						$wm_scaled_height = $tmin * ($wm_scale / 100);
					}

					// Command example: convert input.jpg watermark.png -gravity Center -geometry 40x40+0+0 -resize 1100x800 -composite wm_version.jpg
					$runcommand = sprintf('%s %s %s -gravity %s -geometry %s -resize %s -composite %s',
						$convert_fullpath,
						escapeshellarg($file),
						escapeshellarg($watermarkreal),
						escapeshellarg($watermark_single_image['position']),
						escapeshellarg("{$wm_scaled_width}x{$wm_scaled_height}+0+0"),
						escapeshellarg("{$tw}x{$th}" . ($previews_allow_enlarge && $id != "hpr" ? "" : ">")),
						escapeshellarg($wmpath)
					);
				}
				if (!$imagemagick_mpr) {
					$command_list .= $runcommand . "\n";
					$output = run_command($runcommand);
				}
			}// end hook replacewatermarkcreation
			
			if ($imagemagick_mpr) {
				// need a watermark replacement here as the existing hook doesn't work
				$modified_mpr_watermark = hook("modify_mpr_watermark", '', array($ref, $ps, $n, $alternative));
				if ($modified_mpr_watermark != '') {
					$mpr_parts['wmpath'] = $modified_mpr_watermark;
					
					/* ?????? */
					if ($id != "thm" && $id != "col" && $id != "pre" && $id != "scr") {
						// need to convert the profile
						$mpr_parts['wm_sourceprofile'] = (!$imagemagick_mpr_preserve_profiles ? $iccpath : '') . " " . $icc_preview_options;
						$mpr_parts['wm_targetprofile'] = ($icc_extraction && file_exists($iccpath) && $id != "thm" || $id != "col" || $id != "pre" || $id == "scr" ? dirname(__FILE__) . '/../../../iccprofiles/' . $icc_preview_profile : "");
					}
				}
				$command_parts[] = $mpr_parts;
				
			}
		}
	}
debug(__LINE__ . ':: Rondebug $command_parts: ' . var_export($command_parts, true) );
	// run the mpr command if set
	if ($imagemagick_mpr) {
		// let's run some checks to better optimize the convert command. Assume everything is the same until proven otherwise
		$unique_flatten = false;
		$unique_strip_source = false;
		$unique_source_profile = false;
		$unique_strip_target = false;
		$unique_target_profile = false;

		$cp_count = count($command_parts);
		$mpr_init_write = false;
		$mpr_icc_transform_complete = false;
		$mpr_wm_created = false;

		for ($p = 1; $p < $cp_count; $p++) {
			$force_mpr_write = false;
			$skip_source_and_target_profiles = false;
			// we compare these with the previous
			if ($command_parts[$p]['flatten'] !== $command_parts[$p - 1]['flatten'] && !$unique_flatten) {
				$unique_flatten = true;
			}
			if ($command_parts[$p]['strip_source'] !== $command_parts[$p - 1]['strip_source'] && !$unique_strip_source) {
				$unique_strip_source = true;
			}
			if ($command_parts[$p]['sourceprofile'] !== $command_parts[$p - 1]['sourceprofile'] && !$unique_source_profile) {
				$unique_source_profile = true;
			}
			if ($command_parts[$p]['strip_target'] !== $command_parts[$p - 1]['strip_target'] && !$unique_strip_target) {
				$unique_strip_target = true;
			}
			if ($command_parts[$p]['targetprofile'] !== $command_parts[$p - 1]['targetprofile'] && !$unique_target_profile) {
				$unique_target_profile = true;
			}
		}
		
		// time to build the command
		$command = $convert_fullpath . ' ' . escapeshellarg((!$config_windows && strpos($file, ':') !== false ? $extension . ':' : '') . $file) . (!in_array($extension, $extensions_no_alpha_off) ? '[0] -quiet -alpha off' : '[0] -quiet') . ' -depth ' . $imagemagick_mpr_depth;

		if (!$unique_flatten) {
			$command .= ($command_parts[0]['flatten'] ? " -flatten " : "");
		}
		if (!$unique_strip_source) {
			$command .= ($command_parts[0]['strip_source'] ? " -strip " : "");
		}
		if (!$unique_source_profile && $command_parts[0]['sourceprofile'] !== '') {
			$command .= " -profile " . $command_parts[0]['sourceprofile'];
		}
		if (!$unique_strip_target) {
			$command .= ($command_parts[0]['strip_target'] ? " -strip " : "");
		}
		if (!$unique_source_profile && !$unique_target_profile && $command_parts[0]['targetprofile'] !== '') { // if the source is different but the target is the same we could get into trouble...
			$command .= " -profile " . $command_parts[0]['targetprofile'];
		}

		if ($autorotate_no_ingest) {
			$orientation = get_image_orientation($file);
			if ($orientation != 0) {
				$command .= ' -rotate +' . $orientation;
			}
		}
		
		$mpr_metadata_profiles = '';
		if (!empty($imagemagick_mpr_preserve_metadata_profiles)) {
			$mpr_metadata_profiles = "!" . implode(",!", $imagemagick_mpr_preserve_metadata_profiles);
		}

		for ($p = 0; $p < $cp_count; $p++) {
			if ($extension == "png" || $extension == "gif") {
				$command_parts[$p]['targetpath'] = str_replace($extension, "jpg", $command_parts[$p]['targetpath']);
				if ($p == 0) {
					$command .= " \( -size " . $command_parts[$p]['tw'] . "x" . $command_parts[$p]['th'] . " tile:pattern:checkerboard \) +swap -compose over -composite";
				}
			}
			$command .= ($p > 0 && $mpr_init_write ? ' mpr:' . $ref : '');

			if (isset($command_parts[$p]['icc_transform_complete']) && !$mpr_icc_transform_complete && $command_parts[$p]['icc_transform_complete'] && $command_parts[$p]['targetprofile'] !== '') {
				// convert to the target profile now. the source profile will only contain $icc_preview_options and needs to be included here as well
				$command .= ($command_parts[$p]['sourceprofile'] != '' ? " " . $command_parts[$p]['sourceprofile'] : "") . " -profile " . $command_parts[$p]['targetprofile'] . ($mpr_metadata_profiles !== '' ? " +profile \"" . $mpr_metadata_profiles . ",*\"" : "");
				$mpr_icc_transform_complete = true;
				$force_mpr_write = true;
				$skip_source_and_target_profiles = true;
			}

			if ($command_parts[$p]['tw'] !== '' && $command_parts[$p]['th'] !== '') {
				$command .= " -resize " . $command_parts[$p]['tw'] . "x" . $command_parts[$p]['th'] . (($previews_allow_enlarge && $command_parts[$p]['id'] != "hpr") ? " " : "\">\"");
				if ($p > 0) {
					// $command .= " -profile " . $iccpath;
					$command .= " -write mpr:" . $ref . " -delete 1";
				}
			}

			if ($unique_flatten || $unique_strip_source || $unique_source_profile || $unique_strip_target || $unique_target_profile) {
				// make these changes
				if ($unique_flatten) {
					$command .= ($command_parts[$p]['flatten'] ? " -flatten " : "");
				}
				if ($unique_strip_source && !$skip_source_and_target_profiles && !$mpr_icc_transform_complete) {
					$command .= ($command_parts[$p]['strip_source'] ? " -strip " : "");
				}
				if ($unique_source_profile && $command_parts[$p]['sourceprofile'] !== '' && !$skip_source_and_target_profiles && !$mpr_icc_transform_complete) {
					$command .= " -profile " . $command_parts[$p]['sourceprofile'];
				}
				if ($unique_strip_target && !$skip_source_and_target_profiles && !$mpr_icc_transform_complete) { // if the source is different but the target is the same we could get into trouble...
					$command .= ($command_parts[$p]['strip_target'] ? " -strip" : "");
				}
				if ($unique_target_profile && $command_parts[$p]['targetprofile'] !== '' && !$skip_source_and_target_profiles && !$mpr_icc_transform_complete) {
					$command .= " -profile " . $command_parts[$p]['targetprofile'];
				}
			}
			// save out to file
			//  image sequence preceding the -write filename option is written out, and processing continues with the same image in its current state if there are additional options
			$command .= (($p === ($cp_count - 1) && !isset($command_parts[$p]['wmpath'])) ? " " : " -quality " . $command_parts[$p]['quality'] . " -write ") . escapeshellarg($command_parts[$p]['targetpath']) . ($mpr_wm_created && isset($command_parts[$p]['wmpath']) ? " +delete mpr:" . $ref : "" );
			//$command.=" -write " . $command_parts[$p]['targetpath'];

			// watermarks?
			if (isset($command_parts[$p]['wmpath'])) {
				if (!$mpr_wm_created) {
					if (isset($command_parts[$p]['wm_sourceprofile'])) {
						// convert to the target profile now. the source profile will only contain $icc_preview_options and needs to be included here as well
						$command .= ($command_parts[$p]['wm_sourceprofile'] != '' ? " " . $command_parts[$p]['wm_sourceprofile'] : "") . (isset($command_parts[$p]['wm_targetprofile']) && $command_parts[$p]['wm_targetprofile'] != '' ? " -profile " . $command_parts[$p]['wm_targetprofile'] : "" ) . ($mpr_metadata_profiles !== '' ? " +profile \"" . $mpr_metadata_profiles . ",*\"" : "");
						$mpr_icc_transform_complete = true;
						//$force_mpr_write=true;
						//$skip_source_and_target_profiles=true;
					}
					$TILESIZE = ($command_parts[$p]['th'] < $command_parts[$p]['tw'] ? $command_parts[$p]['th'] : $command_parts[$p]['tw']);
					$TILESIZE = $TILESIZE / 3;
					$TILEROLL = $TILESIZE / 4;

					// let's create the watermark and save as an mpr
					$command .= " \( " . escapeshellarg($watermarkreal) . " -resize x" . escapeshellarg($TILESIZE) . " -background none -write mpr:" . $ref . " +delete \)";
					$command .= " \( -size " . escapeshellarg($command_parts[$p]['tw']) . "x" . escapeshellarg($command_parts[$p]['th']) . " -roll -" . escapeshellarg($TILEROLL) . "-" . escapeshellarg($TILEROLL) . " tile:mpr:" . $ref . " \) \( -clone 0 -clone 1 -compose dissolve -define compose:args=5 -composite \)";
					$mpr_init_write = true;
					$mpr_wm_created = true;
					$command .= " -delete 1 -write mpr:" . $ref . " -delete 0";
					$command .= " -quality " . $command_parts[$p]['quality'] . ($p !== ($cp_count - 1) ? " -write " : " ") . escapeshellarg($command_parts[$p]['wmpath']);
				}
				// now add the watermark line in
				else {
					$command .= " -delete 0" . ($p !== ($cp_count - 1) ? " -write " : " ") . escapeshellarg($command_parts[$p]['wmpath']);
				}
			}
			$command .= ($p !== ($cp_count - 1) && $mpr_init_write ? " +delete" : "");
		}
		$modified_mpr_command = hook('modify_mpr_command', '', array($command, $ref, $extension));
		if ($modified_mpr_command != '') {
			$command = $modified_mpr_command;
		}
		$output = run_command($command);
	}
	# For the thumbnail image, call extract_mean_colour() to save the colour/size information
	$target = @imagecreatefromjpeg(get_resource_path($ref, true, "thm", false, "jpg", -1, 1, false, "", $alternative));
	if ($target && $alternative == -1) { # Do not run for alternative uploads 
		extract_mean_colour($target, $ref);
		# flag database so a thumbnail appears on the site
		sql_query("update resource set has_image=1,preview_extension='jpg',preview_attempts=0,file_modified=now() where ref='$ref'");
	} else {
		if (!$target) {
			sql_query("update resource set preview_attempts=ifnull(preview_attempts,0) + 1 where ref='$ref'");
		}
	}

	hook('afterpreviewcreation', '', array($ref, $alternative));
	return true;

}