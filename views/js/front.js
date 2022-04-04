/**
* 2007-2022 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2022 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*
* Don't forget to prefix your containers with your own identifier
* to avoid any conflicts with others containers.
*/

/*
 * This function will output out debug messages to console, 
 * Used to master enable/disabled all messages
*/
const showDebug = true;
function dbg($msg, forceMsg = false)
{
    if(showDebug || forceMsg)
        console.log($msg);
}

/**
* ====================================
* Filter/Search Page
* ====================================
*/
const makeChangedEvent = new CustomEvent('makeChangedEvent', { bubbles: true, detail: true });
const modelsUpdatedEvent = new CustomEvent('modelsUpdatedEvent', { bubbles: true, detail: true });
const yearsUpdatedEvent = new CustomEvent('yearsUpdatedEvent', { bubbles: true, detail: true });
const filterAvailableEvent = new CustomEvent('filterAvailableEvent', { bubbles: true, detail: true });

var currentType = null;
var currentBrand = null;
var currentMakeValue = null;
var currentModelValue = null;
var currentYearValue = null;
var currentUniversal = null;
var currentFluids = null;

/**
 * Will attempt to save the current filter options to a global variable, reset if page is refreshed
 *
 * saveMMY boolean - Whether to save Make/Model/Year value if set
 * saveType boolean - Whether to save type value if set
 * saveBrands boolean - Whether to save brands value if set
 * saveCheckboxes boolean - Whether to save checkboxes value if set
 *
*/
function saveCurrentFilter(saveMMY = true, saveType = false, saveBrands = true, saveCheckboxes = true)
{
    if(saveType)
    {
        var type = document.getElementById('selectType').value;
        if(type)
            window.currentType = type;
    }

    if(saveBrands)
    {
        var brands = document.getElementById('selectBrand').value;
        if(brands)
            window.currentBrand = brands;
    }
    
    if(saveMMY)
    {
        var make = document.getElementById('selectMake').value;
        if(make)
            window.currentMakeValue = make;
        
        var model = document.getElementById('selectModel').value;
        if(model)
            window.currentModelValue = model;
        
        var year = document.getElementById('selectYear').value;
        if(year)
            window.currentYearValue = year;
    }
    
    if(saveCheckboxes)
    {
        var universal = document.getElementById('universalFitment');
        if(universal)
            window.currentUniversal = universal.checked;
        
        var fluids = document.getElementById('universalFluids');
        if(fluids)
            window.currentFluids = fluids.checked;   
    }   
}
/**
 * Will attempt to restore the globallly saved filter variables, if set
 *
 * saveMMY boolean - Whether to restore Make/Model/Year value if set
 * saveType boolean - Whether to restore type value if set
 * saveBrands boolean - Whether to restore brands value if set
 * saveCheckboxes boolean - Whether to restore checkboxes value if set
 *
*/
function restoreCurrentFilter(restoreMMY = true, restoreType = false, restoreBrands = true, restoreCheckboxes = true)
{
    if(restoreType)
    {
        var type = document.getElementById('selectType');
        if(type)
            setOptionValueIfExits(type, window.currentType);
    }

    if(restoreBrands)
    {
        var brands = document.getElementById('selectBrand');
        if(brands)
            setOptionValueIfExits(type, window.currentBrand);
    }
    if(restoreMMY)
    {
        var model = document.getElementById('selectModel');
        if(model)
            setModelsOnReady();
        
        var year = document.getElementById('selectYear');
        if(year)
            setYearsOnReady();
        
        var make = document.getElementById('selectMake');
        if(make)
            setOptionValueIfExits(make, window.currentMakeValue);
    }

    
    if(restoreCheckboxes)
    {
        var universal = document.getElementById('universalFitment');
        if(universal)
            universal.checked = window.currentUniversal;
        
        var fluids = document.getElementById('universalFluids');
        if(fluids)
            fluids.checked = window.currentFluids;   
    }  
}

