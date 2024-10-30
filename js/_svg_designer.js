ICONSFACTORY.SVG_DESIGNER = (function ($, cp, custom_ui, library) {

    var self =  {

        args: arguments, // Temp variable with module dependencies

        //Cached DOM elements
        els: [

            // Navigator area
            'recolor_mode_toggler',
            'navigator_screen',
            'navigator_footer',
            'navigator_footer_text',
            'reset_gcm',
            'reset_lcm',
            'canvas_processed',

            // Shortcode area
            'shortcode_output',

            // Extra options area
            'preset_selectbox',
            'preset_mode_checkbox',
            'preset_title_input',
            'preset_upd_btn',
            'preset_add_btn',
            'save_png',
            'save_png_wp',

            // Other
			'bg_shape_color',
			'preview_color'
        ],

        // Expected props from the server
        ui: {},
        presets: {},
        actual_preset: false,

        // Local props, data models and states

        shortcode: {},               // Shortcode data model
        color_map: {},               // Color map data model
        recolor_mode: 'g',           // Recolor mode of the color manager (in the navigator). Options: "g" - global, "l" - local
        is_svg_info: false,          // State of navigator footer for a SVG information

        viewbox: false,              // ViewBox of actual image (square, landscape, portrait)
        preview_color: 'transparent',      // Color of navigator screen

        target_color_index: false,   // Color index of actual shape in the navigator
        target_shape_index: false,   // Index of actual shape in the navigator
        preset_style_mode: false,    // State of "preset_mode_checkbox"
        preset_saving_alert: false,  // State of "preset_title_input"

        // Work with Canvas
        canvas_processed_ctx: false,
        is_downloading: false,

        // Color picker callback functions
        cp_callbacks: [
            function() {
                self.update_ui($.clean_id(cp.els.target_input.id), cp.get_ordinary_color_value(true));
                self.generate_shortcode();
            },
            function(response) {
                if(self.els.navigator_screen) {

					var rgba = response.rgba.join(',');
					var preview_color_input = self.els.preview_color;
					// var preview_color_el = preview_color_input.nextElementSibling;

                    self.preview_color = 'rgba('+rgba+')';
					// self.recolor_navigator_screen(self.preview_color);
					self.update_ui('preview_color',  self.preview_color);
					self.generate_shortcode();

					if(preview_color_input) {
						preview_color_input.value = self.preview_color;
					}
					// preview_color_el.children[0].style.backgroundColor = self.preview_color;
					// $.trigger('click', false, preview_color_el);

                }
            }
        ],

        init: function() {
            this.subscribe();
            this.init_design_options();
            this.init_navigator();
            this.init_shortcode_ui();
            this.init_extra_options();

            // Trigger preset from the GET request
            if(this.actual_preset!==0) {
                var ap_init_val = this.actual_preset;
                this.actual_preset = this.actual_preset.split('@');
                if(this.presets[this.actual_preset[0]][this.actual_preset[1]]) {
					this.trigger_preset(ap_init_val);
                } else $.show_notice('Sorry but this preset is not defined.', 1);
            }
        },


        subscribe: function() {
            // Subscribe on events from the image library
            $.on('sel_lib_item', function(event){

				var e = event || window.event,
                    image_id = e.data && e.data.item_id,
                    cln_id = e.data && e.data.cln_id,
					orient;

				// Update collection ID by click on a libraru image (item)
				if(cln_id) {
					library.state.actual_cln = cln_id;
				}

                if(image_id) {
                    orient = image_id.substr(-2);
                    self.update_ui('id', image_id);
                    self.generate_shortcode();
                    self.viewbox = orient==='-p' ? [0,0,316,512] : (orient==='-l' ? [0,0,512,316] : [0,0,512,512]);
                } else $.show_notice('Some problems with ID of selected Image', 1);

            }, false);

            // From the storeroom
            $.on('del_preset', function(event){
                var e = event || window.event,
                    data = e.data;
                if(data) if(data.id && data.el) self.delete_preset(data.id, data.el);
            }, false);
        },

        // UI: DESIGN OPTIONS -------------------------------------------------------------------------------------------------------------

        init_design_options: function() {

            var target_fields = $.gcl('ui');

            // Save links to all UI elements. Set "change" event to target fields
            for (var i = 0; i < target_fields.length; i++) {
                var param = $.attr(target_fields[i], 'data-param');

                if (typeof this.ui[param] !== 'undefined') {
                    var parent_row = target_fields[i].parentNode.parentNode;
                    while($.attr(parent_row, 'data-role')!=='ui_row') parent_row = parent_row.parentNode;
                    this.ui[param]['row_el'] = parent_row;
                    this.ui[param]['el'] = target_fields[i];
                }

                $.on('change', function(){
                    self.update_ui($.attr(this, 'data-param'), this.value);
                    self.generate_shortcode();
                }, target_fields[i]);

            };

            // Init custom UI
            custom_ui.num_ctrls_init(function(param, value) {
                self.update_ui(param, value);
                self.generate_shortcode();
            });

            // Init Material color picker
            cp.init({
                target     : '.'+$.slug+'_mcp',
                callbacks  : this.cp_callbacks,
                reset_calls: true,
                show_initial_color: false
            });
        },

        // UI: NAVIGATOR ------------------------------------------------------------------------------------------------------------------

        init_navigator: function() {

            // Listeners for: Navigator screen

            if(this.els.navigator_screen) $.on('click', function(event){
                var e = event || window.event,
                    color = this.style.backgroundColor || '#fff';

                cp.reset_target_preview();
                cp.config.mode = 'custom';
                cp.config.target_callback = 1;

                cp.show_picker(color);

                $.cancel_bubble(e);
            }, this.els.navigator_screen);

            $.on('mouseout', function(){
                if(self.svg_info) {
                    self.navigator_footer_set_state('init');
                    self.svg_info = false;
                }
            }, this.els.navigator_screen);


            // Listeners for: second UI in the navigator header (reolor mode toggler)

            if(this.els.recolor_mode_toggler) {

                $.on('click', function(event){
                    var e = event || window.event;
                    if(self.recolor_mode==='g') {
                        self.recolor_mode = 'l';
                        this.innerHTML = 'Local repainting';
                    }
                    else {
                        self.recolor_mode = 'g';
                        this.innerHTML = 'Global repainting';
                    }
                    $.cancel_bubble(e);
                }, this.els.recolor_mode_toggler);

            }

            if(this.els.reset_gcm) {

                $.on('click', function(event){
                    if(self.color_map.g) {
                        delete self.color_map.g;
                        self.set_color_map();
                        self.generate_shortcode();
                        this.style.display = 'none';
                    }
                }, this.els.reset_gcm);

            }

            if(this.els.reset_lcm) {

                $.on('click', function(event){
                    if(self.color_map.l) {
                        delete self.color_map.l;
                        self.set_color_map();
                        self.generate_shortcode();
                        this.style.display = 'none';
                    }
                }, this.els.reset_lcm);

            }
        },

        navigator_handler: function(event, hover) {
            var e = event || window.event,
                target = e.target || e.srcElement,
                target_index = parseInt($.attr(target,'data-index')),
                target_name = target.nodeName.toLowerCase(),
                target_fill = $.attr(target,'fill'),
                parent = target.parentNode,
                parent_name = parent.parentNode.nodeName.toLowerCase(),
                parent_index = parseInt($.attr(parent,'data-index'));

            if(target_index>=0) {

                if(hover) {
                    this.render_navigator_footer_text({
                        target_name:  target_name,
                        target_index: target_index,
                        target_fill:  target_fill,
                        parent_name:  parent_name,
                        parent_index: parent_index}
                    );
                } else {

                    // Click on a shape in the navigator

                    if(target_fill){

                        this.target_color_index = $.attr(target,'data-color');
                        this.target_shape_index = target_index;

                        if(cp.els.target_input) if(cp.els.target_input.id!=='color_map_control')  cp.reset_target_preview();

                        cp.config.target_callback = 0;

                        cp.state.color.rgba = false;
                        cp.els.target_preview = undefined;
                        cp.els.prev_target_preview = undefined;
                        cp.els.target_input = {
                            id:'color_map_control',
                            value:false,
                            attributes:{
                                'data-init-value': {
                                    'value':target_fill
                                }
                            }
                        };
                        cp.show_picker(target_fill, true);
                        $.cancel_bubble(e);
                    }
                }

            } else if(target_index===-1) {
                if(!hover){

                    // Trigger for backgroud shape color field
                    if(cp.config.mode==='custom') {cp.config.target_callback = 0; cp.config.mode = 'normal';}
                    $.trigger('click', false, this.els.bg_shape_color.nextElementSibling);
                    $.cancel_bubble(e);
                }
            } else {
                if(!hover) cp.hide_picker();
            }
        },

        toggle_navigator_footer_ui: function() {
            this.els.reset_gcm.style.display = typeof this.color_map.g !== 'undefined' ? 'block' : 'none';
            this.els.reset_lcm.style.display = typeof this.color_map.l !== 'undefined' ? 'block' : 'none';
            this.navigator_footer_set_state(this.color_map?'init':'ui');
        },

        render_navigator_footer_text: function(svg_info, custom_mess) {
            var mess = '';
            if(svg_info) {
                mess = 'Element: <b class="'+$.slug+'_b">'+svg_info.target_name+'</b> with data-index="<b class="'+$.slug+'_b">'+svg_info.target_index+'</b>"';
                if(svg_info.target_fill) mess += '<span style="background-color:'+svg_info.target_fill+';" class="'+$.slug+'_micro_color_preview"></span>'
                if(svg_info.parent_name==='g' && svg_info.parent_index>=0) mess += '<br>Parent element: <b class="'+$.slug+'_b">g</b> with data-index="<b class="'+$.slug+'_b">'+svg_info.parent_index+'</b>"';
                $.add_class(this.els.navigator_footer, 'navigator_footer_state_data', true);
                this.svg_info = true;
            } else {
                mess = custom_mess;
            }
            this.els.navigator_footer_text.innerHTML = mess;
        },

        navigator_footer_set_state: function(state) {
            this.els.navigator_footer.className = $.slug+'_navigator_footer_state_'+state;
        },

        recolor_navigator_screen: function(color) {
            // this.els.navigator_screen.style.backgroundColor = color;
        },

        // UI: SHORTCODE ------------------------------------------------------------------------------------------------------------------

        init_shortcode_ui: function() {

            // Shortcode text output
            if(this.els.shortcode_output) $.on('focus', function(){this.select();}, this.els.shortcode_output);

            // Copy shortcode to clipboard (or localstorage)
            $.on('click', function(){
                $.clipboard(self.els.shortcode_output, 0);
            }, $.ge('copy_shortcode'));

            // Parse shortcode from the textarea manually
            $.on('click', function(){
                var new_shortcode_string = self.els.shortcode_output.value,
                    new_shortcode_data_model;

                // Parse shortcode string to data model
                new_shortcode_data_model = $.parse_shortcode_string(new_shortcode_string);

                if(new_shortcode_data_model.preset) {
                    if(self.presets[new_shortcode_data_model.preset]) {
                        self.trigger_preset(new_shortcode_data_model.preset);
                        $.show_notice('Your shortcode contains the preset: "'+new_shortcode_data_model.preset+'". It has been activated.', 0);
                    } else {
                        $.show_notice('Your shortcode contains an undefined preset ID.', 1);
                    }
                } else {
                    if(new_shortcode_data_model) {
                        self.do_preset(self.actual_preset, new_shortcode_data_model);
                    }
                }
            }, $.ge('paste_shortcode'));

        },

        update_shortcode_output: function(attrs) {
            var normal_attrs = attrs ? attrs.slice(0, -1) : 'id="'+this.ui.id+'"'+(typeof this.shortcode.dir !== 'undefined' ? ' dir="'+this.shortcode.dir+'"' : '');
            this.els.shortcode_output.value = '['+$.slug+' '+normal_attrs+']';
        },

        // UI: EXTRA OPTIONS --------------------------------------------------------------------------------------------------------------

        init_extra_options: function() {

            var alert_mess = 'Your image is empty. Please choose an image in the library.', saving_fn;

            // Presets list. Selecbox
            if(this.els.preset_selectbox) $.on('change',function() { self.do_preset(this.value, false); }, this.els.preset_selectbox);

            // Don't change the image when you choose a preset or update the selected preset
            if(this.els.preset_mode_checkbox) $.on('change',function() { self.preset_style_mode = this.checked; }, this.els.preset_mode_checkbox);

            // Add a new preset
            if(this.els.preset_add_btn) $.on('click',function() { self.save_preset(false); }, this.els.preset_add_btn);

            // Update selected preset
            if(this.els.preset_upd_btn) $.on('click',function() { self.update_preset(); }, this.els.preset_upd_btn);

            // Save 2D context of the pocessed canvas
            if(this.els.canvas_processed) this.canvas_processed_ctx = this.els.canvas_processed.getContext('2d');
            else console.log('Some problems with the HTML5 canvas supporting.');

            // Link to save shortcode result -> download SVG image file
            $.on('click', function(event){
                if(typeof self.shortcode.id !== 'undefined') {
                    var svg_data = self.get_svg_data();
                    if(svg_data) $.quick_file_saver(svg_data);
                } else $.show_notice(alert_mess, 1);
                $.cancel_bubble(event);
            }, $.ge('save_svg'));

            saving_fn = function(event, wp_mode) {
                if(self.is_downloading===false) {
                    var el = event.target || event.srcElement;
                    if(typeof self.shortcode.id !== 'undefined') {
                        var svg_data = self.get_svg_data();
                        if(svg_data) {
                            self.is_downloading = true;
                            $.draw_svg_on_canvas(self.els.canvas_processed, self.canvas_processed_ctx, svg_data, [!1,function(data) {

                                if(wp_mode) $.save_file_to_wp_media(self.shortcode.id, 'png', data);
                                else $.save_canvas_data_as_png(el, self.shortcode.id, data);

                                self.is_downloading = false;
                            }], self.viewbox[2], self.viewbox[3]);
                        }
                    } else $.show_notice(alert_mess, 1);
                    $.cancel_bubble(event);
                }
            };

            // Download PNG image file
            $.on('click', function(event){
                saving_fn(event, !1);
            }, this.els.save_png);

            // Save PNG image file to WP Media Library
            $.on('click', function(event){
                saving_fn(event, 1);
            }, this.els.save_png_wp);

            // Link to reset all UI
            $.on('click', function(event){
                self.reset_ui(true, true, true, true);
                $.cancel_bubble(event);
            }, $.ge('reset_all'));

        },

        do_preset: function(id, preset_params) {

			console.log('id', id, typeof id);
			console.log('preset_params', preset_params);

            if(id==='0') {
                this.actual_preset = false;
                return false;
            } else {
				id = id
					? Array.isArray(id) ? id : id.split('@')
					: 0;
                // id = id ? id.split('@') : 0; // Get the parent group and the preset title
            }

            if(id===false) {
                $.show_notice('Sorry but this preset is corrupted.', 1);
                return false;
            }

            var normalized_ui = {},
                preset_params = preset_params ? preset_params : this.presets[id[0]][id[1]],
                shortcode_attrs = '';


			library.state.actual_cln = preset_params['dir']
				? preset_params['dir']
				: library.state.actual_cln || 'default';


			if(library.state.actual_cln !== 'default') {
				normalized_ui['dir'] = library.state.actual_cln;
				shortcode_attrs += ' dir="'+library.state.actual_cln+'" ';
			}

            if(preset_params) {
                if((this.preset_style_mode === false && preset_params['id']) || this.ui.id) {

                    // if(id==='0') preset_params['id'] = this.ui.id; ??

                    this.reset_ui();

                    // Set all values for UI elements
                    for (var param in preset_params) {

                        var val = preset_params[param];

                        // Save prev ID
                        if(this.preset_style_mode === true) {
                            if(param==='id' && this.ui.id) val = this.ui.id;
                            // else if(param==='dir') val = this.shortcode.dir;
                        } else {
                            // if(param==='dir') this.shortcode.dir = val;
                        }

                        this.update_ui(param, val, true);

                        normalized_ui[param] = val;
                        shortcode_attrs += param+'="'+val+'" ';
                    }

                    // Update color_map UI dependecy
                    this.toggle_navigator_footer_ui();

                    // Save shortcode and render it
                    this.update_shortcode_output(shortcode_attrs);
                    this.shortcode = normalized_ui ? normalized_ui : preset_params;
                    this.render_image();

                    // Update color previews
                    cp.render_previews(true);

                    this.actual_preset = id;
                }
            }  else { if(typeof preset_params === 'undefined') { $.show_notice('Sorry but this preset is corrupted.',1);} }
        },

        save_preset: function(id) { console.log('this.shortcode',this.shortcode);

            var title = id ? id[1] : $.escape_sting(this.els.preset_title_input.value),
                group = id ? id[0] : 'MY',
                preset_id = group+'@'+title;

            if(title && this.shortcode) {

                var preset_data = $.clone_obj(this.shortcode); // Clone actual shortcode params

                if(this.preset_style_mode===true && title) {
                    delete preset_data.id; // Ignore image ID
                    if(typeof preset_data.dir !== 'undefined') delete preset_data.dir; // Ignore image DIR
                }

                // Add info about navigator screen color to data model
                if(this.preview_color) preset_data.preview_color = this.preview_color;

                if(this.preset_saving_alert) $.remove_class(this.els.preset_title_input, 'alert_mess');

                this.actual_preset = [group, title];

                this.preset_sending_wrapper(group, title, preset_data, (id ? 'upd' : 'add'),

                    function(response, responsed_act) {

                        self.preset_saving_alert = false;
                        $.show_notice('OK! Your preset has been '+(responsed_act==='add'?'added':'updated'),0);

                        // Update preset data on the client side
                        self.presets[group][title] = self.shortcode;

                        if(responsed_act==='add') {

                            // Add new option to selectox of presets and set the new value of it
                            var new_option = $.ce('option',{
                                    value: preset_id
                                }, title
                            );

                            self.els.preset_selectbox.children[1].appendChild(new_option);
                            self.els.preset_title_input.value = '';
                            self.trigger_preset(preset_id);
                        }

                    } ,
                    function(response){
                        $.show_notice(response,1)
                    } );

            } else {
                $.add_class(this.els.preset_title_input, 'alert_mess');
                this.preset_saving_alert = true;
            }

        },

        trigger_preset: function(id) {
            this.els.preset_selectbox.value = id;
            $.trigger('change', false, this.els.preset_selectbox);
        },

        update_preset: function() {
            if(this.actual_preset !== false && this.actual_preset !== 0) this.save_preset(this.actual_preset);
        },

        delete_preset: function(id, el) {
            if(id && el) {
                id = id.split('@');
                el.style.backgroundColor = 'tomato';
                this.preset_sending_wrapper(id[0], id[1], null, 'del',
                    function(response){
                        el.parentNode.removeChild(el);
                    } ,
                    function(response){ $.show_notice(response,1); });
            } else $.show_notice('Unable to remove this preset. Reason: some data is empty.',1);
        },

        preset_sending_wrapper: function(group, title, data, act, success_callback, error_callback) {

            var png_base64_data = act==='del' ? !1 : 1,
                config = {action: 'presets_handling', preset_group: group, preset_title: title, preset_data: data, preset_act: act},
                request = function(config) {
                    $.send($.plugin_url, config, function(response){
                        if(parseInt(response)!==1) error_callback(response);
                        else success_callback(response, act);
                    });
                };

                if(act!=='del') {

					// Asynchronous request
                    $.draw_svg_on_canvas(
						this.els.canvas_processed,
						this.canvas_processed_ctx,
						this.get_svg_data(),
						[1,request,config],
						this.viewbox[2],
						this.viewbox[3]
					);

                } else {
                    // Common request
                    request(config);
                }
        },


        // MAIN FUNCTIONAL ----------------------------------------------------------------------------------------------------------------

        // Update shortcode data model, [ UI element ]
        update_ui: function(param, value, update_el) {

            if(param!=='id' && param!=='color_map_control' && typeof this.ui[param] !== 'undefined') {
                var type = this.ui[param].type;

                this.ui[param]['user_value'] = value;
                if(typeof this.ui[param]['dependency'] !== 'undefined') {
                    var dependency = this.ui[param]['dependency'];
                    if(dependency) {
                        var current_dep = dependency[value];
                        for (key in dependency) {
                            if(key!==value) for(i in dependency[key]) this.ui[dependency[key][i]]['row_el'].style.display = 'none';
                        }
                        for (key in current_dep) this.ui[current_dep[key]]['row_el'].style.display = 'block';
                    }
                }

                if(update_el) {
                    if(type==='num_control') custom_ui.update_num_ctrl(param, value);
                    else if(type==='transform_control') custom_ui.update_transform_ctrl(-1, value);
                    else if(type==='preview') this.unpack_color_map();
                    else this.ui[param].el.value = value;
                }

            } else if(param==='id') {
                if(value) {
					this.ui['id'] = value;
					if(update_el) {
						this.hightlight_image(value);
					}
                }
            } else if (param==='color_map_control') {
                this.update_color_map(value);
            } else if (param==='preview_color') {
                this.preview_color = value;
                // this.recolor_navigator_screen(value);
            } else {
                console.log('Undefined parameter: '+param);
            }
        },

        reset_ui: function(do_render_image, update_shortcode_output, reset_preset, reset_mcp_previews) {

            if(this.shortcode.id) {

                // Reset actual shortcode params. Save image DIR
                this.shortcode = typeof this.shortcode.dir !== 'undefined' ? {dir:this.shortcode.dir} : {};

                // Save image ID
                if(typeof this.ui.id !== 'undefined') this.shortcode.id = this.ui.id;

                // Update UI elements
                for (var param in this.ui) {
                    if(param!=='id' && this.ui[param].el) {
                        var default_value = this.define_def_ui_value(param);
                        this.update_ui(param, default_value, true);
                    }
                }

                // Refresh transform controller
                custom_ui.transform_center = true;
                $.add_class(custom_ui.transform_locked_els,'numctrl_locked_content',true);

                // Reset color map (global and local)
                this.color_map = false;

                // Update shortcode text preview
                if(update_shortcode_output) this.update_shortcode_output();

                // Update non-standard UI
                if(reset_mcp_previews) cp.render_previews(true);

                // Reset presets selector
                if(reset_preset) {
                    this.els.preset_selectbox.value = 0;
                    this.actual_preset = false;
                }

                // Render image preview
                if(do_render_image) this.render_image();
            }

        },

        generate_shortcode: function(param, value, update_el) {
            var shortcode_attrs = '',
                normalized_ui = {},
                actual_cln = library.state.actual_cln;

                if(typeof this.ui['id'] !== 'undefined') {

                    //  Set Image ID
                    normalized_ui['id'] = this.ui['id'];
                    shortcode_attrs = 'id="'+this.ui['id']+'" ';

                    // Set or delete Image dir
                    if(actual_cln && actual_cln!=='default') {
                        normalized_ui['dir'] = actual_cln;
                        shortcode_attrs += ' dir="'+actual_cln+'" ';
                    }

                    for (var param in this.ui) {

                        // Check all parent dependencies
                        if(typeof this.ui[param]['parent'] !== 'undefined') {

                            var parent = this.ui[this.ui[param]['parent']],
                                parent_user_value = parent['user_value'] ? parent['user_value'] : false,
                                parent_defaul_value;

                            if(typeof parent['dependency'][parent_user_value] === 'undefined') continue;

                            while (typeof parent['parent'] !== 'undefined') parent = this.ui[parent['parent']]; // Search the top parent

                            parent_user_value = parent.user_value ? parent.user_value : false;
                            parent_defaul_value = Array.isArray(parent.value) ? parent.value[0] : parent.value;

                            if(parent_user_value===parent_defaul_value) {
                                this.ui[param]['row_el'].style.display = 'none';
                                continue;
                            }

                        }

                        // Check all values. Ignore default values
                        if(this.ui[param]['user_value']) {

                            var value_str = $.var_to_str(this.ui[param]['user_value']),
                                def_value_str = $.var_to_str(this.define_def_ui_value(param));

                            // If "user_value" string === 'default_value' string
                            if(value_str !== def_value_str && this.ui[param]['user_value'] !== false) {
                                normalized_ui[param] = this.ui[param]['user_value'];
                                shortcode_attrs += param+'="'+this.ui[param]['user_value']+'" ';
                            }
                        }
                    }

                    this.update_shortcode_output(shortcode_attrs);
                    this.shortcode = normalized_ui;
                    this.render_image();

                }
        },

        define_def_ui_value: function(param) {
            return typeof this.ui[param].def_value !== 'undefined' ? this.ui[param].def_value :
            Array.isArray(this.ui[param].value) ? this.ui[param].value[0] : this.ui[param].value;
        },

        // Send the shortcode to the server and render the result image on the navigator screen
        render_image: function() {
            if(this.shortcode.id) {
                $.send($.plugin_url, {action: 'do_shortcode', shortcode: this.shortcode}, function(response){

					self.els.navigator_screen.innerHTML = response;

                    if(response.indexOf('EMPTY IMAGE') === -1) {

                        var defs = $.gt('defs', 0, self.els.navigator_screen).children,
                            defs_len = defs.length;

                        if(defs_len>0) {
                            for (var i = 0; i < defs_len; i++) {
                                var def = defs[i];
                                $.on('mouseover',function(event){ self.navigator_handler(event, true); }, def);
                                $.on('click',function(event){
                                    self.navigator_handler(event, false);
                                    $.cancel_bubble(event);
                                }, def);
                            }
                        }
                    } else $.show_notice('Sorry but this image is corrupted.', 1);
                });
            }
        },

        // Get SVG data from navigator screen
        get_svg_data: function() {
            return this.els.navigator_screen.children[0].innerHTML;
        },


        // Hightlight current image in the library
        hightlight_image: function(image_title) {
            var el = $.ge(image_title);
            if(el) el.checked = true;
            else console.log('Image with name: '+image_title+' undefined.');
        },

        update_color_map: function(color) {
            if(color) {

                var mode = this.recolor_mode, access = true,
                    id = mode==='g' ? this.target_color_index : this.target_shape_index,
                    index = 0;

                if(this.color_map===false) this.color_map = {};
                // Add part to "color_map" if it is empty
                // Activate reset btns
                if(!this.color_map[mode]) {
                    this.color_map[mode] = [];
                    this.els['reset_'+mode+'cm'].style.display = 'block';
                    this.els.navigator_footer_text.style.display = 'none';
                }

                part = this.color_map[mode];

                part.forEach(function(item, i, part) {
                    if(item[0]+''===id+'') {access = false; index = i;}
                });

                // Add or Update color in "color_map"
                if(access) this.color_map[mode].push([id, color]);
                else this.color_map[mode][index] = [id, color];

                this.set_color_map();
            }
        },

        unpack_color_map: function() {
            var color_map = this.ui.color_map.user_value;

            if(color_map) {

                var parts = color_map.split('@'),
                    color_map = {};

                for (i=0; i < parts.length; i++) {
                    var mode = parts[i][0],
                        color_rules = parts[i].slice(1).split('|');

                    if(!color_map[mode]) color_map[mode] = [];

                    for (j=0; j < color_rules.length; j++) {
                        var rule_arr = color_rules[j].split(':'),
                            color = rule_arr[1];
                        if(rule_arr[0].indexOf(';')>-1) {
                            var ids = rule_arr[0].split(';');
                            for (id in ids) {
                                id = parseInt(id);
                                color_map[mode].push([id, color]);
                            }
                        } else {
                            var id = parseInt(rule_arr[0]);
                            color_map[mode].push([id, color]);
                        }
                    }

                }

                this.color_map = color_map;
            }
        },

        set_color_map: function() {
            var map_str = '', map = this.color_map, index = 0;

            for(part in map) {
                var normal_rules = $.collapse_2Darray_to_clean_object(map[part]);
                map_str += (index>0?'@':'')+(part==='g'? 'g':'l');

                for(rule in normal_rules) {
                    map_str += rule+':'+normal_rules[rule]+'|';
                }
                map_str = map_str.slice(0,-1);

                index++;
            }

            this.ui.color_map.el.value = map_str;
            this.ui.color_map.user_value = map_str;
        },

    };

    return self;

})(ICONSFACTORY, window['MCP'], ICONSFACTORY.CUSTOM_UI, ICONSFACTORY.LIBRARY);
