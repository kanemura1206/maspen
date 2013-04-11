var moodle = {};

moodle.callAPI = function(name, type, data, callback, async){
    data = data || {};
    data.wstoken = MOODLE_TOKEN;
    data.wsfunction = "local_exfunctions_" + name;
    data.cmid = CMID;
    data.moodlewsrestformat = "json";
    return $.ajax({
        type: "GET",
        url: ROOTURL + "webservice/rest/server.php",
        dataType: type,
        data: data,
        async: async === false ? false : true,
        success: callback,
    });
}

moodle.setRunStatusAsync = function(codelinenum, codetext, errornum, errortext, callback){
    moodle.callAPI("set_run_status", "text", {
        code: codelinenum,
        codetext: codetext,
        error: errornum,
        errortext: errortext,
        userid: USERID,
    }, callback);
};

moodle.submitAssignmentAsync = function(codetext, result, callback){
    moodle.callAPI("submit_assignment", "text", {
        text: codetext,
        output: JSON.stringify(result),
        userid: USERID,
    }, callback);
};

moodle.viewAssignment = function(){
    var jsonData = moodle.callAPI("view_assignment", "json", { userid: USERID }, null, false).responseText;
    return jQuery.parseJSON(jsonData);
}

moodle.getRanking = function(){
    var jsonData = moodle.callAPI("get_runking", "json", null, null, false).responseText;
    var ret = jQuery.parseJSON(jsonData);
    if(ret instanceof Array) return ret;
    return [];
}

moodle.getTotalRanking = function(){
    var jsonData = moodle.callAPI("get_total_runking", "json", null, null, false).responseText;
    var ret = jQuery.parseJSON(jsonData);
    if(ret instanceof Array) return ret;
    return [];
}

moodle.initAsync = function(){
    moodle.callAPI("init_aspen", "json", { userid: USERID });
};

moodle.getHeadTextAsync = function(callback){
    moodle.callAPI("get_head_text", "json", { userid: USERID }, callback);
}

