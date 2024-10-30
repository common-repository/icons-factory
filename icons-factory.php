<?php

    /*
        Plugin Name: Icons Factory
        Version: 1.6.12
        Description: Free design editor to create, stylize and animate icons and other website graphics to your taste.
        Author: Artemy
        Author URI: https://svgsprite.com
        Email: letter2artemy@gmail.com
		License: GPL-2.0+
		License URI: http://www.gnu.org/licenses/gpl-2.0.txt
	*/

    if (!defined('ABSPATH')) exit;

    require_once(ABSPATH.'wp-admin/includes/plugin.php');

    // Define global plugin variables
    define('ICNSFCTR_DEBUG', 0);
    define('ICNSFCTR_SLUG', 'icons_factory');
    define('ICNSFCTR_NAME', 'Icons Factory');
    define('ICNSFCTR_CLASS_NAME', 'ICONSFACTORY');
    define('ICNSFCTR_NAME_STR', 'icons-factory');
    define('ICNSFCTR_URL', plugins_url('', __FILE__));
    define('ICNSFCTR_DIR', plugin_dir_path(__FILE__));
    define('ICNSFCTR_DIR_URL', plugin_dir_url( __FILE__ ));
    define('ICNSFCTR_VERSION', '1.6.12');
    define('ICNSFCTR_ADMIN_URL', admin_url('admin.php?page='.ICNSFCTR_SLUG));
    define('ICNSFCTR_VC', is_plugin_active('js_composer/js_composer.php'));
    define('ICNSFCTR_LIBRARY', ICNSFCTR_DIR.'library/');
    define('ICNSFCTR_LIBRARY_URL', ICNSFCTR_URL.'/library/');

    // An opportunity to edit the default image library for developers
    define('ICNSFCTR_EDIT_DEF_LIB_MODE', 0);

    // Main class of the plugin
    class ICONSFACTORY {

        // Keys from a POST request
        public $action_key;
        public $notice = false;

        // Base palette
        public $palette = array('#000','#FFF','#E6E6E6','#F16552','#E55041');

        // Default background color
        public $def_bg = '#ECEFF1';

        // Palette codenames
        // array('Neutral'=>1,'Neutral shading (grey)'=>2,'Active (red)'=>3,'Active shading (dark red)'=>4);
        public $palette_codenames = array('White'=>1,'Grey'=>2,'Red'=>3,'Dark red'=>4);

        // Animation groups
        public $anim_groups = array('a','b','c','d','e','f','g','h');

        // Types of vector shapes
        public $shape_types = array('Base'=>0,'Shading'=>1,'Highlighting'=>2,'Very small or detailed'=>3,'Delete it'=>-1);

        // Count of sparks group - need for the sparks animation
        public $sparks_counter;

        // Global constant to calc some shapes transformations
        public $image_size = 512;

        public $image_small_size = 316;

        public $standard_viewbox = array(0,0,512,512);

        public $images_counter = 0;

        private $wp_plugin_logo = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" enable-background="new 0 0 20 20" xml:space="preserve"><path fill="#FFFFFF" d="M10.3 17.3C10 17.3 2 7.3 2 7s2.7-4 3-4c.4-.3 9.8-.3 10.2 0 .3 0 3.3 3.6 3.3 4s-8 10.3-8.4 10.3z"/></svg>';

		public $temp_data = false;

		public $plugin_files = array();

        function __construct(){

            if (!empty($_POST)) {
                if (isset($_POST[ICNSFCTR_SLUG.'_json_data'])) {

					// "AJAX" REQUESTS

					if(!$this->is_valid_content($_POST[ICNSFCTR_SLUG.'_json_data'])) {
						echo 'Invalid content!';
						exit;
					}

                    $data = json_decode($_POST[ICNSFCTR_SLUG.'_json_data']);

					if($data) {

                        // Save "ajax" action key
                        $this->action_key = isset($data->action) ? $data->action : false;

                        switch ($this->action_key) {

                            case 'parse_svg_file':
                                require_once(ICNSFCTR_DIR.'/includes/svg_parser.php');
                                if (class_exists('ICONSFACTORY_SVG_PARSER')) {
									$svg_parser = new ICONSFACTORY_SVG_PARSER;
									$raw_file_data = file_get_contents($data->file_path);

									if(!$this->is_valid_content($raw_file_data)) exit;

                                    $parsed_data = $svg_parser->parse($raw_file_data, false);
                                    if($parsed_data) echo json_encode($parsed_data);
                                    else echo 0;
                                } else echo 0;
                                exit;
                                break;

                            case 'save_svg_file':
                                require_once(ICNSFCTR_DIR.'/includes/svg_parser.php');
                                if (class_exists('ICONSFACTORY_SVG_PARSER')) {
                                    $svg_parser = new ICONSFACTORY_SVG_PARSER;
                                    echo $svg_parser->save_processed_images_step_1($data);
                                } else echo 0;
                                exit;
                                break;

                            case 'save_file_to_wp_media':
								$this->temp_data = $data;
								// Validate and save a PNG file to WP Media Library
                                add_action('init', array($this, 'save_file_to_wp_media'));
								break;

							case 'do_shortcode':
                                $shortcode_attrs = get_object_vars($data->shortcode);
                                echo $this->shortcode_handler($shortcode_attrs, true);
                                exit;
                                break;

                            case 'presets_handling':
                                require_once(ICNSFCTR_DIR.'/includes/storeroom.php');
                                if (class_exists('ICONSFACTORY_STOREROOM')) {
                                    $storeroom = new ICONSFACTORY_STOREROOM;
                                    echo $storeroom->presets_handler($data);
                                } else echo 0;
                                exit;
                                break;

                            case 'get_lib_content':
                                require_once(ICNSFCTR_DIR.'/includes/library.php');
                                if (class_exists('ICONSFACTORY_LIBRARY')) {
                                    $library = new ICONSFACTORY_LIBRARY;
                                    echo $library->get_lib_content($data->cln_id, false, true);
                                } else echo 0;
                                exit;
                                break;

                            case 'delete_files':
                                echo $this->delete_files($data->files);
                                exit;
                                break;

                            default:
                                echo 'Unknown action key '.$this->action_key;
                                break;
                        }

                    }

                } else {

                    // CLASSIC POST REQUESTS

                    // Save "post" action key
					$this->action_key = isset($_POST['action'])
						? sanitize_text_field($_POST['action'])
						: false;

                    if($this->action_key) {

                        if($this->action_key==='support') {

                            // Check and send an user request
                            $this->check_and_send_support_request($_POST);

                        } else if(strrpos($this->action_key, 'dwnld')>-1) {

                            // Download a file (quick file saver on the client side, download a dir as a zip file)
                            $this->dwnld_file($_POST, $_SERVER['REQUEST_URI']); exit;

                        }
                    }
                }
            }

            add_action('admin_menu', array($this,'add_admin_pages'));
            add_shortcode(ICNSFCTR_SLUG, array($this,'shortcode_handler'));
			if(ICNSFCTR_VC) add_action('vc_before_init', array($this, 'vc_addon'));

            // Allow shortcodes in text widget
            add_filter('widget_text', 'do_shortcode');

			add_action('template_redirect', array($this,'add_style_sheets'));
			add_action('template_redirect', array($this,'add_animation_script'));

        }

        // ------------------------------------------------------------------------- WP CLIENT SIDE

        // Client css
        function add_style_sheets() {
            wp_enqueue_style(ICNSFCTR_SLUG.'_client_css', ICNSFCTR_URL.'/css/'.ICNSFCTR_SLUG.'.min.css', false, ICNSFCTR_VERSION);
        }

        // Client js
        function add_animation_script() {
            wp_enqueue_script(ICNSFCTR_SLUG.'_client_js', ICNSFCTR_URL.'/js/'.ICNSFCTR_SLUG.'.min.js', false, ICNSFCTR_VERSION, true);
        }

        // ------------------------------------------------------------------------- WP ADMIN PLUGIN PAGE

        function add_admin_pages() {
            add_menu_page(
                ICNSFCTR_NAME.' - menu',
                ICNSFCTR_NAME,
                'manage_options',
                ICNSFCTR_SLUG,
                array($this,'render_wp_page'),
                'data:image/svg+xml;base64,'.base64_encode($this->wp_plugin_logo)
            );

            add_submenu_page(ICNSFCTR_SLUG, 'Workroom', 'Workroom', 'manage_options', ICNSFCTR_SLUG, array($this,'render_wp_page') );
            add_submenu_page(ICNSFCTR_SLUG, 'Presets', 'Presets', 'manage_options', ICNSFCTR_SLUG.'_storeroom', array($this,'render_wp_page') );
            add_submenu_page(ICNSFCTR_SLUG, 'Uploads', 'Uploads', 'manage_options', ICNSFCTR_SLUG.'_uploadroom', array($this,'render_wp_page') );
            add_submenu_page(ICNSFCTR_SLUG, 'Feedback & Updates', 'Feedback ✌️ Updates', 'manage_options', ICNSFCTR_SLUG.'_supportroom', array($this,'render_wp_page') );

            wp_enqueue_style(ICNSFCTR_SLUG.'_mcp', ICNSFCTR_URL.'/css/MCP.min.css', false, ICNSFCTR_VERSION);
            wp_enqueue_script(ICNSFCTR_SLUG.'_mcp', ICNSFCTR_URL.'/js/MCP.min.js', false, ICNSFCTR_VERSION, true);

            wp_enqueue_style(ICNSFCTR_SLUG.'_client_css', ICNSFCTR_URL.'/css/'.ICNSFCTR_SLUG.'.min.css', false, ICNSFCTR_VERSION);
			wp_enqueue_style(ICNSFCTR_SLUG.'_admin', ICNSFCTR_URL.'/css/'.ICNSFCTR_SLUG.'_app.min.css', false, ICNSFCTR_VERSION);

			// Note! Tune the Preprocess to decompress it:
            wp_enqueue_script(
				ICNSFCTR_SLUG.'_admin',
				ICNSFCTR_URL.'/js/'.ICNSFCTR_SLUG.'_app.min.js',
				false,
				ICNSFCTR_VERSION,
			true);

            $this->add_animation_script();
        }

        function render_wp_page() {
            if(isset($_GET['page'])) {

                // Default markup
                $htm = 'Page rendering error.';

                // Get page slug
				$page_slug = $_GET['page'] === ICNSFCTR_SLUG
					? 'workroom'
					: str_replace(ICNSFCTR_SLUG.'_', '', sanitize_text_field($_GET['page']));

                // Connect page module
                require_once(ICNSFCTR_DIR.'includes/'.$page_slug.'.php');
                $module_name = 'ICONSFACTORY_'.strtoupper($page_slug);
                if (class_exists($module_name)) {
                    $module_instance = new $module_name;

					// Default notice value is "false"
                    //See "trigger_initial_notice" method in the JS App
					$initial_notice = $this->notice;

					// Sanitize a notice content
					if(isset($_GET['notice'])){
						$initial_notice = explode('|', $_GET['notice']);
						for($i=0; $i<count($initial_notice); $i++) {
							$initial_notice[$i] = sanitize_text_field($initial_notice[$i]);
						}
					}

                    // Concat general javascript
                    $htm = $this->init_js_module(false,
                        array(
                            'plugin_url'     => ICNSFCTR_ADMIN_URL,
                            'plugin_dir'     => ICNSFCTR_DIR,
                            'plugin_dir_url' => ICNSFCTR_DIR_URL,
                            'action_key'     => $this->action_key,
                            'base_palette'   => $this->palette,
                            'notice'         => $initial_notice
                        )
                    );

                    // Get template of actual page
                    $htm .= $module_instance->room_template($page_slug, $this->action_key);
				}

                // Render admin page content
                echo '<div class="wrap admin_mode '.ICNSFCTR_SLUG.'_main_container'.(ICNSFCTR_EDIT_DEF_LIB_MODE?' '.ICNSFCTR_SLUG.'_edit_def_lib_mode':'').'">'.$htm.'
                <div id="'.ICNSFCTR_SLUG.'_response" class="'.ICNSFCTR_SLUG.'_notification"></div></div>';
            }
        }

        function navigator_template($content, $title, $footer_mode, $dark_mode, $wireframe_mode, $suffix) {

			if(!$this->is_valid_content($content)) return 'Invalid content';

            $top_right_ui = '';

            if($footer_mode) {
                $footer_info = '<div class="'.ICNSFCTR_SLUG.'_link" id="'.ICNSFCTR_SLUG.'_reset_gcm">Reset global repainting</div>';
                $footer_info .= '<div class="'.ICNSFCTR_SLUG.'_link" id="'.ICNSFCTR_SLUG.'_reset_lcm">Reset local repainting</div>';
                $footer_info .= '<div class="'.ICNSFCTR_SLUG.'_navigator_footer_text" id="'.ICNSFCTR_SLUG.'_navigator_footer_text">"Mouseover" to see extra info. "Click" to recolor something.</div>';
                $top_right_ui = '<div class="'.ICNSFCTR_SLUG.'_link '.ICNSFCTR_SLUG.'_right" id="'.ICNSFCTR_SLUG.'_recolor_mode_toggler">Global repainting</div>
                <input name="color_map" class="'.ICNSFCTR_SLUG.'_ui" id="'.ICNSFCTR_SLUG.'_color_map" data-param="color_map" type="hidden" value="" >';
            }

            return '<div class="'.ICNSFCTR_SLUG.'_navigator'.$suffix.'" data-role="ui_row">
                        <div class="'.ICNSFCTR_SLUG.'_ui_title" >
                            '.esc_attr($title).$top_right_ui.'
                        </div>

                        <div class="'.ICNSFCTR_SLUG.'_navigator_wrap">
                            <div class="'.ICNSFCTR_SLUG.'_navigator_screen '.($suffix?ICNSFCTR_SLUG.'_navigator_screen'.$suffix.($dark_mode?' '.ICNSFCTR_SLUG.'_dark_bg':'').($wireframe_mode?' '.ICNSFCTR_SLUG.'_wireframe':''):'').'" id="'.ICNSFCTR_SLUG.'_navigator_screen'.$suffix.'">'.$content.'</div>
                            '.($footer_mode?'<div class="'.ICNSFCTR_SLUG.'_navigator_footer"><div id="'.ICNSFCTR_SLUG.'_navigator_footer">'.$footer_info.'</div></div>':'').'
                        </div>
                    </div>';
        }

        function canvas_template($id, $w, $h, $is_visible) {
            return '<canvas id="'.ICNSFCTR_SLUG.'_canvas_'.esc_attr($id).'" width="'.esc_attr($w).'" height="'.esc_attr($h).'"'.($is_visible?'':' style="display: none;"').'></canvas>';
        }

        function simple_num_control_template($settings, $value) {

			$value = sanitize_text_field($value);
			$param_name = esc_attr($settings['param_name']);

            $lbl = isset($settings['label']) ? esc_attr($settings['label']) : false;
			$step = isset($settings['step']) ? esc_attr($settings['step'])  : 1;

            return $this->num_controllers_wrap_template(
				$param_name,
				$value,
				$this->single_num_controller_template(
					$param_name,
					$lbl,
					0,
					$value,
					$step,
					esc_attr($settings['min']),
					esc_attr($settings['max'])
				),
				false
			);
        }

        function transform_control_template($settings, $value) {

			$value = sanitize_text_field($value);
			$param_name = esc_attr($settings['param_name']);

            $value_arr = explode(',', $value);
            $controllers_htm =  $this->single_num_controller_template($param_name, 'Scale', 0, $value_arr[0], 1, 1, 512);
            $controllers_htm .= '<div class="'.ICNSFCTR_SLUG.'_numctrl_locked_content" id="'.ICNSFCTR_SLUG.'_numctrl_locked_content">';

			$controllers_htm .= $this->single_num_controller_template(
				$param_name,
				'X-axis',
				1,
				$value_arr[1],
				1,
				-512,
				512
			);

			$controllers_htm .= $this->single_num_controller_template(
				$param_name,
				'Y-axis',
				2,
				$value_arr[2],
				1,
				-512,
				512
			);

            $controllers_htm .= '</div>';

            return $this->num_controllers_wrap_template(
				$param_name,
				$value,
				$controllers_htm,
				true
			);
        }

        function transform_origin_ctrl_template($key, $index, $value) {
            $htm = '';
            $value = $value ? sanitize_text_field($value) : 'c';
            $list = array('lt','ct','rt','lc','c','rc','lb','cb','rb');
            foreach ($list as $key) {
                $htm .= '<div class="to_ctrl_'.esc_attr($key).($value===$key?' '.ICNSFCTR_SLUG.'_sm_active':'').'" data-role="ui" data-param="to" data-to="'.esc_attr($key).'" data-index="'.esc_attr($index).'"></div>';
            }
            return '<div class="'.ICNSFCTR_SLUG.'_to_ctrl_wrap">'.$htm.'</div>';
        }

		// Only for validated data
		function num_controllers_wrap_template($param, $value, $controllers_htm, $group) {
            return '<div class="'.ICNSFCTR_SLUG.'_numctrl_wrap'.($group?' '.ICNSFCTR_SLUG.'_numctrl_group':'').'" id="'.ICNSFCTR_SLUG.'_'.$param.'_numctrl_wrap" data-param="'.$param.'" data-def_value="'.$value.'">
            <input name="'.$param.'" class="wpb_vc_param_value '.ICNSFCTR_SLUG.'_ui" id="'.ICNSFCTR_SLUG.'_'.$param.'" type="hidden" value="'.$value.'" data-param="'.$param.'">
            '.$controllers_htm.'
            </div>';
        }

		// Only for validated data
		function single_num_controller_template($param, $lbl, $index, $value, $step, $min, $max) {
            return '<div class="'.ICNSFCTR_SLUG.'_numctrl">
                        '.($lbl?'<div class="'.ICNSFCTR_SLUG.'_numctrl_lbl">'.$lbl.':</div>':'').'
                        <div class="'.ICNSFCTR_SLUG.'_numctrl_ui">
                            <div data-act="-"  data-param="'.$param.'" data-target="'.$index.'" class="'.ICNSFCTR_SLUG.'_btn '.ICNSFCTR_SLUG.'_numctrl_handle">&minus;</div>
                            '.$this->simple_input_template($param, 'text', false, array('numctrl_input'), array('def_value'=>$value, 'param'=>$param, 'target'=>$index, 'min'=>$min, 'max'=>$max, 'step'=>$step), $value, $index).'
                            <div data-act="+" data-param="'.$param.'" data-target="'.$index.'" class="'.ICNSFCTR_SLUG.'_btn '.ICNSFCTR_SLUG.'_numctrl_handle">&plus;</div>
                        </div>
                    </div>';
        }

        function simple_input_template($id, $type, $value, $classes_arr, $data_attrs_arr, $placeholder, $index) {

			$value = sanitize_text_field($value);
			$index = $index!==false ? '_'.esc_attr($index) : '';

			$base = 'input';
            $name_attr = esc_attr($id);
            $is_textarea = $type==='textarea';

            if($is_textarea) {
                $base = 'textarea';
                if($classes_arr) array_push($classes_arr, 'textarea');
                else $classes_arr = array('textarea');
            }

            // Multi file input
            if($classes_arr) {
                if(in_array('multiple', $classes_arr)) {
                    $base .= ' multiple="multiple"';
                    $name_attr .= '[]';
                }
            }

            return '<'.$base.' spellcheck="false" class="'.ICNSFCTR_SLUG.'_field'.$this->join_css_classes($classes_arr).'" name="'.$name_attr.'" type="'.esc_attr($type).'" '.($placeholder?'placeholder="'.esc_attr($placeholder).'"':'').' id="'.ICNSFCTR_SLUG.'_'.esc_attr($id).$index.'" '.(!$is_textarea?'value="'.$value.'" ':'').$this->join_data_attrs($data_attrs_arr).'>'.($is_textarea?$value.'</'.$base.'>':'');
        }

        function btn_template($id, $classes_arr, $data_attrs_arr, $content, $index, $href) {

			$index = $index!==false ? '_'.esc_attr($index) : '';

            // Button as a link
            $base = $href ? 'a href="'.esc_url($href).'" target="_blank"' : 'button';

            // Hidden btn
            if($classes_arr) {
                if(in_array('hidden', $classes_arr)) $base .= ' style="display: none;"';
            }

            // "Delete" - btn with special highlighting
            if($data_attrs_arr) {
                if(isset($data_attrs_arr['act'])) {

                    $act = esc_attr($data_attrs_arr['act']);
                    if(!$classes_arr) $classes_arr = array();

                    // Button with alerting style
                    if(strrpos($act, 'del') !== false) $classes_arr[] = 'alert_btn';

                    // Button with a "file download image"
                    if(strrpos($act, 'dwnld') !== false) {
                        $classes_arr[] = 'dwnld_btn';
                        $content = '<svg data-act="dwnld" xmlns="http://www.w3.org/2000/svg" width="15" height="16"><path data-act="dwnld" d="M12 5.9H9.4V2H5.6v3.9H3l4.5 4.5L12 5.9zm-9 5.8V13h9v-1.3H3z"/></svg>';
                        if($href) $base .= 'download';
                    }

                }
            }

            return '<'.$base.' class="'.ICNSFCTR_SLUG.'_btn'.$this->join_css_classes($classes_arr).'" '.($id?'id="'.ICNSFCTR_SLUG.'_'.esc_attr($id).($index).'" ':'').$this->join_data_attrs($data_attrs_arr).'>'.$content.'</'.$base.'>';
        }

        function dropdown_template($id, $options, $ui_mode, $index, $value) {

			$id = esc_attr($id);
			$value = sanitize_text_field($value);

			$options_htm = '';
            $id_attr = $id.($index!==false?'_'.esc_attr($index):'');
			$open_group = false;

            foreach ($options as $key => $val) {
                if($val==='@') {

                    // Close the prev options group
                    if($open_group) $options_htm .= '</optgroup>';

                    // Open a new group
                    $options_htm .= '<optgroup label="'.esc_attr($key).'">';
                    $open_group = $key;
                    continue;
                }
                $val = is_array($val) ? $key : esc_attr($val);
                if($val==='-') $options_htm .= '<option disabled="true">'.$key.'</option>';
                else $options_htm .= '<option value="'.($open_group?$open_group.'@':'').$val.'"'.($value===$val?' selected':'').'>'.(is_numeric($key)?$val:$key).'</option>';
            }

            // Close the last options group
            if($open_group) $options_htm .= '</optgroup>';

            return '<select name="'.$id_attr.'" class="'.ICNSFCTR_SLUG.'_selectbox '.($ui_mode?ICNSFCTR_SLUG.'_ui':ICNSFCTR_SLUG.'_'.$id).'" id="'.ICNSFCTR_SLUG.'_'.$id_attr.'" data-role="ui" data-param="'.$id.'" '.($index!==false?'data-index="'.esc_attr($index).'"':'').'>'.$options_htm.'</select>';
        }

        function shape_ctrl_dropdown_template($key, $list, $index, $value, $is_group) {
			$value = sanitize_text_field($value);
			$index = esc_attr($index);
            if($key==='ag') $list = array_merge(array('–'=>'–'), $list);
            return $this->dropdown_template($key, $list, true, $index, $value);
        }

        function progress_bar_template($actual_val, $total_val, $index) {
			$actual_val = sanitize_text_field($actual_val);
			$total_val = sanitize_text_field($total_val);
            return '<div class="'.ICNSFCTR_SLUG.'_pbar" id="'.ICNSFCTR_SLUG.'_pbar'.($index?'_'.esc_attr($index):'').'">
                <div class="'.ICNSFCTR_SLUG.'_pbar_indicator" style="width:'.($actual_val / $total_val * 100).'%;"></div>
                <div class="'.ICNSFCTR_SLUG.'_pbar_label"><span>'.$actual_val.'</span><span> of </span><span>'.$total_val.'</span></div>
            </div>';
        }

        function switch_template($label, $state) {
			$label = esc_attr($label);
			$state = esc_attr($state);
            $label = '<div class="'.ICNSFCTR_SLUG.'_ui_title">'.$label.'</div>';
            $state = $state ? ' '.ICNSFCTR_SLUG.'_switch_on' : '';
            return $label.'<div class="'.ICNSFCTR_SLUG.'_switch'.$state.'"></div>';
        }

        // ------------------------------------------------------------------------- VC ADD-ON FRAME

        function vc_addon(){

            $vc_ui_params = require_once(ICNSFCTR_DIR.'/includes/vc_ui_params.php');
            require_once(ICNSFCTR_DIR.'/includes/storeroom.php');

            if (class_exists('ICONSFACTORY_STOREROOM')) {

                $storeroom = new ICONSFACTORY_STOREROOM;

                vc_add_shortcode_param(ICNSFCTR_SLUG.'_storeroom', array($storeroom, 'room_template'), ICNSFCTR_URL.'/js/'.ICNSFCTR_SLUG.'_vc_storeroom_init.js');

                $config = array(
                    'name'        => __(ICNSFCTR_NAME, ICNSFCTR_SLUG),
                    'base'        => ICNSFCTR_SLUG,
					'category'    => __('Content', ICNSFCTR_SLUG),
					'icon'        => ICNSFCTR_URL.'/img/addon_icon.png',
                    'image'       => ICNSFCTR_URL.'/img/addon_icon.png',
                    'description' => __('Customizable SVG images', ICNSFCTR_SLUG),
                    'admin_enqueue_js' => ICNSFCTR_URL.'/js/'.ICNSFCTR_SLUG.'_vc_admin.js',
                    'front_enqueue_js' => ICNSFCTR_URL.'/js/'.ICNSFCTR_SLUG.'_vc_front.js',
                    'js_view'     => 'ViewElement_'.ICNSFCTR_SLUG,
                    'params'      => $vc_ui_params
                );

                vc_map($config);
            }
        }

        // ------------------------------------------------------------------------- MAIN FUNCTIONAL

        function shortcode_handler($attrs, $admin_page_mode){

			$inline_svg = false;
            $class = ICNSFCTR_SLUG.'_wrapper';
            $style = '';
            $data_attrs = '';
            $error_mess = false;

            if(isset($attrs['preset'])) {
                $error_mess = 'preset title: <b>'.esc_attr($attrs['preset']).'</b>';
                $inline_svg = $this->get_static_svg(esc_attr($attrs['preset']));// Static mode
            } else {
                if(isset($attrs['id'])) {
                    $error_mess = 'id="<b>'.$attrs['id'].'</b>" without preset';
                    $inline_svg = $this->get_dynamic_svg(
						$attrs,
						$admin_page_mode,
						false,
						false,
						false
					);
                }
            }

            // Break "shortcode_handler" function and Return default message if SVG data is empty
            if($inline_svg===false) return '<b>'.ICNSFCTR_NAME.'</b> – An empty item!'; // with '.($error_mess ? $error_mess: 'undefined ID')

            // Aligment with the parent container
            if(isset($attrs['box_align'])) $class .= ' '.ICNSFCTR_SLUG.'_align_'.esc_attr($attrs['box_align']);

            // CSS box from Visual Composer
            if (isset($attrs['box_css']))
                if (!$admin_page_mode && ICNSFCTR_VC) $class .= ' '.esc_attr(apply_filters(VC_SHORTCODE_CUSTOM_CSS_FILTER_TAG, vc_shortcode_custom_css_class(esc_attr($attrs['box_css']), ' '), ICNSFCTR_SLUG, $attrs));

            // User custom CSS class
            if(isset($attrs['box_class'])) $class .= ' '.esc_attr($attrs['box_class']);

			// Imagebox width
			if(isset($attrs['box_size'])) {
				$style .= 'width:'.esc_attr($attrs['box_size']).';';
			}

			// Recomended background color (the preview background)
			if(isset($attrs['preview_color'])) {
				$preview_color = esc_attr($attrs['preview_color']);
				if($preview_color &&
					$preview_color !== 'transparent' &&
					$preview_color !== 'false' &&
					$preview_color !== false &&
					(!isset($attrs['use_bg']) || (isset($attrs['use_bg']) && $attrs['use_bg'] !== 'false'))
				) {
					if(substr($preview_color,-2,1) !== 0)
					$style .= 'background-color:'.esc_attr($attrs['preview_color']);
				}
			}

            // Animation delay in miliseconds
            if(isset($attrs['box_anim_delay'])) $data_attrs .= ' data-anim_delay="'.esc_attr($attrs['box_anim_delay']).'"';

            // All style attributes of Imagebox
            if($style) $style = ' style="'.$style.'"';

            // All classes of Imagebox
            $class = ' class="'.$class.'"';

            return '<div'.$class.$style.$data_attrs.'>'.$inline_svg.'</div>';
        }

        function get_static_svg($title) {
            $preset_file_full_path = ICNSFCTR_DIR.'presets/'.esc_attr($title).'.svg';
            if(file_exists($preset_file_full_path)) {
				$data = file_get_contents($preset_file_full_path);
				// Force class adding, the reason: VC works with a fuckin Iframe
				if(isset($_GET['vc_editable']) || isset($_GET['vc_action'])) {
					$data = str_replace('icons_factory_anim', 'icons_factory_anim icons_factory_anim_start', $data);
				}
                return $data;
            } else {
				return false;
			}
        }

        function get_dynamic_svg($attrs, $admin_page_mode, $uploadroom_data, $preset, $no_anim_start) {

            $id = isset($attrs['id']) ? $attrs['id'] : 'temp';
            $dir = isset($attrs['dir']) ? $attrs['dir'] : 'default';
            $fallback_png_url = ($preset ? ICNSFCTR_DIR_URL.'presets/'.$preset : ICNSFCTR_DIR_URL.'library/'.$dir.'/'.$id).'.png';
            $orient = ''; // Square, Portrait ('@p') or Landscape ('@l') orientation

            if(!$uploadroom_data) {
				$image_full_path = ICNSFCTR_LIBRARY.$dir.'/'.$id.'.php';
                if(!file_exists($image_full_path)) return 'EMPTY IMAGE';
            }

            extract(shortcode_atts(array(
                'viewbox'                 => $this->standard_viewbox,
                'color_map'               => false,
                'animation'               => false,
                'fx_doodle'               => false,
                'fx_doodle_width'         => 5,
                'fx_doodle_color'         => '#795548',
                'fx_doodle_fill_rule'     => false,
                'fx_sticker'              => false,
                'fx_sticker_width'        => 40,
                'fx_sticker_color'        => '#f2f2f2',
                'fx_sticker_shadow_color' => '#000',
                'fx_sticker_shadow_size'  => 1,
                'fx_btm_shadow'           => false,
                'fx_btm_shadow_color'     => array(97,126,140,.15),
                'fx_sparks'               => false,
                'fx_sparks_color'         => '#fff',
                'fx_sparks_variant'       => 1,
                'fx_sparks_anim'          => false,
                'bg_shape'                => false,
                'bg_shape_color'          => '#ffc107',
                'bg_shape_outline'        => false,
                'bg_shape_outline_color'  => '#ffa000',
                'bg_shape_variant'        => 'circle',
                'bg_shape_sprite_comp'    => 1,
                'bg_shape_brush_comp'     => 1,
                'bg_shape_blob_comp'      => 1,
                'bg_shape_flora_comp'      => 1,
                'bg_shape_size'           => false,
                'bg_shape_dst'            => false,
                'bg_shape_dst_seed'       => 1,
                'bg_shape_dst_lvl'        => 1,
                'bg_shape_mask'           => false,
				'overlay'                 => false,
				'overlay_blending_mode'   => 1,
                'image_transform'         => false,
				'image_compos'            => false,
				'image_size'              => '100%',
            ), $attrs));

            unset($attrs);

            require_once(ICNSFCTR_DIR.'/includes/svg_handler.php');
            if (class_exists('ICONSFACTORY_SVG_HANDLER')) {
                $svg_handler = new ICONSFACTORY_SVG_HANDLER;
            }

            // All data for the result
            $result = array(array(),array(),array(),array()); // 0 - svg tag attrs, 1 - all <defs>, 2 - all <use>, 3 - fallback PNG
            // $helper_id = $id.'_'.date('H-i-s');
            $this->images_counter++;
            $helper_id = $id.'_'.date('i-s').'_'.$this->images_counter;

            // Base color palette
            $temp_palette = $svg_handler->palette($color_map);
            $palette = $temp_palette[0];
            if($color_map) $color_map = $temp_palette[1];
            unset($temp_palette);

            // Animation
            if($animation) {
                $animation = ' '.ICNSFCTR_SLUG.'_anim_'.$animation.' '.ICNSFCTR_SLUG.'_anim';

				if($admin_page_mode ||
					isset($_GET['vc_editable']) ||
					isset($_GET['vc_action'])
				) {
					if($no_anim_start !== true) $animation .= ' '.ICNSFCTR_SLUG.'_start_anim';
				}
			}


            // Get Image code
            $image_code = $svg_handler->image_code(
                ($uploadroom_data ? $uploadroom_data : require($image_full_path)),
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
                $animation
            );

            // Proportion of actual viewbox
            $vb_rate = $image_code[3];

            // Override SVG viewBox attr
            $viewbox = $image_code[2]['viewBox'];
			$orient = $viewbox[2] !== $this->image_size
				? '@p'
				: ($viewbox[3] !== $this->image_size ? '@l' : '');

            // Background shape
            if($bg_shape) {
                $temp_bg_shape = $svg_handler->bg_shape(
                    $helper_id,
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
                    $viewbox
                );
                $result[1][] = $temp_bg_shape[0]; $result[2][] = $temp_bg_shape[1];
                unset($temp_bg_shape);
            }

            // FX - Cast shadow (in the bottom of the image)
            if($fx_btm_shadow) {
                $fx_btm_shadow = $svg_handler->btm_shadow($helper_id, $fx_btm_shadow_color);
                $result[1][] = $fx_btm_shadow[0]; $result[2][] = $fx_btm_shadow[1];
                unset($fx_btm_shadow);
            }

            // FX - Sticker style
            if($fx_sticker) {
                $fx_sticker_temp = $svg_handler->fx_sticker(
                $helper_id,
                $bg_shape,
                $fx_sticker,
                $fx_sticker_width,
                $fx_sticker_color,
                $fx_sticker_shadow_color,
                $fx_sticker_shadow_size,
                $vb_rate);
                $result[1][] = $fx_sticker_temp[0]; $result[2][] = $fx_sticker_temp[1];
                unset($fx_sticker_temp);
            }


            // Image code
            $result[1][] = $image_code[0]; $result[2][] = $image_code[1];
            unset($image_code);


            // FX - Sparks
            if($fx_sparks) {
                $result[2][] = $svg_handler->fx_sparks($fx_sparks_color, $fx_sparks_variant, $fx_sparks_anim); // Only for <use> group
                unset($fx_sparks);
            }

            // FX - Overlay textute
            if($overlay) {
                require_once(ICNSFCTR_DIR.'fx/overlays.php');
                $overlay = overlay_texture($helper_id, $overlay, $overlay_blending_mode);
                $result[1][] = '<mask id="'.$helper_id.'_overlay">'.implode(' ', $result[2]).'</mask>';
                $result[2][] = $overlay;
                unset($overlay);
            }

            // Class, data-attributes and style for the <svg> tag
            $result[0] = array('class="'.ICNSFCTR_SLUG.'"', ' viewBox="'.(is_array($viewbox)?implode(' ', $viewbox):$viewbox).'"');

            // Fallback PNG
            $result[3] = '<image class="'.ICNSFCTR_SLUG.'_fallback_png" src="'.wp_normalize_path($fallback_png_url).'" width="'.esc_attr($viewbox[2]).'" height="'.esc_attr($viewbox[3]).'" />';

            return $this->svg_container_template($result, $image_size);
        }

        function svg_container_template($data, $image_size) {
			$svg_markup = '<svg '.implode('', $data[0]).'
			'.($image_size ? 'style="width:'.esc_attr($image_size).';"' : '').'
			xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1">'.$data[3].'<defs>'.implode('', $data[1]).'</defs>'.implode('', $data[2]).'</svg>';

			return $svg_markup;
        }


        // ------------------------------------------------------------------------- HELPERS

        function layer_template($content, $name, $index, $inner_wrap, $layer_ctrls) {

            if($inner_wrap) $content = '<div class="'.ICNSFCTR_SLUG.'_layer_innerwrap'.($inner_wrap===1?' '.ICNSFCTR_SLUG.'_innerwrap_narrow':'').'">'.$content.'</div>';

            return '<div id="'.ICNSFCTR_SLUG.'_'.$name.'_layer_'.$index.'" class="'.ICNSFCTR_SLUG.'_layer '.ICNSFCTR_SLUG.'_'.$name.'_layer_'.$index.'">
				'.($layer_ctrls?'<div class="'.ICNSFCTR_SLUG.'_layer_ctrls" id="'.ICNSFCTR_SLUG.'_layer_ctrls">
					<div class="'.ICNSFCTR_SLUG.'_other_projects" >
					<a href="https://wordpress.org/support/plugin/icons-factory/reviews/" target="_blank">
					<img width="50" src="'.ICNSFCTR_URL.'/img/avatar100.png"/>Make Review</a>
					</div>
                    <div class="'.ICNSFCTR_SLUG.'_scroller">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                        <path d="M12 5.83l3.17 3.17 1.41-1.41-4.58-4.59-4.59 4.59 1.42 1.41 3.17-3.17zm0 12.34l-3.17-3.17-1.41 1.41 4.58 4.59 4.59-4.59-1.42-1.41-3.17 3.17z"/>
                        </svg>
                    </div>
                </div>':'').$content.'
            </div>';
		}

        // $left_col and $right_col – array(content [,id])
        // $proportion – '50_50', '80_20', '85_15'
        // $separator – boolean
        function grid_template($left_col, $right_col, $proportion, $separator) {
            return '<div class="'.ICNSFCTR_SLUG.'_grid '.ICNSFCTR_SLUG.'_grid_'.$proportion.'">

                <div'.(count($left_col)===2?' '.$left_col[1]:'').'>
                    '.$left_col[0].'
                </div>

                '.($separator?'<div class="'.ICNSFCTR_SLUG.'_grid_separator">&nbsp;</div>':'').'

                <div'.(count($right_col)===2?' '.$right_col[1]:'').'>
                    '.$right_col[0].'
                </div>

            </div>';
        }

        function dynamic_grid_template($content, $max_cols_in_row) {
            $len = count($content);
            $h = $len >= $max_cols_in_row? $max_cols_in_row: $len;
            $col_width = 100 / $h;
            $htm = '';
            for ($i=0; $i < $len; $i++) {

                if($i % $h === 0) {
                    $p_l = 0; $p_r = 4;
                } else {
                    if(($i+1) % $h === 0) {$p_l = 4; $p_r = 0;}
                    else {$p_l = 3; $p_r = 3;}
                }

                $htm .= '<div style="width:'.$col_width.'%; padding-right:'.$p_r.'px; padding-left:'.$p_l.'px;">'.$content[$i].'</div>';
            }
            return '<div class="'.ICNSFCTR_SLUG.'_dynamic_grid">'.$htm.'</div>';
        }

        function form_template($id, $action_url, $content) {
            return '<form enctype="multipart/form-data" method="post" class="'.ICNSFCTR_SLUG.'_upload_form" action="'.$action_url.'" id="'.ICNSFCTR_SLUG.'_'.$id.'_form">
                <input type="hidden" name="action" value="'.$id.'">
                '.$content.'
            </form>';
        }

        function btns_block_template($btns_arr) {
            return '<div class="'.ICNSFCTR_SLUG.'_btns_block">'.implode('&nbsp;&nbsp;', $btns_arr).'</div>';
        }

        function inline_script_template($content) {
            return '<script>
                document.addEventListener(\'DOMContentLoaded\', function(){
                    '.$content.'
                });
            </script>';
        }

        function init_js_module($page_slug, $props) {
            $init_code = $this->normalize_js_module_data($page_slug, $props);
            return $this->inline_script_template($init_code);
        }

        function multi_init_js_modules($modules) {
            $inits_code = '';
            for ($i=0; $i < count($modules); $i++) {
                $inits_code .= $this->normalize_js_module_data($modules[$i][0], $modules[$i][1]);
            }
            return $this->inline_script_template($inits_code);
        }

        function normalize_js_module_data($page_slug, $props) {
            $page_slug = $page_slug ? ', \''.strtoupper($page_slug).'\'' : '';
            if($props) $props = '\''.json_encode($props).'\'';
            else $props = 'false';
            return ICNSFCTR_CLASS_NAME.'.init('.$props.''.$page_slug.');';
        }

        // Default
        function base64_image_container_template($raw_file_data, $ext) {
            $ext = $ext==='svg'?'svg+xml':$ext;
            return '<div class="'.ICNSFCTR_SLUG.'_base64_img" style="background-image: url(data:image/svg+xml;base64,'.base64_encode($raw_file_data).');"></div>';
        }

        function ui_row_template($ui_fields, $title, $color_code, $dependency, $index, $secondary_ui, $is_hidden) {
			$color_code = $color_code!==-1 ? '<div class="'.ICNSFCTR_SLUG.'_ui_color_code" style="background-color: '.($color_code?$color_code:'#eceff1').';" data-role="ui_row_el" data-index="'.$index.'"></div>':'';

			if($secondary_ui) $secondary_ui = '<div class="'.ICNSFCTR_SLUG.'_link '.ICNSFCTR_SLUG.'_right" data-role="secondary_ui" data-index="'.$index.'">'.$secondary_ui.'</div>';

            return '<div '.($is_hidden?'style="display:none" ':'').'class="'.ICNSFCTR_SLUG.'_ui_row '.ICNSFCTR_SLUG.'_ui_'.($dependency?'':'in').'dependent" data-role="ui_row'.($secondary_ui?'_g':'').'" data-index="'.$index.'">
                '.$color_code.($title?'<div class="'.ICNSFCTR_SLUG.'_ui_title" data-role="ui_row_el" data-index="'.$index.'">'.$title.$secondary_ui.'</div>':'').'
                <div class="'.ICNSFCTR_SLUG.'_ui_fields" data-role="ui_row_el" data-index="'.$index.'">'.$ui_fields.'</div>
            </div>';
        }

        function logo_cln_template($data, $selector_mode) {
			$src = $selector_mode ? $data['logo'] : ($data?$data:ICNSFCTR_URL.'/img/default_collection_logo.png');

			$license = isset($data['license'])
				? '<a href="'.esc_url($data['license']).'" target="_blank">License</a> '
				: '';

			$email = isset($data['email'])
				? '<a href="mailto:'.sanitize_email($data['email']).'">Email</a>'
				: '';

            $label = $selector_mode ?
            '<span>'.esc_html($data['title']).' v'.esc_html($data['version']).'</span><span>by '.esc_html($data['author']).'</span><span>'.($license || $email ? $license.$email : '').'</span><span><a href="'.esc_url($data['uri']).'" target="_blank">Website</a></span>':
            'Square image,<br>140 pixels,<br>PNG';

            $img = '<img width="70" height="70" src="'.$src.'" id="'.ICNSFCTR_SLUG.'_cln_logo_img" />';

            // Upload image mode
            if(!$selector_mode) $img = '<label for="'.ICNSFCTR_SLUG.'_cln_logo_input">'.$img.'</label>
            <input class="'.ICNSFCTR_SLUG.'_file_choose" type="file" id="'.ICNSFCTR_SLUG.'_cln_logo_input" value="'.$src.'" name="logo">';

            return '<div class="'.ICNSFCTR_SLUG.'_logo_preview_wrap">
                <div class="'.ICNSFCTR_SLUG.'_logo_preview" id="'.ICNSFCTR_SLUG.'_cln_logo_preview">'.$img.'</div>
                <div style><div class="'.ICNSFCTR_SLUG.'_vertical_align_middle"><div>'.$label.'</div></div></div>
            </div>';
        }

        function string_to_title($str) {
            return ucfirst(str_replace('-', ' ', $str));
        }

        function string_to_sort_hash($str) {
            return substr(base_convert(md5($str), 16,32), 0, 12);
        }

        function join_css_classes($classes_arr) {
            if($classes_arr) {
                $classes = '';
                foreach ($classes_arr as $key) { $classes .= ' '.ICNSFCTR_SLUG.'_'.$key; }
                return $classes;
            } else return '';
        }

        function join_data_attrs($data_attrs_arr) {
            if($data_attrs_arr) {
                $data_attrs = '';
                foreach ($data_attrs_arr as $key => $val) { $data_attrs .= ' data-'.$key.'="'.$val.'"';}
                return $data_attrs;
            } else return '';
        }

        // Example: '[shortcode_name x="1" y="2"]'
        function array_to_shortcode_string($shortcode_name, $attrs, $inner_content) {
            return '['.$shortcode_name.' '.$this->assoc_arr_to_attrs_str($attrs).']'.($inner_content?$inner_content.'[/'.$shortcode_name.']':'');
        }

        // Example: 'x="1" y="2"'
        function assoc_arr_to_attrs_str($arr) {
            $str = '';
            foreach ($arr as $attr => $val) $str .= $attr.'="'.$val.'" ';
            return substr($str, 0, -1);
        }

        // Example: '[shortcode_name preset="title"]'
        function preset_shortcode_string($shortcode_name, $title, $preset_data) {
            $box_attrs = '';
            if($preset_data) {
                foreach ($preset_data as $key => $val) {
                    if(substr($key, 0, 4)==='box_') $box_attrs .= ' '.$key.'="'.$val.'"';
                }
            }
            return '['.$shortcode_name.' preset="'.$title.'"'.$box_attrs.']';
        }

        // Example: string 'ff3300' -> '#ff3300', '255,255,255,.1' -> 'rgba(255,255,255,.1)'
        function wrap_color($color) {
            return strrpos($color, ',')>-1 ? 'rgba('.$color.')' : '#'.$color;
        }

        // Example: string 'rgba(255,255,255,.1)' -> array( int 255, int 255, int 255, float 0.1)
        function color_str_to_rgba_arr($str) {

            if(!is_string($str) && empty($str)) return false;

            $arr = array();

            $is_hex = strrpos($str,'#') !== false;

            if($is_hex) {
              $str = str_replace('#', '', $str);
              $l = strlen($str);
              $arr[0] = hexdec($l == 6 ? substr($str, 0, 2) : ($l == 3 ? str_repeat(substr($str, 0, 1), 2) : 0));
              $arr[1] = hexdec($l == 6 ? substr($str, 2, 2) : ($l == 3 ? str_repeat(substr($str, 1, 1), 2) : 0));
              $arr[2] = hexdec($l == 6 ? substr($str, 4, 2) : ($l == 3 ? str_repeat(substr($str, 2, 1), 2) : 0));
              $arr[3] = 1;
              return $arr;
            }

            $start = strrpos($str,'(');
            $end =  $start - strrpos($str,'(') ;
            if($start=== false && $end) return false;

            $str = substr($str, $start + 1, $end -1);
            if($str===false) return false;

            $arr = explode(',', $str);
            if(count($arr)===4) {
                for($i=0; $i<count($arr); $i++) {
                   $arr[$i] = $i===3 ? floatval($arr[$i]) : intval($arr[$i]);
                }
            } else return false;

            return $arr;
        }

        function filter_array_by_key($arr, $target_key) {
            if($arr) {
                $result = array();
                foreach ($arr as $key => $value) {
                    if (isset($value[$target_key])) $result[] = $value[$target_key];
                }
                return $result;
            } else return false;
        }

        function rmdir_recursive($dir, $only_content) {
          foreach(scandir($dir) as $file) {
             if ('.' === $file || '..' === $file) continue;
             if (is_dir("$dir/$file")) rmdir_recursive("$dir/$file");
             else unlink("$dir/$file");
         }
         if(!$only_content) return rmdir($dir);
        }

        function get_file_type($type, $in_short) {
            $list = array(
                'svg' => 'image/svg+xml',
                'zip' => 'application/zip',
                'png' => 'image/png'
            );
            return $in_short ? array_search($type, $list) :
            (isset($list[$type])?$list[$type]:false);
        }

        // Universal wrapper to file uploading
        // Input data - $_FILES['file-input-name-attr-value'];
        // Output – notification about the result
        function upload_files($target_dir, $data, $type_filters, $return_data_model, $def_filename) {

            $error = false;
            $is_multi = is_array($data['name']) ? 's' : '';
            $files =  $is_multi ? $data['name'] : array($data['name']);
            $files_count = count($files);
            $norm_data = array();
            $matches = 0; // by rules of $type_filters

            for ($i=0; $i < $files_count; $i++) {

				$norm_data[] = array();

				$file_basename = explode(
					'.',
					$is_multi
						? $data['name'][$i]
						: $data['name']
				);

				// Get a normalized file type by the white list
                $norm_data[$i]['type'] = $this->get_file_type(
					($is_multi ? $data['type'][$i] : $data['type']
				), true);

                $norm_data[$i]['name'] = sanitize_file_name($file_basename[0]);
                $norm_data[$i]['ext']  = strtolower(sanitize_text_field($file_basename[1]));
                $norm_data[$i]['tmp_name'] = ($is_multi?$data['tmp_name'][$i]:$data['tmp_name']);

                // Check allowed types

                if(in_array($norm_data[$i]['type'], $type_filters)) {

					$new_file_full_path = $target_dir.($def_filename ? $def_filename : $norm_data[$i]['name'].'.'.$norm_data[$i]['ext']);

                    if(!move_uploaded_file($norm_data[$i]['tmp_name'], $new_file_full_path)) {
                        $error = true;
                        break;
                    }
                    $matches++;
                }

            }

            if($matches===0) $error ='Wrong file type'.$is_multi;

            if($return_data_model) return $matches===0 ? false : $norm_data;
            return $error === false ? ($is_multi?$files:$norm_data[0]['name']) : false;
        }

        // Save an Array as a PHP file
        function save_array_to_file($array, $file) {
			if(!$array) return false;
			if(!is_array($array)) return false;

			$array_as_str = var_export($array, true);

			if(!$this->is_valid_content($array_as_str)) return false;

            if(file_put_contents($file,
'<?php

/* https://svgsprite.com */

if (!defined(\'ABSPATH\')) {die( \'-1\' );}

return '.preg_replace('(\d+\s=>)', "", $array_as_str).';

?>'))
            return true;
            else return false;
        }

        // Quick file downloader
        function dwnld_file($data, $prev_location) {

            if(!( isset($data['data_format']) && isset($data['content']) && isset($data['content_type']) ))
                header('Location: '.$prev_location.'&notice=Some data is empty|file|download|downloaded');

            $data_format = sanitize_text_field($data['data_format']);
            $content = $data_format === 'json' ? json_decode($data['content']) : $data['content'];
            $content_type = $this->get_file_type($data['content_type'], false);
            $file_ext = $data['content_type'];


            if($data_format==='dir') {
                $file_name = $content.'.zip';
                $content_parent_dir = sanitize_text_field($data['content_parent_dir']);
                $content_dir = $content;
                $zip_link = $this->zip_dir($content_parent_dir, $content_dir);
                if(!$zip_link) return false;
            } else {
                $file_name = ICNSFCTR_SLUG.'_'.date('Y-m-d_h-i-s').'.'.$file_ext;
                if(!$content) return false;
            }

            if(!($content_type && $data_format))

            header('Content-type: '.$content_type);
            header('Content-Disposition: attachment; filename="'.$file_name.'"');

            if($data_format==='dir') {
                header('Content-Length: '.filesize($zip_link));
                readfile($zip_link);
                unlink($zip_link);
            } else {
                echo $content;
            }
            exit;
		}


		function is_valid_content($content) {
			return !(preg_match('/script|exec(\s*)\(|eval(\s*)\(|system|include|require|define\(|unlink|rmdir|shell|passthru|header(\s*)\(|request/',strtolower($content)));
		}

		function is_valid_png($file_path) {
			return $this->is_valid_content(file_get_contents($file_path));
		}

		function is_valid_icon_model($file_path) {
			$content_as_str = file_get_contents($file_path);
			// Basic validation
			if(!$this->is_valid_content($content_as_str)) {
				return false;
			}
			// Extra validation
			if(preg_match_all('/\?/', $content_as_str) !== 2) {
				return false;
			} else {
				return true;
			}
		}


        function save_file_to_wp_media() {

            $data = $this->temp_data;
            $file_name = sanitize_text_field($this->temp_data->file_name);
			$file_ext  = strtolower(sanitize_text_field($this->temp_data->file_ext));

			if(!($file_ext === 'png' || $file_ext === 'svg')) {
				return 'It is not Image';
			}

            $file_data = $this->temp_data->file_data;
			unset($this->temp_data);

			if(!$this->is_valid_content($file_data)) return 'Invalid file content';

            if(!($file_name && $file_ext && $file_data)) return 'Empty data';

            $file_name .= '.'.$file_ext;
            $file_data  = explode(',', $file_data);
            $file_data = str_replace(' ', '+', $file_data[1]);


            $parent_post_id = 0;
            $upload_file = wp_upload_bits($file_name, null, base64_decode($file_data));
            if (!$upload_file['error']) {
                $wp_filetype = wp_check_filetype($file_name, null );
                $attachment = array(
                    'post_mime_type' => $wp_filetype['type'],
                    'post_parent' => $parent_post_id,
                    'post_title' => preg_replace('/\.[^.]+$/', '', $file_name),
                    'post_content' => '',
                    'post_status' => 'inherit',
                );
                $attachment_id = wp_insert_attachment( $attachment, $upload_file['file'], $parent_post_id );
                if (!is_wp_error($attachment_id)) {
                    require_once(ABSPATH . "wp-admin" . '/includes/image.php');
                    $attachment_data = wp_generate_attachment_metadata( $attachment_id, $upload_file['file'] );
                    wp_update_attachment_metadata( $attachment_id,  $attachment_data );
                    return 0; // Say OK
                } else return 'Problems with "wp_insert_attachment" function';
            } else return 'Problems with "wp_upload_bits" function';

        }

        // Not recursively
        function zip_dir($parent_dir, $dir_name) {

            $error = false;
            $target_dir = $parent_dir.sanitize_text_field($dir_name);
            $scan = scandir($target_dir);

            $zip_link = $target_dir.'.zip';
            $zip = new ZipArchive;

            if($zip->open($zip_link, ZipArchive::CREATE)) {

                // Add all allowed files from the target folder to target zip
                foreach ($scan as $file) {
                    if(!in_array($file, array('.','..','.DS_Store'))) {
                        $zip->addFile($target_dir.'/'.$file, $file);
                    }
                }

                // Check zip status
                if($zip->status !== ZIPARCHIVE::ER_OK) $error = true;

            } else $error = true;

            $zip->close();
            return $error===false ? $zip_link : false;

		}

        // Unzip an archive to folder with the same name
        function unzip($zip_link, $target_dir) {
            $error = false;
            $zip = new ZipArchive();
            if($zip->open(wp_normalize_path($zip_link))) {
                $zip->extractTo($target_dir);
            } else $error = true;
            $zip->close();
            return !$error;
        }

        // Delete file[s] inside 'library' or 'presets' or 'temp' folder only!
        // $files - an array with relative links
        function delete_files($files) {

			$error_log = [];

			for ($i=0; $i < count($files); $i++) {

				$file = ICNSFCTR_DIR.wp_normalize_path($files[$i]);
				$exploded_path = explode('/',$files[$i]);
				$root = $exploded_path[0];

				// Validate folder
				if(($root === 'library' || $root === 'temp' || $root === 'presets')){
					if(file_exists($file)) if(!unlink($file)) $error_log[] = $file;
				} else {
					$error_log[] = $file;
				}

			}

			return count($error_log) > 0
				? 'Problems with files - '.$error_log.join(', ')
				: 0;
		}


        function bad_module_loading(){
            return '<h3 class="'.ICNSFCTR_SLUG.'_h3">Some problems with the modules loading.<br>Please, send a bugreport via the <a href="'.ICNSFCTR_ADMIN_URL.'_supportroom" target="_blank">Contact form</a></h3>';
        }

        // SUPPORTING FUNCTIONS

        function check_and_send_support_request($data) {
            $sending_result = false;
            if(isset($data['sender']) && isset($data['message'])) {
                $sender = sanitize_email($data['sender']);
                $message = sanitize_text_field($data['message']);
                $subject = sanitize_text_field($data['subject']);
                $content = '<p><b>Task from:</b> '.$sender.'</p><p><b>Subject:</b> '.$subject.'</p><p><b>Details:</b><br>'.htmlspecialchars($message).'</p>';
                $sending_result = $this->send_email($sender, $content);
            } $sending_result = false;
            $this->notice = array( ($sending_result?'Some problems with data handling on the server side':0), 'request', 'send','submitted');
        }

        // EMAIL FUNCTIONS

		// Validation in "check_and_send_support_request" frunction
        function send_email($sender, $content) {

            $to = 'letter2artemy@gmail.com';
            $subject = ICNSFCTR_NAME.' - New request';

            $headers  = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=UTF-8\r\n";
            $headers .= "From: $sender\r\n";

            $content = wordwrap($content, 70, "\r\n");

            return mail($to, $subject, $content, $headers);
        }


    }

    // Finally initialize code
    if (class_exists('ICONSFACTORY')) {
        new ICONSFACTORY;
    }


 ?>
