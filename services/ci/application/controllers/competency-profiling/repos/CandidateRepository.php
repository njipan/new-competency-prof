<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once(__DIR__.'/../contracts/AbstractRepository.php');
class CandidateRepository extends AbstractRepository{

    public function updateJKAMultiples($params){
        if(!$candidates_exec = $this->sp('bn_JKA_UpdateMultipleCandidates', $params, self::$JKA_DB)){
            return null;            
        }
        return $candidates_exec->result();
    }

    public function proxyPostCandidate($params){
        if(!$candidates_exec = $this->sp('bn_JKA_PostCandidate_Proxy', $params, self::$JKA_DB)){
            return null;            
        }
        return $candidates_exec->result();
    }

    public function getCandidateByLecturerCodeAndPeriod($lecturer_code, $period){
        $param_user = [
            '_LecturerCode' => $lecturer_code,
            '_PeriodID' => $period,
        ];        
        if(!$user = $this->sp('bn_JKA_Input_GetCandidate', $param_user, self::$JKA_DB)){
            return null;            
        }

        return $this->handleReturn($user);
    }

    public function getCandidateByID($id){
        $param_user = [
            '_CandidateID' => $id,
        ];        
        if(!$user = $this->sp('bn_JKA_GetCandidateByID', $param_user, self::$JKA_DB)){
            return null;            
        }
        return $this->handleReturn($user);
    }

    public function getCandidateByLecturerCode($lecturer_code){
        $param_candidate = [
            'LECTURER_ID' => $lecturer_code,
        ];        
        if(!$candidate = $this->sp('bn_JKA_GetSingleCandidate', $param_candidate, self::$JKA_DB)){
            return null;            
        }
        return $this->handleReturn($candidate);
    }

    public function changeStatus($params){
        if(!$candidate = $this->sp('bn_JKA_Candidate_ChangeStatusByID', $params, self::$JKA_DB)){
            return null;            
        }
        return $this->handleReturn($candidate);
    }

    public function createLevels($params){
        if(!$levels = $this->sp('bn_JKA_CreateTrLevelByCandidate', $params, self::$JKA_DB)){
            return null;            
        }
        return $levels->result();
    }

    public function post($id){
        $params = [ 
            '_CandidateID' => $id
        ];
        if(!$candidate = $this->sp('bn_JKA_PostTrCandidateByID', $params, self::$JKA_DB)){
            return null;            
        }
        return $this->handleReturn($candidate);
    }

    public function saveMultiple($params){
        if(!$candidates = $this->sp('bn_JKA_InsertTrCandidate', $params, self::$JKA_DB)){
            return null;
        }
        return $candidates->result();
    }

    public function printCandidates($params){
        if(!$candidates = $this->sp('bn_JKA_PrintCandidates', $params, self::$JKA_DB)){
            return null;
        }
        return $candidates->result();
    }

    public function delete($id){
        $params = [ 
            '_UserUp' => $_SESSION['employeeID'],
            '_CandidateID' => $id,
        ];
        if(!$candidate = $this->sp('bn_JKA_DeleteCandidateByID', $params, self::$JKA_DB)){
            return null;            
        }
        return $this->handleReturn($candidate);
    }

    public function select($params){
        if(!$candidates = $this->sp('bn_JKA_GetSpecificCandidates', $params, self::$JKA_DB)){
            return null;            
        }
        return $candidates->result();
    }

    public function deletes($params){
        if(!$candidates = $this->sp('bn_JKA_DeleteMultipleCandidates', $params, self::$JKA_DB)){
            return null;            
        }
        return $candidates->result();
    }

}