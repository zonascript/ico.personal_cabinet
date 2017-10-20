export default function documentReady(callback)
{
    if (document.readyState !== 'complete') {
        $(document).ready(()=>{
            callback();
        })
    }
    else {
        callback();
    }
}