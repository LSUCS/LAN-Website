var serverTable = null;
var lock = false;

$(document).ready(function() {

    //Init table
    serverTable = $("#servers").dataTable( {
        "bJQueryUI": true,
        "sPaginate": false,
        "bAutoWidth": false,
        "bPaginate": false,
        "bLengthChange": false,
        "bInfo": false,
        "sDom": '<>t<>',
        "aaSorting": [[ 0, "desc" ]],
        "aoColumns": [
            { "sTitle": "Game" },
            { "sTitle": "Name" },
			{ "sTitle": "Players" },
            { "sTitle": "Map" },
            { "sTitle": "IP" },
            { "sTitle": "Locked" }
        ] } );
		
    //Row highlighting
    $("tbody tr").live('mouseover', function() {
        $(this).find('td').addClass("row-hover");
    });
    $("tbody tr").live('mouseleave', function() {
        $(this).find('td').removeClass("row-hover");
    });
		
	loadServers();


});

function loadServers() {

    //Lock
    if (lock) return;
    lock = true;
    
    //Get
    $.get(
        "index.php?page=servers&action=loadservers",
        function (data) {
            if (!data) return;
			
			//Clear table
			serverTable.fnClearTable();
			
			//Load servers			
			for (i = 0; i < data.servers.length; i++) {
				var server = data.servers[i];
				var arr = new Array();
				arr[arr.length] = (server.game_icon?'<img class="game-icon" src="' + server.game_icon + '" />' + server.game:server.game);
				arr[arr.length] = server.name;
				arr[arr.length] = (server.max_players?server.num_players + '/' + server.max_players:'-');
				arr[arr.length] = server.map;
				arr[arr.length] = (server.source == 1?'<a href="steam://connect/' + server.hostname + ':' + server.port + '">' + server.hostname + ':' + server.port + '<img class="connect-button" src="images/orangearrow.png" /></a>':server.hostname + ':' + server.port);
				arr[arr.length] = (server.password_protected == 1?'<img class="lock" src="images/locked.png" />':'<img class="lock" src="images/unlocked.png" />');
				serverTable.fnAddData(arr);
			}
			
			//Set next time to load
            setTimeout(function() { loadServers(); }, data.interval * 1000);
	
            //Unset lock
            lock = false;
        },
        'json');

}