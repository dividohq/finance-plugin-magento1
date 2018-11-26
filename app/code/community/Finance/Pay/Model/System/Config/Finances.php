<?php

class Finance_Pay_Model_System_Config_Finances {

    public function toOptionArray () {
        $plans = array();

        try {
            $plans = Mage::helper('finance_pay')->getAllPlans();
        } catch (Exception $e) {
            Mage::logException($e);
        }
        
        $planOptions = array();
        foreach($plans as $plan) {
            $planOptions[] = array(
                'value' => $plan->id,
                'label' => $plan->description,
            );
        }

        return $planOptions;
    }
}
