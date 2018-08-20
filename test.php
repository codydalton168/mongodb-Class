<?php
  include "page.class.php";
  include "mongodb.class.php";
  define(PAGE_SIZE, 5);//Page Size
  $config['HOST'] = '127.0.0.1';
  $config['PORT'] = 20081;  //mongodb pro
  $config['DATABASE'] = 'domain';//mongodb DataBase Name
  $config['USER'] = '';
  $config['PWD'] = '';
  $config['PERSIST'] = TRUE;
 
  $DB = new DB($config);
  $table = 'whois';
  $key = '_id';
  $orderby = array($key => -1);
  $DB->set_table($table);
  $DB->set_key($key);
  $DB->set_orderby($orderby);
  $log = $DB->page_query($lastid,5);
  $page = new Page($lastid, $key, $orderby);
  echo $pager = $page->show();
 
?>