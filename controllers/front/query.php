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


if(!class_exists('dbg'))
{
    include_once dirname (_PS_MODULE_DIR_).'/modules/shoplync_bike_model_filter/classes/helper.php';
    class_alias(get_class($shoplync_dbg), 'dbg');
}

/**
 * Class itself
 */
class shoplync_bike_model_filterqueryModuleFrontController extends ModuleFrontController
{   
    /**
     * Save form data.
     */
    public function postProcess()
    {
        return parent::postProcess(); 
    }
 


/**
* ====================================
* Filter/Search Page
* ====================================
*/ 
    /**
     * This function sets the appropritate error headers and returns the default 'Failed' error response
     * 
     * $errorMessage string - The error message to return
     * $extra_details array() - array of key:value pairs to be added to the error json response
     * 
    */
    public function setErrorHeaders($errorMessage = 'Failed', $extra_details = [])
    {
        header('HTTP/1.1 500 Internal Server Error');
        header('Content-Type: application/json; charset=UTF-8');
        
        $error_array = ['errorResponse' => $errorMessage];
        
        if(!empty($extra_details) && is_array($extra_details))
            $error_array = $error_array + $extra_details;
        
        $this->ajaxDie(json_encode($error_array));
    }
    
    /**
    * Triggered via an AJAX call, unsets all the values stored in the cookie
    */
    public function displayAjaxClearSelectionCookie()
    {
        Context::getContext()->cookie->__unset('vehicle_id');
        Context::getContext()->cookie->__unset('make_id');
        Context::getContext()->cookie->__unset('model_id');
        Context::getContext()->cookie->__unset('year_id');
        
        $this->ajaxDie(json_encode([
            'success' => true,
            'message' => 'Filter Selection Cookies Have Been Cleared',
        ]));
    }
    
    /**
    * Triggered via an AJAX call, retrieves a vehicles make/model/year id values
    *
    * $_POST['vehicle_id'] int - Used to specify which vehicle to retireve
    */    
    public function displayAjaxGetVehicleDetails()
    {
        error_log('Fetching Vehicle details');
        if (Tools::isSubmit('vehicle_id'))
        {
            $vehicle_id = Tools::getValue('vehicle_id', 0);
            dbg::m('vehicle id:'.$vehicle_id);
            if($vehicle_id == null && !is_numeric($vehicle_id) || $vehicle_id <= 0)
                $this->setErrorHeaders();
            
            $vehicle_info = Shoplync_bike_model_filter::GetVehicleInfo($vehicle_id);
            $vehicle_info = array_pop($vehicle_info);
            if(is_array($vehicle_info) && !empty($vehicle_info) 
                && array_key_exists('model_id', $vehicle_info) 
                && array_key_exists('make_id', $vehicle_info) 
                && array_key_exists('id_year', $vehicle_info))
            {
                //Stip out uneeded data, lower bytes returned
                $this->ajaxDie(json_encode([
                    'success' => true,
                    'model_id' => $vehicle_info['model_id'],
                    'make_id' => $vehicle_info['make_id'],
                    'year_id' => $vehicle_info['id_year'],
                ]));
            }
            else
                $this->setErrorHeaders('Failed To Load From Database Vehicle ID:'.$vehicle_id);
        }
        $this->setErrorHeaders();
    }
    
    /**
    * Triggered via an AJAX call, retrieves all vehicle makes associated to the specified type
    *
    * $_POST['type_id'] int - Used to specify which type to retrieve makes from
    */  
    public function displayAjaxGetMakes()
    {
        dbg::m('Fetching Makes...');
        if (Tools::isSubmit('type_id'))
        {
            $type_id = Tools::getValue('type_id');
            dbg::m('type:'.$type_id);
            
            $makes = $this->GetMakesByType($type_id);
            
            if(is_array($makes) && !empty($makes))
                $this->ajaxDie(json_encode(['makes' => $this->GenerateList($makes)]));
            else
                $this->setErrorHeaders('Failed To Load From Database Type ID:'.$type_id);
        }
        $this->setErrorHeaders();
    }
    
