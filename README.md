Модуль bitrix (Наценка/скидка)
============

##Установка
1) Скопировать содержимое репозитория в ```АДРЕС_САЙТА/bitrix/modules/prise_margin/```

2) Перейти на страницу ```АДРЕС_САЙТА/bitrix/admin/module_admin.php```, найти в списке модуль "Наценка/Скидка (price_margin)" и нажать "Установить"

3) Добавить в файл ```АДРЕС_САЙТА/local/php_interface/init.php``` следующий код: 

    CModule::IncludeModule("price_margin");

Если при получении оптимальной цены используется метод ```\CIBlockPriceTools\GetItemPrices``` вместо ```CCatalogProduct::GetOptimalPrice```, то добавить в конец данного метода, расположенного в ```/bitrix/modules/iblock/classes/general/comp_pricetools.php``` перед строкой ```return $arPrices;``` следующий код:

    if(class_exists("\\Interprice\\Price")) {
        $arPrices = \Interprice\Price::changeCurPrice($arItem["ID"], $arPrices);   
    } 

##Использование
1) Перейти на страницу ```АДРЕС_САЙТА/bitrix/admin/price_margin_list.php?lang=ru```
2) Добавить наценки/скидки по желанию
