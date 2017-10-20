<?php
namespace Xcart;

class Products extends CloneData
{
    private $aProductToQueue;
    public $addCounter;
    public $addFailCounter;
    public $updateCounter;
    public $updateFailCounter;
    private $sQueueTable;
    public $changeProvider = 'cron';

    public function __construct($iId = null)
    {
        $this->sPrimaryTable = "products";
        $this->sPrimaryKeyFiled = "productid";

        parent::__construct($iId);

        $this->init();
    }
    public function init()
    {

        $this->sQueueTable = "clone_products_queue";
        $this->addCounter = 0;
        $this->addFailCounter = 0;
        $this->updateCounter = 0;
        $this->updateFailCounter = 0;

        $this->arrCloneTableStructure[] = array("table" => $this->sPrimaryTable,"key_field" => $this->sPrimaryKeyFiled, "primary_key" => $this->sPrimaryKeyFiled);
        $this->arrCloneTableStructure[] = array("table" => "images_D","key_field" => "id", "primary_key" =>"imageid");
        $this->arrCloneTableStructure[] = array("table" => "images_P","key_field" => "id", "primary_key" =>"imageid");
        $this->arrCloneTableStructure[] = array("table" => "images_T","key_field" => "id", "primary_key" =>"imageid");
        $this->arrCloneTableStructure[] = array("table" => "quick_flags","key_field" => "productid", "primary_key" =>"productid");
        $this->arrCloneTableStructure[] = array("table" => "product_files","key_field" => "productid", "primary_key" =>"fileid");
        $this->arrCloneTableStructure[] = array("table" => "product_taxes","key_field" => "productid", "primary_key" =>"productid");
        $this->arrCloneTableStructure[] = array("table" => "products_amz_fields","key_field" => "productid", "primary_key" =>"id");
        //$this->arrCloneTableStructure[] = array("table" => "clean_urls","key_field" => "resource_id", "primary_key" =>"resource_id");
        $this->arrCloneTableStructure[] = array("table" => "pricing","key_field" => "productid", "primary_key" =>"priceid");

        $this->arrCheckFields[$this->sPrimaryTable] = array('productid',
            'productcode',
            'product',
            'product_froogle',
            'provider',
            'original_provider',
            'distribution',
            'weight',
            'list_price',
            'descr',
            'fulldescr',
            'avail',
            'rating',
            'forsale',
            'add_date',
            'mod_date',
            'views_stats',
            'sales_stats',
            'del_stats',
            'shipping_freight',
            'free_shipping',
            'discount_avail',
            'min_amount',
            'dim_x',
            'dim_y',
            'dim_z',
            'low_avail_limit',
            'free_tax',
            'product_type',
            'manufacturerid',
            'brandid',
            'return_time',
            'keywords',
            'google_search_term',
            'discount_slope',
            'discount_table',
            'free_ship_zone',
            'free_ship_text',
            'upc',
            'cost_to_us',
            'source_sfid',
            'map_price',
            'mult_order_quantity',
            'new_map_price',
            'warning_code',
            'eta_date_mm_dd_yyyy',
            'lead_time_message',
            'similar_cron_generated_flag',
            'similar_productids',
            'similar_time',
            'product_price_multiplier',
            'supplier_internal_product_id',
            'generate_similar_products',
            'tmp_generated_file',
            'update_search_index',
            'pc_classify_status',
            'pc_mc_operator',
            'pc_acc_operator',
            'r_avail',
            'supplier_internal_id',
            'supplier_internal_id_last_parsed',
            'supplier_internal_id_last_parsed_update',
            'last_incremental_update',
            'amazon_enabled',
            'pc_most_relevant_categories',
            'pc_delta',
            'supplier_internal_option',
            'amazon_fba',
            'amazon_fba_avail',
            'title_tag',
            'seo_product_name',
            'seo_meta_descr',
            'lock_forsale',
            'seo_h2',
            'prevent_search_indexing_this_product_page',
            'controlled_by_feed',
            'eta_date_lock',
            'clone_parent_productid',
            'dim_lock',
            'shipping_dim_x',
            'shipping_dim_y',
            'shipping_dim_z',
            'shipping_dim_lock',
            'shipping_weight',
            'shipping_weight_lock',
            'weight_lock',
            'verification_statusid',
            'last_verify_date',
            'retail_trust_enabled',
            'log_stock_history',
            'seo_fulldescr',
            'in_list_showed',
            'splash_id',
        );
    }

