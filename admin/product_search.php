<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_before.php';

$RIGHT = $APPLICATION->GetGroupRight('price_margin');
if ($RIGHT == 'D') {
    $APPLICATION->AuthForm(GetMessage('ACCESS_DENIED'));
}
CModule::IncludeModule("catalog");

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/price_margin/include.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/price_margin/prolog.php';
IncludeModuleLangFile(__FILE__);

if ($ex = $APPLICATION->GetException()) {
    require $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_admin_after.php';

    $strError = $ex->GetString();
    ShowError($strError);

    require $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_admin.php';
    die();
}


ClearVars();

$sTableID = 'tbl_catalog_product_search';
//sort
$oSort = new CAdminSorting($sTableID, 'ID', 'asc');
$lAdmin = new CAdminList($sTableID, $oSort);

$IBLOCK_ID = intval($IBLOCK_ID);
$dbIBlock = CIBlock::GetByID($IBLOCK_ID);
if (!($arIBlock = $dbIBlock->Fetch())) {
    $dbIBlock = CCatalog::GetList(array('NAME' => 'ASC'), array('MIN_PERMISSION' => 'R'));
    if($arIBlock = $dbIBlock->Fetch())
        $IBLOCK_ID = intval($arIBlock['ID']);
}

$BlockPerm = CIBlock::GetPermission($IBLOCK_ID);
$bBadBlock = ($BlockPerm < 'R');

if (!$bBadBlock) {
    $arFilterFields = array(
        'filter_section',
        'filter_subsections',
        'filter_id_start',
        'filter_id_end',
        'filter_timestamp_from',
        'filter_timestamp_to',
        'filter_active',
		'filter_name',
    );

    $lAdmin->InitFilter($arFilterFields);

    //create filter
    $arFilter = array();

    $arFilter = array(
       'WF_PARENT_ELEMENT_ID' => false,
       'IBLOCK_ID' => $IBLOCK_ID,
       'SECTION_ID' => $filter_section,
       'ACTIVE' => $filter_active,
       '?NAME' => $filter_name,
       'SHOW_NEW' => 'Y',
       // 'IBLOCK_TYPE' => 'catalog',
       'IBLOCK_TYPE' => $filter_type,
    );

    if (empty($filter_section)) {
        unset($arFilter['SECTION_ID']);
    } else if ($filter_subsections == 'Y') {
        if ($arFilter['SECTION_ID'] == 0) {
            unset($arFilter['SECTION_ID']);
        } else {
            $arFilter['INCLUDE_SUBSECTIONS'] = 'Y';
        }
    }

    if (!empty(${'filter_id_start'})) { $arFilter['>=ID'] = ${'filter_id_start'}; }
    if (!empty(${'filter_id_end'})) { $arFilter['<=ID'] = ${'filter_id_end'}; }
    if (!empty(${'filter_timestamp_from'})) { $arFilter['DATE_MODIFY_FROM'] = ${'filter_timestamp_from'}; }
    if (!empty(${'filter_timestamp_to'})) { $arFilter['DATE_MODIFY_TO'] = ${'filter_timestamp_to'}; }

    $dbResultList = CIBlockElement::GetList(
        array($by => $order),
        $arFilter,
        false,
        array('nPageSize' => CAdminResult::GetNavSize($sTableID)),
        ${'filter_count_for_show'}
    );

    $dbResultList = new CAdminResult($dbResultList, $sTableID);
    $dbResultList->NavStart();

    $lAdmin->NavText($dbResultList->GetNavPrint(GetMessage('sale_prod_search_nav')));

    $arHeaders = array(
        array('id' => 'ID', 'content' => 'ID', 'sort' => 'id', 'default' => true),
        array('id' => 'ACTIVE', 'content' => GetMessage('SPS_ACT'), 'sort' => 'active', 'default' => true),
        array('id' => 'NAME', 'content' => GetMessage('SPS_NAME'), 'sort' => 'name', 'default' => true),
        array('id' => 'ACT', 'content' => '&nbsp;', 'default' => true),
    );

    $lAdmin->AddHeaders($arHeaders);


    while ($arItems = $dbResultList->NavNext(true, 'f_')) {
        $row =& $lAdmin->AddRow($f_ID, $arItems);

        $row->AddField('ID', $f_ID);
        $row->AddField('ACTIVE', $f_ACTIVE);
        $row->AddField('NAME', $f_NAME);

        $URL = CIBlock::ReplaceDetailUrl($arItems['DETAIL_PAGE_URL'], $arItems, true);
        $row->AddField('ACT', '<a href="javascript:void(0)" onclick="SelEl('.$arItems['ID'].', \''.htmlspecialchars(str_replace('\'', '\\\'', str_replace('\\', '\\\\', $arItems['NAME']))).'\', \''.htmlspecialchars(str_replace('\'', '\\\'', str_replace('\\', '\\\\', $URL))).'\')">'.GetMessage('SPS_SELECT').'</a>');
	}

    $lAdmin->AddFooter(array(array(
        'title' => GetMessage('MAIN_ADMIN_LIST_SELECTED'),
        'value' => $dbResultList->SelectedRowsCount()
    )));
} else {
    echo ShowError(GetMessage('SPS_NO_PERMS').'.');
}

