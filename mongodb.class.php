<?php 



//MongoDB
class DB {
	private $config_file = 'MongoDB';
	private $connection;
	private $db;
	private $connection_string;
	private $collection = '';
	private $host;
	private $port;
	private $user;
	private $pass;
	private $dbname;
	private $key;
	private $persist;
	private $persist_key;
	private $selects = array();
	private $wheres = array();
	private $sorts = array();
	private $page_sorts = array();
	private $limit = 999999;
	private $offset = 0;
	
	
	/*
	* 自動檢查是否已安裝/啟用 PECL 擴展。
	* 建立連接 MongoDB 。
	*/
	
	public function __construct($MONGODB_CONFIG){
	
		if(!class_exists('Mongo')){		
			$this->$this->MessageBox("The MongoDB PECL extension has not been installed or enabled", 500);
		}
		
		$this->connection_string($MONGODB_CONFIG);
		
		$this->connect();
	}
 
 	/*
	*
	*使用中生成的連接字符串建立到MongoDB的連接
	* connection_string（）方法。如果'mongo_persist_key'被設置為true
	*配置文件，建立一個持久連接。我們只允許“堅持”
	*選項被設置，因為我們要立即建立連接。
	*/
	private function connect() {
		$options = array();
		if($this->persist === TRUE){
			$options['persist'] = isset($this->persist_key) && !empty($this->persist_key) ? $this->persist_key : 'ci_mongo_persist';
		}
		
		try{
			$this->connection = new Mongo($this->connection_string, $options);
			$this->db = $this->connection->{$this->dbname};
			return($this); 
		} catch(MongoConnectionException $e) {
			$this->$this->MessageBox("Unable to connect to MongoDB: {$e->getMessage()}", 500);
		}
	}
	
	/*
	*
	* 從配置資料庫連接參數。
	*/
	
	private function connection_string($MONGODB_CONFIG) {
	
		$this->host = trim($MONGODB_CONFIG['HOST']);
		$this->port = trim($MONGODB_CONFIG['PORT']);
		$this->user = trim($MONGODB_CONFIG['USER']);
		$this->pass = trim($MONGODB_CONFIG['PWD']);
		$this->dbname = trim($MONGODB_CONFIG['DATABASE']);
		$this->persist = trim($MONGODB_CONFIG['PERSIST']);
		$this->persist_key = trim($MONGODB_CONFIG['PERSIST_KEY']);
		$connection_string = "mongodb://";
		
		if(empty($this->host)){
			$this->MessageBox("The Host must be set to connect to MongoDB", 500);
		}
		
		if(empty($this->dbname)){
			$this->MessageBox("The Database must be set to connect to MongoDB", 500);
		}
		
		if(!empty($this->user) && !empty($this->pass)){
			$connection_string .= "{$this->user}:{$this->pass}@";
		}
		
		if(isset($this->port) && !empty($this->port)){
			$connection_string .= "{$this->host}:{$this->port}/{$this->dbname}";
		} else {
			$connection_string .= "{$this->host}";
		}
		$this->connection_string = trim($connection_string);
	}
 
 
 
 
 
	/*
	*
	*
	* 從預設資料庫切換到其他 db
	*
	*
	*/
	
	public function switch_db($database = ''){
	
		if(empty($database)){
			$this->MessageBox("To switch MongoDB databases, a new database name must be specified", 500);
		}
		$this->dbname = $database;
		try{
			$this->db = $this->connection->{$this->dbname};
			return(TRUE);
			
		}catch(Exception $e){
			$this->MessageBox("Unable to switch Mongo Databases: {$e->getMessage()}", 500);
		}
	}
	
	
	/**
	*
	* 確定在查詢過程中要包含的欄位或要排除的域。
	* 目前, 包括和排除在同一時間是不可用的, 所以
	* $includes 陣列將優先于 $excludes 陣列。 如果要
	* 只選擇要排除的欄位, 保留 $includes an empty array()。
	*
	* @usage: $this->mongo_db->select(array('foo', 'bar'))->get('foobar');
	*/
	
	public function select($includes = array(), $excludes = array()){
	
		if(!is_array($includes)){		
			$includes = array();
		}
		
		if(!is_array($excludes)){	
			$excludes = array();
		}
		
		if(!empty($includes)){		
			foreach($includes as $col){		
				$this->selects[$col] = 1;
			}
		} else {
			foreach($excludes as $col){
				$this->selects[$col] = 0;
			}
		}
		return($this);
	}

