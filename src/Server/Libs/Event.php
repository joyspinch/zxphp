<?php

namespace Server\Libs;
/**
 * Class Websocket
 *
 * @property \Server\Server           $server
 * @property \swoole_websocket_server $http_server
 * @property \swoole_table            $fd_uid
 * @property \swoole_table            $uid_fd
 * @property \swoole_table            $gid_uid
 * @property \swoole_table            $uid_gid
 *
 *
 *
 * //IDE_LOAD_START
 *
 * //IDE_LOAD_END
 */
class Event
{
	private $server;
	private $http_server;
	private $fd_uid;
	private $uid_fd;
	private $gid_uid;
	private $uid_gid;
	private $tablepre;
	private $sid_key;

	public function __construct($server)
	{
		$this->server = $server;
		$this->http_server = $server->http_server;
		$this->fd_uid = $_ENV['table']->fd_uid;
		$this->uid_fd = $_ENV['table']->uid_fd;
		$this->uid_gid = $_ENV['table']->uid_gid;
		$this->gid_uid = $_ENV['table']->gid_uid;
		$this->tablepre = _CONF('cookie_tablepre');
		$this->sid_key = _CONF('session_id');
		empty($this->sid_key) AND $this->sid_key = 'session_id';
	}

	/**
	 * User: zhixiang
	 *  Explain:
	 *  -
	 *
	 * @param $name
	 *
	 * @return mixed
	 */
	public function __get($name)
	{
		if (isset($_ENV['_models'][$name])) {
			return $this->$name = $_ENV['_models'][$name];
		} else {
			throw new \Exception($name . 'Model禁止访问', '500');
		}
	}

	public function Join($data)
	{
		return $data;
	}

	/**
	 * @title  sendToAll
	 * 发送指定信息给所有客户端
	 *
	 * @param string $send_data
	 * @param array  $client_id_array
	 * 2020/5/30 11:04
	 */
	public function sendToAll(string $send_data, array $client_id_array = [])
	{
		// $this->server->connections 遍历所有websocket连接用户的fd，给所有用户推送
		$client_id_array = $client_id_array ? $client_id_array : $this->http_server->connections;
		$ret = false;
		foreach ($client_id_array as $fd) {
			// 需要先判断是否是正确的websocket连接，否则有可能会push失败
			if ($this->isOnline($fd)) {
				$ret = true;
				$this->http_server->push($fd, $send_data);
			} else {
				$this->closeClient($fd);
			}
		}
		return $ret;
	}

	/**
	 * @title  sendToClient
	 * 向指定客户端发送消息
	 *
	 * @param string $client_id
	 * @param string $send_data
	 * 2020/5/30 11:05
	 */
	public function sendToClient(string $client_id, string $send_data)
	{
		if ($this->isOnline($client_id)) {
			return $this->sendToAll($send_data, [$client_id]);
		} else {
			return $this->closeClient($client_id);
		}
	}

	/**
	 * @title  closeClient
	 * 关闭指定客户端
	 *
	 * @param string $client_id
	 * 2020/5/30 11:06
	 */
	public function closeClient(string $client_id)
	{
		$uid = $this->fd_uid->get($client_id, 'uid');
		$uid && $this->unbindUid($client_id, $uid);
		if ($this->isOnline($client_id)) {
			return $this->http_server->disconnect($client_id);
		}
		return true;
	}

	/**
	 * @title  isOnline
	 * 指定客服端是否在线
	 *
	 * @param string $client_id
	 *
	 * @return bool
	 * 2020/5/30 11:09
	 */
	public function isOnline(string $client_id)
	{
		return $this->http_server->isEstablished($client_id);
	}

	/**
	 * @title  bindUid
	 * 客户端绑定UID
	 *
	 * @param string $client_id
	 * @param string $uid
	 * 2020/5/30 11:10
	 */
	public function bindUid(string $client_id, string $uid)
	{
		$this->fd_uid->set($client_id, ['uid' => $uid]);
		$fd = $this->uid_fd->get($uid, 'fd');
		$fd = explode(',', $fd);
		$fd[] = $client_id;
		$fd = array_filter(array_unique($fd));
		$this->uid_fd->set($uid, ['fd' => implode(',', $fd)]);
		return count($fd);
	}

	/**
	 * @title  unbindUid
	 * 客户端解绑用户
	 *
	 * @param string $client_id
	 * @param string $uid
	 * 2020/5/30 15:33
	 */
	public function unbindUid(string $client_id, string $uid)
	{
		$this->fd_uid->del($client_id);
		$fd = $this->uid_fd->get($uid, 'fd');
		$fd = explode(',', $fd);
		$key = array_search($client_id, $fd);
		unset($fd[$key]);
		if (count($fd) < 1) {
			$this->uid_fd->del($uid);
		} else {
			$this->uid_fd->set($uid, ['fd' => implode(',', $fd)]);
		}
		return count($fd);
	}

	/**
	 * @title  isUidOnline
	 * 用户在线客户端
	 *
	 * @param string $uid
	 * 2020/5/30 15:33
	 */
	public function isUidOnline(string $uid)
	{
		$isonline = false;
		$fd = $this->uid_fd->get($uid, 'fd');
		$fd = explode(',', $fd);
		foreach ($fd as $client_id) {
			$this->isOnline($client_id) && $isonline = true;
		}
		return $isonline;
	}

