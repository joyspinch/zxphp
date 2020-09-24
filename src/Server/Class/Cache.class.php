<?php
/**
 * Class Cache
 *
 * @property Redis $link
 */
class Cache
{

	private $conf = [];
	private $link = false;
	private $db = '';
	private $cachepre = '';
	private $maxnum = 0;
	private $max_pool = 100;

	public function __construct($conf = [])
	{
		if (!extension_loaded('Redis')) {
			exit('Redis 扩展没有加载');
		}
		$this->conf = $conf;
		$this->cachepre = isset($conf['cachepre']) ? $conf['cachepre'] : 'pre:';
		$this->db = isset($conf['select']) ? $conf['select'] : '1';
	}

	public function connect()
	{
		if (empty($this->link)) {
			try {
				$this->link = new Redis();
				$this->link->connect($this->conf['host'], $this->conf['port']);
			} catch (Exception $e) {
				$this->maxnum++;
				$this->link = null;
				xn_log('连接 Redis 服务器失败', 'cache_error');
				sleep(1);
				return false;
			}
		}
		$this->maxnum = 0;
		if ($this->conf['password']) {
			$this->link->auth($this->conf['password']);
		}
		$this->link->select($this->db);
		return $this->link;
	}


	public function select($db)
	{
		if (!$this->link && !$this->connect()) return false;
		try {
			$this->db = $db;
			$r = $this->link->select($db);
		} catch (\Exception $e) {
			if ($this->isBreak($e)) {
				$this->link = null;
				$this->connect();
				return $this->select($db);
			}
			xn_log('[ ERR ] Message:[ ' . $e->getMessage() . ' ]', 'cache_error');
		}
		return $r === false ? NULL : $r;
	}

	protected function isBreak($e)
	{
		$info = [
			'lost',
		];

		$error = $e->getMessage();

		foreach ($info as $msg) {
			if (false !== stripos($error, $msg)) {
				return true;
			}
		}
		return false;
	}

	public function flushall()
	{
		return $this->link->flushall();
	}

	public function smembers($db)
	{
		if (!$this->link && !$this->connect()) return false;
		try {
			$r = $this->link->SMEMBERS($db);
		} catch (\Exception $e) {
			if ($this->isBreak($e)) {
				$this->link = null;
				$this->connect();
				return $this->SMEMBERS($db);
			}
			xn_log('[ ERR ] Message:[ ' . $e->getMessage() . ' ] smembers ' . $db, 'cache_error');
		}
		return $r === false ? NULL : $r;
	}

	public function geopos($key = 'geo::user',...$member)
	{
		if (!$this->link && !$this->connect()) return false;
		try {
			$_k = $this->cachepre . $key;
			$r = $this->link->geopos($_k, ...$member);
		} catch (\Exception $e) {
			if ($this->isBreak($e)) {
				$this->link = null;
				$this->connect();
				return $this->geopos($key,...$member);
			}
			xn_log('[ ERR ] Message:[ ' . $e->getMessage() . ' ] geopos ' . $_k, 'cache_error');
		}
		return $r;
	}
	public function geodist($member1,$member2,$unit, $key)
	{
		if (!$this->link && !$this->connect()) return false;
		try {
			$_k = $this->cachepre . $key;
			$r = $this->link->geodist($_k, $member1,$member2,$unit);
		} catch (\Exception $e) {
			if ($this->isBreak($e)) {
				$this->link = null;
				$this->connect();
				return $this->geodist($member1,$member2,$unit, $key);
			}
			xn_log('[ ERR ] Message:[ ' . $e->getMessage() . ' ] geodist ' . $_k, 'cache_error');
		}
		return $r;
	}

