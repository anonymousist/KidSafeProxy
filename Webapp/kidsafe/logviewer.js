//Javascript file

var logposition;

// Ready function
$(function() {
		// default 20000 = 20secs
		window.setInterval (refreshLog, 5000);
		logposition = endinitiallog;
});


function refreshLog ()
{
	$.get('getlog.php', 'start='+logposition, processUpdate);
}

function processUpdate (data)
{
	// first line ends with bytes 
	// extract prior to bytes = new file size
	var endfilesize = data.indexOf ('bytes');
	if (endfilesize > 1)
	{
		newlogposition = parseInt(data.substring(0, endfilesize -1));
		
		// only update if newlogposition > logposition)
		if (newlogposition > logposition)
		{
			logposition = newlogposition;
			// extract after bytes - used for adding to log view
			var logstring = data.substring(endfilesize +6);
			$('#log').prepend(logstring);
		}

	}
}




/*** Notes ***/
//$('#log').html

//use .prepend / .append

// AJax $.get(url, data, callback);
// eg. $.get('rateMovie.php','rating=5&user=Bob');

// run regularly using 
//window.setInterval(yourfunction, 10000);


/*eg. 
$.get('thisfile.php', 'parm1=xyx&parm2=xyz', processResponse)

function processResponse(data) {
var newHTML;
newHTML = '<h2>Your vote is counted</h2>';
newHTML += '<p>The average rating for this movie is ';
newHTML += data + '.</p>';
$('#message').html(newHTML);
}*/




