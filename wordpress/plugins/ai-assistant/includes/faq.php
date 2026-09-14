<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * FAQ Sheet helpers.
 */
function stai_normalize_text_for_match($text) {
    $text = stai_normalize_digits(mb_strtolower((string) $text, 'UTF-8'));

    $search = ['ı', 'İ', 'ş', 'ğ', 'ü', 'ö', 'ç'];
    $replace = ['i', 'i', 's', 'g', 'u', 'o', 'c'];
    $text = str_replace($search, $replace, $text);

    $text = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $text);
    $text = preg_replace('/\s+/u', ' ', $text);

    return trim($text);
}

function stai_get_faqs_from_sheet($force_refresh = false) {
    if (!defined('STAI_FAQ_CSV_URL') || empty(STAI_FAQ_CSV_URL)) {
        return [];
    }

    if (!$force_refresh) {
        $cached = get_transient('stai_faq_cache');

        if (!empty($cached) && is_array($cached)) {
            return $cached;
        }
    }

    $response = wp_remote_get(STAI_FAQ_CSV_URL, [
        'timeout' => 20,
    ]);

    if (is_wp_error($response)) {
        return [];
    }

    $csv = wp_remote_retrieve_body($response);

    if (empty($csv)) {
        return [];
    }

    if (stripos($csv, '<html') !== false || stripos($csv, '<!doctype html') !== false) {
        return [];
    }

    $rows = stai_parse_csv($csv);

    if (count($rows) < 2) {
        return [];
    }

    $faqs = [];

    for ($i = 1; $i < count($rows); $i++) {
        $row = $rows[$i];

        $question = stai_cell($row, 0);
        $answer = stai_cell($row, 1);
        $language = stai_cell($row, 2);
        $category = stai_cell($row, 3);
        $active = stai_cell($row, 4);

        if (empty($question) || empty($answer)) {
            continue;
        }

        if (!empty($active) && !in_array(strtolower($active), ['true', '1', 'yes', 'evet'], true)) {
            continue;
        }

        $faqs[] = [
            'question' => $question,
            'answer' => $answer,
            'language' => $language,
            'category' => $category,
        ];
    }

    set_transient('stai_faq_cache', $faqs, 30 * MINUTE_IN_SECONDS);

    return $faqs;
}


function stai_get_public_faq_buttons_from_sheet($force_refresh = false) {
    if (!defined('STAI_FAQ_CSV_URL') || empty(STAI_FAQ_CSV_URL)) {
        return [];
    }

    if (!$force_refresh) {
        $cached = get_transient('stai_faq_public_buttons_cache');

        if (!empty($cached) && is_array($cached)) {
            return $cached;
        }
    }

    $response = wp_remote_get(STAI_FAQ_CSV_URL, [
        'timeout' => 20,
    ]);

    if (is_wp_error($response)) {
        return [];
    }

    $csv = wp_remote_retrieve_body($response);

    if (empty($csv)) {
        return [];
    }

    if (stripos($csv, '<html') !== false || stripos($csv, '<!doctype html') !== false) {
        return [];
    }

    $rows = stai_parse_csv($csv);

    if (count($rows) < 2) {
        return [];
    }

    $buttons = [];

    for ($i = 1; $i < count($rows); $i++) {
        $row = $rows[$i];

        /*
         * FAQ Sheet public button columns:
         * F = Button Label
         * G = Button Question
         */
        $label = stai_cell($row, 5);
        $question = stai_cell($row, 6);

        if (empty($label)) {
            continue;
        }

        if (empty($question)) {
            $question = $label;
        }

        $buttons[] = [
            'label' => $label,
            'question' => $question,
        ];
    }

    set_transient('stai_faq_public_buttons_cache', $buttons, 30 * MINUTE_IN_SECONDS);

    return $buttons;
}

function stai_get_default_public_faq_buttons() {
    return [
        [
            'label' => 'İhram Nasıl Giyilir?',
            'question' => 'İhram nasıl giyilir?',
        ],
        [
            'label' => 'Hizmetlerimiz',
            'question' => 'Umre hizmetleriniz nelerdir?',
        ],
        [
            'label' => 'Yolcuya Verilenler',
            'question' => 'Yolcuya neler verilir?',
        ],
        [
            'label' => 'Gerekli Evraklar',
            'question' => 'Umre için hangi evraklar gereklidir?',
        ],
        [
            'label' => 'Umreye Hazırlık',
            'question' => 'Umreye gitmeden önce nelere dikkat etmeliyim?',
        ],
        [
            'label' => 'Fiyata Dahil Olanlar',
            'question' => 'Fiyatlara neler dahildir?',
        ],
    ];
}

function stai_get_public_faq_buttons() {
    $buttons = stai_get_public_faq_buttons_from_sheet();

    if (!empty($buttons)) {
        return $buttons;
    }

    return stai_get_default_public_faq_buttons();
}

