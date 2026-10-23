<?php
/** Adminer - Compact database management
* @link https://www.adminer.org/
* @author Jakub Vrana, https://www.vrana.cz/
* @copyright 2007 Jakub Vrana
* @license https://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
* @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License, version 2 (one or other)
* @version 6.1.2
*/namespace
Adminer;if(isset($_GET["status"]))$_GET["variables"]=$_GET["status"];if(isset($_GET["import"]))$_GET["sql"]=$_GET["import"];const
VERSION="6.1.2";error_reporting(24575);set_error_handler(function($sd,$ud){return!!preg_match('~^Undefined (array key|offset|index)~',$ud);},E_WARNING|E_NOTICE);$Xd=!preg_match('~^(unsafe_raw)?$~',ini_get("filter.default"));if($Xd||ini_get("filter.default_flags")){foreach(array('_GET','_POST','_COOKIE','_SERVER')as$W){$hn=filter_input_array(constant("INPUT$W"),FILTER_UNSAFE_RAW);if($hn)$$W=$hn;}}$_COOKIE=array_filter($_COOKIE,'is_scalar');if(function_exists("mb_internal_encoding"))mb_internal_encoding("8bit");function
connection($h=null){return($h?:Db::$instance);}function
adminer(){return
Adminer::$instance;}function
driver(){return
Driver::$instance;}function
connect(){$fc=adminer()->credentials();$H=Driver::connect($fc[0],$fc[1],$fc[2]);return(is_object($H)?$H:null);}function
idf_unescape($u){if(!preg_match('~^[`\'"[]~',$u))return$u;$mg=substr($u,-1);return
str_replace($mg.$mg,$mg,substr($u,1,-1));}function
q($P){return
connection()->quote($P);}function
idx($Ea,$x,$l=null){return($Ea&&array_key_exists($x,$Ea)?$Ea[$x]:$l);}function
number($W){return
preg_replace('~[^0-9]+~','',$W);}function
int_type(){return'(tiny|small|medium|big)?int(eger|\d)?';}function
number_type(){return'(^('.int_type().'|decimal|numeric|number|real|(binary_|half_|scaled_)?float\d?|(binary_)?double( precision)?|(small)?money)$)';}function
text_type(){return'char|text'.(JUSH=="sql"?'|enum|set':'');}function
is_user_type($T){return
in_array($T,idx(driver()->structuredTypes(),'User types',array()));}function
full_type_sql(array$n){$T=$n["type"];return(is_user_type($T)?idf_escape($T).substr($n["full_type"],strlen($T)):$n["full_type"]);}function
is_searchable(array$n,array$W){if(!isset($n["privileges"]["where"]))return
false;if(preg_match('~NULL$~',$W["op"]))return
true;$T=$n["type"];$Jk=$W["val"];$Xa='binary$|bytea|raw|image|bfile|^vector$'.(JUSH=="mssql"?'|^timestamp$':'|^bit').(JUSH=="oracle"?'|^blob|^long|rowid':'');if(preg_match("~$Xa~",$T))return
false;if(preg_match(number_type(),$T)){$Uh='-?\d+(\.\d+)?';$qh='';if(preg_match('~IN$~',$W["op"])){$Jk=preg_replace('~^\s*\(\s*(.*?)\s*\)\s*$~s','\1',$Jk);$qh="( *, *$Uh)*";}elseif($W["op"]=="BETWEEN")$qh="\\s+AND\\s+$Uh";return(bool)preg_match('~^'.$Uh.$qh.'$~i',$Jk);}if(preg_match('~^(small)?date|^timestamp~',$T))return(bool)preg_match('~^\d+-\d+-\d+~',$Jk);if(preg_match('~^time~',$T))return(bool)preg_match('~^\d+:\d+~',$Jk);if(preg_match('~^bool~',$T)||(JUSH=="mssql"&&$T=="bit"))return(bool)preg_match('~^(t|f|true|false|[01])$~i',$Jk);return
true;}function
remove_slashes(array$Y,$Xd=false){$H=array();foreach($Y
as$x=>$W)$H[stripslashes($x)]=(is_array($W)?remove_slashes($W,$Xd):($Xd?$W:stripslashes($W)));return$H;}function
bracket_escape($u,$Qa=false){static$Im=array(':'=>':1',']'=>':2','['=>':3','"'=>':4','='=>':5');return
strtr($u,($Qa?array_flip($Im):$Im));}function
url_escape($P){static$Im=array();if(!$Im){$Im=array(' '=>'+');foreach(str_split("\"'<>#%&+=?".ini_get("arg_separator.input"))as$lb)$Im[$lb]=sprintf('%%%02X',ord($lb));for($s=0;$s<256;$s++){if($s<32||$s>126)$Im[chr($s)]=sprintf('%%%02X',$s);}}return
strtr((string)$P,$Im);}function
min_version($Gn,$Hg="",$h=null){$h=connection($h);$gl=$h->server_info;if($Hg&&preg_match('~([\d.]+)-MariaDB~',$gl,$A)){$gl=$A[1];$Gn=$Hg;}return$Gn&&version_compare($gl,$Gn)>=0;}function
charset(Db$g){return(min_version("5.5.3",0,$g)?"utf8mb4":"utf8");}function
ini_set($ui,$X){return(function_exists('ini_set')?\ini_set($ui,$X):false);}function
ini_bool($Cf){$W=ini_get($Cf);return(preg_match('~^(on|true|yes)$~i',$W)||(int)$W);}function
ini_bytes($Cf){$W=ini_get($Cf);switch(strtolower(substr($W,-1))){case'g':$W=(int)$W*1024;case'm':$W=(int)$W*1024;case'k':$W=(int)$W*1024;}return$W;}function
max_input_vars($I,$Ki){$Lg=(int)ini_get("max_input_vars");return($Lg?(int)floor(($Lg-$Ki)/$I):0);}function
max_input_vars_error(){$Cf="max_input_vars";return
sprintf('Maximum number of allowed fields exceeded. Please increase %s.',"<b>$Cf = ".ini_get($Cf)."</b>");}function
sid(){static$H;if($H===null)$H=(SID&&!($_COOKIE&&ini_bool("session.use_cookies")));return$H;}function
set_password($Fn,$M,$U,$D){$_SESSION["pwds"][$Fn][$M][$U]=($_COOKIE["adminer_key"]&&is_string($D)?array(encrypt_string($D,$_COOKIE["adminer_key"])):$D);}function
get_password(){$H=get_session("pwds");if(is_array($H))$H=($_COOKIE["adminer_key"]?decrypt_string($H[0],$_COOKIE["adminer_key"]):false);return$H;}function
get_val($F,$n=0,$Nb=null){$Nb=connection($Nb);$G=$Nb->query($F);if(!is_object($G))return
false;$I=$G->fetch_row();return($I?$I[$n]:false);}function
get_vals($F,$d=0){$H=array();$G=connection()->query($F);if(is_object($G)){while($I=$G->fetch_row())$H[]=$I[$d];}return$H;}function
get_key_vals($F,$h=null,$jl=true){$h=connection($h);$H=array();$G=$h->query($F);if(is_object($G)){while($I=$G->fetch_row()){if($jl)$H[$I[0]]=$I[1];else$H[]=$I[0];}}return$H;}function
get_rows($F,$h=null,$m="<p class='error'>"){$Nb=connection($h);$H=array();$G=$Nb->query($F);if(is_object($G)){while($I=$G->fetch_assoc())$H[]=$I;}elseif(!$G&&!$h&&$m&&(defined('Adminer\PAGE_HEADER')||$m=="-- "))echo$m.adminer()->error()."\n";return$H;}function
unique_array($I,array$w){foreach($w
as$v){if(preg_match("~^(PRIMARY|UNIQUE)$~",$v["type"])&&!$v["partial"]){$H=array();foreach($v["columns"]as$x){if(!isset($I[$x]))continue
2;$H[$x]=$I[$x];}return$H;}}}function
where_function($ve,$d,array$n){if($ve=="md5")return
driver()->md5($d,$n)?:$d;return(in_array($ve,driver()->functions)||in_array($ve,driver()->grouping)?apply_sql_function($ve,$d):$d);}function
where(array$Z,array$o=array()){$H=array();foreach((array)$Z["where"]as$x=>$W){$x=bracket_escape($x,true);$d=idf_escape($x);$n=idx($o,$x,array());$Rd=$n["type"];$Pf=$n&&(is_blob($n)||preg_match('~binary~',$Rd));$H[]=$d.($Pf&&!is_utf8($W)?" = ".driver()->quoteBinary($W):(JUSH=="sql"&&$Rd=="json"?" = CAST(".q($W)." AS JSON)":(JUSH=="pgsql"&&preg_match('~^jsonb?$~',$n["full_type"])?"::jsonb = ".q($W)."::jsonb":(JUSH=="sql"&&is_numeric($W)&&preg_match('~\.~',$W)?" LIKE ".q($W):(JUSH=="mssql"&&strpos($Rd,"datetime")===false?" LIKE ".q(preg_replace('~[_%[]~','[\0]',$W)):" = ".unconvert_field($n,q($W)))))));if(JUSH=="sql"&&preg_match('~char|text~',$Rd)&&preg_match("~[^ -@]~",$W))$H[]="$d = ".q($W)." COLLATE ".charset(connection())."_bin";}foreach((array)$Z["null"]as$x)$H[]=idf_escape($x)." IS NULL";foreach((array)$Z["col"]as$s=>$_b){$W=idx($Z["val"],$s);$H[]=where_function(idx($Z["fun"],$s),idf_escape($_b),idx($o,$_b,array())).($W!==null?" = ".q($W):" IS NULL");}return
implode(" AND ",$H);}function
where_columns(array$o){$H=array();foreach((array)$_GET["null"]as$x)$H[$x]=true;foreach(array_keys((array)$_GET["where"])as$x)$H[bracket_escape($x,true)]=true;foreach((array)$_GET["col"]as$_b)$H[$_b]=true;return
array_intersect_key($H,$o);}function
where_check($W,array$o=array()){parse_str($W,$ob);remove_slashes(array(&$ob));return
where($ob,$o);}function
where_link($s,$d,$X,$ri="="){$oi=($X!==null?$ri:"IS NULL");return"&where[$s][col]=".url_escape($d).($oi!=first(adminer()->operators())?"&where[$s][op]=".url_escape($oi):"")."&where[$s][val]=".url_escape($X);}function
convert_fields(array$e,array$o,array$L=array()){$H="";foreach($e
as$x=>$W){if($L&&!in_array(idf_escape($x),$L))continue;$Fa=convert_field($o[$x]);if($Fa)$H
.=", $Fa AS ".idf_escape($x);}return$H;}function
cookie_path(){return
strtr(preg_replace('~\?.*~','',$_SERVER["REQUEST_URI"]),array(";"=>"%3B",","=>"%2C"));}function
cookie($B,$X,$wg=2592000){header("Set-Cookie: $B=".rawurlencode($X).($wg?"; expires=".gmdate("D, d M Y H:i:s",time()+$wg)." GMT":"")."; path=".cookie_path().(HTTPS?"; secure":"").($B=="adminer_import"?"":"; HttpOnly")."; SameSite=lax",false);}function
get_url($qn,$Wb){$http_response_header=null;$td=array();set_error_handler(function($sd,$m)use(&$td){$td[]=preg_replace('~^file_get_contents\([^)]*\):\s*~','',$m);return
true;});$H=file_get_contents($qn,false,$Wb);restore_error_handler();$Te=(function_exists('http_get_last_response_headers')?http_get_last_response_headers():$http_response_header);return
array($H,(preg_match('~^HTTP/[\d.]+ (\d+)~',idx($Te,0,''),$A)?$A[1]:''),(array)$Te,($H===false?implode("\n",$td):''),);}function
json_decode_exact($Yf){$Yf=preg_replace('~"(\\\\u0001(?:[^"\\\\]|\\\\.)*+")|"(?:[^"\\\\]|\\\\.)*+"(*SKIP)(*FAIL)~','"\\\\u0001$1',$Yf);return
json_decode(preg_replace('~"(?:[^"\\\\]|\\\\.)*+"(*SKIP)(*FAIL)|-?\d[-+.\deE]*+~','"\\\\u0001$0"',$Yf));}function
json_scalar($W){return(is_string($W)&&substr($W,0,1)=="\1"?substr($W,1):$W);}function
json_encode_exact($W,$de=0){return
preg_replace('~"\\\\u0001(-?\d[^"\\\\]*)"|(")\\\\u0001(\\\\u0001(?:[^"\\\\]|\\\\.)*+")|"(?:[^"\\\\]|\\\\.)*+"(*SKIP)(*FAIL)~','$1$2$3',json_encode($W,$de));}function
get_settings($ac){parse_str($_COOKIE[$ac],$kl);return$kl;}function
get_setting($x,$ac="adminer_settings",$l=null){return
idx(get_settings($ac),$x,$l);}function
save_settings(array$kl,$ac="adminer_settings"){$X=http_build_query($kl+get_settings($ac));cookie($ac,$X);$_COOKIE[$ac]=$X;}function
restart_session(){if(!ini_bool("session.use_cookies")&&(!function_exists('session_status')||session_status()==PHP_SESSION_NONE))session_start();}function
stop_session($ge=false){$tn=ini_bool("session.use_cookies");if(!$tn||$ge){session_write_close();if($tn&&ini_set("session.use_cookies",'0')===false)session_start();}}function&get_session($x){return$_SESSION[$x][DRIVER][SERVER][$_GET["username"]];}function
set_session($x,$W){$_SESSION[$x][DRIVER][SERVER][$_GET["username"]]=$W;}function
auth_url($Fn,$M,$U,$k=null){$pn=remove_from_uri(implode("|",array_keys(SqlDriver::$drivers))."|username|ext|".($k!==null?"db|":"").($Fn=='mssql'||$Fn=='pgsql'?"":"ns|").session_name());preg_match('~([^?]*)\??(.*)~',$pn,$A);return"$A[1]?".(sid()?SID."&":"").($_GET["ext"]?"ext=".url_escape($_GET["ext"])."&":"").($Fn!="server"||$M!=""?url_escape($Fn)."=".url_escape($M)."&":"")."username=".url_escape($U).($k!=""?"&db=".url_escape($k):"").($A[2]?"&$A[2]":"");}function
is_ajax(){return($_SERVER["HTTP_X_REQUESTED_WITH"]=="XMLHttpRequest");}function
redirect($Dg,$bh=null){if($bh!==null){restart_session();$_SESSION["messages"][preg_replace('~^[^?]*~','',($Dg!==null?$Dg:$_SERVER["REQUEST_URI"]))][]=$bh;}if($Dg!==null){if($Dg=="")$Dg=".";header("Location: $Dg");exit;}}function
query_redirect($F,$Dg,$bh,$Yj=true,$Ad=true,$Ld=false,$wm=""){if($Ad){$Fl=microtime(true);$Ld=!connection()->query($F);$wm=format_time($Fl);}$zl=($F?adminer()->messageQuery($F,$wm,$Ld):"");if($Ld){adminer()->error
.=adminer()->error().$zl.script("messagesPrint();")."<br>";return
false;}if($Yj)redirect($Dg,$bh.$zl);return
true;}class
Queries{static$queries=array();static$start=0;}function
remember_query($F){if(!Queries::$start)Queries::$start=microtime(true);Queries::$queries[]=(driver()->delimiter!=';'?$F:(preg_match('~;$~',$F)?"DELIMITER ;;\n$F;\nDELIMITER ":$F).";");}function
queries($F){remember_query($F);return
connection()->query($F);}function
apply_queries($F,array$S,$vd='Adminer\table'){foreach($S
as$Q){if(!queries("$F ".$vd($Q)))return
false;}return
true;}function
queries_redirect($Dg,$bh,$Yj){$Rj=implode("\n",Queries::$queries);$wm=format_time(Queries::$start);return
query_redirect($Rj,$Dg,$bh,$Yj,false,!$Yj,$wm);}function
format_time($Fl){return
sprintf('%.3f s',max(0,microtime(true)-$Fl));}function
relative_uri($pn=''){return
preg_replace_callback('~^[^?]*~',function($A){return
str_replace(":","%3A",$A[0]);},preg_replace('~^[^?]*/([^?]*)~','\1',($pn?:$_SERVER["REQUEST_URI"])));}function
remove_from_uri($Ri=""){return
substr(preg_replace("~(?<=[?&])($Ri".(SID?"":"|".session_name()).")=[^&]*&~",'',relative_uri()."&"),0,-1);}function
get_files($B,$uc=false){$Td=$_FILES[$B];if(!$Td)return
null;foreach($Td
as$x=>$W)$Td[$x]=(array)$W;$H=array();foreach($Td["error"]as$x=>$m){if($m)return$m;$p=$Td["name"][$x];$Dm=$Td["tmp_name"][$x];$Ub=file_get_contents($uc&&preg_match('~\.gz$~',$p)?"compress.zlib://$Dm":$Dm);if($uc){$Fl=substr($Ub,0,3);if(function_exists("iconv")&&preg_match("~^\xFE\xFF|^\xFF\xFE~",$Fl))$Ub=iconv("utf-16","utf-8",$Ub);elseif($Fl=="\xEF\xBB\xBF")$Ub=substr($Ub,3);}$H[]=array($p,$Ub);}return$H;}function
get_file($x,$uc=false,$Ac=""){$Wd=get_files($x,$uc);if(!is_array($Wd))return$Wd;$H='';foreach($Wd
as$Td){$Ub=$Td[1];$H
.=$Ub;if($Ac)$H
.=(preg_match("($Ac\\s*\$)",$Ub)?"":$Ac)."\n\n";}return$H;}function
upload_error($m){$Tg=($m==UPLOAD_ERR_INI_SIZE?ini_get("upload_max_filesize"):0);return($m?'Unable to upload a file.'.($Tg?" ".sprintf('Maximum allowed file size is %sB.',$Tg):""):'File does not exist.');}function
is_utf8($W){return(preg_match('~~u',$W)&&!preg_match('~[\0-\x8\xB\xC\xE-\x1F]~',$W));}function
utf8_length($W){return
strlen(preg_replace('~[\x80-\xBF]~','',$W));}function
format_number($W){preg_match('~^#+([^#0]+)(?:(#+)\1)?(#*0)$~u','#,##0',$A);$ol=strlen($A[3]);$H=number_format($W,0,".","");$H=preg_replace('~\B(?=(\d{'.(strlen($A[2])?:$ol).'})*\d{'.$ol.'}$)~',$A[1],$H);return
strtr($H,preg_split('~~u','0123456789',-1,PREG_SPLIT_NO_EMPTY));}function
format_status(array$R,$x){$W=idx($R,$x,'?');if(!is_numeric($W))return
h($W);if($W<0)return'?';$Aa=($x=="Rows"&&(JUSH=="sqlite"||$R["Engine"]==(JUSH=="pgsql"?"table":"InnoDB")));$W=in_array($x,array("Data_length","Index_length","Data_free"))?adminer()->formatSizeValue($W):format_number($W);return($Aa?"~ ":"").$W;}function
friendly_url($W){return
preg_replace('~\W~i','-',$W);}function
table_status1($Q,$Md=false){$H=table_status($Q,$Md);return($H?reset($H):array("Name"=>$Q));}function
column_foreign_keys($Q){$H=array();foreach(adminer()->foreignKeys($Q)as$q){foreach($q["source"]as$W)$H[$W][]=$q;}return$H;}function
fields_from_edit(){$H=array();foreach((array)$_POST["field_keys"]as$x=>$W){if($W!=""){$W=bracket_escape($W);$_POST["function"][$W]=$_POST["field_funs"][$x];$_POST["fields"][$W]=$_POST["field_vals"][$x];}}foreach((array)$_POST["fields"]as$x=>$W){$B=bracket_escape($x,true);$H[$B]=array("field"=>$B,"full_type"=>"","type"=>"","privileges"=>array("insert"=>1,"update"=>1,"where"=>1,"order"=>1),"null"=>true,"auto_increment"=>($B==driver()->primary),);}return$H;}function
dump_headers($hf,$uh=false){$H=adminer()->dumpHeaders($hf,$uh);$Mi=$_POST["output"];if($Mi!="text"||$H=="tar"){$Kb=($Mi!="text"&&$Mi!="file"&&preg_match('~^[0-9a-z]+$~',$Mi)?".$Mi":"");header("Content-Disposition: attachment; filename=".adminer()->dumpFilename($hf).".$H$Kb");}session_write_close();if(!ob_get_level())ob_start(null,4096);ob_flush();flush();return$H;}function
dump_csv(array$I){$Um=$_POST["format"]=="tsv";foreach($I
as$x=>$W){if(preg_match('~["\n]|^0[^.]|\.\d*0$|'.($Um?'\t':'[,;]|^$').'~',$W))$I[$x]='"'.str_replace('"','""',$W).'"';}echo
implode(($_POST["format"]=="csv"?",":($Um?"\t":";")),$I)."\r\n";}function
parse_csv($ic,$Uk){$H=array();preg_match_all('~(?>"[^"]*"|[^"\r\n]+)+~',$ic,$Jg);foreach($Jg[0]as$I){preg_match_all("~((?>\"[^\"]*\")+|[^$Uk]*)$Uk~",$I.$Uk,$Kg);$H[]=$Kg[1];}return$H;}function
csv_value($W){return(preg_match('~^".*"$~s',$W)?str_replace('""','"',substr($W,1,-1)):$W);}function
apply_sql_function($we,$d){return($we?($we=="unixepoch"?"DATETIME($d, '$we')":($we=="count distinct"?"COUNT(DISTINCT ":strtoupper("$we("))."$d)"):$d);}function
get_temp_dir(){return
ini_get("upload_tmp_dir")?:sys_get_temp_dir();}function
file_open_lock($p){if(is_link($p))return;$ne=@fopen($p,"c+");if(!$ne)return;@chmod($p,0660);if(!flock($ne,LOCK_EX)){fclose($ne);return;}return$ne;}function
file_write_unlock($ne,$mc){rewind($ne);fwrite($ne,$mc);ftruncate($ne,strlen($mc));file_unlock($ne);}function
file_unlock($ne){flock($ne,LOCK_UN);fclose($ne);}function
first(array$Ea){return
reset($Ea);}function
password_file($i){$p=get_temp_dir()."/adminer.key";if(!$i&&!file_exists($p))return'';$ne=file_open_lock($p);if(!$ne)return'';$H=stream_get_contents($ne);if(!$H){$H=rand_string();file_write_unlock($ne,$H);}else
file_unlock($ne);return$H;}function
rand_string(){return(function_exists('random_bytes')?bin2hex(random_bytes(16)):md5(uniqid(strval(mt_rand()),true)));}function
select_value($W,$_,array$n,$um,array$lj=array()){if(is_array($W)){$H="";if(array_filter($W,'is_array')==array_values($W)){$dg=array();foreach($W
as$V)$dg+=array_fill_keys(array_keys($V),null);foreach(array_keys($dg)as$ag)$H
.="<th>".h($ag);foreach($W
as$V){$H
.="<tr>";foreach(array_merge($dg,$V)as$_n)$H
.="<td>".select_value($_n,$_,$n,$um,$lj);}}else{foreach($W
as$ag=>$V)$H
.="<tr>".($W!=array_values($W)?"<th>".h($ag):"")."<td>".select_value($V,$_,$n,$um,$lj);}return"<table>$H</table>";}if(!$_)$_=adminer()->selectLink($W,$n);if($_===null){if(is_mail($W))$_="mailto:$W";if(is_url($W))$_=$W;}$W=driver()->value($W,$n);$H=adminer()->editVal($W,$n);if($H!==null){if(!is_utf8($H))$H="\0";elseif($um!=""&&is_shortable($n))$H=shorten_utf8($H,max(0,+$um),"",$lj);else$H=highlight_matches($H,$lj);}return
adminer()->selectVal($H,$_,$n,$W);}function
is_blob(array$n){return
preg_match('~blob|bytea|raw|file'.(JUSH=="mssql"?'|binary|image':'').'~',$n["type"])&&!in_array($n["type"],idx(driver()->structuredTypes(),'User types',array()));}function
is_identity_always(array$n){return$n["auto_increment"]&&(JUSH=="mssql"||$n["default"]=="GENERATED ALWAYS AS IDENTITY");}function
is_mail($id){$Ha='[-a-z0-9!#$%&\'*+/=?^_`{|}~]';$Sc='[a-z0-9]([-a-z0-9]{0,61}[a-z0-9])';$kj="$Ha+(\\.$Ha+)*@($Sc?\\.)+$Sc";return
is_string($id)&&preg_match("(^$kj(,\\s*$kj)*\$)i",$id);}function
is_url($P){$Sc='[a-z0-9]([-a-z0-9]{0,61}[a-z0-9])';return
preg_match("~^((https?):)?//($Sc?\\.)+$Sc(:\\d+)?(/.*)?(\\?.*)?(#.*)?\$~i",$P);}function
is_ipv6($na){$r='[\da-f]{1,4}';$Of='\d{1,3}(\.\d{1,3}){3}';return(bool)preg_match("~^(($r:){7}$r|($r:){6}$Of|(($r:)*$r)?::(($r:)*($r|$Of))?)$~iD",$na);}function
is_shortable(array$n){return!preg_match('~'.number_type().'|date|time|year~',$n["type"]);}function
url_host($df){return(strpos($df,":")!==false?"[$df]":$df);}function
server_parts(array$ej){return
array("scheme"=>(string)$ej["scheme"],"host"=>(string)$ej["host"],"port"=>(string)$ej["port"],"socket"=>(string)$ej["socket"],"path"=>(string)$ej["path"],);}function
parse_server($M){if($M=="")return
server_parts(array());if($M[0]==":"&&!is_ipv6($M)){$mk=substr($M,1);if(preg_match('~^\d+$~D',$mk))return
server_parts(array("port"=>$mk));return(preg_match('~^/[-\w.:/]*$~D',$mk)?server_parts(array("socket"=>$mk)):null);}$Hk="";if(preg_match('~^([-+.\w]+)://~',$M,$A)){$Hk=strtolower($A[1]);$M=substr($M,strlen($A[0]));}if(preg_match('~^\[(.+)](:(\d+))?(/[-\w./]*)?$~D',$M,$A))return(is_ipv6($A[1])?server_parts(array("scheme"=>$Hk,"host"=>$A[1],"port"=>$A[3],"path"=>$A[4])):null);if(is_ipv6($M))return
server_parts(array("scheme"=>$Hk,"host"=>$M));if(preg_match('~^(/[-\w./]*)(:(\d+))?$~D',$M,$A))return
server_parts(array("scheme"=>$Hk,"host"=>$A[1],"port"=>$A[3]));return(preg_match('~^([-\w.]*)(:(\d+))?(/[-\w./]*)?$~D',$M,$A)?server_parts(array("scheme"=>$Hk,"host"=>$A[1],"port"=>$A[3],"path"=>$A[4])):null);}function
count_rows($Q,array$Z,$Qf,array$r){$F=" FROM ".table($Q).($Z?" WHERE ".implode(" AND ",$Z):"");return($Qf&&(JUSH=="sql"||count($r)==1)?"SELECT COUNT(DISTINCT ".implode(", ",$r).")$F":"SELECT COUNT(*)".($Qf?" FROM (SELECT 1$F GROUP BY ".implode(", ",$r).") x":$F));}function
slow_query($F){$k=adminer()->database();$xm=adminer()->queryTimeout();$ql=driver()->slowQuery($F,$xm);$h=null;if(!$ql&&support("kill")){$h=connect();if($h&&($k==""||$h->select_db($k))){$eg=number(get_val(connection_id(),0,$h));echo
script("const timeout = setTimeout(() => { ajax('".js_escape(ME)."script=kill', function () {}, 'kill=$eg&token=".get_token()."'); }, 1000 * $xm);");}}ob_flush();flush();$H=@get_key_vals(($ql?:$F),$h,false);if($h){echo
script("clearTimeout(timeout);");ob_flush();flush();}return$H;}function
get_token(){$Vj=rand(1,1e6);return($Vj^$_SESSION["token"]).":$Vj";}function
verify_token(){list($Em,$Vj)=explode(":",$_POST["token"]);return($Vj^$_SESSION["token"])==$Em&&in_array($_SERVER["HTTP_SEC_FETCH_SITE"],array("","same-origin"));}function
compress_alphabet(){return
strtr(implode(range('"','~')),"'\\","!\n");}function
decompress_string($P,$Gc=""){$ya=array_flip(str_split(compress_alphabet()));$y=strlen($P);$Cn=($y?13*($y-1)/2-$ya[$P[0]]:0);$Xa="";$mk=0;$nk=0;for($s=1;$s<$y;$s+=2){$mk=($mk<<13)+$ya[$P[$s]]*93+$ya[$P[$s+1]];$nk+=13;while($nk>=8&&$Cn>=8){$nk-=8;$Cn-=8;$Xa
.=chr($mk>>$nk);$mk&=(1<<$nk)-1;}}if($Xa=="")return"";if($Gc!=""&&function_exists('inflate_init'))return
inflate_add(inflate_init(ZLIB_ENCODING_RAW,array('dictionary'=>$Gc)),$Xa,ZLIB_FINISH);return($Gc==""&&function_exists('gzinflate')?gzinflate($Xa):inflate($Xa,$Gc));}function
inflate($Xa,$Gc=""){$tg=array(3,4,5,6,7,8,9,10,11,13,15,17,19,23,27,31,35,43,51,59,67,83,99,115,131,163,195,227,258);$ug=array(0,0,0,0,0,0,0,0,1,1,1,1,2,2,2,2,3,3,3,3,4,4,4,4,5,5,5,5,0);$Kc=array(1,2,3,4,5,7,9,13,17,25,33,49,65,97,129,193,257,385,513,769,1025,1537,2049,3073,4097,6145,8193,12289,16385,24577);$Mc=array(0,0,0,0,1,1,2,2,3,3,4,4,5,5,6,6,7,7,8,8,9,9,10,10,11,11,12,12,13,13);$H=$Gc;$E=0;do{$Yd=inflate_bits($Xa,$E,1);$T=inflate_bits($Xa,$E,2);if(!$T){$E=($E+7)&~7;$y=inflate_bits($Xa,$E,16);$E+=16;$H
.=substr($Xa,$E>>3,$y);$E+=$y<<3;}else{if($T==1){$Bg=array_merge(array_fill(0,144,8),array_fill(0,112,9),array_fill(0,24,7),array_fill(0,8,8));$Nc=array_fill(0,30,5);}else{$Ag=inflate_bits($Xa,$E,5)+257;$Lc=inflate_bits($Xa,$E,5)+1;$yi=array(16,17,18,0,8,7,9,6,10,5,11,4,12,3,13,2,14,1,15);$hh=array_fill(0,19,0);$gh=inflate_bits($Xa,$E,4)+4;for($s=0;$s<$gh;$s++)$hh[$yi[$s]]=inflate_bits($Xa,$E,3);$ih=inflate_table($hh);$vg=array();while(count($vg)<$Ag+$Lc){$Sl=inflate_symbol($Xa,$E,$ih);if($Sl==16)$vg=array_merge($vg,array_fill(0,inflate_bits($Xa,$E,2)+3,end($vg)));elseif($Sl==17)$vg=array_merge($vg,array_fill(0,inflate_bits($Xa,$E,3)+3,0));elseif($Sl==18)$vg=array_merge($vg,array_fill(0,inflate_bits($Xa,$E,7)+11,0));else$vg[]=$Sl;}$Bg=array_slice($vg,0,$Ag);$Nc=array_slice($vg,$Ag);}$Cg=inflate_table($Bg);$Pc=inflate_table($Nc);while(($Sl=inflate_symbol($Xa,$E,$Cg))!=256){if($Sl<256)$H
.=chr($Sl);else{$y=$tg[$Sl-257]+inflate_bits($Xa,$E,$ug[$Sl-257]);$Oc=inflate_symbol($Xa,$E,$Pc);$ci=strlen($H)-$Kc[$Oc]-inflate_bits($Xa,$E,$Mc[$Oc]);for($s=0;$s<$y;$s++)$H
.=$H[$ci+$s];}}}}while(!$Yd);return($Gc==""?$H:substr($H,strlen($Gc)));}function
inflate_bits($Xa,&$E,$cc){$H=0;for($s=0;$s<$cc;$s++){$H+=((ord($Xa[$E>>3])>>($E&7))&1)<<$s;$E++;}return$H;}function
inflate_table(array$vg){$Q=array();$zb=0;for($Ya=1;$Ya<=max($vg);$Ya++){foreach($vg
as$Sl=>$y){if($y==$Ya){$Q[$Ya][$zb]=$Sl;$zb++;}}$zb<<=1;}return$Q;}function
inflate_symbol($Xa,&$E,array$Q){$zb=0;$Ya=0;do{$zb=($zb<<1)+inflate_bits($Xa,$E,1);$Ya++;}while(!isset($Q[$Ya][$zb]));return$Q[$Ya][$zb];}function
script($vl,$Hm="\n"){return"<script".nonce().">$vl</script>$Hm";}function
script_src($qn,$xc=false){return"<script src='".h($qn)."'".nonce().($xc?" defer":"")."></script>\n";}function
nonce(){return' nonce="'.get_nonce().'"';}function
on($wd,$Ke,$Ba=null){$Da=array();foreach(array_slice(func_get_args(),2)as$W)$Da[]=json_encode($W,256);return" data-on$wd='".str_replace(array('&','<',"'"),array('&amp;','&lt;','&#039;'),"$Ke(".implode(", ",$Da).")")."'";}function
input_hidden($B,$X=""){return"<input type='hidden' name='".h($B)."' value='".h($X)."'>\n";}function
input_token(){return
input_hidden("token",get_token());}function
target_blank(){return' target="_blank" rel="noreferrer noopener"';}function
h($P){return
str_replace(array('&','<','"',"'","\0"),array('&amp;','&lt;','&quot;','&#039;','&#0;'),$P);}function
nl_br($P){return
str_replace("\n","<br>",$P);}function
checkbox($B,$X,$rb,$ig="",$c="",$xb="",$kg=""){$H="<input type='checkbox' name='$B' value='".h($X)."'".($rb?" checked":"").($ig==""&&$xb?" class='$xb'":"").($kg?" aria-labelledby='$kg'":"").$c.">";return($ig!=""?"<label".($xb?" class='$xb'":"").">$H".h($ig)."</label>":$H);}function
optionlist($vi,$Qk=null,$un=false){$H="";foreach($vi
as$ag=>$V){$xi=array($ag=>$V);if(is_array($V)){$H
.='<optgroup label="'.h($ag).'">';$xi=$V;}foreach($xi
as$x=>$W)$H
.='<option'.($un||is_string($x)?' value="'.h($x).'"':'').($Qk!==null&&($un||is_string($x)?(string)$x:$W)===$Qk?' selected':'').'>'.h($W);if(is_array($V))$H
.='</optgroup>';}return$H;}function
group_system(array$Bh,$Gk=false){$H=array();$Tl=array();foreach($Bh
as$B){if($Gk?driver()->isSystem(DB,$B):driver()->isSystem($B))$Tl[]=$B;else$H[]=$B;}if($Tl)$H[sprintf('System%s','')]=$Tl;return$H;}function
html_select($B,array$vi,$X="",$c="",$kg=""){static$ig=0;$jg="";if(!$kg&&substr($vi[""],0,1)=="("){$ig++;$kg="label-$ig";$jg="<option value='' id='$kg'>".h($vi[""]);unset($vi[""]);}return"<select name='".h($B)."'".($kg?" aria-labelledby='$kg'":"")."$c>".$jg.optionlist($vi,$X)."</select>";}function
html_radios($B,array$vi,$X="",$Uk=""){$H="";foreach($vi
as$x=>$W)$H
.="<label><input type='radio' name='".h($B)."' value='".h($x)."'".($x==$X?" checked":"").">".h($W)."</label>$Uk";return$H;}function
confirm($bh=""){return
on('click','confirmClick',$bh?:'Are you sure?');}function
print_fieldset($t,$sg,$Jn=false){echo"<fieldset><legend>","<a href='#fieldset-$t' class='toggle'>$sg</a>","</legend>","<div id='fieldset-$t'".($Jn?"":" class='hidden'").">\n";}function
bold($Za,$xb=""){return($Za?" class='active $xb'":($xb?" class='$xb'":""));}function
js_escape($P){return
str_replace("<","\\x3C",addcslashes($P,"\r\n'\\"));}function
js_escape_re($P){return
addcslashes(preg_quote($P,"/"),"\r\n");}function
pagination_href($C){return
remove_from_uri("page|next").($C?"&page=$C".($_GET["next"]!=""?"&next=".url_escape($_GET["next"]):""):"");}function
pagination($C,$jc){return" ".($C==$jc?($C?"<b>".($C+1)."</b>":$C+1):'<a href="'.h(pagination_href($C)).'">'.($C+1)."</a>");}function
hidden_fields(array$Lj,array$mf=array(),$Aj=''){$H=false;foreach($Lj
as$x=>$W){if(!in_array($x,$mf)){if(is_array($W))hidden_fields($W,array(),$x);else{$H=true;echo
input_hidden(($Aj?$Aj."[$x]":$x),$W);}}}return$H;}function
hidden_fields_get(){echo(sid()?input_hidden(session_name(),session_id()):''),($_GET["ext"]?input_hidden("ext",$_GET["ext"]):""),(isset($_GET[DRIVER])?input_hidden(DRIVER,SERVER):""),input_hidden("username",$_GET["username"]);}function
on_upload_progress(&$on){$on=(ini_bool("session.upload_progress.enabled")&&ini_get("session.upload_progress.name")?rand_string():"");return($on?on('submit','uploadProgress',ME."upload=$on",SESSION_NAME."=$on"):"");}function
file_input($c,$mk=""){$Ng="max_file_uploads";$Og=ini_get($Ng);$Tg="upload_max_filesize";$Ug=ini_bytes($Tg);$xj=ini_bytes("post_max_size");if($xj&&$xj<$Ug){$Tg="post_max_size";$Ug=$xj;}$Vg=ini_get($Tg);return(ini_bool("file_uploads")?"<input type='file'$c".on('change','fileChange',(int)$Og,sprintf('Increase %s.',"$Ng = $Og"),$Ug,sprintf('Increase %s.',"$Tg = $Vg")).">$mk":'File uploads are disabled.');}function
enum_input($T,$c,array$n,$X,$ld=""){preg_match_all("~".driver()->enumLength."~",$n["length"],$Jg);$Aj=($n["type"]=="enum"?"val-":"");$rb=(is_array($X)?in_array("null",$X):$X===null);$H=($n["null"]&&$Aj?"<label><input type='$T'$c value='null'".($rb?" checked":"")."><i>$ld</i></label>":"");foreach($Jg[0]as$W){$W=stripcslashes(idf_unescape($W));$rb=(is_array($X)?in_array($Aj.$W,$X):$X===$W);$H
.=" <label><input type='$T'$c value='".h($Aj.$W)."'".($rb?' checked':'').'>'.h(adminer()->editVal($W,$n)).'</label>';}return$H;}function
input(array$n,$X,$we,$Oa=false,$ln=false){$B=h(bracket_escape($n["field"]));echo"<td class='function'>";$rd=driver()->enumLength($n);if($rd){$n["type"]="enum";$n["length"]=$rd;}$vi=($n["type"]=="enum"||$n["type"]=="set");if(is_array($X)&&!$we&&!$vi)$we="json";$Yf=($we=="json"||preg_match('~^jsonb?$~',$n["full_type"]));if($Yf&&$X!=''&&(JUSH!="pgsql"||$n["type"]!="json")&&(is_array($X)||!$_POST["save"]))$X=(is_array($X)?json_encode($X,128|64|256):json_encode_exact(json_decode_exact($X),128|64|256));$lk=($ln&&is_identity_always($n));if($lk&&!$_POST["save"])$we=null;$xe=(isset($_GET["select"])||$lk?array("orig"=>'original'):array())+adminer()->editFunctions($n);$c=" name='fields[$B]".($vi?"[]":"")."'".($Oa?" autofocus":"");echo
driver()->unconvertFunction($n)." ";$Q=$_GET["edit"]?:$_GET["select"];if($n["type"]=="enum")echo
h($xe[""])."<td>".adminer()->editInput($Q,$n,$c,$X);else{$Me=(in_array($we,$xe)||isset($xe[$we]));$Zd=0;foreach($xe
as$x=>$W){if($x===""||!$W)break;$Zd++;}echo(count($xe)>1?"<select name='function[$B]'".on('change','functionChange').on_help_value('^SQL$').">".optionlist($xe,$we===null||$Me?$we:"")."</select>":h(reset($xe)))."<td".($Zd&&count($xe)>1?on('input','skipOriginal',$Zd):"").">";$Ef=adminer()->editInput($Q,$n,$c,$X);if($Ef!="")echo$Ef;elseif(preg_match('~bool~',$n["type"]))echo"<input type='hidden'$c value='0'>"."<input type='checkbox'".(preg_match('~^(1|t|true|y|yes|on)$~i',$X)?" checked":"")."$c value='1'>";elseif($n["type"]=="set")echo
enum_input("checkbox",$c,$n,(is_string($X)?explode(",",$X):$X));elseif(is_blob($n)&&ini_bool("file_uploads"))echo"<input type='file' name='fields-$B'>";elseif($Yf)echo"<textarea$c cols='50' rows='12' class='jush-json'>".h($X).'</textarea>';elseif(($tm=preg_match('~text|lob|memo~i',$n["type"]))||preg_match("~\n~",$X)){if($tm&&JUSH!="sqlite")$c
.=" cols='50' rows='12'";else{$J=min(12,substr_count($X,"\n")+1);$c
.=" cols='30' rows='$J'";}echo"<textarea$c>".h($X).'</textarea>';}else{$Zm=driver()->types();$Xm=$Zm[$n["type"]];$Ea=preg_match('~\[]~',$n["full_type"]);if($Ea)$Wg=0;elseif(preg_match('~date|time|year~',$n["type"])){$_j=($n["length"]==""&&JUSH=="pgsql"?6:$n["length"]);$oe=(preg_match('~time~',$n["type"])&&preg_match('~^[1-9]\d*$~',$_j)?$_j+1:0);$Wg=($Xm?$Xm+$oe:0);}elseif(!preg_match('~int|vector~',$n["type"])&&preg_match('~^(\d+)(,(\d+))?$~',$n["length"],$A))$Wg=(preg_match("~binary~",$n["type"])?2:1)*$A[1]+($A[3]?1:0)+($A[2]&&!$n["unsigned"]?1:0);else$Wg=($Xm?$Xm+($n["unsigned"]?0:1):0);echo"<input".((!$Me||$we==="")&&preg_match('~^'.int_type().'$~',$n["type"])&&!$Ea?" type='number'":"")." value='".h($X)."'".($Wg?" data-maxlength='$Wg'":"").(preg_match('~char|binary~',$n["type"])&&$Wg>20?" size='".($Wg>99?60:40)."'":"")."$c>";}echo
adminer()->editHint($Q,$n,$X),(count($xe)>1?script("fire(qs('select', qsl('td').previousSibling), 'change');",""):"");}}function
process_input(array$n){$u=bracket_escape($n["field"]);$we=idx($_POST["function"],$u);if($we=="orig")return(preg_match('~^CURRENT_TIMESTAMP~i',$n["on_update"])?idf_escape($n["field"]):false);if($we=="NULL")return"NULL";if(is_blob($n)&&ini_bool("file_uploads")){$Td=get_file("fields-$u");if(!is_string($Td))return
false;return
driver()->quoteBinary($Td);}$X=idx($_POST["fields"],$u);if($X===null)return
false;if($n["type"]=="enum"||driver()->enumLength($n)){$X=idx($X,0);if($X=="orig"||!$X)return
false;if($X=="null")return"NULL";$X=substr($X,4);}if($n["auto_increment"]&&$X=="")return
null;if($n["type"]=="set")$X=implode(",",(array)$X);if($we=="json"){$X=json_decode($X,true);if(!is_array($X))return
false;return$X;}return
adminer()->processInput($n,$X,$we);}function
search_tables(){$_GET["where"][0]["val"]=$_POST["query"];$Tk="<ul>\n";foreach(table_status('',true)as$Q=>$R){$B=adminer()->tableName($R);if(isset($R["Engine"])&&$B!=""&&(!$_POST["tables"]||in_array($Q,$_POST["tables"]))){$G=connection()->query("SELECT".limit("1 FROM ".table($Q)," WHERE ".implode(" AND ",adminer()->selectSearchProcess(fields($Q),array(),$R)),1));if(!$G||$G->fetch_row()){$Hj="<a href='".h(ME."select=".url_escape($Q)."&where[0][op]=".url_escape($_GET["where"][0]["op"])."&where[0][val]=".url_escape($_GET["where"][0]["val"]))."'>$B</a>";echo"$Tk<li>".($G?$Hj:"<p class='error'>$Hj: ".adminer()->error())."\n";$Tk="";}}}echo($Tk?"<p class='message'>".'No tables.':"</ul>")."\n";}function
on_help($tm,$nl=0){return
on('mouseover','helpMouseover',$tm,$nl).on('mouseout','helpMouseout');}function
on_help_value($fk="",$kk=""){return
on('mouseover','helpValueMouseover',$fk,$kk).on('mouseout','helpMouseout');}function
edit_form($Q,array$o,$I,$ln,$m='',$F='',$wm=''){$bm=adminer()->tableName(table_status1($Q,true));page_header(($ln?'Edit':'Insert'),$m,array("select"=>array($Q,$bm)),$bm);adminer()->editRowPrint($Q,$o,$I,$ln,$F,$wm);if($I===false){echo"<p class='error'>".'No rows.'."\n";return;}echo"<form action='' method='post' enctype='multipart/form-data' id='form'>\n";$gd=false;$Qn=($ln&&!isset($_GET["select"])?where_columns($o):array());$Xb=(count($Qn)!=count($o));if(!$Xb)$Qn=array();if(!$o)echo"<p class='error'>".'You have no privileges to update this table.'."\n";else{echo"<table class='layout nowrap'".on('keydown','editingKeydown').">\n";$Oa=!$_POST;foreach($o
as$B=>$n){echo"<tr".($Qn[$B]?on('change','whereChange'):"")."><th>".adminer()->fieldName($n);$l=idx($_GET["set"],bracket_escape($B));if($l===null){$l=$n["default"];if($n["type"]=="bit"&&preg_match("~^b'([01]*)'\$~",$l,$hk))$l=$hk[1];if(JUSH=="sql"&&preg_match('~binary~',$n["type"]))$l=bin2hex($l);}$X=($I!==null?($n["type"]=="set"&&is_array($I[$B])?implode(",",$I[$B]):(is_bool($I[$B])?+$I[$B]:$I[$B])):(!$ln&&$n["auto_increment"]?"":(isset($_GET["select"])?false:$l)));if(!$_POST["save"]&&is_string($X))$X=adminer()->editVal($X,$n);if(($ln&&!isset($n["privileges"]["update"]))||$n["generated"])echo"<td class='function'><td>".select_value($X,'',$n,null);else{$gd=true;$we=($_POST["save"]?idx($_POST["function"],bracket_escape($B),""):($ln&&preg_match('~^CURRENT_TIMESTAMP~i',$n["on_update"])?"now":($X===false?null:($X!==null?'':'NULL'))));if(!$_POST&&!$ln&&$X==$n["default"]&&preg_match('~^[\w.]+\(~',$X))$we="SQL";if(preg_match("~time~",$n["type"])&&preg_match('~^CURRENT_TIMESTAMP~i',$X)){$X="";$we="now";}if($n["type"]=="uuid"&&$X=="uuid()"){$X="";$we="uuid";}if($Oa!==false)$Oa=($n["auto_increment"]||$we=="now"||$we=="uuid"?null:true);input($n,$X,$we,$Oa,$ln);if($Oa)$Oa=false;}}if(!fields($Q)&&driver()->primary!="")echo"<tr>"."<th><input name='field_keys[]'".on('input','fieldChange').">"."<td class='function'>".html_select("field_funs[]",adminer()->editFunctions(array("null"=>isset($_GET["select"]))))."<td><input name='field_vals[]'>";echo"</table>\n";}echo"<p>\n";if($gd){echo"<input type='submit' value='".'Save'."'>\n";if(!isset($_GET["select"])&&$Xb){$Hc=($Qn&&($m!=""||adminer()->error!="")?" disabled":"");echo"<input type='submit' name='insert' value='".($ln?'Save and continue editing':'Save and insert next')."' title='Ctrl+Shift+Enter'$Hc".($ln?on('click','ajaxForm','Saving…'):"").">\n";}}echo($ln?"<input type='submit' name='delete' value='".'Delete'."'".confirm().">\n":"");if(isset($_GET["select"]))hidden_fields(array("check"=>(array)$_POST["check"],"clone"=>$_POST["clone"],"all"=>$_POST["all"]));echo
input_hidden("referer",(isset($_POST["referer"])?$_POST["referer"]:$_SERVER["HTTP_REFERER"])),input_hidden("save",1),input_token(),"</form>\n";}function
repeat_pattern($kj,$y){return
str_repeat("$kj{0,65535}",$y/65535)."$kj{0,".($y%65535)."}";}function
shorten_utf8($P,$y=80,$Ol="",array$lj=array()){if(!preg_match("(^(".repeat_pattern("[\t\r\n -\x{10FFFF}]",$y).")($)?)u",$P,$A))preg_match("(^(".repeat_pattern("[\t\r\n -~]",$y).")($)?)",$P,$A);$y=strlen(isset($A[2])?$A[1]:preg_replace('~\n[^\n]*\z~',"\n",$A[1]));return
highlight_matches($P,$lj,$y).$Ol.(isset($A[2])?"":"<i>…</i>");}function
highlight_matches($P,array$lj,$y=null){if($y===null)$y=strlen($P);$H="";$E=0;if($lj&&@preg_match_all("((?|".implode("|",$lj)."))su",$P,$Jg,PREG_OFFSET_CAPTURE)){foreach($Jg[0]as$A){list($tm,$Fl)=$A;if($tm!=""&&$Fl<$y){$md=min($Fl+strlen($tm),$y);$H
.=h(substr($P,$E,$Fl-$E))."<mark>".h(substr($P,$Fl,$md-$Fl))."</mark>";$E=$md;}}}return$H.h(substr($P,$E,$y-$E));}function
icon($gf,$B,$ff,$zm,$c=""){return"<button ".($B?"type='submit' name='$B'":"draggable='true' tabindex='-1'")." title='".h($zm)."' class='icon icon-$gf".($B?"":" jsonly")."'$c><span>$ff</span></button>";}function
copy_icon(){$bc='Copy';return"<a href='' class='jsonly icon-copy' title='$bc'><span>$bc</span></a>";}if(isset($_GET["file"])){if($_SERVER["HTTP_IF_MODIFIED_SINCE"]){header("HTTP/1.1 304 Not Modified");exit;}header("Expires: ".gmdate("D, d M Y H:i:s",time()+365*24*60*60)." GMT");header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");header("Cache-Control: immutable");ini_set("zlib.output_compression",'1');if($_GET["file"]=="default.css"){header("Content-Type: text/css; charset=utf-8");echo
decompress_string('*c(<]iDp;+<8]XG-X#ETBP{IAOo`HAD0Z,$t2FfTr3g#Vd(TV$)k.Y.Mza.nf=/#EsSP{RNA{M`c@a0tg&9-nc;72Wy&XvI>dmHD*qF#w3sU4N@=|&JHw3xBmwvnkq<ydKh3e?rW3UW8c6iorbru~-xFWKSUq)z05WLa*7(Z6q_k
>fA~ZVU{Y8%+yFYE(^i;T$03;v)}SxAXNo<2/|a&hF.tk8g=mh`&V[Y+Jg@kV{Sqq*QTq`Pdg~jopx-(Vm;9h^8LO"b79T*xOZYLrw`W*by*"9E8;y3*Q"%:"cM#I?Nm)g"HjH]t<XaA1o(r3Jbt:+q>5y
hEWT0t;QeU+eKbfA)dYUoNbi2I$`BJhLgr:&V-v0b"$EN89Id8>
n)ARt5TpU8NE}!G^/#!6z%BI)xMGGS)M8f+*2HyhUQ+_oMQF2EQi"&/g>W2yT(IE96sGrq+Z`yDc96$8=2UJ+S`$0tt,suyEps:Z-C1+P,@IL[3H3poVy;_1=sg><#+v{"+lM&vrPd{fIErRru1$V5gIRRet>oSi83]?]VZp0C-`G"*EJ`bK6rcS#pSTs^selWAVk+["M;ix#il8A]a52R7R@rGN9BM=C%<x4>Vi4<y<#Xf`)Q382VesHPT84b5.c%I!6vZ+qZK4Yc+3jfA"DS%8zxXru
,Efd5hE+Q&`koWtBokKfC*o<l)_3Mcd!D9H1!M&*SqAXaT]@9yHV!&Lv?owU9lu%,nFFj0q@wfQ!,ovAoG&y+Y$0`?^M,(Z"WKChD*!<qxg5_tzOJ?Y`dNzA:j~qOS7C"AzCc"PX"-xjlFJM5Su[vo/JKyP..nNNxa`-54e<&OCM3<0N~]U"h*kA$HI;N#fiySXADi9muuVRtrYcEs~-YZy"CTBm/dk&lEz2G`mx:X@ii%=3AL"4lv{tN[X)VsoN?:`MMRKfZPX=&q/(0[F%lKJHrT-53p.r{l<1ua
.%*Q/uEk]Z.lo@dy?Rk6Z9y%sk/zDlAqC?s=f2cHx0hd6gEo<))#>1vfZ;xf+!QU<9ls1m4<HR.4.nSkVahA3iWCMDnnDrf{;"&Va_kK-d1_]_<c<w3a*<K]m_OKTH-n0~:}fI>btB8ZG,Vwo*urg6f3OP&-`d*M0ktSkc%{1Nxi)_v#(

V;_RNE>UWBOhds*U0e8t{ZnGaYDJ3>
"e`mW+vNx3)]lct,8m^Mbg9g;pF
6X]}*;u.Gjf*L0ye9GxZfsQ/%)ovdoEhI?)=GvdLMIh:nYyv2+Br9"i|Qc))DM)0ORVRd,ShD#kZk%oa1|y]G/84-$z"q8gn&N-<MjLPUAYyl{o)NKedIl:1_{l}+-Cno1O}"@Er=&<7_XE82CJw$yZnti$xV[8ndG-4@76_KYxqE(Gj:3_i8h_*
n0f:7ki(??jX^=bX(
Nqcn/403Q`G[8()ZxS{"MD/D"RWvrmtX{3uHa]S`8=PqZ"G,0)[G=Kj.n
]r:G+CeI`YBi_58@k.^<(7;<Q#YqF/wN@lFh?8tDtk0HL2gZP_kl+M9ipj>;^xMkz]OO[k3.Z,K_I=f:C61UU
RgNd%Hb:p:,^}u{nEcO6_Nf;-Z/$Pj}_9Yk
9qWn_hqI%K}W(FuK<"T_KQi.Jp]KkYyY|GRy>_Hq.42Vwc.t(4h1;`#-_*6H!D*<Eedoly,F
y"jys2p}"0R$;wx=ls6e,sh<db
uBY9(o*Fdg3Ke0vOUD/)_?!K?4I!#^+<h3J#"&y)-Ul3H%HqQ1]Wj&NJTS2%*VU2EDT+?,]NgNVE<^`aKZ$Rs3P0,qzR.v)4GILUC<|Ud:p[2Q7i:Xl.5%uA3&=La!];v8G_>_B]wfZoU/!lB;cNp_`DJqA[3JubAy:.MMDd>+^a-`-SnxP#EbXIrFth5YEd$/:a_Pr+e$%DC1?<P!5XQ]lb?#??)St=Ztc__DzH{2kDpc_eQH=eNqC-xh[53f$pq<7N2Jn?jLw?/@g=nfRGXV@/~$&Iz%xt.0!*s)naS(fkLwr,)#5.Pixx?K$FstcJFT->afBGQSBu94W`%*Fr4G?lZBU[M0ekQ*M6{E0,D-WLLkL(7bxoyNCI{0_)u(76[ZgDvbeBxvQ*pG3@BT`,NbVey"-Hq-++QiE&v2TW2D~;dX.I7s}Q)_M2d%<2#_mwza(g}ptHZY*%2t9N-G#pibhL0fJ6YmkqW.88^?
On;%1sB%>6YZ//nv81N|?JS=<bNnkVE?<?R/aIeo7Ju9oxbFMl#$+GVX*hpdVvYe5^Aops?Q5ib2WBE+D*T8s#+fk15|m.FEH<2]$r$bBB6$sZu~t/fs^ciBGt0_]3N,D"M{P6iRA[H?t!iFC+p_fa&3NT2&u(R_b}M/5haEWeF@t+n$MHjAY"D~>jy?r|D,"w11?7:Df-I@"MCWry3NxD<G/lT_kUuz4dSAPJ:DL=+nfPkyx6ptdo!BBB0s:?!UV,g<egPzl^Z@40U~n1(7(H8%%H-S
Csxg;]5f>XPi5JE(oV!t"%KpQ?0!?!u+jev]~!T,fN}V(5+m20qhqIiRV,I
HGRt5A.
X#G_})=,Pjv&`Aa`4g(?:l{yPit95/V?`:%,C]2
a]gxe[=F.s3
aU9Pd-$0%tlHg
?sR>~Br$^80iPo2Z$Z=_qJaBu?_^Qy}ChV61:nhk{bm,}7hk,)XDJXv;L/fp"/Lx=cXV/%KkY@sR1gPcJR-KTR0v{P(M76[xTMr1rxcK[cp]NphX7Y~FQHsb}l7l<U55jMp,:cD)Zw95rWHu!H9?2v/?Lu:aVW@I&5*/Jr.6+r
j]xaW<URw9vVx078rA//Y~H1Bm!Pyb*yN0l/4bud;<G0gn*yNNGm(EP*xFJ(5!;S/VDKMBL!uU#wDKvOy.,DxRMqLOXkl7v!#$M.rsHNvZBuR_?J(jhkKsSr`:*iTO^F9CI?6m[gq2ig<5F=!)@Ug}phXQEGGaVe0l8rfcn.SMg)<[rDxKPW%0Hs80yN^>w}O(O;e3Kgw^y.Ud+MpQl_c(2F@-L<XNfSTrL"A,a&O]#(_mC!0q(-`m9uX/.7PW9)&u*~[GuO<jHHh0<P27wI2Aqm]kfqI)KKb_IBE%Y^9kD"]9NO$^<drZ&R#4_fCW8^s9U,uC/Q>+L%Q>k&q1ULx^pctS-T_!%z,Xx5z)t=LiJ<0JIKN}yF+f`SZRBzA39JF`64g"M3#|u*P
$54:q+;xZ@3|*.!]Rrl!QukvN`,=ntjA^8t}tnnDJVBbV0IE=Id>bbn!d!nkq,
MpzBIrIhoo(swnSZg)stY)"l6KR1`bbgRrr_#cp>GHIISPwA.Q)dp%1
~([OkfpfSImq!0>Ph$P%/g
od={Z5;Ob#A3Y~GT&&wElNH"BC.f
&,O@<T7OU@>wlQt:Re78?]/3e-}4XAHR=ea/~;|c/(3&yxeHLKAr=h+H|sclPSCUz1~-!ZOD|oQw</L<
7"J/G)1I
_Wu]5(?:uHtSqJO6-<;0Ctkx.ufZ5NMtw-dY2)#k+xCb-:(Me=1h+Qi21Inqty>RV
YbAKfX7rL4yL;1jlbPb$*8~@=YBQykY,LIXcW#>E]vR-=$
tmyBFQ3"9%ai:W@1<@+-6+;"L]_aD;
C[<w]veXu0z+"[YRq"R$9[(7;m2TH$?;ZbPuV6+hU!b<?0e;8gRJ@dcC^,r8tljexN#K^0}:WDFcfon7#P|!@rJn#BE!Zw&qg08AIw#l9uVpEgW@}hlY`BO!L6?l-bCt$4284Ao@r$4klc3`!y3
F%:HS$,7;B2+rGmC=UcW[&<hh%hBPe%l^mN(>rdbT0e+lu1f=SKEquxI-hg#5rTKn.=*:^;a<ni8.qg9E@"UG
_KGb<@-+6C)cPwS<QaUh(bJF9TaWOF%ZE6ei83sUyt[auyW
%x5JBoBh5:PB5sG#Zn5;mh?@HH**FCNQ
F!+m?p/qpS/wTIq6<J5EChnxwq-vUr<Ng;yG%(#,J.F?AI]X&ImB>/t)@+V@S&ZYbA0UT{2kT_VCZM?7C8GK#Gp]=mWdSy4.*zJUqFVv%?lK$1EK&yB-O)wi%yKZ"%:5ZVg>3[9/F745SQ?3.10DI`t0Ip-zN"kA#f%64{ybdLZ&MAf*ZbQ<fqP6:SWg3^K{ih$gLIT{y@"
v5<(7hjguju)hrhotTyAx3n%_%nxOg@N`{euSPt/e`4L!gq0];4h9r`MhuR"3~XEH^Jq*@<i2_rgX%^?a%W2=xM;r+!h`:"&wfZ-d)$o$8X/1`KtUaZ9p"m{(M<6cplN2O,]Y4cUG*^PZ$nS7:`!I8RA2zk9t4<M$QurHC4v-pges|H1/6gi8;w/Mwd.s8b1eZf=2=1O@-$Q/ZEv-kun+zX]MfJ9GpF8Z1d-/nIQl#Yw&:@I#rj?cRdNBz_&M-

I$#,IpFTje_drQ*0+3DbuW!t@bJ#s_/93e
u@1>G>_.8rYPaNP,;+G2OC;Z"(Y"8R+=<+U](g3i;G$M3y2j}qfwi3"FgQC(<@(Ex3]u0eqmJlGIlHt,%r@HJ7|:2nQ;k?UAKv]LQ_f2G1,&)Mts#<90u"YMcMTK=');}elseif($_GET["file"]=="dark.css"){header("Content-Type: text/css; charset=utf-8");echo
decompress_string('$Osc2b7V>fj0U7tCw8TNfbT`5e<0!4x49EeL1n%e,E<_WZ?>@wWMzpUD:UubAB^u7,;ZJ.!U!ODBJ$p,I?B8.F[0L@ZP|U.08;>OMB~NvqOKctbeXn{?PG0IyC%OS?)r=jqOH2M?hb}k!R/`..+tcti.<c<RYfU_-K2A
vCxFn<6{L^r&8pu@R.[?Y;Q%DJhl3UE#:f_^Gy.{7^;|fNj6ajaX/-y3:kA
^e)ZU{TubDn#S5p^*>QuANxT;&o~2R.180pfCDp{Af#.9i9,-(*Y&"#eJu7R(uH>BnL38{cb_!.Xt`+GTj["w]mNugo|W%7muWs%-r2M[*vyw`Oi6fN"fuoOa;Cx<jivJ:1;k/yuSo;e7`7Ib+sTqL`5Z!<_Msdu^T1o?NxnfVqUcZ(31mO1mW&?l3hyqkSs<KDz)JOYNX.qcbw.a-hF9!u8),&16yAnEfk#`",*pieSR"^
:QnVS6*:q5["XTfNZdqS`4.wq&WqR$ff;}))8p/))?(NoZ>?`%8E(L)Qbr/mvjYq3odVYIZmt];gErx>%}pw8jHUEMo^U6FzrG6!>r%.Fl,i8GQ7.)96y=aANQAV
I&mM@=8CtkDDN#eQ><"PT&0&"
MqJq.V[OT-W:;AFl}kzYka3jtFJR2
a(@dy4=2dZ`=+P>(`R1E_t2_SsXn^QTobmely7V_<d>rQWl@v,kyf0vur:xH._r2kS"fa0O7^l]f4?(
>1U6U+VK-57o7x8oQWpsfb%dt4*EQPSa_B
R5pEdml@-Xb&>I"-^*scQ1=E/ctCOk5vsYn%D90P*?o@rb?
W?IHCMCI&bJI9sK8]T5bt76}rq_*)G[9K2;FAd)taZI^BtuBX+sK60V2H]NeE|*vCruwDpOK^kn6m6f6&1s^Ucs*]9vP^6+%wutWM/66l4Fj)WO0?_+RGg2|jW.*v?W?ZXyua"T+&(XWj;>M!:kHFApzq<`|]GHW(vkK32!q%[A`AHO1`}O*ZI?@w}[)');}elseif($_GET["file"]=="functions.js"){header("Content-Type: text/javascript; charset=utf-8");echo
decompress_string(',]bcjnsZ323o!;f/.e+fx8n;[f71r0pc]giW`oV!D5mSIO;.F)#>?N&/uKRvUtUkWix^XgjX;sS9{(3
eB-
eJD[z_=IA3FpiUH:=MZ3k@qANy
,pQ[T_3Z)Wvk_gT[Jd2khOG[lbtSJi%Xc~x,$3rQ`e7zs`9hczx~5/)NGI&}cu=mR0U@T!doS{^#,i$18TPKy|Ut2;;<LDrZ*rx?t3j]uV0wBhgRMVF+5rB"o&w`p]@K$P3@jPh+.?b}q$QhMV-w*DRoP)N{L2JTta*c.*>bWJuFy>FjBaYa^O=]J40HjoD4_^T?rw%#V3SaEJ,@haT<%p+hZz%2WiK*x/Ujaht!%qA^VCZ%24?IYc7SxrR/#P0:US@|Fn%2c_mX-0TL`9<sF_kip3+A]LVm4i_B)GKtx"@.D*hh<7$~]-CYZwFt@Oa4x]vbQ|`u+7g+B6IsXQ3H9^K]$9EnvPx3cO;lKlZbjaFHFIy,ncrDLW[Tn<w@a^MaXrb^LUyM7x^;r12)nqu8iDuIM(&I)|_X"Ta}5J+vE8Uf0-m`=4yNV{@ctgiirE-<
?TDx$]G!:iP](g0Z*9Tox-iN*VRy5Z>W|@*CdPh,qgPeFeMj{gObW[)Bvq#<xTo_E9Ma8.QeBgQwGwbq:@%v*h-K?(EG6roY+s6+fSd4qJe8ihH3!oX$R%<=8sjBnS#^E#;AmlT"iBvkm.HK$!dmmiw(<TJiS*Fu)T`tfj)T
@N%}vxH6(]h%)|`r.SEbR&c(LU&vpH"+5`VMV)-ZbIT#<fD?n>8n2J%va!Mv$m.^hRe%-X-j8i^ltxz"+|(/%&CpSH2U#0L{*B3d4
_yY.,rLR)Vu[*cHPfLnsT%2)YL*l"Qrl)l]e?}*q</a6L:)^n}&4kh
$76?e_vQYFJn_$lIesca`%Ewhj%@RVQhWxqp+.jV5;r-HtRBw:=<Sk)v"OJJtc%O%sO:
ZZ&+Q1[:U&fw2;M5@lX{[^,8rZPTbc9_#(EBnTtG;ck;)R4J&Zs.JeIACX?%N(wf_FTvYw0/xQqtaLx0CKViZm]I1+UWX/z$S9P&84YP;E=~j^9RosV!7zfSE#83?W&67RHN5rXJPDCS2Gyv;d!O@S#*/Uo2_|#Mu=br..K!3_K;71(Uy~vxanxZS)w%e;THdO(#U)!#!GJB^&t[rKBocXn!w<n)YgNp!K#5tt=Q,r#DG:?&t8?)!?"%DU_7IMM7ZkymqVXhSO*|CkdoTA3Uncu[!&/UNEkv$d-~Q*Y<41A./WpZv1ekD>a6K,o&o{FA<,hX="<85cJg1"q`ACg$xfbTN^G(18VLq|V^0zEub|d9kf63V>g9m>$"Zen)a{$+a[j><8U7*GC%x99n>"T[6}?,bk*,q-nZ9yH=^Sqtfqs+*[fgu)b>FMLo[`a<HAX%w/b<L.xjMB]jfV,Ar6qJ-IvMed8h$K;tjyg2&/*Iq[?l-Gu<2MghB>KoD{5rWJrFV]bxI9H:=zbb7Td4o7a.Ga2ZLG`$+KgEw@fGI~tV,k-Nd=YwM{)h61ry(c(L/g5$-cWtXw=Q4,CNs`:|aRsFGM+*-V_`%rI/YL@:?n(>E
/n2exjX%P[Dq]yI,HU[R3mauTku
0AA+R`a:Co6f388ML#DT)"c(m-FoQw:%p0
PG&r"LiSMarC.JWx4eaW//o9%OUE[0S6z&C/p4(Bg2oXd8!##.{#w]{VW@hQ7fU7o:tbCrEEJbz:6(cZrU0Xda`4xVx!+Sr@c2&GQ]F):Yb?,oH$K=2j2W%07YqKDDoSheV3Kw.3@aW+w*B#4Z36q<!qp1PyQkpGS9Q<-V(^VFyG;w.H_s5LVHIY&/6]::OcSEM!@>EdFKirB$BjXBihOE=7tX"fY"#JZ?@Rym;[F@lmHfkJOU*h/!MXOdxLq`d<FUof^^.We>/tP!
oZuZPZ8^WdOIo#ty^2XU`|0/.trk[&21Vcp<:iH=NZ0ja;jtf&gI8o]iT]vsA^G)4V5//@E%tfMjh6j$+8wQs:Pc&6-_w^U&]In#`fvwt6#4:&L0:}G^TL=USoe%m{FH;,kcYR@`?q:)8pQ7H?c^Ua;GAk9K%p"fbcHuJ?<=6AT2(R*xu?bcvJKK1j@|g0`v(s$PNT=Z#L,%Q_C/a)6W+41|JQs:,1-J=t%2n.5cAt6l1AiKxX5xDG-iTXMpZBT=_X:=C4WdkI&-`(.GobZFC9!yC%C%/Q(V*k-!uQ?f5>Nt#36)EAcL2%Sn0e]E^+Gd]E4%Ci@ycI9C7j"@kRwz%J5W;$ioW;Co2:4g`/lcCnj@1G*py6%(m?I
>n6Y!F]B-&^2@,;/Y(Rnu%
]vb+wc+CKNq3w+w0D(]GoV^?fNN?,[+?;,"XGP<!b!&aS!S>`Ef:XW`PJXr>)1K&i;)lNuL!uR7/(<lxsGy137GOLb]Fnb{Q0;?J!6cGsEYr"Y?Ub0,cG9p8(2YP]j]KZ&QBDC/"2WjK>"/S&o8b2Fb6wZ]L)+<w7iPj#)yu#L"[sr`cp/__ynn$Cp^DHqvP(DXD^N?;~EeOS.aI-,bRWxnj54PhTJ)tt)hTKpj85%TlGg/JD"Br_)C3*Niig;Pp+XAIn(ilG0HITlWiG-!Giqi#F0nRZt&"Cm:`qUNeim?P-gE#PI/
a"/;T^g$lh(6(/gH;^mRQ/GJh
?ypWph-5Q:YV0biLHp}BJA[;=f#6io7b2@)3<Q8:h[ha$a3u%YhD2pVtC+)Sxldu`/!Dco{+*5mmGR^S[<=QShX]M[{NRMC$p3YYLT:T7)%uAc/"dRz"d%df`,&kOKUrG"u%=Q8G4_+YgWmeiBeyG<RMbn@!=7A.:b<i.=k.4WVYNE&4C"l.8GqVU"^Y]!G=d7hP]C69ADn
Wvsg{r$C!a"K(/onhFLaJ=$R.Omru/!cNR{Ooj)>;Cw:](`AVamlBKN@J8hW@15F-MR>V<FN2r^I9#6t}@=#@acn%tdDD"J
jpU3@l{VIqpQo0V`%op*XE&Lk,D0D

xn"@?(c%ua3"y(U2U=!R[OD9?CRH.z?t>VQgh~gFpv#DLi
rg]W*rmj~c0w(QWr]HbdnZmi(6!ICUX5JR~5cMVF[m#Qtrp5BW#D6_#CyQe+;$$JaCk<"0#RWu?mJ2elz9{U%lCxJ6F"_DDRtG:Jnx}[i,fyXsGHmF8UVDO`@S)o`gCQMbROQmJl=C>a~.lq1gJm;O(U/!3L68.#0p4fp$@"cnV^$=!/?+HnGA0M)H.ssyic]=86rn.GSyW_`->;8v_xbD>+FQ+o|L8"<8-(ZuuE9PW)yN{)X<EngGTo*0.4"3RC*.VZjOz!b=euS;fZE/Uetny/pO2!2%KpoP".NQ"NA`s?Kf|&5CgUEOd=$AZj_B~6KCL<?vOt(+_k9VS;CFPjo`)3~l~N3L0gGDe+kMp$.,mUzFUNp3}u0F!DH+X`WMNZ_Tz*-t<qT.KE|IuL`q+3Cp_lS%3)a89,c,CT{e@6*nB/zH;ZHCa:(!1S%OtQ&R^f{!
^QLkh"pVN&/P1b1>*q7m1}PW4&s3U+]#uBPF@$;OO3T#
sy8Asm^:7
.&2_JP:Zk0,-qW"dl:9lo/(+iR)/|6$-%O1L-_G.71L_1jmYO_G$M<Uk16H8X
=]T<Fo]>#cRYqT{Yr,0,E_L)0Y/QcWT1;!z[;l[6JU5C2MY]wI}iWg^UuP-I@&T10s+$S)aK_0m,[*`;2@C!iZ|MbG!qE@xVbEw,>[8dN^_ARv1jovPT^D6KryM]+`&3:;Rbf"i"4QJV+jD_>b
e+TEhH4xm{QetCK4)U"Wy#P
CF2dl*3MdJ(Y56KS(+A.KD>Hr!S:pYGV8P=q+|FytW]ODoEIY>#Ut6(8d}x6.0%7vdw)PA1XK&P]X">6vF3OG];77oJPUeDZ(^$LaYE/N-.[rgAc-ONe4X]erTeM_0I-R*Mq$DeNl.IQ^bW!nj")IX
4h;YvZ/)vK|4lc1d~Sm<*jR`tl=$0YId.ql+BvBm[^"&u^4XqY%a7!;OWLtD6t
<)3NxSodAe*,C+Jo:+E^@
p:U]-M524?An6KVr<@&{nisr]f4`BRkwr>X
9#6x@&AT07WL-Vfq3ICz)xH3?C,Z8a[.j-Fa@6e2B:9II#kYW+yFy_`4iwrIJ#c>2)A.6sz(47V[I|VWm-H{08eRMvh|Ue&>,k]pMVaN%}IUkC=w0Dn:Gh2j<CB<rnp-XkyNN6*H-k
7Y7.#Q6w5;0K(D;)%SVu[(<("XR=33Er56;X.Vb&9>ffFUlVix_PMv"W!i,V+7O3Lj5%Wr}mX7yE{7m-=:Zlr/|L-%_)IPL%+_&_*)W3p&0cY5Hr%ii0Yg!qrD[>%%zG9-C$ah<?yhwhJ_
4~<G;4wbi#
_?s*bjy*<qq$,$k8WU(82b8U0J+x58D`?X0f:XuGd11."XVNnW:5_u&9`^8wv;Iw_#Rjns
^kTfy?neqpfC&/jGD:J="unu"I;;ADlJX4&_a}V&"x%<#[+0shKLl(f/m]WRKl)
-"gS&
[Tw$DHe!(zWMA8#{umF4
a^F@b
#*Qg34SRcmn^];K,~_/[IK+K^qxFaN]y|@{s=6N+):#KJ<5r0jR.tq{c$0Y$Og^v=rU.V8OuwVheIa:hFu:*Cq
<|/n"h,U,-s.J]5#?;pI-UR%$PFF
e/Oo=*_m(EOAt)f.d#5,XDG(J0I*{LIP5UE8.;V]Yeoag4ja:s1+JEzFj+
k^g)U|EAT[BY3a>|+4Tv[nBD9]60W_?*,a+7"f0(p/>O>n_E0DYVMrMjQS

AXlsf(ihDZ3nm]kUyHmug|B4<22u_PJGZ]Wl)jd0x7&xZuMncLL|?n5kCeI:$Ga}jXL-PP"wW.mDnb3Rj2m{=Q;TouoX(e)t/dgh&l5S9bvQSU&[T`Kk3]`6X"DFCd#k+{A@@]57d^LUSIt"E)A2L^I@"AZ;KJUVTmQk5hpTrKFEs0h?w}2HV8C<]hJMG(Oj&cTNkPqcBK<1+=h-vxsKmQjU-dE8@lEr:5O2Lrpdvdb,fM_Jan-@y+FtWF)WH>!D43b>9Ukf:WA5OR"JO{A(M(AdbR6_lSe8;d@rUuW9VgNybLac57af@mT!Mc<MBvYS],c_
|!F?|k,A#k:Yve$dxkHh))p[M!}+P9lLF,[.*1E*1w:pK.{Uxb~.
5aBOr66GW]$7k>hyrL-!
]o;9[K
"|KK7/x0-#)&;IX6SkmsQb<CX^E(Ma^0K/7oo"6ZYF8Boa8j_rKQ$6jJs3:~/Q%]-Ks#u6o@0^NnD
Vq1va&Zx^KtE-Cc5%&+V*OgReS50z#hzDJEbiL&-b#f}&u5w.MmbvrWe.ceO)WXoQ6%NEAR`NUVXmwC!Fg>xTBYKUd>dl`N?e}d.^NWGpvS/6RNzjrDQGDu5fh="-y&mWk`($&C~WK6U8P[;1mo3KI#I]eV0Y}U(j]*dRz)KZVb0qxDbU^d>t%RuURf:YSWQ(=#@GJ3ukLd1@@xQ9v*GcWSe(55R,x8/Dut5<XI@1g#|gd:STBi3ijXD-<u91iX{/v"{Vxuhs.)-7;fB,E?GE1;d]?&b0]^":*y1/!Md^,1h3~(HnZdLev1jv/KTPkdX-yFp-H3Z2r$X;yBbes%VMJ-O%.;r?"R]->p;>Q8~)o0SQk%=m>_ft4
FQ)Zll{4"8Rc[FYo`6k:$`?u<II$kjeM4T6Upowjv!1^lGd4<;Ndz6~A^;.&`XLFyrfHrPio@u37HqG<
;SX8-8rh#FR>Jk)=3N?`8^:dtICNT7XNVXc@$mg0C`gH[#Zd(M`v*JX6ZyZ+

]|<&^""{+rO,Nm0uCs?7fio02`5740u9Fn.($I2KX*Zl=lHftx4&ISy8(&=DEae43`Y_K!ZA"i5Kv|*_DD[FU=,C3fF*l?Cr/mXRWZ[skp*exF9v1%yoAS@(1l-V8$@JG/07@xb;yH?+H%,Der^jj04a=i?;^L/J@cVb`Z`6V1/*?lv9Js>3Hxa0mYOq-OUK1d1|)/5I#oL!p1Yk>*-PSZdn_Yhw*$24/_kQMX6^U$TgrL%ReDVf[/v$"vq}Hu
:0/%Jt_tsmpo$"7wp[eKL!<^WOfhmYb;NllQ9T9J%vcRRBb0D$,WVQdV5J$d,-6BBbp<7%o:
>(aoK*YZ3P/RQT<|)q.wrCP$cu#Ip!uQl,fxCRYplp2Y]@F,;]*Ce5<yLhT?Kh)nJGPh-TRp)<vSZ]JJZW`1*e)r?WoQHpEHh"#xAE%pq5@@,QpXNGscE^`UQ46UJw+Ti=J=wI_QVVJ<Q|in5gP$_ySg4n7CxXKr2D5o=:(co>k]OEH2.^W6_(GBS:YtT*xo@g<MaogITytp
i+CA>]}XZEL4]wP)?fEtVw!fDR=,(.Z=hazd;e#H+<=Q>YOX@kJs6*1*tudVO^+2a@@4!ZRq6CsqP2}GEBKQ`OSWUCLLdvyk3k.,L_uei^@7NZ*9lUa@+L4).2n(qx$QUWi6PA[X70#H&)PN~pkDbU<3JN,$nFfDqkehqX,LH1kyx*m-emgH<4,(]7!C(`wBb"`gxsWmL`8.1tBJqX`eCR<c24}7#U`%NFoZppTk1$6Of#~%m>q:@vJ%|&8>e_~$AnjlgCdW3VEa]A*wgiOv#W|+r=3`"s*XW+3?rSIPiZ=.%)LRH>iQgL2]sez.Z)((nF88!Y^AN#W#5^@lXDVhot|<6">)|*aLa`dFl*fY(ah5g+8R!^mqyF9*Hi`Z)iI9^m0HBw;y]*63?Z|%nm*>l2Nl-#3
-:x]@=@Xn-Y63EQOBn1[If
$T)tTGsc.vHw:,>*WNMdv*4(o1UwxRaRT"w#3YB;XQF$.`LY]:G([XDFVgZzQgb37ccX8WP5yj>^vcUv<7p-mmHLaYvUvAoFM>k=c[#u]up[p(IN>0a(#(
N:KG6keYjdaj5)4G`"p+SC[K:n&%0.5:bir"++"WW:D0uhDm~lX*Si$YsV"!%?Oe{<Ul$0L5?({_^B&^:Ru?b*oT%=Y*$%R4~@ylBiK?DRo9ZhYMeca^/M&*P(}d}6l5{/i3.nP0}<Qm870XU&Z/o8:pIm>.P^jF/C#,?jPiTu./#yQSf0niY1.`+S39H!4`R"whFE
7z8[?%h]yOR#Rl8s*o&v<pEk8V`GC:^Om[c;Q4!gKX@~:ny7v$%^^h@;eABlu}6YwO:htBw;6jP/=hb#=}=1T0:a8B]prgso1Nd)/2?YKCR2nPV?@-#y^Tr:Mh&
Af4D[@NQmdSjv)k=b1?Iu2JZG%?r1ILY)k4oE9v4AE[/0TeSJe#EAz/36WP$?TXYnkTx]>4&4M(t7V3jY9ikKtVYO."Pm:N.rW1sJfCn4Il(X2VMw(KiRzI._I@SC*d
5Z4T$#B{qGHF5xOTFgoopfBhlVD^duv7hN/^UJ4;iU^gO!En,VvB`=$2cfH}9
.)Rc@u%
E~.|9U#Oq^e8g
o->HMi#n;hW"IvK$.O^7=m"TdVJ0.~SVgE)B5{Z9o[?)tp(h4#!G)yWZ?_M9-[w80"R&bCwa&x)f6_@N#DRruD^lG.!Hl+3.nD84OrYQMj_GmpPu7*1BN5O(%$]GIl1($Ff6]I;&YO>v_TJ=bW#M
DJIUN`s)f4haDO20qv~k<ZxqNX"FeGlYxW$uxUh]Thq8=PFf=bW8<2b6<Q
.;2gWgLdut,!&#trm_f/4=FZ>l`KpK
W1cr}y$[Q
0o8Fq3l&<+h&hm|S6>>fKTVB>!i"}]>.C#D0K52:.nKvThYM/*MjLASiGa5PgmQ@!4#J9[>7i[G6S[7Hf_P*d8
C.]T,,&~0/#ebPNkMg+C[&LU[R9Ru44jr;V|(&RS
N23)sQ4_FE{x<Be<*NW[dG|exK(eO`nfXyCdFMh[:m~94"De~-;+`l5s3w.W82#pt`*A@&%]
QSk5fcMr[A=vUqBV/3!,j[kTZG+55V*p$`ATD?BnBu8zJjEw^_k9kzeBEyAYm]`^ZHj@DL/@4r7wxT>O-H7o]/w&P94&q8lv&KNU
wy;AK.|<+p8F8V&J4#*O%-e1{S}xFfEIEY,_aUmepFrKb/*v&_v^qi~.*=Jvt]N_612+a4uLw6[;uV.V)"E?fVKj@X(o!b)ui)ZIP1q^g]]0l6xG4T])Qm-UI^/E@9bFx"5*EG01O0c>_Ac/?T"v+G;uvd`F%?xoh4fyVK*w|yBMq`sA
K*$N+H8`?L?=o8"E.BQ~xUGZ3O3[=Ty"N^10eE#?Fp&%P9wM_BN)N-RfhD>u82BpUrE3Re,|K=)!#qtmbU:O7W;4B9%hn_"5v^^1UR1|,UcDw]2H)Jl##L[/EG(7QF@B3%1O^!lf[|yh!/&}Etd,5QLd/0fN[m8Iu5=Ai_B9cI#nALItxOg#H<"3-Qm_uZ>@iFj<N>iWWblKq>b&FwX.#I:um!dJY`J>BM-^HNwZMgWF8e-jr,y
K%Kt,amVj#t{OX.X=q`RCS!S]vLs6uV}`v-nX"eS-::<2R[xZ?npZ!&&9xtqSB%E^oWZb}5GZS_!LT0qL-c*e<Aosy
&;w^-O@o]&(skw^]4]2WiXX/#[7g$!M1H$l%)*->;O"3EB..zi8p@VEWKKWD-4%/zz%#L*o5G^F7.sebw+Ert]jcW-e`Z"HNQ%@2+=W)fDMk9aXKx*]QfQI^HdDu;ntGJ_a(.`3eY`Fo]H*Ll&CxX"CFfK%1rmC#d7=<UL;8v3Lh34i#*vl!md+G`@TkI5uf}PS146y-w?;m~9Q5+T}-oe~mXGwnrQk.a]PT~3+5Z_DDYaDeF,46.&MjE?(TN/(<fRW3VPLwg,P
_Fe&d4hTe)BI+`QRHg^t,hJ7|#,[=0w^
d_DF:fo=n+%t!X$z%`V+IcQ~sL([
=FF0:3R=HapXCg3bk^0#DC2Ry5u&1bz#"2gqq?hT
e|:pV+Wt%NCPpDx^"L0Hg8Kdub5$U2&#WL.OU=lkT03i&.bWh4AH+8esD*3H:l.W3*"lPBfRj_(WU6fPJ?
HpriMvg-/#IYa%.>w[LQ&R>JN(a-$jQLUT.+3iikoYDgVxe(z#XIt1yvnxdW>DLMHc:Y>RlPsbSV;kW*m-Ggv-KWfphqdFlEP+~9{n%z"Fz5:w&ylyo2WO4-0BX/Vt[/054!XO4`|=IhCO^-$X6<0y`Q(%Mv%R)W5O:.89a=W47-jVJKpAuL=59OfTcR&4HS>mvu#crlW9TOi;zj6A|<5j%*RAcO)ojgD_1Uj%(DpNfrc8;[oci.,1Cr)(lk"J)_Z2M$FS?b?s[b/?B,@3
if@hL;Hw?jM$;YD
LH;ps~Lz8;M{QyXrX{,c3(U}JrTE#1czA8ws
,.j]zszAsF7i1z"=}!_:A,*wAw}k5+NGXV|fN7>tQZe!RmX1`C.<xy`F[:rBL!`ZQ.i_?4I4l=)IH9a_Cbx5PC{y{ePAJgq/<BOrf0@uk+<xjywQv,qI4:r.uN`0n8^gyn342;P+KB@=e$lo`4pN<4J]0z#*}2;l]8MPrq8j{:(R3a<&xWA64DH@yDl,(ar:|N=-*5T>[7r=-
cgV<J

;1=@/l>)O+qG?iK$Fh5Fqj2.@IvS$$sz$W=&&C/;;I/R;.Cxn=/n`v2#Fe.,<2!P4M-W=s)yHq?GvbX?s$jRuwh`wS
q)?4,Gu
WIplfqzT_.INJ+RYlV6yY/4x:X)*e2NSV7KZ|d
exPg$Mo9$V%(gesw@u.oqhA9bBHdrN":OtmYiO(y$mwyNkc(:qfeY&]Zn.1p&k2dt/AbJ+>eNZp6^}vc`pl&`IXg-Jl.#QF!?H,uRGYE@`JH;l`^<yDvXHPRWA/,/H?-Dq*!^V.^RKH.iyYC[+9//QPQf3#4"lVd!>Xg?FBX#89C=cA;&[8PODUg5HEqIx_
J+_~K$DKo2YnRk-YRoQi-:8UdF?5(dssLbcPkYA~$c3KHf7C-~Lk=cr*@]mA&`+{m|`IMf.Sx842"=!j;D9m@Z*yqT!>sIDo2r,{K>8XF~@?`jkm=2."L6S<9p[sUrobaw3*f?mxLDr^rRvYjbaU&G/.e,gDTl-yI7R{/I=rD=S{F`/2hW3(m!ie+I0uEX;UH,aIM<
+o4(<Q_baVKMI^F&;[pPpI}XEZ-2THzu;h7Xl&*$E)((1p{5H0!XWaP2&MvASJy;>dd6SK}8b2,sI!Kh#@#CXLH]^VaUhE8R][uv4a!`_BiH-Ws+Bmr8w.qqYN?
v=j4XU5PGZ}4{<_<&>cGBvQ_zg.=Xj`h7Tu9P&/&.2;hGr%(uG|W*3n+!n}q}&N)")g7jmIC:H.H!L2Q_wgj?=zQ-*-6TVZ>zQg?%s:j
^+T~vYdVVt8-b~H<p8
/c~L/J@_YS44w`!Ds^"&]Rrs>,mg~<^3zKe[-0x70/qVUr}!m:3#w.c-<oB>NADOkWAH#o5N*8F;>[1xJAIdf>"6Vpocg=D&l@/f1ph<JsyjFwDM"mo^:!3eW+`y2f86`$T-$8EY>*V?ebOMFdKmRL!E$xh7Nm9*M.)mi&B5f54R&?,HI3qrDL;+eL^c(T`U9
V3L`[OSf1;s>Wr=v>[h<K<4Z=?(w:@IQ%2j`"c%o>ZP1*.J%7lBT=.&Fhx2aXFoGTZYoBx
?S,$IOqjMN.rbWU7o8f%NJ?+3l/%YK,vd
AE["U<TxZ!$vbSdKO:[=;$k@,9yMm.5Stko]=o7%r7nQ0MT-IxqIB7;ZVpIcmQ;w(6gE/O>%Y
NT`
r0kSbNe+1#yjUd3_.Q1)V76LJM,-LWqR+OO6]}:~`NLKE4QGT4TAjRctPI/.:KC[vMf,>f
D)h_>nJI5hD0Hyurz%A5]N~o7neXggHuvcTX{^Mn8s#mP*[S_+(&4FnA^a;KDB49P].l5rDTGG1JM/"NE
WsqttZ4>"nvqF
><*AHp1_yD!qGM,?kke-t9)isu%v.BGPA9l4M%rFfy>[<&/2tuwkSF$WRG^)(LMd9$F^<h.bSFf_w.HPyESyJHBI[6xcJ,ZK)h1O(QS>e<!QwK6I-<e(,onn5)^XK71q!2iy"eG8
QFFZO@(ePBrmgW/flK4%g5wXu/vLkko,*|$AXqj4rcQf@`
P1O(
:r:<)mxQk;6h51`^&.yoXvN~Y;xMlHO,>*4n`+uO
vG)#Al[(3Ihj.M$aK<D6S_)/Y0e87YaD1;h&,StXMl>,bC,O%@co|MUb;A<8p&kmHCpn(c`Ulc73TfG-7<R%17obn^e(Tc9"MXbH<g>#XOYb]5MwvjtKpsEsGpIYz(B)3E5YPq.
<`+K"aM19:K2:c<oF=+o1A]Zpo?s~+AdlCjSr]>iJo^9U+EUQW`-y]T=?8Wb=q>X:2_(il#;RHtE1$wWD7HL;*wVQ2N[c6!AYa{6F`[4]uWL`5;1_GLV^j]`(&2&7^_pg0XHP6@d@F;dT25jpIT7gchhud|1nSfw)^_92W=a,Is=CLQ)-.w/_3|e?a:5g?Qn|uyXwMLszFwt>MH`oP=[#uQf[^Z_O]YVFTZa
umh6(co;n{9j_["wOHZ6F&d;u=eaezp})Sk9u`Dr!h.#rK(UiSy(2v_?%djqivXRE`Ezb2JQy1mUFBKx:r_$K5@NWGW5-Qd.",i@ozJk]ev|MWRdmf;gr|fshABV$pM%
W:.cxZoY@0{u>4M6iOEIP!f7:qIj{b|,Wn3h
kuX}O<Jbe2&AP)eXm5TwM9@gfZ/_W5P5B|NMU`;rr+_a(6[HTITbY)C"Py&+1<[OFNz!,]lkQdf%xgbl8;lC6j=YbTwP>dC1%5=B9(pyg_cx+toO#t^0*<$cG*g9p:F++](-Jng@_]m8$9uIG[HCCMV;uyyr[As%7wTNaw*`+1`kcf5n"u7q#*Sz75`3%A/xcL[H(QmcYq6KD>1D8[$flLqg;XJnw^,IL7BodFMp"ph!V^TwIvRTWa?qb8E8=[C$Hz!YQVfw(UovvA-^=Oi$U#ZH%[5M@6woD!1joZi;LOr*=aKKNjjj8g8;`UQZe,rOp.N{50_R8XZW&aBThloLl4)`lRJ.](?}$vZK,#x1SZDw2uKP3HsFVD$lw|>F#2Et*?7Rm2u<2kjQoVZFz&l%oI*SK@."AbIvA+e*KI9:irig$8>pj?[5N4y;oBCO:sx&$bE@y0PY@o=v`pa2nUvGs|*hR_Bq1Nb[>2Fk=$)9f;[~ut[a10d~pgYCc/SRI>gj[|^ikWLexzV,Knh49@uI^L;@@%*L5U-(Mh!s_/:~30cAp&+W6"qs(2yF3wgC&
s?"|MD92XrEb<utst{q=4_7=k8JPLD_?9ND|!}sT=9B:ZP0+e$%ixO`DGmIj"+sI*hl[xVNkf*YIb[_@dtSX.L,Pm.Z:pd`bZ1"+[.Gbx"d>c]h|"Q`?iu_Jxu4t.S%Y#UK;X")F5)i
%H<Wy~@Cnu*r[#d(1GUoZr"r%6:&#~7Q1%kG"i%l&IG[R#uNd?WhCd[6xD/#B53bHCw0"`LS]O1{=+-!$$Kv=~Mi*tPuZexEYsfh-]&gfX^-x<x|e<rDUho%y"O+9"+
S3HiZf1~aqJbPT=XQ54|,~HN4/-;%ftu?7AwEBdecjnC:6-n7$(X*!bvgK6%z$.Rn;n_5UPaP.(~>t[5)"1sDPi"g=[@HibQVk=COQpI/j"|x
N?C^_QD>0JX:G^!.PQY^B^A7ouO2xyA#F9=^B^>NnxgAAdu~`z(+5nKOWKz"^aZ-F&W,dR2;o|
SsRH]bkB`0yR,fY`
aK]p4sZA4*9[d3?UB}UAPYdo@f%*l]B{q*?snbA:kmJ>&,a^
|q*MQn}^biSyeb.Mri36e[ix#7q=b]y7BN/U}$7E<GhH~B#8DCk?)>tW,<JvBajZLJ}9p>y50qiE
K&IZ8X,i<?R2q/lx!v5{vBP3l0+p
Dnb+`@#Quv5xa_qfP]*3nJ(YyJ;B5T?.<86ILB6f+f!GrcVw)I~
`=vcQL?M]a9Sta-m:+H&}"Ru1w}#qAS!mL<ug0"Z%@<()!V<zCO^+6&WGk9f|Y>vP_~Tt=pjyJEZn%v+
Y[(YAm:W=6fy"x7!6Q:G)&^+#tx?p>I%67
`c"`uo)msRtZ=E-^*E5o-w#kkx($6b3%~
(NVw{N>+>Qa0VhP6P-fH8daw.)1[t^Nkz6],~iPtN>-RnZFmaTqcH]C">Cy/jV0bR%!$q5.Rc^}OKunql#n75s0o[*z!RJ=jw<Cpv9W0
9Y>TZxB"
jOzFuXa<:EB.kC4K}gx9r(*bt@nPxe:]c6=j!1eS

:uL`BOSihCw`@?y"Y&tvu.U,:;{xl4`g
J[_hvutBlk+Qyc`tMks{8!2YOJv5.+_G*6[Ol[EihVPmDHaRMNSc+V`CvFWGyg#!OvX0![D4R4/!D)-/3U!XTj@R^IXB`po!MCd7=4Xnx-m<)ehGp*aWLr`96~:TMjijT@[HMgobM*havf$)CR8-mICksEF}O$<L;YpyO0[vb_bmX/kzX!#1_H$u2$;!:VEOYlDb5
]
_$(e!5:EEDa[00mRW:(C;pN,=cyJ(O[yoaNYV{DiUeH:3;<xm:Kh,>,~m6ZR4derhD:!xtfiikb#X?/(JcSja]$b5rYhLiI9N[a&wI4t6Rt$j_Fh6%BnNmSWx-
Em#(=`j4Bk=8#_AB#&.K>G"V^V}3D+mXF,uGbR5sbJi4lqRl+EZlbsfL&wReY/`ceOUvp?dOTh$VJ8<,3N`0#OyK(,G=^we!QlRQ3XIljf):k@n"2<
2Te^FEm}-/F"s|@C-G?ltx#|G6(e<820omi0dHU~vUGxYoW2hwHwG9.2f<04IQ!-.X/1"8.ANJLT9!j}!j+yWqk76hJdBS&`59#8*/%iBu%dQ3ru&FARL<J*evHEJ
e2!2@Fxu1ecACL
A"?"=uS.r3n_Jq6"vG?.v4/K>FmhGVD(:aW6d"XR]lFr03V1Pq!JC3N$g,A_9in8xxdJ4w)$p?)9IxwOe$%w_&)-uG|-FDC.JUoUR89J74REn!qsnPD@}qAJ8qpr[Y/%,NRocLj<VxUY9>vvp(c&D4*>nu##nUa)1[e/8i%)vY#/4gLmA"`PJ8X<|Gk9!]UNymy1Z*v>F4]fz>zI&5+D,v%>e;&v&u*-9.&/`[.=1CCXeZ.(.<dm7Ob#ZF"PsrT;GT/0<18g0b$^)mjnFBh#7IlO7d8A3.TfY%VXQT@Gwi]N>1G_it`o]4NJ:vMY5kg_Uu{jw!{2n$7BNp5.,Inson|PQR.*MYE3Km8A{
3Uc5*RZe$fgfJf3jYsJ2OHY/~vMBjM~s%r[AaDExV<bt[
%o"x5J;l9T
HRU;O`ccyho)');}elseif($_GET["file"]=="jush.js"){header("Content-Type: text/javascript; charset=utf-8");echo
decompress_string('(hkc2q|ZDp9v=s+)0U>_#9{GJsN@RAoYT;a<E5O004<sD1eUn.QFY+{>ObK@j,oKX2B$Sj:g4hGtSMlq%lP[VBR[oNN)U#F3tx0_nLWcvYuZ8-w(]6muzb8g8AHEMV|Qqc@=Bs!Da5vowV!Ft+<c,h[Agi:EZ*1K*Mby@O>tba^EEBqA?S9,V!GM:;FQAvKmWLC@vcpHiBjBAB6z(>g:g<y,vIbv4uWOcY.y:*Wx#?qnkr&x`M"J-ej^t7eeyqaSMn!+c2HbZtwu3v}EHD83ws)JqGxe[2j62<6O6)`Z]<ca>BKA;]c</:p,~4@y2kZAD:*5<y+da_B<CZBbu$l/z0LGRB"z)Tcy$W,r:^~]#D8V1JS!ZfXHz6AF~`BP{.EhG1+e<Og?px_J^s!bmu!?sHw?PPiG.ow?rLOMHp_eerW,rn)KzXlTWei<^gZl4D^iu.tDCrL61aZs@`[Xu0Wlrn];KPPhyTg)GyT5-CxkPva/&QKBsL"luBI_O.}[//vF(-pMZY#6weT[l::byBJx*].,KsnZnRvv]w@7@i>P0)bw?6|pbMe`RjrL/$T2iX2+6`&a-6?.`o&sgI&+MapW{=$vUO>_IP;^=XrvYS*vPX~csAOddlk/}M.x8x;kk,io6jd`
0cxJbMhwR74W04%1j!(s76tR<Doh(Q(r$N8#rUGdIBFC8[>~eoA
rF`!]siqNB[=Hw]ncd.cy#,]]&bLsqkxt>1!In
zUe62E;b=FR1NHCm{;X+BmNF)X_`Yt5:gP_A[[:JWWJ9`d,lyA#XI?7$bMy"PX7oh;chW:HMbVM)rx<5zs@lW;_Io8nysOs.YMAW0l!rd)w?%qo<9@2wESV-IwtvM0ae],z,%Fl^=uXmEa|x(pFd"Xk^$4}.AST%:,_5RUMR
igZUWPAza&qmys7mpc1Qw8v?cN^kcWkS9V`HQ.iBxKtJQPA^LN;D6d?GHAr9FOjq`;kM32KaA~p
`YZ}TWs_>X(<^?7KhZwMM)iv]`jxy^7zI$K5wvtS<_tEiRtI7b[=mBmtM6^3IwQqS3Y:IS2D9y2ynRt+;bI(x5`J4%"w9U2c(hnlc}sT-.HUA>qA.Z.%;[ZU)F
Fc`+[cC^Pa=vLoG-^agO(sw=yRyJqAH1dz!a.jhd+
uvl7QeXZ{A}MWUchcfK$:WPY$VQkSooeA^i0Uw4RA*1-Jdjg{S$6Oa#R^dcvrbjM}0475LbWP)as$N"svySYv`S?ownY_e
,4&u>J82k^m[x#MRPIe[b1@SL_)D-HuYy576<50Ps+hekv4^0@+/@xn>cubF<@;%E4Fmja[Nu*Rxh^c|pS;/N]1*Fb>0)Kx&f!+HyBc<M
m]U;%}g~VI2?G4y?l-$Fnu>aQhZ7G(OFa)DM)?^#q;J39;NL3b2+W&`3_Rx*nEAbTZ7_RTnU`8gz)5iKLsV6A*4VR/jO"hhalCGKq{>ob:(K17:`-]6}@#E]45=SK%Ily`aadt5_N7dw,mGoT6]
(b0BnaaVguI5IXASqw%xag&id>rYyhqd=G[`GL3Pbj1$/eMktGFK-`sOnkAZ&,e2qsD>cfv:FIRCPZ.7p7i|-p:pg5u;fhyaQW[g,$Pn16MgARW?Fc`dS}yC<v0HR(l?.[Kg%5?6kZCRMIC>l3u$`.B>8W:t>DtS7VRic=P#icn,xcO<ifE|tSiOW3=e"bQRUHM%rPK{iusUmPlrQocj]=;zf[>MVLCt`-c<R!P|0~1%CXLzYS2zi[xya87X@1DZ;B:
V4iEvP
?t&KSR
M>7`jGbfDAIX2!a8`73iv1:
tGn<Ai17N^a71ruX2B8aPe7y+0?t#30KTvdJ"eKJIIt@.y/oe]YV8VIBwmlMyyl!3ENe%3,_cQ4cxh`/Be*+KP
D)@v0&]JSD)x1Y$uVf*wQ1N(F-5QM<WG&Hy,Kkw&ht>$j0{K[D/_QEB`Pt5_fP%Cy_W]%`3eY`uv<gN^|lpj"TUU8Ys60X_a]7T&*%O/;u#yI!$FSys>rcNK|6}bzH&MK?>n4i<y!F!bt@;q!?Z=D!8xP>?(B)$,}nlZ^6<ECk$v#?,A#C#tB2&rnp>.@[h4Plt:h^|$hx"y}SwK9/1wT=tcbMIyjmX^2qF[Ld&.X%fj5kJ]UBjLOr5YLq$Fp73!"4e_]Yse:;)TA_6Y
FBK3]bJ&f,"gOzcf/70"[odirJ3oaM:0@hx5=K7uMdt%*MOTHjx7>|k%S^T0eH]o&,$7Iee/=8-gQHLk6%qC]2L5[&ww@GxtoaD{E4N9eM;ARB<mXEi_+Ac#9IlAc{lbLDrJ4UU2+Zb8QhmkVc:6.3a)vOKg/cZTlay^7~`TMG@o;T,yc@7>cHY@RnGqrq$Q2{r&.VT%)gC#,&JPK[NdKM(/6`WF3>KLEN5wba4<.k;g:.*>5M.2DXlSj*A[>9YcsAwV6|1-(;d&3k_jw?N-]U(-j?9T80H&lgGp[Me5;J/-)A*%,vy}S78b%B`ik9pr
/0hPQj]b5nJ$9b!tMNoZK3`0%MUI+LSG!9AC1H*s2l}cDjZ2{6/HdwKg:U#
Y-~g&gbhe*dJT_2,vvTvrrP^gIpnSpH`OE!>/*@NV#aE~jx:*Nc4/p>WY"i3-:evsw(X>B2#eECCl]/)v!]F)x|[YCHlsWjpd.r2MUw/;3A1v#CJ9TA)5
WV}MaE-xHsRC|nqmc"
CAk,nfTKo(o#A)L=#;8IMS`uep2B^R
hC*["Ri?xLtFQN&rfQ!tquP+x:TC"*obmY9Q$*_j|Nm06KJI9/TrgbYdVs4MB]MP$uEcqti+8F/$Yi/j124/vgWm>w7W0-oK$4%yn.z%VuJ*_hPO#SxGj!Iafp%$EL#:GxAo8W<9.y!!Y>=N_(3iT<,s$;6cuAy=fo^BfNjYB%>I]%fafW%M.DKXF3U
rV6AJFigVmudH*73aFj?ul2sI+5dTiL?cx9_j?mb3#9gw8h[.W[j;!-X7HvnH!"My6n-h6h0p^.LNyQT6bO=DJQ
)&*AWcOal]-S2
;U0%kXJ2ct/N1_C4OTmf-elm8g_>wl`v40x*cXhXqHl5XI;93d0wvwXNM1$HR+:Q_Hz&,hz$e25dDI_IM1K4g7/&"J!vHA,bMO]vs`Eixj^Vn]cDM[GSZq-+nARWtU(jS5B?-Qe!{;U$Z(hZXe7`.v7(2HcDk1a+L={v{m2oXE/GNY$l3[&/+#n`huJYxg$k<.:8*;p>iI{h]*;;TKeBACvX=jEW4nTd"
Vt`2XMImWgcZKE|his"f4^eM:j}EAMl6HB|)!UT<68Ha?ApZ!t%`X!7v|qaH&ZtDMbMm_8L>Q[xpr/t>$d4.&b=Dh4uks,OJ:No^aS}1tYdqaEYWNsHE^T
o7N?By9bPn`l`CV)sihvJ+cdsTgBHN;zhLfy76N%tv]k^smKQF6h6[]=qlF0F5dbimn+Pe&|CEtwBH!j(
#v3)lbskWB!#[bF7;o<-[D+?MCf5/^b3l|iD;Zm60>B6
qn-u:1GQ+"W/KDy/-ids)BgR,J#5LQ8GhV4p<GSwWj@xc-3PWJIxv%Zn;B_Y5v-Nc5p@!;H
Z+7T*I0%2wQ[C[njn3/lgm.)j%{,)rm76OA?z$gDtNieaI4j*M`TY&#u^=K95*S$qxXt5S72Q+!_!.lwPCO2s#QA^T5WJX0
B5:$#w<</#K=u1b5K1/].r!tg*/6u_5b`J]8B`n6C&4AV<Bi+vw8I8AjX%ILG0k9;E-m,x^rjv]qRQ7DMAQ?kxE],vTx}<QkRO2;W%_BWf"4L>a;6F[sA2wCG=l/
!LhV-z4CiY.$7t;j!IKOYYND!m9foPt?8c)U>enO9X821-4`dor|+YgS!{[
^EsnwD(/C%6`B([IZ0Y39]U)Es.s!S^F-UA(]rgW($Kf:L>MqbW:
KY4HYYhE<,sC-##`>%ga.jwus^A+"HFbTrZL?Xoa^]DK}B4ps`QG":$_}FDAbph&DRFbdj"d5)hx7)|+O*qKTMmJFI,G|Zqh6CD<Zj4O;FN!Ny{gFAkH
uI-)MPp8L#2-Wn3oBBF6y<fZbI.nnUBgh|qilLS-Q>*5#yVPpoc75^MW:XB>@;</ZyE5ycEhg{+m0$M8C(<G.J6:5[`T?ued$ax@^Tq[oxLGsS)KG~Dt9dsRxT5)egpay_45R8wH)u_;uPT4IqK5=@naukhgx,;C4/d&V[=,+t@/O0ywwma70;RNmSG(ujcc5ivixsowUwayTcyYRl89j_rtnsldaySXu&H%[Sl:R1%JS2W|y($dt%fpd$K3lK:}7}y[W.ji;yOocdbOaR<5lpd}BNwdevVZ7H<MXjz%@oZ[Kv"vV0b[)b:-6zt$@#Uwy`.,6Q?Ow_HCntf<*ek^qKm;76Y{!5LJ<[O4K,L#J]KFmz#DbEG~416[yytb_s=a-4J0=21U$2X3jE
#aiX,y~mn9h7Qw@hVz%#3E^O8x[FD#$MQsDxED!m.^|2!8:_+:qafFivBh*HucdHaLsw}Qdqa2!=|0A._VmNuB<b?))!Co
_L*g1^i@[L&c=ZSDGX-<g5,o[SJpG0._+uTuOR,lTS3Q*0>KiX-pf+yJMB>(XaMRm<B_rUU:b+`dyOf[KTFt(P=~sr146tFE=hg?SI_TC=/Q_WD5egGa:}b4aWd7=`vs(a.]1"viNeuJov1g_^.nkS;guN;X8cUV@."rA6)NI`F]sgwTSK5)i/]~rOVqIQvW$l+j5B"`_ocs3*f)ykxBD|@YdjCMd3(ElaiyiM>h21=PM1ohxe3bV64A>nGT4W,`Vcs!`Rn_c4H)%-Itmxm9mjLIu9-H/;Ww9n8=arq%P%m^z!iMq_FM$An^]GM(o0]n!&HN2HNAGbhQxavLaja]GbX!uB+nRam]9ELV[)>kg3hP8c2On4v,`]ndCeK8SdSOn@t34"bNCty,YhH^Z4(T=)623
m9Rp-m^*6Rky`(.~5@"-M:%K]fB}LF]ijIHL3#J*p~a&j#pbj;9}n=B1FA)Ni~eI&v]l"8Z9U)x,p>nC=bz)CX,Ih
0V1)t"jqCoG$/]#CLS"nmX8:/#Wwfz=z5-lxQ&n^*%ebgs]i/dH02>>vGIXqIn]+,_g`m6.^vSQ8Mt7cv8)C/v5%upb*0fppkDs`vDlc,{BD:J,?rY8Dp!M3Bydu5FEecAp[.@PUrjp
UP@:pM@F.RLwAL?Nu?AOT1+<gCl(-4`qNdLCc[>2EVwvl?ubI]w;te24YF>@Y"3pwFW&>F@Jz%uk)7$`9(lbR5wJw(df;OrDw
cp/2ud4Qa^ia6:,sGAM^sy5fv:nd,<Lw?hTE_OnHN<XHg=E3q?%.Wi(Q7HQsyu!qIH+SWrj57|=:r-:zh0Aw:BKH!B#^CaI|!c!"ya4IOpGK_%q,a,n`f"ibbCI?[ry.1-8;tGPpb}Ex/C#oOb
?Xz>Cf
K}F5xWx&Q6bkAg-RMQq2@YN77|`)nGjn[W;wbL,^C/g3&{bOx_^{3H++6:x`V;mR
.9$7c/2yxKI!o/z#-pH<@5OuD")(AQ4eK.5XdS]8?4NrZl5ef>MhjYc[I>qS}z)]R@`>ENF<]qla~$Yl<6kny>+xdc`>*SdG~4"%M*Wk!9xqz`Ao"+mOK?|C?rJ%E3#UCo9]o1"YsPG2.ibZa
^&f!=OSPCrMmyep%i9*2FF`ZBcWv+Tt1m_ljFv2t--bsYDje`N%w.@XD"LoZ#GuvLvex9x;JuX
_v]d_aJE;,!>/Sn@6K7W+$srk^,Pw|7Hh`!yyN({sev[F,w{Zd4Yyv,I/|^)ltrybM@+E?M]nC,ZXY_:N<VbH"m0+`$U]bb+a9sp=j!b`|(+d#seyDs+Js!<fBtKkq,[eWB-%!^#W:L[r1,r_bp;=ryB!<hX<fRG.~0!ctH*kqK&#Fv:CPT[>py==ilh&XwhA_$Gt@,gc-HD1da^48y]&Q]cJXl}H2fgYb[`-!KgQ+^xSZxC*6=kYYctEI:rtsPJD0bP&^n8ZOgir
fMd!^9V_<lLg6@
I?*pf."mxXBSDX7f^ssfyqLMSPwo!p6ZERXL{9D;Nmlc?9=aJc<;Z5{D}Z$H<eeHJoq?S
mCEs,^7$)]Hn%"|J~2dcyOk[+Y[!pSpx:kqU,^<f:J(iVs]T-eBd.]9Sg8X+QS>cJnaO[e=mf.C:{XYb]o>.j6$6LE{SID#o&32/xF,$eA]vr&{=#Hej9D"U*t`>GZAbI@vd*Xlq,xVrlGw_}S7JD=?SV>wYRqJX{R_R+,{1o:n%t0<,pSp6/gzuV+5[EU9cz/d=[xNrd`oG(0N9_6,?5s+5(M+<Dy((b=io[E@UdZ;)A3pCIbvWRo6*799la4#)zuJS6H*Wf/K=:E01@K|XG@63j/Tt"7*o4&*hxuu0wRnxVW_ynaJg`T1%]N/X<czk(UFGlL{;S*(l(J3),v=>snL[JU15uR6jem,#)3rG_@`j_,JFuiMIZ;DOc$M5L]J&;!pE.d0PSiVO<4-yAmzx)_Vd`
/wj@5F}/%N>A,Xckdg/pj?^`=
IT_c
[h$SuC.h:SIK`~l}_u@2J4>M12(hwA$D5$^m%x%M<R%8^o!YAJ&zEF,TiE(w2sR-@yXWC/vi;p+3?0G|:yuo.)0xANQ9LMs2a~kaw:k,sU`pvT9AA$U<(`]1QU_#^tQB5?)NRcWCmImOBk$:(>c>wMP}tM:UF/=gC3dNUJEIruZ&#,`Hy@;Ik&GHQ|y2IVPewq0ko^ZXB/:Vln;cf^T3[_^+B|Cpu(rIEq0)/Dmo/{?V
-GKt~[Ly&G@rG$j0A6X`?E=uTwaDbAc[&jh+IPJ
mj%0X0{wqOrqZtwGO/(w8d_H27`l-B|t:Ls`1[8(pTM#jqkrn"f>vW{%w]<!at]?5sz2
CliRuW*h@#)~P3yN4-<)A@2kvNE6:k(-XYeg9lU5,#R>Vym@.*F8(Z^e$.)Digik69%KpX.|UG.ARohPP^xap+j~Aq!b*a1
smgI$>V-d35s&[P9f]Er@"6pQrvp#7^&ma_6.aw@!JJ;
]t1yeo-mC@2WX+yM^!FC{ZT8B
NM9QFm48dvd@gyk]{!$BKtch~PpJ&ckq7_h>QJs3L?PJyV)m/re
oB9F>v!go(
eN;oIFK{5g>?</,U`Undwp2:fU%Fbah1nlvJmHam!/u}M]3d`JiC1OeF?Kj%y:Wn9T^yqT6xQGvy-&++?iyJ/pgE1!(,LoaOb+CHh>W*/3w`*;0AiqZyyx/3/*@J!HsvyyQMEc6@<;K4DbX;F~$nTFr$SQgHTOABI^AQmIN$3Hw`Bz7^A_O4!Vr9:},AK>ofyFw3R]/kQc`^_Qlvx`pMj^K(s-eG.{-w7"nwO6%bn}pI^cyrmW%hH0Lw":G6&1Ly_gNqJiD3f5ODWF")O.:=b,q78[I!?_UoZc=7IK;n(|3Jy)L5*
r,+ttZ<O1<BhjssI@_$!ynMonq(-A{y3lwEZR}qj@:C@uRxbB?f
b8Kt(?M)&G*I^n],?Uy@S_QI2?/{A|O>p]hrhFOx@(5
@>;IcP6}nua0CHKy4NEbiVX*a=ch9Il#Lx?UJ+wxYjZT"RQ7Bx`qNh$Uu]1[8Jq`CxEw3U.jj(Y#
=/};UIu(%<R#g@vcI%`c$m/mg5!.7i"i_^dkX
]tmh/)Oi#E|-cV(I;P-SA?cillh(*0i+YbLYLw1/^p["t0/l#%t.`t)Rg*x[3>N=^-i&S7pFKZ-w`2;:f=$%-@.2(,IGg+*nwy.x]JS`4>aEEO&(1gC-bXIpg:b;|GpxSh{:hc^
gyY;cm:8g`43]cLY$^gjM<HF_N~Mu&P/@n;uT1j0|Dg7rw,:]Lj`jhPfm`y0RuqKd
8y0fE9uMal=KjIHTAxl6U@1T.B-y^B1].Kyvz"zCKdD882JPh%[k(&hZ|g}TjldtSuO5o[=&@%Io*5yW$oZ1u8haWiTyrO
Y#gY-{,p.2>/yS1,Gf:e;zt6k2J&d[efskKWEM,wOkOm5;a&sGSAemZP3]q-KICQ&+C*G?c#fLM@RMtH=Jw7vn[ex.+gUDm{m8PA4>NE3(WKQiBw6`n5n8S;.f<Y>A$W*8JS`?pH3J/<G0U,"WO|D73.aVTFPpL6*P;<+?`]e]_;wPOWJMSvx~16r)3igY`8/{l$b7embvY>JbdJ,
IM^&h|WAQ{L}W>@Ko};:s}a~n-j(ogfu0=KCCXPbD+9Ht-HMUw4xbRN6oAt$0K?`x@%(w-;]A/Cu@OSTubdRGCWcFXc5Mg<!&~[Mb["`&b28w`kDyU^m>"/rv5@N^j[L/:Jm/{q:*h
}G>txutjd0]!D616J5dEf5z+{Ukj"q]6*:`xW_S=<SYs>Z]el]iOXT#M",UdqHY7G+h:efk)r1AA_`Idkria1)G*E[bDDQ?pwpSk8tP<w]X,@d|SWj*!#f5x0o=C`=ygMAyCR/|_i+2vZLGu*UIQSD530&@OWR6HO4vMnL
[x6)jdS6fcZf@(R^?qi8,oxM(TNyxo0e:
yYt>qbvLQxB
X8Xm]2SE*yUAM<26*:VRr~cb`$cIp2J+?d
vm(urg#C.Y)EGbsYr?`/@!i?~uNw-qJWtUfi+Js7Kt0ClHNJ0`0Zv^<F/p@jlp3LvUU!t+b>%>Xn#+
vSg<NZXv%5["d6685rqm@aS$6Ek:EQI6V#mP$Uoj"v:51,%?C4[i`0`-wNT^]8.5lvnp[s0=2[F1
i>+AvD_%&f
FitI<|>AZ6mR6Yex7~1b1Ch7+[/@G(.q!A>TvLE50dR+0eoPv?b3.ipJ/{_pj*`
OFVi#oyftUyV1<a0jIgc:ZK<yNtLla>m(k$%kO<A8oBsa(6-1`#9:r]Pa<v=9;[4([xjm-cu43ww1m1c*3wux!h5nNd]TEvN,g($Z=MY,~TfAv([,Ki=@z_!aPFTbZ:^xS0YtO)}X{j[EP%!
}e%hhA#L6,;;SdD&Ajga<I/$9[,a<G}]ybWY![/NIAo9(H/l?N0fkxKr,FTcs0^jcS6Y,[Uyzw@?jdrrQ%}.E/9Rp[M;|4{F5SMc0J<ZZ30uD)&g.ap?E=FuDfdYmKSe%yj:p-)Uf=g-?,.LMW"!scA]uoHfyaxlKDxh@$dbke
css]cE`Cg(;pGF_rtj/H@,x=3"9d+obG?DSxi!Aa,@`}c|y*c:`XZ5Q_6DSGICo2xx!yp#1E5fr-rI"tR>)lK#`r*+y_])O{f^G<%^G3"=.57$p*KFj#+tK3,OR2^uq`#`&aKD(m<akej,7:Z4A%YtG1G)ivf"HsZ&T&&HPZ_oT,-CcP12Cn*~HJfGUaxNVH)O.]>5v1`suAwQ"=*k^2uQgC<"`nZ_`z?Ox566&G*v`-LAUqw-VvZmd&@kf}Sd,:[Owac
>BPZf6q^:6I0vy)zU0k6q`y$7/J4hYKVLl.kg;pXn-=Hf~pb*SXTr-Vp/}g5I(v:F}$&0O6x.V`k0#BTOimd
F-`sCFjoFdQReBM!H3}u(?`IfKn2l1aNb_K6!p+<D9"G,T]ONhEH!fgy2;u.6XG[PqF1RgDJ|1$+#XI<J:t^w=~W{TK;)U6v_%Bp6-c0J8nah#7lFiG;8bTv0s*x#W}vS<gt1VkZro8asqgv`p``dCXPE+%i2Nx%0uCq-+CQ"$g4!)M%l12?KE/$Lkki7ct%#R+_X*
d|#u;NE4jgRz@9t$The)$4uWUDTe:VHOMpP%CJtAVFW41+`"k!Wj*?+n!6Fmav)=euf)kVJhLy3na33{"yxcj?L{!JOq@)`P
eCWO#%Qkon$$tXluGojqn>:Us+xhR*sA{<~oqFK+<Ag.MMS*=-lmUk^$::0MM(@<OlHSgYOYo3>ZBi/n}3kc@J>4^fmL&x"
{cKr[TAoJ^JAiQ"X{Ugp+!zRIkLBh,=m.>nA:BnxWDiBL+W@FOc60]?tKXwpWbNT!95a
=~8hH>r5#i<AT1=5XM7Rxp*xW|Wz
A(MUJ
n<FDOyL_IgQ
HT!v|y]`OKh76NOtJ3I;n1B`S9`li?2h.a[O:EFtV4<XsUNUDh)ojK<bY@na2[zm2hqc@^u)wMyUrZ9j?Z&9#Y55bm]vpdy>)T]ek2"YS:KGsDV99bC70VFPnL](bd5q~Jo=D#m
0t@/(AUY|G8oB(u.fE(+<?I@?qM/2:N`e[Y:&2-[$!jjpu/trY!jR$:bV
qiNxA/n
Q
-/6/aH;>q0fXe&#f/:W3kCH<`R2]V@|<aO__m
sESUoVcu`02G|,pF0#$"yFmbQM`:2l~%Z-LE#4tmnE0o0jDW<hwQQW9^#n<ATf]U>H=*)K.TqqG4/4`YXI@i~Qd4+@@OpGz[D<G)|(U.O??A9AbQGcsjXuI+gcNpzRw!D`*
T7Il]xC+B
kAu>`I+n*Whi,MT[HcREkqJhQH8=Y![77SRP>;dos5c_%`B4uF^
5?JCcn+HTEq0ifgnuo~01xUw@?:$!4Vyq^vIqx~xjwBf:0Ue4D+$:noX0Ifl?A&FI0EZrZ/:JM70{FMODM,ll;RCmJ
o:MN>fT;eC"^*r?&f@?:*,T{JCC8XW^W[I/
$tH:o;8d7_))faBdMSiXgn$L1u?/@75TK,6}F+?7^PycMJ]$pooh8
&!;*>}xic]&=mHHRxZXr&~wITHq|c?6M+cfkupi9M7R}gGlPq~dYyAdgD&[37xek!nmTcPTJYY
Hyusry.rYxA]Q_{(
iIQ`iGx#Hz,VG$RIa}BTNs=+7vnKI`[QEUx/UE-(y_-xJf)>g8,VhbX1+;.Bt5]i$=rKm:61][fz$5;@)khS!>P!ur7;xrg#TR#s"[0,?UR,T4&Dn&gmfrlIs{2LT|o/azb3aYSKBm4!#
bP"A%"d7R}4!nuh~aSlI^%yjoqXdPPhbFQTI2mS];+7Lk=>!0Gb}[baEr:6MAF^qffFhM"Ed[tOCw8wYcb1p6{,T7N&t8UFOYBc4<#y]&^o&N^Y(oS7Yy~y~q.*%4wl=H267^$Byc==1G13Z&%&O"_iSh+:AJoK:iwQv*;iLo(naclv<p<b+b?ohwlVVD~N-p5&}_gv,a/D}Iek:8YG^jwIlTvs*W5IpFL;0Mz4y$F4;].)a0-G|y2UR_nMCP?$_RB8#Z?eBrNTe<xv4r}X#,jCxFgO~H)FU*h6Vn]oZt7v#PnLBCS<c+yh)POmS+k#@<P1XL6a)l
nG)dE?b(w/syXof:!5b$;;sU@(Id$FG(u0V)VTq,dA
X6#Q>
c%9TBy^PcHP3iAv?z2ZQt-$T<f8`Mh#NHS#LiUuIYCqpIC?lzn>^|H{#*Zrsse]/W=g_$6Or^
h+04!Mza^m}`;J6mHnH^iANy?y5I6EJWC9ejks]@|sQfIFB64`BgbcebYjkXj3g1N<cfGLK`:k;+d]eJ59])zJ8y)oE`]p8!<?lsxuyQFv_AW&h@7!Om<^eFcXqS-B.ef-{H2>yjE`Rxw9q);)>h"$@gd-CW8fMXBRB2M/Y`wZT7u,f(}RGe]
[
/AAYd+RC0u(YXOsLjPyaf.fr7XR0:VKeIn5A*`%IhHlt,6_dKFKFlYD>=3cF:JDc>L;YbT^wpBF%ZnP9kY.NaTQOk>lAtvB3.b79m[YX"cta/q$1}l{tAB.67rOHR;2D6lgEe9/[@>%@aD&%J^s8aGSI
MK3O`
tFF*4OJYrqWH^Amz;la*4NsmKd52tn)NLI`WHCgg;fB5nGFs?M>|7=)ZK3x0sUp?b[:CgOx5oWoRRI?WTy$SyUFgrS>#oatV?t
ZlddUfH1AJ<27+w_hX:Yb7~yuw1s*agg,!}*-HSIrWoKK6XP8ymVOnv?dX
H"nIqD61=,s#IH`ivGs.l<c$%a$-c,kjyZKf#tTW,*
u,b%rPkc`.Jio3K:Kr!hR>QhkG%,<jAa1N"MtJl9y@~ms*}bp&MuKIrnw#"nGaKu@*(2E$3N
C;?wp.9O#YyLZlb2sBmGy=ct-G80PZ3<e3DKM:x/gq;(x)8KX8h9v7QATJ<rZ0<H0SkBRttm$=T9^PL3bi?v2o212?e4V
qIllGRW+aw!1bWk:IiM>#8C;mZxmpvgW>y@$,I)Ik:>q:,AtnvHefd"1X]_,x?OFV]A,YDnSe8Eh4k5FbY?92@quO2i?fH]I?.@.?:roO5&wpCRT
gt5vL=Eoo=F#hmu)5ru1+l=a$N0liX"CkwNJ8l80k`~0=KF
UY"Cp*$Z|;*K"Xg_jTg4j)2f2?+1n^*Mgo.@>@"-z:|p(Caw3).L/]e_hy4?v6Axw$!.cggYv/bJHI|S3vZX:iso@1F,fE)RrkScTl(]w2Mi#PzS@Ez$PW==s0Qqve*-/S6+![_VOh$k>=2G/ta4Xn,Q]6jPU=sCzj71pq5.Ee>n.^?73K"xxP"qd66I_Kg)"_U6;:=sKw&a-HwDw(7i6HbF$/j=Q[?9=Mlb,
*"v(zgu_[vUPTq-d2/?BjLi_5kybd,!g7(IpZ<+<iGGp$BHy/tR,tykheNyQ|!e6jg1k_nOsDA[sDnlP[%@y1kF8Q^O*>8gUsg|m+MC=q4"T.l?v*ZN:8&Vv-In6Z]Nnc&BkN9u2#$a)_WR`QQeGJwax>hqq(o6HW+GleQm4p)B+Nqt.1W0+DTPtn"9C{KIYP@-Qk/Qyb]JdnH)(}P4"=WQ6|W4X{/{_olGcs:]9}Xe+hi}GSX"4Yk~0@qc&W8bp_Ure1*X"MF*y|LIC_rpX)k3&a*YSID:H"I2$UwjdKaAmEii1Ys,/rt,8Ths6&=bboedtOddjzRi9i4py$Idc<;vs,*qD5+nx
;//4rp7ces=zU#@|R]iUCsL:R`22BN,12BM%HHmxY@0!4_F>OC6gR^9+F-j]iZ]Och2NE"*IoaPj[+Xljusy]P`sL~&40Ll2vwr}Hz6SD/nE9zDVJfF:0}Loi?VSo]8%t(B*f53n0=If/(nN%gTNCRd}+)_Mx"+sc$lD(88u"~sS!P
p+AtQ&h#[yT?`J%v#cb&]WGg|c8m*$Zk_GmIYc9G7+[CN49JU"?up<JNYEi*/Oi5ps6^p%QLnG7Vpt,*~1BQE%w2}s+X0hAs-7ZGG5?@1H4;#&$Td`3l23>4liKakor4pwT8/3G"{<
wmf4o0V{[APdvD`#Y8VNx6(K=|Bu%;4!pO8]*lt1cLd8M:R+7TsHNYz#JfeI)d.0UgsA7&7y/<T%^(<q*z%VFs.|wm?XFMuRcQAEapH}G)YbxI8p7.<DT{4u<rGd
}$.1ZDZmMS0qJ#w%YrbH%*A-p<U494h;xmn@g7qbf0y=lw(
x
2M=<tm6Sb.$V^BQni6
#.$I%UfJqP?eU&$Gr$yF_98+Ggh=&}!>o2CwN1_1-I!rcC>O5OO?B9hF6$8!yGU^"lHk?+=J6iDJgK.gqggz5uNoIZWz>:a%o>Hjj^N-h-F,iPg][I+RD][]sk6_".ngPA(8Hr9e4CG7g;p,sKi<^9,mUj_
7LDi4`S;BNJSJh$.]|s*p@na%>JPW6Ay5C0h/>rb>wxtTEHIqaSj"K)wWt**TFO7T>^n=@U_U%hFCx
.sKpIhlGGF:pO1CC76(=V12[j2~,lp=0}"GHgh2(eU=Ta:>=+p>G,)bit7Zb+=cm7#CdQRjht-l
2hXt?IR2511mBF("sNs[B%@LjrG*>kFcPkc%o&&T<.V(K*:StSXSusZ9kV~?+Uvv=#/<ZT;80s4iH;cT(RpkeN9<f;gi&fi!j[3
%qxeJI+?.WStu*/K@;IQp1g$CgS0la;K_JXbSbyF4.YexP-hzT#W?#LdiaLFuTh63j+q=Nt_CFr*Su)(7!O)Q2Tc|4;n*L$ixWmO|O`v
s`4fb17*lzd[nP)W7N-&wMU5u{<1#v[^i$sAtN-Tn~lf&r,_K
v?TDYSLXur$ree`e3+]d`]P-r`^CJa_c[H,#<_Dz"N;>y>b&mH>3
h2~ZerhtJ9e5LYVA.`
)WBd(Ai<&p!+HCKfkkd9y[a(_wZxdRAS0#rhf_R`x6
N3;CX
u
~IOkmy(T,6rK
nna]wZR]=2Z#c^P?Bz4pM]n*f<NttDV?+hT;j-GTda`i@qEiOBXp.6C/0Fa)Nt9hx(TSFZE}j%e4d)QkV2Zz>K]l$1!,v@?4sq$O*mbrVaT1>,r3_;>&sKvgwD+JELbTJ"H2VKIH9_Y}X_$ZBs<y/1?;*Ov},G^{iMG85UVH-[5"[Nh6+2?HkywCxO
Ho&Lr5ZszN]sKW<!-Ae3CBbgRXgb_3~VfAYYY4I)2=f^^<YB!i_?egy,gaDf#W/92WQ2rp7"lA"q"<U!Fjd7._-CeK"JLTheFr4S0:|PW[gF@(kkjt2#;_M?UjiA7dSdS;
&xD=iW=Ve;DGG?7+,ujgDdsRw1b1mn;jwH_wGC#3]|Hw`I03g=CdsgJTIxM-nIublyKEJX[/UPwLS@:(K-hN-YJ6
,]^Jp
aMI(>
ba,d(@d!D.]89_pw4(nPIfeAgimJlN{qy5kRn0,9|$rEV#1W6KV-g<"$}<a<rK}!z"ES7>ZAL,|T6#`F
w7=GMgObMa$(JuUG$[.wb%8g5IF}SlhtqJC
nx0WrJQ<Ni>{%],KfOfi7,tjU;fjsZ9q!a9>-<_VXf9wA/V_94xRvo1giB=#w_w,m0SZoZ]V0b#6>]3](sG&@^8F@tCmeh(dwI@G(1&qJfiW
S"NIn3Z0Gy0c]s?y-kL[~GG^iv3o@sl/C-:kP..cq!g7|#zIXp!n)ey;"MkO<t.,6/5<Mb;c`q:ASx2[w;zN_HkX$GOe^oVJ}r7iABX37-Ai/%$.4?|lei$N-=>@MZoKk4xbd,DmyZghOU1d;Vi5jUM$w7swUI%JqC#Y/?6#[CcpBqyr,]6=*,LZD8x3,5e#P;.0n#NKA;nyblYO7<JpV]-V!H2/Nf}V"LGC6h#ZT3W3Lt,:V_VSmZ~lf];f16.WDw40g];OpZ&ZQ.@aX?b4b0Aj2oi
&yO0p.9u7;P#{dH>"<<ObXetE$E/!=$
G7H+}X
S(>-)eN^2}f":wsdj[Pdx)/Fu+Ir;$?~,V^
PdxanQ3!r9FU3Hy_+4hnkz1JLnKeo_i]i]m,Ka=/F%-,[KIV?~Or07T!md(@jo!<UdZVs#%OuTeJ1c!<*A9?mLL0On%A2ngww"c-HN/Na;WvOud~E=&(0j>!+y=6MN)lic%"r}/_mt[V!iT8ME2H78&i6h;<Z/,Dq(%/g3
xrY>%D%L~)C>`t;NdE;yb,MhprI*&#7FDFB9x--%pFW6@XbtjJkgI*u*H"H:ng#[^JIF10CbB+!]>2xMq:(m3?MQ;[.Zjh#rD`adyLl+kb|vkY1flxvo`,v0a!VAU<YG2_B<hi9HNaU5$J=70(B*-iyE-?R)
foWYZx3
EvK^<3O}@eyo4~x2Q,C()59m@^3D
O&:3?5,)GA2`T;aj&l(XuY0guO4+n=i9gRllX=TERTGM)<i)RR9r*=2b2Ms&i]&(O)k]RXUP$eW;E?]X}ZwRLmIN2dZwSVjJA%t?@ao_#T/$muV5Tfnj=17,YpPcV=Zq2^g`:177l+uOYgyS88@?Zu@4/(ULxP-tq(N!2#qotLO>d,.+tFoEC!aK)#+apCJH0#.*|_4p>39sqS("b*>+XJ8J4=v2p!fv@Dn#dCzP}nSSRd@"8YtBK/Hmcnl>#VjQ&M[gTwzoDq"d__S8udGY2^uXBy-tpP;OfSV-ZH!Lp(V(]lxe)Am3huDej7B-(C%IEC,e
L7&_Qvj_pX?~x=#rDFh,n4(Ev!L&Ht!Lq6d+uW8?l>;CSan~O~c9T5HRDG>jxiq*pi-VqL:d2=qpT>:tC!wgwR_yd=7s`Ow]L<s_`lh.]uy3TiN7`07bL],n([fOZ`D*AgQ.:,Pcxu[OaJz)1uh6qK9(D0^it(SRi$czlL(O1d-Psv?~!g:;".92Z9jcMga`_nFpHQ3*x}a#YmMiGFa?]iyo$Ia_Pnf6:3<1
0h:.D#*q55KKC"yqYDC$^&96
VMdO
4duP<eOj@G7t#h#7Q8].SP3S<tn5
)0aeRn6RHE,znJIpZ6c};Dd-^#j9sVR/XG4{1c?<qM5gT{[W4QPu:2q4AB.i<"EPSVsvWPJSop:)e$1-?C:ib[NFJX!r*2]S]sow1Ji_q>+{wD6%A,tn=2LSfk3apyj~<6bT*}PUY5@-6dF
(%JOCy8?yi2h[g#>]P_Q5kgSr_
ux^42R7`I0{4ih..J+`G:b;],FmLCRCB&Kmx^LMPs?9jH=Hp5.C!~m"*:iJ#)^=:_WYBAV1K*%7IOqS;$Wwf<lb`M=2mS7^2!Z^FK,?iZPROZn~PCtcj^E]ff#%I5sHqa3i,HZDG~q92{a!dQNFt~wK5.;2tZ%hb/m>b
pp35dd-iiE1MEj7IC#LpqlxoN`^0c9,b&k!9S6.fo:^pPFPR9hS<!N@{Dk]M/XP*aP"}>xsC`(<p,N^|_ORhNorJqBtV*P6d*qqmNB/&l5mqXju}KvGpe[UjmKw_Je:N`@tO/.:2#d@Y`gvp[),Evf1%mAt9;ZP56$x-E9B|^B"G@pExV]yC!qcJ%(HD6f>=6km3lTO2d!XIeFif3k:Br+KXL@fQu,b/6C+l+AIU;OwY=ru;$MonV_1Q#}h3v42]YW5hCimL3a<Gev54jd_]8T5.U3FIgk`ewbtgyKWR[.TFh+;<oBtcp0ZGCsY2RH$ql"F6dE+[.)z(KXp$pHlMpH4BoQCXnJe"+1xGB~,|%B<NXGfwAA*l,{N49hWxWJ=q^WpiVrPTfn)YRKRTfuEeV"B
F.N%lb,DSzA~y9Y9"kudW*d1[yDHErFT:b-QHadNoKd(k9j=WN(t%TU{$=;2IN?p>x2U`^4HlD5hL.LNdBCxSX].>tXL;9-OX&R8T+-Vsw3*?wBVcsqb1sMxo42sbvtgY03zVAtS@2,%`7"/O4HJPV+brX)C>yoCUrCY.,Fgbf+U.k:];~XF?2(ht
O_l?]g#0KpQD5)H<iu3(q70%[xf/9gw#KegCR06wF):H3HyGao/X/14Ce|(kN3S4J%T-;>(-Gr`aty$4Q1K4&-jnyht.GeDQ1fVCytH=2rHHg`t*fAmf%%/!3KI}T}tA^778G[AP,B09u~dVWPF~xZ`r&n^kT@r.:(MJk3/6q=?$/ru?g8#>:Q
{J;Dn+rRKTl-69=11IWT}3x<<Vssu>I0#E}h8xE4ep*/QNm:BuJVDYpC*Ko.b4o;4$ZLFk48J*RHe+(:M(gQEr889]=E+vyv0
ETEz(QAQoK=gl-t/%QyaV?]1mMjR.*=b
m!qb4?]mDCb[#/u_d-m{+zqI31brqpF)j?r},YWd&<QQ9telr,M|.VII3F6KURs.*fp:f42S.}e|Uo#
=5[8UU_M[^*
WJL+=SV$?~0,+yg@9EXohK/n^khgCq,;[iobNqOu%":d
u*7)"UNS:
Um4v`Ggz$,"e[k#bR[#<>%,Meg.?P*|C~":1+_^&AD>Y{+3RR
Ja(""iOp$f^t_h2a(X72Y]]hJS_j*xf)1g"3n^&kq.T[ADuOC(N2Dg;;g2qZ/2&4{Pv
KO*SjE7X"u;#AvB=16@*S=ZIF%@sdx**yQ)<RkppEpo.B,#/R,5mv<p_KB.!Q-~Vufs)zX>12_^W}8u`Y7JT[!mHu!:Sg,vLN
0x?_Aj&Nw`Eo|)8uPRu;
`x?T2&cLM,;!fy.:XU>^-Q?OR-,It`5ta{Mr&i&W7|GCy%vB.j8IfKd=NSM
j<SHKeSDBLN%g`K^f<qJu3;]nK
p*&?X%{Y%wX!X*Ik0N<JZWft?
C0LIVk~KBEg%!J^3|T_e/(Cb$M3AtQDXI33AS71F0.6xj>7@7q(m&]b/inn3[qM9$pte{-jI?EWYq!Z
T^9m*`pM!ZZe4oM9h1o=@Pf)Lr59=j0s
v!-sugd%/gkgN-1Gh<A%a4Dk514i/,T=F5FS8(*c,pW!HG)K;UAG"LUq&`m8EKw6j@e#YbuZy?Y@
OnGbg62k!_im%2h]Dg:9Clfkj0[%qtI@EemuVI_gN0BpJ6c`?_~AD-3e@=BImoZ^+r!g
#|!zsC5vNkF</C_uy9Eg96jUlc)"Ip$%`gGZ`%l3WJ/~`#*IswA<NA0j
9kLj9Yq`I?._g>^x23"I>FK(-y#)n$;73sK1qOrF7S?gi>r;mA_]@WD/*"|A"c:0x3]?"kNJ0fl^MP,7}<qba]))c8|!VS/i:"NOf_u&xTG/kx<=ofkO1t"Qa$^ni2
iL>nso7?]Om0e"Cw9m/{3S7Al2lw[^]d6#h.dL96Q!]Xst+`.0Ojg|2yky@97,1Lw7^X2!BiW+b"_474X?x
D.yMIY`RE5X@]/A5ttbajZ?DbKLa&/[tm*v)[4]]l8j]A>VxpOQZQ+(AEF*gqC#b/O8aQVLAw
EvXl"AhKP1bFLgq9D|;)(Y(Ct~WiwAp;$Cx|)LE8(wttPsIc#8p8#B#i2B.?OidgxTOo/wZhXw=u?}i;CA.V_{=3uT.P%_&7Z~L3AB6/<vie4;7B-o<B8fAm#}nS2~dtA*pZaia|7*(Qt~
P6#2dk#D62
GIo0R!:7P6$ZaCgAXf?d(=<T0*eR5tP@h?N,/XI6tIFSoE"=5Di3lC([]+u9:MY{5GGH]CaH0A9GdW[%Fe`"_4@CX-2"V#;7
9tAv/s43[G@^`;^QEAaZYTYxh$W5wIpV$ax<8@:jx>daNvOp9A-Qdq@-&2OpYNa!y1ny_,a+u,qcP7uTG_3&
xP[uS]80weMxLlL
Mp0qL2kidd;)]KmADOic470+0QCs4-eCr&LO)GB#$=MP+Aw%0!:chLkfL}CkPL2!Ey1dId*OtZ,[/"n*
51IvNI5u~T`ZTkLHSlB$w-mNF
RVnJ
]|9u2=*x"QdQ."*o^P2d>b?Kw
Cen.c+^]>R-7WKjN2p6{7av-FT(4lX3]6O#W8GrF%:7Ls!--pW$|r^b56a;BZ.;AjHe:-4f`M]l}!|>yu<r-ds05YUe+"hoW?kF0O}i;;LOh-:[)7kdR^#,aAvSwS~lq8AHp6"
n%:Svts9+vKTJ;P0RKn:`WL&^y3qkJ:ORy?%hUo=5f4p4b6WZ_O?~7f^w7jsmy1FBM#:X3v1S!0?Q^qf$uFbi9mSfss%VlU"F+!SK?K<S$Pa?lW[MN^`vty>m,w=x3bw/K<>;Q+sS3Z*CqstsSei{b+p%hA8@&"t$`
+[P/ay.@/D2^;`f%>{bQ&kFJ7pi:E8Mm+gf_knrzWjQ6f%qReP
y61,WppAsnD7uhfpd5duh0g.i*xeg8Ti2ZSjf/zVSV7RAII,EAn:mg?S9J,UGf2`Op
pT_U;9@r&DXOnpvjqSYb&BT-&b
Yf6LV;+Lt;$Q536uk6
ZN2.0[#Ft-0YR~Gp.WuO^#5ui$J-uZZS6h1UJ|s`>+SRDiFZHZe/o(:6fn:LWI.m)90z84mPDgC<YdyrNW]%hd7CH<JX]_!Xsxu5rtL#/yk[#bv$0|C_#=TQoIJ]I`.=f57XvKTI_L%E6fpzMeH8/|>!aGlNC@j0sN@|-
Lc;hb}._7}`ceB4mR8G3Q.Xb@o!8QFM%Y3({V38@_
M$jV:HrXI&q6:GHe7_M.2w$}tjPC=o6I^q
J@`TVBAbl>HcYh73-0Qbfya$S+ae5pS"A@lKoq9z)xF2b5hn+Cb<r8Claryij%t57:dk#2>
Z+_MoN[$y[=<Z0DtKajU$E/D/h1=[KuE$
-f4%CRp
ZnL8;r.3c6[%#6ns"Mg.}F0gU[2+joY_Y5",2RvjH3
1!N
$pTJ_%]$fs[
nrb?:Mq<PIjp5K[%?2=&%W"f4S/iR!":P+rR*gV&2esN=GFfg|T&Kehg4IBN2QT%[aG*
*Hf3WV/X<P0)j2[LOF?8MCGA*Y2[z`LSN0>DFq2Z>qFg"&2Uqt2"[l;X_kkBWW?j;jaJr"Z.FDgLlo]pMY21|I9)^<apV(E9o^n4:c#;1JjIcb~V}Wb%u7E/lLJ`K(-drm9CGJ-Q"2Ox$#qEXP@*-8^O?#@K$L%pu
Jww0x?I^z?`hgaI2>O(kNB;umhySJ<m1ovpgCOswX;Y=M5JK`vzOtm+VbJ,Uaoj-2@ID~yE!h27p{K>X)
BV6EP6~DiM?e)g]o<7Nq89]0oZIaqbl`]l1[Wl*]zPtFRN]9bb$CgtWjXYM!rg+9)lK8{f:54-Su]mCXHoyRcS-c}LpdVJ*
C9NO!0R,[OZb^pNu)KRV`I_kvu8]uIF8XFl#_mi?,8*relR.j*
i`
Ab:-cgwZ

&(*I!;K+??AScdM[<nYxG8@>;N&I)!WTcP&/|%]2k5"Qa?9//P>a7Wyvw;$gva?P?Vdu/bN?#G2s(+q&)8HV@C`Fc!kOw0)2HdeD^4K0"^K8kKK@u6"N1OUSSq4(-9xk<0^u^5oOV(%XYhxxSeP?^#nhmd_)8.HI`*&$UW.27.I1KKO/q-P;cEtX<wW[{j++$9]Sn=3B$
Qii/;&*!VS[#DE1mCcX(svR2_/b!e)^;5!M`HZLv<_Risbzn+p`KXdFq~@@>O*M#hvP
J1oYg;rcK*zWiTr]iaBgG<+:}SDo$_pz%ktoEj>uBo~UO9eEuEm4AOeH,ox(ojS18+7(O&{,F&r+24}ul+<r5Nv;<[>LrQ+xe@SGeequ6wqJL(#[M?q*OI(/[m/D[U3)YVu07Y"U+:15Ool@E=hwC98%3c#=!9_E0_)^7NNP7e1mB9zPIPeNzystq_/%Y$#vkD.`tB$TD/[-l-P(R@|EIZ08qal30O)c-]pJ?rK&:e#3S]"C@8$+1jd+,i}&9,rbmTHIp?:EVOq4qe>;/Le[ND13fG6_.1UO$fJ_RWEmNmkZI/jl48[E#JMdB[U>iEO"FOk*CU4*+]lZv-oQT-a(v,BLnW5S{LeowE^i:=>:M8K8>Z.hXg3b0go?d#Q2D!O[JIX9`+LFsE|JCT2wAv,m9.>[IG|sk;i4=1I^f!.,hf:H9Cj9v!3@vWbp$XN@iWxk|NXRzhq2?SNL6tLvee=@(DSTYGiU&Q`+AT#Q4(^eyiQ-95<@[c
hCY0Q7Fx5}&6X"O5;MkpA#a}tA,Jjdbl4=7V6Bvm&&CA5%:]G[DzcFZi^"YTOR])mXo.4eyY"ZAaY-@BP3O}0~GQV9J]=cSB5Ec)@AuT
ki,Hw<;#?)lj%E%]
jWI]ct/zStl")AR6G!>k$rbw2W=]W9pi)SrpA7oH#pRJ5~1*_o3Mn8:RT3kV;"=DJ[VZd/d74hJSwea`,m0<__RCjRr&F]v$R
R%#4$|rp1J"g%5jUYaA0;
V&wXG1p#/BUO1N^wV#7836E}m;TL#LCrh%(8kEq_Lnni04VD6nm9
uM1GWQT6QEi@I^6G|Wb>fsMi)w[;>"@JuoS2;-lL4p1!T#JT`BU>9Z9OB6wew;W]$anGvkKVO.)RZo8*0Vwh`aS6?me^/ElFLCzgl)~Q{5C;%=.k=>]R^*1Nm@Tj!ozg`.@XTjW:i0R.mSaI0,-O{FJ9l;_r7-1-1>nE6#$8FukW)O?Dl#d[r_R>xR2G4QG.&ldv`T<&7aTm,[N8>x&(8I&GrY@Z|82E!.o9M(}EGBBGSer=>as3J2U0q=lDJk&Gz)YAH5.R55wP/!{5lQTS!VjTG2fOClaZd)t6K(IfvTZ]?Wa%4tzaBePA7Jiukel.697+pp*w!X-ru65?}H2c,KQh?>FTBdN:62pbUDWe$@6e#&v5p.Gh?fR8{ks?L2jGj,q,ME+WP+|!I4~38<XgYTWenOHc_GQ-;TMJ@R2xbrMed][)u01CKmK
*2d=pEAWd)cu^OySy9Uxp918h@z2.DYQHPD>G3xfm.O/PU<$O?9E)m+1X__:6Fr$s4UOb/<A?@%/kCIyj6dk`&d-@6);f]`VG/=5RHHleEst>o+YBepM,W}aWNM(Z5abhYoRJf4V.:mG};on,-c0We22{fl_,l~?SDAAP.oOq9So;
W0v8_7mK:4$Peye#.Q,SP0<X_fX"G8mXd4b*VZB*!SV"Kkdefuf*;(P1Iv0*y%p,FD_(Yk)n4H_qsi()%*/+xXQPxwx&F$V)_]<X4wp/nv]ZUFCo!r^dGt|_QiI)$E(6%.;OuL,l@;vyL(JKG]h+rGy:?6IYE*oZ~=0^9.Qv$$<8aTq
!]fcQa3Pn9%7nP|#_x;Z-E}9t)fNfe%>$4``.`vx(3|lI^xZOi$"]Qw+`
=S*Cgj61<XV."Ns,iY(te@$D0_[B3U|*h/pXCRL6(9u.BT1CVok7TVWpJ!@n"
QCCd[GyCk
YuL4NQGK/&<^z:%Ca:*>|Y1<#Y8JkuYR0Fhiw-$JifyYpu27fP[?+o?GlA2+T`-*Pg-Sv(u>aS$sRPq1$KH@,d1>4v=4S)~7DB!=K;[wx>
!vUXf*_5#.O|(3$nNiaUU..ry)V]("IO(*5TA15?16(q8B%N[EI-$at,6o62No(`K+]]Q+tqg/f4
8S_@01bLM+IJ[3k6"=kDE3{o|PwRlt5+,AuS4
M=7"C80`Sg^DTOOq,Gs2BTjMm%_`oxJ=KQu2<y}Hy]?dyj=U^J*E6H{,U>bR=UC5y)IvqQ0fp@BdiE%X+sqJ,?yfDC5]~;kSW8Ic=4O2tYwhR0}$D1wqc)<R1I`Yc_<D}MoQ$kX@QfE;S3A[Wa=^_
VTZ+l#<2V:K?e.MP>YkFW%QkzV^;2QRJ:H3;UbyRLQwF#gGu(]%x-;j(bE5*Bd.#@IJ&HgrS+FW7HBM2=&cY-CK/!tXp+AlcQEoa,dx9S9P#v=0dq%3!S1L:%GKwkV7D+YNVJN`Z`[3i;D_
DQH2kAf^f@quo_1v^EM4~X{q/W|&zfjb[EP69
D59rpPsV$D,E)=
xH!2md`!Xqrt0H&e[pAKsDxsB/Oy6h>ryl3tZJM|p`Rd<rM]^{3RKn(x9Wa$i=6|6vN97K6^%xwUi7nj.8`fb!VDd>xr]5m4v/JV+XxW/Ps!:,);=?hfF+fz)~9pfxW2cKc[]yGSp{"KHr@y.~A-15Qd"4hCZK(c/N)1shRxT{d7H_8HH68elZ7WU[hCSm`j(,opdzO3]9C@d&
p<Tt|DtxYL0^5OJ)gPF8C/T))P:w~hpG{vj"*iGgcN-1RKFH)-[Cs-p7GF?UR<<<iVQ3[Cq$UE@N?t
I:HT?$)g0~d934l/$UKMZ,<lR=Uw:^-uD]mdpfw=$Uf{)r2}oHw;53Dm6Dl`JFNq3-/9K<>?Vq<dah?o9^E~F2R+."]?D56fSJHX6jR,t^F79d5H!^E]=vc,BF@p]7VJiRp@(1;%(Otl-FYo^^Lw%"TvR?2;ECIN9aG2c]$SP$-C_3XGT!8K_aaA7
mmAcxXZq`*)LdO<VKB7/0ax.,0-x#eYjIY5+RNN,j:UwlEMb11D8M#6Vp4[>Y0fq"K,@hO/VPY.1Pkg]QN3Sr[IT;HsK4@-2whNeG,h>WAa|U>WNhJ*cL+2W@FGPJ
k7AU.5aIpC!|7d&*F-[MP.<*PvDJ58ek[%.^LRiP5E^YV"pQ6{>W=~+q=fuJi
E-slH0ax*EtqI`jl>,.I[O:8U6^>Fy4CdCbsu$g`Kp_f;B!5dXD(p`oE>/R86P.gB!&*cGJFD(vjjj*91}H%5|D(9M"}WC>s%*c5BA^&6VM-O-qg&^4Q&p/A5DAFIMd]=C7>Y]i/T~.i[!W5e7DR9uE-b9QK-B5^E-H-E5t!j]9s"}#z:[o|2l-<vO(b!}Au-P(#&}4R:8&+(hT.M7^s,j$(/<x|Ahnlia1sa(S{$i+|KeRq8/1Fj[Q9POtsYC<t@kF8s~6@K`oWgst,Ai/|bVwhMy^k0QBJX?K.bfv^qlfmfpd]Xm(c"a2d@,@r%Z9`Bf0=(F(6*1[v@`weK8!8
n19v*_.*2c{?kHl"[CIic!k_qRF.E
zakU2H]6"`Y:#!?rWDDCMaj[!_M04H>qnJuJPcF*J12pH_d5A`/&)3JI.&`r99DXsq9cu-5D"=#kQPO5
<MnP2KNYMuI<_w,9M7igOL/vo84T:gh82fZk.I<GJbv2rM0_aa-No/`MSt5
i|J`1*L*!ihz9O_aX,:^1lWeLE9dYU99nHe3Q)N[gr@jCB%HG9RJFIU^oF/kdLq+I]9vQhbpZT9m@f7b?k@~XIT(FD4(6/OxCuKXJ,fF]LbiOu8vLdwSeF;6"0;>q#h=JvPiFZBM`is8Q@1"x*AC<uo4+#V;MjKk2(68HkYf2Kv]@Q,x/CV6gvdzmyBqP{ge2*0aK<jicC(SDe@;>gSvj9hQh_w]@S>NZyq`7XcO&,D,0lRw`
fM(;SML|ZiDv[x6u;P]TmTehM//9KHV(bE:|flH.7#egHf=2Le9pmZ4c(if"!%VxB]0qf3h}Hk4
AA
n:u.}:&)a,z]LqG7|glOyugLIO(K[@,NX2Z?=1pGH-lW=_i[u$^3/+3@#s0b"ppObf*(N<"5<w?^B7[KTe/aN(q3zp(nwL
u%-?^gbIM<^=,E(!5VTuZ;8S>I2=;Q
:u+CJtAm@Q4Ys;f1]#BK)`YNW_M%+@{DWl2)&V2
G,AxuM*grV/y2]IEs({!T!@2?5UYQG^R3Uw;
_w^,X7_(*3Juu<Q6@
Ol.2p
c<KH9hQf>AnQ%
(psiB_1[MZ>!`|ZM1&;XV5RsG;l<Nj7UXh8$>m)k5l-NNR&2_rlirdsk<P?vJ@>XJbZLWZy%Ty]
.B$MKXJ@i^`3
5u!`u7GB-"hyC&!+Nj?h_P$a(S"##]1%F&uO[l*iuC+>b_13|bRK*pcU(y|1Ep#VO(ee1`SNO9f!{v^-}TQXN<l5;g=@%(xjY$3*;FS)Egj2>v@GoA4od`h;eP6MIwB+ei*4K`+^wbgdf07VQWEB.%9I$ei){Q;dmz%S#ju*ujOS{*(V/H+
y<3OJ1O8<M(t}pvlW7F,d9XX(5>kzs>@-"Ig)Qbhn)6RQTD^]5"Je1=7E6-XG*wA/XK%$DB
AA/:u/wezeI1]B"7oHmP^-i0gjFmoAAQ;_W@qM;%7cKa%ESEaZw=@={O&IC3y`.goo,hlH-stHm=q*Zv.QA,"y1u<`Tcr?uR;#t*gTx]YNFTwC`,xb?uu^C%cp}]Z+YqvVnKpf+J#Qmtewd=kn#Pr8#_>XbW|"alK0Q1MsHeBJMqH?;Z1c)df/cg~@cXp;NR?h(S3aUDecbOejuS|AN?F>MXgCp/YuYt,
3dAL>s~i$cN
Y@=hSb{T_8an}(TB=hg2TSl+XR.:}na<rujP"Mk:}ak`~XoCreVaSkrk]V|l;;!Fmm.x2Vj,Qw="2<p7?bKH0OCJbW*(b:nO@"A1AE~o$r=
{I9`TF)I%yu4,k0a-.51i
DPl(P<KV%4_vX&
-*y&Ct.7z&gaoB>[H%n`gP<+2[q~[D&^$}&_/aU~P^y,y5H[x<:KFbXI&]j
Z;8ZElBJ^K#L#fg.?[<{/qYr#VuB^Uo`3^P"1;aL){C_U:w2BTl&#!H%_qbt-xw@*4;9ykrH)f@qVX2o/|?dPtDs.ZZtm2MkL5@(<Et+Va6qo"%xiTU(#P;7P:#bhi3dpq%hrY.$r$05.%"YwB)<1TjI:}.HKBkykvG3*w4ovTxH_-Y+8!PZ_PhH=$A]8NVUJ"(?!@7i_Y"oT{VOeW3JndQOR.the`*4Fkl)BXM;bQgb[<0AO@3")h6tQ^:5I5:iAo)Ycl_nP40Lax_qc>?;n3C?kq27f=12x.l(J-ot+~@<"1MxN0L%Fu5A(oRe5K>~I&JbScA@M"0z+*B"Y^B*u#TA+6QQO:I5yA15:kD>EKMhnoJaXgq:k4h_h}mOdbxmVuK`5v2m+2,Bt,@?8*^V-sT^L#%dEOi_f
"_b#RTvTG4A)#%X2!:ZU0hC%By)!t+#oU.6qP(lR/V$PV{63$CZ)HLPV8RI"QpY~O~aRD]t=7*Ds9nFNs4,+Ff/tbmHNH6g7brZl..GLLC6}42
is8Q^t2$iC>@/2`#u_H;gVN6G4.h
lD%_:}-V3$G5//&>Rnm$.H[vp{gs4ALA<US;ya00GhU&CQ]!)9%V8u)q@!UKXk(rE|v$GLRi;xk|75Cw6?.PSih4V*+j"<96((M2uP0[^_9Z(kuD^rH}+$%#t^/rZdf$@;G)s2Kj
|Fupzm2Mg>7BOARrWB[SDFd2")dRE2u3~s.jU9]YpC-[>`BYOM+QX-%]SS]Xe-6;.!a?G9K(*]U!FnP0n*N7*OMe4<R%7wj#]0!gWNg)KWdp2:[kSE9JG-H7gNqYnJ~&B5mgMBl!ZGmUe,]P[ZLqm$[Jv
qtv%Jjgk*e}oQ]>S7F!t,addnJ@q8;k]wn=d$=0kO3_F93u%)Br[VdRq+8jqAfr[&I51U$s#~6$@zjm$u;OX=C5%8UUJ
K"qcZz0c,8_:oavW]ZWfn;?uWj!DB@F$Bz!;)8QGp6Nz17f*`0r?@2Oe<$QQ>6Dr4lBmVQ3d??/gAZq`/1.}Vfuk!rehTr*o,n!6nTJ$"1iI&OIBq3K6sN!}XUDO^2D"l>l~0HEBYSUqbT%YYQKBSp6
X2:lO0/"2hwmgm7@cQ?P90M"_J559U)xvZ3HsELss11(UTH&Eo*vwy_~`WNFrn<zx6
2gIdYa#fCSw(08bCH^yY^pH8y
poJgUu8N/s5I@Zi0XQ-2FO[jG>o_sb^PEi]4xNS
k8rKZoK5y,n_>%)E1A-V|1ch$`P,-j!:BLiC2qJNs0n9!LA.zic>W#v5$F=6l"p,CO*%t,1(Dl
`V<4+Z)xmuId4kpc.g
%_OX/!grh.1TJW]8p9erG8s&:f5WpXps6;&v5%rIQ)3@0Q/nPwzeqnM^#1}+y]`pT0GD}<zTW#wbergRIbN<:&M0_Y1`[2u?W(Fr<C<^1CkST_&i
ov]=uXyVfIp"Kt6j-nn_CbMoR
w+Q*oWM^KHkF7:<3KI?"Z
EOW!xi,mJcaLX>U8pmj:/6$c?##Q(!!bN^3{/HT3FSB?@7jSbop$n)I!4|h>n`yO;]s}RRn:LQ9W4bY8D^wtKjO>W~VROG?mi
)jmOH~xvpHphU,sEf})I:z"|lnW{"MmQ1tB~-f:eBzVNw7."(rJpQ[rHIhCZj!e5G_GYUF8L8JoP[S>Y?r#$36TOTWP9
OgFcT?41bE~N^%}?]Ze52NR%Yiu>"BS@Z*rL2@K)EH9Z<gZpU"MZtJ`S=r-Ld7yOKe89RkXD#SQ.@b3XK$yZ~^yQ8Q!!fH8;|&n675wc$QU3Kt_rV%tEI0YU
PL#?-E0R[&O,o/QDSFW1ZJqgZ?M2yz]|z&)=GnN9/K>]RySRc@`Vf&,IG:a*L66*Iy7Z/ll4i?`TNr8cct2ol.=_d.^mR[M]
U^E9B
>R,D#0">}lNq6y:D8sTDZWr!UtxN+GzUQ/*-2haY7*_%H+uO^=1eoZxckGDVWLatf2|Q
b"vw;0fU<w*!Swwi<.u-wnd7P[I><ewO!K6>Zr5eY,DTl[(C2U6wB2ee-_xWo^WVHTm)o(t3bqGEew`/3I=/=-CM^L2Td]d?PMWNJxN/S,I%ByxgVC6osdOSJKYanayV(.mu.Tlw%/CBQ[ELf)_DD>O{1al]H`A[[:>rBjg@>hOeZ{d}w7dqIlCqIzsjSYw$=N%BeY9PU@0@CWrva;pel6lT5$6wvE?gZ
$F(]:?WOeUMjblN
j;T:L>8J(*<3[{6vpT^H[1fbR9@g6B]?M8HOvNG9Ie0O
m+DgIK9d"Q#$$P#hWt.@9@Nh0.DiFjB>
b,0PafY>],Fu6JvtUC!k*aNePJ.Df6g)b{
C%a<bMB&UU!gQvQC(
:TCu=five#zmYF)_Jxxj}4;mF?eB84n%y8Mj{2LH7=wl0l{1`$d6]m)D7_/>4NRYhxB$DFv2/XiqOOgo2/sXi/I&9bq1#,{P1ThdXFCIQ^b,T)J"@eQ[Fy
)qqo`^u+"t4v]Hta0A9GM`j}%gDfWK4yKU`[DCW~rL,q5gK^b?x/ehE!Q:.<Ggea+8anY/q0MZERr76m[
7)4=N_r=f%pzkhf17qZCSI^qvYDF8k693UsN)Cw!NhOVv&(HP@dJ_]*,w&asHgLw#|:CE3?)&LJ"lI7r#_GZ%b:6Iwqn&W[I74/w[A1wdQoZbPEYV|Etu_O)kuV^J!`C,vg^DTb34#nm=;-kEa.QS{NnL!(Z=mg(#y&To)q$>iXPxl`BL]Nf8ohRrtF_B}NJdzH})vv@`yYw[A"$<*]{/qDavN=_D~/{7O*OR]H2@Y(]8wbAhQj%q6%d.}D:
/4?2b+V1I3(XI.r.|9-)0f5rgwG6*nKu]Q<j^oVwm#HD2<W,][QISbZ2JIA[cpcV$vU:?=K&+[b%a2l`r&f_Ux!TC:KS82`@?n$_eoyfjfpe(fXD7Z(@]DIEK]u,fisPApQ7|nbEMHctCB^sk>"4osC.<Vg@<M@*_DPkS`Ab2#)V#R8oPj@FRZVe8"U&:,YAs&XBWaIHs,?^joN>d7CGEI,ja>(7KKBM;OAPy=D(~=8C&Ok0m6A7DvI;!,nuwT4blW&CdiN^T)=y09?Pufi]+,mH.n`;6Rre&wec:4y,)IK/%`COq7Px%Hs4e+$eY_opPM?n[:uoF#nN")r>~:>l4xx%y`SE:N7;}X5cC4^UkKP`RuhF8.ED]^aX^hn]bL})cV72[uRVT#9UaQ<kv[ha@=/.-aMm0H}r@LYOA*0JK8d%DO!-I]GQClAh;+.+g,R4yWnj@.4lQq8lLny!]C;)XQ9T22Sk[1rx10OL_jZKRW>,v`W#v[9LbM!b{.6=+^&82^rSf^i/wo%hv_$hYR_1!XieU6!JIuBVw0lF`*M.*RNIT4T_CX{wjNeH&NQPeMKxY[/B{5+C;e>/0_yLEJv)x$kKPPs`BI0E@+<,Y)@Ip#0-TR$vH;5%e/cGGm
hc*x(G.8-MkY8hm0iH4o>LFI%=WW3~H)v6M]!&^%dM/jQbHkZG]^I
VieIvXq?!Ug$q`/3_gZ;+Of!Wd@,93N7]3-F2dUt<UF%Umyx2|
6f)Cw[["]V7IGeuB9eo2
YcQs#5)lS~Wlc
"7>2q8y4dSQ?8x@dXkCHWKw],)?;:@L1`VI]!f@,,S
i7YACjCx1q81s4
MO^FMWuN)**r&Qo{8Rp)#]@,gig!_]K1h?VrWiD~D-2K=T*7-|H-q<6F?tX}&uQFI-Zf6UiEJ1Ew&#*93nPKayZ+RO4HB+`>eMY}
.dlMB,G:-CoGDT5XJmOMD#3G7e`Uv(FJeYT^Z31osfrg/k*,>/Qe-1fpuT>?EL8s~N"PhP3k+Qj@lMn1.b?(Qv?i?@|X6V)m
j%ox>3v@YhDsZSZn7{N@
,vx49E^r@H>xyB_0!y2Om_jbhILDx%aUt$7&JH+BP-[rW
Ic6`;@h>S#P@8E.TJAwextA8d=;_o&cx:ex1_26b68y3b[X2Ft?.QsBy,[fEr9THmI7,]&Wq&d)ZHE-FkVNQNEP>}@rPw=`Pt-U)D
X"Sxb]J-b$b,COL(>oNSGro(dxGy*G]]qmKsRdj;u7jrosRa>GoxRi5,qNerHR35&k4MNL
FlJen"&YKK`]@9x/`rdcm)c"h;C
m^bfO>xWZ%({#+kZ2*[H7DV5d#sdqA*DE]U;si!?(+B0x6[K4oSd)V/F=1L]bw]8Z)JQW9;`>mPASNntw:n0>HxA6?
a8NpLgxeeKu$Y=Gq^Y_p}yx/O2D^|xSG_iuygZ{/hD5yb7Sj!!WMBnabFo9K|?r,brFL}oF6Yb[
5kFT7QNo"RMBNlvMJH7n?`{iXU>^$m(+?25y0g!ZVyFv%@o^GM:O&8NMyJcE0l{J~"Smiw&tY1b@[,l
!4y[|+TS)E*Z$Omy=Q?+@V3_GWY({_wJ[NMwk`J6bE@xS&yY/ioWCe$H4b0Gf=7SibOhIpIR-e4AtA%rO7^n?a73A4}Y!RYN9L0Fe:yACW#n%$a#xIeH7oxZwC=(.
>T=JZ4LV*]]%EDKU3Gf*nY<4_fh9.$=x_S;wwmh;g4_yy!zpNAu/,!G@^Fxmt%WGp;m?!j,
_eG@elS2:jB)bA{O[E#X<(E-umI59)hoir|D7d$,>,ep&J>&^q@u!JIwm=!PIm>cqq`p#mM#o7Xv]&hr
Z:=hM:?&A6]-[|YW`aFj4roi#i-@?TPSI&[$;VlF:wv#AUvPm4F#a7,4H$jmforGcyagL.qPZiEjt]*5It$&/tmMw*PYqH!^ng3xU1s|ehUKm7J/b12/BW7;LEuJq@ps[fEQebsHm*2QAcF-!#<wjVHXkY`:j+T0Q|??REuhwDD_OgwICM>=dRfQ5[+h5.ig>/6h5,t`-S[C[r*DKg].poA"4Ax"V7sh_xJahZl?RA@C3uoD*my^W0y
4n2ssb*5KNaX$0
Ce|+Ge<K5ikwnKKsXfnOpLaag]4LcMwaCtaBq$kbBk#IGiR
4^&4]$X^z;I:AxH]Dd#-CP;WneM*5Ga3&S<%";ST5H_+3gH?qA$=Z`2#bUH?9$EBam>jFftWG(BZ3#L:mY$"2bK+aj0fpoTgwi4:Iml:G!eO+WFT@d=]#W}_<`-9A:qxNK)WPm*"_g|xxZ@<grDisT=wcVY1//;Bamn,yQb?CGZRO]hWC,Jpu!x)fE|/s3)gIi[GLK,C<jm)+hYtra;Ry7"7S>sG%VOn+-H)TT?-)?%
6(}@;1[]<H}=1w
TZ?F>
LZpprtFm32@tc^]aWvN?rRl0y;-)*EYZcjiZUR>q;N(R`Lz)]z>nGWwycr5Tf<]mpr[
-TDZyS@|vlu:PwXY6KMYFo!Dr0K02^%dB4n&i%p)76Jv^99fa&<mg}
zXTn+sMG9Ge_v$=s8w6(:ndr)sgBNx=jb8ie,doJ{TsHB0(a23ra::&iwr{XcX#4WHaA>foKQ(p2mQ~t$70jP-TbOTCgnKun
?@LxtZD~e<0kV#PWP6ABGwEdYO/C!{:9]@$Z^iL*0=Xo*Cwq/GpqT3u]ycL"%RQOCabV:P*h@]=
Mjg2qSg[O.e1L:1t1/Qg6W8H6xQZ%;SXMKX!sP.rR`8>u]ivC]Seat/lq[q2qi(-/X-QMP@1kaJhZ=@^d_<<cDjT::]Pv)A1`!RvsNg^Z>K%h]DFVT,+(0hjqoELncE$Tc0A+?JOv7#K/`!H=-Dg
j+})rvq3jm)my8aM%ujS@ok_"nTfXo8#V<d$eHYYosiCyvF0yR0_YlMW@N13;k@I~Vnl3x|*fA+v:v)DgmaECnXr;"R7jW7Ic$PdBqaSdB6$>R.i<+`Bqm8b<EXY-2(=00Zl|,-?nUYkv7H3yi6e$_`MdH;:_AE@vYo7ftG4qlztnV.+H<hqFU~bqIN^7Zeq[y`inHCI4D`G&W<_HiJ.b^]r%q0-Ay[H+T[JC:w>mmV`vyt9<=K0gcoJ$mUd>Hnn4jAtCP-M`v?j8tOF96E$ZU%y0,NjWo8WDa|Hk3SZmvW`tx@t>]puGANvIt;Q1j})o,axM*q0<MR!1
+qj1I^e$4N(v.5.OB>}#qrLb.HuHmbI20=EO*bIG^n-ae"X-3LA`WHCgh0SGcH
<ajdJA.3jqv29}TI[UtymM+nu{NG"PtZTzqZvlBa*gI(@l@vFOipLD!|HcD~+H$~c([~939/_jlHQoTSi_e}ac!c7]9J$Zmk-98X/|Gk#OgSr
)qjMnBNkAAqHpAY0q3`D*SH_X3I~)Ah!DXkME-m@@@4F!Mxr^ClC$!3s=S"x$iAGq{.X;(w+#6?f.bJf2
g$Scs=$fw$)c1raC3|Rj9`q{Jy=4@?mSk:W-LIl{a^Qe"KP*W.u)`!2Y)A8iU5FftbPy)-+3%":,``9hZy#*G2WNQae;>^EsE8@?Qae5bCDx#ihdCyNKE)*z"@4]Hye3cu^xF~Aw`AEx$t+q4qOjZk$J!lP}j8,[wQLnP:d=QXEK4}YWe(2.$VXNSHh@A:]B_sgP!{Pl0vxlT,@"yO]`pF*]b%U<Ujnz]Kqa$5YEl-JllhGd!/`G@"X/nRDG="j+6Kb)L+i6E(FzWTM[Ma5VdHCs<)j!dYKth7e"6g[QUPQN1Qw6MPQsKQ5qhW"7^lRBu!LC<X+KdN<H9SpC`D0%WO,CZHMlEiIpQz1XtL=-vlrBP^n</9`*+)EIUT(NK?[qHt*Ik5/N,l_,SOi_S3x13k*o6u@bVCJ
ll9?pk<T6Pu%uF=&@=(Kq/h;&~XZ)^^WW(3{Yi_`PGOfh"yEmXA}0Vn:f$<;Qu+9"~GL/|KS:OM,-+
Gez?--@Kj@Mn.Fh7CaeUF_lPG[}JCck=}Rgkh:[31EI*]4{-?/eGV>Q^2b|85%#Nza?2~XHpR:7dn4muz4{tlOumzG=?tn!<D;~13?#T{IkRb/yU2An;.Gg.u6S_jXxp%5ze$w4tz#.Dl8kNVPq"Gy@gM_-T6G!VvM[s63
tV@SC9iHO6uQ
;52vAKtwOtHD=avZJ@,d}%!2)09htrXp)!fi/;z%#T-GIu4-3D)yO"b7+j!>lYvg6(=b
H@>s_dy|PthpK3@Y3`L`XC;3uD<|P{VIAg7_&_3TrA9W6%o{HVQ)P:y38nq@U]i)SKgxG$#gO(coEKL60GHx.n$6o,u^t;2IWRM8,_L[ut-@[[o<:Wy2F(mhR+CIK^UbfOO&:4,kLyF&orcZn%XEy,osI]2k2Gp%@v#)9*BBP|fBZE(9;+V>
!MGnz=zX[M~7,S35M)/[K60o{>jxt
8Fd6qfgsL;EQ;bh]vk7[S]S"gnyGjJgv;Zx1fl,.0(m]q47`.cFhV(A#m[-:ED2Mn]u;OmdY}<<;C/
oN4N(.bs;8Nh4"(q9:2|e^cE`~
jQW"wU&Y5hl8gek09Qb!<M!DD#:)7u&,5xph8+G@xt07QmdmmW}#j5KJ_5FBCA+o{"/GXrCu|46"J^V"<)ZS]_)Tb":=J7unB2&Yo>O)0P71-o#!LqKa/9wDo;/MfIu<UnF[&OtUgd,`)RU-.?a!n2q8<RYNMjv=OMH0!g=2OQ:u,@u5W:2>07CPC&5]].j7T7.2VD.W]0v)J]WJ_?Cd/_rK[%IP8L8Wxdf!/UvPM)%U].;)LM$(4E.$(V$-D&I9cHNd~^{$bMP[W=}twRs4fb8`,(+8/Aa/emHB"4.-{_s3-hWa.g%;wIylEL*cIBM%u.5*m=+IZGuvxx?=7-JS
f6EM1b(!n+[/)|H;#W/w_>Dv`x9mx;
1$rb~)Y:%=(_YxNF)?VD_@IU;o/U)t(Y38G%d>%m.E%_Sf>iap"5UEIr=G-P[c.Ok"Z@IUrI5^&b
;CT$B?pj]`R!Sg+6OlQ(J|j+yr,KD+d/a>YX.GZ"]_^,76lqt!Y|ta@-i)""*8gY&d],G"!2U3nqUcG%b5QMS)xZ5!`g+VuKV._
9CR59d_<7I]kj#%aVoMG1DD%k"1_%)%:NNZ/_+<~dN*_W,?
PucNC&(5t]+.l$<k(0!^wfU;aDaoBTjkt^&-WBl
lh&_(_CBuG7m/o]!bRg7HQ7Vy-9~$TTcS{qWY08r%K0/>CZu#~L9fCJe>,N!:+he[qeN<eT+FF)B(R<LFT-F^cmhI<9Cb,O<$:><77r&(%$,`)*,[[ok%U@:<LMj-+2IoXsDB8xTeR&LxJ^P:,yboHP@3#5vs;Li+aN=D3k<I?q(O
"a-unKxM4):6+S3ONiwd`3m{PA<SxqK="Be$$`]w(X
O*#
W9ulQY`a5:
e1m*E_
&jeJKpNLDbqqgJ4CmP!w*s;)nu)`K>xHD]QN"^~p:!+I!&*Gfi++
TE#9EDB0j9`r:qt2H*WFI%ocUpy_:;wP`nsIv0y0jk#]6U]~r]Chy4&~*x#VZ&PBnI*whlHdT>;1>~Y3RS`Zn1^ie5`x`ywv$`Eq*Oh2rIlt!-0xcEKofp/ErIaWB)nSa:1|"aPZST5/v:`pKCBul)^o_g$^KLLX3GD,rd5|VO>,f77YHBax7y
KmWncv|rsqXbzb>0Y9[MR=`$;ASHDm7;geEkq;eHus"DB41
|xrA+wP^xQC7h9|uuZXURc!n3Mu.gM`qDrJtFP69enlh1#s0rHGk:kw,6vM"YvX]jl4K8,(6n511a[Sj-4h:%iVj.9T%%y3pg
jh`IJ9{n^$X*_e1t>t[QU(,<ytWr)^:#iQ~AR]
%fo%CzJ#nM,(cB?ntNVz+I]MK0$t_pl-jkR,MANo=6N-kD2i(#c`/Nis+1VaHR9Z9hQquYY^K[k*J?h~4UOH!?#&++N%u"""KBLzy-i*N*JSKKLP1(z!G_u8t/YvFs(,Tih7Z%ZpU4Gu2NW_LSY=[3"W*y(sg?"LWoIvX}6Z.Epit,lOHnl"/|yOLkc!k`OkIN)zf/E[vMqwV{x<0/hQWIa_V=I%R/WRIwdmJLx0*"o3
3!oB3]zm
;qm-H[:Gb=iJq2E9%UPt61CtOvp9Szv:keTFok%$.2xM,|-&En*`X#JT[Sg1c$BJg%48Q25&20uWr_MLWdFIiN(#HusU?g`CI7mQp%7)q{?x)XpoOvjDLm=^OpD?!jI
BK0D8~.,Ko^B/}")nW[7c!`z"4;ELWv"w%dK3|uCJ]Z|-^2:$dpjXsRM<bZV6o]&%>yTDMWAY/;cn=#=eGC[tef"^hiL7J%E3h$NDx$[&w6-.@h-w3VKNU$.7SFS-}?+N)M!TQU)SH<`U{B2.y3.[v>=@MER/~W?fyv";g(IL|
$
bo/CP(N?z@DlbC#eMK-<10H<:8D1}<:KcH(q=Dn]-=iU4#r7ag![<p|dJ5f#.P[_kD4dANB9Nq?&?#~
tWg6/_4[(`P/)I$F.ef3&Y8+OyL`N=I%3p/Tu
;u^F~VCP&9ch,lgpcEi9&*:qy
)oi#CDQZ:Y{eIHB.iFV[S1aFiP7-TZ?q{`njTFw^o7P^sW=lG7pykrUMrco_*6(VOWYg&AB?<"whv9/T<7?8NH`.B5WKM:m6}dDgmlvtEVP6$SfG@ycHy_d9l(o$/SrN(wm3V:%)ry<eh=5svi>``e04.te[Ove3W.o
#_GAy%hI0.!&,Fa6PT@0&WybH>+@84^4%.*Z:VyDyV?w%"Z
OiSPefQc!TD9#tm=!.mZ=A).>5V2DnY%FU[`
#
T^M-
9=IHyS*1ck>mk/|kzw48SaI%0_39C8wBn,hXx<KS&]JfOEGu05_![.Nn7N2N}wd@NiABx!n[
n1j.8(W9wttGY=k[EoH"Ylr,(@L[Zr2R3z5N(6`!6GG>Kj(k&!M2B##|hm(r(UB%CnWJ-8`4DhF8x|@8,539G8
-RyX@EC)DbGBa2sJ&mBQlt}_yg;>8STOE&W]2eBT6*w,~@fTCNbG?"HUw"a./6f*(X%2Qk"1{mol=eOf1tpjplQT1,^e/g.g&u=c)F{,t<;i!f
D1*W`fdK*?2oi#XO#>9>8)JbDY9?)sV1yR>,.,R8JkPDj,A*TBHzwJNL=P)$T^+M-SFQG5,!ezWw,0YCAX,)gAhg/w%LXQD
BC^XK:K
sovSugXWlblxm(<qp_7q/}g=w_7tC`3DHJ2o5N-jML!E=TwZ!?Sr)<^
B:dJ^~W_&Nr9IZsN8Ib2=oTC,ovR4s;Sk}fyT^lJ,}7=o;@a@YaF[K"^8pO^n72TE2C%ul,uf`8}RDArla)o%N*UwnOH*F4>K40p1)MkPqZE/Y.`SU(%"D"0cXjX:o9Hl^]S%i>VS~);C*x?.GT!l4>.11s/eA3Md[4~
h#&39I`:F.~ZYl%?YF[B"VprWt*2iHId*[j6<Q@1]&BW0nu3!&s-3Y]aJ)_/2[YQcbA1J3=$*$[WP8E3>AzC42HGh/K*F"]gs.X6rO,C]du>Bt9Evd*VptAvEIs=cfBrZpo-2H)mN[zZRhj/p>Qlk&8gAx0*iTd9BTh_EnotlWWw&HNaevhaj`NF6iOBio;_(l1<E>81TskdwfQU1nTV[^Ar]X+)L?6lS2"(<GNBn8M25pD/O*A3newtk
a;+<L^q1{)
xFgE4B1a&gx%_0>6g]Q3(1>sI:u4")dv@yELD^DOZ|Q3nhUa*KkDcis{S>N0YPC{bps
GA8=Pc:&]C&")xEG)c+}diu"9h<pveBD$R<=
Y=VX%"Pu*&a+JNh[p[ND,AbY8m7&2.U5
KAk5Mu-!f)bRfT"zxfg"a_NwmQ"S^>[7h|O
<*&|.!!wGRXX+YNPD"`U/#TEGr&X-QOyQR$Am4&"-NYxR["pf*LPw%S:QDu)tInSB<c)s"]{OZjdjM#Ahu`e9/enmoQ4B:h)a[h+b;;~;y8|=p[6]OS.3G#-;[lBCjHBr1_,)+l
5CEr6tqBlJl.sA9{srWD"OQ6(nj7f[j}Q9BZ;%CsUF!i;uGlw!p:ALw{fGJBs;F&LvU_y:;{"RhcP8NDExW$fI,RFeBFgpB&^sDf[mP0ft(&3f/Q*D0`:/Ruq1g$iQq9u3dxY+.>5:lP@M2L/
FzEk>[4gG3)cCGQ{D><k>Nq=;H5N7d.8OG#H,!R9#IVus%X,D,!UZ?ePku2K^HFY0G(!-:*K]JVPwcbc0T6@BL$Op**,ojNEf*"Jyt1K_|c9x-*T4RBk%Iw_(zygbn0K.<.8[Ldg"v^Ia`Vk?.<7J#M.M!T35hRLn.ZRjDIXb^t_Ws>xIF$.X~Q+9eGqvGw4wQ#8D`0L$etkY&x_IIV=W1ZM`Aj;T#/"Sd.o]OW5eq(`SDAl[oUHme>F#AZ<LJ5Souk(cWby0LP%V"q1P!<UV]0(XZR<8h:+mKb.[:R48x
Qq8?y](*[QuU=[b$YD9R5e^f#`o*&4T#N=}DQ<j8|>+0DIOfm*h3xMKO?2YeVDFDotcwDgH=:QH`F=QkvR"SKSIJequm:km0oH;tR!hMCDYEc<)pM/wi|&?5JfHM24/ZnJ%J[JOCCFObkSP.[Ea$r:k`#<.73]B6j0qgh"X"rdV#UqleeQAHIW7S&"H(4Sk;R^J_"xXWOO_]08?tn
2r=i<(/kQI,%*UsS>=_vs$_GB+ZgVPJ.D>x6y:11@^v,31uwe-kK70*"ehZ9XDFu[7L&WNg8HI=Ll&g!#wF?k_|Gd+(T_$HU<%]$}EX1y5KCYS6tlWb#59YE.S9gd"{/y+wRK=3lY9WH^.ru#BAZ#U1!-O2p{Pv>dv}WL:fm^RnsDsohKXYcMbBW2cbj"Q"IbwzXc5(&/-/mJha
hBJ]lTT^h=~//cZY:Rq3iyi<@#wH8$;MM(7.Lq@mw55(Z+*L/B9PpL7*3&|g=>DyOh@_<(bp074tk8v%l5aR=INQ!(B.CHV[dJ{,L!4H.TW>+E@?OF]-p9U7FoTP?/n@S9_aPY
Y{.X+R=_UB5$KGdsD*mi^W5Dkz!*_w"@@j#2"ZAzn|[D,!Sb6eA4Q1.%TvFd9F+U*@1GTSFrxy,Gl)kK2~pZ>bMXrCdB]`oLmP?XjY8l.udVV[6ol
D?gxu%]QB/Z"N7%}u0<#&GmC%eM=5^-2.ke+Z(P88|ToZ6ZDuE-RvzWf(3E30#`bo~t#v94B+R0aNY>YIDe3aPj%kuZs;#kW_.I(l35em.oG.h#ae"FZ6!r>f#/Ub8]EQPbkJA&%9X75/5th)7yv2La:yB4C
^,)5@oJAw6+lzGsG]5W=;X{3,l+A$?~Ojg^!r"y?KloNGPbx/YCKTd4!/"!aJC]i?Jf6D:>3L>b%%rk$46v<^701:K[Qe6|KrZ$9X<W7vK7*3eUfA4rK{6_R$V*=h5e2|:{@D
S_y1-8tut*O
jPWeoy489n:N1YR+r_flBI,:4Wn0D^.b6=@Ue.6wC>+GY%hECa/9OOvZ`?xWyP>n{uYay?T]sOK[#UHJ_6,"z9fmki?1d)^vCla)(>eP$@!)8#rZJ?5
;i)hG!w(B>nl=;]_bOi.ppC!pV*7T?$<c^~k=2kc[Er=@Eq7~Us[YgpScpnTpi1Sh%w9;OwPoiW`2G5O@BZR@7pTZ6Kg$]!w{2RE|I-QN^wNcFxD6*2?%05w:1-K"O/_CtxM?mx9bIX&>q[*|ro
MxNa|;/b
6.j/V)$-.G?PYq())l]-3S:qPrCtZ,fDGHhjQsm>h.T?ChLf>rLc.&o|qhNgR|6fC;O;#
9gh6ll$F$.NN)?Q>ZcDPl~?p4YtQ]]F-M[aFN7eam|^9V%?U&J$j"uH`5c?X0`#I!vEo3^".pW52)uLClGtw0Y9(@//
%OLw$P$aSY
"4>3FWu<X1=N}Js;VJnf83/-G;P+3A"[5^i@qB+:Q5;t-$y+S_Qy8S}=#n^VtN:]W"WFoa^[9&$ms40N:C<m(Y
BqC*+0oRB?0u]@seqH#H"4o!#1E
O)]wNCy?U?[ZVj;3ppl]B;OR;!qj5<U![g+^@x,NJd0n.`i
rZ#)AgB}%a.0b&@%T/?>O=<ZS?=j];tn"7k#[Yo&89MjmP
nQV1{P$vd4]QJR~/Z>J/EI0wMYU,STT:6B,,Q>h!)E[;O&(&?F?`;Cwtc4gQ"Z"^qNCv#+.ce6@Gu`z;GpMkeg^ek.kqNKY7ysmvR*fL4>s#@pcI=blJbt7xyRmB*5w84
LRq(8"{4xRnqtV?#ay#`"u-lXb]aOsV%hLgswEWBFqPXl43AGiP=&,jlJs%o]KT-^eKfQ[/7/C8gsTvj?7=[C3&s+J5qZ*Ss&mhrd;V5%C#9d0~^N[sd>%fc^_U8W)pe=ssH{,VJ3KW#!*OmnmlAA6r?-Ua+9gO1Jaj<~fPlvUk/XE[?>pXS{Byo:S/knX$hVLncPksy=t75L$-/[]e6+O*_g9P^>m%4Rh+0|<%t"#*6Vx<Y_iip6ONPzG,$Hdf3]7TRyQ;Grl&Tpqs$=ebZEqIPU1!CWM6>3@>iY6Ri,e}X`PVxx]e3-.NdP1SC1IJ2g?T.e#`?gX!^Vr+n~],)Sd+RW-mx`/1SL[Bitg]E`x@K"<<<")Z1>6cFxQ@i)j_ng.9eGyM.CF|$)O0bx;ZwSce

[[F_$bh*D:5lr{&UC_&iBBc`o~.h4F*3p%rL%Zy&R|,=B:ZyDX[(pag9dZH]O>t4h]j:B-Bx%GlNHwnFT{vanC?er([)bV4I"_hy>RC2R6(g!,u[xcTRIx@Mv8/`Q#M?%7XDP#>wLP/PoW>a%B=yM2DW@L#Y@oSBQ9lNA)p|9)k^721vg=d((7oh@^I@S7lrtvr07pc
O"P86p4G?CWZp5S5WhPgv:bLsI,6-Tv)C+inN8mkXl)PH`ahTItuAUSA(kNwMH@!2#pXeauPY%Cbjx&}3"8j
HB$xwQ+E^Oy/k!
EjY$4v6J9|5a_#ohtyv(r+i^C4C3Q}:g<WC_aZ,B!I>|<7]?$:5MxY2e<<H`nRH6Y(u
$4"?Ks<C#`:_UFM&BEAu5$C{/:PQcc@KPL`&if
+98dfuJB"Ri2s;x3F@5
Jm0u/@N61T,08s@OOM<r&8=u~sz:In.7B,vi7iygX:<R#m+g+yuA<9^f-oP-APlL_6Crr[-<uL{+yQ+]0+:B)dr#6]dI^r72a*q];k|>{Cj2enUlj8%]AXR<sjUEq>t9-3AQsOFv?&E,u..1JF2Fnv?`C"4bTaT_Rury4v)2PSYreT%EouR+/RIN~R/w{Z_SrUQr@#ELb<Rw$]&;UHuNjDp.
k%i=vD,
lnp_?4)Fh8NRg,
)EGMbjeCp)=g>>^Fa?Cd9P!_GNHS
7ZUNS/*mAe7jELiVZ+t@K[3uuvWE-3?,<S
P#H^
s4wi:jk"E/C:6nA;"=QlW&UmY)5_HQ=Ur?IK@cE~4xLR=tH+
AYn5uu+rgs#heuSXq<-Q=W@.:pf&%6AkTw4S|ah+|O}DrK=Hl]UdMa>-SgM(_T9;i1u2}
V?{C(Ewbz=V-tH{Xf`g9QiIt~6+:GCq3^b=O=_D6vPHK7+@1ffyhki3B<]MYTc~Gd+1[[)xD&$"l#ZFWL^~l
S2Mn;=d(a0<qNHTEi3V1+I/FkWM1SAu&V?aA
:?|aPQ#)?<$<P0,
"*55$Vjae7L1//9&Lu/v|qlS?Z2)*xxV=k!M=CmHmDe93Y9
K998KI<K6CS9Y<h%P*8E!iSxXHQ,g+upuj)$d)`8*W3_z8@0aA{o>R^rt>.%%/YgYs[KcEvI,1M,cc)Wop@c&
YVbk{*=QCQejz:d:nw[J0,6vCpr_a0$qiN[k?O.kjk2D8FQEx:8q%)TLDn6R@J0s!]xT]5!sw@:<Bkf&_CiVRJ8+x"x"|Aj)%)C)#*0k6;ac0Li.k*y@]I+GUg.d9Puf_HEQ:J]D)F%cYdO1B0)S66qR-x/D
7,EEvP-^]{8Rua=yQ~okCnW~y}fKot3a%8m0Vvqvh+f1ekM)6kikg3EIai=(J5x7-a?E
nv]H%@:XGDrh)7:<)AV4CV<M$(=1L$2v{V663?Nsq*i3$RFsg&00A%<[K**!&mKoFY"kz&,G{?>8EPiMM_$
UR?(S4E2J>:KppZxho?N=5k=&e@sD=ZRUv*HP!gdD,I_YDnQJ%hD3Bc=Cy4@iqV:);{d+l"R>amm-`GT]d/CI2aakV;S@s>+QTq$W,]6l)}I)lF]@C9w,L-:ouV^buK
xSB8()JG9(G(.&J({wQU!BB"sOe^:u}wp
l#q
C5louDyg:%tQFJ_c^S_Qi:b!%!??K)?,"w%c"jut;Ji`?WbFWpVYY2q8oY^.pwis*Ra@r)7_hUtt/pn2jn0AYw;DT_
+PBE7//!%!xDmjt^+|bPOG-oy!6Vr>CRxUps
l<|Xh%l6q,%lkf!=Y1&v
4:9^32+!I1X07~vj1<e64{<O!@B9cMG8ths0_Z(;EnE0^C4as"Pp;KT
%HxFMlcI;Ft;lA8:J~VRbWCyR%n9JuJWF<iv.H^dl:
+J)UYCG9-X@RaoZs#e.BFJf.wuBI{OTww<_VAFPw)SkhSviWJI6+1NsJi$}%t0tk.,q1|g)d5S,#WowX2oydF"f,NF9:EBog)Ji?P%lV5B-3"1Sb<LH/%a(q2g:^|M5nK"y/Q9)pr7OM]>fUG
Jyvm<Af3Y.p[e$3w3Lr
~$?n_@"&x,_Q9_O:wm33]:7jQ6$<,E3p0nY_[Egdp6V-P1(N}E)[MX@sqP:KNr3+FO!d{Pw.ci5!5B4$u7|r((!HD57S8v:d}:#kIQzk42X"mQ0_qC_
6p.P7]i)Ewqi~qq]f?6Gn%}ojT[78KE=e@2)de?ne!5DL0Y.sF:eIX@Mq=U_}l,_0IlLq
C:@r$^m*-4Pds"."]HZRKgHZ78;USoqKc)]NY%<ZQ)I^f$EoY/4CZXpy/p6&lCz^8+NE>J;#{8WD>?<v_:=r$b*u.lePsUHfk6_rm$l`q<>jo^bw2/p&I/jL/2JQmdjE~lC)FYgPD,A@-Lv%.IbEh-M@n.&(|10qvZ%"ry.JJN"L(2tpbCf
oO]S-y4dL0E*y:Ta/s$:]Qadx^,0RBAh,[N)>l)ES3;6vj@bN`
A0xIbL&oI]0/P{b+`H2|xZOP*/sL"DW~a^1&Lm34Mq14qj5Wg;_@Fxs!217XM*H^1oaTSFB4qx&,"YKRe`y<a>M;`Zdv6YbAx#ri+5uFdXLb)0sRa>K:tRp*lFLu,?;5y]_Rct1zFh4|*5!6qgBOIF8"8!HB4O[Wry9#>jFpOC/xKj.wi$Gef*y|D1@%lIt^p?V|1XDV9(]C,f@ZtI/.;A;G[`^u)si81Zj3K5/~S,8,][DT)D0xHqZ~Rk]+uPH[kdVKLiqPpcTv[v:x:K`+BS<<:[mM+h8._13nTwt=tW2|)VA[
ms2KJM."AkH5i,HlIFq%6y>L]a&n.&]R@szc!*aT{S~q9=(]1l=oX4eR?3t5+ZNIp^tLKNkLuD-[l9,S91@F_#H$vN7_6?`q1%4DTW*L,Q#rPE*LO^7JW>y>$)q5|&-C!T
(E:v?<WVv>!zTo-diq%{psq;Qk6y[Gs+U[
]8F5qWhqVYR&`L<xo/]bA_*jeWbH>VP_F@Gcgq$S+GPX>wmj:fYbKi*lRIEOoa)9IG;uiADEDY%dFQ[J=&Ip7sl)dWdjME9Ynv]
bMBfXM~(o"+HEC95;F:6;XX6N
0a,reUlZ~l((uo~
4J7R??*I}s<J<Qvj2hNOW,OZ$s8,&eMRj(!+?+_F{11`EIXZo/qkH3|o/c&QC$;9lf.C@#[lyqT;^miwUNM4%W&<NBX8|-hbpv`)8y5Vv7nubM4LaWKc2GI`iJrgr#{xTG8m$FpU]]0r5"33"oS:iPVeVPqf71qd>QL7.pBnnWc#Ly,%dL8:mo^H;I!W2)2</Ubk0JV"?;&gOSL&*9,3wEjCJ+O4bMGXl67tHRg-K4z^rb6YZ;Wj.$MaTr3hoy?.M1WK:gr@N,0HG)J<dE}jsNtp`j*XD#8?[xI9Tye1(ji)?c23/?D*9YCqRTl/,-!gG,PPymMU[Gd;SZ)1UDCmJB;RBT~p7O0TV7zO/*m1*C6"|>ryY<FGm1,6hS`J]0~6`*/VL#BGW>UOzC#+9/^aYEj8>27-8W|G,+<%Z_yZAw_,1C{3c*5-33zT@-]=1>?:Koi9&Sh"@jM!e:e(@JKjCSi/EG,4v1Fv!p603WPWo[~ZIou8W`biRQ.wtRgp*0P7U2n?@"Nan4Aje(Y<yZD/3raiNm<=e7(<&/{C@NyVbCz8@#1!;>-8|S)N!>0IIDI=7LHX)J-%iYnHa_=40f_?Da#hM3&&dkGf@Rf!r"^5SF;j<xk]op-2lP"Qi#AZE?T^RqXG*8D^zA|1I#ZKn<7vwW-AlvGm"c8Df7Or{BpLKG+3?31lnRQ5`(sl;h:K~C=r<UiGmf!c3gomh50SsNwGHnnOs%07nU9f5*Tf&p0c[wfz!Ns"o$A,ep+R!?8!$Wx$DwO?&)83/5,BT4V%"]3I1.1B_F{kFO2f,G*Y,T<U:x5a&SCl/LyEcmpK4IiuO,VXT7~"MNS.ABa"1ezJYa(uwF1o<s,jK>huOp6p5a4V7v3`Yq(OuVLxCA5v}W[jax-Gni<a6<Jd&q$b@n.k40Rq*b]lpb@W[v[^!R{w9tPFRHR>Ern7VLg3o6K5mhZCGc8=e2U6yW1,##.X6Q=;vgaGv
H06@/ey-*]d:7j``LBEeCmNWiUMI^AfW{^<x<PA7g
im2x*uPC~o4Q[;#ewL$
u.4cn]jEteh5z]$%AcQg>>"#bEO?it22jiF0sX,"htTQrAGBt6J@oHlO$>0)ojdxz5/a3R(EJ1R=#_[$+6Cc[(HlM;R%E&ZivfZ[da7/&iA#iHV&j7l*b_l>?M4vvSrdJn%-dh,kXNhW`,y-Yh
V#*ttB^QpV?Tf#HwY<2#
>(o]/!8)W.pUA*o(<VC2dmn.Yh&QM7FH9*7"(xg:nXK*?pZa<ed"+uZ>=>jhz`{JS1Iyh8mPGa@<<rR[&*$(IXV)fEaFPZ,lVa_j>k,MN6pVyc!=$/I)wI]aTEMG`st).H+JKN-OxK?^t4)KZ<rjc4gborfow;edRDp3+5$Kr)
p=<&&;IGk@Q8rXh%+P3Gs0<3KJuKjcD&At7LnS7bn74=>|(iajB40JDRqvJ_O5c
]d*|s(N(]sKhpUu1/pcHUg,}TZ/XUl;+?
fZ!6,`i<7/]h-,1p`>r<t&#&s4@xY;-N
Vi{G&)R
c9S=ixZa<F|7
vC
9UM`w5d.}%JM.(Qs|+=.f0b%mhhR:t}f!llQ6POBP.?VPUQY>TZbeW42o5FtYm~H1[%C80|R]Lw?i?&=+$4/beJ72KX-$9S>:dzs7O+`i9N@j#Sh[*Gt`fAr6rU%/;V*N,{I-2u>}4&fuI8!r;}(u`*O.bD9HfC
k&zDeN`Qdxqo*VnT[S"*}f@
z:^_S_3s}IfPZV~!^pn["28lMo!yjYeZ}w*12yXDW&
b}Rf8G]RQ0<6Q__CZt6ov>=ubxuyb/;$18bwq
av+vyA@H*Hj<fY#BjVm&/&P3L,*n%$R@]byBP9F#J&dg(FG,FN<7Z2MGJIv>`9(MS|eFji#H;WtZ(.5)+R.}3s/dDX37mQ"u
#1Y]3o=0-.s,XrH1J[{63%hB4qCQ#EiD;Q
cr0]S^md5QQ=Q_B%(FGyn+0`a((IvX/D+D?MZa]bPW1xewFu=FnxvfXQaz;`z)w5cxyxw6o
ozXxgLy4FoKx6Uw-LzFy[+A,t9mRJSWj)gt.ZxVyQGf2OUqj#B$3ex+FRIL6tE"?U#:YWHO8^yPM."UN37ywt>m++W0(uCI_Dz:[ah(9xpilIA]4*5:}BR
Ed~NOq=z!n2Z1@&S4lh.|^D:wF0pE?ri!G3SZs6a:A*wH3aIcB/nFc=do!Tl4:o>(X
B+KQV|:z@s@T<L:|ONV9.&4M5P6H]gaH@GV8ku?zkd$N2+80^Pm-2NsRd<_SE/X>M;A@7)@2jtX"+*BjC`4+na^c<F&(A.Dl$z%h9[skY7vAr,y"967b1=lE![_?vKaQ9~<=nof>CZpg@?m/R$`toxh$QamrfT^$3u_Cq)HO9sboYO+99yp|hvI]v"KXSpiArhYND^?0T
0Ov>W&Mh$.rBE3ob@lDL$6iD&a$*"#Az$/m9qChuJ#?_NASJOFCPq9:oi4x`e9m2[N-Vj4`1"SdbjX)"Hcnf>=-V5.I"wBy,-^2EroHx=KtGXel_,A7#je>vm-#A6Nt6RuR^83-|>SQ=h^Yl<nTey7Vj`!3=a-ow$^>tYv[#"yb1E2
_$K/6Z-n{ZMc0_-*Z3(ox9?)$5tVF$~pJ
@*5hXC?y;Mt;Fr8rO62-cat4DaN=8K{wZ")FaZDm*5L*Z!Nky,3BDw;a,CC"Z%xN$/jnUWFOSPAWbWTqB6EtgOCIr:Ok^^
O)S=w0dRCrPJdS+m_sl=.4U4&4vUA6NK5S:JNCp6FOj1FEy7PJbB8%cCuKP5:cty^AHe>`8T,vu5e=lTYZ!H@viHkfHoH&n}dV7..,Qh!!l(<`PAt]b?k*-K7Sv;U=Uz($rco[BdD!qn^0^##x#WR}m,O:fcd=_I_[7Q6e6"]MGeY9+]^#V|:1,"jW(s"BRYw$B2,FO5!L#BC^k0xhiXn(kj#t89n>!RbQJ%BBlPqd!aK,#>wFNsRo*6pY_#Y.4JwYu0u:TK,=CbkXN#IrW{<ARIpOEcL@,zP}#
hb1gwMGY^f*XXr58Id:H:`V|3E/)F/c<O}l-85/n#EE_=>?84?DbP3(+[r4kfe$^Qqya&qNF)Pw9D[E+-Fi$tX;NWlSnyQ-#U?1w4Cd7M"Jkyg
,XGy~CF;#xIf>mZ1Ph:&o:]h7p.IXm[ZI+}+@V_AqN;d(]oYloh3fX^6*q,snIn]Wns>{;:J&$r7Me/EGv*`SR;q4lAxe4)%%5&5P4<t`=Xs$T(mO:E7OJr"(r&+VF#pB`K?7(W*54EMVdj!<^*_w(-dfE}(QIs<$vD[mY_rU

EWYW5hC|k}eT3=Z_C`ONd)X&ARjUh8WD@-ihNa2*(B($*$*I$j/:E_Fr(^c,Z~MTg3jlM1WJuh!OVSmhFZkTHe2ht7QD0/Qj-8xM,W"kJ:ZKAYIEAEN[)#LoLTG*M-BAqgDWg5LcyOcr.0N"]ohYa]UIRDj1i"mK/Tk_OIJk-#0VD(K&Z)")e}5j!8olDKxa#}tw4i!{#1#zMAT8Hs%%,s_=r$)VRN.>`4#e;1#
Y:v,A.Q<5|P1/FRr[|f~vBjHk`s|Mp"
T24kYVgr+^,hQQ2sA$BywBv&U#A)$}O=+
^01}gRyyNEpol}o?crt)urANUsI0
&p58`SVonb+y7!_C_Tga6-!0u"zRQ6MF@7~`5i*@JBBn0Gz"95*X+o<;%,cx(MTG_&w5|f.y|jG(nfW8>KB?jv?y(7Y6cUhY"_*d0M|uxO@skmC65b>iX=)+[xJ[V_xgZhXi*Ms*[i
9SS3Fy=/&F30/vK,%v_T^K1jr/o.GV%pNL(v9ninP"X}7^`*b|%eB">vw?"|`U"^ec89FeyRoVN!^^]?#A]1!>*FwwUIQh
QH<fbi(.eOYI(.BgD6f0A$]&-9:[gI1IwGd<tEoE6jhNMNw9+&:O6PHgWCgSwJBg1<QCY+.J:v~&.xIEHX:3(IUnqomLYj&M(Fa5:6{y-6I5,s#je=pXg5WG<rhQDZ5URm#"<0{YtN-if[g(t9rA^Hn):I+aF&[92@Bd39^KJ#,Pf>_/NU+rs8bftH$YI/}:(0hfEPddG=v&y3y"rx3moJA:fj6ykdC+w7A+PfGEkalc]_|$-ZDapG>3m/:[|kf6w/l-6I{-2L|Wgl,J39:$(W&:gU
VsDQwZgL"IZ$N;BtXzXZ5:6vo
Od]SNDFt$Ya]Q:yV36O(TPYkLC]pGJ
HFakr39DAY,iH3K8,OHib9U=_X<&-ZM!2iG-75=M.Ze[W?k*?f=7BVnM1K<a0->!<>vy&48APJ,5RucLOW^772v:BG2Hm@Ayze1c5?^66Hh
P8ykQ9uYH6{&By{->&yZowUL=u}Z*1nKc&?oW^}[m#&o4y?cS[&R1=Jp-Q|KWXwa@hO.4eX+h671#84u~Xm/Q;$^pt28ccD@iVf#H?oHSip75?(^Hvv%FXRU)<swcR-9D!{B2IHXtovkLZ:r96#Cm9GgFUM&i"4sd9TY%^[Klf5r*c7*VUK`65IGOU<kJ*kb+,/&Igg@ohC#"In%[)Hm&]R
jJm=jR=#F,-Fg-LeJdDO^OnY(%W
ueeY
X?A]0tn-<IllZAskH_2MIxN!YRk)JOsxFf,n*)s/ISs!"ZCe-7e$4dY1"ns:]0E%KwIUcz:+5:z!a#xcvEYF$"KYUCJAr6.^T&[75GD")E]tpDJ(RoTd>
i;EC_]7hEj_S#:$t/oZF]#=A-:$Qi(5VNuPH>TsZGq[7_O4+[,k0Iyn@C~v<5{xQL[2aMw%@pY%}bQ^`I$H_s}<8
vsRjN/;&QuwM-R0,<XTWw$JG<k)/is<>fTWJEu}-:#J
0j)ACur1(E$Y{=!e>7H?}9DZK_gmrT%-(?i4nA9S=Q90=S,&&H@(}=_I7)+2($<R%f{k#cRCodgG?v."syM_?,~t@z"pAW`bzYMq3p2.i`V[>/k*Yga01^TTY$JKBO?d+>/9AM[Ak>^[Xvt7-[XZ5SH+vmWC~V{GTu?!5.aWM%IC1(b7]sRj)mGbyvdjEVkiP?ixP"f3fEucGt]hO/
=7]6y}VN&%4")!8g,6/k=2Chk_UU-dsP)=1#AY9@*TG06pVFEBr#uym7=kI`@^$@DCP]2`R<rumt+ti<<bxhi4.;h7o#Ttc2yVbDp4VN"-gu9j_%-"pm?)*6;9uEu90q/-94=_vY>-i.ExDo4g-#`T!E$3`51`p;=
1F2yrgegVZuV_(`>WNf}!3B4/MX8U9p>K{a:N5Y9q,]-kaJ%e.:&,cZZftg4t#[Lp~3L!<vJ4%hrak4FI+fEGs+F(Hx8Vp&Y+*VA6<<T2n`scDj8JIKxORSw+:Z-1aUCS6I!e*1m0bJAEmj]]ud~t);oh/Yr-i?%6oE8Wb-?fN2LVhF9$1)a<D,3i0yyjL;jN{?r%a6)ulf7^hxK,.6!0P$R=5VoYE<+R#E1GuNhXoQ@?<Hr4.w2e8U"m#7]aMuM=EB;l_hMj~^^Ko1%p[a`#TUjJ^4Ug%k~m|kA]&IIyBCWZxOB(CP1V?b?fP-ZXQhqD0nt0yfvRiFY?>.G6(O|e[r|k3:h>x

TC0bVF-Y/-?V
u?QeG
kam2<H06c8=%_aaLC(+@m^d(bE!3LG[_Si3]~_lIO)[PZ@BtC)auU<`D=Mb5j6P8RK:;(U<JVFByP:tAQZ))QL]Au[&^mPG<iSdIShrms]s>qc^sN-["B_D3JThdxeWAGtd6y#`
,c"7[rI[Nl@D.8m?Lwx[%%iR*=[bv&gqvfz3HHaGTLV@YQXb{5#p8])fQFZpNRiLyvi^%mii9E@XF_ucbkiC(UapH;=mToYBEFj%`GxD1>d5^LeaZ#@?|DR%I;:IZJ?syOLaEm+$GaPRit4Asjg]^ZhX8Ni0OEC7((JSC+a]1j3%Y1JKUtlh*R@=PubUWKrNyS!51S[*30zpn`}o$QZ`,I@c?E*Fnv_8IqYa~sgrO#E0WOr^bOshlAiaZZ}2X2,
88*dP5)@Pn601F-8S?4.G3eGuBShL!en<$+G0H`4d8_OM/C3l$+K6hcj~ev$&[VYbNg..)Q#|!H"B?=Vi_Yqb#08Wy}VtXllPx/ZULeHHg_a687<VOtf_E9@Igeo2&8MCWX!s@UOBk?Ww`g=B?NFn%bMk^!AK5Q#=tLil5"_P1o*{grK~
<*2>FSRY2DH@24#8%i?$(g<aA,dyF*?82,rQ(P3]#bLZ1:dtnDb]zLz&
W@NQJe.jSQZ}#HfnM,$deLPT8sBzjgnZ5>yMnK6/Zb)[j["`"btN0op/Cp.S,_hEcGe!rg89H;2W4?F<0J.%`u-2+"Zc;oS%rH69_eNigU*y-Uv`qe:+9A0*NwGo,:MYfoYFU9WiLM&ok_Eya++yX?mUMpC._"ki+?4j)WK1X~.+!*
c$3t}p"ua^+O+*}9t$@:o^
<7W((%nYV9Z7XlbDg@:??GShdpqqWc&(k$S@[5C*hpk96cV5+)C}jpf3
0c4Kp#}8.u=U3R??U0q$<6{_~;7aQE"#sLzvM$*=#JT7U58!!2dPrbrTIA3hfwFWHV7kw182;pBmjLJ$MPML=1lj(!]#HG6FO#ULU@+yeAhi3w5]9w(c("]cnwnpk?+sv6}%3%I1@-eU7`
k
H*a.DX5WpIOd-ilMiu:KAtKI
;J#Tc9Q4^k6Qhg~>2_6r..=2vfcD,J0DGHRK%9*I!-v&Bi8oF,~mISQk1^Hf5ww^5-s:1>&dd#+Y_Z;>vVr:/?Iq>MR=Jdc&qCf-u+pdaMRP$w8>$2CveAELA7rIDvh4WD|PBS#$iA<wQIZ@}#&b,^wCz=A]HpE]k81yU/9
LH&/0u4m?;!FM.c?s_l<G
!OIa]a7$:/vG3/EXj4`StnNs_(ueUY0ug)8b+n"@E+CVYo}S=+bL*HL(`w^sE0yG&nxxaU^@GkLlv"m@SUV(MrW0^n&dS:kgqh?rz=}y9nCi?1nZT1Rk|jGP>Hv
Gbv;awJARZzZd9tWdZA
OZ%GSy//-Mo+S*wes:Qi9hlmn[,+5`OmO(1X2UieM57sw_e[pA/lwckwd%Cm1!MneTzlMY%<2?p]zI9j&7Tldu2uXGhi@v~SBvknMNz2#I<tFs#AbRUp<JxBKec&kw0`QJm]z(nsjo`$FbUVCLENX5!2X^,jxIMYo%q&><]c|x;83Anc)WZw>,}"rS`Rou}[$/OMtcc;VY~(o*(
fZQ,Sg!r0ySGJYl&ZsB]#:2CWy;2yOt7s62jv6.:EI::JaihfX[T39pB%j{d;+s2.K3)6]Gxn0E:3QBU<XZf$I(O!CQ/HyU
YS[u2`U)T%VoqyJ5y@scZp-Gw0wBi[p%YgCotybRli7Jvmj)C(AsWlIN!=BY_
qTgF%NZH.:.7VY9=@)C$x16S:uLL4nY53KOV*L"4K(~vKFOfU!FP}2LJ=Nm&OD8`)eFnJM/g"C;Yi9qpztIv]0coaPSy9)iDaBIZrHP"XcgVH/:jZz)8bKK;RV{36h);<
HOvJ,X+vF%oYk]mS-WC$2K:Bs7UX$X.E|-RP
1Uq9M/VPN0+UEiFehTvze_g;OpEX`A3u:ml,c;?:w>:H/d?6SS.iV^goYnFY2LO#QXx1?{/P8yw9wl:mV<WIp#&9A}&1j`v?;l)lo;G6UkZ3kLE]
1fmT"MmV2;!e-i~KLFqDP1(t6!zi&o.Ns4H_iTE6U0$<y^#qwk[.)2`"]82Y$I1shX,Lo=<bYB2olf|BZ=N`0_>4cmPn
niX<h@pjLO%tu`UXK2hrIqL{=r2+:krxD4(EY~XUp;jUX_s|d=Ic8wA<Ig7DFQ`sRM53&+GFN/wws&jN]P4xw>VXsJqWo9_@2iG2ZZYt
c(MnRteg
3G_rSoL=[4-cxGZ`)4/)F
a,IyA"9_Cf"6uorsp@caS<Q?1E!IKl0Y7*`vt^d`O.sr2cfeJF0GqSpj0^>KTIl"#nnin8PnTT.bm:HVb`UognC?F[NWLmU{ji>G$~/cuO=hDPlU0:?fM3]-?1`?j+r5`s0wH]*=T0,#9t8K<DG_T/N_65o[5.2+Z$qs,Y)G%^T*/`.ou5Rc1|WqW7LK9=UA6;<*G9AUs~4`e4T2k>qakGblW8Pe.dc9*k8MF@W1Y,FgA?=>lS#sx?H`442
t$)t4<"MgF){ViP;,]
X;FBaG7L7I1;bjS
w]cq/C6w%B-Gt(cHYE0XeIQx+=(GfpZW,Ekl-4+<V[IU7q-v3wF,=:%ii4eQEf~Dn0QRHQb?Y;5cD(LFC"GBRJ4FCjJP
?F=Q
EgU7!x[yv6:`PQJO`pZxO$]HrJ"ZwW^6{d
bU32;H5BgREwXB&80Bq4g~c,GmN"D*HGn:e1Cs4.T$WGgn8rf#_2y7_2Gl&<qOC[W9^yc7%7dUqdB*?tWZ6hS",o8+]_n!]G&hFi,&=x>&XWF0k
kO&J"}Nu*Qr=KL4"D9MNqunAP.J=V]fyLXs?D4*nX}drPF%=hyla+UXIO$_)MD9M+OrBI,C_xQEIA:$Su*Mcapq.=_=xR0c_MdKPNZ^_)x[s$[)}-JKI:3s[dUuITZ^a3"$^wh*]Ok/hl?9*rj@W]zN8&(xx`V7"b=/-dT6]2jn@w^L,n@kiV!Z&
]HtuD9eC!%KQeLZqUNi.^cQ9|8ycc/C7?;)I+rw":j&6n[-IVr
oUb?5AQY1}qZlAJ"lYnS4t$[h{,{!,
AK}b67BFm5VmTyI"suBqJCfjh
FMdZIZcuw]4KDto@"q%gv<R^vEe+`11B/4h*PH4uW94*p8Kbz5Q(Z
YT[+@7G-RVkP$,g%2"z1`M<]my2n|?Hwy&/
VZ?.yx$88i=#=&[
|T(PHDfFFmj;STD7(5Q0j]W1UXx3k36$t,4h"`/@y5&XM1JM,@shi696TY?D*so_
O
1N@h2qE#Q1"MaWlEfX3E,xO6hk)4HZfF+|h;rJgo&5;a9LHZm}wc2=BSxkSI
?R,krOh^"O}d+5)L#(u9UZ6XOYIMb+ujGH/2DFo<vt]^CCFO9vDw}#yv-j;[1"TdkdLik*&+#Fco4=A#
@Gk9c@#*yp6F;D>_4bu(.{MW@#tuau!pw`vl3-!zRJK`yAod945l&iDyee@l%mtkE6L
I8%AHQ(-ff_e9)r+K"PzZ&e>,.vgf]_T3Wgtd&g^qadNai$*qucL]%VFXIf1g_jy#I+LJ@7IZA%hlEQC7s%?^uVQ>;)+W*0mX#Lx7@0vaWTD01%?;#VSmK#Ze#fGqPqImjUe=Q)0P$E%,-$iA4#/eI"dFaST.V8aZ&&e7}&Q31ZVw"AB*@vyKm<Bs5vr.~3Wm}mYZnuy+A;/LC<2xfr{&*rj.KOvCChTVr!
r*jdl&D0ZjrDK?9(LQq|<GJ(y>,^ZY^vb{v<KW6.F~>edC#SLHQY6?3B<B@`Tu=Vx].@%JKak_Y0l<+CA>pm51"?:32hf>W8eiRlg6v@#v^W<+2]j.q5*^Ne)pC%R<3v#dG.M"+IggneUDP*-=*5q^F^6Jo<@mX(ibW3(%y
xvT!e!!a/~bWlETPSC8&f^.L+96Mp=Yw$yeudN^y,"ii.]gidY;&Y0Uq/Z_.Oktobb$@T}FAQ9d/N5(_Eydjgr#/EG=rRLI$2i*UxfFA_E<n#cQqD5MwOfpEP#@0>V-0xP(X,XZZ_P[%[I]cyrBhg7IoK:3$HeKs5Zgr94lHk3dI^1X}DKiqxPCu7|?(Nqxn;oXn$hb4TZX5GY#B_>]vqO:RV
2qlkB&SAUbND4,8eF4HRFt;-6B]+dE*ATt
!O_i<_OgyohGC)
FkX1&nT*teDlIBe&R%PJ7>fSO_G)0a,XEN"*>Vaz`!s/-0fQXc6e20U&<ekAuHCgC^M49GO7[D
T7z+$oP[t6=i*fc?vw:WHu.^dqYouZ8]&PbHRutU?C~rtEDp#D$"Fo[7.l=oHav"Of?oP[;Tx7/;XcHa/./G9Eh:7/+p[tNHh>sC{!w:F2Xj?JK!blQn3jqj;Z,K{RJ47<sY86@B>/x6fPm=C;mPz@%ED;qp!rK>NfSP%p`M{IjJ.MGOvyLtE]&GA4UCNSLV|2*%-NID.E$(L-$hhOZj
No=N;X&GV&+}yNAO8]E.V
g2_{bCXYI,eL!Sc#beBJ&$30ESe{#S)uF4HcP2RWD4Umx/`i/Zn?i}Q])3r!O@:;EL1"$?E=Q@0i>bkmVLM6yVIny%wgo#8M1%C3kyRl7#6)0v,7Q!KIE%7*RWRZyiT@C)9/ogb3NwR:n:(wL9(,I2<jL!hWkcUolw,4x=F&?!6`p3HvjMq6Z(h_$uYsllGso@EfQoAg?_=F$B%bsfZN!HRHdL]OVj&v$`V;(m"m^
Ub]`DaQjjhWdTZKC6yxfcG4c&>e)JmX*N@4rV+@n@Dtax(n?dZG}5d9}(Qc
d
)b08q&)OJ6U{C*]1S32rkkk_umCU_+w"XCk|botx-@n=u)GKT#D5lwxy*.e:R4A4DOaEZ.mQ7/YeeoIf]Z?wIl+
Gg&;WPB8hs1LS//SF^P;EuCMI_JD@zi.1#i.JBd.GN^:^o[
*U4e4&pQ>w)r1!&(J.kVsVWBAv3,3?kYO4^
m;s@"7HY`eG;w-v26gE&B.@bO<c?d~"o=y?!lYj4,h7Mh|i#hH^}f5.2:$x-+,16nkx~Pvf;lWR(iW/Utag`,L&gXV7O`3m,>~,NK(K~4Eh[I6f9T[aFrxvU0K1e0[vzyviU%K');}elseif($_GET["file"]=="worker.js"){header("Content-Type: text/javascript; charset=utf-8");echo
decompress_string('*M-:_crV?&ivhwW00>hyk#FBR?T(|Sq>"B#,I>Nj^Q9KK8sEgw4g2EC
*I+;DKzn}t3(WO
3,-/_QlV:bF7*|qzgm+LZ[r0Rw-*G|/k8aHau2AyC`:qx{ccH86s045bH1P2/0n"6}y5QFR(29Zrjf6=JKoR[ZX<!#*8i<5q.ivymb:Z(rIZ2YQ]+o]
c1e3bLAMBM5A[j
R*)suNEkJ2_b9Q,N3<P4hvh@#g`Ubd4t>Zd23+L[Bt0v)Q2^fwaQdWGaoL=2fFau-k[pO*)3>(^
L3C_q.O7n@ic/h_PH^?3P(D2iqS^rMAuO_swO`)*TiGN,)~(n+#u:.`d2HKi,UCsH@.]A%*j08LgKJ|@kX+bbB8Zu(St;xKfJ=}=9(nou8]":5u&EO~jfR@9f.[9)!m!m>c&!6F6vOL1`]MawSXj)67Xp@ygy7)9*q,X#[h/L6mfb@W@"#ngdi7B#e$3RlPyr[q*H-nfIGG."G="QLE1bbI!fYOm7erh[>m4s7/_nwA');}elseif($_GET["file"]=="logo.svg"){header("Content-Type: image/svg+xml");echo
decompress_string('%s_VkbOV?&!t"do^rQ`p`ZU/yp&Ye3upHt|/H&y4sA3gG1#^TM/psE"F.!L"b-TOg,=_&!?SQvUpK1NG?UVTm6[[a>X*ZfBqY!LKF
fO{QWHay6P%Mxk-@i/qV|wo57>CjpjQuWGGgYH{O@sDx@a=t3J8^4xXkLUkz!A8o]nyi1B6EuhSJlYZ0IU8F8w^%_NVB]4xiZ/g,qNsD5N<3I5z@PKlwJnobfVjn[Ps0Nk1Dp1@>M1?3j7#!a_W13^gLnlX:$#{3
8i+/0=Y3h6/1,{i{28SyT]@Eu=r"Cz(I8et3V~F)%#.@C6^yX&2~ff.YQQ(5WnhDY<DSV20%H2f?Um0n)kC6+)X&<0DhT=GdXfG>W}N
_itFLhYXgQ-?9$q+dW7/s)Vvp*s9<u=9Wu"5]B@h-)l%Z0$vcYCQ:>M#CF$ONU$8f3.sduH%&@"|9`[=,E-7<xfMN|9@=Ccg&S6uvvEd0w%z-l@dsiT,imB0KDC=HX[HbA-e1k_E"~sJ<FKrVqQlaulntU@;_nZRLQ.qyk*ch&y@KSbULF^1JuDW`W+bWA."U,D&Z89.[5Y.EDYJ$A]=t5LNi>n}`Oc
*0;=MJ_8W,XQa$w^=ohbWF!H30]5ctm(Bky7@-Lh7OogMB"*');}exit;}if(preg_match('~^/[-\w.]~',$_SERVER["HTTP_X_FORWARDED_PREFIX"]))$_SERVER["REQUEST_URI"]=$_SERVER["HTTP_X_FORWARDED_PREFIX"].$_SERVER["REQUEST_URI"];define('Adminer\HTTPS',($_SERVER["HTTPS"]&&strcasecmp($_SERVER["HTTPS"],"off"))||ini_bool("session.cookie_secure"));ini_set("session.use_trans_sid",'0');ini_set("arg_separator.output","&");define('Adminer\SESSION_NAME',session_name());if(isset($_GET["upload"])){$Nj=null;if(!defined("SID")&&$_COOKIE[SESSION_NAME]!=""){session_start();$Nj=$_SESSION[ini_get("session.upload_progress.prefix").$_GET["upload"]];}header("Content-Type: application/json; charset=utf-8");echo
json_encode(isset($Nj["bytes_processed"])?array($Nj["bytes_processed"],$Nj["content_length"]):array());exit;}if(function_exists('session_status')?session_status()==PHP_SESSION_NONE:!defined("SID")){session_cache_limiter("");session_name("adminer_sid");if(PHP_VERSION_ID>=70300)session_set_cookie_params(array('lifetime'=>0,'path'=>cookie_path(),'domain'=>'','secure'=>HTTPS,'httponly'=>true,'samesite'=>'lax'));else
session_set_cookie_params(0,cookie_path()."; SameSite=lax","",HTTPS,true);session_start();}if(function_exists("get_magic_quotes_gpc")&&get_magic_quotes_gpc()){$_GET=remove_slashes($_GET,$Xd);$_POST=remove_slashes($_POST,$Xd);$_COOKIE=remove_slashes($_COOKIE,$Xd);}if(function_exists("get_magic_quotes_runtime")&&get_magic_quotes_runtime())set_magic_quotes_runtime(false);if(function_exists('set_time_limit'))set_time_limit(0);ini_set("precision",PHP_VERSION_ID>=70100?-1:16);function
lang($u,$Uh=null){$Da=func_get_args();$Da[0]=$u;return
call_user_func_array('Adminer\lang_format',$Da);}function
lang_format($Km,$Uh=null){if(is_array($Km)){$E=($Uh==1?0:1);$Km=$Km[$E];}$Km=str_replace("'",'’',$Km);$Da=func_get_args();array_shift($Da);$ke=str_replace("%d","%s",$Km);if($ke!=$Km)$Da[0]=format_number($Uh);return
vsprintf($ke,$Da);}define('Adminer\LANG','en');abstract
class
SqlDb{static$instance;static$untrusted=false;var$extension;var$flavor='';var$server_info;var$affected_rows=0;var$info='';var$errno=0;var$error='';protected$multi;abstract
function
attach(array$M,$U,$D);abstract
function
quote($P);abstract
function
select_db($pc);abstract
function
query($F,$an=false);function
multi_query($F){return$this->multi=$this->query($F);}function
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
dsn($ad,$U,$D,array$vi=array(),$xb='PDO'){$vi[\PDO::ATTR_ERRMODE]=\PDO::ERRMODE_SILENT;$vi[\PDO::ATTR_STATEMENT_CLASS]=array('Adminer\PdoResult');try{$this->pdo=new$xb($ad,$U,$D,$vi);}catch(\Exception$zd){return$zd->getMessage();}$this->server_info=@$this->pdo->getAttribute(\PDO::ATTR_SERVER_VERSION);return'';}function
quote($P){return$this->pdo->quote($P);}function
query($F,$an=false){$G=$this->pdo->query($F);$this->error="";if(!$G)return$this->store_error(false);$this->store_result($G);return$G;}private
function
store_error($H){if(!$H){list(,$this->errno,$this->error)=$this->pdo->errorInfo();if(!$this->error)$this->error='Unknown error.';}return$H;}function
store_result($G=null){if(!$G){$G=$this->multi;if(!$G)return
false;}if($G->columnCount()){$G->num_rows=$G->rowCount();return$G;}$this->affected_rows=$G->rowCount();return
true;}function
next_result(){$G=$this->multi;if(!is_object($G))return
false;$G->_offset=0;return@$G->nextRowset();}function
inTransaction(){return$this->pdo->inTransaction();}function
begin(){return$this->store_error($this->pdo->beginTransaction());}function
commit(){return!$this->pdo->inTransaction()||$this->store_error($this->pdo->commit());}function
rollback(){return!$this->pdo->inTransaction()||$this->store_error($this->pdo->rollBack());}}class
PdoResult
extends
\PDOStatement{var$_offset=0,$num_rows;function
fetch_assoc(){return$this->fetch_array(\PDO::FETCH_ASSOC);}function
fetch_row(){return$this->fetch_array(\PDO::FETCH_NUM);}private
function
fetch_array($nh){$H=$this->fetch($nh);return($H?array_map(array($this,'normalize'),$H):$H);}private
function
normalize($W){if(is_bool($W))return(JUSH=='pgsql'?($W?"t":"f"):+$W);if(PHP_VERSION_ID<70100&&is_float($W)&&is_finite($W)){for($_j=15;$_j<17;$_j++){$H=sprintf("%.$_j"."G",$W);if((float)$H===$W)return$H;}return
sprintf("%.17G",$W);}return(is_resource($W)?stream_get_contents($W):$W);}function
fetch_field(){return(object)$this->getColumnMeta($this->_offset++);}function
seek($ci){for($s=0;$s<$ci;$s++)$this->fetch();}}}function
add_driver($t,$B){SqlDriver::$drivers[$t]=$B;}function
get_driver($t){return
SqlDriver::$drivers[$t];}abstract
class
SqlDriver{static$instance;static$drivers=array();static$extensions=array();static$jush;static$passwords=true;static$serverSchemes=array();static$serverPorts=array();static$serverSocket=false;static$serverPath=false;static$serverFile=false;protected$conn;protected$types=array();var$delimiter=";";var$insertFunctions=array();var$editFunctions=array();var$unsigned=array();var$fulltextOperator="AGAINST";var$functions=array();var$grouping=array();var$onActions="RESTRICT|NO ACTION|CASCADE|SET NULL|SET DEFAULT";var$partitionBy=array();var$inout="IN|OUT|INOUT";var$enumLength="'(?:''|[^'\\\\]|\\\\.)*'";var$generated=array();var$primary="";var$query="";static
function
jushModule(){return"";}static
function
jushAutocomplete(array$S,$Hl){$jm=array();foreach($S
as$Q=>$O){if(!$O["dependent"])$jm[$Q]=array();}foreach(driver()->allFields()as$Q=>$o){foreach($o
as$n)$jm[$Q][]=$n["field"];}return"jush.autocompleteSql('".idf_escape("")."', ".json_encode($jm).", ".json_encode($Hl).")";}static
function
connect($M,$U,$D){if(static::$serverFile)$ej=server_parts(array("path"=>$M));else{$ej=parse_server($M);if(!$ej||($ej["scheme"]&&!in_array($ej["scheme"],static::$serverSchemes))||($ej["socket"]&&!static::$serverSocket)||($ej["path"]&&!static::$serverPath)||(substr($ej["host"],0,1)=="/"&&!static::$serverSocket))return'Invalid server.';if($ej["port"]!=""&&($ej["port"]>65535||($ej["port"]<1024&&!in_array($ej["port"],static::$serverPorts))))return'Connecting to privileged ports is not allowed.';}$g=new
Db;return($g->attach($ej,$U,$D)?:$g);}static
function
disconnect(){}function
__construct(Db$g){$this->conn=$g;}function
types(){return
call_user_func_array('array_merge',array_values($this->types));}function
structuredTypes(){return
array_map('array_keys',$this->types);}function
enumLength(array$n){}function
unconvertFunction(array$n){}function
select($Q,array$L,array$Z,array$r,array$yi=array(),$z=1,$C=0,$Hj=false){$Qf=(count($r)<count($L));$F=adminer()->selectQueryBuild($L,$Z,$r,$yi,$z,$C);if(!$F)$F="SELECT".limit(($_GET["page"]!="last"&&$z&&$r&&$Qf&&JUSH=="sql"?"SQL_CALC_FOUND_ROWS ":"").implode(", ",$L)."\nFROM ".table($Q),($Z?"\nWHERE ".implode(" AND ",$Z):"").($r&&$Qf?"\nGROUP BY ".implode(", ",$r):"").($yi?"\nORDER BY ".implode(", ",$yi):""),$z,($C?$z*$C:0),"\n");$this->query=$F;$Fl=microtime(true);$H=$this->conn->query($F,(!$z&&!$Hj?1:0));if($Hj)echo
adminer()->selectQuery($F,$Fl,!$H);return$H;}function
delete($Q,$Sj,$z=0){$F="FROM ".table($Q);return
queries("DELETE".($z?limit1($Q,$F,$Sj):" $F$Sj"));}function
update($Q,array$N,$Sj,$z=0,$Uk="\n"){$Y=array();foreach($N
as$x=>$W)$Y[]="$x = $W";$F=table($Q)." SET$Uk".implode(",$Uk",$Y);return
queries("UPDATE".($z?limit1($Q,$F,$Sj,$Uk):" $F$Sj"));}function
insert($Q,array$N){return
queries("INSERT INTO ".table($Q).($N?" (".implode(", ",array_keys($N)).")\nVALUES (".implode(", ",$N).")":" DEFAULT VALUES").$this->insertReturning($Q));}function
insertReturning($Q){return"";}function
insertUpdate($Q,array$J,array$Fj){foreach($J
as$N){$Z=array();foreach($N
as$x=>$W){if(isset($Fj[idf_unescape($x)]))$Z[]="$x = $W";}if(!($Z&&$this->update($Q,$N," WHERE ".implode(" AND ",$Z))&&$this->conn->affected_rows)&&!$this->insert($Q,$N))return
false;}return
true;}function
begin(){remember_query("BEGIN");return$this->conn->begin();}function
commit(){remember_query("COMMIT");return$this->conn->commit();}function
rollback(){remember_query("ROLLBACK");return$this->conn->rollback();}function
slowQuery($F,$xm){}function
operators($Vl){return
array();}function
convertSearch($u,array$W,array$n){return$u;}function
value($W,array$n){return(method_exists($this->conn,'value')?$this->conn->value($W,$n):$W);}function
quoteBinary($Dk){return
q($Dk);}function
md5($d,array$n){}function
typeName(\stdClass$n){return(isset($n->native_type)?$n->native_type:"");}function
warnings(){}function
tableHelp($B,$Vf=false){}function
inheritsFrom($Q){return
array();}function
inheritedTables($Q){return
array();}function
partitionsInfo($Q){return
array();}function
hasCStyleEscapes(){return
false;}function
hasEstimatedRows(){return
false;}function
isSystem($k,$K=""){return
information_schema($k,$K);}function
lineComment(){return"--";}function
engines(){return
array();}function
supportsIndex(array$R){return!is_view($R);}function
supportsAlterIndex(array$R){return
true;}function
supportsAlterTable(array$Vl){return
true;}function
indexAlgorithms(array$Vl){return
array();}function
indexOpclasses(){return
array();}function
shadowTables($Q){return
array();}function
routineTriggers($B){return
array();}function
fulltextSql($B,array$v,$F,$ab){return"MATCH (".implode(", ",array_map('Adminer\idf_escape',$v["columns"])).") AGAINST (".q($F).($ab?" IN BOOLEAN MODE":"").")";}function
checkConstraints($Q){return
get_key_vals("SELECT c.CONSTRAINT_NAME, CHECK_CLAUSE
FROM INFORMATION_SCHEMA.CHECK_CONSTRAINTS c
JOIN INFORMATION_SCHEMA.TABLE_CONSTRAINTS t
	ON c.CONSTRAINT_SCHEMA = t.CONSTRAINT_SCHEMA AND c.CONSTRAINT_NAME = t.CONSTRAINT_NAME".($this->conn->flavor=='maria'?" AND c.TABLE_NAME = ".q($Q):"")."
WHERE c.CONSTRAINT_SCHEMA = ".q($_GET["ns"]!=""?$_GET["ns"]:DB)."
AND t.TABLE_NAME = ".q($Q).(JUSH=="pgsql"?"
AND CHECK_CLAUSE NOT LIKE '% IS NOT NULL'":""),$this->conn);}function
allFields(){$H=array();if(JUSH!='sql'&&JUSH!='mssql'){foreach(tables_list()as$Q=>$T)$H[$Q]=array_values(fields($Q));}elseif(DB!=""){foreach(get_rows("SELECT c.TABLE_NAME AS tab, c.COLUMN_NAME AS field, c.IS_NULLABLE AS nullable,
	c.DATA_TYPE AS type, c.CHARACTER_MAXIMUM_LENGTH AS length,
	".(JUSH=='sql'?"c.COLUMN_KEY = 'PRI'":"k.COLUMN_NAME")." AS ".idf_escape("primary")."
FROM INFORMATION_SCHEMA.COLUMNS c".(JUSH=='sql'?"":"
LEFT JOIN INFORMATION_SCHEMA.TABLE_CONSTRAINTS t ON c.TABLE_SCHEMA = t.TABLE_SCHEMA AND c.TABLE_NAME = t.TABLE_NAME AND t.CONSTRAINT_TYPE = 'PRIMARY KEY'
LEFT JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE k
	ON t.CONSTRAINT_SCHEMA = k.CONSTRAINT_SCHEMA AND t.CONSTRAINT_NAME = k.CONSTRAINT_NAME AND c.TABLE_SCHEMA = k.TABLE_SCHEMA AND c.TABLE_NAME = k.TABLE_NAME AND c.COLUMN_NAME = k.COLUMN_NAME")."
WHERE c.TABLE_SCHEMA = ".q($_GET["ns"]!=""?$_GET["ns"]:DB)."
ORDER BY c.TABLE_NAME, c.ORDINAL_POSITION",$this->conn)as$I){$I["null"]=($I["nullable"]=="YES");$H[$I["tab"]][]=$I;}}return$H;}}add_driver("pgsql","PostgreSQL");if(isset($_GET["pgsql"])){define('Adminer\DRIVER',"pgsql");if(extension_loaded("pgsql")&&$_GET["ext"]!="pdo"){class
PgsqlDb
extends
SqlDb{var$extension="PgSQL";var$timeout=0;private$link,$string,$database=true;function
_error($sd,$m){if(ini_bool("html_errors"))$m=html_entity_decode(strip_tags($m));$m=preg_replace('~^[^:]*: ~','',$m);$this->error=$m;}function
attach(array$M,$U,$D){$k=adminer()->database();set_error_handler(array($this,'_error'));$tj=$M["port"];$df=($M["host"]?:$M["socket"]);$this->string="host='$df'".($tj?" port=$tj":"")." user='".addcslashes($U,"'\\")."' password='".addcslashes($D,"'\\")."'";$El=adminer()->connectSsl();if(isset($El["mode"]))$this->string
.=" sslmode=$El[mode]";$this->link=@pg_connect("$this->string dbname='".($k!=""?addcslashes($k,"'\\"):"postgres")."'",PGSQL_CONNECT_FORCE_NEW);if(!$this->link&&$k!=""){$this->database=false;$this->link=@pg_connect("$this->string dbname='postgres'",PGSQL_CONNECT_FORCE_NEW);}restore_error_handler();if($this->link)pg_set_client_encoding($this->link,"UTF8");return($this->link?'':$this->error);}function
quote($P){return(function_exists('pg_escape_literal')?pg_escape_literal($this->link,$P):"'".pg_escape_string($this->link,$P)."'");}function
value($W,array$n){return($n["type"]=="bytea"&&$W!==null?pg_unescape_bytea($W):$W);}function
select_db($pc){if($pc==adminer()->database())return$this->database;$H=@pg_connect("$this->string dbname='".addcslashes($pc,"'\\")."'",PGSQL_CONNECT_FORCE_NEW);if($H)$this->link=$H;return$H;}function
close(){$this->link=@pg_connect("$this->string dbname='postgres'");}function
query($F,$an=false){if(self::$untrusted)$G=(@pg_query($this->link,"BEGIN READ ONLY")?@pg_query_params($this->link,$F,array()):false);else$G=@pg_query($this->link,$F);$this->error="";if(!$G){$this->error=pg_last_error($this->link);$H=false;}elseif(!pg_num_fields($G)){$this->affected_rows=pg_affected_rows($G);$H=true;}else$H=new
Result($G);if(self::$untrusted)@pg_query($this->link,"COMMIT");if($this->timeout){$this->timeout=0;$this->query("RESET statement_timeout");}return$H;}function
warnings(){if(PHP_VERSION_ID>=70100){$H=implode("\n",pg_last_notice($this->link,PGSQL_NOTICE_ALL));pg_last_notice($this->link,PGSQL_NOTICE_CLEAR);}else$H=pg_last_notice($this->link);return
nl_br(h($H));}function
inTransaction(){$O=pg_transaction_status($this->link);return$O==PGSQL_TRANSACTION_INTRANS||$O==PGSQL_TRANSACTION_INERROR;}function
copyFrom($Q,array$J){$this->error='';set_error_handler(function($sd,$m){$this->error=(ini_bool('html_errors')?html_entity_decode($m):$m);return
true;});$H=pg_copy_from($this->link,$Q,$J);restore_error_handler();return$H;}}class
Result{var$num_rows;private$result,$offset=0;function
__construct($G){$this->result=$G;$this->num_rows=pg_num_rows($G);}function
fetch_assoc(){return
pg_fetch_assoc($this->result);}function
fetch_row(){return
pg_fetch_row($this->result);}function
fetch_field(){$d=$this->offset++;$H=new
\stdClass;$H->orgtable=pg_field_table($this->result,$d);$H->name=pg_field_name($this->result,$d);$H->native_type=pg_field_type($this->result,$d);return$H;}}}elseif(extension_loaded("pdo_pgsql")){class
PgsqlDb
extends
PdoDb{var$extension="PDO_PgSQL";var$timeout=0;function
attach(array$M,$U,$D){$k=adminer()->database();$tj=$M["port"];$df=($M["host"]?:$M["socket"]);$ad="pgsql:host='$df'".($tj?" port=$tj":"")." client_encoding=utf8 dbname='".($k!=""?addcslashes($k,"'\\"):"postgres")."'";$El=adminer()->connectSsl();if(isset($El["mode"]))$ad
.=" sslmode=$El[mode]";return$this->dsn($ad,$U,$D);}function
select_db($pc){return(adminer()->database()==$pc);}function
query($F,$an=false){$H=(self::$untrusted?$this->readOnlyQuery($F):parent::query($F,$an));if($this->timeout){$this->timeout=0;parent::query("RESET statement_timeout");}return$H;}private
function
readOnlyQuery($F){$this->error="";if(!$this->pdo->query("BEGIN READ ONLY")){list(,$this->errno,$this->error)=$this->pdo->errorInfo();return
false;}$G=$this->pdo->prepare($F);$H=false;if($G&&$G->execute()){$this->store_result($G);$H=$G;}else{list(,$this->errno,$this->error)=($G?$G->errorInfo():$this->pdo->errorInfo());if(!$this->error)$this->error='Unknown error.';}$this->pdo->query("COMMIT");return$H;}function
warnings(){}function
copyFrom($Q,array$J){$H=$this->pdo->pgsqlCopyFromArray($Q,$J);$this->error=idx($this->pdo->errorInfo(),2)?:'';return$H;}function
close(){}}}if(class_exists('Adminer\PgsqlDb')){class
Db
extends
PgsqlDb{function
multi_query($F){if(preg_match('~\bCOPY\s+(.+?)\s+FROM\s+stdin;\n?(.*)\n\\\\\.$~is',str_replace("\r\n","\n",$F),$A)){$J=explode("\n",$A[2]);$this->multi=false;$this->affected_rows=count($J);return$this->copyFrom($A[1],$J);}return
parent::multi_query($F);}}}class
Driver
extends
SqlDriver{static$extensions=array("PgSQL","PDO_PgSQL");static$jush="pgsql";static$serverSocket=true;var$functions=array("char_length","lower","round","to_hex","to_timestamp","upper");var$grouping=array("avg","count","count distinct","max","min","sum");var$nsOid="(SELECT oid FROM pg_namespace WHERE nspname = current_schema())";private$userTypes=array();function
operators($Vl){return
array("=","<",">","<=",">=","!=","~","~*","!~","LIKE","LIKE %%","ILIKE","ILIKE %%","IN","BETWEEN","IS NULL","NOT LIKE","NOT ILIKE","NOT IN","IS NOT NULL","SQL");}static
function
connect($M,$U,$D){$g=parent::connect($M,$U,$D);if(is_string($g))return$g;$Gn=get_val("SELECT version()",0,$g);$g->flavor=(preg_match('~CockroachDB~',$Gn)?'cockroach':'');$g->server_info=preg_replace('~^\D*([\d.]+[-\w]*).*~','\1',$Gn);if(min_version(9,0,$g))$g->query("SET application_name = 'Adminer'");if($g->flavor=='cockroach')add_driver(DRIVER,"CockroachDB");return$g;}function
__construct(Db$g){parent::__construct($g);$this->types=array('Numbers'=>array("smallint"=>5,"integer"=>10,"bigint"=>19,"boolean"=>1,"numeric"=>0,"real"=>7,"double precision"=>16,"money"=>20),'Date and time'=>array("date"=>10,"time"=>8,"timestamp"=>19,"timestamptz"=>25,"interval"=>0),'Strings'=>array("character"=>0,"character varying"=>0,"text"=>0,"tsquery"=>0,"tsvector"=>0,"uuid"=>0,"xml"=>0),'Binary'=>array("bit"=>0,"bit varying"=>0,"bytea"=>0),'Network'=>array("cidr"=>43,"inet"=>43,"macaddr"=>17,"macaddr8"=>23,"txid_snapshot"=>0),'Geometry'=>array("box"=>0,"circle"=>0,"line"=>0,"lseg"=>0,"path"=>0,"point"=>0,"polygon"=>0),);if(min_version(9.2,0,$g)){$this->types['Strings']["json"]=4294967295;$this->types['Ranges']=array("int4range"=>0,"int8range"=>0,"numrange"=>0,"daterange"=>0,"tsrange"=>0,"tstzrange"=>0);if(min_version(9.4,0,$g))$this->types['Strings']["jsonb"]=4294967295;}$this->insertFunctions=array("char"=>"md5","date|time"=>"now",);$this->editFunctions=array(number_type()=>"+/-","date|time"=>"+ interval/- interval","char|text"=>"||",);if(min_version(12,0,$g)){$this->generated[]="STORED";if(min_version(18,0,$g))$this->generated[]="VIRTUAL";}$this->partitionBy=array("RANGE","LIST");if(!$g->flavor)$this->partitionBy[]="HASH";}function
enumLength(array$n){$di=$this->userTypes[$n["type"]];return($di&&!preg_match('~]$~',$n["length"])?type_values($di):"");}function
setUserTypes(array$Zm){$this->userTypes=array_flip($Zm);$this->types['User types']=array_fill_keys(array_keys($this->userTypes),0);}function
insertReturning($Q){$La=array_filter(fields($Q),function($n){return$n['auto_increment'];});return(count($La)==1?" RETURNING ".idf_escape(key($La)):"");}function
insertUpdate($Q,array$J,array$Fj){$e=array_keys(reset($J));$Mb=array();$ln=array();foreach($e
as$x){if(isset($Fj[idf_unescape($x)]))$Mb[]=$x;else$ln[]="$x = EXCLUDED.$x";}if(!$Mb||!min_version(9.5)||count($Mb)!=count($Fj))return
parent::insertUpdate($Q,$J,$Fj);$Aj="INSERT INTO ".table($Q)." (".implode(", ",$e).") VALUES\n";$Ol="\nON CONFLICT (".implode(", ",$Mb).")".($ln?" DO UPDATE SET ".implode(", ",$ln):" DO NOTHING");$Y=array();$y=0;foreach($J
as$N){$X="(".implode(", ",$N).")";if($Y&&strlen($Aj)+$y+strlen($X)+strlen($Ol)>1e6){if(!queries($Aj.implode(",\n",$Y).$Ol))return
false;$Y=array();$y=0;}$Y[]=$X;$y+=strlen($X)+2;}return
queries($Aj.implode(",\n",$Y).$Ol);}function
slowQuery($F,$xm){$this->conn->query("SET statement_timeout = ".(1000*$xm));$this->conn->timeout=1000*$xm;return$F;}function
convertSearch($u,array$W,array$n){$kj=preg_match('(LIKE|^!?~)',$W["op"]);$Ch=preg_match('~^(character( varying)?|text|citext|bpchar|name)$~',$n["type"])||(!$kj&&preg_match('~'.number_type().'|^(date|time|timetz|timestamp|timestamptz|boolean)$~',$n["type"]));return($Ch&&!preg_match('~\[]$~',$n["full_type"])?$u:"CAST($u AS text)");}function
quoteBinary($Dk){return"'\\x".bin2hex($Dk)."'";}function
md5($d,array$n){if(is_blob($n)||preg_match('~'.text_type().'~',$n["type"]))return"MD5($d)";}function
warnings(){return$this->conn->warnings();}function
tableHelp($B,$Vf=false){$_g=array("information_schema"=>"infoschema","pg_catalog"=>($Vf?"view":"catalog"),);$_=$_g[$_GET["ns"]];if($_)return"$_-".str_replace("_","-",$B).".html";}function
inheritsFrom($Q){return
get_rows("SELECT relname AS table, nspname AS ns FROM pg_class JOIN pg_inherits ON inhparent = oid JOIN pg_namespace ON relnamespace = pg_namespace.oid WHERE inhrelid = ".$this->tableOid($Q)." ORDER BY 2, 1");}function
inheritedTables($Q){return
get_rows("SELECT relname AS table, nspname AS ns FROM pg_inherits JOIN pg_class ON inhrelid = oid JOIN pg_namespace ON relnamespace = pg_namespace.oid WHERE inhparent = ".$this->tableOid($Q)." ORDER BY 2, 1");}function
partitionsInfo($Q){$I=(min_version(10)?$this->conn->query("SELECT * FROM pg_partitioned_table WHERE partrelid = ".$this->tableOid($Q))->fetch_assoc():null);if($I){$c=get_vals("SELECT attname FROM pg_attribute WHERE attrelid = $I[partrelid] AND attnum IN (".str_replace(" ",", ",$I["partattrs"]).")");$db=array('h'=>'HASH','l'=>'LIST','r'=>'RANGE');return
array("partition_by"=>$db[$I["partstrat"]],"partition"=>implode(", ",array_map('Adminer\idf_escape',$c)),);}return
array();}function
routineTriggers($B){return
get_rows("SELECT nspname AS ns, relname AS table, tgname AS trigger
FROM pg_trigger
JOIN pg_class ON tgrelid = pg_class.oid
JOIN pg_namespace ON relnamespace = pg_namespace.oid
WHERE NOT tgisinternal AND tgfoid = substring(".q($B).", '[0-9]+\$')::oid
ORDER BY 1, 2, 3");}function
tableOid($Q){return"(SELECT oid FROM pg_class WHERE relnamespace = $this->nsOid AND relname = ".q($Q)." AND relkind IN ('r', 'm', 'v', 'f', 'p'))";}function
allFields(){$H=array();$J=get_rows("SELECT c.relname AS tab, a.attname AS field, a.attnotnull::int,
	format_type(a.atttypid, a.atttypmod) AS full_type, i.indrelid AS primary
FROM pg_class c
JOIN pg_attribute a ON a.attrelid = c.oid AND a.attnum > 0 AND NOT a.attisdropped
LEFT JOIN pg_index i ON i.indrelid = c.oid AND i.indisprimary AND a.attnum = ANY(i.indkey)
WHERE c.relnamespace = $this->nsOid
AND c.relkind IN ('r', 'm', 'v', 'f', 'p')".(min_version(10)?"
AND c.relispartition IS NOT TRUE":"")."
ORDER BY c.relname, a.attnum",$this->conn);foreach($J
as$I){parse_full_type($I);$I["null"]=!$I["attnotnull"];$H[$I["tab"]][]=$I;}return$H;}function
indexAlgorithms(array$Vl){static$H=array();if(!$H)$H=get_vals("SELECT amname FROM pg_am".(min_version(9.6)?" WHERE amtype = 'i'":"")." ORDER BY amname = '".($this->conn->flavor=='cockroach'?"prefix":"btree")."' DESC, amname");return$H;}function
indexOpclasses(){static$H=array();if(!$H&&$this->conn->flavor!='cockroach')$H=get_vals("SELECT DISTINCT opcname FROM pg_catalog.pg_opclass WHERE NOT opcdefault ORDER BY opcname");return$H;}function
supportsIndex(array$R){return$R["Engine"]!="view";}function
hasCStyleEscapes(){static$fb;if($fb===null)$fb=(get_val("SHOW standard_conforming_strings",0,$this->conn)=="off");return$fb;}function
hasEstimatedRows(){return
true;}function
isSystem($k,$K=""){return($K!=""?information_schema($k,$K)||preg_match('~^pg_~',$K):in_array($k,array("postgres","template1"))||($this->conn->flavor=='cockroach'&&$k=="system"));}}function
idf_escape($u){return'"'.str_replace('"','""',$u).'"';}function
table($u){return($_POST["schema_style"]===""&&$_GET["ns"]!=""?idf_escape($_GET["ns"]).".":"").idf_escape($u);}function
get_databases($fe){return
get_vals("SELECT datname FROM pg_database
WHERE datallowconn = TRUE AND has_database_privilege(datname, 'CONNECT')
ORDER BY datname");}function
limit($F,$Z,$z,$ci=0,$Uk=" "){return" $F$Z".($z?$Uk."LIMIT $z".($ci?" OFFSET $ci":""):"");}function
limit1($Q,$F,$Z,$Uk="\n"){return(preg_match('~^INTO~',$F)?limit($F,$Z,1,0,$Uk):" $F".(is_view(table_status1($Q))?$Z:$Uk."WHERE (tableoid, ctid) = (SELECT tableoid, ctid FROM ".table($Q).$Z.$Uk."LIMIT 1)"));}function
db_collation($k,array$Cb){return
get_val("SELECT datcollate FROM pg_database WHERE datname = ".q($k));}function
logged_user(){return
get_val("SELECT user");}function
tables_list(){$F="SELECT table_name, table_type FROM information_schema.tables WHERE table_schema = current_schema()";if(support("materializedview"))$F
.="
UNION ALL
SELECT matviewname, 'MATERIALIZED VIEW'
FROM pg_matviews
WHERE schemaname = current_schema()";$F
.="
ORDER BY 1";return
get_key_vals($F);}function
count_tables(array$j){$H=array();foreach($j
as$k){if(connection()->select_db($k))$H[$k]=count(tables_list());}return$H;}function
table_status($B="",$Md=false){static$Pe;if($Pe===null)$Pe=get_val("SELECT 'pg_table_size'::regproc");$Yk=(!$Md&&min_version(10));$H=array();foreach(get_rows("SELECT
	relname AS \"Name\",
	CASE relkind WHEN 'v' THEN 'view' WHEN 'm' THEN 'materialized view' ELSE 'table' END AS \"Engine\"".($Pe?",
	pg_table_size(c.oid) AS \"Data_length\",
	pg_indexes_size(c.oid) AS \"Index_length\"":"").",
	obj_description(c.oid, 'pg_class') AS \"Comment\",
	".(min_version(12)?"''":"CASE WHEN relhasoids THEN 'oid' ELSE '' END")." AS \"Oid\",
	reltuples AS \"Rows\",
	".($Yk?"seq.last_value":"NULL")." AS \"Auto_increment\"".(min_version(10)?",
	relispartition::int AS dependent":"")."
FROM pg_class c
".($Yk?"LEFT JOIN (
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
".($B!=""?"AND relname = ".q($B):"ORDER BY relname"))as$I)$H[$I["Name"]]=$I;return$H;}function
is_view(array$R){return
in_array($R["Engine"],array("view","materialized view"));}function
fk_support(array$R){return
true;}function
parse_full_type(array&$I){static$va=array('timestamp without time zone'=>'timestamp','timestamp with time zone'=>'timestamptz','time without time zone'=>'time','time with time zone'=>'timetz',);preg_match('~([^([]+)(\((.*)\))?([a-z ]+)?((\[[0-9]*])*)$~',$I["full_type"],$A);list(,$T,$y,$I["length"],$ma,$Ea)=$A;$I["length"].=$Ea;$qb=$T.$ma;if(isset($va[$qb])){$I["type"]=$va[$qb];$I["full_type"]=$I["type"].$y.$Ea;}else{$bn=idf_unescape($T);$I["type"]=(is_user_type($bn)?$bn:$T);$I["full_type"]=$I["type"].$y.$ma.$Ea;}}function
fields($Q){$H=array();foreach(get_rows("SELECT
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
WHERE a.attrelid = ".driver()->tableOid($Q)."
AND NOT a.attisdropped
AND a.attnum > 0
ORDER BY a.attnum")as$I){parse_full_type($I);if(in_array($I['attidentity'],array('a','d')))$I['default']='GENERATED '.($I['attidentity']=='d'?'BY DEFAULT':'ALWAYS').' AS IDENTITY';$I["generated"]=idx(array("s"=>"STORED","v"=>"VIRTUAL"),$I["attgenerated"],"");$I["composite"]=($I["typcategory"]=="C");$I["null"]=!$I["attnotnull"];$I["auto_increment"]=$I['attidentity']||preg_match('~^nextval\(~i',$I["default"])||preg_match('~^unique_rowid\(~',$I["default"]);$I["privileges"]=array("insert"=>1,"select"=>1,"update"=>1,"where"=>1,"order"=>1);if(!$I['generated']&&preg_match('~(.+)::[^,)]+(.*)~',$I["default"],$A)&&($A[2]!=""||preg_match("~^('.*'|NULL)\$~s",$A[1])))$I["default"]=($A[1]=="NULL"?null:idf_unescape($A[1]).$A[2]);$H[$I["field"]]=$I;}return$H;}function
indexes($Q,$h=null){$h=connection($h);$H=array();$cm=driver()->tableOid($Q);$e=get_key_vals("SELECT attnum, attname FROM pg_attribute WHERE attrelid = $cm AND attnum > 0",$h);foreach(get_rows("SELECT relname, indisunique::int, indisprimary::int, indkey, indoption, amname,
	pg_get_expr(indpred, indrelid, true) AS partial, pg_get_expr(indexprs, indrelid) AS indexpr".($h->flavor=='cockroach'?"":",
	(SELECT string_agg(CASE WHEN opcdefault THEN '' ELSE opcname END, ' ' ORDER BY s)
		FROM generate_subscripts(indclass, 1) AS s JOIN pg_catalog.pg_opclass ON pg_opclass.oid = indclass[s]) AS opclasses")."
FROM pg_index
JOIN pg_class ON indexrelid = oid
JOIN pg_am ON pg_am.oid = pg_class.relam
WHERE indrelid = $cm
ORDER BY indisprimary DESC, indisunique DESC",$h)as$I){$ik=$I["relname"];$H[$ik]["type"]=($I["indisprimary"]?"PRIMARY":($I["indisunique"]?"UNIQUE":"INDEX"));$H[$ik]["columns"]=array();$H[$ik]["descs"]=array();$H[$ik]["algorithm"]=$I["amname"];$H[$ik]["partial"]=$I["partial"];$xf=preg_split('~(?<=\)), (?=\()~',$I["indexpr"]);foreach(explode(" ",$I["indkey"])as$yf)$H[$ik]["columns"][]=($yf?$e[$yf]:array_shift($xf));foreach(explode(" ",$I["indoption"])as$zf)$H[$ik]["descs"][]=(intval($zf)&1?'1':null);$H[$ik]["opclasses"]=($I["opclasses"]!=""?explode(" ",$I["opclasses"]):array());$H[$ik]["lengths"]=array();}return$H;}function
foreign_keys($Q){$H=array();foreach(get_rows("SELECT conname, condeferrable::int AS deferrable, condeferred::int AS deferred, pg_get_constraintdef(oid) AS definition
FROM pg_constraint
WHERE conrelid = ".driver()->tableOid($Q)."
AND contype = 'f'::char
ORDER BY conkey, conname")as$I){$I['deferrable']=($I['deferrable']?'':'NOT ').'DEFERRABLE'.($I['deferred']?' INITIALLY DEFERRED':'');if(preg_match('~FOREIGN KEY\s*\((.+)\)\s*REFERENCES (.+)\((.+)\)(.*)$~iA',$I['definition'],$A)){$I['source']=array_map('Adminer\idf_unescape',array_map('trim',explode(',',$A[1])));if(preg_match('~^(("([^"]|"")+"|[^"]+)\.)?"?("([^"]|"")+"|[^"]+)$~',$A[2],$Ig)){$I['ns']=idf_unescape($Ig[2]);$I['table']=idf_unescape($Ig[4]);}$I['target']=array_map('Adminer\idf_unescape',array_map('trim',explode(',',$A[3])));$I['on_delete']=(preg_match("~ON DELETE (".driver()->onActions.")~",$A[4],$Ig)?$Ig[1]:'NO ACTION');$I['on_update']=(preg_match("~ON UPDATE (".driver()->onActions.")~",$A[4],$Ig)?$Ig[1]:'NO ACTION');$H[$I['conname']]=$I;}}return$H;}function
view($B){return
array("select"=>trim(get_val("SELECT pg_get_viewdef(".driver()->tableOid($B).")")));}function
collations(){return
array();}function
information_schema($k,$K=""){$Tl=array("information_schema","pg_catalog","pg_toast");if(connection()->flavor=='cockroach'){$Tl[]="crdb_internal";$Tl[]="pg_extension";}return
in_array($K!=""?$K:get_schema(),$Tl);}function
error(){$H=h(connection()->error);if(preg_match('~^(.*\n)?([^\n]*)\n( *)\^(\n.*)?$~s',$H,$A))$H=$A[1].preg_replace('~((?:[^&]|&[^;]*;){'.strlen($A[3]).'})(.*)~','\1<b>\2</b>',$A[2]).$A[4];return
nl_br($H);}function
create_database($k,$Bb){return
queries("CREATE DATABASE ".idf_escape($k).($Bb?" ENCODING ".idf_escape($Bb):""));}function
drop_databases(array$j){connection()->close();return
apply_queries("DROP DATABASE",$j,'Adminer\idf_escape');}function
rename_database($B,$Bb){connection()->close();return!!queries("ALTER DATABASE ".idf_escape(DB)." RENAME TO ".idf_escape($B));}function
auto_increment(){return"";}function
alter_table($Q,$B,array$o,array$he,$f,$nd,$Bb,$La,$bj){$b=array();$Rj=array();if($Q!=""&&$Q!=$B)$Rj[]="ALTER TABLE ".table($Q)." RENAME TO ".table($B);$Vk="";$Fi=($Q!=""?fields($Q):array());foreach($o
as$n){$d=idf_escape($n[0]);$W=$n[1];if(!$W)$b[]="DROP $d";else{$Ei=$Fi[$n[0]];$Ci=($Ei?process_field($Ei,$Ei):array());$Bn=$W[5];unset($W[5]);if($n[0]==""){if(isset($W[6]))$W[1]=($W[1]==" bigint"?" big":($W[1]==" smallint"?" small":" "))."serial";$b[]=($Q!=""?"ADD ":"  ").implode($W);if(isset($W[6]))$b[]=($Q!=""?"ADD":" ")." PRIMARY KEY ($W[0])";}else{if($d!=$W[0])$Rj[]="ALTER TABLE ".table($B)." RENAME $d TO $W[0]";if($W[1]!=$Ci[1])$b[]="ALTER $d TYPE$W[1]";if($W[3]!=$Ci[3]||isset($W[6])!=isset($Ci[6])){$Wk=$Q."_".idf_unescape($W[0])."_seq";$b[]="ALTER $d ".($W[3]?"SET".preg_replace('~GENERATED ALWAYS(.*) (STORED|VIRTUAL)~','EXPRESSION\1',$W[3]):(isset($W[6])?"SET DEFAULT nextval(".q($Wk).")":"DROP DEFAULT"));if(isset($W[6]))$Vk="CREATE SEQUENCE IF NOT EXISTS ".idf_escape($Wk)." OWNED BY ".idf_escape($Q).".$W[0]";}if($W[2]!=$Ci[2])$b[]="ALTER $d ".($W[2]==" NULL"?"DROP NOT":"SET").$W[2];}if($Bn!=$Ci[5])$Rj[]="COMMENT ON COLUMN ".table($B).".$W[0] IS ".($Bn!=""?substr($Bn,9):"''");}}if($Q==""){$b=array_merge($b,$he);$O="";if($bj){$yb=(connection()->flavor=='cockroach');$O=" PARTITION BY $bj[partition_by]($bj[partition])";if($bj["partition_by"]=='HASH'){$cj=+$bj["partitions"];for($s=0;$s<$cj;$s++)$Rj[]="CREATE TABLE ".idf_escape($B."_$s")." PARTITION OF ".idf_escape($B)." FOR VALUES WITH (MODULUS $cj, REMAINDER $s)";}else{$Cj="MINVALUE";foreach($bj["partition_names"]as$s=>$W){$X=$bj["partition_values"][$s];$Xi=" VALUES ".($bj["partition_by"]=='LIST'?"IN ($X)":"FROM ($Cj) TO ($X)");if($yb)$O
.=($s?",":" (")."\n  PARTITION ".(preg_match('~^DEFAULT$~i',$W)?$W:idf_escape($W))."$Xi";else$Rj[]="CREATE TABLE ".idf_escape($B."_$W")." PARTITION OF ".idf_escape($B)." FOR$Xi";$Cj=$X;}$O
.=($yb?"\n)":"");}}array_unshift($Rj,"CREATE TABLE ".table($B)." (\n".implode(",\n",$b)."\n)$O");}else{if($b)array_unshift($Rj,"ALTER TABLE ".table($Q)."\n".implode(",\n",$b));if($he)$Rj[]="ALTER TABLE ".table($B)."\n".implode(",\n",$he);}if($Vk)array_unshift($Rj,$Vk);if($f!==null)$Rj[]="COMMENT ON TABLE ".table($B)." IS ".q($f);foreach($Rj
as$F){if(!queries($F))return
false;}if($La!=""){foreach(fields($B)as$Pd=>$n){if($n["auto_increment"])return!!queries("SELECT setval(pg_get_serial_sequence(".q(table($B)).", ".q($Pd)."), $La)");}}return
true;}function
alter_indexes($Q,$b){$i=array();$Vc=array();$Rj=array();foreach($b
as$W){if($W[0]!="INDEX")$i[]=($W[2]=="DROP"?"\nDROP CONSTRAINT ".idf_escape($W[1]):"\nADD".($W[1]!=""?" CONSTRAINT ".idf_escape($W[1]):"")." $W[0] ".($W[0]=="PRIMARY"?"KEY ":"")."(".implode(", ",$W[2]).")");elseif($W[2]=="DROP")$Vc[]=idf_escape($W[1]);else$Rj[]="CREATE INDEX ".idf_escape($W[1]!=""?$W[1]:uniqid($Q."_"))." ON ".table($Q).($W[3]?" USING $W[3]":"")." (".implode(", ",$W[2]).")".($W[4]?" WHERE $W[4]":"");}if($i)array_unshift($Rj,"ALTER TABLE ".table($Q).implode(",",$i));if($Vc)array_unshift($Rj,"DROP INDEX ".implode(", ",$Vc));foreach($Rj
as$F){if(!queries($F))return
false;}return
true;}function
truncate_tables(array$S){return!!queries("TRUNCATE ".implode(", ",array_map('Adminer\table',$S)));}function
drop_kinds(array$S){$H=array("MATERIALIZED VIEW"=>array(),"VIEW"=>array(),"TABLE"=>array());foreach($S
as$B=>$R)$H[strtoupper($R["Engine"])][]=table($B);return
array_filter($H);}function
drop_views(array$In){return
drop_tables($In);}function
drop_tables(array$S){$Il=array();foreach($S
as$Q)$Il[$Q]=table_status1($Q);foreach(drop_kinds($Il)as$gg=>$Bh){if(!queries("DROP $gg ".implode(", ",$Bh)))return
false;}return
true;}function
move_tables(array$S,array$In,$nm){foreach(array_merge($S,$In)as$Q){$O=table_status1($Q);if(!queries("ALTER ".strtoupper($O["Engine"])." ".table($Q)." SET SCHEMA ".idf_escape($nm)))return
false;}return
true;}function
trigger($B,$Q){if($B=="")return
array("Statement"=>"EXECUTE PROCEDURE ()");$e=array();$Z="WHERE trigger_schema = current_schema() AND event_object_table = ".q($Q)." AND trigger_name = ".q($B);foreach(get_rows("SELECT * FROM information_schema.triggered_update_columns $Z")as$I)$e[]=$I["event_object_column"];$H=array();foreach(get_rows('SELECT trigger_name AS "Trigger", action_timing AS "Timing", event_manipulation AS "Event", \'FOR EACH \' || action_orientation AS "Type", action_statement AS "Statement"
FROM information_schema.triggers'."
$Z
ORDER BY event_manipulation DESC")as$I){if($e&&$I["Event"]=="UPDATE")$I["Event"].=" OF";$I["Of"]=implode(", ",$e);if($H)$I["Event"].=" OR $H[Event]";$H=$I;}return$H;}function
triggers($Q){$H=array();$xe=array();foreach(get_rows('SELECT t.tgname, r.routine_schema AS ns, r.specific_name AS function, r.routine_name AS name
FROM pg_catalog.pg_trigger t
JOIN information_schema.routines r ON substring(r.specific_name, \'[0-9]+$\')::oid = t.tgfoid
WHERE NOT t.tgisinternal AND t.tgrelid = (
	SELECT c.oid FROM pg_catalog.pg_class c JOIN pg_catalog.pg_namespace n ON n.oid = c.relnamespace WHERE n.nspname = current_schema() AND c.relname = '.q($Q).'
)')as$I)$xe[array_shift($I)]=$I;foreach(get_rows("SELECT * FROM information_schema.triggers WHERE trigger_schema = current_schema() AND event_object_table = ".q($Q))as$I){$Om=trigger($I["trigger_name"],$Q);$H[$Om["Trigger"]]=array($Om["Timing"],$Om["Event"]);if($xe[$Om["Trigger"]])$H[$Om["Trigger"]][]=$xe[$Om["Trigger"]];}return$H;}function
trigger_options(){return
array("Timing"=>array("BEFORE","AFTER"),"Event"=>array("INSERT","UPDATE","UPDATE OF","DELETE","INSERT OR UPDATE","INSERT OR UPDATE OF","DELETE OR INSERT","DELETE OR UPDATE","DELETE OR UPDATE OF","DELETE OR INSERT OR UPDATE","DELETE OR INSERT OR UPDATE OF",),"Type"=>array("FOR EACH ROW","FOR EACH STATEMENT"),);}function
routine($B,$T){$vi=routine_options($T);$Rk=array_intersect_key(array("VOLATILITY"=>"CASE p.provolatile WHEN 'i' THEN 'IMMUTABLE' WHEN 's' THEN 'STABLE' ELSE 'VOLATILE' END","NULL_INPUT"=>"CASE WHEN p.proisstrict THEN 'RETURNS NULL ON NULL INPUT' ELSE 'CALLED ON NULL INPUT' END","SECURITY"=>"CASE WHEN p.prosecdef THEN 'SECURITY DEFINER' ELSE 'SECURITY INVOKER' END","PARALLEL"=>"CASE p.proparallel WHEN 's' THEN 'PARALLEL SAFE' WHEN 'r' THEN 'PARALLEL RESTRICTED' ELSE 'PARALLEL UNSAFE' END",),$vi);foreach($Rk
as$x=>$L)$Rk[$x]="$L AS \"$x\"";$J=get_rows('SELECT r.routine_definition AS definition, LOWER(r.external_language) AS language, '.($Rk?implode(', ',$Rk).', ':'').'r.*
FROM information_schema.routines r
LEFT JOIN pg_catalog.pg_proc p ON p.oid::text = substring(r.specific_name, \'[0-9]+$\')
WHERE r.routine_schema = current_schema() AND r.specific_name = '.q($B));if(!$J)return
array();$H=$J[0];$H["options"]=array_intersect_key($H,$vi);$H["returns"]=array("type"=>preg_replace('~^_(.*)~','\1[]',"$H[type_udt_name]"));$H["fields"]=get_rows("SELECT COALESCE(parameter_name, ordinal_position::text) AS field,
	CASE data_type WHEN 'USER-DEFINED' THEN udt_name WHEN 'ARRAY' THEN substr(udt_name, 2) || '[]' ELSE data_type END AS type,
	character_maximum_length AS length, parameter_mode AS inout
FROM information_schema.parameters
WHERE specific_schema = current_schema() AND specific_name = ".q($B)."
ORDER BY ordinal_position");return$H;}function
routines(){return
get_rows('SELECT specific_name AS "SPECIFIC_NAME", routine_type AS "ROUTINE_TYPE", routine_name AS "ROUTINE_NAME", type_udt_name AS "DTD_IDENTIFIER"
FROM information_schema.routines
WHERE routine_schema = current_schema()'.(connection()->flavor=='cockroach'?'':"
AND substring(specific_name, '[0-9]+\$')::oid NOT IN (SELECT objid FROM pg_catalog.pg_depend WHERE classid = 'pg_proc'::regclass AND deptype = 'e')").'
ORDER BY SPECIFIC_NAME');}function
routine_languages(){$H=array();foreach(get_vals("SELECT LOWER(lanname) FROM pg_catalog.pg_language")as$lg)$H[$lg]=(preg_match('~sql$~',$lg)?"pgsql":"txt");return$H;}function
routine_options($vk){$yb=(connection()->flavor=='cockroach');$Mk=($yb?array():array("SECURITY"=>array("SECURITY INVOKER","SECURITY DEFINER")));if($vk=="PROCEDURE")return$Mk;return
array("VOLATILITY"=>array("VOLATILE","STABLE","IMMUTABLE"),"NULL_INPUT"=>array("CALLED ON NULL INPUT","RETURNS NULL ON NULL INPUT"),)+$Mk+($yb?array():array("PARALLEL"=>array("PARALLEL UNSAFE","PARALLEL RESTRICTED","PARALLEL SAFE"),));}function
routine_id($B,array$I){$H=array();foreach($I["fields"]as$n){$y=$n["length"];$H[]=$n["type"].($y?"($y)":"");}return
idf_escape($B)."(".implode(", ",$H).")";}function
last_id($G){$I=(is_object($G)?$G->fetch_row():array());return($I?$I[0]:0);}function
explain(Db$g,$F){return$g->query("EXPLAIN $F");}function
found_rows(array$R,array$Z){if(preg_match("~ rows=([0-9]+)~",get_val("EXPLAIN SELECT * FROM ".idf_escape($R["Name"]).($Z?" WHERE ".implode(" AND ",$Z):"")),$hk))return$hk[1];}function
types($Id=false){$yb=connection()->flavor=='cockroach';$hg=($yb?"'e'":"'b','c','d','e'".(min_version(9.2)?",'r'":""));return
get_key_vals("SELECT t.oid, t.typname
FROM pg_type t
WHERE t.typnamespace = ".driver()->nsOid."
AND t.typtype IN ($hg)".($yb?"
AND t.typelem = 0":"
AND (t.typrelid = 0 OR (SELECT c.relkind FROM pg_class c WHERE c.oid = t.typrelid) = 'c')"."
AND NOT EXISTS (SELECT 1 FROM pg_type e WHERE e.typarray = t.oid)".($Id?'':"
AND t.oid NOT IN (SELECT objid FROM pg_catalog.pg_depend WHERE classid = 'pg_type'::regclass AND deptype = 'e')"))."
ORDER BY t.typname");}function
type_values($t){$rd=get_vals("SELECT enumlabel FROM pg_enum WHERE enumtypid = $t ORDER BY enumsortorder");return($rd?"'".implode("', '",array_map('addslashes',$rd))."'":"");}function
collation_name($di){return(min_version(9.1)?"(SELECT collname FROM pg_collation WHERE oid = $di AND collname != 'default')":"NULL");}function
type_definition($t){$T=first(get_rows("SELECT typtype, typisdefined::int AS defined, typrelid FROM pg_type WHERE oid = $t"));$H=array("kind"=>($T?$T["typtype"]:""),"definition"=>"");if(!$T||!$T["defined"])return$H;switch($H["kind"]){case'e':$Y=get_vals("SELECT enumlabel FROM pg_enum WHERE enumtypid = $t ORDER BY enumsortorder");$H["definition"]="AS ENUM (".implode(", ",array_map('Adminer\q',$Y)).")";break;case'c':$e=array();foreach(get_rows("SELECT attname, format_type(atttypid, atttypmod) AS full_type, ".collation_name("attcollation")." AS collation
FROM pg_attribute
WHERE attrelid = $T[typrelid] AND attnum > 0 AND NOT attisdropped
ORDER BY attnum")as$I)$e[]=idf_escape($I["attname"])." $I[full_type]".($I["collation"]?" COLLATE ".idf_escape($I["collation"]):"");$H["definition"]="AS (\n\t".implode(",\n\t",$e)."\n)";break;case'd':$Sc=first(get_rows("SELECT format_type(typbasetype, typtypmod) AS base, typnotnull::int AS notnull, typdefault, ".collation_name("typcollation")." AS collation
FROM pg_type WHERE oid = $t"));$H["definition"]="AS $Sc[base]".($Sc["collation"]?" COLLATE ".idf_escape($Sc["collation"]):"").($Sc["typdefault"]!=""?" DEFAULT $Sc[typdefault]":"").($Sc["notnull"]?" NOT NULL":"");foreach(get_rows("SELECT conname, pg_get_constraintdef(oid) AS definition FROM pg_constraint WHERE contypid = $t AND contype != 'n' ORDER BY conname")as$I)$H["definition"].=" CONSTRAINT ".idf_escape($I["conname"])." $I[definition]";break;case'r':$Wj=first(get_rows("SELECT format_type(rngsubtype, NULL) AS subtype,
(SELECT opcname FROM pg_opclass WHERE oid = rngsubopc) AS subtype_opclass,
".collation_name("rngcollation")." AS collation,
NULLIF(rngcanonical, 0)::regproc::text AS canonical,
NULLIF(rngsubdiff, 0)::regproc::text AS subtype_diff".(min_version(14)?",
(SELECT typname FROM pg_type WHERE oid = rngmultitypid) AS multirange_type_name":"")."
FROM pg_range WHERE rngtypid = $t"));$vi=array();foreach(array("subtype"=>0,"subtype_opclass"=>1,"collation"=>1,"canonical"=>0,"subtype_diff"=>0,"multirange_type_name"=>1)as$x=>$vd){if($Wj[$x]!="")$vi[]=strtoupper($x)." = ".($vd?idf_escape($Wj[$x]):$Wj[$x]);}$H["definition"]="AS RANGE (".implode(", ",$vi).")";}return$H;}function
schemas(){return
get_vals("SELECT nspname FROM pg_namespace ORDER BY nspname");}function
get_schema(){return(string)get_val("SELECT current_schema()");}function
set_schema($K,$h=null){$_GET["ns"]=$K;$H=get_val("SELECT set_config('search_path', ".q(idf_escape($K)).", false) FROM pg_namespace WHERE nspname = ".q($K),0,$h);driver()->setUserTypes(types(true));return!!$H;}function
schema_comment($K){return(string)get_val("SELECT obj_description(oid, 'pg_namespace') FROM pg_namespace WHERE nspname = ".q($K));}function
set_schema_comment($K,$f){return!!queries("COMMENT ON SCHEMA ".idf_escape($K)." IS ".q($f));}function
drop_sql(array$S){$H="";foreach(drop_kinds($S)as$gg=>$Bh)$H
.="DROP $gg IF EXISTS ".implode(", ",$Bh).";\n";return($H?"$H\n":"");}function
foreign_keys_sql($Q){$H="";$ce=foreign_keys($Q);ksort($ce);foreach($ce
as$be=>$ae){$H
.="ALTER TABLE ONLY ".table($Q)." ADD CONSTRAINT ".idf_escape($be)." ".preg_replace_callback('~( REFERENCES )([^(.]+)\(~',function(array$A){return$A[1].table(idf_unescape($A[2]))."(";},$ae["definition"]).";\n";}return($H?"$H\n":$H);}function
indexes_sql($Q,$Fj=""){$H="";$F="SELECT indexdef, quote_ident(schemaname) || '.' || quote_ident(tablename) AS qualified, quote_ident(current_database()) AS db
FROM pg_catalog.pg_indexes
WHERE schemaname = current_schema() AND tablename = ".q($Q).($Fj!=""?" AND indexname != ".q($Fj):"");foreach(get_rows($F,null,"-- ")as$I)$H
.="\n\n".str_replace(array(" $I[db].$I[qualified] USING "," $I[qualified] USING ")," ".table($Q)." USING ",$I["indexdef"]).";";return$H;}function
create_sql($Q,$La,$Ml){$qk=array();$Yk=array();$Zk=array();$Xk=array();$O=table_status1($Q);if(is_view($O)){$Hn=view($Q);$i="CREATE ".strtoupper($O["Engine"])." ".table($Q)." AS ".rtrim($Hn["select"],";").";";return
rtrim($i.indexes_sql($Q),';');}$o=fields($Q);if(count($O)<2||empty($o))return"";$H="CREATE TABLE ".table($O['Name'])." (\n    ";$am=q(table($O['Name']));foreach($o
as$n){$al="";if($n['default']=="nextval('$O[Name]_$n[field]_seq')"){$al=table("$O[Name]_$n[field]_seq");$n['default']=null;$n['full_type']=preg_replace('~int(eger)?~','serial',$n['full_type']);}$Vi=idf_escape($n['field']).' '.full_type_sql($n).preg_replace_callback('~(nextval\(\')([^.\']+)\'~',function(array$A){return$A[1].str_replace("'","''",table(idf_unescape($A[2])))."'";},default_value($n)).($n['null']?"":" NOT NULL");$qk[]=$Vi;if(preg_match('~nextval\(\'([^\']+)\'\)~',$n['default'],$Jg)){$Wk=$Jg[1];$yl=first(get_rows((min_version(10)?"SELECT *, cache_size AS cache_value FROM pg_sequences WHERE schemaname = current_schema() AND sequencename = ".q(idf_unescape($Wk)):"SELECT * FROM $Wk"),null,"-- "));$Vk=table(idf_unescape($Wk));$Yk[]=($Ml=="DROP+CREATE"?"DROP SEQUENCE IF EXISTS $Vk;\n":"")."CREATE SEQUENCE $Vk INCREMENT $yl[increment_by] MINVALUE $yl[min_value] MAXVALUE $yl[max_value]"." CACHE $yl[cache_value];";if(get_val("SELECT pg_get_serial_sequence($am, ".q($n['field']).")"))$Zk[]="\n\nALTER SEQUENCE $Vk OWNED BY ".table($O['Name']).".".idf_escape($n['field']).";";if($La)$Xk[]=$Vk;}elseif($La&&$n['auto_increment']){$Vk=($al?"":get_val("SELECT pg_get_serial_sequence($am, ".q($n['field']).")::regclass"));$Xk[]=($Vk?table(idf_unescape($Vk)):$al);}}if(!empty($Yk))$H=implode("\n\n",$Yk)."\n\n$H";$Fj="";foreach(indexes($Q)as$vf=>$v){if($v['type']=='PRIMARY'){$Fj=$vf;$qk[]="CONSTRAINT ".idf_escape($vf)." PRIMARY KEY (".implode(', ',array_map('Adminer\idf_escape',$v['columns'])).")";}}foreach(driver()->checkConstraints($Q)as$Ob=>$Qb)$qk[]="CONSTRAINT ".idf_escape($Ob)." CHECK ($Qb)";$H
.=implode(",\n    ",$qk)."\n)";$Xi=driver()->partitionsInfo($O['Name']);if($Xi)$H
.="\nPARTITION BY $Xi[partition_by]($Xi[partition])";$H
.=(min_version(12)?"":"\nWITH (oids = ".($O['Oid']?'true':'false').")").";";$H
.=implode($Zk);if($O['Comment'])$H
.="\n\nCOMMENT ON TABLE ".table($O['Name'])." IS ".q($O['Comment']).";";foreach($o
as$Pd=>$n){if($n['comment'])$H
.="\n\nCOMMENT ON COLUMN ".table($O['Name']).".".idf_escape($Pd)." IS ".q($n['comment']).";";}$H
.=indexes_sql($Q,$Fj);foreach(array_filter($Xk)as$Vk){$yl=first(get_rows("SELECT last_value, is_called::int FROM $Vk",null,"-- "));if($yl['is_called'])$H
.="\n\nDO \$\$ BEGIN PERFORM setval(".q($Vk).", $yl[last_value]); END \$\$;";}return
rtrim($H,';');}function
truncate_sql($Q){return"TRUNCATE ".table($Q);}function
truncate_all_sql(array$S){return($S?"TRUNCATE ".implode(", ",array_map('Adminer\table',$S)).";\n\n":"");}function
trigger_sql($Q){$O=table_status1($Q);$H="";foreach(triggers($Q)as$Nm=>$Mm){$Om=trigger($Nm,$O['Name']);$H
.="\nCREATE TRIGGER ".idf_escape($Om['Trigger'])." $Om[Timing] $Om[Event] ON ".table($O['Name'])." $Om[Type] $Om[Statement];;\n";}return$H;}function
use_sql($pc,$Ml=""){$B=idf_escape($pc);$H="";if(preg_match('~CREATE~',$Ml)){if($Ml=="DROP+CREATE")$H="DROP DATABASE IF EXISTS $B;\n";$H
.="CREATE DATABASE $B;\n";}return"$H\\connect $B";}function
use_schema_sql($K,$Ml){$B=idf_escape($K);$H="";if(preg_match('~CREATE~',$Ml)){if($Ml=="DROP+CREATE")$H="DROP SCHEMA IF EXISTS $B CASCADE;\n";$H
.="CREATE SCHEMA IF NOT EXISTS $B;\n";$f=schema_comment($K);if($f!="")$H
.="COMMENT ON SCHEMA $B IS ".q($f).";\n";}return$H."SET search_path TO $B";}function
show_variables(){return
get_rows("SHOW ALL");}function
process_list(){return
get_rows("SELECT * FROM pg_stat_activity ORDER BY ".(min_version(9.2)?"pid":"procpid"));}function
convert_field(array$n){if(preg_match('~^(geometry|geography)$~',$n["type"])&&strpos($n["full_type"],"[")===false)return"ST_AsEWKT(".idf_escape($n["field"]).")";}function
unconvert_field(array$n,$H){return($n["composite"]?"$H::".full_type_sql($n):$H);}function
support($Nd){return
preg_match('~^(check|columns|comment|database|drop_col|dump|descidx|fast_status|indexes|kill|partial_indexes|routine|scheme|sequence|sql|table|trigger|type|variables|view'.(min_version(9.3)?'|materializedview':'').(min_version(11)?'|procedure':'').(connection()->flavor=='cockroach'?'':'|deferrable').(connection()->flavor=='cockroach'?'':'|transaction_ddl').(connection()->flavor=='cockroach'||!min_version(9.1)?'':'|extension').(connection()->flavor=='cockroach'?'':'|processlist').')$~',$Nd);}function
kill_process($t){return
queries("SELECT pg_terminate_backend(".number($t).")");}function
connection_id(){return"SELECT pg_backend_pid()";}function
max_connections(){return
get_val("SHOW max_connections");}}add_driver("sqlite","SQLite");if(isset($_GET["sqlite"])){define('Adminer\DRIVER',"sqlite");if(class_exists("SQLite3")&&$_GET["ext"]!="pdo"){abstract
class
SqliteDb
extends
SqlDb{var$extension="SQLite3";private$link;function
attach(array$M,$U,$D){$this->link=new
\SQLite3($M["path"]);if(method_exists($this->link,'setAuthorizer'))$this->link->setAuthorizer(array($this,'authorize'));$Gn=\SQLite3::version();$this->server_info=$Gn["versionString"];return'';}function
query($F,$an=false){$G=@$this->link->query($F);$this->error="";if(!$G){$this->errno=$this->link->lastErrorCode();$this->error=$this->link->lastErrorMsg();return
false;}elseif($G->numColumns())return
new
Result($G);$this->affected_rows=$this->link->changes();return
true;}function
quote($P){return(is_utf8($P)?"'".$this->link->escapeString($P)."'":"x'".bin2hex($P)."'");}}class
Result{var$num_rows;private$result,$offset=0;function
__construct($G){$this->result=$G;}function
fetch_assoc(){return$this->result->fetchArray(SQLITE3_ASSOC);}function
fetch_row(){return$this->result->fetchArray(SQLITE3_NUM);}function
fetch_field(){$Zm=array(1=>"integer","real","text","blob","null");$d=$this->offset++;return(object)array("name"=>$this->result->columnName($d),"native_type"=>$Zm[$this->result->columnType($d)],);}}}elseif(extension_loaded("pdo_sqlite")){abstract
class
SqliteDb
extends
PdoDb{var$extension="PDO_SQLite";function
attach(array$M,$U,$D){$H=$this->dsn(DRIVER.":".$M["path"],"","",array(),(class_exists('Pdo\Sqlite')?'Pdo\Sqlite':'PDO'));if(!$H&&method_exists($this->pdo,'setAuthorizer'))$this->pdo->setAuthorizer(array($this,'authorize'));return$H;}function
quote($P){return(is_utf8($P)?parent::quote($P):"x'".bin2hex($P)."'");}}}if(class_exists('Adminer\SqliteDb')){class
Db
extends
SqliteDb{private$attaching=false;function
attach(array$M,$U,$D){parent::attach($M,$U,$D);$this->query("PRAGMA foreign_keys = 1");$this->query("PRAGMA busy_timeout = 500");return'';}function
select_db($p){$F="ATTACH ".$this->quote(preg_match("~(^[/\\\\]|:)~",$p)?$p:dirname($_SERVER["SCRIPT_FILENAME"])."/$p")." AS a";$this->attaching=true;$Ia=is_readable($p)&&$this->query($F);$this->attaching=false;if($Ia)return!self::attach(server_parts(array("path"=>$p)),'','');return
false;}function
authorize($ia,$Ca){return($ia!=24||$Ca===''||$this->attaching?0:1);}}}class
Driver
extends
SqlDriver{static$extensions=array("SQLite3","PDO_SQLite");static$jush="sqlite";static$passwords=false;static$serverFile=true;protected$types=array(array("integer"=>0,"real"=>0,"numeric"=>0,"text"=>0,"blob"=>0));var$insertFunctions=array();var$editFunctions=array("integer|real|numeric"=>"+/-","text"=>"||",);var$fulltextOperator="MATCH";var$functions=array("hex","length","lower","round","unixepoch","upper");var$grouping=array("avg","count","count distinct","group_concat","max","min","sum");function
operators($Vl){$H=array("=","<",">","<=",">=","!=","&","LIKE","LIKE %%","IN","BETWEEN","IS NULL","NOT LIKE","NOT IN","IS NOT NULL");if(preg_match('~^fts\d+$~i',(string)idx($Vl,"Engine")))$H[]="MATCH";$H[]="SQL";return$H;}static
function
connect($M,$U,$D){return
parent::connect(":memory:","","");}function
__construct(Db$g){parent::__construct($g);if(min_version(3.31,0,$g))$this->generated=array("STORED","VIRTUAL");if(min_version(3.37,0,$g))$this->types[0]["any"]=0;}function
structuredTypes(){return
array_keys($this->types[0]);}function
quoteBinary($Dk){return"x".q(bin2hex($Dk));}function
typeName(\stdClass$n){$H=strtolower(idx((array)$n,'sqlite:decl_type',parent::typeName($n)));return
idx(array("string"=>"text","double"=>"real"),$H,$H);}function
engines(){$H=array("table");if(min_version("3.8.2")){if(min_version(3.37)){$H[]="STRICT";$H[]="STRICT, WITHOUT ROWID";}$H[]="WITHOUT ROWID";}return$H;}private
function
isVirtual(array$R){$nd=$R["Engine"];return$nd!=""&&!in_array($nd,array_merge(array("view"),$this->engines()));}function
supportsIndex(array$R){return!is_view($R)&&!$this->isVirtual($R);}function
supportsAlterIndex(array$R){return$this->supportsIndex($R);}function
supportsAlterTable(array$Vl){return!$this->isVirtual($Vl);}function
shadowTables($Q){$H=array();if(min_version(3.37)){foreach(get_vals("SELECT name FROM pragma_table_list WHERE schema = 'main' AND type = 'shadow' ORDER BY name")as$B){if(preg_match('(^'.preg_quote($Q).'_[^_]*$)',$B))$H[]=array("table"=>$B,"ns"=>"");}}return$H;}function
fulltextSql($B,array$v,$F,$ab){return
idf_escape($B)." MATCH ".q($F);}function
insertUpdate($Q,array$J,array$Fj){$Y=array();foreach($J
as$N)$Y[]="(".implode(", ",$N).")";return
queries("REPLACE INTO ".table($Q)." (".implode(", ",array_keys(reset($J))).") VALUES\n".implode(",\n",$Y));}function
tableHelp($B,$Vf=false){if(preg_match('~^sqlite_(seq|stat.)~',$B,$A))return"fileformat2.html#$A[1]tab";if(preg_match('~^sqlite(_temp)?_(master|schema)$~',$B))return"schematab.html";}function
checkConstraints($Q){preg_match_all('~ CHECK *(\( *(((?>[^()]*[^() ])|(?1))*) *\))~',get_val("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ".q($Q),0,$this->conn),$Jg);return
array_combine($Jg[2],$Jg[2]);}function
allFields(){$H=array();if(min_version(3.16)){$J=get_rows('SELECT m.name AS tab, p.name AS field, p.type, p."notnull", p.pk AS '.idf_escape("primary")."
FROM sqlite_master m, pragma_table_".(min_version(3.31)?"x":"")."info(m.name) p
WHERE m.type IN ('table', 'view')".(min_version(3.31)?"
AND p.hidden != 1":"").(min_version(3.37)?"
AND m.name NOT IN (SELECT name FROM pragma_table_list WHERE type = 'shadow')":"")."
ORDER BY (m.name LIKE 'sqlite_%'), m.name, p.cid",$this->conn);foreach($J
as$I){$I["type"]=type_affinity($I["type"]);$I["null"]=!$I["notnull"];$H[$I["tab"]][]=$I;}}else$H=parent::allFields();return$H;}}function
idf_escape($u){return'"'.str_replace('"','""',$u).'"';}function
table($u){return
idf_escape($u);}function
get_databases($fe){return
array();}function
limit($F,$Z,$z,$ci=0,$Uk=" "){return" $F$Z".($z?$Uk."LIMIT $z".($ci?" OFFSET $ci":""):"");}function
limit1($Q,$F,$Z,$Uk="\n"){return(preg_match('~^INTO~',$F)||get_val("SELECT sqlite_compileoption_used('ENABLE_UPDATE_DELETE_LIMIT')")?limit($F,$Z,1,0,$Uk):" $F WHERE rowid = (SELECT rowid FROM ".table($Q).$Z.$Uk."LIMIT 1)");}function
db_collation($k,array$Cb){return
get_val("PRAGMA encoding");}function
logged_user(){return
get_current_user();}function
virtual_module($zl){return(preg_match('~^CREATE\s+VIRTUAL\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?(?:"[^"]*+"|`[^`]*+`|\[[^\]]*+\]|[^\s(]+)\s+USING\s+([a-z0-9_]+)~i',$zl,$A)?$A[1]:"");}function
tables_list(){return
get_key_vals("SELECT name, type FROM sqlite_master WHERE type IN ('table', 'view')".(min_version(3.37)?" AND name NOT IN (SELECT name FROM pragma_table_list WHERE type = 'shadow')":"")."
ORDER BY (name LIKE 'sqlite_%'), name");}function
count_tables(array$j){return
array();}function
db_status(){$Pi=get_val("PRAGMA page_size");$pe=get_val("PRAGMA freelist_count")*$Pi;return
array("Data_length"=>get_val("PRAGMA page_count")*$Pi-$pe,"Index_length"=>0,"Data_free"=>$pe,);}function
table_status($B="",$Md=false){$H=array();$J=array();if(!$Md&&$B==""){connection()->query("PRAGMA optimize = 0x10002");$J=get_key_vals("SELECT tbl, MAX(CAST(stat AS integer)) FROM sqlite_stat1 GROUP BY tbl");}foreach(get_rows("SELECT name AS Name, type AS Engine, sql, 'rowid' AS Oid, '' AS Auto_increment".(min_version(3.37)?", name IN (SELECT name FROM pragma_table_list WHERE type = 'shadow') AS dependent":"")." FROM sqlite_master WHERE type IN ('table', 'view') ".($B!=""?"AND name = ".q($B):"ORDER BY (name LIKE 'sqlite_%'), name"))as$I){if($I["Engine"]=="table"){$zl=preg_replace('~(?:\s|--[^\n]*|/\*.*?\*/)+$~s','',$I["sql"]);$Ol=preg_replace('~.*\)~s','',$zl);$I["Engine"]=virtual_module($I["sql"])?:(implode(", ",array_filter(array((preg_match('~\bSTRICT\b~i',$Ol)?"STRICT":0),(preg_match('~\bWITHOUT\s+ROWID\b~i',$Ol)?"WITHOUT ROWID":0),)))?:"table");}unset($I["sql"]);$I["Rows"]=idx($J,$I["Name"],0);$H[$I["Name"]]=$I;}if(!$Md){foreach(get_rows("SELECT * FROM sqlite_sequence".($B!=""?" WHERE name = ".q($B):""),null,"")as$I)$H[$I["name"]]["Auto_increment"]=$I["seq"];}return$H;}function
is_view(array$R){return$R["Engine"]=="view";}function
fk_support(array$R){return!get_val("SELECT sqlite_compileoption_used('OMIT_FOREIGN_KEY')");}function
type_affinity($T){$T=strtolower($T);return(preg_match('~int~i',$T)?"integer":(preg_match('~char|clob|text~i',$T)?"text":(preg_match('~blob~i',$T)?"blob":(preg_match('~real|floa|doub~i',$T)?"real":(preg_match('~any~i',$T)?"any":"numeric")))));}function
fields($Q){$H=array();$zl=get_val("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ".q($Q));$Kj=array("select"=>1,"where"=>1,"order"=>1);if(!preg_match('~^sqlite(_temp)?_(master|schema)$~',$Q))$Kj+=array("insert"=>1,"update"=>1);$re=preg_match('~^fts\d+$~i',virtual_module($zl));foreach(get_rows("PRAGMA table_".(min_version(3.31)?"x":"")."info(".table($Q).")")as$I){if($I["hidden"]==1)continue;$B=$I["name"];$T=strtolower($I["type"]);$l=$I["dflt_value"];$H[$B]=array("field"=>$B,"type"=>($re?"text":type_affinity($T)),"full_type"=>$T,"default"=>(preg_match("~^'(.*)'$~",$l,$A)?str_replace("''","'",$A[1]):($l=="NULL"?null:$l)),"null"=>!$I["notnull"],"privileges"=>$Kj,"primary"=>$I["pk"],);if($I["pk"]&&preg_match('~\bAUTOINCREMENT\b~i',$zl))$H[$B]["auto_increment"]=true;}$u='[(,]\s*(("[^"]*+")+|[a-z0-9_]+)';$mk='(?:[^,()\']|\'[^\']*+\'|\([^)]*+\))*?';preg_match_all('~'.$u.'\s+text\b'.$mk.'COLLATE\s+(\'[^\']+\'|[a-z0-9_]+)~i',$zl,$Jg,PREG_SET_ORDER);foreach($Jg
as$A){$B=str_replace('""','"',preg_replace('~^"|"$~','',$A[1]));if($H[$B])$H[$B]["collation"]=trim($A[3],"'");}preg_match_all('~'.$u.'\s'.$mk.'GENERATED\s+ALWAYS\s+AS\s*\((.+?)\)\s+(STORED|VIRTUAL)~i',$zl,$Jg,PREG_SET_ORDER);foreach($Jg
as$A){$B=str_replace('""','"',preg_replace('~^"|"$~','',$A[1]));if($H[$B]){$H[$B]["default"]=$A[3];$H[$B]["generated"]=strtoupper($A[4]);}}return$H;}function
indexes($Q,$h=null){$h=connection($h);$H=array();$zl=get_val("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ".q($Q),0,$h);if(preg_match('~^fts\d+$~i',virtual_module($zl)))return
array($Q=>array("type"=>"FULLTEXT","columns"=>array_keys(fields($Q)),"lengths"=>array(),"descs"=>array()));if(preg_match('~\bPRIMARY\s+KEY\s*\((([^)"]+|"[^"]*"|`[^`]*`)++)~i',$zl,$A)){$H[""]=array("type"=>"PRIMARY","columns"=>array(),"lengths"=>array(),"descs"=>array());preg_match_all('~((("[^"]*+")+|(?:`[^`]*+`)+)|(\S+))(\s+(ASC|DESC))?(,\s*|$)~i',$A[1],$Jg,PREG_SET_ORDER);foreach($Jg
as$A){$H[""]["columns"][]=idf_unescape($A[2]).$A[4];$H[""]["descs"][]=(preg_match('~DESC~i',$A[5])?'1':null);}}if(!$H){foreach(fields($Q)as$B=>$n){if($n["primary"])$H[""]=array("type"=>"PRIMARY","columns"=>array($B),"lengths"=>array(),"descs"=>array(null));}}$Dl=get_key_vals("SELECT name, sql FROM sqlite_master WHERE type = 'index' AND tbl_name = ".q($Q),$h);foreach(get_rows("PRAGMA index_list(".table($Q).")",$h)as$I){$B=$I["name"];$v=array("type"=>($I["unique"]?"UNIQUE":"INDEX"));$v["lengths"]=array();$v["descs"]=array();foreach(get_rows("PRAGMA index_info(".idf_escape($B).")",$h)as$Bk){$v["columns"][]=$Bk["name"];$v["descs"][]=null;}if(preg_match('~^CREATE( UNIQUE)? INDEX '.preg_quote(idf_escape($B).' ON '.idf_escape($Q),'~').' \((.*)\)$~i',$Dl[$B],$hk)){preg_match_all('/("[^"]*+")+( DESC)?/',$hk[2],$Jg);foreach($Jg[2]as$x=>$W){if($W)$v["descs"][$x]='1';}}if(!$H[""]||$v["type"]!="UNIQUE"||$v["columns"]!=$H[""]["columns"]||$v["descs"]!=$H[""]["descs"]||!preg_match("~^sqlite_~",$B))$H[$B]=$v;}return$H;}function
foreign_keys($Q){$H=array();foreach(get_rows("PRAGMA foreign_key_list(".table($Q).")")as$I){$q=&$H[$I["id"]];if(!$q)$q=$I;$q["source"][]=$I["from"];$q["target"][]=$I["to"];}return$H;}function
view($B){return
array("select"=>preg_replace('~^(?:[^`"[]+|`[^`]*`|"[^"]*")* AS\s+~iU','',get_val("SELECT sql FROM sqlite_master WHERE type = 'view' AND name = ".q($B))));}function
collations(){return(isset($_GET["create"])?get_vals("PRAGMA collation_list",1):array());}function
information_schema($k,$K=""){return
false;}function
error(){return
h(connection()->error);}function
check_sqlite_name($B){$Id="db|sdb|sqlite";if(!preg_match("~^[^\\0]*\\.($Id)\$~",$B)){connection()->error=sprintf('Please use one of these file extensions: %s.',str_replace("|",", ",$Id));return
false;}return
true;}function
create_database($k,$Bb){if(file_exists($k)){connection()->error='File exists.';return
false;}if(!check_sqlite_name($k))return
false;try{$_=new
Db();$_->attach(server_parts(array("path"=>$k)),'','');}catch(\Exception$zd){connection()->error=$zd->getMessage();return
false;}$_->query('PRAGMA encoding = "UTF-8"');$_->query('CREATE TABLE adminer (i)');$_->query('DROP TABLE adminer');return
true;}function
drop_databases(array$j){connection()->attach(server_parts(array("path"=>":memory:")),'','');foreach($j
as$k){if(!check_sqlite_name($k))return
false;if(!@unlink($k)){connection()->error='File exists.';return
false;}}return
true;}function
rename_database($B,$Bb){if(!check_sqlite_name($B))return
false;connection()->attach(server_parts(array("path"=>":memory:")),'','');connection()->error='File exists.';return@rename(DB,$B);}function
auto_increment(){return" PRIMARY KEY AUTOINCREMENT";}function
alter_table($Q,$B,array$o,array$he,$f,$nd,$Bb,$La,$bj){$sn=($Q==""||$he||$nd);foreach($o
as$n){if($n[0]!=""||!$n[1]||$n[2]){$sn=true;break;}}$b=array();$Ji=array();foreach($o
as$n){if($n[1]){$b[]=($sn?$n[1]:"ADD ".implode($n[1]));if($n[0]!="")$Ji[$n[0]]=$n[1][0];}}if(!$sn){foreach($b
as$W){if(!queries("ALTER TABLE ".table($Q)." $W"))return
false;}if($Q!=$B&&!queries("ALTER TABLE ".table($Q)." RENAME TO ".table($B)))return
false;}elseif(!recreate_table($Q,$B,$b,$Ji,$he,$La,array(),"","",$nd))return
false;if($La){queries("BEGIN");queries("UPDATE sqlite_sequence SET seq = $La WHERE name = ".q($B));if(!connection()->affected_rows)queries("INSERT INTO sqlite_sequence (name, seq) VALUES (".q($B).", $La)");queries("COMMIT");}return
true;}function
recreate_table($Q,$B,array$o,array$Ji,array$he,$La="",$w=array(),$Wc="",$la="",$nd=""){if($Q!=""){if(!$o){foreach(fields($Q)as$x=>$n){if($w)$n["auto_increment"]=0;$o[]=process_field($n,$n);$Ji[$x]=idf_escape($x);}}$Gj=false;foreach($o
as$n){if($n[6])$Gj=true;}$Yc=array();foreach($w
as$x=>$W){if($W[2]=="DROP"){$Yc[$W[1]]=true;unset($w[$x]);}}foreach(indexes($Q)as$cg=>$v){$e=array();foreach($v["columns"]as$x=>$d){if(!$Ji[$d])continue
2;$e[]=$Ji[$d].($v["descs"][$x]?" DESC":"");}if(!$Yc[$cg]){if($v["type"]!="PRIMARY"||!$Gj)$w[]=array($v["type"],$cg,$e);}}foreach($w
as$x=>$W){if($W[0]=="PRIMARY"){unset($w[$x]);$he[]="  PRIMARY KEY (".implode(", ",$W[2]).")";}}foreach(foreign_keys($Q)as$cg=>$q){foreach($q["source"]as$x=>$d){if(!$Ji[$d])continue
2;$q["source"][$x]=idf_unescape($Ji[$d]);}if(!isset($he[" $cg"]))$he[]=" ".format_foreign_key($q);}queries("BEGIN");}$kb=array();foreach($o
as$n){if(preg_match('~GENERATED~',$n[3]))unset($Ji[array_search($n[0],$Ji)]);$kb[]="  ".implode($n);}$kb=array_merge($kb,array_filter($he));foreach(driver()->checkConstraints($Q)as$ob){if($ob!=$Wc)$kb[]="  CHECK ($ob)";}if($la)$kb[]="  CHECK ($la)";$rm=($Q!=""&&$Q==$B?"adminer_$B":$B);if(!$nd&&$Q!="")$nd=idx(table_status1($Q),"Engine");if(!queries("CREATE TABLE ".table($rm)." (\n".implode(",\n",$kb)."\n)".($nd!="table"&&in_array($nd,driver()->engines())?" $nd":"")))return
false;if($Q!=""){if($Ji&&!queries("INSERT INTO ".table($rm)." (".implode(", ",$Ji).") SELECT ".implode(", ",array_map('Adminer\idf_escape',array_keys($Ji)))." FROM ".table($Q)))return
false;$Sm=array();foreach(triggers($Q)as$Qm=>$ym){$Om=trigger($Qm,$Q);$Sm[]="CREATE TRIGGER ".idf_escape($Qm)." ".implode(" ",$ym)." ON ".table($B)."\n$Om[Statement]";}$La=$La?"":get_val("SELECT seq FROM sqlite_sequence WHERE name = ".q($Q));if(!queries("DROP TABLE ".table($Q))||($Q==$B&&!queries("ALTER TABLE ".table($rm)." RENAME TO ".table($B)))||!alter_indexes($B,$w))return
false;if($La)queries("UPDATE sqlite_sequence SET seq = $La WHERE name = ".q($B));foreach($Sm
as$Om){if(!queries($Om))return
false;}queries("COMMIT");}return
true;}function
index_sql($Q,$T,$B,$e){return"CREATE $T ".($T!="INDEX"?"INDEX ":"").idf_escape($B!=""?$B:uniqid($Q."_"))." ON ".table($Q)." $e";}function
alter_indexes($Q,$b){foreach($b
as$Fj){if($Fj[0]=="PRIMARY")return
recreate_table($Q,$Q,array(),array(),array(),"",$b);}foreach(array_reverse($b)as$W){if(!queries($W[2]=="DROP"?"DROP INDEX ".idf_escape($W[1]):index_sql($Q,$W[0],$W[1],"(".implode(", ",$W[2]).")")))return
false;}return
true;}function
truncate_tables(array$S){return
apply_queries("DELETE FROM",$S);}function
drop_views(array$In){return
apply_queries("DROP VIEW",$In);}function
drop_tables(array$S){return
apply_queries("DROP TABLE",$S);}function
move_tables(array$S,array$In,$nm){return
false;}function
trigger($B,$Q){if($B=="")return
array("Statement"=>"BEGIN\n\t;\nEND");$u='(?:[^`"\s]+|`[^`]*`|"[^"]*")+';$Rm=trigger_options();preg_match("~^CREATE\\s+TRIGGER\\s*$u\\s*(".implode("|",$Rm["Timing"]).")\\s+([a-z]+)(?:\\s+OF\\s+($u))?\\s+ON\\s*$u\\s*(?:FOR\\s+EACH\\s+ROW\\s)?(.*)~is",get_val("SELECT sql FROM sqlite_master WHERE type = 'trigger' AND name = ".q($B)),$A);if(!$A)return
array();$Xh=$A[3];return
array("Timing"=>strtoupper($A[1]),"Event"=>strtoupper($A[2]).($Xh?" OF":""),"Of"=>idf_unescape($Xh),"Trigger"=>$B,"Statement"=>$A[4],);}function
triggers($Q){$H=array();$Rm=trigger_options();foreach(get_rows("SELECT * FROM sqlite_master WHERE type = 'trigger' AND tbl_name = ".q($Q))as$I){preg_match('~^CREATE\s+TRIGGER\s*(?:[^`"\s]+|`[^`]*`|"[^"]*")+\s*('.implode("|",$Rm["Timing"]).')\s*(.*?)\s+ON\b~i',$I["sql"],$A);$H[$I["name"]]=array($A[1],$A[2]);}return$H;}function
trigger_options(){return
array("Timing"=>array("BEFORE","AFTER","INSTEAD OF"),"Event"=>array("INSERT","UPDATE","UPDATE OF","DELETE"),"Type"=>array("FOR EACH ROW"),);}function
last_id($G){return
get_val("SELECT LAST_INSERT_ROWID()");}function
explain(Db$g,$F){return$g->query("EXPLAIN QUERY PLAN $F");}function
found_rows(array$R,array$Z){}function
types($Id=false){return
array();}function
create_sql($Q,$La,$Ml){$H=get_val("SELECT sql FROM sqlite_master WHERE type IN ('table', 'view') AND name = ".q($Q));foreach(indexes($Q)as$B=>$v){if($B==''||$v['type']=='FULLTEXT')continue;$H
.=";\n\n".index_sql($Q,$v['type'],$B,"(".implode(", ",array_map('Adminer\idf_escape',$v['columns'])).")");}return$H;}function
truncate_sql($Q){return"DELETE FROM ".table($Q);}function
use_sql($pc,$Ml=""){return"";}function
trigger_sql($Q){return
implode(get_vals("SELECT sql || ';;\n' FROM sqlite_master WHERE type = 'trigger' AND tbl_name = ".q($Q)));}function
show_variables(){$H=array();foreach(get_rows("PRAGMA pragma_list")as$I){$B=$I["name"];if($B!="pragma_list"&&$B!="compile_options"){$H[$B]=array($B,'');foreach(get_rows("PRAGMA $B")as$I)$H[$B][1].=implode(", ",$I)."\n";}}return$H;}function
show_status(){$H=array();foreach(get_vals("PRAGMA compile_options")as$ui)$H[]=explode("=",$ui,2)+array('','');return$H;}function
convert_field(array$n){}function
unconvert_field(array$n,$H){return$H;}function
support($Nd){return
preg_match('~^(check|columns|database|drop_col|dump|fast_status|indexes|descidx|move_col|sql|status|table|transaction_ddl|trigger|variables|view|view_trigger)$~',$Nd);}}add_driver("mssql","MS SQL");if(isset($_GET["mssql"])){define('Adminer\DRIVER',"mssql");if(extension_loaded("sqlsrv")&&$_GET["ext"]!="pdo"){class
Db
extends
SqlDb{var$extension="sqlsrv";private$link,$result,$warnings,$transaction=false;private
function
get_error(){$this->error="";foreach(sqlsrv_errors()as$m){$this->errno=$m["code"];$this->error
.="$m[message]\n";}$this->error=rtrim($this->error);}function
attach(array$M,$U,$D){sqlsrv_configure("WarningsReturnAsErrors",0);$Pb=array("UID"=>$U,"PWD"=>$D,"CharacterSet"=>"UTF-8","ReturnDatesAsStrings"=>true);if(isset($_GET["sql"])&&!self::$instance)$Pb["MultipleActiveResultSets"]=false;$El=adminer()->connectSsl();if(isset($El["Encrypt"]))$Pb["Encrypt"]=$El["Encrypt"];if(isset($El["TrustServerCertificate"]))$Pb["TrustServerCertificate"]=$El["TrustServerCertificate"];$k=adminer()->database();if($k!="")$Pb["Database"]=$k;$tj=$M["port"];$this->link=@sqlsrv_connect($M["host"].($tj?",$tj":""),$Pb);if($this->link){$_f=sqlsrv_server_info($this->link);$this->server_info=$_f['SQLServerVersion'];}else$this->get_error();return($this->link?'':$this->error);}function
quote($P){return
unicode_prefix($P)."'".str_replace("'","''",$P)."'";}function
select_db($pc){return$this->query(use_sql($pc));}function
query($F,$an=false){$G=sqlsrv_query($this->link,$F);$this->error="";if(!$G){$this->get_error();return
false;}return$this->store_result($G);}function
multi_query($F){$this->result=sqlsrv_query($this->link,$F);$this->error="";if(!$this->result){$this->get_error();return
false;}return
true;}function
store_result($G=null){if(!$G)$G=$this->result;if(!$G)return
false;$this->warnings=sqlsrv_errors(SQLSRV_ERR_WARNINGS);if(sqlsrv_field_metadata($G))return
new
Result($G);$this->affected_rows=sqlsrv_rows_affected($G);return
true;}function
next_result(){if(!$this->result)return
false;$H=sqlsrv_next_result($this->result);if($H===false){$this->get_error();$this->result=null;return
true;}return!!$H;}function
warnings(){$H=array();foreach((array)$this->warnings
as$Ln)$H[]=$Ln["message"];return$H;}function
inTransaction(){return$this->transaction||(isset($_GET["sql"])&&get_val("SELECT @@TRANCOUNT",0,$this));}function
begin(){$this->transaction=sqlsrv_begin_transaction($this->link);if(!$this->transaction)$this->get_error();return$this->transaction;}function
commit(){if($this->transaction&&!sqlsrv_commit($this->link)){$this->get_error();return
false;}$this->transaction=false;return
true;}function
rollback(){if(!$this->transaction&&isset($_GET["sql"]))return!!$this->query("IF @@TRANCOUNT > 0 ROLLBACK");if($this->transaction&&!sqlsrv_rollback($this->link)){$this->get_error();return
false;}$this->transaction=false;return
true;}}class
Result{var$num_rows;private$result,$offset=0,$fields;function
__construct($G){$this->result=$G;}function
fetch_assoc(){return
sqlsrv_fetch_array($this->result,SQLSRV_FETCH_ASSOC);}function
fetch_row(){return
sqlsrv_fetch_array($this->result,SQLSRV_FETCH_NUMERIC);}function
fetch_field(){if(!$this->fields)$this->fields=sqlsrv_field_metadata($this->result);$Zm=array(-155=>"datetimeoffset","time",-152=>"xml","varbinary","sql_variant",-11=>"uniqueidentifier","ntext","nvarchar","nchar","bit","tinyint","bigint","image","varbinary","binary","text",1=>"char","numeric","decimal","int","smallint","float","real","float",12=>"varchar",91=>"date","time","datetime",);$n=$this->fields[$this->offset++];$H=new
\stdClass;$H->name=$n["Name"];$H->native_type=idx($Zm,$n["Type"],"");return$H;}function
seek($ci){for($s=0;$s<$ci;$s++)sqlsrv_fetch($this->result);}}function
last_id($G){return(string)get_val("SELECT SCOPE_IDENTITY()");}function
explain(Db$g,$F){$g->query("SET SHOWPLAN_ALL ON");$H=$g->query($F);$g->query("SET SHOWPLAN_ALL OFF");return$H;}}else{abstract
class
MssqlDb
extends
PdoDb{function
quote($P){return
unicode_prefix($P).parent::quote($P);}function
select_db($pc){return$this->query(use_sql($pc));}function
lastInsertId(){return$this->pdo->lastInsertId();}function
inTransaction(){return
parent::inTransaction()||(isset($_GET["sql"])&&get_val("SELECT @@TRANCOUNT"));}function
rollback(){return(parent::inTransaction()||!isset($_GET["sql"])?parent::rollback():!!$this->query("IF @@TRANCOUNT > 0 ROLLBACK"));}function
warnings(){$G=$this->multi;if(!is_object($G))return
array();$m=$G->errorInfo();return
array((string)$m[2]);}}function
last_id($G){return
connection()->lastInsertId();}function
explain(Db$g,$F){}if(extension_loaded("pdo_sqlsrv")){class
Db
extends
MssqlDb{var$extension="PDO_SQLSRV";function
attach(array$M,$U,$D){$tj=$M["port"];$ad="sqlsrv:Server=$M[host]".($tj?",$tj":"").(isset($_GET["sql"])&&!self::$instance?";MultipleActiveResultSets=0":"");$El=adminer()->connectSsl();foreach(array("Encrypt","TrustServerCertificate")as$x){if(isset($El[$x]))$ad
.=";$x=".($El[$x]?1:0);}return$this->dsn($ad,$U,$D,array(\PDO::SQLSRV_ATTR_DIRECT_QUERY=>true));}}}elseif(extension_loaded("pdo_dblib")){class
Db
extends
MssqlDb{var$extension="PDO_DBLIB";function
attach(array$M,$U,$D){$tj=$M["port"];$rl=$M["socket"];$vi=array(1002=>true);$H=$this->dsn("dblib:charset=utf8;host=$M[host]".($tj!=""?";port=$tj":($rl!=""?";unix_socket=$rl":"")),$U,$D,$vi);if(!$H){$this->query("SET ANSI_NULLS, QUOTED_IDENTIFIER, CONCAT_NULL_YIELDS_NULL, ANSI_WARNINGS, ANSI_PADDING ON");$this->server_info=get_val("SELECT CAST(SERVERPROPERTY('ProductVersion') AS varchar(20))",0,$this);}return$H;}}}}class
Driver
extends
SqlDriver{static$extensions=array("SQLSRV","PDO_SQLSRV","PDO_DBLIB");static$jush="mssql";static$serverSocket=true;var$insertFunctions=array("date|time"=>"getdate");var$editFunctions=array("int|decimal|real|float|money|datetime"=>"+/-","char|text"=>"+",);var$functions=array("len","lower","round","upper");var$grouping=array("avg","count","count distinct","max","min","sum");var$generated=array("PERSISTED","VIRTUAL");var$onActions="NO ACTION|CASCADE|SET NULL|SET DEFAULT";var$inout="|OUTPUT";private$unknownTypes=array();function
operators($Vl){return
array("=","<",">","<=",">=","!=","LIKE","LIKE %%","IN","BETWEEN","IS NULL","NOT LIKE","NOT IN","IS NOT NULL");}static
function
connect($M,$U,$D){if($M=="")$M="localhost:1433";return
parent::connect($M,$U,$D);}function
__construct(Db$g){parent::__construct($g);$this->types=array('Numbers'=>array("tinyint"=>3,"smallint"=>5,"int"=>10,"bigint"=>20,"bit"=>1,"decimal"=>0,"numeric"=>0,"real"=>12,"float"=>53,"smallmoney"=>10,"money"=>20,"vector"=>0,),'Date and time'=>array("date"=>10,"smalldatetime"=>19,"datetime"=>23,"datetime2"=>19,"time"=>8,"datetimeoffset"=>26),'Strings'=>array("char"=>8000,"varchar"=>8000,"text"=>2147483647,"nchar"=>4000,"nvarchar"=>4000,"ntext"=>1073741823,"uniqueidentifier"=>36,"xml"=>2147483647,"json"=>2147483647,"sql_variant"=>8000,"hierarchyid"=>892,),'Binary'=>array("binary"=>8000,"varbinary"=>8000,"image"=>2147483647),'Geometry'=>array("geometry"=>0,"geography"=>0),);$Zm=array_flip(get_vals("SELECT name FROM sys.types WHERE is_user_defined = 0 ORDER BY name"));if($Zm){foreach($this->types
as$r=>$Ee){foreach($Ee
as$T=>$y){if(isset($Zm[$T]))unset($Zm[$T]);else
unset($this->types[$r][$T]);}if(!$this->types[$r])unset($this->types[$r]);}$this->unknownTypes=array_keys($Zm);}}function
types(){return
parent::types()+array_fill_keys($this->unknownTypes,0);}function
structuredTypes(){return
array_merge(parent::structuredTypes(),$this->unknownTypes);}function
typeName(\stdClass$n){return
idx((array)$n,'sqlsrv:decl_type',parent::typeName($n));}function
insertUpdate($Q,array$J,array$Fj){$o=fields($Q);$ln=array();$Z=array();$N=reset($J);$e="c".implode(", c",range(1,count($N)));$eb=0;$Ff=array();foreach($N
as$x=>$W){$eb++;$B=idf_unescape($x);if(!$o[$B]["auto_increment"])$Ff[$x]="c$eb";if(isset($Fj[$B]))$Z[]="$x = c$eb";else$ln[]="$x = c$eb";}$Y=array();foreach($J
as$N)$Y[]="(".implode(", ",$N).")";if($Z){$if=queries("SET IDENTITY_INSERT ".table($Q)." ON");$H=queries("MERGE ".table($Q)." USING (VALUES\n\t".implode(",\n\t",$Y)."\n) AS source ($e) ON ".implode(" AND ",$Z).($ln?"\nWHEN MATCHED THEN UPDATE SET ".implode(", ",$ln):"")."\nWHEN NOT MATCHED THEN INSERT (".implode(", ",array_keys($if?$N:$Ff)).") VALUES (".($if?$e:implode(", ",$Ff)).");");if($if)queries("SET IDENTITY_INSERT ".table($Q)." OFF");}else$H=queries("INSERT INTO ".table($Q)." (".implode(", ",array_keys($N)).") VALUES\n".implode(",\n",$Y));return$H;}function
begin(){remember_query("BEGIN TRANSACTION");return$this->conn->begin();}function
convertSearch($u,array$W,array$n){return(preg_match('~^(bit|n?text|xml|json|vector|uniqueidentifier|sql_variant|hierarchyid|geography|geometry)$~',$n["type"])?"CAST($u AS nvarchar(max))":$u);}function
quoteBinary($Dk){return"0x".bin2hex($Dk);}function
warnings(){$H=array();foreach($this->conn->warnings()as$bh){$bh=trim(preg_replace('~^(\[[^]]+])+~','',$bh));if($bh!="")$H[]=$bh;}return
nl_br(h(implode("\n",$H)));}function
tableHelp($B,$Vf=false){$_g=array("sys"=>"catalog-views/sys-","INFORMATION_SCHEMA"=>"information-schema-views/",);$_=$_g[get_schema()];if($_)return"relational-databases/system-$_".preg_replace('~_~','-',strtolower($B))."-transact-sql";}function
isSystem($k,$K=""){return($K!=""?information_schema($k,$K)||preg_match('~^(guest|db_(owner|accessadmin|securityadmin|ddladmin|backupoperator|(deny)?data(reader|writer)))$~',$K):in_array($k,array("master","tempdb","model","msdb")));}}function
unicode_prefix($P){return(strlen($P)!=utf8_length($P)?"N":"");}function
idf_escape($u){return"[".str_replace("]","]]",$u)."]";}function
table($u){return($_GET["ns"]!=""?idf_escape($_GET["ns"]).".":"").idf_escape($u);}function
get_databases($fe){return
get_vals("SELECT name FROM sys.databases WHERE name NOT IN ('master', 'tempdb', 'model', 'msdb')");}function
limit($F,$Z,$z,$ci=0,$Uk=" "){return($z?" TOP (".($z+$ci).")":"")." $F$Z";}function
limit1($Q,$F,$Z,$Uk="\n"){return
limit($F,$Z,1,0,$Uk);}function
db_collation($k,array$Cb){return
get_val("SELECT collation_name FROM sys.databases WHERE name = ".q($k));}function
logged_user(){return
get_val("SELECT SUSER_NAME()");}function
tables_list(){return
get_key_vals("SELECT name, type_desc FROM sys.all_objects WHERE schema_id = SCHEMA_ID(".q(get_schema()).") AND type IN ('S', 'U', 'V') ORDER BY name");}function
count_tables(array$j){$H=array();foreach($j
as$k){connection()->select_db($k);$H[$k]=get_val("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES");}return$H;}function
table_status($B="",$Md=false){$H=array();$pl=array();foreach(get_rows("SELECT object_id, SUM(CASE WHEN index_id < 2 THEN row_count ELSE 0 END) AS [Rows],
SUM(CASE WHEN index_id < 2 THEN used_page_count ELSE 0 END) * 8192 AS Data_length,
SUM(CASE WHEN index_id > 1 THEN used_page_count ELSE 0 END) * 8192 AS Index_length,
SUM(reserved_page_count - used_page_count) * 8192 AS Data_free
FROM sys.dm_db_partition_stats
GROUP BY object_id",null,"")as$I){$Wh=$I["object_id"];unset($I["object_id"]);$pl[$Wh]=$I;}foreach(get_rows("SELECT ao.object_id, ao.name AS Name, ao.type_desc AS Engine,
	(SELECT cast(value as varchar(max)) FROM fn_listextendedproperty('MS_Description', 'SCHEMA', schema_name(schema_id), 'TABLE', ao.name, null, null)) AS Comment
FROM sys.all_objects AS ao
WHERE schema_id = SCHEMA_ID(".q(get_schema()).") AND type IN ('S', 'U', 'V') ".($B!=""?"AND name = ".q($B):"ORDER BY name"))as$I){$Wh=$I["object_id"];unset($I["object_id"]);$H[$I["Name"]]=$I+idx($pl,$Wh,array());}return$H;}function
is_view(array$R){return$R["Engine"]=="VIEW";}function
fk_support(array$R){return
true;}function
type_length($T,array$I){return(preg_match("~char|binary~",$T)?($I["max_length"]==-1?"max":intval($I["max_length"])/($T[0]=='n'?2:1)):($T=="decimal"?"$I[precision],$I[scale]":(preg_match('~^(datetime2|datetimeoffset|time)$~',$T)?$I["scale"]:($T=="vector"?(intval($I["max_length"])-8)/4:""))));}function
fields($Q){$Jb=get_key_vals("SELECT objname, cast(value as varchar(max)) FROM fn_listextendedproperty('MS_DESCRIPTION', 'schema', ".q(get_schema()).", 'table', ".q($Q).", 'column', NULL)");$H=array();$Wl=get_val("SELECT object_id FROM sys.all_objects WHERE schema_id = SCHEMA_ID(".q(get_schema()).") AND type IN ('S', 'U', 'V') AND name = ".q($Q));foreach(get_rows("SELECT c.max_length, c.precision, c.scale, c.name, c.is_nullable, c.is_identity, c.collation_name,
	COALESCE(bt.name, t.name) type, d.definition [default], d.name default_constraint, i.is_primary_key
FROM sys.all_columns c
JOIN sys.types t ON c.user_type_id = t.user_type_id
LEFT JOIN sys.types bt ON t.system_type_id = bt.user_type_id AND t.is_user_defined = 1
LEFT JOIN sys.default_constraints d ON c.default_object_id = d.object_id
LEFT JOIN sys.index_columns ic ON c.object_id = ic.object_id AND c.column_id = ic.column_id
LEFT JOIN sys.indexes i ON ic.object_id = i.object_id AND ic.index_id = i.index_id
WHERE c.object_id = ".q($Wl))as$I){$T=$I["type"];$y=type_length($T,$I);$H[$I["name"]]=array("field"=>$I["name"],"full_type"=>$T.($y!=""?"($y)":""),"type"=>$T,"length"=>$y,"default"=>(preg_match("~^\(N?'(.*)'\)$~s",$I["default"],$A)?str_replace("''","'",$A[1]):$I["default"]),"default_constraint"=>$I["default_constraint"],"null"=>$I["is_nullable"],"auto_increment"=>$I["is_identity"],"collation"=>$I["collation_name"],"privileges"=>($T=="timestamp"?array("select"=>1,"where"=>1,"order"=>1):array("insert"=>1,"select"=>1,"update"=>1,"where"=>1,"order"=>1)),"primary"=>$I["is_primary_key"],"comment"=>$Jb[$I["name"]],);}foreach(get_rows("SELECT * FROM sys.computed_columns WHERE object_id = ".q($Wl))as$I){$H[$I["name"]]["generated"]=($I["is_persisted"]?"PERSISTED":"VIRTUAL");$H[$I["name"]]["default"]=$I["definition"];}return$H;}function
indexes($Q,$h=null){$H=array();foreach(get_rows("SELECT i.name, key_ordinal, is_unique, is_primary_key, c.name AS column_name, is_descending_key
FROM sys.indexes i
INNER JOIN sys.index_columns ic ON i.object_id = ic.object_id AND i.index_id = ic.index_id
INNER JOIN sys.columns c ON ic.object_id = c.object_id AND ic.column_id = c.column_id
WHERE OBJECT_NAME(i.object_id) = ".q($Q),$h)as$I){$B=$I["name"];$H[$B]["type"]=($I["is_primary_key"]?"PRIMARY":($I["is_unique"]?"UNIQUE":"INDEX"));$H[$B]["lengths"]=array();$H[$B]["columns"][$I["key_ordinal"]]=$I["column_name"];$H[$B]["descs"][$I["key_ordinal"]]=($I["is_descending_key"]?'1':null);}return$H;}function
view($B){return
array("select"=>preg_replace('~^(?:[^[]|\[[^]]*])*\s+AS\s+~isU','',get_val("SELECT VIEW_DEFINITION FROM INFORMATION_SCHEMA.VIEWS WHERE TABLE_SCHEMA = SCHEMA_NAME() AND TABLE_NAME = ".q($B))));}function
collations(){$H=array();foreach(get_vals("SELECT name FROM fn_helpcollations()")as$Bb)$H[preg_replace('~_.*~','',$Bb)][]=$Bb;return$H;}function
information_schema($k,$K=""){return
in_array($K!=""?$K:get_schema(),array("INFORMATION_SCHEMA","sys"));}function
error(){return
nl_br(h(preg_replace('~^(\[[^]]*])+~m','',connection()->error)));}function
create_database($k,$Bb){return
queries("CREATE DATABASE ".idf_escape($k).(preg_match('~^[a-z0-9_]+$~i',$Bb)?" COLLATE $Bb":""));}function
drop_databases(array$j){return!!queries("DROP DATABASE ".implode(", ",array_map('Adminer\idf_escape',$j)));}function
rename_database($B,$Bb){if(preg_match('~^[a-z0-9_]+$~i',$Bb))queries("ALTER DATABASE ".idf_escape(DB)." COLLATE $Bb");queries("ALTER DATABASE ".idf_escape(DB)." MODIFY NAME = ".idf_escape($B));return
true;}function
auto_increment(){return" IDENTITY".($_POST["Auto_increment"]!=""?"(".number($_POST["Auto_increment"]).",1)":"")." PRIMARY KEY";}function
alter_table($Q,$B,array$o,array$he,$f,$nd,$Bb,$La,$bj){$b=array();$Jb=array();$Fi=fields($Q);foreach($o
as$n){$d=idf_escape($n[0]);$W=$n[1];if(!$W)$b["DROP"][]=" COLUMN $d";else{$Di=idx($Fi[$n[0]],"comment");if($W[5]!=($Di!=""?" COMMENT ".q($Di):""))$Jb[idf_unescape($W[0])]=array($Di!="",substr($W[5],9));unset($W[5]);if(preg_match('~ AS ~',$W[3]))unset($W[1],$W[2]);if($n[0]=="")$b["ADD"][]="\n  ".implode("",$W).($Q==""?substr($he[$W[0]],16+strlen($W[0])):"");else{$l=$W[3];unset($W[3]);unset($W[6]);if($d!=$W[0])queries("EXEC sp_rename ".q(table($Q).".$d").", ".q(idf_unescape($W[0])).", 'COLUMN'");$b["ALTER COLUMN ".implode("",$W)][]="";$Ei=$Fi[$n[0]];if(default_value($Ei)!=$l){if($Ei["default"]!==null)$b["DROP"][]=" ".idf_escape($Ei["default_constraint"]);if($l)$b["ADD"][]="\n $l FOR $d";}}}}if($Q==""){$ka=(array)$b["ADD"];foreach($he
as$x=>$W){if(!is_string($x))$ka[]="\n$W";}if(!queries("CREATE TABLE ".table($B)." (".implode(",",$ka)."\n)"))return
false;}else{if($Q!=$B)queries("EXEC sp_rename ".q(table($Q)).", ".q($B));if($he)$b[""]=$he;foreach($b
as$x=>$W){if(!queries("ALTER TABLE ".table($B)." $x".implode(",",$W)))return
false;}}$Oj="@name = N'MS_Description', @level0type = N'Schema', @level0name = ".q(get_schema()).", @level1type = N'Table', @level1name = ".q($B);foreach($Jb
as$x=>$W){list($Vc,$Hb)=$W;$Pj="$Oj, @level2type = N'Column', @level2name = ".q($x);if($Vc)queries("EXEC sp_dropextendedproperty $Pj");if($Hb!="")queries("EXEC sp_addextendedproperty $Pj, @value = $Hb");}if($f!==null){if(idx(table_status1($B),"Comment")!="")queries("EXEC sp_dropextendedproperty $Oj");if($f!="")queries("EXEC sp_addextendedproperty $Oj, @value = ".q($f));}return
true;}function
alter_indexes($Q,$b){$v=array();$Vc=array();foreach($b
as$W){if($W[2]=="DROP"){if($W[0]=="PRIMARY")$Vc[]=idf_escape($W[1]);else$v[]=idf_escape($W[1])." ON ".table($Q);}elseif(!queries(($W[0]!="PRIMARY"?"CREATE $W[0] ".($W[0]!="INDEX"?"INDEX ":"").idf_escape($W[1]!=""?$W[1]:uniqid($Q."_"))." ON ".table($Q):"ALTER TABLE ".table($Q)." ADD PRIMARY KEY")." (".implode(", ",$W[2]).")"))return
false;}return(!$v||queries("DROP INDEX ".implode(", ",$v)))&&(!$Vc||queries("ALTER TABLE ".table($Q)." DROP ".implode(", ",$Vc)));}function
found_rows(array$R,array$Z){}function
foreign_keys($Q){$H=array();$ni=array("CASCADE","NO ACTION","SET NULL","SET DEFAULT");$K=get_schema();foreach(get_rows("EXEC sp_fkeys @fktable_name = ".q($Q).", @fktable_owner = ".q($K))as$I){$q=&$H[$I["FK_NAME"]];$q["db"]=($I["PKTABLE_QUALIFIER"]==DB?"":$I["PKTABLE_QUALIFIER"]);$q["ns"]=($I["PKTABLE_OWNER"]==$K?"":$I["PKTABLE_OWNER"]);$q["table"]=$I["PKTABLE_NAME"];$q["on_update"]=$ni[$I["UPDATE_RULE"]];$q["on_delete"]=$ni[$I["DELETE_RULE"]];$q["source"][]=$I["FKCOLUMN_NAME"];$q["target"][]=$I["PKCOLUMN_NAME"];}return$H;}function
truncate_tables(array$S){return
apply_queries("TRUNCATE TABLE",$S);}function
drop_views(array$In){return
queries("DROP VIEW ".implode(", ",array_map('Adminer\table',$In)));}function
drop_tables(array$S){return
queries("DROP TABLE ".implode(", ",array_map('Adminer\table',$S)));}function
move_tables(array$S,array$In,$nm){return
apply_queries("ALTER SCHEMA ".idf_escape($nm)." TRANSFER",array_merge($S,$In));}function
trigger($B,$Q){if($B=="")return
array();$J=get_rows("SELECT s.name [Trigger],
CASE WHEN OBJECTPROPERTY(s.id, 'ExecIsInsertTrigger') = 1 THEN 'INSERT'
	WHEN OBJECTPROPERTY(s.id, 'ExecIsUpdateTrigger') = 1 THEN 'UPDATE'
	WHEN OBJECTPROPERTY(s.id, 'ExecIsDeleteTrigger') = 1 THEN 'DELETE' END [Event],
CASE WHEN OBJECTPROPERTY(s.id, 'ExecIsInsteadOfTrigger') = 1 THEN 'INSTEAD OF' ELSE 'AFTER' END [Timing],
c.text
FROM sysobjects s
JOIN syscomments c ON s.id = c.id
WHERE s.xtype = 'TR' AND s.name = ".q($B));$H=reset($J);if($H)$H["Statement"]=preg_replace('~^.+\s+AS\s+~isU','',$H["text"]);return($H?:array());}function
triggers($Q){$H=array();foreach(get_rows("SELECT sys1.name,
CASE WHEN OBJECTPROPERTY(sys1.id, 'ExecIsInsertTrigger') = 1 THEN 'INSERT'
	WHEN OBJECTPROPERTY(sys1.id, 'ExecIsUpdateTrigger') = 1 THEN 'UPDATE'
	WHEN OBJECTPROPERTY(sys1.id, 'ExecIsDeleteTrigger') = 1 THEN 'DELETE' END [Event],
CASE WHEN OBJECTPROPERTY(sys1.id, 'ExecIsInsteadOfTrigger') = 1 THEN 'INSTEAD OF' ELSE 'AFTER' END [Timing]
FROM sysobjects sys1
JOIN sysobjects sys2 ON sys1.parent_obj = sys2.id
WHERE sys1.xtype = 'TR' AND sys2.name = ".q($Q))as$I)$H[$I["name"]]=array($I["Timing"],$I["Event"]);return$H;}function
trigger_options(){return
array("Timing"=>array("AFTER","INSTEAD OF"),"Event"=>array("INSERT","UPDATE","DELETE"),"Type"=>array("AS"),);}function
routine($B,$T){$zc=get_val("SELECT m.definition
FROM sys.objects o
JOIN sys.sql_modules m ON m.object_id = o.object_id
WHERE o.schema_id = SCHEMA_ID(".q(get_schema()).") AND o.name = ".q($B)." AND o.type = ".q($T=="PROCEDURE"?"P":"FN"));if(!$zc)return
array();$H=array("definition"=>preg_replace('~^(?:[^[]|\[[^]]*])*\s+AS\s+~isU','',$zc),"fields"=>array());foreach(get_rows("SELECT p.name, TYPE_NAME(p.user_type_id) [type], p.max_length, p.precision, p.scale, p.is_output
FROM sys.parameters p
JOIN sys.objects o ON p.object_id = o.object_id
WHERE o.schema_id = SCHEMA_ID(".q(get_schema()).") AND o.name = ".q($B)."
ORDER BY p.parameter_id")as$I){$Rd=$I["type"];$y=type_length($Rd,$I);$n=array("field"=>preg_replace('~^@~','',$I["name"]),"type"=>$Rd,"length"=>$y,"full_type"=>$Rd.($y!=""?"($y)":""),"null"=>true,"inout"=>($I["is_output"]?"OUTPUT":""),);if($n["field"]=="")$H["returns"]=$n;else$H["fields"][]=$n;}return$H;}function
routines(){return
get_rows("SELECT o.name SPECIFIC_NAME, o.name ROUTINE_NAME,
	CASE o.type WHEN 'P' THEN 'PROCEDURE' ELSE 'FUNCTION' END ROUTINE_TYPE, TYPE_NAME(p.user_type_id) DTD_IDENTIFIER
FROM sys.objects o
LEFT JOIN sys.parameters p ON o.object_id = p.object_id AND p.parameter_id = 0
WHERE o.schema_id = SCHEMA_ID(".q(get_schema()).") AND o.type IN ('P', 'FN')
ORDER BY o.name");}function
routine_languages(){return
array();}function
routine_options($vk){return
array();}function
routine_id($B,array$I){return
table($B);}function
schemas(){return
get_vals("SELECT name FROM sys.schemas");}function
get_schema(){if($_GET["ns"]!="")return$_GET["ns"];return
get_val("SELECT SCHEMA_NAME()");}function
set_schema($K,$h=null){$_GET["ns"]=$K;return!!get_val("SELECT 1 FROM sys.schemas WHERE name = ".q($K),0,$h);}function
schema_comment($K){return(string)get_val("SELECT CAST(value AS nvarchar(max)) FROM fn_listextendedproperty('MS_Description', 'SCHEMA', ".q($K).", NULL, NULL, NULL, NULL)");}function
set_schema_comment($K,$f){$Oj="@name = N'MS_Description', @level0type = N'Schema', @level0name = ".q($K);return(schema_comment($K)==""||queries("EXEC sp_dropextendedproperty $Oj"))&&($f==""||queries("EXEC sp_addextendedproperty $Oj, @value = ".q($f)));}function
create_sql($Q,$La,$Ml){$O=table_status1($Q);if(is_view($O)){$Hn=view($Q);return"CREATE VIEW ".table($Q)." AS $Hn[select]";}$Oj="EXEC sp_addextendedproperty @name = N'MS_Description', @level0type = N'Schema', @level0name = ".q(get_schema()).", @level1type = N'Table', @level1name = ".q($Q);$Jb=($O["Comment"]!=""?";\n\n$Oj, @value = ".q($O["Comment"]):"");$o=array();$Fj=false;foreach(fields($Q)as$B=>$n){$W=process_field($n,$n);if($W[6])$Fj=true;unset($W[5]);$o[]=implode("",$W);if($n["comment"]!="")$Jb
.=";\n\n$Oj, @level2type = N'Column', @level2name = ".q($B).", @value = ".q($n["comment"]);}foreach(indexes($Q)as$B=>$v){if(!$Fj||$v["type"]!="PRIMARY"){$e=array();foreach($v["columns"]as$x=>$W)$e[]=idf_escape($W).($v["descs"][$x]?" DESC":"");$B=idf_escape($B);$o[]=($v["type"]=="INDEX"?"INDEX $B":"CONSTRAINT $B ".($v["type"]=="UNIQUE"?"UNIQUE":"PRIMARY KEY"))." (".implode(", ",$e).")";}}foreach(driver()->checkConstraints($Q)as$B=>$ob)$o[]="CONSTRAINT ".idf_escape($B)." CHECK ($ob)";return"CREATE TABLE ".table($Q)." (\n\t".implode(",\n\t",$o)."\n)$Jb";}function
foreign_keys_sql($Q){$o=array();foreach(foreign_keys($Q)as$he)$o[]=ltrim(format_foreign_key($he));return($o?"ALTER TABLE ".table($Q)." ADD\n\t".implode(",\n\t",$o).";\n\n":"");}function
truncate_sql($Q){return"TRUNCATE TABLE ".table($Q);}function
use_sql($pc,$Ml=""){return"USE ".idf_escape($pc);}function
use_schema_sql($K,$Ml){$B=idf_escape($K);$i="EXEC(".q("CREATE SCHEMA $B").")";$f=schema_comment($K);if($f!="")$i="BEGIN\n$i\nEXEC sp_addextendedproperty @name = N'MS_Description', @level0type = N'Schema', @level0name = ".q($K).", @value = ".q($f)."\nEND";return($Ml=="DROP+CREATE"?"DROP SCHEMA IF EXISTS $B;\n":"")."IF SCHEMA_ID(".q($K).") IS NULL $i";}function
trigger_sql($Q){$H="";foreach(triggers($Q)as$B=>$Om)$H
.=create_trigger(" ON ".table($Q),trigger($B,$Q)).";";return$H;}function
convert_field(array$n){}function
unconvert_field(array$n,$H){return$H;}function
support($Nd){return
preg_match('~^(check|comment|columns|database|drop_col|dump|fast_status|indexes|descidx|procedure|routine|scheme|sql|table|transaction_ddl|trigger|view|view_trigger)$~',$Nd);}}add_driver("oracle","Oracle");if(isset($_GET["oracle"])){define('Adminer\DRIVER',"oracle");function
easy_connect(array$M){return
url_host($M["host"]).($M["port"]!=""?":$M[port]":"").$M["path"];}if(extension_loaded("oci8")&&$_GET["ext"]!="pdo"){class
Db
extends
SqlDb{var$extension="oci8";private$link,$transaction=false;function
_error($sd,$m){if(ini_bool("html_errors"))$m=html_entity_decode(strip_tags($m));$m=preg_replace('~^[^:]*: ~','',$m);$this->error=$m;}function
attach(array$M,$U,$D){$this->link=@oci_new_connect($U,$D,easy_connect($M),"AL32UTF8");if($this->link){$this->server_info=oci_server_version($this->link);return'';}$m=oci_error();return($m?$m["message"]:'Unknown error.');}function
quote($P){return"'".str_replace("'","''",$P)."'";}function
select_db($pc){return$this->query("ALTER SESSION SET CURRENT_SCHEMA = ".idf_escape($pc));}function
query($F,$an=false){$G=oci_parse($this->link,$F);$this->error="";if(!$G){$m=oci_error($this->link);$this->errno=$m["code"];$this->error=$m["message"];return
false;}set_error_handler(array($this,'_error'));$H=@oci_execute($G,($this->transaction?OCI_NO_AUTO_COMMIT:OCI_COMMIT_ON_SUCCESS));restore_error_handler();if($H){if(oci_num_fields($G))return
new
Result($G);$this->affected_rows=oci_num_rows($G);oci_free_statement($G);}return$H;}function
timeout($sh){return
function_exists('oci_set_call_timeout')&&oci_set_call_timeout($this->link,$sh);}function
inTransaction(){return$this->transaction;}function
begin(){$this->transaction=true;return
true;}function
commit(){return$this->end_transaction(@oci_commit($this->link));}function
rollback(){return$this->end_transaction(@oci_rollback($this->link));}private
function
end_transaction($H){$this->transaction=false;if(!$H){$m=oci_error($this->link);$this->errno=$m["code"];$this->error=$m["message"];}return$H;}}class
Result{var$num_rows;private$result,$offset=1;function
__construct($G){$this->result=$G;}private
function
convert($I){foreach((array)$I
as$x=>$W){if(is_a($W,'OCILob')||is_a($W,'OCI-Lob'))$I[$x]=$W->load();}return$I;}function
fetch_assoc(){return$this->convert(oci_fetch_assoc($this->result));}function
fetch_row(){return$this->convert(oci_fetch_row($this->result));}function
fetch_field(){$d=$this->offset++;$H=new
\stdClass;$H->name=oci_field_name($this->result,$d);$T=oci_field_type($this->result,$d);$H->native_type=idx(array(100=>"binary_float","binary_double"),$T,$T);return$H;}}}elseif(extension_loaded("pdo_oci")){class
Db
extends
PdoDb{var$extension="PDO_OCI";function
attach(array$M,$U,$D){return$this->dsn("oci:dbname=//".easy_connect($M).";charset=AL32UTF8",$U,$D);}function
select_db($pc){return$this->query("ALTER SESSION SET CURRENT_SCHEMA = ".idf_escape($pc));}}}class
Driver
extends
SqlDriver{static$extensions=array("OCI8","PDO_OCI");static$jush="oracle";static$serverPath=true;var$insertFunctions=array("date"=>"current_date","timestamp"=>"current_timestamp",);var$editFunctions=array("number|float|double"=>"+/-","date|timestamp"=>"+ interval/- interval","char|clob"=>"||",);var$functions=array("length","lower","round","upper");var$grouping=array("avg","count","count distinct","max","min","sum");function
operators($Vl){return
array("=","<",">","<=",">=","!=","LIKE","LIKE %%","IN","BETWEEN","IS NULL","NOT LIKE","NOT IN","IS NOT NULL","SQL");}static
function
connect($M,$U,$D){$g=parent::connect($M,$U,$D);if(is_object($g))$g->query("ALTER SESSION SET CURSOR_SHARING = FORCE"." NLS_DATE_FORMAT = 'YYYY-MM-DD HH24:MI:SS'"." NLS_TIMESTAMP_FORMAT = 'YYYY-MM-DD HH24:MI:SS.FF'"." NLS_TIMESTAMP_TZ_FORMAT = 'YYYY-MM-DD HH24:MI:SS.FF TZH:TZM'");return$g;}function
__construct(Db$g){parent::__construct($g);$this->types=array('Numbers'=>array("number"=>38,"binary_float"=>12,"binary_double"=>21),'Date and time'=>array("date"=>19,"timestamp"=>29,"interval year"=>12,"interval day"=>28),'Strings'=>array("char"=>2000,"varchar2"=>4000,"nchar"=>2000,"nvarchar2"=>4000,"clob"=>4294967295,"nclob"=>4294967295),'Binary'=>array("raw"=>2000,"long raw"=>2147483648,"blob"=>4294967295,"bfile"=>4294967296),'Geometry'=>array("sdo_geometry"=>0),);}function
begin(){return$this->conn->begin();}function
convertSearch($u,array$W,array$n){$T=$n["type"];$kj=strpos($W["op"],"LIKE")!==false;if($T=="xmltype")return"XMLSERIALIZE(CONTENT $u AS VARCHAR2(4000))";if($T=="json")return"JSON_SERIALIZE($u)";if(preg_match('~^(date|timestamp)~',$T))return"TO_CHAR($u, 'YYYY-MM-DD HH24:MI:SS')";if(preg_match('~char~',$T)||(preg_match('~clob~',$T)&&$kj))return$u;return(!$kj&&preg_match(number_type(),$T)?$u:"TO_CHAR($u)");}function
quoteBinary($Dk){return"HEXTORAW(".q(bin2hex($Dk)).")";}function
typeName(\stdClass$n){return
strtolower(parent::typeName($n));}function
hasCStyleEscapes(){return
true;}function
select($Q,array$L,array$Z,array$r,array$yi=array(),$z=1,$C=0,$Hj=false){if(in_array("*",$L)){$Yb=array();$le=false;foreach(fields($Q)as$B=>$n){$Fa=convert_field($n);$le=($le||$Fa);$Yb[]=($Fa?"$Fa AS ":"").idf_escape($B);}if($le)$L=$Yb;}return
parent::select($Q,$L,$Z,$r,$yi,$z,$C,$Hj);}function
allFields(){$H=array();$J=get_rows('SELECT c.table_name "tab", c.column_name "field", c.data_type "type", c.nullable "nullable",
	c.data_precision "precision", c.data_scale "scale", c.char_col_decl_length "char_length"
FROM all_tab_columns c
WHERE '.where_owner("c.owner").'
ORDER BY c.table_name, c.column_id',$this->conn);foreach($J
as$I){$y="$I[precision],$I[scale]";$I["length"]=(strpos($I["type"],"(")?"":($y==","?$I["char_length"]:$y));$I["type"]=strtolower($I["type"]);$I["null"]=($I["nullable"]=="Y");$H[$I["tab"]][]=$I;}return$H;}}function
idf_escape($u){return'"'.str_replace('"','""',$u).'"';}function
table($u){return
idf_escape($u);}function
get_databases($fe){$H=get_vals("SELECT username FROM all_users WHERE oracle_maintained = 'N' ORDER BY 1");return($H?:get_vals("SELECT username FROM all_users ORDER BY 1"));}function
limit($F,$Z,$z,$ci=0,$Uk=" "){return($ci?" * FROM (SELECT t.*, rownum AS rnum FROM (SELECT $F$Z) t WHERE rownum <= ".($z+$ci).") WHERE rnum > $ci":($z?" * FROM (SELECT $F$Z) WHERE rownum <= ".($z+$ci):" $F$Z"));}function
limit1($Q,$F,$Z,$Uk="\n"){return" $F$Z";}function
db_collation($k,array$Cb){return
get_val("SELECT value FROM nls_database_parameters WHERE parameter = 'NLS_CHARACTERSET'");}function
logged_user(){return
get_val("SELECT USER FROM DUAL");}function
where_owner($Ni="owner"){return"$Ni = ".q(DB);}function
views_table($e){return"(SELECT $e FROM all_views WHERE ".where_owner().")";}function
objects_table(){return"(SELECT object_name, DECODE(object_type, 'VIEW', 'view', 'table') object_type FROM all_objects WHERE ".where_owner()." AND object_type IN ('TABLE', 'VIEW'))";}function
tables_list(){return
get_key_vals("SELECT * FROM ".objects_table()." ORDER BY 1");}function
count_tables(array$j){$H=array();foreach($j
as$k)$H[$k]=get_val("SELECT COUNT(*) FROM all_objects WHERE object_type IN ('TABLE', 'VIEW') AND owner = ".q($k));return$H;}function
table_status($B="",$Md=false){$H=array();$Jk=q($B);if($Md||$B!=""){foreach(get_rows('SELECT object_name "Name", object_type "Engine" FROM '.objects_table().($B!=""?" WHERE object_name = $Jk":"").' ORDER BY 1')as$I)$H[$I["Name"]]=$I;return$H;}foreach(get_rows('SELECT t.table_name "Name", \'table\' "Engine", s.bytes "Data_length", i.bytes "Index_length", t.num_rows "Rows"
FROM all_tables t
LEFT JOIN (SELECT segment_name, SUM(bytes) bytes FROM user_segments WHERE segment_type LIKE \'TABLE%\' GROUP BY segment_name) s ON s.segment_name = t.table_name
LEFT JOIN (SELECT i.table_name, SUM(s.bytes) bytes FROM user_indexes i
	JOIN user_segments s ON s.segment_name = i.index_name AND s.segment_type LIKE \'INDEX%\' GROUP BY i.table_name) i ON i.table_name = t.table_name
WHERE '.where_owner("t.owner")."
UNION SELECT view_name, 'view', 0, 0, 0 FROM ".views_table("view_name")."
ORDER BY 1")as$I)$H[$I["Name"]]=$I;return$H;}function
is_view(array$R){return$R["Engine"]=="view";}function
fk_support(array$R){return
true;}function
fields($Q){$H=array();$if=null;foreach(get_rows("SELECT * FROM all_tab_columns WHERE table_name = ".q($Q)." AND ".where_owner()." ORDER BY column_id")as$I){$T=$I["DATA_TYPE"];$y="$I[DATA_PRECISION],$I[DATA_SCALE]";if($y==",")$y=$I["CHAR_COL_DECL_LENGTH"];elseif(strpos($T,"("))$y="";$l=$I["DATA_DEFAULT"];if($l!==null){$l=rtrim($l);if(preg_match("~^'(.*)'\$~s",$l,$A))$l=str_replace("''","'",$A[1]);}if($I["IDENTITY_COLUMN"]=="YES"){if($if===null)$if=get_key_vals("SELECT column_name, generation_type FROM all_tab_identity_cols WHERE table_name = ".q($Q)." AND ".where_owner());$l="GENERATED ".$if[$I["COLUMN_NAME"]].($I["DEFAULT_ON_NULL"]=="YES"?" ON NULL":"")." AS IDENTITY";}$Kj=array("insert"=>1,"select"=>1,"update"=>1,"order"=>1);if($I["DATA_TYPE_OWNER"]==""||$T=="XMLTYPE")$Kj["where"]=1;$H[$I["COLUMN_NAME"]]=array("field"=>$I["COLUMN_NAME"],"full_type"=>$T.($y?"($y)":""),"type"=>strtolower($T),"length"=>$y,"default"=>$l,"null"=>($I["NULLABLE"]=="Y"),"auto_increment"=>($I["IDENTITY_COLUMN"]=="YES"),"privileges"=>$Kj,);}return$H;}function
table_constraints($Q,$h=null){$H=array();foreach(get_rows('SELECT c.constraint_name "name", c.constraint_type "type", c.r_owner "r_owner", c.r_constraint_name "r_constraint", c.delete_rule "delete_rule", cc.column_name "column"
FROM all_constraints c
JOIN all_cons_columns cc ON cc.owner = c.owner AND cc.constraint_name = c.constraint_name
WHERE c.constraint_type IN (\'P\', \'U\', \'R\') AND '.where_owner("c.owner")." AND c.table_name = ".q($Q).'
ORDER BY cc.position',$h)as$I){$B=$I["name"];$H[$B]["type"]=$I["type"];$H[$B]["r_owner"]=$I["r_owner"];$H[$B]["r_constraint"]=$I["r_constraint"];$H[$B]["delete_rule"]=$I["delete_rule"];$H[$B]["columns"][]=$I["column"];}return$H;}function
indexes($Q,$h=null){$H=array();$Sb=array();foreach(table_constraints($Q,$h)as$B=>$Rb)$Sb[$B]=$Rb["type"];foreach(get_rows("SELECT aic.*, atc.data_default
FROM all_ind_columns aic
LEFT JOIN all_tab_cols atc ON aic.column_name = atc.column_name AND aic.table_name = atc.table_name AND aic.index_owner = atc.owner
WHERE aic.table_name = ".q($Q)." AND ".where_owner("aic.table_owner")."
ORDER BY aic.column_position",$h)as$I){$vf=$I["INDEX_NAME"];$Fb=$I["DATA_DEFAULT"];$Fb=($Fb?trim($Fb,'"'):$I["COLUMN_NAME"]);$T=idx($Sb,$vf);$H[$vf]["type"]=($T=="P"?"PRIMARY":($T=="U"?"UNIQUE":"INDEX"));$H[$vf]["columns"][]=$Fb;$H[$vf]["lengths"][]=($I["CHAR_LENGTH"]&&$I["CHAR_LENGTH"]!=$I["COLUMN_LENGTH"]?$I["CHAR_LENGTH"]:null);$H[$vf]["descs"][]=($I["DESCEND"]&&$I["DESCEND"]=="DESC"?'1':null);}uasort($H,function($ha,$Pa){$yi=array("PRIMARY"=>0,"UNIQUE"=>1,"INDEX"=>2);return$yi[$ha["type"]]-$yi[$Pa["type"]];});return$H;}function
view($B){$J=get_rows('SELECT text "select" FROM '.views_table("view_name, text").' WHERE view_name = '.q($B));return($J?$J[0]:array());}function
collations(){return
array();}function
information_schema($k,$K=""){return
in_array($K!=""?$K:$k,array("INFORMATION_SCHEMA","SYS","SYSTEM"));}function
error(){return
h(connection()->error);}function
explain(Db$g,$F){$g->query("EXPLAIN PLAN FOR $F");return$g->query("SELECT * FROM plan_table");}function
found_rows(array$R,array$Z){}function
auto_increment(){return"";}function
alter_table($Q,$B,array$o,array$he,$f,$nd,$Bb,$La,$bj){$b=$Vc=array();$Fi=($Q?fields($Q):array());foreach($o
as$n){$W=$n[1];if($W&&$n[0]!=""&&idf_escape($n[0])!=$W[0])queries("ALTER TABLE ".table($Q)." RENAME COLUMN ".idf_escape($n[0])." TO $W[0]");$Ei=$Fi[$n[0]];if($W&&$Ei){$ei=process_field($Ei,$Ei);if($W[2]==$ei[2])$W[2]="";}if($W){list($W[2],$W[3])=array($W[3],$W[2]);$b[]=($Q!=""?($n[0]!=""?"MODIFY (":"ADD ("):"  ").implode($W).($Q!=""?")":"");}else$Vc[]=idf_escape($n[0]);}if($Q=="")return
queries("CREATE TABLE ".table($B)." (\n".implode(",\n",array_merge($b,$he))."\n)");return(!$b||queries("ALTER TABLE ".table($Q)."\n".implode("\n",$b)))&&(!$Vc||queries("ALTER TABLE ".table($Q)." DROP (".implode(", ",$Vc).")"))&&($Q==$B||queries("ALTER TABLE ".table($Q)." RENAME TO ".table($B)));}function
alter_indexes($Q,$b){$Vc=array();$Rj=array();foreach($b
as$W){if($W[0]!="INDEX"){$W[2]=preg_replace('~ DESC$~','',$W[2]);$i=($W[2]=="DROP"?"\nDROP CONSTRAINT ".idf_escape($W[1]):"\nADD".($W[1]!=""?" CONSTRAINT ".idf_escape($W[1]):"")." $W[0] ".($W[0]=="PRIMARY"?"KEY ":"")."(".implode(", ",$W[2]).")");array_unshift($Rj,"ALTER TABLE ".table($Q).$i);}elseif($W[2]=="DROP")$Vc[]=idf_escape($W[1]);else$Rj[]="CREATE INDEX ".idf_escape($W[1]!=""?$W[1]:uniqid($Q."_"))." ON ".table($Q)." (".implode(", ",$W[2]).")";}if($Vc)array_unshift($Rj,"DROP INDEX ".implode(", ",$Vc));foreach($Rj
as$F){if(!queries($F))return
false;}return
true;}function
foreign_keys($Q){$H=array();$qm=array();foreach(table_constraints($Q)as$B=>$Rb){if($Rb["type"]=="R"){$H[$B]=array("source"=>$Rb["columns"],"target"=>array(),"on_delete"=>$Rb["delete_rule"],"on_update"=>null,);$qm[$B]=array($Rb["r_owner"],$Rb["r_constraint"]);}}if($qm){$Z=array();foreach($qm
as$nm)$Z[]="(owner = ".q($nm[0])." AND constraint_name = ".q($nm[1]).")";foreach(get_rows("SELECT owner, constraint_name, table_name, column_name FROM all_cons_columns WHERE ".implode(" OR ",array_unique($Z))." ORDER BY position")as$I){foreach($qm
as$B=>$nm){if($nm==array($I["OWNER"],$I["CONSTRAINT_NAME"])){$H[$B]["db"]=$I["OWNER"];$H[$B]["table"]=$I["TABLE_NAME"];$H[$B]["target"][]=$I["COLUMN_NAME"];}}}}return$H;}function
trigger($B,$Q){if($B=="")return
array();$J=get_rows('SELECT trigger_name "Trigger", trigger_type "Type", triggering_event "Event", trigger_body "Statement"
FROM all_triggers
WHERE trigger_name = '.q($B)." AND ".where_owner());$H=reset($J);if($H){$T=$H["Type"];$H["Timing"]=(preg_match('~^(BEFORE|AFTER|INSTEAD OF)~',$T,$A)?$A[1]:$T);$H["Type"]=(preg_match('~EACH ROW~',$T)||$T=="INSTEAD OF"?"FOR EACH ROW":"");}return($H?:array());}function
triggers($Q){$H=array();foreach(get_rows("SELECT trigger_name, trigger_type, triggering_event FROM all_triggers WHERE table_name = ".q($Q)." AND ".where_owner())as$I)$H[$I["TRIGGER_NAME"]]=array(preg_replace('~ (STATEMENT|EACH ROW)$~','',$I["TRIGGER_TYPE"]),$I["TRIGGERING_EVENT"]);return$H;}function
trigger_options(){return
array("Timing"=>array("BEFORE","AFTER","INSTEAD OF"),"Event"=>array("INSERT","UPDATE","DELETE","INSERT OR UPDATE","INSERT OR DELETE","UPDATE OR DELETE","INSERT OR UPDATE OR DELETE"),"Type"=>array("FOR EACH ROW",""),);}function
truncate_tables(array$S){return
apply_queries("TRUNCATE TABLE",$S);}function
drop_views(array$In){return
apply_queries("DROP VIEW",$In);}function
drop_tables(array$S){return
apply_queries("DROP TABLE",$S);}function
last_id($G){return"0";}function
create_database($k,$Bb){$H=queries("CREATE USER ".idf_escape($k)." NO AUTHENTICATION");return($H?queries("GRANT UNLIMITED TABLESPACE TO ".idf_escape($k)):$H);}function
drop_databases(array$j){$H=true;foreach($j
as$k)$H=!!queries("DROP USER ".idf_escape($k)." CASCADE")&&$H;return$H;}function
rename_database($B,$Bb){return!!queries("ALTER USER ".idf_escape(DB)." RENAME TO ".idf_escape($B));}function
show_variables(){return
get_rows('SELECT name, display_value FROM v$parameter');}function
show_status(){$H=array();$J=get_rows('SELECT * FROM v$instance');foreach(reset($J)as$x=>$W)$H[]=array($x,$W);return$H;}function
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
unconvert_field(array$n,$H){return($n["type"]=="sdo_geometry"?"SDO_UTIL.FROM_WKTGEOMETRY($H)":$H);}function
support($Nd){return
preg_match('~^(columns|database|drop_col|fast_status|indexes|descidx|processlist|sql|status|table|trigger|variables|view|view_trigger)$~',$Nd);}}class
Adminer{static$instance;var$error='';function
name(){return"<a href='https://www.adminer.org/'".target_blank()." id='h1'><img src='".h(preg_replace("~\\?.*~","",ME)."?file=logo.svg&version=6.1.2+30b83850")."' width='24' height='24' alt='' id='logo'>Adminer</a>";}function
credentials(){return
array(SERVER,$_GET["username"],get_password());}function
connectSsl(){}function
permanentLogin($i=false){return
password_file($i);}function
bruteForceKey(){return$_SERVER["REMOTE_ADDR"];}function
verifyLoginToken(){return
true;}function
serverName($M){return
h($M);}function
database(){return
DB;}function
databases($fe=true){return
get_databases($fe);}function
pluginsLinks(){}function
operators($Vl=null){return
driver()->operators($Vl);}function
schemas(){$H=schemas();if($_GET["ns"]!=""&&!in_array($_GET["ns"],$H))array_unshift($H,$_GET["ns"]);return$H;}function
queryTimeout(){return
2;}function
afterConnect(){}function
headers(){}function
csp(array$gc){return$gc;}function
verifyVersion(){return
true;}function
serviceWorker(){service_worker();}function
manifest(){$df=$_SERVER["HTTP_HOST"]?:$_SERVER["SERVER_NAME"];$Sk=preg_replace('~\?.*~','',ME)?:'.';return
array('name'=>"Adminer".($df!=""?" - $df":""),'short_name'=>'Adminer','description'=>'Database management in a single PHP file','start_url'=>$Sk,'scope'=>$Sk,'display'=>'minimal-ui','icons'=>array(array('src'=>preg_replace("~\\?.*~","",ME)."?file=logo.svg&version=6.1.2+30b83850",'sizes'=>'any','type'=>'image/svg+xml')),);}function
head($lc=null){return
true;}function
bodyClass(){echo" adminer";}function
css(){$H=array();foreach(array("","-dark")as$nh){$p="adminer$nh.css";if(file_exists($p)){$Td=file_get_contents($p);$H["$p?v=".crc32($Td)]=($nh?"dark":(preg_match('~prefers-color-scheme:\s*dark~',$Td)?'':'light'));}}return$H;}function
loginForm(){echo"<table class='layout'>\n",adminer()->loginFormField('driver','<tr><th>'.'System'.'<td>',html_select("auth[driver]",SqlDriver::$drivers,DRIVER,on('change','loginDriver'))),adminer()->loginFormField('server','<tr><th>'.'Server'.'<td>',"<input name='auth[server]' value='".h(SERVER)."' title='".'hostname[:port] or :socket'."' placeholder='localhost' autocapitalize='off'>"),adminer()->loginFormField('username','<tr><th>'.'Username'.'<td>','<input name="auth[username]" id="username" autofocus value="'.h($_GET["username"]).'" autocomplete="username" autocapitalize="off">'.script("fire(qs('#username').form['auth[driver]'], 'change');")),adminer()->loginFormField('password','<tr><th>'.'Password'.'<td>','<input type="password" name="auth[password]" autocomplete="current-password">'),adminer()->loginFormField('db','<tr><th>'.'Database'.'<td>','<input name="auth[db]" value="'.h($_GET["db"]).'" autocapitalize="off">'),"</table>\n","<p><input type='submit' value='".'Login'."'>\n",checkbox("auth[permanent]",1,$_COOKIE["adminer_permanent"],'Permanent login')."\n";}function
loginFormField($B,$Ue,$X){return$Ue.$X."\n";}function
login($Eg,$D){if($D=="")return'Adminer does not support accessing a database without a password.'.require_password_link(null);if(!Driver::$passwords)return'The database does not support passwords.'.require_password_link($D);if(!password_required())return'The server accepts any password, so filling it in protects nothing.'.require_password_link($D);return
true;}function
tableName(array$Vl){return
h($Vl["Name"]);}function
fieldName(array$n,$yi=0){$T=$n["full_type"].($n["null"]?" NULL":"");$f=$n["comment"];return'<span title="'.h($T.($f!=""?($T?": ":"").$f:'')).'">'.h($n["field"]).'</span>';}function
commentValue($T,$f){if($f==""||$T=='SCHEMA'||$T=='TABLE'||$T=='COLUMN')return
h($f);$zj=function($Dk,$ib='td'){return
preg_replace('~^~m','<tr>',preg_replace('~\|~',"<$ib>",preg_replace('~\|$~m',"",rtrim($Dk))));};$Q='(\+--[-+]+\+\n)';$I='(\| .* \|\n)';return"<pre>\n".preg_replace_callback("~^$Q?$I$Q?($I*)$Q?~m",function($A)use($zj){return"<table>\n".($A[1]?"<thead>".$zj($A[2],'th')."<tbody>\n":$zj($A[2])).$zj($A[4])."\n</table>";},preg_replace('~(\n(    -|mysql)&gt; )(.+)~',"\\1<code class='jush-sql'>\\3</code>",preg_replace('~(.+)\n---+\n~',"<b>\\1</b>\n",h($f))))."</pre>\n";}function
commentInput($T,$c,$f){$X=h($f);return(preg_match('~\n~',$X)?"<textarea$c rows='2' cols='".($T=='TABLE'?20:30)."' style='vertical-align: bottom;'>\n$X</textarea>":"<input$c value='$X'>");}function
selectLinks(array$Vl,$N=""){$B=$Vl["Name"];echo'<p class="links">';$_g=array();if($B!="")$_g["select"]='Select data';if(support("table")||support("indexes"))$_g["table"]='Show structure';if(support("table")){if(is_view($Vl)){if(support("view"))$_g["view"]='Alter view';}elseif(function_exists('Adminer\alter_table')&&$B!="")$_g["create"]='Alter table';}if($N!==null)$_g["edit"]='New item';foreach($_g
as$x=>$W)echo" <a href='".h(ME)."$x=".url_escape($B).($x=="edit"?$N:"")."'".bold(isset($_GET[$x])).">$W</a>";echo"\n";}function
foreignKeys($Q){return
foreign_keys($Q);}function
backwardKeys($Q,$Ul){return
array();}function
backwardKeysPrint(array$Ra,array$I){}function
selectQuery($F,$Fl,$Ld=false){$H="\n";if(!$Ld&&($Mn=driver()->warnings())){$t="warnings";$H=", <a href='#$t' class='toggle'>".'Warnings'."</a>"."$H<div id='$t' class='hidden'>\n$Mn</div>\n";}return"<p><code class='jush-".JUSH."'>".h(str_replace("\n"," ",$F))."</code> <span class='time'>(".format_time($Fl).")</span>".(support("sql")?" <a href='".h(ME)."sql=".url_escape($F)."' class='hover'>".'Edit'."</a>":"").$H;}function
sqlCommandQuery($F){return
shorten_utf8(trim($F),1000);}function
sqlPrintAfter(){}function
sqlUrlLength(){return
2000;}function
explain(Db$g,$F,array$Bi){$G=explain($g,$F);if(!$G)return"";ob_start();print_select_result($G,$g,$Bi);return
ob_get_clean();}function
rowDescription($Q){return"";}function
rowDescriptions(array$J,array$ie){return$J;}function
selectLink($W,array$n){}function
selectVal($W,$_,array$n,$Ii){$H=($W===null?"<i>NULL</i>":(preg_match("~char|binary|boolean~",$n["type"])&&!preg_match("~var~",$n["type"])?"<code>$W</code>":(preg_match('~^jsonb?$~',$n["full_type"])?"<code class='jush-json'>$W</code>":$W)));if(is_blob($n)&&!is_utf8($W))$H="<i>".lang_format(array('%d byte','%d bytes'),strlen($Ii))."</i>";return($_?"<a href='".h($_)."'".(is_url($_)?target_blank():"").">$H</a>":$H);}function
editVal($W,array$n){return$W;}function
config(){return
array();}function
tableStructurePrint(array$o,$Vl=null){echo"<div class='scrollable'>\n","<table class='nowrap odds'>\n","<thead><tr><th>".'Column'."<th>".'Type'.(support("comment")?"<th>".'Comment':"")."<tbody>\n";$yn=(support("type")?types():array());foreach($o
as$n){echo"<tr><th>".h($n["field"]);$T=h($n["full_type"]);$Bb=h($n["collation"]);echo"<td><span title='$Bb'>".(in_array($T,$yn)?"<a href='".h(ME.'type='.url_escape($T))."'>$T</a>":$T.($Bb&&isset($Vl["Collation"])&&$Bb!=$Vl["Collation"]?" $Bb":""))."</span>",($n["null"]?" <i>NULL</i>":""),($n["auto_increment"]?" <i>".'Auto Increment'."</i>":""),(isset($n["default"])?" <span title='".'Default value'."'>[<b>".($n["generated"]?"<code class='jush-".JUSH."'>".shorten_utf8(preg_replace('~\s+~',' ',ltrim($n["default"])),80,"</code>"):h($n["default"]))."</b>]</span>":""),(support("comment")?"<td>".adminer()->commentValue('COLUMN',$n["comment"]):""),"\n";}echo"</table>\n","</div>\n";}function
tableIndexesPrint(array$w,array$Vl){$Wi=false;foreach($w
as$B=>$v)$Wi|=!!$v["partial"];echo"<table>\n";$vc=first(driver()->indexAlgorithms($Vl));foreach($w
as$B=>$v){ksort($v["columns"]);$Hj=array();foreach($v["columns"]as$x=>$W)$Hj[]="<i>".h($W)."</i>".($v["lengths"][$x]?"(".h($v["lengths"][$x]).")":"").($v["descs"][$x]?" DESC":"");echo"<tr title='".h($B)."'>","<th>".h($v["type"]).($vc&&$v['algorithm']!=$vc?" (".h($v['algorithm']).")":""),"<td>".implode(", ",$Hj);if($Wi)echo"<td>".($v['partial']?"<code class='jush-".JUSH."'>WHERE ".h($v['partial']):"");echo"\n";}echo"</table>\n";}function
namePattern($T){if($T=="FOREIGN"||$T=="CHECK")return"";if($T=="TRIGGER")return"{table}_{timing}{event}";return(JUSH=="sql"?"":"{table}_")."{columns}";}function
selectColumnInput($c,array$e,$X="",$oj=""){return
select_input($c,$e,$X,$oj);}function
selectColumnsPrint(array$L,array$e){print_fieldset("select",'Select',$L);$s=0;$L[""]=array();foreach($L
as$x=>$W){$W=idx($_GET["columns"],$x,array());$d=adminer()->selectColumnInput(" name='columns[$s][col]' data-default=''".on('change',($x!==""?'selectFieldChange':'selectAddRow')),$e,$W["col"]);echo"<div>".(driver()->functions||driver()->grouping?html_select("columns[$s][fun]",array(-1=>"")+array_filter(array('Functions'=>driver()->functions,'Aggregation'=>driver()->grouping)),$W["fun"]," data-default=''".on('change',($x!==""?'helpClose':'selectFunAddRow')).on_help_value(' (.*)|$','($1)'))."($d)":$d)."</div>\n";$s++;}echo"</div></fieldset>\n";}function
selectSearchPrint(array$Z,array$e,array$w,$Vl=null){print_fieldset("search",'Search',$Z);foreach($w
as$s=>$v){if($v["type"]=="FULLTEXT")echo"<div>(<i>".implode("</i>, <i>",array_map('Adminer\h',$v["columns"]))."</i>) ".h(driver()->fulltextOperator)," <input type='search' name='fulltext[$s]' value='".h(idx($_GET["fulltext"],$s))."' data-default=''".on('input','selectFieldChange').">",(JUSH=='sql'?checkbox("boolean[$s]",1,isset($_GET["boolean"][$s]),"BOOL"):''),"</div>\n";}$si=adminer()->operators($Vl);foreach(array_merge((array)$_GET["where"],array(array()))as$s=>$W){if(!$W||(("$W[col]$W[val]"!=""||preg_match('~NULL$~',$W["op"]))&&in_array($W["op"],$si)))echo"<div>".adminer()->selectColumnInput(" name='where[$s][col]' data-default=''".on('change',($W?'selectFieldChange':'selectAddRow')),$e,$W["col"],"(".'anywhere'.")"),html_select("where[$s][op]",$si,$W["op"]," data-default='".h(first($si))."'".on('change','selectFirstChange')),"<input type='search' name='where[$s][val]' value='".h($W["val"])."' data-default=''".on('input','selectFirstChange').on('keydown','selectSearchKeydown').on('search','selectSearchSearch').">","</div>\n";}echo"</div></fieldset>\n";}function
selectOrderPrint(array$yi,array$e,array$w){print_fieldset("sort",'Sort',$yi);$s=0;foreach((array)$_GET["order"]as$x=>$W){if($W!=""){echo"<div>".adminer()->selectColumnInput(" name='order[$s]' data-default=''".on('change','selectFieldChange'),$e,$W),checkbox("desc[$s]",1,isset($_GET["desc"][$x]),'descending')."</div>\n";$s++;}}echo"<div>".adminer()->selectColumnInput(" name='order[$s]' data-default=''".on('change','selectAddRow'),$e),checkbox("desc[$s]",1,false,'descending')."</div>\n","</div></fieldset>\n";}function
selectLimitPrint($z){echo"<fieldset><legend>".'Limit'."</legend><div>","<input type='number' name='limit' class='size' value='".h($z?:"")."' data-default='50'".on('input','selectFieldChange').">","</div></fieldset>\n";}function
selectLengthPrint($um){echo"<fieldset><legend>".'Text length'."</legend><div>","<input type='number' name='text_length' class='size' value='".h($um)."' data-default='100'>","</div></fieldset>\n";}function
selectActionPrint(array$w){echo"<fieldset><legend>".'Action'."</legend><div>","<input type='submit' value='".'Select'."'>"," <span id='noindex' title='".'Full table scan'."'></span>","<script".nonce().">\n","const indexColumns = ";$e=array();foreach($w
as$v){$kc=reset($v["columns"]);if($v["type"]!="FULLTEXT"&&$kc)$e[$kc]=1;}$e[""]=1;foreach($e
as$x=>$W)json_row($x);echo";\n","selectFieldChange.call(qs('#form')['select']);\n","</script>\n","</div></fieldset>\n";}function
selectCommandPrint(){return!information_schema(DB);}function
selectImportPrint(){return!information_schema(DB);}function
selectEmailPrint(array$jd,array$e){}function
selectColumnsProcess(array$e,array$w){$L=array();$r=array();foreach((array)$_GET["columns"]as$x=>$W){if($W["fun"]=="count"||($W["col"]!=""&&(!$W["fun"]||in_array($W["fun"],driver()->functions)||in_array($W["fun"],driver()->grouping)))){$L[$x]=apply_sql_function($W["fun"],($W["col"]!=""?idf_escape($W["col"]):"*"));if(!in_array($W["fun"],driver()->grouping))$r[]=$L[$x];}}return
array($L,$r);}function
selectSearchProcess(array$o,array$w,$Vl=null){$H=array();foreach($w
as$s=>$v){if($v["type"]=="FULLTEXT"&&idx($_GET["fulltext"],$s)!="")$H[]=driver()->fulltextSql($s,$v,$_GET["fulltext"][$s],isset($_GET["boolean"][$s]));}$si=adminer()->operators($Vl);foreach((array)$_GET["where"]as$x=>$W){$W+=array("col"=>"","op"=>first($si),"val"=>"");$_GET["where"][$x]=$W;$_b=$W["col"];if(("$_b$W[val]"!=""||preg_match('~NULL$~',$W["op"]))&&in_array($W["op"],$si)){if($W["op"]=="SQL"&&(!$_POST||!verify_token()))SqlDb::$untrusted=true;$Lb=array();foreach(($_b!=""?array($_b=>idx($o,$_b,array())):$o)as$B=>$n){if($_b!=""||is_searchable($n,$W))$Lb[]=adminer()->selectSearchCondition(driver()->convertSearch(idf_escape($B),$W,$n),$W["op"],$W["val"]);}$H[]=(count($Lb)==1?$Lb[0]:($Lb?"(".implode(" OR ",$Lb).")":"1 = 0"));}}return$H;}function
selectSearchCondition($d,$oi,$W){if(preg_match('~IN$~',$oi))return"$d $oi ".($W!=""?process_in($W):"(NULL)");if($oi=="BETWEEN")return"$d $oi ".process_between($W);if($oi=="SQL")return"$d $W";if(preg_match('~^(I?LIKE) %%$~',$oi,$A))return"$d $A[1] ".q("%$W%");if($oi=="FIND_IN_SET")return"$oi(".q($W).", $d)";return"$d $oi".(!preg_match('~NULL$~',$oi)?" ".q($W):"");}function
selectOrderProcess(array$o,array$w){$H=array();foreach((array)$_GET["order"]as$x=>$W){if($W!="")$H[]=(preg_match('~^((COUNT\(DISTINCT |[A-Z0-9_]+\()(`(?:[^`]|``)+`|"(?:[^"]|"")+")\)|COUNT\(\*\))$~',$W)?$W:idf_escape($W)).(isset($_GET["desc"][$x])?" DESC".(JUSH=='pgsql'&&idx($o[$W],"null")?" NULLS LAST":""):"");}return$H;}function
selectLimitProcess(){return(isset($_GET["limit"])?intval($_GET["limit"]):50);}function
selectLengthProcess(){return(isset($_GET["text_length"])?"$_GET[text_length]":"100");}function
selectEmailProcess(array$Z,array$ie){return
false;}function
selectQueryBuild(array$L,array$Z,array$r,array$yi,$z,$C){return"";}function
messageQuery($F,$wm,$Ld=false){restart_session();$af=&get_session("queries");if(!idx($af,$_GET["db"]))$af[$_GET["db"]]=array();if(strlen($F)>1e6)$F=preg_replace('~[\x80-\xFF]+$~','',substr($F,0,1e6))."\n…";$af[$_GET["db"]][]=array($F,time(),$wm);$Al="sql-".count($af[$_GET["db"]]);$H="<a href='#$Al' class='toggle'>".'SQL command'."</a> ".copy_icon()."\n";if(!$Ld&&($Mn=driver()->warnings())){$t="warnings-".count($af[$_GET["db"]]);$H="<a href='#$t' class='toggle'>".'Warnings'."</a>, $H<div id='$t' class='hidden'>\n$Mn</div>\n";}return" <span class='time'>".@date("H:i:s")."</span>"." $H<div id='$Al' class='hidden'><pre><code class='jush-".JUSH."'>".shorten_utf8($F,1e4)."</code></pre>".($wm?" <span class='time'>($wm)</span>":'').(support("sql")?'<p><a href="'.h(str_replace("db=".url_escape(DB),"db=".url_escape($_GET["db"]),ME).'sql=&history='.(count($af[$_GET["db"]])-1)).'">'.'Edit'.'</a>':'').'</div>';}function
error(){return
error();}function
editRowPrint($Q,array$o,$I,$ln,$F='',$wm=''){echo($F!=""?"<p><code class='jush-".JUSH."'>".h(str_replace("\n"," ",$F))."</code> <span class='time'>($wm)</span>\n":"");}function
editFunctions(array$n){$H=($n["null"]?"NULL/":"");$Qe=isset($_GET["select"])||where($_GET);foreach(array(driver()->insertFunctions,driver()->editFunctions)as$x=>$xe){if(!$x||(!isset($_GET["call"])&&$Qe)){foreach($xe
as$kj=>$W){if(!$kj||preg_match("~$kj~",$n["type"]))$H
.="/$W";}}if($x&&$xe&&!preg_match('~set|bool~',$n["type"])&&!is_blob($n))$H
.="/SQL";}if($n["auto_increment"]&&!$Qe)$H='Auto Increment';return
explode("/",$H);}function
editInput($Q,array$n,$c,$X){if($n["type"]=="enum")return(isset($_GET["select"])?"<label><input type='radio'$c value='orig' checked><i>".'original'."</i></label> ":"").enum_input("radio",$c,$n,$X,"NULL");return"";}function
editHint($Q,array$n,$X){return"";}function
processInput(array$n,$X,$we=""){if($we=="SQL")return$X;$B=$n["field"];$H=q($X);if(preg_match('~^(now|getdate|uuid)$~',$we))$H="$we()";elseif(preg_match('~^current_(date|timestamp)$~',$we))$H=$we;elseif(preg_match('~^([+-]|\|\|)$~',$we))$H=idf_escape($B)." $we $H";elseif(preg_match('~^[+-] interval$~',$we))$H=idf_escape($B)." $we ".(preg_match("~^(\\d+|'[0-9.: -]') [A-Z_]+\$~i",$X)&&JUSH!="pgsql"?$X:$H);elseif(preg_match('~^(addtime|subtime|concat)$~',$we))$H="$we(".idf_escape($B).", $H)";elseif(preg_match('~^(md5|sha1|password|encrypt)$~',$we))$H="$we($H)";return
unconvert_field($n,$H);}function
dumpOutput(){$H=array('text'=>'open','file'=>'save');if(function_exists('gzencode'))$H['gz']='gzip';return$H;}function
dumpFormat(){return(support("dump")?array('sql'=>'SQL'):array())+array('csv'=>'CSV,','csv;'=>'CSV;','tsv'=>'TSV');}function
dumpPrint(){}function
dumpDatabase($k){}function
dumpTable($Q,$Ml,$Vf=0){if($_POST["format"]!="sql"){echo"\xef\xbb\xbf";if($Ml)dump_csv(array_keys(fields($Q)));}else{if($Vf==2){$o=array();foreach(fields($Q)as$B=>$n)$o[]=idf_escape($B)." ".full_type_sql($n);$i="CREATE TABLE ".table($Q)." (".implode(", ",$o).")";}else$i=create_sql($Q,$_POST["auto_increment"],$Ml);set_utf8mb4($i);if($Ml&&$i){if(($Ml=="DROP+CREATE"&&!function_exists('Adminer\drop_sql'))||$Vf==1)echo"DROP ".($Vf==2?"VIEW":"TABLE")." IF EXISTS ".table($Q).";\n";if($Vf==1)$i=remove_definer($i);echo"$i;\n\n";}}}function
dumpData($Q,$Ml,$F,array$L=array(),array$Z=array(),array$r=array(),array$yi=array()){if($Ml){$Pg=(JUSH=="sqlite"?0:1048576);$o=array();$jf=false;if($_POST["format"]=="sql"){if($Ml=="TRUNCATE+INSERT"&&!function_exists('Adminer\truncate_all_sql'))echo
truncate_sql($Q).";\n";$o=fields($Q);if(JUSH=="mssql"){foreach($o
as$n){if($n["auto_increment"]){echo"SET IDENTITY_INSERT ".table($Q)." ON;\n";$jf=true;break;}}}}$G=($F!=""?connection()->query($F,1):driver()->select($Q,($L?:array("*")),$Z,$r,$yi,0));if($G){$Ff="";$cb="";$dg=array();$ye=array();$Ol="";$Od=($Q!=''?'fetch_assoc':'fetch_row');$cc=0;while($I=$G->$Od()){if(!$dg){$Y=array();foreach($I
as$W){$n=$G->fetch_field();if(idx($o[$n->name],'generated')){$ye[$n->name]=true;continue;}$dg[]=$n->name;$x=idf_escape($n->name);$Y[]="$x = VALUES($x)";}$Ol=($Ml=="INSERT+UPDATE"?"\nON DUPLICATE KEY UPDATE ".implode(", ",$Y):"").";\n";}foreach($I
as$x=>$W){if(is_array($W))$I[$x]=json_encode($W,256|64);}if($_POST["format"]!="sql"){if($Ml=="table"){dump_csv($dg);$Ml="INSERT";}dump_csv($I);}else{if(!$Ff)$Ff="INSERT INTO ".table($Q)." (".implode(", ",array_map('Adminer\idf_escape',$dg)).") VALUES";foreach($I
as$x=>$W){if($ye[$x]){unset($I[$x]);continue;}$n=$o[$x];$I[$x]=($W===null?"NULL":($W===false?0:unconvert_field($n,preg_match(number_type(),$n["type"])&&!preg_match('~\[~',$n["full_type"])&&is_numeric($W)?$W:(!is_blob($n)||is_utf8($W)?q($W):driver()->quoteBinary($W)))));}$Dk=($Pg?"\n":" ")."(".implode(",\t",$I).")";if(!$cb)$cb=$Ff.$Dk;elseif(JUSH=='mssql'?$cc%1000!=0:strlen($cb)+4+strlen($Dk)+strlen($Ol)<$Pg)$cb
.=",$Dk";else{echo$cb.$Ol;$cb=$Ff.$Dk;}}$cc++;}if($cb)echo$cb.$Ol;}elseif($_POST["format"]=="sql")echo"-- ".str_replace("\n"," ",connection()->error)."\n";else
dump_csv(array(connection()->error));if($jf)echo"SET IDENTITY_INSERT ".table($Q)." OFF;\n";}}function
dumpFilename($hf){return
friendly_url($hf!=""?$hf:(SERVER?:"localhost"));}function
dumpHeaders($hf,$uh=false){$Mi=$_POST["output"];$Gd=(preg_match('~sql~',$_POST["format"])?"sql":($uh?"tar":"csv"));header("Content-Type: ".($Mi=="gz"?"application/x-gzip":($Gd=="tar"?"application/x-tar":($Gd=="sql"||$Mi!="file"?"text/plain":"text/csv")."; charset=utf-8")));if($Mi=="gz"){ob_start(function($P){return
gzencode($P);},1e6);}return$Gd;}function
dumpFooter(){if($_POST["format"]=="sql")echo"-- ".gmdate("Y-m-d H:i:s e")."\n";}function
importServerPath(){return"adminer.sql";}function
importPrint(){}function
importProcess(){return
false;}function
homepage(){echo'<p class="links">'.($_GET["ns"]==""&&support("database")?'<a href="'.h(ME).'database=">'.'Alter database'."</a>\n":""),(support("scheme")?"<a href='".h(ME)."scheme='>".($_GET["ns"]!=""?'Alter schema':'Create schema')."</a>\n":""),($_GET["ns"]!==""?'<a href="'.h(ME).'schema=">'.'Database schema'."</a>\n":""),(support("privileges")?"<a href='".h(ME)."privileges='>".'Privileges'."</a>\n":"");if($_GET["ns"]!=="")echo(support("routine")?"<a href='#routines'>".'Routines'."</a>\n":""),(support("sequence")?"<a href='#sequences'>".'Sequences'."</a>\n":""),(support("type")?"<a href='#user-types'>".'User types'."</a>\n":""),(support("event")?"<a href='#events'>".'Events'."</a>\n":"");return
true;}function
navigation($mh){echo"<h1>".adminer()->name()." <span class='version'>".VERSION;$Nh=$_COOKIE["adminer_version"];echo" <a href='https://www.adminer.org/#download'".target_blank()." id='version'>".(version_compare(VERSION,$Nh)<0?h($Nh):"").version_iframe()."</a>","</span></h1>\n";if($mh=="auth"){$Mi="";foreach((array)$_SESSION["pwds"]as$Fn=>$hl){foreach($hl
as$M=>$zn){$B=h(get_setting("vendor-$Fn-$M")?:get_driver($Fn));foreach($zn
as$U=>$D){if($B&&$D!==null){$tc=$_SESSION["db"][$Fn][$M][$U];foreach(($tc?array_keys($tc):array(""))as$k)$Mi
.="<li><a href='".h(auth_url($Fn,$M,$U,$k))."'>($B) ".h("$U@").($M!=""?adminer()->serverName($M):"").h($k!=""?" - $k":"")."</a>\n";}}}}if($Mi)echo"<ul id='logins'".on('mouseover','menuOver').on('mouseout','menuOut').">\n$Mi</ul>\n";}else{$S=array();if($_GET["ns"]!==""&&!$mh&&DB!=""){connection()->select_db(DB);$S=table_status('',true);}adminer()->syntaxHighlighting($S);adminer()->databasesPrint($mh);$ja=array();if(DB==""||!$mh){if(support("sql")){$ja['sql']="<a href='".h(ME)."sql='".bold(isset($_GET["sql"])&&!isset($_GET["import"])).">".'SQL command'."</a>";$ja['import']="<a href='".h(ME)."import='".bold(isset($_GET["import"])).">".'Import'."</a>";}$ja['dump']="<a href='".h(ME)."dump=".url_escape(isset($_GET["table"])?$_GET["table"]:$_GET["select"])."' id='dump'".bold(isset($_GET["dump"])).">".'Export'."</a>";}$pf=$_GET["ns"]!==""&&!$mh&&DB!="";if($pf&&function_exists('Adminer\alter_table'))$ja['create']='<a href="'.h(ME).'create="'.bold($_GET["create"]==="").">".'Create table'."</a>";$ja=adminer()->menuActions($ja,$mh);echo($ja?"<p class='links'>\n".implode("\n",$ja)."\n":"");if($pf){if($S)adminer()->tablesPrint($S);else
echo"<p class='message'>".'No tables.'."</p>\n";}}}function
syntaxHighlighting(array$S){echo
script_src(preg_replace("~\\?.*~","",ME)."?file=jush.js&version=6.1.2+30b83850",true);$ph=preg_replace('~<(?=/script)~i','<\\',Driver::jushModule());echo($ph?script("addEventListener('DOMContentLoaded', () => {\n$ph\n});"):"");if(support("sql")){echo"<script".nonce().">\n";if($S){$_g=array();foreach($S
as$Q=>$T)$_g[]=js_escape_re($Q);echo"var jushLinks = { ".JUSH.":";json_row(js_escape(ME).(support("table")?"table":"select").'=$&','/\b(?<!\$)('.implode('|',$_g).')(?!\$)\b/g',false);$Cl=array("sql","check","event","procedure","trigger","view","type","table","processlist");if(support("routine")&&array_intersect_key($_GET,array_flip($Cl))){foreach(routines()as$I)json_row(js_escape(ME).'function='.url_escape($I["SPECIFIC_NAME"]).'&name=$&','/\b'.js_escape_re($I["ROUTINE_NAME"]).'(?=["`\]]?\()/g',false);}json_row('');echo"};\n";foreach(array("bac","bra","sqlite_quo","mssql_bra")as$W)echo"jushLinks.$W = jushLinks.".JUSH.";\n";if(array_intersect_key($_GET,array_flip(array("sql","check","event","procedure","trigger","view")))){$Hl=(isset($_GET["trigger"])?array('INSERT INTO','UPDATE','DELETE FROM'):(isset($_GET["check"])?array():(isset($_GET["view"])?array('SELECT'):null)));$Na=Driver::jushAutocomplete($S,$Hl);echo($Na?"addEventListener('DOMContentLoaded', () => { autocompleter = $Na; });\n":"");}}echo"</script>\n";}echo
script("syntaxHighlighting('".doc_version()."', '".connection()->flavor."');");}function
databasesPrint($mh){if(support("single_db"))return;$j=adminer()->databases();if(DB&&$j&&!in_array(DB,$j))array_unshift($j,DB);echo"<form action=''>\n<p id='dbs'>\n";hidden_fields_get();$qc=on('mousedown','dbMouseDown').on('change','dbChange');echo"<label title='".'Database'."'>".'DB'.": ".($j?html_select("db",array(""=>"")+group_system($j),DB,$qc):"<input name='db' value='".h(DB)."' autocapitalize='off' size='19'>\n")."</label>","<input type='submit' value='".'Use'."'".($j?" class='hidden'":"").">\n";if(support("scheme")){if($mh!="db"&&DB!=""&&connection()->select_db(DB)){echo"<br><label>".'Schema'.": ".html_select("ns",array(""=>"")+group_system(adminer()->schemas(),true),$_GET["ns"],$qc)."</label>";if($_GET["ns"]!="")set_schema($_GET["ns"]);}}foreach(array("import","sql","schema","dump","privileges")as$W){if(isset($_GET[$W])){echo
input_hidden($W);break;}}echo"</p></form>\n";}function
menuActions(array$ja,$mh){return$ja;}function
tablesPrint(array$S){echo"<ul id='tables'".on('mouseover','menuOver').on('mouseout','menuOut').">";foreach($S
as$Q=>$O){$Q="$Q";$B=adminer()->tableName($O);if($B!=""&&!$O["dependent"])echo'<li><a href="'.h(ME).'select='.url_escape($Q).'"'.bold($_GET["select"]==$Q||$_GET["edit"]==$Q,"select hover")." title='".'Select data'."'>".'select'."</a> ",(support("table")||support("indexes")?'<a href="'.h(ME).'table='.url_escape($Q).'"'.bold(in_array($Q,array($_GET["table"],$_GET["create"],$_GET["indexes"],$_GET["foreign"],$_GET["trigger"],$_GET["check"],$_GET["view"])),(is_view($O)?"view":"structure"))." title='".'Show structure'."'>$B</a>":"<span>$B</span>")."\n";}echo"</ul>\n";}function
showVariables(){return
show_variables();}function
showStatus(){return
show_status();}function
processList(){return
process_list();}function
killProcess($t){return
kill_process($t);}function
formatSizeValue($W){return
format_number($W);}}class
Plugins{private
static$append=array('dumpFormat'=>true,'dumpOutput'=>true,'editRowPrint'=>true,'editFunctions'=>true,'config'=>true);var$plugins;var$drivers=array();var$driverFiles=array();var$error='';private$hooks=array();function
__construct($sj){$Uc=SqlDriver::$drivers;$We=" href='https://www.adminer.org/plugins/#use'".target_blank();if($sj===null){$sj=array();$Va="adminer-plugins";if(is_dir($Va)){foreach(glob("$Va/*.php")as$p){$Ud=SqlDriver::$drivers;$this->includeOnce($p);foreach(array_diff_key(SqlDriver::$drivers,$Ud)as$t=>$B)$this->driverFiles[$t]=$p;}}if(file_exists("$Va.php")){$rf=$this->includeOnce("$Va.php");if(is_array($rf)){foreach($rf
as$x=>$pj)$sj[is_object($pj)?get_class($pj):$x]=$pj;}else$this->error
.=sprintf('%s must <a%s>return an array</a>.',"<b>$Va.php</b>",$We)."<br>";}foreach(get_declared_classes()as$xb){if(!$sj[$xb]&&(preg_match('~^Adminer\w~i',$xb)||is_subclass_of($xb,'Adminer\Plugin'))){$dk=new
\ReflectionClass($xb);$Tb=$dk->getConstructor();if($Tb&&$Tb->getNumberOfRequiredParameters())$this->error
.=sprintf('<a%s>Configure</a> %s in %s.',$We,"<b>$xb</b>","<b>$Va.php</b>")."<br>";else$sj[$xb]=new$xb;}}}$Kf=array_filter($sj,function($pj){return!is_object($pj);});if($Kf){$this->error
.=sprintf('Every plugin must <a%s>be an object</a>.',$We)."<br>";$sj=array_diff_key($sj,$Kf);}$this->drivers=array_diff_key(SqlDriver::$drivers,$Uc);$this->plugins=$sj;$oa=new
Adminer;$sj[]=$oa;$dk=new
\ReflectionObject($oa);foreach($dk->getMethods()as$jh){foreach($sj
as$pj){$B=$jh->getName();if(method_exists($pj,$B))$this->hooks[$B][]=$pj;}}}function
includeOnce($p){return
include_once"./$p";}static
function
checksum($p){$Td=str_replace("\r","",file_get_contents($p));$Td=preg_replace('~\n\tprotected \$translations = array\(.*?\n\t\);~s','',$Td);return
dechex(crc32($Td));}function
checksums(){$Vd=array_values($this->driverFiles);foreach($this->plugins
as$pj){$dk=new
\ReflectionObject($pj);$Vd[]=$dk->getFileName();}$H=array();foreach($Vd
as$p)$H[basename($p,'.php')]=self::checksum($p);return$H;}static
function
officialChecksums(){return
array('adminer.js'=>'a0599090','backward-keys'=>'5fb34a8b','before-unload'=>'2a613523','config'=>'722eb4af','dark-switcher'=>'3d490dea','database-hide'=>'e304a899','designs'=>'ed7e44e3','dump-alter'=>'896b579e','dump-bz2'=>'f0d0e336','dump-date'=>'adc7f1c7','dump-json'=>'767dd321','dump-xml'=>'4fc3cd60','dump-zip'=>'93817d96','edit-foreign'=>'eb823bb0','edit-textarea'=>'a24c3cc','editor-setup'=>'a7dc3a37','editor-views'=>'5c12b185','enum-option'=>'1e24970e','file-upload'=>'10add0e8','foreign-system'=>'ebb4c654','frames'=>'b0e1d11a','highlight-codemirror'=>'c5716555','highlight-monaco'=>'edd1b0af','highlight-prism'=>'267948e5','import-csv'=>'d429c77','login-ip'=>'4d174fea','login-otp'=>'5b5a68af','login-passkey'=>'f69f2f06','login-password-less'=>'e150daac','login-reverse-proxy'=>'24558ea2','login-servers'=>'19c42e45','login-ssl'=>'2b06a5b7','login-table'=>'811f8cef','menu-links'=>'c78461b3','name-patterns'=>'84c10d09','remote-color'=>'ddeecc48','row-numbers'=>'eec8698c','select-email'=>'f84fbd2c','select-expression'=>'4b61d59c','select-foreign'=>'38ac915c','select-image'=>'f55c0231','slugify'=>'dec64713','sql-gemini'=>'c60ab309','sql-log'=>'8e435000','sql-url-length'=>'cd70f1a0','table-indexes-structure'=>'a90cc0c9','table-structure'=>'a8458e02','tables-filter'=>'ec2bcd6e','timeout'=>'97321caf','version-github'=>'627cadf9','version-noverify'=>'966937e9','clickhouse'=>'92ca960d','elastic'=>'1582a04d','firebird'=>'1c4309b2','igdb'=>'a6a1750a','imap'=>'3da1022b','mongo'=>'63486492','redis'=>'6a653462','simpledb'=>'b8e2cc7d',);}function
__call($B,array$Ti){$Da=array();foreach($Ti
as$x=>$W)$Da[]=&$Ti[$x];$H=null;foreach($this->hooks[$B]as$pj){$X=call_user_func_array(array($pj,$B),$Da);if($X!==null){if(!self::$append[$B])return$X;$H=$X+(array)$H;}}return$H;}}abstract
class
Plugin{protected$translations=array();function
description(){return$this->lang('');}function
screenshot(){return"";}protected
function
lang($u,$Uh=null){$Da=func_get_args();$Da[0]=idx($this->translations[LANG],$u)?:$u;return
call_user_func_array('Adminer\lang_format',$Da);}}class
Password{private$password_hash;private$password_matches=null;function
__construct($gj){$this->password_hash=$gj;}function
description(){return'Require a password verified by Adminer';}function
credentials(){$D=get_password();return
array(SERVER,$_GET["username"],($this->passwordMatches($D)&&!password_required()?"":$D));}function
login($Eg,$D){if($this->passwordMatches($D))return
true;if(!Driver::$passwords||!password_required())return
false;}protected
function
passwordMatches($D){if($this->password_matches===null)$this->password_matches=(function_exists('password_verify')&&password_verify(strval($D),$this->password_hash));return$this->password_matches;}}Adminer::$instance=(function_exists('adminer_object')?adminer_object():(is_dir("adminer-plugins")||file_exists("adminer-plugins.php")?new
Plugins(null):new
Adminer));SqlDriver::$drivers=array("server"=>"MySQL / MariaDB")+SqlDriver::$drivers;if(!defined('Adminer\DRIVER')){define('Adminer\DRIVER',"server");if(extension_loaded("mysqli")&&$_GET["ext"]!="pdo"){class
Db
extends
\mysqli{static$instance;var$extension="MySQLi",$flavor='';function
__construct(){parent::init();}function
attach(array$M,$U,$D){mysqli_report(MYSQLI_REPORT_OFF);$tj=$M["port"];$ld=("$M[host]$tj$M[socket]"=="");$El=adminer()->connectSsl();$vn=($El&&($El['key']||$El['cert']||$El['ca']||isset($El['verify'])));if($vn)$this->ssl_set($El['key'],$El['cert'],$El['ca'],'','');$H=@$this->real_connect((!$ld?$M["host"]:ini_get("mysqli.default_host")),(!$ld||$U!=""?$U:ini_get("mysqli.default_user")),(!$ld||$U.$D!=""?$D:ini_get("mysqli.default_pw")),null,($tj!=""?intval($tj):ini_get("mysqli.default_port")),($tj!=""?null:$M["socket"]),($vn?($El['verify']!==false?MYSQLI_CLIENT_SSL:64):0));$this->options(MYSQLI_OPT_LOCAL_INFILE,0);return($H?'':$this->error);}function
set_charset($mb){if(parent::set_charset($mb))return
true;parent::set_charset('utf8');return$this->query("SET NAMES $mb");}function
next_result(){return
self::more_results()&&parent::next_result();}function
quote($P){return"'".$this->escape_string($P)."'";}function
inTransaction(){return
false;}function
begin(){return$this->begin_transaction();}}}elseif(extension_loaded("mysql")&&!((ini_bool("sql.safe_mode")||ini_bool("mysql.allow_local_infile"))&&extension_loaded("pdo_mysql"))){class
Db
extends
SqlDb{private$link;function
attach(array$M,$U,$D){if(ini_bool("mysql.allow_local_infile"))return
sprintf('Disable %s or enable the %s or %s extension.',"'mysql.allow_local_infile'","MySQLi","PDO_MySQL");$tj="$M[port]$M[socket]";$B=$M["host"].($tj!=""?":$tj":"");$this->link=@mysql_connect(($B!=""?$B:ini_get("mysql.default_host")),($B.$U!=""?$U:ini_get("mysql.default_user")),($B.$U.$D!=""?$D:ini_get("mysql.default_password")),true,131072);if(!$this->link)return
mysql_error();$this->server_info=mysql_get_server_info($this->link);return'';}function
set_charset($mb){return
mysql_set_charset($mb,$this->link)||mysql_set_charset('utf8',$this->link);}function
quote($P){return"'".mysql_real_escape_string($P,$this->link)."'";}function
select_db($pc){return
mysql_select_db($pc,$this->link);}function
query($F,$an=false){$G=@($an?mysql_unbuffered_query($F,$this->link):mysql_query($F,$this->link));$this->error="";if(!$G){$this->errno=mysql_errno($this->link);$this->error=mysql_error($this->link);return
false;}if($G===true){$this->affected_rows=mysql_affected_rows($this->link);$this->info=mysql_info($this->link);return
true;}return
new
Result($G);}}class
Result{var$num_rows;private$result;private$offset=0;function
__construct($G){$this->result=$G;$this->num_rows=mysql_num_rows($G);}function
fetch_assoc(){return
mysql_fetch_assoc($this->result);}function
fetch_row(){return
mysql_fetch_row($this->result);}function
fetch_field(){$H=mysql_fetch_field($this->result,$this->offset++);$H->orgtable=$H->table;$H->native_type=idx(array("string"=>"varchar","real"=>"double"),$H->type,$H->type);return$H;}}}elseif(extension_loaded("pdo_mysql")){class
Db
extends
PdoDb{var$extension="PDO_MySQL";function
attach(array$M,$U,$D){$vi=array(\PDO::MYSQL_ATTR_LOCAL_INFILE=>false);if(isset($_GET["select"]))$vi[\PDO::MYSQL_ATTR_MULTI_STATEMENTS]=false;$El=adminer()->connectSsl();if($El){if($El['key'])$vi[\PDO::MYSQL_ATTR_SSL_KEY]=$El['key'];if($El['cert'])$vi[\PDO::MYSQL_ATTR_SSL_CERT]=$El['cert'];if($El['ca'])$vi[\PDO::MYSQL_ATTR_SSL_CA]=$El['ca'];if(isset($El['verify']))$vi[\PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT]=$El['verify'];}$df=$M["host"];$tj=$M["port"];$rl=$M["socket"];return$this->dsn("mysql:charset=utf8".($df!=""?";host=$df":'').($tj!=""?";port=$tj":($rl!=""?";unix_socket=$rl":"")),$U,$D,$vi);}function
set_charset($mb){return$this->query("SET NAMES $mb");}function
select_db($pc){return$this->query("USE ".idf_escape($pc));}function
query($F,$an=false){$this->pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY,!$an);return
parent::query($F,$an);}}}class
Driver
extends
SqlDriver{static$extensions=array("MySQLi","MySQL","PDO_MySQL");static$jush="sql";static$serverSocket=true;var$unsigned=array("unsigned","zerofill","unsigned zerofill");var$functions=array("char_length","date","from_unixtime","lower","round","floor","ceil","sec_to_time","time_to_sec","upper");var$grouping=array("avg","count","count distinct","group_concat","max","min","sum");var$partitionBy=array("HASH","LINEAR HASH","KEY","LINEAR KEY","RANGE","LIST");function
operators($Vl){return
array("=","<",">","<=",">=","!=","&","LIKE","LIKE %%","REGEXP","IN","BETWEEN","FIND_IN_SET","IS NULL","NOT LIKE","NOT REGEXP","NOT IN","IS NOT NULL","SQL");}static
function
connect($M,$U,$D){$g=parent::connect($M,$U,$D);if(is_string($g)){if(function_exists('iconv')&&!is_utf8($g)&&strlen($Dk=iconv("windows-1252","utf-8//IGNORE",$g))>strlen($g))$g=$Dk;return$g;}$g->set_charset(charset($g));$g->query("SET sql_quote_show_create = 1, autocommit = 1");$g->flavor=(preg_match('~MariaDB~',$g->server_info)?'maria':'mysql');add_driver(DRIVER,($g->flavor=='maria'?"MariaDB":"MySQL"));return$g;}function
__construct(Db$g){parent::__construct($g);$this->types=array('Numbers'=>array("tinyint"=>3,"smallint"=>5,"mediumint"=>8,"int"=>10,"bigint"=>20,"decimal"=>66,"float"=>12,"double"=>21),'Date and time'=>array("date"=>10,"datetime"=>19,"timestamp"=>19,"time"=>10,"year"=>4),'Strings'=>array("char"=>255,"varchar"=>65535,"tinytext"=>255,"text"=>65535,"mediumtext"=>16777215,"longtext"=>4294967295),'Lists'=>array("enum"=>65535,"set"=>64),'Binary'=>array("bit"=>20,"binary"=>255,"varbinary"=>65535,"tinyblob"=>255,"blob"=>65535,"mediumblob"=>16777215,"longblob"=>4294967295),'Geometry'=>array("geometry"=>0,"point"=>0,"linestring"=>0,"polygon"=>0,"multipoint"=>0,"multilinestring"=>0,"multipolygon"=>0,"geometrycollection"=>0),);$this->insertFunctions=array("char"=>"md5/sha1/password/encrypt/uuid","binary"=>"md5/sha1","date|time"=>"now",);$this->editFunctions=array(number_type()=>"+/-","date"=>"+ interval/- interval","time"=>"addtime/subtime","char|text"=>"concat",);if(min_version('5.7.8',10.2,$g))$this->types['Strings']["json"]=4294967295;if(min_version('',10.7,$g)){$this->types['Strings']["uuid"]=128;$this->insertFunctions['uuid']='uuid';}if(min_version('',10.5,$g)){$this->types['Network']["inet6"]=39;if(min_version('','10.10',$g))$this->types['Network']["inet4"]=15;}if(min_version(9,11.7,$g))$this->types['Numbers']["vector"]=16383;if(min_version(5.7,10.2,$g))$this->generated=array("STORED","VIRTUAL");}function
unconvertFunction(array$n){return(preg_match("~binary~",$n["type"])?"<code class='jush-sql'>UNHEX</code>":($n["type"]=="bit"?doc_link(array('sql'=>'bit-value-literals.html'),"<code>b''</code>"):($n["type"]=="vector"?"<code class='jush-sql'>".($this->conn->flavor=='maria'?"VEC_FromText":"STRING_TO_VECTOR")."</code>":(preg_match("~geom|point|linestring|polygon~",$n["type"])?"<code class='jush-sql'>GeomFromText</code>":""))));}function
insert($Q,array$N){return($N?parent::insert($Q,$N):queries("INSERT INTO ".table($Q)." ()\nVALUES ()"));}function
insertUpdate($Q,array$J,array$Fj){$e=array_keys(reset($J));$Aj="INSERT INTO ".table($Q)." (".implode(", ",$e).") VALUES\n";$Y=array();foreach($e
as$x)$Y[$x]="$x = VALUES($x)";$Ol="\nON DUPLICATE KEY UPDATE ".implode(", ",$Y);$Y=array();$y=0;foreach($J
as$N){$X="(".implode(", ",$N).")";if($Y&&(strlen($Aj)+$y+strlen($X)+strlen($Ol)>1e6)){if(!queries($Aj.implode(",\n",$Y).$Ol))return
false;$Y=array();$y=0;}$Y[]=$X;$y+=strlen($X)+2;}return
queries($Aj.implode(",\n",$Y).$Ol);}function
slowQuery($F,$xm){if(min_version('5.7.8','10.1.2')){if($this->conn->flavor=='maria')return"SET STATEMENT max_statement_time=$xm FOR $F";elseif(preg_match('~^(SELECT\b)(.+)~is',$F,$A))return"$A[1] /*+ MAX_EXECUTION_TIME(".($xm*1000).") */ $A[2]";}}function
convertColumn($u,array$n){if(preg_match("~binary~",$n["type"]))return"HEX($u)";if($n["type"]=="bit")return"BIN($u + 0)";if($n["type"]=="vector")return($this->conn->flavor=='maria'?"VEC_ToText":"VECTOR_TO_STRING")."($u)";if(preg_match("~geom|point|linestring|polygon~",$n["type"]))return(min_version(8)?"ST_":"")."AsWKT($u)";return"";}function
convertSearch($u,array$W,array$n){return($this->convertColumn($u,$n)?:(preg_match('~'.text_type().'~',$n["type"])&&!preg_match("~^utf8~",$n["collation"])&&preg_match('~[\x80-\xFF]~',$W['val'])?"CONVERT($u USING ".charset($this->conn).")":$u));}function
typeName(\stdClass$n){$B=parent::typeName($n);if($B!=""){$Zm=array("TINY"=>"tinyint","SHORT"=>"smallint","LONG"=>"int","INT24"=>"mediumint","LONGLONG"=>"bigint","NEWDECIMAL"=>"decimal","VAR_STRING"=>"varchar","STRING"=>"char",);return
idx($Zm,$B,strtolower($B));}$Zm=array("decimal","tinyint","smallint","int","float","double",7=>"timestamp","bigint","mediumint","date","time","datetime","year",15=>"varchar","bit",242=>"vector",245=>"json","decimal","enum","set","tinytext","mediumtext","longtext","text","varchar","char","geometry",);$H=idx($Zm,$n->type,"");return($n->charsetnr==63?str_replace(array("text","varchar","char"),array("blob","varbinary","binary"),$H):$H);}function
quoteBinary($Dk){return"X".q(bin2hex($Dk));}function
md5($d,array$n){if(is_blob($n)||preg_match('~'.text_type().'~',$n["type"]))return"MD5(".(is_blob($n)||preg_match("~^utf8~",$n["collation"])?$d:"CONVERT($d USING ".charset($this->conn).")").")";}function
warnings(){$G=$this->conn->query("SHOW WARNINGS");if($G&&$G->num_rows){ob_start();print_select_result($G);return
ob_get_clean();}}function
tableHelp($B,$Vf=false){$Gg=($this->conn->flavor=='maria');if(information_schema(DB))return
strtolower(str_replace("_","-",DB)."-".($Gg?"$B-table/":str_replace("_","-",$B)."-table.html"));if(DB=="sys")return($Gg?"sys-schema/":strtolower("sys-".str_replace("_","-",preg_replace('~^x\$~','',$B)).".html"));if(DB=="mysql")return($Gg?"mysql$B-table/":"system-schema.html");}function
partitionsInfo($Q){$qe="FROM information_schema.PARTITIONS WHERE TABLE_SCHEMA = ".q(DB)." AND TABLE_NAME = ".q($Q);$G=$this->conn->query("SELECT PARTITION_METHOD, PARTITION_EXPRESSION, PARTITION_ORDINAL_POSITION $qe ORDER BY PARTITION_ORDINAL_POSITION DESC LIMIT 1");$I=($G?$G->fetch_row():null);if(!$I)return
array();$H=array();list($H["partition_by"],$H["partition"],$H["partitions"])=$I;$cj=get_key_vals("SELECT PARTITION_NAME, PARTITION_DESCRIPTION $qe AND PARTITION_NAME != '' ORDER BY PARTITION_ORDINAL_POSITION");$H["partition_names"]=array_keys($cj);$H["partition_values"]=array_values($cj);return$H;}function
checkConstraints($Q){$H=parent::checkConstraints($Q);return($this->conn->flavor=='maria'?$H:array_map('stripslashes',$H));}function
hasCStyleEscapes(){static$fb;if($fb===null){$Bl=get_val("SHOW VARIABLES LIKE 'sql_mode'",1,$this->conn);$fb=(strpos($Bl,'NO_BACKSLASH_ESCAPES')===false);}return$fb;}function
hasEstimatedRows(){return
true;}function
isSystem($k,$K=""){return
information_schema($k,$K)||in_array($k,array("mysql","sys"));}function
lineComment(){return"#|-- ";}function
engines(){$H=array();foreach(get_rows("SHOW ENGINES")as$I){if(preg_match("~YES|DEFAULT~",$I["Support"]))$H[]=$I["Engine"];}return$H;}function
indexAlgorithms(array$Vl){return(preg_match('~^(MEMORY|NDB)$~',$Vl["Engine"])?array("HASH","BTREE"):array());}}function
idf_escape($u){return"`".str_replace("`","``",$u)."`";}function
table($u){return
idf_escape($u);}function
get_databases($fe){$H=get_session("dbs");if($H===null){$F="SELECT SCHEMA_NAME FROM information_schema.SCHEMATA ORDER BY SCHEMA_NAME";$Fl=microtime(true);$H=($fe?slow_query($F):get_vals($F));if(microtime(true)-$Fl>0.1){restart_session();set_session("dbs",$H);stop_session();}}return$H;}function
limit($F,$Z,$z,$ci=0,$Uk=" "){return" $F$Z".($z?$Uk."LIMIT $z".($ci?" OFFSET $ci":""):"");}function
limit1($Q,$F,$Z,$Uk="\n"){return
limit($F,$Z,1,0,$Uk);}function
db_collation($k,array$Cb){$H=null;$i=get_val("SHOW CREATE DATABASE ".idf_escape($k),1);if(preg_match('~ COLLATE ([^ ]+)~',$i,$A))$H=$A[1];elseif(preg_match('~ CHARACTER SET ([^ ]+)~',$i,$A))$H=$Cb[$A[1]][-1];return$H;}function
logged_user(){return
get_val("SELECT CURRENT_USER()");}function
tables_list(){return
get_key_vals("SELECT TABLE_NAME, TABLE_TYPE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME");}function
count_tables(array$j){$H=array();foreach($j
as$k)$H[$k]=count(get_vals("SHOW TABLES IN ".idf_escape($k)));return$H;}function
table_status($B="",$Md=false){$H=array();$F="SELECT ENGINE AS Engine, TABLE_NAME AS Name, TABLE_COMMENT AS Comment FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() ".($B!=""?"AND TABLE_NAME = ".q($B):"ORDER BY Name");$K=array();foreach(($Md?array():get_rows($F))as$I)$K[$I["Name"]]=$I;$Ej=null;foreach(get_rows($Md?$F:"SHOW TABLE STATUS".($B!=""?" LIKE ".q(addcslashes($B,"%_\\")):""))as$I){$Ii=idx($K,$I["Name"]);if($Ii){if($I["Comment"]!==$Ii["Comment"]&&$I["Comment"]!==$Ej)$I["Error"]=$I["Comment"];$Ej=$I["Comment"];$I["Comment"]=$Ii["Comment"];$I["Engine"]=$Ii["Engine"];}if($I["Engine"]=="InnoDB")$I["Comment"]=preg_replace('~(?:(.+); )?InnoDB free: .*~','\1',$I["Comment"]);if(!isset($I["Engine"]))$I["Comment"]="";if($B!="")$I["Name"]=$B;$H[$I["Name"]]=$I;}return$H;}function
is_view(array$R){return$R["Engine"]===null;}function
fk_support(array$R){return
preg_match('~InnoDB|IBMDB2I'.(min_version(5.6)?'|NDB':'').'~i',$R["Engine"]);}function
parse_type($te){preg_match('~^([^( ]+)(?:\((.+)\))?( unsigned)?( zerofill)?$~',$te,$A);return
array($A[1],$A[2],ltrim($A[3].$A[4]));}function
fields($Q){$Gg=(connection()->flavor=='maria');$H=array();foreach(get_rows("SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ".q($Q)." ORDER BY ORDINAL_POSITION")as$I){$n=$I["COLUMN_NAME"];$T=$I["COLUMN_TYPE"];$ze=$I["GENERATION_EXPRESSION"];$Jd=$I["EXTRA"];preg_match('~^(VIRTUAL|PERSISTENT|STORED)~',$Jd,$ye);list($Ym,$y,$jn)=parse_type($T);$l=$I["COLUMN_DEFAULT"];if($l!=""){$Tf=preg_match('~text|json~',$Ym);if(!$Gg&&$Tf)$l=preg_replace("~^(_\w+)?('.*')$~",'\2',stripslashes($l));if($Gg||$Tf){$l=($l=="NULL"?null:preg_replace_callback("~^'(.*)'$~",function($A){return
stripslashes(str_replace("''","'",$A[1]));},$l));}if(!$Gg&&preg_match('~binary~',$Ym)&&preg_match('~^0x(\w*)$~',$l,$A))$l=pack("H*",$A[1]);}$H[$n]=array("field"=>$n,"full_type"=>$T,"type"=>$Ym,"length"=>$y,"unsigned"=>$jn,"default"=>($ye?($Gg?$ze:stripslashes($ze)):$l),"null"=>($I["IS_NULLABLE"]=="YES"),"auto_increment"=>($Jd=="auto_increment"),"on_update"=>(preg_match('~\bon update (\w+)~i',$Jd,$A)?$A[1]:""),"collation"=>$I["COLLATION_NAME"],"privileges"=>array_flip(explode(",","$I[PRIVILEGES],where,order")),"comment"=>$I["COLUMN_COMMENT"],"primary"=>($I["COLUMN_KEY"]=="PRI"),"generated"=>($ye[1]=="PERSISTENT"?"STORED":$ye[1]),);}return$H;}function
indexes($Q,$h=null){$H=array();foreach(get_rows("SHOW INDEX FROM ".table($Q),$h)as$I){$B=$I["Key_name"];$H[$B]["type"]=($B=="PRIMARY"?"PRIMARY":($I["Index_type"]=="FULLTEXT"?"FULLTEXT":($I["Non_unique"]?(preg_match('~^(SPATIAL|VECTOR)$~',$I["Index_type"])?$I["Index_type"]:"INDEX"):"UNIQUE")));$H[$B]["columns"][]=$I["Column_name"];$H[$B]["lengths"][]=($I["Index_type"]=="SPATIAL"?null:$I["Sub_part"]);$H[$B]["descs"][]=null;$H[$B]["algorithm"]=$I["Index_type"];}return$H;}function
foreign_keys($Q){static$kj='(?:`(?:[^`]|``)+`|"(?:[^"]|"")+")';$H=array();$dc=get_val("SHOW CREATE TABLE ".table($Q),1);if($dc){preg_match_all("~CONSTRAINT ($kj) FOREIGN KEY ?\\(((?:$kj,? ?)+)\\) REFERENCES ($kj)(?:\\.($kj))? \\(((?:$kj,? ?)+)\\)(?: ON DELETE (".driver()->onActions."))?(?: ON UPDATE (".driver()->onActions."))?~",$dc,$Jg,PREG_SET_ORDER);foreach($Jg
as$A){preg_match_all("~$kj~",$A[2],$vl);preg_match_all("~$kj~",$A[5],$nm);$H[idf_unescape($A[1])]=array("db"=>idf_unescape($A[4]!=""?$A[3]:$A[4]),"table"=>idf_unescape($A[4]!=""?$A[4]:$A[3]),"source"=>array_map('Adminer\idf_unescape',$vl[0]),"target"=>array_map('Adminer\idf_unescape',$nm[0]),"on_delete"=>($A[6]?:"RESTRICT"),"on_update"=>($A[7]?:"RESTRICT"),);}}return$H;}function
view($B){return
array("select"=>preg_replace('~^(?:[^`]|`[^`]*`)*\s+AS\s+~isU','',get_val("SHOW CREATE VIEW ".table($B),1)));}function
collations(){$H=array();foreach(get_rows("SHOW COLLATION")as$I){if($I["Default"])$H[$I["Charset"]][-1]=$I["Collation"];else$H[$I["Charset"]][]=$I["Collation"];}ksort($H);foreach($H
as$x=>$W)sort($H[$x]);return$H;}function
information_schema($k,$K=""){return($k=="information_schema")||(min_version(5.5)&&$k=="performance_schema");}function
error(){return
h(preg_replace('~^You have an error.*syntax to use~U',"Syntax error",connection()->error));}function
create_database($k,$Bb){return
queries("CREATE DATABASE ".idf_escape($k).($Bb?" COLLATE ".q($Bb):""));}function
drop_databases(array$j){$H=apply_queries("DROP DATABASE",$j,'Adminer\idf_escape');restart_session();set_session("dbs",null);return$H;}function
rename_database($B,$Bb){$H=false;if(create_database($B,$Bb)){$S=array();$In=array();foreach(tables_list()as$Q=>$T){if($T=='VIEW')$In[]=$Q;else$S[]=$Q;}$H=(!$S&&!$In)||move_tables($S,$In,$B);drop_databases($H?array(DB):array());}return$H;}function
auto_increment(){$Ma=" PRIMARY KEY";if($_GET["create"]!=""&&$_POST["auto_increment_col"]){foreach(indexes($_GET["create"])as$v){if(in_array($_POST["fields"][$_POST["auto_increment_col"]]["orig"],$v["columns"],true)){$Ma="";break;}if($v["type"]=="PRIMARY")$Ma=" UNIQUE";}}return" AUTO_INCREMENT$Ma";}function
alter_table($Q,$B,array$o,array$he,$f,$nd,$Bb,$La,$bj){$b=array();foreach($o
as$n){if($n[1]){$l=$n[1][3];if(preg_match('~ GENERATED~',$l)){$n[1][3]=(connection()->flavor=='maria'?"":$n[1][2]);$n[1][2]=$l;}$b[]=($Q!=""?($n[0]!=""?"CHANGE ".idf_escape($n[0]):"ADD"):" ")." ".implode($n[1]).($Q!=""?$n[2]:"");}else$b[]="DROP ".idf_escape($n[0]);}$b=array_merge($b,$he);$O=($f!==null?" COMMENT=".q($f):"").($nd?" ENGINE=".q($nd):"").($Bb?" COLLATE ".q($Bb):"").($La!=""?" AUTO_INCREMENT=$La":"");if($bj){$cj=array();if($bj["partition_by"]=='RANGE'||$bj["partition_by"]=='LIST'){foreach($bj["partition_names"]as$x=>$W){$X=$bj["partition_values"][$x];$cj[]="\n  PARTITION ".idf_escape($W)." VALUES ".($bj["partition_by"]=='RANGE'?"LESS THAN":"IN").($X!=""?" ($X)":" MAXVALUE");}}$O
.="\nPARTITION BY $bj[partition_by]($bj[partition])";if($cj)$O
.=" (".implode(",",$cj)."\n)";elseif($bj["partitions"])$O
.=" PARTITIONS ".(+$bj["partitions"]);}elseif($bj===null)$O
.="\nREMOVE PARTITIONING";if($Q=="")return
queries("CREATE TABLE ".table($B)." (\n".implode(",\n",$b)."\n)$O");if($Q!=$B)$b[]="RENAME TO ".table($B);if($O)$b[]=ltrim($O);return($b?queries("ALTER TABLE ".table($Q)."\n".implode(",\n",$b)):true);}function
alter_indexes($Q,$b){$kb=array();foreach($b
as$W)$kb[]=($W[2]=="DROP"?"\nDROP INDEX ".idf_escape($W[1]):"\nADD $W[0] ".($W[0]=="PRIMARY"?"KEY ":"").($W[1]!=""?idf_escape($W[1])." ":"")."(".implode(", ",$W[2]).")");return
queries("ALTER TABLE ".table($Q).implode(",",$kb));}function
truncate_tables(array$S){return
apply_queries("TRUNCATE TABLE",$S);}function
drop_views(array$In){return
queries("DROP VIEW ".implode(", ",array_map('Adminer\table',$In)));}function
drop_tables(array$S){return
queries("DROP TABLE ".implode(", ",array_map('Adminer\table',$S)));}function
move_tables(array$S,array$In,$nm){$jk=array();foreach($S
as$Q)$jk[]=table($Q)." TO ".idf_escape($nm).".".table($Q);if(!$jk||queries("RENAME TABLE ".implode(", ",$jk))){$_c=array();foreach($In
as$Q)$_c[table($Q)]=view($Q);connection()->select_db($nm);$k=idf_escape(DB);foreach($_c
as$B=>$Hn){if(!queries("CREATE VIEW $B AS ".str_replace(" $k."," ",$Hn["select"]))||!queries("DROP VIEW $k.$B"))return
false;}return
true;}return
false;}function
copy_tables(array$S,array$In,$nm){queries("SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO'");foreach($S
as$Q){$B=($nm==DB?table("copy_$Q"):idf_escape($nm).".".table($Q));if(($_POST["overwrite"]&&!queries("\nDROP TABLE IF EXISTS $B"))||!queries("CREATE TABLE $B LIKE ".table($Q))||!queries("INSERT INTO $B SELECT * FROM ".table($Q)))return
false;foreach(get_rows("SHOW TRIGGERS LIKE ".q(addcslashes($Q,"%_\\")))as$I){$Om=$I["Trigger"];list($wd,$Xh)=trigger_event($I);if(!queries("CREATE TRIGGER ".($nm==DB?idf_escape("copy_$Om"):idf_escape($nm).".".idf_escape($Om))." $I[Timing] $wd".($Xh!=""?" $Xh":"")." ON $B FOR EACH ROW\n$I[Statement];"))return
false;}}foreach($In
as$Q){$B=($nm==DB?table("copy_$Q"):idf_escape($nm).".".table($Q));$Hn=view($Q);if(($_POST["overwrite"]&&!queries("DROP VIEW IF EXISTS $B"))||!queries("CREATE VIEW $B AS $Hn[select]"))return
false;}return
true;}function
trigger_event(array$I){$yd=explode(",",$I["Event"]);$H=array();foreach(array("DELETE","INSERT","UPDATE")as$wd){if(in_array($wd,$yd))$H[]=$wd;}$H=implode(" OR ",$H);if(in_array("UPDATE",$yd)&&min_version('','12.0.1')&&preg_match('~\s(?:BEFORE|AFTER)\s+(.+?)\s+ON\s~is',get_val("SHOW CREATE TRIGGER ".idf_escape($I["Trigger"]),2),$A)&&preg_match('~\bOF\s+(.+)~is',$A[1],$Xh))return
array("$H OF",$Xh[1]);return
array($H,"");}function
trigger($B,$Q){if($B=="")return
array();$J=get_rows("SHOW TRIGGERS WHERE `Trigger` = ".q($B));$H=reset($J);if($H)list($H["Event"],$H["Of"])=trigger_event($H);return($H?:array());}function
triggers($Q){$H=array();foreach(get_rows("SHOW TRIGGERS LIKE ".q(addcslashes($Q,"%_\\")))as$I){list($wd)=trigger_event($I);$H[$I["Trigger"]]=array($I["Timing"],$wd);}return$H;}function
trigger_options(){return
array("Timing"=>array("BEFORE","AFTER"),"Event"=>(min_version('','12.0.1')?array("INSERT","UPDATE","UPDATE OF","DELETE","INSERT OR UPDATE","INSERT OR UPDATE OF","DELETE OR INSERT","DELETE OR UPDATE","DELETE OR UPDATE OF","DELETE OR INSERT OR UPDATE","DELETE OR INSERT OR UPDATE OF",):array("INSERT","UPDATE","DELETE")),"Type"=>array("FOR EACH ROW"),);}function
routine($B,$T){$J=get_rows("SELECT PARAMETER_NAME, DTD_IDENTIFIER, PARAMETER_MODE, COLLATION_NAME
FROM information_schema.PARAMETERS
WHERE SPECIFIC_SCHEMA = DATABASE() AND ROUTINE_TYPE = '$T' AND SPECIFIC_NAME = ".q($B)."
ORDER BY ORDINAL_POSITION");$o=array();foreach($J
as$I){$te=$I["DTD_IDENTIFIER"];list($Ym,$y,$jn)=parse_type($te);$o[]=array("field"=>$I["PARAMETER_NAME"],"type"=>$Ym,"length"=>$y,"unsigned"=>$jn,"null"=>true,"full_type"=>$te,"inout"=>($T=="FUNCTION"?"":$I["PARAMETER_MODE"]),"collation"=>$I["COLLATION_NAME"],);}$H=connection()->query("SELECT
	ROUTINE_COMMENT comment,
	ROUTINE_DEFINITION definition,
	LOWER(EXTERNAL_LANGUAGE) language,
	IF(DEFINER = CURRENT_USER(), '', DEFINER) definer,
	IF(IS_DETERMINISTIC = 'YES', 'DETERMINISTIC', 'NOT DETERMINISTIC') is_deterministic,
	SQL_DATA_ACCESS data_access,
	CONCAT('SQL SECURITY ', SECURITY_TYPE) security
FROM information_schema.ROUTINES
WHERE ROUTINE_SCHEMA = DATABASE() AND ROUTINE_TYPE = '$T' AND ROUTINE_NAME = ".q($B))->fetch_assoc();if(!$H)return
array();$H['options']=array("DEFINER"=>$H['definer'],"DETERMINISTIC"=>$H['is_deterministic'],"SQL_DATA_ACCESS"=>$H['data_access'],"SQL_SECURITY"=>$H['security'],"COMMENT"=>$H['comment'],);if($o&&$o[0]['field']=='')$H['returns']=array_shift($o);$H['fields']=$o;return$H;}function
routines(){return
get_rows("SELECT SPECIFIC_NAME, ROUTINE_NAME, ROUTINE_TYPE, DTD_IDENTIFIER FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA = DATABASE()");}function
routine_languages(){return(min_version(9,99)?array("sql"=>"sql","javascript"=>"js"):array());}function
routine_options($vk){return
array("DEFINER"=>array(),"DETERMINISTIC"=>array("NOT DETERMINISTIC","DETERMINISTIC"),"SQL_DATA_ACCESS"=>array("CONTAINS SQL","NO SQL","READS SQL DATA","MODIFIES SQL DATA"),"SQL_SECURITY"=>array("SQL SECURITY DEFINER","SQL SECURITY INVOKER"),"COMMENT"=>array(),);}function
routine_id($B,array$I){return
idf_escape($B);}function
last_id($G){return
get_val("SELECT LAST_INSERT_ID()");}function
explain(Db$g,$F){return$g->query("EXPLAIN ".(min_version(5.7)?"":"PARTITIONS ").$F);}function
found_rows(array$R,array$Z){return($Z||$R["Engine"]!="InnoDB"?null:$R["Rows"]);}function
create_sql($Q,$La,$Ml){$H=get_val("SHOW CREATE TABLE ".table($Q),1);if(!$La)$H=preg_replace('~(\n\)[^\n]*?) AUTO_INCREMENT=\d+~','\1',$H);return$H;}function
truncate_sql($Q){return"TRUNCATE ".table($Q);}function
use_sql($pc,$Ml=""){$B=idf_escape($pc);$H="";if(preg_match('~CREATE~',$Ml)&&($i=get_val("SHOW CREATE DATABASE $B",1))){set_utf8mb4($i);if($Ml=="DROP+CREATE")$H="DROP DATABASE IF EXISTS $B;\n";$H
.="$i;\n";}return$H."USE $B";}function
trigger_sql($Q){$H="";foreach(get_rows("SHOW TRIGGERS LIKE ".q(addcslashes($Q,"%_\\")),null,"-- ")as$I){list($I["Event"],$I["Of"])=trigger_event($I);$H
.="\n".create_trigger(" ON ".table($I["Table"]),$I+array("Type"=>"FOR EACH ROW")).";\n";}return$H;}function
show_variables(){return
get_rows("SHOW VARIABLES");}function
show_status(){return
get_rows("SHOW STATUS");}function
process_list(){return
get_rows("SHOW FULL PROCESSLIST");}function
convert_field(array$n){return
driver()->convertColumn(idf_escape($n["field"]),$n);}function
unconvert_field(array$n,$H){if(preg_match("~binary~",$n["type"]))$H="UNHEX($H)";if($n["type"]=="bit")$H="CONVERT(b$H, UNSIGNED)";if($n["type"]=="vector")$H=(connection()->flavor=='maria'?"VEC_FromText":"STRING_TO_VECTOR")."($H)";if(preg_match("~geom|point|linestring|polygon~",$n["type"])){$Aj=(min_version(8)?"ST_":"");$H=$Aj."GeomFromText($H, $Aj"."SRID($n[field]))";}return$H;}function
support($Nd){return
preg_match('~^(comment|columns|copy|database|drop_col|dump|event|indexes|kill|privileges|move_col|procedure|processlist|routine|sql|status|table|trigger|variables|view'.(min_version(8)?'|descidx':'').(min_version('8.0.16','10.2.1')?'|check':'').(min_version(8,99)?'|fast_status':'').')$~',$Nd);}function
kill_process($t){return
queries("KILL ".number($t));}function
connection_id(){return"SELECT CONNECTION_ID()";}function
max_connections(){return
get_val("SELECT @@max_connections");}function
types($Id=false){return
array();}function
type_values($t){return"";}function
type_definition($t){return
array("kind"=>"","definition"=>"");}function
schemas(){return
array();}function
get_schema(){return"";}function
set_schema($K,$h=null){return
true;}function
schema_comment($K){return"";}function
set_schema_comment($K,$f){return
true;}}define('Adminer\JUSH',Driver::$jush);define('Adminer\SERVER',"".$_GET[DRIVER]);define('Adminer\DB',"$_GET[db]");define('Adminer\ME',preg_replace('~\?.*~','',relative_uri()).'?'.(sid()?SID.'&':'').($_GET["ext"]?"ext=".url_escape($_GET["ext"]).'&':'').(isset($_GET[DRIVER])?DRIVER."=".url_escape(SERVER).'&':'').(isset($_GET["username"])?"username=".url_escape($_GET["username"]).'&':'').(isset($_GET["db"])?'db='.url_escape(DB).'&'.(isset($_GET["ns"])?"ns=".url_escape($_GET["ns"])."&":""):''));if(isset($_GET["manifest"])){header("Content-Type: application/manifest+json; charset=utf-8");header("Cache-Control: no-cache");echo
json_encode(adminer()->manifest(),64|256);exit;}function
page_header($zm,$m="",$bb=array(),$_m="",$Qh=false,$Qc=""){if($Qh){header("HTTP/1.1 404 Not Found");$m=($m?:'Not found.');}page_headers();if(is_ajax()&&$m){page_messages($m);exit;}if(!ob_get_level())ob_start('ob_gzhandler',4096);$Am=$zm.($_m!=""?": $_m":"");$Bm=strip_tags($Am.(SERVER!=""&&SERVER!="localhost"?h(" - ".SERVER):"")." - ".adminer()->name());echo'<!DOCTYPE html>
<html lang=\'en\' dir=\'ltr\' class=\'ltr nojs\'>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="robots" content="noindex">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>',$Bm,'</title>
<link rel="stylesheet" href="',h(preg_replace("~\\?.*~","",ME)."?file=default.css&version=6.1.2+30b83850"),'">
';$hc=adminer()->css();if(is_int(key($hc)))$hc=array_fill_keys($hc,'light');$Ne=in_array('light',$hc)||in_array('',$hc);$Le=in_array('dark',$hc)||in_array('',$hc);$lc=($Ne?($Le?null:false):($Le?:null));$Yg=" media='(prefers-color-scheme: dark)'";if($lc!==false)echo"<link rel='stylesheet'".($lc?"":$Yg)." href='".h(preg_replace("~\\?.*~","",ME)."?file=dark.css&version=6.1.2+30b83850")."'>\n";echo"<meta name='color-scheme' content='".($lc===null?"light dark":($lc?"dark":"light"))."'>\n",script_src(preg_replace("~\\?.*~","",ME)."?file=functions.js&version=6.1.2+30b83850");if(adminer()->head($lc))echo"<link rel='icon' href='data:image/gif;base64,"."R0lGODlhEAAQAJEAAAQCBPz+/PwCBAROZCH5BAEAAAAALAAAAAAQABAAAAI2hI+pGO1rmghihiUdvUBnZ3XBQA7f05mOak1RWXrNq5nQWHMKvuoJ37BhVEEfYxQzHjWQ5qIAADs='>\n","<link rel='apple-touch-icon' href='".h(preg_replace("~\\?.*~","",ME)."?file=logo.svg&version=6.1.2+30b83850")."'>\n";if(adminer()->manifest())echo"<link rel='manifest' href='".h(preg_replace('~\?.*~','',ME)."?manifest=")."' crossorigin='use-credentials'>\n";foreach($hc
as$qn=>$nh){$c=($nh=='dark'&&!$lc?$Yg:($nh=='light'&&$Le?" media='(prefers-color-scheme: light)'":""));echo"<link rel='stylesheet'$c href='".h($qn)."'>\n";}echo"\n<body class='";adminer()->bodyClass();echo"'>\n",script((isset($_COOKIE["adminer_version"])||!adminer()->verifyVersion()?"":"onload = partial(verifyVersion, '".VERSION."');\n")."
const offlineMessage = '".js_escape('You are offline.')."';
const numberFormat = '".js_escape('#,##0')."';
const numberDigits = '".js_escape('0123456789')."';
const urlSeparators = '".js_escape(ini_get("arg_separator.input"))."';"),"<div id='help' class='jush-".JUSH." jsonly hidden'".on('mouseover','helpKeep').on('mouseout','helpMouseout')."></div>\n","<div id='content'>\n","<span id='menuopen' class='jsonly'".on('click','menuToggle')."><button title='".'Menu'."' class='icon icon-move' aria-expanded='false'></button></span>\n";if($bb!==null){$_=substr(preg_replace('~\b(username|db|ns)=[^&]*&~','',ME),0,-1);echo'<p id="breadcrumb"><a href="'.h($_?:".").'">'.get_driver(DRIVER).'</a> » ';$_=substr(preg_replace('~\b(db|ns)=[^&]*&~','',ME),0,-1);$M=adminer()->serverName(SERVER);$M=($M!=""?$M:'Server');if($bb===false)echo"$M\n";else{echo"<a href='".h($_.(DB!=""&&support("single_db")?"&db=":""))."' accesskey='1' title='Alt+Shift+1'>$M</a> » ";$Lk="";if(is_string($bb)){$Lk=$bb;$bb=array();}if($_GET["ns"]!=""||(DB!=""&&is_array($bb))){$rc="$_&db=".url_escape(DB).(support("scheme")?"&ns=":"").(support("single_table")?"&select=":"");echo'<a href="'.h($rc.($_GET["ns"]==""?$Lk:"")).'">'.h(DB).'</a> » ';}if(is_array($bb)){if($_GET["ns"]!="")echo'<a href="'.h(substr(ME,0,-1).$Lk).'">'.h($_GET["ns"]).'</a> » ';foreach($bb
as$x=>$W){$Bc=(is_array($W)?$W[1]:h($W));if($Bc!="")echo"<a href='".h(ME."$x=").url_escape(is_array($W)?$W[0]:$W)."'>$Bc</a> » ";}}echo"$zm\n";}}echo"<h2>$Am$Qc</h2>\n","<div id='ajaxstatus' role='status' class='jsonly'></div>\n";restart_session();page_messages($m);adminer()->serviceWorker();$j=&get_session("dbs");if(DB!=""&&$j&&!in_array(DB,$j,true))$j=null;stop_session();define('Adminer\PAGE_HEADER',1);ob_flush();flush();if($Qh){page_footer($Qh===true?"":$Qh);exit;}}function
service_worker(){$gk=has_passwords();$zb=($gk?"navigator.serviceWorker.register('".js_escape(preg_replace('~\?.*~','',ME)."?file=worker.js&version=6.1.2+30b83850")."', {scope: location.pathname}).catch(() => {});":"navigator.serviceWorker.getRegistration().then(registration => registration && registration.unregister());
	caches.keys().then(keys => keys.forEach(key => key.startsWith('adminer-') && caches.delete(key)));");echo
script("if (navigator.serviceWorker) {\n\t$zb\n}");}function
has_passwords(){foreach((array)$_SESSION["pwds"]as$hl){foreach($hl
as$zn){foreach($zn
as$D){if($D!==null)return
true;}}}return
false;}function
page_headers(){header("Content-Type: text/html; charset=utf-8");header("Cache-Control: no-cache");header("X-Frame-Options: deny");header("X-XSS-Protection: 0");header("X-Content-Type-Options: nosniff");header("Referrer-Policy: origin-when-cross-origin");foreach(adminer()->csp(csp())as$gc){$Se=array();foreach($gc
as$x=>$W)$Se[]="$x $W";header("Content-Security-Policy: ".implode("; ",$Se));}adminer()->headers();}function
csp(){return
array(array("script-src"=>"'self' 'unsafe-inline' 'nonce-".get_nonce()."' 'strict-dynamic'","connect-src"=>"'self' https://www.adminer.org","frame-src"=>"https://www.adminer.org","object-src"=>"'none'","base-uri"=>"'none'","form-action"=>"'self'",),);}function
design_checksums(){$wn=array();foreach(array_keys(adminer()->css())as$qn)$wn[preg_replace('~\?.*~','',$qn)]=true;$H=array();foreach(array("adminer.css","adminer-dark.css")as$p){if($wn[$p]&&file_exists($p)){preg_match('~^/\* Adminer design ([-\w]+) \*/~',file_get_contents($p),$A);$H[$p]=array((string)$A[1],Plugins::checksum($p));}}return$H;}function
official_design_checksums(){return
array('adminer-border/adminer.css'=>'ec757f3e','adminer-dark/adminer-dark.css'=>'a26bcd7b','brade/adminer.css'=>'be4161f0','bueltge/adminer.css'=>'1a8f00b4','cpanel/adminer.css'=>'59ce604e','dracula/adminer-dark.css'=>'cfaf61dd','esterka/adminer.css'=>'1f805f36','flat/adminer.css'=>'49a61af9','galkaev/adminer-dark.css'=>'16c46f94','haeckel/adminer.css'=>'147a3565','hever/adminer.css'=>'ef0e1948','konya/adminer.css'=>'2b409696','lavender-light/adminer.css'=>'bf03f5d7','lucas-sandery/adminer.css'=>'6596353','mancave/adminer-dark.css'=>'d9169f12','mvt/adminer.css'=>'ebd3afdc','nette/adminer.css'=>'1b8ac749','ng9/adminer.css'=>'488583cf','nicu/adminer.css'=>'a38b3b6a','pappu687/adminer.css'=>'b58d128c','paranoiq/adminer.css'=>'64d27e5','pepa-linha/adminer.css'=>'baf25f0','pokorny/adminer.css'=>'ee9eea6d','price/adminer.css'=>'81be9a85','rmsoft/adminer.css'=>'6cd4a237','rmsoft_blue-dark/adminer.css'=>'32102a8','rmsoft_blue/adminer.css'=>'7d8d5b18','win98/adminer.css'=>'e82d63c3',);}function
version_iframe(){return(isset($_COOKIE["adminer_version"])||!adminer()->verifyVersion()?"":"<noscript><iframe sandbox src='https://www.adminer.org/version/?current=".VERSION."&amp;noscript=1'></iframe></noscript>");}function
get_nonce(){static$Ph;if(!$Ph)$Ph=base64_encode(rand_string());return$Ph;}function
page_messages($m){$pn=preg_replace('~^[^?]*~','',$_SERVER["REQUEST_URI"]);$fh=idx($_SESSION["messages"],$pn);if($fh){echo"<div class='message'>".implode("</div>\n<div class='message'>",$fh)."</div>".script("messagesPrint();");unset($_SESSION["messages"][$pn]);}if($m)echo"<div class='error'>$m</div>\n";if(adminer()->error)echo"<div class='error'>".adminer()->error."</div>\n";}function
page_footer($mh=""){echo"</div>\n\n<div id='foot' class='foot'>\n<div id='menu'>\n";adminer()->navigation($mh);echo"</div>\n";if($mh!="auth")echo'<form action="" method="post">
<p class="logout">
<span title="Username">',h($_GET["username"])."\n",'</span>
<input type=\'submit\' name=\'logout\' value=\'Logout\' id=\'logout\'>
',input_token(),'</form>
';echo"</div>\n\n",script("setupSubmitHighlight(document);");}function
int32($wh){while($wh>=2147483648)$wh-=4294967296;while($wh<=-2147483649)$wh+=4294967296;return(int)$wh;}function
long2str(array$V,$Kn){$Dk='';foreach($V
as$W)$Dk
.=pack('V',$W);if($Kn)return
substr($Dk,0,end($V));return$Dk;}function
str2long($Dk,$Kn){$V=array_values(unpack('V*',str_pad($Dk,4*ceil(strlen($Dk)/4),"\0")));if($Kn)$V[]=strlen($Dk);return$V;}function
xxtea_mx($Vn,$Un,$Pl,$ag){return
int32((($Vn>>5&0x7FFFFFF)^$Un<<2)+(($Un>>3&0x1FFFFFFF)^$Vn<<4))^int32(($Pl^$Un)+($ag^$Vn));}function
encrypt_string($Jl,$x){if($Jl=="")return"";$x=array_values(unpack("V*",pack("H*",md5($x))));$V=str2long($Jl,true);$wh=count($V)-1;$Vn=$V[$wh];$Un=$V[0];$Qj=floor(6+52/($wh+1));$Pl=0;while($Qj-->0){$Pl=int32($Pl+0x9E3779B9);$bd=$Pl>>2&3;for($Oi=0;$Oi<$wh;$Oi++){$Un=$V[$Oi+1];$vh=xxtea_mx($Vn,$Un,$Pl,$x[$Oi&3^$bd]);$Vn=int32($V[$Oi]+$vh);$V[$Oi]=$Vn;}$Un=$V[0];$vh=xxtea_mx($Vn,$Un,$Pl,$x[$Oi&3^$bd]);$Vn=int32($V[$wh]+$vh);$V[$wh]=$Vn;}return
long2str($V,false);}function
decrypt_string($Jl,$x){if($Jl=="")return"";if(!$x)return
false;$x=array_values(unpack("V*",pack("H*",md5($x))));$V=str2long($Jl,false);$wh=count($V)-1;$Vn=$V[$wh];$Un=$V[0];$Qj=floor(6+52/($wh+1));$Pl=int32($Qj*0x9E3779B9);while($Pl){$bd=$Pl>>2&3;for($Oi=$wh;$Oi>0;$Oi--){$Vn=$V[$Oi-1];$vh=xxtea_mx($Vn,$Un,$Pl,$x[$Oi&3^$bd]);$Un=int32($V[$Oi]-$vh);$V[$Oi]=$Un;}$Vn=$V[$wh];$vh=xxtea_mx($Vn,$Un,$Pl,$x[$Oi&3^$bd]);$Un=int32($V[0]-$vh);$V[0]=$Un;$Pl=int32($Pl-0x9E3779B9);}return
long2str($V,true);}$nj=array();if($_COOKIE["adminer_permanent"]){foreach(explode(" ",$_COOKIE["adminer_permanent"])as$W){list($x)=explode(":",$W);$nj[$x]=$W;}}function
add_invalid_login(){$Ta=get_temp_dir()."/adminer-invalid";foreach(glob("$Ta*")?:array($Ta)as$p){$ne=file_open_lock($p);if($ne)break;}if(!$ne)$ne=file_open_lock("$Ta-".rand_string());if(!$ne)return;$Mf=json_decode(stream_get_contents($ne),true);$wm=time();if($Mf){foreach($Mf
as$Nf=>$W){if($W[0]<$wm)unset($Mf[$Nf]);}}$Kf=&$Mf[adminer()->bruteForceKey()];if(!$Kf)$Kf=array($wm+30*60,0);$Kf[1]++;file_write_unlock($ne,json_encode($Mf));}function
check_invalid_login(array&$nj){$Mf=array();foreach(glob(get_temp_dir()."/adminer-invalid*")as$p){$ne=file_open_lock($p);if($ne){$Mf=json_decode(stream_get_contents($ne),true);file_unlock($ne);break;}}$x=adminer()->bruteForceKey();$Kf=idx($Mf,$x,array());$Oh=($Kf[1]>29?$Kf[0]-time():0);if($Oh>0){$m=lang_format(array('Too many unsuccessful logins, try again in %d minute.','Too many unsuccessful logins, try again in %d minutes.'),ceil($Oh/60));if($_SERVER["HTTP_X_FORWARDED_FOR"]!=""&&$x==$_SERVER["REMOTE_ADDR"])$m
.='<br>'.sprintf('Use the %s <a%s>plugin</a> if Adminer runs behind a reverse proxy.','<b>login-reverse-proxy</b>'," href='https://www.adminer.org/plugins/?version=".VERSION."'".target_blank());auth_error($m,$nj,false);}}function
password_required(){static$H;if($H===null){$H=(bool)get_session("password_required");if(!$H){$fc=adminer()->credentials();$H=!is_object(Driver::connect($fc[0],$fc[1],""));if($H)set_session("password_required",true);}}return$H;}function
require_password_link($D){$rh="<a href='https://www.adminer.org/password/'".target_blank().">".'More options'."</a>";if(!function_exists('password_hash'))return" $rh";$qj=($D!==null?$D:base64_encode(substr(pack("H*",rand_string()),0,12)));$Re=password_hash($qj,PASSWORD_DEFAULT);$p="adminer-plugins.php";$Cd=file_exists("adminer-plugins.php");if($Cd)$If=($D!==null?sprintf('Add this line to %s to require the entered password:',"<b>$p</b>"):sprintf('Add this line to %s to require the password %s:',"<b>$p</b>","<b>$qj</b>"));else{$p="<button name='password_less' value='".h($Re)."' class='link'>$p</button>";$If=($D!==null?sprintf('Save %s next to Adminer to require the entered password:',$p):sprintf('Save %s next to Adminer to require the password %s:',$p,"<b>$qj</b>"));}$yg="\t<a>new</a> Adminer\\Password(<span class='jush-apo'>'".h($Re)."'</span>),";$H="<p>$If
<pre><code class='jush'>".($Cd?$yg:"&lt;?php\n<a>return</a> <a>array</a>(\n$yg\n);")."</code></pre>
<p>$rh
";return" <a href='#password-less' class='toggle'>".'Require a password.'."</a>
<div id='password-less' class='hidden'>".($Cd?$H:"<form action='' method='post'>\n".$H.input_token()."</form>")."</div>";}if(preg_match('~^[-\w$./]+$~',$_POST["password_less"])&&verify_token()){header("Content-Type: application/octet-stream");header("Content-Disposition: attachment; filename=adminer-plugins.php");echo"<?php\nreturn array(\n\tnew Adminer\\Password('$_POST[password_less]'),\n);\n";exit;}$Ka=$_POST["auth"];if($Ka&&(!adminer()->verifyLoginToken()||verify_token())){session_regenerate_id();$Fn=$Ka["driver"];$M=$Ka["server"];$U=$Ka["username"];$D=(string)$Ka["password"];$k=$Ka["db"];set_password($Fn,$M,$U,$D);$_SESSION["db"][$Fn][$M][$U][$k]=true;if($Ka["permanent"]){$x=implode("-",array_map('base64_encode',array($Fn,$M,$U,$k)));$Ij=adminer()->permanentLogin(true);$nj[$x]="$x:".base64_encode($Ij?encrypt_string($D,$Ij):"");cookie("adminer_permanent",implode(" ",$nj));}if(!array_diff(array_keys($_POST),array("auth","token"))||$Fn!=DRIVER||$M!=SERVER||$U!==$_GET["username"]||$k!=DB)redirect(auth_url($Fn,$M,$U,$k));}elseif($_POST["logout"]&&(!$_SESSION["token"]||verify_token())){Driver::disconnect();foreach(array("pwds","db","dbs","queries")as$x)set_session($x,null);unset_permanent($nj);redirect(substr(preg_replace('~\b(username|db|ns)=[^&]*&~','',ME),0,-1),'Logout successful.'.' '.'Thanks for using Adminer. Consider <a href="https://www.adminer.org/en/donation/">donating</a>.');}elseif($nj&&!$_SESSION["pwds"]){session_regenerate_id();$Ij=adminer()->permanentLogin();foreach($nj
as$x=>$W){list(,$wb)=explode(":",$W);list($Fn,$M,$U,$k)=array_map('base64_decode',explode("-",$x));set_password($Fn,$M,$U,decrypt_string(base64_decode($wb),$Ij));$_SESSION["db"][$Fn][$M][$U][$k]=true;}}function
unset_permanent(array&$nj){foreach($nj
as$x=>$W){list($Fn,$M,$U,$k)=array_map('base64_decode',explode("-",$x));if($Fn==DRIVER&&$M==SERVER&&$U==$_GET["username"]&&$k==DB)unset($nj[$x]);}cookie("adminer_permanent",implode(" ",$nj));}function
auth_error($m,array&$nj,$Lf=true){$il=session_name();if(isset($_GET["username"])){header("HTTP/1.1 403 Forbidden");if(($_COOKIE[$il]||$_GET[$il])&&!$_SESSION["token"])$m='Session expired. Please log in again.';elseif($Lf&&($D=get_password())!==null){restart_session();add_invalid_login();if($D===false)$m
.=($m?'<br>':'').sprintf('Master password expired. <a href="https://www.adminer.org/en/extension/"%s>Implement</a> the %s method to make it permanent.',target_blank(),'<code>permanentLogin()</code>');set_password(DRIVER,SERVER,$_GET["username"],null);unset_permanent($nj);}}if(!$_COOKIE[$il]&&$_GET[$il]&&ini_bool("session.use_only_cookies"))$m='Session support must be enabled.';$Ti=session_get_cookie_params();cookie("adminer_key",($_COOKIE["adminer_key"]?:rand_string()),$Ti["lifetime"]);if(!$_SESSION["token"])$_SESSION["token"]=rand(1,1e6);page_header('Login',$m,null);echo"<form action='' method='post'>\n","<div>";if(hidden_fields($_POST,array("auth","token")))echo"<p class='message'>".'The action will be performed after successful login with the same credentials.'."\n";echo
input_token(),"</div>\n";adminer()->loginForm();echo"</form>\n";page_footer("auth");exit;}if(isset($_GET["username"])&&!class_exists('Adminer\Db')){unset($_SESSION["pwds"][DRIVER]);unset_permanent($nj);page_header('No extension',sprintf('None of the supported PHP extensions (%s) are available.',implode(", ",Driver::$extensions)),false);page_footer("auth");exit;}$g='';if(isset($_GET["username"])&&is_string(get_password())){check_invalid_login($nj);$fc=adminer()->credentials();$g=Driver::connect($fc[0],$fc[1],$fc[2]);if(is_object($g)){Db::$instance=$g;Driver::$instance=new
Driver($g);if($g->flavor)save_settings(array("vendor-".DRIVER."-".SERVER=>get_driver(DRIVER)));}}$Eg=null;if(!is_object($g)||($Eg=adminer()->login($_GET["username"],get_password()))!==true){$m=(is_string($g)?nl_br(h($g)):(is_string($Eg)?$Eg:'Invalid credentials.')).(preg_match('~^ | $~',get_password())?'<br>'.'There is a space in the entered password, which might be the cause.':'');auth_error($m,$nj);}if($_POST["logout"]&&$_SESSION["token"]&&!verify_token()){page_header('Logout','Invalid CSRF token. Submit the form again.');page_footer("db");exit;}if(!$_SESSION["token"])$_SESSION["token"]=rand(1,1e6);stop_session(true);if($Ka&&$_POST["token"])$_POST["token"]=get_token();$m='';if($_POST){if(!verify_token()){header("HTTP/1.1 403 Forbidden");$m='Invalid CSRF token. Submit the form again.'.' '.'If you did not send this request from Adminer, close this page.';}}elseif($_SERVER["REQUEST_METHOD"]=="POST"){header("HTTP/1.1 413 Content Too Large");$m=sprintf('The POST data is too large. Reduce the data or increase the %s configuration directive.',"<b>post_max_size</b>");if(isset($_GET["sql"]))$m
.=' '.'You can upload a large SQL file via FTP and import it from the server.';}function
print_select_result($G,$h=null,array$Bi=array(),&$z=0,&$cd=false){$_g=array();$w=array();$e=array();$S=array();$Fj=array();$gd=array();$Zm=array();$H=array();$oh=$cd;$cd=false;for($s=0;(!$z||$s<$z)&&($I=$G->fetch_row());$s++){if(!$s){echo"<div class='scrollable'>\n","<table class='nowrap odds'".($oh?on('click','tableClick').on('dblclick','tableClick').on('keydown','editingKeydown'):"").">\n","<thead><tr>";for($Xf=0;$Xf<count($I);$Xf++){$n=$G->fetch_field();$B=$n->name;$Q=(isset($n->table)?$n->table:"");$Ai=(isset($n->orgtable)?$n->orgtable:"");$_i=(isset($n->orgname)?$n->orgname:$B);$Ym=driver()->typeName($n);if($Bi&&JUSH=="sql")$_g[$Xf]=($B=="table"?"table=":($B=="possible_keys"?"indexes=":null));elseif($Ai!=""){$ua=($Q!=""?$Q:$Ai);if($Q!="")$H[$Q]=$Ai;if(!isset($w[$ua])){if(!isset($Fj[$Ai])){$Fj[$Ai]=array();foreach(indexes($Ai,$h)as$v){if($v["type"]=="PRIMARY"){$Fj[$Ai]=array_flip($v["columns"]);break;}}}$S[$ua]=$Ai;$w[$ua]=$Fj[$Ai];$e[$ua]=$Fj[$Ai];}if(isset($e[$ua][$_i])){unset($e[$ua][$_i]);$w[$ua][$_i]=$Xf;$_g[$Xf]=$ua;}elseif($oh&&isset($n->orgname)&&$n->db==DB&&!is_blob(array("type"=>$Ym)))$gd[$Xf]=array($ua,$_i,preg_match('~text|json|lob~',$Ym));}$Zm[$Xf]=$Ym;echo"<th title='".h(trim(($Ai!=""?"$Ai.$_i":($n->name!=$_i?$_i:""))." ".$Ym))."'>".h($B).($Bi?doc_link(array('sql'=>"explain-output.html#explain_".strtolower($B),'mariadb'=>"explain/#the-columns-in-explain-select",)):"");}foreach($gd
as$Xf=>$ib){if($e[$ib[0]])unset($gd[$Xf]);}echo"<tbody>\n";}$kf=array();foreach($w
as$ua=>$v){if($v&&!$e[$ua]){$u="";foreach($v
as$_b=>$Xf){if($I[$Xf]===null){$u=null;break;}$u
.="&where[".url_escape(bracket_escape($_b))."]=".url_escape($I[$Xf]);}$kf[$ua]=$u;}}echo"<tr>";foreach($I
as$x=>$W){$_="";if(isset($_g[$x])){if($Bi&&JUSH=="sql"){$Q=$I[array_search("table=",$_g)];$_=ME.$_g[$x].url_escape($Bi[$Q]!=""?$Bi[$Q]:$Q);}elseif(idx($kf,$_g[$x])!==null)$_=ME."edit=".url_escape($S[$_g[$x]]).$kf[$_g[$x]];}$ff=select_value($W,$_,array('type'=>(preg_match('~binary~',$Zm[$x])?'blob':$Zm[$x])),null);$c="";$ib=idx($gd,$x);if($ib&&idx($kf,$ib[0])!==null&&is_utf8($W)){$cd=true;$c=" data-name='".h("val[".bracket_escape($S[$ib[0]])."][".bracket_escape(substr($kf[$ib[0]],1))."][".bracket_escape($ib[1])."]")."' data-text='".($ib[2]?1:0)."'".(html_entity_decode(strip_tags($ff),ENT_QUOTES,"UTF-8")!==$W?" data-value='".h($W)."'":"");}echo"<td".(preg_match(number_type(),$Zm[$x])?" class='number'":"")."$c>$ff";}}$z=$s;echo($s?"</table>\n</div>":"<p class='message'>".'No rows.')."\n";return$H;}function
textarea($B,$X,$J=10,$Db=80,$Zf=JUSH){echo"<textarea name='".h($B)."' rows='$J' cols='$Db' class='sqlarea jush-".h($Zf)."' spellcheck='false' wrap='off'>";if(is_array($X)){foreach($X
as$W)echo
h($W[0])."\n\n\n";}else
echo
h($X);echo"</textarea>";}function
select_input($c,array$vi,$X="",$oj=""){if($vi&&$X!=""&&!isset($vi[$X]))$vi=array($X=>$X)+$vi;$mm=($vi?"select":"input");return"<$mm$c".($vi?"><option value=''>$oj".optionlist($vi,$X,true)."</select>":" size='10' value='".h($X)."' placeholder='$oj'>");}function
json_row($x,$W=null,$vd=true){static$Zd=true;if($Zd)echo"{";if($x!=""){echo($Zd?"":",")."\n\t\"".addcslashes($x,"\r\n\t\"\\/").'": '.($W!==null?($vd?'"'.addcslashes($W,"\r\n\"\\/").'"':$W):'null');$Zd=false;}else{echo"\n}\n";$Zd=true;}}function
flat_collations(){$Cb=collations();return(is_array(reset($Cb))?call_user_func_array('array_merge',array_values($Cb)):$Cb);}function
edit_type($x,array$n,array$Cb,array$je=array(),array$Kd=array()){$T=(string)$n["type"];echo"<td><select name='".h($x)."[type]' class='type' aria-labelledby='label-type'".on_help_value().">";if($T&&!array_key_exists($T,driver()->types())&&!isset($je[$T])&&!in_array($T,$Kd))$Kd[]=$T;$Kl=driver()->structuredTypes();if($je)$Kl['Foreign keys']=$je;echo
optionlist(array_merge($Kd,$Kl),$T),"</select><td>","<input name='".h($x)."[length]' value='".h($n["length"])."' size='3'".(!$n["length"]&&preg_match('~var(char|binary)$~',$T)?" class='required'":"")." aria-labelledby='label-length'>","<td class='options'>",($Cb?"<input list='collations' name='".h($x)."[collation]'".option_types($T,'('.text_type().')$')." value='".h($n["collation"])."' placeholder='(".'collation'.")'>":''),(driver()->unsigned?"<select name='".h($x)."[unsigned]'".option_types($T,'^$|'.number_type()).'><option>'.optionlist(driver()->unsigned,$n["unsigned"]).'</select>':''),(isset($n['on_update'])?"<select name='".h($x)."[on_update]'".option_types($T,'timestamp|datetime').'>'.optionlist(array(""=>"(".'ON UPDATE'.")","CURRENT_TIMESTAMP"),(preg_match('~^CURRENT_TIMESTAMP~i',$n["on_update"])?"CURRENT_TIMESTAMP":$n["on_update"])).'</select>':''),($je?"<select name='".h($x)."[on_delete]'".option_types($T,'`')."><option value=''>(".'ON DELETE'.")".optionlist(explode("|",driver()->onActions),$n["on_delete"])."</select> ":" ");}function
option_types($T,$Zm){return" data-types='".h($Zm)."'".(preg_match("~$Zm~",$T)?"":" class='hidden'");}function
process_length($y){if(JUSH=="mssql"&&preg_match('~^\s*\(?\s*max\s*\)?\s*$~i',$y))return"(max)";$qd=driver()->enumLength;return(preg_match("~^\\s*\\(?\\s*$qd(?:\\s*,\\s*$qd)*+\\s*\\)?\\s*\$~",$y)&&preg_match_all("~$qd~",$y,$Jg)?"(".implode(",",$Jg[0]).")":preg_replace('~^[0-9].*~','(\0)',preg_replace('~[^-0-9,+()[\]]~','',$y)));}function
process_in($W){$W=preg_replace('~^\s*\(\s*(.*?)\s*\)\s*$~s','\1',$W);$Uj="'((?:[^']|'')*+)'";$Wf=(preg_match("~^\\s*$Uj(?:\\s*,\\s*$Uj)*+\\s*\$~",$W)&&preg_match_all("~$Uj~",$W,$Jg)?str_replace("''","'",$Jg[1]):array_map('trim',explode(",",$W)));return"(".implode(", ",array_map('Adminer\q',$Wf)).")";}function
process_between($W){$Uj="'((?:[^']|'')*+)'";$Wj=(preg_match("~^\\s*$Uj\\s+AND\\s+$Uj\\s*\$~i",$W,$A)?array(str_replace("''","'",$A[1]),str_replace("''","'",$A[2])):preg_split('~\s+AND\s+~i',trim($W)));return(count($Wj)==2?q($Wj[0])." AND ".q($Wj[1]):"NULL AND NULL");}function
process_type(array$n,$Ab="COLLATE"){return" ".(is_user_type($n["type"])?idf_escape($n["type"]):$n["type"]).process_length($n["length"]).(preg_match(number_type(),$n["type"])&&in_array($n["unsigned"],driver()->unsigned)?" $n[unsigned]":"").(preg_match('~'.text_type().'~',$n["type"])&&$n["collation"]?" $Ab ".(JUSH=="mssql"&&preg_match('~^\w+$~',$n["collation"])?$n["collation"]:q($n["collation"])):"");}function
process_field(array$n,array$Vm){if($n["on_update"])$n["on_update"]=str_ireplace("current_timestamp()","CURRENT_TIMESTAMP",$n["on_update"]);return
array(idf_escape(trim($n["field"])),process_type($Vm),($n["null"]?" NULL":" NOT NULL"),default_value($n),(preg_match('~timestamp|datetime~',$n["type"])&&$n["on_update"]?" ON UPDATE $n[on_update]":""),(support("comment")&&$n["comment"]!=""?" COMMENT ".q($n["comment"]):""),($n["auto_increment"]?auto_increment():null),);}function
default_value(array$n){if($n["default"]===null)return"";$l=str_replace("\r","",$n["default"]);$ye=$n["generated"];$P=!preg_match('~]$~',$n["length"])&&(preg_match('~char|binary|text|json|enum|set|String~',$n["type"])||driver()->enumLength($n));return(in_array($ye,driver()->generated)?(JUSH=="mssql"?" AS ($l)".($ye=="VIRTUAL"?"":" $ye"):" GENERATED ALWAYS AS ($l) $ye"):(preg_match('~^GENERATED ~i',$l)?" $l":" DEFAULT ".($P||preg_match('~^(?![a-z])~i',$l)?(JUSH=="sql"&&preg_match('~text|json~',$n["type"])?"(".q($l).")":q($l)):str_ireplace("current_timestamp()","CURRENT_TIMESTAMP",(JUSH=="sqlite"?"($l)":$l)))));}function
edit_fields(array$o,array$Cb,$T="TABLE",array$je=array()){$o=array_values($o);$wc=(($_POST?$_POST["defaults"]:get_setting("defaults"))?"":" class='hidden'");$Ib=(($_POST?$_POST["comments"]:get_setting("comments"))?"":" class='hidden'");echo"<thead><tr>\n",($T=="PROCEDURE"?"<td>":""),"<th id='label-name'>".($T=="TABLE"?'Column name':'Parameter name'),"<th id='label-type'>".'Type'."<textarea id='enum-edit' rows='4' cols='12' wrap='off' hidden></textarea>".script("qs('#enum-edit').onblur = editingLengthBlur;"),"<th id='label-length'>".'Length',"<th>".'Options';if($T=="TABLE")echo"<th id='label-null'>NULL\n","<th><input type='radio' name='auto_increment_col' value=''><abbr id='label-ai' title='".'Auto Increment'."'>AI</abbr>",doc_link(array('sql'=>"example-auto-increment.html",'mariadb'=>"auto_increment/",'sqlite'=>"autoinc.html",'pgsql'=>"datatype-numeric.html#DATATYPE-SERIAL",'cockroach'=>"serial",'mssql'=>"t-sql/statements/create-table-transact-sql-identity-property",)),"<th id='label-default'$wc>".'Default value',(support("comment")?"<th id='label-comment'$Ib>".'Comment':"");$ng=!support("move_col");echo"<td>".icon("plus","add[".($ng?count($o):0)."]","+",'Add next',($ng?on('click','editingAddLastRow'):"")),"<tbody".on('click','editingClick').on('input','editingInput').on('keydown','editingKeydown').">\n";foreach($o
as$s=>$n){$s++;$Ci=$n[($_POST?"orig":"field")];$Ic=(isset($_POST["add"][$s-1])||(isset($n["field"])&&!idx($_POST["drop_col"],$s)))&&(support("drop_col")||$Ci=="");echo"<tr".($Ic?"":" hidden").">\n",($T=="PROCEDURE"?"<td>".html_select("fields[$s][inout]",explode("|",driver()->inout),$n["inout"]):"")."<th>",(support("move_col")?icon("move","","↕",'Move')." ":"");if($Ic)echo"<input name='fields[$s][field]' value='".h($n["field"])."' data-maxlength='64' autocapitalize='off' aria-labelledby='label-name'".(isset($_POST["add"][$s-1])?" autofocus":"").">";echo
input_hidden("fields[$s][orig]",$Ci);edit_type("fields[$s]",$n,$Cb,$je);if($T=="TABLE"){echo"<td><label class='block'>".checkbox("fields[$s][null]",1,$n["null"],"","","","label-null")."</label>","<td><label class='block'><input type='radio' name='auto_increment_col' value='$s'".($n["auto_increment"]?" checked":"")." aria-labelledby='label-ai'></label>","<td$wc>".(driver()->generated?html_select("fields[$s][generated]",array_merge(array("","DEFAULT"),driver()->generated),$n["generated"])." ":checkbox("fields[$s][generated]",1,$n["generated"],"","","","label-default"));$c=" name='fields[$s][default]' aria-labelledby='label-default'";$X=h($n["default"]);echo(preg_match('~\n~',$n["default"])?"<textarea$c rows='2' cols='30' style='vertical-align: bottom;'>\n$X</textarea>":"<input$c value='$X'>");if(support("comment")){$c=" name='fields[$s][comment]' data-maxlength='".(min_version(5.5)?1024:255)."' aria-labelledby='label-comment'";echo"<td$Ib>".adminer()->commentInput('COLUMN',$c,$n["comment"]);}}echo"<td>",(support("move_col")?icon("plus","add[$s]","+",'Add next')." ":""),($Ci==""||support("drop_col")?icon("cross","drop_col[$s]","x",'Remove'):"");}}function
process_fields(array&$o){if($_POST["add"]){$o=array_values($o);array_splice($o,key($_POST["add"]),0,array(array()));}return$_POST["add"]||$_POST["drop_col"];}function
drop_create($Vc,$i,$Xc,$sm,$Zc,$Dg,$eh,$ch,$dh,$hi,$Ih){if($_POST["drop"])query_redirect($Vc,$Dg,$eh);elseif($hi=="")query_redirect($i,$Dg,$dh);elseif(support("transaction_ddl")){driver()->begin();queries_redirect($Dg,$ch,queries($Vc)&&queries($i)&&driver()->commit());driver()->rollback();}elseif($hi!=$Ih){$ec=queries($i);queries_redirect($Dg,$ch,$ec&&queries($Vc));if($ec&&$Xc)queries($Xc);}else
queries_redirect($Dg,$ch,queries($sm)&&queries($Zc)&&queries($Vc)&&queries($i));}function
create_trigger($li,array$I){$ym=" $I[Timing] $I[Event]".(preg_match('~ OF~',$I["Event"])?" $I[Of]":"");return"CREATE TRIGGER ".idf_escape($I["Trigger"]).(JUSH=="mssql"?$li.$ym:$ym.$li).preg_replace('~[\s;]+$~',''," $I[Type]\n$I[Statement]").";";}function
q_dollar($P){$Ac='$$';while(strpos($P.$Ac,$Ac)!=strlen($P))$Ac='$_'.substr($Ac,1);return$Ac.$P.$Ac;}function
routine_collate($Bb){static$nb=array();if($Bb&&!$nb){foreach(collations()as$mb=>$Dn){foreach((array)$Dn
as$W)$nb[$W]=$mb;}}return($nb[$Bb]?"CHARACTER SET ".q($nb[$Bb])." ":"")."COLLATE";}function
create_routine($vk,array$I){$N=array();$o=$I["fields"];ksort($o);foreach($o
as$n){if($n["field"]!=""){$Df=(preg_match("~^(".driver()->inout.")\$~",$n["inout"])?$n["inout"]:"");$N[]="\n  ".(JUSH=="mssql"?"@$n[field]".process_type($n).($Df?" $Df":""):($Df?"$Df ":"").idf_escape($n["field"]).process_type($n,routine_collate($n["collation"])));}}$yc="";$vi=array();foreach(routine_options($vk)as$x=>$Y){$X=idx($I["options"],$x,"");if($x=="DEFINER")$yc=($X?" $x=".implode("@",array_map('Adminer\q',explode("@",$X,2))):"");elseif(!$Y){if($X!="")$vi[]="$x ".q($X);}elseif($X!=reset($Y)&&in_array($X,$Y))$vi[]=$X;}$lg=$I["language"];$zc=preg_replace('~[\s;]+$~','',$I["definition"]);$Rc=(JUSH=="pgsql"||($lg&&$lg!="sql"));$Si=($N?implode(",",$N)."\n":"");return"CREATE$yc $vk ".table(trim($I["name"])).(JUSH=="mssql"&&$vk=="PROCEDURE"?rtrim($Si):" ($Si)").($vk=="FUNCTION"?"\nRETURNS".process_type($I["returns"],routine_collate($I["returns"]["collation"])):"").($lg?" LANGUAGE $lg":"").($vi?"\n".implode(" ",$vi):"").($Rc?" AS ".q_dollar("\n".trim($zc)."\n"):(JUSH=="mssql"?"\nAS":"")."\n$zc;");}function
remove_definer($F){$yc=implode("@",array_map('Adminer\idf_escape',explode("@",logged_user(),2)));return
preg_replace('(^([A-Z =]+) DEFINER='.preg_quote($yc).')','\1',$F);}function
object_name($T,$Q,array$e){return
str_replace(array("{table}","{columns}"),array($Q,implode("_",$e)),adminer()->namePattern($T));}function
format_foreign_key(array$q,$B=""){$k=$q["db"];$Rh=$q["ns"];return($B!=""?" CONSTRAINT ".idf_escape($B):"")." FOREIGN KEY (".implode(", ",array_map('Adminer\idf_escape',$q["source"])).") REFERENCES ".($k!=""&&$k!=$_GET["db"]?idf_escape($k).".":"").($Rh!=""&&$Rh!=$_GET["ns"]?idf_escape($Rh).".":"").idf_escape($q["table"])." (".implode(", ",array_map('Adminer\idf_escape',$q["target"])).")".(preg_match("~^(".driver()->onActions.")\$~",$q["on_delete"])?" ON DELETE $q[on_delete]":"").(preg_match("~^(".driver()->onActions.")\$~",$q["on_update"])?" ON UPDATE $q[on_update]":"").($q["deferrable"]?" $q[deferrable]":"");}function
tar_file($p,$Cm){$H=pack("a100a8a8a8a12a12",$p,644,0,0,decoct($Cm->size),decoct(time()));$tb=8*32;for($s=0;$s<strlen($H);$s++)$tb+=ord($H[$s]);$H
.=sprintf("%06o",$tb)."\0 ";echo$H,str_repeat("\0",512-strlen($H));$Cm->send();echo
str_repeat("\0",511-($Cm->size+511)%512);}function
doc_version(){$gl=connection()->server_info;if(JUSH=='oracle'){preg_match('~(?:.* |^)(\d+)\.\d+\.\d+\.\d+\.\d+~s',$gl,$A);return($A[1]>=18?$A[1]:"19");}$fk=(JUSH=='sql'||connection()->flavor=='cockroach'?'~^\d+\.\d+~':'~^\d\.?\d~');$Gn=(preg_match($fk,$gl,$A)?$A[0]:"");if(JUSH=='mssql')return($Gn>=15?"sql-server-ver$Gn":($Gn==12?"azuresqldb-current":"sql-server-2017"));return$Gn;}function
doc_link(array$jj,$tm="📖"){$Gn=doc_version();$rn=array('sql'=>"https://dev.mysql.com/doc/refman/$Gn/en/",'sqlite'=>"https://www.sqlite.org/",'pgsql'=>"https://www.postgresql.org/docs/".(connection()->flavor=='cockroach'?"current":$Gn)."/",'mssql'=>"https://learn.microsoft.com/en-us/sql/",'oracle'=>"https://docs.oracle.com/en/database/oracle/oracle-database/$Gn/",);if(connection()->flavor=='maria'){$rn['sql']="https://mariadb.com/kb/en/";$jj['sql']=($jj['mariadb']?:str_replace(".html","/",$jj['sql']));}if(connection()->flavor=='cockroach'&&$jj['cockroach']){$rn['pgsql']="https://docs.cockroachlabs.com/docs/v$Gn/";$jj['pgsql']=$jj['cockroach'];}return($jj[JUSH]?" <a href='".h($rn[JUSH].$jj[JUSH].(JUSH=='mssql'?"?view=$Gn":""))."'".target_blank()." class='doc' title='".'Documentation'."'>$tm</a>":"");}function
db_size($k){if(!connection()->select_db($k))return"?";$H=0;foreach(table_status()as$R)$H+=$R["Data_length"]+$R["Index_length"];return
adminer()->formatSizeValue($H);}function
set_utf8mb4($i){static$N=false;if(!$N&&preg_match('~\butf8mb4~i',$i)){$N=true;echo"SET NAMES ".charset(connection()).";\n\n";}}if(DB==""&&isset($_GET["ns"]))redirect(remove_from_uri('ns'));if(!(DB!=""?connection()->select_db(DB):isset($_GET["sql"])||isset($_GET["dump"])||isset($_GET["database"])||isset($_GET["processlist"])||isset($_GET["privileges"])||isset($_GET["user"])||isset($_GET["variables"])||$_GET["script"]=="connect"||$_GET["script"]=="kill")){if(DB!=""||$_GET["refresh"]){restart_session();set_session("dbs",null);}if(DB!="")page_header('Database'.": ".h(DB),adminer()->error(),true,"","db");else{if(!isset($_GET["db"])&&support("single_db")){$j=adminer()->databases();if($j)redirect(ME."db=".url_escape($j[0]));}if($_POST["db"]&&!$m)queries_redirect(substr(ME,0,-1),'Databases have been dropped.',drop_databases($_POST["db"]));page_header('Select database',$m,false);echo"<p class='links'>\n";foreach(array('database'=>'Create database','privileges'=>'Privileges','processlist'=>'Process list','variables'=>'Variables','status'=>'Status',)as$x=>$W){if(support($x))echo"<a href='".h(ME)."$x='>$W</a>\n";}echo"<p>".sprintf('%s version: %s through PHP extension %s',get_driver(DRIVER),"<b>".h(connection()->server_info)."</b>","<b>".connection()->extension."</b>")."\n","<p>".sprintf('Logged in as: %s',"<b>".h(logged_user())."</b>")."\n";$j=adminer()->databases();if($j){$Hk=support("scheme");$Cb=collations();echo"<form action='' method='post'>\n","<table class='checkable odds'".on('click','tableClick').on('dblclick','tableClick').">\n","<thead><tr>".(support("database")?"<td class='hover'>":"")."<th".(JUSH!='mssql'?" aria-sort='ascending'":"").">".'Database'.(get_session("dbs")!==null?" - <a href='".h(ME)."refresh=1'>".'Refresh'."</a>":"")."<th>".'Collation'."<th>".'Tables'."<th>".'Size'." - <a href='".h(ME)."dbsize=1'".on('click','ajaxSetHtml',ME."script=connect").">".'Compute'."</a>"."<tbody>\n";$j=($_GET["dbsize"]?count_tables($j):array_flip($j));foreach($j
as$k=>$S){$uk=h(preg_replace('~&db=[^&]*~','',ME))."db=".url_escape($k);$t=h("Db-".$k);echo"<tr>".(support("database")?"<td class='hover'>".checkbox("db[]",$k,in_array($k,(array)$_POST["db"]),"","","",$t):""),"<th><a href='$uk' id='$t'>".h($k)."</a>";$Bb=h(db_collation($k,$Cb));echo"<td>".(support("database")?"<a href='$uk".($Hk?"&amp;ns=":"")."&amp;database=' title='".'Alter database'."'>$Bb</a>":$Bb),"<td align='right'><a href='$uk&amp;schema=' id='tables-".h($k)."' title='".'Database schema'."'>".($_GET["dbsize"]?format_number($S):"?")."</a>","<td align='right' id='size-".h($k)."'>".($_GET["dbsize"]?db_size($k):"?"),"\n";}echo"</table>\n",(support("database")?"<div class='footer'><div>\n"."<fieldset><legend>".'Selected'." <span id='selected'></span></legend><div>\n"."<input type='hidden' name='all' value=''".on('click','countDbs').">\n"."<input type='submit' name='drop' value='".'Drop'."'".confirm().">\n"."</div></fieldset>\n"."</div></div>\n":""),input_token(),"</form>\n",script("tableCheck();");}$oa=adminer();$sj=($oa
instanceof
Plugins?$oa->plugins:array());$Uc=($oa
instanceof
Plugins?$oa->drivers:array());$Fc=design_checksums();if($sj||$Uc||$Fc){$ub=($oa
instanceof
Plugins?$oa->checksums():array());$Zh=Plugins::officialChecksums();$mn=function($qn){return" (<a href='$qn'".target_blank()." class='update'>".VERSION."</a>)";};$rj=function($Td)use($ub,$Zh,$mn){return($ub[$Td]&&$Zh[$Td]&&$ub[$Td]!==$Zh[$Td]?$mn("https://www.adminer.org/plugins/?version=".VERSION):"");};echo"<div class='plugins'>\n","<h3>".'Loaded plugins'."</h3>\n<ul>\n";foreach($sj
as$pj){$dk=new
\ReflectionObject($pj);$Cc=(method_exists($pj,'description')?$pj->description():"");if(!$Cc){if(preg_match('~^/[\s*]+(.+)~',$dk->getDocComment(),$A))$Cc=$A[1];}$Ik=(method_exists($pj,'screenshot')?$pj->screenshot():"");echo"<li><b>".get_class($pj)."</b>".h($Cc?": $Cc":"").($Ik?" (<a href='".h($Ik)."'".target_blank().">".'screenshot'."</a>)":"").$rj(basename((string)$dk->getFileName(),'.php'))."\n";}foreach($Uc
as$t=>$B)echo"<li><b>".h($t)."</b>: ".h($B).$rj(basename((string)$oa->driverFiles[$t],'.php'))."\n";if($Fc){$bi=official_design_checksums();foreach($Fc
as$p=>$Ec){list($B,$tb)=$Ec;$ai=$bi["$B/$p"];echo"<li><b>".h($p)."</b>".h($B?": $B":"").($ai&&$ai!==$tb?$mn("https://www.adminer.org/?version=".VERSION."#extras"):"")."\n";}}echo"</ul>\n";adminer()->pluginsLinks();echo"</div>\n";}}page_footer("db");exit;}if(support("scheme")){if(DB!=""&&$_GET["ns"]!==""){if(!isset($_GET["ns"]))redirect(preg_replace('~&db=[^&]+~','\0&ns='.url_escape(get_schema()),relative_uri()));if(!set_schema($_GET["ns"]))page_header('Schema'.h(": $_GET[ns]"),adminer()->error(),true,"","ns");}}adminer()->afterConnect();class
TmpFile{private$handler;var$size=0;function
__construct(){$this->handler=tmpfile();}function
write($Vb){$this->size+=strlen($Vb);fwrite($this->handler,$Vb);}function
send(){fseek($this->handler,0);fpassthru($this->handler);fclose($this->handler);}}if($_GET["select"]!=""&&($_POST["edit"]||$_POST["clone"])&&!$_POST["save"])$_GET["edit"]=$_GET["select"];if(isset($_GET["callf"]))$_GET["call"]=$_GET["callf"];if(isset($_GET["function"]))$_GET["procedure"]=$_GET["function"];if(isset($_GET["download"])){$a=$_GET["download"];$o=fields($a);header("Content-Type: application/octet-stream");$Y=array_merge((array)$_GET["where"],(array)$_GET["val"]);header("Content-Disposition: attachment; filename=".friendly_url("$a-".implode("_",$Y)).".".friendly_url($_GET["field"]));$L=array(idf_escape($_GET["field"]));$G=driver()->select($a,$L,array(where($_GET,$o)),$L);$I=($G?$G->fetch_row():array());echo
driver()->value($I[0],$o[$_GET["field"]]);exit;}elseif(isset($_GET["table"])){$a=$_GET["table"];$o=fields($a);if(!$o)$m=adminer()->error();$R=table_status1($a);$B=adminer()->tableName($R);$m=$m?:h($R["Error"]);page_header(($o&&is_view($R)?$R['Engine']=='materialized view'?'Materialized view':'View':'Table').": ".($B!=""?$B:h($a)),$m,array(),"",!$o,($o?doc_link(array(JUSH=>driver()->tableHelp($a,is_view($R)))):""));$tk=array();foreach($o
as$x=>$n)$tk+=$n["privileges"];adminer()->selectLinks($R,(isset($tk["insert"])||!support("table")?"":null));$f=$R["Comment"];if($f!="")echo"<p class='nowrap'>".'Comment'.": ".adminer()->commentValue('TABLE',$f)."\n";if($o)adminer()->tableStructurePrint($o,$R);function
tables_links(array$S){echo"<ul>\n";foreach($S
as$I){$_=preg_replace('~&ns=[^&]*~',"&ns=".url_escape($I["ns"]),ME);echo"<li><a href='".h($_."table=".url_escape($I["table"]))."'>".($I["ns"]!=$_GET["ns"]?"<b>".h($I["ns"])."</b>.":"").h($I["table"])."</a>";}echo"</ul>\n";}$Bf=driver()->inheritsFrom($a);if($Bf){echo"<h3>".'Inherits from'."</h3>\n";tables_links($Bf);}if(support("indexes")&&driver()->supportsIndex($R)){echo"<div>\n","<h3 id='indexes'>".'Indexes'."</h3>\n";$w=indexes($a);if($w)adminer()->tableIndexesPrint($w,$R);if(driver()->supportsAlterIndex($R))echo'<p class="links hover"><a href="'.h(ME).'indexes='.url_escape($a).'">'.'Alter indexes'."</a>\n";echo"</div>\n";}if(!is_view($R)&&driver()->supportsAlterTable($R)){if(fk_support($R)){echo"<div>\n","<h3 id='foreign-keys'>".'Foreign keys'."</h3>\n";$je=foreign_keys($a);if($je){echo"<table>\n","<thead><tr><th>".'Source'."<th>".'Target'."<th>".'ON DELETE'."<th>".'ON UPDATE'."<td class='hover'><tbody>\n";foreach($je
as$B=>$q){echo"<tr title='".h($B)."'>","<th><i>".implode("</i>, <i>",array_map('Adminer\h',$q["source"]))."</i>";$_=($q["db"]!=""?preg_replace('~&db=[^&]*~',"&db=".url_escape($q["db"]),ME):($q["ns"]!=""?preg_replace('~&ns=[^&]*~',"&ns=".url_escape($q["ns"]),ME):ME));echo"<td><a href='".h($_."table=".url_escape($q["table"]))."'>".($q["db"]!=""&&$q["db"]!=DB?"<b>".h($q["db"])."</b>.":"").($q["ns"]!=""&&$q["ns"]!=$_GET["ns"]?"<b>".h($q["ns"])."</b>.":"").h($q["table"])."</a>","(<i>".implode("</i>, <i>",array_map('Adminer\h',$q["target"]))."</i>)","<td>".h($q["on_delete"]),"<td>".h($q["on_update"]),'<td class="hover"><a href="'.h(ME.'foreign='.url_escape($a).'&name='.url_escape($B)).'">'.'Alter'.'</a>',"\n";}echo"</table>\n";}echo'<p class="links hover"><a href="'.h(ME).'foreign='.url_escape($a).'">'.'Create foreign key'."</a>\n","</div>\n";}if(support("check")){echo"<div>\n","<h3 id='checks'>".'Checks'."</h3>\n";$pb=driver()->checkConstraints($a);if($pb){echo"<table>\n";foreach($pb
as$x=>$W)echo"<tr title='".h($x)."'>","<td><code class='jush-".JUSH."'>".shorten_utf8(preg_replace('~\s+~',' ',ltrim($W)),80,"</code>"),"<td class='hover'><a href='".h(ME.'check='.url_escape($a).'&name='.url_escape($x))."'>".'Alter'."</a>","\n";echo"</table>\n";}echo'<p class="links hover"><a href="'.h(ME).'check='.url_escape($a).'">'.'Create check'."</a>\n","</div>\n";}}if(support(is_view($R)?"view_trigger":"trigger")&&driver()->supportsAlterTable($R)){echo"<div>\n","<h3 id='triggers'>".'Triggers'."</h3>\n";$Sm=triggers($a);if($Sm){echo"<table>\n";foreach($Sm
as$x=>$W){echo"<tr valign='top'><td>".h($W[0])."<td>".h($W[1])."<th>".h($x)."<td class='hover'><a href='".h(ME.'trigger='.url_escape($a).'&name='.url_escape($x))."'>".'Alter'."</a>";$vk=$W[2];if($vk){$xk=preg_replace('~&ns=[^&]*~',"&ns=".url_escape($vk["ns"]),ME).'function='.url_escape($vk["function"]).'&name='.url_escape($vk["name"]);echo", <a href='".h($xk)."' title='".h($vk["name"])."'>".'Alter function'."</a>";}echo"\n";}echo"</table>\n";}echo'<p class="links hover"><a href="'.h(ME).'trigger='.url_escape($a).'">'.'Create trigger'."</a>\n","</div>\n";}$ll=driver()->shadowTables($a);if($ll){echo"<h3 id='shadow-tables'>".'Shadow tables'."</h3>\n";tables_links($ll);}$Af=driver()->inheritedTables($a);if($Af){echo"<h3 id='partitions'>".'Inherited by'."</h3>\n";$Xi=driver()->partitionsInfo($a);if($Xi)echo"<p><code class='jush-".JUSH."'>BY ".h("$Xi[partition_by]($Xi[partition])")."</code>\n";tables_links($Af);}}elseif(isset($_GET["schema"])){page_header('Database schema',"",array(),h(DB.($_GET["ns"]?".$_GET[ns]":"")));function
schema_column($Q,array$ck,array&$e){if(!isset($e[$Q])){$e[$Q]=0;foreach((array)idx($ck,$Q)as$B=>$ek){if($B!=$Q)$e[$Q]=max($e[$Q],schema_column($B,$ck,$e)+1);}}return$e[$Q];}function
type_class($T){foreach(array('char'=>'text','date'=>'time|year','binary'=>'blob','enum'=>'set',)as$x=>$W){if(preg_match("~$x|$W~",$T))return" class='$x'";}}$dm=array();$fm=array();$em=array();$Qd=array();$ca=($_GET["schema"]?:$_COOKIE["adminer_schema-".str_replace(".","_",DB)]);preg_match_all('~([^:]+):([-0-9.]+)x([-0-9.]+)(_|$)~',$ca,$Jg,PREG_SET_ORDER);foreach($Jg
as$s=>$A){$dm[$A[1]]=array((float)$A[2],(float)$A[3]);$fm[]="\n\t'".js_escape($A[1])."': [ $A[2], $A[3] ]";}$K=array();$ck=array();$je=array();$xa=driver()->allFields();$Xe=array();$gm=array();foreach(table_status('',true)as$Q=>$R){if(!is_view($R)){if(adminer()->tableName($R)!=""&&!$R["dependent"])$gm[$Q]=$R;else$Xe[$Q]=true;}}foreach($gm
as$Q=>$R){$E=0;$K[$Q]["fields"]=array();foreach($xa[$Q]as$n){$E+=1.25;$Qd[$Q][$n["field"]]=$E;$K[$Q]["fields"][$n["field"]]=$n;}foreach(adminer()->foreignKeys($Q)as$W){if($W["db"]==""&&$W["ns"]==""&&!$Xe[$W["table"]]){$je[$Q][]=$W;$ck[$W["table"]][$Q]=array();}}}$e=array();$Be=array();$Tn=array();$He=array();foreach(array_keys($K)as$B)schema_column($B,$ck,$e);arsort($e);foreach($e
as$B=>$d){$kh=null;foreach((array)idx($je,$B)as$W){if($W["table"]!=$B&&$K[$W["table"]])$kh=($kh===null?$e[$W["table"]]:min($kh,$e[$W["table"]]));}$e[$B]=max($d,(int)$kh-1);}foreach($K
as$B=>$Q){$d=$e[$B];$Be[$d][]=$B;$vm=.75*strlen($B);foreach($Q["fields"]as$n)$vm=max($vm,.65*strlen($n["field"]));$Tn[$d]=max(idx($Tn,$d,0),ceil($vm)+1);}foreach($je
as$B=>$Dn){foreach($Dn
as$W){$Ge=$e[$B]+(idx($e,$W["table"],$e[$B])>$e[$B]?1:0);$He[$Ge]=idx($He,$Ge,0)+1;}}ksort($Be);$Ve=0;$Sn=0;$Eb=0;$Dj=null;$Zl=array();$im=array();foreach($Be
as$d=>$S){if($Dj!==null){$Eb=round($Eb+$Tn[$Dj]+1.7+idx($He,$d,0)*.1,1);$yi=array();foreach($S
as$B){$Pl=0;$cc=0;$Dh=array_keys((array)idx($ck,$B));foreach((array)idx($je,$B)as$W)$Dh[]=$W["table"];foreach($Dh
as$xh){if($K[$xh]&&$e[$xh]<$d){$Pl+=$K[$xh]["pos"][0];$cc++;}}$yi[$B]=($cc?$Pl/$cc:$Ve);}asort($yi);$S=array_keys($yi);}$Fm=0;foreach($S
as$B){$E=1.25*count($K[$B]["fields"]);$K[$B]["pos"]=($dm[$B]?:array($Fm,$Eb));$Zl[$B]=$K[$B]["pos"][1];$im[$B]=$Tn[$d];$Fm+=2.5+$E;$Ve=max($Ve,$K[$B]["pos"][0]+2.5+$E);$Sn=max($Sn,round($K[$B]["pos"][1]+$Tn[$d],1));if(!$dm[$B])$em[]="\n\t'".js_escape($B)."': [ ".$K[$B]["pos"][0].", ".$K[$B]["pos"][1]." ]";}$Dj=$d;}$rg=array();$Ua=array();foreach($je
as$B=>$Dn){foreach($Dn
as$W){$om=idx($Zl,$W["table"],$Zl[$B]);$wl=$Zl[$B]+$im[$B];$sk=($om-1>$wl);$pg=($sk?$wl+1:min($Zl[$B],$om)-1);$Ta=idx($Ua,(string)$pg,0);$Ua[(string)$pg]=$Ta+1;$pg=round($sk?min($pg+$Ta*.1,$om-1):$pg-$Ta*.1,1);while($rg[(string)$pg])$pg-=.0001;$K[$B]["references"][$W["table"]][(string)$pg]=array($W["source"],$W["target"]);$ck[$W["table"]][$B][(string)$pg]=$W["target"];$rg[(string)$pg]=true;}}echo'<div id="schema" style="height: ',$Ve,'em; width: ',$Sn,'em;">
<script',nonce(),'>
const tablePos = {',implode(",",$fm)."\n",'};
const tablePosDefault = {',implode(",",$em)."\n",'};
const em = qs(\'#schema\').offsetHeight / ',$Ve,';
document.onmousemove = schemaMousemove;
document.onmouseup = event => schemaMouseup(event, \'',js_escape(DB),'\');
</script>
';foreach($K
as$B=>$Q){echo"<div class='table'".on('mousedown','schemaMousedown')." style='top: ".$Q["pos"][0]."em; left: ".$Q["pos"][1]."em; width: ".$im[$B]."em;'>",'<a href="'.h(ME).'table='.url_escape($B).'"><b>'.h($B)."</b></a>";foreach($Q["fields"]as$n){$W='<span'.type_class($n["type"]).' title="'.h($n["type"].($n["length"]?"($n[length])":"").($n["null"]?" NULL":'')).'">'.h($n["field"]).'</span>';echo"<br>".($n["primary"]?"<i>$W</i>":$W);}foreach((array)$Q["references"]as$pm=>$ek){foreach($ek
as$pg=>$Zj){$qg=$pg-$Q["pos"][1];$Ml=($qg>0?"left: 100%; width: calc($qg"."em - 100%)":"left: $qg"."em");$Sn=($qg>0?"100%":(-$qg)."em");$s=0;foreach($Zj[0]as$vl)echo"\n<div class='references' title='".h($pm)."' id='refs$pg-".($s++)."' style='$Ml"."; top: ".$Qd[$B][$vl]."em; padding-top: .5em;'>"."<div style='border-top: 1px solid gray; width: $Sn;'></div></div>";}}foreach((array)$ck[$B]as$pm=>$ek){foreach($ek
as$pg=>$qm){$qg=$pg-$Q["pos"][1];$s=0;foreach($qm
as$nm)echo"\n<div class='references arrow' title='".h($pm)."' id='refd$pg-".($s++)."' style='left: $qg"."em; top: ".$Qd[$B][$nm]."em;'>"."<div style='height: .5em; border-bottom: 1px solid gray; width: ".(-$qg)."em;'></div>"."</div>";}}echo"\n</div>\n";}foreach($K
as$B=>$Q){foreach((array)$Q["references"]as$pm=>$ek){if($K[$pm]){foreach($ek
as$pg=>$Zj){$lh=$Ve;$Rg=-10;foreach($Zj[0]as$x=>$vl){$uj=$Q["pos"][0]+$Qd[$B][$vl];$vj=$K[$pm]["pos"][0]+$Qd[$pm][$Zj[1][$x]];$lh=min($lh,$uj,$vj);$Rg=max($Rg,$uj,$vj);}echo"<div class='references' id='refl$pg' style='left: $pg"."em; top: $lh"."em; padding: .5em 0;'><div style='border-right: 1px solid gray; margin-top: 1px; height: ".($Rg-$lh)."em;'></div></div>\n";}}}}echo'</div>
<p class="links"><a href="',h(ME."schema=".url_escape($ca)),'" id="schema-link">Permanent link</a>
';}elseif(isset($_GET["dump"])){$a=$_GET["dump"];if($_POST&&!$m){$l=array("auto_increment"=>'');foreach(array("type","routine","event","trigger")as$Rl){if(support($Rl))$l[$Rl."s"]='';}save_settings(array_intersect_key($_POST+$l,array_flip(array("output","format","db_style","schema_style","table_style","data_style"))+$l),"adminer_export");$wa=(DB==""||$_GET["ns"]==="");$S=array_flip((array)$_POST["tables"])+array_flip((array)$_POST["data"]);$Gd=dump_headers((count($S)==1?key($S):DB),($wa||count($S)>1));$Sf=preg_match('~sql~',$_POST["format"]);if($Sf){echo"-- Adminer ".VERSION." ".get_driver(DRIVER)." ".str_replace("\n"," ",connection()->server_info)." dump\n\n";if(JUSH=="sql"){echo"SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
".($_POST["data_style"]?"SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';
":"")."
";connection()->query("SET time_zone = '+00:00'");connection()->query("SET sql_mode = ''");}}$Ml=$_POST["db_style"];$j=array(DB);if(DB==""){$j=$_POST["databases"];if(is_string($j))$j=explode("\n",rtrim(str_replace("\r","",$j),"\n"));}foreach((array)$j
as$k){adminer()->dumpDatabase($k);if(connection()->select_db($k)){if($Sf&&$Ml)echo
use_sql($k,$Ml).";\n\n";foreach(($_GET["ns"]===""?(array)$_POST["schemas"]:(DB!=""||!support("scheme")?array(""):adminer()->schemas()))as$K){if($K!=""){if(DB==""&&information_schema(DB,$K))continue;set_schema($K);}if($Sf&&$_POST["schema_style"]&&function_exists('Adminer\use_schema_sql'))echo
use_schema_sql($_GET["ns"],$_POST["schema_style"]).";\n\n";$Il=($_POST["table_style"]||$_POST["data_style"]?table_status('',true):array());$Fd=array();$oc=array();foreach($Il
as$B=>$R){if($wa||in_array($B,(array)$_POST["tables"]))$Fd[$B]=$R;if($wa||in_array($B,(array)$_POST["data"]))$oc[$B]=$R;}if($Sf){if($_POST["table_style"]=="DROP+CREATE"&&function_exists('Adminer\drop_sql'))echo
drop_sql($Fd);if($_POST["data_style"]=="TRUNCATE+INSERT"&&function_exists('Adminer\truncate_all_sql')){$Tm=array();foreach($oc
as$B=>$R){if(!is_view($R)&&!($_POST["table_style"]=="DROP+CREATE"&&isset($Fd[$B])))$Tm[]=$B;}echo
truncate_all_sql($Tm);}$Li="";if($_POST["types"]){foreach(types()as$t=>$T){$zc=type_definition($t);$Vh=($zc["kind"]=='d'?"DOMAIN":"TYPE");if($zc["definition"])$Li
.=($Ml!='DROP+CREATE'?"DROP $Vh IF EXISTS ".table($T).";;\n":"")."CREATE $Vh ".table($T)." $zc[definition];\n\n";else$Li
.="-- Could not export type ".preg_replace('~[\r\n]+~',' ',$T)."\n\n";}}if($_POST["routines"]){foreach(routines()as$I){$B=$I["ROUTINE_NAME"];$vk=$I["ROUTINE_TYPE"];$_f=routine($I["SPECIFIC_NAME"],$vk);if($_f["definition"]!=""){$i=create_routine($vk,array("name"=>$B)+$_f);set_utf8mb4($i);$Li
.=($Ml!='DROP+CREATE'?"DROP $vk IF EXISTS ".table($B).";;\n":"")."$i;\n\n";}else$Li
.="-- Could not export ".strtolower($vk)." ".preg_replace('~[\r\n]+~',' ',$B)."\n\n";}}if($_POST["events"]){foreach(get_rows("SHOW EVENTS",null,"-- ")as$I){$i=remove_definer(get_val("SHOW CREATE EVENT ".idf_escape($I["Name"]),3));set_utf8mb4($i);$Li
.=($Ml!='DROP+CREATE'?"DROP EVENT IF EXISTS ".idf_escape($I["Name"]).";;\n":"")."$i;;\n\n";}}echo($Li&&JUSH=='sql'?"DELIMITER ;;\n\n$Li"."DELIMITER ;\n\n":$Li);}if($_POST["table_style"]||$_POST["data_style"]){$In=array();foreach($Il
as$B=>$R){$Q=array_key_exists($B,$Fd);$mc=array_key_exists($B,$oc);if($Q||$mc){$Cm=null;if($Gd=="tar"){$Cm=new
TmpFile;ob_start(array($Cm,'write'),1e5);}adminer()->dumpTable($B,($Q?$_POST["table_style"]:""),(is_view($R)?2:0));if(is_view($R))$In[]=$B;elseif($mc){$o=fields($B);$L=array("*");$Zb=convert_fields($o,$o);if($Zb)$L[]=substr($Zb,2);adminer()->dumpData($B,$_POST["data_style"],"",$L);}if($Sf&&$_POST["triggers"]&&$Q&&($Sm=trigger_sql($B)))echo"\nDELIMITER ;;\n$Sm\nDELIMITER ;\n";if($Gd=="tar"){ob_end_flush();tar_file((DB!=""?"":"$k/")."$B.csv",$Cm);}elseif($Sf)echo"\n";}}if($Sf&&$_POST["table_style"]&&function_exists('Adminer\foreign_keys_sql')){foreach($Fd
as$B=>$R){if(!is_view($R))echo
foreign_keys_sql($B);}}if($Sf){foreach($In
as$Hn)adminer()->dumpTable($Hn,$_POST["table_style"],1);}if($Gd=="tar")echo
pack("x1024");}}}}adminer()->dumpFooter();exit;}page_header('Export',$m,($_GET["export"]!=""?array("table"=>$_GET["export"]):array()),h(DB));echo'
<form action="" method="post">
<table class="layout">
';$sc=array('','USE','DROP+CREATE','CREATE');$Fk=(JUSH=="mssql"?array('','DROP+CREATE','CREATE'):$sc);$hm=array('','DROP+CREATE','CREATE');$nc=array('','TRUNCATE+INSERT','INSERT');if(JUSH=="sql")$nc[]='INSERT+UPDATE';$I=get_settings("adminer_export");if(!$I)$I=array("output"=>"text","format"=>"sql","db_style"=>(DB!=""?"":"CREATE"),"schema_style"=>"","table_style"=>"DROP+CREATE","data_style"=>"INSERT");echo"<tr><th>".'Output'."<td>".html_radios("output",adminer()->dumpOutput(),$I["output"])."\n","<tr><th>".'Format'."<td>".html_radios("format",adminer()->dumpFormat(),$I["format"])."\n",(JUSH=="sqlite"?"":"<tr><th>".'Database'."<td>".html_select('db_style',$sc,$I["db_style"]).(support("type")?checkbox("types",1,$I["types"],'User types'):"").(support("routine")?checkbox("routines",1,$I["routines"],'Routines'):"").(support("event")?checkbox("events",1,$I["events"],'Events'):"")),(function_exists('Adminer\use_schema_sql')?"<tr><th>".'Schema'."<td>".html_select('schema_style',$Fk,$I["schema_style"]):""),"<tr><th>".'Tables'."<td>".html_select('table_style',$hm,$I["table_style"]).checkbox("auto_increment",1,$I["auto_increment"],'Auto Increment').(support("trigger")?checkbox("triggers",1,$I["triggers"],'Triggers'):""),"<tr><th>".'Data'."<td>".html_select('data_style',$nc,$I["data_style"]),'</table>
';adminer()->dumpPrint();echo'<p><input type=\'submit\' value=\'Export\'>
',input_token(),'
<table',on('click','dumpClick'),'>
';$Bj=array();if($_GET["ns"]===""&&support("scheme")){echo"<thead><tr><th style='text-align: left;'>","<label class='block'><input type='checkbox' id='check-schemas' checked class='jsonly' title='".'All'."'".on('click','formCheck','^schemas\[').">".'Schema'."</label>","<tbody>\n";foreach(adminer()->schemas()as$K){if(!information_schema(DB,$K))echo"<tr><td>".checkbox("schemas[]",$K,true,$K,"","block")."\n";}}elseif(DB!=""){$rb=($a!=""?"":" checked");echo"<thead><tr>","<th style='text-align: left;'><label class='block'><input type='checkbox' id='check-tables'$rb class='jsonly' title='".'All'."'".on('click','formCheck','^tables\[').">".'Table'."</label>","<th style='text-align: right;'><label class='block'>".'Data'."<input type='checkbox' id='check-data'$rb class='jsonly' title='".'All'."'".on('click','formCheck','^data\[')."></label>","<tbody>\n";$In="";$km=tables_list();foreach($km
as$B=>$T){$Aj=preg_replace('~_.*~','',$B);$rb=($a==""||$a==(substr($a,-1)=="%"?"$Aj%":$B));$Hj="<tr><td>".checkbox("tables[]",$B,$rb,$B,"","block");if($T!==null&&!preg_match('~table~i',$T))$In
.="$Hj\n";else
echo"$Hj<td align='right'><label class='block'><span id='Rows-".h($B)."'></span>".checkbox("data[]",$B,$rb)."</label>\n";$Bj[$Aj]++;}echo$In;if($km)echo
script("ajaxSetHtml('".js_escape(ME)."script=db');");}else{$j=adminer()->databases();echo"<thead><tr><th style='text-align: left;'>","<label class='block'>".($j?"<input type='checkbox' id='check-databases'".($a==""?" checked":"")." class='jsonly' title='".'All'."'".on('click','formCheck','^databases\[').">":"").'Database'."</label>","<tbody>\n";if($j){foreach($j
as$k){if(!information_schema($k)){$Aj=preg_replace('~_.*~','',$k);echo"<tr><td>".checkbox("databases[]",$k,$a==""||$a=="$Aj%",$k,"","block")."\n";$Bj[$Aj]++;}}}else
echo"<tr><td><textarea name='databases' rows='10' cols='20'></textarea>";}echo'</table>
</form>
';$Zd=true;foreach($Bj
as$x=>$W){if($x!=""&&$W>1){echo($Zd?"<p>":" ")."<a href='".h(ME)."dump=".url_escape("$x%")."'>".h($x)."</a>";$Zd=false;}}}elseif(isset($_GET["privileges"])){page_header('Privileges');echo'<p class="links"><a href="'.h(ME).'user=">'.'Create user'."</a>";$G=connection()->query("SELECT User, Host FROM mysql.".(DB==""?"user":"db WHERE ".q(DB)." LIKE Db")." ORDER BY Host, User");$_e=$G;if(!$G)$G=connection()->query("SELECT SUBSTRING_INDEX(CURRENT_USER, '@', 1) AS User, SUBSTRING_INDEX(CURRENT_USER, '@', -1) AS Host");echo"<form action=''><p>\n";hidden_fields_get();echo
input_hidden("db",DB),($_e?"":input_hidden("grant")),"<table class='odds'>\n","<thead><tr><th>".'Username'."<th>".'Server'."<td class='hover'><tbody>\n";while($I=$G->fetch_assoc())echo'<tr><td>'.h($I["User"]),"<td>".h($I["Host"]),'<td class="hover"><a href="'.h(ME.'user='.url_escape($I["User"]).'&host='.url_escape($I["Host"])).'">'.'Edit'."</a>\n";if(!$_e||DB!="")echo"<tr><td><input name='user' autocapitalize='off'>","<td><input name='host' value='localhost' autocapitalize='off'>","<td class='hover'><input type='submit' value='".'Edit'."'>\n";echo"</table>\n","</form>\n";}elseif(isset($_GET["sql"])){if(!$m&&$_POST["export"]){save_settings(array("output"=>$_POST["output"],"format"=>$_POST["format"]),"adminer_import");dump_headers("sql");if($_POST["format"]=="sql")echo"$_POST[query]\n";else{adminer()->dumpTable("","");adminer()->dumpData("","table",$_POST["query"]);adminer()->dumpFooter();}exit;}if(!$m&&$_POST["val"]){$ra=0;$Nl=true;$jb=array();$Ck=0;foreach($_POST["val"]as$J)$Ck+=count($J);$Wa=$Ck>1&&driver()->begin();foreach($_POST["val"]as$Xl=>$J){$Q=bracket_escape($Xl,true);$o=fields($Q);$Yl=indexes($Q);foreach($J
as$u=>$I){parse_str(bracket_escape($u,true),$Z);$dn=array();foreach($Z["where"]as$x=>$W)$dn[bracket_escape($x,true)]=$W;if(!$o||$Z["null"]||array_diff_key($dn,$o)||!unique_array($dn,$Yl)){$Nl=false;break
2;}$N=array();$L=array();foreach($I
as$bg=>$W){$x=bracket_escape($bg,true);$n=idx($o,$x);if(!$n){$Nl=false;break
3;}$N[idf_escape($x)]=(preg_match('~char|text~',$n["type"])||$W!=""?adminer()->processInput($n,$W):"NULL");$L[$bg]=$x;}$Tj=where($Z,$o);if(!driver()->update($Q,$N," WHERE $Tj",0," ")){$Nl=false;break
2;}$ra+=connection()->affected_rows;$e=array();foreach($L
as$x)$e[]=idf_escape($x);$nn=driver()->select($Q,$e,array($Tj),$e);$Kh=($nn?$nn->fetch_row():array());$Xf=0;foreach($L
as$bg=>$x){$n=$o[$x];$Ll=array('type'=>(preg_match('~binary~',$n["type"])?'blob':$n["type"]));$jb["val[$Xl][$u][$bg]"]=select_value(idx($Kh,$Xf++),"",$Ll,null);}}}if($Wa&&$Nl)$Nl=driver()->commit();queries_redirect(null,lang_format(array('%d item has been affected.','%d items have been affected.'),$ra),$Nl);if($Wa&&!$Nl)driver()->rollback();page_headers();page_messages($m);foreach($jb
as$B=>$W)echo"<div data-name='".h($B)."' hidden>$W</div>\n";exit;}restart_session();$bf=&get_session("queries");$af=&$bf[DB];if(!$m&&$_POST["clear"]){$af=array();redirect(remove_from_uri("history"));}stop_session();$pa=get_settings("adminer_import");if($_POST&&$pa)save_settings($pa,"adminer_import");page_header((isset($_GET["import"])?'Import':'SQL command'),$m);$zg=driver()->lineComment();if(!$m&&$_POST&&!(isset($_GET["import"])&&adminer()->importProcess())){$Ac=driver()->delimiter;$ne=false;if(!isset($_GET["import"]))$F=$_POST["query"];elseif($_POST["webfile"]){$_l=adminer()->importServerPath();$ne=@fopen((file_exists($_l)?$_l:"compress.zlib://$_l.gz"),"rb");$F=($ne?fread($ne,1e6):false);}else$F=get_file("sql_file",true,$Ac);if(is_string($F)){if(($Zg=ini_bytes("memory_limit"))!="-1")ini_set("memory_limit",max($Zg,strval(2*strlen($F)+memory_get_usage()+8e6)));if($F!=""&&strlen($F)<1e6){$Qj=$F.(preg_match("~$Ac\\s*\$~",$F)?"":$Ac);if(!$af||first(end($af))!=$Qj){restart_session();$af[]=array($Qj,time());set_session("queries",$bf);stop_session();}}$xl="(?:\\s|\xEF\xBB\xBF|/\\*[\s\S]*?\\*/|(?:$zg)[^\n]*\n?|--\r?\n)";$ci=0;$ld=true;$bc=false;$h=connect();if($h&&DB!=""){$h->select_db(DB);if($_GET["ns"]!="")set_schema($_GET["ns"],$h);}$Gb=0;$td=array();$Ui='[\'"'.(JUSH=="sql"?'`':(JUSH=="sqlite"?'`[':(JUSH=="mssql"?'[':''))).']|/\*|'.$zg.'|$'.(JUSH=="pgsql"?'|\$([a-zA-Z]\w*)?\$':'');$Gm=microtime(true);while($F!=""){if(!$ci&&preg_match("~^$xl*+DELIMITER\\s+(\\S+)~i",$F,$A)){$Ac=preg_quote($A[1]);$F=substr($F,strlen($A[0]));}elseif(!$ci&&JUSH=='pgsql'&&preg_match("~^($xl*+COPY\\s+)[^;]+\\s+FROM\\s+stdin;~i",$F,$A)){$Ac="\n\\\\\\.\r?\n";$bc=true;$ci=strlen($A[0]);}else{preg_match("($Ac\\s*|$Ui)",$F,$A,PREG_OFFSET_CAPTURE,$ci);list($le,$E)=$A[0];if(!$le&&$ne&&!feof($ne))$F
.=fread($ne,1e5);else{if(!$le&&rtrim($F)=="")break;$ci=$E+strlen($le);if($le&&!preg_match("(^$Ac)",$le)){$gb=driver()->hasCStyleEscapes()||(JUSH=="pgsql"&&($E>0&&strtolower($F[$E-1])=="e"));$kj=($le=='/*'?'\*/':($le=='['?']':(preg_match("~^(?:$zg)~",$le)?"\n":preg_quote($le).($gb?'|\\\\.':''))));while(preg_match("($kj|\$)s",$F,$A,PREG_OFFSET_CAPTURE,$ci)){$Dk=$A[0][0];if(!$Dk&&$ne&&!feof($ne))$F
.=fread($ne,1e5);else{$ci=$A[0][1]+strlen($Dk);if(!$Dk||$Dk[0]!="\\")break;}}}else{$Qj=substr($F,0,$E+($bc?3:0));$F=substr($F,$ci);$ci=0;if($bc){$Ac=driver()->delimiter;$bc=false;}$zb="<code class='jush-".JUSH."'>".adminer()->sqlCommandQuery($Qj)."</code>";if(preg_match("~^$xl*+\$~",$Qj)&&!preg_match('~/\*M?!~',$Qj)){echo($_POST["only_errors"]?"":"<pre>$zb</pre>\n");continue;}$ld=false;$Gb++;$Hj="<pre id='sql-$Gb'>$zb</pre>\n";if(JUSH=="sqlite"&&preg_match("~^$xl*+(ATTACH|VACUUM\\b.*\\bINTO)\\b~is",$Qj,$A)!==0){echo$Hj,"<p class='error'>".sprintf('%s queries are not supported.',preg_match('~ATTACH~i',$A[1])?'ATTACH':'VACUUM INTO')."\n";$td[]=" <a href='#sql-$Gb'>$Gb</a>";if($_POST["error_stops"])break;}else{if(!$_POST["only_errors"]){echo$Hj;ob_flush();flush();}$Fl=microtime(true);if(connection()->multi_query($Qj)&&$h&&preg_match("~^$xl*+USE\\b~i",$Qj))$h->query($Qj);do{$G=connection()->store_result();if(connection()->error){echo($_POST["only_errors"]?$Hj:""),"<p class='error'>".'Error in query'.(connection()->errno?" (".connection()->errno.")":"").": ".adminer()->error()."\n";$td[]=" <a href='#sql-$Gb'>$Gb</a>";if($_POST["error_stops"])break
2;}else{$_=ME."sql=".url_escape(trim($Qj));$wm=" <span class='time'>(".format_time($Fl).")</span>".(strlen($_)<adminer()->sqlUrlLength()-100?" <a href='".h($_)."'>".'Edit'."</a>":"");$ra=connection()->affected_rows;$Mn=($_POST["only_errors"]?"":driver()->warnings());$Nn="warnings-$Gb";if($Mn)$wm
.=", <a href='#$Nn' class='toggle'>".'Warnings'."</a>";$Dd="";$Ed="explain-$Gb";if(is_object($G)){$z=$_POST["limit"];$Th=$z;$cd=!$_POST["only_errors"];if($cd)echo"<form action='' method='post'>\n";$Bi=print_select_result($G,$h,array(),$Th,$cd);if(!$_POST["only_errors"]){$Th=max($G->num_rows,$Th);echo"<p class='sql-footer'>".($Th?($z&&$Th>$z?sprintf('%d / ',$z):"").lang_format(array('%d row','%d rows'),$Th):""),$wm;if($h&&preg_match("~^($xl|\\()*+SELECT\\b~i",$Qj)&&($Dd=adminer()->explain($h,$Qj,$Bi))!="")echo", <a href='#$Ed' class='toggle'>Explain</a>";if($cd)echo", <input type='submit' name='save' value='".'Save'."' class='jsonly' disabled"." title='".'Ctrl+click on a value to modify it.'."'".on('click','sqlSave','Saving…').">";$t="export-$Gb";echo", <a href='#$t' class='toggle'>".'Export'."</a><span id='$t' class='hidden'>: ".html_select("output",adminer()->dumpOutput(),$pa["output"])." ".html_select("format",adminer()->dumpFormat(),$pa["format"]).input_hidden("query",$Qj)."<input type='submit' name='export' value='".'Export'."'".($z?"":on('click','sqlExport')).">".input_token()."</span>\n"."</form>\n";}}else{if(preg_match("~^$xl*+(CREATE|DROP|ALTER)$xl++(DATABASE|SCHEMA)\\b~i",$Qj)){restart_session();set_session("dbs",null);stop_session();}if(!$_POST["only_errors"])echo"<p class='message' title='".h(connection()->info)."'>".lang_format(array('Query executed OK, %d row affected.','Query executed OK, %d rows affected.'),$ra)."$wm\n";}echo($Mn?"<div id='$Nn' class='hidden'>\n$Mn</div>\n":""),($Dd!=""?"<div id='$Ed' class='hidden explain'>\n$Dd</div>\n":"");}$Fl=microtime(true);}while(connection()->next_result());}}}}}if($ld)echo"<p class='message'>".'No commands to execute.'."\n";else{$qf=connection()->inTransaction();driver()->rollback();if($qf)echo"<pre><code class='jush-".JUSH."'>ROLLBACK".(JUSH=="mssql"?" TRANSACTION":"")." -- Adminer</code></pre>\n";if($_POST["only_errors"])echo"<p class='message'>".lang_format(array('%d query executed OK.','%d queries executed OK.'),$Gb-count($td))," <span class='time'>(".format_time($Gm).")</span>\n";elseif($td&&$Gb>1)echo"<p class='error'>".'Error in query'.": ".implode("",$td)."\n";}}else
echo"<p class='error'>".upload_error($F)."\n";}echo'
<form action="" method="post" enctype="multipart/form-data" id="form"';$on="";if(!isset($_GET["import"]))echo
on('submit','sqlSubmit',remove_from_uri("sql|limit|error_stops|only_errors|history"),adminer()->sqlUrlLength());else
echo
on_upload_progress($on);echo'>
';$Ad="<input type='submit' value='".'Execute'."' title='Ctrl+Enter'>";if(!isset($_GET["import"])){$Qj=$_GET["sql"];if($_POST)$Qj=$_POST["query"];elseif($_GET["history"]=="all")$Qj=$af;elseif($_GET["history"]!="")$Qj=idx($af[$_GET["history"]],0);echo"<p>";textarea("query",$Qj,20);echo($_POST?"":script("qs('textarea').focus();")),"<p>";adminer()->sqlPrintAfter();echo"$Ad\n",'Limit rows'.": <input type='number' name='limit' class='size' value='".h($_POST?$_POST["limit"]:$_GET["limit"])."'>\n";}else{$Ie=(extension_loaded("zlib")?"[.gz]":"");echo"<fieldset><legend>".'File upload'."</legend><div>",($on?input_hidden(ini_get("session.upload_progress.name"),$on):""),"SQL$Ie: ".file_input(" name='sql_file[]' multiple","\n$Ad"),($on?" <progress class='jsonly hidden' max='1' value='0'></progress>":""),"</div></fieldset>\n";$nf=adminer()->importServerPath();if($nf)echo"<fieldset><legend>".'From server'."</legend><div>",sprintf('Webserver file %s',"<code>".h($nf)."$Ie</code>")," <input type='submit' name='webfile' value='".'Run file'."'>","</div></fieldset>\n";adminer()->importPrint();echo"<p>";}echo
checkbox("error_stops",1,($_POST?$_POST["error_stops"]:isset($_GET["import"])||$_GET["error_stops"]),'Stop on error')."\n",checkbox("only_errors",1,($_POST?$_POST["only_errors"]:isset($_GET["import"])||$_GET["only_errors"]),'Show only errors')."\n",input_token();if(!isset($_GET["import"])&&$af){print_fieldset("history",'History',$_GET["history"]!="");for($W=end($af);$W;$W=prev($af)){$x=key($af);list($Qj,$wm,$hd)=$W;echo'<div><a href="'.h(ME."sql=&history=$x").'" class="hover">'.'Edit'."</a>"." <span class='time' title='".@date('Y-m-d',$wm)."'>".@date("H:i:s",$wm)."</span>"." <code class='jush-".JUSH."'>".shorten_utf8(preg_replace('~\s+~',' ',ltrim(preg_replace("~^(?:$zg).*~m",'',$Qj))),80,"</code>").($hd?" <span class='time'>($hd)</span>":"")."</div>\n";}echo"<input type='submit' name='clear' value='".'Clear'."'>\n","<a href='".h(ME."sql=&history=all")."'>".'Edit all'."</a>\n","</div></fieldset>\n";}echo'</form>
';}elseif(isset($_GET["edit"])){$a=$_GET["edit"];$o=fields($a);$Z=(isset($_GET["select"])?($_POST["check"]&&count($_POST["check"])==1?where_check($_POST["check"][0],$o):""):where($_GET,$o));$ln=(isset($_GET["select"])?$_POST["edit"]:$Z);foreach($o
as$B=>$n){if((!$ln&&!isset($n["privileges"]["insert"]))||adminer()->fieldName($n)=="")unset($o[$B]);}if($_POST&&!$m&&!isset($_GET["select"])){$Dg=relative_uri((string)$_POST["referer"]);if($_POST["insert"])$Dg=($ln?null:relative_uri());elseif(!preg_match('~^.+&select=.+$~',$Dg))$Dg=ME."select=".url_escape($a);$w=indexes($a);$en=unique_array($_GET["where"],$w);$Tj="\nWHERE $Z";if(isset($_POST["delete"]))queries_redirect($Dg,'Item has been deleted.',driver()->delete($a,$Tj,$en?0:1));else{$N=array();foreach($o
as$B=>$n){$W=process_input($n);if($W!==false&&$W!==null)$N[idf_escape($B)]=$W;}if($ln){if(!$N)redirect($Dg);queries_redirect($Dg,'Item has been updated.',driver()->update($a,$N,$Tj,$en?0:1));if(is_ajax()){page_headers();page_messages($m);exit;}}else{$G=driver()->insert($a,$N);$og=($G?last_id($G):0);queries_redirect($Dg,sprintf('Item%s has been inserted.',($og?" $og":"")),$G);}}}$I=null;$F="";$wm="";if($Z){$L=array();$Pk=array("*");foreach($o
as$B=>$n){if(isset($n["privileges"]["select"])){$Fa=($_POST["clone"]&&$n["auto_increment"]?"''":convert_field($n));$d=($Fa?"$Fa AS ":"").idf_escape($B);$L[]=$d;if($Fa)$Pk[]=$d;}}$I=array();if(!support("table")){$L=array("*");$Pk=$L;}if($L){$Fl=microtime(true);$G=driver()->select($a,$L,array($Z),$L,array(),(isset($_GET["select"])?2:1));$F=str_replace("SELECT ".implode(", ",$L),"SELECT ".implode(", ",$Pk),driver()->query);$wm=format_time($Fl);if(!$G)$m=adminer()->error();else{$I=$G->fetch_assoc();if(!$I)$I=false;}if(isset($_GET["select"])&&(!$I||$G->fetch_assoc()))$I=null;}}if(!$o&&driver()->primary!=""){if(!$Z){$G=driver()->select($a,array("*"),array(),array("*"));$I=($G?$G->fetch_assoc():false);if(!$I)$I=array(driver()->primary=>"");}if($I){foreach($I
as$x=>$W){if(!$Z)$I[$x]=null;$o[$x]=array("field"=>$x,"null"=>($x!=driver()->primary),"auto_increment"=>($x==driver()->primary));}}}if($_POST["save"]){$wj=array();foreach((array)$_POST["fields"]as$x=>$W)$wj[bracket_escape($x,true)]=$W;$I=$wj+($I?$I:array());}edit_form($a,$o,$I,$ln,$m,$F,$wm);}elseif(isset($_GET["create"])){function
referencable_primary($Sk){$H=array();foreach(table_status('',true)as$bm=>$Q){if($bm!=$Sk&&!$Q["dependent"]&&fk_support($Q)){foreach(fields($bm)as$n){if($n["primary"]){if($H[$bm]){unset($H[$bm]);break;}$H[$bm]=$n;}}}}return$H;}$a=$_GET["create"];$Zi=driver()->partitionBy;$dj=($Zi&&$a!=""?driver()->partitionsInfo($a):array());$bk=referencable_primary($a);$je=array();foreach($bk
as$bm=>$n)$je[str_replace("`","``",$bm)."`".str_replace("`","``",$n["field"])]=$bm;$Fi=array();$R=array();$Qh=false;if($a!=""){$Fi=fields($a);$R=table_status1($a);$Qh=(count($R)<2);}$za=($a==""||driver()->supportsAlterTable($R));$I=$_POST;$I["fields"]=(array)$I["fields"];if($I["auto_increment_col"])$I["fields"][$I["auto_increment_col"]]["auto_increment"]=true;if($_POST&&!$m)save_settings(array("comments"=>$_POST["comments"],"defaults"=>$_POST["defaults"]));if($_POST&&!process_fields($I["fields"])&&!$m){if($_POST["drop"])queries_redirect(substr(ME,0,-1),'Table has been dropped.',drop_tables(array($a)));else{$o=array();$xa=array();$sn=false;$he=array();$Ei=reset($Fi);$ta=" FIRST";foreach($I["fields"]as$n){$q=$je[$n["type"]];$Vm=($q!==null?$bk[$q]:$n);if($n["field"]!=""){if(!$n["generated"])$n["default"]=null;$Mj=process_field($n,$Vm);$xa[]=array($n["orig"],$Mj,$ta);if(!$Ei||$Mj!==process_field($Ei,$Ei)){$o[]=array($n["orig"],$Mj,$ta);if($n["orig"]!=""||$ta)$sn=true;}if($q!==null)$he[idf_escape($n["field"])]=($a!=""&&JUSH!="sqlite"?"ADD":" ").format_foreign_key(array('table'=>$je[$n["type"]],'source'=>array($n["field"]),'target'=>array($Vm["field"]),'on_delete'=>$n["on_delete"],),object_name("FOREIGN",trim($I["name"]),array($n["field"])));$ta=" AFTER ".idf_escape($n["field"]);}elseif($n["orig"]!=""){$sn=true;$o[]=array($n["orig"]);}if($n["orig"]!=""){$Ei=next($Fi);if(!$Ei)$ta="";}}$bj=array();if(in_array($I["partition_by"],$Zi)){foreach($I
as$x=>$W){if(preg_match('~^partition~',$x))$bj[$x]=$W;}foreach($bj["partition_names"]as$x=>$B){if($B==""){unset($bj["partition_names"][$x]);unset($bj["partition_values"][$x]);}}$bj["partition_names"]=array_values($bj["partition_names"]);$bj["partition_values"]=array_values($bj["partition_values"]);if($bj==$dj)$bj=array();}elseif(preg_match("~partitioned~",$R["Create_options"]))$bj=null;$bh='Table has been altered.';if($a==""){cookie("adminer_engine",$I["Engine"]);$bh='Table has been created.';}$B=trim($I["name"]);$Dg=ME.(support("table")?"table=":"select=").url_escape($B);$G=alter_table($a,$B,(JUSH=="sqlite"&&($sn||$he)?$xa:$o),$he,($I["Comment"]!=$R["Comment"]?$I["Comment"]:null),($I["Engine"]&&$I["Engine"]!=$R["Engine"]?$I["Engine"]:""),($I["Collation"]&&$I["Collation"]!=$R["Collation"]?$I["Collation"]:""),($I["Auto_increment"]!=""?number($I["Auto_increment"]):""),$bj);if($G&&!Queries::$queries&&$a!=""&&!$o&&!$he)redirect($Dg);queries_redirect($Dg,$bh,$G);}}$Gl=($a!=""?"alter":"create");page_header(($a!=""?'Alter table':'Create table'),$m,array("table"=>$a),h($a),$Qh,doc_link(array('sql'=>"$Gl-table.html",'mariadb'=>($a!=""?"$Gl-table":""),'pgsql'=>"sql-$Gl"."table.html",'cockroach'=>"$Gl-table",'mssql'=>"t-sql/statements/$Gl-table-transact-sql",'sqlite'=>"lang_{$Gl}table.html",'oracle'=>"sqlrf/".strtoupper($Gl)."-TABLE.html",)));if(!$_POST){$Zm=driver()->types();$I=array("Engine"=>$_COOKIE["adminer_engine"],"fields"=>array(array("field"=>"","type"=>(isset($Zm["int"])?"int":(isset($Zm["integer"])?"integer":"")),"on_update"=>"")),"partition_names"=>array(""),);if($a!=""){$I=$R;$I["name"]=$a;$I["fields"]=array();if(!$_GET["auto_increment"])$I["Auto_increment"]="";foreach($Fi
as$n){if($n["generated"])$n["default"]=ltrim($n["default"]);$n["generated"]=$n["generated"]?:(isset($n["default"])?"DEFAULT":"");$I["fields"][]=$n;}if($Zi){$I+=$dj;$I["partition_names"][]="";$I["partition_values"][]="";}}}$Cb=flat_collations();$od=driver()->engines();foreach($od
as$nd){if(!strcasecmp($nd,$I["Engine"])){$I["Engine"]=$nd;break;}}$Mg=max_input_vars(12,20);if($Mg){$Xe=(count($I["fields"])>$Mg?"":" hidden");echo"<p".($Xe?" id='max-fields' data-columns='$Mg'":"")." class='error$Xe'>".max_input_vars_error()."\n";}echo'
<form action="" method="post" id="form">
<p>
';if(support("columns")||$a==""){echo'Table name'.": <input name='name'".($a==""&&!$_POST?" autofocus":"")." data-maxlength='64' value='".h($I["name"])."' autocapitalize='off'>\n",(!$za?h($R["Engine"])."\n":($od?html_select("Engine",array(""=>"(".'engine'.")")+$od,$I["Engine"],on('change','helpClose').on_help_value())."\n":""));if($Cb)echo"<datalist id='collations'>".optionlist($Cb)."</datalist>\n",(preg_match("~sqlite|mssql~",JUSH)?"":"<input list='collations' name='Collation' value='".h($I["Collation"])."' placeholder='(".'collation'.")'>\n");echo"<input type='submit' value='".'Save'."'>\n";}if(support("columns")&&$za){echo"<div class='scrollable'>\n","<table id='edit-fields' class='nowrap'>\n";edit_fields($I["fields"],$Cb,"TABLE",$je);echo"</table>\n",script("editFields();"),"</div>\n<p>\n",'Auto Increment'.": <input type='number' name='Auto_increment' class='size' value='".h($I["Auto_increment"])."'>\n",checkbox("defaults",1,($_POST?$_POST["defaults"]:get_setting("defaults")),'Default values',on('click','columnShowClick',6),"jsonly");$Jb=($_POST?$_POST["comments"]:get_setting("comments"));if(support("comment")){echo
checkbox("comments",1,$Jb,'Comment',on('click','editingCommentsClick',true),"jsonly").' ';$c=" name='Comment' data-maxlength='".(min_version(5.5)?2048:60)."'".($Jb?"":" class='hidden'");echo
adminer()->commentInput('TABLE',$c,$I["Comment"]);}echo'<p>
<input type=\'submit\' value=\'Save\'>
';}echo'
';if($a!="")echo'<input type=\'submit\' name=\'drop\' value=\'Drop\'',confirm(sprintf('Drop %s?',$a)),'>
';if($Zi&&(JUSH=='sql'||$a=="")){$aj=preg_match('~RANGE|LIST~',$I["partition_by"]);print_fieldset("partition",'Partition by',$I["partition_by"]);echo"<p>".html_select("partition_by",array_merge(array(""),$Zi),$I["partition_by"],on('change','partitionByChange').on_help_value('.','PARTITION BY $&'))."\n","(<input name='partition' value='".h($I["partition"])."'>)\n",'Partitions'.": <input type='number' name='partitions' class='size".($aj||!$I["partition_by"]?" hidden":"")."' value='".h($I["partitions"])."'>\n","<table id='partition-table'".($aj?"":" class='hidden'").">\n","<thead><tr><th>".'Partition name'."<th>".'Values'."<tbody>\n";foreach($I["partition_names"]as$x=>$W)echo'<tr>','<td><input name="partition_names[]" value="'.h($W).'" autocapitalize="off"'.($x==count($I["partition_names"])-1?on('input','partitionNameChange'):'').'>','<td><input name="partition_values[]" value="'.h(idx($I["partition_values"],$x)).'">';echo"</table>\n</div></fieldset>\n";}echo
input_token(),'</form>
';}elseif(isset($_GET["indexes"])){$a=$_GET["indexes"];$wf=array("PRIMARY","UNIQUE","INDEX");$R=table_status1($a,true);$tf=driver()->indexAlgorithms($R);if(preg_match('~MyISAM|M?aria'.(min_version(5.6,'10.0.5')?'|InnoDB':'').'~i',$R["Engine"]))$wf[]="FULLTEXT";if(preg_match('~MyISAM|M?aria'.(min_version(5.7,'10.2.2')?'|InnoDB':'').'~i',$R["Engine"]))$wf[]="SPATIAL";if(min_version('',11.7)&&preg_match('~MyISAM|InnoDB~i',$R["Engine"]))$wf[]="VECTOR";$w=indexes($a);$o=fields($a);$Fj=array();if(JUSH=="mongo"){$Fj=$w["_id_"];unset($wf[0]);unset($w["_id_"]);}$I=$_POST;if($I)save_settings(array("index_options"=>$I["options"]));if($_POST&&!$m&&!$_POST["add"]&&!$_POST["drop_col"]){$b=array();foreach($I["indexes"]as$v){$B=$v["name"];if(in_array($v["type"],$wf)){$e=array();$vg=array();$Dc=array();$qi=array();$uf=(support("partial_indexes")?$v["partial"]:"");$sf=(in_array($v["algorithm"],$tf)?$v["algorithm"]:"");$N=array();ksort($v["columns"]);foreach($v["columns"]as$x=>$d){if($d!=""){$y=idx($v["lengths"],$x);$Bc=idx($v["descs"],$x);$pi=idx($v["opclasses"],$x);$N[]=($o[$d]?idf_escape($d):$d).($y?"(".(+$y).")":"").($pi!=""?" ".idf_escape($pi):"").($Bc?" DESC":"");$e[]=$d;$vg[]=($y?:null);$Dc[]=$Bc;$qi[]="$pi";}}$Bd=$w[$B];if($Bd){ksort($Bd["columns"]);ksort($Bd["lengths"]);ksort($Bd["descs"]);if($v["type"]==$Bd["type"]&&array_values($Bd["columns"])===$e&&(!$Bd["lengths"]||array_values($Bd["lengths"])===$vg)&&array_values($Bd["descs"])===$Dc&&(!$Bd["opclasses"]||array_values($Bd["opclasses"])===$qi)&&$Bd["partial"]==$uf&&(!$tf||$Bd["algorithm"]==$sf)){unset($w[$B]);continue;}}if($e)$b[]=array($v["type"],$B,$N,$sf,$uf);}}foreach($w
as$B=>$Bd)$b[]=array($Bd["type"],$B,"DROP");if(!$b)redirect(ME."table=".url_escape($a));queries_redirect(ME."table=".url_escape($a),'Indexes have been altered.',alter_indexes($a,$b));}page_header('Indexes',$m,array("table"=>$a),h($a),false,doc_link(array('sql'=>"create-index.html",'pgsql'=>"sql-createindex.html",'cockroach'=>"create-index",'mssql'=>"t-sql/statements/create-index-transact-sql",'sqlite'=>"lang_createindex.html",'oracle'=>"sqlrf/CREATE-INDEX.html",)));$Sd=array_keys($o);if($_POST["add"]){foreach($I["indexes"]as$x=>$v){if($v["columns"][count($v["columns"])]!="")$I["indexes"][$x]["columns"][]="";}$v=end($I["indexes"]);if($v["type"]||array_filter($v["columns"],'strlen'))$I["indexes"][]=array("columns"=>array(1=>""));}if(!$I){foreach($w
as$x=>$v){$w[$x]["name"]=$x;$w[$x]["columns"][]="";}$w[]=array("columns"=>array(1=>""));$I["indexes"]=$w;}$vg=(JUSH=="sql"||JUSH=="mssql");$qi=driver()->indexOpclasses();$ml=($_POST?$_POST["options"]:get_setting("index_options"));$zh=array();foreach($wf
as$T)$zh[$T]=str_replace("{table}",$a,adminer()->namePattern($T));echo'
<form action="" method="post">
<div class="scrollable">
<table class="nowrap odds">
<thead><tr>
<th id="label-type">Index Type
';$lf=" class='idxopts".($ml?"":" hidden")."'";if($tf)echo"<th id='label-algorithm'$lf>".'Algorithm'.doc_link(array('sql'=>'create-index.html#create-index-storage-engine-index-types','mariadb'=>'storage-engine-index-types/','pgsql'=>'indexes-types.html','cockroach'=>'create-index#parameters',));echo'<th><input type="submit" hidden>','Columns'.($vg?"<span$lf> (".'length'.")</span>":"");if($vg||support("descidx"))echo
checkbox("options",1,$ml,'Options',on('click','indexOptionsShow'),"jsonly")."\n";echo'<th id="label-name">Name
';if(support("partial_indexes"))echo"<th id='label-condition'$lf>".'Condition';echo'<td><noscript>',icon("plus","add[0]","+",'Add next'),'</noscript>
<tbody>
';if($Fj){echo"<tr><td>PRIMARY<td>";foreach($Fj["columns"]as$x=>$d)echo
select_input(" disabled",array_combine($Sd,$Sd),$d),"<label><input disabled type='checkbox'>".'descending'."</label> ";echo"<td><td>\n";}$Xf=1;foreach($I["indexes"]as$v){if(!$_POST["drop_col"]||$Xf!=key($_POST["drop_col"])){echo"<tr><td>".html_select("indexes[$Xf][type]",array(-1=>"")+$wf,$v["type"],on('change','indexesChangeType',$zh),"label-type");if($tf)echo"<td$lf>".html_select("indexes[$Xf][algorithm]",array_merge(array(""),$tf),$v['algorithm'],"","label-algorithm");echo"<td>";ksort($v["columns"]);$s=1;foreach($v["columns"]as$x=>$d){echo"<span>".select_input(" name='indexes[$Xf][columns][$s]' title='".'Column'."'".on('change','indexesChangeColumn',$zh),($o&&($d==""||$o[$d])?array_combine($Sd,$Sd):array()),$d)," <span$lf>",($vg?"<input type='number' name='indexes[$Xf][lengths][$s]' class='size' value='".h(idx($v["lengths"],$x))."' title='".'Length'."'>":"");if($qi){$pi=idx($v["opclasses"],$x);echo
html_select("indexes[$Xf][opclasses][$s]",array(""=>"(".'operator class'.")")+array_combine($qi,$qi)+($pi!=""?array($pi=>$pi):array()),$pi),doc_link(array('pgsql'=>'indexes-opclass.html'));}echo(support("descidx")?checkbox("indexes[$Xf][descs][$s]",1,idx($v["descs"],$x),'descending'):""),"<br>","</span></span>";$s++;}echo"<td><input name='indexes[$Xf][name]' value='".h($v["name"])."' autocapitalize='off' aria-labelledby='label-name'>\n";if(support("partial_indexes"))echo"<td$lf><input name='indexes[$Xf][partial]' value='".h($v["partial"])."' autocapitalize='off' aria-labelledby='label-condition'>\n";echo"<td>".icon("cross","drop_col[$Xf]","x",'Remove',on('click','editingRemoveRow','indexes$1[type]'));}$Xf++;}echo'</table>
</div>
<p>
<input type=\'submit\' value=\'Save\'>
',input_token(),'</form>
';}elseif(isset($_GET["database"])){$I=$_POST;if($_POST&&!$m&&!$_POST["add"]){$B=trim($I["name"]);if($_POST["drop"]){$_GET["db"]="";queries_redirect(remove_from_uri("db|database"),'Database has been dropped.',drop_databases(array(DB)));}elseif($B!==DB){if(DB!=""){$_GET["db"]=$B;queries_redirect(preg_replace('~\bdb=[^&]*&~','',ME)."db=".url_escape($B),'Database has been renamed.',rename_database($B,(string)$I["collation"]));}else{$j=explode("\n",str_replace("\r","",$B));$Nl=true;$mg="";foreach($j
as$k){if(count($j)==1||$k!=""){if(!create_database($k,(string)$I["collation"]))$Nl=false;$mg=$k;}}restart_session();set_session("dbs",null);queries_redirect(preg_replace('~&db=[^&]*~','',ME)."db=".url_escape($mg),'Database has been created.',$Nl);}}else{if(!$I["collation"])redirect(substr(ME,0,-1));query_redirect("ALTER DATABASE ".idf_escape($B).(preg_match('~^[a-z0-9_]+$~i',$I["collation"])?" COLLATE $I[collation]":""),substr(ME,0,-1),'Database has been altered.');}}$Gl=(DB!=""?"alter":"create");page_header(DB!=""?'Alter database':'Create database',$m,array(),h(DB),false,doc_link(array('sql'=>"$Gl-database.html",'mariadb'=>(DB!=""?"":"$Gl-database"),'pgsql'=>"sql-$Gl"."database.html",'cockroach'=>"$Gl-database",'mssql'=>"t-sql/statements/$Gl-database-transact-sql",'oracle'=>"sqlrf/".strtoupper($Gl)."-USER.html",)));$Cb=collations();$B=DB;if($_POST)$B=$I["name"];elseif(DB!="")$I["collation"]=db_collation(DB,$Cb);elseif(JUSH=="sql"){foreach(get_vals("SHOW GRANTS")as$_e){if(preg_match('~ ON (`(([^\\\\`]|``|\\\\.)*)%`\.\*)?~',$_e,$A)&&$A[1]){$B=stripcslashes(idf_unescape("`$A[2]`"));break;}}}echo'
<form action="" method="post">
<p>
',($_POST["add"]||strpos($B,"\n")?'<textarea autofocus name="name" rows="10" cols="40">'.h($B).'</textarea><br>':'<input name="name" autofocus value="'.h($B).'" data-maxlength="64" autocapitalize="off">')."\n",($Cb?html_select("collation",array(""=>"(".'collation'.")")+$Cb,$I["collation"]).doc_link(array('sql'=>"charset-charsets.html",'mariadb'=>"supported-character-sets-and-collations/",'mssql'=>"relational-databases/system-functions/sys-fn-helpcollations-transact-sql",)):"")."\n",'<input type=\'submit\' value=\'Save\'>
';if(DB!="")echo"<input type='submit' name='drop' value='".'Drop'."'".confirm(sprintf('Drop %s?',DB)).">\n";elseif(!$_POST["add"]&&$_GET["db"]=="")echo
icon("plus","add[0]","+",'Add next')."\n";echo
input_token(),'</form>
';}elseif(isset($_GET["scheme"])){$I=$_POST;$f=(support("comment")&&$_GET["ns"]!=""?schema_comment($_GET["ns"]):"");if($_POST&&!$m){$_=preg_replace('~&ns=[^&]*~','',ME)."ns=";if($_POST["drop"])query_redirect("DROP SCHEMA ".idf_escape($_GET["ns"]),$_,'Schema has been dropped.');else{$B=trim($I["name"]);$_
.=url_escape($B);$Fh=(support("comment")?$I["comment"]:$f);$G=true;$bh='Schema has been altered.';if($_GET["ns"]==""){$G=queries("CREATE SCHEMA ".idf_escape($B));$bh='Schema has been created.';}elseif($_GET["ns"]!=$B)$G=queries("ALTER SCHEMA ".idf_escape($_GET["ns"])." RENAME TO ".idf_escape($B));elseif($Fh===$f)redirect($_);if($G&&$Fh!==$f)$G=set_schema_comment($B,$Fh);queries_redirect($_,$bh,$G);}}$Gl=($_GET["ns"]!=""?"alter":"create");page_header($_GET["ns"]!=""?'Alter schema':'Create schema',$m,array(),"",false,doc_link(array('pgsql'=>"sql-$Gl"."schema.html",'cockroach'=>"$Gl-schema",'mssql'=>"t-sql/statements/create-schema-transact-sql",)));if(!$I){$I["name"]=$_GET["ns"];$I["comment"]=$f;}echo'
<form action="" method="post">
<p><input name="name" autofocus value="',h($I["name"]),'" autocapitalize="off">
<input type=\'submit\' value=\'Save\'>
';if($_GET["ns"]!="")echo"<input type='submit' name='drop' value='".'Drop'."'".confirm(sprintf('Drop %s?',$_GET["ns"])).">\n";echo(support("comment")?"<p>".'Comment'.": ".adminer()->commentInput('SCHEMA'," name='comment'",$I["comment"])."\n":""),input_token(),'</form>
';}elseif(isset($_GET["call"])){$ba=($_GET["name"]?:$_GET["call"]);$_k=(isset($_GET["callf"])?"FUNCTION":"PROCEDURE");$vk=routine($_GET["call"],$_k);$Uf=(idx($vk["returns"],"type")=="trigger");page_header(($Uf?'Triggers':'Call').": ".h($ba),$m,"#routines","",!$vk,(isset($_GET["callf"])?"":doc_link(array('sql'=>"call.html",'pgsql'=>"sql-call.html",'cockroach'=>"call",'mssql'=>"t-sql/language-elements/execute-transact-sql",))));$of=array();$Li=array();foreach($vk["fields"]as$s=>$n){if(substr($n["inout"],-3)=="OUT"&&JUSH=='sql')$Li[$s]="@".idf_escape($n["field"])." AS ".idf_escape($n["field"]);if(!$n["inout"]||preg_match('~^(IN|OUTPUT)~',$n["inout"]))$of[]=$s;}if(!$m&&$_POST){$hb=array();foreach($vk["fields"]as$x=>$n){$W="";if(in_array($x,$of)){$W=process_input($n);if($W===false)$W="''";if(isset($Li[$x]))connection()->query("SET @".idf_escape($n["field"])." = $W");}if(isset($Li[$x]))$hb[]="@".idf_escape($n["field"]);elseif(in_array($x,$of))$hb[]=$W;}$Da=implode(", ",$hb);$F=(isset($_GET["callf"])||JUSH!="mssql"?(isset($_GET["callf"])?"SELECT ":"CALL ").(idx($vk["returns"],"type")=="record"?"* FROM ":"").table($ba)."($Da)":"EXEC ".table($ba).($Da!=""?" $Da":""));$Fl=microtime(true);$G=connection()->multi_query($F);$ra=connection()->affected_rows;echo
adminer()->selectQuery($F,$Fl,!$G);if(!$G)echo"<p class='error'>".adminer()->error()."\n";else{$h=connect();if($h)$h->select_db(DB);do{$G=connection()->store_result();if(is_object($G))print_select_result($G,$h);else
echo"<p class='message'>".lang_format(array('Routine has been called, %d row affected.','Routine has been called, %d rows affected.'),$ra)." <span class='time'>".@date("H:i:s")."</span>\n";}while(connection()->next_result());if($Li)print_select_result(connection()->query("SELECT ".implode(", ",$Li)));}}if($Uf){$Sm=driver()->routineTriggers($_GET["call"]);if($Sm){echo"<table>\n";foreach($Sm
as$I){$_=preg_replace('~&ns=[^&]*~',"&ns=".url_escape($I["ns"]),ME);echo"<tr><th>".h($I["trigger"])."<td><a href='".h($_."table=".url_escape($I["table"])."#triggers")."'>".($I["ns"]!=$_GET["ns"]?"<b>".h($I["ns"])."</b>.":"").h($I["table"])."</a>"."<td class='hover'><a href='".h($_."trigger=".url_escape($I["table"])."&name=".url_escape($I["trigger"]))."'>".'Alter'."</a>\n";}echo"</table>\n";}}else{echo'<form action="" method="post">
';if($of){echo"<table class='layout'>\n";foreach($of
as$x){$n=$vk["fields"][$x];$B=$n["field"];echo"<tr><th>".adminer()->fieldName($n);$X=idx($_POST["fields"],$B);if($X!=""){if($n["type"]=="set")$X=implode(",",$X);}input($n,$X,idx($_POST["function"],$B,""));echo"\n";}echo"</table>\n";}echo'<p>
<input type=\'submit\' value=\'Call\'>
',input_token(),'</form>
';}echo'
',adminer()->commentValue($_k,$vk['comment']);}elseif(isset($_GET["foreign"])){$a=$_GET["foreign"];$B=$_GET["name"];$I=$_POST;if($_POST&&!$m&&!$_POST["add"]&&!$_POST["change"]&&!$_POST["change-js"]){if(!$_POST["drop"]){$I["source"]=array_filter($I["source"],'strlen');ksort($I["source"]);$nm=array();foreach($I["source"]as$x=>$W)$nm[$x]=$I["target"][$x];$I["target"]=$nm;}$Rb=object_name("FOREIGN",$a,$I["source"]);$Wa=false;if(JUSH=="sqlite")$G=recreate_table($a,$a,array(),array(),array(" $B"=>($I["drop"]?"":" ".format_foreign_key($I,$Rb))));else{$b="ALTER TABLE ".table($a);$Vc="$b DROP ".(JUSH=="sql"?"FOREIGN KEY ":"CONSTRAINT ").idf_escape($B?:'');if($I["drop"])$G=queries($Vc);else{$ka=" ADD".format_foreign_key($I,$Rb);if($B=="")$G=queries($b.$ka);elseif(JUSH=="pgsql"||(JUSH=="sql"&&$Rb!=$B))$G=queries("$Vc,$ka");else{$Wa=support("transaction_ddl")&&driver()->begin();$G=($Wa||idx(foreign_keys($a),$B)?queries($Vc):true)&&queries($b.$ka)&&(!$Wa||driver()->commit());}}}queries_redirect(ME."table=".url_escape($a),($I["drop"]?'Foreign key has been dropped.':($B!=""?'Foreign key has been altered.':'Foreign key has been created.')),$G);if($Wa)driver()->rollback();if(!$I["drop"])$m='Source and target columns must have the same data type, there must be an index on the target columns and the referenced data must exist.';}$Qh=false;if(!$_POST&&$B!=""){$je=foreign_keys($a);$I=idx($je,$B,array());$Qh=!$I;}page_header(($B!=""?'Alter foreign key':'Create foreign key'),$m,array("table"=>$a),h($B!=""?$B:$a),$Qh,doc_link(array('sql'=>"innodb-foreign-key-constraints.html",'mariadb'=>"foreign-keys/",'pgsql'=>"sql-createtable.html#SQL-CREATETABLE-PARMS-REFERENCES",'cockroach'=>"foreign-key",'mssql'=>"t-sql/statements/create-table-transact-sql",'oracle'=>"sqlrf/constraint.html",)));if($_POST){ksort($I["source"]);if($_POST["change"]||$_POST["change-js"])$I["target"]=array();else$I["source"][]="";}elseif($B!="")$I["source"][]="";else{$I["table"]=$a;$I["source"]=array("");}echo'
<form action="" method="post">
';$vl=array_keys(fields($a));if($I["db"]!="")connection()->select_db($I["db"]);if($I["ns"]!=""){$Gi=get_schema();set_schema($I["ns"]);}$ak=array_keys(array_filter(table_status('',true),function(array$R){return!$R["dependent"]&&fk_support($R);}));$nm=array_keys(fields(in_array($I["table"],$ak)?$I["table"]:reset($ak)));$c=on('change','foreignChange');echo"<p><label>".'Target table'.": ".html_select("table",$ak,$I["table"],$c)."</label>\n";if(support("scheme")){$Gk=array_filter(adminer()->schemas(),function($K){return!information_schema(DB,$K);});echo"<label>".'Schema'.": ".html_select("ns",$Gk,$I["ns"]!=""?$I["ns"]:$_GET["ns"],$c)."</label>";if($I["ns"]!="")set_schema($Gi);}elseif(JUSH!="sqlite"){$tc=array();foreach(adminer()->databases()as$k){if(!information_schema($k))$tc[]=$k;}echo"<label>".'DB'.": ".html_select("db",$tc,$I["db"]!=""?$I["db"]:$_GET["db"],$c)."</label>";}echo
input_hidden("change-js"),'<noscript><p><input type=\'submit\' name=\'change\' value=\'Change\'></noscript>
<table>
<thead><tr><th id="label-source">Source<th id="label-target">Target<tbody>
';$Xf=0;foreach($I["source"]as$x=>$W){echo"<tr>","<td>".html_select("source[".(+$x)."]",array(-1=>"")+$vl,$W,($Xf==count($I["source"])-1?on('change','foreignAddRow'):""),"label-source"),"<td>".html_select("target[".(+$x)."]",$nm,idx($I["target"],$x),"","label-target");$Xf++;}echo'</table>
<p>
<label>ON DELETE: ',html_select("on_delete",array(-1=>"")+explode("|",driver()->onActions),$I["on_delete"]),'</label>
<label>ON UPDATE: ',html_select("on_update",array(-1=>"")+explode("|",driver()->onActions),$I["on_update"]),'</label>
',(support("deferrable")?html_select("deferrable",array('NOT DEFERRABLE','DEFERRABLE','DEFERRABLE INITIALLY DEFERRED'),$I["deferrable"]):''),'<p>
<input type=\'submit\' value=\'Save\'>
<noscript><p><input type=\'submit\' name=\'add\' value=\'Add column\'></noscript>
';if($B!="")echo'<input type=\'submit\' name=\'drop\' value=\'Drop\'',confirm(sprintf('Drop %s?',$B)),'>
';echo
input_token(),'</form>
';}elseif(isset($_GET["view"])){$a=$_GET["view"];$I=$_POST;$Hi="VIEW";if(JUSH=="pgsql"&&$a!=""){$O=table_status1($a);$Hi=strtoupper($O["Engine"]);}if($_POST&&!$m){$B=trim($I["name"]);$Fa=" AS\n$I[select]";$Dg=ME."table=".url_escape($B);$bh='View has been altered.';$T=($_POST["materialized"]?"MATERIALIZED VIEW":"VIEW");if(!$_POST["drop"]&&$a==$B&&JUSH!="sqlite"&&$T=="VIEW"&&$Hi=="VIEW")query_redirect((JUSH=="mssql"?"ALTER":"CREATE OR REPLACE")." VIEW ".table($B).$Fa,$Dg,$bh);else{$rm="adminer_".uniqid();drop_create("DROP $Hi ".table($a),"CREATE $T ".table($B).$Fa,"DROP $T ".table($B),"CREATE $T ".table($rm).$Fa,"DROP $T ".table($rm),($_POST["drop"]?substr(ME,0,-1):$Dg),'View has been dropped.',$bh,'View has been created.',$a,$B);}}$Qh=false;if(!$_POST&&$a!=""){$I=view($a);$Qh=!$I["select"];$I["name"]=$a;$I["materialized"]=($Hi!="VIEW");if(!$m)$m=adminer()->error();}page_header(($a!=""?'Alter view':'Create view'),$m,array("table"=>$a),h($a),$Qh,doc_link(array('sql'=>"create-view.html",'pgsql'=>"sql-createview.html",'cockroach'=>"create-view",'mssql'=>"t-sql/statements/create-view-transact-sql",'sqlite'=>"lang_createview.html",'oracle'=>"sqlrf/CREATE-VIEW.html",)));echo'
<form action="" method="post">
<p>Name: <input name="name" value="',h($I["name"]),'" data-maxlength="64" autocapitalize="off">
',(support("materializedview")?" ".checkbox("materialized",1,$I["materialized"],'Materialized view'):""),'<p>';textarea("select",$I["select"]);echo'<p>
<input type=\'submit\' value=\'Save\'>
';if($a!="")echo'<input type=\'submit\' name=\'drop\' value=\'Drop\'',confirm(sprintf('Drop %s?',$a)),'>
';echo
input_token(),'</form>
';}elseif(isset($_GET["event"])){$aa=$_GET["event"];$Jf=array("YEAR","QUARTER","MONTH","DAY","HOUR","MINUTE","WEEK","SECOND","YEAR_MONTH","DAY_HOUR","DAY_MINUTE","DAY_SECOND","HOUR_MINUTE","HOUR_SECOND","MINUTE_SECOND");$Il=array("ENABLED"=>"ENABLE","DISABLED"=>"DISABLE","SLAVESIDE_DISABLED"=>"DISABLE ON SLAVE");$I=$_POST;if($_POST&&!$m){if($_POST["drop"])query_redirect("DROP EVENT ".idf_escape($aa),substr(ME,0,-1),'Event has been dropped.');elseif(in_array($I["INTERVAL_FIELD"],$Jf)&&isset($Il[$I["STATUS"]])){$Ek="\nON SCHEDULE ".($I["INTERVAL_VALUE"]?"EVERY ".q($I["INTERVAL_VALUE"])." $I[INTERVAL_FIELD]".($I["STARTS"]?" STARTS ".q($I["STARTS"]):"").($I["ENDS"]?" ENDS ".q($I["ENDS"]):""):"AT ".q($I["STARTS"]))." ON COMPLETION".($I["ON_COMPLETION"]?"":" NOT")." PRESERVE";queries_redirect(substr(ME,0,-1),($aa!=""?'Event has been altered.':'Event has been created.'),queries(($aa!=""?"ALTER EVENT ".idf_escape($aa).$Ek.($aa!=$I["EVENT_NAME"]?"\nRENAME TO ".idf_escape($I["EVENT_NAME"]):""):"CREATE EVENT ".idf_escape($I["EVENT_NAME"]).$Ek)."\n".$Il[$I["STATUS"]]." COMMENT ".q($I["EVENT_COMMENT"]).rtrim(" DO\n$I[EVENT_DEFINITION]",";").";"));}}$Qh=false;if(!$I&&$aa!=""){$J=get_rows("SELECT * FROM information_schema.EVENTS WHERE EVENT_SCHEMA = ".q(DB)." AND EVENT_NAME = ".q($aa));$Qh=!$J;$I=reset($J);}page_header(($aa!=""?'Alter event'.": ".h($aa):'Create event'),$m,"#events","",$Qh,doc_link(array('sql'=>"create-event.html")));echo'
<form action="" method="post">
<table class="layout">
<tr><th>Name<td><input name="EVENT_NAME" value="',h($I["EVENT_NAME"]),'" data-maxlength="64" autocapitalize="off">
<tr><th title="datetime">Start<td><input name="STARTS" value="',h("$I[EXECUTE_AT]$I[STARTS]"),'">
<tr><th title="datetime">End<td><input name="ENDS" value="',h($I["ENDS"]),'">
<tr><th>Every
<td><input type="number" name="INTERVAL_VALUE" value="',h($I["INTERVAL_VALUE"]),'" class="size"> ',html_select("INTERVAL_FIELD",$Jf,$I["INTERVAL_FIELD"]),'<tr><th>Status<td>',html_select("STATUS",$Il,$I["STATUS"]),'<tr><th>Comment<td><input name="EVENT_COMMENT" value="',h($I["EVENT_COMMENT"]),'" data-maxlength="64">
<tr><th><td>',checkbox("ON_COMPLETION","PRESERVE",$I["ON_COMPLETION"]=="PRESERVE",'On completion preserve'),'</table>
<p>';textarea("EVENT_DEFINITION",$I["EVENT_DEFINITION"]);echo'<p>
<input type=\'submit\' value=\'Save\'>
';if($aa!="")echo'<input type=\'submit\' name=\'drop\' value=\'Drop\'',confirm(sprintf('Drop %s?',$aa)),'>
';echo
input_token(),'</form>
';}elseif(isset($_GET["procedure"])){$ba=($_GET["name"]?:$_GET["procedure"]);$vk=(isset($_GET["function"])?"FUNCTION":"PROCEDURE");$I=$_POST;$I["fields"]=(array)$I["fields"];if($_POST&&!process_fields($I["fields"])&&!$m){foreach($I["fields"]as$x=>$n){if($n["field"]=="")unset($I["fields"][$x]);}$ii=routine($_GET["procedure"],$vk);$gi=($ii?routine_id($ba,$ii):"");$Hh=routine_id($I["name"],$I);$i=create_routine($vk,$I);$Dg=substr(ME,0,-1);$bh='Routine has been altered.';if(!$_POST["drop"]&&$gi==$Hh&&connection()->flavor!="mysql")queries_redirect($Dg,$bh,queries(substr_replace($i,(JUSH=="mssql"?' OR ALTER':' OR REPLACE'),6,0)));else{$rm="adminer_".uniqid();drop_create("DROP $vk $gi",$i,"DROP $vk $Hh",create_routine($vk,array("name"=>$rm)+$I),"DROP $vk ".routine_id($rm,$I),$Dg,'Routine has been dropped.',$bh,'Routine has been created.',$ba,$I["name"]);}}$Qh=false;if(!$_POST&&$ba!=""){$I=routine($_GET["procedure"],$vk);$Qh=!$I;$I["name"]=$ba;}$yk=strtolower($vk);page_header(($ba!=""?(isset($_GET["function"])?'Alter function':'Alter procedure').": ".h($ba):(isset($_GET["function"])?'Create function':'Create procedure')),$m,"#routines","",$Qh,doc_link(array('sql'=>"create-procedure.html",'mariadb'=>"create-$yk/",'pgsql'=>"sql-create$yk.html",'cockroach'=>"create-$yk",'mssql'=>"t-sql/statements/create-$yk-transact-sql",)));if(!$_POST&&$ba=="")$I["language"]="sql";$Cb=(JUSH=="sql"?flat_collations():array());$wk=routine_languages();echo($Cb?"<datalist id='collations'>".optionlist($Cb)."</datalist>":""),'
<form action="" method="post" id="form">
<p>Name: <input name="name" value="',h($I["name"]),'" data-maxlength="64" autocapitalize="off">
',($wk?"<label>".'Language'.": ".html_select("language",array_keys($wk),$I["language"],on('change','routineLanguage',$wk))."</label>\n":""),'<input type=\'submit\' value=\'Save\'>
<div class="scrollable">
<table id="edit-fields" class="nowrap">
';edit_fields($I["fields"],$Cb,$vk);if(isset($_GET["function"])){echo"<tr><td>".'Return type';edit_type("returns",(array)$I["returns"],$Cb,array(),(JUSH=="pgsql"?array("void","trigger"):array()));}echo'</table>
',script("editFields();"),'</div>
<p>';textarea("definition",$I["definition"],20,80,($wk[$I["language"]]?:JUSH));echo'<p>
<input type=\'submit\' value=\'Save\'>
';if($ba!="")echo'<input type=\'submit\' name=\'drop\' value=\'Drop\'',confirm(sprintf('Drop %s?',$ba)),'>
';$zk=routine_options($vk);if($zk){$wi=false;foreach($zk
as$x=>$Y){$l=($Y?reset($Y):"");$I["options"][$x]=idx($I["options"],$x,$l);if($I["options"][$x]!=$l)$wi=true;}print_fieldset("options",'Options',$wi);echo"<table class='layout'>\n";foreach($zk
as$x=>$Y){$ig="label-option-$x";$zm=str_replace("_"," ",$x);$L=array();foreach($Y
as$X)$L[$X]=(strpos($X,"$zm ")===0?substr($X,strlen($zm)+1):$X);echo"<tr><th id='$ig'>$zm<td>".($L?html_select("options[$x]",$L,$I["options"][$x],"",$ig):"<input name='options[$x]' value='".h($I["options"][$x])."' aria-labelledby='$ig' autocapitalize='off'>")."\n";}echo"</table>\n</div></fieldset>\n";}echo
input_token(),'</form>
';}elseif(isset($_GET["sequence"])){$da=$_GET["sequence"];$I=$_POST;if($_POST&&!$m){$_=substr(ME,0,-1);$B=trim($I["name"]);if($_POST["drop"])query_redirect("DROP SEQUENCE ".idf_escape($da),$_,'Sequence has been dropped.');elseif($da=="")query_redirect("CREATE SEQUENCE ".idf_escape($B),$_,'Sequence has been created.');elseif($da!=$B)query_redirect("ALTER SEQUENCE ".idf_escape($da)." RENAME TO ".idf_escape($B),$_,'Sequence has been altered.');else
redirect($_);}$Qh=(!$_POST&&$da!=""&&!get_val("SELECT relname FROM pg_class WHERE relkind = 'S' AND relnamespace = ".driver()->nsOid." AND relname = ".q($da)));page_header(($da!=""?'Alter sequence'.": ".h($da):'Create sequence'),$m,"#sequences","",$Qh,doc_link(array('pgsql'=>"sql-createsequence.html",'cockroach'=>"create-sequence",)));if(!$I)$I["name"]=$da;echo'
<form action="" method="post">
<p><input name="name" value="',h($I["name"]),'" autocapitalize="off">
<input type=\'submit\' value=\'Save\'>
';if($da!="")echo"<input type='submit' name='drop' value='".'Drop'."'".confirm(sprintf('Drop %s?',$da)).">\n";echo
input_token(),'</form>
';}elseif(isset($_GET["type"])){function
enum_values($zc){$X="'(?:[^']|'')*'";if(!preg_match('~^AS\s+ENUM\s*\(\s*('.$X.'(?:\s*,\s*'.$X.')*)\s*\)$~i',$zc,$A))return
null;preg_match_all('~'.$X.'~',$A[1],$Jg);return$Jg[0];}function
add_enum_values($T,$ei,$Eh){$ki=enum_values($ei);$Mh=enum_values($Eh);if($ki===null||$Mh===null)return
null;$H=array();$s=0;foreach($Mh
as$X){if($X===idx($ki,$s))$s++;else$H[]="ALTER TYPE ".idf_escape($T)." ADD VALUE $X".($s<count($ki)?" BEFORE ".$ki[$s]:"");}return($s==count($ki)?$H:null);}$ea=$_GET["type"];$I=$_POST;$Wm=($ea!=""?array_search($ea,types(true)):0);$T=($Wm?type_definition(+$Wm):array());$Vh=($T["kind"]=='d'?"DOMAIN":"TYPE");if($_POST&&!$m){$_=substr(ME,0,-1);$B=trim($I["name"]);$Fa=trim(str_replace("\r","",$I["as"]));$Jh=(preg_match('~^AS\s+(?!ENUM\b|RANGE\b|\()~i',$Fa)?"DOMAIN":"TYPE");$bh='Type has been altered.';$b=(!$_POST["drop"]&&$ea!=""&&$Jh==$Vh?($Fa==$T["definition"]?array():add_enum_values($ea,$T["definition"],$Fa)):null);if($b!==null){if($ea!=$B)$b[]="ALTER $Vh ".idf_escape($ea)." RENAME TO ".idf_escape($B);if(!$b)redirect($_);$Ld=false;foreach($b
as$F){if(!queries($F)){$Ld=true;break;}}queries_redirect($_,$bh,!$Ld);}else{$rm="adminer_".uniqid();drop_create("DROP $Vh ".idf_escape($ea),"CREATE $Jh ".idf_escape($B)." $Fa","DROP $Jh ".idf_escape($B),"CREATE $Jh ".idf_escape($rm)." $Fa","DROP $Jh ".idf_escape($rm),$_,'Type has been dropped.',$bh,'Type has been created.',$ea,$B);}}page_header(($ea!=""?'Alter type'.": ".h($ea):'Create type'),$m,"#user-types","",($Wm===false),doc_link(array('pgsql'=>"sql-createtype.html",'cockroach'=>"create-type",)));if(!$I){$I["name"]=$ea;$I["as"]=($ea!=""?$T["definition"]:"AS ");}echo'
<form action="" method="post">
<p>
','Name'.": <input name='name' value='".h($I['name'])."' autocapitalize='off'>\n";textarea("as",$I["as"]);echo"<p><input type='submit' value='".'Save'."'>\n";if($ea!="")echo"<input type='submit' name='drop' value='".'Drop'."'".confirm(sprintf('Drop %s?',$ea)).">\n";echo
input_token(),'</form>
';}elseif(isset($_GET["check"])){$a=$_GET["check"];$B="$_GET[name]";$I=$_POST;if($I&&!$m){$Dg=ME."table=".url_escape($a);$eh='Check has been dropped.';$ch='Check has been altered.';$dh='Check has been created.';if(JUSH=="sqlite")queries_redirect($Dg,($I["drop"]?$eh:($B!=""?$ch:$dh)),recreate_table($a,$a,array(),array(),array(),"",array(),"$B",($I["drop"]?"":$I["clause"])));else{$b="ALTER TABLE ".table($a);$ob=" CHECK ($I[clause])";$rm="adminer_".uniqid();drop_create("$b DROP CONSTRAINT ".idf_escape($B),"$b ADD".($I["name"]!=""?" CONSTRAINT ".idf_escape($I["name"]):"").$ob,"$b DROP CONSTRAINT ".idf_escape($I["name"]),"$b ADD CONSTRAINT ".idf_escape($rm).$ob,"$b DROP CONSTRAINT ".idf_escape($rm),$Dg,$eh,$ch,$dh,$B,$I["name"]);}}$Qh=false;if(!$I){$sb=driver()->checkConstraints($a);$Qh=($B!=""&&!$sb[$B]);$I=array("name"=>($B!=""?$B:object_name("CHECK",$a,array())),"clause"=>$sb[$B]);}page_header(($B!=""?'Alter check':'Create check'),$m,array("table"=>$a),h($B!=""?$B:$a),$Qh,doc_link(array('sql'=>"create-table-check-constraints.html",'mariadb'=>"constraint/",'pgsql'=>"ddl-constraints.html#DDL-CONSTRAINTS-CHECK-CONSTRAINTS",'cockroach'=>"check",'mssql'=>"relational-databases/tables/create-check-constraints",'sqlite'=>"lang_createtable.html#check_constraints",)));echo'
<form action="" method="post">
';if(JUSH!="sqlite")echo'<p>'.'Name'.': <input name="name" value="'.h($I["name"]).'" data-maxlength="64" autocapitalize="off">';echo'<p>';textarea("clause",$I["clause"]);echo'<p><input type=\'submit\' value=\'Save\'>
';if($B!="")echo'<input type=\'submit\' name=\'drop\' value=\'Drop\'',confirm(sprintf('Drop %s?',$B)),'>
';echo
input_token(),'</form>
';}elseif(isset($_GET["trigger"])){$a=$_GET["trigger"];$B="$_GET[name]";$Rm=trigger_options();$I=trigger($B,$a);$Qh=($B!=""&&!$I);$yh=str_replace("{table}",$a,adminer()->namePattern("TRIGGER"));$I+=array("Trigger"=>strtr($yh,array("{timing}"=>"b","{event}"=>"i","{columns}"=>"","{type}"=>"row")));if($_POST){if(!$m&&in_array($_POST["Timing"],$Rm["Timing"])&&in_array($_POST["Event"],$Rm["Event"])&&in_array($_POST["Type"],$Rm["Type"])){$li=" ON ".table($a);$Vc="DROP TRIGGER ".idf_escape($B).(JUSH=="pgsql"?$li:"");$Dg=ME."table=".url_escape($a);if($_POST["drop"])query_redirect($Vc,$Dg,'Trigger has been dropped.');else{if($B!="")queries($Vc);queries_redirect($Dg,($B!=""?'Trigger has been altered.':'Trigger has been created.'),queries(create_trigger($li,$_POST)));if($B!="")queries(create_trigger($li,$I+array("Type"=>reset($Rm["Type"]))));}}$I=$_POST;}page_header(($B!=""?'Alter trigger':'Create trigger'),$m,array("table"=>$a),h($B!=""?$B:$a),$Qh,doc_link(array('sql'=>"create-trigger.html",'pgsql'=>"sql-createtrigger.html",'cockroach'=>"create-trigger",'mssql'=>"t-sql/statements/create-trigger-transact-sql",'sqlite'=>"lang_createtrigger.html",'oracle'=>"lnpls/CREATE-TRIGGER-statement.html",)));$Ah=strtr(preg_quote($yh),array('\{timing\}'=>'[abi]','\{event\}'=>'[iud]*','\{columns\}'=>'.*','\{type\}'=>'(row|statement)'));$Pm=on('change','triggerChange',"^$Ah$",$yh);$Yh=on('input','triggerChange',"^$Ah$",$yh);echo'
<form action="" method="post" id="form">
<table class="layout">
<tr><th>Time
<td>',html_select("Timing",$Rm["Timing"],$I["Timing"],$Pm),'<tr><th>Event<td>',html_select("Event",$Rm["Event"],$I["Event"],$Pm),(in_array("UPDATE OF",$Rm["Event"])?" <input name='Of' value='".h($I["Of"])."' class='hidden'$Yh>":""),'<tr><th>Type<td>',html_select("Type",$Rm["Type"],$I["Type"],$Pm),'<tr><th>Name<td><input name="Trigger" value="',h($I["Trigger"]),'" data-maxlength="64" autocapitalize="off">
</table>
',script("fire(qs('#form')['Timing'], 'change');"),'<p>';textarea("Statement",$I["Statement"]);echo'<p>
<input type=\'submit\' value=\'Save\'>
';if($B!="")echo'<input type=\'submit\' name=\'drop\' value=\'Drop\'',confirm(sprintf('Drop %s?',$B)),'>
';echo
input_token(),'</form>
';}elseif(isset($_GET["user"])){function
grant($_e,array$Kj,$e,$li){if(!$Kj)return
true;if($Kj==array("ALL PRIVILEGES","GRANT OPTION"))return($_e=="GRANT"?queries("$_e ALL PRIVILEGES$li WITH GRANT OPTION"):queries("$_e ALL PRIVILEGES$li")&&queries("$_e GRANT OPTION$li"));return
queries("$_e ".preg_replace('~(GRANT OPTION)\([^)]*\)~','\1',implode("$e, ",$Kj).$e).$li);}$fa=$_GET["user"];$Kj=array(""=>array("All privileges"=>""));foreach(get_rows("SHOW PRIVILEGES")as$I){foreach(explode(",",($I["Privilege"]=="Grant option"?"":$I["Context"]))as$Wb)$Kj[$Wb=="File access on server"?"Server Admin":$Wb][$I["Privilege"]]=$I["Comment"];}unset($Kj["Server Admin"]["Usage"]);foreach($Kj["Tables"]as$x=>$W)unset($Kj["Databases"][$x]);$Gh=array();if($_POST){foreach($_POST["objects"]as$x=>$W)$Gh[$W]=(array)$Gh[$W]+idx($_POST["grants"],$x,array());}$Ae=array();$G=(isset($_GET["host"])?connection()->query("SHOW GRANTS FOR ".q($fa)."@".q($_GET["host"])):null);$Qh=(isset($_GET["host"])&&!$G);if($G){while($I=$G->fetch_row()){if(preg_match('~GRANT (.*) ON (.*) TO ~',$I[0],$A)&&preg_match_all('~ *([^(,]*[^ ,(])( *\([^)]+\))?~',$A[1],$Jg,PREG_SET_ORDER)){foreach($Jg
as$W){if($W[1]!="USAGE")$Ae["$A[2]$W[2]"][$W[1]]=true;if(preg_match('~ WITH GRANT OPTION~',$I[0]))$Ae["$A[2]$W[2]"]["GRANT OPTION"]=true;}}}}if($_POST&&!$m){$ji=(isset($_GET["host"])?q($fa)."@".q($_GET["host"]):"''");if($_POST["drop"])query_redirect("DROP USER $ji",ME."privileges=",'User has been dropped.');else{$Lh=q($_POST["user"])."@".q($_POST["host"]);$fj=$_POST["pass"];$ec=false;$G=true;if($ji!=$Lh){$ec=queries("CREATE USER $Lh IDENTIFIED BY ".($_POST["hashed"]?"PASSWORD ":"").q($fj));$G=$ec;}elseif($fj!="")$G=queries("SET PASSWORD FOR $Lh = ".(min_version(8,99)||$_POST["hashed"]?q($fj):"PASSWORD(".q($fj).")"));if($G){$rk=array();foreach($Gh
as$Vh=>$_e){if(isset($_GET["grant"]))$_e=array_filter($_e);$_e=array_keys($_e);if(isset($_GET["grant"]))$rk=array_diff(array_keys(array_filter($Gh[$Vh],'strlen')),$_e);elseif($ji==$Lh){$fi=array_keys((array)$Ae[$Vh]);$rk=array_diff($fi,$_e);$_e=array_diff($_e,$fi);unset($Ae[$Vh]);}if(preg_match('~^(.+)\s*(\(.*\))?$~U',$Vh,$A)&&(!grant("REVOKE",$rk,$A[2]," ON $A[1] FROM $Lh")||!grant("GRANT",$_e,$A[2]," ON $A[1] TO $Lh"))){$G=false;break;}}}if($G&&isset($_GET["host"])){if($ji!=$Lh)queries("DROP USER $ji");elseif(!isset($_GET["grant"])){foreach($Ae
as$Vh=>$rk){if(preg_match('~^(.+)(\(.*\))?$~U',$Vh,$A))grant("REVOKE",array_keys($rk),$A[2]," ON $A[1] FROM $Lh");}}}if($G&&!Queries::$queries)redirect(ME."privileges=");queries_redirect(ME."privileges=",(isset($_GET["host"])?'User has been altered.':'User has been created.'),$G);if($ec)connection()->query("DROP USER $Lh");}}page_header((isset($_GET["host"])?'Username'.": ".h("$fa@$_GET[host]"):'Create user'),$m,array("privileges"=>array('','Privileges')),"",$Qh,doc_link(array('sql'=>"grant.html",'mariadb'=>"grant")));$I=$_POST;if($I)$Ae=$Gh;else{$I=$_GET+array("host"=>get_val("SELECT SUBSTRING_INDEX(CURRENT_USER, '@', -1)"));$Ae[(DB==""||$Ae?"":idf_escape(addcslashes(DB,"%_\\"))).".*"]=array();}echo'<form action="" method="post">
<table class="layout">
<tr><th>Server<td><input name="host" data-maxlength="60" value="',h($I["host"]),'" autocapitalize="off">
<tr><th>Username<td><input name="user" data-maxlength="80" value="',h($I["user"]),'" autocapitalize="off">
<tr><th>Password<td><input name="pass" id="pass" value="',h($I["pass"]),'" autocomplete="new-password">
',($I["hashed"]?"":script("typePassword(qs('#pass'));")),(min_version(8,99)?"":checkbox("hashed",1,$I["hashed"],'Hashed',on('click','hashedClick'))),'</table>

',"<table class='odds'>\n","<thead><tr><th colspan='2'>".'Privileges';$s=0;foreach($Ae
as$Vh=>$_e){echo'<th>'.($Vh!="*.*"?"<input name='objects[$s]' value='".h($Vh)."' size='10' autocapitalize='off'>":input_hidden("objects[$s]","*.*")."*.*");$s++;}echo"<tbody>\n";foreach(array(""=>"","Server Admin"=>'Server',"Databases"=>'Database',"Tables"=>'Table',"Procedures"=>'Routine',)as$Wb=>$Bc){foreach((array)$Kj[$Wb]as$Jj=>$f){echo"<tr><td".($Bc?">$Bc<td":" colspan='2'").' lang="en" title="'.h($f).'">'.h($Jj);$s=0;foreach($Ae
as$Vh=>$_e){$B="'grants[$s][".h(strtoupper($Jj))."]'";$X=$_e[strtoupper($Jj)];if($Wb=="Server Admin"&&$Vh!=(isset($Ae["*.*"])?"*.*":".*"))echo"<td>";elseif(isset($_GET["grant"]))echo"<td><select name=$B><option><option value='1'".($X?" selected":"").">".'Grant'."<option value='0'".($X=="0"?" selected":"").">".'Revoke'."</select>";else
echo"<td align='center'><label class='block'>","<input type='checkbox' name=$B value='1'".($X?" checked":"").($Jj=="All privileges"?" id='grants-$s-all'":($Jj=="Grant option"?"":on('click','grantsClick',"grants-$s-all"))).">","</label>";$s++;}}}echo"</table>\n",'<p>
<input type=\'submit\' value=\'Save\'>
';if(isset($_GET["host"]))echo'<input type=\'submit\' name=\'drop\' value=\'Drop\'',confirm(sprintf('Drop %s?',"$fa@$_GET[host]")),'>
';echo
input_token(),'</form>
';}elseif(isset($_GET["processlist"])){if(support("kill")){if($_POST&&!$m){$fg=0;foreach((array)$_POST["kill"]as$W){if(adminer()->killProcess($W))$fg++;}queries_redirect(ME."processlist=",lang_format(array('%d process has been killed.','%d processes have been killed.'),$fg),$fg||!$_POST["kill"]);}}page_header('Process list',$m);echo'
<form action="" method="post">
<div class="scrollable">
<table class="nowrap checkable odds"',on('click','tableClick').on('dblclick','tableClick'),'>
';$s=-1;foreach(adminer()->processList()as$s=>$I){if(!$s){echo"<thead><tr lang='en'>".(support("kill")?"<td class='hover'>":"");foreach($I
as$x=>$W)echo"<th>$x".doc_link(array('sql'=>"show-processlist.html#processlist_".strtolower($x),'pgsql'=>"monitoring-stats.html#PG-STAT-ACTIVITY-VIEW",'oracle'=>"refrn/V-SESSION.html",));echo"<tbody>\n";}echo"<tr>".(support("kill")?"<td class='hover'>".checkbox("kill[]",$I[JUSH=="sql"?"Id":"pid"],0):"");foreach($I
as$x=>$W)echo"<td>".($W!=""&&((JUSH=="sql"&&$x=="Info"&&preg_match("~Query|Killed~",$I["Command"]))||(JUSH=="pgsql"&&$x=="query")||(JUSH=="oracle"&&$x=="sql_text"))?"<code class='jush-".JUSH."' data-full='".h($W)."'>".shorten_utf8($W,100,"</code>").' <a href="'.h(($I["db"]!=""?preg_replace('~&db=[^&]*~','',ME)."db=".url_escape($I["db"])."&":ME)."sql=".url_escape($W)).'">'.'Clone'.'</a>'.' '.copy_icon():h($W));echo"\n";}echo'</table>
</div>
<p>
',script("copyCode(qsl('table'));");if(support("kill"))echo
format_number($s+1)."/".sprintf('%d in total',max_connections()),"<p><input type='submit' value='".'Kill'."'>\n";echo
input_token(),'</form>
',script("tableCheck();");}elseif($_GET["select"]!=""){$a=$_GET["select"];$R=table_status1($a);$w=indexes($a);$o=fields($a);$je=column_foreign_keys($a);$di=$R["Oid"];$tk=array();$e=array();$Kk=array();$zi=array();$um=null;foreach($o
as$x=>$n){$B=adminer()->fieldName($n);$_h=html_entity_decode(strip_tags($B),ENT_QUOTES);if(isset($n["privileges"]["select"])&&$B!=""){$e[$x]=$_h;if(is_shortable($n))$um=adminer()->selectLengthProcess();}if(isset($n["privileges"]["where"])&&$B!="")$Kk[$x]=$_h;if(isset($n["privileges"]["order"])&&$B!="")$zi[$x]=$_h;$tk+=$n["privileges"];}list($L,$r)=adminer()->selectColumnsProcess($e,$w);$L=array_unique($L);$r=array_unique($r);$Qf=count($r)<count($L);$Z=adminer()->selectSearchProcess($o,$w,$R);$yi=adminer()->selectOrderProcess($o,$w);$z=adminer()->selectLimitProcess();if($_GET["val"]&&is_ajax()){header("Content-Type: text/plain; charset=utf-8");foreach($_GET["val"]as$fn=>$I){$Fa=convert_field($o[key($I)]);$L=array($Fa?:idf_escape(key($I)));$Z[]=where_check(bracket_escape($fn,true),$o);$H=driver()->select($a,$L,$Z,$L);if($H)echo
first($H->fetch_row());}exit;}$Fj=$in=array();foreach($w
as$v){if($v["type"]=="PRIMARY"){$Fj=array_flip($v["columns"]);$in=($L?$Fj:array());foreach($in
as$x=>$W){if(in_array(idf_escape($x),$L))unset($in[$x]);}break;}}if($di&&!$Fj){$Fj=$in=array($di=>0);$w[]=array("type"=>"PRIMARY","columns"=>array($di));}if($_POST&&!$m){$Pn=$Z;if(!$_POST["all"]&&is_array($_POST["check"])){$sb=array();foreach($_POST["check"]as$ob)$sb[]=where_check($ob,$o);$Pn[]="((".implode(") OR (",$sb)."))";}$Rn=$Pn;$Pn=($Pn?"\nWHERE ".implode(" AND ",$Pn):"");if($_POST["export"]){save_settings(array("output"=>$_POST["output"],"format"=>$_POST["format"]),"adminer_import");dump_headers($a);adminer()->dumpTable($a,"");$Ok=($L?:array("*"));$Zb=convert_fields($e,$o,$L);if($Zb)$Ok[]=substr($Zb,2);$F="";if(is_array($_POST["check"])&&!$Fj){$qe=implode(", ",$Ok)."\nFROM ".table($a);$De=($r&&$Qf?"\nGROUP BY ".implode(", ",$r):"").($yi?"\nORDER BY ".implode(", ",$yi):"");$cn=array();foreach($_POST["check"]as$W)$cn[]="(SELECT".limit($qe,"\nWHERE ".($Z?implode(" AND ",$Z)." AND ":"").where_check($W,$o).$De,1).")";$F=implode(" UNION ALL ",$cn);}adminer()->dumpData($a,"table",$F,$Ok,$Rn,($Qf?$r:array()),$yi);adminer()->dumpFooter();exit;}if(!adminer()->selectEmailProcess($Z,$je)){if($_POST["save"]||$_POST["delete"]){$G=true;$ra=0;$Wa=false;$N=array();if(!$_POST["delete"]){foreach($o
as$B=>$W){$u=bracket_escape($B);if(isset($_POST["fields"][$u])||$_FILES["fields-$u"]){$W=process_input($o[$B]);if($W!==null&&($_POST["clone"]||$W!==false))$N[idf_escape($B)]=($W!==false?$W:idf_escape($B));}}}if($_POST["delete"]||$N){$F=($_POST["clone"]?"INTO ".table($a)." (".implode(", ",array_keys($N)).")\nSELECT ".implode(", ",$N)."\nFROM ".table($a):"");if($_POST["all"]||($Fj&&is_array($_POST["check"]))||$Qf){$G=($_POST["delete"]?driver()->delete($a,$Pn):($_POST["clone"]?queries("INSERT $F$Pn".driver()->insertReturning($a)):driver()->update($a,$N,$Pn)));$ra=connection()->affected_rows;if(is_object($G))$ra+=$G->num_rows;}else{$Wa=count((array)$_POST["check"])>1&&driver()->begin();foreach((array)$_POST["check"]as$W){$On="\nWHERE ".($Z?implode(" AND ",$Z)." AND ":"").where_check($W,$o);$G=($_POST["delete"]?driver()->delete($a,$On,1):($_POST["clone"]?queries("INSERT".limit1($a,$F,$On)):driver()->update($a,$N,$On,1)));if(!$G)break;$ra+=connection()->affected_rows;}if($Wa&&$G&&!driver()->commit())$G=false;}}$bh=lang_format(array('%d item has been affected.','%d items have been affected.'),$ra);if($_POST["clone"]&&$G&&$ra==1){$og=last_id($G);if($og)$bh=sprintf('Item%s has been inserted.'," $og");}queries_redirect(remove_from_uri($_POST["all"]&&$_POST["delete"]?"page|next":""),$bh,$G);if($Wa)driver()->rollback();if(!$_POST["delete"]){$wj=(array)$_POST["fields"];edit_form($a,array_intersect_key($o,$wj),$wj,!$_POST["clone"],$m);page_footer();exit;}}elseif(!$_POST["import"]){$G=true;$ra=0;$Wa=count((array)$_POST["val"])>1&&driver()->begin();foreach((array)$_POST["val"]as$fn=>$I){$N=array();foreach($I
as$x=>$W){$x=bracket_escape($x,true);$N[idf_escape($x)]=(preg_match('~char|text~',$o[$x]["type"])||$W!=""?adminer()->processInput($o[$x],$W):"NULL");}$G=driver()->update($a,$N," WHERE ".($Z?implode(" AND ",$Z)." AND ":"").where_check(bracket_escape($fn,true),$o),($Qf||$Fj?0:1)," ");if(!$G)break;$ra+=connection()->affected_rows;}if($Wa)$G=$G&&driver()->commit();queries_redirect(remove_from_uri(),lang_format(array('%d item has been affected.','%d items have been affected.'),$ra),$G);if($Wa)driver()->rollback();}else{save_settings(array("format"=>$_POST["separator"]),"adminer_import");$Td=get_file("csv_file",true);if(!is_string($Td))$m=upload_error($Td);elseif(!preg_match('~~u',$Td))$m='File must be in UTF-8 encoding.';else{$Db=array_keys($o);$Uk=($_POST["separator"]=="csv"?",":($_POST["separator"]=="tsv"?"\t":";"));$ic=parse_csv($Td,$Uk);$ra=count($ic);driver()->begin();$J=array();foreach($ic
as$x=>$Y){if(!$x&&!array_diff($Y,$Db)){$Db=$Y;$ra--;}else{$N=array();foreach($Y
as$s=>$_b)$N[idf_escape($Db[$s])]=($_b==""&&$o[$Db[$s]]["null"]?"NULL":q(csv_value($_b)));$J[]=$N;}}$G=(!$J||driver()->insertUpdate($a,$J,$Fj));if($G)driver()->commit();queries_redirect(remove_from_uri("page|next"),lang_format(array('%d row has been imported.','%d rows have been imported.'),$ra),$G);driver()->rollback();}}}}$bm=adminer()->tableName($R);if(is_ajax()){page_headers();ob_start();}else
page_header('Select'.": $bm",$m,array(),"",(!$o&&support("table")),($o?doc_link(array(JUSH=>driver()->tableHelp($a,is_view($R)))):""));$N=null;if(isset($tk["insert"])||!support("table")){$N="";foreach((array)$_GET["where"]as$W){$X=$W["val"];if(is_array($X))$X=(count($X)==1&&preg_match('~^val-(.*)~s',reset($X),$A)?$A[1]:"");if($W["col"]!=""&&$X!=""&&($W["op"]=="="||(!$W["op"]&&(is_array($W["val"])||!preg_match('~[_%]~',$X)))))$N
.="&set[".url_escape(bracket_escape($W["col"]))."]=".url_escape($X);}}adminer()->selectLinks($R,$N);if(!$e&&support("table"))echo"<p class='error'>".'Unable to select the table.'."\n";else{echo"<form action='' id='form'>\n","<div hidden>";hidden_fields_get();echo(DB!=""?input_hidden("db",DB).(isset($_GET["ns"])?input_hidden("ns",$_GET["ns"]):""):""),input_hidden("select",$a),"</div>\n";adminer()->selectColumnsPrint($L,$e);adminer()->selectSearchPrint($Z,$Kk,$w,$R);adminer()->selectOrderPrint($yi,$zi,$w);adminer()->selectLimitPrint($z);if($um!==null)adminer()->selectLengthPrint($um);adminer()->selectActionPrint($w);echo"</form>\n";foreach((array)$_GET["where"]as$W){if($W["op"]=="SQL"&&!in_array($_SERVER["HTTP_SEC_FETCH_SITE"],array("","same-origin"))){echo"<p class='error'>".'Invalid CSRF token. Submit the form again.'.' '.'If you did not send this request from Adminer, close this page.'."\n";page_footer();exit;}}$C=$_GET["page"];$me=null;if($C=="last"){$me=get_val(count_rows($a,$Z,$Qf,$r));$C=floor(max(0,intval($me)-1)/$z);}$Nk=$L;$Ce=$r;if(!$Nk){$Nk[]="*";$Zb=convert_fields($e,$o,$L);if($Zb)$Nk[]=substr($Zb,2);}foreach($L
as$x=>$W){$n=$o[idf_unescape($W)];if($n&&($Fa=convert_field($n)))$Nk[$x]="$Fa AS $W";}if(JUSH=="pgsql"||JUSH=="mssql"){foreach((array)$_GET["columns"]as$x=>$W){if(isset($Nk[$x])&&$W["fun"])$Nk[$x].=" AS ".idf_escape(apply_sql_function($W["fun"],($W["col"]!=""?$W["col"]:"*")));}}if(!$Qf&&$in){foreach($in
as$x=>$W){$Nk[]=idf_escape($x);if($Ce)$Ce[]=idf_escape($x);}}$G=driver()->select($a,$Nk,$Z,$Ce,$yi,$z,$C,true);if(!is_object($G))echo"<p class='error'>".(adminer()->error()?:'Unknown error.')."\n";else{if(JUSH=="mssql"&&$C)$G->seek($z*$C);$kd=array();$J=array();while($I=$G->fetch_assoc()){if($C&&JUSH=="oracle")unset($I["RNUM"]);$J[]=$I;}$Oe=($z&&(support("cursor")?$_GET["next"]!="":count($J)>=$z));if(is_ajax()&&$Oe)header("X-Next-Page: ".pagination_href($C+1));if($_GET["modify"]&&$J){$Sg=max_input_vars(count($J[0])+1,20);echo($Sg&&count($J)>$Sg?"<p class='error'>".max_input_vars_error()."\n":"");}echo"<form action='' method='post' enctype='multipart/form-data'".on_upload_progress($on).">\n";if($_GET["page"]!="last"&&$z&&$r&&$Qf&&JUSH=="sql")$me=get_val(" SELECT FOUND_ROWS()");if(!$J)echo"<p class='message'>".'No rows.'."\n";else{$Sa=adminer()->backwardKeys($a,$bm);$pk=array();reset($L);foreach($J[0]as$x=>$W){if(!isset($in[$x])){$W=idx($_GET["columns"],key($L))?:array();$pk[$x]=array("fun"=>$W["fun"],"col"=>($L?$W["col"]:$x));next($L);}}echo"<div class='scrollable'>","<table id='table' class='nowrap checkable odds'".on('click','tableClick').on('dblclick','tableClick').on('keydown','editingKeydown').">\n","<thead><tr>".(!$r&&$L?"":"<td class='hover check sticky'><input type='checkbox' id='all-page' class='jsonly' title='".'All rows on this page'."'".on('click','formCheck','^check').">");$Bh=array();$Xj=1;foreach($pk
as$x=>$W){$n=$o[$W["col"]];$B=($n?adminer()->fieldName($n,$Xj):($W["fun"]?($W["col"]!=""?h($W["col"]):"*"):h($x)));if($B!=""){$Xj++;$Bh[$x]=$B;$d=idf_escape($x);$ef=remove_from_uri('(order|desc)[^=]*|page|next').'&order[0]='.url_escape($x);$Bc="&desc[0]=1";$sl=preg_replace('~ DESC( NULLS LAST)?$~','',$yi[0]);$ul=($sl==$d||$sl==$x);echo"<th id='th[".h(bracket_escape($x))."]'".($ul?" aria-sort='".($sl==$yi[0]?"ascending":"descending")."'":"").">";$ve=apply_sql_function(h($W["fun"]),$B);$tl=isset($n["privileges"]["order"])||$W["fun"];echo($tl?"<a href='".h($ef.($ul&&$sl==$yi[0]?$Bc:''))."'>$ve</a>":$ve);$ah=($tl?"<a href='".h($ef.$Bc)."' title='".'descending'."' class='text'> ↓</a>":'');if(!$W["fun"]&&isset($n["privileges"]["where"]))$ah
.="<a href='#fieldset-search' title='".'Search'."' class='text jsonly'".on('click','selectSearch',$x)."> =</a>";echo($ah?"<span class='column'>$ah</span>":"");}}$vg=array();if($_GET["modify"]){foreach($J
as$I){foreach($I
as$x=>$W)$vg[$x]=max($vg[$x],min(40,utf8_length((string)$W)));}}$Ze=array();$Ye=array();foreach((array)$_GET["where"]as$W){$W+=array("col"=>"","op"=>"","val"=>"");$_b=$W["col"];$Jk=$W["val"];if(!is_array($Jk)&&($Jk!=""||preg_match('~NULL$~',$W["op"]))&&(!$W["op"]||in_array($W["op"],adminer()->operators($R)))){$xg=strtr(preg_quote($Jk),array("%"=>".*?","_"=>"."));$lj=array("LIKE %%"=>$xg,"ILIKE %%"=>$xg,"REGEXP"=>$Jk)+(JUSH=="pgsql"?array("~"=>$Jk,"~*"=>$Jk):array())+($_b!=""?array():array("="=>'^'.preg_quote($Jk).'\z',"IN"=>'^(?:'.implode("|",array_map('preg_quote',array_map('trim',explode(",",$Jk)))).')\z',"LIKE"=>"^$xg\\z","ILIKE"=>"^$xg\\z","FIND_IN_SET"=>'(?<=^|,)'.preg_quote($Jk).'(?=,|\z)',));foreach(($_b!=""?array($_b=>$o[$_b]):$o)as$B=>$n){if($_b!=""||is_searchable($n,$W)){$oi=$W["op"]?:(!preg_match('~'.text_type().'~',$n["type"])?"IN":(preg_match('~%~',$Jk)?"LIKE":"LIKE %%"));if(isset($lj[$oi])){$vb=preg_match('~^ILIKE|\*$~',$oi)||($oi!="~"&&preg_match('~^(sql|mssql|sqlite)$~',JUSH));$Ze[$B][]="(?".($vb?"i":"").":$lj[$oi])";}elseif($oi=="IS NULL"&&$_b=="")$Ye[$B]=true;}}}}echo($Sa?"<th>".'Relations':"")."<tbody>\n";if(is_ajax())ob_end_clean();foreach(adminer()->rowDescriptions($J,$je)as$wh=>$I){$en=unique_array($J[$wh],$w);if(!$en){$en=array();foreach($J[$wh]as$x=>$W){if(!in_array(idx(idx($pk,$x,array()),"fun"),driver()->grouping))$en[$x]=$W;}}$fn="";$s=0;foreach($en
as$x=>$W){$ok=idx($pk,$x,array());if($L&&$ok&&!isset($o[$ok["col"]]))continue;$ve=idx($ok,"fun","");$_b=($ve?$ok["col"]:$x);$n=(array)$o[$_b];$Pf=is_blob($n);if(!$ve&&strlen($W)>64&&driver()->md5(idf_escape($_b),$n)){$ve="md5";$W=md5($Pf?(string)driver()->value($W,$n):$W);}if($ve){$fn
.="&fun[$s]=".url_escape($ve)."&col[$s]=".url_escape($_b).($W!==null?"&val[$s]=".url_escape($W===false?"f":$W):"");$s++;}else$fn
.="&".($W!==null?"where[".url_escape(bracket_escape($_b))."]=".url_escape($W===false?"f":$W):"null[]=".url_escape($_b));}echo"<tr>".(!$r&&$L?"":"<td class='hover check sticky'>".($Qf||information_schema(DB)?"":"<a href='".h(ME."edit=".url_escape($a).$fn)."' class='edit'>".'edit'."</a> ").checkbox("check[]",substr($fn,1),in_array(substr($fn,1),(array)$_POST["check"])));foreach($I
as$x=>$W){if(isset($Bh[$x])){$ve=$pk[$x]["fun"];$_b=$pk[$x]["col"];$n=(array)$o[$x];if($W!=""&&(!isset($kd[$x])||$kd[$x]!=""))$kd[$x]=(is_mail($W)?$Bh[$x]:"");$_="";if(is_blob($n)&&$W!="")$_=ME.'download='.url_escape($a).'&field='.url_escape($x).$fn;if(!$_&&$W!==null){foreach((array)$je[$x]as$q){if(count($je[$x])==1||end($q["source"])==$x){$_="";foreach($q["source"]as$s=>$vl)$_
.=where_link($s,$q["target"][$s],$J[$wh][$vl]);$_=($q["db"]!=""?preg_replace('~&db=[^&]*~',"&db=".url_escape($q["db"]),ME):ME).'select='.url_escape($q["table"]).$_;if($q["ns"]!="")$_=preg_replace('~&ns=[^&]*~',"&ns=".url_escape($q["ns"]),$_);if(count($q["source"])==1)break;}}}if($ve=="count"&&$_b==""){$_=ME."select=".url_escape($a);$s=0;foreach((array)$_GET["where"]as$V){if(!array_key_exists($V["col"],$en))$_
.=where_link($s++,$V["col"],$V["val"],$V["op"]);}foreach($en
as$ag=>$V){if(idx(idx($pk,$ag,array()),"fun")){$_="";break;}$_
.=where_link($s++,$ag,$V);}}$ff=select_value($W,$_,$n,$um,($ve?array():idx($Ze,$x,array())));if($W===null&&!$ve&&isset($Ye[$x]))$ff="<mark>$ff</mark>";$u=bracket_escape($fn);$t=h("val[$u][".bracket_escape($x)."]");$yj=idx(idx($_POST["val"],$u),bracket_escape($x));$ln=idx($n["privileges"],"update")&&!is_identity_always($n);$fd=$J[$wh][$x];$gd=!is_array($I[$x])&&!is_blob($n)&&is_utf8($fd)&&!$ve&&!$n["generated"]&&$ln;$T=($ve=="min"||$ve=="max"?$o[$_b]["type"]:$n["type"]);$tm=preg_match('~text|json|lob~',$T);$Rf=preg_match(number_type(),$T)||preg_match('~^(avg|ceil|char_length|count|count distinct|floor|len|length|round|sum|time_to_sec)$~',$ve);echo"<td id='$t'".($Rf&&($W===null||is_numeric(strip_tags($ff))||$T=="money")?" class='number'":"");if(($_GET["modify"]&&$gd&&$fd!==null)||$yj!==null){$Je=h($yj!==null?$yj:$fd);echo">".($tm?"<textarea name='$t' cols='30' rows='".(substr_count($fd,"\n")+1)."'>$Je</textarea>":"<input name='$t' value='$Je' size='$vg[$x]'>");}else{$Fg=strpos($ff,"<i>…</i>");$ed=adminer()->editVal($fd,$n);echo($ln?" data-text='".($Fg?2:($tm?1:0))."'".($gd?"":" data-warning='".'Use the edit link to modify this value.'."'").($gd&&!$Fg&&html_entity_decode(strip_tags($ff),ENT_QUOTES,"UTF-8")!==$ed?" data-value='".h($ed)."'":""):"").">$ff";}}}if($Sa)echo"<td>";adminer()->backwardKeysPrint($Sa,$J[$wh]);echo"</tr>\n";}if(is_ajax())exit;echo"</table>\n","</div>\n";}if(!is_ajax()){$qa=get_settings("adminer_import");if($J||$C||$Oe){$_d=true;if($_GET["page"]!="last"){if(!$z||(count($J)<$z&&($J||!$C)))$me=($C?$C*$z:0)+count($J);elseif(JUSH!="sql"||!$Qf){$me=($Qf?null:found_rows($R,$Z));$_d=!driver()->hasEstimatedRows();if($me===null||(!$_d&&$me<max(1e4,2*($C+1)*$z))){$me=first(slow_query(count_rows($a,$Z,$Qf,$r)));$_d=true;}}}if(!support("cursor"))$Oe=(($me===false?count($J)+1:$me-$C*$z)>$z);$Qi=($z&&($Oe||$C));if($Qi)echo($Oe?'<p><a href="'.h(pagination_href($C+1)).'" class="loadmore"'.on('click','selectLoadMore','Loading…').'>'.'Load more data'.'</a>':''),"\n";echo"<div class='footer'><div>\n";if($Qi){$Qg=($me===false?$C+($J?(count($J)>=$z?2:1):0):floor(($me-1)/$z));echo"<fieldset><legend>".'Page'."</legend>";if(!support("cursor")){echo
pagination(0,$C).($C>5?" …":"");for($s=max(1,$C-4);$s<min($Qg,$C+5);$s++)echo
pagination($s,$C);if($Qg>0)echo($C+5<$Qg?" …":""),($_d&&$me!==false?pagination($Qg,$C):" <a href='".h(remove_from_uri("page")."&page=last")."' title='~$Qg'>".'last'."</a>");}else
echo
pagination(0,$C).($C>1?" …":""),($C?pagination($C,$C):""),($Oe?pagination($C+1,$C)." …":"");echo"</fieldset>\n";}echo"<fieldset>","<legend>".'Whole result'."</legend>";$Jc=($_d?"":"~ ").$me;$ig=($me!==false?($_d?"":"~ ").lang_format(array('%d row','%d rows'),$me):"");echo
checkbox("all",1,0,$ig,on('click','countRows',$Jc))."\n","</fieldset>\n";if(adminer()->selectCommandPrint())echo'<fieldset',($_GET["modify"]?'':" title='".'Ctrl+click on a value to modify it.'."'"),'>
<legend><a href=\'',h($_GET["modify"]?remove_from_uri("modify"):relative_uri()."&modify=1"),'\'>Modify</a></legend><div>
<input type=\'submit\' id=\'save\' value=\'Save\'',($_GET["modify"]||$_POST["val"]?'':" class='jsonly' disabled"),'>
</div></fieldset>

<fieldset><legend>Selected <span id="selected"></span></legend><div>
<input type=\'submit\' name=\'edit\' value=\'Edit\'>
<input type=\'submit\' name=\'clone\' value=\'Clone\'>
<input type=\'submit\' name=\'delete\' value=\'Delete\'',confirm(),'>
</div></fieldset>
';$ke=adminer()->dumpFormat();foreach((array)$_GET["columns"]as$d){if($d["fun"]||($d["col"]!=""&&!isset($o[$d["col"]]))){unset($ke['sql']);break;}}if($ke){print_fieldset("export",'Export'." <span id='selected2'></span>");$Mi=adminer()->dumpOutput();echo($Mi?html_select("output",$Mi,$qa["output"])." ":""),html_select("format",$ke,$qa["format"])," <input type='submit' name='export' value='".'Export'."'>\n","</div></fieldset>\n";}adminer()->selectEmailPrint(array_filter($kd,'strlen'),$e);echo"</div></div>\n";}if(adminer()->selectImportPrint())echo"<p>","<a href='#import' class='toggle'>".'Import'."</a>","<span id='import'".($_POST["import"]?"":" class='hidden'").">: ",($on?input_hidden(ini_get("session.upload_progress.name"),$on):""),file_input(" name='csv_file'"," ".html_select("separator",array("csv"=>"CSV,","csv;"=>"CSV;","tsv"=>"TSV"),$qa["format"])." <input type='submit' name='import' value='".'Import'."'>".($on?" <progress class='jsonly hidden' max='1' value='0'></progress>":"")),"</span>";echo
input_token(),"</form>\n",(!$r&&$L?"":script("tableCheck();"));}}}if(is_ajax()){ob_end_clean();exit;}}elseif(isset($_GET["variables"])){$O=isset($_GET["status"]);page_header($O?'Status':'Variables');$En=($O?adminer()->showStatus():adminer()->showVariables());if(!$En)echo"<p class='message'>".'No rows.'."\n";else{echo"<table>\n";foreach($En
as$I){echo"<tr>";$x=array_shift($I);echo"<th><code class='jush-".JUSH.($O?"status":"set")."'>".h($x)."</code>";foreach($I
as$W)echo"<td>".nl_br(h($W));}echo"</table>\n";}}elseif(isset($_GET["script"])){header("Content-Type: application/json; charset=utf-8");if($_GET["script"]=="db"){$Ql=array("Data_length"=>0,"Index_length"=>0,"Data_free"=>0);foreach(table_status()as$B=>$R){json_row("Comment-$B",h($R["Comment"]).($R["Error"]?" <span class='error'>".h($R["Error"])."</span>":""));if(!is_view($R)||preg_match('~materialized~i',$R["Engine"])){foreach(array("Engine","Collation")as$x)json_row("$x-$B",h($R[$x]));foreach(array_keys($Ql+array("Auto_increment"=>0,"Rows"=>0))as$x){if(array_key_exists($x,$R))json_row("$x-$B",format_status($R,$x));if($R[$x]!=""&&isset($Ql[$x]))$Ql[$x]+=($R["Engine"]!="InnoDB"||$x!="Data_free"?$R[$x]:0);}}}if(function_exists('Adminer\db_status'))$Ql=db_status();foreach($Ql
as$x=>$W)json_row("sum-$x",adminer()->formatSizeValue($W));json_row("");}elseif($_GET["script"]=="kill"){if(!$m)connection()->query("KILL ".number($_POST["kill"]));}else{foreach(count_tables(adminer()->databases(false))as$k=>$W){json_row("tables-$k",format_number($W));json_row("size-$k",db_size($k));}json_row("");}exit;}else{if(!isset($_GET["select"])&&support("single_table")){$S=tables_list();if($S)redirect(ME.(support("table")?"table=":"select=").url_escape(key($S)));}$Xg=ME.(isset($_GET["select"])?"select=&":"");$lm=array_merge((array)$_POST["tables"],(array)$_POST["views"]);if($lm&&!$m&&!$_POST["search"]){$G=true;$bh="";if(JUSH=="sql"&&$_POST["tables"]&&count($_POST["tables"])>1&&($_POST["drop"]||$_POST["truncate"]||$_POST["copy"]))queries("SET foreign_key_checks = 0");if($_POST["truncate"]){if($_POST["tables"])$G=truncate_tables($_POST["tables"]);$bh='Tables have been truncated.';}elseif($_POST["move"]){$G=move_tables((array)$_POST["tables"],(array)$_POST["views"],$_POST["target"]);$bh='Tables have been moved.';}elseif($_POST["copy"]){$G=copy_tables((array)$_POST["tables"],(array)$_POST["views"],$_POST["target"]);$bh='Tables have been copied.';}elseif($_POST["drop"]){if($_POST["views"])$G=drop_views($_POST["views"]);if($G&&$_POST["tables"])$G=drop_tables($_POST["tables"]);$bh='Tables have been dropped.';}elseif(JUSH=="sqlite"&&$_POST["check"]){foreach((array)$_POST["tables"]as$Q){foreach(get_rows("PRAGMA integrity_check(".q($Q).")")as$I)$bh
.="<b>".h($Q)."</b>: ".h($I["integrity_check"])."<br>";}}elseif(JUSH=="mssql"&&$_POST["check"]){foreach((array)$_POST["tables"]as$Q){foreach(get_rows("DBCC CHECKTABLE (".q(table($Q)).") WITH TABLERESULTS")as$I)$bh
.="<b>".h($Q)."</b>: ".h($I["MessageText"])."<br>";}}elseif(JUSH!="sql"){$G=(JUSH=="sqlite"?queries("VACUUM"):apply_queries("VACUUM".($_POST["optimize"]?" ANALYZE":""),(array)$_POST["tables"]));$bh='Tables have been optimized.';}elseif(!$_POST["tables"])$bh='No tables.';elseif($G=queries(($_POST["optimize"]?"OPTIMIZE":($_POST["check"]?"CHECK":($_POST["repair"]?"REPAIR":"ANALYZE")))." TABLE ".implode(", ",array_map('Adminer\idf_escape',$_POST["tables"])))){while($I=$G->fetch_assoc())$bh
.="<b>".h($I["Table"])."</b>: ".h($I["Msg_text"])."<br>";}queries_redirect(relative_uri(),$bh,$G);}page_header(($_GET["ns"]==""?'Database'.": ".h(DB):'Schema'.": ".h($_GET["ns"])),$m,true);if(adminer()->homepage()){if($_GET["ns"]!==""){if(support("scheme")&&support("comment")){$f=schema_comment($_GET["ns"]);if($f!="")echo"<p class='nowrap'>".'Comment'.": ".adminer()->commentValue('SCHEMA',$f)."\n";}$yi=$_GET["order"];$se=($yi||support("fast_status"));echo"<div>\n","<h3 id='tables-views'>".'Tables and views'."</h3>\n";$km=($se?table_status():tables_list());if(!$km)echo"<p class='message'>".'No tables.'."\n";else{echo"<form action='' method='post'>\n";if(support("table")){echo"<fieldset><legend>".'Search data in tables'." <span id='selected2'></span></legend><div>",html_select("op",adminer()->operators(),idx($_POST,"op",JUSH=="elastic"?"should":"LIKE %%"))," <input type='search' name='query' value='".h($_POST["query"])."'".on('keydown','submitKeydown','search').">"," <input type='submit' name='search' value='".'Search'."'>\n","</div></fieldset>\n";if(!$m&&$_POST["search"]&&$_POST["query"]!=""){$_GET["where"][0]["op"]=$_POST["op"];search_tables();}}echo"<div class='scrollable'>\n","<table class='nowrap checkable odds'".on('click','tableClick').on('dblclick','tableClick').">\n",'<thead><tr>','<td class="hover"><input id="check-all" type="checkbox" class="jsonly" title="'.'All'.'"'.on('click','formCheck','^(tables|views)\[').'>','<th class="sticky"'.(!$yi&&JUSH!='sqlite'?" aria-sort='ascending'":'').'><a href="'.h(substr($Xg,0,-1)).'">'.'Table'.'</a>';$e=array("Engine"=>array('Engine'.doc_link(array('sql'=>'storage-engines.html'))));if(collations())$e["Collation"]=array('Collation'.doc_link(array('sql'=>'charset-charsets.html','mariadb'=>'supported-character-sets-and-collations/')));if(function_exists('Adminer\alter_table'))$e["Data_length"]=array('Data Length'.doc_link(array('sql'=>'show-table-status.html','pgsql'=>'functions-admin.html#FUNCTIONS-ADMIN-DBOBJECT','oracle'=>'refrn/ALL_TABLES.html')),"create",'Alter table',);if(support("indexes"))$e["Index_length"]=array('Index Length'.doc_link(array('sql'=>'show-table-status.html','pgsql'=>'functions-admin.html#FUNCTIONS-ADMIN-DBOBJECT')),"indexes",'Alter indexes',);$e["Data_free"]=array('Data Free'.doc_link(array('sql'=>'show-table-status.html')),"edit",'New item');if(function_exists('Adminer\alter_table'))$e["Auto_increment"]=array('Auto Increment'.doc_link(array('sql'=>'example-auto-increment.html','mariadb'=>'auto_increment/')),"auto_increment=1&create",'Alter table',);$e["Rows"]=array('Rows'.doc_link(array('sql'=>'show-table-status.html','pgsql'=>'catalog-pg-class.html#CATALOG-PG-CLASS','oracle'=>'refrn/ALL_TABLES.html')),"select",'Select data',);if(support("comment"))$e["Comment"]=array('Comment'.doc_link(array('sql'=>'show-table-status.html','pgsql'=>'functions-info.html#FUNCTIONS-INFO-COMMENT-TABLE','cockroach'=>'comment-on')),);$Ga=array('Engine','Collation','Comment');foreach($e
as$x=>$d)echo"<th".($yi==$x?" aria-sort='".(in_array($x,$Ga)?"ascending":"descending")."'":"")."><a href='".h($Xg)."order=$x'>$d[0]</a>";echo"<tbody>\n";if($yi){uasort($km,function($ha,$Pa)use($yi,$Ga){$H=($ha[$yi]<$Pa[$yi]?-1:($ha[$yi]>$Pa[$yi]?1:0));return(in_array($yi,$Ga)?$H:-$H);});}$S=0;$Ql=array("Data_length"=>0,"Index_length"=>0,"Data_free"=>0);foreach($km
as$B=>$O){$Hn=($se?is_view($O):$O!==null&&!preg_match('~table|sequence~i',$O));$O=($se?$O:array('Engine'=>$O));$t=h("Table-".$B);echo'<tr><td class="hover">'.checkbox(($Hn?"views[]":"tables[]"),$B,in_array("$B",$lm,true),"","","",$t),'<th class="sticky">'.(support("table")||support("indexes")?"<a href='".h(ME)."table=".url_escape($B)."' title='".'Show structure'."' id='$t'>".h($B).'</a>':h($B));if($Hn&&!preg_match('~materialized~i',$O['Engine'])){$zm='View';echo'<td colspan="'.(count($e)-(support("comment")?2:1)).'">'.(support("view")?"<a href='".h(ME)."view=".url_escape($B)."' title='".'Alter view'."'>$zm</a>":$zm),"<td align='right'><a href='".h(ME)."select=".url_escape($B)."' title='".'Select data'."'>?</a>";if(support("comment"))echo'<td>'.h($O['Comment']);}else{if($se){foreach(array_keys($Ql)as$x)$Ql[$x]+=($O["Engine"]!="InnoDB"||$x!="Data_free"?idx($O,$x):0);}foreach($e
as$x=>$d){$t=" id='$x-".h($B)."'";echo($d[1]?"<td align='right'><a href='".h(ME."$d[1]=").url_escape($B)."'$t title='$d[2]'>".format_status($O,$x)."</a>":"<td$t>".h(idx($O,$x,'?')).($x=="Comment"&&$O["Error"]?" <span class='error'>".h($O["Error"])."</span>":""));}$S++;}echo"\n";}echo"<tr><td class='hover'><th class='sticky'>".sprintf('%d in total',count($km)),"<td>".h(JUSH=="sql"?get_val("SELECT @@default_storage_engine"):""),(collations()?"<td>".h(db_collation(DB,collations())):'');if($se&&function_exists('Adminer\db_status'))$Ql=db_status();foreach($Ql
as$x=>$Pl)echo($e[$x]?"<td align='right' id='sum-$x'>".($se?adminer()->formatSizeValue($Pl):""):"");echo"\n","</table>\n",($se?'':script("ajaxSetHtml('".js_escape(ME)."script=db');")),"</div>\n";if(!information_schema(DB)){$An="<input type='submit' value='".'Vacuum'."'".on_help("VACUUM")."> ";$ti="<input type='submit' name='optimize' value='".'Optimize'."'".on_help(JUSH=="sql"?"OPTIMIZE TABLE":"VACUUM ANALYZE")."> ";$Hj=(JUSH=="sqlite"?$An."<input type='submit' name='check' value='".'Check'."'".on_help("PRAGMA integrity_check")."> ":(JUSH=="pgsql"?$An.$ti:(JUSH=="mssql"?"<input type='submit' name='check' value='".'Check'."'".on_help("DBCC CHECKTABLE")."> ":(JUSH=="sql"?"<input type='submit' value='".'Analyze'."'".on_help("ANALYZE TABLE")."> ".$ti."<input type='submit' name='check' value='".'Check'."'".on_help("CHECK TABLE")."> "."<input type='submit' name='repair' value='".'Repair'."'".on_help("REPAIR TABLE")."> ":"")))).(function_exists('Adminer\truncate_tables')?"<input type='submit' name='truncate' value='".'Truncate'."'".confirm().on_help(JUSH=="sqlite"?"DELETE":"TRUNCATE".(JUSH=="pgsql"?"":" TABLE"))."> ":"").(function_exists('Adminer\drop_tables')?"<input type='submit' name='drop' value='".'Drop'."'".confirm().on_help("DROP TABLE").">":"");echo($Hj?"<div class='footer'><div>\n<fieldset><legend>".'Selected'." <span id='selected'></span></legend><div>$Hj\n</div></fieldset>\n":"");$j=(support("scheme")?adminer()->schemas():adminer()->databases());if(count($j)!=1&&function_exists('Adminer\move_tables')){echo"<fieldset><legend>".'Move to another database'." <span id='selected3'></span></legend><div>";$k=(isset($_POST["target"])?$_POST["target"]:(support("scheme")?$_GET["ns"]:DB));echo($j?html_select("target",$j,$k):'<input name="target" value="'.h($k).'" autocapitalize="off">'),"</label> <input type='submit' name='move' value='".'Move'."'>",(support("copy")?" <input type='submit' name='copy' value='".'Copy'."'> ".checkbox("overwrite",1,$_POST["overwrite"],'overwrite'):""),"</div></fieldset>\n";}echo"<input type='hidden' name='all' value=''".on('click','countTables',$S).">\n",input_token(),"</div></div>\n";}echo"</form>\n",script("tableCheck();");}echo(function_exists('Adminer\alter_table')?"<p class='links hover'><a href='".h(ME)."create='>".'Create table'."</a>\n":''),(support("view")?"<a href='".h(ME)."view='>".'Create view'."</a>\n":""),"</div>\n";if(support("routine")){echo"<div>\n","<h3 id='routines'>".'Routines'."</h3>\n";$Ak=routines();if($Ak){echo"<table class='odds'>\n",'<thead><tr><th>'.'Name'.'<th>'.'Type'.'<th>'.'Return type'."<td class='hover'><tbody>\n";foreach($Ak
as$I){$B=($I["SPECIFIC_NAME"]==$I["ROUTINE_NAME"]?"":"&name=".url_escape($I["ROUTINE_NAME"]));echo'<tr>','<th><a href="'.h(ME.($I["ROUTINE_TYPE"]!="PROCEDURE"?'callf=':'call=').url_escape($I["SPECIFIC_NAME"]).$B).'"'.' title="'.($I["DTD_IDENTIFIER"]=="trigger"?'Triggers':'Call').'">'.h($I["ROUTINE_NAME"]).'</a>','<td>'.h($I["ROUTINE_TYPE"]),'<td>'.h($I["DTD_IDENTIFIER"]),'<td class="hover"><a href="'.h(ME.($I["ROUTINE_TYPE"]!="PROCEDURE"?'function=':'procedure=').url_escape($I["SPECIFIC_NAME"]).$B).'">'.'Alter'."</a>";}echo"</table>\n";}echo'<p class="links hover">'.(support("procedure")?'<a href="'.h(ME).'procedure=">'.'Create procedure'.'</a>':'').'<a href="'.h(ME).'function=">'.'Create function'."</a>\n","</div>\n";}if(support("sequence")){echo"<div>\n","<h3 id='sequences'>".'Sequences'."</h3>\n";$Yk=get_vals("SELECT relname FROM pg_class WHERE relkind = 'S' AND relnamespace = ".driver()->nsOid." ORDER BY relname");if($Yk){echo"<table class='odds'>\n","<thead><tr><th>".'Name'."<tbody>\n";foreach($Yk
as$W)echo"<tr><th><a href='".h(ME)."sequence=".url_escape($W)."'>".h($W)."</a>\n";echo"</table>\n";}echo"<p class='links hover'><a href='".h(ME)."sequence='>".'Create sequence'."</a>\n","</div>\n";}if(support("type")){echo"<div>\n","<h3 id='user-types'>".'User types'."</h3>\n";$yn=types();if($yn){echo"<table class='odds'>\n","<thead><tr><th>".'Name'."<tbody>\n";foreach($yn
as$W)echo"<tr><th><a href='".h(ME)."type=".url_escape($W)."'>".h($W)."</a>\n";echo"</table>\n";}echo"<p class='links hover'><a href='".h(ME)."type='>".'Create type'."</a>\n","</div>\n";}if(support("event")){echo"<div>\n","<h3 id='events'>".'Events'."</h3>\n";$J=get_rows("SHOW EVENTS");if($J){echo"<table>\n","<thead><tr><th>".'Name'."<th>".'Schedule'."<th>".'Start'."<th>".'End'."<td class='hover'><tbody>\n";foreach($J
as$I)echo"<tr>","<th>".h($I["Name"]),"<td>".($I["Execute at"]?'At given time'."<td>".h($I["Execute at"]):'Every'." ".h($I["Interval value"])." ".h($I["Interval field"])."<td>".h($I["Starts"])),"<td>".h($I["Ends"]),'<td class="hover"><a href="'.h(ME).'event='.url_escape($I["Name"]).'">'.'Alter'.'</a>';echo"</table>\n";$xd=get_val("SELECT @@event_scheduler");if($xd&&$xd!="ON")echo"<p class='error'><code class='jush-sqlset'>event_scheduler</code>: ".h($xd)."\n";}echo'<p class="links hover"><a href="'.h(ME).'event=">'.'Create event'."</a>\n","</div>\n";}}elseif(support("extension")){$Id=get_rows("SELECT e.extname, e.extversion, n.nspname, obj_description(e.oid, 'pg_extension') AS comment
FROM pg_extension e
JOIN pg_namespace n ON n.oid = e.extnamespace
ORDER BY e.extname");if($Id){echo"<div>\n","<h3 id='extensions'>".'Extensions'."</h3>\n","<table class='odds'>\n","<thead><tr><th>".'Name'."<th>".'Version'."<th>".'Schema'."<th>".'Comment'."<tbody>\n";foreach($Id
as$I)echo"<tr><th><code class='jush-pgsqlext'>".h($I["extname"])."</code>","<td>".h($I["extversion"]),"<td><a href='".h(substr(ME,0,-1).url_escape($I["nspname"]))."'>".h($I["nspname"])."</a>","<td>".h($I["comment"]),"\n";echo"</table>\n","</div>\n";}}}}page_footer();