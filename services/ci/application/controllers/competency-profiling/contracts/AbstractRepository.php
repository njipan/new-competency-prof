<?php

abstract class AbstractRepository{
    protected static $JKA_DB = 'JKA_DB';

    public function sp($sp_name, $array = array(), $db = 'default')
	{
        $CI = &get_instance();
		$DBS = $CI->load->database($db, TRUE);

		$param_name = array();
		$param_list = array();
		foreach($array as $key => $val)
		{
			$param_name[] = '@' . $key . '=?';
			$param_list[] = $val;
		}
		
		$sp_name .= ' '. implode(', ', $param_name);
	
		return $DBS->query($sp_name, $param_list);
	}

	public function handleReturn($res){
        $ret = $res->result();
        if(empty($ret)) return null;
        return $ret[0];
	}
}