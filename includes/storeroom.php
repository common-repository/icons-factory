<?php

    if (!defined('ABSPATH')) exit;

    class ICONSFACTORY_STOREROOM extends ICONSFACTORY {

        public $presets = false;
        public $registry_link = ICNSFCTR_DIR.'presets/registry.php';

        function __construct() {
            if(file_exists($this->registry_link)) {
                $this->presets = require($this->registry_link);
            }
        }

        // Input: Settings from VC widget & value Or wp page_slug & null
        function room_template($settings = NULL, $actual_preset = NULL) {

			$actual_preset = esc_html($actual_preset);

            $htm = '';
            $items = '';
            $vc_mode = is_array($settings);
            $bg_toggler = '<div class="'.ICNSFCTR_SLUG.'_link" data-act="bg_toggler">Hide recommended backgrounds</div>';


            $actual_preset_exists = false;
            if($actual_preset) {
                $p = explode('@', $actual_preset);
                $actual_preset_exists = isset($this->presets[$p[0]][$p[1]]);
            }

            if($vc_mode) {
            $htm .= '<div class="'.ICNSFCTR_SLUG.'_vc_storeroom_head" data-act="nothing">
                <div data-act="nothing" class="wpb_element_label">Choose an preset or make a new in <a href="'.ICNSFCTR_ADMIN_URL.'" target="_blank">the Workroom</a>.</div>
                <div class="vc_description">'.($actual_preset_exists?'
                    <a class="'.ICNSFCTR_SLUG.'_link" target="_blank" data-act="nothing" data-home="'.ICNSFCTR_ADMIN_URL.'&preset=" href="'.ICNSFCTR_ADMIN_URL.'&preset='.$actual_preset.'" id="'.ICNSFCTR_SLUG.'_selected_preset">Edit the selected preset</a>.
                ':'').$bg_toggler.'.
                </div>
                <input type="hidden" name="preset" class="wpb_vc_param_value" id="'.ICNSFCTR_SLUG.'_target_field_vc" value="'.$actual_preset.'">
            </div>';
            } else {
				$htm .= '<div class="'.ICNSFCTR_SLUG.'_top_ctrls">
				<svg class="'.ICNSFCTR_SLUG.'_extra_col_icon"xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M12 .587l3.668 7.568 8.332 1.151-6.064 5.828 1.48 8.279-7.416-3.967-7.417 3.967 1.481-8.279-6.064-5.828 8.332-1.151z"/></svg>
				<div class="'.ICNSFCTR_SLUG.'_l">'.$bg_toggler.'</div></div><br><br>';
            }


            if(is_array($this->presets)) {
                if(count($this->presets)>0) {
                    foreach ($this->presets as $group_title => $group_data) {

                        $items .= '<h1 class="'.ICNSFCTR_SLUG.'_h1 '.ICNSFCTR_SLUG.'_group_title">'.$group_title.'</h1>';

                        $items .= '<div>';

                        foreach ($group_data as $preset_title => $preset_data) {

                            if($preset_title !== 0) {

                                $preset_id = $group_title.'@'.$preset_title;

                                $preview_color_exists = isset($preset_data['preview_color']) && $preset_data['preview_color'] !== 'transparent';
                                $preview_color = $preview_color_exists ? ' style="background-color: '.$preset_data['preview_color'].'"' : '';

                                $items .= '<label class="'.ICNSFCTR_SLUG.'_preset" data-id="'.$preset_id.'"'.$preview_color.'>';

                                $items .= '<input name="'.ICNSFCTR_SLUG.'_radio" type="radio" class="'.ICNSFCTR_SLUG.'_preset_radio_input" data-id="'.$preset_id.'" value="'.$preset_id.'" '.($actual_preset === $preset_id ? 'checked' : '').'>';

                                $items .= '<div class="'.ICNSFCTR_SLUG.'_preset_inner_wrap">';

                                    $items .= '<h3 class="'.ICNSFCTR_SLUG.'_preset_title">'.$preset_title.'</h3>';

                                    $items .= '<div class="'.ICNSFCTR_SLUG.'_preset_preview">'.$this->get_static_svg($preset_id).'</div>';



                                    $items .= '<div class="'.ICNSFCTR_SLUG.'_preset_second_ui">';

                                        $edit_btn = $this->btn_template(false, array('narrow_btn', 'edit_btn'), array('act'=>'edit'), 'EDIT', false, ICNSFCTR_ADMIN_URL.'&preset='.$preset_id);

                                        if(!$vc_mode) {

                                            $items .= '<strong>Shortcode:</strong><textarea data-act="focus" spellcheck="false" class="'.ICNSFCTR_SLUG.'_field" onfocus="this.select();">'.
                                                $this->preset_shortcode_string(ICNSFCTR_SLUG, $preset_id, (ICNSFCTR_VC?false:$preset_data)).
                                            '</textarea>';

                                            $items .= '<strong>As static Image:</strong><span class="'.ICNSFCTR_SLUG.'_right '.ICNSFCTR_SLUG.'_link" data-act="toggle_format">SVG</span><textarea data-act="focus" spellcheck="false" class="'.ICNSFCTR_SLUG.'_field" onfocus="this.select();"><img src="'.ICNSFCTR_DIR_URL.'presets/'.$preset_id.'.svg" alt="Your text"></textarea>';

                                            if($preview_color_exists) $items .= 'Background: '.$preset_data['preview_color'];

                                            $items .= $this->btns_block_template(array(
                                                /*$this->btn_template(false, false, array('act'=>'copy'), 'COPY', false, false),*/
                                                $edit_btn,
                                                $this->btn_template(false, array('narrow_btn'), array('act'=>'del'), 'DEL', false, false),
                                                $this->btn_template(false, array('narrow_btn'), array('act'=>'dwnld'), false, false, ICNSFCTR_DIR_URL.'presets/'.$preset_id.'.svg')
                                            ));
                                        } else {

                                            // Micro button – 'EDIT'
                                            $items .= $edit_btn;

                                            // Micro button – 'COPY BG'
                                            $items .= $this->btn_template(false, array('narrow_btn', 'copy_bg_btn'),array('act'=>'vc_copy_bg'), 'COPY BG', false, false).
                                            '<input type="text" value="'.($preview_color_exists ? $preset_data['preview_color'] : $this->def_bg).'" class="'.ICNSFCTR_SLUG.'_ghost">';

                                        }


                                    $items .= '</div>'; // End of "preset_second_ui"


                                $items .= '</div>'; // End of "preset_inner_wrap"

                                $items .= '</label>';
                            }
                        }

                        $items .= '</div>';
                    }
					$htm .= $items;

					// A crazy hack
					if($vc_mode) {
						$htm = str_replace('icons_factory_anim', 'icons_factory_anim icons_factory_anim_start', $htm);
					}

                }
            } else $htm .= 'You have no saved images. Please, go to <a href="'.ICNSFCTR_ADMIN_URL.'" target="_blank">Workroom</a> to make some presets.';

            $htm = '<div class="'.ICNSFCTR_SLUG.'_preset_grid" id="'.ICNSFCTR_SLUG.'_preset_grid" data-act="nothing" data-url="'.ICNSFCTR_ADMIN_URL.'">'.$htm.'</div>';

            // Upload case
            if($vc_mode) {

                // $this->btn_template(false, array('success_btn'), false, 'CREATE NEW PRESET', false, ICNSFCTR_ADMIN_URL)
                $htm .= $this->btn_template(false, array('success_btn'), false, 'MANAGE PRESETS', false, ICNSFCTR_ADMIN_URL.'_storeroom').
				$this->btn_template(false, array('success_btn'), false, 'ORDER FRESH IMAGES', false, ICNSFCTR_ADMIN_URL.'_supportroom').
				$this->btn_template(false, array('success_btn'), false, 'DEVELOPER\'S WEBSITE', false, 'https://svgsprite.com');

            } else {
                $htm .= $this->multi_init_js_modules(
                array(
                    array(
                        'storeroom',
                        array(
                            'presets' => $this->presets,
                            'actual_preset' => $actual_preset ? $actual_preset : 0,
                            'init_subscriptions' => 'SVG_DESIGNER'
                        )
                    ),
                    array(
                        'svg_designer',
                        array('only_subscribe' => true)
                    ),
                )
            );
			}

            if($vc_mode) return '<div class="'.ICNSFCTR_SLUG.'_main_container">'.$htm.'</div>';
            else return $htm;
        }

        function presets_handler($data) {

            $group = $data->preset_group;
            $title = $data->preset_title;
            $preset_data = $data->preset_data;
            $act = $data->preset_act;
            $png_data = isset($data->png_data) ? $data->png_data : false;

            if($group && $title) {

                $presets_dir = ICNSFCTR_DIR.'presets/';
                $preset_id = $group.'@'.$title;
                $svg_file = $presets_dir.$preset_id.'.svg';
                $png_file = $presets_dir.$preset_id.'.png';
                $svg_file_exists = file_exists($svg_file);
                $png_file_exists = file_exists($png_file);

                if($act==='add') if(isset($this->presets[$group][$title])) return 'A preset with this title already exists.'; // Break this function

                if($act==='del') {
                    unset($this->presets[$group][$title]);
                    if($svg_file_exists) {
                        unlink($svg_file);
                        if($png_file_exists) unlink($png_file);
                    }
                }
                else {
                    if($preset_data) {
                        $temp_data =  json_decode(json_encode($preset_data), True);
                        if(!isset($temp_data['id'])) $temp_data['id'] = $this->presets[$group][$title]['id']; // Add prev icon ID if it's undefined
                        $this->presets[$group][$title] = $temp_data; // Update presets list in memory

                        // Save SVG image file
                        if(!file_put_contents($svg_file, $this->get_dynamic_svg($temp_data, true, false, $preset_id, true, true))) return 'Problems with SVG file saving.';

                        // Save PNG image file
                        if($png_data) {
                            $png_data = base64_decode(str_replace(' ','+',$png_data));
                            if(!file_put_contents($png_file, $png_data)) return 'Problems with PNG file saving.';
                        }
                    } else return 'No data - no saving';
                }

                // Update file
                if($this->save_array_to_file($this->presets, $this->registry_link)) return 1; // Say OK
                else return 'Some problems with file';


            } else return 'Invalid title of the preset';
        }


    }

?>
