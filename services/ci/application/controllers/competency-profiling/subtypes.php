<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
require_once('BaseController.php');
require_once('repos/CandidateRepository.php');
require_once('repos/ComdevRepository.php');
require_once('constants/Subtype.php');
require_once('strategies/upload/AzureUpload.php');
class Subtypes extends BaseController {
    
    static protected $JKA_DB = 'JKA_DB';

    public function __construct(){
        parent::__construct();
        $this->allowed_types = [
            "teachingForm" => [
                "pdf",
                "ppt",
            ],
            "additionalMaterials" => [
                "pdf",
                "mpeg",
                "mp4",
                "docx",
            ],
            "supportingMaterials" => [
                "docx",
                "pdf"
            ],
        ];
        $this->user_id = $this->getLecturerCode();
        $this->candidate_id = 21;
        $this->path = "/uploads/";
        $this->defaultConfig = [
            'remove_space' => FALSE,
        ];
        $this->load->library('upload', $this->defaultConfig);
        $this->lecturer_code = $this->getLecturerCode();
    }

    public function research(){
        return $this->restURIs(__FUNCTION__);
    } 

    public function research_post(){
        $errors = [];
        $info = $this->getAdditionalInfo();
        $body = $_POST;
        $files = $this->getFiles($_FILES);

        $this->lecturer_code = $this->getLecturerCode();
        if(empty($this->lecturer_code)){
            http_response_code(401);
            return $this->load->view("json_view",[
                'json' => 'You are not allowed',
            ]);
        }

        $userCandidate = $this->getCandidateObjectByLecturerCode($info['period_id'], $this->lecturer_code);
        if(empty($userCandidate)){
            return $this->httpRequestInvalid('You are not allowed');
        }
        $userCandidate = $userCandidate[0];
        $this->candidate_id = $userCandidate->CandidateTrID;
        
        foreach ($body as $id => $data) {
            $messages = $this->validateResearch($body[$id], $files[$id]);
            if(!empty($messages)) $errors[$id] = $messages;
        }
        if(sizeof($errors) > 0) {
            http_response_code(422);
            return $this->load->view("json_view",[
                'json' => $errors,
            ]);
        }

        $candidateRepo = new CandidateRepository();
        $user = $candidateRepo->getCandidateByLecturerCodeAndPeriod($this->lecturer_code, $info['period_id']);
        if(empty($user)){
            return $this->httpRequestInvalid('You are not allowed'); 
        }

        $payload = [];
        foreach ($body as $id => $research) {
            $param_insert_research = [
                '_UserIn' => $this->user_id,
                '_N_ITEM_ID' => $info["N_ITEM_ID"],
                '_DESCR50_ITEM' => $info["N_ITEM_DESCR"],
                '_N_SUBITEM_ID' => $info["N_SUBITEM_ID"],
                '_DESCR50_SUBITEM' => $info["N_SUBITEM_DESCR"],
                '_Year_Research' => (int)$research['year'],
                '_Title' => $research['title'],
                '_BudgetResource' => $research['budgetSource'],
                '_Budget' => $research['budget'],
                '_StatusKepID' => (int)$research['status'],
                '_LevelResearchID' => (int)$research['researchLevel'],
                '_PublisherName' => $research['publisher'],
                '_Volume' => $research['publisherVolume'],
                '_Year_Journal' => (int)$research['publisherYear'],
                '_Number' => (int)$research['publisherNumber'],
                '_ISSN_ISBN' => $research['publisherISSNISBN'],
                '_Publication_Title' => $research['publicationTitle'],
                '_Publication_Year' => (int)$research['publicationYear'],
                '_CandidateTrID' =>  $user->CandidateTrID,
            ];
            
            $result_research = $this->sp('bn_JKA_InsertTrResearch', $param_insert_research, self::$JKA_DB)->result()[0];
            foreach ($files[$id]["supportingMaterials"] as $file) {
                $uploadInstance = new AzureUpload($file);
                $file_location = $uploadInstance->upload();
                $result_material = $this->sp('bn_JKA_InsertMaterial',[
                    '_UserIn' => $this->user_id,
                    '_LocationFile' => $file_location,
                ],'JKA_DB')->result();
                $tr_material_result = $this->sp('bn_JKA_InsertTrMaterialSubType',[
                    '_SubTypeID' => $result_research->ResearchTrID,
                    '_MaterialID' => $result_material[0]->MaterialID,
                    '_N_SUBITEM_ID' => $info['N_SUBITEM_ID'],
                    '_UserIn' => $this->lecturer_code
                ],self::$JKA_DB);
            }
            $payload[] = $result_research;
        }
        return $this->load->view("json_view",[
            'json' => $payload,
        ]);

    }