    /**
     * https://s3stores.teamwork.com/tasks/6416520
     *
     * @param $sleepTime
     * @return bool
     */
    public function cloneProductFunction($sleepTime) {

        $bResult = true;

        if (!$this->checkDBChanges()) {
            $this->BackprocessLogs("DB schema changed, product cloning disabled...","clone_products_cron_errors");
            return false;
        }

        $aProductsQueue = $this->getProductsFromQueue();

        if (empty($aProductsQueue)) {
            $this->message[] = "No products in queue";
            return $bResult;
        }

        foreach ($aProductsQueue as $oProductQueue) {
            $this->aProductToQueue = $oProductQueue;

            $aProduct = $this->getProductInfo($this->aProductToQueue[$this->sPrimaryKeyFiled]);

            switch ($this->aProductToQueue["clone"]) {
                case "Y":
                    $bResult = $this->cloneProduct($aProduct); //(клонирование продукта)
                    break;
                case "N":
                    $bResult = $this->updateProduct($aProduct); //ИНАЧЕ (обновление продукта)
                    break;
            }

            if (!$this->deleteFromQueue()) {
                $this->message[] = "Error delete from Queue table";
                $bResult = false;
            }

            if ($bResult) usleep($sleepTime);
        }

        return $bResult;

    }

    public function getProductQueueCount () {
        return func_query_first_cell("SELECT count(1) as count FROM ".self::$sql_tbl[$this->sQueueTable]);
    }

    public function getChildProducts ($iProductId) {
        return func_query("SELECT * FROM ".self::$sql_tbl[$this->sPrimaryTable]." WHERE clone_parent_productid = $iProductId");
    }

    public function getProductInfo($iProductId) {
        return func_query_first("SELECT * FROM ".self::$sql_tbl[$this->sPrimaryTable]." WHERE productid = $iProductId");
    }

    public function getProductsInfo($aProductsId) {
        return func_query("SELECT * FROM ".self::$sql_tbl[$this->sPrimaryTable]." WHERE productid IN (".implode(',',$aProductsId).")");
    }

