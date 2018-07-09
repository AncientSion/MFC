$(document).ready(function(){
	window.options = {
		setAll: 0,
		rarityAll: 0,
		//charter: new Charter()
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
		
		/*$(this).find("tbody").find("tr").each(function(){
			$(this).find("td").first().hover(
				function(){
					showChart(
						$(this).parent().parent().parent().children().first().children().first().children().first().children().last().html(),
						$(this).find("a").first().html()
					)
				},
				function(){
					console.log("out");
				}
			)
		})*/
	})
	
	function showChart(set, name){
		//console.log("showChart");
		console.log(set);
		console.log(name);
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

	$(".setDivider").contextmenu(function(e){
		e.preventDefault(); e.stopPropagation();
		/*	var boxes = $(this).find("input");
			
			boxes.each(function(){
				console.log($(this).prop("checked"));
				$(this).prop("checked", !($(this).prop("checked")))
			})
		*/
		
		$(this).find("input").each(function(){
			$(this).prop("checked", !($(this).prop("checked")))
		})
		/*if (options.setAll){
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
		}*/
	})
	
})