    public function validateResearch($research, $files){
        $messages = [];
        $maxs = [
            'year' => [
                'min' => 1599,
                'max' => date('Y'),
            ],
            'title' => [
                'min' => 3,
                'max' => 100,
            ],
            'budgetSource' => [
                'min' => 3,
                'max' => 20,
            ],
            'budget' => [
                'min' => 0,
            ],
            'publisher' => [
                'max' => 100,
            ],
        ];
        $rule_types = [
            "supportingMaterials" => [
                "pdf",
                "jpeg",
                "jpg",
                "png",
            ],
        ];

        if(empty($research['year'])) 
            $messages["year"] = "Must be filled";
        else if(!is_numeric($research['year']))
            $messages["year"] = "Must be numeric";
        else if($research['year'] < $maxs['year']['min'])
            $messages["year"] = "Minimum ".$maxs['year']['min'];

        if(empty($research['title']))
            $messages["title"] = "Must be filled";
        if(empty($research['budgetSource']))
            $messages["budgetSource"] = "Must be filled";
        if(empty($research['budget']))
            $messages["budget"] = "Must be filled";
        else if(!is_numeric($research['budget']))
            $messages["budget"] = "Must be numeric";
        if(empty($research['status']))
            $messages["status"] = "Must be selected";
        if(empty($research['researchLevel']))
            $messages["researchLevel"] = "Must be selected";
        if(empty($research['publisher']))
            $messages["publisher"] = "Must be filled";
        if(empty($research['publisherVolume']))
            $messages["publisherVolume"] = "Must be filled";
        if(empty($research['publisherNumber']))
            $messages["publisherNumber"] = "Must be filled";
        if(empty($research['publisherYear']))
            $messages["publisherYear"] = "Must be filled";
        else if(!is_numeric($research['publisherYear']))
            $messages["publisherYear"] = "Must be numeric";
        else if($research['publisherYear'] < $maxs['year']['min'])
            $messages["publisherYear"] = "Minimum ".$maxs['year']['min'];
        if(empty($research['publisherISSNISBN']))
            $messages["publisherISSNISBN"] = "Must be filled";
        if(empty($research['publicationTitle']))
            $messages["publicationTitle"] = "Must be filled";
        if(empty($research['publicationYear'])) 
            $messages["publicationYear"] = "Must be filled";
        else if(!is_numeric($research['publicationYear']))
            $messages["publicationYear"] = "Must be numeric";
        
        foreach ($files['supportingMaterials'] as $file) {
            $message = $this->validateFile(
                $file, 
                $rule_types["supportingMaterials"]
            );
            if(!empty($message)){
                $messages['supportingMaterials'] = $message;
                break;    
            }
        }

        return $messages;
    }

    public function toefl(){
        return $this->restURIs(__FUNCTION__);
    }   

    protected function toefl_post(){
        $errors = [];
        if(empty($this->lecturer_code)){
            http_response_code(401);
            return $this->load->view("json_view",[
                'json' => 'You are not allowed',
            ]);
        }
        
        if(!empty($errors = $this->validate_toefl_post())){
            http_response_code(422);
            return $this->load->view("json_view",[
                'json' => $errors,
            ]);     
        }
        
        $certificate = $_FILES["certificate"];
        $uploadInstance = new AzureUpload($certificate);
        $certificate = $uploadInstance->upload();
        $result = $this->sp('bn_JKA_InsertMaterial',[
            '_UserIn' => $this->user_id,
            '_LocationFile' => $certificate,
        ],'JKA_DB')->result();

        $userCandidate = $this->getCandidateObjectByLecturerCode($_POST['period_id'], $this->lecturer_code);
        if(empty($userCandidate)){
            return $this->httpRequestInvalid('You are not allowed');
        }
        $userCandidate = $userCandidate[0];
        $this->candidate_id = $userCandidate->CandidateTrID;
        $payload = $this->sp('bn_JKA_InsertTrToefl',[
            '_UserIn' => $this->user_id,
            '_MaterialID' => $result[0]->MaterialID,
            '_CandidateTrID' => $this->candidate_id,
        ],'JKA_DB')->result();
        return $this->load->view("json_view",[
            'json' => $payload,
        ]);     
    }

