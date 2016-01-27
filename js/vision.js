jQuery( function() {

//	if ( $( "input:radio[name=survival]:checked" ).val() === "1" ) {
//		$( "#dischargedate" ).prevAll( "label" ).html( "Date of hospital discharge&nbsp;:&nbsp;" );
//	} else if ( $( "input:radio[name=survival]:checked" ).val() === "0" ) {
//		$( "#dischargedate" ).prevAll( "label" ).html( "Date of death&nbsp;:&nbsp;" );
//	}
//	
//	$( "input:radio[name=survival]" ).click( function() {
//		if( $( this ).val() === "1" ) {
//			$( "#dischargedate" ).prevAll( "label" ).html( "Date of hospital discharge&nbsp;:&nbsp;" );
//		} else {
//			$( "#dischargedate" ).prevAll( "label" ).html( "Date of death&nbsp;:&nbsp;" );
//		}
//	});

	var toggle = [ "day3stay", "day7stay", "eblrecord", "day30stay" ];
	
	for ( var i = 0; i < toggle.length; i++ ) {
		setupToggle( toggle[i] );
	}
	
	if ( $( "input:checkbox" ) ) {
		$( "input:checkbox" ).change( function(e){
			toggleCB( this, e );
		});
	}
		
	if ( $( ".mandatory" ) ) { // If any mandatory fields ensure form submission checks mandatory fields first
		$( ".mandatory" ).closest( "form" ).submit( function(e){
			return checkMandatory(e);
		});
	}
	
	var dataEnt = new RegExp( /dataentry/ );
	if ( dataEnt.test( window.location ) ) {
		$( "a" ).click( function(e) {
			return checkChange(e);
		});
	}
	
	var page = $( "body" ).attr( "id" );
	
	if ( page == "day30" ) {
        var html = '<div class="control-group"><label class="control-label" for id="buttonNone">Click here to set all answers to "None"</label><div class="controls"><button id="buttonNone">Set to None</button></div></div>';
		var button = $( html );
		button.click( function( e ) {
			e.preventDefault();
			e.stopPropagation();
			$( 'div.hideday30stay input:radio' ).each( function() {
				if ( $( this ).val() === "None" ) {
					$( this ).attr( 'checked', true );
				}
			} );
		});
		$( "label#defheading" ).parents( 'div.hideday30stay' ).append( button );
	}
	
	if ( page == "day7" ) {
        var html = '<div class="control-group"><label class="control-label" for id="buttonNone">Click here to set all answers to "No"</label><div class="controls"><button id="buttonNone">Set to No</button></div></div>';
		var button = $( html );
		button.click( function( e ) {
			e.preventDefault();
			e.stopPropagation();
			$( 'div.hideday7stay input:radio' ).each( function() {
				if ( $( this ).val() === "0" ) {
					$( this ).attr( 'checked', true );
				}
			} );
		});
		$( "input#creatinine" ).parents( 'div.hideday7stay' ).append( button );
	}
	
	if ( page == "day3" ) {
        var html = '<div class="control-group"><label class="control-label" for id="buttonNone">Click here to set all answers to "No"</label><div class="controls"><button id="buttonNone">Set to No</button></div></div>';
		var button = $( html );
		button.click( function( e ) {
			e.preventDefault();
			e.stopPropagation();
			$( 'div.hideday3stay input:radio' ).each( function() {
				if ( $( this ).val() === "0" ) {
					$( this ).attr( 'checked', true );
				}
			} );
		});
		$( "input#creatinine" ).parents( 'div.hideday3stay' ).append( button );
	}
	
	$.getJSON( "jsonhelp.php", "page=" + page, function( data ){
		help( data )
	});
	
	$.getJSON( "jsondb.php", function( data ){ 
		validation( data )
	});
    
    $( '#checklist' ).dataTable( {
        "sDom": "<'row'<'span6'l><'span6'f>r>t<'row'<'span6'i><'span6'p>>",
        "sPaginationType": "bootstrap",
        "aaSorting": [[ 0, "desc" ]],
		"oLanguage": {
			"sLengthMenu": "_MENU_ records per page"
		}
    });
});

$.extend( $.fn.dataTableExt.oStdClasses, {
    "sWrapper": "dataTables_wrapper form-inline"
} );

$.fn.exists = function () {
    return this.length !== 0;
}

var change = 0;

