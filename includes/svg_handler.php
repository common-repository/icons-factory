<?php

    if (!defined('ABSPATH')) exit;

    class ICONSFACTORY_SVG_HANDLER extends ICONSFACTORY {

        function __construct() {
            //
        }

        // MAIN FUNCTION

        // Image content (without background and other FX elements)
        function image_code($image_raw_code,
            $helper_id,
            $image_transform,
            $palette,
            $color_map,
            $image_compos,
            $bg_shape_mask,
            $fx_doodle,
            $fx_doodle_color,
            $fx_doodle_width,
            $fx_doodle_fill_rule,
            $fx_sticker,
            $viewbox,
            $animation) {

            $image_code_def = $image_code_use = $image_code_raw_clone_def = '';
            $svg_attrs = array('viewBox'=>$viewbox);
            $bg_shape_mask = $bg_shape_mask ? ' mask="url(#'.$helper_id.'_mask)"' : '';
            $vb_rate = 1; // Proportion of the standard viewbox
            $image_compos = $image_compos ? intval($image_compos) : false;


            // Get custom viewbox from SVG data model
            if(isset($image_raw_code[0]['p'])) {
                $svg_attrs = $image_raw_code[0]['p'];
                if(isset($svg_attrs['viewBox'])) {
                    $viewbox = is_array($svg_attrs['viewBox']) ? $svg_attrs['viewBox'] : explode(' ', $svg_attrs['viewBox']);
                }
            }
            $is_standard_vb = $viewbox === $this->standard_viewbox;

            // Operations in the case of a non-standard viewbox
            if(!$is_standard_vb) {
                $temp_vb_data = $this->normalize_viewbox($viewbox);
                $svg_attrs['viewBox'] = $temp_vb_data[0];

                $vb_rate = $temp_vb_data[1];

                // Correct width of doodle outlines on viewbox rate value
                if($fx_doodle) $fx_doodle_width /= $vb_rate;

                unset($temp_vb_data);
            }

            // Calc main transformations
            $transform = $this->transform($image_transform, $viewbox);

            $group_index = 0;
            $shape_index = 0;
			$masks = array();
			$first_el_is_group = false;

            foreach ($image_raw_code as $index => $el) {

                $index = $shape_index + $group_index;
                $shape = '';
                $shape_id = $helper_id.'_'.$index;
                $shape_type = isset($el['t']) ? $el['t'] : 0;
                $shape_class = ICNSFCTR_SLUG.'_shape'.(isset($el['ag']) ? ' '.ICNSFCTR_SLUG.'_ag '.ICNSFCTR_SLUG.'_ag_'.$el['ag'] : '');

                // Define custom transform origin
				$transform_origin = isset($el['to'])
					? $this->normalize_transform_origin($el['to'])
					: '';

                $is_g = isset($el['g']);

                if(isset($el['d'])) { // d - array or string with all attributes of actual svg shape

                    // Common shapes
                    // Ignore: shadows, higthlights if $image_compos is true
                    if(!($image_compos===1 && ($shape_type===1 || $shape_type===2))) {

                        // Define fills and strokes
						$shape_stroke = $fx_doodle
							? ' stroke="'.$fx_doodle_color.'" stroke-width="'.($fx_doodle_width*(!$fx_doodle_fill_rule&&$shape_type===3?.3:1)).'" stroke-linejoin="round"'
							: '';
                        $base_color = $palette[$el['f']];
                        if($color_map) if(array_key_exists($index, $color_map)) $base_color = $color_map[$index]; // Override base color on custom color from colormap
                        if($fx_doodle && (!$fx_doodle_fill_rule && $shape_type===3)) $base_color = $fx_doodle_color;    // Override base color on doodle color if shape type is "small" or "text"
                        $shape_fill = $base_color;

                        // Define opacity attr
                        $opacity = isset($el['o']) ? ' fill-opacity="'.$el['o'].'"' : '';

                        // Define node name
                        $node_name = isset($el['n']) ? $el['n'] : 'path';

                        // Define all parametric attrs
                        $node_attrs = is_array($el['d']) ? $this->assoc_arr_to_attrs_str($el['d']) : 'd="'.$el['d'].'"';

                        // Define def data
                        $shape = '<'.$node_name.' id="'.$shape_id.'" '.$shape_stroke.' fill="'.$shape_fill.'" '.$node_attrs.' '.$opacity.' class="'.$shape_class.'" data-index="'.($index).'" data-color="'.$el['f'].'"'.$transform_origin.'></'.$node_name.'>';

                        // Wrap shape defined as masked
                        if(isset($el['m'])) {
                            $m_val = $el['m'] + ($is_standard_vb ? 0 : 1); // Images with Non-standard viewbox contains a special record in the data model (first position)
                            if($m_val < count($image_raw_code)) {
                                $m_el = $image_raw_code[$m_val];
                                $m_node_name = $m_el['n'];
                                $m_node_attrs = is_array($m_el['d']) ? $this->assoc_arr_to_attrs_str($m_el['d']) : 'd="'.$m_el['d'].'"';
                                $m_shape_class = isset($m_el['ag']) ? ' '.ICNSFCTR_SLUG.'_ag '.ICNSFCTR_SLUG.'_ag_'.$m_el['ag'] : '';
                                $mask_id = $helper_id.'_'.$m_val.'_shape_mask';
                                if(!in_array($m_val, $masks)) {
                                    // Mask with a clone of parent shape
                                    $image_code_def .= '<mask id="'.$mask_id.'"><'.$m_node_name.' fill="#fff" '.$m_node_attrs.' class="'.$m_shape_class.'"></'.$m_node_name.'></mask>';
                                    // A shorter alternative method with "<use>" tag but its highlighting in the parser is incorrect
                                    // $image_code_def .= '<mask id="'.$mask_id.'"><use stroke-width="1" stroke="#fff" xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#'.$helper_id.'_'.$m_val.'"'.$bg_shape_mask.'/></mask>';
                                    $masks[] = $m_val;
                                }
                                $shape = '<g mask="url(#'.$mask_id.')">'.$shape.'</g>';
                            }
                        }

                        // Collect actual shape
                        $image_code_def .= $shape;

                        $shape_index++;

                        if($fx_sticker) $image_code_raw_clone_def .= '<'.$node_name.' '.$node_attrs.' '.$opacity.' class="'.$shape_class.'"'.$transform_origin.'/>';
                    }

                    // Close the opened group
                    if($is_g) if($el['g']===1) $image_code_def .= '</g>';

                } else {
                    // Open a group
                    if($is_g) {
						if($el['g']===0) {
							$image_code_def .= '<g class="'.$shape_class.'" data-index="'.$group_index.'"'.$transform_origin.'>';
							$group_index++;
						}
						if($index === 0) {
							$first_el_is_group = true;
						}
					}
                }

			}

			// Close the top group if it exists
			if($first_el_is_group) {
				$image_code_def .= '</g>';
			}

			$image_code_def = '<g style="transform-origin: center;" transform-origin="50% 50%" class="'.ICNSFCTR_SLUG.'_base_code" '.$transform.' id="'.$helper_id.'_code"><g transform-origin="50% 50%" class="'.$animation.'">'.$image_code_def.'</g></g>';

            $image_code_use = '<use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#'.$helper_id.'_code"'.$bg_shape_mask.'/>';

			if($fx_sticker) {
                $image_code_raw_clone_def = '<g transform-origin="50% 50%" class="'.ICNSFCTR_SLUG.'_base_code '.$animation.'" '.$transform.' id="'.$helper_id.'_raw-clone">'.$image_code_raw_clone_def.'</g>';
            }

            return array(
				$image_code_def.$image_code_raw_clone_def,
				$image_code_use,
				$svg_attrs,
				$vb_rate
			);

        }

        function normalize_viewbox($viewbox) {

			$vb_rate = 1;

            $vb_w = $viewbox[2];
            $vb_h = $viewbox[3];

			// Non standard!
            // $largest_side = $vb_w > $vb_h ? $vb_w : $vb_h;

            // $vb_rate = $this->image_size / $largest_side;

            // $vb_w *= $vb_rate;
            // $vb_h *= $vb_rate;

            return array(array(0, 0, $vb_w, $vb_h), $vb_rate);
        }

        function normalize_transform_origin($to) {

            if($to==='c') return '';

            $result = '';
            $str_len = strlen($to);

            for($i=0; $i<2;$i++){
              $x = $i < $str_len ? $to[$i] : $to[0];
              $result .= ($i>0?' ':'').($x==='c'?50:($x==='l'||$x==='t'?0:100)).'%';
            }

            return ' style="-webkit-transform-origin: '.$result.' !important; transform-origin: '.$result.' !important; -ms-transform-origin: '.$result.' !important; -webkit-transform-box: fill-box; transform-box: fill-box;"';
        }

        // Transformations of the image content. $t -> s,x,y
        function transform($t, $viewbox) {

            $a = $d = $x = $y = 1;
            $b = $c = $e = $f = 0;

            $viewbox = is_array($viewbox) ? $viewbox : explode(',', $viewbox);

            $vb_w = $viewbox[2];
            $vb_h = $viewbox[3];
            $is_non_standard_vb = $vb_w !== $this->image_size || $vb_h !== $this->image_size;

			// Non standard!
            // if($is_non_standard_vb) {
            //     $vb_uni_size = $vb_w > $vb_h ? $vb_w : $vb_h;
            //     $vb_rate = $is_non_standard_vb ? $this->image_size / $vb_uni_size : 1;
            //     $hs = $this->image_size / 2;
            //     $a = $d = $x = $y = $vb_rate;
            //     $p_w = $vb_w / 100;
            //     $p_h = $vb_h / 100;
            // } else {
                $p_w = $p_h = $this->image_size / 100;
            // }

            if($t) {

                $t = is_array($t) ? $t : explode(',', $t);
                if(count($t)!==3) return '';   // Ignore incorrect matrix value. Format: s,x,y

                $x *=  $t[1] * $p_w;
                $y *= -$t[2] * $p_h;
                $s =  $t[0] / 100;

                $a *= $s;
                $b =  0;
                $c =  0;
                $d *= $s;
                $e += $x;
                $f += $y;

            }

            return $t || $is_non_standard_vb ? $this->transform_wrap($a, $b, $c, $d, $e, $f) : '';

        }

        function transform_wrap($a, $b, $c, $d, $e, $f) {
            return 'transform="matrix('.$a.','.$b.','.$c.','.$d.','.$e.','.$f.')"';
        }

        function palette($color_map) {

            // Base color palette
            $palette = $this->palette;

            // Custom colormap
            // g|3:666|4:000@l|10:2962ff|11,13:00ff84 - Example (g-global,l-local)
            if($color_map) {
                $parts = explode('@', $color_map);
                $color_map = array();
                for ($i=0; $i < count($parts); $i++) {
                    $mode = $parts[$i][0];
                    $color_rules = explode('|', substr($parts[$i], 1));
                    // print_r($color_rules);
                    for ($j=0; $j < count($color_rules); $j++) {
                        $rule_arr = explode(':', $color_rules[$j]);
                        // $color = strrpos($rule_arr[1], ',') ? 'rgba('.$rule_arr[1].')' : '#'.$rule_arr[1];
                        $color = $rule_arr[1];
                        if(strrpos($rule_arr[0], ';')) {
                            $ids = explode(';', $rule_arr[0]);
                            foreach ($ids as $id) {
                                if($mode==='l') $color_map[$id] = $color;
                                else if($mode==='g') $palette[intval($id)] = $color;
                            }
                        } else {
                            if($mode==='l') $color_map[$rule_arr[0]] = $color;
                            else if($mode==='g') $palette[intval($rule_arr[0])] = $color;
                        }
                    }
                }
            }

            return array($palette, $color_map);
        }

        // OTHER FEATURES

        function bg_shape($helper_id,
            $bg_shape_color,
            $bg_shape_outline,
            $bg_shape_outline_color,
            $bg_shape_variant,
            $bg_shape_sprite_comp,
            $bg_shape_brush_comp,
            $bg_shape_blob_comp,
            $bg_shape_flora_comp,
            $bg_shape_size,
            $bg_shape_dst,
            $bg_shape_dst_seed,
            $bg_shape_dst_lvl,
            $bg_shape_mask,
            $viewbox) {

            $bg_small_side = $this->image_size;

            // dst - distorsion of edges

            // There are 3 standards of the background proportion: Square (512x512 default), Landscape (512x316) and Portrait (316x512)
            // It defined in the string of $bg_shape_variant variable "-l", "-p"
            $_t = substr($bg_shape_variant, -2);
            $bg_orient = $_t==='@l'? 3 : ($_t==='@p'? 2 : -1);
            unset($_t);

            $bg_shape_code_def = $bg_shape_code_use = $bg_shape_mask_code_def = $bg_shape_mask_attr = $dst_use = $dst_def = '';

            $shape_variant = '';
            if($bg_shape_variant==='sprite') $shape_variant = $bg_shape_sprite_comp;
            else if($bg_shape_variant==='brush-stroke') $shape_variant = $bg_shape_brush_comp;
            else if($bg_shape_variant==='blob') $shape_variant = $bg_shape_blob_comp;
            else if($bg_shape_variant==='flora') $shape_variant = $bg_shape_flora_comp;

            $bg_shape_full_path = ICNSFCTR_DIR.'fx/bg_shape_'.$bg_shape_variant.($shape_variant?'-'.$shape_variant:'').'.fx';
            $bg_shape_code_def = file_exists($bg_shape_full_path)?file_get_contents($bg_shape_full_path):'';

            $im_small_side = $viewbox[2] < $viewbox[3] ? $viewbox[2] : $viewbox[3];
            $bg_shape_size = $bg_shape_size ? $bg_shape_size : 100;

            if($bg_shape_dst) {
                require(ICNSFCTR_DIR.'/fx/filter_distortion.php');
                $dst_def =  filter_distortion(ICNSFCTR_SLUG, $bg_shape_dst_seed, $bg_shape_dst_lvl);
                $dst_use = 'filter="url(#'.ICNSFCTR_SLUG.'_dst)"';
            }

            if($bg_orient>-1) $bg_small_side = $viewbox[$bg_orient];

            // Coefficient of scale
            $s = $im_small_side / $bg_small_side * ($bg_shape_size / 100);

            $h_s = $this->image_size / 2 * $s;
            if($bg_orient>-1) $h_o_s = ($this->image_size - $this->image_small_size) / 2 * $s;
            $x = 0; //($viewbox[2] / 2) - $h_s + ($bg_orient===2 ? $h_o_s : 0);
            $y = 0; // ($viewbox[3] / 2) - $h_s + ($bg_orient===3 ? $h_o_s : 0);

            $bg_shape_transform = $this->transform_wrap($s, 0, 0, $s, $x, $y);

            if($bg_shape_code_def) {
                $bg_shape_code_def = '<g id="'.$helper_id.'_bg" transform-origin="50% 50%" '.$bg_shape_transform.'><path data-index="-1" d="'.$bg_shape_code_def.'" /></g>'.$dst_def;
                if($bg_shape_mask) {
                    $bg_shape_mask_attr = 'mask="url(#'.$helper_id.'_mask)"';
                    $bg_shape_mask_code_def = '<mask id="'.$helper_id.'_mask"> <use xmlns:xlink="http://www.w3.org/1999/xlink" stroke="#fff" stroke-width="4px" xlink:href="#'.$helper_id.'_bg" fill="#fff" / '.$dst_use.'> </mask>';
                }
                $bg_shape_code_use = '<use xmlns:xlink="http://www.w3.org/1999/xlink" class="'.ICNSFCTR_SLUG.'_bg_shape" xlink:href="#'.$helper_id.'_bg" fill="'.$bg_shape_color.'" '.($bg_shape_outline?'stroke="'.$bg_shape_outline_color.'" stroke-width="'.$bg_shape_outline.'"':'').' '.$dst_use.'/>';
            }

            return array($bg_shape_code_def.$bg_shape_mask_code_def, $bg_shape_code_use);
        }

        function btm_shadow($helper_id, $color) {
            $shadow_code_def = $shadow_code_use = '';
            $color = is_array($color) ? $color : $this->color_str_to_rgba_arr($color);
            $shadow_generator =  ICNSFCTR_DIR.'fx/cast_shadow.php';

            if(file_exists($shadow_generator)) {

                require_once($shadow_generator);
                $_temp = cast_shadow_generator(ICNSFCTR_SLUG, $helper_id, $color);

                $shadow_code_def = $_temp[0].'<g id="'.$helper_id.'_btm_shadow" class="'.ICNSFCTR_SLUG.'_cast_shadow">'.$_temp[1].'</g>';
                $shadow_code_use = '<use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#'.$helper_id.'_btm_shadow"/>';
                unset($_temp);
            }

            return array($shadow_code_def, $shadow_code_use);
        }

        function fx_sticker($helper_id,
            $bg_shape,
            $fx_sticker,
            $fx_sticker_width,
            $fx_sticker_color,
            $fx_sticker_shadow_color,
            $fx_sticker_shadow_size,
            $vb_rate) {

            $fx_sticker_width /= $vb_rate;

            $sticker_code_def = $sticker_code_use = '';
            $fx_sticker_shadow_size = intval($fx_sticker_shadow_size);
            require_once(ICNSFCTR_DIR.'/fx/filter_shadow.php'); // Get code of shadow filter

            $style_attrs = $fx_sticker_width > 1 ? ' fill="'.$fx_sticker_color.'" stroke="'.$fx_sticker_color.'" stroke-width="'.$fx_sticker_width.'" stroke-linecap="round" stroke-linejoin="round" ': '';

            $sticker_code_def = $fx_sticker_shadow_size !== 4 ? filter_shadow($helper_id, $fx_sticker_shadow_color, $fx_sticker_shadow_size) : ''; // 4 - without the shadow
            $sticker_code_use = '<use xmlns:xlink="http://www.w3.org/1999/xlink" class="'.ICNSFCTR_SLUG.'_fx_sticker" xlink:href="#'.$helper_id.($fx_sticker_width>1?'_raw-clone':'_code').'"'.$style_attrs.($fx_sticker_shadow_size !== 4 ? 'filter="url(#'.$helper_id.'_shadow)" ' : '').'/>';

            return array($sticker_code_def, $sticker_code_use);
        }

        function fx_sparks($fx_sparks_color, $fx_sparks_variant, $fx_sparks_anim) {

            $sparks_rules = require(ICNSFCTR_DIR.'/fx/sparks.php'); // Get sparks rules
            $fx_sparks_use = '';
            $spark_shape = $sparks_rules['shape'];
            $compos = $sparks_rules['compos'][intval($fx_sparks_variant)-1];
            $sparks_buf = '';
            $p = $this->image_size / 100; // One percent of global imagebox size
            $hs = ($sparks_rules['shape_size'] / 2) / $p; // Half size of the spark shape

            foreach ($compos as $spark) {
                $s = $spark[0];
                $x = ($spark[1] - $hs) * $p;
                $y = ($spark[2] - $hs) * $p;
                $spark_transform = 'transform="matrix('.$s.',0,0,'.$s.','.$x.','.$y.')"';
                $sparks_buf .= '<g transform-origin="0 0" '.$spark_transform.' class="'.ICNSFCTR_SLUG.'_spark" ><path class="'.ICNSFCTR_SLUG.'_spark_shape" fill="'.$fx_sparks_color.'" d="'.$spark_shape.'"/></g>';
            }

            $fx_sparks_use = '<g transform-origin="50% 50%" class="'.ICNSFCTR_SLUG.'_sparks_group_'.($this->sparks_counter?$this->sparks_counter:1).($fx_sparks_anim!==false?' '.ICNSFCTR_SLUG.'_sparks_animation':'').'">'.$sparks_buf.'</g>';
            if($this->sparks_counter>4) $this->sparks_counter=1; else $this->sparks_counter++;

            return $fx_sparks_use;
        }


    }

?>
