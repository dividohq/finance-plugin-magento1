# divido-magento
Divido for Magento

## Uninstallation
Remove the following files and folders
```
app/code/community/Divido/Pay/
app/design/adminhtml/default/default/layout/finance_provider_pay.xml
app/design/adminhtml/default/default/template/divido/
app/design/frontend/base/default/layout/divido.xml
app/design/frontend/base/default/template/pay/form/details.phtml
app/design/frontend/base/default/template/pay/widget.phtml
app/etc/modules/finance_provider_payment.xml
js/Divido/
lib/Divido/
skin/frontend/base/default/css/Divido/
```

Run the following SQL queries - replacing <prefix> with the prefix of your db if it exists or removing it
```
DROP TABLE <prefix>divido_lookup;
DELETE FROM <prefix>core_resource WHERE code = 'FinanceProvider_Pay_setup';
DELETE FROM eav_attribute where attribute_code LIKE '%divido%'
```
