# File: duanqttt/scraper/scraper_beauti.py

from bs4 import BeautifulSoup
from urllib.parse import urljoin
import logging
import re

def clean_price_value(text):
    """Hàm phụ trợ: Làm sạch chuỗi giá (vd: '2.090.000đ' -> 2090000)"""
    if not text:
        return None
    # Chỉ giữ lại số
    cleaned = re.sub(r"[^\d]", "", text)
    try:
        return int(cleaned) if cleaned else None
    except ValueError:
        return None

def extract_products(html_content, base_url):
    """
    Phân tích HTML. Logic này đang tối ưu cho các site như SieuThiYTe
    """
    results = []
    soup = BeautifulSoup(html_content, 'html.parser')
    
    # Tìm các thẻ chứa sản phẩm
    product_items = soup.select("div.item-slide")
    
    logging.info(f"Found {len(product_items)} item-slide elements.")

    for item in product_items:
        try:
            # 1. LẤY LINK
            link_tag = item.find('a', href=True)
            if not link_tag:
                continue
            product_link = urljoin(base_url, link_tag['href'])
            
            # 2. LẤY TIÊU ĐỀ
            title_tag = item.select_one("h3.title")
            title = title_tag.get_text(strip=True) if title_tag else "No Title"

            # 3. LẤY ẢNH
            img_tag = item.select_one("div.img img")
            image_url = None
            if img_tag:
                # Ưu tiên lấy ảnh gốc từ lazyload nếu có
                raw_img_url = (
                    img_tag.get('data-original') 
                    or img_tag.get('data-src') 
                    or img_tag.get('src')
                )
                if raw_img_url:
                    image_url = urljoin(base_url, raw_img_url)

            # 4. LẤY GIÁ
            price_old = None
            price_new = None
            discount = None

            price_tag = item.select_one("p.price")
            if price_tag:
                # Giá cũ (gạch ngang)
                del_tag = price_tag.select_one("del")
                if del_tag:
                    price_old = clean_price_value(del_tag.get_text())
                
                # Giá mới: Lấy toàn bộ text và tìm số nhỏ nhất
                full_price_text = price_tag.get_text(strip=True)
                prices_found = re.findall(r"[\d\.]+", full_price_text)
                prices_int = [clean_price_value(p) for p in prices_found if clean_price_value(p)]
                
                if len(prices_int) >= 1:
                    price_new = min(prices_int) 
                    if len(prices_int) > 1:
                         potential_old = max(prices_int)
                         if potential_old > price_new:
                             price_old = potential_old

            # Tính phần trăm giảm giá tự động nếu web không ghi
            if price_old and price_new and price_old > price_new:
                pct = int(((price_old - price_new) / price_old) * 100)
                discount = f"-{pct}%"

            results.append({
                "title": title,
                "link": product_link,
                "image": image_url,
                "price_old": price_old,
                "price_new": price_new,
                "discount": discount
            })

        except Exception as e:
            logging.error(f"Error parsing item: {e}")
            continue

    return results