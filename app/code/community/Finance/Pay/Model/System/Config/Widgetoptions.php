<?php

class Finance_Pay_Model_System_Config_Widgetoptions {
    public function toOptionArray ()
    {
        return array(
            array(
                'value' => 'popup_widget',
                'label' => Mage::helper('adminhtml')->__('Popup Widget'),
            ),
            array(
                'value' => 'big_widget',
                'label' => Mage::helper('adminhtml')->__('Calculator View'),
            ),

        );
    }
}
