<?php 

if (!defined('ABSPATH')) {die( '-1' );}

function filter_distortion($slug, $seed, $level) {
  $level = intval($level);
  switch ($level) {
    case 2:  $d = array(0.05, 15); break; // Middle
    case 3:  $d = array(0.01, 30); break; // High
    default: $d = array(0.1,  10); break; // low
  }
  return '<filter id="'.$slug.'_dst">
      <feTurbulence type="fractalNoise" baseFrequency="'.$d[0].'" result="noise" seed="'.$seed.'"/>
      <feDisplacementMap in="SourceGraphic" in2="noise" scale="'.$d[1].'" />
    </filter>';
}

?>

