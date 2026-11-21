<?php
/** Adminer - Compact database management
* @link https://www.adminer.org/
* @author Jakub Vrana, https://www.vrana.cz/
* @copyright 2007 Jakub Vrana
* @license https://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
* @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License, version 2 (one or other)
* @version 6.3.1
*/namespace
Adminer;if(isset($_GET["status"]))$_GET["variables"]=$_GET["status"];if(isset($_GET["import"]))$_GET["sql"]=$_GET["import"];const
VERSION="6.3.1";error_reporting(24575);set_error_handler(function($_d,$Dd){return!!preg_match('~^Undefined (array key|offset|index)~',$Dd);},E_WARNING|E_NOTICE);if(!preg_match('~^(unsafe_raw)?$~',ini_get("filter.default"))||ini_get("filter.default_flags")){foreach(['_GET','_POST','_COOKIE','_SERVER']as$X){$Jn=filter_input_array(constant("INPUT$X"),FILTER_UNSAFE_RAW);if($Jn)$$X=$Jn;}}$_COOKIE=array_filter($_COOKIE,'is_scalar');if(function_exists("mb_internal_encoding"))mb_internal_encoding("8bit");function
connection($h=null){return($h?:Db::$instance);}function
adminer(){return
Adminer::$instance;}function
driver(){return
Driver::$instance;}function
connect(){$kc=adminer()->credentials();$I=Driver::connect($kc[0],$kc[1],$kc[2]);return(is_object($I)?$I:null);}function
idf_unescape($u){if(!preg_match('~^[`\'"[]~',$u))return$u;$xg=substr($u,-1);return
str_replace($xg.$xg,$xg,substr($u,1,-1));}function
q($Q){return
connection()->quote($Q);}function
idx($Ea,$x,$l=null){return($Ea&&array_key_exists($x,$Ea)?$Ea[$x]:$l);}function
number($X){return
preg_replace('~[^0-9]+~','',$X);}function
int_type(){return'(tiny|small|medium|big)?int(eger|\d)?';}function
number_type(){return'(^('.int_type().'|decimal|numeric|number|real|(binary_|half_|scaled_)?float\d?|(binary_)?double( precision)?|(small)?money)$)';}function
text_type(){return'char|text'.(JUSH=="sql"?'|enum|set':'');}function
is_user_type($U){return
in_array($U,idx(driver()->structuredTypes(),'User types',[]));}function
full_type_sql(array$n){$U=$n["type"];return(is_user_type($U)?idf_escape($U).substr($n["full_type"],strlen($U)):$n["full_type"]);}function
is_searchable(array$n,array$X){if(!isset($n["privileges"]["where"]))return
false;if(preg_match('~NULL$~',$X["op"]))return
true;$U=$n["type"];$dl=$X["val"];$Xa='binary$|bytea|raw|image|bfile|^vector$'.(JUSH=="mssql"?'|^timestamp$':'|^bit').(JUSH=="oracle"?'|^blob|^long|rowid':'');if(preg_match("~$Xa~",$U))return
false;if(preg_match(number_type(),$U)){$hi='-?\d+(\.\d+)?';$Bh='';if(preg_match('~IN$~',$X["op"])){$dl=preg_replace('~^\s*\(\s*(.*?)\s*\)\s*$~s','\1',$dl);$Bh="( *, *$hi)*";}elseif($X["op"]=="BETWEEN")$Bh="\\s+AND\\s+$hi";return(bool)preg_match('~^'.$hi.$Bh.'$~i',$dl);}if(preg_match('~^(small)?date|^timestamp~',$U))return(bool)preg_match('~^\d+-\d+-\d+~',$dl);if(preg_match('~^time~',$U))return(bool)preg_match('~^\d+:\d+~',$dl);if(preg_match('~^bool~',$U)||(JUSH=="mssql"&&$U=="bit"))return(bool)preg_match('~^(t|f|true|false|[01])$~i',$dl);return
true;}function
bracket_escape($u,$Qa=false){static$ln=[':'=>':1',']'=>':2','['=>':3','"'=>':4','='=>':5'];return
strtr($u,($Qa?array_flip($ln):$ln));}function
url_escape($Q){static$ln=[];if(!$ln){$ln=[' '=>'+'];foreach(str_split("\"'<>#%&+=?".ini_get("arg_separator.input"))as$ob)$ln[$ob]=sprintf('%%%02X',ord($ob));for($s=0;$s<256;$s++){if($s<32||$s>126)$ln[chr($s)]=sprintf('%%%02X',$s);}}return
strtr((string)$Q,$ln);}function
min_version($lo,$Sg="",$h=null){$h=connection($h);$Bl=$h->server_info;if($Sg&&preg_match('~([\d.]+)-MariaDB~',$Bl,$A)){$Bl=$A[1];$lo=$Sg;}return$lo&&version_compare($Bl,$lo)>=0;}function
charset(Db$g){return(min_version("5.5.3",0,$g)?"utf8mb4":"utf8");}function
ini_set($Fi,$Y){return(function_exists('ini_set')?\ini_set($Fi,$Y):false);}function
ini_bool($Nf){$X=ini_get($Nf);return(preg_match('~^(on|true|yes)$~i',$X)||(int)$X);}function
ini_bytes($Nf){$X=ini_get($Nf);switch(strtolower(substr($X,-1))){case'g':$X=(int)$X*1024;case'm':$X=(int)$X*1024;case'k':$X=(int)$X*1024;}return$X;}function
max_input_vars($J,$Xi){$Wg=(int)ini_get("max_input_vars");return($Wg?(int)floor(($Wg-$Xi)/$J):0);}function
max_input_vars_error(){$Nf="max_input_vars";return
sprintf('Maximum number of allowed fields exceeded. Please increase %s.',"<b>$Nf = ".ini_get($Nf)."</b>");}function
sid(){static$I;if($I===null)$I=(SID&&!($_COOKIE&&ini_bool("session.use_cookies")));return$I;}function
set_password($ko,$N,$V,$E){if(is_string($E)&&!$_COOKIE["adminer_key"]){$hj=session_get_cookie_params();$_COOKIE["adminer_key"]=rand_string();cookie("adminer_key",$_COOKIE["adminer_key"],$hj["lifetime"]);}$_SESSION["pwds"][$ko][$N][$V]=(is_string($E)?[encrypt_string($E,$_COOKIE["adminer_key"])]:$E);}function
get_password(){$I=get_session("pwds");if(is_array($I))$I=($_COOKIE["adminer_key"]?decrypt_string($I[0],$_COOKIE["adminer_key"]):false);return$I;}function
get_val($G,$n=0,$Sb=null){$Sb=connection($Sb);$H=$Sb->query($G);if(!is_object($H))return
false;$J=$H->fetch_row();return($J?$J[$n]:false);}function
get_vals($G,$d=0){$I=[];$H=connection()->query($G);if(is_object($H)){while($J=$H->fetch_row())$I[]=$J[$d];}return$I;}function
get_key_vals($G,$h=null,$Fl=true){$h=connection($h);$I=[];$H=$h->query($G);if(is_object($H)){while($J=$H->fetch_row()){if($Fl)$I[$J[0]]=$J[1];else$I[]=$J[0];}}return$I;}function
get_rows($G,$h=null,$m="<p class='error'>"){$Sb=connection($h);$I=[];$H=$Sb->query($G);if(is_object($H)){while($J=$H->fetch_assoc())$I[]=$J;}elseif(!$H&&!$h&&$m&&(defined('Adminer\PAGE_HEADER')||$m=="-- "))echo$m.adminer()->error()."\n";return$I;}function
unique_array($J,array$w){foreach($w
as$v){if(preg_match("~^(PRIMARY|UNIQUE)$~",$v["type"])&&!$v["partial"]&&!$v["invalid"]){$I=[];foreach($v["columns"]as$x){if(!isset($J[$x]))continue
2;$I[$x]=$J[$x];}return$I;}}}function
where_function($Fe,$d,array$n){if($Fe=="md5")return
driver()->md5($d,$n)?:$d;return(in_array($Fe,driver()->functions)||in_array($Fe,driver()->grouping)?apply_sql_function($Fe,$d):$d);}function
where(array$Z,array$o=[]){$I=[];foreach((array)$Z["where"]as$x=>$X){$x=bracket_escape($x,true);$d=idf_escape($x);$n=idx($o,$x,[]);$ce=$n["type"];$ag=$n&&(is_blob($n)||preg_match('~binary~',$ce));$I[]=$d.($ag&&!is_utf8($X)?" = ".driver()->quoteBinary($X):(JUSH=="sql"&&$ce=="json"?" = CAST(".q($X)." AS JSON)":(JUSH=="pgsql"&&preg_match('~^jsonb?$~',$n["full_type"])?"::jsonb = ".q($X)."::jsonb":(JUSH=="pgsql"&&preg_match('~\.~',$ce)?"::text = ".q($X):(JUSH=="sql"&&is_numeric($X)&&preg_match('~\.~',$X)?" LIKE ".q($X):(JUSH=="mssql"&&strpos($ce,"datetime")===false?" LIKE ".q(preg_replace('~[_%[]~','[\0]',$X)):" = ".unconvert_field($n,q($X))))))));if(JUSH=="sql"&&preg_match('~char|text~',$ce)&&preg_match("~[^ -@]~",$X))$I[]="$d = ".q($X)." COLLATE ".charset(connection())."_bin";}foreach((array)$Z["null"]as$x)$I[]=idf_escape($x)." IS NULL";foreach((array)$Z["col"]as$s=>$Db){$X=idx($Z["val"],$s);$I[]=where_function(idx($Z["fun"],$s),idf_escape($Db),idx($o,$Db,[])).($X!==null?" = ".q($X):" IS NULL");}return
implode(" AND ",$I);}function
where_columns(array$o){$I=[];foreach((array)$_GET["null"]as$x)$I[$x]=true;foreach(array_keys((array)$_GET["where"])as$x)$I[bracket_escape($x,true)]=true;foreach((array)$_GET["col"]as$Db)$I[$Db]=true;return
array_intersect_key($I,$o);}function
where_check($X,array$o=[]){parse_str($X,$rb);return
where($rb,$o);}function
where_link($s,$d,$Y,$Ci="="){$_i=($Y!==null?$Ci:"IS NULL");return"&where[$s][col]=".url_escape($d).($_i!=first(adminer()->operators())?"&where[$s][op]=".url_escape($_i):"")."&where[$s][val]=".url_escape($Y);}function
convert_fields(array$e,array$o,array$M=[]){$I="";foreach($e
as$x=>$X){if($M&&!in_array(idf_escape($x),$M))continue;$Fa=convert_field($o[$x]);if($Fa)$I
.=", $Fa AS ".idf_escape($x);}return$I;}function
cookie_path(){return
strtr(preg_replace('~\?.*~','',$_SERVER["REQUEST_URI"]),[";"=>"%3B",","=>"%2C"]);}function
cookie($B,$Y,$Gg=2592000){header("Set-Cookie: $B=".rawurlencode($Y).($Gg?"; expires=".gmdate("D, d M Y H:i:s",time()+$Gg)." GMT":"")."; path=".cookie_path().(HTTPS?"; secure":"").($B=="adminer_import"?"":"; HttpOnly")."; SameSite=lax",false);}function
php_error_text($m){return(ini_bool("html_errors")?html_entity_decode(strip_tags($m)):$m);}function
php_errors($jb){$Cd=[];set_error_handler(function($_d,$m)use(&$Cd){$Cd[]=preg_replace('~^\w+\([^)]*\)( \[[^]]*])?:\s*~','',php_error_text($m));return
true;},E_WARNING|E_NOTICE);$jb();restore_error_handler();return$Cd;}function
get_url($Sn,$bc){$Cd=php_errors(function()use($Sn,$bc,&$I,&$ef){$http_response_header=null;$I=file_get_contents($Sn,false,$bc);$ef=(function_exists('http_get_last_response_headers')?http_get_last_response_headers():$http_response_header);});return[$I,(preg_match('~^HTTP/[\d.]+ (\d+)~',idx($ef,0,''),$A)?$A[1]:''),(array)$ef,($I===false?implode("\n",$Cd):''),];}function
json_decode_exact($ig){$ig=str_replace(['\\\\','\\"'],['\\u005c','\\u0022'],$ig);$ig=preg_replace('~"(\\\\u0001[^"]*+")|"[^"]*+"(*SKIP)(*FAIL)~','"\\\\u0001$1',$ig);return
json_decode(preg_replace('~"[^"]*+"(*SKIP)(*FAIL)|-?\d[-+.\deE]*+~','"\\\\u0001$0"',$ig));}function
json_scalar($X){return(is_string($X)&&substr($X,0,1)=="\1"?substr($X,1):$X);}function
json_encode_exact($X,$oe=0){$ig=str_replace(['\\\\','\\"'],["\2","\3"],json_encode($X,$oe));$ig=preg_replace('~"\\\\u0001(-?\d[^"\\\\]*)"|(")\\\\u0001(\\\\u0001[^"]*+")|"[^"]*+"(*SKIP)(*FAIL)~','$1$2$3',$ig);return
str_replace(["\2","\3"],['\\\\','\\"'],$ig);}function
get_settings($fc){parse_str($_COOKIE[$fc],$Gl);return$Gl;}function
get_setting($x,$fc="adminer_settings",$l=null){return
idx(get_settings($fc),$x,$l);}function
save_settings(array$Gl,$fc="adminer_settings"){$Y=http_build_query($Gl+get_settings($fc));cookie($fc,$Y);$_COOKIE[$fc]=$Y;}function
restart_session(){if(!ini_bool("session.use_cookies")&&session_status()==PHP_SESSION_NONE)session_start();}function
stop_session($re=false){$Vn=ini_bool("session.use_cookies");if(!$Vn||$re){session_write_close();if($Vn&&ini_set("session.use_cookies",'0')===false)session_start();}}function&get_session($x){return$_SESSION[$x][DRIVER][SERVER][$_GET["username"]];}function
set_session($x,$X){$_SESSION[$x][DRIVER][SERVER][$_GET["username"]]=$X;}function
auth_url($ko,$N,$V,$k=null){$Rn=remove_from_uri(implode("|",array_keys(SqlDriver::$drivers))."|username|ext|".($k!==null?"db|":"").($ko=='mssql'||$ko=='pgsql'?"":"ns|").session_name());preg_match('~([^?]*)\??(.*)~',$Rn,$A);return"$A[1]?".(sid()?SID."&":"").($_GET["ext"]?"ext=".url_escape($_GET["ext"])."&":"").($ko!="server"||$N!=""?url_escape($ko)."=".url_escape($N)."&":"")."username=".url_escape($V).($k!=""?"&db=".url_escape($k):"").($A[2]?"&$A[2]":"");}function
is_ajax(){return($_SERVER["HTTP_X_REQUESTED_WITH"]=="XMLHttpRequest");}function
redirect($Og,$mh=null,$P=303){if($mh!==null){restart_session();$_SESSION["messages"][preg_replace('~^[^?]*~','',($Og!==null?$Og:$_SERVER["REQUEST_URI"]))][]=$mh;}if($Og!==null){if($Og=="")$Og=".";header("Location: $Og",true,$P);exit;}}function
query_redirect($G,$Og,$mh,$rk=true,$Kd=true,$Vd=false,$Xm=""){if($Kd){$fm=microtime(true);$Vd=!connection()->query($G);$Xm=format_time($fm);}$Vl=($G?adminer()->messageQuery($G,$Xm,$Vd):"");if($Vd){adminer()->error
.=adminer()->error().$Vl.script("messagesPrint();")."<br>";return
false;}if($rk)redirect($Og,$mh.$Vl);return
true;}class
Queries{static$queries=[];static$start=0;}function
remember_query($G){if(!Queries::$start)Queries::$start=microtime(true);Queries::$queries[]=(driver()->delimiter!=';'?$G:(preg_match('~;$~',$G)?"DELIMITER ;;\n$G;\nDELIMITER ":$G).";");}function
queries($G){remember_query($G);return
connection()->query($G);}function
apply_queries($G,array$T,$Ed='Adminer\table'){foreach($T
as$R){if(!queries("$G ".$Ed($R)))return
false;}return
true;}function
queries_redirect($Og,$mh,$rk){$ik=implode("\n",Queries::$queries);$Xm=format_time(Queries::$start);return
query_redirect($ik,$Og,$mh,$rk,false,!$rk,$Xm);}function
format_time($fm){return
sprintf('%.3f s',max(0,microtime(true)-$fm));}function
relative_uri($Rn=''){return
preg_replace_callback('~^[^?]*~',function($A){return
str_replace(":","%3A",$A[0]);},preg_replace('~^[^?]*/([^?]*)~','\1',($Rn?:$_SERVER["REQUEST_URI"])));}function
remove_from_uri($fj=""){return
substr(preg_replace("~(?<=[?&])($fj".(SID?"":"|".session_name()).")=[^&]*&~",'',relative_uri()."&"),0,-1);}function
get_files($B,$zc=false){$ee=$_FILES[$B];if(!$ee)return
null;foreach($ee
as$x=>$X)$ee[$x]=(array)$X;$I=[];foreach($ee["error"]as$x=>$m){if($m)return$m;$p=$ee["name"][$x];$fn=$ee["tmp_name"][$x];$Zb=file_get_contents($zc&&preg_match('~\.gz$~',$p)?"compress.zlib://$fn":$fn);if($zc){$fm=substr($Zb,0,3);if(function_exists("iconv")&&preg_match("~^\xFE\xFF|^\xFF\xFE~",$fm))$Zb=iconv("utf-16","utf-8",$Zb);elseif($fm=="\xEF\xBB\xBF")$Zb=substr($Zb,3);}$I[]=[$p,$Zb];}return$I;}function
get_file($x,$zc=false,$Gc=""){$he=get_files($x,$zc);if(!is_array($he))return$he;$I='';foreach($he
as$ee){$Zb=$ee[1];$I
.=$Zb;if($Gc)$I
.=(preg_match("($Gc\\s*\$)",$Zb)?"":$Gc)."\n\n";}return$I;}function
upload_error($m){$eh=($m==UPLOAD_ERR_INI_SIZE?ini_get("upload_max_filesize"):0);return($m?'Unable to upload a file.'.($eh?" ".sprintf('Maximum allowed file size is %sB.',$eh):""):'File does not exist.');}function
is_utf8($X){return(preg_match('~~u',$X)&&!preg_match('~[\0-\x8\xB\xC\xE-\x1F]~',$X));}function
utf8_length($X){return
strlen(preg_replace('~[\x80-\xBF]~','',$X));}function
format_number($X){preg_match('~^#+([^#0]+)(?:(#+)\1)?(#*0)$~u','#,##0',$A);$Kl=strlen($A[3]);$I=number_format($X,0,".","");$I=preg_replace('~\B(?=(\d{'.(strlen($A[2])?:$Kl).'})*\d{'.$Kl.'}$)~',$A[1],$I);return
strtr($I,preg_split('~~u','0123456789',-1,PREG_SPLIT_NO_EMPTY));}function
format_status(array$S,$x){$X=idx($S,$x,'?');if(!is_numeric($X))return
h($X);if($X<0)return'?';$Aa=($x=="Rows"&&(JUSH=="sqlite"||$S["Engine"]==(JUSH=="pgsql"?"table":"InnoDB")));$X=in_array($x,["Data_length","Index_length","Data_free"])?adminer()->formatSizeValue($X):format_number($X);return($Aa?"~ ":"").$X;}function
friendly_url($X){return
preg_replace('~\W~i','-',$X);}function
table_status1($R,$Wd=false){$I=table_status($R,$Wd);return($I?reset($I):["Name"=>$R]);}function
column_foreign_keys($R){$I=[];foreach(adminer()->foreignKeys($R)as$q){foreach($q["source"]as$X)$I[$X][]=$q;}return$I;}function
fields_from_edit(){$I=[];foreach((array)$_POST["field_keys"]as$x=>$X){if($X!=""){$X=bracket_escape($X);$_POST["function"][$X]=$_POST["field_funs"][$x];$_POST["fields"][$X]=$_POST["field_vals"][$x];}}foreach((array)$_POST["fields"]as$x=>$X){$B=bracket_escape($x,true);$I[$B]=["field"=>$B,"full_type"=>"","type"=>"","privileges"=>["insert"=>1,"update"=>1,"where"=>1,"order"=>1],"null"=>true,"auto_increment"=>($B==driver()->primary),];}return$I;}function
dump_headers($sf,$Fh=false){$I=adminer()->dumpHeaders($sf,$Fh);$Zi=$_POST["output"];if($Zi!="text"||$I=="tar"){$Ob=($Zi!="text"&&$Zi!="file"&&preg_match('~^[0-9a-z]+$~',$Zi)?".$Zi":"");header("Content-Disposition: attachment; filename=".adminer()->dumpFilename($sf).".$I$Ob");}session_write_close();if(!ob_get_level())ob_start(null,4096);ob_flush();flush();return$I;}function
dump_csv(array$J){$xn=$_POST["format"]=="tsv";foreach($J
as$x=>$X){if(preg_match('~["\n]|^0[^.]|\.\d*0$|'.($xn?'\t':'[,;]|^$').'~',$X))$J[$x]='"'.str_replace('"','""',$X).'"';}echo
implode(($_POST["format"]=="csv"?",":($xn?"\t":";")),$J)."\r\n";}function
parse_csv($nc,$ql){$I=[];preg_match_all('~(?>"[^"]*"|[^"\r\n]+)+~',$nc,$Ug);foreach($Ug[0]as$J){preg_match_all("~((?>\"[^\"]*\")+|[^$ql]*)$ql~",$J.$ql,$Vg);$I[]=$Vg[1];}return$I;}function
csv_value($X){return(preg_match('~^".*"$~s',$X)?idf_unescape($X):$X);}function
apply_sql_function($r,$d){return($r?($r=="unixepoch"?"DATETIME($d, '$r')":($r=="count distinct"?"COUNT(DISTINCT ":strtoupper("$r("))."$d)"):$d);}function
get_temp_dir(){return
ini_get("upload_tmp_dir")?:sys_get_temp_dir();}function
file_open_lock($p){if(is_link($p))return;$ye=@fopen($p,"c+");if(!$ye)return;@chmod($p,0660);if(!flock($ye,LOCK_EX)){fclose($ye);return;}return$ye;}function
file_write_unlock($ye,$rc){rewind($ye);fwrite($ye,$rc);ftruncate($ye,strlen($rc));file_unlock($ye);}function
file_unlock($ye){flock($ye,LOCK_UN);fclose($ye);}function
first(array$Ea){return
reset($Ea);}function
password_file($i){$p=get_temp_dir()."/adminer.key";if(!$i&&!file_exists($p))return'';$ye=file_open_lock($p);if(!$ye)return'';$I=stream_get_contents($ye);if(!$I){$I=rand_string();file_write_unlock($ye,$I);}else
file_unlock($ye);return$I;}function
rand_string(){return(function_exists('random_bytes')?bin2hex(random_bytes(16)):md5(uniqid(strval(mt_rand()),true)));}function
select_value($X,$_,array$n,$Vm,array$Aj=[]){if(is_array($X)){$I="";if(array_filter($X,'is_array')==array_values($X)){$og=[];foreach($X
as$W)$og+=array_fill_keys(array_keys($W),null);foreach(array_keys($og)as$lg)$I
.="<th>".h($lg);foreach($X
as$W){$I
.="<tr>";foreach(array_merge($og,$W)as$do)$I
.="<td>".select_value($do,$_,$n,$Vm,$Aj);}}else{foreach($X
as$lg=>$W)$I
.="<tr>".($X!=array_values($X)?"<th>".h($lg):"")."<td>".select_value($W,$_,$n,$Vm,$Aj);}return"<table>$I</table>";}if(!$_)$_=adminer()->selectLink($X,$n);if($_===null){if(is_mail($X))$_="mailto:$X";if(is_url($X))$_=$X;}$X=driver()->value($X,$n);$I=adminer()->editVal($X,$n);if($I!==null){if(!is_utf8($I))$I="\0";elseif($Vm!=""&&is_shortable($n))$I=shorten_utf8($I,max(0,+$Vm),"",$Aj);else$I=highlight_matches($I,$Aj);}return
adminer()->selectVal($I,$_,$n,$X);}function
is_blob(array$n){return
preg_match('~blob|bytea|raw|file'.(JUSH=="mssql"?'|binary|image':'').'~',$n["type"])&&!in_array($n["type"],idx(driver()->structuredTypes(),'User types',[]));}function
is_identity_always(array$n){return$n["auto_increment"]&&(JUSH=="mssql"||$n["default"]=="GENERATED ALWAYS AS IDENTITY");}function
is_mail($pd){$Ha='[-a-z0-9!#$%&\'*+/=?^_`{|}~]';$Zc='[a-z0-9]([-a-z0-9]{0,61}[a-z0-9])';$_j="$Ha+(\\.$Ha+)*@($Zc?\\.)+$Zc";return
is_string($pd)&&preg_match("(^$_j(,\\s*$_j)*\$)i",$pd);}function
is_url($Q){$Zc='[a-z0-9]([-a-z0-9]{0,61}[a-z0-9])';return
preg_match("~^((https?):)?//($Zc?\\.)+$Zc(:\\d+)?(/\\S*)?(\\?\\S*)?(#\\S*)?\$~iD",$Q);}function
is_ipv6($na){$Me='[\da-f]{1,4}';$Zf='\d{1,3}(\.\d{1,3}){3}';return(bool)preg_match("~^(($Me:){7}$Me|($Me:){6}$Zf|(($Me:)*$Me)?::(($Me:)*($Me|$Zf))?)$~iD",$na);}function
is_shortable(array$n){return!preg_match('~'.number_type().'|date|time|year~',$n["type"]);}function
url_host($of){return(strpos($of,":")!==false?"[$of]":$of);}function
server_parts(array$uj){return["scheme"=>(string)$uj["scheme"],"host"=>(string)$uj["host"],"port"=>(string)$uj["port"],"socket"=>(string)$uj["socket"],"path"=>(string)$uj["path"],];}function
parse_server($N){if($N=="")return
server_parts([]);if($N[0]==":"&&!is_ipv6($N)){$Ek=substr($N,1);if(preg_match('~^\d+$~D',$Ek))return
server_parts(["port"=>$Ek]);return(preg_match('~^/[-\w.:/]*$~D',$Ek)?server_parts(["socket"=>$Ek]):null);}$bl="";if(preg_match('~^([-+.\w]+)://~',$N,$A)){$bl=strtolower($A[1]);$N=substr($N,strlen($A[0]));}if(preg_match('~^\[(.+)](:(\d+))?(/[-\w./]*)?$~D',$N,$A))return(is_ipv6($A[1])?server_parts(["scheme"=>$bl,"host"=>$A[1],"port"=>$A[3],"path"=>$A[4]]):null);if(is_ipv6($N))return
server_parts(["scheme"=>$bl,"host"=>$N]);if(preg_match('~^(/[-\w./]*)(:(\d+))?$~D',$N,$A))return
server_parts(["scheme"=>$bl,"host"=>$A[1],"port"=>$A[3]]);return(preg_match('~^([-\w.]*)(:(\d+))?(/[-\w./]*)?$~D',$N,$A)?server_parts(["scheme"=>$bl,"host"=>$A[1],"port"=>$A[3],"path"=>$A[4]]):null);}function
count_rows($R,array$Z,$bg,array$Me){$G=" FROM ".table($R).($Z?" WHERE ".implode(" AND ",$Z):"");return($bg&&(JUSH=="sql"||count($Me)==1)?"SELECT COUNT(DISTINCT ".implode(", ",$Me).")$G":"SELECT COUNT(*)".($bg?" FROM (SELECT 1$G GROUP BY ".implode(", ",$Me).") x":$G));}function
slow_query($G){$Ml=driver()->slowQuery($G,adminer()->queryTimeout());ob_flush();flush();return@get_key_vals(($Ml?:$G),null,false);}function
get_token(){$ok=rand_string();return
bin2hex($ok^"$_SESSION[token]").":$ok";}function
verify_token(){list($gn,$ok)=explode(":",$_POST["token"]);$fl=$_SESSION["token"];return
is_string($fl)&&strlen($ok)==strlen($fl)&&bin2hex($ok^$fl)===$gn&&in_array($_SERVER["HTTP_SEC_FETCH_SITE"],["","same-origin"]);}function
compress_alphabet(){return
strtr(implode(range('"','~')),"'\\","!\n");}function
decompress_string($Q,$Nc=""){$ya=array_flip(str_split(compress_alphabet()));$y=strlen($Q);$go=($y?13*($y-1)/2-$ya[$Q[0]]:0);$Xa="";$Ek=0;$Fk=0;for($s=1;$s<$y;$s+=2){$Ek=($Ek<<13)+$ya[$Q[$s]]*93+$ya[$Q[$s+1]];$Fk+=13;while($Fk>=8&&$go>=8){$Fk-=8;$go-=8;$Xa
.=chr($Ek>>$Fk);$Ek&=(1<<$Fk)-1;}}if($Xa=="")return"";if($Nc!=""&&function_exists('inflate_init'))return
inflate_add(inflate_init(ZLIB_ENCODING_RAW,['dictionary'=>$Nc]),$Xa,ZLIB_FINISH);return($Nc==""&&function_exists('gzinflate')?gzinflate($Xa):inflate($Xa,$Nc));}function
inflate($Xa,$Nc=""){$Dg=[3,4,5,6,7,8,9,10,11,13,15,17,19,23,27,31,35,43,51,59,67,83,99,115,131,163,195,227,258];$Eg=[0,0,0,0,0,0,0,0,1,1,1,1,2,2,2,2,3,3,3,3,4,4,4,4,5,5,5,5,0];$Rc=[1,2,3,4,5,7,9,13,17,25,33,49,65,97,129,193,257,385,513,769,1025,1537,2049,3073,4097,6145,8193,12289,16385,24577];$Tc=[0,0,0,0,1,1,2,2,3,3,4,4,5,5,6,6,7,7,8,8,9,9,10,10,11,11,12,12,13,13];$I=$Nc;$F=0;do{$je=inflate_bits($Xa,$F,1);$U=inflate_bits($Xa,$F,2);if(!$U){$F=($F+7)&~7;$y=inflate_bits($Xa,$F,16);$F+=16;$I
.=substr($Xa,$F>>3,$y);$F+=$y<<3;}else{if($U==1){$Mg=array_merge(array_fill(0,144,8),array_fill(0,112,9),array_fill(0,24,7),array_fill(0,8,8));$Uc=array_fill(0,30,5);}else{$Lg=inflate_bits($Xa,$F,5)+257;$Sc=inflate_bits($Xa,$F,5)+1;$Ki=[16,17,18,0,8,7,9,6,10,5,11,4,12,3,13,2,14,1,15];$th=array_fill(0,19,0);$sh=inflate_bits($Xa,$F,4)+4;for($s=0;$s<$sh;$s++)$th[$Ki[$s]]=inflate_bits($Xa,$F,3);$uh=inflate_table($th);$Fg=[];while(count($Fg)<$Lg+$Sc){$um=inflate_symbol($Xa,$F,$uh);if($um==16)$Fg=array_merge($Fg,array_fill(0,inflate_bits($Xa,$F,2)+3,end($Fg)));elseif($um==17)$Fg=array_merge($Fg,array_fill(0,inflate_bits($Xa,$F,3)+3,0));elseif($um==18)$Fg=array_merge($Fg,array_fill(0,inflate_bits($Xa,$F,7)+11,0));else$Fg[]=$um;}$Mg=array_slice($Fg,0,$Lg);$Uc=array_slice($Fg,$Lg);}$Ng=inflate_table($Mg);$Wc=inflate_table($Uc);while(($um=inflate_symbol($Xa,$F,$Ng))!=256){if($um<256)$I
.=chr($um);else{$y=$Dg[$um-257]+inflate_bits($Xa,$F,$Eg[$um-257]);$Vc=inflate_symbol($Xa,$F,$Wc);$C=strlen($I)-$Rc[$Vc]-inflate_bits($Xa,$F,$Tc[$Vc]);for($s=0;$s<$y;$s++)$I
.=$I[$C+$s];}}}}while(!$je);return($Nc==""?$I:substr($I,strlen($Nc)));}function
inflate_bits($Xa,&$F,$hc){$I=0;for($s=0;$s<$hc;$s++){$I+=((ord($Xa[$F>>3])>>($F&7))&1)<<$s;$F++;}return$I;}function
inflate_table(array$Fg){$R=[];$Cb=0;for($Ya=1;$Ya<=max($Fg);$Ya++){foreach($Fg
as$um=>$y){if($y==$Ya){$R[$Ya][$Cb]=$um;$Cb++;}}$Cb<<=1;}return$R;}function
inflate_symbol($Xa,&$F,array$R){$Cb=0;$Ya=0;do{$Cb=($Cb<<1)+inflate_bits($Xa,$F,1);$Ya++;}while(!isset($R[$Ya][$Cb]));return$R[$Ya][$Cb];}function
script($Rl,$kn="\n"){return"<script".nonce().">$Rl</script>$kn";}function
script_src($Sn,$Cc=false){return"<script src='".h($Sn)."'".nonce().($Cc?" defer":"")."></script>\n";}function
nonce(){return' nonce="'.get_nonce().'"';}function
on($Fd,$Ve,$Ba=null){$Da=[];foreach(array_slice(func_get_args(),2)as$X)$Da[]=json_encode($X,JSON_UNESCAPED_UNICODE);return" data-on$Fd='".str_replace(['&','<',"'"],['&amp;','&lt;','&#039;'],"$Ve(".implode(", ",$Da).")")."'";}function
input_hidden($B,$Y=""){return"<input type='hidden' name='".h($B)."' value='".h($Y)."'>\n";}function
input_token(){return
input_hidden("token",get_token());}function
target_blank(){return' target="_blank" rel="noreferrer noopener"';}function
h($Q){return
str_replace(['&','<','"',"'","\0"],['&amp;','&lt;','&quot;','&#039;','&#0;'],$Q);}function
nl_br($Q){return
str_replace("\n","<br>",$Q);}function
checkbox($B,$Y,$ub,$sg="",$c="",$_b="",$ug=""){$I="<input type='checkbox' name='".h($B)."' value='".h($Y)."'".($ub?" checked":"").($sg==""&&$_b?" class='$_b'":"").($ug?" aria-labelledby='$ug'":"").$c.">";return($sg!=""?"<label".($_b?" class='$_b'":"").">$I".h($sg)."</label>":$I);}function
optionlist($Hi,$ml=null,$Wn=false){$I="";foreach($Hi
as$lg=>$W){$Ji=[$lg=>$W];if(is_array($W)){$I
.='<optgroup label="'.h($lg).'">';$Ji=$W;}foreach($Ji
as$x=>$X)$I
.='<option'.($Wn||is_string($x)?' value="'.h($x).'"':'').($ml!==null&&($Wn||is_string($x)?(string)$x:$X)===$ml?' selected':'').'>'.h($X);if(is_array($W))$I
.='</optgroup>';}return$I;}function
group_system(array$Nh,$al=false){$I=[];$vm=[];foreach($Nh
as$B){if($al?driver()->isSystem(DB,$B):driver()->isSystem($B))$vm[]=$B;else$I[]=$B;}if($vm)$I[sprintf('System%s','')]=$vm;return$I;}function
html_select($B,array$Hi,$Y="",$c="",$ug=""){static$sg=0;$tg="";if(!$ug&&substr($Hi[""],0,1)=="("){$sg++;$ug="label-$sg";$tg="<option value='' id='$ug'>".h($Hi[""]);unset($Hi[""]);}return"<select name='".h($B)."'".($ug?" aria-labelledby='$ug'":"")."$c>".$tg.optionlist($Hi,$Y)."</select>";}function
html_radios($B,array$Hi,$Y="",$ql=""){$I="";foreach($Hi
as$x=>$X)$I
.="<label><input type='radio' name='".h($B)."' value='".h($x)."'".($x==$Y?" checked":"").">".h($X)."</label>$ql";return$I;}function
confirm($mh=""){return
on('click','confirmClick',$mh?:'Are you sure?');}function
print_fieldset($t,$Cg,$oo=false){echo"<fieldset><legend>","<a href='#fieldset-$t' class='toggle'>$Cg</a>","</legend>","<div id='fieldset-$t'".($oo?"":" class='hidden'").">\n";}function
bold($Za,$_b=""){return($Za?" class='active $_b'":($_b?" class='$_b'":""));}function
js_escape($Q){return
str_replace("<","\\x3C",addcslashes($Q,"\r\n'\\"));}function
js_escape_re($Q){return
addcslashes(preg_quote($Q,"/"),"\r\n");}function
pagination_href($D){return
remove_from_uri("page|next").($D?"&page=$D".($_GET["next"]!=""?"&next=".url_escape($_GET["next"]):""):"");}function
pagination($D,$oc){return" ".($D==$oc?($D?"<b>".($D+1)."</b>":$D+1):'<a href="'.h(pagination_href($D)).'">'.($D+1)."</a>");}function
hidden_fields(array$bk,array$xf=[],$Qj=''){$I=false;foreach($bk
as$x=>$X){if(!in_array($x,$xf)){if(is_array($X))hidden_fields($X,[],$x);else{$I=true;echo
input_hidden(($Qj?$Qj."[$x]":$x),$X);}}}return$I;}function
hidden_fields_get(){echo(sid()?input_hidden(session_name(),session_id()):''),($_GET["ext"]?input_hidden("ext",$_GET["ext"]):""),(isset($_GET[DRIVER])?input_hidden(DRIVER,SERVER):""),input_hidden("username",$_GET["username"]);}function
on_upload_progress(&$Qn){$Qn=(ini_bool("session.upload_progress.enabled")&&ini_get("session.upload_progress.name")?rand_string():"");return($Qn?on('submit','uploadProgress',ME."upload=$Qn",SESSION_NAME."=$Qn"):"");}function
file_input($c,$Ek=""){$Yg="max_file_uploads";$Zg=ini_get($Yg);$eh="upload_max_filesize";$fh=ini_bytes($eh);$Mj=ini_bytes("post_max_size");if($Mj&&$Mj<$fh){$eh="post_max_size";$fh=$Mj;}$gh=ini_get($eh);return(ini_bool("file_uploads")?"<input type='file'$c".on('change','fileChange',(int)$Zg,sprintf('Increase %s.',"$Yg = $Zg"),$fh,sprintf('Increase %s.',"$eh = $gh")).">$Ek":'File uploads are disabled.');}function
enum_input($U,$c,array$n,$Y,$sd=""){preg_match_all("~".driver()->enumLength."~",$n["length"],$Ug);$Qj=($n["type"]=="enum"?"val-":"");$ub=(is_array($Y)?in_array("null",$Y):$Y===null);$I=($n["null"]&&$Qj?"<label><input type='$U'$c value='null'".($ub?" checked":"")."><i>$sd</i></label>":"");foreach($Ug[0]as$X){$X=stripcslashes(idf_unescape($X));$ub=(is_array($Y)?in_array($Qj.$X,$Y):$Y===$X);$I
.=" <label><input type='$U'$c value='".h($Qj.$X)."'".($ub?' checked':'').'>'.h(adminer()->editVal($X,$n)).'</label>';}return$I;}function
input(array$n,$Y,$r,$Oa=false,$Nn=false){$B=h(bracket_escape($n["field"]));echo"<td class='function'>";$zd=driver()->enumLength($n);if($zd){$n["type"]="enum";$n["length"]=$zd;}$Hi=($n["type"]=="enum"||$n["type"]=="set");if(is_array($Y)&&!$r&&!$Hi)$r="json";$ig=($r=="json"||preg_match('~^jsonb?$~',$n["full_type"]));if($ig&&$Y!=''&&(JUSH!="pgsql"||$n["type"]!="json")&&(is_array($Y)||!$_POST["save"])){$oe=JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE;$Y=(is_array($Y)?json_encode($Y,$oe):json_encode_exact(json_decode_exact($Y),$oe));}$Dk=($Nn&&is_identity_always($n));if($Dk&&!$_POST["save"])$r=null;$Ge=(isset($_GET["select"])||$Dk?["orig"=>'original']:[])+adminer()->editFunctions($n);$c=" name='fields[$B]".($Hi?"[]":"")."'".($Oa?" autofocus":"");echo
driver()->unconvertFunction($n)." ";$R=$_GET["edit"]?:$_GET["select"];if($n["type"]=="enum")echo
h($Ge[""])."<td>".adminer()->editInput($R,$n,$c,$Y);else{$Xe=(in_array($r,$Ge)||isset($Ge[$r]));$ke=0;foreach($Ge
as$x=>$X){if($x===""||!$X)break;$ke++;}echo(count($Ge)>1?"<select name='function[$B]'".on('change','functionChange').on_help_value('^SQL$').">".optionlist($Ge,$r===null||$Xe?$r:"")."</select>":h(reset($Ge)))."<td".($ke&&count($Ge)>1?on('input','skipOriginal',$ke):"").">";$Pf=adminer()->editInput($R,$n,$c,$Y);if($Pf!="")echo$Pf;elseif(preg_match('~bool~',$n["type"]))echo"<input type='hidden'$c value='0'>"."<input type='checkbox'".(preg_match('~^(1|t|true|y|yes|on)$~i',$Y)?" checked":"")."$c value='1'>";elseif($n["type"]=="set")echo
enum_input("checkbox",$c,$n,(is_string($Y)?explode(",",$Y):$Y));elseif(is_blob($n)&&ini_bool("file_uploads"))echo"<input type='file' name='fields-$B'>";elseif($ig)echo"<textarea$c cols='50' rows='12' class='jush-json'>\n".h($Y).'</textarea>';elseif(($Um=preg_match('~text|lob|memo~i',$n["type"]))||preg_match("~\n~",$Y)){if($Um&&JUSH!="sqlite")$c
.=" cols='50' rows='12'";else{$K=min(12,substr_count($Y,"\n")+1);$c
.=" cols='30' rows='$K'";}echo"<textarea$c>\n".h($Y).'</textarea>';}else{$Bn=driver()->types();$_n=$Bn[$n["type"]];$Ea=preg_match('~\[]~',$n["full_type"]);if($Ea)$hh=0;elseif(preg_match('~date|time|year~',$n["type"])){$Pj=($n["length"]==""&&JUSH=="pgsql"?6:$n["length"]);$ze=(preg_match('~time~',$n["type"])&&preg_match('~^[1-9]\d*$~',$Pj)?$Pj+1:0);$hh=($_n?$_n+$ze:0);}elseif(!preg_match('~int|vector~',$n["type"])&&preg_match('~^(\d+)(,(\d+))?$~',$n["length"],$A))$hh=(preg_match("~binary~",$n["type"])?2:1)*$A[1]+($A[3]?1:0)+($A[2]&&!$n["unsigned"]?1:0);else$hh=($_n?$_n+($n["unsigned"]?0:1):0);echo"<input".((!$Xe||$r==="")&&preg_match('~^'.int_type().'$~',$n["type"])&&!$Ea?" type='number'":"")." value='".h($Y)."'".($hh?" data-maxlength='$hh'":"").(preg_match('~char|binary~',$n["type"])&&$hh>20?" size='".($hh>99?60:40)."'":"")."$c>";}echo
adminer()->editHint($R,$n,$Y),(count($Ge)>1?script("fire(qs('select', qsl('td').previousSibling), 'change');",""):"");}}function
process_input(array$n){$u=bracket_escape($n["field"]);$r=idx($_POST["function"],$u);if($r=="orig")return(preg_match('~^CURRENT_TIMESTAMP~i',$n["on_update"])?idf_escape($n["field"]):false);if($r=="NULL")return"NULL";if(is_blob($n)&&ini_bool("file_uploads")){$ee=get_file("fields-$u");if(!is_string($ee))return
false;return(JUSH=="sql"?"_binary".q($ee):driver()->quoteBinary($ee));}$Y=idx($_POST["fields"],$u);if($Y===null){if(!isset($_FILES["fields"]["name"][$u]))return
false;$Y="";}if($n["type"]=="enum"||driver()->enumLength($n)){$Y=idx($Y,0);if($Y=="orig"||!$Y)return
false;if($Y=="null")return"NULL";$Y=substr($Y,4);}if($n["auto_increment"]&&$Y=="")return
null;if($n["type"]=="set")$Y=implode(",",(array)$Y);if($r=="json"){$Y=json_decode($Y,true);if(!is_array($Y))return
false;return$Y;}return
adminer()->processInput($n,$Y,$r);}function
jump_input(){$vg=['select'=>'select','insert'=>'insert','search'=>(support("table")?'Search data in tables':''),'schema'=>'Schema','database'=>'Database',];$I="<span id='jump'><input type='search' form='jump-form' name='filter' value='".h($_GET["filter"])."'"." placeholder='".'Search'."' aria-label='".'Search'."' autocomplete='off' autocapitalize='off'"." role='combobox' aria-expanded='false' aria-autocomplete='list' aria-controls='jump-list'".on('input','jumpInput').on('keydown','jumpKeydown');foreach($vg
as$x=>$X)$I
.=($X!=""?" data-$x='".h($X)."'":"");return"$I><input type='submit' form='jump-form' value='".'Search'."' class='hidden'></span> ";}function
search_tables($G,$_i=""){$_GET["where"]=[["op"=>$_i,"val"=>$G]];$pl="<ul>\n";foreach(table_status('',true)as$R=>$S){$B=adminer()->tableName($S);if(isset($S["Engine"])&&$B!=""&&(!$_POST["tables"]||in_array($R,$_POST["tables"]))){$H=connection()->query("SELECT".limit("1 FROM ".table($R)," WHERE ".implode(" AND ",adminer()->selectSearchProcess(fields($R),[],$S)),1));if(!$H||$H->fetch_row()){$Xj="<a href='".h(ME."select=".url_escape($R)."&where[0][op]=".url_escape($_GET["where"][0]["op"])."&where[0][val]=".url_escape($_GET["where"][0]["val"]))."'>$B</a>";echo"$pl<li>".($H?$Xj:"<p class='error'>$Xj: ".adminer()->error())."\n";$pl="";}}}echo($pl?"<p class='message'>".'No tables.':"</ul>")."\n";}function
on_help($Um,$Jl=0){return
on('mouseover','helpMouseover',$Um,$Jl).on('mouseout','helpMouseout');}function
on_help_value($yk="",$Ck=""){return
on('mouseover','helpValueMouseover',$yk,$Ck).on('mouseout','helpMouseout');}function
edit_form($R,array$o,$J,$Nn,$m='',$G='',$Xm=''){$Cm=adminer()->tableName(table_status1($R,true));page_header(($Nn?'Edit':'Insert'),$m,["select"=>[$R,$Cm]],$Cm);adminer()->editRowPrint($R,$o,$J,$Nn,$G,$Xm);if($J===false){echo"<p class='error'>".'No rows.'."\n";return;}echo"<form method='post' enctype='multipart/form-data' id='form'>\n";$nd=false;$vo=($Nn&&!isset($_GET["select"])?where_columns($o):[]);$cc=(count($vo)!=count($o));if(!$cc)$vo=[];if(!$o)echo"<p class='error'>".'You have no privileges to update this table.'."\n";else{echo"<table class='layout nowrap'".on('keydown','editingKeydown').">\n";$Oa=!$_POST;foreach($o
as$B=>$n){echo"<tr".($vo[$B]?on('change','whereChange'):"")."><th>".adminer()->fieldName($n);$l=idx($_GET["set"],bracket_escape($B));if($l===null){$l=$n["default"];if($n["type"]=="bit"&&preg_match("~^b'([01]*)'\$~",$l,$_k))$l=$_k[1];if(JUSH=="sql"&&preg_match('~binary~',$n["type"]))$l=bin2hex($l);}$Y=($J!==null?($n["type"]=="set"&&is_array($J[$B])?implode(",",$J[$B]):(is_bool($J[$B])?+$J[$B]:$J[$B])):(!$Nn&&$n["auto_increment"]?"":(isset($_GET["select"])?false:$l)));if(!$_POST["save"]&&is_string($Y))$Y=adminer()->editVal($Y,$n);if(($Nn&&!isset($n["privileges"]["update"]))||$n["generated"])echo"<td class='function'><td>".select_value($Y,'',$n,null);else{$nd=true;$r=($_POST["save"]?idx($_POST["function"],bracket_escape($B),""):($Nn&&preg_match('~^CURRENT_TIMESTAMP~i',$n["on_update"])?"now":($Y===false?null:($Y!==null?'':'NULL'))));if(!$_POST&&!$Nn&&$Y==$n["default"]&&($n["default_sql"]||preg_match('~^[\w.]+\(~',$Y)))$r="SQL";if(preg_match("~time~",$n["type"])&&preg_match('~^CURRENT_TIMESTAMP~i',$Y)){$Y="";$r=(in_array("now",adminer()->editFunctions($n))?"now":"current_timestamp");}if(preg_match("~date~",$n["type"])&&preg_match('~^CURRENT_DATE~i',$Y)){$Y="";$r=(in_array("now",adminer()->editFunctions($n))?"now":"current_date");}if($n["type"]=="uuid"&&$Y=="uuid()"){$Y="";$r="uuid";}if($Oa!==false)$Oa=($n["auto_increment"]||$r=="now"||$r=="uuid"?null:true);input($n,$Y,$r,$Oa,$Nn);if($Oa)$Oa=false;}}if(driver()->primary!=""&&!fields($R))echo"<tr>"."<th><input name='field_keys[]'".on('input','fieldChange').">"."<td class='function'>".html_select("field_funs[]",adminer()->editFunctions(["null"=>isset($_GET["select"])]))."<td><input name='field_vals[]'>";echo"</table>\n";}echo"<p>\n";if($nd){echo"<input type='submit' value='".'Save'."'>\n";if(!isset($_GET["select"])&&$cc){$Oc=($vo&&($m!=""||adminer()->error!="")?" disabled":"");echo"<input type='submit' name='insert' value='".($Nn?'Save and continue editing':'Save and insert next')."' title='Ctrl+Shift+Enter'$Oc".($Nn?on('click','ajaxForm','Saving…'):"").">\n";}}echo($Nn?"<input type='submit' name='delete' value='".'Delete'."'".confirm().">\n":"");if(isset($_GET["select"]))hidden_fields(["check"=>(array)$_POST["check"],"clone"=>$_POST["clone"],"all"=>$_POST["all"]]);echo
input_hidden("referer",(isset($_POST["referer"])?$_POST["referer"]:$_SERVER["HTTP_REFERER"])),input_hidden("save",1),input_token(),"</form>\n";}function
repeat_pattern($_j,$y){return
str_repeat("$_j{0,65535}",$y/65535)."$_j{0,".($y%65535)."}";}function
shorten_utf8($Q,$y=80,$pm="",array$Aj=[]){if(!preg_match("(^(".repeat_pattern("[\t\r\n -\x{10FFFF}]",$y).")($)?)u",$Q,$A))preg_match("(^(".repeat_pattern("[\t\r\n -~]",$y).")($)?)",$Q,$A);$y=strlen(isset($A[2])?$A[1]:preg_replace('~\n[^\n]*\z~',"\n",$A[1]));return
highlight_matches($Q,$Aj,$y).$pm.(isset($A[2])?"":"<i>…</i>");}function
highlight_matches($Q,array$Aj,$y=null){if($y===null)$y=strlen($Q);$I="";$F=0;if($Aj&&@preg_match_all("((?|".implode("|",$Aj)."))su",$Q,$Ug,PREG_OFFSET_CAPTURE)){foreach($Ug[0]as$A){list($Um,$fm)=$A;if($Um!=""&&$fm<$y){$ud=min($fm+strlen($Um),$y);$I
.=h(substr($Q,$F,$fm-$F))."<mark>".h(substr($Q,$fm,$ud-$fm))."</mark>";$F=$ud;}}}return$I.h(substr($Q,$F,$y-$F));}function
icon($rf,$B,$qf,$an,$c=""){return"<button ".($B?"type='submit' name='$B'":"draggable='true' tabindex='-1'")." title='".h($an)."' class='icon icon-$rf".($B?"":" jsonly")."'$c><span>$qf</span></button>";}function
copy_icon(){$gc='Copy';return"<a href='' class='jsonly icon-copy' title='$gc'><span>$gc</span></a>";}if(isset($_GET["file"])){if($_SERVER["HTTP_IF_MODIFIED_SINCE"]){header("HTTP/1.1 304 Not Modified");exit;}header("Expires: ".gmdate("D, d M Y H:i:s",time()+365*24*60*60)." GMT");header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");header("Cache-Control: immutable");ini_set("zlib.output_compression",'1');if($_GET["file"]=="default.css"){header("Content-Type: text/css; charset=utf-8");echo
decompress_string(')c(GViDWPFloQuDeeQUO^DgIF+y?4O<cX<jDcJA*[iuQ/YeF96jOuG0x[iu&n>{/Lt(peel""twnUSV4BjfRQHIaBSj#}4}^YKK<$BQMFP!+g0;Oaei]~TuoHWgJWsnlo=Q<zp
uKaG7hs1<!+Oi==O^}->/9Ea]~3synPV<Bm<c_L,Wm<:hI3>D]aslZ$SmG>H4Jg&]_G3uM_JP:Uxi;FAxIn_^J<D92mCE[jOq`wQHqqX[abvndl6
BuYT?K{gCgwCMO.[Bh>G.8O8j4cIh5,JaCXgWkoIQ!Xe%"4xCJ:#SsY.cQ)wF)[oAV@<X0jd2+]KMoD[>B4-v8{,,5LHUM34%n.ryR|:FR{0:aICtMI@=)$E^(E*:+o^~E%/JJVY<6`t<36)7mKyhI2:4Q%)Fo6^*oXn"r:NoSCF7=8WtpI91EqD&q]%mr`%zJG0Us@Lmi0lzD?Jm_.!%96.
05-6$9gJ5ax@d2@W#%4dOJ)dKrIhH<fII!d{G[[I[ajMju&sY5,h-_prPbj1%#EWXF1Gq.K+lD:sy4U_,<r&5^8e0eNI+ZclG?lfYe9UG!7:$;LSenK}0zA}Hr"r6Hk&7{RDK%2"Lqg8d<iC84Z1HzadoTQIv^Nm637v.T[>ekSylqlA2,Sd0UOW[GOnj]?I(rr3D?<fp#f77<J++3-&%V.~ZnmNJH[y]`-^0uG@G4iId}ogQQE
<?w%giV}f@vlXb
MvCbX8f:=d3Ax]qKEp-m^PAQ60knO9)+,(K`(k_*pRZtYK2oEe>T<T19
8/KE0QRqMQ2Zi{y|s_*Z*3nf*SKkX~pV^ZNE&eF+E=*;AA"3>99[b}$CN/W3C*?dXP.!6E:7&
8DN`6p1]rtfE4<_
f(P[Ux2ry0nf<PY]XTU^Ll=8vSj8kmWn;8oo8dfJYdbKvrr6d%L0F6vmh%?=RDtJ>od.Kf_;-K&lem4.[s6-U8?_qZ!30>e
#,<5F60=.DmmVj*!6Y,vLqPc2443S*-I32v==XWfqK[TF2if,l72YBe~P:GUq|
X/rlx#"KguG#c7PC`orer$85!$5Tj8oa_"6XJgGlS7}9bUBF
:+9e"$IUt8G/YUY$<R%(D.F*RHQ:R>7?7T
:;`qB5t:65dN2oNk|;n3M^iUQN&@e-?9zZ?.b`%JIj8_J)
`cMhjz
][wofYiC
gdGrH?eQrkjyw-N|Be##pH#<Lc>6W5y#!tpHi439wyIApL)uX2<QNy,BPQ4}75%o0I.;0i;.J%(pu9KQ^{&h)*yu9S-q@n7`rr$s$qf78+R|D,B"/R"Sd})z=(Ed>Q,IpF&r#&g"P}1G<Cq&*"[f?fU85I0e::"@.fa;1W7q(QoXN">i)rx,jnB>]4-}`5EFEN4C2Z&IW"dxGF(]u[$EwudHf#X>!zsbDN31mN7/?E[{/U:1;Zw/e{YysExxYZM6eH,KIC1x72,.aQ"rHSs99nJ%d
Nf7^n6N:mxZXNKlGB4T*hRa*>nL-ZO^pl/$W=|^uQr`Pd"B;
5Ztg*$}E[pbUg5NY@=se`iV;,T0NVF7p,3`HFXRCH"j%tYQJFf~)h-uKw4BIUbI,&<}AFHP4ApY.r;;
0m?fQ4<mpL)wya|rpAEHX6"EK.xg.u:X]QZ:I>Q:_e*oVAtEIG8Tw]6#ura,4q,yjoUK{2x2Q<s8V!8YX_QZgJJVf!wdV]qWh?6^^48=msu1g(B%jO@Dv/krFZ<gQ(h$6BBa%/]=1Os^zfTZMn684",4rZmBm6f-)1Q!p%D6-azWWSqrT0PgSB90R<.2nK~x
@oln1hq7O+Aq%HS^!j31w6P.*!P1Fuez2Hg2F7[wTUAMI[!7daU^NVgGj,(*sud9I%PQ:?as)ZeRjm"7P!3F7)7cfrnHqB]TUQn&*~?yBd/f*H:J]~6,#9nu3646kl/VY2-F"pVVvb/4Lp@yY>^%1i#[
R#?q]:E,DDTcKxEsah{FO5ql6##D.^U
9lP7cB+#dtG9=_ce&gubW4L;gi_v{,[Bu/ze2=6C3f0v;.gnQ+=Cqy>Wu7aS3m`=/9eotIE"WigNR6W[xiMjqP#7g)xry3KMzUQILw~5o"/"fVz]8&i4~"H@}!v^cIlk>nf(j00GV!c%w0u3N[XQ
4FCk-&%,qxg03ekJnCA/fJ*AGxq_6O8@)];9={1=b&UTTX$uBrk%#QoM:r&huE`/FzQL%NbKRzbMiAQN1Ty)!IWWFXDRh#D$91F4
aj>R#u`<W4t1Gle.GCy3hYOE$`:H]m4pBN@#9c?>~nU
@7-Ts2bRIB}*[NjBCVhMH5h.+HLiA,7hUq{H!:=di6gt-W.qJ_sHC5xy5P%LOiv>bQJ<Z?HnK;:0#8c.~brLL`#,5i"u]n~o<#llm)#vxUI2%yZZJhA<;wp.UB3bs3?6)>2ZI%qQndQ
T]5gzW2-n?gm@"p)H<w^@Fw?@2[KO<H@NPr9x6J2WkpFe#_:`sS;
GXbLGn+r)`Eo4ey<#;J%XTCwn0,>=p[>U_a"J;kW/pDY/_x_#)w-v7s`C$?Nlw!-H=ZqKs
`:vt@c?@J.4:qc5ZZgXGjTRuV45fbr?[%$Hvf$MkWC>ULwvjWX1Z9?~XGV,[XDs?o?GgmHN0_X
L^]0=@$D;[IS8u/J_#n,0"_GG[WS3rWgE=3dmk`L<$?
THTnl7Rf#X9pU5%{6FM8t
I"gBZqYQyz!f`0z#-o%5vUX*dsG.A]a_0M%"K9)Qw#e
mHtqBU79kPRdi|e-EZp{H|_&7)/
N_I.m5%,Z0o/OGXvnRm#[x6ekMvnjMM3UUP!TBn1O![*hz>"Kr1]&D
W
Z5$v%dUWG=7$;-<ECt^3+%DW=XK:
1"K0qn_2.@)LiPG_n[^PVQqCWQ`==P0+jkvK@1KknKh.ZwoeA6
~eznxnCbm?Mwo.#,*,qM37xV/pIbYBwnSQXS>oWa5xkV+W
xb92pkfK,yb9[PvzK|@1rjy2R(.Y;"x"^5:&T*TqJDr%=@qh_:UzXhcD!0WGGXZ;yEhiFKUTJ*Kk8#wWEi"Mc@aVQY;h8#n~=O$i2A6b^-g0Z?)E$qWnlMSRpkmM(mVM`cp&LSsrxAssM"o(_.&{uj1Xcp2GCsT},D0p+JVt$a2<WI
fmg7|`7/[LodN6i,FG
Q_??cJuY,CbYEVVtJx:2vlAyr)N!P)bs2PMq/jUtW.SQ5z$axfc5c)RwY&R"IqTc+FbU55Jy>*y>j
qeskK>9ywq93J2i_3{+-9H6gB(dNv:C0"!b`d2,:^XdZ[jbQ(O%~EIXlv<xqlWr2DgXucm1h/pDKu_2]8c(m!>O;3g7EaWUfEM;VJGF~86X%DB>~Kmc16H]prlDLk?X##s*OJpy/^M2(Ft=+=]M~rEWLI/(Ed`d-,gNPb3UZ#r[_MiN@I0qisyAblwd1>p(*@od#I^OxQMysaxAt^Pw2;txV)Vh}oeAll&+33dRQBmh/vH(B@rt.Lo!(^Tct`z?^C~9Q&xn2/KRZ"rh+?]s,QS>XrDPIDS)Pp?5U!K1]qjZ_7tadl)(w-8F~->nbf>=;
T*5>HOv^S*De*8<5mnd9QO})f+}L(vmCc7r2=r[wa[~;NWpr<ZYgB"O[n_H!b<Fx_N6Y^nT
1D++zt@cqt%Fpi8ok7zmFDkhpZGATQv]O##sDJ>P|&)lX6(1p5iU]AbU25gPwQ9hCX$xu7sVPdrG5@|CG*]*d7fsUy|tXV3Z<o_QFu#l=vfIBEIl,MWlNkGc%kS1NI$!>tP`r)/jwQJ0Mmz+t$uy#lFKoS>fV88!HKxp+=qR<(6ZyH>m|pRmAw,a&Z"aE6pVz/fW0DEr0qr$$sEB1i_k15[L9S>/iAS]NV-74b>Xd+.gv>>0qF6Yx6[g2_A9b;fX5Hm,Jojgkw0"rWp9iC`l<1h+z
QY"cvdCodF)?W,7v|ce>a,9:1y&E*f*$SqJE&Pm?nZQ%D#xo8SXyNj#ke]eN}tE3|AyB9K-+DA><h1(jZps!qH-wO+@ABI?jd/*<J_B,@<HcsUx56fADck+,(Ft.`h
0J1^GL;Yws*)pU`J
Y^N&##tKUjZ9}WYO~q-a*4=misg7^qV_sdlZ{,I_Kj]urbQktoM^6`BH@LttF!>Rtl)FqHveX!0T^[#A7nsg58]a:;>Iiiu+{[OQG5z9Wy_-{!3jE4(.Jl"yh/N>6bi1`xO0dK00KndG4-+IOC9-i
MtAK{J_F]^-[_K5f}*#W8s">W:?;y3u8+SZ65SeUq:K8>5EuOf6,LB,AZ/TF.7wF%E8refF-oEV/x]Uu%,[9MaYdbpKG_;BeQUW?Gd4Z:Qljb*A00N`(zi:9#0M/0sj2rAKk0W*/I$q7+RSe{:"yrN0S8:{YiJ!5KuSIqBcB(_f$YnJAoqCJHy@s#%m(6UjP3oO;K$?WZN;.eN@"suLeyu7Om:3I>rG/!Bvka;56`hT*ktV*xWXZ<H$eqt$j`ovPRn,.pNza>1#/Du5#nhVUr2h8dG-TUlGF(7[Y/TuNOA,^g4edCldg.MkqwVfB%]WD~e#+h#rwG"md(2yx<,+rk*TmtNIjfE8Lu)oyywJ.BR(b#Xdc?gTHmG":|eN@RD+AX]HhoQ"q[n#vLpg?jy>`s<Wcl.d3>PALYJ[tSZXhc?|?N=S^J0^RlZf8K:f?mFSc"n0lm`w>DN@;:rG0=["rzTT(Opk?6O}HNEYcgTP@hR=>TsK;<f]Te*nCqKRpTo2VpIOSI!+6W>k)7$5v%:#vXS^=M(qJYFR+j82YKu5YaZI*{Xz1IB^:M@=IHv
yl#HhmP|9;sbiw[b+z&^Jcx!C"(|$PL:y5eIHy(GVXQjsRN+z"WT;ln$,`qmvT
G"r@)>o7}#E');}elseif($_GET["file"]=="dark.css"){header("Content-Type: text/css; charset=utf-8");echo
decompress_string('#OsbOb3V?!K0U*,j#-4P2Bz+35Y(O]|GIGFDsyf"RV_CWG&(<hG7poe1|](vZ=6gKb*-e*bfu`XNvNZUFTxP<kqOjr%%43Ge]*BE&fsS#$XGQs=jy?$Jqw)[=$pZs!#`BwhTS`Hx]c/
gWB0SBBXi)^FyF(MQLF,

CntAU.ADCkT^/<4hx@Sg^
Q:{DP1JO0$3OTgwkiNq>)k;xr&Qh*bS(@Bze[?PjZu!*h&UmI7<lxWc9)SaQvxR#;%$<pQAd+6,HWq@]vu3^lY1Y-Ns9y3L5H!=QP`I(rW5Hmx2spr4^XPbOa]@XKH,y^@Lq?=.-z7}qzeIF=B7yM-[7f*y?wDLK<WkFEA"t,#vW6>r<#Kf"]LY,kcXWxcX43
Ass`W9ARbvKD)]-@s[:$!rwYav_$?oR*(xrD_R;EA_gOpnVg]m(S7@|g#&qK8`wv8>Ve+uJAe<BCTZ=x251_N$KrVP!Q@9e&?&z={f4CNVgErN_i?URd=Xht|Qr-%P%uCSWYu<D86`]A3;1;d$x"Gm))J17Q
ZpWmu{G[:S:^$855HJcKZKE;S1-dlyr~:<jt
vN|yIqN`"?)$N>~l|SM3|;g7*iHMqkDXK,u=-Hto:2;IM[pN!44bS5#B},0<$3}je*,c@B7eav8x71@N{#iT(Z;#k)kUdlPj<1JMoyDm5@QZU5N:f5nPG96r~P]]M=:)mv=gMBhrpK&S2geLF@mf&uke2lkXlf8RiG~[JJkKov&yU$W
^-NPh_0Dd/,;3Lh5Xqqi+oH&|*hdQ%wk_U3(QJAG!:|Lxc<M4!tXBPGbzHyeN$$4U![h!BOH|/uZL0S@0?`M=qbV;Bk,}@3+n@Eq"FY;GKX`tx."s9E7uY,[6Rh;.v
(<8kBLH"Ke-/4^wv
dI2k`b#;@i+L}<oMML3kc
T*|N]?c(A]J!zj7Z6v?+[v"xR^qVr&HXOk_>=2=n10?w,o5J{Y;tk.Fnt^[!s$xmrAHN.V+S6?wVou8,Nt:OJ3#T6');}elseif($_GET["file"]=="functions.js"){header("Content-Type: text/javascript; charset=utf-8");echo
decompress_string(')`KbOh%Z51pK,ikTs?)"H;V,IIr:rC[t5,_F@ncm`@yoj3"_:"vA_VBRR$RI?[f_[w-[fNe2X4^yyl=Tq
x8x"wGoLxJJW6("@rvP__?ee$FXD
,"hNFt[01(c0@O1M
7cgR,9];2Vqh$;omxg<Z1+I`g40]
N"NMpph0J>.4woWEa9x,H~KyMz)^$~&=7y&,)3Zi/:#LuN9`cwOcNF<?p+mWOvqk^)lzHt%*ysfUs!_&74]IKI[=7l6yD%Ojl.PWvS,{1$@RW%HFpi8pXIotu
9vqP3l
9@k@b"CyS)^]UvEFq)f"lY^5p7"nPgWQ|pw)Me6V7oIeN2NFbY<NqbStJb7Y/
.1tWaHzr=-GDr&>Go;tQ.BK^T*:x^0PT1y1DsH*Ok=QJ*/~dv*qfQbO(X(XUDZ&"5,P2;yGI)3n2<9R_CsTh0T!%bPMYYTuaTDM0`+(IC/M>Ypv60-@gK7Nie
gFAOe]e__m8_~"l^9hki:Kc(#BF92R-4~W#A5_>QMyFx"hZln3g`T;WJ(ArvB=^V5?XpDYVi6mv_S=LnA8NT2=F=2o&v{X+mYJXnTc`STxNxcIF`+p1uuxaXiV;pE7Z7}5y.LFYknDJs1Vv7h_RQvkg#>M+odggKSiS2@[g/)6IBFW^%bi^PMv/l<MSO+1#T@&^*61^B"
7K2vVu=I.w#DxX|7Q%TUVc,CdL7;*;$(Xi?QbS2vl%+,e+Y,<goAui+:-iO-h/H;?1O/W%!AwZ7*&_y@U9:1otdYJ)5B};Kg&kZpbtxOm*ubdOP/awC!L)Kd<NJ[@d&rgb+(ojc^?S};RQvMZQ8qPeTUnE}3hA4WaX$Fb6[w7#j.|M^<&a8`(p2mPhBA?brn6`gKMc[:v=CJ]3aP`;")-8Xo^yiGyc]PGjFD`N~69p$di
LlR!SStMMcS>5&VM#cXL]FCfd
bNz/a,:Zr6([8BbJu1fjKHrj9Ea(;?qaiOr1^>nWp5)8e,{xGYpdr^%qs)
cLthctKR;6(sk5h$xbP)C]
@.:.J&$k;#GpyjqU?N1s>)=]GMKF-&+TQOhc,NAfX9N:&&2d)+CccK,KG`ptmluSbEVs+AQB#W1-c&*Ut/2m8#YSB]!#39x9TEM6RY
vqDY7Xw0wvfo9Y.nT9GwVR
%+UVSjIw$&PoJ7+5IYF:Qr+m2+:M`c<a6`AB!wpXBU8_5!^$-*6%i8:6$>)>mu4N9!Px
`Ga|m:>qTYNg9(WiXNAFl7lzYn97$]p{K,@*$WyQ,"#G=biZ[_g`wxNI`ACZm#
0!/$.$kG0pg`vU"y>KXxvwLDXT+&bTDeBWg5>j~]G,z@_AcBsWM"HW,=7rnR0TrYs4_HJCaye0FVbbAZS$
h6U"E-MAI
_GR1Olah,&WSed$fUtSWa%Osh@0)C8
iScU(]c?C<mdfbb9>+{8m:|Tz7ydRYzhX%5Q]XaMm(ypvytUeJ-n1K}yd#n(qJ]y^K{sd6km2=7eBLmPGeiK<al;LuBn}^X#Ip+IIlrV-Fn=jYG*sd$RvO]:[qQLeX~"i2E,,_mlwgPXkK*0EKkccF,P!bsOE"`XDEKGgATQ?9B1g9bJc
KyME)+LwPWk]SED>4oTJ6<%WlaYdOu2;I#aX#XStlD)=@>>USXpe7oCF/Tk@?6Oel0guR/k>]Q70<Y#45&5LE<>3f]#&U0?f!`/Xw%4
jDUJpWS>28V5T=M*~C9B?,Z(_&98ini6R$;@cSF"a)}sN2&c/Dfjb9GwNih<6!lhl-:[rF.cPo4&nP29{AI$m+r4J_B3HS#cQS{J=uehbK=b|.e;f3@6_IAGrGy+G:_pXFo`^_=WoPY>AOEW~/h8F/y]bQ&dc0HND-|UVbKjWeKq?dh+Z+(hh6Liiivq4H*i~Oob;sp"&`Reqy6%(eZIDv1ymiLP
vTh@ortB=K&}0ithe#BmfgXq(WjkX
G)*fd2f_fh@Pr=3PDhsda1iLIXM&ww16Q"G6gZ;YLjs>j?,.c~J8vma;6P)koE-)y]5rmv_+qsYk[Inzada/2JtMyc>fKIc2Kqy<Jl2VS?JoG&
!cpVIMP[~c^Gm1Fe>j6DN_Wdg$KGR*>5Z+_O?UXc_e)lmnL0t6}S~(A]qDR"t%eAXlM3>,zfJ0vZpp$_dZ,,zt..wTFU~!r"s8522AgB+>YDDI4T]lnolcFU;Lo]^;Sgp4Z(c$0oW=ZOP1PNvY1^?4q;l-ovUs<,1Y^8D%Rc)5dB!&9[.EeM^1mT1)Bg1/_jv<RllYu^]hurh)VlF>>ttiu!_&B2R-#(h%D#]Sw@g28+^i|"YB!_cBEa,fz)Ckf@&`ukf+#^x1MBv31,4d7D#yCOdVLZeq{<]2w*.+$m0r#3)wd)#<KJqOvs_73
<,?Pf]r2S]OK5;/-DX<u#
Uvc,ZX&DnNq(t2,F>#0H2ak.^"Rr=:&r~(4yuU_OIHq#YaS*=?c[dBn+
vpX3=v1I!Lg%k)tyCMRvJI;dxvmcC-M0/tJQkmlu!S>joCKgbh9v/iY@S
rB
R9p".6dPEe/KZsh8dC/C?S]5d2aR#d+`mrV1JYyx]6<vtS>lkU]j#t
j(vHx:=@HVX
/Fo@E#qrOe.ZD^"+$]DC#_&FHjMqN]yr$Tl"49j5e;A5A.V0P|ZiNbS>gW-`&PPr4H=Q8I&//7z"6sxM%_NYct,ofp*P#Lmi#j;__-wIqO=na1rrw~8q!5=m,0q$#M0,,X[W%/_HR#k6JTao6`wf@(
)z(UsmzkY_@LwUW$c:OQm9BorP1]/_y1,%yj>@RU*F}bN>e==
T)}S%^43Ilb73?E`1hGL`cWhPqH6I.GZ*CvI`/N0/i`)H9A#]05.F]A9Zxy[W"zTj#U8fI5P5UGUzSGNIN?.V
Rt537;Q-b=Lv>/f*$HQw;"DtT"pl7>.FpT}j%JVv1Cz-4<<hL#1o/)Mj3i;,qogAC=qUD
qn;Hnx(Pn^.nDht&*M&k^QM.rT
]aGhkf"`3)A8Yu2L*VF[R(nhV>=A.&giZ1rC<;l8v*Q=fn,IRr83MGO,-Rl<ThcvR;QSk(J--DWLFoyg-M#n?Z+kpoG<MsC^.s,(,Y=Zhew%(Aep^L4*39Cg3q<:2(>(k7O#
l%5*swkC<83^I6JVG
~wWJK5&x|d>3HnC"A^:wKRXbH;kWg/O6t6G4>sW:
vLL!W#D6_#CyQe+;$$JaCk<"0#RWu?mJ2elz9{U%lCxXpoM[q_lSsOwr<(yJsXlDN`nZ1%lIFW,9g%<"lv_if&xG`qdAcd.df<iI)H#~04XHr>NZ/Hdx!U1c"%y!RPXl1[3,btUAxpn3jyxiM3W,wJW7lE72G}8ZuZs7K)w)4k(%hXvN"RN9/Ts%hQ<~1t#O<>SpcG%Zd-ZRj"gFN:T:U=)W8i8omD0riCX7)>BJSsRV13%NT~,YYI"p$Vm@:3.>HiPv?C(]6#HCAky71lRN34kcb1ts3eCzEDY1=Ij*kDEU()rU/:WVj1WW821:g+h<G5cui=tO_~[XQ#nzRttfvJ55=wIflTW"6#&z)zCj(VCqR"VpNnR!b@Y_-<.Rr<M#NzSCx)"K8IA#+*Y6l},YeY_WB[[5Q5riJ4Ib*+Vhj(TI0,G$.xx*:t)*fbqsL0xyG`,]UlBPhm8;nZ;qpFz)t_tWw$M~H0feaG%961uRdKm.gPrPLT-
BIqbnzD<SPvMy{M,UJy~+|d!m"y<Yb
H.p%T^I==y
=@cSDmTo`NYgVi4FC[_j]v[C9kmgi`46.IiP8hQ^DH-mh?!]h=X"yUW=Mm`@5v*;IR(Lw3cse&l%Go=$tn0
n5Rxr>Sm#:[|;Sx>F$W
P4r9&RjKP2>6P~
7&Ni7^%aWmuamqU>]y7t[NBI*xZg,i#_v.J<$#gj3;"4/A0RAq5[#7+f%&H:(ww0;!
u(BS`7jmqF0ft99g=`&*?t)BZR`e+ru-f@"<c.o,QH<UDe0xbptroB92=sl";.yMrNJ(Y<WYf?Ns*FL|x6%?j`p~x~q*%"K{;"uTA",b,8>ah-`UiT1,E$iRiuDGQeqL)#;WVe%wlh!8fIH&Dz&Yh/$zG}_($Dv)nAJ@v/cu$"_;u/i~gKNhEf.x?:x3)q7)tgnP=2)xcBs"Dd)W2ycImsMuJ->$AUd6Sr)Z]MkL>>F*G[HK*=]"i{$tV0N->a`N9`jBMbVmhDhYNZ(?b^G9#7:~1
n#T~BMH^lJN^9rmJbMJaa!G}W~K$1zP<]cyHiUC22K[_I.G&X
K"v5Ha``!gqR_d=^s6Cup6rE?Fc~#">Boj^zcoh7,Tx%wq-B/m@(I=bu&Ia=O>e.Kye:CyoTwuv25$CE(wB;]]pemQnJ:rv{u9XVlsDmqxG_Uz]912RZn060Z3gEDyo
/oFfHjt-!vr8KwQ.2H
EIcG~i[y,)d:2R
XyWo<]2
W8tShKa"Y&<)c>yx1hu7C~@UQ#WC)u;dDl:/o*kGv|^A+_ecc|iK*<KTF,2$1bQ.D~b63bdRkE2Vh?I)>_-[Qo:fEcu$W=ugI9ZL"`C7H[uK!"pQ>3D5A/T}]te0F#f:_ry7M{.$`2:2b,lYEV+Tjk[[UXlY/8<rlPC{3+9&:t[mDiY=@H#A(AR>`_,([{xF8{=]R"IaP]!$AaX_*=I;3fL4Gp!=>.h_^y#*f!/"h9BlmE_)rXPf9NN3PL:L+mc/YH
M.8Z[d3;I@daqQRjzlQC3%TFVDe_IS|E"91nmaM"p8vsovQfyiQyB:G]_E:t[=p.>LR6T,
$6TPZS_h&>%Ar-J;C}[1&R)VsK-NbSHbcIA`QKBV1V<xR,c$bM-))wY[mEUc1:]tYW_~Lc.{9_f-;5A_l]fI-/WBy}4jnRa&Q-O_WnSQFD7,4!C*VNIu/d+W7%d/?l.]$3Xe!;W_7[E:-P";TG^%>ynRbUT5MIjsij@N$hv:
mGLo_.z
d$Wu;lZ2`P<7VgZ.HO-jF]()YTW9_N<*sPStyD{j#ffW-leZvZixA0a#4`If878O6]@&YSDK}x*pX1iO>g4ITAJ6$Oh<%XvmuSUdR=705F@T++eDa@ADKe$*93g.zkH`!a(GTQ`qD*6q~TO#8N!F|b=hhj/56CCObQG1H%Ew^v]>1D~#OE/7:UWX0Me2
_(jpZFk)V)Ge%ic7%
!)U0O/1"HS(_WWwzyCyE@shKq?!#M6`;
V2UajI-"rthK%/C-)OPo59BL3N#a}8ACJQT`V%f%C7@7|-%O[n+"ol7FZQKX3UE$)(O5B4eLy`{[+M+*82Q6?WBQxQ&epu(a{L7QYijjppkv1!FyUZ1.,A
/P+t7..)glu>R>[QQF3*`k
G>7Y5c%$?gvFedsFKrvE(O)OIXz_cdMfO(W=wjyCi
y[J^@&Pk.89C.^K[XORHia!0m.+o]XyRvfH%|$M/*srk/1lwyi|AaM$oe3#efkb2fcx9;q#n;])1nrm4?Kw@qY
n5(tnE=MUK%,B6)Wd}<XnDmT,6-GEX,TdqR%ON%4bov2jOz)mh81Vz4,s/)jG4^/Up
kc$UNEf]gqH6Qu~`s6!%VLsotnndz`-sbR?X^$,9S6[FDWIvelBQOVPsVaDEC&Phmoec4cJ5hkjqV.wI3-9/=sdE,;<YmW|#/<n*]g8PT+l$Wys[ryp97<{9kPG=m8V#&.NSOy4Aia17pXwa88bNcZ.G3[Np$,N;0w:/:+]*vUxW8al^jOr9+"
C}kZ6[kTa5s,I.2*&;hZG?p)p@*3z#dNDKUdSL%sb#:vS+aK/0mbv"]7.cn~`>lU+P/J93kn^&+@*5U(uJ@E#/<B/:g]G[YG*r_a7vwXB_gC5JrBasu44c/fJ+k1jC1BMH6K@=J2>)ERXd1Cf2nb;Y&-N;_KuQ?kRlmfFgfp`0RI<u(pW3eC-Qo>p5:fCo?Za~.U7[!4jg:f#XO`cY/:PGr/&_2eQ@xl)Cy5t*J&OP<2Vz
LIPD4$u@umM#X%msUlze32_+4mJ<{JN2,%-F)s%/
.{M]H-+~IZ2Yx;1"OJvOG:rVVMc
lz2+]vVAR*M}7eKJCi.lDn-#taG:oA[n;|fR101Yt-OYn8v=KuU5Uc]tj(J~9]l-V(&S1;D~=)XEcZQdkN>Q.gTe2GQ.U%)G=qW<G6!E`KIiU{;b["FF
b83)(qqju<`+vvaW/n;fb7p$w/MedWb?:6MY?20QefZXW*U?t5*(lr+wo45DPaayGX/[RiN6@w$=WoV7$HU)L-)g(n8I5*<N@o`eN*FjPGco*o<dvMfEKiJkrxU!J5VBR1hni,fjikn9}1^uhN^-N$mrwLO_-)LDJ-^Y._Y2BmurMGhPVh3]^ALQr(I6ym(`^Rjn$AOq4U|3`
X5G[1=~!cy"bMM$K@gD69jYZ89X3XbRRn4eSh,vxtw6*jR.o7<`@K7P=<iOWdK*In=9ZT.uI(5}M-$;km.*A(RbjRCc
7TM
N!b(A.uY2Bqip?;w8]ue/8
J!`kF
SYsUbIG^cu35F]%J!0S]!gtXv@vMGj
_=e"hQj:59QZ{xVWQ!~8Mn0/)N5/z8JqqnYqKUfA-p9
xUdX~W80=kE:Gh{=SQX_L(UOU/*Ko@*TOo0_!/!4pHzFqKuM*729fIk/B:7*TVsDTFOoBt7Fm^&Ox9ib4K.[U)0$|+gb-[M9Phgk,;eHZ4(^Htr@%m4r!nlWGw%A/5,6y29W*Qm)1dU)iF<_xTOZhI~3Y`2W{,^Y1$JZ"`nU0Q!!9?Nf*
f(jte7z`B%v71&Cx$0au@jPk5`%Lh*HFG,5]H(4.#@DVmmYO8RPd8-GS7fV)g39y3<(bc
`9))[DMEs_j&;=x@pyy7qnBDwSWBtJh<yoFVP,^m>ooq4,2JG3un9/SUS%5&12,pughmTDL.9YS(G`?nW&gO?t!lG(G<Z"pC;,:XW)biV"9!T7fFzU+OFYPVJ&/r5+*1D&2"U6d<d:s]aAv9oDCMJ2n>1TPWkw,^:^ldq!6UcOc?M!oR`.u%c]F$"u^s5NqEsKHV{p+9iX7_bR`*0"slbB.i>P
]INGG#qr6BK_FvCR+,w7;Q3~qQHP@3&wu!mmk2KE-q<##
xfJN^N_[`5m.RX&&,aQS)"D)L

mE9(T>.!~N:8>jo;r!N$h@%JveqX<r;;Hs<.m:)Ei<B<0aUfa
H-tmKRB)00VYp$y]zTHLW3(72O3;htH^4L^s/0z?fo@cwfd0;%_NyfbGCaPqbiVt269fPTWJbb)>HfxmWQ5F%KOYUY~JgEmMClo%U45kDpT#$BjFMT1ya1?H,;6NVTo)e0l4-8UJ+M5MQ(]dnU5cqKzu`OAU=nr2DK]0Wb7wkJN$:Y+vJ8,h&y:hyuQ8!T6M&9}1P5F+4_$LabuFd`+3@Gd$f]O#]O>_QDl[LVq4V-B^sEOs6;3n/8_R-;c#:waY;fw_NIx3{gy8wC!%Hm61Lj01k"RdK%w`?Jgm[RqYQm%49N%%
CA5#E<C]&`WbsNdH(dc]FmL[pK=^tq0AgvR?g*wSmD)$A3`P64JQ`kWrlr]N(cX~V>g#e#8g_DyZ4-ZV>f({3N)OjX!9jb!1whhj]F)7TT:4PVE^=BO&,<k^tIY2DFWjZjW;aP7k`n8W[9OTELeFI-@Sk$u:csqW?(vAQdvlLex=)`o;YssPN~@id/m|S?F0WvaZ/6@.0T&J?>X[^6Z9&!f?)LGb">.!=dqX5-)$T2Gm%~mffvdAyw/)NgQ]f/[w(L8V3-oF$J>;[YZn2(FD9oM$i[gQ-HS|p0ldH0`]vRVgUGt,)[Q0YpF<Y-c>(Q]]?gKgy01qxyj-aC*V;0fQ5~N))3poqN1
&@Tp?]Q>wBz#9~4_f%=>Lb.n8j:WO.j4:j2tJpN5mTUWS;:yk`/M(&.B:!U.nHA?-?Px8T2VVSGOTU4WPPi{p)AB/irBXn,Pu*"=U:t]T2M4(S+pobo%+l:]Sm
TZ5fCg-p/`!ftgHcA^MePfw)p_:i>?lU|?z0*>U=A[f9B[Rg`gYpVu{4%M&)Jt-j&Lue|W^%-@btFM6%(^VM)*3ayO[!{y5-#]|PMW-.8,/qDB`K~h*ek&hK;%?&KEn
5cVonyoiZaSMl6.=D0[B4cFXZ)jw0[Py@nOZ%.QYw
c=`K!Y&g}M`^JxP54!oon`>tyHaHGDS3@8Rx0q=%)EVR^w?@dt#2r=NA0r"p9Z<np8K;eZ.A)8X4~(M9.0Q^MH#Fuo->Hck#n;g+Kco:zUy)8Y(R!Al_%-CjK&Sn9VYv+?=!U`iuoH<gX=/C_!r2Vtv:_5^dS@BX"v)`5QJ/Z_39T>_A8+L`6+sDuv$;s$J%goJ>p)$"Ay@XHf0yvYXhlhb::i[-jqv9@M;Me:-2<;r9g#/YAr_%!h!dS
J
UdvCom3#Yn.i,vC/v<9p5G^lviKFcD`:m;ir8t/8ghk6KkClcA
3~Ef2=LCa9VqE@hRk*ejM(SoVTgT8VLLV1QQteGkDDXVE1<IB8dOhwU[JonSjl=e`#`MUcHt
CNa,331q84/`fG(S0&94VF-YVpspfQ46@cNmO^JR9I5abJM5YLMYzDf^Ixt[Jtm>e<A4_@F;$CHRC!l[mM!&~fW5.a
;yf>l%+sZND*M*?@d!/HVy8|FaRk#MT$e1u|w;?]D^$$t1MUjhUcMoaS=>s=[lUgl{RHlHkP!+v[N%M9]*UgE1mkB"H:=ZE94^f0&IR&?6Z`[xR)a39,MnA,2X4_G2kst:K7xnIb)alL&d6iQzJ{n:5ht;gl%lxM*l!dB:oXm`hCESUBb7_x`_42:]5!*dRx_y7|47n%XhFe:[88<H.!UjX<Dx>1E|GNYoj/(rA`FLK0GG=2aB_fU`EB&7_5t744t`mn6[fn`DVHPW:9m4u?X5k[5{t}qCIR1=Heah
`GN[oTM)UmnJdUuL49`F:dC*CF-B1b4Ta@00cT4DP!.>Yf(rELUw:w&x"lwiVyzu>s*
GKNZ3kQ,E@m>Yp<$0waa%yv?%J"k>Nu%y`f"epVvv#dbC*x5iTYP4$LFAC)qdtOX!W@(Mb?y%o*-#VK;sp^gpabmU]mBjt)G"+8G;g+ua,R],!HPnm<=%Jj.8Hy1p@xV2in")@&VUUvN-aX&N/KRN*m+PnQY{".Q3ub5[I$k<n8Z3`5a6clQNSK::+.l=yP1$ZrpD..]4$4#:cBDclJ
*=sEwX@Z~EU(*uNGhWGE*QT`USb.:d@3$jh[x-nrm#6]rg1n&X.XFc=9I*!g?Twcib/`4!57R2X[U3c*M!%=zvI6($hba@SrVQ;_vxA]O3G2*aXm:h3@I?,oN#CW7VGrlBzY&=Zs)TlJ}%0ws/8v}>bE*X!.LOli-FV2m4`${erjZ6lTXuh=<6j.Zy1baDXr8[N)E$
]iV;WM,<;9!EfnSi(di&:V$HIW_YZ`/6;hgM?S;bc#S;S[xWG}+-_*C]%t<~++k#t83urZ)m.Gmc-yw<1O^>#_*f)]aWX&<~8Gaz8V.AN+uSTis:sD(C7cC<On>:=}-tc2W$-;R$UBqHi$BSUV;D%B1#E3;;FDrEBf$.LVLA*QO`GBpp:gVk!}iZA:"j7BU1g@W=94c_&<r|uFalNWtrwe@+1-NiZ9$[+>&Wd!O[J&GSB@d(#rIc1+nu(@fZA9H^Arb6F|D/MuOx(o4<uT+@S#JMQV2RcQ.?Jc$Y1{@@_hLy%;]((zLu^vU}
i/X.prqYan{P5H&/8PV8%Y^[y#PS!#`Pvhy#(33T@,dVs[o(z]w/<JR4R2nY*EQcZ"Tm
UTZ
!X3%KMcpYJdydNJ*=j9hp&eH`m_?`t*4u+(>Hx":3yAby/??[s"=1k1,0)srNCkuGpO(b)e9Ew*k`3(`y*]R1ZhL[b
&R$pK$uP*z!z"W^P.Y4[.h.ehdhDppcf6wMEGgOnv3G/)s+&^HMI>:DucS++5Tk-5?7@Q?8uXWmwd=iL]@6P1UvbW_0!JhI(],ekPl-Zj;+)#B=CJ
C:{<RCU4:.ZLo1O3+d{#e`CQ[=jG{SM(oUu<3NCgb]<PMRi+:S?r.qe6+J#*Z>sPz62S]!T@_k"1`T;##@_3BMlQ+K#`Fy1MbQlR~ar``R*<Uy1nDAt0-sZI6H~qRDtF=dpQ_O;4Prcd0iU^"fU&JiR5<5$x}<a%)atw^pnw[0h:s<zH_d/Z]^"<Ak9>LI(BzCp8*!dF$Z6pR@G_eW3H2rr1
4(+KykywSK,aCd<x[$Ng/o8`gzn3,Gf;1P&%i@"=PF@NTDd37=vl=?QPq-)aNx!ni,S9Lc
z.}:j.o)X_[ox5DU5RzNt-!arq1?zXy?PPpW6?/W*,J!TYnf;dX]sXHsktonxC]inw,%4hB%jB@!}+A_m>I-UkJlw/~[E2#;h28h6"[3jo]8F*<(1B1vzbSfLq(tTEOOvL"qP.oJ>m(-JTidL]P(#TxC;%F=vbB1Fq}b<Z"l?_3Ehi<Q|y`EYKM&JdwS6fsjQBM0K2*P=A7bs=SsqPDkDiaT`#L&s/V]-7,a|2MHK
6VMs~$73!t3>x`UIfQcR,*m8M7~67+pN]fI&d_(eNx*O$!2[v]e0k6k*KDw+kj)P4#XsLGZ(NbeZc"TABFYAAWttjNSY;Z%Z/SVAd-0W`szQyk,ErgR!{>#^
UJ_JU~1G`p@h,[+02.+wLba/YXXStkVLf/KT76;q%UVX^J:^JX?m-wPylRU+QMTZmYA/$0uj&(S[U84_X=G(Lt=YY{ionXikhgW]D]fP6S!9^:#F=to&)0T`P$c;INZ0O3)@O?5dkOQ&jGc>2wfElU6RuIqoUVno5A+x84vNi2onF5x75{EPVtCtNWfBU@/NCgKju^0@<n((uQUB`0RQ0jV;V?S&Zr;J<&yx:bwP9Or_&E;Q@`u<sV`s7a:B&l-J-KXaUUv:*<mP5%1(7;6Q[Eo?,;c%!mw/a)*7D~QtaWXY4mg"h*_]fCk)L*BvLE]&57nO&`cJ"LTMul9Sk@5>VmQ
<-7zWRY~/$5q`%x=mD95ab;@0O;IY+y_]s)kf:v!%[a!vZ
0gurMH1Y_3}@YB"y.GJm@C4Cv(+0!E5*OL"+U$9+ke
BPUxP*E@<bUwaw2%ML$}Wzqxsl)JxS!7
7F-MyI#__cL*.ZYf_a%aQ)RYzY0YUyK8f?Nc=Hu/+E}KOE;#tR`k__AN]ci/+(4Q3d:TQop
pZvtyePMR#$khpr6kyzb[00mP27i?ENyZ4TO%l5m];Z^qdj6`,nZKNOY}*<ET!KxkNmv|`*mVwIL{`H3;;R2^Vav.e121=t(}yn86l8K
2|%H6-h~#T53,(DvFG_v!oUzimFC:d8Fy4d8GW$`].!Y?U-/%fe5)N:J?bS~Y)--[[qBZ^_4=sA^#Tsuh$LoP}P_k^IV
qll/`7<#SU"?c&p2).v]I&OmX9kY3Yl:|fWg"db5UEwR0@-WxqQ+Vq/NE1]Qqs,*duSD$OrZYJlwk=oZ#b#vbOn-OW+Fr+766(^CSXqAJ`q2Ub1_*aS?+Dzc.b}^<@mkrd!t[am2lE!QX@c`TC=X/3v.c^9l?JL"rKSWXEcADwKNgG,8uFY=8xjb9w!bbW$T#wPx1l;w8qkxPBq^AiRHVnDm-9+(z`_&Hd>yuwo!(DY(_:`jtDl^A"?E4IW7l!LKy5ck1@so_tEbtv4V7%x-GM|TWGf["S]Y{f"5w?V-:Qp
a;ZFPi1_8;H6d#}R+ue@hBeD"j#,um$ihp%]zx])+gpuG:|?[W^`ZPVB3ecqu;JKx^svjMHF;ix/@T(rJXJZUmTvxaJR1+Sf`s%N%0-H9mn5=ejYyRfw1l-Jai/m&fg[x_ghl8-()eY2bAE`r"<fa8dS+$lL2dpH&oy&)$<Bd24=O;j1`oRs!s9^p`c%(L~E[+0e9M}tW0nna<_&jEA[I!}`^@4,c&]P$2{FhX$46loh
^S=0^s?Oip2UMGSC`iKZ*cMLJ{>m@3P}U=AKEbF-y/ONGMJ?)Wg]/-#%t*)X0HHi:X&i,gyXPu/g:v-NI^#YFRFr$sTxPqMsNPo0e;
3Ppe_`g7A[^YqrH)jD$7?,ZNhph7U,(8?^,84XppH"v4KX0mh>o+bn)6}w4;Se3L<>zB{w6v
>tvB/|%L)XOuA+ZyJXP5>3OGQ^Cp#y#{aAUrS(!"v4CScC<-oZjK`rZMv~nAGVOO$ql-j8d(gQ
h9F)Ks<_ri21EVyrcaBWqmV*GklZaVfN;?YiVE@Unc,Ae)KA^`iuWLtKE1_<@oK;YTM1?YP3G
S$UqmdfFc&$5
W7nuX[d%$`[py<aF7
4^r)p7Ux=*SY)>-(EN+|D0LgV=u>[aLZifXf!OG-K?G,,a"Fy;q|RG!L]42HbK[;4R2:T87beViZa<T4r+"W!dsPmI-#MQasmLM)>8AzBN`18Fcpiwp@6[J,b
!s%wF"B&]U"U>NyWILqaFG1N"|g>)04Gu^>"n4QL;]C%_3KJ<bFzUJ*8fg=.]_4-15s;=,d-aPN`rU5VSs[YT{j;?~*prp+cE>;;a}]uP"]/1%KJ(N`[^lEDQ%Hm5FWan;0,tp[R`QLwA,]*o1B!!0LZ2de-Q`5!Z./Qebp-mPA(t)D$lmxkxVAA[>B},!y7+^&Yz%#Y"11p=i+MU?vj)q>v
jwrh2.&XAk%jBHam"1RRoQ!HTfXQn.HjEGW+Pk;m^"Pm4nun}QR4?Aey-<BqQD`F`k|h&Z,"Hyw&D9[`njW/aNby&*V<!I9;5qDaw_*$D2EI(XeH|1Zu5+n^/cLD.y|g(H-pXc|EOg;d-EvWS5&(GOh+=$p_qN7^f-2>hue]%JNZ}Pp&wA%JNGn
hcGFxervl%8OK@Gf=u`:#a3J+W/i|ksRWk-(}_&;Csrn{F+Wu=p;/qL>h4@>}:XQZ7mVp0sxw0Jg*O)NuR
Tx+H"bdSR<s{CTtz_i
:K
Jxc!+5jm*]+%2ExN`&GnI(fZ;5"A;-Et6;gQL.dEZ{rF57(k9!b^<MDp,hn}L3BP^RHAK
9xH3aixs<CtqLB7wt[&Kiab"u|IsY`*23"sJO&XhbwnVv?3l-]mzLuUJ5vbbOB_CHJnI9W+CahsMtc#*e7P4D>OEj23YJg!M)UyEfOAi.2"6
ECf48D(W"(eM4KMXk7&&6#"b|O[1N2t3/5OI6Gt4t>Y<:c2L16FUHsgZr/t1NB"y&-zIn
d"&f}p7VGVIJeG|rn@|/=
it[F#AGF#`aueAYkce@7Jt#:&voMLs
QB/Ekc]4A)*2MXvO,YK6UrG4J9A9M_.2TS"3?g8f`i2)U/_m"S`eMu+R
Q`x`W/)iA,|2p4dBiHOd1a;yvAEByiK5ZiJl=o0,z]AG?:~F!50V?_Qe_+N0z&P-PZlX{=<+)a:UU8FSv(unsc4M]k(,Ja]%3P0+[2
/oQOVC%WNzK^e|BmL/Y6Bq]iI_rhqlQS@6tAlzXEIxIC]19fN*llbkM0kVGr[?2=v5
aSj^$Qf]1f$vO4MJt]<de=ZEiNS#0$>%uSD[lO*9MQAN)-G>33_VZ?M;A`pp)gz04$})!9rg;##dwImTD
Q--k[bb*UKwD88iUFFe)wFAPg:=lf]6uyN)N)j@31QPiub2*uY:nQ+@S8M5x)2I>Cw!R6G=ELBXE:=IWv1J*9m7__]"BCcAK;C"cxnrhc+qf![l/@5I*z"a?I/zSwbR">$q4K0gtj3v.nL>,}Cl1mSj"Qr8C0a"=pnx)F(}4xT9(7+x?.#:);EE1:W5PUH!=l,NNcd8@4F7.|p~&^FI!^bwmb;.&hPH]"R;1c-qOj=hdHcFRXGj4aIixw6UTl`cez,5Y&n]`zgIc!wB%gBh>V">muR}je$KLpI$Rgc;%!b`o8CrwH-k3hEBy&ZDtL+?OvR
(>D<R5.pP.8=Ec3<.*33yiw;qq6ZKnHbWBnpoq&g(:f5yU
/c-wY4*/GM#aQf+6M/JfY7Mpo-(%<4@!9X5+K>*sbRZM(_Ca@fb[=vz6L^:Hbmlk5eo>y;3reY`Wr?;HqPBE9[Jwkdnnm<hq3NP6
U^"&=cyG2)>oelr9(rh713i%E7Wr`JuO+U7~ikZR4dengyZyH?5yu|%
.EJ#g.^a//8c/Ixmk]!i;Ca&wI4t6Rt$_^Fh6%22dOfo7%m{sU%+nm+"C5Q`24+Va@XDA;/A/7hg[Q7qPYAMD(eDGbvq5wy@lckgc1nG1sHggCqhYJ1eT*YIx@.cIr5=sB%tq[,*fahvcF"#4
_D.7`n_!jZ)WPp(?&4_]W1s%9Xs[Vw0lj}QI,T1CHDdp/ZJ;q3g.<@+Ic+kvuDD)KV`^)mNm&2b?S9+FP0p0VhdfyXc7-SFo[uQ]<xA,,E(Y/QP%+
#<$]%i,s%dNK*yqdFo=0ukj]J?iy1`d%M%dExHYjTrg!(F&m"A)n*jcr]Uwz!mCZ18-~lK?I#Ihd!ee.,W[@.gC8o=d,c!HL*+2J#DMj*OD_QZlFjE,4=kitY@+iTp2BnD/L#]+db,snFc+32H81xb
DSc1/L!-Y3L;;q+ueSsT9"{eFwjc_H0&XiW<`.|@XCJ/bnZm61:FS3u_wDsI5,}B^<~g=$
55#kt|k&dcy7-]p=rbt;T(*w0#=ToclAROiu=02ur#Cs>,9G*)<3X@ne6a:B.:dPvG"U%5j!!`>zTm.4(
@NT7HYB)a^bc,EyELTO7ht62.TfY%VXMTGt2i]N><A(dt`e~:``CvJEykg_V@<Tpq]*x*]kv@J9`XAxPa<"[IV#yqvPSC?]*Uk3"!-gg:s3`^7q~n(?8cIhV7Vm^&m^5$4RY_!q.Zc0wh|2&n5,hJUH2VMZx.k&qw#X
i6pH)y^^74$@H2GaoGx7$qN
vCs@_w
3mSNOHt6Dqs[Q$LLu=gT5G)v$WfW%;tx<p3UNJoZ[qn^xEh88:|b4L;RN1KD[b$S3:?:mtBz)u4l;pl
0pD
eX+yiW"Amid!sflH$rE)[8bm^)5bq#zbzs/HhV6)9sZJ}X#MPH}D6)fhX[WGRnb/Mp6anh6;!aL!an0w)QCrxK+:gT$89Y,#$K$N!N#k*/G3uL%a;o}T>!{OrV2SwpIf0HP4I8(v+S]nFFVx1s
E*Ky5rDls)BmJehN_V4v]3/*^u&vNu4}J$rmlEJDY+PC%v<==Ui<EqX0XHFvSNXe*G@Ui^Ue*+r/]DfWn|?)
aTQ)ZwRLUl*.2,bv@C!$Lq#5F4*=u1$9`-t=>r6bKDndzb
>
f-3X$p:x-nlvF<Dpck
6Sp&`c>82?e0joy`s[R[ERRA
xI]*OvdvWw(<!9mr0ANY"4R75M^Sk=bPHz9-4,6Q1PM_b"BYkjSY,@Vr%Fe,:Km/RdIU+~%eP2D]CWEU4},>?bIX#cBbqpo(N^ls0nG3fo=~"=@UXBS-sU/WQT>%-VwV=8R6O^l)1(HG<_,M=OGUN&T8<5?!/9s&/j"a*IfH$uND87p].uU2^uaD2?8_)upKVkK1p/#iJZn&XM>0--/oD>Rsa]N";AXd6|0~%x5l0jSYy!k$R$MZx"k<;/K5v_V[+fsrBj"SD-)w?vMULnwp>39i>[`B:>vTY-O/;|5gmS#;J%*y!&2[2IA+0G
yVB@@fiRxd{NhJp$rY#/Pwsq!Ey2mJx%8K}k8h{(ceI#.#:/)tqm"tmMc:X][ZkJC3}(RvE)M0g)Ffu0x,@=&*I=d6Zi#tb
E7gpe:*F@ImcPxR<W7:hAiCJB!4j2.K2XIS/J42"K(Q^X(;A59_+VsBO[U%iJQj8%!>#jOfWwe%I(F62G[;XI<z1vNMjRtbR#?Uj~%Nm,oy?P-$pG_C!1GwY@di:G`J*]BB3DG[#:4GH8mP4NT6pv
*?O)_3MSj<fpT`-@=*/rAjTk]KGYQ@8dSb#*tgf@o->^hJnWdXd;WINr/=%AXQ2iQ!G80g!#dW/PB&eFYhz3<w?"X")SlpLFmM|PXXEdTJSBV5.RS3@(F13qC[{Y-TwNCCJ*ve8#y7^d(`w<T-Gqw+j$H$/N59"4hQO.A(8v-wF=)@X%1o0qNK@CEK%#GuDYTkMd#%dG
P:J?"Hv!F;+_OQBKna2Dt:!j0(H"agR2@J?b6b@6H?<fXsDvxUk>jdHh&QW4W!PzXz9}d|_:eOds(7-$LrkNmBP0Sw6`g#c
`WBS5<8e]LpsH|&ov/SON,uCD*?yu+F}B.2uX^
a8d8p
2PyVu3njBmWe3/AnS#b#Wq
AQ]}D[788``SgAZ1CCR*Vvw+v{#(m>Dz,1pK@,dmO_u6`:*D-Im9E?nHCJNL8W#J<PB,y1D
ACOB0KRC-#j4uag)f7dB5cMO)cv)E<Y#=]p5P6OU<`ijQ=-$3kAHfw15<VL_At`dA;YEWh^_)47OV3<Dcy)[DVQ(ts
mm!c:`ljQF&SOd^J7f|:h/A0R=vfi?<!FWKZAWe9z([.Uc!<R#"jS@3rcmO-0F{(G6P"HYx+.I[`wcra{F,9u^=Pr*]p"N)py&bLaBMKUfLIu`{Y[/2^;v~O4$@I}&lm|GEjqEVAdCdoaHXhBL#FD1:syoe&_ol]k=q#A:tkU#N5u`|WyUT."ZNG_+cD)7/q}Kd*WQYf1
}gALpGE$gX0v*76JNf&"d&(sZz"<F56jI&giLyBrrZQ3{jTPkPMyrtkBpSaerUBR#htG][B..H$^CYnLpt8GATP0P;@Y{$-h**js.*):1"AC!Pdj"=:kFpgcO1N!2vw)F_TX;3Nl~(z^LRxtx6UeX25`VoesFXO!96~T9Qu5vhm=rizA(DYwe`=&Y&B!iZ^v|07y?8Y;;joq{@CXCyO3
e)#lJ9]0AT]*I~4Y:FOKc>(p9b]!18daq33e7-rk(f5JD~WL<xgR]6b=d$&.');}elseif($_GET["file"]=="jush.js"){header("Content-Type: text/javascript; charset=utf-8");echo
decompress_string('%hk^CG^ZE.5v]nf>]Jce[)I4
vksP"e$sSk._%<(:+l$J@n;gd9=*1
;0,cpiiV]^
gkO1//h0It!.BFd@7w/^+kWb9?hNMRQMN.bo&iSEM2}2i%?W8rOlkwT]kbsGLOzB_[fz%JgXpu"Fn_,RqnWpi]7HD_pQrbYcTt58Bxr]ylV
hw-(HS/8c=Khzj(BVbN<`Mjk:A_ss<!xZW`;k1L1(Ir9KyQg[Shv.upYdnot3mw7&Wp*3y2xmr
uXH.Q[21RT*5I4L_:jMrTfw~)8)?1FmQ
>&tSJ#!e&.hHzCQXaX7vI#z+<jDTt&@Mgw-knFa%K,y1HBJjAkZ,APu!y?)(H2&7vXsuHL3udHxV3GQ$7(r?o/*KBA&Ph3[*O(-OTxVe~OW-vA9w0GQy2>!B[TS<h%gmAt!k&ho*,uynwwD^OHg,6cx;<"y4ypcFr6iF]Fo6zv#7,6[4yt2S=y6Z"a0qxe@M:EG#U1d=OKQu!yWVeEzkiEB_g*3r!%Hax&0)@Ag]MX_eDV~ravc;~JS%CFcU2n0[AT?3sz%xryChU[zTuyB4gUJe=Z$KKH4lEkpR2B-UNE-CO49L~B)d^AD5Np?J<FBT"h)72ujiGBbvVDf`(I.8Y$^0?PdJAdZ:<SGCwj2ANT{QO>-BJ)AJ;tAEA8OQyXpY?,URlSG)"wPU,9Z-.n9`ZWJ&Ycs0ErUs@e%PZMz7JE2>RM_n9H;W
*kk78v!.OiNOnZa&*$0M0jsi)^]_90/qU]x"c0W:mCx~W^R/:fGfnih(U`OuW"M
yoGI`m#&MJ=="M4O_1u@65=vO
H:[cq"EvT:mJ#qO85UHt(4j@xkR()&]!JB4)LBcc]^q`sp1<)l:H9]2}J/_#Tb&Os%<r6ArH4MZ%1mw/]>qAft,aNl(PeH3vZm3dAdpvkpgYQ5Xi
i!u*~I^WOF0rpkQ?QraMYXf;]ktm!RlyWZ6m|g.=`GR<!44I#:0*>[#<p.O?^U.2.9!4D"#7rD%2GMXmMZuk|Ds>ulilzozTJS0FkV::nMhe.<c&#Lv?#w*)x=<unGgQtJBH9`MH4,c$#8l2a_o5&6<wnfr)`Zap7Z<]3tRnIu7jdp31W,_ZjnJ@7P1L;+DGp1Dux2-wzL[XksnVh];"0n)SajnY!W1hwxcKx4eUplEXSe^Pw?hp:bS&3SOb.6?`5cfmnti&J:+u
VXNET7<DiiG`bKyqcX3-F-fC.?C;LTu1bBD2RC`=Zq#dFYudW{j8bj]
s5w:AmoBeR
$Mct12GrWvs7TC+QNf7^_?9??oMyB@6
:t.0*Qfxbc"t%u&L!AXkBa5LmCRhhe1)VSo@K"rT;5%[sL4+F)wV~ohMdk:/e(f[:iPk,qd<xXTOuZf`JP"fk8sfAe9q^eY7Bn@<BvJ)
M-gEy44zCL>Ji""1/.*I!.k*04<ehJLG-MJ:J5c~^35rXz&Z6R>BIt&Fo24q#B`Eqzsv_<A!,;UT>q!)*f*J4,]}vb/R5=y-D#:9d"PDp(UyGA:qY
EeoV:OTm9L0f=#GvD6bsL_4_xFp;uyRky)^"]MkBU1
92|,&09L/@X*hQ
&4G_UJh2PR^U93az]=aPAEjL3e%B>L2xR!3vv9s5B)EcMe
n.dBAXw!Cm^@B7^]$+Fj=nI9r$ltEYzvWx;Pd)>2bqCLII}jl)G<z
^M.QdMg$6V+=(hKdo4WSn],z$<Nb*k),|E*w]RI[q
2e%vQI%M~V)Mp2`Kb)NMX&c:Zrh8NnXkuY
FdVc;m]r`F(itETcAI
5tP<tNqE,e`4=gVa-Nt</`D<D:!L)uQF#e9w,!wUUyqSHc
`5N$MOntFE4?P^BVR1B(.>#0`"n,+.%Sei`9M++2+WX3)n/zj:Z6bZnE)"[:^i`:hX;69v#d%a0c=ub=W_cC2LV9JP(+*!<D@iv?0!=6bE]gUuN$/"qZjrt0K@=O3X
<B>h~e**/4+%*@)_v`>m%h6if)a4C6|()p+E/VAd5RiCZq@hH$d@3lIt]
O`(O@h3cA3t*84yMEbt,<#/fNp;<?PqUKI%X+=PsRRFccl$21e8VQRPex6s<51cVh]djS4Z5q+/@pn?BwQt5b&KE29JsU?ujjsv1A:ZMKv8
,LdF,O~E15:EZ?hrIHgY(C~FC<%tBdWd%L?
zV8WMA4w<]jrnesY;<Ty9oOJoW0!;B5[MD}UjihK:Hc<8yA)"K{?7)Y5q/
Bc
IGnuZ9%"LUZCvt=C/q{>Obz>=6h:Aoc5Z?`E]2oSUMnIMnK^ye7%1Nwdq)oN}.nx;cd+wA:dD9"IaC-%_n:69S]wY5x=P

OpFYK:5.HA]p(ZC
`t>QZv,m#]MML!6-5%C
Gw,HaOELbTDI+o!7jI81Lnhc.PvY+i#C*PIRMF"N
q8$:bxpi=${%5?6j7ofGv(4H;uKErLFVW`%gBadL
,J?k]"YlPWtJ&=dd
7LjBtiCS;Xi/B<tIyrQ#_-ltry^%R"R;d3@&~<W<!xAltT-Qt*!a75rOf4MaI^wmGx72,o7vaZn8AfpHEB&-T4X/y,_>1tuna9nQ4>V2ic"JhB-t&(m1b9^Z_Ag&8C}>`AKl?W;+*!JJqyP/jNj;)?|cs^9o9eRxc-..g`"p!wbs7-!5+l%20No>VG:0Nu~/Mcwk(C$]a3ZV
MNtJo|Gou["tL^Eu2NJAN~MU9{Gzr8Yp&-`!KtcePReoaAY4TaTUBkE]ww"~W^P,%VkxWZt:H8t/5zu>aRA=UWd9N%fUG^iC]>)pVK#NEDFH/,
9p):hxnA?BcLNNl^g?8>%xFV%nkPhxcu<QZy(U:E&6uT}Z0utM~S~lE/.t![=[@DL657<BC:NR*sB9D8y;?Zoz%FaJU>e)(,U7]]-f+YF(b"1XsxM!q>yKLP{isGH]bz!b:t&G+HOym0-,=>s?si|ZQw/T2(JA5^yl`r%EF3?/P5/R{.a,J+3VELK9>:FBb.9DfJiQ6n?cv3@3|SD%T.1`0?`TA8YkWk#7b[jQ^dcZIU}HG_bGn+Z<Z+H>>/):Ivs:qDIgm&`h!tglPb2*Z
=m]g7_cbEL*Q{[=cv&yPE4oA(K1E.C1
vTYTR9uj+0>$?kdj:v;K=MCt4gz@AbP0y*=o=.mAXL*3-4#/B9&Npm8]vv@oCI3wNfX)p?Q%9dSKIyh!Dz%4%TW5H^r,QINgF;l!.i@"MTNTI$-%_R-+#4
eN/B)Gib;p7VBgg0P[IsfUvt5ZiY4M8&@Kb,4tJG.ssW0XaSX`EpUD;++|4YuD76w7kX&/4u,)+r7;EB&%e[>]1zvN%?VbF30?kt`/I~ISL=)$kzK|6iXa"g,s<N$tb.Z_9T`~58QKh`g#UrG`+-x#>b0ZHX#
Xbs")
GH3[!=CE&V)z.6CB*Fu$AB"eVR.Cds1(i$H,"52`3$k[+<;k+T5|MYu"(EJ0<Pv,p8,x

Tj*^*-j-5S;I
^?lv9
%r^np5bd=x
Ig;%]:cH[d/navs/>O$Z
=F^R.fJUS@:<_I:jLg:?pi>+-Y/6IU_HHa]xK(MrksYJ@Tl0~3Q@$#6B:aw41Ev](D/C,@7yoU0iK=r:^RL@]@IN4lYVhqWvc0xV+s
18*Wf3+u9?_z<.=&/"]Jk{%_TGvD=<-fA6NM^51}5U^K_wiXOO.@]DXfvrbpa;U5*`RS&hXIxl8.<hVu35RqTQY~ea8]YJ5F_"AaS.TN<|pbEwbJQbJ~UAbi):=yrsJd_PepVH`UhB+c*t!uu9$:S]+Z#`QP@J4}cEdoS)gw^|J]mZy
&#ugMN0Ncl1@<UIMjcGcsn:;2{k~-9Fp+|.qD<m
7i0+

`79sSx3ka_?2uFJa6yN:CaE3$7W4^]Sw%ZK-*6AdQ_LFF@cN%!!4m>`LLa3rv5,Cu`Z]jgm8-JUP_EQ=l`dl]f-K4,Yb3^kWWr&[bzUmmf.r:*R$(?631e+A+bP{`fRETw-L]Ss/Qv@.iTQZCTEWMaW~1X^n.
3M`!]&y9f`1
2Cr#_=C:1KYjht.U6zSCv0f@y:^4_Pn(6oYW8m62>FGc-Wcqg%x%H%MM4e/h]j[,Q<&Y(|Iy;=:#(9nrxNd3`rl#TfUA?}4SB]Yp"uD3IHKI04bB-6g.1iQ)K(KFr/NI+K)[@OS>L)v]nl+H7[E/rB)8I?MT4:%8ami<l?Dz)TI`az7{C~J$X
04Bt+.y_%l-hDf
MqE1"JW%?XdmPIV_]KMqe2a%@D1#W3u@Zx0Sch#a3rDCzUOi{c`s&NIqMp8D,]0yK$=/FQEWFW+aKjq7TG5<x#n61RG5]NE<BvJXoWgrklwl"u-GStoj`mb.z$SYTyY;
kRC"Y5uJNS3*@6]Kld,;Mri{x&#[o}g{*a)|F_F~K?F?AvU;LRp,e/bs9/V05u^uhdGRJPDywyMEL"TFgOk9OLyL0OVC;L,p:
j;lNp2]Tuj@fpyhuK64rxf!A.IFe->"*u5G-#ls$1w<^y$Um5h.
>7#l9^1k0)TR<@6JZLMqhzb3FGUMT6wob)M
bP@TICr`T[B*X:]"hHit;e4}8jj)FtjT=tad.{+S4pjzT&ITRHO]
Q:@K_J-eI/$a}%-7/=U9$r)yKM/t_9.;&vw/{0cf]f/$ryl9*S~>[OaW?xkV1Ha@f=4V!Q?SBSq;.-@oIA9-HDH;q`/+NU(Ywd|RvE*l%4KIn;!o4>w%.c{d8#b%O&fk|pHOB-BT?HF[coqL/S@bAsFI7B4dO`QWR:$^ZFDAaq!1AQ#btnNdu)huMqT6T*qKDH>K)I,G{Zqs;CD<Zd@O;;RHQy{rG@vw*ou-iMHs"L#S.Wn3q<|0tc9iCb)%7nUBwf:q)VJS.Q<$g,4*LvAaqK`vs:XB>>uh1ZyDRycHQg[6r;%M(C*<GDL3Q5[UW?uED*5w[^Tf[xsABv<UOH"Due`sRxV5)egpaye4uR8iY*8_[vs(@HZK58AnanbEDy+.a+(o(R@[YXO],d[z"L6?7%Vp3`h%jy"tWR|Lr,gxw.}2HU5cyZ7SZ9dM[R!B3:mwe4{K20[>MeZotMix|n3B5uctP)Ts.HuDwfm<$Vdxxg[;~y~mD+{Bu<A:!w3+k`M93J8o!t?]>+&K8SY:y&#UG_}3ni3VhM0xRg42]n3>r"YnUbM5G
{$[I`CDko=ZSZ
f[-s.ye$EO!+t35csewiM
7N%IA%?5`:T%";aqrj>ZsdD0f
OTakLs/e_=nLvysoJwtv_Kz4WAAXg2Ia+f-I%2Q->>XMWT#0XP%MAXAKiW`+uGh:?etBbW@Lb0ay)*|1@Vv5c7LvH.X8cBH)eF;[RrM43C[
st]^>qq5_ZIvz=-gFp
OH(#2s(oT3/<TUfxb..OaJd&FD
nW
^/mR1)c"_Jw*vg$a*Wt>oU9R]2W8UC@7pO#lZ
I&>H2ASl3*Yu/S0|hU9TE3d>t<QeYvVv6[g0wh^xS1l|vvJop*U1jl-:w-pp^trFpbk<mFxJcICBcR`LprsB;Cc[*m3`5&)X8^0@(!qz?F`[tp]|OG=Pg;9tx|LXRRjH*/]e&xE)uRG1][l7xx?%dZO9`Ko}^s5w+tyL)o;kbXu&0
(irnFhUF,]X@J";VZq0#!$_hbO=%uvL_vWuwte7bR-_D0+&&%vw$]fv)sc/HMv6
xaB0!??4LG<dRy.)B|v.y%r+L7Fp9z5WMXxKB?,jdUk+Lo6]+.j1JU_~qMm(Y&#-Z&)-pXlS=Sf{O__L%SW,
~sZb!+>HcI7(#
g",mxnAah@eE%r,V5>nB-5pqh<
@;Xpm8E]V/W^+p7"g(-CP@^9yf]H>LsttWc^E5>l2
U%pGX@q?UMEdr:y%EcW+rb16`E1a*N<]r(CGBy^m<I)^UXYLu9u*WFov,
9g6=
JwUap;Yr]5GA^(P/^Os-iWO]{f*8N?~uObyH[`Z34eA1>R5ebM%S&ZBPCGOnf`gUJom5jmi=-p0N;a^w!+5(<G-UfFa]gW<Wtg+cFX[84]g[njmMB_jdSBz^Myz7tK,f`F]);uN?Gd;o%GK-vN^@7nyN[2Ud+f7#T,X!P5{Iq4aTp,?AFi:@xgIywA9r1>:h2]J;+D"X[_8L?2Gt<LM&cOx]ZNPB[T1Y):Q)Sb1SGgPnJ^2ZN1j@VAvmLz(+&2oKHVVlO%,u#T:6leq*)j9j>cut=Wx2.bqITpz;Z$c^m.XO(uka#%sK7NQ
i9pIX-527s1wrTn9aEPK|dqHNOKvz3]cB/$*kM:Pe2!Z6u`nYJ,gRuL&aAa@NuxxQl
Poa|Z-t!kfL}w3PQS(oODg5JXD&0[}2a7UY*d_%-&K%I#Y,aAtfn9(_ug+rM,Z5!
g7]OKLS;4faY"`89xC^#{vIXtu9M*?:yr?(wXiavL$.;S_N>6+gopcb%QNdxwHXoq-1@Y3VbD/9eg=UYx_m.$lD)ET+!vVy
P28cDCdxC[L71;<*bMtteTlKRiuhfI|c[;k?7]0k=7~ZN
iUJz$s~XC3DSM)<Og^Tqh>5@*h+lZB
S>56KsRR_D]Xl=EHsuTbhS8vr1X6oA`)1=D99CjIB`l*Wk&]<LJkeaSQrGWVYaUDM:0sy6m,E%p$-{%pW$/7:=e;>#3jjWBXG{.fOmcep(wo`Ww7
Ix
,-+ZncQkbZjA>/@W4KK$gO7fEHe)
Oww,gm!WN*d;};DMan3sUt*$krJe"//[bqYYt_I+2uIa?&mnN7XL0nfkZ>C+<bQ%SvZ)D^1;Fyua&.Z
0<}HvSYuI[YCDYi,gJwg)g"S4C}SBJ:vR6$:zb-hsB]R%vr`n3xn?46
XT@X.a$E`[!qcw~5jTfwvBFClJIKX2aoWn4iqiWEMHB=p`_]
iv;]*w:~sqwo#AbT.h[Dm+0uw=Q7FtKdYEVvRf^O.n
7^c#lZTy[`Q4>Gj_$*4Fes~ewSreBdNR4U-dL+Q<YyVkwO[p@o&.3@MXYqZo>fN*|7gEkRhFSiMSsURk*$^slKf$*fR3~rAT!:q^efpirybY3CFBtJyc.G
k:+![^63/]:"
X@RuH^Mt.9{c~Su.H(hW9=O:w,0gExQRm>d;
,~Tu
5]e`Qh`kygAu!${kL0fO$Xn6C7Y.cT<wm
CDzb{mS65(wI5!Mc%L;-)bFww%_+6[9%GV7@]AEAn[h`ZRO=1mUSOK=eBKUW0g%I7vUFAz!wahK_I[oZP=PJ=aXnsAAjWW;5-JA#)mzsA
b%JJ@hJ_B5U4iAy$w.yguWMTq9S5qnUr)tm<DDJ"2q`8i`5K|84uz^vMcby:!Il#wsxpxc6;`h$btu=7.:9Mn+AKoKh)qP@q8XcBus8^O-5Q2Zc>QQ{vwu#5uF7Au&7jv"%^`ME+Z1`WndI<b!t$r`OpV<Ux:J+"w)/yTS-s+.mGf2skH[G(rG)Rs[xiP1}1^BGiMama5Ts:oUF]e,RjfqoT]BvI-Q<8Y^%oM]gDUlu<KGbM!+%:uK,o{-j`pX.TDYQdeNs20Rkf3:b;
Ffx6Tu
"m")rm9q.$ZBsgkoaIB2GQj)dXy3)&;G]at:)n`j}lF1KqRqb0~U<
!kbE|@+MrcjkwpM!k+{A6_b5gsF3b&x?uEr,68SkLY<#kJQtle?e>yV
WT&yDten}i>q*>t<)!6`AQEej8I:uL[65"2jzfTPG
r9L6aps6N<RaPuxL&UnU*D$tpy{qA
?0x/,7W&GF.-j3oaRSi_L9Fu+TbLW4&@ExFd4MlA`X$8X2^4bdc*bhmKHv1Q~25q5xc3j25!S-y1|PJly`#BTBz$/LoGXm[273apS([;HUCNxkZy-B)lN!HeuEXsol<7Y"9pg`yCJlR
x`H?YvgTlBZXrhU"=5gZITo4S<_q_/P5_I13T*}3Lc.1bGJAkc<L)2O3j=47?hieE$UJ"Z1y,Q(]X)6_B#jpUksqXM/xs^pX(t*y|LWnE@rRXG3cB.e^b^LS-]XH
5Pl}$!m|PB8:)hRklXxQN!RYL43VgZjZ!/iU)xs9=-EtIIP@;?7H`_;:)&G[L|;?QV)bW`s%<`,@*H,^U}n;@^[ZW=$)Zwb"xc3Jvlku]Ok{a8^U
aU@f!$ntx?lv?3-($m>Bky6$j`m
6WM14<kK2047!:X7xWdd3(I<(&m*Dk2z)B)Bbn|Zu.@Qsoe5[e32>LY8VvPAYHwnd.Gg,r*2R
fbt=zdqTiO{3{Y_<O(lYA&BMk
]?CbXV&tv_nj^j#GPJql#2{wJz)6]PJBs6&K:q(r|KU21haMQz);Paml$b;
5#WnoO/:-s;0a?L7oDJYuqj;QV%C>Km3d_]YRkX&v?YT?21E^x]uX1GMjP:hRx"uorkuxIH7-4qZui?vo]U*>2c*GO(`D=T[|PiSOogRd9g3q40N*Toc4e-
&(A8ZwiO_PmPIJ*TT!+f6+/Q:$}Qp0Fn[@2T>4-)8T;CrN]97E8UYo{0BWl4$D256`uI:rs@9
Quzi,jBi>oRD.fW>sLlhdd<fJK}LLxc!6s%bsi~-m_A>8@Jub[jU:nD@!``[/WDbv(lH<=&@k:b,]n|l%
_A:3OEn*PlA745.5V0@^=A:)X(_t2u!UxUS4fw573l}nHRe`c3iHa,KIQ7iIkizX{`ZG~k!>o7R$Vvl_?AWEiVY]ja~b"^f9x"R<#v?KA_.T}Yi>QEA0AE~EiKl,Z>fJc#d2RX"<aoEU~-]9SEiY$i&<vJ@)CS5(+;inh,>`rVPI@QYb/*g4c6i59YFd;992D>h)|Mh&e>KNkm^Cf8!xK
t4Nw<XvyMFEj9o#*UFguz<F6).,_t:*k9ooIp=<QUtWU/LNM|OM5c
+6Ut7-9?LA+[]6%(5<Cq`XvAf`"RQkQ5M&HrxkgaH&~Rc!]t^y@w<E47Cb{k;QiO
E`X_g$3<su;;yT.NsH4L.bA3nu[v]gy_&2R%LZdpUQ/+B7QBMP.PfdP$Oj+uaSQj*<Z*(dT.[=/c.fek060h9l[k5<]_smXqLqZ[&}ots`x"d#UU
,fQv9g^w@XSi3ZGY2>X(M%o(19uPoDNHGF.]|Fpfooena?U]s>FH_rwV?[5hTu:7Ep{/1q5b8k5_YdXl*-$annPGh2{CZfdQNw6Zzgs"_Q9^ZU$8:W&(@gZVZMr-w+B`Mul*/WV:>Nz7H@w:jbeY(V@8JMHq18^=8Sc@dt&cC4wO@(wZns.eXvZAL^|:PJrGVV:4@OpV8_JDk+kK$Ww,ba;UMhhiXS~FzfZ)}R<p*hZ3K2@ktO]YbSJ<q,Z<UVDl+HgfjQ7x?xJ)Amvg1hQtviGwmvm7^mndDW{>*OrRjd)H.f%J6<$3=0%5Pw=N&=HR2IRuw^>UybZ2lZFV">Ti;_z&k^q4Wmhx!v8BIWOm&&
rH%&+r3YMK:l=+qb8xMvsjuh`h?oAyXd`Tg#
84iR5=}fepWUn@3,WK)oyP~a:*KTv=n=u`4VzZh,d6J"$6lh.m$X?%ex:jWFJ<bm0Y9jlGc`gJpz&Y4@(0bY&so]05$>sux,gnAi=[3hFFuRU[8<gMN4
0r`8X6[x?WJWoQEEHwISl7OjLFF:W
mXW]yA.1w1M>[L9@DuM"XITvvK2IxYer^*[{bg-L&xh2(ly:u2ArZ
.oCfZ=9A#2:p+o]<!fkhw5m$sw"Tj/nEl/W,YiBGUSZlL<,[P{p4c6Gs,~d7Diw@R?r]>7*y%5wK4l>UQ4#%ba:.=_qTJ-xW<f&g5oZRaT.M7.kv8(nI9avz:Mf+[3kn5b"W@^#kW%U}QTyfE@"t9n1UZfq
kceDjLTMtj"GD`]fl}lc<n5:_z",1nOm]GxR%-uj5#kQs#;KW8w`"Uu%EfTV9c0:[k>W]
5W$D=#8jvc_.`!ou-GdY<<gJ/F_]4"07vSB=3pJh<[iOa{E+Cn[]lGpjtf#7@g<^@[7,O~!q?/Z&rcH`Pck%+5_ny]]&<<[9Ys
7L~o?osg6Dob%4`e3qDyHb$y.Rt5Tcz
agKXtdF19F)>`cn082O.=m!UY-98D`=s$n2Fb3Z1Q
V-EU0jD`QG3((g@0n(/?2>
5]>W6!I"9}f[^(2_:2_en=1G"0T
GGv,ob?cHW0T]ucGs|MEW%;{3g7A]q0.U^q
)8C"/{4+:f;a!8qGYk`pE)4fgAS
56A9J856<s,HX2^H0XhJ`4H];L)E,@[BuUhLCush&7wipvS&jq0m[}j[75Jn]6n-=Jw@y%Dbgz^?OmdSz),wjvX-lIZL$R;!Dm/o-.RQ)k37-?DrnUc^i]hIr6xVfyrWQ?yB!zw.ZvcqD@fv4b57Zu,ju@(<c{9FklR!`CA>ij:=w#%t8z0[R(P[qXY]uZjDnU2.`}I`Mtl;/nmJ&X<gN:E&rF[TCcvk3bDGnAnqkpRlmTqj8Q(w*mn_l?N0e^n]j]uv]04"E7@kiB)+(>2[D!;G*9_h^xq_AL2urPSMc0)oa+
h8SiOj12j@2e}g*&0PiCB(vJ
yVY9O#6-9{Ol-ant^v/7vlFA3Xszhm5BbSGE0aj$UL_#^>n[
g#$L@s[JqmJV)Q,A%GW)haSGd<-6%?E&}y*]ZBpua3:u%HR_GRUw]&G;4>[qgb!B=lu"vf:]/^bGY25y)<dM@_:s},f.F#2R>mjc4/K(<2`n:"oQP%zqUB:<|*1W#J!^|mxou+Oo
(_S[)WBHLp2af0v?ZsC4jH2t#K-:Q|L5.wYna+1HLM^_pm<2h)!D=N;x0DPl<kTwHC%yxNly_D;h2O;z0O]6<"B]:Q_r0oHp1cwEIVRQG{],<uu;5*M?Dx*]cB,1fUmEBP(KAEXFc{vV@gvCTb322iO]X[2o7XCxSiM^).0_mz^iQl67#QevKC[ER)_2
q(g#{lG5XMGBcE?c-XDb"uY=Lpj#P*!p7M"7pOJK]LK2.ZLaT?&/~SiFLUTOY`~*
gF[R)8UMVX9<Q(SCABN7rTSVb[%ma2O^=A8VM1clBZe6V)8YU%d<!5Hll|6Cvh+BoSQGh&ncQ"T/O&xRVT%A[fG+9$&pjFE;-g^[:2Wqm|:EcgUB={4[>nC4
x"b
EO*lvw{=EbrF1HMW5l%c~Z@58cc];TZf"h@;u`N>DL4pefFwJKX$]gj
F(J1Unqa0E/[
_RZz4VmAwX"{LyO?+LsbJF(<M[8f]-a|`]7Rpd9JtRY/]c.D,tB3]K=)sAFK+;ms#V3<K!LSk($MLu]w^#DdFRPo:~i{%.m^H0=MXonWaOchs(`>]CM6mV!>QXD;SdW/QF2A2+/!0_WCepLyK$F7<Qx2cov$rV]H?kY4OoMJ]A`$^Irjb^W[&Kp
(pgac([<).,D,8wOF!$<TD>D:0y-I2xKj*qmX^6m:p3F3]-Bt.Cp>[wk01F?w1-myGUb,uybo#t.MgEOaH7?-eT&cWQJ_f@*f:`:@<kd3q5yjXcOF{E$Idx[avwxm!
Qy_A@2)vJ^zUdH$XwP_&aoBLHRBg/c9CeqYLv.>_:6e"_B"?`Rc:KM3Nq1QPx[Rt90:
pmK<nk6I#4_Hk>7T($&UIFVPt@djPwtade=b@V;p!:7;@w[J!=bocNR0f1!a`X"hAHz7Visy*q/]"nq3jeaAzo1#uT%
:iw^^bWX6B:eWrjf|VcYC-@mhBJK3_fJms^PTlo]vo78;7026pb0PUZ*q4z@Vm+!306vXDCUy.h>jncg,bFqi$U.|0kc[;[>HXH:%VsUYlpW~gEp+]gyzP.l~k/KUC87sa<Thot6h^Ju$GNi,3"x*/557g~)]tHJ@63d5OEbAs?txTLOZ<Phki|B3!*+=yd^:Ft):U65$>YB8s_Db4;r{Pp<_qYTHK"W90=Nq=w#CK*w8&s&B*OHkT7H>^>H;[dwvqxo]w@4W%m4
!P3MX`_Exc/5Khv*":1vlls)4%+B75[}JCo8ZOsR3uqL(l?7R0UXP$-.*N`B#U`|KCmX+Nd=8ypo#n0bai[9.3bBEBV^=No~N:G7?JS+9X,Z$Zb+f8aa*kf^qQSTb{wR)?F]
CrLL7)bcMU2O`OblrM{6YNWxP^5XrjsfH,A+gGRR%RfuFt7S:,nA,CDbuGhN`r77x@CG1vtx^xx"e6H4qIA>|?@,]nAa43"7L`Okm:&/g,K8Z,wuWjth_KzK$h,@E
K$fbZ^#5!U8BkIK9Gd##O^/Y{w[v<]=r<ilCuvwjOtbyti5yQUB8L2aW,*`QYw_sbSSb^Pa)aUWd/I8"K"xqbkrpX4u4`q7E;=1c4`,23r/rLuzTWgnFex#4WH`wZ7Fx}TlLi7w@5u3QXRq:$ne)~"DPV,+kJ>n=sp)kG#l+v6OW.Y&H&OCvSK~ZY$AZGvm+<d"GhK6OEjV(rkze3e7EhEBs4].Iz7nC?&[jw;M,BZH^Na~z!sLFBnmXrFIGeMb76b&td>iIi5H5BraqI7J5mp?pkR=5X^Fi.t6m"d$yj5YK{t9yhAxd-v&3Wfynoc/&yfBsil}k|<kAVaks[a}FTNC_k3sIUExoxb1Os[/UeZEb]9,Bnr/"+yPp/o@pOMVi8J>WJrIgLw@&?neUe`]E~o{O0XBy;iPnZfL*k2B7-]`qW,$Glyw^Ym0H}8_UF?wp*%%C;]UG!JhpkP/>ei-/4Jg#"CUq{s%cED4`&a4Ugh^"6hGM6luHg?wg_`jt"7dbyNN8./y<D-);l^$w$N8]0hxO1KdU]]Grke}-t^Y.j>V1tK^IGZ}lPS?h)O;a(K<L2iG
yK+mw,<H54N`xw?%~xJI2`>5Sl/!WvFmPA)RY:AOaM`S$I*UGL"V7CZW"HKS@fC6kdw#5ifR}H`+-u0x=
=`]kvU8ygj!+yG.qdD=amG&S.^aR^fILpEI>yuF`G7J_M%u5q
]4hl]2>Y*V92I7H.AUHjg7y%~>Ti8p%@em1]DHrR,l%v2j"jM"Vw&k96`TmUJ@L_Ea0ugc2&x1heMP]mm$HRdyJadlv]%3Xs-@YssX*mh54L?CgJy>;#f@?Aamh(Qv:^J-DGl0Q4S9fJI4W!&
.F7yv;*I2=<_gx-38[B6E>I??W3L11RyPn:s3ux(g0J9yIm!?q@e8JEi4y=LF]lJOs$2mH/P^D7MTG~K3W.[Ly
DmUVB.3c
&WzfV&os#JULP8^ncpDl-G9toMifIW:9Xe8l&5!vrbvI%@/!>kJLtE"O+,Ib3fjNr@ytF>4Meunx
FM#jq$P}%Df
`[i>6gV:n:o&5GFY5H8YFU+Pv
GT8Zw^?t4"qnpv_WIzBe`^O6`[<m12cV?!^+wPo.X!cVFaTh8AAb.*>
x-=7n:>,xR+x0Gwpp9`/KS5r2XOXAn3q-_l3E+xSTf*VgVo7qM`=Q
Ugk:D0Wd=oe{Y~=qhg9d`si7.NRBrVKuEwQ~`+<-C.+L9q5n._$#VO(J?b8hJeVC$Z]de)48j,MYs,9>mMkDGVnkVv]fK[t3G>YZ;d5<pcJDRhLoxSZ7)r@5ECliQYXnqzQ1_sX6I_8oUT_G>&v_!$lv>uWPcX,sGpsOhlG]y+ji#)yZdChmw@M8@{^a6s1DZ_A[a^=b+MGt?Fc@K!afiHwpd(Rp@HKv<]uiv)?q3i>}1K=Q0]6UuecFeNp*$:hWJbXKkf.[7];txPp0XNb!W@8rSZ7vlOqK<v.
h<^(-[5SB]q8^,PYZvw8WV]"LY7BM
0}^>m(uXAlFqa{KtGej2s|/0<iM&PaF5w6u^[tu^*L7&jTA8NFb68&R-!1S#,c"hX,f4.<q#8q
jz&>KN[<2wH!rj
K!
D6QTqR:cutL=~o6D%h+5NGj
Vnojx>y(ha^3Fb
1
g~X0IR@@$c`zs/G3$;Li`0v=(jX-$9El#wgEk[G2j7MUV!RE?u"NFB7uO`:ZGV;~),D)uT2JHg!}8UbMogg1LPZl+95G*lCZQETS7WxanmO{k?h/B7jJFyuE:rPR^i55OgN*6i){]ht6AH/~SW>32)Ggw|76W$FGE;mg>9v#-YugEm]Q4G=QP#B|Ic%36Iw_2Ll`pxFTNb`FFJft!4dPJu(!8u2}^c@-%OK*v
&av_rWj{[5d+p@46x_PM^)eCm)6|2F;#yd$s=iseSt3~i>1cE3&]9c47aSe?%6$[r:mVMl>-F<X9sM$ZK`XwD8S-M>f[NSd*lVfO>HB:slF/S+=4s8"kji1^hN29YKn&1BJAa!WnG_Ji+EvU*eLOY%0|>YE^x:5}P(CGS]F
SC!K>:b]G33Umo4>EvM%Ehkr8=-=43*jP$MdsX:NQ.e0?|x|ch1k:),N-W^RTFXrj8HH`6a&buie]Ca.L;_K@g5opGh_<#_{F[C1DFL.iCRDC)HWrd,8XMAxQHHBTGBF)6(b!ye@WES%yCrpnnVb(<:[D8y#$wmI,dH%#|#;cvCkufv!
lhkmPh>XAyMN8E-KztzrBFu+!F1`m5j,nILJ5cw[[0}OiFDqnYzolMQhJ`Lr6Ff1?g_)uFe4a#1Uvw5Fam/Hs>K3x/%@`,5`ZbC+^lOYNZ;5$wQd33G$*W@uT&;id2mG^$qr$]aYe.ReRe[UV&xx|<1Z)-A%Sh_F3u9#HyD@ev9YCSTyPQ5,x_A(XkAD3rkyI*s)qs{d9Y!.L57K,U`BxY&1*on_d@Vv<bd,?_M0vvC;V8WH(dNr@`|Cgk
Q,`Dv
ZB0,V,aEr#O;/=j==fEn`af#UNw
$-G[,>EPKT"1b+ef=e00E}o_SHdvx}mgONFqE[$3I;/,MwG/wzdd"6G[?&3{v~F{V^Apg72S1To<QWmj`8MK`U_9=;P[^zPLJ"/@/
cF+|#OAn9pT*g]w"kMQ]pe(MV.kE>^SuAB@_M*l%2N/:"v(jlF>x3#$3J"C}5bGA1YAEZ=#/I^Tov8hl%+Bhr3Xi^f+YBs28A7bHO,EQw:H~EXPEx;8O0y1/.4TtvI0LBF<WJteH=E8(Th<yR"M)e7YiaWiEP2U%RD86En?3oA=#GG;:DK0%Cw+!>}@C=MC}7ZfR?{"lq6@4/J3n[IR
X5fRl64/-IL3pbYxE{=N"DMj!Yi48*F1M>kfHoD>a2%
Dd(]3]ResJ<8o/

ui_+Bb*zQ2/).vmh$4SqPq
21toKOY`x[%G_l(Y;/7=UK=coV^^9FWQ#eo]"$
MVSQ.(RQ9s$[,eor
?%lFkSIm#SgnF<`]V^aBCXzm&
#hnLN(0?;)NyZ^iP:R*"A%4rTY>=KuOfEg?*0SX5ghBQR(SU+&xW/d$ZYJ"rIR2umUy,]U~h[o$awwaDCf1CPl`NeOWcc90ix^j!VnE%H`x6Vb%MFeM2%7>Z>mi/wauH7!)6Hiy2wddl2d-,Wt&f`A5nITe`I3-(|4W4_?5oCGf;S^:e->s_QcF(ht?D~8>l@qzG:g89X6!xQ
jqF*y(zKoy+i14#4=HD8}^oXb@1t":>tu$&Fdm":u-et_P8nYoH,>wzZ8+}5zgcjeVKntmF
*<0)qt+yZp@8^>C$]miy.5HB]rLt<I!&oae[wAi-b).@H&Z0egYI%&a+_]EcO(uQ5gr+Ab-lEk,qQ(SZ`Yi
>/6D&-i5X?[u8NIsasj^
qxj-]mCRW%)ZgsIbVghy02
Q)%HKmWDaf5`cbA-e5ePjyAN)4@glBA*Us*q~qfB953e<`qPCm4G>W&EHlisI8oM)SJlGDaIeNNUe>d"0
;4<pW<Q@?skDdY^=+G*G7Ci9*<Z#-]"[.5oMJ<^KC:9OKXYx,;p2%VG5Xo}%x,~x.<_iZ4~&8b==6GO0t%z9h%G7n2=8d83MPPA`
vHM*ssX6tki"s4>3$^E9Xe]|h^YMhc4P]O/Za-b,Or[wumI2wPC*_.eEVx!"p:cR_olC3cWz!c]1`iAMP.L8@rh?sD/jD(XlUy$^yJm/hlIr+I!`8|B<&5cNZL/h.g)c-K+yB0MgC1th.oYp#D5DfnD-f<DK<osTc5e39>_rmlE;rEj;eIWR_YThUOf6p9$tso2Pn#5{*wkzLI/|^6.,Pz>Ms-Gy+D^S=}dBv;#u`a*b:J!EWDgWbu!`06G@%ARUx%_
nE6`pIe-D~-ZYl4dL*$.<4y4:!1xC0"x=@XL?5&yrr-C9=c"%`8d/u0Fv[1g
{v3ezn;WRW7mF%J;s1*kO<^!E?;9Nkf;hq#BY=]/(j64~TN`D5tt9QyL$<KW|f-DyR!
zq+_QX[%~i^&r@x(+j4S4vcWF1QRvePRd*+E!TD`wvS4%Vl>->aH
"cv#h_SAho;909NY)p5iR$5Zb[!xs2tBPQ=#;3,n#2PZhno~F?Ui6c]&#5TXD|P~_<U0Pptgk
vVa/wmf(hYJq2$L<PEQn^JQ[f`L"C@eGp0kh!*&h0M+@OU@m];HwJK^@k7@w1y985F<EHVp^rf(CdZO2oaunkQ,hoc?E,N&OOz5nj8ElmOJ+/[JgRWodrJYkM^v>_m&oFP5j9F;}SrMb_iHCmJ78N;>OF{N^LocBq"*gSkeJ-aG2m_kj=.B&gxk!*f#2.+Y>>JyKQ_DbH;^#D$?+UD$ee<:+2#JCJ6N45:-p1GB|cKJ;Z>7vDO^^=cwh%XZ(D!l:n@J4ML
`A,;SF2aatM"AhXc`p}
]?]UYwkCTg+J3"+-:i}y*ba<`f2l*.&sC<DTDT*bnY
K;1Ei8E24fI*qVEzhip(nTbSK=wwT^VBKUDq4.O`_aHS"FF*xqVnPL(XNrgKKQk3j8S
2cZrefkDylB/_,+sYHy>VAc)8}KG,~RBvje%cVwQ-V,Vs*d39;N?<5c-17ryFH;,l*t31W*)vidNCg9w%R%dbRZ7(K:vy!s:QC@MS:7h+9WzoLZJ9.lR;Q
osY.7b#:$:<5#IMr>U_WV.?6?*jml-5;Mk3iv>W$HR>yQGf*w0cioi&%eK/!LO@)Q4`YA,1aykl$x&uW9P)`9lv=gj,a@lbI^QdCJ+-t~*/;Np#7ZF.x$0LH%op6o![rTtq`62*iH$A:ZTZQ2"^FQuP3~$JyP]vtp379*"^-xM0>dDu(kh%H0)!$J!6^_=uGMZC"g3`>]6d1{X["BKE#=uiDZ=6I=C8uWZ`&:o>r%ie!.^u%""c?bTw1ImG(qg6O@XdsxKfH[y:8;x:9X9:bU`+M%u^ukmf-^oOEK=Z@p*~.aOCUy@P)zsGwSH[fP:kw]"5V_u)A4"nD#tqaj)OMZe5cD[)=*2zx@Zj6N$eu^oZey/61..a;s!E:EB|5SK<I._4MeQdvmT1mKf,V#s6g6#F3|xgAmmsd92Da2r&L=sCV"h.Gsn2o[N7bw7BLZ+K([fQZ`CG>~StE1PcLq[Ob-y~/0hVku9(ZB^gt(iTi#czi_E67B3$sx1y-ORT":PC:I[?yPI9qQkao#D3wtGz9SySlkHUALyW&qI8!VRBRDVA>8Safh&EVVojtp%*X-PY,x,HtRu:#cZ];C(6(!F!^q1`Whc$"BsG)Z,C+Ul
OSZfm1w)jqp[s$TAqBYEZ6nfV5*"y}$Mr]!,k;j$Ru@Y4bRu
*pPDu.8)$`6Q4;@[7$_$5_3q`,&u^D]r<
sn
s3X~lLOTC{V$(1du=$:P*Vz#r,L?HDk[tK,Oumm+B:U/FE-/-1Xdev,yrVNQ,*?wF/I3Eig)9&pm[7:Eo_=
&"nr:[oc
f/F`u5J9[]%El$_4eS0qTM6QWP2V}iT
0
T+!eJ7&TyZ_xPv]H`N55X5hm>V]OeyFO<G:.@j_Z36p`gPeURS#,WQMEyX2
yrWq@*aJ6O&eQz"AYq.b"nqq$rzX[e28a%r`zlTp>O-R4wC!4HDZ52d,h5P8a?V3o2<D.$k8xM"pENMz!#Tw@&}n~Z3t-X:h-;DOC1!;55?G7Y]ZC/yfiSS]RTJlMToe*AgdMYhK8L".&SL:zWNfI;2v:9/w@J57B!,ur:y(Bmaop=FFO7]`sdq;ThhJ)m
_i@NK6WDYk"q[|ATs"=q!;vB/$<^JzUQj^X!7%bH2/a9!}b|3{i&n
*]cJ%$D9n>8emPm68BO2Y&U`eFto3kf6r-Kx6>i9Hib17fWx"wZIr<wY>%dj$M-pV^24.zeJJ@!
[]KbCnlI(`9^f)4UqY2~>&4+U7C`;wb-yh2YylmUP:Ui;T@koRuvxIYD"p]>R85Fiy0DiD+[.5w?Kxdrq+!^LU4""FGdBFIH*NxLo%tTK{daclY7_=IS$
$drU2~3WodE(Pw6M&?!li[Vs0STnSR,Nc9TOL__nbk/45JjaACZQo{4+o[ZziX^fZccfZF%9KI94dmPj0E_~5%1!!c2",.TBD~Zy
t:kitiZC9q$FGsn!:QU:x^#=MM$.vOz`f2d:7$/aGfCmL
dz%nR2by~Y3eQIJndOmSpoAbonZHj>r#y&[nV)@I.[m@-Jx!i@YfFRK
ftlH5066wAstj[5X+NJ.|cxGq+$t4<YbUx*P]RQ36:3#32
/p
#g[<DBsq7fZ4iU:t]h56G3:
n17X@#1K8Z&&PG9R}Ha_(Pt3Y:}c=o(XkwPJ%J=-dGmezxOLQN)M)(sJ02ppD:=*7pm==<_u0Z9>q
*(=tL0SIR6PSF`2t^#v]E@&o.3k5r^fIe
h8sYHRjm%-`AZ[t.wDtA+X%RF.,fU=lvp!jmp338v36(HgnYBC;r8Eq]/7E_77G!}T1+!8/xu/,ry%nLF>W<$6AVLF1<hjZIh4i1w4CObNcK6.pI[W`y)E!dF"T`dZ;I:kK2!PwM"i9wG$^vSQ(r_=FJ3K+uZ?V<`&%2YmT`(M2-="|5/h0kd_`$+,BVOAP9A46vf>/:{<M`fyzo5_j_
[E9Yl"fY=U@cIJ4br+9"h@Q
Ob1^"}Q0Uod.I[bc3?H:mw$BV$Rk5It(3eE,2$N*Be_NWkv(1yh{)l*):e%Ijxg:_;==F[jLlFKYP*QW?Gmp$yDJC:_:Wh<Am8>kA%O]r-ZR"`Ei9.f&,u
i/GhL,WgwA+0t95)IFr9`t/8oHj61=u[0r[2igu
5;)Md).=nRvFjq/PTJM3;LI"%u5T!HpQ;Dp+v6rfbd%@HuA;4%z;~E]REtfREZXkk<W#.dVY/"s1smI)BP6r(R{Q]3V:<y&/:4&.rF7%.t~5/[z
kpa$1ri%1?<Yu?OgY/7q}xY?&mQrKIL#*4Ch~FR<pOZL;
D*ij<<Mniw:GyIByB.es*-4;-%y4~,/%vUwO_Mnm)JUw4o%>qQ)5/471yL&idHwI|nnaoxsR/!A$5@v!8=jU|rG3Z:yB|oX"NwR&:eQP^EcDJ=R:9Hk0nFuNrLvf}C?9Ooh&BN{w2ImPWBL-Zichmby6WT)C.!-SnlhUS(^Q:flSB
/;6-Cjr:{8oO,mDv#G+8TsiGl=Q)`=x`Cv1x/0P+sD>.25rFx*"7>1c;=p_A(mAHVO]SE<W(m%e4>GZ"LPB!#m8Fnvuj@e#oTuZy?o>[,Bcbc7QU)b3>8trZa;FIon,U`6+%1tVAg9iuXK(Q878dfAlJ>`9LACuO.BpI%u8^*FCx/"Y>!pY5vdmCS[WOCyy[a;|irx/+OIP/(^a]l]<kpbO-H4?5DrTm@Q*0*r;kL=r_G`I?.
~>>m17.uBFG/"bf,W61M}pa24Q8F7&pjcB^%w=S1lWB-d8~8gczFsl)k%e{INfdc|OiM{:+baLV)#e#$oS/^A%7#RTr!#TW2Swy=kh1Z%vJT?Ja(!@+QjfZ=pvLqG^2
sTa={UNHc)nSnyDUGQI1vk^6qG9f[2GoF+@(yIAlp2)qC09@ktOUW)e1.+w?SKndfY$cxgt4f.=qdIk?5q>Y}cVD`7X$=B-</*r;MD~M{Q[Ju]`U@3*lwcOasQ"fN)
VonS8_U5<GYGw.7<#qM?-2oYK%?{V/c>+pQ1fSIZF&Jj3yd#"EwGiUY*>&g97BwWI]PzLo8C8M&**fdxF,x|EB!X9&=L[~0~Dz2`(<@}/IrqeW3Y!CZ~62nh6LGsievA/).21JQ[Am#]lN31du@GOSc0Vk(c)p3#b"*_!kj~D&">nff57jTS<@#+u4Yh6b1m))WlS~tgiy<WSh"FELohnraJe#I-Hfmd^]/8l9uy&5)?KV*A@aIi3WPz"Q6Xll/~G"`+m59i2LZ#>HXOwck[EGhQp0po#1KJ=k-Fw],]I/fm1;Iq
&]1$d`{w0
shNJ7)bgSC;<(h/K/=t`1TgMg(d<tN"nJC#9j,AqBC[?="%.Mxz3nc{y=1dkgn)EQo[2EII,h!oLxMh#z68)xs+=pWoeq#HB?2_jmSjG4fTEVZxD{*}NE?M5Qf!`(@4&=GCi=sKU1<*f&LK,KY90v_pys/m5L
`N{$tNA%R4
aV]?wQce[>2Vn]r.F8_Dg}M6"PZ((M,)=gUh0v=kL>4/R-6s*G#1C.YFeM2s!Ei{W|2Gf~6P13>[6:<OhTjKT,jk5|bzPNf~r~v&]m7z#y2;1C[]WBP/4Y8xWJVKdt-LxlyhuED8
3s)j5(MVt1O,#>}jZ"zL7d^aa,`517aleDl-u[@OhM8_$Btd7:f6SVy%(j(f
15g^*.Z73@rjW
`0V7.}XyD6Q#gNd{Y;"Yto?DC=nu?qsK?EX/fXwE[yOQScrit}8+&QW;Ph29UfD%1-O1v%f#Utfm]+bc,CL6cFJ"[1IYL(O1[HgB[/#*r}/[Gh.=fv3h.-TGkEed17JCWRv6@/pDxC`piT_qs@)D,FQIW3v1E97)+GXy^sG{:b&ipI=.&sR$XjjK`&I"pTfQ<Eb*+cd{+NlgVGw&3)?Rv.&@i5O_-Mi#Fy3=!9
w9Q#pF!G-y=Z6=1;P^oQJ=k<$bjPcDYqj9X5=bF,!9:q794dxvfXI8zm?PGuD,eA;l3]?nq7FW%V7.@Mo0&vY@XkG4he+GgGpv*&>&:0bNhJ=-ipS%yUd`^K`o#_|re+v$d/GV(wZoIL=7tcU.zIP$2orXdC|$[N+Q4,B,l4>u>Lm_V=;nKW^5KZ%1qqm/78u7$6>ULpMy6H,)+36USv+_q_bjme8y.94B]E3/|w^O|47wT@:0LddCS?:
smU(YaI*NqQq=J!V+,}qIO+=dBk/wJ:eD`]61#|S*k@s.[NG";%3XTJBz!F[{_1G3YptivO#8H0d%xz#*!Cm}E,isp]vQ+s,%5Q(spW^>CkgfZB:deMix0Y#aClXU60b"WnfdGFpl:eUcWRU!.Y%6rd
LpUMVdNOHn&#>7SjxPBr6Fw)/Z:c+4d$Dp18NXMQVq/!WdI(>krOu6>v;x`UvtlM,^qb.em?0!00&NSOQ$/Dh("Y2*Py/#&_V$:H%1-h_!}I
i8,6E11*P+_~c$RA4?;Ogm>R>m(DO$[S!;XJ/n6x:s3~J:a5$c2|hCh]vuAqwc-3_Sl{d?6=IrL1h._Dm/6*HO1^#m*ani5p5cEmh!AoOzkIK_aSBL15&V^=TA,4+x290[qO8~SF%Kn`h_ef^h_NXtVbop;myW"M*uCZ$$ieYDYd]&cguOn"L{4J0dlB0opempBx83gm^2KrJ<lzqMU|L)9d:?LjDp/&An`4xRCzr6hF0W;p7IVD]9Z%Mb${PjuRxtf@kV8
@.XD3Gca.5F5K&Xfu_Yr)HjyWrBGkMJY
*`V]$xe+..;i}t(VNbapc[vOQv,-4G6Zupr+YTP5nFvXhWyRb!Id"MS"XLq
c#LQmYm!)"sbVv%db_$7FE,X?pFAgsPOQkI*qaga_aKk$acQW_BZZiZE!<p)lHu>"Z.qPSa)e[=^#Yr;,M6vbN~i^#H9O5T:x?eNs<8_al

A9PW;+?e,Ij;r6:]Ao++k;8hGHg>-
U1sIP/5$&C/#R6:?CU#eB`6$aZ(TD4KI%Uf9.lJ>.62Y,S"!?hv)0[&k<
"O.3iO~33Vsi3p:ep/8$Qw:rC!R#OLH*&@%VK2/.I1K@>/s-`;iE4n6o^=pZ<1<Py8bU<JDPs8l+%7qdW"R,{3#wLoZ8#L!"tLq%NCYU#`f@&CP;x]P&yhAjQQVRn2@NsN{6o_)(+AJX&d4.J_Du^Y0OO1ZbKNbWYc)VxvZM;bayDG/,[f-Pf8V`,Uh%x/2>Wb-YYU`57W/)]J"`=`[r#"nk`^Idq7a]yd
0Ql>:zIp!.mugwW)BpnAPlnWPVC)GjfA+P4)Rt:s&;M+m+sotq%!>g&
-LUq+,"jJ:q~[9jbwV*q3zdZ2#%cM
Ahu_hfxTDYsTVL4]=3LBkKqEVAY9RVh:HYso8ndTcWG:<g,#7183
%7of!/)hdHWATi7#/Ny?3G4!-7HT?Zq#,YcU8;ek~=b`L/CA}%TNzkiM.+GG#RZ^GVjT^hjkd($yy9,tkHM2cF~`%tnoB:sNFf</3E^fDf@^g_>#Ux/:q"`6+>A)UU|5ph[hc]UR2X(+.T^U?Q,LMI"1Q!hh;x!D{PNDUc9)+?W[5+Y(n5h,ireI.!Cn+tpU1F&"1Loe.3(T)UM]:Q{
^$YD70$_GnqMCf{:WoXG@h.a.[c7DTOD,Ar%xe_AqQ*.W<9/|?^JioI_1%cnDcQy!g#PBpH-;Q(ftwURo.z<#ccV{]yR.[W>c9==?twRrdyD[4r[H6Z4+`MWh_O4:l/brUtcA"A%{@
QolXHXUM;AjQEFg7#(:-1hE8IM1%h&BK3gl|&8Pyc#Ug_#ar7h[{jY+>+s#w1g/DDm>>WS@=?E_/QhI&hhDUP1vp*[V3_0+JH9!D<=NhcNZZjif
"Uh4+euGqBXt^FT0lS;[F*(x27L3M[;%Y>^doH:_O*OO=unC]3On<Dxs]vE*,RO
2)OlU[6{//G{0XWX$3"CVQJwe
#m5wXga8PvE,PQ4]bA@W+F_K=OazXFrj6Tq?
8JhET3~.eNU+F`1V"N`aiu-N4O+rGgz0=pCR+6wg9<"],anGvkKVP.)RZd7*0Vwh`aS6?me^/El;KCzgl)~Q{5C;%=.k=>]R^*1Nm@DnSozg`.@XDQlPg0R.mSaI0,-aqFJKb;_sz"0-1TpE6#$8FukW)O?Dl#d[r_RTzR2G4QG.!mosZ.N&AI"Z[<2deu}.Ne%mfd[9-S1]#8wQ;!chd6n6#Q]XcI_DR8*?cYYlE
"Ws<4a1=6+kqQ*H9/[C/}5e:%Rf9),kC+>f>ktL%b0.h>N6JZDoeSo8=dD(loyN!U<q)WI&R-irJPR>sRWj]-z!d{4D;OF7";@leEsBj5#*nq0p5[/-VD3DLu##AHE5e,a+J^LUVD-_Iq,Ocu!sHW?=l*0+,-PJt(hu8u?Ao8WJY#myfqH(
o(@k)`=QDGuCOdQ7G1HwF%L<?PH5_bwP
p2=|&IG*%":(Lb&LKU=_G#)]cfrSU.ABE1TP)GBjGKFDHu4(Rx>=dYf;I#hV!<e}ZdV/VQHg;mtfp:bltgd6"_>,OTm.&^Ih373D_f-+<@Kf^c
@o-ipl%Yi9g3(Ug/L,}MhHybWg#?s?mJZ$se1UA,SPCMCffGJt9smO$Ko*Qux3e%_-{ShM#&H6wEC"z.XD%]B&%P-7Cew;>tL_i",Mw_"/4i|PJopo:PMFb=<4nWn*Rv,K]#csN@[4XTVAiGpVJnpcRRQO(-p@m,T@NeE4v@#$AON_~TK15.tuP;s4"GA`FK$Ya6FgivP?@;0o5!)3p0~j9D/L8Rw1JPHRs&QR/ae;3=c[E/x.KV5C1<KG6]4d5sT!
Cig$RJ#
l8(tjU1[m!p!r$7E)(425N"7v<17`qEpL3)Y_uip1jVb?+S2$=+S8GpdGW3Xw>,^VS<48ap2muOf1BrtQT6Nt<*?Lj%QpAO)%4KO!hCct8[R2Oik"vDLrQ/hA2p"J_%I$mn3lv`V09
z%4Sn8z3u[dhMeETJ$MwW!&Wx/f@o_psk>zblcqV[Ie*2Lt4/5+gARX7J:F8:)%p.-qTk4:G/8=;qA:Cj%Oq?I#Bs#=f|U{f&Bi^gIKgc#IsZgN42(mS;o|0&YioN>I4EssCU@@fHtkD-P`>qQO0{w$R%H1jP7~ZO1G&2N`i31DT6&GU`wC_<hukK0~L+wZ7CGOa)7rhD_,;`82lMdwV+ewGB<"^|B$EC?Zif.U!ieA=V2)g01VY:aU#x$a_E:^NXF;sy1X
3(zELAj49I>5[
,B|VKSRQ*1ZbF9u2Lpv4RS~TY5pX}R~i[VNqg,1NG5ETY/%`1!t>~S{[[gs%7>1??tL.QQws]`bgmTg*4anZ^]D/MELOU$HLN>HTQCVkj;4fYgvrs`P$4/-cc">BmRih=#ksm8,R:Q/_"n>Ht/k"gX36KW@0^wmD</
&>62)kfGpXV34)5_+HPb!
4VJ05Yd4oop_w=d;WBhLv5LLpsAT5AydBS$Zq;D
cO%JDIpG%xV%h,f8K@H>$^l{+
,W;pCt)+u9g}yP#dI`5^D[eb`I;aW;E0Pm-RgDRA6m2r/8U*N8h1h!d^fvpyVlQuRR1n,+9}ZF.#K?w?qHiODYeP86lsDf7s*?i(>()EZ8RA]bRM:"<r(P6|uCI-bm%-0dL@5MedZ[V(G#hzW[22*r%{asQPD,CDmN.&5Cy"/tp=,m8d0r5mFm6;VdSmj8_u?&INK?ef8eEkLw]5<#&&w!_86X2rD+fRgQ1?R.f%Aqoz*O`6*Nq=lfI-q;<IVo2T3xJ`D@(-$#"L&5O^:jJ6wl6y`XApQ9uA]v8SknHwS;$:</xkGIF&n6nftLl7p15s`EDCC!VOg7N=m1Aq+:`U@S3aT`Xm#TelDt>[Gm9nR#E$$wWCp~#`jk(<XH0Oxz:):=QfQB&UXG:Lv#aa5<_U/m;uV:He5>sQkK)aT]v7^!)4"P_*PXLIYcK_OMMI;y(r6fJP?P%EFwtC+cF&.V>triAk
N#JH^0M2xO6#^g1+}&ZRVL^3M`g#}dGf<NRmKY7>h"BD#_/_*mJlKk.iJT[y[UXphb:DB]xK?*Yt,M[u."Tln5pBkj,RhIS3m?I1cM^;{DulaXb0]J7YERGu7Hf92!rs9;K+5J,Nxt*Cbp-qKFj,HTh.bLI9wR<?asv2:)JG+l73Yxw2*)D
zg)w]${g$3BGhCZaZ2By@#6h~G,WTFg^^.GZ1%p/)Cvi#_$Sz%h/lhs(II^%w<g(#uG[B(h)]#QDM5q.mjKb]$"xa.=OC0"<}O$#(Sq-pZ=(*K2G2?x9Bj<GNQ7Co3@
F<vP,ZUq[%~LBdyu+4d/>UgHPwg^`d=Y0?$,M3UYhXs!wCGkXC!Sww^ufOUo@>-A"E&%lj8YS-p7zP$O(l0uO;|V@88)Pw3Q(v^uLJA*l[{5|36eeO{vr^3TN/_7CG72/:#C~6Q=E@/H5j>9sR-w(+U=?/8mrGA10@zT&wDu
#p,3o-pW)U+x*_-G"QMS.xBr:YtnILp$S=gtBaR/F;./N(U]<Td)0sQ*8+x:]&eT?yO=h/eVSs]G?_f0R(W,1$>FPJ]byta&<()HN~7Fg7usE3I&EW]yo_B&VK_vx}@:#G=F;G+1oHqS0?b[:)2XHR:2`kO@2HW#!aDibcqFQ.aoP.U/.FF@t1,PX+DrJ~9LL`ks38EO6"2*ewt98g*rg")d(i*W;|DvB[=zEvY?_i^p3;Y4IG?dVECpAAZ0%WE[wH;M_"*e+vT3xxH7r<S}Ue^N>*)ga2DT+"NKSI8L6r,FhX_o0rEKoT2vWBY[YCT@8/W-KJ(Z?P-b;`*,spL@p0;ex5Ut(gL$P=]q@uvmR!$.WhraR(LOkbEe%AZx3PIGa$V:oluL!GQX<ovyt?RIlOJYispdDUubIaveqTv1g<wpX{siO(Sn&&.7Ga3,e^og,o8Jlk
Vi<>FxNJ67PBl%?7Goa2*j@_vWWPT^}+aTonh={JvhphSHa.;-qV4jUIh3g>zhe4t_P!Slmj%#wPb
V@b&,uQu)6y,RC<nM`T-/R4)@%{+Jg#Eb&F1qNSR@aTkVuZtH*c.l>$#ySqh<JX].2HW&tq7Dej<vKT57Mb5MQ&6bGJXrG*f_DTgWpkK[=^U/h.jk$rbn*SG)JqC~E~(jFyNEB-<_C1GF%S)flcu7#xvR/vPdMgBke!E!M}rwSW5deKm~h38j0&WJD(E*%]sT]$PXsPkYB8sEG9l8C6VtI?=,b!CukG[rt=RZQ=?w28?w5-]+wq@|)"`BhoPTcIFl-Q+?=E"(1j3W+vT]N<%Ml
hJvG`o2%2`+<.v6BCj<,7X@}kQT2#8boW2GtA"n$x:A?WB^KYJK[:&P%r4PAi3m!e/NUkD&MOLYtG&F=-#2H@@%Nn~LVv(gWz"UfI2h=QGN]B
8H^aRGxD%7%W^;0jRB:z2Fg[I#92w[Cjpzb6094PB>B6dScT;eP6C,@u+eh!)RZX3FxiflPoaTWE7-%8u(e_*_g-jsw`^4jsTCZ)WGUaPaFe1)>w5lM#9_6cg5q9aV6$Xx.cVb*]fFuDa0!w:rjMeE?POdT$^a4|vxs54QxcP-+ZL$QS%tD:]dA?Eu0:Dkm`]A<46L3.SH-);Xf=BN+=Q~__T+LY0@Yla5@(Gh.c@)={YlIF``OYfLD3n>FF&iL{jH0.uL$z)=PV8yc=7^)sTAP#-Pp,]eU;Sd(3,9L]ju1|Fjd[1F6BopV^I-fkJ/S.I$laBZAdVA5zjWUWWl>)ibrs.ese.9KoqHJD
vbn%G-=h1@aWML-Px&"cabXeL]rOU_lWPA&G^>McpI?.Fp+t-rMisK[1xyT#>b+>w&Ub}V%-Pf}TXGnh!I)Y7+$rr=fc0&xwo9UCC<s*f`}NaAlh?aS!#T8ayi^gkF`6ibPah+NSM"5h4#=_"tD].J"A8(a;Qe<"#s7C77<%JcnHwq!EF2ytE5/k0bPZ)<l[";14HU2!7DQsix28sa@xx:mncc,8v4^elMxuqTOdGhN;[s+#-*|:f022KouysH_y8i)UC6)mVQRe]ZFiXcF?{%z>YRj0PAo>Hf40.oWMVs$o,h.8=_[1rf#$wqVc:BV(yW|,tLd.lh11ce*uF(p6{`GicE)k$R7))gW*d;B4Bvqw;,TUF,gL$J`nl`z+8K[#
=x)1"]WG3R<JvGdw:=g1A1OS+<o>u{TJS*s<ORNi73mGCQJk+|XOx7DzlZ"01/e*H}osEc[+-xA`aC%HcwWu*n!w;MWarY>0tD:KemH
D2]*70Fja)7=7(wijQOef(@B_cVi#t[R6]Ih[uQh=IlzZ*#gnrwu?&G":=_Fgg+Mo0q:sZr^%Wi
GqY(N}SMfsb|ZzS809m|,(;Y7gL
Eo0|`wQD&4S1?4t<:~;q1WhUeCwZHq)Z&/`7_m72sIxSgBJ7uJo^E[q&oVG}pfy0OeW.G[WBv(&9Y)V>%4:}7&X3=u*BEad.vAf?b10
1hI/<FPXrTScHp3pQW*%O-gZUfe)RG,v#9GEWKOFwk`X#>.
5@P*l*drk,`&w!Up2gYzce<^^FQ;v+nK`)/dCwXL>G(hvk_)Xs
W>"54y!teOMbn
IVA%$*iZrgcbS1Eh
l@$=:}"U3$1C/-!Ahlj;.H[|oXQy6hK^GVPSMm00G((wKi
$?C$2dy4wUFkUXk!o0mv$GlWX=>kd4MC76C.P!UxdTd6hT,PJD0x@EU9fC73+/XE>-So{4&(%pX=U@tQ{^TnwkwuZTP?hgp`z`
ZMcAa&k!74t6kIB*1J*`8jGD?LauR_"<g"<SID=#x5#W8-+&-37c8FT8-C_UQWD1A$,kbrT<_1F_"+fJT?(MjL&^i~OV$*Fw2Tf:S<^B?5x>8n4e#
9Uh|*bu`T`bvC>js1C7<%j:oo!!7]lBDoe(s^1/tWHd9AF,Dijn3H_#Qr_i)k[Ahb1N"Vy0pJpim/irNb#=*PQ;uOUi<iX%}pHA!)-QqJ&_6[SoDT}+Oek(?1)syt#i?;m?C6ODKjms(A.5Fb,Rk5MXj]/j&yw)lgT3fd}OtVQR!.gm>1|0DZ3(x_{geGYc
!w:D^"iSV5n(&>LO>GfR/,%6*.3_M`)aM?$|"AXx%MqFh<tKlR.#5_f}<h:_^Kb^>o<[8}]VEQ)4"~wL-Y@96:!f)e<"1|x=USL_KU1-W3yFEME`Q+&qs-psg;xKV6Bt0~n!ikE^qQiml.(w]$*er
.S/,$.mv+39O:[$aOWeYPKSF%a]6OL2e:`"I^Wt~Te0>pDcW(?;A=9iAiI+D8<me#{
}%DpCPtqgM=g%.?VWFHEj4k1qkmInKr+>t}e:/E(]*T*<li1QOkVF6gaJ&!s&(Dis+mm+s=T~PQ^(CEnraMDKWL`w.wd19T`PuSOp?L+9U7l<(V$_<zTpD.31p@xKCOE6Y_l{P>[Oc18*uo1zgGu(Ib3e[ZF|.!H/06CyPf2!Y.B/DdmICuET9f"w^ONC^FU2;`OvEF(hMxQ~NU(aC|l/pH4wE&n>p-3SuFSPrvFeb>:H%^v}fL0UyOC@e-

.Y5[kTq7xM_Fe7t-[
$vTy_a2*YwD0OaOu&oYX5!S0=}t:cdV+e6)]r+Wdae-yy]u=Iz39qusVmmDI_M#P.yhOh0,,q[Ou,r`BNR^tARW6psC$/MZY9I:R]5>x)-R.t(%imTH~LA!i7[ySh:bs4|P_4L>]=Fmf#tQ}+4G$FR
h$j;L"N5ma]_A*3N4V:V23b=1G^uu[6L}Oe<0kcD21jLX)wPU&SUxY;RSbqYKPMK#uUwmZg9W?K=zC}pXSLd{xl93)k:&my1mxf7<Y<oy
.C/(#.dQP%aoob2N5]O,;z"YJ0{#BY!ee[O[f=mIw7#"BYqA{5z"jQkx=/k=`LjA2l>w[y&xejxi/%}!v7yCmneo$B6<1qeZ]Kt0_`a9js5V|KQ`!Ah/=!Jun=0ER&~GA.+k
DrV(B*yqBD?QXO2!=E"
iJ4wsP,F.iaSF68GPoO4CmZ:3fAWC19Gwp;5fB7*JH@[1D5bHs9B!^UMQ{#2j@@.<KcOoKZx;$x0?O?LnoPHR#R,16uyI(@I)W>K(+4|P4OHO"E=v&-TtQgoi#OoRYu5w9vz.DKa0Y]F*=`v8wRk&_Qc!79}6+bETkPHJPSH7hQ.e49jEh6ALw)n`cubO`#z2D;%5^*gj-H`/Eu^.y1`6zljdRXh5Fnpm!h
6f.*^TGQ_OuVVk)JC*F;e"tjM|&@(i-oF?9ihcKf_`qeQDx%6;t%
:Z;1?km
^`X+[2%5@rf#;Sqbr>xF@O{-03noPp5:{^|&dK7g:VmFQstD#/|TAP^T==5ivtG`~KAJ:29HR/~L27swQn7nzI3j$T*l~={soO!i&@uVeq#ZJ:Kx]*Rt:.2E7s9hO@
QumjXljs:fd)BxN0]T.AoaMAp,A4cl<k!khANU<o2W?dlc
p[O)4o?sYi|9$LlmetmMf,qnj`GM$(]*45YF^AlErnyO}p],l*a${.rwAQD^CLbL0e{J2_n4D@R16lB_97_P@VueknC"4hR_iI;kr?G$l5#o0lzO*LjVQ0joV+SisMvW~%Lub1N0BA>G%UH7"6BJg`dXE@s=G%!dWw)ofDoXys
[o]d5H6Za;H0m^dOisJJu1+WbN4pBiAE8)U>1=qCBR<VND?ip
4/p~p$pC`:+JU[l$j&fx^[XR=n%/u[m,
0@4hBNBMdY
n-u`"v>{g)1kH{JgtKBC$#Cu8<mvD>X;[0vs;9RiC
&)ELo0;9k]P^/YKHO+q9+6({3v[JNBUIK0<GSlZY,0<8gzX_Crizp>/a><1rC#Yyn:SYj}"KA8d,QKQG_CR>`bC,^#*j
mt#+?
HASB[n<1+fOK_`HuP,dZP=bHyW7.;dJ]7`dDK5siS?W/0?t#sF_2Lpd-a1OTdEtq2f$pyA~HXKkL4v:*,ye`dkFxexhv?>|W/f0bX+p<#:2j+Z0!,(oXz&B?1M4.#AGFRK2Bm"JOwC:u<pfWanENmx[ikD6-"tCWv[728w2NkJm%8"17$.rQI`vY^=cD
E
$<55OvDYN[m#:1+d3L/j?p
v)47hBj1ggehAPGF"D=iKDSU9P{CEtz+8omp_gi#&9ajFf=Pa?k4?y?wex#)=Q!M~qYy.<_>Fu=(gvHtgm>`3U1?|NV[k;|;Zdo>RPuKjiH=`Y%e!4dW!^K,(Gj%(Kt
b*2/*TWxi`a"5](rH/lmpH-Xd-uu"D7LmQ4[k/VGmm%>~y`0x,g`/JEA>_>V_uG>jZ/1Or3RTmH_bvL8waH=A,2X`jtA}txtephCuN,IW6v<[0.0J#8!7Yn!X.cj_+Mh4n<.?Tn*qq3%#1hO+=wJF.9MuPjVHOgc9g002b%r?J[D1W@9f8S3=d?_$[M&Z6B.*1z+twtOu@/AB@Fu*e;)E_VA[SKCmdBZP1xg4*Rb/w}V%ss=kM]OI?~v}("r}PCbkr11?q8SB"0gpaDNh!//K2@<MIPb&bg_>>Ru;q;Meq,9W>R`%y"1Eukm)Ld[7DP?NKMQ%WXq<2&b|IyDvpY(oR/2q4+bMXt%M#([E!9yr?umI@,cBJ2Y+0B#>orOwn.YuseI^$=o9@l.1"j$4.j_LFl2!)Fow?C&_M[Zcx$t#q(9)
>nhSm0
u@SVd3?d/7^5A_?Aun/g`|]wdI={YvX;&{]+HbZtQ2@y:DrKVF?Dd7=(DgCRTa/f5.-:!l(33L8c>o9Q<<o/SF;-b3K@CHe0D,A8T3j`1{jIdZlqU2p|"-p]Z}E^45YRH#q6,Dn>nXHqnmayn:2Wk(x}q2M_rcP2J4dV[G%m%
A3LA(2xs+p&_0B<xvlVk_8#Fau+??dG:;@20`t!iul0p(XLJ`*wTZ+P~nx[6*b_6pK.gu^u>,j6#GYdg]HydJcK7PSYT"pg
^CqW^G7)w;Q`#w^(/;SlrRFbIrF(O{iKVu]1?HJ.HRcGTQfL+V?Kd1A*DK6dI+n55KpA)[[KfHM/_#Iz]^u.&o;;DDa6Zk"IZ#lFl=AA^ld?TV=t?2`/`2joWs2H59Unq&sq4PE$"1I}[_nf6/wt%@Jf8hc6UE,,m!1Xka-3fx`q-#fkx:Apxd/x&p&71")>h1J#eHSSRh_PF
c|`]5`YF[bD>K8n<uxbHKtfOyhaH%6=IB@1icqcbK:/fqC!<e5n<;cvJ,^[Zu{w6+2V|Q^L;^0<Ay6kLH$sAHGd=God"@(EnP6%2Bw+%nl+R@-ajGr6Y&?BdMXt#BYkEZ!G!dA42F!rzvxAlL[kaD{4!e7it?1"8pB33-5t(EtxKLY"it!h?4HA|X=>1maN[L-Lh7<6-qKI}&,Xw^$vvi]D_uv`UF-A?K
HQn8@1!}2>My>rcY,QIzxQuVc<o#dbZ@9R5fEoCta~q#1J_xPg>sx2a7JHG0&_nYPr4gh{YN&ldeZ98u5RgI6kMU6kp0gNK*4I0AF1NY=RqycSiWRv]RXgR@&]WH
AZG>Y@,&#(:Rf0&o3a
rEF85gVaA]y)V*n
0QC|5bX7avymh6rFN7P^)E!^H<%V5Z<B,pwmcs2?s2;aRn(ubG=-9vN
WqsJ%
4d#!VM_
^j.he*A;MD?8+Tt53PYRBh/Tx!

doSZ8}nR/6CpRU<}^7E6&p(G=+c$1`!vr.<3;pVSTd*E_{(X8yFUM]
,-YkC4ryXyhuX1Z8gm1${,G`Jm5J>FuIKMli<eza363M2h2,uAd:LYQ6@coUF?J8C98G;kU1lg1)!O>@*+zf,Ux-JC>-ZilH/khFWYymC`mW$="-*[CyYprrMW{TuY&d<B,a=%G!pkqi*2-hpYB]sFT;6m2Q*0q?-v:ADUqxLW~yAnMv"TUiLVglgv"@9x
lq+"n=TGI?O{bASfV|%X4FP|u8xYH*9]tcES(;gYe~@h)a5.qU>O+c/ZIO3%Z`0)!YLJ;tvA@ODquj%Gs`t7X5ud4Gu^j{es5AfZnwp_y|B5OQ6A?X1q)x2{UELu!g]r(c&_Btl7H&4)$r@8,rQW*44W;}=4U9ig6T&VEP&Mx>;i#1=^!39Jk,My;f(r_[$twyAw3"`99*!pI(?-F1k}Y^H!4=Rg>vALC.lzeu"okZy;bfhDFDCYrXS]J234=Th&T0<SLp11y%Dek*yCSb>!:p0F@`OKA6%Dgub[#LZZ7w^EgGn:Izmid#;TDcMU1Pr2Bt
1_Q"oD}y?_8aT4B[7Vil[0B-_]}SwJGv_(FYJ?,Oj:ca)rUy.-U:|B+sdgm>pDE[$m7=D,2@~=~r2I7DpgaD0UBuV;ea%U8tI
6.pT&Ea^8H*-xunkNR/m=oa@]`l&]!<8dCUx!6UA_5jH{_}!vr}LCr@6Auj549nI2i=4AmH`#@aS9*0L;#[K.v!B0V|FlDB*QX,nC,^EUx=TU#{GRrM^PrA+QkTmP/DDp:hqC0x#1D7[@H:i@,W[s6Mq[X/UyDBf?AaGt&p65,gbB3-q&VqRml8E</X<qxpu0L?sQcP%cN=p<6q!@8B,Vc,&ILvBLh%v{_nLPh%T]PKRdL{.LBH8w+K3<hc9mSg;TM//p,0W+G|S4@D=.NLw-1?ny/ax^ii7t"cP![<^_>QJjQ~4a&u/1=V^).>jh0@&@dCQT-[k!:scU+K<N,,a`C1uKw8o96?hEPEr:+CxZ&uihgHK`I3H!i.Z`?:VWk^
_DfXZm&#ql4W{kQWo/5t$GS.-?h=6`%-BAP
QbJ#<,nX%h?8L
KIlZ?gE"Gb06$gwcqT]&Tn
b3^0`pb^tuK8=_nHJMHp4sEKLDQ
]:F^7%8Q.0^Q8o1:vQx4F*beVh7V+15XS/+1SpC?/Mw8W%X[w!fdWN<q8KAz_,-*;Mkd)+KZYF4af];u)vL;]_lghkj)_H0:JsCcH*hEJpgJ6RE1yJhg+V%:bY`62kUE;KHCI-hMV6Z`ODA$l&c,X@I)M*,%l!qegb
^AVu_9D
+XvBI,%qo2:VdQ?z!Y6Az(i;eL5
*sol>FqQVhnkLTGVYU[_jl-kw6LB866u0SNtFb+@{3{U?:U<NJ
qOM],%tIaavE,5&To&q^J[fap?K0ANWb$<+3yq$Sfw
;xblGy1l!*ZnWM~J6)KdeTyge7~H5?K>u!1sh2~urvY%F6K=$5T^Wa_Na&
mRya
D7N(
>DRUB;kBt;XxZOJ4X<;^!,A~vb#H&/4x6t)>,vJyL)ATS.Nwe6v^R-1@mC;p_TVF]o+eQZGsK@^.9ReMkm:/["1@("dl,G)[=t_:dTTdikJd)aSkk`W~eU0f)mt;
0!?.|8<s-A|xH5P=^+IXXNl?kt1Y
H_pHJAEF*?V{y&O%S_%f$HHzS}Y&>yI]@^q%YJP5Xt*&<6w8%+EY,@-Y7Noii@o_mT)orUqDrlD?=.m{h>^WRGy"Gb?I?IaTCk0`(Vfa?A!5^q_1
P(*Cr#JLCFv"<sOU^Ny#5>ZFf6hXFDs3VQT/>O*Zk%ltsNVj*0=2-<lP=([S29L%uGy-7Y[hY<Y#KSUxrS&t^o$Nb3{I71i;n"U1`Rur`YZHrkLXRDbX@JmpUaP@DNYEg9XY~2QDV&yk1Xn1A!+6%c1g8t^DiK4L2O"O?QEvS6K/VH0>YxrMLQMH.LZA.bWuh[@,6R,w[XE#!IBndHR
q`i,bwy9n.OR;yTl;ZHG|#9Yaa40H]#4$,pC_+68@v?(>d,qR]i8lREKZX7jy<WM>hFAL!8^%kcEXP3Up
7Nz!g/OJOlPjr)?9:"Z,Xer/xE"Wp)gAnv8%jb{[y
i40
>)qChfVySY0a2>hm)O"%=cg"n5yOa`2MS&G5K4B(Fc+xft#a<vHkC@S0:jlt|0qS^12HzGC;IVq[&Zzo}gsbl-7LL4L=>[b:q(A+!ybeM2&I3Z)@`bgk:1Vedu^C1RMRFH4t^`WEB3;CH8(Ng:GO1T9FAVI$*,[w>x|RMT:c4W{5gF<Oo9kfD=s1yRcdjd|o{;175f"kobov|eZ3ZK[cn6PCAOQfJ:j!AHv<(+P6Q@
DoFHRnH03XL/f)Lb!0/lj^h2^8MMqlE]HS,K=exP1X$}o:go2
]E%@SNhTm0.XiYgT4lQR3r35*VdF(,]=kAFRy1B$+$P:kD@Rz&@.sVjt^_KxB>9tds5-0jEtx|%]</^[VowjA7RjbdeG(<))B{E-a)PpUYfwjEx?-((Vc8BiNloVl6-PC)HTo(#3+
sJP)F<uvnyFJldJJr_D4m[t50WR#53&41r$Ds+@TA;FzmD<O=)`9SJ$O?L!}ey1QxAm&-f-{#-f@^vlRL"v7#UOF/he$466]m"AnT(VUdnT.Xg(Gl:OrNx
MXKSMb+f<4(70XXem3Bo?."*wb^;BGn@hc
YM;=VIuby00x.e./=YW*&{P<a;w]?t5S+R8C9Z(G10>q;:]CM1d(:RT%j~uMDT4U3c!&]W^s={bB)/RWGC$9@A=W[hfLfv@Fs{NZBJGO@s.IksQL]?-b1(!S@m:6.Cb>)+dnYW[-L3Bz.3ol0L!U-/5_O)%9o-#_3HGpf+L*Q3N7^[_pRMc$box]*CO[D`20?xegn";dN/LDi
u-(L3{sM.1Y!GLK]YsU>7tNis3ZRi&D7s@@.QhQ?q5e<oe(:wM1
>[8^XAE{.-3P]~;"X19A-xkm,7h,^gwGDBpd_:u9p
mjSiGF!tip[i%=#%+cOX-E%7W-p9kcP#1v>tJ@=dJJxsQ%E!`Pd)_9@x4cqI0y"X`>O|j6exMBI?Z5IgqG]%?W.$*E?;wzW)e>+SBxl:W,9hc5,@9.oVSfXA&X*Q]"$jUCJD0sKsYl6X6g0B?UY5(9lN]n&(jbm8t<L(Lu#e4,K,#E8k+ggbM]@A`RkcP<:P_1Tfyr@gpSI3hm!TecsC]#vlP+kf49IVs-JVpU2"oCd0,8xY)hiN@X@?MO5qM$Q5,E%UmBC`B5)sx3Md0f5u8$#$li"XkL1]-_3H+ZIP&
U5CLocXrfKP%Mr7K(R)cEzT(E~`
Ed4O+"-M>
2Jaq]>b2P""Ed-t])#`b;FlDe/_bFfHu541/-k7AZ46:N]ClNgci_=)wa%&(A=7BQiECJ]4bNz9_lGLs!KIX
i2.Z#R/=HMlXMdc.dpU5u5==fdx"jk0;7woBHC
BdT}[n=c(`GrcEe8E3W!&YebT[+.PtxeJ;Ax5Y2$Y?f"TySDaD&6Z-@Bh->mHU.k0K[;/ZT
K0ubJqc^M;E>R~LsK20myTo6fXlO#cw$7AP.dcNWt!akISdqNAS~swsiXLZ8)O*gCJwQ@wvf9y[6vdchN6Cq#9@"%}>}R)lcWxDPj6m^.?CPGS_3J(
+,,K]Xm5%u1,&VT-QM-LCPeaAGdl`6Z0sXs6h5Ve?U/#D+FIlP;p_8A@r>`a.hfW_rxW8b:+haV["Bx)Kx!smad7-a)JV8F%hW[x4m#yj<fxSNFF%NK[_f=3c+b.wTB!s/~..G_`npQu5Re1fyt=!,~Q
IU6+x1x_QZ2DG=_AN
m0s{*#vQB?<S=JrMZ)sr7(hi&pV*vXG81jN`W$t4+3
)62P<[Q?"_L=9&)`kSS0[ql*-B7bBH/H>H3"t=x7vjwy
:I+Y4u-y^tUL/?WiFgA;hV9b7d[D6aJ6^c&gVkby
SOvw-`OHRQw*1asM26Wb5S|a8
n#0QhR.vE?MPA]
e8y-
yv{,>O5vR7>Q;
H`)<Gtfuzv+-D8jtB30GWp;+q>3l8(p:+Hm6/aCq@#S(dL_x1uw%4.%kyGk(IJI@Y?;LQf
1&JPFmb06;6Esmdo0)JU?5D&SDY:>NfgvG<Ak&sKQLKC$=0To(!uj"C}a^Y3nq`?B+IiRa8Ae>Q(:=Y"wJd;svBHt@_no*,*A"^2+;7@AJ]AbOr%m|:h.bHt
.r:YMLU&-/8S3W!Q0N2:9pguFN0`]X#l6:I%JwdMPb%h8a7h8l2,biC`LC]Wvff_)@x7,m)/8MY"lu"/E5,)VmrZ)/5B$3F,"cX$"5B]s9NV2LQ`zA860bcj1GwpJyxVqP~Yi&~lSoTKVZSM*7S5N$x$.:u2,:i:rj:fWPOn4
I_C29V:6"&6-p<j<!by61BvH5m1jsfym{LH*-]Tk.56Q)==qF%tRRuFm9qtB
ZNe._+RvK_a%UW"C)jbkE]%Ud;qWi|,]AM"P.n7<b8Msk`U"q7vaOy/#Gq*7oGXwSni)o/4)]&3+wnDqS5Z2+-lve;pKC;SZn;^hXw5CQI.=$.E!!C&GFW/dgZn}XQ8G(9bt;X1IU%PnKAC{Wp!$B@V^&F&d41Ep?^@}EN1CmItct|F`"yMO
"Z
YU@l!kK&:rAA,tgTvoR80Hh:;.1=&6KdH8g[Dnh*?0)`?<2sQM]}p
o_5c#.q
_,oudENB.IrbRC#xYL,.(Da:.m`S/9"KH5Ol*m[>6@om5-7w%3olW]
[_`Ms
kgqN`U<k+f]t<P+^gd)A1P4Msd;PMF:">7_6%UDBD@aOx+sd[f[g*Gv]ge]N@GO*5ps[<7Hi"nm7TBuV2ZW24`]oW_@;gptVRJk2
5OQnoAP,;LJ[!UIse,vQ_F7N5^vkNVegMcxkGHFh(e)6dY#LHT7LRL96f
S7BIj:X{J#R]2khQh9GILeg,%5FjwQq-o{?g%G?w,d2"^g%Aw-.>aXGm$y<Y:T1#hwidq78]#CW:tWYmKrpTP!-5c*96$R
$f>Nd=9z(3i-cse1W-OrkBTXPjG+hLUQT6~t{]+skx@Hiu>98#MUY^nz#$RV5,!CQ%my@lk2Z;&9WWD44"1-wcim!a!/dRy
c[Ute3&3Lca;zrVv+m0)zj/:*Xw=#oQ<8J@9LD
`>g+x_:4$5xA@f*&@fK;.IW;k-^hC`F?HtW9x4^:*)ES:{AXnMD9iH&%<{7_jPsMJA3lotEt^&-iXe#@l[$bQ^p(fmcQ9&4nN6oH%xR:fm8&g+205u/a
{y(Q,2=5ZSV-Tym_|Ym/!$a_2p3E0K}o$,XO^+>y[ij^q9^RX^`9x+f6p$Zeiido;;?WUE~!8gzfU1J30@~6Fe3F&G^dK5n+ums/!R:yyR20)2SJx)iC|yJ"{=y4WS).RC}({J>e2I9$Ingxb`+v{;?x[=Dp_F*1uZ,yO2D)"q-Lm,yitVbK8EwVGizcfN@qsxr$_@BN9@_$BDyKU?XOY9f_j4l
#mK:yWxS,m?*gC(D>ZOF9LdSSX
J#GcZXgtD;%!-yu?oBVg#(`
Kr,yCn7IdqGrmsPxZwOWMyoZOPm>1!V0v?.<fPT)Tp>o8&*k"T!Wcxe%8(e|r+22T>;mS~)#DML+#LUJV"8_1q%}fd3Mu04~0T(W3=3V=0.n`!i|?aCqA_l~q4tJ2iFC",a;5YF?1]Rfb+n5H~$--Sdb^a)_4eZvgmZ!2-35&pP]Qx9(uT9`D7Gs@r[O$v#@%}.X6rO0C]e8IArs/|fqW3^?wguJ^ld|0den-R=0ji/vZRgG
/>Nm.<6dXx`*eV*O,Tj_EXixyWGw%rLaus~aJkSGYiG=8d~_(f@<EIE1SGguHf1eaf;QliFr
X)#l?6@?4k(
GBAL8=,aoaqm*@3nOuv.0}F*=/HW4g(ymEgF2l1e%Db;I)>VgR&RT%>uJ]3.$uPHumhvg=fx;vTA]x1;H{YgMlxm+p-MOmQiu6`9^W#+,]*,_9/9AdWA@l3x$oeh(y5>l]KE,)3+
582I{$#f*4dGg$rY-+bsFHbNmF^2bT4pQo<>WyON9*(sx+u%ntc-ipL%RGi$.c!Vi5](A2:5sR69``"K}HF%4Qyl$V0;BaC4@P&)Y:v+DFR2"Q=QU4<%6(esu6p6tEBf)cLwvw+v1]iaa(;<{;Y!D5A@a&Q.UI)-8J}2(p?20rw1t1`%l9=Vd_k6BgY&O0GC.Q$c@ZHf:>ID8oJYCuVW0CPB=_*)ja5G=#{.b=>:a,7=a.oL@.!QG?E9!1Pa.hORKGhqb+Bk@^oZ+rk@Ovk1g5~4V*l"{Y]Ez+IL)
mKO](Lse@TrZL*M-;:2hxc%CM
a*P5JVI8s7lVifQ%MN9!1o(CfCicmXQ]dY)<Dl}^^@mOS1`S.5(;pVw/UouxpR|!I!]J82n!cEIG}J;RB86S,!hA?ckcN
<[Y?eO%Ck_WYrpVt

0s>Ke+|R*BMPn#D**#hy;_kievqt|D0l)M!/dpD=nxkp-!K,1,555!IUPGNg]QqR]C@Nut3F2X]g=/Gv$.,_LVKBiT4DZY:Tp2fnZ:L[`Ez?8wxPo*p&4[mMR%Fx#7@2|M<[{:%(A->))&G!D?3hu
](UJfw6$BVi?6YP/icg%:a"hJ4e9b
7hg-NWz3C-K<ejsSMNqs-vB-!Y4[x[WEChY0VN6D]tk0]Tp_Z?DK}:|7YdL@{@w%DMEBRDY!wOMhZ27+-S[1cHEU~"as?5>#A8[c[9D#De<2Aq@5yYqy1azL04Yxni4i%Z@[)[.l{JVMSP%ojp_Jc>D"W<#%S*KRqIB],
-%*O&3P7<"U(YuDL9pO)I9)"5c@B+X#F&e<GYDP]c+b%CLa4FE;Fty3ZjG9Q/[oR%u(=yQ>n&hjqD5!&l:#?,g_I+Q+Gtc*8]kq@INmf(.@q_SCAkvs$O/:P6.>DMo4sm=]i|T{7D==YSdhezN$=XhUg1G{/WE:PlVoy&#w(BWM^Yh*ELC)Sv>6?EvBja2^6noG#XO)^VW0NwOgA?le!nW2(-r*+Ei]$-1M6Qc9VhP/nvFFYx#?M
mVs-x}L1y$PhbKEbkT%*E}>Mn@/z(r</5nC#sUNj1NH,]H;"hIws?.!{0xi-j~3hck4!orTX_>Ek>Um!:NPHeFT~b,Fj<OMl_jO`=5x!t).P="r-Q%9)EHERTh(]>gHy-tQ}:
*
,@9vO=iEoL&7uilR7g5hkG`g;q<hRwec&&mGaJ$PWxYK.zT|_<JE&`PLtG;m>bDCPF&TQAh_lvy_)b1?W)Mj4os~
WiK+*RH
|<9cIx~
!y]oa&92vc`gP0Sw6ua<<@uA{l_
jD?kH6V@2@QMw`lN/Ms@b6=.<5StJiT?D+tAG3%-cq.
jsw3Ko+^uJ(?c+f%{g9beH}Cnkn_Js./yd#3C(|y:-7B]+N@32LGK[zk2?Yh]RK4y
lOq^ACwZnWKrjUSl+hP
}2BEmPzIXB72_/M`>k0_VA5;R$F/#W&n(9eij3m8.OHb9rmtVsN^+5IZ^)GOwCB5!rI%A^rHzYvm{nq]`)BU]PQ4!#FB,h#7AP-m`2"uu7AHqoiWA]sS3t9Z9;!U]o68OJF.?EL(*9l`[GS(.y|ftJ9?*00fd]ReJ/|B|/,@#9wI%?vuEce9L
J0l)?/=!?%-7B.(vM?6L$^GYibHScpv:K;)vNGI>gq2TDgHtE$bokF$2UV;(U&%@F0JUMNKDm?@a%1Ckb?0^TvVuloQNJYRbw+3"
I:p,uUO88UbwMP:D*ck;q|NtT.!YA!?>fELi&Y2t-_r9%:k2#+*L3@[|e6xC#_:{C[w&6.$+J|hUr/]_fl_d*YViu<k1>E*m@~CbPzT-iwI9))RfWor]3,eH6j=BbDJDT^::7!/oS|q43
sb9S8s%lS:7`4EiS^^jBBv,uX6EnG}.8+nQxnA`?al@#ae>E:0)^3BC=ZZ[{v!"hZ$_!:5g7u[R3pZ@VhjwwisQ*LO(4xnHDolO9ph17M"_a5s9X&0k*8Fg#*j+ZN@d`S^%
Sjpb0-AVjSqg7<5o<cz)!qtj5LmV.zJvFeO@&nN:md&z?(d|&o#NBB&H8!6@Rof%XLvcxkgT8>@8QKga,Edb"`DI0PR[RNH8(>%rZ_n<8tB7I*#KPu3IfC)grD1sU`Bl=lht6S8h:G^,7eG<(h51-p-(0w"oA7FTgD-|_Y&T-(*?vwEwUvwF8xw[@+@VFqLL5Q8Dd/a>NA+YC=.7-JMo.O0GJc(=_"6%kk8J?_5ynF
T>c&n]Q&U6C4:Ogk"FV(XAg,s%b.0W%l)T/?@O=&XS@>,R:v4"!eQ/Uo&;"MJxQ
nQV7MP$`b7EQ*^".7jN/GI0aC
?,STP8pn@!">h!)E
;O%}#VF?ZiC7tc7PQ"Z"`7"/v%+.cm3WH8Uu<jpMkeg^Oq00qNL87ysmuq+I62A["}pcJpblJbt7c"RlB*5w9W
<XC!U:DIy*s^e5>$qgPHI.3aoJq3"nH)Af}nJi!bmi:7YD~4yXvWGMXY=k^Z6wp8kPsPs&JFjn-U`.B.`LY=?.9iCriS#5mk`aL4|?+JpcAQM>[BSC4O5UBDzEcp2-UPax`owc1oZu/:.3!aFaQ``aw
91[4IV;@C8zXai,_e1o2(gr].lY-/7pix*Os
4X+AwZMZ]huho!2x&8=H;qIRP?EGQ",QaCFcPZ9!l;k6$R*3y78uYNe`Q*"?ks&{OEE+<S,OTHeN^CEdeS&9Q<8[hq,R?IO5xJZ4S
[-`}R[Q|M@$=KvLDD8PuQa@DN>r9C?R&<ng7Zh5b-,n87BK367N5(<:_l1RC-"G^Yb>vl+vVs{Wz*&5kb_^[X..mA:9
M7sv"Ux#7t
HaSP%p._ph)wV6>60`H0]BuhKNt*JFG&_HNh7Ji`W-t%+`j*)t0G+CquYtB1lAG.kH!.qhJh=N4An$MH"j"TSy!BxgAf|t[^4SWv9`S?F02,9bf#t(2S:"~CRRf29*Q[FtW.ZPzc!FKSD)_WI"z7r;s[oK5?fP(N
(cZFm7g/HT*b`cXS$=_%k1dI:OW{LBWpWz"R(ydg3Qv3*_(astjvH-M19J&IMg;Wc[4+vw(4K|!Dsnk^lj5W9.p#"_Q<#JwB7T0NSjLC.Ywy`i7Y0{P>xfYBBmSlQ
ps7~92q_)u.0Wn>&m<s[(,ni"C((/zi6MeJVJs+GJ&.CjypoQ,dZ.DN>i3U/a=OwO:CYbf=GYslGF)%/_*rzooQ+qGy/f58!E-&IO3e+X*;+V-0Bx:[t5W2$g=<K1|MoH_$)G&N;@r$SZGoGm4+IYW[AGS]~DG_TO)
5I~(p?1@c*Hx7-_NXrisAP+x4Ld5H,MYk)Ec2)9`%_.ycJY,.&-P,Ts-DtTr)s=N)6STXMv.(XvHII~!y%1vkei.ju7CCu&9C>+Q!i$KLKj$.u%C?6MI`]K<y&FhfGK$]l$IKL5RRZ7Y8]FeOkyCkq9D>xKhS`Clnb|d;^%9
Shgm
b6;%-2IqY(jIk?tZr"#uDI~ivB
(7Px!t0M#{owKt
@l"SL.P4r
LCI#+PIE:4{w?Vw!irH91W$?3
d#Q7^T,%!N_w-]OJj<:u,x/KKK:*IL!e<Y.X`la#GZdF+=W.c95d!T|N38zCk%^fs]s&f^/Z#F8"ET>y
"PV%0QTnQBH[Vc!gs-VX.9X3*H
n5!l]18vrc`*#^H6,<Par_6A^Kv(mU,iOA%?@N+&@j&C|M5*FXE4Q-7]up/)QZ/Hz"XP7k""T0#(mu&Z~<
wpD+[AGG/<5V^N6).yh<<wM$_+m_Oum_szn0h>)rsKlq
ZJ{Hj1_m=qcLbs/.aApkKr{2n#[H}j*-5&gmvu^<4n@bAcVsS1""X"w0Vqf2tPq?-[p@F8.9LvjjPKi;
kp!ZY*71E(c.xKl(?~7}/ny|+{v6YA3Nb*dR/S)ksE=/Qhtw+e1AHWrStpwtrPrmx+nJ3.Ik@|wv_I%q.{(E5ETK4PhWKyd|Hf17o{_08C<j$BFYbqfZ:qM7oV#/Z.<%xj)wEmhi#
Iy5u2osLE!;F1[lI^_eqHI%3h(Vs0hpg$2+0WZ1DFjh+8K+y1ip{RV;ZlyPaxv]k(GIORX/n.M4xs=`2)l106#VCq#3!
;>UTQFNKSe!C@;?LT/K(5e&Dj?fS0dB!<Z{g|%S7$8s4Yv;kz83@%[(E45.P}B^QcI:BzU9S|6Mq"aw`hSVmIQuIqi9f`Oz-B:yWU0B9`M7?*
mK7[6sJ;Wb_MV
oxdEA>)Fm>FvtG>BGyf-cW+b#[&b!H]bGFI5IBNnx%@DK9YCfGzk*pP
Vu96E5(b$z%k.8te,gn*&l2TY!uvg?$txaYC^nE"]1O&tD[[-xc+q=N:f*Ua@hdavO1e$RTGqb:kxgciuvLa2W{vBce^ENo2~g|/D^?U|+-!e3T3pU`^LkZdqol6qJ7aZgAe8+K#w?t&[4?Ut!/7:"o
R_yw!d]-T<0qv2t!J92!t#FeNe$V8g]PCJ}35k;wr(2:NDeU[m~UrCp
%%4s;=Z
8iA8&V7T&K(tO1j4w!7E$.8[}g+K^2Bp^]/;zkcca5?QhMRK-Aqr.If/vmXshIXOTg@]IY:xH>gP[XL"1L]m@v.3cYFo^^^V<x:cbHz_Ho2O!RHR{
R$$+rBla]Ro@
h=2*
Vm|v)=pl?GK`YH{n(l].<HJ9<,IOT*N4O!!4rJ}_~!mdtV"4yXCZju%pBSA4=@W(8>Sk^3<goR5B1wwA3DX&&;xqDG{&}yw;2TDIao(:axRZw(/w;fh+w-i.B@zXX!FR)Yn@B!N1|E#v+@*`G.M@1OZZtOXhE2Vp81sBTG"4&6G>
K>!T-0^9,%bwyq&el9PCiz2Jk/Ugq|
!v9:U)}.~X^O4q8LpZ8FtSs6MF4fy3Ajtc2q-&GTjb5DJGtoh;AlK@(Efqt-qyk
hB&3Z9`Y*4}?r*oo]f?LO(MQB_`2.-*6pE,c?rd#3usyItdEbN4P=Jz=717J@K

8<4u~qfrK&_ftuBc}Hu?Grz2gnn?2@m$(P%N^y]*[TC-9FHWZfVXp(Jh^?MyKgb7OQ-715ffyPq5|>ULeV0t]-5`pjavl3$Vm!K(_Z#BKURR!hOAS<(Wlg*CVAk3u1/+$D,ncaaB
OB1k*=(5gu$:*D<(=^Km8?,RkL(nbU[sapBX%(v
:|9]iw23Q5QzE&E_;JO1f])K,H_
D]7tHX_L8K679o,]Yq,A4BET3LD5LI!E!5&;a`e*C1#Pq#@AhUTo`LF^k.e/CG%4c?N*R.A</_o3u*%13O)Ct}9yl9r#Y`ed5b79R|fM];Tj?Q^_u@f1i>F#-uiNoU0J9i,:EBC{OD0GC;%|u$DMIr&c;Ck(.T4l]Wte)o1?X17mKlss3aEPvVqTB369:WQ<L&1jmb<G6<k$OqgFpyd(o%*0<,?vlzszy!R>K+z$K7^LT0V.YF&5->mYq[M`xBB]:9U>cN0*l51Iw#q%]6I8M3uYM<qF/
yk"Sv~AYJF_p]x6$^~7#feUm@XqEm/?hx:4-Hq$g#32kXfim:N2
fou`SCwSCyW%X.Kx!BMOtj?F*QL|QgrF#Wk#:+Um`c!+A!m}xs;d#BDAS4
LoUV0Jn%41;d
&yi|Rz_,FQT`1E5AlbHSEhck0NcpAtB?yF&[
1X~Z125r-%XD)1,`KMAtR[n^GS29~_<.xeE-_QxkNL;c-7gRhhZK#u/OSKsXSF~9vU?D}yRO~DM(1=sO*!)(*6hfZdig=bs;<<Q+y7UcpHJoGc-O1G{j@<qMo:<pN<y&xNyMBY)%=J~8^%NIS[ae
B)bRl]J
d?<X-j4jPHqc23_@A~Qsv:n
3/<!R_FQ(J=F9>p(4)x1+Z[K4rM
G(FFkX3C;][Cx.N3QmE6Zvu2L0+.&<YHm_G.a1q&OC>bi+:(d"W~Lqv=x)vn(Mp$Ey";@KABaNCO>#lz[V_bF#^[&&
Z@Y5^W..$!,!&8Qi+>-4yAvPT4S/08TQBC)[3Oh+ulK9p>SdrA+()?^!J,>:2iWhvp1#D[o%sp?UY>bVZ8rW;4M#fL/g7GA)%1*$3:PTGxcU|[,:hA&cca)D!4c<eR2l]BX,t[*Yu%@ESW}f&O{[O7fbQ1S@52fP|FZrp`mp:YUqy#A*/?JF3`
wu+Upa@EsJhy,sGl;(J^$
synktyX^${f$@)${*y(#tA[hTVxtZ%%KIU=s]^04T#X
>JPjuwo[AqZFvcc1:xNIlJRdj.;u[V6N@$8s*;h?+RSx`s8;qXc#`953lb]>MzarR{C@*L((Py;Rx1"g#{GV+-ym
Cp8,^v_9YmLYi,95dsvl
v/rBZ_F6I&5i@rt}n$1wh_#
;k5>idKVe{XMnLh`KZHenbHoh^HK(t>DHAs6Lecy-vl
5YhelY[pq@8v&y-sg4M+[P[b"B7AG2Kf&:;.GzI!fWECC6ILG8LwB,/hZ^,eUd7uf7s!3e^+*aH<M|i=V!D^(c-5LK*;UOLu29E}0zGZg5?[<}c`s0*t6)YKU|)aWq.)0XjzDWCf
R$Vmd#U(^bH%ZO29fF"Y5u^As9}.z:1VRn51ue
Q4TIksZcYxPmYWh=&|2L6;Pa,0?>Wak`9Jy[ANa4B*g:M;-?/qn?
YW/3*SMA2GFSRrM,p_%M^jQG,nQ("KsCn42t1
s[?nhuu$O<y)3F|q<]kKgJj,3]Sv0Xsc4)"@D[pZavTVFf5$C7=_Ri6pSsg:jhPswpXHM@fWzbX)wLw0i%=j00R_,u;c!*+S`62rz2?GMgFDAntGc#Wv9Rq!ow8s/i>Q;L[c7w/^t4&j-t[n!0,nDKeBBMx]bBtobMpTb,~`n!sK<?9=2sKf"srl&hi`,Gl68o$vxl36X=P:776ltvWx)sSv}::
J4]B]cdR-4/nyFlbv>*f-xQ/x>DK!EgAE4KTXb}hzVjp(=3@P%*2G:rPmf@?~^{V#9}U_o;]<S;aO,fQ:5c;gu&$iuxb.YagJVm,v8uXAow<D*OK(7>WZ.}CmMA;V3z(
1ZE~Z~=0mSJ_FGX+XMJ@4_GB-Kt8ZpqYj+f5*c7~CW0tEFx59&=^INWr.tm3>
4[@zIVUQZTuMvJXrZs!WA"9~`Of.?d1X8%L1c$,(]Io^x0kRp-&J#KKlUe?EqcTUFKt
0}8w,TSHt]/ONMcLr
FA>!hzvy`EvWT{@.H0Oa6(YQ6du%Khf0NOJK=WrFiw&:ZY7P-5q-J(%6m$yG&E!%%2<z&)WnWR7Bj^`sRFF=U:cb-r4VNnbB`*e"%FNO-hlE0{`#X]15&W:`&R<dgN4rT![!EX:-Nuf28Y?G2+pg>oMXM^
,C-4xA]pt4%YE0cO=jwXq,ZrFRD_9#X>B]hjVE0&"oyt;%e
DP>AVNX-iH#.L`m!0+AT-USA>7MV5W,eAD5C|%o]-8{q91@*4<uQtm2!HGx#O[9$RN4B}(PGO+IO~lGu_etk~Z|6kO;@oQ^e=gp?AFV(?SCfHE"XEe=i(L(
$==!%@VFS4dP=,tGer|]RH.Zt>[Du^#(=BL9%@0h-RxC_^h@Agsj>Yof
uiHpU[gqwVq=;!QgA!%i4c;%`,16P*#"bkHnAudbZxUFeEDmE-t[qQy$.vFqO{"G)x%Y0RQJ4B5g6P+=P8l06E<DDF,:T-(x?(]3w`Qy+s.-,i#KNK"47Z5<?bSxOl:GG+_+E1hy*,6Q6:NoPs4U/W;BCPkuFV].1gyJ_A$P`~P>?@mE3dW9MZwt!<aVRQ:(5ALV:-kD<vIqP`+69&0VYA:$d`TUA(gp7Fmy!+?+kyk,V<g?6kb##*
1K.Mp&p[0,|3)o*CkW;[ktKJR@6_IeW=G1T+cWbdc(/yI(SH]::S6HMbn$LF~-2rM*;k;m|aRtJ=$%v^w]s;#
F>p=g#GU%:AKsL6V{$eAN%^A%>IVdnL`[Ze:2[K$%@@W*?ER<@m0:?">QYpK/r%GVscjMBHRt^ME][ABySBl{y6f3hyS<x_uzz%l{vFM~Y.V|N$m=D"HbtUw`a%w"KT+Hj`itIn%OZ$]m@cK/8
S6*[q2x%V78j>Y,
]nm;6B(rc!
wC$V#d05hvjOhS,hk(.VL7}=WirE<Z*BGQ^*p0hZu(9wS/DZt]3weE$,/urWIqDlbc[tgFy*L+w:bjOcyY24:%FO6qD)u<|itlgARO8ix&t#zQ-JnDoN;?woWDX.<=%6paA.i&S4`lMEy0Vl+tv.:,%RmaUd}^#d23e6)uq?,u,6NCQ`5Q.J7Jpdo@Qt,`C7;WXl*ID/d[
+P!Cv4,Y@>-%L13/GYH):ek<4nnU"},5%o?ANcb`.[GC+
e=Z#h%7T$>Q5p9I!V1p0f%)wstbo#1ZkJ5kvL++Fj$Q&Ed2%RPhQ$~3Z^):)0t=$1F1S
&Q|8:
lfoh^@&Sm-yH<R#]dj=[.R2"9)hPb1hVuOf>COm4;Q{t0@%N<%~LNu.n|XidqFR;<1oZ2GgcJ)g(kdXcOC2/UC$#$;YIBEEb@?l^{nmO?MR$FeOqF#;Txo5pGn-fP@L<z_M6H?I@$sLHx>uS{B/kaNSn^bgo=M[XqFVBg1b,uKVOh`mfYjMm@9~]+Ef8pK3Seu0+T[hHZ0GM-2
w$QG/&GPP0w8+QU4?)O%k6_5L.b]qGiG(^XGqM79%$!Zt>ylWO$_kRFoyjN<l?Cu,<ww@D[zlr(bL{EWsvuZ,Fj7Jj/ohSY1&]x>I=q[ibmgZD?fTnObvtZzH(oZ2nfN"_pc=p+
k*A$M?I5^E>nLMbvNX8cL9G#kaUWaJ!<5HYWFX2o$[-pV2y+aLcpGlog7M-i
m%q:RB4O~pWJU
*8tM!rO0Q<n.!k@e/cIf-iF?PAy%q%0+t`!$KS@NQDiE7L@p(
"bJ`nYr^]uWtL3-@In_5k2neHI<qvrz4g9z7w*S"ae1u,YA<@nj)R*@5+@I@/_LC.doB>WabZ&og~=WHAbI#.XZ9:KhO_W0!P1`nT!0B7yv5s<4lZhpdfDO_:X$xtg-qSlOPJeS&,a!w$yn4jFrx]PO0."0W{mKL)J.$jP>TDu|g|WgWMW1fP/`$NThw"l9>UnG97-%b`aCX1hx.?gO"+>+R$9}dm"DONh:h,2AXRT7o]7(Pkt09Y1n=DY]t#9:&1Nwqb$`egeAu%yN8*fT8w.f0|"MK]R*Jfp3`*VwLZuGtC;BmH(FA<*<)&
@g*a|0:H7(mWU9ERF[{j1*Z
G.V."FcLSB6d<uOw.OI]t<f(u7LFxvgr74!jNl}XX-3`9jir6W~EH5a>}45;7rJ%MQCLxf<g$B:nJc#1<Sy%X>76.G1+W.m#kc~0>0:XUEp_U80t|#KCIv0-p$18{dFR.7,:S${^QG!q&Bhior#y+;d=.)|,`1}r%@.KF[/nLodI<U](A(+$
vRr58Dh-4wBhN@q.vm$`sM=KM_#Di}fYk8RlKpr|0-,%M?M5?gbn@@5h!i-sY-PfNku[6^^9MxBEa!Y"eeXGqG5B81[~Y=jym&eAvhinX01Wt]q}CHRcZT#G=
K~8#FDwsjj!]jgl^&>GB!<<W1fnbw+eH
spA-cy](()V$|.(*.kDcu,H
4O}Y8C#fY`oE%mRcCiZy;L#]:H?>jMpqHq%+cFSaY=r0LtzB&y_%R2g8[A2a9(("^?fX;v/*9mOfW]922n0FW8C/WXKo@<H,cgUMdXB+pIZ<Auc/da"O[Nbie]v;dyGMciNBXckgZ%+yNz&V}fII:FDs29+K}LZ1YMUjrrQZ^4dMkxf%6jF9{Xn,k]tsN3`5CLOQbbzH)<_yvCJ,-%/N<159n2iX:Xm2!`jGC0f@<`,w>TBP#$d#m=jFe)ft%Ngo)^#9;b[uhlTo])eW4b"2QLQiH.IRCIX9?e>M+5k$]&-Bu[G/;L`1R:-FRPCg}")TI9,vJ_gNb%aI:XFK_g2&O>)*K`[9*Rq7.D]Xb(*Jx+`yMxMosJ?]0/h6;n@2<5,Qyl*itPQ6:<Et=%`Z9T.+-*W0s#"N+>U[_*zf)9?HnUBC
mJs`8N*hSsSC2l,M&BqI;W[~kaNcick?8h=sSQ?Rc2$Y"XYn,V/u#fv%VZt&!:_qy0yY3*y/=",%-5wwLBi^r9R|fk_-iGQUYf*^y+^BNsk-O&y9L{G_j>!E*tGFX~BzD]T!rxL9%H)k#oxxy;v
d!q6&J/F[rq`@{aLffA>uuSa*6U`!tl]H?Ea=N?54A^y,M"PLiUM"f,~O#73S9suC"-;KKMA$&d?re.#60`,i%4Qy}hss(d%bkx4LzY.q
]1mnZ[f)XGx
kfn7Q14[C0QYdWsS*or.j#k`On=U45W%AI(gkn="K},0q)(KQVa=D22TBiT[;k)z(oN&!k$!m~w)Awy{uy3-UnR[w|Sshf4|:;e^`2cIPs8_v03yR,&
sU,{n{UvU[7WFTtU%br_:Tpzt7B3qX:.oXUCc)cXqTVE0$wo/B@6a]O:Y}DT80W:@_@oQMfb:Ccp&EUwDFR=lqXM@G&QS;hu=p>GVY`/kMRn
UVzo50cLP[p@YYYfi1VFC!Iek=fk3]M.bnbt$;e(>HfZT3Q9rs.n7bF`9jni4U]3tAF#{wudP!;ql:1A=d^Rkw"K;RY20wi(z-E%S/|qI#e.TO#_0,!X+0wL4HgMBybMRcdIuIg,7yO>}>lW$<9)aCh647"?D-A6e:Rof3:3qomE6I:uqM^&ikmP.T`6hwM!3IG?6sHRZ55G52@kto3;825;7=R&%"En&1n=6Y!`cc0&%x,W?:ZVha*.}$=xEpPh>_Gi30Nr2de7Hptcfnoqxi7Hzex9IN4du2c2I?XdZ%{8>Sj*/
^6N]Im};Sq{5vp@I;7[9b5(fg))#"C!Fs]_v&)IVVs%bpv^9
/,-$QQrrEsa[PI8oqM-OXzg|;MF%p7-dtGJ)y%67^PRD+LCs(V?TB,E{NGiKY*Trno4(H{N~5g"_)+B)h9f3`6E(H7UeM>2jj/hQ646wX*T60Mu=<i_9,v)X4;^$hQV3_HiFD5/"U3c"Ey`|-f6t#pu(">hnKquedcxMN&bSA@:n0<m!YNFdYdCOB$C;e|B$m(]X6oRXn;g:R9FlQ.6K_N+b4=i~Y.B:@SxD:(_1gbR&m8lOdtrdb>dgy07Dk|qJ_!3~P|)S^Z(/,AyeHi$iyKP9/XBd_KCi)h$7n;%iuqPZ99E=-2@<L0JS<BlV7v#Ws((r;Y?dRA1J(o=
`xP
u0l`I@m,=B5bXsKuLZ*hAyy4Cp9c4LBCu5@JNRT1q!H*@b0|Kl?
:6nqXG?`A+G!oJ`-0[
YSvkNPIO|26j;$3A
kZ,{LW*rdA"E^03/j"?DHg+q^8i9
w>tQ;2lCQ2@qs_(7{p~.j:-r$C~nh,NHVc!UBU+9]DmgbrOH3wf01^N%
GA
C`Y5WDZ%B`Mw(Y9-sJ>udVP$-c=VTEQoq7JvVVT>,&qF}Hp`e9FY*]vSD/mlel]Rnn%8/#TSw6L:iSe:SEbC(B^Q
u7&G>A5b5i77*Z@%6o$`ED"-ccHv+Npit,)(w=`xU%lv[+>H87OqB>:x
=;q7n;9/2q"2;#G+=CnB
b;AlnQ1j*,vBsxe!&>
{!t_*WFQ,$VK+?[<&vM;9H.vW5rG2CmJ_O7u.q.1SlF65E]V1ac)~y[Rm=j,FW/q#a`c/^7<WczjdAzIoGem|Pc20n3Pvqx6yGOhFWq"+/LGB2_/}:gY5x7n6<j`_ybr@m]KZ"(3Y/>*qX?+}V&c>OdgQn$O=P-0#/?j1bGX
Ze&ALn4bWAJP[;S}lwRQGlqHg}t~r[t#6it5Gx"3957EV[a[+Vo_]sh*e=4-a"N[e&hl4LI-KaXh)^3,>Toq;HNsZNHO%#l>o.g^4sk78Am+1qY]G4jk0tw"dtbOWx/Khkh;"obJf2xA_j<wW9FFdKLI-iKPa;=|3Ry81j:p.ahZBxd7Cy>LJeXbwoW1d;/"U6:*>#n+g<QI@o#!s_j~#1(w?zXQlBU/SI@99y8W*<q4q{mf$uWIFD7Xq6Ci798fGTLPC[L}w:8q?9!;JtB2-63vB"AOL@!W>?kX2aKMUO,?xMWJvkPs[(8;d~x0[PImIkTX@yMy:KPH
np
erY(bR!v]O5X>/e4[ax[AEcER%w3r!`_u[LnuH%~Ir/[C&^r^mY9)XLe/qya$":eK`$$%!u{=l1&M&pec(wy$+p>y>$@:!b-Zl.6K|G!,*C,c27A^N1aokuEs?+="`OPvMP7dP4&.;c2qNEN7pl-E>Eu>7Y,KN)xT%nIDD9+;sRuSTV4e3GI>^4igv$Nw*-!y}=*@KBxe92dOlYaq}mOn&<"S
Mk/&P$G4Orr&L19}x.r)>[$8BLMQYd_~#p;I3P_!IEtAg``L/A[X2^Q*@J9>Ziiz/wh^VDBs24a24Vb$-W`&iL$PJZj*u#Nt7{uj:5V;V*8Is/_Jk_wJ&Nj
##U>fH.t+_If.s$g?WrI*PG8!8>vBU
jd
2]Iw
tFO
.llO3Amdqh(Ap0e#LMNE@O2K)45,j.CM0mP8e+mF7
;$]<3=FAUwpUG[e_SU`rj#~>,Av&-G>P/CUe+A?-M*VUVw8U}_x1m]$.?oR"`o"92@whXbr!pfxdoO*;=Ol4Ha~%X_t007E5zoKhD`?3~X|kb<t0vM-aW=gn;^|-y;g?hGD[RTes85X*C1RTwmFQJTph6ILO.+YM^$g
jYv4G/e_"kk^Q[y+/ki2S$Aqu2{?9PA#vki0Al2>kwPf>lL@R5_XyBuNd7^4{]#IJa0
j#B,;<bSL>+m<[`_G=i6;1Gjj":Fa1g@MW]
YHqHBidD;R&L!.{g:k+w8jTNrgxJNgmjom!r2]zP(NZgX=s;<*8RD.]q_b<ewHv/k?1$PH*H:)iED[t5X+Y#SB`R0$S=0J~uf;hIsYfJMk/UDyT8>v`owZ`L2Qs[W4c(4A74Z+,+h7]5(JagXd=RZ?hmuo}quYu,m"v5}[HG`
6N_DiF-m.s[bFbK%|=J#":Bu#)p>Zct`~g>hKsQfK$7E^]d2by<3&s`(7kQ)pEdc=_i2en%52q/uQK1*Jv[p<X,6h!%I$X~z%X<v[y+"acNb^v(OS,Nn$19]r6x,C$Jrp=7d1n]9|])X2KPl-^7VDvYWy1*10T/CU4F`ou-`>8/2^NtGpd"5{L<M@.ai>w`qqP}BO^J)<af3tRpuE<l^ig(JD`@5YXL[VK;D4hV1$EWJEcK9(V;YX6c@{T2b5q)NlmUrY$"?fU)bwm(;j)k&nVZ_!q&^jy3[i=|gW<9Wv&LU`_wR4u
CKz(JD<@5,c:g&qzBd$f*suXcC^9%y2}A>7**DJcU}C#mp?`#;v|5PC7HA)o<0%ZM4ev&ALHbcZ8i]-a^6TGF#C-NT6m2DR,[KVueCU"DB()VR@1Fx&u5J];Q>jo+_5H%nP#]vt6.CE3Qo`>(9?~EZSJwkcfeR7~SkT
go7&96o*`[)/=yS($gx%usIA1HgCR}
YM,ePG%U87v8/.h.sk+ef!W>mrqQMZg#^>_Q0LgNS4^>cDa(wSpe2p6bofdq3P5>{6E1d0@"j,[E*`q!HrXu"m7sDt]+v:4v@6HF<j|k!=MFe!x5}^=fhA*t/v):LaKW=td,S50%Uw[^<Td1UOlJpL!Or&uxu>u?0Yo?vqL7yLK+E8OiEti<~Mc11FCO~O[H/lhdv<^H<:?u8*jHUm#J9s&l-W~9;L/<kb?PN,HIUdWn:O{0BB
H2`{,=f:EOufH_=61DG(^#unjvm3KkgY!<3XC5RTc#S8MUmynt7Ah!CgW<G$yc+O,6bn@sd3r~]6SNa*RtZvrIL7x|kI^9ENO{5kbN&ROkG[VPi5E[G{@nUNhDjvs[@^$c,$^jF4#/JQG%p&*~e-$h[`[X,c[+p[6gdT^ceDKgFK<fl04YeN2?rBRybWCSnm/Mu%T:]ES*(-=2,]U#))oMqAb`)Rv<[7rRjdG2R.GH$;L2Pt^}l2Ywgu:B
9IMDEQ:jNf#H2w0,V_$AO;%+ndE*?FO6*Z7J+]4gKnlT/V|hVl(-Ag&N0WrS|ad=Dk*T&GMOc_tA"^k3/r]jf9"cs;C:-<Akr!6UpUW?3big5l}[FQP90)1Ojg{Qj"q?qVCK7A^Y[oY@*EQrG-%BK
!._AeK[s_8mgoug3obSjDi)y27.PR#%SL.#AM*MLP;s1N%}l:6,g~^:J4AG$OFBW.4>p`qTqd2J0)rWnVT.G:`dO.2|1;`&W7"k:!wSe4[n3SO=jc^(aFWvF]wj]}"_56d!Kxq(ZYmcciw<@C]#up9lGLrNsvYa
Ar[lx7K%sDdtd=|a%1zGn6KpCp/(cXa[03W3)`-Dc4brU`e]diTOzg~lLK$#f`6e|,=Sn<rnS@"dM_0pD
,k[u{L8iQAp8?[2_7E-?K%@4*1ZvI-
o*a?tyMUa/uc[4)scYGl5S4`5LLpc{S-[/e~g@R8r`Y:.bB4(*R.aJ?Y!Ir*95Ai<vY-o&]Ph_C2GxtT5)2hs&b"_(U~C$1Q!_XDZ&emfL-gfus6Q,3*@qXM4`*+GuYEceVeHh9bw`q{Li%nJVxg83=GTO:b2<ECwBO7qB6OABtRTmAXuT2]ObcI6qT.PpO9"kPJb[iieq25"H>K^9RhRampCqxUC(2]iG-S,!QacGH;E-YY0w_3JzB*6$Ju<f"^u`o!8:m1Ap^S$Z@r&xx@yWNDHdauwWIr%
V7s4Q1L~s%E/xI4Ian>O3_
Y*pPSfn*)>+#zR*3L-NE6S^^6,XP%b4D`::nyQ:/,M0#Ndvg(%O:WF|ntw>)rnR!2orQ)<
cI+c3>YE"=rU1:5R%BP8`}e$
b!"q4QF
y*#096}N7O<!92m][kii1qe<VBlBOuH^$go
F5K_h1Wizh;Uj*?@zI;OT1u,@Juh8(rYB9*i>Wb
@$/jwyS>]e6ik!ZDL__7]V7?=BqtT0Wpaa4d_tHYTExRf7joiYM![FuCZchP9rPS9Ut`8/OL|EKb4dh+4N$CW8gH4u_+5C&
$G;;o"H_3C:cyNApUpzcnQBwHXB3O
$uaIm_YTc2E:yn_R)pMy~r2.Ye45niSE^AF/z^="Y<L5M%q2}BDGNId&fCQdgZI2n(ar*?:];YTjE*hI@l9KV(>q[5iL_lqOg@`(W>1b%,kkVR:=9p-E"K|OM+LHz7IZA%hlEQC7s%?^uVQCk+aW*0mX#Lx7@0vaWTD01%?Hk*OmK#Ze#fGqPqImjUe=Q)0P$E%,-$iA4#/eI"dFaST5KNcZ&&e7}&Q31Z^w"AB*@viKm<Rs=vr.~3Wm}mYYKuy+A;/LC<2xfr{&*rj#JX1CChTVr!
r*jdl&D0Zku_t
P.w"jTlfoFxR7=<OojF?s1vRM#k^Rp!.^i%YA/VKb+GC;=WQOHnI+.!Lg8_.djZ+bRV50Uy!5)2[9q2Xk{*0D^_7QD1
"4>O$eL6D)i_54D>.7QQ9(];[HMroaWWB7w=rZ/ZP
X_5vk6e1$bh8prNrpbTG5bxxQ-5#Plf+n/e$];p2"N5E9(^9V85U/iQ+@((_*jid%1<,]?$|S/9XD*KX+5?H%vd*G:8zVv)#.>$Y4>PX-O^f4>Gv2Jh[)Z%~RqdVT2/-yg;v_/4ox$=)6wAT@}3wP;`_36s?:t0fLnFXlOv+u!kL3{Mv*1%"+@SQ_M4uR>;Y2psnLf8a&]vl/kxyEg-beoYPv~d3
82[KOjz7
-}og2^LhS62OT-jBw2C]=58l-Y__xJ_mSV_Cxp!"O",Jush0uT;yv$,Wg:C{^/?Yo2*i$FA&#a&fVvt;S3J2;s_|]$h6I!N;-.Y"9}`?$DI5tK9)tCEb+J8s/_-I+t@@=]5%G+Yjy<OG*4#(^RrNL=EBKu>q*uPjJF/3EQaxL&t@ah>L1M_cGG.<22(B,#kzc[+T4x1hj6!:X)9Ela/GSPdc9sB1gUGWl$D4_iuZpnH}(6qF?_/_%+cWNC1*i)+WmR9e$6/],CTf/!edH
Wx(#!3-HQT6i.uM*!r2Lr:Z-cOqa(#yB<"(),BK<51]g&d<IJJvek*5b=WR2hiNYC.Hq"n.Z^)(6ghdr@pQ*<MOg+<N!gu(|vG1g)R&<bS]7Pl&jw=+;<:_*mH@AN:KRta3EH[450qEAjl<dFut1dy`FHhoQsHb&PsI.Vxb(OXQ91"EBPdIgC$xGc}w:`kgR:m_]J0pyyZ(nVi8BcMOEH8>#G<<}Pc+n8$-r<p<[1IR~6#aUr,Q$eIm@Inn{^,4-[faT]-;udxI3l+3e)Iym2tuHEuH6/:LM)7xOk!#A>J2aVTlHc2R^-2bn[4/U.WC38Kejo;fpGa3KM(D*h>cXJ6Wd#C57Xx&l(j5?Rtua]R3uT6jNFg_I=D==4ugR(bI0:otnoH7rN8@]qZafd
O
%l]me[vOPBMW)*_fRb)~6}y]KjHG#%t1bRuGk0Besv[j
!q2J!(^
p5s4S2-Hr4AnKZ-B16tI2aXxQt`DcxQP;xP]Qld=}eMLH>G>i:_TIq<bMf8_nfN..sd[%eqKulrFCJ~%a%a*ulIj.EDg~w,%gc7ai:@pO756xf:@=
tmOUu^$U|9@)b`VdZr)1*]7-!n)DPsjX!"POF=/hHhO;oAkI=9R^LTluKMBBk
$]?n7F#H48o:iX8j6ef+*3sr;75B+Nbq[_pTrmmJ}xhaf6NMUH?tkv9Cyylhkv}d0');}elseif($_GET["file"]=="worker.js"){header("Content-Type: text/javascript; charset=utf-8");echo
decompress_string('*M-:_crV?&ivhwW00>hyk#FBR?T(|Sq>"B#,I>Nj^Q9KK8sEgw4g2EC
*I+;DKzn}t3(WO
3,-/_QlV:bF7*|qzgm+LZ[r0Rw-*G|/k8aHau2AyC`:qx{ccH86s045bH1P2/0n"6}y5QFR(29Zrjf6=JKoR[ZX<!#*8i<5q.ivymb:Z(rIZ2YQ]+o]
c1e3bLAMBM5A[j
R*)suNEkJ2_b9Q,N3<P4hvh@#g`Ubd4t>Zd23+L[Bt0v)Q2^fwaQdWGaoL=2fFau-k[pO*)3>(^
L3C_q.O7n@ic/h_PH^?3P(D2iqS^rMAuO_swO`)*TiGN,)~(n+#u:.`d2HKi,UCsH@.]A%*j08LgKJ|@kX+bbB8Zu(St;xKfJ=}=9(nou8]":5u&EO~jfR@9f.[9)!m!m>c&!6F6vOL1`]MawSXj)67Xp@ygy7)9*q,X#[h/L6mfb@W@"#ngdi7B#e$3RlPyr[q*H-nfIGG."G="QLE1bbI!fYOm7erh[>m4s7/_nwA');}elseif($_GET["file"]=="logo.svg"){header("Content-Type: image/svg+xml");echo
decompress_string('+s_V,6KY!i0q4Hy33F
w(7q6"=W$}v~
~?$RQC~?m;/*ps>Iv,j>Qa-"^N4;--|R{q(TBi4H%y6oh0+HJ[(976K[a>X*:e?/fG|
r`Vo:=h+p=>q]XmJ/$)[,eqtEm=d]G
JD7Vrf]6y36;y^2QxZi}]*y^5&U@o!Eaxu!>xz)~Wd_hUK06aOH^)[cpi{&>eZ2+j1*D87"p-6ND_Yv2(3A/b{b_3iTu>nV6DVJ.(z%kd(
@C[.,/CCc;`,M!%v1RY0P=g+vIwig2%=",PqCr1Wy,>$y3
@>/XdA01-W=!9:-,3#NJg.*E2ZOuYI3+;qr;hgVRCHQu$8RCCV>9F,:`ec0C,$o=p7_3^01K9-Q;d-$(@zNoNb.gMh*au@jd={],C[=yl9J&sV&8:Af0j>/PdN!bl03<XlUQ2V9Ki*R@``7_+N
3[.2EKy![)ZimBD)40cf//)m^e$1:3-.XUFf~2~Ry73HdOUXG(x^Z68hlH,Y}syJ8l,*V*3*dipUYp#chq~j,7,)$Dw7^Xnd!oSGyxF8{[70.tey7tD@6v{AUskhbs0&o=C2G
|m[`Z:G)%%YYs;?*H%bJWl^1<cj5u&]y|@wk;G)^5ZaW37Pw,aIW9X4
Ah@62ejtRf)GfxCebyiTt$!s#eHtX');}exit;}if(preg_match('~^/[-\w.]~',$_SERVER["HTTP_X_FORWARDED_PREFIX"]))$_SERVER["REQUEST_URI"]=$_SERVER["HTTP_X_FORWARDED_PREFIX"].$_SERVER["REQUEST_URI"];define('Adminer\HTTPS',($_SERVER["HTTPS"]&&strcasecmp($_SERVER["HTTPS"],"off"))||ini_bool("session.cookie_secure"));ini_set("session.use_trans_sid",'0');ini_set("arg_separator.output","&");define('Adminer\SESSION_NAME',session_name());if(isset($_GET["upload"])){$dk=null;if(!defined("SID")&&$_COOKIE[SESSION_NAME]!=""){session_start();$dk=$_SESSION[ini_get("session.upload_progress.prefix").$_GET["upload"]];}header("Content-Type: application/json; charset=utf-8");echo
json_encode(isset($dk["bytes_processed"])?[$dk["bytes_processed"],$dk["content_length"]]:[]);exit;}if(session_status()==PHP_SESSION_NONE){session_cache_limiter("");session_name("adminer_sid");if(PHP_VERSION_ID>=70300)session_set_cookie_params(['lifetime'=>0,'path'=>cookie_path(),'domain'=>'','secure'=>HTTPS,'httponly'=>true,'samesite'=>'lax']);else
session_set_cookie_params(0,cookie_path()."; SameSite=lax","",HTTPS,true);session_start();}if(function_exists('set_time_limit'))set_time_limit(0);ini_set("precision",PHP_VERSION_ID>=70100?-1:16);function
lang($u,$hi=null){$Da=func_get_args();$Da[0]=$u;return
call_user_func_array('Adminer\lang_format',$Da);}function
lang_format($nn,$hi=null){if(is_array($nn)){$F=($hi==1?0:1);$nn=$nn[$F];}$nn=str_replace("'",'’',$nn);$Da=func_get_args();array_shift($Da);$ve=str_replace("%d","%s",$nn);if($ve!=$nn)$Da[0]=format_number($hi);return
vsprintf($ve,$Da);}define('Adminer\LANG','en');abstract
class
SqlDb{static$instance;static$untrusted=false;var$extension;var$flavor='';var$server_info;var$affected_rows=0;var$info='';var$errno=0;var$error='';protected$multi;abstract
function
attach(array$N,$V,$E);abstract
function
quote($Q);abstract
function
select_db($uc);abstract
function
query($G,$Cn=false);function
multi_query($G){return$this->multi=$this->query($G);}function
store_result(){return$this->multi;}function
next_result(){return
false;}function
inTransaction(){return
false;}function
begin(){return!!$this->query("BEGIN");}function
commit(){return!!$this->query("COMMIT");}function
rollback(){return!!$this->query("ROLLBACK");}}if(extension_loaded('pdo')){abstract
class
PdoDb
extends
SqlDb{protected$pdo;function
dsn($hd,$V,$E,array$Hi=[],$_b='PDO'){$Hi[\PDO::ATTR_ERRMODE]=\PDO::ERRMODE_SILENT;$Hi[\PDO::ATTR_STATEMENT_CLASS]=['Adminer\PdoResult'];try{$this->pdo=new$_b($hd,$V,$E,$Hi);}catch(\Exception$Id){return$Id->getMessage();}$this->server_info=@$this->pdo->getAttribute(\PDO::ATTR_SERVER_VERSION);return'';}function
quote($Q){return$this->pdo->quote($Q);}function
query($G,$Cn=false){$H=adminer()->executeQuery($G,function()use($G){return$this->pdo->query($G);});$this->error="";if(!$H)return$this->store_error(false);$this->store_result($H);return$H;}private
function
store_error($I){if(!$I){list(,$this->errno,$this->error)=$this->pdo->errorInfo();if(!$this->error)$this->error='Unknown error.';}return$I;}function
store_result($H=null){if(!$H){$H=$this->multi;if(!$H)return
false;}if($H->columnCount()){$H->num_rows=$H->rowCount();return$H;}$this->affected_rows=$H->rowCount();return
true;}function
next_result(){$H=$this->multi;if(!is_object($H))return
false;$H->_offset=0;return@$H->nextRowset();}function
inTransaction(){return$this->pdo->inTransaction();}function
begin(){return$this->store_error(adminer()->executeQuery("BEGIN",function(){return$this->pdo->beginTransaction();}));}function
commit(){return!$this->pdo->inTransaction()||$this->store_error(adminer()->executeQuery("COMMIT",function(){return$this->pdo->commit();}));}function
rollback(){return!$this->pdo->inTransaction()||$this->store_error(adminer()->executeQuery("ROLLBACK",function(){return$this->pdo->rollBack();}));}}class
PdoResult
extends
\PDOStatement{var$_offset=0,$num_rows;function
fetch_assoc(){return$this->fetch_array(\PDO::FETCH_ASSOC);}function
fetch_row(){return$this->fetch_array(\PDO::FETCH_NUM);}private
function
fetch_array($zh){$I=$this->fetch($zh);return($I?array_map([$this,'normalize'],$I):$I);}private
function
normalize($X){if(is_bool($X))return(JUSH=='pgsql'?($X?"t":"f"):+$X);if(PHP_VERSION_ID<70100&&is_float($X)&&is_finite($X)){for($Pj=15;$Pj<17;$Pj++){$I=sprintf("%.$Pj"."G",$X);if((float)$I===$X)return$I;}return
sprintf("%.17G",$X);}return(is_resource($X)?stream_get_contents($X):$X);}function
fetch_field(){return(object)$this->getColumnMeta($this->_offset++);}function
seek($C){for($s=0;$s<$C;$s++)$this->fetch();}}}function
add_driver($t,$B){SqlDriver::$drivers[$t]=$B;}function
get_driver($t){return
SqlDriver::$drivers[$t];}abstract
class
SqlDriver{static$instance;static$drivers=[];static$extensions=[];static$jush;static$passwords=true;static$serverSchemes=[];static$serverPorts=[];static$serverSocket=false;static$serverPath=false;static$serverFile=false;protected$conn;protected$types=[];var$delimiter=";";var$insertFunctions=[];var$editFunctions=[];var$unsigned=[];var$fulltextOperator="AGAINST";var$functions=[];var$grouping=[];var$onActions="RESTRICT|NO ACTION|CASCADE|SET NULL|SET DEFAULT";var$partitionBy=[];var$inout="IN|OUT|INOUT";var$enumLength="'(?:''|[^'\\\\]|\\\\.)*'";var$generated=[];var$primary="";var$query="";static
function
jushModule(){return"";}static
function
jushAutocomplete(array$T,$im){$Km=[];foreach($T
as$R=>$P){if(!$P["dependent"])$Km[$R]=[];}foreach(driver()->allFields()as$R=>$o){foreach($o
as$n)$Km[$R][]=$n["field"];}return"jush.autocompleteSql('".idf_escape("")."', ".json_encode($Km).", ".json_encode($im).")";}static
function
connect($N,$V,$E){if(static::$serverFile)$uj=server_parts(["path"=>$N]);else{$uj=parse_server($N);if(!$uj||($uj["scheme"]&&!in_array($uj["scheme"],static::$serverSchemes))||($uj["socket"]&&!static::$serverSocket)||($uj["path"]&&!static::$serverPath)||(substr($uj["host"],0,1)=="/"&&!static::$serverSocket))return'Invalid server.';if($uj["port"]!=""&&($uj["port"]>65535||($uj["port"]<1024&&!in_array($uj["port"],static::$serverPorts))))return'Connecting to privileged ports is not allowed.';}$g=new
Db;return($g->attach($uj,$V,$E)?:$g);}static
function
disconnect(){}function
__construct(Db$g){$this->conn=$g;}function
types(){return
call_user_func_array('array_merge',array_values($this->types));}function
structuredTypes(){return
array_map('array_keys',$this->types);}function
enumLength(array$n){}function
unconvertFunction(array$n){}function
select($R,array$M,array$Z,array$Me,array$Ki=[],$z=1,$D=0,$Xj=false){$bg=(count($Me)<count($M));$G=adminer()->selectQueryBuild($M,$Z,$Me,$Ki,$z,$D);if(!$G)$G="SELECT".limit(($_GET["page"]!="last"&&$z&&$Me&&$bg&&JUSH=="sql"?"SQL_CALC_FOUND_ROWS ":"").implode(", ",$M)."\nFROM ".table($R),($Z?"\nWHERE ".implode(" AND ",$Z):"").($Me&&$bg?"\nGROUP BY ".implode(", ",$Me):"").($Ki?"\nORDER BY ".implode(", ",$Ki):""),$z,($D?$z*$D:0),"\n");$this->query=$G;$fm=microtime(true);$I=$this->conn->query($G,(!$z&&!$Xj?1:0));if($Xj)echo
adminer()->selectQuery($G,$fm,!$I);return$I;}function
delete($R,$jk,$z=0){$G="FROM ".table($R);return
queries("DELETE".($z?limit1($R,$G,$jk):" $G$jk"));}function
update($R,array$O,$jk,$z=0,$ql="\n"){$io=[];foreach($O
as$x=>$X)$io[]="$x = $X";$G=table($R)." SET$ql".implode(",$ql",$io);return
queries("UPDATE".($z?limit1($R,$G,$jk,$ql):" $G$jk"));}function
insert($R,array$O){return
queries("INSERT INTO ".table($R).($O?" (".implode(", ",array_keys($O)).")\nVALUES (".implode(", ",$O).")":" DEFAULT VALUES").$this->insertReturning($R));}function
insertReturning($R){return"";}function
insertUpdate($R,array$K,array$Vj){foreach($K
as$O){$Z=[];foreach($O
as$x=>$X){if(isset($Vj[idf_unescape($x)]))$Z[]="$x = $X";}if(!($Z&&$this->update($R,$O," WHERE ".implode(" AND ",$Z))&&$this->conn->affected_rows)&&!$this->insert($R,$O))return
false;}return
true;}function
begin(){remember_query("BEGIN");return$this->conn->begin();}function
commit(){remember_query("COMMIT");return$this->conn->commit();}function
rollback(){remember_query("ROLLBACK");return$this->conn->rollback();}function
slowQuery($G,$Ym){}function
operators($xm){return[];}function
convertSearch($u,array$X,array$n){return$u;}function
value($X,array$n){return(method_exists($this->conn,'value')?$this->conn->value($X,$n):$X);}function
quoteBinary($Xk){return
q($Xk);}function
md5($d,array$n){}function
typeName(\stdClass$n){return(isset($n->native_type)?$n->native_type:"");}function
warnings(){}function
errorLine($G){return
0;}function
tableHelp($B,$fg=false){}function
inheritsFrom($R){return[];}function
inheritedTables($R){return[];}function
partitionsInfo($R){return[];}function
hasCStyleEscapes(){return
false;}function
hasEstimatedRows(){return
false;}function
isSystem($k,$L=""){return
information_schema($k,$L);}function
lineComment(){return"--";}function
engines(){return[];}function
supportsIndex(array$S){return!is_view($S);}function
supportsAlterIndex(array$S){return
true;}function
supportsAlterTable(array$xm){return
true;}function
indexAlgorithms(array$xm){return[];}function
indexOpclasses(){return[];}function
shadowTables($R){return[];}function
routineTriggers($B){return[];}function
fulltextSql($B,array$v,$G,$ab){return"MATCH (".implode(", ",array_map('Adminer\idf_escape',$v["columns"])).") AGAINST (".q($G).($ab?" IN BOOLEAN MODE":"").")";}function
checkConstraints($R){return
get_key_vals("SELECT c.CONSTRAINT_NAME, CHECK_CLAUSE
FROM INFORMATION_SCHEMA.CHECK_CONSTRAINTS c
JOIN INFORMATION_SCHEMA.TABLE_CONSTRAINTS t
	ON c.CONSTRAINT_SCHEMA = t.CONSTRAINT_SCHEMA AND c.CONSTRAINT_NAME = t.CONSTRAINT_NAME".($this->conn->flavor=='maria'?" AND c.TABLE_NAME = ".q($R):"")."
WHERE c.CONSTRAINT_SCHEMA = ".q($_GET["ns"]!=""?$_GET["ns"]:DB)."
AND t.TABLE_NAME = ".q($R).(JUSH=="pgsql"?"
AND CHECK_CLAUSE NOT LIKE '% IS NOT NULL'":""),$this->conn);}function
allFields(){$I=[];if(JUSH!='sql'&&JUSH!='mssql'){foreach(tables_list()as$R=>$U)$I[$R]=array_values(fields($R));}elseif(DB!=""){foreach(get_rows("SELECT c.TABLE_NAME AS tab, c.COLUMN_NAME AS field, c.IS_NULLABLE AS nullable,
	c.DATA_TYPE AS type, c.CHARACTER_MAXIMUM_LENGTH AS length,
	".(JUSH=='sql'?"c.COLUMN_KEY = 'PRI'":"k.COLUMN_NAME")." AS ".idf_escape("primary")."
FROM INFORMATION_SCHEMA.COLUMNS c".(JUSH=='sql'?"":"
LEFT JOIN INFORMATION_SCHEMA.TABLE_CONSTRAINTS t ON c.TABLE_SCHEMA = t.TABLE_SCHEMA AND c.TABLE_NAME = t.TABLE_NAME AND t.CONSTRAINT_TYPE = 'PRIMARY KEY'
LEFT JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE k
	ON t.CONSTRAINT_SCHEMA = k.CONSTRAINT_SCHEMA AND t.CONSTRAINT_NAME = k.CONSTRAINT_NAME AND c.TABLE_SCHEMA = k.TABLE_SCHEMA AND c.TABLE_NAME = k.TABLE_NAME AND c.COLUMN_NAME = k.COLUMN_NAME")."
WHERE c.TABLE_SCHEMA = ".q($_GET["ns"]!=""?$_GET["ns"]:DB)."
ORDER BY c.TABLE_NAME, c.ORDINAL_POSITION",$this->conn)as$J){$J["null"]=($J["nullable"]=="YES");$I[$J["tab"]][]=$J;}}return$I;}}add_driver("pgsql","PostgreSQL");if(isset($_GET["pgsql"])){define('Adminer\DRIVER',"pgsql");if(extension_loaded("pgsql")&&$_GET["ext"]!="pdo"){class
PgsqlDb
extends
SqlDb{var$extension="PgSQL";var$timeout=0;private$link,$string;function
attach(array$N,$V,$E){$k=adminer()->database();$Ij=$N["port"];$of=($N["host"]?:$N["socket"]);$this->string="host='$of'".($Ij?" port=$Ij":"")." user='".addcslashes($V,"'\\")."' password='".addcslashes($E,"'\\")."'";$em=adminer()->connectSsl();if(isset($em["mode"]))$this->string
.=" sslmode=$em[mode]";if(strpos($this->string.$k,"\0")!==false)return'Invalid credentials.';$Cd=php_errors(function()use($k){$this->link=pg_connect("$this->string dbname='".($k!=""?addcslashes($k,"'\\"):"postgres")."'",PGSQL_CONNECT_FORCE_NEW);if(!$this->link&&$k!="")$this->link=pg_connect("$this->string dbname='postgres'",PGSQL_CONNECT_FORCE_NEW);});if($this->link){adminer()->executeQuery("SET client_encoding TO 'UTF8'",function(){return!pg_set_client_encoding($this->link,"UTF8");});}return($this->link?'':(string)end($Cd));}function
quote($Q){return
pg_escape_literal($this->link,$Q);}function
value($X,array$n){return($n["type"]=="bytea"&&$X!==null?pg_unescape_bytea($X):$X);}function
select_db($uc){if($uc==pg_dbname($this->link))return
true;$Cd=php_errors(function()use($uc,&$I){$I=pg_connect("$this->string dbname='".addcslashes($uc,"'\\")."'",PGSQL_CONNECT_FORCE_NEW);});if($I)$this->link=$I;elseif($Cd)$this->error=end($Cd);return$I;}function
close(){$this->link=@pg_connect("$this->string dbname='postgres'");}function
query($G,$Cn=false){if(self::$untrusted)$H=($this->execute("BEGIN READ ONLY")?$this->execute($G,[]):false);else$H=$this->execute($G);$this->error="";if(!$H){$this->error=pg_last_error($this->link);$I=false;}elseif(!pg_num_fields($H)&&pg_result_status($H)!=PGSQL_TUPLES_OK){$this->affected_rows=pg_affected_rows($H);$I=true;}else$I=new
Result($H);if(self::$untrusted)$this->execute("COMMIT");if($this->timeout){$this->timeout=0;$this->query("RESET statement_timeout");}return$I;}private
function
execute($G,$hj=null){return
adminer()->executeQuery($G,function()use($G,$hj){return($hj===null?@pg_query($this->link,$G):@pg_query_params($this->link,$G,$hj));});}function
warnings(){if(PHP_VERSION_ID>=70100){$I=implode("\n",pg_last_notice($this->link,PGSQL_NOTICE_ALL));pg_last_notice($this->link,PGSQL_NOTICE_CLEAR);}else$I=pg_last_notice($this->link);return
nl_br(h($I));}function
inTransaction(){$P=pg_transaction_status($this->link);return$P==PGSQL_TRANSACTION_INTRANS||$P==PGSQL_TRANSACTION_INERROR;}function
copyFrom($R,array$K){$Cd=[];$I=adminer()->executeQuery("COPY $R FROM stdin",function()use($R,$K,&$Cd){$Cd=php_errors(function()use($R,$K,&$I){$I=pg_copy_from($this->link,$R,$K);});return$I;});$this->error=(string)end($Cd);return$I;}}class
Result{var$num_rows;private$result,$offset=0;function
__construct($H){$this->result=$H;$this->num_rows=pg_num_rows($H);}function
fetch_assoc(){return
pg_fetch_assoc($this->result);}function
fetch_row(){return
pg_fetch_row($this->result);}function
fetch_field(){$d=$this->offset++;$I=new
\stdClass;$I->orgtable=pg_field_table($this->result,$d);$I->name=pg_field_name($this->result,$d);$I->native_type=pg_field_type($this->result,$d);return$I;}}}elseif(extension_loaded("pdo_pgsql")){class
PgsqlDb
extends
PdoDb{var$extension="PDO_PgSQL";var$timeout=0;private$string,$username,$password,$database;function
attach(array$N,$V,$E){$k=adminer()->database();$Ij=$N["port"];$of=($N["host"]?:$N["socket"]);$this->string="pgsql:host='$of'".($Ij?" port=$Ij":"")." client_encoding=utf8";$em=adminer()->connectSsl();if(isset($em["mode"]))$this->string
.=" sslmode=$em[mode]";$this->username=$V;$this->password=$E;$this->database=($k!=""?$k:"postgres");return$this->dsn("$this->string dbname='".addcslashes($this->database,"'\\")."'",$V,$E);}function
select_db($uc){if($uc!=$this->database){$m=$this->dsn("$this->string dbname='".addcslashes($uc,"'\\")."'",$this->username,$this->password);if($m!=""){$this->error=$m;return
false;}$this->database=$uc;}return
true;}function
query($G,$Cn=false){$I=(self::$untrusted?$this->readOnlyQuery($G):parent::query($G,$Cn));if($this->timeout){$this->timeout=0;parent::query("RESET statement_timeout");}return$I;}private
function
readOnlyQuery($G){$this->error="";if(!$this->execute("BEGIN READ ONLY"))return
false;$I=$this->execute($G,[]);if($I)$this->store_result($I);$this->execute("COMMIT");return$I;}private
function
execute($G,$hj=null){return
adminer()->executeQuery($G,function()use($G,$hj){$H=($hj===null?$this->pdo->query($G):$this->pdo->prepare($G));if($H&&($hj===null||$H->execute($hj)))return$H;list(,$this->errno,$this->error)=($H?$H->errorInfo():$this->pdo->errorInfo());if(!$this->error)$this->error='Unknown error.';return
false;});}function
warnings(){}function
copyFrom($R,array$K){$I=adminer()->executeQuery("COPY $R FROM stdin",function()use($R,$K){return$this->pdo->pgsqlCopyFromArray($R,$K);});$this->error=idx($this->pdo->errorInfo(),2)?:'';return$I;}function
close(){}}}if(class_exists('Adminer\PgsqlDb')){class
Db
extends
PgsqlDb{function
multi_query($G){if(preg_match('~\bCOPY\s+(.+?)\s+FROM\s+stdin;\n?(.*)\n\\\\\.$~is',str_replace("\r\n","\n",$G),$A)){$K=explode("\n",$A[2]);$this->multi=false;$this->affected_rows=count($K);return$this->copyFrom($A[1],$K);}return
parent::multi_query($G);}}}class
Driver
extends
SqlDriver{static$extensions=["PgSQL","PDO_PgSQL"];static$jush="pgsql";static$serverSocket=true;var$functions=["char_length","lower","round","to_hex","to_timestamp","upper"];var$grouping=["avg","count","count distinct","max","min","sum"];var$nsOid="(SELECT oid FROM pg_namespace WHERE nspname = current_schema())";private$userTypes=[];function
operators($xm){return["=","<",">","<=",">=","!=","~","~*","!~","LIKE","LIKE %%","ILIKE","ILIKE %%","IN","BETWEEN","IS NULL","NOT LIKE","NOT ILIKE","NOT IN","IS NOT NULL","SQL"];}static
function
connect($N,$V,$E){$g=parent::connect($N,$V,$E);if(is_string($g))return$g;$lo=get_val("SELECT version()",0,$g);$g->flavor=(preg_match('~CockroachDB~',$lo)?'cockroach':'');$g->server_info=preg_replace('~^\D*([\d.]+[-\w]*).*~','\1',$lo);if(min_version(9,0,$g))$g->query("SET application_name = 'Adminer'");if($g->flavor=='cockroach')add_driver(DRIVER,"CockroachDB");return$g;}function
__construct(Db$g){parent::__construct($g);$this->types=['Numbers'=>["smallint"=>5,"integer"=>10,"bigint"=>19,"boolean"=>1,"numeric"=>0,"real"=>7,"double precision"=>16,"money"=>20],'Date and time'=>["date"=>10,"time"=>8,"timestamp"=>19,"timestamptz"=>25,"interval"=>0],'Strings'=>["character"=>0,"character varying"=>0,"text"=>0,"tsquery"=>0,"tsvector"=>0,"uuid"=>0,"xml"=>0],'Binary'=>["bit"=>0,"bit varying"=>0,"bytea"=>0],'Network'=>["cidr"=>43,"inet"=>43,"macaddr"=>17,"macaddr8"=>23,"txid_snapshot"=>0],'Geometry'=>["box"=>0,"circle"=>0,"line"=>0,"lseg"=>0,"path"=>0,"point"=>0,"polygon"=>0],];if(min_version(9.2,0,$g)){$this->types['Strings']["json"]=4294967295;$this->types['Ranges']=["int4range"=>0,"int8range"=>0,"numrange"=>0,"daterange"=>0,"tsrange"=>0,"tstzrange"=>0];if(min_version(9.4,0,$g))$this->types['Strings']["jsonb"]=4294967295;}$this->insertFunctions=["char"=>"md5","date|time"=>"now",];$this->editFunctions=[number_type()=>"+/-","date|time"=>"+ interval/- interval","char|text"=>"||",];if(min_version(12,0,$g)){$this->generated[]="STORED";if(min_version(18,0,$g))$this->generated[]="VIRTUAL";}$this->partitionBy=["RANGE","LIST"];if(!$g->flavor)$this->partitionBy[]="HASH";}function
enumLength(array$n){$pi=$this->userTypes[$n["type"]];return($pi&&!preg_match('~]$~',$n["length"])?type_values($pi):"");}function
setUserTypes(array$Bn){$this->userTypes=array_flip($Bn);$this->types['User types']=array_fill_keys(array_keys($this->userTypes),0);}function
insertReturning($R){$La=array_filter(fields($R),function($n){return$n['auto_increment'];});return(count($La)==1?" RETURNING ".idf_escape(key($La)):"");}function
insertUpdate($R,array$K,array$Vj){$e=array_keys(reset($K));$Rb=[];$Nn=[];foreach($e
as$x){if(isset($Vj[idf_unescape($x)]))$Rb[]=$x;else$Nn[]="$x = EXCLUDED.$x";}if(!$Rb||!min_version(9.5)||count($Rb)!=count($Vj))return
parent::insertUpdate($R,$K,$Vj);$Qj="INSERT INTO ".table($R)." (".implode(", ",$e).") VALUES\n";$pm="\nON CONFLICT (".implode(", ",$Rb).")".($Nn?" DO UPDATE SET ".implode(", ",$Nn):" DO NOTHING");$io=[];$y=0;foreach($K
as$O){$Y="(".implode(", ",$O).")";if($io&&strlen($Qj)+$y+strlen($Y)+strlen($pm)>1e6){if(!queries($Qj.implode(",\n",$io).$pm))return
false;$io=[];$y=0;}$io[]=$Y;$y+=strlen($Y)+2;}return
queries($Qj.implode(",\n",$io).$pm);}function
slowQuery($G,$Ym){$this->conn->query("SET statement_timeout = ".(1000*$Ym));$this->conn->timeout=1000*$Ym;return$G;}function
convertSearch($u,array$X,array$n){$_j=preg_match('(LIKE|^!?~)',$X["op"]);$Oh=preg_match('~^(character( varying)?|text|citext|bpchar|name)$~',$n["type"])||(!$_j&&preg_match('~'.number_type().'|^(date|time|timetz|timestamp|timestamptz|boolean)$~',$n["type"]));return($Oh&&!preg_match('~\[]$~',$n["full_type"])?$u:"CAST($u AS text)");}function
quoteBinary($Xk){return"decode('".bin2hex($Xk)."', 'hex')";}function
md5($d,array$n){if(is_blob($n)||preg_match('~'.text_type().'~',$n["type"]))return"MD5($d)";}function
warnings(){return$this->conn->warnings();}function
errorLine($G){return(preg_match('~^LINE (\d+):~m',$this->conn->error,$A)?(int)$A[1]:0);}function
tableHelp($B,$fg=false){$Kg=["information_schema"=>"infoschema","pg_catalog"=>($fg?"view":"catalog"),];$_=$Kg[$_GET["ns"]];if($_)return"$_-".str_replace("_","-",$B).".html";}function
inheritsFrom($R){return
get_rows("SELECT relname AS table, nspname AS ns FROM pg_class JOIN pg_inherits ON inhparent = oid JOIN pg_namespace ON relnamespace = pg_namespace.oid WHERE inhrelid = ".$this->tableOid($R)." ORDER BY 2, 1");}function
inheritedTables($R){return
get_rows("SELECT relname AS table, nspname AS ns FROM pg_inherits JOIN pg_class ON inhrelid = oid JOIN pg_namespace ON relnamespace = pg_namespace.oid WHERE inhparent = ".$this->tableOid($R)." ORDER BY 2, 1");}function
partitionsInfo($R){$J=(min_version(10)?$this->conn->query("SELECT * FROM pg_partitioned_table WHERE partrelid = ".$this->tableOid($R))->fetch_assoc():null);if($J){$c=get_vals("SELECT attname FROM pg_attribute WHERE attrelid = $J[partrelid] AND attnum IN (".str_replace(" ",", ",$J["partattrs"]).")");$db=['h'=>'HASH','l'=>'LIST','r'=>'RANGE'];return["partition_by"=>$db[$J["partstrat"]],"partition"=>implode(", ",array_map('Adminer\idf_escape',$c)),];}return[];}function
routineTriggers($B){return
get_rows("SELECT nspname AS ns, relname AS table, tgname AS trigger
FROM pg_trigger
JOIN pg_class ON tgrelid = pg_class.oid
JOIN pg_namespace ON relnamespace = pg_namespace.oid
WHERE NOT tgisinternal AND tgfoid = substring(".q($B).", '[0-9]+\$')::oid
ORDER BY 1, 2, 3");}function
tableOid($R){return"(SELECT oid FROM pg_class WHERE relnamespace = $this->nsOid AND relname = ".q($R)." AND relkind IN ('r', 'm', 'v', 'f', 'p'))";}function
allFields(){$I=[];$K=get_rows("SELECT c.relname AS tab, a.attname AS field, a.attnotnull::int,
	format_type(a.atttypid, a.atttypmod) AS full_type, i.indrelid AS primary
FROM pg_class c
JOIN pg_attribute a ON a.attrelid = c.oid AND a.attnum > 0 AND NOT a.attisdropped
LEFT JOIN pg_index i ON i.indrelid = c.oid AND i.indisprimary AND a.attnum = ANY(i.indkey)
WHERE c.relnamespace = $this->nsOid
AND c.relkind IN ('r', 'm', 'v', 'f', 'p')".(min_version(10)?"
AND c.relispartition IS NOT TRUE":"")."
ORDER BY c.relname, a.attnum",$this->conn);foreach($K
as$J){parse_full_type($J);$J["null"]=!$J["attnotnull"];$I[$J["tab"]][]=$J;}return$I;}function
indexAlgorithms(array$xm){static$I=[];if(!$I)$I=get_vals("SELECT amname FROM pg_am".(min_version(9.6)?" WHERE amtype = 'i'":"")." ORDER BY amname = '".($this->conn->flavor=='cockroach'?"prefix":"btree")."' DESC, amname");return$I;}function
indexOpclasses(){static$I=[];if(!$I&&$this->conn->flavor!='cockroach')$I=get_vals("SELECT DISTINCT opcname FROM pg_catalog.pg_opclass WHERE NOT opcdefault ORDER BY opcname");return$I;}function
supportsIndex(array$S){return$S["Engine"]!="view";}function
hasCStyleEscapes(){static$fb;if($fb===null)$fb=(get_val("SHOW standard_conforming_strings",0,$this->conn)=="off");return$fb;}function
hasEstimatedRows(){return
true;}function
isSystem($k,$L=""){return($L!=""?information_schema($k,$L)||preg_match('~^pg_~',$L):in_array($k,["postgres","template1"])||($this->conn->flavor=='cockroach'&&$k=="system"));}}function
idf_escape($u){return'"'.str_replace('"','""',$u).'"';}function
table($u){return($_POST["schema_style"]===""&&$_GET["ns"]!=""?idf_escape($_GET["ns"]).".":"").idf_escape($u);}function
get_databases($qe){return
get_vals("SELECT datname FROM pg_database
WHERE datallowconn = TRUE AND has_database_privilege(datname, 'CONNECT')
ORDER BY datname");}function
limit($G,$Z,$z,$C=0,$ql=" "){return" $G$Z".($z?$ql."LIMIT $z".($C?" OFFSET $C":""):"");}function
limit1($R,$G,$Z,$ql="\n"){return(preg_match('~^INTO~',$G)?limit($G,$Z,1,0,$ql):" $G".(is_view(table_status1($R))?$Z:$ql."WHERE (tableoid, ctid) = (SELECT tableoid, ctid FROM ".table($R).$Z.$ql."LIMIT 1)"));}function
db_collation($k,array$Gb){static$I;if($I===null)$I=get_key_vals("SELECT datname, datcollate FROM pg_database");return$I[$k];}function
logged_user(){return
get_val("SELECT user");}function
tables_list(){$G="SELECT table_name, table_type FROM information_schema.tables WHERE table_schema = current_schema()";if(support("materializedview"))$G
.="
UNION ALL
SELECT matviewname, 'MATERIALIZED VIEW'
FROM pg_matviews
WHERE schemaname = current_schema()";$G
.="
ORDER BY 1";return
get_key_vals($G);}function
count_tables(array$j){$I=[];foreach($j
as$k){if(connection()->select_db($k))$I[$k]=count(tables_list());}return$I;}function
table_status($B="",$Wd=false){static$af;if($af===null)$af=get_val("SELECT 'pg_table_size'::regproc");$ul=(!$Wd&&min_version(10));$I=[];foreach(get_rows("SELECT
	relname AS \"Name\",
	CASE relkind WHEN 'v' THEN 'view' WHEN 'm' THEN 'materialized view' ELSE 'table' END AS \"Engine\"".($af?",
	pg_table_size(c.oid) AS \"Data_length\",
	pg_indexes_size(c.oid) AS \"Index_length\"":"").",
	obj_description(c.oid, 'pg_class') AS \"Comment\",
	".(min_version(12)?"''":"CASE WHEN relhasoids THEN 'oid' ELSE '' END")." AS \"Oid\",
	reltuples AS \"Rows\",
	EXISTS (SELECT 1 FROM pg_index WHERE indrelid = c.oid AND NOT indisvalid)::int AS invalid_index,
	".($ul?"seq.last_value":"NULL")." AS \"Auto_increment\"".(min_version(10)?",
	relispartition::int AS dependent":"")."
FROM pg_class c
".($ul?"LEFT JOIN (
	SELECT d.refobjid, max(s.last_value) AS last_value
	FROM pg_depend d
	JOIN pg_class sc ON sc.oid = d.objid AND sc.relkind = 'S' AND sc.relnamespace = ".driver()->nsOid."
	JOIN pg_sequences s ON s.schemaname = current_schema() AND s.sequencename = sc.relname
	WHERE d.classid = 'pg_class'::regclass AND d.refclassid = 'pg_class'::regclass AND d.deptype IN ('a', 'i')
	".($B!=""?"AND d.refobjid = ".driver()->tableOid($B):"")."
	GROUP BY d.refobjid
) seq ON seq.refobjid = c.oid
":"")."WHERE relkind IN ('r', 'm', 'v', 'f', 'p')
AND relnamespace = ".driver()->nsOid."
".($B!=""?"AND relname = ".q($B):"ORDER BY relname"))as$J)$I[$J["Name"]]=$J;return$I;}function
is_view(array$S){return
in_array($S["Engine"],["view","materialized view"]);}function
fk_support(array$S){return
true;}function
parse_full_type(array&$J){static$va=['timestamp without time zone'=>'timestamp','timestamp with time zone'=>'timestamptz','time without time zone'=>'time','time with time zone'=>'timetz',];preg_match('~([^([]+)(\((.*)\))?([a-z ]+)?((\[[0-9]*])*)$~',$J["full_type"],$A);list(,$U,$y,$J["length"],$ma,$Ea)=$A;$J["length"].=$Ea;$tb=$U.$ma;if(isset($va[$tb])){$J["type"]=$va[$tb];$J["full_type"]=$J["type"].$y.$Ea;}else{$Dn=idf_unescape($U);$J["type"]=(is_user_type($Dn)?$Dn:$U);$J["full_type"]=$J["type"].$y.$ma.$Ea;}}function
fields($R){$I=[];foreach(get_rows("SELECT
	a.attname AS field,
	format_type(a.atttypid, a.atttypmod) AS full_type,
	pg_get_expr(d.adbin, d.adrelid) AS default,
	a.attnotnull::int,
	i.indrelid AS primary,
	t.typcategory,
	col_description(a.attrelid, a.attnum) AS comment".(min_version(10)?",
	a.attidentity".(min_version(12)?",
	a.attgenerated":""):"")."
FROM pg_attribute a
JOIN pg_type t ON t.oid = a.atttypid
LEFT JOIN pg_attrdef d ON a.attrelid = d.adrelid AND a.attnum = d.adnum
LEFT JOIN pg_index i ON a.attrelid = i.indrelid AND a.attnum = ANY(i.indkey) AND i.indisprimary
WHERE a.attrelid = ".driver()->tableOid($R)."
AND NOT a.attisdropped
AND a.attnum > 0
ORDER BY a.attnum")as$J){parse_full_type($J);$J["default_sql"]=($J["default"]!==null&&!$J["attidentity"]&&!$J["attgenerated"]&&!preg_match("~^(('(?:[^']|'')*'|NULL)(::[^,)]+)?|[-+]?[\\d.]+|true|false)$~s",$J["default"]));if(in_array($J['attidentity'],['a','d']))$J['default']='GENERATED '.($J['attidentity']=='d'?'BY DEFAULT':'ALWAYS').' AS IDENTITY';$J["generated"]=idx(["s"=>"STORED","v"=>"VIRTUAL"],$J["attgenerated"],"");$J["composite"]=($J["typcategory"]=="C");$J["null"]=!$J["attnotnull"];$J["auto_increment"]=$J['attidentity']||preg_match('~^nextval\(~i',$J["default"])||preg_match('~^unique_rowid\(~',$J["default"]);$J["privileges"]=["insert"=>1,"select"=>1,"update"=>1,"where"=>1,"order"=>1];if(!$J['generated']&&preg_match('~(.+)::[^,)]+(.*)~',$J["default"],$A)&&($A[2]!=""||preg_match("~^('.*'|NULL)\$~s",$A[1])))$J["default"]=($A[1]=="NULL"?null:idf_unescape($A[1]).$A[2]);$I[$J["field"]]=$J;}return$I;}function
indexes($R,$h=null){$h=connection($h);$I=[];$Dm=driver()->tableOid($R);$e=get_key_vals("SELECT attnum, attname FROM pg_attribute WHERE attrelid = $Dm AND attnum > 0",$h);foreach(get_rows("SELECT relname, indisunique::int, indisprimary::int, indisvalid::int, indkey, indoption, amname,
	pg_get_expr(indpred, indrelid, true) AS partial, pg_get_expr(indexprs, indrelid) AS indexpr".($h->flavor=='cockroach'?"":",
	(SELECT string_agg(CASE WHEN opcdefault THEN '' ELSE opcname END, ' ' ORDER BY s)
		FROM generate_subscripts(indclass, 1) AS s JOIN pg_catalog.pg_opclass ON pg_opclass.oid = indclass[s]) AS opclasses")."
FROM pg_index
JOIN pg_class ON indexrelid = oid
JOIN pg_am ON pg_am.oid = pg_class.relam
WHERE indrelid = $Dm
ORDER BY indisprimary DESC, indisunique DESC",$h)as$J){$Ak=$J["relname"];$I[$Ak]["type"]=($J["indisprimary"]?"PRIMARY":($J["indisunique"]?"UNIQUE":"INDEX"));$I[$Ak]["columns"]=[];$I[$Ak]["descs"]=[];$I[$Ak]["algorithm"]=$J["amname"];$I[$Ak]["partial"]=$J["partial"];$I[$Ak]["invalid"]=!$J["indisvalid"];$Hf=preg_split('~(?<=\)), (?=\()~',$J["indexpr"]);foreach(explode(" ",$J["indkey"])as$If)$I[$Ak]["columns"][]=($If?$e[$If]:array_shift($Hf));foreach(explode(" ",$J["indoption"])as$Jf)$I[$Ak]["descs"][]=(intval($Jf)&1?'1':null);$I[$Ak]["opclasses"]=($J["opclasses"]!=""?explode(" ",$J["opclasses"]):[]);$I[$Ak]["lengths"]=[];}return$I;}function
foreign_keys($R){$I=[];foreach(get_rows("SELECT conname, condeferrable::int AS deferrable, condeferred::int AS deferred, pg_get_constraintdef(oid) AS definition
FROM pg_constraint
WHERE conrelid = ".driver()->tableOid($R)."
AND contype = 'f'::char
ORDER BY conkey, conname")as$J){$J['deferrable']=($J['deferrable']?'':'NOT ').'DEFERRABLE'.($J['deferred']?' INITIALLY DEFERRED':'');if(preg_match('~FOREIGN KEY\s*\((.+)\)\s*REFERENCES (.+)\((.+)\)(.*)$~iA',$J['definition'],$A)){$J['source']=array_map('Adminer\idf_unescape',array_map('trim',explode(',',$A[1])));if(preg_match('~^(("([^"]|"")+"|[^"]+)\.)?"?("([^"]|"")+"|[^"]+)$~',$A[2],$Tg)){$J['ns']=idf_unescape($Tg[2]);$J['table']=idf_unescape($Tg[4]);}$J['target']=array_map('Adminer\idf_unescape',array_map('trim',explode(',',$A[3])));$J['on_delete']=(preg_match("~ON DELETE (".driver()->onActions.")~",$A[4],$Tg)?$Tg[1]:'NO ACTION');$J['on_update']=(preg_match("~ON UPDATE (".driver()->onActions.")~",$A[4],$Tg)?$Tg[1]:'NO ACTION');$I[$J['conname']]=$J;}}return$I;}function
view($B){return["select"=>trim(get_val("SELECT pg_get_viewdef(".driver()->tableOid($B).")"))];}function
collations(){return(isset($_GET["database"])&&DB==""&&connection()->flavor!='cockroach'&&min_version(13)?get_vals("SELECT DISTINCT collcollate FROM pg_collation WHERE collprovider = 'c' ORDER BY 1"):[]);}function
information_schema($k,$L=""){$vm=["information_schema","pg_catalog","pg_toast"];if(connection()->flavor=='cockroach'){$vm[]="crdb_internal";$vm[]="pg_extension";}return
in_array($L!=""?$L:get_schema(),$vm);}function
error(){$I=h(connection()->error);if(preg_match('~^(.*\n)?([^\n]*)\n( *)\^(\n.*)?$~s',$I,$A))$I=$A[1].preg_replace('~((?:[^&]|&[^;]*;){'.strlen($A[3]).'})(.*)~','\1<b>\2</b>',$A[2]).$A[4];return
nl_br($I);}function
create_database($k,$Fb){return
queries("CREATE DATABASE ".idf_escape($k).($Fb?" TEMPLATE template0 LOCALE ".q($Fb):""));}function
drop_databases(array$j){connection()->close();return
apply_queries("DROP DATABASE",$j,'Adminer\idf_escape');}function
rename_database($B,$Fb){connection()->close();return!!queries("ALTER DATABASE ".idf_escape(DB)." RENAME TO ".idf_escape($B));}function
auto_increment(){return"";}function
alter_table($R,$B,array$o,array$se,$f,$vd,$Fb,$La,$rj){$b=[];$ik=[];if($R!=""&&$R!=$B)$ik[]="ALTER TABLE ".table($R)." RENAME TO ".table($B);$rl="";$Si=($R!=""?fields($R):[]);foreach($o
as$n){$d=idf_escape($n[0]);$X=$n[1];if(!$X)$b[]="DROP $d";else{$Ri=$Si[$n[0]];$Pi=($Ri?process_field($Ri,$Ri):[]);$fo=$X[5];unset($X[5]);if($n[0]==""){if(isset($X[6]))$X[1]=($X[1]==" bigint"?" big":($X[1]==" smallint"?" small":" "))."serial";$b[]=($R!=""?"ADD ":"  ").implode($X);if(isset($X[6]))$b[]=($R!=""?"ADD":" ")." PRIMARY KEY ($X[0])";}else{if($d!=$X[0])$ik[]="ALTER TABLE ".table($B)." RENAME $d TO $X[0]";if($X[1]!=$Pi[1])$b[]="ALTER $d TYPE$X[1]";if($X[3]!=$Pi[3]||isset($X[6])!=isset($Pi[6])){$sl=$R."_".idf_unescape($X[0])."_seq";$b[]="ALTER $d ".($X[3]?"SET".preg_replace('~GENERATED ALWAYS(.*) (STORED|VIRTUAL)~','EXPRESSION\1',$X[3]):(isset($X[6])?"SET DEFAULT nextval(".q($sl).")":"DROP DEFAULT"));if(isset($X[6]))$rl="CREATE SEQUENCE IF NOT EXISTS ".idf_escape($sl)." OWNED BY ".idf_escape($R).".$X[0]";}if($X[2]!=$Pi[2])$b[]="ALTER $d ".($X[2]==" NULL"?"DROP NOT":"SET").$X[2];}if($fo!=$Pi[5])$ik[]="COMMENT ON COLUMN ".table($B).".$X[0] IS ".($fo!=""?substr($fo,9):"''");}}if($R==""){$b=array_merge($b,$se);$P="";if($rj){$Bb=(connection()->flavor=='cockroach');$P=" PARTITION BY $rj[partition_by]($rj[partition])";if($rj["partition_by"]=='HASH'){$sj=+$rj["partitions"];for($s=0;$s<$sj;$s++)$ik[]="CREATE TABLE ".idf_escape($B."_$s")." PARTITION OF ".idf_escape($B)." FOR VALUES WITH (MODULUS $sj, REMAINDER $s)";}else{$Sj="MINVALUE";foreach($rj["partition_names"]as$s=>$X){$Y=$rj["partition_values"][$s];$mj=" VALUES ".($rj["partition_by"]=='LIST'?"IN ($Y)":"FROM ($Sj) TO ($Y)");if($Bb)$P
.=($s?",":" (")."\n  PARTITION ".(preg_match('~^DEFAULT$~i',$X)?$X:idf_escape($X))."$mj";else$ik[]="CREATE TABLE ".idf_escape($B."_$X")." PARTITION OF ".idf_escape($B)." FOR$mj";$Sj=$Y;}$P
.=($Bb?"\n)":"");}}array_unshift($ik,"CREATE TABLE ".table($B)." (\n".implode(",\n",$b)."\n)$P");}else{if($b)array_unshift($ik,"ALTER TABLE ".table($R)."\n".implode(",\n",$b));if($se)$ik[]="ALTER TABLE ".table($B)."\n".implode(",\n",$se);}if($rl)array_unshift($ik,$rl);if($f!==null)$ik[]="COMMENT ON TABLE ".table($B)." IS ".q($f);foreach($ik
as$G){if(!queries($G))return
false;}if($La!=""){foreach(fields($B)as$ae=>$n){if($n["auto_increment"])return!!queries("SELECT setval(pg_get_serial_sequence(".q(table($B)).", ".q($ae)."), $La)");}}return
true;}function
alter_indexes($R,$b){$i=[];$cd=[];$ik=[];$Pb=(support("concurrent_indexes")&&$_POST["concurrently"]?" CONCURRENTLY":"");foreach($b
as$X){if($X[0]=="UNIQUE"&&$X[2]=="DROP"&&!get_val("SELECT 1 FROM pg_constraint JOIN pg_class ON conindid = pg_class.oid WHERE conrelid = ".driver()->tableOid($R)." AND relname = ".q($X[1])))$X[0]="INDEX";if($X[0]!="INDEX")$i[]=($X[2]=="DROP"?"\nDROP CONSTRAINT ".idf_escape($X[1]):"\nADD".($X[1]!=""?" CONSTRAINT ".idf_escape($X[1]):"")." $X[0] ".($X[0]=="PRIMARY"?"KEY ":"")."(".implode(", ",$X[2]).")");elseif($X[2]=="DROP")$cd[]=idf_escape($X[1]);else$ik[]="CREATE INDEX$Pb ".idf_escape($X[1]!=""?$X[1]:uniqid($R."_"))." ON ".table($R).($X[3]?" USING $X[3]":"")." (".implode(", ",$X[2]).")".($X[4]?" WHERE $X[4]":"");}if($i)array_unshift($ik,"ALTER TABLE ".table($R).implode(",",$i));if($cd){$ik=array_merge(($Pb?array_map(function($u){return"DROP INDEX CONCURRENTLY $u";},$cd):["DROP INDEX ".implode(", ",$cd)]),$ik);}foreach($ik
as$G){if(!queries($G))return
false;}return
true;}function
truncate_tables(array$T){return!!queries("TRUNCATE ".implode(", ",array_map('Adminer\table',$T)));}function
drop_kinds(array$T){$I=["MATERIALIZED VIEW"=>[],"VIEW"=>[],"TABLE"=>[]];foreach($T
as$B=>$S)$I[strtoupper($S["Engine"])][]=table($B);return
array_filter($I);}function
drop_views(array$no){return
drop_tables($no);}function
drop_tables(array$T){$jm=[];foreach($T
as$R)$jm[$R]=table_status1($R);foreach(drop_kinds($jm)as$qg=>$Nh){if(!queries("DROP $qg ".implode(", ",$Nh)))return
false;}return
true;}function
move_tables(array$T,array$no,$Om){foreach(array_merge($T,$no)as$R){$P=table_status1($R);if(!queries("ALTER ".strtoupper($P["Engine"])." ".table($R)." SET SCHEMA ".idf_escape($Om)))return
false;}return
true;}function
trigger($B,$R){if($B=="")return["Statement"=>"EXECUTE PROCEDURE ()"];$e=[];$Z="WHERE trigger_schema = current_schema() AND event_object_table = ".q($R)." AND trigger_name = ".q($B);foreach(get_rows("SELECT * FROM information_schema.triggered_update_columns $Z")as$J)$e[]=$J["event_object_column"];$I=[];foreach(get_rows('SELECT trigger_name AS "Trigger", action_timing AS "Timing", event_manipulation AS "Event", \'FOR EACH \' || action_orientation AS "Type", action_statement AS "Statement"
FROM information_schema.triggers'."
$Z
ORDER BY event_manipulation DESC")as$J){if($e&&$J["Event"]=="UPDATE")$J["Event"].=" OF";$J["Of"]=implode(", ",$e);if($I)$J["Event"].=" OR $I[Event]";$I=$J;}return$I;}function
triggers($R){$I=[];$Ge=[];foreach(get_rows('SELECT t.tgname, r.routine_schema AS ns, r.specific_name AS function, r.routine_name AS name
FROM pg_catalog.pg_trigger t
JOIN information_schema.routines r ON substring(r.specific_name, \'[0-9]+$\')::oid = t.tgfoid
WHERE NOT t.tgisinternal AND t.tgrelid = (
	SELECT c.oid FROM pg_catalog.pg_class c JOIN pg_catalog.pg_namespace n ON n.oid = c.relnamespace WHERE n.nspname = current_schema() AND c.relname = '.q($R).'
)')as$J)$Ge[array_shift($J)]=$J;foreach(get_rows("SELECT * FROM information_schema.triggers WHERE trigger_schema = current_schema() AND event_object_table = ".q($R))as$J){$rn=trigger($J["trigger_name"],$R);$I[$rn["Trigger"]]=[$rn["Timing"],$rn["Event"]];if($Ge[$rn["Trigger"]])$I[$rn["Trigger"]][]=$Ge[$rn["Trigger"]];}return$I;}function
trigger_options(){return["Timing"=>["BEFORE","AFTER"],"Event"=>["INSERT","UPDATE","UPDATE OF","DELETE","INSERT OR UPDATE","INSERT OR UPDATE OF","DELETE OR INSERT","DELETE OR UPDATE","DELETE OR UPDATE OF","DELETE OR INSERT OR UPDATE","DELETE OR INSERT OR UPDATE OF",],"Type"=>["FOR EACH ROW","FOR EACH STATEMENT"],];}function
routine($B,$U){$Hi=routine_options($U);$nl=array_intersect_key(["VOLATILITY"=>"CASE p.provolatile WHEN 'i' THEN 'IMMUTABLE' WHEN 's' THEN 'STABLE' ELSE 'VOLATILE' END","NULL_INPUT"=>"CASE WHEN p.proisstrict THEN 'RETURNS NULL ON NULL INPUT' ELSE 'CALLED ON NULL INPUT' END","SECURITY"=>"CASE WHEN p.prosecdef THEN 'SECURITY DEFINER' ELSE 'SECURITY INVOKER' END","PARALLEL"=>"CASE p.proparallel WHEN 's' THEN 'PARALLEL SAFE' WHEN 'r' THEN 'PARALLEL RESTRICTED' ELSE 'PARALLEL UNSAFE' END",],$Hi);foreach($nl
as$x=>$M)$nl[$x]="$M AS \"$x\"";$K=get_rows('SELECT r.routine_definition AS definition, LOWER(r.external_language) AS language, '.($nl?implode(', ',$nl).', ':'').'r.*
FROM information_schema.routines r
LEFT JOIN pg_catalog.pg_proc p ON p.oid::text = substring(r.specific_name, \'[0-9]+$\')
WHERE r.routine_schema = current_schema() AND r.specific_name = '.q($B));if(!$K)return[];$I=$K[0];$I["definition"]=trim("$I[definition]","\r\n");$I["options"]=array_intersect_key($I,$Hi);$I["returns"]=["type"=>preg_replace('~^_(.*)~','\1[]',"$I[type_udt_name]")];$I["fields"]=get_rows("SELECT COALESCE(parameter_name, ordinal_position::text) AS field,
	CASE data_type WHEN 'USER-DEFINED' THEN udt_name WHEN 'ARRAY' THEN substr(udt_name, 2) || '[]' ELSE data_type END AS type,
	character_maximum_length AS length, parameter_mode AS inout
FROM information_schema.parameters
WHERE specific_schema = current_schema() AND specific_name = ".q($B)."
ORDER BY ordinal_position");return$I;}function
routines(){return
get_rows('SELECT specific_name AS "SPECIFIC_NAME", routine_type AS "ROUTINE_TYPE", routine_name AS "ROUTINE_NAME", type_udt_name AS "DTD_IDENTIFIER"
FROM information_schema.routines
WHERE routine_schema = current_schema()'.(connection()->flavor=='cockroach'?'':"
AND substring(specific_name, '[0-9]+\$')::oid NOT IN (SELECT objid FROM pg_catalog.pg_depend WHERE classid = 'pg_proc'::regclass AND deptype = 'e')").'
ORDER BY SPECIFIC_NAME');}function
routine_languages(){$I=[];foreach(get_vals("SELECT LOWER(lanname) FROM pg_catalog.pg_language")as$wg)$I[$wg]=(preg_match('~sql$~',$wg)?"pgsql":"txt");return$I;}function
routine_options($Ok){$Bb=(connection()->flavor=='cockroach');$il=($Bb?[]:["SECURITY"=>["SECURITY INVOKER","SECURITY DEFINER"]]);if($Ok=="PROCEDURE")return$il;return["VOLATILITY"=>["VOLATILE","STABLE","IMMUTABLE"],"NULL_INPUT"=>["CALLED ON NULL INPUT","RETURNS NULL ON NULL INPUT"],]+$il+($Bb?[]:["PARALLEL"=>["PARALLEL UNSAFE","PARALLEL RESTRICTED","PARALLEL SAFE"],]);}function
routine_id($B,array$J){$I=[];foreach($J["fields"]as$n){$y=$n["length"];$I[]=$n["type"].($y?"($y)":"");}return
idf_escape($B)."(".implode(", ",$I).")";}function
last_id($H){$J=(is_object($H)?$H->fetch_row():[]);return($J?$J[0]:0);}function
explain(Db$g,$G){return$g->query("EXPLAIN $G");}function
found_rows(array$S,array$Z){if(preg_match("~ rows=([0-9]+)~",get_val("EXPLAIN SELECT * FROM ".idf_escape($S["Name"]).($Z?" WHERE ".implode(" AND ",$Z):"")),$_k))return$_k[1];}function
types($Sd=false){$Bb=connection()->flavor=='cockroach';$rg=($Bb?"'e'":"'b','c','d','e'".(min_version(9.2)?",'r'":""));return
get_key_vals("SELECT t.oid, t.typname
FROM pg_type t
WHERE t.typnamespace = ".driver()->nsOid."
AND t.typtype IN ($rg)".($Bb?"
AND t.typelem = 0":"
AND (t.typrelid = 0 OR (SELECT c.relkind FROM pg_class c WHERE c.oid = t.typrelid) = 'c')"."
AND NOT EXISTS (SELECT 1 FROM pg_type e WHERE e.typarray = t.oid)".($Sd?'':"
AND t.oid NOT IN (SELECT objid FROM pg_catalog.pg_depend WHERE classid = 'pg_type'::regclass AND deptype = 'e')"))."
ORDER BY t.typname");}function
type_values($t){$zd=get_vals("SELECT enumlabel FROM pg_enum WHERE enumtypid = $t ORDER BY enumsortorder");return($zd?"'".implode("', '",array_map('addslashes',$zd))."'":"");}function
collation_name($pi){return(min_version(9.1)?"(SELECT collname FROM pg_collation WHERE oid = $pi AND collname != 'default')":"NULL");}function
type_definition($t){$U=first(get_rows("SELECT typtype, typisdefined::int AS defined, typrelid FROM pg_type WHERE oid = $t"));$I=["kind"=>($U?$U["typtype"]:""),"definition"=>""];if(!$U||!$U["defined"])return$I;switch($I["kind"]){case'e':$io=get_vals("SELECT enumlabel FROM pg_enum WHERE enumtypid = $t ORDER BY enumsortorder");$I["definition"]="AS ENUM (".implode(", ",array_map('Adminer\q',$io)).")";break;case'c':$e=[];foreach(get_rows("SELECT attname, format_type(atttypid, atttypmod) AS full_type, ".collation_name("attcollation")." AS collation
FROM pg_attribute
WHERE attrelid = $U[typrelid] AND attnum > 0 AND NOT attisdropped
ORDER BY attnum")as$J)$e[]=idf_escape($J["attname"])." $J[full_type]".($J["collation"]?" COLLATE ".idf_escape($J["collation"]):"");$I["definition"]="AS (\n\t".implode(",\n\t",$e)."\n)";break;case'd':$Zc=first(get_rows("SELECT format_type(typbasetype, typtypmod) AS base, typnotnull::int AS notnull, typdefault, ".collation_name("typcollation")." AS collation
FROM pg_type WHERE oid = $t"));$I["definition"]="AS $Zc[base]".($Zc["collation"]?" COLLATE ".idf_escape($Zc["collation"]):"").($Zc["typdefault"]!=""?" DEFAULT $Zc[typdefault]":"").($Zc["notnull"]?" NOT NULL":"");foreach(get_rows("SELECT conname, pg_get_constraintdef(oid) AS definition FROM pg_constraint WHERE contypid = $t AND contype != 'n' ORDER BY conname")as$J)$I["definition"].=" CONSTRAINT ".idf_escape($J["conname"])." $J[definition]";break;case'r':$pk=first(get_rows("SELECT format_type(rngsubtype, NULL) AS subtype,
(SELECT opcname FROM pg_opclass WHERE oid = rngsubopc) AS subtype_opclass,
".collation_name("rngcollation")." AS collation,
NULLIF(rngcanonical, 0)::regproc::text AS canonical,
NULLIF(rngsubdiff, 0)::regproc::text AS subtype_diff".(min_version(14)?",
(SELECT typname FROM pg_type WHERE oid = rngmultitypid) AS multirange_type_name":"")."
FROM pg_range WHERE rngtypid = $t"));$Hi=[];foreach(["subtype"=>0,"subtype_opclass"=>1,"collation"=>1,"canonical"=>0,"subtype_diff"=>0,"multirange_type_name"=>1]as$x=>$Ed){if($pk[$x]!="")$Hi[]=strtoupper($x)." = ".($Ed?idf_escape($pk[$x]):$pk[$x]);}$I["definition"]="AS RANGE (".implode(", ",$Hi).")";}return$I;}function
schemas(){return
get_vals("SELECT nspname FROM pg_namespace ORDER BY nspname");}function
get_schema(){if($_GET["ns"]!="")return$_GET["ns"];return(string)get_val("SELECT current_schema()");}function
set_schema($L,$h=null){$_GET["ns"]=$L;$I=get_val("SELECT set_config('search_path', ".q(idf_escape($L)).", false) FROM pg_namespace WHERE nspname = ".q($L),0,$h);driver()->setUserTypes(types(true));return!!$I;}function
schema_comment($L){return(string)get_val("SELECT obj_description(oid, 'pg_namespace') FROM pg_namespace WHERE nspname = ".q($L));}function
set_schema_comment($L,$f){return!!queries("COMMENT ON SCHEMA ".idf_escape($L)." IS ".q($f));}function
drop_sql(array$T){$I="";foreach(drop_kinds($T)as$qg=>$Nh)$I
.="DROP $qg IF EXISTS ".implode(", ",$Nh).";\n";return($I?"$I\n":"");}function
foreign_keys_sql($R){$I="";$ne=foreign_keys($R);ksort($ne);foreach($ne
as$me=>$le){$I
.="ALTER TABLE ONLY ".table($R)." ADD CONSTRAINT ".idf_escape($me)." ".preg_replace_callback('~( REFERENCES )([^(.]+)\(~',function(array$A){return$A[1].table(idf_unescape($A[2]))."(";},$le["definition"]).";\n";}return($I?"$I\n":$I);}function
indexes_sql($R,$Vj=""){$I="";$G="SELECT indexdef, quote_ident(schemaname) || '.' || quote_ident(tablename) AS qualified, quote_ident(current_database()) AS db
FROM pg_catalog.pg_indexes
WHERE schemaname = current_schema() AND tablename = ".q($R).($Vj!=""?" AND indexname != ".q($Vj):"");foreach(get_rows($G,null,"-- ")as$J)$I
.="\n\n".str_replace([" $J[db].$J[qualified] USING "," $J[qualified] USING "]," ".table($R)." USING ",$J["indexdef"]).";";return$I;}function
create_sql($R,$La,$nm){$Jk=[];$ul=[];$vl=[];$tl=[];$P=table_status1($R);if(is_view($P)){$mo=view($R);$i="CREATE ".strtoupper($P["Engine"])." ".table($R)." AS ".rtrim($mo["select"],";").";";return
rtrim($i.indexes_sql($R),';');}$o=fields($R);if(count($P)<2||empty($o))return"";$I="CREATE TABLE ".table($P['Name'])." (\n    ";$Bm=q(table($P['Name']));foreach($o
as$n){$wl="";if($n['default']=="nextval('$P[Name]_$n[field]_seq')"){$wl=table("$P[Name]_$n[field]_seq");$n['default']=null;$n['full_type']=preg_replace('~int(eger)?~','serial',$n['full_type']);}$kj=idf_escape($n['field']).' '.full_type_sql($n).preg_replace_callback('~(nextval\(\')([^.\']+)\'~',function(array$A){return$A[1].str_replace("'","''",table(idf_unescape($A[2])))."'";},default_value($n)).($n['null']?"":" NOT NULL");$Jk[]=$kj;if(preg_match('~nextval\(\'([^\']+)\'\)~',$n['default'],$Ug)){$sl=$Ug[1];$Ul=first(get_rows((min_version(10)?"SELECT *, cache_size AS cache_value FROM pg_sequences WHERE schemaname = current_schema() AND sequencename = ".q(idf_unescape($sl)):"SELECT * FROM $sl"),null,"-- "));$rl=table(idf_unescape($sl));$ul[]=($nm=="DROP+CREATE"?"DROP SEQUENCE IF EXISTS $rl;\n":"")."CREATE SEQUENCE $rl INCREMENT $Ul[increment_by] MINVALUE $Ul[min_value] MAXVALUE $Ul[max_value]"." CACHE $Ul[cache_value];";if(get_val("SELECT pg_get_serial_sequence($Bm, ".q($n['field']).")"))$vl[]="\n\nALTER SEQUENCE $rl OWNED BY ".table($P['Name']).".".idf_escape($n['field']).";";if($La)$tl[]=$rl;}elseif($La&&$n['auto_increment']){$rl=($wl?"":get_val("SELECT pg_get_serial_sequence($Bm, ".q($n['field']).")::regclass"));$tl[]=($rl?table(idf_unescape($rl)):$wl);}}if(!empty($ul))$I=implode("\n\n",$ul)."\n\n$I";$Vj="";foreach(indexes($R)as$Ff=>$v){if($v['type']=='PRIMARY'){$Vj=$Ff;$Jk[]="CONSTRAINT ".idf_escape($Ff)." PRIMARY KEY (".implode(', ',array_map('Adminer\idf_escape',$v['columns'])).")";}}foreach(driver()->checkConstraints($R)as$Tb=>$Vb)$Jk[]="CONSTRAINT ".idf_escape($Tb)." CHECK ($Vb)";$I
.=implode(",\n    ",$Jk)."\n)";$mj=driver()->partitionsInfo($P['Name']);if($mj)$I
.="\nPARTITION BY $mj[partition_by]($mj[partition])";$I
.=(min_version(12)?"":"\nWITH (oids = ".($P['Oid']?'true':'false').")").";";$I
.=implode($vl);if($P['Comment'])$I
.="\n\nCOMMENT ON TABLE ".table($P['Name'])." IS ".q($P['Comment']).";";foreach($o
as$ae=>$n){if($n['comment'])$I
.="\n\nCOMMENT ON COLUMN ".table($P['Name']).".".idf_escape($ae)." IS ".q($n['comment']).";";}$I
.=indexes_sql($R,$Vj);foreach(array_filter($tl)as$rl){$Ul=first(get_rows("SELECT last_value, is_called::int FROM $rl",null,"-- "));if($Ul['is_called'])$I
.="\n\nDO \$\$ BEGIN PERFORM setval(".q($rl).", $Ul[last_value]); END \$\$;";}return
rtrim($I,';');}function
truncate_sql($R){return"TRUNCATE ".table($R);}function
truncate_all_sql(array$T){return($T?"TRUNCATE ".implode(", ",array_map('Adminer\table',$T)).";\n\n":"");}function
trigger_sql($R){$P=table_status1($R);$I="";foreach(triggers($R)as$qn=>$pn){$rn=trigger($qn,$P['Name']);$I
.="\nCREATE TRIGGER ".idf_escape($rn['Trigger'])." $rn[Timing] $rn[Event] ON ".table($P['Name'])." $rn[Type] $rn[Statement];;\n";}return$I;}function
use_sql($uc,$nm=""){$B=idf_escape($uc);$I="";if(preg_match('~CREATE~',$nm)){if($nm=="DROP+CREATE")$I="DROP DATABASE IF EXISTS $B;\n";$I
.="CREATE DATABASE $B;\n";}return"$I\\connect $B";}function
use_schema_sql($L,$nm){$B=idf_escape($L);$I="";if(preg_match('~CREATE~',$nm)){if($nm=="DROP+CREATE")$I="DROP SCHEMA IF EXISTS $B CASCADE;\n";$I
.="CREATE SCHEMA IF NOT EXISTS $B;\n";$f=schema_comment($L);if($f!="")$I
.="COMMENT ON SCHEMA $B IS ".q($f).";\n";}return$I."SET search_path TO $B";}function
show_variables(){return
get_rows("SHOW ALL");}function
process_list(){return
get_rows("SELECT * FROM pg_stat_activity ORDER BY ".(min_version(9.2)?"pid":"procpid"));}function
convert_field(array$n){if(preg_match('~^(geometry|geography)$~',$n["type"])&&strpos($n["full_type"],"[")===false)return"ST_AsEWKT(".idf_escape($n["field"]).")";}function
unconvert_field(array$n,$I){return($n["composite"]?"$I::".full_type_sql($n):$I);}function
support($Xd){return
preg_match('~^(check|columns|comment|database|drop_col|dump|descidx|fast_status|indexes|kill|partial_indexes|routine|scheme|sequence|sql|table|trigger|type|variables|view'.(min_version(9.3)?'|materializedview':'').(min_version(11)?'|procedure':'').(connection()->flavor=='cockroach'?'':'|deferrable').(connection()->flavor=='cockroach'?'':'|transaction_ddl').(connection()->flavor=='cockroach'||!min_version(9.1)?'':'|extension').(connection()->flavor=='cockroach'||!min_version(9.2)?'':'|concurrent_indexes').(connection()->flavor=='cockroach'?'':'|processlist').')$~',$Xd);}function
kill_process($t){return
queries("SELECT pg_terminate_backend(".number($t).")");}function
max_connections(){return
get_val("SHOW max_connections");}}add_driver("sqlite","SQLite");if(isset($_GET["sqlite"])){define('Adminer\DRIVER',"sqlite");if(class_exists("SQLite3")&&$_GET["ext"]!="pdo"){abstract
class
SqliteDb
extends
SqlDb{var$extension="SQLite3";private$link;function
attach(array$N,$V,$E){$this->link=new
\SQLite3($N["path"]);if(method_exists($this->link,'setAuthorizer'))$this->link->setAuthorizer([$this,'authorize']);$lo=\SQLite3::version();$this->server_info=$lo["versionString"];return'';}function
query($G,$Cn=false){$H=adminer()->executeQuery($G,function()use($G){return@$this->link->query($G);});$this->error="";if(!$H){$this->errno=$this->link->lastErrorCode();$this->error=$this->link->lastErrorMsg();return
false;}elseif($H->numColumns())return
new
Result($H);$this->affected_rows=$this->link->changes();return
true;}function
quote($Q){return(is_utf8($Q)?"'".$this->link->escapeString($Q)."'":"x'".bin2hex($Q)."'");}}class
Result{var$num_rows;private$result,$offset=0;function
__construct($H){$this->result=$H;}function
fetch_assoc(){return$this->result->fetchArray(SQLITE3_ASSOC);}function
fetch_row(){return$this->result->fetchArray(SQLITE3_NUM);}function
fetch_field(){$Bn=[1=>"integer","real","text","blob","null"];$d=$this->offset++;return(object)["name"=>$this->result->columnName($d),"native_type"=>$Bn[$this->result->columnType($d)],];}}}elseif(extension_loaded("pdo_sqlite")){abstract
class
SqliteDb
extends
PdoDb{var$extension="PDO_SQLite";function
attach(array$N,$V,$E){$I=$this->dsn(DRIVER.":".$N["path"],"","",[],(class_exists('Pdo\Sqlite')?'Pdo\Sqlite':'PDO'));if(!$I&&method_exists($this->pdo,'setAuthorizer'))$this->pdo->setAuthorizer([$this,'authorize']);return$I;}function
quote($Q){return(is_utf8($Q)?parent::quote($Q):"x'".bin2hex($Q)."'");}function
store_result($H=null){$sa=$this->affected_rows;$I=parent::store_result($H);if(!$H&&$I===true)$this->affected_rows=$sa;return$I;}}}if(class_exists('Adminer\SqliteDb')){class
Db
extends
SqliteDb{private$attaching=false;private$total_changes=0;function
attach(array$N,$V,$E){parent::attach($N,$V,$E);$this->total_changes=0;$this->query("PRAGMA foreign_keys = 1");$this->query("PRAGMA busy_timeout = 500");return'';}function
query($G,$Cn=false){$I=parent::query($G,$Cn);if($this->affected_rows){$H=parent::query("SELECT total_changes()");$J=($H?$H->fetch_row():false);if($J){if($J[0]==$this->total_changes)$this->affected_rows=0;$this->total_changes=$J[0];}}return$I;}function
select_db($p){if(preg_match('~^file:~',$p)&&!check_sqlite_name($p))return
false;$G="ATTACH ".$this->quote(preg_match("~(^[/\\\\]|:)~",$p)?$p:dirname($_SERVER["SCRIPT_FILENAME"])."/$p")." AS a";$this->attaching=true;$Ia=is_readable(file_from_uri($p))&&$this->query($G);$this->attaching=false;if($Ia)return!self::attach(server_parts(["path"=>$p]),'','');return
false;}function
authorize($ia,$Ca){return($ia!=24||$Ca===''||$this->attaching?0:1);}}}class
Driver
extends
SqlDriver{static$extensions=["SQLite3","PDO_SQLite"];static$jush="sqlite";static$passwords=false;static$serverFile=true;protected$types=[["integer"=>0,"real"=>0,"numeric"=>0,"text"=>0,"blob"=>0]];var$insertFunctions=["date"=>"current_date","time"=>"current_timestamp",];var$editFunctions=["^(?!.*(char|clob|text|blob))."=>"+/-","char|clob|text"=>"||",];var$fulltextOperator="MATCH";var$functions=["hex","length","lower","round","unixepoch","upper"];var$grouping=["avg","count","count distinct","group_concat","max","min","sum"];function
operators($xm){$I=["=","<",">","<=",">=","!=","&","LIKE","LIKE %%","IN","BETWEEN","IS NULL","NOT LIKE","NOT IN","IS NOT NULL"];if(preg_match('~^fts\d+$~i',(string)idx($xm,"Engine")))$I[]="MATCH";$I[]="SQL";return$I;}static
function
connect($N,$V,$E){return
parent::connect(":memory:","","");}function
__construct(Db$g){parent::__construct($g);if(min_version(3.31,0,$g))$this->generated=["STORED","VIRTUAL"];if(min_version(3.37,0,$g))$this->types[0]["any"]=0;}function
structuredTypes(){return
array_keys($this->types[0]);}function
quoteBinary($Xk){return"x".q(bin2hex($Xk));}function
typeName(\stdClass$n){$I=strtolower(idx((array)$n,'sqlite:decl_type',parent::typeName($n)));return
idx(["string"=>"text","double"=>"real"],$I,$I);}function
engines(){$I=["table"];if(min_version("3.8.2")){if(min_version(3.37)){$I[]="STRICT";$I[]="STRICT, WITHOUT ROWID";}$I[]="WITHOUT ROWID";}return$I;}private
function
isVirtual(array$S){$vd=$S["Engine"];return$vd!=""&&!in_array($vd,array_merge(["view"],$this->engines()));}function
supportsIndex(array$S){return!is_view($S)&&!$this->isVirtual($S);}function
supportsAlterIndex(array$S){return$this->supportsIndex($S);}function
supportsAlterTable(array$xm){return!$this->isVirtual($xm);}function
shadowTables($R){$I=[];if(min_version(3.37)){foreach(get_vals("SELECT name FROM pragma_table_list WHERE schema = 'main' AND type = 'shadow' ORDER BY name")as$B){if(preg_match('(^'.preg_quote($R).'_[^_]*$)',$B))$I[]=["table"=>$B,"ns"=>""];}}return$I;}function
fulltextSql($B,array$v,$G,$ab){return
idf_escape($B)." MATCH ".q($G);}function
insertUpdate($R,array$K,array$Vj){$io=[];foreach($K
as$O)$io[]="(".implode(", ",$O).")";return
queries("REPLACE INTO ".table($R)." (".implode(", ",array_keys(reset($K))).") VALUES\n".implode(",\n",$io));}function
tableHelp($B,$fg=false){if(preg_match('~^sqlite_(seq|stat.)~',$B,$A))return"fileformat2.html#$A[1]tab";if(preg_match('~^sqlite(_temp)?_(master|schema)$~',$B))return"schematab.html";}function
checkConstraints($R){$i=get_val("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ".q($R),0,$this->conn);preg_match_all('~\s(?:CONSTRAINT\s+("(?:[^"]|"")*"|`(?:[^`]|``)*`|\[[^]]*]|\S+)\s+)?CHECK\s*(\(((?>[^()]+)|(?2))*\))~',$i,$Ug,PREG_SET_ORDER);$I=[];foreach($Ug
as$A){$Ab=trim(substr($A[2],1,-1));$I[$A[1]!=""?idf_unescape($A[1]):$Ab]=$Ab;}return$I;}function
allFields(){$I=[];if(min_version(3.16)){$K=get_rows('SELECT m.name AS tab, p.name AS field, p.type, p."notnull", p.pk AS '.idf_escape("primary")."
FROM sqlite_master m, pragma_table_".(min_version(3.31)?"x":"")."info(m.name) p
WHERE m.type IN ('table', 'view')".(min_version(3.31)?"
AND p.hidden != 1":"").(min_version(3.37)?"
AND m.name NOT IN (SELECT name FROM pragma_table_list WHERE type = 'shadow')":"")."
ORDER BY (m.name LIKE 'sqlite_%'), m.name, p.cid",$this->conn);foreach($K
as$J){$J["type"]=type_affinity($J["type"]);$J["null"]=!$J["notnull"];$I[$J["tab"]][]=$J;}}else$I=parent::allFields();return$I;}}function
idf_escape($u){return'"'.str_replace('"','""',$u).'"';}function
table($u){return
idf_escape($u);}function
get_databases($qe){return[];}function
limit($G,$Z,$z,$C=0,$ql=" "){return" $G$Z".($z?$ql."LIMIT $z".($C?" OFFSET $C":""):"");}function
limit1($R,$G,$Z,$ql="\n"){return(preg_match('~^INTO~',$G)||get_val("SELECT sqlite_compileoption_used('ENABLE_UPDATE_DELETE_LIMIT')")?limit($G,$Z,1,0,$ql):" $G WHERE rowid = (SELECT rowid FROM ".table($R).$Z.$ql."LIMIT 1)");}function
db_collation($k,array$Gb){return
get_val("PRAGMA encoding");}function
logged_user(){return
get_current_user();}function
virtual_module($Vl){return(preg_match('~^CREATE\s+VIRTUAL\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?(?:"[^"]*+"|`[^`]*+`|\[[^\]]*+\]|[^\s(]+)\s+USING\s+([a-z0-9_]+)~i',$Vl,$A)?$A[1]:"");}function
parse_comments($Vl){$I=[];if(virtual_module($Vl)!="")return$I;preg_match_all('~\'(?:[^\']|\'\')*\'|"(?:[^"]|"")*"|`(?:[^`]|``)*`|\[[^]]*]|--[^\n]*|/\*.*?\*/|[(),\n]|[^-/\'"`[(),\n]+|[-/]~s',$Vl,$Ug);$Hc=0;$Nh=[];$xg="";$ai=true;foreach($Ug[0]as$gn){if(preg_match('~^--~',$gn)){if($Hc==1&&!$ai){$B=($xg=="("?"":($xg==","?idx($Nh,count($Nh)-2):end($Nh)));if(is_string($B))$I[$B]=trim(substr($gn,2));}}elseif($gn=="\n")$ai=true;elseif(trim($gn)!=""&&!preg_match('~^/\*~',$gn)){$ai=false;$xg=($Hc==1&&$gn==","?",":($Hc==0&&$gn=="("?"(":""));if($xg)$Nh[]=false;elseif($Hc==1&&end($Nh)===false&&$gn!="("&&$gn!=")"){$nk=preg_match('~^[\'"`[]~',$gn);$zo=($nk?idf_unescape($gn):preg_replace('~\s.*~s','',trim($gn)));$Nh[count($Nh)-1]=($nk||!preg_match('~^(CONSTRAINT|PRIMARY|UNIQUE|CHECK|FOREIGN)$~i',$zo)?$zo:null);}$Hc+=($gn=="("?1:($gn==")"?-1:0));}}return$I;}function
tables_list(){return
get_key_vals("SELECT name, type FROM sqlite_master WHERE type IN ('table', 'view')".(min_version(3.37)?" AND name NOT IN (SELECT name FROM pragma_table_list WHERE type = 'shadow')":"")."
ORDER BY (name LIKE 'sqlite_%'), name");}function
count_tables(array$j){return[];}function
db_status(){$dj=get_val("PRAGMA page_size");$_e=get_val("PRAGMA freelist_count")*$dj;return["Data_length"=>get_val("PRAGMA page_count")*$dj-$_e,"Index_length"=>0,"Data_free"=>$_e,];}function
table_status($B="",$Wd=false){$I=[];$K=[];if(!$Wd&&$B==""){connection()->query("PRAGMA optimize = 0x10002");$K=get_key_vals("SELECT tbl, MAX(CAST(stat AS integer)) FROM sqlite_stat1 GROUP BY tbl");}foreach(get_rows("SELECT name AS Name, type AS Engine, sql, 'rowid' AS Oid, '' AS Auto_increment".(min_version(3.37)?", name IN (SELECT name FROM pragma_table_list WHERE type = 'shadow') AS dependent":"")." FROM sqlite_master WHERE type IN ('table', 'view') ".($B!=""?"AND name = ".q($B):"ORDER BY (name LIKE 'sqlite_%'), name"))as$J){if($J["Engine"]=="table"){$Vl=preg_replace('~(?:\s|--[^\n]*|/\*.*?\*/)+$~s','',$J["sql"]);$pm=preg_replace('~.*\)~s','',$Vl);$J["Engine"]=virtual_module($J["sql"])?:(implode(", ",array_filter([(preg_match('~\bSTRICT\b~i',$pm)?"STRICT":0),(preg_match('~\bWITHOUT\s+ROWID\b~i',$pm)?"WITHOUT ROWID":0),]))?:"table");$J["Comment"]=idx(parse_comments($J["sql"]),"","");}unset($J["sql"]);$J["Rows"]=idx($K,$J["Name"],0);$I[$J["Name"]]=$J;}if(!$Wd){foreach(get_rows("SELECT * FROM sqlite_sequence".($B!=""?" WHERE name = ".q($B):""),null,"")as$J)$I[$J["name"]]["Auto_increment"]=$J["seq"];}return$I;}function
is_view(array$S){return$S["Engine"]=="view";}function
fk_support(array$S){return!get_val("SELECT sqlite_compileoption_used('OMIT_FOREIGN_KEY')");}function
type_affinity($U){$U=strtolower($U);return(preg_match('~int~i',$U)?"integer":(preg_match('~char|clob|text~i',$U)?"text":(preg_match('~blob~i',$U)?"blob":(preg_match('~real|floa|doub~i',$U)?"real":(preg_match('~any~i',$U)?"any":"numeric")))));}function
fields($R){$I=[];$Vl=get_val("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ".q($R));$ak=["select"=>1,"where"=>1,"order"=>1];if(!preg_match('~^sqlite(_temp)?_(master|schema)$~',$R))$ak+=["insert"=>1,"update"=>1];$Be=preg_match('~^fts\d+$~i',virtual_module($Vl));$Nb=parse_comments($Vl);foreach(get_rows("PRAGMA table_".(min_version(3.31)?"x":"")."info(".table($R).")")as$J){if($J["hidden"]==1)continue;$B=$J["name"];$U=strtolower($J["type"]);$l=$J["dflt_value"];preg_match('~^(.+?)\s*(?:\((.*)\))?$~s',$U,$uj);$nk=preg_match("~^'(?:[^']|'')*'$~",$l);$I[$B]=["field"=>$B,"type"=>($Be?"text":($uj?$uj[1]:type_affinity($U))),"full_type"=>$U,"length"=>($Be?"":idx($uj,2,"")),"default"=>($nk?idf_unescape($l):($l=="NULL"?null:$l)),"default_sql"=>($l!==null&&!$nk&&$l!="NULL"&&!is_numeric($l)),"null"=>!$J["notnull"],"privileges"=>$ak,"primary"=>$J["pk"],"comment"=>idx($Nb,$B,""),];if($J["pk"]&&preg_match('~\bAUTOINCREMENT\b~i',$Vl))$I[$B]["auto_increment"]=true;}$u='[(,]\s*(("[^"]*+")+|[a-z0-9_]+)';$Ek='(?:[^,()\']|\'[^\']*+\'|\([^)]*+\))*?';preg_match_all('~'.$u.'\s+text\b'.$Ek.'COLLATE\s+(\'[^\']+\'|[a-z0-9_]+)~i',$Vl,$Ug,PREG_SET_ORDER);foreach($Ug
as$A){$B=idf_unescape($A[1]);if($I[$B])$I[$B]["collation"]=trim($A[3],"'");}preg_match_all('~'.$u.'\s'.$Ek.'GENERATED\s+ALWAYS\s+AS\s*\((.+?)\)\s+(STORED|VIRTUAL)~i',$Vl,$Ug,PREG_SET_ORDER);foreach($Ug
as$A){$B=idf_unescape($A[1]);if($I[$B]){$I[$B]["default"]=$A[3];$I[$B]["generated"]=strtoupper($A[4]);}}return$I;}function
indexes($R,$h=null){$h=connection($h);$I=[];$Vl=get_val("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ".q($R),0,$h);if(preg_match('~^fts\d+$~i',virtual_module($Vl)))return[$R=>["type"=>"FULLTEXT","columns"=>array_keys(fields($R)),"lengths"=>[],"descs"=>[]]];if(preg_match('~\bPRIMARY\s+KEY\s*\((([^)"]+|"[^"]*"|`[^`]*`)++)~i',$Vl,$A)){$I[""]=["type"=>"PRIMARY","columns"=>[],"lengths"=>[],"descs"=>[]];preg_match_all('~((("[^"]*+")+|(?:`[^`]*+`)+)|(\S+))(\s+(ASC|DESC))?(,\s*|$)~i',$A[1],$Ug,PREG_SET_ORDER);foreach($Ug
as$A){$I[""]["columns"][]=idf_unescape($A[2]).$A[4];$I[""]["descs"][]=(preg_match('~DESC~i',$A[5])?'1':null);}}if(!$I){foreach(get_rows("PRAGMA table_info(".table($R).")",$h)as$J){if($J["pk"])$I[""]=["type"=>"PRIMARY","columns"=>[$J["name"]],"lengths"=>[],"descs"=>[null]];}}$dm=get_key_vals("SELECT name, sql FROM sqlite_master WHERE type = 'index' AND tbl_name = ".q($R),$h);foreach(get_rows("PRAGMA index_list(".table($R).")",$h)as$J){$B=$J["name"];$v=["type"=>($J["unique"]?"UNIQUE":"INDEX")];$v["lengths"]=[];$v["descs"]=[];foreach(get_rows("PRAGMA index_info(".idf_escape($B).")",$h)as$Vk){$v["columns"][]=$Vk["name"];$v["descs"][]=null;}if(preg_match('~^CREATE( UNIQUE)? INDEX '.preg_quote(idf_escape($B).' ON '.idf_escape($R),'~').' \((.*)\)$~i',$dm[$B],$_k)){preg_match_all('/("[^"]*+")+( DESC)?/',$_k[2],$Ug);foreach($Ug[2]as$x=>$X){if($X)$v["descs"][$x]='1';}}if(!$I[""]||$v["type"]!="UNIQUE"||$v["columns"]!=$I[""]["columns"]||$v["descs"]!=$I[""]["descs"]||!preg_match("~^sqlite_~",$B))$I[$B]=$v;}return$I;}function
foreign_keys($R){$I=[];foreach(get_rows("PRAGMA foreign_key_list(".table($R).")")as$J){$q=&$I[$J["id"]];if(!$q)$q=$J;$q["source"][]=$J["from"];$q["target"][]=$J["to"];}return$I;}function
view($B){return["select"=>preg_replace('~^(?:[^`"[]+|`[^`]*`|"[^"]*")* AS\s+(?=\S)~iU','',get_val("SELECT sql FROM sqlite_master WHERE type = 'view' AND name = ".q($B)))];}function
collations(){return(isset($_GET["create"])?get_vals("PRAGMA collation_list",1):[]);}function
information_schema($k,$L=""){return
false;}function
error(){return
h(connection()->error);}function
check_sqlite_name($B){$Sd="db|sdb|sqlite";$Rn=(connection()->extension=="PDO_SQLite"&&PHP_VERSION_ID>=80100?"file:[^?#%\\0]+\\.($Sd)(\\?[^\\0]*)?|":"");if(!preg_match("~^($Rn(?!file:)[^\\0]*\\.($Sd))\\z~",$B)){connection()->error=sprintf('Please use one of these file extensions: %s.',str_replace("|",", ",$Sd));return
false;}return
true;}function
file_from_uri($B){return(preg_match('~^file:(//[^/]*)?(/(?=[a-zA-Z]:))?([^?]*)~',$B,$A)?$A[3]:$B);}function
create_database($k,$Fb){if(file_exists(file_from_uri($k))){connection()->error='File exists.';return
false;}if(!check_sqlite_name($k))return
false;try{$_=new
Db();$_->attach(server_parts(["path"=>$k]),'','');}catch(\Exception$Id){connection()->error=$Id->getMessage();return
false;}$_->query('PRAGMA encoding = "UTF-8"');$_->query('CREATE TABLE adminer (i)');$_->query('DROP TABLE adminer');return
true;}function
drop_databases(array$j){connection()->attach(server_parts(["path"=>":memory:"]),'','');foreach($j
as$k){if(!check_sqlite_name($k))return
false;if(!@unlink(file_from_uri($k))){connection()->error='File exists.';return
false;}}return
true;}function
rename_database($B,$Fb){if(!check_sqlite_name($B))return
false;connection()->attach(server_parts(["path"=>":memory:"]),'','');connection()->error='File exists.';return@rename(file_from_uri(DB),file_from_uri($B));}function
auto_increment(){return" PRIMARY KEY AUTOINCREMENT";}function
alter_table($R,$B,array$o,array$se,$f,$vd,$Fb,$La,$rj){$Un=($R==""||$se||$vd||$f!==null);foreach($o
as$n){if($n[0]!=""||!$n[1]||$n[2]||$n[1][5]){$Un=true;break;}}$b=[];$Wi=[];foreach($o
as$n){if($n[1]){$b[]=($Un?$n[1]:"ADD ".implode($n[1]));if($n[0]!="")$Wi[$n[0]]=$n[1][0];}}if(!$Un){foreach($b
as$X){if(!queries("ALTER TABLE ".table($R)." $X"))return
false;}if($R!=$B&&!queries("ALTER TABLE ".table($R)." RENAME TO ".table($B)))return
false;}elseif(!recreate_table($R,$B,$b,$Wi,$se,$La,[],"","",$vd,$f))return
false;if($La){queries("BEGIN");queries("UPDATE sqlite_sequence SET seq = $La WHERE name = ".q($B));if(!connection()->affected_rows)queries("INSERT INTO sqlite_sequence (name, seq) VALUES (".q($B).", $La)");queries("COMMIT");}return
true;}function
recreate_table($R,$B,array$o,array$Wi,array$se,$La="",$w=[],$dd="",$la="",$vd="",$f=null){if($R!=""){if(!$o){foreach(fields($R)as$x=>$n){if($w)$n["auto_increment"]=0;$o[]=process_field($n,$n);$Wi[$x]=idf_escape($x);}}$Wj=false;foreach($o
as$n){if($n[6])$Wj=true;}$fd=[];foreach($w
as$x=>$X){if($X[2]=="DROP"){$fd[$X[1]]=true;unset($w[$x]);}}foreach(indexes($R)as$ng=>$v){$e=[];foreach($v["columns"]as$x=>$d){if(!$Wi[$d])continue
2;$e[]=$Wi[$d].($v["descs"][$x]?" DESC":"");}if(!$fd[$ng]){if($v["type"]!="PRIMARY"||!$Wj)$w[]=[$v["type"],$ng,$e];}}foreach($w
as$x=>$X){if($X[0]=="PRIMARY"){unset($w[$x]);$se[]="  PRIMARY KEY (".implode(", ",$X[2]).")";}elseif(preg_match('~^sqlite_autoindex_~',$X[1])){unset($w[$x]);$se[]="  UNIQUE (".implode(", ",$X[2]).")";}}foreach(foreign_keys($R)as$ng=>$q){foreach($q["source"]as$x=>$d){if(!$Wi[$d])continue
2;$q["source"][$x]=idf_unescape($Wi[$d]);}if(!isset($se[" $ng"]))$se[]=" ".format_foreign_key($q);}queries("PRAGMA foreign_keys = 0");queries("PRAGMA legacy_alter_table = 1");queries("BEGIN");}$nb=[];$Nb=[];foreach($o
as$n){if(preg_match('~GENERATED~',$n[3]))unset($Wi[array_search($n[0],$Wi)]);if(preg_match("~^ COMMENT ('.*')$~s",$n[5],$A))$Nb[count($nb)]=idf_unescape($A[1]);unset($n[5]);$nb[]="  ".implode($n);}$nb=array_merge($nb,array_values(array_filter($se)));foreach(driver()->checkConstraints($R)as$x=>$rb){if($x!=$dd)$nb[]=" ".($x!=$rb?" CONSTRAINT ".idf_escape($x):"")." CHECK ($rb)";}if($la)$nb[]="  $la";$Sm=($R!=""&&$R==$B?"adminer_$B":$B);if($R!=""&&(!$vd||$f===null)){$P=table_status1($R);$vd=$vd?:idx($P,"Engine");$f=($f===null?idx($P,"Comment",""):$f);}$Jg=function($Um){return($Um!=""?" -- ".preg_replace('~[\r\n]+~',' ',$Um):"");};$i="CREATE TABLE ".table($Sm)." (".$Jg($f)."\n";foreach($nb
as$s=>$mb)$i
.=$mb.($s<count($nb)-1?",":"").$Jg(idx($Nb,$s))."\n";if(!queries("$i)".($vd!="table"&&in_array($vd,driver()->engines())?" $vd":"")))return
false;if($R!=""){if($Wi&&!queries("INSERT INTO ".table($Sm)." (".implode(", ",$Wi).") SELECT ".implode(", ",array_map('Adminer\idf_escape',array_keys($Wi)))." FROM ".table($R)))return
false;$vn=[];foreach(triggers($R)as$tn=>$Zm){$rn=trigger($tn,$R);$vn[]="CREATE TRIGGER ".idf_escape($tn)." ".implode(" ",$Zm)." ON ".table($B)."\n$rn[Statement]";}$La=$La?"":get_val("SELECT seq FROM sqlite_sequence WHERE name = ".q($R));if(!queries("DROP TABLE ".table($R))||($R==$B&&!queries("ALTER TABLE ".table($Sm)." RENAME TO ".table($B)))||!alter_indexes($B,$w))return
false;if($La)queries("UPDATE sqlite_sequence SET seq = $La WHERE name = ".q($B));foreach($vn
as$rn){if(!queries($rn))return
false;}if(get_rows("PRAGMA foreign_key_check(".table($B).")")){connection()->error="FOREIGN KEY constraint failed";return
false;}queries("COMMIT");queries("PRAGMA foreign_keys = 1");queries("PRAGMA legacy_alter_table = 0");}return
true;}function
index_sql($R,$U,$B,$e){return"CREATE $U ".($U!="INDEX"?"INDEX ":"").idf_escape($B!=""?$B:uniqid($R."_"))." ON ".table($R)." $e";}function
alter_indexes($R,$b){foreach($b
as$Vj){if($Vj[0]=="PRIMARY")return
recreate_table($R,$R,[],[],[],"",$b);}foreach(array_reverse($b)as$X){if(!queries($X[2]=="DROP"?"DROP INDEX ".idf_escape($X[1]):index_sql($R,$X[0],$X[1],"(".implode(", ",$X[2]).")")))return
false;}return
true;}function
truncate_tables(array$T){return
apply_queries("DELETE FROM",$T);}function
drop_views(array$no){return
apply_queries("DROP VIEW",$no);}function
drop_tables(array$T){return
apply_queries("DROP TABLE",$T);}function
move_tables(array$T,array$no,$Om){return
false;}function
trigger($B,$R){if($B=="")return["Statement"=>"BEGIN\n\t;\nEND"];$u='(?:[^`"\s]+|`[^`]*`|"[^"]*")+';$un=trigger_options();preg_match("~^CREATE\\s+TRIGGER\\s*$u\\s*(".implode("|",$un["Timing"]).")\\s+([a-z]+)(?:\\s+OF\\s+($u))?\\s+ON\\s*$u\\s*(?:FOR\\s+EACH\\s+ROW\\s+)?(.*)~is",get_val("SELECT sql FROM sqlite_master WHERE type = 'trigger' AND name = ".q($B)),$A);if(!$A)return[];$ki=$A[3];return["Timing"=>strtoupper($A[1]),"Event"=>strtoupper($A[2]).($ki?" OF":""),"Of"=>idf_unescape($ki),"Trigger"=>$B,"Statement"=>$A[4],];}function
triggers($R){$I=[];$un=trigger_options();foreach(get_rows("SELECT * FROM sqlite_master WHERE type = 'trigger' AND tbl_name = ".q($R))as$J){preg_match('~^CREATE\s+TRIGGER\s*(?:[^`"\s]+|`[^`]*`|"[^"]*")+\s*('.implode("|",$un["Timing"]).')\s*(.*?)\s+ON\b~i',$J["sql"],$A);$I[$J["name"]]=[$A[1],$A[2]];}return$I;}function
trigger_options(){return["Timing"=>["BEFORE","AFTER","INSTEAD OF"],"Event"=>["INSERT","UPDATE","UPDATE OF","DELETE"],"Type"=>["FOR EACH ROW"],];}function
last_id($H){return
get_val("SELECT LAST_INSERT_ROWID()");}function
explain(Db$g,$G){return$g->query("EXPLAIN QUERY PLAN $G");}function
found_rows(array$S,array$Z){}function
types($Sd=false){return[];}function
create_sql($R,$La,$nm){$I=get_val("SELECT sql FROM sqlite_master WHERE type IN ('table', 'view') AND name = ".q($R));foreach(indexes($R)as$B=>$v){if($B==''||$v['type']=='FULLTEXT')continue;$I
.=";\n\n".index_sql($R,$v['type'],$B,"(".implode(", ",array_map('Adminer\idf_escape',$v['columns'])).")");}return$I;}function
truncate_sql($R){return"DELETE FROM ".table($R);}function
use_sql($uc,$nm=""){return"";}function
trigger_sql($R){return
implode(get_vals("SELECT sql || ';;\n' FROM sqlite_master WHERE type = 'trigger' AND tbl_name = ".q($R)));}function
show_variables(){$I=[];foreach(get_rows("PRAGMA pragma_list")as$J){$B=$J["name"];if($B!="pragma_list"&&$B!="compile_options"){$I[$B]=[$B,''];foreach(get_rows("PRAGMA $B")as$J)$I[$B][1].=implode(", ",$J)."\n";}}return$I;}function
show_status(){$I=[];foreach(get_vals("PRAGMA compile_options")as$Fi)$I[]=explode("=",$Fi,2)+['',''];return$I;}function
convert_field(array$n){}function
unconvert_field(array$n,$I){return$I;}function
support($Xd){return
preg_match('~^(check|columns|comment|database|drop_col|dump|fast_status|indexes|descidx|move_col|sql|status|table|transaction_ddl|trigger|variables|view|view_trigger)$~',$Xd);}}add_driver("mssql","MS SQL");if(isset($_GET["mssql"])){define('Adminer\DRIVER',"mssql");if(extension_loaded("sqlsrv")&&$_GET["ext"]!="pdo"){class
Db
extends
SqlDb{var$extension="SQLSRV";var$timeout=0;private$link,$result,$warnings,$transaction=false;private$query_timeout=0;private
function
get_error(){$this->error="";foreach(sqlsrv_errors()as$m){$this->errno=$m["code"];$this->error
.="$m[message]\n";}$this->error=rtrim($this->error);}function
attach(array$N,$V,$E){sqlsrv_configure("WarningsReturnAsErrors",0);$Ub=["UID"=>$V,"PWD"=>$E,"CharacterSet"=>"UTF-8","ReturnDatesAsStrings"=>true];if(isset($_GET["sql"])&&!self::$instance)$Ub["MultipleActiveResultSets"]=false;$em=adminer()->connectSsl();if(isset($em["Encrypt"]))$Ub["Encrypt"]=$em["Encrypt"];if(isset($em["TrustServerCertificate"]))$Ub["TrustServerCertificate"]=$em["TrustServerCertificate"];$k=adminer()->database();if($k!="")$Ub["Database"]=$k;$Ij=$N["port"];$this->link=@sqlsrv_connect($N["host"].($Ij?",$Ij":""),$Ub);if($this->link){$Kf=sqlsrv_server_info($this->link);$this->server_info=$Kf['SQLServerVersion'];}else$this->get_error();return($this->link?'':$this->error);}function
quote($Q){return
unicode_prefix($Q)."'".str_replace("'","''",$Q)."'";}function
select_db($uc){return$this->query(use_sql($uc));}function
query($G,$Cn=false){$H=$this->execute($G);$this->error="";if(!$H){$this->get_error();return
false;}return$this->store_result($H);}function
multi_query($G){$this->result=$this->execute($G);$this->error="";if(!$this->result){$this->get_error();return
false;}return
true;}private
function
execute($G){return
adminer()->executeQuery($G,function()use($G){return
sqlsrv_query($this->link,$G,[],$this->options());});}private
function
options(){$I=["QueryTimeout"=>($this->timeout?:$this->query_timeout)];$this->timeout=0;return$I;}function
timeout($Dh){$this->query_timeout=(int)ceil($Dh/1000);return
true;}function
store_result($H=null){if(!$H)$H=$this->result;if(!$H)return
false;$this->warnings=sqlsrv_errors(SQLSRV_ERR_WARNINGS);if(sqlsrv_field_metadata($H))return
new
Result($H);$this->affected_rows=sqlsrv_rows_affected($H);return
true;}function
next_result(){if(!$this->result)return
false;$I=sqlsrv_next_result($this->result);if($I===false){$this->get_error();$this->result=null;return
true;}return!!$I;}function
warnings(){$I=[];foreach((array)$this->warnings
as$qo)$I[]=$qo["message"];return$I;}function
inTransaction(){return$this->transaction||(isset($_GET["sql"])&&get_val("SELECT @@TRANCOUNT",0,$this));}private
function
transaction_command($G,$r){return
adminer()->executeQuery($G,function()use($r){return$r($this->link);});}function
begin(){$this->transaction=$this->transaction_command("BEGIN",'sqlsrv_begin_transaction');if(!$this->transaction)$this->get_error();return$this->transaction;}function
commit(){if($this->transaction&&!$this->transaction_command("COMMIT",'sqlsrv_commit')){$this->get_error();return
false;}$this->transaction=false;return
true;}function
rollback(){if(!$this->transaction&&isset($_GET["sql"]))return!!$this->query("IF @@TRANCOUNT > 0 ROLLBACK");if($this->transaction&&!$this->transaction_command("ROLLBACK",'sqlsrv_rollback')){$this->get_error();return
false;}$this->transaction=false;return
true;}}class
Result{var$num_rows;private$result,$offset=0,$fields;function
__construct($H){$this->result=$H;}function
fetch_assoc(){return
sqlsrv_fetch_array($this->result,SQLSRV_FETCH_ASSOC);}function
fetch_row(){return
sqlsrv_fetch_array($this->result,SQLSRV_FETCH_NUMERIC);}function
fetch_field(){if(!$this->fields)$this->fields=sqlsrv_field_metadata($this->result);$Bn=[-155=>"datetimeoffset","time",-152=>"xml","varbinary","sql_variant",-11=>"uniqueidentifier","ntext","nvarchar","nchar","bit","tinyint","bigint","image","varbinary","binary","text",1=>"char","numeric","decimal","int","smallint","float","real","float",12=>"varchar",91=>"date","time","datetime",];$n=$this->fields[$this->offset++];$I=new
\stdClass;$I->name=$n["Name"];$I->native_type=idx($Bn,$n["Type"],"");return$I;}function
seek($C){for($s=0;$s<$C;$s++)sqlsrv_fetch($this->result);}}function
last_id($H){return(string)get_val("SELECT SCOPE_IDENTITY()");}function
explain(Db$g,$G){$g->query("SET SHOWPLAN_ALL ON");$I=$g->query($G);$g->query("SET SHOWPLAN_ALL OFF");return$I;}}else{abstract
class
MssqlDb
extends
PdoDb{function
quote($Q){return
unicode_prefix($Q).parent::quote($Q);}function
select_db($uc){return$this->query(use_sql($uc));}function
lastInsertId(){return$this->pdo->lastInsertId();}function
inTransaction(){return
parent::inTransaction()||(isset($_GET["sql"])&&get_val("SELECT @@TRANCOUNT"));}function
rollback(){return(parent::inTransaction()||!isset($_GET["sql"])?parent::rollback():!!$this->query("IF @@TRANCOUNT > 0 ROLLBACK"));}function
warnings(){$H=$this->multi;if(!is_object($H))return[];$m=$H->errorInfo();return[(string)$m[2]];}}function
last_id($H){return
connection()->lastInsertId();}function
explain(Db$g,$G){}if(extension_loaded("pdo_sqlsrv")){class
Db
extends
MssqlDb{var$extension="PDO_SQLSRV";var$timeout=0;private$query_timeout=0;function
attach(array$N,$V,$E){$Ij=$N["port"];$hd="sqlsrv:Server=$N[host]".($Ij?",$Ij":"").(isset($_GET["sql"])&&!self::$instance?";MultipleActiveResultSets=0":"");$em=adminer()->connectSsl();foreach(["Encrypt","TrustServerCertificate"]as$x){if(isset($em[$x]))$hd
.=";$x=".($em[$x]?1:0);}return$this->dsn($hd,$V,$E,[\PDO::SQLSRV_ATTR_DIRECT_QUERY=>true]);}function
query($G,$Cn=false){if(!$this->timeout)return
parent::query($G,$Cn);$this->pdo->setAttribute(\PDO::SQLSRV_ATTR_QUERY_TIMEOUT,$this->timeout);$this->timeout=0;$I=parent::query($G,$Cn);$this->pdo->setAttribute(\PDO::SQLSRV_ATTR_QUERY_TIMEOUT,$this->query_timeout);return$I;}function
timeout($Dh){$this->query_timeout=(int)ceil($Dh/1000);return$this->pdo->setAttribute(\PDO::SQLSRV_ATTR_QUERY_TIMEOUT,$this->query_timeout);}}}elseif(extension_loaded("pdo_dblib")){class
Db
extends
MssqlDb{var$extension="PDO_DBLIB";function
attach(array$N,$V,$E){$Ij=$N["port"];$Nl=$N["socket"];$Hi=[1001=>0,1002=>true,];$I=$this->dsn("dblib:charset=utf8;host=$N[host]".($Ij!=""?";port=$Ij":($Nl!=""?";unix_socket=$Nl":"")),$V,$E,$Hi);if(!$I){$this->query("SET ANSI_NULLS, QUOTED_IDENTIFIER, CONCAT_NULL_YIELDS_NULL, ANSI_WARNINGS, ANSI_PADDING ON");$this->server_info=get_val("SELECT CAST(SERVERPROPERTY('ProductVersion') AS varchar(20))",0,$this);}return$I;}}}}class
Driver
extends
SqlDriver{static$extensions=["SQLSRV","PDO_SQLSRV","PDO_DBLIB"];static$jush="mssql";static$serverSocket=true;var$insertFunctions=["date|time"=>"getdate"];var$editFunctions=["int|decimal|real|float|money|datetime"=>"+/-","char|text"=>"+",];var$functions=["len","lower","round","upper"];var$grouping=["avg","count","count distinct","max","min","sum"];var$generated=["PERSISTED","VIRTUAL"];var$onActions="NO ACTION|CASCADE|SET NULL|SET DEFAULT";var$inout="|OUTPUT";function
operators($xm){return["=","<",">","<=",">=","!=","LIKE","LIKE %%","IN","BETWEEN","IS NULL","NOT LIKE","NOT IN","IS NOT NULL"];}static
function
connect($N,$V,$E){if($N=="")$N="localhost:1433";return
parent::connect($N,$V,$E);}function
__construct(Db$g){parent::__construct($g);$this->types=['Numbers'=>["tinyint"=>3,"smallint"=>5,"int"=>10,"bigint"=>20,"bit"=>1,"decimal"=>0,"numeric"=>0,"real"=>12,"float"=>53,"smallmoney"=>10,"money"=>20,"vector"=>0,],'Date and time'=>["date"=>10,"smalldatetime"=>19,"datetime"=>23,"datetime2"=>19,"time"=>8,"datetimeoffset"=>26],'Strings'=>["char"=>8000,"varchar"=>8000,"text"=>2147483647,"nchar"=>4000,"nvarchar"=>4000,"ntext"=>1073741823,"uniqueidentifier"=>36,"xml"=>2147483647,"json"=>2147483647,"sql_variant"=>8000,"hierarchyid"=>892,],'Binary'=>["binary"=>8000,"varbinary"=>8000,"image"=>2147483647],'Geometry'=>["geometry"=>0,"geography"=>0],];}function
types(){$In=$this->unknownTypes();return
parent::types()+array_fill_keys($In,0);}function
structuredTypes(){$In=$this->unknownTypes();return
array_merge(parent::structuredTypes(),$In);}private
function
unknownTypes(){static$I;if($I===null){$I=[];$Bn=array_flip(get_vals("SELECT name FROM sys.types WHERE is_user_defined = 0 ORDER BY name"));if($Bn){foreach($this->types
as$Me=>$Pe){foreach($Pe
as$U=>$y){if(isset($Bn[$U]))unset($Bn[$U]);else
unset($this->types[$Me][$U]);}if(!$this->types[$Me])unset($this->types[$Me]);}$I=array_keys($Bn);}}return$I;}function
typeName(\stdClass$n){return
idx((array)$n,'sqlsrv:decl_type',parent::typeName($n));}function
insertUpdate($R,array$K,array$Vj){$o=fields($R);$Nn=[];$Z=[];$O=reset($K);$e="c".implode(", c",range(1,count($O)));$eb=0;$Qf=[];foreach($O
as$x=>$X){$eb++;$B=idf_unescape($x);if(!$o[$B]["auto_increment"])$Qf[$x]="c$eb";if(isset($Vj[$B]))$Z[]="$x = c$eb";else$Nn[]="$x = c$eb";}$io=[];foreach($K
as$O)$io[]="(".implode(", ",$O).")";if($Z){$tf=queries("SET IDENTITY_INSERT ".table($R)." ON");$I=queries("MERGE ".table($R)." USING (VALUES\n\t".implode(",\n\t",$io)."\n) AS source ($e) ON ".implode(" AND ",$Z).($Nn?"\nWHEN MATCHED THEN UPDATE SET ".implode(", ",$Nn):"")."\nWHEN NOT MATCHED THEN INSERT (".implode(", ",array_keys($tf?$O:$Qf)).") VALUES (".($tf?$e:implode(", ",$Qf)).");");if($tf)queries("SET IDENTITY_INSERT ".table($R)." OFF");}else$I=queries("INSERT INTO ".table($R)." (".implode(", ",array_keys($O)).") VALUES\n".implode(",\n",$io));return$I;}function
begin(){remember_query("BEGIN TRANSACTION");return$this->conn->begin();}function
slowQuery($G,$Ym){if($this->conn->extension!="PDO_DBLIB"){$this->conn->timeout=$Ym;return$G;}}function
convertSearch($u,array$X,array$n){return(preg_match('~^(bit|n?text|xml|json|vector|uniqueidentifier|sql_variant|hierarchyid|geography|geometry)$~',$n["type"])?"CAST($u AS nvarchar(max))":$u);}function
quoteBinary($Xk){return"0x".bin2hex($Xk);}function
warnings(){$I=[];foreach($this->conn->warnings()as$mh){$mh=trim(preg_replace('~^(\[[^]]+])+~','',$mh));if($mh!="")$I[]=$mh;}return
nl_br(h(implode("\n",$I)));}function
tableHelp($B,$fg=false){$Kg=["sys"=>"catalog-views/sys-","INFORMATION_SCHEMA"=>"information-schema-views/",];$_=$Kg[get_schema()];if($_)return"relational-databases/system-$_".preg_replace('~_~','-',strtolower($B))."-transact-sql";}function
isSystem($k,$L=""){return($L!=""?information_schema($k,$L)||preg_match('~^(guest|db_(owner|accessadmin|securityadmin|ddladmin|backupoperator|(deny)?data(reader|writer)))$~',$L):in_array($k,["master","tempdb","model","msdb"]));}}function
unicode_prefix($Q){return(strlen($Q)!=utf8_length($Q)?"N":"");}function
idf_escape($u){return"[".str_replace("]","]]",$u)."]";}function
table($u){return($_GET["ns"]!=""?idf_escape($_GET["ns"]).".":"").idf_escape($u);}function
get_databases($qe){return
get_vals("SELECT name FROM sys.databases WHERE name NOT IN ('master', 'tempdb', 'model', 'msdb')");}function
limit($G,$Z,$z,$C=0,$ql=" "){return($z?" TOP (".($z+$C).")":"")." $G$Z";}function
limit1($R,$G,$Z,$ql="\n"){return
limit($G,$Z,1,0,$ql);}function
db_collation($k,array$Gb){static$I;if($I===null)$I=get_key_vals("SELECT name, collation_name FROM sys.databases");return$I[$k];}function
logged_user(){return
get_val("SELECT SUSER_NAME()");}function
tables_list(){return
get_key_vals("SELECT name, type_desc FROM sys.all_objects WHERE schema_id = SCHEMA_ID(".q(get_schema()).") AND type IN ('S', 'U', 'V') ORDER BY name");}function
count_tables(array$j){$I=[];foreach($j
as$k){connection()->select_db($k);$I[$k]=get_val("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES");}return$I;}function
table_status($B="",$Wd=false){$I=[];$Ll=[];foreach(($Wd?[]:get_rows("SELECT object_id, SUM(CASE WHEN index_id < 2 THEN row_count ELSE 0 END) AS [Rows],
SUM(CASE WHEN index_id < 2 THEN used_page_count ELSE 0 END) * 8192 AS Data_length,
SUM(CASE WHEN index_id > 1 THEN used_page_count ELSE 0 END) * 8192 AS Index_length,
SUM(reserved_page_count - used_page_count) * 8192 AS Data_free
FROM sys.dm_db_partition_stats".($B!=""?"
WHERE object_id = OBJECT_ID(".q(table($B)).")":"")."
GROUP BY object_id",null,""))as$J){$ji=$J["object_id"];unset($J["object_id"]);$Ll[$ji]=$J;}foreach(get_rows("SELECT ao.object_id, ao.name AS Name, ao.type_desc AS Engine,
	(SELECT cast(value as varchar(max)) FROM fn_listextendedproperty('MS_Description', 'SCHEMA', schema_name(schema_id), 'TABLE', ao.name, null, null)) AS Comment,
	CASE WHEN EXISTS (SELECT 1 FROM sys.indexes i WHERE i.object_id = ao.object_id AND i.is_disabled = 1) THEN 1 END AS invalid_index
FROM sys.all_objects AS ao
WHERE schema_id = SCHEMA_ID(".q(get_schema()).") AND type IN ('S', 'U', 'V') ".($B!=""?"AND name = ".q($B):"ORDER BY name"))as$J){$ji=$J["object_id"];unset($J["object_id"]);$I[$J["Name"]]=$J+idx($Ll,$ji,[]);}return$I;}function
is_view(array$S){return$S["Engine"]=="VIEW";}function
fk_support(array$S){return
true;}function
type_length($U,array$J){return(preg_match("~char|binary~",$U)?($J["max_length"]==-1?"max":intval($J["max_length"])/($U[0]=='n'?2:1)):($U=="decimal"?"$J[precision],$J[scale]":(preg_match('~^(datetime2|datetimeoffset|time)$~',$U)?$J["scale"]:($U=="vector"?(intval($J["max_length"])-8)/4:""))));}function
fields($R){$Nb=get_key_vals("SELECT objname, cast(value as varchar(max)) FROM fn_listextendedproperty('MS_DESCRIPTION', 'schema', ".q(get_schema()).", 'table', ".q($R).", 'column', NULL)");$I=[];$ym=get_val("SELECT object_id FROM sys.all_objects WHERE schema_id = SCHEMA_ID(".q(get_schema()).") AND type IN ('S', 'U', 'V') AND name = ".q($R));foreach(get_rows("SELECT c.max_length, c.precision, c.scale, c.name, c.is_nullable, c.is_identity, c.collation_name,
	COALESCE(bt.name, t.name) type, d.definition [default], d.name default_constraint, i.is_primary_key
FROM sys.all_columns c
JOIN sys.types t ON c.user_type_id = t.user_type_id
LEFT JOIN sys.types bt ON t.system_type_id = bt.user_type_id AND t.is_user_defined = 1
LEFT JOIN sys.default_constraints d ON c.default_object_id = d.object_id
LEFT JOIN sys.index_columns ic ON c.object_id = ic.object_id AND c.column_id = ic.column_id
LEFT JOIN sys.indexes i ON ic.object_id = i.object_id AND ic.index_id = i.index_id
WHERE c.object_id = ".q($ym))as$J){$U=$J["type"];$y=type_length($U,$J);$l=$J["default"];$Bc=false;if(preg_match("~^\\(N?('(?:[^']|'')*')\\)$~s",$l,$A))$l=idf_unescape($A[1]);elseif(preg_match('~^\(\(([-+]?[\d.]+)\)\)$~',$l,$A))$l=$A[1];elseif($l!==null){$l=preg_replace('~^\((.*)\)$~s','\1',$l);$Bc=true;}$I[$J["name"]]=["field"=>$J["name"],"full_type"=>$U.($y!=""?"($y)":""),"type"=>$U,"length"=>$y,"default"=>$l,"default_sql"=>$Bc,"default_constraint"=>$J["default_constraint"],"null"=>$J["is_nullable"],"auto_increment"=>$J["is_identity"],"collation"=>$J["collation_name"],"privileges"=>($U=="timestamp"?["select"=>1,"where"=>1,"order"=>1]:["insert"=>1,"select"=>1,"update"=>1,"where"=>1,"order"=>1]),"primary"=>$J["is_primary_key"],"comment"=>$Nb[$J["name"]],];}foreach(get_rows("SELECT * FROM sys.computed_columns WHERE object_id = ".q($ym))as$J){$I[$J["name"]]["generated"]=($J["is_persisted"]?"PERSISTED":"VIRTUAL");$I[$J["name"]]["default"]=$J["definition"];}return$I;}function
indexes($R,$h=null){$I=[];foreach(get_rows("SELECT i.name, key_ordinal, is_unique, is_primary_key, is_disabled, c.name AS column_name, is_descending_key
FROM sys.indexes i
INNER JOIN sys.index_columns ic ON i.object_id = ic.object_id AND i.index_id = ic.index_id
INNER JOIN sys.columns c ON ic.object_id = c.object_id AND ic.column_id = c.column_id
WHERE OBJECT_NAME(i.object_id) = ".q($R),$h)as$J){$B=$J["name"];$I[$B]["type"]=($J["is_primary_key"]?"PRIMARY":($J["is_unique"]?"UNIQUE":"INDEX"));$I[$B]["lengths"]=[];$I[$B]["invalid"]=!!$J["is_disabled"];$I[$B]["columns"][$J["key_ordinal"]]=$J["column_name"];$I[$B]["descs"][$J["key_ordinal"]]=($J["is_descending_key"]?'1':null);}return$I;}function
view($B){return["select"=>preg_replace('~^(?:[^[]|\[[^]]*])*\s+AS\s+~isU','',get_val("SELECT VIEW_DEFINITION FROM INFORMATION_SCHEMA.VIEWS WHERE TABLE_SCHEMA = SCHEMA_NAME() AND TABLE_NAME = ".q($B)))];}function
collations(){$I=[];foreach(get_vals("SELECT name FROM fn_helpcollations()")as$Fb)$I[preg_replace('~_.*~','',$Fb)][]=$Fb;return$I;}function
information_schema($k,$L=""){return
in_array($L!=""?$L:get_schema(),["INFORMATION_SCHEMA","sys"]);}function
error(){return
nl_br(h(preg_replace('~^(\[[^]]*])+~m','',connection()->error)));}function
create_database($k,$Fb){return
queries("CREATE DATABASE ".idf_escape($k).(preg_match('~^[a-z0-9_]+$~i',$Fb)?" COLLATE $Fb":""));}function
drop_databases(array$j){return!!queries("DROP DATABASE ".implode(", ",array_map('Adminer\idf_escape',$j)));}function
rename_database($B,$Fb){if(preg_match('~^[a-z0-9_]+$~i',$Fb))queries("ALTER DATABASE ".idf_escape(DB)." COLLATE $Fb");queries("ALTER DATABASE ".idf_escape(DB)." MODIFY NAME = ".idf_escape($B));return
true;}function
auto_increment(){return" IDENTITY".($_POST["Auto_increment"]!=""?"(".number($_POST["Auto_increment"]).",1)":"")." PRIMARY KEY";}function
alter_table($R,$B,array$o,array$se,$f,$vd,$Fb,$La,$rj){$b=[];$Nb=[];$Si=fields($R);foreach($o
as$n){$d=idf_escape($n[0]);$X=$n[1];if(!$X)$b["DROP"][]=" COLUMN $d";else{$Qi=idx($Si[$n[0]],"comment");if($X[5]!=($Qi!=""?" COMMENT ".q($Qi):""))$Nb[idf_unescape($X[0])]=[$Qi!="",substr($X[5],9)];unset($X[5]);if(preg_match('~ AS ~',$X[3]))unset($X[1],$X[2]);if($n[0]=="")$b["ADD"][]="\n  ".implode("",$X).($R==""?str_replace(" FOREIGN KEY ($X[0])","",$se[$X[0]]):"");else{$l=$X[3];unset($X[3]);unset($X[6]);if($d!=$X[0])queries("EXEC sp_rename ".q(table($R).".$d").", ".q(idf_unescape($X[0])).", 'COLUMN'");$b["ALTER COLUMN ".implode("",$X)][]="";$Ri=$Si[$n[0]];if(default_value($Ri)!=$l){if($Ri["default"]!==null)$b["DROP"][]=" ".idf_escape($Ri["default_constraint"]);if($l)$b["ADD"][]="\n $l FOR $d";}}}}if($R==""){$ka=(array)$b["ADD"];foreach($se
as$x=>$X){if(!is_string($x))$ka[]="\n$X";}if(!queries("CREATE TABLE ".table($B)." (".implode(",",$ka)."\n)"))return
false;}else{if($R!=$B)queries("EXEC sp_rename ".q(table($R)).", ".q($B));if($se)$b[""]=$se;foreach($b
as$x=>$X){if(!queries("ALTER TABLE ".table($B)." $x".implode(",",$X)))return
false;}}$ek="@name = N'MS_Description', @level0type = N'Schema', @level0name = ".q(get_schema()).", @level1type = N'Table', @level1name = ".q($B);foreach($Nb
as$x=>$X){list($cd,$Lb)=$X;$fk="$ek, @level2type = N'Column', @level2name = ".q($x);if($cd)queries("EXEC sp_dropextendedproperty $fk");if($Lb!="")queries("EXEC sp_addextendedproperty $fk, @value = $Lb");}if($f!==null){if(idx(table_status1($B),"Comment")!="")queries("EXEC sp_dropextendedproperty $ek");if($f!="")queries("EXEC sp_addextendedproperty $ek, @value = ".q($f));}return
true;}function
alter_indexes($R,$b){$v=[];$cd=[];$ik=[];foreach($b
as$X){if($X[2]=="DROP"){if($X[0]=="PRIMARY"||($X[0]=="UNIQUE"&&get_val("SELECT is_unique_constraint FROM sys.indexes WHERE object_id = OBJECT_ID(".q(table($R)).") AND name = ".q($X[1]))))$cd[]=idf_escape($X[1]);else$v[]=idf_escape($X[1])." ON ".table($R);}else$ik[]=($X[0]!="PRIMARY"?"CREATE $X[0] ".($X[0]!="INDEX"?"INDEX ":"").idf_escape($X[1]!=""?$X[1]:uniqid($R."_"))." ON ".table($R):"ALTER TABLE ".table($R)." ADD PRIMARY KEY")." (".implode(", ",$X[2]).")";}if($cd)array_unshift($ik,"ALTER TABLE ".table($R)." DROP ".implode(", ",$cd));if($v)array_unshift($ik,"DROP INDEX ".implode(", ",$v));foreach($ik
as$G){if(!queries($G))return
false;}return
true;}function
found_rows(array$S,array$Z){}function
foreign_keys($R){$I=[];$zi=["CASCADE","NO ACTION","SET NULL","SET DEFAULT"];$L=get_schema();foreach(get_rows("EXEC sp_fkeys @fktable_name = ".q($R).", @fktable_owner = ".q($L))as$J){$q=&$I[$J["FK_NAME"]];$q["db"]=($J["PKTABLE_QUALIFIER"]==DB?"":$J["PKTABLE_QUALIFIER"]);$q["ns"]=($J["PKTABLE_OWNER"]==$L?"":$J["PKTABLE_OWNER"]);$q["table"]=$J["PKTABLE_NAME"];$q["on_update"]=$zi[$J["UPDATE_RULE"]];$q["on_delete"]=$zi[$J["DELETE_RULE"]];$q["source"][]=$J["FKCOLUMN_NAME"];$q["target"][]=$J["PKCOLUMN_NAME"];}return$I;}function
truncate_tables(array$T){return
apply_queries("TRUNCATE TABLE",$T);}function
drop_views(array$no){return
queries("DROP VIEW ".implode(", ",array_map('Adminer\table',$no)));}function
drop_tables(array$T){return
queries("DROP TABLE ".implode(", ",array_map('Adminer\table',$T)));}function
move_tables(array$T,array$no,$Om){return
apply_queries("ALTER SCHEMA ".idf_escape($Om)." TRANSFER",array_merge($T,$no));}function
trigger($B,$R){if($B=="")return[];$K=get_rows("SELECT s.name [Trigger],
CASE WHEN OBJECTPROPERTY(s.id, 'ExecIsInsertTrigger') = 1 THEN 'INSERT'
	WHEN OBJECTPROPERTY(s.id, 'ExecIsUpdateTrigger') = 1 THEN 'UPDATE'
	WHEN OBJECTPROPERTY(s.id, 'ExecIsDeleteTrigger') = 1 THEN 'DELETE' END [Event],
CASE WHEN OBJECTPROPERTY(s.id, 'ExecIsInsteadOfTrigger') = 1 THEN 'INSTEAD OF' ELSE 'AFTER' END [Timing],
c.text
FROM sysobjects s
JOIN syscomments c ON s.id = c.id
WHERE s.xtype = 'TR' AND s.name = ".q($B));$I=reset($K);if($I)$I["Statement"]=preg_replace('~^.+\s+AS\s+~isU','',$I["text"]);return($I?:[]);}function
triggers($R){$I=[];foreach(get_rows("SELECT sys1.name,
CASE WHEN OBJECTPROPERTY(sys1.id, 'ExecIsInsertTrigger') = 1 THEN 'INSERT'
	WHEN OBJECTPROPERTY(sys1.id, 'ExecIsUpdateTrigger') = 1 THEN 'UPDATE'
	WHEN OBJECTPROPERTY(sys1.id, 'ExecIsDeleteTrigger') = 1 THEN 'DELETE' END [Event],
CASE WHEN OBJECTPROPERTY(sys1.id, 'ExecIsInsteadOfTrigger') = 1 THEN 'INSTEAD OF' ELSE 'AFTER' END [Timing]
FROM sysobjects sys1
JOIN sysobjects sys2 ON sys1.parent_obj = sys2.id
WHERE sys1.xtype = 'TR' AND sys2.name = ".q($R))as$J)$I[$J["name"]]=[$J["Timing"],$J["Event"]];return$I;}function
trigger_options(){return["Timing"=>["AFTER","INSTEAD OF"],"Event"=>["INSERT","UPDATE","DELETE"],"Type"=>["AS"],];}function
routine($B,$U){$Ec=get_val("SELECT m.definition
FROM sys.objects o
JOIN sys.sql_modules m ON m.object_id = o.object_id
WHERE o.schema_id = SCHEMA_ID(".q(get_schema()).") AND o.name = ".q($B)." AND o.type = ".q($U=="PROCEDURE"?"P":"FN"));if(!$Ec)return[];$I=["definition"=>preg_replace('~^(?:[^[]|\[[^]]*])*\s+AS\s+(?=\S)~isU','',$Ec),"fields"=>[]];foreach(get_rows("SELECT p.name, TYPE_NAME(p.user_type_id) [type], p.max_length, p.precision, p.scale, p.is_output
FROM sys.parameters p
JOIN sys.objects o ON p.object_id = o.object_id
WHERE o.schema_id = SCHEMA_ID(".q(get_schema()).") AND o.name = ".q($B)."
ORDER BY p.parameter_id")as$J){$ce=$J["type"];$y=type_length($ce,$J);$n=["field"=>preg_replace('~^@~','',$J["name"]),"type"=>$ce,"length"=>$y,"full_type"=>$ce.($y!=""?"($y)":""),"null"=>true,"inout"=>($J["is_output"]?"OUTPUT":""),];if($n["field"]=="")$I["returns"]=$n;else$I["fields"][]=$n;}return$I;}function
routines(){return
get_rows("SELECT o.name SPECIFIC_NAME, o.name ROUTINE_NAME,
	CASE o.type WHEN 'P' THEN 'PROCEDURE' ELSE 'FUNCTION' END ROUTINE_TYPE, TYPE_NAME(p.user_type_id) DTD_IDENTIFIER
FROM sys.objects o
LEFT JOIN sys.parameters p ON o.object_id = p.object_id AND p.parameter_id = 0
WHERE o.schema_id = SCHEMA_ID(".q(get_schema()).") AND o.type IN ('P', 'FN')
ORDER BY o.name");}function
routine_languages(){return[];}function
routine_options($Ok){return[];}function
routine_id($B,array$J){return
table($B);}function
schemas(){return
get_vals("SELECT name FROM sys.schemas");}function
get_schema(){if($_GET["ns"]!="")return$_GET["ns"];return
get_val("SELECT SCHEMA_NAME()");}function
set_schema($L,$h=null){$_GET["ns"]=$L;return!!get_val("SELECT 1 FROM sys.schemas WHERE name = ".q($L),0,$h);}function
schema_comment($L){return(string)get_val("SELECT CAST(value AS nvarchar(max)) FROM fn_listextendedproperty('MS_Description', 'SCHEMA', ".q($L).", NULL, NULL, NULL, NULL)");}function
set_schema_comment($L,$f){$ek="@name = N'MS_Description', @level0type = N'Schema', @level0name = ".q($L);return(schema_comment($L)==""||queries("EXEC sp_dropextendedproperty $ek"))&&($f==""||queries("EXEC sp_addextendedproperty $ek, @value = ".q($f)));}function
create_sql($R,$La,$nm){$P=table_status1($R);if(is_view($P)){$mo=view($R);return"CREATE VIEW ".table($R)." AS $mo[select]";}$ek="EXEC sp_addextendedproperty @name = N'MS_Description', @level0type = N'Schema', @level0name = ".q(get_schema()).", @level1type = N'Table', @level1name = ".q($R);$Nb=($P["Comment"]!=""?";\n\n$ek, @value = ".q($P["Comment"]):"");$o=[];$Vj=false;foreach(fields($R)as$B=>$n){$X=process_field($n,$n);if($X[6])$Vj=true;unset($X[5]);$o[]=implode("",$X);if($n["comment"]!="")$Nb
.=";\n\n$ek, @level2type = N'Column', @level2name = ".q($B).", @value = ".q($n["comment"]);}foreach(indexes($R)as$B=>$v){if(!$Vj||$v["type"]!="PRIMARY"){$e=[];foreach($v["columns"]as$x=>$X)$e[]=idf_escape($X).($v["descs"][$x]?" DESC":"");$B=idf_escape($B);$o[]=($v["type"]=="INDEX"?"INDEX $B":"CONSTRAINT $B ".($v["type"]=="UNIQUE"?"UNIQUE":"PRIMARY KEY"))." (".implode(", ",$e).")";}}foreach(driver()->checkConstraints($R)as$B=>$rb)$o[]="CONSTRAINT ".idf_escape($B)." CHECK ($rb)";return"CREATE TABLE ".table($R)." (\n\t".implode(",\n\t",$o)."\n)$Nb";}function
foreign_keys_sql($R){$o=[];foreach(foreign_keys($R)as$se)$o[]=ltrim(format_foreign_key($se));return($o?"ALTER TABLE ".table($R)." ADD\n\t".implode(",\n\t",$o).";\n\n":"");}function
truncate_sql($R){return"TRUNCATE TABLE ".table($R);}function
use_sql($uc,$nm=""){return"USE ".idf_escape($uc);}function
use_schema_sql($L,$nm){$B=idf_escape($L);$i="EXEC(".q("CREATE SCHEMA $B").")";$f=schema_comment($L);if($f!="")$i="BEGIN\n$i\nEXEC sp_addextendedproperty @name = N'MS_Description', @level0type = N'Schema', @level0name = ".q($L).", @value = ".q($f)."\nEND";return($nm=="DROP+CREATE"?"DROP SCHEMA IF EXISTS $B;\n":"")."IF SCHEMA_ID(".q($L).") IS NULL $i";}function
trigger_sql($R){$I="";foreach(triggers($R)as$B=>$rn)$I
.=create_trigger(" ON ".table($R),trigger($B,$R)).";";return$I;}function
convert_field(array$n){}function
unconvert_field(array$n,$I){return$I;}function
support($Xd){return
preg_match('~^(check|comment|columns|database|drop_col|dump|fast_status|indexes|descidx|procedure|routine|scheme|sql|table|transaction_ddl|trigger|view|view_trigger)$~',$Xd);}}add_driver("oracle","Oracle");if(isset($_GET["oracle"])){define('Adminer\DRIVER',"oracle");function
easy_connect(array$N){return
url_host($N["host"]).($N["port"]!=""?":$N[port]":"").$N["path"];}if(extension_loaded("oci8")&&$_GET["ext"]!="pdo"){class
Db
extends
SqlDb{var$extension="OCI8";var$error_offset=0;var$timeout=0;private$link,$transaction=false;private$query_timeout=0;function
attach(array$N,$V,$E){$this->link=@oci_new_connect($V,$E,easy_connect($N),"AL32UTF8");if($this->link){$this->server_info=oci_server_version($this->link);return'';}$m=oci_error();return($m?$m["message"]:'Unknown error.');}function
quote($Q){return"'".str_replace("'","''",$Q)."'";}function
select_db($uc){return$this->query("ALTER SESSION SET CURRENT_SCHEMA = ".idf_escape($uc));}function
query($G,$Cn=false){$H=oci_parse($this->link,$G);$this->error="";$this->error_offset=0;if(!$H){$m=oci_error($this->link);$this->errno=$m["code"];$this->error=$m["message"];return
false;}$Ym=$this->timeout;$lk=$this->query_timeout;if($Ym){$this->timeout=0;$this->timeout(1000*$Ym);}$Cd=[];$I=adminer()->executeQuery($G,function()use($H,&$Cd){$Cd=php_errors(function()use($H,&$I){$I=oci_execute($H,($this->transaction?OCI_NO_AUTO_COMMIT:OCI_COMMIT_ON_SUCCESS));});return$I;});if($Ym)$this->timeout($lk);if($Cd)$this->error=end($Cd);if(!$I){$m=oci_error($H);$this->error_offset=($m?$m["offset"]:0);}if($I){if(oci_num_fields($H))return
new
Result($H);$this->affected_rows=oci_num_rows($H);oci_free_statement($H);}return$I;}function
timeout($Dh){$this->query_timeout=$Dh;return
function_exists('oci_set_call_timeout')&&oci_set_call_timeout($this->link,$Dh);}function
inTransaction(){return$this->transaction;}function
begin(){$this->transaction=true;return
true;}function
commit(){return$this->end_transaction($this->transaction_command("COMMIT",'oci_commit'));}function
rollback(){return$this->end_transaction($this->transaction_command("ROLLBACK",'oci_rollback'));}private
function
transaction_command($G,$r){return
adminer()->executeQuery($G,function()use($r){return@$r($this->link);});}private
function
end_transaction($I){$this->transaction=false;if(!$I){$m=oci_error($this->link);$this->errno=$m["code"];$this->error=$m["message"];}return$I;}}class
Result{var$num_rows;private$result,$offset=1;function
__construct($H){$this->result=$H;}private
function
convert($J){foreach((array)$J
as$x=>$X){if(is_a($X,'OCILob')||is_a($X,'OCI-Lob'))$J[$x]=$X->load();}return$J;}function
fetch_assoc(){return$this->convert(oci_fetch_assoc($this->result));}function
fetch_row(){return$this->convert(oci_fetch_row($this->result));}function
fetch_field(){$d=$this->offset++;$I=new
\stdClass;$I->name=oci_field_name($this->result,$d);$U=oci_field_type($this->result,$d);$I->native_type=idx([100=>"binary_float","binary_double"],$U,$U);return$I;}}}elseif(extension_loaded("pdo_oci")){class
Db
extends
PdoDb{var$extension="PDO_OCI";var$timeout=0;private$query_timeout=0;function
attach(array$N,$V,$E){return$this->dsn("oci:dbname=//".easy_connect($N).";charset=AL32UTF8",$V,$E);}function
select_db($uc){return$this->query("ALTER SESSION SET CURRENT_SCHEMA = ".idf_escape($uc));}function
query($G,$Cn=false){$Ym=$this->timeout;if(!$Ym)return
parent::query($G,$Cn);$lk=$this->query_timeout;$this->timeout=0;$this->timeout(1000*$Ym);$I=parent::query($G,$Cn);$this->timeout($lk);return$I;}function
timeout($Dh){$this->query_timeout=$Dh;return$this->pdo->setAttribute(1004,$Dh);}}}class
Driver
extends
SqlDriver{static$extensions=["OCI8","PDO_OCI"];static$jush="oracle";static$serverPath=true;var$insertFunctions=["date"=>"current_date","timestamp"=>"current_timestamp",];var$editFunctions=["number|float|double"=>"+/-","date|timestamp"=>"+ interval/- interval","char|clob"=>"||",];var$functions=["length","lower","round","upper"];var$grouping=["avg","count","count distinct","max","min","sum"];function
operators($xm){return["=","<",">","<=",">=","!=","LIKE","LIKE %%","IN","BETWEEN","IS NULL","NOT LIKE","NOT IN","IS NOT NULL","SQL"];}static
function
connect($N,$V,$E){$g=parent::connect($N,$V,$E);if(is_object($g))$g->query("ALTER SESSION SET CURSOR_SHARING = FORCE"." NLS_DATE_FORMAT = 'YYYY-MM-DD HH24:MI:SS'"." NLS_TIMESTAMP_FORMAT = 'YYYY-MM-DD HH24:MI:SS.FF'"." NLS_TIMESTAMP_TZ_FORMAT = 'YYYY-MM-DD HH24:MI:SS.FF TZH:TZM'");return$g;}function
__construct(Db$g){parent::__construct($g);$this->types=['Numbers'=>["number"=>38,"binary_float"=>12,"binary_double"=>21],'Date and time'=>["date"=>19,"timestamp"=>29,"interval year"=>12,"interval day"=>28],'Strings'=>["char"=>2000,"varchar2"=>4000,"nchar"=>2000,"nvarchar2"=>4000,"clob"=>4294967295,"nclob"=>4294967295],'Binary'=>["raw"=>2000,"long raw"=>2147483648,"blob"=>4294967295,"bfile"=>4294967296],'Geometry'=>["sdo_geometry"=>0],];}function
begin(){return$this->conn->begin();}function
slowQuery($G,$Ym){$this->conn->timeout=$Ym;return$G;}function
convertSearch($u,array$X,array$n){$U=$n["type"];$_j=strpos($X["op"],"LIKE")!==false;if($U=="xmltype")return"XMLSERIALIZE(CONTENT $u AS VARCHAR2(4000))";if($U=="json")return"JSON_SERIALIZE($u)";if(preg_match('~^(date|timestamp)~',$U))return"TO_CHAR($u, 'YYYY-MM-DD HH24:MI:SS')";if(preg_match('~char~',$U)||(preg_match('~clob~',$U)&&$_j))return$u;return(!$_j&&preg_match(number_type(),$U)?$u:"TO_CHAR($u)");}function
quoteBinary($Xk){return"HEXTORAW(".q(bin2hex($Xk)).")";}function
typeName(\stdClass$n){return
strtolower(parent::typeName($n));}function
hasCStyleEscapes(){return
true;}function
errorLine($G){$C=(isset($this->conn->error_offset)?$this->conn->error_offset:0);return($C?substr_count($G,"\n",0,min($C,strlen($G)))+1:0);}function
tableHelp($B,$fg=false){if(DB=="SYS"){if(preg_match('~^G?V_\$(.+)~',$B,$A))return"refrn/V-$A[1].html";if(preg_match('~^(ALL|DBA|USER)_~',$B))return"refrn/$B.html";}}function
select($R,array$M,array$Z,array$Me,array$Ki=[],$z=1,$D=0,$Xj=false){if(in_array("*",$M)){$dc=[];$we=false;foreach(fields($R)as$B=>$n){$Fa=convert_field($n);$we=($we||$Fa);$dc[]=($Fa?"$Fa AS ":"").idf_escape($B);}if($we)$M=$dc;}return
parent::select($R,$M,$Z,$Me,$Ki,$z,$D,$Xj);}function
allFields(){$I=[];$K=get_rows('SELECT c.table_name "tab", c.column_name "field", c.data_type "type", c.nullable "nullable",
	c.data_precision "precision", c.data_scale "scale", c.char_col_decl_length "char_length"
FROM all_tab_columns c
WHERE '.where_owner("c.owner").'
ORDER BY c.table_name, c.column_id',$this->conn);foreach($K
as$J){$y=($J["precision"]==""&&$J["scale"]!=""?"*":$J["precision"]).",$J[scale]";$J["length"]=(strpos($J["type"],"(")?"":($y==","?$J["char_length"]:$y));$J["type"]=strtolower($J["type"]);$J["null"]=($J["nullable"]=="Y");$I[$J["tab"]][]=$J;}return$I;}}function
idf_escape($u){return'"'.str_replace('"','""',$u).'"';}function
table($u){return
idf_escape($u);}function
get_databases($qe){$I=get_vals("SELECT username FROM all_users WHERE oracle_maintained = 'N' ORDER BY 1");return($I?:get_vals("SELECT username FROM all_users ORDER BY 1"));}function
limit($G,$Z,$z,$C=0,$ql=" "){return($C?" * FROM (SELECT t.*, rownum AS rnum FROM (SELECT $G$Z) t WHERE rownum <= ".($z+$C).") WHERE rnum > $C":($z?" * FROM (SELECT $G$Z) WHERE rownum <= ".($z+$C):" $G$Z"));}function
limit1($R,$G,$Z,$ql="\n"){return" $G$Z";}function
db_collation($k,array$Gb){static$I;if($I===null)$I=get_val("SELECT value FROM nls_database_parameters WHERE parameter = 'NLS_CHARACTERSET'");return$I;}function
logged_user(){return
get_val("SELECT USER FROM DUAL");}function
where_owner($bj="owner"){return"$bj = ".q(DB);}function
views_table($e){return"(SELECT $e FROM all_views WHERE ".where_owner().")";}function
objects_table(){return"(SELECT object_name, DECODE(object_type, 'VIEW', 'view', 'table') object_type FROM all_objects WHERE ".where_owner()." AND object_type IN ('TABLE', 'VIEW'))";}function
tables_list(){return
get_key_vals("SELECT * FROM ".objects_table()." ORDER BY 1");}function
count_tables(array$j){$I=[];foreach($j
as$k)$I[$k]=get_val("SELECT COUNT(*) FROM all_objects WHERE object_type IN ('TABLE', 'VIEW') AND owner = ".q($k));return$I;}function
table_status($B="",$Wd=false){$I=[];$dl=q($B);if($Wd||$B!=""){foreach(get_rows('SELECT object_name "Name", object_type "Engine" FROM '.objects_table().($B!=""?" WHERE object_name = $dl":"").' ORDER BY 1')as$J)$I[$J["Name"]]=$J;return$I;}foreach(get_rows('SELECT t.table_name "Name", \'table\' "Engine", s.bytes "Data_length", i.bytes "Index_length", t.num_rows "Rows"
FROM all_tables t
LEFT JOIN (SELECT segment_name, SUM(bytes) bytes FROM user_segments WHERE segment_type LIKE \'TABLE%\' GROUP BY segment_name) s ON s.segment_name = t.table_name
LEFT JOIN (SELECT i.table_name, SUM(s.bytes) bytes FROM user_indexes i
	JOIN user_segments s ON s.segment_name = i.index_name AND s.segment_type LIKE \'INDEX%\' GROUP BY i.table_name) i ON i.table_name = t.table_name
WHERE '.where_owner("t.owner")."
UNION SELECT view_name, 'view', 0, 0, 0 FROM ".views_table("view_name")."
ORDER BY 1")as$J)$I[$J["Name"]]=$J;foreach(get_vals("SELECT table_name FROM all_indexes ai WHERE ".where_owner("table_owner")." AND (status = 'UNUSABLE'
	OR EXISTS (SELECT 1 FROM all_ind_partitions aip WHERE aip.index_owner = ai.owner AND aip.index_name = ai.index_name AND aip.status = 'UNUSABLE')
	OR EXISTS (SELECT 1 FROM all_ind_subpartitions ais WHERE ais.index_owner = ai.owner AND ais.index_name = ai.index_name AND ais.status = 'UNUSABLE'))")as$R){if($I[$R])$I[$R]["invalid_index"]="1";}return$I;}function
is_view(array$S){return$S["Engine"]=="view";}function
fk_support(array$S){return
true;}function
fields($R){$I=[];$tf=null;$Vj=[];foreach(table_constraints($R)as$Wb){if($Wb["type"]=="P")$Vj=$Wb["columns"];}foreach(get_rows("SELECT * FROM all_tab_columns WHERE table_name = ".q($R)." AND ".where_owner()." ORDER BY column_id")as$J){$U=$J["DATA_TYPE"];$y=($J["DATA_PRECISION"]==""&&$J["DATA_SCALE"]!=""?"*":$J["DATA_PRECISION"]).",$J[DATA_SCALE]";if($y==","){$y=($U=="RAW"?$J["DATA_LENGTH"]:(preg_match('~CHAR~',$U)?$J["CHAR_COL_DECL_LENGTH"]:""));if($J["CHAR_USED"]=="C"&&preg_match('~^(VARCHAR2|CHAR)$~',$U))$y="$J[CHAR_LENGTH] CHAR";}elseif(strpos($U,"("))$y="";$l=$J["DATA_DEFAULT"];$Bc=false;if($l!==null){$l=rtrim($l);if(preg_match("~^'(?:[^']|'')*'\$~s",$l))$l=idf_unescape($l);elseif(preg_match('~^NULL$~i',$l))$l=null;else$Bc=!is_numeric($l);}if($J["IDENTITY_COLUMN"]=="YES"){if($tf===null)$tf=get_key_vals("SELECT column_name, generation_type FROM all_tab_identity_cols WHERE table_name = ".q($R)." AND ".where_owner());$l="GENERATED ".$tf[$J["COLUMN_NAME"]].($J["DEFAULT_ON_NULL"]=="YES"?" ON NULL":"")." AS IDENTITY";}$ak=["insert"=>1,"select"=>1,"update"=>1,"order"=>1];if($J["DATA_TYPE_OWNER"]==""||$U=="XMLTYPE")$ak["where"]=1;$I[$J["COLUMN_NAME"]]=["field"=>$J["COLUMN_NAME"],"full_type"=>$U.($y?"($y)":""),"type"=>strtolower($U),"length"=>$y,"default"=>$l,"default_sql"=>($Bc&&$J["IDENTITY_COLUMN"]!="YES"),"null"=>($J["NULLABLE"]=="Y"),"auto_increment"=>($J["IDENTITY_COLUMN"]=="YES"),"privileges"=>$ak,"primary"=>in_array($J["COLUMN_NAME"],$Vj),];}return$I;}function
table_constraints($R,$h=null){static$hb=[];if(!$h&&isset($hb[$R]))return$hb[$R];$I=[];foreach(get_rows('SELECT c.constraint_name "name", c.constraint_type "type", c.r_owner "r_owner", c.r_constraint_name "r_constraint", c.delete_rule "delete_rule", cc.column_name "column"
FROM all_constraints c
JOIN all_cons_columns cc ON cc.owner = c.owner AND cc.constraint_name = c.constraint_name
WHERE c.constraint_type IN (\'P\', \'U\', \'R\') AND '.where_owner("c.owner")." AND c.table_name = ".q($R).'
ORDER BY cc.position',$h)as$J){$B=$J["name"];$I[$B]["type"]=$J["type"];$I[$B]["r_owner"]=$J["r_owner"];$I[$B]["r_constraint"]=$J["r_constraint"];$I[$B]["delete_rule"]=$J["delete_rule"];$I[$B]["columns"][]=$J["column"];}if(!$h)$hb[$R]=$I;return$I;}function
indexes($R,$h=null){$I=[];$Xb=[];$qj=[];foreach(table_constraints($R,$h)as$B=>$Wb)$Xb[$B]=$Wb["type"];foreach(get_rows("SELECT aic.*, atc.data_default, ai.status, ai.partitioned
FROM all_ind_columns aic
JOIN all_indexes ai ON ai.owner = aic.index_owner AND ai.index_name = aic.index_name
LEFT JOIN all_tab_cols atc ON aic.column_name = atc.column_name AND aic.table_name = atc.table_name AND aic.index_owner = atc.owner
WHERE aic.table_name = ".q($R)." AND ".where_owner("aic.table_owner")."
ORDER BY aic.column_position",$h)as$J){$Ff=$J["INDEX_NAME"];$Jb=$J["DATA_DEFAULT"];$Jb=($Jb?trim($Jb,'"'):$J["COLUMN_NAME"]);$U=idx($Xb,$Ff);$I[$Ff]["type"]=($U=="P"?"PRIMARY":($U=="U"?"UNIQUE":"INDEX"));$I[$Ff]["invalid"]=($J["STATUS"]=="UNUSABLE");$I[$Ff]["columns"][]=$Jb;$I[$Ff]["lengths"][]=($J["CHAR_LENGTH"]&&$J["CHAR_LENGTH"]!=$J["COLUMN_LENGTH"]?$J["CHAR_LENGTH"]:null);$I[$Ff]["descs"][]=($J["DESCEND"]&&$J["DESCEND"]=="DESC"?'1':null);if($J["PARTITIONED"]=="YES")$qj[$Ff]=q($Ff);}if($qj){$zf=where_owner("index_owner")." AND index_name IN (".implode(", ",$qj).") AND status = 'UNUSABLE'";foreach(get_rows("SELECT index_name FROM all_ind_partitions WHERE $zf UNION SELECT index_name FROM all_ind_subpartitions WHERE $zf",$h)as$J)$I[$J["INDEX_NAME"]]["invalid"]=true;}uasort($I,function($ha,$Pa){$Ki=["PRIMARY"=>0,"UNIQUE"=>1,"INDEX"=>2];return$Ki[$ha["type"]]-$Ki[$Pa["type"]];});return$I;}function
view($B){$K=get_rows('SELECT text "select" FROM '.views_table("view_name, text").' WHERE view_name = '.q($B));return($K?$K[0]:[]);}function
collations(){return[];}function
information_schema($k,$L=""){return
in_array($L!=""?$L:$k,["INFORMATION_SCHEMA","SYS","SYSTEM"]);}function
error(){return
h(connection()->error);}function
explain(Db$g,$G){$g->query("EXPLAIN PLAN FOR $G");return$g->query("SELECT * FROM plan_table");}function
found_rows(array$S,array$Z){}function
auto_increment(){return"";}function
alter_table($R,$B,array$o,array$se,$f,$vd,$Fb,$La,$rj){$b=$cd=[];$Si=($R?fields($R):[]);foreach($o
as$n){$X=$n[1];if($X&&$n[0]!=""&&idf_escape($n[0])!=$X[0])queries("ALTER TABLE ".table($R)." RENAME COLUMN ".idf_escape($n[0])." TO $X[0]");$Ri=$Si[$n[0]];if($X&&$Ri){$qi=process_field($Ri,$Ri);if(array_slice($X,1)==array_slice($qi,1))continue;if($X[2]==$qi[2])$X[2]="";}if($X){list($X[2],$X[3])=[$X[3],$X[2]];$b[]=($R!=""?($n[0]!=""?"MODIFY (":"ADD ("):"  ").implode($X).($R!=""?")":"");}else$cd[]=idf_escape($n[0]);}$b=array_merge($b,$se);if($R=="")return
queries("CREATE TABLE ".table($B)." (\n".implode(",\n",$b)."\n)");return(!$b||queries("ALTER TABLE ".table($R)."\n".implode("\n",$b)))&&(!$cd||queries("ALTER TABLE ".table($R)." DROP (".implode(", ",$cd).")"))&&($R==$B||queries("ALTER TABLE ".table($R)." RENAME TO ".table($B)));}function
alter_indexes($R,$b){$cd=[];$ik=[];foreach($b
as$X){if($X[0]!="INDEX"){$X[2]=preg_replace('~ DESC$~','',$X[2]);$i=($X[2]=="DROP"?"\nDROP CONSTRAINT ".idf_escape($X[1]):"\nADD".($X[1]!=""?" CONSTRAINT ".idf_escape($X[1]):"")." $X[0] ".($X[0]=="PRIMARY"?"KEY ":"")."(".implode(", ",$X[2]).")");array_unshift($ik,"ALTER TABLE ".table($R).$i);}elseif($X[2]=="DROP")$cd[]=idf_escape($X[1]);else$ik[]="CREATE INDEX ".idf_escape($X[1]!=""?$X[1]:uniqid($R."_"))." ON ".table($R)." (".implode(", ",$X[2]).")";}if($cd)array_unshift($ik,"DROP INDEX ".implode(", ",$cd));foreach($ik
as$G){if(!queries($G))return
false;}return
true;}function
foreign_keys($R){$I=[];$Rm=[];foreach(table_constraints($R)as$B=>$Wb){if($Wb["type"]=="R"){$I[$B]=["source"=>$Wb["columns"],"target"=>[],"on_delete"=>$Wb["delete_rule"],"on_update"=>null,];$Rm[$B]=[$Wb["r_owner"],$Wb["r_constraint"]];}}if($Rm){$Z=[];foreach($Rm
as$Om)$Z[]="(owner = ".q($Om[0])." AND constraint_name = ".q($Om[1]).")";foreach(get_rows("SELECT owner, constraint_name, table_name, column_name FROM all_cons_columns WHERE ".implode(" OR ",array_unique($Z))." ORDER BY position")as$J){foreach($Rm
as$B=>$Om){if($Om==[$J["OWNER"],$J["CONSTRAINT_NAME"]]){$I[$B]["db"]=($J["OWNER"]==DB?"":$J["OWNER"]);$I[$B]["table"]=$J["TABLE_NAME"];$I[$B]["target"][]=$J["COLUMN_NAME"];}}}}return$I;}function
trigger($B,$R){if($B=="")return[];$K=get_rows('SELECT trigger_name "Trigger", trigger_type "Type", triggering_event "Event", trigger_body "Statement"
FROM all_triggers
WHERE trigger_name = '.q($B)." AND ".where_owner());$I=reset($K);if($I){$U=$I["Type"];$I["Timing"]=(preg_match('~^(BEFORE|AFTER|INSTEAD OF)~',$U,$A)?$A[1]:$U);$I["Type"]=(preg_match('~EACH ROW~',$U)||$U=="INSTEAD OF"?"FOR EACH ROW":"");}return($I?:[]);}function
triggers($R){$I=[];foreach(get_rows("SELECT trigger_name, trigger_type, triggering_event FROM all_triggers WHERE table_name = ".q($R)." AND ".where_owner())as$J)$I[$J["TRIGGER_NAME"]]=[preg_replace('~ (STATEMENT|EACH ROW)$~','',$J["TRIGGER_TYPE"]),$J["TRIGGERING_EVENT"]];return$I;}function
trigger_options(){return["Timing"=>["BEFORE","AFTER","INSTEAD OF"],"Event"=>["INSERT","UPDATE","DELETE","INSERT OR UPDATE","INSERT OR DELETE","UPDATE OR DELETE","INSERT OR UPDATE OR DELETE"],"Type"=>["FOR EACH ROW",""],];}function
truncate_tables(array$T){return
apply_queries("TRUNCATE TABLE",$T);}function
drop_views(array$no){return
apply_queries("DROP VIEW",$no);}function
drop_tables(array$T){return
apply_queries("DROP TABLE",$T);}function
last_id($H){return"0";}function
create_database($k,$Fb){$I=queries("CREATE USER ".idf_escape($k)." NO AUTHENTICATION");return($I?queries("GRANT UNLIMITED TABLESPACE TO ".idf_escape($k)):$I);}function
drop_databases(array$j){$I=true;foreach($j
as$k)$I=!!queries("DROP USER ".idf_escape($k)." CASCADE")&&$I;return$I;}function
rename_database($B,$Fb){return!!queries("ALTER USER ".idf_escape(DB)." RENAME TO ".idf_escape($B));}function
show_variables(){return
get_rows('SELECT name, display_value FROM v$parameter');}function
show_status(){$I=[];$K=get_rows('SELECT * FROM v$instance');foreach(reset($K)as$x=>$X)$I[]=[$x,$X];return$I;}function
process_list(){return
get_rows('SELECT
	sess.process AS "process",
	sess.username AS "user",
	sess.schemaname AS "schema",
	sess.status AS "status",
	sess.wait_class AS "wait_class",
	sess.seconds_in_wait AS "seconds_in_wait",
	sql.sql_text AS "sql_text",
	sess.machine AS "machine",
	sess.port AS "port"
FROM v$session sess
LEFT JOIN v$sql sql ON sql.sql_id = sess.sql_id
WHERE sess.type = \'USER\'
ORDER BY PROCESS
');}function
convert_field(array$n){if($n["type"]=="sdo_geometry")return"SDO_UTIL.TO_WKTGEOMETRY(".idf_escape($n["field"]).")";}function
unconvert_field(array$n,$I){return($n["type"]=="sdo_geometry"?"SDO_UTIL.FROM_WKTGEOMETRY($I)":$I);}function
support($Xd){return
preg_match('~^(columns|database|drop_col|fast_status|indexes|descidx|processlist|sql|status|table|trigger|variables|view|view_trigger)$~',$Xd);}}class
Adminer{static$instance;var$error='';function
name(){return"<a href='https://www.adminer.org/'".target_blank()." id='h1'><img src='".h(preg_replace("~\\?.*~","",ME)."?file=logo.svg&version=6.3.1+5c48c0d4")."' width='24' height='24' alt='' id='logo'>Adminer</a>";}function
credentials(){return[SERVER,$_GET["username"],get_password()];}function
connectSsl(){}function
permanentLogin($i=false){return
password_file($i);}function
bruteForceKey(){return$_SERVER["REMOTE_ADDR"];}function
verifyLoginToken(){return
true;}function
serverName($N){return
h($N);}function
database(){return
DB;}function
databases($qe=true){return
get_databases($qe);}function
pluginsLinks(){}function
operators($xm=null){return
driver()->operators($xm);}function
schemas(){$I=schemas();if($_GET["ns"]!=""&&!in_array($_GET["ns"],$I))array_unshift($I,$_GET["ns"]);return$I;}function
queryTimeout(){return
2;}function
afterConnect(){}function
headers(){}function
csp(array$lc){return$lc;}function
verifyVersion(){return
true;}function
donation(){return"<a href='https://www.adminer.org/donation/'".target_blank()." id='donation' title='".'Donate'."'>❤️</a>";}function
serviceWorker(){service_worker();}function
manifest(){$of=$_SERVER["HTTP_HOST"]?:$_SERVER["SERVER_NAME"];$ol=preg_replace('~\?.*~','',ME)?:'.';return['name'=>"Adminer".($of!=""?" - $of":""),'short_name'=>'Adminer','description'=>'Database management in a single PHP file','start_url'=>$ol,'scope'=>$ol,'display'=>'minimal-ui','icons'=>[['src'=>preg_replace("~\\?.*~","",ME)."?file=logo.svg&version=6.3.1+5c48c0d4",'sizes'=>'any','type'=>'image/svg+xml']],];}function
head($qc=null){return
true;}function
bodyClass(){echo" adminer";}function
css(){$I=[];foreach(["","-dark"]as$zh){$p="adminer$zh.css";if(file_exists($p)){$ee=file_get_contents($p);$I["$p?v=".crc32($ee)]=($zh?"dark":(preg_match('~prefers-color-scheme:\s*dark~',$ee)?'':'light'));}}return$I;}function
loginForm(){echo"<table class='layout'>\n",adminer()->loginFormField('driver','<tr><th>'.'System'.'<td>',html_select("auth[driver]",SqlDriver::$drivers,DRIVER,on('change','loginDriver'))),adminer()->loginFormField('server','<tr><th>'.'Server'.'<td>',"<input name='auth[server]' value='".h(SERVER)."' title='".'hostname[:port] or :socket'."' placeholder='localhost' autocapitalize='off'>"),adminer()->loginFormField('username','<tr><th>'.'Username'.'<td>','<input name="auth[username]" id="username" autofocus value="'.h($_GET["username"]).'" autocomplete="username" autocapitalize="off">'.script("fire(qs('#username').form['auth[driver]'], 'change');")),adminer()->loginFormField('password','<tr><th>'.'Password'.'<td>','<input type="password" name="auth[password]" autocomplete="current-password">'),adminer()->loginFormField('db','<tr><th>'.'Database'.'<td>','<input name="auth[db]" value="'.h($_GET["db"]).'" autocapitalize="off">'),"</table>\n","<p><input type='submit' value='".'Login'."'>\n",checkbox("auth[permanent]",1,$_COOKIE["adminer_permanent"],'Permanent login')."\n";}function
loginFormField($B,$ff,$Y){return$ff.$Y."\n";}function
login($Pg,$E){if($E=="")return'Adminer does not support accessing a database without a password.'.require_password_link(null);if(!Driver::$passwords)return'The database does not support passwords.'.require_password_link($E);if(!password_required())return'The server accepts any password, so filling it in protects nothing.'.require_password_link($E);return
true;}function
tableName(array$xm){return
h($xm["Name"]);}function
fieldName(array$n,$Ki=0){$U=$n["full_type"].($n["null"]?" NULL":"");$f=$n["comment"];return'<span title="'.h($U.($f!=""?($U?": ":"").$f:'')).'">'.h($n["field"]).'</span>';}function
commentValue($U,$f){if($f==""||$U=='SCHEMA'||$U=='TABLE'||$U=='COLUMN')return
h($f);$Oj=function($Xk,$kb='td'){return
preg_replace('~^~m','<tr>',preg_replace('~\|~',"<$kb>",preg_replace('~\|$~m',"",rtrim($Xk))));};$R='(\+--[-+]+\+\n)';$J='(\| .* \|\n)';return"<pre>\n".preg_replace_callback("~^$R?$J$R?($J*)$R?~m",function($A)use($Oj){return"<table>\n".($A[1]?"<thead>".$Oj($A[2],'th')."<tbody>\n":$Oj($A[2])).$Oj($A[4])."\n</table>";},preg_replace('~(\n(    -|mysql)&gt; )(.+)~',"\\1<code class='jush-sql'>\\3</code>",preg_replace('~(.+)\n---+\n~',"<b>\\1</b>\n",h($f))))."</pre>\n";}function
commentInput($U,$c,$f){$Y=h($f);return(preg_match('~\n~',$Y)?"<textarea$c rows='2' cols='".($U=='TABLE'?20:30)."' style='vertical-align: bottom;'>\n$Y</textarea>":"<input$c value='$Y'>");}function
selectLinks(array$xm,$O=""){$B=$xm["Name"];echo'<p class="links">';$Kg=[];if($B!="")$Kg["select"]='Select data';if(support("table")||support("indexes"))$Kg["table"]='Show structure';if(support("table")){if(is_view($xm)){if(support("view"))$Kg["view"]='Alter view';}elseif(function_exists('Adminer\alter_table')&&$B!="")$Kg["create"]='Alter table';}if($O!==null)$Kg["edit"]='New item';foreach($Kg
as$x=>$X)echo" <a href='".h(ME)."$x=".url_escape($B).($x=="edit"?$O:"")."'".bold(isset($_GET[$x])).">$X</a>";echo"\n";}function
foreignKeys($R){return
foreign_keys($R);}function
backwardKeys($R,$wm){return[];}function
backwardKeysPrint(array$Ra,array$J){}function
selectQuery($G,$fm,$Vd=false){$I="\n";if(!$Vd&&($ro=driver()->warnings())){$t="warnings";$I=", <a href='#$t' class='toggle'>".'Warnings'."</a>"."$I<div id='$t' class='hidden'>\n$ro</div>\n";}return"<p><code class='sql jush-".JUSH."'>".h(str_replace("\n"," ",$G))."</code> <span class='time'>(".format_time($fm).")</span>".(support("sql")?" <a href='".h(ME)."sql=".url_escape($G)."' class='hover'>".'Edit'."</a>":"").$I;}function
sqlCommandQuery($G){return
shorten_utf8(trim($G),1000);}function
sqlPrintAfter(){}function
sqlUrlLength(){return
2000;}function
explain(Db$g,$G,array$Oi){$H=explain($g,$G);if(!$H)return"";ob_start();print_select_result($H,$g,$Oi);return
ob_get_clean();}function
rowDescription($R){return"";}function
rowDescriptions(array$K,array$te){return$K;}function
selectLink($X,array$n){}function
selectVal($X,$_,array$n,$Vi){$I=($X===null?"<i>NULL</i>":(preg_match("~char|binary|boolean~",$n["type"])&&!preg_match("~var~",$n["type"])?"<code>$X</code>":(preg_match('~^jsonb?$~',$n["full_type"])?"<code class='jush-json'>$X</code>":$X)));if(is_blob($n)&&!is_utf8($X))$I="<i>".lang_format(['%d byte','%d bytes'],strlen($Vi))."</i>";return($_?"<a href='".h($_)."'".(is_url($_)?target_blank():"").">$I</a>":$I);}function
editVal($X,array$n){return$X;}function
config(){return[];}function
tableStructurePrint(array$o,$xm=null){echo"<div class='scrollable'>\n","<table class='nowrap odds'>\n","<thead><tr><th>".'Column'."<th>".'Type'.(support("comment")?"<th>".'Comment':"")."<tbody>\n";$bo=(support("type")?types():[]);foreach($o
as$n){echo"<tr><th>".h($n["field"]).doc_link([JUSH=>$n["doc"]]);$U=h($n["full_type"]);$Fb=h($n["collation"]);echo"<td><span title='$Fb'>".(in_array($U,$bo)?"<a href='".h(ME.'type='.url_escape($U))."'>$U</a>":$U.($Fb&&isset($xm["Collation"])&&$Fb!=$xm["Collation"]?" $Fb":""))."</span>",($n["null"]?" <i>NULL</i>":""),($n["auto_increment"]?" <i>".'Auto Increment'."</i>":""),(isset($n["default"])?" <span title='".'Default value'."'>[<b>".($n["generated"]||$n["default_sql"]?"<code class='sql jush-".JUSH."'>".shorten_utf8(preg_replace('~\s+~',' ',ltrim($n["default"])),80,"</code>"):h($n["default"]))."</b>]</span>":""),(support("comment")?"<td>".adminer()->commentValue('COLUMN',$n["comment"]):""),"\n";}echo"</table>\n","</div>\n";}function
tableIndexesPrint(array$w,array$xm){$lj=false;foreach($w
as$B=>$v)$lj|=!!$v["partial"];echo"<table>\n";$_c=first(driver()->indexAlgorithms($xm));foreach($w
as$B=>$v){ksort($v["columns"]);$Xj=[];foreach($v["columns"]as$x=>$X)$Xj[]="<i>".h($X)."</i>".($v["lengths"][$x]?"(".h($v["lengths"][$x]).")":"").($v["descs"][$x]?" DESC":"");echo"<tr title='".h($B)."'>","<th>".h($v["type"]).($_c&&$v['algorithm']!=$_c?" (".h($v['algorithm']).")":""),"<td>".implode(", ",$Xj).($v["invalid"]?" <span class='error'>INVALID</span>":"");if($lj)echo"<td>".($v['partial']?"<code class='sql jush-".JUSH."'>WHERE ".h($v['partial']):"");echo"\n";}echo"</table>\n";}function
namePattern($U){if($U=="FOREIGN"||$U=="CHECK")return"";if($U=="TRIGGER")return"{table}_{timing}{event}";return(JUSH=="sql"?"":"{table}_")."{columns}";}function
selectColumnInput($c,array$e,$Y="",$Dj=""){return
select_input($c,$e,$Y,$Dj);}function
selectColumnsPrint(array$M,array$e){print_fieldset("select",'Select',$M);$s=0;$M[""]=[];foreach($M
as$x=>$X){$X=idx($_GET["columns"],$x,[]);$d=adminer()->selectColumnInput(" name='columns[$s][col]' data-default=''".on('change',($x!==""?'selectFieldChange':'selectAddRow')),$e,$X["col"]);echo"<div>".(driver()->functions||driver()->grouping?html_select("columns[$s][fun]",[-1=>""]+array_filter(['Functions'=>driver()->functions,'Aggregation'=>driver()->grouping]),$X["fun"]," data-default=''".on('change',($x!==""?'helpClose':'selectFunAddRow')).on_help_value(' (.*)|$','($1)'))."($d)":$d)."</div>\n";$s++;}echo"</div></fieldset>\n";}function
selectSearchPrint(array$Z,array$e,array$w,$xm=null){print_fieldset("search",'Search',$Z);foreach($w
as$s=>$v){if($v["type"]=="FULLTEXT")echo"<div>(<i>".implode("</i>, <i>",array_map('Adminer\h',$v["columns"]))."</i>) ".h(driver()->fulltextOperator)," <input type='search' name='fulltext[".h($s)."]' value='".h(idx($_GET["fulltext"],$s))."' data-default=''".on('input','selectFieldChange').">",(JUSH=='sql'?checkbox("boolean[$s]",1,isset($_GET["boolean"][$s]),"BOOL"):''),"</div>\n";}$Di=adminer()->operators($xm);foreach(array_merge((array)$_GET["where"],[[]])as$s=>$X){$s=number($s);if(!$X||(("$X[col]$X[val]"!=""||preg_match('~NULL$~',$X["op"]))&&in_array($X["op"],$Di)))echo"<div>".adminer()->selectColumnInput(" name='where[$s][col]' data-default=''".on('change',($X?'selectFieldChange':'selectAddRow')),$e,$X["col"],"(".'anywhere'.")"),html_select("where[$s][op]",$Di,$X["op"]," data-default='".h(first($Di))."'".on('change','selectFirstChange')),"<input type='search' name='where[$s][val]' value='".h($X["val"])."' data-default=''".on('input','selectFirstChange').on('keydown','selectSearchKeydown').on('search','selectSearchSearch').">","</div>\n";}echo"</div></fieldset>\n";}function
selectOrderPrint(array$Ki,array$e,array$w){print_fieldset("sort",'Sort',$Ki);$s=0;foreach((array)$_GET["order"]as$x=>$X){if($X!=""){echo"<div>".adminer()->selectColumnInput(" name='order[$s]' data-default=''".on('change','selectFieldChange'),$e,$X),checkbox("desc[$s]",1,isset($_GET["desc"][$x]),'descending')."</div>\n";$s++;}}echo"<div>".adminer()->selectColumnInput(" name='order[$s]' data-default=''".on('change','selectAddRow'),$e),checkbox("desc[$s]",1,false,'descending')."</div>\n","</div></fieldset>\n";}function
selectLimitPrint($z){echo"<fieldset><legend>".'Limit'."</legend><div>","<input type='number' name='limit' class='size' value='".h($z?:"")."' data-default='50'".on('input','selectFieldChange').">","</div></fieldset>\n";}function
selectLengthPrint($Vm){echo"<fieldset><legend>".'Text length'."</legend><div>","<input type='number' name='text_length' class='size' value='".h($Vm)."' data-default='100'>","</div></fieldset>\n";}function
selectActionPrint(array$w){echo"<fieldset><legend>".'Action'."</legend><div>","<input type='submit' value='".'Select'."'>"," <span id='noindex' title='".'Full table scan'."'></span>","<script".nonce().">\n","const indexColumns = ";$e=[];foreach($w
as$v){$pc=reset($v["columns"]);if($v["type"]!="FULLTEXT"&&$pc)$e[$pc]=1;}$e[""]=1;foreach($e
as$x=>$X)json_row($x);echo";\n","selectFieldChange.call(qs('#form')['select']);\n","</script>\n","</div></fieldset>\n";}function
selectCommandPrint(){return!information_schema(DB);}function
selectImportPrint(){return!information_schema(DB);}function
selectEmailPrint(array$qd,array$e){}function
selectColumnsProcess(array$e,array$w){$M=[];$Me=[];foreach((array)$_GET["columns"]as$x=>$X){if($X["fun"]=="count"||($X["col"]!=""&&(!$X["fun"]||in_array($X["fun"],driver()->functions)||in_array($X["fun"],driver()->grouping)))){$M[$x]=apply_sql_function($X["fun"],($X["col"]!=""?idf_escape($X["col"]):"*"));if(!in_array($X["fun"],driver()->grouping))$Me[]=$M[$x];}}return[$M,$Me];}function
selectSearchProcess(array$o,array$w,$xm=null){$I=[];foreach($w
as$s=>$v){if($v["type"]=="FULLTEXT"&&idx($_GET["fulltext"],$s)!="")$I[]=driver()->fulltextSql($s,$v,$_GET["fulltext"][$s],isset($_GET["boolean"][$s]));}$Di=adminer()->operators($xm);foreach((array)$_GET["where"]as$x=>$X){$X+=["col"=>"","op"=>first($Di),"val"=>""];$_GET["where"][$x]=$X;$Db=$X["col"];if(("$Db$X[val]"!=""||preg_match('~NULL$~',$X["op"]))&&in_array($X["op"],$Di)){if($X["op"]=="SQL"&&(!$_POST||!verify_token()))SqlDb::$untrusted=true;$Qb=[];foreach(($Db!=""?[$Db=>idx($o,$Db,[])]:$o)as$B=>$n){if($Db!=""||is_searchable($n,$X))$Qb[]=adminer()->selectSearchCondition(driver()->convertSearch(idf_escape($B),$X,$n),$X["op"],$X["val"]);}$I[]=(count($Qb)==1?$Qb[0]:($Qb?"(".implode(" OR ",$Qb).")":"1 = 0"));}}return$I;}function
selectSearchCondition($d,$_i,$X){if(preg_match('~IN$~',$_i))return"$d $_i ".($X!=""?process_in($X):"(NULL)");if($_i=="BETWEEN")return"$d $_i ".process_between($X);if($_i=="SQL")return"$d $X";if(preg_match('~^(I?LIKE) %%$~',$_i,$A))return"$d $A[1] ".q("%$X%");if($_i=="FIND_IN_SET")return"$_i(".q($X).", $d)";return"$d $_i".(!preg_match('~NULL$~',$_i)?" ".q($X):"");}function
selectOrderProcess(array$o,array$w){$I=[];foreach((array)$_GET["order"]as$x=>$X){if($X!="")$I[]=(preg_match('~^((COUNT\(DISTINCT |[A-Z0-9_]+\()(`(?:[^`\\\\]|``)+`|"(?:[^"\\\\]|"")+")\)|COUNT\(\*\))$~',$X)?$X:idf_escape($X)).(isset($_GET["desc"][$x])?" DESC".(JUSH=='pgsql'&&idx($o[$X],"null")?" NULLS LAST":""):"");}return$I;}function
selectLimitProcess(){return(isset($_GET["limit"])?intval($_GET["limit"]):50);}function
selectLengthProcess(){return(isset($_GET["text_length"])?"$_GET[text_length]":"100");}function
selectEmailProcess(array$Z,array$te){return
false;}function
selectQueryBuild(array$M,array$Z,array$Me,array$Ki,$z,$D){return"";}function
executeQuery($G,$jb){return$jb();}function
messageQuery($G,$Xm,$Vd=false){restart_session();$lf=&get_session("queries");if(!idx($lf,$_GET["db"]))$lf[$_GET["db"]]=[];if(strlen($G)>1e6)$G=preg_replace('~[\x80-\xFF]+$~','',substr($G,0,1e6))."\n…";$lf[$_GET["db"]][]=[$G,time(),$Xm];$Yl="sql-".count($lf[$_GET["db"]]);$I="<a href='#$Yl' class='toggle'>".'SQL command'."</a> ".copy_icon()."\n";if(!$Vd&&($ro=driver()->warnings())){$t="warnings-".count($lf[$_GET["db"]]);$I="<a href='#$t' class='toggle'>".'Warnings'."</a>, $I<div id='$t' class='hidden'>\n$ro</div>\n";}return" <span class='time'>".@date("H:i:s")."</span>"." $I<div id='$Yl' class='hidden'><pre><code class='jush-".JUSH."'>".shorten_utf8($G,1e4)."</code></pre>".($Xm?" <span class='time'>($Xm)</span>":'').(support("sql")?'<p><a href="'.h(str_replace("db=".url_escape(DB),"db=".url_escape($_GET["db"]),ME).'sql=&history='.(count($lf[$_GET["db"]])-1)).'">'.'Edit'.'</a>':'').'</div>';}function
error(){return
error();}function
editRowPrint($R,array$o,$J,$Nn,$G='',$Xm=''){echo($G!=""?"<p><code class='sql jush-".JUSH."'>".h(str_replace("\n"," ",$G))."</code> <span class='time'>($Xm)</span>\n":"");}function
editFunctions(array$n){$I=($n["null"]?"NULL/":"");$bf=isset($_GET["select"])||where($_GET);foreach([driver()->insertFunctions,driver()->editFunctions]as$x=>$Ge){if(!$x||(!isset($_GET["call"])&&$bf)){foreach($Ge
as$_j=>$X){if(!$_j||preg_match("~$_j~",$n["type"]))$I
.="/$X";}}if($x&&$Ge&&!preg_match('~set|bool~',$n["type"])&&!is_blob($n))$I
.="/SQL";}if($n["auto_increment"]&&!$bf)$I='Auto Increment';return
explode("/",$I);}function
editInput($R,array$n,$c,$Y){if($n["type"]=="enum")return(isset($_GET["select"])?"<label><input type='radio'$c value='orig' checked><i>".'original'."</i></label> ":"").enum_input("radio",$c,$n,$Y,"NULL");return"";}function
editHint($R,array$n,$Y){return"";}function
processInput(array$n,$Y,$r=""){if($r=="SQL")return$Y;$B=$n["field"];$I=q($Y);if(preg_match('~^(now|getdate|uuid)$~',$r))$I="$r()";elseif(preg_match('~^current_(date|timestamp)$~',$r))$I=$r;elseif(preg_match('~^([+-]|\|\|)$~',$r))$I=idf_escape($B)." $r $I";elseif(preg_match('~^[+-] interval$~',$r))$I=idf_escape($B)." $r ".(preg_match("~^(\\d+|'[0-9.: -]') [A-Z_]+\$~i",$Y)&&JUSH!="pgsql"?$Y:$I);elseif(preg_match('~^(addtime|subtime|concat)$~',$r))$I="$r(".idf_escape($B).", $I)";elseif(preg_match('~^(md5|sha1|password|encrypt)$~',$r))$I="$r($I)";return
unconvert_field($n,$I);}function
dumpOutput(){$I=['text'=>'open','file'=>'save'];if(function_exists('gzencode'))$I['gz']='gzip';return$I;}function
dumpFormat(){return(support("dump")?['sql'=>'SQL']:[])+['csv'=>'CSV,','csv;'=>'CSV;','tsv'=>'TSV'];}function
dumpPrint(){}function
dumpDatabase($k){}function
dumpTable($R,$nm,$fg=0){if($_POST["format"]!="sql"){echo"\xef\xbb\xbf";if($nm)dump_csv(array_keys(fields($R)));}else{if($fg==2){$o=[];foreach(fields($R)as$B=>$n)$o[]=idf_escape($B)." ".full_type_sql($n);$i="CREATE TABLE ".table($R)." (".implode(", ",$o).")";}else$i=create_sql($R,$_POST["auto_increment"],$nm);set_utf8mb4($i);if($nm&&$i){if(($nm=="DROP+CREATE"&&!function_exists('Adminer\drop_sql'))||$fg==1)echo"DROP ".($fg==2?"VIEW":"TABLE")." IF EXISTS ".table($R).";\n";if($fg==1)$i=remove_definer($i);echo"$i;\n\n";}}}function
dumpData($R,$nm,$G,array$M=[],array$Z=[],array$Me=[],array$Ki=[]){if($nm){$ah=(JUSH=="sqlite"?0:1048576);$o=[];$uf=false;if($_POST["format"]=="sql"){if($nm=="TRUNCATE+INSERT"&&!function_exists('Adminer\truncate_all_sql'))echo
truncate_sql($R).";\n";$o=fields($R);if(JUSH=="mssql"){foreach($o
as$n){if($n["auto_increment"]){echo"SET IDENTITY_INSERT ".table($R)." ON;\n";$uf=true;break;}}}}$H=($G!=""?connection()->query($G,1):driver()->select($R,($M?:["*"]),$Z,$Me,$Ki,0));if($H){$Qf="";$cb="";$og=[];$He=[];$aj="";$pm="";$Zd=($R!=''?'fetch_assoc':'fetch_row');$hc=0;while($J=$H->$Zd()){if(!$og){$io=[];foreach($J
as$X){$n=$H->fetch_field();if(idx($o[$n->name],'generated')){$He[$n->name]=true;continue;}$og[]=$n->name;if(JUSH=="pgsql"&&isset($o[$n->name])&&is_identity_always($o[$n->name]))$aj=" OVERRIDING SYSTEM VALUE";$x=idf_escape($n->name);$io[]="$x = VALUES($x)";}$pm=($nm=="INSERT+UPDATE"?"\nON DUPLICATE KEY UPDATE ".implode(", ",$io):"").";\n";}foreach($J
as$x=>$X){if(is_array($X))$J[$x]=json_encode($X,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);}if($_POST["format"]!="sql"){if($nm=="table"){dump_csv($og);$nm="INSERT";}dump_csv($J);}else{if(!$Qf)$Qf="INSERT INTO ".table($R)." (".implode(", ",array_map('Adminer\idf_escape',$og)).")$aj VALUES";foreach($J
as$x=>$X){if($He[$x]){unset($J[$x]);continue;}$n=$o[$x];$J[$x]=($X===null?"NULL":($X===false?0:unconvert_field($n,preg_match(number_type(),$n["type"])&&!preg_match('~\[~',$n["full_type"])&&is_numeric($X)?$X:(!is_blob($n)||is_utf8($X)?q($X):driver()->quoteBinary($X)))));}$Xk=($ah?"\n":" ")."(".implode(",\t",$J).")";if(!$cb)$cb=$Qf.$Xk;elseif(JUSH=='mssql'?$hc%1000!=0:strlen($cb)+4+strlen($Xk)+strlen($pm)<$ah)$cb
.=",$Xk";else{echo$cb.$pm;$cb=$Qf.$Xk;}}$hc++;}if($cb)echo$cb.$pm;}elseif($_POST["format"]=="sql")echo"-- ".str_replace("\n"," ",connection()->error)."\n";else
dump_csv([connection()->error]);if($uf)echo"SET IDENTITY_INSERT ".table($R)." OFF;\n";}}function
dumpFilename($sf){return
friendly_url($sf!=""?$sf:(SERVER?:"localhost"));}function
dumpHeaders($sf,$Fh=false){$Zi=$_POST["output"];$Qd=(preg_match('~sql~',$_POST["format"])?"sql":($Fh?"tar":"csv"));header("Content-Type: ".($Zi=="gz"?"application/x-gzip":($Qd=="tar"?"application/x-tar":($Qd=="sql"||$Zi!="file"?"text/plain":"text/csv")."; charset=utf-8")));if($Zi=="gz"){ob_start(function($Q){return
gzencode($Q);},1e6);}return$Qd;}function
dumpFooter(){if($_POST["format"]=="sql")echo"-- ".gmdate("Y-m-d H:i:s e")."\n";}function
importServerPath(){return"adminer.sql";}function
importPrint(){}function
importProcess(){return
false;}function
homepage(){echo'<p class="links">'.($_GET["ns"]==""&&support("database")?'<a href="'.h(ME).'database=">'.'Alter database'."</a>\n":""),(support("scheme")?"<a href='".h(ME)."scheme='>".($_GET["ns"]!=""?'Alter schema':'Create schema')."</a>\n":""),($_GET["ns"]!==""?'<a href="'.h(ME).'schema=">'.'Database schema'."</a>\n":""),(support("privileges")?"<a href='".h(ME)."privileges='>".'Privileges'."</a>\n":"");if($_GET["ns"]!=="")echo(support("routine")?"<a href='#routines'>".'Routines'."</a>\n":""),(support("sequence")?"<a href='#sequences'>".'Sequences'."</a>\n":""),(support("type")?"<a href='#user-types'>".'User types'."</a>\n":""),(support("event")?"<a href='#events'>".'Events'."</a>\n":"");return
true;}function
navigation($yh){echo"<h1>".adminer()->name()." <span class='version'>".VERSION;$Zh=$_COOKIE["adminer_version"];echo" <a href='https://www.adminer.org/#download'".target_blank()." id='version'>".(version_compare(VERSION,$Zh)<0?h($Zh):"").version_iframe()."</a>","</span></h1>\n";if($yh=="auth"){$Zi="";foreach((array)$_SESSION["pwds"]as$ko=>$Dl){foreach($Dl
as$N=>$co){$B=h(get_setting("vendor-$ko-$N")?:get_driver($ko));foreach($co
as$V=>$E){if($B&&$E!==null){$yc=$_SESSION["db"][$ko][$N][$V];foreach(($yc?array_keys($yc):[""])as$k)$Zi
.="<li><a href='".h(auth_url($ko,$N,$V,$k))."'>($B) ".h("$V@").($N!=""?adminer()->serverName($N):"").h($k!=""?" - $k":"")."</a>\n";}}}}if($Zi)echo"<ul id='logins'".on('mouseover','menuOver').on('mouseout','menuOut').">\n$Zi</ul>\n";}else{$T=[];if($_POST&&!$yh&&DB!=""){connection()->select_db(DB);if(support("scheme")&&$_GET["ns"]!="")set_schema($_GET["ns"]);}if($_GET["ns"]!==""&&!$yh&&DB!="")$T=table_status('',true);adminer()->syntaxHighlighting($T);adminer()->databasesPrint($yh);$ja=[];if(DB==""||!$yh){if(support("sql")){$ja['sql']="<a href='".h(ME)."sql='".bold(isset($_GET["sql"])&&!isset($_GET["import"])).">".'SQL command'."</a>";$ja['import']="<a href='".h(ME)."import='".bold(isset($_GET["import"])).">".'Import'."</a>";}$ja['dump']="<a href='".h(ME)."dump=".url_escape(isset($_GET["table"])?$_GET["table"]:$_GET["select"])."' id='dump'".bold(isset($_GET["dump"])).">".'Export'."</a>";}$_f=$_GET["ns"]!==""&&!$yh&&DB!="";if($_f&&function_exists('Adminer\alter_table'))$ja['create']='<a href="'.h(ME).'create="'.bold($_GET["create"]==="").">".'Create table'."</a>";$ja=adminer()->menuActions($ja,$yh);echo($ja?"<p class='links'>\n".implode("\n",$ja)."\n":"");$hl=[];if($_f){if(function_exists('Adminer\alter_table'))$hl[]=['Create table',ME."create="];if(support("view"))$hl[]=['Create view',ME."view="];$hl[]=['Database schema',ME."schema="];$Yd=['routine'=>['routines','Routines'],'sequence'=>['sequences','Sequences'],'type'=>['user-types','User types'],'event'=>['events','Events'],];foreach($Yd
as$Xd=>$gl){if(support($Xd))$hl[]=[$gl[1],substr(ME,0,-1)."#$gl[0]"];}}if(DB!=""&&!$yh&&support("extension"))$hl[]=['Extensions',preg_replace('~&ns=[^&]*~','',ME)."ns=#extensions"];$Cl=['privileges'=>'Privileges','processlist'=>'Process list','variables'=>'Variables','status'=>'Status',];foreach($Cl
as$x=>$X){if(support($x))$hl[]=[$X,preg_replace('~&(db|ns)=[^&]*~','',ME)."$x="];}echo($hl?script("jumpSections.unshift(...".json_encode($hl,JSON_UNESCAPED_UNICODE).");"):"");if($_f){if($T)adminer()->tablesPrint($T);else
echo"<p class='message'>".'No tables.'."</p>\n";}}}function
syntaxHighlighting(array$T){echo
script_src(preg_replace("~\\?.*~","",ME)."?file=jush.js&version=6.3.1+5c48c0d4",true);$Ah=preg_replace('~<(?=/script)~i','<\\',Driver::jushModule());echo($Ah?script("addEventListener('DOMContentLoaded', () => {\n$Ah\n});"):"");if(support("sql")){echo"<script".nonce().">\n";if($T){$Kg=[];foreach($T
as$R=>$U)$Kg[]=js_escape_re($R);echo"var jushLinks = { ".JUSH.":";json_row(js_escape(ME).(support("table")?"table":"select").'=$&','/\b(?<!\$)('.implode('|',$Kg).')(?!\$)\b/g',false);$am=["sql","check","event","procedure","trigger","view","type","table","processlist"];if(support("routine")&&array_intersect_key($_GET,array_flip($am))){foreach(routines()as$J)json_row(js_escape(ME).'function='.url_escape($J["SPECIFIC_NAME"]).'&name=$&','/\b'.js_escape_re($J["ROUTINE_NAME"]).'(?=["`\]]?\()/g',false);}json_row('');echo"};\n";foreach(["bac","bra","sqlite_quo","mssql_bra"]as$X)echo"jushLinks.$X = jushLinks.".JUSH.";\n";if(array_intersect_key($_GET,array_flip(["sql","check","event","procedure","trigger","view"]))){$im=(isset($_GET["trigger"])?['INSERT INTO','UPDATE','DELETE FROM']:(isset($_GET["check"])?[]:(isset($_GET["view"])?['SELECT']:null)));$Na=Driver::jushAutocomplete($T,$im);echo($Na?"addEventListener('DOMContentLoaded', () => { autocompleter = $Na; });\n":"");}}echo"</script>\n";}echo
script("syntaxHighlighting('".doc_version()."', '".connection()->flavor."');");}function
databasesPrint($yh){if(support("single_db"))return;$j=adminer()->databases();if(DB&&$j&&!in_array(DB,$j))array_unshift($j,DB);echo"<form>\n<p id='dbs'>\n";hidden_fields_get();$vc=on('mousedown','dbMouseDown').on('change','dbChange');echo"<label title='".'Database'."'>".'DB'.": ".($j?html_select("db",[""=>""]+group_system($j),DB,$vc):"<input name='db' value='".h(DB)."' autocapitalize='off' size='19'>\n")."</label>","<input type='submit' value='".'Use'."'".($j?" class='hidden'":"").">\n";if(support("scheme")){if($yh!="db"&&DB!="")echo"<br><label>".'Schema'.": ".html_select("ns",[""=>""]+group_system(adminer()->schemas(),true),$_GET["ns"],$vc)."</label>";}foreach(["import","sql","schema","dump","privileges"]as$X){if(isset($_GET[$X])){echo
input_hidden($X);break;}}echo"</p></form>\n";}function
menuActions(array$ja,$yh){return$ja;}function
tablesPrint(array$T){echo"<ul id='tables'".on('mouseover','menuOver').on('mouseout','menuOut').">";foreach($T
as$R=>$P){$R="$R";$B=adminer()->tableName($P);if($B!=""&&!$P["dependent"])echo'<li><a href="'.h(ME).'select='.url_escape($R).'"'.bold($_GET["select"]==$R||$_GET["edit"]==$R,"select hover")." title='".'Select data'."'>".'select'."</a> ",(support("table")||support("indexes")?'<a href="'.h(ME).'table='.url_escape($R).'"'.bold(in_array($R,[$_GET["table"],$_GET["create"],$_GET["indexes"],$_GET["foreign"],$_GET["trigger"],$_GET["check"],$_GET["view"]]),(is_view($P)?"view":"structure"))." title='".'Show structure'."'>$B</a>":"<span>$B</span>")."\n";}echo"</ul>\n";}function
showVariables(){return
show_variables();}function
showStatus(){return
show_status();}function
processList(){return
process_list();}function
killProcess($t){return
kill_process($t);}function
formatSizeValue($X){return
format_number($X);}}class
Plugins{private
static$append=['dumpFormat'=>true,'dumpOutput'=>true,'editRowPrint'=>true,'editFunctions'=>true,'config'=>true];var$plugins;var$disabled=[];var$drivers=[];var$driverFiles=[];var$error='';private$hooks=[];function
__construct($Hj){$bd=SqlDriver::$drivers;$hf=" href='https://www.adminer.org/plugins/#use'".target_blank();if($Hj===null){$Hj=[];$Va="adminer-plugins";if(is_dir($Va)){foreach(glob("$Va/*.php")as$p){$fe=SqlDriver::$drivers;$this->includeOnce($p);foreach(array_diff_key(SqlDriver::$drivers,$fe)as$t=>$B)$this->driverFiles[$t]=$p;}}if(file_exists("$Va.php")){$Bf=$this->includeOnce("$Va.php");if(is_array($Bf)){foreach($Bf
as$x=>$Ej)$Hj[is_object($Ej)?get_class($Ej):$x]=$Ej;}else$this->error
.=sprintf('%s must <a%s>return an array</a>.',"<b>$Va.php</b>",$hf)."<br>";}foreach(get_declared_classes()as$_b){if(!$Hj[$_b]&&(preg_match('~^Adminer\w~i',$_b)||is_subclass_of($_b,'Adminer\Plugin'))){$wk=new
\ReflectionClass($_b);$Yb=$wk->getConstructor();if($Yb&&$Yb->getNumberOfRequiredParameters())$this->error
.=sprintf('<a%s>Configure</a> %s in %s.',$hf,"<b>$_b</b>","<b>$Va.php</b>")."<br>";else$Hj[$_b]=new$_b;}}}$Vf=array_filter($Hj,function($Ej){return!is_object($Ej);});if($Vf){$this->error
.=sprintf('Every plugin must <a%s>be an object</a>.',$hf)."<br>";$Hj=array_diff_key($Hj,$Vf);}$tm=get_setting("plugins","adminer_config");foreach($Hj
as$x=>$Ej){if($Ej
instanceof
Plugin&&$Ej::$optional){$gm=idx($tm,get_class($Ej));if($gm!==null?!$gm:!$Ej->enabled){$this->disabled[]=$Ej;unset($Hj[$x]);}}}$this->drivers=array_diff_key(SqlDriver::$drivers,$bd);$this->plugins=$Hj;$oa=new
Adminer;$Hj[]=$oa;$wk=new
\ReflectionObject($oa);foreach($wk->getMethods()as$vh){foreach($Hj
as$Ej){$B=$vh->getName();if(method_exists($Ej,$B))$this->hooks[$B][]=$Ej;}}}function
includeOnce($p){return
include_once"./$p";}static
function
pluginName($Ej){return
preg_replace('~^ Adminer\\\\? ~','',preg_replace('~[A-Z]~',' $0',get_class($Ej)));}static
function
describe($Ej){$Jc=(method_exists($Ej,'description')?$Ej->description():"");if(!$Jc){$wk=new
\ReflectionObject($Ej);if(preg_match('~^/[\s*]+(.+?)(?:\s*\*/)?$~m',$wk->getDocComment(),$A))$Jc=$A[1];}$cl=(method_exists($Ej,'screenshot')?$Ej->screenshot():"");return
h($Jc).($cl?($Jc?" ":"")."(<a href='".h($cl)."'".target_blank().">".'screenshot'."</a>)":"");}static
function
checksum($p){$ee=str_replace("\r","",file_get_contents($p));$ee=preg_replace('~\n\tprotected \$translations = array\(.*?\n\t\);~s','',$ee);return
dechex(crc32($ee));}function
checksums(){$ge=array_values($this->driverFiles);foreach($this->plugins
as$Ej){$wk=new
\ReflectionObject($Ej);$ge[]=$wk->getFileName();}$I=[];foreach($ge
as$p)$I[basename($p,'.php')]=self::checksum($p);return$I;}static
function
officialChecksums(){return['adminer.js'=>'a0599090','backward-keys'=>'1c017d59','before-unload'=>'9674d220','config'=>'bd5d79e5','dark-switcher'=>'8c201319','database-hide'=>'e304a899','designs'=>'c0a56b18','donation'=>'365aa745','dump-alter'=>'896b579e','dump-bz2'=>'c0d6de78','dump-date'=>'512aad63','dump-json'=>'f8aa6b99','dump-xml'=>'aedd2d3a','dump-zip'=>'96882e78','edit-foreign'=>'d04a0ac8','edit-textarea'=>'a178d7d9','editor-setup'=>'a7dc3a37','editor-views'=>'5c12b185','enum-option'=>'e9005cb0','file-upload'=>'e7f2b388','foreign-system'=>'634ce119','frames'=>'b0e1d11a','highlight-codemirror'=>'ada4b569','highlight-monaco'=>'95cf93b3','highlight-prism'=>'19933b19','import-csv'=>'bcecfad2','login-ip'=>'4d174fea','login-otp'=>'66bd013d','login-passkey'=>'a73c386f','login-password-less'=>'e150daac','login-reverse-proxy'=>'24558ea2','login-servers'=>'19c42e45','login-ssl'=>'2b06a5b7','login-table'=>'811f8cef','menu-links'=>'55b9b15c','name-patterns'=>'84c10d09','remote-color'=>'c5e74631','row-numbers'=>'1ad1b6cf','select-email'=>'c33a2661','select-expression'=>'b6a4b3f1','select-foreign'=>'2c6bb6c3','select-image'=>'19dd2499','slugify'=>'7b422f0e','sql-gemini'=>'d65d01df','sql-log'=>'b9aa4ea6','sql-url-length'=>'cd70f1a0','table-indexes-structure'=>'e0b1433','table-structure'=>'f71ec61b','tables-filter'=>'2729bf7','timeout'=>'9e9bce60','version-github'=>'627cadf9','version-noverify'=>'966937e9','clickhouse'=>'79f6dd80','elastic'=>'b7e4a70d','firebird'=>'5f3d9493','igdb'=>'39c028f6','imap'=>'35808394','mongo'=>'2d9cae00','redis'=>'c2f04eae','simpledb'=>'9eb464cf',];}function
__call($B,array$hj){$Da=[];foreach($hj
as$x=>$X)$Da[]=&$hj[$x];$I=null;foreach($this->hooks[$B]as$Ej){$Y=call_user_func_array([$Ej,$B],$Da);if($Y!==null){if(!self::$append[$B])return$Y;$I=$Y+(array)$I;}}return$I;}}abstract
class
Plugin{static$optional=false;var$enabled=true;protected$translations=[];function
disable(){$this->enabled=false;return$this;}function
description(){return$this->lang('');}function
screenshot(){return"";}protected
function
lang($u,$hi=null){$Da=func_get_args();$Da[0]=idx($this->translations[LANG],$u)?:$u;return
call_user_func_array('Adminer\lang_format',$Da);}}class
Password{private$password_hash;private$password_matches=null;function
__construct($wj){$this->password_hash=$wj;}function
description(){return'Require a password verified by Adminer';}function
credentials(){$E=get_password();return[SERVER,$_GET["username"],($this->passwordMatches($E)&&!password_required()?"":$E)];}function
login($Pg,$E){if($this->passwordMatches($E))return
true;if(!Driver::$passwords||!password_required())return
false;}protected
function
passwordMatches($E){if($this->password_matches===null)$this->password_matches=(function_exists('password_verify')&&password_verify(strval($E),$this->password_hash));return$this->password_matches;}}Adminer::$instance=(function_exists('adminer_object')?adminer_object():(is_dir("adminer-plugins")||file_exists("adminer-plugins.php")?new
Plugins(null):new
Adminer));SqlDriver::$drivers=["server"=>"MySQL / MariaDB"]+SqlDriver::$drivers;if(!defined('Adminer\DRIVER')){define('Adminer\DRIVER',"server");if(extension_loaded("mysqli")&&$_GET["ext"]!="pdo"){class
Db
extends
\mysqli{static$instance;var$extension="MySQLi",$flavor='';function
__construct(){parent::init();}function
attach(array$N,$V,$E){mysqli_report(MYSQLI_REPORT_OFF);$Ij=$N["port"];$sd=("$N[host]$Ij$N[socket]"=="");$em=adminer()->connectSsl();$Xn=($em&&($em['key']||$em['cert']||$em['ca']||isset($em['verify'])));if($Xn)$this->ssl_set($em['key'],$em['cert'],$em['ca'],'','');$I=@$this->real_connect((!$sd?$N["host"]:ini_get("mysqli.default_host")),(!$sd||$V!=""?$V:ini_get("mysqli.default_user")),(!$sd||$V.$E!=""?$E:ini_get("mysqli.default_pw")),null,($Ij!=""?intval($Ij):ini_get("mysqli.default_port")),($Ij!=""?null:$N["socket"]),($Xn?($em['verify']!==false?MYSQLI_CLIENT_SSL:64):0));$this->options(MYSQLI_OPT_LOCAL_INFILE,0);return($I?'':$this->error);}function
set_charset($pb){if($this->send_charset($pb))return
true;$this->send_charset('utf8');return$this->query("SET NAMES $pb");}private
function
send_charset($pb){return
adminer()->executeQuery("SET NAMES $pb",function()use($pb){return
parent::set_charset($pb);});}function
select_db($uc){return
adminer()->executeQuery("USE ".idf_escape($uc),function()use($uc){return
parent::select_db($uc);});}function
query($G,$Ik=MYSQLI_STORE_RESULT){return
adminer()->executeQuery($G,function()use($G,$Ik){return
parent::query($G,$Ik);});}function
multi_query($G){return
adminer()->executeQuery($G,function()use($G){return
parent::multi_query($G);});}function
next_result(){return
self::more_results()&&parent::next_result();}function
quote($Q){return"'".$this->escape_string($Q)."'";}function
inTransaction(){return
false;}function
begin(){return(method_exists($this,'begin_transaction')?adminer()->executeQuery("BEGIN",function(){return$this->begin_transaction();}):!!$this->query("BEGIN"));}function
commit($oe=0,$B=null){return
adminer()->executeQuery("COMMIT",function(){return
parent::commit();});}function
rollback($oe=0,$B=null){return
adminer()->executeQuery("ROLLBACK",function(){return
parent::rollback();});}}}elseif(extension_loaded("mysql")&&!((ini_bool("sql.safe_mode")||ini_bool("mysql.allow_local_infile"))&&extension_loaded("pdo_mysql"))){class
Db
extends
SqlDb{private$link;function
attach(array$N,$V,$E){if(ini_bool("mysql.allow_local_infile"))return
sprintf('Disable %s or enable the %s or %s extension.',"'mysql.allow_local_infile'","MySQLi","PDO_MySQL");$Ij="$N[port]$N[socket]";$B=$N["host"].($Ij!=""?":$Ij":"");$this->link=@mysql_connect(($B!=""?$B:ini_get("mysql.default_host")),($B.$V!=""?$V:ini_get("mysql.default_user")),($B.$V.$E!=""?$E:ini_get("mysql.default_password")),true,131072);if(!$this->link)return
mysql_error();$this->server_info=mysql_get_server_info($this->link);return'';}function
set_charset($pb){return$this->send_charset($pb)||$this->send_charset('utf8');}private
function
send_charset($pb){return
adminer()->executeQuery("SET NAMES $pb",function()use($pb){return
mysql_set_charset($pb,$this->link);});}function
quote($Q){return"'".mysql_real_escape_string($Q,$this->link)."'";}function
select_db($uc){return
adminer()->executeQuery("USE ".idf_escape($uc),function()use($uc){return
mysql_select_db($uc,$this->link);});}function
query($G,$Cn=false){$H=adminer()->executeQuery($G,function()use($G,$Cn){return@($Cn?mysql_unbuffered_query($G,$this->link):mysql_query($G,$this->link));});$this->error="";if(!$H){$this->errno=mysql_errno($this->link);$this->error=mysql_error($this->link);return
false;}if($H===true){$this->affected_rows=mysql_affected_rows($this->link);$this->info=mysql_info($this->link);return
true;}return
new
Result($H);}}class
Result{var$num_rows;private$result;private$offset=0;function
__construct($H){$this->result=$H;$this->num_rows=mysql_num_rows($H);}function
fetch_assoc(){return
mysql_fetch_assoc($this->result);}function
fetch_row(){return
mysql_fetch_row($this->result);}function
fetch_field(){$I=mysql_fetch_field($this->result,$this->offset++);$I->orgtable=$I->table;$I->native_type=idx(["string"=>"varchar","real"=>"double"],$I->type,$I->type);return$I;}}}elseif(extension_loaded("pdo_mysql")){class
Db
extends
PdoDb{var$extension="PDO_MySQL";function
attach(array$N,$V,$E){$Hi=[\PDO::MYSQL_ATTR_LOCAL_INFILE=>false];if(isset($_GET["select"]))$Hi[1012]=false;$em=adminer()->connectSsl();if($em){if($em['key'])$Hi[\PDO::MYSQL_ATTR_SSL_KEY]=$em['key'];if($em['cert'])$Hi[\PDO::MYSQL_ATTR_SSL_CERT]=$em['cert'];if($em['ca'])$Hi[\PDO::MYSQL_ATTR_SSL_CA]=$em['ca'];if(isset($em['verify']))$Hi[\PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT]=$em['verify'];}$of=$N["host"];$Ij=$N["port"];$Nl=$N["socket"];return$this->dsn("mysql:charset=utf8".($of!=""?";host=$of":'').($Ij!=""?";port=$Ij":($Nl!=""?";unix_socket=$Nl":"")),$V,$E,$Hi);}function
set_charset($pb){return$this->query("SET NAMES $pb");}function
select_db($uc){return$this->query("USE ".idf_escape($uc));}function
query($G,$Cn=false){$this->pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY,!$Cn);return
parent::query($G,$Cn);}}}class
Driver
extends
SqlDriver{static$extensions=["MySQLi","MySQL","PDO_MySQL"];static$jush="sql";static$serverSocket=true;var$unsigned=["unsigned","zerofill","unsigned zerofill"];var$functions=["char_length","date","from_unixtime","lower","round","floor","ceil","sec_to_time","time_to_sec","upper"];var$grouping=["avg","count","count distinct","group_concat","max","min","sum"];var$partitionBy=["HASH","LINEAR HASH","KEY","LINEAR KEY","RANGE","LIST"];function
operators($xm){return["=","<",">","<=",">=","!=","&","LIKE","LIKE %%","REGEXP","IN","BETWEEN","FIND_IN_SET","IS NULL","NOT LIKE","NOT REGEXP","NOT IN","IS NOT NULL","SQL"];}static
function
connect($N,$V,$E){$g=parent::connect($N,$V,$E);if(is_string($g)){if(function_exists('iconv')&&!is_utf8($g)&&strlen($Xk=iconv("windows-1252","utf-8//IGNORE",$g))>strlen($g))$g=$Xk;return$g;}$g->set_charset(charset($g));$g->query("SET sql_quote_show_create = 1, autocommit = 1");$g->flavor=(preg_match('~MariaDB~',$g->server_info)?'maria':'mysql');add_driver(DRIVER,($g->flavor=='maria'?"MariaDB":"MySQL"));return$g;}function
__construct(Db$g){parent::__construct($g);$this->types=['Numbers'=>["tinyint"=>3,"smallint"=>5,"mediumint"=>8,"int"=>10,"bigint"=>20,"decimal"=>66,"float"=>12,"double"=>21],'Date and time'=>["date"=>10,"datetime"=>19,"timestamp"=>19,"time"=>10,"year"=>4],'Strings'=>["char"=>255,"varchar"=>65535,"tinytext"=>255,"text"=>65535,"mediumtext"=>16777215,"longtext"=>4294967295],'Lists'=>["enum"=>65535,"set"=>64],'Binary'=>["bit"=>20,"binary"=>255,"varbinary"=>65535,"tinyblob"=>255,"blob"=>65535,"mediumblob"=>16777215,"longblob"=>4294967295],'Geometry'=>["geometry"=>0,"point"=>0,"linestring"=>0,"polygon"=>0,"multipoint"=>0,"multilinestring"=>0,"multipolygon"=>0,"geometrycollection"=>0],];$this->insertFunctions=["char"=>"md5/sha1/password/encrypt/uuid","binary"=>"md5/sha1","date|time"=>"now",];$this->editFunctions=[number_type()=>"+/-","date"=>"+ interval/- interval","time"=>"addtime/subtime","char|text"=>"concat",];if(min_version('5.7.8',10.2,$g))$this->types['Strings']["json"]=4294967295;if(min_version('',10.7,$g)){$this->types['Strings']["uuid"]=128;$this->insertFunctions['uuid']='uuid';}if(min_version('',10.5,$g)){$this->types['Network']["inet6"]=39;if(min_version('','10.10',$g))$this->types['Network']["inet4"]=15;}if(min_version(9,11.7,$g))$this->types['Numbers']["vector"]=16383;if(min_version(5.7,10.2,$g))$this->generated=["STORED","VIRTUAL"];}function
unconvertFunction(array$n){return(preg_match("~binary~",$n["type"])?"<code class='jush-sql'>UNHEX</code>":($n["type"]=="bit"?doc_link(['sql'=>'bit-value-literals.html'],"<code>b''</code>"):($n["type"]=="vector"?"<code class='jush-sql'>".($this->conn->flavor=='maria'?"VEC_FromText":"STRING_TO_VECTOR")."</code>":(preg_match("~geom|point|linestring|polygon~",$n["type"])?"<code class='jush-sql'>GeomFromText</code>":""))));}function
insert($R,array$O){return($O?parent::insert($R,$O):queries("INSERT INTO ".table($R)." ()\nVALUES ()"));}function
insertUpdate($R,array$K,array$Vj){$e=array_keys(reset($K));$Qj="INSERT INTO ".table($R)." (".implode(", ",$e).") VALUES\n";$io=[];foreach($e
as$x)$io[$x]="$x = VALUES($x)";$pm="\nON DUPLICATE KEY UPDATE ".implode(", ",$io);$io=[];$y=0;foreach($K
as$O){$Y="(".implode(", ",$O).")";if($io&&(strlen($Qj)+$y+strlen($Y)+strlen($pm)>1e6)){if(!queries($Qj.implode(",\n",$io).$pm))return
false;$io=[];$y=0;}$io[]=$Y;$y+=strlen($Y)+2;}return
queries($Qj.implode(",\n",$io).$pm);}function
slowQuery($G,$Ym){if(min_version('5.7.8','10.1.2')){if($this->conn->flavor=='maria')return"SET STATEMENT max_statement_time=$Ym FOR $G";elseif(preg_match('~^(SELECT\b)(.+)~is',$G,$A))return"$A[1] /*+ MAX_EXECUTION_TIME(".($Ym*1000).") */ $A[2]";}}function
convertColumn($u,array$n){if(preg_match("~binary~",$n["type"]))return"HEX($u)";if($n["type"]=="bit")return"BIN($u + 0)";if($n["type"]=="vector")return($this->conn->flavor=='maria'?"VEC_ToText":"VECTOR_TO_STRING")."($u)";if(preg_match("~geom|point|linestring|polygon~",$n["type"]))return(min_version(8)?"ST_":"")."AsWKT($u)";return"";}function
convertSearch($u,array$X,array$n){return($this->convertColumn($u,$n)?:(preg_match('~'.text_type().'~',$n["type"])&&!preg_match("~^utf8~",$n["collation"])&&preg_match('~[\x80-\xFF]~',$X['val'])?"CONVERT($u USING ".charset($this->conn).")":$u));}function
typeName(\stdClass$n){$B=parent::typeName($n);if($B!=""){$Bn=["TINY"=>"tinyint","SHORT"=>"smallint","LONG"=>"int","INT24"=>"mediumint","LONGLONG"=>"bigint","NEWDECIMAL"=>"decimal","VAR_STRING"=>"varchar","STRING"=>"char",];return
idx($Bn,$B,strtolower($B));}$Bn=["decimal","tinyint","smallint","int","float","double",7=>"timestamp","bigint","mediumint","date","time","datetime","year",15=>"varchar","bit",242=>"vector",245=>"json","decimal","enum","set","tinytext","mediumtext","longtext","text","varchar","char","geometry",];$I=idx($Bn,$n->type,"");return($n->charsetnr==63?str_replace(["text","varchar","char"],["blob","varbinary","binary"],$I):$I);}function
quoteBinary($Xk){return"X".q(bin2hex($Xk));}function
md5($d,array$n){if(is_blob($n)||preg_match('~'.text_type().'~',$n["type"]))return"MD5(".(is_blob($n)||preg_match("~^utf8~",$n["collation"])?$d:"CONVERT($d USING ".charset($this->conn).")").")";}function
warnings(){$H=$this->conn->query("SHOW WARNINGS");if($H&&$H->num_rows){ob_start();print_select_result($H);return
ob_get_clean();}}function
errorLine($G){return(preg_match('~ at line (\d+)$~',$this->conn->error,$A)?(int)$A[1]:0);}function
tableHelp($B,$fg=false){$Rg=($this->conn->flavor=='maria');if(information_schema(DB))return
strtolower(str_replace("_","-",DB)."-".($Rg?"$B-table/":str_replace("_","-",$B)."-table.html"));if(DB=="sys")return($Rg?"sys-schema/":strtolower("sys-".str_replace("_","-",preg_replace('~^x\$~','',$B)).".html"));if(DB=="mysql")return($Rg?"mysql$B-table/":"system-schema.html");}function
partitionsInfo($R){$Ae="FROM information_schema.PARTITIONS WHERE TABLE_SCHEMA = ".q(DB)." AND TABLE_NAME = ".q($R);$H=$this->conn->query("SELECT PARTITION_METHOD, PARTITION_EXPRESSION, PARTITION_ORDINAL_POSITION $Ae ORDER BY PARTITION_ORDINAL_POSITION DESC LIMIT 1");$J=($H?$H->fetch_row():null);if(!$J)return[];$I=[];list($I["partition_by"],$I["partition"],$I["partitions"])=$J;$sj=get_key_vals("SELECT PARTITION_NAME, PARTITION_DESCRIPTION $Ae AND PARTITION_NAME != '' ORDER BY PARTITION_ORDINAL_POSITION");$I["partition_names"]=array_keys($sj);$I["partition_values"]=array_values($sj);return$I;}function
checkConstraints($R){$I=parent::checkConstraints($R);return($this->conn->flavor=='maria'?$I:array_map('stripslashes',$I));}function
hasCStyleEscapes(){static$fb;if($fb===null){$Zl=get_val("SHOW VARIABLES LIKE 'sql_mode'",1,$this->conn);$fb=(strpos($Zl,'NO_BACKSLASH_ESCAPES')===false);}return$fb;}function
hasEstimatedRows(){return
true;}function
isSystem($k,$L=""){return
information_schema($k,$L)||in_array($k,["mysql","sys"]);}function
lineComment(){return"#|-- ";}function
engines(){$I=[];foreach(get_rows("SHOW ENGINES")as$J){if(preg_match("~YES|DEFAULT~",$J["Support"]))$I[]=$J["Engine"];}return$I;}function
indexAlgorithms(array$xm){return(preg_match('~^(MEMORY|NDB)$~',$xm["Engine"])?["HASH","BTREE"]:[]);}}function
idf_escape($u){return"`".str_replace("`","``",$u)."`";}function
table($u){return
idf_escape($u);}function
get_databases($qe){$I=get_session("dbs");if($I===null){$G="SELECT SCHEMA_NAME FROM information_schema.SCHEMATA ORDER BY SCHEMA_NAME";$fm=microtime(true);$I=($qe?slow_query($G):get_vals($G));if(microtime(true)-$fm>0.1){restart_session();set_session("dbs",$I);stop_session();}}return$I;}function
limit($G,$Z,$z,$C=0,$ql=" "){return" $G$Z".($z?$ql."LIMIT $z".($C?" OFFSET $C":""):"");}function
limit1($R,$G,$Z,$ql="\n"){return
limit($G,$Z,1,0,$ql);}function
db_collation($k,array$Gb){static$I;if($I===null)$I=get_key_vals("SELECT SCHEMA_NAME, DEFAULT_COLLATION_NAME FROM information_schema.SCHEMATA");return$I[$k];}function
logged_user(){return
get_val("SELECT CURRENT_USER()");}function
tables_list(){return
get_key_vals("SELECT TABLE_NAME, TABLE_TYPE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME");}function
count_tables(array$j){$I=[];foreach($j
as$k)$I[$k]=count(get_vals("SHOW TABLES IN ".idf_escape($k)));return$I;}function
table_status($B="",$Wd=false){$I=[];$G="SELECT ENGINE AS Engine, TABLE_NAME AS Name, TABLE_COMMENT AS Comment FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() ".($B!=""?"AND TABLE_NAME = ".q($B):"ORDER BY Name");$L=[];foreach(($Wd?[]:get_rows($G))as$J)$L[$J["Name"]]=$J;$Uj=null;foreach(get_rows($Wd?$G:"SHOW TABLE STATUS".($B!=""?" LIKE ".q(addcslashes($B,"%_\\")):""))as$J){$Vi=idx($L,$J["Name"]);if($Vi){if($J["Comment"]!==$Vi["Comment"]&&$J["Comment"]!==$Uj)$J["Error"]=$J["Comment"];$Uj=$J["Comment"];$J["Comment"]=$Vi["Comment"];$J["Engine"]=$Vi["Engine"];}if($J["Engine"]=="InnoDB")$J["Comment"]=preg_replace('~(?:(.+); )?InnoDB free: .*~','\1',$J["Comment"]);if(!isset($J["Engine"]))$J["Comment"]="";if($B!="")$J["Name"]=$B;$I[$J["Name"]]=$J;}return$I;}function
is_view(array$S){return$S["Engine"]===null;}function
fk_support(array$S){return
preg_match('~InnoDB|IBMDB2I'.(min_version(5.6)?'|NDB':'').'~i',$S["Engine"]);}function
parse_type($De){preg_match('~^([^( ]+)(?:\((.+)\))?( unsigned)?( zerofill)?$~',$De,$A);return[$A[1],$A[2],ltrim($A[3].$A[4])];}function
fields($R){$Rg=(connection()->flavor=='maria');$I=[];foreach(get_rows("SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ".q($R)." ORDER BY ORDINAL_POSITION")as$J){$n=$J["COLUMN_NAME"];$U=$J["COLUMN_TYPE"];$Ie=$J["GENERATION_EXPRESSION"];$Td=$J["EXTRA"];preg_match('~^(VIRTUAL|PERSISTENT|STORED)~',$Td,$He);list($An,$y,$Ln)=parse_type($U);$l=$J["COLUMN_DEFAULT"];$Bc=false;if($l!=""){$nk=$Rg;if($Rg)$Bc=!preg_match("~^('(?:[^'\\\\]|\\\\.|'')*'|NULL|[-+]?[\\d.]+)$~s",$l);elseif(preg_match('~text|json~',$An)||preg_match('~\bDEFAULT_GENERATED\b~',$Td)){$l=stripslashes($l);$nk=preg_match("~^(_\\w+)?('(?:[^'\\\\]|\\\\.|'')*')$~s",$l,$A);if($nk)$l=$A[2];else$Bc=true;}elseif(preg_match("~^[bx]'[\\da-f]*'$~i",$l))$Bc=true;if($nk)$l=($l=="NULL"?null:(preg_match("~^'.*'$~s",$l)?stripslashes(idf_unescape($l)):$l));if(preg_match('~binary~',$An)&&preg_match("~^(?:0x(\\w*)|x'(\\w*)')$~",$l,$A)){$l=pack("H*",$A[1].idx($A,2));$Bc=false;}}$I[$n]=["field"=>$n,"full_type"=>$U,"type"=>$An,"length"=>$y,"unsigned"=>$Ln,"default"=>($He?($Rg?$Ie:stripslashes($Ie)):$l),"default_sql"=>(!$He&&$Bc),"null"=>($J["IS_NULLABLE"]=="YES"),"auto_increment"=>($Td=="auto_increment"),"on_update"=>(preg_match('~\bon update current_timestamp\b~i',$Td)?"CURRENT_TIMESTAMP":""),"collation"=>$J["COLLATION_NAME"],"privileges"=>array_flip(explode(",","$J[PRIVILEGES],where,order")),"comment"=>$J["COLUMN_COMMENT"],"primary"=>($J["COLUMN_KEY"]=="PRI"),"generated"=>($He[1]=="PERSISTENT"?"STORED":$He[1]),];}return$I;}function
indexes($R,$h=null){$I=[];foreach(get_rows("SHOW INDEX FROM ".table($R),$h)as$J){$B=$J["Key_name"];$I[$B]["type"]=($B=="PRIMARY"?"PRIMARY":($J["Index_type"]=="FULLTEXT"?"FULLTEXT":($J["Non_unique"]?(preg_match('~^(SPATIAL|VECTOR)$~',$J["Index_type"])?$J["Index_type"]:"INDEX"):"UNIQUE")));$I[$B]["columns"][]=$J["Column_name"];$I[$B]["lengths"][]=($J["Index_type"]=="SPATIAL"?null:$J["Sub_part"]);$I[$B]["descs"][]=null;$I[$B]["algorithm"]=$J["Index_type"];}return$I;}function
foreign_keys($R){static$_j='(?:`(?:[^`]|``)+`|"(?:[^"]|"")+")';$I=[];$ic=get_val("SHOW CREATE TABLE ".table($R),1);if($ic){preg_match_all("~CONSTRAINT ($_j) FOREIGN KEY ?\\(((?:$_j,? ?)+)\\) REFERENCES ($_j)(?:\\.($_j))? \\(((?:$_j,? ?)+)\\)(?: ON DELETE (".driver()->onActions."))?(?: ON UPDATE (".driver()->onActions."))?~",$ic,$Ug,PREG_SET_ORDER);foreach($Ug
as$A){preg_match_all("~$_j~",$A[2],$Rl);preg_match_all("~$_j~",$A[5],$Om);$I[idf_unescape($A[1])]=["db"=>idf_unescape($A[4]!=""?$A[3]:$A[4]),"table"=>idf_unescape($A[4]!=""?$A[4]:$A[3]),"source"=>array_map('Adminer\idf_unescape',$Rl[0]),"target"=>array_map('Adminer\idf_unescape',$Om[0]),"on_delete"=>($A[6]?:"RESTRICT"),"on_update"=>($A[7]?:"RESTRICT"),];}}return$I;}function
view($B){return["select"=>preg_replace('~^(?:[^`]|`[^`]*`)*\s+AS\s+~isU','',get_val("SHOW CREATE VIEW ".table($B),1))];}function
collations(){$I=[];foreach(get_rows("SHOW COLLATION")as$J)$I[$J["Charset"]][]=$J["Collation"];ksort($I);foreach($I
as$x=>$X)sort($I[$x]);return$I;}function
information_schema($k,$L=""){return($k=="information_schema")||(min_version(5.5)&&$k=="performance_schema");}function
error(){return
h(preg_replace('~^You have an error.*syntax to use~U',"Syntax error",connection()->error));}function
create_database($k,$Fb){return
queries("CREATE DATABASE ".idf_escape($k).($Fb?" COLLATE ".q($Fb):""));}function
drop_databases(array$j){$I=apply_queries("DROP DATABASE",$j,'Adminer\idf_escape');restart_session();set_session("dbs",null);return$I;}function
rename_database($B,$Fb){$I=false;if(create_database($B,$Fb)){$T=[];$no=[];foreach(tables_list()as$R=>$U){if($U=='VIEW')$no[]=$R;else$T[]=$R;}$I=(!$T&&!$no)||move_tables($T,$no,$B);drop_databases($I?[DB]:[]);}return$I;}function
auto_increment(){$Ma=" PRIMARY KEY";if($_GET["create"]!=""&&$_POST["auto_increment_col"]){foreach(indexes($_GET["create"])as$v){if(in_array($_POST["fields"][$_POST["auto_increment_col"]]["orig"],$v["columns"],true)){$Ma="";break;}if($v["type"]=="PRIMARY")$Ma=" UNIQUE";}}return" AUTO_INCREMENT$Ma";}function
alter_table($R,$B,array$o,array$se,$f,$vd,$Fb,$La,$rj){$b=[];foreach($o
as$n){if($n[1]){$l=$n[1][3];if(preg_match('~ GENERATED~',$l)){$n[1][3]=(connection()->flavor=='maria'?"":$n[1][2]);$n[1][2]=$l;}$b[]=($R!=""?($n[0]!=""?"CHANGE ".idf_escape($n[0]):"ADD"):" ")." ".implode($n[1]).($R!=""?$n[2]:"");}else$b[]="DROP ".idf_escape($n[0]);}$b=array_merge($b,$se);$P=($f!==null?" COMMENT=".q($f):"").($vd?" ENGINE=".q($vd):"").($Fb?" COLLATE ".q($Fb):"").($La!=""?" AUTO_INCREMENT=$La":"");if($rj){$sj=[];if($rj["partition_by"]=='RANGE'||$rj["partition_by"]=='LIST'){foreach($rj["partition_names"]as$x=>$X){$Y=$rj["partition_values"][$x];$sj[]="\n  PARTITION ".idf_escape($X)." VALUES ".($rj["partition_by"]=='RANGE'?"LESS THAN":"IN").($Y!=""?" ($Y)":" MAXVALUE");}}$P
.="\nPARTITION BY $rj[partition_by]($rj[partition])";if($sj)$P
.=" (".implode(",",$sj)."\n)";elseif($rj["partitions"])$P
.=" PARTITIONS ".(+$rj["partitions"]);}elseif($rj===null)$P
.="\nREMOVE PARTITIONING";if($R=="")return
queries("CREATE TABLE ".table($B)." (\n".implode(",\n",$b)."\n)$P");if($R!=$B)$b[]="RENAME TO ".table($B);if($P)$b[]=ltrim($P);return($b?queries("ALTER TABLE ".table($R)."\n".implode(",\n",$b)):true);}function
alter_indexes($R,$b){$nb=[];foreach($b
as$X)$nb[]=($X[2]=="DROP"?"\nDROP INDEX ".idf_escape($X[1]):"\nADD $X[0] ".($X[0]=="PRIMARY"?"KEY ":"").($X[1]!=""?idf_escape($X[1])." ":"")."(".implode(", ",$X[2]).")");return
queries("ALTER TABLE ".table($R).implode(",",$nb));}function
truncate_tables(array$T){return
apply_queries("TRUNCATE TABLE",$T);}function
drop_views(array$no){return
queries("DROP VIEW ".implode(", ",array_map('Adminer\table',$no)));}function
drop_tables(array$T){return
queries("DROP TABLE ".implode(", ",array_map('Adminer\table',$T)));}function
move_tables(array$T,array$no,$Om){$Bk=[];foreach($T
as$R)$Bk[]=table($R)." TO ".idf_escape($Om).".".table($R);if(!$Bk||queries("RENAME TABLE ".implode(", ",$Bk))){$Fc=[];foreach($no
as$R)$Fc[table($R)]=view($R);connection()->select_db($Om);$k=idf_escape(DB);foreach($Fc
as$B=>$mo){if(!queries("CREATE VIEW $B AS ".str_replace(" $k."," ",$mo["select"]))||!queries("DROP VIEW $k.$B"))return
false;}return
true;}return
false;}function
copy_tables(array$T,array$no,$Om){queries("SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO'");foreach($T
as$R){$B=($Om==DB?table("copy_$R"):idf_escape($Om).".".table($R));if(($_POST["overwrite"]&&!queries("\nDROP TABLE IF EXISTS $B"))||!queries("CREATE TABLE $B LIKE ".table($R))||!queries("INSERT INTO $B SELECT * FROM ".table($R)))return
false;foreach(get_rows("SHOW TRIGGERS LIKE ".q(addcslashes($R,"%_\\")))as$J){$rn=$J["Trigger"];list($Fd,$ki)=trigger_event($J);if(!queries("CREATE TRIGGER ".($Om==DB?idf_escape("copy_$rn"):idf_escape($Om).".".idf_escape($rn))." $J[Timing] $Fd".($ki!=""?" $ki":"")." ON $B FOR EACH ROW\n$J[Statement];"))return
false;}}foreach($no
as$R){$B=($Om==DB?table("copy_$R"):idf_escape($Om).".".table($R));$mo=view($R);if(($_POST["overwrite"]&&!queries("DROP VIEW IF EXISTS $B"))||!queries("CREATE VIEW $B AS $mo[select]"))return
false;}return
true;}function
trigger_event(array$J){$Hd=explode(",",$J["Event"]);$I=[];foreach(["DELETE","INSERT","UPDATE"]as$Fd){if(in_array($Fd,$Hd))$I[]=$Fd;}$I=implode(" OR ",$I);if(in_array("UPDATE",$Hd)&&min_version('','12.0.1')&&preg_match('~\s(?:BEFORE|AFTER)\s+(.+?)\s+ON\s~is',get_val("SHOW CREATE TRIGGER ".idf_escape($J["Trigger"]),2),$A)&&preg_match('~\bOF\s+(.+)~is',$A[1],$ki))return["$I OF",$ki[1]];return[$I,""];}function
trigger($B,$R){if($B=="")return[];$K=get_rows("SHOW TRIGGERS WHERE `Trigger` = ".q($B));$I=reset($K);if($I)list($I["Event"],$I["Of"])=trigger_event($I);return($I?:[]);}function
triggers($R){$I=[];foreach(get_rows("SHOW TRIGGERS LIKE ".q(addcslashes($R,"%_\\")))as$J){list($Fd)=trigger_event($J);$I[$J["Trigger"]]=[$J["Timing"],$Fd];}return$I;}function
trigger_options(){return["Timing"=>["BEFORE","AFTER"],"Event"=>(min_version('','12.0.1')?["INSERT","UPDATE","UPDATE OF","DELETE","INSERT OR UPDATE","INSERT OR UPDATE OF","DELETE OR INSERT","DELETE OR UPDATE","DELETE OR UPDATE OF","DELETE OR INSERT OR UPDATE","DELETE OR INSERT OR UPDATE OF",]:["INSERT","UPDATE","DELETE"]),"Type"=>["FOR EACH ROW"],];}function
routine($B,$U){$K=get_rows("SELECT PARAMETER_NAME, DTD_IDENTIFIER, PARAMETER_MODE, COLLATION_NAME
FROM information_schema.PARAMETERS
WHERE SPECIFIC_SCHEMA = DATABASE() AND ROUTINE_TYPE = '$U' AND SPECIFIC_NAME = ".q($B)."
ORDER BY ORDINAL_POSITION");$o=[];foreach($K
as$J){$De=$J["DTD_IDENTIFIER"];list($An,$y,$Ln)=parse_type($De);$o[]=["field"=>$J["PARAMETER_NAME"],"type"=>$An,"length"=>$y,"unsigned"=>$Ln,"null"=>true,"full_type"=>$De,"inout"=>($U=="FUNCTION"?"":$J["PARAMETER_MODE"]),"collation"=>$J["COLLATION_NAME"],];}$I=connection()->query("SELECT
	ROUTINE_COMMENT comment,
	ROUTINE_DEFINITION definition,
	LOWER(EXTERNAL_LANGUAGE) language,
	IF(DEFINER = CURRENT_USER(), '', DEFINER) definer,
	IF(IS_DETERMINISTIC = 'YES', 'DETERMINISTIC', 'NOT DETERMINISTIC') is_deterministic,
	SQL_DATA_ACCESS data_access,
	CONCAT('SQL SECURITY ', SECURITY_TYPE) security
FROM information_schema.ROUTINES
WHERE ROUTINE_SCHEMA = DATABASE() AND ROUTINE_TYPE = '$U' AND ROUTINE_NAME = ".q($B))->fetch_assoc();if(!$I)return[];if($I['language']!='javascript'&&preg_match('~^[^\'"]+(?=[\'"])~',$I['definition'],$A)){$i=get_val("SHOW CREATE $U ".idf_escape($B),2);$F=strpos($i,"\n$A[0]");if($F)$I['definition']=substr($i,$F+1);}$I['options']=["DEFINER"=>$I['definer'],"DETERMINISTIC"=>$I['is_deterministic'],"SQL_DATA_ACCESS"=>$I['data_access'],"SQL_SECURITY"=>$I['security'],"COMMENT"=>$I['comment'],];if($o&&$o[0]['field']=='')$I['returns']=array_shift($o);$I['fields']=$o;return$I;}function
routines(){return
get_rows("SELECT SPECIFIC_NAME, ROUTINE_NAME, ROUTINE_TYPE, DTD_IDENTIFIER FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA = DATABASE()");}function
routine_languages(){return(min_version(9,99)?["sql"=>"sql","javascript"=>"js"]:[]);}function
routine_options($Ok){return["DEFINER"=>[],"DETERMINISTIC"=>["NOT DETERMINISTIC","DETERMINISTIC"],"SQL_DATA_ACCESS"=>["CONTAINS SQL","NO SQL","READS SQL DATA","MODIFIES SQL DATA"],"SQL_SECURITY"=>["SQL SECURITY DEFINER","SQL SECURITY INVOKER"],"COMMENT"=>[],];}function
routine_id($B,array$J){return
idf_escape($B);}function
last_id($H){return
get_val("SELECT LAST_INSERT_ID()");}function
explain(Db$g,$G){return$g->query("EXPLAIN ".(min_version(5.7)?"":"PARTITIONS ").$G);}function
found_rows(array$S,array$Z){return($Z||$S["Engine"]!="InnoDB"?null:$S["Rows"]);}function
create_sql($R,$La,$nm){$I=get_val("SHOW CREATE TABLE ".table($R),1);$I=preg_replace_callback('~(?<=^CREATE TABLE )`(?:[^`]|``)+`~',function()use($R){return
table($R);},$I);if(!$La)$I=preg_replace('~(\n\)[^\n]*?) AUTO_INCREMENT=\d+~','\1',$I);return$I;}function
truncate_sql($R){return"TRUNCATE ".table($R);}function
use_sql($uc,$nm=""){$B=idf_escape($uc);$I="";if(preg_match('~CREATE~',$nm)&&($i=get_val("SHOW CREATE DATABASE $B",1))){set_utf8mb4($i);if($nm=="DROP+CREATE")$I="DROP DATABASE IF EXISTS $B;\n";$I
.="$i;\n";}return$I."USE $B";}function
trigger_sql($R){$I="";foreach(get_rows("SHOW TRIGGERS LIKE ".q(addcslashes($R,"%_\\")),null,"-- ")as$J){list($J["Event"],$J["Of"])=trigger_event($J);$I
.="\n".create_trigger(" ON ".table($J["Table"]),$J+["Type"=>"FOR EACH ROW"]).";\n";}return$I;}function
show_variables(){return
get_rows("SHOW VARIABLES");}function
show_status(){return
get_rows("SHOW STATUS");}function
process_list(){return
get_rows("SHOW FULL PROCESSLIST");}function
convert_field(array$n){return
driver()->convertColumn(idf_escape($n["field"]),$n);}function
unconvert_field(array$n,$I){if(preg_match("~binary~",$n["type"]))$I="UNHEX($I)";if($n["type"]=="bit")$I="CONVERT(b$I, UNSIGNED)";if($n["type"]=="vector")$I=(connection()->flavor=='maria'?"VEC_FromText":"STRING_TO_VECTOR")."($I)";if(preg_match("~geom|point|linestring|polygon~",$n["type"])){$Qj=(min_version(8)?"ST_":"");$I=$Qj."GeomFromText($I, $Qj"."SRID(".idf_escape($n["field"])."))";}return$I;}function
support($Xd){return
preg_match('~^(comment|columns|copy|database|drop_col|dump|event|indexes|kill|privileges|move_col|procedure|processlist|routine|sql|status|table|trigger|variables|view'.(min_version(8)?'|descidx':'').(min_version('8.0.16','10.2.1')?'|check':'').(min_version(8,99)?'|fast_status':'').')$~',$Xd);}function
kill_process($t){return
queries("KILL ".number($t));}function
max_connections(){return
get_val("SELECT @@max_connections");}function
types($Sd=false){return[];}function
type_values($t){return"";}function
type_definition($t){return["kind"=>"","definition"=>""];}function
schemas(){return[];}function
get_schema(){return"";}function
set_schema($L,$h=null){return
true;}function
schema_comment($L){return"";}function
set_schema_comment($L,$f){return
true;}}define('Adminer\JUSH',Driver::$jush);define('Adminer\SERVER',"".$_GET[DRIVER]);define('Adminer\DB',"$_GET[db]");define('Adminer\ME',preg_replace('~\?.*~','',relative_uri()).'?'.(sid()?SID.'&':'').($_GET["ext"]?"ext=".url_escape($_GET["ext"]).'&':'').(isset($_GET[DRIVER])?DRIVER."=".url_escape(SERVER).'&':'').(isset($_GET["username"])?"username=".url_escape($_GET["username"]).'&':'').(isset($_GET["db"])?'db='.url_escape(DB).'&'.(isset($_GET["ns"])?"ns=".url_escape($_GET["ns"])."&":""):''));if(isset($_GET["manifest"])){header("Content-Type: application/manifest+json; charset=utf-8");header("Cache-Control: no-cache");echo
json_encode(adminer()->manifest(),JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);exit;}function
page_header($an,$m="",$bb=[],$bn="",$di=false,$Xc=""){if($di){header("HTTP/1.1 404 Not Found");$m=($m?:'Not found.');}page_headers();if(is_ajax()&&$m){page_messages($m);exit;}if(!ob_get_level())ob_start('ob_gzhandler',4096);$cn=$an.($bn!=""?": $bn":"");$dn=strip_tags($cn.(SERVER!=""&&SERVER!="localhost"?h(" - ".SERVER):"")." - ".adminer()->name());echo'<!DOCTYPE html>
<html lang=\'en\' dir=\'ltr\' class=\'ltr nojs\'>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="robots" content="noindex">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>',$dn,'</title>
<link rel="stylesheet" href="',h(preg_replace("~\\?.*~","",ME)."?file=default.css&version=6.3.1+5c48c0d4"),'">
';$mc=adminer()->css();if(is_int(key($mc)))$mc=array_fill_keys($mc,'light');$Ye=in_array('light',$mc)||in_array('',$mc);$We=in_array('dark',$mc)||in_array('',$mc);$qc=($Ye?($We?null:false):($We?:null));$jh=" media='(prefers-color-scheme: dark)'";if($qc!==false)echo"<link rel='stylesheet'".($qc?"":$jh)." href='".h(preg_replace("~\\?.*~","",ME)."?file=dark.css&version=6.3.1+5c48c0d4")."'>\n";echo"<meta name='color-scheme' content='".($qc===null?"light dark":($qc?"dark":"light"))."'>\n",script_src(preg_replace("~\\?.*~","",ME)."?file=functions.js&version=6.3.1+5c48c0d4");if(adminer()->head($qc))echo"<link rel='icon' href='data:image/gif;base64,"."R0lGODlhEAAQAJEAAAQCBPz+/PwCBAROZCH5BAEAAAAALAAAAAAQABAAAAI2hI+pGO1rmghihiUdvUBnZ3XBQA7f05mOak1RWXrNq5nQWHMKvuoJ37BhVEEfYxQzHjWQ5qIAADs='>\n","<link rel='apple-touch-icon' href='".h(preg_replace("~\\?.*~","",ME)."?file=logo.svg&version=6.3.1+5c48c0d4")."'>\n";if(adminer()->manifest())echo"<link rel='manifest' href='".h(preg_replace('~\?.*~','',ME)."?manifest=")."' crossorigin='use-credentials'>\n";foreach($mc
as$Sn=>$zh){$c=($zh=='dark'&&!$qc?$jh:($zh=='light'&&$We?" media='(prefers-color-scheme: light)'":""));echo"<link rel='stylesheet'$c href='".h($Sn)."'>\n";}echo"\n<body class='";adminer()->bodyClass();echo"'>\n",script((isset($_COOKIE["adminer_version"])||!adminer()->verifyVersion()?"":"onload = partial(verifyVersion, '".VERSION."');\n")."
const offlineMessage = '".js_escape('You are offline.')."';
const numberFormat = '".js_escape('#,##0')."';
const numberDigits = '".js_escape('0123456789')."';
const urlSeparators = '".js_escape(ini_get("arg_separator.input"))."';"),"<div id='help' class='jush-".JUSH." jsonly hidden'".on('mouseover','helpKeep').on('mouseout','helpMouseout')."></div>\n","<div id='content'>\n","<span id='menuopen' class='jsonly'".on('click','menuToggle')."><button title='".'Menu'."' class='icon icon-move' aria-expanded='false'></button></span>\n";if($bb!==null){$jg=(class_exists('Adminer\Db')&&connection());$_=substr(preg_replace('~\b(username|db|ns)=[^&]*&~','',ME),0,-1);echo'<p id="breadcrumb">'.($jg?jump_input():'').'<a href="'.h($_?:".").'">'.get_driver(DRIVER).'</a> » ';$_=substr(preg_replace('~&(db|ns)=[^&]*~','',ME),0,-1);$N=adminer()->serverName(SERVER);$N=($N!=""?$N:'Server');if($bb===false)echo"$N\n";else{echo"<a href='".h($_.(DB!=""&&support("single_db")?"&db=":""))."' accesskey='1' title='Alt+Shift+1'>$N</a> » ";$gl="";if(is_string($bb)){$gl=$bb;$bb=[];}if($_GET["ns"]!=""||(DB!=""&&is_array($bb))){$wc="$_&db=".url_escape(DB).(support("scheme")?"&ns=":"").(support("single_table")?"&select=":"");echo'<a href="'.h($wc.($_GET["ns"]==""?$gl:"")).'">'.h(DB).'</a> » ';}if(is_array($bb)){if($_GET["ns"]!="")echo'<a href="'.h(substr(ME,0,-1).$gl).'">'.h($_GET["ns"]).'</a> » ';foreach($bb
as$x=>$X){$Ic=(is_array($X)?$X[1]:h($X));if($Ic!="")echo"<a href='".h(ME."$x=").url_escape(is_array($X)?$X[0]:$X)."'>$Ic</a> » ";}}echo"$an\n";}if($jg){echo"<form id='jump-form'>\n";hidden_fields_get();echo(DB!=""?input_hidden("db",DB):""),(isset($_GET["ns"])?input_hidden("ns",$_GET["ns"]):""),"</form>\n",script("jumpSetup();");}}echo"<h2>$cn$Xc</h2>\n","<div id='ajaxstatus' role='status' class='jsonly'></div>\n";restart_session();page_messages($m);adminer()->serviceWorker();$j=&get_session("dbs");if(DB!=""&&$j&&!in_array(DB,$j,true))$j=null;stop_session();define('Adminer\PAGE_HEADER',1);ob_flush();flush();if($di){page_footer($di===true?"":$di);exit;}}function
service_worker(){$zk=has_passwords();$Cb=($zk?"navigator.serviceWorker.register('".js_escape(preg_replace('~\?.*~','',ME)."?file=worker.js&version=6.3.1+5c48c0d4")."', {scope: location.pathname}).catch(() => {});":"navigator.serviceWorker.getRegistration().then(registration => registration && registration.unregister());
	caches.keys().then(keys => keys.forEach(key => key.startsWith('adminer-') && caches.delete(key)));");echo
script("if (navigator.serviceWorker) {\n\t$Cb\n}");}function
has_passwords(){foreach((array)$_SESSION["pwds"]as$Dl){foreach($Dl
as$co){foreach($co
as$E){if($E!==null)return
true;}}}return
false;}function
page_headers(){header("Content-Type: text/html; charset=utf-8");header("Cache-Control: no-cache");header("X-Frame-Options: deny");header("X-XSS-Protection: 0");header("X-Content-Type-Options: nosniff");header("Referrer-Policy: origin-when-cross-origin");foreach(adminer()->csp(csp())as$lc){$df=[];foreach($lc
as$x=>$X)$df[]="$x $X";header("Content-Security-Policy: ".implode("; ",$df));}adminer()->headers();}function
csp(){return[["script-src"=>"'self' 'unsafe-inline' 'nonce-".get_nonce()."' 'strict-dynamic'","connect-src"=>"'self' https://www.adminer.org","frame-src"=>"https://www.adminer.org","object-src"=>"'none'","base-uri"=>"'none'","form-action"=>"'self'",],];}function
design_checksums(){$Yn=[];foreach(array_keys(adminer()->css())as$Sn)$Yn[preg_replace('~\?.*~','',$Sn)]=true;$I=[];foreach(["adminer.css","adminer-dark.css"]as$p){if($Yn[$p]&&file_exists($p)){preg_match('~^/\* Adminer design ([-\w]+) \*/~',file_get_contents($p),$A);$I[$p]=[(string)$A[1],Plugins::checksum($p)];}}return$I;}function
official_design_checksums(){return['adminer-dark/adminer-dark.css'=>'a26bcd7b','brade/adminer.css'=>'be4161f0','bueltge/adminer.css'=>'1a8f00b4','cpanel/adminer.css'=>'59ce604e','dracula/adminer-dark.css'=>'cfaf61dd','esterka/adminer.css'=>'1f805f36','flat/adminer.css'=>'49a61af9','galkaev/adminer-dark.css'=>'16c46f94','haeckel/adminer.css'=>'147a3565','hever/adminer.css'=>'c9d1d11c','konya/adminer.css'=>'2b409696','lavender-light/adminer.css'=>'bf03f5d7','lucas-sandery/adminer.css'=>'6596353','mancave/adminer-dark.css'=>'d9169f12','mvt/adminer.css'=>'ebd3afdc','nette/adminer.css'=>'a8babfc5','ng9/adminer.css'=>'488583cf','nicu/adminer.css'=>'bded6cdf','pappu687/adminer.css'=>'b58d128c','paranoiq/adminer.css'=>'64d27e5','pepa-linha/adminer.css'=>'baf25f0','pokorny/adminer.css'=>'ee9eea6d','price/adminer.css'=>'81be9a85','rmsoft/adminer.css'=>'6cd4a237','rmsoft_blue-dark/adminer.css'=>'32102a8','rmsoft_blue/adminer.css'=>'7d8d5b18','win98/adminer.css'=>'d20077a7',];}function
version_iframe(){return(isset($_COOKIE["adminer_version"])||!adminer()->verifyVersion()?"":"<noscript><iframe sandbox src='https://www.adminer.org/version/?current=".VERSION."&amp;noscript=1'></iframe></noscript>");}function
get_nonce(){static$ci;if(!$ci)$ci=base64_encode(rand_string());return$ci;}function
page_messages($m){$Rn=preg_replace('~^[^?]*~','',$_SERVER["REQUEST_URI"]);$qh=idx($_SESSION["messages"],$Rn);if($qh){echo"<div class='message'>".implode("</div>\n<div class='message'>",$qh)."</div>".script("messagesPrint();");unset($_SESSION["messages"][$Rn]);}if($m)echo"<div class='error'>$m</div>\n";if(adminer()->error)echo"<div class='error'>".adminer()->error."</div>\n";}function
page_footer($yh=""){echo"</div>\n\n<div id='foot' class='foot'>\n<div id='menu'>\n";adminer()->navigation($yh);echo"</div>\n";if($yh!="auth")echo'<form method="post">
<p class="logout">
<span title="Username">',h($_GET["username"])."\n",'</span>
<input type=\'submit\' name=\'logout\' value=\'Logout\' id=\'logout\'>
',adminer()->donation()."\n",input_token(),'</form>
';echo"</div>\n\n",script("setupSubmitHighlight(document);");}function
int32($Hh){while($Hh>=2147483648)$Hh-=4294967296;while($Hh<=-2147483649)$Hh+=4294967296;return(int)$Hh;}function
long2str(array$W,$po){$Xk='';foreach($W
as$X)$Xk
.=pack('V',$X);if($po)return
substr($Xk,0,end($W));return$Xk;}function
str2long($Xk,$po){$W=array_values(unpack('V*',str_pad($Xk,4*ceil(strlen($Xk)/4),"\0")));if($po)$W[]=strlen($Xk);return$W;}function
xxtea_mx($Ao,$_o,$qm,$lg){return
int32((($Ao>>5&0x7FFFFFF)^$_o<<2)+(($_o>>3&0x1FFFFFFF)^$Ao<<4))^int32(($qm^$_o)+($lg^$Ao));}function
encrypt_string($km,$x){if($km=="")return"";$x=array_values(unpack("V*",pack("H*",md5($x))));$W=str2long($km,true);$Hh=count($W)-1;$Ao=$W[$Hh];$_o=$W[0];$gk=floor(6+52/($Hh+1));$qm=0;while($gk-->0){$qm=int32($qm+0x9E3779B9);$id=$qm>>2&3;for($cj=0;$cj<$Hh;$cj++){$_o=$W[$cj+1];$Gh=xxtea_mx($Ao,$_o,$qm,$x[$cj&3^$id]);$Ao=int32($W[$cj]+$Gh);$W[$cj]=$Ao;}$_o=$W[0];$Gh=xxtea_mx($Ao,$_o,$qm,$x[$cj&3^$id]);$Ao=int32($W[$Hh]+$Gh);$W[$Hh]=$Ao;}return
long2str($W,false);}function
decrypt_string($km,$x){if($km=="")return"";if(!$x)return
false;$x=array_values(unpack("V*",pack("H*",md5($x))));$W=str2long($km,false);$Hh=count($W)-1;$Ao=$W[$Hh];$_o=$W[0];$gk=floor(6+52/($Hh+1));$qm=int32($gk*0x9E3779B9);while($qm){$id=$qm>>2&3;for($cj=$Hh;$cj>0;$cj--){$Ao=$W[$cj-1];$Gh=xxtea_mx($Ao,$_o,$qm,$x[$cj&3^$id]);$_o=int32($W[$cj]-$Gh);$W[$cj]=$_o;}$Ao=$W[$Hh];$Gh=xxtea_mx($Ao,$_o,$qm,$x[$cj&3^$id]);$_o=int32($W[0]-$Gh);$W[0]=$_o;$qm=int32($qm-0x9E3779B9);}return
long2str($W,true);}$Cj=[];if($_COOKIE["adminer_permanent"]){foreach(explode(" ",$_COOKIE["adminer_permanent"])as$X){list($x)=explode(":",$X);$Cj[$x]=$X;}}function
add_invalid_login(){$Ta=get_temp_dir()."/adminer-invalid";foreach(glob("$Ta*")?:[$Ta]as$p){$ye=file_open_lock($p);if($ye)break;}if(!$ye)$ye=file_open_lock("$Ta-".rand_string());if(!$ye)return;$Xf=json_decode(stream_get_contents($ye),true);$Xm=time();if($Xf){foreach($Xf
as$Yf=>$X){if($X[0]<$Xm)unset($Xf[$Yf]);}}$Vf=&$Xf[adminer()->bruteForceKey()];if(!$Vf)$Vf=[$Xm+30*60,0];$Vf[1]++;file_write_unlock($ye,json_encode($Xf));}function
check_invalid_login(array&$Cj){$Xf=[];foreach(glob(get_temp_dir()."/adminer-invalid*")as$p){$ye=file_open_lock($p);if($ye){$Xf=json_decode(stream_get_contents($ye),true);file_unlock($ye);break;}}$x=adminer()->bruteForceKey();$Vf=idx($Xf,$x,[]);$bi=($Vf[1]>29?$Vf[0]-time():0);if($bi>0){$m=lang_format(['Too many unsuccessful logins, try again in %d minute.','Too many unsuccessful logins, try again in %d minutes.'],ceil($bi/60));if($_SERVER["HTTP_X_FORWARDED_FOR"]!=""&&$x==$_SERVER["REMOTE_ADDR"])$m
.='<br>'.sprintf('Use the %s <a%s>plugin</a> if Adminer runs behind a reverse proxy.','<b>login-reverse-proxy</b>'," href='https://www.adminer.org/plugins/?version=".VERSION."'".target_blank());auth_error($m,$Cj,false);}}function
password_required(){static$I;if($I===null){$I=(bool)get_session("password_required");if(!$I){$kc=adminer()->credentials();$I=!is_object(Driver::connect($kc[0],$kc[1],""));if($I)set_session("password_required",true);}}return$I;}function
require_password_link($E){$Ch="<a href='https://www.adminer.org/password/'".target_blank().">".'More options'."</a>";if(!function_exists('password_hash'))return" $Ch";$Fj=($E!==null?$E:base64_encode(substr(hex2bin(rand_string()),0,12)));$cf=password_hash($Fj,PASSWORD_DEFAULT);$p="adminer-plugins.php";$Md=file_exists("adminer-plugins.php");if($Md)$Tf=($E!==null?sprintf('Add this line to %s to require the entered password:',"<b>$p</b>"):sprintf('Add this line to %s to require the password %s:',"<b>$p</b>","<b>$Fj</b>"));else{$p="<button name='password_less' value='".h($cf)."' class='link'>$p</button>";$Tf=($E!==null?sprintf('Save %s next to Adminer to require the entered password:',$p):sprintf('Save %s next to Adminer to require the password %s:',$p,"<b>$Fj</b>"));}$Ig="\t<a>new</a> Adminer\\Password(<span class='jush-apo'>'".h($cf)."'</span>),";$I="<p>$Tf
<pre><code class='jush'>".($Md?$Ig:"&lt;?php\n<a>return</a> <a>array</a>(\n$Ig\n);")."</code></pre>
<p>$Ch
";return" <a href='#password-less' class='toggle'>".'Require a password.'."</a>
<div id='password-less' class='hidden'>".($Md?$I:"<form method='post'>\n".$I.input_token()."</form>")."</div>";}if(preg_match('~^[-\w$./]+$~',$_POST["password_less"])&&verify_token()){header("Content-Type: application/octet-stream");header("Content-Disposition: attachment; filename=adminer-plugins.php");echo"<?php\nreturn array(\n\tnew Adminer\\Password('$_POST[password_less]'),\n);\n";exit;}$Ka=$_POST["auth"];if($Ka&&(!adminer()->verifyLoginToken()||verify_token())){session_regenerate_id();$ko=$Ka["driver"];$N=$Ka["server"];$V=$Ka["username"];$E=(string)$Ka["password"];$k=$Ka["db"];set_password($ko,$N,$V,$E);$_SESSION["db"][$ko][$N][$V][$k]=true;if($Ka["permanent"]){$x=implode("-",array_map('base64_encode',[$ko,$N,$V,$k]));$Yj=adminer()->permanentLogin(true);$Cj[$x]="$x:".base64_encode($Yj?encrypt_string($E,$Yj):"");cookie("adminer_permanent",implode(" ",$Cj));}if(!array_diff(array_keys($_POST),["auth","token"])||$ko!=DRIVER||$N!=SERVER||$V!==$_GET["username"]||$k!=DB)redirect(auth_url($ko,$N,$V,$k));}elseif($_POST["logout"]&&(!$_SESSION["token"]||verify_token())){Driver::disconnect();foreach(["pwds","db","dbs","queries"]as$x)set_session($x,null);unset_permanent($Cj);redirect(substr(preg_replace('~\b(username|db|ns)=[^&]*&~','',ME),0,-1),'Logout successful.'.' '.sprintf('Thanks for using Adminer. Consider <a%s>donating</a>.'," href='https://www.adminer.org/donation/'".target_blank()));}elseif($Cj&&!$_SESSION["pwds"]){session_regenerate_id();$Yj=adminer()->permanentLogin();foreach($Cj
as$x=>$X){list(,$zb)=explode(":",$X);list($ko,$N,$V,$k)=array_map('base64_decode',explode("-",$x));set_password($ko,$N,$V,decrypt_string(base64_decode($zb),$Yj));$_SESSION["db"][$ko][$N][$V][$k]=true;}}function
unset_permanent(array&$Cj){foreach($Cj
as$x=>$X){list($ko,$N,$V,$k)=array_map('base64_decode',explode("-",$x));if($ko==DRIVER&&$N==SERVER&&$V==$_GET["username"]&&$k==DB)unset($Cj[$x]);}cookie("adminer_permanent",implode(" ",$Cj));}function
auth_error($m,array&$Cj,$Wf=true){$El=session_name();if(isset($_GET["username"])){header("HTTP/1.1 403 Forbidden");if(($_COOKIE[$El]||$_GET[$El])&&!$_SESSION["token"])$m='Session expired. Please log in again.';elseif($Wf&&($E=get_password())!==null){restart_session();add_invalid_login();if($E===false)$m
.=($m?'<br>':'').sprintf('Master password expired. <a href="https://www.adminer.org/en/extension/"%s>Implement</a> the %s method to make it permanent.',target_blank(),'<code>permanentLogin()</code>');set_password(DRIVER,SERVER,$_GET["username"],null);unset_permanent($Cj);}}if(!$_COOKIE[$El]&&$_GET[$El]&&ini_bool("session.use_only_cookies"))$m='Session support must be enabled.';$hj=session_get_cookie_params();cookie("adminer_key",($_COOKIE["adminer_key"]?:rand_string()),$hj["lifetime"]);if(!is_string($_SESSION["token"]))$_SESSION["token"]=rand_string();page_header('Login',$m,null);echo"<form method='post'>\n","<div>";if($_SERVER["HTTP_SEC_FETCH_SITE"]=="same-origin"&&hidden_fields($_POST,["auth","token"]))echo"<p class='message'>".'The action will be performed after successful login with the same credentials.'."\n";echo
input_token(),"</div>\n";adminer()->loginForm();echo"</form>\n";page_footer("auth");exit;}if(isset($_GET["username"])&&!class_exists('Adminer\Db')){unset($_SESSION["pwds"][DRIVER]);unset_permanent($Cj);page_header('No extension',sprintf('None of the supported PHP extensions (%s) are available.',implode(", ",Driver::$extensions)),false);page_footer("auth");exit;}$g='';if(isset($_GET["username"])&&is_string(get_password())){check_invalid_login($Cj);$kc=adminer()->credentials();$g=Driver::connect($kc[0],$kc[1],$kc[2]);if(is_object($g)){Db::$instance=$g;Driver::$instance=new
Driver($g);if($g->flavor)save_settings(["vendor-".DRIVER."-".SERVER=>get_driver(DRIVER)]);}}$Pg=null;if(!is_object($g)||($Pg=adminer()->login($_GET["username"],get_password()))!==true){$m=(is_string($g)?nl_br(h($g)):(is_string($Pg)?$Pg:'Invalid credentials.')).(preg_match('~^ | $~',get_password())?'<br>'.'There is a space in the entered password, which might be the cause.':'');auth_error($m,$Cj);}if($_POST["logout"]&&$_SESSION["token"]&&!verify_token()){page_header('Logout','Invalid CSRF token. Submit the form again.');page_footer("db");exit;}if(!is_string($_SESSION["token"]))$_SESSION["token"]=rand_string();stop_session(true);$m='';if($_POST){if(!verify_token()){header("HTTP/1.1 403 Forbidden");$m='Invalid CSRF token. Submit the form again.'.' '.'If you did not send this request from Adminer, close this page.';}}elseif($_SERVER["REQUEST_METHOD"]=="POST"){header("HTTP/1.1 413 Content Too Large");$m=sprintf('The POST data is too large. Reduce the data or increase the %s configuration directive.',"<b>post_max_size</b>");if(isset($_GET["sql"]))$m
.=' '.'You can upload a large SQL file via FTP and import it from the server.';}function
print_select_result($H,$h=null,array$Oi=[],&$z=0,&$jd=false){$Kg=[];$w=[];$e=[];$T=[];$Vj=[];$nd=[];$Bn=[];$I=[];$_h=$jd;$jd=false;for($s=0;(!$z||$s<$z)&&is_array($J=$H->fetch_row());$s++){if(!$s){echo"<div class='scrollable'>\n","<table class='nowrap odds'".($_h?on('click','tableClick').on('dblclick','tableClick').on('keydown','editingKeydown'):"").">\n","<thead><tr>";for($hg=0;$hg<count($J);$hg++){$n=$H->fetch_field();$B=$n->name;$R=(isset($n->table)?$n->table:"");$Ni=(isset($n->orgtable)?$n->orgtable:"");$Mi=(isset($n->orgname)?$n->orgname:$B);$An=driver()->typeName($n);if($Oi&&JUSH=="sql")$Kg[$hg]=($B=="table"?"table=":($B=="possible_keys"?"indexes=":null));elseif($Ni!=""){$ua=($R!=""?$R:$Ni);if($R!="")$I[$R]=$Ni;if(!isset($w[$ua])){if(!isset($Vj[$Ni])){$Vj[$Ni]=[];foreach(indexes($Ni,$h)as$v){if($v["type"]=="PRIMARY"){$Vj[$Ni]=array_flip($v["columns"]);break;}}}$T[$ua]=$Ni;$w[$ua]=$Vj[$Ni];$e[$ua]=$Vj[$Ni];}if(isset($e[$ua][$Mi])){unset($e[$ua][$Mi]);$w[$ua][$Mi]=$hg;$Kg[$hg]=$ua;}elseif($_h&&isset($n->orgname)&&$n->db==DB&&!is_blob(["type"=>$An]))$nd[$hg]=[$ua,$Mi,preg_match('~text|json|lob~',$An)];}$Bn[$hg]=$An;echo"<th title='".h(trim(($Ni!=""?"$Ni.$Mi":($n->name!=$Mi?$Mi:""))." ".$An))."'>".h($B).($Oi?doc_link(['sql'=>"explain-output.html#explain_".strtolower($B),'mariadb'=>"explain/#the-columns-in-explain-select",]):"");}foreach($nd
as$hg=>$kb){if($e[$kb[0]])unset($nd[$hg]);}echo"<tbody>\n";}$vf=[];foreach($w
as$ua=>$v){if($v&&!$e[$ua]){$u="";foreach($v
as$Db=>$hg){if($J[$hg]===null){$u=null;break;}$u
.="&where[".url_escape(bracket_escape($Db))."]=".url_escape($J[$hg]);}$vf[$ua]=$u;}}echo"<tr>";foreach($J
as$x=>$X){$_="";if(isset($Kg[$x])){if($Oi&&JUSH=="sql"){$R=$J[array_search("table=",$Kg)];$_=ME.$Kg[$x].url_escape($Oi[$R]!=""?$Oi[$R]:$R);}elseif(idx($vf,$Kg[$x])!==null)$_=ME."edit=".url_escape($T[$Kg[$x]]).$vf[$Kg[$x]];}$qf=select_value($X,$_,['type'=>(preg_match('~binary~',$Bn[$x])?'blob':$Bn[$x])],null);$c="";$kb=idx($nd,$x);if($kb&&idx($vf,$kb[0])!==null&&is_utf8($X)){$jd=true;$c=" data-name='".h("val[".bracket_escape($T[$kb[0]])."][".bracket_escape(substr($vf[$kb[0]],1))."][".bracket_escape($kb[1])."]")."' data-text='".($kb[2]?1:0)."'".(html_entity_decode(strip_tags($qf),ENT_QUOTES,"UTF-8")!==$X?" data-value='".h($X)."'":"");}echo"<td".(preg_match(number_type(),$Bn[$x])?" class='number'":"")."$c>$qf";}}$z=$s;echo($s?"</table>\n</div>":"<p class='message'>".'No rows.')."\n";return$I;}function
textarea($B,$Y,$K=10,$Hb=80,$kg=JUSH,$Ad=0){echo"<textarea name='".h($B)."' rows='$K' cols='$Hb' class='sqlarea jush-".h($kg)."' spellcheck='false' wrap='off'".($Ad?" data-error-line='$Ad'":"").">\n";if(is_array($Y)){foreach($Y
as$X)echo
h($X[0])."\n\n\n";}else
echo
h($Y);echo"</textarea>";}function
select_input($c,array$Hi,$Y="",$Dj=""){if($Hi&&$Y!=""&&!isset($Hi[$Y]))$Hi=[$Y=>$Y]+$Hi;$Nm=($Hi?"select":"input");return"<$Nm$c".($Hi?"><option value=''>$Dj".optionlist($Hi,$Y,true)."</select>":" size='10' value='".h($Y)."' placeholder='$Dj'>");}function
json_row($x,$X=null,$Ed=true){static$ke=true;if($ke)echo"{";if($x!=""){echo($ke?"":",")."\n\t\"".addcslashes($x,"\r\n\t\"\\/").'": '.($X!==null?($Ed?'"'.addcslashes($X,"\r\n\"\\/").'"':$X):'null');$ke=false;}else{echo"\n}\n";$ke=true;}}function
flat_collations(){$Gb=collations();return(is_array(reset($Gb))?call_user_func_array('array_merge',array_values($Gb)):$Gb);}function
edit_type($x,array$n,array$Gb,array$ue=[],array$Ud=[]){$U=(string)$n["type"];echo"<td><select name='".h($x)."[type]' class='type' aria-labelledby='label-type'".on_help_value().">";if($U&&!array_key_exists($U,driver()->types())&&!isset($ue[$U])&&!in_array($U,$Ud))$Ud[]=$U;$lm=driver()->structuredTypes();if($ue)$lm['Foreign keys']=$ue;echo
optionlist(array_merge($Ud,$lm),$U),"</select><td>","<input name='".h($x)."[length]' value='".h($n["length"])."' size='3'".(!$n["length"]&&preg_match('~var(char|binary)$~',$U)?" class='required'":"")." aria-labelledby='label-length'>","<td class='options'>",($Gb?"<input list='collations' name='".h($x)."[collation]'".option_types($U,'('.text_type().')$')." value='".h($n["collation"])."' placeholder='(".'collation'.")'>":''),(driver()->unsigned?"<select name='".h($x)."[unsigned]'".option_types($U,'^$|'.number_type()).'><option>'.optionlist(driver()->unsigned,$n["unsigned"]).'</select>':''),(isset($n['on_update'])?"<select name='".h($x)."[on_update]'".option_types($U,'timestamp|datetime').'>'.optionlist([""=>"(".'ON UPDATE'.")","CURRENT_TIMESTAMP"],(preg_match('~^CURRENT_TIMESTAMP~i',$n["on_update"])?"CURRENT_TIMESTAMP":$n["on_update"])).'</select>':''),($ue?"<select name='".h($x)."[on_delete]'".option_types($U,'`')."><option value=''>(".'ON DELETE'.")".optionlist(explode("|",driver()->onActions),$n["on_delete"])."</select> ":" ");}function
option_types($U,$Bn){return" data-types='".h($Bn)."'".(preg_match("~$Bn~",$U)?"":" class='hidden'");}function
process_length($y){if(JUSH=="mssql"&&preg_match('~^\s*\(?\s*max\s*\)?\s*$~i',$y))return"(max)";if(JUSH=="oracle"&&preg_match('~^\s*\(?\s*(\d+)\s+(CHAR|BYTE)\s*\)?\s*$~i',$y,$A))return"($A[1] ".strtoupper($A[2]).")";if(JUSH=="oracle"&&preg_match('~^\s*\(?\s*\*\s*,\s*(-?\d+)\s*\)?\s*$~',$y,$A))return"(*,$A[1])";$yd=driver()->enumLength;return(preg_match("~^\\s*\\(?\\s*$yd(?:\\s*,\\s*$yd)*+\\s*\\)?\\s*\$~",$y)&&preg_match_all("~$yd~",$y,$Ug)?"(".implode(",",$Ug[0]).")":preg_replace('~^[0-9].*~','(\0)',preg_replace('~[^-0-9,+()[\]]~','',$y)));}function
process_in($X){$X=preg_replace('~^\s*\(\s*(.*?)\s*\)\s*$~s','\1',$X);$nk="'(?:[^']|'')*+'";$gg=(preg_match("~^\\s*$nk(?:\\s*,\\s*$nk)*+\\s*\$~",$X)&&preg_match_all("~$nk~",$X,$Ug)?array_map('Adminer\idf_unescape',$Ug[0]):array_map('trim',explode(",",$X)));return"(".implode(", ",array_map('Adminer\q',$gg)).")";}function
process_between($X){$nk="('(?:[^']|'')*+')";$pk=(preg_match("~^\\s*$nk\\s+AND\\s+$nk\\s*\$~i",$X,$A)?[idf_unescape($A[1]),idf_unescape($A[2])]:preg_split('~\s+AND\s+~i',trim($X)));return(count($pk)==2?q($pk[0])." AND ".q($pk[1]):"NULL AND NULL");}function
process_type(array$n,$Eb="COLLATE"){return" ".(is_user_type($n["type"])?idf_escape($n["type"]):$n["type"]).process_length($n["length"]).(preg_match(number_type(),$n["type"])&&in_array($n["unsigned"],driver()->unsigned)?" $n[unsigned]":"").(preg_match('~'.text_type().'~',$n["type"])&&$n["collation"]?" $Eb ".(JUSH=="mssql"&&preg_match('~^\w+$~',$n["collation"])?$n["collation"]:q($n["collation"])):"");}function
process_field(array$n,array$yn){return[idf_escape(trim($n["field"])),process_type($yn),($n["null"]?" NULL":" NOT NULL"),default_value($n),(preg_match('~timestamp|datetime~',$n["type"])&&$n["on_update"]?" ON UPDATE $n[on_update]".process_length($n["length"]):""),(support("comment")&&$n["comment"]!=""?" COMMENT ".q($n["comment"]):""),($n["auto_increment"]?auto_increment():null),];}function
default_value(array$n){if($n["default"]===null)return"";$l=str_replace("\r","",$n["default"]);$He=$n["generated"];$Q=!preg_match('~]$~',$n["length"])&&(preg_match('~char|binary|text|clob|json|enum|set|String~',$n["type"])||driver()->enumLength($n));$ij=(JUSH=="sqlite"||(JUSH=="sql"&&$n["default_sql"]&&!preg_match("~^(current_timestamp(\\(\\d*\\))?|[bx]'[\\da-f]*')$~i",$l)));return(in_array($He,driver()->generated)?(JUSH=="mssql"?" AS ($l)".($He=="VIRTUAL"?"":" $He"):" GENERATED ALWAYS AS ($l) $He"):(preg_match('~^GENERATED ~i',$l)?" $l":" DEFAULT ".(!$n["default_sql"]&&($Q||preg_match('~^(?![a-z])~i',$l))?(JUSH=="sql"&&preg_match('~text|json~',$n["type"])?"(".q($l).")":q($l)):($ij?"($l)":$l))));}function
edit_fields(array$o,array$Gb,$U="TABLE",array$ue=[]){$o=array_values($o);$Ac=(($_POST?$_POST["defaults"]:get_setting("defaults"))?"":" class='hidden'");$Mb=(($_POST?$_POST["comments"]:get_setting("comments"))?"":" class='hidden'");echo"<thead><tr>\n",($U=="PROCEDURE"?"<td>":""),"<th id='label-name'>".($U=="TABLE"?'Column name':'Parameter name'),"<th id='label-type'>".'Type'."<textarea id='enum-edit' rows='4' cols='12' wrap='off' hidden></textarea>".script("qs('#enum-edit').onblur = editingLengthBlur;"),"<th id='label-length'>".'Length',"<th>".'Options';if($U=="TABLE")echo"<th id='label-null'>NULL\n","<th><input type='radio' name='auto_increment_col' value=''><abbr id='label-ai' title='".'Auto Increment'."'>A_I</abbr>",doc_link(['sql'=>"example-auto-increment.html",'mariadb'=>"auto_increment/",'sqlite'=>"autoinc.html",'pgsql'=>"datatype-numeric.html#DATATYPE-SERIAL",'cockroach'=>"serial",'mssql'=>"t-sql/statements/create-table-transact-sql-identity-property",]),"<th id='label-default'$Ac>".'Default value',(support("comment")?"<th id='label-comment'$Mb>".'Comment':"");$yg=!support("move_col");echo"<td>".icon("plus","add[".($yg?count($o):0)."]","+",'Add next',($yg?on('click','editingAddLastRow'):"")),"<tbody".on('click','editingClick').on('input','editingInput').on('keydown','editingKeydown').">\n";foreach($o
as$s=>$n){$s++;$Pi=$n[($_POST?"orig":"field")];$Pc=(isset($_POST["add"][$s-1])||(isset($n["field"])&&!idx($_POST["drop_col"],$s)))&&(support("drop_col")||$Pi=="");echo"<tr".($Pc?"":" hidden").">\n",($U=="PROCEDURE"?"<td>".html_select("fields[$s][inout]",explode("|",driver()->inout),$n["inout"]):"")."<th>",(support("move_col")?icon("move","","↕",'Move')." ":"");if($Pc)echo"<input name='fields[$s][field]' value='".h($n["field"])."' data-maxlength='64' autocapitalize='off' aria-labelledby='label-name'".(isset($_POST["add"][$s-1])?" autofocus":"").">";echo
input_hidden("fields[$s][orig]",$Pi);edit_type("fields[$s]",$n,$Gb,$ue);if($U=="TABLE"){echo"<td><label class='block'>".checkbox("fields[$s][null]",1,$n["null"],"","","","label-null")."</label>","<td><label class='block'><input type='radio' name='auto_increment_col' value='$s'".($n["auto_increment"]?" checked":"")." aria-labelledby='label-ai'></label>","<td$Ac>".html_select("fields[$s][generated]",array_merge(["","DEFAULT","DEFAULT SQL"],driver()->generated),$n["generated"],"","label-default")." ";$c=" name='fields[$s][default]' aria-labelledby='label-default'";$Y=h($n["default"]);echo(preg_match('~\n~',$n["default"])?"<textarea$c rows='2' cols='30' style='vertical-align: bottom;'>\n$Y</textarea>":"<input$c value='$Y'>");if(support("comment")){$c=" name='fields[$s][comment]' data-maxlength='".(min_version(5.5)?1024:255)."' aria-labelledby='label-comment'";echo"<td$Mb>".adminer()->commentInput('COLUMN',$c,$n["comment"]);}}echo"<td>",(support("move_col")?icon("plus","add[$s]","+",'Add next')." ":""),($Pi==""||support("drop_col")?icon("cross","drop_col[$s]","x",'Remove'):"");}}function
process_fields(array&$o){if($_POST["add"]){$o=array_values($o);array_splice($o,key($_POST["add"]),0,[[]]);}return$_POST["add"]||$_POST["drop_col"];}function
drop_create($cd,$i,$ed,$Tm,$gd,$Og,$ph,$nh,$oh,$ti,$Uh){if($_POST["drop"])query_redirect($cd,$Og,$ph);elseif($ti=="")query_redirect($i,$Og,$oh);elseif(support("transaction_ddl")){driver()->begin();queries_redirect($Og,$nh,queries($cd)&&queries($i)&&driver()->commit());driver()->rollback();}elseif($ti!=$Uh){$jc=queries($i);queries_redirect($Og,$nh,$jc&&queries($cd));if($jc&&$ed)queries($ed);}else
queries_redirect($Og,$nh,queries($Tm)&&queries($gd)&&queries($cd)&&queries($i));}function
create_trigger($xi,array$J){$Zm=" $J[Timing] $J[Event]".(preg_match('~ OF~',$J["Event"])?" $J[Of]":"");return"CREATE TRIGGER ".idf_escape($J["Trigger"]).(JUSH=="mssql"?$xi.$Zm:$Zm.$xi).preg_replace('~[\s;]+$~',''," $J[Type]\n$J[Statement]").";";}function
q_dollar($Q){$Gc='$$';while(strpos($Q.$Gc,$Gc)!=strlen($Q))$Gc='$_'.substr($Gc,1);return$Gc.$Q.$Gc;}function
routine_collate($Fb){static$qb=[];if($Fb&&!$qb){foreach(collations()as$pb=>$ho){foreach((array)$ho
as$X)$qb[$X]=$pb;}}return($qb[$Fb]?"CHARACTER SET ".q($qb[$Fb])." ":"")."COLLATE";}function
create_routine($Ok,array$J){$O=[];$o=$J["fields"];ksort($o);foreach($o
as$n){if($n["field"]!=""){$Of=(preg_match("~^(".driver()->inout.")\$~",$n["inout"])?$n["inout"]:"");$O[]="\n  ".(JUSH=="mssql"?"@$n[field]".process_type($n).($Of?" $Of":""):($Of?"$Of ":"").idf_escape($n["field"]).process_type($n,routine_collate($n["collation"])));}}$Dc="";$Hi=[];foreach(routine_options($Ok)as$x=>$io){$Y=idx($J["options"],$x,"");if($x=="DEFINER")$Dc=($Y?" $x=".implode("@",array_map('Adminer\q',explode("@",$Y,2))):"");elseif(!$io){if($Y!="")$Hi[]="$x ".q($Y);}elseif($Y!=reset($io)&&in_array($Y,$io))$Hi[]=$Y;}$wg=$J["language"];$Ec=preg_replace('~[\s;]+$~','',$J["definition"]);$Yc=(JUSH=="pgsql"||($wg&&$wg!="sql"));$gj=($O?implode(",",$O)."\n":"");return"CREATE$Dc $Ok ".table(trim($J["name"])).(JUSH=="mssql"&&$Ok=="PROCEDURE"?rtrim($gj):" ($gj)").($Ok=="FUNCTION"?"\nRETURNS".process_type($J["returns"],routine_collate($J["returns"]["collation"])):"").($wg?" LANGUAGE $wg":"").($Hi?"\n".implode(" ",$Hi):"").($Yc?" AS ".q_dollar("\n".trim($Ec)."\n"):(JUSH=="mssql"?"\nAS":"")."\n$Ec;");}function
remove_definer($G){$Dc=implode("@",array_map('Adminer\idf_escape',explode("@",logged_user(),2)));return
preg_replace('(^([A-Z =]+) DEFINER='.preg_quote($Dc).')','\1',$G);}function
object_name($U,$R,array$e){return
str_replace(["{table}","{columns}"],[$R,implode("_",$e)],adminer()->namePattern($U));}function
format_foreign_key(array$q,$B=""){$k=$q["db"];$ei=$q["ns"];return($B!=""?" CONSTRAINT ".idf_escape($B):"")." FOREIGN KEY (".implode(", ",array_map('Adminer\idf_escape',$q["source"])).") REFERENCES ".($k!=""&&$k!=$_GET["db"]?idf_escape($k).".":"").($ei!=""&&$ei!=$_GET["ns"]?idf_escape($ei).".":"").idf_escape($q["table"])." (".implode(", ",array_map('Adminer\idf_escape',$q["target"])).")".(preg_match("~^(".driver()->onActions.")\$~",$q["on_delete"])?" ON DELETE $q[on_delete]":"").(preg_match("~^(".driver()->onActions.")\$~",$q["on_update"])?" ON UPDATE $q[on_update]":"").($q["deferrable"]?" $q[deferrable]":"");}function
tar_file($p,$en){$I=pack("a100a8a8a8a12a12",$p,644,0,0,decoct($en->size),decoct(time()));$wb=8*32;for($s=0;$s<strlen($I);$s++)$wb+=ord($I[$s]);$I
.=sprintf("%06o\0 ",$wb);echo$I,str_repeat("\0",512-strlen($I));$en->send();echo
str_repeat("\0",511-($en->size+511)%512);}function
doc_version(){$Bl=connection()->server_info;if(JUSH=='oracle'){preg_match('~(?:.* |^)(\d+)\.\d+\.\d+\.\d+\.\d+~s',$Bl,$A);return($A[1]>=18?$A[1]:"19");}$yk=(JUSH=='sql'||connection()->flavor=='cockroach'?'~^\d+\.\d+~':'~^\d\.?\d~');$lo=(preg_match($yk,$Bl,$A)?$A[0]:"");if(JUSH=='mssql')return($lo>=15?"sql-server-ver$lo":($lo==12?"azuresqldb-current":"sql-server-2017"));return$lo;}function
doc_link(array$zj,$Um="📖"){$lo=doc_version();$Tn=['sql'=>"https://dev.mysql.com/doc/refman/$lo/en/",'sqlite'=>"https://www.sqlite.org/",'pgsql'=>"https://www.postgresql.org/docs/".(connection()->flavor=='cockroach'?"current":$lo)."/",'mssql'=>"https://learn.microsoft.com/en-us/sql/",'oracle'=>"https://docs.oracle.com/en/database/oracle/oracle-database/$lo/",];if(connection()->flavor=='maria'){$Tn['sql']="https://mariadb.com/kb/en/";$zj['sql']=($zj['mariadb']?:str_replace(".html","/",$zj['sql']));}if(connection()->flavor=='cockroach'&&$zj['cockroach']){$Tn['pgsql']="https://docs.cockroachlabs.com/docs/v$lo/";$zj['pgsql']=$zj['cockroach'];}return($zj[JUSH]?" <a href='".h($Tn[JUSH].$zj[JUSH].(JUSH=='mssql'?"?view=$lo":""))."'".target_blank()." class='doc' title='".'Documentation'."'>$Um</a>":"");}function
db_size($k){if(!connection()->select_db($k))return"?";$I=0;foreach(table_status()as$S)$I+=$S["Data_length"]+$S["Index_length"];return
adminer()->formatSizeValue($I);}function
set_utf8mb4($i){static$O=false;if(!$O&&preg_match('~\butf8mb4~i',$i)){$O=true;echo"SET NAMES ".charset(connection()).";\n\n";}}if(DB==""&&isset($_GET["ns"]))redirect(remove_from_uri('ns'),null,301);if(!(DB!=""?connection()->select_db(DB):isset($_GET["sql"])||isset($_GET["dump"])||isset($_GET["database"])||isset($_GET["processlist"])||isset($_GET["privileges"])||isset($_GET["user"])||isset($_GET["variables"])||$_GET["script"]=="connect")){if(DB!=""||$_GET["refresh"]){restart_session();set_session("dbs",null);}if(DB!="")page_header('Database'.": ".h(DB),adminer()->error(),true,"","db");else{if(!isset($_GET["db"])&&support("single_db")){$j=adminer()->databases();if($j)redirect(ME."db=".url_escape($j[0]));}if($_POST["db"]&&!$m)queries_redirect(substr(ME,0,-1),'Databases have been dropped.',drop_databases($_POST["db"]));page_header('Select database',$m,false);echo"<p class='links'>\n";foreach(['database'=>'Create database','privileges'=>'Privileges','processlist'=>'Process list','variables'=>'Variables','status'=>'Status',]as$x=>$X){if(support($x))echo"<a href='".h(ME)."$x='>$X</a>\n";}echo"<p>".sprintf('%s version: %s through PHP extension %s',get_driver(DRIVER),"<b>".h(connection()->server_info)."</b>","<b>".connection()->extension."</b>")."\n","<p>".sprintf('Logged in as: %s',"<b>".h(logged_user())."</b>")."\n";$j=adminer()->databases();$ie=$_GET["filter"];if($ie!=""){foreach($j
as$x=>$k){if(stripos($k,$ie)===false)unset($j[$x]);}}if($j){$bl=support("scheme");echo"<form method='post'>\n","<table class='checkable odds'".on('click','tableClick').on('dblclick','tableClick').">\n","<thead><tr>".(support("database")?"<td class='hover'>":"")."<th".(JUSH!='mssql'?" aria-sort='ascending'":"").">".'Database'.(get_session("dbs")!==null?" - <a href='".h(ME)."refresh=1'>".'Refresh'."</a>":"")."<th>".'Collation'."<th>".'Tables'."<th>".'Size'." - <a href='".h(ME)."dbsize=1'".on('click','ajaxSetHtml',ME."script=connect").">".'Compute'."</a>"."<tbody>\n";$j=($_GET["dbsize"]?count_tables($j):array_flip($j));foreach($j
as$k=>$T){$Nk=h(preg_replace('~&db=[^&]*~','',ME))."db=".url_escape($k);$t=h("Db-".$k);echo"<tr>".(support("database")?"<td class='hover'>".checkbox("db[]",$k,in_array($k,(array)$_POST["db"]),"","","",$t):""),"<th><a href='$Nk' id='$t'>".h($k)."</a>";$Fb=h(db_collation($k,[]));echo"<td>".(support("database")?"<a href='$Nk".($bl?"&amp;ns=":"")."&amp;database=' title='".'Alter database'."'>$Fb</a>":$Fb),"<td align='right'><a href='$Nk&amp;schema=' id='tables-".h($k)."' title='".'Database schema'."'>".($_GET["dbsize"]?format_number($T):"?")."</a>","<td align='right' id='size-".h($k)."'>".($_GET["dbsize"]?db_size($k):"?"),"\n";}echo"</table>\n",(support("database")?"<div class='footer'><div>\n"."<fieldset><legend>".'Selected'." <span id='selected'></span></legend><div>\n"."<input type='hidden' name='all' value=''".on('click','countDbs').">\n"."<input type='submit' name='drop' value='".'Drop'."'".confirm().">\n"."</div></fieldset>\n"."</div></div>\n":""),input_token(),"</form>\n",script("tableCheck();");}$oa=adminer();$Hj=($oa
instanceof
Plugins?$oa->plugins:[]);$bd=($oa
instanceof
Plugins?$oa->drivers:[]);$Mc=design_checksums();if($Hj||$bd||$Mc){$xb=($oa
instanceof
Plugins?$oa->checksums():[]);$mi=Plugins::officialChecksums();$On=function($Sn){return" (<a href='$Sn'".target_blank()." class='update'>".VERSION."</a>)";};$Gj=function($ee)use($xb,$mi,$On){return($xb[$ee]&&$mi[$ee]&&$xb[$ee]!==$mi[$ee]?$On("https://www.adminer.org/plugins/?version=".VERSION):"");};echo"<div class='plugins'>\n","<h3>".'Loaded plugins'."</h3>\n<ul>\n";foreach($Hj
as$Ej){$wk=new
\ReflectionObject($Ej);$Jc=Plugins::describe($Ej);echo"<li><b>".h(Plugins::pluginName($Ej))."</b>".($Jc!=""?": $Jc":"").$Gj(basename((string)$wk->getFileName(),'.php'))."\n";}foreach($bd
as$t=>$B)echo"<li><b>".h($t)."</b>: ".h($B).$Gj(basename((string)$oa->driverFiles[$t],'.php'))."\n";if($Mc){$oi=official_design_checksums();foreach($Mc
as$p=>$Lc){list($B,$wb)=$Lc;$ni=$oi["$B/$p"];echo"<li><b>".h($p)."</b>".h($B?": $B":"").($ni&&$ni!==$wb?$On("https://www.adminer.org/?version=".VERSION."#extras"):"")."\n";}}echo"</ul>\n";adminer()->pluginsLinks();echo"</div>\n";}}page_footer("db");exit;}if(support("scheme")){if(DB!=""&&$_GET["ns"]!==""){if(!isset($_GET["ns"]))redirect(preg_replace('~&db=[^&]+~','\0&ns='.url_escape(get_schema()),relative_uri()));if(!set_schema($_GET["ns"]))page_header('Schema'.h(": $_GET[ns]"),adminer()->error(),true,"","ns");}}adminer()->afterConnect();class
TmpFile{private$handler;var$size=0;function
__construct(){$this->handler=tmpfile();}function
write($ac){$this->size+=strlen($ac);fwrite($this->handler,$ac);}function
send(){fseek($this->handler,0);fpassthru($this->handler);fclose($this->handler);}}if($_GET["select"]!=""&&($_POST["edit"]||$_POST["clone"])&&!$_POST["save"])$_GET["edit"]=$_GET["select"];if(isset($_GET["callf"]))$_GET["call"]=$_GET["callf"];if(isset($_GET["function"]))$_GET["procedure"]=$_GET["function"];if(isset($_GET["download"])){$a=$_GET["download"];$o=fields($a);header("Content-Type: application/octet-stream");$io=array_merge((array)$_GET["where"],(array)$_GET["val"]);header("Content-Disposition: attachment; filename=".friendly_url("$a-".implode("_",$io)).".".friendly_url($_GET["field"]));$M=[idf_escape($_GET["field"])];$H=driver()->select($a,$M,[where($_GET,$o)],$M);$J=($H?$H->fetch_row():[]);echo
driver()->value($J[0],$o[$_GET["field"]]);exit;}elseif(isset($_GET["table"])){$a=$_GET["table"];$o=fields($a);if(!$o)$m=adminer()->error();$S=table_status1($a);$B=adminer()->tableName($S);$m=$m?:h($S["Error"]);page_header(($o&&is_view($S)?$S['Engine']=='materialized view'?'Materialized view':'View':'Table').": ".($B!=""?$B:h($a)),$m,[],"",!$o,($o?doc_link([JUSH=>driver()->tableHelp($a,is_view($S))]):""));$Mk=[];foreach($o
as$x=>$n)$Mk+=$n["privileges"];adminer()->selectLinks($S,(isset($Mk["insert"])||!support("table")?"":null));$f=$S["Comment"];if($f!="")echo"<p class='nowrap'>".'Comment'.": ".adminer()->commentValue('TABLE',$f)."\n";if($o)adminer()->tableStructurePrint($o,$S);function
tables_links(array$T){echo"<ul>\n";foreach($T
as$J){$_=preg_replace('~&ns=[^&]*~',"&ns=".url_escape($J["ns"]),ME);echo"<li><a href='".h($_."table=".url_escape($J["table"]))."'>".($J["ns"]!=$_GET["ns"]?"<b>".h($J["ns"])."</b>.":"").h($J["table"])."</a>";}echo"</ul>\n";}$Mf=driver()->inheritsFrom($a);if($Mf){echo"<h3>".'Inherits from'."</h3>\n";tables_links($Mf);}if(support("indexes")&&driver()->supportsIndex($S)){echo"<div>\n","<h3 id='indexes'>".'Indexes'."</h3>\n";$w=indexes($a);if($w)adminer()->tableIndexesPrint($w,$S);if(driver()->supportsAlterIndex($S))echo'<p class="links hover"><a href="'.h(ME).'indexes='.url_escape($a).'">'.'Alter indexes'."</a>\n";echo"</div>\n";}if(!is_view($S)&&driver()->supportsAlterTable($S)){if(fk_support($S)){echo"<div>\n","<h3 id='foreign-keys'>".'Foreign keys'."</h3>\n";$ue=foreign_keys($a);$za=function_exists('Adminer\alter_table');if($ue){echo"<table>\n","<thead><tr><th>".'Source'."<th>".'Target'."<th>".'ON DELETE'."<th>".'ON UPDATE'.($za?"<td class='hover'>":"")."<tbody>\n";foreach($ue
as$B=>$q){echo"<tr".(!is_int($B)?" title='".h($B)."'":"").">","<th><i>".implode("</i>, <i>",array_map('Adminer\h',$q["source"]))."</i>";$_=($q["db"]!=""?preg_replace('~&db=[^&]*~',"&db=".url_escape($q["db"]),ME):($q["ns"]!=""?preg_replace('~&ns=[^&]*~',"&ns=".url_escape($q["ns"]),ME):ME));echo"<td><a href='".h($_."table=".url_escape($q["table"]))."'>".($q["db"]!=""&&$q["db"]!=DB?"<b>".h($q["db"])."</b>.":"").($q["ns"]!=""&&$q["ns"]!=$_GET["ns"]?"<b>".h($q["ns"])."</b>.":"").h($q["table"])."</a>","(<i>".implode("</i>, <i>",array_map('Adminer\h',$q["target"]))."</i>)","<td>".h($q["on_delete"]),"<td>".h($q["on_update"]),($za?'<td class="hover"><a href="'.h(ME.'foreign='.url_escape($a).'&name='.url_escape($B)).'">'.'Alter'.'</a>':''),"\n";}echo"</table>\n";}echo($za?'<p class="links hover"><a href="'.h(ME).'foreign='.url_escape($a).'">'.'Create foreign key'."</a>\n":''),"</div>\n";}if(support("check")){echo"<div>\n","<h3 id='checks'>".'Checks'."</h3>\n";$sb=driver()->checkConstraints($a);if($sb){echo"<table>\n";foreach($sb
as$x=>$X)echo"<tr".($x!=$X?" title='".h($x)."'":"").">","<td><code class='sql jush-".JUSH."'>".shorten_utf8(preg_replace('~\s+~',' ',ltrim($X)),80,"</code>"),"<td class='hover'><a href='".h(ME.'check='.url_escape($a).'&name='.url_escape($x))."'>".'Alter'."</a>","\n";echo"</table>\n";}echo'<p class="links hover"><a href="'.h(ME).'check='.url_escape($a).'">'.'Create check'."</a>\n","</div>\n";}}if(support(is_view($S)?"view_trigger":"trigger")&&driver()->supportsAlterTable($S)){echo"<div>\n","<h3 id='triggers'>".'Triggers'."</h3>\n";$vn=triggers($a);if($vn){echo"<table>\n";foreach($vn
as$x=>$X){echo"<tr valign='top'><td>".h($X[0])."<td>".h($X[1])."<th>".h($x)."<td class='hover'><a href='".h(ME.'trigger='.url_escape($a).'&name='.url_escape($x))."'>".'Alter'."</a>";$Ok=$X[2];if($Ok){$Qk=preg_replace('~&ns=[^&]*~',"&ns=".url_escape($Ok["ns"]),ME).'function='.url_escape($Ok["function"]).'&name='.url_escape($Ok["name"]);echo", <a href='".h($Qk)."' title='".h($Ok["name"])."'>".'Alter function'."</a>";}echo"\n";}echo"</table>\n";}echo'<p class="links hover"><a href="'.h(ME).'trigger='.url_escape($a).'">'.'Create trigger'."</a>\n","</div>\n";}$Hl=driver()->shadowTables($a);if($Hl){echo"<h3 id='shadow-tables'>".'Shadow tables'."</h3>\n";tables_links($Hl);}$Lf=driver()->inheritedTables($a);if($Lf){echo"<h3 id='partitions'>".'Inherited by'."</h3>\n";$mj=driver()->partitionsInfo($a);if($mj)echo"<p><code class='sql jush-".JUSH."'>BY ".h("$mj[partition_by]($mj[partition])")."</code>\n";tables_links($Lf);}}elseif(isset($_GET["schema"])){page_header('Database schema',"",[],h(DB.($_GET["ns"]?".$_GET[ns]":"")));function
schema_column($R,array$vk,array&$e){if(!isset($e[$R])){$e[$R]=0;foreach((array)idx($vk,$R)as$B=>$xk){if($B!=$R)$e[$R]=max($e[$R],schema_column($B,$vk,$e)+1);}}return$e[$R];}function
type_class($U){foreach(['char'=>'text','date'=>'time|year','binary'=>'blob','enum'=>'set',]as$x=>$X){if(preg_match("~$x|$X~",$U))return" class='$x'";}}$Em=[];$Gm=[];$Fm=[];$be=[];$ca=($_GET["schema"]?:$_COOKIE["adminer_schema-".str_replace(".","_",DB)]);preg_match_all('~([^:]+):([-0-9.]+)x([-0-9.]+)(_|$)~',$ca,$Ug,PREG_SET_ORDER);foreach($Ug
as$s=>$A){$Em[$A[1]]=[(float)$A[2],(float)$A[3]];$Gm[]="\n\t'".js_escape($A[1])."': [ $A[2], $A[3] ]";}$L=[];$vk=[];$ue=[];$xa=driver()->allFields();$if=[];$Hm=[];foreach(table_status('',true)as$R=>$S){if(!is_view($S)){if(adminer()->tableName($S)!=""&&!$S["dependent"])$Hm[$R]=$S;else$if[$R]=true;}}foreach($Hm
as$R=>$S){$F=0;$L[$R]["fields"]=[];foreach($xa[$R]as$n){$F+=1.25;$be[$R][$n["field"]]=$F;$L[$R]["fields"][$n["field"]]=$n;}foreach(adminer()->foreignKeys($R)as$X){if($X["db"]==""&&$X["ns"]==""&&!$if[$X["table"]]){$ue[$R][]=$X;$vk[$X["table"]][$R]=[];}}}$e=[];$Le=[];$yo=[];$Se=[];foreach(array_keys($L)as$B)schema_column($B,$vk,$e);arsort($e);foreach($e
as$B=>$d){$wh=null;foreach((array)idx($ue,$B)as$X){if($X["table"]!=$B&&$L[$X["table"]])$wh=($wh===null?$e[$X["table"]]:min($wh,$e[$X["table"]]));}$e[$B]=max($d,(int)$wh-1);}$Jm=[];foreach($L
as$B=>$R){$d=$e[$B];$Le[$d][]=$B;$Wm=.75*strlen($B);foreach($R["fields"]as$n)$Wm=max($Wm,.65*strlen($n["field"]));$Jm[$B]=ceil($Wm)+1;$yo[$d]=max(idx($yo,$d,0),$Jm[$B]);}foreach($ue
as$B=>$ho){foreach($ho
as$X){$Re=$e[$B]+(idx($e,$X["table"],$e[$B])>$e[$B]?1:0);$Se[$Re]=idx($Se,$Re,0)+1;}}ksort($Le);$gf=0;$xo=0;$Ib=0;$Tj=null;$Am=[];foreach($Le
as$d=>$T){if($Tj!==null){$Ib=round($Ib+$yo[$Tj]+1.7+idx($Se,$d,0)*.1,1);$Ki=[];foreach($T
as$B){$qm=0;$hc=0;$Ph=array_keys((array)idx($vk,$B));foreach((array)idx($ue,$B)as$X)$Ph[]=$X["table"];foreach($Ph
as$Ih){if($L[$Ih]&&$e[$Ih]<$d){$qm+=$L[$Ih]["pos"][0];$hc++;}}$Ki[$B]=($hc?$qm/$hc:$gf);}asort($Ki);$T=array_keys($Ki);}$hn=0;foreach($T
as$B){$F=1.25*count($L[$B]["fields"]);$L[$B]["pos"]=($Em[$B]?:[$hn,$Ib]);$Am[$B]=$L[$B]["pos"][1];$hn+=2.5+$F;$gf=max($gf,$L[$B]["pos"][0]+2.5+$F);$xo=max($xo,round($L[$B]["pos"][1]+$yo[$d],1));if(!$Em[$B])$Fm[]="\n\t'".js_escape($B)."': [ ".$L[$B]["pos"][0].", ".$L[$B]["pos"][1]." ]";}$Tj=$d;}$Bg=[];$Ua=[];foreach($ue
as$B=>$ho){foreach($ho
as$X){$Pm=idx($Am,$X["table"],$Am[$B]);$Sl=$Am[$B]+$yo[$e[$B]];$Lk=($Pm-1>$Sl);$_g=($Lk?$Sl+1:min($Am[$B],$Pm)-1);$Ta=idx($Ua,(string)$_g,0);$Ua[(string)$_g]=$Ta+1;$_g=round($Lk?min($_g+$Ta*.1,$Pm-1):$_g-$Ta*.1,1);while($Bg[(string)$_g])$_g-=.0001;$L[$B]["references"][$X["table"]][(string)$_g]=[$X["source"],$X["target"]];$vk[$X["table"]][$B][(string)$_g]=$X["target"];$Bg[(string)$_g]=true;}}echo'<div id="schema" style="height: ',$gf,'em; width: ',$xo,'em;">
<script',nonce(),'>
const tablePos = {',implode(",",$Gm)."\n",'};
const tablePosDefault = {',implode(",",$Fm)."\n",'};
const em = qs(\'#schema\').offsetHeight / ',$gf,';
document.onmousemove = schemaMousemove;
document.onmouseup = event => schemaMouseup(event, \'',js_escape(DB),'\');
</script>
';foreach($L
as$B=>$R){echo"<div class='table'".on('mousedown','schemaMousedown')." style='top: ".$R["pos"][0]."em; left: ".$R["pos"][1]."em; width: ".$Jm[$B]."em;'>",'<a href="'.h(ME).'table='.url_escape($B).'"><b>'.h($B)."</b></a>";foreach($R["fields"]as$n){$X='<span'.type_class($n["type"]).' title="'.h($n["type"].($n["length"]?"($n[length])":"").($n["null"]?" NULL":'')).'">'.h($n["field"]).'</span>';echo"<br>".($n["primary"]?"<i>$X</i>":$X);}foreach((array)$R["references"]as$Qm=>$xk){foreach($xk
as$_g=>$sk){$Ag=$_g-$R["pos"][1];$nm=($Ag>0?"left: 100%; width: calc($Ag"."em - 100%)":"left: $Ag"."em");$xo=($Ag>0?"100%":(-$Ag)."em");$s=0;foreach($sk[0]as$Rl)echo"\n<div class='references' title='".h($Qm)."' id='refs$_g-".($s++)."' style='$nm"."; top: ".$be[$B][$Rl]."em; padding-top: .5em;'>"."<div style='border-top: 1px solid gray; width: $xo;'></div></div>";}}foreach((array)$vk[$B]as$Qm=>$xk){foreach($xk
as$_g=>$Rm){$Ag=$_g-$R["pos"][1];$s=0;foreach($Rm
as$Om)echo"\n<div class='references arrow' title='".h($Qm)."' id='refd$_g-".($s++)."' style='left: $Ag"."em; top: ".$be[$B][$Om]."em;'>"."<div style='height: .5em; border-bottom: 1px solid gray; width: ".(-$Ag)."em;'></div>"."</div>";}}echo"\n</div>\n";}foreach($L
as$B=>$R){foreach((array)$R["references"]as$Qm=>$xk){if($L[$Qm]){foreach($xk
as$_g=>$sk){$xh=$gf;$ch=-10;foreach($sk[0]as$x=>$Rl){$Jj=$R["pos"][0]+$be[$B][$Rl];$Kj=$L[$Qm]["pos"][0]+$be[$Qm][$sk[1][$x]];$xh=min($xh,$Jj,$Kj);$ch=max($ch,$Jj,$Kj);}echo"<div class='references' id='refl$_g' style='left: $_g"."em; top: $xh"."em; padding: .5em 0;'><div style='border-right: 1px solid gray; margin-top: 1px; height: ".($ch-$xh)."em;'></div></div>\n";}}}}echo'</div>
<p class="links"><a href="',h(ME."schema=".url_escape($ca)),'" id="schema-link">Permanent link</a>
';}elseif(isset($_GET["dump"])){$a=$_GET["dump"];if($_POST&&!$m){$l=["auto_increment"=>''];foreach(["type","routine","event","trigger"]as$sm){if(support($sm))$l[$sm."s"]='';}save_settings(array_intersect_key($_POST+$l,array_flip(["output","format","db_style","schema_style","table_style","data_style"])+$l),"adminer_export");$wa=(DB==""||$_GET["ns"]==="");$T=array_flip((array)$_POST["tables"])+array_flip((array)$_POST["data"]);$Qd=dump_headers((count($T)==1?key($T):DB),($wa||count($T)>1));$dg=preg_match('~sql~',$_POST["format"]);if($dg){echo"-- Adminer ".VERSION." ".get_driver(DRIVER)." ".str_replace("\n"," ",connection()->server_info)." dump\n\n";if(JUSH=="sql"){echo"SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
".($_POST["data_style"]?"SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';
":"")."
";connection()->query("SET time_zone = '+00:00'");connection()->query("SET sql_mode = ''");}}$nm=$_POST["db_style"];$j=[DB];if(DB==""){$j=$_POST["databases"];if(is_string($j))$j=explode("\n",rtrim(str_replace("\r","",$j),"\n"));}foreach((array)$j
as$k){adminer()->dumpDatabase($k);if(connection()->select_db($k)){if($dg&&$nm)echo
use_sql($k,$nm).";\n\n";foreach(($_GET["ns"]===""?(array)$_POST["schemas"]:(DB!=""||!support("scheme")?[""]:adminer()->schemas()))as$L){if($L!=""){if(DB==""&&information_schema(DB,$L))continue;set_schema($L);}if($dg&&$_POST["schema_style"]&&function_exists('Adminer\use_schema_sql'))echo
use_schema_sql($_GET["ns"],$_POST["schema_style"]).";\n\n";$jm=($_POST["table_style"]||$_POST["data_style"]?table_status('',true):[]);$Pd=[];$tc=[];foreach($jm
as$B=>$S){if($wa||in_array($B,(array)$_POST["tables"]))$Pd[$B]=$S;if($wa||in_array($B,(array)$_POST["data"]))$tc[$B]=$S;}if($dg){if($_POST["table_style"]=="DROP+CREATE"&&function_exists('Adminer\drop_sql'))echo
drop_sql($Pd);if($_POST["data_style"]=="TRUNCATE+INSERT"&&function_exists('Adminer\truncate_all_sql')){$wn=[];foreach($tc
as$B=>$S){if(!is_view($S)&&!($_POST["table_style"]=="DROP+CREATE"&&isset($Pd[$B])))$wn[]=$B;}echo
truncate_all_sql($wn);}$Yi="";if($_POST["types"]){foreach(types()as$t=>$U){$Ec=type_definition($t);$ii=($Ec["kind"]=='d'?"DOMAIN":"TYPE");if($Ec["definition"])$Yi
.=($nm!='DROP+CREATE'?"DROP $ii IF EXISTS ".table($U).";;\n":"")."CREATE $ii ".table($U)." $Ec[definition];\n\n";else$Yi
.="-- Could not export type ".preg_replace('~[\r\n]+~',' ',$U)."\n\n";}}if($_POST["routines"]){foreach(routines()as$J){$B=$J["ROUTINE_NAME"];$Ok=$J["ROUTINE_TYPE"];$Kf=routine($J["SPECIFIC_NAME"],$Ok);if($Kf["definition"]!=""){$i=create_routine($Ok,["name"=>$B]+$Kf);set_utf8mb4($i);$Yi
.=($nm!='DROP+CREATE'?"DROP $Ok IF EXISTS ".table($B).";;\n":"")."$i;\n\n";}else$Yi
.="-- Could not export ".strtolower($Ok)." ".preg_replace('~[\r\n]+~',' ',$B)."\n\n";}}if($_POST["events"]){foreach(get_rows("SHOW EVENTS",null,"-- ")as$J){$i=remove_definer(get_val("SHOW CREATE EVENT ".idf_escape($J["Name"]),3));set_utf8mb4($i);$Yi
.=($nm!='DROP+CREATE'?"DROP EVENT IF EXISTS ".idf_escape($J["Name"]).";;\n":"")."$i;;\n\n";}}echo($Yi&&JUSH=='sql'?"DELIMITER ;;\n\n$Yi"."DELIMITER ;\n\n":$Yi);}if($_POST["table_style"]||$_POST["data_style"]){$no=[];foreach($jm
as$B=>$S){$R=array_key_exists($B,$Pd);$rc=array_key_exists($B,$tc);if($R||$rc){$en=null;if($Qd=="tar"){$en=new
TmpFile;ob_start([$en,'write'],1e5);}adminer()->dumpTable($B,($R?$_POST["table_style"]:""),(is_view($S)?2:0));if(is_view($S))$no[]=$B;elseif($rc){$o=fields($B);$M=["*"];$ec=convert_fields($o,$o);if($ec)$M[]=substr($ec,2);adminer()->dumpData($B,$_POST["data_style"],"",$M);}if($dg&&$_POST["triggers"]&&$R&&($vn=trigger_sql($B)))echo"\nDELIMITER ;;\n$vn\nDELIMITER ;\n";if($Qd=="tar"){ob_end_flush();tar_file((DB!=""?"":"$k/")."$B.csv",$en);}elseif($dg)echo"\n";}}if($dg&&$_POST["table_style"]&&function_exists('Adminer\foreign_keys_sql')){foreach($Pd
as$B=>$S){if(!is_view($S))echo
foreign_keys_sql($B);}}if($dg){foreach($no
as$mo)adminer()->dumpTable($mo,$_POST["table_style"],1);}if($Qd=="tar")echo
pack("x1024");}}}}adminer()->dumpFooter();exit;}page_header('Export',$m,($_GET["export"]!=""?["table"=>$_GET["export"]]:[]),h(DB));echo'
<form method="post">
<table class="layout">
';$xc=['','USE','DROP+CREATE','CREATE'];$Zk=(JUSH=="mssql"?['','DROP+CREATE','CREATE']:$xc);$Im=['','DROP+CREATE','CREATE'];$sc=['','TRUNCATE+INSERT','INSERT'];if(JUSH=="sql")$sc[]='INSERT+UPDATE';$J=get_settings("adminer_export");if(!$J)$J=["output"=>"text","format"=>"sql","db_style"=>(DB!=""?"":"CREATE"),"schema_style"=>"","table_style"=>"DROP+CREATE","data_style"=>"INSERT"];echo"<tr><th>".'Output'."<td>".html_radios("output",adminer()->dumpOutput(),$J["output"])."\n","<tr><th>".'Format'."<td>".html_radios("format",adminer()->dumpFormat(),$J["format"])."\n",(JUSH=="sqlite"?"":"<tr><th>".'Database'."<td>".html_select('db_style',$xc,$J["db_style"]).(support("type")?checkbox("types",1,$J["types"],'User types'):"").(support("routine")?checkbox("routines",1,$J["routines"],'Routines'):"").(support("event")?checkbox("events",1,$J["events"],'Events'):"")),(function_exists('Adminer\use_schema_sql')?"<tr><th>".'Schema'."<td>".html_select('schema_style',$Zk,$J["schema_style"]):""),"<tr><th>".'Tables'."<td>".html_select('table_style',$Im,$J["table_style"]).checkbox("auto_increment",1,$J["auto_increment"],'Auto Increment').(support("trigger")?checkbox("triggers",1,$J["triggers"],'Triggers'):""),"<tr><th>".'Data'."<td>".html_select('data_style',$sc,$J["data_style"]),'</table>
';adminer()->dumpPrint();echo'<p><input type=\'submit\' value=\'Export\'>
',input_token(),'
<table',on('click','dumpClick'),'>
';$Rj=[];if($_GET["ns"]===""&&support("scheme")){echo"<thead><tr><th style='text-align: left;'>","<label class='block'><input type='checkbox' id='check-schemas' checked class='jsonly' title='".'All'."'".on('click','formCheck','^schemas\[').">".'Schema'."</label>","<tbody>\n";foreach(adminer()->schemas()as$L){if(!information_schema(DB,$L))echo"<tr><td>".checkbox("schemas[]",$L,true,$L,"","block")."\n";}}elseif(DB!=""){$ub=($a!=""?"":" checked");echo"<thead><tr>","<th style='text-align: left;'><label class='block'><input type='checkbox' id='check-tables'$ub class='jsonly' title='".'All'."'".on('click','formCheck','^tables\[').">".'Table'."</label>","<th style='text-align: right;'><label class='block'>".'Data'."<input type='checkbox' id='check-data'$ub class='jsonly' title='".'All'."'".on('click','formCheck','^data\[')."></label>","<tbody>\n";$no="";$Lm=tables_list();foreach($Lm
as$B=>$U){$Qj=preg_replace('~_.*~','',$B);$ub=($a==""||$a==(substr($a,-1)=="%"?"$Qj%":$B));$Xj="<tr><td>".checkbox("tables[]",$B,$ub,$B,"","block");if($U!==null&&!preg_match('~table~i',$U))$no
.="$Xj\n";else
echo"$Xj<td align='right'><label class='block'><span id='Rows-".h($B)."'></span>".checkbox("data[]",$B,$ub)."</label>\n";$Rj[$Qj]++;}echo$no;if($Lm)echo
script("ajaxSetHtml('".js_escape(ME)."script=db');");}else{$j=adminer()->databases();echo"<thead><tr><th style='text-align: left;'>","<label class='block'>".($j?"<input type='checkbox' id='check-databases'".($a==""?" checked":"")." class='jsonly' title='".'All'."'".on('click','formCheck','^databases\[').">":"").'Database'."</label>","<tbody>\n";if($j){foreach($j
as$k){if(!information_schema($k)){$Qj=preg_replace('~_.*~','',$k);echo"<tr><td>".checkbox("databases[]",$k,$a==""||$a=="$Qj%",$k,"","block")."\n";$Rj[$Qj]++;}}}else
echo"<tr><td><textarea name='databases' rows='10' cols='20'></textarea>";}echo'</table>
</form>
';$ke=true;foreach($Rj
as$x=>$X){if($x!=""&&$X>1){echo($ke?"<p>":" ")."<a href='".h(ME)."dump=".url_escape("$x%")."'>".h($x)."</a>";$ke=false;}}}elseif(isset($_GET["privileges"])){page_header('Privileges');echo'<p class="links"><a href="'.h(ME).'user=">'.'Create user'."</a>";$H=connection()->query("SELECT User, Host FROM mysql.".(DB==""?"user":"db WHERE ".q(DB)." LIKE Db")." ORDER BY Host, User");$Je=$H;if(!$H)$H=connection()->query("SELECT SUBSTRING_INDEX(CURRENT_USER, '@', 1) AS User, SUBSTRING_INDEX(CURRENT_USER, '@', -1) AS Host");echo"<form><p>\n";hidden_fields_get();echo
input_hidden("db",DB),($Je?"":input_hidden("grant")),"<table class='odds'>\n","<thead><tr><th>".'Username'."<th>".'Server'."<td class='hover'><tbody>\n";while($J=$H->fetch_assoc())echo'<tr><td>'.h($J["User"]),"<td>".h($J["Host"]),'<td class="hover"><a href="'.h(ME.'user='.url_escape($J["User"]).'&host='.url_escape($J["Host"])).'">'.'Edit'."</a>\n";if(!$Je||DB!="")echo"<tr><td><input name='user' autocapitalize='off'>","<td><input name='host' value='localhost' autocapitalize='off'>","<td class='hover'><input type='submit' value='".'Edit'."'>\n";echo"</table>\n","</form>\n";}elseif(isset($_GET["sql"])){$Jg=driver()->lineComment();$Tl="(?:\\s|\xEF\xBB\xBF|/\\*[\s\S]*?\\*/|(?:$Jg)[^\n]*\n?|--\r?\n)";$cm="~^(?:$Tl|;)*+(ATTACH|VACUUM\\b.*\\bINTO)\\b~is";if(!$m&&$_POST["export"]&&JUSH=="sqlite"&&preg_match($cm,$_POST["query"],$A)!==0)$m=sprintf('%s queries are not supported.',preg_match('~ATTACH~i',$A[1])?'ATTACH':'VACUUM INTO');if(!$m&&$_POST["export"]){save_settings(["output"=>$_POST["output"],"format"=>$_POST["format"]],"adminer_import");dump_headers("sql");if($_POST["format"]=="sql")echo"$_POST[query]\n";else{adminer()->dumpTable("","");adminer()->dumpData("","table",$_POST["query"]);adminer()->dumpFooter();}exit;}if(!$m&&$_POST["val"]){$ra=0;$om=true;$lb=[];$Wk=0;foreach($_POST["val"]as$K)$Wk+=count($K);$Wa=$Wk>1&&driver()->begin();foreach($_POST["val"]as$zm=>$K){$R=bracket_escape($zm,true);$o=fields($R);$_m=indexes($R);foreach($K
as$u=>$J){parse_str(bracket_escape($u,true),$Z);$Fn=[];foreach($Z["where"]as$x=>$X)$Fn[bracket_escape($x,true)]=$X;if(!$o||$Z["null"]||array_diff_key($Fn,$o)||!unique_array($Fn,$_m)){$om=false;break
2;}$O=[];$M=[];foreach($J
as$mg=>$X){$x=bracket_escape($mg,true);$n=idx($o,$x);if(!$n){$om=false;break
3;}$O[idf_escape($x)]=(preg_match('~char|text~',$n["type"])||$X!=""?adminer()->processInput($n,$X):"NULL");$M[$mg]=$x;}$mk=where($Z,$o);if(!driver()->update($R,$O," WHERE $mk",0," ")){$om=false;break
2;}$ra+=connection()->affected_rows;$e=[];foreach($M
as$x)$e[]=idf_escape($x);$Pn=driver()->select($R,$e,[$mk],$e);$Wh=($Pn?$Pn->fetch_row():[]);$hg=0;foreach($M
as$mg=>$x){$n=$o[$x];$mm=['type'=>(preg_match('~binary~',$n["type"])?'blob':$n["type"])];$lb["val[$zm][$u][$mg]"]=select_value(idx($Wh,$hg++),"",$mm,null);}}}if($Wa&&$om)$om=driver()->commit();queries_redirect(null,lang_format(['%d item has been affected.','%d items have been affected.'],$ra),$om);if($Wa&&!$om)driver()->rollback();page_headers();page_messages($m);foreach($lb
as$B=>$X)echo"<div data-name='".h($B)."' hidden>$X</div>\n";exit;}restart_session();$mf=&get_session("queries");$lf=&$mf[DB];if(!$m&&$_POST["clear"]){$lf=[];redirect(remove_from_uri("history"));}stop_session();$pa=get_settings("adminer_import");if($_POST&&$pa)save_settings($pa,"adminer_import");page_header((isset($_GET["import"])?'Import':'SQL command'),$m);$Ad=0;function
query_error_line($Um,$C,$G,$Ig){return
substr_count(substr($Um,0,$C),"\n")+($Ig?:substr_count(substr($G,0,strspn($G," \t\r\n")),"\n")+1);}if(!$m&&$_POST&&!(isset($_GET["import"])&&adminer()->importProcess())){$Gc=driver()->delimiter;$ye=false;if(!isset($_GET["import"]))$G=$_POST["query"];elseif($_POST["webfile"]){$Xl=adminer()->importServerPath();$ye=@fopen((file_exists($Xl)?$Xl:"compress.zlib://$Xl.gz"),"rb");$G=($ye?fread($ye,1e6):false);}else$G=get_file("sql_file",true,$Gc);if(is_string($G)){if(($kh=ini_bytes("memory_limit"))!="-1")ini_set("memory_limit",max($kh,strval(2*strlen($G)+memory_get_usage()+8e6)));if($G!=""&&strlen($G)<1e6){$gk=$G.(preg_match("~$Gc\\s*\$~",$G)?"":$Gc);if(!$lf||first(end($lf))!=$gk){restart_session();$lf[]=[$gk,time()];set_session("queries",$mf);stop_session();}}$C=0;$kk=0;$sd=true;$gc=false;$rh=false;$h=connect();if($h&&DB!=""){$h->select_db(DB);if($_GET["ns"]!="")set_schema($_GET["ns"],$h);}$Kb=0;$Cd=[];$jj='[\'"'.(JUSH=="sql"?'`':(JUSH=="sqlite"?'`[':(JUSH=="mssql"?'[':''))).']|/\*|'.$Jg.'|$'.(JUSH=="pgsql"?'|\$([a-zA-Z]\w*)?\$':'');$jn=microtime(true);while($G!=""){if(!$C&&preg_match("~^$Tl*+DELIMITER\\s+(\\S+)~i",$G,$A)){$Gc=preg_quote($A[1]);$G=substr($G,strlen($A[0]));$kk+=strlen($A[0]);}elseif(!$C&&JUSH=='pgsql'&&preg_match("~^($Tl*+COPY\\s+)[^;]+\\s+FROM\\s+stdin;~i",$G,$A)){$Gc="\n\\\\\\.\r?\n";$gc=true;$C=strlen($A[0]);}elseif(!$C&&JUSH=='pgsql'&&preg_match("~^$Tl*+\\\\connect[ \t][^\n]*~",$G,$A)){$Gc="\n";$rh=true;$C=strlen($A[0]);}else{preg_match("($Gc\\s*|$jj)",$G,$A,PREG_OFFSET_CAPTURE,$C);list($we,$F)=$A[0];if(!$we&&$ye&&!feof($ye))$G
.=fread($ye,1e5);else{if(!$we&&rtrim($G)=="")break;$C=$F+strlen($we);if($we&&!preg_match("(^$Gc)",$we)){$gb=driver()->hasCStyleEscapes()||(JUSH=="pgsql"&&($F>0&&strtolower($G[$F-1])=="e"));$_j=($we=='/*'?'\*/':($we=='['?']':(preg_match("~^(?:$Jg)~",$we)?"\n":preg_quote($we).($gb?'|\\\\.':''))));while(preg_match("($_j|\$)s",$G,$A,PREG_OFFSET_CAPTURE,$C)){$Xk=$A[0][0];if(!$Xk&&$ye&&!feof($ye))$G
.=fread($ye,1e5);else{$C=$A[0][1]+strlen($Xk);if(!$Xk||$Xk[0]!="\\")break;}}}else{$gk=substr($G,0,$F+($gc?3:0));$G=substr($G,$C);$hk=$kk;$kk+=$C;$C=0;if($gc||$rh){$Gc=driver()->delimiter;$gc=false;$rh=false;}$Cb="<code class='jush-".JUSH."'>".adminer()->sqlCommandQuery($gk)."</code>";if(preg_match("~^$Tl*+\$~",$gk)&&!preg_match('~/\*M?!~',$gk)){echo($_POST["only_errors"]?"":"<pre>$Cb</pre>\n");continue;}$sd=false;$Kb++;$Xj="<pre id='sql-$Kb'>$Cb</pre>\n";if(JUSH=="sqlite"&&preg_match($cm,$gk,$A)!==0){echo$Xj,"<p class='error'>".sprintf('%s queries are not supported.',preg_match('~ATTACH~i',$A[1])?'ATTACH':'VACUUM INTO')."\n";$Cd[]=" <a href='#sql-$Kb'>$Kb</a>";if(!$Ad&&!isset($_GET["import"]))$Ad=query_error_line($_POST["query"],$hk,$gk,0);if($_POST["error_stops"])break;}else{if(!$_POST["only_errors"]){echo$Xj;ob_flush();flush();}$fm=microtime(true);if(JUSH=="pgsql"&&preg_match("~^$Tl*+\\\\connect\\s+(\"(?:[^\"]|\"\")+\"|[^\\s\";]+)\\s*;*\\s*\$~",$gk,$A)){$k=idf_unescape($A[1]);if(connection()->select_db($k)){if($h)$h->select_db($k);echo($_POST["only_errors"]?"":"<p class='message'>".'Database'.": ".h($k)." <span class='time'>(".format_time($fm).")</span>\n");continue;}}elseif(connection()->multi_query($gk)&&$h&&preg_match("~^$Tl*+USE\\b~i",$gk))$h->query($gk);do{$H=connection()->store_result();if(connection()->error){echo($_POST["only_errors"]?$Xj:""),"<p class='error'>".'Error in query'.(connection()->errno?" (".connection()->errno.")":"").": ".adminer()->error()."\n";$Cd[]=" <a href='#sql-$Kb'>$Kb</a>";if(!$Ad&&!isset($_GET["import"]))$Ad=query_error_line($_POST["query"],$hk,$gk,driver()->errorLine($gk));if($_POST["error_stops"])break
2;}else{$_=ME."sql=".url_escape(trim($gk));$Xm=" <span class='time'>(".format_time($fm).")</span>".(strlen($_)<adminer()->sqlUrlLength()-100?" <a href='".h($_)."'>".'Edit'."</a>":"");$ra=connection()->affected_rows;$ro=($_POST["only_errors"]?"":driver()->warnings());$so="warnings-$Kb";if($ro)$Xm
.=", <a href='#$so' class='toggle'>".'Warnings'."</a>";$Nd="";$Od="explain-$Kb";if(is_object($H)){$z=$_POST["limit"];$gi=$z;$jd=!$_POST["only_errors"];if($jd)echo"<form method='post'>\n";$Oi=print_select_result($H,$h,[],$gi,$jd);if(!$_POST["only_errors"]){$gi=max($H->num_rows,$gi);echo"<p class='sql-footer'>".($gi?($z&&$gi>$z?sprintf('%d / ',$z):"").lang_format(['%d row','%d rows'],$gi):""),$Xm;if($h&&preg_match("~^($Tl|\\()*+SELECT\\b~i",$gk)&&($Nd=adminer()->explain($h,$gk,$Oi))!="")echo", <a href='#$Od' class='toggle'>Explain</a>";if($jd)echo", <input type='submit' name='save' value='".'Save'."' class='jsonly' disabled"." title='".'Ctrl+click on a value to modify it.'."'".on('click','sqlSave','Saving…').">";$t="export-$Kb";echo", <a href='#$t' class='toggle'>".'Export'."</a><span id='$t' class='hidden'>: ".html_select("output",adminer()->dumpOutput(),$pa["output"])." ".html_select("format",adminer()->dumpFormat(),$pa["format"]).input_hidden("query",$gk)."<input type='submit' name='export' value='".'Export'."'".($z?"":on('click','sqlExport')).">".input_token()."</span>\n"."</form>\n";}}else{if(preg_match("~^$Tl*+(CREATE|DROP|ALTER)$Tl++(DATABASE|SCHEMA)\\b~i",$gk)){restart_session();set_session("dbs",null);stop_session();}if(!$_POST["only_errors"])echo"<p class='message' title='".h(connection()->info)."'>".lang_format(['Query executed OK, %d row affected.','Query executed OK, %d rows affected.'],$ra)."$Xm\n";}echo($ro?"<div id='$so' class='hidden'>\n$ro</div>\n":""),($Nd!=""?"<div id='$Od' class='hidden explain'>\n$Nd</div>\n":"");}$fm=microtime(true);}while(connection()->next_result());}}}}}if($sd)echo"<p class='message'>".'No commands to execute.'."\n";else{$Af=connection()->inTransaction();driver()->rollback();if($Af)echo"<pre><code class='jush-".JUSH."'>ROLLBACK".(JUSH=="mssql"?" TRANSACTION":"")." -- Adminer</code></pre>\n";if($_POST["only_errors"])echo"<p class='message'>".lang_format(['%d query executed OK.','%d queries executed OK.'],$Kb-count($Cd))," <span class='time'>(".format_time($jn).")</span>\n";elseif($Cd&&$Kb>1)echo"<p class='error'>".'Error in query'.": ".implode("",$Cd)."\n";}}else
echo"<p class='error'>".upload_error($G)."\n";}echo'
<form method="post" enctype="multipart/form-data" id="form"';$Qn="";if(!isset($_GET["import"]))echo
on('submit','sqlSubmit',remove_from_uri("sql|limit|error_stops|only_errors|history"),adminer()->sqlUrlLength());else
echo
on_upload_progress($Qn);echo'>
';$Kd="<input type='submit' value='".'Execute'."' title='Ctrl+Enter'>";if(!isset($_GET["import"])){$gk=$_GET["sql"];if($_POST)$gk=$_POST["query"];elseif($_GET["history"]=="all")$gk=$lf;elseif($_GET["history"]!="")$gk=idx($lf[$_GET["history"]],0);echo"<p>";textarea("query",$gk,20,80,JUSH,$Ad);echo($_POST?"":script("qs('textarea').focus();")),"<p>";adminer()->sqlPrintAfter();echo"$Kd\n",'Limit rows'.": <input type='number' name='limit' class='size' value='".h($_POST?$_POST["limit"]:$_GET["limit"])."'>\n";}else{$Te=(extension_loaded("zlib")?"[.gz]":"");echo"<fieldset><legend>".'File upload'."</legend><div>",($Qn?input_hidden(ini_get("session.upload_progress.name"),$Qn):""),"SQL$Te: ".file_input(" name='sql_file[]' multiple","\n$Kd"),($Qn?" <progress class='jsonly hidden' max='1' value='0'></progress>":""),"</div></fieldset>\n";$yf=adminer()->importServerPath();if($yf)echo"<fieldset><legend>".'From server'."</legend><div>",sprintf('Webserver file %s',"<code>".h($yf)."$Te</code>")," <input type='submit' name='webfile' value='".'Run file'."'>","</div></fieldset>\n";adminer()->importPrint();echo"<p>";}echo
checkbox("error_stops",1,($_POST?$_POST["error_stops"]:isset($_GET["import"])||$_GET["error_stops"]),'Stop on error')."\n",checkbox("only_errors",1,($_POST?$_POST["only_errors"]:isset($_GET["import"])||$_GET["only_errors"]),'Show only errors')."\n",input_token();if(!isset($_GET["import"])&&$lf){print_fieldset("history",'History',$_GET["history"]!="");for($X=end($lf);$X;$X=prev($lf)){$x=key($lf);list($gk,$Xm,$od)=$X;echo'<div><a href="'.h(ME."sql=&history=$x").'" class="hover">'.'Edit'."</a>"." <span class='time' title='".@date('Y-m-d',$Xm)."'>".@date("H:i:s",$Xm)."</span>"." <code class='sql jush-".JUSH."'>".shorten_utf8(preg_replace('~\s+~',' ',ltrim(preg_replace("~^(?:$Jg).*~m",'',$gk))),80,"</code>").($od?" <span class='time'>($od)</span>":"")."</div>\n";}echo"<input type='submit' name='clear' value='".'Clear'."'>\n","<a href='".h(ME."sql=&history=all")."'>".'Edit all'."</a>\n","</div></fieldset>\n";}echo'</form>
';}elseif(isset($_GET["edit"])){$a=$_GET["edit"];$o=fields($a);$Z=(isset($_GET["select"])?($_POST["check"]&&count($_POST["check"])==1?where_check($_POST["check"][0],$o):""):where($_GET,$o));$Nn=(isset($_GET["select"])?$_POST["edit"]:$Z);foreach($o
as$B=>$n){if((!$Nn&&!isset($n["privileges"]["insert"]))||adminer()->fieldName($n)=="")unset($o[$B]);}if($_POST&&!$m&&!isset($_GET["select"])){$Og=relative_uri((string)$_POST["referer"]);if($_POST["insert"])$Og=($Nn?null:relative_uri());elseif(!preg_match('~^.+&select=.+$~',$Og))$Og=ME."select=".url_escape($a);$w=indexes($a);$Gn=unique_array($_GET["where"],$w);$mk="\nWHERE $Z";if(isset($_POST["delete"]))queries_redirect($Og,'Item has been deleted.',driver()->delete($a,$mk,$Gn?0:1));else{$O=[];foreach($o
as$B=>$n){$X=process_input($n);if($X!==false&&$X!==null)$O[idf_escape($B)]=$X;}if($Nn){if(!$O)redirect($Og);queries_redirect($Og,'Item has been updated.',driver()->update($a,$O,$mk,$Gn?0:1));if(is_ajax()){page_headers();page_messages($m);exit;}}else{$H=driver()->insert($a,$O);$zg=($H?last_id($H):0);queries_redirect($Og,sprintf('Item%s has been inserted.',($zg?" $zg":"")),$H);}}}$J=null;$G="";$Xm="";if($Z){$M=[];$ll=["*"];foreach($o
as$B=>$n){if(isset($n["privileges"]["select"])){$Fa=($_POST["clone"]&&$n["auto_increment"]?"''":convert_field($n));$d=($Fa?"$Fa AS ":"").idf_escape($B);$M[]=$d;if($Fa)$ll[]=$d;}}$J=[];if(!support("table")){$M=["*"];$ll=$M;}if($M){$fm=microtime(true);$H=driver()->select($a,$M,[$Z],$M,[],(isset($_GET["select"])?2:1));$G=str_replace("SELECT ".implode(", ",$M),"SELECT ".implode(", ",$ll),driver()->query);$Xm=format_time($fm);if(!$H)$m=adminer()->error();else{$J=$H->fetch_assoc();if(!$J)$J=false;}if(isset($_GET["select"])&&(!$J||$H->fetch_assoc()))$J=null;}}if(!$o&&driver()->primary!=""){if(!$Z){$H=driver()->select($a,["*"],[],["*"]);$J=($H?$H->fetch_assoc():false);if(!$J)$J=[driver()->primary=>""];}if($J){foreach($J
as$x=>$X){if(!$Z)$J[$x]=null;$o[$x]=["field"=>$x,"null"=>($x!=driver()->primary),"auto_increment"=>($x==driver()->primary)];}}}if($_POST["save"]){$Lj=[];foreach((array)$_POST["fields"]as$x=>$X)$Lj[bracket_escape($x,true)]=$X;$J=$Lj+($J?$J:[]);}edit_form($a,$o,$J,$Nn,$m,$G,$Xm);}elseif(isset($_GET["create"])){function
referencable_primary($ol){$I=[];foreach(table_status('',true)as$Cm=>$R){if($Cm!=$ol&&!$R["dependent"]&&fk_support($R)){foreach(fields($Cm)as$n){if($n["primary"]){if($I[$Cm]){unset($I[$Cm]);break;}$I[$Cm]=$n;}}}}return$I;}$a=$_GET["create"];$oj=driver()->partitionBy;$tj=($oj&&$a!=""?driver()->partitionsInfo($a):[]);$uk=referencable_primary($a);$ue=[];foreach($uk
as$Cm=>$n)$ue[str_replace("`","``",$Cm)."`".str_replace("`","``",$n["field"])]=$Cm;$Si=[];$S=[];$di=false;if($a!=""){$Si=fields($a);$S=table_status1($a);$di=(count($S)<2);}$za=($a==""||driver()->supportsAlterTable($S));$J=$_POST;$J["fields"]=(array)$J["fields"];if($J["auto_increment_col"])$J["fields"][$J["auto_increment_col"]]["auto_increment"]=true;if($_POST&&!$m)save_settings(["comments"=>$_POST["comments"],"defaults"=>$_POST["defaults"]]);if($_POST&&!process_fields($J["fields"])&&!$m){if($_POST["drop"])queries_redirect(substr(ME,0,-1),'Table has been dropped.',drop_tables([$a]));else{$o=[];$xa=[];$Un=false;$se=[];$Ri=reset($Si);$ta=" FIRST";foreach($J["fields"]as$n){$q=$ue[$n["type"]];$yn=($q!==null?$uk[$q]:$n);if($n["field"]!=""){if(!$n["generated"])$n["default"]=null;$n["default_sql"]=($n["generated"]=="DEFAULT SQL");$ck=process_field($n,$yn);$xa[]=[$n["orig"],$ck,$ta];if(!$Ri||$ck!==process_field($Ri,$Ri)){$o[]=[$n["orig"],$ck,$ta];if($n["orig"]!=""||$ta||(JUSH=="sqlite"&&$n["comment"]!=""))$Un=true;}if($q!==null)$se[idf_escape($n["field"])]=($a!=""&&JUSH!="sqlite"?"ADD":" ").format_foreign_key(['table'=>$ue[$n["type"]],'source'=>[$n["field"]],'target'=>[$yn["field"]],'on_delete'=>$n["on_delete"],],object_name("FOREIGN",trim($J["name"]),[$n["field"]]));$ta=" AFTER ".idf_escape($n["field"]);}elseif($n["orig"]!=""){$Un=true;$o[]=[$n["orig"]];}if($n["orig"]!=""){$Ri=next($Si);if(!$Ri)$ta="";}}$rj=[];if(in_array($J["partition_by"],$oj)){foreach($J
as$x=>$X){if(preg_match('~^partition~',$x))$rj[$x]=$X;}foreach($rj["partition_names"]as$x=>$B){if($B==""){unset($rj["partition_names"][$x]);unset($rj["partition_values"][$x]);}}$rj["partition_names"]=array_values($rj["partition_names"]);$rj["partition_values"]=array_values($rj["partition_values"]);if($rj==$tj)$rj=[];}elseif(preg_match("~partitioned~",$S["Create_options"]))$rj=null;$mh='Table has been altered.';if($a==""){cookie("adminer_engine",$J["Engine"]);$mh='Table has been created.';}$B=trim($J["name"]);$Og=ME.(support("table")?"table=":"select=").url_escape($B);$f=($J["Comment"]!=$S["Comment"]?$J["Comment"]:null);$vd=($J["Engine"]&&$J["Engine"]!=$S["Engine"]?$J["Engine"]:"");$H=alter_table($a,$B,(JUSH=="sqlite"&&($Un||$se||$f!==null||$vd)?$xa:$o),$se,$f,$vd,($J["Collation"]&&$J["Collation"]!=$S["Collation"]?$J["Collation"]:""),($J["Auto_increment"]!=""?number($J["Auto_increment"]):""),$rj);if($H&&!Queries::$queries&&$a!=""&&!$o&&!$se)redirect($Og);queries_redirect($Og,$mh,$H);}}$hm=($a!=""?"alter":"create");page_header(($a!=""?'Alter table':'Create table'),$m,["table"=>$a],h($a),$di,doc_link(['sql'=>"$hm-table.html",'mariadb'=>($a!=""?"$hm-table":""),'pgsql'=>"sql-$hm"."table.html",'cockroach'=>"$hm-table",'mssql'=>"t-sql/statements/$hm-table-transact-sql",'sqlite'=>"lang_{$hm}table.html",'oracle'=>"sqlrf/".strtoupper($hm)."-TABLE.html",]));if(!$_POST){$Bn=driver()->types();$J=["Engine"=>$_COOKIE["adminer_engine"],"fields"=>[["field"=>"","type"=>(isset($Bn["int"])?"int":(isset($Bn["integer"])?"integer":"")),"on_update"=>""]],"partition_names"=>[""],];if($a!=""){$J=$S;$J["name"]=$a;$J["fields"]=[];if(!$_GET["auto_increment"])$J["Auto_increment"]="";foreach($Si
as$n){if($n["generated"])$n["default"]=ltrim($n["default"]);$n["generated"]=$n["generated"]?:(isset($n["default"])?($n["default_sql"]?"DEFAULT SQL":"DEFAULT"):"");$J["fields"][]=$n;}if($oj){$J+=$tj;$J["partition_names"][]="";$J["partition_values"][]="";}}}$Gb=flat_collations();$wd=driver()->engines();foreach($wd
as$vd){if(!strcasecmp($vd,$J["Engine"])){$J["Engine"]=$vd;break;}}$Xg=max_input_vars(12,20);if($Xg){$if=(count($J["fields"])>$Xg?"":" hidden");echo"<p".($if?" id='max-fields' data-columns='$Xg'":"")." class='error$if'>".max_input_vars_error()."\n";}echo'
<form method="post" id="form">
<p>
';if(support("columns")||$a==""){echo'Table name'.": <input name='name'".($a==""&&!$_POST?" autofocus":"")." data-maxlength='64' value='".h($J["name"])."' autocapitalize='off'>\n",(!$za?h($S["Engine"])."\n":($wd?html_select("Engine",[""=>"(".'engine'.")"]+$wd,$J["Engine"],on('change','helpClose').on_help_value())."\n":""));if($Gb)echo"<datalist id='collations'>".optionlist($Gb)."</datalist>\n",(preg_match("~sqlite|mssql~",JUSH)?"":"<input list='collations' name='Collation' value='".h($J["Collation"])."' placeholder='(".'collation'.")'>\n");echo"<input type='submit' value='".'Save'."'>\n";}if(support("columns")&&$za){echo"<div class='scrollable'>\n","<table id='edit-fields' class='nowrap'>\n";edit_fields($J["fields"],$Gb,"TABLE",$ue);echo"</table>\n",script("editFields();"),"</div>\n<p>\n",'Auto Increment'.": <input type='number' name='Auto_increment' class='size' value='".h($J["Auto_increment"])."'>\n",checkbox("defaults",1,($_POST?$_POST["defaults"]:get_setting("defaults")),'Default values',on('click','columnShowClick',6),"jsonly");$Nb=($_POST?$_POST["comments"]:get_setting("comments"));if(support("comment")){echo
checkbox("comments",1,$Nb,'Comment',on('click','editingCommentsClick',true),"jsonly").' ';$c=" name='Comment' data-maxlength='".(min_version(5.5)?2048:60)."'".($Nb?"":" class='hidden'");echo
adminer()->commentInput('TABLE',$c,$J["Comment"]);}echo'<p>
<input type=\'submit\' value=\'Save\'>
';}echo'
';if($a!="")echo'<input type=\'submit\' name=\'drop\' value=\'Drop\'',confirm(sprintf('Drop %s?',$a)),'>
';if($oj&&(JUSH=='sql'||$a=="")){$pj=preg_match('~RANGE|LIST~',$J["partition_by"]);print_fieldset("partition",'Partition by',$J["partition_by"]);echo"<p>".html_select("partition_by",array_merge([""],$oj),$J["partition_by"],on('change','partitionByChange').on_help_value('.','PARTITION BY $&'))."\n","(<input name='partition' value='".h($J["partition"])."'>)\n",'Partitions'.": <input type='number' name='partitions' class='size".($pj||!$J["partition_by"]?" hidden":"")."' value='".h($J["partitions"])."'>\n","<table id='partition-table'".($pj?"":" class='hidden'").">\n","<thead><tr><th>".'Partition name'."<th>".'Values'."<tbody>\n";foreach($J["partition_names"]as$x=>$X)echo'<tr>','<td><input name="partition_names[]" value="'.h($X).'" autocapitalize="off"'.($x==count($J["partition_names"])-1?on('input','partitionNameChange'):'').'>','<td><input name="partition_values[]" value="'.h(idx($J["partition_values"],$x)).'">';echo"</table>\n</div></fieldset>\n";}echo
input_token(),'</form>
';}elseif(isset($_GET["indexes"])){$a=$_GET["indexes"];$Gf=["PRIMARY","UNIQUE","INDEX"];$S=table_status1($a,true);$Df=driver()->indexAlgorithms($S);if(preg_match('~MyISAM|M?aria'.(min_version(5.6,'10.0.5')?'|InnoDB':'').'~i',$S["Engine"]))$Gf[]="FULLTEXT";if(preg_match('~MyISAM|M?aria'.(min_version(5.7,'10.2.2')?'|InnoDB':'').'~i',$S["Engine"]))$Gf[]="SPATIAL";if(min_version('',11.7)&&preg_match('~MyISAM|InnoDB~i',$S["Engine"]))$Gf[]="VECTOR";$w=indexes($a);$o=fields($a);$Vj=[];if(JUSH=="mongo"){$Vj=$w["_id_"];unset($Gf[0]);unset($w["_id_"]);}$J=$_POST;if($J)save_settings(["index_options"=>$J["options"]]);if($_POST&&!$m&&!$_POST["add"]&&!$_POST["drop_col"]){$b=[];foreach($J["indexes"]as$v){$B=$v["name"];if(in_array($v["type"],$Gf)){$e=[];$Fg=[];$Kc=[];$Bi=[];$Ef=(support("partial_indexes")?$v["partial"]:"");$Cf=(in_array($v["algorithm"],$Df)?$v["algorithm"]:"");$O=[];ksort($v["columns"]);foreach($v["columns"]as$x=>$d){if($d!=""){$y=idx($v["lengths"],$x);$Ic=idx($v["descs"],$x);$Ai=idx($v["opclasses"],$x);$O[]=($o[$d]?idf_escape($d):$d).($y?"(".(+$y).")":"").($Ai!=""?" ".idf_escape($Ai):"").($Ic?" DESC":"");$e[]=$d;$Fg[]=($y?:null);$Kc[]=$Ic;$Bi[]="$Ai";}}$Ld=$w[$B];if($Ld){ksort($Ld["columns"]);ksort($Ld["lengths"]);ksort($Ld["descs"]);if($v["type"]==$Ld["type"]&&array_values($Ld["columns"])===$e&&(!$Ld["lengths"]||array_values($Ld["lengths"])===$Fg)&&array_values($Ld["descs"])===$Kc&&(!$Ld["opclasses"]||array_values($Ld["opclasses"])===$Bi)&&$Ld["partial"]==$Ef&&(!$Df||$Ld["algorithm"]==$Cf)&&!$Ld["invalid"]){unset($w[$B]);continue;}}if($e)$b[]=[$v["type"],$B,$O,$Cf,$Ef];}}foreach($w
as$B=>$Ld)$b[]=[$Ld["type"],$B,"DROP"];if(!$b)redirect(ME."table=".url_escape($a));queries_redirect(ME."table=".url_escape($a),'Indexes have been altered.',alter_indexes($a,$b));}page_header('Indexes',$m,["table"=>$a],h($a),false,doc_link(['sql'=>"create-index.html",'pgsql'=>"sql-createindex.html",'cockroach'=>"create-index",'mssql'=>"t-sql/statements/create-index-transact-sql",'sqlite'=>"lang_createindex.html",'oracle'=>"sqlrf/CREATE-INDEX.html",]));$de=array_keys($o);if($_POST["add"]){foreach($J["indexes"]as$x=>$v){if($v["columns"][count($v["columns"])]!="")$J["indexes"][$x]["columns"][]="";}$v=end($J["indexes"]);if($v["type"]||array_filter($v["columns"],'strlen'))$J["indexes"][]=["columns"=>[1=>""]];}if(!$J){foreach($w
as$x=>$v){$w[$x]["name"]=$x;$w[$x]["columns"][]="";}$w[]=["columns"=>[1=>""]];$J["indexes"]=$w;}$Fg=(JUSH=="sql"||JUSH=="mssql");$Bi=driver()->indexOpclasses();$Il=($_POST?$_POST["options"]:get_setting("index_options"));$Kh=[];foreach($Gf
as$U)$Kh[$U]=str_replace("{table}",$a,adminer()->namePattern($U));echo'
<form method="post">
<div class="scrollable">
<table class="nowrap odds">
<thead><tr>
<th id="label-type">Index Type
';$wf=" class='idxopts".($Il?"":" hidden")."'";if($Df)echo"<th id='label-algorithm'$wf>".'Algorithm'.doc_link(['sql'=>'create-index.html#create-index-storage-engine-index-types','mariadb'=>'storage-engine-index-types/','pgsql'=>'indexes-types.html','cockroach'=>'create-index#parameters',]);echo'<th><input type="submit" hidden>','Columns'.($Fg?"<span$wf> (".'length'.")</span>":"");if($Fg||support("descidx"))echo
checkbox("options",1,$Il,'Options',on('click','indexOptionsShow'),"jsonly")."\n";echo'<th id="label-name">Name
';if(support("partial_indexes"))echo"<th id='label-condition'$wf>".'Condition';echo'<td><noscript>',icon("plus","add[0]","+",'Add next'),'</noscript>
<tbody>
';if($Vj){echo"<tr><td>PRIMARY<td>";foreach($Vj["columns"]as$x=>$d)echo
select_input(" disabled",array_combine($de,$de),$d),"<label><input disabled type='checkbox'>".'descending'."</label> ";echo"<td><td>\n";}$hg=1;foreach($J["indexes"]as$v){if(!$_POST["drop_col"]||$hg!=key($_POST["drop_col"])){echo"<tr><td>".html_select("indexes[$hg][type]",[-1=>""]+$Gf,$v["type"],on('change','indexesChangeType',$Kh),"label-type");if($Df)echo"<td$wf>".html_select("indexes[$hg][algorithm]",array_merge([""],$Df),$v['algorithm'],"","label-algorithm");echo"<td>";ksort($v["columns"]);$s=1;foreach($v["columns"]as$x=>$d){echo"<span>".select_input(" name='indexes[$hg][columns][$s]' title='".'Column'."'".on('change','indexesChangeColumn',$Kh),($o&&($d==""||$o[$d])?array_combine($de,$de):[]),$d)," <span$wf>",($Fg?"<input type='number' name='indexes[$hg][lengths][$s]' class='size' value='".h(idx($v["lengths"],$x))."' title='".'Length'."'>":"");if($Bi){$Ai=idx($v["opclasses"],$x);echo
html_select("indexes[$hg][opclasses][$s]",[""=>"(".'operator class'.")"]+array_combine($Bi,$Bi)+($Ai!=""?[$Ai=>$Ai]:[]),$Ai),doc_link(['pgsql'=>'indexes-opclass.html']);}echo(support("descidx")?checkbox("indexes[$hg][descs][$s]",1,idx($v["descs"],$x),'descending'):""),"<br>","</span></span>";$s++;}echo"<td><input name='indexes[$hg][name]' value='".h($v["name"])."' autocapitalize='off' aria-labelledby='label-name'>\n";if(support("partial_indexes"))echo"<td$wf><input name='indexes[$hg][partial]' value='".h($v["partial"])."' autocapitalize='off' aria-labelledby='label-condition'>\n";echo"<td>".icon("cross","drop_col[$hg]","x",'Remove',on('click','editingRemoveRow','indexes$1[type]')),(idx($w[$v["name"]],"invalid")?" <span class='error'>INVALID</span>":""),"\n";}$hg++;}echo'</table>
</div>
<p>
<input type=\'submit\' value=\'Save\'>
';if(support("concurrent_indexes"))echo
checkbox("concurrently",1,$_POST["concurrently"],"concurrently").doc_link(['pgsql'=>'sql-createindex.html#SQL-CREATEINDEX-CONCURRENTLY'])."\n";echo
input_token(),'</form>
';}elseif(isset($_GET["database"])){$J=$_POST;if($_POST&&!$m&&!$_POST["add"]){$B=trim($J["name"]);if($_POST["drop"]){$_GET["db"]="";queries_redirect(remove_from_uri("db|database"),'Database has been dropped.',drop_databases([DB]));}elseif($B!==DB){if(DB!=""){$_GET["db"]=$B;queries_redirect(preg_replace('~&db=[^&]*~','',ME)."db=".url_escape($B),'Database has been renamed.',rename_database($B,(string)$J["collation"]));}else{$j=explode("\n",str_replace("\r","",$B));$om=true;$xg="";foreach($j
as$k){if(count($j)==1||$k!=""){if(!create_database($k,(string)$J["collation"]))$om=false;$xg=$k;}}restart_session();set_session("dbs",null);queries_redirect(preg_replace('~&db=[^&]*~','',ME)."db=".url_escape($xg),'Database has been created.',$om);}}else{if(!$J["collation"])redirect(substr(ME,0,-1));query_redirect("ALTER DATABASE ".idf_escape($B).(preg_match('~^[a-z0-9_]+$~i',$J["collation"])?" COLLATE $J[collation]":""),substr(ME,0,-1),'Database has been altered.');}}$hm=(DB!=""?"alter":"create");page_header(DB!=""?'Alter database':'Create database',$m,[],h(DB),false,doc_link(['sql'=>"$hm-database.html",'mariadb'=>(DB!=""?"":"$hm-database"),'pgsql'=>"sql-$hm"."database.html",'cockroach'=>"$hm-database",'mssql'=>"t-sql/statements/$hm-database-transact-sql",'oracle'=>"sqlrf/".strtoupper($hm)."-USER.html",]));$Gb=collations();$B=DB;if($_POST)$B=$J["name"];elseif(DB!="")$J["collation"]=db_collation(DB,$Gb);elseif(JUSH=="sql"){foreach(get_vals("SHOW GRANTS")as$Je){if(preg_match('~ ON (`(([^\\\\`]|``|\\\\.)*)%`\.\*)?~',$Je,$A)&&$A[1]){$B=stripcslashes(idf_unescape("`$A[2]`"));break;}}}echo'
<form method="post">
<p>
',($_POST["add"]||strpos($B,"\n")?"<textarea autofocus name='name' rows='10' cols='40'>\n".h($B).'</textarea><br>':'<input name="name" autofocus value="'.h($B).'" data-maxlength="64" autocapitalize="off">')."\n",($Gb?"<datalist id='collations'>".optionlist(flat_collations())."</datalist>"."<input list='collations' name='collation' value='".h($J["collation"])."' placeholder='(".'collation'.")'>".doc_link(['sql'=>"charset-charsets.html",'mariadb'=>"supported-character-sets-and-collations/",'mssql'=>"relational-databases/system-functions/sys-fn-helpcollations-transact-sql",]):"")."\n",'<input type=\'submit\' value=\'Save\'>
';if(DB!="")echo"<input type='submit' name='drop' value='".'Drop'."'".confirm(sprintf('Drop %s?',DB)).">\n";elseif(!$_POST["add"]&&$_GET["db"]=="")echo
icon("plus","add[0]","+",'Add next')."\n";echo
input_token(),'</form>
';}elseif(isset($_GET["scheme"])){$J=$_POST;$f=(support("comment")&&$_GET["ns"]!=""?schema_comment($_GET["ns"]):"");if($_POST&&!$m){$_=preg_replace('~&ns=[^&]*~','',ME)."ns=";if($_POST["drop"])query_redirect("DROP SCHEMA ".idf_escape($_GET["ns"]),$_,'Schema has been dropped.');else{$B=trim($J["name"]);$_
.=url_escape($B);$Rh=(support("comment")?$J["comment"]:$f);$H=true;$mh='Schema has been altered.';if($_GET["ns"]==""){$H=queries("CREATE SCHEMA ".idf_escape($B));$mh='Schema has been created.';}elseif($_GET["ns"]!=$B)$H=queries("ALTER SCHEMA ".idf_escape($_GET["ns"])." RENAME TO ".idf_escape($B));elseif($Rh===$f)redirect($_);if($H&&$Rh!==$f)$H=set_schema_comment($B,$Rh);queries_redirect($_,$mh,$H);}}$hm=($_GET["ns"]!=""?"alter":"create");page_header($_GET["ns"]!=""?'Alter schema':'Create schema',$m,[],"",false,doc_link(['pgsql'=>"sql-$hm"."schema.html",'cockroach'=>"$hm-schema",'mssql'=>"t-sql/statements/create-schema-transact-sql",]));if(!$J){$J["name"]=$_GET["ns"];$J["comment"]=$f;}echo'
<form method="post">
<p><input name="name" autofocus value="',h($J["name"]),'" autocapitalize="off">
<input type=\'submit\' value=\'Save\'>
';if($_GET["ns"]!="")echo"<input type='submit' name='drop' value='".'Drop'."'".confirm(sprintf('Drop %s?',$_GET["ns"])).">\n";echo(support("comment")?"<p>".'Comment'.": ".adminer()->commentInput('SCHEMA'," name='comment'",$J["comment"])."\n":""),input_token(),'</form>
';}elseif(isset($_GET["call"])){$ba=($_GET["name"]?:$_GET["call"]);$Tk=(isset($_GET["callf"])?"FUNCTION":"PROCEDURE");$Ok=routine($_GET["call"],$Tk);$eg=(idx($Ok["returns"],"type")=="trigger");page_header(($eg?'Triggers':'Call').": ".h($ba),$m,"#routines","",!$Ok,(isset($_GET["callf"])?"":doc_link(['sql'=>"call.html",'pgsql'=>"sql-call.html",'cockroach'=>"call",'mssql'=>"t-sql/language-elements/execute-transact-sql",])));$zf=[];$Yi=[];foreach($Ok["fields"]as$s=>$n){if(substr($n["inout"],-3)=="OUT"&&JUSH=='sql')$Yi[$s]="@".idf_escape($n["field"])." AS ".idf_escape($n["field"]);if(!$n["inout"]||preg_match('~^(IN|OUTPUT)~',$n["inout"]))$zf[]=$s;}if(!$m&&$_POST){$ib=[];foreach($Ok["fields"]as$x=>$n){$X="";if(in_array($x,$zf)){$X=process_input($n);if($X===false)$X="''";if(isset($Yi[$x]))connection()->query("SET @".idf_escape($n["field"])." = $X");}if(isset($Yi[$x]))$ib[]="@".idf_escape($n["field"]);elseif(in_array($x,$zf))$ib[]=$X;}$Da=implode(", ",$ib);$G=(isset($_GET["callf"])||JUSH!="mssql"?(isset($_GET["callf"])?"SELECT ":"CALL ").(idx($Ok["returns"],"type")=="record"?"* FROM ":"").table($ba)."($Da)":"EXEC ".table($ba).($Da!=""?" $Da":""));$fm=microtime(true);$H=connection()->multi_query($G);$ra=connection()->affected_rows;echo
adminer()->selectQuery($G,$fm,!$H);if(!$H)echo"<p class='error'>".adminer()->error()."\n";else{$h=connect();if($h)$h->select_db(DB);do{$H=connection()->store_result();if(is_object($H))print_select_result($H,$h);else
echo"<p class='message'>".lang_format(['Routine has been called, %d row affected.','Routine has been called, %d rows affected.'],$ra)." <span class='time'>".@date("H:i:s")."</span>\n";}while(connection()->next_result());if($Yi)print_select_result(connection()->query("SELECT ".implode(", ",$Yi)));}}if($eg){$vn=driver()->routineTriggers($_GET["call"]);if($vn){echo"<table>\n";foreach($vn
as$J){$_=preg_replace('~&ns=[^&]*~',"&ns=".url_escape($J["ns"]),ME);echo"<tr><th>".h($J["trigger"])."<td><a href='".h($_."table=".url_escape($J["table"])."#triggers")."'>".($J["ns"]!=$_GET["ns"]?"<b>".h($J["ns"])."</b>.":"").h($J["table"])."</a>"."<td class='hover'><a href='".h($_."trigger=".url_escape($J["table"])."&name=".url_escape($J["trigger"]))."'>".'Alter'."</a>\n";}echo"</table>\n";}}else{echo'<form method="post">
';if($zf){echo"<table class='layout'>\n";foreach($zf
as$x){$n=$Ok["fields"][$x];$B=$n["field"];echo"<tr><th>".adminer()->fieldName($n);$Y=idx($_POST["fields"],$B);if($Y!=""){if($n["type"]=="set")$Y=implode(",",$Y);}input($n,$Y,idx($_POST["function"],$B,""));echo"\n";}echo"</table>\n";}echo'<p>
<input type=\'submit\' value=\'Call\'>
',input_token(),'</form>
';}echo'
',adminer()->commentValue($Tk,$Ok['comment']);}elseif(isset($_GET["foreign"])){$a=$_GET["foreign"];$B=$_GET["name"];$J=$_POST;if($_POST&&!$m&&!$_POST["add"]&&!$_POST["change"]&&!$_POST["change-js"]){if(!$_POST["drop"]){$J["source"]=array_filter($J["source"],'strlen');ksort($J["source"]);$Om=[];foreach($J["source"]as$x=>$X)$Om[$x]=$J["target"][$x];$J["target"]=$Om;}$Wa=false;if(JUSH=="sqlite")$H=recreate_table($a,$a,[],[],[" $B"=>($J["drop"]?"":" ".format_foreign_key($J))]);else{$Wb=$J["constraint"];$b="ALTER TABLE ".table($a);$cd="$b DROP ".(JUSH=="sql"?"FOREIGN KEY ":"CONSTRAINT ").idf_escape($B?:'');if($J["drop"])$H=queries($cd);else{$ka=" ADD".format_foreign_key($J,$Wb);if($B=="")$H=queries($b.$ka);elseif(JUSH=="pgsql"||(JUSH=="sql"&&$Wb!=$B))$H=queries("$cd,$ka");else{$Wa=support("transaction_ddl")&&driver()->begin();$H=($Wa||idx(foreign_keys($a),$B)?queries($cd):true)&&queries($b.$ka)&&(!$Wa||driver()->commit());}}}queries_redirect(ME."table=".url_escape($a),($J["drop"]?'Foreign key has been dropped.':($B!=""?'Foreign key has been altered.':'Foreign key has been created.')),$H);if($Wa)driver()->rollback();if(!$J["drop"])$m='Source and target columns must have the same data type, there must be an index on the target columns and the referenced data must exist.';}$di=false;if(!$_POST&&$B!=""){$ue=foreign_keys($a);$J=idx($ue,$B,[]);$di=!$J;}$Jh=str_replace("{table}",$a,adminer()->namePattern("FOREIGN"));$Mh="^".str_replace('\{columns\}','.*',preg_quote($Jh))."$";page_header(($B!=""?'Alter foreign key':'Create foreign key'),$m,["table"=>$a],h($B!=""?$B:$a),$di,doc_link(['sql'=>"innodb-foreign-key-constraints.html",'mariadb'=>"foreign-keys/",'pgsql'=>"sql-createtable.html#SQL-CREATETABLE-PARMS-REFERENCES",'cockroach'=>"foreign-key",'mssql'=>"t-sql/statements/create-table-transact-sql",'oracle'=>"sqlrf/constraint.html",]));if($_POST){ksort($J["source"]);if($_POST["change"]||$_POST["change-js"])$J["target"]=[];else$J["source"][]="";}elseif($B!=""){$J["source"][]="";$J["constraint"]=$B;}else{$J["table"]=$a;$J["source"]=[""];$J["constraint"]=str_replace("{columns}","",$Jh);}echo'
<form method="post">
';$Rl=array_keys(fields($a));if($J["db"]!="")connection()->select_db($J["db"]);$Ti=null;if($J["ns"]!=""){$Ti=get_schema();set_schema($J["ns"]);}$tk=array_keys(array_filter(table_status('',true),function(array$S){return!$S["dependent"]&&fk_support($S);}));$Om=array_keys(fields(in_array($J["table"],$tk)?$J["table"]:reset($tk)));$c=on('change','foreignChange');echo"<p><label>".'Target table'.": ".html_select("table",$tk,$J["table"],$c)."</label>\n";if(support("scheme")){$ei=($J["ns"]!=""?$J["ns"]:$_GET["ns"]);$al=array_filter(adminer()->schemas(),function($L)use($ei){return!driver()->isSystem(DB,$L)||$L==$ei||$L==$_GET["ns"];});echo"<label>".'Schema'.": ".html_select("ns",$al,$ei,$c)."</label>";if($Ti!==null)set_schema($Ti);}elseif(JUSH!="sqlite"){$ml=($J["db"]!=""?$J["db"]:DB);$yc=[];foreach(adminer()->databases()as$k){if(!driver()->isSystem($k)||$k==$ml||$k==DB)$yc[]=$k;}echo"<label>".'DB'.": ".html_select("db",$yc,$ml,$c)."</label>";}if($J["db"]!="")connection()->select_db(DB);echo
input_hidden("change-js"),'<noscript><p><input type=\'submit\' name=\'change\' value=\'Change\'></noscript>
<table',on('change','foreignName',$Mh,$Jh),'>
<thead><tr><th id="label-source">Source<th id="label-target">Target<tbody>
';$hg=0;foreach($J["source"]as$x=>$X){echo"<tr>","<td>".html_select("source[".(+$x)."]",[-1=>""]+$Rl,$X,($hg==count($J["source"])-1?on('change','foreignAddRow'):""),"label-source"),"<td>".html_select("target[".(+$x)."]",$Om,idx($J["target"],$x),"","label-target");$hg++;}echo'</table>
<p>
<label>ON DELETE: ',html_select("on_delete",[-1=>""]+explode("|",driver()->onActions),$J["on_delete"]),'</label>
<label>ON UPDATE: ',html_select("on_update",[-1=>""]+explode("|",driver()->onActions),$J["on_update"]),'</label>
',(support("deferrable")?html_select("deferrable",['NOT DEFERRABLE','DEFERRABLE','DEFERRABLE INITIALLY DEFERRED'],$J["deferrable"]):''),'<p>
<input type=\'submit\' value=\'Save\'>
<noscript><p><input type=\'submit\' name=\'add\' value=\'Add column\'></noscript>
';if($B!="")echo'<input type=\'submit\' name=\'drop\' value=\'Drop\'',confirm(sprintf('Drop %s?',$B)),'>
';if(JUSH!="sqlite"){print_fieldset("options",'Options',$J["constraint"]!=$B&&!preg_match("($Mh)",$J["constraint"]));echo"<label>".'Name'.": <input name='constraint' value='".h($J["constraint"])."' autocapitalize='off'></label>\n","</div></fieldset>\n";}echo
input_token(),'</form>
';}elseif(isset($_GET["view"])){$a=$_GET["view"];$J=$_POST;$Ui="VIEW";if(JUSH=="pgsql"&&$a!=""){$P=table_status1($a);$Ui=strtoupper($P["Engine"]);}if($_POST&&!$m){$B=trim($J["name"]);$Fa=" AS\n$J[select]";$Og=ME."table=".url_escape($B);$mh='View has been altered.';$U=($_POST["materialized"]?"MATERIALIZED VIEW":"VIEW");if(!$_POST["drop"]&&$a==$B&&JUSH!="sqlite"&&$U=="VIEW"&&$Ui=="VIEW")query_redirect((JUSH=="mssql"?"ALTER":"CREATE OR REPLACE")." VIEW ".table($B).$Fa,$Og,$mh);else{$Sm="adminer_".uniqid();drop_create("DROP $Ui ".table($a),"CREATE $U ".table($B).$Fa,"DROP $U ".table($B),"CREATE $U ".table($Sm).$Fa,"DROP $U ".table($Sm),($_POST["drop"]?substr(ME,0,-1):$Og),'View has been dropped.',$mh,'View has been created.',$a,$B);}}$di=false;if(!$_POST&&$a!=""){$J=view($a);$di=!$J["select"];$J["name"]=$a;$J["materialized"]=($Ui!="VIEW");if(!$m)$m=adminer()->error();}page_header(($a!=""?'Alter view':'Create view'),$m,["table"=>$a],h($a),$di,doc_link(['sql'=>"create-view.html",'pgsql'=>"sql-createview.html",'cockroach'=>"create-view",'mssql'=>"t-sql/statements/create-view-transact-sql",'sqlite'=>"lang_createview.html",'oracle'=>"sqlrf/CREATE-VIEW.html",]));echo'
<form method="post">
<p>Name: <input name="name" value="',h($J["name"]),'" data-maxlength="64" autocapitalize="off">
',(support("materializedview")?" ".checkbox("materialized",1,$J["materialized"],'Materialized view'):""),'<p>';textarea("select",$J["select"]);echo'<p>
<input type=\'submit\' value=\'Save\'>
';if($a!="")echo'<input type=\'submit\' name=\'drop\' value=\'Drop\'',confirm(sprintf('Drop %s?',$a)),'>
';echo
input_token(),'</form>
';}elseif(isset($_GET["event"])){$aa=$_GET["event"];$Uf=["YEAR","QUARTER","MONTH","DAY","HOUR","MINUTE","WEEK","SECOND","YEAR_MONTH","DAY_HOUR","DAY_MINUTE","DAY_SECOND","HOUR_MINUTE","HOUR_SECOND","MINUTE_SECOND"];$jm=["ENABLED"=>"ENABLE","DISABLED"=>"DISABLE","SLAVESIDE_DISABLED"=>"DISABLE ON SLAVE"];$J=$_POST;if($_POST&&!$m){if($_POST["drop"])query_redirect("DROP EVENT ".idf_escape($aa),substr(ME,0,-1),'Event has been dropped.');elseif(in_array($J["INTERVAL_FIELD"],$Uf)&&isset($jm[$J["STATUS"]])){$Yk="\nON SCHEDULE ".($J["INTERVAL_VALUE"]?"EVERY ".q($J["INTERVAL_VALUE"])." $J[INTERVAL_FIELD]".($J["STARTS"]?" STARTS ".q($J["STARTS"]):"").($J["ENDS"]?" ENDS ".q($J["ENDS"]):""):"AT ".q($J["STARTS"]))." ON COMPLETION".($J["ON_COMPLETION"]?"":" NOT")." PRESERVE";queries_redirect(substr(ME,0,-1),($aa!=""?'Event has been altered.':'Event has been created.'),queries(($aa!=""?"ALTER EVENT ".idf_escape($aa).$Yk.($aa!=$J["EVENT_NAME"]?"\nRENAME TO ".idf_escape($J["EVENT_NAME"]):""):"CREATE EVENT ".idf_escape($J["EVENT_NAME"]).$Yk)."\n".$jm[$J["STATUS"]]." COMMENT ".q($J["EVENT_COMMENT"]).rtrim(" DO\n$J[EVENT_DEFINITION]",";").";"));}}$di=false;if(!$J&&$aa!=""){$K=get_rows("SELECT * FROM information_schema.EVENTS WHERE EVENT_SCHEMA = ".q(DB)." AND EVENT_NAME = ".q($aa));$di=!$K;$J=reset($K);}page_header(($aa!=""?'Alter event'.": ".h($aa):'Create event'),$m,"#events","",$di,doc_link(['sql'=>"create-event.html"]));echo'
<form method="post">
<table class="layout">
<tr><th>Name<td><input name="EVENT_NAME" value="',h($J["EVENT_NAME"]),'" data-maxlength="64" autocapitalize="off">
<tr><th title="datetime">Start<td><input name="STARTS" value="',h("$J[EXECUTE_AT]$J[STARTS]"),'">
<tr><th title="datetime">End<td><input name="ENDS" value="',h($J["ENDS"]),'">
<tr><th>Every
<td><input type="number" name="INTERVAL_VALUE" value="',h($J["INTERVAL_VALUE"]),'" class="size"> ',html_select("INTERVAL_FIELD",$Uf,$J["INTERVAL_FIELD"]),'<tr><th>Status<td>',html_select("STATUS",$jm,$J["STATUS"]),'<tr><th>Comment<td><input name="EVENT_COMMENT" value="',h($J["EVENT_COMMENT"]),'" data-maxlength="64">
<tr><th><td>',checkbox("ON_COMPLETION","PRESERVE",$J["ON_COMPLETION"]=="PRESERVE",'On completion preserve'),'</table>
<p>';textarea("EVENT_DEFINITION",$J["EVENT_DEFINITION"]);echo'<p>
<input type=\'submit\' value=\'Save\'>
';if($aa!="")echo'<input type=\'submit\' name=\'drop\' value=\'Drop\'',confirm(sprintf('Drop %s?',$aa)),'>
';echo
input_token(),'</form>
';}elseif(isset($_GET["procedure"])){$ba=($_GET["name"]?:$_GET["procedure"]);$Ok=(isset($_GET["function"])?"FUNCTION":"PROCEDURE");$J=$_POST;$J["fields"]=(array)$J["fields"];if($_POST&&!process_fields($J["fields"])&&!$m){foreach($J["fields"]as$x=>$n){if($n["field"]=="")unset($J["fields"][$x]);}$ui=routine($_GET["procedure"],$Ok);$si=($ui?routine_id($ba,$ui):"");$Th=routine_id($J["name"],$J);$i=create_routine($Ok,$J);$Og=substr(ME,0,-1);$mh='Routine has been altered.';if(!$_POST["drop"]&&$si==$Th&&connection()->flavor!="mysql")queries_redirect($Og,$mh,queries(substr_replace($i,(JUSH=="mssql"?' OR ALTER':' OR REPLACE'),6,0)));else{$Sm="adminer_".uniqid();drop_create("DROP $Ok $si",$i,"DROP $Ok $Th",create_routine($Ok,["name"=>$Sm]+$J),"DROP $Ok ".routine_id($Sm,$J),$Og,'Routine has been dropped.',$mh,'Routine has been created.',$ba,$J["name"]);}}$di=false;if(!$_POST&&$ba!=""){$J=routine($_GET["procedure"],$Ok);$di=!$J;$J["name"]=$ba;}$Rk=strtolower($Ok);page_header(($ba!=""?(isset($_GET["function"])?'Alter function':'Alter procedure').": ".h($ba):(isset($_GET["function"])?'Create function':'Create procedure')),$m,"#routines","",$di,doc_link(['sql'=>"create-procedure.html",'mariadb'=>"create-$Rk/",'pgsql'=>"sql-create$Rk.html",'cockroach'=>"create-$Rk",'mssql'=>"t-sql/statements/create-$Rk-transact-sql",]));if(!$_POST&&$ba=="")$J["language"]="sql";$Gb=(JUSH=="sql"?flat_collations():[]);$Pk=routine_languages();echo($Gb?"<datalist id='collations'>".optionlist($Gb)."</datalist>":""),'
<form method="post" id="form">
<p>Name: <input name="name" value="',h($J["name"]),'" data-maxlength="64" autocapitalize="off">
',($Pk?"<label>".'Language'.": ".html_select("language",array_keys($Pk),$J["language"],on('change','routineLanguage',$Pk))."</label>\n":""),'<input type=\'submit\' value=\'Save\'>
<div class="scrollable">
<table id="edit-fields" class="nowrap">
';edit_fields($J["fields"],$Gb,$Ok);if(isset($_GET["function"])){echo"<tr><td>".'Return type';edit_type("returns",(array)$J["returns"],$Gb,[],(JUSH=="pgsql"?["void","trigger"]:[]));}echo'</table>
',script("editFields();"),'</div>
<p>';textarea("definition",$J["definition"],20,80,($Pk[$J["language"]]?:JUSH));echo'<p>
<input type=\'submit\' value=\'Save\'>
';if($ba!="")echo'<input type=\'submit\' name=\'drop\' value=\'Drop\'',confirm(sprintf('Drop %s?',$ba)),'>
';$Sk=routine_options($Ok);if($Sk){$Ii=false;foreach($Sk
as$x=>$io){$l=($io?reset($io):"");$J["options"][$x]=idx($J["options"],$x,$l);if($J["options"][$x]!=$l)$Ii=true;}print_fieldset("options",'Options',$Ii);echo"<table class='layout'>\n";foreach($Sk
as$x=>$io){$sg="label-option-$x";$an=str_replace("_"," ",$x);$M=[];foreach($io
as$Y)$M[$Y]=(strpos($Y,"$an ")===0?substr($Y,strlen($an)+1):$Y);echo"<tr><th id='$sg'>$an<td>".($M?html_select("options[$x]",$M,$J["options"][$x],"",$sg):"<input name='options[$x]' value='".h($J["options"][$x])."' aria-labelledby='$sg' autocapitalize='off'>")."\n";}echo"</table>\n</div></fieldset>\n";}echo
input_token(),'</form>
';}elseif(isset($_GET["sequence"])){$da=$_GET["sequence"];$J=$_POST;if($_POST&&!$m){$_=substr(ME,0,-1);$B=trim($J["name"]);if($_POST["drop"])query_redirect("DROP SEQUENCE ".idf_escape($da),$_,'Sequence has been dropped.');elseif($da=="")query_redirect("CREATE SEQUENCE ".idf_escape($B),$_,'Sequence has been created.');elseif($da!=$B)query_redirect("ALTER SEQUENCE ".idf_escape($da)." RENAME TO ".idf_escape($B),$_,'Sequence has been altered.');else
redirect($_);}$di=(!$_POST&&$da!=""&&!get_val("SELECT relname FROM pg_class WHERE relkind = 'S' AND relnamespace = ".driver()->nsOid." AND relname = ".q($da)));page_header(($da!=""?'Alter sequence'.": ".h($da):'Create sequence'),$m,"#sequences","",$di,doc_link(['pgsql'=>"sql-createsequence.html",'cockroach'=>"create-sequence",]));if(!$J)$J["name"]=$da;echo'
<form method="post">
<p><input name="name" value="',h($J["name"]),'" autocapitalize="off">
<input type=\'submit\' value=\'Save\'>
';if($da!="")echo"<input type='submit' name='drop' value='".'Drop'."'".confirm(sprintf('Drop %s?',$da)).">\n";echo
input_token(),'</form>
';}elseif(isset($_GET["type"])){function
enum_values($Ec){$Y="'(?:[^']|'')*'";if(!preg_match('~^AS\s+ENUM\s*\(\s*('.$Y.'(?:\s*,\s*'.$Y.')*)\s*\)$~i',$Ec,$A))return
null;preg_match_all('~'.$Y.'~',$A[1],$Ug);return$Ug[0];}function
add_enum_values($U,$qi,$Qh){$wi=enum_values($qi);$Yh=enum_values($Qh);if($wi===null||$Yh===null)return
null;$I=[];$s=0;foreach($Yh
as$Y){if($Y===idx($wi,$s))$s++;else$I[]="ALTER TYPE ".idf_escape($U)." ADD VALUE $Y".($s<count($wi)?" BEFORE ".$wi[$s]:"");}return($s==count($wi)?$I:null);}$ea=$_GET["type"];$J=$_POST;$zn=($ea!=""?array_search($ea,types(true)):0);$U=($zn?type_definition(+$zn):[]);$ii=($U["kind"]=='d'?"DOMAIN":"TYPE");if($_POST&&!$m){$_=substr(ME,0,-1);$B=trim($J["name"]);$Fa=trim(str_replace("\r","",$J["as"]));$Vh=(preg_match('~^AS\s+(?!ENUM\b|RANGE\b|\()~i',$Fa)?"DOMAIN":"TYPE");$mh='Type has been altered.';$b=(!$_POST["drop"]&&$ea!=""&&$Vh==$ii?($Fa==$U["definition"]?[]:add_enum_values($ea,$U["definition"],$Fa)):null);if($b!==null){if($ea!=$B)$b[]="ALTER $ii ".idf_escape($ea)." RENAME TO ".idf_escape($B);if(!$b)redirect($_);$Vd=false;foreach($b
as$G){if(!queries($G)){$Vd=true;break;}}queries_redirect($_,$mh,!$Vd);}else{$Sm="adminer_".uniqid();drop_create("DROP $ii ".idf_escape($ea),"CREATE $Vh ".idf_escape($B)." $Fa","DROP $Vh ".idf_escape($B),"CREATE $Vh ".idf_escape($Sm)." $Fa","DROP $Vh ".idf_escape($Sm),$_,'Type has been dropped.',$mh,'Type has been created.',$ea,$B);}}page_header(($ea!=""?'Alter type'.": ".h($ea):'Create type'),$m,"#user-types","",($zn===false),doc_link(['pgsql'=>"sql-createtype.html",'cockroach'=>"create-type",]));if(!$J){$J["name"]=$ea;$J["as"]=($ea!=""?$U["definition"]:"AS ");}echo'
<form method="post">
<p>
','Name'.": <input name='name' value='".h($J['name'])."' autocapitalize='off'>\n";textarea("as",$J["as"]);echo"<p><input type='submit' value='".'Save'."'>\n";if($ea!="")echo"<input type='submit' name='drop' value='".'Drop'."'".confirm(sprintf('Drop %s?',$ea)).">\n";echo
input_token(),'</form>
';}elseif(isset($_GET["check"])){$a=$_GET["check"];$B="$_GET[name]";$J=$_POST;if($J&&!$m){$Og=ME."table=".url_escape($a);$ph='Check has been dropped.';$nh='Check has been altered.';$oh='Check has been created.';if(JUSH=="sqlite"&&!(min_version(3.53)&&($B==""||idx(driver()->checkConstraints($a),$B)!=$B))){$ka=($J["drop"]?"":($J["name"]!=""?"CONSTRAINT ".idf_escape($J["name"])." ":"")."CHECK ($J[clause])");queries_redirect($Og,($J["drop"]?$ph:($B!=""?$nh:$oh)),recreate_table($a,$a,[],[],[],"",[],"$B",$ka));}else{$b="ALTER TABLE ".table($a);$rb=" CHECK ($J[clause])";$Sm="adminer_".uniqid();drop_create("$b DROP CONSTRAINT ".idf_escape($B),"$b ADD".($J["name"]!=""?" CONSTRAINT ".idf_escape($J["name"]):"").$rb,"$b DROP CONSTRAINT ".idf_escape($J["name"]),"$b ADD CONSTRAINT ".idf_escape($Sm).$rb,"$b DROP CONSTRAINT ".idf_escape($Sm),$Og,$ph,$nh,$oh,$B,$J["name"]);}}$di=false;if(!$J){$vb=driver()->checkConstraints($a);$di=($B!=""&&!$vb[$B]);$J=["name"=>($B==""?object_name("CHECK",$a,[]):($vb[$B]!=$B?$B:"")),"clause"=>$vb[$B]];}page_header(($B!=""?'Alter check':'Create check'),$m,["table"=>$a],h($B!=""?$B:$a),$di,doc_link(['sql'=>"create-table-check-constraints.html",'mariadb'=>"constraint/",'pgsql'=>"ddl-constraints.html#DDL-CONSTRAINTS-CHECK-CONSTRAINTS",'cockroach'=>"check",'mssql'=>"relational-databases/tables/create-check-constraints",'sqlite'=>"lang_createtable.html#check_constraints",]));echo'
<form method="post">
<p><label>Name: <input name="name" value="',h($J["name"]),'" data-maxlength="64" autocapitalize="off"></label>
<p>';textarea("clause",$J["clause"]);echo'<p><input type=\'submit\' value=\'Save\'>
';if($B!="")echo'<input type=\'submit\' name=\'drop\' value=\'Drop\'',confirm(sprintf('Drop %s?',$B)),'>
';echo
input_token(),'</form>
';}elseif(isset($_GET["trigger"])){$a=$_GET["trigger"];$B="$_GET[name]";$un=trigger_options();$J=trigger($B,$a);$di=($B!=""&&!$J);$Jh=str_replace("{table}",$a,adminer()->namePattern("TRIGGER"));$J+=["Trigger"=>strtr($Jh,["{timing}"=>"b","{event}"=>"i","{columns}"=>"","{type}"=>"row"])];if($_POST){if(!$m&&in_array($_POST["Timing"],$un["Timing"])&&in_array($_POST["Event"],$un["Event"])&&in_array($_POST["Type"],$un["Type"])){$xi=" ON ".table($a);$cd="DROP TRIGGER ".idf_escape($B).(JUSH=="pgsql"?$xi:"");$Og=ME."table=".url_escape($a);if($_POST["drop"])query_redirect($cd,$Og,'Trigger has been dropped.');else{if($B!="")queries($cd);queries_redirect($Og,($B!=""?'Trigger has been altered.':'Trigger has been created.'),queries(create_trigger($xi,$_POST)));if($B!="")queries(create_trigger($xi,$J+["Type"=>reset($un["Type"])]));}}$J=$_POST;}page_header(($B!=""?'Alter trigger':'Create trigger'),$m,["table"=>$a],h($B!=""?$B:$a),$di,doc_link(['sql'=>"create-trigger.html",'pgsql'=>"sql-createtrigger.html",'cockroach'=>"create-trigger",'mssql'=>"t-sql/statements/create-trigger-transact-sql",'sqlite'=>"lang_createtrigger.html",'oracle'=>"lnpls/CREATE-TRIGGER-statement.html",]));$Mh=strtr(preg_quote($Jh),['\{timing\}'=>'[abi]','\{event\}'=>'[iud]*','\{columns\}'=>'.*','\{type\}'=>'(row|statement)']);$sn=on('change','triggerChange',"^$Mh$",$Jh);$li=on('input','triggerChange',"^$Mh$",$Jh);echo'
<form method="post" id="form">
<table class="layout">
<tr><th>Time
<td>',html_select("Timing",$un["Timing"],$J["Timing"],$sn),'<tr><th>Event<td>',html_select("Event",$un["Event"],$J["Event"],$sn),(in_array("UPDATE OF",$un["Event"])?" <input name='Of' value='".h($J["Of"])."' class='hidden'$li>":""),'<tr><th>Type<td>',html_select("Type",$un["Type"],$J["Type"],$sn),'<tr><th>Name<td><input name="Trigger" value="',h($J["Trigger"]),'" data-maxlength="64" autocapitalize="off">
</table>
',script("fire(qs('#form')['Timing'], 'change');"),'<p>';textarea("Statement",$J["Statement"]);echo'<p>
<input type=\'submit\' value=\'Save\'>
';if($B!="")echo'<input type=\'submit\' name=\'drop\' value=\'Drop\'',confirm(sprintf('Drop %s?',$B)),'>
';echo
input_token(),'</form>
';}elseif(isset($_GET["user"])){function
grant($Je,array$ak,$e,$xi){if(!$ak)return
true;if($ak==["ALL PRIVILEGES","GRANT OPTION"])return($Je=="GRANT"?queries("$Je ALL PRIVILEGES$xi WITH GRANT OPTION"):queries("$Je ALL PRIVILEGES$xi")&&queries("$Je GRANT OPTION$xi"));return
queries("$Je ".preg_replace('~(GRANT OPTION)\([^)]*\)~','\1',implode("$e, ",$ak).$e).$xi);}function
queries_password($ve,$Zn,$vj){remember_query(sprintf($ve,$Zn,"<password>"));return
connection()->query(sprintf($ve,$Zn,q($vj)));}$fa=$_GET["user"];$ak=[""=>["All privileges"=>""]];foreach(get_rows("SHOW PRIVILEGES")as$J){foreach(explode(",",($J["Privilege"]=="Grant option"?"":$J["Context"]))as$bc)$ak[$bc=="File access on server"?"Server Admin":$bc][$J["Privilege"]]=$J["Comment"];}unset($ak["Server Admin"]["Usage"]);foreach($ak["Tables"]as$x=>$X)unset($ak["Databases"][$x]);$Sh=[];if($_POST){foreach($_POST["objects"]as$x=>$X)$Sh[$X]=(array)$Sh[$X]+idx($_POST["grants"],$x,[]);}$Ke=[];$H=(isset($_GET["host"])?connection()->query("SHOW GRANTS FOR ".q($fa)."@".q($_GET["host"])):null);$di=(isset($_GET["host"])&&!$H);if($H){while($J=$H->fetch_row()){if(preg_match('~GRANT (.*) ON (.*) TO ~',$J[0],$A)&&preg_match_all('~ *([^(,]*[^ ,(])( *\([^)]+\))?~',$A[1],$Ug,PREG_SET_ORDER)){foreach($Ug
as$X){if($X[1]!="USAGE")$Ke["$A[2]$X[2]"][$X[1]]=true;if(preg_match('~ WITH GRANT OPTION~',$J[0]))$Ke["$A[2]$X[2]"]["GRANT OPTION"]=true;}}}}if($_POST&&!$m){$vi=(isset($_GET["host"])?q($fa)."@".q($_GET["host"]):"''");if($_POST["drop"])query_redirect("DROP USER $vi",ME."privileges=",'User has been dropped.');else{$Xh=q($_POST["user"])."@".q($_POST["host"]);$vj=$_POST["pass"];$jc=false;$H=true;if($vi!=$Xh){$jc=queries_password("CREATE USER %s IDENTIFIED BY ".($_POST["hashed"]?"PASSWORD %s":"%s"),$Xh,$vj);$H=$jc;}elseif($vj!="")$H=queries_password("SET PASSWORD FOR %s = ".(min_version(8,99)||$_POST["hashed"]?"%s":"PASSWORD(%s)"),$Xh,$vj);if($H){$Kk=[];foreach($Sh
as$ii=>$Je){if(isset($_GET["grant"]))$Je=array_filter($Je);$Je=array_keys($Je);if(isset($_GET["grant"]))$Kk=array_diff(array_keys(array_filter($Sh[$ii],'strlen')),$Je);elseif($vi==$Xh){$ri=array_keys((array)$Ke[$ii]);$Kk=array_diff($ri,$Je);$Je=array_diff($Je,$ri);unset($Ke[$ii]);}if(preg_match('~^(.+)\s*(\(.*\))?$~U',$ii,$A)&&(!grant("REVOKE",$Kk,$A[2]," ON $A[1] FROM $Xh")||!grant("GRANT",$Je,$A[2]," ON $A[1] TO $Xh"))){$H=false;break;}}}if($H&&isset($_GET["host"])){if($vi!=$Xh)queries("DROP USER $vi");elseif(!isset($_GET["grant"])){foreach($Ke
as$ii=>$Kk){if(preg_match('~^(.+)(\(.*\))?$~U',$ii,$A))grant("REVOKE",array_keys($Kk),$A[2]," ON $A[1] FROM $Xh");}}}if($H&&!Queries::$queries)redirect(ME."privileges=");queries_redirect(ME."privileges=",(isset($_GET["host"])?'User has been altered.':'User has been created.'),$H);if($jc)connection()->query("DROP USER $Xh");}}page_header((isset($_GET["host"])?'Username'.": ".h("$fa@$_GET[host]"):'Create user'),$m,["privileges"=>['','Privileges']],"",$di,doc_link(['sql'=>"grant.html",'mariadb'=>"grant"]));$J=$_POST;if($J)$Ke=$Sh;else{$J=$_GET+["host"=>get_val("SELECT SUBSTRING_INDEX(CURRENT_USER, '@', -1)")];$Ke[(DB==""||$Ke?"":idf_escape(addcslashes(DB,"%_\\"))).".*"]=[];}echo'<form method="post">
<table class="layout">
<tr><th>Server<td><input name="host" data-maxlength="60" value="',h($J["host"]),'" autocapitalize="off">
<tr><th>Username<td><input name="user" data-maxlength="80" value="',h($J["user"]),'" autocapitalize="off">
<tr><th>Password<td><input name="pass" id="pass" value="',h($J["pass"]),'" autocomplete="new-password">
',($J["hashed"]?"":script("typePassword(qs('#pass'));")),(min_version(8,99)?"":checkbox("hashed",1,$J["hashed"],'Hashed',on('click','hashedClick'))),'</table>

',"<table class='odds'>\n","<thead><tr><th colspan='2'>".'Privileges';$s=0;foreach($Ke
as$ii=>$Je){echo'<th>'.($ii!="*.*"?"<input name='objects[$s]' value='".h($ii)."' size='10' autocapitalize='off'>":input_hidden("objects[$s]","*.*")."*.*");$s++;}echo"<tbody>\n";foreach([""=>"","Server Admin"=>'Server',"Databases"=>'Database',"Tables"=>'Table',"Procedures"=>'Routine',]as$bc=>$Ic){foreach((array)$ak[$bc]as$Zj=>$f){echo"<tr><td".($Ic?">$Ic<td":" colspan='2'").' lang="en" title="'.h($f).'">'.h($Zj);$s=0;foreach($Ke
as$ii=>$Je){$B="'grants[$s][".h(strtoupper($Zj))."]'";$Y=$Je[strtoupper($Zj)];if($bc=="Server Admin"&&$ii!=(isset($Ke["*.*"])?"*.*":".*"))echo"<td>";elseif(isset($_GET["grant"]))echo"<td><select name=$B><option><option value='1'".($Y?" selected":"").">".'Grant'."<option value='0'".($Y=="0"?" selected":"").">".'Revoke'."</select>";else
echo"<td align='center'><label class='block'>","<input type='checkbox' name=$B value='1'".($Y?" checked":"").($Zj=="All privileges"?" id='grants-$s-all'":($Zj=="Grant option"?"":on('click','grantsClick',"grants-$s-all"))).">","</label>";$s++;}}}echo"</table>\n",'<p>
<input type=\'submit\' value=\'Save\'>
';if(isset($_GET["host"]))echo'<input type=\'submit\' name=\'drop\' value=\'Drop\'',confirm(sprintf('Drop %s?',"$fa@$_GET[host]")),'>
';echo
input_token(),'</form>
';}elseif(isset($_GET["processlist"])){if(support("kill")){if($_POST&&!$m){$pg=0;foreach((array)$_POST["kill"]as$X){if(adminer()->killProcess($X))$pg++;}queries_redirect(ME."processlist=",lang_format(['%d process has been killed.','%d processes have been killed.'],$pg),$pg||!$_POST["kill"]);}}page_header('Process list',$m);echo'
<form method="post">
<div class="scrollable">
<table class="nowrap checkable odds"',on('click','tableClick').on('dblclick','tableClick'),'>
';$s=-1;foreach(adminer()->processList()as$s=>$J){if(!$s){echo"<thead><tr lang='en'>".(support("kill")?"<td class='hover'>":"");foreach($J
as$x=>$X)echo"<th>$x".doc_link(['sql'=>"show-processlist.html#processlist_".strtolower($x),'pgsql'=>"monitoring-stats.html#PG-STAT-ACTIVITY-VIEW",'oracle'=>"refrn/V-SESSION.html",]);echo"<tbody>\n";}echo"<tr>".(support("kill")?"<td class='hover'>".checkbox("kill[]",$J[JUSH=="sql"?"Id":"pid"],0):"");foreach($J
as$x=>$X)echo"<td>".($X!=""&&((JUSH=="sql"&&$x=="Info"&&preg_match("~Query|Killed~",$J["Command"]))||(JUSH=="pgsql"&&$x=="query")||(JUSH=="oracle"&&$x=="sql_text"))?"<code class='sql jush-".JUSH."' data-full='".h($X)."'>".shorten_utf8($X,100,"</code>").' <a href="'.h(($J["db"]!=""?preg_replace('~&db=[^&]*~','',ME)."db=".url_escape($J["db"])."&":ME)."sql=".url_escape($X)).'">'.'Clone'.'</a>'.' '.copy_icon():h($X));echo"\n";}echo'</table>
</div>
<p>
',script("copyCode(qsl('table'));");if(support("kill"))echo
format_number($s+1)."/".sprintf('%d in total',max_connections()),"<p><input type='submit' value='".'Kill'."'>\n";echo
input_token(),'</form>
',script("tableCheck();");}elseif($_GET["select"]!=""){$a=$_GET["select"];$S=table_status1($a);$w=indexes($a);$o=fields($a);$ue=column_foreign_keys($a);$pi=$S["Oid"];$Mk=[];$e=[];$el=[];$Li=[];$Vm=null;foreach($o
as$x=>$n){$B=adminer()->fieldName($n);$Lh=html_entity_decode(strip_tags($B),ENT_QUOTES);if(isset($n["privileges"]["select"])&&$B!=""){$e[$x]=$Lh;if(is_shortable($n))$Vm=adminer()->selectLengthProcess();}if(isset($n["privileges"]["where"])&&$B!="")$el[$x]=$Lh;if(isset($n["privileges"]["order"])&&$B!="")$Li[$x]=$Lh;$Mk+=$n["privileges"];}list($M,$Me)=adminer()->selectColumnsProcess($e,$w);$M=array_unique($M);$Me=array_unique($Me);$bg=count($Me)<count($M);$Z=adminer()->selectSearchProcess($o,$w,$S);$Ki=adminer()->selectOrderProcess($o,$w);$z=adminer()->selectLimitProcess();if($_GET["val"]&&is_ajax()){header("Content-Type: text/plain; charset=utf-8");foreach($_GET["val"]as$Hn=>$J){$Fa=convert_field($o[key($J)]);$M=[$Fa?:idf_escape(key($J))];$Z[]=where_check(bracket_escape($Hn,true),$o);$I=driver()->select($a,$M,$Z,$M);if($I)echo
first($I->fetch_row());}exit;}$Vj=$Kn=[];foreach($w
as$v){if($v["type"]=="PRIMARY"){$Vj=array_flip($v["columns"]);$Kn=($M?$Vj:[]);foreach($Kn
as$x=>$X){if(in_array(idf_escape($x),$M))unset($Kn[$x]);}break;}}if($pi&&!$Vj){$Vj=$Kn=[$pi=>0];$w[]=["type"=>"PRIMARY","columns"=>[$pi]];}if($_POST&&!$m){$uo=$Z;if(!$_POST["all"]&&is_array($_POST["check"])){$vb=[];foreach($_POST["check"]as$rb)$vb[]=where_check($rb,$o);$uo[]="((".implode(") OR (",$vb)."))";}$wo=$uo;$uo=($uo?"\nWHERE ".implode(" AND ",$uo):"");if($_POST["export"]){save_settings(["output"=>$_POST["output"],"format"=>$_POST["format"]],"adminer_import");dump_headers($a);adminer()->dumpTable($a,"");$kl=($M?:["*"]);$ec=convert_fields($e,$o,$M);if($ec)$kl[]=substr($ec,2);$G="";if(is_array($_POST["check"])&&!$Vj){$Ae=implode(", ",$kl)."\nFROM ".table($a);$Oe=($Me&&$bg?"\nGROUP BY ".implode(", ",$Me):"").($Ki?"\nORDER BY ".implode(", ",$Ki):"");$En=[];foreach($_POST["check"]as$X)$En[]="(SELECT".limit($Ae,"\nWHERE ".($Z?implode(" AND ",$Z)." AND ":"").where_check($X,$o).$Oe,1).")";$G=implode(" UNION ALL ",$En);}adminer()->dumpData($a,"table",$G,$kl,$wo,($bg?$Me:[]),$Ki);adminer()->dumpFooter();exit;}if(!adminer()->selectEmailProcess($Z,$ue)){if($_POST["save"]||$_POST["delete"]){$H=true;$ra=0;$Wa=false;$O=[];if(!$_POST["delete"]){foreach($o
as$B=>$X){$u=bracket_escape($B);if(isset($_POST["fields"][$u])||$_FILES["fields-$u"]){$X=process_input($o[$B]);if($X!==null&&($_POST["clone"]||$X!==false))$O[idf_escape($B)]=($X!==false?$X:idf_escape($B));}}}if($_POST["delete"]||$O){$G=($_POST["clone"]?"INTO ".table($a)." (".implode(", ",array_keys($O)).")\nSELECT ".implode(", ",$O)."\nFROM ".table($a):"");if($_POST["all"]||($Vj&&is_array($_POST["check"]))||$bg){$H=($_POST["delete"]?driver()->delete($a,$uo):($_POST["clone"]?queries("INSERT $G$uo".driver()->insertReturning($a)):driver()->update($a,$O,$uo)));$ra=connection()->affected_rows;if(is_object($H))$ra+=$H->num_rows;}else{$Wa=count((array)$_POST["check"])>1&&driver()->begin();foreach((array)$_POST["check"]as$X){$to="\nWHERE ".($Z?implode(" AND ",$Z)." AND ":"").where_check($X,$o);$H=($_POST["delete"]?driver()->delete($a,$to,1):($_POST["clone"]?queries("INSERT".limit1($a,$G,$to)):driver()->update($a,$O,$to,1)));if(!$H)break;$ra+=connection()->affected_rows;}if($Wa&&$H&&!driver()->commit())$H=false;}}$mh=lang_format(['%d item has been affected.','%d items have been affected.'],$ra);if($_POST["clone"]&&$H&&$ra==1){$zg=last_id($H);if($zg)$mh=sprintf('Item%s has been inserted.'," $zg");}queries_redirect(remove_from_uri($_POST["all"]&&$_POST["delete"]?"page|next":""),$mh,$H);if($Wa)driver()->rollback();if(!$_POST["delete"]){$Lj=(array)$_POST["fields"];edit_form($a,array_intersect_key($o,$Lj),$Lj,!$_POST["clone"],$m);page_footer();exit;}}elseif(!$_POST["import"]){$H=true;$ra=0;$Wa=count((array)$_POST["val"])>1&&driver()->begin();foreach((array)$_POST["val"]as$Hn=>$J){$O=[];foreach($J
as$x=>$X){$x=bracket_escape($x,true);$O[idf_escape($x)]=(preg_match('~char|text~',$o[$x]["type"])||$X!=""?adminer()->processInput($o[$x],$X):"NULL");}$H=driver()->update($a,$O," WHERE ".($Z?implode(" AND ",$Z)." AND ":"").where_check(bracket_escape($Hn,true),$o),($bg||$Vj?0:1)," ");if(!$H)break;$ra+=connection()->affected_rows;}if($Wa)$H=$H&&driver()->commit();queries_redirect(remove_from_uri(),lang_format(['%d item has been affected.','%d items have been affected.'],$ra),$H);if($Wa)driver()->rollback();}else{save_settings(["format"=>$_POST["separator"]],"adminer_import");$ee=get_file("csv_file",true);if(!is_string($ee))$m=upload_error($ee);elseif(!preg_match('~~u',$ee))$m='File must be in UTF-8 encoding.';else{$Hb=array_keys($o);$ql=($_POST["separator"]=="csv"?",":($_POST["separator"]=="tsv"?"\t":";"));$nc=parse_csv($ee,$ql);$ra=count($nc);driver()->begin();$K=[];foreach($nc
as$x=>$io){if(!$x&&!array_diff($io,$Hb)){$Hb=$io;$ra--;}else{$O=[];foreach($io
as$s=>$Db)$O[idf_escape($Hb[$s])]=($Db==""&&$o[$Hb[$s]]["null"]?"NULL":q(csv_value($Db)));$K[]=$O;}}$H=(!$K||driver()->insertUpdate($a,$K,$Vj));if($H)driver()->commit();queries_redirect(remove_from_uri("page|next"),lang_format(['%d row has been imported.','%d rows have been imported.'],$ra),$H);driver()->rollback();}}}}$Cm=adminer()->tableName($S);if(is_ajax()){page_headers();ob_start();}else
page_header('Select'.": $Cm",$m,[],"",(!$o&&support("table")),($o?doc_link([JUSH=>driver()->tableHelp($a,is_view($S))]):""));$O=null;if(isset($Mk["insert"])||!support("table")){$O="";foreach((array)$_GET["where"]as$X){$Y=$X["val"];if(is_array($Y))$Y=(count($Y)==1&&preg_match('~^val-(.*)~s',reset($Y),$A)?$A[1]:"");if($X["col"]!=""&&$Y!=""&&($X["op"]=="="||(!$X["op"]&&(is_array($X["val"])||!preg_match('~[_%]~',$Y)))))$O
.="&set[".url_escape(bracket_escape($X["col"]))."]=".url_escape($Y);}}adminer()->selectLinks($S,$O);if(!$e&&support("table"))echo"<p class='error'>".'Unable to select the table.'."\n";else{echo"<form id='form'>\n","<div hidden>";hidden_fields_get();echo(DB!=""?input_hidden("db",DB).(isset($_GET["ns"])?input_hidden("ns",$_GET["ns"]):""):""),input_hidden("select",$a),"</div>\n";adminer()->selectColumnsPrint($M,$e);adminer()->selectSearchPrint($Z,$el,$w,$S);adminer()->selectOrderPrint($Ki,$Li,$w);adminer()->selectLimitPrint($z);if($Vm!==null)adminer()->selectLengthPrint($Vm);adminer()->selectActionPrint($w);$Wl=[];foreach((array)$_GET["where"]as$X){if($X["op"]=="SQL")$Wl[]=$X["val"];}$bm=($Wl&&$_SERVER["HTTP_SEC_FETCH_SITE"]!="same-origin"?hash_hmac('sha256',json_encode([$a,$Wl]),$_SESSION["token"]):"");echo($bm?input_hidden("sql_sig",$bm):""),"</form>\n";if($bm&&$_GET["sql_sig"]!==$bm){echo"<p class='error'>".'Invalid CSRF token. Submit the form again.'.' '.'If you did not send this request from Adminer, close this page.'."\n";page_footer();exit;}$D=$_GET["page"];$xe=null;if($D=="last"){$xe=get_val(count_rows($a,$Z,$bg,$Me));$D=floor(max(0,intval($xe)-1)/$z);}$jl=$M;$Ne=$Me;if(!$jl){$jl[]="*";$ec=convert_fields($e,$o,$M);if($ec)$jl[]=substr($ec,2);}foreach($M
as$x=>$X){$n=$o[idf_unescape($X)];if($n&&($Fa=convert_field($n)))$jl[$x]="$Fa AS $X";}if(JUSH=="pgsql"||JUSH=="mssql"){foreach((array)$_GET["columns"]as$x=>$X){if(isset($jl[$x])&&$X["fun"])$jl[$x].=" AS ".idf_escape(apply_sql_function($X["fun"],($X["col"]!=""?$X["col"]:"*")));}}if(!$bg&&$Kn){foreach($Kn
as$x=>$X){$jl[]=idf_escape($x);if($Ne)$Ne[]=idf_escape($x);}}$H=driver()->select($a,$jl,$Z,$Ne,$Ki,$z,$D,true);if(!is_object($H))echo"<p class='error'>".(adminer()->error()?:'Unknown error.')."\n";else{if(JUSH=="mssql"&&$D)$H->seek($z*$D);$rd=[];$K=[];while($J=$H->fetch_assoc()){if($D&&JUSH=="oracle")unset($J["RNUM"]);$K[]=$J;}$Ze=($z&&(support("cursor")?$_GET["next"]!="":count($K)>=$z));if(is_ajax()&&$Ze)header("X-Next-Page: ".pagination_href($D+1));if($_GET["modify"]&&$K){$dh=max_input_vars(count($K[0])+1,20);echo($dh&&count($K)>$dh?"<p class='error'>".max_input_vars_error()."\n":"");}echo"<form method='post' enctype='multipart/form-data'".on_upload_progress($Qn).">\n";if($_GET["page"]!="last"&&$z&&$Me&&$bg&&JUSH=="sql")$xe=get_val(" SELECT FOUND_ROWS()");if(!$K)echo"<p class='message'>".'No rows.'."\n";else{$Sa=adminer()->backwardKeys($a,$Cm);$Hk=[];reset($M);foreach($K[0]as$x=>$X){if(!isset($Kn[$x])){$X=idx($_GET["columns"],key($M))?:[];$Hk[$x]=["fun"=>$X["fun"],"col"=>($M?$X["col"]:$x)];next($M);}}echo"<div class='scrollable'>","<table id='table' class='nowrap checkable odds'".on('click','tableClick').on('dblclick','tableClick').on('keydown','editingKeydown').">\n","<thead><tr>".(!$Me&&$M?"":"<td class='hover check sticky'><input type='checkbox' id='all-page' class='jsonly' title='".'All rows on this page'."'".on('click','formCheck','^check').">");$Nh=[];$qk=1;foreach($Hk
as$x=>$X){$n=$o[$X["col"]];$B=($n?adminer()->fieldName($n,$qk):($X["fun"]?($X["col"]!=""?h($X["col"]):"*"):h($x)));if($B!=""){$qk++;$Nh[$x]=$B;$d=idf_escape($x);$pf=remove_from_uri('(order|desc)[^=]*|page|next').'&order[0]='.url_escape($x);$Ic="&desc[0]=1";$Ol=preg_replace('~ DESC( NULLS LAST)?$~','',$Ki[0]);$Ql=($Ol==$d||$Ol==$x);echo"<th id='th[".h(bracket_escape($x))."]'".($Ql?" aria-sort='".($Ol==$Ki[0]?"ascending":"descending")."'":"").">";$Fe=apply_sql_function(h($X["fun"]),$B);$Pl=isset($n["privileges"]["order"])||$X["fun"];echo($Pl?"<a href='".h($pf.($Ql&&$Ol==$Ki[0]?$Ic:''))."'>$Fe</a>":$Fe),doc_link([JUSH=>idx($n,"doc")]);$lh=($Pl?"<a href='".h($pf.$Ic)."' title='".'descending'."' class='text'> ↓</a>":'');if(!$X["fun"]&&isset($n["privileges"]["where"]))$lh
.="<a href='#fieldset-search' title='".'Search'."' class='text jsonly'".on('click','selectSearch',$x)."> =</a>";echo($lh?"<span class='column'>$lh</span>":"");}}$Fg=[];if($_GET["modify"]){foreach($K
as$J){foreach($J
as$x=>$X)$Fg[$x]=max($Fg[$x],min(40,utf8_length((string)$X)));}}$kf=[];$jf=[];foreach((array)$_GET["where"]as$X){$X+=["col"=>"","op"=>"","val"=>""];$Db=$X["col"];$dl=$X["val"];if(!is_array($dl)&&($dl!=""||preg_match('~NULL$~',$X["op"]))&&(!$X["op"]||in_array($X["op"],adminer()->operators($S)))){$Hg=strtr(preg_quote($dl),["%"=>".*?","_"=>"."]);$Aj=["LIKE %%"=>$Hg,"ILIKE %%"=>$Hg,"REGEXP"=>$dl]+(JUSH=="pgsql"?["~"=>$dl,"~*"=>$dl]:[])+($Db!=""?[]:["="=>'^'.preg_quote($dl).'\z',"IN"=>'^(?:'.implode("|",array_map('preg_quote',array_map('trim',explode(",",$dl)))).')\z',"LIKE"=>"^$Hg\\z","ILIKE"=>"^$Hg\\z","FIND_IN_SET"=>'(?<=^|,)'.preg_quote($dl).'(?=,|\z)',]);foreach(($Db!=""?[$Db=>$o[$Db]]:$o)as$B=>$n){if($Db!=""||is_searchable($n,$X)){$_i=$X["op"]?:(!preg_match('~'.text_type().'~',$n["type"])?"IN":(preg_match('~%~',$dl)?"LIKE":"LIKE %%"));if(isset($Aj[$_i])){$yb=preg_match('~^ILIKE|\*$~',$_i)||($_i!="~"&&preg_match('~^(sql|mssql|sqlite)$~',JUSH));$kf[$B][]="(?".($yb?"i":"").":$Aj[$_i])";}elseif($_i=="IS NULL"&&$Db=="")$jf[$B]=true;}}}}echo($Sa?"<th>".'Relations':"")."<tbody>\n";if(is_ajax())ob_end_clean();foreach(adminer()->rowDescriptions($K,$ue)as$Hh=>$J){$Gn=unique_array($K[$Hh],$w);if(!$Gn){$Gn=[];foreach($K[$Hh]as$x=>$X){if(!in_array(idx(idx($Hk,$x,[]),"fun"),driver()->grouping))$Gn[$x]=$X;}}$Hn="";$s=0;foreach($Gn
as$x=>$X){$Gk=idx($Hk,$x,[]);if($M&&$Gk&&!isset($o[$Gk["col"]]))continue;$Fe=idx($Gk,"fun","");$Db=($Fe?$Gk["col"]:$x);$n=(array)$o[$Db];$ag=is_blob($n);if(!$Fe&&strlen($X)>64&&driver()->md5(idf_escape($Db),$n)){$Fe="md5";$X=md5($ag?(string)driver()->value($X,$n):$X);}if($Fe){$Hn
.="&fun[$s]=".url_escape($Fe)."&col[$s]=".url_escape($Db).($X!==null?"&val[$s]=".url_escape($X===false?"f":$X):"");$s++;}else$Hn
.="&".($X!==null?"where[".url_escape(bracket_escape($Db))."]=".url_escape($X===false?"f":$X):"null[]=".url_escape($Db));}echo"<tr>".(!$Me&&$M?"":"<td class='hover check sticky'>".($bg||information_schema(DB)?"":"<a href='".h(ME."edit=".url_escape($a).$Hn)."' class='edit'>".'edit'."</a> ").checkbox("check[]",substr($Hn,1),in_array(substr($Hn,1),(array)$_POST["check"])));foreach($J
as$x=>$X){if(isset($Nh[$x])){$Fe=$Hk[$x]["fun"];$Db=$Hk[$x]["col"];$n=(array)$o[$x];if($X!=""&&(!isset($rd[$x])||$rd[$x]!=""))$rd[$x]=(is_mail($X)?$Nh[$x]:"");$_="";if(is_blob($n)&&$X!="")$_=ME.'download='.url_escape($a).'&field='.url_escape($x).$Hn;if(!$_&&$X!==null){foreach((array)$ue[$x]as$q){if(count($ue[$x])==1||end($q["source"])==$x){$_="";foreach($q["source"]as$s=>$Rl)$_
.=where_link($s,$q["target"][$s],$K[$Hh][$Rl]);$_=($q["db"]!=""?preg_replace('~&db=[^&]*~',"&db=".url_escape($q["db"]),ME):ME).'select='.url_escape($q["table"]).$_;if($q["ns"]!="")$_=preg_replace('~&ns=[^&]*~',"&ns=".url_escape($q["ns"]),$_);if(count($q["source"])==1)break;}}}if($Fe=="count"&&$Db==""){$_=ME."select=".url_escape($a);$s=0;foreach((array)$_GET["where"]as$W){if(!array_key_exists($W["col"],$Gn))$_
.=where_link($s++,$W["col"],$W["val"],$W["op"]);}foreach($Gn
as$lg=>$W){if(idx(idx($Hk,$lg,[]),"fun")){$_="";break;}$_
.=where_link($s++,$lg,$W);}}$qf=select_value($X,$_,$n,$Vm,($Fe?[]:idx($kf,$x,[])));if($X===null&&!$Fe&&isset($jf[$x]))$qf="<mark>$qf</mark>";$u=bracket_escape($Hn);$t=h("val[$u][".bracket_escape($x)."]");$Nj=idx(idx($_POST["val"],$u),bracket_escape($x));$Nn=idx($n["privileges"],"update")&&!is_identity_always($n);$md=$K[$Hh][$x];$nd=!is_array($J[$x])&&!is_blob($n)&&is_utf8($md)&&!$Fe&&!$n["generated"]&&$Nn;$U=($Fe=="min"||$Fe=="max"?$o[$Db]["type"]:$n["type"]);$Um=preg_match('~text|json|lob~',$U);$cg=preg_match(number_type(),$U)||preg_match('~^(avg|ceil|char_length|count|count distinct|floor|len|length|round|sum|time_to_sec)$~',$Fe);echo"<td id='$t'".($cg&&($X===null||is_numeric(strip_tags($qf))||$U=="money")?" class='number'":"");if(($_GET["modify"]&&$nd&&$md!==null)||$Nj!==null){$Ue=h($Nj!==null?$Nj:$md);echo">".($Um?"<textarea name='$t' cols='30' rows='".(substr_count($md,"\n")+1)."'>\n$Ue</textarea>":"<input name='$t' value='$Ue' size='$Fg[$x]'>");}else{$Qg=strpos($qf,"<i>…</i>");$ld=adminer()->editVal($md,$n);echo($Nn?" data-text='".($Qg?2:($Um?1:0))."'".($nd?"":" data-warning='".'Use the edit link to modify this value.'."'").($nd&&!$Qg&&html_entity_decode(strip_tags($qf),ENT_QUOTES,"UTF-8")!==$ld?" data-value='".h($ld)."'":""):"").">$qf";}}}if($Sa)echo"<td>";adminer()->backwardKeysPrint($Sa,$K[$Hh]);echo"</tr>\n";}if(is_ajax())exit;echo"</table>\n","</div>\n";}if(!is_ajax()){$qa=get_settings("adminer_import");if($K||$D||$Ze){$Jd=true;if($_GET["page"]!="last"){if(!$z||(count($K)<$z&&($K||!$D)))$xe=($D?$D*$z:0)+count($K);elseif(JUSH!="sql"||!$bg){$xe=($bg?null:found_rows($S,$Z));$Jd=!driver()->hasEstimatedRows();if($xe===null||(!$Jd&&$xe<max(1e4,2*($D+1)*$z))){$xe=first(slow_query(count_rows($a,$Z,$bg,$Me)));$Jd=true;}}}if(!support("cursor"))$Ze=(($xe===false?count($K)+1:$xe-$D*$z)>$z);$ej=($z&&($Ze||$D));if($ej)echo($Ze?'<p><a href="'.h(pagination_href($D+1)).'" class="loadmore"'.on('click','selectLoadMore','Loading…').'>'.'Load more data'.'</a>':''),"\n";echo"<div class='footer'><div>\n";if($ej){$bh=($xe===false?$D+($K?(count($K)>=$z?2:1):0):floor(($xe-1)/$z));echo"<fieldset><legend>".'Page'."</legend>";if(!support("cursor")){echo
pagination(0,$D).($D>5?" …":"");for($s=max(1,$D-4);$s<min($bh,$D+5);$s++)echo
pagination($s,$D);if($bh>0)echo($D+5<$bh?" …":""),($Jd&&$xe!==false?pagination($bh,$D):" <a href='".h(remove_from_uri("page")."&page=last")."' title='~$bh'>".'last'."</a>");}else
echo
pagination(0,$D).($D>1?" …":""),($D?pagination($D,$D):""),($Ze?pagination($D+1,$D)." …":"");echo"</fieldset>\n";}echo"<fieldset>","<legend>".'Whole result'."</legend>";$Qc=($Jd?"":"~ ").$xe;$sg=($xe!==false?($Jd?"":"~ ").lang_format(['%d row','%d rows'],$xe):"");echo
checkbox("all",1,0,$sg,on('click','countRows',$Qc))."\n","</fieldset>\n";if(adminer()->selectCommandPrint())echo'<fieldset',($_GET["modify"]?'':" title='".'Ctrl+click on a value to modify it.'."'"),'>
<legend><a href=\'',h($_GET["modify"]?remove_from_uri("modify"):relative_uri()."&modify=1"),'\'>Modify</a></legend><div>
<input type=\'submit\' id=\'save\' value=\'Save\'',($_GET["modify"]||$_POST["val"]?'':" class='jsonly' disabled"),'>
</div></fieldset>

<fieldset><legend>Selected <span id="selected"></span></legend><div>
<input type=\'submit\' name=\'edit\' value=\'Edit\'>
<input type=\'submit\' name=\'clone\' value=\'Clone\'>
<input type=\'submit\' name=\'delete\' value=\'Delete\'',confirm(),'>
</div></fieldset>
';$ve=adminer()->dumpFormat();foreach((array)$_GET["columns"]as$d){if($d["fun"]||($d["col"]!=""&&!isset($o[$d["col"]]))){unset($ve['sql']);break;}}if($ve){print_fieldset("export",'Export'." <span id='selected2'></span>");$Zi=adminer()->dumpOutput();echo($Zi?html_select("output",$Zi,$qa["output"])." ":""),html_select("format",$ve,$qa["format"])," <input type='submit' name='export' value='".'Export'."'>\n","</div></fieldset>\n";}adminer()->selectEmailPrint(array_filter($rd,'strlen'),$e);echo"</div></div>\n";}if(adminer()->selectImportPrint())echo"<p>","<a href='#import' class='toggle'>".'Import'."</a>","<span id='import'".($_POST["import"]?"":" class='hidden'").">: ",($Qn?input_hidden(ini_get("session.upload_progress.name"),$Qn):""),file_input(" name='csv_file'"," ".html_select("separator",["csv"=>"CSV,","csv;"=>"CSV;","tsv"=>"TSV"],$qa["format"])." <input type='submit' name='import' value='".'Import'."'>".($Qn?" <progress class='jsonly hidden' max='1' value='0'></progress>":"")),"</span>";echo
input_token(),"</form>\n",(!$Me&&$M?"":script("tableCheck();"));}}}if(is_ajax()){ob_end_clean();exit;}}elseif(isset($_GET["variables"])){$P=isset($_GET["status"]);page_header($P?'Status':'Variables');$jo=($P?adminer()->showStatus():adminer()->showVariables());if(!$jo)echo"<p class='message'>".'No rows.'."\n";else{echo"<table>\n";foreach($jo
as$J){echo"<tr>";$x=array_shift($J);echo"<th><code class='jush-".JUSH.($P?"status":"set")."'>".h($x)."</code>";foreach($J
as$X)echo"<td>".nl_br(h($X));}echo"</table>\n";}}elseif(isset($_GET["script"])){header("Content-Type: application/json; charset=utf-8");if($_GET["script"]=="db"){$rm=["Data_length"=>0,"Index_length"=>0,"Data_free"=>0];foreach(table_status()as$B=>$S){json_row("Comment-$B",h($S["Comment"]).($S["Error"]?" <span class='error'>".h($S["Error"])."</span>":""));if(!is_view($S)||preg_match('~materialized~i',$S["Engine"])){foreach(["Engine","Collation"]as$x)json_row("$x-$B",h($S[$x]));foreach(array_keys($rm+["Auto_increment"=>0,"Rows"=>0])as$x){if(array_key_exists($x,$S))json_row("$x-$B",format_status($S,$x));if($S[$x]!=""&&isset($rm[$x]))$rm[$x]+=($S["Engine"]!="InnoDB"||$x!="Data_free"?$S[$x]:0);}}}if(function_exists('Adminer\db_status'))$rm=db_status();foreach($rm
as$x=>$X)json_row("sum-$x",adminer()->formatSizeValue($X));json_row("");}else{foreach(count_tables(adminer()->databases(false))as$k=>$X){json_row("tables-$k",format_number($X));json_row("size-$k",db_size($k));}json_row("");}exit;}else{if(!isset($_GET["select"])&&support("single_table")){$T=tables_list();if($T)redirect(ME.(support("table")?"table=":"select=").url_escape(key($T)));}$ih=ME.(isset($_GET["select"])?"select=&":"");$Mm=array_merge((array)$_POST["tables"],(array)$_POST["views"]);if($Mm&&!$m&&!$_POST["search"]){$H=true;$mh="";if(JUSH=="sql"&&$_POST["tables"]&&count($_POST["tables"])>1&&($_POST["drop"]||$_POST["truncate"]||$_POST["copy"]))queries("SET foreign_key_checks = 0");if($_POST["truncate"]){if($_POST["tables"])$H=truncate_tables($_POST["tables"]);$mh='Tables have been truncated.';}elseif($_POST["move"]){$H=move_tables((array)$_POST["tables"],(array)$_POST["views"],$_POST["target"]);$mh='Tables have been moved.';}elseif($_POST["copy"]){$H=copy_tables((array)$_POST["tables"],(array)$_POST["views"],$_POST["target"]);$mh='Tables have been copied.';}elseif($_POST["drop"]){if($_POST["views"])$H=drop_views($_POST["views"]);if($H&&$_POST["tables"])$H=drop_tables($_POST["tables"]);$mh='Tables have been dropped.';}elseif(JUSH=="sqlite"&&$_POST["check"]){foreach((array)$_POST["tables"]as$R){foreach(get_rows("PRAGMA integrity_check(".q($R).")")as$J)$mh
.="<b>".h($R)."</b>: ".h($J["integrity_check"])."<br>";}}elseif(JUSH=="mssql"&&$_POST["check"]){foreach((array)$_POST["tables"]as$R){foreach(get_rows("DBCC CHECKTABLE (".q(table($R)).") WITH TABLERESULTS")as$J)$mh
.="<b>".h($R)."</b>: ".h($J["MessageText"])."<br>";}}elseif(JUSH!="sql"){$H=(JUSH=="sqlite"?queries("VACUUM"):apply_queries("VACUUM".($_POST["optimize"]?" ANALYZE":""),(array)$_POST["tables"]));$mh='Tables have been optimized.';}elseif(!$_POST["tables"])$mh='No tables.';elseif($H=queries(($_POST["optimize"]?"OPTIMIZE":($_POST["check"]?"CHECK":($_POST["repair"]?"REPAIR":"ANALYZE")))." TABLE ".implode(", ",array_map('Adminer\idf_escape',$_POST["tables"])))){while($J=$H->fetch_assoc())$mh
.="<b>".h($J["Table"])."</b>: ".h($J["Msg_text"])."<br>";}queries_redirect(relative_uri(),$mh,$H);}page_header(($_GET["ns"]==""?'Database'.": ".h(DB):'Schema'.": ".h($_GET["ns"])),$m,true);if(adminer()->homepage()){if($_GET["ns"]!==""){if(support("scheme")&&support("comment")){$f=schema_comment($_GET["ns"]);if($f!="")echo"<p class='nowrap'>".'Comment'.": ".adminer()->commentValue('SCHEMA',$f)."\n";}$Ki=$_GET["order"];$Ce=($Ki||support("fast_status"));echo"<div>\n","<h3 id='tables-views'>".'Tables and views'."</h3>\n";$Lm=($Ce?table_status():tables_list());$ie=$_GET["filter"];if($ie!=""){foreach($Lm
as$R=>$P){if(stripos("$R",$ie)===false)unset($Lm[$R]);}}if(!$Lm)echo"<p class='message'>".'No tables.'."\n";else{echo"<form method='post'>\n";if(support("table")){echo"<fieldset><legend>".'Search data in tables'." <span id='selected2'></span></legend><div>";$_i=idx($_POST,"op",JUSH=="elastic"?"should":"LIKE %%");echo
html_select("op",adminer()->operators(),$_i);$G=($_POST?$_POST["query"]:$_GET["query"]);echo" <input type='search' name='query' value='".h($G!=""?$G:$ie)."'".on('keydown','submitKeydown','search').">"," <input type='submit' name='search' value='".'Search'."'>\n","</div></fieldset>\n";if(!$m&&(!$_POST||$_POST["search"])&&$G!="")search_tables($G,$_i);}echo"<div class='scrollable'>\n","<table class='nowrap checkable odds'".on('click','tableClick').on('dblclick','tableClick').">\n",'<thead><tr>','<td class="hover"><input id="check-all" type="checkbox" class="jsonly" title="'.'All'.'"'.on('click','formCheck','^(tables|views)\[').'>','<th class="sticky"'.(!$Ki&&JUSH!='sqlite'?" aria-sort='ascending'":'').'><a href="'.h(substr($ih,0,-1)).'">'.'Table'.'</a>';$e=["Engine"=>['Engine'.doc_link(['sql'=>'storage-engines.html'])]];$Gb=collations();if($Gb)$e["Collation"]=['Collation'.doc_link(['sql'=>'charset-charsets.html','mariadb'=>'supported-character-sets-and-collations/'])];if(function_exists('Adminer\alter_table'))$e["Data_length"]=['Data Length'.doc_link(['sql'=>'show-table-status.html','pgsql'=>'functions-admin.html#FUNCTIONS-ADMIN-DBOBJECT','oracle'=>'refrn/ALL_TABLES.html']),"create",'Alter table',];if(support("indexes"))$e["Index_length"]=['Index Length'.doc_link(['sql'=>'show-table-status.html','pgsql'=>'functions-admin.html#FUNCTIONS-ADMIN-DBOBJECT']),"indexes",'Alter indexes',];$e["Data_free"]=['Data Free'.doc_link(['sql'=>'show-table-status.html']),"edit",'New item'];if(function_exists('Adminer\alter_table'))$e["Auto_increment"]=['Auto Increment'.doc_link(['sql'=>'example-auto-increment.html','mariadb'=>'auto_increment/']),"auto_increment=1&create",'Alter table',];$e["Rows"]=['Rows'.doc_link(['sql'=>'show-table-status.html','pgsql'=>'catalog-pg-class.html#CATALOG-PG-CLASS','oracle'=>'refrn/ALL_TABLES.html']),"select",'Select data',];if(support("comment"))$e["Comment"]=['Comment'.doc_link(['sql'=>'show-table-status.html','pgsql'=>'functions-info.html#FUNCTIONS-INFO-COMMENT-TABLE','cockroach'=>'comment-on']),];$Ga=['Engine','Collation','Comment'];foreach($e
as$x=>$d)echo"<th".($Ki==$x?" aria-sort='".(in_array($x,$Ga)?"ascending":"descending")."'":"")."><a href='".h($ih)."order=$x'>$d[0]</a>";echo"<tbody>\n";if($Ki){uasort($Lm,function($ha,$Pa)use($Ki,$Ga){$I=($ha[$Ki]<$Pa[$Ki]?-1:($ha[$Ki]>$Pa[$Ki]?1:0));return(in_array($Ki,$Ga)?$I:-$I);});}$T=0;$rm=["Data_length"=>0,"Index_length"=>0,"Data_free"=>0];foreach($Lm
as$B=>$P){$mo=($Ce?is_view($P):$P!==null&&!preg_match('~table|sequence~i',$P));$P=($Ce?$P:['Engine'=>$P]);$t=h("Table-".$B);echo'<tr><td class="hover">'.checkbox(($mo?"views[]":"tables[]"),$B,in_array("$B",$Mm,true),"","","",$t),'<th class="sticky">'.(support("table")||support("indexes")?"<a href='".h(ME)."table=".url_escape($B)."' title='".'Show structure'."' id='$t'>".h($B).'</a>':h($B));if($mo&&!preg_match('~materialized~i',$P['Engine'])){$an='View';echo'<td colspan="'.(count($e)-(support("comment")?2:1)).'">'.(support("view")?"<a href='".h(ME)."view=".url_escape($B)."' title='".'Alter view'."'>$an</a>":$an),"<td align='right'><a href='".h(ME)."select=".url_escape($B)."' title='".'Select data'."'>?</a>";if(support("comment"))echo'<td>'.h($P['Comment']);}else{if($Ce){foreach(array_keys($rm)as$x)$rm[$x]+=($P["Engine"]!="InnoDB"||$x!="Data_free"?idx($P,$x):0);}foreach($e
as$x=>$d){$t=" id='$x-".h($B)."'";echo($d[1]?"<td align='right'>".($x=="Index_length"&&$P["invalid_index"]?"<span class='error'>INVALID</span> ":"")."<a href='".h(ME."$d[1]=").url_escape($B)."'$t title='$d[2]'>".format_status($P,$x)."</a>":"<td$t>".h(idx($P,$x,'?')).($x=="Comment"&&$P["Error"]?" <span class='error'>".h($P["Error"])."</span>":""));}$T++;}echo"\n";}echo"<tr><td class='hover'><th class='sticky'>".sprintf('%d in total',count($Lm)),"<td>".h(JUSH=="sql"?get_val("SELECT @@default_storage_engine"):""),($Gb?"<td>".h(db_collation(DB,$Gb)):'');if($Ce&&function_exists('Adminer\db_status'))$rm=db_status();foreach($rm
as$x=>$qm)echo($e[$x]?"<td align='right' id='sum-$x'>".($Ce?adminer()->formatSizeValue($qm):""):"");echo"\n","</table>\n",($Ce?'':script("ajaxSetHtml('".js_escape(ME)."script=db');")),"</div>\n";if(!information_schema(DB)){$eo="<input type='submit' value='".'Vacuum'."'".on_help("VACUUM")."> ";$Ei="<input type='submit' name='optimize' value='".'Optimize'."'".on_help(JUSH=="sql"?"OPTIMIZE TABLE":"VACUUM ANALYZE")."> ";$Xj=(JUSH=="sqlite"?$eo."<input type='submit' name='check' value='".'Check'."'".on_help("PRAGMA integrity_check")."> ":(JUSH=="pgsql"?$eo.$Ei:(JUSH=="mssql"?"<input type='submit' name='check' value='".'Check'."'".on_help("DBCC CHECKTABLE")."> ":(JUSH=="sql"?"<input type='submit' value='".'Analyze'."'".on_help("ANALYZE TABLE")."> ".$Ei."<input type='submit' name='check' value='".'Check'."'".on_help("CHECK TABLE")."> "."<input type='submit' name='repair' value='".'Repair'."'".on_help("REPAIR TABLE")."> ":"")))).(function_exists('Adminer\truncate_tables')?"<input type='submit' name='truncate' value='".'Truncate'."'".confirm().on_help(JUSH=="sqlite"?"DELETE":"TRUNCATE".(JUSH=="pgsql"?"":" TABLE"))."> ":"").(function_exists('Adminer\drop_tables')?"<input type='submit' name='drop' value='".'Drop'."'".confirm().on_help("DROP TABLE").">":"");echo($Xj?"<div class='footer'><div>\n<fieldset><legend>".'Selected'." <span id='selected'></span></legend><div>$Xj\n</div></fieldset>\n":"");$j=(support("scheme")?adminer()->schemas():adminer()->databases());if(count($j)!=1&&function_exists('Adminer\move_tables')){echo"<fieldset><legend>".'Move to another database'." <span id='selected3'></span></legend><div>";$k=(isset($_POST["target"])?$_POST["target"]:(support("scheme")?$_GET["ns"]:DB));echo($j?html_select("target",$j,$k):'<input name="target" value="'.h($k).'" autocapitalize="off">'),"</label> <input type='submit' name='move' value='".'Move'."'>",(support("copy")?" <input type='submit' name='copy' value='".'Copy'."'> ".checkbox("overwrite",1,$_POST["overwrite"],'overwrite'):""),"</div></fieldset>\n";}echo"<input type='hidden' name='all' value=''".on('click','countTables',$T).">\n",input_token(),"</div></div>\n";}echo"</form>\n",script("tableCheck();");}echo(function_exists('Adminer\alter_table')?"<p class='links hover'><a href='".h(ME)."create='>".'Create table'."</a>\n":''),(support("view")?"<a href='".h(ME)."view='>".'Create view'."</a>\n":""),"</div>\n";if(support("routine")){echo"<div>\n","<h3 id='routines'>".'Routines'."</h3>\n";$Uk=routines();if($Uk){echo"<table class='odds'>\n",'<thead><tr><th>'.'Name'.'<th>'.'Type'.'<th>'.'Return type'."<td class='hover'><tbody>\n";foreach($Uk
as$J){$B=($J["SPECIFIC_NAME"]==$J["ROUTINE_NAME"]?"":"&name=".url_escape($J["ROUTINE_NAME"]));echo'<tr>','<th><a href="'.h(ME.($J["ROUTINE_TYPE"]!="PROCEDURE"?'callf=':'call=').url_escape($J["SPECIFIC_NAME"]).$B).'"'.' title="'.($J["DTD_IDENTIFIER"]=="trigger"?'Triggers':'Call').'">'.h($J["ROUTINE_NAME"]).'</a>','<td>'.h($J["ROUTINE_TYPE"]),'<td>'.h($J["DTD_IDENTIFIER"]),'<td class="hover"><a href="'.h(ME.($J["ROUTINE_TYPE"]!="PROCEDURE"?'function=':'procedure=').url_escape($J["SPECIFIC_NAME"]).$B).'">'.'Alter'."</a>";}echo"</table>\n";}echo'<p class="links hover">'.(support("procedure")?'<a href="'.h(ME).'procedure=">'.'Create procedure'.'</a>':'').'<a href="'.h(ME).'function=">'.'Create function'."</a>\n","</div>\n";}if(support("sequence")){echo"<div>\n","<h3 id='sequences'>".'Sequences'."</h3>\n";$ul=get_vals("SELECT relname FROM pg_class WHERE relkind = 'S' AND relnamespace = ".driver()->nsOid." ORDER BY relname");if($ul){echo"<table class='odds'>\n","<thead><tr><th>".'Name'."<tbody>\n";foreach($ul
as$X)echo"<tr><th><a href='".h(ME)."sequence=".url_escape($X)."'>".h($X)."</a>\n";echo"</table>\n";}echo"<p class='links hover'><a href='".h(ME)."sequence='>".'Create sequence'."</a>\n","</div>\n";}if(support("type")){echo"<div>\n","<h3 id='user-types'>".'User types'."</h3>\n";$bo=types();if($bo){echo"<table class='odds'>\n","<thead><tr><th>".'Name'."<tbody>\n";foreach($bo
as$X)echo"<tr><th><a href='".h(ME)."type=".url_escape($X)."'>".h($X)."</a>\n";echo"</table>\n";}echo"<p class='links hover'><a href='".h(ME)."type='>".'Create type'."</a>\n","</div>\n";}if(support("event")){echo"<div>\n","<h3 id='events'>".'Events'."</h3>\n";$K=get_rows("SHOW EVENTS");if($K){echo"<table>\n","<thead><tr><th>".'Name'."<th>".'Schedule'."<th>".'Start'."<th>".'End'."<td class='hover'><tbody>\n";foreach($K
as$J)echo"<tr>","<th>".h($J["Name"]),"<td>".($J["Execute at"]?'At given time'."<td>".h($J["Execute at"]):'Every'." ".h($J["Interval value"])." ".h($J["Interval field"])."<td>".h($J["Starts"])),"<td>".h($J["Ends"]),'<td class="hover"><a href="'.h(ME).'event='.url_escape($J["Name"]).'">'.'Alter'.'</a>';echo"</table>\n";$Gd=get_val("SELECT @@event_scheduler");if($Gd&&$Gd!="ON")echo"<p class='error'><code class='jush-sqlset'>event_scheduler</code>: ".h($Gd)."\n";}echo'<p class="links hover"><a href="'.h(ME).'event=">'.'Create event'."</a>\n","</div>\n";}}elseif(support("extension")){$Sd=get_rows("SELECT e.extname, e.extversion, n.nspname, obj_description(e.oid, 'pg_extension') AS comment
FROM pg_extension e
JOIN pg_namespace n ON n.oid = e.extnamespace
ORDER BY e.extname");if($Sd){echo"<div>\n","<h3 id='extensions'>".'Extensions'."</h3>\n","<table class='odds'>\n","<thead><tr><th>".'Name'."<th>".'Version'."<th>".'Schema'."<th>".'Comment'."<tbody>\n";foreach($Sd
as$J)echo"<tr><th><code class='jush-pgsqlext'>".h($J["extname"])."</code>","<td>".h($J["extversion"]),"<td><a href='".h(substr(ME,0,-1).url_escape($J["nspname"]))."'>".h($J["nspname"])."</a>","<td>".h($J["comment"]),"\n";echo"</table>\n","</div>\n";}}}}page_footer();