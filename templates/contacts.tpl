<div id="content">
    <div class="container">
        {if $section == "add"}
        <h1>{$lang.CONTACTS.TITLE}<span class="pull-right"><a href="{$cfg.PAGEURL}contacts"><i class="fa fa-reply"></i></a></span></h1><hr />

        {if !empty($err)}<div class="alert alert-danger">{$err}</div>{/if}

        <form method="POST">
            <div class="form-group">
                <label>{$lang.CONTACTS.NAME}</label>
                <div class="row">
                    <div class="col-md-6">
                        <input type="text" name="firstname" class="form-control" placeholder="{$lang.CONTACTS.FIRSTNAME}" value="{if isset($smarty.post.firstname)}{$smarty.post.firstname|htmlentities}{/if}" required="">
                    </div>
                    <div class="col-md-6">
                        <input type="text" name="lastname" class="form-control" placeholder="{$lang.CONTACTS.LASTNAME}" value="{if isset($smarty.post.lastname)}{$smarty.post.lastname|htmlentities}{/if}" required="">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>{$lang.CONTACTS.EMAIL}</label>
                <input type="email" name="mail" class="form-control" placeholder="{$lang.CONTACTS.EMAIL}" required="" value="{if isset($smarty.post.mail)}{$smarty.post.mail|htmlentities}{/if}">
            </div>

            <input type="submit" class="btn btn-primary btn-block" value="{$lang.CONTACTS.ADD}">
        </form>
        {elseif $section == "edit"}
        <h1><center><a href="{$cfg.PAGEURL}contacts" style="float: left;"><i class="fa fa-reply"></i></a>{$c.firstname|htmlentities} {$c.lastname|htmlentities}<a href="{$cfg.PAGEURL}contacts/{$c.ID}/del" onclick="return confirm('{$lang.CONTACTS.AYS}');" style="float: right;"><i class="fa fa-trash"></i></a></h1><hr />

        <form method="POST">
            <div class="form-group">
                <div class="row">
                    <div class="col-md-6">
                        <label>{$lang.CONTACTS.FIRSTNAME}</label>
                        <input type="text" name="firstname" class="form-control" placeholder="{$lang.CONTACTS.FIRSTNAME}" value="{if isset($smarty.post.firstname)}{$smarty.post.firstname|htmlentities}{else}{$c.firstname|htmlentities}{/if}" required="">
                    </div>
                    <div class="col-md-6">
                        <label>{$lang.CONTACTS.LASTNAME}</label>
                        <input type="text" name="lastname" class="form-control" placeholder="{$lang.CONTACTS.LASTNAME}" value="{if isset($smarty.post.lastname)}{$smarty.post.lastname|htmlentities}{else}{$c.lastname|htmlentities}{/if}" required="">
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>{$lang.CONTACTS.COMPANY}</label>
                        <input type="text" name="company" class="form-control" placeholder="{$lang.CONTACTS.OPTIONAL}" value="{if isset($smarty.post.company)}{$smarty.post.company|htmlentities}{else}{$c.company|htmlentities}{/if}">
                    </div>                    
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label>{$lang.CONTACTS.POSITION}</label>
                        <input type="text" name="type" class="form-control" placeholder="{$lang.CONTACTS.OPTIONAL}" value="{if isset($smarty.post.type)}{$smarty.post.type|htmlentities}{else}{$c.type|htmlentities}{/if}">
                    </div>                    
                </div>
            </div>

            <div class="row">
                <div class="col-md-8">
                    <div class="form-group">
                        <label>{$lang.CONTACTS.STREET}</label>
                        <input type="text" name="street" class="form-control" placeholder="{$lang.CONTACTS.OPTIONAL}" value="{if isset($smarty.post.street)}{$smarty.post.street|htmlentities}{else}{$c.street|htmlentities}{/if}">
                    </div>                    
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label>{$lang.CONTACTS.STREET_NUMBER}</label>
                        <input type="text" name="street_number" class="form-control" placeholder="{$lang.CONTACTS.OPTIONAL}" value="{if isset($smarty.post.street_number)}{$smarty.post.street_number|htmlentities}{else}{$c.street_number|htmlentities}{/if}">
                    </div>                    
                </div>
            </div>

            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>{$lang.CONTACTS.POSTCODE}</label>
                        <input type="text" name="postcode" class="form-control" placeholder="{$lang.CONTACTS.OPTIONAL}" value="{if isset($smarty.post.postcode)}{$smarty.post.postcode|htmlentities}{else}{$c.postcode|htmlentities}{/if}">
                    </div>                    
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label>{$lang.CONTACTS.CITY}</label>
                        <input type="text" name="city" class="form-control" placeholder="{$lang.CONTACTS.OPTIONAL}" value="{if isset($smarty.post.city)}{$smarty.post.city|htmlentities}{else}{$c.city|htmlentities}{/if}">
                    </div>                    
                </div>

                <div class="col-md-3">
                    <div class="form-group">
                        <label>{$lang.CONTACTS.COUNTRY}</label>
                        <select name="country" class="form-control">
                            {foreach from=$countries item=name key=key}
                            <option value="{$key|htmlentities}"{if $c.country == $key} selected=""{/if}>{$name|htmlentities}</option>
                            {/foreach}
                        </select>
                    </div>                    
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>{$lang.CONTACTS.EMAIL}</label>
                        <input type="email" name="mail" class="form-control" placeholder="{$lang.CONTACTS.EMAIL}" required="" value="{if isset($smarty.post.mail)}{$smarty.post.mail|htmlentities}{else}{$c.mail|htmlentities}{/if}">
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label>{$lang.CONTACTS.TELEPHONE}</label>
                        <input type="text" name="telephone" class="form-control" placeholder="{$lang.CONTACTS.OPTIONAL}" value="{if isset($smarty.post.telephone)}{$smarty.post.telephone|htmlentities}{else}{$c.telephone|htmlentities}{/if}">
                    </div>                    
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>{$lang.CONTACTS.LANGUAGE}</label>
                        <select name="language" class="form-control">
                            {foreach from=$langs item=name key=key}
                            <option value="{$key|htmlentities}"{if $c.language == $key} selected=""{/if}>{$name|htmlentities}</option>
                            {/foreach}
                        </select>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label>{$lang.CONTACTS.CURRENCY}</label>
                        <select name="currency" class="form-control">
                            {foreach from=$currencies item=info key=key}
                            <option value="{$key|htmlentities}"{if $c.currency == $key} selected=""{/if}>{$info.name|htmlentities}</option>
                            {/foreach}
                        </select>
                    </div>                    
                </div>
            </div><br />

            <div class="panel panel-default">
                <div class="panel-heading">{$lang.CONTACTS.TEMPLATES}</div>
                <div class="panel-body">
                    {$tHtml}

                    <input type="hidden" name="mail_templates[]" value="0">

                    <script>
                    $(".mt_checkall").click(function(e) {
                        $(".mt_checkbox[data-category=" + $(this).data("category") + "]").prop("checked", e.target.checked);
                    });

                    $(".mt_checkbox").click(function(e) {
                        var cat = $(this).data("category");
                        var chk = true;

                        $(".mt_checkbox[data-category=" + cat + "]").each(function() {
                            if (!$(this).is(":checked")) {
                                chk = false;
                            }
                        });

                        $(".mt_checkall[data-category=" + cat + "]").prop("checked", chk);
                    });
                    </script>
                </div>
            </div>

            <div class="panel panel-default">
                <div class="panel-heading">{$lang.CONTACTS.ACCESS2}</div>
                <div class="panel-body">
                    <div class="alert alert-warning">{$lang.CONTACTS.ACWAR}</div>

                    <div class="checkbox" style="margin-top: 0; margin-bottom: 0;">
                        <label>
                            <input type="checkbox" name="rights[]" value="tickets"{if in_array("tickets", $rights)} checked=""{/if} />
                            {$lang.CONTACTS.RIGHT1}
                        </label>
                    </div>

                    <div class="checkbox" style="margin-top: 0; margin-bottom: 0;">
                        <label>
                            <input type="checkbox" name="rights[]" value="invoices"{if in_array("invoices", $rights)} checked=""{/if} />
                            {$lang.CONTACTS.RIGHT2}
                        </label>
                    </div>

                    <div class="checkbox" style="margin-top: 0; margin-bottom: 0;">
                        <label>
                            <input type="checkbox" name="rights[]" value="quotes"{if in_array("quotes", $rights)} checked=""{/if} />
                            {$lang.CONTACTS.RIGHT3}
                        </label>
                    </div>

                    <div class="checkbox" style="margin-top: 0; margin-bottom: 0;">
                        <label>
                            <input type="checkbox" name="rights[]" value="products"{if in_array("products", $rights)} checked=""{/if} />
                            {$lang.CONTACTS.RIGHT4}
                        </label>
                    </div>

                    <div class="checkbox" style="margin-top: 0; margin-bottom: 0;">
                        <label>
                            <input type="checkbox" name="rights[]" value="domains"{if in_array("domains", $rights)} checked=""{/if} />
                            {$lang.CONTACTS.RIGHT5}
                        </label>
                    </div>

                    <div class="checkbox" style="margin-top: 0; margin-bottom: 0;">
                        <label>
                            <input type="checkbox" name="rights[]" value="emails"{if in_array("emails", $rights)} checked=""{/if} />
                            {$lang.CONTACTS.RIGHT6}
                        </label>
                    </div>
                </div>
            </div>

            <input type="submit" class="btn btn-primary btn-block" value="{$lang.CONTACTS.EDIT}">
        </form>
        {else}
        <h1>{$lang.CONTACTS.TITLE}<span class="pull-right"><a href="{$cfg.PAGEURL}contacts/add"><i class="fa fa-plus-circle"></i></a></span></h1><hr />

        <p>{$lang.CONTACTS.INTRO}</p>

        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <tr>
                    <th>{$lang.CONTACTS.NAME}</th>
                    <th>{$lang.CONTACTS.POSITION}</th>
                    <th>{$lang.CONTACTS.EMAIL}</th>
                    <th>{$lang.CONTACTS.ACCESS}</th>
                    <th>{$lang.CONTACTS.EMAILS}</th>
                </tr>

                {foreach from=$contacts item=c}
                <tr>
                    <td><a href="{$cfg.PAGEURL}contacts/{$c.ID}">{$c.firstname|htmlentities} {$c.lastname|htmlentities}</a></td>
                    <td>{if $c.type}{$c.type|htmlentities}{else}-{/if}</td>
                    <td>{if $c.mail}{$c.mail|htmlentities}{else}-{/if}</td>
                    <td>{if $c.rights}{$lang.CONTACTS.YES}{else}{$lang.CONTACTS.NO}{/if}</td>
                    <td>{if $c.mail_templates}{$lang.CONTACTS.YES}{else}{$lang.CONTACTS.NO}{/if}</td>
                </tr>
                {foreachelse}
                <tr>
                    <td colspan="5">
                        <center>{$lang.CONTACTS.NOTHING}</center>
                    </td>
                </tr>
                {/foreach}
            </table>
        </div>
        {/if}
    </div>
</div><br />