    /**
    * Triggered via an AJAX call, retrieves all vehicle model associated to the specified make
    *
    * $_POST['make_id'] int - Used to specify which makes to retrieve vehicle models from
    */  
    public function displayAjaxGetModels() 
    {
        $type_id = Tools::isSubmit('type_id') ? Tools::getValue('type_id', null) : null;
        dbg::m('Fetching Models...');
        if (Tools::isSubmit('make_id'))
        {
            $make_id = Tools::getValue('make_id');
            dbg::m('make:'.$make_id);

            if($type_id != null)
                dbg::m('type id recieved: '.$type_id ?? 'null');
            
            $models = $this->GetModelsByMake($make_id, $type_id);
            if(is_array($models) && !empty($models))
                $this->ajaxDie(json_encode(['models' => $this->GenerateList($models)]));
            else
                $this->setErrorHeaders('Failed To Load From Database Make ID:'.$make_id);
        }
        $this->setErrorHeaders();
    }

    /**
    * Triggered via an AJAX call, retrieves all vehicle years associated with the specified model
    *
    * $_POST['model_id'] int - Used to specify which model to retrieve vehicle years from
    */
    public function displayAjaxGetYears() 
    {
        dbg::m('Fetching Model Years...');
        if (Tools::isSubmit('model_id'))
        {
            $model_id = Tools::getValue('model_id');
            dbg::m('model:'.$model_id);
            
            $years = $this->GetYearsFromModel($model_id);
            if(is_array($years) && !empty($years))
                $this->ajaxDie(json_encode(['years' => $this->GenerateList($years)]));
            else
                $this->setErrorHeaders('Failed To Load From Database Make ID:'.$model_id);
        }
        $this->setErrorHeaders();
    }
    
/**
* =================================
* Customers My Garage Page
* =================================
*/    
    /**
    * Triggered via an AJAX call, adds the specified vehicle to the current customers garage
    *
    * $_POST['model_id'] int - Used to retrive the appropriate vehicle_id
    * $_POST['year_id'] int - Used to retrive the appropriate vehicle_id
    * $_POST['vehicle_name'] int - The nickcname that will be displayed in the customer garage
    * $_POST['customer_id'] int - Specifies for which customer will the vehicle be added to their garage
    */
    public function displayAjaxAddToGarage()
    {
        if (Tools::isSubmit('model_id') && Tools::isSubmit('year_id') && Tools::isSubmit('vehicle_name') && Tools::isSubmit('customer_id'))
        {
            $model_id = Tools::getValue('model_id');
            $year_id = Tools::getValue('year_id');
            
            //Get Vehicle ID
            $vehicle_id = Shoplync_bike_model_filter::GetVehicleID($model_id, $year_id);
            if(!is_numeric($vehicle_id))
            {
                $this->setErrorHeaders('Failed To Load Find Vehicle ID');
            }
            
            $customer_id = Tools::getValue('customer_id');
            $vehicle_name = Tools::getValue('vehicle_name');
            $image_path = Tools::isSubmit('image_path') ? Tools::getValue('image_path') : 'NULL';
            
            $sqlInsert = 'INSERT INTO ' . _DB_PREFIX_ . 'garage(vehicle_id, customer_id, vehicle_name, image_path) VALUES('.$vehicle_id.', '.$customer_id.', \''.$vehicle_name.'\', '.$image_path.');';

            $db = Db::getInstance();
            if($db->execute($sqlInsert) == FALSE)
            {
                $this->setErrorHeaders('SQL Query Failed');
            }
            
            //check if there is a perfered bike for this customer if not create one using the provided garage id + customer_id
            $garage_id = $db->Insert_ID();
            $this->CreateOrUpdatePrefered($customer_id, $garage_id, false);
            
            $this->ajaxDie(json_encode(['success' => 'Added To Garage', 'garage_id' => $garage_id]));
        }
    }
    
