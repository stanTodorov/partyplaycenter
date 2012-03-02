$(document).ready(function() {
	"use strict";

	// default forms (Firefox not repsect input default values!)
	$("input[type=checkbox], input[type=radio]").each(function() {
		$(this).attr("checked", $(this).is("[checked]"));
	});

	$("option[selected]").each(function() {
		$(this).parent().val( $(this).val() );
	});

	$("textarea").each(function() {
		$(this).val($(this).text());
	});

	$("a[data-link-external=true]").attr("target", "_blank");

	$("a.zoom").fancybox({"hideOnContentClick": true});

	$("input.datepicker").datepicker();

	$("a.captcha").on("click", function() {
		$(this).find("img").attr("src", URL + "captcha.php?" + Math.random());
		return false;
	});

	$(".gallery-albums .slide ul").each(function() {
		var slide = $(this).parents(".gallery-albums").find(".slide").width();
		var width = 0;

		$(this).find("li").each(function() {
			width += $(this).outerWidth(true);
		});

		if (width == 0) {
			$(this).parents(".gallery-albums").remove();
		} else if (width <= slide) {
			$(this).parents(".gallery-albums").find(".arrows").remove();
		}

		$(this).css("width", width + "px" );
	});

	$(".gallery-albums a.arrows").on("click", function() {
		var gallery = $(this).parents(".gallery-albums");

		if ($(gallery).hasClass("sliding")) return false;

		var next = $(this).hasClass("right") ? true : false;
		var slide = $(gallery).find(".slide").width();
		var left = parseInt($(gallery).find(".slide ul").css("left"), 10);
		var pos = false;

		var totalWidth = 0;
		$(gallery).find(".slide ul li").each(function() {
			totalWidth += $(this).outerWidth(true);
		});

		$(gallery).addClass("sliding");

		if (next) {
			pos = SlideNext(1, totalWidth, slide, left);
		} else {
			pos = SlidePrev(slide, left);
		}

		if (pos === false) {
			$(gallery).removeClass("sliding");
			return false;
		}

		var duration = Math.ceil(1000 - (slide - Math.abs(pos - left)));

		$(gallery).find("ul")
			.stop(true, true)
			.animate({left: pos}, duration, function() {
				$(gallery).removeClass("sliding");
			});

		return false;
	});

	$("#tv .screen").each(function() {
		var e = $(this);
		var count = $(e).find("ul li").length;
		var current = 0;
		var delay = 4000;
		var timer = null;
		var next = 0;
		if (count == 0) return;


		slide();
		timer = window.setInterval(function (){slide()}, delay);

		function slide() {
			if (next == current) {
				next++;
				$(e).find("ul li").eq(current).css({"z-index": 2, "display": "block", "opacity": 1});
				if (next >= count) next = 0;
				return;
			}

			$(e).find("ul li").css({"z-index": 1, "display": "none", "opacity": 0});
			$(e).find("ul li").eq(next).css({"z-index": 1, "display": "block", "opacity": 1});
			$(e).find("ul li").eq(current)
				.stop(true, true)
				.css({"z-index": 2, "display": "block", "opacity": 1})
				.fadeTo(600, 0, function(){
					$(this).css("display", "none");
				});
			current = next;

			next++;
			if (next >= count) next = 0;
		}
	});


	$("form select.toggle").on("change", function() {
		var form = $(this).parents("form");
		if ($(this).is(":visible") == false) return;

		var hide = $(this).attr("id");
		var show = hide + $(this).find("option:selected").val();
		$(form).find("." + hide).hide();
		$(form).find("." + show).show();
		formUsuable();
		return true;
	});


	function formUsuable() {
		$("form select.toggle").each(function() {
			if ($(this).is(":visible") == false) return;

			var form = $(this).parents("form");
			var hide = $(this).attr("id");
			var show = hide + $(this).find("option:selected").val();

			$(form).find("." + hide).hide();
			$(form).find("." + show).show();
		});
	}

	formUsuable();

	$(".showThumb").each(function() {
		$.preLoadImages($(this).attr("data-url"));
	});

	$(".showThumb").on("hover", function(e) {
		var img = $(this).attr("data-url");

		if ($("#thumb-box").length == 0) {
			$("body").append('<div id="thumb-box"></div>');
		}

		$("#thumb-box").css({
			"left": e.pageX+18,
			"top": e.pageY+18,
			"background-image": "url(\""+img+"\")"
		});

		$(this).css("cursor", "pointer");
	}).on("mousemove", function(e) {
		$("#thumb-box").css({
			"left": e.pageX+18,
			"top": e.pageY+18
		});
	}).on("mouseout", function() {
		$("#thumb-box").remove();
	});


	$("a.valueSubAdd").on("click", function() {
		var isAdd = ($(this).attr("data-operation") == "add") ? true : false;
		var field = $(this).parent().find("input[type=text]");
		var value = parseInt($(field).val(), 10);
		var between = $(field).attr("data-value-between");

		if (isNaN(value)) value = 0;

		if (isAdd) {
			$(field).val(++value);
		} else {
			$(field).val(--value);
		}

		value = parseInt($(field).val(), 10);


		if (between !== undefined) {
			between = between.split('|');

			var min = parseInt(between[0], 10);
			var max = parseInt(between[1], 10);

			if (!isNaN(min) && value < min) {
				$(field).val(min);
			}
			else if (!isNaN(max) && value > max) {
				$(field).val(max);
			}
		}

		return false;

	}).on("dblclick", function() {
		return false;
	});

	$("input.isInteger").on("keyup", function() {
		var value = parseInt($(this).val(), 10);

		if (isNaN(value)) value = "";
		$(this).val(value);

		$(this).focus();
	});

	$("#cateringList table td.qty input[type=text]").on("keydown", function(e) {
		var keys = [
			48, 49, 50, 51, 52, 53, 54, 55, 56, 57, // 0-9
			96, 97, 98, 99, 100, 101, 102, 103, 104, 105, // numpad
			8, 46, 110 // del/bs
		];

		for (var i = keys.length; i > 0; i--) {
			if (e.keyCode == keys[i]) return;
		}

		e.preventDefault();
		return false;
	}).on("keyup", function(e) {
		var value = parseInt($(this).val(), 10);

		if (isNaN(value)) value = 0;

		if (value < 0) {
			$(this).val(0);
		} else if (value > 100) {
			$(this).val(100);
		} else {
			$(this).val(value);
		}
	});

	$("#calcReservation").on("click", function() {

		// var data = {};

		// $(this).

		jQuery.ajax({
			url: URL + "?page=ajax&action=CalcReservation",
			dataType: "json",
			type: "POST",
			data: $("#reservationForm").serializeArray(),
			success: function(data, status){
				HandleJSON(data, status);
			}
		});

		return false;
	});
});


function SlidePrev(frame, pos)
{
	frame = parseInt(frame, 10);
	pos = parseInt(pos, 10);

	if (pos == 0) return false;
	else if ((Math.abs(pos) / frame) > 1) return (pos += (frame * 0.90));
	else return 0;
}


function SlideNext(num, numsize, frame, pos)
{
	num = parseInt(num, 10);
	numsize = parseInt(numsize, 10);
	frame = parseInt(frame, 10);
	pos = parseInt(pos, 10);

	if (( frame - (num * numsize)) > 0) {
		return false;
	} else if ( ((num * numsize + pos) / frame) < 2) {
		pos = (num * numsize) - frame;
		pos *= -1.0;
	} else {
		pos -= (frame * 0.90);
	}

	return pos;
}
