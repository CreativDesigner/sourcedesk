<div id="content">
	<div class="container">
			<h1>{$lang.CART.TITLE}</h1><hr>

            {if $step == 1}
			{if $buyed == 1}
			{if $payment_required}
			<div class="alert alert-warning">{$lang.CART.PAYMENTR|replace:"%l":{$cfg.PAGEURL|cat:"invoices"}}</div>
			{/if}

			<link rel="stylesheet" href="{$raw_cfg.PAGEURL}themes/standard/fonts/pe-icon-7-stroke/css/pe-icon-7-stroke.css">
	        <style>
	        .why .benefits .item { margin-bottom: 30px; }
	        .why .benefits { padding-bottom: 0px; }
	        @media (max-width: 767px) {
	          .testimonials { padding-top: 0 !important; margin-top: -20px; }
	          .why .testimonials .item { margin-bottom: 0px; }
	        }
	        </style>
	        <section id="why" class="why section" style="padding-top: 20px; padding-bottom: 0;">
	            <div class="container">
	                <h2 class="title text-center">{$lang.CART.THANKS1}</h2>
	                <p class="intro text-center" style="margin-bottom: 0;">{$lang.CART.THANKS2}</p><br /><br />
	                <div class="row">
	                    <div class="benefits col-md-6 col-sm-6 col-xs-12">
	                        <div class="item clearfix">
	                            <div class="icon col-md-3 col-xs-12 text-center">
	                                <span style="font-size: 72px; color: #b3b3b3;" class="fa fa-download"></span>
	                            </div><!--//icon-->
	                            <div class="content col-md-9 col-xs-12">
	                                <h3 class="title" style="margin-top: 0;">{$lang.CART.CAT1}</h3>
	                                <p class="desc" style="text-align: justify;">{$lang.CART.TEXT1|replace:"%u":{$raw_cfg.PAGEURL|cat:"products"}}</p>
	                            </div><!--//content-->
	                        </div><!--//item-->

	                        <div class="item clearfix">
	                            <div class="icon col-md-3 col-xs-12 text-center">
	                                <span style="font-size: 72px; color: #b3b3b3;" class="fa fa-at"></span>
	                            </div><!--//icon-->
	                            <div class="content col-md-9 col-xs-12">
	                                <h3 class="title" style="margin-top: 0;">{$lang.CART.CAT2}</h3>
	                                <p class="desc" style="text-align: justify;">{$lang.CART.TEXT2}</p>
	                            </div><!--//content-->
	                        </div><!--//item-->
	                    </div>

	                    <div class="testimonials benefits col-md-6 col-sm-6 col-xs-12">
	                        {if $invoice}<div class="item clearfix">
	                            <div class="icon col-md-3 col-xs-12 text-center">
	                                <span style="font-size: 72px; color: #b3b3b3;" class="fa fa-file-pdf-o"></span>
	                            </div><!--//icon-->
	                            <div class="content col-md-9 col-xs-12">
	                                <h3 class="title" style="margin-top: 0;">{$lang.CART.CAT3}</h3>
	                                <p class="desc" style="text-align: justify;">{$lang.CART.TEXT3|replace:"%u":{$raw_cfg.PAGEURL|cat:"invoices/"|cat:$invoice}|replace:"%i":$invoice}</span></p>
	                            </div><!--//content-->
	                        </div><!--//item-->{/if}

	                        <div class="item last clearfix" style="margin-bottom: 0;">
	                            <div class="icon col-md-3  col-xs-12 text-center">
	                                <span style="font-size: 72px; color: #b3b3b3;" class="fa fa-balance-scale"></span>
	                            </div><!--//icon-->
	                            <div class="content col-md-9 col-xs-12">
	                                <h3 class="title" style="margin-top: 0;">{$lang.CART.CAT4}</h3>
	                                <p class="desc" style="text-align: justify;">{$lang.CART.TEXT4|replace:"%u":{$raw_cfg.PAGEURL|cat:"testimonials/add"}}</p>
	                            </div><!--//content-->
	                        </div><!--//item-->
	                    </div>
	                </div><!--//row-->
	            </div><!--//container-->
	        </section>
			{elseif $buyed == 2}
			<div class="alert alert-danger">
			{$lang.CART.ERROR}
			</div>
			{/if}

			{if $logged_in && $b2b && $buyed == 0}
			{if $tax_info !== false}<div class="alert alert-info">{$lang.CART.B2B}<br />

			{if $tax_info == "reverse"}{$lang.CART.B2B1}

			{else if $tax_info == "reverse_vatid"}{$lang.CART.B2B2}

			{else if $tax_info|is_array && $tax_info|count > 2}{$lang.CART.B2B3}

			{else}
			{$lang.CART.B2B4}{/if}
			</div>{else}
			<div class="alert alert-danger">{$lang.CART.B2B}<br />{$lang.CART.B2B5}</div>{/if}
			{else if $buyed == 0 && $logged_in}
			{if $tax_info|is_array}<div class="alert alert-info">{$lang.CART.B2C}<br />{$lang.CART.B2C1}</div>

			{else if $tax_info == "reverse"}
			<div class="alert alert-info">{$lang.CART.B2C}<br />{$lang.CART.B2C2}</div>

			{else}
			<div class="alert alert-danger">{$lang.CART.B2C}<br />{$lang.CART.B2C3}</div>
			{/if}
			{/if}

			{if isset($voucher_error)}<div class="alert alert-danger">{$voucher_error}</div>{/if}
			{if isset($voucher_ok)}<div class="alert alert-success">{$voucher_ok}</div>{/if}

			{if $buyed != 1}<div class="table table-responsive">
			<table class="table table-bordered table-striped" style="margin-bottom:0;">
				<tr>
					<th width="30%">{$lang.GENERAL.PRODUCT}</th>
					<th width="25%">{$lang.GENERAL.SINGLE_PRICE}</th>
					<th width="15%"><center>{$lang.GENERAL.QUANTITY}</center></th>
					<th align="right" style="text-align:right" width="25%">{$lang.GENERAL.SUM}</th>
				</tr>

				{foreach from=$cartitems item=i}
				<tr>
					<td width="30%">{$i.extension}{if $i.typ == "domain_reg" || $i.typ == "domain_in"}{assign var="info" value=$i.type|unserialize}{$info.domain|htmlentities} {if $i.typ == "domain_reg"}- <font class="text-primary">{$lang.CART.TYPE_REG}</font>{else}- <font class="text-primary">{$lang.CART.TYPE_IN}</font>{/if}{else}{if $i.desc}<b>{/if}{$i.name|htmlentities}{if $i.desc}</b>{/if} {if $i.typ == "update"}- <font class="text-primary">{$lang.CART.TYPE_UPDATE}{/if}{if $i.typ == "product" && $i.type == "e"}- <font class="text-primary">{$lang.CART.TYPE_SOFTWARE}{/if}{if $i.typ == "bundle"}- <font class="text-primary">{$lang.CART.TYPE_BUNDLE}</font>{/if}{/if} {if !empty($i.desc)}<br />{$i.desc}{/if}</td>
					<td width="25%">{if isset($i.oldprice) && $i.oldprice > 0 && $i.oldprice != $i.raw_amount}<s style="color:red;"><span style="color:#444444;">{$i.oldprice_f}</span></s> {/if}{$i.f_amount}{if $i.type == "h" || ($i.type == "e" && $i.billing != "onetime" && $i.billing != "")} {if $i.billing == "" || $i.billing == "onetime"}{$lang.CART.ONETIME}{else if $i.billing == "monthly"}{$lang.CART.MONTHLY}{else if $i.billing == "quarterly"}{$lang.CART.QUARTERLY}{else if $i.billing == "semiannually"}{$lang.CART.SEMIANNUALLY}{else if $i.billing == "annually"}{$lang.CART.ANNUALLY}{else if $i.billing == "minutely"}{$lang.CART.MINUTELY}{else if $i.billing == "hourly"}{$lang.CART.HOURLY}{/if}{/if}{if $i.setup > 0}<br />+ {$i.f_setup} {$lang.CART.SETUP}{else if $i.setup < 0}<br />- {$i.f_setup} {$lang.CART.DISCOUNT}{/if}</td>
					<td width="15%"><center><a href="{if ($i.qty - 1) <= 0}?delete={$i.ID}{else}?id={$i.ID}&qty={$i.qty - 1}{/if}" class="btn btn-default btn-xs" style="padding: 1px 5px;">-</a>&nbsp;&nbsp;&nbsp;{$i.qty}&nbsp;&nbsp;&nbsp;<a href="?id={$i.ID}&qty={$i.qty + 1}" class="btn btn-default btn-xs" style="padding: 1px 5px;"{if $i.type != "r" && $i.typ != "domain_reg" && $i.typ != "domain_in" && $i.typ != "update" && empty($i.additional)}{else} disabled=""{/if}>+</a></center></td>
					<td align="right" width="25%">{if isset($i.oldprice) && $i.oldprice > 0 && $i.oldprice != $i.raw_amount}<s style="color:red;"><span style="color:#444444;">{$i.oldsum_f}</span></s> {/if}{$i.f_sum}{if $i.type == "h" || ($i.type == "e" && $i.billing != "onetime" && $i.billing != "")} {if $i.billing == "" || $i.billing == "onetime"}{$lang.CART.ONETIME}{else if $i.billing == "monthly"}{$lang.CART.MONTHLY}{else if $i.billing == "quarterly"}{$lang.CART.QUARTERLY}{else if $i.billing == "semiannually"}{$lang.CART.SEMIANNUALLY}{else if $i.billing == "minutely"}{$lang.CART.MINUTELY}{else if $i.billing == "hourly"}{$lang.CART.HOURLY}{else if $i.billing == "annually"}{$lang.CART.ANNUALLY}{/if}{/if}{if $i.setup_sum > 0}<br />+ {$i.f_setup_sum} {$lang.CART.SETUP}{else if $i.setup_sum < 0}<br />- {$i.f_setup_sum} {$lang.CART.DISCOUNT}{/if}</td>
				</tr>
				{foreachelse}
				<tr>
					<td colspan="4"><center>{$lang.CART.NOTHING}</center></td>
				</tr>
				{/foreach}

				{if $cartitems|@count > 0}
				<tr>
					<th width="80%" colspan="3">{$lang.CART.SUM}</th>
					<th align="right" style="text-align:right" width="15%">{$sum_f}</th>
				</tr>
				{if (isset($tax) && $tax > 0) || !$logged_in}{if $cfg.TAXES && $sum > 0}
				<form method="POST"><tr>
					<td width="80%" colspan="3"><small>{$lang.CART.TAX_PREFIX} {if $country_tax->percent == 0}{$lang.CART.NO_TAX}{else}{nfo i=$country_tax->percent} % {$country_tax->tax}{/if} {$lang.CART.TAX_SUFFIX} {if $logged_in}{$country_tax->name}{else}<select name="country" onchange="form.submit();">{foreach from=$countries item=name key=id}<option value="{$id}"{if $id == $country_tax->ID} selected="selected"{/if}>{$name}</option>{/foreach}</select>{/if}</small></td>
					<td align="right" style="text-align:right" widtd="15%"><smalL>{$tax_f}</smalL></td>
				</tr></form>
				{/if}{/if}
				{/if}
			</table></div>

			{$moduleFooterMessage}

			{if $cfg.TAXES && $sum > 0 && !$logged_in}
			{if $country_tax->b2b == "3"}
				{if $country_tax->b2c == "2"}
					<div class="alert alert-danger">{$lang.CART.LO1}</div>
				{else}
				<div class="alert alert-warning">{$lang.CART.LO2}{if $country_tax->b2c == "0"}<br />{$lang.CART.LO3}{/if}</div>
				{/if}
			{else}
				{if $country_tax->b2c == "2"}
					<div class="alert alert-warning">{$lang.CART.LO4}{if $country_tax->b2b == "0"}<br />{$lang.CART.LO5}{else if $country_tax->b2b == "1"}<br />{$lang.CART.LO6}{/if}</div>
				{else}
					{if $country_tax->b2c == "1"}
					{if $country_tax->b2b == "0" || $country_tax->b2b == "1"}<div class="alert alert-warning">{$lang.CART.LO7}{if $country_tax->b2b == "1"} {$lang.CART.LO8}{/if} {$lang.CART.LO9}</div>{/if}
					{else}
						<div class="alert alert-warning">{if $country_tax->b2b == "0"}{$lang.CART.LO10}{else}{$lang.CART.LO11}{/if} {if $country_tax->b2b == "1"}{$lang.CART.LO12} {/if}{$lang.CART.LO13}</div>
					{/if}
				{/if}
			{/if}
			{/if}

			{if $cartitems|@count > 0}
				{if !isset($voucher)}<form method="POST">
					<input type="text" name="code" value="{if isset($smarty.post.code)}{$smarty.post.code}{/if}" placeholder="{$lang.CART.VOUCHER}" class="form-control">
					<input type="submit" name="add_voucher" style="margin-top:10px;margin-bottom:20px;" value="{$lang.CART.ADD_VOUCHER}" class="btn btn-warning btn-block btn-sm">
				</form>{else}<b>{$lang.CART.VOUCHER}:</b> {$voucher.code} <a href="./cart?removevoucher"><i class="fa fa-times"></i></a><br /><br />{/if}

				<a href="?delete=all" onclick="return confirm('{$lang.CART.EMPTY_SURE}');" class="btn btn-default">{$lang.CART.EMPTY}</a>

				<div class="pull-right">
					<a style="float:right;" href="{$cfg.PAGEURL}cart/data" class="btn btn-primary"{if $block} disabled="disabled"{/if}>{$lang.CART.CONTINUE} &raquo;</a>
				</div><br /><br />
			{/if}{if $currencies|@count > 1}<form method="POST" class="form-inline" style="float: right; display: inline; padding-bottom: 20px;"><select name="currency" class="form-control" onchange="form.submit()"><option disabled>- {$lang.GENERAL.CHOOSE_CURRENCY} -</option>{foreach from=$currencies item=info key=code}<option value="{$code}"{if $myCurrency == $code} selected="selected"{/if}>{$info.name}</option>{/foreach}</select></form>{/if}
			{/if}

			{else if $step == 2}

			{if isset($waiting_mail)}
			<div class="alert alert-success">{$lang.CART.TH1}</div>

			<p style="text-align: justify;">
			{$lang.CART.TH2}
			</p>
			{else if isset($waiting_conf)}
			<div class="alert alert-success">{$lang.CART.CF1}</div>

			<p style="text-align: justify;">
			{$lang.CART.CF2}
			</p>
			{else}
			<form method="POST" id="order-form" class="captcha-form">

			{if IdentifyProxy::is()}
			<div class="alert alert-danger">{$lang.GENERAL.BLOCKED}</div>
			{else}
			{if !empty($error)}<div class="alert alert-danger">{$error}</div>{/if}

			<div class="card">
                <div class="card-body">
                    {if !$logged_in}<ul class="nav nav-tabs">
                        <li class="nav-item"><a class="nav-link{if !isset($smarty.post.customer) || $smarty.post.customer == "new"} active{/if}" href="#newcust" data-toggle="tab" onclick="$('#custh').val('new');">{$lang.CART.NEW_CUSTOMER}</a></li>
                        <li class="nav-item"><a class="nav-link{if isset($smarty.post.customer) && $smarty.post.customer == "login"} active{/if}" href="#exicust" data-toggle="tab" onclick="$('#custh').val('login');">{$lang.CART.EXISTING_CUSTOMER}</a></li>
                    </ul><br />
                    {/if}
                    <div class="tab-content">
                        {if !$logged_in}<div class="tab-pane fade {if !isset($smarty.post.customer) || $smarty.post.customer == "new"}show active{/if}" id="newcust" style="margin-bottom: -10px;">{/if}
                        	<div class="form-group">
                        		<div class="row">
                        			<div class="col-md-6">
	                        			<label for="reg_firstname">
	                        				{$lang.CART.FIRSTNAME}
	                        			</label>
	                        			<input type="text" name="reg_firstname" id="reg_firstname" class="form-control" placeholder="{$lang.CART.FIRSTNAMEP}" value="{if isset($smarty.post.reg_firstname)}{$smarty.post.reg_firstname|htmlentities}{else if isset($smarty.session.card.firstname)}{$smarty.session.card.firstname|htmlentities}{/if}"{if $logged_in && "firstname"|in_array:$ro_fields} readonly="readonly"{/if}>
	                        		</div>

	                        		<div class="col-md-6">
	                        			<label for="reg_lastname">
	                        				{$lang.CART.LASTNAME}
	                        			</label>
	                        			<input type="text" name="reg_lastname" id="reg_lastname" class="form-control" placeholder="{$lang.CART.LASTNAMEP}" value="{if isset($smarty.post.reg_lastname)}{$smarty.post.reg_lastname|htmlentities}{else if isset($smarty.session.card.lastname)}{$smarty.session.card.lastname|htmlentities}{/if}"{if $logged_in && "lastname"|in_array:$ro_fields} readonly="readonly"{/if}>
	                        		</div>
	                        	</div>
                        	</div>

                        	<div class="form-group">
                        		<label for="reg_company">
                        			{$lang.CART.COMPANY}
                        		</label>
                        		<input type="text" name="reg_company" id="reg_company" class="form-control" placeholder="{$lang.CART.COMPANYP}" value="{if isset($smarty.post.reg_company)}{$smarty.post.reg_company|htmlentities}{else if isset($smarty.session.card.company)}{$smarty.session.card.company|htmlentities}{/if}"{if $logged_in && "company"|in_array:$ro_fields} readonly="readonly"{/if}>
                        	</div>

                        	{if !$logged_in}<div class="form-group">
                        		<label for="reg_email">
                        			{$lang.CART.EMAIL}
                        		</label>
                        		<input type="text" name="reg_email" id="reg_email" class="form-control" placeholder="{$lang.CART.EMAILP}" value="{if isset($smarty.post.reg_email)}{$smarty.post.reg_email|htmlentities}{else if isset($smarty.session.card.email)}{$smarty.session.card.email|htmlentities}{/if}">
                        	</div>

                        	<div class="form-group">
                        		<div class="row">
                        			<div class="col-md-6">
	                        			<label for="reg_pw1">
	                        				{$lang.CART.PW1}
	                        			</label>
	                        			<input type="password" name="reg_pw1" id="reg_pw1" class="form-control" placeholder="tGk0/2!l">
	                        		</div>

	                        		<div class="col-md-6">
	                        			<label for="reg_pw2">
	                        				{$lang.CART.2}
	                        			</label>
	                        			<input type="password" name="reg_pw2" id="reg_pw2" class="form-control" placeholder="tGk0/2!l">
	                        			<input type="hidden" name="pwl" id="pwl" value="0">
	                        		</div>
	                        	</div>
                        	</div>{/if}

                        	{if "street"|in_array:$duty_fields || "street_number"|in_array:$duty_fields}
                        	<div class="form-group">
                        		<div class="row">
                        			{if "street"|in_array:$duty_fields}<div class="col-md-{if "street_number"|in_array:$duty_fields}9{else}12{/if}">
	                        			<label for="reg_street">
	                        				{$lang.CART.STREET}
	                        			</label>
	                        			<input type="text" name="reg_street" id="reg_street" class="form-control" placeholder="{$lang.CART.STREETP}" value="{if isset($smarty.post.reg_street)}{$smarty.post.reg_street|htmlentities}{else if isset($smarty.session.card.street)}{$smarty.session.card.street|htmlentities}{/if}"{if $logged_in && "street"|in_array:$ro_fields} readonly="readonly"{/if}>
	                        		</div>{/if}

	                        		{if "street_number"|in_array:$duty_fields}<div class="col-md-{if "street"|in_array:$duty_fields}3{else}12{/if}">
	                        			<label for="reg_street_number">
	                        				{$lang.CART.NUMBER}
	                        			</label>
	                        			<input type="text" name="reg_street_number" id="reg_street_number" class="form-control" placeholder="{$lang.CART.NUMBERP}" value="{if isset($smarty.post.reg_street_number)}{$smarty.post.reg_street_number|htmlentities}{else if isset($smarty.session.card.street_number)}{$smarty.session.card.street_number|htmlentities}{/if}"{if $logged_in && "street_number"|in_array:$ro_fields} readonly="readonly"{/if}>
	                        		</div>{/if}
	                        	</div>
                        	</div>
                        	{/if}

                        	{if "postcode"|in_array:$duty_fields || "city"|in_array:$duty_fields}
                        	<div class="form-group">
                        		<div class="row">
                        			{if "postcode"|in_array:$duty_fields}<div class="col-md-{if "city"|in_array:$duty_fields}3{else}12{/if}">
	                        			<label for="reg_postcode">
	                        				{$lang.CART.POSTCODE}
	                        			</label>
	                        			<input type="text" name="reg_postcode" id="reg_postcode" class="form-control" placeholder="{$lang.CART.POSTCODEP}" value="{if isset($smarty.post.reg_postcode)}{$smarty.post.reg_postcode|htmlentities}{else if isset($smarty.session.card.postcode)}{$smarty.session.card.postcode|htmlentities}{/if}"{if $logged_in && "postcode"|in_array:$ro_fields} readonly="readonly"{/if}>
	                        		</div>{/if}

	                        		{if "city"|in_array:$duty_fields}<div class="col-md-{if "postcode"|in_array:$duty_fields}9{else}12{/if}">
	                        			<label for="reg_city">
	                        				{$lang.CART.CITY}
	                        			</label>
	                        			<input type="text" name="reg_city" id="reg_city" class="form-control" placeholder="{$lang.CART.CITYP}" value="{if isset($smarty.post.reg_city)}{$smarty.post.reg_city|htmlentities}{else if isset($smarty.session.card.city)}{$smarty.session.card.city|htmlentities}{/if}"{if $logged_in && "city"|in_array:$ro_fields} readonly="readonly"{/if}>
	                        		</div>{/if}
	                        	</div>
                        	</div>
                        	{/if}

                        	{if "country"|in_array:$duty_fields}
                        	<div class="form-group">
                        		<label for="reg_country">
                        			{$lang.CART.COUNTRY}
                        		</label>

                        		<select name="reg_country" id="reg_country" class="form-control"{if $logged_in && "country"|in_array:$ro_fields} readonly="readonly"{/if}>
									{foreach from=$countries item=name key=id}
									<option value="{$id}"{if $country == $id} selected="selected"{/if}>{$name|htmlentities}</option>
									{/foreach}
								</select>
                        	</div>
                        	{/if}

                        	{if "telephone"|in_array:$duty_fields}
                        	<div class="form-group">
                        		<label for="reg_telephone">
                    				{$lang.CART.PHONE}
                    			</label>
                    			<input type="text" name="reg_telephone" id="reg_telephone" class="form-control" placeholder="{$lang.CART.PHONEP}" value="{if isset($smarty.post.reg_telephone)}{$smarty.post.reg_telephone|htmlentities}{else if isset($smarty.session.card.telephone)}{$smarty.session.card.telephone|htmlentities}{/if}"{if $logged_in && "telephone"|in_array:$ro_fields} readonly="readonly"{/if}>
                        	</div>
                        	{/if}

                        	{if "birthday"|in_array:$duty_fields}
                        	<div class="form-group">
                        		<label for="reg_birthday">
                    				{$lang.CART.BIRTHDAY}
                    			</label>
                    			<input type="text" name="reg_birthday" id="reg_birthday" class="form-control" placeholder="{dfo d="27.12.1984" m=false}" value="{if isset($smarty.post.reg_birthday)}{$smarty.post.reg_birthday|htmlentities}{else if isset($smarty.session.card.birthday)}{$smarty.session.card.birthday|htmlentities}{/if}"{if $logged_in && "birthday"|in_array:$ro_fields} readonly="readonly"{/if}>
                        	</div>
                        	{/if}

                        	{if "website"|in_array:$duty_fields}
                        	<div class="form-group">
                        		<label for="reg_website">
                    				{$lang.CART.WEBSITE}
                    			</label>
                    			<input type="text" name="reg_website" id="reg_website" class="form-control" placeholder="{$lang.CART.WEBSITEP}" value="{if isset($smarty.post.reg_website)}{$smarty.post.reg_website|htmlentities}{else if isset($smarty.session.card.website)}{$smarty.session.card.website|htmlentities}{/if}"{if $logged_in && "website"|in_array:$ro_fields} readonly="readonly"{/if}>
                        	</div>
                        	{/if}

                        	{if $vatid}
                        	<div class="form-group">
                        		<label for="reg_vatid">
                    				{$lang.CART.VATID}{if "vatid"|in_array:$duty_fields}{else} <span style="font-weight: normal;">({$lang.CART.VATHINT})</span>{/if}
                    			</label>
                    			<input type="text" name="reg_vatid" id="reg_vatid" class="form-control" placeholder="DE123456789" value="{if isset($smarty.post.reg_vatid)}{$smarty.post.reg_vatid|htmlentities}{else if isset($smarty.session.card.vatid)}{$smarty.session.card.vatid|htmlentities}{/if}"{if $logged_in && "vatid"|in_array:$ro_fields} readonly="readonly"{/if}>
                        	</div>
                        	{/if}

                        	{foreach from=$cf key=id item=info}
                        	<div class="form-group">
                        		<label for="reg_cf_{$id}">
                    				{$info.0}
                    			</label>
                    			<input type="text" name="reg_cf[{$id}]" id="reg_cf_{$id}" class="form-control" placeholder="" value="{$info.1|htmlentities}"{if $logged_in && $info.3} readonly="readonly"{/if}>
                        	</div>
							{/foreach}
							
							<div class="checkbox">
								<label>
									<input type="checkbox" name="reg_newsletter" value="1" />
									{$lang.GENERAL.NEWSLETTER_CB}
								</label>
							</div>

                        	{if isset($captchaText)}
							<div class="form-group">
								<label for="reg_captcha">{$captchaText}</label>
			    				<input type="text" name="captcha" id="reg_captcha" class="form-control" placeholder="{$lang.CART.ANSWER}">
			    			</div>{/if}
			    			{if isset($captchaCode)}
			    			{$captchaCode}
			    			{/if}
                        {if !$logged_in}</div>

                        <div class="tab-pane fade {if isset($smarty.post.customer) && $smarty.post.customer == "login"}in active{/if}" id="exicust">
                        	<div class="form-group">
							    <label for="login_email">{$lang.CART.EMAIL}</label>
							    <input type="email" class="form-control" id="login_email" placeholder="{$lang.CART.EMAILP}" value="{if isset($smarty.post.login_email)}{$smarty.post.login_email|htmlentities}{/if}" name="login_email">
							</div>

							<div class="form-group">
							    <label for="login_password">{$lang.CART.PWD}</label>
							    <input type="password" class="form-control" id="login_password" placeholder="zQ8)oL!9" name="login_password">
							</div>

							<div class="form-group" style="margin-bottom: 0;">
							    <label for="login_otp">{$lang.CART.TFA}</label>
							    <input type="number" class="form-control" id="login_otp" placeholder="{$lang.CART.TFAP}" name="login_otp">
							</div>
                        </div>{/if}
                    </div>
                </div>
            </div>

            {if isset($captchaModal)}
			<div class="modal fade" id="captchaModal" tabindex="-1" role="dialog">
			  <div class="modal-dialog">
			    <div class="modal-content">
			      <div class="modal-body">
			        {$captchaModal}
			        <button type="submit" class="btn btn-primary btn-block">{$lang.CART.CONTINUE} &raquo;</button>
			      </div>
			    </div>
			  </div>
			</div>

			<script type="text/javascript">
				function openCaptchaModal() {
					if($('#custh').val() != "new") return true;

					$('#captchaModal').modal({
						keyboard: false,
						backdrop: 'static',
						show: false,
					});

					$('#captchaModal').modal('show');
					return false;
				}
			</script>
			{/if}

            {if !$logged_in}<input type="hidden" name="customer" id="custh" value="{if isset($smarty.post.customer) && $smarty.post.customer == "login"}login{else}new{/if}" />{/if}
