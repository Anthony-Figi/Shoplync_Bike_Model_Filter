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
class VehicleModel extends ObjectModel {
 
    /** @var int model id reference */ 
    public $model_id;
 
    /** @var string name */ 
    public $name;
 
    /** @var int make id */ 
    public $make_id; 
    
    /** @var int type id */ 
    public $type_id;
 
    /**
     * Definition of class parameters
     */ 
    public static $definition = array( 
        'table' => 'model', 
        'primary' => 'model_id', 
        'multilang' => false, 
        'multilang_shop' => false, 
        'fields' => array( 
            'model_id' => array('type' => self::TYPE_INT), 
            'name' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 255), 
            'make_id' => array('type' => self::TYPE_INT), 
            'type_id' => array('type' => self::TYPE_INT), 
        ), 
    );
 
    /**
     * Mapping of the class with the webservice
     *
     * @var type
     */ 
    protected  $webserviceParameters  =  [ 
        'objectsNodeName' => 'vehicle_models',  //objectsNodeName must be the value declared in hookAddWebserviceResources(entity list) 
        'objectNodeName' => 'vehicle_model',  // Detail of an entity 
        'fields' => [] 
    ]; 
}