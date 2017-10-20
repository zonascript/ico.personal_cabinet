window['medias'] = {};

export default function isMedia(name, media = null)
{
    if (media) {
        window['medias'][name] = window.matchMedia( media );
    }
    else {
        if (window['medias'][name]) {
            return window['medias'][name].matches;
        }
    }
}