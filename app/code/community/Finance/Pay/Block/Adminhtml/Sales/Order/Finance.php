<?php

class Finance_Pay_Block_Adminhtml_Sales_Order_Finance 
    extends Mage_Adminhtml_Block_Template
    implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
    protected $_chat = null;

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('finance/order-info.phtml');
    }

    public function getTabLabel ()
    {
        return $this->__('Finance Info');
    }

    public function getTabTitle ()
    {
        return $this->__('See relevant Finance information');
    }

    public function canShowTab () 
    {
        return true;
    }

    public function isHidden () 
    {
        return false;
    }

    public function getOrder () 
    {
        return Mage::registry('current_order');
    }

    public function getFinanceInfo ()
    {
        $financeProviderInfo = array(
            'proposal_id'    => null,
            'application_id' => null,
            'deposit_amount' => null,
        );
        
        $order   = $this->getOrder();
        $quoteId = $order->getQuoteId();

        $lookup  = Mage::getModel('callback/lookup');
        $lookup->load($quoteId, 'quote_id');

        if ($lookup->getId()) {
            if ($proposalId = $lookup->getCreditRequestId()) {
                $financeProviderInfo['proposal_id'] = $proposalId;
            }

            if ($applicationId = $lookup->getCreditApplicationId()) {
                $financeProviderInfo['application_id'] = $applicationId;
            }

            if ($depositAmount = $lookup->getDepositAmount()) {
                $financeProviderInfo['deposit_amount'] = $depositAmount;
            }
        }

        return $financeProviderInfo;
    }
}
