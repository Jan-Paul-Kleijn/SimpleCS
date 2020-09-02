/*
Artistry plugin
For use with filemanager
Created by Jan-Paul Kleijn
17/03/2017
*/

(function(){
  var artistryCmd = {exec:function(editor){editor.openDialog('artistry');return}};
  CKEDITOR.plugins.add('artistry',{
    lang:['nl','en'],
    requires:['dialog'],
  	 init:function(editor){
           var commandName='artistry';
           editor.addCommand(commandName,artistryCmd);
       				editor.ui.addButton('artistry',{label:editor.lang.artistry.button,command:commandName,icon:this.path+"images/artistry.png"});
  				     CKEDITOR.dialog.add(commandName,CKEDITOR.getUrl(this.path+'dialogs/artistry.js'))
         }
  })
})();
