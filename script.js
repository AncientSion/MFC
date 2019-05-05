class Charter {
	
	constructor(multi = 0){
		this.data;
		this.draws = 0;
		this.screens = [];

		if (!multi){
			this.screens[0] = {};
			this.screens[0].foilPriceCtx = document.getElementById("foilPriceCanvas") ? document.getElementById("foilPriceCanvas").getContext("2d") : false;
			this.screens[0].foilAvailCtx = document.getElementById("foilAvailCanvas") ? document.getElementById("foilAvailCanvas").getContext("2d") : false;
			this.screens[0].basePriceCtx = document.getElementById("basePriceCanvas") ? document.getElementById("basePriceCanvas").getContext("2d") : false;
			this.screens[0].baseAvailCtx = document.getElementById("baseAvailCanvas") ? document.getElementById("baseAvailCanvas").getContext("2d") : false;
		
			//this.getData("cardList");
		}

		this.getData("cardList");


	/*	this.foilPriceCtx = document.getElementById("foilPriceCanvas") ? document.getElementById("foilPriceCanvas").getContext("2d") : false;
		this.foilAvailCtx = document.getElementById("foilAvailCanvas") ? document.getElementById("foilAvailCanvas").getContext("2d") : false;
		this.basePriceCtx = document.getElementById("basePriceCanvas") ? document.getElementById("basePriceCanvas").getContext("2d") : false;
		this.baseAvailCtx = document.getElementById("baseAvailCanvas") ? document.getElementById("baseAvailCanvas").getContext("2d") : false;
	
		this.getData(this, "cardList", "addAutocomplete");
	*/
	};

	isValidSetSelected(){
		var entered = $("#setSearch").val();
		var valid = false;
		var i = 0;
		for (var i = 0; i < this.data.length; i++){
			if (this.data[i].name == entered || this.data[i].code == entered){
				valid = true; break;
			}
		}

		if (valid){
			var suggest = [];
			for (let j = 0; j < this.data[i].cards.length; j++){
				suggest.push(this.data[i].cards[j].name);
			}
			//console.log("init");
			$("#cardSearch").autocomplete({source: suggest});
		}
		else {
			//console.log("destroy");
			$("#cardSearch").autocomplete("destroy");
		}
	}

	getCardData(screen, setName, cardName){
		if (!setName || !cardName){return;}

		var set = this.getFullSet(setName);
		var card = this.getCardName(set, cardName);
		var others = this.getOthers(cardName);

		if (!set || !card){return;}

		//console.log("valid, setName: " + setName + ", cardName: " + cardName);
		this.getPriceData(screen, set.code, card);
		
	}
	
	getOthers(cardName){
		$(".reprints").empty();
		
		var results = [];
		
		for (let i = 0; i < this.data.length; i++){
			for (let j = 0; j < this.data[i].cards.length; j++){
				if (this.data[i].cards[j].name == cardName){
					results.push(this.data[i].code);
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
					$("#setSearch").val($(this).val());
					charter.getPriceData(0, $('#setSearch').val(), $('#cardSearch').val());
				})
			)
		}
	}

	getFullSet(setName){
		for (let i = 0; i < this.data.length; i++){
			if (this.data[i].name == setName || this.data[i].code == setName){
				return this.data[i];
			}
		}
		return false;
	}

	getCardName(set, cardName){
		for (var i = 0; i < set.cards.length; i++){
			if (set.cards[i].name == cardName){
				return set.cards[i].name;
			}
		}
		return false;
	}

	getPriceData(screen, set, card){
		$.ajax({
			type: "GET",
			url: "charts.php",
			datatype: "json",
			data: {
				type: "price",
				set: set,
				card: card
			},
			success: function(data){
				charter.buildAllCards(screen, card, set, JSON.parse(data));
			},
			error: function(){console.log("error");}
		});
	}

	getLabel(points){
		//console.log("points " + points.length);
		var ret = [];
		var year = 18;
		for (let i = 0; i < points.length; i++){
			if (points[i].time.substr(8, points[i].time.length-1) == year){
				ret.push([points[i].time.substr(0, 5), year]);
				year++;
			} else ret.push(points[i].time.substr(0, 5));

			//ret[ret.length-1] = 0;
		}
		return ret;
	}

	getCardDataSets(points, card, set){

		var sets = [[], [], [], []];
		var labels = ["Foil Stock", "Foil Price", "Base Stock", "Base Price"];
		var ref = ["foilAvail", "foilPrice", "baseAvail", "basePrice"];
		var colors = ["rgba(0,255,0,1)", "rgba(0,255,0,1)", "rgba(0,255,255,1)", "rgba(0,255,255,1)"];
		var title = card + " - " + set;
		var foilAvail = [];
		var foilPrice = [];
		var baseAvail = [];
		var basePrice = [];

		for (let i = 0; i < points.length; i++){
			sets[0].push(points[i].data.foilAvail || 0);
			sets[1].push(points[i].data.foilPrice || 0);
			sets[2].push(points[i].data.baseAvail || 0);
			sets[3].push(points[i].data.basePrice || 0);
		}

		var returnData = [];

		for (let i = 0; i < sets.length; i++){
			returnData.push([{
				"ref": ref[i],
				"label": (labels[i] + " - " + set + " " + card),
				"data": sets[i],
				"fontColor": "white",
				"fontSize": 14,
				"pointRadius": 0,
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

	getCardLink(set, name){
		
		set = set.length < 4 ? this.getFullSet(set).name : set
		
		set = set.replace(/ /g, "-");
		name = name.replace(/ /g, "-");
		name = name.replace(/'/g, "");
		name = name.replace(/,/g, "");
		name = name.replace("//", "");
		name = name.replace("/,", "");
		name = name.replace(/--/g, "-");
		name = name.replace(":", "");
		
		//console.log(set);
		//console.log(name);
		
		
		var url = "https://www.cardmarket.com/en/Magic/Products/Singles/" + (set + "/" + name);
		var link = "<a target='_blank' href='" + url + "'>click dat link to mkm</a>";
		return link
	}

	buildAllCards(screen, card, set, data){
		
		if ($("#cardName").length){
			var link = this.getCardLink($("#setSearch").val(), card);
			$("#cardName").html(link);
		}

		this.undrawOldCharts(screen);
		this.draws++;

		var label = this.getLabel(data);
		var cardData = this.getCardDataSets(data, card, set);
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
							fontSize: 16,
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
									fontSize: 12,
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

	addAutocomplete(data){
		this.data = data;
		var tags = [];

		for (let i = 0; i < this.data.length; i++){
			tags.push(data[i].code);
			tags.push(data[i].name);
		}
		$("#setSearch").autocomplete({source: tags});
	}

	getData(type){
		//return;
		$.ajax({
			type: "GET",
			url: "charts.php",
			datatype: "json",
			data: {
				type: type
			},
			success: function(data){
				charter.addAutocomplete(JSON.parse(data));
			},
			error: function(){console.log("error");}
		});
	}
	
}


/*
		var chartData = {
				type: 'line',
				data: {
					labels: label,
					datasets: dataSets
				},
				options: {
					legend: {
						display: true,
						labels: {
							fontColor: "white",
							fontSize: 16,
						}
					}, 
					title: {
						display: true,
						text: card + " - " + set,
						fontSize: 24,
						fontColor: "white"
					},
					display: true,
					//responsive: false,
					scales: {
						yAxes: [
						{
							id: "price",
							position: 'left',
							ticks: {
								beginAtZero: true,
								fontColor: "white"
							},
							gridLines: {
								display: false,
								color: ["rgba(255,255,255,1)"]
							},
							scaleLabel: {
								display: true,
								labelString: "Price",
								fontSize: 20,
								fontColor: "white"
							}
						},
						{	
							id: "stock",
							position: 'right',
							ticks: {
								beginAtZero: true,
								fontColor: "white",
								//stepSize: 1
							},
							gridLines: {
								display: false,
								color: ["rgba(255,255,255,1)"]
							},
							scaleLabel: {
								display: true,
								labelString: "Stock",
								fontSize: 20,
								fontColor: "white"
							}
						},
						],
						xAxes: [{
							ticks: {
								beginAtZero: true,
								fontColor: "white"
							},
							gridLines: {
								color: ["rgba(255,255,255,1)"]
							},
						}],
					}
				}
		}
		*/