if($("#working_project").val() != 0){
    var task = $("#working_project").val();
    var task_rawtime = $("#rawtime_" + task).val(Date.now() - parseInt($("#rawtime_" + task).val()) * 1000);
    var task_acttime = $("#working_rawtime").val(Date.now() - parseInt($("#working_rawtime").val() * 1000));
    var gesTime = $("#gesTime").val(Date.now() - parseInt($("#gesTime").val() * 1000));
    
    add_working();
    task_timer = setInterval(function(){ add_working(); }, 1000);
}

function add_working() {
    var secs = Date.now() - parseInt(task_rawtime.val()) + 1000;
    $("#time_link_" + task).html(formatTimeUser(Math.round(secs / 1000, 0)));

    var secs = Date.now() - parseInt(task_acttime.val()) + 1000;
    $("#project_menu_title").hide();
    $("#project_menu_time").html(formatTimeShort(Math.round(secs / 1000, 0)));

    var secs = Date.now() - parseInt(gesTime.val()) + 1000;
    $("#gesTimec").show().html(formatTimeUser(Math.round(secs / 1000, 0)));
}

function deleteTaskTime(id) {
    $.post("?p=ajax", {
        action: "delete_task_time",
        id: id,
        csrf_token: $("#csrf_token").val(),
    }, function (responseText) {
        if(responseText == "ok")
            $("#taskTimeEntry_" + id).remove();
    })
}

function germanDate(date) {
    var ex = date.split(" ");
    var date = ex.shift().split(".");
    return date[2] + "-" + date[1] + "-" + date[0] + " " + ex.join(" ");
}

function calculateEnteredTime() {
    var start = germanDate($("#from").val().replace("-", ""));
    var end = germanDate($("#to").val().replace("-", ""));
    var time = $("#new_task_duration");
    var time_default = $("#new_task_duration_default");
    var diff = Date.parse(end) / 1000 - Date.parse(start) / 1000;
    
    if(isNaN(diff) || diff < 0){
        time.html("");
        time_default.show();
    } else {
        time_default.hide();

        time.html(formatTimeUser(diff));
    }
}

function formatTimeUser(diff) {
    diff = Math.round(diff, 0);
    if(Math.floor(diff / 60) == 0){
        if(diff == 1) formatted = "1 Sekunde";
        else formatted = diff + " Sekunden";
    } else if(Math.floor(diff / 3600) == 0) {
        if(Math.floor(diff / 60) == 1) formatted = "1 Minute";
        else formatted = Math.floor(diff / 60) + " Minuten";

        var secs = diff % 60;
        if(secs == 1) formatted += ", 1 Sekunde";
        else if(secs > 0) formatted += ", " + secs + " Sekunden";
    } else {
        if(Math.floor(diff / 3600) == 1) formatted = "1 Stunde";
        else formatted = Math.floor(diff / 3600) + " Stunden";

        var mins = Math.floor(diff % 3600 / 60);
        if(mins == 1) formatted += ", 1 Minute";
        else if(mins > 0) formatted += ", " + mins + " Minuten";

        var secs = diff % 60;
        if(secs == 1) formatted += ", 1 Sekunde";
        else if(secs > 0) formatted += ", " + secs + " Sekunden";
    }

    return formatted;
}

function formatTimeShort(secs) {
    secs = Math.round(secs, 0);
    var hours = Math.floor(secs / 3600);
    secs -= hours * 3600;
    var minutes = Math.floor(secs / 60);
    secs -= minutes * 60;
    return (hours.toString().length == 1 ? "0" + hours : hours) + ":" + (minutes.toString().length == 1 ? "0" + minutes : minutes) + ":" + (secs.toString().length == 1 ? "0" + secs : secs);
}

$('#time_modal').on('hide.bs.modal', function () {
    var task = $("#task_id").val();
    $.post("?p=ajax", {
        action: "get_task_time",
        task: task,
        csrf_token: $("#csrf_token").val(),
    }, function (response) {
        var info = $.parseJSON(response);
        $("#time_link_" + task).html(info.formatted);
        $("#rawtime_" + task).val(Date.now() - info.raw * 1000);
        $("#gesTimec").html(info.formatted_all);
        $("#gesTime").val(Date.now() - info.raw_all * 1000);
    });
});

function addTaskTime() {
    if($("#task_id").val() == 0) return;

    $.post("?p=ajax", {
        action: "add_task_time",
        task: $("#task_id").val(),
        from: $("#from").val(),
        to: $("#to").val(),
        staff: $("#staff").val(),
        csrf_token: $("#csrf_token").val(),
    }, function (response) {
        var value = $.parseJSON(response);
        addTaskTimeRow(value.ID, value.from, value.to, value.duration, value.staff);
    });
}

function emptyNewTaskTimeRow() {
    $("#from").val("");
    $("#to").val("");
    $("#new_task_duration_default").show();
    $("#new_task_duration").html("");
    $("#staff").prop('selectedIndex', 0);
}

