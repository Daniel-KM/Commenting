<?php

class Table_Comment extends Omeka_Db_Table
{
    
    public function getSelect()
    {
        $select = parent::getSelect();
        $request = Zend_Controller_Front::getInstance()->getRequest();
        //only show approved comments to api without a proper key
        if($request->getControllerName() == 'api') {
            if(!is_allowed('Commenting_Comment', 'update-approved')) {
                $select->where('approved = ?', 1);
            }
        }
        return $select;
    }    
    
    public function applySearchFilters($select, $params)
    {
        if(isset($params['record'])) {
            $exploded = explode(',' , $params['record']);
            $params['record_type'] = $exploded[0];
            $params['record_id'] = $exploded[1];
        }
        parent::applySearchFilters($select, $params);
    }
    
    
}