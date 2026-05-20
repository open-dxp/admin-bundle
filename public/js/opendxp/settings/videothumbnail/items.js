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

opendxp.registerNS('opendxp.settings.videothumbnail.items');
/**
 * @private
 */
opendxp.settings.videothumbnail.items = {

    getTopBar: function (name, index, parent) {
        return [{
            xtype: 'tbtext',
            text: `<b>${name}</b>`
        }, '-', {
            iconCls: 'opendxp_icon_up',
            handler: function (blockId, container) {
                const blockElement = Ext.getCmp(blockId);
                container.moveBefore(blockElement, blockElement.previousSibling());
            }.bind(window, index, parent)
        }, {
            iconCls: 'opendxp_icon_down',
            handler: function (blockId, container) {
                const blockElement = Ext.getCmp(blockId);
                container.moveAfter(blockElement, blockElement.nextSibling());
            }.bind(window, index, parent)
        }, '->', {
            iconCls: 'opendxp_icon_delete',
            handler: function (blockId, container) {
                container.remove(Ext.getCmp(blockId));
            }.bind(window, index, parent)
        }];
    },

    itemResize: function (panel, data, getName) {
        const niceName = t('resize');
        if (getName) {
            return niceName;
        }

        data = data ?? {};
        const myId = Ext.id();

        return new Ext.form.FormPanel({
            id: myId,
            style: 'margin-top: 10px',
            border: true,
            bodyStyle: 'padding: 10px;',
            tbar: this.getTopBar(niceName, myId, panel),
            items: [{
                xtype: 'fieldset',
                layout: 'hbox',
                style: 'border-top: none !important;',
                border: 'false',
                padding: 0,
                items: [{
                    xtype: 'numberfield',
                    name: 'width',
                    style: 'padding-right: 10px',
                    fieldLabel: `${t('width')}, ${t('height')}`,
                    width: 210,
                    value: data.width
                }, {
                    xtype: 'numberfield',
                    name: 'height',
                    hideLabel: true,
                    width: 95,
                    value: data.height
                }]
            }, {
                xtype: 'hidden',
                name: 'type',
                value: 'resize'
            }, {
                xtype: 'displayfield',
                hideLabel: true,
                value: `<small style='color: red;'>${t('width_and_height_must_be_an_even_number')}</small>`
            }]
        });
    },

    itemScaleByHeight: function (panel, data, getName) {
        const niceName = t('scalebyheight');
        if (getName) {
            return niceName;
        }

        data = data ?? {};
        const myId = Ext.id();

        return new Ext.form.FormPanel({
            id: myId,
            style: 'margin-top: 10px',
            border: true,
            bodyStyle: 'padding: 10px;',
            tbar: this.getTopBar(niceName, myId, panel),
            items: [{
                xtype: 'numberfield',
                name: 'height',
                fieldLabel: t('height'),
                width: 250,
                value: data.height
            }, {
                xtype: 'checkbox',
                name: 'forceResize',
                checked: (data['forceResize'] !== false),
                fieldLabel: t('force_resize')
            }, {
                xtype: 'hidden',
                name: 'type',
                value: 'scaleByHeight'
            }]
        });
    },

    itemScaleByWidth: function (panel, data, getName) {
        const niceName = t('scalebywidth');
        if (getName) {
            return niceName;
        }

        data = data ?? {};
        const myId = Ext.id();

        return new Ext.form.FormPanel({
            id: myId,
            style: 'margin-top: 10px',
            border: true,
            bodyStyle: 'padding: 10px;',
            tbar: this.getTopBar(niceName, myId, panel),
            items: [{
                xtype: 'numberfield',
                name: 'width',
                fieldLabel: t('width'),
                width: 250,
                value: data.width
            }, {
                xtype: 'checkbox',
                name: 'forceResize',
                checked: (data['forceResize'] !== false),
                fieldLabel: t('force_resize')
            }, {
                xtype: 'hidden',
                name: 'type',
                value: 'scaleByWidth'
            }]
        });
    },

    itemCut: function (panel, data, getName) {
        const niceName = t('cut');
        if (getName) {
            return niceName;
        }

        data = data ?? { start: '00:00:00', duration: '00:00:00' };
        const myId = Ext.id();

        return new Ext.form.FormPanel({
            id: myId,
            style: 'margin-top: 10px',
            border: true,
            bodyStyle: 'padding: 10px;',
            tbar: this.getTopBar(niceName, myId, panel),
            items: [{
                xtype: 'textfield',
                name: 'start',
                fieldLabel: t('start'),
                width: 250,
                value: data.start,
                regex: /^\d*:?[0-5]\d:?[0-5]\d\.?\d*$/,
                emptyText: 'HH:MM:SS.MS'
            }, {
                xtype: 'textfield',
                name: 'duration',
                fieldLabel: t('duration'),
                width: 250,
                value: data.duration,
                regex: /^\d*:?[0-5]\d:?[0-5]\d\.?\d*$/,
                emptyText: 'HH:MM:SS.MS'
            }, {
                xtype: 'hidden',
                name: 'type',
                value: 'cut'
            }]
        });
    },

    itemColorChannelMixer: function (panel, data, getName) {
        const niceName = t('colorChannelMixer');
        if (getName) {
            return niceName;
        }

        data = data ?? { effect: 'bw' };
        const myId = Ext.id();

        return new Ext.form.FormPanel({
            id: myId,
            style: 'margin-top: 10px',
            border: true,
            bodyStyle: 'padding: 10px;',
            tbar: this.getTopBar(niceName, myId, panel),
            items: [{
                xtype: 'combobox',
                name: 'effect',
                fieldLabel: t('effect'),
                width: 450,
                value: data.effect,
                store: [
                    ['.9:0:0:0:0:1.1:0:0:0:0:1:0:0:0:0:1', 'Cold'],
                    ['.3:.4:.3:0:.3:.4:.3:0:.3:.4:.3', 'Grayscale'],
                    ['.393:.769:.189:0:.349:.686:.168:0:.272:.534:.131', 'Sepia'],
                ],
                required: true
            }, {
                xtype: 'hidden',
                name: 'type',
                value: 'colorChannelMixer'
            }]
        });
    },

    itemMute: function (panel, data, getName) {
        const niceName = t('mute');
        if (getName) {
            return niceName;
        }

        const myId = Ext.id();

        return new Ext.form.FormPanel({
            id: myId,
            style: 'margin-top: 10px',
            border: true,
            bodyStyle: 'padding: 10px;',
            tbar: this.getTopBar(niceName, myId, panel),
            items: [{
                xtype: 'container',
                html: t('this_filter_has_no_settings')
            }, {
                xtype: 'hidden',
                name: 'type',
                value: 'mute'
            }]
        });
    },

    itemSetFramerate: function (panel, data, getName) {
        const niceName = t('setframerate');
        if (getName) {
            return niceName;
        }

        data = data ?? { fps: 1 };
        const myId = Ext.id();

        return new Ext.form.FormPanel({
            id: myId,
            style: 'margin-top: 10px',
            border: true,
            bodyStyle: 'padding: 10px;',
            tbar: this.getTopBar(niceName, myId, panel),
            items: [{
                xtype: 'numberfield',
                name: 'fps',
                fieldLabel: t('fps'),
                minValue: 1,
                maxValue: 60,
                width: 250,
                value: data.fps
            }, {
                xtype: 'hidden',
                name: 'type',
                value: 'setFramerate'
            }]
        });
    }
};