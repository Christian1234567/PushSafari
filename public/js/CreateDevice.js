//var applicationServerPublicKey = '';
var serviceWorker = '/sw.js';
var isSubscribed = false;
var pushId = 'web.com.propiedadesmexico';
var navegador = '';
var server = 'https://980626a7.ngrok.io';

$(document).ready(function () {
    getBrowserInfo()
    try {
        if (navegador.includes("Safari")) {
            //$('#btnPresioname').click();
        } else {
            requestPermissionNavegadores();
        }
    } catch (e) {
        console.log(e);
    }
});

async function requestPermissionNavegadores(){
    var permission = await Notification.requestPermission();
    if (permission === 'denied'){
        console.log('Notificacion.requestPermission: Negacion para recibir notificaciones.');
    }else if (permission === 'granted'){
        console.log('[Notification.requestPermission] Afirmacion para recibir notificaciones.');
        initialiseServiceWorker();
        subscribe();
    }
}

function initialiseServiceWorker() {
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register(serviceWorker).then(function(result){
            handleSWRegistration(result);
        });
    } else {
        errorHandler('[initialiseServiceWorker] Service workers are not supported in this browser.');
    }
};

function handleSWRegistration(reg) {
    if (reg.installing) {
        console.log('Service worker installing');
    } else if (reg.waiting) {
        console.log('Service worker installed');
    } else if (reg.active) {
        console.log('Service worker active');
    }

    initialiseState(reg);
}

// Once the service worker is registered set the initial state
function initialiseState(reg) {
    console.log(reg);
    // Are Notifications supported in the service worker?
    if (!(reg.showNotification)) {
        errorHandler('[initialiseState] Notifications aren\'t supported on service workers.');
        return;
    }

    // Check if push messaging is supported
    if (!('PushManager' in window)) {
        errorHandler('[initialiseState] Push messaging isn\'t supported.');
        return;
    }

    navigator.serviceWorker.ready.then(function(subscription){
        isSubscribed = subscription;
        if (isSubscribed) {
            console.log('User is already subscribed to push notifications');
        } else {
            console.log('User is not yet subscribed to push notifications');
        }
    });
}

async function subscribe() {
    try {
        let serviceReady = await navigator.serviceWorker.ready;

        var subscribeParams = { userVisibleOnly: true };
        var applicationServerKey = urlB64ToUint8Array(applicationServerPublicKey);
        subscribeParams.applicationServerKey = applicationServerKey;

        let subscribre = await serviceReady.pushManager.subscribe(subscribeParams);
        isSubscribed = true;

        var p256dh = base64Encode(subscribre.getKey('p256dh'));
        var auth = base64Encode(subscribre.getKey('auth'));

        $('#PushEndpoint').val(subscribre.endpoint);
        $('#PushP256DH').val(p256dh);
        $('#PushAuth').val(auth);
    } catch (e) {
        console.log(e);
    }
}

function urlB64ToUint8Array(base64String) {
    var padding = '='.repeat((4 - base64String.length % 4) % 4);
    var base64 = (base64String + padding)
        .replace(/\-/g, '+')
        .replace(/_/g, '/');

    var rawData = window.atob(base64);
    var outputArray = new Uint8Array(rawData.length);

    for (var i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
}

function base64Encode(arrayBuffer) {
    return btoa(String.fromCharCode.apply(null, new Uint8Array(arrayBuffer)));
}

var getBrowserInfo = function () {
    var ua = navigator.userAgent, tem,
        M = ua.match(/(opera|chrome|safari|firefox|msie|trident(?=\/))\/?\s*(\d+)/i) || [];
    if (/trident/i.test(M[1])) {
        tem = /\brv[ :]+(\d+)/g.exec(ua) || [];
        return 'IE ' + (tem[1] || '');
    }
    
    if (M[1] === 'Chrome') {
        tem = ua.match(/\b(OPR|Edge)\/(\d+)/);
        if (tem != null) return tem.slice(1).join(' ').replace('OPR', 'Opera');
    }
    M = M[2] ? [M[1], M[2]] : [navigator.appName, navigator.appVersion, '-?'];
    if ((tem = ua.match(/version\/(\d+)/i)) != null) M.splice(1, 1, tem[1]);
    navegador = M[0] + " " + M[1];
};




//Metodos para Safari

async function PeticionPermiso() {
    var permiso = await window.safari.pushNotification.permission(pushId);
    checkRemotePermission(permiso);
}

async function checkRemotePermission(permissionData) {
    if (permissionData.permission === 'default') {
        window.safari.pushNotification.requestPermission(server,pushId,{},checkRemotePermission);
    }                                                     
    else if (permissionData.permission === 'denegado') {
        console.dir(argumentos);
    }
    else if (permissionData.permission === 'granted') {
        console.log(permissionData.deviceToken);
        PruebaWebPush();
    }
};

async function PruebaWebPush(){

}

$('#btnPresioname').on('click', function(e){
    PeticionPermiso();
});