# Resourcespace Plugin rz_create_previews_im

## The task is to achive 1c images with embedded ICC profiles in the sizes HPR and LPR in 1c,
## convert all other sizes into sRGB, with embedding ICC profiles in the sizes SCR and PRE.

The plugin is for ImageMagick >= 6.9
–––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––

Normal magick command to do so would be:
========================================
magick -verbose original.tif[0] -quiet -depth 8 -flatten  -resize 3000x2000 -profile original.tif.icc -intent perceptual -black-point-compensation -profile /iccprofiles/sRGB_IEC61966-2-1_black_scaled.icc -quality 90 -type truecolor  out.jpg


The Resourcespace command within this Plugin will be something like:
====================================================================
In Resourcespace, when Imagemagick is used the command is built this way, that the original input file gets resized and converted for each preview size.

Simplified CLI command:  
magick convert 'original.tif'[0] -quiet -depth 8 -flatten  -resize 3000x2000">" -quality 90 
-write 'hpr.jpg' -resize 2000x2000">" -write mpr:121 -delete 1 -quality 90 
-write 'lpr.jpg' -resize 1400x800">" -write mpr:121 -delete 1 -profile original.tif.icc -intent perceptual -black-point-compensation -profile /iccprofiles/sRGB.icc -type truecolor -quality 90 -write 'scr.jpg' 
-resize 900x480">" -write mpr:121 -delete 1 -profile original.tif.icc -intent perceptual -black-point-compensation -profile /iccprofiles/sRGB.icc -type truecolor -quality 90 -write 'pre.jpg' 
-resize 175x175">" -write mpr:121 -delete 1 -profile original.tif.icc -intent perceptual -black-point-compensation -profile /iccprofiles/sRGB.icc -type truecolor -strip  -quality 90 -write 'thm.jpg' 
-resize 100x75">" -write mpr:121 -delete 1 -profile original.tif.icc -intent perceptual -black-point-compensation -profile /iccprofiles/sRGB.icc -type truecolor -strip  'col.jpg'