    /**
    * Triggered via an AJAX call, Updates/Create a prefered vehicle for the custoemrs garage
    *
    * $_POST['garage_id'] int - The new vehicle garage id to set as prefered
    * $_POST['customer_id'] int - Specifies for which customer will the vehicle be set as prefered
    */
    public function displayAjaxUpdatePrefered()
    {
        if (Tools::isSubmit('garage_id') && Tools::isSubmit('customer_id'))
        {
            $garage_id = Tools::getValue('garage_id');
            $customer_id = Tools::getValue('customer_id');
            
            if($this->CreateOrUpdatePrefered($customer_id, $garage_id) == TRUE)
                $this->ajaxDie(json_encode(['success' => 'Updated Prefered']));
            else
                $this->setErrorHeaders('SQL Query Failed');  
        }
    }
    
    /**
     * This function updates the specified customers prefered garage vehicle, 
     * if none is set it will create a new database entry
     *
     * $customer_id int - The specified customer
     * $garage_id int - The vehicle garage id to be set as prefered
     * $allowUpdate boolean - Whether to allow prefered to be overwritten if already set
     *
     * return boolean - whether the prefered was updated successfully or not
    */
    protected function CreateOrUpdatePrefered($customer_id, $garage_id, $allowUpdate = true)
    {
        if(is_numeric($customer_id) && is_numeric($garage_id))
        {
            //If exists return
            if(!$allowUpdate)
            {
                $result = $this->GetPrefered($customer_id);

                if(!empty($result))   
                    return false;
            }
            
            //Perform  Insert or Update
            $sqlInsert = 'INSERT INTO ' . _DB_PREFIX_ . 'prefered_vehicle(customer_id, garage_id) VALUES('.$customer_id.', '.$garage_id.') ON DUPLICATE KEY UPDATE garage_id = '.$garage_id.';';
            if(Db::getInstance()->execute($sqlInsert) == FALSE)
            {
                dbg::m('SQL Query Failed: '.$sqlInsert);
                return false;
            }
            
            return true;
        }
    }
    
    /**
    * Retrieves the prefered bike model associated with the customer
    *
    * $customer_id int - The specified customer to retrive prefered for
    *
    * return array() - db result
    */
    protected function GetPrefered($customer_id = null)
    {
        if(!isset($customer_id))
            return [];
        
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'prefered_vehicle` WHERE customer_id = '.$customer_id;
        return Db::getInstance()->executeS($sql);
    }
 
