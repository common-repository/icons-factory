ICONSFACTORY.SVG_PARSER = (function ($, page_ctrls) {

    var self =  {

        args: arguments, // Temp variable with module dependencies

        els: [
            'ctrls_container',
            'trash_container',
            'navigator_screen_original',
            'navigator_screen_processed',
            'canvas_processed',
            'cln_selectbox',
            'save_btn',
            'title_input',
            'next_btn',
            'pbar'
        ],

        // General data & module state

        files            : false,
        actual_file      : 0,
        is_saved         : false,

        // Data & state of the actual parsed file

        standard_viewbox  : '0 0 512 512',
        viewbox           : false,
        original_title    : false, // Name of original image file (without extension)
        svg_data_model    : false,
        model_start_offset: 0,     // Offset for the start position of svg_data_model ()
        shapes            : false, // List of shape nodes
        shape_ctrls       : false, // Object with cached UI, dependency map
        processed_image   : false, // Processed <SVG> container
        canvas_processed_ctx: false,


        // Params for shape controllers

        prev_shape_index : -1,
        is_shift: false,


        init: function() { console.log(this.svg_data_model);

            var error = [];

            // Init fixed layout
            page_ctrls.init_fixed_layout();

            // Init all shape nodes of actual SVG file
            this.init_shapes();

            // Check svg_data_model
            this.check_svg_data_model();

            // Init all controllers of shapes
            if(this.ui_dep_map) this.init_shape_controllers(); else error.push('No "ui_dep_map."');

            // Save 2D context of the pocessed canvas
            if(this.els.canvas_processed) this.canvas_processed_ctx = this.els.canvas_processed.getContext('2d');
            else error.push('No "canvas_processed."');

            // Init progress bar
            if(this.els.pbar) this.init_pbar();

            // Set all event listeners

            if(this.els.navigator_screen_processed) {
                this.processed_image = this.els.navigator_screen_processed.childNodes[0];
                $.on('click', function(event){
                    self.restart_animation();
                }, this.els.navigator_screen_processed);
            } else error.push('No "navigator_screen_processed."');

            if(this.els.ctrls_container) {
                $.on('mouseover', function(event){
                    self.ctrls_handler(event, 0);
                    $.cancel_bubble(event);
                }, this.els.ctrls_container);

                $.on('change', function(event){
                    self.ctrls_handler(event, 1);
                    $.cancel_bubble(event);
                }, this.els.ctrls_container);

                $.on('click', function(event){
                    self.ctrls_handler(event, 2);
                    // $.cancel_bubble(event);
                }, this.els.ctrls_container);

                $.on('click', function(event){
                    if(self.prev_shape_index!==false) {
                        self.mute_actual_shape(true);
                        self.mute_navigator_screen_processed();
                    }
                }, document.body);

                $.on('keydown', function(event){
                    var e = event || window.event;
                    self.is_shift = e.shiftKey;
                }, document.body);

                $.on('keyup', function(event){
                    self.is_shift = false;
                }, document.body);

            }  else error.push('No "ctrls_container."');

            $.on('click', function(event){
                self.save_processed_image_step_1();
                $.cancel_bubble(event);
            }, this.els.save_btn);

            if(this.els.title_input) {
                $.on('change', function(event){
                    this.value = this.value ? (self.r_mode || self.edit_def_lib_mode ? this.value : $.title_to_string(this.value)) : 'auto-name';
                }, this.els.title_input);

                this.init_file_title();

            } else error.push('No "title_input."');

            if(this.els.next_btn) {
                $.on('click', function(){
                    self.get_next_file();
                }, this.els.next_btn);
            }

            if(error.length>0) $.show_fatal_notice(error.join(' '));

            if(!this.r_mode) $.confirm_page_refreshing();

        },

        check_svg_data_model: function() {
            if(typeof this.svg_data_model[0]['p'] !== 'undefined') {
               this.model_start_offset++;
            }
        },

        // Init all shape nodes of actual SVG file
        init_shapes: function() {
            this.shapes = $.gcl('shape');
        },

        init_shape_controllers: function(dep_map) {
            var dep_map = this.ui_dep_map,
                ui_rows = $.gcl('ui_row'),
                shape_ctrls = [];
            for (var i = 0; i < ui_rows.length; i++) {
                var row = ui_rows[i],
                    label_el = row.children[1].children[0],
                    is_group = typeof dep_map[i] !== 'undefined',
                    obj = {
                        g: is_group ? 1:0,
                        row_el: row,
                        label_el: label_el,
                        field_els: $.gcl('ui', row),
                    };

                if(is_group) {
                    obj['childrens'] = dep_map[i]['c'];
                    obj['g_state'] = dep_map[i]['s'];
                } else {
                    obj['inner_ui'] = $.gcl('inner_ui', row)[0]
                    obj['inner_ui_state'] = false;
                }

                shape_ctrls.push(obj);
            };
            this.shape_ctrls = shape_ctrls;
        },

        init_file_title: function() {

            // Save original title of actual SVG file
            if(!this.original_title) this.original_title = this.files[this.actual_file].name;

            // Set normalized title
            this.els.title_input.value = this.r_mode || this.edit_def_lib_mode ? this.original_title : $.title_to_string(this.original_title);
        },

        // Init progress bar
        init_pbar: function() {
            var pbar = this.els.pbar;
            this.els.pbar_indicator = pbar.children[0];
            this.els.pbar_label = pbar.children[1];
            this.els.pbar_label_actual_val = this.els.pbar_label.children[0];
            this.els.pbar_label_total_val = this.els.pbar_label.children[2];
        },

        update_svg_data_model: function(param, index, new_value) {
            if(new_value==='–') {
                delete this.svg_data_model[index][param];
            } else {
                this.svg_data_model[index][param] = new_value;
            }
        },

        ctrls_handler: function(event, event_type) {
            var e = event || window.event,
                target = e.target || e.srcElement,
                index = $.attr(target, 'data-index');

            this.is_shift = e.shiftKey === true;

            if(index || index=='0') {
                if(event_type === 0) { // HOVER
                    this.navigator_screen_processed_handler(index);
                } else if(event_type === 1 || (event_type===2 && this.is_shift)) { // CHANGE or recursive setter on CLICK
                    var param = $.attr(target, 'data-param'),
                        new_value = target.value;
                    if(param) {
                        if(param==='f' || param==='t' || param==='m') new_value = parseInt(new_value);
                        this.change_actual_file(param, index, new_value, false, this.is_shift);
                    }
                } else if(event_type===2) { // CLICK
                    var role = $.attr(target, 'data-role');
                    if(role==='group_expander') {
                        this.toggle_group_state(index, target);
                    } else if(role==='inner_ui_expander'){
                        this.toggle_inner_ui_state(index, target);
                    } else if(role==='ui') {
                        var param = $.attr(target, 'data-param');
                        if(param==='to') {
                            var new_value = $.attr(target, 'data-to');
                            if(new_value!==this.svg_data_model[index].to) {
                                this.change_actual_file(param, index, new_value, false, false);
                                this.update_to_ctrl(target);
                            }
                        }
                    }
                }
            }
        },

        // Recursive function to update "svg_data_model", "shape_ctrls" & "processed_image" (in the preview box)
        change_actual_file: function(param, index, new_value, update_ui_field, recursion) {
            this.update_shape_in_preview(param, index, new_value);
            if(update_ui_field) this.update_ui_field(param, index, new_value);
            if(this.svg_data_model[index].g===0 && recursion) {
                // Start a new loop of hell. Recursive setter for selected group childrens
                var deps = this.shape_ctrls[index].childrens;
                for (var i = 0; i < deps.length; i++) {
                    this.change_actual_file(param, deps[i], new_value, true, true);
                }
            }
            this.update_svg_data_model(param, index, new_value);
        },

        update_ui_field: function(param, index, new_value) {
            var field = this.shape_ctrls[index].field_els[$.slug+'_'+param+'_'+index];
            if(field) field.value = new_value;
        },

        // Toggle transform origin controller
        update_to_ctrl: function(el) {
            var cl = 'sm_active',
                prev_el = $.gcl(cl, el.parentNode)[0];
            $.remove_class(prev_el, cl)
            $.add_class(el, cl);
        },

        // Recursive UI toggler.
        // index - row index
        // el - group toggler (DOM element)
        toggle_group_state: function(index, el) {
            if(typeof this.shape_ctrls[index] !== 'undefined') {
                var row = this.shape_ctrls[index].row_el,
                    row_ui_fields = row.children[2],
                    deps = this.shape_ctrls[index].childrens,
                    state = this.shape_ctrls[index].g_state;
                for (var i = 0; i < deps.length; i++) {
                    var dep_row = this.shape_ctrls[deps[i]].row_el;
                    if(typeof dep_row !== 'undefined') {
                        dep_row.style.display = state===0 ? 'block': 'none';
                        if(typeof this.shape_ctrls[deps[i]] !== 'undefined') {
                            if(this.shape_ctrls[deps[i]].g_state===1) {
                                this.toggle_group_state(deps[i], this.shape_ctrls[deps[i]].label_el); // Start a new loop of hell
                            }
                        }
                    }
                };
                if(el) {
                    // row_ui_fields.style.opacity = state===0 ? .4: 1; // Mute opened group header
                    el.innerHTML = state===0 ? 'Close Group' : 'Group';
                    this.shape_ctrls[index].g_state = state===0 ? 1 : 0;
                }
            }
        },

        // Toggle hidden UI of a shape
        toggle_inner_ui_state: function(index, el) {
            var rec = this.shape_ctrls[index], nodename;
            if(typeof rec !== 'undefined') {
                nodename = el.innerHTML;
                rec.inner_ui.style.display = rec.inner_ui_state ? 'none' : 'block';
                el.innerHTML = rec.inner_ui_state ? nodename.replace('Close ','') : 'Close '+nodename;
                rec.inner_ui_state = !rec.inner_ui_state;
            }
        },

        navigator_screen_processed_handler: function(index) {
            if(index !== this.prev_shape_index) {
                var shape = this.shapes[index];
                this.hightlight_navigator_screen_processed();
                if(shape) this.hightlight_actual_shape(shape);
                if(this.prev_shape_index!==false) this.mute_actual_shape();
                this.prev_shape_index = index;
            }
        },

        update_shape_in_preview: function(param, index, new_value) {
            var shape = this.shapes[index], nodename;
            if(param==='f') {
                $.attr(shape, 'fill', $.base_palette[new_value]);
            } else if(param==='ag') {
                $.add_class(shape, (new_value==='–' ? 'shape' : 'shape '+$.slug+'_ag '+$.slug+'_ag_'+new_value), true); // Reset all class value
            } else if(param==='t') {
                shape.style.display = new_value===-1?'none':'block';
            } else if(param==='to') {
                $.attr(shape, 'style', this.normalize_transform_origin(new_value));
            } else if(param==='d') {
                nodename = shape.nodeName.toLowerCase();
                if(nodename==='path') {
                    $.attr(shape, 'd', new_value);
                }
            }
            // this.restart_animation();
        },

        normalize_transform_origin: function(to) {
            if(to==='c') return [50,50];
            result = '';
            str_len = to.length;

            for(i=0; i<2;i++){
              x = i < str_len ? to[i] : to[0];
              result += (i>0?' ':'')+(x==='c'?50:(x==='l'||x==='t'?0:100))+'%';
            }
            return '-webkit-transform-origin: '+result+' !important; transform-origin: '+result+' !important; -ms-transform-origin: '+result+' !important;"';
        },

        restart_animation: function() {
			var animGroup = self.processed_image.childNodes[1].childNodes[0].childNodes[0];
            $.remove_class(animGroup, 'start_anim');
            setTimeout(function() { $.add_class(animGroup, 'start_anim'); }, 100);
        },

        normalize_svg_data_model: function() {
            var n = this.viewbox !== this.standard_viewbox ? [{p:{viewBox:this.viewbox}}] : [],
                i = 0;

            for(var key in this.svg_data_model) {

                var node = this.svg_data_model[key];

                // Ignored shapes
                if(node['t'] === -1) continue;

                // Add path lenght
                // if(typeof node['n'] !== 'undefined') {
                //     if(typeof node['n']['len'] === 'undefined') {
                //         node['len'] = this.shapes[i-this.model_start_offset].getTotalLength().toFixed(2);
                //     }
                // }

                // Push node to normalized svg data model
                n.push($.clone_obj(this.svg_data_model[key]));

                i++;

            }
            return n;
        },

        // Draw PNG image preview on the canvas
        save_processed_image_step_1: function() {
            this.init_png_image_preview(function(response){
                self.save_processed_image_step_2(response);
            });
        },

        // Send normalized SVG data model to server and save it as PHP source file + PNG preview
        save_processed_image_step_2: function(png_base64_data) {
            var normalized_svg_data_model = this.normalize_svg_data_model(),
                title = this.els.title_input.value,
                cln_id = this.els.cln_selectbox.value,
                alert_mess = 'Oops, unable to save the file. Call to Guru, please.';

            if(cln_id==='0') {
                $.show_notice('You should select an image collection.', 1);
                return false;
            }

            if(!title) {
                $.add_class(this.els.title_input, 'alert_mess');
                return false;
            } else $.remove_class(this.els.title_input, 'alert_mess');

            if(title && normalized_svg_data_model && png_base64_data && this.viewbox) {
                $.send($.plugin_url, {
                    action  : 'save_svg_file',
                    svg_data: normalized_svg_data_model,
                    png_data: png_base64_data,
                    viewbox : self.viewbox,
                    title   : title,
                    cln     : cln_id,
                    o_title : self.original_title,
                    resave  : self.r_mode ? true : self.is_saved
                }, function(response){
                    console.log('SVG saving response',response);
                    response = parseInt(response);
                    if(response===1) {self.is_saved = true; $.show_notice('Ok! The image has been saved in the Image library.', 0);}
                    else if(response===-1) $.show_notice('An image with this title already exists.', 1);
                    else $.show_notice(alert_mess, 1);
                });
            } else $.show_notice(alert_mess, 1);

        },

        init_png_image_preview: function(callback) {
            var svg_data = this.els.navigator_screen_processed.innerHTML, c_w, c_h,
                normal_viewbox = $.attr(this.processed_image, 'viewBox');

            if(normal_viewbox) {
                normal_viewbox = normal_viewbox.split(' ');
                c_w = normal_viewbox[2];
                c_h = normal_viewbox[3];
            }

            $.draw_svg_on_canvas(this.els.canvas_processed, this.canvas_processed_ctx, svg_data, [1,callback], c_w, c_h);
        },

        hightlight_actual_shape: function(shape) {
            $.add_class(shape, 'hl');
        },

        mute_actual_shape: function(reset_prev_shape_index) {
            if(this.shapes[this.prev_shape_index]) $.remove_class(this.shapes[this.prev_shape_index], 'hl');
            if(reset_prev_shape_index) this.prev_shape_index = false;
        },

        hightlight_navigator_screen_processed: function() {
            $.add_class(this.els.navigator_screen_processed, 'wireframe');
        },

        mute_navigator_screen_processed: function() {
            $.remove_class(this.els.navigator_screen_processed, 'wireframe');
        },

        // MULTIFILE OPERATIONS

        get_next_file: function() {
            var next_btn = this.els.next_btn,
                files_count = this.files.length,
                new_title;

            // Update index of actual file
            this.actual_file++;

            if(this.actual_file < files_count) {

                // Update progress bar
                this.els.pbar_label_actual_val.innerHTML = this.actual_file + 1;
                this.els.pbar_indicator.style.width = ((this.actual_file + 1) / files_count * 100)+'%';

                // Update file title
                new_title = this.files[this.actual_file].name;
                this.original_title = new_title;
                this.original_title.value = new_title;

                // Get all data of actual file and re-init all UI
                setTimeout(function() {
                   self.re_init();
                }, 1000);

            }

            // Finish
            if(this.actual_file === files_count-1){
                next_btn.innerHTML = 'FINISH';
                $.off('click', self.get_next_file, next_btn);
                $.on('click', function(){
                    self.finish();
                },next_btn);
            }

        },

        re_init: function() {
            if(this.files[this.actual_file]) {

                var file_name = this.files[this.actual_file]['name'],
                    file_path = $.plugin_dir+'temp/'+file_name+'.svg',
                    parsed_data,
                    error_callback = function() { $.trigger_initial_notice(['SVG parsing problems','file','load','loaded']); };

                $.send($.plugin_url, {action: 'parse_svg_file', file_path: file_path, file_name: file_name}, function(response){
                    if(response==='0'||response===0) error_callback();
                    else {
                        parsed_data = JSON.parse(response);
                        if(parsed_data) {
                            if(parsed_data.ui_view && parsed_data.viewbox && parsed_data.svg_data_model && parsed_data.ui_dep_map) {

                                // Render shape controllers
                                self.els.ctrls_container.innerHTML = parsed_data.ui_view;

                                // Set new viewbox
                                self.viewbox = parsed_data.viewbox;

                                // Set new svg_data_model
                                self.svg_data_model = parsed_data.svg_data_model;

                                // Set new ui_dep_map
                                self.ui_dep_map = parsed_data.ui_dep_map;

                                // Render original file preview
                                self.render_navigator_screen_original(parsed_data.svg_original);

                                // Render processed file preview
                                self.render_navigator_screen_processed(parsed_data.svg_processed);

                                // Re-init shapes
                                self.init_shapes();

                                // Re-init all shape controllers
                                self.init_shape_controllers();

                                // Re-init, set file title
                                self.init_file_title();

                                // Reset duplicates resaving
                                self.is_saved = false;

                                if(parsed_data.trash.length>0) render_trash_container(parsed_data.trash);
                                self.toggle_trash_container(parsed_data.trash.length);


                            } else error_callback();
                        } else error_callback();
                    }
                });

            }
        },

        render_navigator_screen_original: function(data_str) {
            this.els.navigator_screen_original.innerHTML = data_str;
        },

        render_navigator_screen_processed: function(data_str) {
            this.els.navigator_screen_processed.innerHTML = data_str;
            this.processed_image = this.els.navigator_screen_processed.childNodes[0];
        },

        render_trash_container: function(data_arr) {
            this.els.trash_container.innerHTML = data_arr.join(', ');
        },

        toggle_trash_container: function(is_visible) {
            $.toggle_visibility(this.els.trash_container, 'block', is_visible);
        },

        finish: function() {
             window.location = $.plugin_url+'_uploadroom';
        },

        get_model_as_json: function() {
            return JSON.stringify(this.svg_data_model);
        },

    };

    return self;

})(ICONSFACTORY, ICONSFACTORY.UNIVERSAL_PAGE_CTRLS);
