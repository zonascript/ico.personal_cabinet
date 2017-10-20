import 'modernizr';
import  "./_binds/response_status_278";
import  "./_binds/click_mmodal";
import  "./_binds/search_page";

import  "./ext/jq-swipe";

import ViewType from "./components/ViewType";
import LazyImageLoad from "./components/LazyImageLoad";
import Loader from "./components/Loader";

import isTouch from "./utils/isTouch";
import isMedia from "./utils/isMedia";
import documentReady from "./utils/documentReady";

(function(){
    window['loader'] = new Loader;
    new LazyImageLoad();
    new ViewType();

    isMedia('medium', '(max-width: 1023px)');

    $('img[usemap]').rwdImageMaps();
    Waves.attach('.waves');
    Waves.init();

    $(document).on('swipe', function(e, Dx, Dy, angle) {
        if (isMedia('medium') && isTouch()) {

            if (angle < 15) {
                if (Dx === 1 && Dy === 0) { //right
                    $('#offCanvasLeft').foundation('open');
                }
                else if (Dx === -1 && Dy === 0) {
                    $('#offCanvasLeft').foundation('close');
                }
            }
        }
    });

    documentReady(()=>{
        WebFont.load({
            google: {
                families: ['Lato:300,300i,400,400i,700,700i,900']
            }
        });

        $(document).foundation();
        loader.detach();
    })
})();
