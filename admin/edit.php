<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/price_margin/include.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/price_margin/prolog.php';
IncludeModuleLangFile(__FILE__);

//check access & exceptions
$RIGHT = $APPLICATION->GetGroupRight('price_margin');
if ($RIGHT == 'D')
    $APPLICATION->AuthForm(GetMessage('ACCESS_DENIED'));

if ($ex = $APPLICATION->GetException()) {
    require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

    $strError = $ex->GetString();
    ShowError($strError);

    require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
    die();
}

$aTabs = array(
    array('DIV' => 'edit1', 'TAB' => GetMessage('PRICE_MARGIN_TAB'), 'ICON' => 'price_margin', 'TITLE' => GetMessage('PRICE_MARGIN_TAB_DESCR')),
);

$tabControl = new CAdminTabControl('tabControl', $aTabs);
//Data processing
$ID = intval($ID);
$errorMessage = '';
$bVarsFromForm = false;


if ($RIGHT >= 'W' && $REQUEST_METHOD == 'POST' && !empty($Update) && check_bitrix_sessid()) {

    $price_margin = new \Interprice\CMargin();

    $DB->StartTransaction();

    $arFields = array(
        'NAME' => !empty($NAME) ? $NAME : '',
        'SITE_ID' => $SITE_ID,
        'ACTIVE' => $ACTIVE == 'Y' ? 'Y' : 'N',
        'ITEM' => !empty($ITEM) ? $ITEM : '',
        'SECTION' => !empty($SECTION) ? $SECTION : '',
        'PRICE' => !empty($PRICE) ? $PRICE : '',
        'MARGIN' => !empty($MARGIN) ? $MARGIN : '',
        'USER_ID' => !empty($USER_ID) ? $USER_ID : '',
        'GROUP' => !empty($GROUP) ? $GROUP : '',
        'USER_MODIFIER' => $USER->GetID(),
    );

    if (!empty($ID)) {
        $res = $price_margin->Update($ID, $arFields);
    } else {
        $ID = $price_margin->Add($arFields);
        $res = ($ID > 0);
    }

    if (!$res) {
        $ex = $APPLICATION->GetException();
        $errorMessage .= $ex->GetString() . '<br />';
        $bVarsFromForm = true;
        $DB->Rollback();
    } else {
        $DB->Commit();
        if (!empty($apply)) {
            $_SESSION["SESS_ADMIN"]["POSTING_EDIT_MESSAGE"] = array("MESSAGE" => GetMessage("PRICE_MARGIN_SAVE_OK"), "TYPE" => "OK");
            LocalRedirect("/bitrix/admin/price_margin_edit.php?ID=" . $ID . "&lang=" . LANG . "&" . $tabControl->ActiveTabParam());
        } else {
            LocalRedirect('/bitrix/admin/price_margin_list.php?lang=' . LANGUAGE_ID);
        }
    }
}

ClearVars();
//get data from DB
if ($ID > 0) {
    $dbMargin = \Interprice\CMargin::GetById($ID);
    if (!$dbMargin->ExtractFields('str_'))
        $ID = 0;
}

//if data from form
if ($bVarsFromForm) {
    $DB->InitTableVarsForEdit(\Interprice\CMargin::getTableName(), '', 'str_');
    if ($str_ACTIVE != 'Y') {
        $str_ACTIVE = 'N';
    }
}

// сформируем выборку из таблицы групп
$strSql = "
    SELECT
        G.ID as REFERENCE_ID,
        G.NAME as REFERENCE
    FROM
        b_group G
    ";
$groups = $DB->Query($strSql, false, "FILE: " . __FILE__ . "<br>LINE: " . __LINE__);

// сформируем выборку из таблицы пользователи
$strSql = "
    SELECT
        U.ID as REFERENCE_ID,
        CONCAT(U.NAME, ' ', U.LAST_NAME) as REFERENCE
    FROM
        b_user U
    ";
$users = $DB->Query($strSql, false, "FILE: " . __FILE__ . "<br>LINE: " . __LINE__);

// сформируем выборку из таблицы разделы
$strSql = "
    SELECT
        U.ID as REFERENCE_ID,
        U.NAME as REFERENCE
    FROM
        b_iblock_section U
    WHERE
        U.DEPTH_LEVEL=1
    ";
$sections = $DB->Query($strSql, false, "FILE: " . __FILE__ . "<br>LINE: " . __LINE__);

if (intval($str_ITEM) > 0) {
    $dbRes = \CIBlockElement::GetByID($str_ITEM);
    if ($arEl = $dbRes->GetNext()) {
        $str_ITEM = $arEl;
    }
}

//default
if (empty($str_ACTIVE))
    $str_ACTIVE = "Y";

//set title
if ($ID > 0) {
    $APPLICATION->SetTitle(str_replace('#NAME#', $str_NAME, GetMessage('PRICE_MARGIN_TITLE_UPDATE')));
} else {
    $APPLICATION->SetTitle(GetMessage('PRICE_MARGIN_TITLE_ADD'));
}

