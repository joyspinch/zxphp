<?php

class Db
{

	public $conf = []; // 配置，可以支持主从

	private $wlink = NULL;  // 写连接
	private $rlink = NULL;  // 读连接
	private $link = NULL;   // 最后一次使用的连接
	private $errno = 0;
	private $maxnum = 0;
	private $errstr = '';
    private $max_pool = 100;

    public function __construct($conf)
    {
        $this->conf = $conf;
        $this->debug = $conf['debug'];
    }

    // 根据配置文件连接
    public function connect()
    {
        $this->rlink = $this->connect_master('r');
        $this->wlink = $this->connect_master('w');
        return $this->rlink;
    }

    // 连接写服务器
    public function connect_master($r)
    {
        if ($r == 'r') {
            if (!empty($this->rlink)) $this->rlink = null;
            $conf = $this->conf;
            $this->rlink = $this->real_connect($conf['server'], $conf['port'], $conf['username'], $conf['password'], $conf['database_name'], $conf['charset']);
            xn_log('Rlink ' . $conf['server'], 'db');
            return $this->rlink;
        } else {
            if (!empty($this->wlink)) $this->wlink = null;
            $conf = $this->conf;
            $this->wlink = $this->real_connect($conf['server'], $conf['port'], $conf['username'], $conf['password'], $conf['database_name'], $conf['charset']);
            xn_log('Wlink ' . $conf['server'], 'db');
            return $this->wlink;
        }
    }

    public function real_connect($host, $port, $user, $password, $name, $charset = '', $engine = '')
    {
        if (strpos($host, ':') !== false) {
            list($host, $port) = explode(':', $host);
        } else {
            empty($port) AND $port = 3306;
        }
        try {
            $attr = [
                PDO::ATTR_CASE => PDO::CASE_NATURAL,
                PDO::ATTR_PERSISTENT => true,
                PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_STRINGIFY_FETCHES => FALSE,
                PDO::ATTR_EMULATE_PREPARES => FALSE,
            ];
            $link = new PDO("mysql:host=$host;port=$port;dbname=$name", $user, $password, $attr);
        } catch (Exception $e) {
            $this->maxnum++;
            xn_log('连接数据库服务器失败: ' . $e->getMessage(), 'db');
            return false;
        }
        $charset AND $link->query("SET names $charset, sql_mode=''");
        $this->maxnum = 0;
        return $link;
    }

    public function sql_find_one($sql)
    {
        $query = $this->query($sql);
        if($this->debug==true){
            xn_log($sql,"db_query");
        }
        if (!$query) return $query;
        $query->setFetchMode(PDO::FETCH_ASSOC);
        return $query->fetch();
    }

    public function sql_find($sql, $key = NULL)
    {
        $query = $this->query($sql);
        if($this->debug==true){
            xn_log($sql,"db_query");
        }
        if (!$query) return $query;
        $query->setFetchMode(PDO::FETCH_ASSOC);
        $arrlist = $query->fetchAll();
        $key AND $arrlist = arrlist_change_key($arrlist, $key);
        return $arrlist;
    }

    public function find($table, $cond = [], $orderby = [], $page = 0, $pagesize = 0, $key = '', $col = [], $group = '')
    {
        $page = min(max(0, $page),200000000);
        $cond = db_cond_to_sqladd($cond);
        $orderby = db_orderby_to_sqladd($orderby);
        $offset = ($page > 0 || $pagesize > 0) ? 'LIMIT ' . (($page - 1) * $pagesize) . ',' . $pagesize : '';
        if (!empty($col)) {
            $cols = is_array($col) ? implode(',', $col) : $col;
        } else {
            $cols = '*';
        }

        return $this->sql_find("SELECT $cols FROM $table $cond $group $orderby $offset", $key);
    }

    public function find_one($table, $cond = [], $orderby = [], $col = [])
    {
        $cond = db_cond_to_sqladd($cond);
        $orderby = db_orderby_to_sqladd($orderby);
        if (!empty($col)) {
            $cols = is_array($col) ? implode(',', $col) : $col;
        } else {
            $cols = '*';
        }
        return $this->sql_find_one("SELECT $cols FROM $table $cond $orderby LIMIT 1");
    }


    public function query($sql)
    {
        if (!$this->rlink && !$this->connect_master('r')) return false;
        $this->link = $this->rlink;
        try {
            $t1 = $this->getMillisecond();
            $query = $this->rlink->query($sql);
            $t2 = $this->getMillisecond();
        } catch (Exception $e) {
            xn_log($e->getMessage() . "\t" . $sql, 'query_error');
            $this->error($e->getCode(), $e->getMessage());
            if (strpos($e->getMessage(), 'has gone away') !== false) {
                $this->rlink = null;
                return $this->query($sql);
            }
            return false;
        }
        $use_time = bcsub($t2, $t1, 5);
        if ($this->debug == true) xn_log($use_time . ' ' . $sql, 'query');
        if ($use_time > 1) xn_log($use_time . ' ' . $sql, 'slowquery');
        return $query;
    }

