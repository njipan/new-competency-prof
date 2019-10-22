<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once(__DIR__.'/../contracts/AbstractRepository.php');
class TeachingRepository extends AbstractRepository{

    public function getTeachingByCandidateID($candidate_id, $item_id, $subitem_id){
        $params = [
            '_CandidateID' => $candidate_id,
            '_N_ITEM_ID' => $item_id,
            '_N_SUBITEM_ID' => $subitem_id,
        ];
        if(!$teachs = $this->sp('bn_JKA_GetTrTeachingByCandidateID', $params, self::$JKA_DB)){   
            return null;
        }
        $teachs = $teachs->result();
        if(empty($teachs)) return null;
        return $teachs[0];
    }

    public function getByID($id){
        $params = [
            '_TeachingID' => $id,
        ];
        if(!$teachs = $this->sp('bn_JKA_GetTrTeachingById', $params, self::$JKA_DB)){   
            return null;
        }
        return $this->handleReturn($teachs);
    }

    public function updateByID($params){
        if(!$comdevs = $this->sp('bn_JKA_UpdateTrTeachingByID', $params, self::$JKA_DB)){
            return null;
        }
        return $this->handleReturn($comdevs);
    }

    public function deleteByParams($params){
        if(!$teachs = $this->sp('bn_JKA_DeleteTrTeaching', $params, self::$JKA_DB)){
            return null;
        }
        return $this->handleReturn($teachs);
    }

}