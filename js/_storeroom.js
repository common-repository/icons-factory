ICONSFACTORY.STOREROOM = (function ($) {

    var self =  {

        args: arguments, // Temp variable with module dependencies

        els: [
            'preset_grid'
        ],

         // Data & UI elements for Visual Composer addon
        vc: false,
        vc_target_field: false,
        vc_actual_shortcode: false,  // Data from the VC model
        vc_mode: 'dynamic',          // Server rendering mode (static SVG file (preset) or dynamic generation (traditional shortcode))

        preset_cards: [],
        def_bg_color: '#eceff1',
        card_bg_state: false,

        init: function() {

            this.set_local_listeners();

            this.cache_preset_cards();

            // For VC storeroom
            if(this.vc) {
                this.els.selected_preset = $.ge('selected_preset');
                if(!$.plugin_url) {
                    $.plugin_url = $.attr(this.els.preset_grid, 'data-url');
                }
            }
        },

        cache_preset_cards: function() {
            var temp = $.gcl('preset') ,i;
            if(temp) {
                for (i = 0; i < temp.length; i++) {
                    var card_el = temp[i],
                        card_bg = card_el.style.backgroundColor;
                    this.preset_cards.push([card_el, card_bg]);
                }
            }
        },

        set_local_listeners: function() {
            if(this.els.preset_grid) {
                $.on('click', function(event){
                    self.preset_grid_handler(event);
                }, this.els.preset_grid);

                if(!this.vc) {
                    $.on('click', function(event){
                        self.hide_active_cards();
                    }, false);
                }
            }
        },

        hide_active_cards: function() {
            var active_cards = $.gcl('active');
            while(active_cards.length > 0){
                $.remove_class(active_cards[0], 'active');
            }
        },

        preset_grid_handler: function(event) {
            var e = event || window.event,
                target = e.target || e.srcElement,
                top_container = target,
                act = $.attr(target, 'data-act'),
                id,
                is_shift = e.shiftKey;

            if(target && act!=='nothing') {

                if(this.vc===false && act!=='edit' && act!=='dwnld') $.cancel_bubble(event);

                // if(act!=='vc_copy' && act!=='vc_paste')
                if(act && act!=='bg_toggler') {
                    while (top_container.tagName.toLowerCase() !== 'label') top_container = top_container.parentNode;
                }

                id = $.attr(top_container, 'data-id');

                if(act==='del') this.delete_preset(id, top_container, !is_shift);
                else if(act==='vc_copy_bg') {$.clipboard(target.nextSibling, 0);}
                // else if(act==='copy') $.clipboard(target.parentNode.children[2], 0);
                // else if(act==='vc_copy') $.clipboard(this.vc_target_field, 0);
                // else if(act==='vc_paste') {$.clipboard(this.vc_target_field, 1); this.vc_mode = 'dynamic'; this.find_selected_preset();}
                else if(act==='bg_toggler') {this.toggle_cards_bg(target);}
                else if(this.vc===false && act==='focus') {$.add_class(top_container, 'active');}
                else if(act==='toggle_format') {
                    var static_img_txtarea = target.nextSibling,
                        dwnld_btn = $.gcl('dwnld_btn',top_container)[0];
                    this.toggle_format(target, static_img_txtarea, dwnld_btn);
                }
                else {
                    if(this.vc) {
                        this.vc_target_field.value = id;
                        $.attr(this.els.selected_preset, 'href', $.plugin_url+'&preset='+id);
                        this.vc_mode = 'static';
                    }
                }

            }

            if(this.vc===false && act!=='focus') this.hide_active_cards();
        },

        toggle_cards_bg: function(toggler) {
            if(this.card_bg_state) {
                toggler.innerHTML = 'Hide recommended backgrounds';
                this.card_bg_state = false;
            } else {
                toggler.innerHTML = 'Show recommended backgrounds';
                this.card_bg_state = true;
            }

            $.toggle_class(this.els.preset_grid, 'def_bg_colors', this.card_bg_state);
        },

        find_selected_preset: function() {
            var el = document.querySelector('input[name="'+$.slug+'_radio"]:checked');
            if(el) el.checked = false;
        },

        // Toggle file format of the actual preset card
        toggle_format: function(el, target_field_el, dwnld_btn) {
            var htm = el.innerHTML,
                is_svg = htm==='SVG',
                target_field_val = target_field_el.value,
                dwnld_btn_href = dwnld_btn ? $.attr(dwnld_btn, 'href') : false,
                ext_toggler = function(str) {
                    return str.replace('.'+htm.toLowerCase(),(is_svg ? '.png' : '.svg'));
                };

            el.innerHTML = is_svg ? 'PNG' : 'SVG';

            if(target_field_el) {
                $.highlight_changes(target_field_el);
                target_field_el.value = ext_toggler(target_field_val);
            }

            if(dwnld_btn_href) {
                $.highlight_changes(dwnld_btn);
                $.attr(dwnld_btn, 'href', ext_toggler(dwnld_btn_href));
            }

        },

        delete_preset: function(id, el, do_confirm) {

            // Confirm this request on deleting
            if(do_confirm) if(!confirm('Do you really want to do this?')) return false;

            // Run a deleting event
            $.trigger('del_preset', {id:id, el:el});

        },



        // VISUAL COMPOSER OPTIONS

        init_vc_ui: function() {
            this.vc = true;
            this.els.preset_grid = $.ge('preset_grid');
            this.init();
            this.vc_target_field = $.ge('target_field_vc');
            if(this.vc_target_field && this.vc_actual_shortcode && this.vc_mode==='dynamic') this.vc_target_field.value = this.vc_actual_shortcode;
        },

        // Get actual shortcode model from VC and paste it to target field (textarea in storeroom UI) as shortcode string
        set_actual_shortcode_data_from_vc: function(actual_data) {
            var actual_shortcode = '';
            for(key in actual_data) {
                actual_shortcode += ' '+key+'="'+actual_data[key]+'"';
            }
            this.vc_actual_shortcode = '['+$.slug+' '+actual_shortcode+']';

            this.vc_mode = actual_data.preset ? 'static' : 'dynamic';
        },

        // Save new shortcode params from the target field (textarea in storeroom UI)
        set_new_model_to_vc: function(vc_model) {
            var new_shortcode = this.vc_target_field.value,
                normal_params;

            if(new_shortcode) {
                if(new_shortcode.indexOf('[')!==-1) {
                    this.vc_mode = 'dynamic';
                    normal_params = $.parse_shortcode_string(new_shortcode);
                    if(normal_params) {
                        for(key in vc_model.attributes.params) {
                            if(key==='image_box_size' || key==='image_box_align' || key==='css' || key==='css_class')
                                normal_params[key] = vc_model.attributes.params[key];
                        }
                        vc_model.attributes.params = normal_params;
                        vc_model.changed.params = normal_params;
                    }
                } else this.vc_mode = 'static';
            }

        }

    };

    return self;

})(ICONSFACTORY);
