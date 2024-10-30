<?php 

if (!defined('ABSPATH')) {die( '-1' );}

function filter_shadow($slug, $color, $size) { 
  
  switch ($size) {
    case 2:  $style = array(20,10,.15, 3,5,.5); break;
    case 3:  $style = array(40,10,.2, 40,30,.2); break;
    default: $style = array(20,10,.05, 3,3,.25); break;
  }

  return '<filter id="'.$slug.'_shadow" x="-50%" y="-50%" height="200%" width="200%">

    <feGaussianBlur in="SourceAlpha" stdDeviation="'.$style[0].'" result="'.$slug.'_blur"></feGaussianBlur>
    <feOffset dx="0" dy="'.$style[1].'" result="'.$slug.'_shadow_effect_1" in="'.$slug.'_blur"></feOffset>

    <feFlood flood-color="'.$color.'" flood-opacity="'.$style[2].'" result="'.$slug.'_offset_color_1"></feFlood>
    <feComposite in="'.$slug.'_offset_color_1" in2="'.$slug.'_shadow_effect_1" operator="in" result="'.$slug.'_shadow_1"></feComposite>

    <feGaussianBlur in="SourceAlpha" stdDeviation="'.$style[3].'" result="'.$slug.'_blur_2"></feGaussianBlur>
    <feOffset dx="0" dy="'.$style[4].'" result="'.$slug.'_shadow_effect_2" in="'.$slug.'_blur_2"></feOffset>

    <feFlood flood-color="'.$color.'" flood-opacity="'.$style[5].'" result="'.$slug.'_offset_color_2"></feFlood>
    <feComposite in="'.$slug.'_offset_color_2" in2="'.$slug.'_shadow_effect_2" operator="in" result="'.$slug.'_shadow_2"></feComposite>

    <feMerge> 
        <feMergeNode in="'.$slug.'_shadow_1"></feMergeNode>
        <feMergeNode in="'.$slug.'_shadow_2"></feMergeNode>
        <feMergeNode in="SourceGraphic"></feMergeNode>
    </feMerge>

  </filter>';
}

?>
