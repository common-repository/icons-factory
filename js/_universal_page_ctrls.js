ICONSFACTORY.UNIVERSAL_PAGE_CTRLS = (function ($) {

    var min_window_width  = 600,
        min_window_height = 770,
        window_width      = 0,
        window_height     = 0,

        self = {


        // COMMON METHODS

        init_window: function() {
            window_width  = window.innerWidth  || document.documentElement.clientWidth  || document.body.clientWidth;
            window_height = window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight;
        },


        // SMART (CUSTOMIZABLE) LAYOUTS

        init_smart_layout: function(layout_id, states) {

            var error_fn = function() {
                // console.log('Some problems with initialization of a Smart layout on this page.');
            };

            this.smart_layout = $.ge(layout_id);

            if(this.smart_layout) {

                // Find controller of this layout
                this.smart_layout_ctrl = $.gcl('smart_layout_ctrl',this.smart_layout)[0];
                if(this.smart_layout_ctrl) {

                    // Set initial state
                    this.smart_layout_state = 0;

                    // Save initial CSS classes
                    this.smart_layout_initial_cl = this.smart_layout.className;

                    // Save array of states
                    this.smart_layout_states = states;

                    $.on('click', function(event) {
                        self.toggle_smart_layout_state();
                    }, this.smart_layout_ctrl);

                } else error_fn();

            } else error_fn();

        },

        toggle_smart_layout_state: function() {
            var new_cl;
            this.smart_layout_state = this.smart_layout_state === (this.smart_layout_states.length - 1) ? 0 : this.smart_layout_state + 1;
            new_cl = this.smart_layout_states[this.smart_layout_state];
            this.smart_layout.className = this.smart_layout_initial_cl + (new_cl ? ' ' + $.slug + '_' + new_cl : '');
        },


        // PAGE SCROLLER (one on one page)

        init_scroller: function(control_point) {

            var scroller = $.gcl('scroller')[0];

            if(scroller) {

                // Set initial value
                this.scroller_state = false;

                $.on('click', function(event) {
                    if(self.scroller_state) {
                        window.scrollTo(0,0);
                        self.scroller_state = false;
                    } else {
                        window.scrollTo(0, control_point);
                        self.scroller_state = true;
                    }
                }, scroller);
            } else {
                console.log('Some problems with initialization of a page scrolling controller on this page.');
            }
        },



        // FIXED LAYOUTS (one on one page)

        init_fixed_layout: function(control_point) {

            this.fixed_layout = $.ge('fixed_layout');

            if(this.fixed_layout) {

                // Save control point value of the fixed layout
                this.fixed_layout_control_point = control_point || 0;

                // Get width and height of actual browser window
                this.init_window();

                if(window_width>min_window_width && window_height>min_window_height)
                    $.on('scroll', function() { self.fixed_layout_handler() }, false);

                $.on('resize', function(event){ self.init_window(); }, false);

            } else {
                console.log('Some problems with initialization of a fixed layout on this page.');
            }
        },

        fixed_layout_handler: function() {
            if(this.fixed_layout) {
                var y = window.pageYOffset || document.documentElement.scrollTop;

                if (y > this.fixed_layout_control_point) {
                    this.fixed_layout.style.transform = 'translateY('+(y-this.fixed_layout_control_point)+'px)';
                    this.state_fixed = true;
                } else {
                    if(this.state_fixed===true) {this.fixed_layout.style.transform = 'translateY(0)'; this.state_fixed = false;}
                    else state_fixed = false;
                }
            }
        }



    };

    return self;

})(ICONSFACTORY);
