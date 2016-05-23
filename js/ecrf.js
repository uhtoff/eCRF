jQuery( function() {

	if ( !Array.prototype.indexOf ) {
		Array.prototype.indexOf = function(obj, start) {
		     for (var i = (start || 0), j = this.length; i < j; i++) {
		         if (this[i] === obj) { return i; }
		     }
		     return -1;
		}
	}
    
    
    
    $( ".checklist input:checkbox.master" ).change( function(e) {
        setChildCB( this );
    });
    
    setupChecklist();
	
    setupToggle(); // add toggle handlers
    
	$( "form.crf input:checkbox" ).change( function(e){ // Bind change event to checkbox inputs
		toggleCB( this, e );
	});
		
	if ( $( ".mandatory" ) ) { // If any mandatory fields ensure form submission checks mandatory fields first
		$( "form" ).not(".nomand").submit( function(e){
			return checkMandatory(e);
		});
	}
	
	var dataEnt = new RegExp( /dataentry/ ); 
	if ( dataEnt.test( window.location ) ) { // If dataentry is in the URL then warn on changed data
		$( "a" ).not(".nocheck").click( function(e) {
			return checkChange(e);
		});
		$( "input, select, textarea" ).filter( function(){
				return !$( this ).closest( 'form' ).hasClass( 'nocheck' ); // To exclude forms from being checked for change and warning on screen change
			}).change( function() { // Add onChange event to input fields to set change = 1 (it's set to 0 on script running)
			change = 1;
			var parentDiv = $( this ).closest( 'div.control-group' );
			if ( parentDiv.hasClass( 'warning' ) ) { // If mandatory error then remove it when something is entered into the box
				parentDiv.removeClass( 'warning' );
				parentDiv.find( "span.help-inline" ).remove();
			}
		});
		if ( page !== 'signpt' && page !== 'core' ) {
			addFlags(); // Add the flags for modifying data
		}
	}
	
    if ( page === 'dlsite' || page === 'dldb' || page === 'newdldb' || page === 'countdb' ) {
        $( '.confirmText' ).hide();
        $( 'input:radio[name=encrypted]' ).change( function () {
            if ( $(this).val() === '1' ) {
                $( '.confirmText' ).show();
            } else {
                $( '.confirmText' ).hide();
            }
        })
    }
	
    $( "input:radio[name=flag-flagType_id]" ).change( function() { // Attach to the change event for the flagType radio buttons
		$( 'textarea[name=flag-flagText]' ).remove(); // Remove any previously formed text boxes
		var selRadio = $( "input:radio[name=flag-flagType_id]:checked" ); // Define the selected radio button
        if ( selRadio.attr( 'textarea' ) == 1 ) { // If it has the textarea attribute
			var textArea = $( "<textarea/>",
				{ name: 'flag-flagText' } );
			selRadio.parent().after( textArea ); // Create and insert a text box
		}
	});
    
    $( "input:radio[name$='survival']" ).click( function() {
        if ( $(this).parent('label').text().trim() === "Dead" ) {
            $('#mortModal').modal();
        }
    });
    
    $( "#mortModal a.btn-primary" ).click( function() {
        $( "label[for$=discdate]" ).text( 'Date of death' );
    });
    
    $( "#mortModal a.btn-cancel" ).click( function() {
        $("input:radio[name$='survival']").attr('checked',false);
        $( "label[for$=discdate]" ).text( 'Date of discharge' );
    });

    $('form[data-async]').on('submit', function(event) { // Async form entry, used for setting flags
        var form = $(this);
        $.ajax({
            type: form.attr('method'),
            url: form.attr('action'),
            data: form.serialize(),
 
            success: function(data, status) {
				try {
					var dataParse = $.parseJSON( data ); // Use the JSON returned
					form.trigger('reset'); // Reset all values on the form
					$( 'textarea[name=flag-flagText]' ).remove(); // Remove the textareas
					form.closest( "div.modal" ).modal('hide'); // Hide the modal
					setupFlag( dataParse.flag.field, true, dataParse.flag ); // Resetup the flag as a positive one
					if ( dataParse.message != '' ) {
						alert( dataParse.message ); // If there is a message then alert it
					}
					if ( dataParse.code == 1 ) { // If user has timed out, code 1 will be sent by processAjax
						window.location.reload( true ); // Refresh page to avoid confusion
					}
				} catch (err) {
					//alert( err.message );
					//alert( data );
					//alert( "The wrong response was received, please try again." );
				}				
            }
        });
 
        event.preventDefault();
    });
    
    $( '.clickable ' ).click( function() {
        $('input', this).prop('checked','checked').each( function() {
                changeSelect(this);
            }
        );
    });
    
    $( 'button.ignoreFlags').click(function() {
        var parentForm = $(this).parents('form');
        $('<input type="hidden" name="ignoreFlag" value="1">').appendTo( parentForm );
        parentForm.submit();
    });

    $( 'button.deleteRecord').click(function() {
        $('#deleteForm').modal();
    });
    
    $( "#deleteForm" ).submit( function(e) {
        var parentForm = $('button.deleteRecord').parents('form');
        $('<input type="hidden" name="deleteRecord" value="1">').appendTo( parentForm );
        $('<input type="hidden" name="deletePassword" >').val($('#deleteForm input[name=passwordConfirm]').val()).appendTo( parentForm );
        $('<input type="hidden" name="deleteReason" >').val($('#deleteForm textarea[name=deleteReason]').val()).appendTo( parentForm );
        parentForm.submit();
        e.preventDefault();
    });
	
	if ( page === "usereg" ) {
        refreshCentre($('#' + page + '-country_id').val());
        $('#' + page + '-centre_id').live("change",( function() {
            centreSelect = $(centreBox + ' option:selected').val();
        }));
        $('#' + page + '-country').change( function( e ){         
            refreshCentre($(this).val());
        });
        $('button.btn-danger').removeClass('hidden').click( function ( e ) {
            var delUser = confirm("Are you sure you want to delete this user?");
            if ( delUser ) {
                $('input[name="deleteUser"]').val('1');
                $(this).closest('form').submit();
            }
        });
        
    }
    if ( page === "sitereg" ) {
        $('button.btn-danger').removeClass('hidden').click( function ( e ) {
            var regUsers = $('input[name="regUsers"]').val();
            if ( regUsers !== '0' ) {
                var confMess = "Are you sure you want to delete this centre, it has " + regUsers + " registered users.  They will be deleted also.";
            } else {
                var confMess = "Are you sure you want to delete this centre?";
            }
            var delUser = confirm(confMess);
            if ( delUser ) {
                $('input[name="deleteCentre"]').val('1');
                $(this).closest('form').submit();
            }
        });
        $('button.btn-warning').removeClass('hidden').click( function ( e ) {
            var buttonText = $(this).text();
            if ( buttonText == 'Unlock site' ) {
                var confMess = "Are you sure you want to unlock this site and allow data entry?";
            } else {
                var confMess = "Are you sure you want to lock this site?";
            }
            var toggle = confirm(confMess);
            if ( toggle ) {
                $('input[name="toggleLock"]').val('1');
                $(this).closest('form').submit();
            }
        });
        $('button.btn-info').click( function( e ) {
            if ( $('input[name="centreSelect"]:checked').val() ) {
                window.location.href="index.php?page=othercrfs&centre=" + $('input[name="centreSelect"]:checked').val();
            }
            e.preventDefault();
        }); 
    }

	if ( page === "emailgroup" ) {
		$.post( "processAjax.php",
			{ page: page,
				request: 'emailList'},
			function( data ) {
				addEmailList(data);
			}, 'json' );
		$('select').change( function( e ) {
			$.post( "processAjax.php",
				{ page: page,
					request: 'emailList',
					country: $('select[name=emailgroup-country]').val(),
					centre: $('select[name=emailgroup-centre]').val(),
					privilege: $('select[name=emailgroup-privilege]').val() },
				function( data ) {
					addEmailList(data);
				}, 'json' );
		});
	}
   
	$.post( "processAjax.php", 
			{ page: page,
			  request: 'getValidation' },
			function( data ){
//                console.log(JSON.stringify(data));
				addValidation ( data );
			}, 'json' );

	if ( page === "register" || page === "forgotpass" ) {
		var countryBox = '#' + page + '-country_id';
		var centreBox = '#' + page + '-centre_id';
		$('#' + page + '-country_id').val('');
		$('#' + page + '-centre_id').prop('disabled',true).val('');
		$('#' + page + '-centre_id').live("change",( function() {
			centreSelect = $(centreBox + ' option:selected').val();
		}));
		$('input[id=' + page + '-country][type=hidden]').each( function( e ){
				refreshCentre($(this).val());
		});
		$('#' + page + '-country').change( function( e ){
			$('#' + page + '-centre_id').val('');
			refreshCentre($(this).val());
		});
	}

    $( "input:radio[name=searchpt-link_id]" ).click( function() { // Click handler on radio Button in worklist
        changeSelect(this);
    });
    // @TODO Update to DTables latest
	//$.fn.dataTable.moment( 'Do MMM YYYY, HH:mm' );

	$( '#searchTable' ).dataTable( {
        "sDom": "<'row'<'span6'l><'span6'f>r>t<'row'<'span6'i><'span6'p>>",
        "sPaginationType": "bootstrap",
        "aoColumns": [ null, null, null, null, null, null, {"bVisible":false, "bSearchable":false} ],
        "aaSorting": [[ 1, "asc" ], [ 6, "desc"]],
		"oLanguage": {
			"sLengthMenu": "_MENU_ records per page"
		}
    } );
    
    $( '#searchTableAll' ).dataTable( {
        "sDom": "<'row'<'span6'l><'span6'f>r>t<'row'<'span6'i><'span6'p>>",
        "sPaginationType": "bootstrap",
        "aoColumns": [ null, null, null, null, null, null, null, {"bVisible":false, "bSearchable":false} ],
        "aaSorting": [[ 1, "asc" ], [ 2, "asc"], [7, "desc"]],
		"oLanguage": {
			"sLengthMenu": "_MENU_ records per page"
		}
    } );
    
    $( '.dataTable' ).dataTable( {
        "sDom": "<'row'<'span6'l><'span6'f>r>t<'row'<'span6'i><'span6'p>>",
	"iDisplayLength": 25,
        "sPaginationType": "bootstrap",
        "oLanguage": {
			"sLengthMenu": "_MENU_ records per page"
		}
    });
});

