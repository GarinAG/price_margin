Модуль bitrix (Наценка/скидка)
============

Для корректной работы модуля необходимо: 

1) Добавить в файл init.php: 

    CModule::IncludeModule("price_margin");


2) Если при получении оптимальной цены используется метод ```\CIBlockPriceTools\GetItemPrices``` вместо ```CCatalogProduct::GetOptimalPrice```, то добавить в конец данного метода, расположенного в ```/bitrix/modules/iblock/classes/general/comp_pricetools.php``` перед строкой ```return $arPrices;``` следующий код:

    if(class_exists("\\Interprice\\Price")) {
        $arPrices = \Interprice\Price::changeCurPrice($arItem["ID"], $arPrices);   
    } 


