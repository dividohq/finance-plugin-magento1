<?php

require_once Mage::getBaseDir('lib') . '/vendor/autoload.php';

class Finance_Pay_Helper_Data extends Mage_Core_Helper_Abstract
{
    const CACHE_KEY_PLANS      = 'finance_plans';
    const CACHE_LIFETIME_PLANS = 3600;

    public  $financeEnvironment;

    public function __construct()
    {
        $this->financeEnvironment = $this->getFinanceEnvironment();
    }

    /**
    * Define environment function
    *
    *  @param [string] $key - The API key.
    */
    function environments($key)
    {
        $array       = explode('_', $key);
        $environment = strtoupper($array[0]);
        if (constant("Divido\MerchantSDK\Environment::$environment") !== null ) {
            return constant("Divido\MerchantSDK\Environment::$environment");
        } else {
            return false;
        }
    }

    public function getCurrentStore()
    {
        if (! Mage::app()->getStore()->isAdmin()) {
            return null;
        }

        $store_id = 0;
        if (strlen($code = Mage::getSingleton('adminhtml/config_data')->getStore())) {
            $store_id = Mage::getModel('core/store')->load($code)->getId();
        } elseif (strlen($code = Mage::getSingleton('adminhtml/config_data')->getWebsite())) {
            $website_id = Mage::getModel('core/website')->load($code)->getId();
            $store_id = Mage::app()->getWebsite($website_id)->getDefaultStore()->getId();
        }
        return $store_id;
    }

    public function getApiKey()
    {
        $store = $this->getCurrentStore();
        $apiKey = Mage::getStoreConfig('payment/pay/api_key', $store);
        if (empty($apiKey)) {
            Mage::log('API key not set', null, 'finance.log');
            return array();
        }
        $apiKey = Mage::helper('core')->decrypt($apiKey);

        return $apiKey;
    }

    public function getSdk()
    {
        $apiKey = $this->getApiKey();
        $env = $this->environments($apiKey);
        $client = new \GuzzleHttp\Client();
        $httpClientWrapper = new \Divido\MerchantSDK\HttpClient\HttpClientWrapper(
            new \Divido\MerchantSDKGuzzle6\GuzzleAdapter($client),
            \Divido\MerchantSDK\Environment::CONFIGURATION[$env]['base_uri'],
            $apiKey
        );
        
        $sdk = new \Divido\MerchantSDK\Client($httpClientWrapper, $env);
        return $sdk;
    }

    public function getAllPlans()
    {
        $apiKey = $this->getApiKey();
        $cacheKey = self::CACHE_KEY_PLANS . md5($apiKey);
        $finances=array();

        $cache = Mage::app()->getCache();
        if ($plans = $cache->load($cacheKey)) {
            $plans = unserialize($plans);
            return $plans;
        }
        $sdk = $this->getSdk();
        $requestOptions = (new \Divido\MerchantSDK\Handlers\ApiRequestOptions());
        try {
            
            $plans = $sdk->getAllPlans($requestOptions);
            $finances = $plans->getResources();

        } catch (\Divido\MerchantSDK\Exceptions\MerchantApiBadResponseException $e) {
            if (Mage::getStoreConfig('payment/pay/debug')) {
                Mage::log('Could not get financing plans.:'.$e->getMessage(), null, 'finance.log');
            }
            return array();
        }
        $cache->save(serialize($finances), $cacheKey, array('finance_cache'), self::CACHE_LIFETIME_PLANS);

        return $finances;
    }

    public function getSingleApplication($applicationID)
    {
        $sdk = $this->getSdk();
        try {
            $application = $sdk->applications()->getSingleApplication($applicationID);
        } catch (\Divido\MerchantSDK\Exceptions\MerchantApiBadResponseException $e) {
            if (Mage::getStoreConfig('payment/pay/debug')) {
                Mage::log('Could not get Single Application:'.$e->getMessage(), null, 'finance.log');
            }
            return array();
        }
 

        return $application;
    }