    protected function validate_toefl_post(){
        $errors = [];
        if(empty($_POST['period_id']) || !is_numeric($_POST['period_id'])){
            $errors["period"] = "Period not valid";
        }
        if(empty($_FILES["certificate"]) || empty($_FILES["certificate"]["name"])){
            $errors["certificate"] = "File must be selected";
        }
        return $errors;
    }

    public function teach(){
        return $this->restURIs(__FUNCTION__);
    }

    protected function teach_post(){
        $errors = [];
        $info = $this->getAdditionalInfo();
        $body = $_POST;
        $files = $this->getFiles($_FILES);

        $userCandidate = $this->getCandidateObjectByLecturerCode($info['period_id'], $this->lecturer_code);
        if(empty($userCandidate)){
            return $this->httpRequestInvalid('You are not allowed');
        }
        $userCandidate = $userCandidate[0];
        $this->candidate_id = $userCandidate->CandidateTrID;
        
        foreach ($body as $id => $data) {
            $messages = $this->validateTeach($body[$id], $files[$id]);
            if(!empty($messages)) $errors[$id] = $messages;
        }
        if(sizeof($errors) > 0) {
            http_response_code(422);
            return $this->load->view("json_view",[
                'json' => $errors,
            ]);
        }
        $payload = [];
        foreach ($body as $id => $data) {
            $teach = [];
            $teach['course'] = $body[$id]['course'];
            $teach['teachingPeriod'] = $body[$id]['teachingPeriod'];
            $uploadInstance = new AzureUpload($files[$id]["teachingForm"]);
            $teaching_form_location = $uploadInstance->upload();

            $result = $this->sp('bn_JKA_InsertMaterial',[
                                '_UserIn' => $this->user_id,
                                '_LocationFile' => $teaching_form_location,
                            ],'JKA_DB')->result();
            $teach["teachingForm"] = $result[0];

            $tr_teaching = $this->sp('bn_JKA_InsertTrTeaching',[
                '_UserIn' => $this->user_id,
                '_N_ITEM_ID' => $info["N_ITEM_ID"],
                '_DESCR50_ITEM' => $info["N_ITEM_DESCR"],
                '_N_SUBITEM_ID' => $info["N_SUBITEM_ID"],
                '_DESCR50_SUBITEM' => $info["N_SUBITEM_DESCR"],
                '_TeachingPeriod' => $teach["teachingPeriod"],
                '_Course' => $teach["course"],
                '_TeachingMaterialLocation' => $teach["teachingForm"]->LocationFile,
                '_MaterialID' => $teach["teachingForm"]->MaterialID,
                '_CandidateTrID' => $this->candidate_id,
            ],'JKA_DB')->result(); 

            foreach ($files[$id]["additionalMaterials"] as $file) {
                $uploadInstance = new AzureUpload($file);
                $file_location = $uploadInstance->upload();
                $result = $this->sp('bn_JKA_InsertMaterial',[
                                '_UserIn' => $this->user_id,
                                '_LocationFile' => $file_location,
                            ],'JKA_DB')->result();
                $teach["additionalMaterials"][] = $result[0];

                $tr_material_result = $this->sp('bn_JKA_InsertTrMaterialSubType',[
                    '_SubTypeID' => $tr_teaching[0]->TeachingTrID,
                    '_MaterialID' => $result[0]->MaterialID,
                    '_N_SUBITEM_ID' => $info['N_SUBITEM_ID'],
                    '_UserIn' => $this->lecturer_code
                ],'JKA_DB');
            }   
            $payload = $tr_teaching[0];
        }

        return $this->load->view("json_view",[
            'json' => $payload,
        ]);
    }

