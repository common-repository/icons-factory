<?php

if (!defined('ABSPATH')) {die( '-1' );}

function overlay_texture($helper_id, $index, $overlay_blending_mode) {

    $overlay_file = ICNSFCTR_DIR.'fx/rasters/overlay-'.$index.'.fx';

	if(!file_exists($overlay_file)) return '';

	$modes = array('overlay','soft-light','screen','multiply');

    // Texture in the base64 format
    return '<image style="mix-blend-mode: '.($modes[$overlay_blending_mode - 1]).'; mask: url(#'.$helper_id.'_overlay);" xlink:href="data:image/png;base64, '.(file_get_contents($overlay_file)).'" x="0" y="0" width="100%" height="100%"/>';

}

?>
