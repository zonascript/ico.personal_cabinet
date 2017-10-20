<?php
namespace Xcart;

use Mindy\QueryBuilder\Expression;
use Mindy\QueryBuilder\Q\QAndNot;
use Mindy\QueryBuilder\QueryBuilder;
use Xcart\App\Main\Xcart;

class Product extends Data
{
    const ADMIN_PRODUCT_MODIFY_URL = '/admin/product_modify.php?productid=%d&sf=%d';

    const PRODUCT_STATUS_NOT_VERIFY = 0;
    const PRODUCT_STATUS_PROBLEM_NOT_FIXED = 1;
    const PRODUCT_STATUS_PROBLEM_FIXED = 2;
    const PRODUCT_STATUS_VERIFY = 3;

    const RETAIL_TRUST_SKU_PREFIX = 'RT*';


    private $oManufacturer;
    private $oStoreFront;
    private $aProductVerificationHistoryLast = [];

    private $aImagesD = null;
    private $aImagesP = null;
    private $aImagesT = null;

    private $aThumbNails = null;

    private $aPricing = null;

    private $iAmazonQuantity = null;
    private $fAmazonPrice = null;

    /**
     * @var ProductQuestion[]
     */
    private $aProductQuestions = null;

    private $iAmazonFbaAvail = null;
    private $iAmazonFbaStockTotal = null;
    private $iAmazonFbaStockReservedTransfers = null;

    private $fExtraMarginValue = null;
    private $fPrice = null;

    private $bSupplierFeed = null;

    /**
     * @var ProductCategories[]
     */
    private $aProductCategories = null;

    /**
     * @var Category
     */
    private $oMainCategory = null;

    public function __construct($iId = null)
    {
        $this->sPrimaryTable = 'products';
        $this->aPrimaryKeys = ['productid'];

        parent::__construct($iId);
    }

    public function getManfacturerClass($iManufacurerId = null)
    {
        if (!is_null($iManufacurerId))
            return new Manufacturer(['manufacturerid' => $iManufacurerId]);
        else {
            if (is_null($this->oManufacturer)) {
                $this->oManufacturer = new Manufacturer(['manufacturerid' => $this->aPrimaryTableValue['manufacturerid']]);
            }
            return $this->oManufacturer;
        }
    }

    public function getManufacturerId()
    {
        return $this->getField('manufacturerid');
    }

    public function getMPN()
    {
        $sMPN = '';
        if (strpos($this->getSKU(), $this->getManfacturerClass()->getField('code')) == 0)
            $sMPN = preg_replace("/^(" . $this->getManfacturerClass()->getField('code') . "-)/i", "", $this->getSKU());
        return $sMPN;
    }

    public function getSKU()
    {
        return $this->getField('productcode');
    }


    public function getStoreFront()
    {
        if (is_null($this->oStoreFront)) {
            $this->oStoreFront = StoreFront::getStoreFrontByProductId($this->getProductId());
        }
        return $this->oStoreFront;
    }

    public function setProductManufacturer($aManufacturerInfo)
    {
        if (!empty($aManufacturerInfo) && is_array($aManufacturerInfo)) {
            $this->oManufacturer = (new Manufacturer())->fill($aManufacturerInfo);
        }
        return $this;
    }

    public function getAdminUrl()
    {
        return sprintf(self::ADMIN_PRODUCT_MODIFY_URL, $this->getProductId(), $this->getStoreFront()->getField('storefrontid'));
    }

    public function getURL($http = '//')
    {
        return $http . $this->getStoreFront()->getDomain() . $this->getRelativeURL();
    }

    public function getRelativeURL()
    {
        return '/' . func_clean_url_get('P', $this->getProductId(), false);
    }

    public function getHTMLShot($iOrderID)
    {
        return HTMLShot::model()->find(SQLBuilder::getInstance()->addCondition('product_id = ' . $this->getProductId())->addCondition('order_id = ' . $iOrderID));
    }