function stai_get_static_faqs() {
    return [
        [
            'question' => 'İhram nasıl giyilir?',
            'answer' => "İhram, umre ibadetine başlamadan önce niyetle girilen özel bir hâldir. Erkekler için ihram iki parça dikişsiz beyaz örtüden oluşur: biri belden aşağıya sarılır, diğeri omuzlara alınır. Kadınlar için özel bir ihram kıyafeti şart değildir; tesettüre uygun, sade ve rahat kıyafetlerle ihrama girilebilir.\n\nİhrama girmeden önce gusül veya abdest almak, tırnak ve kişisel bakım hazırlıklarını yapmak, güzel koku kullanmak ve ardından mikat sınırında umre niyetiyle telbiye getirmek tavsiye edilir.\n\nİhram kuralları ve uygulama detayları için rehberlerimiz yolculuk öncesi ve yolculuk sırasında misafirlerimize gerekli bilgilendirmeyi yapmaktadır.",
            'language' => 'tr',
            'category' => 'umre_bilgi',
        ],
        [
            'question' => 'İhram yasakları nelerdir?',
            'answer' => "İhrama girdikten sonra bazı davranışlardan kaçınmak gerekir. Erkeklerin dikişli kıyafet giymemesi, başı örtmemesi; tüm misafirlerin saç, sakal veya tırnak kesmemesi, koku sürmemesi, avlanmaması, tartışma ve kötü sözlerden uzak durması gerekir.\n\nBu kurallar ibadetin manevi ciddiyetini korumak içindir. Yolculuk sırasında rehberlerimiz ihram yasakları konusunda gerekli hatırlatmaları yapar.",
            'language' => 'tr',
            'category' => 'umre_bilgi',
        ],
        [
            'question' => 'Umre hizmetleriniz nelerdir?',
            'answer' => "Server Turizm olarak umre yolculuğunda misafirlerimize program içeriğine göre kapsamlı hizmetler sunuyoruz. Hizmetlerimiz genel olarak uçak bileti organizasyonu, otel konaklamaları, Mekke ve Medine transferleri, rehberlik hizmetleri, program takibi, bilgilendirme desteği ve satış ekibiyle rezervasyon sürecinin yönetimini kapsar.\n\nHer programın hizmet kapsamı, otel standardı, tarihleri, fiyatı ve kontenjan durumu farklı olabilir. Bu nedenle kesin hizmet içeriği seçilen programa göre Server Turizm satış ekibi tarafından teyit edilmelidir.",
            'language' => 'tr',
            'category' => 'hizmetler',
        ],
        [
            'question' => 'Yolcuya neler verilir?',
            'answer' => "Program içeriğine göre misafirlerimize yolculuk öncesi gerekli bilgilendirme yapılır. Bazı programlarda umre çantası, bilgilendirme materyalleri, rehberlik desteği ve yolculuk sürecinde ihtiyaç duyulabilecek temel yönlendirmeler sağlanabilir.\n\nVerilecek materyaller programa, döneme ve kampanyaya göre değişebilir. Kesin bilgi için seçtiğiniz program özelinde Server Turizm satış ekibiyle görüşmeniz önerilir.",
            'language' => 'tr',
            'category' => 'hizmetler',
        ],
        [
            'question' => 'Umre için hangi evraklar gereklidir?',
            'answer' => "Umre başvurusu için genellikle geçerli pasaport, kimlik bilgileri, biyometrik fotoğraf ve vize işlemleri için gerekli bilgiler talep edilir. Pasaportun geçerlilik süresi ve evrak şartları dönemsel olarak değişebilir.\n\nGüncel evrak listesi, başvuru yapılacak tarihe ve Suudi Arabistan resmi uygulamalarına göre değişebileceği için kesin bilgi Server Turizm satış ekibi tarafından teyit edilmelidir.",
            'language' => 'tr',
            'category' => 'evrak',
        ],
        [
            'question' => 'Pasaport süresi ne kadar olmalı?',
            'answer' => "Umre başvurularında pasaport geçerlilik süresi önemlidir. Genel olarak pasaportun seyahat tarihinden sonra yeterli süre geçerli olması beklenir; ancak bu süre resmi uygulamalara göre değişebilir.\n\nRezervasyon öncesinde pasaportunuzun geçerlilik süresini satış ekibimizle paylaşmanız, vize ve seyahat sürecinin sorunsuz ilerlemesi açısından önemlidir.",
            'language' => 'tr',
            'category' => 'evrak',
        ],
        [
            'question' => 'Umreye gitmeden önce nelere dikkat etmeliyim?',
            'answer' => "Umre yolculuğu öncesinde pasaport ve evraklarınızı kontrol etmeniz, rahat yürüyüş ayakkabısı hazırlamanız, düzenli kullandığınız ilaçları yanınıza almanız, hava durumuna uygun kıyafet seçmeniz ve rehberlerin bilgilendirmelerini takip etmeniz önerilir.\n\nAyrıca ibadet sürecini daha bilinçli geçirmek için umre menasiki, ihram kuralları, tavaf ve sa’y uygulamaları hakkında temel bilgi edinmek faydalı olur.",
            'language' => 'tr',
            'category' => 'hazirlik',
        ],
        [
            'question' => 'Aile ile umreye gitmek için nelere dikkat edilmeli?',
            'answer' => "Aile ile umre planlarken otel mesafesi, oda tipi, çocuk ücretleri, seyahat süresi, transfer düzeni ve program temposu dikkat edilmesi gereken önemli konulardır. Çocuklu aileler için otelin konumu, yürüme mesafesi ve programın yoğunluğu özellikle önemlidir.\n\nSize en uygun programı belirlemek için aile kişi sayısı, çocuk yaşları ve tercih ettiğiniz bütçe bilgisiyle Server Turizm satış ekibiyle görüşebilirsiniz.",
            'language' => 'tr',
            'category' => 'aile',
        ],
        [
            'question' => 'Lüks program ile ekonomik program arasındaki fark nedir?',
            'answer' => "Lüks ve ekonomik programlar arasındaki temel farklar genellikle otel standardı, otelin Harem’e yakınlığı, konfor seviyesi, oda seçenekleri ve fiyat aralığıdır. Lüks programlar daha yüksek konfor ve genellikle daha avantajlı konum sunarken, ekonomik programlar daha uygun bütçeyle umre yapmak isteyen misafirler için hazırlanır.\n\nKesin farklar seçilen programın otel, tarih ve hizmet detaylarına göre değişir.",
            'language' => 'tr',
            'category' => 'program',
        ],
        [
            'question' => 'Fiyatlara neler dahildir?',
            'answer' => "Fiyatlara dahil olan hizmetler programa göre değişebilir. Genellikle konaklama, belirlenen transferler, rehberlik ve program organizasyonu gibi hizmetler paket kapsamında olabilir. Uçak bileti, vize, yemek, kişisel harcamalar veya ek hizmetler programa göre dahil ya da hariç olabilir.\n\nKesin kapsam için ilgilendiğiniz program numarasıyla Server Turizm satış ekibinden teyit almanız gerekir.",
            'language' => 'tr',
            'category' => 'fiyat',
        ],
        [
            'question' => 'Oteller Harem’e yakın mı?',
            'answer' => "Otel konumu seçilen programa göre değişir. Bazı programlarda Harem’e daha yakın ve yüksek standartlı oteller tercih edilirken, ekonomik programlarda daha uygun fiyatlı ve farklı mesafelerde oteller kullanılabilir.\n\nHer programın Mekke ve Medine otel bilgileri program kartlarında belirtilir. Mesafe ve konum teyidi için satış ekibimizden destek alabilirsiniz.",
            'language' => 'tr',
            'category' => 'otel',
        ],
        [
            'question' => 'Rezervasyon nasıl yapılır?',
            'answer' => "Rezervasyon için ilgilendiğiniz programı seçtikten sonra Server Turizm satış ekibiyle iletişime geçmeniz gerekir. Satış ekibimiz müsaitlik, kontenjan, güncel fiyat ve evrak süreci hakkında sizi bilgilendirir.\n\nRezervasyon ve kesin kayıt işlemleri, güncel kontenjan ve ödeme koşulları teyit edildikten sonra tamamlanır.",
            'language' => 'tr',
            'category' => 'rezervasyon',
        ],
    ];
}

