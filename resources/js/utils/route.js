import { route as ziggyRoute } from '../../../vendor/tightenco/ziggy/dist/index.esm.js';

export function appRoute(name, params, absolute, config = globalThis.Ziggy) {
    return ziggyRoute(name, params, absolute, config);
}