    public function setFulfilled($applicationId, $shippingMethod = null, $trackingNumbers = null, $orderTotalInPence = null)
    {
        Mage::log('setFulfilled Helper function', Zend_Log::DEBUG, 'finance.log', true);
        $application = (new \Divido\MerchantSDK\Models\Application())
            ->withId($applicationId);
        $items       = array(
                    array(
                        'name'     => "Magento Order id: $applicationId",
                        'quantity' => 1,
                        'price'    => $orderTotalInPence,
                    ),
                );
            $applicationActivation = (new \Divido\MerchantSDK\Models\ApplicationActivation())
                ->withOrderItems($items)
                ->withAmount($orderTotalInPence)
                ->withReference('Magento 1 Order')
                ->withComment('Automatic activation activated.')
                ->withDeliveryMethod($shippingMethod)
                ->withTrackingNumber($trackingNumbers);

                // Create a new activation for the application.
                $sdk = $this->getSdk();
        try {
                    $response = $sdk->applicationActivations()->createApplicationActivation($application, $applicationActivation);
                    $activationResponseBody = $response->getBody()->getContents();
        } catch (\Divido\MerchantSDK\Exceptions\MerchantApiBadResponseException $e) {
            if (Mage::getStoreConfig('payment/pay/debug')) {
                Mage::log('Could not Activate Application:'.$e->getMessage(), null, 'finance.log');
            }
            return array();
        }
    }


    public function getCommonApiKey()
    {
        $apiKey = $this->getApiKey();
        $keyParts = explode('.', $apiKey);
        $commonKey = array_shift($keyParts);
        $normalized = strtolower($commonKey);

        return $normalized;
    }

