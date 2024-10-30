<?php

    if (!defined('ABSPATH')) exit;

    class ICONSFACTORY_SVG_PARSER extends ICONSFACTORY {

        private $r_mode;   // Reconstructor mode

        function __construct() {
            $this->r_mode = isset($_GET['cln']);
        }

        // MARKUP: SVGs PARSER

        function template($files, $file_path, $file_name, $library) {

            if($files===false) {

                // RECONSTRUCTOR MODE
                $file_count = 1;
                if(file_exists($file_path)) {
                    $file_content = require_once($file_path);
                    $data = $this->parse($file_content, true);
                } else return 'Sorry. But this item "'.$file_path.'" is corrupted.';

            } else {

                // NORMAL MODE
                $file_content = file_get_contents($file_path);
                $data = $this->parse($file_content, false);
                $file_count = count($files);

            }


            if(!$data) {
                return 'Sorry. But this file cannot be processed.';
            } else {

                $left  = '<h3 class="'.ICNSFCTR_SLUG.'_h3">Shapes</h3>';
                $left .= '<h4 class="'.ICNSFCTR_SLUG.'_h4">Origin point, anim. group, role, & color</h4>';
                // $left .= '<div class="'.ICNSFCTR_SLUG.'_text_row">'.$this->dynamic_grid_template(array('Shape role','Color','Anim. group'), 3).'</div>';
                $left .= '<div id="'.ICNSFCTR_SLUG.'_ctrls_container">'.$data['ui_view'].'</div>';

                $left .= '<div id="'.ICNSFCTR_SLUG.'_trash_container" style="'.(count($data['trash'])===0?'display:none;':'').'margin-top: 25px;"><div class="'.ICNSFCTR_SLUG.'_ui_title">Ignored elements</div>'.implode(', ', $data['trash']).'</p></div>';

                $left .= '<h3 class="'.ICNSFCTR_SLUG.'_h3">Save options</h3>';
                $left .= $this->ui_row_template(
                            $library->clns_selector_template(true,'<option value="0">List of existing collections</option>',($this->r_mode?sanitize_file_name($_GET['cln']):false))[0],
                            'Choose an collection', false, false, false, false, false
                        );
                $left .= $this->ui_row_template(
                    $this->grid_template(
                        array($this->simple_input_template('title_input', 'text', $file_name, false, false, 'Image title', false)),
                        array($this->btn_template('save_btn', false, false, 'SAVE', false, false)),
                        '80_20',
                        false
                    ),
                    'Title of the image', false, false, false, false, false
                );

                if($file_count>1)
                    $left .= $this->ui_row_template(
                    $this->grid_template(
                        array($this->progress_bar_template(1, $file_count, false)),
                        array($this->btn_template('next_btn', false, false, 'NEXT', false, false)),
                        '80_20',
                        false
                    ),
                    'Files. Progress', false, false, false, false, false
                );

                $left .= '<p class="'.ICNSFCTR_SLUG.'_p"><a href="'.ICNSFCTR_ADMIN_URL.'_uploadroom" class="'.ICNSFCTR_SLUG.'_link">Stop this progress and go to the Uploadroom</a></p>';

                $right = '<h3 class="'.ICNSFCTR_SLUG.'_h3">Preview</h3>';
                $right .= $this->navigator_template($data['svg_processed'], 'Processed file', false, true, true, '_processed');
                $right .= $this->navigator_template($data['svg_original'], 'Original file', false, false, false, '_original');
                $right .= $this->canvas_template('processed', 512, 512, false);

                return $this->grid_template(
                array($left, 'id="'.ICNSFCTR_SLUG.'_ctrls_layout"'),
                array($right, 'id="'.ICNSFCTR_SLUG.'_fixed_layout"'),
                '50_50',
                true).
                $this->init_js_module('svg_parser', array(
                    'original_title' => $file_name,
                    'viewbox' => $data['viewbox'],
                    'svg_data_model' => $data['svg_data_model'],
                    'ui_dep_map' => $data['ui_dep_map'],
                    'trash' => $data['trash'],
                    'files' => $files,
                    'r_mode' => $this->r_mode,
                    'edit_def_lib_mode' => ICNSFCTR_EDIT_DEF_LIB_MODE
                ));

            }
        }


        // ACTIONS: SVGs PARSER

        // $file_content - raw SVG code of an unsaved image or a standard PHP data array of an existed image (normal svg_data_model)
        // $r_mode - toggler between an unsaved image and an existed image (r -> reconstructor (reverse) mode)
        function parse($file_content, $r_mode) {
            if($file_content) {

				if(!$r_mode && !$this->is_valid_content($file_content)) {
					return 'Invalid SVG content!';
				}

                $raw_data = $r_mode ? $file_content : simplexml_load_string($file_content);

                if($raw_data) {

                    $viewbox = array(0, 0, $this->image_size, $this->image_size);

                    if(!$r_mode) {
                        $svg_tag_attrs = $raw_data->attributes();
                        $viewbox_attr = (string) $svg_tag_attrs['viewBox'];
                        if($viewbox_attr) $viewbox = explode(' ', $viewbox_attr);
                        else {
                            $w = (string) $svg_tag_attrs['width'];
                            $h = (string) $svg_tag_attrs['height'];
                            if($w && $h)  $viewbox = array(0, 0, $w, $h); // Set viewBox based on SVG width & height attrs
                            unset($w);
                            unset($h);
                        }
                        unset($viewbox_attr);
                        unset($svg_tag_attrs);
                    }

                    $result = array(
                        'viewbox' => implode(' ',$viewbox),
                        'ui_view' => '',
                        'ui_dep_map' => '',
                        'svg_original' => '',
                        'svg_processed' => '',
                        'svg_data_model' => array(),
                        'trash' => array()
                    );

                    $this->recursive_ui_constructor($raw_data, $r_mode, false);

                    $result['ui_view'] = $this->rc_buffer[0];
                    $result['ui_dep_map'] = $this->rc_buffer[1];
                    $result['svg_data_model'] = $this->rc_buffer[2];
                    $result['trash'] = $this->rc_buffer[3];
                    unset($this->rc_buffer);

                    $result['svg_processed'] = $this->get_dynamic_svg(
                        array(
                            'viewbox'   => $viewbox,
                            'animation' => 'each_zoom_spring',
                            // 'animation' => 'each_zoom',
                            // 'fx_doodle' => true,
                            // 'fx_doodle_width' => 2,
                            // 'fx_doodle_color' => '#eee'
                        ),
                        true,
                        ($r_mode ? $file_content : $result['svg_data_model']),
						false,
						false
                    );

                    // If reconstructor mode is active then preview of "svg_original" = preview of "svg_processed"
                    $result['svg_original'] = $this->base64_image_container_template(($r_mode ? $result['svg_processed'] : $file_content),'svg');

                    return $result;

                } else return false;
            } else return false;
        }

        private $rc_buffer = array('', array(), array(), array()); // UI view, UI model, main Data model & Trash (unknown nodes)
        private $rc_max_depth = 5;
        private $rc_actual_depth = 0;
        private $rc_i = 0;
        private $rc_group_state = false;
        private $rc_opened_groups = false;
        private $rc_start_offset = 0;

        // Return: Data model + UI markup
        function recursive_ui_constructor($nodes, $r_mode, $actual_gi) {

            foreach ($nodes as $key => $val) {

                if($r_mode && isset($val['p'])) {
                    $this->rc_buffer[2][] = array('p'=>$val['p']);
                    $this->rc_start_offset++;
                    continue;
                }

                if(!$r_mode) $shape_attrs = $val->attributes();

                $n = $r_mode ? (isset($val['n'])?$val['n']:(isset($val['d'])?'path':'g')) : strtolower($key); // Node name
                $is_base_svg_shape = $r_mode ? true : $this->is_base_svg_shape($n);
                $is_group_end = $r_mode ? (isset($val['g']) ? $val['g']===1 : false) : false;
                $is_group = $n === 'g';

                $d =  $r_mode && isset($val['d'])  ? $val['d']  : ($r_mode?false:$this->normalize_svg_shape_attrs($shape_attrs)); // All parametric attrs of actual shape
                $f =  $r_mode && isset($val['f'])  ? $val['f']  : ($r_mode?false:(isset($shape_attrs['fill']) ? $this->color_to_color_group($shape_attrs['fill']) : 1)); // Shape fill
                $o =  $r_mode && isset($val['o'])  ? $val['o']  : false; // Shape opacity
                $t =  $r_mode && isset($val['t'])  ? $val['t']  : 0; // Shape type (role)
                $m =  $r_mode && isset($val['m'])  ? $val['m'] : false; // Mask (index of parent shape)
                $ag = $r_mode && isset($val['ag']) ? $val['ag'] : false; // Animation group
                $to = $r_mode && isset($val['to']) ? $val['to'] : false; // Transform origin
                $len = $r_mode && isset($val['len']) ? $val['len'] : false; // Total length

                if($n || ($r_mode && $d)) {
                    if($is_base_svg_shape || $is_group) {

                        if($this->rc_actual_depth <= $this->rc_max_depth) {

                            // Label of UI row
                            $label = '<span class="icons_factory_link" data-role="'.($is_group?'group_expander':'inner_ui_expander').'" data-index="'.$this->rc_i.'">'.($is_group?'Group':ucfirst($n)).'</span>';

                            // Inner (hidden) UI for actual shape
                            // Textarea field for Raw code of a shape
                            // Numeric input of shape mask index
                            $inner_ui = $is_group ? '' : '<div class="'.ICNSFCTR_SLUG.'_inner_ui">'.
                                '<p class="'.ICNSFCTR_SLUG.'_ui_title">Main shape attributes</p>'.
                                $this->simple_input_template(
                                    false,
                                    'textarea',
                                    (is_array($d)?$this->assoc_arr_to_attrs_str($d):$d), array('ui'), array('param'=>'d', 'index'=>$this->rc_i),
                                    'SVG markup',
                                    false
                                ).
                                '<p class="'.ICNSFCTR_SLUG.'_ui_title">Shape mask ID</p>'.
                                $this->simple_input_template(
                                    false,
                                    'text',
                                    $m,
                                    false,
                                    array('param'=>'m', 'index'=>$this->rc_i),
                                    'Num. index of a parent shape (not group)',
                                    false
                                ).
                            '</div>';

                            // Collect UI view
                            $this->rc_buffer[0] .=  $this->ui_row_template(
                                $this->dynamic_grid_template(
                                    array(
                                        $this->grid_template(
                                            array($this->transform_origin_ctrl_template('to',$this->rc_i, $to)),
                                            array($this->shape_ctrl_dropdown_template('ag', $this->anim_groups, $this->rc_i, $ag, $is_group)),
                                            '30_70',
                                            false
                                        ),
                                        ($is_group ? '' : $this->shape_ctrl_dropdown_template('t', $this->shape_types, $this->rc_i, $t, $is_group)),
                                        ($is_group ? '' : $this->shape_ctrl_dropdown_template('f', $this->palette_codenames, $this->rc_i, $f, $is_group))
                                    ),
                                    3
                                ).
                                $inner_ui,
                                ($this->rc_i).'. '.$label,  // Title - index & node name
                                false,                        // Row color code (default)
                                $this->rc_group_state,        // Dependency
                                $this->rc_i,                  // Index
								false,
								false
                            );


                            // Collect UI dependency map
                            if($this->rc_group_state && $actual_gi!==false) {
                                $this->rc_buffer[1][$actual_gi]['c'][] = $this->rc_i;
                            }

                            // Collect SVG data model
                            $_temp = $n==='g' ? array('g'=>0) : array('n'=>$n);
                            if($r_mode) {

                                if($ag!==false) $_temp['ag'] = $ag;
                                if($to!==false) $_temp['to'] = $to;

                                if($n==='g') {
                                    $this->rc_group_state = true;
                                    $this->rc_actual_depth++;
                                    $actual_gi = $this->rc_i;
                                    $this->rc_opened_groups[$this->rc_actual_depth] = $this->rc_i;
                                    $this->rc_buffer[1][$this->rc_i] = array('s'=>0,'c'=>array());
                                } else {
                                    if($d!==false) $_temp['d'] = $d;
                                    if($f!==false) $_temp['f'] = $f;
                                    if($o!==false) $_temp['o'] = $o;
                                    if($t!==false) $_temp['t'] = $t;
                                    if($m!==false) $_temp['m'] = $m;
                                    if($len!==false) $_temp['len'] = $len;
                                }

                                $this->rc_buffer[2][] = $_temp;

                                if($is_group_end) {

                                    $this->rc_buffer[2][$this->rc_i + $this->rc_start_offset]['g'] = 1; // Set close group attr to prev shape
                                    $this->rc_actual_depth--;
                                    if($this->rc_actual_depth===0) $this->rc_group_state = false;
                                    else $actual_gi = $this->rc_opened_groups[$this->rc_actual_depth]; // Return last opened group index

                                }

                                $this->rc_i++;

                            } else {
                                if($is_base_svg_shape) {
                                    $_temp['d'] = $d;
                                    $_temp['f'] = $f;
                                    $this->rc_buffer[2][] = $_temp;
                                    $this->rc_i++;
                                } else if($n === 'g') {
                                    $this->rc_buffer[2][] = array('g'=>0);
                                    $this->rc_group_state = true;
                                    $this->rc_actual_depth++;
                                    $this->rc_buffer[1][$this->rc_i] = array('s'=>0,'c'=>array()); // s - state (0- closed, 1-opened); c - childrens
                                    $this->rc_i++;
                                    $this->recursive_ui_constructor($val, $r_mode, $this->rc_i-1); // Start a new loop of hell
                                    $this->rc_actual_depth--;
                                    if($this->rc_actual_depth===0) $this->rc_group_state = false;
                                    $this->rc_buffer[2][$this->rc_i - 1]['g'] = 1; // Set close group attr to prev shape
                                }
                            }
                            unset($_temp);
                        }

                    } else {
                        $this->rc_buffer[3][] = $n; // Collect unknown nodes
                    }
                } else $this->rc_buffer[3][] = var_export($val, true); // Collect corrupted elements
            }
        }

        function color_to_color_group($color) {
            $group_index = array_search(strtoupper($color), $this->palette);
            return $group_index===false ? 1 : $group_index;
        }

        function normalize_svg_shape_attrs($attrs_obj) {
            if($attrs_obj) {

                $d = (string) $attrs_obj->d;
                if($d!=='') return $d;

                $normalized_obj = array();
                foreach ($attrs_obj as $attr => $val) {
                    if($this->is_base_svg_shape_attr($attr)) $normalized_obj[$attr] = (string) $val;
                }
                return $normalized_obj;
            } else return false;
        }

        function is_base_svg_shape($node_name) {
            $base_node_list = array('path', 'polygon', 'polyline', 'circle', 'ellipse', 'rect', 'line', 'text', 'tspan');
            return in_array($node_name, $base_node_list);
        }

        function is_base_svg_shape_attr($attr_name) {
            $base_attr_list = array('points', 'x', 'y', 'x1', 'x2', 'y1', 'y2', 'cx', 'cy', 'r', 'rx', 'ry', 'dx', 'dy', 'width', 'height', 'font-family', 'font-size');
            return in_array($attr_name, $base_attr_list);
        }

        // FILE SAVING

        // First step: Checkings of processed images data
        function save_processed_images_step_1($data) {
            $title = $data->title;
            $original_title = $data->o_title;
            $cln = $data->cln;
            $cln_dir = ICNSFCTR_LIBRARY.$cln.'/';
            $resave = $data->resave;
            if(is_dir($cln_dir)) {
                if($title && $cln && $original_title) {
                    $file_basename = 'ic_'.$title;
                    if(file_exists($cln_dir.$file_basename.'.png') && !$resave) return -1;
                    $svg_data = json_decode(json_encode($data->svg_data), true);
                    $png_data = base64_decode(str_replace(' ','+',$data->png_data));
                    if($svg_data && $png_data) {
                        return $this->save_processed_images_step_2($cln_dir, $file_basename, $original_title, $svg_data, $png_data);
                    } else return 0;
                } else return 0;
            } else return 0;
        }

        // Second step: Saving images
        function save_processed_images_step_2($cln_dir, $file_basename, $original_title, $svg_data, $png_data) {
            $temp_dir = ICNSFCTR_DIR.'temp/';
            $original_svg = $temp_dir.$original_title.'.svg';

            // Save PNG preview
            $png_result = file_put_contents($cln_dir.$file_basename.'.png', $png_data);

            // Save PHP data model
            $php_result = $this->save_array_to_file($svg_data, $cln_dir.$file_basename.'.php');

            // Delete oroginal SVG
            // $original_svg_result = file_exists($original_svg) ? unlink($original_svg) : true;

            // Return results
            return ($png_result && $php_result) ? 1 : 0;
        }


    }

?>