var checkMandatory = function(e) {
	var unfilled = new Array();
	var labels = new Array();
	var dateDefault = [ 'dd', 'mm', 'yyyy', 'yy' ];
	var timeDefault = [ 'hh', 'mm' ];
	$( ".mandatory" ).each( function(){
		switch( $( this ).attr( "class" ).split( " " )[0] ) {
			case "radiogroup":
			case "checkboxgroup":
				var id = $( this ).attr( "id" );
				if ( !$( "#" + id + " input:checked" ).exists() ) {
					var labelText = getLabel( this );
					var labelFor = $( this ).prevAll( "label" ).attr( "for" );
					if ( $.inArray( labelText, unfilled ) === -1 ) {  // Add to the unfilled array if not already present
						unfilled.push( labelText );
						labels.push( labelFor );
					}
				}
				break;
			default:
				if ( $( this ).val() === "" ||   // Empty box
					( $( this ).hasClass( "date" ) && $.inArray( $( this ).val(), dateDefault ) >= 0 ) || // Unfilled default date
					( $( this ).hasClass( "time" ) && $.inArray( $( this ).val(), timeDefault ) >= 0 ) ) { // Unfilled default time
					var labelText = getLabel( this ); // Label associated with input area
					var labelFor = $( this ).prevAll( "label" ).attr( "for" );
					if ( $.inArray( labelText, unfilled ) === -1 ) {  // Add to the unfilled array if not already present
						unfilled.push( labelText );
						labels.push( labelFor );
					}
				}
		}
	});
	if ( unfilled.length > 0 ) {
		var checkSubmit = confirm( "Are you sure you wish to submit with these mandatory fields unfilled?\n" + unfilled.join("\n") + "\n\nClick OK to submit anyway and Cancel to go back and complete the form" );
		if ( !checkSubmit ) {
			for ( var i = 0; i < labels.length; i++ ) {
				$( "label[for=" + labels[i] + "]" ).addClass( "error" );
			}
			e.preventDefault();
		}
	}
}