	/*
	*
	* 根據這些搜索參數獲取文檔。 $wheres 陣列應
	* 是一個以欄位為鍵的關聯陣列, 作為搜索的值
	* 標準.
	*
	* @usage = $this->mongo_db->where(array('foo' => 'bar'))->get('foobar');
	*/
	
	public function where($wheres = array()){	
		foreach($wheres as $wh => $val){	
			$this->wheres[$wh] = $val;
		}
		return($this);
	}


	/*
	*
	* 獲取 $field 的值在給定 $in array().
	*
	* @usage = $this->mongo_db->where_in('foo', array('bar', 'zoo', 'blah'))->get('foobar');
	*/
	
	public function where_in($field = "", $in = array()){
		$this->where_init($field);
		$this->wheres[$field]['$in'] = $in;
		return($this);
	}

	/*
	*
	* 獲取 $field 的值不在給定 $in array().
	*
	* @usage = $this->mongo_db->where_not_in('foo', array('bar', 'zoo', 'blah'))->get('foobar');
	*/
	
	public function where_not_in($field = "", $in = array()){	
		$this->where_init($field);
		$this->wheres[$field]['$nin'] = $in;
		return($this);
	}

	/*
	*
	* 獲取文檔的值 $field 大於 $x
	*
	* @usage = $this->mongo_db->where_gt('foo', 20);
	*/

	public function where_gt($field = "", $x){
		$this->where_init($field);
		$this->wheres[$field]['$gt'] = $x;
		return($this);
	}

	/*
	*
	* 獲取文檔的值 $field 大於或等於 $x
	*
	* @usage = $this->mongo_db->where_gte('foo', 20);
	*/
	
	public function where_gte($field = "", $x){
		$this->where_init($field);
		$this->wheres[$field]['$gte'] = $x;
		return($this);
	}

	/*
	*
	* 獲取文檔的值 $field 小於 $x
	*
	* @usage = $this->mongo_db->where_lt('foo', 20);
	*/
	
	public function where_lt($field = "", $x){
		$this->where_init($field);
		$this->wheres[$field]['$lt'] = $x;
		return($this);
	}
	
	/*
	*
	* 獲取文檔的值 $field 小於或等於 $x
	*
	* @usage = $this->mongo_db->where_lte('foo', 20);
	*/
		
	public function where_lte($field = "", $x){
		$this->where_init($field);
		$this->wheres[$field]['$lte'] = $x;
		return($this);
	}
  
  
  
	/*
	*
	* 獲取文檔的值 $field 介於 $x and $y
	*
	* @usage = $this->mongo_db->where_between('foo', 20, 30);
	*/
	public function where_between($field = "", $x, $y){
		$this->where_init($field);
		$this->wheres[$field]['$gte'] = $x;
		$this->wheres[$field]['$lte'] = $y;
		return($this);
	}
	
	/*
	*
	* 獲取文檔的值 $field 介於但不等於 $x and $y
	*
	* @usage = $this->mongo_db->where_between_ne('foo', 20, 30);
	*/

	public function where_between_ne($field = "", $x, $y){
		$this->where_init($field);
		$this->wheres[$field]['$gt'] = $x;
		$this->wheres[$field]['$lt'] = $y;
		return($this);
	}
	
	/*
	*
	* 獲取文檔的值 $field 不等於 $x
	*
	* @usage = $this->mongo_db->where_between('foo', 20, 30);
	*/
	
	public function where_ne($field = "", $x){
		$this->where_init($field);
		$this->wheres[$field]['$ne'] = $x;
		return($this);
	}
	
	/*
	*
	* 獲取文檔的值 $field 在一個或多個值中
	*
	* @usage = $this->mongo_db->where_or('foo', array( 'foo', 'bar', 'blegh' );
	*/
	
	public function where_or($field = "", $values){
		$this->where_init($field);
		$this->wheres[$field]['$or'] = $values;
		return($this);
	}
	
	
	/*
	*
	* 獲取元素與指定值匹配的文檔
	*
	* @usage = $this->mongo_db->where_and( array ( 'foo' => 1, 'b' => 'someexample' );
	*/
	
	public function where_and( $elements_values = array() ) {
		foreach ( $elements_values as $element => $val ) {
			$this->wheres[$element] = $val;
		}
		return($this);
	}
	
	
	/*
	*
	* 獲取文檔 $field % $mod = $result
	*
	* @usage = $this->mongo_db->where_mod( 'foo', 10, 1 );
	*/
	public function where_mod( $field, $num, $result ) {
		$this->where_init($field);
		$this->wheres[$field]['$mod'] = array ( $num, $result );
		return($this);
	}
	
