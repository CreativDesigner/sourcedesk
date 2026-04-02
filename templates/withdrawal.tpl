<div id="content">
    <div class="container">
        <h1>{$lang.WITHDRAWAL.TITLE} {if $new_tos}
                <small><span class="label label-success">{$lang.TOS.NEW}</span></small>{/if}</h1>
        <hr>
        <p style="text-align:justify;">{$terms}</p>
        {if $new_tos}
            <center>
            <form method="POST"><input type="submit" class="btn btn-success btn-block" name="accept"
                                       value="{$lang.WITHDRAWAL.ACCEPT}"><br/></form></center>{/if}
    </div>
</div>