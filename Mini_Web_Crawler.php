<?php
$logFile = 'log.txt';
$urlFile = 'urls.json';

try {
    // Έλεγχος αρχείου με URLs (fetch)
    if (!file_exists($urlFile)) {
        throw new Exception("URLs file not found.");
    }

    // Ανάγνωση και μετατροπή σε πίνακα (parse)
    $jsonData = file_get_contents($urlFile);
    $urls = json_decode($jsonData, true); //Μετατροπη Json σε array

    if (!is_array($urls)) {
        throw new Exception("Invalid JSON format in $urlFile");
    }

    // Συνάρτηση check URL accessibility
    function isUrlAccessible($url, &$error) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_exec($ch);

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if (!empty($curlError)) {
            $error = $curlError;
            return false;
        }

        if ($httpCode < 200 || $httpCode >= 400) {
            $error = "HTTP $httpCode";
            return false;
        }

        return true;
    }

    // Loop ελέγχου URL (retry if failed) (save)
    foreach ($urls as $url) {
        $timestamp = date("Y-m-d H:i:s");
        $error = '';

        if (isUrlAccessible($url, $error)) {
            echo "Accessible: $url\n";
        } else {
            echo "Retrying: $url\n";
            sleep(1);
            if (isUrlAccessible($url, $error)) {
                echo "Accessible on retry: $url\n";
            } else {
                $message = "[$timestamp] ERROR Unreachable: $url - $error";
                echo "$message\n";
                file_put_contents($logFile, $message . PHP_EOL, FILE_APPEND); //error logging if site unreachable
            }
        }
    }

} catch (Exception $e) {
    // Κεντρικός χειρισμός σφαλμάτων (error loging if catastropic failure)
    $timestamp = date("Y-m-d H:i:s");
    $errorMsg = "[$timestamp] ERROR: " . $e->getMessage();
    echo $errorMsg . "\n";
    file_put_contents($logFile, $errorMsg . PHP_EOL, FILE_APPEND);
}
?>
