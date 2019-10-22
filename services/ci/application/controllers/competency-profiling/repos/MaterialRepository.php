<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once(__DIR__.'/../contracts/AbstractRepository.php');
class MaterialRepository extends AbstractRepository{

    public function get($id){
    	$params = [
            '_MaterialID' => $id,
        ];
    	if(!$materials = $this->sp('bn_JKA_GetMaterialByID', $params, self::$JKA_DB)){
    		return null;
    	}
    	return $this->handleReturn($materials);
    }

    public function updateByID($params){
    	if(!$materials = $this->sp('bn_JKA_UpdateMaterialByID', $params, self::$JKA_DB)){
    		return null;
    	}
    	return $this->handleReturn($materials);
    }

    public function insert($params){
        if(!$materials = $this->sp('bn_JKA_InsertMaterial', $params, self::$JKA_DB)){
            return null;
        }
        return $this->handleReturn($materials);
    }

    public function transactionSubtype($params){
        if(!$materials = $this->sp('bn_JKA_InsertTrMaterialSubType', $params, self::$JKA_DB)){
            return null;
        }
        return $this->handleReturn($materials);
    }

}