    public function getScriptTags()
    {
        $scriptTags = [];
        $this->financeEnvironment;
        //$url = '//cdn.divido.com/widget/dist/'.$this->financeEnvironment.'.calculator.js';
        $key = $this->getCommonApiKey();

        $scriptTags[] = '<script type="text/javascript"> 
        //<![CDATA[  
           window.__widgetConfig = { apiKey:\'' . $key . '\',};
           //]]>
        </script>';
        //$scriptTags[] = '<script  src="' . $url . '"></script>';

        $html = implode("\n", $scriptTags);

        return $html;
    }

    public function getScriptTags2()
    {
        $scriptTags = [];
        $key = $this->getCommonApiKey();
        $this->financeEnvironment;
        $url1 = 'https://cdn.divido.com/widget/dist/'.$this->financeEnvironment.'.calculator.js';
        $scriptTags[] = '<script src="' . $url1 . '"></script>';
        $url = 'http://localhost/js/prototype/prototype.js';
        $scriptTags[] = '<script src="' . $url . '"></script>';
        $scriptTags[] = '<script type="text/javascript"> 
        //<![CDATA[  
           window.__widgetConfig = { apiKey:\'' . $key . '\',};
           //]]>
        </script>';
        $html = implode("\n", $scriptTags);

        return $html;
    }

    public function isActiveGlobal()
    {
        $globalActive = Mage::getStoreConfig('payment/pay/active');

        return (bool) $globalActive;
    }

    public function isActiveLocal($product, $price = null)
    {
        $globalActive = $this->isActiveGlobal();

        if (! $globalActive) {
            return false;
        }

        $productOptions        = Mage::getStoreConfig('payment/pay/product_options');
        $productPriceThreshold = Mage::getStoreConfig('payment/pay/product_price_treshold');

        if (! $price) {
            $price = $product['price'];
        }

        switch ($productOptions) {
            case 'products_price_treshold':
                if ($price < $productPriceThreshold) {
                    return false;
                }
                break;

            case 'products_selected':
                $productPlans = $this->getLocalPlans($product);
                if (! $productPlans) {
                    return false;
                }
        }
        return true;
    }

    public function getQuotePlans($quote)
    {
        if (! $quote) {
            return null;
        }

        $grandTotal = $quote->getGrandTotal();
        $items = $quote->getAllVisibleItems();
        $plans = array();
        foreach ($items as $item) {
            $mockProduct = array(
                'plan_selection' => $item->getProduct()->getData('plan_selection'),
                'plan_option'    => $item->getProduct()->getData('plan_option')
            );
            $localPlans = $this->getLocalPlans($mockProduct);
            $plans      = array_merge($plans, $localPlans);
        }

        foreach ($plans as $key => $plan) {
            $planMinTotal = $grandTotal - ($grandTotal * ($plan->min_deposit / 100));
            if ($plan->min_amount > $planMinTotal) {
                unset($plans[$key]);
            }
        }

        return $plans;
    }
    public function getLocalPlans($product)
    {
        // Get Global product settings
        $globalProdOptions = Mage::getStoreConfig('payment/pay/product_options');

        // Get Divido settings for product
        $productPlans    = $product['plan_option'];
        $productPlanList = $product['plan_selection'];
        $productPlanList = ! empty($productPlanList) ? explode(',', $productPlanList) : array();

        if ($productPlans == 'default_plans' || (empty($productPlans) && $globalProdOptions != 'products_selected')) {
            return $this->getGlobalSelectedPlans();
        }

        $allPlans = $this->getAllPlans();

        $plans = array();
        foreach ($allPlans as $plan) {
            if (in_array($plan->id, $productPlanList)) {
                $plans[] = $plan;
            }
        }

        return $plans;
    }

    public function getGlobalSelectedPlans()
    {
        // Get all finance plans.
        $allPlans = $this->getAllPlans();

        // Get system settings. 
        $globalPlansDisplayed = Mage::getStoreConfig('payment/pay/finances_displayed');
        $globalPlanList       = Mage::getStoreConfig('payment/pay/finances_list');
        $globalPlanList       = ! empty($globalPlanList) ? explode(',', $globalPlanList) : null;

        if ($globalPlansDisplayed == 'all_finances') {
            return $allPlans;
        }

        // Only showing selected finance plans.
        $plans = array();
        foreach ($allPlans as $plan) {
            if (in_array($plan->id, $globalPlanList)) {
                $plans[] = $plan;
            }
        }

        return $plans;
    }

    public function plans2list($plans)
    {
        $plansBare = array_map(
            function ($plan) {
                return $plan->id;
            }, $plans
        );

        $plansBare = array_unique($plansBare);

        return implode(',', $plansBare);
    }

    public function hashQuote($salt, $quote_id)
    {
        return hash('sha256', $salt.$quote_id);
    }

    public function createSignature($payload)
    {
        $secretEnc = Mage::getStoreConfig('payment/pay/secret');
        $secret    = Mage::helper('core')->decrypt($secretEnc);

        if (! $secret) {
            throw new Exception("No secret defined");
        }
    
        $hmac = hash_hmac('sha256', $payload, $secret, true);
        $signature = base64_encode($hmac);

        return $signature;
    }

    public function getWidgetOption()
    {
        return Mage::getStoreConfig('payment/pay/catalog_product_calculator_type');
    }

    public function returnWidgetHtml()
    {
        $option = $this->getWidgetOption();
        if ($option=='popup_widget') {
            return 'data-mode="lightbox"';
        }
        return 'data-mode="calculator"';
    }

    public function getFinanceEnvironment()
    {
        $this->financeEnvironment='divido';
        try {
            $sdk = $this->getSdk();
            $response = $sdk->platformEnvironments()->getPlatformEnvironment();
            $finance_env = $response->getBody()->getContents();
            $decoded =json_decode($finance_env);
            $financeEnvironment = $decoded->data->environment;
        } catch (Exception $e) {
            //add log
        }
           
        return $financeEnvironment;
    }
}
