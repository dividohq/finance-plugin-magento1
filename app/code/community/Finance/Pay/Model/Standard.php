<?php

/**
* Our test CC module adapter
*/
//require_once(Mage::getBaseDir('lib') . '/Divido/Divido.php');
class Finance_Pay_Model_Standard extends Mage_Payment_Model_Method_Abstract
{
    /**
    * unique internal payment method identifier
    *
    * @var string [a-z0-9_]
    */
    protected $_code = 'pay';
    protected $_formBlockType = 'pay/form_details';
	public function _construct()
    {
        /*
        Divido::setMerchant($this->getApiKey());
        if ($this->getMethod()->getConfigData('sandbox')) {
			Divido::setSandboxMode(true);
        }
        */
    }

	public function getApiKey()
    {
        $api_key = Mage::getStoreConfig('payment/pay/api_key');
        $api_key = Mage::helper('core')->decrypt($api_key);
        return $api_key;
    }

	/*
     * Set Merchant for divido using api key
     */
    /*
    public function _setMerchant()
    {
        Divido::setMerchant($this->getApiKey());
        $this->getApiKey();
		$resource = Mage::getSingleton('core/resource');
		$readConnection = $resource->getConnection('core_read');
		$table = $resource->getTableName('core_config_data');
		$sandbox_query = "Select value from $table where path='payment/pay/sandbox'";	
		$sandbox_value = $readConnection->fetchOne($sandbox_query); 	
		$sandbox = '';
		if($sandbox_value == '1'){
			Divido::setSandboxMode(true);
		}
    }
    */
	/*
	public function getCampaign()
    {
		$this->_setMerchant();
        $response = Divido_Finances::all(array(
                'merchant'=>$this->getApiKey()
        ));

        return $response;
    }
    */
	
	protected function getLogo()
    {
        return $this->getMethod()->getConfigData('logo');
    }
	
    /**
     * Return Order place redirect url
     *
     * @return string
     */
    public function getOrderPlaceRedirectUrl()
    {
          return Mage::getUrl('pay/payment/redirect', array('_secure' => true));
    }

    /**
     * Checkout redirect URL getter for onepage checkout (hardcode)
     *
     * @return string
     */
    public function getCheckoutRedirectUrl()
    {
        //TODO - CHANGE 
		$deposit = $_POST['divido_deposit'];
		$finance = $_POST['divido_plan'];

        $parameters = array(
            'divido_deposit' => $deposit,
            'divido_plan' => $finance
        );

        return Mage::getUrl('pay/payment/start', $parameters);
    }
    
    /**
     * Get checkout session namespace
     *
     * @return Mage_Checkout_Model_Session
     */
    public function getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Get current quote
     *
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        return $this->getCheckout()->getQuote();
    }

    public function isAvailable ($quote = null)
    {
        if($quote){
            $shippingEqBilling = $quote->getShippingAddress()->getSameAsBilling();
            if($shippingEqBilling)
            $hasPlans          = Mage::helper('finance_pay')->getQuotePlans($quote);
    
            $cartThreshold = (float) Mage::getStoreConfig('payment/pay/cart_threshold');
            $cartTotal     = (float) $quote->getGrandTotal();
            $aboveLimit    = $cartTotal >= $cartThreshold;
    
            $billingAddr   = $quote->getBillingAddress()->getData();
        }else{
            return false;
        }

        $countries_allowed = Mage::getStoreConfig('payment/pay/countries_allowed');
        if (! is_null($countries_allowed)) {
            $rightCountry = strpos($countries_allowed, $billingAddr['country_id']) !== false;
        } else {
            $rightCountry  = $billingAddr['country_id'] == 'GB';
        }
    
        return $shippingEqBilling && $hasPlans && $aboveLimit && $rightCountry;

    }
    
}
