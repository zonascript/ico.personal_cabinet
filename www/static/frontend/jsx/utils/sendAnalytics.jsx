export default function sendAnalytics(data = {})
{
    if (window['ga']) {
        window.ga('send', data);
    }
}