	public function geoadd($longitude, $latitude, $member, $key)
	{
		if (!$this->link && !$this->connect()) return false;

		try {
			$_k = $this->cachepre . $key;
			$r = $this->link->geoadd($_k, $longitude, $latitude, $member);
		} catch (\Exception $e) {
			if ($this->isBreak($e)) {
				$this->link = null;
				$this->connect();
				return $this->geoadd($longitude, $latitude, $member, $key);
			}
			xn_log('[ ERR ] Message:[ ' . $e->getMessage() . ' ] geoadd ' . $_k, 'cache_error');
		}
		return $r;
	}

	public function georadius($longitude, $latitude, $radius , $unit , $withdist , $sort , $key)
	{
		if (!$this->link && !$this->connect()) return false;
		try {
			$_k = $this->cachepre . $key;
			$r = $this->link->georadius($_k, $longitude, $latitude, $radius, $unit, ['withdist' => $withdist, 'sort' => $sort]);
			//半径50000、单位m/km可选、距离显示、排序规则
		} catch (\Exception $e) {
			if ($this->isBreak($e)) {
				$this->link = null;
				$this->connect();
				return $this->georadius($longitude, $latitude, $radius, $unit, $withdist, $sort, $key);
			}
			xn_log('[ ERR ] Message:[ ' . $e->getMessage() . ' ] georadius ' . $_k, 'cache_error');
		}
		return $r;
	}

	public function georadiusbymember($member, $radius = 3, $unit = 'km', $withdist = true, $sort = 'asc', $key = 'geo::user')
	{
		if (!$this->link && !$this->connect()) return false;
		try {
			$_k = $this->cachepre . $key;
			$r = $this->link->georadiusbymember($_k, $member, $radius, $unit, ['withdist' => $withdist, 'sort' => $sort]);
			//半径50000、单位m/km可选、距离显示、排序规则
		} catch (\Exception $e) {
			if ($this->isBreak($e)) {
				$this->link = null;
				$this->connect();
				return $this->georadiusbymember($member, $radius, $unit, $withdist, $sort, $key);
			}
			xn_log('[ ERR ] Message:[ ' . $e->getMessage() . ' ] georadiusbymember ' . $_k, 'cache_error');
		}
		return $r;
	}


	public function set($k, $v, $life = 0)
	{
		if (!$this->link && !$this->connect()) return false;

		try {
			$_k = $this->cachepre . $k;
			$_v = xn_json_encode($v);
			$r = $this->link->set($_k, $_v);
			$life AND $r AND $this->link->expire($_k, $life);
		} catch (\Exception $e) {
			if ($this->isBreak($e)) {
				$this->link = null;
				$this->connect();
				return $this->set($k, $v, $life);
			}
			xn_log('[ ERR ] Message:[ ' . $e->getMessage() . ' ] set ' . $_k, 'cache_error');
		}
		return $r;
	}

	public function get($k, $json = 1)
	{
		if (!$this->link && !$this->connect()) return false;
		try {
			$_k = $this->cachepre . $k;
			$r = $this->link->get($_k);

		} catch (\Exception $e) {

			if ($this->isBreak($e)) {
				$this->link = false;
				$this->connect();
				return $this->get($k);
			}
			xn_log('[ ERR ] Message:[ ' . $e->getMessage() . ' ] get ' . $_k, 'cache_error');
		}

		if ($json == 1) {
			return $r === false ? NULL : json_decode($r, true);
		} else {
			return $r === false ? NULL : $r;
		}
	}

	public function ttl($k)
	{
		if (!$this->link && !$this->connect()) return false;
		try {
			$_k = $this->cachepre . $k;
			$r = $this->link->ttl($_k);
		} catch (\Exception $e) {
			if ($this->isBreak($e)) {
				$this->link = null;
				$this->connect();
				return $this->ttl($k);
			}
			xn_log('[ ERR ] Message:[ ' . $e->getMessage() . ' ] ttl ' . $_k, 'cache_error');
		}
		return $r === false ? NULL : $r;
	}

