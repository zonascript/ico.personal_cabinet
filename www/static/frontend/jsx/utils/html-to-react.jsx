import { Parser } from 'html-to-react';
const parser = new Parser();

export default function toReact(html, state = {})
{
    return parser.parse(html, state);
}