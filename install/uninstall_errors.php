<?php
if (!check_bitrix_sessid()) return;
IncludeModuleLangFile(dirname(__FILE__).'/index.php');
echo CAdminMessage::ShowMessage(array(
    'TYPE' => 'ERROR',
    'MESSAGE' => GetMessage('MOD_UNINST_ERR'),
    'DETAILS' => implode('<br>', $errors),
    'HTML' => true,
));
?>
<form action="<?php echo $APPLICATION->GetCurPage() ?>">
    <input type="hidden" name="lang" value="<?php echo LANG ?>">
    <input type="submit" name="" value="<?php echo GetMessage('MOD_BACK') ?>">
</form>
