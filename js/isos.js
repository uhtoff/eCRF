 jQuery( function() {
    if ( page == "postop" ) {
        var html = '<div class="control-group"><label class="control-label noflag" for id="buttonNone">Click here if there were no post-op complications</label><div class="controls"><button id="buttonNone">Set to None</button></div></div>';
		var button = $( html );
		button.click( function( e ) {
			
			$( 'input:radio.complication' ).each( function() {
                if ( $( this ).val() === "0" ) {
                    $( this ).attr( 'checked', true );
                }
                if ( $( this ).parents('label').text().trim() === "None" ) {
                    $( this ).attr( 'checked', true );
                }
			} );
            $( 'input:radio.compres' ).each( function() {
                if ( $( this ).val() === "0" ) {
                    $( this ).attr( 'checked', true );
                }
                if ( $( this ).parents('label').text().trim() === "None" ) {
                    $( this ).attr( 'checked', true );
                }
                if ( $( this ).parents('label').text().trim() === "Alive" ) {
                    $( this ).attr( 'checked', true );
                }
			} );
            $( 'input:text[name="postop-pacuhours\\[number\\]"]').focus();
            e.preventDefault();
			e.stopPropagation();
		});
		$( "form#dataEntry" ).prepend( button );
        $( 'input:radio.compres' ).change( function(e) {
            if ( $( this ).parents('label').text().trim() === "Yes" ) {
                var anyComp = false;
                $( 'input:radio.complication' ).each( function() {
                    if ( $( this ).parents('label').text().trim() !== "None" && $( this ).attr( 'checked' ) ) {
                        anyComp = true;
                    }   
                });
                if ( !anyComp ) {
                    alert('The patient cannot have had treatment for a complication if they didn\'t have a complication.  Please check your entry.');
                    $(this).attr('checked',false);
                }
            }
        } );
        $( 'input:radio.complication' ).change( function() {
            if ( $( this ).parents('label').text().trim() === "None" ) {
                var anyComp = false;
                $( 'input:radio.complication' ).each( function() {
                    if ( $( this ).parents('label').text().trim() !== "None" && $( this ).attr( 'checked' ) ) {
                        anyComp = true;
                    }   
                });
                if ( !anyComp ) {
                    $( 'input:radio.compres' ).each( function() {
                        if ( $( this ).parents('label').text().trim() === "Yes" && $( this ).attr( 'checked' ) ) {
                            alert('The patient cannot have had treatment for a complication if they didn\'t have a complication.  Please check your entry for ' + getLabel(this) + '.');
                            $(this).attr('checked',false);
                        }
                        if ( $( this ).parents('label').text().trim() === "Dead" && $( this ).attr( 'checked' ) ) {
                            alert('If the patient died they must have developed a complication.  Please check your entry for ' + getLabel(this) + '.');
                            $(this).attr('checked',false);
                        }
                    });
                }
            }
        });
        $('#mortModal').on('shown', function() {
            var anyComp = false;
            $( 'input:radio.complication' ).each( function() {
                if ( $( this ).parents('label').text().trim() !== "None" && $( this ).attr( 'checked' ) ) {
                    anyComp = true;
                }   
            });
            if ( !anyComp ) {
                if ( !oldHTML ) {
                    oldHTML = $("#mortModal div.modal-body").html();
                }
                $("#mortModal div.modal-body").html('<p>If the patient died they must have developed a complication.</p><p>Please check your entry.<p>');
                $( "#mortModal a.btn-primary" ).hide();
            } else {
                if ( oldHTML ) {
                    $("#mortModal div.modal-body").html( oldHTML );
                }
                $( "#mortModal a.btn-primary" ).show();
            }
        })
	}
    if ( page === "register" || page === "forgotpass" ) {
        var countryBox = '#' + page + '-country_id';
        var centreBox = '#' + page + '-centre_id';
        $('#' + page + '-country_id').val('');
        $('#' + page + '-centre_id').prop('disabled',true).val('');
        $('#' + page + '-centre_id').live("change",( function() {
            centreSelect = $(centreBox + ' option:selected').val();
        }));
        $('#' + page + '-country').change( function( e ){
            $('#' + page + '-centre_id').val('');
            refreshCentre($(this).val());
        });
    }
 });

var centreSelect;
var oldHTML;

if(typeof String.prototype.trim !== 'function') {
  String.prototype.trim = function() {
    return this.replace(/^\s+|\s+$/g, ''); 
  }
}
