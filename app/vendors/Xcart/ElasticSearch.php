<?php
namespace Xcart;

class ElasticSearch
{
    private $server;
    private $index;
    private $type;
    private $_id;
    private $queryParams = array();
    private $data_json;

    public $hitsCount;
    public $hitsTotal;
    public $curl_info;



    function __construct($elasticConfig = array(),$index = ''){
        $this->server = $elasticConfig["es_url"];
        $this->index = $index;
        //$this->queryParams["min_score"] = $elasticConfig["search_results_minimum_score_value"];
        $this->init();
    }

    private function init()
    {
        $this->setSource();
        $this->queryParams["query"] = [];
        $this->queryParams["query"]["dis_max"] = [];
        $this->queryParams["query"]["dis_max"]["queries"] = [];
        $this->queryParams["query"]["dis_max"]["tie_breaker"] = 0.4;
    }

    public function reinit()
    {
        $this->init();
        $this->hitsCount = null;
        $this->hitsTotal = null;
        $this->curl_info = null;
        $this->data_json = null;
        $this->_id = null;
        $this->queryParams = [];
    }

    public function setSource($sSource = "*._id")
    {
        $this->queryParams["_source"] = $sSource;
    }

    function call($path, $data_json = array()){
        //if (!$this->index) throw new Exception('$this->index needs a value');
        $url = $this->server . '/' . $this->index . '/' . $path;

        $method = $data_json['method'];
        $content = $data_json['content'];
        $this->data_json = json_encode($content);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array ("Accept: application/json"));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->data_json);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result_json = curl_exec($ch);
        $this->curl_info = curl_getinfo($ch);
        curl_close($ch);
        $result = json_decode($result_json, true);

        if (!empty($result["hits"])) {
            $this->hitsCount = count($result["hits"]["hits"]);
            $this->hitsTotal = $result["hits"]["total"];
        }

        return $result;
    }

    public function setDisMaxBoost($boost)
    {
        $this->queryParams["query"]["dis_max"]['boost'] = $boost;
    }

    public function setQueryParamsDefault($sQuery)
    {
        $query = /** @lang JSON */ <<<JSON
{
    "query_string": {
        "fields": [
         "productname.productname_original^1.5",
         "productname.title_tag^1.5",
         "productname.seo_productname^1.5",
         "productname.seo_h2^1.5",
         "sku",
         "upc",
         "brand.brand_original^0.5",
         "description.description_original",
         "description.seo_description"
        ],
        "analyzer":  "english",
        "query": ""
    }
}
JSON;
        $query = json_decode($query, true);

        $query["query_string"]["query"] = $sQuery;
        $this->queryParams["query"]["dis_max"]["queries"][] = $query;

        $query = /** @lang JSON */ <<<JSON
{
    "query_string": {
        "analyzer": "snowball",
        "fields": [
             "productname.productname^1.5",
             "productname.title_tag^1.5",
             "productname.seo_productname^1.5",
             "productname.seo_h2^1.5",
             "sku",
             "upc",
             "brand.brand^0.5",
             "description.description",
             "description.seo_description"
        ],
        "query": ""
    }
}
JSON;
        $query = json_decode($query, true);

        $query["query_string"]["query"] = $sQuery;
        $this->queryParams["query"]["dis_max"]["queries"][] = $query;

        $query = /** @lang JSON */ <<<JSON
{
    "multi_match": {
        "analyzer": "snowball",
        "boost": 0.5,
        "fields": [
            "description.seo_description",
            "description.description_original",
            "productname.productname_original" ,
            "productname.title_tag" ,
            "productname.seo_productname" ,
            "productname.seo_h2"
        ],
        "query": "",
        "slop": 3,
        "type": "phrase"
    }
}
JSON;
        $query = json_decode($query, true);

        $query["multi_match"]["query"] = $sQuery;
        $this->queryParams["query"]["dis_max"]["queries"][] = $query;
    }

    public function getQuery()
    {
        return  $this->queryParams["query"];
    }

    public function setQueryParams($sQuery, $aDismax = array()){
        if (empty($aDismax)) {
            $this->setQueryParamsDefault($sQuery);
        } else {
            foreach ($aDismax as $oDismax) {
                $this->queryParams["query"]["dis_max"]["queries"][] = $oDismax;
            }
        }
    }

    private function escapeReservedCharacters ($sString) {
        return addcslashes($sString,'#@&<>~.?+*|{}[]()\\"');
    }

    public function getQuerySimilarProductsBrands ($sExcludeBrand = '') {
        $Similar_products_other_brands =
            [
                'filtered' => [
                    'query' => [
                        'more_like_this' => [
                            'fields' => ['productname'],
                            'analyzer' => 'snowball',
                            'docs' => [[
                                           '_index' => $this->index,
                                           '_type' => $this->type,
                                           '_id' => $this->_id
                                       ]],
                            'min_term_freq' => 1,
                            'max_query_terms' => 240
                        ]
                    ],

                ]
            ];
        if (!empty($sExcludeBrand)) {
            $Similar_products_other_brands['filtered']['filter'] = [
                'bool' => [
                    'must' => [],
                    'should' => [],
                    'must_not' => [
                        'regexp' => [
                            'brand' => '.*'.$this->escapeReservedCharacters($sExcludeBrand).'.*'
                        ]
                    ]
                ]
            ];
        }

        return $Similar_products_other_brands;
    }

    public function setSearchQuery ($aQuery = array()) {
        $this->queryParams['query'] = $aQuery;
    }

    public function setQueryParam ($aQuery = array()) {
        $this->queryParams = $aQuery;
    }

    public function setMinScore($sMinScore){
        $this->queryParams["min_score"] = $sMinScore;
    }

    public function setType($sType){
        $this->type = $sType;
    }

    public function setProductId($iProductid){
        $this->_id = $iProductid;
    }

    public function setSize($iSize = 10) {
        $this->queryParams['size'] = $iSize;
    }

    public function setFrom($iFrom = 0) {
        $this->queryParams['from'] = $iFrom;
    }

    public function setFilterTerms($aFilterTerm){
        $this->queryParams["filter"]["terms"]["_id"] = $aFilterTerm;
    }

    //curl -X PUT http://localhost:9200/{INDEX}/
    function create(){
        $this->call(NULL, array('method' => 'PUT'));
    }
    //curl -X DELETE http://localhost:9200/{INDEX}/
    function delete($id=""){
        return $this->call($this->type. '/'.$id, array('method' => 'DELETE'));
    }
    //curl -X GET http://localhost:9200/{INDEX}/_status
    function status(){
        return $this->call('_status');
    }
    //curl -X GET http://localhost:9200/{INDEX}/{TYPE}/_count -d {matchAll:{}}
    function count(){
        return $this->call($this->type . '/_count', array('method' => 'GET', 'content' => '{ matchAll:{} }'));
    }
    //curl -X PUT http://localhost:9200/{INDEX}/{TYPE}/_mapping -d ...
    function map($data){
        return $this->call($this->type . '/_mapping', array('method' => 'PUT', 'content' => $data));
    }
    //curl -X PUT http://localhost:9200/{INDEX}/{TYPE}/{ID} -d ...
    function add($id){
        return $this->call($this->type . '/' . $id, array('method' => 'POST', 'content' => $this->queryParams));
    }
    //curl -X GET http://localhost:9200/{INDEX}/{TYPE}/_search?q= ...
    function query($q = array()){
        return $this->call($this->type . '/_search?' . http_build_query($q), array('method' => 'POST', 'content' => $this->queryParams));
    }
}