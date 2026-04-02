<div class="container">
    <h1>{$l.NAME} <small>{$l.AV}</small></h1><hr>

    {if $av}
    <p style="text-align: justify;">{$l.DONE}</p>

    <a href="{$cfg.PAGEURL}dsgvo/av/pdf" target="_blank"><i class="fa fa-file-pdf-o"></i> {$l.PDF}</a><br /><br />
    {else}
    <p style="text-align: justify;">{$l.INTRO}</p>

    <a href="{$cfg.PAGEURL}dsgvo/av/pdf" target="_blank"><i class="fa fa-file-pdf-o"></i> {$l.EXAMPLE}</a><br /><br />

    {if !$logged_in}
    <div class="alert alert-warning">{$l.NEEDTOLOGIN}</div>
    {else}
    <p style="text-align: justify; font-weight: bold;">{$l.WID}</p>

    <a href="{$cfg.PAGEURL}dsgvo/av/order" class="btn btn-primary btn-block">{$l.ORDER}</a>
    {/if}
    {/if}
</div><br />
