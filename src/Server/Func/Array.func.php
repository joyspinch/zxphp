<?php

function array_filter_empty_unique($array, $k)
{
	return array_filter_empty(array_unique(arrlist_values($array, $k)));
}

function array_value($arr, $key, $default = '')
{
	return isset($arr[$key]) ? $arr[$key] : $default;
}

function array_filter_empty($arr)
{
	foreach ($arr as $k => $v) {
		if (empty($v)) unset($arr[$k]);
	}
	return $arr;
}

// 从一个二维数组中对某一列求和
function arrlist_sum($arrlist, $key)
{
	if (!$arrlist) return 0;
	$n = 0;
	foreach ($arrlist as &$arr) {
		$n += $arr[$key];
	}
	return $n;
}

function array_addslashes(&$var)
{
	if (is_array($var)) {
		foreach ($var as $k => &$v) {
			array_addslashes($v);
		}
	} else {
		$var = addslashes($var);
	}

	return $var;
}

function array_stripslashes(&$var)
{
	if (is_array($var)) {
		foreach ($var as $k => &$v) {
			array_stripslashes($v);
		}
	} else {
		$var = stripslashes($var);
	}

	return $var;
}


/**
 * 多字段排序
 *
 * @return mixed|null
 * @throws Exception
 *
 *
	$_order = [];
	foreach ($order as $k => $v){
	$_order[]=$k;
	$_order[]=($v==-1?SORT_DESC:SORT_ASC);
	}
	$list = arrlist_sort_by_many_field($_list,...$_order);
 */
//$arr = arrlist_sort_by_many_field($array1,'id',SORT_ASC,'name',SORT_ASC,'age',SORT_DESC);
function arrlist_sort_by_many_field()
{
	$args = func_get_args();
	if (empty($args)) {
		return null;
	}
	$arr = array_shift($args);
	if (!is_array($arr)) {
		return null;
	}
	foreach ($args as $key => $field) {
		if (is_string($field)) {
			$temp = [];
			foreach ($arr as $index => $val) {
				$temp[$index] = $val[$field];
			}
			$args[$key] = $temp;
		}
	}
	$args[] = &$arr;//引用值
	call_user_func_array('array_multisort', $args);
	return array_pop($args);
}

function array_htmlspecialchars(&$var)
{
	if (is_array($var)) {
		foreach ($var as $k => &$v) {
			array_htmlspecialchars($v);
		}
	} else {
		$var = str_replace([
			'&',
			'"',
			'<',
			'>',
		], [
			'&amp;',
			'&quot;',
			'&lt;',
			'&gt;',
		], $var);
	}

	return $var;
}

function array_trim(&$var)
{
	if (is_array($var)) {
		foreach ($var as $k => &$v) {
			array_trim($v);
		}
	} else {
		$var = trim($var);
	}

	return $var;
}

// 比较数组的值，如果不相同则保留，以第一个数组为准
function array_diff_value($arr1, $arr2)
{
	foreach ($arr1 as $k => $v) {
		if (isset($arr2[$k]) && $arr2[$k] == $v) unset($arr1[$k]);
	}

	return $arr1;
}

/*
    $data = array();
    $data[] = array('volume' => 67, 'edition' => 2);
    $data[] = array('volume' => 86, 'edition' => 1);
    $data[] = array('volume' => 85, 'edition' => 6);
    $data[] = array('volume' => 98, 'edition' => 2);
    $data[] = array('volume' => 86, 'edition' => 6);
    $data[] = array('volume' => 67, 'edition' => 7);
    arrlist_multisort($data, 'edition', TRUE);
*/
// 对多维数组排序
function arrlist_multisort($arrlist, $col, $asc = true)
{
	$colarr = [];
	foreach ($arrlist as $k => $arr) {
		$colarr[$k] = $arr[$col];
	}
	$asc = $asc ? SORT_ASC : SORT_DESC;
	array_multisort($colarr, $asc, $arrlist);

	return $arrlist;
}

function fun_adm_each(&$array)
{
	$res = [];
	$key = key($array);
	if ($key !== null) {
		next($array);
		$res[1] = $res['value'] = $array[$key];
		$res[0] = $res['key'] = $key;
	} else {
		$res = false;
	}
	return $res;
}

