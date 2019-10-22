<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once(__DIR__.'/../contracts/AbstractRepository.php');
class PeriodRepository extends AbstractRepository{

    public function proxy_insert($params){        
        if(!$user = $this->sp('bn_JKA_Proxy_InsertPeriod', $params, self::$JKA_DB)){
            return false;
        }
        return empty($user->result()) ? true : false;
    }

    public function getByID($id){
        $params = [
            '_PeriodID' => $id,
        ];        
        if(!$period = $this->sp('bn_JKA_GetPeriodById', $params, self::$JKA_DB)){
            return null;            
        }
        return $this->handleReturn($period);
    }

    public function update($params){
        if(!$period = $this->sp('bn_JKA_UpdatePeriodById', $params ,self::$JKA_DB)){
            return null;            
        }
        return $this->handleReturn($period);
    }

    public function delete($params){
        if(!$period = $this->sp('bn_JKA_DeletePeriodByID', $params ,self::$JKA_DB)){
            return null;            
        }
        return $this->handleReturn($period);
    }

}