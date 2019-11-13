<?php
require_once(__DIR__.'/../contracts/AbstractRequest.php');
require_once(__DIR__.'/../repos/CandidateRepository.php');
require_once(__DIR__.'/../constants/CandidateStatusType.php');

class UpdateCandidateStatusRequest extends AbstractRequest {

    public function validate(){
        $errors = [];
        $candidate_repo = new CandidateRepository();
        $candidate_id = $this->request['candidate_id'];
        $status_id_to_be_changed = $this->request['status_id'];
        if(empty($candidate = $candidate_repo->getCandidateByID($candidate_id))){
            return [
                'message' => 'Invalid request parameter',
            ];
        }

        $rules = [
            [],
            [CandidateStatusType::$OPEN, CandidateStatusType::$WAITING_HOP], 
            [CandidateStatusType::$WAITING_HOP, CandidateStatusType::$APPROVED_HOP, CandidateStatusType::$DECLINED_HOP],
            [CandidateStatusType::$APPROVED_HOP,CandidateStatusType::$DECLINED_LRC,CandidateStatusType::$ON_PROCESS],
            [CandidateStatusType::$DECLINED_HOP, CandidateStatusType::$DECLINED_LRC, CandidateStatusType::$ON_PROCESS],
            [CandidateStatusType::$DECLINED_LRC],
            [CandidateStatusType::$ON_PROCESS,CandidateStatusType::$ON_REVIEW],
            [CandidateStatusType::$ON_REVIEW,CandidateStatusType::$ON_PROCESS,CandidateStatusType::$REVIEWED],
            []
        ];
        if($candidate->StartDt == null) $rules[1][] = CandidateStatusType::$ON_PROCESS;

        $rule_validate = !empty($rules[$candidate->StatusID]) && in_array($status_id_to_be_changed, $rules[$candidate->StatusID]);
        if(!$rule_validate){
            $errors['message'] = 'Status cannot be changed';
        }
        return $errors;
    }

    public function transform(){
        $data = $this->data();
        return [
            "_UserIn" => $_SESSION['employeeID'],
            "_CandidateID" => $data['candidate_id'],
            "_StatusID" => $data['status_id'],
        ];
    }

}