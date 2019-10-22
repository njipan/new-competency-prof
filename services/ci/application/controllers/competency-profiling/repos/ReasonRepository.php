<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once(__DIR__.'/../contracts/AbstractRepository.php');
class ReasonRepository extends AbstractRepository{

    public function all(){
    	if(!$reasons = $this->sp('bn_JKA_GetReasons', [], self::$JKA_DB)){
    		return null;
    	}
    	return $reasons->result();
    }

}