function stai_find_faq_answer($message) {
    $faqs = array_merge(stai_get_static_faqs(), stai_get_faqs_from_sheet());

    if (empty($faqs)) {
        return '';
    }

    $msg = stai_normalize_text_for_match($message);

    if ($msg === '') {
        return '';
    }

    $best_score = 0;
    $best_answer = '';

    foreach ($faqs as $faq) {
        $q = stai_normalize_text_for_match($faq['question']);

        if ($q === '') {
            continue;
        }

        $score = 0;

        if ($msg === $q) {
            $score = 100;
        } elseif (mb_strpos($msg, $q) !== false || mb_strpos($q, $msg) !== false) {
            $score = 80;
        } else {
            $msg_words = array_filter(explode(' ', $msg));
            $q_words = array_filter(explode(' ', $q));

            foreach ($msg_words as $word) {
                if (mb_strlen($word, 'UTF-8') < 3) {
                    continue;
                }

                if (in_array($word, $q_words, true)) {
                    $score += 10;
                }
            }
        }

        if ($score > $best_score) {
            $best_score = $score;
            $best_answer = $faq['answer'];
        }
    }

    if ($best_score >= 30) {
        return $best_answer . "\n\nSon bilgi ve rezervasyon için Server Turizm satış ekibiyle iletişime geçebilirsiniz.";
    }

    return '';
}
