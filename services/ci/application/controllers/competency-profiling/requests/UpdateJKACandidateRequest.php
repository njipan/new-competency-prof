<?php
require_once(__DIR__.'/../contracts/AbstractRequest.php');
require_once(__DIR__.'/../repos/CandidateRepository.php');
class UpdateJKACandidateRequest extends AbstractRequest {

    public function validate(){
    	$candidateRepository = new CandidateRepository();
    	$data = $this->request;
    	if(empty($data)) return ['message' => 'Please select at least one data'];
    	try{
    		foreach ($data as $candidate_item) {
	    		$candidate = $candidateRepository->getCandidateByID($data[0]['CandidateTrID']);
	    		if(empty($candidate)) return ['message' => 'Data not found'];
	    		if(strcasecmp($candidate_item['NextGradeJKA'], $candidate->NextGradeJKA) != 1) return ['message' => 'Request body is invalid'];
	    	}	
	    	return [];
    	}catch(\Exception $e){
    		return ['message' => 'Request body is invalid'];
    	}
        return ['message' => 'Request body is invalid'];
    }

    public function transform(){
        $candidates = $this->data();
        $xml = $this->array2xml($candidates, false);
    	return [
    		'_Data' => $xml,
    	];
    }

}