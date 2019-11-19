<?php
require_once(__DIR__.'/../contracts/AbstractRequest.php');
require_once(__DIR__.'/../repos/CandidateRepository.php');
require_once(__DIR__.'/../repos/LevelRepository.php');
require_once(__DIR__.'/../repos/ReasonRepository.php');

require_once(__DIR__.'/../constants/CandidateError.php');
require_once(__DIR__.'/../constants/CandidateStatusType.php');
class UpdateJKACandidateRequest extends AbstractRequest {

    public function validate(){
        $errors = [];
    	$candidateRepository = new CandidateRepository();
    	$data = $this->request;

        $reasonRepo = new ReasonRepository();
        $levelRepo = new LevelRepository();
        $level_rows = $levelRepo->all() ?: [];
        $levels = [];
        foreach ($level_rows as $item) {
            $levels[$item->N_JKA_ID] = $item;
        }
        
        $reasons_set = $reasonRepo->all() ?: [];
        $reasons = [];
        foreach ($reasons_set as $reason) {
            $reason_id = $reason->ReasonID;
            $reasons['reason-'.$reason_id] = $reason;
        }

    	if(empty($data)) return ['message' => 'Please select at least one data'];
    	try{
    		foreach ($data as $candidate_item) {
                $candidate_id = $candidate_item['CandidateTrID'];
	    		$candidate = $candidateRepository->getCandidateByID($candidate_id);
                $currentJKA = $levels[$candidate->N_JKA_ID]->Descr;
                $currentGradeJKA = $candidate->N_JKA_ID;
                
	    		if(empty($candidate)){
                    $errors['candidates'][$candidate_id] = true;
                    continue;  
                }
                else if($candidate->StatusID != CandidateStatusType::$OPEN){
                    $errors['candidates'][$candidate_id] = true;
                    continue;  
                }
	    		else if(strcasecmp($candidate_item['NextGradeJKA'], $currentGradeJKA) < 1){
                    $errors['candidates'][$candidate_id] = CandidateError::$CURRENT_JKA;
                    continue;
                }
                $currentGradeJKA = $candidate->N_JKA_ID;
                if(empty($levels[$currentGradeJKA])){
                    $errors['candidates'][$candidate_id] = CandidateError::$CURRENT_JKA;
                    continue;
                }
                else if(empty($levels[$candidate_item['NextGradeJKA']]) || $levels[$candidate_item['NextGradeJKA']]->Descr != $candidate_item['NextJKA']){
                    $errors['candidates'][$candidate_id] = CandidateError::$NEXT_JKA;
                    continue;
                }
                else if(strcasecmp($candidate_item['NextGradeJKA'], $currentGradeJKA) <= 0){
                    $errors['candidates'][$candidate_id] = CandidateError::$NEXT_JKA;
                    continue;
                }
                else if(empty($reasons['reason-'.$candidate_item['ReasonID']])){
                    $errors['candidates'][$candidate_id] = CandidateError::$REASON;
                    continue;
                }
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
            '_UserUp' => $_SESSION['employeeID'],
    		'_Data' => $xml,
    	];
    }

}