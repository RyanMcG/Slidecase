/*
 * Slidecase
 * Author: Ryan McGowan (StudioCT)
 */
 
//Set jQuery to shortcut since the default '$' is incompatible with WordPress
var $j = jQuery;
var displayed = new Array();
var leftpos = -352;
var querying_c = false;
var querying_i = false;


// Exectued on page load
$j(document).ready(function ($) {
	//Prepare next and previous buttons to be clicked on
	$j(".slc .slc-content").hide();
	if($j(".slc").length > 0)
	{
		$(".slc .slc-arrow").fadeIn();
		$(".slc .slc-container .slc-arrow").click(function () 
		{
			slc_select($(this).hasClass("right"), $(this).parents(".slc:eq(0)"));
		});	
		slc_init();
	}
});

function slc_init()		//Sets the default data and positions a few key elements.
{
	
	var len = $j(".slc .slc-content").length; //Len is equal to the number of posts being displayed in the slidecase
	data = {
		ascoffset:		0,
		descoffset: 	0
		};
	if(len == 1)
	{
		data.ascoffset = -1;
	}
	if(len > 2)
	{
		data.descoffset = len - 2;
	}	
	$j(".slc").data(data);

	var pieces = $j(".slc .slc-content-holder .slc-content");
	var middlepiece = pieces.eq(1);
	if(pieces.length < 3)
	{
		middlepiece = pieces.first();
		leftpos = 210;
		$j(".slc .slc-image-container img.attachment-slidecase-thumbnail").css({left: leftpos});
	}
	
	middlepiece.addClass("active").fadeIn();
	$j(".slc-content-holder").css("marginTop", (95- middlepiece.height()/2));
	slc_loadMoreContent(true, function ()
	{
		slc_loadMoreContent(false);
	});
}

function slc_loadMoreContent(next, callback)
{
	var slc = $j(".slc:eq(0)");
	var getimage = true;
	var displayed = new Array();
	data = {
		action: "get_slc_content"
		};
	
	//Makes an array of all the currently displayed pieces of content.
	$j(".slc .slc-content").each(function(index, element)
	{
		displayed[index] = $j(element).attr("id");
	});
	
	data.displayed = displayed;
	
	if(next)
	{
		data.offset = slc.data("descoffset") + 1;
		data.order = "DESC";
	}
	else
	{
		data.offset = slc.data("ascoffset") + 1;
		data.order = "ASC";
	}
	
	querying_c = true;
	querying_i = true;
	
	$j.post(slidecase_stuff.ajaxurl, data, function (response)
	{
		if(response == "-3")
		{
			if(next)
			{
				slc.data("descoffset", data.offset);
			}
			else
			{
				slc.data("ascoffset", data.offset);
			}
		}
		else if(response != "-1" && response != "-2")
		{
			if(next)
			{
				slc.find(".slc-content-holder").append(response);
				slc.data("descoffset", data.offset);
			}
			else
			{
				slc.find(".slc-content-holder").prepend(response);
				slc.data("ascoffset", data.offset);
			}
		}
		querying_c = false;
		if(!querying_i)
		{
			if($j.isFunction(callback))
			{
				callback();
			}
		}
	});
	data.action = "get_slc_image";
	$j.post(slidecase_stuff.ajaxurl, data, function (response)
	{
		if(response != "-1" && response !="-2" && response !="-3")
		{
			//Add the new image (behind the previous one)
			if(next)
			{
				$j(response).appendTo(".slc .slc-image-container");
			}
			else
			{
				leftpos = leftpos - 565;
				slc.children(".slc-image-container").prepend(response);
				slc.find("img.attachment-slidecase-thumbnail").css("left", leftpos+"px");
			}
		}
		querying_i = false;
		if(!querying_c)
		{
			if($j.isFunction(callback))
			{
				callback();
			}
		}
	});
}


//SELECT AND ANIMATE FUNCTIONS

function slc_select(next, slc)
{
	if(slc.find(":animated").length == 0 )
	{
		var active = slc.find(".slc-content.active");
		var nextactive;
		var nextnextactive;
		var zoomtoend = false;
		
		if(next)
		{
			nextactive = active.next();
			nextnextactive = nextactive.next().next();
			if(nextactive.length == 0)
			{
				nextactive = slc.find(".slc-content:first");
				zoomtoend = true;
			}
		}
		else
		{
			nextactive = active.prev();
			nextnextactive = nextactive.prev().prev();
			if(nextactive.length == 0)
			{
				nextactive = slc.find(".slc-content:last");
				zoomtoend = true;
			}
		}

		if(!(zoomtoend && (querying_i || querying_c)))
		{
			slc_animateImages(next, slc, zoomtoend);
			slc_animateContent(active, nextactive);
		}
		
		if(nextnextactive.length == 0 && !(querying_i || querying_c))
		{
			slc_loadMoreContent(next, function ()
			{
				$j(".slc img.attachment-slidecase-thumbnail").animate({left: leftpos}, 500, "easeOutExpo");
			});
		}
		
		
	}
}

function slc_animateImages(next, slc, zoomtoend)
{
	var images = slc.find(".slc-image-container img.attachment-slidecase-thumbnail");
	images.css({left: leftpos});
	var len = images.length;
	var operator;
	if(next)
	{
		leftpos = leftpos - 565;
		if(zoomtoend)
		{
			leftpos =  210;
		}
	}
	else
	{
		leftpos = leftpos + 565;
		if(zoomtoend)
		{
			leftpos = (1-len)*562 + 210;
		}
	}

	images.animate({left: leftpos}, 500, "easeOutExpo");
}

function slc_animateContent(active, nextactive)
{
	if(nextactive.index() != active.index())
	{
		active.removeClass("active");
		var settings = {
			height: active.css("height"),
			width: active.css("width")
		};
		
		var newdimensions = {
			width: nextactive.width(),
			height: nextactive.height()
		};
		
		var newposition = {
			marginTop: (95 - newdimensions.height/2)
			};
			
		active.children().css("visibility", "hidden");
		active.parents(".slc-content-holder").animate(newposition, 300, "swing");
		active.animate(newdimensions, 300, function()
		{
			nextactive.addClass("active");
			nextactive.children().hide();
			nextactive.show();
			nextactive.children().fadeIn(200);
			//Return the old active to the proper values
			active.hide();
			active.children().css("visibility", "");
			active.css({width: '', height: ''});
		});
	}
}