$.extend( $.fn.dataTableExt.oStdClasses, {
    "sWrapper": "dataTables_wrapper form-inline"
} );

/*

$.fn.exists = function () {
    return this.length !== 0;
}
*/
var change = 0;
var valOnBlur = 0;
var valid;
var message;
var centreSelect;

var page = getUrlVars()["page"]; // Get the page from the query string

var changeSelect = function ( that ) {
    var newVal = $( that ).val();
    var selectEle = $("[class^=action]");
    selectEle.each( function() {
        if ( $(this).hasClass('action-'+newVal) ) {
            $(this).prop('disabled', false);
            if ( $(this).val() == 'No action' ) {
                $(this).val('data');
            }
        } else {
            $(this).prop('disabled', true);
            $(this).val('');
        }
    });
};

function escapeHtml(text) {
  return text
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
}

function isNumber (o) {
  return ! isNaN (o-0) && o !== null && o !== "" && o !== false;
}

function getUrlVars()
{
    var vars = [], hash;
    var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
    for(var i = 0; i < hashes.length; i++)
    {
        hash = hashes[i].split('=');
        vars.push(hash[0]);
        vars[hash[0]] = hash[1];
    }
    return vars;
}

var setupFlag = function( name, flag, data ) { // To close the data item within the function
	var target = $( 'label[for=' + name + '] i' ); // To work in IE7 needs to come off for attrib of label
	var actions = !target.closest( 'form' ).hasClass( 'signed' );
	var parentDiv = $( target ).closest( 'div.control-group' );
	if ( flag ) { // Flag set in database

		if ( !data.id ) {
			data.id = data.flag_id;
		}
		target.off(); // Remove modal
		var title = data.flagType_name; // Get title from data input
		tSplit = title.split('('); // Remove anything after first parenthesis
		title = tSplit[0];
		var content;
		if ( actions ) { // If form unsigned
			if ( data.flagText ) {
				content = data.flagText
				content += "<br />";
			} else {
				content = '';
			}
			content += "Click flag to clear it" // Message to suggest clicking to cancel the flag
		} else {
			content = data.flagText;
		}
		target.addClass( 'red' )
			.popover( { trigger: 'hover',
						title: title,
						content: content,
						html: true } );
		if ( actions ) {
			target.on( 'click', function() { // Add on click to clear the flag from database
					var clear = confirm( "Press OK to clear the flag" );
					if ( clear == true ) {
						$( this ).popover( 'destroy' ); // Ensure popover is cleared or it will prevent a new one being set
						$.post( "processAjax.php", {
								page: page,
								request: 'clearFlag',
								flag_id: data.id
								}, function( data ){
									setupFlag( name, false );
								}, 'json' );					
					}
				});
			parentDiv.find( "input, select, textarea" ).attr( "disabled", "disabled" ).val( '' ).removeAttr( "checked" );
		}
		if ( parentDiv.hasClass( 'warning' ) ) { // If mandatory error then remove it when field is flagged
			parentDiv.removeClass( 'warning' );
			parentDiv.addClass( 'flagged' ); // Add flagged class so it can be reversed
			parentDiv.find( "span.help-inline" ).text( '' );
		}
		parentDiv.find( ".mandatory" )
			.removeClass( "mandatory" )
			.addClass( "flagged" );		
	} else { // Flag not set in database
		target.removeClass( 'red' ); // Remove red class
		if ( actions ) {
			target
				.off()					// Remove popover and click event to clear flag
				.on( 'click', function() {	// Add flag modal to add new flag
					var value = $( this ).attr( 'name' );
					var label = $( this ).parent( 'label' ).clone().children().remove().end().text();
					$( "#flagForm .modal-body input:hidden[name=flag-field]" ).val( value );
					$( "#flagForm .modal-body p span" ).html( label );
					$( '#flagForm' ).modal();
				});
			parentDiv.find( "input, select, textarea" ).removeAttr( "disabled" );
		}
		if ( parentDiv.hasClass( 'flagged' ) ) { // If mandatory error then remove it when field is flagged
			parentDiv.removeClass( 'flagged' );
			parentDiv.addClass( 'warning' ); // Add flagged class so it can be reversed
			parentDiv.find( "span.help-inline" ).text( 'This is a mandatory field' );
		}
		parentDiv.find( ".flagged" )
			.removeClass( "flagged" )
			.addClass( "mandatory" );		
	}
};

