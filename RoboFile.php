<?php


/**
 * This is project's console commands configuration for Robo task runner.
 *
 * @see http://robo.li/
 */
class RoboFile extends \Robo\Tasks {

    const WEBSERVICE_SERVER_URL = 'https://pcm-prd.e-mabenet.com';

    const WEBSERVICE_URL = 'https://pcm-prd.e-mabenet.com:2222/mabews/v2/Canada/products?currentPage=%d&pageSize=%d&fields=%s&COUNTRY=Canada&lang=%s_CA';

    const LOCAL_DATA_FILE = 'WebServiceData-%s-%s.json.txt';

    const WEBSERVICE_DATA_TYPE_PRODUCTS = 'Products';

    const WEBSERVICE_DATA_TYPE_ATTRIBUTES = 'Attributes';

    const WEBSERVICE_DATA_TYPE_IMAGES = 'Images';

    const WEBSERVICE_DATA_TYPE_DOCUMENTS = 'Documents';

    const WEBSERVICE_DATA_TYPE_HIGHLIGHTS = 'Highlights';

    const WEBSERVICE_DATA_TYPES = [
        self::WEBSERVICE_DATA_TYPE_PRODUCTS => [
            'fields' => 'PRODUCTOS',
            'dbTableName' => 'cafe_products_%s',
            'properties' => [],
        ],
        self::WEBSERVICE_DATA_TYPE_ATTRIBUTES => [
            'fields' => 'ATRIBUTOS',
            'dbTableName' => 'cafe_products_attributes_%s',
            'properties' => ['classifications'],
        ],
        self::WEBSERVICE_DATA_TYPE_IMAGES => [
            'fields' => 'IMAGENES',
            'dbTableName' => 'cafe_products_images_%s',
            'properties' => ['images', 'diagrams'],
        ],
        self::WEBSERVICE_DATA_TYPE_DOCUMENTS => [
            'fields' => 'MANUALES',
            'dbTableName' => 'cafe_products_documents_%s',
            'properties' => ['manuals', 'embeddedManual', 'installationManual'],
        ],
        self::WEBSERVICE_DATA_TYPE_HIGHLIGHTS => [
            'fields' => 'HIGHLIGHTS',
            'dbTableName' => 'cafe_products_highlights_%s',
            'properties' => [],
        ],
    ];

    const MANUAL_TYPES = [
        'en' => [
            'user-guide' => 'Use and Care Manual',
            'user-install-guide' => 'Use and Care / Installation Manual',
            'user-warranty-guide' => 'Use and Care / Warranty Manual',
            'install-guide' => 'Installation Instructions',
            'install-warranty-guide' => 'Warranty / Installation Manual',
            'warranty-guide' => 'Warranty',
            'timer-guide' => 'Timer Guide',
            'energuide' => 'Energy Guide',
            'bracket-guide' => 'Bracket',
            'GE-fits' => 'GEFits',
            'quick-guide' => 'Quick Guide',
            'combo' => 'Combo',
        ],
        'fr' => [
            'manuel-utilisateur' => 'Use and Care Manual (French)',
            'manuel-util-installation' => 'Use and Care / Installation Manual (French)',
            'manuel-util-garantie' => 'UseAndCare/Warranty_French',
            'manuel-installation' => 'Installation_French',
            'manuel-inst-garantie' => 'Warranty/Installation_French',
            'garantie' => 'Warranty_French',
            'guide-minuterie' => 'TimerGuide_French',
            'etiq-energuide' => 'EnerGuide_French',
            'anti-bascule' => 'Bracket_French',
            'programme-GE-fits' => 'GEFits_French',
            'guide-rapide' => 'QuickGuide_French',
            'combinaison' => 'Combo_French',
        ],
    ];

    const CLAIM_KEYS = [
        'ADA Compliant' => 'ada-compliant',
        'Energy Star' => 'energy-star',
        'Energuide Rating' => 'energy-guide',
        'EnerGuide Rating (kWh/year)' => 'energy-guide',
        'Made in America' => 'made-in-america',
    ];

    /**
     * The location directory that we store webervice json files for further
     * processing
     */
    protected $local_data_directory;

    /**
     * The location directory for storing webervice images & documents files
     */
    protected $local_media_directory;

    public function __construct() {
        try {
            $this->local_data_directory = 'data/webservice';
            $this->local_data_directory = rtrim($this->local_data_directory, '/\\');
            if (!is_dir($this->local_data_directory)) {
                if (!mkdir($this->local_data_directory, 0777, TRUE)) {
                    throw new Exception('Error creating directory to store Web service Data!');
                }
            }

            $this->local_media_directory = 'media';
            $this->local_media_directory = rtrim($this->local_media_directory, '/\\');
            if (!is_dir($this->local_media_directory)) {
                if (!mkdir($this->local_media_directory, 0777, TRUE)) {
                    throw new Exception('Error creating directory to store Images & Manuals!');
                }
            }
            //file_save_htaccess($this->local_media_directory, TRUE);   // the files should be accessible by users
        } catch (\Exception $e) {
            $this->local_data_directory = FALSE;
        }
    }


    private function connectDatabase() {
        $servername = "127.0.0.1:8889";
        $username = "root";
        $password = "rootpw";
        $db_name = 'pcm_data';

        try {
            $conn = new PDO("mysql:host=$servername;dbname=$db_name", $username, $password);
            // set the PDO error mode to exception
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $conn;
        } catch (PDOException $e) {
            echo "Connection failed: " . $e->getMessage();
        }
    }

    public function test() {
        $dataTypes = [self::WEBSERVICE_DATA_TYPE_PRODUCTS];
        $languages = ['en', 'fr'];

        $this->downloadDataFromRemoteWebService($dataTypes, $languages, 100);
    }

    public function importProducts($datatype, $generate_nodes, $download_from_webservice, $load_into_db, $initialize_db_table) {
        set_time_limit(0);
        ini_set('display_errors', 1);

        ob_end_flush();
        ob_start();

//    echo "$datatype, $generate_nodes, $download_from_webservice, $load_into_db, $initialize_db_table";
//    exit;

        $this->loadProductsDataIntoDatabase(strtolower($datatype), $download_from_webservice, $load_into_db, $initialize_db_table);
        if ($generate_nodes) {
            $this->createProductNodes();
        }
    }


