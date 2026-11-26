/*
 * Journey – kleine Helfer für alle Seiten (klassisches Skript, kein Modul: die Partials rufen die Funktionen
 * direkt aus onclick bzw. ihren Inline-Skripten auf).
 */

/**
 * fetch für die Prüf-Routen, mit bis zu drei Versuchen: Im Mobilnetz (Zug, Sauna, Keller …) geht schon mal eine
 * Anfrage verloren. Früher blieb das Spiel dann einfach stehen („Kompiliere...“), und die Seite war gesperrt.
 */
window.journeyFetch = async function (url, options, attempts = 3) {
    let lastError;
    for (let i = 0; i < attempts; i++) {
        try {
            const response = await fetch(url, Object.assign({ credentials: 'same-origin' }, options));
            if (response.ok || response.status < 500) {
                return response;
            }
            lastError = new Error('HTTP ' + response.status);
        } catch (e) {
            lastError = e;
        }
        await new Promise((resolve) => setTimeout(resolve, 600 * (i + 1)));
    }
    alert('Keine Verbindung – bitte gleich nochmal versuchen.');
    throw lastError;
};
