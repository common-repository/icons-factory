/*  @license Copyright 2018  Artemy  https://svgsprite.com  */

var ICONSFACTORY_CLIENT = (function () {

    var slug = 'icons_factory',
        self = {

            anims: false,
            anims_data: {},
            anims_data_len: 0,

            init: function() {
                this.init_animation();
            },

            init_animation: function() {

                var wh = window.innerHeight,
                    scroll_top = window.pageYOffset || document.scrollTop || 0;

                this.anims = document.getElementsByClassName(slug+'_anim');

                for (var i = 0; i < this.anims.length; i++) {

                    var parent = this.anims[i].parentNode,
                        rect = parent.getBoundingClientRect(),
                        top = rect.top+scroll_top,
						bottom = rect.bottom+scroll_top,
						wrap = parent.parentNode.parentNode.parentNode,
						delay = wrap.getAttribute('data-anim_delay') || 0;

                    this.anims_data[i] = [bottom, parseInt(delay)];
                };

                this.anims_data_len = this.anims_data.length;


                this.check_and_start_animation(scroll_top + wh);

                window.addEventListener('scroll', function(event) {
                    var scroll_top = this.scrollY,
                        scroll_bottom = scroll_top + wh;
                    self.check_and_start_animation(scroll_bottom);

                }, false);

            },

            check_and_start_animation: function(y){
                if(this.anims_data) {
                    for (i in this.anims_data) {
                        if(y >= this.anims_data[i][0]) {
							if(this.anims[i].getAttribute('class').indexOf('start_anim') === -1) {
								this.start_animation(i,this.anims[i],this.anims_data[i][1]);
							}
                            // delete this.anims_data[i];
                        }
                    }
                }
            },

            start_animation: function(i,el,delay) {
                setTimeout(function() {
                    var new_class = el.getAttribute('class')+' '+slug+'_start_anim';
                    el.setAttribute('class', new_class);
                }, delay);
			}


	};

    return self;
})();


document.addEventListener('DOMContentLoaded', function(){
    ICONSFACTORY_CLIENT.init();
});

