<?php
global $MESS;
IncludeModuleLangFile(__FILE__);

if (class_exists('price_margin')) return;

class price_margin extends CModule
{
    var $MODULE_ID = 'price_margin';
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $MODULE_NAME;
    var $MODULE_DESCRIPTION;
    var $MODULE_GROUP_RIGHTS = 'Y';

    function price_margin()
    {
        $arModuleVersion = [];
        include(dirname(__FILE__) . '/version.php');
        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_NAME = GetMessage('PRICE_MARGIN_MODULE_NAME');
        $this->MODULE_DESCRIPTION = GetMessage('PRICE_MARGIN_MODULE_DESCRIPTION');
        $this->PARTNER_NAME = GetMessage('PRICE_MARGIN_PARTNER_NAME');
        $this->PARTNER_URI = "";
    }

    function DoInstall()
    {
        global $APPLICATION, $errors;
        $this->CheckModules();
        $this->InstallDB();
        if (empty($errors)) {
            $this->InstallFiles();
            RegisterModule('price_margin');
            $APPLICATION->IncludeAdminFile(GetMessage('PRICE_MARGIN_INSTALL_TITLE'), dirname(__FILE__) . '/install_success.php');
        } else {
            $APPLICATION->IncludeAdminFile(GetMessage('PRICE_MARGIN_INSTALL_TITLE'), dirname(__FILE__) . '/install_errors.php');
        }
    }

    function CheckModules()
    {
        global $APPLICATION, $errors;
        if (!IsModuleInstalled("catalog")) {
            $errors[] = GetMessage('PRICE_MARGIN_MODULE_CATALOG_NOT_INSTALL');
            $APPLICATION->ThrowException(GetMessage('PRICE_MARGIN_MODULE_CATALOG_NOT_INSTALL'));
        }
        if (!IsModuleInstalled("sale")) {
            $errors[] = GetMessage('PRICE_MARGIN_MODULE_SALE_NOT_INSTALL');
            $APPLICATION->ThrowException(GetMessage('PRICE_MARGIN_MODULE_SALE_NOT_INSTALL'));
        }
    }

    function InstallDB()
    {
        global $APPLICATION, $DB, $errors;
        $db_errors = $DB->RunSQLBatch(dirname(__FILE__) . '/db/' . strtolower($DB->type) . '/install.sql');
        if (!empty($db_errors)) {
            foreach ($db_errors as $error) {
                $errors[] = $error;
            }
            $APPLICATION->ThrowException(implode('', $db_errors));
            return false;
        }
        return true;
    }

    function InstallFiles()
    {
        CopyDirFiles(dirname(__FILE__) . "/admin", $_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin', true, true);
        return true;
    }

    function DoUninstall()
    {
        global $APPLICATION, $step, $errors;
        switch (intval($step)) {
            case 2:
                $this->UnInstallDB(array('savedata' => $_REQUEST['savedata']));
                if (empty($errors)) {
                    $this->UnInstallFiles();
                    UnRegisterModule('price_margin');
                    $APPLICATION->IncludeAdminFile(GetMessage('PRICE_MARGIN_UNINSTALL_TITLE'), dirname(__FILE__) . '/uninstall_success.php');
                } else {
                    $APPLICATION->IncludeAdminFile(GetMessage('PRICE_MARGIN_UNINSTALL_TITLE'), dirname(__FILE__) . '/uninstall_errors.php');
                }
                break;
            default:
                $APPLICATION->IncludeAdminFile(GetMessage('PRICE_MARGIN_UNINSTALL_TITLE'), dirname(__FILE__) . '/uninstall_step1.php');
        }
    }

    function UnInstallDB($arParams = array())
    {
        global $APPLICATION, $DB, $errors;

        if (!array_key_exists('savedata', $arParams) || $arParams['savedata'] != 'Y') {
            $errors = $DB->RunSQLBatch(dirname(__FILE__) . '/db/' . strtolower($DB->type) . '/uninstall.sql');
            if (!empty($errors)) {
                $APPLICATION->ThrowException(implode('', $errors));
                return false;
            }
        }
        return true;
    }

    function UnInstallFiles()
    {
        DeleteDirFiles(dirname(__FILE__) . '/admin', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin');
        return true;
    }

    function GetModuleRightList()
    {
        return array(
            'reference_id' => array('D', 'R', 'W'),
            'reference' => array(
                '[D] ' . GetMessage('PRICE_MARGIN_DENIED'),
                '[R] ' . GetMessage('PRICE_MARGIN_OPENED'),
                '[W] ' . GetMessage('PRICE_MARGIN_FULL'),
            ),
        );
    }
}
