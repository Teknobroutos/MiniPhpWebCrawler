<?php
//HardCoded URL List
$urls = [
    "smashduck.eu",
    "https://try-123456789.com",
    "https://google.com"
];

$logFile = 'log.txt';
//Chech if URL Accesible - Log
foreach ($urls as $url) {
    $ch = curl_init($url);//new curl session
    curl_setopt($ch, CURLOPT_NOBODY, true); //Παίρνω Μονο headers, μετα να το αλλαξω για να παιρνω ολο το Html
    curl_setopt($ch, CURLOPT_TIMEOUT, 10); // timeout 10 sec

    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch); // 0 if no error

    $timestamp = date("Y-m-d H:i:s"); // Year-Month-Day- Hours-Mins-Sec

    if (!curl_errno($ch) && $httpCode >= 200 && $httpCode < 400) {
        echo "Accessible: $url\n";
    } else { 
        $message = "[$timestamp] ERROR Unreachable: $url - ";
        $message .= $curlError ? $curlError : "HTTP $httpCode";
        echo "$message\n";
        file_put_contents($logFile, $message . PHP_EOL, FILE_APPEND); //Write to file
    }

    curl_close($ch);
}
?>
