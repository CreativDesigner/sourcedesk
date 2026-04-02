if($("#top_task_title").html() != ""){
    $("#working_rawtime").val(Date.now() - parseInt($("#working_rawtime").val()) * 1000);

    task_timer = setInterval(function () {
        var rawtime = $("#working_rawtime");
        $("#project_menu_time").html(formatTimeShort(Math.floor((Date.now() - parseInt($("#working_rawtime").val())) / 1000)));
    }, 1000);
}

function formatTimeShort(secs) {
    var hours = Math.floor(secs / 3600);
    secs -= hours * 3600;
    var minutes = Math.floor(secs / 60);
    secs -= minutes * 60;
    return (hours.toString().length == 1 ? "0" + hours : hours) + ":" + (minutes.toString().length == 1 ? "0" + minutes : minutes) + ":" + (secs.toString().length == 1 ? "0" + secs : secs);
}

function pauseTaskTime(task) {
    $.post("?p=ajax", {
        action: 'pause_task_time',
        task: task,
        csrf_token: $("#csrf_token").val(),
    }, function (response) {
        if(response == "ok"){
            if(typeof task_timer != 'undefined')
                clearInterval(task_timer);
            $("#project_top_menu").css('color', '');
            $(".project_top_task").hide();
            $("#project_menu_title").show();
            $("#project_menu_time").html("");
        }
    });
}