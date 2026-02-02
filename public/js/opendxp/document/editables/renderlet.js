/**
 * OpenDXP
 *
 * This source file is licensed under the GNU General Public License version 3 (GPLv3).
 *
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.ch)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

opendxp.registerNS('opendxp.document.editables.renderlet');
/**
 * @private
 */
opendxp.document.editables.renderlet = Class.create(opendxp.document.editable, {

    defaultHeight: 100,

    initialize: function($super, id, name, config, data, inherited) {
        $super(id, name, config, data, inherited);

        //TODO maybe there is a nicer way, the Panel doesn't like this
        this.controller = config.controller;
        delete(config.controller);

        this.data = data ?? {};

        // height management
        if (this.config.defaultHeight) {
            this.defaultHeight = this.config.defaultHeight;
        }

        if (this.config.height) {
            this.initalHeightSet = true;
        }
        else {
            this.initalHeightSet = false;
            this.config.height = this.data.id ? 'auto' : this.defaultHeight;
        }

        this.config.name = id + '_editable';
        this.config.border = false;
        this.config.bodyStyle = 'min-height: 40px;';
    },

    render: function() {
        this.setupWrapper();

        this.element = new Ext.Panel(this.config);
        this.element.on('render', function (el) {
            // register at global DnD manager
            dndManager.addDropTarget(el.getEl(), this.onNodeOver.bind(this), this.onNodeDrop.bind(this));

            this.getBody().insertHtml('beforeEnd','<div class="opendxp_editable_droptarget"></div>');
            this.getBody().addCls('opendxp_editable_snippet_empty');

            el.getEl().on('contextmenu', this.onContextMenu.bind(this));

        }.bind(this));

        this.element.render(this.id);

        if (this.data.id) {
            this.updateContent();
        }
    },

    onNodeDrop: function (target, dd, e, data) {
        if(!opendxp.helpers.dragAndDropValidateSingleItem(data)) {
            return false;
        }

        var record = data.records[0];
        data = record.data;

        this.data.id = data.id;
        this.data.type = data.elementType;
        this.data.subtype = data.type;

        if (this.config.type) {
            if (this.config.type != data.elementType) {
                return false;
            }
        }

        if (this.config.className) {
            if(Array.isArray(this.config.className)) {
                if (this.config.className.indexOf(data.className) < 0) {
                    return false;
                }
            } else {
                if (this.config.className != data.className) {
                    return false;
                }
            }
        }

        if (this.config.reload) {
            this.reloadDocument();
        } else {
            this.updateContent();
        }

        return true;
    },

    onNodeOver: function(target, dd, e, data) {
        if (data.records.length !== 1) {
            return false;
        }

        data = data.records[0].data;
        if (this.config.type) {
            if (this.config.type != data.elementType) {
                return false;
            }
        }

        if (this.config.className) {
            if(Array.isArray(this.config.className)) {
                if (this.config.className.indexOf(data.className) < 0) {
                    return false;
                }
            } else {
                if (this.config.className != data.className) {
                    return false;
                }
            }
        }

        return Ext.dd.DropZone.prototype.dropAllowed;
    },

    getBodyWrap: function () {
        var bodyId = this.element.getEl().query('.' + Ext.baseCSSPrefix + 'panel-bodyWrap')[0].getAttribute('id');
        return Ext.get(bodyId);
    },

    getBody: function () {
        // get the id from the body element of the panel because there is no method to set body's html
        // (only in configure)
        var bodyId = this.element.getEl().query('.' + Ext.baseCSSPrefix + 'panel-body')[0].getAttribute('id');
        return Ext.get(bodyId);
    },

    updateContent: function () {
        var self = this;

        this.getBody().removeCls('opendxp_editable_snippet_empty');
        this.getBody().dom.innerHTML = '<br />&nbsp;&nbsp;Loading ...';

        var params = this.data;
        params.controller = this.controller;
        Ext.apply(params, this.config);

        try {
            // add the id of the current document, so that the renderlet knows in which document it is embedded
            // this information is then grabbed in OpenDxp_Controller_Action_Frontend::init() to set the correct locale
            params['opendxp_parentDocument'] = window.editWindow.document.id;
        } catch (e) {
        }

        if ('undefined' !== typeof window.editWindow.targetGroup && window.editWindow.targetGroup.getValue()) {
            params['_ptg'] = window.editWindow.targetGroup.getValue();
        }

        var setContent = function(content) {
            self.getBody().dom.innerHTML = content;
            self.getBody().insertHtml('beforeEnd','<div class="opendxp_editable_droptarget"></div>');
            self.updateDimensions();
        };

        Ext.Ajax.request({
            method: 'GET',
            url: Routing.generate('opendxp_admin_document_renderlet_renderlet'),
            success: function (response) {
                setContent(response.responseText);
            }.bind(this),

            failure: function(response) {
                var message = response.responseText;

                try {
                    var json = Ext.decode(response.responseText);
                    if (json && 'undefined' !== typeof json.message) {
                        message = '<strong style="color:red">' + json.message + '</strong>';
                    }
                } catch (e) {
                    // noop - fall back to responseText
                }

                message = '<br />&nbsp;&nbsp;' + message;

                setContent(message);
            }.bind(this),

            params: params
        });
    },

    updateDimensions: function () {
        if(this.initalHeightSet){
            if(this.config.height !== 'auto'){
                this.getBodyWrap().setStyle({
                    overflowY: 'auto',
                });
            }
        }
        else{
            this.element.setStyle({
                height: this.data.id ? 'auto' : this.defaultHeight + 'px',
            });
            this.getBodyWrap().setStyle({
                height: this.data.id ? 'auto' : '100%',
            });
        }
        this.getBody().setStyle({
            height: this.data.id ? 'auto' : this.config.title ? 'calc(100% - 49px)' : '100%',
        })
    },

    onContextMenu: function (e) {
        var menu = new Ext.menu.Menu();

        if(this.data['id']) {
            menu.add(new Ext.menu.Item({
                text: t('empty'),
                iconCls: 'opendxp_icon_delete',
                handler: function () {
                    this.data = {};
                    this.getBody().update('');
                    this.getBody().insertHtml('beforeEnd','<div class="opendxp_editable_droptarget"></div>');
                    this.getBody().addCls('opendxp_editable_snippet_empty');

                    if (this.config.reload) {
                        this.reloadDocument();
                    }

                    this.updateDimensions();
                }.bind(this)
            }));

            menu.add(new Ext.menu.Item({
                text: t('open'),
                iconCls: 'opendxp_icon_open',
                handler: function () {
                    if(this.data.id) {
                        opendxp.helpers.openElement(this.data.id, this.data.type, this.data.subtype);
                    }
                }.bind(this)
            }));

            if (opendxp.elementservice.showLocateInTreeButton('document')) {
                menu.add(new Ext.menu.Item({
                    text: t('show_in_tree'),
                    iconCls: 'opendxp_icon_show_in_tree',
                    handler: function (item) {
                        item.parentMenu.destroy();
                        opendxp.treenodelocator.showInTree(this.data.id, this.data.type);
                    }.bind(this)
                }));
            }
        }

        if(opendxp.helpers.hasSearchImplementation()) {
            menu.add(new Ext.menu.Item({
                text: t('search'),
                iconCls: 'opendxp_icon_search',
                handler: function (item) {
                    item.parentMenu.destroy();
                    this.openSearchEditor();
                }.bind(this)
            }));
        }


        menu.showAt(e.getXY());

        e.stopEvent();
    },

    openSearchEditor: function () {
        var restrictions = {};

        if (this.config.type) {
            restrictions.type = [this.config.type];
        }
        if (this.config.className) {
            restrictions.specific = {
                classes: [this.config.className]
            };
        }

        opendxp.helpers.itemselector(false, this.addDataFromSelector.bind(this), restrictions, {
            context: this.getContext()
        });
    },

    addDataFromSelector: function (item) {
        if(item) {
            this.data.id = item.id;
            this.data.type = item.type;
            this.data.subtype = item.subtype;

            if (this.config.reload) {
                this.reloadDocument();
            } else {
                this.updateContent();
            }
        }
    },

    getValue: function () {
        return this.data;
    },

    getType: function () {
        return 'renderlet';
    }
});
