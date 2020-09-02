var simpleCS = {
  displayNotification:function(){
    var body = document.getElementsByTagName("body")[0],
    koekiemelding=document.createElement('DIV'),
    koekiemeldingId="cookiewarning";
    body.insertBefore(koekiemelding,body.childNodes[0]);
    koekiemelding.id=koekiemeldingId;
    var message = "<form class=\"koekie\" action=\""+document.location.href+"\" method=\"post\">";
    message += "<h2>Cookies op "+location.hostname+"</h2>\n";
    message += "<div class=\"koekieknoppen\"><input type=\"checkbox\" name=\"geen_koekie\" value=\"NOCOOKIE\" id=\"geen_koekie\" /><label for=\"geen_koekie\">Ik heb geen behoefte aan het delen van deze pagina via sociale netwerken</label>\n";
    message += "<input type=\"submit\" value=\"Cookies aanpassen\" id=\"koekie_anders\" />\n";
    message += "<a href=\""+document.location.href+"#lead\" class=\"button\" id=\"wel_koekie\" onclick=\"simpleCS.setCookie('jsCookieCheck','OK',365);\">Ik vind het prima</a></div>\n";
    message += "<hr />\n</form>\n";
    koekiemelding.innerHTML = message;
  },
  findActions:function(){
    var allDivs = document.documentElement.getElementsByTagName('span');
    for(var x=0; x<allDivs.length; x++) {
      var div = allDivs[x];
      if(div.hasAttribute("data-action")) {
        action = div.getAttribute("data-action");
        if(action == "goback") {
          simpleCS.addevent(div,"click",function(){
var href = window.location.pathname.trim().replace(/^\/+|\/+$/g, '').split('/');
href.pop();
window.location.href = "/"+href.join('/');
          },false);
        }
        
      }
    }
  },
  getDocHeight:function(){
    return Math.max(
      document.body.scrollHeight, document.documentElement.scrollHeight,
      document.body.offsetHeight, document.documentElement.offsetHeight,
      document.body.clientHeight, document.documentElement.clientHeight
    );
  },
  getScrollXY:function(){
    var scrOfX = 0, scrOfY = 0;
    if( typeof( window.pageYOffset ) == 'number' ) {
      //Netscape compliant
      scrOfY = window.pageYOffset;
      scrOfX = window.pageXOffset;
    } else if( document.body && ( document.body.scrollLeft || document.body.scrollTop ) ) {
      //DOM compliant
      scrOfY = document.body.scrollTop;
      scrOfX = document.body.scrollLeft;
    } else if( document.documentElement && ( document.documentElement.scrollLeft || document.documentElement.scrollTop ) ) {
      //IE6 standards compliant mode
      scrOfY = document.documentElement.scrollTop;
      scrOfX = document.documentElement.scrollLeft;
    }
    return [ scrOfX, scrOfY ];
  },
  getHeight:function(target) {
    if(target == "window")
      return window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight;
    else
      return document.getElementById(target).offsetHeight;
  },
  getWidth:function(target) {
    if(target == "window")
      return window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
    else
      return document.getElementById(target).offsetWidth;
  },
  getChar:function(event){
    var keyCode = ('which' in event) ? event.which : event.keyCode;
    if (keyCode=="38" || keyCode=="40" || keyCode=="13" || keyCode=="9") {
      event.preventDefault();
    }
    return keyCode;
  },
  addevent:function(obj,type,fn,capture){
    if(obj) {
      if (obj.attachEvent) {
        obj['e'+type+fn] = fn;
        obj[type+fn] = function(){obj['e'+type+fn](window.event);}
        obj.attachEvent('on'+type, obj[type+fn]);
      } else
        obj.addEventListener(type, fn, false);
    }
  },
  delevent:function(obj, type, fn, capture){
    if (obj.detachEvent) {
      obj.detachEvent('on'+type, obj[type+fn]);
      obj[type+fn] = null;
    } else
      obj.removeEventListener(type, fn, false);
  },
  getElementStyleProp:function(elemid,prop,cn){
    var stylePropValue, elem=(typeof(cn)!="undefined")?document.getElementById(elemid).childNodes[cn]:document.getElementById(elemid);
    if(!elem.currentStyle) {
      stylePropValue=getComputedStyle(elem,null).getPropertyValue(prop);
    } else {
      if(typeof(elem.currentStyle[prop])=="undefined") {
        var DOMprop = css2DOM(prop);
        stylePropValue=elem.currentStyle[DOMprop];
      } else {
        stylePropValue=elem.currentStyle[prop];
      }
    }
    return stylePropValue;
  },
  getClassStyleProp:function(tagname,classname,cssProp){
    var stylePropValue, obj=simpleCS.getElementsByTagAndClassName(tagname,classname);
    if(obj[0] !== "undefined") {
      if(!obj[0].currentStyle) {
        stylePropValue=getComputedStyle(obj[0],null).getPropertyValue(cssProp);
      } else {
        if(typeof(obj[0].currentStyle[cssProp])=="undefined") {
          var DOMprop = css2DOM(cssProp);
          stylePropValue=obj[0].currentStyle[DOMprop];
        } else {
          stylePropValue=obj[0].currentStyle[cssProp];
        }
      }
    }
    return stylePropValue;
  },
/*
  function setCookie(cname,cvalue,exdays) {
    var d = new Date();
    d.setTime(d.getTime()+(exdays*24*60*60*1000));
    var expires = "expires="+d.toGMTString();
    document.cookie = cname + "=" + cvalue + "; " + expires;
  }

  function getCookie(cname) {
    var name = cname + "="
    ,   ca = document.cookie.split(';')
    ,   cn = ca.length
    ,   i
    ,   c
    ,   cks = []
    ,   vls = [];
    for(i=0; i<cn; i++) {
      c = ca[i].trim();
      cks[i] = c.substr(0,(c.indexOf('=')));
      vls[i] = c.substr(c.indexOf('='),c.length);
    }
    if (cks.indexOf(cname)==-1) return false;
    return vls[cks.indexOf(cname)];
  } 
*/
  requestHTML:function(url,callback,fromElem){
    var req = new XMLHttpRequest;
    var exdate=new Date();
    exdate.setDate(exdate.getDate() + 1);
    req.open("GET", url, true);
    req.setRequestHeader("Accept", "text/html");
    req.onreadystatechange = function() {
      if (req.readyState === 4) {
        if (req.status === 200) callback(null, req.responseText, fromElem);
        else callback(req.statusText, 'Oh my, why does this keep happening to me?', fromElem);
      }
    };
    req.send(null);
  },
  requestJSON:function(url,callback,fromElem){
    var req = new XMLHttpRequest;
    var exdate=new Date();
    exdate.setDate(exdate.getDate() + 1);
    req.open("GET", url, true);
    req.setRequestHeader("Accept", "application/json");
    req.onreadystatechange = function() {
      if (req.readyState === 4) {
        if (req.status === 200) callback(null, req.responseText, fromElem);
        else callback(req.statusText, 'Oh my, why does this keep happening to me?', fromElem);
      }
    };
    req.send(null);
  },
  getElementsByTagAndClassName:function(tagname,classname){
    var elements = document.getElementsByTagName(tagname),
        elementsN = elements.length,
        selection = [];
    for(var d=0;d<elementsN;d++) {
      if(elements[d].className.indexOf(classname)!=-1) {
        selection.push(elements[d]);
      }
    }
    return selection;
  },
  getFocus:function(e){
    var focused;
    if (!e) var e = window.event;
    if (e.target) focused = e.target;
    else if (e.srcElement) focused = e.srcElement;
    if (focused.nodeType == 3) focused = focused.parentNode;
    if (document.querySelector) {
      return focused.id;
    } else if (!focused || focused == document.documentElement) {
      return focused;
    }
  },
  externelinks:function(evt){
    var a_tags=document.body.getElementsByTagName('a'),aantal_a_tags=a_tags.length,b,href_adres;
    for(b=0;b<aantal_a_tags;b++) {
      href = a_tags[b].getAttribute("href");
      if((" "+a_tags[b].className+" ").indexOf(" extern ") != -1) {
        simpleCS.addevent(a_tags[b],"click",(function(h){return function(evt){window.open(h,'_blank'); evt.preventDefault ? evt.preventDefault() : evt.returnValue = false;}})(href),false);
      } else {
        if(a_tags[b].hasAttribute("href") && a_tags[b].getAttribute("href").indexOf("#")==0) {
          simpleCS.addevent(a_tags[b],"click",(function(h){return function(evt){evt.preventDefault ? evt.preventDefault() : evt.returnValue = false;window.location.hash = h;}})(href),false);
        }
      }
    }
  },
  bookmarks:function(evt){
    var a_tags=document.body.getElementsByTagName('a'),aantal_a_tags=a_tags.length,b,href_adres;
    for(b=0;b<aantal_a_tags;b++) {
      if(a_tags[b].className == "extern" || a_tags[b].className == "extern nostyle" || a_tags[b].className == "nostyle extern") {
        href_adres=a_tags[b].getAttribute('href');
        simpleCS.addevent(a_tags[b],"click",(function(){var zhref_adres=href_adres;return function(evt){window.open(zhref_adres,'_blank'); evt.preventDefault ? evt.preventDefault() : evt.returnValue = false;}})(),false);
      }
    }
  },
  toarray:function(obj){
    var l = obj.length, i, out = [];
    for(i=0; i<l; i++) out[i] = obj[i];
    return out.filter(function(val){return !(val.nodeType==3);});
  },
  getCookie:function(c_name){
    var i,x,y,ARRcookies=document.cookie.split(";");
    for (i=0;i<ARRcookies.length;i++) {
      x=ARRcookies[i].substr(0,ARRcookies[i].indexOf("="));
      y=ARRcookies[i].substr(ARRcookies[i].indexOf("=")+1);
      x=x.replace(/^\s+|\s+$/g,"");
      if (x==c_name) {
        return unescape(y);
      }
    }
  },
  setCookie:function(c_name,value,exdays){
    var exdate=new Date();
    exdate.setDate(exdate.getDate() + exdays);
    var c_value=escape(value) + ((exdays==null) ? "" : "; expires="+exdate.toUTCString());
    document.cookie=c_name + "=" + c_value + "; path=/; SameSite=Strict";
    var cw = document.getElementById("cookiewarning");
    if(cw) {
      cw.innerHTML = "";
      cw.style.display = "none";
    }
  },
  checkCookie:function(){
    var cookieName="jsCookieCheck";
    var cookieChk=simpleCS.getCookie(cookieName);
    if (cookieChk!=null && cookieChk!="") {
      simpleCS.setCookie(cookieName,cookieChk,365);
    } else {
      simpleCS.displayNotification();  
    }
  },
  delete_cookie:function(name){
    document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:01 GMT; path=/; SameSite=Strict';
  },
  pop_url:function(url) {
    var urlArray = url.replace(/\/+$/, "").split('/');
    urlArray.pop();
    return( urlArray.join('/') );
  },
  swipedetect:function(el, callback) {
    var touchsurface = el,
      swipedir,
      startX,
      startY,
      distX,
      distY,
      threshold = 150, //required min distance traveled to be considered swipe
      restraint = 100, // maximum distance allowed at the same time in perpendicular direction
      allowedTime = 300, // maximum time allowed to travel that distance
      elapsedTime,
      startTime,
      handleswipe = callback || function(swipedir){}

    touchsurface.addEventListener('touchstart', function(e){
      var touchobj = e.changedTouches[0]
      swipedir = 'none'
      dist = 0
      startX = touchobj.pageX
      startY = touchobj.pageY
      startTime = new Date().getTime() // record time when finger first makes contact with surface
      e.preventDefault()
     }, false)
   
    touchsurface.addEventListener('touchmove', function(e){
      e.preventDefault() // prevent scrolling when inside DIV
    }, false)
   
    touchsurface.addEventListener('touchend', function(e){
      var touchobj = e.changedTouches[0]
      distX = touchobj.pageX - startX // get horizontal dist traveled by finger while in contact with surface
      distY = touchobj.pageY - startY // get vertical dist traveled by finger while in contact with surface
      elapsedTime = new Date().getTime() - startTime // get time elapsed
      if (elapsedTime <= allowedTime){ // first condition for awipe met
        if (Math.abs(distX) >= threshold && Math.abs(distY) <= restraint){ // 2nd condition for horizontal swipe met
          swipedir = (distX < 0)? 'left' : 'right' // if dist traveled is negative, it indicates left swipe
        }
        else if (Math.abs(distY) >= threshold && Math.abs(distX) <= restraint){ // 2nd condition for vertical swipe met
          swipedir = (distY < 0)? 'up' : 'down' // if dist traveled is negative, it indicates up swipe
        }
      }
      handleswipe(swipedir)
      e.preventDefault()
    }, false)
  },
  showText:function() {
    style = document.getElementsByTagName('style')[0];
    if(style) style.parentNode.removeChild(style);
  },
  loadbatch:function(){
    if(simpleCS.getCookie('loadedBatch')) simpleCS.delete_cookie('loadedBatch');
    if(document.getElementById('loadbatch')) {
      var loadbtn = document.getElementById('loadbatch'),
          batchtimestamp = loadbtn.getAttribute('value'),
          autoload = 0;
      if(autoload = 1) {
        if(simpleCS.isInViewport(loadbtn)) {
          if(simpleCS.getCookie('loadedBatch')) {
            if(simpleCS.getCookie('loadedBatch') != batchtimestamp) {
              simpleCS.fetchbatch(getCookie('loadedBatch'),document.getElementById('loadbatch'));
              simpleCS.setCookie('loadedBatch',batchtimestamp,365);
            }
          } else {
            simpleCS.setCookie('loadedBatch',batchtimestamp,365);
            simpleCS.fetchbatch(batchtimestamp,document.getElementById('loadbatch'));
          }
        }
        simpleCS.addevent(window,"scroll",(function(a,b){
          return function(){
            if(simpleCS.isInViewport(b)) {
              if(simpleCS.getCookie('loadedBatch')) {
                if(simpleCS.getCookie('loadedBatch') != a) {
                  simpleCS.fetchbatch(getCookie('loadedBatch'),b);
                  simpleCS.setCookie('loadedBatch',a,365);
                }
              } else {
                simpleCS.setCookie('loadedBatch',a,365);
                simpleCS.fetchbatch(a,b);
              }
            }
          }
        })(batchtimestamp,loadbtn),false);
      } else {
        simpleCS.addevent(loadbtn,"click",(function(a,b){
          return function(){
            if(simpleCS.getCookie('loadedBatch')) {
              if(simpleCS.getCookie('loadedBatch') != a) {
                simpleCS.fetchbatch(getCookie('loadedBatch'),b);
                simpleCS.setCookie('loadedBatch',a,365);
              }
            } else {
              simpleCS.setCookie('loadedBatch',a,365);
              simpleCS.fetchbatch(a,b);
            }
          }
        })(batchtimestamp,loadbtn),false);
      }
    }
  },
  fetchbatch:function(marker,fromElem){
    var u = window.location.origin +"/api/v1/gordianpage/"+marker;
    simpleCS.requestHTML(u, simpleCS.writebatch, fromElem);
  },
  writebatch:function(error, html, fromElem){
    var el = document.createElement( 'html' );
    el.innerHTML = "<head><title>Semaphore</title><meta name=\"robots\" content=\"noindex, nofollow\" /></head><body>"+html+"</body>";
    var container = fromElem.parentNode,
        docFrag = document.createDocumentFragment(),
        uiswitch = container.removeChild(fromElem),
        divElemsRaw = el.getElementsByTagName('div'),
        divElemsClean = [],
        newuiswitch = el.getElementsByTagName('button');
    if(typeof newuiswitch[0] != "undefined") {
      var uiswitchVal = newuiswitch[0].getAttribute('data-lastinlist');
      simpleCS.setCookie('loadedBatch',uiswitchVal,365);
      uiswitch.setAttribute('data-lastinlist',uiswitchVal);
      for(var x=0;x<divElemsRaw.length;x++) {
        if(divElemsRaw[x].className=="agenda_item published") {
          divElemsClean.push(divElemsRaw[x]);
        }
      }
      for(var i=0;i<divElemsClean.length;i++) {
        docFrag.appendChild(divElemsClean[i]);
      }
      container.appendChild(docFrag);
      container.appendChild(uiswitch);
    }
  },
  isInViewport:function(element){
    if(element) {
      var rect = element.getBoundingClientRect(),
          windowHeight = window.innerHeight || document.documentElement.clientHeight,
          windowWidth = window.innerWidth || document.documentElement.clientWidth;
      return rect.bottom > 0 && rect.top < windowHeight && rect.right > 0 && rect.left < windowWidth
    }
    return false;
  },
  showMenu:function(menuId,e){
    hoofdmenu = document.getElementById(menuId);
    if(e.type == "load") {
      if(hoofdmenu.classList.contains("transDelay")) {
        hoofdmenu.classList.remove("transDelay");
        hoofdmenu.classList.add("transDirect");
      }      
    } else if((simpleCS.getDocHeight() <= ((simpleCS.getScrollXY()[1] + window.innerHeight) + 20))) {
      if(hoofdmenu.classList.contains("transDelay")) {
        hoofdmenu.classList.remove("transDelay");
        hoofdmenu.classList.add("transDirect");
      }
    } else {
      if(hoofdmenu.classList.contains('transDirect')) {
        hoofdmenu.classList.remove('transDirect');
        hoofdmenu.classList.add('transDelay');
      }
    }
  },
  previousElementSibling:function(elem){
    do {
      elem = elem.previousSibling;
    } while ( elem && elem.nodeType !== 1 );
    return elem;
  },
  hasClass:function(element, cls){
    return (' ' + element.className + ' ').indexOf(' ' + cls + ' ') > -1;
  },
  moveElem:function(elemID,destinationID) {
    if(document.getElementById(elemID) && document.getElementById(destinationID)) {
      elem = document.getElementById(elemID).parentNode.removeChild(document.getElementById(elemID));
      destination = document.getElementById(destinationID).appendChild(elem);
    }
  }
}
var gordianPage = {
  loadbatch:function() {
    if(simpleCS.getCookie('loadedBatch')) simpleCS.delete_cookie('loadedBatch');
    if(document.getElementById('loadbatch')) {
      var loadbtn = document.getElementById('loadbatch'),
          batchtimestamp = loadbtn.getAttribute('data-lastinlist'),
          autoload = 0;
      if(autoload == 1) {
        if(gordianPage.isInViewport(loadbtn)) {
          if(simpleCS.getCookie('loadedBatch')) {
            if(simpleCS.getCookie('loadedBatch') != batchtimestamp) {
              gordianPage.fetchbatch(simpleCS.getCookie('loadedBatch'),loadbtn);
              simpleCS.setCookie('loadedBatch',batchtimestamp,365);
            }
          } else {
            simpleCS.setCookie('loadedBatch',batchtimestamp,365);
            gordianPage.fetchbatch(batchtimestamp,loadbtn);
          }
        }
        simpleCS.addevent(window,"scroll",(function(a){
          return function(){
            if(gordianPage.isInViewport(document.getElementById('loadbatch'))) {
              if(simpleCS.getCookie('loadedBatch')) {
                if(simpleCS.getCookie('loadedBatch') != a) {
                  gordianPage.fetchbatch(simpleCS.getCookie('loadedBatch'),document.getElementById('loadbatch'));
                  simpleCS.setCookie('loadedBatch',a,365);
                }
              } else {
                simpleCS.setCookie('loadedBatch',a,365);
                gordianPage.fetchbatch(a,document.getElementById('loadbatch'));
              }
            }
          }
        })(batchtimestamp),false);
      } else {
        simpleCS.addevent(loadbtn,"click",(function(a,b){
          return function(){
            if(simpleCS.getCookie('loadedBatch')) {
              if(simpleCS.getCookie('loadedBatch') != a) {
                gordianPage.fetchbatch(simpleCS.getCookie('loadedBatch'),b);
              }
            } else {
              simpleCS.setCookie('loadedBatch',a,365);
console.log(a + ' - ' + b);
              gordianPage.fetchbatch(a,b);
            }
          }
        })(batchtimestamp,loadbtn),false);
      }
    }
  },
  fetchbatch:function(marker,fromElem) {
    var u = window.location.protocol + '//' + window.location.hostname + '/api/v1/gordianpage/' + marker + window.location.pathname;
    simpleCS.requestHTML(u, gordianPage.writebatch, fromElem);
  },
  isInViewport:function(element) {
    if(element) {
      var rect = element.getBoundingClientRect()
      var windowHeight = window.innerHeight || document.documentElement.clientHeight
      var windowWidth = window.innerWidth || document.documentElement.clientWidth
      return rect.bottom > 0 && rect.top < windowHeight && rect.right > 0 && rect.left < windowWidth
    }
    return false;
  },
  writebatch:function(error, html, fromElem) {
    var el = document.createElement( 'html' );
    el.innerHTML = "<head><title>Semaphore</title><meta name=\"robots\" content=\"noindex, nofollow\" /></head><body>"+html+"</body>";
    var container = fromElem.parentNode,
        docFrag = document.createDocumentFragment(),
        separator = simpleCS.previousElementSibling(fromElem),
        separator = container.removeChild(separator),
        uiswitch = container.removeChild(fromElem),
        divElemsRaw = el.getElementsByTagName('div'),
        divElemsClean = [],
        newuiswitch = el.getElementsByTagName('button');
    if(typeof newuiswitch[0] != "undefined") {
      var uiswitchVal = newuiswitch[0].getAttribute('data-lastinlist');
      simpleCS.setCookie('loadedBatch',uiswitchVal,365);
      uiswitch.setAttribute('data-lastinlist',uiswitchVal);
      uiswitch.setAttribute('value',uiswitchVal);
    }
    for(var x=0;x<divElemsRaw.length;x++) {
      if(simpleCS.hasClass(divElemsRaw[x],"agenta_item") || simpleCS.hasClass(divElemsRaw[x],"lead")) {
        divElemsClean.push(divElemsRaw[x]);
      }
    }
    for(var i=0;i<divElemsClean.length;i++) {
      docFrag.appendChild(divElemsClean[i]);
    }
    container.appendChild(docFrag);
    container.appendChild(separator);
    if(typeof newuiswitch[0] != "undefined") {
      container.appendChild(uiswitch);
    }
  }
}
var touchScrollElement = {
  initialised:false, e:null, dataCode:0, x:0, y:0, prevX:0, prevY:0, mouseDown:false,
  setFlags:function() {
    if( document.documentElement ) this.dataCode=3;
    else if(document.body && typeof document.body.scrollTop!='undefined') this.dataCode=2;
    else if( this.e && this.e.pageX!='undefined' ) this.dataCode=1;
    this.initialised=true;
  },
  init:function(obj) {
    if(!document.getElementById && document.captureEvents && Event) document.captureEvents(Event.MOUSEMOVE);
    touchScrollElement.addToHandler(document, 'onmousemove', function(){touchScrollElement.getMousePosition(arguments[0],obj);}); 
    touchScrollElement.addToHandler(document, 'onmousedown', function(){touchScrollElement.mouseDown=true;return false;});
    touchScrollElement.addToHandler(document, 'onmouseup', function(){touchScrollElement.mouseDown=false;}); 
    touchScrollElement.addToHandler(document, 'onselectstart',  function(){return false;} );   
  },
  addToHandler: function(obj, evt, func) {
    if (obj[evt]) {
        obj[evt] = function(f, g) {
            return function() {
                f.apply(this, arguments);
                return g.apply(this, arguments)
            }
        }(func, obj[evt])
    } else {
        obj[evt] = func
    }
  },
  getMousePosition: function(e, obj) {
      if (!e) {
          this.e = event
      } else {
          this.e = e
      }
      if (!this.initialised) {
          this.setFlags()
      }
      switch (this.dataCode) {
          case 3:
              this.x = Math.max(document.documentElement.scrollLeft, document.body.scrollLeft) - this.e.clientX;
              break;
          case 2:
              this.x = document.body.scrollLeft - this.e.clientX;
              break;
          case 1:
              this.x = this.e.pageX;
              break
      }
      var bla = obj.children;
      if (this.mouseDown && (this.x != this.prevX || this.y != this.prevY)) {
          for (var x = 0; x < bla.length; x += 1) {
              simpleCS.delevent(bla[x].children[0], "click", carousel.view, false)
          }
          carousel.evPropagated = false;
          obj.scrollLeft += (this.x - this.prevX);
          obj.scrollTop += (this.y - this.prevY)
      } else if (!carousel.evPropagated && carousel.canClickInList()) {
          for (var y = 0; y < bla.length; y += 1) {
              simpleCS.addevent(bla[y].children[0], "click", carousel.view, false)
          }
          carousel.evPropagated = true
      }
      this.prevX = this.x;
      this.prevY = this.y
  }
}

simpleCS.addevent(window,"load",function(event){activatePage(event);},false);
function activatePage(e) {
  simpleCS.moveElem('edit-control','imageInfo');
  simpleCS.moveElem('social_network_buttons','imageInfo');
  simpleCS.findActions();
  simpleCS.externelinks();
  if(typeof artistry !== 'undefined' && artistry.triggerArtistry()) {
    carousel.init(document.getElementById('mainmenubar'));
    document.getElementById('top').style.height = "0px";
  } else {
    simpleCS.showMenu("hoofdmenu",e);
    simpleCS.addevent(window, "scroll", function(event){simpleCS.showMenu("hoofdmenu",event);}, false);
  }
  gordianPage.loadbatch();
}
