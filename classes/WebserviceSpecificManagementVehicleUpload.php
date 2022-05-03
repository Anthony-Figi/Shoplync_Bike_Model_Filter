<?php
/**
* @author    Anthony Figueroa - Shoplync Inc <sales@shoplync.com>
* @copyright 2007-2022 Shoplync Inc
* @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
* @category  PrestaShop module
* @package   Bike Model Filter
*      International Registered Trademark & Property of Shopcreator
* @version   1.0.0
* @link      http://www.shoplync.com/
*/
use PrestaShop\PrestaShop\Core\File\Exception\FileUploadException;
use PrestaShop\PrestaShop\Core\File\Exception\MaximumSizeExceededException;
use PrestaShop\PrestaShop\Core\File\FileUploader;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Response;

if(!class_exists('dbg'))
{
    include_once dirname (_PS_MODULE_DIR_).'/modules/shoplync_bike_model_filter/classes/helper.php';
    class_alias(get_class($shoplync_dbg), 'dbg');
}
/**
 * This class is responsible for managing VehicleFitments through webservice
 */
class WebserviceSpecificManagementVehicleUpload implements WebserviceSpecificManagementInterface {
    
    /**
     * @var WebserviceRequest
     */
    protected $wsObject;

    /**
     * @var string
     */
    protected $output;
    
    /**
     * @var WebserviceOutputBuilder
     */
    protected $objOutput;
    
    /**
     * @var array|null
     */
    protected $displayFile;
    
    /**
     * @var array The list of supported mime types
     */
    protected $acceptedMimeTypes = [
        'text/csv', 'text/plain', 
        'application/csv', 'application/x-csv', 
        'text/x-csv', 'text/comma-separated-values', 
        'text/x-comma-separated-values', 'text/tab-separated-values',
        'application/vnd.ms-excel'
    ];
    
    /**
     * @var int The maximum size supported when uploading images, in bytes
     */
    protected $maximumSize = 3000000;


    /**
     * @param WebserviceOutputBuilder $obj
     *
     * @return WebserviceSpecificManagementInterface
     */
    public function setObjectOutput(WebserviceOutputBuilderCore $obj)
    {
        $this->objOutput = $obj;

        return $this;
    }
    
    /**
     * Get Object Output
     */
    public function getObjectOutput()
    {
        return $this->objOutput;
    }

    public function setWsObject(WebserviceRequestCore $obj)
    {
        $this->wsObject = $obj;
        
        return $this;
    }

    public function getWsObject()
    {
        return $this->wsObject;
    }

    protected function defaultResponse()
    {
        $more_attr = [
            'get' => 'true', 
            'put' => 'false', 
            'post' => 'true', 
            'delete' => 'false', 
            'head' => 'true',
            'upload_allowed_mimetypes' => implode(', ', $this->acceptedMimeTypes),
        ];
        
        $this->output .= $this->objOutput->getObjectRender()->renderNodeHeader('file_types', []);
        $this->output .= $this->objOutput->getObjectRender()->renderNodeHeader('csv', [], $more_attr, false);
        $this->output .= $this->objOutput->getObjectRender()->renderNodeFooter('file_types', []);
    }

    public function manage()
    {
        $method = $this->wsObject->method;
        
        if(isset($method) && $method == 'POST')
        {
            switch($this->getWsObject()->urlSegment[1])
            {
                case 'fitments':
                    $this->processPostedFile(0);
                    break;
                case 'years':
                    $this->processPostedFile(1);
                    break;      
                case 'makes':
                    $this->processPostedFile(2);
                    break;        
                case 'models':
                    $this->processPostedFile(3);
                    break;     
                case 'types':
                    $this->processPostedFile(4);
                    break;                    
                case 'vehicles':
                    $this->processPostedFile(5);
                    break;
                default:
                    $this->defaultResponse();
                    return true;
            }
        }
        
        return $this->getWsObject()->getOutputEnabled();
    }

