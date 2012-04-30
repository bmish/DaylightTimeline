// http://stackoverflow.com/questions/901115/get-query-string-values-in-javascript
function getParameterByName(name)
{
  name = name.replace(/[\[]/, "\\\[").replace(/[\]]/, "\\\]");
  var regexS = "[\\?&]" + name + "=([^&#]*)";
  var regex = new RegExp(regexS);
  var results = regex.exec(window.location.search);
  if(results == null)
    return "";
  else
    return decodeURIComponent(results[1].replace(/\+/g, " "));
}

// http://wiki.vyre.com/index.php/JavaScript:_Opposite_colour
function decimalToHex(decimal) {
  var hex = decimal.toString(16);
  if (hex.length == 1) hex = '0' + hex;
  return hex;
}

function hexToDecimal(hex) {return parseInt(hex,16);}
 
function returnOpposite(color) {
  return decimalToHex(255 - hexToDecimal(color.substr(0,2))) 
    + decimalToHex(255 - hexToDecimal(color.substr(2,2))) 
    + decimalToHex(255 -  hexToDecimal(color.substr(4,2)));
}