<?php
$token = '08785ff27bbf462a64cca1fee185255f';
$domainname = 'http://localhost/maspen';
$id = 2;
if(0){
	$token = '863941fa304ba6566e5c392515286aa3';
	$domainname = 'http://konoha.ubicg.ynu.ac.jp/maspen';
	$id = 2;
}
$functionname = 'local_exfunctions_get_run_runking';

$restformat = 'json';

$params = array('id'=> $id);

$serverurl = $domainname . '/webservice/rest/server.php'. '?wstoken=' . $token . '&wsfunction='.$functionname;

require_once('./curl.php');
$curl = new curl;
//if rest format == 'xml', then we do not add the param for backward compatibility with Moodle < 2.2
$restformat = ($restformat == 'json')?'&moodlewsrestformat=' . $restformat:'';
$resp = $curl->post($serverurl . $restformat, $params);

echo "<pre>";
print_r($resp);
echo "\n\n\n";
var_dump(json_decode($resp));
echo "</pre>";