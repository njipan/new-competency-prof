<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once(__DIR__.'/../contracts/AbstractRepository.php');
class ComdevRepository extends AbstractRepository{

    public function getComdevByCandidateID($candidate_id, $item_id, $subitem_id){
        $param_comdev = [
            '_CandidateID' => $candidate_id,
            '_N_ITEM_ID' => $item_id,
            '_N_SUBITEM_ID' => $subitem_id,
        ];
        if(!$comdevs = $this->sp('bn_JKA_GetTrComdevByCandidateID', $param_comdev, self::$JKA_DB)){   
            return null;
        }
        $comdevs = $comdevs->result();
        if(empty($comdevs)) return null;
        return $comdevs[0];
    }

    public function getByID($id){
        $param_comdev = [
            '_ComdevID' => $id,
        ];
        if(!$comdevs = $this->sp('bn_JKA_GetTrComdevById', $param_comdev, self::$JKA_DB)){   
            return null;
        }
        return $this->handleReturn($comdevs);
    }

    public function updateByID($params){
        if(!$comdevs = $this->sp('bn_JKA_UpdateTrComdevByID', $params, self::$JKA_DB)){
            return null;
        }
        return $this->handleReturn($comdevs);
    }

    public function deleteByParams($params){
        if(!$comdevs = $this->sp('bn_JKA_DeleteTrComdev', $params, self::$JKA_DB)){
            return null;
        }
        return $this->handleReturn($comdevs);
    }

}