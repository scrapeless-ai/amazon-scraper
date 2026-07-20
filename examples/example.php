<?php
/**
 * Google Scraper — Scrapeless Scraping API (PHP example)
 *
 * Docs:  https://apidocs.scrapeless.com/doc-800321
 *        https://apidocs.scrapeless.com/doc-1275927
 * Token: https://app.scrapeless.com/passport/login?redirect=/quick-start
 *
 * The Google actor (`scraper.google.search`) selects a search vertical via the
 * `tbm` input field:
 *   web (default) | images (isch) | local (lcl) | video (vid) | shopping (shop) | news (nws)
 *
 * Run (requires the php-curl extension):
 *   export SCRAPELESS_API_TOKEN="your_api_token"
 *   php example.php            # defaults to the "web" vertical
 *   php example.php images     # or: web | images | local | video | shopping
 */

$apiUrl = "https://api.scrapeless.com/api/v1/scraper/request";
$apiToken = getenv("SCRAPELESS_API_TOKEN") ?: "YOUR_API_TOKEN";

// Ready-to-use input payloads for each search vertical.
$sampleInputs = [
    "web" => [
        "q"             => "coffee",
        "hl"            => "en",
        "gl"            => "us",
        "google_domain" => "google.com",
    ],
    "images" => [
        "q"             => "Apple Iphone16",
        "hl"            => "en",
        "gl"            => "us",
        "google_domain" => "google.com",
        "tbm"           => "isch",
    ],
    "local" => [
        "q"             => "Coffee",
        "hl"            => "en",
        "gl"            => "us",
        "google_domain" => "google.com",
        "tbm"           => "lcl",
    ],
    "video" => [
        "q"             => "Coffee",
        "google_domain" => "google.com",
        "start"         => 0,
        "num"           => 10,
        "tbm"           => "vid",
    ],
    "shopping" => [
        "q"             => "Coffee",
        "google_domain" => "google.com",
        "start"         => 0,
        "num"           => 10,
        "tbm"           => "shop",
    ],
];

$vertical = $argv[1] ?? "web";
if (!isset($sampleInputs[$vertical])) {
    fwrite(STDERR, "Unknown vertical '{$vertical}'. Choose one of: web, images, local, video, shopping\n");
    exit(1);
}

$payload = ["actor" => "scraper.google.search", "input" => $sampleInputs[$vertical]];

$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_TIMEOUT        => 180,
    CURLOPT_HTTPHEADER     => [
        "Content-Type: application/json",
        "x-api-token: " . $apiToken,
    ],
    CURLOPT_POSTFIELDS => json_encode($payload),
]);

$response = curl_exec($ch);
if ($response === false) {
    fwrite(STDERR, "Request failed: " . curl_error($ch) . PHP_EOL);
    exit(1);
}
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// The Scraping API distinguishes scenarios by HTTP status code.
switch ($status) {
    case 200:
        // Synchronous success: the body is the scraped SERP data.
        $data = json_decode($response, true);
        echo "[200] Success — '{$vertical}' results received." . PHP_EOL;
        if (is_array($data)) {
            echo "  top-level keys: " . implode(", ", array_slice(array_keys($data), 0, 12)) . PHP_EOL;
        }
        echo PHP_EOL . "  Raw response (truncated to 1500 chars):" . PHP_EOL;
        echo "  " . substr($response, 0, 1500) . PHP_EOL;
        break;

    case 201:
        // Task accepted but still running. Retrieve it later by task id (see docs).
        $body = json_decode($response, true);
        echo "[201] Task in progress — message: " . ($body["message"] ?? "") .
             ", taskId: " . ($body["taskId"] ?? "") . PHP_EOL;
        echo "      Fetch the result later using the task id (see docs)." . PHP_EOL;
        break;

    case 400:
        // Scraping failed — inspect the error code and message.
        $body = json_decode($response, true);
        echo "[400] Bad request — code: " . ($body["code"] ?? "") .
             ", message: " . ($body["message"] ?? "") . PHP_EOL;
        break;

    default:
        echo "[{$status}] Unexpected response:" . PHP_EOL . $response . PHP_EOL;
        exit(1);
}
