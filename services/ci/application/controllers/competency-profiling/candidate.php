<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
require_once('BaseController.php');
require_once('requests/SearchCandidateRequest.php');
require_once('requests/CreateCandidateRequest.php');
require_once('requests/SearchAddCandidateRequest.php');
require_once('requests/GeneralRequest.php');
require_once('requests/UpdateCandidateStatusRequest.php');
require_once('requests/UpdateJKACandidateRequest.php');
require_once('requests/PostCandidateRequest.php');
require_once('requests/PrintCandidateRequest.php');

require_once('repos/CandidateRepository.php');

class Candidate extends BaseController {

    static protected $JKA_DB = 'JKA_DB';
    static protected $CMS_DB = 'CMS_DB';
    static protected $PERPAGE = 5;
    static protected $STATUS_ON_PROCESS = 6;
    static protected $NEW_CANDIDATE_STATUS = 1;
    static protected $STATUS_WAITING_HOP = 2;

    public function __construct(){
        parent::__construct();
    }

    private function isValidRequestForInputPage($user){
        $repo = new CandidateRepository();
        $candidate = $repo->getCandidateByLecturerCode($user);
        return !empty($candidate) && $candidate->StatusID == self::$STATUS_ON_PROCESS;
    }

    public function delete(){
        return $this->restUris(__FUNCTION__, true);
    }

    public function delete_post(){
        $lecturer_code = $this->getLecturerCode();
        if(!$this->isValidRequestForInputPage($lecturer_code)){
            $this->httpRequestInvalid('You are not allowed');
            http_response_code(401);
            return;
        }
        $request = new GeneralRequest();
        $data = $request->data();
        if(empty($data['id'])){
            $this->httpRequestInvalid('Request parameter is invalid');
            return;   
        }
        $candidate_id = $data['id'];
        $candidateRepo = new CandidateRepository();
        $candidate = $candidateRepo->delete($candidate_id);
        if(empty($candidate)){
            $this->httpRequestInvalid('Data is not valid');
            return;   
        }   
        return $this->load->view('json_view', [
            'json' => $candidate
        ]);
    }

    public function proxy(){
        return $this->restUris(__FUNCTION__, true);
    }

    public function proxy_get(){
        $lecturer_code = $this->getLecturerCode();
        if(!$this->isValidRequestForInputPage($lecturer_code)){
            $this->httpRequestInvalid('You are not allowed');
            http_response_code(401);
            return;
        }
    }

    public function report(){
        return $this->restUris(__FUNCTION__, true);
    }

