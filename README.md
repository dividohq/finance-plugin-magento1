# Finance Plugin
Finance Plugin for Magento


## Uninstallation
Remove the following files and folders
```
app/code/community/Finance/Pay/
app/design/adminhtml/default/default/layout/finance_pay.xml
app/design/adminhtml/default/default/template/finance/
app/design/frontend/base/default/layout/finance.xml
app/design/frontend/base/default/template/pay/form/details.phtml
app/design/frontend/base/default/template/pay/widget.phtml
app/etc/modules/Finance_Payment.xml
js/Finance/
lib/Divido/
skin/frontend/base/default/css/Finance/
```

Run the following SQL queries 
```
DROP TABLE finance_lookup;
DELETE FROM core_resource WHERE code = 'Finance_Pay_setup';
DELETE FROM eav_attribute where attribute_code LIKE '%finance%'
```

Sometimes a user may have a prefix on their table - replacing <prefix> with the prefix of your db if it exists or removing it
```
DROP TABLE <prefix>_finance_lookup;
```
