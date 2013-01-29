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
//		if(myCodeMirror.getValue() != sessionStorage.getItem("previousValue") ||
//				$("#result").text() == String.fromCharCode(160)) {
        		var iframedoc;
      			function onLoad() {
        			var iframe = document.getElementById("console-iframe");
        			if (document.all) {
          				iframedoc = iframe.contentWindow.document;
        			} else {
          				iframedoc = iframe.contentDocument;
				}

				iframedoc.body.innerHTML = "";

				iframedoc.writeln("<body></body>");

	                      /*  $.ajax({
        	                        type: "GET",
                	                url: PATH + "k/k2js.cgi",
                        	        dataType: "text",
                                	data: encodeURI(myCodeMirror.getValue()),
                               		success: function(res) {
                                        	console.log(res);
                                        	prettyPrint();
                                	}
                        	});*/

	                        $.ajax({
        	                        type: "GET",
                	                url: PATH + "k/k2jsC.cgi",
                        	        dataType: "text",
                                	data: encodeURI(myCodeMirror.getValue()),
                               		success: function(res) {
						var error = [];
						var warning = [];
						if(res.replace(/(^\s+)|(\s+$)/g, "") == ""){
							iframedoc.writeln("<script>function p(text){document.body.innerHTML += text + '<br>'}</script>");
							iframedoc.writeln("<script src ='" + PATH + "k/k2js.cgi?" + encodeURI(myCodeMirror.getValue()) + "'></script>");
						}
						else{
							var array = res.split(/\r\n|\r|\n/);
							var i;
							for(i = 0; i < array.length; i++){
								if(array[i] != ""){
									var obj = array[i].split(/[()]/);
									if(obj[1] == "error"){
										error.push(obj[4]);
									}
									else if(obj[1] == "warning"){
										warning.push(obj[4]);
									}
								}
							}
							iframedoc.body.innerHTML = "<pre>" + res + "</pre>";
						}

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
								errors: JSON.stringify({"error": error, "warning": warning}),
								text: myCodeMirror.getValue(), 
							},
							success: function(res) {
							}
						});
                                	}
                        	});

			}
      			window.onload = onLoad();
			sessionStorage.setItem("previousValue", myCodeMirror.getValue());
                        prettyPrint();
//		}
	});

	$("#button-submit").click(function() {
		$("#submit-text").text(myCodeMirror.getValue());
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