    private function loadProductsDataIntoDatabase($datatype = 'files', $download_from_server = false, $load_into_db = true, $initialize_db_table = true) {
        $datatype = strtolower($datatype);
        $datatype_all = ($datatype == 'all');

        if ($datatype_all || ($datatype == 'products')) {
            $this->importProductsData($download_from_server, $load_into_db, $initialize_db_table);
        }
        if ($datatype_all || ($datatype == 'attributes')) {
            $this->importProductAttributes($download_from_server, $load_into_db, $initialize_db_table);
        }
        if ($datatype_all || ($datatype == 'images')) {
            $this->importProductRelatedFiles(self::WEBSERVICE_DATA_TYPE_IMAGES, $download_from_server, $load_into_db, $initialize_db_table);
        }
        if ($datatype_all || ($datatype == 'documents')) {
            $this->importProductRelatedFiles(self::WEBSERVICE_DATA_TYPE_DOCUMENTS, $download_from_server, $load_into_db, $initialize_db_table);
        }
        if ($datatype_all || ($datatype == 'highlights')) {
            $this->importProductsHighlights($download_from_server, $load_into_db, $initialize_db_table);
        }

        if ($datatype == 'files') {
            $this->downloadProductRelatedFiles();
        }
    }


    private function createProductNodes() {
        try {
            $connection = $this->connectDatabase();

            $products_reference_table_name = $this->getDatabaseTableName(self::WEBSERVICE_DATA_TYPE_PRODUCTS, 'en');

            $products_total = 0;
            $products_with_sku = 0;
            $products_imported = 0;

            // get list of products categories (Taxonomy terms)
            $taxonomyTermStorage = \Drupal::entityManager()->getStorage('taxonomy_term');
            $vid = 'product_category';
            $terms = $taxonomyTermStorage->loadTree($vid);
            $term_data = [];
            if (!empty($terms)) {
                foreach ($terms as $term) {
                    $category_id_field = $taxonomyTermStorage->load($term->tid)->get('field_prodcat_category_id');

                    if (!empty($category_id_field)) {
                        $category_id_field_value = $category_id_field->getValue();
                        if (!empty($category_id_field_value)) {
                            $category_id = $category_id_field_value[0]['value'];
                            if (!empty($category_id)) {
                                $term_data[] = array(
                                    'id' => $term->tid,
                                    'name' => $term->name,
                                    'category_id' => intval($category_id),
                                    'nodes' => [],
                                );
                            }
                        }
                    }
                }
            }
            unset($taxonomyTermStorage, $terms);

            //print_r($term_data);
            foreach ($term_data as $k => $term) {
                $category_id = $term['category_id'];
                $results = $connection->query(sprintf("SELECT * FROM {%s} WHERE (category = %d) ORDER BY sku", $products_reference_table_name, $category_id));
                if (!empty($results)) {
                    foreach ($results as $product) {
                        $products_total ++;

                        // Convert stdClass to PHP Associative Array
                        $product = json_decode(json_encode($product), true);
                        if (!empty($product)) {
                            $product['dimensions'] = $this->getProductDimensions($product);
                            $product['features'] = $this->getProductFeatures($product);
                            $product['claims'] = $this->getProductClaims($product);
                            $product['images'] = $this->getProductRelatedFiles($product, self::WEBSERVICE_DATA_TYPE_IMAGES, 'en', true);
                            $product['highlights'] = $this->getProductHighlights($product, 'en');
                            $product['documents'] = $this->getProductRelatedFiles($product, self::WEBSERVICE_DATA_TYPE_DOCUMENTS, 'en');

                            $product_french = $this->getProductFrenchTranslation($product);
                            if (!empty($product_french)) {
                                $product_french['dimensions'] = $this->getProductDimensions($product_french);
                                $product_french['features'] = $this->getProductFeatures($product_french);
                                $product_french['claims'] = $this->getProductClaims($product_french);
                                $product_french['images'] = $product['images'];   // $this->getProductRelatedFiles($product_french, self::WEBSERVICE_DATA_TYPE_IMAGES, 'fr');
                                $product_french['highlights'] = $this->getProductHighlights($product_french, 'fr');
                                $product_french['documents'] = $this->getProductRelatedFiles($product_french, self::WEBSERVICE_DATA_TYPE_DOCUMENTS, 'fr');
                            }

                            $nid = $this->saveProductAsNode($product, $product_french, intval($term['id']), 1);
                            if ($nid > 0) {
                                if ($nid != intval($product['nid'])) {
                                    $connection->update(sprintf('{%s}', $products_reference_table_name))->fields(array('nid' => $nid))->condition('sku', $product['sku'])->execute();
                                }

                                $term_data[$k]['nodes'][] = $nid;

                                $products_imported ++;

                                //if ($products_imported > 10) { exit; }
                            }
                        }
                    }
                    unset($results);
                }
            }
        }
        catch (Exception $e) {
            $this->printInfo('Caught exception: ',  $e->getMessage(), "\n");
        }

        $this->printInfo(sprintf("<br><hr>Total Products: %d <br>Imported Products (Nodes): %d", $products_total, $products_imported));
        return $products_imported;
    }

    private function getWebServiceURL($dataType, $lang = 'en', $pageNo = 0, $pageSize = 100) {
        return sprintf(self::WEBSERVICE_URL, $pageNo, $pageSize, self::WEBSERVICE_DATA_TYPES[$dataType]['fields'], $lang);
    }

    private function getLocalDataFilePath($dataType, $lang = 'en') {
        if (is_dir($this->local_data_directory)) {
            return $this->local_data_directory . '/' . sprintf(self::LOCAL_DATA_FILE, $dataType, $lang);
        }
    }

    private function getDatabaseTableName($dataType, $lang = 'en') {
        if (!empty(self::WEBSERVICE_DATA_TYPES[$dataType])) {
            return sprintf(self::WEBSERVICE_DATA_TYPES[$dataType]['dbTableName'], $lang);
        }
    }

