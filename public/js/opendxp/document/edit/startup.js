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

/**
 * @private
 */

// debug
if (!console) {
    if (!parent.console) {
        var console = {
            log: function (v) {
            }
        };
    }
    else {
        console = parent.console;
    }
}

// some globals
/**
 * @private
 * @internal
 */
var editWindow;

/**
 * @private
 * @internal
 */
var editableManager = new opendxp.document.editables.manager();

/**
 * @private
 * @internal
 */
var dndManager;


if (typeof opendxp == "object") {
    opendxp.registerNS("opendxp.globalmanager");
    opendxp.registerNS("opendxp.helpers");
    opendxp.registerNS("opendxp.treenodelocator");
    opendxp.registerNS("opendxp.events");

    opendxp.globalmanager = parent.opendxp.globalmanager;
    opendxp.helpers = parent.opendxp.helpers;
    opendxp.settings = parent.opendxp.settings;
    opendxp.treenodelocator = parent.opendxp.treenodelocator;
    opendxp.events = parent.opendxp.events;
}

if (opendxp_document_id) {
    editWindow = opendxp.globalmanager.get("document_" + opendxp_document_id).edit;
    editWindow.reloadInProgress = false;
    editWindow.frame = window;

    window.onbeforeunload = editWindow.iframeOnbeforeunload.bind(editWindow);
}

// overwrite default z-index of windows
Ext.WindowManager.zseed = 10020;


Ext.Ajax.setDisableCaching(true);
Ext.Ajax.setTimeout(900000);
Ext.Ajax.setMethod("GET");
Ext.Ajax.setDefaultHeaders({
    'X-opendxp-csrf-token': parent.opendxp.settings["csrfToken"]
});

Ext.Loader.setConfig({
    enabled: true
});

Ext.Loader.setPath('Ext.ux', '/bundles/opendxpadmin/extjs/ext-ux/src/classic/src');

Ext.require([
    'Ext.dom.Element',
    'Ext.ux.form.MultiSelect'
]);

Ext.override(Ext.tip.ToolTip, {
    showDelay: 1700,
    hideDelay: 0,
    trackMouse: false,
});

Ext.onReady(function () {
    var body = Ext.getBody();

    // causes styling issues, we don't need this anyway
    body.removeCls("x-body");

    try {
        // init cross frame drag & drop handler
        dndManager = new opendxp.document.edit.dnd(parent.Ext, Ext.getBody(),
            parent.Ext.get('document_iframe_' + window.editWindow.document.id));
    } catch (e) {
        console.log(e);
    }

    body.on("click", function () {
        parent.Ext.menu.MenuMgr.hideAll();
        editWindow.toggleTagHighlighting(false);
    });

    Ext.QuickTips.init();
    Ext.MessageBox.minPromptWidth = 500;

    if (typeof Ext == "object" && typeof opendxp == "object") {

        // check for duplicate editables
        var editableHtmlEls = {};
        document.querySelectorAll('.opendxp_editable').forEach(editableEl => {
            if(editableHtmlEls[editableEl.id] && editableEl.dataset.name) {
                let message = "Duplicate editable name: " + editableEl.dataset.name;
                opendxp.helpers.showNotification("ERROR", message, "error");
                throw message;
            }
            editableHtmlEls[editableEl.id] = true;
        });

        // initialize editables
        editableDefinitions.forEach(editableDef => {
            editableManager.addByDefinition(editableDef);
        });

        if (editWindow.lastScrollposition) {
            if(typeof editWindow.lastScrollposition === 'string') {
                var scrollToEl = document.querySelector(editWindow.lastScrollposition);
                if(scrollToEl) {
                    scrollToEl.scrollIntoView();
                }
            } else if (editWindow.lastScrollposition.top > 100) {
                window.scrollTo(editWindow.lastScrollposition.left, editWindow.lastScrollposition.top);
            }
            editWindow.lastScrollposition = null;
        }

        editableManager.setInitialized(true);

        // add lazyload styles
        // this is necessary, because otherwise ext will overwrite many default styles (reset.css)
        // and then the style detection of eg. input, textarea editable isn't accurate anymore
        Ext.each(Ext.query("link[type='opendxp-lazyload-style']"), function (item) {
            item.setAttribute("type", "text/css");
            item.setAttribute("rel", "stylesheet");
        });

        // register the global key bindings
        opendxp.helpers.registerKeyBindings(document, Ext);


        // add contextmenu note in help tool-tips
        var editablesForTooltip = Ext.query(".opendxp_editable");
        for (var e=0; e<editablesForTooltip.length; e++) {
            let tmpEl = Ext.get(editablesForTooltip[e]);

            if (!tmpEl) {
                continue;
            }

            if (tmpEl.hasCls("opendxp_editable_inc")
                || tmpEl.hasCls("opendxp_editable_href")
                || tmpEl.hasCls("opendxp_editable_image")
                || tmpEl.hasCls("opendxp_editable_renderlet")
                || tmpEl.hasCls("opendxp_editable_snippet")
            ) {
                new Ext.ToolTip({
                    target: tmpEl,
                    hideDelay: 0,
                    trackMouse: false,
                    html: t("click_right_for_more_options")
                });
            }
        }

        // add contextmenu menu to elements included by $this->inc();
        var incElements = Ext.query(".opendxp_editable_inc");
        var tmpIncEl;
        for (var q=0; q<incElements.length; q++) {
            tmpIncEl = Ext.get(incElements[q]);
            if(tmpIncEl) {
                if(tmpIncEl.getAttribute("opendxp_id") && tmpIncEl.getAttribute("opendxp_type")) {
                    tmpIncEl.on("contextmenu", function (e) {

                        var menu = new Ext.menu.Menu();
                        menu.add(new Ext.menu.Item({
                            text: t('open'),
                            iconCls: "opendxp_icon_open",
                            handler: function (item) {
                                item.parentMenu.destroy();
                                opendxp.helpers.openDocument(this.getAttribute("opendxp_id"),
                                    this.getAttribute("opendxp_type"));
                            }.bind(this)
                        }));

                        menu.showAt(e.getXY());

                        e.stopEvent();
                    });
                }
            }
        }

    }

    // put a mask over all iframe, because they would break the dnd functionality
    editWindow.maskFrames();

    // enable the edit tab again
    if (typeof editWindow.loadMask != 'undefined') {
        editWindow.loadMask.hide();
    }
});
