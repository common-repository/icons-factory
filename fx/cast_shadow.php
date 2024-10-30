<?php 

if (!defined('ABSPATH')) {die( '-1' );}

function cast_shadow_generator($slug, $helper_id, $c) {

    $grad = '<radialGradient id="'.$helper_id.'_grad">
    <stop offset="20%" stop-color="rgba('.$c[0].','.$c[1].','.$c[2].','.($c[3]).')"/>
    <stop offset="80%" stop-color="rgba('.$c[0].','.$c[1].','.$c[2].','.($c[3]/10).')"/>
    <stop offset="100%" stop-color="rgba('.$c[0].','.$c[1].','.$c[2].',0)"/>
    </radialGradient>';

    $shape = '<ellipse cx="256" cy="460" rx="160" ry="20" fill="url(#'.$helper_id.'_grad)"></ellipse>';
    // $shape = '<path fill="url(#'.$helper_id.'_grad)" d="m256.59 428.76c65.85 0 119.24 5.29 119.24 11.82s-53.39 11.82-119.24 11.82-119.24-5.29-119.24-11.82 53.39-11.82 119.24-11.82z"></path>';

    return array($grad, $shape);
}

?>