    public function exec($sql)
    {
        if (!$this->wlink && !$this->connect_master('w')) return false;
        $this->link = $this->wlink;

        try {
            $t1 = $this->getMillisecond();
            $n = $this->wlink->exec($sql); // 返回受到影响的行，插入的 id ?
            $t2 = $this->getMillisecond();
        } catch (Exception $e) {
            xn_log($e->getMessage() . "\t" . $sql, 'exec_error');
            $this->error($e->getCode(), $e->getMessage());
            if (strpos($e->getMessage(), 'has gone away') !== false && $this->maxnum < 10) {
                $this->maxnum += 1;
                $this->wlink = null;
                return $this->exec($sql);
            }
            return false;
        }

        $use_time = bcsub($t2, $t1, 5);
        if ($this->debug == true) xn_log($use_time . ' ' . $sql, 'exec');
        if ($use_time > 1) xn_log($use_time . ' ' . $sql, 'slowquery');
        if ($n !== false) {
            $pre = strtoupper(substr(trim($sql), 0, 7));
            if ($pre == 'INSERT ' || $pre == 'REPLACE') {
                return $this->last_insert_id();
            }
        } else {
            $this->error();
        }
        return $n;
    }

    public function count($table, $cond = [], $x = '*')
    {
        $cond = db_cond_to_sqladd($cond);
        $sql = "SELECT COUNT($x) AS num FROM $table $cond";
        $arr = $this->sql_find_one($sql);
        return !empty($arr) ? intval($arr['num']) : $arr;
    }

    public function Max($table, $field, $cond = [])
    {
        $sqladd = db_cond_to_sqladd($cond);
        $sql = "SELECT MAX($field) AS maxid FROM $table $sqladd";
        $arr = $this->sql_find_one($sql);
        return !empty($arr) ? intval($arr['maxid']) : $arr;
    }

    public function truncate($table)
    {
        return $this->exec("TRUNCATE $table");
    }

    public function sum($table, $sum, $cond = [])
    {
        $sqladd = db_cond_to_sqladd($cond);
        $sql = "SELECT sum($sum) AS num FROM $table $sqladd";
        $r = $this->sql_find_one($sql);
        return !empty($r['num']) ? $r['num'] : 0;
    }

    public function create($table, $arr)
    {
        return $this->insert($table, $arr);
    }

    public function insert($table, $arr)
    {
        $sqladd = db_array_to_insert_sqladd($arr);
        if (!$sqladd) return false;
        return $this->exec("INSERT INTO $table $sqladd");
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
    public function insert_batch($table, $arr)
    {
        if (!$arr) return false;
        $_arr = [];
        $columns = [];
        foreach ($arr as $data) {
            foreach ($data as $key => $value) {
                $columns[] = $key;
            }
        }
        $columns = array_unique($columns);
        foreach ($arr as $k => $v) {
            $sql_arr = [];
            foreach ($columns as $_v) {
                $sql_arr[] = $v[$_v];
            }
            $_arr[] = '(\'' . implode('\',\'', $sql_arr) . '\')';
        }
        //echo "INSERT INTO $table (`" . implode( '`, `' , $term ) . "`) VALUES " . implode( ',' , $_arr );
        return $this->exec("INSERT INTO $table (`" . implode('`, `', $columns) . "`) VALUES " . implode(',', $_arr));
    }

    public function replace($table, $arr)
    {
        $sqladd = db_array_to_insert_sqladd($arr);
        if (!$sqladd) return false;
        return $this->exec("REPLACE INTO $table $sqladd");
    }

    public function update($table, $cond, $update)
    {
        $condadd = db_cond_to_sqladd($cond);
        $sqladd = db_array_to_update_sqladd($update);
        if (!$sqladd) return false;
        return $this->exec("UPDATE $table SET $sqladd $condadd");
    }

    public function delete($table, $cond)
    {
        $condadd = db_cond_to_sqladd($cond);
        return $this->exec("DELETE FROM $table $condadd");
    }

    public function last_insert_id()
    {
        return $this->link->lastinsertid();
    }

    public function version()
    {
        $r = $this->sql_find_one("SELECT VERSION() AS v");
        return $r['v'];
    }

    // 设置错误。
    public function error($errno = 0, $errstr = '')
    {
        $error = $this->link ? $this->link->errorInfo() : [
            0,
            $errno,
            $errstr,
        ];
        $errno = $errno ? $errno : (isset($error[1]) ? $error[1] : 0);
        $errstr = $errstr ? $errstr : (isset($error[2]) ? $error[2] : '');
        xn_log($errno . ' ' . $errstr, 'db_error');
    }


    public function __destruct()
    {
        $this->wlink = NULL;
        $this->rlink = NULL;
    }

    public function getMillisecond()
    {
        [$usec, $sec] = explode(" ", microtime());
        return bcadd($usec, $sec, 6);
    }

}

?>