<?php
require_once(__DIR__.'/../contracts/AbstractRequest.php');

require_once(__DIR__.'/../repos/CandidateRepository.php');

require_once(__DIR__.'/../constants/CandidateStatusType.php');

class DeleteMultipleCandidateRequest extends AbstractRequest {

    public function validate(){
    	$data = $this->request;
    	$candidateRepo = new CandidateRepository();
    	$xml_candidates = $this->array2xml($this->data(), false);
		$candidates = $candidateRepo->select([ '_Data' => $xml_candidates]);
        $message = "Failed to delete data with details : <br>";
        $isError = false;
        $response_candidates = [];
        foreach ($candidates as $candidate) {
            if($this->isCanDelete($candidate->StatusID) === false){
                $isError = true;
                $candidate_lecture_name = $candidate->EXTERNAL_SYSTEM_ID."-".$candidate->NAME_FORMAL;
                $message .= "<br>- $candidate_lecture_name";
                $response_candidates[$candidate->CandidateTrID] = true;
            }
        }
        if($isError) {
            $response_candidates['message'] = $message;
            return $response_candidates;
        }
        
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

    private function isCanDelete($status_id){
        return $status_id == CandidateStatusType::$OPEN;
    }

}
