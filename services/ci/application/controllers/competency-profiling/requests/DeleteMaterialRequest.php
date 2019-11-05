<?php
require_once(__DIR__.'/../contracts/AbstractRequest.php');

require_once(__DIR__.'/../repos/MaterialRepository.php');

class DeleteMaterialRequest extends AbstractRequest {

    public function validate(){
        $errors = [];
        $data = $this->data();
        if(empty($data['material_id']) || !is_numeric($data['material_id'])){
            $errors['message'] = 'Material record is not exist';
            return $errors;
        }

        $materialRepo = new MaterialRepository();
        $materials = $materialRepo->getMaterialsBySubtype($data['subtype_id'], $data['sub_item_id']);
        if(count($materials) <= 1){
        	$errors['message'] = 'The last material cannot be deleted';
        }

        return $errors;
    }

}