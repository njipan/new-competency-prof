<?php
require_once(__DIR__.'/../contracts/AbstractRequest.php');
require_once(__DIR__.'/../repos/CandidateRepository.php');

class UpdateCandidateStatusRequest extends AbstractRequest {

    public function validate(){
        $errors = [];
        $candidate_repo = new CandidateRepository();
        $candidate_id = $this->request['candidate_id'];
        $status_id_to_be_changed = $this->request['status_id'];
        if(empty($candidate = $candidate_repo->getCandidateByID($candidate_id))){
            return [
                'message' => 'Any data in request is invalid',
            ];
        }

        $rules = [
            [],
            [6], 
            [3,4],
            [5,6],
            [6],
            [],
            [7],
            [6, 8],
            []
        ];
        $rule_validate = !empty($rules[$candidate->StatusID]) && in_array($status_id_to_be_changed, $rules[$candidate->StatusID]);
        if(!$rule_validate){
            $errors['message'] = 'Status is not valid';
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