addevent(window,"load",function(){activatePage();},false);
var isHTML = RegExp.prototype.test.bind(/(<([^>]+)>)/i);
function activatePage() {
  addselectevent();
  knob();
  if (typeof CKEDITOR != "undefined") {
    CKEDITOR.on('instanceReady', function( ev ) {
      ev.editor.on( 'paste', function( evt ) {
        if(isHTML(evt.data['html'])) {
          evt.data['html'] = '<!-- class="Mso" -->'+evt.data['html'];
        }
      }, null, null, 9);
    });
  }
}

var allowsef = /new|add|_ar|_ca/.test("new");
var allowpreview = /new|_add|_ar|/.test("");
var browser = navigator.appName;
var get_info = navigator.appVersion;
var version = parseFloat(get_info);

function genSEF(from,to) {
	if (allowsef == true) {
   	var str = from.value.toLowerCase();
   	str = str.replace(/[\xc0-\xc5\xe0-\xe5\u0100-\u0105\u0386\u0391\u03ac\u03b1\u0410\u0430\u05d0]/g,'a');
   	str = str.replace(/[\xc8-\xcb\xe8-\xeb\u0116-\u011b\u0112\u0113\u0388\u0395\u03ad\u03b5\u042d\u044d]/g,'e');
   	str = str.replace(/[\xa1\xcc-\xcf\xec-\xef\u0128-\u012b\u012e-\u0132\u013a\u0389\u038a\u0390\u0397\u0399\u03aa\u03ae\u03af\u03b7\u03b9\u03ca\u0418\u0438]/g,'i');
   	str = str.replace(/[\xd2-\xd6\xd8\xf0\xf2-\xf6\xf8\u014d\u014c\u0150\u0151\u038c\u038f\u039f\u03a9\u03bf\u03c9\u03cc\u03ce\u041e\u043e]/g,'o');
   	str = str.replace(/[\xb5\xd9-\xdc\xf9-\xfc\u0171\u0173\u0168-\u0170\u0423\u0443]/g,'u');
   	str = str.replace(/[\u0392\u03b2\u05d1]/g,'b');
   	str = str.replace(/[\xc7\xe7\u0106-\u010d\u0147\u0148\u05da\u05db]/g,'c');
   	str = str.replace(/[\xd0\u010e-\u0111\u0394\u03b4\u05d3]/g,'d');
   	str = str.replace(/[\u03a6\u03c6]/g,'f');
   	str = str.replace(/[\u011c-\u0123\u0393\u03b3\u05d2]/g,'g');
   	str = str.replace(/[\u0124-\u0127\u05d4]/g,'h');
   	str = str.replace(/[\u0134\u0135]/g,'j');
   	str = str.replace(/[\u0136\u0137\u039a\u03ba\u05d7\u05e7]/g,'k');
   	str = str.replace(/[\u0139-\u013e\u0141\u0142\u039b\u03bb\u05dc]/g,'l');
   	str = str.replace(/[\u039c\u03bc\u05dd\u05de]/g,'m');
   	str = str.replace(/[\xd1\xf1\u0143-\u0148\u039d\u03bd\u05df\u05e0]/g,'n');
   	str = str.replace(/[\u0154-\u0159\u03a1\u03c1\u05e8]/g,'r');
   	str = str.replace(/[\u03a0\u03c0\u05e3\u05e4]/g,'p');
   	str = str.replace(/[\x8a\x9a\xdf\u015a-\u0161\u03a3\u03c2\u03c3\u05e1]/g,'s');
   	str = str.replace(/[\u0162-\u0167\u021a\u021b\u03a4\u03c4\u05d8\u05ea]/g,'t');
   	str = str.replace(/[\u05d5]/g,'v');
   	str = str.replace(/[\u03be\u039e]/g,'x');
   	str = str.replace(/[\x9f\xdd\xfd\xff\u038e\u03a5\u03ab\u03b0\u03c5\u03cb\u03cd\u05d9]/g,'y');
   	str = str.replace(/[\x9e\u0179-\u017e\u0396\u03b6\u05d6]/g,'z');
   	str = str.replace(/[\u05e2]/g,'aa');
   	str = str.replace(/[\xc6\xe6]/g,'ae');
   	str = str.replace(/[\u03a7\u03c7]/g,'ch');
   	str = str.replace(/[\u039e\u03be\u0152\u0153]/g,'oe');
   	str = str.replace(/[\xde\xfe\u0398\u03b8]/g,'th');
   	str = str.replace(/[\u05e5\u05e6]/g,'ts');
   	str = str.replace(/[\u03c8\u03a8\u0398\u03b8]/g,'ps');
   	str = str.replace(/[\u05e9]/g,'sh');
   	str = str.replace(/[\xdf]/g,'sz');
   	str = str.replace(/[^a-z 0-9]+/g,'');
   	str = str.replace(/\s+/g, "-");
   	to.value = str;
  }
}
// settings home SEF restrict to alphanumeric
function SEFrestrict(x) {
  if (window.event) var key = window.event.keyCode;
  else if (x) key = x.which;
  else return true;
  var keychar = String.fromCharCode(key);
  keychar.toLowerCase();
  if (key == (null || 0 || 8 || 13 || 27) || ("abcdefghijklmnopqrstuvwxyz0123456789-_").indexOf(keychar) > -1) return true;
  if (key == ("9").indexOf(keychar) > -1) return true;	
  else return false;
}
function pop(x) {
  if (x) {
  	var agree = confirm("Waarschuwing: Verwijderen van gegevens kan niet ongedaan gemaakt worden!");
  	if (agree) return true;
  	else return false;
  } else {
  	var agree = confirm("Weet u zeker dat u dit wilt verwijderen?");
  	if (agree) return true;
  	else return false;
  }
}
function dependancy(extra) {
  var category = document.forms[0]['define_category'];
  var page = document.getElementById('def_page');
  if (extra=='extra') {
  	page.style.display = category.options[category.selectedIndex].value == '-3' ? 'inline' : 'none';
  }
}
function subparents(obj,tagName){
  if(obj) {
    var subnodes=obj.childNodes,subnode,subparents=[],n=subnodes.length;
    for(var x=0;x<n;x++) {
      subnode=subnodes[x];
      if(subnode.nodeType==1&&subnode.tagName.toLowerCase()==tagName)subparents.push(subnode);
    }
    return subparents;
  }
}
/*
function flip:
- elemid: het id van het element dat je wilt sluiten/weergeven
- standalone: alle overige onderdelen verbergen? (1 of 0)
- closing: wordt het element na de 2e klik weer gesloten? (true of false)
*/
function flip(elemid,standalone,closing) {
  if(closing!==true) closing=false;
  var elem=document.getElementById(elemid),savediv,updiv,divs=[],deform,dediv,formarray=[],t,ln;
  standalone=typeof(standalone)!=="undefined"&&standalone!=""?standalone:0;
  savediv = document.getElementById('save');
  updiv = document.getElementById('submit_pass');
  deform = document.forms[0];
  if (deform.id=='search_engine') deform = document.forms[1];
  dediv = deform.parentNode;
  formarray = subparents(dediv,"form");
  if(standalone==0) {
    divs=subparents(formarray[0].childNodes[3],"div");
    
    if(formarray.length>1) divs.push(formarray[1].childNodes[1]);
//      alert(divs[1].id);
//      alert(formarray[0].childNodes[3].id);
    ln = divs.length;
    for(t=0; t<ln; t++) {
      if(divs[t].id!=="" && divs[t].id!=="iconbar") {
        divs[t].style.display="none";
        if (formarray[0].childNodes[3].parentNode.id=="upload") formarray[0].childNodes[3].style.display="none";
      }
    }
  }
  if(elemid=="up_div") {
    savediv.style.visibility="hidden";
    updiv.style.visibility="visible";
  } else if(updiv) {
    savediv.style.visibility="visible";
    updiv.style.visibility="hidden";
  }
  if(getStyle(elemid,"display")=="block") {
  }
  elem.style.display = getStyle(elemid,"display")=="block" && closing===true ? elem.style.display="none" : elem.style.display="block";
  return;
}
/*
 * Calendar Script
 * Creates a calendar widget which can be used to select the date more easily than using just a text box
 * http://www.openjs.com/scripts/ui/calendar/
 */
