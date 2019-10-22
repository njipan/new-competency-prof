<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once(__DIR__.'/../contracts/AbstractRepository.php');
class ResearchRepository extends AbstractRepository{

    public function getResearchByCandidateID($candidate_id, $item_id, $subitem_id){
        $param_research = [
            '_CandidateID' => $candidate_id,
            '_N_ITEM_ID' => $item_id,
            '_N_SUBITEM_ID' => $subitem_id,
        ];
        if(!$researchs = $this->sp('bn_JKA_GetTrResearchByCandidateID', $param_research, self::$JKA_DB)){   
            return null;
        }
        $researchs = $researchs->result();
        if(empty($researchs)) return null;
        return $researchs[0];
    }

    public function getByID($id){
        $param_research = [
            '_ResearchID' => $id,
        ];
        if(!$researchs = $this->sp('bn_JKA_GetTrResearchById', $param_research, self::$JKA_DB)){   
            return null;
        }
        return $this->handleReturn($researchs);
    }

    public function updateByID($params){
        if(!$researchs = $this->sp('bn_JKA_UpdateTrResearchByID', $params, self::$JKA_DB)){
            return null;
        }
        return $this->handleReturn($researchs);
    }

    public function deleteByParams($params){
        if(!$researchs = $this->sp('bn_JKA_DeleteTrResearch', $params, self::$JKA_DB)){
            return null;
        }
        return $this->handleReturn($researchs);
    }

}