/**
 * Will send AJAX reques to the server to clear stored filter setting cookies
 * If current_page is a search page, will reload the same search query without filter
 * Other wise will redirect back to homepage
*/
function clearFilterCookie()
{
    $.ajax({
        type: 'POST',
        cache: false,
        dataType: 'json',
        url: adminajax_link, 
        data: {
            ajax: true,
            action: 'clearSelectionCookie',//lowercase with action name
        },
        success : function (data) {
            if(data && data.success)
            {
                dbg('Cookies Cleared', true);
                if(document.getElementById('filter-form'))
                    resetFilter();
                
                //check if we are on the search page, if yes re-do with native search and pass param S
                if(document.getElementsByTagName('body')[0].id == 'fitmentsearch')
                {   
                    var searchWidgets = document.querySelectorAll('#search_widget form');
                    if(searchWidgets)
                    {
                        for (var i = 0, len = searchWidgets.length; i < len; i++) {
                            var searchBar = searchWidgets[i].querySelector('input[name="s"]');
                            if(searchBar && searchBar.value)
                                searchWidgets[i].submit();
                        }
                    }                   
                    else if(prestashop.urls.base_url)
                        window.location.assign(prestashop.urls.base_url);
                }
                
            }
        },
        error : function (data){
            dbg('FAILED');
            dbg(data);
        }
    });
}

/**
 * Will query the database via AJAX and retrieve make/model/year from given a vehicle id
 * then set the filter accordingly
 */
function setFilterFromVehicleID(thisElement = null, vehicle_id = null)
{
    if ((thisElement != null && thisElement.value) || vehicle_id != null)
    {
        var new_vehicle_id = (thisElement != null && thisElement.value) ? thisElement.value : vehicle_id;
        //reset filter, but save the brands/universal/fluids
        saveCurrentFilter(false, false, true, true);
        resetFilter();
        restoreCurrentFilter(false, false, true, true);
        
        $.ajax({
            type: 'POST',
            cache: false,
            dataType: 'json',
            url: adminajax_link, 
            data: {
                ajax: true,
                action: 'getVehicleDetails',//lowercase with action name
                vehicle_id: new_vehicle_id
            },
            success : function (data) {
                if(data)
                {
                    dbg('Presetting filter settings', true);
                    var make = document.getElementById('selectMake');
                    var model = document.getElementById('selectModel');
                    var year = document.getElementById('selectYear');
                    
                    if(make && model && year)
                    {
                        //Gotta do the bubbles event handler here
                        setOptionValueIfExits(make, data.make_id);
                        make.disabled = false;
                        
                        setModelsOnReady(data.model_id);
                        model.disabled = false;
                        
                        setYearsOnReady(data.year_id);
                        year.disabled = false;
                    }
                    if(thisElement)
                        thisElement.value = '';
                }
            },
            error : function (data){
                dbg('FAILED');
                dbg(data);
            }
        }); 
    }
}

