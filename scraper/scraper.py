# File: duanqttt/scraper/scraper.py

import os
# --- TẮT LOG RÁC CỦA DRIVER ---
os.environ['WDM_LOG'] = '0'
os.environ['WDM_PRINT_FIRST_LINE'] = 'False'

import sys
import io
import json
import logging
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.chrome.service import Service
from webdriver_manager.chrome import ChromeDriverManager

# Import hàm xử lý bóc tách dữ liệu
from scraper_beauti import extract_products

# Cấu hình logging ra stderr để không lẫn vào kết quả JSON trả về PHP
logging.basicConfig(stream=sys.stderr, level=logging.INFO)

# Ép UTF-8 cho output
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
sys.stderr = io.TextIOWrapper(sys.stderr.buffer, encoding='utf-8')

REQUEST_TIMEOUT = 30
WAIT_TIME = 10

def get_driver():
    chrome_options = Options()
    chrome_options.add_argument('--headless') # Chạy ẩn không hiện trình duyệt
    chrome_options.add_argument('--no-sandbox')
    chrome_options.add_argument('--disable-dev-shm-usage')
    chrome_options.add_argument('user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36')
    chrome_options.add_experimental_option("excludeSwitches", ["enable-logging"])
    
    service = Service(ChromeDriverManager().install())
    driver = webdriver.Chrome(service=service, options=chrome_options)
    driver.set_page_load_timeout(REQUEST_TIMEOUT)
    return driver

def scrape_page(url):
    driver = None
    try:
        driver = get_driver()
        logging.info(f"Loading URL: {url}")
        driver.get(url)
        
        # Chờ thẻ body load xong
        WebDriverWait(driver, WAIT_TIME).until(
            EC.presence_of_element_located((By.TAG_NAME, "body"))
        )
        
        # Lấy toàn bộ HTML source
        page_source = driver.page_source
        
        # Gọi hàm bóc tách dữ liệu
        results = extract_products(page_source, url)
        
        logging.info(f"Scraped {len(results)} products successfully.")
        return results

    except Exception as e:
        logging.error(f"Scrape Error: {e}")
        # Trả về lỗi JSON nếu có sự cố
        print(json.dumps({"error": str(e)}))
        sys.exit(1)
    finally:
        if driver:
            driver.quit()

if __name__ == "__main__":
    input_url = sys.argv[1] if len(sys.argv) > 1 else ""
    if not input_url:
        print(json.dumps({"error": "No URL provided"}))
    else:
        data = scrape_page(input_url)
        # In kết quả JSON ra stdout cho PHP nhận
        print(json.dumps(data, ensure_ascii=False))