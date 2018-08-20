<?php
//mongoDB Paging class
class Page {
	 var $count='';
	 var $size='';
	 var $total='';
	 var $last='';
	 var $link='';
	 var $url='';
	 var $set='';
	 var $page='';
	 var $turnto='';
	 var $key = '';
	 var $next = '';
	 var $prev = '';
	 var $lefttresult = '';
	 var $rightresult = '';
	 var $left = '';
	 var $right = '';
	 var $orderby = '';
	 var $lastd = '';
	 var $db = '';
 
	//Initialize Build
	public function __construct($last, $key, $orderby){
		global $DB;
		$this->db = $DB;
		$this->count = $this->db->count($this->db->get_table());
		$url = SITE_ROOT.strtolower(CLASS_NAME).'/'.METHOD_NAME;
		$this->url = $this->url ? $this->url : $url;
		$set = $set ? $set : 5;
		$this->set = $set;
		$size = $size ? $size : PAGE_SIZE;
		$this->size = $size;
		$this->last = $last;
		$this->prev = $DB->get_prev($this->last);
		$this->next = $DB->get_next($this->last);
		//$this->page = GET::UINT('page');
		$this->page = $this->page ? $this->page : 1;
		$this->total = @ceil($this->count / $this->size);
		$this->key = $key;
		$this->orderby = $orderby; 
	}
 
	//Output Paging link
	public function get_link(){
		if($this->total != 1){
			$this->get_first();
			$this->get_prev();
			$this->get_center();
			$this->get_next();
			$this->get_last();
			$this->get_turnto();
		}
		if($this->link){
			$this->link = $this->turnto.$this->link.'共'.number_format($this->total).'頁 '.number_format($this->count).'筆';
		}
		if($this->turnto){
			$this->link .= '</form>';
		}
		return $this->link;
	}
 
	//獲取左邊顯示的個數
	public function get_left(){
		return  $this->left = ($this->set - $this->page >= 0) ? ($this->page - 1) : $this->set;
	}

	//獲取右邊顯示的個數
	public function get_right(){
		return $this->right = ($this->total - $this->page > $this->set) ? $this->set : ($this->total - $this->page);
	}

	//設置左邊的結果集
	public function set_left_result($left_result){
		$this->leftresult = $left_result;
	}

	//設置右邊的結果集
	public function set_right_result($right_result){
		$this->rightresult = $right_result;
	}

	//設置排序條件
	public function set_orderby($orderby){
		$this->orderby = $orderby;
	}
 
	//設置最後一頁
	public function set_last($last){
		$this->lastd = $last;
	} 

	//設置中間顯示頁碼個數
	public function set($set){
		$this->set = $set;
	}

	//獲取首頁
	private function get_first(){
		if($this->page != 1){
			if($this->total > 0){
				$this->link.='<a href="'.$this->url.'" title="首頁">首頁</a>';
			}
		}
	}

	//獲取上一頁
	private function get_prev(){
		if($this->prev){
			$this->link.='<a href="'.$this->url.'/page/'.($this->page - 1).'/id/'.$this->prev.'" title="上一頁">上一頁</a>';
		}
	}
 
	//中間顯示
	private function get_center(){
		$start = ($this->page - $this->set) <= 0 ? 1 : ($this->page - $this->set);  
		$end = ($this->page + $this->set + 1 >= $this->total) ? $this->total + 1 : ($this->page + $this->set + 1);

		$ii = $this->left;
		$iii = 0;
		//显示左边的
		for($i = $start; $i < $end; $i++, $ii--, $iii++){
			if($this->page == $i){
				$this->link.='<a style="color:#06F">'.$i.'</a>';
			}else{
				$the_id = $ii * $this->size - 1;
				if($the_id > 0){
					$this->link.='<a href="'.$this->url.'/page/'.$i.'/id/'.$this->leftresult[$the_id][$this->key].'" title="第'.$i.'頁">'.$i.'</a>';
				}else{
					$the_id = ($iii - $this->left) * $this->size;
					$this->link.='<a href="'.$this->url.'/page/'.$i.'/id/'.$this->rightresult[$the_id][$this->key].'" title="第'.$i.'頁">'.$i.'</a>';
				}    
			}
		}
	}

	//獲取下一頁
	private function get_next(){
		if($this->next){
			$this->link.='<a href="'.$this->url.'/page/'.($this->page + 1).'/id/'.$this->next.'" title="下一頁">下一頁</a>';
		}
	}

	//獲取尾頁
	private function get_last(){
		if($this->page != $this->total){
			$this->link.='<a href="'.$this->url.'/page/'.$this->total.'/id/'.$this->lastd.'" title="尾頁">尾頁</a>';
		}
	}

	//跳轉到
	private function get_turnto(){
		$this->turnto = '<form action="" method="get" onsubmit="window.location=''.$this->url.'/search/'+this.p.value+''.'';return false;">轉到第 &lt;input type="text" name="p" style="width:25px;text-align:center"&gt; 頁';
	}
 
	//求反
	public function nor_orderby(){
		foreach($this->orderby as $key => $order){
			if($order==-1){
				$orderby[$key] = 1;
			}else{
				$orderby[$key] = -1;
			}   
		}
		return $orderby;
	}
 
	//設置key
	public function set_key($key){
		$this->key = $key;
	}
 
	//分頁操作
	public function show(){
		$this->set_key($this->key);
		$this->set_orderby($this->orderby);
		$left = $this->get_left();
		$right = $this->get_right();
		$leftresult = $this->db->get_left($left, $this->last);
		$rightresult = $this->db->get_right($right, $this->last);
		$this->set_left_result($leftresult);
		$this->set_right_result($rightresult);
		$last = $this->db->get_last();
		$this->set_last($last); 
		return $this->get_link();
	}
}


/*      调用例子rockmongo
  global $DB;
  $lastid = GET::UINT('id');
  $table = 'log';
  $key = '_id';
  $orderby = array($key => -1);
  
  $DB->set_table($table);
  $DB->set_key($key);
  $DB->set_orderby($orderby);
  
  $log = $DB->page_query($lastid);

  $page = new Page($lastid, $key, $orderby);
  $pager = $page->show();
*/

?>