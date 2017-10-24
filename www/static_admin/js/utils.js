/**
 * Example:
 * $(window).resize(function (e) {
 *      delay(function() { ... }, 300);
 * });
 */
var delay = (function(){
    var timer = 0;
    return function(callback, ms){
        clearTimeout (timer);
        timer = setTimeout(callback, ms);
    };
})();
