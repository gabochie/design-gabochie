<section class="woocommerce-order-details">
  <h2 class="woocommerce-order-details__title" style="margin-top: 50px !important; margin-bottom: 5px !important;"><?php echo esc_html($detailHeader); ?></h2>
  <ul class="woocommerce-order-overview woocommerce-thankyou-order-details order_details" style="padding-top:20px !important; padding-left: 20px !important;">
    <?php if (!empty($detailResultText)): ?>
      <li class="woocommerce-order-overview__order order">
        <?php echo esc_html__('Payment Status:', 'wc-gateway-expresspay'); ?><strong><?php echo esc_html($detailResultText); ?></strong>
      </li>
    <?php endif; ?>

    <?php if (!empty($detailOrderId)): ?>
      <li class="woocommerce-order-overview__order order">
        <?php echo esc_html__('Order ID:', 'wc-gateway-expresspay'); ?><strong><?php echo esc_html($detailOrderId); ?></strong>
      </li>
    <?php endif; ?>

    <?php if (!empty($detailCurrency)): ?>
      <li class="woocommerce-order-overview__order order">
        <?php echo esc_html__('Currency:', 'wc-gateway-expresspay'); ?><strong><?php echo esc_html($detailCurrency); ?></strong>
      </li>
    <?php endif; ?>

    <?php if (!empty($detailAmount)): ?>
      <li class="woocommerce-order-overview__order order">
        <?php echo esc_html__('Amount:', 'wc-gateway-expresspay'); ?><strong><?php echo esc_html(number_format($detailAmount, 2, '.', ',')); ?></strong>
      </li>
    <?php endif; ?>

    <?php if (!empty($detailPaymentOption)): ?>
      <li class="woocommerce-order-overview__order order">
        <?php echo esc_html__('Payment Option:', 'wc-gateway-expresspay'); ?><strong><?php echo esc_html($detailPaymentOption); ?></strong>
      </li>
    <?php endif; ?>

    <?php if (!empty($detailTransactionID)): ?>
      <li class="woocommerce-order-overview__order order">
        <?php echo esc_html__('Transaction ID:', 'wc-gateway-expresspay'); ?><strong><?php echo esc_html($detailTransactionID); ?></strong>
      </li>
    <?php endif; ?>

    <?php if (!empty($detailDateProcessed)): ?>
      <li class="woocommerce-order-overview__order order">
        <?php echo esc_html__('Date Processed:', 'wc-gateway-expresspay'); ?><strong><?php echo esc_html($detailDateProcessed); ?></strong>
      </li>
    <?php endif; ?>
  </ul>
</section>