/**
 * Will set the years <select> element value after the event 'yearsUpdatedEvent' is heared, Value is only set if it exists as an <option>
 *
 * newValue - if this value is null it will attempt to use the global variable currentYearValue
*/
function setYearsOnReady(newValue = null)
{
    if(newValue)
        window.currentYearValue = newValue;
    
    document.addEventListener('yearsUpdatedEvent', function(e) {
        dbg('yearsUpdatedEvent - Attempting to restore value');
        var selYears = document.getElementById('selectYear');
        if(selYears)
        {
            setOptionValueIfExits(selYears, currentYearValue);
            setFilterButtonDisabled(false, true);
        }
    });   
}
/**
 * Will set the model <select> element value after the event 'modelsUpdatedEvent' is heared, Value is only set if it exists as an <option>
 *
 * newValue - if this value is null it will attempt to use the global variable currentModelValue
*/
function setModelsOnReady(newValue = null)
{
    if(newValue)
        window.currentModelValue = newValue;
    
    document.addEventListener('modelsUpdatedEvent', function(e) {
        dbg('modelsUpdatedEvent - Attempting to restore value');
        var selModels = document.getElementById('selectModel');
        if(selModels)
            setOptionValueIfExits(selModels, currentModelValue);
    });
}
/**
 * Will retrieve an array of all the values contained within a <select> element
 *
 * selectElement <element> - The <select> element to retrieve option value from
 *
 * return array - An array of all the option values, extracted from <select> element
*/
function getOptionValues(selectElement = null)
{
    if(selectElement && selectElement.options)
    {
        var values_array = [];
        for (let i = 0; i < selectElement.options.length; i++) { 
          values_array.push(selectElement.options[i].value);
        }
        return values_array;
    }
    return [];
}
/**
 * Will Attempt to set the value of a <select> element IF the value is exists as a valid <option>
 *
 * selectElement <element> - The <select> element to set the value for
 * newValue - The value that will be set
 * triggerOnchange boolean - Whether to trigger the OnChange event handler on the element
 *
*/
function setOptionValueIfExits(selectElement = null, newValue = null, triggerOnchange = true)
{
    dbg('updated event');
    if(selectElement && newValue)
    {
        var optionsVal = getOptionValues(selectElement);
        dbg('select and new value is values');
        if(optionsVal.includes(newValue))
        {    
            dbg('inside if in option updater');
            selectElement.value = newValue;
            if(triggerOnchange)
                selectElement.dispatchEvent(new Event('change', { 'bubbles': true }));
        }
        else
            selectElement.focus();
    }
}
/**
 * This helper function allows the filter button to be enabled/disabled and to change focus to it
 *
 * isDisabled boolean - Whether the button should is disabled or not
 * setFocus boolean - Whether to trigger the pages focus to this button after enabled
 * btnID string - the id of the corresponding filter button
 *
*/
function setFilterButtonDisabled(isDisabled = false, setFocus = false, btnID = 'modelFilterButton')
{
    var filterBtn = document.getElementById(btnID);
    if(filterBtn)
    {
        filterBtn.disabled = isDisabled;
        if(!isDisabled)
        {
            dbg('Filter Button Enabled');
            filterBtn.dispatchEvent(filterAvailableEvent); 
            if(setFocus)
                filterBtn.focus();
        }
        else
            dbg('Filter Button Disabled');
    }
}
/**
* Called every time the type select option is changed
* and retrives sub-models for the type and populates the <select> element
*/
function typeChanged(e, thisElement)
{
    dbg("Type changed");
    if (thisElement != null && thisElement.value){
        setFilterButtonDisabled(false);
        
        //Save current selection and then attempt to reselect if possible
        saveCurrentFilter(true, false, false, false);
        
        //clear make / model / year first
        var make = document.getElementById('selectMake');
        make.disabled = true;
        removeOptions(make, 1);
        
        var model = document.getElementById('selectModel');
        model.disabled = true;
        removeOptions(model, 1);

        var years = document.getElementById('selectYear');
        years.disabled = true;
        removeOptions(years, 1);
        
        //send ajax request to get makes
        $.ajax({
            type: 'POST',
            cache: false,
            dataType: 'json',
            url: adminajax_link, 
            data: {
                ajax: true,
                action: 'getMakes',//lowercase with action name
                type_id: thisElement.value
            },
            success : function (data) {
                if(data)
                {
                    dbg('Please Select A Make');
                    var makes = document.getElementById('selectMake');
                    makes.innerHTML = data.makes;
                    makes.disabled = false;
                    
                    //will listen for our custom event to be emitted then will set the new value if it exists
                    restoreCurrentFilter(true, false, false, false);
                    
                    setFilterButtonDisabled(true);
                }
            },
            error : function (data){
                dbg('FAILED');
                dbg(data);
            }
        });   
        
    }
    else
        setFilterButtonDisabled(true);
    //check is make / model / year has value if not the disabled btn
    if(!(make.value && model.value && years.model))
        setFilterButtonDisabled(true);
}

