<?php

namespace Interprice;

use Bitrix\Main\Data\StaticHtmlCache;

IncludeModuleLangFile(__FILE__);

class CMargin extends CAllMargin
{
    static protected $sTableName = 'b_interprice_margin';
    static protected $arFields = array(
        'ID',
        'NAME',
        'SITE_ID',
        'ACTIVE',
        'ITEM',
        'SECTION',
        'PRICE',
        'MARGIN',
        'USER_ID',
        'GROUP',
        'USER_MODIFIER',
    );

    var $LAST_ERROR = '';

    static public function Add($arFields)
    {
        global $DB;

        if (!CMargin::CheckFields("ADD", $arFields, 0))
            return false;

        $arInsert = $DB->PrepareInsert(self::$sTableName, $arFields);
        if (!empty($arInsert)) {
            $sSql = 'INSERT INTO `' . self::$sTableName . '` (' . $arInsert[0] . ') VALUES(' . $arInsert[1] . ')';
            $DB->Query($sSql, false, 'File: ' . __FILE__ . '<br />Line: ' . __LINE__);
            $ID = $DB->LastID();
            return $ID;
        }
        self::clearCache();
        return false;
    }

    private function clearCache()
    {
        BXClearCache(true);
        $GLOBALS["CACHE_MANAGER"]->CleanAll();
        $GLOBALS["stackCacheManager"]->CleanAll();
        $staticHtmlCache = StaticHtmlCache::getInstance();
        $staticHtmlCache->deleteAll();
    }

    static public function Update($ID, $arFields)
    {
        global $DB;

        $ID = intval($ID);
        if (!CMargin::CheckFields("UPDATE", $arFields, $ID))
            return false;

        if (!empty($ID)) {
            $sUpdate = $DB->PrepareUpdate(self::$sTableName, $arFields);
            if (!empty($sUpdate)) {
                $sSql = 'UPDATE `' . self::$sTableName . '` SET ' . $sUpdate . ' WHERE `ID`="' . $ID . '"';
                $DB->Query($sSql, false, 'File: ' . __FILE__ . '<br />Line: ' . __LINE__);
                return $ID;
            }
        }
        self::clearCache();
        return false;
    }

    static public function Delete($ID)
    {
        global $DB, $stackCacheManager;
        $ID = intval($ID);
        $stackCacheManager->Clear('price_margin');
        $DB->Query('DELETE FROM `' . self::$sTableName . '` WHERE `ID`="' . $ID . '"');
        self::clearCache();
        return true;
    }

    static public function GetList($arSort = array(), $arFilter = array())
    {
        global $DB;

        $arWhere = array();
        $arOrderBy = array();
        $sLimit = '';

        if (!empty($arFilter)) {
            foreach ($arFilter as $key => $value) {
                $key = addslashes($key);
                if (!empty($value)) {
                    switch ($key) {
                        case 'LIMIT':
                            if (is_array($value)) {
                                $sLimit = intval($value[0]);
                                if (!empty($value[1])) {
                                    $sLimit .= ', ' . intval($value[1]);
                                }
                            } else {
                                $sLimit = intval($value);
                            }
                            break;
                        default:
                            if (is_array($value)) {
                                $in = array();
                                foreach ($value as $v) {
                                    $in[] = $DB->ForSql($v);
                                }
                                $arWhere[] = '`' . $key . '` IN ("' . implode('", "', $in) . '")';
                            } else {
                                $arWhere[] = '`' . $key . '` = "' . $DB->ForSql($value) . '"';
                            }
                    }
                }
            }
        }

        if (!empty($arSort)) {
            foreach ($arSort as $by => $order) {
                $by = strtoupper($by);
                $order = strtoupper($order);
                if (in_array($by, self::$arFields) && in_array($order, array('ASC', 'DESC'))) {
                    $arOrderBy[] = '`' . $DB->ForSql($by) . '` ' . $order;
                }
            }
        }

        $arFields = self::$arFields;
        $arSelect = array();
        foreach ($arFields as $value) {
            $arSelect[] = '`B`.`' . $value . '`';
        }
        $sSQL = 'SELECT ' . implode(', ', $arSelect) . ' FROM `' . self::$sTableName . '` AS `B`';
        if (!empty($arWhere)) {
            $sSQL .= ' WHERE ' . implode(' AND ', $arWhere);
        }
        if (!empty($arOrderBy)) {
            $sSQL .= ' ORDER BY ' . implode(', ', $arOrderBy);
        }
        if (!empty($sLimit)) {
            $sSQL .= ' LIMIT ' . $sLimit;
        }

        return $DB->Query($sSQL, false, 'FILE: ' . __FILE__ . '<br />LINE: ' . __LINE__);
    }

    static public function getTableName()
    {
        return self::$sTableName;
    }

    static public function getFields()
    {
        return self::$arFields;
    }
}