var addFlags = function() {
	$.post( "processAjax.php", {
			page: page,
			request: 'getFlags'
			}, function( data ){
				if ( data.message ) {
					alert( data.message );
				}
				$( "div.control-group > label:not(.strong):not(.noflag)" ).each( function() { // Gets the descendent labels without the class strong (not headings)
					var name = $( this ).attr( 'for' );
					var flag = $( '<i class="icon-flag"></i>' ); 
					flag.attr( 'name', name );
					$( this ).append( ' ' ).append( flag ); // Appends flag icon to labels
					if ( data[name] ) {
						setupFlag( name, true, data[name] );
					} else {
						setupFlag( name, false );
					}
				});
			}, 'json' );
}

var getLabel = function( ele ) {
	var labelText = $( ele ).parents( 'div.controls' ).prevAll( "label" ).text(); // Label associated with input area
	return labelText;
}

var checkMandatory = function(e) {
	var labels = new Array();
	var elements = new Array();
	$( ".mandatory" ).each( function(){
		var labelText = getLabel( this ); // Label associated with input area
		var parentDiv = $( this ).closest( 'div.control-group' );
		switch( $( this ).attr( "type" ) ) {
			case "radio":
			case "checkbox":
				var controlDiv = $( this ).closest( "div.controls" );
				if ( controlDiv.has( "input:checked" ).length === 0 ) { // Subset of controlDiv with checked boxes will equal 0 when no boxes checked
					if ( $.inArray( labelText, labels ) === -1 ) {  // Add to the unfilled array if not already present
						labels.push( labelText );
						elements.push( parentDiv );
					}
				}
				break;
			default:
				if ( $( this ).val() === "" ) { // Empty box or unselected select with give value of ""					
					if ( $.inArray( labelText, labels ) === -1 ) {  // Add to the unfilled array if not already present
						labels.push( labelText );
						elements.push( parentDiv );
					}
				}
		}
	});
	if ( labels.length > 0 ) {
		var checkSubmit = confirm( "Are you sure you wish to submit with these mandatory fields unfilled?\n" + labels.join("\n") + "\n\nClick OK to submit the form or Cancel to go back and add these data." );
		if ( !checkSubmit ) {
			for ( var i = 0; i < elements.length; i++ ) {
				elements[i].addClass( "warning" );
			}
			e.preventDefault();
		}
	}
}

