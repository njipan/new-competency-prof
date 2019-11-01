<?php
require_once(__DIR__.'/../contracts/AbstractRequest.php');
require_once(__DIR__.'/../repos/CandidateRepository.php');
class UpdateJKACandidateRequest extends AbstractRequest {

    public function validate(){
        $errors = [];
    	$candidateRepository = new CandidateRepository();
    	$data = $this->request;
    	if(empty($data)) return ['message' => 'Please select at least one data'];
    	try{
    		foreach ($data as $candidate_item) {
                $candidate_id = $candidate_item['CandidateTrID'];
	    		$candidate = $candidateRepository->getCandidateByID($candidate_id);
	    		if(empty($candidate)) $errors['candidates'][$candidate_id] = true;
	    		if(strcasecmp($candidate_item['NextGradeJKA'], $candidate->NextGradeJKA) != 1) $errors['candidates'][$candidate_id] = true;
	    	}
    	}catch(\Exception $e){
    		$errors['message'] = 'Error occured when updating data';
    	}

        if(!empty($errors)) $errors['message'] = 'Error occured when updating data';
        return $errors;
    }

    public function transform(){
        $candidates = $this->data();
        $xml = $this->array2xml($candidates, false);
    	return [
    		'_Data' => $xml,
    	];
    }

}