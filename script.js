document.addEventListener('DOMContentLoaded', () => {

    const scrapeForm = document.getElementById('scrapeForm');
    const urlInput = document.getElementById('urlInput');
    const scrapeBtn = document.getElementById('scrapeBtn');
    const btnText = scrapeBtn.querySelector('.btn-text');
    const spinner = scrapeBtn.querySelector('.spinner');
    const errorMessage = document.getElementById('errorMessage');

    const resultsSection = document.getElementById('resultsSection');
    const resultsList = document.getElementById('resultsList');
    const countValue = document.getElementById('countValue');

    const siteInfo = document.getElementById('siteInfo');
    const siteFavicon = document.getElementById('siteFavicon');
    const siteDomain = document.getElementById('siteDomain');

    urlInput.addEventListener('input', (e) => {
        const raw = e.target.value.trim();

        if (!raw) {
            siteInfo.classList.add('hidden');
            return;
        }

        try {
            let fullUrl = raw;

            if (!/^https?:\/\//i.test(fullUrl)) {
                fullUrl = "https://" + fullUrl;
            }

            const obj = new URL(fullUrl);
            const domain = obj.hostname;

            siteDomain.textContent = domain;
            siteFavicon.src = `https://www.google.com/s2/favicons?domain=${domain}&sz=64`;
            siteInfo.href = obj.origin;

            siteInfo.classList.remove('hidden');

        } catch {
            siteInfo.classList.add('hidden');
        }
    });

    scrapeForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const url = urlInput.value.trim();
        if (!url) return;

        errorMessage.classList.add('hidden');
        resultsSection.classList.add('hidden');
        resultsList.innerHTML = '';
        countValue.textContent = "0";

        setLoading(true);

        try {
            // Sử dụng đường dẫn tương đối để tránh lỗi sai tên thư mục
            const apiURL = `api/api.php?url=${encodeURIComponent(url)}`;
            // const apiURL = `http://localhost/duanqttt/api/api.php?url=${encodeURIComponent(url)}`;

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

            // ⭐ TỰ ĐỘNG BẬT TAB PRODUCTS + LOAD DATA MỚI ⭐
            setTimeout(() => {
                window.postMessage({ action: "refreshProducts" }, "*");
            }, 300);

        } catch (err) {
            errorMessage.textContent = err.message;
            errorMessage.classList.remove('hidden');
        }

        setLoading(false);
    });

    function setLoading(state) {
        if (state) {
            scrapeBtn.disabled = true;
            urlInput.disabled = true;
            btnText.textContent = "Processing...";
            spinner.classList.remove("hidden");
        } else {
            scrapeBtn.disabled = false;
            urlInput.disabled = false;
            btnText.textContent = "Start Scraping";
            spinner.classList.add("hidden");
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
        resultsSection.classList.remove('hidden');

        if (!Array.isArray(list)) {
            resultsList.innerHTML = `<p class="error-text">Invalid data format received.</p>`;
            return;
        }

        countValue.textContent = list.length;

        if (list.length === 0) {
            resultsList.innerHTML = `<p style="grid-column:1/-1;text-align:center;">Không có sản phẩm nào.</p>`;
            return;
        }

        const html = list.map(item => `
            <div class="product-card">
                <div class="product-image-container">
                    <img src="${item.image || 'https://placehold.co/300x300'}"
                         class="product-image"
                         onerror="this.src='https://placehold.co/300x300?text=No+Image'">
                </div>

                <div class="product-info">
                    <h3 class="product-title">${item.title}</h3>

                    <div class="price-box">
                        <span class="current-price">${formatCurrency(item.price_new)}</span>
                        ${item.price_old ? `<span class="old-price">${formatCurrency(item.price_old)}</span>` : ""}
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
