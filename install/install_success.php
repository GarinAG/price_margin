<?php
if (!check_bitrix_sessid()) return;
IncludeModuleLangFile(dirname(__FILE__).'/index.php');
echo CAdminMessage::ShowNote(GetMessage('MOD_INST_OK'));
?>
<form action="<?php echo $APPLICATION->GetCurPage() ?>">
    <input type="hidden" name="lang" value="<?php echo LANG ?>">
    <input type="submit" name="" value="<?php echo GetMessage('MOD_BACK') ?>">
</form>
