
/**************************************
    Webutler V2.1 - www.webutler.de
    Copyright (c) 2008 - 2011
    Autor: Sven Zinke
    Free for any use
    Lizenz: GPL
**************************************/


var FLVPlayer_rootpath = 'includes/javascript/';

document.writeln('\n<link rel="stylesheet" type="text/css" href="' + FLVPlayer_rootpath + 'player/flvvideobox.css" />');

function FLVscreenWidth()
{
	var screenw = (window.innerWidth) ? window.innerWidth : (document.documentElement && document.documentElement.clientWidth) ? document.documentElement.clientWidth : document.body.clientWidth;
	
	return screenw;
}

function FLVscreenHeight()
{
	var screenh = (window.innerHeight) ? window.innerHeight : (document.documentElement && document.documentElement.clientHeight) ? document.documentElement.clientHeight : document.body.clientHeight;
	
	return screenh;
}

function FLV_BrowserIsIE6()
{
	var result = navigator.appVersion.match(/MSIE (\d+\.\d+)/, '');
	var itsIE6 = (result != null && Number(result[1]) >= 5.5 && Number(result[1]) < 7);
	
	return itsIE6;
}

function makeFLVScreen()
{
	var divpos = 'fixed';
	var closecursor = 'pointer';
	var divclose = 'absolute';
	if(FLV_BrowserIsIE6()) {
	    divpos = 'absolute';
	    closecursor = 'hand';
	    divclose = 'relative';
    }
	
    var obj = document.body;
    
	var bg = document.createElement('div');
	obj.appendChild(bg);
	bg.id = 'flvscreenbg';
	bg.style.display = 'none';
	bg.style.position = divpos;
	bg.style.left = '0px';
	bg.style.top = '0px';
	bg.style.width = FLVscreenWidth() + 'px';
	bg.style.height = FLVscreenHeight() + 'px';
	bg.style.zIndex = '100';
    
	var box = document.createElement('div');
	obj.appendChild(box);
	box.id = 'flvborderdiv';
	box.style.display = 'none';
	box.style.position = divpos;
	box.style.zIndex = '110';
	
	var close = document.createElement('div');
	box.appendChild(close);
	close.id = 'flvtopclosediv';
	close.style.height = '0px';
	close.style.lineHeight = '0px';
	
	var climg = document.createElement('img');
	close.appendChild(climg);
	climg.id = 'flvcloseimg';
	climg.src = FLVPlayer_rootpath + 'player/close.png';
	climg.style.position = divclose;
	climg.onclick = new Function("closeFLVPopup()");
	climg.style.cursor = closecursor;

	var embeddiv = document.createElement('div');
	box.appendChild(embeddiv);
	embeddiv.id = 'flvembeddiv';
    
	var flv = document.createElement('embed');
	embeddiv.appendChild(flv);
	flv.id = 'popupFLV';
	flv.setAttribute( 'type', 'application/x-shockwave-flash' ) ;
	flv.setAttribute( 'pluginspage', 'http://www.macromedia.com/go/getflashplayer' ) ;
	flv.setAttribute( 'wmode', 'transparent' ) ;
	flv.setAttribute( 'allowfullscreen', 'true' ) ;
	flv.setAttribute( 'play', 'false' ) ;
	flv.setAttribute( 'loop', 'false' ) ;
	flv.setAttribute( 'menu', 'false' ) ;
}

function openFLVPopup(path, w, h)
{	
    var w = parseInt(w);
    var h = parseInt(h);
    var screenh = parseInt(FLVscreenHeight());
    var screenw = parseInt(FLVscreenWidth());
    var climg = document.getElementById('flvcloseimg');

    document.getElementById('flvscreenbg').style.display = 'block';
    document.getElementById('flvborderdiv').style.display = 'block';
    document.getElementById('flvborderdiv').style.height = h + 'px';
    document.getElementById('flvborderdiv').style.width = w + 'px';
    
	if(FLV_BrowserIsIE6()) {
        document.getElementById('flvscreenbg').style.height = document.body.scrollHeight + 'px';
    }

    document.getElementById('flvborderdiv').style.top = (screenh-h)/2 + 'px';
    document.getElementById('flvborderdiv').style.left = (screenw-w)/2 + 'px';
    document.getElementById('flvtopclosediv').style.width = w + 'px';
    
	if(FLV_BrowserIsIE6()) {
        document.getElementById('flvscreenbg').style.height = document.body.scrollHeight + 'px';
	    var closeimg = '<span id="flvcloseimg" style="display: inline-block; width: ' + climg.width + 'px; height: ' + climg.height + 'px; filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(src=\'' + FLVPlayer_rootpath + 'player/close.png\', sizingMethod=\'scale\'); margin: -' + climg.height + 'px 0px 0px ' + parseInt(w-climg.width) + 'px; ' + climg.style.cssText + '" onclick="closeFLVPopup()"></span>';
	    climg.outerHTML = closeimg;
    }
    else {
	    climg.style.margin = '-' + climg.height + 'px 0px 0px ' + parseInt(w-climg.width) + 'px';
	}
	
	document.getElementById('popupFLV').setAttribute( 'src', path ) ;
    document.getElementById('popupFLV').width = w;
    document.getElementById('popupFLV').height = h;
    document.getElementById('popupFLV').outerHTML = document.getElementById('popupFLV').outerHTML;
}

function resizeFLVPopup()
{
    var w = parseInt(document.getElementById('flvborderdiv').style.width);
    var h = parseInt(document.getElementById('flvborderdiv').style.height);
    var screenh = parseInt(FLVscreenHeight());
    var screenw = parseInt(FLVscreenWidth());

    document.getElementById('flvscreenbg').style.height = screenh + 'px';
    document.getElementById('flvscreenbg').style.width = screenw + 'px';
    document.getElementById('flvborderdiv').style.top = (screenh-h)/2 + 'px';
    document.getElementById('flvborderdiv').style.left = (screenw-w)/2 + 'px';
}

function closeFLVPopup()
{
	document.getElementById('popupFLV').setAttribute( 'src', '' ) ;
    document.getElementById('popupFLV').outerHTML = document.getElementById('popupFLV').outerHTML;
    document.getElementById('flvscreenbg').style.display = 'none';
    document.getElementById('flvborderdiv').style.display = 'none';
}

(function()
{	
    if (window.addEventListener)
    {
        window.addEventListener('load', makeFLVScreen, false); 
        window.addEventListener('resize', resizeFLVPopup, false);    
    }
    else
    {
        window.attachEvent('onload', makeFLVScreen);
        window.attachEvent('onresize', resizeFLVPopup);
    }
})();


