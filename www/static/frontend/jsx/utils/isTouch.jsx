export default function isTouch()
{
    return (window.whatInput.ask('loose') === 'touch' || window.whatInput.ask() === 'touch' );
}