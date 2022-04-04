<!-- shoplync_bike_model_filter - Block customer account -->
{extends file='page.tpl'}
{block name='page_content'}


{assign var=default_image value=$module_dir|escape:'html':'UTF-8'|cat:'/views/img/no_bike_image.png'}
<script type="text/javascript"> 
    window.default_vehicle_image = "{$default_image}";
    window.new_entry_image = '<img class="bike-model-image replace-2x img-responsive" src="'+default_vehicle_image+'" alt="" title=""><input class="bike-picture-upload hidden" type="file" name="bike_picture" accept=".jpg, .jpeg, .png" onchange="EditImage(this, true)"><button type="button" class="btn btn-primary" onclick="EditImage(this)">Change</button>';
    window.new_entry_actions = ' <button type="button" class="btn btn-primary prefered-btn" title="Set As Prefered" onclick="MakePrefered(this)"><i class="material-icons">check_circle</i></button>'
        + ' <button type="button" class="btn btn-primary edit-button" title="Edit Entry" onclick="EditEntry(this)"><i class="material-icons">edit</i></button>'
        + ' <button type="button" class="btn btn-primary" title="Delete This Entry" onclick="DeleteEntry(this)"><i class="material-icons">delete</i></button>';
</script>
<div id="my-garage">
    <h1 class="h1 title">
        {l s='Garage' mod='shoplync_bike_model_filter'}
    </h1>
    <div class="clr_10"></div>
    
    <section id="content" class="page-content">
        <h3 class="h3">{l s='Prefered Vehicle' mod='shoplync_bike_model_filter'}</h3>
        <div class="clr_10"></div>
        {if !isset($preferedBikeModel)}
            <div class="alert alert-warning my-1" id="noProductsInList">
                {l s='Please set a prefered vehicle model from your garage.' mod='Shoplync_bike_model_filter'}
            </div>
            {assign var=preferedBikeModel value=['vehicle_name'=>'','model_name'=>'', 'garage_id'=>'', 'image_path'=>'']}
            {assign var=hidden value='hidden'}
        {else}
            {assign var=hidden value=''}
        {/if}            
        <div class="prefered-container {$hidden}">
            <div class="left-block"> 
                <div class="product-image-container text-center"> 
                    <img id="preferedImg" class="bike-model-image replace-2x img-responsive" src="{if isset($preferedBikeModel.image_path) && $preferedBikeModel.image_path != ''} {$preferedBikeModel.image_path} {else}{$default_image}{/if}" alt="{$preferedBikeModel.vehicle_name}" title="{$preferedBikeModel.vehicle_name}"> 
                </div>
            </div>
            <div class="right-block py-2">
                <div class="product-description">
                    <h5 class="text-capitalize"><span id="preferedName" class="vehicle-name-text">{$preferedBikeModel.vehicle_name}</span> <em style="font-weight:normal;">(Prefered)</em></h5>
                    <p id="preferedModel" class="model-desc">Model: {$preferedBikeModel.model_name}</p>
                    <span id="preferedGarageID" class="garage hidden"><span class="garage-id">{$preferedBikeModel.garage_id}</span></span>
                    <input class="new-vehicle-name hidden d-block" type="text" name="new_vehicle_name" value="" placeholder="Enter A New Name">
                    <button type="button" class="btn btn-primary new-vehicle-name hidden" title="Cancel" onclick="editPreferedName(this, false)"><i class="material-icons">close</i></button>
                    <button type="button" class="btn btn-primary new-vehicle-name hidden" title="Save" onclick="EditEntry(this, true, false);"><i class="material-icons">check</i></button>
                    <button href="#" class="btn btn-primary d-block edit-prefered-name" onclick="editPreferedName(this)">Edit Name</button>
                </div>
                <div class="product-bottom">
                </div>
            </div>
        </div>
        <div class="clr_hr clearfix"></div>
        <div class="clr_10"></div>
        <div id="ListContainer">
            <!-- Vehicle Model List -->
            <h1>Manage Your Garage</h1>
            <button type="button" class="btn btn-primary" onclick="AddModel()">+ Add A New Model</button>
            <div class="prefered-container hidden p-1 mt-1" id="add-a-bike">
                <h1 class="text-center">Add To Garage</h1>
                <span class="control-label">Name</span>
                <input type="text" id="vehicle_name" class="form-control" placeholder="My Track Bike">
                <span class="control-label">Make</span>
                {$makeList|unescape: "html" nofilter}
                <span class="control-label">Model</span>
                <select id="selectModel" class="form-control " name="-" size="1" onchange="modelChanged(event, this)" disabled>
                    <option selected="" value="">-</option>
                </select>
                <span class="control-label">Year</span>
                <select id="selectYear" class="form-control " name="-" size="1" onchange="yearChanged(event, this)" disabled>
                    <option selected="" value="">-</option>
                </select>
                <button type="button" class="btn btn-primary" onclick="SaveModel()">Save</button>
                <button type="button" class="btn btn-primary" onclick="AddModel(false)">Cancel</button>
            </div>
        {if isset($productDetailsList)}                     
            <table id="vehicle-model-table" class="table my-1">
            <thead class="thead-default">
                <tr class="column-headers">
                    <th class="hidden" scope="col">ID</th>
                    <th class="hidden-sm-down" scope="col"></th>
                    <th scope="col">Name</th>
                    <th class="hidden-md-down" scope="col">Model</th>
                    <th scope="col"></th>
                </tr>
            </thead>
            <tbody>
                {foreach from=$productDetailsList item=model name=i}
                <tr {if isset($model.is_prefered) && $model.is_prefered > 0} id="prefered" {/if}>
                    <td id="garage-{$model.garage_id}" class="hidden garage"><span class="garage-id">{$model.garage_id}</span></td>
                    <td class="text-center align-middle hidden-sm-down">
                        <img class="bike-model-image replace-2x img-responsive" src="{if !empty($model.image_path)} {$model.image_path} {else}{$default_image}{/if}" alt="{*$preferedBikeModelName*}" title="{$model.vehicle_name}">
                        <input class="bike-picture-upload hidden" type="file" name="bike_picture" accept=".jpg, .jpeg, .png" onchange="EditImage(this, true)" />
                        <button type="button" class="btn btn-primary" onclick="EditImage(this)">Change</button>
                    </td>
                    <td class="align-middle vehicle-name">
                        <span class="vehicle-name-text">{$model.vehicle_name}</span>
                        <input class="new-vehicle-name hidden" type="text" name="new_vehicle_name" value="" placeholder="Enter A New Name">
                        <button type="button" class="btn btn-primary new-vehicle-name hidden" title="Cancel" onclick="toggleEditVehicleName(this, false)"><i class="material-icons">close</i></button>
                        <button type="button" class="btn btn-primary new-vehicle-name hidden" title="Save" onclick="EditEntry(this, true)"><i class="material-icons">check</i></button>
                    </td>
                    <td class="align-middle model-name hidden-md-down">{$model.model_name}</td>
                    <td class="action-buttons text-center align-middle">
                        <button type="button" class="btn btn-primary prefered-btn" title="Set As Prefered" onclick="MakePrefered(this)" {if isset($model.is_prefered) && $model.is_prefered > 0} disabled {/if}><i class="material-icons">check_circle</i></button>
                        <button type="button" class="btn btn-primary edit-button" title="Edit Entry" onclick="EditEntry(this)" {if isset($model.is_prefered) && $model.is_prefered > 0} disabled {/if}><i class="material-icons">edit</i></button>
                        <button type="button" class="btn btn-primary" title="Delete This Entry" onclick="DeleteEntry(this)"><i class="material-icons">delete</i></button>
                    </td>
                </tr> 
                {/foreach}  
            </tbody>
            </table>
        {else}
            <div class="alert alert-warning my-1" id="noProductsInList">
                {l s='Your garage is empty, please add a bike model.' mod='Shoplync_bike_model_filter'}
            </div>
        {/if}
            <button type="button" class="btn btn-primary" onclick="AddModel()">+ Add A New Model</button>
        </div>
    </section>
   
   {* <footer class="page-footer">
    <a href="{$sMyAccountLink|escape:'htmlall':'UTF-8'}" class="account-link">
        <i class="material-icons">&#xE5CB;</i>
        <span>{l s='Back to Your Account' mod='shoplync_bike_model_filter'}</span>
    </a>
    <a href="{$sBASE_URI|escape:'htmlall':'UTF-8'}" class="account-link">
        <i class="material-icons">&#xE88A;</i>
        <span>{l s='Home' mod='shoplync_bike_model_filter'}</span>
    </a>
    </footer>*}
</div>

{/block}
<!-- /Shoplync_bike_model_filter - Block customer account -->