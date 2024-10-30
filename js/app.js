// @koala-append "_universal_page_ctrls.js"
// @koala-append "_custom_ui.js"

// @koala-append "_library.js"
// @koala-append "_svg_parser.js"
// @koala-append "_cln_manager.js"
// @koala-append "_svg_designer.js"

// @koala-append "_workroom.js"
// @koala-append "_storeroom.js"
// @koala-append "_uploadroom.js"
// @koala-append "_supportroom.js"

/*  @license Copyright 2017  Artemy Krylov  (email : letter2artemy@gmail.com)  */

var ICONSFACTORY = (function () {

    var slug = 'icons_factory',
        debug = !1,
        self = {

        slug: slug,            // Global namespace
        plugin_url: false,     // "http://.../wordpress/wp-admin/admin.php?page={slug}"
        plugin_dir: false,     // Full path to the plugin root directory
        plugin_dir_url: false, // Full URL to the plugin root directory
        action_key: false,     // Key from a POST request
        base_palette: false,   // Color palette of default plugin images

        // PAGE MODULES

        WORKROOM: {},
        STOREROOM: {},
        UPLOADROOM: {},
        SUPPORTROOM: {},

        // UNIVERSAL MODULES

        LIBRARY: {},
        SVG_DESIGNER: {},
        SVG_PARSER: {},
        CLN_MANAGER: {},
        VC: {},

        active_modules: [],

        // BASE MODULE INITIALISATION

        init: function(props, module_name) {
            if(props) {
                var target = module_name ? this[module_name] : this;
                props = JSON.parse(props);
                for(var prop in props) target[prop] = props[prop];
                if(target.notice) this.trigger_initial_notice(target.notice);
            }
            if(module_name) {

                var module = this[module_name],
                    deps = module.args;

                // Check dependencies of actual module
                if(typeof deps !== 'undefined') {
                    if(!this.check_depends(deps)) return false;
                    delete module.args;
                }

                // Save module name to a local module prop
                module['module_name'] = module_name;

                // Call module subscribe or full init method
                if(module.only_subscribe) module.subscribe();
                else {
                    // Cashe all announced DOM elements in actual module (in "els" variable)
                    if(module.els) module.els = this.cache_dom_els(module.els);
                    // Init
                    module.init();
                }

                // Add actual module to the list of active modules
                this.active_modules.push(module_name);

            }
        },

        // Check all dependicies of a module
        check_depends: function(args) {
                var f = [];
                for(i = 0; i < args.length; i++) {
                    if (typeof args[i] === 'undefined') {
                        f.push(i+1);
                        access = false;
                    }
                }
                if(f.length>0) {
                    this.show_fatal_notice('Failed loading modules with indexes: '+f.join(', '));
                    return false;
                }
                return true;
        },

        // EVENTS

        on: function(event_name, fn, el, off_mode) {
            if(typeof el === 'undefined' ||  el === null) {
				// console.log('Unable to '+(off_mode?'remove':'add')+' an event listener. Reason: Empty element. Args:', arguments);
				return false;
			}
            if(el===false) el =  window;
            if(off_mode) el.removeEventListener(event_name, fn);
            else el.addEventListener(event_name, fn);
        },

        off: function(event_name, fn, el) {
            this.on(event_name, fn, el, true);
        },

        trigger: function(event_name, data, el) {
            if(typeof(Event) === 'function') {
                var e = new Event(event_name);
            }else{
                var e = document.createEvent('Event');
                e.initEvent(event_name, true, true);
            }
            e.data = data,
            el = el || window;
            el.dispatchEvent(e);
        },

        cancel_bubble: function(event) {
            var e = event || window.event;
            if (typeof e.stopPropagation !== 'undefined') e.stopPropagation();
            else e.cancelBubble = true;
            e.preventDefault();
        },

        // DOM

        // Get element by id
        ge: function(id) {
            return document.getElementById(slug+'_'+id);
        },

        // Create element
        ce: function(el_type, attrs, content, as_htm) {
            var el = document.createElement(el_type);
            if(attrs) {
                for(var attr in attrs) {
                    this.attr(el, attr, attrs[attr]);
                }
            }
            if(content) el.innerHTML = content;
            return as_htm ? el.outerHTML :el;
        },

        // Get elements by class name
        gcl: function(cl, el) {
            el = el || document;
            return el.getElementsByClassName(slug+'_'+cl);
        },

        // Get elements by tag name. If index = -1 then return all array of founded elements
        // If index is undefined then return the first founded element
        gt: function(tag, index, el) {
            var result;
            index = typeof index !== 'undefined' ? index : 0;
            el = el || document;
            result = el.getElementsByTagName(tag);
            return index === -1 ? result : result[index];
        },

        // Set or get an attribute
        attr: function(el, attr, value) {
            if(!el || (el && typeof el.getAttribute !== 'function')) return false;
            return value ? el.setAttribute(attr, value) : el.getAttribute(attr);
        },

        // Add a CSS class
        add_class: function(el, cl, replace_all) {
            if(el && cl) {
                var full_cl_name = this.attr(el, 'class');
                cl = slug+'_'+cl;
                if(!full_cl_name || replace_all) {this.attr(el, 'class', cl); return true;}
                full_cl_name = full_cl_name+(full_cl_name.indexOf(cl)===-1 ? (full_cl_name?' ':'')+cl : '');
                this.attr(el, 'class', full_cl_name);
                return true;
            } else return false;
        },

        // Remove a CSS class
        remove_class: function(el, cl) {
            if(el) {

                if(cl==='') {this.attr(el, 'class', ' '); return true;}

                var full_cl_name = this.attr(el, 'class');
                cl = slug+'_'+cl;
                if(full_cl_name.indexOf(cl)!==-1) {
                    full_cl_name = full_cl_name.replace(cl, '').trim();
                    this.attr(el, 'class', full_cl_name);
                }
            } else return false;
        },

        // Toggle a CSS class
        toggle_class: function(el, cl, is_on) {
            if(el) {
                if(is_on) this.add_class(el, cl, false);
                else this.remove_class(el, cl);
            } else return false;
        },

        // Toggle "display" prop of an element
        toggle_visibility: function(el, display_attr, is_visible) {
            if(el) {
                el.style.display = is_visible ? display_attr : 'none';
                return true;
            } else return false;
        },

        // Cashe DOM elements on a list (array)
        cache_dom_els: function(els_id_list) {
            var obj = {}, unfound = [];
            for (var i = 0; i < els_id_list.length; i++) {
                var id = els_id_list[i],
                    el = this.ge(id);
                if(!el) unfound.push(id);
                obj[id] = el;
            };
            if(unfound.length>0) {
				// console.log('Not found: ', unfound.join(', '));
			}
            return obj;
        },

        // CLIPBOARD

        // Copy data from the clipboard to some form field or paste it to some form field
        clipboard: function(el, mode) {
            if(el) {
                el.select();
                if(this.supports_storage) {
                    if(mode===0) localStorage.setItem(slug+'_clipboard', el.value);
                    else {
                        var storage_clipboard = localStorage.getItem(slug+'_clipboard');
                        if(storage_clipboard) el.value = storage_clipboard;
                    }
                    this.add_class(el, 'success_mess');
                    setTimeout(function() { self.remove_class(el, 'success_mess'); }, 300);
                }
                try {
                    document.execCommand(mode===0?'copy':'paste');
                    this.add_class(el, 'success_mess');
                    el.blur();
                } catch (err) {
                    this.show_notice('Oops, unable to copy/paste.',1);
                }
            }
        },

        supports_storage: function() {
            try {
                return 'localStorage' in window && window['localStorage'] !== null;
            } catch (e) {
                return false;
          }
        },

        // AJAX

        send: function(url, data, callback) {
            var xobj = new XMLHttpRequest(),
                send_data = slug+'_json_data='+JSON.stringify(data);
            if(url) {
                xobj.open('POST', url, true);
                xobj.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
                if(callback) {
                    xobj.onreadystatechange = function () {
                        if (xobj.readyState == 4 && xobj.status == '200') {
                            callback(xobj.responseText);
                        } else {
                            if(xobj.status != '200')
                                self.show_notice('Data sending problems.', 1);
                        }
                    };
                }
                xobj.send(send_data);
            } else this.show_notice('Data sending problems. Invalid or empty URL of current request: '+url, 1);
        },

        // QUICK FILE SAVER
        // Function genarates a form, assigns the data as JSON string to a hidden field and sends request to the server
        // Server fn: dwnld_file()
        quick_file_saver: function(data) {

            var file_input = this.ce('input',{
                    name : 'content',
                    type : 'hidden',
                    value: JSON.stringify(data)
                }, false, true),
                format_input = this.ce('input',{
                    name : 'data_format',
                    type : 'hidden',
                    value: 'json'
                }, false, true),
                type_input = this.ce('input',{
                    name : 'content_type',
                    type : 'hidden',
                    value: 'svg'
                }, false, true),
                action_input = this.ce('input',{
                    name : 'action',
                    type : 'hidden',
                    value: 'dwnld'
                }, false, true),
                form = this.ce('form', {
                    method: 'post',
                    action: this.plugin_url,
                    style : 'display:none;'
                }, file_input+format_input+type_input+action_input);

            document.body.appendChild(form);
            form.submit();
            setTimeout(function() { document.body.removeChild(form); }, 300);
        },

        // Delete files and selected DOM node
        delete_files: function(el, files, do_confirm) {

            // Hightlight actual item
            el.style.backgroundColor = 'tomato';

            // Confirm this request on deleting
            if(do_confirm) {
                if(!confirm('Do you really want to do this?')) {
                    el.style.backgroundColor = 'transparent';
                    return false;
                }
			}


            // Send the request
            this.send(this.plugin_url, {action: 'delete_files', files: files}, function(response){
				console.log('response',typeof response, response);
                if(response==='0' || response===0) {
					if(el) {
						el.parentNode.removeChild(el);
					}
				}
                self.trigger_initial_notice([response,'item','remove','removed']);
            });

        },

        // Draw SVG data on Canvas
        draw_svg_on_canvas: function(c, ctx, svg_data, callback, c_w, c_h) {


            var temp_img = new Image(),
                c_w = c_w || 512,
                c_h = c_h || 512,
                error = false;

            if(svg_data) {

                // Resize processed canvas
                this.attr(c, 'width', c_w);
                this.attr(c, 'height', c_h);

                // Set SVG image to processed canvas
                svg_data = 'data:image/svg+xml;base64,'+btoa(svg_data);
                temp_img.src = svg_data;

                // Draw temp_img on the canvas and send new canvas data to next saving function
                this.on('load', function() {
                    ctx.drawImage(temp_img, 0, 0);
                    var c_data = c.toDataURL(), callback_args;
                    if(c_data) {
                        if(callback) {
                            if(callback[0]) c_data = c_data.slice(c_data.indexOf(',')+1);
                            callback_args = callback.length===3 ? callback[2] : c_data;
                            if(callback.length===3) {
                                callback[2].png_data = c_data;
                                callback_args = callback[2];
                            } else callback_args = c_data;
                            callback[1](callback_args);
                        }
                    } else error = 'Unable to draw Image on the canvas to save it. Reason: maybe browser doesn\'t support this operation.';
                }, temp_img);

            } else error = 'Empty SVG data.';

            if(error!==false) this.show_notice(error, 1);
            return !error;
        },

        save_canvas_data_as_png: function(link_el, new_file_name, data) {
            this.attr(link_el,'download', slug+'_'+new_file_name+'.png');
            this.attr(link_el,'href',data);
            link_el.click();
        },

        save_file_to_wp_media: function(file_name, file_ext, file_data) {
            this.send(this.plugin_url, {action: 'save_file_to_wp_media', file_name: file_name, file_ext: file_ext, file_data: file_data}, false);
            this.show_notice('OK! Please, check your Media Library.', 0);
        },

        // SMALL HELPERS

        // Highlight user changes in some form field
        highlight_changes: function(target_field_el) {
            this.add_class(target_field_el, 'success_mess');
            setTimeout(function() { self.remove_class(target_field_el, 'success_mess'); }, 300);
        },

        // Example: "1,2,3" -> [1,2,3]
        explode_num_str_to_arr: function(str) {
            return str.split(',').map(function(elem) { return parseFloat(elem); });
        },

        // Example: (1.12345, 2) -> 1.12
        round_num: function(val, c) {
            c = c>0?Math.pow(10,c):1;
            return (parseFloat(val)*c^0)/c;
        },

        // Anything variable convert to string
        var_to_str: function(val) {
            return String(val).toLowerCase();
        },

        clean_id: function(id) {
            if(id) return id.replace(slug+'_','');
        },

        // Clone an object
        clone_obj: function(obj, json_method) {
            if(json_method) return JSON.parse(JSON.stringify(obj));
            var clone = {};
            for(var key in obj) clone[key] = obj[key];
            return clone;
        },

        // Parse shortcode string [name key="val" ... ]
        // Return Object {key:val, ...}
        parse_shortcode_string: function(string) {
            var start_index = string.indexOf('['),
                end_index = string.indexOf(']'),
                slug_index = string.indexOf(slug),
                syntax_error = false,
                model = {},
                matches;
            if(string) {
                if(start_index===-1 || end_index === -1 || slug_index===-1) {
                    syntax_error = true;
                } else {
                    string = string.slice(start_index+1,end_index);
                    matches = string.match(/[\w-]+="[^"]*"/g);
                    if(matches.length>0) {
                        for (var i = 0; i < matches.length; i++) {
                            var temp_arr = matches[i].split('=');
                            model[temp_arr[0].trim()] = temp_arr[1].trim().slice(1,-1);
                        };
                    } else syntax_error = true;
                }
            } else this.show_notice('Empty value of the target form field.',1);

            if(syntax_error===false) return model;
            else {
                this.show_notice('Sorry but your shortcode syntax is incorrect.',1);
                return false;
            }
        },


        // Collapse 2D array to clean object
        //
        // Before:
        //  [
        //     [2, '#a'],
        //     [3, '#b'],
        //     [5, '#a'],
        //     [10,'#a']
        //  ]
        //
        // After: { '2;5;10':'#a', '3':'#b' }

        collapse_2Darray_to_clean_object: function(arr) {
            var arr_len = arr.length,
                ids = '',
                target_value,
                norm = {},
                unique_values = [];

            for (var i = 0; i < arr_len; i++) {
                target_value = arr[i][1];

                if(unique_values.indexOf(target_value)===-1) {

                    for (var j = 0; j < arr_len; j++) {
                        if(arr[j][1]===target_value) ids += arr[j][0]+';';
                    };

                    norm[ids.slice(0,-1)] = target_value;
                    ids = '';
                    unique_values.push(target_value);
                }

            }

            return norm;
        },

        check_allowed_file_type: function(files, type_filter) {
            var access = true,
                file_count = files.length;

            if(file_count>0 && type_filter) {
                for (var i = 0; i < file_count; i++) {
                    var file = files[i];
                    if(this.get_normal_file_type(file.type) !== type_filter) access = false;
                };
            } else access = false;

            return access;
        },

        get_normal_file_type: function(type) {
            if(type==='image/svg+xml') return 'svg';
            else if(type==='application/zip') return 'zip';
            else if(type==='image/png') return 'png';
            else return false;
        },

        // Escape a string (e.g. HTML string for using in JSON)
        escape_sting: function(str, URI_mode) {
            if(str) {
                str = str.replace(/\s+/g,' ');
                return URI_mode ? encodeURIComponent(str.replace(/"/g, '&quot;').replace(/'/g, '&apos;')) :
                                str.replace(/\"|\[|\]|\'|\&|\r\n|\n|\r|\t|\.|,|<|>/gm, '').trim();
            }
            console.log('Empty string');
            return false;
        },

        // Example: ' My  crazy TITLE -_ ' -> 'my-crazy-title'
        title_to_string: function(title) {
            if(title) {
                title = title.replace(/ic_/,'');
                var matches = title.toLowerCase().match(/[a-z0-9]+/g);
                if(matches) return matches.join('-');
            }
            console.log('Empty string!')
            return false;
        },

        // Example: ' version 1','1','1.1','1-1','01-1' -> '1.1' or '1{glue}1'
        float_num_to_string: function(val, glue) {
            if(!glue) glue = '.';
            var matches = val.toLowerCase().match(/[0-9]+/g);
            if(matches) {
                if(matches.length>1) return matches.map(Number).join(glue);
                else return matches+'.0';
            }
            console.log('Empty string!')
            return false;
        },

        // Simplified function to check and return a clean email address
        check_email: function(val) {
            if(!val) return false;
            var p1 = val.split('@'), p2;
            if(p1.length===2) {
                p2 = p1[1].split('.');
                if(p2.length===2) {
                    return p1[0].trim()+'@'+p2[0].trim()+'.'+p2[1].trim();
                } else return false;
            } else return false;
        },

        // OTHER

        show_notice: function(message, type) {
            if(!this.notice_container) this.notice_container = this.ge('response');
            if(typeof this.notice_container === 'undefined') {alert(message); return;}

            var type_class, timer = 7000;
            if(type===0) type_class = 'success';
            else if(type===1) {type_class = 'alert'; timer = 14000;}
            else if(type===-1) type_class = 'fatal_error';

            this.notice_container.className =  slug+'_notification '+slug+'_active_notification '+slug+'_'+type_class+'_mess';
            this.notice_container.innerHTML = '<div>'+message+'</div>';

            if(type!==-1) {
                setTimeout(function() {
                    self.remove_class(self.notice_container, 'active_notification');
                }, timer);
            }
        },

        trigger_initial_notice: function(notice) {

            if(notice.length===4) {

                var error = notice[0],
                    subject = notice[1],
                    verb = notice[2],
                    verb_past = notice[3],
                    mess,
                    type;

                if(error==='false' || error===false || typeof error === 'undefined' || error==='0' || error===0) {
                    mess = 'Ok! The '+subject+' has been '+verb_past+'.';
                    type = 0;
                } else {

                    mess = 'Oops, unable to '+verb+' this '+subject+'. Reason: '+error+'.'; // Call to Guru, please.
                    type = 1;
                }

                this.show_notice(mess, type);

            } else console.log(notice);

        },

        show_fatal_notice: function(reason) {
            this.show_notice('The plugin was stopped. Reason: '+reason+'. Please call to Guru: letter2artemy@gmail.com.',-1);
        },

        // Confirm a page refreshing
        confirm_page_refreshing: function(off) {
            window.onbeforeunload = off ? '' : function() {
                return 'Are you sure you want to leave?';
            }
        },

    };

    return self;

})();

// Print map of all active modules in the App
function appmap() {
    var m = ICONSFACTORY.active_modules, c = m.length;
    if(c>0) {
        for (var i = 0; i < c; i++) {
            console.log(m[i], ICONSFACTORY[m[i]]);
        };
    } else console.log('There are no modules');
}