	public function inc($k, $amount = 1)
	{
		if (!$this->link && !$this->connect()) return false;
		try {
			$_k = $this->cachepre . $k;
			$r = $this->link->incrby($_k, $amount);
		} catch (\Exception $e) {
			if ($this->isBreak($e)) {
				$this->link = null;
				$this->connect();
				return $this->inc($k);
			}
			xn_log('[ ERR ] Message:[ ' . $e->getMessage() . ' ] inc ' . $_k, 'cache_error');
		}
		return $r === false ? NULL : $r;
	}

	public function dec($k, $amount = 1)
	{
		if (!$this->link && !$this->connect()) return false;
		try {
			$_k = $this->cachepre . $k;
			$r = $this->link->decrby($_k, $amount);
		} catch (\Exception $e) {
			if ($this->isBreak($e)) {
				$this->link = null;
				$this->connect();
				return $this->dec($k);
			}
			xn_log('[ ERR ] Message:[ ' . $e->getMessage() . ' ] dec ' . $_k, 'cache_error');
		}
		return $r === false ? NULL : $r;
	}

	public function llen($k)
	{
		if (!$this->link && !$this->connect()) return false;
		try {
			$_k = $this->cachepre . $k;
			$r = $this->link->llen($_k);
		} catch (\Exception $e) {
			if ($this->isBreak($e)) {
				$this->link = null;
				$this->connect();
				return $this->llen($k);
			}
			xn_log('[ ERR ] Message:[ ' . $e->getMessage() . ' ] llen ' . $_k, 'cache_error');
		}
		return $r === false ? NULL : $r;
	}

	public function lpop($k)
	{
		if (!$this->link && !$this->connect()) return false;
		try {
			$_k = $this->cachepre . $k;
			$r = $this->link->lpop($_k);
		} catch (\Exception $e) {
			if ($this->isBreak($e)) {
				$this->link = false;
				$this->connect();
				return $this->lpop($k);
			}
			xn_log('[ ERR ] Message:[ ' . $e->getMessage() . ' ] lpop ' . $_k, 'cache_error');
		}

		return $r === false ? NULL : $r;
	}

	public function lpush($k, $data)
	{
		if (!$this->link && !$this->connect()) return false;
		try {
			$_k = $this->cachepre . $k;
			$r = $this->link->lpush($_k, $data);
		} catch (\Exception $e) {
			if ($this->isBreak($e)) {
				$this->link = false;
				$this->connect();
				return $this->lpush($k, $data);
			}
			xn_log('[ ERR ] Message:[ ' . $e->getMessage() . ' ] lpush ' . $_k, 'cache_error');
		}

		return $r ? true : false;
	}

	public function rpush($k, $data)
	{
		if (!$this->link && !$this->connect()) return false;
		try {
			$_k = $this->cachepre . $k;
			$r = $this->link->rpush($_k, $data);
		} catch (\Exception $e) {
			if ($this->isBreak($e)) {
				$this->link = false;
				$this->connect();
				return $this->rpush($k, $data);
			}
			xn_log('[ ERR ] Message:[ ' . $e->getMessage() . ' ] rpush ' . $_k, 'cache_error');
		}
		return $r ? true : false;
	}

	public function delete($k)
	{
		if (!$this->link && !$this->connect()) return false;
		try {
			$_k = $this->cachepre . $k;
			$r = $this->link->del($_k);
		} catch (\Exception $e) {
			if ($this->isBreak($e)) {
				$this->link = false;
				$this->connect();
				return $this->delete($k);
			}
			xn_log('[ ERR ] Message:[ ' . $e->getMessage() . ' ] rpush ' . $_k, 'cache_error');
		}
		return $r ? true : false;
	}

	public function truncate()
	{
		if (!$this->link && !$this->connect()) return false;
		try {
			$r = $this->link->flushdb();
		} catch (\Exception $e) {
			if ($this->isBreak($e)) {
				$this->link = false;
				$this->connect();
				return $this->truncate();
			}
			xn_log('[ ERR ] Message:[ ' . $e->getMessage() . ' ] truncate', 'cache_error');
		}
		return $r;
	}

	public function __destruct()
	{
		$this->link = false;
	}
}
?>