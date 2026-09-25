
var report_data = new Array();


$.post("/Invoices/json_home_data",
    {
      'homeReport': 'view_raport_released_invoices'
    },
    function(data,status){
		var report_data = jQuery.parseJSON(data);
		//alert("Data: " + report_data);
		
		var canvas = document.getElementById('myChart');

/*		var dataC = {
			 labels: [],
			datasets: [{
			  label: 'Facturat (mii RON)',
			  data: [],
			  backgroundColor: "rgba(0,179,89,0.4)",
			   yAxisID: 'y'
			}, {
			  label: 'EA (MWh)',
			  data: [],
			  backgroundColor: "rgba(255,0,0,0.4)"
			   yAxisID: 'y1'
			}]
		};
*/

		const dataC = {
		  labels: [],
		  datasets: [
			{
			  label: 'Facturat [Mii Lei]',
			  data: [],
			  borderColor: "rgba(0,179,89)",
			  backgroundColor: "rgba(0,179,89,0.4)",
			  fill: false,
			  borderWidth:5,
			  cubicInterpolationMode: 'monotone',
			  yAxisID: 'y',
			},
			{
			  label: 'EA [MWh]',
			  data: [],
			  borderColor: "rgba(255,0,0,0.4)",
			  backgroundColor: "rgba(240,0,0,0.4)",
			  fill: true,
			  cubicInterpolationMode: 'monotone',
			  yAxisID: 'y1',
			},
			{
			  label: 'Pret mediu [Lei]',
			  data: [],
			  borderColor: "rgba(0,0,209)",
			  backgroundColor: "rgba(0,0,209,0.4)",
			  fill: false,
			  borderWidth:5,
			  cubicInterpolationMode: 'monotone',
			  yAxisID: 'y',
			}
		  ]
		};
		
		$.each(report_data, function(i, value) {
					dataC.labels.push(report_data[i].LUNA);
					dataC.datasets[0].data.push(report_data[i].FACTURAT / 1000);
					dataC.datasets[1].data.push(report_data[i].EA);
					dataC.datasets[2].data.push(report_data[i].PRETMEDIU);
				});
				

			const config = {
			  type: 'line',
			  data: dataC,
			  options: {
				responsive: true,
				interaction: {
				  mode: 'index',
				  intersect: false,
				},
				stacked: false,
				plugins: {
				  title: {
					display: true,
					text: 'Facturat'
				  }
				},
				scales: {
				  y: {
					type: 'linear',
						ticks:{color: "rgba(0,179,89,0.4)",
						font: {size: 16, weight:'bold'}
						},
					display: true,
					position: 'left',
				  },
				  y1: {
					type: 'linear',
					display: true,
					position: 'right',
						ticks:{color: "rgba(255,0,0,0.4)",
						font: {size: 16, weight:'bold'}
						},
					// grid line settings
					grid: {
					  drawOnChartArea: false, // only want the grid lines for one axis to show up
					},
				  },
				}
			  },
			};

/*		var option = {
			scales: {
			yAxes:[{
					stacked:true,
				gridLines: {
					display:true,
				  color:"rgba(255,99,132,0.2)"
				}
			}],
			xAxes:[{
					gridLines: {
					display:true
				}
			}]
		  }
		};

		var myBarChart = Chart.Line(canvas,{
			data:dataC,
		  options:option
		});*/
		
		
		new Chart(document.getElementById('fChart'), config);

	});
