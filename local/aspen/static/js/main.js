$(function() {
	/* editor */
	var myCodeMirror = CodeMirror(function(elt) {
		$("#editor").replaceWith(elt);
	}, {
		value: $("#editor").val(),
		mode: "text/x-konoha",
		lineNumbers: true,
		onCursorActivity: function(){
					myCodeMirror.setLineClass(myCodeMirror.getCursor().line, null);
				}

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
	
		            $.ajax({
	        	            type: "GET",
	                        url: PATH + "k/k2js.cgi",
	                        dataType: "text",
	                       	data: encodeURI(myCodeMirror.getValue()),
	                        success: function(res) {
								var array = res.split(/\r\n|\r|\n/);
								var i, text = "", str = "", error = [], warning = [];
								for(i = 0; i < array.length; i++){
									if(array[i].substring(0, 4) == " - ("){
										array[i] = array[i].replace(/js\.......:/g, 'at line ');
										var obj = array[i].split(/[()]/);
										if(obj[1] == "error"){
											error.push("(" + obj[3]  + ")" + obj[4]);
											
										}
										else if(obj[1] == "warning"){
											warning.push("(" + obj[3] + ")" + obj[4]);
										}
										str += array[i] + "<br>";
										myCodeMirror.setLineClass(obj[3].substring(8)-1, "errorLine");
									}
									else{
										text += array[i] + "\n";
									}
								}
								iframedoc.writeln("<script>function p(text){document.body.innerHTML += text + '<br>'}</script>");
								text = "var startTime = new Date();" + text + "var endTime = new Date() ;var msec = endTime - startTime; p('実行時間は' + msec + 'ミリ秒')";
		
								iframedoc.writeln("<script>" + text + "</script>");
								iframedoc.body.innerHTML += str;
		
								var blank = 0;
								array = myCodeMirror.getValue().split(/\r\n|\r|\n/);
								for(i = 0; i < array.length; i++){
									if(array[i].trim() == ""){
										blank++;
									}
								}
		
								$.ajax({
									type: "GET",
									url: ROOTURL + "webservice/rest/server.php",
									dataType: "text",
									data: {
										wstoken: "2d1a05efd36f0751a6a9fa7c6e3179e7",
										wsfunction: "local_exfunctions_set_run_status",
										moodlewsrestformat: "json",
										userid: USERID,
										cmid: CMID,
										code: myCodeMirror.lineCount() - blank,
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

	$("#button-compile").click(function() {
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
	
		        $.ajax({
		        	type: "GET",
	                url: PATH + "k/k2jsC.cgi",
	                dataType: "text",
	                data: encodeURI(myCodeMirror.getValue()),
	                success: function(res) {
						var i;
						console.log("'" + res + "'");
						var array = res.split(/\r\n|\r|\n/);
						for(i = 0; i < array.length; i++){
							if(array[i] != "" && array[i].substring(0, 6) != " - (js"){
								var obj = array[i].split(/[()]/);
								myCodeMirror.setLineClass(obj[3].substring(10)-1, "errorLine");
							}
						}
						res = res.replace(/\r\n|\r|\n/g, "<br>").replace(/js\.......:/g, 'at line ');
						iframedoc.body.innerHTML += res;
	                }
	           });
      		}

      		window.onload = onLoad();
		    sessionStorage.setItem("previousValue", myCodeMirror.getValue());
        	prettyPrint();
	});

	$("#button-submit").click(function() {
	     	var jsonData = $.ajax({
        		url: "http://konoha.ubicg.ynu.ac.jp/maspen/webservice/rest/server.php",
		        dataType: "json",
		        async: false,
		        data: {
                	wstoken: "2d1a05efd36f0751a6a9fa7c6e3179e7",
		            wsfunction: "local_exfunctions_view_assignment",
		            moodlewsrestformat: "json",
	  	            cmid: CMID,
	  	            userid: USERID
	  	        }
	        }).responseText;
	        var obj = jQuery.parseJSON(jsonData);
	        if(Math.round(new Date().getTime() / 1000) < obj.duedate){
	        	$("#submit-text").text(myCodeMirror.getValue());
	        	$("#modal-submit").modal("show");
	        }
	        else{
	        	$("#modal-submit-over").modal("show");
	        }
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
				cmid: CMID,
				userid: USERID,
				text: "<pre>" + myCodeMirror.getValue() + "</pre>"
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
        (function() {
        	$.ajax({
	                type: "GET",
        	        url: ROOTURL + "webservice/rest/server.php",
                	dataType: "text",
	                data: {
        	        	wstoken: "2d1a05efd36f0751a6a9fa7c6e3179e7",
                	        wsfunction: "local_exfunctions_get_head_text",
                        	moodlewsrestformat: "json",
	                        userid: USERID,
        	                cmid: CMID,
                	},
	                success: function(res) {
        	                if(res != null){
					myCodeMirror.setValue(jQuery.parseJSON(res));
	                        }
        	        }
                });
        })();

});