    private function getMainProperties($dataType, $lang = 'en') {
        if (!empty(self::WEBSERVICE_DATA_TYPES[$dataType]) && !empty(self::WEBSERVICE_DATA_TYPES[$dataType]['properties'])) {
            return self::WEBSERVICE_DATA_TYPES[$dataType]['properties'];
        }
        return [];
    }

    private function getLocalFileDirectory($dataType, $lang = 'en') {
        $dir = $this->local_media_directory . '/' . strtolower($dataType);
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0777, true)) {
                throw new Exception('Error creating directory %s!', $dir);
            }
            //file_save_htaccess($dir, TRUE);
        }
        return $dir;
    }


    private function printInfo($str = '', $addNewLine = true) {
        echo $str . ($addNewLine ? "<br>\n" : "");
    }

    private function initDatabaseTables($dataTypes, $languages = ['en']) {
        $connection = $this->connectDatabase();

        $this->printInfo();

        foreach($dataTypes as $dataType) {
            foreach($languages as $lang) {
                $db_table_name = $this->getDatabaseTableName($dataType, $lang);

                $this->printInfo(sprintf('Initializing db table %s', $db_table_name));

                $connection->query(sprintf('TRUNCATE TABLE {%s}', $db_table_name));
            }
        }
    }

    private function deleteUnwantedData($dataTypes, $languages = ['en']) {
        $connection = $this->connectDatabase();

        $this->printInfo();

        $products_reference_table_name = $this->getDatabaseTableName(self::WEBSERVICE_DATA_TYPE_PRODUCTS, 'en');

        $result = $connection->query(sprintf('SELECT count(sku) as NumberOfProducts FROM {%s}', $products_reference_table_name));
        if (!empty($result)) {
            $row = $result->fetchAssoc();
            if (!empty($row)) {
                if ($row['NumberOfProducts'] > 0) {
                    foreach($dataTypes as $dataType) {
                        foreach($languages as $lang) {
                            $db_table_name = $this->getDatabaseTableName($dataType, $lang);

                            if ($db_table_name != $products_reference_table_name) {
                                $db_table_name_tmp = $db_table_name . '_tmp';
                                $db_table_name_backup = $db_table_name . '_backup';

                                $connection->query(sprintf('DROP TABLE IF EXISTS {%s}, {%s};', $db_table_name_tmp, $db_table_name_backup));
                                $connection->query(sprintf('CREATE TABLE {%s} LIKE {%s};', $db_table_name_tmp, $db_table_name));
                                $connection->query(sprintf('INSERT INTO {%s} SELECT f.* FROM {%s} as f INNER JOIN {%s} as e ON (f.sku = e.sku);', $db_table_name_tmp, $db_table_name, $products_reference_table_name));
                                $connection->query(sprintf('RENAME TABLE {%s} TO {%s}, {%s} TO {%s};', $db_table_name, $db_table_name_backup, $db_table_name_tmp, $db_table_name));

                                $this->printInfo(sprintf('Deleted extra unwanted records from database table %s', $db_table_name));
                            }
                        }
                    }
                }
                else {
                    $this->printInfo(sprintf('Warning: There is no product record in database table %s', $products_reference_table_name));
                }
            }
        }
    }

    private function getDataFromLocalFile($dataType, $lang) {
        $products = [];

        try {
            $localfile = $this->getLocalDataFilePath($dataType, $lang);
            if (is_readable($localfile)) {

                $this->printInfo(sprintf('<br>Reading local file: %s', $localfile));

                $dataContent = file_get_contents($localfile);   // server return empty products array  as  "{ "Products" : [ ] }"
                if (!empty($dataContent) && (strlen($dataContent) > 50)) {
                    $dataContent = '[' . $dataContent . ']';

                    $this->printInfo(sprintf('Loaded data into memory (%s bytes).', number_format(strlen($dataContent))));

                    $products = json_decode($dataContent, true);
                    if (!empty($products) && is_array($products)) {
                        $this->printInfo(sprintf('Decoded %s products.', number_format(count($products))));
                    }
                }
                unset($dataContent);
            }
            else {
                throw new Exception('Local Data File (%s) was NOT found!' . $localfile);
            }
        }
        catch (Exception $e) {
            $this->printInfo('getDataFromLocalFile - Exception: ',  $e->getMessage(), "\n");
        }

        return $products;
    }

    private function downloadDataFromRemoteWebService($dataTypes, $languages = ['en'], $pageSize = 100) {
        try {
            foreach($dataTypes as $dataType) {
                foreach($languages as $lang) {
                    $localfile = $this->getLocalDataFilePath($dataType, $lang);
                    if (file_exists($localfile)) { unlink($localfile); }

                    $this->printInfo(sprintf('Downloading data (Fields: %s, Language: %s, PageSize: %d) ', $dataType, $lang, $pageSize));
                    $this->printInfo(sprintf('Local File: %s', $localfile));
                    $this->printInfo(sprintf('Started at %s', date('l jS \of F Y h:i:s A')));

                    $pageNo = 0;
                    while(true)  {
                        $remoteURL = $this->getWebServiceURL($dataType, $lang, $pageNo, $pageSize);
                        $this->printInfo($remoteURL);

                        $dataContent = file_get_contents($remoteURL);
//                        $ch = curl_init();
//                        curl_setopt($ch, CURLOPT_URL, $remoteURL);
//                        curl_setopt($ch, CURLOPT_FAILONERROR, true); // Required for HTTP error codes to be reported via our call to curl_error($ch)
//                        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
////...
//                        $dataContent = curl_exec($ch);
//                        if (curl_errno($ch)) {
//                            $error_msg = curl_error($ch);
//                            print $error_msg;
//                        }
//                        curl_close($ch);
                        if (!empty($dataContent)) {
                            preg_match('/^\{\s*"products"\s*:\s*\[(.*)\]\s*\}$/msU', $dataContent, $matches);
                            $dataContent = empty($matches[1]) ? '' : $matches[1];
                            unset($matches);

                            if (empty($dataContent) || (strlen($dataContent) < 50)) { break; }   // server return empty products array  as  "{ "Products" : [ ] }"

                            if (file_put_contents($localfile, ($pageNo > 0 ? ',': '') . $dataContent, FILE_APPEND) === FALSE) {
                                $this->printInfo(sprintf('Error saving data int local File: %s', $localfile));
                                exit;
                            }
                        }

                        $pageNo++;
                    }

                    $this->printInfo(sprintf('Completed at %s.<br>The data was stored in %s<br><br>', date('l jS \of F Y h:i:s A'), $localfile));
                }
            }
        }
        catch (Exception $e) {
            $this->printInfo('downloadDataFromRemoteWebService - Exception: ',  $e->getMessage(), "\n");
        }
    }

    private function formatProductData($product) {
        if (!empty($product)) {
//      if (!empty($product['sku']) && !empty($product['category']) && !empty($product['brand']) && !empty($product['modelStatus'])) {
            if (!empty($product['sku']) && !empty($product['category']) && !empty($product['brand'])) {

                $isGRBrand = (strtoupper($product['brand']) == 'GE CAFE');
                //$isCafeModel = in_array(strtoupper($product['modelStatus']), array('Z5', 'Z7'));
                $discontinued = boolval(isset($product['discontinuedModelFlag']) ? intval($product['discontinuedModelFlag']): 0);

                if ($isGRBrand && !$discontinued) {  // && $isCafeModel

                    $product['sku'] = strtoupper($product['sku']);
                    $product['category'] = empty($product['category']) ? 0 : intval($product['category']);

                    $floatFields = ['weight', 'realWeight', 'depth', 'depthInches', 'realDepth', 'realDepthInches', 'height', 'heightInches', 'realHeight', 'realHeightInches', 'length', 'lengthInches', 'realLength', 'realLengthInches'];
                    foreach($floatFields as $fieldName) {
                        $product[$fieldName] = empty($product[$fieldName]) ? 0 : floatval($product[$fieldName]);
                    }

                    $databaseFieldNames = ['sku', 'category', 'upc', 'brand', 'modelStatus', 'country', 'model', 'linkText', 'metatag', 'name', 'siteTitle', 'descriptionMedium', 'descriptionLong', 'keywords', 'realWeight', 'weight', 'depth', 'depthInches', 'height', 'heightInches', 'length', 'lengthInches', 'realDepth', 'realDepthInches', 'realHeight', 'realHeightInches', 'realLength', 'realLengthInches', 'html', 'modelStartDate', 'modelEndDate'];
                    foreach($product as $k => $v) {
                        if (!in_array($k, $databaseFieldNames)) {
                            unset($product[$k]);
                        }
                    }

                    return $product;
                }
            }
        }
    }

    private function importProductsData($download_from_webservice = false, $load_into_db = false, $initialize_db_table = false) {
        $dataTypes = [self::WEBSERVICE_DATA_TYPE_PRODUCTS];
        $languages = ['en', 'fr'];

        try {

            if ($download_from_webservice) {
                $this->downloadDataFromRemoteWebService($dataTypes, $languages, 100);
            }

            if ($initialize_db_table) {
                $this->initDatabaseTables($dataTypes, $languages);
            }

            if ($load_into_db) {
                $connection = $this->connectDatabase();
                foreach($dataTypes as $dataType) {
                    foreach($languages as $lang) {
                        $products = $this->getDataFromLocalFile($dataType, $lang);
                        if (!empty($products)) {
                            $db_table_name = $this->getDatabaseTableName($dataType, $lang);

                            $record_count = 0;
                            foreach($products as $product) {
                                $product = $this->formatProductData($product);
                                if (!empty($product)) {
                                    $connection->upsert($db_table_name)->fields($product)->key('sku')->execute();
                                    $record_count++;
                                }
                            }
                            $this->printInfo(sprintf('Stored %s records in database table %s', number_format($record_count), $db_table_name));
                        }
                        unset($products);
                    }
                }
                $this->deleteUnwantedData($dataTypes, $languages);
            }
        }
        catch (Exception $e) {
            $this->printInfo('Caught exception: ',  $e->getMessage(), "\n");
        }
    }

    private function importProductAttributes($download_from_webservice = false, $load_into_db = false, $initialize_db_table = false) {
        $dataTypes = [self::WEBSERVICE_DATA_TYPE_ATTRIBUTES];
        $languages = ['en', 'fr'];

        try {

            if ($download_from_webservice) {
                $this->downloadDataFromRemoteWebService($dataTypes, $languages, 100);
            }

            if ($initialize_db_table) {
                $this->initDatabaseTables($dataTypes, $languages);
            }

            if ($load_into_db) {
                $connection = $this->connectDatabase();
                foreach($dataTypes as $dataType) {
                    foreach($languages as $lang) {
                        $products = $this->getDataFromLocalFile($dataType, $lang);
                        if (!empty($products)) {
                            $db_table_name = $this->getDatabaseTableName($dataType, $lang);
                            $product_table_name = $this->getDatabaseTableName(self::WEBSERVICE_DATA_TYPE_PRODUCTS, $lang);

                            $record_count = 0;
                            foreach($products as $product) {
                                if (!empty($product['sku'])) {
                                    $discontinued = boolval(isset($product['discontinuedModelFlag']) ? intval($product['discontinuedModelFlag']): 0);
                                    if (!$discontinued) {
                                        $product_features = [];

                                        $product['sku'] = strtoupper($product['sku']);

                                        foreach($this->getMainProperties($dataType, $lang) as $property) {
                                            if (!empty($product[$property]) && is_array($product[$property])) {
                                                foreach($product[$property] as $group) {
                                                    if (!empty($group['features']) && is_array($group['features'])) {
                                                        foreach($group['features'] as $item) {
                                                            if (!empty($item['name'])) {

                                                                $value = '';
                                                                if (!empty($item['featureValues'])) {
                                                                    if (is_array($item['featureValues'])) {
                                                                        $values = [];
                                                                        foreach($item['featureValues'] as $v) {
                                                                            if (!empty($v['value'])) {
                                                                                $values[] = $v['value'];
                                                                            }
                                                                        }
                                                                        if (!empty($values)) {
                                                                            $value = (count($values) == 1) ? $values[0] : json_encode($values);
                                                                        }
                                                                    }
                                                                    else {
                                                                        $value = $item['featureValues'];
                                                                    }
                                                                }

                                                                $data = array(
                                                                    'sku' => $product['sku'],
                                                                    'name' => $item['name'],
                                                                    'value' => $value,
                                                                );
                                                                $connection->insert($db_table_name)->fields($data)->execute();

                                                                $product_features[$item['name']] = $value;

                                                                $record_count++;
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }

                                        $connection->update($product_table_name)->fields(array('features' => (empty($product_features) ? '' : json_encode($product_features))))->condition('sku', $product['sku'])->execute();
                                    }
                                }
                            }
                            $this->printInfo(sprintf('Stored %s records in database table %s', number_format($record_count), $db_table_name));
                        }
                        unset($products);
                    }
                }
                $this->deleteUnwantedData($dataTypes, $languages);
            }
        }
        catch (Exception $e) {
            $this->printInfo('Caught exception: ',  $e->getMessage(), "\n");
        }
    }


    private function downloadProductRelatedFiles($sku = '') {
        $dataTypes = [self::WEBSERVICE_DATA_TYPE_IMAGES, self::WEBSERVICE_DATA_TYPE_DOCUMENTS, self::WEBSERVICE_DATA_TYPE_HIGHLIGHTS];
        $languages = ['en', 'fr'];

        try {
            $where = ' WHERE (downloaded = 0) ' . (empty($sku) ? '' : sprintf(" AND (sku = '%s')", $sku));

            $connection = $this->connectDatabase();
            foreach($dataTypes as $dataType) {
                foreach($languages as $lang) {

                    $db_table_name = $this->getDatabaseTableName($dataType, $lang);
                    $local_Directory = $this->getLocalFileDirectory($dataType, $lang) . '/';

                    $record_count = 0;

                    $result = $connection->query(sprintf('SELECT * FROM {%s} %s;', $db_table_name, $where));
                    while ($row = $result->fetchAssoc()) {
                        $name = empty($row['name']) ? '': $row['name'];
                        $url = empty($row['url']) ? '': $row['url'];

                        if ($dataType == self::WEBSERVICE_DATA_TYPE_HIGHLIGHTS) {
                            $url = empty($row['imageUrl']) ? '': $row['imageUrl'];
                            if ($url) {
                                $name = $this->extractFilenameFromURL($url);
                            }
                        }

                        if ($name && $url) {
                            $url = SELF::WEBSERVICE_SERVER_URL . '/' . ltrim($url, '/');

                            $localFilename = $local_Directory . ltrim($name, '/');
                            if (!is_readable($localFilename)) {
                                if ($handle = fopen($url, "rb")) {
                                    if (file_put_contents($localFilename, $handle)) {
                                        $record_count++;

                                        if ($dataType == self::WEBSERVICE_DATA_TYPE_HIGHLIGHTS) {
                                            $connection->query(sprintf("UPDATE {%s} SET downloaded = 1 WHERE (sku = '%s') AND (imageUrl = '%s') LIMIT 1;", $db_table_name, $row['sku'], $row['imageUrl']));
                                        }
                                        else {
                                            $connection->query(sprintf("UPDATE {%s} SET downloaded = 1 WHERE (sku = '%s') AND (name = '%s') LIMIT 1;", $db_table_name, $row['sku'], $row['name']));
                                        }
                                    }
                                    fclose($handle);
                                }
                            }
                        }
                    }
                    $this->printInfo(sprintf('%s (%s) %d Files Downloaded in %s', $dataType, $lang, $record_count, $local_Directory));
                }
            }
        }
        catch (Exception $e) {
            $this->printInfo('Caught exception: ',  $e->getMessage(), "\n");
        }
    }

    private function importProductRelatedFiles($datatype = self::WEBSERVICE_DATA_TYPE_IMAGES, $download_from_webservice = false, $load_into_db = false, $initialize_db_table = false) {
        $dataTypes = [$datatype];
        $languages = ['en'];

        try {

            if ($download_from_webservice) {
                $this->downloadDataFromRemoteWebService($dataTypes, $languages, 100);
            }

            if ($initialize_db_table) {
                $this->initDatabaseTables($dataTypes, $languages);
            }

            if ($load_into_db) {
                $connection = $this->connectDatabase();
                foreach($dataTypes as $dataType) {
                    foreach($languages as $lang) {
                        $products = $this->getDataFromLocalFile($dataType, $lang);
                        if (!empty($products)) {
                            $db_table_name = $this->getDatabaseTableName($dataType, $lang);

                            $record_count = 0;
                            foreach($products as $product) {
                                if (!empty($product['sku'])) {

                                    $discontinued = boolval(isset($product['discontinuedModelFlag']) ? intval($product['discontinuedModelFlag']): 0);
                                    if (!$discontinued) {

                                        foreach($this->getMainProperties($dataType, $lang) as $property) {
                                            if (!empty($product[$property]) && is_array($product[$property])) {

                                                // embeddedManual, installationManual are NOT array of documents but a single object
                                                if (empty($product[$property]['url'])) {

                                                    foreach($product[$property] as $item) {

                                                        // diagram has the URL in the name property!!!
                                                        if ($property == 'diagrams') {
                                                            if (empty($item['url']) && !empty($item['name']) && (substr($item['name'], 0, 4) == 'http')) {
                                                                $item['url'] = $item['name'];
                                                            }
                                                        }

                                                        $data = array(
                                                            'sku' => $product['sku'],
                                                            'type' => $property,
                                                            'label' => empty($item['label']) ? '' : $item['label'],
                                                            'name' => empty($item['name']) ? '' : $item['name'],
                                                            'url' => empty($item['url']) ? '' : $item['url'],
                                                        );

                                                        $connection->insert($db_table_name)->fields($data)->execute();
                                                        $record_count++;
                                                    }
                                                }
                                                else {
                                                    $item = $product[$property];

                                                    $data = array(
                                                        'sku' => $product['sku'],
                                                        'type' => $property,
                                                        'label' => empty($item['label']) ? '' : $item['label'],
                                                        'name' => empty($item['name']) ? '' : $item['name'],
                                                        'url' => empty($item['url']) ? '' : $item['url'],
                                                    );

                                                    $connection->insert($db_table_name)->fields($data)->execute();
                                                    $record_count++;
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                            $this->printInfo(sprintf('Stored %s records in database table %s', number_format($record_count), $db_table_name));
                        }
                        unset($products);
                    }
                }
                $this->deleteUnwantedData($dataTypes, $languages);

                if ($datatype == self::WEBSERVICE_DATA_TYPE_DOCUMENTS) {
                    $this->prepareFrenchManuals();  // Moves the french documents records into table '{cafe_products_manuals_fr}'
                }
            }
        }
        catch (Exception $e) {
            $this->printInfo('Caught exception: ',  $e->getMessage(), "\n");
        }
    }

    private function prepareFrenchManuals() {
        $FrenchManualTypes = ['manuel-utilisateur.pdf', 'manuel-util-installation.pdf', 'manuel-util-garantie.pdf', 'manuel-installation.pdf', 'manuel-inst-garantie.pdf', 'garantie.pdf', 'guide-minuterie.pdf', 'etiq-energuide.pdf', 'anti-bascule.pdf', 'programme-ge-fits.pdf', 'guide-rapide.pdf', 'combinaison.pdf'];

        $regExStr = '';
        foreach($FrenchManualTypes as $v)  {
            $regExStr .= ($regExStr ? '|' : '') . strtolower($v);
        }

        $english_manauls_table_name = $this->getDatabaseTableName(self::WEBSERVICE_DATA_TYPE_DOCUMENTS, 'en');
        $french_manauls_table_name = $this->getDatabaseTableName(self::WEBSERVICE_DATA_TYPE_DOCUMENTS, 'fr');

        $connection = $this->connectDatabase();
        $connection->query(sprintf("TRUNCATE TABLE {%s};", $french_manauls_table_name));
        $connection->query(sprintf("INSERT IGNORE INTO {%s} SELECT * FROM {%s} WHERE LOWER(name) REGEXP '(%s)';", $french_manauls_table_name, $english_manauls_table_name, $regExStr));
        $connection->query(sprintf("DELETE FROM {%s} WHERE LOWER(name) REGEXP '(%s)';", $english_manauls_table_name, $regExStr));
    }

    private function importProductsHighlights($download_from_webservice = false, $load_into_db = false, $initialize_db_table = false) {
        $dataTypes = [self::WEBSERVICE_DATA_TYPE_HIGHLIGHTS];
        $languages = ['en', 'fr'];

        try {

            if ($download_from_webservice) {
                $this->downloadDataFromRemoteWebService($dataTypes, $languages, 100);
            }

            if ($initialize_db_table) {
                $this->initDatabaseTables($dataTypes, $languages);
            }

            if ($load_into_db) {
                $connection = $this->connectDatabase();
                foreach($dataTypes as $dataType) {
                    foreach($languages as $lang) {
                        $products = $this->getDataFromLocalFile($dataType, $lang);
                        if (!empty($products)) {
                            $db_table_name = $this->getDatabaseTableName($dataType, $lang);

                            $record_count = 0;
                            foreach($products as $product) {
                                if (!empty($product['sku'])) {
                                    $discontinued = boolval(isset($product['discontinuedModelFlag']) ? intval($product['discontinuedModelFlag']): 0);

                                    if (!$discontinued) {
                                        if (!empty($product['highlights']) && is_array($product['highlights'])) {
                                            foreach($product['highlights'] as $item) {
                                                $data = array(
                                                    'sku' => $product['sku'],
                                                    'title' => empty($item['title']) ? '' : $item['title'],
                                                    'description' => empty($item['description']) ? '' : $item['description'],
                                                    'imageUrl' => empty($item['imageUrl']) ? '' : $item['imageUrl'],
                                                    'videoUrl' => empty($item['videoUrl']) ? '' : $item['videoUrl'],
                                                );
                                                $connection->insert($db_table_name)->fields($data)->execute();

                                                $record_count++;
                                            }
                                        }
                                    }
                                }
                            }
                            $this->printInfo(sprintf('Stored %s records in database table %s', number_format($record_count), $db_table_name));
                        }
                        unset($products);
                    }
                }
                $this->deleteUnwantedData($dataTypes, $languages);
            }
        }
        catch (Exception $e) {
            $this->printInfo('Caught exception: ',  $e->getMessage(), "\n");
        }
    }

    private function getProductFrenchTranslation($product) {
        $product_french = [];
        if (!empty($product)) {
            $connection = $this->connectDatabase();

            $products_reference_table_name = $this->getDatabaseTableName(self::WEBSERVICE_DATA_TYPE_PRODUCTS, 'fr');
            $results = $connection->query(sprintf("SELECT * FROM {%s} WHERE (sku = '%s') LIMIT 1", $products_reference_table_name, $product['sku']));
            if (!empty($results)) {
                foreach ($results as $row) {
                    // Convert stdClass to PHP Associative Array
                    $product_french = json_decode(json_encode($row), true);
                    if (!empty($product_french)) {

                        // Replace NULL values with values from English version
                        foreach($product_french as $k => $v) {
                            if (empty($v) && !empty($product[$k])) {
                                $product_french[$k] = $product[$k];
                            }
                        }

                        return $product_french;
                    }
                }
            }
        }
        return $product_french;
    }

    protected function saveProductAsNode($product, $product_french, $term_id, $enforce_update = 0) {
        try {
            if (!empty($product['sku'])) {

                $node_type = 'product';
                if (\Drupal::entityTypeManager()->getAccessControlHandler('node')->createAccess($node_type)) {

                    $node = null;
                    $inNew = true;
                    $sku = strtoupper($product['sku']);

                    // Find prebiously created drupal node (by nid) if exists (previuosly imported data)
                    $nid = empty($product['nid']) ? 0 : intval($product['nid']);
                    if ($nid > 0) {
                        $node = Node::load($nid);
                        if (!empty($node)) {
                            $inNew = false;
                        }
                    }

                    // If not found by nid, try to find it by sku custom field of nodes (previuosly imported data)
                    if (empty($node)) {
                        $query = \Drupal::service('entity.query')->get('node');
                        $result = $query->condition('type', $node_type)->condition("field_product_import_sku.value", $sku, "=")->execute();
                        if (!empty($result)) {
                            foreach ($result as $nid) {
                                $node = Node::load($nid);
                                if (!empty($node)) {
                                    $inNew = false;
                                    break;
                                }
                            }
                        }
                    }

                    if (!empty($node) && ($node->getType() != $node_type)) {
                        throw new Exception(sprintf('Invalid existing Node type! (nid: %d) - Importing Product with SKU: %s', $node->id(), $sku));
                    }

                    if ($inNew) {
                        $node = Node::create(['type' => $node_type]);
                    }

                    if ($enforce_update || $inNew) {
                        if (!empty($node) && ($node->getType() == $node_type)) {
                            $title = trim(empty($product['siteTitle']) ? $product['name'] : $product['siteTitle']);
                            if (!empty($title)) {
                                $title = trim(rtrim(rtrim($title, $sku), '- '));  // remove the SKU from end of title

                                $node->set('langcode', 'en');
                                $node->set('title', $title);
                                $node->set('body', $product['descriptionLong']);

                                // Custom fields / Drupal Entity Model
                                $node->set('field_product_sku', $sku);
                                $node->set('field_product_import_sku', $sku);  // store a backup just in case user changes the original sku
                                $node->set('field_product_description', $product['descriptionMedium']);
                                $node->set('field_product_keywords', $product['keywords']);

                                // User's custom data entry (Not included in the Webservice DataFeed)
                                //$node->set('field_product_price', $product['price']);
                                //$node->set('field_product_value_proposition', empty($product['value_proposition']) ? '' : $product['value_proposition']);

                                // product additional info:  (remove unwanted or redundant data fields)
                                $unwanted_fields = ['nid', 'country', 'siteTitle', 'descriptionMedium', 'descriptionLong', 'keywords',
                                    'weight', 'realWeight', 'depth', 'height', 'length', 'realDepth', 'realHeight', 'realLength',
                                    'realHeightInches', 'realLengthInches', 'realDepthInches', 'heightInches', 'lengthInches','depthInches'
                                ];
                                foreach($unwanted_fields as $k) {
                                    unset($product[$k]);
                                }
                                $node->set('field_product_info', json_encode($product));

                                // link to taxonomy term (product category)
                                $node->set('field_product_group', $term_id);

                                $node->save();
                                $nid = intval($node->id());

                                if (!empty($product_french)) {
                                    $title = trim(empty($product_french['siteTitle']) ? $product_french['name'] : $product_french['siteTitle']);
                                    if (empty($title)) {
                                        $title = $node->get('title');
                                    }

                                    //Creating the translation Spanish in this case
                                    $node_fr = ($node->hasTranslation('fr') ? $node->getTranslation('fr') : $node->addTranslation('fr'));
                                    $node_fr->set('title', $title);
                                    $node_fr->set('body', $product_french['descriptionLong']);
                                    $node_fr->set('field_product_description', $product_french['descriptionMedium']);
                                    $node_fr->set('field_product_keywords', $product_french['keywords']);

                                    // product additional info:  (remove unwanted or redundant data fields)
                                    $unwanted_fields = ['nid', 'country', 'siteTitle', 'descriptionMedium', 'descriptionLong', 'keywords',
                                        'weight', 'realWeight', 'depth', 'height', 'length', 'realDepth', 'realHeight', 'realLength',
                                        'realHeightInches', 'realLengthInches', 'realDepthInches', 'heightInches', 'lengthInches','depthInches'
                                    ];
                                    foreach($unwanted_fields as $k) {
                                        unset($product_french[$k]);
                                    }
                                    $node_fr->set('field_product_info', json_encode($product_french));

                                    $node_fr->save();
                                }

                                $this->printInfo(sprintf("Product %s was %s as Node %d!<br>\n", $sku, ($inNew ? 'created' : 'updated'), $nid));
                                return $nid;
                            }
                        }
                    }

                    unset($result, $node);
                }
            }
        }
        catch (Exception $e) {
            \Drupal::logger('product')->error($e->getMessage());
        }
        return false;
    }

    // Format Product Data Fields
    private function convertKgToLb($weight) {
        return empty($weight) ? 0 : round($weight * 2.2046226218, 2);
    }

    private function getProductDimensions($product) {
        $data = [];
        if (!empty($product)) {
            $data = array(
                'product' => array(
                    'weight' => $product['realWeight'],
                    'height' => $product['realHeight'],
                    'length' => $product['realLength'],
                    'depth' => $product['realDepth'],
                    'weightLB' => $this->convertKgToLb($product['realWeight']),
                    'heightInches' => $product['realHeightInches'],
                    'lengthInches' => $product['realLengthInches'],
                    'depthInches' => $product['realDepthInches'],
//          'dimensions' => sprintf('%0.2f" H x %0.2f" W x %0.2f" D', $product['realHeightInches'], $product['realLengthInches'], $product['realDepthInches']),
                ),
                'package' => array(
                    'weight' => $this->convertKgToLb($product['weight']),
                    'height' => $product['height'],
                    'length' => $product['length'],
                    'depth' => $product['depth'],
                    'weightLB' => $this->convertKgToLb($product['weight']),
                    'heightInches' => $product['heightInches'],
                    'lengthInches' => $product['lengthInches'],
                    'depthInches' => $product['depthInches'],
//          'dimensions' => sprintf('%0.2f" H x %0.2f" W x %0.2f" D', $product['heightInches'], $product['lengthInches'], $product['depthInches']),
                )
            );
        }
        return $data;
    }

    private function getProductFeatures($product) {
        $data = [];
        if (!empty($product)) {
            if (!empty($product['features'])) {
                $data =  json_decode($product['features'], true);
            }
        }
        return $data;
    }

    private function getProductClaims($product){
        $data = [];
        try {
            if (!empty($product) && !empty($product['features'])) {
                $features = (is_array($product['features']) ? $product['features'] : $this->getProductFeatures($product));
                if (!empty($features) && is_array($features)) {
                    foreach(self::CLAIM_KEYS as $k => $v) {
                        if (!empty($features[$k]) && (strtoupper($features[$k]) == 'YES')) {
                            $data[] = $v;
                        }
                    }
                }
            }
        }
        catch (Exception $e) {
            $this->printInfo('Caught exception: ',  $e->getMessage(), "\n");
        }
        return array_unique($data);
    }

    private function getProductRelatedFiles($product, $dataType = self::WEBSERVICE_DATA_TYPE_IMAGES, $lang = 'en', $downloadFileIfNotExists = false) {
        $data = [];
        try {
            $sku = empty($product['sku']) ? false : $product['sku'];
            if (!empty($sku)) {
                $connection = $this->connectDatabase();

                $db_table_name = $this->getDatabaseTableName($dataType, $lang);
                $local_Directory = $this->getLocalFileDirectory($dataType, $lang) . '/';

                $where = sprintf(" WHERE (sku = '%s')", $sku);

                $record_count = 0;
                $result = $connection->query(sprintf('SELECT * FROM {%s} %s;', $db_table_name, $where));
                while ($row = $result->fetchAssoc()) {
                    $filePath = $local_Directory . ltrim($row['name'], '/');
                    $altLabel = $row['label'];

                    if ($dataType == self::WEBSERVICE_DATA_TYPE_DOCUMENTS) {
                        $docKey = '';
                        if (empty($altLabel)) {
                            foreach(self::MANUAL_TYPES[$lang] as $k => $v) {
                                if (strstr($row['name'], '-'.$k.'.pdf') != false) {
                                    $docKey = $k;
                                    $altLabel = $v;
                                    break;
                                }
                            }
                            if ($docKey) {
                                $data[$docKey] = array('type' => $altLabel, 'url' => '/' . $filePath);
                            }
                            else {
                                $data[] = array('type' => $altLabel, 'url' => '/' . $filePath);
                            }
                        }
                    }
                    else {
                        $data[] = array('alt' => $altLabel, 'url' => '/' . $filePath);
                    }

                    if (!empty($row['name']) && !empty($row['url'])) {
                        if ($downloadFileIfNotExists && !is_readable($filePath)) {
                            $url = SELF::WEBSERVICE_SERVER_URL . '/' . ltrim($row['url'], '/');

                            if ($handle = fopen($url, "rb")) {
                                if (file_put_contents($filePath, $handle)) {
                                    $record_count++;

                                    $connection->query(sprintf("UPDATE {%s} SET downloaded = 1 WHERE (sku = '%s') AND (name = '%s') LIMIT 1;", $db_table_name, $row['sku'], $row['name']));
                                }
                                fclose($handle);
                            }
                        }
                    }
                }
                if ($record_count > 0) {
                    $this->printInfo(sprintf('%s (%s) %s Files Downloaded in %s', $dataType, $lang, number_format($record_count), $local_Directory));
                }
            }
        }
        catch (Exception $e) {
            $this->printInfo('Caught exception: ',  $e->getMessage(), "\n");
        }
        return $data;
    }

    private function getProductHighlights($product, $lang = 'en') {
        $data = [];
        try {
            $sku = empty($product['sku']) ? false : $product['sku'];
            if (!empty($sku)) {
                $connection = $this->connectDatabase();

                $dataType = self::WEBSERVICE_DATA_TYPE_HIGHLIGHTS;

                $db_table_name = $this->getDatabaseTableName($dataType, $lang);
                $local_Directory = $this->getLocalFileDirectory($dataType, $lang) . '/';

                $where = sprintf(" WHERE (sku = '%s')", $sku);

                $record_count = 0;
                $result = $connection->query(sprintf('SELECT * FROM {%s} %s;', $db_table_name, $where));
                while ($row = $result->fetchAssoc()) {
                    $title = empty($row['title']) ? '' : $row['title'];
                    $description = empty($row['description']) ? '' : $row['description'];
                    $imageUrl = empty($row['imageUrl']) ? '' : $row['imageUrl'];
                    $videoUrl = empty($row['videoUrl']) ? '': $row['videoUrl'];

                    $imageName = $this->extractFilenameFromURL($imageUrl);
                    $filePath = $local_Directory . $imageName;

                    $data[] = [
                        'title' => $title,
                        'description' => $description,
                        'image' => ($imageName ? '/' . $filePath : ''),
                        'video' => $videoUrl,
                    ];

                    if ($imageName) {
                        if ($downloadFileIfNotExists && !is_readable($filePath)) {
                            $url = SELF::WEBSERVICE_SERVER_URL . '/' . ltrim($imageUrl, '/');

                            if ($handle = fopen($url, "rb")) {
                                if (file_put_contents($filePath, $handle)) {
                                    $record_count++;

                                    $connection->query(sprintf("UPDATE {%s} SET downloaded = 1 WHERE (sku = '%s') AND (imageUrl = '%s') LIMIT 1;", $db_table_name, $row['sku'], $imageUrl));
                                }
                                fclose($handle);
                            }
                        }
                    }
                }
                if ($record_count > 0) {
                    $this->printInfo(sprintf('%s (%s) %s Files Downloaded in %s', $dataType, $lang, number_format($record_count), $local_Directory));
                }
            }
        }
        catch (Exception $e) {
            $this->printInfo('Caught exception: ',  $e->getMessage(), "\n");
        }
        return $data;
    }

    private function extractFilenameFromURL($url) {
        if (!empty($url)) {
            $path = parse_url($url,  PHP_URL_PATH);
            if (!empty($path)) {
                $path = basename($path);
                if ($path) {
                    return $path;
                }
            }
        }
        return '';
    }

}
