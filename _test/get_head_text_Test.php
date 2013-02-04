<?php
$token = '08785ff27bbf462a64cca1fee185255f';
$domainname = 'http://localhost/maspen';
$module = 2;
$user = 4;
if(0){
	$token = '2d1a05efd36f0751a6a9fa7c6e3179e7';
	$domainname = 'http://konoha.ubicg.ynu.ac.jp/maspen';
	$id = 2;
	$userid = 3;
}
$functionname = 'local_exfunctions_get_head_text';

$restformat = 'json';

$params = array('module'=> $module, 'user' => $user);

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