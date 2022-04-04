{*
* @author    Anthony Figueroa - Shoplync Inc <sales@shoplync.com>
* @copyright 2021 Shoplync
* @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
* @category  PrestaShop module
* @package   Bulk Purchase Order
*      International Registered Trademark & Property of Shopcreator
* @version   1.0.0
* @link      http://www.shoplync.com/
*}

<!-- MODULE Bike Model Filter -->
{if $psversion >= 1.7}
  <a class="col-lg-4 col-md-6 col-sm-6 col-xs-12" id="shoplync_bike_model_filter" href="{$link->getModuleLink('shoplync_bike_model_filter', 'mygarage', array(), true)|escape:'htmlall':'UTF-8'}" title="{l s='Users Garage' mod='shoplync_bike_model_filter'}">
  <span class="link-item">
  <i class="fa fa-motorcycle" style="padding-bottom:10px;"></i> 
  {l s='Garage' mod='shoplync_bike_model_filter'}
  </span>
  </a>
{else}
  <li class="lnk_bike_model_filter">
      <a href="{$link->getModuleLink('shoplync_bike_model_filter', 'mygarage', array(), true)|escape:'htmlall':'UTF-8'}" title="{l s='Users Garage' mod='shoplync_bike_model_filter'}">
          <i class="fa fa-motorcycle"></i> 
          <span>{l s='Garage' mod='shoplync_bike_model_filter'}</span>
      </a>
  </li>
{/if}
<!-- END : MODULE Bike Model Filter -->
