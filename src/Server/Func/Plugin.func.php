<?php
//$_ENV['g_include_slot_kv'] = [];
function _include($srcfile)
{
	static $len;
	global $server_worker;

	$len = $len ? $len : strlen(dirname(__ROTDIR__));
	$tmpfile = __TMPDIR__ . $server_worker->http_server->worker_id . substr(str_replace('/', '_', $srcfile), $len);
	if (!is_file($srcfile)) {
		return __APPDIR__ . 'ErrorView/NoFile.php';
	}

	if (!is_file($tmpfile) || (_CONF('debug') == 1 && '.html' == strrchr($srcfile, '.'))) {
		$s = plugin_compile_srcfile($srcfile);
		if (empty($s)) return $tmpfile;
//		$_ENV['g_include_slot_kv'] = [];
//        for ($i = 0; $i < 3; $i++) {
//            $s = preg_replace_callback('#<template\sinclude="(.*?)">(.*?)</template>#is', '_include_callback_1', $s);
//            if (strpos($s, '<template') === FALSE) break;
//        }
//		unset($_ENV['g_include_slot_kv']);
		!empty($s) AND file_put_contents_try($tmpfile, $s);
	}
	return $tmpfile;
}

//function _include_callback_1($m)
//{
//	$r = file_get_contents_try($m[1]);
//	preg_match_all('#<slot\sname="(.*?)">(.*?)</slot>#is', $m[2], $m2);
//	if (!empty($m2[1])) {
//		$kv = array_combine($m2[1], $m2[2]);
//		$_ENV['g_include_slot_kv'] = array_merge($_ENV['g_include_slot_kv'],$kv);
//		foreach ($_ENV['g_include_slot_kv'] as $slot => $content) {
//			$r = preg_replace('#<slot\sname="' . $slot . '"\s*/>#is', $content, $r);
//		}
//	}
//	return $r;
//}

// 在安装、卸载插件的时候，需要先初始化
function plugin_init()
{
	$plugin_paths = glob(__PLUDIR__ . '*', GLOB_ONLYDIR);
	if (is_array($plugin_paths)) {
		$plugins = [];
		foreach ($plugin_paths as $path) {
			$dir = file_name($path);
			$conffile = $path . "/conf.json";
			if (!is_file($conffile)) continue;
			$arr = xn_json_decode(file_get_contents_try($conffile));

			if (empty($arr)) continue;
			!isset($arr['rank']) AND $arr['rank'] = 0;
			//var_dump($arr);
			$plugins[$dir] = $arr;
			// 额外的信息
			$plugins[$dir]['hooks'] = [];
			$hookpaths = glob(__PLUDIR__ . "$dir/hook/*.*"); // path
			if (is_array($hookpaths)) {
				foreach ($hookpaths as $hookpath) {
					$hookname = file_name($hookpath);
					$plugins[$dir]['hooks'][$hookname] = $hookpath;
				}
			}

			// 本地 + 线上数据
			$plugins[$dir] = plugin_read_by_dir($dir, $plugins[$dir]);
		}

		//$plugins = arrlist_multisort($plugins,'rank');
		$_ENV['plugins'] = $plugins;
		//arrlist_multisort($plugins,'rank',FALSE);
	}
}

// 插件依赖检测，返回依赖的插件列表，如果返回为空则表示不依赖
/*
	返回依赖的插件数组：
	array(
		'xn_ad'=>'1.0',
		'xn_umeditor'=>'1.0',
	);
*/
function plugin_dependencies($dir)
{

	$plugin = $_ENV['plugins'][$dir];
	$dependencies = $plugin['dependencies'];
	// 检查插件依赖关系
	$arr = [];
	foreach ($dependencies as $_dir => $version) {
		if (!isset($_ENV['plugins'][$_dir]) || !$_ENV['plugins'][$_dir]['enable']) {
			$arr[$_dir] = $version;
		}
	}
	return $arr;
}

/*
	返回被依赖的插件数组：
	array(
		'xn_ad'=>'1.0',
		'xn_umeditor'=>'1.0',
	);
*/
function plugin_by_dependencies($dir)
{
	$plugins = $_ENV['plugins'];
	$arr = [];
	foreach ($plugins as $_dir => $plugin) {
		if (isset($plugin['dependencies'][$dir]) && $plugin['enable']) {
			$arr[$_dir] = $plugin['version'];
		}
	}
	return $arr;
}

function plugin_enable($dir)
{
	$plugins = $_ENV['plugins'];
	if (!isset($plugins[$dir])) {
		return FALSE;
	}
	$plugins[$dir]['enable'] = 1;
	file_replace_var(__PLUDIR__ . "$dir/conf.json", ['enable' => 1], TRUE);
	plugin_clear_tmp_dir();
	return TRUE;
}