    public function createHTMLShot($iOrderID)
    {
        $aManufacturerProductVerifySettings = $this->getManfacturerClass()->getFields(['products_always_verify', 'days_before_verify']);
        if ($aManufacturerProductVerifySettings['products_always_verify'] == 'Y') {
            $this->changeVerificationStatus(self::PRODUCT_STATUS_VERIFY, '', true, [$iOrderID]);
        } elseif (intval($aManufacturerProductVerifySettings['days_before_verify']) > 0 && $this->getProductLastVerifyDate()) {
            $currentDate = new \DateTime("now");
            $iDaysInterval = $currentDate->diff($this->getProductLastVerifyDate())->days;
            if ($iDaysInterval <= $aManufacturerProductVerifySettings['days_before_verify']) {
                $this->changeVerificationStatus(self::PRODUCT_STATUS_VERIFY, '', true, [$iOrderID]);
            }
        } else {
            $this->changeVerificationStatus(self::PRODUCT_STATUS_NOT_VERIFY, '', true, [$iOrderID]);
            HTMLShot::model()->createHTMLShot($this, $iOrderID);
        }
    }

    public function getProductURLOnDistributorWebSite()
    {
        $sWebsiteProduct = $this->getManfacturerClass()->getField('d_website_search_for_sku_url');
        if (empty($sWebsiteProduct))
            $sWebsiteProduct = $this->getManfacturerClass()->getField('url');
        return str_replace(['{{mpn}}', '{{supplier_internal_id}}'], [$this->getMPN(), $this->getField('supplier_internal_id')], $sWebsiteProduct);
    }

    public function getProductLastVerifyDate()
    {
        $iDate = (int)$this->getField('last_verify_date');
        if (!empty($iDate)) {
            $oDatetime = new \DateTime();
            $oDatetime->setTimestamp($iDate);
            return $oDatetime;
        }
        return false;
    }

    public static function getProductVerificationStatuses()
    {
        return func_query("SELECT * FROM " . self::$sql_tbl['product_verification_statuses'] . " ORDER BY orderby ASC");
    }

    public function getProductVerificationHistoryLastNote()
    {
        $sResult = '';
        $this->aProductVerificationHistoryLast = func_query_first("SELECT * FROM " . self::$sql_tbl['product_verification_history'] . " WHERE productid = " . $this->getProductId() . " ORDER BY timestamp DESC");
        if (!empty($this->aProductVerificationHistoryLast)) {
            $sResult = stripslashes($this->aProductVerificationHistoryLast['verification_note']);
        }
        return $sResult;
    }

    public function changeVerificationStatus($iStatusId, $sNote = '', $add2History = true, $aOrders)
    {
        global $login;
        $bResult['result'] = false;
        if ($this->getField('verification_statusid') != $iStatusId) {
            $aUpdateParams = ['verification_statusid' => $iStatusId];
            $oDatetime = new \DateTime();
            if ($iStatusId == self::PRODUCT_STATUS_VERIFY) {
                $aUpdateParams['last_verify_date'] = $oDatetime->getTimestamp();
            }
            $res = func_array2update($this->sPrimaryTable, $aUpdateParams, 'productid = ' . $this->getProductId());

            if ($res) {
                if ($add2History) {
                    $aInsertArray = ['productid' => $this->getProductId(),
                        'verification_note' => addslashes($sNote),
                        'timestamp' => $oDatetime->getTimestamp(),
                        'username' => $login,
                        'oldstatusid' => $this->getField('verification_statusid'),
                        'newstatusid' => $iStatusId];
                    func_array2insert('product_verification_history', $aInsertArray);
                }
                $bResult['result'] = true;

                $this->setField('last_verify_date', $aUpdateParams['last_verify_date']);

                if (!empty($aOrders) && ($iStatusId == self::PRODUCT_STATUS_PROBLEM_NOT_FIXED || $iStatusId == self::PRODUCT_STATUS_PROBLEM_FIXED)) {
                    foreach ($aOrders as $iOrderId) {
                        $oVerificationStatusNew = new ProductVerificationStatus($iStatusId);
                        $oVerificationStatusOld = new ProductVerificationStatus($this->getField('verification_statusid'));
                        $sLogMessage = "<b>" . $this->getField('productcode') . "</b> product verification status: " . $oVerificationStatusOld->getField('name') . " -> " . $oVerificationStatusNew->getField('name') . "\n";
                        if (!empty($sNote)) $sLogMessage .= 'Problem/fix description: ' . $sNote;
                        func_log_order($iOrderId, 'X', nl2br($sLogMessage));
                    }
                }
                $this->setField('verification_statusid', $aUpdateParams['verification_statusid']);

            } else $bResult['error'] = 'Status not updated';
        } else {
            $bResult['error'] = 'Status not changed. New status = Old status';
        }
        return $bResult;

    }

