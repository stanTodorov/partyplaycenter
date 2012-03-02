$(document).ready(function() {
	"use strict";

	$("form:first input[type=text]:first").focus();

	$("a[data-link-external=true]").attr("target", "_blank");

	$("a[data-warning=true]").on("click", function() {
		return window.confirm("Сигурни ли сте че искате да продължите?");
	});

	$("a, .hint").tooltip({
		showURL: false,
		loadURL: false,
		track: true,
		delay: 100,
		left: 16,
		top: 16,
		showBody: "|"
	});

	$("a.zoom").fancybox({"hideOnContentClick": true});

	$("select.autosubmit").on("change", function() {
		$(this).closest("form").submit();
	});

	$(".datepicker").datepicker();

	$(".hourSpin").spinner({"min": 0, "max": 23});
	$(".minuteSpin").spinner({"min": 0, "max": 59});

	$("#select-all").on("click", function() {
		if ($(this).is(":checked")) {
			$("input[type=checkbox].toSelect").attr("checked", true);
			return;
		}
		$("input[type=checkbox].toSelect").attr("checked", false);
	});

	$("a.setting").on("click", function() {
		var name = $(this).attr("href").replace(/[^a-z0-9\._]+/g, "");
		var value = null;
		var field = $("input[name=\"" + name + "\"]");

		if ($(field).is("[type=checkbox]")) {
			value = $(field).is(":checked");
		} else {
			value = $(field).val();
		}

		jQuery.ajax({
			url: URL + "admin/?page=ajax&action=setting",
			dataType: "json",
			type: "POST",
			data: {"name": name, "value": value},
			success: function(data, status){
				HandleJSON(data, status);
			}
		});

		return false;
	});

	$("#addAlbum").on("change", function() {
		if ($(this).val() == 0) {
			$("#addAlbumName").removeClass("hidden");
			return;
		}

		$("#addAlbumName").addClass("hidden");
	});

	$("#addUploadField").on("click", function() {
		$("#uploadFields input[type=file]:last").parent().after("<div><input type=\"file\" name=\"pictures[]\" /></div>");
		return false;
	});

	$("#getAlbumsByClub").on("change", function() {
		var id = $(this).val();

		jQuery.ajax({
			url: URL + "admin/?page=ajax&action=albums",
			dataType: "json",
			type: "POST",
			data: {"club": id},
			success: function(data, status){
				HandleJSON(data, status);
				$("#addAlbumName").removeClass("hidden");
			}
		});
	});

	$(".album-gallery li").on("click", function(e) {
		if (e.target.tagName.toLowerCase() != 'li') return;
		$(this).parents("ul").find("li").removeClass("active");
		$(this).addClass("active").find("input[type=radio]").attr('checked', true);
	});


	$("span.showThumb").each(function() {
		$.preLoadImages($(this).attr('data-url'));
	});

	$("span.showThumb").on("hover", function(e) {
		var img = $(this).attr('data-url');

		if ($("#thumb-box").length == 0) {
			$("body").append('<div id="thumb-box"></div>');
		}

		$("#thumb-box").css({
			'left': e.pageX+18,
			'top': e.pageY+18,
			'background-image': 'url("'+img+'")'
		});

		$(this).css("cursor", "pointer");
	}).on("mousemove", function(e) {
		$("#thumb-box").css({
			'left': e.pageX+18,
			'top': e.pageY+18
		});
	}).on("mouseout", function() {
		$("#thumb-box").remove();
	});
});
