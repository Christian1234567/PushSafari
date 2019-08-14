<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class ApiPushController extends Controller
{
// Convenience function that returns an array of raw files needed to construct the package.

// Copies the raw push package files to $package_dir.
function copy_raw_push_package_files($package_dir, $raw_files) {
    mkdir($package_dir . '/icon.iconset');
    foreach ($raw_files as $raw_file) {
        copy("Package_Push.raw/$raw_file", "$package_dir/$raw_file");
    }
}

// Creates the manifest by calculating the hashes for all of the raw files in the package.
function create_manifest($package_dir, $package_version, $raw_files) {

    // Obtain hashes of all the files in the push package
    $manifest_data = array();
    foreach ($raw_files as $raw_file) {
        $file_contents = file_get_contents("$package_dir/$raw_file");
        if ($package_version === 1) {
            $manifest_data[$raw_file] = sha1($file_contents);
        } else if ($package_version === 2) {
            $hashType = 'sha512';
            $manifest_data[$raw_file] = array(
                'hashType' => $hashType,
                'hashValue' => hash($hashType, $file_contents),
            );
        } else {
            throw new Exception('Invalid push package version.');
      }
    }
    file_put_contents("$package_dir/manifest.json", json_encode((object)$manifest_data));
}

// Creates a signature of the manifest using the push notification certificate.
function create_signature($package_dir, $cert_path, $cert_password) {
    // Load the push notification certificate
    $pkcs12 = file_get_contents($cert_path);
    $certs = array();
    if(!openssl_pkcs12_read($pkcs12, $certs, $cert_password)) {
        return;
    }

    $signature_path = "$package_dir/signature";

    // Sign the manifest.json file with the private key from the certificate
    $cert_data = openssl_x509_read($certs['cert']);
    $private_key = openssl_pkey_get_private($certs['pkey'], $cert_password);
    openssl_pkcs7_sign("$package_dir/manifest.json", $signature_path, $cert_data, $private_key, array(), PKCS7_BINARY | PKCS7_DETACHED);

    // Convert the signature from PEM to DER
    $signature_pem = file_get_contents($signature_path);
    $matches = array();
    if (!preg_match('~Content-Disposition:[^\n]+\s*?([A-Za-z0-9+=/\r\n]+)\s*?-----~', $signature_pem, $matches)) {
        return;
    }
    $signature_der = base64_decode($matches[1]);
    file_put_contents($signature_path, $signature_der);
}

// Zips the directory structure into a push package, and returns the path to the archive.
function package_raw_data($package_dir, $raw_files) {
    $zip_path = "$package_dir.zip";

    // Package files as a zip file
    $zip = new ZipArchive();
    if (!$zip->open("$package_dir.zip", ZIPARCHIVE::CREATE)) {
        error_log('Could not create ' . $zip_path);
        return;
    }

    //$raw_files = raw_files();
    $raw_files[] = 'manifest.json';
    $raw_files[] = 'signature';
    foreach ($raw_files as $raw_file) {
        $zip->addFile("$package_dir/$raw_file", $raw_file);
    }

    $zip->close();
    return $zip_path;
}

// Creates the push package, and returns the path to the archive.
function create_push_package() {
    $package_version = 2;               // Change this to the desired push package version.
    $certificate_path = "D:\Proyectos\Laravel\SafariApiPus\public\Certificates.p12";     // Change this to the path where your certificate is located
    $certificate_password = ""; // Change this to the certificate's import password
    $package_dir = 'D:\Proyectos\Laravel\SafariApiPus\public\Package_Push';
    $raw_files = array(
        'icon.iconset/icon_16x16.png',
        'icon.iconset/icon_16x16@2x.png',
        'icon.iconset/icon_32x32.png',
        'icon.iconset/icon_32x32@2x.png',
        'icon.iconset/icon_128x128.png',
        'icon.iconset/icon_128x128@2x.png',
        'website.json'
    );
    /*if (!mkdir($package_dir)) {
        unlink($package_dir);
        die;
    }*/

    //$this -> copy_raw_push_package_files($package_dir, $raw_files);
    $this -> create_manifest($package_dir, $package_version, $raw_files);
    $this -> create_signature($package_dir, $certificate_path, $certificate_password);
    $package_path = $this -> package_raw_data($package_dir, $raw_files);

    return $package_path;
}


// MAIN
function mainMethod(){
    $package_path = $this -> create_push_package();
    if (empty($package_path)) {
            http_response_code(500);
            die;
        }

    header("Content-type: application/zip");
    echo file_get_contents($package_path);
    die;
}

function returnPackPush(){
    header("Content-type: application/zip");
    echo file_get_contents('D:\Proyectos\Laravel\SafariApiPus\public\CreatePackages.zip');
    die;
}

function log(Request $request){
    file_put_contents('D:\Proyectos\Laravel\SafariApiPus\public\error_log.txt', json_encode((object)$request->all()));
}

function saveDevice($token){
    try{
         /* We are using the sandbox version of the APNS for development. For production
        environments, change this to ssl://gateway.push.apple.com:2195 */
        $apnsServer = 'ssl://gateway.push.apple.com:2195';
        //$apnsServer = 'ssl://api.sandbox.push.apple.com:443';
        /* Make sure this is set to the password that you set for your private key
        when you exported it
        e .pem file using openssl on your OS X */
        $privateKeyPassword = '123456';
        /* Pur your device token here */
        $deviceToken = $token;
        /* Replace this with the name of the file that you have placed by your PHP
        script file, containing your private key and certificate that you generated
        earlier */
        $pushCertAndKeyPemFile = 'D:\Proyectos\Laravel\SafariApiPus\public\ApnsDev.pem';
        $stream = stream_context_create();
        stream_context_set_option($stream,
        'ssl',
        'passphrase',
        $privateKeyPassword);
        stream_context_set_option($stream,
        'ssl',
        'local_cert',
        $pushCertAndKeyPemFile);

        $connectionTimeout = 20;
        $connectionType = STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT;
        $connection = stream_socket_client($apnsServer,
            $errorNumber,
            $errorString,
            $connectionTimeout,
            $connectionType,
            $stream
        );
        if (!$connection){
        echo "Failed to connect to the APNS server. Error no = $errorNumber<br/>";
        exit;
        } else {
        echo "Successfully connected to the APNS. Processing...</br>";
        }
        $messageBody['aps'] = array(
            'alert' => array(
                "title" => "Flight A998 Now Boarding",
                "body" => "Hola Prueba.",
                "action" => "View"
            ),
            'sound' => 'default',
            'badge' => 1,
            'url-args' => array('https://e7f0081d.ngrok.io')
        );
        $payload = json_encode($messageBody);
        $notification = chr(0) .
            pack('n', 32) .
            pack('H*', $token) .
            pack('n', strlen($payload)) .
            $payload;
        $wroteSuccessfully = fwrite($connection, $notification, strlen($notification));
        if (!$wroteSuccessfully){
        echo "Could not send the message<br/>";
        }
        else {
        echo "Successfully sent the message<br/>";
        }
        fclose($connection);
    }catch(Exception $e){
        file_put_contents('D:\Proyectos\Laravel\SafariApiPus\public\error_log.txt', json_encode((object)$e));
    }
    echo $token;
    die;
}


function mensajePrueba(){
    return ApnMessage::create()
            ->badge(1)
            ->title('Account approved')
            ->body("Your {$notifiable->service} account was approved!");
}

}