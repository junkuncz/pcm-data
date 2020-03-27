<?php


/**
 * This is project's console commands configuration for Robo task runner.
 *
 * @see http://robo.li/
 */
class RoboFile extends \Robo\Tasks {

    const WEBSERVICE_SERVER_URL = 'https://pcm-prd.e-mabenet.com';

    const WEBSERVICE_URL = 'https://pcm-prd.e-mabenet.com/mabews/v2/Canada/products?currentPage=%d&pageSize=%d&fields=%s&COUNTRY=Canada&lang=%s_CA';

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
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
        $dotenv->load();

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
        $servername = getenv('DB_HOST');
        $username = getenv('DB_USERNAME');
        $password = getenv('DB_PASSWORD');
        $db_name = getenv('DB_NAME');

        try {
            $conn = new PDO("mysql:host=$servername;dbname=$db_name", $username, $password);
            // set the PDO error mode to exception
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $conn;
        } catch (PDOException $e) {
            echo "Connection failed: " . $e->getMessage();
        }
    }

    public function importDatabaseSchema() {
        $connection = $this->connectDatabase();
        $sql = file_get_contents('db_schema.sql');
        $connection->exec($sql);
    }

    // It loads all the data from the api into json files then imports to the database.
    public function importData() {
        set_time_limit(0);
        ini_set('display_errors', 1);

        $this->loadProductsDataIntoDatabase('all', TRUE, TRUE, FALSE);
        $this->loadProductsDataIntoDatabase('files', TRUE, TRUE, FALSE);
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
                                    try {
                                        isset($product['sku']) ? $product['sku'] : $product['sku'] = '';
                                        isset($product['category']) ? $product['category'] : $product['category'] = '';
                                        isset($product['upc']) ? $product['upc'] : $product['upc'] = '';
                                        isset($product['brand']) ? $product['brand'] : $product['brand'] = '';
                                        isset($product['modelStatus']) ? $product['modelStatus'] : $product['modelStatus'] = '';
                                        isset($product['country']) ? $product['country'] : $product['country'] = '';
                                        isset($product['model']) ? $product['model'] : $product['model'] = '';
                                        isset($product['linkText']) ? $product['linkText'] : $product['linkText'] = '';
                                        isset($product['metatag']) ? $product['metatag'] : $product['metatag'] = '';
                                        isset($product['name']) ? $product['name'] : $product['name'] = '';
                                        isset($product['siteTitle']) ? $product['siteTitle'] : $product['siteTitle'] = '';
                                        isset($product['descriptionMedium']) ? $product['descriptionMedium'] : $product['descriptionMedium'] = '';
                                        isset($product['descriptionLong']) ? $product['descriptionLong'] : $product['descriptionLong'] = '';
                                        isset($product['keywords']) ? $product['keywords'] : $product['keywords'] = '';
                                        isset($product['realWeight']) ? $product['realWeight'] : $product['realWeight'] = '';
                                        isset($product['weight']) ? $product['weight'] : $product['weight'] = '';
                                        isset($product['depth']) ? $product['depth'] : $product['depth'] = '';
                                        isset($product['depthInches']) ? $product['depthInches'] : $product['depthInches'] = '';
                                        isset($product['height']) ? $product['height'] : $product['height'] = '';
                                        isset($product['heightInches']) ? $product['heightInches'] : $product['heightInches'] = '';
                                        isset($product['length']) ? $product['length'] : $product['length'] = '';
                                        isset($product['lengthInches']) ? $product['lengthInches'] : $product['lengthInches'] = '';
                                        isset($product['realDepth']) ? $product['realDepth'] : $product['realDepth'] = '';
                                        isset($product['realDepthInches']) ? $product['realDepthInches'] : $product['realDepthInches'] = '';
                                        isset($product['realHeight']) ? $product['realHeight'] : $product['realHeight'] = '';
                                        isset($product['realHeightInches']) ? $product['realHeightInches'] : $product['realHeightInches'] = '';
                                        isset($product['realLength']) ? $product['realLength'] : $product['realLength'] = '';
                                        isset($product['realLengthInches']) ? $product['realLengthInches'] : $product['realLengthInches'] = '';
                                        isset($product['html']) ? $product['html'] : $product['html'] = '';
                                        isset($product['modelStartDate']) ? $product['modelStartDate'] : $product['modelStartDate'] = '';
                                        isset($product['modelEndDate']) ? $product['modelEndDate'] : $product['modelEndDate'] = '';

                                        $sql = "INSERT INTO `$db_table_name`
(sku, category, upc, brand, modelStatus, country, model, linkText, metatag, name, siteTitle,
descriptionMedium, descriptionLong, keywords, realWeight, weight, depth, depthInches, height, heightInches, length, lengthInches,
realDepth, realDepthInches, realHeight, realHeightInches, realLength, realLengthInches, html, modelStartDate, modelEndDate)
VALUES (:sku, :category, :upc, :brand, :modelStatus, :country, :model, :linkText, :metatag,
:name, :siteTitle, :descriptionMedium, :descriptionLong, :keywords, :realWeight, :weight, :depth,
:depthInches, :height, :heightInches, :length, :lengthInches, :realDepth, :realDepthInches, :realHeight, :realHeightInches, :realLength, :realLengthInches, :html, :modelStartDate, :modelEndDate)
ON DUPLICATE KEY UPDATE sku=:sku;";
                                        $stmt = $connection->prepare($sql);
                                        $stmt->bindParam(':sku', $product['sku']);
                                        $stmt->bindParam(':category', $product['category']);
                                        $stmt->bindParam(':upc', $product['upc']);
                                        $stmt->bindParam(':brand', $product['brand']);
                                        $stmt->bindParam(':modelStatus', $product['modelStatus']);
                                        $stmt->bindParam(':country', $product['country']);
                                        $stmt->bindParam(':model', $product['model']);
                                        $stmt->bindParam(':linkText', $product['linkText']);
                                        $stmt->bindParam(':metatag', $product['metatag']);
                                        $stmt->bindParam(':name', $product['name']);
                                        $stmt->bindParam(':siteTitle', $product['siteTitle']);
                                        $stmt->bindParam(':descriptionMedium', $product['descriptionMedium']);
                                        $stmt->bindParam(':descriptionLong', $product['descriptionLong']);
                                        $stmt->bindParam(':keywords', $product['keywords']);
                                        $stmt->bindParam(':realWeight', $product['realWeight']);
                                        $stmt->bindParam(':weight', $product['weight']);
                                        $stmt->bindParam(':depth', $product['depth']);
                                        $stmt->bindParam(':depthInches', $product['depthInches']);
                                        $stmt->bindParam(':height', $product['height']);
                                        $stmt->bindParam(':heightInches', $product['heightInches']);
                                        $stmt->bindParam(':length', $product['length']);
                                        $stmt->bindParam(':lengthInches', $product['lengthInches']);
                                        $stmt->bindParam(':realDepth', $product['realDepth']);
                                        $stmt->bindParam(':realDepthInches', $product['realDepthInches']);
                                        $stmt->bindParam(':realHeight', $product['realHeight']);
                                        $stmt->bindParam(':realHeightInches', $product['realHeightInches']);
                                        $stmt->bindParam(':realLength', $product['realLength']);
                                        $stmt->bindParam(':realLengthInches', $product['realLengthInches']);
                                        $stmt->bindParam(':html', $product['html']);
                                        $stmt->bindParam(':modelStartDate', $product['modelStartDate']);
                                        $stmt->bindParam(':modelEndDate', $product['modelEndDate']);
                                        $stmt->execute();

                                    }
                                    catch(PDOException $e)
                                    {
                                        echo $stmt . "<br>" . $e->getMessage();
                                    }
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
//                                                                $connection->insert($db_table_name)->fields($data)->execute();
                                                                try {
                                                                    $sql = "INSERT INTO `$db_table_name` (sku, name, value) VALUES (:sku, :name, :value);";
                                                                    $stmt = $connection->prepare($sql);
                                                                    $stmt->bindParam(':sku', $data['sku']);
                                                                    $stmt->bindParam(':name', $data['name']);
                                                                    $stmt->bindParam(':value', $data['value']);
                                                                    $stmt->execute();

                                                                }
                                                                catch(PDOException $e)
                                                                {
                                                                    echo $stmt . "<br>" . $e->getMessage();
                                                                }
                                                                $record_count++;

                                                                $product_features[$item['name']] = $value;

                                                                $record_count++;
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
//                                        // $connection->update($product_table_name)->fields(array('features' => (empty($product_features) ? '' : json_encode($product_features))))->condition('sku', $product['sku'])->execute();
                                        $product_features = (empty($product_features) ? '' : json_encode($product_features));

                                        try {
                                            $sql = "UPDATE `$product_table_name` SET features=:features WHERE sku=:sku;";
                                            $stmt = $connection->prepare($sql);
                                            $stmt->bindParam(':features', $product_features);
                                            $stmt->bindParam(':sku', $product['sku']);
                                            $stmt->execute();

                                        }
                                        catch(PDOException $e)
                                        {
                                            echo $stmt . "<br>" . $e->getMessage();
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


    private function downloadProductRelatedFiles($sku = '') {
        $dataTypes = [self::WEBSERVICE_DATA_TYPE_IMAGES, self::WEBSERVICE_DATA_TYPE_DOCUMENTS, self::WEBSERVICE_DATA_TYPE_HIGHLIGHTS];
        $languages = ['en', 'fr'];

        try {
           // $where = ' WHERE (downloaded = 0) ' . (empty($sku) ? '' : sprintf(" AND (sku = '%s')", $sku));

            $connection = $this->connectDatabase();
            foreach($dataTypes as $dataType) {
                foreach($languages as $lang) {

                    $db_table_name = $this->getDatabaseTableName($dataType, $lang);
                    $local_Directory = $this->getLocalFileDirectory($dataType, $lang) . '/';


                    $record_count = 0;
                    // $result = $connection->query(sprintf('SELECT * FROM {%s} %s;', $db_table_name, $where));
                    if (empty($sku)) {
                        $stmt = $connection->prepare("SELECT * FROM `$db_table_name` WHERE downloaded = 0;");
                    }
                    else {
                        $stmt = $connection->prepare("SELECT * FROM `$db_table_name` WHERE downloaded = 0 AND sku=:sku;");
                        $stmt->bindParam(':sku', $sku);
                    }
                    $stmt->execute();

                    $result = $stmt->fetchAll();
                    foreach ($result as $row) {
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
                                            // $connection->query(sprintf("UPDATE {%s} SET downloaded = 1 WHERE (sku = '%s') AND (imageUrl = '%s') LIMIT 1;", $db_table_name, $row['sku'], $row['imageUrl']));
                                            $stmt = $connection->prepare("UPDATE `$db_table_name` SET downloaded = 1 WHERE sku=:sku AND imageUrl=:imageUrl LIMIT 1;");
                                            $stmt->bindParam(':sku', $row['sku']);
                                            $stmt->bindParam(':imageUrl', $row['imageUrl']);
                                            $stmt->execute();
                                        }
                                        else {
                                            //$connection->query(sprintf("UPDATE {%s} SET downloaded = 1 WHERE (sku = '%s') AND (name = '%s') LIMIT 1;", $db_table_name, $row['sku'], $row['name']));
                                            $stmt = $connection->prepare("UPDATE `$db_table_name` SET downloaded = 1 WHERE sku=:sku AND name=:name LIMIT 1;");
                                            $stmt->bindParam(':sku', $row['sku']);
                                            $stmt->bindParam(':name', $row['name']);
                                            $stmt->execute();
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

                                                        // $connection->insert($db_table_name)->fields($data)->execute();

                                                        try {
                                                            $sql = "INSERT INTO `$db_table_name` (sku, type, label, name, url) VALUES (:sku, :type, :label, :name, :url);";
                                                            $stmt = $connection->prepare($sql);
                                                            $stmt->bindParam(':sku', $data['sku']);
                                                            $stmt->bindParam(':type', $data['type']);
                                                            $stmt->bindParam(':label', $data['label']);
                                                            $stmt->bindParam(':name', $data['name']);
                                                            $stmt->bindParam(':url', $data['url']);
                                                            $stmt->execute();

                                                        }
                                                        catch(PDOException $e)
                                                        {
                                                            echo $stmt . "<br>" . $e->getMessage();
                                                        }


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

                                                    // $connection->insert($db_table_name)->fields($data)->execute();

                                                    try {
                                                        $sql = "INSERT INTO `$db_table_name` (sku, type, label, name, url) VALUES (:sku, :type, :label, :name, :url);";
                                                        $stmt = $connection->prepare($sql);
                                                        $stmt->bindParam(':sku', $data['sku']);
                                                        $stmt->bindParam(':type', $data['type']);
                                                        $stmt->bindParam(':label', $data['label']);
                                                        $stmt->bindParam(':name', $data['name']);
                                                        $stmt->bindParam(':url', $data['url']);
                                                        $stmt->execute();

                                                    }
                                                    catch(PDOException $e)
                                                    {
                                                        echo $stmt . "<br>" . $e->getMessage();
                                                    }


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
                                                //$connection->insert($db_table_name)->fields($data)->execute();

                                                try {
                                                    $sql = "INSERT INTO `$db_table_name` (sku, title, description, imageUrl, videoUrl) VALUES (:sku, :title, :description, :imageUrl, :videoUrl);";
                                                    $stmt = $connection->prepare($sql);
                                                    $stmt->bindParam(':sku', $data['sku']);
                                                    $stmt->bindParam(':title', $data['title']);
                                                    $stmt->bindParam(':description', $data['description']);
                                                    $stmt->bindParam(':imageUrl', $data['imageUrl']);
                                                    $stmt->bindParam(':videoUrl', $data['videoUrl']);
                                                    $stmt->execute();

                                                }
                                                catch(PDOException $e)
                                                {
                                                    echo $stmt . "<br>" . $e->getMessage();
                                                }

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