    public function comdev(){
        $type = strtoupper($_SERVER['REQUEST_METHOD']);
        $func_name = __FUNCTION__."_".strtolower($type);
        if(!method_exists($this, $func_name)){
            return $this->httpRequestInvalid();
        }
        return $this->{$func_name}();
    }

    protected function comdev_post(){

        $errors = [];
        $info = $this->getAdditionalInfo();
        $body = $_POST;
        $files = $this->getFiles($_FILES);
        $this->lecturer_code = $this->getLecturerCode();
        if(empty($this->lecturer_code)){
            http_response_code(401);
            return $this->load->view("json_view",[
                'json' => 'You are not allowed',
            ]);
        }

        $userCandidate = $this->getCandidateObjectByLecturerCode($info['period_id'], $this->lecturer_code);
        if(empty($userCandidate)){
            return $this->httpRequestInvalid('You are not allowed');
        }
        $userCandidate = $userCandidate[0];
        $this->candidate_id = $userCandidate->CandidateTrID;

        foreach ($body as $id => $data) {
            $messages = $this->validateComdev($body[$id], $files[$id]);
            if(!empty($messages)) $errors[$id] = $messages;
        }

        if(sizeof($errors) > 0) {
            http_response_code(422);
            return $this->load->view("json_view",[
                'json' => $errors,
            ]);
        }

        $candidateRepo = new CandidateRepository();
        $user = $candidateRepo->getCandidateByLecturerCodeAndPeriod($this->lecturer_code, $info['period_id']);
        if(empty($user)){
            return $this->httpRequestInvalid('You are not allowed'); 
        }

        $payload = [];
        foreach ($body as $id => $data) {
            $comdev = [];
            $comdev['activity'] = $body[$id]['activity'];
            $comdev['startDate'] = $body[$id]['startDate'];
            $comdev['startDate'] = DateTime::createFromFormat('d-m-Y', $comdev["startDate"])->format('Y-m-d');
            $comdev['endDate'] = $body[$id]['endDate'];
            $comdev['endDate'] = DateTime::createFromFormat('d-m-Y', $comdev["endDate"])->format('Y-m-d');

            $result_comdev = $this->sp('bn_JKA_InsertTrComdev',[
                '_UserIn' => $this->lecturer_code,
                '_N_ITEM_ID' => $info["N_ITEM_ID"],
                '_DESCR50_ITEM' => $info["N_ITEM_DESCR"],
                '_N_SUBITEM_ID' => $info["N_SUBITEM_ID"],
                '_DESCR50_SUBITEM' => $info["N_SUBITEM_DESCR"],
                "_ActivityName" => $comdev["activity"],
                "_StartDt" => $comdev["startDate"],
                "_EndDt" => $comdev["endDate"],
                "_MaterialID" => NULL,
                "_CandidateTrID" => $user->CandidateTrID,
            ],'JKA_DB')->result();

            foreach ($files[$id]["supportingMaterials"] as $file) {
                $uploadInstance = new AzureUpload($file);
                $file_location = $uploadInstance->upload();

                $result_material = $this->sp('bn_JKA_InsertMaterial',[
                                '_UserIn' => $this->lecturer_code,
                                '_LocationFile' => $file_location,
                            ],'JKA_DB')->result();
                $comdev["supportingMaterials"][] = $result_material[0];

                $tr_material_result = $this->sp('bn_JKA_InsertTrMaterialSubType',[
                    '_SubTypeID' => $result_comdev[0]->ComdevTrID,
                    '_MaterialID' => $result_material[0]->MaterialID,
                    '_N_SUBITEM_ID' => $info['N_SUBITEM_ID'],
                    '_UserIn' => $this->lecturer_code
                ],self::$JKA_DB);
            }
            
            $payload[] = $result_comdev[0];
        }
        return $this->load->view("json_view",[
            'json' => $payload,
        ]);
    }

    protected function getAdditionalInfo(){
        $temp = json_decode($_POST["config"], true);
        unset($_POST["config"]);
        return $temp;
    }