// 对数组进行查找，排序，筛选，只支持一种条件排序
function arrlist_cond_orderby($arrlist, $cond = [], $orderby = [], $page = 1, $pagesize = 20)
{
	$resultarr = [];
	if (empty($arrlist)) return $arrlist;

	// 根据条件，筛选结果
	if ($cond) {
		foreach ($arrlist as $key => $val) {
			$ok = true;
			foreach ($cond as $k => $v) {
				if (!isset($val[$k]) || $val[$k] != $v) {
					$ok = false;
					break;
				}
			}
			if ($ok) $resultarr[$key] = $val;
		}
	} else {
		$resultarr = $arrlist;
	}

	if ($orderby) {
		[$k, $v] = fun_adm_each($orderby);
		$resultarr = arrlist_multisort($resultarr, $k, $v == 1);
	}

	$start = ($page - 1) * $pagesize;

	$resultarr = array_assoc_slice($resultarr, $start, $pagesize);

	return $resultarr;
}

function array_assoc_slice($arrlist, $start, $length = 0)
{
	if (isset($arrlist[0])) return array_slice($arrlist, $start, $length);
	$keys = array_keys($arrlist);
	$keys2 = array_slice($keys, $start, $length);
	$retlist = [];
	foreach ($keys2 as $key) {
		$retlist[$key] = $arrlist[$key];
	}

	return $retlist;
}


// 从一个二维数组中取出一个 key=>value 格式的一维数组
function arrlist_key_values($arrlist, $key, $value = NULL)
{
	$return = [];
	if ($key) {
		foreach ((array)$arrlist as $k => $arr) {
			$return[$arr[$key]] = $value ? $arr[$value] : $k;
		}
	} else {
		foreach ((array)$arrlist as $arr) {
			$return[] = $arr[$value];
		}
	}

	return $return;
}

/* php 5.5:
function array_column($arrlist, $key) {
    return arrlist_values($arrlist, $key);
}
*/

// 从一个二维数组中取出一个 values() 格式的一维数组，某一列key
function arrlist_values($arrlist, $key)
{
	if (!$arrlist) return [];
	$return = [];
	foreach ($arrlist as &$arr) {
		isset($arr[$key]) AND $return[] = $arr[$key];
	}
	$arr = null;
	return $return;
}

// 将 key 更换为某一列的值，在对多维数组排序后，数字key会丢失，需要此函数
function arrlist_change_key($arrlist, $key, $pre = '')
{
	$return = [];
	if (empty($arrlist)) return $return;
	foreach ($arrlist as &$arr) {
		$return[$pre . '' . $arr[$key]] = $arr;
	}
	//$arrlist = $return;
	$arr = null;
	return $return;
}

function arrlist_change_key_many($arrlist, $key, $pre = '')
{
	$return = [];
	if (empty($arrlist)) return $return;
	foreach ($arrlist as &$arr) {
		$return[$pre . '' . $arr[$key]] = $arr;
	}
	//$arrlist = $return;
	$arr = null;
	return $return;
}

// 根据某一列的值进行 chunk
function arrlist_chunk($arrlist, $key)
{
	$r = [];
	if (empty($arrlist)) return $r;
	foreach ($arrlist as &$arr) {
		!isset($r[$arr[$key]]) AND $r[$arr[$key]] = [];
		$r[$arr[$key]][] = $arr;
	}
	$arr = null;
	return $r;
}

// 根据某一列的值进行 push
function arrlist_push(&$arrlist, $key, $val)
{
	$r = [];
	foreach ($arrlist as $k => $arr) {
		$r[$k] = $arr;
		if ($k == $key) {
			foreach ($val as $_k => $_v) {
				$r[$_k] = $_v;
			}
		}
	}
	$arrlist = $r;

	return $r;
}

function arrlist_group($arrlist, $key)
{
	$r = [];
	if (empty($arrlist)) return $r;
	foreach ($arrlist as $k => $arr) {
		!isset($r[$arr[$key]]) AND $r[$arr[$key]] = [];
		$r[$arr[$key]][] = $arr;
	}
	unset($arrlist);
	return $r;
}

