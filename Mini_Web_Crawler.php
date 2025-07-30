<?php
require_once('simple_html_dom.php'); // Βιβλιοθήκη όπως ζητήθηκε

$logFile = 'log.txt';
$urlFile = 'urls.json';
$configFile = 'scrape_config.json';

function initializeDatabase($dbPath = 'scrape_results.db') {
    //init Database
    $db = new SQLite3($dbPath);
    $db->exec("CREATE TABLE IF NOT EXISTS scraped_products (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        store TEXT,
        product_name TEXT,
        price TEXT,
        availability TEXT,
        scraped_at TEXT
    )");
    return $db;
}


try {
    // Φόρτωση URLs και config από αρχεία JSON
    $urls = loadJsonFile($urlFile);
    $config = loadJsonFile($configFile);

    $db = initializeDatabase(); // Δημιουργεί τη βάση αν δεν υπάρχει


    foreach ($urls as $url) {
        $timestamp = date("Y-m-d H:i:s");
        $domain = getDomainFromUrl($url); // Εξαγωγή domain (π.χ. askadamstore.com)
        $selectors = $config[$domain] ?? null;

        if (!$selectors) {
            logMessage("[$timestamp] No config for: $domain"); // Αν δεν υπάρχει config για αυτό το domain, skip
            continue;
        }

        if (!checkUrlAvailability($url)) {
            logMessage("[$timestamp] ERROR Unreachable: $url"); // Αν δεν είναι προσβάσιμο, log error
            continue;
        }

        echo "Accessible: $url\n";

        // Κύρια συνάρτηση scraping
        [$title, $price, $availability] = scrapeDataFromPage($url, $selectors);

        // Εμφάνιση και logging αποτελεσμάτων
        printScrapeResult($url, $title, $price, $availability, $timestamp);
        saveToDatabase($db, $domain, $title, $price, $availability, $timestamp); //Save to DB
    }

} catch (Exception $e) {
    // Κεντρικός χειρισμός σφαλμάτων
    logMessage("[" . date("Y-m-d H:i:s") . "] ERROR: " . $e->getMessage());
}

//Συναρτήσεις

function loadJsonFile($path) {
    // Επιστρέφει array από JSON αρχείο, ή πετάει exception αν λείπει ή είναι άκυρο
    if (!file_exists($path)) throw new Exception("Missing file: $path");
    $data = json_decode(file_get_contents($path), true);
    if (!is_array($data)) throw new Exception("Invalid JSON in $path");
    return $data;
}

function getDomainFromUrl($url) {
    // Επιστρέφει μόνο το καθαρό domain χωρίς www 
    $host = parse_url($url, PHP_URL_HOST);
    return preg_replace('/^www\./', '', strtolower($host));
}

function checkUrlAvailability($url) {
    // Χρησιμοποιεί cURL για να ελέγξει αν το URL είναι προσβάσιμο (status code check)
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_NOBODY => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true
    ]);
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    return empty($curlError) && $httpCode >= 200 && $httpCode < 400;
}

function scrapeDataFromPage($url, $selectors) {
    // Εκτελεί scraping με βάση τους selectors από το config
    $html = file_get_html($url);
    if (!$html) return [null, null, null];

    $title = $html->find($selectors['title'], 0)?->plaintext ?? '';
    $price = extractPrice($html, $selectors['price']);
    $availability = $html->find($selectors['availability'], 0)?->plaintext ?? '';

    return [trim($title), trim($price), trim($availability)];
}

function extractPrice($html, $priceSelector) {
    // Υλοποιεί την ανάγνωση τιμής μέσω selector (απλή λογική προς το παρόν επειδη σε αλλα site το βαζει σε ξεχωριστο tag και θελει μαλλον regex επέλεξα να μην τα βάλω στο παράδειγμα)
    $priceNode = $html->find($priceSelector, 0);
    if (!$priceNode) return '';
    return $priceNode->plaintext;
}

function printScrapeResult($url, $title, $price, $availability, $timestamp) {
    // Εκτυπώνει το αποτέλεσμα scraping και καταγράφει αν λείπει κάποιο πεδίο
    echo "Title: " . ($title ?: 'N/A') . "\n";
    echo "Price: " . ($price ?: 'N/A') . "\n";
    echo "Availability: " . ($availability ?: 'Unknown') . "\n";

    if (!$title || !$price || !$availability) {
        $missing = (!$title ? 'title ' : '') . (!$price ? 'price ' : '') . (!$availability ? 'availability ' : '');
        logMessage("[$timestamp] Missing: $missing-> $url");
    }

    echo str_repeat('-', 50) . "\n";
}

function logMessage($msg) {
    // Καταγράφει μηνύματα σε log αρχείο και εμφανίζει στην κονσόλα
    echo $msg . "\n";
    file_put_contents('log.txt', $msg . PHP_EOL, FILE_APPEND);
}

function saveToDatabase($db, $store, $product_name, $price, $availability, $scraped_at) {
    $stmt = $db->prepare("INSERT INTO scraped_products (store, product_name, price, availability, scraped_at)
                          VALUES (:store, :product_name, :price, :availability, :scraped_at)");
    $stmt->bindValue(':store', $store, SQLITE3_TEXT);
    $stmt->bindValue(':product_name', $product_name, SQLITE3_TEXT);
    $stmt->bindValue(':price', $price, SQLITE3_TEXT);
    $stmt->bindValue(':availability', $availability, SQLITE3_TEXT);
    $stmt->bindValue(':scraped_at', $scraped_at, SQLITE3_TEXT);
    $stmt->execute();
}

