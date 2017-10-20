import { h, render } from 'preact';

export default function preact_replace(container, component)
{
    let temp = document.createElement("div");
    let $container = $(container);
    let children = $container.children;

    render(component, temp);

    $container.replaceChild(temp.children, children);
}