//context menu
$aMenu = array(array(
    'TEXT' => GetMessage('PRICE_MARGIN_LIST'),
    'ICON' => 'btn_list',
    'LINK' => '/bitrix/admin/price_margin_list.php?lang=' . LANGUAGE_ID . GetFilterParams('filter_', false),
));
if ($ID > 0) {
    $aMenu[] = array(
        'TEXT' => GetMessage('PRICE_MARGIN_DELETE'),
        'ICON' => 'btn_delete',
        'LINK' => 'javascript:if(confirm(\'' . GetMessage('PRICE_MARGIN_DELETE_CONF') . '\')) window.location=\'/bitrix/admin/price_margin_list.php?action=delete&ID=' . $ID . '&lang=' . LANGUAGE_ID . '&' . bitrix_sessid_get() . '#tb\';',
        'WARNING' => 'Y',
    );
}

//show form
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';
//add admin context menu
$context = new CAdminContextMenu($aMenu);
$context->Show();
//show msg
if (is_array($_SESSION["SESS_ADMIN"]["POSTING_EDIT_MESSAGE"])) {
    CAdminMessage::ShowMessage($_SESSION["SESS_ADMIN"]["POSTING_EDIT_MESSAGE"]);
    $_SESSION["SESS_ADMIN"]["POSTING_EDIT_MESSAGE"] = false;
}
if ($message)
    echo $message->Show();
elseif ($price_margin->LAST_ERROR != "")
    CAdminMessage::ShowMessage($price_margin->LAST_ERROR);
CAdminMessage::ShowMessage($errorMessage);
?>
<form method="POST" action="<?= $APPLICATION->GetCurPage() ?>?" name="f_price_margin_edit">
    <?php
    $tabControl->Begin();
    $tabControl->BeginNextTab();
    ?>
    <input type="hidden" name="Update" value="Y">
    <input type="hidden" name="lang" value="<?= LANGUAGE_ID ?>">
    <input type="hidden" name="ID" value="<?= $ID ?>">
    <? echo bitrix_sessid_post(); ?>
    <? if ($ID > 0): ?>
        <tr>
            <td width="40%">ID:</td>
            <td width="60%"><?= $ID ?></td>
        </tr>
    <? endif; ?>
    <tr>
        <td width="40%"><b><?= GetMessage('PRICE_MARGIN_NAME') ?></b><span class="required">*</span>:</td>
        <td width="60%"><input type="text" name="NAME" value="<?= $str_NAME; ?>" size="30"></td>
    </tr>
    <tr>
        <td width="40%"><?= GetMessage('PRICE_MARGIN_SITE') ?>:</td>
        <td width="60%"><?= CSite::SelectBox('SITE_ID', $str_SITE_ID, '') ?></td>
    </tr>
    <tr>
        <td><?= GetMessage('PRICE_MARGIN_ACT') ?>:</td>
        <td><input type="checkbox" name="ACTIVE" value="Y"<?= $str_ACTIVE == 'Y' ? ' checked' : '' ?>></td>
    </tr>
    <tr>
        <td valign="top" width="40%"><?= GetMessage('PRICE_MARGIN_ITEM') ?>:</td>
        <td valign="top" width="60%">
            <input name="ITEM" value="<?= $str_ITEM['ID'] ?>" size="5" type="text">
            <input type="button" class="tablebodybutton" value="..."
                   onClick="window.open('price_margin_search.php?field_name=ITEM&amp;alt_name=product_alt&amp;form_name=f_price_margin_edit', '', 'scrollbars=yes,resizable=yes,width=600,height=500,top='+Math.floor((screen.height - 500)/2-14)+',left='+Math.floor((screen.width - 600)/2-5));">
            <span id="product_alt"><?= $str_ITEM["NAME"] ?></span>
        </td>
    </tr>
    <tr>
        <td><?= GetMessage('PRICE_MARGIN_SECTION') ?>:</td>
        <td><? echo SelectBox("SECTION", $sections, "< выберите раздел >", $str_SECTION); ?></td>
    </tr>
    <tr>
        <td><?= GetMessage('PRICE_MARGIN_USER_ID') ?>:</td>
        <td><? echo SelectBox("USER_ID", $users, "< выберите пользователя >", $str_USER_ID); ?></td>
    </tr>
    <tr>
        <td><?= GetMessage('PRICE_MARGIN_GROUP') ?>:</td>
        <td><? echo SelectBox("GROUP", $groups, "< выберите группу >", $str_GROUP); ?></td>
    </tr>
    <tr>
        <td><?= GetMessage('PRICE_MARGIN_PRICE') ?>:</td>
        <td><input type="text" name="PRICE" value="<?= $str_PRICE ?>" size="10"></td>
    </tr>
    <tr>
        <td><?= GetMessage('PRICE_MARGIN_MARGIN') ?>:</td>
        <td><input type="text" name="MARGIN" value="<?= $str_MARGIN ?>" size="5"></td>
    </tr>
    <?php
    $tabControl->EndTab();
    $tabControl->Buttons(array(
        'disabled' => $RIGHT < 'W',
        'back_url' => '/bitrix/admin/price_margin_list.php?lang=' . LANGUAGE_ID . GetFilterParams('filter_', false),
    ));
    $tabControl->End();
    ?>
</form>
<?= BeginNote() ?>
<span class="required">*</span> <?= GetMessage('REQUIRED_FIELDS') ?>
<?= EndNote() ?>

<?php require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php' ?>
