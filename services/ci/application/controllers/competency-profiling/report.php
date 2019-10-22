<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Report extends BM_Controller {

    static protected $JKA_DB = 'JKA_DB';
    static protected $CMS_DB = 'CMS_DB';
    static protected $PERPAGE = 5;

    public function __construct(){
        parent::__construct();
    }

    public function getFilterData() {
        $request = $this->rest->post()->listField;
        $data = array();
        $functionName = "";
        for ($i = 0; $i < count($request); $i++) {
            $functionName = "get".$request[$i];
            $data[$request[$i]] = $this->$functionName();
        }
        return $this->load->view("json_view", array("json" => $data));
    }

    public function columnLetter($c){

        $c = intval($c);
        if ($c <= 0) return '';
    
        $letter = '';
                 
        while($c != 0){
           $p = ($c - 1) % 26;
           $c = intval(($c - $p) / 26);
           $letter = chr(65 + $p) . $letter;
        }
        
        return $letter;
            
    }
    
    public function reportCandidate(){
        $post = $_POST;

        $formInst = $post['formInst'];
        $formOrg = $post['formOrg'];
        $formOrgName = $post['formOrgName'];
        $formDep = $post['formDep'];
        $formDepName = $post['formDepName'];
        $formDegree = $post['formDegree'];
        $formPeriod = $post['formPeriod'];

        $data = array(
            'INSTITUTION' => $post['INSTITUTION'],
            'ACADORG' => $post['ACADORG'],
            'DEPARTMENT' => $post['DEPARTMENT'],
            'PERIOD' => $post['PERIOD']
        );
        
        $result=$this->sp('bn_JKA_GetLectureForReport',$data,self::$JKA_DB)->result();

        if (count($result)>0) {
            $keys = array_keys(get_object_vars($result[0]));
        } else {
            $keys = [];
        }
        

        $usedKeys = 12;
        $usedKeysProfile = [
            "No.",
            "Lecturer",
            "Current JKA",
            "Current Grade",
            "Next JKA",
            "Next Grade",
            "Institution",
            "Academic Organization",
            "Department",
            "Behavioral Date",
            "Period",
            "Lecture Type"
        ];

        $usedKeysProfileDB = [
            '-',
            'Lecture',
            'CurrentJKA',
            'CurrentGrade',
            'NextJKA',
            'NextGrade',
            'Institution',
            'AcadOrg',
            'Department',
            'Behavioral',
            'Period',
            'LectureType'
        ];

        $usedKeysDynamic = [];
        $usedKeysDynamicID = [];
        $usedKeysSubtype = $this->sp('bn_JKA_GetSubTypeName',[],self::$JKA_DB)->result();
        $col = 'DESCR50';
        $colID = 'N_SUBITEM_ID';

        foreach($usedKeysSubtype as $sub => $s) {
            array_push($usedKeysDynamic,$s->$col . " Level");
            array_push($usedKeysDynamicID,$s->$colID);
        }

        $maxColumnLetter = 'A';
        if (count($result)>0) {
            $totalActual = count($keys) - 17;
            $maxColumnIdx = $usedKeys + $totalActual - 1;
            $maxColumnLetter = ($this->columnLetter($maxColumnIdx));
        } else {
            $noDataCount = $usedKeys + count($usedKeysDynamic);
            $maxColumnLetter = $this->columnLetter($noDataCount);
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

        $sheetExcel = $this->excel->getActiveSheet();
        $sheetExcel->setTitle('Competency Profiling Report');
        $sheetExcel->getStyle('A1:'.$maxColumnLetter.'1')->applyFromArray($exampleTitleStyle);
        $sheetExcel->mergeCells('A1:'.$maxColumnLetter.'1');
        $sheetExcel->setCellValue('A1','Competency Profiling Report');

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
        if ($formOrg == "All") {
            $printOrg = $printOrg . "All";
        } else {
            $printOrg = $printOrg . $formOrg . ' - ' . $formOrgName;
        }
        $sheetExcel->setCellValue('A'.($lastIndex+3),$printOrg);
        $printDep = "Department: ";
        if ($formOrg == "All") {
            $printDep = $printDep . "All";
        } else {
            $printDep = $printDep . $formDep . ' - ' . $formDepName . ' ('. $formDegree .')';
        }
        $sheetExcel->setCellValue('A'.($lastIndex+4),$printDep);
        $printPeriod = "Period: " . $formPeriod;
        $sheetExcel->setCellValue('A'.($lastIndex+5),$printPeriod);

        for ($i = 1; $i <= count($usedKeysProfile); $i++) {
            $cell = $this->columnLetter($i) . '10';
            $sheetExcel->setCellValue($cell,$usedKeysProfile[$i-1])->getStyle($cell)->applyFromArray($exampleHeaderStyle); 
        }
        
        $lastLetter = count($usedKeysProfile) + 1;
        for ($i = 1; $i<= count($usedKeysDynamic); $i++) {
            $cell = $this->columnLetter($lastLetter) . '10';
            $sheetExcel->setCellValue($cell,$usedKeysDynamic[$i-1])->getStyle($cell)->applyFromArray($exampleHeaderStyle);     
            $lastLetter++;
        }

        if (count($result) > 0) {
            for($i = 0 ; $i < count($result); $i++) {
                for($j = 0; $j < count($usedKeysProfile); $j++) {
                    if($j==0) {
                        $cell = $this->columnLetter($j+1) . strval($i+11);
                        $sheetExcel->setCellValue($cell,strval($i+1))->getStyle($cell)->applyFromArray($exampleDetailStyle);
                    } else {
                        $cell = $this->columnLetter($j+1) . strval($i+11);
                        $sheetExcel->setCellValue($cell,$result[$i]->$usedKeysProfileDB[$j])->getStyle($cell)->applyFromArray($exampleDetailStyle);
                    }
                }
    
                for($j = 0; $j < count($usedKeysDynamicID); $j++) {
                    $cell = $this->columnLetter($j+count($usedKeysProfile)+1) . strval($i+11);
                    $cellValue = $result[$i]->$usedKeysDynamicID[$j];        
                    if ($cellValue == NULL) {
                        $sheetExcel->setCellValue($cell,'0')->getStyle($cell)->applyFromArray($exampleDetailStyle);
                    } else {
                        $sheetExcel->setCellValue($cell,$cellValue)->getStyle($cell)->applyFromArray($exampleDetailStyle);
                    }
                }
            }   
        } else {
            $cell = 'A11:'.$maxColumnLetter.'11';
            $sheetExcel->mergeCells($cell);
            $sheetExcel->setCellValue('A11','No data available')->getStyle($cell)->applyFromArray($exampleDetailStyle);
            // $sheetExcel->setCellValue('A1','Competency Profiling Report');
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

        for ($i = 0; $i<count($usedKeysDynamicID); $i++) {
            $letter = $this->columnLetter($i+count($usedKeysProfile)+1);
            $sheetExcel->getColumnDimension($letter)->setWidth(15);
        }

        $filename = 'CompetencyProfilingReport.xlsx';
                
        header('Content-Type: application/ms-excel');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: private');

        $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'Excel2007'); 

        ob_end_clean();
        $objWriter->save('php://output');
    }

    public function reportCandidateBackup(){
        $post = $_POST;

        $Lecture = explode(",",$post['Lecture']);
        $CurrentJKA = explode(",",$post['CurrentJKA']);
        $CurrentGrade = explode(",",$post['CurrentGrade']);
        $NextJKA = explode(",",$post['NextJKA']);
        $NextGrade = explode(",",$post['NextGrade']);
        $Institution = explode(",",$post['Institution']);
        $AcadOrg = explode(",",$post['AcadOrg']);
        $Department = explode(",",$post['Department']);
        $Behavioral = explode(",",$post['Behavioral']);
        $Period = explode(",",$post['Period']);
        $LectureType = explode(",",$post['LectureType']);
        $TEACH = explode(",",$post['TEACH']);
        $RSCH = explode(",",$post['RSCH']);
        $COMDEV = explode(",",$post['COMDEV']);
        $COMPUTER = explode(",",$post['COMPUTER']);
        $TOEFL = explode(",",$post['TOEFL']);
        $LDRSHP = explode(",",$post['LDRSHP']);
        $TEAMWORK = explode(",",$post['TEAMWORK']);
        $FCSCOM = explode(",",$post['FCSCOM']);
        $KNOWIN = explode(",",$post['KNOWIN']);
        
        $formInst = $post['formInst'];
        $formOrg = $post['formOrg'];
        $formOrgName = $post['formOrgName'];
        $formDep = $post['formDep'];
        $formDepName = $post['formDepName'];
        $formDegree = $post['formDegree'];
        $formPeriod = $post['formPeriod'];

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

        $sheetExcel = $this->excel->getActiveSheet();
        $sheetExcel->setTitle('Competency Profiling Report');
        $sheetExcel->getStyle('A1:U1')->applyFromArray($exampleTitleStyle);
        $sheetExcel->mergeCells('A1:U1');
        $sheetExcel->setCellValue('A1','Competency Profiling Report');

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
        if ($formOrg == "All") {
            $printOrg = $printOrg . "All";
        } else {
            $printOrg = $printOrg . $formOrg . ' - ' . $formOrgName;
        }
        $sheetExcel->setCellValue('A'.($lastIndex+3),$printOrg);
        $printDep = "Department: ";
        if ($formOrg == "All") {
            $printDep = $printDep . "All";
        } else {
            $printDep = $printDep . $formDep . ' - ' . $formDepName . ' ('. $formDegree .')';
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
        $sheetExcel->setCellValue('L10','Lecture Type')->getStyle('L10')->applyFromArray($exampleHeaderStyle);
        $sheetExcel->setCellValue('M10','Teaching Level')->getStyle('M10')->applyFromArray($exampleHeaderStyle);
        $sheetExcel->setCellValue('N10','Research Level')->getStyle('N10')->applyFromArray($exampleHeaderStyle);
        $sheetExcel->setCellValue('O10','Community Development Level')->getStyle('O10')->applyFromArray($exampleHeaderStyle);
        $sheetExcel->setCellValue('P10','Computer Literacy Level')->getStyle('P10')->applyFromArray($exampleHeaderStyle);
        $sheetExcel->setCellValue('Q10','English Proficiency Level')->getStyle('Q10')->applyFromArray($exampleHeaderStyle);
        $sheetExcel->setCellValue('R10','Leadership Level')->getStyle('R10')->applyFromArray($exampleHeaderStyle);
        $sheetExcel->setCellValue('S10','Teamwork Level')->getStyle('S10')->applyFromArray($exampleHeaderStyle);
        $sheetExcel->setCellValue('T10','Focus & Commitment Level')->getStyle('T10')->applyFromArray($exampleHeaderStyle);
        $sheetExcel->setCellValue('U10','Knowledge Innovation Level')->getStyle('U10')->applyFromArray($exampleHeaderStyle);

        for($i = 0 ; $i < $count; $i++)
        {
            $sheetExcel->setCellValue('A'.($i+11),strval($i+1))->getStyle('A'.($i+11))->applyFromArray($exampleDetailStyle);
            $sheetExcel->setCellValue('B'.($i+11),$Lecture[$i])->getStyle('B'.($i+11))->applyFromArray($exampleDetailStyle);
            $sheetExcel->setCellValue('C'.($i+11),$CurrentJKA[$i])->getStyle('C'.($i+11))->applyFromArray($exampleDetailStyle);
            $sheetExcel->setCellValue('D'.($i+11),$CurrentGrade[$i])->getStyle('D'.($i+11))->applyFromArray($exampleDetailStyle);
            $sheetExcel->setCellValue('E'.($i+11),$NextJKA[$i])->getStyle('E'.($i+11))->applyFromArray($exampleDetailStyle);
            $sheetExcel->setCellValue('F'.($i+11),$NextGrade[$i])->getStyle('F'.($i+11))->applyFromArray($exampleDetailStyle);
            $sheetExcel->setCellValue('G'.($i+11),$Institution[$i])->getStyle('G'.($i+11))->applyFromArray($exampleDetailStyle);
            $sheetExcel->setCellValue('H'.($i+11),$AcadOrg[$i])->getStyle('H'.($i+11))->applyFromArray($exampleDetailStyle);
            $sheetExcel->setCellValue('I'.($i+11),$Department[$i])->getStyle('I'.($i+11))->applyFromArray($exampleDetailStyle);
            $sheetExcel->setCellValue('J'.($i+11),$Behavioral[$i])->getStyle('J'.($i+11))->applyFromArray($exampleDetailStyle);
            $sheetExcel->setCellValue('K'.($i+11),$Period[$i])->getStyle('K'.($i+11))->applyFromArray($exampleDetailStyle);
            $sheetExcel->setCellValue('L'.($i+11),$LectureType[$i])->getStyle('L'.($i+11))->applyFromArray($exampleDetailStyle);
            $sheetExcel->setCellValue('M'.($i+11),$TEACH[$i])->getStyle('M'.($i+11))->applyFromArray($exampleDetailStyle);
            $sheetExcel->setCellValue('N'.($i+11),$RSCH[$i])->getStyle('N'.($i+11))->applyFromArray($exampleDetailStyle);
            $sheetExcel->setCellValue('O'.($i+11),$COMDEV[$i])->getStyle('O'.($i+11))->applyFromArray($exampleDetailStyle);
            $sheetExcel->setCellValue('P'.($i+11),$COMPUTER[$i])->getStyle('P'.($i+11))->applyFromArray($exampleDetailStyle);
            $sheetExcel->setCellValue('Q'.($i+11),$TOEFL[$i])->getStyle('Q'.($i+11))->applyFromArray($exampleDetailStyle);
            $sheetExcel->setCellValue('R'.($i+11),$LDRSHP[$i])->getStyle('R'.($i+11))->applyFromArray($exampleDetailStyle);
            $sheetExcel->setCellValue('S'.($i+11),$TEAMWORK[$i])->getStyle('S'.($i+11))->applyFromArray($exampleDetailStyle);
            $sheetExcel->setCellValue('T'.($i+11),$FCSCOM[$i])->getStyle('T'.($i+11))->applyFromArray($exampleDetailStyle);
            $sheetExcel->setCellValue('U'.($i+11),$KNOWIN[$i])->getStyle('U'.($i+11))->applyFromArray($exampleDetailStyle);

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
        $sheetExcel->getColumnDimension('O')->setWidth(15);
        $sheetExcel->getColumnDimension('P')->setWidth(15);
        $sheetExcel->getColumnDimension('Q')->setWidth(15);
        $sheetExcel->getColumnDimension('R')->setWidth(15);
        $sheetExcel->getColumnDimension('S')->setWidth(15);
        $sheetExcel->getColumnDimension('T')->setWidth(15);
        $sheetExcel->getColumnDimension('U')->setWidth(15);


        $filename = 'CompetencyProfilingReport.xlsx';
                
        header('Content-Type: application/ms-excel');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: private');

        $objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'Excel2007'); 

        ob_end_clean();
        $objWriter->save('php://output');

        // return $this->load->view('json_view', array('json' => $candidate)); 
    }

    public function getCandidates() {
        $post = $this->rest->post();

        $data = array(
            'INSTITUTION' => $post->INSTITUTION,
            'ACADORG' => $post->ACADORG,
            'DEPARTMENT' => $post->DEPARTMENT,
            'PERIOD' => $post->PERIOD
        );
        
        $result=$this->sp('bn_JKA_GetLectureForReport',$data,self::$JKA_DB)->result();
        return $this->load->view('json_view', array('json' => $result));
    }
    
    public function getStatus() {
        $result=$this->sp('bn_JKA_GetAllStatus',array(),self::$JKA_DB)->result();
        return $this->load->view('json_view', array('json' => $result));
    }
    
    public function getScoreNames() {
        $result=$this->sp('bn_JKA_GetLevelScoreNames',array(),self::$JKA_DB)->result();
        return $this->load->view('json_view', array('json' => $result));
    }

    public function getPeriods(){
        $result = $this->sp('bn_JKA_GetPeriods',[],self::$JKA_DB)->result();
        return $this->load->view("json_view",['json' => $result]);
    }

    public function getInstitutions(){
        $result = $this->sp('bn_JKA_GetInstitutions',[],self::$JKA_DB)->result();
        return $this->load->view("json_view",['json' => $result]);
    }

    public function getOrganizations(){
        $result = $this->sp('bn_JKA_GetOrganizations',[],self::$JKA_DB)->result();
        return $this->load->view("json_view",['json' => $result]);
    }

    public function getDepartments(){
        $result = $this->sp('bn_JKA_GetDepartments',[],self::$JKA_DB)->result();
        return $this->load->view("json_view",['json' => $result]);
    }

    public function getSession(){
        $result = $_SESSION['employeeID'];
        return $this->load->view("json_view",['json' => $result]);
    }

    public function getRole(){
        $result = $_SESSION['RoleID'];
        return $this->load->view("json_view",['json' => $result]);
    }
    
    public function getProgramHeads(){
        $result = $this->sp('bn_JKA_GetHeadOfProgram',[],self::$JKA_DB)->result();
        return $this->load->view("json_view",['json' => $result]);
    }

    public function getAllStaff() {
        $result=$this->sp('bn_JKA_GetStaffData',array(),self::$JKA_DB)->result();
        return $this->load->view('json_view', array('json' => $result));
    }

    public function getmimetype($filename)
    {
        $idx = explode('.', $filename);
        $count_explode = count($idx);
        $idx = strtolower($idx[$count_explode - 1]);

        $mimet = array(
            'txt' => 'text/plain',
            'htm' => 'text/html',
            'html' => 'text/html',
            'php' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'swf' => 'application/x-shockwave-flash',
            'flv' => 'video/x-flv',

            // images
            'png' => 'image/png',
            'jpe' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpg',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'ico' => 'image/vnd.microsoft.icon',
            'tiff' => 'image/tiff',
            'tif' => 'image/tiff',
            'svg' => 'image/svg+xml',
            'svgz' => 'image/svg+xml',

            // archives
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            'exe' => 'application/x-msdownload',
            'msi' => 'application/x-msdownload',
            'cab' => 'application/vnd.ms-cab-compressed',

            // audio/video
            'mp3' => 'audio/mpeg',
            'qt' => 'video/quicktime',
            'mov' => 'video/quicktime',

            // adobe
            'pdf' => 'application/pdf',
            'psd' => 'image/vnd.adobe.photoshop',
            'ai' => 'application/postscript',
            'eps' => 'application/postscript',
            'ps' => 'application/postscript',

            // ms office
            'doc' => 'application/msword',
            'rtf' => 'application/rtf',
            'xls' => 'application/vnd.ms-excel',
            'ppt' => 'application/vnd.ms-powerpoint',
            'docx' => 'application/msword',
            'xlsx' => 'application/vnd.ms-excel',
            'pptx' => 'application/vnd.ms-powerpoint',


            // open office
            'odt' => 'application/vnd.oasis.opendocument.text',
            'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        );

        if (isset($mimet[$idx])) {
            return $mimet[$idx];
        } else {
            return 'application/octet-stream';
        }
    }
}