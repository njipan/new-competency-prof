<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once(__DIR__.'/../contracts/AbstractRepository.php');
class LevelRepository extends AbstractRepository{

    public function all(){
    	if(!$levels = $this->sp('bn_JKA_GetLevelDescs', [], self::$JKA_DB)){
    		return null;
    	}
    	return $levels->result();
    }

}