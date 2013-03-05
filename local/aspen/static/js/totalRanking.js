      google.load('visualization', '1', {packages:['table']});
      google.setOnLoadCallback(drawTotalRanking);
      setInterval(drawTotalRanking, 10000);

      var table;
      var data;

      function drawTotalRanking() {
        data = new google.visualization.DataTable();
        data.addColumn('string', 'Name');
	data.addColumn('number', 'Submission');
        data.addColumn('number', 'Number of correct answers');
        data.addColumn('string', 'Percentage of correct answers');

        var jsonData = $.ajax({
           url: "http://konoha.ubicg.ynu.ac.jp/maspen/webservice/rest/server.php",
           dataType: "json",
           async: false,
           data: {
                wstoken: "2d1a05efd36f0751a6a9fa7c6e3179e7",
                wsfunction: "local_exfunctions_get_total_runking",
                moodlewsrestformat: "json",
                cmid: CMID,
           }
        }).responseText;
        var obj = jQuery.parseJSON(jsonData);
	    var array = [];
	    var i = 0, row = -1;
	    while(i < obj.length){
	    	   var num = array.push([obj[i].username, obj[i].submission, obj[i].num_of_correct, obj[i].per_of_correct + "%"]) - 1;
	    	   if(obj[i].userid == USERID){
	    		     row = num;
	    	   }
	    	   i++;
	    }

        data.addRows(array);
		if(row != -1){
			data.setProperty(row, 0, 'style', 'background-color: yellow;');
			data.setProperty(row, 1, 'style', 'background-color: yellow;');
			data.setProperty(row, 2, 'style', 'background-color: yellow;');
			data.setProperty(row, 3, 'style', 'background-color: yellow;');
		}
	data.sort([{column: 2, desc: true}, {column: 3, desc: true}, {column: 1, desc: true}]);
        table = new google.visualization.Table(document.getElementById('totalRanking'));
        table.draw(data, {showRowNumber: true, allowHtml: true});
      }