    public function getProductId()
    {
        return $this->getField('productid');
    }


    /**
     * @param string $type
     * @return ProductImage[]
     */
    public function getImages($type)
    {
        $sImagesVar = "aImages" . $type;
        if (is_null($this->$sImagesVar)) {
            $this->$sImagesVar = (new ProductImage($type))->findAll(SQLBuilder::getInstance()->addCondition('id = ' . $this->getProductId())->addOrderBy('orderby ASC'));
        }
        return $this->$sImagesVar;
    }

    private function fetchPricing()
    {
        if (is_null($this->aPricing)) {
            $aPricing = Xcart::app()->db->getConnection()->fetchAll("SELECT * FROM " . self::$sql_tbl['pricing'] . " WHERE productid = " . $this->getProductId() . " ORDER BY quantity ASC");
            if (!empty($aPricing))
                foreach ($aPricing as $aPrice) {
                    $oProductPricing = new Pricing();
                    $oProductPricing->fill($aPrice);
                    $this->aPricing[] = $oProductPricing;
                }
        }
    }

    public function getPricing()
    {
        $this->fetchPricing();
        return $this->aPricing;
    }

    public function getProductTableValues()
    {
        return $this->aPrimaryTableValue;
    }

    public function isParent()
    {
        return ($this->getField('clone_parent_productid') == 0);
    }

    public function isForSale()
    {
        return ($this->getField('forsale') == 'Y' ? true : false);
    }

    public function isProductOutOfStock()
    {
        if (!$this->isForSale()) {
            return true;
        }

        if ($this->cost_to_us <= 0) {
            return true;
        }

        if ($this->avail <= 0) {
            return true;
        }

        if ($this->avail < $this->min_amount) {
            return true;
        }

        if ($this->cost_to_us >= $this->getPrice()) {
            return true;
        }

        if ($this->eta_date_mm_dd_yyyy && time() < $this->eta_date_mm_dd_yyyy) {
            return true;
        }

        if (floatval($this->shipping_freight) == 0 && strpos($this->productcode, "ART-") === false) {
            return true;
        }

        return false;
    }

    public function getMapPrice()
    {
        return floatval($this->getField('new_map_price'));
    }

    public function getProductCostToUs(\DateTime $oDate = null)
    {
        $fResult = floatval($this->getField('cost_to_us'));
        if (!is_null($oDate) && $oDate instanceof \DateTime) {
            $sSQL = <<<SQL
SELECT value
FROM xcart_history_data
WHERE     resource_type = 'product'
      AND resourceid = :product_id
      AND changedate < :date
      ORDER BY changedate DESC
LIMIT 1
SQL;
            $aResult = Connection::getInstance()->executeQuery($sSQL, ['product_id' => $this->getProductId(), 'date' => $oDate->format('Y-m-d')])->fetch();
            if (!empty($aResult)) {
                $fResult = floatval($aResult['value']);
            }
        }
        return $fResult;
    }

