google.load("visualization", "1", {packages:["corechart"]});
google.setOnLoadCallback(drawChart);

function drawChart() {
	/* Init */
	var id,
			rows = [],
			output = document.getElementById('ab_chart'),
			data = new google.visualization.DataTable();
	
	/* Leer? */
	if ( !output || !antispambee.created.length ) {
		return;
	}
	
	/* Extrahieren */
	var created = antispambee.created.split(','),
			count = antispambee.count.split(',');
	
	/* Loopen */
	for (id in created) {
		rows[id] = [created[id], parseInt(count[id], 10)];
	}

	data.addColumn('string', 'Date');
	data.addColumn('number', 'Spam');
	data.addRows(rows);

  var chart = new google.visualization.AreaChart(output);
  chart.draw(
  	data,
  	{
  		width: parseInt(jQuery('#ab_chart').parent().width(), 10),
  		height: 120,
  		legend: 'none',
  		pointSize: 6,
  		lineWidth: 3,
  		gridlineColor: '#ececec',
  		colors:['#3399CC'],
  		reverseCategories: true,
  		backgroundColor: 'transparent',
  		vAxis: {
  			baselineColor: 'transparent',
  			textPosition: 'in',
  			textStyle: {
  				color: '#8F8F8F',
  				fontSize: 10
  			}
  		},
  		hAxis: {
  			textStyle: {
  				color: '#3399CC',
  				fontSize: 10
  			}
  		},
  		chartArea: {
  			width: "100%",
  			height: "100%"
  		}
		}
  );
}