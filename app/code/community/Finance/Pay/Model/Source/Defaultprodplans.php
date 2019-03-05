<?php
class Finance_Pay_Model_Source_Defaultprodplans extends Mage_Eav_Model_Entity_Attribute_Source_Table
{
    public function getAllOptions ($withEmpty = true, $defaultValues = false)
    {
        if ($this->_options) {
            return $this->_options;
        }

        $plans = Mage::helper('finance_pay')->getAllPlans();
        
        $this->_options = array();
        foreach ($plans as $plan) {
            $this->_options[] = array(
                'value' => $plan->id,
                'label' => $plan->description,
            );
        }

        return $this->_options;
    }
}
