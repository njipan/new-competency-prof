<?php
require_once(__DIR__.'/../contracts/AbstractRequest.php');

require_once(__DIR__.'/../repos/CandidateRepository.php');

class DeleteMultipleCandidateRequest extends AbstractRequest {

    public function validate(){
    	$data = $this->request;
    	$candidateRepo = new CandidateRepository();
    	$xml_candidates = $this->array2xml($this->data(), false);
		$candidates = $candidateRepo->select([ '_Data' => $xml_candidates]);
		if(count($this->data()) != count($candidates)){
			return [ 'message' => 'Please check selected candidate is appropriate' ];
		}
		$this->xml_candidates = $xml_candidates;
    	return [];
    }


    public function transform(){
        return [
        	"_UserUp" => $_SESSION['employeeID'],
			"_Data" => $this->xml_candidates,
        ];
    }

}
