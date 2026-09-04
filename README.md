# Sözleşme Yönetimi

WooCommerce ödeme (checkout) sayfasında müşteriye onaylatılacak sözleşmeler (Mesafeli Satış Sözleşmesi, Ön Bilgilendirme Formu, KVKK metni vb.) eklemenizi sağlayan site-özel WordPress eklentisi.

- **Sürüm:** 1.0.0
- **Yazar:** Mehmet Ali Çakmak — [mehmetalicakmak.me](https://mehmetalicakmak.me)
- **Konum:** `wp-content/plugins/woocommerce-sozlesmeler/`
- **Gerekli:** WooCommerce (aktif olmalı)

---

## 1. Genel Bakış

Bu eklenti dört ana işi yapar:

1. **Sözleşme içeriği yönetimi** — WordPress admin panelinden, WooCommerce değişkenleriyle (ürün, tutar, müşteri bilgisi vb.) doldurulabilen sözleşme metinleri oluşturursunuz.
2. **Checkout'ta gösterim** — Bu sözleşmeler, ödeme sayfasında ödeme yöntemi kutusunun **üstünde** onay kutuları (checkbox) olarak listelenir. Zorunlu işaretlenenler onaylanmadan sipariş tamamlanamaz. Başlığa tıklanınca sözleşme metni bir popup'ta okunabilir.
3. **Sipariş kaydı** — Müşterinin onayladığı sözleşmelerin, o anki gerçek sipariş bilgileriyle (sipariş no, ürünler, tutar vb.) doldurulmuş hali sipariş kaydına not düşülür (yasal kanıt amaçlı).
4. **E-posta PDF eki** — Sipariş başarıyla tamamlandığında (ödeme onaylanınca), onaylanan sözleşmelerin PDF hali müşteriye giden sipariş e-postasına otomatik eklenir.

Ayrıca, sitenin daha önce temada (`functions.php`) yer alan **Fatura Tipi (Bireysel/Kurumsal)** checkout özelliği bu eklentiye taşınmıştır ve buradan açılıp kapatılabilir.

---

## 2. Yönetim Paneli

WordPress admin menüsünde **WooCommerce** ana menüsü altında iki alt sayfa bulunur:

### Sözleşmeler

Sözleşmelerin listelendiği ve yönetildiği ekran (`sozlesme_wce` özel içerik tipi).

- **Sözleşme Ekle** ile yeni bir sözleşme oluşturulur:
  - **Başlık** — checkout'ta ve popup'ta görünen sözleşme adı (ör. "Mesafeli Satış Sözleşmesi").
  - **İçerik (editör)** — standart WordPress editörüyle yazılan sözleşme metni. İçinde aşağıdaki (§4) WooCommerce değişkenleri kullanılabilir.
  - **Sözleşme Ayarları** kutusu:
    - *"Bu sözleşme zorunludur"* — işaretlenirse müşteri bu sözleşmeyi onaylamadan siparişi tamamlayamaz. İşaretlenmezse isteğe bağlıdır.
  - **WooCommerce Değişkenleri** kutusu — her değişken için bir buton vardır; butona tıklandığında ilgili `{degisken}` etiketi imlecin bulunduğu yere (editörün görsel veya metin moduna uygun şekilde) eklenir.
  - **Sıra** (sayfa özniteliklerinden) — birden fazla sözleşme varsa checkout'taki gösterim sırasını belirler (küçükten büyüğe).
- Liste ekranında **"Zorunlu mu?"** sütunu, hangi sözleşmelerin zorunlu olduğunu hızlıca gösterir.
- Sözleşmeler `public: false` olarak kayıtlıdır: tek başlarına bir web sayfası olarak görüntülenemezler, arama sonuçlarına ve XML sitemap'e girmezler. Sadece checkout'ta ve admin panelde görünürler.

### Sözleşme Ayarları

Eklentinin genel ayarlarının olduğu tekil sayfa.

- **"Fatura Tipi Alanları"** — bkz. §6. Açıkken checkout'ta Bireysel/Kurumsal seçimi ve buna bağlı alanlar gösterilir; kapatılırsa bu alanlar ve ilgili değişkenler tamamen devre dışı kalır.

---

## 3. Checkout'ta Davranış

### Konum

Sözleşmeler, ödeme yöntemi seçim kutusunun (kredi kartı alanları vb.) **hemen üstünde** listelenir. Bu site **CheckoutWC** eklentisiyle özelleştirilmiş bir checkout kullandığından, iki farklı hook'a birden bağlanılmıştır:

- `woocommerce_review_order_before_payment` — standart/klasik WooCommerce checkout şablonu için (CheckoutWC devre dışı kalırsa diye).
- `cfw_checkout_before_payment_methods` — CheckoutWC'nin kendi ödeme adımı şablonu için (bu sitede fiilen kullanılan).

Aynı sayfada ikisi birden tetiklenirse tekrar basılmaması için bir bayrak (`rendered_on_checkout`) kullanılır.

### Onay kutuları ve popup

Her sözleşme için:

```
[ ] Sözleşme Adı 'nı okudum, onaylıyorum. *
```

- Checkbox işaretlenmeden **zorunlu** bir sözleşme geçilemez: hem tarayıcı seviyesinde (`required` özniteliği — form gönderilmeden native uyarı çıkar) hem sunucu tarafında (`woocommerce_checkout_process` hook'unda kontrol edilir, eksikse hata mesajı gösterilip sipariş engellenir) doğrulanır.
- Sözleşme adına tıklanınca, ortalanmış, animasyonlu bir popup açılır. Popup'ta:
  - Sözleşme başlığı ve tam metni (değişkenler doldurulmuş hâliyle),
  - **"Kapat"** butonu — popup'ı kapatır,
  - **"Okudum, Onaylıyorum"** butonu — ilgili checkbox'ı otomatik işaretler ve popup'ı kapatır.
- Popup; overlay'e tıklayınca, `Esc` tuşuna basınca veya kapat butonuyla kapanır.

### Değişkenlerin checkout önizlemesinde çözümlenmesi

Sipariş henüz oluşmadığından (`{siparis_no}` gibi) bazı değişkenler checkout aşamasında kesin değildir:

- **Sepet/müşteri bilgileri** (ürünler, tutar, KDV, müşteri adı/e-posta/telefon, adresler, ödeme yöntemi) — o anki sepet ve oturum açmış müşteri bilgisinden canlı olarak doldurulur.
- **Sipariş no / sipariş tarihi** — henüz yok, `—` olarak gösterilir; sipariş tamamlandığında gerçek değerle değişir.
- **Fatura Tipi alanları** (`{fatura_tipi}`, `{sirket_unvani}`, `{vergi_numarasi}`, `{vergi_dairesi}` ve buna bağlı `{musteri_adi}`) — CheckoutWC bu alan değişikliklerinde sunucuya istek göndermediği için, bunlar **JavaScript ile istemci tarafında canlı** güncellenir (`assets/checkout.js`): kullanıcı "Bireysel/Kurumsal" seçimini veya şirket/vergi alanlarını değiştirdikçe, açık olan veya sonradan açılacak popup'lardaki bu değişkenler anında güncellenir.

---

## 4. Kullanılabilir Değişkenler

Sözleşme içeriğine yazılan `{...}` etiketleri, hem checkout önizlemesinde hem de sipariş/PDF'te otomatik olarak gerçek değerlerle değiştirilir.

| Değişken | Açıklama |
|---|---|
| `{siparis_no}` | Sipariş numarası (checkout'ta `—`) |
| `{siparis_tarihi}` | Sipariş tarihi (checkout'ta `—`) |
| `{musteri_adi}` | Müşteri adı soyadı (Fatura Tipi aktif ve Kurumsal seçiliyse şirket ünvanı — bkz. §6) |
| `{musteri_email}` | Müşteri e-posta adresi |
| `{musteri_telefon}` | Müşteri telefon numarası |
| `{fatura_adresi}` | Fatura adresi (sadece adres — ad/soyad içermez) |
| `{teslimat_adresi}` | Teslimat adresi |
| `{odeme_yontemi}` | Seçilen/kullanılan ödeme yöntemi başlığı |
| `{sepet_urunleri}` | Sepetteki/siparişteki ürünlerin **tablo** hâli — bkz. §5 |
| `{sepet_toplami}` | Sepet/sipariş toplam tutarı |
| `{site_adi}` | Site adı |
| `{tarih}` | Bugünün tarihi |
| `{fatura_tipi}` * | "Bireysel" veya "Kurumsal" |
| `{sirket_unvani}` * | Şirket ünvanı (kurumsalsa) |
| `{vergi_numarasi}` * | Vergi numarası (kurumsalsa) |
| `{vergi_dairesi}` * | Vergi dairesi (kurumsalsa) |

\* Sadece **Sözleşme Ayarları** sayfasındaki "Fatura Tipi Alanları" açıkken kullanılabilir; kapalıyken bu dört değişken listede görünmez.

Boş/uygulanamaz durumlarda değişkenler `—` olarak gösterilir (ör. teslimat adresi girilmemişse).

---

## 5. `{sepet_urunleri}` Tablosu

Bu değişken düz metin değil, Türkiye'deki mesafeli satış sözleşmesi formatına uygun bir **HTML tablo** üretir:

| Ürün/Hizmet Adı | Miktar | Bedel (Birim Fiyat) | İndirim (Varsa) | KDV | Toplam |
|---|---|---|---|---|---|

- Her satır bir sepet/sipariş kalemidir; indirim yoksa `—` gösterilir.
- Tablonun altında **Ara Toplam**, **KDV** ve **Toplam** satırları bulunur — bu satırlar sepetin/siparişin gerçek toplamlarıyla birebir eşleşir.
- Tablo, dar ekranlarda (ör. popup içinde) taşmaması için sabit kolon genişlikleriyle (`table-layout: fixed`) render edilir; gerekirse yatay kaydırılabilir bir sarmalayıcı içindedir.
- PDF çıktısında da aynı tablo, PDF'e özel (biraz daha küçük, sayfaya sığacak şekilde optimize edilmiş) bir stille basılır.

---

## 6. Fatura Tipi (Bireysel / Kurumsal) Entegrasyonu

Bu özellik, sitenin temasında (`functions.php`) yer alan checkout alanlarının bu eklentiye taşınmış hâlidir. **WooCommerce → Sözleşme Ayarları** sayfasındaki checkbox ile tamamen açılıp kapatılabilir (varsayılan: **açık**).

Açıkken checkout'ta "Fatura Adresi" bölümüne şu alanlar eklenir:

- **Fatura Tipi** (radyo) — Bireysel / Kurumsal
- **Kurumsal** seçilirse: Şirket Ünvanı, Vergi Dairesi, Vergi Numarası (zorunlu, 10 haneli sayı doğrulaması)
- **Bireysel** seçilirse: T.C. Kimlik No (zorunlu, 11 haneli TC kimlik no doğrulaması)

Seçime göre ilgili olmayan grup gizlenir, devre dışı bırakılır ve gönderilen veriden çıkarılır.

Bu bilgiler:

- **Sipariş admin ekranında** (sipariş detayında, teslimat adresinin altında) "Fatura Bilgileri" olarak gösterilir.
- **Sipariş e-postalarında** (Yeni Sipariş, İşleme Alındı, Tamamlandı vb.) "Fatura Bilgileri" bölümü olarak eklenir.
- **Sözleşme değişkenlerinde** kullanılabilir hale gelir (§4'teki `{fatura_tipi}` vb.).

**Özel davranış:** Kurumsal seçilip Şirket Ünvanı doldurulmuşsa, `{musteri_adi}` değişkeni otomatik olarak **şirket ünvanını** gösterir (kişi adı yerine). Bireysel'de herhangi bir değişiklik olmaz, müşterinin adı-soyadı gösterilmeye devam eder.

---

## 7. Sipariş Kaydı (Yasal Kanıt)

Müşteri siparişi tamamladığında, onayladığı her sözleşme için:

- Sözleşme ID'si, başlığı,
- **O anki gerçek sipariş bilgileriyle** (placeholder'lar çözümlenmiş hâliyle) tam metni,
- Onay tarihi/saati,

`_sozlesme_wce_onaylari` adında bir sipariş meta alanına (HPOS uyumlu, `$order->update_meta_data()` ile) kaydedilir. Bu, ileride bir anlaşmazlık durumunda müşterinin tam olarak neyi, hangi bilgilerle onayladığının kanıtı olarak saklanır.

---

## 8. E-posta PDF Eki

Sipariş **"İşleme Alındı" (processing)** veya **"Tamamlandı" (completed)** durumuna geçtiğinde — yani ödeme başarıyla sonuçlandığında — müşteriye giden WooCommerce sipariş e-postasına, onaylanan sözleşmelerin PDF hâli otomatik eklenir.

### Nasıl çalışır

1. `woocommerce_email_attachments` filtresine bağlanılır; e-posta `customer_processing_order` veya `customer_completed_order` ise ve siparişte kayıtlı sözleşme onayı varsa devam edilir.
2. [dompdf](https://github.com/dompdf/dompdf) kütüphanesiyle (Composer üzerinden `vendor/` içine kurulmuştur), sipariş meta'sındaki sözleşme snapshot'larından bir A4 PDF üretilir. Türkçe karakterler için `DejaVu Sans` fontu kullanılır.
3. PDF, WordPress'in **yerel geçici klasörüne** (`get_temp_dir()`) yazılır — `wp_upload_dir()`'e **kasıtlı olarak değil**, çünkü bu sitede **S3-Uploads** eklentisi upload klasörünü uzak depolamaya (S3) yönlendiriyor ve PHPMailer uzak (stream) dosya yollarından ek okuyamıyor (bu, önceden yaşanan "PDF e-postaya gelmiyor" hatasının kök nedeniydi).
4. E-posta gönderimi tamamlandıktan hemen sonra (`register_shutdown_function`), geçici PDF dosyası sunucudan silinir — sunucuda birikmez.

### PDF içeriği

- Her sözleşme kendi başlığıyla ve tam metniyle (gerçek sipariş verileriyle doldurulmuş) ayrı bir bölüm olarak yer alır; birden fazla sözleşme varsa her biri kendi sayfasında başlar.
- `{sepet_urunleri}` tablosu PDF'e özel, sayfaya sığacak şekilde optimize edilmiş stille basılır.
- Sayfa altında sözleşmenin onay tarihi belirtilir.

---

## 9. Dosya Yapısı

```
woocommerce-sozlesmeler/
├── woocommerce-sozlesmeler.php   # Ana eklenti dosyası (tüm mantık: Sozlesme_Yonetimi sınıfı)
├── composer.json / composer.lock # dompdf bağımlılığı
├── vendor/                       # dompdf ve bağımlılıkları (composer install ile gelir)
├── assets/
│   ├── admin.js                  # Sözleşme editöründe değişken ekleme butonları
│   ├── checkout.js               # Popup aç/kapat, onay butonu, Fatura Tipi canlı güncelleme
│   └── checkout.css              # Onay kutuları, popup ve ürün tablosu stilleri
└── README.md                     # Bu dosya
```

---

## 10. Teknik Notlar / Kullanılan Hook'lar

| Hook | Amaç |
|---|---|
| `init` | Özel içerik tipini (`sozlesme_wce`) kaydeder |
| `add_meta_boxes`, `save_post_sozlesme_wce` | Sözleşme düzenleme ekranı meta box'ları |
| `admin_menu` | "Sözleşme Ayarları" alt menüsü |
| `woocommerce_review_order_before_payment` / `cfw_checkout_before_payment_methods` | Checkout'ta sözleşme listesini basar |
| `woocommerce_checkout_process` | Zorunlu sözleşme sunucu tarafı doğrulaması |
| `woocommerce_checkout_update_order_meta` | Onaylanan sözleşmelerin sipariş meta'sına kaydedilmesi |
| `woocommerce_email_attachments` | Sipariş e-postasına PDF eklenmesi |
| `woocommerce_checkout_fields`, `woocommerce_checkout_posted_data`, `woocommerce_after_checkout_validation`, `wp_footer`, `woocommerce_admin_order_data_after_shipping_address`, `woocommerce_email_order_meta` | Fatura Tipi (Bireysel/Kurumsal) alanları — sadece ayar açıkken kayıtlı |

### Gereksinimler

- WordPress + WooCommerce (aktif)
- PHP 8.1+ (dompdf gereksinimi)
- Composer ile kurulmuş `vendor/` klasörü (dompdf) — eksikse PDF eki sessizce devre dışı kalır, checkout/sözleşme özellikleri etkilenmez.

### Bilinen sınırlamalar

- Checkout önizlemesinde (sipariş oluşmadan önce) `{siparis_no}`, `{siparis_tarihi}` gibi sipariş-özel değişkenler kesin değildir (`—` gösterilir); yalnızca sipariş tamamlandıktan sonra (sipariş kaydı ve PDF'te) gerçek değerle dolar.
- Fatura Tipi alanlarının checkout önizlemesindeki canlı güncellemesi, bu sitenin kullandığı CheckoutWC'nin bu alanlar için sunucuya istek göndermemesi nedeniyle istemci tarafı (JavaScript) ile yapılır; farklı bir checkout altyapısına geçilirse bu kısmın gözden geçirilmesi gerekebilir.
- Eklenti bu siteye (CheckoutWC + İyzico + S3-Uploads + WooCommerce Subscriptions gibi aktif eklentilerin bulunduğu kurulum) özel geliştirilmiştir; farklı bir kurulumda checkout hook'larının ve dosya depolama davranışının tekrar doğrulanması önerilir.
