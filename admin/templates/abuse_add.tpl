<div class="row">
	<div class="col-lg-12">
		<h1 class="page-header">{$l.TITLE} <small>{$l.CREATE}</small></h1>

        {$warning}

        <form method="POST">
            <div class="form-group">
                <label>{$l.SUBJECT}</label>
                <input type="text" name="subject" class="form-control" value="{$subject|htmlentities}">
            </div>

            <div class="form-group">
                <label>{$l.REPORT}</label>
                <textarea name="answer" class="form-control" style="width: 100%; height: 150px;">{$report|htmlentities}</textarea>
            </div>

            <div class="form-group">
                <label>{$l.REACTIONTIME}</label>
                <div class="input-group">
                    <input type="text" name="reaction_time" class="form-control" value="48">
                    <span class="input-group-addon">{$l.HOURS}</span>
                </div>
            </div>

            <input type="submit" class="btn btn-primary btn-block" value="{$l.CREATE}">
        </form>
    </div>
</div>