    public function report_post(){
        $candidateRepo = new CandidateRepository();
        $request = new PrintCandidateRequest();
        $data = $request->data();
        $form = $data['form'];
        $errors = $request->getErrors();
        if(!empty($errors)){
            http_response_code(422);    
            return $this->load->view('json_view', [
                'json' => $errors,
            ]);
        }
        $params = $request->transform();
        $result = $candidateRepo->printCandidates($params);
        if(empty($result)){
            return $this->httpRequestInvalid('Error occured when getting data');
        }

        $institution = $form['institution'];
        $organization = $form['organization'];
        $organization_name = !empty($form['organization_name']) ? $form['organization_name'] : 'ALL';
        $department = $form['department'];
        $department_name = !empty($form['department_name']) ? $form['department_name'] : 'ALL';
        $period_date = !empty($form['period_date']) ? $form['period_date'] : 'NULL';

        $header_details = [
            'Printed By' => $_SESSION['displayName'],
            'Date' => date('d-M-Y'),
            'Institution' => $institution,
            'Academic Organization' => ($organization_name == '*') ? 'ALL' : $organization_name,
            'Department' => $department_name,
            'Period' => $period_date,
        ];

        $columns = [
            'No',
            'Lecture',
            'Institution',
            'Academic Organization',
            'Department',
            'Behavioral Date',
            'Period',
            'Current JKA',
            'Current Grade',
            'Next JKA',
            'Next Grade',
            'Reason',
            'Status',
            'Note',
        ];
        $columns_count = count($columns);
        $this->load->library('excel');
        
        $this->excel->setActiveSheetIndex(0);

        PHPExcel_Shared_Font::setAutoSizeMethod(PHPExcel_Shared_Font::AUTOSIZE_METHOD_EXACT);

        $exampleHeaderStyle = array(
            'alignment' => array(
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
                'wrap' => true,
                'shrinkToFit' => true
             ),
            'borders' => array(
                'allborders' => array(
                'style' => PHPExcel_Style_Border::BORDER_THIN
                )
            ),
            'fill' => array(
                'type' => PHPExcel_Style_Fill::FILL_SOLID,
                'color' => array('rgb' => 'cccccc')
            ),
            'font'  => array(
                'bold'  => true,
                'color' => array('rgb' => '000000'),
                'size'  => 8,
                'name'  => 'Calibri'
            )
        );
        
        $exampleTitleStyle = array(
            'alignment' => array(
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
                'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
                'wrap' => true,
                'shrinkToFit' => true
             ),
            'borders' => array(
                'allborders' => array(
                'style' => PHPExcel_Style_Border::BORDER_NONE 
                )
            ),
            'font'  => array(
                'bold'  => true,
                'color' => array('rgb' => '000000'),
                'size'  => 12,
                'name'  => 'Calibri'
            )
        );
        
        $examplePrinterStyle = array(
            'alignment' => array(
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
                'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
                'wrap' => true,
                'shrinkToFit' => true
             ),
            'borders' => array(
                'allborders' => array(
                'style' => PHPExcel_Style_Border::BORDER_NONE 
                )
            ),
            'font'  => array(
                'color' => array('rgb' => '000000'),
                'size'  => 12,
                'name'  => 'Calibri'
            )
        );

        $exampleDetailStyle = array(
            'alignment' => array(
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
                'wrap' => true,
                'shrinkToFit' => true
             ),
            'borders' => array(
                'allborders' => array(
                'style' => PHPExcel_Style_Border::BORDER_THIN
                )
            ),
            'font'  => array(
                'bold'  => false,
                'color' => array('rgb' => '000000'),
                'size'  => 8,
                'name'  => 'Calibri'
            )
        );
        $title_file = 'List Candidate Report';

        $sheetExcel = $this->excel->getActiveSheet();
        $sheetExcel->setTitle($title_file);
        $sheetExcel->getStyle('A1:N1')->applyFromArray($exampleTitleStyle);
        $sheetExcel->mergeCells('A1:N1');
        $sheetExcel->setCellValue('A1',$title_file);

        $sheetExcel->setCellValue('A1',$title_file);

        $last_index = 3;
        $character_begin = 65;
        foreach ($header_details as $column_name => $value) {
            $cell_field = 'A'.$last_index;
            $sheetExcel->setCellValue($cell_field, $column_name);
            $cell_value = 'B'.$last_index;
            $sheetExcel->setCellValue($cell_value, ': '.$value);
            $last_index++;
        }

        $last_index++;
        $i = 0;
        foreach ($columns as $value) {
            $cell = chr(65 + $i).$last_index;
            $sheetExcel->setCellValue($cell, $value)->getStyle($cell)->applyFromArray($exampleHeaderStyle);
            $i++;
        }
        $last_index++;

        foreach ($result as $index => $candidate) {
            $candidate = [
                ($index + 1),
                $candidate->LecturerCode.'-'.$candidate->Name,
                $candidate->Institution.'-'.$candidate->InstitutionName,
                $candidate->AcadOrg.'-'.$candidate->AcadName,
                $candidate->Dep.'-'.$candidate->DepName,
                $candidate->FormattedBehaviourDate ?: 'NULL',
                $candidate->Period ?: 'NULL',
                $candidate->CurrentJKA,
                $candidate->CurrentGradeJKA,
                $candidate->NextJKA,
                $candidate->NextGradeJKA,
                $candidate->Reason,
                $candidate->Status,
                $candidate->Note,
            ];
            foreach ($candidate as $idx_col => $value) {
                $cell = chr(65 + $idx_col).$last_index;
                $sheetExcel->setCellValue($cell, $value)->getStyle($cell)->applyFromArray($exampleDetailStyle);
            }
            $last_index++;
        }
        for ($i = 'A'; $i !=  $sheetExcel->getHighestColumn(); $i++) {
            $sheetExcel->getColumnDimension($i)->setAutoSize(TRUE);
        }

        $filename = 'List Candidate Report_'.date('d-M-Y').'.xlsx';
                
        header('Content-Type: application/ms-excel');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: private');

        $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'Excel2007'); 

