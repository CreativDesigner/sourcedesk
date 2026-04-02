<div class="row">
	<div class="col-lg-12">
		<h1 class="page-header">{$lang.INVOICE_EXPORT.TITLE}</h1>
	</div>
	<!-- /.col-lg-12 -->
</div>

<form accept-charset="UTF-8" role="form" method="post">
    <fieldset>
        <div class="form-group">
            <label>{$lang.INVOICE_EXPORT.TIME}</label>
            <div class="row">
                <div class="col-xs-6">
                    <div class="input-group" style="position: relative;">
                        <span class="input-group-addon">{$lang.INVOICE_EXPORT.SINCE}</span>
                        <input type="text" class="form-control datepicker" name="from" value="{if isset($smarty.post.from)}{$smarty.post.from|htmlentities}{/if}" placeholder="{dfo d=$smarty.now m=0}">
                    </div>
                </div>

                <div class="col-xs-6">
                    <div class="input-group" style="position: relative;">
                        <span class="input-group-addon">{$lang.INVOICE_EXPORT.UNTIL}</span>
                        <input type="text" class="form-control datepicker" name="until" value="{if isset($smarty.post.until)}{$smarty.post.until|htmlentities}{else}{dfo d=$smarty.now m=0}{/if}" placeholder="{dfo d=$smarty.now m=0}">
                    </div>
                </div>
            </div>
        </div>

        <div class="form-group">
            <label>{$lang.INVOICE_EXPORT.EXCLUDE}</label>
            <input type="text" class="form-control" name="exclude" value="{if isset($smarty.post.exclude)}{$smarty.post.exclude|htmlentities}{/if}" placeholder="254, {$cfg.INVOICE_PREFIX}000126">
            <p class="help-block">{$lang.INVOICE_EXPORT.EXCLUDE_HINT}</p>
        </div>

        <label>{$lang.INVOICE_EXPORT.CUSTOMERS}</label>
        <div class="checkbox" style="margin-top: 0;">
            <label><input type="checkbox" name="all_customers" onchange="javascript:customerSelect(this.checked);" value="yes"{if !isset($smarty.post.download) || $smarty.post.all_customers == "yes"} checked="checked"{/if}> {$lang.INVOICE_EXPORT.CUSTOMERS_NOLIMIT}</label>
        </div>
        <div id="customerSelect"{if !isset($smarty.post.download) || $smarty.post.all_customers == "yes"} style="display:none;"{/if}>
            <select{if !isset($smarty.post.download) || $smarty.post.all_customers == "yes"}{else} name="customers[]"{/if} class="form-control" id="customerSelectBox" multiple>
                {foreach from=$customers key=id item=display}
                    <option value="{$id}"{if !isset($smarty.post.download) || $id|in_array:$smarty.post.customers} selected="selected"{/if}>{$display|htmlentities}</option>
                {/foreach}
            </select>
            <p class="help-block">{$lang.INVOICE_EXPORT.CUSTOMERS_HINT}</p>
        </div>

        {literal}<script type="text/javascript">
            function customerSelect(checked) {
                if(checked) {
                    document.getElementById("customerSelect").style.display = "none";
                    document.getElementById("customerSelectBox").setAttribute("name", "");
                } else {
                    document.getElementById("customerSelect").style.display = "block";
                    document.getElementById("customerSelectBox").setAttribute("name", "customers[]");
                }
            }
        </script>{/literal}

        <label>{$lang.INVOICE_EXPORT.SORT}</label>
        <div class="form-group">
            <select name="sort" class="form-control">
                <option value="0"{if isset($smarty.post.sort) && $smarty.post.sort == 0} selected="selected"{/if}>{$lang.INVOICE_EXPORT.DATE_ASC}</option>
                <option value="1"{if isset($smarty.post.sort) && $smarty.post.sort == 1} selected="selected"{/if}>{$lang.INVOICE_EXPORT.DATE_DESC}</option>
                <option value="2"{if isset($smarty.post.sort) && $smarty.post.sort == 2} selected="selected"{/if}>{$lang.INVOICE_EXPORT.ID_ASC}</option>
                <option value="3"{if isset($smarty.post.sort) && $smarty.post.sort == 3} selected="selected"{/if}>{$lang.INVOICE_EXPORT.ID_DESC}</option>
                <option value="4"{if isset($smarty.post.sort) && $smarty.post.sort == 4} selected="selected"{/if}>{$lang.INVOICE_EXPORT.USER_ASC}</option>
                <option value="5"{if isset($smarty.post.sort) && $smarty.post.sort == 5} selected="selected"{/if}>{$lang.INVOICE_EXPORT.USER_DESC}</option>
            </select>
        </div>

        <label>{$lang.INVOICE_EXPORT.FORMAT}</label>
        <div class="form-group">
            <select name="format" class="form-control">
                <option value="pdf"{if isset($smarty.post.format) && $smarty.post.format == "pdf"} selected="selected"{/if}>PDF</option>
                <option value="zip"{if isset($smarty.post.format) && $smarty.post.format == "zip"} selected="selected"{/if}>PDF (ZIP)</option>
                <option value="csv"{if isset($smarty.post.format) && $smarty.post.format == "csv"} selected="selected"{/if}>CSV</option>
                <option value="xml"{if isset($smarty.post.format) && $smarty.post.format == "xml"} selected="selected"{/if}>XML</option>
            </select>
        </div>

        <div class="form-group">
            <button type="submit" name="download" class="btn btn-primary btn-block">
                {$lang.INVOICE_EXPORT.DOWNLOAD}
            </button>
        </div>
    </fieldset>
</form>