calendar = {
	month_names: ["Januari","Februari","Maart","April","Mei","Juni","Juli","Augustus","September","Oktober","November","December"],
	weekdays: ["Zo", "Ma", "Di", "Wo", "Do", "Vr", "Za"],
	month_days: [31,28,31,30,31,30,31,31,30,31,30,31],
	//Get today's date - year, month, day and date
	today : new Date(),
	opt : {},
	data: [],

	//Functions
	// Used to create HTML in an optimized way.
	wrt:function(txt) {
		this.data.push(txt);
	},
	
	/* Inspired by http://www.quirksmode.org/dom/getstyles.html */
	getStyle: function(ele, property){
		if (ele.currentStyle) {
			var alt_property_name = property.replace(/\-(\w)/g,function(m,c){return c.toUpperCase();});//background-color becomes backgroundColor
			var value = ele.currentStyle[property]||ele.currentStyle[alt_property_name];
		
		} else if (window.getComputedStyle) {
			property = property.replace(/([A-Z])/g,"-$1").toLowerCase();//backgroundColor becomes background-color

			var value = document.defaultView.getComputedStyle(ele,null).getPropertyValue(property);
		}
		
		//Some properties are special cases
		if(property == "opacity" && ele.filter) value = (parseFloat( ele.filter.match(/opacity\=([^)]*)/)[1] ) / 100);
		else if(property == "width" && isNaN(value)) value = ele.clientWidth || ele.offsetWidth;
		else if(property == "height" && isNaN(value)) value = ele.clientHeight || ele.offsetHeight;
		return value;
	},
  
	getPosition:function(ele) {
		var x = 0;
		var y = 0;
		while (ele) {
			x += ele.offsetLeft;
			y += ele.offsetTop;
			ele = ele.offsetParent;
		}
		if (navigator.userAgent.indexOf("Mac") != -1 && typeof document.body.leftMargin != "undefined") {
			x += document.body.leftMargin;
			offsetTop += document.body.topMargin;
		}
	
		var xy = new Array(x,y);
		return xy;
	},

	// Called when the user clicks on a date in the calendar.
	selectDate:function(year,month,day) {
		var ths = _calendar_active_instance;
		if(ths.opt['onDateSelect']) ths.opt['onDateSelect'].apply(ths, [year,month,day]); // Custom handler if the user wants it that way.
		else {
      document.getElementById(ths.opt["input"]).value = day + "-" + month + "-" + year; // Adjust for date format
			ths.hideCalendar();
		}
	},

	// Creates a calendar with the date given in the argument as the selected date.
	makeCalendar:function(year, month, day) {
		year = parseInt(year);
		month= parseInt(month);
		day	 = parseInt(day);
		
		//Display the table
		var next_month = month+1;
		var next_month_year = year;
		if(next_month>=12) {
			next_month = 0;
			next_month_year++;
		}
		
		var previous_month = month-1;
		var previous_month_year = year;
		if(previous_month< 0) {
			previous_month = 11;
			previous_month_year--;
		}
		this.wrt("<input type='button' value='' class='calendar-cancel' onclick='calendar.hideCalendar();' />");
		this.wrt("<table cellpadding='0px' cellspacing='0px'>");
		this.wrt("<tr><th><a href='javascript:calendar.makeCalendar("+(previous_month_year)+","+(previous_month)+");' title='"+this.month_names[previous_month]+" "+(previous_month_year)+"'>&lt;</a></th>");
		this.wrt("<th colspan='5' class='calendar-title'><select name='calendar-month' class='calendar-month' onChange='calendar.makeCalendar("+year+",this.value);'>");
		for(var i in this.month_names) {
			this.wrt("<option value='"+i+"'");
			if(i == month) this.wrt(" selected='selected'");
			this.wrt(">"+this.month_names[i]+"</option>");
		}
		this.wrt("</select>&#160;");
		this.wrt("<select name='calendar-year' class='calendar-year' onChange='calendar.makeCalendar(this.value, "+month+");'>");
		var current_year = this.today.getFullYear();
		if(current_year < 1900) current_year += 1900;
		
		for(var i=current_year-3; i<current_year+5; i++) {
			this.wrt("<option value='"+i+"'")
			if(i == year) this.wrt(" selected='selected'");
			this.wrt(">"+i+"</option>");
		}
		this.wrt("</select></th>");
		this.wrt("<th><a href='javascript:calendar.makeCalendar("+(next_month_year)+","+(next_month)+");' title='"+this.month_names[next_month]+" "+(next_month_year)+"'>&gt;</a></th></tr>");
		this.wrt("<tr class='header'>");
		for(var weekday=0; weekday<7; weekday++) this.wrt("<td><span>"+this.weekdays[weekday]+"</span></td>");
		this.wrt("</tr>");
		
		//Get the first day of this month
		var first_day = new Date(year,month,1);
		var start_day = first_day.getDay();
		
		var d = 1;
		var flag = 0;
		
		//Leap year support
		if(year % 4 == 0) this.month_days[1] = 29;
		else this.month_days[1] = 28;
		
		var days_in_this_month = this.month_days[month];

		//Create the calendar
		for(var i=0;i<=5;i++) {
			if(w >= days_in_this_month) break;
			this.wrt("<tr>");
			for(var j=0;j<7;j++) {
				if(d > days_in_this_month) flag=0; //If the days has overshooted the number of days in this month, stop writing
				else if(j >= start_day && !flag) flag=1;//If the first day of this month has come, start the date writing

				if(flag) {
					var w = d, mon = month+1;
					if(w < 10)	w	= "0" + w;
					if(mon < 10)mon = "0" + mon;

					//Is it today?
					var class_name = '';
					var yea = this.today.getFullYear();
					if(yea < 1900) yea += 1900;

					if(yea == year && this.today.getMonth() == month && this.today.getDate() == d) class_name = " today";
					if(day == d) class_name += " selected";
					
					class_name += " " + this.weekdays[j].toLowerCase();

					this.wrt("<td class='days"+class_name+"'><a href='javascript:calendar.selectDate(\""+year+"\",\""+mon+"\",\""+w+"\")'>"+w+"</a></td>");
					d++;
				} else {
					this.wrt("<td class='days'>&nbsp;</td>");
				}
			}
			this.wrt("</tr>");
		}
		this.wrt("</table>");

		document.getElementById(this.opt['calendar']).innerHTML = this.data.join("");
		this.data = [];
	},
	
	/// Display the calendar - if a date exists in the input box, that will be selected in the calendar.
	showCalendar: function() {
		var input = document.getElementById(this.opt['input']);
		
		//Position the div in the correct location...
		var div = document.getElementById(this.opt['calendar']);
		
		if(this.opt['display_element']) var display_element = document.getElementById(this.opt['display_element']);
		else var display_element = document.getElementById(this.opt['input']);
		
		var xy = this.getPosition(display_element);
		var width = parseInt(this.getStyle(display_element,'width'));
		div.style.left=(xy[0]+width+10)+"px";
		div.style.top=xy[1]+"px";

		// Show the calendar with the date in the input as the selected date
		var existing_date = new Date();
		var date_in_input = input.value;
		if(date_in_input) {
			var selected_date = false;
			var date_parts = date_in_input.split("-");
			if(date_parts.length == 3) {
				date_parts[1]--; //Month starts with 0
				selected_date = new Date(date_parts[2], date_parts[1], date_parts[0]); // Adjust for date format: [0]=Year, [1]=Month, [2]=Day
			}
			if(selected_date && !isNaN(selected_date.getFullYear())) { //Valid date.
				existing_date = selected_date;
			}
		}
		
		var the_year = existing_date.getFullYear();
		if(the_year < 1900) the_year += 1900;
		this.makeCalendar(the_year, existing_date.getMonth(), existing_date.getDate());
		document.getElementById(this.opt['calendar']).style.display = "block";
		_calendar_active_instance = this;
    input.blur();
    var date_array = date_in_input.split("-");
	},
	
	/// Hides the currently show calendar.
	hideCalendar: function(instance) {
		var active_calendar_id = "";
		if(instance) active_calendar_id = instance.opt['calendar'];
		else active_calendar_id = _calendar_active_instance.opt['calendar'];
		
		if(active_calendar_id) document.getElementById(active_calendar_id).style.display = "none";
		_calendar_active_instance = {};
	},
	
	/// Setup a text input box to be a calendar box.
	set: function(input_id, opt) {
		var input = document.getElementById(input_id);
		if(!input) return; //If the input field is not there, exit.
		
		if(opt) this.opt = opt;

		if(!this.opt['calendar']) this.init();
		
		var ths = this;
		if(this.opt['onclick']) {
      input.onclick=this.opt['onclick'];
    }
		else {
			input.onclick=function(){
			ths.opt['input'] = this.id;
			ths.showCalendar();
			};
		}
	},
	
	/// Will be called once when the first input is set.
	init: function() {
		if(!this.opt['calendar'] || !document.getElementById(this.opt['calendar'])) {
			var div = document.createElement('div');
			if(!this.opt['calendar']) this.opt['calendar'] = 'calender_div_'+ Math.round(Math.random() * 100);

			div.setAttribute('id',this.opt['calendar']);
			div.className="calendar-box";

			document.getElementsByTagName("body")[0].insertBefore(div,document.getElementsByTagName("body")[0].firstChild);
		}
	}
}
function getStyle(elemId,styleProp,childNr) {
  if(elemId) {
    var elem = typeof(childNr)!="undefined" ? document.getElementById(elemId).childNodes[childNr] : document.getElementById(elemId),style;
    if (elem.currentStyle) style = elem.currentStyle[styleProp];
    else if (window.getComputedStyle) style = document.defaultView.getComputedStyle(elem,null).getPropertyValue(styleProp);
    return style;
  }
}

  function knob() {

    var knobarray=[];
    knobarray = subparents(document.getElementById('iconbar'),"button");
    if(knobarray) {
      var n=knobarray.length;
      for(var x=0; x<n; x++) {
        var knobelem = knobarray[x];
        addevent(knobelem,'focus',(function(){return function(evt){for(var y=0;y<n;y++){knobarray[y].className="";}document.getElementById(getFocus(evt)).className="knob_on";}})(),true);
      }
    }
  }


