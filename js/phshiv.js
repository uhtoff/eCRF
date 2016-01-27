$(document).ready(function() {
if(!Modernizr.input.placeholder){
  $("input").each(
  function(){
    var inputField = $(this);
    if(inputField.val()=="" && inputField.attr("placeholder")!=""){
    
      inputField.val(inputField.attr("placeholder"));
      inputField.focus(function(){
        if(inputField.val()==inputField.attr("placeholder")){ inputField.val(""); }
      });
      
      inputField.blur(function(){
        if(inputField.val()==""){ inputField.val(inputField.attr("placeholder"));
		};
		if(inputField.attr('type')=='password'){}
      });
      
      $(inputField).closest('form').submit(function(){
        var form = $(this);
        if(!form.hasClass('placeholderPending')){
          $('input',this).each(function(){
            var clearInput = $(this);
            if(clearInput.val()==clearInput.attr("placeholder")){ clearInput.val(""); }
          });
          form.addClass('placeholderPending');
        }
      });
    
    }
  });
}
});