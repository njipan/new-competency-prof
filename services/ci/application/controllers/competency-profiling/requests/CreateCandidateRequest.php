<?php
require_once(__DIR__.'/../contracts/AbstractRequest.php');
require_once(__DIR__.'/../repos/LevelRepository.php');
require_once(__DIR__.'/../repos/ReasonRepository.php');
require_once(__DIR__.'/../constants/CandidateError.php');


class CreateCandidateRequest  extends AbstractRequest {

    public function validate(){
        $errors = [];
    	$data = $this->request;
    	$reasonRepo = new ReasonRepository();
    	$levelRepo = new LevelRepository();
        $level_rows = $levelRepo->all() ?: [];
        $levels = [];
        foreach ($level_rows as $item) {
        	$levels[$item->N_JKA_ID] = $item;
        }
        
		$reasons = $reasonRepo->all() ?: [];
    	if(empty($data['form']['period_id']) || empty($data['form']['institution']) || empty($data['form']['organization']) || empty($data['form']['department'])){
    		return ['message' => 'Data is invalid'];
    	}
    	foreach($data['data'] as $candidate){
            $lecturer_code = $candidate['LecturerCode'];
    		if(empty($levels[$candidate['CurrentGradeJKA']]) || $levels[$candidate['CurrentGradeJKA']]->Descr != $candidate['CurrentJKA']){
                $errors['candidates'][$lecturer_code] = CandidateError::$CURRENT_JKA;
                continue;
            }
    		if(empty($levels[$candidate['NextGradeJKA']]) || $levels[$candidate['NextGradeJKA']]->Descr != $candidate['NextJKA']){
    			$errors['candidates'][$lecturer_code] = CandidateError::$NEXT_JKA;
                continue;
            }
    		if(empty($reasons[$candidate['ReasonID']])){
    			$errors['candidates'][$lecturer_code] = CandidateError::$REASON;
                continue;
            }
            if(strcasecmp($candidate['NextGradeJKA'], $candidate['CurrentGradeJKA']) <= 0){
                $errors['candidates'][$lecturer_code] = CandidateError::$NEXT_JKA;
                continue;
            }
    	}
        if(!empty($errors)) $errors['message'] = 'Error occured when saving data';
        return $errors;
    }


    public function transform(){
    	$data = $this->data();
        $candidates = $data['data'];

        $xml = $this->array2xml($candidates, false);
        return [
        	"_UserIn" => $_SESSION['employeeID'],
			"_PeriodID" => $data['form']['period_id'],
			"_InstitutionID" => $data['form']['institution'],
			"_AcadID" => $data['form']['organization'],
			"_DepartmentID" => $data['form']['department'],
			"_Data" => $xml,
        ];
    }

}
