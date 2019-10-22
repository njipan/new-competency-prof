<?php
require_once(__DIR__.'/../contracts/AbstractRequest.php');

class SearchAddCandidateRequest extends AbstractRequest {

    public function validate(){
        $errors = [];
        $data = $this->data();
        if(empty($data['period_id']) || !is_numeric($data['period_id'])){
            $errors['period_id'] = 'Must be chosen';
        }
        if(empty($data['institution'])){
            $errors['institution'] = 'Must be chosen';
        }
        if(empty($data['organization'])){
            $errors['organization'] = 'Must be chosen';
        }
        if(empty($data['department'])){
            $errors['department'] = 'Must be chosen';
        }

        return $errors;
    }

    public function transform(){
        $data = $this->data();
        return [
            "_PeriodID" => $data['period_id'],
	        "_InstitutionID" => $data['institution'],
	        "_AcadID" => $data['organization'],
	        "_DepartmentID" => $data['department'],
        ];
    }

}