
const charter = new Charter();

$(document).ready(function(){
	if (options.autoHideUI){
		toggleUI();
	}

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
			$(this).hover(
				function(){
					charter.setCode = $(this).closest(".moveTable").find(".setName").html();
					charter.cardName = $(this).find("a").first().html();
				},
				function(){
					charter.setCode = "";
					charter.cardName = "";
				}
			)
			.find("td a").hover(
				function(){
					charter.getCardData(0, charter.setCode, charter.cardName);
				},
				function(){
				}
			)
		})
	})

	$(".hover").hover(
		function(){
			showPic(this);
		},
		function(){
			emptyPic(this);
		}
	)
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
	var rem = (resY - formHeight - chartHeight - 60);
	$(".scrollWrapper").css("max-height", rem).css("height", rem);
}

function showCardText(){
	return;
    $.ajax({
        type: "GET",
        url: "shakers.php",
        datatype: "json",
        data: {
                type: "cardrules",
                setCode: charter.setCode,
                cardName: charter.cardName,
            },
        success: function(data){
            $("#card").html(data);
        },
        error: function(){console.log("error")},
    });
}

function showPic(ele){
            if (charter.loadPics){
                var url = "https://deckbox.org/mtg/";
                    url += $(ele).attr("data-hover");
                    url += "/tooltip";

                img = document.createElement('img');
                img.style.height = $(".mainContainer").height()-10;
                img.src = url;
                img.onload = function(){
                    $("#card").empty().append($("<div>").append($(img)));
              }
            }
            else {
                showCardText();
            }
}

function emptyPic(){
	$("#card").empty();
}