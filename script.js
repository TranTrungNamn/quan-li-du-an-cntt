document.addEventListener("DOMContentLoaded", () => {
  // Chỉ áp dụng chế độ "Ứng dụng" (App Mode) nếu đang ở trang chủ (có chứa tab-scraper)
  if (document.getElementById("tab-scraper")) {
    document.body.classList.add("app-mode");
  }
});

document.addEventListener("DOMContentLoaded", () => {
  const scrapeForm = document.getElementById("scrapeForm");
  const urlInput = document.getElementById("urlInput");
  const scrapeBtn = document.getElementById("scrapeBtn");
  // Kiểm tra null để tránh lỗi nếu không tìm thấy element con
  const btnText = scrapeBtn ? scrapeBtn.querySelector(".btn-text") : null;
  const spinner = scrapeBtn ? scrapeBtn.querySelector(".spinner") : null;
  const errorMessage = document.getElementById("errorMessage");

  const resultsSection = document.getElementById("resultsSection");
  const resultsList = document.getElementById("resultsList");
  const countValue = document.getElementById("countValue");

  const siteInfo = document.getElementById("siteInfo");
  const siteFavicon = document.getElementById("siteFavicon");
  const siteDomain = document.getElementById("siteDomain");

  if (urlInput) {
    urlInput.addEventListener("input", (e) => {
      const raw = e.target.value.trim();

      if (!raw || !siteInfo) {
        if (siteInfo) siteInfo.classList.add("hidden");
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
        if (siteFavicon)
          siteFavicon.src = `https://www.google.com/s2/favicons?domain=${domain}&sz=64`;
        if (siteInfo) {
          siteInfo.href = obj.origin;
          siteInfo.classList.remove("hidden");
        }
      } catch {
        if (siteInfo) siteInfo.classList.add("hidden");
      }
    });
  }

  if (scrapeForm) {
    scrapeForm.addEventListener("submit", async (e) => {
      e.preventDefault();

      const url = urlInput.value.trim();
      if (!url) return;

      if (errorMessage) errorMessage.classList.add("hidden");
      if (resultsSection) resultsSection.classList.add("hidden");
      if (resultsList) resultsList.innerHTML = "";
      if (countValue) countValue.textContent = "0";

      setLoading(true);

      try {
        // [MODIFIED] Cấu hình đường dẫn API linh động
        // Nếu có biến global API_BASE_URL (từ Admin) thì dùng, ngược lại dùng mặc định
        const baseUrl = window.API_BASE_URL || "api/api.php";
        const apiURL = `${baseUrl}?url=${encodeURIComponent(url)}`;

        const response = await fetch(apiURL);

        if (!response.ok) {
          throw new Error(
            `Connection Error: ${response.status} ${response.statusText}`
          );
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

        // // Gửi tín hiệu refresh (nếu cần)
        // setTimeout(() => {
        //     window.postMessage({ action: "refreshProducts" }, "*");
        // }, 300);
      } catch (err) {
        if (errorMessage) {
          errorMessage.textContent = err.message;
          errorMessage.classList.remove("hidden");
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
    // Nếu giá trị là 0, null, undefined hoặc rỗng thì trả về "Contact"
    if (!v || v == 0) return "Contact";
    return new Intl.NumberFormat("vi-VN", {
      style: "currency",
      currency: "VND",
    }).format(v);
  }
  function displayResult(list) {
    if (!resultsSection || !resultsList) return;

    resultsSection.classList.remove("hidden");

    if (!Array.isArray(list)) {
      resultsList.innerHTML = `<p class="error-text">Invalid data format received.</p>`;
      return;
    }

    if (countValue) countValue.textContent = list.length;

    if (list.length === 0) {
      resultsList.innerHTML = `<p style="grid-column:1/-1;text-align:center;">Không có sản phẩm nào.</p>`;
      return;
    }

    // [MODIFIED] Cấu trúc HTML đồng bộ với products.php
    const html = list
      .map((item) => {
        // Tính toán discount giả lập (nếu có)
        let discountHtml = "";
        if (item.price_original > item.price_new && item.price_new > 0) {
          const percent = Math.round(
            ((item.price_original - item.price_new) / item.price_original) * 100
          );
          if (percent > 0) {
            discountHtml = `<span class="tag-discount">-${percent}%</span>`;
          }
        }

        // Hiển thị giá cũ (nếu có)
        let oldPriceHtml = "";
        if (item.price_original > item.price_new) {
          oldPriceHtml = `<span class="price-old">${formatCurrency(
            item.price_original
          )}</span>`;
        }

        return `
            <div class="product-card">
                
                <div class="card-image-wrap">
                    <img src="${item.image || "https://placehold.co/300x300"}"
                         onerror="this.src='https://placehold.co/300x300?text=No+Image'">
                    ${discountHtml}
                </div>

                <div class="card-body">
                    <span class="tag-platform" style="background:#dbeafe; color:#1e40af">Scraped Item</span>

                    <h3 class="product-title" title="${item.title}">
                        ${item.title}
                    </h3>

                    <div class="price-row">
                        <span class="price-current">
                            ${formatCurrency(item.price_new)}
                        </span>
                        ${oldPriceHtml}
                    </div>
                </div>

                <div class="card-footer">
                    <a href="${item.link}" target="_blank" class="btn-view">
                        View Product
                    </a>
                </div>

            </div>
            `;
      })
      .join("");

    resultsList.innerHTML = html;
  }
});

/* =========================================
   AUTO CONTRAST BACKGROUND ALGORITHM
   ========================================= */
document.addEventListener("DOMContentLoaded", () => {
  const bgElement = document.querySelector(".random-background");

  if (bgElement) {
    setSmartRandomBackground(bgElement);
  }
});

// ... (Phần code cũ giữ nguyên)

async function setSmartRandomBackground(element) {
  try {
    // --- SỬA ĐỔI BẮT ĐẦU ---
    // Tự động tính toán đường dẫn dựa trên thư mục hiện tại
    let apiPath = "api/random_bg.php";
    const currentPath = window.location.pathname;

    // Nếu đang đứng trong thư mục con (auth, admin, pages) thì lùi ra 1 cấp
    if (
      currentPath.includes("/auth/") ||
      currentPath.includes("/admin/") ||
      currentPath.includes("/pages/")
    ) {
      apiPath = "../api/random_bg.php";
    }
    // --- SỬA ĐỔI KẾT THÚC ---

    // 1. Gọi API lấy ảnh (dùng đường dẫn vừa tính toán)
    const response = await fetch(apiPath + "?t=" + new Date().getTime());

    if (!response.ok) throw new Error("Failed to fetch image");

    // 2. Chuyển đổi ảnh thành Blob URL
    const blob = await response.blob();
    const imgUrl = URL.createObjectURL(blob);

    // 3. Tạo đối tượng ảnh ảo để phân tích
    const img = new Image();
    img.crossOrigin = "Anonymous";
    img.src = imgUrl;

    img.onload = function () {
      // 4. Cập nhật hình nền
      element.style.backgroundImage = `url('${imgUrl}')`;

      // 5. Thuật toán phân tích màu (Vẽ lên canvas 1x1 pixel)
      const canvas = document.createElement("canvas");
      canvas.width = 1;
      canvas.height = 1;
      const ctx = canvas.getContext("2d");
      ctx.drawImage(img, 0, 0, 1, 1);

      // Lấy dữ liệu pixel
      const [r, g, b] = ctx.getImageData(0, 0, 1, 1).data;

      // 6. Tính độ sáng (Luminance)
      const brightness = (r * 299 + g * 587 + b * 114) / 1000;

      // 7. Ra quyết định màu chữ
      if (brightness > 140) {
        // NỀN SÁNG -> Chữ Đen
        document.body.style.setProperty("--dynamic-text-color", "#111827");
        document.body.classList.add("is-bright-bg");

        const logo = document.querySelector(".logo-image");
        if (logo) logo.style.filter = "invert(1)";
      } else {
        // NỀN TỐI -> Chữ Trắng
        document.body.style.setProperty("--dynamic-text-color", "#ffffff");
        document.body.classList.remove("is-bright-bg");

        const logo = document.querySelector(".logo-image");
        if (logo) logo.style.filter = "invert(0)";
      }
    };
  } catch (error) {
    console.error("Lỗi tải hình nền:", error);
    // Fallback: Nền tối mặc định
    element.style.backgroundColor = "#222";
    element.style.setProperty("--dynamic-text-color", "#ffffff");
  }
}

// Hàm hỗ trợ: Chuyển đổi RGB sang HSL
function rgbToHsl(r, g, b) {
  (r /= 255), (g /= 255), (b /= 255);
  const max = Math.max(r, g, b),
    min = Math.min(r, g, b);
  let h,
    s,
    l = (max + min) / 2;

  if (max === min) {
    h = s = 0; // achromatic
  } else {
    const d = max - min;
    s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
    switch (max) {
      case r:
        h = (g - b) / d + (g < b ? 6 : 0);
        break;
      case g:
        h = (b - r) / d + 2;
        break;
      case b:
        h = (r - g) / d + 4;
        break;
    }
    h /= 6;
  }
  return [h * 360, s * 100, l * 100]; // Trả về H(0-360), S(0-100), L(0-100)
}

async function setSmartRandomBackground(element) {
  try {
    // --- Tự động tính toán đường dẫn API ---
    let apiPath = "api/random_bg.php";
    const currentPath = window.location.pathname;
    if (
      currentPath.includes("/auth/") ||
      currentPath.includes("/admin/") ||
      currentPath.includes("/pages/")
    ) {
      apiPath = "../api/random_bg.php";
    }

    const response = await fetch(apiPath + "?t=" + new Date().getTime());
    if (!response.ok) throw new Error("Failed to fetch image");

    const blob = await response.blob();
    const imgUrl = URL.createObjectURL(blob);
    const img = new Image();
    img.crossOrigin = "Anonymous";
    img.src = imgUrl;

    img.onload = function () {
      element.style.backgroundImage = `url('${imgUrl}')`;

      // --- PHÂN TÍCH MÀU NÂNG CAO ---
      const canvas = document.createElement("canvas");
      canvas.width = 1;
      canvas.height = 1;
      const ctx = canvas.getContext("2d");
      ctx.drawImage(img, 0, 0, 1, 1);

      // Lấy màu trung bình (Dominant Color)
      const [r, g, b] = ctx.getImageData(0, 0, 1, 1).data;

      // Chuyển sang HSL để dễ thao tác độ sáng/bão hòa
      const [h, s, l] = rgbToHsl(r, g, b);

      // --- THUẬT TOÁN CHỌN MÀU CHỮ "NGHỆ" ---
      let textColor, accentColor, glassBg;

      // Ngưỡng độ sáng để quyết định nền Sáng hay Tối
      if (l > 55) {
        // NỀN SÁNG (Ví dụ: Bãi cát, Bầu trời ban ngày)
        // -> Chữ sẽ là phiên bản RẤT ĐẬM của màu nền (giữ tông màu nhưng làm tối đi)
        // Hue giữ nguyên, Saturation tăng lên chút, Lightness giảm sâu xuống 10-15%
        textColor = `hsl(${h}, ${Math.min(s + 20, 100)}%, 15%)`;

        // Màu nhấn (accent) tươi hơn chút
        accentColor = `hsl(${h}, ${Math.min(s + 40, 100)}%, 40%)`;

        // Nền kính (Glass) màu trắng mờ
        glassBg = `rgba(255, 255, 255, 0.6)`;

        document.body.classList.add("is-bright-bg");
        const logo = document.querySelector(".logo-image");
        if (logo) logo.style.filter = "invert(1) sepia(1) hue-rotate(180deg)"; // Logo đổi màu theo tông
      } else {
        // NỀN TỐI (Ví dụ: Rừng đêm, Biển sâu)
        // -> Chữ sẽ là phiên bản RẤT NHẠT của màu nền (pha chút màu gốc)
        // Hue giữ nguyên, Saturation giảm bớt để đỡ chói, Lightness tăng lên 90-95%
        textColor = `hsl(${h}, ${Math.max(s - 10, 10)}%, 95%)`;

        // Màu nhấn rực rỡ
        accentColor = `hsl(${h}, 80%, 70%)`;

        // Nền kính (Glass) màu đen mờ
        glassBg = `rgba(0, 0, 0, 0.4)`;

        document.body.classList.remove("is-bright-bg");
        const logo = document.querySelector(".logo-image");
        if (logo) logo.style.filter = "none";
      }

      // --- ÁP DỤNG BIẾN CSS ---
      document.body.style.setProperty("--dynamic-text-color", textColor);
      document.body.style.setProperty("--dynamic-accent-color", accentColor);
      document.body.style.setProperty("--dynamic-glass-bg", glassBg);
    };
  } catch (error) {
    console.error("Lỗi tải hình nền:", error);
    element.style.backgroundColor = "#222";
  }
}

/* =========================================
   NAVBAR RANDOM BACKGROUND LOGIC
   ========================================= */
document.addEventListener("DOMContentLoaded", () => {
    const navbarBg = document.getElementById("navbarBg");
    if (navbarBg) {
        setNavbarRandomImage(navbarBg);
    }
});

async function setNavbarRandomImage(element) {
    try {
        // Tự động tính đường dẫn API (giống hàm background body cũ)
        let apiPath = "api/random_bg.php";
        const currentPath = window.location.pathname;
        
        // Nếu đang ở thư mục con
        if (currentPath.includes("/auth/") || currentPath.includes("/admin/") || currentPath.includes("/pages/")) {
            apiPath = "../api/random_bg.php";
        }

        // Gọi API để lấy ảnh (thêm tham số t để tránh cache)
        const response = await fetch(apiPath + "?type=navbar&t=" + new Date().getTime());
        
        if (!response.ok) throw new Error("Failed navbar bg");

        const blob = await response.blob();
        const imgUrl = URL.createObjectURL(blob);

        // Set background
        element.style.backgroundImage = `url('${imgUrl}')`;

    } catch (error) {
        console.warn("Navbar BG Error:", error);
        // Fallback image nếu lỗi
        element.style.backgroundImage = "url('https://source.unsplash.com/random/1600x200?abstract,texture')";
    }
}