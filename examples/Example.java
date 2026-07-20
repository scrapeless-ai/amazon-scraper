// Google Scraper — Scrapeless Scraping API (Java example)
//
// Docs:  https://apidocs.scrapeless.com/doc-800321
//        https://apidocs.scrapeless.com/doc-1275927
// Token: https://app.scrapeless.com/passport/login?redirect=/quick-start
//
// The Google actor (scraper.google.search) selects a search vertical via the
// tbm input field:
//   web (default) | images (isch) | local (lcl) | video (vid) | shopping (shop) | news (nws)
//
// Run (Java 11+, uses the built-in HttpClient):
//   export SCRAPELESS_API_TOKEN="your_api_token"
//   java Example.java            # defaults to the "web" vertical
//   java Example.java images     # or: web | images | local | video | shopping

import java.net.URI;
import java.net.http.HttpClient;
import java.net.http.HttpRequest;
import java.net.http.HttpResponse;
import java.time.Duration;
import java.util.Map;

public class Example {

    private static final String API_URL = "https://api.scrapeless.com/api/v1/scraper/request";

    // Ready-to-use request bodies (actor + input) for each search vertical, as raw JSON.
    private static final Map<String, String> SAMPLE_BODIES = Map.of(
        "web", """
            { "actor": "scraper.google.search", "input": {
                "q": "coffee",
                "hl": "en",
                "gl": "us",
                "google_domain": "google.com"
            }}""",
        "images", """
            { "actor": "scraper.google.search", "input": {
                "q": "Apple Iphone16",
                "hl": "en",
                "gl": "us",
                "google_domain": "google.com",
                "tbm": "isch"
            }}""",
        "local", """
            { "actor": "scraper.google.search", "input": {
                "q": "Coffee",
                "hl": "en",
                "gl": "us",
                "google_domain": "google.com",
                "tbm": "lcl"
            }}""",
        "video", """
            { "actor": "scraper.google.search", "input": {
                "q": "Coffee",
                "google_domain": "google.com",
                "start": 0,
                "num": 10,
                "tbm": "vid"
            }}""",
        "shopping", """
            { "actor": "scraper.google.search", "input": {
                "q": "Coffee",
                "google_domain": "google.com",
                "start": 0,
                "num": 10,
                "tbm": "shop"
            }}"""
    );

    public static void main(String[] args) throws Exception {
        String vertical = args.length > 0 ? args[0] : "web";
        String body = SAMPLE_BODIES.get(vertical);
        if (body == null) {
            System.out.println("Unknown vertical '" + vertical + "'. Choose one of: web, images, local, video, shopping");
            System.exit(1);
        }

        String apiToken = System.getenv().getOrDefault("SCRAPELESS_API_TOKEN", "YOUR_API_TOKEN");

        HttpClient client = HttpClient.newBuilder()
                .connectTimeout(Duration.ofSeconds(30))
                .build();

        HttpRequest request = HttpRequest.newBuilder()
                .uri(URI.create(API_URL))
                .timeout(Duration.ofSeconds(180))
                .header("Content-Type", "application/json")
                .header("x-api-token", apiToken)
                .POST(HttpRequest.BodyPublishers.ofString(body))
                .build();

        HttpResponse<String> response = client.send(request, HttpResponse.BodyHandlers.ofString());

        // The Scraping API distinguishes scenarios by HTTP status code.
        switch (response.statusCode()) {
            case 200 -> // Synchronous success: the body is the scraped SERP data.
                System.out.println("[200] Success — '" + vertical + "' results received.\n" + response.body());
            case 201 -> // Task accepted but still running; retrieve later by task id (see docs).
                System.out.println("[201] Task in progress (async). Body:\n" + response.body());
            case 400 -> // Scraping failed — inspect code/message in the body.
                System.out.println("[400] Bad request (scraping failed). Body:\n" + response.body());
            default -> {
                System.out.println("[" + response.statusCode() + "] Unexpected response:\n" + response.body());
                System.exit(1);
            }
        }
    }
}
