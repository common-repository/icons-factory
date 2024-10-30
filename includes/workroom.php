<?php

    if (!defined('ABSPATH')) exit;

    class ICONSFACTORY_WORKROOM extends ICONSFACTORY {

        // UI parameters (shortcode attributes)
        public $wp_ui_params;
        public $vc_ui_params;

        // Images presets
        public $presets;

        function __construct() {

            require_once(ICNSFCTR_DIR.'/includes/svg_designer.php');
            if (class_exists('ICONSFACTORY_SVG_DESIGNER')) {
                $this->svg_designer = new ICONSFACTORY_SVG_DESIGNER;
            }

            require_once(ICNSFCTR_DIR.'/includes/library.php');
            if (class_exists('ICONSFACTORY_LIBRARY')) {
                $this->library = new ICONSFACTORY_LIBRARY;
            }
        }

        function room_template($page_slug) {

            $lib_content = $this->library->template(0b1111111);
            $lib_ctrls = $this->library->lib_ctrls_template();
            $svg_designer_htm = $this->svg_designer->template();

            return  $lib_ctrls.
                    $this->layer_template($lib_content, 'workroom', 1, false, false).
                    $this->layer_template($svg_designer_htm[0], 'workroom', 2, true, true).
                    $this->multi_init_js_modules(
                        array(
                            array(
                                'workroom',
                                false
                            ),
                            array(
                                'library',
                                false
                            ),
                            array(
                                'svg_designer',
                                array(
                                    'ui' => $svg_designer_htm[1],
                                    'presets' => $svg_designer_htm[2],
									'actual_preset' => isset($_GET['preset'])
										? sanitize_file_name($_GET['preset'])
										: 0
                                )
                            )
                        )
                    );
        }


    }

?>
