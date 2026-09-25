<?php

class SQLAsyncQueue
{
	
	private $redis;
	private $queueName;
	
	function __construct($queueName,$db=1)
	{	
		$this->redis = new \Redis(); 
		$this->redis->connect('127.0.0.1', 6379); 
		$this->redis->select(1);
					
		$this->queueName = $queueName;
		//echo "Connection to server sucessfully"; 
		//check whether server is running or not 
		//log_message('info', "Server is running: ".$this->redis->ping());
	}
	
	function sendItem($item)
	{		
		//log_message("error",print_r($item,true));
		$data['item'] = $item;
		$data['time'] = time();
		
		$id = 'ID_'.md5(json_encode($data));
		$data['id'] = $id; 
		
		$rData = json_encode($data);
		
		$this->redis->rPush($this->queueName, $rData);
		$this->redis->set($id,'wait',86400);
		
		return $id;
	}
	
	function sendUniqueItem($item,$lock)
	{
		$data['item'] = $item;
		$data['time'] = time();
		$data['lock'] = $lock;
		
		$id = 'ID_'.md5(json_encode($data));
		$data['id'] = $id;
		
		$rData = json_encode($data);
		
		$this->redis->rPush($this->queueName, $rData);
		$this->redis->set($id,'wait');
		
		return $id;
	}
	
	function getItemStatus($id)
	{
		//if(!$this->redis->exists('ID_'.$id)) return false;
		$status = $this->redis->get($id);
		
		return $status;
	}
	
	function waitFor($ids)
	{
		foreach($ids as $id)
		{
			while(true)
			{
				$itemStatus = $this->getItemStatus($id);
				if($itemStatus != 'done' && !empty($itemStatus)) usleep(10000);
				else break;
			}
		}
	}
	
}
?>