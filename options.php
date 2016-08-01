<?
$module_id = "price_margin";
$CAT_RIGHT = $APPLICATION->GetGroupRight($module_id);

if ($CAT_RIGHT >= "R") :

    global $MESS;
    include(GetLangFileName($GLOBALS["DOCUMENT_ROOT"] . "/bitrix/modules/main/lang/", "/options.php"));
    include(GetLangFileName($GLOBALS["DOCUMENT_ROOT"] . "/bitrix/modules/price_margin/lang/", "/options.php"));

    include_once($GLOBALS["DOCUMENT_ROOT"] . "/bitrix/modules/price_margin/include.php");

    if ($ex = $APPLICATION->GetException()) {
        require($DOCUMENT_ROOT . "/bitrix/modules/main/include/prolog_admin_after.php");

        $strError = $ex->GetString();
        ShowError($strError);

        require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php");
        die();
    }

    if ($REQUEST_METHOD == "GET" && strlen($RestoreDefaults) > 0 && $CAT_RIGHT == "W" && check_bitrix_sessid()) {
        COption::RemoveOption("price_margin");
        $z = CGroup::GetList($v1 = "id", $v2 = "asc", array("ACTIVE" => "Y", "ADMIN" => "N"));
        while ($zr = $z->Fetch())
            $APPLICATION->DelGroupRight($module_id, array($zr["ID"]));

        LocalRedirect($APPLICATION->GetCurPage() . "?lang=" . LANG . "&mid=" . urlencode($mid));
    }


    $aTabs = array(
        array("DIV" => "edit1", "TAB" => GetMessage("MAIN_TAB_RIGHTS"), "ICON" => "price_margin_settings", "TITLE" => GetMessage("MAIN_TAB_TITLE_RIGHTS")),
    );
    $tabControl = new CAdminTabControl("tabControl", $aTabs);

    $tabControl->Begin();
    ?>
    <form method="POST"
          action="<? echo $APPLICATION->GetCurPage() ?>?mid=<?= htmlspecialchars($mid) ?>&lang=<? echo LANG ?>"
          name="ara">
        <?= bitrix_sessid_post(); ?>
        <?
        $tabControl->BeginNextTab();
        ?>
        <? require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/admin/group_rights.php"); ?>
        <tr>
            <td valign="top"><? echo GetMessage("CO_PAR_DPG_CSV") ?></td>
            <td valign="top"><?
                $strVal = COption::GetOptionString("catalog", "allowed_group_fields", $defCatalogAvailGroupFields);
                $arVal = explode(",", $strVal);
                ?>
            </td>
        </tr>
        <? $tabControl->Buttons(); ?>
        <script language="JavaScript">
            function RestoreDefaults() {
                if (confirm('<?echo AddSlashes(GetMessage("MAIN_HINT_RESTORE_DEFAULTS_WARNING"))?>'))
                    window.location = "<?echo $APPLICATION->GetCurPage()?>?RestoreDefaults=Y&lang=<?echo LANG?>&mid=<?echo urlencode($mid)?>&<?=bitrix_sessid_get()?>";
            }
        </script>
        <input type="submit" <? if ($CAT_RIGHT < "W") echo "disabled" ?> name="Update"
               value="<? echo GetMessage("MAIN_SAVE") ?>">
        <input type="hidden" name="Update" value="Y">
        <input type="reset" name="reset" value="<? echo GetMessage("MAIN_RESET") ?>">
        <input type="button" <? if ($CAT_RIGHT < "W") echo "disabled" ?>
               title="<? echo GetMessage("MAIN_HINT_RESTORE_DEFAULTS") ?>" OnClick="RestoreDefaults();"
               value="<? echo GetMessage("MAIN_RESTORE_DEFAULTS") ?>">
        <? $tabControl->End(); ?>
    </form>
<? endif; ?>