$lAdmin->CheckListMode();


/***************************************************************************
				HTML form
****************************************************************************/
$APPLICATION->SetTitle(GetMessage('SPS_SEARCH_TITLE'));
require $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_popup_admin.php';

$func_name = preg_replace('/[^a-z0-9_\\[\\]:]/i', '', $_REQUEST['func_name']);
$form_name = preg_replace('/[^a-z0-9_\\[\\]:]/i', '', $_REQUEST['form_name']);
$field_name = preg_replace('/[^a-z0-9_\\[\\]:]/i', '', $_REQUEST['field_name']);
$field_name_name = preg_replace('/[^a-z0-9_\\[\\]:]/i', '', $_REQUEST['field_name_name']);
$field_name_url = preg_replace('/[^a-z0-9_\\[\\]:]/i', '', $_REQUEST['field_name_url']);
$alt_name = preg_replace('/[^a-z0-9_\\[\\]:]/i', '', $_REQUEST['alt_name']);
?>

<script language="javascript">
<!--
function SelEl(id, name, url)
{
	<?php if ($new_value == 'Y'): ?>
		window.opener.<?php echo $func_name ?>(id, name, url);
	<?php else: ?>
		el = eval("window.opener.document.<?php echo $form_name ?>.<?php echo $field_name ?>");
		if(el)
			el.value = id;
		<?php if (strlen($field_name_name) > 0): ?>
			el = eval("window.opener.document.<?php echo $form_name ?>.<?php echo $field_name_name ?>");
			if(el)
				el.value = name;
		<?php endif ?>
		<?php if (strlen($field_name_url) > 0): ?>
			el = eval("window.opener.document.<?php echo $form_name ?>.<?php echo $field_name_url ?>");
			if(el)
				el.value = url;
		<?php endif ?>
		<?php if (strlen($alt_name) > 0): ?>
			el = window.opener.document.getElementById("<?php echo $alt_name ?>");
			if(el)
				el.innerHTML = name;
		<?php endif ?>
		window.close();
	<?php endif ?>
}
//-->
</script>
<!--filter -->
<form name="find_form" method="GET" action="<?php echo $APPLICATION->GetCurPage() ?>?">
    <input type="hidden" name="field_name" value="<?php echo htmlspecialchars($field_name) ?>">
    <input type="hidden" name="field_name_name" value="<?php echo htmlspecialchars($field_name_name) ?>">
    <input type="hidden" name="field_name_url" value="<?php echo htmlspecialchars($field_name_url) ?>">
    <input type="hidden" name="alt_name" value="<?php echo htmlspecialchars($alt_name) ?>">
    <input type="hidden" name="form_name" value="<?php echo htmlspecialchars($form_name) ?>">
    <input type="hidden" name="func_name" value="<?php echo htmlspecialchars($func_name) ?>">
    <input type="hidden" name="new_value" value="<?php echo htmlspecialchars($new_value) ?>">
<?php
$arIBTYPE = CIBlockType::GetByIDLang($arIBlock["IBLOCK_TYPE_ID"], LANG);

$oFilter = new CAdminFilter($sTableID.'_filter', array(
    'ID ('.GetMessage('SPS_ID_FROM_TO').')',
    GetMessage('SPS_TIMESTAMP'),
    ($arIBTYPE['SECTIONS'] == 'Y' ? GetMessage('SPS_SECTION') : null),
    GetMessage('SPS_ACTIVE'),
    GetMessage('SPS_NAME'),
));

