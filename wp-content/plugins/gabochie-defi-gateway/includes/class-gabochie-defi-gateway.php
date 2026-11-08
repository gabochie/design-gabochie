<?php
/**
 * WooCommerce Payment Gateway Class for Gabochie DeFi & Crypto Payments.
 *
 * @package Gabochie_DeFi
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Gateway_Gabochie_DeFi extends WC_Payment_Gateway {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id                 = 'gabochie_defi';
		$this->icon               = GABOCHIE_DEFI_URL . 'assets/images/crypto-icons.png';
		$this->has_fields         = true;
		$this->method_title       = __( 'DeFi & Crypto Payments', 'gabochie-defi' );
		$this->method_description = __( 'Direct Web3 self-custodial payment gateway. Accepts USDT, USDC, ETH, SOL, MATIC, and BNB across Polygon, Base, Arbitrum, BSC, Ethereum, and Solana.', 'gabochie-defi' );

		// Load settings
		$this->init_form_fields();
		$this->init_settings();

		// User settings
		$this->title         = $this->get_option( 'title', __( 'DeFi & Crypto Payments (USDT / USDC / ETH / SOL)', 'gabochie-defi' ) );
		$this->description   = $this->get_option( 'description', __( 'Pay directly from your Web3 wallet or exchange using USDT, USDC, ETH, SOL, or MATIC. Fast, secure, zero payment processing markups.', 'gabochie-defi' ) );
		$this->instructions  = $this->get_option( 'instructions', __( 'Send exact crypto amount to the wallet address shown for your chosen chain. Enter your Transaction Hash (TxHash) for instant on-chain verification.', 'gabochie-defi' ) );
		$this->evm_wallet    = $this->get_option( 'evm_wallet', get_option( 'gabochie_defi_evm_wallet', '0x1234567890123456789012345678901234567890' ) );
		$this->solana_wallet = $this->get_option( 'solana_wallet', get_option( 'gabochie_defi_solana_wallet', '' ) );
		$this->usd_ghs_rate  = (float) $this->get_option( 'usd_ghs_rate', '15.5' );

		// Actions
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
		add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
	}

	private function evm_wallet_is_valid() {
		$wallet = trim( (string) $this->evm_wallet );
		if ( '' === $wallet || '0x1234567890123456789012345678901234567890' === strtolower( $wallet ) ) {
			return false;
		}
		return (bool) preg_match( '/^0x[a-f0-9]{40}$/i', $wallet );
	}

	private function solana_wallet_is_valid() {
		$wallet = trim( (string) $this->solana_wallet );
		if ( '' === $wallet ) {
			return true;
		}
		return (bool) preg_match( '/^[1-9A-HJ-NP-Za-km-z]{32,44}$/', $wallet );
	}

	private function has_configured_wallet() {
		return $this->evm_wallet_is_valid() && $this->solana_wallet_is_valid();
	}

	public function is_available() {
		return parent::is_available() && $this->has_configured_wallet();
	}

	/**
	 * Admin settings fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'   => __( 'Enable/Disable', 'gabochie-defi' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable DeFi & Crypto Payments', 'gabochie-defi' ),
				'default' => 'yes',
			),
			'title' => array(
				'title'       => __( 'Title', 'gabochie-defi' ),
				'type'        => 'text',
				'description' => __( 'Payment method title displayed during checkout.', 'gabochie-defi' ),
				'default'     => __( 'DeFi & Crypto Payments (USDT / USDC / ETH / SOL)', 'gabochie-defi' ),
				'desc_tip'    => true,
			),
			'description' => array(
				'title'       => __( 'Description', 'gabochie-defi' ),
				'type'        => 'textarea',
				'description' => __( 'Payment method description displayed during checkout.', 'gabochie-defi' ),
				'default'     => __( 'Pay directly from your Web3 wallet or exchange using USDT, USDC, ETH, SOL, or MATIC. Zero gateway markups.', 'gabochie-defi' ),
			),
			'instructions' => array(
				'title'       => __( 'Instructions', 'gabochie-defi' ),
				'type'        => 'textarea',
				'description' => __( 'Instructions displayed on Thank You page and order emails.', 'gabochie-defi' ),
				'default'     => __( 'Send payment to the wallet address shown for your chosen chain. Enter your Transaction Hash (TxHash) for automated verification.', 'gabochie-defi' ),
			),
			'evm_wallet' => array(
				'title'       => __( 'EVM Wallet Address (0x...)', 'gabochie-defi' ),
				'type'        => 'text',
				'description' => __( 'Your primary EVM non-custodial wallet address for Polygon, Base, Arbitrum, BSC, and Ethereum.', 'gabochie-defi' ),
				'default'     => '0x1234567890123456789012345678901234567890',
				'desc_tip'    => true,
			),
			'solana_wallet' => array(
				'title'       => __( 'Solana Wallet Address', 'gabochie-defi' ),
				'type'        => 'text',
				'description' => __( 'Your Solana wallet address for SOL / USDC-SPL payments.', 'gabochie-defi' ),
				'default'     => '',
				'desc_tip'    => true,
			),
			'usd_ghs_rate' => array(
				'title'       => __( 'GHS to USD/USDT Exchange Rate', 'gabochie-defi' ),
				'type'        => 'text',
				'description' => __( 'Number of GHS per 1 USD/USDT (e.g. 15.5). Used to convert GHS cart total to USDT/USDC equivalent.', 'gabochie-defi' ),
				'default'     => '15.5',
				'desc_tip'    => true,
			),
		);
	}

	/**
	 * Render payment fields on WooCommerce checkout page.
	 */
	public function payment_fields() {
		if ( $this->description ) {
			echo wp_kses_post( wpautop( wptexturize( $this->description ) ) );
		}

		$cart_total_ghs = WC()->cart ? WC()->cart->get_total( '' ) : 0;
		$rate = $this->usd_ghs_rate > 0 ? $this->usd_ghs_rate : 15.5;
		$usd_total = round( (float) $cart_total_ghs / $rate, 2 );

		$evm = esc_attr( $this->evm_wallet );
		$sol = esc_attr( $this->solana_wallet );

		?>
		<div class="gabochie-defi-checkout-wrapper" id="gabochieDeFiWrapper">
			<div class="gd-defi-summary">
				<span><?php esc_html_e( 'Cart Total:', 'gabochie-defi' ); ?> <strong>GH₵<?php echo esc_html( number_format( (float) $cart_total_ghs, 2 ) ); ?></strong></span>
				<span class="gd-defi-usd">&asymp; <strong>$<?php echo esc_html( number_format( $usd_total, 2 ) ); ?> USDT / USDC</strong></span>
			</div>

			<div class="gd-defi-chain-selector">
				<label><?php esc_html_e( 'Select Blockchain & Token:', 'gabochie-defi' ); ?></label>
				<div class="gd-defi-chains">
					<button type="button" class="gd-chain-btn active" data-chain="polygon" data-addr="<?php echo $evm; ?>" data-token="USDT (Polygon)" data-network="Polygon POS">Polygon (USDT/USDC)</button>
					<button type="button" class="gd-chain-btn" data-chain="base" data-addr="<?php echo $evm; ?>" data-token="USDC (Base)" data-network="Base L2">Base (USDC/ETH)</button>
					<button type="button" class="gd-chain-btn" data-chain="arbitrum" data-addr="<?php echo $evm; ?>" data-token="USDT (Arbitrum)" data-network="Arbitrum One">Arbitrum (USDT)</button>
					<button type="button" class="gd-chain-btn" data-chain="bsc" data-addr="<?php echo $evm; ?>" data-token="USDT (BEP20)" data-network="BNB Smart Chain">BSC (USDT/BNB)</button>
					<button type="button" class="gd-chain-btn" data-chain="ethereum" data-addr="<?php echo $evm; ?>" data-token="USDT (ERC20)" data-network="Ethereum Mainnet">Ethereum (USDT/ETH)</button>
					<?php if ( ! empty( $sol ) ) : ?>
						<button type="button" class="gd-chain-btn" data-chain="solana" data-addr="<?php echo $sol; ?>" data-token="USDC (Solana)" data-network="Solana Mainnet">Solana (SOL/USDC)</button>
					<?php endif; ?>
				</div>
			</div>

			<div class="gd-defi-details-box">
				<div class="gd-defi-qr-col">
					<img id="gdDefiQR" src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?php echo urlencode( $evm ); ?>" alt="Wallet QR Code" width="140" height="140" />
				</div>
				<div class="gd-defi-address-col">
					<span class="gd-label"><?php esc_html_e( 'Send Payment To Wallet Address:', 'gabochie-defi' ); ?></span>
					<div class="gd-addr-box">
						<code id="gdDefiAddr"><?php echo $evm; ?></code>
						<button type="button" class="gd-copy-btn" id="gdCopyAddrBtn"><?php esc_html_e( 'Copy', 'gabochie-defi' ); ?></button>
					</div>
					<div class="gd-net-info">
						<span>Network: <strong id="gdNetName">Polygon POS</strong></span> | 
						<span>Token: <strong id="gdTokenName">USDT (Polygon)</strong></span>
					</div>
				</div>
			</div>

			<div class="gd-defi-tx-input-wrap">
				<label for="gabochie_defi_tx_hash"><?php esc_html_e( 'Transaction Hash / Signature (optional for instant auto-verification):', 'gabochie-defi' ); ?></label>
				<input type="text" id="gabochie_defi_tx_hash" name="gabochie_defi_tx_hash" placeholder="0x... (EVM) or Solana tx signature" autocomplete="off" />
				<input type="hidden" id="gabochie_defi_chain" name="gabochie_defi_chain" value="polygon" />
				<span class="gd-field-note"><?php esc_html_e( 'If pasted now, our system will verify on-chain immediately upon order placement.', 'gabochie-defi' ); ?></span>
			</div>
		</div>
		<?php
	}

	/**
	 * Process order payment.
	 *
	 * @param int $order_id Order ID.
	 * @return array
	 */
	public function process_payment( $order_id ) {
		if ( ! $this->has_configured_wallet() ) {
			wc_add_notice( __( 'Crypto payments are not available yet. Please choose another payment method.', 'gabochie-defi' ), 'error' );
			return array( 'result' => 'fail' );
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return array( 'result' => 'fail' );
		}

		$tx_hash = isset( $_POST['gabochie_defi_tx_hash'] ) ? sanitize_text_field( wp_unslash( $_POST['gabochie_defi_tx_hash'] ) ) : '';
		$chain   = isset( $_POST['gabochie_defi_chain'] ) ? sanitize_text_field( wp_unslash( $_POST['gabochie_defi_chain'] ) ) : 'polygon';

		if ( ! empty( $tx_hash ) ) {
			$verifier = new Gabochie_DeFi_Verifier();
			$result   = $verifier->verify_transaction( $tx_hash, $chain, $order );

			if ( $result['success'] ) {
				$order->add_order_note( sprintf( __( 'DeFi Payment Instant Verification Passed (%1$s)! TxHash: %2$s', 'gabochie-defi' ), strtoupper( $chain ), $tx_hash ) );
				$order->payment_complete( $tx_hash );
				WC()->cart->empty_cart();

				return array(
					'result'   => 'success',
					'redirect' => $this->get_return_url( $order ),
				);
			}
		}

		// Otherwise mark as on-hold pending manual/on-chain verification
		$order->update_status( 'on-hold', sprintf( __( 'Awaiting DeFi / Crypto payment confirmation on %s chain.', 'gabochie-defi' ), strtoupper( $chain ) ) );
		if ( ! empty( $tx_hash ) ) {
			update_post_meta( $order_id, '_gabochie_defi_pending_tx', $tx_hash );
			update_post_meta( $order_id, '_gabochie_defi_pending_chain', $chain );
		}

		WC()->cart->empty_cart();

		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order ),
		);
	}

	/**
	 * Thank you page instructions.
	 *
	 * @param int $order_id Order ID.
	 */
	public function thankyou_page( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) return;

		if ( $order->is_paid() ) {
			echo '<div class="woocommerce-message">' . esc_html__( 'Payment confirmed on-chain! Your order and services are active.', 'gabochie-defi' ) . '</div>';
			return;
		}

		$pending_tx = get_post_meta( $order_id, '_gabochie_defi_pending_tx', true );
		$chain      = get_post_meta( $order_id, '_gabochie_defi_pending_chain', true );

		?>
		<section class="gd-defi-thankyou-box" style="margin: 2rem 0; padding: 1.5rem; background: #FAF7F2; border: 1px solid #C9A96E; border-radius: 12px;">
			<h3 style="margin-top:0; color:#0D5C52;"><?php esc_html_e( 'DeFi Payment Verification', 'gabochie-defi' ); ?></h3>
			<p><?php esc_html_e( 'If you have sent your transaction, enter your Transaction Hash below for instant automated on-chain verification:', 'gabochie-defi' ); ?></p>
			
			<div style="display:flex; gap: 0.5rem; max-width: 600px; margin-bottom: 1rem;">
				<input type="text" id="gdThankyouTx" placeholder="0x... or Solana TxHash" value="<?php echo esc_attr( $pending_tx ); ?>" style="flex:1; padding:0.6rem 0.9rem; border:1px solid #ccc; border-radius:6px;" />
				<button type="button" id="gdThankyouVerifyBtn" data-order="<?php echo esc_attr( $order_id ); ?>" data-chain="<?php echo esc_attr( $chain ? $chain : 'polygon' ); ?>" style="background:#0D5C52; color:#fff; border:none; padding:0.6rem 1.2rem; border-radius:6px; font-weight:600; cursor:pointer;"><?php esc_html_e( 'Verify On-Chain', 'gabochie-defi' ); ?></button>
			</div>
			<div id="gdThankyouMsg" style="font-size:0.88rem; font-weight:600;"></div>
		</section>
		<script>
		document.getElementById('gdThankyouVerifyBtn')?.addEventListener('click', function() {
			var btn = this;
			var tx = document.getElementById('gdThankyouTx').value;
			var msg = document.getElementById('gdThankyouMsg');
			if (!tx) { msg.style.color = '#b02323'; msg.textContent = 'Please enter a transaction hash.'; return; }
			btn.disabled = true; btn.textContent = 'Verifying...';
			msg.textContent = '';
			var data = new FormData();
			data.append('action', 'gabochie_defi_verify_tx');
			data.append('nonce', '<?php echo wp_create_nonce( 'gabochie_defi_nonce' ); ?>');
			data.append('tx_hash', tx);
			data.append('chain', btn.getAttribute('data-chain') || 'polygon');
			data.append('order_id', btn.getAttribute('data-order'));
			fetch('<?php echo admin_url( 'admin-ajax.php' ); ?>', { method: 'POST', body: data })
				.then(function(r) { return r.json(); })
				.then(function(d) {
					if (d.success) {
						msg.style.color = '#2ea368';
						msg.textContent = d.data.message;
						setTimeout(function() { window.location.reload(); }, 1500);
					} else {
						btn.disabled = false; btn.textContent = 'Verify On-Chain';
						msg.style.color = '#b02323';
						msg.textContent = d.data && d.data.message ? d.data.message : 'Verification failed.';
					}
				}).catch(function() {
					btn.disabled = false; btn.textContent = 'Verify On-Chain';
					msg.style.color = '#b02323';
					msg.textContent = 'Network error. Please try again.';
				});
		});
		</script>
		<?php
	}

	/**
	 * Email instructions.
	 */
	public function email_instructions( $order, $sent_to_admin, $plain_text ) {
		if ( $sent_to_admin || $order->get_payment_method() !== $this->id ) return;

		if ( $this->instructions ) {
			echo wp_kses_post( wpautop( wptexturize( $this->instructions ) ) );
		}
	}
}