        ob_end_clean();
        $objWriter->save('php://output');

    }

    public function add(){
        return $this->restUris(__FUNCTION__);
    }

    public function add_get(){
        $request = new SearchAddCandidateRequest();
        $errors = $request->validate();
        if(!empty($errors)){
            http_response_code(422);
            return $this->load->view('json_view', [
                'json' => $errors,
            ]);    
        }
        $params = $request->transform();
        $period_id = $params['_PeriodID'];

        $rows = $this->sp('bn_JKA_GetReasonForCandidate', $params, self::$JKA_DB);
        if(empty($rows)){
            return $this->httpRequestInvalid('Error when getting data');
        }
        
        return $this->load->view('json_view', [
            'json' => $rows->result()
        ]);
    }

    public function add_post(){
        $candidateRepo = new CandidateRepository();
        $request = new CreateCandidateRequest();
        $data = $request->data();
        $errors = $request->getErrors();
        if(!empty($errors)){
            http_response_code(422);
            return $this->load->view('json_view', [
                'json' => $errors,
            ]);
        }
        $params = $request->transform();
        $candidates = $candidateRepo->saveMultiple($request->transform());
        if(empty($candidates)){
            return $this->httpRequestInvalid('Error occured when saving data');
        }
        return $this->load->view('json_view', [
            'json' => $candidates,
        ]); 
    }

    public function update(){
        return $this->restUris(__FUNCTION__);
    }

    public function getLevel($param_level){
        $level = $this->sp('bn_JKA_GetLevelDescByJKADesc', $param_level, self::$JKA_DB);
        if(!$level) return null;
        $level = $level->result();
        if(empty($level)) return null;
        return $level[0];
    }

    public function update_post(){
        $candidateRepo = new CandidateRepository();
        $request = new UpdateJKACandidateRequest();
        $errors = $request->getErrors();
        if(!empty($errors['message'])){
            return $this->httpRequestInvalid($errors['message']);
        }
        $params = $request->transform();
        $candidates = $candidateRepo->updateJKAMultiples($params);
        if(empty($candidates)){
            return $this->httpRequestInvalid('No data updated');
        }
        
        return $this->load->view('json_view', [
            'json' => $candidates,
        ]);   
    }

    public function statuses(){
        return $this->restUris(__FUNCTION__);
    }

    public function statuses_post(){
        $repo = new CandidateRepository();
        $request = new UpdateCandidateStatusRequest();
        $errors = $request->getErrors();
        if(!empty($errors)){
            http_response_code(422);
            return $this->load->view('json_view', [
                'json' => $errors,
            ]);
        }

        $data = $request->data();
        if(empty($candidate = $repo->getCandidateByID($data['candidate_id']))){
            return $this->httpRequestInvalid('Invalid parameter request');
        }

        $params = $request->transform();
        $params["_JKA"] = $candidate->NextGradeJKA;
        if(empty($candidate = $repo->changeStatus($params))){
            return $this->httpRequestInvalid('Error occured when changing status');
        }

        return $this->load->view('json_view', [
            'json' => $candidate,
        ]);
    }

    public function post(){
        return $this->restUris(__FUNCTION__);
    }

    public function post_post(){
        $_POST = $this->getBody();
        $request = new PostCandidateRequest();
        $candidateRepo = new CandidateRepository();
        $errors = $request->getErrors();
        if(!empty($errors['message'])){
            return $this->httpRequestInvalid($errors['message']);
        }

        $candidates = [];
        foreach($request->data() as $item){
            $id = $item['CandidateTrID'];
            if(!$candidate = $candidateRepo->post($id)){
                return $this->httpRequestInvalid('Error occured when posting candidate');
            }
            $item['StatusID'] = self::$STATUS_WAITING_HOP;
            $candidates[] = $item;
        }

        return $this->load->view('json_view', [
            'json' => $candidates,
        ]);
    }

    public function reasons(){
        return $this->restUris(__FUNCTION__);
    }

    public function reasons_get(){
        $candidates = $this->sp('bn_JKA_GetReasonForCandidate', [], self::$JKA_DB);
        
        return $this->load->view('json_view', [
            'json' => $candidates->result()
        ]);
        
    }

    public function all(){
        return $this->restUris(__FUNCTION__, true);
    }

    public function all_get(){
        $request = new SearchCandidateRequest();
        $errors = $request->getErrors();
        if(!empty($errors)){
            http_response_code(422);    
            return $this->load->view('json_view', [
                'json' => $errors,
            ]);
        }
        $params = [
            '_InstitutionID' => $_GET['institution'],
            '_OrganizationID'=> $_GET['organization'],
            '_Department' => $_GET['department'],
            '_PeriodID' => $_GET['period_id'],
        ];
        $result = $this->sp('bn_JKA_GetCandidates', $params, 'JKA_DB');
        if(empty($result)){
            return $this->httpRequestInvalid();
        }
        $result = $result->result();
        return $this->load->view('json_view', [
            'json' => $result,
        ]);
    }

    public function information(){
    	$type = strtoupper($_SERVER['REQUEST_METHOD']);
        return $this->{__FUNCTION__."_".strtolower($type)}();
    }

    public function information_get(){
    	if(empty($_GET['EMPLID'])){
    		
    	}
    	$emplid = $_GET['EMPLID'];
    }

}