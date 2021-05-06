<?php

/**
 * @author Stefano Martellos
 * @author Matteo Conti
 * @version 2.0
 * @created 24-Jul-2009
 * @modified 23-Dec-2020
 * 
 *
 */

class database
{

	private $connect;
	private $db_selected;
	//private $db_selected;


	public function __construct($database, $password, $user, $url)  // localhost in locale
	{
		//echo $database. $password. $user. $url;
		$this->setConnectDatabase($password, $user, $url);
		$this->setSelectDatabase($database);
	}


	private function setConnectDatabase($password, $user, $url)  // connette a mysql
	{
		$this->connect = new mysqli($url, $user, $password)
		or die("can't connect to the database".' '. $url.' '. $password.' '. $user);
		//mysqli_query("SET NAMES 'UTF8'");
	}


	private function setSelectDatabase($database)  // seleziona un database
	{
		$this->db_selected =$this->connect->select_db($database)
		or die("can't select {$database}.");
	}

	public function escapeString($string)
	{
		$result = $this->connect->real_escape_string($string);
		return $result;
	}

	public function setSingleQuery($query)
	{
		//$this->debugQuery($query);

		$result = $this->connect->query($query);
		return $result;
	}


	public function getSingleQuery($query)
	{
		$this->debugQuery($query);

		$result = $this->connect->query($query);
		$row = $result->fetch_array(MYSQLI_ASSOC);

		$this->debugResults($row);

		return true;
	}


	public function getSingleSelectQuery($query)
	{
		$this->debugQuery($query);

		$result = $this->connect->query($query);
		$row = $result->fetch_array(MYSQLI_ASSOC);

		//$this->debugResults($row);

		return $row;
	}


	public function getMultipleSelectQuery($query)
	{
        $return = array();
		//$this->debugQuery($query);

		$result = $this->connect->query($query);
		while($row = $result->fetch_array(MYSQLI_ASSOC))
			$return[] = $row;

		//$this->debugResults($return);
		

	    return $return;
	}


	public function getInsertQuery($query)
	{
		$result = $this->connect->query($query);
//		$row = mysql_fetch_array($result,MYSQL_ASSOC);
		return true;
	}


	private function debugQuery($query)
	{
//		echo "<hr>".$query."<hr>";
	}


	private function debugResults($query)
	{
//		echo"<pre>";		print_r($query);		echo"</pre>";
	}


	public function findIfTableExist($table)
	{
		//INSERISCI QUESTO TIPO DI QUERY: 'select 1 from `table` LIMIT 1'
		$query='select 1 from' .$table. 'LIMIT 1';
		$ifTable=$this->connect->query($query);
		return$ifTable;
		//SE IFTABLE == FALSE LA table NON ESISTE
	}


	public function getListSelectQuery($query)
	{
		$this->debugQuery($query);

		$result = $this->connect->query($query);

		for($i=0; $array[$i] = $result->fetch_array(); $i++);
		array_pop($array);

		return $array;
	}
}
?>

