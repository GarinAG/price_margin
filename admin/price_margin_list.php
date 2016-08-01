<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/price_margin/include.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/price_margin/prolog.php';
IncludeModuleLangFile(__FILE__);

//check access
$RIGHT = $APPLICATION->GetGroupRight('price_margin');
if ($RIGHT == 'D') {
    $APPLICATION->AuthForm(GetMessage('ACCESS_DENIED'));
}
//check errors
if ($ex = $APPLICATION->GetException()) {
    require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

    $strError = $ex->GetString();
    ShowError($strError);

    require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
    die();
}

$sTableID = \Interprice\CMargin::getTableName();
$oSort = new CAdminSorting($sTableID, 'ID', 'asc');
$lAdmin = new CAdminList($sTableID, $oSort);

$arFilter = array();
// "Save" button was pressed
if ($lAdmin->EditAction() && $RIGHT >= "W" && check_bitrix_sessid()) {
    foreach ($FIELDS as $ID => $arFields) {
        if (!$lAdmin->IsUpdated($ID))
            continue;

        // update each element
        $DB->StartTransaction();
        $ID = IntVal($ID);
        $cData = new \Interprice\CMargin;
        if (($rsData = $cData->GetByID($ID)) && ($arData = $rsData->Fetch())) {
            foreach ($arFields as $key => $value)
                $arData[$key] = $value;
            if (!$cData->Update($ID, $arData)) {
                $lAdmin->AddGroupError(GetMessage("SAVE_ERROR") . " " . $cData->LAST_ERROR, $ID);
                $DB->Rollback();
            }
        } else {
            $lAdmin->AddGroupError(GetMessage("SAVE_ERROR") . " " . GetMessage("NO_ELEMENT"), $ID);
            $DB->Rollback();
        }
        $DB->Commit();
    }
}
// single and group actions processing
if (($arID = $lAdmin->GroupAction()) && $RIGHT == "W" && check_bitrix_sessid()) {
    // if choose "All elements"
    if ($_REQUEST['action_target'] == 'selected') {
        $cData = new \Interprice\CMargin;
        $rsData = $cData->GetList(array($by => $order), $arFilter);
        while ($arRes = $rsData->Fetch())
            $arID[] = $arRes['ID'];
    }

    foreach ($arID as $ID) {
        if (strlen($ID) <= 0)
            continue;
        $ID = IntVal($ID);

        //choose and make action
        switch ($_REQUEST['action']) {
            // delete
            case "delete":
                @set_time_limit(0);
                $DB->StartTransaction();
                if (!\Interprice\CMargin::Delete($ID)) {
                    $DB->Rollback();
                    $lAdmin->AddGroupError(GetMessage("DELETE_ERROR"), $ID);
                }
                $DB->Commit();
                break;

            // activate/deactivate
            case "activate":
            case "deactivate":
                $cData = new \Interprice\CMargin;
                if (($rsData = $cData->GetByID($ID)) && ($arFields = $rsData->Fetch())) {
                    $arFields["ACTIVE"] = ($_REQUEST['action'] == "activate" ? "Y" : "N");
                    if (!$cData->Update($ID, $arFields))
                        $lAdmin->AddGroupError(GetMessage("SAVE_ERROR") . $cData->LAST_ERROR, $ID);
                } else
                    $lAdmin->AddGroupError(GetMessage("SAVE_ERROR") . " " . GetMessage("NO_ELEMENT"), $ID);
                break;
        }

    }
}
// list initialization - get data
$dbResultList = \Interprice\CMargin::GetList(array($by => $order), $arFilter);
$dbResultList = new CAdminResult($dbResultList, $sTableID);
//nav bar
$dbResultList->NavStart();
$lAdmin->NavText($dbResultList->GetNavPrint(GetMessage('PRICE_MARGIN_NAV')));

$lAdmin->AddHeaders(array(
    array('id' => 'ID', 'content' => 'ID', 'sort' => 'id', 'default' => true),
    array('id' => 'NAME', 'content' => GetMessage('PRICE_MARGIN_NAME'), 'sort' => 'name', 'default' => true),
    array('id' => 'SITE_ID', 'content' => GetMessage('PRICE_MARGIN_SITE'), 'sort' => 'site_id', 'default' => true),
    array('id' => 'ACTIVE', 'content' => GetMessage('PRICE_MARGIN_ACT'), 'sort' => 'active', 'default' => true),
    array('id' => 'ITEM', 'content' => GetMessage('PRICE_MARGIN_ITEM'), 'sort' => 'item', 'default' => true),
    array('id' => 'SECTION', 'content' => GetMessage('PRICE_MARGIN_SECTION'), 'sort' => 'section', 'default' => true),
    array('id' => 'USER_ID', 'content' => GetMessage('PRICE_MARGIN_USER_ID'), 'sort' => 'user_id', 'default' => true),
    array("id" => "GROUP", "content" => GetMessage("PRICE_MARGIN_GROUP"), "sort" => "group", "default" => true),
    array("id" => "PRICE", "content" => GetMessage("PRICE_MARGIN_PRICE"), "sort" => "price", "default" => true),
    array("id" => "MARGIN", "content" => GetMessage("PRICE_MARGIN_MARGIN"), "sort" => "margin", "default" => true),
));

