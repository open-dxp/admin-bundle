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

opendxp.registerNS("opendxp.asset.metadata.editor");
/**
 * @private
 */
opendxp.asset.metadata.editor = Class.create({

    initialize: function(asset) {
        this.asset = asset;

        var dataProvider = new opendxp.asset.metadata.dataProvider();

        var eventData = {
            dataProvider: dataProvider,
            asset: asset,
            instance: null
        };

        const preCreateAssetMetadataEditor = new CustomEvent(opendxp.events.preCreateAssetMetadataEditor, {
            detail: {
                editor: this,
                eventParams: eventData
            }
        });

        document.dispatchEvent(preCreateAssetMetadataEditor);

        this.editorInstance = eventData.instance;

        if (!this.editorInstance) {
            // if no panel has been defined by event handler then use the standard grid
            this.editorInstance = new opendxp.asset.metadata.grid({
                asset: this.asset,
                dataProvider: eventData.dataProvider
            });
        }
    },

    getLayout: function() {
        return this.editorInstance.getLayout();
    },

    getValues: function() {
        var values = this.editorInstance.getValues();
        return values;
    }
});
