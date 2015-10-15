//Javascript file


// Ready function
$(function() {
		// default 20000 = 20secs
		window.setInterval (refreshUsers, 5000);
});


function refreshUsers ()
{
	$.get('getusers.php', '', processUpdate);
}

function processUpdate (data)
{
	$('#activeusers').replaceWith(data)

}







