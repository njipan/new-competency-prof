<?php
require_once(__DIR__.'/../contracts/AbstractRequest.php');

class DeleteMaterialRequest extends AbstractRequest {

    public function validate(){
        $errors = [];
        $data = $this->data();
        if(empty($data['material_id']) || !is_numeric($data['material_id'])){
            $errors['material_id'] = 'Material record is not exist';
        }

        return $errors;
    }

}