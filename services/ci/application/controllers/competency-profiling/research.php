<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
require_once('BaseController.php');
require_once('repos/CandidateRepository.php');
require_once('repos/ResearchRepository.php');
require_once('requests/UpdateResearchRequest.php');
require_once('requests/GeneralRequest.php');
require_once('constants/Subtype.php');

require_once('strategies/upload/AzureUpload.php');

class Research extends BaseController {
    
    static protected $JKA_DB = 'JKA_DB';

    public function __construct(){
        parent::__construct();
        $this->lecturer_code = $this->getLecturerCode();
        $this->allowed_types['supportingMaterials'] = '*';
    }

    public function levels(){
    	return $this->restURIs(__FUNCTION__, true);
    }

    protected function levels_get(){
    	try{
            $levels = $this->sp('bn_JKA_GetLevelResearch', [], self::$JKA_DB)
            		->result();
            return $this->load->view("json_view",[
                'json' => $levels,
            ]);
        }catch(\Exception $e){
            return $this->httpRequestInvalid();
        }
    }

    public function memberships(){
    	return $this->restURIs(__FUNCTION__, true);
    }

    protected function memberships_get(){
    	try{
            $memberships = $this->sp('bn_JKA_GetStatusKepesertaan', [], self::$JKA_DB)
            		->result();
            return $this->load->view("json_view",[
                'json' => $memberships,
            ]);
        }catch(\Exception $e){
            return $this->httpRequestInvalid();
        }
    }

    public function update(){
    	return $this->restURIs(__FUNCTION__);
    }

    protected function update_post(){
        $request = new UpdateResearchRequest();
        if(empty($_POST['id'])){
            return $this->httpRequestInvalid();
        }
        $errors = $this->validate_update_post();

        if(!empty($errors)){
            http_response_code(422);
            return $this->load->view('json_view', [
                'json' => $errors,
            ]);
        }
        
        $id = $_POST['id'];
        $researchRepo = new ResearchRepository();
        $research = $researchRepo->getByID($id);
        if(empty($research)){
            return $this->httpRequestInvalid('Teaching record is not exist');
        }
        if($this->lecturer_code != $research->UserIn){
            return $this->httpRequestInvalid('You are not allowed');
        }
        $params = [
            '_ResearchID' => $id,
            '_UserUp' => $this->lecturer_code,
            '_Year_Research' => $_POST['year'],
            '_Title' => $_POST['title'],
            '_BudgetResource' => $_POST['budgetSource'],
            '_Budget' => $_POST['budget'],
            '_StatusKepID' => $_POST['status'],
            '_LevelResearchID' => $_POST['researchLevel'],
            '_PublisherName' => $_POST['publisher'],
            '_Volume' => $_POST['publisherVolume'],
            '_Year_Journal' => $_POST['publisherYear'],
            '_Number' => $_POST['publisherNumber'],
            '_ISSN_ISBN' => $_POST['publisherISSNISBN'],
            '_Publication_Title' => $_POST['publicationTitle'],
            '_Publication_Year' => $_POST['publicationYear'],
        ];
        $teach = $researchRepo->updateByID($params);
        if(empty($teach)){
            return $this->httpRequestInvalid('Error occured when updating data');
        }

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
                $result_material = $this->sp('bn_JKA_UpdateMaterialByID',$param_update ,self::$JKA_DB)->result();
            }
        }

        $supporting_material_files = $request->getFile('supportingMaterials');
        foreach($supporting_material_files as $file){
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
                '_N_SUBITEM_ID' => Subtype::$RESEARCH,
                '_UserIn' => $this->getLecturerCode()
            ],self::$JKA_DB);
        }

        return $this->load->view('json_view', [
            'json' => $teach
        ]);
    }

    public function validate_update_post(){
        $errors = [];
        if(empty($_POST['year'])){
            $errors['year'] = "Can't be empty";
        }
        if(!is_numeric($_POST['year'])){
            $errors['year'] = "Must be numeric";
        }
        if(empty($_POST['title'])){
            $errors['title'] = "Can't be empty";
        }
        if(empty($_POST['budgetSource'])){
            $errors['budgetSource'] = "Can't be empty";
        }
        if(empty($_POST['budget'])){
            $errors['budget'] = "Can't be empty";
        }
        if(!is_numeric($_POST['budget'])){
            $errors['budget'] = "Can't be empty";
        }
        if(empty($_POST['status'])){
            $errors['status'] = "Can't be empty";
        }
        if(!is_numeric($_POST['status'])){
            $errors['status'] = "Must be numeric";
        }
        if(empty($_POST['researchLevel'])){
            $errors['researchLevel'] = "Can't be empty";
        }
        if(!is_numeric($_POST['researchLevel'])){
            $errors['researchLevel'] = "Must be numeric";
        }
        if(empty($_POST['publisher'])){
            $errors['publisher'] = "Can't be empty";
        }
        if(empty($_POST['publisherVolume'])){
            $errors['publisherVolume'] = "Can't be empty";
        }
        if(empty($_POST['publisherYear'])){
            $errors['publisherYear'] = "Can't be empty";
        }
        if(empty($_POST['publisherNumber'])){
            $errors['publisherNumber'] = "Can't be empty";
        }
        if(empty($_POST['publisherISSNISBN'])){
            $errors['publisherISSNISBN'] = "Can't be empty";
        }
        if(empty($_POST['publicationTitle'])){
            $errors['publicationTitle'] = "Can't be empty";
        }
        if(empty($_POST['publicationYear'])){
            $errors['publicationYear'] = "Can't be empty";
        }
        if(!is_numeric($_POST['publicationYear'])){
            $errors['publicationYear'] = "Must be numeric";
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
        $researchRepo = new ResearchRepository();
        $research = $researchRepo->getByID($id);
        if(empty($research)){
            return $this->httpRequestInvalid('Data not found');
        }
        if($this->lecturer_code != $research->UserIn){
            return $this->httpRequestInvalid('You are not allowed');
        }
        $param = [
            '_ResearchID' => $id,
            '_UserUp' => $this->lecturer_code,
        ];
        $result = $researchRepo->deleteByParams($param);
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


    public function candidate_post(){
    	$_POST = $this->getBody();
        $errors = $this->validate_candidate_post();
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
        
        $param_research = [
            '_CandidateID' => $user->CandidateTrID,
            '_N_ITEM_ID' => $_POST['item_id'],
            '_N_SUBITEM_ID' => $_POST['sub_item_id'],
        ];

        if(!$researchs = $this->sp('bn_JKA_GetTrResearchByCandidateID', $param_research, self::$JKA_DB)){   
            return $this->httpRequestInvalid();
        }

        $payload = array_map(function($research){
            $param_materials = [
                '_SubTypeID' => $research->ResearchTrID,
                '_N_SUBITEM_ID' => $research->N_SUBITEM_ID
            ];
            $research->SupportingMaterials = [];    
            if($materials = $this->sp('bn_JKA_GetMaterialsBySubType', $param_materials, self::$JKA_DB)){
                $research->SupportingMaterials = $materials->result();    
            }
            return $research;
        }, $researchs->result());
        
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