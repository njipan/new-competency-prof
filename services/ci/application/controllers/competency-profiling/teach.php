<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
require_once('BaseController.php');
require_once('repos/CandidateRepository.php');
require_once('repos/TeachingRepository.php');
require_once('repos/MaterialRepository.php');

require_once('requests/UpdateTeachRequest.php');
require_once('requests/GeneralRequest.php');

require_once('constants/Subtype.php');

require_once('strategies/upload/AzureUpload.php');
class Teach extends BaseController {
    
    static protected $JKA_DB = 'JKA_DB';

    public function __construct(){
        parent::__construct();

        $this->candidate_id = 21;
        $this->lecturer_code = $this->getLecturerCode();
        $this->allowed_types["additionalMaterials"] = ['pdf', 'mp4', 'mpeg', 'docx', 'zip'];
        $this->allowed_types["teachingForm"] = ['pdf', 'ppt', 'zip'];
    }

    public function update(){
        return $this->restURIs(__FUNCTION__);
    }

    protected function update_post(){
        $request = new UpdateTeachRequest();
        if(empty($_POST['id'])){
            return $this->httpRequestInvalid();
        }
        $errors = $this->validate_update_post($request);
        if(!empty($errors)){
            http_response_code(422);
            return $this->load->view('json_view', [
                'json' => $errors,
            ]);
        }
        
        $id = $_POST['id'];
        $teachingRepo = new TeachingRepository();
        $teach = $teachingRepo->getByID($id);
        if(empty($teach)){
            return $this->httpRequestInvalid('Teaching record is not exist');
        }
        if($this->lecturer_code != $teach->UserIn){
            return $this->httpRequestInvalid('You are not allowed');
        }
        $file_teaching_form = $request->getFile('teachingForm')[0];
        if(!empty($file_teaching_form) && $file_teaching_form['size'] > 0){
            $uploadInstance = new AzureUpload($file_teaching_form);
            $teach->TeachingMaterialLocation = $uploadInstance->upload();
        }
        $params = [
            '_TeachID' => $id,
            '_UserUp' => $this->lecturer_code,
            '_TeachingPeriod' => $_POST['teachingPeriod'],
            '_Course' => $_POST['course'],
            '_TeachingMaterialLocation' => $teach->TeachingMaterialLocation,
        ];
        $teach = $teachingRepo->updateByID($params);
        if(empty($teach)){
            return $this->httpRequestInvalid('Error occured when updating data');
        }
        
        $param_update = [
            '_MaterialID' => $teach->MaterialID,
            '_UserUp' => $this->getLecturerCode(),
            '_LocationFile' => $teach->TeachingMaterialLocation,
        ];
        $result_material = $this->sp('bn_JKA_UpdateMaterialByID',$param_update ,self::$JKA_DB)->result();

        $files = $request->getFile();
        unset($files['additionalMaterials']);
        unset($files['teachingForm']);

        foreach($files as $key => $file){
            $file = $file[0];
            $material_id = explode('_', $key)[1];
            if($file['size'] > 0){
                $uploadInstance = new AzureUpload($file);
                $file_location = $uploadInstance->upload();
                $param_update = [
                    '_MaterialID' => $material_id,
                    '_UserUp' => $this->getLecturerCode(),
                    '_LocationFile' => $file_location,
                ];
                $result_material = $this->sp('bn_JKA_UpdateMaterialByID',$param_update ,self::$JKA_DB)->result();
            }
            
        }

        $additionalMaterials = $request->getFile('additionalMaterials');
        foreach($additionalMaterials as $file){
            if($file['size'] > 0){
                $uploadInstance = new AzureUpload($file);
                $file_location = $uploadInstance->upload();
            }
            $result_material = $this->sp('bn_JKA_InsertMaterial',[
                '_UserIn' => $this->getLecturerCode(),
                '_LocationFile' => $file_location,
            ],self::$JKA_DB)->result();

            $tr_material_result = $this->sp('bn_JKA_InsertTrMaterialSubType',[
                '_SubTypeID' => $id,
                '_MaterialID' => $result_material[0]->MaterialID,
                '_N_SUBITEM_ID' => Subtype::$TEACH,
                '_UserIn' => $this->getLecturerCode()
            ],self::$JKA_DB);
        }

        return $this->load->view('json_view', [
            'json' => $teach
        ]);
    }