    public function getPrice($forQuantity = 1)
    {
        if (is_null($this->fPrice)) {
            $this->fPrice = 0;
            if (is_null($this->aPricing)) {
                $this->getPricing();
            }
            if (!empty($this->aPricing)) {
                foreach ($this->aPricing as $oPrice) {
                    if ($forQuantity >= floatval($oPrice->getQuantity())) {
                        $this->fPrice = floatval($oPrice->getPrice());
                        break;
                    }

                }
            }
            $fMapPrice = $this->getMapPrice();
            $this->fPrice = max($this->fPrice, $fMapPrice);
        }

        return $this->fPrice;
    }

    public function setPrice($fPrice)
    {
        $this->fPrice = $fPrice;
    }

    public function getFrontendPrice($forQuantity = 1)
    {
        $fPrice = $this->getPrice($forQuantity);

        if ($this->isSupplierFeedsEnabled() && $this->isProductOutOfStock() && $fPrice > $this->cost_to_us) {
            $fPrice = round($this->cost_to_us + ($fPrice - $this->cost_to_us) / 3,2);
            $fPrice = max($this->getMapPrice(), $fPrice);
        }

        return $fPrice;
    }

    public function isSupplierFeedsEnabled()
    {
        if (is_null($this->bSupplierFeed)) {
            $this->bSupplierFeed = false;
            $sEnabled = func_query_first_cell_param(
                "SELECT enabled 
                         FROM xcart_supplier_feeds 
                        WHERE manufacturerid=:manufacturer 
                        AND feed_type = 'I' 
                        AND enabled='Y' 
                        AND (multiple_feed_destinations!='Y' OR (multiple_feed_destinations='Y' AND feed_file_name=:feed_file_name))",
                ['manufacturer' => $this->manufacturerid,
                 'feed_file_name' => $this->controlled_by_feed]);
            if ($sEnabled == 'Y') $this->bSupplierFeed = true;
        }
        return $this->bSupplierFeed;
    }

    public function getPreviewImageURL()
    {
        $sUrl = null;
        $this->getImages('P');
        if (!empty($this->aImagesP)) {
            $oImage = reset($this->aImagesP);
            $sUrl = $oImage->getURL();
        }
        return $sUrl;
    }

    public function getDetailedImages()
    {
        return $this->getImages('D');
    }

    public function getAmazonASIN()
    {
        $sASIN = func_query_first_cell("SELECT asin FROM " . self::$sql_tbl['products_amz_fields'] . " WHERE productid=" . $this->getProductId());
        return $sASIN;
    }

    public function getAmazonFBAAvail()
    {
        if (is_null($this->iAmazonFbaAvail)) {
            $this->iAmazonFbaAvail = intval(func_query_first_cell("SELECT cidev_get_amazon_FBA_cloned_stock(" . $this->getProductId() . ") as amazon_fba_avail"));
        }
        return $this->iAmazonFbaAvail;
    }

    public function getAmazonFBAStockTotal()
    {
        if (is_null($this->iAmazonFbaStockTotal)) {
            $this->iAmazonFbaStockTotal = intval(func_query_first_cell("SELECT cidev_get_amazon_FBA_stock_total(" . $this->getProductId() . ") as stock_total"));
        }
        return $this->iAmazonFbaStockTotal;
    }

    public function getAmazonFBAStockReservedTransfers()
    {
        if (is_null($this->iAmazonFbaStockReservedTransfers)) {
            $this->iAmazonFbaStockReservedTransfers = intval(func_query_first_cell("SELECT cidev_get_amazon_FBA_stock_reserved_transfers(" . $this->getProductId() . ") as stock_total"));
        }
        return $this->iAmazonFbaStockReservedTransfers;
    }

    public function getAmazonFBAAvailReal()
    {
        return intval($this->getField('amazon_fba_avail'));
    }

