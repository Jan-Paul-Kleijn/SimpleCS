/*

created by Unknow

modified by introtik

Mohammed Ahmed: maa@intro.ps

22/May/2012
*/
(function(){
  var mp4playerCmd = {exec:function(editor){editor.openDialog('mp4player');return}};
  CKEDITOR.plugins.add('mp4player',{
    lang:['nl','en'],
    requires:['dialog'],
  	 init:function(editor){
           var commandName='mp4player';
           editor.addCommand(commandName,mp4playerCmd);
       				editor.ui.addButton('Mp4Player',{label:editor.lang.mp4player.button,command:commandName,icon:this.path+"images/mp4.png"});
  				     CKEDITOR.dialog.add(commandName,CKEDITOR.getUrl(this.path+'dialogs/mp4player.js') ) 
         }
  })
})();