function plugin_check_dependency($dir, $action = 'install')
{
	$plugins = $_ENV['plugins'];
	$name = $plugins[$dir]['name'];
	if ($action == 'install') {
		$arr = plugin_dependencies($dir);
		if (!empty($arr)) {
			$s = plugin_dependency_arr_to_links($arr);
			$msg = ['name' => $name, 's' => $s];
			return [-1, $msg];
		}
	} else {
		$arr = plugin_by_dependencies($dir);
		if (!empty($arr)) {
			$s = plugin_dependency_arr_to_links($arr);
			$msg = ['name' => $name, 's' => $s];
			return [-1, $msg];
		}
	}
}

function plugin_dependency_arr_to_links($arr)
{
	$plugins = $_ENV['plugins'];
	$s = '';
	foreach ($arr as $dir => $version) {
		//if(!isset($plugins[$dir])) continue;
		$name = isset($plugins[$dir]['name']) ? $plugins[$dir]['name'] : $dir;
		$url = "../plugin/read?dir=$dir";
		$s .= " <a href=\"$url\">【{$name}】</a> ";
	}
	return $s;
}

// 清空插件的临时目录
function plugin_clear_tmp_dir()
{
	rmdir_recusive(__TMPDIR__, TRUE);
}

function plugin_disable($dir)
{
	$plugins = $_ENV['plugins'];
	if (!isset($plugins[$dir])) {
		return FALSE;
	}
	$plugins[$dir]['enable'] = 0;
	file_replace_var(__PLUDIR__ . "$dir/conf.json", ['enable' => 0], TRUE);
	plugin_clear_tmp_dir();
	return TRUE;
}

// 安装所有的本地插件
function plugin_install_all()
{
	$plugins = $_ENV['plugins'];
	foreach ($plugins as $dir => $plugin) {
		plugin_install($dir);
	}
}

// 卸载所有的本地插件
function plugin_unstall_all()
{
	$plugins = $_ENV['plugins'];
	foreach ($plugins as $dir => $plugin) {
		plugin_unstall($dir);
	}
}

/*
	插件安装：
		把所有的插件点合并，重新写入文件。如果没有备份文件，则备份一份。
		插件名可以为源文件名：view/header.htm
*/
function plugin_install($dir)
{
	$plugins = $_ENV['plugins'];
	if (!isset($plugins[$dir])) {
		return FALSE;
	}

	$plugins[$dir]['installed'] = 1;
	$plugins[$dir]['enable'] = 1;

	// 写入配置文件
	file_replace_var(__PLUDIR__ . "$dir/conf.json", ['installed' => 1, 'enable' => 1], TRUE);
	plugin_clear_tmp_dir();
	return TRUE;
}

// copy from plugin_install 修改
function plugin_unstall($dir)
{
	$plugins = $_ENV['plugins'];
	if (!isset($plugins[$dir])) {
		return FALSE;
	}

	$plugins[$dir]['installed'] = 0;
	$plugins[$dir]['enable'] = 0;

	// 写入配置文件
	file_replace_var(__PLUDIR__ . "$dir/conf.json", ['installed' => 0, 'enable' => 0], TRUE);
	plugin_clear_tmp_dir();
	return TRUE;
}

function plugin_paths_enabled()
{
	$return_paths = [];
	$plugin_paths = glob(__PLUDIR__ . '*', GLOB_ONLYDIR);
	if (empty($plugin_paths)) return [];
	foreach ($plugin_paths as $path) {
		$conffile = $path . "/conf.json";
		if (!is_file($conffile)) continue;
		$pconf = xn_json_decode(file_read($conffile));
		if (empty($pconf)) continue;
		if (empty($pconf['enable']) || empty($pconf['installed'])) continue;
		//!isset($pconf['rank']) AND $pconf['rank']=0;
		$return_paths[$path] = $pconf;
	}
	//$return_paths=arrlist_multisort($return_paths,'rank',false);
	return $return_paths;
}

// 编译源文件，把插件合并到该文件，不需要递归，执行的过程中 include _include() 自动会递归。
function plugin_compile_srcfile($srcfile)
{
	// 判断是否开启插件
	if (!empty(_CONF('disabled_plugin'))) {
		$s = file_read($srcfile);
		return $s;
	}

	$s = file_read($srcfile);
	// 最多支持 10 层
	for ($i = 0; $i < 10; $i++) {
		if (strpos($s, '<!--{hook') !== FALSE || strpos($s, '// hook') !== FALSE) {
			$s = preg_replace('#<!--{hook\s+(.*?)}-->#', '// hook \\1', $s);
			$s = preg_replace_callback('#//\s*hook\s+(\S+)#is', 'plugin_compile_srcfile_callback', $s);
		} else {
			break;
		}
	}
	return $s;
}

function plugin_get_hook()
{
	$hooks = [];
	$plugin_paths = plugin_paths_enabled();
	foreach ($plugin_paths as $path => $pconf) {
		$dir = file_name($path);
		$hookpaths = glob(__PLUDIR__ . "$dir/hook/*.*"); // path
		if (is_array($hookpaths)) {
			foreach ($hookpaths as $hookpath) {
				$hookname = file_name($hookpath);
				$rank = isset($pconf['hooks_rank']["$hookname"]) ? $pconf['hooks_rank']["$hookname"] : 999;
				$hooks[$hookname][] = ['hookpath' => $hookpath, 'rank' => $rank];
			}
		}
	}
	foreach ($hooks as $hookname => $arrlist) {
		$arrlist = arrlist_multisort($arrlist, 'rank', FALSE);
		$hooks[$hookname] = arrlist_values($arrlist, 'hookpath');
	}
	$_ENV['hooks'] = $hooks;
}