<br />
            <div class="checkbox">
			    <label>
			      	<input type="checkbox" name="terms" value="yes"{if (isset($smarty.post.terms) && $smarty.post.terms == "yes") || $hasTos || (!isset($smarty.post.action) && isset($smarty.session.card.terms) && $smarty.session.card.terms == "yes")} checked="checked="{/if}>
			      	{$lang.CART.TERMS1} <a href="{$cfg.PAGEURL}terms" target="_blank">{$lang.CART.TERMS2|replace:"%p":$cfg.PAGENAME}</a> {$lang.CART.TERMS3}
			    </label>
			</div>

			<div class="checkbox">
			    <label>
			      	<input type="checkbox" name="withdrawal" value="yes"{if (isset($smarty.post.withdrawal) && $smarty.post.withdrawal == "yes") || $hasWithdrawal || (!isset($smarty.post.action) && isset($smarty.session.card.withdrawal) && $smarty.session.card.withdrawal == "yes")} checked="checked="{/if}>
			      	{$lang.CART.TERMS1} <a href="{$cfg.PAGEURL}withdrawal" target="_blank">{$lang.CART.WITHDRAWAL|replace:"%p":$cfg.PAGENAME}</a> {$lang.CART.TERMS3}
			    </label>
			</div>

			<div class="checkbox">
			    <label>
			      	<input type="checkbox" name="privacy" value="yes"{if (isset($smarty.post.privacy) && $smarty.post.privacy == "yes") || $hasPrivacy || (!isset($smarty.post.action) && isset($smarty.session.card.privacy) && $smarty.session.card.privacy == "yes")} checked="checked="{/if}>
			      	{$lang.CART.TERMS1} <a href="{$cfg.PAGEURL}privacy" target="_blank">{$lang.CART.PRIVACY|replace:"%p":$cfg.PAGENAME}</a> {$lang.CART.TERMS3}
			    </label>
			</div>

			<div class="checkbox">
			    <label>
			      	<input type="checkbox" name="cancel" value="yes"{if (isset($smarty.post.cancel) && $smarty.post.cancel == "yes") || (!isset($smarty.post.action) && isset($smarty.session.card.cancel) && $smarty.session.card.cancel == "yes")} checked="checked="{/if}>
			      	{$lang.CART.CANCEL|replace:"%p":$cfg.PAGENAME}
			    </label>
			</div>

			<input type="hidden" name="action" value="cont">
			<input type="hidden" name="token" value="{$token}">
			<input type="submit" class="btn btn-primary btn-block" value="{$lang.CART.CONTINUE} &raquo;"{if isset($captchaModal)} onclick="return openCaptchaModal();"{/if}{if isset($captchaExec)} onclick="{$captchaExec}"{/if} />

			<small>{$lang.CART.OHINT}</small>

			</form>
			{/if}
			{/if}
			{else if $step == 3}
			{if $smsverify}
			<p>{$lang.ORDER.SMS_REQUIRED}</p>

			<div id="smsverifyFail" class="alert alert-danger" style="display: none;"></div>

  <div class="form-group">
        <div class="input-group">
        <input type="text" placeholder="{$lang.ORDER.MOBILE_PHONE}" name="p_telephone" value="{$user.telephone|htmlentities}" class="form-control input-lg">
          <span class="input-group-addon">
            <a href="#" id="verifyPhone" data-toggle="tooltip" title="{$lang.PROFILE.VERIFY}">
            <i class="fa fa-check fa-fw" id="verifyCheck"></i>
            </a>
          </span>
        </div>
    </div>

  <div id="smsverifyContainer" style="display: none;" class="alert alert-success">{$lang.PROFILE.SMS_CODE_ENTER}<br /><center><div class="input-group" style="max-width: 250px; margin-top: 10px;"><input type="text" id="sms_code" class="form-control input-lg" placeholder="00000000" style="text-align: center; letter-spacing: 2px;"><span class="input-group-addon"><i id="sms_code_status" class="fa fa-arrow-left fa-fw"></i></center></div>

  <script>
  var doingSms = 0;

  $("#sms_code").keyup(function() {
    $("#sms_code_status").addClass("fa-arrow-left").removeClass("fa-spinner fa-spin fa-times").css("color", "");

    var code = $(this).val().trim();
    if (code.length == 8) {
      $(this).prop("disabled", true);
      $("#sms_code_status").removeClass("fa-arrow-left").addClass("fa-spinner fa-spin");

      $.post("{$cfg.PAGEURL}profile", {
        "sms_code": $(this).val(),
        "csrf_token": "{ct}"
      }, function (r) {
        if (r == "ok") {
          window.location = "./payment";
        } else {
          $("#sms_code_status").removeClass("fa-spinner fa-spin").addClass("fa-times").css("color", "red");
          alert(r);
          $("#sms_code").prop("disabled", false).val("");
        }
      });
    }
  });

  $("#verifyPhone").click(function(e) {
    e.preventDefault();

    if (doingSms) {
      return;
    }
    doingSms = 1;

    $(this).find("i").removeClass("fa-check").addClass("fa-spinner fa-spin");
    $("#smsverifyContainer").slideUp();

    $("#smsverifyFail").slideUp(function() {
      $.post("{$cfg.PAGEURL}profile", {
        "sms_verify": $("[name=p_telephone]").val(),
        "csrf_token": "{ct}"
      }, function(r) {
        if (r == "ok") {
          $("#sms_code_status").addClass("fa-arrow-left").removeClass("fa-spinner fa-spin fa-times").css("color", "");
          $("#smsverifyContainer").slideDown();
        } else {
          $("#smsverifyFail").html(r).slideDown();
        }
        
        doingSms = 0;
        $("#verifyPhone").find("i").addClass("fa-check").removeClass("fa-spinner fa-spin");
      });
    });
  });
  </script>
			{else}
			<p style="text-align: justify;">
				{$lang.CART.CONF1}
			</p>

			<div class="table table-responsive">
			<table class="table table-bordered table-striped" style="margin-bottom:0;">
				<tr>
					<th width="30%">{$lang.GENERAL.PRODUCT}</th>
					<th width="25%">{$lang.GENERAL.SINGLE_PRICE}</th>
					<th width="15%"><center>{$lang.GENERAL.QUANTITY}</center></th>
					<th align="right" style="text-align:right" width="25%">{$lang.GENERAL.SUM}</th>
				</tr>

				{foreach from=$cartitems item=i}
				<tr>
					<td width="30%">{$i.extension}{if $i.typ == "domain_reg" || $i.typ == "domain_in"}{assign var="info" value=$i.type|unserialize}{$info.domain|htmlentities} {if $i.typ == "domain_reg"}- <font class="text-primary">{$lang.CART.TYPE_REG}</font>{else}- <font class="text-primary">{$lang.CART.TYPE_IN}</font>{/if}{else}{if $i.desc}<b>{/if}{$i.name|htmlentities}{if $i.desc}</b>{/if} {if $i.typ == "update"}- <font class="text-primary">{$lang.CART.TYPE_UPDATE}{/if}{if $i.typ == "product" && $i.type == "e"}- <font class="text-primary">{$lang.CART.TYPE_SOFTWARE}{/if}{if $i.typ == "bundle"}- <font class="text-primary">{$lang.CART.TYPE_BUNDLE}</font>{/if}{/if} {if !empty($i.desc)}<br />{$i.desc}{/if}</td>
					<td width="25%">{if isset($i.oldprice) && $i.oldprice > 0 && $i.oldprice != $i.raw_amount}<s style="color:red;"><span style="color:#444444;">{$i.oldprice_f}</span></s> {/if}{$i.f_amount}{if $i.type == "h" || ($i.type == "e" && $i.billing != "onetime" && $i.billing != "")} {if $i.billing == "" || $i.billing == "onetime"}{$lang.CART.ONETIME}{else if $i.billing == "monthly"}{$lang.CART.MONTHLY}{else if $i.billing == "quarterly"}{$lang.CART.QUARTERLY}{else if $i.billing == "semiannually"}{$lang.CART.SEMIANNUALLY}{else if $i.billing == "annually"}{$lang.CART.ANNUALLY}{else if $i.billing == "minutely"}{$lang.CART.MINUTELY}{else if $i.billing == "hourly"}{$lang.CART.HOURLY}{/if}{/if}{if $i.setup > 0}<br />+ {$i.f_setup} {$lang.CART.SETUP}{else if $i.setup < 0}<br />- {$i.f_setup} {$lang.CART.DISCOUNT}{/if}</td>
					<td width="15%"><center>{$i.qty}</center></td>
					<td align="right" width="25%">{if isset($i.oldprice) && $i.oldprice > 0 && $i.oldprice != $i.raw_amount}<s style="color:red;"><span style="color:#444444;">{$i.oldsum_f}</span></s> {/if}{$i.f_sum}{if $i.type == "h" || ($i.type == "e" && $i.billing != "onetime" && $i.billing != "")} {if $i.billing == "" || $i.billing == "onetime"}{$lang.CART.ONETIME}{else if $i.billing == "monthly"}{$lang.CART.MONTHLY}{else if $i.billing == "quarterly"}{$lang.CART.QUARTERLY}{else if $i.billing == "semiannually"}{$lang.CART.SEMIANNUALLY}{else if $i.billing == "minutely"}{$lang.CART.MINUTELY}{else if $i.billing == "hourly"}{$lang.CART.HOURLY}{else if $i.billing == "annually"}{$lang.CART.ANNUALLY}{/if}{/if}{if $i.setup_sum > 0}<br />+ {$i.f_setup_sum} {$lang.CART.SETUP}{else if $i.setup_sum < 0}<br />- {$i.f_setup_sum} {$lang.CART.DISCOUNT}{/if}</td>
				</tr>
				{foreachelse}
				<tr>
					<td colspan="4"><center>{$lang.CART.NOTHING}</center></td>
				</tr>
				{/foreach}

				{if $cartitems|@count > 0}
				<tr>
					<th width="80%" colspan="3">{$lang.CART.SUM}</th>
					<th align="right" style="text-align:right" width="15%">{$sum_f}</th>
				</tr>
				{if (isset($tax) && $tax > 0) || !$logged_in}{if $cfg.TAXES && $sum > 0}
				<form method="POST"><tr>
					<td width="80%" colspan="3"><small>{$lang.CART.TAX_PREFIX} {if $country_tax->percent == 0}{$lang.CART.NO_TAX}{else}{nfo i=$country_tax->percent} % {$country_tax->tax}{/if} {$lang.CART.TAX_SUFFIX} {if $logged_in}{$country_tax->name}{else}<select name="country" onchange="form.submit();">{foreach from=$countries item=name key=id}<option value="{$id}"{if $id == $country_tax->ID} selected="selected"{/if}>{$name}</option>{/foreach}</select>{/if}</small></td>
					<td align="right" style="text-align:right" widtd="15%"><smalL>{$tax_f}</smalL></td>
				</tr></form>
				{/if}{/if}
				{/if}

					<tr>
						<td width="60%" colspan="3"><b>{$lang.CART.C1}</b></td>
						<td width="40%" style="text-align: right; color: {if $credit_raw > 0}green{else if $credit_raw < 0}red{else}black{/if};">{if $credit_raw < 0}- {/if}{$credit}</td>
					</tr>

					{if $rest_raw >= 0}
					<tr>
						<th width="60%" colspan="3"><b>{$lang.CART.C3}</b></th>
						<th width="40%" style="text-align: right; color: {if $rest_raw > 0}green{else if $rest_raw < 0}red{else}black{/if};">{if $rest_raw < 0}- {/if}{$rest}</th>
					</tr>
					{else}
					<tr>
						<th width="60%" colspan="3"><b>{$lang.CART.C4}</b></th>
						<th width="40%" style="text-align: right; color: {if $rest_raw > 0}green{else if $rest_raw < 0}red{else}black{/if};">{$rest}</th>
					</tr>
					{/if}
				</table>
			</div>

			<a href="{$cfg.PAGEURL}cart/buy/{$cartitems|count}/{$sum_raw2}" class="btn btn-primary btn-block">{$lang.CART.DOORDER}</a><br />

			{if $currencies|@count > 1}
			<form method="POST" class="form-inline" style="float: right; display: inline; padding-bottom: 20px;"><select name="currency" class="form-control" onchange="form.submit()"><option disabled>- {$lang.GENERAL.CHOOSE_CURRENCY} -</option>{foreach from=$currencies item=info key=code}<option value="{$code}"{if $myCurrency == $code} selected="selected"{/if}>{$info.name}</option>{/foreach}</select></form>
			{/if}
{/if}
			{/if}
	</div>
</div><br />
{$extaffcode}
