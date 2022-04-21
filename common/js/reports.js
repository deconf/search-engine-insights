/*-
 * Author: Alin Marcu 
 * Author URI: https://deconf.com 
 * Copyright 2013 Alin Marcu 
 * License: GPLv2 or later 
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

"use strict";

if ( seiwpItemData.mapsApiKey ) {
	google.charts.load( 'current', {
		'mapsApiKey' : seiwpItemData.mapsApiKey,
		'packages' : [ 'corechart', 'table', 'orgchart', 'geochart', 'controls' ]
	} );
} else {
	google.charts.load( 'current', {
		'packages' : [ 'corechart', 'table', 'orgchart', 'geochart', 'controls' ]
	} );
}

google.charts.setOnLoadCallback( SEIWPReportLoad );

// Get the numeric ID
seiwpItemData.getID = function ( item ) {
	if ( seiwpItemData.scope == 'admin-item' ) {
		if ( typeof item.id == "undefined" ) {
			return 0
		}
		if ( item.id.split( '-' )[ 1 ] == "undefined" ) {
			return 0;
		} else {
			return item.id.split( '-' )[ 1 ];
		}
	} else {
		if ( typeof item.id == "undefined" ) {
			return 1;
		}
		if ( item.id.split( '-' )[ 4 ] == "undefined" ) {
			return 1;
		} else {
			return item.id.split( '-' )[ 4 ];
		}
	}
}

// Get the selector
seiwpItemData.getSelector = function ( scope ) {
	if ( scope == 'admin-item' ) {
		return 'a[id^="seiwp-"]';
	} else {
		return 'li[id^="wp-admin-bar-seiwp"] a';
	}
}

seiwpItemData.responsiveDialog = function () {
	var dialog, wWidth, visible;

	visible = jQuery( ".ui-dialog:visible" );

	// on each visible dialog
	visible.each( function () {
		dialog = jQuery( this ).find( ".ui-dialog-content" ).data( "ui-dialog" );
		// on each fluid dialog
		if ( dialog.options.fluid ) {
			wWidth = jQuery( window ).width();
			// window width vs dialog width
			if ( wWidth < ( parseInt( dialog.options.maxWidth ) + 50 ) ) {
				// don't fill the entire screen
				jQuery( this ).css( "max-width", "90%" );
			} else {
				// maxWidth bug fix
				jQuery( this ).css( "max-width", dialog.options.maxWidth + "px" );
			}
			// change dialog position
			dialog.option( "position", dialog.options.position );
		}
	} );
}

jQuery.fn.extend( {
	seiwpItemReport : function ( itemId ) {
		var postData, tools, template, reports, refresh, init, swmetric, slug = "-" + itemId;

		tools = {
			setCookie : function ( name, value ) {
				var expires, dateItem = new Date();

				if ( seiwpItemData.scope == 'admin-widgets' ) {
					name = "seiwp_wg_" + name;
				} else {
					name = "seiwp_ir_" + name;
				}
				dateItem.setTime( dateItem.getTime() + ( 24 * 60 * 60 * 1000 * 365 ) );
				expires = "expires=" + dateItem.toUTCString();
				document.cookie = name + "=" + value + "; " + expires + "; path=/";
			},
			getCookie : function ( name ) {
				var cookie, cookiesArray, div, i = 0;

				if ( seiwpItemData.scope == 'admin-widgets' ) {
					name = "seiwp_wg_" + name + "=";
				} else {
					name = "seiwp_ir_" + name + "=";
				}
				cookiesArray = document.cookie.split( ';' );
				for ( i = 0; i < cookiesArray.length; i++ ) {
					cookie = cookiesArray[ i ];
					while ( cookie.charAt( 0 ) == ' ' )
						cookie = cookie.substring( 1 );
					if ( cookie.indexOf( name ) == 0 )
						return cookie.substring( name.length, cookie.length );
				}
				return false;
			},
			escape : function ( str ) {
				div = document.createElement( 'div' );
				div.appendChild( document.createTextNode( str ) );
				return div.innerHTML;
			}
		}

		template = {

			addOptions : function ( id, list ) {
				var defaultMetric, defaultDimension, defaultView, output = [];

				if ( !tools.getCookie( 'default_metric' ) || !tools.getCookie( 'default_dimension' ) || !tools.getCookie( 'default_swmetric' ) ) {
					defaultMetric = 'sessions';
					defaultDimension = moment().subtract( 30, 'days' ).format( "YYYY-MM-DD" ) + ' - ' + moment().subtract( 1, 'days' ).format( "YYYY-MM-DD" );
					swmetric = 'impressions';
					tools.setCookie( 'default_metric', defaultMetric );
					tools.setCookie( 'default_dimension', defaultDimension );
					tools.setCookie( 'default_swmetric', swmetric );
				} else {
					defaultMetric = tools.getCookie( 'default_metric' );
					defaultDimension = tools.getCookie( 'default_dimension' );
					defaultView = tools.getCookie( 'default_view' );
					swmetric = tools.getCookie( 'default_swmetric' );
				}

				if ( list == 'submetrics' ) {

					output = '<span id="seiwp-swmetric-impressions" title="' + seiwpItemData.i18n[ 5 ] + '" class="dashicons dashicons-visibility" style="font-size:22px;padding:7px 4px;"></span>';
					output += '<span id="seiwp-swmetric-clicks" title="' + seiwpItemData.i18n[ 6 ] + '" class="dashicons dashicons-external" style="font-size:22px;padding:7px 4px;"></span>';
					output += '<span id="seiwp-swmetric-position" title="' + seiwpItemData.i18n[ 7 ] + '" class="dashicons dashicons-layout" style="font-size:22px;padding:7px 4px;"></span>';
					output += '<span id="seiwp-swmetric-ctr" title="' + seiwpItemData.i18n[ 8 ] + '" class="dashicons dashicons-performance" style="font-size:22px;padding:7px 4px;"></span>';

					jQuery( id ).html( output );

					jQuery( '#seiwp-swmetric-' + swmetric ).css( "color", "#008ec2" );
				} else if ( list == 'range' ) {
					jQuery( id ).val( defaultDimension );
				} else {
					jQuery.each( list, function ( key, value ) {
						if ( key == defaultMetric || key == defaultDimension || key == defaultView ) {
							output.push( '<option value="' + key + '" selected="selected">' + value + '</option>' );
						} else {
							output.push( '<option value="' + key + '">' + value + '</option>' );
						}
					} );
					jQuery( id ).html( output.join( '' ) );
				}
			},

			init : function () {
				var tpl;

				if ( !jQuery( '#seiwp-window' + slug ).length ) {
					return;
				}

				if ( jQuery( '#seiwp-window' + slug ).html().length ) { // add main template once
					return;
				}

				tpl = '<div id="seiwp-container' + slug + '">';

				if ( seiwpItemData.propertyList != false ) {
					tpl += '<select id="seiwp-sel-property' + slug + '"></select>';
				}

				tpl += '<input type="text" id="seiwp-sel-period' + slug + '" name="seiwp-sel-period' + slug + '" size="21"/>';
				tpl += '<select id="seiwp-sel-report' + slug + '"></select>';
				tpl += '<div id="seiwp-sel-metric' + slug + '" style="float:right;display:none;">';
				tpl += '</div>';
				tpl += '<div id="seiwp-progressbar' + slug + '"></div>';
				tpl += '<div id="seiwp-status' + slug + '"></div>';
				tpl += '<div id="seiwp-reports' + slug + '"></div>';
				tpl += '<div style="text-align:right;width:100%;font-size:0.8em;clear:both;margin-right:5px;margin-top:10px;">';
				tpl += seiwpItemData.i18n[ 14 ];
				tpl += ' <a href="https://deconf.com/search-engine-insights/" rel="nofollow" style="text-decoration:none;font-size:1em;color:#0073aa;">Search Engine Insights</a>&nbsp;';
				tpl += '</div>';
				tpl += '</div>',

				jQuery( '#seiwp-window' + slug ).append( tpl );

				template.addOptions( '#seiwp-sel-period' + slug, 'range' );
				template.addOptions( '#seiwp-sel-property' + slug, seiwpItemData.propertyList );
				template.addOptions( '#seiwp-sel-report' + slug, seiwpItemData.reportList );
				template.addOptions( '#seiwp-sel-metric' + slug, 'submetrics' );

			}
		}

		reports = {
			oldViewPort : 0,
			orgChartTableChartData : '',
			tableChartData : '',
			orgChartPieChartsData : '',
			geoChartTableChartData : '',
			areachartSummaryData : '',
			rtRuns : null,
			i18n : null,

			getTitle : function ( scope ) {
				if ( scope == 'admin-item' ) {
					return jQuery( '#seiwp' + slug ).attr( "title" );
				} else {
					return document.getElementsByTagName( "title" )[ 0 ].innerHTML;
				}
			},

			alertMessage : function ( msg ) {
				jQuery( "#seiwp-status" + slug ).css( {
					"margin-top" : "3px",
					"padding-left" : "5px",
					"height" : "auto",
					"color" : "#000",
					"border-left" : "5px solid red"
				} );
				jQuery( "#seiwp-status" + slug ).html( msg );
			},

			areachartSummary : function ( response ) {
				var tpl;
				jQuery( '#seiwp-sel-metric' + slug ).hide();
				tpl = '<div id="seiwp-areachartsummary' + slug + '">';
				tpl += '<div id="seiwp-summary' + slug + '">';
				tpl += '<div class="inside">';
				tpl += '<div class="small-box first"><h3>' + seiwpItemData.i18n[ 5 ] + '</h3><p id="gdimpressions' + slug + '">&nbsp;</p></div>';
				tpl += '<div class="small-box second"><h3>' + seiwpItemData.i18n[ 6 ] + '</h3><p id="gdclicks' + slug + '">&nbsp;</p></div>';
				tpl += '<div class="small-box third"><h3>' + seiwpItemData.i18n[ 7 ] + '</h3><p id="gdposition' + slug + '">&nbsp;</p></div>';
				tpl += '<div class="small-box last"><h3>' + seiwpItemData.i18n[ 8 ] + '</h3><p id="gdctr' + slug + '">&nbsp;</p></div>';
				tpl += '</div>';
				tpl += '<div id="seiwp-areachart' + slug + '"></div>';
				tpl += '</div>';
				tpl += '</div>';

				if ( !jQuery( '#seiwp-areachartsummary' + slug ).length ) {
					jQuery( '#seiwp-reports' + slug ).html( tpl );
				}

				reports.areachartSummaryData = response;
				if ( jQuery.isArray( response ) ) {
					if ( !jQuery.isNumeric( response[ 0 ] ) ) {
						if ( jQuery.isArray( response[ 0 ] ) ) {
							
							if ( postData.query == 'visitBounceRate,summary' ) {
								reports.drawareachart( response[ 0 ], true );
							} else {
								reports.drawareachart( response[ 0 ], false );
							}
						} else {
							reports.throwDebug( response[ 0 ] );
						}
					} else {
						
						reports.throwError( '#seiwp-areachart' + slug, response[ 0 ], "125px" );
					}
					if ( !jQuery.isNumeric( response[ 1 ] ) ) {
						if ( jQuery.isArray( response[ 1 ] ) ) {
							
							reports.drawSummary( response[ 1 ] );
						} else {
							reports.throwDebug( response[ 1 ] );
						}
					} else {
						
						reports.throwError( '#seiwp-summary' + slug, response[ 1 ], "40px" );
					}
				} else {
					reports.throwDebug( response );
				}
				SEIWPNProgress.done();

			},

			orgChartPieCharts : function ( response ) {
				var i = 0;
				var tpl;

				tpl = '<div id="seiwp-orgchartpiecharts' + slug + '">';
				tpl += '<div id="seiwp-orgchart' + slug + '"></div>';
				tpl += '<div class="seiwp-floatwraper">';
				tpl += '<div id="seiwp-piechart-1' + slug + '" class="halfsize floatleft"></div>';
				tpl += '<div id="seiwp-piechart-2' + slug + '" class="halfsize floatright"></div>';
				tpl += '</div>';
				tpl += '<div class="seiwp-floatwraper">';
				tpl += '<div id="seiwp-piechart-3' + slug + '" class="halfsize floatleft"></div>';
				tpl += '<div id="seiwp-piechart-4' + slug + '" class="halfsize floatright"></div>';
				tpl += '</div>';
				tpl += '</div>';

				if ( !jQuery( '#seiwp-orgchartpiecharts' + slug ).length ) {
					jQuery( '#seiwp-reports' + slug ).html( tpl );
				}

				reports.orgChartPieChartsData = response;
				if ( jQuery.isArray( response ) ) {
					if ( !jQuery.isNumeric( response[ 0 ] ) ) {
						if ( jQuery.isArray( response[ 0 ] ) ) {
							
							reports.drawOrgChart( response[ 0 ] );
						} else {
							reports.throwDebug( response[ 0 ] );
						}
					} else {
						
						reports.throwError( '#seiwp-orgchart' + slug, response[ 0 ], "125px" );
					}

					for ( i = 1; i < response.length; i++ ) {
						if ( !jQuery.isNumeric( response[ i ] ) ) {
							if ( jQuery.isArray( response[ i ] ) ) {
								
								reports.drawPieChart( 'piechart-' + i, response[ i ], reports.i18n[ i ] );
							} else {
								reports.throwDebug( response[ i ] );
							}
						} else {
							
							reports.throwError( '#seiwp-piechart-' + i + slug, response[ i ], "80px" );
						}
					}
				} else {
					reports.throwDebug( response );
				}
				SEIWPNProgress.done();
			},

			geoChartTableChart : function ( response ) {
				var tpl;

				tpl = '<div id="seiwp-geocharttablechart' + slug + '">';
				tpl += '<div id="seiwp-geochart' + slug + '"></div>';
				tpl += '<div id="seiwp-dashboard' + slug + '">';
				tpl += '<div id="seiwp-control' + slug + '"></div>';
				tpl += '<div id="seiwp-tablechart' + slug + '"></div>';
				tpl += '</div>';
				tpl += '</div>';
				
				if ( !jQuery( '#seiwp-geocharttablechart' + slug ).length ) {
					jQuery( '#seiwp-reports' + slug ).html( tpl );
				}

				reports.geoChartTableChartData = response;
				if ( jQuery.isArray( response ) ) {
					if ( !jQuery.isNumeric( response[ 0 ] ) ) {
						if ( jQuery.isArray( response[ 0 ] ) ) {
							reports.drawGeoChart( response[ 0 ] );
							reports.drawTableChart( response[ 0 ] );
						} else {
							reports.throwDebug( response[ 0 ] );
						}
					} else {
						
						reports.throwError( '#seiwp-geochart' + slug, response[ 0 ], "125px" );
						reports.throwError( '#seiwp-tablechart' + slug, response[ 0 ], "125px" );
					}
				} else {
					reports.throwDebug( response );
				}
				SEIWPNProgress.done();
			},

			orgChartTableChart : function ( response ) {
				var tpl;

				tpl = '<div id="seiwp-orgcharttablechart' + slug + '">';
				tpl += '<div id="seiwp-orgchart' + slug + '"></div>';
				tpl += '<div id="seiwp-dashboard' + slug + '">';
				tpl += '<div id="seiwp-control' + slug + '"></div>';
				tpl += '<div id="seiwp-tablechart' + slug + '"></div>';
				tpl += '</div>';
				tpl += '</div>';

				if ( !jQuery( '#seiwp-orgcharttablechart' + slug ).length ) {
					jQuery( '#seiwp-reports' + slug ).html( tpl );
				}

				reports.orgChartTableChartData = response
				if ( jQuery.isArray( response ) ) {
					if ( !jQuery.isNumeric( response[ 0 ] ) ) {
						if ( jQuery.isArray( response[ 0 ] ) ) {
							
							reports.drawOrgChart( response[ 0 ] );
						} else {
							reports.throwDebug( response[ 0 ] );
						}
					} else {
						
						reports.throwError( '#seiwp-orgchart' + slug, response[ 0 ], "125px" );
					}

					if ( !jQuery.isNumeric( response[ 1 ] ) ) {
						if ( jQuery.isArray( response[ 1 ] ) ) {
							reports.drawTableChart( response[ 1 ] );
						} else {
							reports.throwDebug( response[ 1 ] );
						}
					} else {
						reports.throwError( '#seiwp-tablechart' + slug, response[ 1 ], "125px" );
					}
				} else {
					reports.throwDebug( response );
				}
				SEIWPNProgress.done();
			},

			tableChart : function ( response ) {
				var tpl;

				tpl = '<div id="seiwp-404tablechart' + slug + '">';
				tpl += '<div id="seiwp-tablechart' + slug + '"></div>';
				tpl += '</div>';

				if ( !jQuery( '#seiwp-404tablechart' + slug ).length ) {
					jQuery( '#seiwp-reports' + slug ).html( tpl );
				}

				reports.tableChartData = response
				if ( jQuery.isArray( response ) ) {
					if ( !jQuery.isNumeric( response[ 0 ] ) ) {
						if ( jQuery.isArray( response[ 0 ] ) ) {
							
							reports.drawTableChart( response[ 0 ] );
						} else {
							reports.throwDebug( response[ 0 ] );
						}
					} else {
						
						reports.throwError( '#seiwp-tablechart' + slug, response[ 0 ], "125px" );
					}
				} else {
					reports.throwDebug( response );
				}
				SEIWPNProgress.done();
			},

			drawTableChart : function ( data ) {
				var chartData, options, chart, ascending, dashboard, control, wrapper;

				if ( swmetric == "position" ) {
					ascending = true;
				} else {
					ascending = false;
				}

				chartData = google.visualization.arrayToDataTable( data );
				options = {
					page : 'enable',
					pageSize : 10,
					width : '100%',
					allowHtml : true,
					sortColumn : 1,
					sortAscending : ascending,
				};
				
				dashboard = new google.visualization.Dashboard(document.getElementById( 'seiwp-dashboard' + slug ));
				
			    control = new google.visualization.ControlWrapper({
			        controlType: 'StringFilter',
			        containerId: 'seiwp-control' + slug,
			        options: {
			            filterColumnIndex: 0, 
			            matchType : 'any',
			            ui : { label : '', cssClass : 'seiwp-dashboard-control' },
			        }
			    });
			    
			    google.visualization.events.addListener(control, 'ready', function () {
			        jQuery('.seiwp-dashboard-control input').prop('placeholder', seiwpItemData.i18n[ 1 ]);
			    });
				
			    wrapper = new google.visualization.ChartWrapper({
			    	  'chartType' : 'Table',
			    	  'containerId' : 'seiwp-tablechart' + slug,
			    	  'options' : options,
		    	});
			    
			    dashboard.bind(control, wrapper);
			    
			    dashboard.draw( chartData );
			    
			    // outputs selection
			    google.visualization.events.addListener(wrapper, 'select', function() {
			    	console.log(wrapper.getDataTable().getValue(wrapper.getChart().getSelection()[0].row, 0));
			    });
			},

			drawOrgChart : function ( data ) {
				var chartData, options, chart;

				chartData = google.visualization.arrayToDataTable( data );
				options = {
					allowCollapse : true,
					allowHtml : true,
					height : '100%',
					nodeClass : 'seiwp-orgchart',
					selectedNodeClass : 'seiwp-orgchart-selected',
				};
				chart = new google.visualization.OrgChart( document.getElementById( 'seiwp-orgchart' + slug ) );

				chart.draw( chartData, options );
			},

			drawPieChart : function ( id, data, title ) {
				var chartData, options, chart;

				chartData = google.visualization.arrayToDataTable( data );
				options = {
					is3D : false,
					tooltipText : 'percentage',
					legend : 'none',
					chartArea : {
						width : '99%',
						height : '80%'
					},
					title : title,
					pieSliceText : 'value',
					colors : seiwpItemData.colorVariations
				};
				chart = new google.visualization.PieChart( document.getElementById( 'seiwp-' + id + slug ) );

				chart.draw( chartData, options );
			},

			drawGeoChart : function ( data ) {
				var chartData, options, chart;

				chartData = google.visualization.arrayToDataTable( data );
				options = {
					chartArea : {
						width : '99%',
						height : '90%'
					},
					colors : [ seiwpItemData.colorVariations[ 5 ], seiwpItemData.colorVariations[ 4 ] ]
				}

				chart = new google.visualization.GeoChart( document.getElementById( 'seiwp-geochart' + slug ) );

				chart.draw( chartData, options );
			},

			drawareachart : function ( data, format ) {
				var chartData, options, chart, formatter;

				chartData = google.visualization.arrayToDataTable( data );

				if ( format ) {
					formatter = new google.visualization.NumberFormat( {
						suffix : '%',
						fractionDigits : 2
					} );

					formatter.format( chartData, 1 );
				}

				options = {
					legend : {
						position : 'none'
					},
					pointSize : 1.2,
					colors : [ seiwpItemData.colorVariations[ 0 ], seiwpItemData.colorVariations[ 4 ] ],
					areaOpacity : 0.9,
					chartArea : {
						width : '99%',
						height : '90%'
					},
					vAxis : {
						textPosition : "in",
						minValue : 0,
						textStyle : {
							auraColor : 'white',
							color : 'black'
						},
					},
					hAxis : {
						textPosition : 'none'
					},
					curveType : 'function',
				};
				chart = new google.visualization.AreaChart( document.getElementById( 'seiwp-areachart' + slug ) );

				chart.draw( chartData, options );
			},

			drawSummary : function ( data ) {
				jQuery( "#gdimpressions" + slug ).html( data[ 0 ] );
				jQuery( "#gdclicks" + slug ).html( data[ 1 ] );
				jQuery( "#gdposition" + slug ).html( data[ 2 ] );
				jQuery( "#gdctr" + slug ).html( data[ 3 ] );
				jQuery( "#gdservererrors" + slug ).html( data[ 5 ] );
				jQuery( "#gdnotfound" + slug ).html( data[ 4 ] );
			},

			throwDebug : function ( response ) {
				jQuery( "#seiwp-status" + slug ).css( {
					"margin-top" : "3px",
					"padding-left" : "5px",
					"height" : "auto",
					"color" : "#000",
					"border-left" : "5px solid red"
				} );
				if ( response == '-24' ) {
					jQuery( "#seiwp-status" + slug ).html( seiwpItemData.i18n[ 15 ] );
				} else {
					jQuery( "#seiwp-reports" + slug ).css( {
						"background-color" : "#F7F7F7",
						"height" : "auto",
						"margin-top" : "10px",
						"padding-top" : "50px",
						"padding-bottom" : "50px",
						"color" : "#000",
						"text-align" : "center"
					} );
					jQuery( "#seiwp-reports" + slug ).html( response );
					jQuery( "#seiwp-reports" + slug ).show();
					jQuery( "#seiwp-status" + slug ).html( seiwpItemData.i18n[ 11 ] );
					console.log( "\n********************* SEIWP Log ********************* \n\n" + response );
					postData = {
						action : 'seiwp_set_error',
						response : response,
						seiwp_security_set_error : seiwpItemData.security,
					}
					jQuery.post( seiwpItemData.ajaxurl, postData );
				}
			},

			throwError : function ( target, response, p ) {
				jQuery( target ).css( {
					"background-color" : "#F7F7F7",
					"height" : "auto",
					"padding-top" : p,
					"padding-bottom" : p,
					"color" : "#000",
					"text-align" : "center"
				} );
				if ( response == -21 ) {
					jQuery( target ).html( '<p><span style="font-size:4em;color:#778899;margin-left:-20px;" class="dashicons dashicons-clock"></span></p><br><p style="font-size:1.1em;color:#778899;">' + seiwpItemData.i18n[ 12 ] + '</p>' );
				} else {
					jQuery( target ).html( seiwpItemData.i18n[ 13 ] + ' (' + response + ')' );
				}
			},

			render : function ( view, period, query ) {
				var projectId, from, to, tpl, focusFlag;

				jQuery( '#seiwp-sel-report' + slug ).show();

				jQuery( '#seiwp-status' + slug ).html( '' );

				if ( period ) {
					from = period.split( " - " )[ 0 ];
					to = period.split( " - " )[ 1 ];
				} else {
					var date = new Date();
					date.setDate( date.getDate() - 30 );
					from = date.toISOString().split( 'T' )[ 0 ]; // "2016-06-08"
					date = new Date();
					to = date.toISOString().split( 'T' )[ 0 ]; // "2016-06-08"
				}

				tools.setCookie( 'default_metric', query );
				if ( period ) {
					tools.setCookie( 'default_dimension', period );
				}

				if ( typeof view !== 'undefined' ) {
					tools.setCookie( 'default_view', view );
					projectId = view;
				} else {
					projectId = false;
				}

				if ( seiwpItemData.scope == 'admin-item' ) {
					postData = {
						action : 'seiwp_backend_item_reports',
						seiwp_security_backend_item_reports : seiwpItemData.security,
						from : from,
						to : to,
						filter : itemId
					}
				} else if ( seiwpItemData.scope == 'front-item' ) {
					postData = {
						action : 'seiwp_frontend_item_reports',
						seiwp_security_frontend_item_reports : seiwpItemData.security,
						from : from,
						to : to,
						filter : seiwpItemData.filter
					}
				} else {
					postData = {
						action : 'seiwp_backend_item_reports',
						seiwp_security_backend_item_reports : seiwpItemData.security,
						projectId : projectId,
						from : from,
						to : to
					}
				}
				if ( jQuery.inArray( query, [ 'pages', 'keywords' ] ) > -1 ) {


					jQuery( '#seiwp-sel-metric' + slug ).show();

					postData.query = 'channelGrouping,' + query;
					postData.metric = swmetric;

					jQuery.post( seiwpItemData.ajaxurl, postData, function ( response ) {
						reports.orgChartTableChart( response );
					} );
				} else if ( query == '404errors' ) {


					jQuery( '#seiwp-sel-metric' + slug ).show();

					postData.query = query;
					postData.metric = swmetric;

					jQuery.post( seiwpItemData.ajaxurl, postData, function ( response ) {
						reports.tableChart( response );
					} );
				} else if ( query == 'siteperformance' || query == 'technologydetails' ) {


					jQuery( '#seiwp-sel-metric' + slug ).show();

					if ( query == 'siteperformance' ) {
						postData.query = 'channelGrouping,medium,visitorType,source,socialNetwork';
						reports.i18n = seiwpItemData.i18n.slice( 0, 5 );
					} else {
						reports.i18n = seiwpItemData.i18n.slice( 15, 20 );
						postData.query = 'deviceCategory,browser,operatingSystem,screenResolution,mobileDeviceBranding';
					}
					postData.metric = swmetric;

					jQuery.post( seiwpItemData.ajaxurl, postData, function ( response ) {
						reports.orgChartPieCharts( response )
					} );

				} else if ( query == 'locations' ) {


					jQuery( '#seiwp-sel-metric' + slug ).show();

					postData.query = query;
					postData.metric = swmetric;

					jQuery.post( seiwpItemData.ajaxurl, postData, function ( response ) {
						reports.geoChartTableChart( response );
					} );

				} else {

					postData.query = query + ',summary';

					jQuery.post( seiwpItemData.ajaxurl, postData, function ( response ) {
						reports.areachartSummary( response );
					} );

				}
			},

			refresh : function () {
				if ( jQuery( '#seiwp-areachartsummary' + slug ).length > 0 && jQuery.isArray( reports.areachartSummaryData ) ) {
					reports.areachartSummary( reports.areachartSummaryData );
				}
				if ( jQuery( '#seiwp-orgchartpiecharts' + slug ).length > 0 && jQuery.isArray( reports.orgChartPieChartsData ) ) {
					reports.orgChartPieCharts( reports.orgChartPieChartsData );
				}
				if ( jQuery( '#seiwp-geocharttablechart' + slug ).length > 0 && jQuery.isArray( reports.geoChartTableChartData ) ) {
					reports.geoChartTableChart( reports.geoChartTableChartData );
				}
				if ( jQuery( '#seiwp-orgcharttablechart' + slug ).length > 0 && jQuery.isArray( reports.orgChartTableChartData ) ) {
					reports.orgChartTableChart( reports.orgChartTableChartData );
				}
				if ( jQuery( '#seiwp-404tablechart' + slug ).length > 0 && jQuery.isArray( reports.tableChartData ) ) {
					reports.tableChart( reports.tableChartData );
				}
			},

			init : function () {

				try {
					SEIWPNProgress.configure( {
						parent : "#seiwp-progressbar" + slug,
						showSpinner : false
					} );
					SEIWPNProgress.start();
				} catch ( e ) {
					reports.alertMessage( seiwpItemData.i18n[ 0 ] );
				}

				reports.render( jQuery( '#seiwp-sel-property' + slug ).val(), jQuery( 'input[name="seiwp-sel-period' + slug + '"]' ).val(), jQuery( '#seiwp-sel-report' + slug ).val() );

				jQuery( window ).resize( function () {
					var diff = jQuery( window ).width() - reports.oldViewPort;
					if ( ( diff < -5 ) || ( diff > 5 ) ) {
						reports.oldViewPort = jQuery( window ).width();
						reports.refresh(); // refresh only on over 5px viewport width changes
					}
				} );
			}
		}

		template.init();

		reports.init();

		jQuery( '#seiwp-sel-property' + slug ).change( function () {
			reports.init();
		} );

		jQuery( function () {
			jQuery( 'input[name="seiwp-sel-period' + slug + '"]' ).daterangepicker( {
				ranges : {
					'Last 7 Days' : [ moment().subtract( 6, 'days' ), moment() ],
					'Last 30 Days' : [ moment().subtract( 29, 'days' ), moment() ],
					'Last 90 Days' : [ moment().subtract( 89, 'days' ), moment() ],
					'This Month' : [ moment().startOf( 'month' ), moment().endOf( 'month' ) ],
					'Last Month' : [ moment().subtract( 1, 'month' ).startOf( 'month' ), moment().subtract( 1, 'month' ).endOf( 'month' ) ]
				},
				minDate : moment().subtract( 16, 'months' ),
				maxDate : moment(),
				autoUpdateInput : true,
				locale : {
					format : 'YYYY-MM-DD'
				}
			} );
		} );

		jQuery( 'input[name="seiwp-sel-period' + slug + '"]' ).change( function () {
			reports.init();
		} );

		jQuery( '#seiwp-sel-report' + slug ).change( function () {
			reports.init();
		} );

		jQuery( '[id^=seiwp-swmetric-]' ).click( function () {
			swmetric = this.id.replace( 'seiwp-swmetric-', '' );
			tools.setCookie( 'default_swmetric', swmetric );
			jQuery( '#seiwp-swmetric-impressions' ).css( "color", "#444" );
			jQuery( '#seiwp-swmetric-position' ).css( "color", "#444" );
			jQuery( '#seiwp-swmetric-clicks' ).css( "color", "#444" );
			jQuery( '#seiwp-swmetric-ctr' ).css( "color", "#444" );
			jQuery( '#' + this.id ).css( "color", "#008ec2" );

			reports.init();
		} );

		if ( seiwpItemData.scope == 'admin-widgets' ) {
			return;
		} else {
			return this.dialog( {
				width : 'auto',
				maxWidth : 510,
				height : 'auto',
				modal : true,
				fluid : true,
				dialogClass : 'seiwp wp-dialog',
				resizable : false,
				title : reports.getTitle( seiwpItemData.scope ),
				position : {
					my : "top",
					at : "top+100",
					of : window
				}
			} );
		}
	}
} );

function SEIWPReportLoad () {
	if ( seiwpItemData.scope == 'admin-widgets' ) {
		jQuery( '#seiwp-window-1' ).seiwpItemReport( 1 );
	} else if ( seiwpItemData.scope == 'front-item' ) {
		jQuery( seiwpItemData.getSelector( seiwpItemData.scope ) ).click( function () {
			if ( !jQuery( "#seiwp-window-1" ).length > 0 ) {
				jQuery( "body" ).append( '<div id="seiwp-window-1"></div>' );
			}
			jQuery( '#seiwp-window-1' ).seiwpItemReport( 1 );
		} );
	} else {
		jQuery( seiwpItemData.getSelector( seiwpItemData.scope ) ).click( function () {
			if ( !jQuery( "#seiwp-window-" + seiwpItemData.getID( this ) ).length > 0 ) {
				jQuery( "body" ).append( '<div id="seiwp-window-' + seiwpItemData.getID( this ) + '"></div>' );
			}
			jQuery( '#seiwp-window-' + seiwpItemData.getID( this ) ).seiwpItemReport( seiwpItemData.getID( this ) );
		} );
	}

	// on window resize
	jQuery( window ).resize( function () {
		seiwpItemData.responsiveDialog();
	} );

	// dialog width larger than viewport
	jQuery( document ).on( "dialogopen", ".ui-dialog", function ( event, ui ) {
		seiwpItemData.responsiveDialog();
	} );
}
