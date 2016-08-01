<?php
/**
 * Цена товара с учетом наценки
 */

namespace Interprice;

use Cetera\Exception\Exception;
use CPHPCache;
use CPrice;
use CUser;

/**
 * Class price
 * Package /Interprice/Price
 */
class Price
{
    /**
     * @var Integer
     */
    private $ID;
    /**
     * @var string
     */
    private $CUR;

    /**
     * @var array
     */
    private $PRICES = [];

    /**
     * @var int
     */
    private $HBLOCK_ID = 5;

    /**
     * @var
     */
    private $MARGIN;

    /**
     * @param Integer $ID
     * @param string $CUR
     * @throws Exception
     */
    public function __construct($ID, $CUR = "RUB")
    {
        if (intval($ID) > 0) {
            $this->ID = intval($ID);
            $this->CUR = $CUR;
        } else {
            $this->throwException("Invalid productID");
        }
    }

    /**
     * @param $args
     * @throws Exception
     */
    protected function throwException($args)
    {
        throw new Exception($args);
    }

    /**
     * @param $ID
     * @return array
     */
    public static function getCurItemPrice($ID)
    {
        $item = new self($ID);
        return $item->getPrice();
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getPrice()
    {
        if (empty($this->ID)) {
            $this->throwException("productID is required");
        }

        //Подключаем модули
        if (\CModule::IncludeModule("sale") && \CModule::IncludeModule("catalog")) {
            global $USER;

            //Очищаем массив с ценами
            $this->clearPrices();

            //Поиск цен
            $dbPrice = CPrice::GetList(
                array("QUANTITY_FROM" => "ASC", "QUANTITY_TO" => "ASC", "SORT" => "ASC"),
                array("PRODUCT_ID" => $this->ID),
                false,
                false,
                array("ID", "CATALOG_GROUP_ID", "PRICE", "CURRENCY", "QUANTITY_FROM", "QUANTITY_TO", "PRODUCT_ID")
            );

            $this->getMargin();

            if (!empty($this->MARGIN)) {
                while ($arPrice = $dbPrice->Fetch()) {
                    if (!empty($this->MARGIN["PRICE"])) {
                        $arPrice["PRICE"] = $this->MARGIN["PRICE"];
                    } elseif (!empty($this->MARGIN["MARGIN"])) {
                        $arPrice["PRICE"] = floatval($this->MARGIN["MARGIN"]) * floatval($arPrice["PRICE"]);
                    } else {
                        $arPrice["PRICE"] = $arPrice["PRICE"];
                    }
                    $arPrice["PRINT_PRICE"] = CurrencyFormat($arPrice["PRICE"], $this->CUR);
                    $this->PRICES[$arPrice["CATALOG_GROUP_ID"]] = $arPrice;
                }
            }
        } else {
            $this->throwException("Modules(sale and catalog) are required");
        }
        return $this->PRICES;
    }

    public static function getPriceSimple($ID, &$price){
        $item = new self($ID);
        $item->changePriceSimple($price);
    }

    public function changePriceSimple(&$price){
        $this->getMargin();
        if (!empty($this->MARGIN["PRICE"])) {
            $price = $this->MARGIN["PRICE"];
        } elseif (!empty($this->MARGIN["MARGIN"])) {
            $price = floatval($this->MARGIN["MARGIN"]) * floatval($price);
        }
    }

    /**
     *
     */
    private function clearPrices()
    {
        $this->PRICES = [];
    }

    /**
     * @return array
     */
    private function getMargin()
    {
        global $USER;

        //Используем кеш для наценок
        $obCACHE = new CPHPCache;
        $iCACHE_TIME = 3600;
        $strCACHE_ID = "itemMargins";
        $strCACHE_PATH = "/item.margins";
        $elMargin = [];
        if (isset($_REQUEST['clear_cache']) && $_REQUEST['clear_cache'] == "Y") {
            $obCACHE->Clean($strCACHE_ID, $strCACHE_PATH);
        }
        if ($iCACHE_TIME > 0 && $obCACHE->InitCache($iCACHE_TIME, $strCACHE_ID, $strCACHE_PATH)) {
            $arrRESULT = $obCACHE->GetVars();
            $elMargin = $arrRESULT['elMargin'];
        }
        if (!is_array($elMargin) || !count($elMargin)) {
            $filter = [
                "ACTIVE" => "Y",
                "SITE_ID" => SITE_ID
            ];
            $list = CMargin::GetList(array("ID" => "ASC"), $filter);
            $elMargin = [];
            while ($el = $list->fetch()) {
                $elMargin[] = $el;
            }

            $obCACHE->StartDataCache($iCACHE_TIME, $strCACHE_ID, $strCACHE_PATH);
            $obCACHE->EndDataCache(array("elMargin" => $elMargin));
        }

        //Используем кеш для разделов
        $obCACHE = new CPHPCache;
        $iCACHE_TIME = 360000;
        $strCACHE_ID = "itemSections" . $this->ID;
        $strCACHE_PATH = "/item.sections" . $this->ID;
        $maxDepth = 0;
        $arSections = [];
        if (isset($_REQUEST['clear_cache']) && $_REQUEST['clear_cache'] == "Y") {
            $obCACHE->Clean($strCACHE_ID, $strCACHE_PATH);
        }
        if ($iCACHE_TIME > 0 && $obCACHE->InitCache($iCACHE_TIME, $strCACHE_ID, $strCACHE_PATH)) {
            $arrRESULT = $obCACHE->GetVars();
            $arSections = $arrRESULT['arSections'];
        }
        if (!is_array($arSections) || !count($arSections)) {
            $dbRes = \CIBlockElement::GetList(Array(""), Array("ID" => $this->ID), false, false, Array("ID", "IBLOCK_SECTION_ID"));
            if ($arEl = $dbRes->Fetch()) {
                if (!empty($arEl["IBLOCK_SECTION_ID"])) {
                    $nav = \CIBlockSection::GetNavChain(false, $arEl["IBLOCK_SECTION_ID"]);
                    while ($n = $nav->Fetch()) {
                        if (intval($n["ID"]) === intval($arEl["IBLOCK_SECTION_ID"]))
                            $maxDepth = intval($n["DEPTH_LEVEL"]);
                        $arSections[$n["ID"]] = intval($n["DEPTH_LEVEL"]);
                    }
                }
            }

            $obCACHE->StartDataCache($iCACHE_TIME, $strCACHE_ID, $strCACHE_PATH);
            $obCACHE->EndDataCache(array("arSections" => $arSections));
        }

        $userId = $USER->GetID();
        $arGroups = [];
        if (intval($userId) > 0) {
            $arGroups = CUser::GetUserGroup($userId);
        }

        $this->MARGIN = [];
        foreach ($elMargin as $margin) {
            if (!empty($margin["ITEM"])) {
                if (intval($margin["ITEM"]) === $this->ID) {
                    if (intval($userId) > 0) {
                        if (!empty($margin["USER_ID"]) && $margin["USER_ID"] === $userId) {
                            $relevance = 1;
                            if (!empty($margin["PRICE"])) {
                                $this->MARGIN = [
                                    "RELEVANCE" => $relevance,
                                    "PRICE" => $margin["PRICE"]
                                ];
                            } elseif (!empty($margin["MARGIN"])) {
                                $this->MARGIN = [
                                    "RELEVANCE" => $relevance,
                                    "MARGIN" => $margin["MARGIN"]
                                ];
                            }
                        } elseif (!empty($margin["GROUP"]) && in_array($margin["GROUP"], $arGroups)) {
                            $relevance = 2;
                            if (count($this->MARGIN) && $this->MARGIN["RELEVANCE"] < $relevance)
                                continue;

                            if (!empty($margin["PRICE"])) {
                                $this->MARGIN = [
                                    "RELEVANCE" => $relevance,
                                    "PRICE" => $margin["PRICE"]
                                ];
                            } elseif (!empty($margin["MARGIN"])) {
                                $this->MARGIN = [
                                    "RELEVANCE" => $relevance,
                                    "MARGIN" => $margin["MARGIN"]
                                ];
                            }
                        } else {
                            $relevance = 3;
                            if (count($this->MARGIN) && $this->MARGIN["RELEVANCE"] < $relevance)
                                continue;

                            if (!empty($margin["PRICE"])) {
                                $this->MARGIN = [
                                    "RELEVANCE" => $relevance,
                                    "PRICE" => $margin["PRICE"]
                                ];
                            } elseif (!empty($margin["MARGIN"])) {
                                $this->MARGIN = [
                                    "RELEVANCE" => $relevance,
                                    "MARGIN" => $margin["MARGIN"]
                                ];
                            }
                        }
                    } elseif (empty($margin["USER_ID"]) && empty($margin["GROUP"])) {
                        $relevance = 3;
                        if (count($this->MARGIN) && $this->MARGIN["RELEVANCE"] < $relevance)
                            continue;

                        if (!empty($margin["PRICE"])) {
                            $this->MARGIN = [
                                "RELEVANCE" => $relevance,
                                "PRICE" => $margin["PRICE"]
                            ];
                        } elseif (!empty($margin["MARGIN"])) {
                            $this->MARGIN = [
                                "RELEVANCE" => $relevance,
                                "MARGIN" => $margin["MARGIN"]
                            ];
                        }
                    }
                }
            } elseif (!empty($margin["SECTION"])) {
                if (count($arSections) && array_key_exists($margin["SECTION"], $arSections) && $arSections[$margin["SECTION"]] <= $maxDepth && $arSections[$margin["SECTION"]] >= intval($this->MARGIN["DEPTH"])) {
                    if (intval($userId) > 0) {
                        if (!empty($margin["USER_ID"]) && $margin["USER_ID"] === $userId) {
                            $relevance = 4;
                            if (count($this->MARGIN) && $this->MARGIN["RELEVANCE"] < $relevance)
                                continue;

                            if (!empty($margin["MARGIN"])) {
                                $this->MARGIN = [
                                    "RELEVANCE" => $relevance,
                                    "MARGIN" => $margin["MARGIN"],
                                    "DEPTH" => $arSections[$margin["SECTION"]]
                                ];
                            }
                        } elseif (!empty($margin["GROUP"]) && in_array($margin["GROUP"], $arGroups)) {
                            $relevance = 5;
                            if (count($this->MARGIN) && $this->MARGIN["RELEVANCE"] < $relevance)
                                continue;

                            if (!empty($margin["MARGIN"])) {
                                $this->MARGIN = [
                                    "RELEVANCE" => $relevance,
                                    "MARGIN" => $margin["MARGIN"],
                                    "DEPTH" => $arSections[$margin["SECTION"]]
                                ];
                            }
                        } else {
                            $relevance = 7;
                            if (count($this->MARGIN) && $this->MARGIN["RELEVANCE"] < $relevance)
                                continue;

                            if (!empty($margin["MARGIN"])) {
                                $this->MARGIN = [
                                    "RELEVANCE" => $relevance,
                                    "MARGIN" => $margin["MARGIN"],
                                    "DEPTH" => $arSections[$margin["SECTION"]]
                                ];
                            }
                        }
                    } elseif (empty($margin["USER_ID"]) && empty($margin["GROUP"])) {
                        $relevance = 7;
                        if (count($this->MARGIN) && $this->MARGIN["RELEVANCE"] < $relevance)
                            continue;

                        if (!empty($margin["MARGIN"])) {
                            $this->MARGIN = [
                                "RELEVANCE" => $relevance,
                                "MARGIN" => $margin["MARGIN"],
                                "DEPTH" => $arSections[$margin["SECTION"]]
                            ];
                        }
                    }
                }
            } else {
                if (intval($userId) > 0) {
                    if (!empty($margin["USER_ID"]) && $margin["USER_ID"] === $userId) {
                        $relevance = 8;
                        if (count($this->MARGIN) && $this->MARGIN["RELEVANCE"] < $relevance)
                            continue;

                        if (!empty($margin["MARGIN"])) {
                            $this->MARGIN = [
                                "RELEVANCE" => $relevance,
                                "MARGIN" => $margin["MARGIN"]
                            ];
                        }
                    } elseif (!empty($margin["GROUP"]) && in_array($margin["GROUP"], $arGroups)) {
                        $relevance = 9;
                        if (count($this->MARGIN) && $this->MARGIN["RELEVANCE"] < $relevance)
                            continue;

                        if (!empty($margin["MARGIN"])) {
                            $this->MARGIN = [
                                "RELEVANCE" => $relevance,
                                "MARGIN" => $margin["MARGIN"]
                            ];
                        }
                    } else {
                        $relevance = 10;
                        if (count($this->MARGIN) && $this->MARGIN["RELEVANCE"] < $relevance)
                            continue;

                        if (!empty($margin["MARGIN"])) {
                            $this->MARGIN = [
                                "RELEVANCE" => $relevance,
                                "MARGIN" => $margin["MARGIN"]
                            ];
                        }
                    }
                } elseif (empty($margin["USER_ID"]) && empty($margin["GROUP"])) {
                    $relevance = 10;
                    if (count($this->MARGIN) && $this->MARGIN["RELEVANCE"] < $relevance)
                        continue;

                    if (!empty($margin["MARGIN"])) {
                        $this->MARGIN = [
                            "RELEVANCE" => $relevance,
                            "MARGIN" => $margin["MARGIN"]
                        ];
                    }
                }
            }
        }

        return $this->MARGIN;
    }

    /**
     * @param $ID
     * @param $prices
     * @param string $CUR
     * @return mixed
     */
    public static function changeCurPrice($ID, $prices, $CUR = "RUB")
    {
        $item = new self($ID, $CUR);
        return $item->changePrice($prices);
    }

    /**
     * @param $prices
     * @return mixed
     */
    private function changePrice($prices)
    {
        $this->getMargin();

        if (count($this->MARGIN)) {
            foreach ($prices as &$price) {
                if ($price["MIN_PRICE"] !== "Y")
                    continue;

                //НДС
                $vatRate = 0;
                if (!empty($price["VATRATE_VALUE"])) {
                    $vatRate = floatval($price["VATRATE_VALUE"]) / floatval($price["VALUE"]);
                }

                //Скидка %
                $discount = 1;
                if (!empty($price["DISCOUNT_DIFF_PERCENT"])) {
                    $discount = (100 - $price["DISCOUNT_DIFF_PERCENT"]) / 100;
                }

                if (!empty($this->MARGIN["PRICE"]))
                    $price["VALUE"] = floatval($this->MARGIN["PRICE"]);
                elseif (!empty($this->MARGIN["MARGIN"]))
                    $price["VALUE"] = floatval($price["VALUE"]) * floatval($this->MARGIN["MARGIN"]);

                $noVat = $price["VALUE"] - $price["VALUE"] * $vatRate;

                $newPrice = [
                    "VALUE_NOVAT" => $noVat,
                    "PRINT_VALUE_NOVAT" => CurrencyFormat($noVat, $this->CUR),
                    "VALUE_VAT" => $price["VALUE"],
                    "PRINT_VALUE_VAT" => CurrencyFormat($price["VALUE"], $this->CUR),
                    "VATRATE_VALUE" => $price["VALUE"] * $vatRate,
                    "PRINT_VATRATE_VALUE" => CurrencyFormat($price["VALUE"] * $vatRate, $this->CUR),
                    "DISCOUNT_VALUE_NOVAT" => $noVat * $discount,
                    "PRINT_DISCOUNT_VALUE_NOVAT" => CurrencyFormat($noVat * $discount, $this->CUR),
                    "DISCOUNT_VALUE_VAT" => $price["VALUE"] * $discount,
                    "PRINT_DISCOUNT_VALUE_VAT" => CurrencyFormat($price["VALUE"] * $discount, $this->CUR),
                    "DISCOUNT_VATRATE_VALUE" => $price["VALUE"] * $vatRate * $discount,
                    "PRINT_DISCOUNT_VATRATE_VALUE" => CurrencyFormat($price["VALUE"] * $vatRate * $discount, $this->CUR),
                    "CURRENCY" => $price["CURRENCY"],
                    "PRICE_ID" => $price["PRICE_ID"],
                    "ID" => $price["ID"],
                    "CAN_ACCESS" => $price["CAN_ACCESS"],
                    "CAN_BUY" => $price["CAN_BUY"],
                    "MIN_PRICE" => $price["MIN_PRICE"],
                    "VALUE" => $price["VALUE"],
                    "PRINT_VALUE" => CurrencyFormat($price["VALUE"], $this->CUR),
                    "DISCOUNT_VALUE" => $price["VALUE"] * $discount,
                    "PRINT_DISCOUNT_VALUE" => CurrencyFormat($price["VALUE"] * $discount, $this->CUR),
                    "DISCOUNT_DIFF" => $price["VALUE"] - ($price["VALUE"] * $discount),
                    "DISCOUNT_DIFF_PERCENT" => $price["DISCOUNT_DIFF_PERCENT"],
                    "PRINT_DISCOUNT_DIFF" => CurrencyFormat($price["VALUE"] - ($price["VALUE"] * $discount), $this->CUR)
                ];

                $price = $newPrice;
            }
        }

        return $prices;
    }

    /**
     * @param $ID
     * @param $prices
     * @param string $CUR
     * @return mixed
     */
    public static function getCurOptimalPrice($ID, $prices, $CUR = "RUB")
    {
        $item = new self($ID, $CUR);
        return $item->getOptimalPrice($prices);
    }

    /**
     * @param $prices
     * @return mixed
     */
    private function getOptimalPrice($prices)
    {
        $this->getMargin();

        if (count($this->MARGIN)) {
            $newPrice = $prices;
            $newPriceValue = $newPrice["PRICE"]["PRICE"];
            if (!empty($this->MARGIN["PRICE"]))
                $newPriceValue = $this->MARGIN["PRICE"];
            elseif (!empty($this->MARGIN["MARGIN"]))
                $newPriceValue = floatval($newPriceValue) * floatval($this->MARGIN["MARGIN"]);

            $newPriceValue = floatval($newPriceValue);

            $newPrice["PRICE"]["PRICE"] = $newPriceValue;

            $newPrice["RESULT_PRICE"]["BASE_PRICE"] = $newPriceValue;
            if (!empty($newPrice["RESULT_PRICE"]["PERCENT"])) {
                $discount = $newPriceValue * (floatval($newPrice["RESULT_PRICE"]["PERCENT"]) / 100);
                $newPrice["RESULT_PRICE"]["DISCOUNT"] = $discount;
                $newPrice["RESULT_PRICE"]["DISCOUNT_PRICE"] = $newPriceValue - $discount;
            } else {
                $newPrice["RESULT_PRICE"]["DISCOUNT_PRICE"] = $newPriceValue;
            }
            $newPrice["DISCOUNT_PRICE"] = $newPrice["RESULT_PRICE"]["DISCOUNT_PRICE"];

            $prices = $newPrice;
        }

        return $prices;
    }

    /**
     * @return int
     */
    public function getHBLOCKID()
    {
        return $this->HBLOCK_ID;
    }

    /**
     * @param int $HBLOCK_ID
     */
    public function setHBLOCKID($HBLOCK_ID)
    {
        if (intval($HBLOCK_ID) > 0) {
            $this->HBLOCK_ID = $HBLOCK_ID;
        } else {
            $this->throwException("Invalid HBLOCK_ID value");
        }
    }

    /**
     * @return Integer
     */
    public function getID()
    {
        return $this->ID;
    }

    /**
     * @param Integer $ID
     */
    public function setID($ID)
    {
        $this->ID = $ID;
    }

    /**
     * @return string
     */
    public function getCUR()
    {
        return $this->CUR;
    }

    /**
     * @param string $CUR
     */
    public function setCUR($CUR)
    {
        $this->CUR = $CUR;
    }
}
