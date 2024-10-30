
window.InlineShortcodeView_icons_factory = window.InlineShortcodeView.extend({

    edit: function (e) {
        window.InlineShortcodeView_icons_factory.__super__.edit.call(this, e);
        var actual_data = this.model._previousAttributes.params;
        if(actual_data) {
			ICONSFACTORY.STOREROOM.set_actual_shortcode_data_from_vc(actual_data);
		}
    },

    beforeUpdate: function (e) {
        window.InlineShortcodeView_icons_factory.__super__.beforeUpdate.call(this);

        ICONSFACTORY.STOREROOM.set_new_model_to_vc(this.model);

        // Render results
		window.InlineShortcodeView_icons_factory.__super__.render.call(this);

		if(ICONSFACTORY_CLIENT) {
			setTimeout(() => {
				ICONSFACTORY_CLIENT.init();
			}, 300);
		}

    }

});
