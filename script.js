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
        if (!v) return "";
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