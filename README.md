# Resourcespace Plugin rz_create_previews_im

## The task is to achive 1c images with embedded ICC profiles in the sizes HPR and LPR in 1c,
## convert all other sizes into sRGB, with embedding ICC profiles in the sizes SCR and PRE.

The plugin is for ImageMagick >= 6.9
–––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––––

Normal magick command to do so would be:
========================================
magick -verbose 121_132027ca86ff40d.tif[0] -quiet -depth 8 -flatten  -resize 3000x2000 -profile 121_132027ca86ff40d.tif.icc -intent perceptual -black-point-compensation 
-profile /var/customers/webs/cdc/plugins/rz_create_previews_im/hooks/../../../iccprofiles/sRGB_IEC61966-2-1_black_scaled.icc -quality 90 -type truecolor  out.jpg

Verbose response ImageMagick 7.0
================================
121_132027ca86ff40d.tif[0]=>121_132027ca86ff40d.tif TIFF 3000x2000 3000x2000+0+0 16-bit Grayscale Gray 14.27MiB 0.130u 0:00.134

magick: Incompatible type for "RichTIFFIPTC"; tag ignored. `TIFFFetchNormalTag' @ warning/tiff.c/TIFFWarnings/1037.
magick: Unknown field with tag 34864 (0x8830) encountered. `TIFFReadCustomDirectory' @ warning/tiff.c/TIFFWarnings/1037.
magick: Unknown field with tag 34866 (0x8832) encountered. `TIFFReadCustomDirectory' @ warning/tiff.c/TIFFWarnings/1037.
magick: Unknown field with tag 42033 (0xa431) encountered. `TIFFReadCustomDirectory' @ warning/tiff.c/TIFFWarnings/1037.
magick: Unknown field with tag 42034 (0xa432) encountered. `TIFFReadCustomDirectory' @ warning/tiff.c/TIFFWarnings/1037.
magick: Unknown field with tag 42036 (0xa434) encountered. `TIFFReadCustomDirectory' @ warning/tiff.c/TIFFWarnings/1037.

121_132027ca86ff40d.tif.icc ICC 1x1 1x1+0+0 8-bit sRGB 936B 0.000u 0:00.000

/var/customers/webs/cdc/plugins/rz_create_previews_im/hooks/../../../iccprofiles/sRGB_IEC61966-2-1_black_scaled.icc ICC 1x1 1x1+0+0 8-bit sRGB 3048B 0.000u 0:00.000
121_132027ca86ff40d.tif[0]=>out.jpg TIFF 3000x2000 3000x2000+0+0 8-bit TrueColor sRGB 953735B 1.690u 0:00.558



The Resourcespace command within this Plugin will be something like:
====================================================================
CLI command:  magick convert '/var/customers/webs/cdc/include/../filestore/1/2/1_ee0ba95f8660383/121_132027ca86ff40d.tif'[0] -quiet -depth 8 -flatten  -resize 3000x2000">" -quality 90 -write '/var/customers/webs/cdc/include/../filestore/1/2/1_ee0ba95f8660383/121hpr_a29589fe372d5fc.jpg' -resize 2000x2000">" -write mpr:121 -delete 1 -quality 90 -write '/var/customers/webs/cdc/include/../filestore/1/2/1_ee0ba95f8660383/121lpr_cd3333a19eb7e0b.jpg' -resize 1400x800">" -write mpr:121 -delete 1 -profile /var/customers/webs/cdc/include/../filestore/1/2/1_ee0ba95f8660383/121_132027ca86ff40d.tif.icc -intent perceptual -black-point-compensation -profile /var/customers/webs/cdc/plugins/rz_create_previews_im/hooks/../../../iccprofiles/sRGB_IEC61966-2-1_black_scaled.icc -type truecolor -quality 90 -write '/var/customers/webs/cdc/include/../filestore/1/2/1_ee0ba95f8660383/121scr_c51a3de0be0b42d.jpg' -resize 900x480">" -write mpr:121 -delete 1 -profile /var/customers/webs/cdc/include/../filestore/1/2/1_ee0ba95f8660383/121_132027ca86ff40d.tif.icc -intent perceptual -black-point-compensation -profile /var/customers/webs/cdc/plugins/rz_create_previews_im/hooks/../../../iccprofiles/sRGB_IEC61966-2-1_black_scaled.icc -type truecolor -quality 90 -write '/var/customers/webs/cdc/include/../filestore/1/2/1_ee0ba95f8660383/121pre_1b58784831686c5.jpg' -resize 175x175">" -write mpr:121 -delete 1 -profile /var/customers/webs/cdc/include/../filestore/1/2/1_ee0ba95f8660383/121_132027ca86ff40d.tif.icc -intent perceptual -black-point-compensation -profile /var/customers/webs/cdc/plugins/rz_create_previews_im/hooks/../../../iccprofiles/sRGB_IEC61966-2-1_black_scaled.icc -type truecolor -strip  -quality 90 -write '/var/customers/webs/cdc/include/../filestore/1/2/1_ee0ba95f8660383/121thm_6af4c17e0533452.jpg' -resize 100x75">" -write mpr:121 -delete 1 -profile /var/customers/webs/cdc/include/../filestore/1/2/1_ee0ba95f8660383/121_132027ca86ff40d.tif.icc -intent perceptual -black-point-compensation -profile /var/customers/webs/cdc/plugins/rz_create_previews_im/hooks/../../../iccprofiles/sRGB_IEC61966-2-1_black_scaled.icc -type truecolor -strip  '/var/customers/webs/cdc/include/../filestore/1/2/1_ee0ba95f8660383/121col_f0b39ffbe9d197b.jpg'
