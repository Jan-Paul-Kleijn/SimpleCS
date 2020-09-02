var carousel = {
  initialised:false, e:null, dataCode:0, x:0, y:0, prevX:0, prevY:0, mouseDown:false, evPropagated:false, scrollStart:new Date().getTime(),
  buildStrip:function() {
    
  },
  setFlags:function() {
    if( document.documentElement ) this.dataCode=3;
    else if(document.body && typeof document.body.scrollTop!='undefined') this.dataCode=2;
    else if( this.e && this.e.pageX!='undefined' ) this.dataCode=1;
    this.initialised=true;
  },
  create:function(error,response,obj) {
    docFrag = document.createDocumentFragment();

    box = document.createElement("div");
    box.setAttribute("class","carousel-box");

    strip = document.createElement("div");
    strip.setAttribute("id","carousel-strip");
    strip.setAttribute("class","carousel-strip");
    
    data = JSON.parse(response);
    
    for(var x=0; x<data.artObjects.length; x++) {
      
      art = document.createElement("div");
      art.setAttribute("class","carousel-art");
      
      artLink = document.createElement("a");
      artLink.setAttribute("href",data.artObjects[x].location);
      artLink.setAttribute("class","image-link");
      artLink.setAttribute("title",data.artObjects[x].title);
      artLink.onclick = function(event){event.preventDefault();}
      
      artImage = document.createElement("img");
      artImage.setAttribute("src",data.artObjects[x].imgsrc);
      
      artLink.appendChild(artImage);
      art.appendChild(artLink);

      docFrag.appendChild(art);
    }

    strip.appendChild(docFrag);
    box.appendChild(strip);
    obj.appendChild(box);

    carousel.addHandlers(strip);
  },
  addHandlers:function(obj) {
    if(!document.getElementById && document.captureEvents && Event) document.captureEvents(Event.MOUSEMOVE);
    carousel.addToHandler(document, 'onmousemove', function(){carousel.getMousePosition(arguments[0],obj);}); 
    carousel.addToHandler(document, 'onmousedown', function(){carousel.mouseDown=true;return false;});
    carousel.addToHandler(document, 'onmouseup', function(){carousel.mouseDown=false;}); 
    carousel.addToHandler(document, 'onselectstart', function(){return false;});   
  },    
  init:function(objID,parent) {
    if(document.getElementById("srcimg") && document.getElementById("canvas")) {
      artistry.presenter("srcimg","canvas");
      simpleCS.addevent(window,"resize",(function(i,d){return function(){artistry.presenter(i,d);}})("srcimg","canvas"),false);
      var style = document.createElement("style"),
          parentHeight = parseInt(simpleCS.getElementStyleProp(parent.id,'height')) + 220;
      style.type = "text/css";
      style.innerHTML = "#"+parent.id+":hover {height:"+parentHeight+"px !important;}";
      document.getElementsByTagName('head')[0].appendChild(style);
      simpleCS.addevent(window,"scroll",function() {
        if(simpleCS.getDocHeight() <= (simpleCS.getScrollXY()[1] + window.innerHeight)) {
          document.getElementById("mainmenubar").style.height = parentHeight+"px";
          document.getElementById("hoofdmenu").style.top = "0px";
        } else {
          document.getElementById("mainmenubar").removeAttribute('style');
          document.getElementById("hoofdmenu").removeAttribute('style');
        }        
      },false);
      simpleCS.requestJSON(window.location.protocol+'//'+window.location.hostname+'/api/v1/artistry-carousel'+simpleCS.pop_url(window.location.pathname),carousel.create,parent); // obj = mainmenubar object
    }
  },
  scrollList:function() {
    carousel.scrollStart = new Date().getTime();
  },
  canClickInList:function() {
    var diff =  new Date().getTime() - carousel.scrollStart;
    if (diff > 300) {
        return true;
    } else {
        return false;
    }
  },
  getMousePosition:function(e,obj) {
    if(!e) this.e = event;
    else this.e = e;
    if(!this.initialised) this.setFlags(); 

    switch( this.dataCode ) {
       case 3 : this.x = Math.max(document.documentElement.scrollLeft, document.body.scrollLeft) - this.e.clientX;
//                this.y = Math.max(document.documentElement.scrollTop, document.body.scrollTop) - this.e.clientY;
                break;
       case 2 : this.x = document.body.scrollLeft - this.e.clientX;
//                this.y = document.body.scrollTop - this.e.clientY;
                break;
       case 1 : this.x = this.e.pageX;
//                this.y = this.e.pageY;
                break;
    }
    var bla = obj.children;
    if(this.mouseDown && (this.x!=this.prevX || this.y!=this.prevY)) {
      for(var x=0; x<bla.length; x++) {
        simpleCS.delevent(bla[x].children[0], "click", carousel.view, false);
      }
      carousel.evPropagated = false;
      obj.scrollLeft += (this.x-this.prevX);
      obj.scrollTop += (this.y-this.prevY);
    } else if(!carousel.evPropagated && carousel.canClickInList()) { 
      for(var y=0; y<bla.length; y++) {
        simpleCS.addevent(bla[y].children[0], "click", carousel.view, false);
      }
      carousel.evPropagated = true;
    }
    this.prevX=this.x;
    this.prevY=this.y; 
  },
  view:function(e) {
    if(!e) this.e = event;
    else this.e = e;
    window.location.href = e.target.parentNode.href
  },
  addToHandler: function(obj, evt, func) {
    if(obj[evt]) {
      obj[evt]=function(f,g) {
        return function() {
          f.apply(this,arguments);
          return g.apply(this,arguments);
        };
      }(func, obj[evt]);
    } else obj[evt]=func;
  }
}

