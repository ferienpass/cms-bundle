'use strict';
var __awaiter = (this && this.__awaiter) || function (thisArg, _arguments, P, generator) {
    function adopt(value) { return value instanceof P ? value : new P(function (resolve) { resolve(value); }); }
    return new (P || (P = Promise))(function (resolve, reject) {
        function fulfilled(value) { try { step(generator.next(value)); } catch (e) { reject(e); } }
        function rejected(value) { try { step(generator["throw"](value)); } catch (e) { reject(e); } }
        function step(result) { result.done ? resolve(result.value) : adopt(result.value).then(fulfilled, rejected); }
        step((generator = generator.apply(thisArg, _arguments || [])).next());
    });
};
import {Controller} from '@hotwired/stimulus';
// @ts-ignore
import {getComponent} from '@symfony/ux-live-component';

export default class default_1 extends Controller {
    initialize() {
        return __awaiter(this, void 0, void 0, function* () {
            if (!(this.element instanceof HTMLElement)) {
                return;
            }
            this.component = yield getComponent(this.element);
            this.component.on('render:finished', (component) => {
            });
        });
    }
    submit(event) {
        fetch('/check_login', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify({ username: this.usernameTarget.value, password: this.passwordTarget.value, REQUEST_TOKEN: this.requestTokenValue }) })
            .then(response => response.json())
            //.then(json => message = json.message)
            //.then(message => { if(!message) authError = true })
            .catch(() => { window.location.reload(); });
        //.finally(() => { isLoading = false; })
    }
}
default_1.values = {
    requestToken: String,
};
default_1.targets = ["username", "password", "remember_me"];
