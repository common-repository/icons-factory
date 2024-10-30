ICONSFACTORY.SUPPORTROOM = (function ($) {

    var alert_cl = 'alert_mess',

        self =  {

            els: [
                'support_form',
                'notes',
                'subject',
                'sender',
                'message',
				'submit',
				// 'download_backup',
				// 'upload_backup'
            ],

            state: {
                prev_subject: 0
            },

            // Data from the server
            subjects: false,

            init: function() { console.log(this.subjects);

                // Cache notes
                // this.els.notes

                // Set private event listeners

                $.on('click', function(event){
                    $.cancel_bubble(event);
                    self.submit_form();
                }, this.els.submit);

                $.on('change', function(){
                    self.toggle_subject(this.value);
                }, this.els.subject);

                $.on('focus', function(){
                    $.remove_class(self.els.sender, alert_cl);
                }, this.els.sender);

                $.on('focus', function(){
                    $.remove_class(self.els.message, alert_cl);
				}, this.els.message);

				// $.on('click', function(event){
                //     self.download_backup();
				// }, this.els.download_backup);

				// $.on('click', function(event){
                //     self.upload_backup();
                // }, this.els.upload_backup);

            },

            toggle_subject: function(val) {
                var index = this.subjects.indexOf(val);
                this.els.notes.children[this.state.prev_subject].style.display = 'none';
                this.els.notes.children[index].style.display = 'inline-block';
                this.state.prev_subject = index;
            },

            submit_form: function() {
                if(this.check_form()) this.els.support_form.submit();
            },

            check_form: function() {
                var error = false;
                if(this.els.sender && this.els.message) {
                    if(!$.check_email(this.els.sender.value)) {$.add_class(this.els.sender, alert_cl); error = true;}
                    if(!this.els.message.value) {$.add_class(this.els.message, alert_cl); error = true;}
                } else error = true;
                return !error;
			},

			download_backup: function() {
				// Send a request
				$.send($.plugin_url, {action: 'download_backup'}, function(response){
					// console.log('response', response);
					// if(response==='0' || response===0) {
					// 	if(el) el.parentNode.removeChild(el);
					// }
					// self.trigger_initial_notice([response,'item','remove','removed']);
				});
			}

    };

    return self;

})(ICONSFACTORY);
