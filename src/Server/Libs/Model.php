<?php

namespace Server\Libs;

/**
 * Class Model
 *
 * @package Server\Libs
 * @property \Db            $db
 * @property \Cache         $cache
 * @property \Server\Server $server
 * @property \swoole_server $http_server
 * //IDE_LOAD_START
 *
 * //IDE_LOAD_END
 */
class Model
{

	private $db;
	private $cache;
	private $server;
	private $http_server;

	private $columns;
	private $columns_not_null;
	private $columns_rpi;

	private $with;
	private $install_rule;
	private $update_rule;

	public $link = 'db';
	public $table;
	public $is_delete = '';
	public $index;
	public $create_at;
	public $update_at;
	public $status;
	public $create_at_type;
	public $update_at_type;

	public $create_at_default;
	public $update_at_default;
	public $status_default;


	/**
	 * @title  add_with
	 *
	 * @param string $table
	 * @param string $from
	 * @param string $to
	 * @param string $key_name
	 * 2020/8/17 13:37
	 */
	public function add_with($table, $from, $to, $select='*', $key_name='')
	{
		$this->with[] = [$table, $from, $to, $select,$key_name];
	}


	public function install_rule_set($rule)
	{
		return $this->install_rule=$rule;
	}

	public function update_rule_set($rule)
	{
		return $this->update_rule=$rule;
	}

	public function install_rule_get()
	{
		return $this->install_rule;
	}

	public function update_rule_get()
	{
		return $this->update_rule;
	}


