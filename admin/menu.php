<?php
IncludeModuleLangFile(__FILE__);

if ($APPLICATION->GetGroupRight('price_margin') > 'D') {
    $aMenu = array(
		'parent_menu' => 'global_menu_store',
		'section' => 'price_margin',
		'sort' => 1000,
		'url' => 'price_margin_list.php?lang='.LANGUAGE_ID,
		'text' => GetMessage('PRICE_MARGIN_MENU_MAIN'),
		'title' => GetMessage('PRICE_MARGIN_MENU_MAIN_TITLE'),
		'icon' => 'sale_menu_icon_catalog',
		'page_icon' => 'sale_menu_icon_catalog',
		'module_id' => 'price_margin',
		'items_id' => 'price_margin_menu',
	);
	return $aMenu;
} else {
    return false;
}
