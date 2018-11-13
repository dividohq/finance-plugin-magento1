<?php
require_once('app/Mage.php');
Mage::app()->setCurrentStore(Mage::getModel('core/store')->load(Mage_Core_Model_App::ADMIN_STORE_ID));

 
$installer = $this;

$setup = new Mage_Eav_Model_Entity_Setup('core_setup');
$installer->startSetup();

/**
 * Adding a lookup table for Provider
 */
$conn = $installer->getConnection();
$lookup_table = $conn->newTable($installer->getTable('callback/lookup'));
$lookup_table->addColumn('lookup_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, 
        array(
            'identity' => true,
            'unsigned' => true,
            'nullable' => false,
            'primary'  => true,
        ), 
        'Id'
    )
    ->addColumn('salt', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, 
        array(
            'nullable' => false,
        ), 
        'Salt'
    )
    ->addColumn('quote_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, 
        array(
            'nullable' => false,
            'unsigned' => true,
        ), 
        'Quote ID'
    )
    ->addColumn($installer->getTable('callback/lookup'),
    'credit_request_id', 
    array(
        'type'     => Varien_Db_Ddl_Table::TYPE_TEXT,
        'nullable' => false,
        'comment' => 'Credit request ID',
        )   
    )
    ->addColumn($installer->getTable('callback/lookup'),
    'credit_application_id', 
    array(
        'type'     => Varien_Db_Ddl_Table::TYPE_TEXT,
        'nullable' => false,
        'comment' => 'Credit application ID',
    )
)->addColumn($installer->getTable('callback/lookup'),
'order_id',
array(
    'type' => Varien_Db_Ddl_Table::TYPE_INTEGER,
    'nullable' => true,
    'comment' => 'Order ID',
)
)
->addColumn($installer->getTable('callback/lookup'),
    'deposit_amount', 
    array(
        'type'      => Varien_Db_Ddl_Table::TYPE_NUMERIC,
        'nullable'  => false,
        'precision' => 10,
        'scale'     => 2,
        'comment'   => 'Credit application ID',
    )
)
->addColumn($installer->getTable('callback/lookup'),
    'canceled',
    array(
        'type' => Varien_Db_Ddl_Table::TYPE_BOOLEAN,
        'nullable' => true,
        'comment' => 'The application has ben cancelled',
    )
)->addColumn($installer->getTable('callback/lookup'),
'declined',
array(
    'type' => Varien_Db_Ddl_Table::TYPE_BOOLEAN,
    'nullable' => true,
    'comment' => 'The application was denied'
)
)->addColumn($installer->getTable('callback/lookup'),
'created_at',
array(
    'type' => Varien_Db_Ddl_Table::TYPE_DATETIME,
    'nullable' => true,
    'comment' => 'Record created at',
)
)
->addColumn($installer->getTable('callback/lookup'),
    'invalidated_at',
    array(
        'type' => Varien_Db_Ddl_Table::TYPE_DATETIME,
        'nullable' => true,
        'comment' => 'Record updated at',
    )
)->addColumn($installer->getTable('callback/lookup'),
'total_order_amount', 
array(
    'type'      => Varien_Db_Ddl_Table::TYPE_NUMERIC,
    'nullable'  => false,
    'precision' => 10,
    'scale'     => 2,
    'comment'   => 'Credit application ID',
)
)
->addColumn($installer->getTable('callback/lookup'),
    'customer_checkout',
    array(
        'type' => Varien_Db_Ddl_Table::TYPE_TEXT,
        'nullable' => true,
        'comment' => 'Checkout Type',
    )
)
    
    ;
$lookup_table->->addIndex(
    $installer->getTable('callback/lookup'),
    $installer->getIdxName('callback/lookup', 
        array('quote_id'), 
        Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX),
    array('quote_id'),
    Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX
));
        
$conn->createTable($lookup_table);

/**
 * Adding Divido attributes to products
 */

$groupName        = 'Divido';
$entityTypeId     = $setup->getEntityTypeId('catalog_product');
$defaultAttrSetId = $setup->getDefaultAttributeSetId($entityTypeId);

// adding attribute group
$setup->addAttributeGroup($entityTypeId, $defaultAttrSetId, $groupName, 1000);
$groupId = $setup->getAttributeGroupId($entityTypeId, $defaultAttrSetId, $groupName);

// Add attributes
$planOptionAttrCode =  'plan_option';
$setup->addAttribute($entityTypeId, $planOptionAttrCode, array(
    'label'            => 'Available on finance',
    'type'             => 'varchar',
    'input'            => 'select',
    'backend'          => 'eav/entity_attribute_backend_array',
    'frontend'         => '',
    'source'           => 'pay/source_option',
    'default'          => 'default_plans',
    'global'           => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    'visible'          => true,
    'required'         => true,
    'user_defined'     => true,
    'searchable'       => true,
    'filterable'       => false,
    'comparable'       => false,
    'visible_on_front' => true,
    'unique'           => false,
));
$planOptionAttrId = $setup->getAttributeId($entityTypeId, $planOptionAttrCode);
$setup->addAttributeToGroup($entityTypeId, $defaultAttrSetId, $groupId, $planOptionAttrId, null);
  
$planSelectionAttrCode = 'plan_selection';
$setup->addAttribute($entityTypeId, $planSelectionAttrCode, array(
    'label'            => 'Selected plans',
    'type'             => 'varchar',
    'input'            => 'multiselect',
    'backend'          => 'eav/entity_attribute_backend_array',
    'frontend'         => '',
    'source'           => 'pay/source_defaultprodplans',
    'global'           => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_GLOBAL,
    'visible'          => true,
    'required'         => false,
    'user_defined'     => true,
    'searchable'       => true,
    'filterable'       => false,
    'comparable'       => false,
    'visible_on_front' => true,
    'unique'           => false,
));
$planSelectionAttrId = $setup->getAttributeId($entityTypeId, $planSelectionAttrCode);
$setup->addAttributeToGroup($entityTypeId, $defaultAttrSetId, $groupId, $planSelectionAttrId, null);
 

$installer->endSetup();
