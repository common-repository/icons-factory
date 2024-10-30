
window.ViewElement_icons_factory = vc.shortcode_view.extend({

    changeShortcodeParams: function(model) {
        window.ViewElement_icons_factory.__super__.changeShortcodeParams.call(this, model);
        ICONSFACTORY.STOREROOM.set_new_model_to_vc(model);
    },

    editElement: function(e) {
        window.ViewElement_icons_factory.__super__.editElement.call(this, e);
        var actual_data = this.model._previousAttributes.params;
        if(actual_data)
            ICONSFACTORY.STOREROOM.set_actual_shortcode_data_from_vc(actual_data);
    }

});
