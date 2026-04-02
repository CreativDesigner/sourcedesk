<div class="row">
	<div class="col-lg-12">
		<h1 class="page-header">{$l.TITLE} <small>{$a.subject|htmlentities}</small></h1>

		<div class="row">
            <div class="col-md-6">
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        {$l.REPORT}
                    </div>
                    <div class="panel-body">
                        <form method="POST">                        
                            <div class="form-group">
                                <label>{$l.SUBJECT}</label>
                                <input type="text" name="subject" value="{$a.subject|htmlentities}" class="form-control">
                            </div>

                            <div class="form-group">
                                <label>{$l.STATUS}</label>
                                <select name="status" class="form-control">
                                    <option value="open">{$l.OPEN}</option>
                                    <option value="resolved"{if $a.status == "resolved"} selected=""{/if}>{$l.RESOLVED}</option>
                                </select>
                            </div>

                            <div class="form-group" style="position: relative;">
                                <label>{$l.TIME}</label>
                                <input type="text" name="time" class="form-control datetimepicker" value="{dfo d=$a.time}">
                            </div>

                            <div class="form-group" style="position: relative;">
                                <label>{$l.DEADLINE}</label>
                                <input type="text" name="deadline" class="form-control datetimepicker" value="{dfo d=$a.deadline}">
                            </div>

                            <div class="form-group">
                                <label>{$l.CUSTOMER}</label>
                                <input type="text" class="form-control customer-input" placeholder="{$l.NOTASSIGNED}" value="{$ci}">
                                <input type="hidden" name="user" value="{$a.user}">
                                <div class="customer-input-results"></div>
                            </div>

                            <div class="form-group">
                                <label>{$l.SERVICE}</label>
                                <select name="service" class="form-control" disabled="">
                                </select>
                            </div>

                            <script>
                            $(document).ready(function() {
                                servicesFor = -1;

                                function getServices() {
                                    if (servicesFor == $("[name=user]").val()) {
                                        return;
                                    }

                                    $("[name=service]").prop("disabled", true).val("0");
                                    $(".customer-input").prop("disabled", true);

                                    $.post("", {
                                        "user_services": $("[name=user]").val(),
                                        "csrf_token": "{ct}",
                                    }, function(r) {
                                        servicesFor = $("[name=user]").val();
                                        $("[name=service]").prop("disabled", false);
                                        $(".customer-input").prop("disabled", false);
                                        $("[name=service]").html(r);
                                    });
                                }

                                getServices();
                                $("[name=user]").change(getServices);
                            });
                            </script>

                            <input type="submit" class="btn btn-primary btn-block" value="{$l.SAVE}">
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        {$l.MESSAGES}
                    </div>
                    <div class="panel-body">
                        <form method="POST">
                            <textarea name="answer" class="form-control" style="width: 100%; height: 150px; resize: vertical; margin-bottom: 10px;"></textarea>
                            <input type="submit" class="btn btn-primary btn-block" value="{$l.ANSWER}">
                        </form>
                        <hr />

                        {foreach from=$messages item=msg key=i}
                            <h3>{$l.{$msg.author|strtoupper}} <small>{dfo d=$msg.time}</small></h3>

                            {$msg.text|htmlentities|nl2br}

                            {if $i + 1 < $messages|count}
                            <hr />
                            {/if}
                        {/foreach}
                    </div>
                </div>
            </div>
        </div>
	</div>
</div>