/**
* Called every time the make select option is changed
* and retrives models for the make and populates the <select> element
*/
function makeChanged(e, thisElement) {
    dbg("Make changed");
    if (thisElement !== null) {
        var selectType = document.getElementById('selectType');
        if(thisElement.value) {
            $.ajax({
                type: 'POST',
                cache: false,
                dataType: 'json',
                url: adminajax_link, 
                data: {
                    ajax: true,
                    action: 'getModels',//lowercase with action name
                    make_id: thisElement.value,
                    type_id: (selectType && selectType.value ? selectType.value : null),
                },
                success : function (data) {
                    if(data)
                    {
                        dbg('Please Select A Model');
                        var model = document.getElementById('selectModel');
                        model.innerHTML = data.models;
                        model.disabled = false;
                        
                        setFilterButtonDisabled(true);
                        
                        thisElement.dispatchEvent(modelsUpdatedEvent);
                    }
                },
                error : function (data){
                    dbg('FAILED');
                    dbg(data);
                }
            });            
        }
        else 
        {
            var model = document.getElementById('selectModel');
            model.disabled = true;
            removeOptions(model, 1);

            var years = document.getElementById('selectYear');
            years.disabled = true;
            removeOptions(years, 1);

            if(selectType.value)
                setFilterButtonDisabled(false);
            else
                setFilterButtonDisabled(true);
        }
    }
}

/**
* Called every time the model select option is changed, 
* and retrives years for the model and populates the <select> element
*/
function modelChanged(e, thisElement)
{
    dbg("Make changed");
    if (thisElement != null){
        if(thisElement.value)
        {
            $.ajax({
                type: 'POST',
                cache: false,
                dataType: 'json',
                url: adminajax_link, 
                data: {
                    ajax: true,
                    action: 'getYears',//lowercase with action name
                    model_id: thisElement.value
                },
                success : function (data) {
                    if(data)
                    {
                        dbg('Please Select A Year');
                        var year = document.getElementById('selectYear');
                        year.innerHTML = data.years;
                        year.disabled = false;
                        
                        setFilterButtonDisabled(true);
                        
                        thisElement.dispatchEvent(yearsUpdatedEvent);
                    }
                },
                error : function (data){
                    dbg('FAILED');
                    dbg(data);
                }
            });            
        }
        else 
        {
            var years = document.getElementById('selectYear');
            years.disabled = true;
            removeOptions(years, 1);

            setFilterButtonDisabled(true);
        }
    }
}

/**
* Called every time the year select option is changed, and enabled/disabled filter button
*/
function yearChanged(e, thisElement)
{
    dbg("Year changed");

    if (thisElement != null && thisElement.value){
        setFilterButtonDisabled(false);
    }
    else
        setFilterButtonDisabled(true);
}


/**
* Deletes a <select> elements inner <option>, can be stoped at a certain index
*
* stopAt int - Which index to stop deleting <option>
*/
function removeOptions(selectElement, stopAt = 0) {
   var i, L = selectElement.options.length - 1;
   for(i = L; i >= stopAt; i--) {
      selectElement.remove(i);
   }
}
/**
* Clears All Filter Parameters
*/
function resetFilter() 
{
    document.getElementById('selectBrand').value = '';
    
    var selectType = document.getElementById('selectType');
    if(selectType && selectType.value)
    {
        //restore makes
        //send ajax request to get makes
        $.ajax({
            type: 'POST',
            cache: false,
            dataType: 'json',
            url: adminajax_link, 
            data: {
                ajax: true,
                action: 'getMakes',//lowercase with action name
                type_id: 0
            },
            success : function (data) {
                if(data)
                {
                    dbg('Please Select A Make');
                    var makes = document.getElementById('selectMake');
                    makes.innerHTML = data.makes;
                    makes.disabled = false;
                }
            },
            error : function (data){
                dbg('FAILED');
                dbg(data);
            }
        });
    }
    
    selectType.value = '';
    
    resetMainDropdown();

    setFilterButtonDisabled(true);
    document.getElementById('universalFitment').checked = false;
    document.getElementById('universalFluids').checked = false;
    
    var garageSelect = document.getElementById('selectGarageBike');
    if(garageSelect)
        garageSelect.value = '';
    
}
/**
* Only Resets Make/Model/Year filter elements
*/
function resetMainDropdown()
{
    document.getElementById('selectMake').value = '';
    document.getElementById('selectMake').disabled = false;
    
    var model = document.getElementById('selectModel');
    model.disabled = true;
    removeOptions(model, 1);
    
    var years = document.getElementById('selectYear');
    years.disabled = true;
    removeOptions(years, 1);
}

