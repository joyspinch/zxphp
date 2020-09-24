<?php

// hook common_start.php

function ConfigReload()
{
	// hook common_ConfigReload_brfore.php
	$conf = include __CONDIR__ . __LV__ . '_config.php';
	$conf['url_suffix'] = explode('|', $conf['url_suffix']);
	$_ENV['conf'] = $conf;

	$_ENV['Mines'] = include _include(__CONDIR__ . 'Mines.php');
	$_ENV['Smtp'] = include _include(__CONDIR__ . 'Smtp.php');
	$_ENV['Module'] = include _include(__CONDIR__ . 'Module.php');
	$_ENV['conf']['config_tabs'] = include _include(__CONDIR__ . 'Setting.php');
	$_ENV['ErrorCode'] = include _include(__CONDIR__ . 'ErrorCode.php');
	$_ENV['Attach'] = include _include(__CONDIR__ . 'Attach.php');

	if (!empty($_ENV['conf']['db'])) {
		$data = $_ENV['db_class']['db']->find($_ENV['conf']['db']['prefix'].'config');
		foreach ($data as &$v) {
			if ($v['is_json']) {
				$v['value'] = xn_json_decode($v['value']);
			}
		}
		$config = arrlist_key_values($data, 'name', 'value');
		$_ENV['conf'] = array_merge($_ENV['conf'], $config);
		$menu = $_ENV['db_class']['db']->find($_ENV['conf']['db']['prefix'].'menu', []);
		foreach ($menu as $v) {
			$v['node'] = str_replace('/', '_', $v['node']);
			$_ENV['auth'][strtolower($v['node'])] = [
				'is_login' => $v['is_login'],
				'is_auth' => $v['is_auth'],
			];
			$_ENV['auth'][strtolower($v['node'] . '_' . $v['method'])] = [
				'is_login' => $v['is_login'],
				'is_auth' => $v['is_auth'],
			];
		}
	}

	$_ENV['dispatcher'] = FastRoute\simpleDispatcher(function (FastRoute\RouteCollector $r) {
		// hook common_route_before.php

		//$r->addRoute(['GET', 'POST'], 'forum-{fid:\d+}[-{page:\d+}]', 'index/forum/index');

		// hook common_route_after.php
	});

	// hook common_ConfigReload_after.php
}

function IncludeFiles(\Server\Server $serv)
{

	// hook common_IncludeFiles_brfore.php

	require_once _include(__APPDIR__ . 'Model.php');
	require_once _include(__APPDIR__ . 'Controller/Controller.php');

	// hook common_IncludeFiles_public_before.php

	$include_public_files = glob(__PUBDIR__ . '*.php');
	foreach ($include_public_files as $public_file) {
		require_once _include($public_file);
	}


	// hook common_IncludeFiles_model_before.php

	$is_load = [];
	$_ENV['_models'];
	foreach ($_ENV['plugin_model_files'] as $k => $model_file) {
		$is_load[$k] = 1;
		require_once _include($model_file);
		$model = "\\Model\\{$k}Model";
		$_ENV['_models'][$k] = new $model($serv);
	}

	$include_model_files = glob(__APPDIR__ . 'Task/*.php');
	foreach ($include_model_files as $model_file) {
		require_once _include($model_file);
	}

	$include_model_files = glob(__APPDIR__ . 'Model/*.php');
	foreach ($include_model_files as $model_file) {
		$name = str_replace([__APPDIR__ . "Model/", 'Model.php'], '', $model_file);
		if (!isset($is_load[$name])) {
			require_once _include($model_file);
			$model = "\\Model\\{$name}Model";
			$_ENV['_models'][$name] = new $model($serv);
		}
	}
	$include_model_files = glob(__APPDIR__ . 'Model/*/*.php');
	foreach ($include_model_files as $model_file) {
		$name = str_replace([dirname($model_file) . "/", 'Model.php'], '', $model_file);
		if (!isset($is_load[$name])) {
			require_once _include($model_file);
			$model = "\\Model\\{$name}Model";
			$_ENV['_models'][$name] = new $model($serv);
		}
	}
	if($serv->http_server->worker_id==0){
		rmdir_recusive(__COLDIR__, TRUE);

		foreach ($_ENV['_models'] as $_k => $_table){
			if($_table instanceof \Server\Libs\Model) {
				$show_columns=$_table->show_columns();

				if(empty($show_columns)){
					continue;
				}

				$code="<?php\r\nclass ".$_k.'_columns {';
				foreach ($show_columns as $_column){
						$code.="\r\n\tpublic \$".$_column['Field'].' = '.var_export($_column,1).';';
				}
				$code.="\r\n}\r\n?>";
				file_put_contents(__COLDIR__.$_k.'_columns.php',$code);
			}
		}
	}

	// hook common_IncludeFiles_controller_before.php

	$controller_files = glob(__APPDIR__ . 'Controller/*.php');
	foreach ($controller_files as $controller_file) {
		if ($controller_file == __APPDIR__ . 'Controller/Controller.php') {
			continue;
		}
		require_once _include($controller_file);
	}

	$is_load = [];
	foreach ($_ENV['plugin_controllers_files'] as $k => $controllers_file) {
		$is_load[$k] = 1;
		require_once _include($controllers_file);
	}

	$controllers_files = glob(__APPDIR__ . "Controller/*/*.php"); // path
	if (is_array($controllers_files)) {
		foreach ($controllers_files as $k => $controllers_file) {
			$name = str_replace([__APPDIR__ . "Controller/", '.php'], '', $controllers_file);
			$name = '\\' . str_replace('/', '\\', $name);
			if (!isset($is_load[$name])) {
				require_once _include($controllers_file);
			}
		}
	}
	// hook common_IncludeFiles_after.php
}


// hook common_end.php
?>