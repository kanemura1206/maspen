
function countBlankLine(str){
    var blank = 0;
    jQuery.each(str.split(/\r\n|\r|\n/), function(i, v){
        if(v.trim() == ""){ blank++; }
    });
    return blank;
}

var aspen = {}
aspen.k2jsAsync = function(src, callback){
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
        return {src: text, errors: error, warnings: warning, errorLines: eline, result: res};
    }
    $.ajax({
        type: "GET",
        url: PATH + "k/k2js.cgi",
        dataType: "text",
        data: encodeURI(src),
        success: function(res) { callback(processK2JSResult(res)); },
    });
};

function dispatch(worker, src, callback){
    var work = "function(){\n" + src + "\nreturn true;\n}";
    var startTime = new Date();
    worker.onmessage = callback;
    worker.postMessage(work.toString());
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
        $("#button-stop").show();

        $('#button-stop').click(function() {
            $("#button-stop").hide();
            worker.terminate();
        });
        
        var codetext = myCodeMirror.getValue();
        aspen.k2jsAsync(codetext, function(result) {
            jQuery.each(result.errorLines, function(i, v){
                myCodeMirror.setLineClass(v, "errorLine");
            });
            var blank = countBlankLine(myCodeMirror.getValue());
            var code = myCodeMirror.lineCount() - blank;
            var error = result.errors.length + result.warnings.length;
            var errortext = JSON.stringify({"error": result.errors, "warning": result.warning});
            moodle.setRunStatusAsync(code, codetext, error, errortext, function(res) {
                drawRanking();
            });
            
            var str = "";
            var startTime = new Date();
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
            };
            worker.postMessage("function(){\n" + result.src + "\nreturn true;\n}");
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

            aspen.k2jsAsync(myCodeMirror.getValue(), function(result) {
                jQuery.each(result.errorLines, function(i, v){
                    myCodeMirror.setLineClass(v, "errorLine");
                });
                var res = result.replace(/\r\n|\r|\n/g, "<br>").replace(/js\.......:/g, 'at line ');
                iframedoc.body.innerHTML = res;
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
        var output = [];
        
        var worker = new Worker(PATH + 'static/js/workaround.js');
        $("#button-stop").show();
    
        $('#button-stop').click(function() {
            $("#button-stop").hide();
            worker.terminate();
        });
        
        var codetext = myCodeMirror.getValue();
        aspen.k2jsAsync(codetext, function(result) {
            jQuery.each(result.errorLines, function(i, v){
                myCodeMirror.setLineClass(v, "errorLine");
            });
            
            worker.onmessage = function(event){
                if(event.data != "uhai42ludkxRdvjmfb"){
                    output.push(event.data);
                }
                else{
                    $("#button-stop").hide();
                    moodle.submitAssignmentAsync(codetext, output, function(res) {
                        drawStatus();
                        drawRanking();
                        drawTotalRanking();
                        prettyPrint();
                    });
                }
            }
            worker.postMessage("function(){\n" + result.src + "\nreturn true;\n}");
        });
    });
    
    moodle.getHeadTextAsync(function(res) {
        if(res instanceof String){
            myCodeMirror.setValue(res);
        }
    });

    if(USERID != 2){
        moodle.initAsync();
    }
});