    public function getAmazonFBAAvailExcludedProcessing()
    {
        $aResult = SQLBuilder::getInstance()->addSelect('COALESCE(SUM(OD.amount- OD.back),0)', 'AvailOnFBA')->
        addFromTable('order_groups', 'OG')->
        addInnerJoin('orders', 'O', 'O.orderid = OG.orderid', 'LEFT JOIN')->
        addInnerJoin('order_details', 'OD', 'OD.orderid = O.orderid', 'LEFT JOIN')->
        addInnerJoin('products', 'P', 'P.productid = ' . $this->getProductId() . ' AND OD.productid = P.productid')->
        addCondition("OG.cb_status IN ('IO','P','H','3','Q','N','O','AP')")->
        addCondition("OG.dc_status IN ('B','M','T','K','DP','E','G')")->
        addCondition('FROM_UNIXTIME(O.date) > DATE_ADD(NOW(),INTERVAL -4 WEEK)')->
        query_first()->getQueryResult();
        $avail = intval($this->getAmazonFBAAvail() * 0.8) - intval($aResult['AvailOnFBA']);
        if ($avail <= 0 && $this->getAmazonFBAAvail() == 1 && intval($aResult['AvailOnFBA'] == 0)){
            $avail = 1;
        }
        return $avail;
    }

    public function isProductFBAAvail()
    {
        return ($this->getAmazonFBAAvail() > 0);
    }

    public function isAmazonFBAEnabled()
    {
        return ($this->getField('amazon_fba') == 'Y');
    }

    public function isAmazonEnabled()
    {
        return ($this->getField('amazon_enabled') == 'Y');
    }

    public function isAmazonFBARestricted()
    {
        $bResult = false;
        if ($this->getProductId()) {
            $oProductAmazon = ProductsAmazonFields::model(['productid' => $this->getProductId()]);
            $bResult = ($oProductAmazon->getField('amazon_fba_restricted') == 'Y');
        }
        return $bResult;
    }

    public function getUPC()
    {
        return $this->getField('upc');
    }

    public function getProductName()
    {
        return $this->getField('product');
    }

    public function isRetailTrustEnabled()
    {
        return ($this->getField('retail_trust_enabled') == 'Y') ? true : false;
    }

    /**
     * @param $sSKU
     * @return Product
     */
    public static function getProductBySKU($sSKU)
    {
        return Product::model()->find(SQLBuilder::getInstance()->addCondition("productcode = '$sSKU'"));
    }

    /**
     * @return Product[]
     */
    public function getChildProducts()
    {
        $aResult = [];
        if ($this->productid) {
            $aResult = self::objects()->filter(['clone_parent_productid' => $this->productid])->all();
        }
        return $aResult;
    }

    /**
     * @return Product|null
     */
    public function getParentProduct()
    {
        /** @var Product $oParentProduct */
        $oParentProduct = null;
        if ($this->clone_parent_productid) {
            $oParentProduct = self::objects()->filter(['productid' => $this->clone_parent_productid])->get();
        }
        return $oParentProduct;
    }

