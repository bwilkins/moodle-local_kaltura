/**
 * Kaltura Player JavaScript functions for Kaltura Plugins for Moodle 2
 * @source: http://github.com/bwilkins/local-kaltura
 *
 * @licstart
 * Copyright (C) 2011 Catalyst IT Ltd (http://catalyst.net.nz)
 *
 * The JavaScript code in this page is free software: you can
 * redistribute it and/or modify it under the terms of the GNU Affero
 * General Public License (GNU Affero GPL) as published by the Free Software
 * Foundation, either version 3 of the License, or (at your option)
 * any later version.  The code is distributed WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE.  See the GNU Affero GPL for more details.
 *
 * As additional permission under GNU Affero GPL version 3 section 7, you
 * may distribute non-source (e.g., minimized or compacted) forms of
 * that code without the copy of the GNU Affero GPL normally required by
 * section 4, provided you include this license notice and a URL
 * through which recipients can access the Corresponding Source.
 * @licend
 */

function initialisevideo(obj) {
    YUI().use("swf","node","io","json-parse","event", function(Y) {
        var player = Y.one(obj.playerselector);
        if (player == undefined) {
            return false;
        }
        if (player.hasChildNodes()) {
            player.one('*').remove(true);
        }

        var datastr = '';
        datastr += 'actions[0]=playerurl';
        if (obj.entryid != undefined && obj.entryid != '') {
            datastr += '&params[0][entryid]='+obj.entryid;
        }
        else if (window.kaltura.entryid != 0 && window.kaltura.entryid != undefined) {
            datastr += '&params[0][entryid]='+window.kaltura.entryid;
        }
        else if (window.kaltura.cmid != 0 && window.kaltura.cmid != undefined) {
            datastr += '&params[0][id]='+window.kaltura.cmid;
        }
        else {
            return false;
        }

        Y.io(M.cfg.wwwroot+'/local/kaltura/ajax.php',
            {
                data: datastr,
                on: {
                    complete: function(i, o, a) {
                        var data = Y.JSON.parse(o.responseText);
                        var kaltura_player = new Y.SWF(obj.playerselector, data[0].url,
                            {
                                fixedAttributes: {
                                    wmode: "opaque",
                                    allowScriptAccess: "always",
                                    allowFullScreen: true,
                                    allowNetworking: "all"
                                },
                                flashVars: {
                                    externalInterfaceDisabled: 0
                                }
                            }
                        );
                    }
                }
            }
        );
    });
}

YUI.use('node','event', function(Y) {
    Y.on("domready",function() { initialisevideo({playerselector:'.kalturaPlayer'}); });
});