	/*
	*
	* 獲取欄位大小為給定的文檔 $size int
	*
	* @usage : $this->mongo_db->where_size('foo', 1)->get('foobar');
	*/

	public function where_size($field = "", $size = ""){
		$this->_where_init($field);
		$this->wheres[$field]['$size'] = $size;
		return ($this);
	}
	
	/*
	*
	* 
	*獲取$字段的（字符串）值就像一個值的文檔。默認值
	*允許不區分大小寫的搜索。
	*
	* @param $flags
	*允許典型的正則表達式標誌：
	*  i = 不區分大小寫
	*  m = 多行
	*  x = 可以包含評論
	*  l = locale
	*  s = dotall, "." 匹配一切，包括換行符
	*  u = match unicode
	*
	* @param $enable_start_wildcard
	*如果設置為TRUE以外的任何值，則會預置起始行字符“^”
	*表示搜索值，僅表示搜索開始處的值
	*一個新的行。
	*
	* @param $enable_end_wildcard
	*如果設置為TRUE以外的任何值，則會追加結尾行字符“$”
	*表示搜索值，僅表示在結尾處搜索值
	*一行。
	*
	* @usage = $this->mongo_db->like('foo', 'bar', 'im', FALSE, TRUE);
	*/
	public function like($field = "", $value = "", $flags = "i", $enable_start_wildcard = TRUE, $enable_end_wildcard = TRUE){
	
		$field = (string) trim($field);
		$this->where_init($field);
		$value = (string) trim($value);
		$value = quotemeta($value);
		
		if($enable_start_wildcard !== TRUE){
			$value = "^" . $value;
		}
		
		if($enable_end_wildcard !== TRUE){
			$value .= "$";
		}
		$regex = "/$value/$flags";
		$this->wheres[$field] = new MongoRegex($regex);
		return($this);
	}
	/*
	*
	*
	*根據傳遞的參數對文檔進行排序。要將值設置為降序，
	*您必須傳遞-1，FALSE，“desc”或“DESC”的值，否則將會是
	*設為1（ASC）。
	*
	* @usage = $this->mongo_db->where_between('foo', 20, 30);
	*/
	
	public function order_by($fields = array()){
	
		foreach($fields as $col => $val){
		
			if($val == -1 || $val === FALSE || strtolower($val) == 'desc'){
				$this->sorts[$col] = -1; 
			} else {
				$this->sorts[$col] = 1;
			}
		}
		return($this);
	}
	
	/*
	*
	*
	* 將結果集限制為 $x 個文檔
	*
	* @usage = $this->mongo_db->limit($x);
	*/
	public function limit($x = 99999) {
	
		if($x !== NULL && is_numeric($x) && $x >= 1){
			$this->limit = (int) $x;
		}
		return($this);
	}
	
	/*
	*
	*
	* 偏移結果集以跳過 $x 個文檔
	*
	* @usage = $this->mongo_db->offset($x);
	*/
	public function offset($x = 0){
	
		if($x !== NULL && is_numeric($x) && $x >= 1){
			$this->offset = (int) $x;
		}
		return($this);
	}
	
	/*
	*
	* 根據傳遞的參數獲取文檔
	*
	* @usage = $this->mongo_db->get_where('foo', array('bar' => 'something'));
	*/
	
	public function get_where($collection = "", $where = array(), $limit = 99999){
		return($this->where($where)->limit($limit)->get($collection));
	}
	
	
	/*
	*
	* 根據傳遞的參數獲取文檔
	*
	* @usage = $this->mongo_db->get('foo', array('bar' => 'something'));
	*/
	public function get($collection = ""){
	
		if(empty($collection)){
		
			$this->MessageBox("In order to retreive documents from MongoDB, a collection name must be passed", 500);
		}
		
		$results = array();
		$documents = $this->db->{$collection}->find($this->wheres, $this->selects)->limit((int) $this->limit)->skip((int) $this->offset)->sort($this->sorts);
		$returns = array();
		foreach($documents as $doc):
			$returns[] = $doc;
		endforeach;
		$this->clear();
		return($returns);
	}
	