/**
 * This helper function allow you toggle an elements class, Will only toggle the first occurence of the query selector
*/
function toggleClass(theSelector, theClass)
{
    var theElement = document.querySelector(theSelector);
    if(theElement && theElement.classList.contains(theClass))
    {
        theElement.classList.remove(theClass);
    }
    else
    {
        theElement.classList.add(theClass);
    }
}

/**
* ========================================
* Customers My Garage Page
* ========================================
*/

/**
* This shows/hides the add a bike model form
*/
function AddModel(visible = true)
{
    if(!visible)
    {
        document.getElementById('add-a-bike').classList.add('hidden');
        //reset filter
        document.getElementById('vehicle_name').value = '';
        resetMainDropdown();
        return;
    }
    //scroll to element
    document.getElementById('add-a-bike').classList.remove('hidden');
    
    var vehicleNameInput = document.getElementById('vehicle_name');
    vehicleNameInput.scrollIntoView({behavior: 'smooth', block: 'end', inline: 'nearest'});
    vehicleNameInput.focus();
}

/**
* This sends an AJAX request, to the server to save the corresponding garage vehicle model,
*/
function SaveModel()
{
    var vehicleName = document.getElementById('vehicle_name');
    var vehicleMake = document.getElementById('selectMake');
    var vehicleModel = document.getElementById('selectModel');
    var vehicleYear = document.getElementById('selectYear');
    
    if(vehicleMake && vehicleModel && vehicleYear)
    {
        var vName = vehicleName.value;
        var vehicleModelName = vehicleYear.selectedOptions[0].text + ' ' + vehicleMake.selectedOptions[0].text + ' ' + vehicleModel.selectedOptions[0].text;
        
        if(vName === "")
            vName = vehicleModelName;
        
        dbg('Saving Model...');
    
        $.ajax({
            type: 'POST',
            cache: false,
            dataType: 'json',
            url: adminajax_link, 
            data: {
                ajax: true,
                action: 'addToGarage',//lowercase with action name
                model_id: vehicleModel.value,
                year_id: vehicleYear.value,
                vehicle_name: vName,
                customer_id: ps_customer_id
            },
            success : function (data) {
                if(data && data.success)
                {
                    dbg('Completed addition to garage');
                    //dbg(data);
                    var table = document.getElementById('vehicle-model-table');
                    if(!table)
                        window.location.reload();
                    
                    var row = table.insertRow(-1);
                    
                    var cell0 = row.insertCell(0);
                    cell0.classList.add('hidden', 'garage');
                    cell0.id = (data.garage_id ? data.garage_id : '');
                    cell0.innerHTML = '<span class="garage-id">'+(data.garage_id ? data.garage_id : '')+'</span>';
                    var cell1 = row.insertCell(1);
                    cell1.classList.add('text-center', 'align-middle');
                    cell1.innerHTML = new_entry_image;
                    var cell2 = row.insertCell(2);
                    cell2.classList.add('align-middle', 'vehicle-name');
                    cell2.innerHTML = vName;
                    var cell3 = row.insertCell(3);
                    cell3.classList.add('align-middle', 'model-name');
                    cell3.innerHTML = vehicleModelName;
                    var cell4 = row.insertCell(4);
                    cell4.classList.add('action-buttons', 'text-center', 'align-middle');
                    cell4.innerHTML = new_entry_actions;
                    
                    //close add model
                    AddModel(false);
                }
                else
                {
                    alert('Could Not Add To Garage, Please Try Again');
                    window.location.reload();
                }
            },
            error : function (data){
                dbg('FAILED');
                dbg(data);
            }
        });
    }
}

/**
* Changes the state of all prefered-btn to enabled/disabled
*
* state boolean - Whether to set the button to enabled/disabled
* selector string - The button selector to target
*/
function ChangePreferedBtn(state = false, selector = 'button.prefered-btn')
{
    var btns = document.querySelectorAll(selector);
    if(btns)
    {
        for (var i = 0, len = btns.length; i < len; i++) {
            btns[i].disabled = state;
        }
    }
}

