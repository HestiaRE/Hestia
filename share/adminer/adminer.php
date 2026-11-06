<?php
/** Adminer - Compact database management
* @link https://www.adminer.org/
* @author Jakub Vrana, https://www.vrana.cz/
* @copyright 2007 Jakub Vrana
* @license https://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
* @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License, version 2 (one or other)
* @version 6.3.0
*/namespace
Adminer;if(isset($_GET["status"]))$_GET["variables"]=$_GET["status"];if(isset($_GET["import"]))$_GET["sql"]=$_GET["import"];const
VERSION="6.3.0";error_reporting(24575);set_error_handler(function($_d,$Dd){return!!preg_match('~^Undefined (array key|offset|index)~',$Dd);},E_WARNING|E_NOTICE);if(!preg_match('~^(unsafe_raw)?$~',ini_get("filter.default"))||ini_get("filter.default_flags")){foreach(['_GET','_POST','_COOKIE','_SERVER']as$X){$Dn=filter_input_array(constant("INPUT$X"),FILTER_UNSAFE_RAW);if($Dn)$$X=$Dn;}}$_COOKIE=array_filter($_COOKIE,'is_scalar');if(function_exists("mb_internal_encoding"))mb_internal_encoding("8bit");function
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
true;$U=$n["type"];$cl=$X["val"];$Xa='binary$|bytea|raw|image|bfile|^vector$'.(JUSH=="mssql"?'|^timestamp$':'|^bit').(JUSH=="oracle"?'|^blob|^long|rowid':'');if(preg_match("~$Xa~",$U))return
false;if(preg_match(number_type(),$U)){$hi='-?\d+(\.\d+)?';$Bh='';if(preg_match('~IN$~',$X["op"])){$cl=preg_replace('~^\s*\(\s*(.*?)\s*\)\s*$~s','\1',$cl);$Bh="( *, *$hi)*";}elseif($X["op"]=="BETWEEN")$Bh="\\s+AND\\s+$hi";return(bool)preg_match('~^'.$hi.$Bh.'$~i',$cl);}if(preg_match('~^(small)?date|^timestamp~',$U))return(bool)preg_match('~^\d+-\d+-\d+~',$cl);if(preg_match('~^time~',$U))return(bool)preg_match('~^\d+:\d+~',$cl);if(preg_match('~^bool~',$U)||(JUSH=="mssql"&&$U=="bit"))return(bool)preg_match('~^(t|f|true|false|[01])$~i',$cl);return
true;}function
bracket_escape($u,$Qa=false){static$fn=[':'=>':1',']'=>':2','['=>':3','"'=>':4','='=>':5'];return
strtr($u,($Qa?array_flip($fn):$fn));}function
url_escape($Q){static$fn=[];if(!$fn){$fn=[' '=>'+'];foreach(str_split("\"'<>#%&+=?".ini_get("arg_separator.input"))as$ob)$fn[$ob]=sprintf('%%%02X',ord($ob));for($s=0;$s<256;$s++){if($s<32||$s>126)$fn[chr($s)]=sprintf('%%%02X',$s);}}return
strtr((string)$Q,$fn);}function
min_version($eo,$Sg="",$h=null){$h=connection($h);$_l=$h->server_info;if($Sg&&preg_match('~([\d.]+)-MariaDB~',$_l,$A)){$_l=$A[1];$eo=$Sg;}return$eo&&version_compare($_l,$eo)>=0;}function
charset(Db$g){return(min_version("5.5.3",0,$g)?"utf8mb4":"utf8");}function
ini_set($Fi,$Y){return(function_exists('ini_set')?\ini_set($Fi,$Y):false);}function
ini_bool($Nf){$X=ini_get($Nf);return(preg_match('~^(on|true|yes)$~i',$X)||(int)$X);}function
ini_bytes($Nf){$X=ini_get($Nf);switch(strtolower(substr($X,-1))){case'g':$X=(int)$X*1024;case'm':$X=(int)$X*1024;case'k':$X=(int)$X*1024;}return$X;}function
max_input_vars($J,$Xi){$Wg=(int)ini_get("max_input_vars");return($Wg?(int)floor(($Wg-$Xi)/$J):0);}function
max_input_vars_error(){$Nf="max_input_vars";return
sprintf('Maximum number of allowed fields exceeded. Please increase %s.',"<b>$Nf = ".ini_get($Nf)."</b>");}function
sid(){static$I;if($I===null)$I=(SID&&!($_COOKIE&&ini_bool("session.use_cookies")));return$I;}function
set_password($do,$N,$V,$E){$_SESSION["pwds"][$do][$N][$V]=($_COOKIE["adminer_key"]&&is_string($E)?[encrypt_string($E,$_COOKIE["adminer_key"])]:$E);}function
get_password(){$I=get_session("pwds");if(is_array($I))$I=($_COOKIE["adminer_key"]?decrypt_string($I[0],$_COOKIE["adminer_key"]):false);return$I;}function
get_val($G,$n=0,$Sb=null){$Sb=connection($Sb);$H=$Sb->query($G);if(!is_object($H))return
false;$J=$H->fetch_row();return($J?$J[$n]:false);}function
get_vals($G,$d=0){$I=[];$H=connection()->query($G);if(is_object($H)){while($J=$H->fetch_row())$I[]=$J[$d];}return$I;}function
get_key_vals($G,$h=null,$Cl=true){$h=connection($h);$I=[];$H=$h->query($G);if(is_object($H)){while($J=$H->fetch_row()){if($Cl)$I[$J[0]]=$J[1];else$I[]=$J[0];}}return$I;}function
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
get_url($Mn,$bc){$Cd=php_errors(function()use($Mn,$bc,&$I,&$ef){$http_response_header=null;$I=file_get_contents($Mn,false,$bc);$ef=(function_exists('http_get_last_response_headers')?http_get_last_response_headers():$http_response_header);});return[$I,(preg_match('~^HTTP/[\d.]+ (\d+)~',idx($ef,0,''),$A)?$A[1]:''),(array)$ef,($I===false?implode("\n",$Cd):''),];}function
json_decode_exact($ig){$ig=str_replace(['\\\\','\\"'],['\\u005c','\\u0022'],$ig);$ig=preg_replace('~"(\\\\u0001[^"]*+")|"[^"]*+"(*SKIP)(*FAIL)~','"\\\\u0001$1',$ig);return
json_decode(preg_replace('~"[^"]*+"(*SKIP)(*FAIL)|-?\d[-+.\deE]*+~','"\\\\u0001$0"',$ig));}function
json_scalar($X){return(is_string($X)&&substr($X,0,1)=="\1"?substr($X,1):$X);}function
json_encode_exact($X,$oe=0){$ig=str_replace(['\\\\','\\"'],["\2","\3"],json_encode($X,$oe));$ig=preg_replace('~"\\\\u0001(-?\d[^"\\\\]*)"|(")\\\\u0001(\\\\u0001[^"]*+")|"[^"]*+"(*SKIP)(*FAIL)~','$1$2$3',$ig);return
str_replace(["\2","\3"],['\\\\','\\"'],$ig);}function
get_settings($fc){parse_str($_COOKIE[$fc],$Dl);return$Dl;}function
get_setting($x,$fc="adminer_settings",$l=null){return
idx(get_settings($fc),$x,$l);}function
save_settings(array$Dl,$fc="adminer_settings"){$Y=http_build_query($Dl+get_settings($fc));cookie($fc,$Y);$_COOKIE[$fc]=$Y;}function
restart_session(){if(!ini_bool("session.use_cookies")&&session_status()==PHP_SESSION_NONE)session_start();}function
stop_session($re=false){$Pn=ini_bool("session.use_cookies");if(!$Pn||$re){session_write_close();if($Pn&&ini_set("session.use_cookies",'0')===false)session_start();}}function&get_session($x){return$_SESSION[$x][DRIVER][SERVER][$_GET["username"]];}function
set_session($x,$X){$_SESSION[$x][DRIVER][SERVER][$_GET["username"]]=$X;}function
auth_url($do,$N,$V,$k=null){$Ln=remove_from_uri(implode("|",array_keys(SqlDriver::$drivers))."|username|ext|".($k!==null?"db|":"").($do=='mssql'||$do=='pgsql'?"":"ns|").session_name());preg_match('~([^?]*)\??(.*)~',$Ln,$A);return"$A[1]?".(sid()?SID."&":"").($_GET["ext"]?"ext=".url_escape($_GET["ext"])."&":"").($do!="server"||$N!=""?url_escape($do)."=".url_escape($N)."&":"")."username=".url_escape($V).($k!=""?"&db=".url_escape($k):"").($A[2]?"&$A[2]":"");}function
is_ajax(){return($_SERVER["HTTP_X_REQUESTED_WITH"]=="XMLHttpRequest");}function
redirect($Og,$mh=null,$P=303){if($mh!==null){restart_session();$_SESSION["messages"][preg_replace('~^[^?]*~','',($Og!==null?$Og:$_SERVER["REQUEST_URI"]))][]=$mh;}if($Og!==null){if($Og=="")$Og=".";header("Location: $Og",true,$P);exit;}}function
query_redirect($G,$Og,$mh,$qk=true,$Kd=true,$Vd=false,$Rm=""){if($Kd){$Zl=microtime(true);$Vd=!connection()->query($G);$Rm=format_time($Zl);}$Sl=($G?adminer()->messageQuery($G,$Rm,$Vd):"");if($Vd){adminer()->error
.=adminer()->error().$Sl.script("messagesPrint();")."<br>";return
false;}if($qk)redirect($Og,$mh.$Sl);return
true;}class
Queries{static$queries=[];static$start=0;}function
remember_query($G){if(!Queries::$start)Queries::$start=microtime(true);Queries::$queries[]=(driver()->delimiter!=';'?$G:(preg_match('~;$~',$G)?"DELIMITER ;;\n$G;\nDELIMITER ":$G).";");}function
queries($G){remember_query($G);return
connection()->query($G);}function
apply_queries($G,array$T,$Ed='Adminer\table'){foreach($T
as$R){if(!queries("$G ".$Ed($R)))return
false;}return
true;}function
queries_redirect($Og,$mh,$qk){$hk=implode("\n",Queries::$queries);$Rm=format_time(Queries::$start);return
query_redirect($hk,$Og,$mh,$qk,false,!$qk,$Rm);}function
format_time($Zl){return
sprintf('%.3f s',max(0,microtime(true)-$Zl));}function
relative_uri($Ln=''){return
preg_replace_callback('~^[^?]*~',function($A){return
str_replace(":","%3A",$A[0]);},preg_replace('~^[^?]*/([^?]*)~','\1',($Ln?:$_SERVER["REQUEST_URI"])));}function
remove_from_uri($ej=""){return
substr(preg_replace("~(?<=[?&])($ej".(SID?"":"|".session_name()).")=[^&]*&~",'',relative_uri()."&"),0,-1);}function
get_files($B,$zc=false){$ee=$_FILES[$B];if(!$ee)return
null;foreach($ee
as$x=>$X)$ee[$x]=(array)$X;$I=[];foreach($ee["error"]as$x=>$m){if($m)return$m;$p=$ee["name"][$x];$Zm=$ee["tmp_name"][$x];$Zb=file_get_contents($zc&&preg_match('~\.gz$~',$p)?"compress.zlib://$Zm":$Zm);if($zc){$Zl=substr($Zb,0,3);if(function_exists("iconv")&&preg_match("~^\xFE\xFF|^\xFF\xFE~",$Zl))$Zb=iconv("utf-16","utf-8",$Zb);elseif($Zl=="\xEF\xBB\xBF")$Zb=substr($Zb,3);}$I[]=[$p,$Zb];}return$I;}function
get_file($x,$zc=false,$Gc=""){$he=get_files($x,$zc);if(!is_array($he))return$he;$I='';foreach($he
as$ee){$Zb=$ee[1];$I
.=$Zb;if($Gc)$I
.=(preg_match("($Gc\\s*\$)",$Zb)?"":$Gc)."\n\n";}return$I;}function
upload_error($m){$eh=($m==UPLOAD_ERR_INI_SIZE?ini_get("upload_max_filesize"):0);return($m?'Unable to upload a file.'.($eh?" ".sprintf('Maximum allowed file size is %sB.',$eh):""):'File does not exist.');}function
is_utf8($X){return(preg_match('~~u',$X)&&!preg_match('~[\0-\x8\xB\xC\xE-\x1F]~',$X));}function
utf8_length($X){return
strlen(preg_replace('~[\x80-\xBF]~','',$X));}function
format_number($X){preg_match('~^#+([^#0]+)(?:(#+)\1)?(#*0)$~u','#,##0',$A);$Hl=strlen($A[3]);$I=number_format($X,0,".","");$I=preg_replace('~\B(?=(\d{'.(strlen($A[2])?:$Hl).'})*\d{'.$Hl.'}$)~',$A[1],$I);return
strtr($I,preg_split('~~u','0123456789',-1,PREG_SPLIT_NO_EMPTY));}function
format_status(array$S,$x){$X=idx($S,$x,'?');if(!is_numeric($X))return
h($X);if($X<0)return'?';$Aa=($x=="Rows"&&(JUSH=="sqlite"||$S["Engine"]==(JUSH=="pgsql"?"table":"InnoDB")));$X=in_array($x,["Data_length","Index_length","Data_free"])?adminer()->formatSizeValue($X):format_number($X);return($Aa?"~ ":"").$X;}function
friendly_url($X){return
preg_replace('~\W~i','-',$X);}function
table_status1($R,$Wd=false){$I=table_status($R,$Wd);return($I?reset($I):["Name"=>$R]);}function
column_foreign_keys($R){$I=[];foreach(adminer()->foreignKeys($R)as$q){foreach($q["source"]as$X)$I[$X][]=$q;}return$I;}function
fields_from_edit(){$I=[];foreach((array)$_POST["field_keys"]as$x=>$X){if($X!=""){$X=bracket_escape($X);$_POST["function"][$X]=$_POST["field_funs"][$x];$_POST["fields"][$X]=$_POST["field_vals"][$x];}}foreach((array)$_POST["fields"]as$x=>$X){$B=bracket_escape($x,true);$I[$B]=["field"=>$B,"full_type"=>"","type"=>"","privileges"=>["insert"=>1,"update"=>1,"where"=>1,"order"=>1],"null"=>true,"auto_increment"=>($B==driver()->primary),];}return$I;}function
dump_headers($sf,$Fh=false){$I=adminer()->dumpHeaders($sf,$Fh);$Zi=$_POST["output"];if($Zi!="text"||$I=="tar"){$Ob=($Zi!="text"&&$Zi!="file"&&preg_match('~^[0-9a-z]+$~',$Zi)?".$Zi":"");header("Content-Disposition: attachment; filename=".adminer()->dumpFilename($sf).".$I$Ob");}session_write_close();if(!ob_get_level())ob_start(null,4096);ob_flush();flush();return$I;}function
dump_csv(array$J){$rn=$_POST["format"]=="tsv";foreach($J
as$x=>$X){if(preg_match('~["\n]|^0[^.]|\.\d*0$|'.($rn?'\t':'[,;]|^$').'~',$X))$J[$x]='"'.str_replace('"','""',$X).'"';}echo
implode(($_POST["format"]=="csv"?",":($rn?"\t":";")),$J)."\r\n";}function
parse_csv($nc,$ol){$I=[];preg_match_all('~(?>"[^"]*"|[^"\r\n]+)+~',$nc,$Ug);foreach($Ug[0]as$J){preg_match_all("~((?>\"[^\"]*\")+|[^$ol]*)$ol~",$J.$ol,$Vg);$I[]=$Vg[1];}return$I;}function
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
select_value($X,$_,array$n,$Pm,array$_j=[]){if(is_array($X)){$I="";if(array_filter($X,'is_array')==array_values($X)){$og=[];foreach($X
as$W)$og+=array_fill_keys(array_keys($W),null);foreach(array_keys($og)as$lg)$I
.="<th>".h($lg);foreach($X
as$W){$I
.="<tr>";foreach(array_merge($og,$W)as$Wn)$I
.="<td>".select_value($Wn,$_,$n,$Pm,$_j);}}else{foreach($X
as$lg=>$W)$I
.="<tr>".($X!=array_values($X)?"<th>".h($lg):"")."<td>".select_value($W,$_,$n,$Pm,$_j);}return"<table>$I</table>";}if(!$_)$_=adminer()->selectLink($X,$n);if($_===null){if(is_mail($X))$_="mailto:$X";if(is_url($X))$_=$X;}$X=driver()->value($X,$n);$I=adminer()->editVal($X,$n);if($I!==null){if(!is_utf8($I))$I="\0";elseif($Pm!=""&&is_shortable($n))$I=shorten_utf8($I,max(0,+$Pm),"",$_j);else$I=highlight_matches($I,$_j);}return
adminer()->selectVal($I,$_,$n,$X);}function
is_blob(array$n){return
preg_match('~blob|bytea|raw|file'.(JUSH=="mssql"?'|binary|image':'').'~',$n["type"])&&!in_array($n["type"],idx(driver()->structuredTypes(),'User types',[]));}function
is_identity_always(array$n){return$n["auto_increment"]&&(JUSH=="mssql"||$n["default"]=="GENERATED ALWAYS AS IDENTITY");}function
is_mail($pd){$Ha='[-a-z0-9!#$%&\'*+/=?^_`{|}~]';$Zc='[a-z0-9]([-a-z0-9]{0,61}[a-z0-9])';$zj="$Ha+(\\.$Ha+)*@($Zc?\\.)+$Zc";return
is_string($pd)&&preg_match("(^$zj(,\\s*$zj)*\$)i",$pd);}function
is_url($Q){$Zc='[a-z0-9]([-a-z0-9]{0,61}[a-z0-9])';return
preg_match("~^((https?):)?//($Zc?\\.)+$Zc(:\\d+)?(/\\S*)?(\\?\\S*)?(#\\S*)?\$~iD",$Q);}function
is_ipv6($na){$Me='[\da-f]{1,4}';$Zf='\d{1,3}(\.\d{1,3}){3}';return(bool)preg_match("~^(($Me:){7}$Me|($Me:){6}$Zf|(($Me:)*$Me)?::(($Me:)*($Me|$Zf))?)$~iD",$na);}function
is_shortable(array$n){return!preg_match('~'.number_type().'|date|time|year~',$n["type"]);}function
url_host($of){return(strpos($of,":")!==false?"[$of]":$of);}function
server_parts(array$tj){return["scheme"=>(string)$tj["scheme"],"host"=>(string)$tj["host"],"port"=>(string)$tj["port"],"socket"=>(string)$tj["socket"],"path"=>(string)$tj["path"],];}function
parse_server($N){if($N=="")return
server_parts([]);if($N[0]==":"&&!is_ipv6($N)){$Dk=substr($N,1);if(preg_match('~^\d+$~D',$Dk))return
server_parts(["port"=>$Dk]);return(preg_match('~^/[-\w.:/]*$~D',$Dk)?server_parts(["socket"=>$Dk]):null);}$al="";if(preg_match('~^([-+.\w]+)://~',$N,$A)){$al=strtolower($A[1]);$N=substr($N,strlen($A[0]));}if(preg_match('~^\[(.+)](:(\d+))?(/[-\w./]*)?$~D',$N,$A))return(is_ipv6($A[1])?server_parts(["scheme"=>$al,"host"=>$A[1],"port"=>$A[3],"path"=>$A[4]]):null);if(is_ipv6($N))return
server_parts(["scheme"=>$al,"host"=>$N]);if(preg_match('~^(/[-\w./]*)(:(\d+))?$~D',$N,$A))return
server_parts(["scheme"=>$al,"host"=>$A[1],"port"=>$A[3]]);return(preg_match('~^([-\w.]*)(:(\d+))?(/[-\w./]*)?$~D',$N,$A)?server_parts(["scheme"=>$al,"host"=>$A[1],"port"=>$A[3],"path"=>$A[4]]):null);}function
count_rows($R,array$Z,$bg,array$Me){$G=" FROM ".table($R).($Z?" WHERE ".implode(" AND ",$Z):"");return($bg&&(JUSH=="sql"||count($Me)==1)?"SELECT COUNT(DISTINCT ".implode(", ",$Me).")$G":"SELECT COUNT(*)".($bg?" FROM (SELECT 1$G GROUP BY ".implode(", ",$Me).") x":$G));}function
slow_query($G){$Jl=driver()->slowQuery($G,adminer()->queryTimeout());ob_flush();flush();return@get_key_vals(($Jl?:$G),null,false);}function
get_token(){$nk=rand(1,1e6);return($nk^$_SESSION["token"]).":$nk";}function
verify_token(){list($an,$nk)=explode(":",$_POST["token"]);return($nk^$_SESSION["token"])==$an&&in_array($_SERVER["HTTP_SEC_FETCH_SITE"],["","same-origin"]);}function
compress_alphabet(){return
strtr(implode(range('"','~')),"'\\","!\n");}function
decompress_string($Q,$Nc=""){$ya=array_flip(str_split(compress_alphabet()));$y=strlen($Q);$Zn=($y?13*($y-1)/2-$ya[$Q[0]]:0);$Xa="";$Dk=0;$Ek=0;for($s=1;$s<$y;$s+=2){$Dk=($Dk<<13)+$ya[$Q[$s]]*93+$ya[$Q[$s+1]];$Ek+=13;while($Ek>=8&&$Zn>=8){$Ek-=8;$Zn-=8;$Xa
.=chr($Dk>>$Ek);$Dk&=(1<<$Ek)-1;}}if($Xa=="")return"";if($Nc!=""&&function_exists('inflate_init'))return
inflate_add(inflate_init(ZLIB_ENCODING_RAW,['dictionary'=>$Nc]),$Xa,ZLIB_FINISH);return($Nc==""&&function_exists('gzinflate')?gzinflate($Xa):inflate($Xa,$Nc));}function
inflate($Xa,$Nc=""){$Dg=[3,4,5,6,7,8,9,10,11,13,15,17,19,23,27,31,35,43,51,59,67,83,99,115,131,163,195,227,258];$Eg=[0,0,0,0,0,0,0,0,1,1,1,1,2,2,2,2,3,3,3,3,4,4,4,4,5,5,5,5,0];$Rc=[1,2,3,4,5,7,9,13,17,25,33,49,65,97,129,193,257,385,513,769,1025,1537,2049,3073,4097,6145,8193,12289,16385,24577];$Tc=[0,0,0,0,1,1,2,2,3,3,4,4,5,5,6,6,7,7,8,8,9,9,10,10,11,11,12,12,13,13];$I=$Nc;$F=0;do{$je=inflate_bits($Xa,$F,1);$U=inflate_bits($Xa,$F,2);if(!$U){$F=($F+7)&~7;$y=inflate_bits($Xa,$F,16);$F+=16;$I
.=substr($Xa,$F>>3,$y);$F+=$y<<3;}else{if($U==1){$Mg=array_merge(array_fill(0,144,8),array_fill(0,112,9),array_fill(0,24,7),array_fill(0,8,8));$Uc=array_fill(0,30,5);}else{$Lg=inflate_bits($Xa,$F,5)+257;$Sc=inflate_bits($Xa,$F,5)+1;$Ki=[16,17,18,0,8,7,9,6,10,5,11,4,12,3,13,2,14,1,15];$th=array_fill(0,19,0);$sh=inflate_bits($Xa,$F,4)+4;for($s=0;$s<$sh;$s++)$th[$Ki[$s]]=inflate_bits($Xa,$F,3);$uh=inflate_table($th);$Fg=[];while(count($Fg)<$Lg+$Sc){$om=inflate_symbol($Xa,$F,$uh);if($om==16)$Fg=array_merge($Fg,array_fill(0,inflate_bits($Xa,$F,2)+3,end($Fg)));elseif($om==17)$Fg=array_merge($Fg,array_fill(0,inflate_bits($Xa,$F,3)+3,0));elseif($om==18)$Fg=array_merge($Fg,array_fill(0,inflate_bits($Xa,$F,7)+11,0));else$Fg[]=$om;}$Mg=array_slice($Fg,0,$Lg);$Uc=array_slice($Fg,$Lg);}$Ng=inflate_table($Mg);$Wc=inflate_table($Uc);while(($om=inflate_symbol($Xa,$F,$Ng))!=256){if($om<256)$I
.=chr($om);else{$y=$Dg[$om-257]+inflate_bits($Xa,$F,$Eg[$om-257]);$Vc=inflate_symbol($Xa,$F,$Wc);$C=strlen($I)-$Rc[$Vc]-inflate_bits($Xa,$F,$Tc[$Vc]);for($s=0;$s<$y;$s++)$I
.=$I[$C+$s];}}}}while(!$je);return($Nc==""?$I:substr($I,strlen($Nc)));}function
inflate_bits($Xa,&$F,$hc){$I=0;for($s=0;$s<$hc;$s++){$I+=((ord($Xa[$F>>3])>>($F&7))&1)<<$s;$F++;}return$I;}function
inflate_table(array$Fg){$R=[];$Cb=0;for($Ya=1;$Ya<=max($Fg);$Ya++){foreach($Fg
as$om=>$y){if($y==$Ya){$R[$Ya][$Cb]=$om;$Cb++;}}$Cb<<=1;}return$R;}function
inflate_symbol($Xa,&$F,array$R){$Cb=0;$Ya=0;do{$Cb=($Cb<<1)+inflate_bits($Xa,$F,1);$Ya++;}while(!isset($R[$Ya][$Cb]));return$R[$Ya][$Cb];}function
script($Ol,$en="\n"){return"<script".nonce().">$Ol</script>$en";}function
script_src($Mn,$Cc=false){return"<script src='".h($Mn)."'".nonce().($Cc?" defer":"")."></script>\n";}function
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
checkbox($B,$Y,$ub,$sg="",$c="",$_b="",$ug=""){$I="<input type='checkbox' name='$B' value='".h($Y)."'".($ub?" checked":"").($sg==""&&$_b?" class='$_b'":"").($ug?" aria-labelledby='$ug'":"").$c.">";return($sg!=""?"<label".($_b?" class='$_b'":"").">$I".h($sg)."</label>":$I);}function
optionlist($Hi,$kl=null,$Qn=false){$I="";foreach($Hi
as$lg=>$W){$Ji=[$lg=>$W];if(is_array($W)){$I
.='<optgroup label="'.h($lg).'">';$Ji=$W;}foreach($Ji
as$x=>$X)$I
.='<option'.($Qn||is_string($x)?' value="'.h($x).'"':'').($kl!==null&&($Qn||is_string($x)?(string)$x:$X)===$kl?' selected':'').'>'.h($X);if(is_array($W))$I
.='</optgroup>';}return$I;}function
group_system(array$Nh,$Zk=false){$I=[];$pm=[];foreach($Nh
as$B){if($Zk?driver()->isSystem(DB,$B):driver()->isSystem($B))$pm[]=$B;else$I[]=$B;}if($pm)$I[sprintf('System%s','')]=$pm;return$I;}function
html_select($B,array$Hi,$Y="",$c="",$ug=""){static$sg=0;$tg="";if(!$ug&&substr($Hi[""],0,1)=="("){$sg++;$ug="label-$sg";$tg="<option value='' id='$ug'>".h($Hi[""]);unset($Hi[""]);}return"<select name='".h($B)."'".($ug?" aria-labelledby='$ug'":"")."$c>".$tg.optionlist($Hi,$Y)."</select>";}function
html_radios($B,array$Hi,$Y="",$ol=""){$I="";foreach($Hi
as$x=>$X)$I
.="<label><input type='radio' name='".h($B)."' value='".h($x)."'".($x==$Y?" checked":"").">".h($X)."</label>$ol";return$I;}function
confirm($mh=""){return
on('click','confirmClick',$mh?:'Are you sure?');}function
print_fieldset($t,$Cg,$ho=false){echo"<fieldset><legend>","<a href='#fieldset-$t' class='toggle'>$Cg</a>","</legend>","<div id='fieldset-$t'".($ho?"":" class='hidden'").">\n";}function
bold($Za,$_b=""){return($Za?" class='active $_b'":($_b?" class='$_b'":""));}function
js_escape($Q){return
str_replace("<","\\x3C",addcslashes($Q,"\r\n'\\"));}function
js_escape_re($Q){return
addcslashes(preg_quote($Q,"/"),"\r\n");}function
pagination_href($D){return
remove_from_uri("page|next").($D?"&page=$D".($_GET["next"]!=""?"&next=".url_escape($_GET["next"]):""):"");}function
pagination($D,$oc){return" ".($D==$oc?($D?"<b>".($D+1)."</b>":$D+1):'<a href="'.h(pagination_href($D)).'">'.($D+1)."</a>");}function
hidden_fields(array$ak,array$xf=[],$Pj=''){$I=false;foreach($ak
as$x=>$X){if(!in_array($x,$xf)){if(is_array($X))hidden_fields($X,[],$x);else{$I=true;echo
input_hidden(($Pj?$Pj."[$x]":$x),$X);}}}return$I;}function
hidden_fields_get(){echo(sid()?input_hidden(session_name(),session_id()):''),($_GET["ext"]?input_hidden("ext",$_GET["ext"]):""),(isset($_GET[DRIVER])?input_hidden(DRIVER,SERVER):""),input_hidden("username",$_GET["username"]);}function
on_upload_progress(&$Kn){$Kn=(ini_bool("session.upload_progress.enabled")&&ini_get("session.upload_progress.name")?rand_string():"");return($Kn?on('submit','uploadProgress',ME."upload=$Kn",SESSION_NAME."=$Kn"):"");}function
file_input($c,$Dk=""){$Yg="max_file_uploads";$Zg=ini_get($Yg);$eh="upload_max_filesize";$fh=ini_bytes($eh);$Lj=ini_bytes("post_max_size");if($Lj&&$Lj<$fh){$eh="post_max_size";$fh=$Lj;}$gh=ini_get($eh);return(ini_bool("file_uploads")?"<input type='file'$c".on('change','fileChange',(int)$Zg,sprintf('Increase %s.',"$Yg = $Zg"),$fh,sprintf('Increase %s.',"$eh = $gh")).">$Dk":'File uploads are disabled.');}function
enum_input($U,$c,array$n,$Y,$sd=""){preg_match_all("~".driver()->enumLength."~",$n["length"],$Ug);$Pj=($n["type"]=="enum"?"val-":"");$ub=(is_array($Y)?in_array("null",$Y):$Y===null);$I=($n["null"]&&$Pj?"<label><input type='$U'$c value='null'".($ub?" checked":"")."><i>$sd</i></label>":"");foreach($Ug[0]as$X){$X=stripcslashes(idf_unescape($X));$ub=(is_array($Y)?in_array($Pj.$X,$Y):$Y===$X);$I
.=" <label><input type='$U'$c value='".h($Pj.$X)."'".($ub?' checked':'').'>'.h(adminer()->editVal($X,$n)).'</label>';}return$I;}function
input(array$n,$Y,$r,$Oa=false,$Hn=false){$B=h(bracket_escape($n["field"]));echo"<td class='function'>";$zd=driver()->enumLength($n);if($zd){$n["type"]="enum";$n["length"]=$zd;}$Hi=($n["type"]=="enum"||$n["type"]=="set");if(is_array($Y)&&!$r&&!$Hi)$r="json";$ig=($r=="json"||preg_match('~^jsonb?$~',$n["full_type"]));if($ig&&$Y!=''&&(JUSH!="pgsql"||$n["type"]!="json")&&(is_array($Y)||!$_POST["save"])){$oe=JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE;$Y=(is_array($Y)?json_encode($Y,$oe):json_encode_exact(json_decode_exact($Y),$oe));}$Ck=($Hn&&is_identity_always($n));if($Ck&&!$_POST["save"])$r=null;$Ge=(isset($_GET["select"])||$Ck?["orig"=>'original']:[])+adminer()->editFunctions($n);$c=" name='fields[$B]".($Hi?"[]":"")."'".($Oa?" autofocus":"");echo
driver()->unconvertFunction($n)." ";$R=$_GET["edit"]?:$_GET["select"];if($n["type"]=="enum")echo
h($Ge[""])."<td>".adminer()->editInput($R,$n,$c,$Y);else{$Xe=(in_array($r,$Ge)||isset($Ge[$r]));$ke=0;foreach($Ge
as$x=>$X){if($x===""||!$X)break;$ke++;}echo(count($Ge)>1?"<select name='function[$B]'".on('change','functionChange').on_help_value('^SQL$').">".optionlist($Ge,$r===null||$Xe?$r:"")."</select>":h(reset($Ge)))."<td".($ke&&count($Ge)>1?on('input','skipOriginal',$ke):"").">";$Pf=adminer()->editInput($R,$n,$c,$Y);if($Pf!="")echo$Pf;elseif(preg_match('~bool~',$n["type"]))echo"<input type='hidden'$c value='0'>"."<input type='checkbox'".(preg_match('~^(1|t|true|y|yes|on)$~i',$Y)?" checked":"")."$c value='1'>";elseif($n["type"]=="set")echo
enum_input("checkbox",$c,$n,(is_string($Y)?explode(",",$Y):$Y));elseif(is_blob($n)&&ini_bool("file_uploads"))echo"<input type='file' name='fields-$B'>";elseif($ig)echo"<textarea$c cols='50' rows='12' class='jush-json'>\n".h($Y).'</textarea>';elseif(($Om=preg_match('~text|lob|memo~i',$n["type"]))||preg_match("~\n~",$Y)){if($Om&&JUSH!="sqlite")$c
.=" cols='50' rows='12'";else{$K=min(12,substr_count($Y,"\n")+1);$c
.=" cols='30' rows='$K'";}echo"<textarea$c>\n".h($Y).'</textarea>';}else{$wn=driver()->types();$un=$wn[$n["type"]];$Ea=preg_match('~\[]~',$n["full_type"]);if($Ea)$hh=0;elseif(preg_match('~date|time|year~',$n["type"])){$Oj=($n["length"]==""&&JUSH=="pgsql"?6:$n["length"]);$ze=(preg_match('~time~',$n["type"])&&preg_match('~^[1-9]\d*$~',$Oj)?$Oj+1:0);$hh=($un?$un+$ze:0);}elseif(!preg_match('~int|vector~',$n["type"])&&preg_match('~^(\d+)(,(\d+))?$~',$n["length"],$A))$hh=(preg_match("~binary~",$n["type"])?2:1)*$A[1]+($A[3]?1:0)+($A[2]&&!$n["unsigned"]?1:0);else$hh=($un?$un+($n["unsigned"]?0:1):0);echo"<input".((!$Xe||$r==="")&&preg_match('~^'.int_type().'$~',$n["type"])&&!$Ea?" type='number'":"")." value='".h($Y)."'".($hh?" data-maxlength='$hh'":"").(preg_match('~char|binary~',$n["type"])&&$hh>20?" size='".($hh>99?60:40)."'":"")."$c>";}echo
adminer()->editHint($R,$n,$Y),(count($Ge)>1?script("fire(qs('select', qsl('td').previousSibling), 'change');",""):"");}}function
process_input(array$n){$u=bracket_escape($n["field"]);$r=idx($_POST["function"],$u);if($r=="orig")return(preg_match('~^CURRENT_TIMESTAMP~i',$n["on_update"])?idf_escape($n["field"]):false);if($r=="NULL")return"NULL";if(is_blob($n)&&ini_bool("file_uploads")){$ee=get_file("fields-$u");if(!is_string($ee))return
false;return(JUSH=="sql"?"_binary".q($ee):driver()->quoteBinary($ee));}$Y=idx($_POST["fields"],$u);if($Y===null)return
false;if($n["type"]=="enum"||driver()->enumLength($n)){$Y=idx($Y,0);if($Y=="orig"||!$Y)return
false;if($Y=="null")return"NULL";$Y=substr($Y,4);}if($n["auto_increment"]&&$Y=="")return
null;if($n["type"]=="set")$Y=implode(",",(array)$Y);if($r=="json"){$Y=json_decode($Y,true);if(!is_array($Y))return
false;return$Y;}return
adminer()->processInput($n,$Y,$r);}function
jump_input(){$vg=['select'=>'select','insert'=>'insert','search'=>(support("table")?'Search data in tables':''),'schema'=>'Schema','database'=>'Database',];$I="<span id='jump'><input type='search' form='jump-form' name='filter' value='".h($_GET["filter"])."'"." placeholder='".'Search'."' aria-label='".'Search'."' autocomplete='off' autocapitalize='off'"." role='combobox' aria-expanded='false' aria-autocomplete='list' aria-controls='jump-list'".on('input','jumpInput').on('keydown','jumpKeydown');foreach($vg
as$x=>$X)$I
.=($X!=""?" data-$x='".h($X)."'":"");return"$I><input type='submit' form='jump-form' value='".'Search'."' class='hidden'></span> ";}function
search_tables($G){$_GET["where"][0]["val"]=$G;$nl="<ul>\n";foreach(table_status('',true)as$R=>$S){$B=adminer()->tableName($S);if(isset($S["Engine"])&&$B!=""&&(!$_POST["tables"]||in_array($R,$_POST["tables"]))){$H=connection()->query("SELECT".limit("1 FROM ".table($R)," WHERE ".implode(" AND ",adminer()->selectSearchProcess(fields($R),[],$S)),1));if(!$H||$H->fetch_row()){$Wj="<a href='".h(ME."select=".url_escape($R)."&where[0][op]=".url_escape($_GET["where"][0]["op"])."&where[0][val]=".url_escape($_GET["where"][0]["val"]))."'>$B</a>";echo"$nl<li>".($H?$Wj:"<p class='error'>$Wj: ".adminer()->error())."\n";$nl="";}}}echo($nl?"<p class='message'>".'No tables.':"</ul>")."\n";}function
on_help($Om,$Gl=0){return
on('mouseover','helpMouseover',$Om,$Gl).on('mouseout','helpMouseout');}function
on_help_value($xk="",$Bk=""){return
on('mouseover','helpValueMouseover',$xk,$Bk).on('mouseout','helpMouseout');}function
edit_form($R,array$o,$J,$Hn,$m='',$G='',$Rm=''){$xm=adminer()->tableName(table_status1($R,true));page_header(($Hn?'Edit':'Insert'),$m,["select"=>[$R,$xm]],$xm);adminer()->editRowPrint($R,$o,$J,$Hn,$G,$Rm);if($J===false){echo"<p class='error'>".'No rows.'."\n";return;}echo"<form method='post' enctype='multipart/form-data' id='form'>\n";$nd=false;$oo=($Hn&&!isset($_GET["select"])?where_columns($o):[]);$cc=(count($oo)!=count($o));if(!$cc)$oo=[];if(!$o)echo"<p class='error'>".'You have no privileges to update this table.'."\n";else{echo"<table class='layout nowrap'".on('keydown','editingKeydown').">\n";$Oa=!$_POST;foreach($o
as$B=>$n){echo"<tr".($oo[$B]?on('change','whereChange'):"")."><th>".adminer()->fieldName($n);$l=idx($_GET["set"],bracket_escape($B));if($l===null){$l=$n["default"];if($n["type"]=="bit"&&preg_match("~^b'([01]*)'\$~",$l,$zk))$l=$zk[1];if(JUSH=="sql"&&preg_match('~binary~',$n["type"]))$l=bin2hex($l);}$Y=($J!==null?($n["type"]=="set"&&is_array($J[$B])?implode(",",$J[$B]):(is_bool($J[$B])?+$J[$B]:$J[$B])):(!$Hn&&$n["auto_increment"]?"":(isset($_GET["select"])?false:$l)));if(!$_POST["save"]&&is_string($Y))$Y=adminer()->editVal($Y,$n);if(($Hn&&!isset($n["privileges"]["update"]))||$n["generated"])echo"<td class='function'><td>".select_value($Y,'',$n,null);else{$nd=true;$r=($_POST["save"]?idx($_POST["function"],bracket_escape($B),""):($Hn&&preg_match('~^CURRENT_TIMESTAMP~i',$n["on_update"])?"now":($Y===false?null:($Y!==null?'':'NULL'))));if(!$_POST&&!$Hn&&$Y==$n["default"]&&($n["default_sql"]||preg_match('~^[\w.]+\(~',$Y)))$r="SQL";if(preg_match("~time~",$n["type"])&&preg_match('~^CURRENT_TIMESTAMP~i',$Y)){$Y="";$r=(in_array("now",adminer()->editFunctions($n))?"now":"current_timestamp");}if(preg_match("~date~",$n["type"])&&preg_match('~^CURRENT_DATE~i',$Y)){$Y="";$r=(in_array("now",adminer()->editFunctions($n))?"now":"current_date");}if($n["type"]=="uuid"&&$Y=="uuid()"){$Y="";$r="uuid";}if($Oa!==false)$Oa=($n["auto_increment"]||$r=="now"||$r=="uuid"?null:true);input($n,$Y,$r,$Oa,$Hn);if($Oa)$Oa=false;}}if(driver()->primary!=""&&!fields($R))echo"<tr>"."<th><input name='field_keys[]'".on('input','fieldChange').">"."<td class='function'>".html_select("field_funs[]",adminer()->editFunctions(["null"=>isset($_GET["select"])]))."<td><input name='field_vals[]'>";echo"</table>\n";}echo"<p>\n";if($nd){echo"<input type='submit' value='".'Save'."'>\n";if(!isset($_GET["select"])&&$cc){$Oc=($oo&&($m!=""||adminer()->error!="")?" disabled":"");echo"<input type='submit' name='insert' value='".($Hn?'Save and continue editing':'Save and insert next')."' title='Ctrl+Shift+Enter'$Oc".($Hn?on('click','ajaxForm','Saving…'):"").">\n";}}echo($Hn?"<input type='submit' name='delete' value='".'Delete'."'".confirm().">\n":"");if(isset($_GET["select"]))hidden_fields(["check"=>(array)$_POST["check"],"clone"=>$_POST["clone"],"all"=>$_POST["all"]]);echo
input_hidden("referer",(isset($_POST["referer"])?$_POST["referer"]:$_SERVER["HTTP_REFERER"])),input_hidden("save",1),input_token(),"</form>\n";}function
repeat_pattern($zj,$y){return
str_repeat("$zj{0,65535}",$y/65535)."$zj{0,".($y%65535)."}";}function
shorten_utf8($Q,$y=80,$jm="",array$_j=[]){if(!preg_match("(^(".repeat_pattern("[\t\r\n -\x{10FFFF}]",$y).")($)?)u",$Q,$A))preg_match("(^(".repeat_pattern("[\t\r\n -~]",$y).")($)?)",$Q,$A);$y=strlen(isset($A[2])?$A[1]:preg_replace('~\n[^\n]*\z~',"\n",$A[1]));return
highlight_matches($Q,$_j,$y).$jm.(isset($A[2])?"":"<i>…</i>");}function
highlight_matches($Q,array$_j,$y=null){if($y===null)$y=strlen($Q);$I="";$F=0;if($_j&&@preg_match_all("((?|".implode("|",$_j)."))su",$Q,$Ug,PREG_OFFSET_CAPTURE)){foreach($Ug[0]as$A){list($Om,$Zl)=$A;if($Om!=""&&$Zl<$y){$ud=min($Zl+strlen($Om),$y);$I
.=h(substr($Q,$F,$Zl-$F))."<mark>".h(substr($Q,$Zl,$ud-$Zl))."</mark>";$F=$ud;}}}return$I.h(substr($Q,$F,$y-$F));}function
icon($rf,$B,$qf,$Um,$c=""){return"<button ".($B?"type='submit' name='$B'":"draggable='true' tabindex='-1'")." title='".h($Um)."' class='icon icon-$rf".($B?"":" jsonly")."'$c><span>$qf</span></button>";}function
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
#,<5F60=.DmmVj*!6Y,vLqPc2443S*-I32v==ZWdo4dR<xcq=.Tq,7iqik(&DOhm=Qoqt*5cDK;$y{"Bm
kF?)=ch!$5TIt8QN<9$a#R+(O1$s#aZNShtE-(.7_F``i:"}kxp]Yx6^OM^;e0M+??x79M7Y<Lg41Q@!!`Lo[[?s0%`hIXW7A?*rG&c)dt_!.k<P
!LH.#D0r[5j!nBvJFZ/
zca<%!yWK?IJCS-HJ[HNqf_!.pUz%_:@h&2bU2SnD&W"o>tH
SFubJ3(pod1+"-L.aeP6<C%N^VY:W1T:9YL&[C!X#~VLBaTF!aM@x"Jn8001@ofj_y.fRnEo1ooO_No)K-@D.)JN_QZ_@-sT5;N|.j%#r(hxf|D;JD6gJr<CqQY;E{2U=~eId)KBcs(+yT0AG0eX3;AyoGZwhlhi#X4G&W=<dBZR&)ef4xhwP:[9Fil*UQth<COFaql94jP34`J&C8]sY{C"^rVW_HOH.);{<5fuu&Y}TX?f(q#UcrQ!)3<7mnY=?5U@i!/D$K`n_rXT%vKD<dPBL6rr(n;?JKZH`1wlat.z
Y03*&^jP<h[>f)AYOQRdzD,NHD"(deXs0vAr*f>SWjb?2>=-2oqYE4&Zhc3i&9YW7i2xqjc%VA1,";Capf7<gW?OGIeLR`3<nPzQ(8=JL5w!xe.V/cY`mrrc}A6Fgcr&xo?&xe`6ljlw@SE_5wYVUy.&NfuXTfGCwJTe@Y/WAb8Z8/3Lx-=NNR)Y=o25@MDn8aS&"jKgv#2p_hBN3hAr%<Q`JWRR[$_C5-!!^4U@%kl=[#P9K,FIEY%EOt<5uRdQAZ]t%R<si6<O?wWEAEBIp($eb.HAC_>3a@yshOWmJQqipBLsG8~>`UTgfGN:mhdI<PLDzGUDLGlNV0hA`jKjhEpV1&bq7.!K)Nu?(9y%@Pe[
<(Gh8*RG8+k&wG+veu
^WerIBNNs76r#jb)_E+X*32W:U:C;LH1zcPJ?1YOKvWWz`bQH,/[+eF@mS/dvD}s5b~g<z$9=-2OaA%7
y[sakM[_h"r[F4`P(1cLxT*bQR6=%kJ`*6oM!OKX#!EX]QZI#Kt
s!o}vHn(C(^&ORC_h<IV`obwa|3p=F3(_bm~w4^:5xY!/k1GpX/vn=^W-{%
GaD:^K2-"5_"3b/ySXCP:|s
@FrGD*3U@=$Q=;lsPAH1tBP2oA70;4NeQOC+3@Cv"NM>.[hG"*F]PB8(/J0T81l2>*+XJ#qy,39V:%BQ=
w~7~!`O]bR>:?hLBCh>_5%S!.PS(0Y77oUkv00%mYYWbD-gEukB
RUv&iYZguO9%y@NcD[VnR>bAlY%F(4%K^U)3`-URp3[{no*.3,<S=4#c/`?v5~H,FHL|uAbdq.cwM,_=s[
:ct);`0B"xvqM?V_S+kl!2KQPpL<2Xp`&3pFXaV=RII(Z4GM<mY5-/2:SFUU*oUj%wF4@UK@i3_.OFgmNY0d~0Dv[<]0`q024;@%m=sg!/VP,b<G[o<;+n8@fGew.<gZ<Z#1OPEc{Y5i(I;<BAT9>gT`63MUwH(659gsG.dnuC1TU`/76G0rhbHodRo4,t>kG%,XA*1;neXg1w7))*bsl5*BMEGaaX)`3">,Y"?b><33K7gb-_n`)rn_qITk:GFFhgflkRq9qIr!@,/rOYBQ8i"Pv.b4]ASemkfhi>d:9_ihN:?x})m%9QgIA(BmAu1N8F#3H8R$U!GcERwKc4,v#C#Cg`nY&.ONKBXuolK]c]"l$#m-Et7N}B^@N6JcH1)Eb+3I-A$aP<Mxx1uU`[b#cH[1uxN8H4$mcifjxAXWI)8E[x:,Z+!HJ(K=jIDmZH_k4awgVS;["dZkEvG:G7QwF_egMN@DK1J,@f4dO3`Tpr09uH2M"ve.U8za~1dm]`[>XLzTf)k0KZmx27V
n^;m[an?.biQyvKlUxbAVa0FdXieVZ?9E2H$fuY6ma-s3b[_"326ck{nvuX_hMrf{Lsw
ZI5%v:M^S<rpc0,uI%e]F0clkXr)T>>E2!B$FKc$4`3PuwV5odughhv,7xlv]Z_P2$!6fmyihSd-K4J|u$;8Ejxc%JNF/&q_UUwdv,Y|dI3fbC_8buxQ8s3Vvvxo=AM:cpc<iJxcvdY`cRq$58E)]<jJZBOuO6__dG/&>c)AmSPkJmDf=DwEf_D@hiH~%_l8cPeCK+sRI]i2g*,Yh!B$tX2ia1/(iTek3P3^_8PSoH7q@2*.T0_xu)&}>CD55)EJ=.rXXzx0A}n<i70"Bh$uS(*{$8e5$x/Z1#lDnYR3C%a.lA9>?Y@B?8a,otCSGLjw,SnwbDy+&Auy58#|.fsEMOP-Erox.K_!:8:bU~(MGM;62!]a$j>j1>;^!7*/PY?Pc0<@W5>io>e-^0n{)Uq)h`gL%L=RM)IdGx"l5B*:ZFt]@&3Lo>`:=OiZ<w6}B:R"LFwB0[8jgwK<G}tmu"o&V%0~`[B`08yu8{wz6eR#b>e5f;u.s2KlMX8mgwn@iHocv]l=vygi]>/z8]ATOe>+",*hrjX.u8Q[B(=n]DC|6lEN8c9|n%J,[kJ~b?-sDJ]a#HL[VX%H)?O(FX=h`[Z+@Gqr$NG-*K=iO"p@!:,Ys<fl/&M,7e)9043fy,?*wg-(`<4aok0>nu>wL+=16U-_D?c(y2YX
;AfVIMj18WoK!a0HJR4cvQzYXijD@,oCeg5]9q9$kJi(Hhe-qO|-bvAu=uECp"M^g/jN1E7DvtyvYf@emMaUd$b/DhVnuhxi$xS?LL3-Z=}_|B``Yq+5qcGW;B%!7$S^=T8H3Y>OHC~qi1O+^ou6:EZWR<*HdExTUXCER(OG(l.AuO0p$3;Mv,fKzSL.)aL!d_RFLd+.$qprx#hZKS!^Eo%k_o
M;Cg/~F:
$.hx,o_DVla!~
~L!d|;6QUrukX!3_&*DOX7sc*ImaFKk,_+J$U_
@4vupHxO][ERVMahT6sm,,WN*$3ymI^f.**1Ne:Y5V;2m5ki&Lc1l[K&v_f1HA#:F.H3DJSqeh^=D0G@Ew:m9pW&>XdMwfp.yAm_l,+JT[;FJ[WyVi3u^S_v>s3!qg&eXk?Ka`c%^"*xC(6a*T^P7WTrc=e`uFkiw7A}fZ%=kqHz<+Lgi5stD%N8bphObsozc>y%/vAD1Hh=TlG%:E?rFoLw.[+DX2Jei"NvDAVH/QuzSXmCO)M&>"jztw8.y5hK:-_5]xq{rwnU[<&#aK8-j^P<0*]*7Fl>2!rH_WVG,c0KBxFiY[ha0b/y!b#d9;W3:,mL+d"j8_gdV>0GLHtDZy[-,M^3+Tm(+}g2R_Z(3xlhxA!ir|:U^O`UP?+wkn#z"U=M1"=3-3aQPDC>6k;mN+XC4`h6tK8hCJ+}0-u%`!*}V1RR#076"hR[(0x&;NtCJ^J<vQ+dV[J
W2`YynG1#O==l~)t&w@.*X28+-TV9C(8gh4Ie}kF"Ghs[5V8xg0#.!^Y3zE27qK5H"nO`_k8Y[<krL#]vvkC!$BFXZW0[e"Ha)+5dA:{Xw=J"!bJKWtx;D9|>,eo*u$UDkDEwd--DmJ(`5W<Qo-Q*0C]"P$H9JrsJIWKCO3s1;<aV_x;m6RwsZ=%$Cqub|p609udYkZ5!]DAGFK"_p*&1>X?G%jujw?_w/
g4vbfR*hZL-p1mZS=X~vKJ$=ho/_J
j*"V.eX*/B3Q)s,JU?Snr$r0y.}/=`a).n(;}Q~WY?/v`2AX^mm<QZt*g=.3N1m,.7=GB%DgeSx-QL)fpBX5cH9E;?9Vil^UrPT;qMkMxk:PtGe"<`1g{:LV9cKXLcmxE&=C)QnqgxX^i3Y-42-_/emXUHhkzdOr?!A@]Axrzvy23in;k>b1]3s5NxF=,7}U3xml=)yUu&OM(Bfxd');}elseif($_GET["file"]=="dark.css"){header("Content-Type: text/css; charset=utf-8");echo
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
decompress_string(',hk^CG^ZE.5v]nf>]Jce[)I4
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
7LjBrhDNv=e8p
SSh=%tVbXF4rrgflutxHGb,%xUG9>*xE8`M,-O~:zAR.ap#:sYsN,d-cRw=6U`m=5;aQ[5*,-WP?xX0u(q>2;0aSVU7^
k3lrXj`"TcuX^UTB"gP~5PEjG0BGtpI8yFiZoym&=|xtvo!Q+UG+Xr8(
D4
)HK~Tj,yH-^5vu*lh87dw:2{cdKM-P5z47$
61dRS>.@4xv1eOP)lEy"l19:8zA`=igEg?^J_qL{-M;Ke;)<r?Rnyx4rJmX#98ml^E7!CVcDe@`kqeBqQER8QSV*PY)xj~u)ZIMP2B/yW,9jwI6+
!y8<"tJpJK|wqkBxtQ^b?XOrTj-Ki^Uge]5WCvejf>_3?+jXat@Z<!-?_[+YQ`5=ed%c.b?
CRzS?Xp@9Cv,zec-&qjya9PnfXGol9MxIr}z)H*6QW?W_z#T@0{:Y?O`%M$spZXO^Up];vf6"lvhFQB&k.8;3f`PA[.n``Yj1*2$~)]xh`-O#Y2$o/Il*ob*SE>u@7ayXxoa~H-[#oVt~/v[~-6CP2L?U6X$ME1mHhY8lN?y"6;qCFeGHZcl~?$L`ocD;/BN8emF?&>hA]v";ltZh9cwI?TsF.vK9A-f<S3]^8SH,BbYK5N1)@c?]Jo&-*yjQq.=wdMXCqGsSvu+pDo(.&"xfE{/]!k;W`!"EwPVhl~K}agB(ss%MTF,Z5CyTgJF^_+:|!]Zrg"1&/-8:xxfNI0+KFU4#yF+Mt>JtMAyG*-Y)T#euR:&gjOT^kx,F+/=<2r
`b:y>y;KO!A<ma-Z10A:c?}5iV{jXtX4%dWM/(A-,W:8qvQRUm~B4,S#(JQBPi9qn<0ewonNit#-I=v5_Q>D!-j4
DujdfbMNk8Qb[ge0i>M8RtklUL$LC?$((e[8iP*<$
jQR|H40$e,Y&jQnd3VJZ#FWFRP^Fl;.5fMq36AiuhPZ)1o=Vy<LbEmE5p<7H=P-4=MZBWXl-.]r9EV)UxBo)!$$NZHwEU9z(ya
8rf1|-8L84JPvMlBz?)i`;8kV:ILt%RQoh:I~7S.)KZ;LZ8`lI}IL15rs8GSj7gr3(i`H2/tcbAmzUD?/,iK6W#.1G5.$Kp+#pkOnAssq:a`6*bE"KyL^Q1H]3masV(cdA+&|i&pQ.-<%-x!Pm!a|X>M]d`NJKPNGuY?(nXu-mV(/Z[6#l>:zI8_,4zD
aA5y<*Us(-/OQ]-QOjKgA)=/cH"y
AH>CgkpU=oC?vDW
VAYYz+3Yai:`d2TDr#_l2*DoFmT("$oE&n$d)#dL&.deDK
J7KyH3h%@PZWwmYv4n;84Eail%0NdOR>-~^6*&U(H;FsMs9=bRSj#;8a19lF>]g8m?2`6:P|lp(2HR-#<zqtyR?J=a/.Fgf.y,pDAeC4V#xjgx^4DTSSr]9}0|`:?/0F-[D[2faKO]x:"^c8=|I*X6yBVEESSf?[0W:j_<`5Rb<FZ(Vp7M&bZwb%H>2knA=?2>eXn9yB_mVbf.0Bgt3s4`x/]W_.5r8pk.N:ZAT}n|0%n#/"a|qZy1ZA#H#,p9><Za-n9UG`@j()PgYvSdVwy2F6yaI7?~2c#XoJ>DSKVDy<)D1TgwB!<JeV`cV+UKZnfJO!$n>e".*%5F`J44k{>h2rf
eKQ#PcncT1HKdoUjRMQI"!BO!@YqgQmAB9i,7JI9x`_gJi`D
m!TSsAtAUO@aq8

C7KLU`!pi;[GWdNrpXp$v-[sQBHC>(]#N"XnHRXXfS7[hDpQqG%
Gb
Ce:a*3`,Vjw`:wb-5mB&gwkC3*t!:8"4Ti3=Z,0RJ;7tf+@`?p)9c;27q)EfDl2IhzS#0L=KDDZ|C"L4xLM5TYAKF_vI>0>|v+iK8c9H1mCWJ(9l;gNvqN$"L227-)so9&Ve/yu0]<J&,{J58q+ml>3,]OOMy1D|3f@1SO9]3LLg-(wQNlGkgse?t*?2+LTUux[,"d-44q[uU[$!v(wH9AX_[~@{6O^c@e+wOI7Z$1KgbZY9Yu
?&LB5UFYa3VGWz(JFL>uqe_4t1X0US3dVm7cKD2K"Y9THN[+V5gRO)1k!m>Z,&w5;UaC#T]&P)3CK0oKocWxiQ1!z!2&r<.TvpB)x*nHPD
E{#!cxJ&-aUD`hYH)EIqK;1~eDJNN+N1qGka`#CMS%`lQ=E{`?NKS_6ify`u9)=Z2|p6*4y:le0|&
#i;<j]e-#F,FYjBww:."Lj)N
glpCR/0^Sz$<jND
tVX*7n!r*={dVQqdF<4jh;x-Dv-:c:}ZijGX(,7].t|kT0^tR(dy-QsDi%4+MvmuymOW4Mz4)5^#<`y7HC3v7dK&cokl!_FLj_gbqqT3GFZ7Fq3pUvl>*AXQK;EVyj-r6w*yLy[cW<!E"vcKY?>o/Ri(:,6%%ICYiu&HVG=sQFmt:a-M2Pgb
gB60w!@0Jwg0abIa=QL;v`!9QzQ`SP)@0E,["FMoH3CbHR=m?#1^cc7Q_hM
plS4m"AiaD2BX?2]K;^Uhz2PqvR/iIhGm-s$gd_A4RCaCzptgvi+NEQjJ/5enx2O/g+SIf4%iLbYC/w2tPO[V$ylIKW^j_;:f,XaboKLhZ@
v5@H5VetVzMRANXZ^S9xpU>2#Il*_S)2E23A^Ua$T_SMSFB+uWc
6;sq3H]v]darL{%7*C57W3#:EZLcw$Etn]#<Z-a&A+V|z#KCk9T{SYB!TogzT*[Xv0rVt"[W7~w#j"<dBAuca=g,P(oDydm(N=nybEy_@Sv0tQQD=X]?T9q8m9y03RWiHKmfBfyV-xI4R!@.;Z;3E5oGmT?DZ
8cAe?gEPZorQ43C[
Qu8seo,5_OHvzi1ezp
O@(#2S3tTC/,O(g[b&.OaJd"Gg
^W
^/mR1*c=IHw*Js*3*W^<r>9RR+MZUC@5q2DoAqHc>L0s!X3,Yu/S.7hU9TC6i-yqQ%YvT16[g0wh^xX`l|THH)oo`2jlY.q]qS^lpAq%k8mFxJcID%]dbxh.uIE!sGj&"{4~IWC2cq2=R9Mrz#&~VkUjC6VX-%d}`]Wma<?5XzjYLOh;7m)_4G?w0[&j,Qm`oJFj^@bQ4N[~5Y]y]
?Jt0nmDHYAjlQ%qr4{`qEvX;d!n%1~$;tpc@.x^Ja?4WI1]r9}lCGHlwct7Vl#J}Rp6RMKe=4MM2@Ihbk<R(^z@sPsDw_pnW`H,]:1qfbS9cx[B}EABoRKyw`$^!9S8qgKfYP8mMkT9P?QPZm>UlO`/
-$V(]%#rQdnAUP`.@jN_LTP1`O6<iK`
&!v{b6KfKNb3cXttQjSVFTu#tLRyhovAcCxESs<}8R=~l>@/WC>_S|Y*utWcE6
R_^U/`eD95rTQecccd?I^lZ@2O`HOP)]G%>(q^$vM9:u$xNDc,V2g7}/%B]%_OJ[JBPR&N{]}9qM]cx]q34`X.T"]y[Iy&sa:$!LdjZa:UDp0wtk$<ZO5O^]*k}+5>:IuaZFc[a-~O^g+y`PA8Dhr]3uv,>_JoPB}]Jz%6P4ol0F=USrd?7lsl=1I%]PDw@iHN;"1dj$a3},xi:7BuE)XX|nELBi:@`n@yW(.sS4LpF^M.
?rX;h=M"H9nkX01lQ;2)u!B[!ba?8KiscS!Cwvkb"F]4]ZZFI&a2cu6qocd=5,^t(5oj9#Hq%g+bZIpUJ{nqKg?RK[kZi3?3!GCS09%JE,MR)$2O%_?+
brTD
MRj33X2@Q*S#w?OkiN!N0tMVVwR(6@L?2DBm$9sHc+
=VAJKguaC32w:vyT1+cIrE0n%sOwyszH#!w9CgIHsxl$o>VC?H|_,!i:zES=C.fwMZv8&*GXj:A=+vobG?Rw%-6m};K6zyd[O1:&v1f
lxkSHrL[|x>ZIf,N}]#60<eUESUp#(1w)>*!HpgNM(5#7fWVKlQ3zH6NC()X%*0dHs&hLQ;w>8%Kjsx!Pl=54s~;ugT*IO5X!eyPbGDYTvQXvE_[[[nyt-H?e]2y^ICt:TtMC
</^Mz7KSObXB@f@vrL)c`hhEpTUF<cp5*HXW7Ee).:fsL$-Z3D;+g,fTrw7b6oyG+EN`*.zMj<
n3!%
[sn@{s3jE2))X)W@hir3a46+_QxX2V(vQIH0P.+wH)~h)^5c:="nFrzntw-?~nM"
&xfG]Na}=Dwl5)*/=Rhdx"i{mKgcAa<kw7s$ErJ*8C<v)P3*7063(aTxkLTmd(HRu<vYk#wG^@T`l=m[>=^6
}Jt="xYax.k:lIbQ#NMTm6A$e!aw|a-:09SK8)[LK[t]Tj,9LjXHNvzA~`A`-YEs~[b>:T<rgaj7.2)7(i@h0WXh^t{(/].fGOH&$s+PBN)5NLRPY^sF
Pf>viA9nH;h$,emt/~4Vj;A.ci;P@ogN$
hxGVML0Q;JO10I-zvC]

CG~Qg
%Y&ew^keBd.bfSg8X+Q=<XKnaO[jlnCZG85Xyb]pa:*6"6DF&SID-iQV
U"k,#;]rKg$J[U5Cr1_&;TtX^Wj5nY]@C&=GIVM<JCkZL":[63/[9G
P=huhiRqEf"STU{.H#yU3!I:wX$rHwnR^>b;
B|Tu[rc9`Q<dncgaSy&AkL6:OdBl3Z7y._U`wMVsDzLr_k6U)"I5&zRXM^-1Z.x]Q`(I[N@tT1Vw;qBqf^k[&KB
lriAHWeB*VXS;:I-w80?tVxD]Jb2[oTx<mvY
(ns6Lm?+!4.K$9;e_tDuLssJ@sI^_wK/<B
/l-VgebNTqOU0Anug+RFh8DJ#yE<A%v6Kn&>IFS{MTy&?WHi/!okE!c:=&%sbtu]8M7Pyb0pLRab!*P@f7XcBus<]lC?NH[&IJQ|K&j$7{0-<E!:jj$kta[GAd4.(,&rvnDQ-=P1WJveYyn+0T9)x*LG[E?VUnf4U-
}gz!O3yD/?~a7U@?0coq)m~=P;*?!kq?b=6C;?-M4^0/~:>Y
$JvqV4/.Fx`a`4M%.s1TW,g?TKLJ;#Q="8FLYaa.8@(T
M[Ts_Mk[wpXF}G,w)3Dx[1(Oj;)cZ/Uln;cf^T3[_[BB|IBu*F!_|+lTEtR(}
nlI
Rmp]iy^mVq|8lTE=2<TkRbh<|*`@--OJuPK.}
g>~i*gdt#-mK{wG?Tv5x"K[yB[Xgj^TAsK2Jl)4ID!{oEb>+`S]RB(c<])(I;(tu>`VYhhHrPMN>cgxiyLkx.ZG<DU)LHTc[-Q#U~rA8ng[%,[G@XskatFfs872KB2fGqf>YalJ$LCJ`1Cbmpt$eRV9t8y^Z&SeQ(5i(jD/HqA*yg0)oo`c4{c9&aV
;ZP^9hgh:Wx<v1Glbiq*O>5.K:xD#4O4m(k,I6G4kN@~5]L4ws1
GwNzP)RP=Ofy^e^,TT/ZXfg.%7i0t|kra$_C^,szciksau[X7X=+E(j
]7ih/?eXY%;YAsT>x+
3Ij_/nQu.gSw6HOM~qIZe[84
mad{nL)z4}>(wU.RHRI>x1"}6T%tA?sCKC2ngaxf0CrB0D!yDWbRx%Zo<:b+-m5YVA1$^l
?Jk4f.G@Z!F]Tz$RpES%q<{K2G;Wx<$&4(ZlVRn;LW7A"If@.mIB~4kwLB}7~+eQy!Vr3<CnGC%Z?`SyzO$*>RFJd_QlVWUs6jNK*tP9378-77)l0#B63rIjwaMyR`J%959,INrJ
P,cYwt8IbI_.p/NbhgNIdZZ3<UI[-~aO-F;wLle;ay.vPp*dvo7+]HHa!,qp/8Ui[_FJu*1M#$yzd"HQQ+^<MUGL_pl&u7?XVDxyohV-IR];hG%XRG7F"0i>[r@9rx8~=8`1#:@-&ue&3mIQE$>G["AgZL2@MAynJmeq>fMr<OY$3BtG?l/[w$KU?U9^M
ixm!"2EK3p?NC5NaLxR],il|@@m&&F$q5F[l6)QT(H+|OU>_"J(7;Ut81WE$PbUL($RMKiB7K!WT/v68-i/[(i*h:Bd6-_H<:U7G>xbyGH/!o0JtP[@/qlOqqhvcjm:>cYf8@F%k8e[$v^jkuyVIfkIY-Q.[F?;y^pEJ=P7~KyqICN]f;NLhw%g"XZulS&y6_c^QZ
]i[=PY2^j)bEskT">_Y$pm.hq^kNEDB%/
GvnsMHufVA+T6rEDoD1Bpa7MjcZABiEhB%aR
"M12->c`"aLRo@wBq_D:nC5WAK`?0Nqx-9"#B9{DjmQT*wmqjua5r.V<B"_p-@zXGCV,D:.KEo%yzpcTe;U3N+MZ6T1ysG*F:2cp_Up^h#`skKWHP"YaaNJ7a_`t*nNW+.e6l2h#cT>`cBQG^tG.,x`(qcBc90KnxfPZtI]4mCZ<yY^h@qIY}V;=:5&v:fa#MTi8:w@&R@j0YLs9!d~$n*Qyl.-LO:w+<Qo<VWFE6Z^tbB#)Z"OmrM6HFd;y$"ZM??N5
g<QbO/pSU*UwAm:}A%;q<)w:PFA2x%wq@ua+w.9(D/%MWX/KM-K@n2:{;X4v_*"T2W4Y+?C*M%88;NU]ka?SXORN=aAxx9/C@OrP#cen+8yDBm!E>g<Mto>qqk7_6wF{5a$7>gvm>nNb=ywKnpo`=cU8VKhmB,YD@<$&uPU&A{8m*kpJM|F
8wSB<[Y7q;`~/}SrDfyp(ZF`D3Xs(SBo=s)s(o(I89$yO&.R._U^I)xCZb+,E~K50t$2]V#.XAO4T)%$8$3Yowu%&R_j,kSk[(nkG)G0:d!WhM:iX]
hUmD!Guj+BMBjIH;<2HCHgw2ks$wk7FGZALS^"94l?bMK.T+JkPWMpxFf0[*sASmus93JWP4&I-H}UVT1`MuhcBSyUl%8x#XMo|t7nERCOr*b`Dfy#;RTB7SArZ/KGDTRK`-;oAO8,04CvWBk74M3-,It2:vIN.rl%~HKgpY6JArJ`i`Qc3+YTh%>6(q7o-icy;gXq+mJB]E>FWsa
K&.QxU>?7hI(xCZf!gHGc]"7ON/
3>+!J7d$
T+.Kj$tQ(84XI0dTL}Sj)!RiV+]Y^0jf[O2}2rVdlqsF^",UJ4
xtW/!PAK2H!/<uxcDxONssY2s_!LW@O,;GE39?clz4==yY%F)CKgd-qy?Y6
vkfkVa8cpv?_x;$iOtL<N@2i0xC6m/Es3LLIrbm:UjO!Q9yc&d!YX+Zr(Kd=y86.}Mc$<P,PzwV%(.qG@p:^y+i+C>qIp[+4?C&6iAF<uvV$&)6fCD)nj!HSt<+fl)R4*Wa`JNirg2JUK!
[bDE&vHkfy
GnfWnW+:Ugb8V?eU.P)NNB8]OFFB]OB<EU;L/O+T9Q
J#-jl3seg;9wTBAlG.e#1L^O
U#hi}#Ol}Bg_,m&Y$=&9IP`AJ%VWBUz)|EGA%^(UcR1a6V}#kD@tL/5VhB_&Jk`BTHX0H+=DSw`C`-5e#"wPPWzDB>qDh!pA6h&c|EQn"Wu,@D>hPCt9-5*%,w^O}jOY$j4A0$r8n^DOZOqs.<s]E3?qZwmkkoC:BO~;#LM-PjP>pYjI}1<
b%&]BI;wh&9L2cdB#SZ!4P*Vr_4Z^XFM]AY[VcO0p!|N&
xk{b*4JH^WF(};R07VTr<*F5%S{,Cby0.TEHh[CJ*D6ueQJ+qDc&okp]_+E8?J+;C3j&83@Sp=x!jx{u&d:k?mLO
t]=H9
^e%y
Z7j9XZd7cB#wp$-!vJE7s_/&:P$c_vs[^cGHot|s.KN>
JOYj8fB}
@^c4G)mE.)^1GIDg80`LnX-Ex8j@UsXa$86spyFc~q"f;IDKi;;!0z"yEWE0Zdu-368;_/r@nt4[UZjSf:Q2)uxf8O?E*8Jn|"u>#<7nbQ^LM6Bwd,N:+_ZXk1Gb)T#SZy=,~_e<_^P*0Ob47lSnLU<D@BvxS0X!HV4X[ud:Ob-m6v,c~(VkVFpGi$KH#<T5v!PbJW
8"t6b(`:H*vA69%SYEgovda<#;.
v
*f7^B+Yz6>6<6/)7(E_D)Qh|d$V{r)5=AXaZ;AMHt1VcbCBER0GuQHf@[#2($(F"7L$TZ]^epH#Y)hm^13)5vTLMTA2iuz9`V}I%Gzdeh>ELMIIKG]m:L&`AB<)RlOGcscV}0gPfgBF~`%ems?=@j4+U#Dro;Ky}VoT#REcjO?F-[MG{gKan6#>(t4h
)A6BBWT[GrKTr%H7S>Sdu;gB-c+QFup)h)2b(eZ:rr.*5D@[KorUu[RnjMD=TLo8I_k3RZQkNV^WoUqPOG2i0)+*$&Qp:z.79wmN?B&;(16WMNF{8g.Po/G[M]yQ>P<4C+H1XOA]B9A-kkTRJkLJmL8?CMWst06;nS@W$Y<UfQ57*4IL,NI&Sfb[r5G9l_t@h:4WlBpBUUVjr!x{i%go[yb7S>#i1Svw[VvX)3`AvvdY1N,}.b>_@44n.aNK1sC"hy?",8&de`P?oJKdkBonu)^YTj,F@-.>cd,[qXh2-JfYm<ppY<g;./DrOl=7[:.KlPg(hA%7]?1Ci),l:.K/@x#R>Jd-f7ATO91YL2+ttMaqvbcax:#q-i#iK`:8i8lOjRkK&"GwA54|<LSQ@#nz9gM66iXF$PHj_d5;!fT&2V.
vi4UlTYbR,^fXGe08f(Y&PAm<Gt.KRDc=mM/k?2|yT78c2H9-hVt&PD+1DWwT+)N4f4_Yjgic3E8<`?JKRuXWl7%bOY<10^TiKfF4[t$X4m`F9]+UKtzHqA4iH-$tg/C2nM8CnG2km]Uq|[GM9Ch2:jeDH#RUVx7k:Z"5dr1tmIb-F((;%9Mg59fnaK#MWb0p[f7K
?6/~DOt6mcIc>a0`N">:B=LYJc/[?Ul^-NqJVy;W[|jowxNa@apvU,$4XOw~CZorHrl%yim3/P6`X*?"sR3=P*tx21jk9o);XgTj"WtKi.deRY7}yWCoCZTI*s!MJm>TUI;^F;:yb{"wu?u7vMw?t/I*wl2yd1SJ+rQtv2;jmUbW2WPbh:^_cjhjV;x5A*="XrB?stgOT5=av`aLK{0qV`^av"pk7y1N"mASI}8Y]n!X5Nq,(9!Zu9%5[oYdG{K@pQ(]@L9,wpKG;B@>nIH*UhQ83(P$Sas92F
s><8&QmP"iTr@+rl4cn(NIq?6fAO3Q}%O6A=dBGlOqE>e@lJXPMr1tm>rx]c+.xEj,L0P<%8xQ4onkJGcnH-J)]<$f{/N%zoConbWW
`9F:A)f*D!.<#>iPF4.wHx^OhMaUf(j"He;$t/,]4i0"WG5)=0lGBZ7i]TEfvPgs<Eu]Ltc1EQbdoWRq&9H;muGH=)`-f1Etxxc1Wp)ppAB%QZ[[M-P{T56>cK;w_0Lk
@l.D4,e@;8yPp)K.QFt<fNQs=:8m*w>F/AO
,Z"b<
*sl0W/;]2*z<Ss$0`T;akf.<|>HRSXw
{c}(tGQfYq!Pgx9;NN#MBkI8SG`c}]{:}`|cfXKv<I^m?3Dm$YGP=K{fu,%
y@esZ8g0[&--r$Q[s`tcGZ~Bfa+1p."hY0,#LbedNesnT.p)F/q0[?NQ
6@*GaF32gCH-!}#xCWiG4N.Ov~+nk+I8n{g?Ab6EN#q<d0
[9E<O2)kV_foi[i.z/8hGyHjVRaxKJVxZUj4hS]o{r>GMH;T?HSKEjtXo$EpeGm=";7yaNeF"`St)qOi`jJ`!TN]f
Hw-roM,Taux]EaAMD,pv8hlyCUAndFiiRa}@~8iB
76XQLI62V*wL)A2Wy?-|L,)Nny["C|;aOt3|b0)VTKy5a&rf
*%PPh>^lv?0:QLNM8yk4A3iS9#@=S%T*16xanYhV!^]_L6_^K],Y!K|K*=*XxY%@!JYiO2E_(dq2yt!XGonu.,nXF?eHF7Vvvl~U.6Nm?
:B
VCOW;a-[qXmql,chJ/,WR]mpZ24e,o_al?yZGdb.XJa#WWw7+"*}7l7?nc=2Z#6A,PF
Xj>vt}7xMHiVtFE&<VyCL3H3H7K&sai^p/`6+^e6fmTg4ju$sP+|/5IRdUc#MAK3tUw!KVw4h+wXt`q]"Q[-*p>Txq
`Fm4?GWiF4rH2l};|Kshg31$TWrcxTkO8+Zj6%`4,_^7Zm=lzxH:R"mvl*y$!/Dv<Eu
tx!:)i&^TE9a+Y}_d.Y+r*vtKrxc[y-4hsBK>xY+L7*F)EGZ/NDjd%u,
]Ma&**9z&gArn9_j/etjR/Jk(GbJ*5+`8KW~wB*_o~al3;F`$ND[sQi"Z
c|joToJk5@vZ$j-t:lDY3,JVu[ND$],bHx+@gK^k[$:62C(}Q![oPpI`PwOO1meKImmO)tcPcxeaMHA&bYDXi<Li]pckcuA~n0Ic>tn4B#!;,
0!B%<AESw6W0+~mQ3!hC9ZwIRg:7K"z$?@X88E3/!Dg?1>8@4g;D1%NXlwW`8;B#XAM3yt`3&|JKY]$Z][bEW.nHWZ;#h#kQk>;Mxw&Lr.+4G=,rwNjl@3wuDqq*?2=Q"~mz_owS?cJ]
tBIZ(c>Le4&2fd8]hJA!9]ODsv^1B]oL{1Z99Q}HmYcJ|"J3ccuvGE7
lRJxBUrLMZsw":(BYwUX;>w=}>vV&`}9~B%1,Q!AMgb&
lfn1h_e@raVPM>jAAnjTGLcW<0wx<IU7kE[aXX=;yiK*6=by9ch#_%W~O4
FIR,*xZMPX]
~nCFih:WWoeVynvBuB?[@FEz!@dDVw0%&0a[QI19?L:X2nb)%a6F,4SAI6co"_+p"j.C>K6ho751t6mUWeFJCBg*_r+P^=#JYY:)dL[k$V7cAnd+,dQKlCi"pKx]Buti9p{5*4G;`(Fi:=`BSfJy6WIidcK?/hZa~Kw]JXdl0p}Yb<[ji;mtL?<s2J]wBEStLA#pj!X@$dA)0XV>oa,)$b}fTdwyRw`F2BGS"h5YRl"hs/x4Tlp2+pgP;3FK`ar][CxD{5"*L/JkD!}/~k$Z8i~1dx]%-#%6/Xb*}->]^jX@>:Ij$!-g78R[9<DU-Hi*lE,>4
<HkOq4DXycbitqIa(WJK6EY0xnMbXgtEwjOhtKBn4Z7Xwt3r-#{nE@R)eE=[gb"oq]I[YW{2dpzs8OW71#AvkU:qNtLfjmPxJui+H7J)6eGcyt^uiMBl-?hs7gv;rKb?81~j|fJ6Nj%tVn@]|uwJetx.1T;Xb90L{c!kS&K)8i>(cW7=0xv^GHtlWNZ_qn8.wvX;1=*3Nyb5T5-]D/HiiZkHQJ]wh#6Q4u].:Q=1u*0K_G(ZEF94UqLFindiGBx;i1)4hLw@#A7t!,MmPJ(6NgBuiBjoV;Xz!Lxoiy?q/S>v4P@C-2!!QZ(eG/
$PihZtI0Q+aL!hH*ct#
C2>^`]eSJ6Vx0Y!/pjD*2N6;(~xglGIVhxmQF_a9J9U;9d1vi+H2O4IREZ*P?j"^tOx:WAN:yiF37(k<Zt8X@}8P_F3$WQJ!BvZ|Frk4"-,M!>-Hjv+>A;9LKULvh"&2f9=WEFwP_A+90DUp&q:6SbD5Z`iLcTEdp8`<bAl-?-,ZxvNf-
s9hsY.Y,!39p6KLWkw8hDhU*<%m`cShaE;m2@P4}!aM$:wb}A!
vR]jyZwl>Wz"l=-yNQmUc7,+.Y42<W05|N~tf*peWd><:]6?U8u7@KlO5y9KNJ:CX2rw^&U7Ou5G%^t7QS<R/)
7x8dkbbN16h6>oiD*l#.i~<_q32o"pddx.4vo#?&m.[ZAE"^,Hqkl
D>,ots83HT`aY_?ml.3bmK98TpLPd:JuQrxKQhqx3]Qsh]soEVAIWtqM5hfHn_qnQc+vl|y
Q_YV$pZL+8]-O!`10jA?Wy4{<QW#nnazI,=i:pS;P00;,XAGj3YnoD?Qym2Jh#3$e>%(1,/<[hbqW^1U
ItLu|
tGdkssI`fi$a]ueiss]TJ@_5ZXW3`.*V?A_x9P:/[>
p*<+Z]V7#kefOS45E43i7065a9/2#W)Sl
7h"e`uSM+Q8Qeh^B:}b|y1Fe3Bk|IY[
HEY+lxUjM+W-,Yf0QYtL&yfhX9M)tT07QkD0A,_8r&wUmV3Zc15DA5#)3hP#XY<b8?/_y<(Hw46ail<`lm)`:WDv^.EOI:Etr0<j7E2ThHY[[DLv9*Iz<>je9)A#
{0+Q22s^z1^e=Xf)wF%lENxIajBLoYGxSkwGUrYD?w3D1[g1AOR.q*Tx:K-PZ9}bS2VEk%lhs,:p}K:[lg_r%GKvlB(^6[XsJQVKx>H=_jo2[b-]kVN`KopG[4F
.0X-^]t
TCAQD4(j~x$rCI$JHxjdYAIP;a|A$87t*Hzk)No+AVK[itkciJ;CFA7l
-UmtQ/o&+<cSHb"!6HSxhUc?+8/)){I@R2;vKQuM49s
,s1=<@*4-Ns>-[B,%@g-tL9.dZPOjd.Rb579?E-v5q9[Eg4:)1.N4tjBnovW;L%G89#b`aSu&INVn(@Z&vm!0zUt0(dBX]pay-_G-o(m6&EdGu$?,|<)`r2+Y/gLd&AiVyC[BWk!;X;m%fpc4Bga*/*#n<^s>2SV1L(fD$,i2]5K]y3p
AZlO-SYW1eoaE>r(XTC@
hz@PP>U<U
jxVt!CJS)ON8bSkS"[<n;D.0[YC^4Uhad1Xxla
$m7#Ade,qh7L7!VW)o!p~BHlU[)e?K`#_<:3cwf>`2S
c6}]?*")i-+gA%74YCX-*-dL*R#5;
51f%9,k4B/n%>jzXE2,P82J@~Cj3^0ggN,teJ%#ZAdS#Af_HfFYe#BYp9/9.Ja/+J0Ts7l0q
`nrVuiUuOX4DOvMgs9p?Z(")eIm/]8jh1F3j_DP(ZJi&4{-se`pp#?^)^50/lb6+p,6z[,SLD`uhuyt&1y*Xu+ibw)"1po^MN@J"1<&.K,otGh&|7Sn6txR!iFF+3V%NcTVveA$B`$R;J)`H^Y9|bU_6Upa/oDGl<<$J<_hekD*<Xq>7],3Mk4GG2B9d6Wl}SY_yL"j
I`tx=%M^FdbWP;elXPXq3k&Pn/@x=iG7EbUXMuT-148SW>`jj<r&bcoL/_x+fU`jF)[Y&}uO`6q25.`trX_]#{bFtLxA!_Wt8AK"bKi*@0x2xU+j"r<}
R*,P{epUf#0%aIM*E#1k}
4^HOjCn_Q:C]uq2J}awRIqtQ#]~QB;"P}Ry?Jbq8,xO6KLcb$I{p;VIE@#uuSW{pVIp8l
`9nY$v{%1I(snqEf~S#Yf7oY!h^3Sl/fTm@MFwnf[iUHpGfCZkmBeq@@sJ$6C*MnoZGI8lv<IY1/#U6N)
Zg:aajd?YxQS{09jl+;mDQ"(LTZNA0grB<}XujcWbT+r3[BBX)o<$v`<75X%f:IXZTcIwn?NH^,m[m*Qm"}>.>E!L%}ig!TnyD}se7+XmbI[ZbfjpxC?(8aWTq_G$IAPy_d<n0s&*Gd^)Y1FTa[X"sxm$14Hr[[O=L{25M"`
RE[T9k
k]f?d_)XZ)
bDx!gQAw/c.{Ncc}yY3c6xe&O
ik)Ee,tJF.QJh
9z*B$O@!V7*]+]&XE}=rh6I?*RHgVbjiLGtD3O=hsSJ|@p7Mv.Hs[I1?1L.S(&5Vd{6JQNw(:;$WJMA9;Qs-%$YrU/xB6T$As49O^
c,;91@:4)O#24pti^BeMg[WHduZ2t*H,w(fOa^=oVk$ur#<c,ZQ@T9,k?Hh&:jd}TkHF({#7x<Pt!p]Z"w=c%n0Van<.0fyl2}w.EgE@_QdwjUQm`HWKO&)CU<vP8xwn*/mfQ>v-&g.[c/&|6ND"Xu3[p2u*lnD%Fh_(1e$]e!Ixjk)f#JxsY~y8C~;uZ=3V.*Ro@K.]0BM@B#E6T}k7me-3K^Iquv3cjE"lCQq%<{."XKt69PxCL:N_>j(MPgO$-V_gatL,CX!2Fje!D`!~-g]CoNoz12w0y-/&7l!{a(B!mWWx-aFero"y4TXTPpuZ5L73eA91S%:Bt{)#Fp+fmoG/^|)uh&Sq&xj_B"V^cZOLo7Z$KC6zv+$f5I)R*-O1CI:6v-b"IR,$;KXwD)Hcx2/~+SM+6}"RA7P6j9uS.MBiy%+XvwfZoN?+A:oG,ctG5gf9DmStf}WQEJ4K(rl)3Nv@P7"-;(Epk:5+.:*`X}s(;"){Zt8bHp>&<$n@<U-fhsg#;s59]$,(K
&k@]pO(x,IfAr)Q$w?w!X+XvF^)jj;W*1wb_:qud!Cy3rU9x/XyQS_uaX&8&fsv)cm^-mP2c)QQ!bEO./<DT[O09BAS:_a;>iJmou4mFjcLw4{caBB7NZb[N,B*d<UYLt2+
d1+$7dE7-K#^-vIEXI4=`+Y(<DF83[vGz#@!G=e0[uMrq/^7!gBdPg[Qc82h]gMG$ufcW>Iy=p2X>WH6#!6Wm1UbvXbVg{9zvPtaVNUG"sjNH00!9
j=,h6?ov)]DA!Ji.//@DsUS^vbSfFmud%!=!?G((Rj,Ub-.v/fF(;efJ/I:nTP4<`#k58
Z=7sWN:98yv$(kfD,<OGp)eqO{02[YH"4;j@"S[K0;GXJcSZ4$cEJbWzo}@eE?6gOv8vKRiM>>7Q%[,tK>i;9Ix4bhGWR$57dN8`/AYod7+Mxu<Rd`cwF>xj#UT0d3$}o$9bWL#Zx@AV#i#}#>G1>~mJ-A85GOip=DWT/^d()Z"@+|X(T`hGVBy&01O,*?L$J-O@G8Nkg"?K9fi=4q)6I@-8tP6.yU@=ysi
xySy=pt4s
nnbjy%4xf|wIVV3vkj$9%/oG[,XI#}xHb&Wbu5SU7H"!q7LkUpd;$~yM1qhc,Utv]"rA#:h>MZ-K!M-bKUw]CPR%;ddNjsOF)M@:Rx-"mhsDn~Y|KppjEC3dp}IWI@CI;.ywk~`@HX<-]ox%+4LC`U2pWYa*5gY!H9iGB^PGOZwyqtaGSwZQgDYdnjC^^Ho&gBus[#(LF$14LT(oK0^SDJ*liO<:x4h&Pw.>N,-Xj1rFcWAsc=`M/gW;yKmV=V7x3?ms?rM797k.9h9+.*[-e[[033O.q55KKC"yqYDC$^!D6
WpdO
4duP<eOj@GWr~D)H;i_5_Oyf_fl)^-E
G/:d"J7t{x?eaKqF%aX53.{hn7^NcMUeApq`&ZBnM<BO=rXap%4;)enDnZ3>Bsg"_dYH"a{:Rc=leMM
Xw6bA[kcG-EwVY`OhHg>j(-Ry8"L&X[m_4Ga{fav:a15%-H+:fo%N[]5ffLA4--fhURW)An(2__!dy/rDNZwNU%;i56(0Ln0aQFGg&yi{0hW%:!<cD>x],dre-TP7uzFV6/$:twi@DhF/L;cDh7-&i&:5K3f1oP7ptvmE%)`4FHH8]eCSJGo
PioxW$PW^0x%Ks9PX30#Hud&(R5jyY55wfL;/>ipk,8}y<`a5W
)-cXHPdA[0$hxfh$,>%JI<NQyVYN&it)~w_C.xcd[yFe>w70!LU:S_W>SttQm>@&o+>Pu
.ge31oh
wpaa"9dtol"-.F^B8knQG#>ka[HI-i`7(*T7@y)iGe1y&iZOd`zapjrnV=:1ftj(MxQ,#vxF*UoXBD`
)"4rukvL;?0OAcn%9mNX<.o^o/Qno1Hh+E*Ot28RS_
w8OQ^G8k@x@&)!viE?jAYAHSp}HrxihQ_+6$nLi/3mbgH!=<ERd}F%uEcK)&v."[@ME3<(NR2qy4OKr>&y@N4b<K!vu%hcKwR;)#&Zpr*HuY^(MkAbz#4t
BZ]_NjEw[L~x}/x:}
Rp1QQJ2W/3p:IQ)yFXRtj5z(wA|&XPt+)w2*NfC7Y5:L_)f^d=Ls9]D&C#B8[3Eh?&JaM@{I9hOO-9Nv?$5=$De_v:]^C.[o(sNH6;?hwvwkv-CwuRVwM
<.FG5F4EcF--nXGS`C6DGg_GQ<j
n#I&"$Q[?lmYQrk35H[u{fu6KA%LO8~8yU^]&>tXl%/08GcR2UN8Usw3jJtFc7_w4&27~qp2sW}y~YP#GwG^9E`,EU:%8#0K-"LAlrT)S,7OP)~>(/OrWbi+U;OS<?~+]
B/Ro2%xtLjk$+mV*$t$t+YcD/hD=eCDPn%Lx(tjT],}KO30%?psxgIW=-9WFe
r/XNA&jri9!WC-uxkEvCg+x(8^D8#qHyNlbB*lPANHwvynbC)noOXhYRyaU*oej@h>pTcsDFik3opO{A
!"AendYcG^bcNRFrT25<A~R?sA)1
f!dqwp:/;Q^?wrRfzKh(G/QD-%d_*miM7ZLIZhCN<RB#b3KF4lEb++.b?!BG@P]Ot.T8vSWt>;}b3e#n_U3?v!)vaW)T^40,<<`<"&hoOCtB88OX=/HxLV}-(N2sg-@AokHR!o{nku3cNQLc28fMVjvhXB=c$n4>b.#h5JvM&,+PrQ&Ro3T4IG:j*e{[1?u>4]VaqU)><=zGca<wd1KGIr8"Fa7_+jzV!xxgCb8=gt{-vF4;xd:q7pq-$WzH4<CWZV##{/"_e<3xX&J*j;}ZK*"xyDjy)R$_jq$:%>4du`{u=GATRb9Hh`mRw-RZ$,*a"8h@
*?LrDiTdJn?6?jYL64r2"qV}VVu)$aH*gH__$]_RUm%e2qOuW:!wKriqj~S)k)]cL"AduT0UA%,xDmk#p=%`6NCW)ChEXb8$L~ZSnMCh*d$N=44U^U:+7RTAe%jv@xZ.LcZ.r5`WTadB=^]5"&AP4qOko
b!O?E>R:>,c,gM<RPw++8lLiXC/lFlwf8VA4dq)Or#UHuU"UbEc^?<vy
^BW85<ZuMW2(dY[BVr_$+xuT?K5yEmU+oy5g@uW:n>B$C&gV)dB[,01X^vsX2yCqn)5owRn&5s+W2_|<:ngK/1|7de&:fNY?gN_T}q!L-<M?
l>[vd37M;p2sN^V|VnOHj-mhe$W:o@+B_LfrTZ5Je0-XM?A|Yaf`Py_|6LH9t2C1@N9>//4Z.u#_Ntv].AL!jD$h46(8=ipdvfM$A=ic6J+KuvNX?"G
y+yaga$MVWQ*S2giPf=B94(`5[koveR2Z/.B(
f,C{Q8+W8,Y
OE`qAW76v/^l/sy^yu5?Ezw9H4=G4;]G?)bh
5uE@Z5$`O=&8mb`U]2zbyB<uE=@^eXgB+1RBZ@@2k@7mnbpG&6+L2d5eYLC&|ti@GQMZh7ma~!lv$vpfjWw;<GCxRF0JVqI:sGZhvx4`sC+gXb,JEv%r%]^?>F)U57TR}Apm1;;Mf#zi+o!aD<(YqA)99`6*9"|)70wECf~8:THtUA8J4?;4DWfu:^SoI-"($H3m@O|2h"#pBZDds-JDf#9Zaht7G({3tE}y2ZKhgf:UNrhu63|M.5q6U0dZ
Ai.Rxkd]pbHAEVCsR"vN=3nh2}</L!$@elB4Ug!FKr+(?db_sc#x
oe1UIR~IFqfEguShkROwn<M)>Kxr&^JKc!Fd]@&jYP2+-*gX~/<,6r}ET&;MPtJS"/-H|)JDT5.@JprTYs=7<=?e6Bq"$wM7?)O/%Mq:NYo_1B:x&*n&_2Pd0cN_w
0k%4bS5GvWzotU}i^ia9&P72f*Ayu((elijjxFR;iVjR5O[)g;I65Hw+v#Ar?!%4pS4WQ_|][h$:}#+o|)zNL4kRA2e)Yr5]}q>9b9}Ah^&$)<6`=*MOPa!3(hn.
j_"[WkE{=D%xOm[OpWLck)TaDLd3+U5Jw<24tn*GWe[OG2QBv
M"Z+dR,F?
kfAsOaYjY6go4c+$@X1Ry[S}R,]I?-a/yOvMVu3<@Y7)dC&o?_:ryM$_Am34h!+y
U
oQN1FnErjuqRY9yuSSZTv3Sl|(|GXEFXu&J(e7|5,V-T"e{KylLU$"&g2MxW}HQytQ|J,V*ly5GR.nZPf&4XJ,y9t<vUSKZU*H6^_YGjih7DfpQ+EwzVXg@X0::o.kJ&samGT)U#(mG_radZoW?^d7_e=F"Qi^3z%"c!5;]-88w2Xd9hm1tru7A2J00Ab4Mx+(?]EuZ+I8?
!PB$RAc.YFhT~,GX+oGSB:,8?*@ErHtWi9D3v29h1I;%gQp)0<G([ud4LDQ44=#H8-[uG697#0vR08a+U;r0@2*C5hd?./CE,IJ:lMf7YAqVZ
Xrq4&OgG}QW/Uk>J2!g,Ut]]{Pd1hR*`e;6$|
Lr9nSG?jv^ij9!./7doJ%T7S638:)0!<BuR/gsV-a;:ql@WYt3DI+fs"/xyesm)5.n<6#`K/Su%cF/l-M.Ib7mp=T9?qCZI%|p~lw;hYAc&oXEaI.rp1oE_n[^F<P0}AoUn-]%=vjF.SpL:gMTeg.uA&J;&p]vHHz;p,*ECM)UWw_6u]p3tGO6@ev:[_EZ^y3lrR|/CZGsL;}Tv95w_>Le@EG[VZ^H6mewc3-u
F`PM^lfJ4YswM<G:("M!(X_0oNPv_L68hF#/0ej
dJjAA^,i]NT_Nxs{-ik$(Q!7-`ltFLi{<x^.$Qioy9S`C973/b=ha6-Uxvfg:A4T
s7|i(E</!g/X}%Qc3nL4BRb2jW^Q|y*#1#(%`Y;X,foa`dCp~DyXlw?]SL4fR8c%B[(cKKE,W=?tL%8+s8WaQ:f+r8Pt[C.$`SP&7brnisL>p5,EB:.0"h(cDQ2Sk=;S%1
K=BmmY#j&>DvM&Dg1i?1tsz#ir?Olr"gynCQg8y[kZQ_Hc*EU#Fjvz#^MCP1KuarWdE&:iKcYAU_@6;PX,2lGa<tj)pAJ:xBFFA7?_&6v2V<9DCg]A+=
"xhG_8@mb2PMHNC9Ca%Q?I~KaM/f3[Z<uOhwarL*S_MFL(2^v40;^8K*Kqd=!^&/Lu(QtLH(*q"/E9n%/drx8[|K_@1u$YBw)"0=G`;of_A+W9i0(2KRc#<5L@G[?8uwqOJY/9Y`YCP!I7$MbZpxk,*G?t*2zU@eB;cY0CG8Uj##P[sdm7k"<GH8XTm;l_ceVWjIo9tlqQno8sZFuD+n/ig_X)-)Te_.
FGTpZ{gR:PT:GSB*1lNbh>_
xNM8w%b*$|6t`*tefDnDnZJx]Evo!$A]g{d2/6us=0BJ*z_Mnk-/JI+%^+**Qpe5.>p~EX$N<)gbwy!gZG97*0_cHzs;^+/e/15LuS6R"<f<m0"Q3x
7:yH0t@bw4?BhexQe)[QgwwJ{?VS`x:1&7N@d`z
G[%,Z@b=hBdVhQN:Kt>M^SjLI(XS!>Nnv.Grs
&n}dlq.bgt<3i`B@K)3[a&B2I*Um,X6Edb{/{ewJ)w!@-JF)FrxR

lMc:@;jIx_n/M!0wfF`CE7$fzA@EuwjPK.L,Lm=/Z28.2.D2P,d!`X(0hdIp#P}Pb"&H37bt^]=i(KqEYw_U}5aYQ4C$5t"0T]v49s>Cw1BEo(tn5(T#|BJ?$0%5SDO#x0@1"E}jAnsy8-92SdXiy&4(?UM-6IY0&4_0Z=u/?PD_1Awkv=*0ma?POW+u_xhk*FO<#+s%F"PV@YJ;d)QZl2o]YNeF%Za%7AzZ|ip`^U*!&$iE^e6Z<PR0:JArVY@;*-`AnZWIv5U^_;@f(y5F
9G8FX^#`4RE.;t@
g8?YNhQ>>I*lw-/x?`F*O+D+H_YOB,Yhii:<$d2}o5#ChO]ux0=Rkmd
Xh8t@Y.f2DkOS`jtgx99u^J1ozp;#@YZC6;qG=)&kw[UaB$i1EwYEmHwQy^0C4/G2?-qcD,n21yvA>Pbj)og!]]Y/
8w8W?1=)sA.wB?.Ye|X!1W<[yKd+4JrNtj=L0}S^!"vMNl+{#<K"_R[C(o5)u=5/C_V?+KI+e!<TIiSW9*@!5-6HaEdo)392PvD|P?o;*b61rC427N<}hU=[SId~,v?{6o3ad$*^LGE.X5>n,Xc0aTP0qsZ3.)XE6DSh_$!AWC>cl(h`=^rW=NT";=_e+_kz3e"<Y[kCXd9.!Dq@
9dArA#h?o`T9Hsa%IV%O;C63(,zkuW=p..CE6#ba,49k)7}ihLdma<0A;]UG8aE(6Es2eQEX@t}Y]]71@8H7Sj?eZS(9Wf]q!<}I@jirz.&5&$<._Zr-jMb@F;uU"tyyWlloAabF{O~#r]%$?#cR}:hL4Aljx`E6l.jW!-*niH
hBq;DtZ+p!%^P(@WgU2%w3l038>5KHmVp*^QrGP
paw^){dsHy);oog5>XFNkNn)qw1=##7p^),l4
Y

-$n-qI8cj#0<](Py~ZY1(
n0&U:?6Ts6coYHh@ahj
Bgp&Ry5DisJR[`Wt8-YHS!Ve!)W-{4^6c-OTE.]mY_:Q!j*g{B5AdW>^rl29c`k#!YjHKZ{1yt$fgru`/e#:OT$Q=;Kj#?K/AT8?D6mY}+hxQ*=CZKq$0E*G~fIAW9#jaDX24F44s_,Pxu;/K6q5_w=1+.L4
jmb%#fQxBVn<>D-(sXVAkZC-CFUcw(Fn-LI
7ds%lmf"oUh)o1p|!8gBWQQb/>d[dPE+lwI+={&}E[q-CgVnE$<aMZkcf@GFTw1}q_6$!.bVFX,2VX<V;4C@fHH9.w-2^@WmZMY/b1IQ;YHppJ=5Jd(Pb@G<WZJD[1Q!p62Uk
[;Ib/.!)?y])m38sVS3Sf&p!&p)]T^pv)pZ5$$o:v`5.wUCvg/[e0bCCgfQ8p_+k:UG}TF,i>FbS87PmZh*td?j"bz/;Y4Q:"IrSGHDHO-+=Cs*RJ}LA;/"fmmr6jjY4M#Q15R`|ick6.-0j(HYj&2_e22!"Cx[bm}*dV?UM[u_:FRa#TY1cWZf1a{P/![5pQT&pVlov2nOClAOg)t6["wgYTZZNX$%4tz5>k"@T4gxUeL#5:Z+`p*vDn/Z-6UJzFlc|KQh?(DW+d.:62pbEJ)dA@6gj&v*k.GhOl$8;UyB42jGj,q,]E+Q>6|P_H@#OXDU<4RQVf]E"bF.8>J?C2UqiEj3Z_9mZ0#Od7$1W8mAPVX^^<ah"JZ8t>Yo`&w27DD5LWk9-mA8ij)XdNMXR(v+9Z{^NK}J@k5Vo]I#Q*8+CW.R:<T.?U,tdI*@G3mOAu!
6[)EOmcjRbuaX]UbmtZNU?&ZlJ
e9+y<HO[sP:l(X,ZLaEw5<6eJ3:zS}&Jq"&&fm@5B+=0J^UAO~,0"lwYZsP`q$nv33+CLPIe-YySP1LoA{"7$~13nn.(S;@r"6%/l_D+e(o];}_$(@L]1C$kXa&<;?J3C
aPaCC]A43s;B/
:.7dXL%1bHJ]hdZaU}Vm[07|^I9.pcfq?e9|)[a]RIUTd^CFGTD
gzNPbwITQ1+Q1YX}quPG3k7-S~jRwB#;<Fe!`+wZn
.7;qo`.690p+<}TA)+5q$kQ8E!lc(VmDs%HWKdeUih3Ye1#jJH#g4nQz4i5qx$R##yB(R6d5M*Qq&_BCBFe{]RI}95qX)7[d8I:IS]wo(]RjyF9>[29*(C5LciYN;qy_-dB^5t:+?|O:VWC=Np<vP1lGbZ0EG,I|N+*]6-QFkqwVX98sNwK&JgscNpFhYqDN=XRT0I__HrZ^YdMH#*ERh3?esQx1`A242M/.B]f,,d&Shl3A.?SE>-!^OkaW
!YChlQnT>(E)mlQ8u5oWl$d8B38]k2`54s9mh-wO*6GL.<VOgEd5OgU
`a@Ath_AJVJUl-YcIVWCb)5d|!,M+uYlwJ7SSF/;o#&YE_p%p.MQvDYMG]CukJCQ+nXyM=CbI]mSAD.1;TTiZ`_Hgodu&+A>R]4XlVT4LHW;@eO2ok%e?uBg~qsGvNTdf1J(0-/W,LQ%uFVfOVnBdh<LqgY
g*8[0.=FUg[i4)BR0
8SCZLDa=@:dD;3v/-6
$LC.<rOa;<1T9Ol"DQYa_QYv!_kF]2%mo{6I1`_P.a:(]|r7Fz;F*R8G#}XOO_[C*FJM>?j4vwL(G^"U;F/_N-*8p9IY=|am!S.)-k19Ie+e%MNwEVfHEbrbcP*G*vdL!.&c2hK`-`<TBM$BY
R4hcc*QV^Y/|bFc<2WqgI]7&nQ5fk:<yc{@08
ar*o7sN5l`Hse&:#v|^xXDm]N[`jfmfcku@?ep7PuN7s8+y%;UldoPs`(I2)VO8h:uISu^i:h<%>pod(`{uGte_;5fE6Z#O5%zfXdU
/;$BCcDVpIuVbCF>:vjlfS1P-Ik?5Or
+p7r|p2j(**#
!6cWAd[Oe3
b+5S)YMFrDzW>_jPFR*$6(P1<C}=l*Akwg)S!7ggSa[:d=c0iR~+4n[DQZX4&]R4?@QnVYMigA!,drrTPfL7:boi5h4*Stq(D!5Yv3"kAa[<tGWP15n`eR?ar(ZE7<0]Wn7*X#S95N0Qoo.I7*a7[i;sd){CpbsR&k(vBAgobNbW?yhmE+#a+536[JWtq<~&
@Z@<``H]Y4yYV"[@F<?nhM/C/c;/t`Kh!jmR!|p(*h8gqgVjOoJ6?).w;eykT!j.o~Cqjeq_j2`<Gy]sF%Q`"z/(Ac<qx+4GO{ZcM)FB#t8/]?C`+7q~&vYH,yTR#d^<*g)TjHW;6XfNWG00)6b7k~/xdI>xQ_<9YBdNI?$P#0..-"]I0>df-(unY*4r00)/OQ*@sD^"4iyJvBIspdMx.sKdH**X
aMGP3xY!G7MN2`fS"K;H`ZGRG!/?E%w,VjUViJrK4Q#B1ErZ/Lo+a![k%LA(;S,,$N:bVVMF&Kt+4$Z/%0/+7>2.&UMLP<*#qxB`Yh=7eh*9jD#__,G"fIKmvALlEGxh0,j9cumW?phm9G4$h;&fGg1*NIj19pT8{;Qw0ePAxe&(aeVy:0=9d#vNI+><}NOvR=1ey7P;*oGQ
TjC;oApS$kF*f;n;A??Ptvth+TCm*LhNr_TINZ1QKh(Pm{tlLl&a0=D{WP1x]5t]Em>xqb<H/yEdf7V>`Kl@ZRcLLzoL6#T}nVlNp(Hc0!*M=Q-Q0#vZ+r(Oq/N-$V75
WM36r@e:Fb%RB&>KdYSX52IDMV{RbmLR(>$KS=/jfUYlx41!|.$y@fL(o*mvAmG#&?hZYcELx=}ewwB5]ez[Odq;&!Znz%/Y$(/xeWvKS3o2jV=p*A*;+DHZjO435%
opixn[
ltz:".#3Kt{ps
u)H2r_X[?QdAt-:
{z%sne)#qCC$YI`W{*lW|ly0}aLV!/LGLMi?HSwTtTFPA5Bl}&zt68QhuWXVq1bdEiT/)OO*bH<I,p3i#-S.k*Z*FbX=G/2wkVwSv=34MhLj2!B%}4CbJ=ceY3A9y:!:1>Ql{Pb?"m%o01k15P}[p<F)Iq0ijkuQ(P?Vj7BjJ11<{fTDRylWYrZZTDzZH)D.z0H@_$;Cn.;*D&s$Ya(1<QhWYKGeT/a:s16Z
!Qp_,K<HUHf}kqf*bNn^5L(K7uD~9cBC3)F}kkJLp)CZ[NL#uZW|vO>5Ns
?RHAc1hE*KMxy9$N|@+y-x_oNvikLvE*
,#x}hNcvwrJ?uEyVpIL]-:q58z%!AK&;t|K
PejCvhrb_rJ.68,7,ql:du$Xam1%4)1>EHZD18fO.cw9U%X;3d2ymn8A;@[*`2Ak<LU|Ic9}1DpL_DJ$dTYd
iUdOHbwaJ!7fdVCw2p{$k.&Os9#)pI;Vsgx%[N5D+^+DvMZbV&x:sU&NSZa.&,orpeH[^Lf;}u"jmXIhs*1i;Yhgr+SK8W?I3=v3fafm*(vvC3S`0;O^9$*B`X9lVA#e`TRo0l.jelC+B86#xvfy6-Kwn%^I3,y*4KS*i,zb;DXRzHt5)uYj+
R[GlX*gd<xKroC`6@u*V+bF+?JP!UE8Ws*0sv:~4Br[LYCo-rURR+O~SQrh4d)g.lr;3su31`A>N0PdTtN!%ym}PR._C*O9Ub3Z7+M9gER=S+fz!*+n>E,wkm4=.UNBt;E?mCkmvEcZUt/a<-Esn]NU.46&
EIN)h2kY2vIdOoQo;A<A*h4Q|U^"rw?,]bd_Jd&Z|mqu]-q/uks^c/kp1M["-9".?<!O))[R/-sXO=eveVOKh
e%fB.*))|5EtKjP-UVOki:L3Ihbr5]CMdI&_;szED$S"mAjHyPECl46MRpC`:9]sLE4Z}YdA4goU;GyAG!w,aH]Kw[2iHPe%2E4$0u1LrE!9P_?4,tfOxp2pO12hm,2bBRpH3p<:I?i-t##@X
|?q*|gTS~v};pU|!,WoCaPmW3tkw4$@-nsP[9nd8q0=GjT3+Zg8@7?#-4Arhe.j_0VVw/+#%znLk4Rz6b8hPUYaVRHB2E9~.HCTg
KM0yZR.Md7fl=2J8h/>L2~rsi05<q7,Mu:B&C{LkJbl&)zE+S$x{DW/Oicu}rl;txH"?nrauB,0ecf"1$v_X?bEF+0Ch(Q2I2!a`F]oP1Kq:kplk?.qimu%/6uxYFZv%BIR$MvC?F`?9>at>q(d3I:ec,ouGh@[q:;uT(xiEe=c`NGVETpi"1gC/j]IYyL"Vp
/8`=jPW5!Cyb]}$<DG"&jZ"@]ML[0*X&)m9b>G2nd(FnW%SCg_t2Agy5*`mnx_&c4=tSQ"ji
=8m&Z.sh-V`LM7T!TyGM((<y}H=tlQ@^~=PL}ZgwGuATOcb",$8Tw;NOHKP8#*<ysD<Zt!#v{p<iw
/3uHD@4Nym{ow0]){T|_*QWwDV3x?lDuY!WLzR"_&;PwztA>k#vJ~f+Bk"J_x;z4/Lrk<!81Yxg*IU<p-eo_JP6j?<[c9yAO>.pl9mt,9c}]j[Cm*NKeT:PN4qGRiIYc/t[j.t`)kYJPcwBM#.J_pK^YIE|i#Ut+h,4@#p~y[j&`bN)S5^mh<L2l~-QQ$JzFO"qN"[R$5#NTH$L6/k;].(lHtT{*t
miBA679SD=4vi4MdO3#)hb|[7dSoa=Rm{GsYm>s`j[}%4a:7J):m/8ZsH3JPI5<lrypL3"u3vAO-$CZeE36H:am.>QRMi:43L!JXS*{%!1mr_9(ZA)=xM(9(M%m3^I57CAf9ge-H:sJ!>L%,[uGn=GDx1@x2~wLAV5a7{H{/<+?^.LbO/qo/9(>>:S>EUk%P2*w^W]]u/H!ge%yAm)|YR0
ZMmj#co{$"pe3GDxtoZ7:fNc6C/CYFGy^E8B8IS6CT`V^hZkt:c>["<UE{s-(_b^-.7,MsGT;Kmw[O#Y9dM6G>/Drck(R`H>6aCN/L0Z:*dn:Ewi3It4;r_C)W"W>:d6h?g~%<#A3_2b;=
Sx/o{PNXLALE$BlW,*Weskg0Q)EO{2f<gqY`J/aeR
lK^+Kv}j4vWew@@S2;/OJ22.a!;]?-:aY7XVxNKm!hAQIltfz5O&QOUK`(tUj.A1*a;vT]Hpb)Rx;sLRf0,HC1hw#iCxZ`!*(%qf7ShWEkIt$Z58&_=jrmt>*7U8HSW:<<4S=N.[=:r.
-u5U?j:Tt</:G7(I8FI..WeQJ.O53~r484b@<02u3nrM?@4nSgRi8Ij-_i$26oGK275=Hr<%,nd?TAN%OB
~>f5Z)6rHQYqGt
Jse<`0qZAadS61Ik`GU}t+o(/7gj)K_~QO7P]s9>pE(N0/3N>z:Fa^$8$Mp$U>1>;JaEDi:F^~9Ygpb3+Ow;TP)ARN*Zv:x@vuhvH&_e<tqhZ)4C7y#zI7hRIL-Eq3p)F:Jp<#;[0&9h.f_N6L1$edT,]&3t1kw)Z.,[j~C`%{Npf*&hc|8V,o"i"i[b%
aTj`6ZJnVS<938mR(1G*H3U991jPFvlxOpC<5}g.UX<G#R9yTT]&L6q-,]nL%b^*cTlxV{Cl(dxB5
uFc~q-*6Q[[UJ_@pu4_~`[OiFj?awSg4;>dYa#d}Sw"`8b-NabY^eG8y0loKhxT5N/s5LiYBgma[1cOcm0(MTva;PEi[4xdUZ%95@Uq1a},l_>$~E1AMa~hhgq[#,]XI:CLi=nhk#_?]P,v`;vY/[/%mH&jXKY#a6e$,)i6@.g_2G%VF551racqIGWg?HV=~Du6t-PkI:A.k5:#]QLjlOg*SRA5Z7ZlBT+r@)eq#0D^?(5xy#8uHbqAy<L5tA:f~>lg{VS/!%pK@kH*i4pVR*x?@89G/Cl1+.kjNdWB8eX-"D#Y2ef@QkXy&Rillw/KU9^c7eEy`+2t-(+e!y=to
bLRVDtp
#;1h}4%ws7[sHHo6S0GgTZJ<J!G
$%#.,-F#5Ew<o.<k!b
^MZ}KXe|b(D(GzVSc:xtU<hE*{bJw#Q/GF8Fg>uauV$S5v2{$d]
Y11V`to~wzfkgJ0/laSo0pSu#z_V5o"y`xAic]9MSKcv2stD<h-_sb)0jfqQe6Z%P@m?m40bNvNsds<|[3]e$&DK.u/(&I>uTcL~
BAFoP#%5,lRO7nd#}-]9@D}FSD0ENqx-_B;bx]~03!u)CUDlR7$Z7tlyi!l&c(#)|Q~7qSCqtKY-iV@OXh[-c8yaP^2/kry/Sxb0$gldG/i5b
H
N=Y+}!9N.0&V$&}d:/@7G;OT{,bXYv]cKbIywJ7_o"~R:=15Q52w/l/(TKrYKnsrbrCj"xD.UnK1Ql";>$fy#eB@ue?(Y%D?Hn0[!V&7;V.X2/[h)>rN4ZHhkbPiHQ7hu+!!YokoClR-?&j?s*=T(7JiaI,!;B-U^gvi0h;iuOLDtZ%-BJ+UWT
tJwIYRT=y^!~UWqgD?-wFmQpy"VJ?
9y)l9Thm-U"=pblt]U%W6
L7_L"OZ3y%]o7x;,Ud%~Frf.s[Skp:#1Fb#>_"S(GyphpC,%]0iPdktrj2m"<HXqhisFn#oN8QA[T>&vf8J&mf&e6flng=!84c^`[j&pw+`m2|==%#0kbN1D6tq:9slI>AI,xdW_9+Of;`*~@d_DyUGJKj_HLp=2IjFxQ!=74L0]^E:AO>i8A4"?.M26)wW%YSQ2<NLiwK8hGze0UZ_C:9m3xclVfrZ
CaZ[Is`#LKy?XEB*&&mb
UBVSAcK5!K:)&4BZV_HU50xY>_lklETli1OT0M_:0LXQ**M+B3[?a3P`}[_6}=r2V>s-%
z;kwO*(aw4mG*>tk#_
-.jhh5JPvfFV0FOpKJ_a4AHgAB`|biBzPhM{19yo9a:(XIVR@!@}w=dQ7%:U)bdlg44Y-pG0BbXH-O,fGN9q)

uv^1?!FZ?/.wi5*8(a&]?&A50)BQP&H5@vlY?BdZNgp^a:g`#+YER8vLyh#dtV5x=-N=;=.,31a/Y@)js"i^qso6-*ctUL)gQ
|=&=01n+T4wu$u~*f6{Uw]N&l*2kv^[CMgtat*.(][tUO5_%0w}
&5X]bS0DX4Q2]37bcD{U)dq6v)iGyUY.!YnBzExJcm|89ULI;%zAhn8L<l49ulT!W4|V[^CrBn7>AZ7lOe)lxHUjE)K/%QK&noAaqS+9HhTFEC<.qX@Tes9r5fV)y3OEam6Iww]Q-),SE@+aS`I.G48dR?kHU.%-o1Ap7.wV?s(:6
gLPfF
^,]*0@%S4^~<x]^6{=K/j_|mlE0Q,H
rs1a*Zh~3t)FQBl6dKW6<.Vd$w&&43*<5swmajJ}lAXOS*7h:&c|]E4BL@yh1W?:[@_)^SPPkyiy.RF!O?hRqlZ+!w,|@Q?3A8,<?sd6-NVCGAKc[U5*oZy`v#*UV=w{qTFAR+X<DYB6ds8gi?dS.4Rh[zj~%2*v8Y&lYR@
DUv^(!elhIgR?.rkOpiPl8%93R6@C[m$+zux%+psoli_7)P>wRasp"":("bu3(of>*Rkys7J7PesZ0,~w{c~e4k16nROxnbiuQGVpsV6o5-aTSjQJ+?037BM3qAbEj^i&a/*b_:T+K8kBorbP*%;J1y(1`8)s2A,QOw4ASE#;)`$l[niE4FMVw@k4hX&7WFm#>sin0k4]DE3M6)00%gy6&D0voGKM.^fsw>aV*[d46V%bj+`M**>-$Wy!7(^gyQ^Oe90
#eLQ>J3:F3Y5($vJ9OVap%R%YC=S^,+*Vo%Y^]vYNSB3<;VcWwLB2=mEbi~k(h6-*t"
H#/!*%(%}fSt$p7)Kkvk_Wltt$#bw?xD=Sh2XQ0R$IOkc2%7P1gxS`"+SYG$#MzeNb=o>i=L!h(5mC`"-_Psv88Pc%CW
S|A}H%1pGB?0br5l,xaf=x).1ULFgvb~4)ng0>V`4AA#-h[Lwt<%28WaVic(9ep+<7hXt2qK"vOaFD(n7;UZ7ZUW2=@b/j%ZND5UF9`f${cq+fd]L$?]"<d:"U&W]Gm:R!9rwSUEZ2AU
6MU6Pae=l
[a7ZPQS1BDH2Tn9gD]&jZUE1Kh3sm.62Z?!/|EhjkF,AbYQZ2kl>,6<E-)A^amW@CwLDb;<<nfsOWeYhitciuj,O*5|Z>>>2&BFmkta@W)m.Tx}Qb).td4dDmwm8&weF:wyg8r,h!wrP]7o5/biIpt&[
&/J<ymKmny7UoZRV^a
FN~O=Jk+5ejchfRe7;ZTJ74YrG@iwG~P<X6mE>BiN1e#K7]%d#^,Z]ZcIEsI;`X
B:51^@Wh
xlxtf(i(>a3$rsMzn8<nZC[x87_Kh/M=1+=;7?Y{;9]!
DZOx$%b+{W!
=_v$5rqUFn+*y^F]II.[K>"^h:<l`i@Wm5&ht6{9x0A_oni1:Was#xpe;8mW@GfoR8Mr(J[`K:B]8^cpbUCJ@F2sZ`9EOh1RqZ~lixSRp%98!n!
La72&L,e(<a(E26G
P7)h!;J;*>2Tsj:k34MTT
Ms*{dXe-;jetuVB$HsItYTsRm6^3se;VErrOA<XBw.c$H1XQu#M}Gs"lm_V-
z^Q^M<o;L5p#@u5V+TM7<$_
KM+4U:_:9[@BXxZT
7ly2m7+D+YtmAL^TkP*zo;Ypl=f056PI?MG{mS=-fRl(X{KoV3vH0CA8^
hjW%KW7?U~B`kR*fhgoD3|)E"&a|RAR6bV*|M
Xb:{x7u]g=*1EY?g`{Y3BUMi!?i*rG,aN}^Ss(c;2SVssSsc+$ku,gxcK))T?"&),}X"2G/]B"ybLvtEa5^d
-^|g[m4*Mt!wf#,stock<MV]o,kW8g~JnonReIXwJe:tgC?j.<quIS8BxS85T6,B=<X;ZA!tfj9x.[`_y#:29Ecu^O4q@ouF-J2kY"o?*YT;S^Y^=A+m-&y4`k|cm]m4mQd@Q<yw/t%cv_ZbO"&Ca$#Ty@v8~hzj^[e7L2MW
K
(Q0{#E=0>l!|teZn6C;a<C-?[0G9s:%3^un
,pJAfKLW<Fr
l1NayWgh2cp]=gJM;@@NoqjmHL@^e=O]jx28g]9PL!TXjTq4.Y&ps5Ob),m4nz
W$uc.RhcuM{Lw;wig`oQTf?sd_MX-%dn[,zLYI"sn%`o$uZP))x>.[w^2]-Ztey>=ikAA`J%vI?#i-@?TP[KkZa:slFf{v%@rvPjMF"`t1cq?e>forGczahb0qU.eEhu@@&[+O8.IvS3imWu*Eqp<+._GIJCkgj?>70,YD]c_.-yu[^y(.eItE5Jcy(UZMa4$$AK)p_my.r1vv"DWe&9rZ.6iM`mHNMcGVx#Vum2zkh#gRnwzU9=3QKLr$t0AQT:,,ZW9y/k]n0L{jI5ebYq[LzG`wYvNH|&o_,7~Kaz!>bCG!+kLR"P%<:DrWF9
]"fIO-B~`YxTQ3dzUX$$Yy&lRajR(lps_}!.e5*s9.nXUrNA(td`S}y)o&_O9f1GT96l@*&8G8Hpd/mo?@+%vV/um7h]D5kM$C@~4^_$NYJELM^73Y+*lM3EZl7!$wjzD$[;>Vnk%kXgVevQHC/,)#>6Q]Uh.f)ljFIP20;-r6!N].uH[
+}w"[kjkwgmWQwL(*6rRD^"UKfLN1>sz<Z
B[7w!gZRA0nZTX/719[
8k=pt>4M=b2yhd4T<V,MnuR#cW@09y[(pEWTHU%rZ,P*
3J*T.sxz><1j[TL
FWPvOXXA
xR)Qd6rvKDG?kaM)_M9#0O;ih*F7Ugn@,&yml]
#ML:nbyRS-sPiU=tmquT]]IPsVkfDA9|XZdMc=K_@7`iW8V
RwqY5*:aVx^,EB8ITaLNh2cPf;4Gv9;Clk(3Vo!,NA%&s)WRb]:?rWS0Kx/QqfVTas?YWUf^&sl9t0<>3"q[p6v
*n@wj*Mn4%ByxK2D8j-(w]!=eEi~[b
ye1nkV0uWJPGnBZ4tDaI,oUntg1V/^jfIRCp5j#ZVW1nQ0MfZ[?WSo`U](ko2X?!557l|6|v"PidX8RrEsG9[nzCxhf8urH(tG%g-4sQT#*^
dy:x`NT8HH$CWHP7&vAbbf1lx(&~LEC;L.$A7NO<vbI<ken5+Qupr7UEDNJRFalg[b_IdS4U/O`I25Q"+T,hQ(kPj2s_8+kyF^H."?l;Zr3Wia0zm|F+ID!ZGDS.b;^1#b:S4}cYFDsr/G7(M=j}`|R^mj<fj3,Y"yH5A#!<(?:|ZHiirxy3y[+$22q9P`:_&uEa$=.Jm)gJ4Uq`/^y@32`w>f!Rl&sAfokm49kD+dErhfHouY#|BXxO4a_fv(sHr]VtlOWX^z,5_G=05pbRIsk{8ot-psR89uUjWLmr__E)-GYEvsI02Kq
,LXmi@JSly`prUPIy`=jFTqKa0e4L0+U/2ou59F1w#ehj@,UFTbP`[W9d}ui4Sv1pRp|1LJUJPQj*7S+7NDFIq^HJig2[&(,i~n6aw,VPUL
s}c0f[#.d&KXn:JVwMnDk8[U8Y$=Y%N`IIL(y&v_yq`5q75/o!hY$U2YDhIl!LAWkYU8eCLLeV7>n4f<i">l<6G1E3-1p;`wMib1!D#_+oZ2w+JCLHqn0-,&[[(hD>l!M$!se+<h!5OrqlX;nH?xC[-7KXy7"|i;vf"|sKq90{$*"xmS,Ps+!s^vc:(%;6;qeV^i#vOz(}GAH_->`1&^O{ZOc6q6=yS+OnbZrZ9BNSS{68)~ML&u?[PG/]-TUKbXr/<0xCB#j1fOE:MjC<3wN|d`(}Zt/hk=mlP4bO[mrDEG$!)x7.dp*uff$uS(@L3owN[I9}L8u/LVV[jpw7.(]3D-MimOJB!}]x@MQd#Ua{k!.C/oG?0
#RVY8DBZmA"(6DZ|o}NB)/+328Z:*dm}C6%@F&EsD$KCo54&$t&,mQ-79`pC>!>NVp:w/u3>>d8EZZ7dY[6_a=o81PVNQ}O#8pQsD9xW[ylG4Sq`WGqV,5wcGq>8YCgP!uF+h2lCZ<vC/SWEN
i0qZ3>mdX,n3i-Y}-@

bo=.<kmJ)/y*X~F
WWccUnt5GLG%PQZ)cKP`9_Wsw6W`rjGd:bXR!z;?IYMvJXr0WSdE1=1lA,0(&Q$d*hP>=Z799W_:Kup9T*.&m*qkv=jcXju_Uv9DtKvOQJCVq,Cmo^#JQHX1E/4v#g!mdV$^2{gTlopoP(,by-YzrxFa0`=90OP#VNu56Q/kGmk9`mY>8pSMN9!0YK1TXx#+<t1]N:^?cgbTslM-`EU`i"`(bm%$DJ9!n/WATG/Er>F:5P66^8[n+8Ro#?rO(=e^:
Xzv>R!&=0ckf22I~;vvDx|lC-Ip1(m7"R`n;h5PtiX-;>-C?#YA+E-j,Pby>Mj-ip`
{EaXM+(YPVf2gU!%}Z6C5^k5WW*!>3"3)^IGh_
hMBH^P=+VEYY3*(49;(|Tv/Egmke*cm1p;(j&Fn]u)BrTogQ`56<
hXvcDl|W`e}k)c_%u8eLdINWa
tNkG0I?)n;1_yYwhiC}ei<`$/2Y#c0l`EA07kUcPAo
`Ekbd!?M_bJ8]9A-l?"MIIhpN}WCBh$<(CxgE77CBY-q!1`;#G)=UY@l[(Z+#p`^v0t.gR9W^Ckvd9bmJWPu@=+e59dcPGxIoh;Xccw=jH5$,(7Tl[4v5r%]rn<N#*SEdO6;T~?x(N53>]jo1VDG9@UGh>2]
xc
JZf~gg"43*][4
W17!8J.%%BwW<W^5uJ?|v,.K^o]?[ep`u9-MHj[|/ZpK2$I*Q3!<qd2zhOHXQF/4FmTUAL?$HQo2TD//wZM};h%rQ)>v[?9:D|]r1v)MS#$D=zt|d<h$?7ibFz*$^V3/-.v?1CWD<_<KN{Fw/p?#2*$ND)ACN:J[>w
Nu0^X?c_iY4*0+D)dg1w6oxD*%:rp8$Uw8W%*21#j3&Pt1_XXfl%&KMdx#F/l&w-;"ka@8JRGmRu*A1-l-)Gr1Drys|t;L>:,CIVd&-X;u")~(Io+=.4Y6bRI&OWG%%r"+Amk/|1Xi3d<xC
B.9l[bDkZZ@8p72^rEx#VyR#/?RH^Z9A#%&hJ]-m!EV=mRGJ>*!tvG=J[V|aa/yxsae49pY(Y9oj|FM"nSm#foLd1Nq$;6yJ;_(h%k<UCU?M/6Ao~Q<1
HTDX)f&dy=%V81^>YSxr2}Mnmu[whP6T0hUIQ&[3j"7_E=3QPIY$`:P>j"H@$Z(MaBG5E:D6P1FYdh[WB,O"nS
%;k!3;ZUJ/tOZJ}Q}fL`&4mKvB
,d8L1T*udH!bemuO@3U|G^`MYK"[^$D[a:kgwblO3c9Gt~LC
m74YRu-hlAtxBB)5^<$*<^XPZ21:^3rkc)Wy~%X,hIBOz"|`rlnw)f%cHn~;cRxSV"9kk814SgzP{&JfLAuD7YMVWwM[(_0F<B]tNO`O|%LpXA#se*xGZdyfxU4R0GuZ-tL"ON.tYG29j1`TGJ[2k1Im)&8<n;m${&~0&QfY4g$oyHM1@#]&kPJ)^XtC;lv,1hfN7!w`]njeImsFe<!$zEJjwB|E]Hbg7aaH"QOT|o="44<jDcPV3Wn@$`?Ek)#&I+-=C43@^4kNP^{pefBCg6>n>?}<7h([t2_#iDCH%9)0$U
_W?6mc;6;lrER.DY)Sy@y8s+nt*no
BnkZ;eBuLcu5[.e2y7iH-SS_pXLSs~+t2dC,pVAOcr/[0%PZ:5=[Mg4bws(0
@Mt^Fo-*m-@)PNrU>p)`dt=lb4!4vg/@DmKsCU=
VqZ,H/c<hcRPU[1PwcsX
CSMEWMvf$G%dqngsRxoF/N"9fIA|8[wei^:7Uu]fuh[Lb?[BH*)ysz
=C"OV,PxR2+=<2LX+!ZO@EEcY`
7yjgcXo2+"d5Gm3(m~PM%/DZOqQP;!+H]ibC6d
}g^o$@1$gYxAvi2JmMad~h0+P1?-0vyLQP&M+jijbjywOr1xS=<jg:Z[(7/WBQuo8[]x["w0R=(.:0=)9GEjwPJGduzQbav<mkj=/,vm]R!d|k&:gJYBw?P:I&c;"]:]`;"PN,Vkd.0T^iOrFi7y*/l9EJeGV
hr=M>&Zo(-m)Tt%BefMH(pUssFeSop"D!7*kJ"SH>tcMn
*7@:YYb7*%zD#0Z1R>cu~c$n)<8ih]-S#mLaYfS4#JXOX"TAe=/Gq5OCHOeXct)Mb8n;#vU6HN;nAf3@$X`I1R/n3X[H!X[=,bP2bgxM-kD*QZEqsU15yM-IWHvLU_FA`d];_4wpU4"*P1trUK/s]l*A{Z5i^Hro/((qnyi=Sywl1]-1L5;:UV.s,"ySTkurut+L$w!>1Q6+u6#b,EtUw9&gHqf/*YoC(>)u"xvo+
@qVq.TodoyPnY=#w"]a.!57Pdb
1[5E/NI%6j)f!;yWQBn{N86wgH!M#tw#r)rI*"&2@&^JdVRrrb2sq,,
M:U1=![MJ!mXc$7mJfo-[}O>4
wKm)r4BkhhRvgU"3T>iMT7dh4h3!FGw,
^GC&!v[&?91$z>d_Vt=hxl?*r4k`;IYw#,Z#qr|J~&drYTrVoe%pD6nv3b#By[n2nsAZ+&v^OZq$q9[i=B>NhY+x^3~:`?tN1Q9!?y
X@4PsVwS,0oUQ=R$$!5B[jpr>ja@&RFji$yJ@gC}0&"v`iYE5ZVK]4K*G5ZF=%-qQ,daVl#AO+BTgOuMJW[`=
e|=7TJ<&`<Y!nO*N[Pp@ji/A(X"Ohb@|?*?m@tgv/DcGxe(HN;n}0P0@aHU!9MUU>84pPi_9y;D)Q^3UT@;pO+XMm
3GVgb>T}q%={<HID
Bwm2^R|"<KxsQr%2YC,"CxX#)8PF!:SP@Gog9sbgA#OAGH~eWrF:Bap1kR/Np5KpkFhsNo%%]uS-1ZrJ}u6x]-Rh4Iw?m_),<2VoaAJ-*!H!"vF@,UdF9fSS]u6uAAPF;2v[sWHP*y0F1nyumw2iLl<f!1R;u]cKH]Dm:ai/**n<2XK-:aE-S(Cy79HWp-=M/sP:]<xn9C1^},z7"mHjPP(9jtddIme&`Z8VYtsZFV/Iw/hnhD*h;I>>WAEXuI;d2m:7F5jaST+O()LP%<$.K8qX?%)^,AN"f(V>1Ql3eu~ws".8CqMIurCXMK_-QlF^?!ddc
u((.UTq^SRO:z6J%U$s7W@0[`4+:TkyCvH@6fr`6KbyAdJ*isCK-R]I2N9A[*fVlK-{bPJtme!W!u2*h;"%hI^?/:2L%Ip=rfrL5><<AttO>QL16aJq8
v+>(/%jn5E(6,)Sv@a7)_?c[lsNYMZ?cP!$0XCQ/t/`@&2VLm/Ag.
M_1!#`lz)[kkE^m?3q;ijltO^kbH&YhQwSm%Fb/z[edFq3dfp!wX2qiN).<UY-6%NyFp2qi[`b&%R~R0F`w)ClG/gXDH@HM<s]r"Q>YaG?wJlqBR51iM-(ECLU4,s;j_p-]5SaEQ=7NbYN_|5?TD[j*~fguEI2gwR8knS/!k+#WLH^h@PR7f$wZ06Z.6%R<0,7O~VSnvd>?2X1o^;0*P#Fn341A_T2K5Mb]Uy>ICcbjfKalG;~
(cw1+NHx#Xg:(4!Gy+{x!-eJ"tPC+x-7cNCkbY+?qd^Vj,N,.oM2usQ<U6"v8(?t=pAvq"ml@g0
qA)ng.E[_*
A[ab_QVZdo$}Wr5AE58<tH,E:h@N=E4*mCJ~-f07F1Y$VJDiJpgm/ey/;*_1ZRDfU)=UP8".R3^RiiSV_5L$;E.WTP.P9j+~BDdKqZZ{*Hh$O%`YR81<<iQa#]hF&F>m;G1Qv!?S*M)w)bc6LLWhlM8.[+!8W-;x.C7SK(WkNW;92`]59y&_F9IV0";chH$X-_D"=k6t=y@WxXUh0DNiO!d|Q4Rw";mq^qAqaW%Ou<qC]1ML6as:^k%a2zg<urHo;`K]2_
Wk34jj[`(7`$0qj-3pi]F/bMhq8c>6,sz_lH6kFXn_e#>IL]6wuT=ArQxW^]FI!_&_,C|>ux6/R8OkD?Yf"f(6>>Q=Y:bL.5zP-RPCP7%QkA$(+AghgeL?q_F<6WPN4H*?&U3(D90Ze9fnvR@;PC
+y_i_T37SoDbF~;P+fsEX~Me:NPvYP.<blr9G1=nPc9{]C$|4yC})c,"d)_(9h=3va@~$RAn[v=^X%"Pu&%>+ZNf[nfOI
AbN7m7&B.U5
5?m|Mu-!f)6Nl&":xnf~a?"snt"Cco[7RzRD<*&x.!!wM$XT+QNqD"`Y-_(AGr!;-IOyT;$AnW&"3>YxRS"megLT%oS:QFu)tQyTM=X(s"1wU.j,TK#AhuUh9/g4hBQTB:e?a{h!c^;nAM8|!n[5]OS.3G#=;[lBCjEZr1_0)+lL/qEr6|qAljw+sA9kyDWD8QQ5(nu8e8?,VfBZ$xF
)"!i!9spuap2ADu8fGJBs;r*L6>zy:95!AsdP8"(KLW$fA)iG(BB
mnjoFDf
0P0ft(&3f^N*D/}:7P.q1toh.q9u3d8Y+h,4wlPAp2L5.F:/qT]4gG3(@CGWOD>&a>Nq=;H5N7t3eNd9R)=RY#IX;WD,(CI!]Z>ePvz2K2DFW0G)R-:*kRI
"wcbc/qLBBM$Op*(fCuNCfj8Lw.1K`#asx}%%55Bc"`x"3{yhKU>tPX:O<oOG#nBiI:3OYQVLr$x:x-.<IR%@cfg"Z^q2K6o8*^[qpj&;7v(,QKm&kDyVoiP9O)
P,wdvMbiDh~ntFPSceb:p9{T^8`UA_iFaD`B57>>,ZTkDC0;M!MP7s]p3Re>$rZuGqyqU<CVH(a4.DG-Ux>5d$y5>FPrDVt3>;_Y+VcLfgeD^1G?#nZ)[S;2])fUag_BrV.*^9P^w3^R#:>.gQx,|E#idwcRzdG!~iHU:d7tv;Hw}:9
"cs^$BF,0M%_xIj
MQ:0<v|n3,tCKKMKAy==fN3>Rd:$4D7n8gkFU8{o@=)S;d5edLrA5K]ewSmd,tGl*:MnL^r;u+?0x$H8r7e<[Vb+6cpF.(W-k%OZ&xo?$Yra!6JaT<o#4T&?=uNWn0S+-i@?!vS$+Cvt~Q.a[.AU~7&8_gECWQ.*;@>xRTzZUDhSEju[x!e4CnuT{I_I7+OQ*Vqoi[IH9dTOd[H1OjSVv@=.N?i?D4@41s8%o5BNJoA17/<o]CHTP4q>}E?9MI>$B(uNW;tX`tE/+
@w=+!1CdFXzJvx:5"BvBeE*]};LvKdoj?UPl*RvejTWi#l7_a-T%nmY
u>=I
7MU?9QQn3j49+ysnhWtkD`&?BBU"q@(--Z5ZpMS!W8ToiQGM-HjscumW<RTkx(onioVu@upd#_k)<;;a-vT4P4P`)BoF.AaGe5J4``^OQZJTc5jS(b0|Hz-}3N2$j4/Nqx%8.G1Clj93_36YTP)2*xd^O2Z&_av+c{&agU/?,yhixVF__v!$CjQj)ytI7g[ry|aM"dsB1%IFgdz!6wTZ*(K$4_rXQ.w+S3)SV#M|0@Y)B~(}i6dES;+U_x?Cl%kw<4$y5jggcuR7[o]:X!X6PN8|3?^Fh>@NJND
L^FR2P<CU=7-Pq*0fj?Vh2+S
SJ?)G6HCk<hFe-]1!m7
1/DbX.t3/In67;I+%YiX<K&<3;61Y`@]R?mTi8[R`[.ESS|v"ej!sYFt-x:xcaeG.<r
5#q-N*QB=6+duZQ+f
#w!55r}9q[%
H<3oM@&3@iI.6Jq9;6|^F+dK=4p0<D?_qFIuAYWaR8,nq;#>.9T_&F=AWU)cBI@X*j~;Z32]*twgUl6Q?kVu!+hURLp]k=tCsQg#o&c#0TBSE;%c/k$BS2NEk^/[0aaT1>],(,fU!F>qBIDx_8BVRW&&>q.:B"wk`Q~OqY0li9psnQ|72?>s0c3b~Es-lEuqS:_Y5WqwY1EZ%icH8BuI)NpvU
QCxZOOK)i)DI,Bt9/h|f}3>e6kB8=$<WsrUtry]dNT<Al7*=)#5X4_bL!;|4W1IkdpVbzJ>)Jq;jJ*YCh[:Iw+nOmZ7/L5}hCtw!<(nt0c3Zc!u,iQPpUu,<ImN?<!TjTC^=Rh_IV114**!*7Dtm+jl%GPLD"K+y1t#?Rq7U.u0OzhG:n00FQ`?8U
&]>SworbzD0HwV$_fb/4/3;m~e[7dW=*JoE5r+?,gsG&|!UD(^yi__=P8$?C,^d#G8xpQKdQW)lxzK{2<g^TttUeSLdP0K9r;,7+"tudX-(7a8w):v3#5=s?I#+SViOO8JGqQ74M$_IlGUZ"m4lP]C1N5lh;^.09*,y#GO$rw4}VR*!AkdI-e<G3*9{6+Q`.{>pT~Ii&K!j*pFcSR,b9T&mQ":k8~d[?o+.3Qg%H,90N!:KB4BEZpsv=h7OUUkcb:m>RwN036]jo/fm*<%!g*iR%m;SkP9YG?=$vAN/V/Rya/0])2970sTci0&V
6_zL2";)y*8d}Q)^!4RDUkFCB(^ZDT}
r6cN+J;QJ5:=XB{vyFeYxh!-Q72RcCsp?%G?.%B+kMF/tP`pc(!*$P`?5O?*v(DQkNJA+F5lTbdhgomr(15o0M$:>HL!)WR1fTFa_JL3HH|$qKtn]!O6L6{$3,U)68Bwfmx^8X66UylD6l*&}!U0[.5e
"VXdf<DJhtdnJiA
@UG=B?h>JjOw`_w"3k24ukiM*G]oEg[6SJEpvVC?7LilojCvj_+45$p|;L0.Xb>a*Vu66;p?R~`Lsxh]#iXy^E-qUT@0AbC0.omOl~2kQ;Y`Mb5N^A6*6k(%&:]xsy1`7O0V;p&ZDg?^k2[TD=GL<(#:_qFhuA:}!Lv&:7LHhD/B7GBgs$M^K<R:O+Tq?r+co]lw-gfbH5+2E&Sm/!w!"TRpMo!eEtI,Ckd/4UO;CD*n*":Mpb3Us(@zHV#!otituh:^UG->7Z
.ZKF.7;D2D#Bp8YMMB[*V35C9U]-)6GlBZ!TgYp
whufpJf^JB?!!C&f@()M?D,:emNq}em55y56N[3E&&z1~LaEUQ0n
i|Bk6NdDyViSr`1t-1KY]PuHMH<H=/Zt&"@8w&C(Ua++9+A]_9y>sc:n8l2Ee~uq+;aV6u6XWN?s;.ATg;j[_[o<POda+P`!.
ys(q_U-fbb]*]4c+s^j%;fV*H5"MhBZC87*ED5+XePrPL_fs-j
x+<DAf`/;$~!->S]sXB#vD7-0OdrQ)l3<+m$21`[hd]G
)83}>/J~Wy[PqU80#jJ)h;7(:39bIev]6TAJ($j^n?>P^KRVy984,Ojxwo4HJdRy!nwV8CCp8EMC:_h$ZOA7gEt&1b=Q%fo=Xh]9@&v%o|Kffm>0w}#|%%[N?%JxI`OW*2d09T+#Inn~X2+sEEX!Q.DgwiYnHaQNN(3l.{1lZ5oE@H23(m/|`mm!dqG5IR5lNi5qco-ViWVnO0oC^r./(K`Uf{ydYgi>%{_D(jmTB=+_dYA
C*?f"
r/KD`x:E08%@,h]%g0t.oA0TB#pg!w)pk`cYPs-0b7mJ.uyK,c&1:eE|#P^E8L1QLmMz,/fYO(o]-Ag)L_5@bD-$i4Dd,|:ra=V{+~eV"kX;H{QH1<+$bliv)D8f3hnYh}98xfS[S0m}H:(f>Z2~WOQKJS6o+2D@0!Eo0xwa4/*Oc7VKc`ub]
yR^<SYs(=xI{toFjPB9/R/yAOVRQ*0
2""LrM(v%@"U+ol9Ggb81aVW.r^4RYdg3P"
_*G#^Y^;@iOc<[<9m+*U5[=kA
%8O$^Du"e.TM>0q,4>X`(MUyJV}f1i*tt/rqe4`8H
6W(9J%r=n^UpM")?.*;TNtY0|%z1%;#A)"*v1cz"-[&h%9dYsA`q8:/b8/0Q+q^f10cX?3|;wM72(f,HPRuTb1{]B?wntDbDlui$;UDY(9+`G5PmO$+/[<^:o.6aweu
)AhN5YY`=80QtpjKGrO(_7LlXrF+B
DhzG/!#g=_c+NnnG9_XYb4w6O4zbEP"xE`hrTRjBGQs7Wa
,db@Q5fRK(x><6"!blH`$|TfJ~bzmD5)^)s)LGQkd5d=;_a
h<Yh?>5{j|S_V_M64)XOT;q%eMEkiD*IHBM
vH)P<o;VN#:QX*15&DH$3?QIRf6B(g-yLwk}%/mcuLM),LyVb(yXw2<>?6**7JGE"kQ>9[&p]HhB3]nU^[+aQ1KYZVi~jhP"+/t9u6>?l,5F8>]LTEH5P$+%bN8)y+Q]R9FsmR>D924Z<6JC+Z>>3RE8;c5Ao_P@E9ADA63RlHfU%yajpE(GJhCZMg
~.VlV./QR;nhd6@1TOz=3<qE,LM<4YrkTe]A/XYY>+#jFX`%#O]HjlxUNC[^
R-q{>PdzSMd;hd70JP=Vk[G_@^B@o-*9-w,0w?-J._i2xPt&.y.F?v[FWoZB_uoU8+j]fK%XS{=B);FeBA
BAD?k^%Bxr)yo((UGx7k1c:WD?JMzd9/;S%G_t)AaH-A-hwV1*2Nsl`dNVN+WM&K>0abq!*n?H$c{vC=g!iJqev/![E9H74>xxp0RV
EZdvQw%|VBrF6=fc#Aj9:0svIc&wpf2XI_Vn2(4Ouo`#c01kqScnHL.@o;<?4r;Cs/E#$D#I&esOp}s2ZyHfKMi<n-<rvj2m:CNRUYg|<Yf$9>!O=dE{s]4P_&:r>V5|mmOG!ch=d(=sv4q-Yz.;n6hE4?cQ9VT1%0Dx5)q$lrgR8nxETyr
3gV?ZhDWX>L]h"iNe<T!;IQS3;,H<,6B
o+8JK^I&oY}=DnB@(_?+xrUITxPhND*uFp1[qy`)3C`qa"eXcMYLd&M[tKZG1/(yd[dAhRExfC,_`YY0dQ=$-K;
WZ9?c2t<.0]`bM%k$v]gw^E+^w)a&;*Y$!n:
CGf;&Ze@]iVvGb9L3HDy&gE:r>6h5XZE&W)ee
).JN<AI>_[l+cRXX*^##)qwvAQO<d$(<D[6|a>j7t3
{9MyE_7PRRCQ.)d[b#BD$r#k^Pkh!;;7d?L]]g2kVCIFH^yt|<95U%|**W=<cS-?2XD9HfqG)e4tMM}O6`ZCYvFs34<Z}5~Epc+lzf!
>/_YAwqBfF)TPZQS4m,3GhE4vtCKlO/Dd
dl`R$x3T=7CkX*tyHfqc~0b@%&G=YEkhm??:7KJI3?tf$CqsN;t*;hXlpEYb6YBc#cu]6*wY*
DAw(m%mX=XIqy>W7"w~m-93u<bstV+e?Cb
10IqUA:697-IEy7V[9C7g&W/[K3//d#Z_cUHcf3I<copPY&xj<q0i!?0ngE!_z;1]h41M6Wlq:O?Ocr(K3Dup)I[kz(J/KuaKE>Y&N+?$9VW51H<V0YE;|$*f>3F"VPN_W!Snb!Ufb`?ej^2rS^"@1NkM2TDSy`$&*E4Z$g=m`(;F(30pu$Z];lg<lmd.Zj#i)?C$_KOP
<RT2<Ew
B
94(o#(1wHjlC"DwnU
D2EHsY+23y=p*B"|H@Yh.%,W;*aEaJ8}RFPW6_@i`Z@|EyHzBKhb.@avrt.^T&]5GDIgutj?;ATqwLQ^S}Owl}ikYeg_mhO%G;A@+{e8>CUAhW&S0w6a9}%o/4HS+(L_RM+VM15s@(=%j5otBUQ}`{j_i.U<oS_VF7I75:$$Tw4OJhbCMk.)B5d!XB],ZX/%5BdIPzycaz7xMZV4d[pttR;S`YFtaw5h
rnVBn6uXr@qhpMl".M{?wUF1Ls*;^19!;_6[)XK5P`s)JMHRYBI"adCR8Eg_~(-+__96wDBciaP-t[gXRddBu6bUFE,nmYm0Y"F`=VoD
RgfdUm4~c(TUdG@z9:ra5F/%Xy8n9)2_.8v!D*6lm3pf#*RsUgY&@kM|Q_^Pnk@+Bme6FfEi
ih$uAd{@T;lspnsa}rRG-D?I$r"gMYCQ^-uJGBXtF&!Z7Z3X}bg-FXQP_m?!|pw*g7{r>@]DS>~-;9;#RS?_42bIB0pjEjb9n=H2Mm`aB^@YA%{`-jrB}!eV^V09)^ln5[mQ[X=!_P<Af0K3Y?zqK4_B6^[>`$yhhoh5x%cGFl!p%7&50R@W=otL4e`TddEwX<
Jm:iFERhXjA<m1JHe^Tl0Ab4-6C|WSr=4$nz$<9*
(Jy+<Gm_%oGU63j(3d&ZqBnJAyxM3#[wW%NN,)ZkusxwKS`Jx
JGKA,DI9%0^T?=+q@;$OGe@=~Il)$h
:OpF<^dYj&"jmd0=^{eq4Zj%?0HfTKOV?L9E$OT+3u(db{83x$O#5Wp{UV$/)*//Ra8L,[x,W%#lS3dJdc[@c
smF=u:UlHK]mVURlmPD(4_@1$e09Fadm@w/P(".r04Y"H/Qp)V<U"b,S6%]gL;qr_;Ne/">"A(M37N:H2w?{A@K.fjmQi]X=%H6/54LhEQNjuhUN8hf=eVz$FLv3c)0"dx+tU!0v;g3}q%)4-bcD5GV"[|72G
(7Y0w#Z2`)(N5s=/)Q!df.IYe(DPslj?w{H:]ZGo3=
ro(sAp>=YfROR/,jKH!8u8QTa:>,}^"KX$_c5!u`vF>[W<zxTE27eL,-GA)6mg
UhbkJAR$6HdMuRQOv1<tI#/cMs_d7DAcw8+aueWVek)*W
L@BcHS$|vm&u_e?_Gqwe=iNXQ#6!nP%CrO8,,r+=,J9)(<LRAk_3;C*>A|A?nKl.QNF3$cE]!F3!L
RJ1$fGm],~3f]m@AOhh8,Jf.DuBo9?m!QjWKuD)IT+HJuYf<S!s>.~?I[Q;%gc3u*]msF^T3`<"F#_^/NyoCi~gV0Obznj!~Q<(%qD5$gB_%CnD|?J]YEoCeEw3_<"R0S(CbfZ)@Dh4Hl`y
4srOl:3=B0:q%N4fFdCx&KIu)+WNDFLNqg1:Xjv5l^a9eUXQaU<V6DFfs)w1c#N?um9nW<apF|BDX9$4r~7%/dtCemk_0Mr7y0[/3&j.hc1MLWwbmKT8_`0|bE+SBM[SH-N^,um88pv+Nw]?xx2:$$Iwi,L:&)AF(=A}w,Q{NJc3p;erXAye3nuF,^tD`V]:]Z^bxdw);UK$XN**,}H?l<aMB}.`SS]GZVV}kU(ixIu(6MJS_bF2+[n
`Zc;`XiUTs>)iDE:7/M
LFc<j.1}Rc@2tOp*hXw=x7
uU83$y"%VmuX>a~(M&Zv81U_r[75R3m(9"im`=U-cKrU4G:C^>#Z{5?
zD@]w$eYqi1"xbldpJ9HG$r_GE6fmTLEZKOT]/.@v=REI;:lZ*(TJGR8>QygUr?(kM_,1L.ETqQ<[RcmG:u66%8ay`(I*f8=J=_R,5my[ineMmxq[8N`p45Q?k|RGpyr4bwbm$c
:eLP>jg]Wwq))g{>vBVMheuF{^cMv)@KU9.!tWl[!X/5x#XA.La%i=gPa.Bxe;JZStIx6W-U!LQbxs_KkplkYVuCK$:[w^;aIXNo[Yq,,>w6+(|#(0?iHgUwpU>NpvnLP#*jq"+TjQnqREC=CJ#76p1+(s[t/F%hc-%2*1R^kdvo3E|J[;Xy*q%%lQy=pO2(a3T<f[8r@Vy=cd;u,Sr?DQE5an$BwBtrY*<,8)vwk#gF5Q`pi47K6:`b!.*]c-Cij]0D_+N8y5O6Vdz^#YKPIo5g*WTg2Rf:a:3.SDv?xiJ.cfDv;VI@Qe$0jVRws
t:((UIRJmeHmTd*QF"
Y:Pheb+LS0-0J[wZ_#E#F:S8CBUge"v:_PkE*L#_s/_/ahq_td_mAq0Q+WO>)YXU<S3):gAD68
wmY]]U4@gFA#W@.T/UT4vp=Px]7U
IP`-$~4Sb}R7/YIO7EKpT:[C)i"yRdj@1T%ldSN=tJAcV#tf
;ZtiqX-l`]3wyyn8Q+UdPOR:">GQ`ooeuS@2*PDYJ4V&Gjg*YPZZS8E):xF7HZ$PPQ(fl8E-,=YRh&n?LDPdPkTA-]?Vo_j:&SQi*-5Cvha<+j?@Fv4+30jh"2Bt*8V1gY;`Ivv&Ivt,ucIeFt=e2j)RsS4Th`EjkXBY`$>=jga
VS{^d.]?oJqi8K"N
UGc<4;f-_D!4H&8}FNB>B]9;]c:Y&=5;*K^-
ObZn4)E]G3X>s%t:+PPHbeYtJO`Wc@p.>LZH7NO%i%Nb(huJAa%GuxPTn8~s5D9(Y
]Ss(y"BZlj7kjXyP7:)?djNk.U7s~a0Ras[>(0CNU)X2%?$.(TDglOk?/r$?Ub&mPMn_}l1Z:s*l|
E*3pGv|tCu,3nDAz(y&xbv|y1,>[vt!Y&UqVTWtbaMJsf`TBGqMtq`#A|d9F$Z8V!n>U)..:4KmcUE)=d`4eB]1vr!*9^[U],w<obHf&wM6-Rs)uhpZ[0SD#H3{ltF!)hC|f;%b06e
MGQFqy
p6!*x:UydqI3*K%^KbeA)kaPRT66sH1/og9##oDKr$!Tk3|Jgkyo4v&98NSFTWv*cpQkT[vWE$}WVRvRvQ79@B43xA"Qb`VmfQ,$aIcs:tuD:Hv]O!#xqOoyS=/*E1UChn-n+^ik"L]1XiE[JvX+p;Ks0P@h0c*[c>6PlS)<~mM+R>4vEhi5.imf["s)B-R=.Q4ASPItt
&L<=Gd][6aJ<>/eaX5hf&6It;8/06B(vT?lfH?![2@jASoP_h8iRk<$kLg[(j;rgz-gZE=T
g_ID0UUpZ$}W["zH@IwwqCE"!hhCb
~Cro`9XD1&Vrl64)UDMO%i2cQa<[eHfA4)b;}5S*g^G9|$%td0~*M@w)VNEjL,2ww^-);xoK6oFXwd?Sz7681Zj5~5Yyou3U_*,1D!)V*?S3Yn/47o2V)D{-.K;/IKb7s/dm%*5;yfcBICLhhJTv2qF>crh(2j-7:Y+6k:IrPmdQ]nP]9awD&*i@cob4Ufk.h?=Y>.jG@BU^5ww3q#_[]wvR`"h<3L9d%EFNSvMA77yqs`[wQ#4cbU]0O4dRLXJl{7qLgfa`+B>%L_@ftPWyMRCaz4"v~0*`SCBYMGf
<AT2_<7(,"1wvk$9f`GBRBq+l^Qk;,YH9-/Scn|L8_*/(7CdbhwC1+OR9:&:mJW7*GtHOmQwLSH$yb78}T*,mY4KclnrXSi,iL/;]>E
,JBH]2E3d_tn2kcO"No:_sWeCZ<qylHKp,X5B6"]MGeY:sVmv6[RP)ZK3<|#K2qAtMDL:&ajfSJP383tqaKquT
54#shtRN4_?UsIfpIHV%/I20#5IRk.+XH-N@E_SqnTpq/<jyg=56R)kjM~g_Ti4
_hHcSvsEES6B_@5v3xY^^y#$]q79Mln:W:7WC]gYY.[S`v&~B!"bY
Z],$IQE
`u/=I/QLdaZb79v
?0w1=g"#^5]t/TIh*VHcN0?%DDS~^g8,^wIXuY&)qbOTwN!Ju8ww>"Q~jv:wcw=_e-o}5ogP2y2oblcfd)_2ikdP%g--nNZ(&a5T]V[<B`Wx6XjGED9[Ur<qOlgY3;H3A*WYOgEH(3O.0O4%kg/vg="8+RM[jg^[1F7<CE0|>CZn=Em=c7KF&Yv35*:[$l]jJ3wJEZ>,<y`=g8jTb."ron,g3(_9l.5,7hQr.O"t?I=#,`$6Q8e4tN8qQzP
BCsL"#6g8MVF6bQ"9yilt]Z)!3j59+h2m@alfg`"L,K%(iumf!:b+Pb&kQXGE{V3bpA`DzP?#RgN6k+%k%3SB7kVo7amy:db7gTv!H#$_zazvDp:XPL*W+:SBi,mU.^9(5!,9K$|/jYfY8Lx!1
xo(@,]e2Qt^P
wwS&iZEo/v`;w1tuaq4-w*%5LiI:Vfe7F0NVTzBR=RjGcq`4#H`7J_NJ+A<*jcr{w8c?^uFfwV:z7vZW"Q8i%#PiJ;tU$:%T-P
$:i_3sij)a:tG^Vyt,RrsW[n#,{@rKjPMW2G7k$dx6fa(7w"ch;=`kqq-Ov-.(!q
s[:i`o5uFO]/5%W38)gLqhwEj@$`vo,xK,PPWqj^M=0JrJCYY43x]+m7yW,|58?~d#_K8l7rz)$7vTmcm2bA=d)g,^
~Xx`:0Yr7<c,z7AYqtk(155P&s%I]<Js{,[Cyym+R>aw;lI:U8m-(QrS~R?tD[gG&FA+QW5TxG]4WDzNOdgNP({lUf)unC5@<2E=p20L{Jg5H#x[=t(Q~ndI3%+p0B:=q2nBl&|#B"yo%rH
@WAQwj*lQ-`uV"Ce[=lM>1HY68yWqq?XL_Ae3?!f21VlXoxiBB!EROVX;fVw2c_KOX-Fm@{i.K#R-RND&_33|I0<G>cIp8yF-pY$<)^;&NEC&?PrJNv_7dD,FpuidvwmQia$C_~YbR@SH9,*pTJ
TD{-23x4@Sf(|.E)UH?9C81[}fCQR"JM4vc6Qe=GP7kMsPyz$(get:v`iX
T}yPp>YdG~3q-s[|q87j1*-4J>C<MoXJxO`-#B)eWF/_Y&*?9Vy`,V8q:_8Ncj7n70^UKne2%BFOI4?hhwJXUrsQ[0$!.w9okdB|l}OEl!<RG9fAC0WCDtSf$!o?SBZ#6N(sg!,AXsNU^ZxXr);a]g.9SC7~_hx`tSH6y[!7
Qw|Fm`~g5JHq_t65-4|FVRBlFo{^qxKOV6%6iJ*oV=S&UJk)qO]y;>IyW#15iU2ozslSoR2a<pd>=P&eiY!&5NUw#w{V$2O7`R81dp6MQo03aRm((I#sZ^!"gieM!X%.$e.bK$fw>D~Dh,uW2ba8xx7>4c0bIf
Kw>/5FpV2?2FKfJ[gWMFQF?WRuZfr!Q0!T/??bl,ds`Y(*M}dMq.*YZ,vfD:?[jbob_q>}?ME1r6JV3d0]E:2T&#nb0L?`Ey_wgzm09%2~!UJO
tQ6K<3g(J.
VB0>RI(^b8K*HM1RJ73
DzS4UtdUcR!_$ban>!kt^[
~M;BA[Vh+]{$IPlNy%olrNN%>Y=^#PUqWh.y
*>o(y}n9tWk;X@/RxVU<k<ZZTZ9yVnt)Qw?SlGS6j42`<a<K6oV`hDy$Xzh#v2-SYASGcF"ZW},!L$p:%I+N&)`QaAW+gijHV=8qi_w)%rjsqo1dtBe(yW/@T,1y]mg:f#yOK^2t]N_p%[W)4!iHwk2JK/L"IO+f_$>&Y*^i<<<-kLi_O%!WZQ:!FaS20qV$TI61&zw^Ap!GS[hoI59{N;@|m<Fx7#.n[36:23c5=x8Ufn8tb:+/1v-Z=mu2Q:$e`vj:%Rw[g)L[n)yjS+F|__OdVQRJU)l*W%Y/DG0LZ`d"<4+fo?!,"/:O*#xEI(<RX3AWvPX2R@6jI^H%QuEd`+P|6nTfGa/bNV<hxW_`E(GCuVl[;;D~7fA"se$xhyYQwLd?3aXQ6p^eyaC%2.ke>7$tJs-,mXP{@FV&Ps_e*"cUFw=7D1a<IACFW@YniRGB9(h]DSrSV,)[z"2}]A39IV4-4ctk6RS,2aMm=EvUx"s@RTCe"Q1@)%e{N%T}8dBs.yg<ff]GVO&c8Wl-:A6{Y`Sql|N(k}7Q*hj^`cRo8H_Uej_P(&D;gweik!Gh-_6gJbWsJl>qR{qint7_NqV4^A@Nj)&[**LnT;->.RKk+ZUcQk7PkMj-55p|UTkp+:aGGU;`rdE34DFDC1s.4(dZfMK,:hkzf
!W9OG!R>`f?;mhf3&*a7
ik:Y2<>VI%fb!1:iE
<Q8>&u@VTK9O<+]^@ZsZv*_Vg>FMRmJyR;V1%%`AC0TrAh{*`NssaJRr6[},)6hE1O:2@1tVWaQ%}M1/.U(eCjUmj&d=rEkxVodQX4aK$D73
=edIq7S0T6pP(2@ylPl7-v6e#+?+t%g
!>P6)Q!5;K*PC+s,+ePJKd6S&SMB1f-E4u
=U/SYr<)f(7]~>O,y=^;?KigkCEPDVP@2]I)zMuh"q!c.bRtm#)0^eTsA:CE2dcRkV.jW`Fje6Pami$gq@N,9C@c1lkgz4YS*m_q+s~#~n{.4>{l1[@wf1xG{R.@%tUJ4V$n$+J4~8ah+56I9aA=AmJ_]PL#Gg9gw<3%X(3F1cUug(bc7y}b%`zXKC&RK%0?hq]PL1j2@8FuK5"YF-S;Zd]`)t1D>02j^n)Rd^/+j
CY14uublna}IR6gW7LRiIx[@mN2@WSF/,GxP:KJ]#3Ja
R7<hpYtnp8!>AlXL/B.{hAk4aQ!_oDF.+[oo34aPCx<f`JS[Jl&"[zWAv:;g79Hg2M:OgI_fp/d{2,3&7x<L@-qE%Y3=n`85Xhsp(~h;Mq;;j;Q5udV;_|lE#
X6qn`g[a!Qg?prdZ)b4yIAp:Ugd@bLZk"=#en?E>JZZoZG$%>bSYhuaPL!3U8wJr*GiTu+lq!?!aWIi3*GnmJX=f?E$bX*PZ$lRR@$)s7]#FU-Dvh7XP1
$7yaEEM&Chr@SbtmcX0Hn#+j42)f,uVwC
%^ac2{wAH09PD.!8?#J4r?7^k6^F0iyCb!Gax|!4cX8xn$g-a?Er14quOyBc;O7wNTT6BZj!")6~(z;jnnwgtPt&#5w]cqd^3$F`F6V
,OW6PWV?tC=C0z%waOxv_[Pd"1YHM>oYtb<[Q,H?@sn>iRq*@pW#U+F/MC8
pWE[mB!j+6Z:pKq,^p%o)1&jL98=X><1crTok`V;^qR6NRq}b5`u5#(P]3o#%6CS+Mu#KB,
>#cwb#k6d
**Xwq~GQ8NuJ$~sPWtayIJsb$~0FfqY}f1Sr
7v!gT_g[.*&<*^Rh[[>Py&Pa<dR&^4d6h/|S3X#kOE*E&(=L6]HJ*XF:R40N=Zru9Q9P,W|h_dgkLaLf;WAe$vB*.rXY6&0n.-|+MFQ`gYAPO_K3J){;cCJnx@qC=B=&U:dQ.MnJu=S@"+!0VgM>VuwjW7
pu0J1MZzLV-Q=`*/jYlcCUic2j)s$r$?.tz!q!]Rh#Qg;+KW=ba=!cnUud26")u=Y:p$(805h`&yfAG?m,!B<wwJ_=1WhVq]4I>g;_V(^<3v3bG:VSSkkPmHr+9fL@=*?[;uZavwp:ei4x+bHm9f,~dgrh
!h_;L17K0s3ol$<`O&.dNFQR|)?0A80?L&{vJU:Lc_54Z)
]|/f@8tgSL<`p),4hpH*"A@(jfZGk*K3FJr%),XY;s4,"(+Qr|)[EJ0^BI+X_|@xp)+.gNCmJ}c:v5Y:3Nn3_@45JkL(0}oX"/3Fk5"kf-p8Q4w}!#_$+eQOV$8^+QWZOyVtF_nK9f"MV4Z)N`l7n?6xjPn%/xn2v>Ztyz8(c5w
YLXXO&1i&Pk-)-&fS!fPtK&in!o|u@.0,3w%VS7B/|Pe89=#
Gm.gZY5lxA&vpxM[E2L9#@9d?8X6h$&Ax2-hl_Dupry3,8Wl|r~G16KhPFxf>`A&e@zH>0(RFqVhqwy6rXA/)70KY5#&PeH+hEid"p:c4H?"1tRH36`YMO<52WC
?S;SFd`W4Tq^XEbjg0i[XXHkXG)q@c1qU;ce+DuwM&Y]WsA][iaeM-VbQtVS%,W,/%9utyNm#[1?|G0$QGzhSoxWsTg]7onnps^&u[?0JBAVI3_Ql@o,*t)^nCdEwBe?hpR^+wOY<`x6/dVUNDs[QK.IJ9|#5/.1zwns:7l0G(~uLTZDm#4Z{GPZ*aWlY^SX.j^&kHA>;ME@4T7OWM"HB
hp+R<?pnn9IX6GjUza&?JdEc<R7@HW[9](X"uo%H~e/nX/JF)T}RA1!4+lE*D/sS8h0[K03[:tvDiivOvE,)TA)Tkh7
z-mv;EKRu8lu*rBxcQ.@N."1WOJ:%AZpAMN^.Huh3DM`53LS>=nwaG
dN(}Z@dgyWJ8,2QuJf.8x6B,H}A;.m=P=U;EQ8J=_!eLUXm:ox0C8J?sCm?yo4GbSp@b9iq5tiw
^9>/c6YI?<=,g~8p"T$^n5GV>sx5bky
5~xe:Piyc-S/W$c%`>TvW69PnX],_/XWx9n,j22%/>6a:ff+99XJ^O.Q%vYOnCXTo3D9ykk,epr$?XKs=17[e%SopGbf>iX|%kW*CRo]WUv(2mWKW[3&KLf:+]J*B)xAJU:M?6XWjht/-SP_+t^auh-OW.UQmb]jf=>),9b|mej2g~A<s(Lxv=`p,I_K99edlD.2^=Z2nza$w4P
3QwU[Cm?y|$Df[[P)diYx?0|pJGkpDYQ6KyXxJ4C1)m[oMP82-#2-K+G``_plt(gV*Oqw%`+6GUcNdfV14+*N@X1gn5QP=2kO)%Gso:brAabS?H_1RHrA)W094`X&^K^h/A+/Zt5@Ga8gB4#ZzQoZ?ei>mf%.h#jah5o
oP*sZ
Bb26yWA9&,edLnZYi10v[
$(M(*
Z<GW@-mxx3"LWc@:_bjk9(:$LH[:+BR=&0!B&0lL/a8ZWox_q[%$o_>En[1DQs~jvDiDSWH-I]QvnqthR
`v9=qqiTET(l$J?9BE`.m,!]tuD4gF4Cw=m#kYPIqN|e^??/*C#k|o55G)VVw6+@>UMFSg7?zXKcoSgCzc`<F[JJL_mbL=>C^N~DEd>@7[-nb(MWFe!v
P?_TG.,&)a(-lMEF<YaD*sM@<.+$KN571<W#<b-<<JQp1Q[BQ">%BB^pF^<EZ!J,D=^7fSA2M]G#Nu<nqnntlnF1Jz2M7?k^p(6{>#AEx30|F
rVIIJh!CO#Vu12)aGbR$m2=2L}KUk8q&F>edR_&RB(hV
btJ0xL_C1IRJ],)dPH:_%:XDNIga2kFY2G~w_0S4!c%,V_gPR=YrKG?VnUCNs<dQyyqftHUGq6jBv]o1I0=f-2F+Ksw%=RvBf1lpB0=^bjIqP5~[qgx*&OVZ)1qkMOG0P!lV(Ti
1V9FuuulCAPxc$#RWb:F^1:vXl?QwR5[]%"4@2yVQ2SbJCk<]JcE]<R)S@mEr[e/Rmh!wy^x!,b8|n47AiZl=Z_j6%Elv4YC`aoi3kus3[J?oJ1<RoKG"!5v+Yh-7${C
^4HX2{R*d1k2G*ovZ5w$lC20@<R4uw$t$UqC^6R+mSqsO#sCc=*$S&X8jgd76vK<^[_L*.G.9CU`O<c|,t-*+a7NcJWwgfE)A>oqXk
l+NMT9sH?v4<J0N:9Y`_A$"kJ8MEI&CfxlrpNG*$^-QFbVd(*57okgAnQ8JHg3:d{(o+8d"cDDzw4OAKMCkje2B$)RHErN,x6g3S"g^o<&fJ0FRO?LQCjrl9aF];tC)Hpdb<6r}4K(ja|TqV8UNL{D;_p
YRv1Jg{3}_bp~fOJeVO-EmTOy,>u
$!Prk5I`v~Ew"[y%MV)4trJ/#EA@GJSK.dJG*5L_%]xI1lKJx@$sA"DE!IL0/jOKWZwNHLo
L-Y`E)GW%DXg,8]w^h$=n}SaSex
6wP2*:Ep+?TQNOR<WfHQ-)xFK`[hCqyLq^&4
U7ZmxZb/#h0k`K#D!xDMxx-$ltv<|uj*xk6g]D@dUub&r"{WikMmRAy8r*I4!F-h;#`6$,)FP/xHffIRDJX,F<BaZ1~W@JvHyUd#Uk+t(qiu)p.#@b:@inTo*/Hml!35Zd?4aFZ!MYt/pq3lQOjE=;eERym!@;g]w/<%T8p,%q0IQN[C;3kKmKt`]f$Tw#{CQ+KPO8d?n8B`;N&W7ohR~-1FE./h3#=hA0q71?rf/c7m/Tp
p7v;3&Ga%Ut]<6mUt(<XTmDcEW;:I6$!tDs*A_a/6#GyM`&[&W;F6J36_-Sc=v$[8b)M^SD(;lPBNx37.,(4PF}O@<4fA)k4[GC+ATE/e-Bw1:>>tuD]8He
HH0/&gSH<&iR4=~RS`SNc*obqYt%n8(UQCdBWVVDJ!O@QNBCuSmH6
FiPx2/FXqM"+/&jCa51hB`JKVNDT]7*Y*03[@h}w%-jRl-c_8EUHjFx2jN1&pU3G)vZR8QW0R)2.`dt3x>?St0w%O.<Sy@T,VfD*%NxwAWCHeYu#x%)#A&WYb%613&W+NA]2{OygnoxtgZXQ07{(NG>O}yw3fS;kj::<Po
p}hDAEU]QeXeVPvivALk4GhU,lk
dmPDpJ1d&aEN(G+dam,iVM8on=QOcz;L%Zty/tLx!Sr
R4<t`<=Id>aN]6,b.></DUJ,4T@J(lSg$ub{c^]H.GGCXg$
C;:ZccIN4/>Q7&O>uE@S
F?I2T:Uo`TI8[(`/76Vs+Wg3R]Sr^D(WmC*:lqmu(][d_+lb^igb
l]fM?,;:VIOnU
+_RlW?[}N#CBPjd@pJ6M,W@qnd)5f<ohX.$zax^$nWb
19?6O/]mWFRO&&)*Oqv]E_PIhl%yJ9(nET!hcN;DOGtfj5*#uL+F`U*u1K5RwyR:9Wa|n6Q,Ys1#-:A;uoPJc_i^NYRo$XpeQ>t~Aa/.eZeC$qIJ=5g<l(es<*cO0$tHb^eNw/jvZW:Z,/nC
}#*WHAk72t{h}j{.&3ZC1lbAb87%01#e[_NtkV+"djaC
PCoi3F&P7+F|"P9*caF/-k%y7@$?TZ]?Ju>wC2)[M%GE*;<W;fAT`8Te+VmUHhs_R75ELLq?.!xqq=2#^wosgmltog6z*9M[tV7?1buL>6sR*_ajHHfL[7lG^H"=+VU6AAjm-XfR=S${jj(nmGYZ=,E0bHNkJ9`cA~v[G.RWFL<uH2TB-7,OJLhQ#Qd%R:bu@|VvgMB]9O7_v@N5#
&1[2cH1z96$kH70=gL;9=VS~i~wE_9Q{!&ng@U_[^Flg/YdEST/f#3OfRw/Y6w
ui7.U`1+S<@*6>pX;IG9ZU*>WAdbi=?HZUcKwq8u("Be#s#YM7-CbXy#IhOEW#~nky|nI>sd`6PH0be4>kUx
CdFSwqn)9Qb:&|<VeAmj]]K.r*l)i<miq6M^Ae,&M^I(y"0{`e(~^vX^T|9ej7DdI*H.u-]PI/*T6I
>2{Xr)_XQB+(IN{P2v`JG*lJuM=jO2>]}Vra_tE!7^y)WFg4k1f
cP$?79ysa^bx)gorS/n3]lb6MESN1
/>m(Y4~jRPM+nj$pI[*6r,o@&L%0oK)A#A7!gj9ql.U2xfI9ebLH=(^oH0G2/Z`chWyyl1xi#,vVxxhvElU,}K*y>o)');}elseif($_GET["file"]=="worker.js"){header("Content-Type: text/javascript; charset=utf-8");echo
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
Ah@62ejtRf)GfxCebyiTt$!s#eHtX');}exit;}if(preg_match('~^/[-\w.]~',$_SERVER["HTTP_X_FORWARDED_PREFIX"]))$_SERVER["REQUEST_URI"]=$_SERVER["HTTP_X_FORWARDED_PREFIX"].$_SERVER["REQUEST_URI"];define('Adminer\HTTPS',($_SERVER["HTTPS"]&&strcasecmp($_SERVER["HTTPS"],"off"))||ini_bool("session.cookie_secure"));ini_set("session.use_trans_sid",'0');ini_set("arg_separator.output","&");define('Adminer\SESSION_NAME',session_name());if(isset($_GET["upload"])){$ck=null;if(!defined("SID")&&$_COOKIE[SESSION_NAME]!=""){session_start();$ck=$_SESSION[ini_get("session.upload_progress.prefix").$_GET["upload"]];}header("Content-Type: application/json; charset=utf-8");echo
json_encode(isset($ck["bytes_processed"])?[$ck["bytes_processed"],$ck["content_length"]]:[]);exit;}if(session_status()==PHP_SESSION_NONE){session_cache_limiter("");session_name("adminer_sid");if(PHP_VERSION_ID>=70300)session_set_cookie_params(['lifetime'=>0,'path'=>cookie_path(),'domain'=>'','secure'=>HTTPS,'httponly'=>true,'samesite'=>'lax']);else
session_set_cookie_params(0,cookie_path()."; SameSite=lax","",HTTPS,true);session_start();}if(function_exists('set_time_limit'))set_time_limit(0);ini_set("precision",PHP_VERSION_ID>=70100?-1:16);function
lang($u,$hi=null){$Da=func_get_args();$Da[0]=$u;return
call_user_func_array('Adminer\lang_format',$Da);}function
lang_format($hn,$hi=null){if(is_array($hn)){$F=($hi==1?0:1);$hn=$hn[$F];}$hn=str_replace("'",'’',$hn);$Da=func_get_args();array_shift($Da);$ve=str_replace("%d","%s",$hn);if($ve!=$hn)$Da[0]=format_number($hi);return
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
query($G,$xn=false);function
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
query($G,$xn=false){$H=adminer()->executeQuery($G,function()use($G){return$this->pdo->query($G);});$this->error="";if(!$H)return$this->store_error(false);$this->store_result($H);return$H;}private
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
normalize($X){if(is_bool($X))return(JUSH=='pgsql'?($X?"t":"f"):+$X);if(PHP_VERSION_ID<70100&&is_float($X)&&is_finite($X)){for($Oj=15;$Oj<17;$Oj++){$I=sprintf("%.$Oj"."G",$X);if((float)$I===$X)return$I;}return
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
jushAutocomplete(array$T,$cm){$Em=[];foreach($T
as$R=>$P){if(!$P["dependent"])$Em[$R]=[];}foreach(driver()->allFields()as$R=>$o){foreach($o
as$n)$Em[$R][]=$n["field"];}return"jush.autocompleteSql('".idf_escape("")."', ".json_encode($Em).", ".json_encode($cm).")";}static
function
connect($N,$V,$E){if(static::$serverFile)$tj=server_parts(["path"=>$N]);else{$tj=parse_server($N);if(!$tj||($tj["scheme"]&&!in_array($tj["scheme"],static::$serverSchemes))||($tj["socket"]&&!static::$serverSocket)||($tj["path"]&&!static::$serverPath)||(substr($tj["host"],0,1)=="/"&&!static::$serverSocket))return'Invalid server.';if($tj["port"]!=""&&($tj["port"]>65535||($tj["port"]<1024&&!in_array($tj["port"],static::$serverPorts))))return'Connecting to privileged ports is not allowed.';}$g=new
Db;return($g->attach($tj,$V,$E)?:$g);}static
function
disconnect(){}function
__construct(Db$g){$this->conn=$g;}function
types(){return
call_user_func_array('array_merge',array_values($this->types));}function
structuredTypes(){return
array_map('array_keys',$this->types);}function
enumLength(array$n){}function
unconvertFunction(array$n){}function
select($R,array$M,array$Z,array$Me,array$Ki=[],$z=1,$D=0,$Wj=false){$bg=(count($Me)<count($M));$G=adminer()->selectQueryBuild($M,$Z,$Me,$Ki,$z,$D);if(!$G)$G="SELECT".limit(($_GET["page"]!="last"&&$z&&$Me&&$bg&&JUSH=="sql"?"SQL_CALC_FOUND_ROWS ":"").implode(", ",$M)."\nFROM ".table($R),($Z?"\nWHERE ".implode(" AND ",$Z):"").($Me&&$bg?"\nGROUP BY ".implode(", ",$Me):"").($Ki?"\nORDER BY ".implode(", ",$Ki):""),$z,($D?$z*$D:0),"\n");$this->query=$G;$Zl=microtime(true);$I=$this->conn->query($G,(!$z&&!$Wj?1:0));if($Wj)echo
adminer()->selectQuery($G,$Zl,!$I);return$I;}function
delete($R,$ik,$z=0){$G="FROM ".table($R);return
queries("DELETE".($z?limit1($R,$G,$ik):" $G$ik"));}function
update($R,array$O,$ik,$z=0,$ol="\n"){$bo=[];foreach($O
as$x=>$X)$bo[]="$x = $X";$G=table($R)." SET$ol".implode(",$ol",$bo);return
queries("UPDATE".($z?limit1($R,$G,$ik,$ol):" $G$ik"));}function
insert($R,array$O){return
queries("INSERT INTO ".table($R).($O?" (".implode(", ",array_keys($O)).")\nVALUES (".implode(", ",$O).")":" DEFAULT VALUES").$this->insertReturning($R));}function
insertReturning($R){return"";}function
insertUpdate($R,array$K,array$Uj){foreach($K
as$O){$Z=[];foreach($O
as$x=>$X){if(isset($Uj[idf_unescape($x)]))$Z[]="$x = $X";}if(!($Z&&$this->update($R,$O," WHERE ".implode(" AND ",$Z))&&$this->conn->affected_rows)&&!$this->insert($R,$O))return
false;}return
true;}function
begin(){remember_query("BEGIN");return$this->conn->begin();}function
commit(){remember_query("COMMIT");return$this->conn->commit();}function
rollback(){remember_query("ROLLBACK");return$this->conn->rollback();}function
slowQuery($G,$Sm){}function
operators($rm){return[];}function
convertSearch($u,array$X,array$n){return$u;}function
value($X,array$n){return(method_exists($this->conn,'value')?$this->conn->value($X,$n):$X);}function
quoteBinary($Wk){return
q($Wk);}function
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
supportsAlterTable(array$rm){return
true;}function
indexAlgorithms(array$rm){return[];}function
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
attach(array$N,$V,$E){$k=adminer()->database();$Hj=$N["port"];$of=($N["host"]?:$N["socket"]);$this->string="host='$of'".($Hj?" port=$Hj":"")." user='".addcslashes($V,"'\\")."' password='".addcslashes($E,"'\\")."'";$Yl=adminer()->connectSsl();if(isset($Yl["mode"]))$this->string
.=" sslmode=$Yl[mode]";$Cd=php_errors(function()use($k){$this->link=pg_connect("$this->string dbname='".($k!=""?addcslashes($k,"'\\"):"postgres")."'",PGSQL_CONNECT_FORCE_NEW);if(!$this->link&&$k!="")$this->link=pg_connect("$this->string dbname='postgres'",PGSQL_CONNECT_FORCE_NEW);});if($this->link){adminer()->executeQuery("SET client_encoding TO 'UTF8'",function(){return!pg_set_client_encoding($this->link,"UTF8");});}return($this->link?'':(string)end($Cd));}function
quote($Q){return
pg_escape_literal($this->link,$Q);}function
value($X,array$n){return($n["type"]=="bytea"&&$X!==null?pg_unescape_bytea($X):$X);}function
select_db($uc){if($uc==pg_dbname($this->link))return
true;$Cd=php_errors(function()use($uc,&$I){$I=pg_connect("$this->string dbname='".addcslashes($uc,"'\\")."'",PGSQL_CONNECT_FORCE_NEW);});if($I)$this->link=$I;elseif($Cd)$this->error=end($Cd);return$I;}function
close(){$this->link=@pg_connect("$this->string dbname='postgres'");}function
query($G,$xn=false){if(self::$untrusted)$H=($this->execute("BEGIN READ ONLY")?$this->execute($G,[]):false);else$H=$this->execute($G);$this->error="";if(!$H){$this->error=pg_last_error($this->link);$I=false;}elseif(!pg_num_fields($H)&&pg_result_status($H)!=PGSQL_TUPLES_OK){$this->affected_rows=pg_affected_rows($H);$I=true;}else$I=new
Result($H);if(self::$untrusted)$this->execute("COMMIT");if($this->timeout){$this->timeout=0;$this->query("RESET statement_timeout");}return$I;}private
function
execute($G,$gj=null){return
adminer()->executeQuery($G,function()use($G,$gj){return($gj===null?@pg_query($this->link,$G):@pg_query_params($this->link,$G,$gj));});}function
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
attach(array$N,$V,$E){$k=adminer()->database();$Hj=$N["port"];$of=($N["host"]?:$N["socket"]);$this->string="pgsql:host='$of'".($Hj?" port=$Hj":"")." client_encoding=utf8";$Yl=adminer()->connectSsl();if(isset($Yl["mode"]))$this->string
.=" sslmode=$Yl[mode]";$this->username=$V;$this->password=$E;$this->database=($k!=""?$k:"postgres");return$this->dsn("$this->string dbname='".addcslashes($this->database,"'\\")."'",$V,$E);}function
select_db($uc){if($uc!=$this->database){$m=$this->dsn("$this->string dbname='".addcslashes($uc,"'\\")."'",$this->username,$this->password);if($m!=""){$this->error=$m;return
false;}$this->database=$uc;}return
true;}function
query($G,$xn=false){$I=(self::$untrusted?$this->readOnlyQuery($G):parent::query($G,$xn));if($this->timeout){$this->timeout=0;parent::query("RESET statement_timeout");}return$I;}private
function
readOnlyQuery($G){$this->error="";if(!$this->execute("BEGIN READ ONLY"))return
false;$I=$this->execute($G,[]);if($I)$this->store_result($I);$this->execute("COMMIT");return$I;}private
function
execute($G,$gj=null){return
adminer()->executeQuery($G,function()use($G,$gj){$H=($gj===null?$this->pdo->query($G):$this->pdo->prepare($G));if($H&&($gj===null||$H->execute($gj)))return$H;list(,$this->errno,$this->error)=($H?$H->errorInfo():$this->pdo->errorInfo());if(!$this->error)$this->error='Unknown error.';return
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
operators($rm){return["=","<",">","<=",">=","!=","~","~*","!~","LIKE","LIKE %%","ILIKE","ILIKE %%","IN","BETWEEN","IS NULL","NOT LIKE","NOT ILIKE","NOT IN","IS NOT NULL","SQL"];}static
function
connect($N,$V,$E){$g=parent::connect($N,$V,$E);if(is_string($g))return$g;$eo=get_val("SELECT version()",0,$g);$g->flavor=(preg_match('~CockroachDB~',$eo)?'cockroach':'');$g->server_info=preg_replace('~^\D*([\d.]+[-\w]*).*~','\1',$eo);if(min_version(9,0,$g))$g->query("SET application_name = 'Adminer'");if($g->flavor=='cockroach')add_driver(DRIVER,"CockroachDB");return$g;}function
__construct(Db$g){parent::__construct($g);$this->types=['Numbers'=>["smallint"=>5,"integer"=>10,"bigint"=>19,"boolean"=>1,"numeric"=>0,"real"=>7,"double precision"=>16,"money"=>20],'Date and time'=>["date"=>10,"time"=>8,"timestamp"=>19,"timestamptz"=>25,"interval"=>0],'Strings'=>["character"=>0,"character varying"=>0,"text"=>0,"tsquery"=>0,"tsvector"=>0,"uuid"=>0,"xml"=>0],'Binary'=>["bit"=>0,"bit varying"=>0,"bytea"=>0],'Network'=>["cidr"=>43,"inet"=>43,"macaddr"=>17,"macaddr8"=>23,"txid_snapshot"=>0],'Geometry'=>["box"=>0,"circle"=>0,"line"=>0,"lseg"=>0,"path"=>0,"point"=>0,"polygon"=>0],];if(min_version(9.2,0,$g)){$this->types['Strings']["json"]=4294967295;$this->types['Ranges']=["int4range"=>0,"int8range"=>0,"numrange"=>0,"daterange"=>0,"tsrange"=>0,"tstzrange"=>0];if(min_version(9.4,0,$g))$this->types['Strings']["jsonb"]=4294967295;}$this->insertFunctions=["char"=>"md5","date|time"=>"now",];$this->editFunctions=[number_type()=>"+/-","date|time"=>"+ interval/- interval","char|text"=>"||",];if(min_version(12,0,$g)){$this->generated[]="STORED";if(min_version(18,0,$g))$this->generated[]="VIRTUAL";}$this->partitionBy=["RANGE","LIST"];if(!$g->flavor)$this->partitionBy[]="HASH";}function
enumLength(array$n){$pi=$this->userTypes[$n["type"]];return($pi&&!preg_match('~]$~',$n["length"])?type_values($pi):"");}function
setUserTypes(array$wn){$this->userTypes=array_flip($wn);$this->types['User types']=array_fill_keys(array_keys($this->userTypes),0);}function
insertReturning($R){$La=array_filter(fields($R),function($n){return$n['auto_increment'];});return(count($La)==1?" RETURNING ".idf_escape(key($La)):"");}function
insertUpdate($R,array$K,array$Uj){$e=array_keys(reset($K));$Rb=[];$Hn=[];foreach($e
as$x){if(isset($Uj[idf_unescape($x)]))$Rb[]=$x;else$Hn[]="$x = EXCLUDED.$x";}if(!$Rb||!min_version(9.5)||count($Rb)!=count($Uj))return
parent::insertUpdate($R,$K,$Uj);$Pj="INSERT INTO ".table($R)." (".implode(", ",$e).") VALUES\n";$jm="\nON CONFLICT (".implode(", ",$Rb).")".($Hn?" DO UPDATE SET ".implode(", ",$Hn):" DO NOTHING");$bo=[];$y=0;foreach($K
as$O){$Y="(".implode(", ",$O).")";if($bo&&strlen($Pj)+$y+strlen($Y)+strlen($jm)>1e6){if(!queries($Pj.implode(",\n",$bo).$jm))return
false;$bo=[];$y=0;}$bo[]=$Y;$y+=strlen($Y)+2;}return
queries($Pj.implode(",\n",$bo).$jm);}function
slowQuery($G,$Sm){$this->conn->query("SET statement_timeout = ".(1000*$Sm));$this->conn->timeout=1000*$Sm;return$G;}function
convertSearch($u,array$X,array$n){$zj=preg_match('(LIKE|^!?~)',$X["op"]);$Oh=preg_match('~^(character( varying)?|text|citext|bpchar|name)$~',$n["type"])||(!$zj&&preg_match('~'.number_type().'|^(date|time|timetz|timestamp|timestamptz|boolean)$~',$n["type"]));return($Oh&&!preg_match('~\[]$~',$n["full_type"])?$u:"CAST($u AS text)");}function
quoteBinary($Wk){return"decode('".bin2hex($Wk)."', 'hex')";}function
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
indexAlgorithms(array$rm){static$I=[];if(!$I)$I=get_vals("SELECT amname FROM pg_am".(min_version(9.6)?" WHERE amtype = 'i'":"")." ORDER BY amname = '".($this->conn->flavor=='cockroach'?"prefix":"btree")."' DESC, amname");return$I;}function
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
limit($G,$Z,$z,$C=0,$ol=" "){return" $G$Z".($z?$ol."LIMIT $z".($C?" OFFSET $C":""):"");}function
limit1($R,$G,$Z,$ol="\n"){return(preg_match('~^INTO~',$G)?limit($G,$Z,1,0,$ol):" $G".(is_view(table_status1($R))?$Z:$ol."WHERE (tableoid, ctid) = (SELECT tableoid, ctid FROM ".table($R).$Z.$ol."LIMIT 1)"));}function
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
table_status($B="",$Wd=false){static$af;if($af===null)$af=get_val("SELECT 'pg_table_size'::regproc");$sl=(!$Wd&&min_version(10));$I=[];foreach(get_rows("SELECT
	relname AS \"Name\",
	CASE relkind WHEN 'v' THEN 'view' WHEN 'm' THEN 'materialized view' ELSE 'table' END AS \"Engine\"".($af?",
	pg_table_size(c.oid) AS \"Data_length\",
	pg_indexes_size(c.oid) AS \"Index_length\"":"").",
	obj_description(c.oid, 'pg_class') AS \"Comment\",
	".(min_version(12)?"''":"CASE WHEN relhasoids THEN 'oid' ELSE '' END")." AS \"Oid\",
	reltuples AS \"Rows\",
	EXISTS (SELECT 1 FROM pg_index WHERE indrelid = c.oid AND NOT indisvalid)::int AS invalid_index,
	".($sl?"seq.last_value":"NULL")." AS \"Auto_increment\"".(min_version(10)?",
	relispartition::int AS dependent":"")."
FROM pg_class c
".($sl?"LEFT JOIN (
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
parse_full_type(array&$J){static$va=['timestamp without time zone'=>'timestamp','timestamp with time zone'=>'timestamptz','time without time zone'=>'time','time with time zone'=>'timetz',];preg_match('~([^([]+)(\((.*)\))?([a-z ]+)?((\[[0-9]*])*)$~',$J["full_type"],$A);list(,$U,$y,$J["length"],$ma,$Ea)=$A;$J["length"].=$Ea;$tb=$U.$ma;if(isset($va[$tb])){$J["type"]=$va[$tb];$J["full_type"]=$J["type"].$y.$Ea;}else{$yn=idf_unescape($U);$J["type"]=(is_user_type($yn)?$yn:$U);$J["full_type"]=$J["type"].$y.$ma.$Ea;}}function
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
indexes($R,$h=null){$h=connection($h);$I=[];$ym=driver()->tableOid($R);$e=get_key_vals("SELECT attnum, attname FROM pg_attribute WHERE attrelid = $ym AND attnum > 0",$h);foreach(get_rows("SELECT relname, indisunique::int, indisprimary::int, indisvalid::int, indkey, indoption, amname,
	pg_get_expr(indpred, indrelid, true) AS partial, pg_get_expr(indexprs, indrelid) AS indexpr".($h->flavor=='cockroach'?"":",
	(SELECT string_agg(CASE WHEN opcdefault THEN '' ELSE opcname END, ' ' ORDER BY s)
		FROM generate_subscripts(indclass, 1) AS s JOIN pg_catalog.pg_opclass ON pg_opclass.oid = indclass[s]) AS opclasses")."
FROM pg_index
JOIN pg_class ON indexrelid = oid
JOIN pg_am ON pg_am.oid = pg_class.relam
WHERE indrelid = $ym
ORDER BY indisprimary DESC, indisunique DESC",$h)as$J){$_k=$J["relname"];$I[$_k]["type"]=($J["indisprimary"]?"PRIMARY":($J["indisunique"]?"UNIQUE":"INDEX"));$I[$_k]["columns"]=[];$I[$_k]["descs"]=[];$I[$_k]["algorithm"]=$J["amname"];$I[$_k]["partial"]=$J["partial"];$I[$_k]["invalid"]=!$J["indisvalid"];$Hf=preg_split('~(?<=\)), (?=\()~',$J["indexpr"]);foreach(explode(" ",$J["indkey"])as$If)$I[$_k]["columns"][]=($If?$e[$If]:array_shift($Hf));foreach(explode(" ",$J["indoption"])as$Jf)$I[$_k]["descs"][]=(intval($Jf)&1?'1':null);$I[$_k]["opclasses"]=($J["opclasses"]!=""?explode(" ",$J["opclasses"]):[]);$I[$_k]["lengths"]=[];}return$I;}function
foreign_keys($R){$I=[];foreach(get_rows("SELECT conname, condeferrable::int AS deferrable, condeferred::int AS deferred, pg_get_constraintdef(oid) AS definition
FROM pg_constraint
WHERE conrelid = ".driver()->tableOid($R)."
AND contype = 'f'::char
ORDER BY conkey, conname")as$J){$J['deferrable']=($J['deferrable']?'':'NOT ').'DEFERRABLE'.($J['deferred']?' INITIALLY DEFERRED':'');if(preg_match('~FOREIGN KEY\s*\((.+)\)\s*REFERENCES (.+)\((.+)\)(.*)$~iA',$J['definition'],$A)){$J['source']=array_map('Adminer\idf_unescape',array_map('trim',explode(',',$A[1])));if(preg_match('~^(("([^"]|"")+"|[^"]+)\.)?"?("([^"]|"")+"|[^"]+)$~',$A[2],$Tg)){$J['ns']=idf_unescape($Tg[2]);$J['table']=idf_unescape($Tg[4]);}$J['target']=array_map('Adminer\idf_unescape',array_map('trim',explode(',',$A[3])));$J['on_delete']=(preg_match("~ON DELETE (".driver()->onActions.")~",$A[4],$Tg)?$Tg[1]:'NO ACTION');$J['on_update']=(preg_match("~ON UPDATE (".driver()->onActions.")~",$A[4],$Tg)?$Tg[1]:'NO ACTION');$I[$J['conname']]=$J;}}return$I;}function
view($B){return["select"=>trim(get_val("SELECT pg_get_viewdef(".driver()->tableOid($B).")"))];}function
collations(){return(isset($_GET["database"])&&DB==""&&connection()->flavor!='cockroach'&&min_version(13)?get_vals("SELECT DISTINCT collcollate FROM pg_collation WHERE collprovider = 'c' ORDER BY 1"):[]);}function
information_schema($k,$L=""){$pm=["information_schema","pg_catalog","pg_toast"];if(connection()->flavor=='cockroach'){$pm[]="crdb_internal";$pm[]="pg_extension";}return
in_array($L!=""?$L:get_schema(),$pm);}function
error(){$I=h(connection()->error);if(preg_match('~^(.*\n)?([^\n]*)\n( *)\^(\n.*)?$~s',$I,$A))$I=$A[1].preg_replace('~((?:[^&]|&[^;]*;){'.strlen($A[3]).'})(.*)~','\1<b>\2</b>',$A[2]).$A[4];return
nl_br($I);}function
create_database($k,$Fb){return
queries("CREATE DATABASE ".idf_escape($k).($Fb?" TEMPLATE template0 LOCALE ".q($Fb):""));}function
drop_databases(array$j){connection()->close();return
apply_queries("DROP DATABASE",$j,'Adminer\idf_escape');}function
rename_database($B,$Fb){connection()->close();return!!queries("ALTER DATABASE ".idf_escape(DB)." RENAME TO ".idf_escape($B));}function
auto_increment(){return"";}function
alter_table($R,$B,array$o,array$se,$f,$vd,$Fb,$La,$qj){$b=[];$hk=[];if($R!=""&&$R!=$B)$hk[]="ALTER TABLE ".table($R)." RENAME TO ".table($B);$pl="";$Si=($R!=""?fields($R):[]);foreach($o
as$n){$d=idf_escape($n[0]);$X=$n[1];if(!$X)$b[]="DROP $d";else{$Ri=$Si[$n[0]];$Pi=($Ri?process_field($Ri,$Ri):[]);$Yn=$X[5];unset($X[5]);if($n[0]==""){if(isset($X[6]))$X[1]=($X[1]==" bigint"?" big":($X[1]==" smallint"?" small":" "))."serial";$b[]=($R!=""?"ADD ":"  ").implode($X);if(isset($X[6]))$b[]=($R!=""?"ADD":" ")." PRIMARY KEY ($X[0])";}else{if($d!=$X[0])$hk[]="ALTER TABLE ".table($B)." RENAME $d TO $X[0]";if($X[1]!=$Pi[1])$b[]="ALTER $d TYPE$X[1]";if($X[3]!=$Pi[3]||isset($X[6])!=isset($Pi[6])){$ql=$R."_".idf_unescape($X[0])."_seq";$b[]="ALTER $d ".($X[3]?"SET".preg_replace('~GENERATED ALWAYS(.*) (STORED|VIRTUAL)~','EXPRESSION\1',$X[3]):(isset($X[6])?"SET DEFAULT nextval(".q($ql).")":"DROP DEFAULT"));if(isset($X[6]))$pl="CREATE SEQUENCE IF NOT EXISTS ".idf_escape($ql)." OWNED BY ".idf_escape($R).".$X[0]";}if($X[2]!=$Pi[2])$b[]="ALTER $d ".($X[2]==" NULL"?"DROP NOT":"SET").$X[2];}if($Yn!=$Pi[5])$hk[]="COMMENT ON COLUMN ".table($B).".$X[0] IS ".($Yn!=""?substr($Yn,9):"''");}}if($R==""){$b=array_merge($b,$se);$P="";if($qj){$Bb=(connection()->flavor=='cockroach');$P=" PARTITION BY $qj[partition_by]($qj[partition])";if($qj["partition_by"]=='HASH'){$rj=+$qj["partitions"];for($s=0;$s<$rj;$s++)$hk[]="CREATE TABLE ".idf_escape($B."_$s")." PARTITION OF ".idf_escape($B)." FOR VALUES WITH (MODULUS $rj, REMAINDER $s)";}else{$Rj="MINVALUE";foreach($qj["partition_names"]as$s=>$X){$Y=$qj["partition_values"][$s];$lj=" VALUES ".($qj["partition_by"]=='LIST'?"IN ($Y)":"FROM ($Rj) TO ($Y)");if($Bb)$P
.=($s?",":" (")."\n  PARTITION ".(preg_match('~^DEFAULT$~i',$X)?$X:idf_escape($X))."$lj";else$hk[]="CREATE TABLE ".idf_escape($B."_$X")." PARTITION OF ".idf_escape($B)." FOR$lj";$Rj=$Y;}$P
.=($Bb?"\n)":"");}}array_unshift($hk,"CREATE TABLE ".table($B)." (\n".implode(",\n",$b)."\n)$P");}else{if($b)array_unshift($hk,"ALTER TABLE ".table($R)."\n".implode(",\n",$b));if($se)$hk[]="ALTER TABLE ".table($B)."\n".implode(",\n",$se);}if($pl)array_unshift($hk,$pl);if($f!==null)$hk[]="COMMENT ON TABLE ".table($B)." IS ".q($f);foreach($hk
as$G){if(!queries($G))return
false;}if($La!=""){foreach(fields($B)as$ae=>$n){if($n["auto_increment"])return!!queries("SELECT setval(pg_get_serial_sequence(".q(table($B)).", ".q($ae)."), $La)");}}return
true;}function
alter_indexes($R,$b){$i=[];$cd=[];$hk=[];$Pb=(support("concurrent_indexes")&&$_POST["concurrently"]?" CONCURRENTLY":"");foreach($b
as$X){if($X[0]=="UNIQUE"&&$X[2]=="DROP"&&!get_val("SELECT 1 FROM pg_constraint JOIN pg_class ON conindid = pg_class.oid WHERE conrelid = ".driver()->tableOid($R)." AND relname = ".q($X[1])))$X[0]="INDEX";if($X[0]!="INDEX")$i[]=($X[2]=="DROP"?"\nDROP CONSTRAINT ".idf_escape($X[1]):"\nADD".($X[1]!=""?" CONSTRAINT ".idf_escape($X[1]):"")." $X[0] ".($X[0]=="PRIMARY"?"KEY ":"")."(".implode(", ",$X[2]).")");elseif($X[2]=="DROP")$cd[]=idf_escape($X[1]);else$hk[]="CREATE INDEX$Pb ".idf_escape($X[1]!=""?$X[1]:uniqid($R."_"))." ON ".table($R).($X[3]?" USING $X[3]":"")." (".implode(", ",$X[2]).")".($X[4]?" WHERE $X[4]":"");}if($i)array_unshift($hk,"ALTER TABLE ".table($R).implode(",",$i));if($cd){$hk=array_merge(($Pb?array_map(function($u){return"DROP INDEX CONCURRENTLY $u";},$cd):["DROP INDEX ".implode(", ",$cd)]),$hk);}foreach($hk
as$G){if(!queries($G))return
false;}return
true;}function
truncate_tables(array$T){return!!queries("TRUNCATE ".implode(", ",array_map('Adminer\table',$T)));}function
drop_kinds(array$T){$I=["MATERIALIZED VIEW"=>[],"VIEW"=>[],"TABLE"=>[]];foreach($T
as$B=>$S)$I[strtoupper($S["Engine"])][]=table($B);return
array_filter($I);}function
drop_views(array$go){return
drop_tables($go);}function
drop_tables(array$T){$dm=[];foreach($T
as$R)$dm[$R]=table_status1($R);foreach(drop_kinds($dm)as$qg=>$Nh){if(!queries("DROP $qg ".implode(", ",$Nh)))return
false;}return
true;}function
move_tables(array$T,array$go,$Im){foreach(array_merge($T,$go)as$R){$P=table_status1($R);if(!queries("ALTER ".strtoupper($P["Engine"])." ".table($R)." SET SCHEMA ".idf_escape($Im)))return
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
)')as$J)$Ge[array_shift($J)]=$J;foreach(get_rows("SELECT * FROM information_schema.triggers WHERE trigger_schema = current_schema() AND event_object_table = ".q($R))as$J){$ln=trigger($J["trigger_name"],$R);$I[$ln["Trigger"]]=[$ln["Timing"],$ln["Event"]];if($Ge[$ln["Trigger"]])$I[$ln["Trigger"]][]=$Ge[$ln["Trigger"]];}return$I;}function
trigger_options(){return["Timing"=>["BEFORE","AFTER"],"Event"=>["INSERT","UPDATE","UPDATE OF","DELETE","INSERT OR UPDATE","INSERT OR UPDATE OF","DELETE OR INSERT","DELETE OR UPDATE","DELETE OR UPDATE OF","DELETE OR INSERT OR UPDATE","DELETE OR INSERT OR UPDATE OF",],"Type"=>["FOR EACH ROW","FOR EACH STATEMENT"],];}function
routine($B,$U){$Hi=routine_options($U);$ll=array_intersect_key(["VOLATILITY"=>"CASE p.provolatile WHEN 'i' THEN 'IMMUTABLE' WHEN 's' THEN 'STABLE' ELSE 'VOLATILE' END","NULL_INPUT"=>"CASE WHEN p.proisstrict THEN 'RETURNS NULL ON NULL INPUT' ELSE 'CALLED ON NULL INPUT' END","SECURITY"=>"CASE WHEN p.prosecdef THEN 'SECURITY DEFINER' ELSE 'SECURITY INVOKER' END","PARALLEL"=>"CASE p.proparallel WHEN 's' THEN 'PARALLEL SAFE' WHEN 'r' THEN 'PARALLEL RESTRICTED' ELSE 'PARALLEL UNSAFE' END",],$Hi);foreach($ll
as$x=>$M)$ll[$x]="$M AS \"$x\"";$K=get_rows('SELECT r.routine_definition AS definition, LOWER(r.external_language) AS language, '.($ll?implode(', ',$ll).', ':'').'r.*
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
routine_options($Nk){$Bb=(connection()->flavor=='cockroach');$gl=($Bb?[]:["SECURITY"=>["SECURITY INVOKER","SECURITY DEFINER"]]);if($Nk=="PROCEDURE")return$gl;return["VOLATILITY"=>["VOLATILE","STABLE","IMMUTABLE"],"NULL_INPUT"=>["CALLED ON NULL INPUT","RETURNS NULL ON NULL INPUT"],]+$gl+($Bb?[]:["PARALLEL"=>["PARALLEL UNSAFE","PARALLEL RESTRICTED","PARALLEL SAFE"],]);}function
routine_id($B,array$J){$I=[];foreach($J["fields"]as$n){$y=$n["length"];$I[]=$n["type"].($y?"($y)":"");}return
idf_escape($B)."(".implode(", ",$I).")";}function
last_id($H){$J=(is_object($H)?$H->fetch_row():[]);return($J?$J[0]:0);}function
explain(Db$g,$G){return$g->query("EXPLAIN $G");}function
found_rows(array$S,array$Z){if(preg_match("~ rows=([0-9]+)~",get_val("EXPLAIN SELECT * FROM ".idf_escape($S["Name"]).($Z?" WHERE ".implode(" AND ",$Z):"")),$zk))return$zk[1];}function
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
type_definition($t){$U=first(get_rows("SELECT typtype, typisdefined::int AS defined, typrelid FROM pg_type WHERE oid = $t"));$I=["kind"=>($U?$U["typtype"]:""),"definition"=>""];if(!$U||!$U["defined"])return$I;switch($I["kind"]){case'e':$bo=get_vals("SELECT enumlabel FROM pg_enum WHERE enumtypid = $t ORDER BY enumsortorder");$I["definition"]="AS ENUM (".implode(", ",array_map('Adminer\q',$bo)).")";break;case'c':$e=[];foreach(get_rows("SELECT attname, format_type(atttypid, atttypmod) AS full_type, ".collation_name("attcollation")." AS collation
FROM pg_attribute
WHERE attrelid = $U[typrelid] AND attnum > 0 AND NOT attisdropped
ORDER BY attnum")as$J)$e[]=idf_escape($J["attname"])." $J[full_type]".($J["collation"]?" COLLATE ".idf_escape($J["collation"]):"");$I["definition"]="AS (\n\t".implode(",\n\t",$e)."\n)";break;case'd':$Zc=first(get_rows("SELECT format_type(typbasetype, typtypmod) AS base, typnotnull::int AS notnull, typdefault, ".collation_name("typcollation")." AS collation
FROM pg_type WHERE oid = $t"));$I["definition"]="AS $Zc[base]".($Zc["collation"]?" COLLATE ".idf_escape($Zc["collation"]):"").($Zc["typdefault"]!=""?" DEFAULT $Zc[typdefault]":"").($Zc["notnull"]?" NOT NULL":"");foreach(get_rows("SELECT conname, pg_get_constraintdef(oid) AS definition FROM pg_constraint WHERE contypid = $t AND contype != 'n' ORDER BY conname")as$J)$I["definition"].=" CONSTRAINT ".idf_escape($J["conname"])." $J[definition]";break;case'r':$ok=first(get_rows("SELECT format_type(rngsubtype, NULL) AS subtype,
(SELECT opcname FROM pg_opclass WHERE oid = rngsubopc) AS subtype_opclass,
".collation_name("rngcollation")." AS collation,
NULLIF(rngcanonical, 0)::regproc::text AS canonical,
NULLIF(rngsubdiff, 0)::regproc::text AS subtype_diff".(min_version(14)?",
(SELECT typname FROM pg_type WHERE oid = rngmultitypid) AS multirange_type_name":"")."
FROM pg_range WHERE rngtypid = $t"));$Hi=[];foreach(["subtype"=>0,"subtype_opclass"=>1,"collation"=>1,"canonical"=>0,"subtype_diff"=>0,"multirange_type_name"=>1]as$x=>$Ed){if($ok[$x]!="")$Hi[]=strtoupper($x)." = ".($Ed?idf_escape($ok[$x]):$ok[$x]);}$I["definition"]="AS RANGE (".implode(", ",$Hi).")";}return$I;}function
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
indexes_sql($R,$Uj=""){$I="";$G="SELECT indexdef, quote_ident(schemaname) || '.' || quote_ident(tablename) AS qualified, quote_ident(current_database()) AS db
FROM pg_catalog.pg_indexes
WHERE schemaname = current_schema() AND tablename = ".q($R).($Uj!=""?" AND indexname != ".q($Uj):"");foreach(get_rows($G,null,"-- ")as$J)$I
.="\n\n".str_replace([" $J[db].$J[qualified] USING "," $J[qualified] USING "]," ".table($R)." USING ",$J["indexdef"]).";";return$I;}function
create_sql($R,$La,$hm){$Ik=[];$sl=[];$tl=[];$rl=[];$P=table_status1($R);if(is_view($P)){$fo=view($R);$i="CREATE ".strtoupper($P["Engine"])." ".table($R)." AS ".rtrim($fo["select"],";").";";return
rtrim($i.indexes_sql($R),';');}$o=fields($R);if(count($P)<2||empty($o))return"";$I="CREATE TABLE ".table($P['Name'])." (\n    ";$wm=q(table($P['Name']));foreach($o
as$n){$ul="";if($n['default']=="nextval('$P[Name]_$n[field]_seq')"){$ul=table("$P[Name]_$n[field]_seq");$n['default']=null;$n['full_type']=preg_replace('~int(eger)?~','serial',$n['full_type']);}$jj=idf_escape($n['field']).' '.full_type_sql($n).preg_replace_callback('~(nextval\(\')([^.\']+)\'~',function(array$A){return$A[1].str_replace("'","''",table(idf_unescape($A[2])))."'";},default_value($n)).($n['null']?"":" NOT NULL");$Ik[]=$jj;if(preg_match('~nextval\(\'([^\']+)\'\)~',$n['default'],$Ug)){$ql=$Ug[1];$Rl=first(get_rows((min_version(10)?"SELECT *, cache_size AS cache_value FROM pg_sequences WHERE schemaname = current_schema() AND sequencename = ".q(idf_unescape($ql)):"SELECT * FROM $ql"),null,"-- "));$pl=table(idf_unescape($ql));$sl[]=($hm=="DROP+CREATE"?"DROP SEQUENCE IF EXISTS $pl;\n":"")."CREATE SEQUENCE $pl INCREMENT $Rl[increment_by] MINVALUE $Rl[min_value] MAXVALUE $Rl[max_value]"." CACHE $Rl[cache_value];";if(get_val("SELECT pg_get_serial_sequence($wm, ".q($n['field']).")"))$tl[]="\n\nALTER SEQUENCE $pl OWNED BY ".table($P['Name']).".".idf_escape($n['field']).";";if($La)$rl[]=$pl;}elseif($La&&$n['auto_increment']){$pl=($ul?"":get_val("SELECT pg_get_serial_sequence($wm, ".q($n['field']).")::regclass"));$rl[]=($pl?table(idf_unescape($pl)):$ul);}}if(!empty($sl))$I=implode("\n\n",$sl)."\n\n$I";$Uj="";foreach(indexes($R)as$Ff=>$v){if($v['type']=='PRIMARY'){$Uj=$Ff;$Ik[]="CONSTRAINT ".idf_escape($Ff)." PRIMARY KEY (".implode(', ',array_map('Adminer\idf_escape',$v['columns'])).")";}}foreach(driver()->checkConstraints($R)as$Tb=>$Vb)$Ik[]="CONSTRAINT ".idf_escape($Tb)." CHECK ($Vb)";$I
.=implode(",\n    ",$Ik)."\n)";$lj=driver()->partitionsInfo($P['Name']);if($lj)$I
.="\nPARTITION BY $lj[partition_by]($lj[partition])";$I
.=(min_version(12)?"":"\nWITH (oids = ".($P['Oid']?'true':'false').")").";";$I
.=implode($tl);if($P['Comment'])$I
.="\n\nCOMMENT ON TABLE ".table($P['Name'])." IS ".q($P['Comment']).";";foreach($o
as$ae=>$n){if($n['comment'])$I
.="\n\nCOMMENT ON COLUMN ".table($P['Name']).".".idf_escape($ae)." IS ".q($n['comment']).";";}$I
.=indexes_sql($R,$Uj);foreach(array_filter($rl)as$pl){$Rl=first(get_rows("SELECT last_value, is_called::int FROM $pl",null,"-- "));if($Rl['is_called'])$I
.="\n\nDO \$\$ BEGIN PERFORM setval(".q($pl).", $Rl[last_value]); END \$\$;";}return
rtrim($I,';');}function
truncate_sql($R){return"TRUNCATE ".table($R);}function
truncate_all_sql(array$T){return($T?"TRUNCATE ".implode(", ",array_map('Adminer\table',$T)).";\n\n":"");}function
trigger_sql($R){$P=table_status1($R);$I="";foreach(triggers($R)as$kn=>$jn){$ln=trigger($kn,$P['Name']);$I
.="\nCREATE TRIGGER ".idf_escape($ln['Trigger'])." $ln[Timing] $ln[Event] ON ".table($P['Name'])." $ln[Type] $ln[Statement];;\n";}return$I;}function
use_sql($uc,$hm=""){$B=idf_escape($uc);$I="";if(preg_match('~CREATE~',$hm)){if($hm=="DROP+CREATE")$I="DROP DATABASE IF EXISTS $B;\n";$I
.="CREATE DATABASE $B;\n";}return"$I\\connect $B";}function
use_schema_sql($L,$hm){$B=idf_escape($L);$I="";if(preg_match('~CREATE~',$hm)){if($hm=="DROP+CREATE")$I="DROP SCHEMA IF EXISTS $B CASCADE;\n";$I
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
\SQLite3($N["path"]);if(method_exists($this->link,'setAuthorizer'))$this->link->setAuthorizer([$this,'authorize']);$eo=\SQLite3::version();$this->server_info=$eo["versionString"];return'';}function
query($G,$xn=false){$H=adminer()->executeQuery($G,function()use($G){return@$this->link->query($G);});$this->error="";if(!$H){$this->errno=$this->link->lastErrorCode();$this->error=$this->link->lastErrorMsg();return
false;}elseif($H->numColumns())return
new
Result($H);$this->affected_rows=$this->link->changes();return
true;}function
quote($Q){return(is_utf8($Q)?"'".$this->link->escapeString($Q)."'":"x'".bin2hex($Q)."'");}}class
Result{var$num_rows;private$result,$offset=0;function
__construct($H){$this->result=$H;}function
fetch_assoc(){return$this->result->fetchArray(SQLITE3_ASSOC);}function
fetch_row(){return$this->result->fetchArray(SQLITE3_NUM);}function
fetch_field(){$wn=[1=>"integer","real","text","blob","null"];$d=$this->offset++;return(object)["name"=>$this->result->columnName($d),"native_type"=>$wn[$this->result->columnType($d)],];}}}elseif(extension_loaded("pdo_sqlite")){abstract
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
query($G,$xn=false){$I=parent::query($G,$xn);if($this->affected_rows){$H=parent::query("SELECT total_changes()");$J=($H?$H->fetch_row():false);if($J){if($J[0]==$this->total_changes)$this->affected_rows=0;$this->total_changes=$J[0];}}return$I;}function
select_db($p){$G="ATTACH ".$this->quote(preg_match("~(^[/\\\\]|:)~",$p)?$p:dirname($_SERVER["SCRIPT_FILENAME"])."/$p")." AS a";$this->attaching=true;$Ia=is_readable($p)&&$this->query($G);$this->attaching=false;if($Ia)return!self::attach(server_parts(["path"=>$p]),'','');return
false;}function
authorize($ia,$Ca){return($ia!=24||$Ca===''||$this->attaching?0:1);}}}class
Driver
extends
SqlDriver{static$extensions=["SQLite3","PDO_SQLite"];static$jush="sqlite";static$passwords=false;static$serverFile=true;protected$types=[["integer"=>0,"real"=>0,"numeric"=>0,"text"=>0,"blob"=>0]];var$insertFunctions=["date"=>"current_date","time"=>"current_timestamp",];var$editFunctions=["^(?!.*(char|clob|text|blob))."=>"+/-","char|clob|text"=>"||",];var$fulltextOperator="MATCH";var$functions=["hex","length","lower","round","unixepoch","upper"];var$grouping=["avg","count","count distinct","group_concat","max","min","sum"];function
operators($rm){$I=["=","<",">","<=",">=","!=","&","LIKE","LIKE %%","IN","BETWEEN","IS NULL","NOT LIKE","NOT IN","IS NOT NULL"];if(preg_match('~^fts\d+$~i',(string)idx($rm,"Engine")))$I[]="MATCH";$I[]="SQL";return$I;}static
function
connect($N,$V,$E){return
parent::connect(":memory:","","");}function
__construct(Db$g){parent::__construct($g);if(min_version(3.31,0,$g))$this->generated=["STORED","VIRTUAL"];if(min_version(3.37,0,$g))$this->types[0]["any"]=0;}function
structuredTypes(){return
array_keys($this->types[0]);}function
quoteBinary($Wk){return"x".q(bin2hex($Wk));}function
typeName(\stdClass$n){$I=strtolower(idx((array)$n,'sqlite:decl_type',parent::typeName($n)));return
idx(["string"=>"text","double"=>"real"],$I,$I);}function
engines(){$I=["table"];if(min_version("3.8.2")){if(min_version(3.37)){$I[]="STRICT";$I[]="STRICT, WITHOUT ROWID";}$I[]="WITHOUT ROWID";}return$I;}private
function
isVirtual(array$S){$vd=$S["Engine"];return$vd!=""&&!in_array($vd,array_merge(["view"],$this->engines()));}function
supportsIndex(array$S){return!is_view($S)&&!$this->isVirtual($S);}function
supportsAlterIndex(array$S){return$this->supportsIndex($S);}function
supportsAlterTable(array$rm){return!$this->isVirtual($rm);}function
shadowTables($R){$I=[];if(min_version(3.37)){foreach(get_vals("SELECT name FROM pragma_table_list WHERE schema = 'main' AND type = 'shadow' ORDER BY name")as$B){if(preg_match('(^'.preg_quote($R).'_[^_]*$)',$B))$I[]=["table"=>$B,"ns"=>""];}}return$I;}function
fulltextSql($B,array$v,$G,$ab){return
idf_escape($B)." MATCH ".q($G);}function
insertUpdate($R,array$K,array$Uj){$bo=[];foreach($K
as$O)$bo[]="(".implode(", ",$O).")";return
queries("REPLACE INTO ".table($R)." (".implode(", ",array_keys(reset($K))).") VALUES\n".implode(",\n",$bo));}function
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
limit($G,$Z,$z,$C=0,$ol=" "){return" $G$Z".($z?$ol."LIMIT $z".($C?" OFFSET $C":""):"");}function
limit1($R,$G,$Z,$ol="\n"){return(preg_match('~^INTO~',$G)||get_val("SELECT sqlite_compileoption_used('ENABLE_UPDATE_DELETE_LIMIT')")?limit($G,$Z,1,0,$ol):" $G WHERE rowid = (SELECT rowid FROM ".table($R).$Z.$ol."LIMIT 1)");}function
db_collation($k,array$Gb){return
get_val("PRAGMA encoding");}function
logged_user(){return
get_current_user();}function
virtual_module($Sl){return(preg_match('~^CREATE\s+VIRTUAL\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?(?:"[^"]*+"|`[^`]*+`|\[[^\]]*+\]|[^\s(]+)\s+USING\s+([a-z0-9_]+)~i',$Sl,$A)?$A[1]:"");}function
parse_comments($Sl){$I=[];if(virtual_module($Sl)!="")return$I;preg_match_all('~\'(?:[^\']|\'\')*\'|"(?:[^"]|"")*"|`(?:[^`]|``)*`|\[[^]]*]|--[^\n]*|/\*.*?\*/|[(),\n]|[^-/\'"`[(),\n]+|[-/]~s',$Sl,$Ug);$Hc=0;$Nh=[];$xg="";$ai=true;foreach($Ug[0]as$an){if(preg_match('~^--~',$an)){if($Hc==1&&!$ai){$B=($xg=="("?"":($xg==","?idx($Nh,count($Nh)-2):end($Nh)));if(is_string($B))$I[$B]=trim(substr($an,2));}}elseif($an=="\n")$ai=true;elseif(trim($an)!=""&&!preg_match('~^/\*~',$an)){$ai=false;$xg=($Hc==1&&$an==","?",":($Hc==0&&$an=="("?"(":""));if($xg)$Nh[]=false;elseif($Hc==1&&end($Nh)===false&&$an!="("&&$an!=")"){$mk=preg_match('~^[\'"`[]~',$an);$so=($mk?idf_unescape($an):preg_replace('~\s.*~s','',trim($an)));$Nh[count($Nh)-1]=($mk||!preg_match('~^(CONSTRAINT|PRIMARY|UNIQUE|CHECK|FOREIGN)$~i',$so)?$so:null);}$Hc+=($an=="("?1:($an==")"?-1:0));}}return$I;}function
tables_list(){return
get_key_vals("SELECT name, type FROM sqlite_master WHERE type IN ('table', 'view')".(min_version(3.37)?" AND name NOT IN (SELECT name FROM pragma_table_list WHERE type = 'shadow')":"")."
ORDER BY (name LIKE 'sqlite_%'), name");}function
count_tables(array$j){return[];}function
db_status(){$cj=get_val("PRAGMA page_size");$_e=get_val("PRAGMA freelist_count")*$cj;return["Data_length"=>get_val("PRAGMA page_count")*$cj-$_e,"Index_length"=>0,"Data_free"=>$_e,];}function
table_status($B="",$Wd=false){$I=[];$K=[];if(!$Wd&&$B==""){connection()->query("PRAGMA optimize = 0x10002");$K=get_key_vals("SELECT tbl, MAX(CAST(stat AS integer)) FROM sqlite_stat1 GROUP BY tbl");}foreach(get_rows("SELECT name AS Name, type AS Engine, sql, 'rowid' AS Oid, '' AS Auto_increment".(min_version(3.37)?", name IN (SELECT name FROM pragma_table_list WHERE type = 'shadow') AS dependent":"")." FROM sqlite_master WHERE type IN ('table', 'view') ".($B!=""?"AND name = ".q($B):"ORDER BY (name LIKE 'sqlite_%'), name"))as$J){if($J["Engine"]=="table"){$Sl=preg_replace('~(?:\s|--[^\n]*|/\*.*?\*/)+$~s','',$J["sql"]);$jm=preg_replace('~.*\)~s','',$Sl);$J["Engine"]=virtual_module($J["sql"])?:(implode(", ",array_filter([(preg_match('~\bSTRICT\b~i',$jm)?"STRICT":0),(preg_match('~\bWITHOUT\s+ROWID\b~i',$jm)?"WITHOUT ROWID":0),]))?:"table");$J["Comment"]=idx(parse_comments($J["sql"]),"","");}unset($J["sql"]);$J["Rows"]=idx($K,$J["Name"],0);$I[$J["Name"]]=$J;}if(!$Wd){foreach(get_rows("SELECT * FROM sqlite_sequence".($B!=""?" WHERE name = ".q($B):""),null,"")as$J)$I[$J["name"]]["Auto_increment"]=$J["seq"];}return$I;}function
is_view(array$S){return$S["Engine"]=="view";}function
fk_support(array$S){return!get_val("SELECT sqlite_compileoption_used('OMIT_FOREIGN_KEY')");}function
type_affinity($U){$U=strtolower($U);return(preg_match('~int~i',$U)?"integer":(preg_match('~char|clob|text~i',$U)?"text":(preg_match('~blob~i',$U)?"blob":(preg_match('~real|floa|doub~i',$U)?"real":(preg_match('~any~i',$U)?"any":"numeric")))));}function
fields($R){$I=[];$Sl=get_val("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ".q($R));$Zj=["select"=>1,"where"=>1,"order"=>1];if(!preg_match('~^sqlite(_temp)?_(master|schema)$~',$R))$Zj+=["insert"=>1,"update"=>1];$Be=preg_match('~^fts\d+$~i',virtual_module($Sl));$Nb=parse_comments($Sl);foreach(get_rows("PRAGMA table_".(min_version(3.31)?"x":"")."info(".table($R).")")as$J){if($J["hidden"]==1)continue;$B=$J["name"];$U=strtolower($J["type"]);$l=$J["dflt_value"];preg_match('~^(.+?)\s*(?:\((.*)\))?$~s',$U,$tj);$mk=preg_match("~^'(?:[^']|'')*'$~",$l);$I[$B]=["field"=>$B,"type"=>($Be?"text":($tj?$tj[1]:type_affinity($U))),"full_type"=>$U,"length"=>($Be?"":idx($tj,2,"")),"default"=>($mk?idf_unescape($l):($l=="NULL"?null:$l)),"default_sql"=>($l!==null&&!$mk&&$l!="NULL"&&!is_numeric($l)),"null"=>!$J["notnull"],"privileges"=>$Zj,"primary"=>$J["pk"],"comment"=>idx($Nb,$B,""),];if($J["pk"]&&preg_match('~\bAUTOINCREMENT\b~i',$Sl))$I[$B]["auto_increment"]=true;}$u='[(,]\s*(("[^"]*+")+|[a-z0-9_]+)';$Dk='(?:[^,()\']|\'[^\']*+\'|\([^)]*+\))*?';preg_match_all('~'.$u.'\s+text\b'.$Dk.'COLLATE\s+(\'[^\']+\'|[a-z0-9_]+)~i',$Sl,$Ug,PREG_SET_ORDER);foreach($Ug
as$A){$B=idf_unescape($A[1]);if($I[$B])$I[$B]["collation"]=trim($A[3],"'");}preg_match_all('~'.$u.'\s'.$Dk.'GENERATED\s+ALWAYS\s+AS\s*\((.+?)\)\s+(STORED|VIRTUAL)~i',$Sl,$Ug,PREG_SET_ORDER);foreach($Ug
as$A){$B=idf_unescape($A[1]);if($I[$B]){$I[$B]["default"]=$A[3];$I[$B]["generated"]=strtoupper($A[4]);}}return$I;}function
indexes($R,$h=null){$h=connection($h);$I=[];$Sl=get_val("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ".q($R),0,$h);if(preg_match('~^fts\d+$~i',virtual_module($Sl)))return[$R=>["type"=>"FULLTEXT","columns"=>array_keys(fields($R)),"lengths"=>[],"descs"=>[]]];if(preg_match('~\bPRIMARY\s+KEY\s*\((([^)"]+|"[^"]*"|`[^`]*`)++)~i',$Sl,$A)){$I[""]=["type"=>"PRIMARY","columns"=>[],"lengths"=>[],"descs"=>[]];preg_match_all('~((("[^"]*+")+|(?:`[^`]*+`)+)|(\S+))(\s+(ASC|DESC))?(,\s*|$)~i',$A[1],$Ug,PREG_SET_ORDER);foreach($Ug
as$A){$I[""]["columns"][]=idf_unescape($A[2]).$A[4];$I[""]["descs"][]=(preg_match('~DESC~i',$A[5])?'1':null);}}if(!$I){foreach(get_rows("PRAGMA table_info(".table($R).")",$h)as$J){if($J["pk"])$I[""]=["type"=>"PRIMARY","columns"=>[$J["name"]],"lengths"=>[],"descs"=>[null]];}}$Xl=get_key_vals("SELECT name, sql FROM sqlite_master WHERE type = 'index' AND tbl_name = ".q($R),$h);foreach(get_rows("PRAGMA index_list(".table($R).")",$h)as$J){$B=$J["name"];$v=["type"=>($J["unique"]?"UNIQUE":"INDEX")];$v["lengths"]=[];$v["descs"]=[];foreach(get_rows("PRAGMA index_info(".idf_escape($B).")",$h)as$Uk){$v["columns"][]=$Uk["name"];$v["descs"][]=null;}if(preg_match('~^CREATE( UNIQUE)? INDEX '.preg_quote(idf_escape($B).' ON '.idf_escape($R),'~').' \((.*)\)$~i',$Xl[$B],$zk)){preg_match_all('/("[^"]*+")+( DESC)?/',$zk[2],$Ug);foreach($Ug[2]as$x=>$X){if($X)$v["descs"][$x]='1';}}if(!$I[""]||$v["type"]!="UNIQUE"||$v["columns"]!=$I[""]["columns"]||$v["descs"]!=$I[""]["descs"]||!preg_match("~^sqlite_~",$B))$I[$B]=$v;}return$I;}function
foreign_keys($R){$I=[];foreach(get_rows("PRAGMA foreign_key_list(".table($R).")")as$J){$q=&$I[$J["id"]];if(!$q)$q=$J;$q["source"][]=$J["from"];$q["target"][]=$J["to"];}return$I;}function
view($B){return["select"=>preg_replace('~^(?:[^`"[]+|`[^`]*`|"[^"]*")* AS\s+(?=\S)~iU','',get_val("SELECT sql FROM sqlite_master WHERE type = 'view' AND name = ".q($B)))];}function
collations(){return(isset($_GET["create"])?get_vals("PRAGMA collation_list",1):[]);}function
information_schema($k,$L=""){return
false;}function
error(){return
h(connection()->error);}function
check_sqlite_name($B){$Sd="db|sdb|sqlite";if(!preg_match("~^[^\\0]*\\.($Sd)\$~",$B)){connection()->error=sprintf('Please use one of these file extensions: %s.',str_replace("|",", ",$Sd));return
false;}return
true;}function
create_database($k,$Fb){if(file_exists($k)){connection()->error='File exists.';return
false;}if(!check_sqlite_name($k))return
false;try{$_=new
Db();$_->attach(server_parts(["path"=>$k]),'','');}catch(\Exception$Id){connection()->error=$Id->getMessage();return
false;}$_->query('PRAGMA encoding = "UTF-8"');$_->query('CREATE TABLE adminer (i)');$_->query('DROP TABLE adminer');return
true;}function
drop_databases(array$j){connection()->attach(server_parts(["path"=>":memory:"]),'','');foreach($j
as$k){if(!check_sqlite_name($k))return
false;if(!@unlink($k)){connection()->error='File exists.';return
false;}}return
true;}function
rename_database($B,$Fb){if(!check_sqlite_name($B))return
false;connection()->attach(server_parts(["path"=>":memory:"]),'','');connection()->error='File exists.';return@rename(DB,$B);}function
auto_increment(){return" PRIMARY KEY AUTOINCREMENT";}function
alter_table($R,$B,array$o,array$se,$f,$vd,$Fb,$La,$qj){$On=($R==""||$se||$vd||$f!==null);foreach($o
as$n){if($n[0]!=""||!$n[1]||$n[2]||$n[1][5]){$On=true;break;}}$b=[];$Wi=[];foreach($o
as$n){if($n[1]){$b[]=($On?$n[1]:"ADD ".implode($n[1]));if($n[0]!="")$Wi[$n[0]]=$n[1][0];}}if(!$On){foreach($b
as$X){if(!queries("ALTER TABLE ".table($R)." $X"))return
false;}if($R!=$B&&!queries("ALTER TABLE ".table($R)." RENAME TO ".table($B)))return
false;}elseif(!recreate_table($R,$B,$b,$Wi,$se,$La,[],"","",$vd,$f))return
false;if($La){queries("BEGIN");queries("UPDATE sqlite_sequence SET seq = $La WHERE name = ".q($B));if(!connection()->affected_rows)queries("INSERT INTO sqlite_sequence (name, seq) VALUES (".q($B).", $La)");queries("COMMIT");}return
true;}function
recreate_table($R,$B,array$o,array$Wi,array$se,$La="",$w=[],$dd="",$la="",$vd="",$f=null){if($R!=""){if(!$o){foreach(fields($R)as$x=>$n){if($w)$n["auto_increment"]=0;$o[]=process_field($n,$n);$Wi[$x]=idf_escape($x);}}$Vj=false;foreach($o
as$n){if($n[6])$Vj=true;}$fd=[];foreach($w
as$x=>$X){if($X[2]=="DROP"){$fd[$X[1]]=true;unset($w[$x]);}}foreach(indexes($R)as$ng=>$v){$e=[];foreach($v["columns"]as$x=>$d){if(!$Wi[$d])continue
2;$e[]=$Wi[$d].($v["descs"][$x]?" DESC":"");}if(!$fd[$ng]){if($v["type"]!="PRIMARY"||!$Vj)$w[]=[$v["type"],$ng,$e];}}foreach($w
as$x=>$X){if($X[0]=="PRIMARY"){unset($w[$x]);$se[]="  PRIMARY KEY (".implode(", ",$X[2]).")";}elseif(preg_match('~^sqlite_autoindex_~',$X[1])){unset($w[$x]);$se[]="  UNIQUE (".implode(", ",$X[2]).")";}}foreach(foreign_keys($R)as$ng=>$q){foreach($q["source"]as$x=>$d){if(!$Wi[$d])continue
2;$q["source"][$x]=idf_unescape($Wi[$d]);}if(!isset($se[" $ng"]))$se[]=" ".format_foreign_key($q);}queries("PRAGMA foreign_keys = 0");queries("PRAGMA legacy_alter_table = 1");queries("BEGIN");}$nb=[];$Nb=[];foreach($o
as$n){if(preg_match('~GENERATED~',$n[3]))unset($Wi[array_search($n[0],$Wi)]);if(preg_match("~^ COMMENT ('.*')$~s",$n[5],$A))$Nb[count($nb)]=idf_unescape($A[1]);unset($n[5]);$nb[]="  ".implode($n);}$nb=array_merge($nb,array_values(array_filter($se)));foreach(driver()->checkConstraints($R)as$x=>$rb){if($x!=$dd)$nb[]=" ".($x!=$rb?" CONSTRAINT ".idf_escape($x):"")." CHECK ($rb)";}if($la)$nb[]="  $la";$Mm=($R!=""&&$R==$B?"adminer_$B":$B);if($R!=""&&(!$vd||$f===null)){$P=table_status1($R);$vd=$vd?:idx($P,"Engine");$f=($f===null?idx($P,"Comment",""):$f);}$Jg=function($Om){return($Om!=""?" -- ".preg_replace('~[\r\n]+~',' ',$Om):"");};$i="CREATE TABLE ".table($Mm)." (".$Jg($f)."\n";foreach($nb
as$s=>$mb)$i
.=$mb.($s<count($nb)-1?",":"").$Jg(idx($Nb,$s))."\n";if(!queries("$i)".($vd!="table"&&in_array($vd,driver()->engines())?" $vd":"")))return
false;if($R!=""){if($Wi&&!queries("INSERT INTO ".table($Mm)." (".implode(", ",$Wi).") SELECT ".implode(", ",array_map('Adminer\idf_escape',array_keys($Wi)))." FROM ".table($R)))return
false;$pn=[];foreach(triggers($R)as$nn=>$Tm){$ln=trigger($nn,$R);$pn[]="CREATE TRIGGER ".idf_escape($nn)." ".implode(" ",$Tm)." ON ".table($B)."\n$ln[Statement]";}$La=$La?"":get_val("SELECT seq FROM sqlite_sequence WHERE name = ".q($R));if(!queries("DROP TABLE ".table($R))||($R==$B&&!queries("ALTER TABLE ".table($Mm)." RENAME TO ".table($B)))||!alter_indexes($B,$w))return
false;if($La)queries("UPDATE sqlite_sequence SET seq = $La WHERE name = ".q($B));foreach($pn
as$ln){if(!queries($ln))return
false;}if(get_rows("PRAGMA foreign_key_check(".table($B).")")){connection()->error="FOREIGN KEY constraint failed";return
false;}queries("COMMIT");queries("PRAGMA foreign_keys = 1");queries("PRAGMA legacy_alter_table = 0");}return
true;}function
index_sql($R,$U,$B,$e){return"CREATE $U ".($U!="INDEX"?"INDEX ":"").idf_escape($B!=""?$B:uniqid($R."_"))." ON ".table($R)." $e";}function
alter_indexes($R,$b){foreach($b
as$Uj){if($Uj[0]=="PRIMARY")return
recreate_table($R,$R,[],[],[],"",$b);}foreach(array_reverse($b)as$X){if(!queries($X[2]=="DROP"?"DROP INDEX ".idf_escape($X[1]):index_sql($R,$X[0],$X[1],"(".implode(", ",$X[2]).")")))return
false;}return
true;}function
truncate_tables(array$T){return
apply_queries("DELETE FROM",$T);}function
drop_views(array$go){return
apply_queries("DROP VIEW",$go);}function
drop_tables(array$T){return
apply_queries("DROP TABLE",$T);}function
move_tables(array$T,array$go,$Im){return
false;}function
trigger($B,$R){if($B=="")return["Statement"=>"BEGIN\n\t;\nEND"];$u='(?:[^`"\s]+|`[^`]*`|"[^"]*")+';$on=trigger_options();preg_match("~^CREATE\\s+TRIGGER\\s*$u\\s*(".implode("|",$on["Timing"]).")\\s+([a-z]+)(?:\\s+OF\\s+($u))?\\s+ON\\s*$u\\s*(?:FOR\\s+EACH\\s+ROW\\s+)?(.*)~is",get_val("SELECT sql FROM sqlite_master WHERE type = 'trigger' AND name = ".q($B)),$A);if(!$A)return[];$ki=$A[3];return["Timing"=>strtoupper($A[1]),"Event"=>strtoupper($A[2]).($ki?" OF":""),"Of"=>idf_unescape($ki),"Trigger"=>$B,"Statement"=>$A[4],];}function
triggers($R){$I=[];$on=trigger_options();foreach(get_rows("SELECT * FROM sqlite_master WHERE type = 'trigger' AND tbl_name = ".q($R))as$J){preg_match('~^CREATE\s+TRIGGER\s*(?:[^`"\s]+|`[^`]*`|"[^"]*")+\s*('.implode("|",$on["Timing"]).')\s*(.*?)\s+ON\b~i',$J["sql"],$A);$I[$J["name"]]=[$A[1],$A[2]];}return$I;}function
trigger_options(){return["Timing"=>["BEFORE","AFTER","INSTEAD OF"],"Event"=>["INSERT","UPDATE","UPDATE OF","DELETE"],"Type"=>["FOR EACH ROW"],];}function
last_id($H){return
get_val("SELECT LAST_INSERT_ROWID()");}function
explain(Db$g,$G){return$g->query("EXPLAIN QUERY PLAN $G");}function
found_rows(array$S,array$Z){}function
types($Sd=false){return[];}function
create_sql($R,$La,$hm){$I=get_val("SELECT sql FROM sqlite_master WHERE type IN ('table', 'view') AND name = ".q($R));foreach(indexes($R)as$B=>$v){if($B==''||$v['type']=='FULLTEXT')continue;$I
.=";\n\n".index_sql($R,$v['type'],$B,"(".implode(", ",array_map('Adminer\idf_escape',$v['columns'])).")");}return$I;}function
truncate_sql($R){return"DELETE FROM ".table($R);}function
use_sql($uc,$hm=""){return"";}function
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
attach(array$N,$V,$E){sqlsrv_configure("WarningsReturnAsErrors",0);$Ub=["UID"=>$V,"PWD"=>$E,"CharacterSet"=>"UTF-8","ReturnDatesAsStrings"=>true];if(isset($_GET["sql"])&&!self::$instance)$Ub["MultipleActiveResultSets"]=false;$Yl=adminer()->connectSsl();if(isset($Yl["Encrypt"]))$Ub["Encrypt"]=$Yl["Encrypt"];if(isset($Yl["TrustServerCertificate"]))$Ub["TrustServerCertificate"]=$Yl["TrustServerCertificate"];$k=adminer()->database();if($k!="")$Ub["Database"]=$k;$Hj=$N["port"];$this->link=@sqlsrv_connect($N["host"].($Hj?",$Hj":""),$Ub);if($this->link){$Kf=sqlsrv_server_info($this->link);$this->server_info=$Kf['SQLServerVersion'];}else$this->get_error();return($this->link?'':$this->error);}function
quote($Q){return
unicode_prefix($Q)."'".str_replace("'","''",$Q)."'";}function
select_db($uc){return$this->query(use_sql($uc));}function
query($G,$xn=false){$H=$this->execute($G);$this->error="";if(!$H){$this->get_error();return
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
as$jo)$I[]=$jo["message"];return$I;}function
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
fetch_field(){if(!$this->fields)$this->fields=sqlsrv_field_metadata($this->result);$wn=[-155=>"datetimeoffset","time",-152=>"xml","varbinary","sql_variant",-11=>"uniqueidentifier","ntext","nvarchar","nchar","bit","tinyint","bigint","image","varbinary","binary","text",1=>"char","numeric","decimal","int","smallint","float","real","float",12=>"varchar",91=>"date","time","datetime",];$n=$this->fields[$this->offset++];$I=new
\stdClass;$I->name=$n["Name"];$I->native_type=idx($wn,$n["Type"],"");return$I;}function
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
attach(array$N,$V,$E){$Hj=$N["port"];$hd="sqlsrv:Server=$N[host]".($Hj?",$Hj":"").(isset($_GET["sql"])&&!self::$instance?";MultipleActiveResultSets=0":"");$Yl=adminer()->connectSsl();foreach(["Encrypt","TrustServerCertificate"]as$x){if(isset($Yl[$x]))$hd
.=";$x=".($Yl[$x]?1:0);}return$this->dsn($hd,$V,$E,[\PDO::SQLSRV_ATTR_DIRECT_QUERY=>true]);}function
query($G,$xn=false){if(!$this->timeout)return
parent::query($G,$xn);$this->pdo->setAttribute(\PDO::SQLSRV_ATTR_QUERY_TIMEOUT,$this->timeout);$this->timeout=0;$I=parent::query($G,$xn);$this->pdo->setAttribute(\PDO::SQLSRV_ATTR_QUERY_TIMEOUT,$this->query_timeout);return$I;}function
timeout($Dh){$this->query_timeout=(int)ceil($Dh/1000);return$this->pdo->setAttribute(\PDO::SQLSRV_ATTR_QUERY_TIMEOUT,$this->query_timeout);}}}elseif(extension_loaded("pdo_dblib")){class
Db
extends
MssqlDb{var$extension="PDO_DBLIB";function
attach(array$N,$V,$E){$Hj=$N["port"];$Kl=$N["socket"];$Hi=[1001=>0,1002=>true,];$I=$this->dsn("dblib:charset=utf8;host=$N[host]".($Hj!=""?";port=$Hj":($Kl!=""?";unix_socket=$Kl":"")),$V,$E,$Hi);if(!$I){$this->query("SET ANSI_NULLS, QUOTED_IDENTIFIER, CONCAT_NULL_YIELDS_NULL, ANSI_WARNINGS, ANSI_PADDING ON");$this->server_info=get_val("SELECT CAST(SERVERPROPERTY('ProductVersion') AS varchar(20))",0,$this);}return$I;}}}}class
Driver
extends
SqlDriver{static$extensions=["SQLSRV","PDO_SQLSRV","PDO_DBLIB"];static$jush="mssql";static$serverSocket=true;var$insertFunctions=["date|time"=>"getdate"];var$editFunctions=["int|decimal|real|float|money|datetime"=>"+/-","char|text"=>"+",];var$functions=["len","lower","round","upper"];var$grouping=["avg","count","count distinct","max","min","sum"];var$generated=["PERSISTED","VIRTUAL"];var$onActions="NO ACTION|CASCADE|SET NULL|SET DEFAULT";var$inout="|OUTPUT";function
operators($rm){return["=","<",">","<=",">=","!=","LIKE","LIKE %%","IN","BETWEEN","IS NULL","NOT LIKE","NOT IN","IS NOT NULL"];}static
function
connect($N,$V,$E){if($N=="")$N="localhost:1433";return
parent::connect($N,$V,$E);}function
__construct(Db$g){parent::__construct($g);$this->types=['Numbers'=>["tinyint"=>3,"smallint"=>5,"int"=>10,"bigint"=>20,"bit"=>1,"decimal"=>0,"numeric"=>0,"real"=>12,"float"=>53,"smallmoney"=>10,"money"=>20,"vector"=>0,],'Date and time'=>["date"=>10,"smalldatetime"=>19,"datetime"=>23,"datetime2"=>19,"time"=>8,"datetimeoffset"=>26],'Strings'=>["char"=>8000,"varchar"=>8000,"text"=>2147483647,"nchar"=>4000,"nvarchar"=>4000,"ntext"=>1073741823,"uniqueidentifier"=>36,"xml"=>2147483647,"json"=>2147483647,"sql_variant"=>8000,"hierarchyid"=>892,],'Binary'=>["binary"=>8000,"varbinary"=>8000,"image"=>2147483647],'Geometry'=>["geometry"=>0,"geography"=>0],];}function
types(){$Cn=$this->unknownTypes();return
parent::types()+array_fill_keys($Cn,0);}function
structuredTypes(){$Cn=$this->unknownTypes();return
array_merge(parent::structuredTypes(),$Cn);}private
function
unknownTypes(){static$I;if($I===null){$I=[];$wn=array_flip(get_vals("SELECT name FROM sys.types WHERE is_user_defined = 0 ORDER BY name"));if($wn){foreach($this->types
as$Me=>$Pe){foreach($Pe
as$U=>$y){if(isset($wn[$U]))unset($wn[$U]);else
unset($this->types[$Me][$U]);}if(!$this->types[$Me])unset($this->types[$Me]);}$I=array_keys($wn);}}return$I;}function
typeName(\stdClass$n){return
idx((array)$n,'sqlsrv:decl_type',parent::typeName($n));}function
insertUpdate($R,array$K,array$Uj){$o=fields($R);$Hn=[];$Z=[];$O=reset($K);$e="c".implode(", c",range(1,count($O)));$eb=0;$Qf=[];foreach($O
as$x=>$X){$eb++;$B=idf_unescape($x);if(!$o[$B]["auto_increment"])$Qf[$x]="c$eb";if(isset($Uj[$B]))$Z[]="$x = c$eb";else$Hn[]="$x = c$eb";}$bo=[];foreach($K
as$O)$bo[]="(".implode(", ",$O).")";if($Z){$tf=queries("SET IDENTITY_INSERT ".table($R)." ON");$I=queries("MERGE ".table($R)." USING (VALUES\n\t".implode(",\n\t",$bo)."\n) AS source ($e) ON ".implode(" AND ",$Z).($Hn?"\nWHEN MATCHED THEN UPDATE SET ".implode(", ",$Hn):"")."\nWHEN NOT MATCHED THEN INSERT (".implode(", ",array_keys($tf?$O:$Qf)).") VALUES (".($tf?$e:implode(", ",$Qf)).");");if($tf)queries("SET IDENTITY_INSERT ".table($R)." OFF");}else$I=queries("INSERT INTO ".table($R)." (".implode(", ",array_keys($O)).") VALUES\n".implode(",\n",$bo));return$I;}function
begin(){remember_query("BEGIN TRANSACTION");return$this->conn->begin();}function
slowQuery($G,$Sm){if($this->conn->extension!="PDO_DBLIB"){$this->conn->timeout=$Sm;return$G;}}function
convertSearch($u,array$X,array$n){return(preg_match('~^(bit|n?text|xml|json|vector|uniqueidentifier|sql_variant|hierarchyid|geography|geometry)$~',$n["type"])?"CAST($u AS nvarchar(max))":$u);}function
quoteBinary($Wk){return"0x".bin2hex($Wk);}function
warnings(){$I=[];foreach($this->conn->warnings()as$mh){$mh=trim(preg_replace('~^(\[[^]]+])+~','',$mh));if($mh!="")$I[]=$mh;}return
nl_br(h(implode("\n",$I)));}function
tableHelp($B,$fg=false){$Kg=["sys"=>"catalog-views/sys-","INFORMATION_SCHEMA"=>"information-schema-views/",];$_=$Kg[get_schema()];if($_)return"relational-databases/system-$_".preg_replace('~_~','-',strtolower($B))."-transact-sql";}function
isSystem($k,$L=""){return($L!=""?information_schema($k,$L)||preg_match('~^(guest|db_(owner|accessadmin|securityadmin|ddladmin|backupoperator|(deny)?data(reader|writer)))$~',$L):in_array($k,["master","tempdb","model","msdb"]));}}function
unicode_prefix($Q){return(strlen($Q)!=utf8_length($Q)?"N":"");}function
idf_escape($u){return"[".str_replace("]","]]",$u)."]";}function
table($u){return($_GET["ns"]!=""?idf_escape($_GET["ns"]).".":"").idf_escape($u);}function
get_databases($qe){return
get_vals("SELECT name FROM sys.databases WHERE name NOT IN ('master', 'tempdb', 'model', 'msdb')");}function
limit($G,$Z,$z,$C=0,$ol=" "){return($z?" TOP (".($z+$C).")":"")." $G$Z";}function
limit1($R,$G,$Z,$ol="\n"){return
limit($G,$Z,1,0,$ol);}function
db_collation($k,array$Gb){static$I;if($I===null)$I=get_key_vals("SELECT name, collation_name FROM sys.databases");return$I[$k];}function
logged_user(){return
get_val("SELECT SUSER_NAME()");}function
tables_list(){return
get_key_vals("SELECT name, type_desc FROM sys.all_objects WHERE schema_id = SCHEMA_ID(".q(get_schema()).") AND type IN ('S', 'U', 'V') ORDER BY name");}function
count_tables(array$j){$I=[];foreach($j
as$k){connection()->select_db($k);$I[$k]=get_val("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES");}return$I;}function
table_status($B="",$Wd=false){$I=[];$Il=[];foreach(($Wd?[]:get_rows("SELECT object_id, SUM(CASE WHEN index_id < 2 THEN row_count ELSE 0 END) AS [Rows],
SUM(CASE WHEN index_id < 2 THEN used_page_count ELSE 0 END) * 8192 AS Data_length,
SUM(CASE WHEN index_id > 1 THEN used_page_count ELSE 0 END) * 8192 AS Index_length,
SUM(reserved_page_count - used_page_count) * 8192 AS Data_free
FROM sys.dm_db_partition_stats".($B!=""?"
WHERE object_id = OBJECT_ID(".q(table($B)).")":"")."
GROUP BY object_id",null,""))as$J){$ji=$J["object_id"];unset($J["object_id"]);$Il[$ji]=$J;}foreach(get_rows("SELECT ao.object_id, ao.name AS Name, ao.type_desc AS Engine,
	(SELECT cast(value as varchar(max)) FROM fn_listextendedproperty('MS_Description', 'SCHEMA', schema_name(schema_id), 'TABLE', ao.name, null, null)) AS Comment,
	CASE WHEN EXISTS (SELECT 1 FROM sys.indexes i WHERE i.object_id = ao.object_id AND i.is_disabled = 1) THEN 1 END AS invalid_index
FROM sys.all_objects AS ao
WHERE schema_id = SCHEMA_ID(".q(get_schema()).") AND type IN ('S', 'U', 'V') ".($B!=""?"AND name = ".q($B):"ORDER BY name"))as$J){$ji=$J["object_id"];unset($J["object_id"]);$I[$J["Name"]]=$J+idx($Il,$ji,[]);}return$I;}function
is_view(array$S){return$S["Engine"]=="VIEW";}function
fk_support(array$S){return
true;}function
type_length($U,array$J){return(preg_match("~char|binary~",$U)?($J["max_length"]==-1?"max":intval($J["max_length"])/($U[0]=='n'?2:1)):($U=="decimal"?"$J[precision],$J[scale]":(preg_match('~^(datetime2|datetimeoffset|time)$~',$U)?$J["scale"]:($U=="vector"?(intval($J["max_length"])-8)/4:""))));}function
fields($R){$Nb=get_key_vals("SELECT objname, cast(value as varchar(max)) FROM fn_listextendedproperty('MS_DESCRIPTION', 'schema', ".q(get_schema()).", 'table', ".q($R).", 'column', NULL)");$I=[];$sm=get_val("SELECT object_id FROM sys.all_objects WHERE schema_id = SCHEMA_ID(".q(get_schema()).") AND type IN ('S', 'U', 'V') AND name = ".q($R));foreach(get_rows("SELECT c.max_length, c.precision, c.scale, c.name, c.is_nullable, c.is_identity, c.collation_name,
	COALESCE(bt.name, t.name) type, d.definition [default], d.name default_constraint, i.is_primary_key
FROM sys.all_columns c
JOIN sys.types t ON c.user_type_id = t.user_type_id
LEFT JOIN sys.types bt ON t.system_type_id = bt.user_type_id AND t.is_user_defined = 1
LEFT JOIN sys.default_constraints d ON c.default_object_id = d.object_id
LEFT JOIN sys.index_columns ic ON c.object_id = ic.object_id AND c.column_id = ic.column_id
LEFT JOIN sys.indexes i ON ic.object_id = i.object_id AND ic.index_id = i.index_id
WHERE c.object_id = ".q($sm))as$J){$U=$J["type"];$y=type_length($U,$J);$l=$J["default"];$Bc=false;if(preg_match("~^\\(N?('(?:[^']|'')*')\\)$~s",$l,$A))$l=idf_unescape($A[1]);elseif(preg_match('~^\(\(([-+]?[\d.]+)\)\)$~',$l,$A))$l=$A[1];elseif($l!==null){$l=preg_replace('~^\((.*)\)$~s','\1',$l);$Bc=true;}$I[$J["name"]]=["field"=>$J["name"],"full_type"=>$U.($y!=""?"($y)":""),"type"=>$U,"length"=>$y,"default"=>$l,"default_sql"=>$Bc,"default_constraint"=>$J["default_constraint"],"null"=>$J["is_nullable"],"auto_increment"=>$J["is_identity"],"collation"=>$J["collation_name"],"privileges"=>($U=="timestamp"?["select"=>1,"where"=>1,"order"=>1]:["insert"=>1,"select"=>1,"update"=>1,"where"=>1,"order"=>1]),"primary"=>$J["is_primary_key"],"comment"=>$Nb[$J["name"]],];}foreach(get_rows("SELECT * FROM sys.computed_columns WHERE object_id = ".q($sm))as$J){$I[$J["name"]]["generated"]=($J["is_persisted"]?"PERSISTED":"VIRTUAL");$I[$J["name"]]["default"]=$J["definition"];}return$I;}function
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
alter_table($R,$B,array$o,array$se,$f,$vd,$Fb,$La,$qj){$b=[];$Nb=[];$Si=fields($R);foreach($o
as$n){$d=idf_escape($n[0]);$X=$n[1];if(!$X)$b["DROP"][]=" COLUMN $d";else{$Qi=idx($Si[$n[0]],"comment");if($X[5]!=($Qi!=""?" COMMENT ".q($Qi):""))$Nb[idf_unescape($X[0])]=[$Qi!="",substr($X[5],9)];unset($X[5]);if(preg_match('~ AS ~',$X[3]))unset($X[1],$X[2]);if($n[0]=="")$b["ADD"][]="\n  ".implode("",$X).($R==""?str_replace(" FOREIGN KEY ($X[0])","",$se[$X[0]]):"");else{$l=$X[3];unset($X[3]);unset($X[6]);if($d!=$X[0])queries("EXEC sp_rename ".q(table($R).".$d").", ".q(idf_unescape($X[0])).", 'COLUMN'");$b["ALTER COLUMN ".implode("",$X)][]="";$Ri=$Si[$n[0]];if(default_value($Ri)!=$l){if($Ri["default"]!==null)$b["DROP"][]=" ".idf_escape($Ri["default_constraint"]);if($l)$b["ADD"][]="\n $l FOR $d";}}}}if($R==""){$ka=(array)$b["ADD"];foreach($se
as$x=>$X){if(!is_string($x))$ka[]="\n$X";}if(!queries("CREATE TABLE ".table($B)." (".implode(",",$ka)."\n)"))return
false;}else{if($R!=$B)queries("EXEC sp_rename ".q(table($R)).", ".q($B));if($se)$b[""]=$se;foreach($b
as$x=>$X){if(!queries("ALTER TABLE ".table($B)." $x".implode(",",$X)))return
false;}}$dk="@name = N'MS_Description', @level0type = N'Schema', @level0name = ".q(get_schema()).", @level1type = N'Table', @level1name = ".q($B);foreach($Nb
as$x=>$X){list($cd,$Lb)=$X;$ek="$dk, @level2type = N'Column', @level2name = ".q($x);if($cd)queries("EXEC sp_dropextendedproperty $ek");if($Lb!="")queries("EXEC sp_addextendedproperty $ek, @value = $Lb");}if($f!==null){if(idx(table_status1($B),"Comment")!="")queries("EXEC sp_dropextendedproperty $dk");if($f!="")queries("EXEC sp_addextendedproperty $dk, @value = ".q($f));}return
true;}function
alter_indexes($R,$b){$v=[];$cd=[];$hk=[];foreach($b
as$X){if($X[2]=="DROP"){if($X[0]=="PRIMARY"||($X[0]=="UNIQUE"&&get_val("SELECT is_unique_constraint FROM sys.indexes WHERE object_id = OBJECT_ID(".q(table($R)).") AND name = ".q($X[1]))))$cd[]=idf_escape($X[1]);else$v[]=idf_escape($X[1])." ON ".table($R);}else$hk[]=($X[0]!="PRIMARY"?"CREATE $X[0] ".($X[0]!="INDEX"?"INDEX ":"").idf_escape($X[1]!=""?$X[1]:uniqid($R."_"))." ON ".table($R):"ALTER TABLE ".table($R)." ADD PRIMARY KEY")." (".implode(", ",$X[2]).")";}if($cd)array_unshift($hk,"ALTER TABLE ".table($R)." DROP ".implode(", ",$cd));if($v)array_unshift($hk,"DROP INDEX ".implode(", ",$v));foreach($hk
as$G){if(!queries($G))return
false;}return
true;}function
found_rows(array$S,array$Z){}function
foreign_keys($R){$I=[];$zi=["CASCADE","NO ACTION","SET NULL","SET DEFAULT"];$L=get_schema();foreach(get_rows("EXEC sp_fkeys @fktable_name = ".q($R).", @fktable_owner = ".q($L))as$J){$q=&$I[$J["FK_NAME"]];$q["db"]=($J["PKTABLE_QUALIFIER"]==DB?"":$J["PKTABLE_QUALIFIER"]);$q["ns"]=($J["PKTABLE_OWNER"]==$L?"":$J["PKTABLE_OWNER"]);$q["table"]=$J["PKTABLE_NAME"];$q["on_update"]=$zi[$J["UPDATE_RULE"]];$q["on_delete"]=$zi[$J["DELETE_RULE"]];$q["source"][]=$J["FKCOLUMN_NAME"];$q["target"][]=$J["PKCOLUMN_NAME"];}return$I;}function
truncate_tables(array$T){return
apply_queries("TRUNCATE TABLE",$T);}function
drop_views(array$go){return
queries("DROP VIEW ".implode(", ",array_map('Adminer\table',$go)));}function
drop_tables(array$T){return
queries("DROP TABLE ".implode(", ",array_map('Adminer\table',$T)));}function
move_tables(array$T,array$go,$Im){return
apply_queries("ALTER SCHEMA ".idf_escape($Im)." TRANSFER",array_merge($T,$go));}function
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
routine_options($Nk){return[];}function
routine_id($B,array$J){return
table($B);}function
schemas(){return
get_vals("SELECT name FROM sys.schemas");}function
get_schema(){if($_GET["ns"]!="")return$_GET["ns"];return
get_val("SELECT SCHEMA_NAME()");}function
set_schema($L,$h=null){$_GET["ns"]=$L;return!!get_val("SELECT 1 FROM sys.schemas WHERE name = ".q($L),0,$h);}function
schema_comment($L){return(string)get_val("SELECT CAST(value AS nvarchar(max)) FROM fn_listextendedproperty('MS_Description', 'SCHEMA', ".q($L).", NULL, NULL, NULL, NULL)");}function
set_schema_comment($L,$f){$dk="@name = N'MS_Description', @level0type = N'Schema', @level0name = ".q($L);return(schema_comment($L)==""||queries("EXEC sp_dropextendedproperty $dk"))&&($f==""||queries("EXEC sp_addextendedproperty $dk, @value = ".q($f)));}function
create_sql($R,$La,$hm){$P=table_status1($R);if(is_view($P)){$fo=view($R);return"CREATE VIEW ".table($R)." AS $fo[select]";}$dk="EXEC sp_addextendedproperty @name = N'MS_Description', @level0type = N'Schema', @level0name = ".q(get_schema()).", @level1type = N'Table', @level1name = ".q($R);$Nb=($P["Comment"]!=""?";\n\n$dk, @value = ".q($P["Comment"]):"");$o=[];$Uj=false;foreach(fields($R)as$B=>$n){$X=process_field($n,$n);if($X[6])$Uj=true;unset($X[5]);$o[]=implode("",$X);if($n["comment"]!="")$Nb
.=";\n\n$dk, @level2type = N'Column', @level2name = ".q($B).", @value = ".q($n["comment"]);}foreach(indexes($R)as$B=>$v){if(!$Uj||$v["type"]!="PRIMARY"){$e=[];foreach($v["columns"]as$x=>$X)$e[]=idf_escape($X).($v["descs"][$x]?" DESC":"");$B=idf_escape($B);$o[]=($v["type"]=="INDEX"?"INDEX $B":"CONSTRAINT $B ".($v["type"]=="UNIQUE"?"UNIQUE":"PRIMARY KEY"))." (".implode(", ",$e).")";}}foreach(driver()->checkConstraints($R)as$B=>$rb)$o[]="CONSTRAINT ".idf_escape($B)." CHECK ($rb)";return"CREATE TABLE ".table($R)." (\n\t".implode(",\n\t",$o)."\n)$Nb";}function
foreign_keys_sql($R){$o=[];foreach(foreign_keys($R)as$se)$o[]=ltrim(format_foreign_key($se));return($o?"ALTER TABLE ".table($R)." ADD\n\t".implode(",\n\t",$o).";\n\n":"");}function
truncate_sql($R){return"TRUNCATE TABLE ".table($R);}function
use_sql($uc,$hm=""){return"USE ".idf_escape($uc);}function
use_schema_sql($L,$hm){$B=idf_escape($L);$i="EXEC(".q("CREATE SCHEMA $B").")";$f=schema_comment($L);if($f!="")$i="BEGIN\n$i\nEXEC sp_addextendedproperty @name = N'MS_Description', @level0type = N'Schema', @level0name = ".q($L).", @value = ".q($f)."\nEND";return($hm=="DROP+CREATE"?"DROP SCHEMA IF EXISTS $B;\n":"")."IF SCHEMA_ID(".q($L).") IS NULL $i";}function
trigger_sql($R){$I="";foreach(triggers($R)as$B=>$ln)$I
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
query($G,$xn=false){$H=oci_parse($this->link,$G);$this->error="";$this->error_offset=0;if(!$H){$m=oci_error($this->link);$this->errno=$m["code"];$this->error=$m["message"];return
false;}$Sm=$this->timeout;$kk=$this->query_timeout;if($Sm){$this->timeout=0;$this->timeout(1000*$Sm);}$Cd=[];$I=adminer()->executeQuery($G,function()use($H,&$Cd){$Cd=php_errors(function()use($H,&$I){$I=oci_execute($H,($this->transaction?OCI_NO_AUTO_COMMIT:OCI_COMMIT_ON_SUCCESS));});return$I;});if($Sm)$this->timeout($kk);if($Cd)$this->error=end($Cd);if(!$I){$m=oci_error($H);$this->error_offset=($m?$m["offset"]:0);}if($I){if(oci_num_fields($H))return
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
query($G,$xn=false){$Sm=$this->timeout;if(!$Sm)return
parent::query($G,$xn);$kk=$this->query_timeout;$this->timeout=0;$this->timeout(1000*$Sm);$I=parent::query($G,$xn);$this->timeout($kk);return$I;}function
timeout($Dh){$this->query_timeout=$Dh;return$this->pdo->setAttribute(1004,$Dh);}}}class
Driver
extends
SqlDriver{static$extensions=["OCI8","PDO_OCI"];static$jush="oracle";static$serverPath=true;var$insertFunctions=["date"=>"current_date","timestamp"=>"current_timestamp",];var$editFunctions=["number|float|double"=>"+/-","date|timestamp"=>"+ interval/- interval","char|clob"=>"||",];var$functions=["length","lower","round","upper"];var$grouping=["avg","count","count distinct","max","min","sum"];function
operators($rm){return["=","<",">","<=",">=","!=","LIKE","LIKE %%","IN","BETWEEN","IS NULL","NOT LIKE","NOT IN","IS NOT NULL","SQL"];}static
function
connect($N,$V,$E){$g=parent::connect($N,$V,$E);if(is_object($g))$g->query("ALTER SESSION SET CURSOR_SHARING = FORCE"." NLS_DATE_FORMAT = 'YYYY-MM-DD HH24:MI:SS'"." NLS_TIMESTAMP_FORMAT = 'YYYY-MM-DD HH24:MI:SS.FF'"." NLS_TIMESTAMP_TZ_FORMAT = 'YYYY-MM-DD HH24:MI:SS.FF TZH:TZM'");return$g;}function
__construct(Db$g){parent::__construct($g);$this->types=['Numbers'=>["number"=>38,"binary_float"=>12,"binary_double"=>21],'Date and time'=>["date"=>19,"timestamp"=>29,"interval year"=>12,"interval day"=>28],'Strings'=>["char"=>2000,"varchar2"=>4000,"nchar"=>2000,"nvarchar2"=>4000,"clob"=>4294967295,"nclob"=>4294967295],'Binary'=>["raw"=>2000,"long raw"=>2147483648,"blob"=>4294967295,"bfile"=>4294967296],'Geometry'=>["sdo_geometry"=>0],];}function
begin(){return$this->conn->begin();}function
slowQuery($G,$Sm){$this->conn->timeout=$Sm;return$G;}function
convertSearch($u,array$X,array$n){$U=$n["type"];$zj=strpos($X["op"],"LIKE")!==false;if($U=="xmltype")return"XMLSERIALIZE(CONTENT $u AS VARCHAR2(4000))";if($U=="json")return"JSON_SERIALIZE($u)";if(preg_match('~^(date|timestamp)~',$U))return"TO_CHAR($u, 'YYYY-MM-DD HH24:MI:SS')";if(preg_match('~char~',$U)||(preg_match('~clob~',$U)&&$zj))return$u;return(!$zj&&preg_match(number_type(),$U)?$u:"TO_CHAR($u)");}function
quoteBinary($Wk){return"HEXTORAW(".q(bin2hex($Wk)).")";}function
typeName(\stdClass$n){return
strtolower(parent::typeName($n));}function
hasCStyleEscapes(){return
true;}function
errorLine($G){$C=(isset($this->conn->error_offset)?$this->conn->error_offset:0);return($C?substr_count($G,"\n",0,min($C,strlen($G)))+1:0);}function
tableHelp($B,$fg=false){if(DB=="SYS"){if(preg_match('~^G?V_\$(.+)~',$B,$A))return"refrn/V-$A[1].html";if(preg_match('~^(ALL|DBA|USER)_~',$B))return"refrn/$B.html";}}function
select($R,array$M,array$Z,array$Me,array$Ki=[],$z=1,$D=0,$Wj=false){if(in_array("*",$M)){$dc=[];$we=false;foreach(fields($R)as$B=>$n){$Fa=convert_field($n);$we=($we||$Fa);$dc[]=($Fa?"$Fa AS ":"").idf_escape($B);}if($we)$M=$dc;}return
parent::select($R,$M,$Z,$Me,$Ki,$z,$D,$Wj);}function
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
limit($G,$Z,$z,$C=0,$ol=" "){return($C?" * FROM (SELECT t.*, rownum AS rnum FROM (SELECT $G$Z) t WHERE rownum <= ".($z+$C).") WHERE rnum > $C":($z?" * FROM (SELECT $G$Z) WHERE rownum <= ".($z+$C):" $G$Z"));}function
limit1($R,$G,$Z,$ol="\n"){return" $G$Z";}function
db_collation($k,array$Gb){static$I;if($I===null)$I=get_val("SELECT value FROM nls_database_parameters WHERE parameter = 'NLS_CHARACTERSET'");return$I;}function
logged_user(){return
get_val("SELECT USER FROM DUAL");}function
where_owner($aj="owner"){return"$aj = ".q(DB);}function
views_table($e){return"(SELECT $e FROM all_views WHERE ".where_owner().")";}function
objects_table(){return"(SELECT object_name, DECODE(object_type, 'VIEW', 'view', 'table') object_type FROM all_objects WHERE ".where_owner()." AND object_type IN ('TABLE', 'VIEW'))";}function
tables_list(){return
get_key_vals("SELECT * FROM ".objects_table()." ORDER BY 1");}function
count_tables(array$j){$I=[];foreach($j
as$k)$I[$k]=get_val("SELECT COUNT(*) FROM all_objects WHERE object_type IN ('TABLE', 'VIEW') AND owner = ".q($k));return$I;}function
table_status($B="",$Wd=false){$I=[];$cl=q($B);if($Wd||$B!=""){foreach(get_rows('SELECT object_name "Name", object_type "Engine" FROM '.objects_table().($B!=""?" WHERE object_name = $cl":"").' ORDER BY 1')as$J)$I[$J["Name"]]=$J;return$I;}foreach(get_rows('SELECT t.table_name "Name", \'table\' "Engine", s.bytes "Data_length", i.bytes "Index_length", t.num_rows "Rows"
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
fields($R){$I=[];$tf=null;$Uj=[];foreach(table_constraints($R)as$Wb){if($Wb["type"]=="P")$Uj=$Wb["columns"];}foreach(get_rows("SELECT * FROM all_tab_columns WHERE table_name = ".q($R)." AND ".where_owner()." ORDER BY column_id")as$J){$U=$J["DATA_TYPE"];$y=($J["DATA_PRECISION"]==""&&$J["DATA_SCALE"]!=""?"*":$J["DATA_PRECISION"]).",$J[DATA_SCALE]";if($y==","){$y=($U=="RAW"?$J["DATA_LENGTH"]:(preg_match('~CHAR~',$U)?$J["CHAR_COL_DECL_LENGTH"]:""));if($J["CHAR_USED"]=="C"&&preg_match('~^(VARCHAR2|CHAR)$~',$U))$y="$J[CHAR_LENGTH] CHAR";}elseif(strpos($U,"("))$y="";$l=$J["DATA_DEFAULT"];$Bc=false;if($l!==null){$l=rtrim($l);if(preg_match("~^'(?:[^']|'')*'\$~s",$l))$l=idf_unescape($l);elseif(preg_match('~^NULL$~i',$l))$l=null;else$Bc=!is_numeric($l);}if($J["IDENTITY_COLUMN"]=="YES"){if($tf===null)$tf=get_key_vals("SELECT column_name, generation_type FROM all_tab_identity_cols WHERE table_name = ".q($R)." AND ".where_owner());$l="GENERATED ".$tf[$J["COLUMN_NAME"]].($J["DEFAULT_ON_NULL"]=="YES"?" ON NULL":"")." AS IDENTITY";}$Zj=["insert"=>1,"select"=>1,"update"=>1,"order"=>1];if($J["DATA_TYPE_OWNER"]==""||$U=="XMLTYPE")$Zj["where"]=1;$I[$J["COLUMN_NAME"]]=["field"=>$J["COLUMN_NAME"],"full_type"=>$U.($y?"($y)":""),"type"=>strtolower($U),"length"=>$y,"default"=>$l,"default_sql"=>($Bc&&$J["IDENTITY_COLUMN"]!="YES"),"null"=>($J["NULLABLE"]=="Y"),"auto_increment"=>($J["IDENTITY_COLUMN"]=="YES"),"privileges"=>$Zj,"primary"=>in_array($J["COLUMN_NAME"],$Uj),];}return$I;}function
table_constraints($R,$h=null){static$hb=[];if(!$h&&isset($hb[$R]))return$hb[$R];$I=[];foreach(get_rows('SELECT c.constraint_name "name", c.constraint_type "type", c.r_owner "r_owner", c.r_constraint_name "r_constraint", c.delete_rule "delete_rule", cc.column_name "column"
FROM all_constraints c
JOIN all_cons_columns cc ON cc.owner = c.owner AND cc.constraint_name = c.constraint_name
WHERE c.constraint_type IN (\'P\', \'U\', \'R\') AND '.where_owner("c.owner")." AND c.table_name = ".q($R).'
ORDER BY cc.position',$h)as$J){$B=$J["name"];$I[$B]["type"]=$J["type"];$I[$B]["r_owner"]=$J["r_owner"];$I[$B]["r_constraint"]=$J["r_constraint"];$I[$B]["delete_rule"]=$J["delete_rule"];$I[$B]["columns"][]=$J["column"];}if(!$h)$hb[$R]=$I;return$I;}function
indexes($R,$h=null){$I=[];$Xb=[];$pj=[];foreach(table_constraints($R,$h)as$B=>$Wb)$Xb[$B]=$Wb["type"];foreach(get_rows("SELECT aic.*, atc.data_default, ai.status, ai.partitioned
FROM all_ind_columns aic
JOIN all_indexes ai ON ai.owner = aic.index_owner AND ai.index_name = aic.index_name
LEFT JOIN all_tab_cols atc ON aic.column_name = atc.column_name AND aic.table_name = atc.table_name AND aic.index_owner = atc.owner
WHERE aic.table_name = ".q($R)." AND ".where_owner("aic.table_owner")."
ORDER BY aic.column_position",$h)as$J){$Ff=$J["INDEX_NAME"];$Jb=$J["DATA_DEFAULT"];$Jb=($Jb?trim($Jb,'"'):$J["COLUMN_NAME"]);$U=idx($Xb,$Ff);$I[$Ff]["type"]=($U=="P"?"PRIMARY":($U=="U"?"UNIQUE":"INDEX"));$I[$Ff]["invalid"]=($J["STATUS"]=="UNUSABLE");$I[$Ff]["columns"][]=$Jb;$I[$Ff]["lengths"][]=($J["CHAR_LENGTH"]&&$J["CHAR_LENGTH"]!=$J["COLUMN_LENGTH"]?$J["CHAR_LENGTH"]:null);$I[$Ff]["descs"][]=($J["DESCEND"]&&$J["DESCEND"]=="DESC"?'1':null);if($J["PARTITIONED"]=="YES")$pj[$Ff]=q($Ff);}if($pj){$zf=where_owner("index_owner")." AND index_name IN (".implode(", ",$pj).") AND status = 'UNUSABLE'";foreach(get_rows("SELECT index_name FROM all_ind_partitions WHERE $zf UNION SELECT index_name FROM all_ind_subpartitions WHERE $zf",$h)as$J)$I[$J["INDEX_NAME"]]["invalid"]=true;}uasort($I,function($ha,$Pa){$Ki=["PRIMARY"=>0,"UNIQUE"=>1,"INDEX"=>2];return$Ki[$ha["type"]]-$Ki[$Pa["type"]];});return$I;}function
view($B){$K=get_rows('SELECT text "select" FROM '.views_table("view_name, text").' WHERE view_name = '.q($B));return($K?$K[0]:[]);}function
collations(){return[];}function
information_schema($k,$L=""){return
in_array($L!=""?$L:$k,["INFORMATION_SCHEMA","SYS","SYSTEM"]);}function
error(){return
h(connection()->error);}function
explain(Db$g,$G){$g->query("EXPLAIN PLAN FOR $G");return$g->query("SELECT * FROM plan_table");}function
found_rows(array$S,array$Z){}function
auto_increment(){return"";}function
alter_table($R,$B,array$o,array$se,$f,$vd,$Fb,$La,$qj){$b=$cd=[];$Si=($R?fields($R):[]);foreach($o
as$n){$X=$n[1];if($X&&$n[0]!=""&&idf_escape($n[0])!=$X[0])queries("ALTER TABLE ".table($R)." RENAME COLUMN ".idf_escape($n[0])." TO $X[0]");$Ri=$Si[$n[0]];if($X&&$Ri){$qi=process_field($Ri,$Ri);if(array_slice($X,1)==array_slice($qi,1))continue;if($X[2]==$qi[2])$X[2]="";}if($X){list($X[2],$X[3])=[$X[3],$X[2]];$b[]=($R!=""?($n[0]!=""?"MODIFY (":"ADD ("):"  ").implode($X).($R!=""?")":"");}else$cd[]=idf_escape($n[0]);}$b=array_merge($b,$se);if($R=="")return
queries("CREATE TABLE ".table($B)." (\n".implode(",\n",$b)."\n)");return(!$b||queries("ALTER TABLE ".table($R)."\n".implode("\n",$b)))&&(!$cd||queries("ALTER TABLE ".table($R)." DROP (".implode(", ",$cd).")"))&&($R==$B||queries("ALTER TABLE ".table($R)." RENAME TO ".table($B)));}function
alter_indexes($R,$b){$cd=[];$hk=[];foreach($b
as$X){if($X[0]!="INDEX"){$X[2]=preg_replace('~ DESC$~','',$X[2]);$i=($X[2]=="DROP"?"\nDROP CONSTRAINT ".idf_escape($X[1]):"\nADD".($X[1]!=""?" CONSTRAINT ".idf_escape($X[1]):"")." $X[0] ".($X[0]=="PRIMARY"?"KEY ":"")."(".implode(", ",$X[2]).")");array_unshift($hk,"ALTER TABLE ".table($R).$i);}elseif($X[2]=="DROP")$cd[]=idf_escape($X[1]);else$hk[]="CREATE INDEX ".idf_escape($X[1]!=""?$X[1]:uniqid($R."_"))." ON ".table($R)." (".implode(", ",$X[2]).")";}if($cd)array_unshift($hk,"DROP INDEX ".implode(", ",$cd));foreach($hk
as$G){if(!queries($G))return
false;}return
true;}function
foreign_keys($R){$I=[];$Lm=[];foreach(table_constraints($R)as$B=>$Wb){if($Wb["type"]=="R"){$I[$B]=["source"=>$Wb["columns"],"target"=>[],"on_delete"=>$Wb["delete_rule"],"on_update"=>null,];$Lm[$B]=[$Wb["r_owner"],$Wb["r_constraint"]];}}if($Lm){$Z=[];foreach($Lm
as$Im)$Z[]="(owner = ".q($Im[0])." AND constraint_name = ".q($Im[1]).")";foreach(get_rows("SELECT owner, constraint_name, table_name, column_name FROM all_cons_columns WHERE ".implode(" OR ",array_unique($Z))." ORDER BY position")as$J){foreach($Lm
as$B=>$Im){if($Im==[$J["OWNER"],$J["CONSTRAINT_NAME"]]){$I[$B]["db"]=($J["OWNER"]==DB?"":$J["OWNER"]);$I[$B]["table"]=$J["TABLE_NAME"];$I[$B]["target"][]=$J["COLUMN_NAME"];}}}}return$I;}function
trigger($B,$R){if($B=="")return[];$K=get_rows('SELECT trigger_name "Trigger", trigger_type "Type", triggering_event "Event", trigger_body "Statement"
FROM all_triggers
WHERE trigger_name = '.q($B)." AND ".where_owner());$I=reset($K);if($I){$U=$I["Type"];$I["Timing"]=(preg_match('~^(BEFORE|AFTER|INSTEAD OF)~',$U,$A)?$A[1]:$U);$I["Type"]=(preg_match('~EACH ROW~',$U)||$U=="INSTEAD OF"?"FOR EACH ROW":"");}return($I?:[]);}function
triggers($R){$I=[];foreach(get_rows("SELECT trigger_name, trigger_type, triggering_event FROM all_triggers WHERE table_name = ".q($R)." AND ".where_owner())as$J)$I[$J["TRIGGER_NAME"]]=[preg_replace('~ (STATEMENT|EACH ROW)$~','',$J["TRIGGER_TYPE"]),$J["TRIGGERING_EVENT"]];return$I;}function
trigger_options(){return["Timing"=>["BEFORE","AFTER","INSTEAD OF"],"Event"=>["INSERT","UPDATE","DELETE","INSERT OR UPDATE","INSERT OR DELETE","UPDATE OR DELETE","INSERT OR UPDATE OR DELETE"],"Type"=>["FOR EACH ROW",""],];}function
truncate_tables(array$T){return
apply_queries("TRUNCATE TABLE",$T);}function
drop_views(array$go){return
apply_queries("DROP VIEW",$go);}function
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
name(){return"<a href='https://www.adminer.org/'".target_blank()." id='h1'><img src='".h(preg_replace("~\\?.*~","",ME)."?file=logo.svg&version=6.3.0+9afc53aa")."' width='24' height='24' alt='' id='logo'>Adminer</a>";}function
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
operators($rm=null){return
driver()->operators($rm);}function
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
manifest(){$of=$_SERVER["HTTP_HOST"]?:$_SERVER["SERVER_NAME"];$ml=preg_replace('~\?.*~','',ME)?:'.';return['name'=>"Adminer".($of!=""?" - $of":""),'short_name'=>'Adminer','description'=>'Database management in a single PHP file','start_url'=>$ml,'scope'=>$ml,'display'=>'minimal-ui','icons'=>[['src'=>preg_replace("~\\?.*~","",ME)."?file=logo.svg&version=6.3.0+9afc53aa",'sizes'=>'any','type'=>'image/svg+xml']],];}function
head($qc=null){return
true;}function
bodyClass(){echo" adminer";}function
css(){$I=[];foreach(["","-dark"]as$zh){$p="adminer$zh.css";if(file_exists($p)){$ee=file_get_contents($p);$I["$p?v=".crc32($ee)]=($zh?"dark":(preg_match('~prefers-color-scheme:\s*dark~',$ee)?'':'light'));}}return$I;}function
loginForm(){echo"<table class='layout'>\n",adminer()->loginFormField('driver','<tr><th>'.'System'.'<td>',html_select("auth[driver]",SqlDriver::$drivers,DRIVER,on('change','loginDriver'))),adminer()->loginFormField('server','<tr><th>'.'Server'.'<td>',"<input name='auth[server]' value='".h(SERVER)."' title='".'hostname[:port] or :socket'."' placeholder='localhost' autocapitalize='off'>"),adminer()->loginFormField('username','<tr><th>'.'Username'.'<td>','<input name="auth[username]" id="username" autofocus value="'.h($_GET["username"]).'" autocomplete="username" autocapitalize="off">'.script("fire(qs('#username').form['auth[driver]'], 'change');")),adminer()->loginFormField('password','<tr><th>'.'Password'.'<td>','<input type="password" name="auth[password]" autocomplete="current-password">'),adminer()->loginFormField('db','<tr><th>'.'Database'.'<td>','<input name="auth[db]" value="'.h($_GET["db"]).'" autocapitalize="off">'),"</table>\n","<p><input type='submit' value='".'Login'."'>\n",checkbox("auth[permanent]",1,$_COOKIE["adminer_permanent"],'Permanent login')."\n";}function
loginFormField($B,$ff,$Y){return$ff.$Y."\n";}function
login($Pg,$E){if($E=="")return'Adminer does not support accessing a database without a password.'.require_password_link(null);if(!Driver::$passwords)return'The database does not support passwords.'.require_password_link($E);if(!password_required())return'The server accepts any password, so filling it in protects nothing.'.require_password_link($E);return
true;}function
tableName(array$rm){return
h($rm["Name"]);}function
fieldName(array$n,$Ki=0){$U=$n["full_type"].($n["null"]?" NULL":"");$f=$n["comment"];return'<span title="'.h($U.($f!=""?($U?": ":"").$f:'')).'">'.h($n["field"]).'</span>';}function
commentValue($U,$f){if($f==""||$U=='SCHEMA'||$U=='TABLE'||$U=='COLUMN')return
h($f);$Nj=function($Wk,$kb='td'){return
preg_replace('~^~m','<tr>',preg_replace('~\|~',"<$kb>",preg_replace('~\|$~m',"",rtrim($Wk))));};$R='(\+--[-+]+\+\n)';$J='(\| .* \|\n)';return"<pre>\n".preg_replace_callback("~^$R?$J$R?($J*)$R?~m",function($A)use($Nj){return"<table>\n".($A[1]?"<thead>".$Nj($A[2],'th')."<tbody>\n":$Nj($A[2])).$Nj($A[4])."\n</table>";},preg_replace('~(\n(    -|mysql)&gt; )(.+)~',"\\1<code class='jush-sql'>\\3</code>",preg_replace('~(.+)\n---+\n~',"<b>\\1</b>\n",h($f))))."</pre>\n";}function
commentInput($U,$c,$f){$Y=h($f);return(preg_match('~\n~',$Y)?"<textarea$c rows='2' cols='".($U=='TABLE'?20:30)."' style='vertical-align: bottom;'>\n$Y</textarea>":"<input$c value='$Y'>");}function
selectLinks(array$rm,$O=""){$B=$rm["Name"];echo'<p class="links">';$Kg=[];if($B!="")$Kg["select"]='Select data';if(support("table")||support("indexes"))$Kg["table"]='Show structure';if(support("table")){if(is_view($rm)){if(support("view"))$Kg["view"]='Alter view';}elseif(function_exists('Adminer\alter_table')&&$B!="")$Kg["create"]='Alter table';}if($O!==null)$Kg["edit"]='New item';foreach($Kg
as$x=>$X)echo" <a href='".h(ME)."$x=".url_escape($B).($x=="edit"?$O:"")."'".bold(isset($_GET[$x])).">$X</a>";echo"\n";}function
foreignKeys($R){return
foreign_keys($R);}function
backwardKeys($R,$qm){return[];}function
backwardKeysPrint(array$Ra,array$J){}function
selectQuery($G,$Zl,$Vd=false){$I="\n";if(!$Vd&&($ko=driver()->warnings())){$t="warnings";$I=", <a href='#$t' class='toggle'>".'Warnings'."</a>"."$I<div id='$t' class='hidden'>\n$ko</div>\n";}return"<p><code class='sql jush-".JUSH."'>".h(str_replace("\n"," ",$G))."</code> <span class='time'>(".format_time($Zl).")</span>".(support("sql")?" <a href='".h(ME)."sql=".url_escape($G)."' class='hover'>".'Edit'."</a>":"").$I;}function
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
tableStructurePrint(array$o,$rm=null){echo"<div class='scrollable'>\n","<table class='nowrap odds'>\n","<thead><tr><th>".'Column'."<th>".'Type'.(support("comment")?"<th>".'Comment':"")."<tbody>\n";$Un=(support("type")?types():[]);foreach($o
as$n){echo"<tr><th>".h($n["field"]).doc_link([JUSH=>$n["doc"]]);$U=h($n["full_type"]);$Fb=h($n["collation"]);echo"<td><span title='$Fb'>".(in_array($U,$Un)?"<a href='".h(ME.'type='.url_escape($U))."'>$U</a>":$U.($Fb&&isset($rm["Collation"])&&$Fb!=$rm["Collation"]?" $Fb":""))."</span>",($n["null"]?" <i>NULL</i>":""),($n["auto_increment"]?" <i>".'Auto Increment'."</i>":""),(isset($n["default"])?" <span title='".'Default value'."'>[<b>".($n["generated"]||$n["default_sql"]?"<code class='sql jush-".JUSH."'>".shorten_utf8(preg_replace('~\s+~',' ',ltrim($n["default"])),80,"</code>"):h($n["default"]))."</b>]</span>":""),(support("comment")?"<td>".adminer()->commentValue('COLUMN',$n["comment"]):""),"\n";}echo"</table>\n","</div>\n";}function
tableIndexesPrint(array$w,array$rm){$kj=false;foreach($w
as$B=>$v)$kj|=!!$v["partial"];echo"<table>\n";$_c=first(driver()->indexAlgorithms($rm));foreach($w
as$B=>$v){ksort($v["columns"]);$Wj=[];foreach($v["columns"]as$x=>$X)$Wj[]="<i>".h($X)."</i>".($v["lengths"][$x]?"(".h($v["lengths"][$x]).")":"").($v["descs"][$x]?" DESC":"");echo"<tr title='".h($B)."'>","<th>".h($v["type"]).($_c&&$v['algorithm']!=$_c?" (".h($v['algorithm']).")":""),"<td>".implode(", ",$Wj).($v["invalid"]?" <span class='error'>INVALID</span>":"");if($kj)echo"<td>".($v['partial']?"<code class='sql jush-".JUSH."'>WHERE ".h($v['partial']):"");echo"\n";}echo"</table>\n";}function
namePattern($U){if($U=="FOREIGN"||$U=="CHECK")return"";if($U=="TRIGGER")return"{table}_{timing}{event}";return(JUSH=="sql"?"":"{table}_")."{columns}";}function
selectColumnInput($c,array$e,$Y="",$Cj=""){return
select_input($c,$e,$Y,$Cj);}function
selectColumnsPrint(array$M,array$e){print_fieldset("select",'Select',$M);$s=0;$M[""]=[];foreach($M
as$x=>$X){$X=idx($_GET["columns"],$x,[]);$d=adminer()->selectColumnInput(" name='columns[$s][col]' data-default=''".on('change',($x!==""?'selectFieldChange':'selectAddRow')),$e,$X["col"]);echo"<div>".(driver()->functions||driver()->grouping?html_select("columns[$s][fun]",[-1=>""]+array_filter(['Functions'=>driver()->functions,'Aggregation'=>driver()->grouping]),$X["fun"]," data-default=''".on('change',($x!==""?'helpClose':'selectFunAddRow')).on_help_value(' (.*)|$','($1)'))."($d)":$d)."</div>\n";$s++;}echo"</div></fieldset>\n";}function
selectSearchPrint(array$Z,array$e,array$w,$rm=null){print_fieldset("search",'Search',$Z);foreach($w
as$s=>$v){if($v["type"]=="FULLTEXT")echo"<div>(<i>".implode("</i>, <i>",array_map('Adminer\h',$v["columns"]))."</i>) ".h(driver()->fulltextOperator)," <input type='search' name='fulltext[$s]' value='".h(idx($_GET["fulltext"],$s))."' data-default=''".on('input','selectFieldChange').">",(JUSH=='sql'?checkbox("boolean[$s]",1,isset($_GET["boolean"][$s]),"BOOL"):''),"</div>\n";}$Di=adminer()->operators($rm);foreach(array_merge((array)$_GET["where"],[[]])as$s=>$X){if(!$X||(("$X[col]$X[val]"!=""||preg_match('~NULL$~',$X["op"]))&&in_array($X["op"],$Di)))echo"<div>".adminer()->selectColumnInput(" name='where[$s][col]' data-default=''".on('change',($X?'selectFieldChange':'selectAddRow')),$e,$X["col"],"(".'anywhere'.")"),html_select("where[$s][op]",$Di,$X["op"]," data-default='".h(first($Di))."'".on('change','selectFirstChange')),"<input type='search' name='where[$s][val]' value='".h($X["val"])."' data-default=''".on('input','selectFirstChange').on('keydown','selectSearchKeydown').on('search','selectSearchSearch').">","</div>\n";}echo"</div></fieldset>\n";}function
selectOrderPrint(array$Ki,array$e,array$w){print_fieldset("sort",'Sort',$Ki);$s=0;foreach((array)$_GET["order"]as$x=>$X){if($X!=""){echo"<div>".adminer()->selectColumnInput(" name='order[$s]' data-default=''".on('change','selectFieldChange'),$e,$X),checkbox("desc[$s]",1,isset($_GET["desc"][$x]),'descending')."</div>\n";$s++;}}echo"<div>".adminer()->selectColumnInput(" name='order[$s]' data-default=''".on('change','selectAddRow'),$e),checkbox("desc[$s]",1,false,'descending')."</div>\n","</div></fieldset>\n";}function
selectLimitPrint($z){echo"<fieldset><legend>".'Limit'."</legend><div>","<input type='number' name='limit' class='size' value='".h($z?:"")."' data-default='50'".on('input','selectFieldChange').">","</div></fieldset>\n";}function
selectLengthPrint($Pm){echo"<fieldset><legend>".'Text length'."</legend><div>","<input type='number' name='text_length' class='size' value='".h($Pm)."' data-default='100'>","</div></fieldset>\n";}function
selectActionPrint(array$w){echo"<fieldset><legend>".'Action'."</legend><div>","<input type='submit' value='".'Select'."'>"," <span id='noindex' title='".'Full table scan'."'></span>","<script".nonce().">\n","const indexColumns = ";$e=[];foreach($w
as$v){$pc=reset($v["columns"]);if($v["type"]!="FULLTEXT"&&$pc)$e[$pc]=1;}$e[""]=1;foreach($e
as$x=>$X)json_row($x);echo";\n","selectFieldChange.call(qs('#form')['select']);\n","</script>\n","</div></fieldset>\n";}function
selectCommandPrint(){return!information_schema(DB);}function
selectImportPrint(){return!information_schema(DB);}function
selectEmailPrint(array$qd,array$e){}function
selectColumnsProcess(array$e,array$w){$M=[];$Me=[];foreach((array)$_GET["columns"]as$x=>$X){if($X["fun"]=="count"||($X["col"]!=""&&(!$X["fun"]||in_array($X["fun"],driver()->functions)||in_array($X["fun"],driver()->grouping)))){$M[$x]=apply_sql_function($X["fun"],($X["col"]!=""?idf_escape($X["col"]):"*"));if(!in_array($X["fun"],driver()->grouping))$Me[]=$M[$x];}}return[$M,$Me];}function
selectSearchProcess(array$o,array$w,$rm=null){$I=[];foreach($w
as$s=>$v){if($v["type"]=="FULLTEXT"&&idx($_GET["fulltext"],$s)!="")$I[]=driver()->fulltextSql($s,$v,$_GET["fulltext"][$s],isset($_GET["boolean"][$s]));}$Di=adminer()->operators($rm);foreach((array)$_GET["where"]as$x=>$X){$X+=["col"=>"","op"=>first($Di),"val"=>""];$_GET["where"][$x]=$X;$Db=$X["col"];if(("$Db$X[val]"!=""||preg_match('~NULL$~',$X["op"]))&&in_array($X["op"],$Di)){if($X["op"]=="SQL"&&(!$_POST||!verify_token()))SqlDb::$untrusted=true;$Qb=[];foreach(($Db!=""?[$Db=>idx($o,$Db,[])]:$o)as$B=>$n){if($Db!=""||is_searchable($n,$X))$Qb[]=adminer()->selectSearchCondition(driver()->convertSearch(idf_escape($B),$X,$n),$X["op"],$X["val"]);}$I[]=(count($Qb)==1?$Qb[0]:($Qb?"(".implode(" OR ",$Qb).")":"1 = 0"));}}return$I;}function
selectSearchCondition($d,$_i,$X){if(preg_match('~IN$~',$_i))return"$d $_i ".($X!=""?process_in($X):"(NULL)");if($_i=="BETWEEN")return"$d $_i ".process_between($X);if($_i=="SQL")return"$d $X";if(preg_match('~^(I?LIKE) %%$~',$_i,$A))return"$d $A[1] ".q("%$X%");if($_i=="FIND_IN_SET")return"$_i(".q($X).", $d)";return"$d $_i".(!preg_match('~NULL$~',$_i)?" ".q($X):"");}function
selectOrderProcess(array$o,array$w){$I=[];foreach((array)$_GET["order"]as$x=>$X){if($X!="")$I[]=(preg_match('~^((COUNT\(DISTINCT |[A-Z0-9_]+\()(`(?:[^`]|``)+`|"(?:[^"]|"")+")\)|COUNT\(\*\))$~',$X)?$X:idf_escape($X)).(isset($_GET["desc"][$x])?" DESC".(JUSH=='pgsql'&&idx($o[$X],"null")?" NULLS LAST":""):"");}return$I;}function
selectLimitProcess(){return(isset($_GET["limit"])?intval($_GET["limit"]):50);}function
selectLengthProcess(){return(isset($_GET["text_length"])?"$_GET[text_length]":"100");}function
selectEmailProcess(array$Z,array$te){return
false;}function
selectQueryBuild(array$M,array$Z,array$Me,array$Ki,$z,$D){return"";}function
executeQuery($G,$jb){return$jb();}function
messageQuery($G,$Rm,$Vd=false){restart_session();$lf=&get_session("queries");if(!idx($lf,$_GET["db"]))$lf[$_GET["db"]]=[];if(strlen($G)>1e6)$G=preg_replace('~[\x80-\xFF]+$~','',substr($G,0,1e6))."\n…";$lf[$_GET["db"]][]=[$G,time(),$Rm];$Ul="sql-".count($lf[$_GET["db"]]);$I="<a href='#$Ul' class='toggle'>".'SQL command'."</a> ".copy_icon()."\n";if(!$Vd&&($ko=driver()->warnings())){$t="warnings-".count($lf[$_GET["db"]]);$I="<a href='#$t' class='toggle'>".'Warnings'."</a>, $I<div id='$t' class='hidden'>\n$ko</div>\n";}return" <span class='time'>".@date("H:i:s")."</span>"." $I<div id='$Ul' class='hidden'><pre><code class='jush-".JUSH."'>".shorten_utf8($G,1e4)."</code></pre>".($Rm?" <span class='time'>($Rm)</span>":'').(support("sql")?'<p><a href="'.h(str_replace("db=".url_escape(DB),"db=".url_escape($_GET["db"]),ME).'sql=&history='.(count($lf[$_GET["db"]])-1)).'">'.'Edit'.'</a>':'').'</div>';}function
error(){return
error();}function
editRowPrint($R,array$o,$J,$Hn,$G='',$Rm=''){echo($G!=""?"<p><code class='sql jush-".JUSH."'>".h(str_replace("\n"," ",$G))."</code> <span class='time'>($Rm)</span>\n":"");}function
editFunctions(array$n){$I=($n["null"]?"NULL/":"");$bf=isset($_GET["select"])||where($_GET);foreach([driver()->insertFunctions,driver()->editFunctions]as$x=>$Ge){if(!$x||(!isset($_GET["call"])&&$bf)){foreach($Ge
as$zj=>$X){if(!$zj||preg_match("~$zj~",$n["type"]))$I
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
dumpTable($R,$hm,$fg=0){if($_POST["format"]!="sql"){echo"\xef\xbb\xbf";if($hm)dump_csv(array_keys(fields($R)));}else{if($fg==2){$o=[];foreach(fields($R)as$B=>$n)$o[]=idf_escape($B)." ".full_type_sql($n);$i="CREATE TABLE ".table($R)." (".implode(", ",$o).")";}else$i=create_sql($R,$_POST["auto_increment"],$hm);set_utf8mb4($i);if($hm&&$i){if(($hm=="DROP+CREATE"&&!function_exists('Adminer\drop_sql'))||$fg==1)echo"DROP ".($fg==2?"VIEW":"TABLE")." IF EXISTS ".table($R).";\n";if($fg==1)$i=remove_definer($i);echo"$i;\n\n";}}}function
dumpData($R,$hm,$G,array$M=[],array$Z=[],array$Me=[],array$Ki=[]){if($hm){$ah=(JUSH=="sqlite"?0:1048576);$o=[];$uf=false;if($_POST["format"]=="sql"){if($hm=="TRUNCATE+INSERT"&&!function_exists('Adminer\truncate_all_sql'))echo
truncate_sql($R).";\n";$o=fields($R);if(JUSH=="mssql"){foreach($o
as$n){if($n["auto_increment"]){echo"SET IDENTITY_INSERT ".table($R)." ON;\n";$uf=true;break;}}}}$H=($G!=""?connection()->query($G,1):driver()->select($R,($M?:["*"]),$Z,$Me,$Ki,0));if($H){$Qf="";$cb="";$og=[];$He=[];$jm="";$Zd=($R!=''?'fetch_assoc':'fetch_row');$hc=0;while($J=$H->$Zd()){if(!$og){$bo=[];foreach($J
as$X){$n=$H->fetch_field();if(idx($o[$n->name],'generated')){$He[$n->name]=true;continue;}$og[]=$n->name;$x=idf_escape($n->name);$bo[]="$x = VALUES($x)";}$jm=($hm=="INSERT+UPDATE"?"\nON DUPLICATE KEY UPDATE ".implode(", ",$bo):"").";\n";}foreach($J
as$x=>$X){if(is_array($X))$J[$x]=json_encode($X,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);}if($_POST["format"]!="sql"){if($hm=="table"){dump_csv($og);$hm="INSERT";}dump_csv($J);}else{if(!$Qf)$Qf="INSERT INTO ".table($R)." (".implode(", ",array_map('Adminer\idf_escape',$og)).") VALUES";foreach($J
as$x=>$X){if($He[$x]){unset($J[$x]);continue;}$n=$o[$x];$J[$x]=($X===null?"NULL":($X===false?0:unconvert_field($n,preg_match(number_type(),$n["type"])&&!preg_match('~\[~',$n["full_type"])&&is_numeric($X)?$X:(!is_blob($n)||is_utf8($X)?q($X):driver()->quoteBinary($X)))));}$Wk=($ah?"\n":" ")."(".implode(",\t",$J).")";if(!$cb)$cb=$Qf.$Wk;elseif(JUSH=='mssql'?$hc%1000!=0:strlen($cb)+4+strlen($Wk)+strlen($jm)<$ah)$cb
.=",$Wk";else{echo$cb.$jm;$cb=$Qf.$Wk;}}$hc++;}if($cb)echo$cb.$jm;}elseif($_POST["format"]=="sql")echo"-- ".str_replace("\n"," ",connection()->error)."\n";else
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
navigation($yh){echo"<h1>".adminer()->name()." <span class='version'>".VERSION;$Zh=$_COOKIE["adminer_version"];echo" <a href='https://www.adminer.org/#download'".target_blank()." id='version'>".(version_compare(VERSION,$Zh)<0?h($Zh):"").version_iframe()."</a>","</span></h1>\n";if($yh=="auth"){$Zi="";foreach((array)$_SESSION["pwds"]as$do=>$Al){foreach($Al
as$N=>$Vn){$B=h(get_setting("vendor-$do-$N")?:get_driver($do));foreach($Vn
as$V=>$E){if($B&&$E!==null){$yc=$_SESSION["db"][$do][$N][$V];foreach(($yc?array_keys($yc):[""])as$k)$Zi
.="<li><a href='".h(auth_url($do,$N,$V,$k))."'>($B) ".h("$V@").($N!=""?adminer()->serverName($N):"").h($k!=""?" - $k":"")."</a>\n";}}}}if($Zi)echo"<ul id='logins'".on('mouseover','menuOver').on('mouseout','menuOut').">\n$Zi</ul>\n";}else{$T=[];if($_POST&&!$yh&&DB!=""){connection()->select_db(DB);if(support("scheme")&&$_GET["ns"]!="")set_schema($_GET["ns"]);}if($_GET["ns"]!==""&&!$yh&&DB!="")$T=table_status('',true);adminer()->syntaxHighlighting($T);adminer()->databasesPrint($yh);$ja=[];if(DB==""||!$yh){if(support("sql")){$ja['sql']="<a href='".h(ME)."sql='".bold(isset($_GET["sql"])&&!isset($_GET["import"])).">".'SQL command'."</a>";$ja['import']="<a href='".h(ME)."import='".bold(isset($_GET["import"])).">".'Import'."</a>";}$ja['dump']="<a href='".h(ME)."dump=".url_escape(isset($_GET["table"])?$_GET["table"]:$_GET["select"])."' id='dump'".bold(isset($_GET["dump"])).">".'Export'."</a>";}$_f=$_GET["ns"]!==""&&!$yh&&DB!="";if($_f&&function_exists('Adminer\alter_table'))$ja['create']='<a href="'.h(ME).'create="'.bold($_GET["create"]==="").">".'Create table'."</a>";$ja=adminer()->menuActions($ja,$yh);echo($ja?"<p class='links'>\n".implode("\n",$ja)."\n":"");$fl=[];if($_f){if(function_exists('Adminer\alter_table'))$fl[]=['Create table',ME."create="];if(support("view"))$fl[]=['Create view',ME."view="];$Yd=['routine'=>['routines','Routines'],'sequence'=>['sequences','Sequences'],'type'=>['user-types','User types'],'event'=>['events','Events'],];foreach($Yd
as$Xd=>$el){if(support($Xd))$fl[]=[$el[1],substr(ME,0,-1)."#$el[0]"];}}if(DB!=""&&!$yh&&support("extension"))$fl[]=['Extensions',preg_replace('~\bns=[^&]*&~','',ME)."ns=#extensions"];echo($fl?script("jumpSections.unshift(...".json_encode($fl,JSON_UNESCAPED_UNICODE).");"):"");if($_f){if($T)adminer()->tablesPrint($T);else
echo"<p class='message'>".'No tables.'."</p>\n";}}}function
syntaxHighlighting(array$T){echo
script_src(preg_replace("~\\?.*~","",ME)."?file=jush.js&version=6.3.0+9afc53aa",true);$Ah=preg_replace('~<(?=/script)~i','<\\',Driver::jushModule());echo($Ah?script("addEventListener('DOMContentLoaded', () => {\n$Ah\n});"):"");if(support("sql")){echo"<script".nonce().">\n";if($T){$Kg=[];foreach($T
as$R=>$U)$Kg[]=js_escape_re($R);echo"var jushLinks = { ".JUSH.":";json_row(js_escape(ME).(support("table")?"table":"select").'=$&','/\b(?<!\$)('.implode('|',$Kg).')(?!\$)\b/g',false);$Wl=["sql","check","event","procedure","trigger","view","type","table","processlist"];if(support("routine")&&array_intersect_key($_GET,array_flip($Wl))){foreach(routines()as$J)json_row(js_escape(ME).'function='.url_escape($J["SPECIFIC_NAME"]).'&name=$&','/\b'.js_escape_re($J["ROUTINE_NAME"]).'(?=["`\]]?\()/g',false);}json_row('');echo"};\n";foreach(["bac","bra","sqlite_quo","mssql_bra"]as$X)echo"jushLinks.$X = jushLinks.".JUSH.";\n";if(array_intersect_key($_GET,array_flip(["sql","check","event","procedure","trigger","view"]))){$cm=(isset($_GET["trigger"])?['INSERT INTO','UPDATE','DELETE FROM']:(isset($_GET["check"])?[]:(isset($_GET["view"])?['SELECT']:null)));$Na=Driver::jushAutocomplete($T,$cm);echo($Na?"addEventListener('DOMContentLoaded', () => { autocompleter = $Na; });\n":"");}}echo"</script>\n";}echo
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
__construct($Gj){$bd=SqlDriver::$drivers;$hf=" href='https://www.adminer.org/plugins/#use'".target_blank();if($Gj===null){$Gj=[];$Va="adminer-plugins";if(is_dir($Va)){foreach(glob("$Va/*.php")as$p){$fe=SqlDriver::$drivers;$this->includeOnce($p);foreach(array_diff_key(SqlDriver::$drivers,$fe)as$t=>$B)$this->driverFiles[$t]=$p;}}if(file_exists("$Va.php")){$Bf=$this->includeOnce("$Va.php");if(is_array($Bf)){foreach($Bf
as$x=>$Dj)$Gj[is_object($Dj)?get_class($Dj):$x]=$Dj;}else$this->error
.=sprintf('%s must <a%s>return an array</a>.',"<b>$Va.php</b>",$hf)."<br>";}foreach(get_declared_classes()as$_b){if(!$Gj[$_b]&&(preg_match('~^Adminer\w~i',$_b)||is_subclass_of($_b,'Adminer\Plugin'))){$vk=new
\ReflectionClass($_b);$Yb=$vk->getConstructor();if($Yb&&$Yb->getNumberOfRequiredParameters())$this->error
.=sprintf('<a%s>Configure</a> %s in %s.',$hf,"<b>$_b</b>","<b>$Va.php</b>")."<br>";else$Gj[$_b]=new$_b;}}}$Vf=array_filter($Gj,function($Dj){return!is_object($Dj);});if($Vf){$this->error
.=sprintf('Every plugin must <a%s>be an object</a>.',$hf)."<br>";$Gj=array_diff_key($Gj,$Vf);}$nm=get_setting("plugins","adminer_config");foreach($Gj
as$x=>$Dj){if($Dj
instanceof
Plugin&&$Dj::$optional){$am=idx($nm,get_class($Dj));if($am!==null?!$am:!$Dj->enabled){$this->disabled[]=$Dj;unset($Gj[$x]);}}}$this->drivers=array_diff_key(SqlDriver::$drivers,$bd);$this->plugins=$Gj;$oa=new
Adminer;$Gj[]=$oa;$vk=new
\ReflectionObject($oa);foreach($vk->getMethods()as$vh){foreach($Gj
as$Dj){$B=$vh->getName();if(method_exists($Dj,$B))$this->hooks[$B][]=$Dj;}}}function
includeOnce($p){return
include_once"./$p";}static
function
pluginName($Dj){return
preg_replace('~^ Adminer\\\\? ~','',preg_replace('~[A-Z]~',' $0',get_class($Dj)));}static
function
describe($Dj){$Jc=(method_exists($Dj,'description')?$Dj->description():"");if(!$Jc){$vk=new
\ReflectionObject($Dj);if(preg_match('~^/[\s*]+(.+?)(?:\s*\*/)?$~m',$vk->getDocComment(),$A))$Jc=$A[1];}$bl=(method_exists($Dj,'screenshot')?$Dj->screenshot():"");return
h($Jc).($bl?($Jc?" ":"")."(<a href='".h($bl)."'".target_blank().">".'screenshot'."</a>)":"");}static
function
checksum($p){$ee=str_replace("\r","",file_get_contents($p));$ee=preg_replace('~\n\tprotected \$translations = array\(.*?\n\t\);~s','',$ee);return
dechex(crc32($ee));}function
checksums(){$ge=array_values($this->driverFiles);foreach($this->plugins
as$Dj){$vk=new
\ReflectionObject($Dj);$ge[]=$vk->getFileName();}$I=[];foreach($ge
as$p)$I[basename($p,'.php')]=self::checksum($p);return$I;}static
function
officialChecksums(){return['adminer.js'=>'a0599090','backward-keys'=>'1c017d59','before-unload'=>'9674d220','config'=>'7f179b4f','dark-switcher'=>'8c201319','database-hide'=>'e304a899','designs'=>'c0a56b18','donation'=>'365aa745','dump-alter'=>'896b579e','dump-bz2'=>'c0d6de78','dump-date'=>'512aad63','dump-json'=>'f8aa6b99','dump-xml'=>'aedd2d3a','dump-zip'=>'96882e78','edit-foreign'=>'d04a0ac8','edit-textarea'=>'a178d7d9','editor-setup'=>'a7dc3a37','editor-views'=>'5c12b185','enum-option'=>'e9005cb0','file-upload'=>'10add0e8','foreign-system'=>'634ce119','frames'=>'b0e1d11a','highlight-codemirror'=>'ada4b569','highlight-monaco'=>'95cf93b3','highlight-prism'=>'19933b19','import-csv'=>'bcecfad2','login-ip'=>'4d174fea','login-otp'=>'66bd013d','login-passkey'=>'a73c386f','login-password-less'=>'e150daac','login-reverse-proxy'=>'24558ea2','login-servers'=>'19c42e45','login-ssl'=>'2b06a5b7','login-table'=>'811f8cef','menu-links'=>'55b9b15c','name-patterns'=>'84c10d09','remote-color'=>'c5e74631','row-numbers'=>'1ad1b6cf','select-email'=>'c33a2661','select-expression'=>'b6a4b3f1','select-foreign'=>'255d4532','select-image'=>'3cefeee4','slugify'=>'7b422f0e','sql-gemini'=>'c60ab309','sql-log'=>'b9aa4ea6','sql-url-length'=>'cd70f1a0','table-indexes-structure'=>'e0b1433','table-structure'=>'f71ec61b','tables-filter'=>'2729bf7','timeout'=>'9e9bce60','version-github'=>'627cadf9','version-noverify'=>'966937e9','clickhouse'=>'a5a5e5b4','elastic'=>'b7e4a70d','firebird'=>'5f3d9493','igdb'=>'39c028f6','imap'=>'35808394','mongo'=>'e6e3303b','redis'=>'c2f04eae','simpledb'=>'9eb464cf',];}function
__call($B,array$gj){$Da=[];foreach($gj
as$x=>$X)$Da[]=&$gj[$x];$I=null;foreach($this->hooks[$B]as$Dj){$Y=call_user_func_array([$Dj,$B],$Da);if($Y!==null){if(!self::$append[$B])return$Y;$I=$Y+(array)$I;}}return$I;}}abstract
class
Plugin{static$optional=false;var$enabled=true;protected$translations=[];function
disable(){$this->enabled=false;return$this;}function
description(){return$this->lang('');}function
screenshot(){return"";}protected
function
lang($u,$hi=null){$Da=func_get_args();$Da[0]=idx($this->translations[LANG],$u)?:$u;return
call_user_func_array('Adminer\lang_format',$Da);}}class
Password{private$password_hash;private$password_matches=null;function
__construct($vj){$this->password_hash=$vj;}function
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
attach(array$N,$V,$E){mysqli_report(MYSQLI_REPORT_OFF);$Hj=$N["port"];$sd=("$N[host]$Hj$N[socket]"=="");$Yl=adminer()->connectSsl();$Rn=($Yl&&($Yl['key']||$Yl['cert']||$Yl['ca']||isset($Yl['verify'])));if($Rn)$this->ssl_set($Yl['key'],$Yl['cert'],$Yl['ca'],'','');$I=@$this->real_connect((!$sd?$N["host"]:ini_get("mysqli.default_host")),(!$sd||$V!=""?$V:ini_get("mysqli.default_user")),(!$sd||$V.$E!=""?$E:ini_get("mysqli.default_pw")),null,($Hj!=""?intval($Hj):ini_get("mysqli.default_port")),($Hj!=""?null:$N["socket"]),($Rn?($Yl['verify']!==false?MYSQLI_CLIENT_SSL:64):0));$this->options(MYSQLI_OPT_LOCAL_INFILE,0);return($I?'':$this->error);}function
set_charset($pb){if($this->send_charset($pb))return
true;$this->send_charset('utf8');return$this->query("SET NAMES $pb");}private
function
send_charset($pb){return
adminer()->executeQuery("SET NAMES $pb",function()use($pb){return
parent::set_charset($pb);});}function
select_db($uc){return
adminer()->executeQuery("USE ".idf_escape($uc),function()use($uc){return
parent::select_db($uc);});}function
query($G,$Hk=MYSQLI_STORE_RESULT){return
adminer()->executeQuery($G,function()use($G,$Hk){return
parent::query($G,$Hk);});}function
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
sprintf('Disable %s or enable the %s or %s extension.',"'mysql.allow_local_infile'","MySQLi","PDO_MySQL");$Hj="$N[port]$N[socket]";$B=$N["host"].($Hj!=""?":$Hj":"");$this->link=@mysql_connect(($B!=""?$B:ini_get("mysql.default_host")),($B.$V!=""?$V:ini_get("mysql.default_user")),($B.$V.$E!=""?$E:ini_get("mysql.default_password")),true,131072);if(!$this->link)return
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
query($G,$xn=false){$H=adminer()->executeQuery($G,function()use($G,$xn){return@($xn?mysql_unbuffered_query($G,$this->link):mysql_query($G,$this->link));});$this->error="";if(!$H){$this->errno=mysql_errno($this->link);$this->error=mysql_error($this->link);return
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
attach(array$N,$V,$E){$Hi=[\PDO::MYSQL_ATTR_LOCAL_INFILE=>false];if(isset($_GET["select"]))$Hi[1012]=false;$Yl=adminer()->connectSsl();if($Yl){if($Yl['key'])$Hi[\PDO::MYSQL_ATTR_SSL_KEY]=$Yl['key'];if($Yl['cert'])$Hi[\PDO::MYSQL_ATTR_SSL_CERT]=$Yl['cert'];if($Yl['ca'])$Hi[\PDO::MYSQL_ATTR_SSL_CA]=$Yl['ca'];if(isset($Yl['verify']))$Hi[\PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT]=$Yl['verify'];}$of=$N["host"];$Hj=$N["port"];$Kl=$N["socket"];return$this->dsn("mysql:charset=utf8".($of!=""?";host=$of":'').($Hj!=""?";port=$Hj":($Kl!=""?";unix_socket=$Kl":"")),$V,$E,$Hi);}function
set_charset($pb){return$this->query("SET NAMES $pb");}function
select_db($uc){return$this->query("USE ".idf_escape($uc));}function
query($G,$xn=false){$this->pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY,!$xn);return
parent::query($G,$xn);}}}class
Driver
extends
SqlDriver{static$extensions=["MySQLi","MySQL","PDO_MySQL"];static$jush="sql";static$serverSocket=true;var$unsigned=["unsigned","zerofill","unsigned zerofill"];var$functions=["char_length","date","from_unixtime","lower","round","floor","ceil","sec_to_time","time_to_sec","upper"];var$grouping=["avg","count","count distinct","group_concat","max","min","sum"];var$partitionBy=["HASH","LINEAR HASH","KEY","LINEAR KEY","RANGE","LIST"];function
operators($rm){return["=","<",">","<=",">=","!=","&","LIKE","LIKE %%","REGEXP","IN","BETWEEN","FIND_IN_SET","IS NULL","NOT LIKE","NOT REGEXP","NOT IN","IS NOT NULL","SQL"];}static
function
connect($N,$V,$E){$g=parent::connect($N,$V,$E);if(is_string($g)){if(function_exists('iconv')&&!is_utf8($g)&&strlen($Wk=iconv("windows-1252","utf-8//IGNORE",$g))>strlen($g))$g=$Wk;return$g;}$g->set_charset(charset($g));$g->query("SET sql_quote_show_create = 1, autocommit = 1");$g->flavor=(preg_match('~MariaDB~',$g->server_info)?'maria':'mysql');add_driver(DRIVER,($g->flavor=='maria'?"MariaDB":"MySQL"));return$g;}function
__construct(Db$g){parent::__construct($g);$this->types=['Numbers'=>["tinyint"=>3,"smallint"=>5,"mediumint"=>8,"int"=>10,"bigint"=>20,"decimal"=>66,"float"=>12,"double"=>21],'Date and time'=>["date"=>10,"datetime"=>19,"timestamp"=>19,"time"=>10,"year"=>4],'Strings'=>["char"=>255,"varchar"=>65535,"tinytext"=>255,"text"=>65535,"mediumtext"=>16777215,"longtext"=>4294967295],'Lists'=>["enum"=>65535,"set"=>64],'Binary'=>["bit"=>20,"binary"=>255,"varbinary"=>65535,"tinyblob"=>255,"blob"=>65535,"mediumblob"=>16777215,"longblob"=>4294967295],'Geometry'=>["geometry"=>0,"point"=>0,"linestring"=>0,"polygon"=>0,"multipoint"=>0,"multilinestring"=>0,"multipolygon"=>0,"geometrycollection"=>0],];$this->insertFunctions=["char"=>"md5/sha1/password/encrypt/uuid","binary"=>"md5/sha1","date|time"=>"now",];$this->editFunctions=[number_type()=>"+/-","date"=>"+ interval/- interval","time"=>"addtime/subtime","char|text"=>"concat",];if(min_version('5.7.8',10.2,$g))$this->types['Strings']["json"]=4294967295;if(min_version('',10.7,$g)){$this->types['Strings']["uuid"]=128;$this->insertFunctions['uuid']='uuid';}if(min_version('',10.5,$g)){$this->types['Network']["inet6"]=39;if(min_version('','10.10',$g))$this->types['Network']["inet4"]=15;}if(min_version(9,11.7,$g))$this->types['Numbers']["vector"]=16383;if(min_version(5.7,10.2,$g))$this->generated=["STORED","VIRTUAL"];}function
unconvertFunction(array$n){return(preg_match("~binary~",$n["type"])?"<code class='jush-sql'>UNHEX</code>":($n["type"]=="bit"?doc_link(['sql'=>'bit-value-literals.html'],"<code>b''</code>"):($n["type"]=="vector"?"<code class='jush-sql'>".($this->conn->flavor=='maria'?"VEC_FromText":"STRING_TO_VECTOR")."</code>":(preg_match("~geom|point|linestring|polygon~",$n["type"])?"<code class='jush-sql'>GeomFromText</code>":""))));}function
insert($R,array$O){return($O?parent::insert($R,$O):queries("INSERT INTO ".table($R)." ()\nVALUES ()"));}function
insertUpdate($R,array$K,array$Uj){$e=array_keys(reset($K));$Pj="INSERT INTO ".table($R)." (".implode(", ",$e).") VALUES\n";$bo=[];foreach($e
as$x)$bo[$x]="$x = VALUES($x)";$jm="\nON DUPLICATE KEY UPDATE ".implode(", ",$bo);$bo=[];$y=0;foreach($K
as$O){$Y="(".implode(", ",$O).")";if($bo&&(strlen($Pj)+$y+strlen($Y)+strlen($jm)>1e6)){if(!queries($Pj.implode(",\n",$bo).$jm))return
false;$bo=[];$y=0;}$bo[]=$Y;$y+=strlen($Y)+2;}return
queries($Pj.implode(",\n",$bo).$jm);}function
slowQuery($G,$Sm){if(min_version('5.7.8','10.1.2')){if($this->conn->flavor=='maria')return"SET STATEMENT max_statement_time=$Sm FOR $G";elseif(preg_match('~^(SELECT\b)(.+)~is',$G,$A))return"$A[1] /*+ MAX_EXECUTION_TIME(".($Sm*1000).") */ $A[2]";}}function
convertColumn($u,array$n){if(preg_match("~binary~",$n["type"]))return"HEX($u)";if($n["type"]=="bit")return"BIN($u + 0)";if($n["type"]=="vector")return($this->conn->flavor=='maria'?"VEC_ToText":"VECTOR_TO_STRING")."($u)";if(preg_match("~geom|point|linestring|polygon~",$n["type"]))return(min_version(8)?"ST_":"")."AsWKT($u)";return"";}function
convertSearch($u,array$X,array$n){return($this->convertColumn($u,$n)?:(preg_match('~'.text_type().'~',$n["type"])&&!preg_match("~^utf8~",$n["collation"])&&preg_match('~[\x80-\xFF]~',$X['val'])?"CONVERT($u USING ".charset($this->conn).")":$u));}function
typeName(\stdClass$n){$B=parent::typeName($n);if($B!=""){$wn=["TINY"=>"tinyint","SHORT"=>"smallint","LONG"=>"int","INT24"=>"mediumint","LONGLONG"=>"bigint","NEWDECIMAL"=>"decimal","VAR_STRING"=>"varchar","STRING"=>"char",];return
idx($wn,$B,strtolower($B));}$wn=["decimal","tinyint","smallint","int","float","double",7=>"timestamp","bigint","mediumint","date","time","datetime","year",15=>"varchar","bit",242=>"vector",245=>"json","decimal","enum","set","tinytext","mediumtext","longtext","text","varchar","char","geometry",];$I=idx($wn,$n->type,"");return($n->charsetnr==63?str_replace(["text","varchar","char"],["blob","varbinary","binary"],$I):$I);}function
quoteBinary($Wk){return"X".q(bin2hex($Wk));}function
md5($d,array$n){if(is_blob($n)||preg_match('~'.text_type().'~',$n["type"]))return"MD5(".(is_blob($n)||preg_match("~^utf8~",$n["collation"])?$d:"CONVERT($d USING ".charset($this->conn).")").")";}function
warnings(){$H=$this->conn->query("SHOW WARNINGS");if($H&&$H->num_rows){ob_start();print_select_result($H);return
ob_get_clean();}}function
errorLine($G){return(preg_match('~ at line (\d+)$~',$this->conn->error,$A)?(int)$A[1]:0);}function
tableHelp($B,$fg=false){$Rg=($this->conn->flavor=='maria');if(information_schema(DB))return
strtolower(str_replace("_","-",DB)."-".($Rg?"$B-table/":str_replace("_","-",$B)."-table.html"));if(DB=="sys")return($Rg?"sys-schema/":strtolower("sys-".str_replace("_","-",preg_replace('~^x\$~','',$B)).".html"));if(DB=="mysql")return($Rg?"mysql$B-table/":"system-schema.html");}function
partitionsInfo($R){$Ae="FROM information_schema.PARTITIONS WHERE TABLE_SCHEMA = ".q(DB)." AND TABLE_NAME = ".q($R);$H=$this->conn->query("SELECT PARTITION_METHOD, PARTITION_EXPRESSION, PARTITION_ORDINAL_POSITION $Ae ORDER BY PARTITION_ORDINAL_POSITION DESC LIMIT 1");$J=($H?$H->fetch_row():null);if(!$J)return[];$I=[];list($I["partition_by"],$I["partition"],$I["partitions"])=$J;$rj=get_key_vals("SELECT PARTITION_NAME, PARTITION_DESCRIPTION $Ae AND PARTITION_NAME != '' ORDER BY PARTITION_ORDINAL_POSITION");$I["partition_names"]=array_keys($rj);$I["partition_values"]=array_values($rj);return$I;}function
checkConstraints($R){$I=parent::checkConstraints($R);return($this->conn->flavor=='maria'?$I:array_map('stripslashes',$I));}function
hasCStyleEscapes(){static$fb;if($fb===null){$Vl=get_val("SHOW VARIABLES LIKE 'sql_mode'",1,$this->conn);$fb=(strpos($Vl,'NO_BACKSLASH_ESCAPES')===false);}return$fb;}function
hasEstimatedRows(){return
true;}function
isSystem($k,$L=""){return
information_schema($k,$L)||in_array($k,["mysql","sys"]);}function
lineComment(){return"#|-- ";}function
engines(){$I=[];foreach(get_rows("SHOW ENGINES")as$J){if(preg_match("~YES|DEFAULT~",$J["Support"]))$I[]=$J["Engine"];}return$I;}function
indexAlgorithms(array$rm){return(preg_match('~^(MEMORY|NDB)$~',$rm["Engine"])?["HASH","BTREE"]:[]);}}function
idf_escape($u){return"`".str_replace("`","``",$u)."`";}function
table($u){return
idf_escape($u);}function
get_databases($qe){$I=get_session("dbs");if($I===null){$G="SELECT SCHEMA_NAME FROM information_schema.SCHEMATA ORDER BY SCHEMA_NAME";$Zl=microtime(true);$I=($qe?slow_query($G):get_vals($G));if(microtime(true)-$Zl>0.1){restart_session();set_session("dbs",$I);stop_session();}}return$I;}function
limit($G,$Z,$z,$C=0,$ol=" "){return" $G$Z".($z?$ol."LIMIT $z".($C?" OFFSET $C":""):"");}function
limit1($R,$G,$Z,$ol="\n"){return
limit($G,$Z,1,0,$ol);}function
db_collation($k,array$Gb){static$I;if($I===null)$I=get_key_vals("SELECT SCHEMA_NAME, DEFAULT_COLLATION_NAME FROM information_schema.SCHEMATA");return$I[$k];}function
logged_user(){return
get_val("SELECT CURRENT_USER()");}function
tables_list(){return
get_key_vals("SELECT TABLE_NAME, TABLE_TYPE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME");}function
count_tables(array$j){$I=[];foreach($j
as$k)$I[$k]=count(get_vals("SHOW TABLES IN ".idf_escape($k)));return$I;}function
table_status($B="",$Wd=false){$I=[];$G="SELECT ENGINE AS Engine, TABLE_NAME AS Name, TABLE_COMMENT AS Comment FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() ".($B!=""?"AND TABLE_NAME = ".q($B):"ORDER BY Name");$L=[];foreach(($Wd?[]:get_rows($G))as$J)$L[$J["Name"]]=$J;$Tj=null;foreach(get_rows($Wd?$G:"SHOW TABLE STATUS".($B!=""?" LIKE ".q(addcslashes($B,"%_\\")):""))as$J){$Vi=idx($L,$J["Name"]);if($Vi){if($J["Comment"]!==$Vi["Comment"]&&$J["Comment"]!==$Tj)$J["Error"]=$J["Comment"];$Tj=$J["Comment"];$J["Comment"]=$Vi["Comment"];$J["Engine"]=$Vi["Engine"];}if($J["Engine"]=="InnoDB")$J["Comment"]=preg_replace('~(?:(.+); )?InnoDB free: .*~','\1',$J["Comment"]);if(!isset($J["Engine"]))$J["Comment"]="";if($B!="")$J["Name"]=$B;$I[$J["Name"]]=$J;}return$I;}function
is_view(array$S){return$S["Engine"]===null;}function
fk_support(array$S){return
preg_match('~InnoDB|IBMDB2I'.(min_version(5.6)?'|NDB':'').'~i',$S["Engine"]);}function
parse_type($De){preg_match('~^([^( ]+)(?:\((.+)\))?( unsigned)?( zerofill)?$~',$De,$A);return[$A[1],$A[2],ltrim($A[3].$A[4])];}function
fields($R){$Rg=(connection()->flavor=='maria');$I=[];foreach(get_rows("SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ".q($R)." ORDER BY ORDINAL_POSITION")as$J){$n=$J["COLUMN_NAME"];$U=$J["COLUMN_TYPE"];$Ie=$J["GENERATION_EXPRESSION"];$Td=$J["EXTRA"];preg_match('~^(VIRTUAL|PERSISTENT|STORED)~',$Td,$He);list($vn,$y,$Fn)=parse_type($U);$l=$J["COLUMN_DEFAULT"];$Bc=false;if($l!=""){$mk=$Rg;if($Rg)$Bc=!preg_match("~^('(?:[^'\\\\]|\\\\.|'')*'|NULL|[-+]?[\\d.]+)$~s",$l);elseif(preg_match('~text|json~',$vn)||preg_match('~\bDEFAULT_GENERATED\b~',$Td)){$l=stripslashes($l);$mk=preg_match("~^(_\\w+)?('(?:[^'\\\\]|\\\\.|'')*')$~s",$l,$A);if($mk)$l=$A[2];else$Bc=true;}elseif(preg_match("~^[bx]'[\\da-f]*'$~i",$l))$Bc=true;if($mk)$l=($l=="NULL"?null:(preg_match("~^'.*'$~s",$l)?stripslashes(idf_unescape($l)):$l));if(preg_match('~binary~',$vn)&&preg_match("~^(?:0x(\\w*)|x'(\\w*)')$~",$l,$A)){$l=pack("H*",$A[1].idx($A,2));$Bc=false;}}$I[$n]=["field"=>$n,"full_type"=>$U,"type"=>$vn,"length"=>$y,"unsigned"=>$Fn,"default"=>($He?($Rg?$Ie:stripslashes($Ie)):$l),"default_sql"=>(!$He&&$Bc),"null"=>($J["IS_NULLABLE"]=="YES"),"auto_increment"=>($Td=="auto_increment"),"on_update"=>(preg_match('~\bon update current_timestamp\b~i',$Td)?"CURRENT_TIMESTAMP":""),"collation"=>$J["COLLATION_NAME"],"privileges"=>array_flip(explode(",","$J[PRIVILEGES],where,order")),"comment"=>$J["COLUMN_COMMENT"],"primary"=>($J["COLUMN_KEY"]=="PRI"),"generated"=>($He[1]=="PERSISTENT"?"STORED":$He[1]),];}return$I;}function
indexes($R,$h=null){$I=[];foreach(get_rows("SHOW INDEX FROM ".table($R),$h)as$J){$B=$J["Key_name"];$I[$B]["type"]=($B=="PRIMARY"?"PRIMARY":($J["Index_type"]=="FULLTEXT"?"FULLTEXT":($J["Non_unique"]?(preg_match('~^(SPATIAL|VECTOR)$~',$J["Index_type"])?$J["Index_type"]:"INDEX"):"UNIQUE")));$I[$B]["columns"][]=$J["Column_name"];$I[$B]["lengths"][]=($J["Index_type"]=="SPATIAL"?null:$J["Sub_part"]);$I[$B]["descs"][]=null;$I[$B]["algorithm"]=$J["Index_type"];}return$I;}function
foreign_keys($R){static$zj='(?:`(?:[^`]|``)+`|"(?:[^"]|"")+")';$I=[];$ic=get_val("SHOW CREATE TABLE ".table($R),1);if($ic){preg_match_all("~CONSTRAINT ($zj) FOREIGN KEY ?\\(((?:$zj,? ?)+)\\) REFERENCES ($zj)(?:\\.($zj))? \\(((?:$zj,? ?)+)\\)(?: ON DELETE (".driver()->onActions."))?(?: ON UPDATE (".driver()->onActions."))?~",$ic,$Ug,PREG_SET_ORDER);foreach($Ug
as$A){preg_match_all("~$zj~",$A[2],$Ol);preg_match_all("~$zj~",$A[5],$Im);$I[idf_unescape($A[1])]=["db"=>idf_unescape($A[4]!=""?$A[3]:$A[4]),"table"=>idf_unescape($A[4]!=""?$A[4]:$A[3]),"source"=>array_map('Adminer\idf_unescape',$Ol[0]),"target"=>array_map('Adminer\idf_unescape',$Im[0]),"on_delete"=>($A[6]?:"RESTRICT"),"on_update"=>($A[7]?:"RESTRICT"),];}}return$I;}function
view($B){return["select"=>preg_replace('~^(?:[^`]|`[^`]*`)*\s+AS\s+~isU','',get_val("SHOW CREATE VIEW ".table($B),1))];}function
collations(){$I=[];foreach(get_rows("SHOW COLLATION")as$J)$I[$J["Charset"]][]=$J["Collation"];ksort($I);foreach($I
as$x=>$X)sort($I[$x]);return$I;}function
information_schema($k,$L=""){return($k=="information_schema")||(min_version(5.5)&&$k=="performance_schema");}function
error(){return
h(preg_replace('~^You have an error.*syntax to use~U',"Syntax error",connection()->error));}function
create_database($k,$Fb){return
queries("CREATE DATABASE ".idf_escape($k).($Fb?" COLLATE ".q($Fb):""));}function
drop_databases(array$j){$I=apply_queries("DROP DATABASE",$j,'Adminer\idf_escape');restart_session();set_session("dbs",null);return$I;}function
rename_database($B,$Fb){$I=false;if(create_database($B,$Fb)){$T=[];$go=[];foreach(tables_list()as$R=>$U){if($U=='VIEW')$go[]=$R;else$T[]=$R;}$I=(!$T&&!$go)||move_tables($T,$go,$B);drop_databases($I?[DB]:[]);}return$I;}function
auto_increment(){$Ma=" PRIMARY KEY";if($_GET["create"]!=""&&$_POST["auto_increment_col"]){foreach(indexes($_GET["create"])as$v){if(in_array($_POST["fields"][$_POST["auto_increment_col"]]["orig"],$v["columns"],true)){$Ma="";break;}if($v["type"]=="PRIMARY")$Ma=" UNIQUE";}}return" AUTO_INCREMENT$Ma";}function
alter_table($R,$B,array$o,array$se,$f,$vd,$Fb,$La,$qj){$b=[];foreach($o
as$n){if($n[1]){$l=$n[1][3];if(preg_match('~ GENERATED~',$l)){$n[1][3]=(connection()->flavor=='maria'?"":$n[1][2]);$n[1][2]=$l;}$b[]=($R!=""?($n[0]!=""?"CHANGE ".idf_escape($n[0]):"ADD"):" ")." ".implode($n[1]).($R!=""?$n[2]:"");}else$b[]="DROP ".idf_escape($n[0]);}$b=array_merge($b,$se);$P=($f!==null?" COMMENT=".q($f):"").($vd?" ENGINE=".q($vd):"").($Fb?" COLLATE ".q($Fb):"").($La!=""?" AUTO_INCREMENT=$La":"");if($qj){$rj=[];if($qj["partition_by"]=='RANGE'||$qj["partition_by"]=='LIST'){foreach($qj["partition_names"]as$x=>$X){$Y=$qj["partition_values"][$x];$rj[]="\n  PARTITION ".idf_escape($X)." VALUES ".($qj["partition_by"]=='RANGE'?"LESS THAN":"IN").($Y!=""?" ($Y)":" MAXVALUE");}}$P
.="\nPARTITION BY $qj[partition_by]($qj[partition])";if($rj)$P
.=" (".implode(",",$rj)."\n)";elseif($qj["partitions"])$P
.=" PARTITIONS ".(+$qj["partitions"]);}elseif($qj===null)$P
.="\nREMOVE PARTITIONING";if($R=="")return
queries("CREATE TABLE ".table($B)." (\n".implode(",\n",$b)."\n)$P");if($R!=$B)$b[]="RENAME TO ".table($B);if($P)$b[]=ltrim($P);return($b?queries("ALTER TABLE ".table($R)."\n".implode(",\n",$b)):true);}function
alter_indexes($R,$b){$nb=[];foreach($b
as$X)$nb[]=($X[2]=="DROP"?"\nDROP INDEX ".idf_escape($X[1]):"\nADD $X[0] ".($X[0]=="PRIMARY"?"KEY ":"").($X[1]!=""?idf_escape($X[1])." ":"")."(".implode(", ",$X[2]).")");return
queries("ALTER TABLE ".table($R).implode(",",$nb));}function
truncate_tables(array$T){return
apply_queries("TRUNCATE TABLE",$T);}function
drop_views(array$go){return
queries("DROP VIEW ".implode(", ",array_map('Adminer\table',$go)));}function
drop_tables(array$T){return
queries("DROP TABLE ".implode(", ",array_map('Adminer\table',$T)));}function
move_tables(array$T,array$go,$Im){$Ak=[];foreach($T
as$R)$Ak[]=table($R)." TO ".idf_escape($Im).".".table($R);if(!$Ak||queries("RENAME TABLE ".implode(", ",$Ak))){$Fc=[];foreach($go
as$R)$Fc[table($R)]=view($R);connection()->select_db($Im);$k=idf_escape(DB);foreach($Fc
as$B=>$fo){if(!queries("CREATE VIEW $B AS ".str_replace(" $k."," ",$fo["select"]))||!queries("DROP VIEW $k.$B"))return
false;}return
true;}return
false;}function
copy_tables(array$T,array$go,$Im){queries("SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO'");foreach($T
as$R){$B=($Im==DB?table("copy_$R"):idf_escape($Im).".".table($R));if(($_POST["overwrite"]&&!queries("\nDROP TABLE IF EXISTS $B"))||!queries("CREATE TABLE $B LIKE ".table($R))||!queries("INSERT INTO $B SELECT * FROM ".table($R)))return
false;foreach(get_rows("SHOW TRIGGERS LIKE ".q(addcslashes($R,"%_\\")))as$J){$ln=$J["Trigger"];list($Fd,$ki)=trigger_event($J);if(!queries("CREATE TRIGGER ".($Im==DB?idf_escape("copy_$ln"):idf_escape($Im).".".idf_escape($ln))." $J[Timing] $Fd".($ki!=""?" $ki":"")." ON $B FOR EACH ROW\n$J[Statement];"))return
false;}}foreach($go
as$R){$B=($Im==DB?table("copy_$R"):idf_escape($Im).".".table($R));$fo=view($R);if(($_POST["overwrite"]&&!queries("DROP VIEW IF EXISTS $B"))||!queries("CREATE VIEW $B AS $fo[select]"))return
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
as$J){$De=$J["DTD_IDENTIFIER"];list($vn,$y,$Fn)=parse_type($De);$o[]=["field"=>$J["PARAMETER_NAME"],"type"=>$vn,"length"=>$y,"unsigned"=>$Fn,"null"=>true,"full_type"=>$De,"inout"=>($U=="FUNCTION"?"":$J["PARAMETER_MODE"]),"collation"=>$J["COLLATION_NAME"],];}$I=connection()->query("SELECT
	ROUTINE_COMMENT comment,
	ROUTINE_DEFINITION definition,
	LOWER(EXTERNAL_LANGUAGE) language,
	IF(DEFINER = CURRENT_USER(), '', DEFINER) definer,
	IF(IS_DETERMINISTIC = 'YES', 'DETERMINISTIC', 'NOT DETERMINISTIC') is_deterministic,
	SQL_DATA_ACCESS data_access,
	CONCAT('SQL SECURITY ', SECURITY_TYPE) security
FROM information_schema.ROUTINES
WHERE ROUTINE_SCHEMA = DATABASE() AND ROUTINE_TYPE = '$U' AND ROUTINE_NAME = ".q($B))->fetch_assoc();if(!$I)return[];$I['options']=["DEFINER"=>$I['definer'],"DETERMINISTIC"=>$I['is_deterministic'],"SQL_DATA_ACCESS"=>$I['data_access'],"SQL_SECURITY"=>$I['security'],"COMMENT"=>$I['comment'],];if($o&&$o[0]['field']=='')$I['returns']=array_shift($o);$I['fields']=$o;return$I;}function
routines(){return
get_rows("SELECT SPECIFIC_NAME, ROUTINE_NAME, ROUTINE_TYPE, DTD_IDENTIFIER FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA = DATABASE()");}function
routine_languages(){return(min_version(9,99)?["sql"=>"sql","javascript"=>"js"]:[]);}function
routine_options($Nk){return["DEFINER"=>[],"DETERMINISTIC"=>["NOT DETERMINISTIC","DETERMINISTIC"],"SQL_DATA_ACCESS"=>["CONTAINS SQL","NO SQL","READS SQL DATA","MODIFIES SQL DATA"],"SQL_SECURITY"=>["SQL SECURITY DEFINER","SQL SECURITY INVOKER"],"COMMENT"=>[],];}function
routine_id($B,array$J){return
idf_escape($B);}function
last_id($H){return
get_val("SELECT LAST_INSERT_ID()");}function
explain(Db$g,$G){return$g->query("EXPLAIN ".(min_version(5.7)?"":"PARTITIONS ").$G);}function
found_rows(array$S,array$Z){return($Z||$S["Engine"]!="InnoDB"?null:$S["Rows"]);}function
create_sql($R,$La,$hm){$I=get_val("SHOW CREATE TABLE ".table($R),1);if(!$La)$I=preg_replace('~(\n\)[^\n]*?) AUTO_INCREMENT=\d+~','\1',$I);return$I;}function
truncate_sql($R){return"TRUNCATE ".table($R);}function
use_sql($uc,$hm=""){$B=idf_escape($uc);$I="";if(preg_match('~CREATE~',$hm)&&($i=get_val("SHOW CREATE DATABASE $B",1))){set_utf8mb4($i);if($hm=="DROP+CREATE")$I="DROP DATABASE IF EXISTS $B;\n";$I
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
unconvert_field(array$n,$I){if(preg_match("~binary~",$n["type"]))$I="UNHEX($I)";if($n["type"]=="bit")$I="CONVERT(b$I, UNSIGNED)";if($n["type"]=="vector")$I=(connection()->flavor=='maria'?"VEC_FromText":"STRING_TO_VECTOR")."($I)";if(preg_match("~geom|point|linestring|polygon~",$n["type"])){$Pj=(min_version(8)?"ST_":"");$I=$Pj."GeomFromText($I, $Pj"."SRID($n[field]))";}return$I;}function
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
page_header($Um,$m="",$bb=[],$Vm="",$di=false,$Xc=""){if($di){header("HTTP/1.1 404 Not Found");$m=($m?:'Not found.');}page_headers();if(is_ajax()&&$m){page_messages($m);exit;}if(!ob_get_level())ob_start('ob_gzhandler',4096);$Wm=$Um.($Vm!=""?": $Vm":"");$Xm=strip_tags($Wm.(SERVER!=""&&SERVER!="localhost"?h(" - ".SERVER):"")." - ".adminer()->name());echo'<!DOCTYPE html>
<html lang=\'en\' dir=\'ltr\' class=\'ltr nojs\'>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="robots" content="noindex">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>',$Xm,'</title>
<link rel="stylesheet" href="',h(preg_replace("~\\?.*~","",ME)."?file=default.css&version=6.3.0+9afc53aa"),'">
';$mc=adminer()->css();if(is_int(key($mc)))$mc=array_fill_keys($mc,'light');$Ye=in_array('light',$mc)||in_array('',$mc);$We=in_array('dark',$mc)||in_array('',$mc);$qc=($Ye?($We?null:false):($We?:null));$jh=" media='(prefers-color-scheme: dark)'";if($qc!==false)echo"<link rel='stylesheet'".($qc?"":$jh)." href='".h(preg_replace("~\\?.*~","",ME)."?file=dark.css&version=6.3.0+9afc53aa")."'>\n";echo"<meta name='color-scheme' content='".($qc===null?"light dark":($qc?"dark":"light"))."'>\n",script_src(preg_replace("~\\?.*~","",ME)."?file=functions.js&version=6.3.0+9afc53aa");if(adminer()->head($qc))echo"<link rel='icon' href='data:image/gif;base64,"."R0lGODlhEAAQAJEAAAQCBPz+/PwCBAROZCH5BAEAAAAALAAAAAAQABAAAAI2hI+pGO1rmghihiUdvUBnZ3XBQA7f05mOak1RWXrNq5nQWHMKvuoJ37BhVEEfYxQzHjWQ5qIAADs='>\n","<link rel='apple-touch-icon' href='".h(preg_replace("~\\?.*~","",ME)."?file=logo.svg&version=6.3.0+9afc53aa")."'>\n";if(adminer()->manifest())echo"<link rel='manifest' href='".h(preg_replace('~\?.*~','',ME)."?manifest=")."' crossorigin='use-credentials'>\n";foreach($mc
as$Mn=>$zh){$c=($zh=='dark'&&!$qc?$jh:($zh=='light'&&$We?" media='(prefers-color-scheme: light)'":""));echo"<link rel='stylesheet'$c href='".h($Mn)."'>\n";}echo"\n<body class='";adminer()->bodyClass();echo"'>\n",script((isset($_COOKIE["adminer_version"])||!adminer()->verifyVersion()?"":"onload = partial(verifyVersion, '".VERSION."');\n")."
const offlineMessage = '".js_escape('You are offline.')."';
const numberFormat = '".js_escape('#,##0')."';
const numberDigits = '".js_escape('0123456789')."';
const urlSeparators = '".js_escape(ini_get("arg_separator.input"))."';"),"<div id='help' class='jush-".JUSH." jsonly hidden'".on('mouseover','helpKeep').on('mouseout','helpMouseout')."></div>\n","<div id='content'>\n","<span id='menuopen' class='jsonly'".on('click','menuToggle')."><button title='".'Menu'."' class='icon icon-move' aria-expanded='false'></button></span>\n";if($bb!==null){$jg=(class_exists('Adminer\Db')&&connection());$_=substr(preg_replace('~\b(username|db|ns)=[^&]*&~','',ME),0,-1);echo'<p id="breadcrumb">'.($jg?jump_input():'').'<a href="'.h($_?:".").'">'.get_driver(DRIVER).'</a> » ';$_=substr(preg_replace('~\b(db|ns)=[^&]*&~','',ME),0,-1);$N=adminer()->serverName(SERVER);$N=($N!=""?$N:'Server');if($bb===false)echo"$N\n";else{echo"<a href='".h($_.(DB!=""&&support("single_db")?"&db=":""))."' accesskey='1' title='Alt+Shift+1'>$N</a> » ";$el="";if(is_string($bb)){$el=$bb;$bb=[];}if($_GET["ns"]!=""||(DB!=""&&is_array($bb))){$wc="$_&db=".url_escape(DB).(support("scheme")?"&ns=":"").(support("single_table")?"&select=":"");echo'<a href="'.h($wc.($_GET["ns"]==""?$el:"")).'">'.h(DB).'</a> » ';}if(is_array($bb)){if($_GET["ns"]!="")echo'<a href="'.h(substr(ME,0,-1).$el).'">'.h($_GET["ns"]).'</a> » ';foreach($bb
as$x=>$X){$Ic=(is_array($X)?$X[1]:h($X));if($Ic!="")echo"<a href='".h(ME."$x=").url_escape(is_array($X)?$X[0]:$X)."'>$Ic</a> » ";}}echo"$Um\n";}if($jg){echo"<form id='jump-form'>\n";hidden_fields_get();echo(DB!=""?input_hidden("db",DB):""),(isset($_GET["ns"])?input_hidden("ns",$_GET["ns"]):""),"</form>\n",script("jumpSetup();");}}echo"<h2>$Wm$Xc</h2>\n","<div id='ajaxstatus' role='status' class='jsonly'></div>\n";restart_session();page_messages($m);adminer()->serviceWorker();$j=&get_session("dbs");if(DB!=""&&$j&&!in_array(DB,$j,true))$j=null;stop_session();define('Adminer\PAGE_HEADER',1);ob_flush();flush();if($di){page_footer($di===true?"":$di);exit;}}function
service_worker(){$yk=has_passwords();$Cb=($yk?"navigator.serviceWorker.register('".js_escape(preg_replace('~\?.*~','',ME)."?file=worker.js&version=6.3.0+9afc53aa")."', {scope: location.pathname}).catch(() => {});":"navigator.serviceWorker.getRegistration().then(registration => registration && registration.unregister());
	caches.keys().then(keys => keys.forEach(key => key.startsWith('adminer-') && caches.delete(key)));");echo
script("if (navigator.serviceWorker) {\n\t$Cb\n}");}function
has_passwords(){foreach((array)$_SESSION["pwds"]as$Al){foreach($Al
as$Vn){foreach($Vn
as$E){if($E!==null)return
true;}}}return
false;}function
page_headers(){header("Content-Type: text/html; charset=utf-8");header("Cache-Control: no-cache");header("X-Frame-Options: deny");header("X-XSS-Protection: 0");header("X-Content-Type-Options: nosniff");header("Referrer-Policy: origin-when-cross-origin");foreach(adminer()->csp(csp())as$lc){$df=[];foreach($lc
as$x=>$X)$df[]="$x $X";header("Content-Security-Policy: ".implode("; ",$df));}adminer()->headers();}function
csp(){return[["script-src"=>"'self' 'unsafe-inline' 'nonce-".get_nonce()."' 'strict-dynamic'","connect-src"=>"'self' https://www.adminer.org","frame-src"=>"https://www.adminer.org","object-src"=>"'none'","base-uri"=>"'none'","form-action"=>"'self'",],];}function
design_checksums(){$Sn=[];foreach(array_keys(adminer()->css())as$Mn)$Sn[preg_replace('~\?.*~','',$Mn)]=true;$I=[];foreach(["adminer.css","adminer-dark.css"]as$p){if($Sn[$p]&&file_exists($p)){preg_match('~^/\* Adminer design ([-\w]+) \*/~',file_get_contents($p),$A);$I[$p]=[(string)$A[1],Plugins::checksum($p)];}}return$I;}function
official_design_checksums(){return['adminer-dark/adminer-dark.css'=>'a26bcd7b','brade/adminer.css'=>'be4161f0','bueltge/adminer.css'=>'1a8f00b4','cpanel/adminer.css'=>'59ce604e','dracula/adminer-dark.css'=>'cfaf61dd','esterka/adminer.css'=>'1f805f36','flat/adminer.css'=>'49a61af9','galkaev/adminer-dark.css'=>'16c46f94','haeckel/adminer.css'=>'147a3565','hever/adminer.css'=>'c9d1d11c','konya/adminer.css'=>'2b409696','lavender-light/adminer.css'=>'bf03f5d7','lucas-sandery/adminer.css'=>'6596353','mancave/adminer-dark.css'=>'d9169f12','mvt/adminer.css'=>'ebd3afdc','nette/adminer.css'=>'a8babfc5','ng9/adminer.css'=>'488583cf','nicu/adminer.css'=>'a38b3b6a','pappu687/adminer.css'=>'b58d128c','paranoiq/adminer.css'=>'64d27e5','pepa-linha/adminer.css'=>'baf25f0','pokorny/adminer.css'=>'ee9eea6d','price/adminer.css'=>'81be9a85','rmsoft/adminer.css'=>'6cd4a237','rmsoft_blue-dark/adminer.css'=>'32102a8','rmsoft_blue/adminer.css'=>'7d8d5b18','win98/adminer.css'=>'e82d63c3',];}function
version_iframe(){return(isset($_COOKIE["adminer_version"])||!adminer()->verifyVersion()?"":"<noscript><iframe sandbox src='https://www.adminer.org/version/?current=".VERSION."&amp;noscript=1'></iframe></noscript>");}function
get_nonce(){static$ci;if(!$ci)$ci=base64_encode(rand_string());return$ci;}function
page_messages($m){$Ln=preg_replace('~^[^?]*~','',$_SERVER["REQUEST_URI"]);$qh=idx($_SESSION["messages"],$Ln);if($qh){echo"<div class='message'>".implode("</div>\n<div class='message'>",$qh)."</div>".script("messagesPrint();");unset($_SESSION["messages"][$Ln]);}if($m)echo"<div class='error'>$m</div>\n";if(adminer()->error)echo"<div class='error'>".adminer()->error."</div>\n";}function
page_footer($yh=""){echo"</div>\n\n<div id='foot' class='foot'>\n<div id='menu'>\n";adminer()->navigation($yh);echo"</div>\n";if($yh!="auth")echo'<form method="post">
<p class="logout">
<span title="Username">',h($_GET["username"])."\n",'</span>
<input type=\'submit\' name=\'logout\' value=\'Logout\' id=\'logout\'>
',adminer()->donation()."\n",input_token(),'</form>
';echo"</div>\n\n",script("setupSubmitHighlight(document);");}function
int32($Hh){while($Hh>=2147483648)$Hh-=4294967296;while($Hh<=-2147483649)$Hh+=4294967296;return(int)$Hh;}function
long2str(array$W,$io){$Wk='';foreach($W
as$X)$Wk
.=pack('V',$X);if($io)return
substr($Wk,0,end($W));return$Wk;}function
str2long($Wk,$io){$W=array_values(unpack('V*',str_pad($Wk,4*ceil(strlen($Wk)/4),"\0")));if($io)$W[]=strlen($Wk);return$W;}function
xxtea_mx($uo,$to,$km,$lg){return
int32((($uo>>5&0x7FFFFFF)^$to<<2)+(($to>>3&0x1FFFFFFF)^$uo<<4))^int32(($km^$to)+($lg^$uo));}function
encrypt_string($em,$x){if($em=="")return"";$x=array_values(unpack("V*",pack("H*",md5($x))));$W=str2long($em,true);$Hh=count($W)-1;$uo=$W[$Hh];$to=$W[0];$fk=floor(6+52/($Hh+1));$km=0;while($fk-->0){$km=int32($km+0x9E3779B9);$id=$km>>2&3;for($bj=0;$bj<$Hh;$bj++){$to=$W[$bj+1];$Gh=xxtea_mx($uo,$to,$km,$x[$bj&3^$id]);$uo=int32($W[$bj]+$Gh);$W[$bj]=$uo;}$to=$W[0];$Gh=xxtea_mx($uo,$to,$km,$x[$bj&3^$id]);$uo=int32($W[$Hh]+$Gh);$W[$Hh]=$uo;}return
long2str($W,false);}function
decrypt_string($em,$x){if($em=="")return"";if(!$x)return
false;$x=array_values(unpack("V*",pack("H*",md5($x))));$W=str2long($em,false);$Hh=count($W)-1;$uo=$W[$Hh];$to=$W[0];$fk=floor(6+52/($Hh+1));$km=int32($fk*0x9E3779B9);while($km){$id=$km>>2&3;for($bj=$Hh;$bj>0;$bj--){$uo=$W[$bj-1];$Gh=xxtea_mx($uo,$to,$km,$x[$bj&3^$id]);$to=int32($W[$bj]-$Gh);$W[$bj]=$to;}$uo=$W[$Hh];$Gh=xxtea_mx($uo,$to,$km,$x[$bj&3^$id]);$to=int32($W[0]-$Gh);$W[0]=$to;$km=int32($km-0x9E3779B9);}return
long2str($W,true);}$Bj=[];if($_COOKIE["adminer_permanent"]){foreach(explode(" ",$_COOKIE["adminer_permanent"])as$X){list($x)=explode(":",$X);$Bj[$x]=$X;}}function
add_invalid_login(){$Ta=get_temp_dir()."/adminer-invalid";foreach(glob("$Ta*")?:[$Ta]as$p){$ye=file_open_lock($p);if($ye)break;}if(!$ye)$ye=file_open_lock("$Ta-".rand_string());if(!$ye)return;$Xf=json_decode(stream_get_contents($ye),true);$Rm=time();if($Xf){foreach($Xf
as$Yf=>$X){if($X[0]<$Rm)unset($Xf[$Yf]);}}$Vf=&$Xf[adminer()->bruteForceKey()];if(!$Vf)$Vf=[$Rm+30*60,0];$Vf[1]++;file_write_unlock($ye,json_encode($Xf));}function
check_invalid_login(array&$Bj){$Xf=[];foreach(glob(get_temp_dir()."/adminer-invalid*")as$p){$ye=file_open_lock($p);if($ye){$Xf=json_decode(stream_get_contents($ye),true);file_unlock($ye);break;}}$x=adminer()->bruteForceKey();$Vf=idx($Xf,$x,[]);$bi=($Vf[1]>29?$Vf[0]-time():0);if($bi>0){$m=lang_format(['Too many unsuccessful logins, try again in %d minute.','Too many unsuccessful logins, try again in %d minutes.'],ceil($bi/60));if($_SERVER["HTTP_X_FORWARDED_FOR"]!=""&&$x==$_SERVER["REMOTE_ADDR"])$m
.='<br>'.sprintf('Use the %s <a%s>plugin</a> if Adminer runs behind a reverse proxy.','<b>login-reverse-proxy</b>'," href='https://www.adminer.org/plugins/?version=".VERSION."'".target_blank());auth_error($m,$Bj,false);}}function
password_required(){static$I;if($I===null){$I=(bool)get_session("password_required");if(!$I){$kc=adminer()->credentials();$I=!is_object(Driver::connect($kc[0],$kc[1],""));if($I)set_session("password_required",true);}}return$I;}function
require_password_link($E){$Ch="<a href='https://www.adminer.org/password/'".target_blank().">".'More options'."</a>";if(!function_exists('password_hash'))return" $Ch";$Ej=($E!==null?$E:base64_encode(substr(hex2bin(rand_string()),0,12)));$cf=password_hash($Ej,PASSWORD_DEFAULT);$p="adminer-plugins.php";$Md=file_exists("adminer-plugins.php");if($Md)$Tf=($E!==null?sprintf('Add this line to %s to require the entered password:',"<b>$p</b>"):sprintf('Add this line to %s to require the password %s:',"<b>$p</b>","<b>$Ej</b>"));else{$p="<button name='password_less' value='".h($cf)."' class='link'>$p</button>";$Tf=($E!==null?sprintf('Save %s next to Adminer to require the entered password:',$p):sprintf('Save %s next to Adminer to require the password %s:',$p,"<b>$Ej</b>"));}$Ig="\t<a>new</a> Adminer\\Password(<span class='jush-apo'>'".h($cf)."'</span>),";$I="<p>$Tf
<pre><code class='jush'>".($Md?$Ig:"&lt;?php\n<a>return</a> <a>array</a>(\n$Ig\n);")."</code></pre>
<p>$Ch
";return" <a href='#password-less' class='toggle'>".'Require a password.'."</a>
<div id='password-less' class='hidden'>".($Md?$I:"<form method='post'>\n".$I.input_token()."</form>")."</div>";}if(preg_match('~^[-\w$./]+$~',$_POST["password_less"])&&verify_token()){header("Content-Type: application/octet-stream");header("Content-Disposition: attachment; filename=adminer-plugins.php");echo"<?php\nreturn array(\n\tnew Adminer\\Password('$_POST[password_less]'),\n);\n";exit;}$Ka=$_POST["auth"];if($Ka&&(!adminer()->verifyLoginToken()||verify_token())){session_regenerate_id();$do=$Ka["driver"];$N=$Ka["server"];$V=$Ka["username"];$E=(string)$Ka["password"];$k=$Ka["db"];set_password($do,$N,$V,$E);$_SESSION["db"][$do][$N][$V][$k]=true;if($Ka["permanent"]){$x=implode("-",array_map('base64_encode',[$do,$N,$V,$k]));$Xj=adminer()->permanentLogin(true);$Bj[$x]="$x:".base64_encode($Xj?encrypt_string($E,$Xj):"");cookie("adminer_permanent",implode(" ",$Bj));}if(!array_diff(array_keys($_POST),["auth","token"])||$do!=DRIVER||$N!=SERVER||$V!==$_GET["username"]||$k!=DB)redirect(auth_url($do,$N,$V,$k));}elseif($_POST["logout"]&&(!$_SESSION["token"]||verify_token())){Driver::disconnect();foreach(["pwds","db","dbs","queries"]as$x)set_session($x,null);unset_permanent($Bj);redirect(substr(preg_replace('~\b(username|db|ns)=[^&]*&~','',ME),0,-1),'Logout successful.'.' '.sprintf('Thanks for using Adminer. Consider <a%s>donating</a>.'," href='https://www.adminer.org/donation/'".target_blank()));}elseif($Bj&&!$_SESSION["pwds"]){session_regenerate_id();$Xj=adminer()->permanentLogin();foreach($Bj
as$x=>$X){list(,$zb)=explode(":",$X);list($do,$N,$V,$k)=array_map('base64_decode',explode("-",$x));set_password($do,$N,$V,decrypt_string(base64_decode($zb),$Xj));$_SESSION["db"][$do][$N][$V][$k]=true;}}function
unset_permanent(array&$Bj){foreach($Bj
as$x=>$X){list($do,$N,$V,$k)=array_map('base64_decode',explode("-",$x));if($do==DRIVER&&$N==SERVER&&$V==$_GET["username"]&&$k==DB)unset($Bj[$x]);}cookie("adminer_permanent",implode(" ",$Bj));}function
auth_error($m,array&$Bj,$Wf=true){$Bl=session_name();if(isset($_GET["username"])){header("HTTP/1.1 403 Forbidden");if(($_COOKIE[$Bl]||$_GET[$Bl])&&!$_SESSION["token"])$m='Session expired. Please log in again.';elseif($Wf&&($E=get_password())!==null){restart_session();add_invalid_login();if($E===false)$m
.=($m?'<br>':'').sprintf('Master password expired. <a href="https://www.adminer.org/en/extension/"%s>Implement</a> the %s method to make it permanent.',target_blank(),'<code>permanentLogin()</code>');set_password(DRIVER,SERVER,$_GET["username"],null);unset_permanent($Bj);}}if(!$_COOKIE[$Bl]&&$_GET[$Bl]&&ini_bool("session.use_only_cookies"))$m='Session support must be enabled.';$gj=session_get_cookie_params();cookie("adminer_key",($_COOKIE["adminer_key"]?:rand_string()),$gj["lifetime"]);if(!$_SESSION["token"])$_SESSION["token"]=rand(1,1e6);page_header('Login',$m,null);echo"<form method='post'>\n","<div>";if(hidden_fields($_POST,["auth","token"]))echo"<p class='message'>".'The action will be performed after successful login with the same credentials.'."\n";echo
input_token(),"</div>\n";adminer()->loginForm();echo"</form>\n";page_footer("auth");exit;}if(isset($_GET["username"])&&!class_exists('Adminer\Db')){unset($_SESSION["pwds"][DRIVER]);unset_permanent($Bj);page_header('No extension',sprintf('None of the supported PHP extensions (%s) are available.',implode(", ",Driver::$extensions)),false);page_footer("auth");exit;}$g='';if(isset($_GET["username"])&&is_string(get_password())){check_invalid_login($Bj);$kc=adminer()->credentials();$g=Driver::connect($kc[0],$kc[1],$kc[2]);if(is_object($g)){Db::$instance=$g;Driver::$instance=new
Driver($g);if($g->flavor)save_settings(["vendor-".DRIVER."-".SERVER=>get_driver(DRIVER)]);}}$Pg=null;if(!is_object($g)||($Pg=adminer()->login($_GET["username"],get_password()))!==true){$m=(is_string($g)?nl_br(h($g)):(is_string($Pg)?$Pg:'Invalid credentials.')).(preg_match('~^ | $~',get_password())?'<br>'.'There is a space in the entered password, which might be the cause.':'');auth_error($m,$Bj);}if($_POST["logout"]&&$_SESSION["token"]&&!verify_token()){page_header('Logout','Invalid CSRF token. Submit the form again.');page_footer("db");exit;}if(!$_SESSION["token"])$_SESSION["token"]=rand(1,1e6);stop_session(true);$m='';if($_POST){if(!verify_token()){header("HTTP/1.1 403 Forbidden");$m='Invalid CSRF token. Submit the form again.'.' '.'If you did not send this request from Adminer, close this page.';}}elseif($_SERVER["REQUEST_METHOD"]=="POST"){header("HTTP/1.1 413 Content Too Large");$m=sprintf('The POST data is too large. Reduce the data or increase the %s configuration directive.',"<b>post_max_size</b>");if(isset($_GET["sql"]))$m
.=' '.'You can upload a large SQL file via FTP and import it from the server.';}function
print_select_result($H,$h=null,array$Oi=[],&$z=0,&$jd=false){$Kg=[];$w=[];$e=[];$T=[];$Uj=[];$nd=[];$wn=[];$I=[];$_h=$jd;$jd=false;for($s=0;(!$z||$s<$z)&&is_array($J=$H->fetch_row());$s++){if(!$s){echo"<div class='scrollable'>\n","<table class='nowrap odds'".($_h?on('click','tableClick').on('dblclick','tableClick').on('keydown','editingKeydown'):"").">\n","<thead><tr>";for($hg=0;$hg<count($J);$hg++){$n=$H->fetch_field();$B=$n->name;$R=(isset($n->table)?$n->table:"");$Ni=(isset($n->orgtable)?$n->orgtable:"");$Mi=(isset($n->orgname)?$n->orgname:$B);$vn=driver()->typeName($n);if($Oi&&JUSH=="sql")$Kg[$hg]=($B=="table"?"table=":($B=="possible_keys"?"indexes=":null));elseif($Ni!=""){$ua=($R!=""?$R:$Ni);if($R!="")$I[$R]=$Ni;if(!isset($w[$ua])){if(!isset($Uj[$Ni])){$Uj[$Ni]=[];foreach(indexes($Ni,$h)as$v){if($v["type"]=="PRIMARY"){$Uj[$Ni]=array_flip($v["columns"]);break;}}}$T[$ua]=$Ni;$w[$ua]=$Uj[$Ni];$e[$ua]=$Uj[$Ni];}if(isset($e[$ua][$Mi])){unset($e[$ua][$Mi]);$w[$ua][$Mi]=$hg;$Kg[$hg]=$ua;}elseif($_h&&isset($n->orgname)&&$n->db==DB&&!is_blob(["type"=>$vn]))$nd[$hg]=[$ua,$Mi,preg_match('~text|json|lob~',$vn)];}$wn[$hg]=$vn;echo"<th title='".h(trim(($Ni!=""?"$Ni.$Mi":($n->name!=$Mi?$Mi:""))." ".$vn))."'>".h($B).($Oi?doc_link(['sql'=>"explain-output.html#explain_".strtolower($B),'mariadb'=>"explain/#the-columns-in-explain-select",]):"");}foreach($nd
as$hg=>$kb){if($e[$kb[0]])unset($nd[$hg]);}echo"<tbody>\n";}$vf=[];foreach($w
as$ua=>$v){if($v&&!$e[$ua]){$u="";foreach($v
as$Db=>$hg){if($J[$hg]===null){$u=null;break;}$u
.="&where[".url_escape(bracket_escape($Db))."]=".url_escape($J[$hg]);}$vf[$ua]=$u;}}echo"<tr>";foreach($J
as$x=>$X){$_="";if(isset($Kg[$x])){if($Oi&&JUSH=="sql"){$R=$J[array_search("table=",$Kg)];$_=ME.$Kg[$x].url_escape($Oi[$R]!=""?$Oi[$R]:$R);}elseif(idx($vf,$Kg[$x])!==null)$_=ME."edit=".url_escape($T[$Kg[$x]]).$vf[$Kg[$x]];}$qf=select_value($X,$_,['type'=>(preg_match('~binary~',$wn[$x])?'blob':$wn[$x])],null);$c="";$kb=idx($nd,$x);if($kb&&idx($vf,$kb[0])!==null&&is_utf8($X)){$jd=true;$c=" data-name='".h("val[".bracket_escape($T[$kb[0]])."][".bracket_escape(substr($vf[$kb[0]],1))."][".bracket_escape($kb[1])."]")."' data-text='".($kb[2]?1:0)."'".(html_entity_decode(strip_tags($qf),ENT_QUOTES,"UTF-8")!==$X?" data-value='".h($X)."'":"");}echo"<td".(preg_match(number_type(),$wn[$x])?" class='number'":"")."$c>$qf";}}$z=$s;echo($s?"</table>\n</div>":"<p class='message'>".'No rows.')."\n";return$I;}function
textarea($B,$Y,$K=10,$Hb=80,$kg=JUSH,$Ad=0){echo"<textarea name='".h($B)."' rows='$K' cols='$Hb' class='sqlarea jush-".h($kg)."' spellcheck='false' wrap='off'".($Ad?" data-error-line='$Ad'":"").">\n";if(is_array($Y)){foreach($Y
as$X)echo
h($X[0])."\n\n\n";}else
echo
h($Y);echo"</textarea>";}function
select_input($c,array$Hi,$Y="",$Cj=""){if($Hi&&$Y!=""&&!isset($Hi[$Y]))$Hi=[$Y=>$Y]+$Hi;$Hm=($Hi?"select":"input");return"<$Hm$c".($Hi?"><option value=''>$Cj".optionlist($Hi,$Y,true)."</select>":" size='10' value='".h($Y)."' placeholder='$Cj'>");}function
json_row($x,$X=null,$Ed=true){static$ke=true;if($ke)echo"{";if($x!=""){echo($ke?"":",")."\n\t\"".addcslashes($x,"\r\n\t\"\\/").'": '.($X!==null?($Ed?'"'.addcslashes($X,"\r\n\"\\/").'"':$X):'null');$ke=false;}else{echo"\n}\n";$ke=true;}}function
flat_collations(){$Gb=collations();return(is_array(reset($Gb))?call_user_func_array('array_merge',array_values($Gb)):$Gb);}function
edit_type($x,array$n,array$Gb,array$ue=[],array$Ud=[]){$U=(string)$n["type"];echo"<td><select name='".h($x)."[type]' class='type' aria-labelledby='label-type'".on_help_value().">";if($U&&!array_key_exists($U,driver()->types())&&!isset($ue[$U])&&!in_array($U,$Ud))$Ud[]=$U;$fm=driver()->structuredTypes();if($ue)$fm['Foreign keys']=$ue;echo
optionlist(array_merge($Ud,$fm),$U),"</select><td>","<input name='".h($x)."[length]' value='".h($n["length"])."' size='3'".(!$n["length"]&&preg_match('~var(char|binary)$~',$U)?" class='required'":"")." aria-labelledby='label-length'>","<td class='options'>",($Gb?"<input list='collations' name='".h($x)."[collation]'".option_types($U,'('.text_type().')$')." value='".h($n["collation"])."' placeholder='(".'collation'.")'>":''),(driver()->unsigned?"<select name='".h($x)."[unsigned]'".option_types($U,'^$|'.number_type()).'><option>'.optionlist(driver()->unsigned,$n["unsigned"]).'</select>':''),(isset($n['on_update'])?"<select name='".h($x)."[on_update]'".option_types($U,'timestamp|datetime').'>'.optionlist([""=>"(".'ON UPDATE'.")","CURRENT_TIMESTAMP"],(preg_match('~^CURRENT_TIMESTAMP~i',$n["on_update"])?"CURRENT_TIMESTAMP":$n["on_update"])).'</select>':''),($ue?"<select name='".h($x)."[on_delete]'".option_types($U,'`')."><option value=''>(".'ON DELETE'.")".optionlist(explode("|",driver()->onActions),$n["on_delete"])."</select> ":" ");}function
option_types($U,$wn){return" data-types='".h($wn)."'".(preg_match("~$wn~",$U)?"":" class='hidden'");}function
process_length($y){if(JUSH=="mssql"&&preg_match('~^\s*\(?\s*max\s*\)?\s*$~i',$y))return"(max)";if(JUSH=="oracle"&&preg_match('~^\s*\(?\s*(\d+)\s+(CHAR|BYTE)\s*\)?\s*$~i',$y,$A))return"($A[1] ".strtoupper($A[2]).")";if(JUSH=="oracle"&&preg_match('~^\s*\(?\s*\*\s*,\s*(-?\d+)\s*\)?\s*$~',$y,$A))return"(*,$A[1])";$yd=driver()->enumLength;return(preg_match("~^\\s*\\(?\\s*$yd(?:\\s*,\\s*$yd)*+\\s*\\)?\\s*\$~",$y)&&preg_match_all("~$yd~",$y,$Ug)?"(".implode(",",$Ug[0]).")":preg_replace('~^[0-9].*~','(\0)',preg_replace('~[^-0-9,+()[\]]~','',$y)));}function
process_in($X){$X=preg_replace('~^\s*\(\s*(.*?)\s*\)\s*$~s','\1',$X);$mk="'(?:[^']|'')*+'";$gg=(preg_match("~^\\s*$mk(?:\\s*,\\s*$mk)*+\\s*\$~",$X)&&preg_match_all("~$mk~",$X,$Ug)?array_map('Adminer\idf_unescape',$Ug[0]):array_map('trim',explode(",",$X)));return"(".implode(", ",array_map('Adminer\q',$gg)).")";}function
process_between($X){$mk="('(?:[^']|'')*+')";$ok=(preg_match("~^\\s*$mk\\s+AND\\s+$mk\\s*\$~i",$X,$A)?[idf_unescape($A[1]),idf_unescape($A[2])]:preg_split('~\s+AND\s+~i',trim($X)));return(count($ok)==2?q($ok[0])." AND ".q($ok[1]):"NULL AND NULL");}function
process_type(array$n,$Eb="COLLATE"){return" ".(is_user_type($n["type"])?idf_escape($n["type"]):$n["type"]).process_length($n["length"]).(preg_match(number_type(),$n["type"])&&in_array($n["unsigned"],driver()->unsigned)?" $n[unsigned]":"").(preg_match('~'.text_type().'~',$n["type"])&&$n["collation"]?" $Eb ".(JUSH=="mssql"&&preg_match('~^\w+$~',$n["collation"])?$n["collation"]:q($n["collation"])):"");}function
process_field(array$n,array$sn){return[idf_escape(trim($n["field"])),process_type($sn),($n["null"]?" NULL":" NOT NULL"),default_value($n),(preg_match('~timestamp|datetime~',$n["type"])&&$n["on_update"]?" ON UPDATE $n[on_update]".process_length($n["length"]):""),(support("comment")&&$n["comment"]!=""?" COMMENT ".q($n["comment"]):""),($n["auto_increment"]?auto_increment():null),];}function
default_value(array$n){if($n["default"]===null)return"";$l=str_replace("\r","",$n["default"]);$He=$n["generated"];$Q=!preg_match('~]$~',$n["length"])&&(preg_match('~char|binary|text|clob|json|enum|set|String~',$n["type"])||driver()->enumLength($n));$hj=(JUSH=="sqlite"||(JUSH=="sql"&&$n["default_sql"]&&!preg_match("~^(current_timestamp(\\(\\d*\\))?|[bx]'[\\da-f]*')$~i",$l)));return(in_array($He,driver()->generated)?(JUSH=="mssql"?" AS ($l)".($He=="VIRTUAL"?"":" $He"):" GENERATED ALWAYS AS ($l) $He"):(preg_match('~^GENERATED ~i',$l)?" $l":" DEFAULT ".(!$n["default_sql"]&&($Q||preg_match('~^(?![a-z])~i',$l))?(JUSH=="sql"&&preg_match('~text|json~',$n["type"])?"(".q($l).")":q($l)):($hj?"($l)":$l))));}function
edit_fields(array$o,array$Gb,$U="TABLE",array$ue=[]){$o=array_values($o);$Ac=(($_POST?$_POST["defaults"]:get_setting("defaults"))?"":" class='hidden'");$Mb=(($_POST?$_POST["comments"]:get_setting("comments"))?"":" class='hidden'");echo"<thead><tr>\n",($U=="PROCEDURE"?"<td>":""),"<th id='label-name'>".($U=="TABLE"?'Column name':'Parameter name'),"<th id='label-type'>".'Type'."<textarea id='enum-edit' rows='4' cols='12' wrap='off' hidden></textarea>".script("qs('#enum-edit').onblur = editingLengthBlur;"),"<th id='label-length'>".'Length',"<th>".'Options';if($U=="TABLE")echo"<th id='label-null'>NULL\n","<th><input type='radio' name='auto_increment_col' value=''><abbr id='label-ai' title='".'Auto Increment'."'>A_I</abbr>",doc_link(['sql'=>"example-auto-increment.html",'mariadb'=>"auto_increment/",'sqlite'=>"autoinc.html",'pgsql'=>"datatype-numeric.html#DATATYPE-SERIAL",'cockroach'=>"serial",'mssql'=>"t-sql/statements/create-table-transact-sql-identity-property",]),"<th id='label-default'$Ac>".'Default value',(support("comment")?"<th id='label-comment'$Mb>".'Comment':"");$yg=!support("move_col");echo"<td>".icon("plus","add[".($yg?count($o):0)."]","+",'Add next',($yg?on('click','editingAddLastRow'):"")),"<tbody".on('click','editingClick').on('input','editingInput').on('keydown','editingKeydown').">\n";foreach($o
as$s=>$n){$s++;$Pi=$n[($_POST?"orig":"field")];$Pc=(isset($_POST["add"][$s-1])||(isset($n["field"])&&!idx($_POST["drop_col"],$s)))&&(support("drop_col")||$Pi=="");echo"<tr".($Pc?"":" hidden").">\n",($U=="PROCEDURE"?"<td>".html_select("fields[$s][inout]",explode("|",driver()->inout),$n["inout"]):"")."<th>",(support("move_col")?icon("move","","↕",'Move')." ":"");if($Pc)echo"<input name='fields[$s][field]' value='".h($n["field"])."' data-maxlength='64' autocapitalize='off' aria-labelledby='label-name'".(isset($_POST["add"][$s-1])?" autofocus":"").">";echo
input_hidden("fields[$s][orig]",$Pi);edit_type("fields[$s]",$n,$Gb,$ue);if($U=="TABLE"){echo"<td><label class='block'>".checkbox("fields[$s][null]",1,$n["null"],"","","","label-null")."</label>","<td><label class='block'><input type='radio' name='auto_increment_col' value='$s'".($n["auto_increment"]?" checked":"")." aria-labelledby='label-ai'></label>","<td$Ac>".html_select("fields[$s][generated]",array_merge(["","DEFAULT","DEFAULT SQL"],driver()->generated),$n["generated"],"","label-default")." ";$c=" name='fields[$s][default]' aria-labelledby='label-default'";$Y=h($n["default"]);echo(preg_match('~\n~',$n["default"])?"<textarea$c rows='2' cols='30' style='vertical-align: bottom;'>\n$Y</textarea>":"<input$c value='$Y'>");if(support("comment")){$c=" name='fields[$s][comment]' data-maxlength='".(min_version(5.5)?1024:255)."' aria-labelledby='label-comment'";echo"<td$Mb>".adminer()->commentInput('COLUMN',$c,$n["comment"]);}}echo"<td>",(support("move_col")?icon("plus","add[$s]","+",'Add next')." ":""),($Pi==""||support("drop_col")?icon("cross","drop_col[$s]","x",'Remove'):"");}}function
process_fields(array&$o){if($_POST["add"]){$o=array_values($o);array_splice($o,key($_POST["add"]),0,[[]]);}return$_POST["add"]||$_POST["drop_col"];}function
drop_create($cd,$i,$ed,$Nm,$gd,$Og,$ph,$nh,$oh,$ti,$Uh){if($_POST["drop"])query_redirect($cd,$Og,$ph);elseif($ti=="")query_redirect($i,$Og,$oh);elseif(support("transaction_ddl")){driver()->begin();queries_redirect($Og,$nh,queries($cd)&&queries($i)&&driver()->commit());driver()->rollback();}elseif($ti!=$Uh){$jc=queries($i);queries_redirect($Og,$nh,$jc&&queries($cd));if($jc&&$ed)queries($ed);}else
queries_redirect($Og,$nh,queries($Nm)&&queries($gd)&&queries($cd)&&queries($i));}function
create_trigger($xi,array$J){$Tm=" $J[Timing] $J[Event]".(preg_match('~ OF~',$J["Event"])?" $J[Of]":"");return"CREATE TRIGGER ".idf_escape($J["Trigger"]).(JUSH=="mssql"?$xi.$Tm:$Tm.$xi).preg_replace('~[\s;]+$~',''," $J[Type]\n$J[Statement]").";";}function
q_dollar($Q){$Gc='$$';while(strpos($Q.$Gc,$Gc)!=strlen($Q))$Gc='$_'.substr($Gc,1);return$Gc.$Q.$Gc;}function
routine_collate($Fb){static$qb=[];if($Fb&&!$qb){foreach(collations()as$pb=>$ao){foreach((array)$ao
as$X)$qb[$X]=$pb;}}return($qb[$Fb]?"CHARACTER SET ".q($qb[$Fb])." ":"")."COLLATE";}function
create_routine($Nk,array$J){$O=[];$o=$J["fields"];ksort($o);foreach($o
as$n){if($n["field"]!=""){$Of=(preg_match("~^(".driver()->inout.")\$~",$n["inout"])?$n["inout"]:"");$O[]="\n  ".(JUSH=="mssql"?"@$n[field]".process_type($n).($Of?" $Of":""):($Of?"$Of ":"").idf_escape($n["field"]).process_type($n,routine_collate($n["collation"])));}}$Dc="";$Hi=[];foreach(routine_options($Nk)as$x=>$bo){$Y=idx($J["options"],$x,"");if($x=="DEFINER")$Dc=($Y?" $x=".implode("@",array_map('Adminer\q',explode("@",$Y,2))):"");elseif(!$bo){if($Y!="")$Hi[]="$x ".q($Y);}elseif($Y!=reset($bo)&&in_array($Y,$bo))$Hi[]=$Y;}$wg=$J["language"];$Ec=preg_replace('~[\s;]+$~','',$J["definition"]);$Yc=(JUSH=="pgsql"||($wg&&$wg!="sql"));$fj=($O?implode(",",$O)."\n":"");return"CREATE$Dc $Nk ".table(trim($J["name"])).(JUSH=="mssql"&&$Nk=="PROCEDURE"?rtrim($fj):" ($fj)").($Nk=="FUNCTION"?"\nRETURNS".process_type($J["returns"],routine_collate($J["returns"]["collation"])):"").($wg?" LANGUAGE $wg":"").($Hi?"\n".implode(" ",$Hi):"").($Yc?" AS ".q_dollar("\n".trim($Ec)."\n"):(JUSH=="mssql"?"\nAS":"")."\n$Ec;");}function
remove_definer($G){$Dc=implode("@",array_map('Adminer\idf_escape',explode("@",logged_user(),2)));return
preg_replace('(^([A-Z =]+) DEFINER='.preg_quote($Dc).')','\1',$G);}function
object_name($U,$R,array$e){return
str_replace(["{table}","{columns}"],[$R,implode("_",$e)],adminer()->namePattern($U));}function
format_foreign_key(array$q,$B=""){$k=$q["db"];$ei=$q["ns"];return($B!=""?" CONSTRAINT ".idf_escape($B):"")." FOREIGN KEY (".implode(", ",array_map('Adminer\idf_escape',$q["source"])).") REFERENCES ".($k!=""&&$k!=$_GET["db"]?idf_escape($k).".":"").($ei!=""&&$ei!=$_GET["ns"]?idf_escape($ei).".":"").idf_escape($q["table"])." (".implode(", ",array_map('Adminer\idf_escape',$q["target"])).")".(preg_match("~^(".driver()->onActions.")\$~",$q["on_delete"])?" ON DELETE $q[on_delete]":"").(preg_match("~^(".driver()->onActions.")\$~",$q["on_update"])?" ON UPDATE $q[on_update]":"").($q["deferrable"]?" $q[deferrable]":"");}function
tar_file($p,$Ym){$I=pack("a100a8a8a8a12a12",$p,644,0,0,decoct($Ym->size),decoct(time()));$wb=8*32;for($s=0;$s<strlen($I);$s++)$wb+=ord($I[$s]);$I
.=sprintf("%06o",$wb)."\0 ";echo$I,str_repeat("\0",512-strlen($I));$Ym->send();echo
str_repeat("\0",511-($Ym->size+511)%512);}function
doc_version(){$_l=connection()->server_info;if(JUSH=='oracle'){preg_match('~(?:.* |^)(\d+)\.\d+\.\d+\.\d+\.\d+~s',$_l,$A);return($A[1]>=18?$A[1]:"19");}$xk=(JUSH=='sql'||connection()->flavor=='cockroach'?'~^\d+\.\d+~':'~^\d\.?\d~');$eo=(preg_match($xk,$_l,$A)?$A[0]:"");if(JUSH=='mssql')return($eo>=15?"sql-server-ver$eo":($eo==12?"azuresqldb-current":"sql-server-2017"));return$eo;}function
doc_link(array$yj,$Om="📖"){$eo=doc_version();$Nn=['sql'=>"https://dev.mysql.com/doc/refman/$eo/en/",'sqlite'=>"https://www.sqlite.org/",'pgsql'=>"https://www.postgresql.org/docs/".(connection()->flavor=='cockroach'?"current":$eo)."/",'mssql'=>"https://learn.microsoft.com/en-us/sql/",'oracle'=>"https://docs.oracle.com/en/database/oracle/oracle-database/$eo/",];if(connection()->flavor=='maria'){$Nn['sql']="https://mariadb.com/kb/en/";$yj['sql']=($yj['mariadb']?:str_replace(".html","/",$yj['sql']));}if(connection()->flavor=='cockroach'&&$yj['cockroach']){$Nn['pgsql']="https://docs.cockroachlabs.com/docs/v$eo/";$yj['pgsql']=$yj['cockroach'];}return($yj[JUSH]?" <a href='".h($Nn[JUSH].$yj[JUSH].(JUSH=='mssql'?"?view=$eo":""))."'".target_blank()." class='doc' title='".'Documentation'."'>$Om</a>":"");}function
db_size($k){if(!connection()->select_db($k))return"?";$I=0;foreach(table_status()as$S)$I+=$S["Data_length"]+$S["Index_length"];return
adminer()->formatSizeValue($I);}function
set_utf8mb4($i){static$O=false;if(!$O&&preg_match('~\butf8mb4~i',$i)){$O=true;echo"SET NAMES ".charset(connection()).";\n\n";}}if(DB==""&&isset($_GET["ns"]))redirect(remove_from_uri('ns'),null,301);if(!(DB!=""?connection()->select_db(DB):isset($_GET["sql"])||isset($_GET["dump"])||isset($_GET["database"])||isset($_GET["processlist"])||isset($_GET["privileges"])||isset($_GET["user"])||isset($_GET["variables"])||$_GET["script"]=="connect")){if(DB!=""||$_GET["refresh"]){restart_session();set_session("dbs",null);}if(DB!="")page_header('Database'.": ".h(DB),adminer()->error(),true,"","db");else{if(!isset($_GET["db"])&&support("single_db")){$j=adminer()->databases();if($j)redirect(ME."db=".url_escape($j[0]));}if($_POST["db"]&&!$m)queries_redirect(substr(ME,0,-1),'Databases have been dropped.',drop_databases($_POST["db"]));page_header('Select database',$m,false);echo"<p class='links'>\n";foreach(['database'=>'Create database','privileges'=>'Privileges','processlist'=>'Process list','variables'=>'Variables','status'=>'Status',]as$x=>$X){if(support($x))echo"<a href='".h(ME)."$x='>$X</a>\n";}echo"<p>".sprintf('%s version: %s through PHP extension %s',get_driver(DRIVER),"<b>".h(connection()->server_info)."</b>","<b>".connection()->extension."</b>")."\n","<p>".sprintf('Logged in as: %s',"<b>".h(logged_user())."</b>")."\n";$j=adminer()->databases();$ie=$_GET["filter"];if($ie!=""){foreach($j
as$x=>$k){if(stripos($k,$ie)===false)unset($j[$x]);}}if($j){$al=support("scheme");echo"<form method='post'>\n","<table class='checkable odds'".on('click','tableClick').on('dblclick','tableClick').">\n","<thead><tr>".(support("database")?"<td class='hover'>":"")."<th".(JUSH!='mssql'?" aria-sort='ascending'":"").">".'Database'.(get_session("dbs")!==null?" - <a href='".h(ME)."refresh=1'>".'Refresh'."</a>":"")."<th>".'Collation'."<th>".'Tables'."<th>".'Size'." - <a href='".h(ME)."dbsize=1'".on('click','ajaxSetHtml',ME."script=connect").">".'Compute'."</a>"."<tbody>\n";$j=($_GET["dbsize"]?count_tables($j):array_flip($j));foreach($j
as$k=>$T){$Mk=h(preg_replace('~&db=[^&]*~','',ME))."db=".url_escape($k);$t=h("Db-".$k);echo"<tr>".(support("database")?"<td class='hover'>".checkbox("db[]",$k,in_array($k,(array)$_POST["db"]),"","","",$t):""),"<th><a href='$Mk' id='$t'>".h($k)."</a>";$Fb=h(db_collation($k,[]));echo"<td>".(support("database")?"<a href='$Mk".($al?"&amp;ns=":"")."&amp;database=' title='".'Alter database'."'>$Fb</a>":$Fb),"<td align='right'><a href='$Mk&amp;schema=' id='tables-".h($k)."' title='".'Database schema'."'>".($_GET["dbsize"]?format_number($T):"?")."</a>","<td align='right' id='size-".h($k)."'>".($_GET["dbsize"]?db_size($k):"?"),"\n";}echo"</table>\n",(support("database")?"<div class='footer'><div>\n"."<fieldset><legend>".'Selected'." <span id='selected'></span></legend><div>\n"."<input type='hidden' name='all' value=''".on('click','countDbs').">\n"."<input type='submit' name='drop' value='".'Drop'."'".confirm().">\n"."</div></fieldset>\n"."</div></div>\n":""),input_token(),"</form>\n",script("tableCheck();");}$oa=adminer();$Gj=($oa
instanceof
Plugins?$oa->plugins:[]);$bd=($oa
instanceof
Plugins?$oa->drivers:[]);$Mc=design_checksums();if($Gj||$bd||$Mc){$xb=($oa
instanceof
Plugins?$oa->checksums():[]);$mi=Plugins::officialChecksums();$In=function($Mn){return" (<a href='$Mn'".target_blank()." class='update'>".VERSION."</a>)";};$Fj=function($ee)use($xb,$mi,$In){return($xb[$ee]&&$mi[$ee]&&$xb[$ee]!==$mi[$ee]?$In("https://www.adminer.org/plugins/?version=".VERSION):"");};echo"<div class='plugins'>\n","<h3>".'Loaded plugins'."</h3>\n<ul>\n";foreach($Gj
as$Dj){$vk=new
\ReflectionObject($Dj);$Jc=Plugins::describe($Dj);echo"<li><b>".h(Plugins::pluginName($Dj))."</b>".($Jc!=""?": $Jc":"").$Fj(basename((string)$vk->getFileName(),'.php'))."\n";}foreach($bd
as$t=>$B)echo"<li><b>".h($t)."</b>: ".h($B).$Fj(basename((string)$oa->driverFiles[$t],'.php'))."\n";if($Mc){$oi=official_design_checksums();foreach($Mc
as$p=>$Lc){list($B,$wb)=$Lc;$ni=$oi["$B/$p"];echo"<li><b>".h($p)."</b>".h($B?": $B":"").($ni&&$ni!==$wb?$In("https://www.adminer.org/?version=".VERSION."#extras"):"")."\n";}}echo"</ul>\n";adminer()->pluginsLinks();echo"</div>\n";}}page_footer("db");exit;}if(support("scheme")){if(DB!=""&&$_GET["ns"]!==""){if(!isset($_GET["ns"]))redirect(preg_replace('~&db=[^&]+~','\0&ns='.url_escape(get_schema()),relative_uri()));if(!set_schema($_GET["ns"]))page_header('Schema'.h(": $_GET[ns]"),adminer()->error(),true,"","ns");}}adminer()->afterConnect();class
TmpFile{private$handler;var$size=0;function
__construct(){$this->handler=tmpfile();}function
write($ac){$this->size+=strlen($ac);fwrite($this->handler,$ac);}function
send(){fseek($this->handler,0);fpassthru($this->handler);fclose($this->handler);}}if($_GET["select"]!=""&&($_POST["edit"]||$_POST["clone"])&&!$_POST["save"])$_GET["edit"]=$_GET["select"];if(isset($_GET["callf"]))$_GET["call"]=$_GET["callf"];if(isset($_GET["function"]))$_GET["procedure"]=$_GET["function"];if(isset($_GET["download"])){$a=$_GET["download"];$o=fields($a);header("Content-Type: application/octet-stream");$bo=array_merge((array)$_GET["where"],(array)$_GET["val"]);header("Content-Disposition: attachment; filename=".friendly_url("$a-".implode("_",$bo)).".".friendly_url($_GET["field"]));$M=[idf_escape($_GET["field"])];$H=driver()->select($a,$M,[where($_GET,$o)],$M);$J=($H?$H->fetch_row():[]);echo
driver()->value($J[0],$o[$_GET["field"]]);exit;}elseif(isset($_GET["table"])){$a=$_GET["table"];$o=fields($a);if(!$o)$m=adminer()->error();$S=table_status1($a);$B=adminer()->tableName($S);$m=$m?:h($S["Error"]);page_header(($o&&is_view($S)?$S['Engine']=='materialized view'?'Materialized view':'View':'Table').": ".($B!=""?$B:h($a)),$m,[],"",!$o,($o?doc_link([JUSH=>driver()->tableHelp($a,is_view($S))]):""));$Lk=[];foreach($o
as$x=>$n)$Lk+=$n["privileges"];adminer()->selectLinks($S,(isset($Lk["insert"])||!support("table")?"":null));$f=$S["Comment"];if($f!="")echo"<p class='nowrap'>".'Comment'.": ".adminer()->commentValue('TABLE',$f)."\n";if($o)adminer()->tableStructurePrint($o,$S);function
tables_links(array$T){echo"<ul>\n";foreach($T
as$J){$_=preg_replace('~&ns=[^&]*~',"&ns=".url_escape($J["ns"]),ME);echo"<li><a href='".h($_."table=".url_escape($J["table"]))."'>".($J["ns"]!=$_GET["ns"]?"<b>".h($J["ns"])."</b>.":"").h($J["table"])."</a>";}echo"</ul>\n";}$Mf=driver()->inheritsFrom($a);if($Mf){echo"<h3>".'Inherits from'."</h3>\n";tables_links($Mf);}if(support("indexes")&&driver()->supportsIndex($S)){echo"<div>\n","<h3 id='indexes'>".'Indexes'."</h3>\n";$w=indexes($a);if($w)adminer()->tableIndexesPrint($w,$S);if(driver()->supportsAlterIndex($S))echo'<p class="links hover"><a href="'.h(ME).'indexes='.url_escape($a).'">'.'Alter indexes'."</a>\n";echo"</div>\n";}if(!is_view($S)&&driver()->supportsAlterTable($S)){if(fk_support($S)){echo"<div>\n","<h3 id='foreign-keys'>".'Foreign keys'."</h3>\n";$ue=foreign_keys($a);$za=function_exists('Adminer\alter_table');if($ue){echo"<table>\n","<thead><tr><th>".'Source'."<th>".'Target'."<th>".'ON DELETE'."<th>".'ON UPDATE'.($za?"<td class='hover'>":"")."<tbody>\n";foreach($ue
as$B=>$q){echo"<tr".(!is_int($B)?" title='".h($B)."'":"").">","<th><i>".implode("</i>, <i>",array_map('Adminer\h',$q["source"]))."</i>";$_=($q["db"]!=""?preg_replace('~&db=[^&]*~',"&db=".url_escape($q["db"]),ME):($q["ns"]!=""?preg_replace('~&ns=[^&]*~',"&ns=".url_escape($q["ns"]),ME):ME));echo"<td><a href='".h($_."table=".url_escape($q["table"]))."'>".($q["db"]!=""&&$q["db"]!=DB?"<b>".h($q["db"])."</b>.":"").($q["ns"]!=""&&$q["ns"]!=$_GET["ns"]?"<b>".h($q["ns"])."</b>.":"").h($q["table"])."</a>","(<i>".implode("</i>, <i>",array_map('Adminer\h',$q["target"]))."</i>)","<td>".h($q["on_delete"]),"<td>".h($q["on_update"]),($za?'<td class="hover"><a href="'.h(ME.'foreign='.url_escape($a).'&name='.url_escape($B)).'">'.'Alter'.'</a>':''),"\n";}echo"</table>\n";}echo($za?'<p class="links hover"><a href="'.h(ME).'foreign='.url_escape($a).'">'.'Create foreign key'."</a>\n":''),"</div>\n";}if(support("check")){echo"<div>\n","<h3 id='checks'>".'Checks'."</h3>\n";$sb=driver()->checkConstraints($a);if($sb){echo"<table>\n";foreach($sb
as$x=>$X)echo"<tr".($x!=$X?" title='".h($x)."'":"").">","<td><code class='sql jush-".JUSH."'>".shorten_utf8(preg_replace('~\s+~',' ',ltrim($X)),80,"</code>"),"<td class='hover'><a href='".h(ME.'check='.url_escape($a).'&name='.url_escape($x))."'>".'Alter'."</a>","\n";echo"</table>\n";}echo'<p class="links hover"><a href="'.h(ME).'check='.url_escape($a).'">'.'Create check'."</a>\n","</div>\n";}}if(support(is_view($S)?"view_trigger":"trigger")&&driver()->supportsAlterTable($S)){echo"<div>\n","<h3 id='triggers'>".'Triggers'."</h3>\n";$pn=triggers($a);if($pn){echo"<table>\n";foreach($pn
as$x=>$X){echo"<tr valign='top'><td>".h($X[0])."<td>".h($X[1])."<th>".h($x)."<td class='hover'><a href='".h(ME.'trigger='.url_escape($a).'&name='.url_escape($x))."'>".'Alter'."</a>";$Nk=$X[2];if($Nk){$Pk=preg_replace('~&ns=[^&]*~',"&ns=".url_escape($Nk["ns"]),ME).'function='.url_escape($Nk["function"]).'&name='.url_escape($Nk["name"]);echo", <a href='".h($Pk)."' title='".h($Nk["name"])."'>".'Alter function'."</a>";}echo"\n";}echo"</table>\n";}echo'<p class="links hover"><a href="'.h(ME).'trigger='.url_escape($a).'">'.'Create trigger'."</a>\n","</div>\n";}$El=driver()->shadowTables($a);if($El){echo"<h3 id='shadow-tables'>".'Shadow tables'."</h3>\n";tables_links($El);}$Lf=driver()->inheritedTables($a);if($Lf){echo"<h3 id='partitions'>".'Inherited by'."</h3>\n";$lj=driver()->partitionsInfo($a);if($lj)echo"<p><code class='sql jush-".JUSH."'>BY ".h("$lj[partition_by]($lj[partition])")."</code>\n";tables_links($Lf);}}elseif(isset($_GET["schema"])){page_header('Database schema',"",[],h(DB.($_GET["ns"]?".$_GET[ns]":"")));function
schema_column($R,array$uk,array&$e){if(!isset($e[$R])){$e[$R]=0;foreach((array)idx($uk,$R)as$B=>$wk){if($B!=$R)$e[$R]=max($e[$R],schema_column($B,$uk,$e)+1);}}return$e[$R];}function
type_class($U){foreach(['char'=>'text','date'=>'time|year','binary'=>'blob','enum'=>'set',]as$x=>$X){if(preg_match("~$x|$X~",$U))return" class='$x'";}}$zm=[];$Am=[];$_m=[];$be=[];$ca=($_GET["schema"]?:$_COOKIE["adminer_schema-".str_replace(".","_",DB)]);preg_match_all('~([^:]+):([-0-9.]+)x([-0-9.]+)(_|$)~',$ca,$Ug,PREG_SET_ORDER);foreach($Ug
as$s=>$A){$zm[$A[1]]=[(float)$A[2],(float)$A[3]];$Am[]="\n\t'".js_escape($A[1])."': [ $A[2], $A[3] ]";}$L=[];$uk=[];$ue=[];$xa=driver()->allFields();$if=[];$Bm=[];foreach(table_status('',true)as$R=>$S){if(!is_view($S)){if(adminer()->tableName($S)!=""&&!$S["dependent"])$Bm[$R]=$S;else$if[$R]=true;}}foreach($Bm
as$R=>$S){$F=0;$L[$R]["fields"]=[];foreach($xa[$R]as$n){$F+=1.25;$be[$R][$n["field"]]=$F;$L[$R]["fields"][$n["field"]]=$n;}foreach(adminer()->foreignKeys($R)as$X){if($X["db"]==""&&$X["ns"]==""&&!$if[$X["table"]]){$ue[$R][]=$X;$uk[$X["table"]][$R]=[];}}}$e=[];$Le=[];$ro=[];$Se=[];foreach(array_keys($L)as$B)schema_column($B,$uk,$e);arsort($e);foreach($e
as$B=>$d){$wh=null;foreach((array)idx($ue,$B)as$X){if($X["table"]!=$B&&$L[$X["table"]])$wh=($wh===null?$e[$X["table"]]:min($wh,$e[$X["table"]]));}$e[$B]=max($d,(int)$wh-1);}$Dm=[];foreach($L
as$B=>$R){$d=$e[$B];$Le[$d][]=$B;$Qm=.75*strlen($B);foreach($R["fields"]as$n)$Qm=max($Qm,.65*strlen($n["field"]));$Dm[$B]=ceil($Qm)+1;$ro[$d]=max(idx($ro,$d,0),$Dm[$B]);}foreach($ue
as$B=>$ao){foreach($ao
as$X){$Re=$e[$B]+(idx($e,$X["table"],$e[$B])>$e[$B]?1:0);$Se[$Re]=idx($Se,$Re,0)+1;}}ksort($Le);$gf=0;$qo=0;$Ib=0;$Sj=null;$vm=[];foreach($Le
as$d=>$T){if($Sj!==null){$Ib=round($Ib+$ro[$Sj]+1.7+idx($Se,$d,0)*.1,1);$Ki=[];foreach($T
as$B){$km=0;$hc=0;$Ph=array_keys((array)idx($uk,$B));foreach((array)idx($ue,$B)as$X)$Ph[]=$X["table"];foreach($Ph
as$Ih){if($L[$Ih]&&$e[$Ih]<$d){$km+=$L[$Ih]["pos"][0];$hc++;}}$Ki[$B]=($hc?$km/$hc:$gf);}asort($Ki);$T=array_keys($Ki);}$bn=0;foreach($T
as$B){$F=1.25*count($L[$B]["fields"]);$L[$B]["pos"]=($zm[$B]?:[$bn,$Ib]);$vm[$B]=$L[$B]["pos"][1];$bn+=2.5+$F;$gf=max($gf,$L[$B]["pos"][0]+2.5+$F);$qo=max($qo,round($L[$B]["pos"][1]+$ro[$d],1));if(!$zm[$B])$_m[]="\n\t'".js_escape($B)."': [ ".$L[$B]["pos"][0].", ".$L[$B]["pos"][1]." ]";}$Sj=$d;}$Bg=[];$Ua=[];foreach($ue
as$B=>$ao){foreach($ao
as$X){$Jm=idx($vm,$X["table"],$vm[$B]);$Pl=$vm[$B]+$ro[$e[$B]];$Kk=($Jm-1>$Pl);$_g=($Kk?$Pl+1:min($vm[$B],$Jm)-1);$Ta=idx($Ua,(string)$_g,0);$Ua[(string)$_g]=$Ta+1;$_g=round($Kk?min($_g+$Ta*.1,$Jm-1):$_g-$Ta*.1,1);while($Bg[(string)$_g])$_g-=.0001;$L[$B]["references"][$X["table"]][(string)$_g]=[$X["source"],$X["target"]];$uk[$X["table"]][$B][(string)$_g]=$X["target"];$Bg[(string)$_g]=true;}}echo'<div id="schema" style="height: ',$gf,'em; width: ',$qo,'em;">
<script',nonce(),'>
const tablePos = {',implode(",",$Am)."\n",'};
const tablePosDefault = {',implode(",",$_m)."\n",'};
const em = qs(\'#schema\').offsetHeight / ',$gf,';
document.onmousemove = schemaMousemove;
document.onmouseup = event => schemaMouseup(event, \'',js_escape(DB),'\');
</script>
';foreach($L
as$B=>$R){echo"<div class='table'".on('mousedown','schemaMousedown')." style='top: ".$R["pos"][0]."em; left: ".$R["pos"][1]."em; width: ".$Dm[$B]."em;'>",'<a href="'.h(ME).'table='.url_escape($B).'"><b>'.h($B)."</b></a>";foreach($R["fields"]as$n){$X='<span'.type_class($n["type"]).' title="'.h($n["type"].($n["length"]?"($n[length])":"").($n["null"]?" NULL":'')).'">'.h($n["field"]).'</span>';echo"<br>".($n["primary"]?"<i>$X</i>":$X);}foreach((array)$R["references"]as$Km=>$wk){foreach($wk
as$_g=>$rk){$Ag=$_g-$R["pos"][1];$hm=($Ag>0?"left: 100%; width: calc($Ag"."em - 100%)":"left: $Ag"."em");$qo=($Ag>0?"100%":(-$Ag)."em");$s=0;foreach($rk[0]as$Ol)echo"\n<div class='references' title='".h($Km)."' id='refs$_g-".($s++)."' style='$hm"."; top: ".$be[$B][$Ol]."em; padding-top: .5em;'>"."<div style='border-top: 1px solid gray; width: $qo;'></div></div>";}}foreach((array)$uk[$B]as$Km=>$wk){foreach($wk
as$_g=>$Lm){$Ag=$_g-$R["pos"][1];$s=0;foreach($Lm
as$Im)echo"\n<div class='references arrow' title='".h($Km)."' id='refd$_g-".($s++)."' style='left: $Ag"."em; top: ".$be[$B][$Im]."em;'>"."<div style='height: .5em; border-bottom: 1px solid gray; width: ".(-$Ag)."em;'></div>"."</div>";}}echo"\n</div>\n";}foreach($L
as$B=>$R){foreach((array)$R["references"]as$Km=>$wk){if($L[$Km]){foreach($wk
as$_g=>$rk){$xh=$gf;$ch=-10;foreach($rk[0]as$x=>$Ol){$Ij=$R["pos"][0]+$be[$B][$Ol];$Jj=$L[$Km]["pos"][0]+$be[$Km][$rk[1][$x]];$xh=min($xh,$Ij,$Jj);$ch=max($ch,$Ij,$Jj);}echo"<div class='references' id='refl$_g' style='left: $_g"."em; top: $xh"."em; padding: .5em 0;'><div style='border-right: 1px solid gray; margin-top: 1px; height: ".($ch-$xh)."em;'></div></div>\n";}}}}echo'</div>
<p class="links"><a href="',h(ME."schema=".url_escape($ca)),'" id="schema-link">Permanent link</a>
';}elseif(isset($_GET["dump"])){$a=$_GET["dump"];if($_POST&&!$m){$l=["auto_increment"=>''];foreach(["type","routine","event","trigger"]as$mm){if(support($mm))$l[$mm."s"]='';}save_settings(array_intersect_key($_POST+$l,array_flip(["output","format","db_style","schema_style","table_style","data_style"])+$l),"adminer_export");$wa=(DB==""||$_GET["ns"]==="");$T=array_flip((array)$_POST["tables"])+array_flip((array)$_POST["data"]);$Qd=dump_headers((count($T)==1?key($T):DB),($wa||count($T)>1));$dg=preg_match('~sql~',$_POST["format"]);if($dg){echo"-- Adminer ".VERSION." ".get_driver(DRIVER)." ".str_replace("\n"," ",connection()->server_info)." dump\n\n";if(JUSH=="sql"){echo"SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
".($_POST["data_style"]?"SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';
":"")."
";connection()->query("SET time_zone = '+00:00'");connection()->query("SET sql_mode = ''");}}$hm=$_POST["db_style"];$j=[DB];if(DB==""){$j=$_POST["databases"];if(is_string($j))$j=explode("\n",rtrim(str_replace("\r","",$j),"\n"));}foreach((array)$j
as$k){adminer()->dumpDatabase($k);if(connection()->select_db($k)){if($dg&&$hm)echo
use_sql($k,$hm).";\n\n";foreach(($_GET["ns"]===""?(array)$_POST["schemas"]:(DB!=""||!support("scheme")?[""]:adminer()->schemas()))as$L){if($L!=""){if(DB==""&&information_schema(DB,$L))continue;set_schema($L);}if($dg&&$_POST["schema_style"]&&function_exists('Adminer\use_schema_sql'))echo
use_schema_sql($_GET["ns"],$_POST["schema_style"]).";\n\n";$dm=($_POST["table_style"]||$_POST["data_style"]?table_status('',true):[]);$Pd=[];$tc=[];foreach($dm
as$B=>$S){if($wa||in_array($B,(array)$_POST["tables"]))$Pd[$B]=$S;if($wa||in_array($B,(array)$_POST["data"]))$tc[$B]=$S;}if($dg){if($_POST["table_style"]=="DROP+CREATE"&&function_exists('Adminer\drop_sql'))echo
drop_sql($Pd);if($_POST["data_style"]=="TRUNCATE+INSERT"&&function_exists('Adminer\truncate_all_sql')){$qn=[];foreach($tc
as$B=>$S){if(!is_view($S)&&!($_POST["table_style"]=="DROP+CREATE"&&isset($Pd[$B])))$qn[]=$B;}echo
truncate_all_sql($qn);}$Yi="";if($_POST["types"]){foreach(types()as$t=>$U){$Ec=type_definition($t);$ii=($Ec["kind"]=='d'?"DOMAIN":"TYPE");if($Ec["definition"])$Yi
.=($hm!='DROP+CREATE'?"DROP $ii IF EXISTS ".table($U).";;\n":"")."CREATE $ii ".table($U)." $Ec[definition];\n\n";else$Yi
.="-- Could not export type ".preg_replace('~[\r\n]+~',' ',$U)."\n\n";}}if($_POST["routines"]){foreach(routines()as$J){$B=$J["ROUTINE_NAME"];$Nk=$J["ROUTINE_TYPE"];$Kf=routine($J["SPECIFIC_NAME"],$Nk);if($Kf["definition"]!=""){$i=create_routine($Nk,["name"=>$B]+$Kf);set_utf8mb4($i);$Yi
.=($hm!='DROP+CREATE'?"DROP $Nk IF EXISTS ".table($B).";;\n":"")."$i;\n\n";}else$Yi
.="-- Could not export ".strtolower($Nk)." ".preg_replace('~[\r\n]+~',' ',$B)."\n\n";}}if($_POST["events"]){foreach(get_rows("SHOW EVENTS",null,"-- ")as$J){$i=remove_definer(get_val("SHOW CREATE EVENT ".idf_escape($J["Name"]),3));set_utf8mb4($i);$Yi
.=($hm!='DROP+CREATE'?"DROP EVENT IF EXISTS ".idf_escape($J["Name"]).";;\n":"")."$i;;\n\n";}}echo($Yi&&JUSH=='sql'?"DELIMITER ;;\n\n$Yi"."DELIMITER ;\n\n":$Yi);}if($_POST["table_style"]||$_POST["data_style"]){$go=[];foreach($dm
as$B=>$S){$R=array_key_exists($B,$Pd);$rc=array_key_exists($B,$tc);if($R||$rc){$Ym=null;if($Qd=="tar"){$Ym=new
TmpFile;ob_start([$Ym,'write'],1e5);}adminer()->dumpTable($B,($R?$_POST["table_style"]:""),(is_view($S)?2:0));if(is_view($S))$go[]=$B;elseif($rc){$o=fields($B);$M=["*"];$ec=convert_fields($o,$o);if($ec)$M[]=substr($ec,2);adminer()->dumpData($B,$_POST["data_style"],"",$M);}if($dg&&$_POST["triggers"]&&$R&&($pn=trigger_sql($B)))echo"\nDELIMITER ;;\n$pn\nDELIMITER ;\n";if($Qd=="tar"){ob_end_flush();tar_file((DB!=""?"":"$k/")."$B.csv",$Ym);}elseif($dg)echo"\n";}}if($dg&&$_POST["table_style"]&&function_exists('Adminer\foreign_keys_sql')){foreach($Pd
as$B=>$S){if(!is_view($S))echo
foreign_keys_sql($B);}}if($dg){foreach($go
as$fo)adminer()->dumpTable($fo,$_POST["table_style"],1);}if($Qd=="tar")echo
pack("x1024");}}}}adminer()->dumpFooter();exit;}page_header('Export',$m,($_GET["export"]!=""?["table"=>$_GET["export"]]:[]),h(DB));echo'
<form method="post">
<table class="layout">
';$xc=['','USE','DROP+CREATE','CREATE'];$Yk=(JUSH=="mssql"?['','DROP+CREATE','CREATE']:$xc);$Cm=['','DROP+CREATE','CREATE'];$sc=['','TRUNCATE+INSERT','INSERT'];if(JUSH=="sql")$sc[]='INSERT+UPDATE';$J=get_settings("adminer_export");if(!$J)$J=["output"=>"text","format"=>"sql","db_style"=>(DB!=""?"":"CREATE"),"schema_style"=>"","table_style"=>"DROP+CREATE","data_style"=>"INSERT"];echo"<tr><th>".'Output'."<td>".html_radios("output",adminer()->dumpOutput(),$J["output"])."\n","<tr><th>".'Format'."<td>".html_radios("format",adminer()->dumpFormat(),$J["format"])."\n",(JUSH=="sqlite"?"":"<tr><th>".'Database'."<td>".html_select('db_style',$xc,$J["db_style"]).(support("type")?checkbox("types",1,$J["types"],'User types'):"").(support("routine")?checkbox("routines",1,$J["routines"],'Routines'):"").(support("event")?checkbox("events",1,$J["events"],'Events'):"")),(function_exists('Adminer\use_schema_sql')?"<tr><th>".'Schema'."<td>".html_select('schema_style',$Yk,$J["schema_style"]):""),"<tr><th>".'Tables'."<td>".html_select('table_style',$Cm,$J["table_style"]).checkbox("auto_increment",1,$J["auto_increment"],'Auto Increment').(support("trigger")?checkbox("triggers",1,$J["triggers"],'Triggers'):""),"<tr><th>".'Data'."<td>".html_select('data_style',$sc,$J["data_style"]),'</table>
';adminer()->dumpPrint();echo'<p><input type=\'submit\' value=\'Export\'>
',input_token(),'
<table',on('click','dumpClick'),'>
';$Qj=[];if($_GET["ns"]===""&&support("scheme")){echo"<thead><tr><th style='text-align: left;'>","<label class='block'><input type='checkbox' id='check-schemas' checked class='jsonly' title='".'All'."'".on('click','formCheck','^schemas\[').">".'Schema'."</label>","<tbody>\n";foreach(adminer()->schemas()as$L){if(!information_schema(DB,$L))echo"<tr><td>".checkbox("schemas[]",$L,true,$L,"","block")."\n";}}elseif(DB!=""){$ub=($a!=""?"":" checked");echo"<thead><tr>","<th style='text-align: left;'><label class='block'><input type='checkbox' id='check-tables'$ub class='jsonly' title='".'All'."'".on('click','formCheck','^tables\[').">".'Table'."</label>","<th style='text-align: right;'><label class='block'>".'Data'."<input type='checkbox' id='check-data'$ub class='jsonly' title='".'All'."'".on('click','formCheck','^data\[')."></label>","<tbody>\n";$go="";$Fm=tables_list();foreach($Fm
as$B=>$U){$Pj=preg_replace('~_.*~','',$B);$ub=($a==""||$a==(substr($a,-1)=="%"?"$Pj%":$B));$Wj="<tr><td>".checkbox("tables[]",$B,$ub,$B,"","block");if($U!==null&&!preg_match('~table~i',$U))$go
.="$Wj\n";else
echo"$Wj<td align='right'><label class='block'><span id='Rows-".h($B)."'></span>".checkbox("data[]",$B,$ub)."</label>\n";$Qj[$Pj]++;}echo$go;if($Fm)echo
script("ajaxSetHtml('".js_escape(ME)."script=db');");}else{$j=adminer()->databases();echo"<thead><tr><th style='text-align: left;'>","<label class='block'>".($j?"<input type='checkbox' id='check-databases'".($a==""?" checked":"")." class='jsonly' title='".'All'."'".on('click','formCheck','^databases\[').">":"").'Database'."</label>","<tbody>\n";if($j){foreach($j
as$k){if(!information_schema($k)){$Pj=preg_replace('~_.*~','',$k);echo"<tr><td>".checkbox("databases[]",$k,$a==""||$a=="$Pj%",$k,"","block")."\n";$Qj[$Pj]++;}}}else
echo"<tr><td><textarea name='databases' rows='10' cols='20'></textarea>";}echo'</table>
</form>
';$ke=true;foreach($Qj
as$x=>$X){if($x!=""&&$X>1){echo($ke?"<p>":" ")."<a href='".h(ME)."dump=".url_escape("$x%")."'>".h($x)."</a>";$ke=false;}}}elseif(isset($_GET["privileges"])){page_header('Privileges');echo'<p class="links"><a href="'.h(ME).'user=">'.'Create user'."</a>";$H=connection()->query("SELECT User, Host FROM mysql.".(DB==""?"user":"db WHERE ".q(DB)." LIKE Db")." ORDER BY Host, User");$Je=$H;if(!$H)$H=connection()->query("SELECT SUBSTRING_INDEX(CURRENT_USER, '@', 1) AS User, SUBSTRING_INDEX(CURRENT_USER, '@', -1) AS Host");echo"<form><p>\n";hidden_fields_get();echo
input_hidden("db",DB),($Je?"":input_hidden("grant")),"<table class='odds'>\n","<thead><tr><th>".'Username'."<th>".'Server'."<td class='hover'><tbody>\n";while($J=$H->fetch_assoc())echo'<tr><td>'.h($J["User"]),"<td>".h($J["Host"]),'<td class="hover"><a href="'.h(ME.'user='.url_escape($J["User"]).'&host='.url_escape($J["Host"])).'">'.'Edit'."</a>\n";if(!$Je||DB!="")echo"<tr><td><input name='user' autocapitalize='off'>","<td><input name='host' value='localhost' autocapitalize='off'>","<td class='hover'><input type='submit' value='".'Edit'."'>\n";echo"</table>\n","</form>\n";}elseif(isset($_GET["sql"])){if(!$m&&$_POST["export"]){save_settings(["output"=>$_POST["output"],"format"=>$_POST["format"]],"adminer_import");dump_headers("sql");if($_POST["format"]=="sql")echo"$_POST[query]\n";else{adminer()->dumpTable("","");adminer()->dumpData("","table",$_POST["query"]);adminer()->dumpFooter();}exit;}if(!$m&&$_POST["val"]){$ra=0;$im=true;$lb=[];$Vk=0;foreach($_POST["val"]as$K)$Vk+=count($K);$Wa=$Vk>1&&driver()->begin();foreach($_POST["val"]as$tm=>$K){$R=bracket_escape($tm,true);$o=fields($R);$um=indexes($R);foreach($K
as$u=>$J){parse_str(bracket_escape($u,true),$Z);$_n=[];foreach($Z["where"]as$x=>$X)$_n[bracket_escape($x,true)]=$X;if(!$o||$Z["null"]||array_diff_key($_n,$o)||!unique_array($_n,$um)){$im=false;break
2;}$O=[];$M=[];foreach($J
as$mg=>$X){$x=bracket_escape($mg,true);$n=idx($o,$x);if(!$n){$im=false;break
3;}$O[idf_escape($x)]=(preg_match('~char|text~',$n["type"])||$X!=""?adminer()->processInput($n,$X):"NULL");$M[$mg]=$x;}$lk=where($Z,$o);if(!driver()->update($R,$O," WHERE $lk",0," ")){$im=false;break
2;}$ra+=connection()->affected_rows;$e=[];foreach($M
as$x)$e[]=idf_escape($x);$Jn=driver()->select($R,$e,[$lk],$e);$Wh=($Jn?$Jn->fetch_row():[]);$hg=0;foreach($M
as$mg=>$x){$n=$o[$x];$gm=['type'=>(preg_match('~binary~',$n["type"])?'blob':$n["type"])];$lb["val[$tm][$u][$mg]"]=select_value(idx($Wh,$hg++),"",$gm,null);}}}if($Wa&&$im)$im=driver()->commit();queries_redirect(null,lang_format(['%d item has been affected.','%d items have been affected.'],$ra),$im);if($Wa&&!$im)driver()->rollback();page_headers();page_messages($m);foreach($lb
as$B=>$X)echo"<div data-name='".h($B)."' hidden>$X</div>\n";exit;}restart_session();$mf=&get_session("queries");$lf=&$mf[DB];if(!$m&&$_POST["clear"]){$lf=[];redirect(remove_from_uri("history"));}stop_session();$pa=get_settings("adminer_import");if($_POST&&$pa)save_settings($pa,"adminer_import");page_header((isset($_GET["import"])?'Import':'SQL command'),$m);$Jg=driver()->lineComment();$Ad=0;function
query_error_line($Om,$C,$G,$Ig){return
substr_count(substr($Om,0,$C),"\n")+($Ig?:substr_count(substr($G,0,strspn($G," \t\r\n")),"\n")+1);}if(!$m&&$_POST&&!(isset($_GET["import"])&&adminer()->importProcess())){$Gc=driver()->delimiter;$ye=false;if(!isset($_GET["import"]))$G=$_POST["query"];elseif($_POST["webfile"]){$Tl=adminer()->importServerPath();$ye=@fopen((file_exists($Tl)?$Tl:"compress.zlib://$Tl.gz"),"rb");$G=($ye?fread($ye,1e6):false);}else$G=get_file("sql_file",true,$Gc);if(is_string($G)){if(($kh=ini_bytes("memory_limit"))!="-1")ini_set("memory_limit",max($kh,strval(2*strlen($G)+memory_get_usage()+8e6)));if($G!=""&&strlen($G)<1e6){$fk=$G.(preg_match("~$Gc\\s*\$~",$G)?"":$Gc);if(!$lf||first(end($lf))!=$fk){restart_session();$lf[]=[$fk,time()];set_session("queries",$mf);stop_session();}}$Ql="(?:\\s|\xEF\xBB\xBF|/\\*[\s\S]*?\\*/|(?:$Jg)[^\n]*\n?|--\r?\n)";$C=0;$jk=0;$sd=true;$gc=false;$rh=false;$h=connect();if($h&&DB!=""){$h->select_db(DB);if($_GET["ns"]!="")set_schema($_GET["ns"],$h);}$Kb=0;$Cd=[];$ij='[\'"'.(JUSH=="sql"?'`':(JUSH=="sqlite"?'`[':(JUSH=="mssql"?'[':''))).']|/\*|'.$Jg.'|$'.(JUSH=="pgsql"?'|\$([a-zA-Z]\w*)?\$':'');$dn=microtime(true);while($G!=""){if(!$C&&preg_match("~^$Ql*+DELIMITER\\s+(\\S+)~i",$G,$A)){$Gc=preg_quote($A[1]);$G=substr($G,strlen($A[0]));$jk+=strlen($A[0]);}elseif(!$C&&JUSH=='pgsql'&&preg_match("~^($Ql*+COPY\\s+)[^;]+\\s+FROM\\s+stdin;~i",$G,$A)){$Gc="\n\\\\\\.\r?\n";$gc=true;$C=strlen($A[0]);}elseif(!$C&&JUSH=='pgsql'&&preg_match("~^$Ql*+\\\\connect[ \t][^\n]*~",$G,$A)){$Gc="\n";$rh=true;$C=strlen($A[0]);}else{preg_match("($Gc\\s*|$ij)",$G,$A,PREG_OFFSET_CAPTURE,$C);list($we,$F)=$A[0];if(!$we&&$ye&&!feof($ye))$G
.=fread($ye,1e5);else{if(!$we&&rtrim($G)=="")break;$C=$F+strlen($we);if($we&&!preg_match("(^$Gc)",$we)){$gb=driver()->hasCStyleEscapes()||(JUSH=="pgsql"&&($F>0&&strtolower($G[$F-1])=="e"));$zj=($we=='/*'?'\*/':($we=='['?']':(preg_match("~^(?:$Jg)~",$we)?"\n":preg_quote($we).($gb?'|\\\\.':''))));while(preg_match("($zj|\$)s",$G,$A,PREG_OFFSET_CAPTURE,$C)){$Wk=$A[0][0];if(!$Wk&&$ye&&!feof($ye))$G
.=fread($ye,1e5);else{$C=$A[0][1]+strlen($Wk);if(!$Wk||$Wk[0]!="\\")break;}}}else{$fk=substr($G,0,$F+($gc?3:0));$G=substr($G,$C);$gk=$jk;$jk+=$C;$C=0;if($gc||$rh){$Gc=driver()->delimiter;$gc=false;$rh=false;}$Cb="<code class='jush-".JUSH."'>".adminer()->sqlCommandQuery($fk)."</code>";if(preg_match("~^$Ql*+\$~",$fk)&&!preg_match('~/\*M?!~',$fk)){echo($_POST["only_errors"]?"":"<pre>$Cb</pre>\n");continue;}$sd=false;$Kb++;$Wj="<pre id='sql-$Kb'>$Cb</pre>\n";if(JUSH=="sqlite"&&preg_match("~^$Ql*+(ATTACH|VACUUM\\b.*\\bINTO)\\b~is",$fk,$A)!==0){echo$Wj,"<p class='error'>".sprintf('%s queries are not supported.',preg_match('~ATTACH~i',$A[1])?'ATTACH':'VACUUM INTO')."\n";$Cd[]=" <a href='#sql-$Kb'>$Kb</a>";if(!$Ad&&!isset($_GET["import"]))$Ad=query_error_line($_POST["query"],$gk,$fk,0);if($_POST["error_stops"])break;}else{if(!$_POST["only_errors"]){echo$Wj;ob_flush();flush();}$Zl=microtime(true);if(JUSH=="pgsql"&&preg_match("~^$Ql*+\\\\connect\\s+(\"(?:[^\"]|\"\")+\"|[^\\s\";]+)\\s*;*\\s*\$~",$fk,$A)){$k=idf_unescape($A[1]);if(connection()->select_db($k)){if($h)$h->select_db($k);echo($_POST["only_errors"]?"":"<p class='message'>".'Database'.": ".h($k)." <span class='time'>(".format_time($Zl).")</span>\n");continue;}}elseif(connection()->multi_query($fk)&&$h&&preg_match("~^$Ql*+USE\\b~i",$fk))$h->query($fk);do{$H=connection()->store_result();if(connection()->error){echo($_POST["only_errors"]?$Wj:""),"<p class='error'>".'Error in query'.(connection()->errno?" (".connection()->errno.")":"").": ".adminer()->error()."\n";$Cd[]=" <a href='#sql-$Kb'>$Kb</a>";if(!$Ad&&!isset($_GET["import"]))$Ad=query_error_line($_POST["query"],$gk,$fk,driver()->errorLine($fk));if($_POST["error_stops"])break
2;}else{$_=ME."sql=".url_escape(trim($fk));$Rm=" <span class='time'>(".format_time($Zl).")</span>".(strlen($_)<adminer()->sqlUrlLength()-100?" <a href='".h($_)."'>".'Edit'."</a>":"");$ra=connection()->affected_rows;$ko=($_POST["only_errors"]?"":driver()->warnings());$lo="warnings-$Kb";if($ko)$Rm
.=", <a href='#$lo' class='toggle'>".'Warnings'."</a>";$Nd="";$Od="explain-$Kb";if(is_object($H)){$z=$_POST["limit"];$gi=$z;$jd=!$_POST["only_errors"];if($jd)echo"<form method='post'>\n";$Oi=print_select_result($H,$h,[],$gi,$jd);if(!$_POST["only_errors"]){$gi=max($H->num_rows,$gi);echo"<p class='sql-footer'>".($gi?($z&&$gi>$z?sprintf('%d / ',$z):"").lang_format(['%d row','%d rows'],$gi):""),$Rm;if($h&&preg_match("~^($Ql|\\()*+SELECT\\b~i",$fk)&&($Nd=adminer()->explain($h,$fk,$Oi))!="")echo", <a href='#$Od' class='toggle'>Explain</a>";if($jd)echo", <input type='submit' name='save' value='".'Save'."' class='jsonly' disabled"." title='".'Ctrl+click on a value to modify it.'."'".on('click','sqlSave','Saving…').">";$t="export-$Kb";echo", <a href='#$t' class='toggle'>".'Export'."</a><span id='$t' class='hidden'>: ".html_select("output",adminer()->dumpOutput(),$pa["output"])." ".html_select("format",adminer()->dumpFormat(),$pa["format"]).input_hidden("query",$fk)."<input type='submit' name='export' value='".'Export'."'".($z?"":on('click','sqlExport')).">".input_token()."</span>\n"."</form>\n";}}else{if(preg_match("~^$Ql*+(CREATE|DROP|ALTER)$Ql++(DATABASE|SCHEMA)\\b~i",$fk)){restart_session();set_session("dbs",null);stop_session();}if(!$_POST["only_errors"])echo"<p class='message' title='".h(connection()->info)."'>".lang_format(['Query executed OK, %d row affected.','Query executed OK, %d rows affected.'],$ra)."$Rm\n";}echo($ko?"<div id='$lo' class='hidden'>\n$ko</div>\n":""),($Nd!=""?"<div id='$Od' class='hidden explain'>\n$Nd</div>\n":"");}$Zl=microtime(true);}while(connection()->next_result());}}}}}if($sd)echo"<p class='message'>".'No commands to execute.'."\n";else{$Af=connection()->inTransaction();driver()->rollback();if($Af)echo"<pre><code class='jush-".JUSH."'>ROLLBACK".(JUSH=="mssql"?" TRANSACTION":"")." -- Adminer</code></pre>\n";if($_POST["only_errors"])echo"<p class='message'>".lang_format(['%d query executed OK.','%d queries executed OK.'],$Kb-count($Cd))," <span class='time'>(".format_time($dn).")</span>\n";elseif($Cd&&$Kb>1)echo"<p class='error'>".'Error in query'.": ".implode("",$Cd)."\n";}}else
echo"<p class='error'>".upload_error($G)."\n";}echo'
<form method="post" enctype="multipart/form-data" id="form"';$Kn="";if(!isset($_GET["import"]))echo
on('submit','sqlSubmit',remove_from_uri("sql|limit|error_stops|only_errors|history"),adminer()->sqlUrlLength());else
echo
on_upload_progress($Kn);echo'>
';$Kd="<input type='submit' value='".'Execute'."' title='Ctrl+Enter'>";if(!isset($_GET["import"])){$fk=$_GET["sql"];if($_POST)$fk=$_POST["query"];elseif($_GET["history"]=="all")$fk=$lf;elseif($_GET["history"]!="")$fk=idx($lf[$_GET["history"]],0);echo"<p>";textarea("query",$fk,20,80,JUSH,$Ad);echo($_POST?"":script("qs('textarea').focus();")),"<p>";adminer()->sqlPrintAfter();echo"$Kd\n",'Limit rows'.": <input type='number' name='limit' class='size' value='".h($_POST?$_POST["limit"]:$_GET["limit"])."'>\n";}else{$Te=(extension_loaded("zlib")?"[.gz]":"");echo"<fieldset><legend>".'File upload'."</legend><div>",($Kn?input_hidden(ini_get("session.upload_progress.name"),$Kn):""),"SQL$Te: ".file_input(" name='sql_file[]' multiple","\n$Kd"),($Kn?" <progress class='jsonly hidden' max='1' value='0'></progress>":""),"</div></fieldset>\n";$yf=adminer()->importServerPath();if($yf)echo"<fieldset><legend>".'From server'."</legend><div>",sprintf('Webserver file %s',"<code>".h($yf)."$Te</code>")," <input type='submit' name='webfile' value='".'Run file'."'>","</div></fieldset>\n";adminer()->importPrint();echo"<p>";}echo
checkbox("error_stops",1,($_POST?$_POST["error_stops"]:isset($_GET["import"])||$_GET["error_stops"]),'Stop on error')."\n",checkbox("only_errors",1,($_POST?$_POST["only_errors"]:isset($_GET["import"])||$_GET["only_errors"]),'Show only errors')."\n",input_token();if(!isset($_GET["import"])&&$lf){print_fieldset("history",'History',$_GET["history"]!="");for($X=end($lf);$X;$X=prev($lf)){$x=key($lf);list($fk,$Rm,$od)=$X;echo'<div><a href="'.h(ME."sql=&history=$x").'" class="hover">'.'Edit'."</a>"." <span class='time' title='".@date('Y-m-d',$Rm)."'>".@date("H:i:s",$Rm)."</span>"." <code class='sql jush-".JUSH."'>".shorten_utf8(preg_replace('~\s+~',' ',ltrim(preg_replace("~^(?:$Jg).*~m",'',$fk))),80,"</code>").($od?" <span class='time'>($od)</span>":"")."</div>\n";}echo"<input type='submit' name='clear' value='".'Clear'."'>\n","<a href='".h(ME."sql=&history=all")."'>".'Edit all'."</a>\n","</div></fieldset>\n";}echo'</form>
';}elseif(isset($_GET["edit"])){$a=$_GET["edit"];$o=fields($a);$Z=(isset($_GET["select"])?($_POST["check"]&&count($_POST["check"])==1?where_check($_POST["check"][0],$o):""):where($_GET,$o));$Hn=(isset($_GET["select"])?$_POST["edit"]:$Z);foreach($o
as$B=>$n){if((!$Hn&&!isset($n["privileges"]["insert"]))||adminer()->fieldName($n)=="")unset($o[$B]);}if($_POST&&!$m&&!isset($_GET["select"])){$Og=relative_uri((string)$_POST["referer"]);if($_POST["insert"])$Og=($Hn?null:relative_uri());elseif(!preg_match('~^.+&select=.+$~',$Og))$Og=ME."select=".url_escape($a);$w=indexes($a);$An=unique_array($_GET["where"],$w);$lk="\nWHERE $Z";if(isset($_POST["delete"]))queries_redirect($Og,'Item has been deleted.',driver()->delete($a,$lk,$An?0:1));else{$O=[];foreach($o
as$B=>$n){$X=process_input($n);if($X!==false&&$X!==null)$O[idf_escape($B)]=$X;}if($Hn){if(!$O)redirect($Og);queries_redirect($Og,'Item has been updated.',driver()->update($a,$O,$lk,$An?0:1));if(is_ajax()){page_headers();page_messages($m);exit;}}else{$H=driver()->insert($a,$O);$zg=($H?last_id($H):0);queries_redirect($Og,sprintf('Item%s has been inserted.',($zg?" $zg":"")),$H);}}}$J=null;$G="";$Rm="";if($Z){$M=[];$jl=["*"];foreach($o
as$B=>$n){if(isset($n["privileges"]["select"])){$Fa=($_POST["clone"]&&$n["auto_increment"]?"''":convert_field($n));$d=($Fa?"$Fa AS ":"").idf_escape($B);$M[]=$d;if($Fa)$jl[]=$d;}}$J=[];if(!support("table")){$M=["*"];$jl=$M;}if($M){$Zl=microtime(true);$H=driver()->select($a,$M,[$Z],$M,[],(isset($_GET["select"])?2:1));$G=str_replace("SELECT ".implode(", ",$M),"SELECT ".implode(", ",$jl),driver()->query);$Rm=format_time($Zl);if(!$H)$m=adminer()->error();else{$J=$H->fetch_assoc();if(!$J)$J=false;}if(isset($_GET["select"])&&(!$J||$H->fetch_assoc()))$J=null;}}if(!$o&&driver()->primary!=""){if(!$Z){$H=driver()->select($a,["*"],[],["*"]);$J=($H?$H->fetch_assoc():false);if(!$J)$J=[driver()->primary=>""];}if($J){foreach($J
as$x=>$X){if(!$Z)$J[$x]=null;$o[$x]=["field"=>$x,"null"=>($x!=driver()->primary),"auto_increment"=>($x==driver()->primary)];}}}if($_POST["save"]){$Kj=[];foreach((array)$_POST["fields"]as$x=>$X)$Kj[bracket_escape($x,true)]=$X;$J=$Kj+($J?$J:[]);}edit_form($a,$o,$J,$Hn,$m,$G,$Rm);}elseif(isset($_GET["create"])){function
referencable_primary($ml){$I=[];foreach(table_status('',true)as$xm=>$R){if($xm!=$ml&&!$R["dependent"]&&fk_support($R)){foreach(fields($xm)as$n){if($n["primary"]){if($I[$xm]){unset($I[$xm]);break;}$I[$xm]=$n;}}}}return$I;}$a=$_GET["create"];$nj=driver()->partitionBy;$sj=($nj&&$a!=""?driver()->partitionsInfo($a):[]);$tk=referencable_primary($a);$ue=[];foreach($tk
as$xm=>$n)$ue[str_replace("`","``",$xm)."`".str_replace("`","``",$n["field"])]=$xm;$Si=[];$S=[];$di=false;if($a!=""){$Si=fields($a);$S=table_status1($a);$di=(count($S)<2);}$za=($a==""||driver()->supportsAlterTable($S));$J=$_POST;$J["fields"]=(array)$J["fields"];if($J["auto_increment_col"])$J["fields"][$J["auto_increment_col"]]["auto_increment"]=true;if($_POST&&!$m)save_settings(["comments"=>$_POST["comments"],"defaults"=>$_POST["defaults"]]);if($_POST&&!process_fields($J["fields"])&&!$m){if($_POST["drop"])queries_redirect(substr(ME,0,-1),'Table has been dropped.',drop_tables([$a]));else{$o=[];$xa=[];$On=false;$se=[];$Ri=reset($Si);$ta=" FIRST";foreach($J["fields"]as$n){$q=$ue[$n["type"]];$sn=($q!==null?$tk[$q]:$n);if($n["field"]!=""){if(!$n["generated"])$n["default"]=null;$n["default_sql"]=($n["generated"]=="DEFAULT SQL");$bk=process_field($n,$sn);$xa[]=[$n["orig"],$bk,$ta];if(!$Ri||$bk!==process_field($Ri,$Ri)){$o[]=[$n["orig"],$bk,$ta];if($n["orig"]!=""||$ta||(JUSH=="sqlite"&&$n["comment"]!=""))$On=true;}if($q!==null)$se[idf_escape($n["field"])]=($a!=""&&JUSH!="sqlite"?"ADD":" ").format_foreign_key(['table'=>$ue[$n["type"]],'source'=>[$n["field"]],'target'=>[$sn["field"]],'on_delete'=>$n["on_delete"],],object_name("FOREIGN",trim($J["name"]),[$n["field"]]));$ta=" AFTER ".idf_escape($n["field"]);}elseif($n["orig"]!=""){$On=true;$o[]=[$n["orig"]];}if($n["orig"]!=""){$Ri=next($Si);if(!$Ri)$ta="";}}$qj=[];if(in_array($J["partition_by"],$nj)){foreach($J
as$x=>$X){if(preg_match('~^partition~',$x))$qj[$x]=$X;}foreach($qj["partition_names"]as$x=>$B){if($B==""){unset($qj["partition_names"][$x]);unset($qj["partition_values"][$x]);}}$qj["partition_names"]=array_values($qj["partition_names"]);$qj["partition_values"]=array_values($qj["partition_values"]);if($qj==$sj)$qj=[];}elseif(preg_match("~partitioned~",$S["Create_options"]))$qj=null;$mh='Table has been altered.';if($a==""){cookie("adminer_engine",$J["Engine"]);$mh='Table has been created.';}$B=trim($J["name"]);$Og=ME.(support("table")?"table=":"select=").url_escape($B);$f=($J["Comment"]!=$S["Comment"]?$J["Comment"]:null);$vd=($J["Engine"]&&$J["Engine"]!=$S["Engine"]?$J["Engine"]:"");$H=alter_table($a,$B,(JUSH=="sqlite"&&($On||$se||$f!==null||$vd)?$xa:$o),$se,$f,$vd,($J["Collation"]&&$J["Collation"]!=$S["Collation"]?$J["Collation"]:""),($J["Auto_increment"]!=""?number($J["Auto_increment"]):""),$qj);if($H&&!Queries::$queries&&$a!=""&&!$o&&!$se)redirect($Og);queries_redirect($Og,$mh,$H);}}$bm=($a!=""?"alter":"create");page_header(($a!=""?'Alter table':'Create table'),$m,["table"=>$a],h($a),$di,doc_link(['sql'=>"$bm-table.html",'mariadb'=>($a!=""?"$bm-table":""),'pgsql'=>"sql-$bm"."table.html",'cockroach'=>"$bm-table",'mssql'=>"t-sql/statements/$bm-table-transact-sql",'sqlite'=>"lang_{$bm}table.html",'oracle'=>"sqlrf/".strtoupper($bm)."-TABLE.html",]));if(!$_POST){$wn=driver()->types();$J=["Engine"=>$_COOKIE["adminer_engine"],"fields"=>[["field"=>"","type"=>(isset($wn["int"])?"int":(isset($wn["integer"])?"integer":"")),"on_update"=>""]],"partition_names"=>[""],];if($a!=""){$J=$S;$J["name"]=$a;$J["fields"]=[];if(!$_GET["auto_increment"])$J["Auto_increment"]="";foreach($Si
as$n){if($n["generated"])$n["default"]=ltrim($n["default"]);$n["generated"]=$n["generated"]?:(isset($n["default"])?($n["default_sql"]?"DEFAULT SQL":"DEFAULT"):"");$J["fields"][]=$n;}if($nj){$J+=$sj;$J["partition_names"][]="";$J["partition_values"][]="";}}}$Gb=flat_collations();$wd=driver()->engines();foreach($wd
as$vd){if(!strcasecmp($vd,$J["Engine"])){$J["Engine"]=$vd;break;}}$Xg=max_input_vars(12,20);if($Xg){$if=(count($J["fields"])>$Xg?"":" hidden");echo"<p".($if?" id='max-fields' data-columns='$Xg'":"")." class='error$if'>".max_input_vars_error()."\n";}echo'
<form method="post" id="form">
<p>
';if(support("columns")||$a==""){echo'Table name'.": <input name='name'".($a==""&&!$_POST?" autofocus":"")." data-maxlength='64' value='".h($J["name"])."' autocapitalize='off'>\n",(!$za?h($S["Engine"])."\n":($wd?html_select("Engine",[""=>"(".'engine'.")"]+$wd,$J["Engine"],on('change','helpClose').on_help_value())."\n":""));if($Gb)echo"<datalist id='collations'>".optionlist($Gb)."</datalist>\n",(preg_match("~sqlite|mssql~",JUSH)?"":"<input list='collations' name='Collation' value='".h($J["Collation"])."' placeholder='(".'collation'.")'>\n");echo"<input type='submit' value='".'Save'."'>\n";}if(support("columns")&&$za){echo"<div class='scrollable'>\n","<table id='edit-fields' class='nowrap'>\n";edit_fields($J["fields"],$Gb,"TABLE",$ue);echo"</table>\n",script("editFields();"),"</div>\n<p>\n",'Auto Increment'.": <input type='number' name='Auto_increment' class='size' value='".h($J["Auto_increment"])."'>\n",checkbox("defaults",1,($_POST?$_POST["defaults"]:get_setting("defaults")),'Default values',on('click','columnShowClick',6),"jsonly");$Nb=($_POST?$_POST["comments"]:get_setting("comments"));if(support("comment")){echo
checkbox("comments",1,$Nb,'Comment',on('click','editingCommentsClick',true),"jsonly").' ';$c=" name='Comment' data-maxlength='".(min_version(5.5)?2048:60)."'".($Nb?"":" class='hidden'");echo
adminer()->commentInput('TABLE',$c,$J["Comment"]);}echo'<p>
<input type=\'submit\' value=\'Save\'>
';}echo'
';if($a!="")echo'<input type=\'submit\' name=\'drop\' value=\'Drop\'',confirm(sprintf('Drop %s?',$a)),'>
';if($nj&&(JUSH=='sql'||$a=="")){$oj=preg_match('~RANGE|LIST~',$J["partition_by"]);print_fieldset("partition",'Partition by',$J["partition_by"]);echo"<p>".html_select("partition_by",array_merge([""],$nj),$J["partition_by"],on('change','partitionByChange').on_help_value('.','PARTITION BY $&'))."\n","(<input name='partition' value='".h($J["partition"])."'>)\n",'Partitions'.": <input type='number' name='partitions' class='size".($oj||!$J["partition_by"]?" hidden":"")."' value='".h($J["partitions"])."'>\n","<table id='partition-table'".($oj?"":" class='hidden'").">\n","<thead><tr><th>".'Partition name'."<th>".'Values'."<tbody>\n";foreach($J["partition_names"]as$x=>$X)echo'<tr>','<td><input name="partition_names[]" value="'.h($X).'" autocapitalize="off"'.($x==count($J["partition_names"])-1?on('input','partitionNameChange'):'').'>','<td><input name="partition_values[]" value="'.h(idx($J["partition_values"],$x)).'">';echo"</table>\n</div></fieldset>\n";}echo
input_token(),'</form>
';}elseif(isset($_GET["indexes"])){$a=$_GET["indexes"];$Gf=["PRIMARY","UNIQUE","INDEX"];$S=table_status1($a,true);$Df=driver()->indexAlgorithms($S);if(preg_match('~MyISAM|M?aria'.(min_version(5.6,'10.0.5')?'|InnoDB':'').'~i',$S["Engine"]))$Gf[]="FULLTEXT";if(preg_match('~MyISAM|M?aria'.(min_version(5.7,'10.2.2')?'|InnoDB':'').'~i',$S["Engine"]))$Gf[]="SPATIAL";if(min_version('',11.7)&&preg_match('~MyISAM|InnoDB~i',$S["Engine"]))$Gf[]="VECTOR";$w=indexes($a);$o=fields($a);$Uj=[];if(JUSH=="mongo"){$Uj=$w["_id_"];unset($Gf[0]);unset($w["_id_"]);}$J=$_POST;if($J)save_settings(["index_options"=>$J["options"]]);if($_POST&&!$m&&!$_POST["add"]&&!$_POST["drop_col"]){$b=[];foreach($J["indexes"]as$v){$B=$v["name"];if(in_array($v["type"],$Gf)){$e=[];$Fg=[];$Kc=[];$Bi=[];$Ef=(support("partial_indexes")?$v["partial"]:"");$Cf=(in_array($v["algorithm"],$Df)?$v["algorithm"]:"");$O=[];ksort($v["columns"]);foreach($v["columns"]as$x=>$d){if($d!=""){$y=idx($v["lengths"],$x);$Ic=idx($v["descs"],$x);$Ai=idx($v["opclasses"],$x);$O[]=($o[$d]?idf_escape($d):$d).($y?"(".(+$y).")":"").($Ai!=""?" ".idf_escape($Ai):"").($Ic?" DESC":"");$e[]=$d;$Fg[]=($y?:null);$Kc[]=$Ic;$Bi[]="$Ai";}}$Ld=$w[$B];if($Ld){ksort($Ld["columns"]);ksort($Ld["lengths"]);ksort($Ld["descs"]);if($v["type"]==$Ld["type"]&&array_values($Ld["columns"])===$e&&(!$Ld["lengths"]||array_values($Ld["lengths"])===$Fg)&&array_values($Ld["descs"])===$Kc&&(!$Ld["opclasses"]||array_values($Ld["opclasses"])===$Bi)&&$Ld["partial"]==$Ef&&(!$Df||$Ld["algorithm"]==$Cf)&&!$Ld["invalid"]){unset($w[$B]);continue;}}if($e)$b[]=[$v["type"],$B,$O,$Cf,$Ef];}}foreach($w
as$B=>$Ld)$b[]=[$Ld["type"],$B,"DROP"];if(!$b)redirect(ME."table=".url_escape($a));queries_redirect(ME."table=".url_escape($a),'Indexes have been altered.',alter_indexes($a,$b));}page_header('Indexes',$m,["table"=>$a],h($a),false,doc_link(['sql'=>"create-index.html",'pgsql'=>"sql-createindex.html",'cockroach'=>"create-index",'mssql'=>"t-sql/statements/create-index-transact-sql",'sqlite'=>"lang_createindex.html",'oracle'=>"sqlrf/CREATE-INDEX.html",]));$de=array_keys($o);if($_POST["add"]){foreach($J["indexes"]as$x=>$v){if($v["columns"][count($v["columns"])]!="")$J["indexes"][$x]["columns"][]="";}$v=end($J["indexes"]);if($v["type"]||array_filter($v["columns"],'strlen'))$J["indexes"][]=["columns"=>[1=>""]];}if(!$J){foreach($w
as$x=>$v){$w[$x]["name"]=$x;$w[$x]["columns"][]="";}$w[]=["columns"=>[1=>""]];$J["indexes"]=$w;}$Fg=(JUSH=="sql"||JUSH=="mssql");$Bi=driver()->indexOpclasses();$Fl=($_POST?$_POST["options"]:get_setting("index_options"));$Kh=[];foreach($Gf
as$U)$Kh[$U]=str_replace("{table}",$a,adminer()->namePattern($U));echo'
<form method="post">
<div class="scrollable">
<table class="nowrap odds">
<thead><tr>
<th id="label-type">Index Type
';$wf=" class='idxopts".($Fl?"":" hidden")."'";if($Df)echo"<th id='label-algorithm'$wf>".'Algorithm'.doc_link(['sql'=>'create-index.html#create-index-storage-engine-index-types','mariadb'=>'storage-engine-index-types/','pgsql'=>'indexes-types.html','cockroach'=>'create-index#parameters',]);echo'<th><input type="submit" hidden>','Columns'.($Fg?"<span$wf> (".'length'.")</span>":"");if($Fg||support("descidx"))echo
checkbox("options",1,$Fl,'Options',on('click','indexOptionsShow'),"jsonly")."\n";echo'<th id="label-name">Name
';if(support("partial_indexes"))echo"<th id='label-condition'$wf>".'Condition';echo'<td><noscript>',icon("plus","add[0]","+",'Add next'),'</noscript>
<tbody>
';if($Uj){echo"<tr><td>PRIMARY<td>";foreach($Uj["columns"]as$x=>$d)echo
select_input(" disabled",array_combine($de,$de),$d),"<label><input disabled type='checkbox'>".'descending'."</label> ";echo"<td><td>\n";}$hg=1;foreach($J["indexes"]as$v){if(!$_POST["drop_col"]||$hg!=key($_POST["drop_col"])){echo"<tr><td>".html_select("indexes[$hg][type]",[-1=>""]+$Gf,$v["type"],on('change','indexesChangeType',$Kh),"label-type");if($Df)echo"<td$wf>".html_select("indexes[$hg][algorithm]",array_merge([""],$Df),$v['algorithm'],"","label-algorithm");echo"<td>";ksort($v["columns"]);$s=1;foreach($v["columns"]as$x=>$d){echo"<span>".select_input(" name='indexes[$hg][columns][$s]' title='".'Column'."'".on('change','indexesChangeColumn',$Kh),($o&&($d==""||$o[$d])?array_combine($de,$de):[]),$d)," <span$wf>",($Fg?"<input type='number' name='indexes[$hg][lengths][$s]' class='size' value='".h(idx($v["lengths"],$x))."' title='".'Length'."'>":"");if($Bi){$Ai=idx($v["opclasses"],$x);echo
html_select("indexes[$hg][opclasses][$s]",[""=>"(".'operator class'.")"]+array_combine($Bi,$Bi)+($Ai!=""?[$Ai=>$Ai]:[]),$Ai),doc_link(['pgsql'=>'indexes-opclass.html']);}echo(support("descidx")?checkbox("indexes[$hg][descs][$s]",1,idx($v["descs"],$x),'descending'):""),"<br>","</span></span>";$s++;}echo"<td><input name='indexes[$hg][name]' value='".h($v["name"])."' autocapitalize='off' aria-labelledby='label-name'>\n";if(support("partial_indexes"))echo"<td$wf><input name='indexes[$hg][partial]' value='".h($v["partial"])."' autocapitalize='off' aria-labelledby='label-condition'>\n";echo"<td>".icon("cross","drop_col[$hg]","x",'Remove',on('click','editingRemoveRow','indexes$1[type]')),(idx($w[$v["name"]],"invalid")?" <span class='error'>INVALID</span>":""),"\n";}$hg++;}echo'</table>
</div>
<p>
<input type=\'submit\' value=\'Save\'>
';if(support("concurrent_indexes"))echo
checkbox("concurrently",1,$_POST["concurrently"],"concurrently").doc_link(['pgsql'=>'sql-createindex.html#SQL-CREATEINDEX-CONCURRENTLY'])."\n";echo
input_token(),'</form>
';}elseif(isset($_GET["database"])){$J=$_POST;if($_POST&&!$m&&!$_POST["add"]){$B=trim($J["name"]);if($_POST["drop"]){$_GET["db"]="";queries_redirect(remove_from_uri("db|database"),'Database has been dropped.',drop_databases([DB]));}elseif($B!==DB){if(DB!=""){$_GET["db"]=$B;queries_redirect(preg_replace('~\bdb=[^&]*&~','',ME)."db=".url_escape($B),'Database has been renamed.',rename_database($B,(string)$J["collation"]));}else{$j=explode("\n",str_replace("\r","",$B));$im=true;$xg="";foreach($j
as$k){if(count($j)==1||$k!=""){if(!create_database($k,(string)$J["collation"]))$im=false;$xg=$k;}}restart_session();set_session("dbs",null);queries_redirect(preg_replace('~&db=[^&]*~','',ME)."db=".url_escape($xg),'Database has been created.',$im);}}else{if(!$J["collation"])redirect(substr(ME,0,-1));query_redirect("ALTER DATABASE ".idf_escape($B).(preg_match('~^[a-z0-9_]+$~i',$J["collation"])?" COLLATE $J[collation]":""),substr(ME,0,-1),'Database has been altered.');}}$bm=(DB!=""?"alter":"create");page_header(DB!=""?'Alter database':'Create database',$m,[],h(DB),false,doc_link(['sql'=>"$bm-database.html",'mariadb'=>(DB!=""?"":"$bm-database"),'pgsql'=>"sql-$bm"."database.html",'cockroach'=>"$bm-database",'mssql'=>"t-sql/statements/$bm-database-transact-sql",'oracle'=>"sqlrf/".strtoupper($bm)."-USER.html",]));$Gb=collations();$B=DB;if($_POST)$B=$J["name"];elseif(DB!="")$J["collation"]=db_collation(DB,$Gb);elseif(JUSH=="sql"){foreach(get_vals("SHOW GRANTS")as$Je){if(preg_match('~ ON (`(([^\\\\`]|``|\\\\.)*)%`\.\*)?~',$Je,$A)&&$A[1]){$B=stripcslashes(idf_unescape("`$A[2]`"));break;}}}echo'
<form method="post">
<p>
',($_POST["add"]||strpos($B,"\n")?"<textarea autofocus name='name' rows='10' cols='40'>\n".h($B).'</textarea><br>':'<input name="name" autofocus value="'.h($B).'" data-maxlength="64" autocapitalize="off">')."\n",($Gb?"<datalist id='collations'>".optionlist(flat_collations())."</datalist>"."<input list='collations' name='collation' value='".h($J["collation"])."' placeholder='(".'collation'.")'>".doc_link(['sql'=>"charset-charsets.html",'mariadb'=>"supported-character-sets-and-collations/",'mssql'=>"relational-databases/system-functions/sys-fn-helpcollations-transact-sql",]):"")."\n",'<input type=\'submit\' value=\'Save\'>
';if(DB!="")echo"<input type='submit' name='drop' value='".'Drop'."'".confirm(sprintf('Drop %s?',DB)).">\n";elseif(!$_POST["add"]&&$_GET["db"]=="")echo
icon("plus","add[0]","+",'Add next')."\n";echo
input_token(),'</form>
';}elseif(isset($_GET["scheme"])){$J=$_POST;$f=(support("comment")&&$_GET["ns"]!=""?schema_comment($_GET["ns"]):"");if($_POST&&!$m){$_=preg_replace('~&ns=[^&]*~','',ME)."ns=";if($_POST["drop"])query_redirect("DROP SCHEMA ".idf_escape($_GET["ns"]),$_,'Schema has been dropped.');else{$B=trim($J["name"]);$_
.=url_escape($B);$Rh=(support("comment")?$J["comment"]:$f);$H=true;$mh='Schema has been altered.';if($_GET["ns"]==""){$H=queries("CREATE SCHEMA ".idf_escape($B));$mh='Schema has been created.';}elseif($_GET["ns"]!=$B)$H=queries("ALTER SCHEMA ".idf_escape($_GET["ns"])." RENAME TO ".idf_escape($B));elseif($Rh===$f)redirect($_);if($H&&$Rh!==$f)$H=set_schema_comment($B,$Rh);queries_redirect($_,$mh,$H);}}$bm=($_GET["ns"]!=""?"alter":"create");page_header($_GET["ns"]!=""?'Alter schema':'Create schema',$m,[],"",false,doc_link(['pgsql'=>"sql-$bm"."schema.html",'cockroach'=>"$bm-schema",'mssql'=>"t-sql/statements/create-schema-transact-sql",]));if(!$J){$J["name"]=$_GET["ns"];$J["comment"]=$f;}echo'
<form method="post">
<p><input name="name" autofocus value="',h($J["name"]),'" autocapitalize="off">
<input type=\'submit\' value=\'Save\'>
';if($_GET["ns"]!="")echo"<input type='submit' name='drop' value='".'Drop'."'".confirm(sprintf('Drop %s?',$_GET["ns"])).">\n";echo(support("comment")?"<p>".'Comment'.": ".adminer()->commentInput('SCHEMA'," name='comment'",$J["comment"])."\n":""),input_token(),'</form>
';}elseif(isset($_GET["call"])){$ba=($_GET["name"]?:$_GET["call"]);$Sk=(isset($_GET["callf"])?"FUNCTION":"PROCEDURE");$Nk=routine($_GET["call"],$Sk);$eg=(idx($Nk["returns"],"type")=="trigger");page_header(($eg?'Triggers':'Call').": ".h($ba),$m,"#routines","",!$Nk,(isset($_GET["callf"])?"":doc_link(['sql'=>"call.html",'pgsql'=>"sql-call.html",'cockroach'=>"call",'mssql'=>"t-sql/language-elements/execute-transact-sql",])));$zf=[];$Yi=[];foreach($Nk["fields"]as$s=>$n){if(substr($n["inout"],-3)=="OUT"&&JUSH=='sql')$Yi[$s]="@".idf_escape($n["field"])." AS ".idf_escape($n["field"]);if(!$n["inout"]||preg_match('~^(IN|OUTPUT)~',$n["inout"]))$zf[]=$s;}if(!$m&&$_POST){$ib=[];foreach($Nk["fields"]as$x=>$n){$X="";if(in_array($x,$zf)){$X=process_input($n);if($X===false)$X="''";if(isset($Yi[$x]))connection()->query("SET @".idf_escape($n["field"])." = $X");}if(isset($Yi[$x]))$ib[]="@".idf_escape($n["field"]);elseif(in_array($x,$zf))$ib[]=$X;}$Da=implode(", ",$ib);$G=(isset($_GET["callf"])||JUSH!="mssql"?(isset($_GET["callf"])?"SELECT ":"CALL ").(idx($Nk["returns"],"type")=="record"?"* FROM ":"").table($ba)."($Da)":"EXEC ".table($ba).($Da!=""?" $Da":""));$Zl=microtime(true);$H=connection()->multi_query($G);$ra=connection()->affected_rows;echo
adminer()->selectQuery($G,$Zl,!$H);if(!$H)echo"<p class='error'>".adminer()->error()."\n";else{$h=connect();if($h)$h->select_db(DB);do{$H=connection()->store_result();if(is_object($H))print_select_result($H,$h);else
echo"<p class='message'>".lang_format(['Routine has been called, %d row affected.','Routine has been called, %d rows affected.'],$ra)." <span class='time'>".@date("H:i:s")."</span>\n";}while(connection()->next_result());if($Yi)print_select_result(connection()->query("SELECT ".implode(", ",$Yi)));}}if($eg){$pn=driver()->routineTriggers($_GET["call"]);if($pn){echo"<table>\n";foreach($pn
as$J){$_=preg_replace('~&ns=[^&]*~',"&ns=".url_escape($J["ns"]),ME);echo"<tr><th>".h($J["trigger"])."<td><a href='".h($_."table=".url_escape($J["table"])."#triggers")."'>".($J["ns"]!=$_GET["ns"]?"<b>".h($J["ns"])."</b>.":"").h($J["table"])."</a>"."<td class='hover'><a href='".h($_."trigger=".url_escape($J["table"])."&name=".url_escape($J["trigger"]))."'>".'Alter'."</a>\n";}echo"</table>\n";}}else{echo'<form method="post">
';if($zf){echo"<table class='layout'>\n";foreach($zf
as$x){$n=$Nk["fields"][$x];$B=$n["field"];echo"<tr><th>".adminer()->fieldName($n);$Y=idx($_POST["fields"],$B);if($Y!=""){if($n["type"]=="set")$Y=implode(",",$Y);}input($n,$Y,idx($_POST["function"],$B,""));echo"\n";}echo"</table>\n";}echo'<p>
<input type=\'submit\' value=\'Call\'>
',input_token(),'</form>
';}echo'
',adminer()->commentValue($Sk,$Nk['comment']);}elseif(isset($_GET["foreign"])){$a=$_GET["foreign"];$B=$_GET["name"];$J=$_POST;if($_POST&&!$m&&!$_POST["add"]&&!$_POST["change"]&&!$_POST["change-js"]){if(!$_POST["drop"]){$J["source"]=array_filter($J["source"],'strlen');ksort($J["source"]);$Im=[];foreach($J["source"]as$x=>$X)$Im[$x]=$J["target"][$x];$J["target"]=$Im;}$Wa=false;if(JUSH=="sqlite")$H=recreate_table($a,$a,[],[],[" $B"=>($J["drop"]?"":" ".format_foreign_key($J))]);else{$Wb=$J["constraint"];$b="ALTER TABLE ".table($a);$cd="$b DROP ".(JUSH=="sql"?"FOREIGN KEY ":"CONSTRAINT ").idf_escape($B?:'');if($J["drop"])$H=queries($cd);else{$ka=" ADD".format_foreign_key($J,$Wb);if($B=="")$H=queries($b.$ka);elseif(JUSH=="pgsql"||(JUSH=="sql"&&$Wb!=$B))$H=queries("$cd,$ka");else{$Wa=support("transaction_ddl")&&driver()->begin();$H=($Wa||idx(foreign_keys($a),$B)?queries($cd):true)&&queries($b.$ka)&&(!$Wa||driver()->commit());}}}queries_redirect(ME."table=".url_escape($a),($J["drop"]?'Foreign key has been dropped.':($B!=""?'Foreign key has been altered.':'Foreign key has been created.')),$H);if($Wa)driver()->rollback();if(!$J["drop"])$m='Source and target columns must have the same data type, there must be an index on the target columns and the referenced data must exist.';}$di=false;if(!$_POST&&$B!=""){$ue=foreign_keys($a);$J=idx($ue,$B,[]);$di=!$J;}$Jh=str_replace("{table}",$a,adminer()->namePattern("FOREIGN"));$Mh="^".str_replace('\{columns\}','.*',preg_quote($Jh))."$";page_header(($B!=""?'Alter foreign key':'Create foreign key'),$m,["table"=>$a],h($B!=""?$B:$a),$di,doc_link(['sql'=>"innodb-foreign-key-constraints.html",'mariadb'=>"foreign-keys/",'pgsql'=>"sql-createtable.html#SQL-CREATETABLE-PARMS-REFERENCES",'cockroach'=>"foreign-key",'mssql'=>"t-sql/statements/create-table-transact-sql",'oracle'=>"sqlrf/constraint.html",]));if($_POST){ksort($J["source"]);if($_POST["change"]||$_POST["change-js"])$J["target"]=[];else$J["source"][]="";}elseif($B!=""){$J["source"][]="";$J["constraint"]=$B;}else{$J["table"]=$a;$J["source"]=[""];$J["constraint"]=str_replace("{columns}","",$Jh);}echo'
<form method="post">
';$Ol=array_keys(fields($a));if($J["db"]!="")connection()->select_db($J["db"]);$Ti=null;if($J["ns"]!=""){$Ti=get_schema();set_schema($J["ns"]);}$sk=array_keys(array_filter(table_status('',true),function(array$S){return!$S["dependent"]&&fk_support($S);}));$Im=array_keys(fields(in_array($J["table"],$sk)?$J["table"]:reset($sk)));$c=on('change','foreignChange');echo"<p><label>".'Target table'.": ".html_select("table",$sk,$J["table"],$c)."</label>\n";if(support("scheme")){$ei=($J["ns"]!=""?$J["ns"]:$_GET["ns"]);$Zk=array_filter(adminer()->schemas(),function($L)use($ei){return!driver()->isSystem(DB,$L)||$L==$ei||$L==$_GET["ns"];});echo"<label>".'Schema'.": ".html_select("ns",$Zk,$ei,$c)."</label>";if($Ti!==null)set_schema($Ti);}elseif(JUSH!="sqlite"){$kl=($J["db"]!=""?$J["db"]:DB);$yc=[];foreach(adminer()->databases()as$k){if(!driver()->isSystem($k)||$k==$kl||$k==DB)$yc[]=$k;}echo"<label>".'DB'.": ".html_select("db",$yc,$kl,$c)."</label>";}if($J["db"]!="")connection()->select_db(DB);echo
input_hidden("change-js"),'<noscript><p><input type=\'submit\' name=\'change\' value=\'Change\'></noscript>
<table',on('change','foreignName',$Mh,$Jh),'>
<thead><tr><th id="label-source">Source<th id="label-target">Target<tbody>
';$hg=0;foreach($J["source"]as$x=>$X){echo"<tr>","<td>".html_select("source[".(+$x)."]",[-1=>""]+$Ol,$X,($hg==count($J["source"])-1?on('change','foreignAddRow'):""),"label-source"),"<td>".html_select("target[".(+$x)."]",$Im,idx($J["target"],$x),"","label-target");$hg++;}echo'</table>
<p>
<label>ON DELETE: ',html_select("on_delete",[-1=>""]+explode("|",driver()->onActions),$J["on_delete"]),'</label>
<label>ON UPDATE: ',html_select("on_update",[-1=>""]+explode("|",driver()->onActions),$J["on_update"]),'</label>
',(support("deferrable")?html_select("deferrable",['NOT DEFERRABLE','DEFERRABLE','DEFERRABLE INITIALLY DEFERRED'],$J["deferrable"]):''),'<p>
<input type=\'submit\' value=\'Save\'>
<noscript><p><input type=\'submit\' name=\'add\' value=\'Add column\'></noscript>
';if($B!="")echo'<input type=\'submit\' name=\'drop\' value=\'Drop\'',confirm(sprintf('Drop %s?',$B)),'>
';if(JUSH!="sqlite"){print_fieldset("options",'Options',$J["constraint"]!=$B&&!preg_match("($Mh)",$J["constraint"]));echo"<label>".'Name'.": <input name='constraint' value='".h($J["constraint"])."' autocapitalize='off'></label>\n","</div></fieldset>\n";}echo
input_token(),'</form>
';}elseif(isset($_GET["view"])){$a=$_GET["view"];$J=$_POST;$Ui="VIEW";if(JUSH=="pgsql"&&$a!=""){$P=table_status1($a);$Ui=strtoupper($P["Engine"]);}if($_POST&&!$m){$B=trim($J["name"]);$Fa=" AS\n$J[select]";$Og=ME."table=".url_escape($B);$mh='View has been altered.';$U=($_POST["materialized"]?"MATERIALIZED VIEW":"VIEW");if(!$_POST["drop"]&&$a==$B&&JUSH!="sqlite"&&$U=="VIEW"&&$Ui=="VIEW")query_redirect((JUSH=="mssql"?"ALTER":"CREATE OR REPLACE")." VIEW ".table($B).$Fa,$Og,$mh);else{$Mm="adminer_".uniqid();drop_create("DROP $Ui ".table($a),"CREATE $U ".table($B).$Fa,"DROP $U ".table($B),"CREATE $U ".table($Mm).$Fa,"DROP $U ".table($Mm),($_POST["drop"]?substr(ME,0,-1):$Og),'View has been dropped.',$mh,'View has been created.',$a,$B);}}$di=false;if(!$_POST&&$a!=""){$J=view($a);$di=!$J["select"];$J["name"]=$a;$J["materialized"]=($Ui!="VIEW");if(!$m)$m=adminer()->error();}page_header(($a!=""?'Alter view':'Create view'),$m,["table"=>$a],h($a),$di,doc_link(['sql'=>"create-view.html",'pgsql'=>"sql-createview.html",'cockroach'=>"create-view",'mssql'=>"t-sql/statements/create-view-transact-sql",'sqlite'=>"lang_createview.html",'oracle'=>"sqlrf/CREATE-VIEW.html",]));echo'
<form method="post">
<p>Name: <input name="name" value="',h($J["name"]),'" data-maxlength="64" autocapitalize="off">
',(support("materializedview")?" ".checkbox("materialized",1,$J["materialized"],'Materialized view'):""),'<p>';textarea("select",$J["select"]);echo'<p>
<input type=\'submit\' value=\'Save\'>
';if($a!="")echo'<input type=\'submit\' name=\'drop\' value=\'Drop\'',confirm(sprintf('Drop %s?',$a)),'>
';echo
input_token(),'</form>
';}elseif(isset($_GET["event"])){$aa=$_GET["event"];$Uf=["YEAR","QUARTER","MONTH","DAY","HOUR","MINUTE","WEEK","SECOND","YEAR_MONTH","DAY_HOUR","DAY_MINUTE","DAY_SECOND","HOUR_MINUTE","HOUR_SECOND","MINUTE_SECOND"];$dm=["ENABLED"=>"ENABLE","DISABLED"=>"DISABLE","SLAVESIDE_DISABLED"=>"DISABLE ON SLAVE"];$J=$_POST;if($_POST&&!$m){if($_POST["drop"])query_redirect("DROP EVENT ".idf_escape($aa),substr(ME,0,-1),'Event has been dropped.');elseif(in_array($J["INTERVAL_FIELD"],$Uf)&&isset($dm[$J["STATUS"]])){$Xk="\nON SCHEDULE ".($J["INTERVAL_VALUE"]?"EVERY ".q($J["INTERVAL_VALUE"])." $J[INTERVAL_FIELD]".($J["STARTS"]?" STARTS ".q($J["STARTS"]):"").($J["ENDS"]?" ENDS ".q($J["ENDS"]):""):"AT ".q($J["STARTS"]))." ON COMPLETION".($J["ON_COMPLETION"]?"":" NOT")." PRESERVE";queries_redirect(substr(ME,0,-1),($aa!=""?'Event has been altered.':'Event has been created.'),queries(($aa!=""?"ALTER EVENT ".idf_escape($aa).$Xk.($aa!=$J["EVENT_NAME"]?"\nRENAME TO ".idf_escape($J["EVENT_NAME"]):""):"CREATE EVENT ".idf_escape($J["EVENT_NAME"]).$Xk)."\n".$dm[$J["STATUS"]]." COMMENT ".q($J["EVENT_COMMENT"]).rtrim(" DO\n$J[EVENT_DEFINITION]",";").";"));}}$di=false;if(!$J&&$aa!=""){$K=get_rows("SELECT * FROM information_schema.EVENTS WHERE EVENT_SCHEMA = ".q(DB)." AND EVENT_NAME = ".q($aa));$di=!$K;$J=reset($K);}page_header(($aa!=""?'Alter event'.": ".h($aa):'Create event'),$m,"#events","",$di,doc_link(['sql'=>"create-event.html"]));echo'
<form method="post">
<table class="layout">
<tr><th>Name<td><input name="EVENT_NAME" value="',h($J["EVENT_NAME"]),'" data-maxlength="64" autocapitalize="off">
<tr><th title="datetime">Start<td><input name="STARTS" value="',h("$J[EXECUTE_AT]$J[STARTS]"),'">
<tr><th title="datetime">End<td><input name="ENDS" value="',h($J["ENDS"]),'">
<tr><th>Every
<td><input type="number" name="INTERVAL_VALUE" value="',h($J["INTERVAL_VALUE"]),'" class="size"> ',html_select("INTERVAL_FIELD",$Uf,$J["INTERVAL_FIELD"]),'<tr><th>Status<td>',html_select("STATUS",$dm,$J["STATUS"]),'<tr><th>Comment<td><input name="EVENT_COMMENT" value="',h($J["EVENT_COMMENT"]),'" data-maxlength="64">
<tr><th><td>',checkbox("ON_COMPLETION","PRESERVE",$J["ON_COMPLETION"]=="PRESERVE",'On completion preserve'),'</table>
<p>';textarea("EVENT_DEFINITION",$J["EVENT_DEFINITION"]);echo'<p>
<input type=\'submit\' value=\'Save\'>
';if($aa!="")echo'<input type=\'submit\' name=\'drop\' value=\'Drop\'',confirm(sprintf('Drop %s?',$aa)),'>
';echo
input_token(),'</form>
';}elseif(isset($_GET["procedure"])){$ba=($_GET["name"]?:$_GET["procedure"]);$Nk=(isset($_GET["function"])?"FUNCTION":"PROCEDURE");$J=$_POST;$J["fields"]=(array)$J["fields"];if($_POST&&!process_fields($J["fields"])&&!$m){foreach($J["fields"]as$x=>$n){if($n["field"]=="")unset($J["fields"][$x]);}$ui=routine($_GET["procedure"],$Nk);$si=($ui?routine_id($ba,$ui):"");$Th=routine_id($J["name"],$J);$i=create_routine($Nk,$J);$Og=substr(ME,0,-1);$mh='Routine has been altered.';if(!$_POST["drop"]&&$si==$Th&&connection()->flavor!="mysql")queries_redirect($Og,$mh,queries(substr_replace($i,(JUSH=="mssql"?' OR ALTER':' OR REPLACE'),6,0)));else{$Mm="adminer_".uniqid();drop_create("DROP $Nk $si",$i,"DROP $Nk $Th",create_routine($Nk,["name"=>$Mm]+$J),"DROP $Nk ".routine_id($Mm,$J),$Og,'Routine has been dropped.',$mh,'Routine has been created.',$ba,$J["name"]);}}$di=false;if(!$_POST&&$ba!=""){$J=routine($_GET["procedure"],$Nk);$di=!$J;$J["name"]=$ba;}$Qk=strtolower($Nk);page_header(($ba!=""?(isset($_GET["function"])?'Alter function':'Alter procedure').": ".h($ba):(isset($_GET["function"])?'Create function':'Create procedure')),$m,"#routines","",$di,doc_link(['sql'=>"create-procedure.html",'mariadb'=>"create-$Qk/",'pgsql'=>"sql-create$Qk.html",'cockroach'=>"create-$Qk",'mssql'=>"t-sql/statements/create-$Qk-transact-sql",]));if(!$_POST&&$ba=="")$J["language"]="sql";$Gb=(JUSH=="sql"?flat_collations():[]);$Ok=routine_languages();echo($Gb?"<datalist id='collations'>".optionlist($Gb)."</datalist>":""),'
<form method="post" id="form">
<p>Name: <input name="name" value="',h($J["name"]),'" data-maxlength="64" autocapitalize="off">
',($Ok?"<label>".'Language'.": ".html_select("language",array_keys($Ok),$J["language"],on('change','routineLanguage',$Ok))."</label>\n":""),'<input type=\'submit\' value=\'Save\'>
<div class="scrollable">
<table id="edit-fields" class="nowrap">
';edit_fields($J["fields"],$Gb,$Nk);if(isset($_GET["function"])){echo"<tr><td>".'Return type';edit_type("returns",(array)$J["returns"],$Gb,[],(JUSH=="pgsql"?["void","trigger"]:[]));}echo'</table>
',script("editFields();"),'</div>
<p>';textarea("definition",$J["definition"],20,80,($Ok[$J["language"]]?:JUSH));echo'<p>
<input type=\'submit\' value=\'Save\'>
';if($ba!="")echo'<input type=\'submit\' name=\'drop\' value=\'Drop\'',confirm(sprintf('Drop %s?',$ba)),'>
';$Rk=routine_options($Nk);if($Rk){$Ii=false;foreach($Rk
as$x=>$bo){$l=($bo?reset($bo):"");$J["options"][$x]=idx($J["options"],$x,$l);if($J["options"][$x]!=$l)$Ii=true;}print_fieldset("options",'Options',$Ii);echo"<table class='layout'>\n";foreach($Rk
as$x=>$bo){$sg="label-option-$x";$Um=str_replace("_"," ",$x);$M=[];foreach($bo
as$Y)$M[$Y]=(strpos($Y,"$Um ")===0?substr($Y,strlen($Um)+1):$Y);echo"<tr><th id='$sg'>$Um<td>".($M?html_select("options[$x]",$M,$J["options"][$x],"",$sg):"<input name='options[$x]' value='".h($J["options"][$x])."' aria-labelledby='$sg' autocapitalize='off'>")."\n";}echo"</table>\n</div></fieldset>\n";}echo
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
as$Y){if($Y===idx($wi,$s))$s++;else$I[]="ALTER TYPE ".idf_escape($U)." ADD VALUE $Y".($s<count($wi)?" BEFORE ".$wi[$s]:"");}return($s==count($wi)?$I:null);}$ea=$_GET["type"];$J=$_POST;$tn=($ea!=""?array_search($ea,types(true)):0);$U=($tn?type_definition(+$tn):[]);$ii=($U["kind"]=='d'?"DOMAIN":"TYPE");if($_POST&&!$m){$_=substr(ME,0,-1);$B=trim($J["name"]);$Fa=trim(str_replace("\r","",$J["as"]));$Vh=(preg_match('~^AS\s+(?!ENUM\b|RANGE\b|\()~i',$Fa)?"DOMAIN":"TYPE");$mh='Type has been altered.';$b=(!$_POST["drop"]&&$ea!=""&&$Vh==$ii?($Fa==$U["definition"]?[]:add_enum_values($ea,$U["definition"],$Fa)):null);if($b!==null){if($ea!=$B)$b[]="ALTER $ii ".idf_escape($ea)." RENAME TO ".idf_escape($B);if(!$b)redirect($_);$Vd=false;foreach($b
as$G){if(!queries($G)){$Vd=true;break;}}queries_redirect($_,$mh,!$Vd);}else{$Mm="adminer_".uniqid();drop_create("DROP $ii ".idf_escape($ea),"CREATE $Vh ".idf_escape($B)." $Fa","DROP $Vh ".idf_escape($B),"CREATE $Vh ".idf_escape($Mm)." $Fa","DROP $Vh ".idf_escape($Mm),$_,'Type has been dropped.',$mh,'Type has been created.',$ea,$B);}}page_header(($ea!=""?'Alter type'.": ".h($ea):'Create type'),$m,"#user-types","",($tn===false),doc_link(['pgsql'=>"sql-createtype.html",'cockroach'=>"create-type",]));if(!$J){$J["name"]=$ea;$J["as"]=($ea!=""?$U["definition"]:"AS ");}echo'
<form method="post">
<p>
','Name'.": <input name='name' value='".h($J['name'])."' autocapitalize='off'>\n";textarea("as",$J["as"]);echo"<p><input type='submit' value='".'Save'."'>\n";if($ea!="")echo"<input type='submit' name='drop' value='".'Drop'."'".confirm(sprintf('Drop %s?',$ea)).">\n";echo
input_token(),'</form>
';}elseif(isset($_GET["check"])){$a=$_GET["check"];$B="$_GET[name]";$J=$_POST;if($J&&!$m){$Og=ME."table=".url_escape($a);$ph='Check has been dropped.';$nh='Check has been altered.';$oh='Check has been created.';if(JUSH=="sqlite"&&!(min_version(3.53)&&($B==""||idx(driver()->checkConstraints($a),$B)!=$B))){$ka=($J["drop"]?"":($J["name"]!=""?"CONSTRAINT ".idf_escape($J["name"])." ":"")."CHECK ($J[clause])");queries_redirect($Og,($J["drop"]?$ph:($B!=""?$nh:$oh)),recreate_table($a,$a,[],[],[],"",[],"$B",$ka));}else{$b="ALTER TABLE ".table($a);$rb=" CHECK ($J[clause])";$Mm="adminer_".uniqid();drop_create("$b DROP CONSTRAINT ".idf_escape($B),"$b ADD".($J["name"]!=""?" CONSTRAINT ".idf_escape($J["name"]):"").$rb,"$b DROP CONSTRAINT ".idf_escape($J["name"]),"$b ADD CONSTRAINT ".idf_escape($Mm).$rb,"$b DROP CONSTRAINT ".idf_escape($Mm),$Og,$ph,$nh,$oh,$B,$J["name"]);}}$di=false;if(!$J){$vb=driver()->checkConstraints($a);$di=($B!=""&&!$vb[$B]);$J=["name"=>($B==""?object_name("CHECK",$a,[]):($vb[$B]!=$B?$B:"")),"clause"=>$vb[$B]];}page_header(($B!=""?'Alter check':'Create check'),$m,["table"=>$a],h($B!=""?$B:$a),$di,doc_link(['sql'=>"create-table-check-constraints.html",'mariadb'=>"constraint/",'pgsql'=>"ddl-constraints.html#DDL-CONSTRAINTS-CHECK-CONSTRAINTS",'cockroach'=>"check",'mssql'=>"relational-databases/tables/create-check-constraints",'sqlite'=>"lang_createtable.html#check_constraints",]));echo'
<form method="post">
<p><label>Name: <input name="name" value="',h($J["name"]),'" data-maxlength="64" autocapitalize="off"></label>
<p>';textarea("clause",$J["clause"]);echo'<p><input type=\'submit\' value=\'Save\'>
';if($B!="")echo'<input type=\'submit\' name=\'drop\' value=\'Drop\'',confirm(sprintf('Drop %s?',$B)),'>
';echo
input_token(),'</form>
';}elseif(isset($_GET["trigger"])){$a=$_GET["trigger"];$B="$_GET[name]";$on=trigger_options();$J=trigger($B,$a);$di=($B!=""&&!$J);$Jh=str_replace("{table}",$a,adminer()->namePattern("TRIGGER"));$J+=["Trigger"=>strtr($Jh,["{timing}"=>"b","{event}"=>"i","{columns}"=>"","{type}"=>"row"])];if($_POST){if(!$m&&in_array($_POST["Timing"],$on["Timing"])&&in_array($_POST["Event"],$on["Event"])&&in_array($_POST["Type"],$on["Type"])){$xi=" ON ".table($a);$cd="DROP TRIGGER ".idf_escape($B).(JUSH=="pgsql"?$xi:"");$Og=ME."table=".url_escape($a);if($_POST["drop"])query_redirect($cd,$Og,'Trigger has been dropped.');else{if($B!="")queries($cd);queries_redirect($Og,($B!=""?'Trigger has been altered.':'Trigger has been created.'),queries(create_trigger($xi,$_POST)));if($B!="")queries(create_trigger($xi,$J+["Type"=>reset($on["Type"])]));}}$J=$_POST;}page_header(($B!=""?'Alter trigger':'Create trigger'),$m,["table"=>$a],h($B!=""?$B:$a),$di,doc_link(['sql'=>"create-trigger.html",'pgsql'=>"sql-createtrigger.html",'cockroach'=>"create-trigger",'mssql'=>"t-sql/statements/create-trigger-transact-sql",'sqlite'=>"lang_createtrigger.html",'oracle'=>"lnpls/CREATE-TRIGGER-statement.html",]));$Mh=strtr(preg_quote($Jh),['\{timing\}'=>'[abi]','\{event\}'=>'[iud]*','\{columns\}'=>'.*','\{type\}'=>'(row|statement)']);$mn=on('change','triggerChange',"^$Mh$",$Jh);$li=on('input','triggerChange',"^$Mh$",$Jh);echo'
<form method="post" id="form">
<table class="layout">
<tr><th>Time
<td>',html_select("Timing",$on["Timing"],$J["Timing"],$mn),'<tr><th>Event<td>',html_select("Event",$on["Event"],$J["Event"],$mn),(in_array("UPDATE OF",$on["Event"])?" <input name='Of' value='".h($J["Of"])."' class='hidden'$li>":""),'<tr><th>Type<td>',html_select("Type",$on["Type"],$J["Type"],$mn),'<tr><th>Name<td><input name="Trigger" value="',h($J["Trigger"]),'" data-maxlength="64" autocapitalize="off">
</table>
',script("fire(qs('#form')['Timing'], 'change');"),'<p>';textarea("Statement",$J["Statement"]);echo'<p>
<input type=\'submit\' value=\'Save\'>
';if($B!="")echo'<input type=\'submit\' name=\'drop\' value=\'Drop\'',confirm(sprintf('Drop %s?',$B)),'>
';echo
input_token(),'</form>
';}elseif(isset($_GET["user"])){function
grant($Je,array$Zj,$e,$xi){if(!$Zj)return
true;if($Zj==["ALL PRIVILEGES","GRANT OPTION"])return($Je=="GRANT"?queries("$Je ALL PRIVILEGES$xi WITH GRANT OPTION"):queries("$Je ALL PRIVILEGES$xi")&&queries("$Je GRANT OPTION$xi"));return
queries("$Je ".preg_replace('~(GRANT OPTION)\([^)]*\)~','\1',implode("$e, ",$Zj).$e).$xi);}$fa=$_GET["user"];$Zj=[""=>["All privileges"=>""]];foreach(get_rows("SHOW PRIVILEGES")as$J){foreach(explode(",",($J["Privilege"]=="Grant option"?"":$J["Context"]))as$bc)$Zj[$bc=="File access on server"?"Server Admin":$bc][$J["Privilege"]]=$J["Comment"];}unset($Zj["Server Admin"]["Usage"]);foreach($Zj["Tables"]as$x=>$X)unset($Zj["Databases"][$x]);$Sh=[];if($_POST){foreach($_POST["objects"]as$x=>$X)$Sh[$X]=(array)$Sh[$X]+idx($_POST["grants"],$x,[]);}$Ke=[];$H=(isset($_GET["host"])?connection()->query("SHOW GRANTS FOR ".q($fa)."@".q($_GET["host"])):null);$di=(isset($_GET["host"])&&!$H);if($H){while($J=$H->fetch_row()){if(preg_match('~GRANT (.*) ON (.*) TO ~',$J[0],$A)&&preg_match_all('~ *([^(,]*[^ ,(])( *\([^)]+\))?~',$A[1],$Ug,PREG_SET_ORDER)){foreach($Ug
as$X){if($X[1]!="USAGE")$Ke["$A[2]$X[2]"][$X[1]]=true;if(preg_match('~ WITH GRANT OPTION~',$J[0]))$Ke["$A[2]$X[2]"]["GRANT OPTION"]=true;}}}}if($_POST&&!$m){$vi=(isset($_GET["host"])?q($fa)."@".q($_GET["host"]):"''");if($_POST["drop"])query_redirect("DROP USER $vi",ME."privileges=",'User has been dropped.');else{$Xh=q($_POST["user"])."@".q($_POST["host"]);$uj=$_POST["pass"];$jc=false;$H=true;if($vi!=$Xh){$jc=queries("CREATE USER $Xh IDENTIFIED BY ".($_POST["hashed"]?"PASSWORD ":"").q($uj));$H=$jc;}elseif($uj!="")$H=queries("SET PASSWORD FOR $Xh = ".(min_version(8,99)||$_POST["hashed"]?q($uj):"PASSWORD(".q($uj).")"));if($H){$Jk=[];foreach($Sh
as$ii=>$Je){if(isset($_GET["grant"]))$Je=array_filter($Je);$Je=array_keys($Je);if(isset($_GET["grant"]))$Jk=array_diff(array_keys(array_filter($Sh[$ii],'strlen')),$Je);elseif($vi==$Xh){$ri=array_keys((array)$Ke[$ii]);$Jk=array_diff($ri,$Je);$Je=array_diff($Je,$ri);unset($Ke[$ii]);}if(preg_match('~^(.+)\s*(\(.*\))?$~U',$ii,$A)&&(!grant("REVOKE",$Jk,$A[2]," ON $A[1] FROM $Xh")||!grant("GRANT",$Je,$A[2]," ON $A[1] TO $Xh"))){$H=false;break;}}}if($H&&isset($_GET["host"])){if($vi!=$Xh)queries("DROP USER $vi");elseif(!isset($_GET["grant"])){foreach($Ke
as$ii=>$Jk){if(preg_match('~^(.+)(\(.*\))?$~U',$ii,$A))grant("REVOKE",array_keys($Jk),$A[2]," ON $A[1] FROM $Xh");}}}if($H&&!Queries::$queries)redirect(ME."privileges=");queries_redirect(ME."privileges=",(isset($_GET["host"])?'User has been altered.':'User has been created.'),$H);if($jc)connection()->query("DROP USER $Xh");}}page_header((isset($_GET["host"])?'Username'.": ".h("$fa@$_GET[host]"):'Create user'),$m,["privileges"=>['','Privileges']],"",$di,doc_link(['sql'=>"grant.html",'mariadb'=>"grant"]));$J=$_POST;if($J)$Ke=$Sh;else{$J=$_GET+["host"=>get_val("SELECT SUBSTRING_INDEX(CURRENT_USER, '@', -1)")];$Ke[(DB==""||$Ke?"":idf_escape(addcslashes(DB,"%_\\"))).".*"]=[];}echo'<form method="post">
<table class="layout">
<tr><th>Server<td><input name="host" data-maxlength="60" value="',h($J["host"]),'" autocapitalize="off">
<tr><th>Username<td><input name="user" data-maxlength="80" value="',h($J["user"]),'" autocapitalize="off">
<tr><th>Password<td><input name="pass" id="pass" value="',h($J["pass"]),'" autocomplete="new-password">
',($J["hashed"]?"":script("typePassword(qs('#pass'));")),(min_version(8,99)?"":checkbox("hashed",1,$J["hashed"],'Hashed',on('click','hashedClick'))),'</table>

',"<table class='odds'>\n","<thead><tr><th colspan='2'>".'Privileges';$s=0;foreach($Ke
as$ii=>$Je){echo'<th>'.($ii!="*.*"?"<input name='objects[$s]' value='".h($ii)."' size='10' autocapitalize='off'>":input_hidden("objects[$s]","*.*")."*.*");$s++;}echo"<tbody>\n";foreach([""=>"","Server Admin"=>'Server',"Databases"=>'Database',"Tables"=>'Table',"Procedures"=>'Routine',]as$bc=>$Ic){foreach((array)$Zj[$bc]as$Yj=>$f){echo"<tr><td".($Ic?">$Ic<td":" colspan='2'").' lang="en" title="'.h($f).'">'.h($Yj);$s=0;foreach($Ke
as$ii=>$Je){$B="'grants[$s][".h(strtoupper($Yj))."]'";$Y=$Je[strtoupper($Yj)];if($bc=="Server Admin"&&$ii!=(isset($Ke["*.*"])?"*.*":".*"))echo"<td>";elseif(isset($_GET["grant"]))echo"<td><select name=$B><option><option value='1'".($Y?" selected":"").">".'Grant'."<option value='0'".($Y=="0"?" selected":"").">".'Revoke'."</select>";else
echo"<td align='center'><label class='block'>","<input type='checkbox' name=$B value='1'".($Y?" checked":"").($Yj=="All privileges"?" id='grants-$s-all'":($Yj=="Grant option"?"":on('click','grantsClick',"grants-$s-all"))).">","</label>";$s++;}}}echo"</table>\n",'<p>
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
',script("tableCheck();");}elseif($_GET["select"]!=""){$a=$_GET["select"];$S=table_status1($a);$w=indexes($a);$o=fields($a);$ue=column_foreign_keys($a);$pi=$S["Oid"];$Lk=[];$e=[];$dl=[];$Li=[];$Pm=null;foreach($o
as$x=>$n){$B=adminer()->fieldName($n);$Lh=html_entity_decode(strip_tags($B),ENT_QUOTES);if(isset($n["privileges"]["select"])&&$B!=""){$e[$x]=$Lh;if(is_shortable($n))$Pm=adminer()->selectLengthProcess();}if(isset($n["privileges"]["where"])&&$B!="")$dl[$x]=$Lh;if(isset($n["privileges"]["order"])&&$B!="")$Li[$x]=$Lh;$Lk+=$n["privileges"];}list($M,$Me)=adminer()->selectColumnsProcess($e,$w);$M=array_unique($M);$Me=array_unique($Me);$bg=count($Me)<count($M);$Z=adminer()->selectSearchProcess($o,$w,$S);$Ki=adminer()->selectOrderProcess($o,$w);$z=adminer()->selectLimitProcess();if($_GET["val"]&&is_ajax()){header("Content-Type: text/plain; charset=utf-8");foreach($_GET["val"]as$Bn=>$J){$Fa=convert_field($o[key($J)]);$M=[$Fa?:idf_escape(key($J))];$Z[]=where_check(bracket_escape($Bn,true),$o);$I=driver()->select($a,$M,$Z,$M);if($I)echo
first($I->fetch_row());}exit;}$Uj=$En=[];foreach($w
as$v){if($v["type"]=="PRIMARY"){$Uj=array_flip($v["columns"]);$En=($M?$Uj:[]);foreach($En
as$x=>$X){if(in_array(idf_escape($x),$M))unset($En[$x]);}break;}}if($pi&&!$Uj){$Uj=$En=[$pi=>0];$w[]=["type"=>"PRIMARY","columns"=>[$pi]];}if($_POST&&!$m){$no=$Z;if(!$_POST["all"]&&is_array($_POST["check"])){$vb=[];foreach($_POST["check"]as$rb)$vb[]=where_check($rb,$o);$no[]="((".implode(") OR (",$vb)."))";}$po=$no;$no=($no?"\nWHERE ".implode(" AND ",$no):"");if($_POST["export"]){save_settings(["output"=>$_POST["output"],"format"=>$_POST["format"]],"adminer_import");dump_headers($a);adminer()->dumpTable($a,"");$il=($M?:["*"]);$ec=convert_fields($e,$o,$M);if($ec)$il[]=substr($ec,2);$G="";if(is_array($_POST["check"])&&!$Uj){$Ae=implode(", ",$il)."\nFROM ".table($a);$Oe=($Me&&$bg?"\nGROUP BY ".implode(", ",$Me):"").($Ki?"\nORDER BY ".implode(", ",$Ki):"");$zn=[];foreach($_POST["check"]as$X)$zn[]="(SELECT".limit($Ae,"\nWHERE ".($Z?implode(" AND ",$Z)." AND ":"").where_check($X,$o).$Oe,1).")";$G=implode(" UNION ALL ",$zn);}adminer()->dumpData($a,"table",$G,$il,$po,($bg?$Me:[]),$Ki);adminer()->dumpFooter();exit;}if(!adminer()->selectEmailProcess($Z,$ue)){if($_POST["save"]||$_POST["delete"]){$H=true;$ra=0;$Wa=false;$O=[];if(!$_POST["delete"]){foreach($o
as$B=>$X){$u=bracket_escape($B);if(isset($_POST["fields"][$u])||$_FILES["fields-$u"]){$X=process_input($o[$B]);if($X!==null&&($_POST["clone"]||$X!==false))$O[idf_escape($B)]=($X!==false?$X:idf_escape($B));}}}if($_POST["delete"]||$O){$G=($_POST["clone"]?"INTO ".table($a)." (".implode(", ",array_keys($O)).")\nSELECT ".implode(", ",$O)."\nFROM ".table($a):"");if($_POST["all"]||($Uj&&is_array($_POST["check"]))||$bg){$H=($_POST["delete"]?driver()->delete($a,$no):($_POST["clone"]?queries("INSERT $G$no".driver()->insertReturning($a)):driver()->update($a,$O,$no)));$ra=connection()->affected_rows;if(is_object($H))$ra+=$H->num_rows;}else{$Wa=count((array)$_POST["check"])>1&&driver()->begin();foreach((array)$_POST["check"]as$X){$mo="\nWHERE ".($Z?implode(" AND ",$Z)." AND ":"").where_check($X,$o);$H=($_POST["delete"]?driver()->delete($a,$mo,1):($_POST["clone"]?queries("INSERT".limit1($a,$G,$mo)):driver()->update($a,$O,$mo,1)));if(!$H)break;$ra+=connection()->affected_rows;}if($Wa&&$H&&!driver()->commit())$H=false;}}$mh=lang_format(['%d item has been affected.','%d items have been affected.'],$ra);if($_POST["clone"]&&$H&&$ra==1){$zg=last_id($H);if($zg)$mh=sprintf('Item%s has been inserted.'," $zg");}queries_redirect(remove_from_uri($_POST["all"]&&$_POST["delete"]?"page|next":""),$mh,$H);if($Wa)driver()->rollback();if(!$_POST["delete"]){$Kj=(array)$_POST["fields"];edit_form($a,array_intersect_key($o,$Kj),$Kj,!$_POST["clone"],$m);page_footer();exit;}}elseif(!$_POST["import"]){$H=true;$ra=0;$Wa=count((array)$_POST["val"])>1&&driver()->begin();foreach((array)$_POST["val"]as$Bn=>$J){$O=[];foreach($J
as$x=>$X){$x=bracket_escape($x,true);$O[idf_escape($x)]=(preg_match('~char|text~',$o[$x]["type"])||$X!=""?adminer()->processInput($o[$x],$X):"NULL");}$H=driver()->update($a,$O," WHERE ".($Z?implode(" AND ",$Z)." AND ":"").where_check(bracket_escape($Bn,true),$o),($bg||$Uj?0:1)," ");if(!$H)break;$ra+=connection()->affected_rows;}if($Wa)$H=$H&&driver()->commit();queries_redirect(remove_from_uri(),lang_format(['%d item has been affected.','%d items have been affected.'],$ra),$H);if($Wa)driver()->rollback();}else{save_settings(["format"=>$_POST["separator"]],"adminer_import");$ee=get_file("csv_file",true);if(!is_string($ee))$m=upload_error($ee);elseif(!preg_match('~~u',$ee))$m='File must be in UTF-8 encoding.';else{$Hb=array_keys($o);$ol=($_POST["separator"]=="csv"?",":($_POST["separator"]=="tsv"?"\t":";"));$nc=parse_csv($ee,$ol);$ra=count($nc);driver()->begin();$K=[];foreach($nc
as$x=>$bo){if(!$x&&!array_diff($bo,$Hb)){$Hb=$bo;$ra--;}else{$O=[];foreach($bo
as$s=>$Db)$O[idf_escape($Hb[$s])]=($Db==""&&$o[$Hb[$s]]["null"]?"NULL":q(csv_value($Db)));$K[]=$O;}}$H=(!$K||driver()->insertUpdate($a,$K,$Uj));if($H)driver()->commit();queries_redirect(remove_from_uri("page|next"),lang_format(['%d row has been imported.','%d rows have been imported.'],$ra),$H);driver()->rollback();}}}}$xm=adminer()->tableName($S);if(is_ajax()){page_headers();ob_start();}else
page_header('Select'.": $xm",$m,[],"",(!$o&&support("table")),($o?doc_link([JUSH=>driver()->tableHelp($a,is_view($S))]):""));$O=null;if(isset($Lk["insert"])||!support("table")){$O="";foreach((array)$_GET["where"]as$X){$Y=$X["val"];if(is_array($Y))$Y=(count($Y)==1&&preg_match('~^val-(.*)~s',reset($Y),$A)?$A[1]:"");if($X["col"]!=""&&$Y!=""&&($X["op"]=="="||(!$X["op"]&&(is_array($X["val"])||!preg_match('~[_%]~',$Y)))))$O
.="&set[".url_escape(bracket_escape($X["col"]))."]=".url_escape($Y);}}adminer()->selectLinks($S,$O);if(!$e&&support("table"))echo"<p class='error'>".'Unable to select the table.'."\n";else{echo"<form id='form'>\n","<div hidden>";hidden_fields_get();echo(DB!=""?input_hidden("db",DB).(isset($_GET["ns"])?input_hidden("ns",$_GET["ns"]):""):""),input_hidden("select",$a),"</div>\n";adminer()->selectColumnsPrint($M,$e);adminer()->selectSearchPrint($Z,$dl,$w,$S);adminer()->selectOrderPrint($Ki,$Li,$w);adminer()->selectLimitPrint($z);if($Pm!==null)adminer()->selectLengthPrint($Pm);adminer()->selectActionPrint($w);echo"</form>\n";foreach((array)$_GET["where"]as$X){if($X["op"]=="SQL"&&!in_array($_SERVER["HTTP_SEC_FETCH_SITE"],["","same-origin"])){echo"<p class='error'>".'Invalid CSRF token. Submit the form again.'.' '.'If you did not send this request from Adminer, close this page.'."\n";page_footer();exit;}}$D=$_GET["page"];$xe=null;if($D=="last"){$xe=get_val(count_rows($a,$Z,$bg,$Me));$D=floor(max(0,intval($xe)-1)/$z);}$hl=$M;$Ne=$Me;if(!$hl){$hl[]="*";$ec=convert_fields($e,$o,$M);if($ec)$hl[]=substr($ec,2);}foreach($M
as$x=>$X){$n=$o[idf_unescape($X)];if($n&&($Fa=convert_field($n)))$hl[$x]="$Fa AS $X";}if(JUSH=="pgsql"||JUSH=="mssql"){foreach((array)$_GET["columns"]as$x=>$X){if(isset($hl[$x])&&$X["fun"])$hl[$x].=" AS ".idf_escape(apply_sql_function($X["fun"],($X["col"]!=""?$X["col"]:"*")));}}if(!$bg&&$En){foreach($En
as$x=>$X){$hl[]=idf_escape($x);if($Ne)$Ne[]=idf_escape($x);}}$H=driver()->select($a,$hl,$Z,$Ne,$Ki,$z,$D,true);if(!is_object($H))echo"<p class='error'>".(adminer()->error()?:'Unknown error.')."\n";else{if(JUSH=="mssql"&&$D)$H->seek($z*$D);$rd=[];$K=[];while($J=$H->fetch_assoc()){if($D&&JUSH=="oracle")unset($J["RNUM"]);$K[]=$J;}$Ze=($z&&(support("cursor")?$_GET["next"]!="":count($K)>=$z));if(is_ajax()&&$Ze)header("X-Next-Page: ".pagination_href($D+1));if($_GET["modify"]&&$K){$dh=max_input_vars(count($K[0])+1,20);echo($dh&&count($K)>$dh?"<p class='error'>".max_input_vars_error()."\n":"");}echo"<form method='post' enctype='multipart/form-data'".on_upload_progress($Kn).">\n";if($_GET["page"]!="last"&&$z&&$Me&&$bg&&JUSH=="sql")$xe=get_val(" SELECT FOUND_ROWS()");if(!$K)echo"<p class='message'>".'No rows.'."\n";else{$Sa=adminer()->backwardKeys($a,$xm);$Gk=[];reset($M);foreach($K[0]as$x=>$X){if(!isset($En[$x])){$X=idx($_GET["columns"],key($M))?:[];$Gk[$x]=["fun"=>$X["fun"],"col"=>($M?$X["col"]:$x)];next($M);}}echo"<div class='scrollable'>","<table id='table' class='nowrap checkable odds'".on('click','tableClick').on('dblclick','tableClick').on('keydown','editingKeydown').">\n","<thead><tr>".(!$Me&&$M?"":"<td class='hover check sticky'><input type='checkbox' id='all-page' class='jsonly' title='".'All rows on this page'."'".on('click','formCheck','^check').">");$Nh=[];$pk=1;foreach($Gk
as$x=>$X){$n=$o[$X["col"]];$B=($n?adminer()->fieldName($n,$pk):($X["fun"]?($X["col"]!=""?h($X["col"]):"*"):h($x)));if($B!=""){$pk++;$Nh[$x]=$B;$d=idf_escape($x);$pf=remove_from_uri('(order|desc)[^=]*|page|next').'&order[0]='.url_escape($x);$Ic="&desc[0]=1";$Ll=preg_replace('~ DESC( NULLS LAST)?$~','',$Ki[0]);$Nl=($Ll==$d||$Ll==$x);echo"<th id='th[".h(bracket_escape($x))."]'".($Nl?" aria-sort='".($Ll==$Ki[0]?"ascending":"descending")."'":"").">";$Fe=apply_sql_function(h($X["fun"]),$B);$Ml=isset($n["privileges"]["order"])||$X["fun"];echo($Ml?"<a href='".h($pf.($Nl&&$Ll==$Ki[0]?$Ic:''))."'>$Fe</a>":$Fe),doc_link([JUSH=>$n["doc"]]);$lh=($Ml?"<a href='".h($pf.$Ic)."' title='".'descending'."' class='text'> ↓</a>":'');if(!$X["fun"]&&isset($n["privileges"]["where"]))$lh
.="<a href='#fieldset-search' title='".'Search'."' class='text jsonly'".on('click','selectSearch',$x)."> =</a>";echo($lh?"<span class='column'>$lh</span>":"");}}$Fg=[];if($_GET["modify"]){foreach($K
as$J){foreach($J
as$x=>$X)$Fg[$x]=max($Fg[$x],min(40,utf8_length((string)$X)));}}$kf=[];$jf=[];foreach((array)$_GET["where"]as$X){$X+=["col"=>"","op"=>"","val"=>""];$Db=$X["col"];$cl=$X["val"];if(!is_array($cl)&&($cl!=""||preg_match('~NULL$~',$X["op"]))&&(!$X["op"]||in_array($X["op"],adminer()->operators($S)))){$Hg=strtr(preg_quote($cl),["%"=>".*?","_"=>"."]);$_j=["LIKE %%"=>$Hg,"ILIKE %%"=>$Hg,"REGEXP"=>$cl]+(JUSH=="pgsql"?["~"=>$cl,"~*"=>$cl]:[])+($Db!=""?[]:["="=>'^'.preg_quote($cl).'\z',"IN"=>'^(?:'.implode("|",array_map('preg_quote',array_map('trim',explode(",",$cl)))).')\z',"LIKE"=>"^$Hg\\z","ILIKE"=>"^$Hg\\z","FIND_IN_SET"=>'(?<=^|,)'.preg_quote($cl).'(?=,|\z)',]);foreach(($Db!=""?[$Db=>$o[$Db]]:$o)as$B=>$n){if($Db!=""||is_searchable($n,$X)){$_i=$X["op"]?:(!preg_match('~'.text_type().'~',$n["type"])?"IN":(preg_match('~%~',$cl)?"LIKE":"LIKE %%"));if(isset($_j[$_i])){$yb=preg_match('~^ILIKE|\*$~',$_i)||($_i!="~"&&preg_match('~^(sql|mssql|sqlite)$~',JUSH));$kf[$B][]="(?".($yb?"i":"").":$_j[$_i])";}elseif($_i=="IS NULL"&&$Db=="")$jf[$B]=true;}}}}echo($Sa?"<th>".'Relations':"")."<tbody>\n";if(is_ajax())ob_end_clean();foreach(adminer()->rowDescriptions($K,$ue)as$Hh=>$J){$An=unique_array($K[$Hh],$w);if(!$An){$An=[];foreach($K[$Hh]as$x=>$X){if(!in_array(idx(idx($Gk,$x,[]),"fun"),driver()->grouping))$An[$x]=$X;}}$Bn="";$s=0;foreach($An
as$x=>$X){$Fk=idx($Gk,$x,[]);if($M&&$Fk&&!isset($o[$Fk["col"]]))continue;$Fe=idx($Fk,"fun","");$Db=($Fe?$Fk["col"]:$x);$n=(array)$o[$Db];$ag=is_blob($n);if(!$Fe&&strlen($X)>64&&driver()->md5(idf_escape($Db),$n)){$Fe="md5";$X=md5($ag?(string)driver()->value($X,$n):$X);}if($Fe){$Bn
.="&fun[$s]=".url_escape($Fe)."&col[$s]=".url_escape($Db).($X!==null?"&val[$s]=".url_escape($X===false?"f":$X):"");$s++;}else$Bn
.="&".($X!==null?"where[".url_escape(bracket_escape($Db))."]=".url_escape($X===false?"f":$X):"null[]=".url_escape($Db));}echo"<tr>".(!$Me&&$M?"":"<td class='hover check sticky'>".($bg||information_schema(DB)?"":"<a href='".h(ME."edit=".url_escape($a).$Bn)."' class='edit'>".'edit'."</a> ").checkbox("check[]",substr($Bn,1),in_array(substr($Bn,1),(array)$_POST["check"])));foreach($J
as$x=>$X){if(isset($Nh[$x])){$Fe=$Gk[$x]["fun"];$Db=$Gk[$x]["col"];$n=(array)$o[$x];if($X!=""&&(!isset($rd[$x])||$rd[$x]!=""))$rd[$x]=(is_mail($X)?$Nh[$x]:"");$_="";if(is_blob($n)&&$X!="")$_=ME.'download='.url_escape($a).'&field='.url_escape($x).$Bn;if(!$_&&$X!==null){foreach((array)$ue[$x]as$q){if(count($ue[$x])==1||end($q["source"])==$x){$_="";foreach($q["source"]as$s=>$Ol)$_
.=where_link($s,$q["target"][$s],$K[$Hh][$Ol]);$_=($q["db"]!=""?preg_replace('~&db=[^&]*~',"&db=".url_escape($q["db"]),ME):ME).'select='.url_escape($q["table"]).$_;if($q["ns"]!="")$_=preg_replace('~&ns=[^&]*~',"&ns=".url_escape($q["ns"]),$_);if(count($q["source"])==1)break;}}}if($Fe=="count"&&$Db==""){$_=ME."select=".url_escape($a);$s=0;foreach((array)$_GET["where"]as$W){if(!array_key_exists($W["col"],$An))$_
.=where_link($s++,$W["col"],$W["val"],$W["op"]);}foreach($An
as$lg=>$W){if(idx(idx($Gk,$lg,[]),"fun")){$_="";break;}$_
.=where_link($s++,$lg,$W);}}$qf=select_value($X,$_,$n,$Pm,($Fe?[]:idx($kf,$x,[])));if($X===null&&!$Fe&&isset($jf[$x]))$qf="<mark>$qf</mark>";$u=bracket_escape($Bn);$t=h("val[$u][".bracket_escape($x)."]");$Mj=idx(idx($_POST["val"],$u),bracket_escape($x));$Hn=idx($n["privileges"],"update")&&!is_identity_always($n);$md=$K[$Hh][$x];$nd=!is_array($J[$x])&&!is_blob($n)&&is_utf8($md)&&!$Fe&&!$n["generated"]&&$Hn;$U=($Fe=="min"||$Fe=="max"?$o[$Db]["type"]:$n["type"]);$Om=preg_match('~text|json|lob~',$U);$cg=preg_match(number_type(),$U)||preg_match('~^(avg|ceil|char_length|count|count distinct|floor|len|length|round|sum|time_to_sec)$~',$Fe);echo"<td id='$t'".($cg&&($X===null||is_numeric(strip_tags($qf))||$U=="money")?" class='number'":"");if(($_GET["modify"]&&$nd&&$md!==null)||$Mj!==null){$Ue=h($Mj!==null?$Mj:$md);echo">".($Om?"<textarea name='$t' cols='30' rows='".(substr_count($md,"\n")+1)."'>\n$Ue</textarea>":"<input name='$t' value='$Ue' size='$Fg[$x]'>");}else{$Qg=strpos($qf,"<i>…</i>");$ld=adminer()->editVal($md,$n);echo($Hn?" data-text='".($Qg?2:($Om?1:0))."'".($nd?"":" data-warning='".'Use the edit link to modify this value.'."'").($nd&&!$Qg&&html_entity_decode(strip_tags($qf),ENT_QUOTES,"UTF-8")!==$ld?" data-value='".h($ld)."'":""):"").">$qf";}}}if($Sa)echo"<td>";adminer()->backwardKeysPrint($Sa,$K[$Hh]);echo"</tr>\n";}if(is_ajax())exit;echo"</table>\n","</div>\n";}if(!is_ajax()){$qa=get_settings("adminer_import");if($K||$D||$Ze){$Jd=true;if($_GET["page"]!="last"){if(!$z||(count($K)<$z&&($K||!$D)))$xe=($D?$D*$z:0)+count($K);elseif(JUSH!="sql"||!$bg){$xe=($bg?null:found_rows($S,$Z));$Jd=!driver()->hasEstimatedRows();if($xe===null||(!$Jd&&$xe<max(1e4,2*($D+1)*$z))){$xe=first(slow_query(count_rows($a,$Z,$bg,$Me)));$Jd=true;}}}if(!support("cursor"))$Ze=(($xe===false?count($K)+1:$xe-$D*$z)>$z);$dj=($z&&($Ze||$D));if($dj)echo($Ze?'<p><a href="'.h(pagination_href($D+1)).'" class="loadmore"'.on('click','selectLoadMore','Loading…').'>'.'Load more data'.'</a>':''),"\n";echo"<div class='footer'><div>\n";if($dj){$bh=($xe===false?$D+($K?(count($K)>=$z?2:1):0):floor(($xe-1)/$z));echo"<fieldset><legend>".'Page'."</legend>";if(!support("cursor")){echo
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
';$ve=adminer()->dumpFormat();foreach((array)$_GET["columns"]as$d){if($d["fun"]||($d["col"]!=""&&!isset($o[$d["col"]]))){unset($ve['sql']);break;}}if($ve){print_fieldset("export",'Export'." <span id='selected2'></span>");$Zi=adminer()->dumpOutput();echo($Zi?html_select("output",$Zi,$qa["output"])." ":""),html_select("format",$ve,$qa["format"])," <input type='submit' name='export' value='".'Export'."'>\n","</div></fieldset>\n";}adminer()->selectEmailPrint(array_filter($rd,'strlen'),$e);echo"</div></div>\n";}if(adminer()->selectImportPrint())echo"<p>","<a href='#import' class='toggle'>".'Import'."</a>","<span id='import'".($_POST["import"]?"":" class='hidden'").">: ",($Kn?input_hidden(ini_get("session.upload_progress.name"),$Kn):""),file_input(" name='csv_file'"," ".html_select("separator",["csv"=>"CSV,","csv;"=>"CSV;","tsv"=>"TSV"],$qa["format"])." <input type='submit' name='import' value='".'Import'."'>".($Kn?" <progress class='jsonly hidden' max='1' value='0'></progress>":"")),"</span>";echo
input_token(),"</form>\n",(!$Me&&$M?"":script("tableCheck();"));}}}if(is_ajax()){ob_end_clean();exit;}}elseif(isset($_GET["variables"])){$P=isset($_GET["status"]);page_header($P?'Status':'Variables');$co=($P?adminer()->showStatus():adminer()->showVariables());if(!$co)echo"<p class='message'>".'No rows.'."\n";else{echo"<table>\n";foreach($co
as$J){echo"<tr>";$x=array_shift($J);echo"<th><code class='jush-".JUSH.($P?"status":"set")."'>".h($x)."</code>";foreach($J
as$X)echo"<td>".nl_br(h($X));}echo"</table>\n";}}elseif(isset($_GET["script"])){header("Content-Type: application/json; charset=utf-8");if($_GET["script"]=="db"){$lm=["Data_length"=>0,"Index_length"=>0,"Data_free"=>0];foreach(table_status()as$B=>$S){json_row("Comment-$B",h($S["Comment"]).($S["Error"]?" <span class='error'>".h($S["Error"])."</span>":""));if(!is_view($S)||preg_match('~materialized~i',$S["Engine"])){foreach(["Engine","Collation"]as$x)json_row("$x-$B",h($S[$x]));foreach(array_keys($lm+["Auto_increment"=>0,"Rows"=>0])as$x){if(array_key_exists($x,$S))json_row("$x-$B",format_status($S,$x));if($S[$x]!=""&&isset($lm[$x]))$lm[$x]+=($S["Engine"]!="InnoDB"||$x!="Data_free"?$S[$x]:0);}}}if(function_exists('Adminer\db_status'))$lm=db_status();foreach($lm
as$x=>$X)json_row("sum-$x",adminer()->formatSizeValue($X));json_row("");}else{foreach(count_tables(adminer()->databases(false))as$k=>$X){json_row("tables-$k",format_number($X));json_row("size-$k",db_size($k));}json_row("");}exit;}else{if(!isset($_GET["select"])&&support("single_table")){$T=tables_list();if($T)redirect(ME.(support("table")?"table=":"select=").url_escape(key($T)));}$ih=ME.(isset($_GET["select"])?"select=&":"");$Gm=array_merge((array)$_POST["tables"],(array)$_POST["views"]);if($Gm&&!$m&&!$_POST["search"]){$H=true;$mh="";if(JUSH=="sql"&&$_POST["tables"]&&count($_POST["tables"])>1&&($_POST["drop"]||$_POST["truncate"]||$_POST["copy"]))queries("SET foreign_key_checks = 0");if($_POST["truncate"]){if($_POST["tables"])$H=truncate_tables($_POST["tables"]);$mh='Tables have been truncated.';}elseif($_POST["move"]){$H=move_tables((array)$_POST["tables"],(array)$_POST["views"],$_POST["target"]);$mh='Tables have been moved.';}elseif($_POST["copy"]){$H=copy_tables((array)$_POST["tables"],(array)$_POST["views"],$_POST["target"]);$mh='Tables have been copied.';}elseif($_POST["drop"]){if($_POST["views"])$H=drop_views($_POST["views"]);if($H&&$_POST["tables"])$H=drop_tables($_POST["tables"]);$mh='Tables have been dropped.';}elseif(JUSH=="sqlite"&&$_POST["check"]){foreach((array)$_POST["tables"]as$R){foreach(get_rows("PRAGMA integrity_check(".q($R).")")as$J)$mh
.="<b>".h($R)."</b>: ".h($J["integrity_check"])."<br>";}}elseif(JUSH=="mssql"&&$_POST["check"]){foreach((array)$_POST["tables"]as$R){foreach(get_rows("DBCC CHECKTABLE (".q(table($R)).") WITH TABLERESULTS")as$J)$mh
.="<b>".h($R)."</b>: ".h($J["MessageText"])."<br>";}}elseif(JUSH!="sql"){$H=(JUSH=="sqlite"?queries("VACUUM"):apply_queries("VACUUM".($_POST["optimize"]?" ANALYZE":""),(array)$_POST["tables"]));$mh='Tables have been optimized.';}elseif(!$_POST["tables"])$mh='No tables.';elseif($H=queries(($_POST["optimize"]?"OPTIMIZE":($_POST["check"]?"CHECK":($_POST["repair"]?"REPAIR":"ANALYZE")))." TABLE ".implode(", ",array_map('Adminer\idf_escape',$_POST["tables"])))){while($J=$H->fetch_assoc())$mh
.="<b>".h($J["Table"])."</b>: ".h($J["Msg_text"])."<br>";}queries_redirect(relative_uri(),$mh,$H);}page_header(($_GET["ns"]==""?'Database'.": ".h(DB):'Schema'.": ".h($_GET["ns"])),$m,true);if(adminer()->homepage()){if($_GET["ns"]!==""){if(support("scheme")&&support("comment")){$f=schema_comment($_GET["ns"]);if($f!="")echo"<p class='nowrap'>".'Comment'.": ".adminer()->commentValue('SCHEMA',$f)."\n";}$Ki=$_GET["order"];$Ce=($Ki||support("fast_status"));echo"<div>\n","<h3 id='tables-views'>".'Tables and views'."</h3>\n";$Fm=($Ce?table_status():tables_list());$ie=$_GET["filter"];if($ie!=""){foreach($Fm
as$R=>$P){if(stripos("$R",$ie)===false)unset($Fm[$R]);}}if(!$Fm)echo"<p class='message'>".'No tables.'."\n";else{echo"<form method='post'>\n";if(support("table")){echo"<fieldset><legend>".'Search data in tables'." <span id='selected2'></span></legend><div>",html_select("op",adminer()->operators(),idx($_POST,"op",JUSH=="elastic"?"should":"LIKE %%"));$G=($_POST?$_POST["query"]:$_GET["query"]);echo" <input type='search' name='query' value='".h($G!=""?$G:$ie)."'".on('keydown','submitKeydown','search').">"," <input type='submit' name='search' value='".'Search'."'>\n","</div></fieldset>\n";if(!$m&&(!$_POST||$_POST["search"])&&$G!=""){$_GET["where"][0]["op"]=idx($_POST,"op",JUSH=="elastic"?"should":"LIKE %%");search_tables($G);}}echo"<div class='scrollable'>\n","<table class='nowrap checkable odds'".on('click','tableClick').on('dblclick','tableClick').">\n",'<thead><tr>','<td class="hover"><input id="check-all" type="checkbox" class="jsonly" title="'.'All'.'"'.on('click','formCheck','^(tables|views)\[').'>','<th class="sticky"'.(!$Ki&&JUSH!='sqlite'?" aria-sort='ascending'":'').'><a href="'.h(substr($ih,0,-1)).'">'.'Table'.'</a>';$e=["Engine"=>['Engine'.doc_link(['sql'=>'storage-engines.html'])]];$Gb=collations();if($Gb)$e["Collation"]=['Collation'.doc_link(['sql'=>'charset-charsets.html','mariadb'=>'supported-character-sets-and-collations/'])];if(function_exists('Adminer\alter_table'))$e["Data_length"]=['Data Length'.doc_link(['sql'=>'show-table-status.html','pgsql'=>'functions-admin.html#FUNCTIONS-ADMIN-DBOBJECT','oracle'=>'refrn/ALL_TABLES.html']),"create",'Alter table',];if(support("indexes"))$e["Index_length"]=['Index Length'.doc_link(['sql'=>'show-table-status.html','pgsql'=>'functions-admin.html#FUNCTIONS-ADMIN-DBOBJECT']),"indexes",'Alter indexes',];$e["Data_free"]=['Data Free'.doc_link(['sql'=>'show-table-status.html']),"edit",'New item'];if(function_exists('Adminer\alter_table'))$e["Auto_increment"]=['Auto Increment'.doc_link(['sql'=>'example-auto-increment.html','mariadb'=>'auto_increment/']),"auto_increment=1&create",'Alter table',];$e["Rows"]=['Rows'.doc_link(['sql'=>'show-table-status.html','pgsql'=>'catalog-pg-class.html#CATALOG-PG-CLASS','oracle'=>'refrn/ALL_TABLES.html']),"select",'Select data',];if(support("comment"))$e["Comment"]=['Comment'.doc_link(['sql'=>'show-table-status.html','pgsql'=>'functions-info.html#FUNCTIONS-INFO-COMMENT-TABLE','cockroach'=>'comment-on']),];$Ga=['Engine','Collation','Comment'];foreach($e
as$x=>$d)echo"<th".($Ki==$x?" aria-sort='".(in_array($x,$Ga)?"ascending":"descending")."'":"")."><a href='".h($ih)."order=$x'>$d[0]</a>";echo"<tbody>\n";if($Ki){uasort($Fm,function($ha,$Pa)use($Ki,$Ga){$I=($ha[$Ki]<$Pa[$Ki]?-1:($ha[$Ki]>$Pa[$Ki]?1:0));return(in_array($Ki,$Ga)?$I:-$I);});}$T=0;$lm=["Data_length"=>0,"Index_length"=>0,"Data_free"=>0];foreach($Fm
as$B=>$P){$fo=($Ce?is_view($P):$P!==null&&!preg_match('~table|sequence~i',$P));$P=($Ce?$P:['Engine'=>$P]);$t=h("Table-".$B);echo'<tr><td class="hover">'.checkbox(($fo?"views[]":"tables[]"),$B,in_array("$B",$Gm,true),"","","",$t),'<th class="sticky">'.(support("table")||support("indexes")?"<a href='".h(ME)."table=".url_escape($B)."' title='".'Show structure'."' id='$t'>".h($B).'</a>':h($B));if($fo&&!preg_match('~materialized~i',$P['Engine'])){$Um='View';echo'<td colspan="'.(count($e)-(support("comment")?2:1)).'">'.(support("view")?"<a href='".h(ME)."view=".url_escape($B)."' title='".'Alter view'."'>$Um</a>":$Um),"<td align='right'><a href='".h(ME)."select=".url_escape($B)."' title='".'Select data'."'>?</a>";if(support("comment"))echo'<td>'.h($P['Comment']);}else{if($Ce){foreach(array_keys($lm)as$x)$lm[$x]+=($P["Engine"]!="InnoDB"||$x!="Data_free"?idx($P,$x):0);}foreach($e
as$x=>$d){$t=" id='$x-".h($B)."'";echo($d[1]?"<td align='right'>".($x=="Index_length"&&$P["invalid_index"]?"<span class='error'>INVALID</span> ":"")."<a href='".h(ME."$d[1]=").url_escape($B)."'$t title='$d[2]'>".format_status($P,$x)."</a>":"<td$t>".h(idx($P,$x,'?')).($x=="Comment"&&$P["Error"]?" <span class='error'>".h($P["Error"])."</span>":""));}$T++;}echo"\n";}echo"<tr><td class='hover'><th class='sticky'>".sprintf('%d in total',count($Fm)),"<td>".h(JUSH=="sql"?get_val("SELECT @@default_storage_engine"):""),($Gb?"<td>".h(db_collation(DB,$Gb)):'');if($Ce&&function_exists('Adminer\db_status'))$lm=db_status();foreach($lm
as$x=>$km)echo($e[$x]?"<td align='right' id='sum-$x'>".($Ce?adminer()->formatSizeValue($km):""):"");echo"\n","</table>\n",($Ce?'':script("ajaxSetHtml('".js_escape(ME)."script=db');")),"</div>\n";if(!information_schema(DB)){$Xn="<input type='submit' value='".'Vacuum'."'".on_help("VACUUM")."> ";$Ei="<input type='submit' name='optimize' value='".'Optimize'."'".on_help(JUSH=="sql"?"OPTIMIZE TABLE":"VACUUM ANALYZE")."> ";$Wj=(JUSH=="sqlite"?$Xn."<input type='submit' name='check' value='".'Check'."'".on_help("PRAGMA integrity_check")."> ":(JUSH=="pgsql"?$Xn.$Ei:(JUSH=="mssql"?"<input type='submit' name='check' value='".'Check'."'".on_help("DBCC CHECKTABLE")."> ":(JUSH=="sql"?"<input type='submit' value='".'Analyze'."'".on_help("ANALYZE TABLE")."> ".$Ei."<input type='submit' name='check' value='".'Check'."'".on_help("CHECK TABLE")."> "."<input type='submit' name='repair' value='".'Repair'."'".on_help("REPAIR TABLE")."> ":"")))).(function_exists('Adminer\truncate_tables')?"<input type='submit' name='truncate' value='".'Truncate'."'".confirm().on_help(JUSH=="sqlite"?"DELETE":"TRUNCATE".(JUSH=="pgsql"?"":" TABLE"))."> ":"").(function_exists('Adminer\drop_tables')?"<input type='submit' name='drop' value='".'Drop'."'".confirm().on_help("DROP TABLE").">":"");echo($Wj?"<div class='footer'><div>\n<fieldset><legend>".'Selected'." <span id='selected'></span></legend><div>$Wj\n</div></fieldset>\n":"");$j=(support("scheme")?adminer()->schemas():adminer()->databases());if(count($j)!=1&&function_exists('Adminer\move_tables')){echo"<fieldset><legend>".'Move to another database'." <span id='selected3'></span></legend><div>";$k=(isset($_POST["target"])?$_POST["target"]:(support("scheme")?$_GET["ns"]:DB));echo($j?html_select("target",$j,$k):'<input name="target" value="'.h($k).'" autocapitalize="off">'),"</label> <input type='submit' name='move' value='".'Move'."'>",(support("copy")?" <input type='submit' name='copy' value='".'Copy'."'> ".checkbox("overwrite",1,$_POST["overwrite"],'overwrite'):""),"</div></fieldset>\n";}echo"<input type='hidden' name='all' value=''".on('click','countTables',$T).">\n",input_token(),"</div></div>\n";}echo"</form>\n",script("tableCheck();");}echo(function_exists('Adminer\alter_table')?"<p class='links hover'><a href='".h(ME)."create='>".'Create table'."</a>\n":''),(support("view")?"<a href='".h(ME)."view='>".'Create view'."</a>\n":""),"</div>\n";if(support("routine")){echo"<div>\n","<h3 id='routines'>".'Routines'."</h3>\n";$Tk=routines();if($Tk){echo"<table class='odds'>\n",'<thead><tr><th>'.'Name'.'<th>'.'Type'.'<th>'.'Return type'."<td class='hover'><tbody>\n";foreach($Tk
as$J){$B=($J["SPECIFIC_NAME"]==$J["ROUTINE_NAME"]?"":"&name=".url_escape($J["ROUTINE_NAME"]));echo'<tr>','<th><a href="'.h(ME.($J["ROUTINE_TYPE"]!="PROCEDURE"?'callf=':'call=').url_escape($J["SPECIFIC_NAME"]).$B).'"'.' title="'.($J["DTD_IDENTIFIER"]=="trigger"?'Triggers':'Call').'">'.h($J["ROUTINE_NAME"]).'</a>','<td>'.h($J["ROUTINE_TYPE"]),'<td>'.h($J["DTD_IDENTIFIER"]),'<td class="hover"><a href="'.h(ME.($J["ROUTINE_TYPE"]!="PROCEDURE"?'function=':'procedure=').url_escape($J["SPECIFIC_NAME"]).$B).'">'.'Alter'."</a>";}echo"</table>\n";}echo'<p class="links hover">'.(support("procedure")?'<a href="'.h(ME).'procedure=">'.'Create procedure'.'</a>':'').'<a href="'.h(ME).'function=">'.'Create function'."</a>\n","</div>\n";}if(support("sequence")){echo"<div>\n","<h3 id='sequences'>".'Sequences'."</h3>\n";$sl=get_vals("SELECT relname FROM pg_class WHERE relkind = 'S' AND relnamespace = ".driver()->nsOid." ORDER BY relname");if($sl){echo"<table class='odds'>\n","<thead><tr><th>".'Name'."<tbody>\n";foreach($sl
as$X)echo"<tr><th><a href='".h(ME)."sequence=".url_escape($X)."'>".h($X)."</a>\n";echo"</table>\n";}echo"<p class='links hover'><a href='".h(ME)."sequence='>".'Create sequence'."</a>\n","</div>\n";}if(support("type")){echo"<div>\n","<h3 id='user-types'>".'User types'."</h3>\n";$Un=types();if($Un){echo"<table class='odds'>\n","<thead><tr><th>".'Name'."<tbody>\n";foreach($Un
as$X)echo"<tr><th><a href='".h(ME)."type=".url_escape($X)."'>".h($X)."</a>\n";echo"</table>\n";}echo"<p class='links hover'><a href='".h(ME)."type='>".'Create type'."</a>\n","</div>\n";}if(support("event")){echo"<div>\n","<h3 id='events'>".'Events'."</h3>\n";$K=get_rows("SHOW EVENTS");if($K){echo"<table>\n","<thead><tr><th>".'Name'."<th>".'Schedule'."<th>".'Start'."<th>".'End'."<td class='hover'><tbody>\n";foreach($K
as$J)echo"<tr>","<th>".h($J["Name"]),"<td>".($J["Execute at"]?'At given time'."<td>".h($J["Execute at"]):'Every'." ".h($J["Interval value"])." ".h($J["Interval field"])."<td>".h($J["Starts"])),"<td>".h($J["Ends"]),'<td class="hover"><a href="'.h(ME).'event='.url_escape($J["Name"]).'">'.'Alter'.'</a>';echo"</table>\n";$Gd=get_val("SELECT @@event_scheduler");if($Gd&&$Gd!="ON")echo"<p class='error'><code class='jush-sqlset'>event_scheduler</code>: ".h($Gd)."\n";}echo'<p class="links hover"><a href="'.h(ME).'event=">'.'Create event'."</a>\n","</div>\n";}}elseif(support("extension")){$Sd=get_rows("SELECT e.extname, e.extversion, n.nspname, obj_description(e.oid, 'pg_extension') AS comment
FROM pg_extension e
JOIN pg_namespace n ON n.oid = e.extnamespace
ORDER BY e.extname");if($Sd){echo"<div>\n","<h3 id='extensions'>".'Extensions'."</h3>\n","<table class='odds'>\n","<thead><tr><th>".'Name'."<th>".'Version'."<th>".'Schema'."<th>".'Comment'."<tbody>\n";foreach($Sd
as$J)echo"<tr><th><code class='jush-pgsqlext'>".h($J["extname"])."</code>","<td>".h($J["extversion"]),"<td><a href='".h(substr(ME,0,-1).url_escape($J["nspname"]))."'>".h($J["nspname"])."</a>","<td>".h($J["comment"]),"\n";echo"</table>\n","</div>\n";}}}}page_footer();