
var moodle = {};

moodle.setRunStatus = function(codelinenum, codetext, errornum, errortext, callback){
    $.ajax({
        type: "GET",
        url: ROOTURL + "webservice/rest/server.php",
        dataType: "text",
        data: {
            wstoken: MOODLE_TOKEN,
            wsfunction: "local_exfunctions_set_run_status",
            moodlewsrestformat: "json",
            userid: USERID,
            cmid: CMID,
            code: codelinenum,
            codetext: codetext,
            error: errornum,
            errortext: errortext,
        },
        success: callback;
    });
}

moodle.viewAssignment(){
    var jsonData = $.ajax({
        url: MOODLE_SERVER_PHP_URL,
        dataType: "json",
        async: false,
        data: {
            wstoken: MOODLE_TOKEN,
            wsfunction: "local_exfunctions_view_assignment",
            moodlewsrestformat: "json",
            cmid: CMID,
            userid: USERID
        }
    }).responseText;
    return jQuery.parseJSON(jsonData);
}

function countBlankLine(str){
    var blank = 0;
    jQuery.each(str.split(/\r\n|\r|\n/), function(i, v){
        if(v.trim() == ""){ blank++; }
    });
    return blank;
}

function processK2JSResult(res){
    var array = res.split(/\r\n|\r|\n/);
    var i, str = "", error = [], warning = [], eline = [];
    var text = "var console = { log: function(text){ postMessage(text); }, };\n";
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
            eline.push(obj[3].substring(8) - 1);
        }
        else{
            text += array[i] + "\n";
        }
    }
    return {src: text, errors: error, warnings: warning, errorLines: eline, };
}

function dispatch(src){
    var work = "function(){\n" + src + "\nreturn true;\n}";
    var startTime = new Date();
    var str = "";
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
        },
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
                var result = processK2JSResult(res);

                jQuery.each(result.errorLines, function(i, v){
                    myCodeMirror.setLineClass(v, "errorLine");
                }

                var blank = countBlankLine(myCodeMirror.getValue());
                var code = myCodeMirror.lineCount() - blank;
                var codetext = myCodeMirror.getValue();
                var error = result.errors.length + result.warnings.length;
                var errortext = JSON.stringify({"error": error, "warning": warning});
                moodle.setRunStatus(code, codetext, error, errortext, function(res) {
                    drawRanking();
                });

                dispatch(result.src);
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
            }
            else {
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
                    iframedoc.body.innerHTML = res;
                }
            });
        }
        window.onload = onLoad();
        sessionStorage.setItem("previousValue", myCodeMirror.getValue());
        prettyPrint();
    });

    $("#button-submit").click(function() {
        var obj = moodle.viewAssignment();
        if(!obj) return;
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
                                wstoken: MOODLE_TOKEN,
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
                    wstoken: MOODLE_TOKEN,
                    wsfunction: "local_exfunctions_get_head_text",
                    moodlewsrestformat: "json",
                    userid: USERID,
                    cmid: CMID,
                },
                success: function(res) {
                    var parsed = jQuery.parseJSON(res);
                    if(parsed instanceof String){
                        myCodeMirror.setValue(parsed);
                    }
                }
        });
    
        if(USERID != 2){
            $.ajax({
                type: "GET",
                url: ROOTURL + "webservice/rest/server.php",
                dataType: "text",
                data: {
                    wstoken: MOODLE_TOKEN,
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

