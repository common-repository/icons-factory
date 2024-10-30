<?php 

if (!defined('ABSPATH')) {die( '-1' );}

function patterns($slug, $color, $texture) { 
  
    switch ($texture) {
    // case 2:  
    // $compos = '<rect x="0" y="0" width="3" height="5" />
    //     <rect x="3" y="2" width="5" height="3" />'; break;
    // case 3:  
    // $compos = '<rect x="0" y="0" width="3" height="5" />
    //     <rect x="3" y="2" width="5" height="3" />'; 
    //     break;
    default: 
        $compos = '<rect x="0" y="0" width="3" height="5" /> <rect x="3" y="2" width="5" height="3" />';
      break;
  }

  return '<pattern id="'.$slug.'_pattern" x="0" y="0" width="5" height="5" patternUnits="userSpaceOnUse" >
      <g fill="'.$color.'">
        '.$compos.'
      </g>
    </pattern>';
}

?>