	/*
	*
	* 根據傳遞的參數計算文檔
	*
	* @usage = $this->mongo_db->get('foo');
	*/
	public function count($collection = ""){
	
		if(empty($collection)){
			$this->MessageBox("In order to retreive a count of documents from MongoDB, a collection name must be passed", 500);
		}
		$count = $this->db->{$collection}->find($this->wheres)->limit((int) $this->limit)->skip((int) $this->offset)->count();
		$this->clear();
		return($count);
	}
 
	/**
	* 自動增加ID實現
	* return insert_id
	*/
	private function insert_inc($table){ 
	
		$update = array('$inc'=>array('id'=>1));
		
		$query = array('table'=>$table);
		
		$command = array(
			'findandmodify'	=>	'_increase', 
			'update'	=>	$update,
			'query'		=>	$query, 
			'new'		=>	true, 
			'upsert'	=>	true
		);
		
		$id = $this->db->command($command);
		return $id['value']['id'];
	}
 
	/**
	* --------------------------------------------------------------------------------
	* INSERT
	* --------------------------------------------------------------------------------
	*
	* 將新文檔插入傳遞的集合中
	*
	* @usage = $this->mongo_db->insert('foo', $data = array());
	*/
	public function insert($collection = "", $data = array()) {
	
		if(empty($collection)){
			$this->MessageBox("No Mongo collection selected to insert into", 500);
		}
		if(count($data) == 0 || !is_array($data)){
		
			$this->MessageBox("Nothing to insert into Mongo collection or insert is not an array", 500);
		}
		
		try{
			$inc = $this->insert_inc($collection);
			$data['_id'] = $inc;
			$result = $this->db->{$collection}->insert($data, array('fsync' => TRUE));
			if($result['ok'] || $result){
				return true;
			} else{
				return false;
			}
		} catch(MongoCursorException $e){
			$this->MessageBox("Insert of data into MongoDB failed: {$e->getMessage()}", 500);
		}
	}
	/*
	*
	* 將文檔更新到傳遞的集合中
	*
	* @usage = $this->mongo_db->update('foo', $data = array());
	*/
	
	public function update($collection = "", $data = array(), $flage = false){
		
		if(empty($collection)){	
			$this->MessageBox("No Mongo collection selected to update", 500);
		}
		if(count($data) == 0 || !is_array($data)){
			$this->MessageBox("Nothing to update in Mongo collection or update is not an array", 500);
		}
		unset($data['_id']);
		
		if($flage){
			$arr = $this->wheres;
			unset($arr['_id']);
			if(is_array($arr)){
				foreach($arr as $key => $w){
					unset($data[$key]);
				}
			}
		}
		try{
			$res = $this->db->{$collection}->update($this->wheres, array('$set' => $data), array('fsync' => TRUE, 'multiple' => FALSE));
			$this->clear();
			return $res;
			
		}  catch(MongoCursorException $e){
			$this->MessageBox("Update of data into MongoDB failed: {$e->getMessage()}", 500);
		}
	}
	/*
	*
	* 將新文檔插入傳遞的集合中
	*
	* @usage = $this->mongo_db->update_all('foo', $data = array());
	*/
	
	public function update_all($collection = "", $data = array()) {
		if(empty($collection)){
			$this->MessageBox("No Mongo collection selected to update", 500);
		}
		if(count($data) == 0 || !is_array($data)){
			$this->MessageBox("Nothing to update in Mongo collection or update is not an array", 500);
		}
		
		try{
			$this->db->{$collection}->update($this->wheres, array('$set' => $data), array('fsync' => TRUE, 'multiple' => TRUE));
			$this->clear();
			return(TRUE);
		} catch(MongoCursorException $e){
			$this->MessageBox("Update of data into MongoDB failed: {$e->getMessage()}", 500);
		}
	}
	
	
	/*
	*
	* 根據特定標準從傳遞的集合中刪除文檔
	*
	* @usage = $this->mongo_db->delete('foo', $data = array());
	*/
	
	public function delete($collection, $where){
	
		if(empty($collection)){
			$this->MessageBox("No Mongo collection selected to delete from", 500);
		}
		if(!$where){
			$this->MessageBox("No data input to delete", 500);
		}
	
		try{
			$this->wheres = $where;
			$this->db->{$collection}->remove($this->wheres);
			$this->clear();
			return(TRUE);
		} catch(MongoCursorException $e){
			$this->MessageBox("Delete of data into MongoDB failed: {$e->getMessage()}", 500);
		}
	}
	/*
	*
	* 根據特定的標準刪除通過的集合中的所有文檔
	*
	* @usage = $this->mongo_db->delete_all('foo', $data = array());
	*/
	public function delete_all($collection = ""){
	
		if(empty($collection)){
			$this->MessageBox("No Mongo collection selected to delete from", 500);
		}
		
		try{
			$this->db->{$collection}->remove($this->wheres, array('fsync' => TRUE, 'justOne' => FALSE));
			$this->clear();
			return(TRUE);
			
		} catch(MongoCursorException $e) {
			$this->MessageBox("Delete of data into MongoDB failed: {$e->getMessage()}", 500);
		}
	}
	
