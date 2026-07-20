// Google Scraper — Scrapeless Scraping API (Go example)
//
// Docs:  https://apidocs.scrapeless.com/doc-800321
//
//	https://apidocs.scrapeless.com/doc-1275927
//
// Token: https://app.scrapeless.com/passport/login?redirect=/quick-start
//
// The Google actor (scraper.google.search) selects a search vertical via the
// tbm input field:
//
//	web (default) | images (isch) | local (lcl) | video (vid) | shopping (shop) | news (nws)
//
// Run:
//
//	export SCRAPELESS_API_TOKEN="your_api_token"
//	go run example.go            # defaults to the "web" vertical
//	go run example.go images     # or: web | images | local | video | shopping
package main

import (
	"bytes"
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"os"
	"time"
)

const apiURL = "https://api.scrapeless.com/api/v1/scraper/request"

// sampleInputs holds ready-to-use input payloads for each search vertical.
var sampleInputs = map[string]map[string]any{
	"web": {
		"q":             "coffee",
		"hl":            "en",
		"gl":            "us",
		"google_domain": "google.com",
	},
	"images": {
		"q":             "Apple Iphone16",
		"hl":            "en",
		"gl":            "us",
		"google_domain": "google.com",
		"tbm":           "isch",
	},
	"local": {
		"q":             "Coffee",
		"hl":            "en",
		"gl":            "us",
		"google_domain": "google.com",
		"tbm":           "lcl",
	},
	"video": {
		"q":             "Coffee",
		"google_domain": "google.com",
		"start":         0,
		"num":           10,
		"tbm":           "vid",
	},
	"shopping": {
		"q":             "Coffee",
		"google_domain": "google.com",
		"start":         0,
		"num":           10,
		"tbm":           "shop",
	},
}

func main() {
	vertical := "web"
	if len(os.Args) > 1 {
		vertical = os.Args[1]
	}

	input, ok := sampleInputs[vertical]
	if !ok {
		fmt.Printf("Unknown vertical %q. Choose one of: web, images, local, video, shopping\n", vertical)
		os.Exit(1)
	}

	apiToken := os.Getenv("SCRAPELESS_API_TOKEN")
	if apiToken == "" {
		apiToken = "YOUR_API_TOKEN"
	}

	body, err := json.Marshal(map[string]any{"actor": "scraper.google.search", "input": input})
	if err != nil {
		panic(err)
	}

	req, err := http.NewRequest(http.MethodPost, apiURL, bytes.NewReader(body))
	if err != nil {
		panic(err)
	}
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("x-api-token", apiToken)

	client := &http.Client{Timeout: 180 * time.Second}
	resp, err := client.Do(req)
	if err != nil {
		panic(err)
	}
	defer resp.Body.Close()

	raw, err := io.ReadAll(resp.Body)
	if err != nil {
		panic(err)
	}

	// The Scraping API distinguishes scenarios by HTTP status code.
	switch resp.StatusCode {
	case http.StatusOK: // 200 — synchronous success, body is the scraped SERP data.
		fmt.Printf("[200] Success — '%s' results received.\n", vertical)
		preview := raw
		if len(preview) > 1500 {
			preview = preview[:1500]
		}
		fmt.Printf("\n  Raw response (truncated to 1500 chars):\n  %s\n", preview)

	case http.StatusCreated: // 201 — task accepted, still running.
		var task struct {
			Message string `json:"message"`
			TaskID  string `json:"taskId"`
		}
		_ = json.Unmarshal(raw, &task)
		fmt.Printf("[201] Task in progress — message: %s, taskId: %s\n", task.Message, task.TaskID)
		fmt.Println("      Fetch the result later using the task id (see docs).")

	case http.StatusBadRequest: // 400 — scraping failed.
		var e struct {
			Code    int    `json:"code"`
			Message string `json:"message"`
		}
		_ = json.Unmarshal(raw, &e)
		fmt.Printf("[400] Bad request — code: %d, message: %s\n", e.Code, e.Message)

	default:
		fmt.Printf("[%d] Unexpected response:\n%s\n", resp.StatusCode, raw)
		os.Exit(1)
	}
}