/**
* Sends an AJAX request to the server to update the customers garage, prefered vehicle
* This is displayed inside a product add-adittional info
*/
function preferedChanged(currentSelect)
{
    if(currentSelect)
    {
        var garage_id = document.getElementById('selectPrefered').value;
        $.ajax({
            type: 'POST',
            cache: false,
            dataType: 'json',
            url: adminajax_link, 
            data: {
                ajax: true,
                action: 'updatePrefered',//lowercase with action name
                garage_id: garage_id,
                customer_id: ps_customer_id
            },
            success : function (data) {
                if(data && data.success)
                {
                    dbg('Fitment Prefere Changed');
                    //Reloads AJAX Vehicle Fitment
                    var productCombinationSelect = document.querySelector('.product-variants-item select');
                    if(productCombinationSelect)
                        productCombinationSelect.dispatchEvent(new Event('change', { 'bubbles': true }));
                    else//no combinations
                        location.reload();
                }
            },
            error : function (data){
                dbg('FAILED');
                dbg(data);
            }
        }); 
    }
}

/**
* Sends an AJAX request to the server to update the customers garage, prefered vehicle
* Displayed in the customers account dashboard
*/
function MakePrefered(currentBtn)
{
    if(currentBtn)
    {
        var tr = currentBtn.parentNode.parentNode;
        var td = tr.querySelector('.garage');
        if(td)
        {
            var garageID = td.querySelector('.garage-id').innerHTML;
            
            $.ajax({
                type: 'POST',
                cache: false,
                dataType: 'json',
                url: adminajax_link, 
                data: {
                    ajax: true,
                    action: 'updatePrefered',//lowercase with action name
                    garage_id: garageID,
                    customer_id: ps_customer_id
                },
                success : function (data) {
                    if(data && data.success)
                    {
                        dbg('Prefered Changed');
                        ChangePreferedBtn();
                        ChangePreferedBtn(false, '.edit-button');
                        currentBtn.disabled = true;
                        currentBtn.parentNode.querySelector('.edit-button').disabled = true;
                        document.getElementById('prefered').setAttribute('id', '');
                        
                        currentBtn.parentNode.parentNode.setAttribute('id', 'prefered');
                        
                        document.getElementById('preferedGarageID').innerHTML = '<span class="garage-id">'+garageID+'</span>';
                        document.getElementById('preferedName').innerHTML = tr.querySelector('.vehicle-name').innerHTML;
                        document.getElementById('preferedModel').innerHTML = tr.querySelector('.model-name').innerHTML;
                        document.getElementById('preferedImg').src = tr.querySelector('.bike-model-image').src;
                    }
                },
                error : function (data){
                    dbg('FAILED');
                    dbg(data);
                }
            });  
        }
    }
}

/**
* Enables the functionality to properly display the edit capabilities inside a customers garage
*/
function toggleEditVehicleName(currentElement, state)
{
    if(currentElement)
    {
        var parentRow = currentElement.parentNode.parentNode;
        
        parentRow.querySelector('.edit-button').disabled = state;
        
        if(state)
            parentRow.querySelector('span.vehicle-name-text').classList.add('hidden');
        else
            parentRow.querySelector('span.vehicle-name-text').classList.remove('hidden');
        
        
        var editComponents = parentRow.querySelectorAll('.new-vehicle-name');
        if(editComponents)
        {
            parentRow.querySelector('input.new-vehicle-name').value = '';
            for (var i = 0, len = editComponents.length; i < len; i++) {
                if(state)
                    editComponents[i].classList.remove('hidden');
                else
                    editComponents[i].classList.add('hidden');
            }
        }
    }
}