	/*
	*
	*使用可選參數確保集合中的鍵的索引。要將值設置為降序，
	*您必須傳遞-1，FALSE，“desc”或“DESC”的值，否則將會是
	*設為1（ASC）。
	*
	* @usage = $this->mongo_db->add_index($collection, array('first_name' => 'ASC', 'last_name' => -1), array('unique' => TRUE));
	*/
  
  
	public function add_index($collection = "", $keys = array(), $options = array()){
	
		if(empty($collection)){
			$this->MessageBox("No Mongo collection specified to add index to", 500);
		}
		
		if(empty($keys) || !is_array($keys)){
			$this->MessageBox("Index could not be created to MongoDB Collection because no keys were specified", 500);
		}

		foreach($keys as $col => $val){
		
			if($val == -1 || $val === FALSE || strtolower($val) == 'desc'){
				$keys[$col] = -1; 
			} else {
				$keys[$col] = 1;
			}
		}
		
		if($this->db->{$collection}->ensureIndex($keys, $options) == TRUE){
		
			$this->clear();
			return($this);
			
		} else {
			$this->MessageBox("An error occured when trying to add an index to MongoDB Collection", 500);
		}
	}
 
	/*
	*
	*刪除集合中的鍵的索引。要將值設置為降序，
	*您必須傳遞-1，FALSE，“desc”或“DESC”的值，否則將會是
	*設為1（ASC）。
	*
	* @usage = $this->mongo_db->remove_index($collection, array('first_name' => 'ASC', 'last_name' => -1));
	*/
	
	public function remove_index($collection = "", $keys = array()){
		if(empty($collection)){
			$this->MessageBox("No Mongo collection specified to remove index from", 500);
		}
		if(empty($keys) || !is_array($keys)){
			$this->MessageBox("Index could not be removed from MongoDB Collection because no keys were specified", 500);
		}
		
		if($this->db->{$collection}->deleteIndex($keys, $options) == TRUE){
			$this->clear();
			return($this);
		} else{
			$this->MessageBox("An error occured when trying to remove an index from MongoDB Collection", 500);
		}
	}
	/*
	*
	* 刪除集合中的所有索引。
	*
	* @usage = $this->mongo_db->remove_all_index($collection);
	*/
	
	public function remove_all_indexes($collection = "") {
	
		if(empty($collection)){
			$this->MessageBox("No Mongo collection specified to remove all indexes from", 500);
		}
		$this->db->{$collection}->deleteIndexes();
		$this->clear();
		return($this);
	}
	/*
	*
	* 列出集合中的所有索引。
	*
	* @usage = $this->mongo_db->list_indexes($collection);
	*/
	
	public function list_indexes($collection = "") {
		if(empty($collection)){
			$this->MessageBox("No Mongo collection specified to remove all indexes from", 500);
		}
		return($this->db->{$collection}->getIndexInfo());
	}
	/*
	*
	*從數據庫中刪除指定的集合。要小心，因為這一點
	*在生產中可能會有一些非常大的問題！
	*/
	
	public function drop_collection($collection = ""){
		if(empty($collection)){
			$this->MessageBox("No Mongo collection specified to drop from database", 500);
		}
		$this->db->{$collection}->drop();
		return TRUE;
	}
 

	/*
	*
	* 將類變量重置為默認設置
	*/
	
	private function clear(){
	
		$this->selects = array();
		$this->wheres = array();
		$this->limit = NULL;
		$this->offset = NULL;
		$this->sorts = array();
	}
	
	
	
	/*
	*
	* 準備要插入的參數 $wheres array().
	*/
	private function where_init($param) {
		if(!isset($this->wheres[$param])){
			$this->wheres[$param] = array();
		}
	}
 
	/*
	*  設置表
	*  $table 表名
	*/
	
	public function set_table($table){
		$this->collection = $table;
	}
 
 
	/*
	* 
	* 獲取表名
	* 
	*/
	public function get_table(){
		return $this->collection;
	}
 
 
 
