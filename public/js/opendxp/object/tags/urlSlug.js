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

opendxp.registerNS("opendxp.object.tags.urlSlug");
/**
 * @private
 */
opendxp.object.tags.urlSlug = Class.create(opendxp.object.tags.abstract, {

    type: "urlSlug",

    initialize: function (data, fieldConfig) {

        this.data = [];
        this.usedSiteIds = [];
        this.elements = {};
        this.dirty = false;
        this.formatting = 0;

        if (data) {
            this.data = data;
        }
        this.fieldConfig = fieldConfig;
    },

    getGridColumnEditor: function (field) {
        if (field.layout.noteditable) {
            return null;
        }

        const editorConfig = this.initEditorConfig(field);

        return new Ext.form.TextField(editorConfig);
    },

    getGridColumnFilter: function (field) {
        return {type: 'string', dataIndex: field.key};
    },

    getLayoutEdit: function () {
        this.component = new Ext.Panel();
        this.addSlugElements();
        this.listenToObjectSave();

        return this.component;
    },

    addSlugElements: function () {
        this.addFallbackSlug();
        if (this.data.length > 0) {
            for (var i = 0; i < this.data.length; i++) {
                this.addSiteElement(this.data[i]);
            }
        }

        this.updateSiteFilter();
    },

    /**
     * Saving waits for the formatting, because the formatted slug goes public. Saving may fill or extend a slug, so
     * the slugs are loaded again afterwards.
     */
    listenToObjectSave: function () {
        const preSave = function (event) {
            if (event.detail.object === this.object && this.formatting > 0) {
                opendxp.helpers.showNotification(t("info"), t("url_slug_formatting"), "info");
                event.preventDefault();
            }
        }.bind(this);

        const postSave = function (event) {
            if (event.detail.object === this.object) {
                this.reloadSlugs();
            }
        }.bind(this);

        document.addEventListener(opendxp.events.preSaveObject, preSave);
        document.addEventListener(opendxp.events.postSaveObject, postSave);

        this.component.on('destroy', function () {
            document.removeEventListener(opendxp.events.preSaveObject, preSave);
            document.removeEventListener(opendxp.events.postSaveObject, postSave);
        });
    },

    reloadSlugs: function () {
        const context = this.getContext();

        if (context.subContainerType || !['object', 'localizedfield'].includes(context.containerType)) {
            return;
        }

        Ext.Ajax.request({
            url: Routing.generate('opendxp_admin_dataobject_dataobject_geturlslugs'),
            method: 'GET',
            params: {
                objectId: this.object.id,
                context: Ext.encode(context)
            },
            success: function (response) {
                this.data = Ext.decode(response.responseText).slugs;
                this.elements = {};
                this.usedSiteIds = [];
                this.siteCombo = undefined;
                this.dirty = false;

                Ext.suspendLayouts();
                this.component.removeAll();
                this.addSlugElements();
                Ext.resumeLayouts(true);
            }.bind(this)
        });
    },

    getPrefix: function (siteId) {
        const prefixes = (this.fieldConfig.slugPrefixes || {})[this.getContext().language || ''];

        if (!prefixes) {
            return null;
        }

        return prefixes.hasOwnProperty(siteId) ? prefixes[siteId] : prefixes[0];
    },

    formatSlug: function (field) {
        const text = field.getValue();

        if (!this.fieldConfig.slugGeneratorClass || text === '' || text === field.formattedText) {
            return;
        }

        this.formatting++;
        field.setLoading(true);

        Ext.Ajax.request({
            url: Routing.generate('opendxp_admin_dataobject_dataobject_formaturlslug'),
            method: 'POST',
            params: {
                objectId: this.object.id,
                context: Ext.encode(this.getContext()),
                siteId: field.siteId,
                text: field.slugPrefix === null ? text.replace(/^\/+/, '') : text
            },
            success: function (response) {
                const slug = Ext.decode(response.responseText).slug;

                field.formattedText = slug !== '' && field.slugPrefix === null ? '/' + slug : slug;
                field.setValue(field.formattedText);
            },
            failure: function () {
                opendxp.helpers.showNotification(t("error"), t("url_slug_format_failed"), "error");
            },
            callback: function () {
                this.formatting--;
                field.setLoading(false);
            },
            scope: this
        });
    },

    addFallbackSlug: function () {
        var needed = false;
        if (this.data.length > 0) {
            let firstElement = this.data[0];
            if (firstElement['siteId'] > 0) {
                needed = true;
            }
        } else {
            needed = true;
        }

        if (needed) {
            this.addSiteElement({               // fallback slug must be always there, even if empty
                siteId: 0
            });

        }
    },

    updateSiteFilter: function () {
        if (typeof this.siteCombo === 'undefined') {
            return;
        }

        var showCombo = false;
        this.siteCombo.setFilters([
            function (item) {
                var siteId = item.get("id");
                if (this.elements[siteId]) {
                    return false;
                }
                showCombo = true;
                return true;
            }.bind(this)
        ]);
        if (showCombo) {
            this.siteCombo.show();
        } else {
            this.siteCombo.hide();
        }
    },

    addSiteElement: function (siteData) {

        Ext.suspendLayouts();

        var fieldContainer = new Ext.form.FieldContainer({
            layout: {
                type: 'hbox',
                align: 'middle'
            }
        });


        var domain = '';
        this.usedSiteIds.push(siteData['siteId']);

        if (siteData['siteId'] > 0) {
            domain = " (" + t('site') + ")";
        } else if (opendxp.globalmanager.get("sites").getCount() > 1) {
            domain = " (" + t('fallback') + ")";
        }

        var title = this.fieldConfig.title ? this.fieldConfig.title : this.fieldConfig.name;
        var storedSlug = siteData['slug'] || '';
        var prefix = this.getPrefix(siteData['siteId']);
        var prefixed = prefix !== null && (storedSlug === '' || storedSlug.startsWith(prefix + '/'));
        var locked = storedSlug !== '' && !this.fieldConfig.noteditable;
        var triggers = {};

        if (locked) {
            triggers.lock = {
                cls: 'opendxp_url_slug_trigger opendxp_icon_lock',
                hideOnReadOnly: false
            };
        }

        var textConfig = {
            xtype: "textfield",
            fieldLabel: title + domain,
            name: "slug",
            labelWidth: this.getLabelWidth(),
            value: prefixed ? storedSlug.substring(prefix.length + 1) : storedSlug,
            readOnly: locked,
            triggers: triggers,
            emptyText: this.fieldConfig.fillEmptySlug && !siteData['siteId'] ? t("url_slug_filled_on_save") : '',
            componentCls: this.getWrapperClassNames(prefix === null ? '' : 'opendxp_url_slug_prefixed'),
            afterLabelTpl: this.getPrefixTpl(prefixed ? prefix : null),
            validator: function(value) {
                if (value) {
                    if (text.slugPrefix !== null) {
                        value = '/' + value;
                    }

                    if (!value.startsWith('/') || value.length < 2) {
                        return false;

                    }
                    value = value.substring(1);
                    value = value.replace(/\/$/, "");

                    const parts = value.split('/');
                    for (let i = 0; i < parts.length; i++) {
                        let part = parts[i];
                        if  (part.length === 0) {
                            return false;
                        }
                    }
                }

                return true;
            }
        };
        if (this.fieldConfig.width) {
            textConfig.width = this.fieldConfig.width;
        } else {
            textConfig.width = 350;
        }

        if (prefixed) {
            textConfig.width += this.measure(prefix + '/') + 4;
        }

        // data type allows to configure a field-level label width, otherwise the parent label width gets applied.
        if (this.fieldConfig.domainLabelWidth) {
            textConfig.labelWidth = this.fieldConfig.domainLabelWidth;
        }

        if (this.fieldConfig.labelAlign) {
            textConfig.labelAlign = this.fieldConfig.labelAlign;
        }

        if (!this.fieldConfig.labelAlign || 'left' === this.fieldConfig.labelAlign) {
            textConfig.width = this.sumWidths(textConfig.width, textConfig.labelWidth);
        }

        var text = new Ext.form.TextField(textConfig);
        text.siteId = siteData['siteId'];
        text.slugPrefix = prefixed ? prefix : null;
        text.storedSlug = storedSlug;
        text.storedValue = textConfig.value;
        text.formattedText = textConfig.value;
        text.lockTooltip = t(prefix === null || prefixed ? "url_slug_locked" : "url_slug_other_prefix");

        text.on('afterrender', function (field) {
            const lock = field.getTrigger('lock');

            if (lock) {
                lock.getEl().dom.setAttribute('data-qtip', field.lockTooltip);

                // ExtJS ignores the handler of a trigger while its field is read only.
                lock.getEl().on('click', function () {
                    if (field.readOnly) {
                        this.unlock(field, prefix);
                    } else {
                        this.lock(field);
                    }
                }.bind(this));
            }
        }.bind(this));

        text.on('blur', function (field) {
            this.formatSlug(field);
        }.bind(this));

        var containerItems = [text];

        if (siteData['siteId'] > 0) {
            if (!this.fieldConfig.noteditable) {
                containerItems.push({
                    xtype: "button",
                    iconCls: "opendxp_icon_delete",
                    handler: function (fieldContainer, siteId) {
                        this.dirty = true;
                        this.component.remove(fieldContainer);
                        delete this.elements[siteId];
                        this.updateSiteFilter();

                    }.bind(this, fieldContainer, siteData['siteId'])
                });
            }

            containerItems.push({
                xtype: "component",
                cls: "opendxp_url_slug_domain",
                html: Ext.util.Format.htmlEncode(siteData['domain'])
            });
        } else {
            let siteData = [];
            let allSitesStore = opendxp.globalmanager.get("sites");
            allSitesStore.each(function (record, id) {
                let siteId = record.get("id");
                if (siteId !== "default") {
                    if (this.fieldConfig.availableSites !== null && this.fieldConfig.availableSites.length > 0
                        && !in_array(siteId, this.fieldConfig.availableSites)) {
                        return;
                    }

                    siteData.push([siteId, record.get("domain")]);
                }
            }.bind(this));

            if (siteData.length > 0) {
                // only show combo if something to select which is not the case if there are no sites at all
                this.siteCombo = new Ext.form.ComboBox({
                    triggerAction: "all",
                    editable: true,
                    selectOnFocus: true,
                    queryMode: 'local',
                    typeAhead: true,
                    forceSelection: true,
                    fieldLabel: t("add_site"),
                    store: new Ext.data.ArrayStore({
                        fields: [
                            'id',
                            'domain'
                        ],
                        data: siteData
                    }),
                    listeners: {
                        select: function (combo, record, eOpts) {
                            combo.setValue(null);
                            var siteId = record.getId();
                            if (this.elements[siteId]) {
                                return;
                            }

                            this.addSiteElement({
                                siteId: siteId,
                                domain: record.get('domain')
                            });
                            this.dirty = true;
                            this.updateSiteFilter();

                        }.bind(this)
                    },
                    valueField: 'id',
                    displayField: 'domain',
                });
                containerItems.push(this.siteCombo);
            }
        }

        this.elements[siteData['siteId']] = text;
        fieldContainer.add(containerItems);
        this.component.add(fieldContainer);
        Ext.resumeLayouts();
    },

    getPrefixTpl: function (prefix) {
        if (prefix === null) {
            return '';
        }

        return '<div class="opendxp_url_slug_prefix">' + Ext.util.Format.htmlEncode(prefix + '/') + '</div>';
    },

    measure: function (text) {
        return Ext.util.TextMetrics.measure(Ext.getBody(), text).width;
    },

    getLabelWidth: function () {
        if (this.fieldConfig.domainLabelWidth) {
            return this.fieldConfig.domainLabelWidth;
        }

        const title = this.fieldConfig.title ? this.fieldConfig.title : this.fieldConfig.name;

        return Math.max(
            this.fieldConfig.labelWidth || 100,
            this.measure(title + ' (' + t('fallback') + '):') + 10,
            this.measure(title + ' (' + t('site') + '):') + 10
        );
    },

    /**
     * A slug saved with another prefix gets the current prefix once it is unlocked, and keeps its last path segment.
     */
    unlock: function (field, prefix) {
        if (prefix !== null && field.slugPrefix === null) {
            field.slugPrefix = prefix;
            field.setValue(field.storedSlug.split('/').pop());
            field.bodyEl.insertHtml('beforeBegin', this.getPrefixTpl(prefix));
        }

        field.setReadOnly(false);
        field.getTrigger('lock').getEl().replaceCls('opendxp_icon_lock', 'opendxp_icon_unlock');
        field.getTrigger('lock').getEl().dom.setAttribute('data-qtip', t("url_slug_unlocked"));
        field.focus([field.getValue().length, field.getValue().length]);
    },

    lock: function (field) {
        if (field.storedValue === field.storedSlug && field.slugPrefix !== null) {
            field.slugPrefix = null;
            field.el.down('.opendxp_url_slug_prefix').destroy();
        }

        field.setValue(field.storedValue);
        field.formattedText = field.storedValue;
        field.setReadOnly(true);
        field.getTrigger('lock').getEl().replaceCls('opendxp_icon_unlock', 'opendxp_icon_lock');
        field.getTrigger('lock').getEl().dom.setAttribute('data-qtip', field.lockTooltip);
    },

    getSlug: function (field) {
        const value = field.getValue();

        if (field.slugPrefix === null || value === '') {
            return value;
        }

        return field.slugPrefix + '/' + value;
    },

    getLayoutShow: function () {
        var layout = this.getLayoutEdit();
        for (let key in this.elements) {
            if (this.elements.hasOwnProperty(key)) {
                this.elements[key].setReadOnly(true);

                if (this.elements[key].getTrigger('lock')) {
                    this.elements[key].getTrigger('lock').hide();
                }
            }
        }

        if (this.siteCombo) {
            this.siteCombo.hide();
        }

        return layout;
    },

    getValue: function () {
        var value = [];

        for (let key in this.elements) {
            if (this.elements.hasOwnProperty(key)) {
                let textfield = this.elements[key];
                value.push([key, this.getSlug(textfield), textfield.storedSlug]);
            }
        }

        return value;
    },

    getName: function () {
        return this.fieldConfig.name;
    },

    getGridColumnConfig: function (field) {
        return {
            text: t(field.label), sortable: false, dataIndex: field.key,
            editor: this.getGridColumnEditor(field)
        };
    },

    isDirty: function () {
        if (this.dirty) {
            return true;
        }

        for (let key in this.elements) {
            if (this.elements.hasOwnProperty(key)) {
                let textfield = this.elements[key];
                if (textfield.isDirty()) {
                    this.dirty = true;
                    return true;
                }
            }
        }

        return false;
    }
});
