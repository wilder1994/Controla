/* Cola offline de Supervisión: snapshot + outbox IndexedDB. */
(function (global) {
    const DB_NAME = 'controla-sup';
    const DB_VER = 1;

    function openDb() {
        return new Promise((resolve, reject) => {
            const req = indexedDB.open(DB_NAME, DB_VER);
            req.onupgradeneeded = () => {
                const db = req.result;
                if (!db.objectStoreNames.contains('meta')) {
                    db.createObjectStore('meta');
                }
                if (!db.objectStoreNames.contains('outbox')) {
                    db.createObjectStore('outbox', { keyPath: 'id', autoIncrement: true });
                }
            };
            req.onsuccess = () => resolve(req.result);
            req.onerror = () => reject(req.error);
        });
    }

    function txDone(tx) {
        return new Promise((resolve, reject) => {
            tx.oncomplete = () => resolve();
            tx.onerror = () => reject(tx.error);
            tx.onabort = () => reject(tx.error);
        });
    }

    async function metaGet(key) {
        const db = await openDb();
        return new Promise((resolve, reject) => {
            const req = db.transaction('meta').objectStore('meta').get(key);
            req.onsuccess = () => resolve(req.result);
            req.onerror = () => reject(req.error);
        });
    }

    async function metaSet(key, value) {
        const db = await openDb();
        const tx = db.transaction('meta', 'readwrite');
        tx.objectStore('meta').put(value, key);
        await txDone(tx);
    }

    async function enqueue(item) {
        const db = await openDb();
        const tx = db.transaction('outbox', 'readwrite');
        tx.objectStore('outbox').add(item);
        await txDone(tx);
    }

    async function allOutbox() {
        const db = await openDb();
        return new Promise((resolve, reject) => {
            const req = db.transaction('outbox').objectStore('outbox').getAll();
            req.onsuccess = () => resolve(req.result || []);
            req.onerror = () => reject(req.error);
        });
    }

    async function removeOutbox(id) {
        const db = await openDb();
        const tx = db.transaction('outbox', 'readwrite');
        tx.objectStore('outbox').delete(id);
        await txDone(tx);
    }

    async function outboxCount() {
        const db = await openDb();
        return new Promise((resolve, reject) => {
            const req = db.transaction('outbox').objectStore('outbox').count();
            req.onsuccess = () => resolve(req.result || 0);
            req.onerror = () => reject(req.error);
        });
    }

    function uuid() {
        if (global.crypto?.randomUUID) return global.crypto.randomUUID();
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
            const r = (Math.random() * 16) | 0;
            return (c === 'x' ? r : (r & 0x3) | 0x8).toString(16);
        });
    }

    function isOfflineError(err) {
        if (!err) return false;
        if (err.offline) return true;
        if (typeof navigator !== 'undefined' && navigator.onLine === false) return true;
        return err.name === 'TypeError';
    }

    global.ControlaOffline = {
        metaGet,
        metaSet,
        enqueue,
        allOutbox,
        removeOutbox,
        outboxCount,
        uuid,
        isOfflineError,
    };
})(window);
