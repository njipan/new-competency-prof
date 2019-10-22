<?php
require_once(__DIR__.'/../contracts/AbstractRequest.php');
require_once(__DIR__.'/../repos/CandidateRepository.php');
class PostCandidateRequest extends AbstractRequest {

    public function validate(){
    	$data = $this->request;
    	if(empty($data)) return ['message' => 'No data selected'];
    	$candidateRepo = new CandidateRepository();
    	try{
            $xml = $this->array2xml($data, false);
    		$candidates = $candidateRepo->proxyPostCandidate(['_Data' => $xml]);
    		if(count($candidates) != count($data)) return ['message' => 'Request is invalid'];
    	}catch(\Exception $e){
    		return ['message' => 'Error occured when posting data'];
    	}
    	
        return [];
    }

    public function transform(){
    	return [
    		'_Data' => json_encode($this->request),
    	];
    }

}