var validation = function( validate ) {
	$( "#main :input" ).each( function() {
		var page = $( "body" ).attr( "id" );
		var column = $( this ).attr( "id" );
		var type = $( this ).attr( "type" );
//		alert(JSON.stringify(validate[page][column], null, 4));
		if ( type === "text" ) {
			if ( $( this ).hasClass( "number" ) ) {
				var numpattern = new RegExp( /^(\d+\.?\d*)\/(\d+\.?\d*)$/ );
				var minmax = numpattern.exec( validate[page][column]["validation"] );
				var softlimit = numpattern.exec( validate[page][column]["validation2"] );
				var labelText = getLabel( this );
				$( this ).change( function() {
					change = 1;
					if ( parseFloat( softlimit[1] ) > parseFloat( $( this ).val() ) || parseFloat( softlimit[2] ) < parseFloat( $( this ).val() ) ) {
						if ( parseFloat( minmax[1] ) > parseFloat( $( this ).val() ) || parseFloat( minmax[2] ) < parseFloat( $( this ).val() ) ) {
							alert( "Outside accepted range:\nPlease enter a value between " + minmax[1] + " and " + minmax[2] + " for " + labelText + "\n\nThis value cannot be stored on the database, if you are sure it is correct enter it as a reason for incomplete data when you sign the CRF" );						
						} else {
							alert( "Please check for possible transcription error:\nA more usual range for " + labelText + " would be between " + softlimit[1] + " and " + softlimit[2] + "\n\nThis value will be saved when you submit the form" );
						}
						refocus( column );
					}
				});
			} else if ( $( this ).hasClass( "text" ) ) {
				var reverse = false;
				var baseval = validate[page][column]["validation"];
				if ( baseval.charAt(0) === "!" ) {
					reverse = true;
				}
				var centre = new RegExp( /\/(.+)\// );
				re = centre.exec( baseval );
				var pattern = new RegExp( re[1] );
				$( this ).change( function() {
					change = 1;
					if ( reverse && pattern.test( $( this ).val() ) ) {
						alert( "Invalid character entered - please correct your entry" );
						refocus( column );
					} else if ( !reverse && !pattern.test( $( this ).val() ) ) {
						alert( validate[page][column]["error"] );
						refocus( column );
					}
				});
			} else if ( $( this ).hasClass( "time" ) ) {
				var timeReg = new RegExp( /(.+)(hours|minutes)$/ );
				var timeSplit = timeReg.exec( $( this ).attr( "name" ) );
				if( timeSplit[2] === "minutes" ) {
					$( this ).blur( function() {
						if( window[ timeSplit[1] + "change" ] === 1 ) {
							window[ timeSplit[1] + "change" ] = 0;						
							var hour = $( this ).prevAll( "input" ).val();
							var minute = $( this ).val();
							if ( hour < 0 || hour > 23 || minute < 0 || minute > 59 ) {
								alert ( "Please enter a valid 24-hour clock time (midnight should be entered as 00 hours)" );
								refocus( timeSplit[1] );
							}
						}
						}).change( function() {
						change = 1;
						window[ timeSplit[1] + "change" ] = 0;
						var hour = $( this ).prevAll( "input" ).val();
						var minute = $( this ).val();
						if ( hour < 0 || hour > 23 || minute < 0 || minute > 59 ) {
							alert ( "Please enter a valid 24-hour clock time (midnight should be entered as 00 hours)" );
							refocus( timeSplit[1] );
						}
					});
				} else {
					$( this ).change( function() {
						change = 1;
						window[ timeSplit[1] + "change" ] = 1;
					});
				}
			} else if ( $( this ).hasClass( "date" ) ) {
				var dateReg = new RegExp( /(.+)(day|month|year)$/ );
				var dateSplit = dateReg.exec( $( this ).attr( "name" ) );
				if ( typeof column !== "undefined" && typeof validate[page][column]["validation"] !== "undefined" ) {
					var ageReg = new RegExp( /(\d+)\/(\d+)/ );
					var ageSplit = ageReg.exec( validate[page][column]["validation"] );
					window[ dateSplit[1] + "minAge" ] = ageSplit[1];
					window[ dateSplit[1] + "maxAge" ] = ageSplit[2];
					if ( typeof validate[page][column]["validation2"] !== "undefined" ) {
						window[ dateSplit[1] + "val2" ] = validate[page][column]["validation2"];
						window[ dateSplit[1] + "error2" ] = validate[page][column]["error2"];
						
					}
				}
				if( dateSplit[2] === "year" ) {
					$( this ).blur( function() {
						if ( window[ dateSplit[1] + "change" ] === 1 ) {
							window[ dateSplit[1] + "change" ] = 0;
							if( !checkDate( this ) ) {
								alert( "Invalid date entered" );
								refocus( dateSplit[1] );
							} else {
								var today = new Date();
								var msecInYear = 31556926000;
								var year = $( this ).val();
								var month = $( this ).prevAll( "[name$=month]" ).val();
								var day = $( this ).prevAll( "[name$=day]" ).val();
								var dateToCheck = new Date ( year, month-1, day );
								var diff = today - dateToCheck;
								if ( diff < ( window[ dateSplit[1] + "minAge" ] || 0 ) * msecInYear 
									|| diff > ( window[ dateSplit[1] + "maxAge" ] || 250 ) * msecInYear ) {
									alert( "The date needs to be between " + window[ dateSplit[1] + "minAge" ] + " and " + window[ dateSplit[1] + "maxAge" ] + " years ago." );
									refocus( dateSplit[1] );
								} else if ( typeof window[ dateSplit[1] + "val2" ] !== "undefined" ) {
									var testdate = $( "#" + window[ dateSplit[1] + "val2" ] ).val();
									if ( testdate ) {
										var dateTestParts = testdate.split('-');
									var dateTest = new Date ( dateTestParts[0],dateTestParts[1]-1,dateTestParts[2] );
										if ( dateToCheck < dateTest ) {
											alert( window[ dateSplit[1] + "error2" ] );
											refocus( dateSplit[1] );
										}
									}
								}
							}
						}		
					}).change( function() {
						change = 1;
						window[ dateSplit[1] + "change" ] = 0;
						if( !checkDate( this ) ) {
							alert( "Invalid date entered" );
							refocus( dateSplit[1] );
						} else {
							var today = new Date();
							var msecInYear = 31556926000;
							var year = $( this ).val();
							var month = $( this ).prevAll( "[name$=month]" ).val();
							var day = $( this ).prevAll( "[name$=day]" ).val();
							var dateToCheck = new Date ( year, month-1, day );
							var diff = today - dateToCheck;
							if ( diff < ( window[ dateSplit[1] + "minAge" ] || 0 ) * msecInYear 
								|| diff > ( window[ dateSplit[1] + "maxAge" ] || 250 ) * msecInYear ) {
								alert( "The date needs to be between " + window[ dateSplit[1] + "minAge" ] + " and " + window[ dateSplit[1] + "maxAge" ] + " years ago." );
								refocus( dateSplit[1] );
							} else if ( typeof window[ dateSplit[1] + "val2" ] !== "undefined" ) 
							{
								var testdate = $( "#" + window[ dateSplit[1] + "val2" ] ).val();
								if ( testdate ) {
									var dateTestParts = testdate.split('-');
									var dateTest = new Date ( dateTestParts[0],dateTestParts[1]-1,dateTestParts[2] );
									if ( dateToCheck < dateTest ) {
										alert( window[ dateSplit[1] + "error2" ] );
										refocus( dateSplit[1] );
									}
								}
							}
						}					
					});
				} else {					
					$( this ).change( function() {
						change = 1;
						window[ dateSplit[1] + "change" ] = 1;
					});
				}
			}	
		} else if ( $( this ).hasClass( "textarea" ) ) {
			var reverse = 0;
			var baseval = validate[page][column]["validation"];
			if ( baseval.charAt(0) === "!" ) {
				reverse = 1;
			}
			var centre = new RegExp( /\/(.+)\// );
			re = centre.exec( baseval );
			var pattern = new RegExp( re[1] );
			$( this ).change( function() {
				change = 1;
				if ( reverse && pattern.test( $( this ).val() ) ) {
					alert( "Invalid character entered - please correct your entry" );
					refocus( column );
				} else if ( !reverse && !pattern.test( $( this ).val() ) ) {
					alert( validate[page][column]["error"] );
					refocus( column );
				}
			});
		} else {
			$( this ).change( function() {
				change = 1;
			});
		}
	});
}

var setupToggle = function ( toggle ) {
	if ( $( "input:radio[name=" + toggle + "]:checked" ).val() === "1" ) {
		$( "." + toggle ).addClass( "mandatory" );
		$( ".hide" + toggle ).show();
	} else if ( $( "input:radio[name=" + toggle + "]:checked" ).val() === "0" ) {
		$( "." + toggle ).removeClass( "mandatory" );
		$( ".hide" + toggle ).hide();
	}
	
	$( "input:radio[name=" + toggle + "]" ).click( function() {
		if( $( this ).val() === "1" ) {
			$( "." + toggle ).addClass( "mandatory" );
			$( ".hide" + toggle ).show();		
		} else {
			$( "." + toggle ).removeClass( "mandatory" );
			$( ".hide" + toggle ).hide();
		}
	});
};

var help = function ( helpObj ) {
	for( var i = 0; i < helpObj.length; i++ ) {
		var image = $( '<img src="../images/help.gif" height="16" width="16" class="help" help="' + helpObj[i].help + '" />' );
		var image = $( image ).click( function(e){
			clickHelp(e);
		});
		var targetEle = $( "#" + helpObj[i].fieldid );
		if ( targetEle.is( "label" ) ) {
			targetEle.append( image );
		} else {
			targetEle.parents( 'div.control-group' ).children( 'label' ).append( image );
		}
	}
};

var toggleCB = function( that, e ) {
	var id = $( that ).parents( 'div' ).attr( "id" );
	if ( $( that ).val() == "1" ) { 
		$( "#" + id + " input:checkbox[value!='1']").attr( "checked", false );
	} else if ( $( that ).val() === "0" ) {
		$( "#" + id + " input:checkbox[value!='0']").attr( "checked", false );
	} else {
		$( "#" + id + " input:checkbox[value='1']").attr( "checked", false );
		$( "#" + id + " input:checkbox[value='0']").attr( "checked", false );
	};
}

var clickHelp = function(e) {
	var helpText = $( e.target ).attr( "help" );
	helpText = helpText.replace( /\\n/g, '\n');
	alert( helpText );
}

var checkChange = function(e) {
	if( change ) {
		var confirmLeave = confirm( "You have unsaved data on the form, are you sure you want to leave the page without submitting?" );
		if ( !confirmLeave ) {
			e.preventDefault;
			return false;
		}	
	}
}

var getLabel = function( ele ) {
	var labelText = $( ele ).parents( 'div.controls' ).prevAll( "label" ).text(); // Label associated with input area
	if( labelText.indexOf( ':' ) == labelText.length - 2 ) {
		labelText = labelText.slice( 0, -3 );   // Remove the tailing colon if there
	}
	return labelText;
}

var checkDate = function( ele ) {
	var year = $( ele ).val();
	var month = $( ele ).prevAll( "[name$=month]" ).val();
	var day = $( ele ).prevAll( "[name$=day]" ).val();
	return isDate( year, month, day );
}

function isDate(y,m,d) {
	if ( y.length <= 4 ) {
		y = padLeft( y, 0, 4 );
	} else {
		return false;
	}
	if ( m.length <= 2 ) {
		m = padLeft( m, 0, 2 );
	} else {
		return false;
	}
	if ( d.length <= 2 ) {
		d = padLeft( d, 0, 2 );
	} else {
		return false;
	}
	var date = new Date(y,m-1,d);
	var convertedDate =
	""+ padLeft( date.getFullYear(), 0 , 4 ) + padLeft( date.getMonth()+1, 0, 2 ) + padLeft( date.getDate(), 0, 2 );
	var givenDate = "" + y + m + d;
	return ( givenDate == convertedDate);
}

function padLeft(val, ch, num) {
    var re = new RegExp(".{" + num + "}$");
	var pad = "";
    do  {
        pad += ch;
    }while(pad.length < num);
    return re.exec(pad + val)[0];
}

var refocus = function( column ) {
	setTimeout( function() {
		$( "#" + column ).focus();
	}, 10 );
}