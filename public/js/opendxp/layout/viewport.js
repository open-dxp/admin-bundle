/**
 * OpenDXP
 *
 * This source file is licensed under the GNU General Public License version 3 (GPLv3).
 *
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

/**
 * Generic extension point for plugins/bundles that need a permanent region docked to the
 * main application viewport (border layout: north / south / east / west), next to the
 * center region ("opendxp_body") that hosts the tree panels and the main tab panel.
 *
 * This namespace intentionally has no knowledge of any specific plugin - it only manages
 * the viewport's border-layout regions on their behalf.
 */
opendxp.registerNS("opendxp.layout.viewport");
opendxp.layout.viewport = {

    /**
     * @private
     */
    regions: {},

    addRegion: function (config) {
        var viewport = Ext.getCmp("opendxp_viewport");

        if (!viewport) {
            throw new Error("opendxp.layout.viewport.addRegion: viewport is not ready yet");
        }

        if (!config || !config.id) {
            throw new Error("opendxp.layout.viewport.addRegion: config.id is required");
        }

        if (!config.region || config.region === "center") {
            throw new Error("opendxp.layout.viewport.addRegion: config.region must be one of north, south, east, west (\"center\" is reserved for opendxp_body)");
        }

        if (this.regions[config.id]) {
            return this.regions[config.id];
        }

        var region = viewport.add(config);
        this.regions[config.id] = region;

        region.on("destroy", function () {
            delete this.regions[config.id];
        }.bind(this));

        return region;
    },

    removeRegion: function (id, destroy) {
        var viewport = Ext.getCmp("opendxp_viewport");
        var region = this.regions[id];

        if (!viewport || !region) {
            return;
        }

        viewport.remove(region, !!destroy);
        delete this.regions[id];
    },

    getRegion: function (id) {
        return this.regions[id] || null;
    }
};
