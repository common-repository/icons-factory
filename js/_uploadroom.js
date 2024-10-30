ICONSFACTORY.UPLOADROOM = (function ($) {

    var self =  {

        init: function() {
            this.init_upload_area();
        },

        // Upload SVG files (first step) or upload a premade image set
        init_upload_area: function() {

            // Get two main buttons from the top of uploader page
            var target_fields = $.gcl('auto_submit');

            if(target_fields) {
                for (var i = 0; i < target_fields.length; i++) {

                    var field = target_fields[i],
                        is_disabled = $.attr(field, 'data-disabled');

                    if(is_disabled) {

                        $.on('click', function(event){
                            var new_cln_title_input = $.ge('title');
                            $.show_notice('You have no registered image collections. Please create a new record in the collection manager.',1);
                            if(new_cln_title_input) new_cln_title_input.focus();
                            $.cancel_bubble(event);
                        }, field);

                    } else {

                        $.on('change', function(){
                            var type_filter = $.attr(this, 'data-type_filter');
                            if($.check_allowed_file_type(this.files, type_filter)) {
                                this.parentNode.submit();
                            } else $.show_notice('Sorry but allowed file type is: <strong>'+type_filter+'</strong>.',1);
                        }, field);
                    }

                }
            }

        },

    };

    return self;

})(ICONSFACTORY);
