<?php
require_once(__DIR__.'/../contracts/AbstractRequest.php');
require_once(__DIR__.'/../repos/CandidateRepository.php');
class PostCandidateRequest extends AbstractRequest {

    public function validate(){
        $errors = [];
    	$data = $this->request;
        $request_candidates = $this->transformCandidates($data);
    	if(empty($data)) return ['message' => 'No data selected'];
    	$candidateRepo = new CandidateRepository();
    	try{
            $xml = $this->array2xml($data, false);
    		$candidates = $candidateRepo->proxyPostCandidate(['_Data' => $xml]);
            foreach ($candidates as $candidate) {
                if(!empty($request_candidates[$candidate->CandidateTrID])){
                    unset($request_candidates[$candidate->CandidateTrID]);
                }
            }
            foreach($request_candidates as $candidate){
                $errors[$candidate['CandidateTrID']] = true;
            }

            if(!empty($errors)){
                $errors['message'] = 'Error occured when posting data';
                return $errors;
            } 
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

    private function transformCandidates($data){
        $candidates = [];
        foreach ($data as $candidate) {
            $candidates[$candidate['CandidateTrID']] = $candidate;
        }
        return $candidates;
    }

}