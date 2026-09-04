=== Sözleşme Yönetimi ===
Contributors: mcakmakali
Donate link: https://mehmetalicakmak.me
Tags: woocommerce, checkout, contract, agreement, pdf
Requires at least: 5.8
Tested up to: 6.9
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Requires Plugins: woocommerce

WooCommerce ödeme sayfasında müşteriye onaylatılacak sözleşmeler (mesafeli satış sözleşmesi, ön bilgilendirme formu, KVKK metni vb.) ekler.

== Description ==

**Sözleşme Yönetimi**, WooCommerce ödeme (checkout) sayfasında müşteriye onay kutusuyla onaylatılacak sözleşmeler eklemenizi sağlayan bir WordPress eklentisidir.

= Özellikler =

* Yönetim panelinden sınırsız sayıda sözleşme oluşturma; her biri "zorunlu" veya "isteğe bağlı" olarak işaretlenebilir.
* Sözleşmeler, ödeme yöntemi seçim kutusunun üstünde onay kutuları olarak listelenir; başlığa tıklanınca tam metin bir popup'ta okunabilir.
* Zorunlu sözleşmeler onaylanmadan sipariş tamamlanamaz (hem tarayıcı hem sunucu tarafında doğrulanır).
* Sözleşme metinlerinde kullanılabilen WooCommerce değişkenleri: sipariş numarası, sipariş tarihi, müşteri adı/e-posta/telefon, fatura/teslimat adresi, ödeme yöntemi, sepetteki ürünlerin ürün/miktar/KDV/toplam tablosu, sepet toplamı, site adı, tarih.
* Onaylanan sözleşmelerin, gerçek sipariş bilgileriyle doldurulmuş hâli sipariş kaydına not düşülür (yasal kanıt amaçlı).
* Sipariş başarıyla tamamlandığında, onaylanan sözleşmelerin PDF hâli müşteriye giden sipariş e-postasına otomatik eklenir.
* İsteğe bağlı "Fatura Tipi" (Bireysel/Kurumsal) checkout alanları: şirket ünvanı, vergi dairesi, vergi numarası veya T.C. kimlik no toplanabilir ve sözleşme değişkeni olarak kullanılabilir.
* Sözleşme kayıtları tekil sayfa olarak görüntülenemez, arama sonuçlarına ve site haritasına (sitemap) girmez.

= Gereksinimler =

* WooCommerce (aktif olmalı)
* PHP 8.1 veya üzeri

== Installation ==

1. Eklenti dosyalarını `/wp-content/plugins/woocommerce-sozlesmeler` klasörüne yükleyin (veya WordPress admin panelinden "Eklenti Ekle" ile zip dosyasını yükleyin).
2. Eklentiyi WordPress admin panelindeki "Eklentiler" menüsünden etkinleştirin.
3. WooCommerce menüsü altında **Sözleşmeler** ile yeni sözleşmeler ekleyin, **Sözleşme Ayarları** ile Fatura Tipi alanlarını açıp kapatın.

== Frequently Asked Questions ==

= Bu eklenti hangi ödeme akışlarıyla çalışır? =

Hem klasik WooCommerce checkout şablonuyla hem de CheckoutWC gibi özelleştirilmiş checkout eklentileriyle çalışacak şekilde tasarlanmıştır.

= PDF eki için ek bir kütüphane gerekir mi? =

Hayır, gerekli PDF kütüphanesi (dompdf) eklenti içinde birlikte gelir.

= Sözleşmeler public sayfa olarak görünür mü? =

Hayır, sözleşmeler sadece checkout'ta ve yönetim panelinde görünür; tekil sayfa olarak görüntülenemez ve site haritasına girmez.

== Screenshots ==

1. Checkout sayfasında ödeme yöntemi kutusunun üstünde listelenen sözleşme onay kutuları.
2. Sözleşme içeriğini gösteren okuma popup'ı.
3. Yönetim panelinde sözleşme ekleme ekranı ve WooCommerce değişken butonları.

== Changelog ==

= 1.0.0 =
* İlk sürüm.