    public function getProductsAvailOnAmazonParentWithChild($iQty)
    {
        $aProductAmazonArray = [];
        $iShipNeed = $iQty;
        if ($iQty > 0 && $this->getAmazonFBAAvail() >= $iQty) {
            if ($this->getAmazonFBAAvailReal() > 0) {
                if ($this->getAmazonFBAAvailReal() >= $iQty) {
                    $aProductAmazonArray[] = ['oProduct' => $this, 'qty' => $iQty];
                    $iShipNeed -= $iQty;
                } else {
                    $aProductAmazonArray[] = ['oProduct' => $this, 'qty' => $this->getAmazonFBAAvailReal()];
                    $iShipNeed -= $this->getAmazonFBAAvailReal();
                }
            }
            if ($iShipNeed > 0) {
                if ($this->isParent()) { //parent
                    $aChildProducts = $this->getChildProducts();
                    if (!empty($aChildProducts)) {
                        foreach ($aChildProducts as $oChildProduct) {
                            if ($oChildProduct->getAmazonFBAAvailReal() > 0) {
                                if ($oChildProduct->getAmazonFBAAvailReal() >= $iShipNeed) {
                                    $aProductAmazonArray[] = ['oProduct' => $oChildProduct, 'qty' => $iShipNeed];
                                    $iShipNeed -= $iShipNeed;
                                } else {
                                    $aProductAmazonArray[] = ['oProduct' => $oChildProduct, 'qty' => $oChildProduct->getAmazonFBAAvailReal()];
                                    $iShipNeed -= $oChildProduct->getAmazonFBAAvailReal();
                                }
                            }
                            if ($iShipNeed <= 0) break;
                        }
                    }
                } else { //child
                    $oParentProduct = $this->getParentProduct();
                    if ($oParentProduct->getAmazonFBAAvailReal() > 0) {
                        if ($oParentProduct->getAmazonFBAAvailReal() >= $iShipNeed) {
                            $aProductAmazonArray[] = ['oProduct' => $oParentProduct, 'qty' => $iShipNeed];
                            $iShipNeed -= $iShipNeed;
                        } else {
                            $aProductAmazonArray[] = ['oProduct' => $oParentProduct, 'qty' => $oParentProduct->getAmazonFBAAvailReal()];
                            $iShipNeed -= $oParentProduct->getAmazonFBAAvailReal();
                        }
                    }
                    if ($iShipNeed > 0) {
                        $aChildProducts = $oParentProduct->getChildProducts();
                        if (!empty($aChildProducts)) {
                            foreach ($aChildProducts as $oChildProduct) {
                                if ($oChildProduct->getProductId() != $this->getProductId() && $oChildProduct->getAmazonFBAAvailReal() > 0) {
                                    if ($oChildProduct->getAmazonFBAAvailReal() >= $iShipNeed) {
                                        $aProductAmazonArray[] = ['oProduct' => $oChildProduct, 'qty' => $iShipNeed];
                                        $iShipNeed -= $iShipNeed;
                                    } else {
                                        $aProductAmazonArray[] = ['oProduct' => $oChildProduct, 'qty' => $oChildProduct->getAmazonFBAAvailReal()];
                                        $iShipNeed -= $oChildProduct->getAmazonFBAAvailReal();
                                    }
                                }
                                if ($iShipNeed <= 0) break;
                            }
                        }
                    }
                }
            }
        }
        return $aProductAmazonArray;
    }

    private static function UPC_calculate_check_digit($upc_code)
    {
        $sum = 0;
        $mult = 3;
        for ($i = (strlen($upc_code) - 2); $i >= 0; $i--) {
            $sum += $mult * $upc_code[$i];
            if ($mult == 3) {
                $mult = 1;
            } else {
                $mult = 3;
            }
        }
        if ($sum % 10 == 0) {
            $sum = ($sum % 10);
        } else {
            $sum = 10 - ($sum % 10);
        }
        return $sum;
    }

    private static function isISBN($sCode)
    {
        $bResult = false;
        if (in_array(strlen($sCode), [10, 13])) {
            if (in_array(substr($sCode, 0, 3), [978, 979])) {
                $bResult = true;
            }
        }
        return $bResult;
    }

    public static function calculateUPC($upc_code)
    {
        $upc_code = preg_replace("/[^0-9]/", "", $upc_code);
        switch (strlen($upc_code)) {
            case 8:
            case 14:
                $cd = self::UPC_calculate_check_digit($upc_code);
                if ($cd != $upc_code[strlen($upc_code) - 1]) {
                    return substr($upc_code, 0, -1) . $cd;
                } else {
                    return $upc_code;
                }
                break;
            case 11:
            case 12:
            case 13:
                $cd = self::UPC_calculate_check_digit($upc_code);
                if ($cd != $upc_code[strlen($upc_code) - 1]) {
                    if (!self::isISBN($upc_code) || (self::isISBN($upc_code) && strlen($upc_code) == 12)) {
                        $cd = self::UPC_calculate_check_digit($upc_code . "1");
                        return $upc_code . $cd;
                    } else {
                        return "";
                    }
                } else {
                    return $upc_code;
                }
                break;
        }
        return "";
    }