/**
 * User: zhixiang
 *  Explain:
 *  -  数组到树形,如果子分类ID比在父分类前面可能有问题
 *
 *
 * @param        $items
 * @param string $id
 * @param string $pid
 * @param string $son
 * @param bool   $key
 *
 * @return array|void
 */

function arrlist_tree($items, $id = 'id', $pid = 'pid', $son = 'son', $key = true)
{
	$tree = []; //格式化的树
	$tmpMap = [];  //临时扁平数据
	if (!is_array($items)) return;

	foreach ($items as $item) {
		$tmpMap[$item[$id]] = $item;
	}

	if ($key == false) {
		foreach ($items as $item) {
			if (isset($tmpMap[$item[$pid]])) {
				$tmpMap[$item[$pid]][$son][] = &$tmpMap[$item[$id]];
			} else {
				$tree[] = &$tmpMap[$item[$id]];
			}
		}
	} else {
		foreach ($items as $item) {
			if (isset($tmpMap[$item[$pid]])) {
				$tmpMap[$item[$pid]][$son][$item[$id]] = &$tmpMap[$item[$id]];
			} else {
				$tree[$item[$id]] = &$tmpMap[$item[$id]];
			}
		}
	}

	unset($tmpMap, $items);

	return $tree;
}


/**
 * 一维数据数组生成数据树
 *
 * @param array  $list 数据列表
 * @param string $id   父ID Key
 * @param string $pid  ID Key
 * @param string $son  定义子数据Key
 *
 * @return array
 */
function arr2tree($list, $id = 'id', $pid = 'pid', $son = 'sub')
{
	[$tree, $map] = [[], []];
	foreach ($list as $item) {
		$map[$item[$id]] = $item;
	}
	foreach ($list as $item) {
		if (isset($item[$pid]) && isset($map[$item[$pid]])) {
			$map[$item[$pid]][$son][] = &$map[$item[$id]];
		} else {
			$tree[] = &$map[$item[$id]];
		}
	}
	unset($map);
	return $tree;
}

/**
 * 一维数据数组生成数据树
 *
 * @param array  $list 数据列表
 * @param string $id   ID Key
 * @param string $pid  父ID Key
 * @param string $path
 * @param string $ppath
 *
 * @return array
 */
function arr2table(array $list, $id = 'id', $pid = 'pid', $path = 'path', $ppath = '')
{
	$tree = [];
	foreach (arr2tree($list, $id, $pid) as $attr) {
		$attr[$path] = "{$ppath}-{$attr[$id]}";
		$attr['sub'] = isset($attr['sub']) ? $attr['sub'] : [];
		$attr['spl'] = str_repeat("&nbsp;&nbsp;&nbsp;├&nbsp;&nbsp;", substr_count($ppath, '-'));
		$sub = $attr['sub'];
		unset($attr['sub']);
		$tree[] = $attr;
		if (!empty($sub)) {
			$tree = array_merge($tree, (array)arr2table($sub, $id, $pid, $path, $attr[$path]));
		}
	}

	return $tree;
}

/**
 * 获取数据树子ID
 *
 * @param array  $list 数据列表
 * @param int    $id   起始ID
 * @param string $key  子Key
 * @param string $pkey 父Key
 *
 * @return array
 */
function getArrSubIds($list, $id = 0, $key = 'id', $pkey = 'pid')
{
	$ids = [intval($id)];
	foreach ($list as $vo) {
		if (intval($vo[$pkey]) > 0 && intval($vo[$pkey]) === intval($id)) {
			$ids = array_merge($ids, getArrSubIds($list, intval($vo[$key]), $key, $pkey));
		}
	}

	return $ids;
}


// 从一个二维数组中取出一个 key=>value 格式的一维数组
function arrlist_key_item ($arrlist , $key) {
    $return = array ();
    if(empty($arrlist)){
        return [];
    }
    foreach ((array)$arrlist as $k => $arr) {
        $return[$arr[$key]] =$arr;
    }

    return $return;
}

?>