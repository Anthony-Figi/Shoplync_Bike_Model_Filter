{if !isset($fitmentList)}   
    {*assign var=fitmentList value=[['model_name'=>'GSXR-100','year'=>'2017'],['model_name'=>'GSXR-100','year'=>'2018'],['model_name'=>'GSXR-100','year'=>'2019'],['model_name'=>'GSXR-100','year'=>'2020']]*}
{/if}

<div class="tab-pane fade" id="fitment" role="tabpanel" aria-expanded="false">
    <h3 class="text-center">This Product Fits</h3>
    {$makeList|unescape: "html" nofilter}
    <table id="vehicle-fitment-tbl" class="table my-1">
    <thead class="thead-default">
        <tr class="column-headers ">
            <th scope="col" class="hidden">Vehicle ID</th>
            <th scope="col">Model</th>
            <th scope="col">Years</th>
        </tr>
    </thead>
    <tbody id="fitment-body">
        {if isset($productDetailsList)}    
        {foreach from=$fitmentList item=row name=i}
        <tr>
            <td class="align-middle">{$row.model_name}</td>
            <td class="align-middle">{$row.year}</td>
        </tr>
        {/foreach}  
        {else}
        <tr>
            <td colspan="2" class="align-middle text-center font-weight-bold font-italic">No Product Fitment Found</td>
        </tr>
        {/if}        
    </tbody>
    </table>
</div>
