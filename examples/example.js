/**
 * Google Scraper — Scrapeless Scraping API (Node.js example)
 *
 * Docs:  https://apidocs.scrapeless.com/doc-800321
 *        https://apidocs.scrapeless.com/doc-1275927
 * Token: https://app.scrapeless.com/passport/login?redirect=/quick-start
 *
 * The Google actor (`scraper.google.search`) selects a search vertical via the
 * `tbm` input field:
 *   web (default) | images (isch) | local (lcl) | video (vid) | shopping (shop) | news (nws)
 *
 * Run (Node.js 18+, uses the built-in fetch):
 *   export SCRAPELESS_API_TOKEN="your_api_token"
 *   node example.js            # defaults to the "web" vertical
 *   node example.js images     # or: web | images | local | video | shopping
 */

const API_URL = "https://api.scrapeless.com/api/v1/scraper/request";
const API_TOKEN = process.env.SCRAPELESS_API_TOKEN || "YOUR_API_TOKEN";

// Ready-to-use input payloads for each search vertical.
const SAMPLE_INPUTS = {
  web: {
    q: "coffee",
    hl: "en",
    gl: "us",
    google_domain: "google.com",
  },
  images: {
    q: "Apple Iphone16",
    hl: "en",
    gl: "us",
    google_domain: "google.com",
    tbm: "isch",
  },
  local: {
    q: "Coffee",
    hl: "en",
    gl: "us",
    google_domain: "google.com",
    tbm: "lcl",
  },
  video: {
    q: "Coffee",
    google_domain: "google.com",
    start: 0,
    num: 10,
    tbm: "vid",
  },
  shopping: {
    q: "Coffee",
    google_domain: "google.com",
    start: 0,
    num: 10,
    tbm: "shop",
  },
};

async function scrape(vertical) {
  const input = SAMPLE_INPUTS[vertical];
  if (!input) {
    throw new Error(`Unknown vertical '${vertical}'. Choose one of: ${Object.keys(SAMPLE_INPUTS).join(", ")}`);
  }

  const response = await fetch(API_URL, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "x-api-token": API_TOKEN,
    },
    body: JSON.stringify({ actor: "scraper.google.search", input }),
  });

  // The Scraping API distinguishes scenarios by HTTP status code.
  switch (response.status) {
    case 200: {
      // Synchronous success: the body is the scraped SERP data.
      const data = await response.json();
      console.log(`[200] Success — '${vertical}' results received.`);
      if (data && typeof data === "object") {
        console.log(`  top-level keys: ${Object.keys(data).slice(0, 12).join(", ")}`);
      }
      console.log("\n  Raw response (truncated to 1500 chars):");
      console.log("  " + JSON.stringify(data).slice(0, 1500));
      return data;
    }
    case 201: {
      // Task accepted but still running. Retrieve it later by task id
      // (async retrieval / webhook — see the official documentation).
      const body = await response.json();
      console.log(`[201] Task in progress — message: ${body.message}, taskId: ${body.taskId}`);
      console.log("      Fetch the result later using the task id (see docs).");
      return body;
    }
    case 400: {
      // Scraping failed — inspect the error code and message.
      const body = await response.json();
      console.log(`[400] Bad request — code: ${body.code}, message: ${body.message}`);
      return body;
    }
    default: {
      const text = await response.text();
      console.log(`[${response.status}] Unexpected response:\n${text}`);
      throw new Error(`Unexpected status ${response.status}`);
    }
  }
}

const vertical = process.argv[2] || "web";
scrape(vertical).catch((err) => {
  console.error(err.message);
  process.exit(1);
});
