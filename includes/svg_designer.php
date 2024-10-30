<?php

    if (!defined('ABSPATH')) exit;

    class ICONSFACTORY_SVG_DESIGNER extends ICONSFACTORY {


        function __construct() {

            // Get UI parameters
            $this->wp_ui_params = require_once(ICNSFCTR_DIR.'/includes/wp_ui_params.php');
            $this->vc_ui_params = require_once(ICNSFCTR_DIR.'/includes/vc_ui_params.php');
            if(!ICNSFCTR_VC) $this->wp_ui_params = array_merge($this->wp_ui_params, $this->vc_ui_params);

            // Get presets
            $this->presets = require(ICNSFCTR_DIR.'presets/registry.php');

        }

        function template() {

            // LEFT COL MARKUP

            $left_col = '<h3 class="'.ICNSFCTR_SLUG.'_h3">Design options</h3> <div class="'.ICNSFCTR_SLUG.'_design_options">';
            $js_ui_params = array();
            $row_colors = array();
            $i = 0;

            foreach ($this->wp_ui_params as $key => $p) {

                if(isset($p['only_vc'])) continue; // Ignore non-WP UI (Visual Composer is not defined)

                $ui_row = '';

                $dependency = isset($p['dependency']);

                if(isset($p['wp_row_color'])) $row_colors[$p['param_name']] = $p['wp_row_color'];
                $row_color = isset($p['wp_row_color']) && !$dependency ? $p['wp_row_color'] : ($dependency && isset($row_colors[$p['dependency']['element']])?$row_colors[$p['dependency']['element']]:false);


                if($p['type']!=='preview') {

                    switch ($p['type']) {

						case 'hidden':
							$ui_row .= $this->simple_input_template($p['param_name'], 'hidden', $p['value'], array('ui'), array('param'=>$p['param_name']), false, false);
							break;

                        case 'colorpicker':
                            $ui_row .= $this->simple_input_template($p['param_name'], 'text', $p['value'], array('ui','mcp'), array('param'=>$p['param_name']), false, false);
                            break;

                        case 'textfield':
                            $ui_row .= $this->simple_input_template($p['param_name'], 'text', $p['value'], array('ui'), array('param'=>$p['param_name']), false, false);
                            break;

                        case 'checkbox':
                            $checkbox_options = array('No' => 'false', 'Yes'=>'true');
                            $ui_row .= $this->dropdown_template($p['param_name'], $checkbox_options, true, $i, $p['value']);
                            break;

                        case 'dropdown':
                            $ui_row .= $this->dropdown_template($p['param_name'] ,$p['value'], true, $i, false);
                            break;

                        case 'vc_link':
                            $ui_row .= $this->simple_input_template($p['param_name'], 'text', $p['value'], array('ui'), array('param'=>$p['param_name']), false, false);
                            // $wp_extra_description .= '<br>Example: <b>url: http://example.com | title: Your title | target: _blank | rel:nofollow</b>';
                            break;

                        case 'transform_control':
                            $ui_row .= $this->transform_control_template($p, $p['value']);
                            break;

                        case 'num_control':
                            $ui_row .= $this->simple_num_control_template($p, $p['value']);
                            break;

                        default:
                            $ui_row .= '## UNDEFINED UI ##';
                            break;
                    }

                    $left_col .= $this->ui_row_template($ui_row, ($p['heading']?$p['heading']:''), $row_color, $dependency, $i, false, $p['type'] === 'hidden');
                }


                // Normalized "ui_params" for JS

                if(isset($p['value'])) {
                    $js_ui_params[$p['param_name']] = array(
                        'value' => is_array($p['value'])?array_values($p['value']):$p['value'],
                        'type'  => $p['type']
                    );
                    if(isset($p['dependency']) && isset($js_ui_params[$p['dependency']['element']])) {
                        $parent_name = $p['dependency']['element'];
                        $parent_value = $p['dependency']['value'];
                        $js_ui_params[$p['param_name']]['parent'] = $parent_name;
                        if(isset($js_ui_params[$parent_name]['dependency'])) {
                            if(is_array($parent_value)) {
                                foreach ($parent_value as $key) {
                                    if(isset($js_ui_params[$parent_name]['dependency'][$key])) $js_ui_params[$parent_name]['dependency'][$key][] = $p['param_name'];
                                    else $js_ui_params[$parent_name]['dependency'][$key][] = array($key=>$p['param_name']);
                                }
                            } else {
                                if(isset($js_ui_params[$parent_name]['dependency'][$parent_value])) $js_ui_params[$parent_name]['dependency'][$parent_value][] = $p['param_name'];
                                else $js_ui_params[$parent_name]['dependency'][$parent_value][$parent_value] = $p['param_name'];
                            }
                        } else {
                            if(is_array($parent_value)) {
                                $js_ui_params[$parent_name]['dependency'] = array();
                                foreach ($parent_value as $key) {
                                    $js_ui_params[$parent_name]['dependency'][$key] = array($p['param_name']);
                                }
                            }
                            else $js_ui_params[$parent_name]['dependency'] = array($parent_value=>array($p['param_name']));
                        }
                    }
                }

                $i++;

            } // end of foreach($this->wp_ui_params)

			$left_col .='<p class="icons_factory_p">
			↑ Hold the "Shift key" to change a value faster
			</p>';
            $left_col .= '</div>'; // Close "design_options" container


            // RIGHT COL MARKUP

            $right_col  = '<h3 class="'.ICNSFCTR_SLUG.'_h3">Preview</h3>';

            $right_col .= $this->navigator_template(false, 'Navigator & color manager', true, false, false, false);

            $right_col .= $this->canvas_template('processed', 512, 512, false);

            $preset_selectbox = 'You have no presets';
            if(is_array($this->presets)) {
                if(count($this->presets)>0) {
                    $temp_arr = array('Try a preset'=>0);
                    foreach ($this->presets as $group_title => $group_data) {
                        $temp_arr[$group_title] = '@';
                        foreach ($group_data as $preset_title => $preset_data) {
							$temp_arr[$preset_title] = $preset_title;
                        }
					}

                    $preset_selectbox = $this->dropdown_template('preset_selectbox', $temp_arr, false, false, false);
                    unset($temp_arr);
                }
            }

            $right_col .= $this->grid_template(
                array(
                    '<h3 class="'.ICNSFCTR_SLUG.'_h3">Your shortcode</h3>'.
                    $this->grid_template(
                        array('<textarea spellcheck="false" class="'.ICNSFCTR_SLUG.'_shortcode_text_preview '.ICNSFCTR_SLUG.'_field" rows="3" id="'.ICNSFCTR_SLUG.'_shortcode_output" ></textarea>'),
                        array($this->btn_template('copy_shortcode', false, false, 'COPY', false, false).
                        $this->btn_template('paste_shortcode', false, false, 'PLAY', false, false)),
                        '80_20',
                        false
                    )
                ),

                array(
					'<p class="'.ICNSFCTR_SLUG.'_p">
						Download the result as <a class="'.ICNSFCTR_SLUG.'_link" href="#" id="'.ICNSFCTR_SLUG.'_save_svg">SVG</a> file or bake a <a class="'.ICNSFCTR_SLUG.'_link" href="#" id="'.ICNSFCTR_SLUG.'_save_png" download="image.png">PNG</a>.<br>
						<a href="#" class="'.ICNSFCTR_SLUG.'_link" id="'.ICNSFCTR_SLUG.'_save_png_wp">Save PNG to the Media Library</a><br/>
						<a href="'.ICNSFCTR_DIR_URL.'css/'.ICNSFCTR_SLUG.'.min.css" download class="'.ICNSFCTR_SLUG.'_link" >Get CSS animation script</a><br/>
                        <a href="#" class="'.ICNSFCTR_SLUG.'_link" id="'.ICNSFCTR_SLUG.'_reset_all">Reset curent design settings</a>
					</p>

                    <h3 class="'.ICNSFCTR_SLUG.'_h3">Extra options</h3>'.
                    $this->grid_template(
                        array($preset_selectbox),
                        array($this->btn_template('preset_upd_btn', false, false, 'UPD', false, false)),
                        '80_20',
                        false
                    ).
                    '<div style="margin: 5px 0 10px 0;">
                        <input class="'.ICNSFCTR_SLUG.'_checkbox" type="checkbox" id="'.ICNSFCTR_SLUG.'_preset_mode_checkbox"><label for="'.ICNSFCTR_SLUG.'_preset_mode_checkbox">↑ Without changing of the chosen image</label>
                    </div>'.
                    $this->grid_template(
                        array($this->simple_input_template('preset_title_input', 'text', false, false, false, 'Title of a new preset', false)),
                        array($this->btn_template('preset_add_btn', false, false, 'ADD', false, false)),
                        '80_20',
                        false
                    )

                ),

                'auto',
                false
            );

            return array($this->grid_template(
                array($left_col),
                array($right_col, 'id="'.ICNSFCTR_SLUG.'_fixed_layout"'),
                'smart',
                true // With a separator
            ), $js_ui_params, $this->presets);
        }



    }

?>
