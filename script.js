class Charter {
	
	constructor(multi = 0){
		this.data = [];
		this.draws = 0;
		this.setIndex = 0;
		this.screens = [];
		this.isLoaded = 0;
		this.cardName = "";
		this.setCode = "";
		this.loadPics = 1;

		if (!multi){
			this.screens[0] = {};
			this.screens[0].foilPriceCtx = document.getElementById("foilPriceCanvas") ? document.getElementById("foilPriceCanvas").getContext("2d") : false;
			this.screens[0].foilAvailCtx = document.getElementById("foilAvailCanvas") ? document.getElementById("foilAvailCanvas").getContext("2d") : false;
			this.screens[0].basePriceCtx = document.getElementById("basePriceCanvas") ? document.getElementById("basePriceCanvas").getContext("2d") : false;
			this.screens[0].baseAvailCtx = document.getElementById("baseAvailCanvas") ? document.getElementById("baseAvailCanvas").getContext("2d") : false;
		}

		this.loadAllCards(this);
	};

	toggleLoadPics(){
		this.loadPics = !this.loadPics;
	}

	isValidSetSelected(element){
		let search = $(element).parent().find(".setSearch").val();
		var valid = false;
		for (var i = 0; i < this.data.length; i++){
			if (this.data[i].setname == search || this.data[i].setcode == search){
				this.setIndex = i;
				return true;
			}
		}
		return false;
	}

	addCardAutoComplete(element){
		var suggest = [];
		for (let i = 0; i < this.data[this.setIndex].cards.length; i++){
			suggest.push(this.data[this.setIndex].cards[i].cardname);
		}
		//console.log("init");
		$(element).autocomplete({source: suggest});
	}

	getCardData(screen, setName, cardName){
		if (!setName || !cardName){
			return;
		}

		var set = this.getFullSet(setName);
		var others = this.getOthers(cardName);

		if (!set || !cardName){
			return;
		}

		this.getPriceData(screen, set.setcode, cardName);
		
	}
	
	getOthers(cardName){
		$(".reprints").empty();
		
		var results = [];
		
		for (let i = 0; i < this.data.length; i++){
			for (let j = 0; j < this.data[i].cards.length; j++){
				if (this.data[i].cards[j].cardname == cardName){
					results.push(this.data[i].setcode);
					break;
				}
			}
		}
		
		var divs = [];
		
		for (let i = 0; i < results.length; i++){
			$(".reprints").append(
				$("<input>")
				.attr("value", results[i])
				.attr("type", "button")
				.click(function(){
					$(".setSearch").val($(this).val());
					charter.getPriceData(0, $('.setSearch').val(), $('.cardSearch').val());
				})
			)
		}
	}

	getFullSet(setName){
		for (let i = 0; i < this.data.length; i++){
			if (this.data[i].setname == setName || this.data[i].setcode == setName){
				return this.data[i];
			}
		}
		return false;
	}

	getCardName(set, cardName){
		for (var i = 0; i < set.cards.length; i++){
			if (set.cards[i].cardname == cardName){
				return set.cards[i].cardname;
			}
		}
		return false;
	}

	getSetCodeBySetName(setname){
		for (let i = 0; i < this.data.length; i++){
			if (this.data[i].setname == setname){
				return this.data[i].setcode;
			}
		}
		return false;
	}

	getSetnameBySetcode(setcode){
		for (let i = 0; i < this.data.length; i++){
			if (this.data[i].setcode == setcode){
				return this.data[i].setname;
			}
		}
		return false;
	}

	getLabel(points){
		var ret = [];
		var year = 18;
		for (let i = 0; i < points.length; i++){
			//ret.push(points[i].date.substr(0, 5));
			ret.push(points[i].date.substr(5, 10));
		}
		return ret;
	}

	getCardDataSets(points, card, set){

		var sets = [[], [], [], []];
		var labels = ["Foil #", "Foil €", "Base #", "Base €"];
		var ref = ["foilAvail", "foilPrice", "baseAvail", "basePrice"];
		var colors = ["rgba(0,255,0,1)", "rgba(0,255,0,1)", "rgba(0,255,255,1)", "rgba(0,255,255,1)"];
		var title = card + " - " + set;
		var foilAvail = [];
		var foilPrice = [];
		var baseAvail = [];
		var basePrice = [];

		for (let i = 0; i < points.length; i++){
			sets[0].push(points[i].foilAvail);
			sets[1].push(points[i].foilPrice);
			sets[2].push(points[i].baseAvail);
			sets[3].push(points[i].basePrice);
		}

		var returnData = [];

		for (let i = 0; i < sets.length; i++){
			returnData.push([{
				"ref": ref[i],
				"label": (labels[i] + " - " + set + " " + card),
				"data": sets[i],
				"fontColor": "white",
			//	"fontSize": 4,
				"pointRadius": 1,
				//"pointColor": "red",
				"borderColor": colors[i],
				"backgroundColor": colors[i],
				"borderWidth": 2,
				"fill": false,
			}])
		}
		return returnData;
	}

	getTickData(points){
		var ret = [];

		for (let i = 0; i < points.length; i++){
			var min = 1000;
			var max = 0;
			for (let j = 0; j < points[i].length; j++){
				for (let k = 0; k < points[i][j].data.length; k++){
					min = Math.min(min, points[i][j].data[k]);
					max = Math.max(max, points[i][j].data[k]);
				}
			}
			
			//console.log("min: " + min + ", max: " + max);
			ret.push({suggestedMin: Math.floor(min - min*0.05), suggestedMax: Math.ceil(max + max*0.05), fontColor: "white"});
		}
		return ret;
	}

	
//function doReplace($name){return str_replace("'", "", str_replace(" ", "-", str_replace(",", "", $name)));

	getMKMCardURL(setcode, cardname){
		if (!this.isLoaded){return;}
		setcode = setcode.length < 4 ? this.getSetnameBySetcode(setcode) : setcode
		
		setcode = setcode.replace(/ /g, "-");
		setcode = setcode.replace(/:/g, "");
		setcode = setcode.replace(/-- /g, "");
		cardname = cardname.replace(/ /g, "-");
		cardname = cardname.replace(/'/g, "");
		cardname = cardname.replace(/,/g, "");
		cardname = cardname.replace("//", "");
		cardname = cardname.replace("/,", "");
		cardname = cardname.replace(/--/g, "-");
		cardname = cardname.replace(":", "");
		
		//console.log(setcode);
		//console.log(cardname);		
		
		var url = "https://www.cardmarket.com/en/Magic/Products/Singles/" + (setcode + "/" + cardname);
		return url;
	}

	linkToMKM(element){
		let set = "";
		let card = "";
		$(element).parent().parent().find("input").each(function(i){
			if (!i){
				set = $(this).val();
			} else if (i == 1){card = $(this).val()}
		});
		console.log(set, card);

		let url = this.getMKMCardURL(set, card)

		if (!url){return;}
		window.open(url, '_blank');
	}

	linkToChartsPHP(element){
		let set = "";
		let card = "";
		$(element).parent().parent().find("input").each(function(i){
			if (!i){
				set = $(this).val();
			} else if (i == 1){card = $(this).val()}
		});

		window.open("charts.php?type=preset&set="+set+"&card="+card, '_blank');
	}


	buildAllCards(screen, cardname, setcode, data){
		
		if ($("#cardName").length){
			var url = this.getMKMCardURL(setcode, cardname)
			var link = "<a target='_blank' href='" + url + "'>click dat link to mkm</a>";
		//	var link = this.getMKMCardURL($(".setSearch").val(), card);
			$("#cardName").html(link);
		}

		this.undrawOldCharts(screen);
		this.draws++;

		var label = this.getLabel(data);
		var cardData = this.getCardDataSets(data, cardname, setcode);
		var tickData = this.getTickData(cardData);

		//$("#cardName").html(card + " - " + set);



		if (this.screens[screen].foilAvailCtx && this.isValidData(cardData[0][0])){this.buildChart(screen, label, cardData[0], tickData[0]);}
		if (this.screens[screen].foilPriceCtx && this.isValidData(cardData[1][0])){this.buildChart(screen, label, cardData[1], tickData[1]);}
		if (this.screens[screen].baseAvailCtx && this.isValidData(cardData[2][0])){this.buildChart(screen, label, cardData[2], tickData[2]);}
		if (this.screens[screen].basePriceCtx && this.isValidData(cardData[3][0])){this.buildChart(screen, label, cardData[3], tickData[3]);}		
	}

	isValidData(points){
		for (let i = 0; i < points.data.length; i++){
			if (points.data[i]){return true;}
		} return false;
	}

	undrawOldCharts(screen){
		if (!this.draws){$(".container").addClass("border"); return;}
		//console.log("undraw");

		if (this.screens[screen].foilAvailChart){
			this.screens[screen].foilAvailChart.data.labels = [];
			this.screens[screen].foilAvailChart.data.datasets[0] = [];
			this.screens[screen].foilAvailChart.data.datasets[1] = [];
			this.screens[screen].foilAvailChart.update();
		}
		
		if (this.screens[screen].foilPriceChart){
			this.screens[screen].foilPriceChart.data.labels = [];
			this.screens[screen].foilPriceChart.data.datasets[0] = [];
			this.screens[screen].foilPriceChart.data.datasets[1] = [];
			this.screens[screen].foilPriceChart.update();
		}
		
		if (this.screens[screen].baseAvailChart){
			this.screens[screen].baseAvailChart.data.labels = [];
			this.screens[screen].baseAvailChart.data.datasets[0] = [];
			this.screens[screen].baseAvailChart.data.datasets[1] = [];
			this.screens[screen].baseAvailChart.update();
		}
		
		if (this.screens[screen].basePriceChart){
			this.screens[screen].basePriceChart.data.labels = [];
			this.screens[screen].basePriceChart.data.datasets[0] = [];
			this.screens[screen].basePriceChart.data.datasets[1] = [];
			this.screens[screen].basePriceChart.update();
		}
	}

	buildChart(screen, label, dataSets, tickData){
		
		//console.log(tickData);

		//console.log(this[(dataSets[0].ref) + "Chart"]);

		var chartData = {
				type: 'line',
				data: {
					labels: label,
					datasets: dataSets
				},
				options: {
					elements: {
						line: {
							tension: 0 // bezeir curv
						}
					},
			        animation: {
			            duration: 0,
			        },
			        hover: {
			            animationDuration: 0,
			        },
			        responsiveAnimationDuration: 0,
					legend: {
						display: true,
						labels: {
							fontColor: "white",
							fontSize: 12,
						}
					}, 
					title: {
						display: false,
						text: dataSets[0].title,
						fontSize: 24,
						fontColor: "white"
					},
					scales: {
						yAxes: [
						{	position: "right",
							gridLines: {
								display: false,
							},
							scaleLabel: {
								display: false,
								labelString: dataSets[0].label,
								fontSize: 20,
								fontColor: "white"
							}, 
			                ticks: tickData,
						}
						],
						xAxes: [
							{
								ticks: {
									display: 1,
									//beginAtZero: true,
									fontColor: "white",
									autoskip: true,
									maxTicksLimit: 10,
									fontSize: 9,
									minRotation: 0,
									maxRotation: 0
								},
								gridLines: {
									display: true,
									color: "rgba(255,255,255,0.5)"
								},
								scaleLabel: {
									display: 0,
									labelString: 'Month'
								}
							}
						],
					}
				}
		}

		
		this.screens[screen][dataSets[0].ref + "Chart"] = new Chart(this.screens[screen][dataSets[0].ref + "Ctx"], chartData); 
		return;


		this[(dataSets[0].ref) + "Chart"] = new Chart(this[dataSets[0].ref + "Ctx"], chartData);

	}

	initCardSearchInputs(element){

		this.addSetAutoComplete(element);

		$(element).find(".cardSearch").focus(function(){
			if (charter.isValidSetSelected(this)){
				charter.addCardAutoComplete(this);
			}
			else if ($(this).hasClass("ui-autocomplete-input")){
				$(this).autocomplete("destroy");
			}
		})
	}
	

	addSetAutoComplete(element){
		var tags = [];

		for (let i = 0; i < this.data.length; i++){
			tags.push(this.data[i].setcode);
			tags.push(this.data[i].setname);
		}
		$(element).find(".setSearch").autocomplete({source: tags});
	}

	getPriceData(screen, setcode, cardname){
		$.ajax({
			type: "GET",
			url: "charts.php",
			datatype: "json",
			data: {
				type: "price",
				set: setcode,
				card: cardname
			},
			success: function(data){
				charter.buildAllCards(screen, cardname, setcode, JSON.parse(data));
			},
			error: function(){console.log("error");}
		});
	}

	loadAllCards(self){
		//return;
		$.ajax({
			type: "GET",
			url: "charts.php",
			datatype: "json",
			data: {
				type: "cardlistNew"
			},
			success: function(data){
			//	console.trace();
				self.data = JSON.parse(data);
				self.isLoaded = 1;
				self.initCardSearchInputs($(".search").first());
				self.loadExistingCharts();
			},
			error: function(){console.log("error");}
		});
	}

	needsToLoadChart(){
		if (this.siteIsFavorites()){return true;}
		if (window.remote){return true;}
		return false;
	}

	loadExistingCharts(){

		if (!this.needsToLoadChart()){return;}
		let things = [];

		$(".mainContainer").each(function(){
			var chart = {}
			let can = $(this).find("canvas");
				can.each(function(){
					let type = $(this).attr("id");
						type = type.substr(0, type.length-6) + "Ctx";
					chart[type] = this.getContext("2d");
				})
			/*	can.click(function(){
					let set = "";
					let card = "";
					$(this).parent().parent().find("input").each(function(i){
						if (!i){
							set = $(this).val();
						} else card = $(this).val()
					})
				//	console.log(set, card);

					window.open("charts.php?type=preset&set="+set+"&card="+card, '_blank');
				})
			*/
			charter.screens.push(chart);

			let inputs = $(this).find("input");
			things.push($(inputs[0]).val())
			things.push($(inputs[1]).val())
		})

		for (let i = 0; i < things.length; i+=2){
			//console.log(things[i], things[i+1]);
			charter.getCardData(i/2, things[i], things[i+1]);
		}
	}

	addSingleFavorite($element){
		console.log("addSingleFavorite");
		let set = $element.closest(".moveTable").find(".setName").html();
		let card = $element.parent().find("a").first().html();
		let isFoil = $(".upper").find("input:radio").eq(0).attr("checked") == "checked" ? 1 : 0;
		$element.hide();
		this.postInsertFavs([set], [card], [isFoil]);
	}
	
	assembleFavData(){
		console.log("assembleFavData");

		let sets = [];
		let cards = [];
		let isFoils = [];

		$(".search").each(function(){
			sets.push($(this).find(".setSearch").val());
			cards.push($(this).find(".cardSearch").val());
			isFoils.push( $(this).find("input:checkbox").prop("checked") == true ? 1 : 0);
		})

	//	console.log(sets); console.log(cards); console.log(isFoils); return;

		for (let i = 0; i < sets.length; i++){
			if (sets[i].length > 4){
				sets[i] = charter.getSetCodeBySetName(sets[i]);
			}
		}

		this.postInsertFavs(sets, cards, isFoils);
	}

	deleteSingleFavorite(element){
		console.log("deleteSingleFavorite");
		let name = $(element).closest(".mainContainer").attr("class");
		let id = name.substr(2, name.indexOf(" ")-2)*1;
		//$element.addClass("posted");
		//return;
		this.postDeleteFavs([id]);
	}

	siteIsFavorites(){
		if (window.location.href.substr(window.location.href.length-8, window.location.href.length-1) == "favs.php"){
			return true;
		} return false;
	}

	postInsertFavs(sets, cards, isFoil){
		console.log("postInsertFavs");
        $.ajax({
            type: "POST",
            url: "favs.php",
            datatype: "json",
            data: {
                    type: "addNewFavs",
                    sets: sets,
                    cards: cards,
                    isFoil: isFoil
                },
            success: function(data){
				if (charter.siteIsFavorites()){
	            	$(".newEntryTable tbody tr").each(function(i){
	            		if (!i){return;}
	            		$(this).remove();
	 				})
	            	addNewRow();
				}
				else {
					$("input.posted").each(function(){
						//$(this).removeClass().addClass("added").hide();
						$(this).removeClass().prop("disabled", true);
					})
				}
            },
            error: function(){console.log("error")},
        });
	}

	postDeleteFavs(ids){
		console.log("postDeleteFavs");
        $.ajax({
            type: "POST",
            url: "favs.php",
            datatype: "json",
            data: {
                    type: "delNewFavs",
                    ids: ids
                },
            success: function(data){
            	return;
				if (charter.siteIsFavorites()){
	            	$(".newEntryTable tbody tr").each(function(i){
	            		if (!i){return;}
	            		$(this).remove();
	 				})
	            	addNewRow();
				}
				else {
					$("input.posted").each(function(){
						//$(this).removeClass().addClass("added").hide();
						$(this).removeClass().prop("disabled", true);
					})
				}
            },
            error: function(){console.log("error")},
        });
	}
}