<?php
// hook public_function_before.php

function xn_rand ($n = 16){
	mt_srand();
	$str = '0123456789abcdefghijklmnopqrstuvwxyz';
	$len = strlen($str);
	$return = '';
	for($i = 0 ; $i < $n ; $i++) {
		$r = mt_rand(1, $len);
		$return .= $str[$r - 1];
	}
	return $return;
}

function mid($n, $min, $max)
{
	if ($n < $min) return $min;
	if ($n > $max) return $max;
	return $n;
}

function isMobile($mobile)
{
	return preg_match('#^1[\d]{10}$#', $mobile) ? true : false;
}

function CheckSubstrs($substrs, $text)
{
	foreach ($substrs as $substr)
		if (false !== strpos($text, $substr)) {
			return true;
		}
	return false;
}

/**
 * 签名算法
 * $appKey = 'test';
 * $appSecret = 'test';
 * $sessionkey= 'test';
 * //参数数组
 * $paramArr = array(
 * 'app_key' => $appKey,
 * 'session_key' => $sessionkey,
 * 'method' => 'taobao.user.seller.get',
 * 'format' => 'json',
 * 'v' => '2.0',
 * 'sign_method'=>'md5',
 * 'timestamp' => date('Y-m-d H:i:s'),
 * );
 *
 * //生成签名
 * $sign = createSign($paramArr);
 * //组织参数
 * $strParam = createStrParam($paramArr);
 * $strParam .= 'sign='.$sign;
 */
function createSign($paramArr, $appSecret)
{
	$sign = $appSecret;
	ksort($paramArr);
	foreach ($paramArr as $key => $val) {
		if ($key != '' && $val != '') {
			$sign .= $key . $val;
		}
	}
	$sign .= $appSecret;
	$sign = strtoupper(md5($sign));
	return $sign;
}

function createSign_echo($paramArr, $appSecret)
{
	$sign = '<b style="color: #28a3ef">' . $appSecret . '</b>';
	ksort($paramArr);
	foreach ($paramArr as $key => $val) {
		if ($key != '' && $val != '') {
			$sign .= '<b style="color: #f92672">' . $key . '</b>' . $val;
		}
	}
	$sign .= '<b style="color: #28a3ef">' . $appSecret . '</b>';
	//$sign = strtoupper(md5($sign));
	return $sign;
}

//组参函数

function createStrParam($paramArr)
{

	$strParam = '';
	foreach ($paramArr as $key => $val) {
		if ($key != '' && $val != '') {
			$strParam .= $key . '=' . urlencode($val) . '&';
		}
	}
	return $strParam;
}


function imgReturnLink($imgSrc,$domain=""){
    //获取当前路径
    $link=_CONF("upload_link");
    empty($link) && $link=$domain;
    return $link."/".$imgSrc;
}



function pagination_tpl($url, $text, $active = '')
{
	$g_pagination_tpl = $active ? $active : '<a href="{url}">{text}</a>';
	return str_replace(['{url}', '{text}'], [$url, $text, $active], $g_pagination_tpl);
}

function pagination($url, $totalnum, $page, $pagesize = 20)
{
	$totalpage = ceil($totalnum / $pagesize);
	if ($totalpage < 2) return '';
	$page = min($totalpage, $page);
	$shownum = 2;    // 显示多少个页 * 2

	$start = max(1, $page - $shownum);
	$end = min($totalpage, $page + $shownum);

	// 不足 $shownum，补全左右两侧
	$right = $page + $shownum - $totalpage;
	$right > 0 && $start = max(1, $start -= $right);
	$left = $page - $shownum;
	$left < 0 && $end = min($totalpage, $end -= $left);

	$s = '';
	$page != 1 && $s .= pagination_tpl(str_replace('{page}', $page - 1, $url), '上一页', '');
	if ($start > 1) $s .= pagination_tpl(str_replace('{page}', 1, $url), '1 ' . ($start > 2 ? '...' : ''));
	for ($i = $start; $i <= $end; $i++) {
		$s .= pagination_tpl(str_replace('{page}', $i, $url), $i, $i == $page ? ' <span class="laypage-curr">' . $i . '</span>' : '');
	}
	if ($end != $totalpage) $s .= pagination_tpl(str_replace('{page}', $totalpage, $url), ($totalpage - $end > 1 ? '...' : '') . $totalpage);
	$page != $totalpage && $s .= pagination_tpl(str_replace('{page}', $page + 1, $url), '下一页');
	return $s;
}

// 简单的上一页，下一页，比较省资源，不用count(), 推荐使用，命名与 bootstrap 保持一致
function pager($url, $totalnum, $page, $pagesize = 20)
{
	$totalpage = ceil($totalnum / $pagesize);
	if ($totalpage < 2) return '';
	$page = min($totalpage, $page);

	$s = '';
	$page > 1 AND $s .= '<li><a href="' . str_replace('{page}', $page - 1, $url) . '">上一页</a></li>';
	$s .= " $page / $totalpage ";
	$totalnum >= $pagesize AND $page != $totalpage AND $s .= '<li><a href="' . str_replace('{page}', $page + 1, $url) . '">下一页</a></li>';
	return $s;
}


// hook public_function_after.php

?>