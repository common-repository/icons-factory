ICONSFACTORY.CUSTOM_UI = (function ($) {


    var
        // Numeric input with two control buttons, example: [-] [value] [+]
        num_ctrls_data = {},

        self = {

            // Special case of a num_controller. SVG transformation
            transform_center     : true,
            transform_locked_els : false,

            init: function() {
                //
            },

            num_ctrls_init: function(callback) {

                var num_ctrl_wrappers = $.gcl('numctrl_wrap');

                if(num_ctrl_wrappers) {
                    for (var i = 0; i < num_ctrl_wrappers.length; i++) {
                        var wrap = num_ctrl_wrappers[i],
                            param = $.attr(wrap, 'data-param'),
                            def_value = $.attr(wrap, 'data-def_value'),
                            target_field = wrap.children[0],
                            value = target_field.value,

                            ctrl_inputs = $.gcl('numctrl_input', wrap);

                        if(def_value.indexOf(',')>-1) def_value = $.explode_num_str_to_arr(def_value);
                        else def_value = [parseFloat(def_value)];

                        if(value) {
                            if(value.indexOf(',')>-1) value = $.explode_num_str_to_arr(value);
                            else value = [parseFloat(value)];
                        } else {
                            value = JSON.parse(JSON.stringify(def_value)); // Clone default value
                        }

                        num_ctrls_data[param] = {
                            els: {
                                target_field: target_field
                            },
                            def_value: def_value,
                            value: value
                        };

                        if(ctrl_inputs) {
                            for (var j = 0; j < ctrl_inputs.length; j++) {
                                var ctrl_input = ctrl_inputs[j]
                                    min = parseInt($.attr(ctrl_input, 'data-min')),
                                    max = parseInt($.attr(ctrl_input, 'data-max')),
                                    step = parseInt($.attr(ctrl_input, 'data-step')) || 1,
                                num_ctrls_data[param].els[j] = [ctrl_input, [step, min, max]];
                                ctrl_input.value = value[j];
                                $.on('change', function(event) { self.num_ctrls_handler(event, true); }, ctrl_input);
                            };
                        }

                        $.on('click', function(event) { self.num_ctrls_handler(event, false); }, wrap);

                    };

                    // Only for "image_transform" param
                    this.transform_locked_els = $.ge('numctrl_locked_content');

                    num_ctrls_data.callback = callback;
                }

            },

            num_ctrls_handler: function(event, manual_input) {
                var e = event || window.event,
                    target = e.target || e.srcElement,
                    param = $.attr(target, 'data-param');

                if(!param) return false;

                var target_index = parseInt($.attr(target, 'data-target')),
                    val,
                    norm_val = false,
                    normal_def_value = false,
                    act = false,
                    target_input = num_ctrls_data[param].els[target_index][0],
                    step = num_ctrls_data[param].els[target_index][1][0],
                    inc = step * (e.shiftKey===true ? 10 : 1);

                if(manual_input) {
                    val = this.check_num_ctrl_value(param, target.value, target_index);
                    num_ctrls_data[param].value[target_index] = val;
                    target_input.value = val;
                    act = true;
                } else {
                    act = $.attr(target, 'data-act');
                    if(act) {
                        val = this.check_num_ctrl_value(param, (num_ctrls_data[param].value[target_index] + (act==='-'?-inc:inc)), target_index);
                        num_ctrls_data[param].value[target_index] = val;
                        target_input.value = val;
                    }
                }

                // Special case of a num_controller. Only for "image_transform" parameter
                if(param==='image_transform') {
                    if(act) {
                        this.update_transform_ctrl(target_index, val);
                    }
                }

                // Final checking
                normal_def_value = num_ctrls_data[param].def_value.join(',');
                norm_val = num_ctrls_data[param].value.join(',');

                if(norm_val !== normal_def_value) {
                    num_ctrls_data[param].els['target_field'].value = norm_val;
                } else {
                    norm_val = false;
                }

                // Final callbacks
                if(val !== false && act) {
                    num_ctrls_data.callback(param, (param==='image_transform'?norm_val:val));
                }
            },

            check_num_ctrl_value: function(param, val, target_index) {

                var min = num_ctrls_data[param].els[target_index][1][1],
                    max = num_ctrls_data[param].els[target_index][1][2],
                    def_val = num_ctrls_data[param].def_value[target_index];

                if(val === '' || typeof val === 'undefined') return def_val;
                val = $.round_num(val,2);
                if(val >= min && val <= max) return val;
                else if(val < min) return min;
                else if(val > max) return max;
                else return def_val;
            },

            update_num_ctrl: function(param, val) {
                if(num_ctrls_data[param])
                    if(num_ctrls_data[param].els) {
                        num_ctrls_data[param].els[0][0].value = val;
                        num_ctrls_data[param].value = [val];
                    }
            },

            // Special case of a num_controller
            update_transform_ctrl: function(target_input, val) {
                if(target_input<0) {
                    if(!Array.isArray(val)) val = $.explode_num_str_to_arr(val);
                    for (var i = 0; i < 3; i++) {
                        num_ctrls_data['image_transform'].value[i] = val[i];
                        num_ctrls_data['image_transform'].els[i][0].value = val[i];
                    };
                    if(val+'' !== num_ctrls_data['image_transform'].def_value+'') {
                        this.transform_center = false;
                        this.transform_locked_els.className = '';
                    }
                } else {
                    if(target_input===0 && this.transform_center) {
						var uniform_offset_val = $.round_num((num_ctrls_data['image_transform'].def_value[0] - val) / 2, 2);

						// Uniform offset for "transform-origin:(top left)" not "center"

                        // num_ctrls_data['image_transform'].value[1] = uniform_offset_val;
                        // num_ctrls_data['image_transform'].value[2] = -uniform_offset_val;
                        // num_ctrls_data['image_transform'].els[1][0].value = uniform_offset_val;
						// num_ctrls_data['image_transform'].els[2][0].value = -uniform_offset_val;

                    } else {
                        this.transform_center = false;
                        this.transform_locked_els.className = '';
                    }
				}
            }

    };

    return self;

})(ICONSFACTORY);