/* -----Begin select refacing------ */  
  function addselectevent() {
    var selectarray=[];
    selectarray = document.documentElement.getElementsByTagName('select');
    var n=selectarray.length;
    for(var x=0; x<n; x++) {
      var selectelem = selectarray[x];
      if (selectelem.nodeType==1 && selectelem.className!="nostyle") {
        makeselectdivs(selectelem);
        selectelem.parentNode.style.zIndex=(1200-x);
      }
    }
    window.scrollTo(0,0);
  }
  function makeselectdivs(selectelement) {
    var optiontags_array=subparents(selectelement,"option"),
      divSelectNode=document.createElement('DIV'),
      divOptionGrp=document.createElement('DIV'),
      divOptionGrpId=selectelement.id+"-refaced-options",
      aantal_opts=optiontags_array.length,
      select_class=selectelement.className,
      placeholderid=optiontags_array[0].value+"-"+selectelement.id+"-refaced";
    divSelectNode.id=selectelement.id+"-refaced";
    if(select_class.indexOf("inline")!=-1) {
      divSelectNode.className="select-refaced-inline";
    } else {
      divSelectNode.className="select-refaced";
    }

    divOptionGrp.id=divOptionGrpId;
    divOptionGrp.className="select-refaced-options";
    divSelectNode.appendChild(divOptionGrp);
    for(var idx=0; idx<aantal_opts; idx++) {
      var optiontag = optiontags_array[idx];
      var divOptionNodeId,divOptionNodeClass,divOptionSpanNodeClass,divOptionNodeText,divOptionNodetabIndex;
      if(idx==0) {
        var divOptionNode=document.createElement('DIV');
        divOptionNodeId=placeholderid+"-top";
        divOptionNodeClass="placeholder-refaced";
        divOptionSpanNodeClass="placeholder-refaced-span";
        divOptionNodeText=optiontag.innerHTML;
        divOptionNodetabIndex=selectelement.tabIndex-(aantal_opts+1);
        divSelectNode.insertBefore(divOptionNode,divOptionGrp);
        addevent(divOptionNode,"click",(function(){var optionGrpId=divOptionGrpId; return function(evt){optionGrpShow(evt);evt.preventDefault ? evt.preventDefault() : evt.returnValue = false;}})(),false);
        addevent(divOptionNode,"keydown",(function(){
          return function(evt){
            if(getChar(evt)=="40"){
              if(getStyle(divOptionGrp.id,"display")!="block") {
                optionGrpShow(evt);
              }
            }
          };
        })(), false);
        var divPlaceholder=document.createElement('SPAN');
        divPlaceholder.innerHTML=divOptionNodeText;
        divPlaceholder.className=divOptionSpanNodeClass;
        divOptionNode.appendChild(divPlaceholder);
        divOptionNode.id=divOptionNodeId;
        divOptionNode.className=divOptionNodeClass;
        divOptionNode.tabIndex=divOptionNodetabIndex;
      }
      var divOptionNode=document.createElement('DIV');
      divOptionNodeId=optiontag.value+"-"+selectelement.id+"-refaced";
      divOptionNodeClass="refaced-option";
      divOptionSpanNodeClass="refaced-option-span"
//      divOptionSpanNodeClass="refaced-option-span";
      divOptionNodeText=optiontag.innerHTML;
      divOptionNodetabIndex=divOptionNodetabIndex+1;
      divOptionGrp.appendChild(divOptionNode);
      addevent(divOptionNode,"click",(function(){var optionNodeId=divOptionNodeId; return function(evt){changeSelect(optionNodeId,evt); evt.preventDefault ? evt.preventDefault() : evt.returnValue = false;}})(),false);
      addevent(divOptionNode,"keydown",(function(){
        return function(evt){
          var sel_elemId=getFocus(evt);
          if(sel_elemId) {
            var sel_elem = document.getElementById(sel_elemId);
          } else {
            removeOptGrp();
            return false;
          }
          switch (getChar(evt)) {
            case 40: // omlaag
              if(sel_elemId) {
                if(sel_elem.nextSibling) {
                  sel_elem.nextSibling.focus();
                  if(unaccent(sel_elem.id)) accent(sel_elem.nextSibling.id);
                }
              } else return false;
            break;
            case 38: // omhoog
              if(sel_elemId) {
                if(sel_elem.previousSibling) {
                  sel_elem.previousSibling.focus();
                  if(unaccent(sel_elem.id)) accent(sel_elem.previousSibling.id);
                }
              } else return false;
            break;
            case 13: // enter / selecteren
              if(sel_elemId.toLowerCase().indexOf("placeholder")==-1) changeSelect(sel_elemId,evt);
            break;
            case 27: // escape
              if(getStyle(divOptionGrp.id,"display")=="block") {
                removeOptGrp(sel_elemId);
                document.getElementById(sel_elemId).parentNode.previousSibling.focus();
              }
            break;
            case 9: // tab
              if(getStyle(divOptionGrp.id,"display")=="block") {
                removeOptGrp();
                document.getElementById(sel_elemId).parentNode.previousSibling.focus();
              }
            break;
          }
        };
      })(), false);
      var divOptionSpanNode=document.createElement('SPAN');
      divOptionSpanNode.innerHTML=divOptionNodeText;
      divOptionSpanNode.className=divOptionSpanNodeClass;
      divOptionNode.appendChild(divOptionSpanNode);
      divOptionNode.id=divOptionNodeId;
      divOptionNode.className=divOptionNodeClass;
      divOptionNode.tabIndex=divOptionNodetabIndex;
    }
    function accent(elementid) {
      var element=document.getElementById(elementid).childNodes[0];
      if(element.tagName.toLowerCase()=="span") {
        element.className=element.className + " hilight";
        if(!element.className.indexOf(" hilight")) element.className=element.className + " hilight";
        return false;
      } else return false;
    }
    function unaccent(elementid) {
      var element=document.getElementById(elementid).childNodes[0];
      if(element.tagName.toLowerCase()=="span") {
        element.className=element.className.replace(" hilight","");
        element.style.backgroundColor="";
        element.style.color="";
        return true;
      } else return false;
    }
    selectelement.parentNode.insertBefore(divSelectNode,selectelement);  // Insert the refaced drop-down selectbox
    addevent(divSelectNode,"mouseover",(function(){var optionGrpId=divOptionGrpId; return function(evt){if(typeof makeselectdivsTimer != 'undefined') clearTimeout(makeselectdivsTimer); evt.preventDefault ? evt.preventDefault() : evt.returnValue = false;}})(),false);
    addevent(divSelectNode,"mouseout",(function(){var optionGrpId=divOptionGrpId; return function(evt){optionGrpHide(optionGrpId); evt.preventDefault ? evt.preventDefault() : evt.returnValue = false;}})(),false);
    var makeselectdivsTimer;

    function optionGrpShow(e) {
      var focused,elM;
    	if (!e) var e = window.event;
    	if (e.target) focused = e.target;
    	else if (e.srcElement) focused = e.srcElement;
    	if (focused.nodeType == 3) focused = focused.parentNode;
      if(focused.tagName.toLowerCase()!="div") {
        if(focused.tagName.toLowerCase()=="span") elM=focused.parentNode.id;
        else elM=focused.parentNode.parentNode.id;
      } else {
        if(focused.id.toLowerCase().slice(-4) == "-top") elM=focused.id;
        else elM=focused.previousSibling.id;
      }
      var optGroup=document.getElementById(elM).nextSibling; // option group
      if(typeof makeselectdivsTimer != 'undefined') clearTimeout(makeselectdivsTimer);
      var displaystyle=getStyle(optGroup.id,"display");
      if(displaystyle=="none") {
        optGroup.style.display="block";
        optGroup.previousSibling.style.borderRadius="5px 5px 0px 0px";
        optGroup.previousSibling.style.borderWidth="0px 1px 0px 0px";
        document.getElementById(elM.replace("-top","")).focus();
        accent(elM.replace("-top",""));
        
      } else {
        if (!e) var e=window.event;
        if(typeof(e)!=="undefined") if(e.type!="mouseover") removeOptGrp(elM);
      }
    }
    function removeOptGrp(phId) {
      var grp = document.getElementById(divOptionGrpId);
      var option_divs = subparents(grp,"div");
      var n=option_divs.length;
      for(var div=0; div<n; div++) {
       var od = option_divs[div].childNodes[0].className;
        if (od.indexOf("selected")!=-1 || od.indexOf("hilight")!=-1) {
          odarr = od.split(" ");
          option_divs[div].childNodes[0].className=odarr[0];
        }
      }
      grp.previousSibling.style.borderRadius="5px 5px 5px 5px";
      grp.previousSibling.style.borderWidth="0px 1px 1px 0px";
      grp.style.display="none";
      if(phId) {
        unaccent(phId);
      }
      selectdivAdaptHeight();
      grp.previousSibling.style.backgroundColor="";
    }
    function optionGrpHide(elemId) {
      makeselectdivsTimer=setTimeout(function(){removeOptGrp();},500);
    }
    function changeSelect(elemId,e) {
      if(elemId) {
        var selectedoption=elemId.replace("-"+selectelement.id+"-refaced","");
        selectelement.value=selectedoption;
var option_elements = subparents(selectelement,"option");
var option_elements_n = option_elements.length;
var oen;
for(oen=0;oen<option_elements_n;oen++) {
  option_elements[oen].removeAttribute("selected");
  if (option_elements[oen].value==selectedoption) {
    option_elements[oen].setAttribute("selected","selected");
  }
}

        var selectedDivElem=document.getElementById(elemId);
        var placeholder = selectedDivElem.parentNode.previousSibling;
        placeholder.childNodes[0].innerHTML=selectedDivElem.childNodes[0].innerHTML;
        placeholder.id = elemId + "-top";
        removeOptGrp(elemId);
        placeholder.focus();
        var placeholderSpan=placeholder.childNodes[0];
        var heightElem=parseInt(placeholderSpan.parentNode.offsetHeight);
        var heightSpan=parseInt(placeholderSpan.offsetHeight);
        var lineHeight=parseInt(getStyle(placeholderSpan.parentNode.id,"font-size",0));
        var paddingSpan=parseInt(getStyle(placeholderSpan.parentNode.id,"padding-top",0))+parseInt(getStyle(placeholderSpan.parentNode.id,"padding-bottom",0));
        if(heightSpan-paddingSpan<=(lineHeight+1)) {
          placeholderSpan.style.paddingTop="10px";
        } else {
          placeholderSpan.style.paddingTop=parseInt((heightElem)?(heightElem-lineHeight)/3.2:14)+10+"px";
        }
      }
    }
    if(selectelement.value!="0" || selectelement.id.toLowerCase().indexOf("ajax")!=-1) {
      changeSelect(selectelement.value+"-"+selectelement.id+"-refaced");
    }
    selectelement.style.visibility="hidden";
    addevent(window,"resize",selectdivAdaptHeight,false);
  }
  function getElementsByClassName(className) {
    if (document.getElementsByClassName) return document.getElementsByClassName(className);
    else return document.querySelectorAll('.' + className);
  }
  function selectdivAdaptHeight() {
    var selects = getElementsByClassName('selectbox'),n=selects.length,x=0;
    for ( x; x<n; x++ ) {
      var nodes = selects[x].childNodes, nNodes=nodes.length, nodeNr=0;
      for ( nodeNr; nodeNr<nNodes; nodeNr++ ) {
        if ( nodes[nodeNr].nodeType == 1 && nodes[nodeNr].id.toLowerCase().indexOf("refaced")!=-1 ) {
          selects[x].style.height = nodes[nodeNr].offsetHeight+"px";
        }
      }
    }
  }
/* -----Einde select refacing------ */  

  function getFocus(e) {
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
  }
  function getChar(event){
    var keyCode = ('which' in event) ? event.which : event.keyCode;
    if (keyCode=="38" || keyCode=="40" || keyCode=="13") {
      event.preventDefault();
    }
    return keyCode;
  }

  function delevent( obj, type, fn, capture ) {
    if ( obj.detachEvent ) {
      obj.detachEvent( 'on'+type, obj[type+fn] );
      obj[type+fn] = null;
    } else
      obj.removeEventListener( type, fn, false );
  }

  function addevent( obj, type, fn, capture ) {
    if(obj) {
      if ( obj.attachEvent ) {
        obj['e'+type+fn] = fn;
        obj[type+fn] = function(){obj['e'+type+fn]( window.event );}
        obj.attachEvent( 'on'+type, obj[type+fn] );
      } else
        obj.addEventListener( type, fn, false );
    }
  }
  function get_lastchild(n) {
    x=n.lastChild;
    while (x.nodeType!=1) {
      x=x.previousSibling;
    }
    return x;
  }
