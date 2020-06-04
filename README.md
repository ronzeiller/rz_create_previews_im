# Resourcespace Plugin rz_create_previews_im is used for converting images to archive into proper download images

4c images will be converted to an output profile (most common sRGB) for the preview sizes, but Resourcespace does not take care what the source profile is, so it will deceive wrong colors and wrong gamma if source- and output profiles differ in gamma.

1c (greyscale) images won´t be converted to RGB by Resourcespace. This will matter, when you download LPR (Low printing format) and screen sizes, especially if you have printing profiles embedded in the source images and/or you want to use downloaded images in non color managed environment.

## What this Plugin is doing: 
- embeds source ICC profiles in the sizes HPR and LPR
- convert all other preview sizes into the output profile (acording config)
- embeds output profile into the sizes SCR and PRE

### The plugin is tested with:
- ImageMagick versions >= 6.9
- Resourcespace version 9.2
–––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––

### Normal magick command would be:

magick -verbose original.tif[0] -quiet -depth 8 -flatten  -resize 3000x2000 -profile original.tif.icc -intent perceptual -black-point-compensation -profile /iccprofiles/sRGB_IEC61966-2-1_black_scaled.icc -quality 90 -type truecolor  out.jpg


### The Resourcespace command within this Plugin will be something like:

In Resourcespace, when Imagemagick is used, the command is built this way, that the original input file gets resized for each preview size and then get color converted to the preview images.

** Simplified CLI command **

magick convert 'original.tif'[0] -quiet -depth 8 -flatten  -resize 3000x2000">" -quality 90 
-write 'hpr.jpg' -resize 2000x2000">" -write mpr:121 -delete 1 -quality 90 
-write 'lpr.jpg' -resize 1400x800">" -write mpr:121 -delete 1 -profile original.tif.icc -intent perceptual -black-point-compensation -profile /iccprofiles/sRGB.icc -type truecolor -quality 90 -write 'scr.jpg' 
-resize 900x480">" -write mpr:121 -delete 1 -profile original.tif.icc -intent perceptual -black-point-compensation -profile /iccprofiles/sRGB.icc -type truecolor -quality 90 -write 'pre.jpg' 
-resize 175x175">" -write mpr:121 -delete 1 -profile original.tif.icc -intent perceptual -black-point-compensation -profile /iccprofiles/sRGB.icc -type truecolor -strip  -quality 90 -write 'thm.jpg' 
-resize 100x75">" -write mpr:121 -delete 1 -profile original.tif.icc -intent perceptual -black-point-compensation -profile /iccprofiles/sRGB.icc -type truecolor -strip  'col.jpg'

### Important notice
Hopefully the core will be changed soon, but may be you still need to add `if ( !function_exists('create_previews_using_im') ) {` and an ending `}` to the core in include/image_processing.php at around line 1356 (RS 9.2)

```
if ( !function_exists('create_previews_using_im') ) {

function create_previews_using_im($ref,$thumbonly=false,$extension="jpg",$previewonly=false,$previewbased=false,$alternative=-1,$ingested=false,$onlysizes = array()) {

// leaf everything unchanged

}

} // end function_exists()

````
