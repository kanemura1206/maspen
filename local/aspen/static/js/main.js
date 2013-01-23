$(function() {
	/* editor */
	var myCodeMirror = CodeMirror(function(elt) {
		$("#editor").replaceWith(elt);
	}, {
		value: $("#editor").val(),
		mode: "text/x-konoha",
		lineNumbers: true
	});

	/* button actions */
	$("#button-run").click(function() {
		if(myCodeMirror.getValue() != sessionStorage.getItem("previousValue") ||
				$("#result").text() == String.fromCharCode(160)) {
			$.ajax({
				type: "GET",
				url: PATH + "k/k2js.cgi",
				dataType: "text",
				data: myCodeMirror.getValue(),
				success: function(res) {
					$("#console").text(res);
					prettyPrint();
				}
			});
			sessionStorage.setItem("previousValue", myCodeMirror.getValue());
			$.ajax({
				type: "GET",
				url: ROOTURL + "webservice/rest/server.php",
				dataType: "text",
				data: {
					wstoken: "2d1a05efd36f0751a6a9fa7c6e3179e7",
					wsfunction: "local_exfunctions_set_run_status",
					moodlewsrestformat: "json",
					user: USERID,
					module: ID,
					code: 10 + Math.floor( Math.random() * 60 ),
					error: Math.floor( Math.random() * 10 ),
					text: "hello", 
				},
				success: function(res) {
				}
			});
		}
	});

	$("#button-submit").click(function() {
		$("#modal-submit").modal("show");
                prettyPrint();

	});

	$("#button-submit-yes").click(function() {
		$.ajax({
			type: "GET",
			url: ROOTURL + "webservice/rest/server.php",
			dataType: "text",
			data: {
				wstoken: "2d1a05efd36f0751a6a9fa7c6e3179e7",
				wsfunction: "local_exfunctions_submit_assignment",
				moodlewsrestformat: "json",
				id: ID,
				userid: USERID,
				text: myCodeMirror.getValue()
			},
			success: function(res) {
				prettyPrint();
			}
		});
		document.getElementById("status-iframe").contentWindow.location.reload();
	});

	$("#button-ranking").click(function() {
		$.ajax({
			type: "GET",
			url: ROOTURL + "webservice/rest/server.php", 
			dataType: "text",
			data: {
				wstoken: "2d1a05efd36f0751a6a9fa7c6e3179e7",
				wsfunction: "local_exfunctions_get_run_runking",
				moodlewsrestformat: "json",
				id: ID,
				userid: USERID
			},
			success: function(res) {
				var obj = JSON.parse(res);
				$("#run-1-name").text(obj[0].user);
				$("#run-1-code").text(obj[0].code);
				$("#run-1-error").text(obj[0].error);
				$("#run-1-score").text(obj[0].score);
				$("#run-2-name").text(obj[1].user);
				$("#run-2-code").text(obj[1].code);
				$("#run-2-error").text(obj[1].error);
				$("#run-2-score").text(obj[1].score);
				$("#run-3-name").text(obj[2].user);
				$("#run-3-code").text(obj[2].code);
				$("#run-3-error").text(obj[2].error);
				$("#run-3-score").text(obj[2].score);
				$("#run-4-name").text(obj[3].user);
                                $("#run-4-code").text(obj[3].code);
                                $("#run-4-error").text(obj[3].error);
                                $("#run-4-score").text(obj[3].score);

				$("#modal-ranking").modal("show");
				prettyPrint();
			}
		});

		$.ajax({
			type: "GET",
			url: ROOTURL + "webservice/rest/server.php", 
			dataType: "text",
			data: {
				wstoken: "2d1a05efd36f0751a6a9fa7c6e3179e7",
				wsfunction: "local_exfunctions_get_submit_runking",
				moodlewsrestformat: "json",
				id: ID,
				userid: USERID
			},
			success: function(res) {
				var obj = JSON.parse(res);
				$("#submit-1-name").text(obj[0].username);
				$("#submit-1-time").text(parse_time(obj[0].timemodified));
				$("#submit-2-name").text(obj[1].username);
				$("#submit-2-time").text(parse_time(obj[1].timemodified));
				$("#submit-3-name").text(obj[2].username);
				$("#submit-3-time").text(parse_time(obj[2].timemodified));
				$("#submit-4-name").text(obj[3].username);
				$("#submit-4-time").text(parse_time(obj[3].timemodified));

				$("#modal-ranking").modal("show");
				prettyPrint();
			}
		});
	});

/*	$("#button-graph").click(function() {
		document.getElementById("graph").contentWindow.location.reload();		
		$("#modal-graph").modal("show");
		prettyPrint();
	});*/

	function parse_time(ts) {
		var d = new Date( ts * 1000 );
		var year  = d.getFullYear();
		var month = d.getMonth() + 1;
		month = ( month   < 10 ) ? '0' + month   : month;
		var day  = ( d.getDate()    < 10 ) ? '0' + d.getDate()    : d.getDate();
		var hour = ( d.getHours()   < 10 ) ? '0' + d.getHours()   : d.getHours();
		var min  = ( d.getMinutes() < 10 ) ? '0' + d.getMinutes() : d.getMinutes();
		var sec  = ( d.getSeconds() < 10 ) ? '0' + d.getSeconds() : d.getSeconds();
		return year + '年' + month + '月' + day + '日 ' + hour + '時' + min + '分' + sec + '秒';
	}
});