/**
* This will call toggleEditVehicleName() and send an update AJAX request to the server to update the 
* customers garage vehicle information.
*/
function EditEntry(currentBtn, setNewName = false, toggle = true)
{
    if(currentBtn && !setNewName)
    {
        dbg('Edit Item');
        currentBtn.disabled = true;
        toggleEditVehicleName(currentBtn, true);
    }
    else if (currentBtn && setNewName)
    {
        var new_vehicle_name = currentBtn.parentNode.querySelector("input.new-vehicle-name");
        if(!new_vehicle_name.value)
        {
          new_vehicle_name.focus();
          return;
        }
        
        //Send AJAX Request
        var td = currentBtn.parentNode.parentNode.querySelector('.garage');
        if(td)
        {
            var garageID = td.querySelector('.garage-id').innerHTML;
            
            dbg('garageid: '+garageID+' new name: '+new_vehicle_name.value+' ps customer id'+ps_customer_id);
            
            $.ajax({
                type: 'POST',
                cache: false,
                dataType: 'json',
                url: adminajax_link, 
                data: {
                    ajax: true,
                    action: 'updateVehicleName',//lowercase with action name
                    garage_id: garageID,
                    customer_id: ps_customer_id,
                    vehicle_name: new_vehicle_name.value
                },
                success : function (data) {
                    if(data && data.success)
                    {
                        dbg('Vehicle Name Updated');
                        currentBtn.parentNode.querySelector('.vehicle-name-text').innerHTML = new_vehicle_name.value;
                        if(toggle)
                            toggleEditVehicleName(currentBtn, false);
                        else
                            window.location.reload();
                    }
                },
                error : function (data){
                    dbg('FAILED');
                    dbg(data);
                }
            });
        }
    }
}

/**
* Enables the functionality to properly display the edit prefered name capabilities inside a customers garage
*/
function editPreferedName(thisElement, state = true)
{
    if(thisElement)
    {
        var elementList = thisElement.parentNode.querySelectorAll('.new-vehicle-name');
        if(elementList)
        {
            thisElement.parentNode.querySelector('input.new-vehicle-name').value = '';
            for (var i = 0, len = elementList.length; i < len; i++) {
                if(state)
                    elementList[i].classList.remove('hidden');
                else
                    elementList[i].classList.add('hidden');
            }
        }
        if(state)
            thisElement.parentNode.querySelector('.edit-prefered-name').classList.add('hidden');
        else
            thisElement.parentNode.querySelector('.edit-prefered-name').classList.remove('hidden');
        
    }
}
/**
* Send AJAX request that delete the current vehicle from the customers garage
*/
function DeleteEntry(currentBtn)
{
   if(currentBtn)
   {
        var td = currentBtn.parentNode.parentNode.querySelector('.garage');
        if(td)
        {
            var garageID = td.querySelector('.garage-id').innerHTML;
            
            $.ajax({
                type: 'POST',
                cache: false,
                dataType: 'json',
                url: adminajax_link, 
                data: {
                    ajax: true,
                    action: 'deleteGarageId',//lowercase with action name
                    garage_id: garageID,
                    customer_id: ps_customer_id
                },
                success : function (data) {
                    if(data && data.success)
                    {
                        dbg('Entry Deleted');
                        if(data.prefered)
                        {
                            //new prefered has been set update the html elements
                            dbg(data.prefered);
                            
                            document.getElementById('preferedGarageID').innerHTML = '<span class="garage-id">'+data.prefered.garage_id+'</span>';
                            document.getElementById('preferedName').innerHTML = data.prefered.vehicle_name;
                            document.getElementById('preferedModel').innerHTML = 'Model: '+data.prefered.model_name;
                            document.getElementById('preferedImg').src = data.prefered.image_path !== null ? data.prefered.image_path : window.default_vehicle_image;
                            
                            //updateTable
                            ChangePreferedBtn();
                            ChangePreferedBtn(false, '.edit-button');
                            
                            var new_prefered = document.querySelector('#garage-'+data.prefered.garage_id);
                            if(new_prefered)
                            {
                                new_prefered.parentNode.querySelector('.prefered-btn').disabled = true;
                                new_prefered.parentNode.querySelector('.edit-button').disabled = true;
                                document.getElementById('prefered').setAttribute('id', '');
                                new_prefered.parentNode.setAttribute('id', 'prefered');
                            }
                        }
                        else
                            window.location.reload();
                    }
                },
                error : function (data){
                    dbg('FAILED');
                    dbg(data);
                }
            });
        }
       
        currentBtn.parentNode.parentNode.remove(); 
   }
}
/**
* Allows the user to upload a picture, for their vehicle saved inside their garage
*/
function EditImage(buttonElement, sendUpload = false)
{
    if(buttonElement && !sendUpload)
    {
        buttonElement.parentNode.querySelector('.bike-picture-upload').click();
    }
    else if(buttonElement && sendUpload)
    {
        //Send Ajax Request...etc
        dbg('Uploading photo');
        var garageID = buttonElement.parentNode.parentNode.querySelector('.garage-id');
        if(buttonElement.classList.contains('bike-picture-upload') && buttonElement.files.length > 0 && garageID)
        {
            if((buttonElement.files[0].size / 1024 / 1024) > 3){
                alert('Image Exceeds Max File Size Of 3 MB');
                return;
            }
                        
            var form_data = new FormData();
            form_data.append('ajax', true);
            form_data.append('action', 'uploadVehicleImage');
            form_data.append('customer_id', ps_customer_id);
            form_data.append('garage_id', garageID.innerHTML);
            form_data.append('file', buttonElement.files[0]);
            
            $.ajax({
                type: 'POST',
                cache: false,
                contentType: false,
                processData: false,
                url: adminajax_link, 
                data: form_data,
                success : function (data) {
                    if(data)
                    {
                        var dataObj = JSON.parse(data);
                        if(dataObj && dataObj.success)
                        {
                            dbg('Image Save Sucessfully');
                            if(dataObj.image_path)
                            {
                                buttonElement.parentNode.querySelector('.bike-model-image').src = dataObj.image_path;
                                if(buttonElement.parentNode.parentNode.id == 'prefered')
                                    document.getElementById('preferedImg').src = dataObj.image_path;
                            }

                        }
                    }
                },
                error : function (data){
                    dbg('FAILED');
                    dbg(data);
                    if(data.errorResponse)
                        alert(data.errorResponse);
                }
            });
        }
    }
}

