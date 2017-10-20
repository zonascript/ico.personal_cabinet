import _ from 'lodash';

function dedupArray(arr)
{
    let t =  [];
    for (let i in arr) {
        if (t.indexOf(arr[i]) === -1) {
            t.push(arr[i]);
        }
    }

    return t;
}

function dedupObj(obj)
{
    for (let key in obj) {
        if (obj.hasOwnProperty(key)) {
            obj[key] = dedup(obj[key]);
        }
    }

    return obj;
}

export default function dedup(val)
{
    if (val) {
        if (_.isArray(val)) {
            val = dedupArray(val);
        }
        else if (_.isObject(val)) {
            val = dedupObj(val);
        }
    }

    return val;
}