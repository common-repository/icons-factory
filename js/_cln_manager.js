ICONSFACTORY.CLN_MANAGER = (function ($, library) {

    var self =  {

        args: arguments, // Temp variable with module dependencies

        els: [
            'cln_manager_form',
            'cln_selectbox',
            'cln_logo_img',
            'cln_logo_preview'
        ],

        // Expected props from the server
        cln_data        : false,
        cln_fields_data : false,
        cln_btns_data   : false,

        // Local props, data models and states
        cln_logo_img    : false,
        default_logo    : false,
        cln_selectbox   : false,
        cln_btns_els    : {},
        cln_fields_els  : {},
        actual_cln      : false,


        init: function() {

            if(!(this.cln_data && this.cln_fields_data && this.cln_btns_data)) {
                $.show_fatal_notice('Empty initial data');
                return false;
            }

            // Cache UI elements
            this.cache_ui_els(this.cln_fields_data, this.cln_fields_els);
            this.cache_ui_els(this.cln_btns_data, this.cln_btns_els, true);

            // Save default logo image
            this.default_logo = $.attr(this.els.cln_logo_img, 'src');
            this.cln_fields_data['logo'] = this.default_logo;
            this.cln_fields_els['logo'] = $.ge('cln_logo_input');

            // Set all event listeners

            // Collections selectbox
            if(this.els.cln_selectbox) $.on('change', function(event){
                self.select_cln(this.value);
                $.cancel_bubble(event);
            }, this.els.cln_selectbox);

            // Collection logo input
            $.on('change', function(event){ self.check_cln_logo_file(this); }, this.cln_fields_els.logo);

            // Cache some library elements
            library.els.lib = $.ge('lib');
            library.els.lib_external_wrapper = $.ge('lib_external_wrapper');

        },

        cache_ui_els: function(data_obj, els_obj, set_listener) {
            for(key in data_obj) {
                els_obj[key] = $.ge(key);
                if(set_listener) {
                    if(els_obj[key]) {
                        $.on('click',function(event){
                            var act = $.attr(this, 'data-act');
                            self.cln_handlers_wrapper(act);
                        },els_obj[key]);
                    }
                }
            }
        },

        cln_handlers_wrapper: function(act) {

            if(act) {

                // Set "action" key
                this.cln_fields_els['action'].value = act;

                // Set "cln_id" key
                this.cln_fields_els['cln_id'].value = this.actual_cln;

                // Call a collections method
                this[act](this);

            }
        },

        save_cln: function(self) {

            $.cancel_bubble(window.event);

            var access = self.check_cln_fields_and_get_new_data(),
                alert_mess = 'Oops, unable to save your collection. Call to Guru, please.';

            if(access) {

                // Set "as_blank" key
                self.cln_fields_els['as_blank'].value = self.actual_cln;

                // Send collections manager form
                self.els.cln_manager_form.submit();


            } else console.log('No access to save this collection');

        },

        render_cln: function(self) {
            $.cancel_bubble(window.event);
            library.toggle_lib(true);
            library.render_cln(self.actual_cln);
        },


        del_cln: function(self) {

            $.cancel_bubble(window.event);

            // Check "cln_id"
            if(!self.actual_cln) {
                $.show_notice('Unable to delete this collection.');
                return false;
            }

            // Send collections manager form
            if(confirm('Do you really want to do this? You can cancel this operation to make a reserve copy. Just click on the "Download button" (It is a little button with an arrow). In any case after deleting – all shortcodes and presets which used images from this collection will not be available.')) self.els.cln_manager_form.submit();
            else return false;
        },

        dwnld_cln: function(self) {
            $.cancel_bubble(window.event);

            // Set "content" key for the "quick_file_saver"
            self.cln_fields_els['content'].value = self.actual_cln;

            if(self.actual_cln) self.els.cln_manager_form.submit();
            else $.show_notice('Unable to download this collection. Try to reload the page.');
        },

        // request_wrapper: function() {

        // },

        select_cln: function(cln) {

            // Show or hide cln btns. Do it only if was a switching between blank and existing collections
            if(!(cln!=='0' && this.actual_cln!==false)) {
                for(key in this.cln_btns_els) {
                    if(this.cln_btns_data[key][1].indexOf('hidden')>-1) $.toggle_visibility(this.cln_btns_els[key],  'inline-block', cln!=='0');
                }
                if($.library_visibility) library.toggle_lib(false);
            }

            this.actual_cln = cln==='0' ? false : cln;
            $.attr(this.els.cln_logo_img, 'src', (cln==='0' ? this.default_logo : this.cln_data[cln]['logo']));

            if(cln!=='0') {
                for(key in this.cln_fields_els) {
                    var new_value = this.cln_data[cln][key];
                    $.attr(this.cln_fields_els[key], 'value', new_value);
                }
            }

            library.toggle_lib();
        },

        check_cln_fields_and_get_new_data: function() {

            var access = true,
                new_cln_data = {},
                cln_id,
                new_value,
                alert_cl = 'alert_mess';

            for(key in this.cln_fields_els) {
                var el = this.cln_fields_els[key],
                    value,
					is_hidden = this.cln_fields_data[key][2]==='hidden' || key==='logo';

				var is_not_required = key === 'email' || key === 'license';

                if(el) value = el.value; else {access = false; break;}

                // Check all visible fields. Change wrong values
                if(!is_hidden && !is_not_required) {
                    if(value) {
                        switch (key) {
                          case 'version': new_value = $.float_num_to_string(value); if(new_value) el.value = new_value; break;
                          case 'email'  : new_value = $.check_email(value); if(new_value) el.value = new_value; break;
                          default       : new_value = value;
                        }
                        if(new_value) new_cln_data[key] = new_value; else access = false;
                    }
                    else {
                        access = false;
                        new_value = value;
                    }
                    $.toggle_class(el, alert_cl, !new_value);
                }

                // Check logo image if it's existed
                if(key==='logo') {
                    if(!this.check_cln_logo_file(this.cln_fields_els['logo'], alert_cl)) access = false;
                }

            }

            if(access) {
                this.cln_fields_els.cln_id.value = this.actual_cln;
                return true;
            } else return false;

        },

        check_cln_logo_file: function(logo_input, alert_cl) {

            if(logo_input['files'].length===1) {
                var file = logo_input['files'][0];
                if(file.type!=='image/png') {
                    $.add_class(this.els.cln_logo_preview, alert_cl);
                    return false;
                } else {
                    $.remove_class(this.els.cln_logo_preview, alert_cl);
                    this.set_cln_logo_temp_src(file);
                    return true;
                }
            } return true;
        },

        set_cln_logo_temp_src: function(file) {
            if(FileReader) {
                var fr = new FileReader();
                fr.onload = function () {
                    self.els.cln_logo_img.src = fr.result;
                }
                fr.readAsDataURL(file);
            }
        }

    };

    return self;

})(ICONSFACTORY, ICONSFACTORY.LIBRARY);
