<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
require_once('BaseController.php');

require_once('repos/CandidateRepository.php');
require_once('repos/MaterialRepository.php');
require_once('repos/ComdevRepository.php');

require_once('requests/UpdateComdevRequest.php');
require_once('requests/GeneralRequest.php');

require_once('strategies/upload/AzureUpload.php');

require_once('constants/Subtype.php');

class Comdev extends BaseController {
    
    static protected $JKA_DB = 'JKA_DB';
    private $lecture_code = null;
    static protected $STORAGE_DIR = "Competency Profiling\\Application\\";
    
    public function __construct(){
        parent::__construct();

        $this->lecturer_code = $this->getLecturerCode();
        $this->allowed_types['supportingMaterials'] = '*';
    }
    public function update(){
        return $this->restURIs(__FUNCTION__);
    }
    public function update_post(){
        $repo = new MaterialRepository();
        $request = new UpdateComdevRequest();
        $errors = $this->validate_update_post();
        if(!empty($errors)){ 
            http_response_code(422);
            return $this->load->view("json_view", [
                'json' => $errors,
            ]);
        }
        $comdevRepo = new ComdevRepository();
        $comdev = $comdevRepo->getByID($_POST['id']);
        if(empty($comdev)){
            return $this->httpRequestInvalid('Community development record is not exist');
        }
        if($this->lecturer_code != $comdev->UserIn){
            return $this->httpRequestInvalid('You are not allowed');
        }
        $id = $_POST['id'];
        $params = [
            '_ComdevID' => $_POST['id'],
            '_UserUp' => $this->lecturer_code,
            '_ActivityName' => $_POST['activity'],
            '_StartDt' => date("Y-m-d", strtotime($_POST['startDate'])),
            '_EndDt' => date("Y-m-d", strtotime($_POST['endDate'])),
        ];
        $comdev = $comdevRepo->updateByID($params);

        $files = $request->getFile();
        unset($files['supportingMaterials']);
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
                $result_material = $repo->updateByID($param_update);
            }
        }
        $supporting_material_files = $request->getFile('supportingMaterials');
        foreach($supporting_material_files as $file){
            if($file['size'] > 0){
                $uploadInstance = new AzureUpload($file);
                $file_location = $uploadInstance->upload();
            }
            $material = $repo->insert([
                '_UserIn' => $this->getLecturerCode(),
                '_LocationFile' => $file_location,
            ]);

            $tr_material_result = $repo->transactionSubtype([
                '_SubTypeID' => $id,
                '_MaterialID' => $material->MaterialID,
                '_N_SUBITEM_ID' => Subtype::$COMDEV,
                '_UserIn' => $this->getLecturerCode()
            ]);
        }
        
        return $this->load->view('json_view', [
            'json' => $comdev,
        ]);
    }
    public function validate_update_post(){
        $errors = [];
        $id = $_POST['id'];
        if(empty($id)){
            $errors['id'] = 'ID must be exist';
        }
        $activity_name = $_POST['activity'];
        if(empty($activity_name)){
            $errors['activity'] = 'Can\'t be empty';
        }
        $start_date = $_POST['startDate'];
        if(empty($start_date)){
            $errors['endDate'] = 'Can\'t be empty';
        }
        $end_date = $_POST['endDate'];
        if(empty($end_date)){
            $errors['endDate'] = 'Can\'t be empty';
        }
        return $errors;
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
        $comdevRepo = new ComdevRepository();
        $comdev = $comdevRepo->getByID($id);
        if(empty($comdev)){
            return $this->httpRequestInvalid('Community development record is not exist');
        }

        if($this->lecturer_code != $comdev->UserIn){
            return $this->httpRequestInvalid('You are not allowed');
        }
        $param = [
            '_ComdevID' => $id,
            '_UserUp' => $this->lecturer_code,
        ];
        $result = $comdevRepo->deleteByParams($param);
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
        $errors = $this->validate_candidate_post();
        if(!empty($errors)){
            http_response_code(422);
            return $this->load->view("json_view", [
                'json' => $errors,
            ]);
        }
        $candidateRepo = new CandidateRepository();
        $user = $candidateRepo->getCandidateByLecturerCodeAndPeriod($this->lecturer_code, $_POST['period_id']);
        if(empty($user)){
            return $this->httpRequestInvalid('You are not allowed'); 
        }
        $param_comdev = [
            '_CandidateID' => $user->CandidateTrID,
            '_N_ITEM_ID' => $_POST['item_id'],
            '_N_SUBITEM_ID' => $_POST['sub_item_id'],
        ];
        if(!$teachs = $this->sp('bn_JKA_GetTrComdevByCandidateID', $param_comdev, self::$JKA_DB)){   
            return $this->httpRequestInvalid();
        }

        $payload = array_map(function($teach){
            $param_materials = [
                '_SubTypeID' => $teach->ComdevTrID,
                '_N_SUBITEM_ID' => $teach->N_SUBITEM_ID
            ];
            $teach->SupportingMaterials = [];    
            if($materials = $this->sp('bn_JKA_GetMaterialsBySubType', $param_materials, self::$JKA_DB)){
                $teach->SupportingMaterials = $materials->result();    
            }
            return $teach;
        }, $teachs->result());
        
        return $this->load->view("json_view", [
            'json' => $payload,
        ]);
    }
    protected function validate_candidate_post(){
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

}