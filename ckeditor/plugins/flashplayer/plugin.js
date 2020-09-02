/**************************************
    Webutler V2.1 - www.webutler.de
    Copyright (c) 2008 - 2011
    Autor: Sven Zinke
    Free for any use
    Lizenz: GPL
**************************************/

(function() {
    CKEDITOR.scriptLoader.load(CKEDITOR.plugins.getPath('flashplayer') + 'config.js');
    CKEDITOR.plugins.add('flashplayer', {
        lang: [CKEDITOR.lang.detect(CKEDITOR.config.language)],
        init: function(a) {
            a._.newflashFn = CKEDITOR.tools.addFunction(function(b) {
                var c = CKEDITOR.dialog.getCurrent();
                c.setValueOf('info', 'width', b.newWidth);
                c.setValueOf('info', 'height', b.newHeight);
            }, a);
            a.on('doubleclick', function(b) {
                var c = CKEDITOR.plugins.link.getSelectedLink(a) || b.data.element;
                if (!c.hasClass('flvpopuplink')) return null;
                (b.data.dialog = 'link') === false;
                flvOrgLink = c;
                flvLink = c.data('cke-pa-onclick') || c.getAttribute('onclick');
                flvLinkTxt = c.getText();
                b.data.dialog = 'flash';
            });
            if (a.contextMenu) a.contextMenu.addListener(function(b, c) {
                if (!b.hasClass('flvpopuplink')) return null;
                flvOrgLink = b;
                flvLink = b.data('cke-pa-onclick') || b.getAttribute('onclick');
                flvLinkTxt = b.getText();
                a.contextMenu.removeAll();
                return {
                    cut: a.getCommand('cut').state,
                    copy: a.getCommand('copy').state,
                    paste: CKEDITOR.TRISTATE_OFF,
                    flash: CKEDITOR.TRISTATE_ON
                };
            });
        }
    });
    CKEDITOR.on('dialogDefinition', function(a) {
        var b = a.data.name,
            c = a.data.definition,
            d = a.editor;
        if (b == 'flash') {
            var e = d.lang.player.flv.linksource,
                f = playerConf.color,
                g = c.getContents('info');
            g.add({
                type: 'html',
                id: 'swfsizeframe',
                html: '<iframe src="' + CKEDITOR.plugins.getPath('flashplayer') + 'swfsize.php" id="swfsize" name="swfsize" style="width: 0px; height: 0px;" width="0" height="0" scrolling="no" frameborder="0"></iframe>',
                style: 'display: none;'
            }, 'preview');
            var h = g.get('src');
            h.onChange = function() {
                var z = CKEDITOR.dialog.getCurrent(),
                    A = this.getValue();
                if (A.substring(0, playerConf.mediapath.length) == playerConf.mediapath) {
                    frames.swfsize.location.replace(CKEDITOR.plugins.getPath('flashplayer') + 'swfsize.php?movie=' + A + '&CKEditorFuncNum=' + d._.newflashFn);
                    m('both');
                } else if (k(A, 'flv') != '') {
                    m('flv');
                    if (k(A, 'margin') == '') q();
                } else if (k(A, 'mp3') != '') {
                    m('mp3');
                    if (k(A, 'showloading') == '') u();
                } else m('both');
            };
            var i = g.get('width'),
                j = g.get('height');
            i.onChange = j.onChange = function() {
                var z = CKEDITOR.dialog.getCurrent(),
                    A = z.getValueOf('info', 'src');
                if (k(A, 'flv') != '') {
                    var B = z.getValueOf('info', 'width'),
                        C = z.getValueOf('info', 'height'),
                        D = B + 'x' + C;
                    z.setValueOf('FLVconfig', 'video_160x120', false);
                    z.setValueOf('FLVconfig', 'video_320x240', false);
                    z.setValueOf('FLVconfig', 'video_480x360', false);
                    z.setValueOf('FLVconfig', 'video_640x480', false);
                    z.setValueOf('FLVconfig', 'video_160x90', false);
                    z.setValueOf('FLVconfig', 'video_320x180', false);
                    z.setValueOf('FLVconfig', 'video_480x270', false);
                    z.setValueOf('FLVconfig', 'video_640x360', false);
                    if (z.getContentElement('FLVconfig', 'video_' + D)) z.setValueOf('FLVconfig', 'video_' + D, D);
                } else if (k(A, 'mp3') != '') {
                    if (z.getValueOf('info', 'height') != '20') z.setValueOf('info', 'height', '20');
                    var E = z.getValueOf('info', 'width');
                    if (k(A, 'width') != E) {
                        z.setValueOf('MP3config', 'audio_width200', false);
                        z.setValueOf('MP3config', 'audio_width300', false);
                        z.setValueOf('MP3config', 'audio_width400', false);
                        if (z.getContentElement('MP3config', 'audio_width' + E)) z.setValueOf('MP3config', 'audio_width' + E, E);
                        w();
                    }
                }
            };

            function k(z, A) {
                var B = new RegExp('(?:[?&]|&amp;)' + A + '=([^&]+)', 'i'),
                    C = z.match(B);
                return C && C.length > 1 ? C[1] : '';
            };

            function l(z) {
                if (z.substr(0, 4) == 'amp;') z = z.substring(4, z.length);
                return z;
            };

            function m(z) {
                var A = CKEDITOR.dialog.getCurrent();
                if (z == 'flv') {
                    A.showPage('FLVconfig');
                    A.hidePage('MP3config');
                    A.hidePage('properties');
                    A.setValueOf('properties', 'allowFullScreen', true);
                } else if (z == 'mp3') {
                    A.showPage('MP3config');
                    A.hidePage('FLVconfig');
                    A.hidePage('properties');
                } else if (z == 'both') {
                    A.hidePage('FLVconfig');
                    A.hidePage('MP3config');
                    A.showPage('properties');
                }
            };

            function n() {
                var z = CKEDITOR.dialog.getCurrent();
                if (typeof flvLink != 'undefined') {
                    var A = new RegExp("openFLVPopup\\('([^']+)'([^']+)'([0-9]+)'([^']+)'([0-9]+)'\\)", 'i'),
                        B = flvLink.match(A);
                    z.setValueOf('info', 'width', B[3]);
                    z.setValueOf('info', 'height', B[5]);
                    z.setValueOf('FLVconfig', 'popup', true);
                    if (typeof flvLinkTxt != 'undefined') z.setValueOf('FLVconfig', 'popuptext', flvLinkTxt);
                    z.setValueOf('info', 'src', B[1]);
                }
                var C = z.getValueOf('info', 'src');
                if (C != '' && C.substring(0, playerConf.mediapath.length) != playerConf.mediapath) {
                    if (k(C, 'flv') != '') {
                        if (k(C, 'margin') != '') p(C);
                    } else if (k(C, 'mp3') != '')
                        if (k(C, 'showloading') != '') v(C);
                } else m('both');
            };

            function o() {
                var z = CKEDITOR.dialog.getCurrent();
                if (z.getValueOf('FLVconfig', 'popup') == true) {
                    var A = z.getValueOf('info', 'src'),
                        B = z.getValueOf('info', 'width'),
                        C = z.getValueOf('info', 'height');
                    z.setValueOf('info', 'src', '');
                    var D;
                    if (z.getValueOf('FLVconfig', 'popuptext') != '') D = z.getValueOf('FLVconfig', 'popuptext');
                    else D = e;
                    var E = '<a class="flvpopuplink" data-cke-pa-onclick="openFLVPopup(\'' + A + "', '" + B + "', '" + C + "')\">" + D + '</a>',
                        F = CKEDITOR.dom.element.createFromHtml(E);
                    try {
                        if (typeof flvOrgLink != 'undefined') F.replace(flvOrgLink);
                        else {
                            var G = d.getSelection().getSelectedElement();
                            if (G && G.data('cke-real-element-type') && G.data('cke-real-element-type') == 'flash') F.replace(G);
                            else d.insertHtml(E);
                        }
                    } catch (H) {}
                    z.hide();
                }
            };

            function p(z) {
                if (k(z, 'volume') != '') {
                    var A = CKEDITOR.dialog.getCurrent(),
                        B = A.getValueOf('info', 'width'),
                        C = A.getValueOf('info', 'height'),
                        D = B + 'x' + C;
                    if (A.getContentElement('FLVconfig', 'video_' + D)) A.setValueOf('FLVconfig', 'video_' + D, D);
                    var E = z.split('?'),
                        F = E[1].split('&');
                    for (var G = 0; G < F.length; G++) {
                        F[G] = l(F[G]);
                        var H = F[G].split('=');
                        if (A.getContentElement('FLVconfig', 'video_' + H[0])) {
                            if (H[0] == 'autoplay' || H[0] == 'showiconplay') A.setValueOf('FLVconfig', 'video_' + H[0], H[0]);
                            else A.setValueOf('FLVconfig', 'video_' + H[0], true);
                        } else if (H[0] == 'volume') A.setValueOf('FLVconfig', 'video_' + H[0] + H[1], H[1]);
                    }
                }
            };

            function q() {
                var z = CKEDITOR.dialog.getCurrent();
                z.setValueOf('info', 'width', '160');
                z.setValueOf('info', 'height', '120');
                z.setValueOf('FLVconfig', 'video_160x120', '160x120');
                z.setValueOf('FLVconfig', 'video_autoload', true);
                z.setValueOf('FLVconfig', 'video_showiconplay', 'showiconplay');
                z.setValueOf('FLVconfig', 'video_showstop', true);
                z.setValueOf('FLVconfig', 'video_showvolume', true);
                z.setValueOf('FLVconfig', 'video_volume150', '150');
                r();
            };

            function r() {
                var z = CKEDITOR.dialog.getCurrent(),
                    A = z.getValueOf('info', 'src'),
                    B = playerConf.playerpath + '/flvplayer.swf?flv=' + k(A, 'flv');
                z.setValueOf('info', 'src', B + s());
            };

            function s() {
                var z = CKEDITOR.dialog.getCurrent(),
                    A = '&margin=0&playeralpha=50&iconplaybgalpha=50&showmouse=autohide&loadingcolor=' + f + '&buttonovercolor=' + f + '&sliderovercolor=' + f;
                if (z.getValueOf('FLVconfig', 'video_loop') == true) A += '&loop=1';
                if (z.getValueOf('FLVconfig', 'video_autoload') == true) A += '&autoload=1';
                if (z.getValueOf('FLVconfig', 'video_autoplay') == 'autoplay') A += '&autoplay=1';
                else if (z.getValueOf('FLVconfig', 'video_showiconplay') == 'showiconplay') A += '&showiconplay=1';
                if (z.getValueOf('FLVconfig', 'video_showstop') == true) A += '&showstop=1';
                if (z.getValueOf('FLVconfig', 'video_showvolume') == true) A += '&showvolume=1';
                if (z.getValueOf('FLVconfig', 'video_showtime') == true) A += '&showtime=1';
                if (z.getValueOf('FLVconfig', 'video_showfullscreen') == true) A += '&showfullscreen=1';
                if (z.getValueOf('FLVconfig', 'video_showplayer') == true) A += '&showplayer=never';
                if (z.getValueOf('FLVconfig', 'video_volume1') == '1') A += '&volume=1';
                else if (z.getValueOf('FLVconfig', 'video_volume50') == '50') A += '&volume=50';
                else if (z.getValueOf('FLVconfig', 'video_volume100') == '100') A += '&volume=100';
                else if (z.getValueOf('FLVconfig', 'video_volume150') == '150') A += '&volume=150';
                else if (z.getValueOf('FLVconfig', 'video_volume200') == '200') A += '&volume=200';
                else A += '&volume=150';
                return A;
            };
            var t = function() {
                var z = CKEDITOR.dialog.getCurrent();
                z.setValueOf('FLVconfig', 'video_160x120', false);
                z.setValueOf('FLVconfig', 'video_320x240', false);
                z.setValueOf('FLVconfig', 'video_480x360', false);
                z.setValueOf('FLVconfig', 'video_640x480', false);
                z.setValueOf('FLVconfig', 'video_160x90', false);
                z.setValueOf('FLVconfig', 'video_320x180', false);
                z.setValueOf('FLVconfig', 'video_480x270', false);
                z.setValueOf('FLVconfig', 'video_640x360', false);
                z.setValueOf('FLVconfig', 'video_loop', false);
                z.setValueOf('FLVconfig', 'video_autoload', false);
                z.setValueOf('FLVconfig', 'video_autoplay', false);
                z.setValueOf('FLVconfig', 'video_showiconplay', false);
                z.setValueOf('FLVconfig', 'video_showstop', false);
                z.setValueOf('FLVconfig', 'video_showvolume', false);
                z.setValueOf('FLVconfig', 'video_showtime', false);
                z.setValueOf('FLVconfig', 'video_showfullscreen', false);
                z.setValueOf('FLVconfig', 'video_showplayer', false);
                z.setValueOf('FLVconfig', 'video_volume1', false);
                z.setValueOf('FLVconfig', 'video_volume50', false);
                z.setValueOf('FLVconfig', 'video_volume100', false);
                z.setValueOf('FLVconfig', 'video_volume150', false);
                z.setValueOf('FLVconfig', 'video_volume200', false);
                z.setValueOf('FLVconfig', 'popup', false);
                z.setValueOf('FLVconfig', 'popuptext', '');
                m('both');
                try {
                    if (typeof flvLink != 'undefined') {
                        delete flvOrgLink.flvOrgLink;
                        delete flvLink.flvLink;
                        delete flvLinkTxt.flvLinkTxt;
                    }
                } catch (A) {}
            };
            c.addContents({
                id: 'FLVconfig',
                label: d.lang.player.flv.title,
                elements: [{
                    type: 'hbox',
                    widths: ['40%', '60%'],
                    children: [{
                        type: 'vbox',
                        padding: 3,
                        children: [{
                            type: 'html',
                            html: d.lang.player.flv.settings + ':'
                        }, {
                            type: 'vbox',
                            padding: 3,
                            style: 'padding-left: 25px',
                            children: [{
                                id: 'video_loop',
                                type: 'checkbox',
                                label: ' ' + d.lang.player.flv.loop,
                                'default': '',
                                onClick: function() {
                                    r();
                                }
                            }, {
                                id: 'video_autoload',
                                type: 'checkbox',
                                label: ' ' + d.lang.player.flv.autoload,
                                'default': '',
                                onClick: function() {
                                    r();
                                }
                            }, {
                                id: 'video_autoplay',
                                type: 'radio',
                                label: '',
                                items: [
                                    [' ' + d.lang.player.flv.autoplay, 'autoplay']
                                ],
                                'default': '',
                                onClick: function() {
                                    var z = CKEDITOR.dialog.getCurrent();
                                    if (this.getValue() != null) z.setValueOf('FLVconfig', 'video_showiconplay', false);
                                    r();
                                }
                            }, {
                                id: 'video_showiconplay',
                                type: 'radio',
                                label: '',
                                items: [
                                    [' ' + d.lang.player.flv.iconplay, 'showiconplay']
                                ],
                                'default': '',
                                onClick: function() {
                                    var z = CKEDITOR.dialog.getCurrent();
                                    if (this.getValue() != null) z.setValueOf('FLVconfig', 'video_autoplay', false);
                                    r();
                                }
                            }]
                        }]
                    }, {
                        type: 'vbox',
                        padding: 3,
                        children: [{
                            type: 'html',
                            html: d.lang.player.flv.format
                        }, {
                            type: 'hbox',
                            widths: ['50%', '50%'],
                            children: [{
                                type: 'vbox',
                                padding: 3,
                                style: 'padding-left: 25px',
                                children: [{
                                    id: 'video_160x120',
                                    type: 'radio',
                                    label: '',
                                    items: [
                                        [' 160 x 120', '160x120']
                                    ],
                                    'default': '',
                                    onClick: function() {
                                        var z = CKEDITOR.dialog.getCurrent();
                                        z.setValueOf('FLVconfig', 'video_320x240', false);
                                        z.setValueOf('FLVconfig', 'video_480x360', false);
                                        z.setValueOf('FLVconfig', 'video_640x480', false);
                                        z.setValueOf('FLVconfig', 'video_160x90', false);
                                        z.setValueOf('FLVconfig', 'video_320x180', false);
                                        z.setValueOf('FLVconfig', 'video_480x270', false);
                                        z.setValueOf('FLVconfig', 'video_640x360', false);
                                        z.setValueOf('info', 'width', '160');
                                        z.setValueOf('info', 'height', '120');
                                    }
                                }, {
                                    id: 'video_320x240',
                                    type: 'radio',
                                    label: '',
                                    items: [
                                        [' 320 x 240', '320x240']
                                    ],
                                    'default': '',
                                    onClick: function() {
                                        var z = CKEDITOR.dialog.getCurrent();
                                        z.setValueOf('FLVconfig', 'video_160x120', false);
                                        z.setValueOf('FLVconfig', 'video_480x360', false);
                                        z.setValueOf('FLVconfig', 'video_640x480', false);
                                        z.setValueOf('FLVconfig', 'video_160x90', false);
                                        z.setValueOf('FLVconfig', 'video_320x180', false);
                                        z.setValueOf('FLVconfig', 'video_480x270', false);
                                        z.setValueOf('FLVconfig', 'video_640x360', false);
                                        z.setValueOf('info', 'width', '320');
                                        z.setValueOf('info', 'height', '240');
                                    }
                                }, {
                                    id: 'video_480x360',
                                    type: 'radio',
                                    label: '',
                                    items: [
                                        [' 480 x 360', '480x360']
                                    ],
                                    'default': '',
                                    onClick: function() {
                                        var z = CKEDITOR.dialog.getCurrent();
                                        z.setValueOf('FLVconfig', 'video_160x120', false);
                                        z.setValueOf('FLVconfig', 'video_320x240', false);
                                        z.setValueOf('FLVconfig', 'video_640x480', false);
                                        z.setValueOf('FLVconfig', 'video_160x90', false);
                                        z.setValueOf('FLVconfig', 'video_320x180', false);
                                        z.setValueOf('FLVconfig', 'video_480x270', false);
                                        z.setValueOf('FLVconfig', 'video_640x360', false);
                                        z.setValueOf('info', 'width', '480');
                                        z.setValueOf('info', 'height', '360');
                                    }
                                }, {
                                    id: 'video_640x480',
                                    type: 'radio',
                                    label: '',
                                    items: [
                                        [' 640 x 480', '640x480']
                                    ],
                                    'default': '',
                                    onClick: function() {
                                        var z = CKEDITOR.dialog.getCurrent();
                                        z.setValueOf('FLVconfig', 'video_160x120', false);
                                        z.setValueOf('FLVconfig', 'video_320x240', false);
                                        z.setValueOf('FLVconfig', 'video_480x360', false);
                                        z.setValueOf('FLVconfig', 'video_160x90', false);
                                        z.setValueOf('FLVconfig', 'video_320x180', false);
                                        z.setValueOf('FLVconfig', 'video_480x270', false);
                                        z.setValueOf('FLVconfig', 'video_640x360', false);
                                        z.setValueOf('info', 'width', '640');
                                        z.setValueOf('info', 'height', '480');
                                    }
                                }]
                            }, {
                                type: 'vbox',
                                padding: 3,
                                style: 'padding-left: 15px',
                                children: [{
                                    id: 'video_160x90',
                                    type: 'radio',
                                    label: '',
                                    items: [
                                        [' 160 x 90', '160x90']
                                    ],
                                    'default': '',
                                    onClick: function() {
                                        var z = CKEDITOR.dialog.getCurrent();
                                        z.setValueOf('FLVconfig', 'video_160x120', false);
                                        z.setValueOf('FLVconfig', 'video_320x240', false);
                                        z.setValueOf('FLVconfig', 'video_480x360', false);
                                        z.setValueOf('FLVconfig', 'video_640x480', false);
                                        z.setValueOf('FLVconfig', 'video_320x180', false);
                                        z.setValueOf('FLVconfig', 'video_480x270', false);
                                        z.setValueOf('FLVconfig', 'video_640x360', false);
                                        z.setValueOf('info', 'width', '160');
                                        z.setValueOf('info', 'height', '90');
                                    }
                                }, {
                                    id: 'video_320x180',
                                    type: 'radio',
                                    label: '',
                                    items: [
                                        [' 320 x 180', '320x180']
                                    ],
                                    'default': '',
                                    onClick: function() {
                                        var z = CKEDITOR.dialog.getCurrent();
                                        z.setValueOf('FLVconfig', 'video_160x120', false);
                                        z.setValueOf('FLVconfig', 'video_320x240', false);
                                        z.setValueOf('FLVconfig', 'video_480x360', false);
                                        z.setValueOf('FLVconfig', 'video_640x480', false);
                                        z.setValueOf('FLVconfig', 'video_160x90', false);
                                        z.setValueOf('FLVconfig', 'video_480x270', false);
                                        z.setValueOf('FLVconfig', 'video_640x360', false);
                                        z.setValueOf('info', 'width', '320');
                                        z.setValueOf('info', 'height', '180');
                                    }
                                }, {
                                    id: 'video_480x270',
                                    type: 'radio',
                                    label: '',
                                    items: [
                                        [' 480 x 270', '480x270']
                                    ],
                                    'default': '',
                                    onClick: function() {
                                        var z = CKEDITOR.dialog.getCurrent();
                                        z.setValueOf('FLVconfig', 'video_160x120', false);
                                        z.setValueOf('FLVconfig', 'video_320x240', false);
                                        z.setValueOf('FLVconfig', 'video_480x360', false);
                                        z.setValueOf('FLVconfig', 'video_640x480', false);
                                        z.setValueOf('FLVconfig', 'video_160x90', false);
                                        z.setValueOf('FLVconfig', 'video_320x180', false);
                                        z.setValueOf('FLVconfig', 'video_640x360', false);
                                        z.setValueOf('info', 'width', '480');
                                        z.setValueOf('info', 'height', '270');
                                    }
                                }, {
                                    id: 'video_640x360',
                                    type: 'radio',
                                    label: '',
                                    items: [
                                        [' 640 x 360', '640x360']
                                    ],
                                    'default': '',
                                    onClick: function() {
                                        var z = CKEDITOR.dialog.getCurrent();
                                        z.setValueOf('FLVconfig', 'video_160x120', false);
                                        z.setValueOf('FLVconfig', 'video_320x240', false);
                                        z.setValueOf('FLVconfig', 'video_480x360', false);
                                        z.setValueOf('FLVconfig', 'video_640x480', false);
                                        z.setValueOf('FLVconfig', 'video_160x90', false);
                                        z.setValueOf('FLVconfig', 'video_320x180', false);
                                        z.setValueOf('FLVconfig', 'video_480x270', false);
                                        z.setValueOf('info', 'width', '640');
                                        z.setValueOf('info', 'height', '360');
                                    }
                                }]
                            }]
                        }]
                    }]
                }, {
                    type: 'hbox',
                    widths: ['40%', '60%'],
                    children: [{
                        type: 'vbox',
                        padding: 3,
                        children: [{
                            type: 'html',
                            html: d.lang.player.flv.playerbar + ':'
                        }, {
                            type: 'vbox',
                            padding: 3,
                            style: 'padding-left: 25px',
                            children: [{
                                id: 'video_showstop',
                                type: 'checkbox',
                                label: ' ' + d.lang.player.flv.stop,
                                'default': '',
                                onClick: function() {
                                    var z = CKEDITOR.dialog.getCurrent();
                                    if (z.getValueOf('FLVconfig', 'video_showplayer') == true) this.setValue(false);
                                    r();
                                }
                            }, {
                                id: 'video_showvolume',
                                type: 'checkbox',
                                label: ' ' + d.lang.player.flv.volume,
                                'default': '',
                                onClick: function() {
                                    var z = CKEDITOR.dialog.getCurrent();
                                    if (z.getValueOf('FLVconfig', 'video_showplayer') == true) this.setValue(false);
                                    r();
                                }
                            }, {
                                id: 'video_showtime',
                                type: 'checkbox',
                                label: ' ' + d.lang.player.flv.time,
                                'default': '',
                                onClick: function() {
                                    var z = CKEDITOR.dialog.getCurrent();
                                    if (z.getValueOf('FLVconfig', 'video_showplayer') == true) this.setValue(false);
                                    r();
                                }
                            }, {
                                id: 'video_showfullscreen',
                                type: 'checkbox',
                                label: ' ' + d.lang.player.flv.fullscreen,
                                'default': '',
                                onClick: function() {
                                    var z = CKEDITOR.dialog.getCurrent();
                                    if (z.getValueOf('FLVconfig', 'video_showplayer') == true) this.setValue(false);
                                    r();
                                }
                            }, {
                                id: 'video_showplayer',
                                type: 'checkbox',
                                label: ' ' + d.lang.player.flv.activ,
                                'default': '',
                                onClick: function() {
                                    var z = CKEDITOR.dialog.getCurrent();
                                    if (this.getValue() == true) {
                                        z.setValueOf('FLVconfig', 'video_showstop', false);
                                        z.setValueOf('FLVconfig', 'video_showvolume', false);
                                        z.setValueOf('FLVconfig', 'video_showtime', false);
                                        z.setValueOf('FLVconfig', 'video_showfullscreen', false);
                                    }
                                    r();
                                }
                            }]
                        }]
                    }, {
                        type: 'vbox',
                        padding: 3,
                        children: [{
                            type: 'html',
                            html: d.lang.player.flv.volumestart + ':'
                        }, {
                            type: 'vbox',
                            padding: 3,
                            style: 'padding-left: 25px',
                            children: [{
                                id: 'video_volume1',
                                type: 'radio',
                                label: '',
                                items: [
                                    [' ' + d.lang.player.flv.volumenull, '1']
                                ],
                                'default': '',
                                onClick: function() {
                                    var z = CKEDITOR.dialog.getCurrent();
                                    z.setValueOf('FLVconfig', 'video_volume50', false);
                                    z.setValueOf('FLVconfig', 'video_volume100', false);
                                    z.setValueOf('FLVconfig', 'video_volume150', false);
                                    z.setValueOf('FLVconfig', 'video_volume200', false);
                                    r();
                                }
                            }, {
                                id: 'video_volume50',
                                type: 'radio',
                                label: '',
                                items: [
                                    [' 25%', '50']
                                ],
                                'default': '',
                                onClick: function() {
                                    var z = CKEDITOR.dialog.getCurrent();
                                    z.setValueOf('FLVconfig', 'video_volume1', false);
                                    z.setValueOf('FLVconfig', 'video_volume100', false);
                                    z.setValueOf('FLVconfig', 'video_volume150', false);
                                    z.setValueOf('FLVconfig', 'video_volume200', false);
                                    r();
                                }
                            }, {
                                id: 'video_volume100',
                                type: 'radio',
                                label: '',
                                items: [
                                    [' 50%', '100']
                                ],
                                'default': '',
                                onClick: function() {
                                    var z = CKEDITOR.dialog.getCurrent();
                                    z.setValueOf('FLVconfig', 'video_volume1', false);
                                    z.setValueOf('FLVconfig', 'video_volume50', false);
                                    z.setValueOf('FLVconfig', 'video_volume150', false);
                                    z.setValueOf('FLVconfig', 'video_volume200', false);
                                    r();
                                }
                            }, {
                                id: 'video_volume150',
                                type: 'radio',
                                label: '',
                                items: [
                                    [' 75%', '150']
                                ],
                                'default': '',
                                onClick: function() {
                                    var z = CKEDITOR.dialog.getCurrent();
                                    z.setValueOf('FLVconfig', 'video_volume1', false);
                                    z.setValueOf('FLVconfig', 'video_volume50', false);
                                    z.setValueOf('FLVconfig', 'video_volume100', false);
                                    z.setValueOf('FLVconfig', 'video_volume200', false);
                                    r();
                                }
                            }, {
                                id: 'video_volume200',
                                type: 'radio',
                                label: '',
                                items: [
                                    [' 100%', '200']
                                ],
                                'default': '',
                                onClick: function() {
                                    var z = CKEDITOR.dialog.getCurrent();
                                    z.setValueOf('FLVconfig', 'video_volume1', false);
                                    z.setValueOf('FLVconfig', 'video_volume50', false);
                                    z.setValueOf('FLVconfig', 'video_volume100', false);
                                    z.setValueOf('FLVconfig', 'video_volume150', false);
                                    r();
                                }
                            }]
                        }]
                    }]
                }, {
                    type: 'hbox',
                    widths: ['40%', '60%'],
                    children: [{
                        type: 'vbox',
                        padding: 3,
                        children: [{
                            type: 'html',
                            html: d.lang.player.flv.popuptitle + ':'
                        }, {
                            type: 'vbox',
                            padding: 3,
                            style: 'padding-left: 25px',
                            children: [{
                                id: 'popup',
                                type: 'checkbox',
                                label: ' ' + d.lang.player.flv.popupdesc,
                                'default': '',
                                onChange: function() {
                                    var z = CKEDITOR.dialog.getCurrent(),
                                        A = z.getButton('ok').domId,
                                        B = z.getButton('linkok').domId;
                                    if (this.getValue() == false) {
                                        z.setValueOf('FLVconfig', 'popuptext', '');
                                        z.disableButton('linkok');
                                        z.enableButton('ok');
                                        document.getElementById(A).style.display = 'block';
                                        document.getElementById(B).style.display = 'none';
                                    } else {
                                        z.setValueOf('FLVconfig', 'popuptext', e);
                                        z.disableButton('ok');
                                        z.enableButton('linkok');
                                        document.getElementById(A).style.display = 'none';
                                        document.getElementById(B).style.display = 'block';
                                    }
                                }
                            }]
                        }]
                    }, {
                        type: 'vbox',
                        padding: 3,
                        children: [{
                            type: 'html',
                            html: '&nbsp;'
                        }, {
                            type: 'hbox',
                            widths: ['16%', '58%', '26%'],
                            children: [{
                                type: 'html',
                                html: '<div style="margin-top: 5px">' + d.lang.player.flv.linktxt + ':</div>'
                            }, {
                                id: 'popuptext',
                                type: 'text',
                                label: ' ',
                                'default': '',
                                onKeyup: function() {
                                    var z = CKEDITOR.dialog.getCurrent();
                                    if (z.getValueOf('FLVconfig', 'popup') == false) this.setValue('');
                                }
                            }, {
                                type: 'html',
                                html: '&nbsp;'
                            }]
                        }]
                    }]
                }]
            }, 'properties');

            function u() {
                var z = CKEDITOR.dialog.getCurrent();
                z.setValueOf('info', 'width', '200');
                z.setValueOf('info', 'height', '20');
                z.setValueOf('MP3config', 'audio_autoload', true);
                z.setValueOf('MP3config', 'audio_width200', '200');
                z.setValueOf('MP3config', 'audio_showvolume', true);
                z.setValueOf('MP3config', 'audio_volume150', '150');
                w();
            };

            function v(z) {
                if (k(z, 'volume') != '') {
                    var A = CKEDITOR.dialog.getCurrent(),
                        B = A.getValueOf('info', 'width');
                    if (A.getContentElement('MP3config', 'audio_width' + B)) A.setValueOf('MP3config', 'audio_width' + B, B);
                    A.setValueOf('info', 'height', '20');
                    var C = z.split('?'),
                        D = C[1].split('&');
                    for (var E = 0; E < D.length; E++) {
                        D[E] = l(D[E]);
                        var F = D[E].split('=');
                        if (A.getContentElement('MP3config', 'audio_' + F[0])) A.setValueOf('MP3config', 'audio_' + F[0], true);
                        else {
                            if (F[0] == 'width') {
                                A.setValueOf('info', 'width', F[1]);
                                if (A.getContentElement('MP3config', 'audio_width' + F[1])) A.setValueOf('MP3config', 'audio_width' + F[1], F[1]);
                            }
                            if (F[0] == 'volume') A.setValueOf('MP3config', 'audio_volume' + F[1], F[1]);
                        }
                    }
                }
            };

            function w() {
                var z = CKEDITOR.dialog.getCurrent(),
                    A = z.getValueOf('info', 'src'),
                    B = playerConf.playerpath + '/mp3player.swf?mp3=' + k(A, 'mp3');
                z.setValueOf('info', 'src', B + x());
            };

            function x() {
                var z = CKEDITOR.dialog.getCurrent(),
                    A = '&showloading=always&loadingcolor=' + f + '&buttonovercolor=' + f + '&sliderovercolor=' + f;
                if (z.getValueOf('MP3config', 'audio_loop') == true) A += '&loop=1';
                if (z.getValueOf('MP3config', 'audio_autoload') == true) A += '&autoload=1';
                if (z.getValueOf('MP3config', 'audio_autoplay') == true) A += '&autoplay=1';
                A = A + '&width=' + z.getValueOf('info', 'width');
                if (z.getValueOf('MP3config', 'audio_showstop') == true) A += '&showstop=1';
                if (z.getValueOf('MP3config', 'audio_showinfo') == true) A += '&showinfo=1';
                if (z.getValueOf('MP3config', 'audio_showvolume') == true) A += '&showvolume=1';
                if (z.getValueOf('MP3config', 'audio_volume1') == '1') A += '&volume=1';
                else if (z.getValueOf('MP3config', 'audio_volume50') == '50') A += '&volume=50';
                else if (z.getValueOf('MP3config', 'audio_volume100') == '100') A += '&volume=100';
                else if (z.getValueOf('MP3config', 'audio_volume150') == '150') A += '&volume=150';
                else if (z.getValueOf('MP3config', 'audio_volume200') == '200') A += '&volume=200';
                else A += '&volume=150';
                return A;
            };
            var y = function() {
                var z = CKEDITOR.dialog.getCurrent();
                z.setValueOf('MP3config', 'audio_loop', false);
                z.setValueOf('MP3config', 'audio_autoload', false);
                z.setValueOf('MP3config', 'audio_autoplay', false);
                z.setValueOf('MP3config', 'audio_width200', false);
                z.setValueOf('MP3config', 'audio_width300', false);
                z.setValueOf('MP3config', 'audio_width400', false);
                z.setValueOf('MP3config', 'audio_showstop', false);
                z.setValueOf('MP3config', 'audio_showinfo', false);
                z.setValueOf('MP3config', 'audio_showvolume', false);
                z.setValueOf('MP3config', 'audio_volume1', false);
                z.setValueOf('MP3config', 'audio_volume50', false);
                z.setValueOf('MP3config', 'audio_volume100', false);
                z.setValueOf('MP3config', 'audio_volume150', false);
                z.setValueOf('MP3config', 'audio_volume200', false);
                m('both');
            };
            c.addContents({
                id: 'MP3config',
                label: d.lang.player.mp3.title,
                elements: [{
                    type: 'hbox',
                    widths: ['50%', '50%'],
                    children: [{
                        type: 'vbox',
                        padding: 3,
                        children: [{
                            type: 'html',
                            html: d.lang.player.mp3.settings + ':'
                        }, {
                            type: 'vbox',
                            padding: 3,
                            style: 'padding-left: 25px',
                            children: [{
                                id: 'audio_loop',
                                type: 'checkbox',
                                label: ' ' + d.lang.player.mp3.loop,
                                onClick: function() {
                                    w();
                                }
                            }, {
                                id: 'audio_autoload',
                                type: 'checkbox',
                                label: ' ' + d.lang.player.mp3.autoload,
                                onClick: function() {
                                    w();
                                }
                            }, {
                                id: 'audio_autoplay',
                                type: 'checkbox',
                                label: ' ' + d.lang.player.mp3.autoplay,
                                onClick: function() {
                                    w();
                                }
                            }]
                        }]
                    }, {
                        type: 'vbox',
                        padding: 3,
                        children: [{
                            type: 'html',
                            html: d.lang.player.mp3.format
                        }, {
                            type: 'vbox',
                            padding: 3,
                            style: 'padding-left: 25px',
                            children: [{
                                id: 'audio_width200',
                                type: 'radio',
                                label: '',
                                items: [
                                    [' 200 ' + d.lang.player.mp3.pixel, '200']
                                ],
                                'default': '',
                                onClick: function() {
                                    var z = CKEDITOR.dialog.getCurrent();
                                    z.setValueOf('MP3config', 'audio_width300', false);
                                    z.setValueOf('MP3config', 'audio_width400', false);
                                    z.setValueOf('info', 'width', '200');
                                    z.setValueOf('info', 'height', '20');
                                    w();
                                }
                            }, {
                                id: 'audio_width300',
                                type: 'radio',
                                label: '',
                                items: [
                                    [' 300 ' + d.lang.player.mp3.pixel, '300']
                                ],
                                'default': '',
                                onClick: function() {
                                    var z = CKEDITOR.dialog.getCurrent();
                                    z.setValueOf('MP3config', 'audio_width200', false);
                                    z.setValueOf('MP3config', 'audio_width400', false);
                                    z.setValueOf('info', 'width', '300');
                                    z.setValueOf('info', 'height', '20');
                                    w();
                                }
                            }, {
                                id: 'audio_width400',
                                type: 'radio',
                                label: '',
                                items: [
                                    [' 400 ' + d.lang.player.mp3.pixel, '400']
                                ],
                                'default': '',
                                onClick: function() {
                                    var z = CKEDITOR.dialog.getCurrent();
                                    z.setValueOf('MP3config', 'audio_width200', false);
                                    z.setValueOf('MP3config', 'audio_width300', false);
                                    z.setValueOf('info', 'width', '400');
                                    z.setValueOf('info', 'height', '20');
                                    w();
                                }
                            }]
                        }]
                    }]
                }, {
                    type: 'hbox',
                    widths: ['50%', '50%'],
                    children: [{
                        type: 'vbox',
                        padding: 3,
                        children: [{
                            type: 'html',
                            html: d.lang.player.mp3.playerbar + ':'
                        }, {
                            type: 'vbox',
                            padding: 3,
                            style: 'padding-left: 25px',
                            children: [{
                                id: 'audio_showstop',
                                type: 'checkbox',
                                label: ' ' + d.lang.player.mp3.stop,
                                onClick: function() {
                                    w();
                                }
                            }, {
                                id: 'audio_showinfo',
                                type: 'checkbox',
                                label: ' ' + d.lang.player.mp3.titleinfo,
                                onClick: function() {
                                    w();
                                }
                            }, {
                                id: 'audio_showvolume',
                                type: 'checkbox',
                                label: ' ' + d.lang.player.mp3.volume,
                                onClick: function() {
                                    w();
                                }
                            }]
                        }]
                    }, {
                        type: 'vbox',
                        padding: 3,
                        children: [{
                            type: 'html',
                            html: d.lang.player.mp3.volumestart + ':'
                        }, {
                            type: 'vbox',
                            padding: 3,
                            style: 'padding-left: 25px',
                            children: [{
                                id: 'audio_volume1',
                                type: 'radio',
                                label: '',
                                items: [
                                    [' ' + d.lang.player.mp3.volumenull, '1']
                                ],
                                'default': '',
                                onClick: function() {
                                    var z = CKEDITOR.dialog.getCurrent();
                                    z.setValueOf('MP3config', 'audio_volume50', false);
                                    z.setValueOf('MP3config', 'audio_volume100', false);
                                    z.setValueOf('MP3config', 'audio_volume150', false);
                                    z.setValueOf('MP3config', 'audio_volume200', false);
                                    w();
                                }
                            }, {
                                id: 'audio_volume50',
                                type: 'radio',
                                label: '',
                                items: [
                                    [' 25%', '50']
                                ],
                                'default': '',
                                onClick: function() {
                                    var z = CKEDITOR.dialog.getCurrent();
                                    z.setValueOf('MP3config', 'audio_volume1', false);
                                    z.setValueOf('MP3config', 'audio_volume100', false);
                                    z.setValueOf('MP3config', 'audio_volume150', false);
                                    z.setValueOf('MP3config', 'audio_volume200', false);
                                    w();
                                }
                            }, {
                                id: 'audio_volume100',
                                type: 'radio',
                                label: '',
                                items: [
                                    [' 50%', '100']
                                ],
                                'default': '',
                                onClick: function() {
                                    var z = CKEDITOR.dialog.getCurrent();
                                    z.setValueOf('MP3config', 'audio_volume1', false);
                                    z.setValueOf('MP3config', 'audio_volume50', false);
                                    z.setValueOf('MP3config', 'audio_volume150', false);
                                    z.setValueOf('MP3config', 'audio_volume200', false);
                                    w();
                                }
                            }, {
                                id: 'audio_volume150',
                                type: 'radio',
                                label: '',
                                items: [
                                    [' 75%', '150']
                                ],
                                'default': '',
                                onClick: function() {
                                    var z = CKEDITOR.dialog.getCurrent();
                                    z.setValueOf('MP3config', 'audio_volume1', false);
                                    z.setValueOf('MP3config', 'audio_volume50', false);
                                    z.setValueOf('MP3config', 'audio_volume100', false);
                                    z.setValueOf('MP3config', 'audio_volume200', false);
                                    w();
                                }
                            }, {
                                id: 'audio_volume200',
                                type: 'radio',
                                label: '',
                                items: [
                                    [' 100%', '200']
                                ],
                                'default': '',
                                onClick: function() {
                                    var z = CKEDITOR.dialog.getCurrent();
                                    z.setValueOf('MP3config', 'audio_volume1', false);
                                    z.setValueOf('MP3config', 'audio_volume50', false);
                                    z.setValueOf('MP3config', 'audio_volume100', false);
                                    z.setValueOf('MP3config', 'audio_volume150', false);
                                    w();
                                }
                            }]
                        }]
                    }]
                }]
            }, 'properties');
            c.addButton({
                type: 'button',
                id: 'linkok',
                label: 'OK',
                title: 'OK',
                'class': 'cke_dialog_ui_button_linkok',
                style: 'display: none;',
                onClick: function() {
                    o();
                }
            }, 'cancel');
            CKEDITOR.document.appendStyleText('.cke_dialog_ui_button_linkok span.cke_dialog_ui_button { width: 60px; } ');
            c.onLoad = function() {
                var z = CKEDITOR.dialog.getCurrent();
                z.on('show', function() {
                    n();
                }, null, null, 100);
                z.on('hide', t);
            };
        }
    });
})();