var refreshCentre = function(countryId) {
    if ( countryId ) {
        $.ajax({
            type: 'POST',
            url: 'processAjax.php',
            data: { page: page, request: 'countryList', country: countryId },
            dataType: 'json',
            success: function(result) {
                if ( result.length > 0 ) {
                    $('#' + page + '-centre_id').prop('disabled',false);
                    $('#' + page + '-centre_id option').each(function() {
                        if ( $(this).val() !== '' && $.inArray( Number($(this).val()),result ) == -1 ) {
                            $(this).filter( ":not(span > option)" ).wrap( "<span>" ).parent().hide();
                        } else {
                            $(this).filter( "span > option" ).unwrap();
                            if ( $(this).val() == centreSelect ) {
                                $(this).prop('selected',true);
                            }
                        }
                     });
                } else {
                    $('#' + page + '-centre_id').prop('disabled',true);
                }
            }
        });
    }
};

var valType = function( e, t ) {
    var testVal = null;
    var pDiv = $( e ).closest( 'div.controls' );
    switch( t ) {
        case 'number':
            var value = parseFloat( pDiv.find( '[name$=\\[number\\]]' ).val() );
            if ( !$.isNumeric( pDiv.find( '[name$=\\[number\\]]' ).val() ) || !isNumber( value ) ) { // Obviously it has to be a number!
				message = 'You must enter a number here (please use \'.\' not \',\' to separate decimals).';
				valid = false;
            } else {
                var unit = parseFloat( pDiv.find( '[name$=\\[unit\\]]' ).val() );
                if ( isNumber( unit ) ) {
                    testVal = value * unit;
                } else {
                    testVal = value;
                }
            }
            break;
        case 'date':
            var date = {
				day: pDiv.find( '[name$=\\[day\\]]' ).val(),
				month: pDiv.find( '[name$=\\[month\\]]' ).val(),
				year: pDiv.find( '[name$=\\[year\\]]' ).val()
				};
			if ( !isDate( date.year, date.month, date.day ) ) {
				message = 'Please enter a valid date in the format dd/mm/yyyy.';
				valid = false;
			} else {
                testVal = new Date(date.year,date.month-1,date.day);
            }
            break;
        case 'time':
			var hour = pDiv.find( '[name$=\\[hour\\]]' ).val();
			var minute = pDiv.find( '[name$=\\[minute\\]]' ).val();
			if ( isNumber( hour ) && isNumber( minute ) ) { 
				if ( hour < 0 || hour > 23 || minute < 0 || minute > 59 ) {
                    valid = false;
                } else {
                    var hour = {
                        hour: hour,
                        minute: minute
                    }
                    testVal = hour;
                }
			} else {
                valid = false;
            }
			if ( valid === false ) { // Just check for a valid time
				message = 'Please enter a valid 24-hour clock time (midnight should be entered as 00 hours)';
			}
			break;
        case 'duration':
            var hours = pDiv.find( '[name$=\\[hours\\]\\[number\\]]' ).val(); // Get hours and minutes
			var minutes = pDiv.find( '[name$=\\[minutes\\]\\[number\\]]' ).val();
            if ( hours === '' ) {
                hours = 0;
            }
            if ( minutes === '' ) {
                minutes = 0;
            }
            if ( isNumber( hours ) && isNumber ( minutes ) ) {
                testVal = hours * 60 + minutes * 1;
            } else {
                message = 'Please enter numbers into both fields.';
                valid = false;
            }
            break;
        case 'password':
            var passPatt = new RegExp( /^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}$/ );
            if ( !passPatt.test($(e).val()) ) {
                message = 'The password must be at least 8 characters and contain upper case, lower case and numbers.';
                valid = false;
            } else {
                testVal = $(e).val();
            }
            break;
        default:
            testVal = $(e).val();
            break;
    }
    return testVal;
};

