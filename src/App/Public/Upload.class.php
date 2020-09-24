<?php

class Upload
{
	protected static $accessKey;

	protected static $secretKey;

	protected static $auth = null;

	//TODO 空间域名 Domain
	protected static $uploadUrl;

	//TODO 存储空间名称  公开空间
	protected static $storageName;
	protected static $type;

	public static function autoInfo()
	{
		self::$accessKey = _CONF('upload_accessKey', '');
		self::$secretKey = _CONF('upload_secretKey', '');
		self::$uploadUrl = str_replace(['http://', 'https://'], '', _CONF('upload_domain', ''));
		self::$storageName = _CONF('upload_name', '');
		self::$type = _CONF('upload_type');
		switch (self::$type) {
			case 2://七牛
				if (self::$auth == null) self::$auth = new \Qiniu\Auth(self::$accessKey, self::$secretKey);
				break;
			case 3://阿里oss
				if (self::$auth == null) {
					self::$auth = new \OSS\OssClient(self::$accessKey, self::$secretKey, self::$uploadUrl);
					if (!self::$auth->doesBucketExist(self::$storageName)) self::$auth->createBucket(self::$storageName, self::$auth::OSS_ACL_TYPE_PUBLIC_READ_WRITE);
				}
				break;
			default:
				self::$auth = [];
		}
		return self::$auth;
	}

	public static function returnToken()
	{
		$auto = self::autoInfo();
		//七牛

		$token = $auto->uploadToken(self::$storageName);
		return [
			"token" => $token,
			"uploadUrl" => self::$uploadUrl,
			"storageName" => self::$storageName,
		];
	}


	/**
	 * @title  upload_file
	 * $filePath 本地完整路径
	 * $key   上传后的文件名
	 * @return array|null
	 * 2020/4/21 11:24
	 */
	public static function upload_file($file, $first)
	{
		$auto = self::autoInfo();
		$_filename = self::filename($file, $first);
		switch (self::$type) {
			case 2://七牛
				$token = $auto->uploadToken(self::$storageName);
				$uploadMgr = new \Qiniu\Storage\UploadManager();
				$ret = $uploadMgr->putFile($token, $_filename['filename'], $file['tmp_name']);
				break;
			case 3://阿里oss
				$ret = $auto->uploadFile(self::$storageName, $_filename['filename'], $file['tmp_name']);
				break;
			default:
		}
		$ret = copy($file['tmp_name'], __WEBDIR__ . $_filename['filename']);
		is_file($file['tmp_name']) && unlink($file['tmp_name']);
		$_filename['is_upload'] = $ret;
		return $_filename;
	}

	public static function upload_by_id($ftype, $file, $id)
	{
		$auto = self::autoInfo();
		$_filename = self::filename_by_id($ftype, $file, $id);
		$ret = '';
		switch (self::$type) {
			case 2://七牛
				$token = $auto->uploadToken(self::$storageName);
				$uploadMgr = new \Qiniu\Storage\UploadManager();
				$ret = $uploadMgr->putFile($token, $_filename['filename'], $file['tmp_name']);
				break;
			case 3://阿里oss
				$ret = $auto->uploadFile(self::$storageName, $_filename['filename'], $file['tmp_name']);
				break;
			default:
		}
		$ret_hd = copy($file['tmp_name'], __WEBDIR__ . $_filename['filename']);
		is_file($file['tmp_name']) && unlink($file['tmp_name']);
		$_filename['is_upload_hd'] = $ret_hd;
		$_filename['is_upload'] = $ret;
		return $_filename;
	}

	public static function delete_file($filename)
	{
		switch (self::$type) {
			case 2://七牛
				$bucketManager = new \Qiniu\Storage\BucketManager(self::autoInfo());
				return $bucketManager->delete(self::$storageName, $filename);
				break;
			case 3://阿里oss
				return self::autoInfo()->deleteObject(self::$storageName, $filename);
				break;
			default:
				return unlink(__WEBDIR__ . $filename);
		}
	}


// 获取安全的文件名，如果文件存在，则加时间戳和随机数，避免重复
	public static function image_safe_name($filename, $dir)
	{
		$attach = $_ENV['Attach'];
		$time = time();
		// 最后一个 . 保留，其他的 . 替换
		$s1 = substr($filename, 0, strrpos($filename, '.'));
		$s2 = substr(strrchr($filename, '.'), 1);

		!in_array($s2, $attach['all']) AND $s2 = '_' . $s2;

		if (is_file($dir . "$s1.$s2")) {
			$newname = $s1 . $time . rand(1, 1000) . '.' . $s2;
		} else {
			$newname = "$s1.$s2";
		}
		return $newname;
	}


	public static function filename($file, $first)
	{
		$attach = $_ENV['Attach'];
		mt_srand();
		$dir = date('Ym/');
		$ext = self::file_ext($file['name']);
		if (in_array($ext, $attach['image'])) {
			$s = file_read($file['tmp_name']);
			if (strpos($s, '<script') !== FALSE) {
				unset($s);
				return '图片不安全';
			}
		}
		$name = $first . '_' . time() . mt_rand(11111, 99999) . '.' . $ext;
		$destfile = self::image_safe_name($name, __WEBDIR__ . $dir);
		$url = '/' . $dir . $destfile;
		!is_dir(__WEBDIR__ . $dir) AND mkdir(__WEBDIR__ . $dir, 0777, 1);
		return ['src' => $url, 'uploadUrl' => self::$uploadUrl, 'filename' => $dir . $destfile];
	}
	public static function get_filename_by_id($ftype,$id,$ext)
	{
		empty(self::$uploadUrl) && self::$uploadUrl = rtrim(str_replace(['http://', 'https://'], '', _CONF('upload_domain', '')),'/');
		$_id = sprintf("%09d", $id);
		$s1 = substr($_id, 0, 3);
		$s2 = substr($_id, 3, 3);
		$dir = "{$ftype}/{$s1}/{$s2}/";
		$name = $id . '.' . $ext;
		return ['src' => '/' . $dir . $name, 'uploadUrl' => self::$uploadUrl.'/' .$dir . $name];
	}

	public static function filename_by_id($ftype, $file, $id)
	{
		$attach = $_ENV['Attach'];
		mt_srand();
		$_id = sprintf("%09d", $id);
		$s1 = substr($_id, 0, 3);
		$s2 = substr($_id, 3, 3);
		$dir = "$ftype/$s1/$s2/";
		$ext = self::file_ext($file['name']);
		if (in_array($ext, $attach['image'])) {
			$s = file_read($file['tmp_name']);
			if (strpos($s, '<script') !== FALSE) {
				unset($s);
				return '图片不安全';
			}
		}
		$name = $id . '.' . $ext;
		$destfile = self::image_safe_name($name, __WEBDIR__ . $dir);
		$url = '/' . $dir . $name;
		!is_dir(__WEBDIR__ . $dir) AND mkdir(__WEBDIR__ . $dir, 0777, 1);
		return ['src' => $url, 'uploadUrl' => self::$uploadUrl, 'filename' => $dir . $destfile];
	}


	public static function file_ext($filename, $max = 16)
	{
		$ext = strtolower(substr(strrchr($filename, '.'), 1));
		$ext = xn_urlencode($ext);
		strlen($ext) > $max AND $ext = substr($ext, 0, $max);
		if (!preg_match('#^\w+$#', $ext)) $ext = 'attach';
		return $ext;
	}

}

?>