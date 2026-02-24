from playwright.sync_api import sync_playwright

def run():
    print("Starting browser...")
    with sync_playwright() as p:
        browser = p.chromium.launch()
        page = browser.new_page()
        print("Navigating...")
        page.goto("http://localhost:8000")

        # Wait for chart
        page.wait_for_selector("#chartCoord")

        # Wait a bit for charts to render (canvas animation)
        page.wait_for_timeout(2000)

        # Take screenshot
        print("Taking screenshot...")
        page.screenshot(path="dashboard.png", full_page=True)
        browser.close()
        print("Done.")

if __name__ == "__main__":
    run()
