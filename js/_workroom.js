ICONSFACTORY.WORKROOM = (function ($, page_ctrls) {

    var self =  {

        args: arguments, // Temp variable with module dependencies

        init: function() {
            var control_point = 680; // Scrolling offset value for fixed layouts
            page_ctrls.init_smart_layout('workroom_layer_2', ['','wr_landscape']);
            page_ctrls.init_scroller(control_point);
            page_ctrls.init_fixed_layout(control_point);
        }

    };

    return self;

})(ICONSFACTORY, ICONSFACTORY.UNIVERSAL_PAGE_CTRLS);
