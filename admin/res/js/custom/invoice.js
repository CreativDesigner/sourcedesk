var product_quote = 0;

$(document).ready(function() {
	function commit() {
		var desc = "";
		$("#chprse").find("option").each(function() {
			if ($(this).html() == $("#chprse").val()) {
				if ($(this).data("desc")) {
					desc = "\n\n" + $(this).data("desc");
				}
			}
		});

		var s = $("#chprse").val().split("-");
		$("[name='invoiceitem_amount[" + product_quote + "]']").val(s.pop());
		$("[name='invoiceitem_description[" + product_quote + "]']").val((desc ? '<b>' : '') + s.join("-").trim() + (desc ? "</b>" + desc : ""));
		$("#choose_product").modal("hide");
	}

	var element = $("#invoiceitem_template").wrap("<div>").parent();
	template = element.html();
	element.remove();

	var stageElement = $("#stage_template").wrap("<div>").parent();
	stageTemplate = stageElement.html();
	stageElement.remove();

	$(".select_product").unbind("click").click(function(e) {
		e.preventDefault();
		product_quote = $(this).data("id");
		$("#choose_product").modal("show");
	});

	$("#chprbt").click(function(e) {
		e.preventDefault();
		commit();
	});
	$("#chprfo").submit(function(e) {
		e.preventDefault();
		commit();
	});
});

function addStage(){
	var last = $(".stage").last();
	var newId = parseInt(last.prop("id").split("_")[1]) + 1;
	var newElement = last.after(stageTemplate.split('#ID#').join(newId)).next();

	newElement.prop("class", "stage").prop("id", "stage_" + newId).show();
}

function deleteStage(id){
	$("#stage_" + id).remove();
}

function addRow(){
	var last = $(".invoiceitem").last();
	var newId = parseInt(last.prop("id").split("_")[1]) + 1;
	var newElement = last.after(template.split('#ID#').join(newId)).next();
	
	newElement.prop("class", "invoiceitem").prop("id", "invoiceitem_" + newId).show();
	deleteLinkDecide();

	if (editprevUndisabled) {
		$(".editprev").prop("disabled", false);
	}
}

function deleteRow(){
	$(".invoiceitem").last().remove();
	deleteLinkDecide();
}

function deleteLinkDecide() {
	$(".select_product").unbind("click").click(function(e) {
		e.preventDefault();
		product_quote = $(this).data("id");
		$("#choose_product").modal("show");
	});

	if($(".invoiceitem").length > 1) {
		$(".resize-col").removeClass("col-md-9").addClass("col-md-8");
		$(".resize-col-small").removeClass("col-md-8").addClass("col-md-7");
		$(".resize-col-xs").removeClass("col-md-7").addClass("col-md-6");
		$(".delete-col").show();

		$(".delete_position").unbind('click').click(function() {
			$(this).parent().parent().parent().remove();
			deleteLinkDecide();
		});
	} else {
		$(".resize-col").addClass("col-md-9").removeClass("col-md-8");
		$(".resize-col-small").addClass("col-md-8").removeClass("col-md-7");
		$(".resize-col-xs").removeClass("col-md-6").addClass("col-md-7");
		$(".delete-col").hide();
	}
}

deleteLinkDecide();