var doCompare = function( v, l, o ) {
    var result = true;
    if ( typeof(l[0]) !== 'undefined' && l[0] !== null ) {
        switch (o) {
            case 'BETWEEN':
                if ( typeof(l[1]) !== 'undefined' && l[1] !== null &&
                    ( parseFloat(l[0]) > v || v > parseFloat(l[1]) ) )
                        result = false;
                break;
            case 'AFTER':
                if ( l[0] > v )
                    result = false;
                break;
            case 'BEFORE':
                if ( l[0] < v )
                    result = false
                break;
        }
    }
    return result;
};

var valInput = function( e, v ) {
	valid = true;
    message = '';
    var rules = v.rules, group, limits, groupVal, ruleVal, groupType, testGroup, testRule, testVal = null;
    testVal = valType( e, v.type );
    if ( testVal !== null && valid !== false ) {
        // If a value to test has been returned and the input hasn't failed type checking
        var pDiv = $( e ).closest( 'div.controls' );
        var labelText = getLabel( e );
        for ( group in rules ) {
            groupVal = true;
            groupType = rules[group].type;
            testGroup = rules[group];
            for ( key in testGroup ) {
                if ( key === 'type' ) continue;
                testRule = testGroup[key];
                if ( testRule.value === null ) continue;
                ruleVal = true;
                limits = testRule.value.split('/');
                // Make a split array of limits
                switch( v.type ) {
                    case 'number':
                    case 'duration':
                        if ( v.type === 'duration' ) {
                            var unitName = 'minutes';
                            var unitVal = 1;
                        } else {
                            var unit = pDiv.find( '[name$=\\[unit\\]]' ); // Get unit
                            var unitVal = unit.val();
                            if ( unit.attr( 'type' ) == 'hidden' ) {  // Unit will either be hidden or a select - get relevant text
                                var unitName = unit.closest( 'span' ).text();
                            } else {
                                var unitName = unit.children( "option" ).filter( ":selected" ).text();
                            }
                        }
                        switch( testRule.special ) {
                            case 'HARD':                                                      
                                ruleVal = doCompare( testVal, limits, testRule.operator );
                                for ( var i = 0; i < 2; i ++ ) {
                                    limits[i] = Math.round( limits[i] * 10 / unitVal ) / 10;
                                }
                                if ( ruleVal === false ) {
                                    switch ( testRule.operator ) {
                                        case 'BETWEEN':
                                            message = ["Outside accepted range:\nPlease enter a value between ",
                                                limits[0], " and ", limits[1], " ", unitName, " for ", labelText,
                                                "\n\nThis value cannot be stored on the database, ",
                                                "if you are sure it is correct add a flag by clicking the icon to the left"].join( '' );
                                            break;
                                        case 'AFTER':
                                            message = ["Outside accepted range:\nPlease enter a value greater than ",
                                                limits[0], " ", unitName, " for ", labelText,
                                                "\n\nThis value cannot be stored on the database, ",
                                                "if you are sure it is correct add a flag by clicking the icon to the left"].join( '' );
                                            break;
                                        case 'BEFORE':
                                            message = ["Outside accepted range:\nPlease enter a value less than ",
                                                limits[0], " ", unitName, " for ", labelText,
                                                "\n\nThis value cannot be stored on the database, ",
                                                "if you are sure it is correct add a flag by clicking the icon to the left"].join( '' );
                                            break;
                                    }
                                }
                                break;
                            case 'SOFT':
                                ruleVal = doCompare( testVal, limits, testRule.operator );
                                for ( var i = 0; i < 2; i ++ ) {
                                    limits[i] = Math.round( limits[i] * 10 / unitVal ) / 10;
                                }
                                if ( ruleVal === false ) {
                                    switch ( testRule.operator ) {
                                        case 'BETWEEN':
                                            message = ["Please check for possible transcription error:\nA more usual range for ",
                                                labelText, " would be between ", limits[0], " and ", limits[1], " ", unitName,
                                                "\n\nThis data point will still be saved when you click Submit."].join( '' );
                                            break;
                                        case 'AFTER':
                                            message = ["Please check for possible transcription error:\nA more usual range for ",
                                                labelText, " would be greater than ", limits[0], " ", unitName,
                                                "\n\nThis data point will still be saved when you click Submit."].join( '' );
                                            break;
                                        case 'BEFORE':
                                            message = ["Please check for possible transcription error:\nA more usual range for ",
                                                labelText, " would be less than ", limits[0], " ", unitName,
                                                "\n\nThis data point will still be saved when you click Submit."].join( '' );
                                            break;
                                    }                               
                                    ruleVal = true;
                                }
                                break;
                            case 'REFERENCE':
                                var numLimits = new Array();
                                var arrayLength = limits.length;
                                for (var i = 0; i < arrayLength; i++) {
                                    var pForm = $( e ).closest( 'form' );
                                    var limitLoc = limits[i];
                                    var limitRaw = pForm.find( '[name=' + limitLoc + ']' ).val();
                                    if ( limitRaw ) {
                                        var limit = parseFloat( limitRaw );
                                        numLimits[i] = limit;
                                    } else {
                                        var limitRaw = pForm.find( '[name="' + limitLoc + '\[number\]"]' ).val();
                                        if ( limitRaw !== '' ) {
                                            var limUnit = pForm.find( '[name="' + limitLoc + '\[unit\]"]' ).val();
                                            numLimits[i] = parseFloat( limitRaw * limUnit );
                                        }
                                    }
                                }
                                ruleVal = doCompare( testVal, numLimits, testRule.operator );
                                break;
                            }
                        break;
                    case 'date':
                        testDate = testVal.getTime();
                        var arrayLength = limits.length;
                        var dateLimits = new Array();
                        switch ( testRule.special ) {
                            case 'RELATIVE':
                                for (var i = 0; i < arrayLength; i++) {
                                    var today = new Date();
                                    var limitDate = new Date( today.getFullYear() + parseFloat( limits[i] ), today.getMonth(), today.getDate() );
                                    dateLimits[i] = limitDate.getTime();
                                }
                                ruleVal = doCompare( testDate, dateLimits, testRule.operator );
                                if ( ruleVal === false ) {
                                    switch ( testRule.operator ) {
                                        case 'BETWEEN':
                                            message = ["Outside accepted range:\nPlease enter a date between ",
                                                limits[0], " and ", limits[1], " years ago for ", labelText,
                                                "\n\nThis value cannot be stored on the database, ",
                                                "if you are sure it is correct add a flag by clicking the icon to the left"].join( '' );
                                            break;
                                        case 'AFTER':
                                            message = ["Outside accepted range:\nPlease enter a date after ",
                                                limits[0], " years ago for ", labelText,
                                                "\n\nThis value cannot be stored on the database, ",
                                                "if you are sure it is correct add a flag by clicking the icon to the left"].join( '' );
                                            break;
                                        case 'BEFORE':
                                            message = ["Outside accepted range:\nPlease enter a date before ",
                                                limits[0], " years ago for ", labelText,
                                                "\n\nThis value cannot be stored on the database, ",
                                                "if you are sure it is correct add a flag by clicking the icon to the left"].join( '' );
                                            break;
                                    }
                                }
                                break;
                            case 'ABSOLUTE':
                                for (var i = 0; i < arrayLength; i++) {
                                    var limitDate = parseDate( limits[i] );
                                    dateLimits[i] = limitDate.getTime();
                                }
                                ruleVal = doCompare( testDate, dateLimits, testRule.operator );
                                if ( ruleVal === false ) {
                                    switch ( testRule.operator ) {
                                        case 'BETWEEN':
                                            message = ["Outside accepted range:\nPlease enter a date between ",
                                                limits[0], " and ", limits[1], " for ", labelText,
                                                "\n\nThis value cannot be stored on the database, ",
                                                "if you are sure it is correct add a flag by clicking the icon to the left"].join( '' );
                                            break;
                                        case 'AFTER':
                                            message = ["Outside accepted range:\nPlease enter a date after ",
                                                limits[0], " for ", labelText,
                                                "\n\nThis value cannot be stored on the database, ",
                                                "if you are sure it is correct add a flag by clicking the icon to the left"].join( '' );
                                            break;
                                        case 'BEFORE':
                                            message = ["Outside accepted range:\nPlease enter a date before ",
                                                limits[0], " for ", labelText,
                                                "\n\nThis value cannot be stored on the database, ",
                                                "if you are sure it is correct add a flag by clicking the icon to the left"].join( '' );
                                            break;
                                    }
                                }
                                break;
                            case 'REFERENCE':
                                for (var i = 0; i < arrayLength; i++) {
                                    var pForm = $( e ).closest( 'form' );
                                    var dateLoc = limits[i];
                                    var limitDateRaw = pForm.find( '[name=' + dateLoc + ']' ).val();
                                    if ( limitDateRaw ) {
                                        var limitDate = parseDate( limitDateRaw );
                                        dateLimits[i] = limitDate.getTime();
                                    } else {
                                        var date2 = {
                                            day: pForm.find( '[name$=' + dateLoc + '\\[day\\]]' ).val(),
                                            month: pForm.find( '[name$=' + dateLoc + '\\[month\\]]' ).val(),
                                            year: pForm.find( '[name$=' + dateLoc + '\\[year\\]]' ).val()
                                        };
                                        if ( isDate( date2.year, date2.month, date2.day) ) {
                                            var limitDate = new Date ( date2.year, date2.month-1, date2.day );
                                            dateLimits[i] = limitDate.getTime();
                                        }
                                    }
                                }
                                ruleVal = doCompare( testDate, dateLimits, testRule.operator );
                                break;
                        }
                        break;
                    case 'time':
                        break;
                    case 'text':
                    case 'textarea':
//                        var centre = new RegExp( /\/(.+)\// );
//                        var re = centre.exec( testRule.value );
//                        var testPatt = new RegExp( re[1] );
//                        
//                        switch ( testRule.operator ) {
//                            case 'EQUAL':
//                                if ( !testPatt.test( testVal ) ) {
//                                    ruleVal = false;                                    
//                                }
//                                break;
//                            case 'NOT EQUAL':
//                                if ( testPatt.test( testVal ) ) {
//                                    ruleVal = false;
//                                }
//                                break;
//                        }
                        break;
                }
                // Stuff here to do validation
                if ( ruleVal === false && testRule.message !== null ) {
                    message = testRule.message;
                }
                if ( groupType === 'AND' && ruleVal === false ) {
                    // One false rule to break an AND
                    groupVal = false;
                    break;
                } else if ( groupType === 'OR' && ruleVal === true ) {
                    // One true rule to break an OR
                    groupVal = true;
                    break;
                }
            }
            if ( groupVal === false ) {
                // If any group invalid then stop
                valid = false;
                break;
            }
        }       
    }
    if ( message !== '' ) {
        alert( message );
    }
//    console.log( valid );
    return valid;
};

