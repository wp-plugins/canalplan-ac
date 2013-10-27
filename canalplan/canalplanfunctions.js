 function Canalplan_Download_Code(t,bid) {
xmlhttp=null
// code for Mozilla, etc.
if (window.XMLHttpRequest)
  {
  xmlhttp=new XMLHttpRequest()
  }
// code for IE
else if (window.ActiveXObject)
  {
  xmlhttp=new ActiveXObject("Microsoft.XMLHTTP")
  }
if (xmlhttp!=null)
  {
  xmlhttp.open("GET",wpcontent+"/canalplan-ac/canalplan/canalplan.php?place="+t+"&blogid="+bid,false)
  xmlhttp.send(false)
  xxx=xmlhttp.responseText.replace("\n","");
xxx=xxx.replace(" ","");
  }
else
  {
  alert("Your browser does not support XMLHTTP.")
  }
return(xxx);
}

function getCanalPlan(tag) {
code_id=Canalplan_Download_Code(tag,cplogid);
junk=document.getElementById("CanalPlanID");
junk.value="";
junk2=document.getElementById("tagtypeID");
junk3=document.getElementById("content");
tagcode=junk2.value;
tagextend=code_id.substring(0,1);
code_id=code_id.substring(1);
if (tagextend=="W") {tagcode=tagcode+tagextend};
if (tagextend=="F") {tagcode=tagcode+tagextend};
tinyMCE.execCommand('mceReplaceContent', false, '[['+ tagcode +':' + tag + '|' + code_id + ']]' + ' ');
// The next bit works if you are in HTML raw mode
junk3.value=junk3.value+' [['+ tagcode +':' + tag + '|' + code_id + ']]' + ' '
return;
}

function getCanalRoute(tag) {
junk2=document.getElementById("routetagtypeID");
junk3=document.getElementById("content");
tinyMCE.execCommand('mceReplaceContent', false, '[['+ junk2.value +':' + tag + ']]' + ' ');
// The next bit works if you are in HTML raw mode
junk3.value=junk3.value+' [['+ junk2.value +':' + tag + ']]' + ' '
return;
}