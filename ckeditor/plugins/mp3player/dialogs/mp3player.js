/*

created by Unknow

modified by introtik

Mohammed Ahmed: maa@intro.ps

22/May/2012
*/

(function(){CKEDITOR.dialog.add('mp3player',
	function(editor) {
    return {
      title:editor.lang.mp3player.title,
      minWidth:CKEDITOR.env.ie&&CKEDITOR.env.quirks?568:550,
      minHeight:350,
      onShow:function(){this.getContentElement('general','content').getInputElement().setValue('')},
      onOk:
        function(){
          val = this.getContentElement('general','content').getInputElement().getValue();
          var text='<div class="mediaback"><audio src="userfiles/mp3/'+escape(val)+'" preload="auto" /></div>';
          this.getParentEditor().insertHtml(text)
        },
      contents: [
          {
            label: editor.lang.common.generalTab,
            id: 'general',
            elements: [
              {
                type:'html',
                id:'pasteMsg',
                html:'<div style="white-space:normal;width:auto;text-align:center;margin-bottom:20px"><img src="'+CKEDITOR.getUrl(CKEDITOR.plugins.getPath('mp3player')+'images/mp3_large.png')+'" /></div>'+editor.lang.mp3player.pasteMsg
              },
              {
                type:'html',
                id:'content',
                style:'width:auto;height:auto',
                html:'<input placeholder="'+editor.lang.mp3player.placeholder+'" style="height:1.4em;padding: 4px 6px; border:1px solid #cccccc; margin: 0px 10px 0px 0px; background:white;float:left;width:68%">'+editor.lang.mp3player.uploadBtn
              }
            ]
          }
        ]
      }
    }

//  }

)})();
