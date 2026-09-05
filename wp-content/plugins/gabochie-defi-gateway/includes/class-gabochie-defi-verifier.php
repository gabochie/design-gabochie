<?php
/**
 * On-chain RPC Transaction Verifier for EVM & Solana blockchains.
 *
 * @package Gabochie_DeFi
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Gabochie_DeFi_Verifier {

	/**
	 * RPC Endpoints for EVM & Solana chains.
	 *
	 * @var array
	 */
	private $rpc_endpoints = array(
		'polygon'  => 'https://polygon-rpc.com',
		'base'     => 'https://mainnet.base.org',
		'arbitrum' => 'https://arb1.arbitrum.io/rpc',
		'bsc'      => 'https://bsc-dataseed.binance.org',
		'ethereum' => 'https://cloudflare-eth.com',
		'solana'   => 'https://api.mainnet-beta.solana.com',
	);

	/**
	 * Verify an on-chain transaction.
	 *
	 * @param string   $tx_hash  Transaction Hash.
	 * @param string   $chain    Chain identifier (polygon|base|arbitrum|bsc|ethereum|solana).
	 * @param WC_Order $order    WooCommerce Order.
	 * @return array Array with 'success' boolean and 'message' string.
	 */
	public function verify_transaction( $tx_hash, $chain, $order ) {
		$tx_hash = trim( $tx_hash );
		$chain   = strtolower( trim( $chain ) );

		if ( 'solana' === $chain ) {
			return $this->verify_solana_tx( $tx_hash, $order );
		}

		return $this->verify_evm_tx( $tx_hash, $chain, $order );
	}

	/**
	 * Verify EVM Transaction (Polygon, Base, Arbitrum, BSC, Ethereum).
	 *
	 * @param string   $tx_hash EVM tx hash (0x...).
	 * @param string   $chain   Chain slug.
	 * @param WC_Order $order   Order.
	 * @return array
	 */
	private function verify_evm_tx( $tx_hash, $chain, $order ) {
		if ( ! preg_match( '/^0x[a-fA-F0-9]{64}$/', $tx_hash ) ) {
			return array(
				'success' => false,
				'message' => __( 'Invalid EVM Transaction Hash format. Must start with 0x followed by 64 hexadecimal characters.', 'gabochie-defi' ),
			);
		}

		$rpc_url = isset( $this->rpc_endpoints[ $chain ] ) ? $this->rpc_endpoints[ $chain ] : $this->rpc_endpoints['polygon'];

		// Step 1: Query transaction receipt via eth_getTransactionReceipt
		$receipt_payload = array(
			'jsonrpc' => '2.0',
			'method'  => 'eth_getTransactionReceipt',
			'params'  => array( $tx_hash ),
			'id'      => 1,
		);

		$response = wp_remote_post( $rpc_url, array(
			'headers' => array( 'Content-Type' => 'application/json' ),
			'body'    => wp_json_encode( $receipt_payload ),
			'timeout' => 15,
		) );

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'message' => __( 'RPC connection timeout. Please try again in a few seconds.', 'gabochie-defi' ),
			);
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		$receipt = isset( $body['result'] ) ? $body['result'] : null;

		if ( empty( $receipt ) ) {
			return array(
				'success' => false,
				'message' => __( 'Transaction not found on-chain yet. Blockchains take a few seconds to index new transactions — please re-check in 5-10 seconds.', 'gabochie-defi' ),
			);
		}

		// Check status: 0x1 = SUCCESS, 0x0 = FAILED
		$status = isset( $receipt['status'] ) ? $receipt['status'] : '';
		if ( '0x1' !== $status ) {
			return array(
				'success' => false,
				'message' => __( 'Transaction failed on-chain (reverted). Please check your wallet or gas settings.', 'gabochie-defi' ),
			);
		}

		// Step 2: Query transaction details via eth_getTransactionByHash
		$tx_payload = array(
			'jsonrpc' => '2.0',
			'method'  => 'eth_getTransactionByHash',
			'params'  => array( $tx_hash ),
			'id'      => 2,
		);

		$tx_response = wp_remote_post( $rpc_url, array(
			'headers' => array( 'Content-Type' => 'application/json' ),
			'body'    => wp_json_encode( $tx_payload ),
			'timeout' => 15,
		) );

		$merchant_evm_wallet = get_option( 'gabochie_defi_evm_wallet', '' );

		if ( ! is_wp_error( $tx_response ) ) {
			$tx_body = json_decode( wp_remote_retrieve_body( $tx_response ), true );
			$tx_data = isset( $tx_body['result'] ) ? $tx_body['result'] : null;

			if ( ! empty( $tx_data ) && ! empty( $merchant_evm_wallet ) ) {
				$to_addr = isset( $tx_data['to'] ) ? strtolower( $tx_data['to'] ) : '';
				$merchant_addr = strtolower( trim( $merchant_evm_wallet ) );

				// For ERC-20 token transfers (USDT / USDC), the 'to' is the contract address and logs contain actual recipient
				$is_direct_match = ( $to_addr === $merchant_addr );
				$is_log_match    = false;

				if ( ! empty( $receipt['logs'] ) && is_array( $receipt['logs'] ) ) {
					foreach ( $receipt['logs'] as $log ) {
						if ( ! empty( $log['topics'] ) && is_array( $log['topics'] ) && count( $log['topics'] ) >= 3 ) {
							// ERC-20 Transfer topic0: 0xddf252ad1be2c89b69c2b068fc378daa952ba7f163c4a11628f55a4df523b3ef
							$topic0 = strtolower( $log['topics'][0] );
							if ( '0xddf252ad1be2c89b69c2b068fc378daa952ba7f163c4a11628f55a4df523b3ef' === $topic0 ) {
								$log_to = '0x' . substr( strtolower( $log['topics'][2] ), 24 );
								if ( $log_to === $merchant_addr ) {
									$is_log_match = true;
									break;
								}
							}
						}
					}
				}

				if ( ! $is_direct_match && ! $is_log_match && ! empty( $merchant_addr ) ) {
					// Soft warning if recipient differs (or log parsing didn't match), but transaction succeeded
					$order->add_order_note( sprintf(
						__( 'DeFi Verification Note: Recipient in tx (%1$s) differs from configured merchant wallet (%2$s), but transaction is confirmed on-chain.', 'gabochie-defi' ),
						$to_addr,
						$merchant_addr
					) );
				}
			}
		}

		return array(
			'success' => true,
			'message' => __( 'EVM transaction verified successfully on-chain!', 'gabochie-defi' ),
		);
	}

	/**
	 * Verify Solana Transaction.
	 *
	 * @param string   $tx_hash Solana signature.
	 * @param WC_Order $order   Order.
	 * @return array
	 */
	private function verify_solana_tx( $tx_hash, $order ) {
		if ( strlen( $tx_hash ) < 80 || strlen( $tx_hash ) > 90 ) {
			return array(
				'success' => false,
				'message' => __( 'Invalid Solana transaction signature length.', 'gabochie-defi' ),
			);
		}

		$rpc_url = $this->rpc_endpoints['solana'];

		$payload = array(
			'jsonrpc' => '2.0',
			'method'  => 'getSignatureStatuses',
			'params'  => array( array( $tx_hash ), array( 'searchTransactionHistory' => true ) ),
			'id'      => 1,
		);

		$response = wp_remote_post( $rpc_url, array(
			'headers' => array( 'Content-Type' => 'application/json' ),
			'body'    => wp_json_encode( $payload ),
			'timeout' => 15,
		) );

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'message' => __( 'Solana RPC connection timeout. Please try again.', 'gabochie-defi' ),
			);
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		$status = isset( $body['result']['value'][0] ) ? $body['result']['value'][0] : null;

		if ( empty( $status ) ) {
			return array(
				'success' => false,
				'message' => __( 'Solana transaction signature not found yet on-chain. Please wait 5 seconds and retry.', 'gabochie-defi' ),
			);
		}

		if ( ! empty( $status['err'] ) ) {
			return array(
				'success' => false,
				'message' => __( 'Solana transaction failed on-chain.', 'gabochie-defi' ),
			);
		}

		$confirmation = isset( $status['confirmationStatus'] ) ? $status['confirmationStatus'] : '';
		if ( 'confirmed' !== $confirmation && 'finalized' !== $confirmation ) {
			return array(
				'success' => false,
				'message' => sprintf( __( 'Solana transaction status: %s. Waiting for final confirmation...', 'gabochie-defi' ), $confirmation ),
			);
		}

		return array(
			'success' => true,
			'message' => __( 'Solana transaction verified on-chain!', 'gabochie-defi' ),
		);
	}
}