    /**
     * Gets the mime file type for the given file
     *
     * @param $_FILES array $arry
     *
     * @return string
     */
    protected function GetMimeType($file = null)
    {
        if (!isset($file['tmp_name']))
        {
            $file = $_FILES['file'];
        }
     
        // Get mime content type
        $mime_type = false;
        if (Tools::isCallable('finfo_open')) {
            $const = defined('FILEINFO_MIME_TYPE') ? FILEINFO_MIME_TYPE : FILEINFO_MIME;
            $finfo = finfo_open($const);
            $mime_type = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
        } elseif (Tools::isCallable('mime_content_type')) {
            $mime_type = mime_content_type($file['tmp_name']);
        } elseif (Tools::isCallable('exec')) {
            $mime_type = trim(exec('file -b --mime-type ' . escapeshellarg($file['tmp_name'])));
        }
        if (empty($mime_type) || $mime_type == 'regular file') {
            $mime_type = $file['type'];
        }
        if (($pos = strpos($mime_type, ';')) !== false) {
            $mime_type = substr($mime_type, 0, $pos);
        }
        
        
        return $mime_type;
    }
    /**
    * Check the given mime type to see if it is part of the acceptedMimeTypes
    *
    * $mime_type string - the mime type to be checked
    *
    * return boolean - Whether the given mim type is value true/false
    */
    protected function isValidMimeType($mime_type = null)
    {
        if (!isset($mime_type))
        {
            return false;
        }
        
        if (!$mime_type || !in_array($mime_type, $this->acceptedMimeTypes)) {
            throw new WebserviceException('This type of image format is not recognized, allowed formats are: ' . implode('", "', $this->acceptedMimeTypes), [73, 400]);
        } elseif ($file['error']) {
            // Check error while uploading
            throw new WebserviceException('Error while uploading image. Please change your server\'s settings', [74, 400]);
        }
        
        return true;
    }
    /**
    * This helper function attempts to get matching id_product and id_product_attribute values
    * given a MPN
    *
    * $mpn string - The manufacturer part number to be used as a search
    */
    protected function GetProductAttr($mpn)
    {
        /*
            SELECT p.id_product, pa.id_product_attribute, p.mpn AS 'prod_mpn', pa.mpn AS 'combo_mpn' 
            FROM ps_ca_product AS p LEFT JOIN ps_ca_product_attribute AS pa 
            ON p.id_product = pa.id_product 
            WHERE pa.mpn = '0418-35567';
        */
        $sqlQuery = 'SELECT p.id_product, pa.id_product_attribute, p.mpn AS `prod_mpn`, pa.mpn AS `combo_mpn`'
        .'FROM `' . _DB_PREFIX_ . 'product` AS p LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute` AS pa '
        .'ON p.id_product = pa.id_product';
        
        $result = null;
        if(isset($mpn) && strlen($mpn) > 0)
        {
            $prodQuery = $sqlQuery.' WHERE p.mpn = \''.$mpn.'\'';
            $comboQuery = $sqlQuery.' WHERE pa.mpn = \''.$mpn.'\'';
            
            //error_log('query1->'.$prodQuery);
            $result = Db::getInstance()->executeS($prodQuery);
            
            if(empty($result))
            {
                //error_log('query2->'.$comboQuery);
                $result = Db::getInstance()->executeS($comboQuery);
            }
            
            return $result;
        }
        return [];
    }
    
    /**
    * This helper function will either update/create a vehicle fitment between a product and vehicle id
    *
    * $vehicle_id int - the vehicle id to be updated/created
    * $sqlResult array - List of product data that will be used to create the associations
    *
    */
    protected function InsertOrUpdateFitment($vehicle_id, $sqlResult)
    {
        if(is_array($sqlResult) && isset($vehicle_id) && !empty($vehicle_id))
        {
            foreach($sqlResult as $value)
            {
                if(array_key_exists('id_product', $value) && array_key_exists('id_product_attribute', $value))
                {
                    //error_log('value:'.print_r($value, true));
                    $sqlInsert = 'INSERT INTO ' . _DB_PREFIX_ . 'vehicle_fitment(id_product, id_product_attribute, id_vehicle) VALUES('.$value['id_product'].', '.($value['id_product_attribute'] ?? 'null').', '.$vehicle_id.') ' 
                    .'ON DUPLICATE KEY UPDATE id_product = '.$value['id_product'].', id_product_attribute = '.($value['id_product_attribute'] ?? 'null').', id_vehicle = '.$vehicle_id.';';
                    
                    if(Db::getInstance()->execute($sqlInsert) == FALSE)
                    {
                        dbg::m('SQL Query Failed: '.$sqlInsert);
                    }
                    //dbg::m('> '.$sqlInsert);
                }
            }
        }
    }
    
