const PushNotificationManager = function(publicKey) {
    const applicationServerPublicKey = publicKey;
    const publicMethods              = {
        subscriptionStatus: () => {
            return _isSubscribed;
        },
        subscribe         : () => {
            const applicationServerKey = urlB64ToUint8Array(applicationServerPublicKey);
            _worker.pushManager.subscribe({
                       userVisibleOnly     : true,
                       applicationServerKey: applicationServerKey
                   })
                   .then(function(subscription) {
                       _isSubscribed = true;

                       // console.log( 'User is subscribed.', subscription );
                       updateSubscriptionCode(subscription);

                       //todo: send subscription info to server

                   });
        },
        unSubscribe       : () => {
            if (!_isSubscribed)
                throw "Not subscribed";

            _worker.pushManager.getSubscription()
                   .then((subscription) => {
                       if (subscription) {
                           return subscription.unsubscribe();
                       }
                   })
                   .then(() => {
                       _isSubscribed = false;
                       // console.log( 'un-subscribe successful.' );
                       updateSubscriptionCode(null);
                       //todo: send un-subscription to server
                   });
        }
    };

    let _worker;
    let _isSubscribed = false;

    function urlB64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64  = (base64String + padding)
            .replace(/\-/g, '+')
            .replace(/_/g, '/');

        const rawData     = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);

        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }

    function updateSubscriptionCode(subscription) {
        let codeArea         = document.querySelector(".subscription-info");
        codeArea.textContent = JSON.stringify(subscription);
    }

    function registerWorker(worker, resolve, reject) {
        if (!worker)
            reject('Service worker not registered!');

        const resolveWhenActivated = () => {
            if( workerState.state === "activated" ) {
                _worker = worker;
                resolve(publicMethods);
            }
        };

        let workerState = worker.active || worker.installing || worker.waiting;
        if( workerState.state === "activated" )
            resolveWhenActivated();

        workerState.onstatechange = resolveWhenActivated;
    }

    return new Promise((resolve, reject) => {
        if (!'serviceWorker' in navigator || !'PushManager' in window)
            reject('Push messaging is not supported');

        navigator.serviceWorker.register('scripts/sw.js')
                 .then((worker) => registerWorker(worker, resolve, reject));
    });
};
