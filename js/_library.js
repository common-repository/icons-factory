ICONSFACTORY.LIBRARY = (function ($) {

    var self =  {

        //Cached DOM elements
        els: [
            'lib',
            'lib_def_wrapper',
            'lib_external_wrapper',
            'lib_filters',
            'lib_item_ui_toggler',
            'lib_filters_toggler',
            'workroom_layer_1'
            // Will be here after some operations:
            // 'prev_filter_el'
        ],

        state: {
            filters_visibility: false,
            lib_visibility: true,
            actual_cln: 'default',
            prev_cln_el: false,
            second_ui: false,
            rendering: false,
        },

        init: function() {
            this.publish();
            this.subscribe();
            this.set_private_listeners();
        },

        publish: function() {
            if(this.els.lib) {
                $.on('click', function(event){
                    var e = event || window.event,
                        target = e.target || e.srcElement,
                        act = $.attr(target, 'data-act'),
						lib_item_id,
						lib_item_cln_id,
                        lib_item,
                        is_shift = e.shiftKey;

                    if(act==='ignore') return;

                    while(!act && target!==e.currentTarget) { target = target.parentNode; act = $.attr(target, 'data-act'); }

                    while(target.tagName.toLowerCase() !== 'label' && target!==e.currentTarget) target = target.parentNode;

                    lib_item = target;
                    lib_item_id = $.attr(lib_item, 'data-id');
                    lib_item_cln_id = $.attr(lib_item, 'data-cln-id') || 'default';

                    // Trigger global event: "sel_lib_item"
                    if(act==='sel') {
						$.trigger(
							'sel_lib_item',
							{
								item_id: lib_item_id,
								cln_id : lib_item_cln_id
							}
						);
					}

                    // Call private methods
                    if(act==='edit') self.edit_item(lib_item_id);
                    if(act==='del')  self.del_item(lib_item_id, lib_item, !is_shift);

                }, this.els.lib);
            }
        },

        subscribe: function() {
            $.on('render_lib', function(event){
                var e = event || window.event;
                self.render_cln(e.data);
            }, false);
        },

        set_private_listeners: function() {

            if(this.els.lib) {

                if(this.els.lib_filters) {
                    $.on('click', function(event){
                        self.filters_handler(event);
                    }, this.els.lib_filters);
                }

                if(this.els.lib_item_ui_toggler) {
                    $.on('click', function(event){
                        self.lib_item_ui_toggler_handler(event, this);
                    }, this.els.lib_item_ui_toggler);
                }

                if(this.els.lib_filters_toggler) {
                    $.on('click', function(event){
                        self.lib_filters_toggler_handler(event, this);
                    }, this.els.lib_filters_toggler);
                }

            }

        },

        lib_item_ui_toggler_handler: function(event, toggler_el) {
            if(this.state.second_ui) {
                toggler_el.innerHTML = 'Show titles';
                this.state.second_ui = false;
                $.remove_class(this.els.lib, 'lib_state_second_ui');
            } else {
                toggler_el.innerHTML = 'Hide titles';
                this.state.second_ui = true;
                $.add_class(this.els.lib, 'lib_state_second_ui');
            }

            $.cancel_bubble(event);
        },

        lib_filters_toggler_handler: function(event, el) {
            this.els.workroom_layer_1.scrollTop = 0;
            this.state.filters_visibility = !this.state.filters_visibility;
            $.toggle_visibility(this.els.lib_filters, 'block', this.state.filters_visibility);
            el.innerHTML = (this.state.filters_visibility ? 'Close' : 'Show')+' filters';
        },

        filters_handler: function (event) {
            var e = event || window.event,
                target = e.target || e.srcElement,
                cln = $.attr(target, 'data-cln');

            while(!cln && target!==e.currentTarget) {
                target = target.parentNode; cln = $.attr(target, 'data-cln');
            }

            if(cln) {
                this.state.actual_cln = cln;

                if(this.state.prev_cln_el) $.remove_class(this.state.prev_cln_el, 'actual_cln');

                if(cln==='default') {
                    this.def_cln_handler(target);
                    this.els.lib_external_wrapper.innerHTML = ''; // Remove external image collection
                } else {
                    if(this.state.rendering===false) { // Asynchronous rendering of image collection
                        this.render_cln(cln);
                        $.add_class(target, 'actual_cln');
                        this.state.prev_cln_el = target;
                    }
                }
            }
        },

        def_cln_handler: function(el) {

            // Mute prev filter element
            if(this.els.prev_filter_el) $.remove_class(this.els.prev_filter_el, 'pressed_btn');

            // Show some category of default image collection
            if(el) {
                var value = $.attr(el, 'data-cat');
                $.add_class(el, 'pressed_btn');
                $.add_class(this.els.lib_def_wrapper, 'lib_state_'+value, true);
                this.els.prev_filter_el = el;
            } else {
                $.remove_class(this.els.lib_def_wrapper, ''); // Hide default collection
            }
        },

        render_cln: function(id) {
            if(id) {
                this.state.rendering = true;
                this.def_cln_handler();
                var t = setTimeout(function() {
                    self.els.lib_external_wrapper.innerHTML = '<p class="'+$.slug+'_p">LOADING. Please wait...</p>';
                }, 300);

                $.send($.plugin_url, {action: 'get_lib_content', cln_id: id}, function(response){
                    self.els.lib_external_wrapper.innerHTML = response;
                    self.state.rendering = false;
                    self.state.actual_cln = id;
                    clearTimeout(t);
                });

            }
        },

        edit_item: function(id) {
            if(id) {
                window.open($.plugin_url+'_uploadroom&file='+id+'&cln='+this.state.actual_cln, '_blank');
            } else console.log('Empty item ID');
        },

        del_item: function(id, el, do_confirm) {
            var cln_dir = 'library/'+this.state.actual_cln+'/';
                files = [cln_dir+id+'.png', cln_dir+id+'.php'];
            $.delete_files(el, files, do_confirm);
        },

        toggle_lib: function(do_visible) {
            $.toggle_visibility(this.els.lib, 'block', do_visible);
            this.state.lib_visibility = do_visible;
        }

    };

    return self;

})(ICONSFACTORY);
