/*
MIT License

Pterodactyl®
Copyright © Dane Everitt <dane@daneeveritt.com> and contributors

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
*/

import { action, Action } from 'easy-peasy';

export interface ProgressStore {
    continuous: boolean;
    progress?: number;

    startContinuous: Action<ProgressStore>;
    setProgress: Action<ProgressStore, number | undefined>;
    setComplete: Action<ProgressStore>;
}

const progress: ProgressStore = {
    continuous: false,
    progress: undefined,

    startContinuous: action((state) => {
        state.continuous = true;
    }),

    setProgress: action((state, payload) => {
        state.progress = payload;
    }),

    setComplete: action((state) => {
        if (state.progress) {
            state.progress = 100;
        }

        state.continuous = false;
    }),
};

export default progress;
