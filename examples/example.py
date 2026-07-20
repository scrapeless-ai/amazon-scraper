"""
Google Scraper — Scrapeless Scraping API (Python example)

Docs:  https://apidocs.scrapeless.com/doc-800321
       https://apidocs.scrapeless.com/doc-1275927
Token: https://app.scrapeless.com/passport/login?redirect=/quick-start

The Google actor (`scraper.google.search`) selects a search vertical via the
`tbm` input field:
  web (default) | images (isch) | local (lcl) | video (vid) | shopping (shop) | news (nws)

Run:
    export SCRAPELESS_API_TOKEN="your_api_token"
    pip install requests
    python example.py            # defaults to the "web" vertical
    python example.py images     # or: web | images | local | video | shopping
"""

import os
import sys
import json
import requests

API_URL = "https://api.scrapeless.com/api/v1/scraper/request"
API_TOKEN = os.environ.get("SCRAPELESS_API_TOKEN", "YOUR_API_TOKEN")

# Ready-to-use input payloads for each search vertical.
SAMPLE_INPUTS = {
    "web": {
        "q": "coffee",
        "hl": "en",
        "gl": "us",
        "google_domain": "google.com",
    },
    "images": {
        "q": "Apple Iphone16",
        "hl": "en",
        "gl": "us",
        "google_domain": "google.com",
        "tbm": "isch",
    },
    "local": {
        "q": "Coffee",
        "hl": "en",
        "gl": "us",
        "google_domain": "google.com",
        "tbm": "lcl",
    },
    "video": {
        "q": "Coffee",
        "google_domain": "google.com",
        "start": 0,
        "num": 10,
        "tbm": "vid",
    },
    "shopping": {
        "q": "Coffee",
        "google_domain": "google.com",
        "start": 0,
        "num": 10,
        "tbm": "shop",
    },
}


def scrape(vertical: str):
    if vertical not in SAMPLE_INPUTS:
        raise SystemExit(f"Unknown vertical '{vertical}'. Choose one of: {', '.join(SAMPLE_INPUTS)}")

    payload = {"actor": "scraper.google.search", "input": SAMPLE_INPUTS[vertical]}
    headers = {"Content-Type": "application/json", "x-api-token": API_TOKEN}

    response = requests.post(API_URL, headers=headers, json=payload, timeout=180)

    # The Scraping API distinguishes scenarios by HTTP status code.
    if response.status_code == 200:
        # Synchronous success: the body is the scraped SERP data.
        data = response.json()
        print(f"[200] Success — '{vertical}' results received.")
        if isinstance(data, dict):
            print(f"  top-level keys: {list(data)[:12]}")
        print("\n  Raw response (truncated to 1500 chars):")
        print("  " + json.dumps(data, ensure_ascii=False)[:1500])
        return data

    if response.status_code == 201:
        # Task accepted but still running. Retrieve it later by task id
        # (async retrieval / webhook — see the official documentation).
        body = response.json()
        print(f"[201] Task in progress — message: {body.get('message')}, taskId: {body.get('taskId')}")
        print("      Fetch the result later using the task id (see docs).")
        return body

    if response.status_code == 400:
        # Scraping failed — inspect the error code and message.
        body = response.json()
        print(f"[400] Bad request — code: {body.get('code')}, message: {body.get('message')}")
        return body

    # Any other status: surface it for debugging.
    print(f"[{response.status_code}] Unexpected response:\n{response.text}")
    response.raise_for_status()


if __name__ == "__main__":
    scrape(sys.argv[1] if len(sys.argv) > 1 else "web")
