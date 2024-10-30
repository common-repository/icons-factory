<?php

if (!defined('ABSPATH')) exit;

return array(

    array(
        'type'        => 'preview',
        'heading'     => 'Preview',
        'param_name'  => 'color_map',
        'value'       => false,
        'description' => 'Color manager. Shapes with similar color. Some single shapes.',
    ),

    array(
        'wp_row_color'=> '#ff5722',
        'type'        => 'dropdown',
        'heading'     => 'Animation',
        'param_name'  => 'animation',
        'description' => 'Animate the image in a moment when they come into view.',
        'value'       => array(
            'No effects' => 'false',
            '"Zoom In"' => 'imagebox_zoom',
            '"Zoom In" for each shape' => 'each_zoom',
            '"Spring Zoom In" for each shape' => 'each_zoom_spring',
            '"Rubber Band"' => 'imagebox_rubber_band',
            '"Fade In Up"' => 'imagebox_fade_in_up'
        ),
    ),

    array(
        'wp_row_color'=> '#ff9800',
        'type'        => 'checkbox',
        'value'       => 'false',
        'heading'     => 'Doodle style',
        'param_name'  => 'fx_doodle',
    ),

    array(
        'type'        => 'num_control',
        'heading'     => 'Outline width',
        'param_name'  => 'fx_doodle_width',
        'value'       => 5,
        'min'         => 1,
        'max'         => 50,
        'dependency'  => array(
            'element' => 'fx_doodle',
            'value'   => 'true'
        ),
    ),

    array(
        'type'        => 'colorpicker',
        'heading'     => 'Outline color',
        'param_name'  => 'fx_doodle_color',
        'value'       => '#795548',
        'dependency'  => array(
            'element' => 'fx_doodle',
            'value'   => 'true'
        ),
    ),

    array(
        'type'        => 'checkbox',
        'value'       => 'true',
        'heading'     => 'Fill very small and detailed elements',
        'description' => 'The filling instead of the outlines for all shapes that are defined as very small and detailed elements.',
        'param_name'  => 'fx_doodle_fill_rule',
        'dependency'  => array(
            'element' => 'fx_doodle',
            'value'   => 'true'
        ),
    ),

    array(
        'type'        => 'checkbox',
        'wp_row_color'=> '#ffc107',
        'value'       => 'false',
        'heading'     => 'Sticker style',
        'param_name'  => 'fx_sticker',
    ),

    array(
        'type'        => 'num_control',
        'heading'     => 'Stroke width',
        'param_name'  => 'fx_sticker_width',
        'value'       => 40,
        'min'         => 1,
        'max'         => 100,
        'dependency'  => array(
            'element' => 'fx_sticker',
            'value'   => 'true'
        ),
    ),

    array(
        'type'        => 'colorpicker',
        'heading'     => 'Stroke color',
        'param_name'  => 'fx_sticker_color',
        'value'       => '#eceff1',
        'dependency'  => array(
            'element' => 'fx_sticker',
            'value'   => 'true'
        ),
    ),

    array(
        'type'        => 'colorpicker',
        'heading'     => 'Color of sticker shadow',
        'param_name'  => 'fx_sticker_shadow_color',
        'value'       => '#000',
        'dependency'  => array(
            'element' => 'fx_sticker',
            'value'   => 'true'
        ),
    ),

    array(
        'type'        => 'dropdown',
        'heading'     => 'Variant of sticker shadow',
        'param_name'  => 'fx_sticker_shadow_size',
        'value'       => array(
            'Small'   => 1,
            'Middle'  => 2,
            'Big soft'=> 3,
            'No shadow'=> 4
        ),
        'dependency'  => array(
            'element' => 'fx_sticker',
            'value'   => 'true'
        ),
    ),

    array(
        'wp_row_color'=> '#cddc39',
        'type'        => 'checkbox',
        'value'       => 'false',
        'heading'     => 'Cast shadow',
        'param_name'  => 'fx_btm_shadow',
    ),

    array(
        'type'        => 'colorpicker',
        'heading'     => 'Color',
        'param_name'  => 'fx_btm_shadow_color',
        'value'       => 'rgba(97,126,140,0.15)',
        'dependency'  => array(
            'element' => 'fx_btm_shadow',
            'value'   => 'true'
        ),
    ),

    array(
        'wp_row_color'=> '#8bc34a',
        'type'        => 'checkbox',
        'value'       => 'false',
        'heading'     => 'Sparks',
        'param_name'  => 'fx_sparks',
    ),

    array(
        'type'        => 'colorpicker',
        'heading'     => 'Color',
        'param_name'  => 'fx_sparks_color',
        'value'       => '#FFF',
        'dependency'  => array(
            'element' => 'fx_sparks',
            'value'   => 'true'
        ),
    ),

    array(
        'type'        => 'dropdown',
        'heading'     => 'Composition',
        'param_name'  => 'fx_sparks_variant',
        'value'       => array(
            '1' => 1,
            '2' => 2,
            '3' => 3,
            '4' => 4,
            '5' => 5,
            '6' => 6
        ),
        'dependency'  => array(
            'element' => 'fx_sparks',
            'value'   => 'true'
        ),
    ),

    array(
        'type'        => 'checkbox',
        'value'       => 'false',
        'heading'     => 'Animation',
        'param_name'  => 'fx_sparks_anim',
        'dependency'  => array(
            'element' => 'fx_sparks',
            'value'   => 'true'
        ),
    ),

    array(
        'wp_row_color'=> '#00bcd4',
        'type'        => 'checkbox',
        'value'       => 'false',
        'heading'     => 'Background shape',
        'param_name'  => 'bg_shape',
        'description' => 'A vector background shape under the image.',
    ),

    array(
        'wp_row_color'=> '#80deea',
        'type'        => 'dropdown',
        'heading'     => 'Shape variant',
        'param_name'  => 'bg_shape_variant',
        'value'       => array(
            'Circle' => 'circle',
            'Perfect circle' => 'perfect-circle',
            'Rounded square' => 'rounded-square',
			'Hexagon' => 'hexagon',
			'Octagon' => 'octagon',
            'Rhombus' => 'rhombus',
            'Chat bubble' => 'chat-bubble',
            'Cross' => 'cross',
            'Brush strokes' => 'brush-stroke',
            'Sprites' => 'sprite',
			'Blobs' => 'blob',
			'Flora' => 'flora',
			'Citrus slices' => 'citrus-slices',
			'Fingerprint' => 'fingerprint',
			'Cells' => 'cells',
			'Swirl' => 'swirl',
			'Brick wall' => 'brick-wall'
        ),
        'dependency'  => array(
            'element' => 'bg_shape',
            'value'   => 'true'
        ),
    ),

    array(
        'type'        => 'dropdown',
        'heading'     => 'Sprite variant',
        'param_name'  => 'bg_shape_sprite_comp',
        'value'       => array(
            '1' => 1,
            '2' => 2,
            '3' => 3,
            '4' => 4
        ),
        'dependency'  => array(
            'element' => 'bg_shape_variant',
            'value'   => 'sprite'
        ),
    ),

    array(
        'type'        => 'dropdown',
        'heading'     => 'Brush stroke variant',
        'param_name'  => 'bg_shape_brush_comp',
        'value'       => array(
            '1' => 1,
            '2' => 2,
            '3' => 3
        ),
        'dependency'  => array(
            'element' => 'bg_shape_variant',
            'value'   => 'brush-stroke'
        ),
    ),

    array(
        'type'        => 'dropdown',
        'heading'     => 'Blob variant',
        'param_name'  => 'bg_shape_blob_comp',
        'value'       => array(
            '1' => 1,
            '2' => 2,
            '3' => 3,
            '4' => 4
        ),
        'dependency'  => array(
            'element' => 'bg_shape_variant',
            'value'   => 'blob'
        ),
	),

	array(
        'type'        => 'dropdown',
        'heading'     => 'Flora variant',
        'param_name'  => 'bg_shape_flora_comp',
        'value'       => array(
            '1' => 1,
            // '2' => 2,
            // '3' => 3
        ),
        'dependency'  => array(
            'element' => 'bg_shape_variant',
            'value'   => 'flora'
        ),
    ),

    array(
        'type'        => 'colorpicker',
        'heading'     => 'Fill color',
        'param_name'  => 'bg_shape_color',
        'value'       => '#ffc107',
        'dependency'  => array(
            'element' => 'bg_shape',
            'value'   => 'true'
        ),
    ),

    array(
        'type'        => 'colorpicker',
        'heading'     => 'Outline color',
        'param_name'  => 'bg_shape_outline_color',
        'value'       => '#ffa000',
        'dependency'  => array(
            'element' => 'bg_shape',
            'value'   => 'true'
        ),
    ),

    array(
        'type'        => 'num_control',
        'heading'     => 'Outline width',
        'param_name'  => 'bg_shape_outline',
        'value'       => 0,
        'min'         => 0,
        'max'         => 150,
        'dependency'  => array(
            'element' => 'bg_shape',
            'value'   => 'true'
        ),
    ),

    array(
        'type'        => 'num_control',
        'heading'     => 'Shape scale',
        'value'       => 100,
        'min'         => 1,
        'max'         => 100,
        'param_name'  => 'bg_shape_size',
        'dependency'  => array(
            'element' => 'bg_shape',
            'value'   => 'true'
        ),
    ),

    array(
        'type'        => 'checkbox',
        'value'       => 'false',
        'wp_row_color'=> '#80deea',
        'heading'     => 'Rough edges',
        'param_name'  => 'bg_shape_dst',
        'dependency'  => array(
            'element' => 'bg_shape',
            'value'   => 'true'
        ),
    ),

    array(
        'type'        => 'num_control',
        'heading'     => 'Rough edges variant',
        'value'       => 1,
        'min'         => 1,
        'max'         => 999,
        'param_name'  => 'bg_shape_dst_seed',
        'dependency'  => array(
            'element' => 'bg_shape_dst',
            'value'   => 'true'
        ),
    ),

    array(
        'type'        => 'dropdown',
        'heading'     => 'Rough edges level',
        'param_name'  => 'bg_shape_dst_lvl',
        'value'       => array(
            'Low'     => 1,
            'Middle'  => 2,
            'High'    => 3
        ),
        'dependency'  => array(
            'element' => 'bg_shape_dst',
            'value'   => 'true'
        ),
    ),

    array(
        'type'        => 'checkbox',
        'value'       => 'false',
        'heading'     => 'Clipping mask mode',
        'param_name'  => 'bg_shape_mask',
        'dependency'  => array(
            'element' => 'bg_shape',
            'value'   => 'true'
        ),
    ),

    array(
        'type'        => 'dropdown',
        'wp_row_color'=> '#2196F3',
        'heading'     => 'Overlay texture',
        'param_name'  => 'overlay',
        'value'       => array(
            'No' => 'false',
            'Watercolor' => 1,
            'Gradient - Warm' => 2,
            'Gradient - Warm & cold' => 3,
            'Gradient - Three tone' => 4,
            'Gradient - Vignette' => 5,
            'Paper' => 6,
            'Canvas' => 7,
            'Dust' => 8
        ),
	),

	array(
        'type'        => 'dropdown',
        'heading'     => 'Blending mode',
        'param_name'  => 'overlay_blending_mode',
        'value'       => array(
            'Overlay'    => 1,
            'Soft Light' => 2,
            'Screen'     => 3,
            'Multiply'   => 4
        ),
        'dependency'  => array(
            'element' => 'overlay',
            'value'   => array(1,2,3,4,5,6,7,8)
        ),
    ),

    array(
        'type'        => 'dropdown',
        'heading'     => 'Composition',
        'param_name'  => 'image_compos',
        'value'       => array(
            'Allow all shapes' => 'false',
            'Disallow shadows and highlights' => 1
        ),
    ),

    array(
        'type'        => 'transform_control',
        'heading'     => 'Custom scale and position of the image content',
        'param_name'  => 'image_transform',
        'value'       => '100,0,0',
	),

	array(
        'type'        => 'hidden',
        'heading'     => 'Image box color',
        'param_name'  => 'preview_color',
		'value'       => false,
		'description' => 'Color of the parent container.',
        'group'       => __('Displaying', ICNSFCTR_SLUG)
	),

	array(
        'type'        => 'textfield',
        'heading'     => 'Image content width',
        'param_name'  => 'image_size',
        'value'       => '100%'
	)

);

?>
