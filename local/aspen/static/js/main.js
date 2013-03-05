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
      $("#console").text("\n\n\n");
      var worker = new Worker(PATH + 'static/js/workaround.js');
      document.getElementById("button-stop").style.display = "";

      $('#button-stop').click(function() {
         document.getElementById("button-stop").style.display = "none";
         worker.terminate();
      });

      $.ajax({
         type: "GET",
         url: PATH + "k/k2js.cgi",
         dataType: "text",
         data: encodeURI(myCodeMirror.getValue()),
         success: function(res) { 
            var array = res.split(/\r\n|\r|\n/);
            var i, str = "", error = [], warning = [];
            var text = "function p(text){postMessage(text)}\n";
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
                  str += array[i] + "\n";
                  myCodeMirror.setLineClass(obj[3].substring(8)-1, "errorLine");
               }
               else{
                  text += array[i] + "\n";
               }	
            }

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
                  codetext: myCodeMirror.getValue(),
                  error: error.length + warning.length,
                  errortext: JSON.stringify({"error": error, "warning": warning})
               },
               success: function(res) {
                  drawRanking();
               }
            });

            var work = "function(){\n" + text + "\nreturn true;\n}";
            var startTime = new Date();
            worker.postMessage(work.toString());
            worker.onmessage = function(event){
               if(event.data != "uhai42ludkxRdvjmfb"){
                  str += event.data + "\n";
                  $("#console").text(str);
               }
               else{
                  document.getElementById("button-stop").style.display = "none";
                  var endTime = new Date();
                  var msec = endTime - startTime;
                  str += '\n   実行時間: ' + msec + 'ミリ秒';
                  $("#console").text(str);
               }
            }
         }
      });

      sessionStorage.setItem("previousValue", myCodeMirror.getValue());
      prettyPrint();
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
      var result = new Array();
      
      var worker = new Worker(PATH + 'static/js/workaround.js');
      document.getElementById("button-stop").style.display = "";

      $('#button-stop').click(function() {
         document.getElementById("button-stop").style.display = "none";
         worker.terminate();
      });
      
      $.ajax({
         type: "GET",
         url: PATH + "k/k2js.cgi",
         dataType: "text",
         data: encodeURI(myCodeMirror.getValue()),
         success: function(res) { 
            var array = res.split(/\r\n|\r|\n/);
            var i, str = "", error = [], warning = [];
            var text = "function p(text){postMessage(text)}\n";
            for(i = 0; i < array.length; i++){
               if(array[i].substring(0, 4) != " - ("){
                  text += array[i] + "\n";
               }	
            }

            var work = "function(){\n" + text + "\nreturn true;\n}";
            var startTime = new Date();
            worker.postMessage(work.toString());
            worker.onmessage = function(event){
               if(event.data != "uhai42ludkxRdvjmfb"){
                  result.push(event.data);
               }
               else{
                  document.getElementById("button-stop").style.display = "none";
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
                        text: myCodeMirror.getValue(),
                        output: JSON.stringify(result)
                     },
                     success: function(res) {
                        drawStatus();
                        drawRanking();
                        drawTotalRanking();
                        prettyPrint();
                     }
                  });
               }
            }
         }
      });

   });

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
            if(res != '" "'){
               myCodeMirror.setValue(jQuery.parseJSON(res));
            }
         }
      });

      if(USERID != 2){
         $.ajax({
            type: "GET",
            url: ROOTURL + "webservice/rest/server.php",
            dataType: "text",
            data: {
               wstoken: "2d1a05efd36f0751a6a9fa7c6e3179e7",
               wsfunction: "local_exfunctions_init_aspen",
               moodlewsrestformat: "json",
               userid: USERID,
               cmid: CMID,
            },
            success: function(res) {
            }
         });
      }
   })();

});