$oFilter->Begin();
?>
    <tr>
        <td><?php echo GetMessage('SPS_CATALOG') ?>:</td>
        <td>
            <select name="IBLOCK_ID">
            <?php
            $db_iblocks = CIBlock::GetList(Array('NAME' => 'ASC'));
            while ($db_iblocks->ExtractFields('str_iblock_')): ?>
                <?if (CCatalog::GetByID($str_iblock_ID)):?>
                    <option value="<?php echo $str_iblock_ID ?>"<?php echo $IBLOCK_ID == $str_iblock_ID ? ' selected' : '' ?>><?php echo $str_iblock_NAME ?> [<?php echo $str_iblock_LID ?>] (<?php echo $str_iblock_ID ?>)</option>
                <?endif;?>
            <?endwhile;?>
            </select>
        </td>
    </tr>
    <tr>
        <td>ID (<?php echo GetMessage('SPS_ID_FROM_TO') ?>):</td>
        <td>
            <nobr>
            <input type="text" name="filter_id_start" size="10" value="<?php echo htmlspecialcharsex($filter_id_start) ?>">
            ...
            <input type="text" name="filter_id_end" size="10" value="<?php echo htmlspecialcharsex($filter_id_end) ?>">
            </nobr>
        </td>
    </tr>
    <tr>
        <td  nowrap><?php echo GetMessage('SPS_TIMESTAMP') ?> (<?php echo CLang::GetDateFormat('SHORT') ?>):</td>
        <td nowrap><?php echo CalendarPeriod('filter_timestamp_from', htmlspecialcharsex($filter_timestamp_from), 'filter_timestamp_to', htmlspecialcharsex($filter_timestamp_to), 'form1')?></td>
    </tr>
<?php if ($arIBTYPE['SECTIONS'] == 'Y'): ?>
    <tr>
        <td nowrap valign="top"><?php echo GetMessage('SPS_SECTION') ?>:</td>
        <td nowrap>
            <select name="filter_section">
                <option value="">(<?php echo GetMessage('SPS_ANY') ?>)</option>
                <option value="0"<?php echo $filter_section == '0' ? ' selected' : '' ?>><?php echo GetMessage('SPS_TOP_LEVEL') ?></option>
                <?php
                $bsections = CIBlockSection::GetTreeList(array('IBLOCK_ID' => $IBLOCK_ID));
                while ($bsections->ExtractFields('s_')): ?>
                <option value="<?php echo $s_ID ?>"<?php echo $s_ID == $filter_section ? ' selected' : '' ?>><?php echo str_repeat('&nbsp;.&nbsp;', $s_DEPTH_LEVEL) ?><?php echo $s_NAME?></option>
                <?php endwhile ?>
            </select>
            <br>
            <input type="checkbox" name="filter_subsections" value="Y"<?if($filter_subsections=="Y")echo" checked"?>> <?= GetMessage("SPS_INCLUDING_SUBS") ?>
        </td>
    </tr>
<?php endif ?>
    <tr>
        <td nowrap><?php echo GetMessage('SPS_ACTIVE') ?>:</td>
        <td nowrap>
            <select name="filter_active">
                <option value=""><?php echo htmlspecialcharsex('('.GetMessage('SPS_ANY').')')?></option>
                <option value="Y"<?php echo $filter_active == 'Y' ? ' selected' : '' ?>><?php echo htmlspecialcharsex(GetMessage('SPS_YES')) ?></option>
                <option value="N"<?php echo $filter_active == 'N' ? ' selected' : '' ?>><?php echo htmlspecialcharsex(GetMessage('SPS_NO'))?></option>
            </select>
        </td>
    </tr>
    <tr>
        <td nowrap><?php echo GetMessage('SPS_NAME') ?>:</td>
        <td nowrap>
            <input type="text" name="filter_name" value="<?php echo htmlspecialcharsex($filter_name)?>" size="30">
        </td>
    </tr>
<?php $oFilter->Buttons() ?>
<input type="submit" name="set_filter" value="<?php echo GetMessage('prod_search_find') ?>" title="<?php echo GetMessage('prod_search_find_title') ?>">
<input type="submit" name="del_filter" value="<?php echo GetMessage('prod_search_cancel') ?>" title="<?php echo GetMessage('prod_search_cancel_title') ?>">
<?php $oFilter->End() ?>
</form>

<?php $lAdmin->DisplayList() ?>
<br>
<input type="button" class="typebutton" value="<?php echo GetMessage('SPS_CLOSE') ?>" onClick="window.close();">
<?php require $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_popup_admin.php' ?>
