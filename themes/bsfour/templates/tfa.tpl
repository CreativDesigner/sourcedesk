<div class="container">
    <div class="row" style="padding-top:20px;">
      <div class="col-md-3">&nbsp;</div>
      <div class="col-md-6">
        <div class="card">
          <div class="card-body">
            <h5 class="card-title">{$lang.TFA.TITLE}</h5>
          
            {if isset($alert)}{$alert}{/if}
            <form accept-charset="UTF-8" role="form" method="POST">
                    <fieldset>
                <div class="form-group">
                  <input class="form-control" placeholder="{$lang.TFA.CODE}" required="" name="code" type="text">
              </div>
              <input class="btn btn-lg btn-success btn-block" type="submit" name="check" value="{$lang.GENERAL.NEXTSTEP} &raquo;">
            </fieldset>
              </form>
          </div>
      </div>
    </div>
  </div>
</div>