$(document).ready(function(){
	window.options = {
		setAll: 0,
		rarityAll: 0
	}

	$(".moveTable").each(function(){
		if (!this.childNodes[1].childNodes.length){
			$(this).remove();// return;
		}
		$(this).DataTable({
			"paging": false,
			"info": false,
			"searching": false,
			"aaSorting": [[7, "asc"]],
			//"aaSorting": []
		})
		/*
		$(this).find("tbody").find("tr").each(function(){
			$(this).find("td").first().hover(
				function(){
					console.log($(this).find("a").first().html())
				},
				function(){
					console.log("out");
				}
			)
		})
		*/
		
		/*
		$(this).find("tbody tr").each(function(){
			$($(this).children()[1])
			.hover(
				function(){
				showChart($(this).parent().children()[0].childNodes[0].innerHTML, $(this).parent().parent().parent().children()[0].childNodes[0].childNodes[0].childNodes[0].innerHTML);
					//console.log("in");
				},
				function(){
					hideChart()
					//console.log("out");
				}
			)
		})
		*/
	})
	
	function showChart(set, name){
		console.log(arguments);
		//console.log("showChart");
	}
	function hideChart(){
		//console.log("hideChart");
	}

	$(document).contextmenu(function(e){
		//e.preventDefault(); e.stopPropagation();
	})


	$("#rarity").contextmenu(function(e){
		e.preventDefault(); e.stopPropagation();
		if (options.rarityAll){
			options.rarityAll = 0;
			$(this).parent().find("input").each(function(){
				$(this).prop("checked", options.rarityAll);
			})
		}
		else {
			options.rarityAll = 1;
			$(this).parent().find("input").each(function(){
				$(this).prop("checked", options.rarityAll);
			})
		}
	})

	$("#set").contextmenu(function(e){
		e.preventDefault(); e.stopPropagation();
		if (options.setAll){
			options.setAll = 0;
			$(this).parent().find("input").each(function(){
				$(this).prop("checked", options.setAll);
			})
		}
		else {
			options.setAll = 1;
			$(this).parent().find("input").each(function(){
				$(this).prop("checked", options.setAll);
			})
		}
	})
	
})