    /**
     * @return ProductQuestion[]
     */
    public function getProductQuestions()
    {
        if (is_null($this->aProductQuestions))
            $this->aProductQuestions = ProductQuestion::model()->findAll(SQLBuilder::getInstance()->addCondition('productid=' . $this->getProductId()));
        return $this->aProductQuestions;
    }

    public function getSKURetailTrust()
    {
        return self::RETAIL_TRUST_SKU_PREFIX . $this->getSKU();
    }

    public function getAmazonQuantity()
    {
        if (is_null($this->iAmazonQuantity)) {
            $aResult = SQLBuilder::getInstance()->
            addSelect('cidev_get_amazon_quantity(' . $this->getProductId() . ')', 'aquantity')->
            addFromTable('products')->
            addCondition('productid=' . $this->getProductId())->
            query_first()->getQueryResult();
            $this->iAmazonQuantity = $aResult['aquantity'];
        }
        return $this->iAmazonQuantity;
    }

    public function getAmazonPrice()
    {
        if (is_null($this->fAmazonPrice)) {
            $aResult = SQLBuilder::getInstance()->
            addSelect('cidev_get_amazon_price(' . $this->getProductId() . ')', 'aprice')->
            addFromTable('products')->
            addCondition('productid=' . $this->getProductId())->
            query_first()->getQueryResult();
            $this->fAmazonPrice = floatval($aResult['aprice']);
        }
        return $this->fAmazonPrice;
    }

    public function getMinimumAmazonPrice()
    {
        $aResult = SQLBuilder::getInstance()->
        addSelect('cidev_get_minimum_amazon_price(' . $this->getProductId() . ')', 'aprice')->
        addFromTable('products')->
        addCondition('productid=' . $this->getProductId())->
        query_first()->getQueryResult();
        return floatval($aResult['aprice']);
    }

    public function getShippingVolume($iAmount = 1)
    {
        if (($this->getField('shipping_dim_x') || $this->getField('shipping_dim_y') || $this->getField('shipping_dim_z'))) {
            $aVolume = $this->getField('shipping_dim_x') * $this->getField('shipping_dim_y') * $this->getField('shipping_dim_z') * $iAmount;
        } else {
            $aVolume = $this->getField('dim_x') * $this->getField('dim_y') * $this->getField('dim_z') * $iAmount;
        }
        return $aVolume;
    }

    public function getShippingWeight($iAmount = 1)
    {
        $fProductWeight = 0.1;
        if (floatval($this->getField('shipping_weight')) > 0) {
            $fProductWeight = floatval($this->getField('shipping_weight'));
        } elseif (floatval($this->getField('weight')) > 0) {
            $fProductWeight = floatval($this->getField('weight'));
        }
        return $fProductWeight * $iAmount;
    }

    public function getShippingFreight()
    {
        return floatval($this->getField('shipping_freight'));
    }

    public function getExtraMarginValue($forQuantity = 1)
    {
        if (is_null($this->fExtraMarginValue)) {
            $oManufacturer = $this->getManfacturerClass();
            if ($oManufacturer->getField('reduce_extra_margin') == 'Y') {
                if (floatval($oManufacturer->getField('price_coef_z') != 0) && $this->getProductCostToUs() > 0) {
                    $fExpectedMargin = round(($this->getProductCostToUs() * floatval($oManufacturer->getField('price_coef_x')) + floatval($oManufacturer->getField('price_coef_y'))) / floatval($oManufacturer->getField('price_coef_z')), 2);
                    $this->fExtraMarginValue = ($this->getPrice($forQuantity) - $fExpectedMargin) * $forQuantity;
                }
            }
        }
        return $this->fExtraMarginValue;
    }