/**
* ====================================
* Product Page
* ====================================
*/

/**
* Retrieves the product vehicle fitment given a particular make
*
* selectElement - The <select> element containing the make list
*/
function changeFitmentMake(selectElement)
{
    if(selectElement && selectElement.value)
    {
        $.ajax({
            type: 'POST',
            cache: false,
            dataType: 'json',
            url: adminajax_link, 
            data: {
                ajax: true,
                action: 'getFitmentModels',//lowercase with action name
                make_id: selectElement.value,
                product_id: ps_product_id,
                attribute_id: ps_attribute_id
            },
            success : function (data) {
                if(data && data.fitments)
                {
                    dbg('Fitments Fetched');
                    document.getElementById('fitment-body').innerHTML = data.fitments;                        
                }
            },
            error : function (data){
                dbg('FAILED');
                dbg(data);
            }
        });
    }
    else
        resetFitmentMake();
}

/**
* This resets the product fitment table, deletes are previous rows 
*/
function resetFitmentMake()
{
    var table_body = document.getElementById("fitment-body");
    if(table_body)
    {
        table_body.innerHTML = '<tr><td colspan="2" class="align-middle text-center font-weight-bold font-italic">No Product Fitment Found</td></tr>';
    }
}


/**
* Sends all the search parameters to the server
*/
function startFilter()
{
    //alert("No Products Found, bud");
    var brand = document.getElementById('selectBrand').value;
    var type = document.getElementById('selectType').value;
    var make = document.getElementById('selectMake').value;
    var model = document.getElementById('selectModel').value;
    var year = document.getElementById('selectYear').value;
    
    var includeUniversal = document.getElementById('universalFitment').checked;
    var includeFluids = document.getElementById('universalFluids').checked;
    
    $.ajax({
        type: 'POST',
        cache: false,
        dataType: 'json',
        url: adminajax_link, 
        data: {
            ajax: true,
            action: 'doSearch',//lowercase with action name
            brand_id: brand,
            type_id: type,
            make_id: make,
            model_id: model,
            year_id: year,
            universal: includeUniversal,
            fluids: includeFluids
        },
        success : function (data) {
            if(data)
            {
                dbg(data);
                //if filter settings are set correctly redirect
                //otherwise do not
            }
        },
        error : function (data){
            dbg('FAILED');
            dbg(data);
        }
    });
}