    /**
    * Triggered via an AJAX call, Deletes the specified vehicle from the customers garage
    * if the vehicle being deleted is also prefered, set prefered to the next vehicle in the list.
    *
    * $_POST['garage_id'] int - The new vehicle garage id to be deleted
    * $_POST['customer_id'] int - Specifies for which customer will the vehicle be removed from garage
    */ 
    public function displayAjaxDeleteGarageId()
    {
        if (Tools::isSubmit('garage_id') && Tools::isSubmit('customer_id'))
        {
            $garage_id = Tools::getValue('garage_id');
            $customer_id = Tools::getValue('customer_id');
            $new_prefered = [];
            //check if it is prefered 
            $result = $this->GetPrefered($customer_id);

            //dbg::m('Prefered array: '.print_r($result, true));

            if(!empty($result))
            {
                $result = array_pop($result);
                if($result['garage_id'] == $garage_id)
                {
                    $cust_garage = Shoplync_bike_model_filter::GetCustomerGarage($customer_id);
                    if(count($cust_garage) > 1)
                    {
                        foreach($cust_garage as $entry)
                        {
                            if($entry['garage_id'] != $garage_id)
                            {
                                if($this->CreateOrUpdatePrefered($customer_id, $entry['garage_id']) == FALSE)
                                {
                                    $this->setErrorHeaders('SQL Query Failed');
                                }
                                $new_prefered = $entry;
                                break;
                            }
                        } 
                    }
                    else 
                    {
                        $sql = 'DELETE FROM `' . _DB_PREFIX_ . 'prefered_vehicle` WHERE garage_id ='.$garage_id.' AND customer_id = '.$customer_id;
                        if(Db::getInstance()->execute($sql) == FALSE)
                        {
                            dbg::m('SQL Query Failed: '.$sql);
                            $this->setErrorHeaders('SQL Query Failed');
                        }
                    }
                }
            }

            //If image path set delete image from disk
            $garage_vehicle = Shoplync_bike_model_filter::GetCustomerGarageVehicle($customer_id ,$garage_id);
            if(!empty($garage_vehicle) && array_key_exists('image_path', $garage_vehicle) && !empty($garage_vehicle['image_path']))
                Shoplync_bike_model_filter::deleteImage($garage_vehicle['image_path']);

            $sql = 'DELETE FROM `' . _DB_PREFIX_ . 'garage` WHERE garage_id ='.$garage_id;
            
            if(Db::getInstance()->execute($sql) == FALSE)
            {
                dbg::m('SQL Query Failed: '.$sql);
                $this->setErrorHeaders('SQL Query Failed');
            }
            
            $json_array = ['success' => 'Removed From Garage'];
            if(!empty($new_prefered))
            {
                $new_prefered['is_prefered'] = 1;
                $json_array['prefered'] = $new_prefered;
                dbg::m('prefered:'.print_r($new_prefered, true));
            }

            
            $this->ajaxDie(json_encode($json_array));
        }
    }
    
    /**
    * Triggered via an AJAX call, updates the garage vehicle name set by user
    *
    * $_POST['garage_id'] int - The new vehicle garage id to be updated
    * $_POST['customer_id'] int - Specifies for which customer will the vehicle be updated
    * $_POST['vehicle_name'] int - The new vehicle name to be set
    */ 
    public function displayAjaxUpdateVehicleName()
    {
        if (Tools::isSubmit('garage_id') && Tools::isSubmit('customer_id') && Tools::isSubmit('vehicle_name'))
        {
            $garage_id = Tools::getValue('garage_id');
            $customer_id = Tools::getValue('customer_id');
            $new_vehicle_name = Tools::getValue('vehicle_name');
            /*
            UPDATE ps_ca_garage SET vehicle_name = 'new' WHERE customer_id = 27089 AND garage_id = 2;
            */
            $sql = 'UPDATE `' . _DB_PREFIX_ . 'garage` SET vehicle_name = "'.$new_vehicle_name.'" '
                .'WHERE customer_id = '.$customer_id.' AND garage_id = '.$garage_id;
             
            dbg::m('sql: '.$sql);
             
            if(Db::getInstance()->execute($sql) == FALSE)
            {
                dbg::m('SQL Query Failed: '.$sql);
                $this->setErrorHeaders('SQL Query Failed');
            }
            header('Content-Type: application/json; charset=UTF-8');
            $this->ajaxDie(json_encode(['success' => 'Vehicle Name Updated']));
        }
    }
    
/**
* =================================
* Product Page
* =================================
*/
    /**
    * Triggered via an AJAX call, Retrieves the products vehicle fitment given a particular make
    *
    * $_POST['make_id'] int - Used to filter product vehicle fitments by make
    * $_POST['product_id'] int - Specifies which product to retrieve vehicle fitment for
    * $_POST['attribute_id'] int- Specifies whether to retrieve fitment for a product combination
    */ 
    public function displayAjaxGetFitmentModels()
    {
        if (Tools::isSubmit('make_id') && Tools::isSubmit('product_id'))
        {
            $universal_vehicle_model_id = Configuration::get('SHOPLYNC_FITMENT_UNIVERSAL_VEHICLE', 0);
            $universal_fluid_model_id = Configuration::get('SHOPLYNC_FITMENT_FLUIDS_VEHICLE', 0);
            $make_id = Tools::getValue('make_id');
            $product_id = Tools::getValue('product_id');
            $attribute_id = Tools::getValue('attribute_id', null);
            
            $fitments = Shoplync_bike_model_filter::getProductFitmentByMake($make_id, $product_id, $attribute_id);
            
            if(!empty($fitments))
            {
                dbg::m('fitments: '.print_r($fitments, true));
                $table_rows = [];
                
                foreach($fitments as $model)
                {
                    array_push($table_rows, '<tr><td class="align-middle font-weight-bold hidden">'.$model['vehicle_id'].'</td><td class="align-middle font-weight-bold">'.$model['model_name'].'</td>'
                    .'<td class="align-middle font-weight-bold">'.($model['model_id'] == $universal_vehicle_model_id || $model['model_id'] == $universal_fluid_model_id ? '-' : ($model['min'] == $model['max'] ? $model['min'] : $model['min'].'-'.$model['max'])).'</td></tr>');
                }
                $this->ajaxDie(json_encode(['success' => 'Fetched Fitments', 'fitments' => implode($table_rows)]));
            }
            
            $this->setErrorHeaders('SQL Query Failed');
        }
    }
    