    public function validate_update_post($request){
        $data = $request->data();
        $id = $data['id'];
        $file_teaching_form = $request->getFile('teachingForm')[0];
        $errors = [];
        if(empty($data['teachingPeriod'])){
            $errors['teachingPeriod'] = "Can't be empty";
        }
        if(empty($data['course'])){
            $errors['course'] = "Can't be empty";
        }
        
        $files = $request->getFile();
        if(isset($files['teachingForm']) && $file_teaching_form['size'] > 0){
            $allowed_types = $this->allowed_types['teachingForm'];
            if(!$this->checkMimeType($file_teaching_form, $allowed_types)){
                $errors['teachingForm'] = 'Only accept '.implode(", ", $allowed_types).' files';
            }
        }
        
        $additional_material_files = empty($files['additionalMaterials']) ? [] : $files['additionalMaterials'];
        $materialRepository = new MaterialRepository();
        $materials = $materialRepository->getMaterialsBySubtype($id, Subtype::$TEACH);
        if(count($materials) + count($additional_material_files) > 6){
            $errors['additionalMaterials'] = 'File upload limit reached. Max upload is 6 files.';
            return $errors;
        }

        if(!empty($additional_material_files)){
            $allowed_types = $this->allowed_types['additionalMaterials'];
            foreach ($additional_material_files as $file) {
                if(!$this->checkMimeType($file, $allowed_types)){
                    $errors['additionalMaterials'] = 'Only accept '.implode(", ", $allowed_types).' files';
                }   
            }
        }
        unset($files['teachingForm']);
        if(!empty($files)){
            $allowed_types = $this->allowed_types['additionalMaterials'];
            foreach($files as $additionalMaterials){
                if(empty($additionalMaterials[0]['tmp_name'])) break;
                foreach ($additionalMaterials as $file) {
                    if(!$this->checkMimeType($file, $allowed_types)){
                        $errors['additionalMaterials'] = 'Only accept '.implode(", ", $allowed_types).' files';
                    }   
                }
            }
        }
        return $errors;
    }

    public function checkMimeType($file, $allowed_types=[]){
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        if(empty($allowed_types)) return true;
        if(!in_array($ext, $allowed_types)) return false;
        return true;
    }

    public function delete(){
        return $this->restURIs(__FUNCTION__, true);
    }

    protected function delete_post(){
        $request = new GeneralRequest();
        $data = $request->data();
        if(empty($data['id'])){
            return $this->httpRequestInvalid();
        }
        $id = $data['id'];
        $teachingRepo = new TeachingRepository();
        
        $teach = $teachingRepo->getByID($id);
        if(empty($teach)){
            return $this->httpRequestInvalid('Data not found');
        }
        if($this->lecturer_code != $teach->UserIn){
            return $this->httpRequestInvalid('You are not allowed');
        }
        $param = [
            '_TeachingID' => $id,
            '_UserUp' => $this->lecturer_code,
        ];
        $result = $teachingRepo->deleteByParams($param);
        if(empty($result)){
            return $this->httpRequestInvalid('Error occured when deleting data');
        }
        return $this->load->view('json_view', [
            'json' => $result
        ]);
    }

    public function candidate(){
        return $this->restURIs(__FUNCTION__, true);
    }

    protected function candidate_post(){
        $_POST = $this->getBody();
        $errors = $this->validateCandidate();
        if(!empty($errors)){
            http_response_code(422);
            return $this->load->view("json_view", [
                'json' => $errors,
            ]);
        }

        $param_user = [
            '_LecturerCode' => $this->lecturer_code,
            '_PeriodID' => $_POST['period_id'],
        ];
        $user = $this->sp('bn_JKA_Input_GetCandidate', $param_user, self::$JKA_DB);
        if($user) $user = $user->result();
        else return $this->httpRequestInvalid('User not valid'); 
        if(empty($user)){
            return $this->httpRequestInvalid("You're not allowed");
        }
        $user = $user[0];
        
        $param_teaching = [
            '_CandidateID' => $user->CandidateTrID,
            '_N_ITEM_ID' => $_POST['item_id'],
            '_N_SUBITEM_ID' => $_POST['sub_item_id'],
        ];
        if(!$teachs = $this->sp('bn_JKA_GetTrTeachingByCandidateID', $param_teaching, self::$JKA_DB)){
            return $this->httpRequestInvalid();
        }
        $payload = array_map(function($teach){
            $param_materials = [
                '_SubTypeID' => $teach->TeachingTrID,
                '_N_SUBITEM_ID' => $teach->N_SUBITEM_ID
            ];
            $teach->AdditionalMaterials = [];    
            if($materials = $this->sp('bn_JKA_GetMaterialsBySubType', $param_materials, self::$JKA_DB)){
                $teach->AdditionalMaterials = $materials->result();    
            }
            return $teach;
        }, $teachs->result());
        
        return $this->load->view("json_view", [
            'json' => $payload,
        ]);
    }

    protected function validateCandidate(){
        $errors = [];
        if(empty($_POST['period_id'])) $errors['period'] = 'Can\'t be empty';
        if(empty($_POST['item_id'])) $errors['item_id'] = 'Can\'t be empty';
        if(empty($_POST['sub_item_id'])) $errors['sub_item_id'] = 'Can\'t be empty';

        return $errors;
    }

    public function getCandidateByLecturerCode($period_id, $lecturer_code){
        $param_user = [
            '_LecturerCode' => $lecturer_code,
            '_PeriodID' => $period_id,
        ];
        if(!$user = $this->sp('bn_JKA_Input_GetCandidate', $param_user, self::$JKA_DB)){
            return [];
        }
        return $user->result();
    }
}