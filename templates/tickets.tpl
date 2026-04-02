<div id="content">
	<div class="container">
        {if $step == "add"}
        <h1>{$lang.TICKETS.ADD}<span class="pull-right"><a href="{$cfg.PAGEURL}tickets" data-toggle="tooltip" title="{$lang.TICKETS.TITLE}" data-placement="left"><i class="fa fa-mail-reply"></i></a></span></h1><hr />

        {if !empty($error)}<div class="alert alert-danger">{$error}</div>{/if}

        <form method="POST" enctype="multipart/form-data">
            {if $owners|@count > 1}
            <div class="form-group">
                <label>{$lang.TICKETS.OWNER}</label>
                <select name="owner" class="form-control">
                    {foreach from=$owners key=id item=name}
                    <option value="{$id}"{if isset($smarty.request.owner) && $id == $smarty.request.owner} selected=""{/if}>{$name|htmlentities}</option>
                    {/foreach}
                </select>
            </div>
            {/if}
        
            <div class="form-group">
                <label>{$lang.TICKETS.DEPT}</label>
                <select name="dept" class="form-control">
                    {foreach from=$depts key=id item=name}
                    <option value="{$id}"{if isset($smarty.post.dept) && $id == $smarty.post.dept} selected=""{/if}>{$name}</option>
                    {/foreach}
                </select>
            </div>

            <div class="form-group">
                <label>{$lang.TICKETS.PRIORITY}</label>
                <select name="priority" class="form-control">
                    {foreach from=$priorities key=id item=name}
                    <option value="{$id}"{if (isset($smarty.post.priority) && $id == $smarty.post.priority) || (!isset($smarty.post.priority) && $id == 3)} selected=""{/if}>{$name}</option>
                    {/foreach}
                </select>
            </div>

            <div class="form-group">
                <label>{$lang.TICKETS.SUBJECT}</label>
                <input type="text" name="subject" placeholder="{$lang.TICKETS.SUBJECTP}" class="form-control" value="{if isset($smarty.post.subject)}{$smarty.post.subject}{/if}">
            </div>

            <div class="form-group">
                <label>{$lang.TICKET.TEXT}</label>
                <textarea name="answer" placeholder="{$lang.TICKETS.MESP}" class="form-control" style="resize: vertical; height: 150px; width: 100%;">{if isset($smarty.post.answer)}{$smarty.post.answer}{/if}</textarea>
            </div>
 
            <div class="form-group">
                <label>{$lang.TICKET.ATTACHMENTS}</label>
                <input type="file" name="attachments[]" multiple="multiple" class="form-control">
            </div>

            <input type="submit" class="btn btn-primary btn-block" value="{$lang.TICKETS.SEND}">
        </form><br />
        {else}
        <h1>{$lang.TICKETS.TITLE}<span class="pull-right"><a href="{$cfg.PAGEURL}tickets/add"><i class="fa fa-plus-circle"></i></a></span></h1><hr />

        {$lang.TICKETS.INTRO}<br /><br />

        {if $t|@count == 0 && !$search}
        <i>{$lang.TICKETS.NOTHING}</i>
        {else}
        <form method="POST" class="form-inline">
            <div class="input-group">
                <input type="text" name="searchword" class="form-control" value="{if isset($smarty.post.searchword)}{$smarty.post.searchword|htmlentities}{/if}" placeholder="{$lang.TICKETS.SEARCH}" minlength="4" required="">
                <div class="input-group-btn">
                <button class="btn btn-primary" type="submit">
                    <span class="fa fa-search"></span>
                </button>
                </div>
            </div>
        </form><br />

        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <tr>
                    <th width="10%">#</th>
                    <th width="13%">{$lang.TICKETS.CREATED}</th>
                    <th width="13%">{$lang.TICKETS.DEPT}</th>
                    <th>{$lang.TICKETS.SUBJECT}</th>
                    <th width="13%">{$lang.TICKETS.PRIORITY}</th>
                    <th width="14%">{$lang.GENERAL.STATUS}</th>
                    <th width="15%">{$lang.TICKETS.LASTANSWER}</th>
                </tr>

                {foreach from=$t item=i}
                <tr>
                    <td>T#{$i.lid}</td>
                    <td>{dfo d=$i.created}</td>
                    <td>{$i.t->getDepartmentName()}</td>
                    <td><a href="{$i.url}">{$i.subject|htmlentities}</a></td>
                    <td>{$i.t->getPriorityStr()}</td>
                    <td>{$i.t->getStatusStr()}</td>
                    <td>{$i.t->getLastAnswerStr()}</td>
                </tr>
                {foreachelse}
                <tr>
                    <td colspan="7"><center>{$lang.TICKETS.NORESULTS}</center></td>
                </tr>
                {/foreach}
            </table>
        </div>
        {/if}
        {/if}
    </div>
</div>