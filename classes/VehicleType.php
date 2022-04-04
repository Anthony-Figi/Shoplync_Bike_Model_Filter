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
class VehicleType extends ObjectModel {
 
    /** @var string Document reference */ 
    public $type_id;
 
     /** @var string description */ 
    public $parent_type_id;
 
    /** @var string name */ 
    public $name;
    
    /** @var string name */ 
    public $depth;
 
 
    /**
     * Definition of class parameters
     */ 
    public static $definition = array( 
        'table' => 'type', 
        'primary' => 'type_id', 
        'multilang' => false, 
        'multilang_shop' => false, 
        'fields' => array( 
            'type_id' => array('type' => self::TYPE_INT), 
            'parent_type_id' => array('type' => self::TYPE_INT), 
            'name' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 255), 
            'depth' => array('type' => self::TYPE_INT), 
        ), 
    );
 
    /**
     * Mapping of the class with the webservice
     *
     * @var type
     */ 
    protected  $webserviceParameters  =  [ 
        'objectsNodeName' => 'vehicle_types',  //objectsNodeName must be the value declared in hookAddWebserviceResources(entity list) 
        'objectNodeName' => 'vehicle_type',  // Detail of an entity 
        'fields' => [] 
    ]; 
}