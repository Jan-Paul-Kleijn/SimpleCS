/*
HTML5 media selector (mp3, mp4, ogg, etc.)
For use with filemanager
Created by Jan-Paul Kleijn
31/01/2017
*/

(function(){
  CKEDITOR.dialog.add('mp4player',function(editor) {
    return {
      title:editor.lang.mp4player.title,
      minWidth:CKEDITOR.env.ie&&CKEDITOR.env.quirks?568:550,
      minHeight:350,
      onShow:function(){
        this.getContentElement('general','mp4File').setValue('')
      },
      onOk:function(){
        function insertMarkup(url, callback, dialog) {
          var req = new XMLHttpRequest;
          req.open("GET", url, true);
          req.onreadystatechange = function() {
            if(req.readyState == req.HEADERS_RECEIVED) {
              callback(null, req.getResponseHeader("Content-Type"), url, dialog);
            }
          };
          req.send(null);
        }
        function insertIt(error,type,u,d) { // Callback function for insertMarkup()
          mediaType = type.substring(0,type.indexOf('/'));
          if(mediaType == 'audio') {
            elem = CKEDITOR.dom.element.createFromHtml( "<div class=\"mediaback\"><p>omschrijving</p><audio controls=\"controls\" src=\"/"+u+"\" preload=\"auto\">Your browser unfortunately does not support html5 audio.</audio></div>" );
          } else if(mediaType == 'video') {
            elem = CKEDITOR.dom.element.createFromHtml( "<div class=\"mediaback\"><p>omschrijving</p><video controls=\"controls\" src=\"/"+u+"\" type=\""+type+"\">Your browser unfortunately does not support html5 video.</video></div>" );
          } else elem = false;
          if(elem) d.insertElement(elem);
        }
        val = this.getContentElement('general','mp4File').getValue(); // Value opvragen van form elem ('tabblad','elemId')
        insertMarkup(val, insertIt, this.getParentEditor());
      },
     	contents:[
        {
          label: editor.lang.common.generalTab,
          id:'general',
          elements: [
            {
              type:'html',
              id:'pasteMsg',
              html:'<div style="white-space:normal;width:auto;text-align:center;margin:20px 0px"><img src="'+CKEDITOR.getUrl(CKEDITOR.plugins.getPath('mp4player')+'images/mp4_large.png')+'" /></div>'+editor.lang.mp4player.pasteMsg
            },
            {
              type:'text',
              id:'mp4File',
        						class:'textinput_browse',
              onLoad:function(){
                this.getInputElement().setAttribute( 'readOnly', true );
              },
              onChange:function(){
                url = this.getValue();
                if(url.indexOf(location.protocol)!=-1) {
                  relUrl = url.replace(/^.*\/\/[^\/]+\//, '');
                  this.setValue(relUrl);
                }
              }
            },
            {
        						type:'button',
        						id:'browse',
        						class:'button_browse',
        						align:'center',
        						label:editor.lang.common.browseServer,
        						hidden:true,
              filebrowser: {
                action:'Browse',
                target:'general:mp4File'
              }
            }
          ]
        }
      ]
    }
  }
)})();