function plugin_get_model()
{
	$_models = [];
	$plugin_model_file = [];
	$plugin_paths = plugin_paths_enabled();
	foreach ($plugin_paths as $path => $pconf) {
		$dir = file_name($path);
		$models = glob(__PLUDIR__ . "$dir/Model/*Model.php"); // path
		foreach ($models as $model) {
			$name = str_replace([__PLUDIR__ . "$dir/Model/", 'Model.php'], '', $model);
			$_models[] = $name;
			$plugin_model_file[$name] = $model;
		}
		$models = glob(__PLUDIR__ . "$dir/Model/*/*Model.php"); // path
		foreach ($models as $model) {
			$name = str_replace([dirname($model ). "/", 'Model.php'], '', $model);
			$_models[] = $name;
			$plugin_model_file[$name] = $model;
		}
	}
	$_ENV['plugin_model_files'] = $plugin_model_file;
	return $_models;
}

function plugin_get_controller()
{

	$plugin_controllers_file = [];
	$plugin_view_file = [];
	$plugin_paths = plugin_paths_enabled();

	foreach ($plugin_paths as $path => $pconf) {
		$dir = file_name($path);

		$controllerpaths = glob(__PLUDIR__ . "$dir/Controller/*.php"); // path
		if (is_array($controllerpaths)) {
			foreach ($controllerpaths as $controllerpath) {
				$name = str_replace([__PLUDIR__ . "$dir/Controller/", '.php'], '', $controllerpath);
				$plugin_controllers_file['\\' . str_replace('/', '\\', $name)] = $controllerpath;
			}
		}

		$controllerpaths = glob(__PLUDIR__ . "$dir/Controller/*/*.php"); // path
		if (is_array($controllerpaths)) {
			foreach ($controllerpaths as $controllerpath) {
				$name = str_replace([__PLUDIR__ . "$dir/Controller/", '.php'], '', $controllerpath);
				$plugin_controllers_file['\\' . str_replace('/', '\\', $name)] = $controllerpath;
			}
		}
	}
	$_ENV['plugin_controllers_files'] = $plugin_controllers_file;

	foreach ($plugin_paths as $path => $pconf) {
		$dir = file_name($path);
		$viewpaths = glob(__PLUDIR__ . "$dir/Controller/*/View/*.html"); // path
		if (is_array($viewpaths)) {
			foreach ($viewpaths as $viewpath) {
				$name = str_replace(__PLUDIR__ . "$dir/Controller/", '', $viewpath);
				$plugin_view_file[$name] = $viewpath;
			}
		}
	}

	$_ENV['plugin_view_files'] = $plugin_view_file;
}


function plugin_compile_srcfile_callback($m)
{
	$hooks = $_ENV['hooks'];
	$s = '';
	$hookname = $m[1];
	if (!empty($hooks[$hookname])) {
		$fileext = file_ext($hookname);
		foreach ($hooks[$hookname] as $path) {
			$t = file_read($path);
			if ($fileext == 'php' && preg_match('#^\s*<\?php\s+exit;#is', $t)) {
				// 正则表达式去除兼容性比较好。
				$t = preg_replace('#^\s*<\?php\s*exit;(.*?)(?:\?>)?\s*$#is', '\\1', $t);
			}
			$s .= $t;
		}
	}
	return $s;
}

// 安装，卸载，禁用，更新
function plugin_read_by_dir($dir, $local, $local_first = TRUE)
{
//    $plugins = $_ENV['plugins'];
//    $local = array_value($plugins, $dir, array());
//    if (empty($local)) return array();
	// 本地插件信息
	!isset($local['rank']) && $local['rank'] = 0;
	!isset($local['name']) && $local['name'] = '';
	!isset($local['price']) && $local['price'] = 0;
	!isset($local['brief']) && $local['brief'] = '';
	!isset($local['version']) && $local['version'] = '1.0';
	!isset($local['zx_version']) && $local['zx_version'] = '1.0';
	!isset($local['installed']) && $local['installed'] = 0;
	!isset($local['enable']) && $local['enable'] = 0;
	!isset($local['hooks']) && $local['hooks'] = [];
	!isset($local['hooks_rank']) && $local['hooks_rank'] = [];
	!isset($local['dependencies']) && $local['dependencies'] = [];
	!isset($local['setting_url']) && $local['setting_url'] = '';
	!isset($local['install_url']) && $local['install_url'] = '';

	// 额外的判断
	$local['icon_url'] = "../../zxadmin/plugin/icon?dir=$dir";
	$local['dir'] = $dir;

	return $local;
}

?>