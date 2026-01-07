Ext.define('Tualo.routes.MSGraphDocX.Setup', {
    statics: {
        load: async function () {
            return [
                {
                    name: 'msgraph-docx/setup',
                    path: '#msgraph-docx/setup'
                }
            ]
        }
    },
    url: 'msgraph-docx/setup',
    handler: {
        action: function () {

            let mainView = Ext.getApplication().getMainView(),
                stage = mainView.getComponent('dashboard_dashboard').getComponent('stage'),
                component = null,
                cmp_id = 'msgraph_docx_setup';
            component = stage.down(cmp_id);
            if (component) {
                stage.setActiveItem(component);
            } else {
                Ext.getApplication().addView('Tualo.MSGraph.lazy.Setup', {

                });
            }


        },
        before: function (action) {

            action.resume();
        }
    }
});