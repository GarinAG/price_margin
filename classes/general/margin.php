<?php
namespace Interprice;

IncludeModuleLangFile(__FILE__);

class CAllMargin
{
	function err_mess()
	{
		return "<br>Class: CAllMargin<br>File: ".__FILE__;
	}
    
    function CheckFields($ACTION, &$arFields, $ID = 0)
	{
		global $APPLICATION;
		if ((is_set($arFields, "NAME") || $ACTION=="ADD" || $ACTION=="UPDATE") && empty($arFields["NAME"]))
		{
			$APPLICATION->ThrowException(GetMessage("PRICE_MARGIN_EMPTY_NAME"), "EMPTY_NAME");
			return false;
		}
		return true;
	}
    
    function GetByID($ID)
	{
		return CMargin::GetList(Array(), Array("ID"=>$ID));
	}
}