	public function __construct($server)
	{
		$this->server = $server;
		$this->http_server = $server->http_server;
		$this->db = $_ENV['db_class'][$this->link];
		$this->cache = $_ENV['cache_class'];
		$this->columns = $this->columns_fmt();

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

	public function columns_fmt()
	{
		$data = $this->show_columns();
		foreach ($data as $key => $row) {
			$row['Key'] == 'PRI' && $this->columns_rpi[] = $row['Field'];
			$row['Null'] == 'NO' && $this->columns_not_null[] = $row['Field'];
			empty($this->is_delete) && $row['Field'] == 'is_delete' && $this->is_delete = $row['Field'];
			empty($this->status) && $row['Field'] == 'status' && $this->status = $row['Field'] && $this->status_default = $row['Default'] == NULL ? 0 : $row['Default'];
			empty($this->create_at) && $row['Field'] == 'create_at' && $this->create_at = $row['Field'];
			empty($this->update_at) && $row['Field'] == 'update_at' && $this->update_at = $row['Field'];

		}
		return $data;
	}

	public function CacheSet($k, $v, $life = 0)
	{
		return $this->cache->set($k, $v, $life);
	}

	public function CacheGet($k)
	{
		return $this->cache->get($k);
	}

	public function CacheFlushAll()
	{
		return $this->cache->flushall();
	}

	public function CacheDel($k)
	{
		return $this->cache->delete($k);
	}

	public function CacheSelect($id)
	{
		return $this->cache->select($id);
	}

	public function CacheLlen($id)
	{
		return $this->cache->llen($id);
	}

	public function CacheLpop($id)
	{
		return $this->cache->lpop($id);
	}

	public function CacheRpush($k, $data)
	{
		return $this->cache->rpush($k, $data);
	}

	public function CacheLpush($k, $data)
	{
		return $this->cache->lpush($k, $data);
	}

	public function CacheSmembers($id)
	{
		return $this->cache->smembers($id);
	}

	//GEOPOS：从 Key 里面返回所有给定位置对象的位置(经度和纬度)。
	public function CacheGeoPos($key = 'geo::user',...$member)
	{
		return $this->cache->geopos($key,...$member);
	}

	//GEOADD：将给定的位置对象(纬度、经度、名字)添加到指定的 Key。
	public function CacheGeoAdd($longitude, $latitude, $member, $key = 'geo::user')
	{
		return $this->cache->geoadd($longitude, $latitude, $member, $key);
	}

	//GEODIST：返回两个给定位置之间的距离。
	public function CacheGeoDist($member1, $member2, $unit = 'km', $key = 'geo::user')
	{
		return $this->cache->geodist($member1, $member2, $unit, $key);
	}

	//GEORADIUS：以给定的经纬度为中心，返回目标集合中与中心的距离不超过给定最大距离的所有位置对象。
	public function CacheGeoRadius($longitude, $latitude, $radius = 3, $unit = 'km', $withdist = true, $sort = 'asc', $key = 'geo::user')
	{
		return $this->cache->georadius($longitude, $latitude, $radius, $unit, $withdist, $sort, $key);
	}

	//GEORADIUSBYMEMBER：以给定的位置对象为中心，返回与其距离不超过给定最大距离的所有位置对象。
	public function CacheGeoRadiusMember($member, $radius = 3, $unit = 'km', $withdist = true, $sort = 'asc', $key = 'geo::user')
	{
		return $this->cache->georadiusbymember($member, $radius, $unit, $withdist, $sort, $key);
	}

	public function CacheTruncate()
	{
		return $this->cache->truncate();
	}

	public function show_tables($name)
	{
		return $this->db->sql_find('SHOW TABLE STATUS FROM ' . $name);
	}

	public function show_columns()
	{
		return $this->db->sql_find('SHOW FULL COLUMNS FROM ' . $this->table);
	}

	public function query($sql)
	{
		return $this->db->sql_find($sql);
	}

	public function find_one($cond, $order = [], $select = '*')
	{
		!empty($this->is_delete) && !isset($cond[$this->is_delete]) AND $cond[$this->is_delete] = 0;
		return $this->db->find_one($this->table, $cond, $order, $select);
	}

	public function find_one_with($cond, $order = [], $select = '*')
	{
		!empty($this->is_delete) && !isset($cond[$this->is_delete]) AND $cond[$this->is_delete] = 0;
		$data = $this->db->find_one($this->table, $cond, $order, $select);
		if ($data) {
			foreach ($this->with as $_row) {
				if (!empty($_row[4])) {
					$data[$_row[4]] = $this->db->find_one($_row[0], [$_row[1] => $data[$_row[2]]],$_row[3]);
				} else {
					$data += $this->db->find_one($_row[0], [$_row[1] => $data[$_row[2]]],$_row[3]);
				}
			}
		}
		return $data;
	}

	public function count($cond = [], $select = '*')
	{
		!empty($this->is_delete) && !isset($cond[$this->is_delete]) AND $cond[$this->is_delete] = 0;
		return $this->db->count($this->table, $cond, $select);
	}

	public function sum($sum, $cond)
	{
		!empty($this->is_delete) && !isset($cond[$this->is_delete]) AND $cond[$this->is_delete] = 0;
		return $this->db->sum($this->table, $sum, $cond);
	}

	public function Max($sum, $cond)
	{
		//!empty($this->is_delete) && !isset($cond[$this->is_delete]) AND $cond[$this->is_delete]=0;
		return $this->db->Max($this->table, $sum, $cond);
	}

	public function update($cond, $data)
	{
		!empty($this->is_delete) && !isset($cond[$this->is_delete]) AND $cond[$this->is_delete] = 0;
		$this->update_at && !$data[$this->update_at] && $data[$this->update_at] = time();
		return $this->db->update($this->table, $cond, $data);
	}

	public function select($cond = [], $order = [], $select = '*', $page = 0, $limit = 0, $key = '', $group = '')
	{
		if (is_string($select) && $select != '*') {
			$select = explode(',', $select);
		}
		!empty($this->is_delete) && !isset($cond[$this->is_delete]) AND $cond[$this->is_delete] = 0;
		return $this->db->find($this->table, $cond, $order, $page, $limit, $key, $select, $group);
	}

	public function delete($cond)
	{
		if (!empty($this->is_delete)) {
			$data = [$this->is_delete => 1];
			$this->update_at && $data[$this->update_at] = time();
			return $this->db->update($this->table, $cond, $data);
		} else {
			return $this->db->delete($this->table, $cond);
		}
	}

	public function insert($data)
	{
		$this->create_at && !$data[$this->create_at] && $data[$this->create_at] = time();
		$this->update_at && !$data[$this->update_at] && $data[$this->update_at] = time();
		return $this->db->insert($this->table, $data);
	}


	/**
	 * User: zhixiang
	 *  Explain:
	 *  - 批量插入数据
	 *
	 * @param      $table 表名
	 * @param      $arr   array('字段1'=>数据1,'字段2'=>数据2)
	 * @param null $d
	 *
	 * @return bool|int|mixed|resource
	 */

	public function insertALL($data)
	{
		return $this->db->insert_batch($this->table, $data);
	}

	public function read($cond, $select = '*')
	{
		return $this->find_one($cond, [], $select);
	}


	public function GetListRpi($where, $order = [], $page = 1, $limit = 10, $select = '*', $ispage = 1, $group = '')
	{
		!empty($this->is_delete) && !isset($where[$this->is_delete]) AND $where[$this->is_delete] = 0;
		$index = $this->columns_rpi ? $this->columns_rpi : $this->index;
		$index_list = $this->select($where, $order, $index, $page, $limit, '', $group);
		$list = !empty($index_list) ? $this->select([implode(',',$index) => $index_list], [], $select, 0, 0) : [];
		$_order = [];
		foreach ($order as $k => $v){
			$_order[]=$k;
			$_order[]=($v==-1?SORT_DESC:SORT_ASC);
		}
		$_order && $list = arrlist_sort_by_many_field($list,...$_order);
		if ($ispage == 1) {
			$result['page'] = max(1, $page);
			$result['rows'] = $limit;
			$result['total'] = $this->count($where, !empty($this->index) ? $this->index : 1);
			$result['results'] = $list;
			$result['next_page'] = ceil($result['total'] / $limit) > $result['page'];
		} else {
			$result = $list;
		}
		return $result;
	}


	public function GetListWith($where, $order = [], $page = 1, $limit = 10, $select = '*', $ispage = 1, $group = '')
	{
		!empty($this->is_delete) && !isset($where[$this->is_delete]) AND $where[$this->is_delete] = 0;
		if (!empty($this->index)) {
			$index_list = $this->select($where, $order, $this->index, $page, $limit, $this->index, $group);
			$index_list_index = array_keys($index_list);
			$_list = !empty($index_list_index) ? $this->select([$this->index => $index_list_index], [], $select, 0, 0, $this->index) : [];
			$list = [];
			foreach ($index_list as $k => $v) {
				!empty($_list[$k]) AND $list[] = $_list[$k];
			}
			unset($index_list, $index_list_index, $_list);
		} else {
			$list = $this->select($where, $order, $select, $page, $limit, '', $group);
		}


		foreach ($this->with as $_row) {
			if (!empty($_row[4])) {
				foreach ($list as &$row){

				}
				$data[$_row[4]] = $this->db->find($_row[0], [$_row[1] => $data[$_row[2]]],[],0,0,'',$_row[3]);
			} else {
				$data += $this->db->find($_row[0], [$_row[1] => $data[$_row[2]]],[],0,0,'',$_row[3]);
			}
		}

		if ($ispage == 1) {
			$result['page'] = max(1, $page);
			$result['rows'] = $limit;
			$result['total'] = $this->count($where, !empty($this->index) ? $this->index : 1);
			$result['results'] = $list;
			$result['next_page'] = ceil($result['total'] / $limit) > $result['page'];
		} else {
			$result = $list;
		}
		return $result;
	}

	public function GetList($where, $order = [], $page = 1, $limit = 10, $select = '*', $ispage = 1, $group = '')
	{
		!empty($this->is_delete) && !isset($where[$this->is_delete]) AND $where[$this->is_delete] = 0;
		$list = $this->select($where, $order, $select, $page, $limit, '', $group);
		if ($ispage != 1) {
			$result = $list;
		} else {
			$result['page'] = min(max(1, $page), 200000000);
			$result['rows'] = $limit;
			$result['total'] = $this->count($where, !empty($this->index) ? $this->index : 1);
			$result['results'] = $list;
			$result['next_page'] = ceil($result['total'] / $limit) > $result['page'];
		}
		return $result;
	}

	/**
	 * User: zhixiang
	 *  Explain:
	 *  -
	 *
	 * @param array  $table [ ['table'=>'user b','join'=>'left','and'=>'a.uid=b.uid']  ]
	 * @param array  $order
	 * @param int    $page
	 * @param int    $limit
	 * @param string $select
	 * @param int    $ispage
	 *
	 * @return array|bool
	 */
	public function GetWithList($join = [], $cond = [], $order = [], $page = 1, $limit = 10, $select = '*', $ispage = 1, $group = '')
	{
		$str = [];
		foreach ($join as $v) {
			$str[] = (empty($v['join']) ? 'left' : $v['join']) . ' join ' . $v['table'] . ' on ' . $v['and'];
		}
		if ($ispage == 1) {
			$result['page'] = max(1, $page);
			$result['rows'] = $limit;
			$result['total'] = $this->db->count($this->table . ' a ' . implode(' ', $str), $cond, !empty($this->index) ? 'a.' . $this->index : 1);
			$result['results'] = $this->db->find($this->table . ' a ' . implode(' ', $str), $cond, $order, $page, $limit, '', $select, $group);
			$result['next_page'] = ceil($result['total'] / $limit) > $result['page'];
		} else {
			if ($page == 0 && $limit == 1) {
				$result = $this->db->find_one($this->table . ' a ' . implode(' ', $str), $cond, $order, $select);
			} else {
				$result = $this->db->find($this->table . ' a ' . implode(' ', $str), $cond, $order, $page, $limit, '', $select, $group);
			}
		}
		return $result;
	}

	public function insertGetId($data)
	{
		return $this->insert($data);
	}

}

?>