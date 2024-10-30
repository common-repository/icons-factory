<?php

    if (!defined('ABSPATH')) exit;

    class ICONSFACTORY_LIBRARY extends ICONSFACTORY {


        function __construct() {

        }

        // $config – bitmask with rendering parameters
        function template($config) {

            $def_cln          = 1 << 0;
            $external_cln     = 1 << 1;
            $def_filters      = 1 << 2;
            $external_filters = 1 << 3;
            $hide_titles      = 1 << 4;
            $hide_lib         = 1 << 5;
            $second_ui        = 1 << 6;

            $htm  = '';
            $def_data = false;
            $second_ui = $config & $second_ui;

            // Get data for default image collection
            if($config & ($def_cln | $def_filters)) {
                $def_data = $this->get_lib_content(
                    'default',  // Library markup
                    true,       // Default images categories filter
                    $second_ui  // Second UI of library items (EDIT, DEL, ...)
                );
            }

            // Library filters markup
            if($config & ($def_filters | $external_filters)) {
                $htm .= '<div class="'.ICNSFCTR_SLUG.'_lib_filters" id="'.ICNSFCTR_SLUG.'_lib_filters" style="display: none;">';
                if($def_data && ($config & $def_filters)) $htm .= $def_data[1];

                if($config & $external_filters) {
                    $external_clns = $this->clns_selector_template(false, false, false);
                    if($external_clns) $htm .= '<div class="'.ICNSFCTR_SLUG.'_lib_external_filters">
                        <h3>My image collections</h3>
                        '.$external_clns.'</div>';
                    else unset($external_clns);
                }

                $htm .= '</div>';
            }

            // Library content markup
            $htm .= '<div class="'.ICNSFCTR_SLUG.'_lib '.ICNSFCTR_SLUG.'_dark_chess_bg'.($config & $hide_titles?'':' '.ICNSFCTR_SLUG.'_lib_state_second_ui').'" id="'.ICNSFCTR_SLUG.'_lib" '.($config & $hide_lib?'':'style="display:none;"').'>';
            if($def_data && ($config & $def_cln)) $htm .= '<div id="'.ICNSFCTR_SLUG.'_lib_def_wrapper" class="'.ICNSFCTR_SLUG.'_lib_state_all">'.$def_data[0].'</div>';
            if($config & $external_cln) $htm .= '<div id="'.ICNSFCTR_SLUG.'_lib_external_wrapper" class="'.ICNSFCTR_SLUG.'_lib_state_all"></div>';
            $htm .= '</div>';

            return $htm;
        }

        // Second UI and other library controllers
        function lib_ctrls_template() {
			return '<div class="'.ICNSFCTR_SLUG.'_top_ctrls">
				<svg class="'.ICNSFCTR_SLUG.'_extra_col_icon"xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M12 .587l3.668 7.568 8.332 1.151-6.064 5.828 1.48 8.279-7.416-3.967-7.417 3.967 1.481-8.279-6.064-5.828 8.332-1.151z"/></svg>
                <div id="'.ICNSFCTR_SLUG.'_lib_filters_toggler" class="'.ICNSFCTR_SLUG.'_l '.ICNSFCTR_SLUG.'_link '.ICNSFCTR_SLUG.'_extra_col_filter_ui">Show filters and <strong>User image collections</strong></div>
                <div id="'.ICNSFCTR_SLUG.'_lib_item_ui_toggler" class="'.ICNSFCTR_SLUG.'_r '.ICNSFCTR_SLUG.'_link">Show titles</div>
            </div>';
        }

        function get_clns_data() {
            $clns_dir = ICNSFCTR_LIBRARY;
            $scan = scandir($clns_dir);
            $normalized_list = array();
            $black_list = array('.','..','.DS_Store');
            if(!(isset($_GET['cln'])?$_GET['cln']==='default':false || ICNSFCTR_EDIT_DEF_LIB_MODE) ) $black_list[] = 'default';
            foreach ($scan as $d) {
                if(!in_array($d, $black_list)) {
                    $info_file_path = $clns_dir.sanitize_file_name($d).'/info.php';
                    $logo_file_path = $clns_dir.sanitize_file_name($d).'/logo.png';
                    $logo_url = ICNSFCTR_LIBRARY_URL.sanitize_file_name($d).'/logo.png';
					if($d==='default') $normalized_list['default'] = array('normal_title'=>'Default');

                    if(file_exists($info_file_path)) {
						$info = require($info_file_path);
					} else {
						continue;
					}

					$normalized_list[$d] = $info;
					$normalized_list[$d]['normal_title'] = $this->cln_id_to_normal_title($info);
					$normalized_list[$d]['logo'] = file_exists($logo_file_path)
						? $logo_url
						: ICNSFCTR_URL.'/img/default_collection_logo.png';

                }
            }
            return $normalized_list;
        }

        function get_lib_content($cln_id, $default, $second_ui) {

            $error = false;
            $cln_dir = ICNSFCTR_LIBRARY.$cln_id.'/';
            $i = 0;

            if(is_dir($cln_dir)) {

                $scan = scandir($cln_dir);
                $lib = '';
                $def_cats = $this->btn_template('cat_selector', array('narrow_btn'), array('cln'=>'default','cat'=>'all'), 'All', 0, false);
                $cats_arr = array();
                $css = '';

                $second_ui_cls = array('narrow_btn'); if(!ICNSFCTR_EDIT_DEF_LIB_MODE&&$cln_id==='default') $second_ui_cls[] = 'hidden';

                foreach ($scan as $f) {
                    if(!in_array($f, array('.','..','.DS_Store','info.php','logo.png'))) {
                        $_f = explode('.', strtolower($f));
                        if($_f[1]==='png') {

                            $category = '';
                            $preview_url = ICNSFCTR_LIBRARY_URL.$cln_id.'/'.$f;
                            $_n = explode('_', $_f[0]);

                            if($default) {
                                $title = $this->string_to_title($_n[2]);
                                $category = ' '.ICNSFCTR_SLUG.'_'.$_n[1];
                                $css .= '.'.ICNSFCTR_SLUG.'_lib_state_'.$_n[1].' .'.ICNSFCTR_SLUG.'_'.$_n[1].' {display: inline-block;} ';
                                if(!in_array($_n[1], $cats_arr)) {
                                    $cats_arr[] = $_n[1];
                                    $def_cats .= $this->btn_template('cat_selector', array('narrow_btn'), array('cln'=>'default','cat'=>$_n[1]), $this->string_to_title($_n[1]), $i, false);
                                }
                            } else {
                                $title = $this->string_to_title($_n[1]);
                            }

                            $lib .= '<label class="'.ICNSFCTR_SLUG.'_lib_item'.$category.'" data-act="sel" data-id="'.$_f[0].'" data-cln-id="'.$cln_id.'">
                                <input name="'.ICNSFCTR_SLUG.'_radio" type="radio" class="'.ICNSFCTR_SLUG.'_lib_item_input" id="'.ICNSFCTR_SLUG.'_'.$_f[0].'" data-act="ignore">
                                <img class="'.ICNSFCTR_SLUG.'_lib_item_png" src="'.$preview_url.'" data-act="sel">
                                <div class="'.ICNSFCTR_SLUG.'_lib_item_second_ui">
                                    <div class="'.ICNSFCTR_SLUG.'_lib_item_title">'.$title.'</div>
                                    '.($second_ui ?
                                    $this->btn_template(false, $second_ui_cls, array('act'=>'edit'), 'EDIT', false, false).
                                    $this->btn_template(false, $second_ui_cls, array('act'=>'del'), 'DEL', false, false)
                                    :'').'
                                </div>
                            </label>';

                        $i++;
                        }
                    }
                }
            } else $error = true;

            if($error===false && $i>0) {
                if($default) { if($css) $css = '<style>'.$css.'</style>'; return array($lib.$css, '<div class="'.ICNSFCTR_SLUG.'_lib_def_filters"><h3>Default library</h3>'.$def_cats.'</div>'); }
                else return $lib;
            } else return '<p class="'.ICNSFCTR_SLUG.'_p '.ICNSFCTR_SLUG.'_empty_col_mess">This collection is empty.<br><a href="'.ICNSFCTR_ADMIN_URL.'_uploadroom">Go to the collections manager to add something</a></p>';
        }

        function cln_id_to_normal_title($info) {
            return $info['author'].' – '.$info['title'].' v'.$info['version'];
        }

        function clns_selector_template($editor_mode, $extra_options, $value) {
            $htm = '';
            $clns_data = $this->get_clns_data();
            foreach ($clns_data as $cln_id => $cln) {
                if($editor_mode) {
                    $htm .= '<option value="'.$cln_id.'"'.($value===$cln_id?' selected':'').'>'.$cln['normal_title'].'</option>';
                } else {
                    if($cln_id!=='default') {
                        $htm .= '<div data-cln="'.$cln_id.'" class="'.ICNSFCTR_SLUG.'_cln_item">
                            '.$this->logo_cln_template($cln, true).'
                        </div>';
                    }
                }
            }
            if($editor_mode) return array('<select class="'.ICNSFCTR_SLUG.'_selectbox" id="'.ICNSFCTR_SLUG.'_cln_selectbox">'.
                $extra_options.$htm.
            '</select>', $clns_data);
            return $htm;
        }


    }

?>