function loadTaskTime(taskId, clear) {
    if(clear === true){
        $("#task_id").val(0);
        deleteAllTaskTimeRows();
        setTaskTimeTableHeading("");
    }

    $.post("?p=ajax", {
        action: 'load_task_times',
        id: taskId,
        csrf_token: $("#csrf_token").val(),
    }, function (response) {
        var info = $.parseJSON(response);

        $("#taskTimeWaiting").hide();
        setTaskTimeTableHeading(info.name);
        $("#task_id").val(taskId);

        if(clear !== true){
            deleteAllTaskTimeRows();
            $("#taskTimeWaiting").hide();
        }
        info.times.forEach(function (value) {
            if(value.done)
                addTaskTimeRow(value.ID, value.from, value.to, value.duration, value.staff);
            else
                addTaskLiveRow(value.ID, value.from, value.to, value.secs, value.staff, value.duration);
        });

        emptyNewTaskTimeRow();
    });
}

function addTaskTimeRow(ID, from, to, duration, staff) {
    var code = '<tr class="taskTimeEntry" id="taskTimeEntry_' + ID + '">';
    code += '<td>' + from + '</td>';
    code += '<td>' + to + '</td>';
    code += '<td>' + duration + '</td>';
    code += '<td>' + staff + '</td>';
    code += '<td><a href="#" onclick="deleteTaskTime(' + ID + '); return false;"><i class="fa fa-minus-square-o"></i></a></td>';
    code += '</tr>';

    $("#taskTimeTableNew").before(code);
    emptyNewTaskTimeRow();
}

function addTaskLiveRow(ID, from, to, secs, staff, duration) {
    var code = '<tr class="taskTimeEntry" id="taskTimeEntry_' + ID + '">';
    code += '<td>' + from + '</td>';
    code += '<td><i>L&auml;uft noch</i></td>';
    code += '<td><span id="live_modal">' + formatTimeUser(secs) + '</span><span id="live_raw" style="display: none;">' + (Date.now() - secs * 1000) + '</span> <a href="#" onclick="pauseTaskTime(' + $("#task_id").val() + '); return false;"><i class="fa fa-pause" style="color: orange;"></i></a></td>';
    code += '<td>' + staff + '</td>';
    code += '<td><a href="#" onclick="pauseTaskTime(' + $("#task_id").val() + '); deleteTaskTime(' + ID + '); return false;"><i class="fa fa-minus-square-o"></i></a></td>';
    code += '</tr>';

    setInterval(function() { updateModalLiveTime(); }, 1000);

    $("#taskTimeTableNew").before(code);
    emptyNewTaskTimeRow();
}

function updateModalLiveTime() {
    $("#live_modal").html(formatTimeUser((Date.now() - parseInt($("#live_raw").html())) / 1000));
}

function deleteAllTaskTimeRows() {
    $("#taskTimeWaiting").show();
    $(".taskTimeEntry").remove();
}

function setTaskTimeTableHeading(taskName) {
    $("#task_name").html(taskName);
}

function pauseTaskTime(task) {
    $.post("?p=ajax", {
        action: 'pause_task_time',
        task: task,
        csrf_token: $("#csrf_token").val(),
    }, function (response) {
        if(response == "ok"){
            if($("#task_id").val() == task)
                loadTaskTime(task, false);

            if(typeof task_timer != 'undefined')
                clearInterval(task_timer);
            $("#rawtime").val((Date.now() - $("#rawtime").val()) / 1000);
            $("#rawtime_" + task).val((Date.now() - $("#rawtime_" + task).val()) / 1000);
            $("#gesTime").val((Date.now() - $("#gesTime").val()) / 1000);

            $(".stop_btn").hide();
            $(".start_btn").show();
            $("#project_top_menu").css('color', '');
            $(".project_top_task").hide();
            $("#project_menu_title").show();
            $("#project_menu_time").html("");
        }
    });
}

function getQueryVariable(variable) {
    var query = window.location.search.substring(1);
    var vars = query.split("&");
    for (var i=0;i<vars.length;i++) {
        var pair = vars[i].split("=");
        if (pair[0] == variable) {
            return pair[1];
        }
    }
}

function startTaskTime(task) {
    $.post("?p=ajax", {
        action: 'start_task_time',
        task: task,
        csrf_token: $("#csrf_token").val(),
    }, function (response) {
        if(response == "ok"){
            $("#actTime").val(Date.now());
            $("#project_menu_title").hide();
            $("#project_menu_time").html(formatTimeShort(0));
            $("#time_link_" + task).html(formatTimeUser(parseInt($("#rawtime_" + task).val())));
            $("#rawtime_" + task).val(Date.now() - parseInt($("#rawtime_" + task).val()) * 1000);
            $("#gesTime").val(Date.now() - parseInt($("#gesTime").val()) * 1000);
            task_timer = setInterval(function () {
                var task_rawtime = $("#rawtime_" + task);
                var start = parseInt(task_rawtime.val());
                $("#time_link_" + task).html(formatTimeUser((Date.now() - start) / 1000));

                var task_acttime = $("#actTime");
                var start = parseInt(task_acttime.val());
                $("#project_menu_time").html(formatTimeShort((Date.now() - start) / 1000));

                var gesTime = $("#gesTime");
                var start = parseInt(gesTime.val());
                $("#gesTimec").show().html(formatTimeUser((Date.now() - start) / 1000));
            }, 1000);

            $(".start_btn").hide();
            $("#stop_btn_" + task).show();
            $("#project_top_menu").css('color', 'orange');
            $(".project_top_task").show();

            var projurl = $("#top_task_url");
            projurl.prop("href", projurl.prop("href").replace("###ID###", getQueryVariable("id")));
            $("#top_task_title").html($("#project_name").val());
            $("#top_task_stop").attr("onclick", "pauseTaskTime(" + task + "); return false;");
        }
    });
}