	/**
	 * @title  getClientIdByUid
	 * UID查寻在线终端
	 *
	 * @param string $uid
	 * 2020/5/30 15:33
	 */
	public function getClientIdByUid(string $uid)
	{
		$fd = $this->uid_fd->get($uid, 'fd');
		return explode(',', $fd);
	}

	/**
	 * @title  getUidByClientId
	 * 终端对应UID
	 *
	 * @param string $client_id
	 * 2020/5/30 15:34
	 */
	public function getUidByClientId(string $client_id)
	{
		$uid = $this->fd_uid->get($client_id, 'uid');
		return $uid ? $uid : 0;
	}

	/**
	 * @title  sendToUid
	 * 向指定UID对应所有终端发送指定信息
	 *
	 * @param string $uid
	 * @param string $send_data
	 * 2020/5/30 15:34
	 */
	public function sendToUid(string $uid, string $send_data)
	{
		$fd = $this->getClientIdByUid($uid);
		return $this->sendToAll($send_data, $fd);
	}

	/**
	 * @title  joinGroup
	 * 客户端绑定分组
	 *
	 * @param string $client_id
	 * @param string $group
	 * 2020/5/30 15:35
	 */
	public function joinGroup(string $client_id, string $group)
	{
		$uid = $this->getUidByClientId($client_id);
		$uid_arr = $this->gid_uid->get($group, 'uid');
		$uid_arr = explode(',', $uid_arr);
		$uid_arr[] = $uid;
		$uid_arr = array_filter(array_unique($uid_arr));
		$this->gid_uid->set($group, ['uid' => implode(',', $uid_arr)]);
		$group_arr = $this->uid_gid->get($uid, 'gid');
		$group_arr = explode(',', $group_arr);
		$group_arr[] = $group;
		$group_arr = array_filter(array_unique($group_arr));
		$this->uid_gid->set($uid, ['gid' => implode(',', $group_arr)]);
	}

	/**
	 * @title  leaveGroup
	 * 客户端离开分组
	 *
	 * @param string $client_id
	 * @param string $group
	 * 2020/5/30 15:35
	 */
	public function leaveGroup(string $client_id, string $group)
	{
		$uid = $this->getUidByClientId($client_id);
		$uid_arr = $this->gid_uid->get($group, 'uid');
		$uid_arr = explode(',', $uid_arr);
		$key = array_search($uid, $uid_arr);
		unset($uid_arr[$key]);
		if (count($uid_arr) < 1) {
			$this->gid_uid->del($group);
		} else {
			$this->gid_uid->set($group, ['uid' => implode(',', $uid_arr)]);
		}

		$gid_arr = $this->uid_gid->get($uid, 'gid');
		$gid_arr = explode(',', $gid_arr);
		$key = array_search($group, $gid_arr);
		unset($gid_arr[$key]);
		if (count($gid_arr) < 1) {
			$this->uid_gid->del($uid);
		} else {
			$this->uid_gid->set($uid, ['gid' => implode(',', $gid_arr)]);
		}
	}


	public function ungroup(string $group)
	{

	}

	/**
	 * @title  sendToGroup
	 * 像指定组发送消息
	 *
	 * @param string $group
	 * @param string $send_data
	 * @param array  $exclude_client_id
	 * 2020/5/30 15:36
	 */
	public function sendToGroup(string $group, string $send_data, array $exclude_client_id = [])
	{
		$exclude_client_id = $exclude_client_id ? $exclude_client_id : $this->getClientIdListByGroup($group);
		return $this->sendToAll($send_data, $exclude_client_id);
	}

	/**
	 * @title  getUidListByGroup
	 * 查询组里所有UID
	 *
	 * @param string $group
	 * 2020/5/30 15:36
	 */
	public function getUidListByGroup(string $group)
	{
		$uid_arr = $this->gid_uid->get($group, 'uid');
		return explode(',', $uid_arr);
	}

	/**
	 * @title  getClientIdListByGroup
	 * 查询组里所有终端
	 *
	 * @param string $group
	 * 2020/5/30 15:36
	 */
	public function getClientIdListByGroup(string $group)
	{
		$uid_arr = $this->gid_uid->get($group, 'uid');
		$uid_arr = explode(',', $uid_arr);
		$fd = [];
		foreach ($uid_arr as $uid) {
			$fd = array_merge($fd, $this->getClientIdByUid($uid));
		}
		return $fd;
	}

	/**
	 * @title  getAllClientIdList
	 * 查询所有终端
	 *
	 * 2020/5/30 15:36
	 */
	public function getAllClientIdList()
	{
		//return $this->http_server->connections;
		$fdlist = [];
		foreach ($this->fd_uid as $fd => $row) {
			$fdlist[] = $fd;
		}
		return $fdlist;
	}

	/**
	 * @title  getAllUidList
	 * 查询所有UID
	 *
	 * 2020/5/30 15:37
	 */
	public function getAllUidList()
	{
		$uidlist = [];
		foreach ($this->uid_fd as $uid => $row) {
			$uidlist[] = $uid;
		}
		return $uidlist;
	}

	/**
	 * @title  getAllGroupList
	 * 查询所有组
	 *
	 * 2020/5/30 15:37
	 */
	public function getAllGroupList()
	{
		$group = [];
		foreach ($this->gid_uid as $gid => $row) {
			$group[] = $gid;
		}
		return $group;
	}

}