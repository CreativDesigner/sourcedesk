<link rel="stylesheet" href="{cdnurl}themes/order/slider/slider.min.css">
<script src="{cdnurl}themes/order/slider/slider.min.js"></script>

<center>
    <input style="width: 90%;" id="product_slider" type="text" />
</center>

<script>
$("#product_slider").slider({
    ticks: [{assign var="i" value=0}{foreach from=$articles item=a}{if $i}, {/if}{assign var="i" value=($i+1)}{$i}{/foreach}],
    ticks_labels: [{assign var="i" value=0}{foreach from=$articles item=a}{if $i}, {/if}{assign var="i" value=($i+1)}'{$a.name}'{/foreach}],
    tooltip: "hide",
    value: 1,
});

$("#product_slider").change(function() {
    $(".slider_product").hide();
    $("#slider_product_" + $(this).val()).show();
});
</script>

<br />
{assign var="i" value=0}
{foreach from=$articles item=a}
{assign var="i" value=($i+1)}
<div class="slider_product" id="slider_product_{$i}"{if $i > 1} style="display: none;"{/if}>
{if $a.parameters|count >= 4 && $a.parameters|count % 4 == 0}
{assign "width" "3"}
{elseif $a.parameters|count >= 3 && $a.parameters|count % 3 == 0}
{assign "width" "4"}
{elseif $a.parameters|count >= 2 && $a.parameters|count % 2 == 0}
{assign "width" "6"}
{else}
{assign "width" "12"}
{/if}

{if $a.parameters|count}
<div class="row">
{foreach from=$a.parameters item=v key=k}
<div class="col-md-{$width}">
    <div class="panel panel-default">
        <div class="panel-body" style="text-align: center;">
            <h4>{$v}</h4>
            <p style="margin-bottom: 5px;">{$k}</p>
        </div>
    </div>
</div>
{/foreach}
</div>
{/if}

{if $a.parsed_desc}
<div class="panel panel-default">
    <div class="panel-body">
        {$a.parsed_desc}
    </div>
</div>
{/if}

{$a.link|replace:"<a":"<a class=\"btn-primary btn btn-block\""}
</div>
{/foreach}