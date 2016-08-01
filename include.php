<?php
IncludeModuleLangFile(__FILE__);

if (!CModule::IncludeModule('iblock')) {
    $GLOBALS['APPLICATION']->ThrowException(GetMessage('PRICE_MARGIN_ERROR_IBLOCK_NOT_INSTALLED'));
    return false;
}

if (!CModule::IncludeModule('sale')) {
    $GLOBALS['APPLICATION']->ThrowException(GetMessage('PRICE_MARGIN_ERROR_SALE_NOT_INSTALLED'));
    return false;
}

if (!CModule::IncludeModule('catalog')) {
    $GLOBALS['APPLICATION']->ThrowException(GetMessage('PRICE_MARGIN_ERROR_CATALOG_NOT_INSTALLED'));
    return false;
}

global $DBType;

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/price_margin/classes/general/price.php");
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/price_margin/classes/general/margin.php");
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/price_margin/classes/" . $DBType . "/margin.php");


AddEventHandler("catalog", "OnGetOptimalPrice", "PriceMarginGetOptimalPrice");

function PriceMarginGetOptimalPrice(
    $intProductID,
    $quantity = 1,
    $arUserGroups = array(),
    $renewal = "N",
    $arPrices = array(),
    $siteID = false,
    $arDiscountCoupons = false
)
{
    global $firstLoad;

    if (empty($firstLoad)) {
        $firstLoad = "Y";
        $arPrice = \CCatalogProduct::GetOptimalPrice($intProductID, $quantity, $arUserGroups, $renewal, $arPrices, $siteID, $arDiscountCoupons);
        $prices = \Interprice\Price::getCurOptimalPrice($intProductID, $arPrice);
        $firstLoad = "";
        if (!empty($prices))
            return $prices;
        else
            return false;
    } else {
        return true;
    }
}