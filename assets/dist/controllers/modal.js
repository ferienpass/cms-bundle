'use strict';
import { Controller } from '@hotwired/stimulus';
// @ts-ignore
import { enter, leave } from "el-transition";
//import {Component, getComponent} from '@symfony/ux-live-component';
export default class default_1 extends Controller {
    constructor() {
        super(...arguments);
        this.observer = null;
    }
    connect() {
        if (this.hasDynamicContentTarget) {
            this.observer = new MutationObserver(() => {
                const shouldOpen = this.dynamicContentTarget.innerHTML.trim().length > 0;
                if (shouldOpen && !this.dialogTarget.open) {
                    this.show();
                }
                else if (!shouldOpen && this.dialogTarget.open) {
                    this.hide();
                }
            });
            this.observer.observe(this.dynamicContentTarget, {
                childList: true,
                characterData: true,
                subtree: true
            });
        }
    }
    disconnect() {
        if (this.observer) {
            this.observer.disconnect();
        }
        if (this.dialogTarget.open) {
            this.hide();
        }
    }
    show() {
        if (!this.dialogTarget.classList.contains('sm:max-w-4xl') && !this.dialogTarget.classList.contains('sm:max-w-sm')) {
            this.dialogTarget.classList.add('sm:max-w-4xl');
        }
        this.dialogTarget.showModal();
        document.body.classList.add('overflow-hidden');
        enter(this.dialogTarget);
    }
    hide() {
        document.body.classList.remove('overflow-hidden');
        this.dialogTarget.close();
        leave(this.dialogTarget);
    }
    open(event) {
        var _a, _b;
        this.dynamicContentTarget.setAttribute('src', event.detail.url);
        if ((_a = event.detail.rounded) !== null && _a !== void 0 ? _a : false) {
            this.dialogTarget.classList.add('rounded-lg');
        }
        if ('sm' === ((_b = event.detail.size) !== null && _b !== void 0 ? _b : null)) {
            this.dialogTarget.classList.add('sm:max-w-sm');
        }
        this.show();
    }
}
default_1.targets = ["dialog", "dynamicContent"];
