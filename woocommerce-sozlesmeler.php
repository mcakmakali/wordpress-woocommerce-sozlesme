<?php
/**
 * Plugin Name: Sözleşme Yönetimi
 * Plugin URI: https://github.com/mcakmakali/wordpress-woocommerce-sozlesme
 * Description: WooCommerce ödeme sayfasında onaylanması gereken/opsiyonel sözleşmeler eklemenizi sağlar.
 * Version: 1.0.0
 * Author: Mehmet Ali Çakmak
 * Author URI: https://mehmetalicakmak.me
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires Plugins: woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

final class Sozlesme_Yonetimi {

	const POST_TYPE = 'sozlesme_wce';

	private $rendered_on_checkout = false;

	public function __construct() {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'add_meta_boxes', array( $this, 'register_meta_boxes' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( $this, 'save_meta' ) );

		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( $this, 'add_admin_columns' ) );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( $this, 'render_admin_column' ), 10, 2 );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_checkout_assets' ) );

		// Klasik WooCommerce checkout şablonu (ör. CheckoutWC devre dışıysa).
		add_action( 'woocommerce_review_order_before_payment', array( $this, 'render_checkout_agreements' ) );
		// CheckoutWC eklentisinin kendi ödeme adımı şablonu (bu sitede aktif olan).
		add_action( 'cfw_checkout_before_payment_methods', array( $this, 'render_checkout_agreements' ) );

		add_action( 'woocommerce_checkout_process', array( $this, 'validate_required_agreements' ) );
		add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'save_agreement_acceptance' ) );

		// Sipariş başarılı olunca (ödeme tamamlandığında) müşteri e-postasına sözleşme PDF'ini ekle.
		add_filter( 'woocommerce_email_attachments', array( $this, 'attach_agreements_pdf' ), 10, 3 );

		// Eklenti ayarları sayfası (WooCommerce > Sözleşme Ayarları).
		add_action( 'admin_menu', array( $this, 'register_settings_page' ) );

		// Fatura Tipi (Bireysel/Kurumsal) alanları — ayarlar sayfasından açılıp kapatılabilir.
		if ( self::fatura_tipi_aktif() ) {
			add_filter( 'woocommerce_checkout_fields', array( $this, 'add_billing_customer_type_fields' ) );
			add_filter( 'woocommerce_checkout_posted_data', array( $this, 'filter_billing_customer_type_posted_data' ) );
			add_action( 'woocommerce_after_checkout_validation', array( $this, 'validate_billing_customer_type_fields' ), 10, 2 );
			add_action( 'wp_footer', array( $this, 'billing_customer_type_toggle_script' ) );
			add_action( 'woocommerce_admin_order_data_after_shipping_address', array( $this, 'display_billing_customer_type_admin_meta' ) );
			add_action( 'woocommerce_email_order_meta', array( $this, 'display_billing_customer_type_in_emails' ), 10, 4 );
		}
	}

	/* ------------------------------------------------------------------ */
	/* Custom post type + admin UI                                        */
	/* ------------------------------------------------------------------ */

	public function register_post_type() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'          => array(
					'name'               => 'Sözleşmeler',
					'singular_name'      => 'Sözleşme',
					'menu_name'          => 'Sözleşmeler',
					'add_new'            => 'Sözleşme Ekle',
					'add_new_item'       => 'Yeni Sözleşme Ekle',
					'edit_item'          => 'Sözleşmeyi Düzenle',
					'new_item'           => 'Yeni Sözleşme',
					'view_item'          => 'Sözleşmeyi Görüntüle',
					'search_items'       => 'Sözleşme Ara',
					'not_found'          => 'Sözleşme bulunamadı',
					'not_found_in_trash' => 'Çöp kutusunda sözleşme bulunamadı',
				),
				'public'             => false,
				'publicly_queryable' => false,
				'exclude_from_search'=> true,
				'has_archive'        => false,
				'rewrite'            => false,
				'show_in_rest'       => false,
				'show_ui'            => true,
				'show_in_menu'       => 'woocommerce',
				'capability_type'    => 'post',
				'supports'           => array( 'title', 'editor', 'page-attributes' ),
				'menu_position'      => 58,
			)
		);
	}

	public function register_meta_boxes() {
		add_meta_box(
			'sozlesme_wce_ayarlar',
			'Sözleşme Ayarları',
			array( $this, 'render_settings_meta_box' ),
			self::POST_TYPE,
			'side',
			'high'
		);

		add_meta_box(
			'sozlesme_wce_degiskenler',
			'WooCommerce Değişkenleri',
			array( $this, 'render_placeholders_meta_box' ),
			self::POST_TYPE,
			'normal',
			'low'
		);
	}

	public function render_settings_meta_box( $post ) {
		wp_nonce_field( 'sozlesme_wce_meta_save', 'sozlesme_wce_meta_nonce' );
		$zorunlu = get_post_meta( $post->ID, '_sozlesme_wce_zorunlu', true ) === 'yes';
		?>
		<p>
			<label>
				<input type="checkbox" name="sozlesme_wce_zorunlu" value="1" <?php checked( $zorunlu ); ?> />
				Bu sözleşme zorunludur
			</label>
		</p>
		<p class="description">
			Zorunlu işaretlenirse, müşteri ödeme sayfasında bu sözleşmeyi onaylamadan siparişi tamamlayamaz.
			İşaretlenmezse sözleşme isteğe bağlı olarak gösterilir.
		</p>
		<p class="description">
			Sürükleyerek (sayfa özniteliklerindeki "Sıra" alanı üzerinden) sözleşmelerin ödeme sayfasındaki
			gösterim sırasını belirleyebilirsiniz.
		</p>
		<?php
	}

	public function render_placeholders_meta_box() {
		?>
		<p class="description">
			Aşağıdaki değişkenleri sözleşme metnine eklemek için üzerlerine tıklayın. Ödeme sayfasında sepet
			bilgileriyle, sipariş tamamlandığında ise gerçek sipariş bilgileriyle otomatik olarak doldurulurlar.
		</p>
		<p>
			<?php foreach ( self::get_placeholders() as $tag => $label ) : ?>
				<button type="button" class="button sozlesme-wce-degisken-ekle" data-tag="<?php echo esc_attr( $tag ); ?>" style="margin:2px;">
					<?php echo esc_html( $label ); ?> <code><?php echo esc_html( $tag ); ?></code>
				</button>
			<?php endforeach; ?>
		</p>
		<?php
	}

	public function save_meta( $post_id ) {
		if ( ! isset( $_POST['sozlesme_wce_meta_nonce'] ) || ! wp_verify_nonce( $_POST['sozlesme_wce_meta_nonce'], 'sozlesme_wce_meta_save' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		update_post_meta( $post_id, '_sozlesme_wce_zorunlu', isset( $_POST['sozlesme_wce_zorunlu'] ) ? 'yes' : 'no' );
	}

	public function add_admin_columns( $columns ) {
		$columns['sozlesme_wce_zorunlu'] = 'Zorunlu mu?';
		return $columns;
	}

	public function render_admin_column( $column, $post_id ) {
		if ( 'sozlesme_wce_zorunlu' === $column ) {
			$zorunlu = get_post_meta( $post_id, '_sozlesme_wce_zorunlu', true ) === 'yes';
			echo $zorunlu ? '<strong>Evet</strong>' : 'Hayır';
		}
	}

	public function enqueue_admin_assets( $hook ) {
		$screen = get_current_screen();
		if ( ! $screen || self::POST_TYPE !== $screen->post_type ) {
			return;
		}
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}
		wp_enqueue_script(
			'sozlesme-wce-admin',
			plugins_url( 'assets/admin.js', __FILE__ ),
			array( 'jquery' ),
			'1.0.0',
			true
		);
	}

	/* ------------------------------------------------------------------ */
	/* Eklenti ayarları (WooCommerce > Sözleşme Ayarları)                 */
	/* ------------------------------------------------------------------ */

	public static function fatura_tipi_aktif() {
		return 'yes' === get_option( 'sozlesme_wce_fatura_tipi_aktif', 'yes' );
	}

	public function register_settings_page() {
		add_submenu_page(
			'woocommerce',
			'Sözleşme Ayarları',
			'Sözleşme Ayarları',
			'manage_woocommerce',
			'sozlesme-wce-ayarlar',
			array( $this, 'render_settings_page' )
		);
	}

	public function render_settings_page() {
		if ( isset( $_POST['sozlesme_wce_ayarlar_nonce'] ) && wp_verify_nonce( $_POST['sozlesme_wce_ayarlar_nonce'], 'sozlesme_wce_ayarlar_save' ) ) {
			update_option( 'sozlesme_wce_fatura_tipi_aktif', isset( $_POST['sozlesme_wce_fatura_tipi_aktif'] ) ? 'yes' : 'no' );
			echo '<div class="notice notice-success"><p>Ayarlar kaydedildi.</p></div>';
		}

		$aktif = self::fatura_tipi_aktif();
		?>
		<div class="wrap">
			<h1>Sözleşme Ayarları</h1>
			<form method="post">
				<?php wp_nonce_field( 'sozlesme_wce_ayarlar_save', 'sozlesme_wce_ayarlar_nonce' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row">Fatura Tipi Alanları</th>
						<td>
							<label>
								<input type="checkbox" name="sozlesme_wce_fatura_tipi_aktif" value="1" <?php checked( $aktif ); ?> />
								Ödeme sayfasında "Fatura Tipi" (Bireysel / Kurumsal) alanını ve buna bağlı
								Şirket Ünvanı, Vergi Dairesi, Vergi Numarası, T.C. Kimlik No alanlarını göster.
							</label>
							<p class="description">
								Aktifken bu alanlar checkout'ta görünür, sipariş admin ekranında ve sipariş
								e-postalarında listelenir; sözleşme metinlerinde
								<code>{fatura_tipi}</code>, <code>{sirket_unvani}</code>, <code>{vergi_numarasi}</code>,
								<code>{vergi_dairesi}</code> değişkenlerini kullanabilirsiniz. Kurumsal seçildiğinde
								<code>{musteri_adi}</code> değişkeni de otomatik olarak şirket ünvanını gösterir.
							</p>
						</td>
					</tr>
				</table>
				<?php submit_button( 'Kaydet' ); ?>
			</form>
		</div>
		<?php
	}

	/* ------------------------------------------------------------------ */
	/* Fatura Tipi (Bireysel/Kurumsal) — checkout alanları                */
	/* ------------------------------------------------------------------ */

	public function add_billing_customer_type_fields( $fields ) {
		$fields['billing']['billing_customer_type'] = array(
			'type'     => 'radio',
			'label'    => __( 'Fatura Tipi', 'woocommerce' ),
			'options'  => array(
				'bireysel' => __( 'Bireysel', 'woocommerce' ),
				'kurumsal' => __( 'Kurumsal', 'woocommerce' ),
			),
			'default'  => 'bireysel',
			'required' => true,
			'class'    => array( 'form-row-wide', 'billing-customer-type-field' ),
			'clear'    => true,
			'priority' => 22,
		);

		$fields['billing']['billing_company'] = array(
			'label'       => __( 'Şirket Unvanı', 'woocommerce' ),
			'placeholder' => _x( 'Şirket Unvanı', 'placeholder', 'woocommerce' ),
			'required'    => true,
			'class'       => array( 'form-row-wide', 'billing-corporate-field' ),
			'clear'       => true,
			'priority'    => 24,
		);

		$fields['billing']['billing_tax_office'] = array(
			'label'       => __( 'Vergi Dairesi', 'woocommerce' ),
			'placeholder' => _x( 'Vergi Dairesi', 'placeholder', 'woocommerce' ),
			'required'    => true,
			'class'       => array( 'form-row-first', 'billing-corporate-field' ),
			'clear'       => false,
			'priority'    => 25,
		);

		$fields['billing']['billing_tax_number'] = array(
			'label'       => __( 'Vergi Numarası', 'woocommerce' ),
			'placeholder' => _x( 'Vergi Numarası', 'placeholder', 'woocommerce' ),
			'required'    => true,
			'class'       => array( 'form-row-last', 'billing-corporate-field' ),
			'clear'       => true,
			'priority'    => 26,
			'maxlength'   => 10,
		);

		$fields['billing']['billing_tc_no'] = array(
			'label'       => __( 'T.C. Kimlik No', 'woocommerce' ),
			'placeholder' => _x( 'T.C. Kimlik No', 'placeholder', 'woocommerce' ),
			'required'    => true,
			'class'       => array( 'form-row-wide', 'billing-individual-field' ),
			'clear'       => true,
			'priority'    => 27,
			'maxlength'   => 11,
		);

		return $fields;
	}

	/**
	 * Fatura Tipi'ne göre pasifleşen (görünmeyen) grubun alanlarını WooCommerce'in
	 * genel "zorunlu alan" kontrolünden hariç tutar.
	 */
	public function filter_billing_customer_type_posted_data( $data ) {
		if ( empty( $data['billing_customer_type'] ) ) {
			return $data;
		}

		if ( 'kurumsal' === $data['billing_customer_type'] ) {
			unset( $data['billing_tc_no'] );
		} else {
			unset( $data['billing_company'], $data['billing_tax_office'], $data['billing_tax_number'] );
		}

		return $data;
	}

	/**
	 * Fatura Tipi (Bireysel/Kurumsal) seçimine göre alan format kontrolü.
	 */
	public function validate_billing_customer_type_fields( $data, $errors ) {
		$customer_type = isset( $_POST['billing_customer_type'] ) ? sanitize_text_field( wp_unslash( $_POST['billing_customer_type'] ) ) : '';

		if ( $customer_type && ! in_array( $customer_type, array( 'bireysel', 'kurumsal' ), true ) ) {
			$errors->add( 'validation', __( 'Lütfen fatura tipini (Bireysel/Kurumsal) seçiniz.', 'woocommerce' ) );
			return;
		}

		if ( 'bireysel' === $customer_type ) {
			$tc_no = isset( $_POST['billing_tc_no'] ) ? trim( wp_unslash( $_POST['billing_tc_no'] ) ) : '';

			if ( $tc_no && ! preg_match( '/^[1-9][0-9]{10}$/', $tc_no ) ) {
				$errors->add( 'validation', __( 'T.C. Kimlik Numarası 11 haneli ve rakamlardan oluşmalıdır.', 'woocommerce' ) );
			}
		} elseif ( 'kurumsal' === $customer_type ) {
			$tax_number = isset( $_POST['billing_tax_number'] ) ? trim( wp_unslash( $_POST['billing_tax_number'] ) ) : '';

			if ( $tax_number && ! preg_match( '/^[0-9]{10}$/', $tax_number ) ) {
				$errors->add( 'validation', __( 'Vergi Numarası 10 haneli ve rakamlardan oluşmalıdır.', 'woocommerce' ) );
			}
		}
	}

	/**
	 * Checkout sayfasında Fatura Tipi seçimine göre alanları göster/gizle.
	 */
	public function billing_customer_type_toggle_script() {
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
			return;
		}
		?>
		<script>
		jQuery(function($) {
			function mediacatToggleBillingCustomerTypeFields() {
				var type = $('input[name="billing_customer_type"]:checked').val();
				var $corporate = $('.billing-corporate-field');
				var $individual = $('.billing-individual-field');

				if ('kurumsal' === type) {
					$corporate.show().addClass('validate-required').find('input').prop('disabled', false);
					$individual.hide().removeClass('validate-required').find('input').val('').prop('disabled', true);
				} else {
					$individual.show().addClass('validate-required').find('input').prop('disabled', false);
					$corporate.hide().removeClass('validate-required').find('input').val('').prop('disabled', true);
				}
			}

			$(document.body).on('change', 'input[name="billing_customer_type"]', mediacatToggleBillingCustomerTypeFields);
			$(document.body).on('updated_checkout', mediacatToggleBillingCustomerTypeFields);
			mediacatToggleBillingCustomerTypeFields();
		});
		</script>
		<?php
	}

	/**
	 * Fatura Tipi'ne göre gösterilecek alanları (etiket => değer) döndürür.
	 * Admin sipariş ekranı, sipariş e-postaları ve sözleşme değişkenleri bu fonksiyonu ortak kullanır.
	 */
	private function get_billing_customer_type_rows( $order ) {
		$customer_type = $order->get_meta( '_billing_customer_type', true );
		$labels        = array(
			'bireysel' => __( 'Bireysel', 'woocommerce' ),
			'kurumsal' => __( 'Kurumsal', 'woocommerce' ),
		);

		$rows = array();

		if ( ! $customer_type ) {
			return $rows;
		}

		$rows[ __( 'Fatura Tipi', 'woocommerce' ) ] = isset( $labels[ $customer_type ] ) ? $labels[ $customer_type ] : $customer_type;

		if ( 'kurumsal' === $customer_type ) {
			$rows[ __( 'Şirket Unvanı', 'woocommerce' ) ] = $order->get_meta( '_billing_company', true );
			$rows[ __( 'Vergi Dairesi', 'woocommerce' ) ] = $order->get_meta( '_billing_tax_office', true );
			$rows[ __( 'Vergi Numarası', 'woocommerce' ) ] = $order->get_meta( '_billing_tax_number', true );
		} else {
			$rows[ __( 'T.C. Kimlik No', 'woocommerce' ) ] = $order->get_meta( '_billing_tc_no', true );
		}

		return $rows;
	}

	public function display_billing_customer_type_admin_meta( $order ) {
		foreach ( $this->get_billing_customer_type_rows( $order ) as $label => $value ) {
			echo '<p><strong>' . esc_html( $label ) . ':</strong> ' . esc_html( $value ) . '</p>';
		}
	}

	public function display_billing_customer_type_in_emails( $order, $sent_to_admin, $plain_text, $email ) {
		$rows = $this->get_billing_customer_type_rows( $order );

		if ( empty( $rows ) ) {
			return;
		}

		if ( $plain_text ) {
			echo esc_html__( 'Fatura Bilgileri', 'woocommerce' ) . "\n";
			foreach ( $rows as $label => $value ) {
				echo esc_html( $label ) . ': ' . esc_html( $value ) . "\n";
			}
			echo "\n";
			return;
		}

		echo '<h2>' . esc_html__( 'Fatura Bilgileri', 'woocommerce' ) . '</h2>';
		echo '<ul style="margin-bottom:20px;">';
		foreach ( $rows as $label => $value ) {
			echo '<li>' . esc_html( $label ) . ': ' . esc_html( $value ) . '</li>';
		}
		echo '</ul>';
	}

	/* ------------------------------------------------------------------ */
	/* WooCommerce değişkenleri                                          */
	/* ------------------------------------------------------------------ */

	public static function get_placeholders() {
		$placeholders = array(
			'{siparis_no}'       => 'Sipariş Numarası',
			'{siparis_tarihi}'   => 'Sipariş Tarihi',
			'{musteri_adi}'      => 'Müşteri Adı Soyadı',
			'{musteri_email}'    => 'Müşteri E-posta',
			'{musteri_telefon}'  => 'Müşteri Telefon',
			'{fatura_adresi}'    => 'Fatura Adresi',
			'{teslimat_adresi}'  => 'Teslimat Adresi',
			'{odeme_yontemi}'    => 'Ödeme Yöntemi',
			'{sepet_urunleri}'   => 'Sepetteki Ürünler',
			'{sepet_toplami}'    => 'Sepet / Sipariş Toplamı',
			'{site_adi}'         => 'Site Adı',
			'{tarih}'            => 'Bugünün Tarihi',
		);

		if ( self::fatura_tipi_aktif() ) {
			$placeholders['{fatura_tipi}']    = 'Fatura Tipi (Bireysel/Kurumsal)';
			$placeholders['{sirket_unvani}']  = 'Şirket Unvanı';
			$placeholders['{vergi_numarasi}'] = 'Vergi Numarası';
			$placeholders['{vergi_dairesi}']  = 'Vergi Dairesi';
		}

		return $placeholders;
	}

	private function replace_placeholders( $content, $order = null ) {
		if ( $order instanceof WC_Order ) {
			$musteri_adi = trim( $order->get_formatted_billing_full_name() );

			$replacements = array(
				'{siparis_no}'      => $order->get_order_number(),
				'{siparis_tarihi}'  => wc_format_datetime( $order->get_date_created() ),
				'{musteri_email}'   => $order->get_billing_email(),
				'{musteri_telefon}' => $order->get_billing_phone(),
				'{fatura_adresi}'   => $this->format_address_fields( $order->get_billing_address_1(), $order->get_billing_address_2(), $order->get_billing_city(), $order->get_billing_state(), $order->get_billing_postcode(), $order->get_billing_country() ),
				'{teslimat_adresi}' => $this->format_address_fields( $order->get_shipping_address_1(), $order->get_shipping_address_2(), $order->get_shipping_city(), $order->get_shipping_state(), $order->get_shipping_postcode(), $order->get_shipping_country() ),
				'{odeme_yontemi}'   => $order->get_payment_method_title(),
				'{sepet_urunleri}'  => $this->format_order_items_table( $order ),
				'{sepet_toplami}'   => wp_strip_all_tags( $order->get_formatted_order_total() ),
				'{site_adi}'        => get_bloginfo( 'name' ),
				'{tarih}'           => date_i18n( get_option( 'date_format' ) ),
			);

			if ( self::fatura_tipi_aktif() ) {
				$customer_type = $order->get_meta( '_billing_customer_type', true );
				$sirket_unvani = $order->get_meta( '_billing_company', true );

				if ( 'kurumsal' === $customer_type && $sirket_unvani ) {
					$musteri_adi = $sirket_unvani;
				}

				$fatura_tipi_etiketleri = array( 'bireysel' => 'Bireysel', 'kurumsal' => 'Kurumsal' );

				$replacements['{fatura_tipi}']    = isset( $fatura_tipi_etiketleri[ $customer_type ] ) ? $fatura_tipi_etiketleri[ $customer_type ] : '—';
				$replacements['{sirket_unvani}']  = $sirket_unvani ? $sirket_unvani : '—';
				$replacements['{vergi_numarasi}'] = $order->get_meta( '_billing_tax_number', true ) ?: '—';
				$replacements['{vergi_dairesi}']  = $order->get_meta( '_billing_tax_office', true ) ?: '—';
			}

			$replacements['{musteri_adi}'] = $musteri_adi;
		} else {
			$cart     = function_exists( 'WC' ) ? WC()->cart : null;
			$customer = function_exists( 'WC' ) ? WC()->customer : null;
			$replacements = array(
				'{siparis_no}'      => '—',
				'{siparis_tarihi}'  => '—',
				'{musteri_adi}'     => $customer ? trim( $customer->get_billing_first_name() . ' ' . $customer->get_billing_last_name() ) : '',
				'{musteri_email}'   => $customer ? $customer->get_billing_email() : '',
				'{musteri_telefon}' => $customer ? $customer->get_billing_phone() : '',
				'{fatura_adresi}'   => $customer ? $this->format_customer_address( $customer, 'billing' ) : '—',
				'{teslimat_adresi}' => $customer ? $this->format_customer_address( $customer, 'shipping' ) : '—',
				'{odeme_yontemi}'   => $this->get_chosen_payment_method_title(),
				'{sepet_urunleri}'  => $cart ? $this->format_cart_items_table( $cart ) : '',
				'{sepet_toplami}'   => $cart ? wp_strip_all_tags( $cart->get_total() ) : '',
				'{site_adi}'        => get_bloginfo( 'name' ),
				'{tarih}'           => date_i18n( get_option( 'date_format' ) ),
			);

			// CheckoutWC, Fatura Tipi radyosunu değiştirince sunucuya hiç istek atmıyor (saf JS ile
			// alan gösterip gizliyor) — bu yüzden bu değişkenleri sunucu tarafında çözemiyoruz.
			// Bunun yerine görünmez birer <span> olarak basıp checkout.js'te canlı olarak
			// (form alanları değiştikçe) dolduruyoruz; bkz. assets/checkout.js.
			if ( self::fatura_tipi_aktif() ) {
				$bireysel_ad = $replacements['{musteri_adi}'];

				$replacements['{musteri_adi}']    = '<span class="sozlesme-wce-ad-bireysel">' . esc_html( $bireysel_ad ) . '</span><span class="sozlesme-wce-ad-kurumsal"></span>';
				$replacements['{fatura_tipi}']    = '<span class="sozlesme-wce-fatura-tipi">Bireysel</span>';
				$replacements['{sirket_unvani}']  = '<span class="sozlesme-wce-sirket-unvani">—</span>';
				$replacements['{vergi_numarasi}'] = '<span class="sozlesme-wce-vergi-no">—</span>';
				$replacements['{vergi_dairesi}']  = '<span class="sozlesme-wce-vergi-dairesi">—</span>';
			}
		}

		return strtr( $content, $replacements );
	}

	/**
	 * WooCommerce'in "update_checkout" AJAX çağrısında (radyo/alan değişince tetiklenir) gelen
	 * ham form verisinden Fatura Tipi alanlarını okuyup session'a yazar. Böylece ödeme sayfası
	 * önizlemesindeki sözleşme popup'ı, sipariş henüz oluşmadan Bireysel/Kurumsal seçimine göre
	 * güncel değişkenlerle (ör. {musteri_adi}) yeniden render edilebilir.
	 */
	public function sync_billing_customer_type_to_session( $post_data ) {
		if ( ! function_exists( 'WC' ) || ! WC()->session ) {
			return;
		}

		parse_str( $post_data, $data );

		WC()->session->set( 'sozlesme_wce_billing_customer_type', isset( $data['billing_customer_type'] ) ? sanitize_text_field( $data['billing_customer_type'] ) : '' );
		WC()->session->set( 'sozlesme_wce_billing_company', isset( $data['billing_company'] ) ? sanitize_text_field( $data['billing_company'] ) : '' );
		WC()->session->set( 'sozlesme_wce_billing_tax_office', isset( $data['billing_tax_office'] ) ? sanitize_text_field( $data['billing_tax_office'] ) : '' );
		WC()->session->set( 'sozlesme_wce_billing_tax_number', isset( $data['billing_tax_number'] ) ? sanitize_text_field( $data['billing_tax_number'] ) : '' );
	}

	private function get_chosen_payment_method_title() {
		if ( ! function_exists( 'WC' ) || ! WC()->session ) {
			return '—';
		}

		$chosen_id = WC()->session->get( 'chosen_payment_method' );
		$gateways  = WC()->payment_gateways->get_available_payment_gateways();

		if ( $chosen_id && isset( $gateways[ $chosen_id ] ) ) {
			return $gateways[ $chosen_id ]->get_title();
		}

		$gateway = reset( $gateways );
		return $gateway ? $gateway->get_title() : '—';
	}

	private function format_customer_address( $customer, $type ) {
		$getter = 'get_' . $type;
		return $this->format_address_fields(
			$customer->{$getter . '_address_1'}(),
			$customer->{$getter . '_address_2'}(),
			$customer->{$getter . '_city'}(),
			$customer->{$getter . '_state'}(),
			$customer->{$getter . '_postcode'}(),
			$customer->{$getter . '_country'}()
		);
	}

	private function format_address_fields( $address_1, $address_2, $city, $state, $postcode, $country ) {
		if ( empty( $address_1 ) && empty( $city ) ) {
			return '—';
		}

		$address = array(
			'address_1' => $address_1,
			'address_2' => $address_2,
			'city'      => $city,
			'state'     => $state,
			'postcode'  => $postcode,
			'country'   => $country,
		);

		return wp_strip_all_tags( WC()->countries->get_formatted_address( $address, ', ' ) );
	}

	private function format_cart_items_table( $cart ) {
		$rows = '';
		foreach ( $cart->get_cart() as $item ) {
			$product = $item['data'];
			if ( ! $product || ! $item['quantity'] ) {
				continue;
			}
			$birim_fiyat = $item['line_subtotal'] / $item['quantity'];
			$indirim     = $item['line_subtotal'] - $item['line_total'];
			$kdv         = $item['line_tax'];
			$satir_toplam = $item['line_total'] + $item['line_tax'];

			$rows .= $this->build_item_row( $product->get_name(), $item['quantity'], $birim_fiyat, $indirim, $kdv, $satir_toplam );
		}

		$tax = (float) $cart->get_total_tax();

		$footer  = '<tr><td colspan="5">Ara Toplam</td><td>' . wp_kses_post( wc_price( $cart->get_subtotal() ) ) . '</td></tr>';
		$footer .= '<tr><td colspan="5">KDV</td><td>' . wp_kses_post( wc_price( $tax ) ) . '</td></tr>';
		$footer .= '<tr><td colspan="5"><strong>Toplam</strong></td><td><strong>' . wp_kses_post( wc_price( $cart->get_total( 'edit' ) ) ) . '</strong></td></tr>';

		return $this->wrap_items_table( $rows, $footer );
	}

	private function format_order_items_table( $order ) {
		$rows = '';
		foreach ( $order->get_items() as $item ) {
			if ( ! $item->get_quantity() ) {
				continue;
			}
			$birim_fiyat  = $item->get_subtotal() / $item->get_quantity();
			$indirim      = $item->get_subtotal() - $item->get_total();
			$kdv          = $item->get_total_tax();
			$satir_toplam = $item->get_total() + $item->get_total_tax();

			$rows .= $this->build_item_row( $item->get_name(), $item->get_quantity(), $birim_fiyat, $indirim, $kdv, $satir_toplam, $order->get_currency() );
		}

		$tax = (float) $order->get_total_tax();

		$footer  = '<tr><td colspan="5">Ara Toplam</td><td>' . wp_kses_post( wc_price( $order->get_subtotal(), array( 'currency' => $order->get_currency() ) ) ) . '</td></tr>';
		$footer .= '<tr><td colspan="5">KDV</td><td>' . wp_kses_post( wc_price( $tax, array( 'currency' => $order->get_currency() ) ) ) . '</td></tr>';
		$footer .= '<tr><td colspan="5"><strong>Toplam</strong></td><td><strong>' . wp_kses_post( wc_price( $order->get_total(), array( 'currency' => $order->get_currency() ) ) ) . '</strong></td></tr>';

		return $this->wrap_items_table( $rows, $footer );
	}

	private function build_item_row( $name, $quantity, $birim_fiyat, $indirim, $kdv, $satir_toplam, $currency = '' ) {
		$price_args = $currency ? array( 'currency' => $currency ) : array();

		return '<tr>'
			. '<td>' . esc_html( $name ) . '</td>'
			. '<td>' . esc_html( $quantity ) . '</td>'
			. '<td>' . wp_kses_post( wc_price( $birim_fiyat, $price_args ) ) . '</td>'
			. '<td>' . ( $indirim > 0 ? wp_kses_post( wc_price( $indirim, $price_args ) ) : '—' ) . '</td>'
			. '<td>' . wp_kses_post( wc_price( $kdv, $price_args ) ) . '</td>'
			. '<td>' . wp_kses_post( wc_price( $satir_toplam, $price_args ) ) . '</td>'
			. '</tr>';
	}

	private function wrap_items_table( $rows, $footer ) {
		return '<div class="sozlesme-wce-tablo-sarici"><table class="sozlesme-wce-urun-tablosu"><thead><tr><th>Ürün/Hizmet Adı</th><th>Miktar</th><th>Bedel<br>(Birim Fiyat)</th><th>İndirim<br>(Varsa)</th><th>KDV</th><th>Toplam</th></tr></thead><tbody>' . $rows . '</tbody><tfoot>' . $footer . '</tfoot></table></div>';
	}

	/* ------------------------------------------------------------------ */
	/* Checkout görünümü                                                  */
	/* ------------------------------------------------------------------ */

	private function get_agreements() {
		return get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'menu_order title',
				'order'          => 'ASC',
			)
		);
	}

	public function enqueue_checkout_assets() {
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
			return;
		}
		wp_enqueue_style( 'sozlesme-wce-checkout', plugins_url( 'assets/checkout.css', __FILE__ ), array(), '1.0.0' );
		wp_enqueue_script( 'sozlesme-wce-checkout', plugins_url( 'assets/checkout.js', __FILE__ ), array( 'jquery' ), '1.0.0', true );
	}

	public function render_checkout_agreements() {
		// Aynı sayfada birden fazla hook devreye girerse (klasik + CheckoutWC) tekrar basmayı önle.
		if ( $this->rendered_on_checkout ) {
			return;
		}

		$agreements = $this->get_agreements();
		if ( empty( $agreements ) ) {
			return;
		}

		$this->rendered_on_checkout = true;
		?>
		<div class="sozlesme-wce-liste">
			<?php foreach ( $agreements as $agreement ) :
				$required     = get_post_meta( $agreement->ID, '_sozlesme_wce_zorunlu', true ) === 'yes';
				$modal_id     = 'sozlesme-wce-modal-' . $agreement->ID;
				$content      = $this->replace_placeholders( apply_filters( 'the_content', $agreement->post_content ) );
				?>
				<p class="form-row sozlesme-wce-satir">
					<label>
						<input type="checkbox"
							id="sozlesme-wce-checkbox-<?php echo esc_attr( $agreement->ID ); ?>"
							name="sozlesme_wce_onay[]"
							value="<?php echo esc_attr( $agreement->ID ); ?>"
							class="sozlesme-wce-checkbox"
							<?php echo $required ? 'required' : ''; ?> />
						<a href="#" class="sozlesme-wce-link" data-target="<?php echo esc_attr( $modal_id ); ?>">
							<?php echo esc_html( $agreement->post_title ); ?>
						</a>'nı okudum, onaylıyorum.
						<?php if ( $required ) : ?>
							<span class="sozlesme-wce-zorunlu-isareti" title="Zorunlu">*</span>
						<?php endif; ?>
					</label>
				</p>
				<div class="sozlesme-wce-modal" id="<?php echo esc_attr( $modal_id ); ?>" data-checkbox="sozlesme-wce-checkbox-<?php echo esc_attr( $agreement->ID ); ?>">
					<div class="sozlesme-wce-modal-ic">
						<div class="sozlesme-wce-modal-baslik">
							<h2><?php echo esc_html( $agreement->post_title ); ?></h2>
							<button type="button" class="sozlesme-wce-modal-kapat" aria-label="Kapat">&times;</button>
						</div>
						<div class="sozlesme-wce-modal-icerik"><?php echo wp_kses_post( $content ); ?></div>
						<div class="sozlesme-wce-modal-alt">
							<button type="button" class="sozlesme-wce-modal-vazgec">Kapat</button>
							<button type="button" class="sozlesme-wce-modal-onayla">Okudum, Onaylıyorum</button>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}

	public function validate_required_agreements() {
		$accepted = isset( $_POST['sozlesme_wce_onay'] ) ? array_map( 'absint', (array) $_POST['sozlesme_wce_onay'] ) : array();

		foreach ( $this->get_agreements() as $agreement ) {
			$required = get_post_meta( $agreement->ID, '_sozlesme_wce_zorunlu', true ) === 'yes';
			if ( $required && ! in_array( $agreement->ID, $accepted, true ) ) {
				wc_add_notice(
					sprintf( 'Devam etmek için "%s" sözleşmesini onaylamanız gerekiyor.', esc_html( $agreement->post_title ) ),
					'error'
				);
			}
		}
	}

	public function save_agreement_acceptance( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$accepted = isset( $_POST['sozlesme_wce_onay'] ) ? array_map( 'absint', (array) $_POST['sozlesme_wce_onay'] ) : array();
		if ( empty( $accepted ) ) {
			return;
		}

		$snapshot = array();
		foreach ( $accepted as $agreement_id ) {
			$agreement = get_post( $agreement_id );
			if ( ! $agreement || self::POST_TYPE !== $agreement->post_type ) {
				continue;
			}
			$snapshot[] = array(
				'id'          => $agreement_id,
				'baslik'      => $agreement->post_title,
				'icerik'      => $this->replace_placeholders( apply_filters( 'the_content', $agreement->post_content ), $order ),
				'onay_tarihi' => current_time( 'mysql' ),
			);
		}

		if ( $snapshot ) {
			$order->update_meta_data( '_sozlesme_wce_onaylari', $snapshot );
			$order->save();
		}
	}

	/* ------------------------------------------------------------------ */
	/* Sipariş e-postasına sözleşme PDF'i ekleme                          */
	/* ------------------------------------------------------------------ */

	public function attach_agreements_pdf( $attachments, $email_id, $object ) {
		$siparis_e_postalari = array( 'customer_processing_order', 'customer_completed_order' );

		if ( ! in_array( $email_id, $siparis_e_postalari, true ) || ! ( $object instanceof WC_Order ) ) {
			return $attachments;
		}

		if ( ! class_exists( 'Dompdf\Dompdf' ) ) {
			return $attachments;
		}

		$snapshot = $object->get_meta( '_sozlesme_wce_onaylari' );
		if ( empty( $snapshot ) ) {
			return $attachments;
		}

		$pdf_path = $this->generate_agreements_pdf( $object, $snapshot );
		if ( $pdf_path ) {
			$attachments[] = $pdf_path;
			// E-posta gönderimi bu isteğin sonunda tamamlanmış olacağından,
			// geçici PDF'i istek biter bitmez temizle (S3'e değil, yerel diske yazıldığı için buna gerek var).
			register_shutdown_function( 'unlink', $pdf_path );
		}

		return $attachments;
	}

	private function generate_agreements_pdf( $order, $snapshot ) {
		// wp_upload_dir() bu sitede S3-Uploads tarafından uzak depolamaya (s3://) yönlendiriliyor.
		// PHPMailer eklerini yerel dosya sisteminden okur, bu yüzden ekler için WordPress'in
		// gerçek yerel geçici klasörünü (get_temp_dir()) kullanmak gerekiyor, uploads klasörünü değil.
		$hedef_klasor = trailingslashit( get_temp_dir() ) . 'sozlesmeler-pdf';

		if ( ! file_exists( $hedef_klasor ) ) {
			wp_mkdir_p( $hedef_klasor );
		}

		$dosya_adi = 'sozlesme-siparis-' . $order->get_order_number() . '-' . md5( $order->get_id() . $order->get_order_key() ) . '.pdf';
		$dosya_yolu = $hedef_klasor . '/' . $dosya_adi;

		$dompdf = new Dompdf\Dompdf( array(
			'isRemoteEnabled' => false,
			'defaultFont'     => 'DejaVu Sans',
		) );
		$dompdf->loadHtml( $this->build_pdf_html( $order, $snapshot ) );
		$dompdf->setPaper( 'A4', 'portrait' );
		$dompdf->render();

		$sonuc = file_put_contents( $dosya_yolu, $dompdf->output() );

		return $sonuc ? $dosya_yolu : false;
	}

	private function build_pdf_html( $order, $snapshot ) {
		$bolumler = '';
		foreach ( $snapshot as $index => $sozlesme ) {
			$sinif = 'sozlesme' . ( $index > 0 ? ' sozlesme-yeni-sayfa' : '' );
			$bolumler .= '<div class="' . $sinif . '">'
				. '<h2>' . esc_html( $sozlesme['baslik'] ) . '</h2>'
				. '<div class="icerik">' . wp_kses_post( $sozlesme['icerik'] ) . '</div>'
				. '<p class="onay-notu">Onay tarihi: ' . esc_html( $sozlesme['onay_tarihi'] ) . '</p>'
				. '</div>';
		}

		return '<html><head><meta charset="utf-8"><style>
			@page { margin: 16mm 14mm; }
			* { box-sizing: border-box; }
			body { font-family: "DejaVu Sans", sans-serif; font-size: 10px; color: #222; }
			h1 { font-size: 15px; margin: 0 0 4px; }
			.siparis-bilgi { font-size: 10px; color: #555; margin-bottom: 20px; }
			.sozlesme { margin-bottom: 24px; }
			.sozlesme-yeni-sayfa { page-break-before: always; }
			.sozlesme h2 { font-size: 12px; border-bottom: 1px solid #ccc; padding-bottom: 5px; }
			.sozlesme .icerik { word-wrap: break-word; overflow-wrap: break-word; }
			.sozlesme .icerik p { margin: 0 0 8px; }
			.onay-notu { font-size: 9px; color: #777; margin-top: 6px; }

			/* Sözleşme metninin kendi içindeki bilgi tabloları (Satıcı/Alıcı vb.) */
			.icerik table { max-width: 100%; border-collapse: collapse; margin: 8px 0; font-size: 9px; }
			.icerik table td, .icerik table th { padding: 4px 5px; vertical-align: top; word-wrap: break-word; overflow-wrap: break-word; }

			/* {sepet_urunleri} değişkeninin ürettiği ürün tablosu: taşmayı önlemek için sabit kolon genişlikleri */
			.sozlesme-wce-urun-tablosu { table-layout: fixed; width: 100%; border-collapse: collapse; margin: 8px 0; font-size: 8px; }
			.sozlesme-wce-urun-tablosu th, .sozlesme-wce-urun-tablosu td { border-bottom: 1px solid #ddd; padding: 4px 5px; text-align: right; word-wrap: break-word; overflow-wrap: break-word; white-space: normal; }
			.sozlesme-wce-urun-tablosu th:first-child, .sozlesme-wce-urun-tablosu td:first-child { text-align: left; width: 26%; }
			.sozlesme-wce-urun-tablosu thead th { background: #f5f5f5; }
		</style></head><body>'
			. '<h1>' . esc_html( get_bloginfo( 'name' ) ) . ' — Onaylanan Sözleşmeler</h1>'
			. '<p class="siparis-bilgi">Sipariş No: ' . esc_html( $order->get_order_number() ) . ' &nbsp;|&nbsp; Sipariş Tarihi: ' . esc_html( wc_format_datetime( $order->get_date_created() ) ) . ' &nbsp;|&nbsp; Müşteri: ' . esc_html( trim( $order->get_formatted_billing_full_name() ) ) . '</p>'
			. $bolumler
			. '</body></html>';
	}
}

new Sozlesme_Yonetimi();