	/*
	* 設置表排序
	*  $orderby 排序
	*/
	public function set_orderby($orderby){
		$this->page_sorts = $orderby;
	}
 
 
	/*
	* 獲取左邊結果集
	*
	*  $left 左邊顯示的個數
	*  $last 定位當前頁的值
	*  $size 頁面大小
	*/
	public function get_left($left, $last, $size = PAGE_SIZE){
		if($last){
			$order = $this->nor_orderby();
			if($this->page_sorts[$this->key] == -1){
				$this->where_gt($this->key, $last);
			} else {
				$this->where_lt($this->key, $last);
			}
			return $this->limit($left * $size)->order_by($order)->get($this->collection); 
		}
	}
 
 
	/*
	*  獲取右邊結果集
	*  $right 右邊顯示的個數
	*  $last 定位當前頁的值
	*  $size 頁面大小
	*/
	
	public function get_right($right, $last, $size = PAGE_SIZE){
		if($last){
			if($this->page_sorts[$this->key] == -1){
				$this->where_lte($this->key, $last);
			} else {
				$this->where_gte($this->key, $last);
			}
		}
		return $this->limit($right * $size + 1)->order_by($this->page_sorts)->get($this->collection);
	}
 
	/*
	* 設置鍵
	*  $key 設置索引主鍵
	*/
	public function set_key($key){
		$this->key = $key;
	}
 
	/*
	* 求反
	*/
	
	private function nor_orderby(){
		foreach($this->page_sorts as $key => $order){
			if($order == -1){
				$orderby[$key] = 1;
			}else{
				$orderby[$key] = -1;
			}   
		}
		return $orderby;
	}
 
	 /*
	  * 獲取上一頁的值
	  *  $last 定位當前頁的值
	  *  $size 頁面大小
	  */
  
	public function get_prev($last, $size = PAGE_SIZE){
		if($last){
			if($this->page_sorts[$this->key] == 1){
				$this->where_lt($this->key,$last)->order_by(array($this->key => -1));
			} else {
				$this->where_gt($this->key,$last)->order_by(array($this->key => 1));
			}
			$result = $this->limit($size)->get($this->collection);
		}
		return $result[$size - 1][$this->key];
	}

	/*
	* 獲取下一頁的值
	*  $last 定位當前頁的值
	*  $size 頁面大小
	*/
	public function get_next($last, $size = PAGE_SIZE){
		if($last){
			if($this->page_sorts[$this->key] == 1){
				$this->where_gte($this->key,$last);
			} else {
				$this->where_lte($this->key,$last);
			}
		}
		$result = $this->limit($size+1)->order_by($this->page_sorts)->get($this->collection);
		return $result[$size][$this->key];
	}
 
	/*
	* 獲取最後一頁的值
	*  $size 頁面大小
	*/
	public function get_last($size = PAGE_SIZE){
		$res = $this->count($this->collection) % $size;
		$order = $this->nor_orderby();
		if($res > 0){
			$result = $this->limit($res)->order_by($order)->get($this->collection);
			return $result[$res - 1][$this->key];
		} else {
			$result = $this->limit($size)->order_by($order)->get($this->collection);
			return $result[$size - 1][$this->key];
		}
	}
 
	/*
	* 分頁查詢
	*  $last 定位當前頁的值
	*  $size 頁面大小
	*/
	
	public function page_query($last, $size = PAGE_SIZE){
		if($last){
			if($this->page_sorts[$this->key]==1){
				$this->where_gte($this->key,$last);
			} else {
				$this->where_lte($this->key,$last);
			}
		}
		return $this->limit($size)->order_by($this->page_sorts)->get($this->collection);
	} 
 
	/**
	* 批量執行代碼_插入
	* @param String $collection
	* @param 二维陣列 $code 
	*/
	public function execute_insert($collection,$code){
		//二维陣列分成js格式
		$strcode='';
		foreach($code as $k=>$v){
			foreach($v as $kk=>$vv){
				$strcode.='db.getCollection("'.$collection.'").insert({ "'.$kk.'":"'.$vv.'" });';
			}    
		}
		// retrun array([ok]=>1);
		return $this->db->execute($code);  
	}
	
	
	public function MessageBox($message, $status_code = 500, $heading = 'An Error Was Encountered'){
		echo $message, $status_code,PHP_EOL;
		exit;
	}	
	
}
?>