    /**
    * Triggered via an AJAX call, Uploads a vehicle image to the customers garage vehicle
    *
    * $_POST['customer_id'] int - Used to update the proper garage
    * $_POST['garage_id'] int - Specifies which garage vehicle we are setting the image to
    */ 
    public function displayAjaxUploadVehicleImage()
    {
        //dbg::m('upload image ajax');
        //dbg::m('POST :'.print_r($_POST, true));
        //dbg::m('FILES :'.print_r($_FILES, true));
        
        $new_image = Tools::fileAttachment('file', true);
        if (Tools::isSubmit('customer_id') && Tools::isSubmit('garage_id') && !is_null($new_image))
        {
            $default_image = __PS_BASE_URI__.'modules/'.$this->module->name.'/views/img/no_bike_image.png';
            $path_to_save = __PS_BASE_URI__.'modules/'.$this->module->name.'/users-garage/';
            $garage_id = Tools::getValue('garage_id');
            $customer_id = Tools::getValue('customer_id');
            
            //Check file size
            if($new_image['size']  == 0 || ($new_image['size'] / 1024 / 1024) > 3)
            {
                $this->setErrorHeaders('Max image size exceeded', ['image_path' => $default_image]);
            }
            
            //check if image is correct type
            $file_type = strtolower(pathinfo('/'.$new_image["name"],PATHINFO_EXTENSION) ?? '');
            $accepted_types = ['jpg', 'jpeg', 'png'];
            
            if(!in_array($file_type,$accepted_types))
            {
                $this->setErrorHeaders('File type must be one of the following: '.implode(', ', $accepted_types), ['image_path' => $default_image]);
            }

            
            //Move file from tmp to module location, will overwrite file if exists
            $file_name = $customer_id.'_'.$garage_id.'.'.$file_type;
            $full_path = _PS_CORE_DIR_.$path_to_save.$file_name;
            
            //If image path set delete old image from disk
            $garage_vehicle = Shoplync_bike_model_filter::GetCustomerGarageVehicle($customer_id ,$garage_id);
            if(!empty($garage_vehicle) && array_key_exists('image_path', $garage_vehicle) && !empty($garage_vehicle['image_path']))
                Shoplync_bike_model_filter::deleteImage($garage_vehicle['image_path']);
            
            dbg::m('full path: '.$full_path);
            if(move_uploaded_file($new_image["tmp_name"], $full_path))
            {
                dbg::m('moved');
                //resize image to 1000px max width
                if(Shoplync_bike_model_filter::resizeImage($full_path))
                {
                    dbg::m('resized');
                    //save path/link inside of db for ps_ca_garage cust id + garage_ id
                    $sql = 'UPDATE `' . _DB_PREFIX_ . 'garage` SET image_path = "'.$path_to_save.$file_name.'" '
                        .'WHERE customer_id = '.$customer_id.' AND garage_id = '.$garage_id;
                    
                    if(Db::getInstance()->execute($sql) == FALSE)
                    {
                        dbg::m('SQL Query Failed: '.$sql);
                        $this->setErrorHeaders('SQL Query Failed', ['image_path' => $default_image]);
                    }
                    dbg::m('relative_path: '.$path_to_save.$file_name);
                    
                    //js will use returned path/link to update the image
                    $this->ajaxDie(json_encode(['success' => 'Image Saved!', 'image_path' => ''.$path_to_save.$file_name]));
                }
            }
        }
        else 
        {
            $this->setErrorHeaders('Image not updated, please try again.', ['image_path' => $default_image]);
        }
    }
   
/**
* ==============================
* General Helper Functions
* ==============================
*/   
    /**
    * Helper function that will retrieve all associated vehicle makes based on type
    *
    * $type_id int - The type_id to retrive vehicle makes from
    *
    * return array - result from db
    */ 
    protected function GetMakesByType($type_id)
    {
        if(isset($type_id) && is_numeric($type_id))
        {
            $sql = 'SELECT mk.make_id, mk.name FROM `' . _DB_PREFIX_ . 'make` AS mk LEFT JOIN `' . _DB_PREFIX_ . 'model` AS m ON mk.make_id = m.make_id '.($type_id > 0 ? 'WHERE m.id_type = '.$type_id : '').' GROUP BY mk.make_id ORDER BY mk.name ASC;';
            
            $result = Db::getInstance()->executeS($sql);
            
            return $result;
        }
        return [];
    }
    /**
    * Helper function that will retrieve all associated vehicle makes based on type
    *
    * $make_id int - Used to retrieve all vehicle models associated with the specified make 
    * $filter_by_type_id int - Filter the make/model subset by a particular vehicle type
    *
    * return array - result from db
    */ 
    protected function GetModelsByMake($make_id, $filter_by_type_id = null)
    {
        if(isset($make_id) && is_numeric($make_id))
        {
            $sql = 'SELECT model_id, name FROM `' . _DB_PREFIX_ . 'model` WHERE make_id = '.$make_id.($filter_by_type_id != null && is_numeric($filter_by_type_id) ? ' AND id_type = '.$filter_by_type_id : '').' ORDER BY name ASC';
            $result = Db::getInstance()->executeS($sql);
            
            return $result;
        }
        return [];
    }
    /**
    * Helper function that will retrieve all associated vehicle years associated with vehicle model
    *
    * $model_id int - Used to retrieve all vehicle years associated with the specified model 
    *
    * return array - result from db
    */ 
    protected function GetYearsFromModel($model_id)
    {
        if(isset($model_id) && is_numeric($model_id))
        {
            //SELECT v.vehicle_id, y.year FROM ps_ca_vehicle AS v LEFT JOIN ps_ca_year AS y ON v.id_year = y.id_year WHERE v.model_id = 18 ORDER BY y.year DESC;
            $sql = 'SELECT y.id_year, y.year, v.vehicle_id FROM `' . _DB_PREFIX_ . 'vehicle` AS v LEFT JOIN `' . _DB_PREFIX_ . 'year` AS y ON v.id_year = y.id_year WHERE v.model_id = '.$model_id.' ORDER BY y.year DESC';
            $result = Db::getInstance()->executeS($sql);
            
            return $result;
        }
        return [];
    }
    /**
    * Helper function that calls GenerateList(), ensures the passed array is not empty
    *
    * $result array - array of array objects to be converted into a HTML5 <select> list
    *
     * return string - Raw HTML5 as a string (Not escaped) or empty ''
    */ 
    protected function GenerateList($result)
    {
        if(is_array($result) && !empty($result))
        {
            return Shoplync_bike_model_filter::GenerateList($result, "-", '', '', true, '', true);
        }
        return '';
    }
    
    
}