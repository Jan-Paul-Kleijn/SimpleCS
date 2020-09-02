/*
Artistry plugin
For use with filemanager
Created by Jan-Paul Kleijn
17/03/2017
*/

(function(){
  CKEDITOR.dialog.add('artistry',function(editor) {
    return {
      title:editor.lang.mp4player.title,
      minWidth:CKEDITOR.env.ie&&CKEDITOR.env.quirks?568:550,
      minHeight:450,
      onShow:function(){
        this.getContentElement('artwork','art').setValue('')
      },
      onOk:function(){
        function insertMarkup(data, callback, dialog) {
          var req = new XMLHttpRequest;
          req.open("GET", data[0], true);
          req.onreadystatechange = function() {
            if(req.readyState == req.HEADERS_RECEIVED) {
              callback(null, req.getResponseHeader("Content-Type"), data, dialog);
            }
          };
          req.send(null);
        }
        function insertIt(error,type,data,d) { // Callback function for insertMarkup()
          mediaType = type.substring(0,type.indexOf('/'));
          if(mediaType == 'image') {
            elem1 = CKEDITOR.dom.element.createFromHtml( "<div id=\"dim\"></div>" );
            elem2 = CKEDITOR.dom.element.createFromHtml( "<div id=\"imageHolder\"><div class=\"mask\"><img id=\"srcimg\" src=\"/"+data[0]+"\" /></div><div id=\"imageInfo\"><h1>"+data[1]+"</h1><ul><li><strong>"+editor.lang.artistry.artSize+"</strong><span>"+data[2]+" x "+data[3]+" cm</span></li><li><strong>"+editor.lang.artistry.artMaterials+"</strong><span>"+data[4]+"</span></li><li><strong>"+editor.lang.artistry.artCreationDate+"</strong><span>"+data[5]+"</span></li><li><strong>"+editor.lang.artistry.artOfCollection+"</strong><span>"+(data[6]!==''?data[6]:'-')+"</span></li></div></div>" );
            elem3 = CKEDITOR.dom.element.createFromHtml( "<div id=\"canvasHolder\"><canvas id=\"canvas\"></canvas></div>" );
            d.insertElement(elem1);
            d.insertElement(elem2);
            d.insertElement(elem3);
          }
        }
        ob1 = this.getContentElement('artwork','art').getValue();
        ob2 = this.getContentElement('artdescr','artTitle').getValue();
        ob3 = this.getContentElement('artdescr','artSizeWidth').getValue();
        ob4 = this.getContentElement('artdescr','artSizeHeight').getValue();
        ob5 = this.getContentElement('artdescr','artMaterials').getValue();
        ob6 = this.getContentElement('artdescr','artCreationDate').getValue();
        ob7 = this.getContentElement('artdescr','artOfCollection').getValue();

        html = new Array(ob1,ob2,ob3,ob4,ob5,ob6,ob7);
        insertMarkup(html, insertIt, this.getParentEditor());
      },
     	contents:[
        {
          label: editor.lang.artistry.artdescrTab,
          id:'artdescr',
          elements: [
            {
              type:'html',
              id:'artdescrMsg',
              html:'<p style="white-space:normal;width:auto;text-align:left;margin:20px 0px">'+editor.lang.artistry.artdescrMsg+'</p>'
            },
            {
              type:'text',
              id:'artTitle',
              label: editor.lang.artistry.artTitle+' *',
              validate:function(){
                if(!this.getValue()){
                  alert(editor.lang.artistry.alertEmptyInput+': '+editor.lang.artistry.artTitle);
                  return false;
                }
              },
        						style:'textinput'
            },
            {
              type:'text',
              id:'artSizeWidth',
              label: editor.lang.artistry.artSizeWidth+' *',
              validate:function(){
                if(!this.getValue()){
                  alert(editor.lang.artistry.alertEmptyInput+': '+editor.lang.artistry.artSizeWidth);
                  return false;
                }
              },
        						style:'textinput'
            },
            {
              type:'text',
              id:'artSizeHeight',
              label: editor.lang.artistry.artSizeHeight+' *',
              validate:function(){
                if(!this.getValue()){
                  alert(editor.lang.artistry.alertEmptyInput+': '+editor.lang.artistry.artSizeHeight);
                  return false;
                }
              },
        						style:'textinput'
            },
            {
              type:'text',
              id:'artMaterials',
              label: editor.lang.artistry.artMaterials+' *',
              validate:function(){
                if(!this.getValue()){
                  alert(editor.lang.artistry.alertEmptyInput+': '+editor.lang.artistry.artMaterials);
                  return false;
                }
              },
        						style:'textinput'
            },
            {
              type:'text',
              id:'artCreationDate',
              label: editor.lang.artistry.artCreationDate,
        						style:'textinput'
            },
            {
              type:'text',
              id:'artOfCollection',
              label: editor.lang.artistry.artOfCollection,
        						style:'textinput'
            }
          ]
        },
        {
          label: editor.lang.artistry.artworkTab,
          id:'artwork',
          elements: [
            {
              type:'html',
              id:'artworkMsg',
              html:'<p style="white-space:normal;width:auto;text-align:left;margin:20px 0px">'+editor.lang.artistry.artworkMsg+'</p>'
            },
            {
              type:'text',
              id:'art',
        						style:'textinput_browse',
              onLoad:function(){
                this.getInputElement('artwork:art').setAttribute( 'readOnly', true );
              },
              onChange:function(){
                url = this.getValue();
                if(url.indexOf(location.protocol)!=-1) {
                  relUrl = url.replace(/^.*\/\/[^\/]+\//, '');
                  this.setValue(relUrl);
                }
              },
              validate:function(){
                if(!this.getValue()){
                  alert('Name cannot be empty.');
                  return false;
                }
              }
            },
            {
        						type:'button',
        						id:'browse',
        						style:'button_browse',
        						align:'center',
        						label:editor.lang.common.browseServer,
        						hidden:true,
              filebrowser: {
                action:'Browse',
                target:'artwork:art'
              }
            }
          ]
        }
      ]
    }
  }
)})();
