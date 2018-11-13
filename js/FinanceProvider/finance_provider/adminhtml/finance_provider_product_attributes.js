document.observe("dom:loaded", function() {
    var divido_product_plans = {
        initialize: function () {
            this.toggleFields();
            this.bindEvents();
        },

        bindEvents: function () {
            $('plan_option').observe('change', this.toggleFields);
        },

        toggleFields: function () {
            var planSelection = $('plan_option');
            var planListRow   = $('plan_selection').up(1);
            if (planSelection.value == 'selected_plans') {
                planListRow.show();
            } else {
                planListRow.hide();
            }
        }
    }

    divido_product_plans.initialize();
});