    protected function getFiles($files) {
        $result = [];
        foreach($files as $name => $fileArray) {
            if (is_array($fileArray['name'])) {
                foreach ($fileArray as $attrib => $list) {
                    foreach ($list as $index => $value) {
                        if(!is_array($value)) {
                            $result[$name][$index][$attrib]=$value;
                        }
                        else{
                            $result[$name][$index] = [];
                            foreach ($value as $idx_list => $list_value) {
                                $result[$name][$index][] = [
                                    "name" => $files[$name]['name'][$index][$idx_list],
                                    "size" => $files[$name]['size'][$index][$idx_list],
                                    "tmp_name" => $files[$name]['tmp_name'][$index][$idx_list],
                                    "type" => $files[$name]['type'][$index][$idx_list],
                                    "error" => $files[$name]['error'][$index][$idx_list],
                                ];
                            }
                        }
                    }
                }
            } else {
                $result[$name][] = $fileArray;
            }
        }
        return $result;
    }

    protected function validateComdev($comdev, $files){
        $messages = [];
        $maxs['activity'] = 20;
        $rule_types = [
            "supportingMaterials" => [
                "pdf",
                "docx",
                "zip",
            ],
        ];

        if(empty($comdev['activity']) || trim($comdev['activity']) == '') 
            $messages["activity"] = "Must be filled";
        else if(strlen($comdev['activity']) > $maxs["activity"] )
            $messages["activity"] = "Maximum ".$maxs["activity"]." characters";

        if(empty($comdev['startDate']) || trim($comdev['startDate']) == '') 
            $messages["startDate"] = "Must be filled";

        if(empty($comdev['endDate']) || trim($comdev['endDate']) == '') 
            $messages["endDate"] = "Must be filled";
        
        foreach ($files['supportingMaterials'] as $file) {
            $message = $this->validateFile(
                $file, 
                $rule_types["supportingMaterials"]
            );
            if(!empty($message)){
                $messages['supportingMaterials'] = $message;
                break;    
            }
        }

        return $messages;
    }

    protected function validateTeach($teach, $files){
        $messages = [];
        $maxs = [
            "teaching_period" => 20,
            "course" => 255,
        ];
        $rule_types = [
            "teachingForm" => [
                "pdf",
                "ppt",
                "zip",
            ],
            "additionalMaterials" => [
                "pdf",
                "mpeg",
                "mp4",
                "docx",
                "zip",
            ]
        ];

        if(empty($teach['teachingPeriod']) || trim($teach['teachingPeriod']) == '') 
            $messages["teachingPeriod"] = "Must be filled";
        else if(strlen($teach['teachingPeriod']) > $maxs["teaching_period"] )
            $messages["teachingPeriod"] = "Maximum ".$maxs["teaching_period"]." characters";

        if(empty($teach['course']) || trim($teach['course']) == '') 
            $messages["course"] = "Must be filled";
        else if(strlen($teach['course']) > $maxs["course"] )
            $messages["course"] = "Maximum ".$maxs["course"]." characters";

        $message = $this->validateFile(
            $files["teachingForm"], 
            $rule_types["teachingForm"]
        );
        if(!empty($message)) $messages["teachingForm"] = $message;

        foreach ($files['additionalMaterials'] as $file) {
            $message = $this->validateFile(
                $file, 
                $rule_types["additionalMaterials"]
            );
            if(!empty($message)){
                $messages['additionalMaterials'] = $message;
                break;    
            }
        }

        return $messages;
    }

    protected function validateFile($file, $allowed_types, $max_size=20000000){
        $message = 'File must be selected';
        $ext = pathinfo($file["name"], PATHINFO_EXTENSION);
        if(empty($file['size']))
            $messages = 'File must be selected';
        else if($file['size'] > $max_size)
            $messages = "File exceeds maximum size ".$max_size."MB";
        else if(!in_array($ext, $allowed_types))
            $messages = 'Only accept '.implode(", ", $allowed_types)." files";
        else $message = '';
        return $message;
    }

    protected function getCandidateByLecturerCode($period_id, $lecturer_code){
        $param_user = [
            '_LecturerCode' => $lecturer_code,
            '_PeriodID' => $period_id,
        ];
        if(!$user = $this->sp('bn_JKA_Input_GetCandidate', $param_user, self::$JKA_DB)){
            return $this->httpRequestInvalid('User not valid');    
        }
        return $user->result();
    }

    public function getCandidateObjectByLecturerCode($period_id, $lecturer_code){
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