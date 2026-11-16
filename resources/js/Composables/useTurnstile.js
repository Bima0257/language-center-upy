const SCRIPT_ID = "cf-turnstile-script";
const SCRIPT_SRC = "https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit";
const LOAD_TIMEOUT_MS = 10000;
const LOAD_ERROR_MESSAGE = "Verifikasi keamanan gagal dimuat.";

let scriptPromise = null;

function waitForGlobal(resolve, reject, script) {
    let settled = false;

    const finish = () => {
        if (settled) return;
        settled = true;

        if (window.turnstile) {
            resolve(window.turnstile);
        } else {
            script.remove();
            reject(new Error(LOAD_ERROR_MESSAGE));
        }
    };

    const fail = (error) => {
        clearTimeout(timeout);
        if (settled) return;
        settled = true;
        script.remove();
        reject(error);
    };

    const timeout = setTimeout(() => fail(new Error(LOAD_ERROR_MESSAGE)), LOAD_TIMEOUT_MS);

    script.addEventListener(
        "load",
        () => {
            clearTimeout(timeout);
            finish();
        },
        { once: true },
    );

    script.addEventListener(
        "error",
        () => fail(new Error(LOAD_ERROR_MESSAGE)),
        { once: true },
    );
}

export function loadTurnstile() {
    if (typeof window === "undefined") {
        return Promise.reject(new Error(LOAD_ERROR_MESSAGE));
    }

    if (window.turnstile) {
        return Promise.resolve(window.turnstile);
    }

    if (scriptPromise) {
        return scriptPromise;
    }

    scriptPromise = new Promise((resolve, reject) => {
        const existingScript = document.getElementById(SCRIPT_ID);

        if (existingScript) {
            waitForGlobal(resolve, reject, existingScript);

            return;
        }

        const script = document.createElement("script");
        script.id = SCRIPT_ID;
        script.src = SCRIPT_SRC;
        script.async = true;
        script.defer = true;

        waitForGlobal(resolve, reject, script);

        document.head.appendChild(script);
    }).catch((error) => {
        scriptPromise = null;

        throw error;
    });

    return scriptPromise;
}
