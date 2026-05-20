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

opendxp.registerNS('opendxp.settings.videothumbnail.item');
/**
 * @private
 */
opendxp.settings.videothumbnail.item = Class.create({

    initialize: function (data, parentPanel) {
        this.parentPanel = parentPanel;
        this.data = data;
        this.currentIndex = 0;
        this.medias = {};

        this.addLayout();
        this.addMediaPanel('default', this.data.items, false, true);

        if (this.data.medias) {
            Ext.iterate(this.data.medias, (key, items) => {
                this.addMediaPanel(key, items, true, false);
            });
        }
    },

    addLayout: function () {
        const buttonConfig = {
            text: t('save'),
            iconCls: 'opendxp_icon_apply',
            handler: this.save.bind(this),
            disabled: !this.data.writeable
        };

        if (!this.data.writeable) {
            buttonConfig.tooltip = t('config_not_writeable');
        }

        this.mediaPanel = new Ext.TabPanel({
            autoHeight: true,
            plugins: [Ext.create('Ext.ux.TabReorderer', {})]
        });

        const addViewPortButton = {
            xtype: 'panel',
            style: 'margin-bottom: 15px',
            items: [{
                xtype: 'button',
                style: 'float: right',
                text: t('add_media_segment'),
                iconCls: 'opendxp_icon_add',
                handler: () => {
                    Ext.MessageBox.prompt('', t('enter_media_segment'), (button, value) => {
                        if (button === 'ok') {
                            this.addMediaPanel(value, null, true, true);
                        }
                    }, null, false, '500K');
                }
            }, {
                xtype: 'component',
                style: 'float: right; padding: 8px 40px 0 0;',
                html: t('dash_media_message')
            }]
        };

        this.groupField = new Ext.form.field.Text({
            name: 'group',
            value: this.data.group,
            fieldLabel: t('group'),
            width: 450
        });

        this.settings = new Ext.form.FormPanel({
            border: false,
            labelWidth: 150,
            defaults: {
                renderer: Ext.util.Format.htmlEncode
            },
            items: [{
                xtype: 'panel',
                autoHeight: true,
                border: false,
                loader: {
                    url: Routing.generate('opendxp_admin_settings_videothumbnailadaptercheck'),
                    autoLoad: true
                }
            }, {
                xtype: 'textfield',
                name: 'name',
                value: this.data.name,
                fieldLabel: t('name'),
                width: 450,
                readOnly: true
            }, {
                xtype: 'textarea',
                name: 'description',
                value: this.data.description,
                fieldLabel: t('description'),
                width: 450,
                height: 100
            }, this.groupField, {
                xtype: 'combo',
                name: 'present',
                fieldLabel: t('select_presetting'),
                triggerAction: 'all',
                mode: 'local',
                width: 300,
                store: [['average', t('average')], ['good', t('good')], ['best', t('best')]],
                listeners: {
                    select: (el) => {
                        const presets = {
                            average: { vb: 400, ab: 128 },
                            good:    { vb: 600, ab: 128 },
                            best:    { vb: 800, ab: 196 },
                        };
                        const preset = presets[el.getValue()];
                        if (preset) {
                            this.settings.getComponent('videoBitrate').setValue(preset.vb);
                            this.settings.getComponent('audioBitrate').setValue(preset.ab);
                        }
                    }
                }
            }, {
                xtype: 'numberfield',
                name: 'videoBitrate',
                itemId: 'videoBitrate',
                value: this.data.videoBitrate,
                fieldLabel: t('video_bitrate'),
                width: 250
            }, {
                xtype: 'numberfield',
                name: 'audioBitrate',
                itemId: 'audioBitrate',
                value: this.data.audioBitrate,
                fieldLabel: t('audio_bitrate'),
                width: 250
            }]
        });

        this.panel = new Ext.Panel({
            border: false,
            closable: true,
            autoScroll: true,
            bodyStyle: 'padding: 20px;',
            title: this.data.name,
            id: `opendxp_videothumbnail_panel_${this.data.name}`,
            items: [this.settings, addViewPortButton, this.mediaPanel],
            buttons: [buttonConfig]
        });

        this.parentPanel.getEditPanel().add(this.panel);
        this.parentPanel.getEditPanel().setActiveTab(this.panel);

        opendxp.layout.refresh();
    },

    addMediaPanel: function (name, items, closable, activate) {
        name = name.replace(/[^a-zA-Z0-9_\-+]/g, '');

        if (this.medias[name]) {
            return;
        }

        const addMenu = Object.keys(opendxp.settings.videothumbnail.items)
            .filter(type => type.startsWith('item'))
            .map(type => ({
                iconCls: 'opendxp_icon_add',
                handler: this.addItem.bind(this, name, type),
                text: opendxp.settings.videothumbnail.items[type](null, null, true)
            }));

        const title = name === 'default' ? t('default') : name;

        const itemContainer = new Ext.Panel({
            title: title,
            tbar: [{
                text: t('transformations'),
                iconCls: 'opendxp_icon_add',
                menu: addMenu
            }],
            border: false,
            closable: closable,
            autoHeight: true,
            listeners: {
                close: () => {
                    delete this.medias[name];
                }
            }
        });

        this.medias[name] = itemContainer;

        if (items && items.length > 0) {
            items.forEach(item => {
                this.addItem(name, `item${ucfirst(item.method)}`, item.arguments);
            });
        }

        this.mediaPanel.add(itemContainer);
        this.mediaPanel.updateLayout();

        if (activate) {
            this.mediaPanel.setActiveTab(itemContainer);
        }

        return itemContainer;
    },

    addItem: function (name, type, data) {
        const item = opendxp.settings.videothumbnail.items[type](this.medias[name], data);
        this.medias[name].add(item);
        this.medias[name].updateLayout();
        this.currentIndex++;
    },

    getData: function () {
        const mediaData = {};
        const mediaOrder = {};

        Ext.iterate(this.medias, (key, value) => {
            mediaData[key] = [];
            mediaOrder[key] = this.mediaPanel.tabBar.items.indexOf(value.tab);
            value.items.getRange().forEach(item => {
                mediaData[key].push(item.getForm().getFieldValues());
            });
        });

        return {
            settings: Ext.encode(this.settings.getForm().getFieldValues()),
            medias: Ext.encode(mediaData),
            mediaOrder: Ext.encode(mediaOrder),
            name: this.data.name
        };
    },

    save: function () {
        let reload = false;
        const newGroup = this.groupField.getValue();

        if (newGroup != this.data.group) {
            this.data.group = newGroup;
            reload = true;
        }

        Ext.Ajax.request({
            url: Routing.generate('opendxp_admin_settings_videothumbnailupdate'),
            method: 'PUT',
            params: this.getData(),
            success: this.saveOnComplete.bind(this, reload)
        });
    },

    saveOnComplete: function (reload) {
        if (reload) {
            this.parentPanel.tree.getStore().load({
                node: this.parentPanel.tree.getRootNode()
            });
        }

        opendxp.helpers.showNotification(t('success'), t('saved_successfully'), 'success');
    },

    getCurrentIndex: function () {
        return this.currentIndex;
    }
});