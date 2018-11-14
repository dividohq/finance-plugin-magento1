<?php

class Finance_Pay_Model_System_Config_Financeoptions {
    public function toOptionArray ()
    {
        return array(
            array(
                'value' => 'all_finances',
                'label' => Mage::helper('adminhtml')->__('Display all plans'),
            ),
            array(
                'value' => 'selected_finances',
                'label' => Mage::helper('adminhtml')->__('Display selected plans'),
            ),
        );
    }
}
