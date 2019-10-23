<?php
require_once(__DIR__.'/../contracts/AbstractRequest.php');

class PrintCandidateRequest  extends AbstractRequest {

    public function validate(){
        $data = $this->data();
        $form = $data['form'];
        $errors = [];
        if(empty($form['institution'])){
            $errors['institution'] = "Must be selected";
        }
        if(empty($form['organization'])){
            $errors['organization'] = "Must be selected";
        }
        if(empty($form['department'])){
            $errors['department'] = "Must be selected";
        }
        if(empty($form['period_id'])){
            $errors['period'] = "Must be selected";
        }
        else if($form['period_id'] != '*' && !is_numeric($form['period_id'])){
            $errors['period'] = "Period is not valid";
        }
        if(!is_array($data['candidates'])){
            $errors['candidates'] = "Must be collection";
        }

        return $errors;
    }

    public function transform(){
    	$data = $this->data();
        $form = $data['form'];
        $candidates = $data['candidates'];
        $xml = $this->array2xml($candidates, false);
        return [
			"_InstitutionID" => $form['institution'],
            "_OrganizationID" => $form['organization'],
            "_Department" => $form['department'],
            "_PeriodID" => $form['period_id'],
			"_Candidates" => $xml,
        ];
    }

}
