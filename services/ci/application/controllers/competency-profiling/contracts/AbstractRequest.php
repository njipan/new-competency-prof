<?php

abstract class AbstractRequest{

    protected $errors = [];
    protected $request = null;
    protected $files = [];

    public function __construct(){
        $this->request = (empty($_POST)) ? $this->getBody() : $_POST;
        $this->request = (empty($this->request)) ? $_GET : $this->request;
        $this->files = $this->getFiles($_FILES);
        $this->errors = $this->validate();
    }

    public abstract function validate();

    public function getFile($field=null){
        if(empty($field)) return $this->files;
        if(!empty($this->files[$field])) return $this->files[$field];
        
        return [];
    }

    public function getErrors(){
        return $this->errors;
    }

    public function data(){
        return $this->request;
    }
    
    protected function getBody(){
        return json_decode(file_get_contents("php://input"), true);
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

    public function transform(){
        return $this->request;
    }
    
    public function array2xml($array, $xml = false){
        if($xml === false){
            $xml = new SimpleXMLElement('<root/>');
        }

        foreach($array as $key => $value){
            if(is_array($value)){
                $this->array2xml($value, $xml->addChild('candidate'));
            } else {
                $xml->addChild($key, $value);
            }
        }

        return $xml->asXML();
    }
}