var leftInput = function ( e ) {
	var dfd = $.Deferred(); // Deferred callback
	setTimeout( function() { // Timeout as otherwise we get the main document
		if ( getLabel( document.activeElement ) != getLabel( e ) ) { // If we've moved to a new input/elsewhere
			dfd.resolve( { left: true } );
		} else {
			dfd.resolve( { left: false } );
		}
	}, 1 );
	return dfd.promise();
}

var addEmailList = function( d ) {
	var emails = d.join('; ');
	$('.emailAddresses').text(emails);
}

var addValidation = function( d ) {
	var valOnBlur = 0;
//    console.debug(d);
	$( "input, select, textarea" ).each( function() {
		var pDiv = $( this ).closest( 'div.control-group' );
		var focusTar = pDiv.find( 'div.controls *[name]' ).first();
		var field = pDiv.children( 'label' ).attr( 'for' );
		$( this ).data( 'oVal', $( this ).val() );
		if ( typeof d[field] !== 'undefined' ) { // If validation rule exists for this field
			var v = d[field]; // Put the validation rule into v
			$( this ).blur( function () {
				var self = this; // So we can call it within callback
				if ( this.value != $( this ).data( 'oVal' ) ) { // If the value of the field has changed
					valOnBlur = 1; // Ensure validation when input moved on
					$( this ).data( 'oVal', $( this ).val() ); // Change the 'old value' data to match current value
				}
				$.when( leftInput( this ) ).done( function( response ) { // Have to use this awkward callback due to need for SetTimeout
					if ( response.left && valOnBlur ) { // If input left and a field changed
						valOnBlur = 0;
						if ( !valInput( self, v ) ) { // If field isn't valid then refocus on the invalid field
							refocus ( focusTar );
                            pDiv.addClass('error');
						} else if ( pDiv.hasClass('error') ) {
                            pDiv.removeClass('error');
                            pDiv.find('span.error').remove();
                        }					
					}
				});
			});
		}	
	});
}

