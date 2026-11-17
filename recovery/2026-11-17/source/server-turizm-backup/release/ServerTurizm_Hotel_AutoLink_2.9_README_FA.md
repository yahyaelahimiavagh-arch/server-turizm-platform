# Server Turizm — Hotel Auto-Link 2.9

این بسته دو فایل **جداگانه** دارد (هرکدام را در محیط خودش استفاده کنید):

1. `server-turizm-sync2-hotel-auto-link-2.9.zip` — افزونه WordPress برای جایگزینی **فقط Server Turizm Auto Sync 2** (نسخه 2.8).
2. `ServerTurizm_AutoSync_2.9_OneFile.gs` — کد یکپارچهٔ Google Apps Script برای جایگزینی فایل ST2 قبلی، بدون دست‌کاری Script Properties و کلیدها.

## هدف و مرجع اطلاعات

- هتل‌ها و گالری‌ها در **Hotel Intelligence** در WordPress مدیریت می‌شوند.
- تب `Hotels` در Google Sheets در وضعیت فعلی ستون‌های A تا F (نام، نقشه، عکس، شهر، لوگو، UUID) را دارد. این اطلاعات قدیمی و UUIDها حفظ می‌شوند.
- دو ستون جدید به‌صورت خودکار در همان تب پر می‌شوند:
  - `G: WP Hotel ID` مانند `STH-000023`
  - `H: WP Hotel URL` مانند صفحهٔ اختصاصی هتل
- Hotel Intelligence عکس‌ها، موقعیت، سطح تأیید و متن هتل را مالکیت می‌کند؛ Auto Sync برای ردیف‌های دارای شناسهٔ ثابت G این موارد را بازنویسی نمی‌کند.
- برای هتل‌های جدیدِ WordPress ردیف جدیدی در شیت ایجاد می‌شود، حتی اگر عکس یا نقشه موجود نباشد.
- منوهای انتخاب هتل در `Home` برای مدینه (P)، مکه (S) و شهر سوم (M) با اضافه‌شدن نام‌های جدید به‌روز می‌شوند؛ گزینه‌های قبلی حذف نمی‌شوند.
- اسکریپت شناسهٔ هتل انتخاب‌شده را در Snapshot می‌فرستد؛ WordPress فقط پس از تأیید تطابق شناسه با نام معتبر هتل، آن را به برنامه وصل می‌کند. ارتباط ناشناخته یا مبهم خودکار حدس زده نمی‌شود.
- برنامه‌های قبلی، شناسه‌های STP، آرشیو و طراحی اصلی [stppi_umre_programs] دست‌نخورده می‌مانند.

## ترتیب نصب

1. در `Tools → Server Auto Sync` دکمهٔ **Pause database updates** را بزن. از **Restore checkpoint** استفاده نکن.
2. فایل ZIP 2.9 را از `WordPress → Plugins → Add New → Upload Plugin` آپلود کن و **Replace current with uploaded** را بزن. افزونهٔ Hotel Intelligence، Program Intelligence، Publishing Integration و Direct Sync Foundation را حذف نکن.
3. در Google Sheets به `Extensions → Apps Script` برو. **فقط محتوای فایل موجود ST2 Auto Sync** را با متن کامل فایل `.gs` نسخه 2.9 جایگزین کن. کد جدید را کنار نسخهٔ قبلی اضافه نکن. Script Properties، secret، endpoint و triggerهای نصب‌شده را حذف نکن.
4. از داخل Apps Script تابع `st2SyncNow` را یک بار اجرا کن، یا از منوی شیت `🔁 Server Turizm — Auto Sync → Şimdi Senkronize Et` استفاده کن.
5. در تب `Hotels` ببین ستون‌های G و H اضافه شده‌اند. ردیف Jiwar باید `STH-000023` بگیرد **اگر اطلاعات فعلی آن با نام/شناسهٔ وردپرس به‌طور یکتا تطبیق داشته باشد**. سپس Dropdown برنامه‌ها در Home را باز کن.
6. اگر هر دو بخش درست‌اند، Database updates را در WordPress فعال کن. بار نخست به دلیل اضافه‌شدن فیلدهای شناسهٔ هتل، پردازش کامل برنامه‌ها ممکن است دوباره اجرا شود. `Public design — ORIGINAL STPPI DESIGN — LOCKED` باید حفظ شود.

## رفتار و محدودیت‌ها

- دریافت هتل‌ها در چرخهٔ معمول Sync انجام می‌شود: با تغییر شیت سریع‌تر، و بدون تغییر شیت معمولاً حداکثر در حدود **۵ دقیقه** بعد از ایجاد هتل جدید، بسته به زمان‌بندی Trigger و پاسخ سرور.
- WordPress endpoint جدید `GET /wp-json/server-turizm/v2/hotel-directory` **عمومی نیست**؛ همان امضای HMAC Direct Sync Foundation را لازم دارد. در مرورگر بدون هدرهای امضا، خطای مجوز طبیعی است.
- برای هتل‌های `draft`/`unverified` ممکن است نام در شیت موجود باشد، ولی لینک عمومی یا اجازهٔ انتشار کارت برنامه تا تأیید Hotel Intelligence در دسترس نباشد. ایجاد صفحهٔ هتل به‌تنهایی تأیید رزرو/اقامت نیست.
- انتخاب‌های قدیمی مثل `Medine Merkeziye Bölgesi` و `Mekke Şişe Bölgesi` حفظ می‌شوند.
- در صورت تشابه مبهم نام یا برخورد شناسه، مورد به‌صورت امن گزارش می‌شود و از حدس‌زدن یا ساختن رکورد تکراری جلوگیری می‌شود. خطاها در `Server Turizm Auto Sync → Son Durum` (بخش Hotel Bridge) قابل دیدن‌اند.
- گالری‌ها و نقشه‌های قبلی از WordPress مدیریت می‌شوند. تصاویر قدیمی شیت پاک نمی‌شوند. برای هتل جدید ممکن است ستون C فقط تصویر اصلی را داشته باشد؛ کارت برنامهٔ متصل به هتل باید گالری را از Hotel Intelligence بخواند.
- کدها با lint نحوی PHP/JavaScript و شبیه‌سازی محدود رفتار شیت بررسی شده‌اند؛ هنوز **روی سایت زنده آزمایش یا نصب نشده‌اند**. Checkpoint داخلی جای بکاپ کامل WordPress نیست.

## نقطهٔ توقف ایمن

اگر پس از نصب نسخهٔ 2.9 در `Son Durum` خطای Hotel Bridge دیدی یا برنامه‌ای هتل اشتباه نمایش داد، `Pause database updates` را نگه دار و شماره شناسهٔ همان برنامه و هتل را بررسی کن. نیازی به حذف افزونه یا Restore کل سایت نیست.
