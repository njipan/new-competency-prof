<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
require_once('BaseController.php');

require_once('strategies/upload/AzureUpload.php');

class Toefl extends BaseController {
    
    static protected $JKA_DB = 'JKA_DB';

    public function __construct(){
        parent::__construct();

        $this->allowed_types= [
            "certificate" => ['png', 'jpeg', 'jpg', 'pdf','zip'],
        ];
        $this->candidate_id = 21;
        $this->lecturer_code = $this->getLecturerCode();
        
    }

    public function getLecturerCode(){
        if(empty($_SESSION['UserID'])) return null;
        $lecture_code = $_SESSION['UserID'];
        if(strpos($lecture_code, 'D') !== 0) return null;
        return $lecture_code;
    }

    public function update(){
        return $this->restURIs(__FUNCTION__);
    }

    public function update_post(){
        $errors = $this->validate_update_post();
        if(!empty($errors)){
            http_response_code(422);
            return $this->load->view("json_view", [
                'json' => $errors,
            ]);
        }

        $toefl = $this->getToeflById($_POST['id']);
        if(empty($toefl)){
            return $this->httpRequestInvalid('Toefl not found');
        }
        if($this->lecturer_code != $toefl->UserIn){
            return $this->httpRequestInvalid('You are not allowed');
        }
        $uploadInstance = new AzureUpload($_FILES['certificate']);
        $file_location = $uploadInstance->upload();
        $result_material = $this->sp('bn_JKA_InsertMaterial',[
            '_UserIn' => $this->lecturer_code,
            '_LocationFile' => $file_location,
        ],'JKA_DB')->result();

        $param_toefl = [
            '_ToeflID' => $_POST['id'],
            '_UserUp' => $this->lecturer_code,
            '_MaterialID' => $result_material[0]->MaterialID,
        ];
        
        if(!$toefl = $this->sp('bn_JKA_UpdateTrToeflByID',$param_toefl ,'JKA_DB')){
            return $this->httpRequestFailed('Error occured when updating data');
        }
        return $this->load->view("json_view", [
            'json' => $toefl->result()[0],
        ]);
    }

    public function getToeflById($id){
        $param_toefl = [
            '_ToeflID' => $id,
        ];
        if(!$toefls = $this->sp('bn_JKA_GetTrToeflById', $param_toefl, self::$JKA_DB)){   
            return null;
        }
        $toefls = $toefls->result();
        if(empty($toefls)) return null;
        return $toefls[0];
    }

    public function validate_update_post(){
        $allowed_types = $this->allowed_types['certificate'];
        $errors = [];
        if(empty($_POST['id']) || !is_numeric($_POST['id'])){
            $errors['id'] = 'Toefl is not valid';
        }
        if(empty($_FILES['certificate']['name'])){
            $errors['certificate'] = 'File must be selected';
            return $errors;
        }
        $errors['certificate'] = $this->validateCertificate($_FILES['certificate'], $allowed_types);
        if(empty($errors['certificate'])){
            unset($errors['certificate']);
        }
        return $errors;
    }

    public function validateCertificate($file, $allowed_types=[], $max_size=20000000){
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        if(empty($file)){
            return 'Certificate not valid';
        }
        if(empty($allowed_types)) return null;
        if(!in_array($ext, $allowed_types)) return 'Only accept '.implode(", ", $allowed_types)." files";
        if($file['size'] > $max_size) return "File exceeds maximum size ".($max_size/1000000)."MB";
        return null;
    }

    public function delete(){
        return $this->restURIs(__FUNCTION__, true);
    }

    public function delete_post(){
        $_POST = $this->getBody();
        $errors = $this->validate_candidate_delete();
        if(!empty($errors)){
            http_response_code(422);
            return $this->load->view("json_view", [
                'json' => $errors,
            ]);
        }
        $toefl = $this->getToeflById($_POST['id']);
        if(empty($toefl)){
            return $this->httpRequestInvalid('Toefl not found');
        }
        
        if($this->lecturer_code != $toefl->UserIn){
            return $this->httpRequestInvalid('You are not allowed');
        }
        $param_toefl['_ToeflID'] = $toefl->ToeflID;
        $param_toefl['_UserUp'] = $this->lecturer_code;
        if(!$toefl_deleted = $this->sp('bn_JKA_DeleteTrToefl', $param_toefl, self::$JKA_DB)){
            return $this->httpRequestInvalid('Problem occured while deleting data');
        }
        return $this->load->view('json_view', [
            'json' => $toefl_deleted->result(),
        ]);
    }
    
    public function candidate(){
        return $this->restURIs(__FUNCTION__, true);
    }

    public function validate_candidate_delete(){
        $errors = [];
        if(empty($_POST['id']) || !is_numeric($_POST['id'])){
            $errors['id'] = 'Toefl not valid';
        }
        return $errors;
    }

    public function candidate_post(){
        $_POST = $this->getBody();
        $errors = $this->validate_candidate_post();
        if(!empty($errors)){
            http_response_code(422);
            return $this->load->view("json_view", [
                'json' => $errors,
            ]);
        }

        $user = $this->getCandidateByLecturerCode($_POST['period_id'], $this->lecturer_code);
        if(empty($user)){
            return $this->load->view("json_view",[
                'json' => 'You are not allowed',
            ]);
        }
        $user = $user[0];
        $param_toefl = [
            '_CandidateID' => $user->CandidateTrID,
        ];
        if(!$toefls = $this->sp('bn_JKA_GetTrToeflByCandidateID', $param_toefl, self::$JKA_DB)){   
            return $this->httpRequestInvalid();
        }
        $payload = $toefls->result();
        
        return $this->load->view("json_view", [
            'json' => $payload,
        ]);
    }
    public function validate_candidate_post(){
    	$errors = [];
    	if(empty($_POST['period_id'])){
    		$errors['period_id'] = 'Can\'t be empty';
    	}
    	if(empty($_POST['item_id'])){
    		$errors['item_id'] = 'Can\'t be empty';
    	}
    	if(empty($_POST['sub_item_id'])){
    		$errors['sub_item_id'] = 'Can\'t be empty';
    	}

    	return $errors;
    }

    public function getCandidateByLecturerCode($period_id, $lecturer_code){
        $param_user = [
            '_LecturerCode' => $lecturer_code,
            '_PeriodID' => $period_id,
        ];
        if(!$user = $this->sp('bn_JKA_Input_GetCandidate', $param_user, self::$JKA_DB)){
            return $this->httpRequestInvalid('User not valid');    
        }
        return $user->result();
    }

}