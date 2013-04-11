google.load('visualization', '1', {packages:['table']});
google.setOnLoadCallback(drawRanking);
setInterval(drawRanking, 10000);

function parse_time_ranking(ts) {
    if(ts == 0){
        return "Not submitted";
    }
    var hour = ( Math.floor(ts/3600) < 10 ) ? '0' + Math.floor(ts/3600) : Math.floor(ts/3600);
    ts = ts - hour * 3600;
    var min  = ( Math.floor(ts / 60) < 10 ) ? '0' + Math.floor(ts / 60) : Math.floor(ts / 60);
    var sec  = ( ts - min * 60 < 10 ) ? '0' + (ts - min * 60) : (ts - min * 60);
    return hour + ':' + min + ':' + sec;
}

var table;
var data;

function drawRanking() {
    data = new google.visualization.DataTable();
    data.addColumn('string', 'Name');
    data.addColumn('number', 'Lines Of Code');
    data.addColumn('number', 'Error');
    data.addColumn('string', 'Time');
    data.addColumn('number', ' ');

    var obj = moodle.getRanking();
    var array = [];
    var i = 0, row = -1;
    while(i < obj.length){
    var correct;
    if(obj[i].correct == 1){
        correct = '◯';
    }
    else {
        correct = '✕';
    }
    var num = array.push([obj[i].username, obj[i].code, obj[i].error, parse_time_ranking(obj[i].time), {v: obj[i].correct, f: correct}]) - 1;
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
        data.setProperty(row, 4, 'style', 'background-color: yellow;');
    }

    data.sort([{column: 4, desc: true}, {column: 3}, {column: 1, desc: true}, {column: 2}]);
    table = new google.visualization.Table(document.getElementById('ranking'));
    table.draw(data, {showRowNumber: true, allowHtml: true});
}
