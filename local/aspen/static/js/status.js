    google.load('visualization', '1', {packages: ['table']});

    function parse_time_status(ts) {
       if(ts == 0){
         return "";
       }
       var d = new Date( ts * 1000 );
       var year  = d.getFullYear();
       var month = d.getMonth() + 1;
       month = ( month   < 10 ) ? '0' + month   : month;
       var day  = ( d.getDate()    < 10 ) ? '0' + d.getDate()    : d.getDate();
       var hour = ( d.getHours()   < 10 ) ? '0' + d.getHours()   : d.getHours();
       var min  = ( d.getMinutes() < 10 ) ? '0' + d.getMinutes() : d.getMinutes();
       var sec  = ( d.getSeconds() < 10 ) ? '0' + d.getSeconds() : d.getSeconds();
       return year + '/' + month + '/' + day + '/ ' + hour + ':' + min + ':' + sec;
    }


    function drawStatus() {
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
      var obj = jQuery.parseJSON(jsonData);
      if(!obj || !obj.status) return;
      $("#status").text(obj.status);
      $("#duedate").text(parse_time_status(obj.duedate));
      if(obj.timemodified == null){
      	 $("#timemodified").text("Not submitted");
      }
      else{
      	$("#timemodified").text(parse_time_status(obj.timemodified));
      }
    }

    google.setOnLoadCallback(drawStatus);
