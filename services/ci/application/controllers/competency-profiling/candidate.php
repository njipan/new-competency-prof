<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
require_once('BaseController.php');
require_once('requests/SearchCandidateRequest.php');
require_once('requests/CreateCandidateRequest.php');
require_once('requests/SearchAddCandidateRequest.php');
require_once('requests/GeneralRequest.php');
require_once('requests/UpdateCandidateStatusRequest.php');
require_once('requests/UpdateJKACandidateRequest.php');
require_once('requests/PostCandidateRequest.php');
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

    public function proxy(){
        return $this->restUris(__FUNCTION__, true);
    }

    public function proxy_get(){
        $repo = new CandidateRepository();
        $lecturer_code = $this->getLecturerCode();
        $candidate = $repo->getCandidateByLecturerCode($lecturer_code);
        if(empty($candidate)){
            http_response_code(401);
            return $this->load->view('json_view', [
                'json' => 'You\'re not allowed',
            ]);
        }
        return $this->load->view('json_view', [
            'json' => 'OK',
        ]);
    }

    public function report(){
        return $this->restUris(__FUNCTION__, true);
    }

    public function report_get(){
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
        $result = $this->sp('bn_JKA_GetCandidates', $params, self::$JKA_DB);
        if(empty($result)){
            return $this->httpRequestInvalid('Error when getting data');
        }
        $result = $result->result();

        $formInst = $_GET['institution'];
        $formOrg = $_GET['organization'];
        $formOrgName = !empty($_GET['organization_name']) ? $_GET['organization_name'] : 'Not Valid';
        $formDep = $_GET['department'];
        $formDepName = !empty($_GET['department_name']) ? $_GET['department_name'] : 'Not Valid';
        $formDegree = !empty($_GET['department_degree']) ? $_GET['department_degree'] : 'Not Valid';
        $formPeriod = !empty($_GET['period_date']) ? $_GET['period_date'] : 'Not Valid';

        $count = count($Lecture);

        for ($i=0; $i < $count; $i++) {
            if (strstr($Lecture[$i],"!")) {
                $Lecture[$i] = str_replace("!", ",", $Lecture[$i]);
            }
            if (strstr($Lecture[$i],"#")) {
                $Lecture[$i] = str_replace("#", "–", $Lecture[$i]);
            }
        }

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
        $sheetExcel->getStyle('A1:U1')->applyFromArray($exampleTitleStyle);
        $sheetExcel->mergeCells('A1:U1');
        $sheetExcel->setCellValue('A1',$title_file);

        $lastIndex = 1;
        $lastIndex = $lastIndex + 2;
        $printedBy = "Printed by: ".$_SESSION['displayName'];
        $printDate = getdate();
        $printMonth = substr($printDate['month'],0,3);
        $printDate = "Date: ".$printDate['mday'] . " ". $printMonth . " ". $printDate['year'];
        $sheetExcel->setCellValue('A'.$lastIndex,$printedBy);
        $sheetExcel->setCellValue('A'.($lastIndex+1),$printDate);
        $printInst = "Institution: " . $formInst;
        $sheetExcel->setCellValue('A'.($lastIndex+2),$printInst);
        $printOrg = "Academic Organization: ";
        if ($formOrg == "*") {
            $printOrg = $printOrg . "All";
        } else {
            $printOrg = $printOrg . $formOrg . ' - ' . $formOrgName;
        }
        $sheetExcel->setCellValue('A'.($lastIndex+3),$printOrg);
        $printDep = "Department: ";
        if ($formDep == "*") {
            $printDep = $printDep . "All";
        } else {
            $printDep = $printDep . $formDep . ' - ' . $formDepName;
        }
        $sheetExcel->setCellValue('A'.($lastIndex+4),$printDep);
        $printPeriod = "Period: " . $formPeriod;
        $sheetExcel->setCellValue('A'.($lastIndex+5),$printPeriod);

        $sheetExcel->setCellValue('A10','No.')->getStyle('A10')->applyFromArray($exampleHeaderStyle);
        $sheetExcel->setCellValue('B10','Lecturer')->getStyle('B10')->applyFromArray($exampleHeaderStyle);
        $sheetExcel->setCellValue('C10','Current JKA')->getStyle('C10')->applyFromArray($exampleHeaderStyle);
        $sheetExcel->setCellValue('D10','Current Grade')->getStyle('D10')->applyFromArray($exampleHeaderStyle);
        $sheetExcel->setCellValue('E10','Next JKA')->getStyle('E10')->applyFromArray($exampleHeaderStyle);
        $sheetExcel->setCellValue('F10','Next Grade')->getStyle('F10')->applyFromArray($exampleHeaderStyle);
        $sheetExcel->setCellValue('G10','Institution')->getStyle('G10')->applyFromArray($exampleHeaderStyle);
        $sheetExcel->setCellValue('H10','Academic Organization')->getStyle('H10')->applyFromArray($exampleHeaderStyle);
        $sheetExcel->setCellValue('I10','Department')->getStyle('I10')->applyFromArray($exampleHeaderStyle);
        $sheetExcel->setCellValue('J10','Behavioral Date')->getStyle('J10')->applyFromArray($exampleHeaderStyle);
        $sheetExcel->setCellValue('K10','Period')->getStyle('K10')->applyFromArray($exampleHeaderStyle);
        $sheetExcel->setCellValue('L10','Reason')->getStyle('L10')->applyFromArray($exampleHeaderStyle);
        $sheetExcel->setCellValue('M10','Status')->getStyle('M10')->applyFromArray($exampleHeaderStyle);
        $sheetExcel->setCellValue('N10','Note')->getStyle('N10')->applyFromArray($exampleHeaderStyle);

        foreach($result as $i => $candidate){
            $lecturer = $candidate->LecturerCode.'-'.$candidate->Name;
            $department = $candidate->Dep.'-'.$candidate->DepName;
            $acad = $candidate->AcadOrg.'-'.$candidate->AcadName;
            $institution = $candidate->Institution.'-'.$candidate->InstitutionName;
            $sheetExcel->setCellValue('A'.($i+11),strval($i+1))->getStyle('A'.($i+11))->applyFromArray($exampleDetailStyle);
            $sheetExcel->setCellValue('B'.($i+11),$lecturer)->getStyle('B'.($i+11))->applyFromArray($exampleDetailStyle);
            $sheetExcel->setCellValue('C'.($i+11),$candidate->CurrentJKA)->getStyle('C'.($i+11))->applyFromArray($exampleDetailStyle);
            $sheetExcel->setCellValue('D'.($i+11),$candidate->CurrentGradeJKA)->getStyle('D'.($i+11))->applyFromArray($exampleDetailStyle);
            $sheetExcel->setCellValue('E'.($i+11),$candidate->NextJKA)->getStyle('E'.($i+11))->applyFromArray($exampleDetailStyle);
            $sheetExcel->setCellValue('F'.($i+11),$candidate->NextGradeJKA)->getStyle('F'.($i+11))->applyFromArray($exampleDetailStyle);
            $sheetExcel->setCellValue('G'.($i+11),$institution)->getStyle('G'.($i+11))->applyFromArray($exampleDetailStyle);
            $sheetExcel->setCellValue('H'.($i+11),$acad)->getStyle('H'.($i+11))->applyFromArray($exampleDetailStyle);
            $sheetExcel->setCellValue('I'.($i+11),$department)->getStyle('I'.($i+11))->applyFromArray($exampleDetailStyle);
            $sheetExcel->setCellValue('J'.($i+11),$candidate->BehaviourDate)->getStyle('J'.($i+11))->applyFromArray($exampleDetailStyle);
            $sheetExcel->setCellValue('K'.($i+11),$candidate->StartDt)->getStyle('K'.($i+11))->applyFromArray($exampleDetailStyle);
            $sheetExcel->setCellValue('L'.($i+11),$candidate->Reason)->getStyle('L'.($i+11))->applyFromArray($exampleDetailStyle);
            $sheetExcel->setCellValue('M'.($i+11),$candidate->Status)->getStyle('M'.($i+11))->applyFromArray($exampleDetailStyle);
            $sheetExcel->setCellValue('N'.($i+11),$candidate->Note)->getStyle('N'.($i+11))->applyFromArray($exampleDetailStyle);

            $sheetExcel->protectCells('A1:A'.($i+11), 'PHP');
            $lastIndex = ($i+11);
        }

        

        $sheetExcel->getColumnDimension('A')->setWidth(5);
        $sheetExcel->getColumnDimension('B')->setWidth(26);
        $sheetExcel->getColumnDimension('C')->setWidth(10);
        $sheetExcel->getColumnDimension('D')->setWidth(10);
        $sheetExcel->getColumnDimension('E')->setWidth(10);
        $sheetExcel->getColumnDimension('F')->setWidth(10);
        $sheetExcel->getColumnDimension('G')->setWidth(15);
        $sheetExcel->getColumnDimension('H')->setWidth(18);
        $sheetExcel->getColumnDimension('I')->setWidth(18);
        $sheetExcel->getColumnDimension('J')->setWidth(12);
        $sheetExcel->getColumnDimension('K')->setWidth(12);
        $sheetExcel->getColumnDimension('L')->setWidth(10);
        $sheetExcel->getColumnDimension('M')->setWidth(15);
        $sheetExcel->getColumnDimension('N')->setWidth(15);

        $filename = 'ListCandidateReport.xlsx';
                
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