var refocus = function( j ) {
	setTimeout( function() {
		j.focus();
	}, 10 );
}

var setupToggle = function () {
	$( ".toggle_trigger" ).each( function() {
		var toggleName = $( this ).attr( 'name' );
		var split = toggleName.split( '-' );
		var toggle = split[1];
		var toggleEle = $( ".toggle_" + toggle );
        if ( $(this).is("input:radio") ) {
		    var setVal = $( "input:radio[name=" + toggleName + "]:checked" ).val();
        } else {
            var setVal = $( this ).val();
        }
		toggleEle.each( function() {
			if ( $(this).attr('data-toggle') === setVal ) {
				if ( !$(this).hasClass("nomand") ) $(this).addClass( "mandatory" );
				$(this).closest( 'div.control-group' ).prev( 'div.control-group' ).has( 'label.strong' ).show(); 
				$(this).closest( 'div.control-group' ).show();	
			} else {
				$(this).removeClass( "mandatory" ); // Remove mandatory class so not to confuse the script
				$(this).closest( 'div.control-group' ).prev( 'div.control-group' ).has( 'label.strong' ).hide(); // Hide any preceding headings as well
				$(this).closest( 'div.control-group' ).hide(); // Move up to the control group to hide the whole thing
			}				
		});
		// "input:radio[name=" + toggleName + "]"
		$( this ).click( function() { // Click handler on radio Button
			var newVal = $( this ).val();
			toggleEle.each( function() {
				if ( $(this).attr('data-toggle') === newVal ) {
					if ( !$(this).hasClass("nomand") ) $(this).addClass( "mandatory" );
					$(this).closest( 'div.control-group' ).prev( 'div.control-group' ).has( 'label.strong' ).show(); 
					$(this).closest( 'div.control-group' ).show();		
				} else {
					$(this).removeClass( "mandatory" ); // Remove mandatory class so not to confuse the script
					$(this).closest( 'div.control-group' ).prev( 'div.control-group' ).has( 'label.strong' ).hide(); // Hide any preceding headings as well
					$(this).closest( 'div.control-group' ).hide(); // Move up to the control group to hide the whole thing
					$(this).prop('checked', false);
				}
			});
		});


			
		/*
		if ( $( "input:radio[name=" + toggleName + "]:checked" ).val() === "1" ) { // Initial setup
			if ( !toggleEle.hasClass("nomand") ) toggleEle.addClass( "mandatory" );
			toggleEle.closest( 'div.control-group' ).prev( 'div.control-group' ).has( 'label.strong' ).show(); 
			toggleEle.closest( 'div.control-group' ).show();	
		} else if ( $( "input:radio[name=" + toggleName + "]:checked" ).val() === "0" || toggleEle.hasClass("startHidden") ) {
			toggleEle.removeClass( "mandatory" ); // Remove mandatory class so not to confuse the script
			toggleEle.closest( 'div.control-group' ).prev( 'div.control-group' ).has( 'label.strong' ).hide(); // Hide any preceding headings as well
			toggleEle.closest( 'div.control-group' ).hide(); // Move up to the control group to hide the whole thing
            
		}
		
		$( "input:radio[name=" + toggleName + "]" ).click( function() { // Click handler on radio Button
			if( $( this ).val() === "1" ) {
				if ( !toggleEle.hasClass("nomand") ) toggleEle.addClass( "mandatory" );
				toggleEle.closest( 'div.control-group' ).prev( 'div.control-group' ).has( 'label.strong' ).show(); 
				toggleEle.closest( 'div.control-group' ).show();		
			} else {
				toggleEle.removeClass( "mandatory" ); // Remove mandatory class so not to confuse the script
				toggleEle.closest( 'div.control-group' ).prev( 'div.control-group' ).has( 'label.strong' ).hide(); // Hide any preceding headings as well
				toggleEle.closest( 'div.control-group' ).hide(); // Move up to the control group to hide the whole thing
                toggleEle.prop('checked', false);
			}
		});
		*/
	});
};

