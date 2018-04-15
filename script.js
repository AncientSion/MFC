remote = 0;

window.onload = function(){
	charter = new Charter();
	timeout = false;

	$("#cardSearch").focus(function(){
		console.log("ding");
		charter.isValidSetSelected();
	})

	$(".ui").removeClass("disabled");

	if (remote){
		timeout = setTimeout(function(){
			charter.getCardData()
		}, 500);
	}
}

class Entry {
	constructor(time, data){
		this.time = time;
		this.name = data.name;
		this.baseAvail = data.baseAvail || 0;
		this.basePrice = data.basePrice || 0;
		this.foilAvail = data.foilAvail || 0;
		this.foilPrice = data.foilPrice || 0;
	}
}

class Charter {
	
	constructor(){
		this.foilPriceChart;
		this.foilAvailChart;
		this.basePriceChart;
		this.baseAvailChart;
		this.data;
		this.sets = [];
		this.content;
		this.draws = 0;
		this.foilPriceCtx = document.getElementById("foilPriceCanvas").getContext("2d");
		this.foilAvailCtx = document.getElementById("foilAvailCanvas").getContext("2d");
		this.basePriceCtx = document.getElementById("basePriceCanvas").getContext("2d");
		this.baseAvailCtx = document.getElementById("baseAvailCanvas").getContext("2d");

		this.getData(this, "cardList", "addCards");
	};

	isValidSetSelected(){
		var setName = $("#setSearch").val();
		var valid = false;
		var i = 0;
		for (var i = 0; i < this.data.length; i++){
			if (this.data[i].name == setName){
				valid = true; break;
			}
		}

		if (valid){
			var suggest = [];
			for (let j = 0; j < this.data[i].cards.length; j++){
				suggest.push(this.data[i].cards[j].name);
			}
			console.log("init");
			$("#cardSearch").autocomplete({source: suggest});
		}
		else {
			console.log("destroy");
			$("#cardSearch").autocomplete("destroy");
		}
	}

	getCardData(){
		var setName = $("#setSearch").val();
		var cardName =  $("#cardSearch").val();

		if (setName && cardName){
			for (let i = 0; i < this.data.length; i++){
				if (this.data[i].name == setName){
					for (let j = 0; j < this.data[i].cards.length; j++){
						if (this.data[i].cards[j].name == cardName){
							console.log("valid, setName: " + setName + ", cardName: " + cardName);
							this.getPriceData(this, this.data[i].code, this.data[i].cards[j].name, "buildAllChards");
							return;
						}
					}
				}
			}
		}
	}

	getPriceData(ref, set, card, callback){
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
				ref[callback](card, set, JSON.parse(data));
			},
			error: function(){console.log("error");}
		});
	}

	getLabel(points){
		var ret = [];
		for (let i = 0; i < points.length; i++){
			ret.push(points[i].time);
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
			//min = Math.min(min, ret[ret.length-1]);
			//max = Math.max(max, ret[ret.length-1]);
		}

		var returnData = [];

		for (let i = 0; i < sets.length; i++){
			returnData.push([{
				"ref": ref[i],
				"label": labels[i],
				"data": sets[i],
				"fontColor": "white",
				"fontSize": 14,
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
			
			console.log("min: " + min + ", max: " + max);
			ret.push({suggestedMin: Math.floor(min - min*0.05), suggestedMax: Math.ceil(max + max*0.05), fontColor: "white"});
		}
		return ret;
	}

	buildAllChards(card, set, data){
		var link = "https://www.cardmarket.com/en/Magic/Products/Singles/" + $("#setSearch").val() + "/" + card;
			link = encodeURI(link);
			link = "<a target='_blank' href='" + link + "'>"+ card + " - " + set + " (click)</a>";


		//if (data.msg != undefined){
			$("#cardName").html(link);
		//	return;
		//}
		/*
		var foilAvail = this.getfoilAvailData(data);
		var foilPrice = this.getFoilPriceData(data);
		var baseAvail = this.getbaseAvailData(data);
		var basePrice = this.getbasePriceData(data);


		if (foilAvail[0].data.length){this.buildChart(card, set, label, "Available Stock", foilAvail, "foilAvailChart", this.foilAvailCtx);}
		if (foilPrice[0].data.length){this.buildChart(card, set, label, "Low. Price", foilPrice, "foilPriceChart", this.foilPriceCtx);}
		if (baseAvail[0].data.length){this.buildChart(card, set, label, "Available Stock", baseAvail, "baseAvailChart", this.baseAvailCtx);}
		if (basePrice[0].data.length){this.buildChart(card, set, label, "Low. Price", basePrice, "basePriceChart", this.basePriceCtx);}
		*/


		this.undrawOldCharts();
		this.draws++;


		var label = this.getLabel(data);
		var cardData = this.getCardDataSets(data, card, set);
		var tickData = this.getTickData(cardData);

		//$("#cardName").html(card + " - " + set);

		if (this.isValidData(cardData[0][0])){this.buildChart(label, cardData[0], tickData[0]);}
		if (this.isValidData(cardData[1][0])){this.buildChart(label, cardData[1], tickData[1]);}
		if (this.isValidData(cardData[2][0])){this.buildChart(label, cardData[2], tickData[2]);}
		if (this.isValidData(cardData[3][0])){this.buildChart(label, cardData[3], tickData[3]);}		
	}

	isValidData(points){
		for (let i = 0; i < points.data.length; i++){
			if (points.data[i]){return true;}
		} return false;
	}

	undrawOldCharts(){
		if (!this.draws){$(".container").addClass("border"); return;}
		this.foilAvailChart.destroy();
		this.foilPriceChart.destroy();
		this.baseAvailChart.destroy();
		this.basePriceChart.destroy();
	}

	buildChart(label, dataSets, tickData){

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
						display: false,
						text: dataSets[0].title,
						fontSize: 24,
						fontColor: "white"
					},
					display: true,
					//responsive: true,
					scales: {
						yAxes: [
						{
							//id: "price",
							//position: 'left',
							//ticks: {
							//	beginAtZero: true,
							//	fontColor: "white"
							//},
							gridLines: {
								display: false,
								//color: ["rgba(255,255,255,1)"]
							},
							scaleLabel: {
								display: false,
								labelString: dataSets[0].label,
								fontSize: 20,
								fontColor: "white"
							}, 
			                ticks: tickData,
						}/*,
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
						},*/
						],
						xAxes: [{
							ticks: {
								beginAtZero: true,
								fontColor: "white"
							},
							gridLines: {
								display: true,
								color: "rgba(255,255,255,0.5)"
							},
						}],
					}
				}
		}

		this[(dataSets[0].ref) + "Chart"] = new Chart(this[dataSets[0].ref + "Ctx"], chartData);

	}

	addCards(data){
		this.data = data;
		this.sets = [];

		for (let i = 0; i < data.length; i++){
			this.sets.push(data[i].name);
		}
		$("#setSearch").autocomplete({source: this.sets});
	}

	getData(ref, type, callback){
		//return;
		$.ajax({
			type: "GET",
			url: "charts.php",
			datatype: "json",
			data: {
				type: type
			},
			success: function(data){
				ref[callback](JSON.parse(data));
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