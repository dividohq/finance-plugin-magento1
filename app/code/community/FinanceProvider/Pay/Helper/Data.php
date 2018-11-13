<?php

require_once(Mage::getBaseDir('lib') . '/Divido/Divido.php');

class FinanceProvider_Pay_Helper_Data extends Mage_Core_Helper_Abstract
{
    const CACHE_KEY_PLANS      = 'finance_plans';
    const CACHE_LIFETIME_PLANS = 3600;

    public function getCurrentStore ()
    {
        if (! Mage::app()->getStore()->isAdmin()) {
            return null;
        }

        $store_id = 0;
        if (strlen($code = Mage::getSingleton('adminhtml/config_data')->getStore())) {
            $store_id = Mage::getModel('core/store')->load($code)->getId();
        }
        elseif (strlen($code = Mage::getSingleton('adminhtml/config_data')->getWebsite())) {
            $website_id = Mage::getModel('core/website')->load($code)->getId();
            $store_id = Mage::app()->getWebsite($website_id)->getDefaultStore()->getId();
        }

        return $store_id;
    }

    public function getApiKey ()
    {
        $store = $this->getCurrentStore();
        $apiKey = Mage::getStoreConfig('payment/pay/api_key', $store);
        if (empty($apiKey)) {
            Mage::log('API key not set', null, 'finance_provider.log');
            return array();
        }        
        $apiKey = Mage::helper('core')->decrypt($apiKey);

        return $apiKey;
    }

    public function getAllPlans ()
    {
        $apiKey = $this->getApiKey();
        $cacheKey = self::CACHE_KEY_PLANS . md5($apiKey);

        $cache = Mage::app()->getCache();
        if ($plans = $cache->load($cacheKey)) {
            $plans = unserialize($plans);
            return $plans;
        }

        Divido::setMerchant($apiKey);
        $parameters = array('merchant' => $apiKey);
        $response   = Divido_Finances::all($parameters);

        if ($response->status !== 'ok') {
            Mage::log('Could not get financing plans.', null, 'finance_provider.log');
            return array();
        }

        $plans = $response->finances;

        $cache->save(serialize($plans), $cacheKey, array('finance_provider_cache'), self::CACHE_LIFETIME_PLANS);

        return $plans;
    }

    public function setFulfilled ($applicationId, $shippingMethod = null, $trackingNumbers = null)
    {
        $apiKey = $this->getApiKey();
        $params = array(
            'application'    => $applicationId,
            'deliveryMethod' => $shippingMethod,
            'trackingNumber' => $trackingNumbers
        );

        Divido::setMerchant($apiKey);
        Divido_Activation::activate($params);
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
        $key = $this->getCommonApiKey();
        $scriptTags[] = '<script>window.dividoKey = "' . $key . '";</script>';

        $url = 'https://cdn.divido.com/calculator/v2.1/production/js/template.divido.js';
        $scriptTags[] = '<script src="' . $url . '"></script>';

        $html = implode("\n", $scriptTags);

        return $html;
    }

    //TODO REWORK WITH NEW JS
    public function getScriptUrl ()
    {
        $apiKey = Mage::getStoreConfig('payment/pay/api_key');
        if (empty($apiKey)) {
            return '';
        }

        $apiKey = Mage::helper('core')->decrypt($apiKey);
        $keyParts = explode('.', $apiKey);
        $coreKey = array_shift($keyParts);
        $jsKey = strtolower($coreKey);

        //return '<script src="//calc.divido.dev/calculator.php"></script>';
        return "<script src=\"https://cdn.divido.com/calculator/{$jsKey}.js\"></script>";
    }

    public function isActiveGlobal ()
    {
        $globalActive = Mage::getStoreConfig('payment/pay/active');

        return (bool) $globalActive;
    }

    public function isActiveLocal ($product, $price = null)
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

    public function getQuotePlans ($quote)
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
    public function getLocalPlans ($product)
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

    public function getGlobalSelectedPlans ()
    {
        // Get all finance plans
        $allPlans = $this->getAllPlans();

        // Get system settings 
        $globalPlansDisplayed = Mage::getStoreConfig('payment/pay/finances_displayed');
        $globalPlanList       = Mage::getStoreConfig('payment/pay/finances_list');
        $globalPlanList       = ! empty($globalPlanList) ? explode(',', $globalPlanList) : null;

        if ($globalPlansDisplayed == 'all_finances') {
            return $allPlans;
        }

        // Only showing selected finance plans
        $plans = array();
        foreach ($allPlans as $plan) {
            if (in_array($plan->id, $globalPlanList)) {
                $plans[] = $plan;
            }
        }

        return $plans;
    }

    public function plans2list ($plans)
    {
        $plansBare = array_map(function ($plan) {
            return $plan->id;
        }, $plans);

        $plansBare = array_unique($plansBare);

        return implode(',', $plansBare);
    }

    public function hashQuote ($salt, $quote_id)
    {
        return hash('sha256', $salt.$quote_id);
    }

    public function createSignature ($payload) {
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
        if($option=='popup_widget'){
            return 'data-divido-mode="popup"';
        }
        return;
    }
}