/*
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
*/
var toggleCB = function( that, e ) { // Function to toggle value of associated check boxes
	var cbox = $( that ).parents( 'div.controls' ).find( 'input:checkbox' ); // Parent will always be a control div, find the checkboxes within
	if ( $( that ).val() == "1" ) { // 1 None of the below
		cbox.each( function() {
			if ( $( this ).val() !== "1" ) {
				$( this ).attr( "checked", false );
			}
		});
	} else if ( $( that ).val() === "0" ) { // 0 is test not done
		cbox.each( function() {
			if ( $( this ).val() !== "0" ) {
				$( this ).attr( "checked", false );
			}
		});
	} else {
		cbox.each( function() {  // If anything else is clicked get rid of 0 and 1
			if ( $( this ).val() === "0" || $( this ).val() === "1" ) {
				$( this ).attr( "checked", false );
			}
		});
	};
}

var setChildCB = function( that ) {
    var classes = $(that).attr('class').split(' ');
    for (var i = 0; i < classes.length; i++) {
      var matches = /^group(.+)/.exec(classes[i]);
      if (matches != null) {
        var CBclass = matches[0];
      }
    }
    var masterCheck = that.checked;
    $( 'input:checkbox.' + CBclass ).each( function() {
       $( this ).attr( "checked", masterCheck ); 
    });
}
/*
var clickHelp = function(e) {
	var helpText = $( e.target ).attr( "help" );
	helpText = helpText.replace( /\\n/g, '\n');
	alert( helpText );
}
*/
var checkChange = function(e) {
	if( change ) {
		var confirmLeave = confirm( "You have unsaved data on the form, are you sure you want to leave the page without submitting?" );
		if ( !confirmLeave ) {
			e.preventDefault;
			return false;
		}	
	}
}

function parseDate(input, format) {
  format = format || 'yyyy-mm-dd'; // default format
  var parts = input.match(/(\d+)/g), 
      i = 0, fmt = {};
  // extract date-part indexes from the format
  format.replace(/(yyyy|dd|mm)/g, function(part) { fmt[part] = i++; });

  return new Date(parts[fmt['yyyy']], parts[fmt['mm']]-1, parts[fmt['dd']]);
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
	"" + padLeft( date.getFullYear(), 0 , 4 ) + padLeft( date.getMonth()+1, 0, 2 ) + padLeft( date.getDate(), 0, 2 );
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

function setupChecklist() {
    $( "i.icon-expand").click( function() {
       $( this ).addClass("icon-collapse").removeClass("icon-expand");
       setupChecklist();
    });
    
    $( "i.icon-collapse").click( function() {
       $( this ).removeClass("icon-collapse").addClass("icon-expand"); 
       setupChecklist();
    });
}
