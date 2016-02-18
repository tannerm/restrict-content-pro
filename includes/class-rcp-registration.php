<?php

class RCP_Cart {

	/**
	 * Store the subscription for the cart
	 *
	 * @since 2.5
	 * @var int
	 */
	protected $subscription = 0;

	/**
	 * Store the discounts for the cart
	 *
	 * @since 2.5
	 * @var array
	 */
	protected $discounts = array();

	/**
	 * Store the fees/credits for the cart. Credits are negative fees.
	 *
	 * @since 2.5
	 * @var array
	 */
	protected $fees = array();

	public function __construct( $level_id, $discount = null ) {
		$this->set_subscription( $level_id );

		if ( $discount ) {
			$this->add_discount( $discount );
		}

		do_action( 'rcp_cart_init', $this );
	}

	/**
	 * Set the subscription for this cart
	 *
	 * @since 2.5
	 * @param $subscription_id
	 *
	 * @return bool
	 */
	public function set_subscription( $subscription_id ) {
		if ( ! $subscription = rcp_get_subscription_details( $subscription_id ) ) {
			return false;
		}

		$this->subscription = $subscription_id;

		if ( $subscription->fee ) {
			$description = ( $subscription->fee > 0 ) ? __( 'Signup Fee', 'rcp' ) : __( 'Signup Credit', 'rcp' );
			$this->add_fee( $subscription->fee, $description );
		}

		return true;
	}

	/**
	 * Get cart subscription
	 *
	 * @since 2.5
	 * @return int
	 */
	public function get_subscription() {
		return $this->subscription;
	}

	/**
	 * Add discount to the cart
	 *
	 * @since      2.5
	 * @param      $code
	 * @param bool $recurring
	 *
	 * @return bool
	 */
	public function add_discount( $code, $recurring = true ) {
		if ( ! rcp_validate_discount( $code, $this->subscription ) ) {
			return false;
		}

		$this->discounts[ $code ] = $recurring;
		return true;
	}

	/**
	 * Get cart discounts
	 *
	 * @since 2.5
	 * @return array|bool
	 */
	public function get_discounts() {
		if ( empty( $this->discounts ) ) {
			return false;
		}

		return $this->discounts;
	}

	/**
	 * Add fee to the cart. Use negative fee for credit.
	 *
	 * @since      2.5
	 * @param      $amount
	 * @param null $description
	 * @param bool $recurring
	 *
	 * @return bool
	 */
	public function add_fee( $amount, $description = null, $recurring = false ) {

		$fee = array(
			'amount'     => number_format( (float) $amount, 2 ),
			'description'=> sanitize_text_field( $description ),
			'recurring'  => (bool) $recurring,
		);

		$id = md5( serialize( $fee ) );

		if ( isset( $this->fees[ $id ] ) ) {
			return false;
		}

		$this->fees[ $id ] = apply_filters( 'rcp_cart_add_fee', $fee, $this );

		return true;
	}

	/**
	 * Get cart fees
	 *
	 * @since 2.5
	 * @return array|bool
	 */
	public function get_fees() {
		if ( empty( $this->fees ) ) {
			return false;
		}

		return $this->fees;
	}

	/**
	 * Get the total number of fees
	 *
	 * @since 2.5
	 * @param null $total
	 *
	 * @return int
	 */
	public function get_total_fees( $total = null, $only_recurring = false ) {

		if ( ! $this->get_fees() ) {
			return 0;
		}

		$fees = 0;

		foreach( $this->get_fees() as $fee ) {
			if ( $only_recurring && ! $fee['recurring'] ) {
				continue;
			}

			$fees += $fee['amount'];
		}

		return $fees;

	}

	/**
	 * Get the total discounts
	 *
	 * @since 2.5
	 * @param null $total
	 *
	 * @return int|mixed|void
	 */
	public function get_total_discounts( $total = null, $only_recurring = false ) {

		if ( ! $cart_discounts = $this->get_discounts() ) {
			return 0;
		}

		if ( ! $total ) {
			$total = rcp_get_subscription_price( $this->subscription );
		}

		$original_total = $total;

		foreach( $cart_discounts as $cart_discount => $recurring ) {
			if ( $only_recurring && ! $recurring ) {
				continue;
			}

			$discounts    = new RCP_Discounts();
			$discount_obj = $discounts->get_by( 'code', $cart_discount );

			if ( is_object( $discount_obj ) ) {
				// calculate the after-discount price
				$total = $discounts->calc_discounted_price( $total, $discount_obj->amount, $discount_obj->unit );
			}
		}

		return apply_filters( 'rcp_cart_get_total_discounts', (float) ( $original_total - $total ), $original_total, $this );

	}

	/**
	 * Get the cart total
	 *
	 * @since 2.5
	 * @return mixed|void
	 */
	public function get_total() {

		$total = rcp_get_subscription_price( $this->subscription );
		$total -= $this->get_total_discounts( $total );
		$total += $this->get_total_fees( $total );

		if ( 0 > $total ) {
			$total = 0;
		}

		return apply_filters( 'rcp_cart_get_total', number_format( (float) $total, 2 ), $this );

	}

	public function get_recurring_total() {

		$total = rcp_get_subscription_price( $this->subscription );
		$total -= $this->get_total_discounts( $total, true );
		$total += $this->get_total_fees( $total, true );

		if ( 0 > $total ) {
			$total = 0;
		}

		return apply_filters( 'rcp_cart_get_recurring_total', number_format( (float) $total, 2 ), $this );

	}


}