//$arVisibleColumns = $lAdmin->GetVisibleHeaderColumns();

while ($arResult = $dbResultList->NavNext(true, 'f_')) {
    $row =& $lAdmin->AddRow($f_ID, $arResult);

    $row->AddField('ID', $f_ID);
    $row->AddField('SITE_ID', $f_SITE_ID);

    $row->AddViewField('ACTIVE', $f_ACTIVE);
    $row->AddViewField('NAME', $f_NAME);

    if (intval($f_ITEM) > 0) {
        $dbRes = \CIBlockElement::GetList(Array(), Array("ID" => $f_ITEM), false, false, Array("NAME", "ID"));
        if ($arEl = $dbRes->Fetch()) {
            $f_ITEM = $arEl["NAME"] . " (" . $arEl["ID"] . ")";
        }
        unset($dbRes);
    }
    else {
        $f_ITEM = "";
    }
    $row->AddViewField('ITEM', $f_ITEM);

    if (intval($f_SECTION) > 0) {
        $dbRes = \CIBlockSection::GetList(Array(), Array("ID" => $f_SECTION), false, Array("NAME", "ID"));
        if ($arEl = $dbRes->Fetch()) {
            $f_SECTION = $arEl["NAME"] . " (" . $arEl["ID"] . ")";
        }
        unset($dbRes);
    }
    else {
        $f_SECTION = "";
    }
    $row->AddViewField('SECTION', $f_SECTION);

    if (intval($f_USER_ID) > 0) {
        $dbRes = \CUser::GetByID($f_USER_ID);
        if ($arEl = $dbRes->Fetch()) {
            $f_USER_ID = $arEl["NAME"] . " (" . $arEl["ID"] . ")";
        }
        unset($dbRes);
    }
    else {
        $f_USER_ID = "";
    }
    $row->AddViewField('USER_ID', $f_USER_ID);

    if (intval($f_GROUP) > 0) {
        $dbRes = CGroup::GetByID($f_GROUP);
        if ($arEl = $dbRes->Fetch()) {
            $f_GROUP = $arEl["NAME"] . " (" . $arEl["ID"] . ")";
        }
        unset($dbRes);
    }
    else {
        $f_GROUP = "";
    }
    $row->AddViewField('GROUP', $f_GROUP);

    $row->AddViewField('PRICE', $f_PRICE);
    $row->AddViewField('MARGIN', $f_MARGIN);

    $arActions = array();
    $arActions[] = array('ICON' => 'edit', 'TEXT' => GetMessage('PRICE_MARGIN_UPDATE_ALT'), 'ACTION' => $lAdmin->ActionRedirect('price_margin_edit.php?ID=' . $f_ID . '&lang=' . LANG . GetFilterParams('filter_', false)), 'DEFAULT' => true);
    if ($RIGHT >= 'W') {
        $arActions[] = array('SEPARATOR' => true);
        $arActions[] = array('ICON' => 'delete', 'TEXT' => GetMessage('PRICE_MARGIN_DELETE_ALT'), 'ACTION' => 'if(confirm(\'' . GetMessage('PRICE_MARGIN_DELETE_CONF') . '\')) ' . $lAdmin->ActionDoGroup($f_ID, 'delete'));
    }
    $row->AddActions($arActions);
}

// list footer
$lAdmin->AddFooter(array(
    array(
        'title' => GetMessage('MAIN_ADMIN_LIST_SELECTED'),
        'value' => $dbResultList->SelectedRowsCount(),
    ),
    array(
        'counter' => true,
        'title' => GetMessage('MAIN_ADMIN_LIST_CHECKED'),
        'value' => 0,
    ),
));

if ($RIGHT == "W")
    // add list buttons
    $lAdmin->AddGroupActionTable(Array(
        "delete" => GetMessage("FORM_DELETE_L"),
        "activate" => GetMessage("MAIN_ADMIN_LIST_ACTIVATE"),
        "deactivate" => GetMessage("MAIN_ADMIN_LIST_DEACTIVATE")
    ));
// context menu
if ($RIGHT >= 'W') {
    $aContext = array(array(
        'TEXT' => GetMessage('PRICE_MARGIN_ADD_NEW'),
        'ICON' => 'btn_new',
        'LINK' => 'price_margin_edit.php?lang=' . LANG,
        'TITLE' => GetMessage('PRICE_MARGIN_ADD_NEW_ALT')
    ));
    $lAdmin->AddAdminContextMenu($aContext);
}

$lAdmin->CheckListMode();
$APPLICATION->SetTitle(GetMessage('PRICE_MARGIN_TITLE'));
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

$lAdmin->DisplayList();
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
