document.addEventListener('DOMContentLoaded', () => {

    const scrapeForm = document.getElementById('scrapeForm');
    const urlInput = document.getElementById('urlInput');
    const scrapeBtn = document.getElementById('scrapeBtn');
    // Kiểm tra null để tránh lỗi nếu không tìm thấy element con
    const btnText = scrapeBtn ? scrapeBtn.querySelector('.btn-text') : null;
    const spinner = scrapeBtn ? scrapeBtn.querySelector('.spinner') : null;
    const errorMessage = document.getElementById('errorMessage');

    const resultsSection = document.getElementById('resultsSection');
    const resultsList = document.getElementById('resultsList');
    const countValue = document.getElementById('countValue');

    const siteInfo = document.getElementById('siteInfo');
    const siteFavicon = document.getElementById('siteFavicon');
    const siteDomain = document.getElementById('siteDomain');

    if (urlInput) {
        urlInput.addEventListener('input', (e) => {
            const raw = e.target.value.trim();

            if (!raw || !siteInfo) {
                if (siteInfo) siteInfo.classList.add('hidden');
                return;
            }

            try {
                let fullUrl = raw;
                if (!/^https?:\/\//i.test(fullUrl)) {
                    fullUrl = "https://" + fullUrl;
                }

                const obj = new URL(fullUrl);
                const domain = obj.hostname;

                if (siteDomain) siteDomain.textContent = domain;
                if (siteFavicon) siteFavicon.src = `https://www.google.com/s2/favicons?domain=${domain}&sz=64`;
                if (siteInfo) {
                    siteInfo.href = obj.origin;
                    siteInfo.classList.remove('hidden');
                }

            } catch {
                if (siteInfo) siteInfo.classList.add('hidden');
            }
        });
    }

    if (scrapeForm) {
        scrapeForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const url = urlInput.value.trim();
            if (!url) return;

            if (errorMessage) errorMessage.classList.add('hidden');
            if (resultsSection) resultsSection.classList.add('hidden');
            if (resultsList) resultsList.innerHTML = '';
            if (countValue) countValue.textContent = "0";

            setLoading(true);

            try {
                // [MODIFIED] Cấu hình đường dẫn API linh động
                // Nếu có biến global API_BASE_URL (từ Admin) thì dùng, ngược lại dùng mặc định
                const baseUrl = window.API_BASE_URL || 'api/api.php';
                const apiURL = `${baseUrl}?url=${encodeURIComponent(url)}`;

                const response = await fetch(apiURL);

                if (!response.ok) {
                    throw new Error(`Connection Error: ${response.status} ${response.statusText}`);
                }

                const raw = await response.text();
                let data;

                try {
                    data = JSON.parse(raw);
                } catch (e) {
                    console.log("❌ RAW backend:", raw);
                    throw new Error("Server returned invalid JSON");
                }

                if (data.error) {
                    throw new Error(data.error);
                }

                displayResult(data);

                // Gửi tín hiệu refresh (nếu cần)
                setTimeout(() => {
                    window.postMessage({ action: "refreshProducts" }, "*");
                }, 300);

            } catch (err) {
                if (errorMessage) {
                    errorMessage.textContent = err.message;
                    errorMessage.classList.remove('hidden');
                } else {
                    alert(err.message);
                }
            }

            setLoading(false);
        });
    }

    function setLoading(state) {
        if (!scrapeBtn) return;
        
        if (state) {
            scrapeBtn.disabled = true;
            if (urlInput) urlInput.disabled = true;
            if (btnText) btnText.textContent = "Processing...";
            if (spinner) spinner.classList.remove("hidden");
        } else {
            scrapeBtn.disabled = false;
            if (urlInput) urlInput.disabled = false;
            if (btnText) btnText.textContent = "Start Scraping";
            if (spinner) spinner.classList.add("hidden");
        }
    }

    function formatCurrency(v) {
        // Nếu giá trị là 0, null, undefined hoặc rỗng thì trả về "Liên hệ"
        if (!v || v == 0) return "Liên hệ";
        return new Intl.NumberFormat("vi-VN", {
            style: "currency",
            currency: "VND",
        }).format(v);
    }

    function displayResult(list) {
        if (!resultsSection || !resultsList) return;
        
        resultsSection.classList.remove('hidden');

        if (!Array.isArray(list)) {
            resultsList.innerHTML = `<p class="error-text">Invalid data format received.</p>`;
            return;
        }

        if (countValue) countValue.textContent = list.length;

        if (list.length === 0) {
            resultsList.innerHTML = `<p style="grid-column:1/-1;text-align:center;">Không có sản phẩm nào.</p>`;
            return;
        }

        const html = list.map(item => `
            <div class="product-card">
                <div class="product-image-container">
                    <img src="${item.image || 'https://placehold.co/300x300'}"
                         class="product-image"
                         style="width:100%; height:100%; object-fit:contain;" 
                         onerror="this.src='https://placehold.co/300x300?text=No+Image'">
                </div>

                <div class="product-info">
                    <h3 class="product-title" style="font-size:0.9rem; margin-bottom:5px;">${item.title}</h3>

                    <div class="price-box">
                        <span class="current-price" style="color:#e91e63; font-weight:bold;">
                            ${formatCurrency(item.price_new)}
                        </span>
                    </div>
                </div>

                <a href="${item.link}" target="_blank" class="view-btn">
                    View Product
                </a>
            </div>
        `).join("");

        resultsList.innerHTML = html;
    }

});

