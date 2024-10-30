<?php

    if (!defined('ABSPATH')) exit;

    class ICONSFACTORY_UPLOADROOM extends ICONSFACTORY {

        function __construct() {
            // var_dump($_FILES);
            // var_dump($_POST);
        }

        // MARKUP: UPLOADER PAGE

        function room_template($page_slug, $action_key) {

            $reconstructor_mode = isset($_GET['file']) && isset($_GET['cln']);

            // Connect module: library
            require_once(ICNSFCTR_DIR.'/includes/library.php');
            $library = class_exists('ICONSFACTORY_LIBRARY') ? new ICONSFACTORY_LIBRARY : false;

            if(($action_key==='upload_svgs' && !empty($_FILES)) || $reconstructor_mode) {

                // STATES: "SVGs ARE UPLOADED", "RECONSTRUCTOR MODE" (editing of an existed image file)

                require_once(ICNSFCTR_DIR.'/includes/svg_parser.php');
                if (class_exists('ICONSFACTORY_SVG_PARSER') && $library) {

                    $svg_parser = new ICONSFACTORY_SVG_PARSER;
                    $temp_dir = ICNSFCTR_DIR.'temp/';


                    if($reconstructor_mode) {

						$cln_id = sanitize_file_name($_GET['cln']);
						$file_id = sanitize_file_name($_GET['file']);

                        $clns_dir = ICNSFCTR_LIBRARY.$cln_id.'/';
                        $file_clean_name = str_replace('ic_', '', $file_id);

                        // Get parser markup based on existed image file
                        $svg_parser_template = $svg_parser->template(false, $clns_dir.$file_id.'.php', $file_clean_name, $library);

                    } else {

                        // Clear the temp folder
                        $this->rmdir_recursive($temp_dir, true);

                        // Upload files to the temp folder
                        $files = $this->upload_files($temp_dir, $_FILES['upload_svgs'], array('svg'), true, false);
                        if(!$files) return '<h3 class="'.ICNSFCTR_SLUG.'_h3">Some problems with your files. Call to Guru, please.</h3>
                        <p><a href="'.ICNSFCTR_ADMIN_URL.'_uploadroom">Go back</a></p>';

                        // Get parser markup based on the first uploaded file
                        $svg_parser_template = $svg_parser->template($files, $temp_dir.$files[0]['name'].'.svg', $files[0]['name'], $library);
                    }

                    return $this->layer_template(
                        $svg_parser_template,
                        'uploadroom',
                        1,
                        true, // With an inner wrapper
                        false // Without a scroller
                    );

                } else return $this->bad_module_loading();

            } else {

                // INITIAL STATE
                // Connect module: collenctions manager

                require_once(ICNSFCTR_DIR.'/includes/cln_manager.php');

                if (class_exists('ICONSFACTORY_CLN_MANAGER') && $library) {

                    $cln_manager = new ICONSFACTORY_CLN_MANAGER;

                    // Call action method in the case of a POST request
                    $notice = $action_key ? $cln_manager->$action_key($_POST) : '';

                    // Get all collections data and markup of clns_selector
                    $clns_selector = $library->clns_selector_template(
                        true,
                        '<option value="0">Create a new blank collection</option>
                        <option disabled="true">LIST OF EXISTING COLLECTIONS:</option>',
                        false
                    );

                    // Return markup
                    return $this->init_js_module($page_slug, false). // Init event listeners of upload area buttons
                    $this->layer_template(
                        $this->upload_form_template(count($clns_selector[1])).$cln_manager->template($notice, $clns_selector),
                        'uploadroom',
                        1,
                        true, // With an inner wrapper
                        false // Without a scroller
                    ).$this->layer_template(
                        $library->template(0b1000010),
                        'cln_lib_preview',
                        2,
                        false, // With an inner wrapper
                        false // Without a scroller
                    );
                } else return $this->bad_module_loading();
            }
        }

        // MARKUP: UPLOADS AREA

        function upload_form_template($clns_count) {

            // Uploading form of raw SVGs

            $left = '<label for="'.ICNSFCTR_SLUG.'_upload_svgs" class="'.ICNSFCTR_SLUG.'_btn">UPLOAD SVG FILES</label>
            <p class="'.ICNSFCTR_SLUG.'_p"><strong>Note!</strong> This only works for <a href="https://svgsprite.com/tools/svg-customizer/" target="_blank" title="Online tool to optimize SVG files">pre-optimized</a> square SVG images (512 x 512 px), without any strokes.</p>
            <p class="'.ICNSFCTR_SLUG.'_p"><a href="'.ICNSFCTR_DIR_URL.'img/library-standards.png" target="_blank">Library standards</a></p>';

            $upload_svgs_btn_attrs = array('type_filter'=>'svg');
            if($clns_count===0) $upload_svgs_btn_attrs['disabled'] = true;

            $left .= $this->simple_input_template('upload_svgs', 'file', false, array('file_choose', 'auto_submit', 'multiple'), $upload_svgs_btn_attrs, false, false);

            // Uploading form of a premade image collection (one ZIP file)

            $right = '<label for="'.ICNSFCTR_SLUG.'_upload_cln" class="'.ICNSFCTR_SLUG.'_btn">UPLOAD A PREMADE SET</label>
            <p class="'.ICNSFCTR_SLUG.'_p">If you have a <strong>ZIP</strong> file with a premade collection of images then upload it here.</p>
            <p class="'.ICNSFCTR_SLUG.'_p"> </p>';

            $right .= $this->simple_input_template('upload_cln', 'file', false, array('file_choose', 'auto_submit'), array('type_filter'=>'zip'), false, false);

            $edit_def_lib_mode_attr = ICNSFCTR_EDIT_DEF_LIB_MODE ? '&edit_def_lib_mode' : '';

            $content = $this->grid_template(
                array($this->form_template('upload_svgs', ICNSFCTR_ADMIN_URL.'_uploadroom'.$edit_def_lib_mode_attr, $left)),
                array($this->form_template('upload_cln', ICNSFCTR_ADMIN_URL.'_uploadroom'.$edit_def_lib_mode_attr, $right)),
                '50_50',
                false
            );

            return '<h3 class="'.ICNSFCTR_SLUG.'_h3">Uploads area</h3>'.$content;
        }


    }

?>