    /**
    * This helper function is used to determine which row processor to pass the parsed CSV line to
    *
    * $type int - The specific file type being processed
    */
    public function processPostedFile($type = -1)
    {
        if (isset($_FILES['file']['tmp_name']) && $_FILES['file']['tmp_name'] && is_numeric($type)) {
            $file = $_FILES['file'];
            if ($file['size'] > $this->maximumSize) {
                throw new WebserviceException(sprintf('The image size is too large (maximum allowed is %d KB)', ($this->maximumSize / 1000)), [72, 400]);
            }
            
            // Get mime content type
            $mime_type = $this->GetMimeType($file);
            
            //process csv file
            if (($handle = fopen($file['tmp_name'], "r")) !== FALSE && $this->isValidMimeType($mime_type)) {
                while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    if(!is_numeric($row[0]))
                        continue;//skip possible header

                    switch(intval($type))
                    {
                        case 0:
                            $this->processPostedFitmentFile($row);
                            break;
                        case 1:
                            $this->proccessPostedYearFile($row);
                            break;
                        case 2:
                            $this->proccessPostedMakeFile($row);
                            break;
                        case 3:
                            $this->proccessPostedModelFile($row);
                            break;
                        case 4:
                            $this->proccessPostedTypeFile($row);
                            break;
                        case 5:
                            $this->proccessPostedVehicleFile($row);
                            break;
                        default:
                            $this->defaultResponse();
                    }
                }
                fclose($handle);
            }
        }
    }
    
    /**
    * Checks to see whether there row meets a valid column/parameter count
    *
    * $row array - The parse row stored as an array
    * $count int - The required parameters/column the row must have
    *
    * return Whether the count was met or not
    */
    protected function isValidRowCount($row = [], $count = 2)
    {
        if(count($row) !== $count)
        {
             dbg::m(sprintf('Error: Row count %d, expected %d', count($row), $count));
             return false;
        }
        return true;
    }
    
    /**
    * This function processes row by row vehicle fitment and saves to database
    * 
    * $row array - The current row to process
    */
    protected function processPostedFitmentFile($row = null)
    {
        if(is_array($row) && $this->isValidRowCount($row, 2))
        {
            $result = $this->GetProductAttr($row[1]);
            //dbg::m("RESULT:".print_r($result, true));
            
            $this->InsertOrUpdateFitment($row[0], $result);
        }
    }
    /**
    * This function processes row by row year entries & saves to database
    * 
    * $row array - The current row to process
    */
    protected function proccessPostedYearFile($row = null)
    {
        if(is_array($row) && is_numeric($row[1]) && $this->isValidRowCount($row, 2))
        {
            $sqlInsert = 'INSERT INTO ' . _DB_PREFIX_ . 'year VALUES('.$row[0].', '.$row[0].', '.$row[1].') ' 
            .'ON DUPLICATE KEY UPDATE year = '.$row[1].';';
            
            if(Db::getInstance()->execute($sqlInsert) == FALSE)
            {
                dbg::m('SQL Query Failed: '.$sqlInsert);
            }
        }
    }

    /**
    * This function processes row by row make entries & saves to database
    * 
    * $row array - The current row to process
    */
    protected function proccessPostedMakeFile($row = null)
    {
        if(is_array($row) && $this->isValidRowCount($row, 2))
        {
            $sqlInsert = 'INSERT INTO ' . _DB_PREFIX_ . 'make VALUES('.$row[0].', '.$row[0].', \''.$row[1].'\', TRUE) ' 
            .'ON DUPLICATE KEY UPDATE name = \''.$row[1].'\';';
            
            if(Db::getInstance()->execute($sqlInsert) == FALSE)
            {
                dbg::m('SQL Query Failed: '.$sqlInsert);
            }
        }
    }
    /**
    * This function processes row by row model entries & saves to database
    * 
    * $row array - The current row to process
    */
    protected function proccessPostedModelFile($row = null)
    {
        if(is_array($row) && $this->isValidRowCount($row, 4))
        {
            $sqlInsert = 'INSERT INTO ' . _DB_PREFIX_ . 'model VALUES('.$row[0].', '.$row[0].', \''.$row[1].'\', '.$row[2].', '.(empty($row[3]) ? 'null' : $row[3]).') ' 
            .'ON DUPLICATE KEY UPDATE name = \''.$row[1].'\', make_id = '.$row[2].', id_type = '.(empty($row[3]) ? 'null' : $row[3]).';';
            
            if(Db::getInstance()->execute($sqlInsert) == FALSE)
            {
                dbg::m('SQL Query Failed: '.$sqlInsert);
            }
        }
    }
    /**
    * This function processes row by row vehicle type entries & saves to database
    * 
    * $row array - The current row to process
    */
    protected function proccessPostedTypeFile($row = null)
    {
        if(is_array($row) && $this->isValidRowCount($row, 6))
        {            
            $sqlInsert = 'INSERT INTO ' . _DB_PREFIX_ . 'type VALUES('.$row[0].', '.$row[0].', '.(empty($row[1]) ? 'null' : $row[1]).', \''.$row[2].'\', '.$row[4].', '.$row[5].') ' 
            .'ON DUPLICATE KEY UPDATE parent_id_type = '.(empty($row[1]) ? 'null' : $row[1]).', name = \''.$row[2].'\', depth = '.$row[4].', is_visible = '.$row[5].';';
            
            if(Db::getInstance()->execute($sqlInsert) == FALSE)
            {
                dbg::m('SQL Query Failed: '.$sqlInsert);
            }
        }
    }
    /**
    * This function processes row by row vehicle entries & saves to database
    * 
    * $row array - The current row to process
    */
    protected function proccessPostedVehicleFile($row = null)
    {
        if(is_array($row) && $this->isValidRowCount($row, 3))
        {
            $sqlInsert = 'INSERT INTO ' . _DB_PREFIX_ . 'vehicle VALUES('.$row[0].', '.$row[0].', '.$row[1].', '.$row[2].') ' 
            .'ON DUPLICATE KEY UPDATE model_id = '.$row[1].', id_year = '.$row[2].';';
            
            if(Db::getInstance()->execute($sqlInsert) == FALSE)
            {
                dbg::m('SQL Query Failed: '.$sqlInsert);
            }
        }
    }

    /**
     * This must be return a string with specific values as WebserviceRequest expects.
     *
     * @return string
     */
    public function getContent()
    {
        if ($this->output != '') {
            return $this->objOutput->getObjectRender()->overrideContent($this->output);
        }

        return '';
    }

}