/* =========================================
   AUTO CONTRAST BACKGROUND ALGORITHM
   ========================================= */
document.addEventListener("DOMContentLoaded", () => {
    const bgElement = document.querySelector('.random-background');
    
    if (bgElement) {
        setSmartRandomBackground(bgElement);
    }
});

// ... (Phần code cũ giữ nguyên)

async function setSmartRandomBackground(element) {
    try {
        // --- SỬA ĐỔI BẮT ĐẦU ---
        // Tự động tính toán đường dẫn dựa trên thư mục hiện tại
        let apiPath = 'api/random_bg.php';
        const currentPath = window.location.pathname;

        // Nếu đang đứng trong thư mục con (auth, admin, pages) thì lùi ra 1 cấp
        if (currentPath.includes('/auth/') || 
            currentPath.includes('/admin/') || 
            currentPath.includes('/pages/')) {
            apiPath = '../api/random_bg.php';
        }
        // --- SỬA ĐỔI KẾT THÚC ---

        // 1. Gọi API lấy ảnh (dùng đường dẫn vừa tính toán)
        const response = await fetch(apiPath + '?t=' + new Date().getTime());
        
        if (!response.ok) throw new Error('Failed to fetch image');

        // 2. Chuyển đổi ảnh thành Blob URL
        const blob = await response.blob();
        const imgUrl = URL.createObjectURL(blob);

        // 3. Tạo đối tượng ảnh ảo để phân tích
        const img = new Image();
        img.crossOrigin = "Anonymous";
        img.src = imgUrl;

        img.onload = function() {
            // 4. Cập nhật hình nền
            element.style.backgroundImage = `url('${imgUrl}')`;

            // 5. Thuật toán phân tích màu (Vẽ lên canvas 1x1 pixel)
            const canvas = document.createElement('canvas');
            canvas.width = 1;
            canvas.height = 1;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(img, 0, 0, 1, 1);

            // Lấy dữ liệu pixel
            const [r, g, b] = ctx.getImageData(0, 0, 1, 1).data;

            // 6. Tính độ sáng (Luminance)
            const brightness = (r * 299 + g * 587 + b * 114) / 1000;

            // 7. Ra quyết định màu chữ
            if (brightness > 140) {
                // NỀN SÁNG -> Chữ Đen
                document.body.style.setProperty('--dynamic-text-color', '#111827'); 
                document.body.classList.add('is-bright-bg');
                
                const logo = document.querySelector('.logo-image');
                if(logo) logo.style.filter = "invert(1)"; 
                
            } else {
                // NỀN TỐI -> Chữ Trắng
                document.body.style.setProperty('--dynamic-text-color', '#ffffff');
                document.body.classList.remove('is-bright-bg');
                
                const logo = document.querySelector('.logo-image');
                if(logo) logo.style.filter = "invert(0)";
            }
        };

    } catch (error) {
        console.error("Lỗi tải hình nền:", error);
        // Fallback: Nền tối mặc định
        element.style.backgroundColor = "#222";
        element.style.setProperty('--dynamic-text-color', '#ffffff');
    }
}