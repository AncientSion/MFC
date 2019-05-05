$(document).ready(function(){

	window.charter = new Charter();
	window.cardName = "";
	window.setName = "";

	$(".moveTable").each(function(){
		if (!this.childNodes[1].childNodes.length){
			$(this).remove();// return;
		}
		$(this).DataTable({
			"paging": false,
			"info": false,
			"searching": false,
			"aaSorting": [[2, "asc"]],
			//"aaSorting": []
		})
		
		$(this).find("tbody").find("tr").each(function(){
			$(this).find("td").first().hover(
				function(){
					window.cardName = $(this).find("a").first().html();
					window.setName = $(this).closest(".moveTable").find(".setName").html();
					showChart(setName, cardName)
				},
				function(){}
			)
		})
	})

	if (options.autoHideUI){
		toggleUI();
	}
})
	
function showChart(setname, cardname){
	//console.log("showChart");
	//console.log(set + " / " + card);
	charter.getCardData(0, setname, cardname);
}

$(document).contextmenu(function(e){
	//e.preventDefault(); e.stopPropagation();
})


$(".setDivider").contextmenu(function(e){
	e.preventDefault(); e.stopPropagation();
	
	$(this).find("input").each(function(){
		$(this).prop("checked", !($(this).prop("checked")))
	})
})

$("#toggleVis").click(toggleUI);

function toggleUI(){
	$(".checkWrapper").toggleClass("disabled");
	setRes();
}

function setRes(){
	var resY = window.innerHeight;
	var formHeight = $("form").height();
	var chartHeight = $(".mainContainer").height();
	var rem = (resY - formHeight - chartHeight - 220);
	$(".scrollWrapper").css("max-height", rem).css("height", rem);
}