    public function getMainProductCategoriesInfo($iProductId) {
        return func_query("SELECT c.* FROM ".self::$sql_tbl['products_categories']." pc
                                             INNER JOIN ".self::$sql_tbl['categories']." c ON pc.categoryid = c.categoryid WHERE pc.main='Y' AND pc.productid = $iProductId");
    }

    public function getProductVariants($iProductId) {
        return func_query("SELECT * FROM ".self::$sql_tbl['variants']." WHERE productid = $iProductId");
    }

    protected function getNextProductFromQueue () {
        $this->aProductToQueue = func_query_first("SELECT * FROM ".self::$sql_tbl[$this->sQueueTable]." ORDER BY insert_datetime ASC LIMIT 1");
        return !empty($this->aProductToQueue);
    }

    protected function getProductsFromQueue () {
        return func_query("SELECT * FROM ".self::$sql_tbl[$this->sQueueTable]." ORDER BY insert_datetime");
    }



    protected function deleteFromQueue() {
        return db_query("DELETE FROM ".self::$sql_tbl[$this->sQueueTable]." WHERE $this->sPrimaryKeyFiled = ".$this->aProductToQueue['productid']."
        AND clone = '".$this->aProductToQueue['clone']."'
        AND manufacturerid = ".$this->aProductToQueue['manufacturerid']);
    }

    public function getProductMPN($sSKU, $sPrefixManufacturer = "", $iProductId = null) {
        if (empty($sPrefixManufacturer) && isset($iProductId)) {
            $aProduct = $this->getProductInfo($iProductId);
            if (!empty($aProduct)) {
                $classManufacturer = new Manufacturers();
                $sPrefixManufacturer = $classManufacturer->getManufacturerCodeById($aProduct['manufacturerid']);
            }
        }
        if (strpos($sSKU, $sPrefixManufacturer) == 0)
            return preg_replace("/^($sPrefixManufacturer-)/i","", $sSKU);
        else return false;
    }

    protected function getClonedSKU ($originSKU, $sProductMPN) {
        if (!$sProductMPN) return false;

        return $originSKU."-".$sProductMPN;
    }

    protected function IncSuccessAdd() {
        $this->addCounter++;
    }

    protected function IncSuccessUpdate() {
        $this->updateCounter++;
    }

    protected function IncFailAdd() {
        $this->addFailCounter++;
    }

    protected function IncFailUpdate() {
        $this->updateFailCounter++;
    }

    public function getFilterInfoByFilterValueId($iFilterValueId) {
        return func_query_first("SELECT xc1.* FROM ".self::$sql_tbl['cidev_filter_values']." fv INNER JOIN ".self::$sql_tbl['cidev_filters']." xc1 ON xc1.f_id = fv.f_id AND fv.fv_id =$iFilterValueId");
    }

    public function getFilterByNameAndStoreFront ($sFilterName, $iStoreFrontId) {
        $sFilterName = addslashes($sFilterName);
        return func_query_first_cell("SELECT f_id FROM ".self::$sql_tbl['cidev_filters']." WHERE f_name='$sFilterName' AND storefrontid=$iStoreFrontId");
    }

    public function createNewFilter($aFilter) {
        array_walk_recursive($aFilter, array(__CLASS__,'recursive_escape'));
        $iFilterId = func_array2insert('cidev_filters', $aFilter);
        return $iFilterId;
    }

    public function getProductFilters ($iProductId){
        return func_query("SELECT * FROM ".self::$sql_tbl['cidev_filter_products']." WHERE productid = $iProductId");
    }

    public function getFilterValuesByNameAndFilterType($sFilterName, $iFilterTypeId){
        $sFilterName = addslashes($sFilterName);
        return func_query_first("SELECT * FROM ".self::$sql_tbl['cidev_filter_values']." WHERE fv_name='".$sFilterName."' AND f_id=$iFilterTypeId");
    }

    public function getFilterValues ($iFilterValueId) {
        return func_query("SELECT * FROM ".self::$sql_tbl['cidev_filter_values']." WHERE fv_id=$iFilterValueId");
    }

    public function deleteFilterValues($iProductId) {
        return db_query("DELETE FROM ".self::$sql_tbl['cidev_filter_products']." WHERE productid = $iProductId");
    }

    public function cloneFilterValues ($oFilter, $iNewFilterId) {
        $aNewFilterValuesId = array();

        $aFilterValues = $this->getFilterValues($oFilter['fv_id']);

        if (isset($aFilterValues) && is_array($aFilterValues) && !empty($aFilterValues)) {
            foreach($aFilterValues as $oFilter) {
                $oFilter['f_id'] = $iNewFilterId;
                unset($oFilter['fv_id']);
                $aNewFilterValue = $this->getFilterValuesByNameAndFilterType($oFilter['fv_name'], $iNewFilterId);
                if (isset($aNewFilterValue) && is_array($aNewFilterValue) && !empty($aNewFilterValue)) {
                    $aNewFilterValuesId[] = $aNewFilterValue['fv_id'];
                } else {
                    array_walk_recursive($oFilter, array(__CLASS__,'recursive_escape'));
                    $aNewFilterValuesId[] = func_array2insert('cidev_filter_values', $oFilter);
                }
            }
        }
        return($aNewFilterValuesId);
    }

    public function cloneFilter($oFilter,$aParamToClone) {
        $aFilter = $this->getFilterInfoByFilterValueId($oFilter['fv_id']);
        $iFilterId = $this->getFilterByNameAndStoreFront($aFilter['f_name'], $aParamToClone['d_main_sf']);

        if (empty($iFilterId)) {
            unset($aFilter['f_id']);
            $aFilter['storefrontid'] = $aParamToClone['d_main_sf'];

            return $this->createNewFilter($aFilter);

        } else return $iFilterId;
    }

    private function cloneProductFilters($iProductId, $aParamToClone) {
        $this->deleteFilterValues($aParamToClone[$this->sPrimaryKeyFiled]);
        $aProductFilters = $this->getProductFilters($iProductId);

        if (isset($aProductFilters) && is_array($aProductFilters) && !empty($aProductFilters)) {
            foreach ($aProductFilters as $oFilter) {
                $iFilterId = $this->cloneFilter($oFilter,$aParamToClone);

                $aNewFilterValues = $this->cloneFilterValues($oFilter,$iFilterId);

                if (isset($aNewFilterValues) && is_array($aNewFilterValues) && !empty($aNewFilterValues)) {
                    foreach($aNewFilterValues as $iFilterValue) {
                        $this->addFilterValueToProduct($iFilterValue, $aParamToClone[$this->sPrimaryKeyFiled]);
                    }
                }
            }
        }
    }


    public function addProductToStoreFront ($iProductId, $iStoreFrontId) {
        $aProductStoreFront = array('productid' => $iProductId, 'sfid' => $iStoreFrontId);
        func_array2insert ('products_sf', $aProductStoreFront);
    }

    public function addMainProductCategory ($iProductId, $iCategoryId) {
        $aProductCategory = array('productid' => $iProductId, 'categoryid' => $iCategoryId, 'main' => 'Y', 'orderby' => 10);
        func_array2insert ('products_categories', $aProductCategory);
    }

    public function setProductForSale($iProductId, $sStatus = 'Y') {
        func_array2update($this->sPrimaryTable, array('forsale' => $sStatus), 'productid = '.$iProductId);
    }

    private function addFilterValueToProduct($iFilterId, $iNewProductId){
        $aFilterProduct = array('fv_id' => $iFilterId, 'productid' => $iNewProductId);
        func_array2insert ('cidev_filter_products', $aFilterProduct, true);
    }

    private function BackprocessLogs($sLogMessage, $sProcessId = "clone_products_cron") {
        $this->message[] = $sLogMessage;
        if (isset($this->aProductToQueue[$this->sPrimaryKeyFiled]) && $this->aProductToQueue[$this->sPrimaryKeyFiled])
            $sLogMessage .= "; Productid = ".$this->aProductToQueue[$this->sPrimaryKeyFiled];
        func_backprocess_log($sProcessId, $sLogMessage);
    }

    public function updateProductCleanUrl($iProductId) {
        $aProduct = $this->getProductInfo($iProductId);
        $clean_url = func_clean_url_autogenerate('P', $iProductId, array('product' => $aProduct["product"], 'productcode' => $aProduct['productcode']));
        db_query("DELETE FROM ". self::$sql_tbl['clean_urls'] . " WHERE resource_type='P' AND resource_id=$iProductId");
        func_clean_url_add($clean_url, 'P', $iProductId);
    }

    public function insertQuickPrices($iProductId) {
        x_load('product');
        func_build_quick_prices($iProductId);
    }

    private function getClonedRelatedProductsByIdAndStoreFrontId($iProductId, $iStoreFrontId) {
        return func_query("SELECT xp1.productid FROM ".self::$sql_tbl['product_links']." xp
                                               INNER JOIN xcart_products xp1 ON xp.productid2 = xp1.clone_parent_productid
                                               INNER JOIN xcart_products_sf xp2 ON xp1.productid = xp2.productid AND xp2.sfid = $iStoreFrontId
                                               WHERE xp.productid1 = $iProductId");
    }

    private function deleteFromRelatedProducts($iProductId) {
        db_query("DELETE FROM ".self::$sql_tbl['product_links']." WHERE productid1 = $iProductId");
    }

    private function insertClonedRelatedProducts ($iProductId, $iProductIdCloned, $iStoreFrontId) {
        $this->deleteFromRelatedProducts($iProductIdCloned);
        $aRelatedProducts = $this->getClonedRelatedProductsByIdAndStoreFrontId($iProductId, $iStoreFrontId);

        if (isset($aRelatedProducts) && is_array($aRelatedProducts) && !empty($aRelatedProducts)) {
            foreach($aRelatedProducts as $oRelatedProduct) {
                func_array2insert('product_links',array('productid1' => $iProductIdCloned, 'productid2' => $oRelatedProduct['productid'], 'orderby' => 10));
            }
        }
    }





    protected function cloneProduct($aProduct, $aParamToClone = array()) {

        $classManufacturer = new Manufacturers();

        $aQueuedManufacturer = $classManufacturer->getMainufacturersInfo(array($this->aProductToQueue["manufacturerid"]));
        if (!empty($aQueuedManufacturer))
            $aQueuedManufacturer = reset($aQueuedManufacturer);


        /*ЕСЛИ [PRODUCT] не существует ИЛИ [PRODUCT].forsale !="Y" ИЛИ trim([PRODUCT].clone_parent_productid) >0 или дистрибьютор от [xcart_clone_products_queue].manufacturerid не имеет родителя, ТО
			залоггировать в BackprocessLogs текст 'trying clone cloned, disabled or non-existing product, or target manufacturer is not a clone. skip...'*/

        if (empty($aProduct) || $aProduct["forsale"] != "Y" || $aProduct["clone_parent_product_id"] > 0 || $aQueuedManufacturer["parent_manufacturer_id"] == -1) {
            $this->BackprocessLogs("trying clone cloned, disabled or non-existing product, or target manufacturer is not a clone. skip...");
            $this->IncFailAdd();
            return false;
        }


        /* skip если есть записи в xcart_variants*/

        $aVariants = $this->getProductVariants($aProduct['productid']);
        if (!empty($aVariants)) {
            $this->BackprocessLogs("trying clone product with variants. skip...");
            $this->IncFailAdd();
            return false;
        }


        /*ИНАЧЕ
		получить все подчиненные дистрибьюторы дистрибьютора продукта [PRODUCT] --> [Distributors] (получить code дистрибьютора , отобрать всех дистрибьюторов у которых parent_manufacturer_id = manufacturerid)
		*/



        $aManufacturer = $classManufacturer->getMainufacturersInfo(array($aProduct["manufacturerid"]));
        $aManufacturer = reset($aManufacturer);

        $aChildManufacturers = $classManufacturer->getChildrenManufacturers($aManufacturer["manufacturerid"]);

        //если [xcart_clone_products_queue].manufacturerid >=0, то убрать из списка дистрибьюторов всех кроме дистрибьютора с данным manufacturerid
        if ($this->aProductToQueue["manufacturerid"] >= 0) {
            $aChildManufacturers = $this->search_array_key_value($aChildManufacturers,"manufacturerid", $this->aProductToQueue["manufacturerid"]);
        }


        /*  ЦИКЛ по [Distributors]
                сформировать clonedSKU предполагаемого клона по очередному дистрибьютору: [Distributors].code-[PRODUCT].mpn
                если clonedSKU существует в БД, то
                    залоггировать в BackprocessLogs текст '[clonedSKU] already exist. Added to update queue...'
                    вставить [clonedSKU].productid в очередь с параметром clone = 'N'
                иначе
                    вызвать блок вставки нового продукта для очередного подчиненного дистрибьютора;
                    посчитать успешное добавление
                конец если
            КОНЕЦ ЦИКЛ по [Distributors]
        */
        if (empty($aChildManufacturers)) {
            $this->IncFailAdd();
            $this->message[] = "No Manufacturers found to Clone";
            return false;
        }


        foreach ($aChildManufacturers as $aChildManufacturer) {
            //сформировать clonedSKU предполагаемого клона по очередному дистрибьютору: [Distributors].code-[PRODUCT].mpn
            $sClonedSKU = $this->getClonedSKU($aChildManufacturer["code"], $this->getProductMPN($aProduct["productcode"],$aManufacturer["code"]));
            if (!empty($sClonedSKU)) {
                /*если clonedSKU существует в БД, то
                  залоггировать в BackprocessLogs текст '[clonedSKU] already exist. Added to update queue...'
                  вставить [PRODUCT].productid в очередь с параметром clone = 'N'*/
                $aProductBySKU = $this->getProductBySKU($sClonedSKU);

                if (isset($aProductBySKU) && is_array($aProductBySKU) && !empty($aProductBySKU)) {
                    $this->BackprocessLogs("SKU $sClonedSKU already exist. Added to update queue...");
                    $this->queueNewProductForUpdate($aProduct['productid'], $this->aProductToQueue["manufacturerid"]);
                    $this->IncFailAdd();
                    return false;

                } else {

                    /* иначе
                        вызвать блок вставки нового продукта для очередного подчиненного дистрибьютора;
                    */
                    $aParamToClone = array(
                        "productcode" => $sClonedSKU,
                        "manufacturerid" => $aChildManufacturer["manufacturerid"],
                        "manufacturercode" => $aChildManufacturer["code"],
                        "root_category_id" => $aChildManufacturer["root_categoryid_for_cloned_products"],
                        "d_main_sf" =>  $aChildManufacturer["d_main_sf"],
                        "source_sfid" =>  $aChildManufacturer["d_main_sf"],
                        "productid" =>  $aProduct['productid'],
                        "similar_productids" => "",
                        "forsale" => "N",
                        "pc_classify_status" => "NC",
                        "pc_mc_operator" => "",
                        "pc_acc_operator" => "",
                        "amazon_enabled" => "N",
                        "pc_most_relevant_categories" => "",
                        "pc_delta" => "0",
                        "amazon_fba" => "N",
                        "amazon_fba_avail" => 0,
                        "prevent_search_indexing_this_product_page" => 'N',
                        "controlled_by_feed" => '',
                        "add_date" => time(),
                        "mod_date" => time(),
                        "provider" => $this->changeProvider,
                        "original_provider" => $this->changeProvider,
                        "clone_parent_productid" => $aProduct['productid'],
                        "product_froogle" => "",
                        "seo_product_name" => "",
                        "seo_meta_descr" => "",
                        "seo_h2" => "",
                    );

                    $this->primaryKeyValue = $this->insertClonedProduct($aProduct, $aParamToClone);

                    if (!$this->primaryKeyValue) {
                        $this->IncFailAdd();
                        $this->message[] = "Error Clone Product"; return false;
                    }

                    /*POST UPDATE VALUES:
                    (set this values after all tables updated)
                    forsale = 'Y'*/

                    $this->setProductForSale($this->primaryKeyValue);

                    /*посчитать успешное добавление*/

                    $this->IncSuccessAdd();
                }
            }
            else { $this->IncFailAdd(); $this->message[] = "Error calculate ClonedSKU"; return false;}
        }
        return true;
    }

    protected function insertClonedProduct ($aProduct, $aParamToClone) {
        /*блок вставки нового продукта
        реквизиты
        clonedSKU - SKU нового продукта
        targetDistributor - дистрибьютор нового продукта
        получить root_category_id = [targetDistributor].root_categoryid_for_cloned_products
        targetSFID = [targetDistributor].d_main_s*/


        /*ВАЖНО:
            для условия клонирования продукта:
            если root_category_id = 0, то иерархию категорий продукта нужно вставить в корень категорий
            если root_category_id > 0, то проверить есть ли такая категория и принадлежит ли она магазину назначения [xcart_categories].storefrontid (если одно из условий не выполняется выдать ошибку в backprocesslogs 'Cloning of product has issues with root category id...')
            иерархию категорий копируемого продукта копировать в подчинение этой категории
            P.S. при копировании иерархии категорий сначала проверять (по полю [xcart_categories].category) есть ли такая категория в подчинении
            если нет до добавлять, если есть то использовать ее

            Иерархию категорий продукта берем только с главной категории продукта [xcart_products_categories].main = 'Y'*/


        $classCategory = new Categories();

        $aProductCategories = $this->getMainProductCategoriesInfo($aProduct["productid"]);

        $clonedCategoryId = "";

        $aRootCategory = $classCategory->getCategoryByIdAndStoreFront($aParamToClone["root_category_id"], $aParamToClone["d_main_sf"]);
        if (empty($aRootCategory)) {
            $aParamToClone["root_category_id"] = 0;
            $this->BackprocessLogs("Cloning of product has issues with root category id...");
        }

        foreach($aProductCategories as $aProductCategory) {

            $aProductCategoryPath = $classCategory->getCategoryPathasArray($aProductCategory["categoryid"]);

            $aParamToClone["parentid"] = $aParamToClone["root_category_id"];
            if (!empty($aProductCategoryPath) && is_array($aProductCategoryPath)){
                foreach ($aProductCategoryPath as $iCategoryPathId){
                    $clonedCategoryId = $classCategory->cloneCategory($iCategoryPathId, $aParamToClone);
                    $aParamToClone["parentid"] = $clonedCategoryId;
                }
            }
        }

        $iNewProductCategory = $clonedCategoryId;


        /*данные копируем из следующих таблиц:
            xcart_products

            xcart_images_D
            xcart_images_P
            xcart_images_T
            xcart_product_files
            xcart_products_amz_fields
            xcart_product_taxes
            xcart_variants
            xcart_variant_items
            xcart_clean_urls*/

        //func_clean_url_add();


        $this->primaryKeyValue = $this->DublicatePrimaryTable($aParamToClone);
        $aParamToClone[$this->sPrimaryKeyFiled] = $this->primaryKeyValue;


        $this->DublicateNonPrimaryTable($aParamToClone);

        $this->addMainProductCategory($this->primaryKeyValue, $iNewProductCategory);

        $this->addProductToStoreFront($this->primaryKeyValue, $aParamToClone["d_main_sf"]);

        $this->updateProductCleanUrl($this->primaryKeyValue);

        $this->insertQuickPrices($this->primaryKeyValue);

        $this->insertClonedRelatedProducts($aProduct["productid"], $this->primaryKeyValue, $aParamToClone["d_main_sf"]);

        /*добавляем данные в следующие таблицы:
        xcart_categories
        xcart_products_categories

        xcart_cidev_filters
        xcart_cidev_filter_products
        xcart_cidev_filter_values

        xcart_products_sf*/


        $this->cloneProductFilters($aProduct["productid"], $aParamToClone);



        /*xcart_pricing
         xcart_quick_flags
         xcart_quick_prices

        ???
        xcart_product_links
        xcart_product_options_lng
        */


        return $this->primaryKeyValue;
    }

    protected function updateClonedProduct ($aProduct, $aClonedProduct, $aParamToClone) {
        /*при обновлении перезаписываем полностью (удаляем и вставляем из оригинала)
            xcart_images_D
            xcart_images_P
            xcart_images_T
            xcart_product_files
            xcart_products_amz_fields
            xcart_product_taxes*/


        $aParamToClone[$this->sPrimaryKeyFiled] = $aProduct[$this->sPrimaryKeyFiled];

        $this->DublicatePrimaryTable($aParamToClone ,true);

        $aParamToClone[$this->sPrimaryKeyFiled] = $aClonedProduct[$this->sPrimaryKeyFiled];

        $this->DublicateNonPrimaryTable($aParamToClone, true);

        $this->updateProductCleanUrl($aClonedProduct[$this->sPrimaryKeyFiled]);

        $this->insertClonedRelatedProducts($aProduct[$this->sPrimaryKeyFiled], $aParamToClone[$this->sPrimaryKeyFiled], $aParamToClone["d_main_sf"]);

        $this->cloneProductFilters($aProduct[$this->sPrimaryKeyFiled], $aParamToClone);



        /*xcart_products
        обновляем только поля:
        `product` VARCHAR(255) NOT NULL DEFAULT '',
        `distribution` VARCHAR(255) NOT NULL DEFAULT '',
        `weight` DECIMAL(12,2) NOT NULL DEFAULT '0.00',
        `list_price` DECIMAL(12,2) NOT NULL DEFAULT '0.00',
        `avail` MEDIUMINT(9) NOT NULL DEFAULT '0',
        `forsale` CHAR(1) NOT NULL DEFAULT 'Y',
        `mod_date` INT(11) NOT NULL DEFAULT '0',
        `shipping_freight` DECIMAL(12,2) NOT NULL DEFAULT '0.00',
        `min_amount` MEDIUMINT(9) NOT NULL DEFAULT '1',
        `dim_x` FLOAT NOT NULL DEFAULT '0',
        `dim_y` FLOAT NOT NULL DEFAULT '0',
        `dim_z` FLOAT NOT NULL DEFAULT '0',
        `free_tax` CHAR(1) NOT NULL DEFAULT 'N',
        `return_time` INT(11) NOT NULL DEFAULT '0',
        `upc` VARCHAR(14) NOT NULL DEFAULT '',
        `cost_to_us` DECIMAL(12,2) NOT NULL DEFAULT '0.00',
        `map_price` DECIMAL(12,2) NOT NULL DEFAULT '0.00',
        `mult_order_quantity` CHAR(1) NOT NULL DEFAULT 'N',
        `new_map_price` DECIMAL(12,2) NOT NULL DEFAULT '0.00',
        `eta_date_mm_dd_yyyy` VARCHAR(32) NULL DEFAULT NULL,
        `lead_time_message` VARCHAR(255) NOT NULL DEFAULT '',
        `r_avail` MEDIUMINT(9) NOT NULL DEFAULT '0',*/

        $aUpdateProduct = array(
            'product' => $aProduct['product'],
            'distribution' => $aProduct['distribution'],
            'weight' => $aProduct['weight'],
            'list_price' => $aProduct['list_price'],
            'avail' => $aProduct['avail'],
            'forsale' => $aProduct['forsale'],
            'mod_date' => $aProduct['mod_date'],
            'shipping_freight' => $aProduct['shipping_freight'],
            'min_amount' => $aProduct['min_amount'],
            'dim_x' => $aProduct['dim_x'],
            'dim_y' => $aProduct['dim_y'],
            'dim_z' => $aProduct['dim_z'],
            'free_tax' => $aProduct['free_tax'],
            'return_time' => $aProduct['return_time'],
            'upc' => $aProduct['upc'],
            'cost_to_us' => $aProduct['cost_to_us'],
            'map_price' => $aProduct['map_price'],
            'mult_order_quantity' => $aProduct['mult_order_quantity'],
            'new_map_price' => $aProduct['new_map_price'],
            'eta_date_mm_dd_yyyy' => $aProduct['eta_date_mm_dd_yyyy'],
            'eta_date_lock' => $aProduct['eta_date_lock'],
            'lead_time_message' => $aProduct['lead_time_message'],
            'r_avail' => $aProduct['r_avail'],
            'provider' => $this->changeProvider,
            'dim_lock' => $aProduct['dim_lock'],
            'shipping_dim_x' => $aProduct['shipping_dim_x'],
            'shipping_dim_y' => $aProduct['shipping_dim_y'],
            'shipping_dim_z' => $aProduct['shipping_dim_z'],
            'shipping_dim_lock' => $aProduct['shipping_dim_lock'],
            'shipping_weight' => $aProduct['shipping_weight'],
            'shipping_weight_lock' => $aProduct['shipping_weight_lock'],
            'weight_lock' => $aProduct['weight_lock']
        );

        array_walk_recursive($aUpdateProduct, array(__CLASS__,'recursive_escape'));

        func_array2update($this->sPrimaryTable, $aUpdateProduct, $this->sPrimaryKeyFiled." = ".$aClonedProduct[$this->sPrimaryKeyFiled]);

        return true;

    }

    protected function queueNewProductForUpdate ($iProductId, $iManufacturerId) {
        $aParams = array(
            'productid' => $iProductId,
            'clone' => 'N',
            'insert_datetime' => time(),
            'manufacturerid' => $iManufacturerId
        );
        func_array2insert('clone_products_queue', $aParams, true);

    }

    public function getProductBySKU($sSKU) {
        $sSKU = addslashes($sSKU);
        $aProduct = func_query_first("SELECT * FROM ".self::$sql_tbl[$this->sPrimaryTable]." WHERE productcode = '$sSKU'");
        if (empty($aProduct)) return false;
        return $aProduct;
    }

    public function getProductIdBySKU($sSKU) {
        $sSKU = addslashes($sSKU);
        return func_query_first_cell("SELECT ".$this->sPrimaryKeyFiled." FROM ".self::$sql_tbl[$this->sPrimaryTable]." WHERE productcode = '$sSKU'");
    }

    protected function  updateProduct($aProduct) {
        //ЕСЛИ [PRODUCT] не существует ИЛИ trim([PRODUCT].clone_parent_product_sku) != '', ТО
        if (empty($aProduct) || $aProduct["clone_parent_product_id"] > 0) {
            $this->BackprocessLogs('trying update cloned or non-existing product . skip...');
            $this->IncFailUpdate();
            return false;
        }

        foreach ($this->arrCloneTableStructure as $sKey => $aSdel)
            if (in_array($aSdel['table'], array('pricing'))) unset($this->arrCloneTableStructure[$sKey]);


        /*
         ИНАЧЕ получить все подчиненные дистрибьюторы дистрибьютора продукта [PRODUCT] --> [Distributors] (получить code дистрибьютора , отобрать всех дистрибьюторов у которых parent_manufacturer_id = manufacturer_id)
	    */
        $classManufacturer = new Manufacturers();

        $aManufacturer = $classManufacturer->getMainufacturersInfo(array($aProduct["manufacturerid"]));
        $aManufacturer = reset($aManufacturer);

        $aChildManufacturers = $classManufacturer->getChildrenManufacturers($aManufacturer["manufacturerid"]);

        /*
         ЦИКЛ по [Distributors]
			сформировать clonedSKU предполагаемого клона по очередному дистрибьютору: [Distributors].code-[PRODUCT].mpn
			если clonedSKU существует в БД, то
				вызвать блок обновления продукта для очередного подчиненного дистрибьютора;
				посчитать успешное обновление
			конец если
		КОНЕЦ ЦИКЛ по [Distributors]
         * */
        if (!empty($aChildManufacturers)) {
            foreach ($aChildManufacturers as $aChildManufacturer) {
                //сформировать clonedSKU предполагаемого клона по очередному дистрибьютору: [Distributors].code-[PRODUCT].mpn
                $sClonedSKU = $this->getClonedSKU($aChildManufacturer["code"], $this->getProductMPN($aProduct["productcode"], $aManufacturer["code"]));

                $aProductSKU = $this->getProductBySKU($sClonedSKU);
                if (isset($aProductSKU) && is_array($aProductSKU) && !empty($aProductSKU)) {
                    //если clonedSKU существует в БД, то

                    //вызвать блок обновления продукта для очередного подчиненного дистрибьютора;
                    $aParamToClone = array();
                    $aParamToClone[$this->sPrimaryKeyFiled] = $aProduct[$this->sPrimaryKeyFiled];
                    $aParamToClone["d_main_sf"] = $aChildManufacturer["d_main_sf"];

                    if ($this->updateClonedProduct($aProduct, $aProductSKU, $aParamToClone)) {
                        //посчитать успешное обновление
                        $this->IncSuccessUpdate();
                    }
                } else {
                    $this->message[] = "SKU $sClonedSKU not found, continue";

                }
            }
        }

        return true;

    }

    public function getManfacturerClass($iManufacurerId = null) {
        if (!is_null($iManufacurerId))
            return new Manufacturers($iManufacurerId);
        else return  new Manufacturer(['manufacturerid' => $this->aPrimaryTableValue['manufacturerid']]);
    }

}