    public static function updateShowInLists(array $ids)
    {
        if (!empty($ids) && !defined('IS_ROBOT'))
        {
            $ids        = array_unique($ids);
            $table      = 'xcart_products_showed';
            $connection = Connection::getInstance();

            $sql = QueryBuilder::getInstance($connection)
                               ->setTypeUpdate()
                               ->setOptions('LOW_PRIORITY')
                               ->where(['productid__in' => $ids])
                               ->update($table, ['in_list_showed' => new Expression('in_list_showed + 1')])
                               ->toSQL();

            if ($connection->exec($sql) != count($ids))
            {
                $e_ids = [];
                $sql   = QueryBuilder::getInstance($connection)
                                     ->setTypeSelect()
                                     ->from($table)
                                     ->select(['productid'])
                                     ->where(['productid__in' => $ids])
                                     ->toSQL();

                foreach ($connection->fetchAll($sql) as $item) {
                    $e_ids[] = $item['productid'];
                }

                $ids = array_diff($ids, $e_ids);

                if (!empty($ids))
                {
                    $ids = array_unique($ids);
                    $ids = array_map(function ($id) { return ['productid' => $id]; }, $ids);
                    $ids = array_values($ids);

                    $sql = QueryBuilder::getInstance($connection)->setOptions('ignore')->insert($table, $ids);
                    $connection->exec($sql);
                }
            }
        }
    }

    public static function getRandFbaProducts($limit = 2, array $no_ids = null, $sfid = null)
    {
        global $current_storefront_info;


        if (empty($sfid) && !empty($current_storefront_info)) {
            $sfid = $current_storefront_info['storefrontid'];
        }

        $where = ['amazon_fba' => 'Y', 'amazon_fba_avail__gt' => 1, 'forsale' => 'Y', 'ps.sfid' => $sfid];

        if (!empty($no_ids)) {
            $where[] = new QAndNot(['productid__in' => $no_ids]);
        }

        $connection = Connection::getInstance();
        $sql = QueryBuilder::getInstance($connection)
                           ->setTypeSelect()
                           ->select(['needed_resource_id' => 'p.productid'])
                           ->from('xcart_products')
                           ->setAlias('p')
                           ->join('inner join', 'xcart_products_sf', ['ps.productid' => 'p.productid' , 'ps.sfid' => new Expression($sfid)], 'ps')
                           ->join('left join',  'xcart_products_showed', ['p.productid' => 's.productid'], 's')
                           ->order(['s.in_list_showed', '?'])
                           ->where($where)
                           ->limit($limit)
                           ->toSQL();

        return $connection->fetchAll($sql);
    }

    public function getProductCategories()
    {
        if (is_null($this->aProductCategories)) {
            $this->aProductCategories = ProductCategories::model()->findAll(SQLBuilder::getInstance()->addCondition('productid = ' . $this->getProductId())->addOrderBy('orderby'));
        }
        return $this->aProductCategories;
    }

    /**
     * @return Category
     */
    public function getMainCategory()
    {
        if (is_null($this->oMainCategory)) {
            $aCats = $this->getProductCategories();
            if (!empty($aCats)) {
                foreach ($aCats as $oCat) {
                    if ($oCat->isMain()) {
                        $this->oMainCategory = Category::model(['categoryid' => $oCat->getField('categoryid')]);
                        break;
                    }
                }
            }
        }
        return $this->oMainCategory;
    }

    /**
     * @return Category[]
     */
    public function getAdditionalCategories()
    {
        $aRes = [];
        $aCats = $this->getProductCategories();
        if (!empty($aCats)) {
            foreach ($aCats as $oCat) {
                if (!($oCat->isMain())) {
                    $oCategory = Category::model(['categoryid' => $oCat->getField('categoryid')]);
                    if ($oCategory->getCategoryId()) {
                        $aRes[] = $oCategory;
                    }
                }
            }
        }
        return $aRes;
    }

    public function getThumbnail()
    {
        $oThumbImage = null;
        if (is_null($this->aThumbNails)){
            $this->aThumbNails = \Modules\Product\Models\ImageTModel::objects()->filter(['id' => $this->getProductId()])->all();
        }
        if (!empty($this->aThumbNails)) {
            $oThumbImage = reset($this->aThumbNails);
        }
        return $oThumbImage;
    }

    public function getSplash()
    {
        return \Xcart\Images\Splash::objects()->filter(['id' => (int) $this->splash_id])->get();
    }
}