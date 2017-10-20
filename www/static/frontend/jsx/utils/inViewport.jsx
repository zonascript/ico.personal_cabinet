import getElementPosition from './getElementPosition';

const isHidden = (element) =>
element.offsetParent === null;

export default function inViewport(element, container, customOffset = {top: 0, bottom:0, left: 0, right: 0}) {
    if (isHidden(element)) {
        return false;
    }

    let top;
    let bottom;
    let left;
    let right;

    if (typeof container === 'undefined' || container === window) {
        top = window.pageYOffset;
        left = window.pageXOffset;
        bottom = top + window.innerHeight;
        right = left + window.innerWidth;
    } else {
        const containerPosition = getElementPosition(container);

        top = containerPosition.top;
        left = containerPosition.left;
        bottom = top + container.offsetHeight;
        right = left + container.offsetWidth;
    }

    const elementPosition = getElementPosition(element);

    return (
        top <= elementPosition.top + element.offsetHeight + customOffset.top &&
        bottom >= elementPosition.top - customOffset.bottom &&
        left <= elementPosition.left + element.offsetWidth + customOffset.left &&
        right >= elementPosition.left - customOffset.right
    );
}