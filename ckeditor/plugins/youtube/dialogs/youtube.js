(function(){CKEDITOR.dialog.add('youtube',function(editor){return{title:editor.lang.youtube.title,minWidth:CKEDITOR.env.ie&&CKEDITOR.env.quirks?368:350,minHeight:200,onShow:function(){this.getContentElement('general','content').getInputElement().setValue('')},onOk:function(){val=this.getContentElement('general','content').getInputElement().getValue();val=val.replace("watch\?v\=", "v\/");var text='<div><div class="youtubeMovie" id="'+val+'" tabindex="6" title="4_3"></div></div><p></p>';this.getParentEditor().insertHtml(text)},contents:[{label:editor.lang.common.generalTab,id:'general',elements:[{type:'html',id:'pasteMsg',html:'<div style="white-space:normal;width:auto"><img style="margin:5px auto;" src="'+CKEDITOR.getUrl(CKEDITOR.plugins.getPath('youtube')+'images/youtube_large.png')+'"><br />'+editor.lang.youtube.pasteMsg+'</div>'},{type:'html',id:'content',style:'width:340px;height:240px',html:'<input size="100" style="'+'border:1px solid black;'+'background:white">',focus:function(){this.getElement().focus()}}]}]}})})();
