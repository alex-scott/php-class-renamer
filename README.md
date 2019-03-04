# php-class-renamer

A package to rename classes, specially useful to convert from PHP 5.2 to 5.3 with namespaces

-
<?php
namespace Am\Paysystem;

/**
 * @table paysystems
 * @id commonweb
 * @title  Commonwealth Bank of Australia 
 * @visible_link https://www.commbank.com.au/
 * @recurring amember
 */
class Commonweb extends CreditCard
{

    const
        PLUGIN_STATUS = self::STATUS_DEV,
        \PLUGIN_DATE = '$Date$', <!-----  error -----
        \PLUGIN_REVISION = '@@VERSION@@',
        \CONFIG_MERCHANT_ID = 'merchant_id',
        \CONFIG_API_PASSWORD = 'api-password',
        \CONFIG_TEST_MODE = 'test-mode',
        \CONFIG_SESSION_JS = 'https://paymentgateway.commbank.com.au/form/version/43/merchant/%s/session.js',
        \API_ENDPOINT = 'https://paymentgateway.commbank.com.au/api/nvp/version/43',
        \TOKEN = 'commweb-token',
        \FIELD_SESSION_ID = 'commonweb-session-id';
----
\Am\Paysystem\Transaction\Cardinity\3d <-- wrong classname
----
