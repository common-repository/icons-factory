<?php

if (!defined('ABSPATH')) exit;

return array(

    array(
        'type'        => ICNSFCTR_SLUG.'_storeroom',
        'param_name'  => 'preset',
        'group'       => __('Storeroom', ICNSFCTR_SLUG),
        'only_vc'     => true
    ),

    array(
        'type'        => 'textfield',
        'heading'     => __('Image box width', ICNSFCTR_SLUG),
        'param_name'  => 'box_size',
        'value'       => '100%',
        'description' => __('Any valid CSS value in px, %, em, rem, vh, calc(% - px).<br>Default value: <b>100%</b> of parent container.', ICNSFCTR_SLUG),
        'group'       => __('Displaying', ICNSFCTR_SLUG)
    ),

    array(
        'type'        => 'dropdown',
        'heading'     => __('Image box alignment', ICNSFCTR_SLUG),
        'param_name'  => 'box_align',
        'value'       => array(
            __('Center', ICNSFCTR_SLUG) => 'center',
            __('Left', ICNSFCTR_SLUG) => 'left',
            __('Right', ICNSFCTR_SLUG) => 'right'
        ),
        'description' => 'Alignment with the parent container.',
        'group'       => __('Displaying', ICNSFCTR_SLUG)
	),

	array(
        'type'        => 'dropdown',
        'heading'     => 'Use custom preview color',
        'param_name'  => 'use_bg',
		'value'       => array(
            'Yes' => 'true',
            'No'  => 'false',
        ),
		'description' => 'Color of the parent container.',
        'group'       => __('Displaying', ICNSFCTR_SLUG)
    ),

    array(
        'type'        => 'css_editor',
        'heading'     => __('Image box position', ICNSFCTR_SLUG),
        'param_name'  => 'box_css',
        'group'       => __('Displaying', ICNSFCTR_SLUG),
        'only_vc'     => true
    ),

    array(
        'type'        => 'textfield',
        'heading'     => __('Custom class name', ICNSFCTR_SLUG),
        'param_name'  => 'box_class',
        'value'       => '',
        'description' => __('Custom CSS class for the Image box to refer it in your custom CSS code.', ICNSFCTR_SLUG),
        'group'       => __('Displaying', ICNSFCTR_SLUG)
    ),

    array(
        'type'        => 'textfield',
        'heading'     => __('Delaying of the animation start', ICNSFCTR_SLUG),
        'param_name'  => 'box_anim_delay',
        'value'       => '',
        'description' => __('You can use any positive numerical value in milliseconds.
                            For example: <b>500</b> (half of one seconds). Default value: <b>0</b>.<br>&nbsp;', ICNSFCTR_SLUG),

		'group'       => __('Displaying', ICNSFCTR_SLUG)
    )

);

?>
