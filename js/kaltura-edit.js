/**
 * Kaltura editing JavaScript functions for Kaltura Plugins for Moodle 2
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

YUI().use('node', 'io', 'json-parse', 'event', function (Y) {
    replaceVideoButton('input#id_replacevideo');

    Y.on("domready", function () {
        obj = {};
        obj.playerselector = '.kalturaPlayerEdit';
        obj.entryid = Y.one('input[name=kalturavideo]').get('value');
        initialisevideo(obj);
    });
});

function replaceButton(buttonselector, overlayclass, datastr) {
    YUI().use('node', function (Y) {
        var replace_button = Y.one(buttonselector);
        if (replace_button) {
            replace_button.on('click',function (e) {
                e.preventDefault();
                if (window.kalturaWiz == undefined) {
                    window.kalturaWiz = contribWiz();
                }
                return false;
            });
        }
    });
}

function replaceVideoButton(buttonselector) {
    YUI().use('node', function (Y) {
        replaceButton(buttonselector, 'ContributionWizard', 'action=cwurl');
    });
}

function addEntryComplete(entry) {
    window.kalturaWiz.selectedEntry({entryid: entry.entryId});
}

(function (a, b) {
    var document  = window.document,
        navigator = window.navigator,
        location  = window.location;

    YUI().use('node', 'io', 'event', 'json', 'overlay', 'tabview', 'swf', 'yui2-treeview', 'yui2-progressbar', function (Y) {
        var contribWiz = (function () {
            /* Define a few things... */

            var contribWiz = function () {
                return contribWiz.fn.init();
            },
            /* Nice shot var for holding the ajax url variable */
            _ajaxurl = M.cfg.wwwroot + '/local/kaltura/ajax.php',
            yui = YUI(),
            scaffold = '',

            load_scaffold = function (self) {
                Y.io(_ajaxurl,
                    {
                        data: 'actions[0]=getdomnodes',
                        on: {
                            success: function (i, o, a) {
                                scaffold = Y.JSON.parse(o.responseText)[0];
                            },
                            failure: function (i, o, a) {
                                setTimeout(function () {self(self);}, 1000);
                            }
                        }
                    }
                );
            };

            //YUI 3.3.0 has something similar
            Y.mix(Y.Node.prototype, {
                hide: function() {
                    if (!(this.hasClass('hidden'))) {
                        this.addClass('hidden');
                    }
                },
                show: function() {
                    if (this.hasClass('hidden')) {
                        this.removeClass('hidden');
                    }
                },
            });

            load_scaffold(load_scaffold);

            contribWiz.fn = contribWiz.prototype = {
                constructor: contribWiz,
                init: function () {
                    this.interfaceNodes = scaffold;

                    if (scaffold === '') {
                        setTimeout(function () {window.kalturaWiz._buildRootInterface()}, 1000);
                    }
                    else {
                        this._buildRootInterface();
                    }

                    return this;

                },
                _buildRootInterface: function () {
                    if (window.kalturaWiz !== undefined) {
                        $this = window.kalturaWiz;
                    }
                    else {
                        $this = this;
                    }

                    if (!$this.rootRendered) {
                        /* Ugly in-line html... but it's a necessary evil */
                        var node =   '<div id="overlayContainer">'
                                        +'<div id="kalturahtmlcontrib" class="contentArea"></div>'
                                        +'<input type="submit" id="contribClose"/>'
                                        +'<div class="flashOverlay" id="videooverlay">'
                                            +'<div id="uploadvideo"></div>'
                                        +'</div>'
                                        +'<div class="flashOverlay" id="audiooverlay">'
                                            +'<div id="uploadaudio"></div>'
                                        +'</div>'
                                        +'<img id="kalLoadingImg" src="' + M.cfg.wwwroot + '/local/kaltura/images/ajax-loader.gif" class="loadingicon" alt="Loading..."/>'
                                    +'</div>';
                        Y.one(document.body).append(node);
                        $this.domnode = Y.one('#overlayContainer');

                        Y.one('#videooverlay').hide();
                        Y.one('#audiooverlay').hide();

                        $this.renderables         = {};
                        $this.renderables.overlay = new Y.Overlay({
                            srcNode:'#overlayContainer',
                            centered: true
                        });
                        $this.renderables.overlay.render();

                        Y.one(document.body).removeClass('yui3-skin-sam');
                        Y.one('#contribClose').on('click', function (e) {
                            e.preventDefault();
                            $this._destroyInterface();
                            return false;
                        });
                        $this.rootRendered = true;
                    }


                    if (scaffold == '' || scaffold == undefined) {
                        setTimeout($this._buildRootInterface, 1000);
                    } else {
                        $this.interfaceNodes = scaffold;

                        try {
                            /* Remove loading image */
                            Y.one('#kalLoadingImg').remove();
                        }
                        catch (err) {}
                        setTimeout($this._buildSelectionInterface, 200);
                    }
                },
                _buildSelectionInterface: function () {
                    /* Assign this to another value so it is still valid when defining another object */
                    var $this = window.kalturaWiz;

                    try {
                        if ($this.currentnode) {
                            $this.currentnode.remove(true);
                        }
                    }
                    catch (err) {};

                    /* Fetch and insert dom tree */

                    var node = Y.Node.create($this.interfaceNodes.select);
                    Y.one('#kalturahtmlcontrib').append(node);
                    $this.currentnode = Y.one('#selectionInterface');

                    /* YUIfy DOM sections */
                    $this.renderables.toptabs     = new Y.TabView({srcNode:'#selectionInterface'});
                    $this.renderables.videotabs   = new Y.TabView({srcNode:'#videotabview'});
                    $this.renderables.audiotabs   = new Y.TabView({srcNode:'#audiotabview'});

                    /* Render YUI parts */
                    $this.renderables.toptabs.render();
                    $this.renderables.videotabs.render();
                    $this.renderables.audiotabs.render();

                    /* Show upload divs for setting position */
                    Y.one('#videooverlay').show();
                    Y.one('#audiooverlay').show();

                    /* attempt to move flash button to the right place.. */
                    var d = Y.one("#uploadvideobutton");
                    var offset = d.getXY();
                    Y.all('.flashOverlay').setStyles({position: 'absolute', float: 'left'});
                    Y.one('#videooverlay').setXY(offset);
                    Y.one('#audiooverlay').setXY(offset);

                    /* The video tab is the first to show up, with upload showing, so lets hide audio upload */
                    Y.one('#audiooverlay').hide();

                    /* We're overlaying these flash objects, so let's switch them when events occur. */
                    $this.renderables.toptabs.on('selectionChange', function(e) {
                        /* location.href will occasionally contain a pesky # on the end already */
                        var href = location.origin + location.pathname + location.search;
                        var tab = e.newVal._parentNode.one('[tabindex=0]');
                        if (tab.get('href') != href + '#videotab') {
                            Y.one('#videooverlay').hide();

                            var subtab = Y.one('#audiotab .yui3-tab-selected a');
                            if (subtab.get('href') != href + '#uploadaudiotab') {
                                Y.one('#audiooverlay').hide();
                            }
                            else {
                                Y.one('#audiooverlay').show();
                            }
                        }
                        else if (tab.get('href') != href + '#audiotab') {
                            Y.one('#audiooverlay').hide();

                            var subtab = Y.one('#videotab .yui3-tab-selected a');
                            if (subtab.get('href') != href + '#uploadvideotab') {
                                Y.one('#videooverlay').hide();
                            }
                            else {
                                Y.one('#videooverlay').show();
                            }
                        }
                    });
                    $this.renderables.videotabs.on('selectionChange', function(e) {
                        Y.one('#audiooverlay').hide();

                        /* location.href will occasionally contain a pesky # on the end already */
                        var href = location.origin + location.pathname + location.search;

                        var tab = e.newVal._parentNode.one('[tabindex=0]');
                        if (tab.get('href') != href + '#uploadvideotab') {
                            Y.one('#videooverlay').hide();
                        }
                        else {
                            Y.one('#videooverlay').show();
                        }
                    });
                    $this.renderables.audiotabs.on('selectionChange', function(e) {
                        Y.one('#videooverlay').hide();

                        /* location.href will occasionally contain a pesky # on the end already */
                        var href = location.origin + location.pathname + location.search;

                        var tab = e.newVal._parentNode.one('[tabindex=0]');
                        if (tab.get('href') != href + '#uploadaudiotab') {
                            Y.one('#audiooverlay').hide();
                        }
                        else {
                            Y.one('#audiooverlay').show();
                        }
                    });

                    var pages = Array(
                        {
                            target: '#sharedaudio',
                            type  : 'audio',
                            access: 'public'
                        },
                        {
                            target: '#myaudio',
                            type  : 'audio',
                            access: 'private'
                        },
                        {
                            target: '#sharedvideo',
                            type  : 'video',
                            access: 'public'
                        },
                        {
                            target: '#myvideo',
                            type  : 'video',
                            access: 'private'
                        }
                    );
                    for (var i = 0; i < pages.length; i++) {
                        var ob = pages[i];
                        try {
                            var page      = window.kalturaWiz.interfaceNodes.selectdata[ob.type + 'list' + ob.access].page.current,
                                pagecount = window.kalturaWiz.interfaceNodes.selectdata[ob.type + 'list' + ob.access].page.count;
                        }
                        catch (err) {
                            var page = 1,
                                pagecount = 1;
                        }

                        $this.pageButtonHandlers({
                            action   : 'list' + ob.access,
                            target   : ob.target,
                            type     : ob.type,
                            page     : page,
                            pagecount: pagecount
                        });
                    }


                    /* Load dynamic contents */
                    /* Load video upload button */
                    $this._uploadUrlCallback({
                        passthrough: {
                            target: '#uploadvideo',
                            delegate: 'window.kalturaWiz.videoUploadDelegate'
                        },
                        response: $this.interfaceNodes.selectdata.videouploadurl
                    });
                    /* Load audio upload button */
                    $this._uploadUrlCallback({
                        passthrough: {
                            target: '#uploadaudio',
                            delegate: 'window.kalturaWiz.audioUploadDelegate'
                        },
                        response: $this.interfaceNodes.selectdata.audiouploadurl
                    });
                    /* Load webcam recorder */
                    $this._swfLoadCallback({
                        passthrough: {
                            target: '#webcamtab'
                        },
                        response: $this.interfaceNodes.selectdata.videourl
                    });
                    /* Load mic recorder */
                    $this._swfLoadCallback({
                        passthrough: {
                            target: '#mictab'
                        },
                        response: $this.interfaceNodes.selectdata.audiourl
                    });
                },
                _buildEditInterface: function () {
                    var $this = window.kalturaWiz;

                    $this.currentnode.addClass('hidden');
                    $this.previousnode = this.currentnode;
                    var node = $this.interfaceNodes.edit;
                    Y.one('#kalturahtmlcontrib').append(node);

                    if ($this.upload) {
                        var P = YUI().use('yui2-progressbar');
                        $this.progressbar = new Y.YUI2.widget.ProgressBar({
                            minValue:0,
                            maxValue:100,
                            height:"30px",
                            width:"600px"
                        }).render('editprogressdiv');
                        if ($this.progressvalue) {
                            $this.progressbar.set('value', this.progressvalue);
                        }
                    }

                    this.currentnode = Y.one('#editInterface');

                    if ((!$this.upload) || ($this.upload && $this.entryid)) {
                        this.multiJAX([
                            {
                                action: 'geteditdata',
                                passthrough: {
                                    entryid: $this.entryid,
                                    upload : $this.upload
                                },
                                params: {
                                    entryid: $this.entryid,
                                    upload : $this.upload
                                },
                                successCallback: $this._populateEditCallback
                            }
                        ]);
                    }

                    /*
                     * Apparently YUI2 mangles the original data almost as bad as the DOM nodes,
                     * so make a copy of the data. Without doing this, loading the edit interface,
                     * closing then loading again results in depth information not being kept.
                     */
                    var treedata = Y.clone(this.interfaceNodes.editdata.categorylist);
                    /* Create treeview with YUI2 */
                    $this.tree = new Y.YUI2.widget.TreeView('editcategoriestreeview', treedata);
                    $this.tree.subscribe('clickEvent', function (e) {
                        var textbox         = Y.one('#editcategoriestext'),
                            idlist          = Y.one('#editcategoriesids'),
                            categoriestext  = textbox.get('value'),
                            categoriesids   = idlist.get('value'),
                            sep             = '';

                        if (categoriestext != '') {
                            sep = ', ';
                        }
                        if (categoriesids != '') {
                            sep = ',';
                            if (categoriesids.indexOf(e.node.data.catId) > -1) {
                                return;
                            }
                        }
                        categoriestext += sep + e.node.data.catFullName;
                        categoriesids  += sep + e.node.data.catId;
                        textbox.set('value', categoriestext);
                        idlist.set('value', categoriesids);
                    });
                    $this.tree.render();

                    Y.one('#editupdate').on('click', function (e) {
                        var id, action, mediatype, callback;

                        if ($this.upload) {
                            id = $this.uploadtoken;
                            action = 'addentry';
                            mediatype = $this.uploadtype;
                            callback = $this.addEntryComplete;
                        }
                        else {
                            id = $this.entryid;
                            action = 'updateentry';
                            mediatype = $this.mediatype;
                            callback = $this._updatedVideo;
                        }

                        var title       = Y.one('#edittitle').get('value'),
                            description = Y.one('#editdescription').get('value'),
                            tags        = Y.one('#edittags').get('value'),
                            categories  = Y.one('#editcategoriesids').get('value');

                        $this.multiJAX([{
                            action: action,
                            params: {
                                token: id,
                                entrydata: encodeURI(Y.JSON.stringify({
                                    title: title,
                                    description: description,
                                    tags: tags,
                                    categories: categories
                                }))
                            },
                            passthrough: {
                                id: id
                            },
                            successCallback: callback
                        }]);
                        $this.domnode.hide();
                    });
                },
                _destroyInterface: function () {
                    $this = window.kalturaWiz;
                    $this.rootRendered = false;
                    $this.domnode.setStyles({display: 'none'});
                    $this.domnode.remove(true);
                    delete(window.kalturaWiz);
                },
                _swfLoadCallback: function (ob) {
                    fixedAttributes = {
                        wmode: ob.response.wmode,
                        allowScriptAccess:"always",
                        allowNetworking:"all",
                        allowFullScreen: "TRUE"
                    };
                    if (ob.response.base) {
                        fixedAttributes.base = ob.response.base;
                    }
                    if (!this.swf) {
                        this.swf = {};
                    }
                    var name = ob.passthrough.target.split('#').join('');
                    this.swf[name] = new Y.SWF(ob.passthrough.target, ob.response.url,
                        {
                            version: "9.0.124",
                            fixedAttributes: fixedAttributes,
                            flashVars: ob.response.params
                        }
                    );
                },
                _uploadUrlCallback: function (ob) {
                    ob.response.params.jsDelegate = ob.passthrough.delegate;
                    this._swfLoadCallback(ob);
                },
                _populateEditCallback: function (ob) {
                    Y.one('#editentryid').set('value', ob.response.entry.id);
                    Y.one('#edittitle').set('value', ob.response.entry.name);
                    Y.one('#editdescription').set('value', ob.response.entry.description);
                    if (Y.one('#contribkalturathumb').get('src') == M.cfg.wwwroot + '/local/kaltura/images/ajax-loader.gif') {
                        Y.one('#contribkalturathumb').set('src', ob.response.entry.thumbnailUrl);
                    }
                    if (ob.response.entry.categoriesIds != undefined) {
                        Y.one('#editcategoriesids').set('value', ob.response.entry.categoriesIds);
                    }
                    if (ob.response.entry.categories != undefined) {
                        Y.one('#editcategoriestext').set('value', ob.response.entry.categories);
                    }
                    if (ob.response.entry.tags != '') {
                        Y.one('#edittags').set('value', ob.response.entry.tags);
                    }
                    if (!ob.passthrough.upload) {
                        Y.one('#edittitle').set('disabled', 1);
                    }
                    if (ob.response.entry.description) {
                        Y.one('#editdescription').set('disabled', 1);
                    }
                    Y.one('#editupdate').set('disabled', false);
                },
                _mediaListCallback: function (ob) {
                    var $this = window.kalturaWiz;
                    if (ob.response) {
                        Y.one(ob.passthrough.target+' .controls').setContent('');
                        Y.one(ob.passthrough.target+' .'+ob.passthrough.type+'container').setContent('');
                    }

                    var node = Y.Node.create('<a href="#" class="pageb">&lt;</a>Page ' + ob.response.page.current + '<a href="#" class="pagef">&gt;</a>');
                    Y.one(ob.passthrough.target+' .controls').appendChild(node);

                    for (var i = 0; i < ob.response.count; i++) {
                        var n = ob.response.objects[i];
                        if (n) {
                            Y.one(ob.passthrough.target + ' .' + ob.passthrough.type + 'container').appendChild(
                                Y.Node.create(
                                    '<span class="thumb">'
                                        +'<a href="#" onClick="contribWiz.fn.selectedEntry({entryId: \'' + n.id + '\', mediatype: \'' + ob.passthrough.type + '\', upload: false});return false;" class="kalturavideo" id="' + n.id + '">'
                                            + (
                                                ob.passthrough.type === 'audio' ?
                                                    '<span><div class="kalthumb">' + n.name + '</div></span>' :
                                                    '<img src="' + n.thumbnailUrl + '" type="image/jpeg" class="kalthumb" alt="' + n.name + '" title="' + n.name + '"/>'
                                                )
                                        +'</a>'
                                    +'</span>'
                                )
                            );
                        }
                    }

                    $this.pageButtonHandlers({
                        action   : ob.passthrough.action,
                        target   : ob.passthrough.target,
                        type     : ob.passthrough.type,
                        page     : ob.response.page.current,
                        pagecount: ob.response.page.count
                    });
                },
                pageButtonHandlers: function (ob) {
                    var $this   = this,
                    back        = Y.one(ob.target+' .pageb'),
                    forward     = Y.one(ob.target+' .pagef');

                    if (ob.page <= 1) {
                        back.setAttribute('disabled', true);
                    }
                    else {
                        back.setAttribute('disabled', false);
                        back.on(
                            {
                                click: function (e) {
                                    e.preventDefault();

                                    $this.multiJAX([{
                                        action: ob.action,
                                        passthrough: {
                                            target: ob.target,
                                            action: ob.action,
                                            type:   ob.type,
                                            page:   ob.page-1
                                        },
                                        params: {
                                            mediatype: ob.type,
                                            page: ob.page-1
                                        },
                                        successCallback: window.kalturaWiz._mediaListCallback
                                    }]);

                                    return false;
                                }
                            }
                        );
                    }

                    if (ob.page >= ob.pagecount) {
                        forward.setAttribute('disabled', true);
                    }
                    else {
                        forward.setAttribute('disabled', false);
                        forward.on(
                            {
                                click: function (e) {
                                    e.preventDefault();

                                    $this.multiJAX([{
                                        action: ob.action,
                                        passthrough: {
                                            target: ob.target,
                                            action: ob.action,
                                            type:   ob.type,
                                            page:   ob.page-1
                                        },
                                        params: {
                                            mediatype: ob.type,
                                            page: ob.page+1
                                        },
                                        successCallback: window.kalturaWiz._mediaListCallback
                                    }]);

                                    return false;
                                }
                            }
                        );
                    }
                },
                multiJAX: function (conf) {
                    var str = '';
                    var callbacks = Array();
                    var passthroughs = Array();
                    for (var i = 0; i < conf.length; i++) {
                        var c = conf[i];
                        var actionstr = 'actions[' + i + ']=' + c.action;
                        var paramstr_head  = 'params[' + i + ']';
                        var paramstr = '';
                        if (c.params) {
                            for (var p in c.params) {
                                var paramstr = paramstr + paramstr_head + '[' + p + ']=' + c.params[p] + '&';
                            }
                        }
                        str += actionstr + '&' + paramstr; /* paramstr should already end with a & */
                        callbacks[i] = {
                            success : c.successCallback,
                            failure : (c.failureCallback ? c.failureCallback : function () {})
                        };
                        if (c.passthrough) {
                            passthroughs[i] = c.passthrough;
                        }
                        else {
                            passthroughs[i] = {};
                        }
                    }
                    str = str.replace(/&$/,'');

                    Y.io(_ajaxurl,
                        {
                            data: str,
                            on: {
                                success: function (i, o, a) {
                                    response = Y.JSON.parse(o.responseText);
                                    for (var j = 0; j < response.length; j++) {
                                        callbacks[j].success({
                                            response: response[j],
                                            passthrough: passthroughs[j]
                                        });
                                    }
                                },
                                failure: function (i, o, a) {
                                    response = Y.JSON.parse(o.responseText);
                                    for (var j = 0; j < response.length; j++) {
                                        callbacks[j].failure({
                                            response: response[j],
                                            passthrough: passthroughs[j]
                                        });
                                    }
                                }
                            }
                        }
                    );
                },
                _updatedVideo: function (ob) {
                    if (ob.response.entry) {
                        window.kalturaWiz._useEntry(ob.response.entry.id);
                    }
                    else {
                        window.kalturaWiz._useEntry(ob.passthrough.id);
                    }
                },
                _useEntry: function (id) {
                    Y.one('input[name=kalturavideo]').set('value', id);
                    initialisevideo({playerselector: '.kalturaPlayerEdit', entryid: id});
                    window.kalturaWiz._destroyInterface();
                },
                _addEntryComplete: function (ob) {
                    $this = window.kalturaWiz;
                    $this.addEntryComplete(ob.response.entry.id);
                },
                selectedEntry: function (ob) {
                    $this = window.kalturaWiz;
                    $this.entryid = ob.entryId;
                    $this.mediatype = ob.mediatype;
                    $this.upload  = ob.upload;

                    $this._buildEditInterface();
                },
                audioUploadDelegate: {
                    singleUploadCompleteHandler: function (args) {
                        $this = window.kalturaWiz;
                        $this.uploadtoken = args[0].token;
                        $this.uploadtype  = 'audio';
                        Y.one('#editupdate').set('disabled', false);
                    },
                    selectHandler: function (){
                        var $this = window.kalturaWiz;
                        $this.upload = true;
                        $this.swf['uploadvideo'].callSWF('upload');
                        $this._buildEditInterface();
                    },
                    progressHandler: function (args) {
                        var $this = window.kalturaWiz;
                        if (!$this.progressvalue) {
                            $this.progressvalue = 0;
                        }
                        if ($this.progressbar) {
                            var progvalue = args[0]/args[1]*100;
                            if (progvalue > $this.progressvalue) {
                                $this.progressvalue = progvalue;
                                $this.progressbar.set('value', progvalue);
                            }
                        }
                    }
                },
                videoUploadDelegate: {
                    singleUploadCompleteHandler: function (args) {
                        $this = window.kalturaWiz;
                        $this.uploadtoken = args[0].token;
                        $this.uploadtype  = 'video';
                        Y.one('#editupdate').set('disabled', false);
                    },
                    selectHandler: function (){
                        var $this = window.kalturaWiz;
                        $this.upload = true;
                        $this.swf['uploadvideo'].callSWF('upload');
                        $this._buildEditInterface();
                    },
                    progressHandler: function (args) {
                        var $this = window.kalturaWiz;
                        if (!$this.progressvalue) {
                            $this.progressvalue = 0;
                        }
                        if ($this.progressbar) {
                            var progvalue = args[0]/args[1]*100;
                            if (progvalue > $this.progressvalue) {
                                $this.progressvalue = progvalue;
                                $this.progressbar.set('value', progvalue);
                            }
                        }
                    }
                },
                destroy: function () {
                    window.kalturaWiz._destroyInterface();
                }
            };
            contribWiz.fn.init.prototype = contribWiz.prototype;
            return contribWiz;
        })();
        a.contribWiz = contribWiz;
    });
})(window);

