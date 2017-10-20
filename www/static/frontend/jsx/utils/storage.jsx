import Cookies from 'js-cookie';

class Storage
{
    constructor() {
        this.LocalStorageChecked = null;
    }

    hasLocalStorage() {
        if (this.LocalStorageChecked === null) {
            let test = 'test';
            try {
                localStorage.setItem(test, test);
                localStorage.removeItem(test);
                this.LocalStorageChecked = true;
            } catch (e) {
                this.LocalStorageChecked = false;
            }
        }

        return this.LocalStorageChecked;
    }

    get(key, def = null) {
        let value;

        if (this.hasLocalStorage()) {
            value = localStorage.getItem(key);
        }
        else {
            value = Cookies.get(key);
        }

        if (!value) {
            value = def;
        }
        return value;
    }

    set(key, value, expires = 30) {
        if (value === null) {
            this.remove(key);
            return;
        }

        if (this.hasLocalStorage()) {

            localStorage.setItem(key, value)
        }
        else {
            Cookies.set(key, value, {expires: expires});
        }
    }

    remove(key) {
        if (this.hasLocalStorage()) {
            localStorage.removeItem(key);
        }
        else {
            Cookies.remove(key);
        }
    }
}
export default new Storage();