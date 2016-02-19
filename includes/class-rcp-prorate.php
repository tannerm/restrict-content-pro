<?php

RCP_Prorate::get_instance();

/**
 * Class RCP_Prorate
 *
 * Handles member prorating
 *
 * @since 2.5
 */
class RCP_Prorate {

	/**
	 * @var
	 */
	protected static $_instance;

	/**
	 * is this page load a submission
	 *
	 * @var bool
	 */
	public $is_submission = false;

	/**
	 * is this submission for an auto renew membership
	 *
	 * @var bool
	 */
	public $is_auto_renew = false;

	/**
	 * get the subscription id for the registration submission
	 *
	 * @var bool
	 */
	public $subscription_id = false;

	/**
	 * The amount that this account should be credited.
	 *
	 * @var int
	 */
	public $prorate_amount = 0;

	/**
	 * Store the date that the membership expires
	 *
	 * @var string
	 */
	protected $member_expiration = '';

	/**
	 * Only make one instance of the RCP_Prorate
	 *
	 * @return RCP_Prorate
	 */
	public static function get_instance() {
		if ( ! self::$_instance instanceof RCP_Prorate ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Add Hooks and Actions
	 */
	protected function __construct() {

//		add_action( 'init',                                     array( $this, 'setup_submission_vars' ), 99 );
//		add_action( 'wp_ajax_rcp_process_register_form',        array( $this, 'setup_submission_vars' ), 99 );
//		add_action( 'wp_ajax_nopriv_rcp_process_register_form', array( $this, 'setup_submission_vars' ), 99 );

		add_action( 'rcp_before_subscription_form_fields', array( $this, 'add_prorate_message' ) );
		add_action( 'rcp_registration_init',                       array( $this, 'add_prorate_fee'     ) );
//		add_filter( 'rcp_get_level_field',        array( $this, 'maybe_prorate_price'  ), 10, 3 );
//		add_filter( 'rcp_get_level',              array( $this, 'maybe_prorate_level'  ) );
//		add_filter( 'rcp_get_levels',             array( $this, 'maybe_prorate_levels' ) );
//		add_filter( 'rcp_calc_member_expiration', array( $this, 'calc_renewal_expiration' ), 10, 2 );

	}

	public function add_prorate_fee( $registration ) {
		if ( ! $amount = $this->get_prorate_amount() ) {
			return;
		}

		$registration->add_fee( -1 * $amount, __( 'Proration Credit', 'rcp' ) );
	}

	public function add_prorate_message() {
		if ( ! $amount = $this->get_prorate_amount() ) {
			return;
		}
		?>

		<p>If you upgrade or downgrade your account, the new subscription will be prorated up to <?php echo esc_html( rcp_currency_filter( $amount ) ); ?> for the first payment. Prorated prices are shown below.</p>
	<?php
	}

	/**
	 * Determine if this is a submission and if it is an auto renewing subscription
	 * Hooks into the same actions that rcp_process_registration uses and runs just before
	 */
	public function setup_submission_vars() {

		// All subscriptions auto renew if 1
		if ( '1' == rcp_get_auto_renew_behavior() ) {
			$this->is_auto_renew = true;
		}

		if ( empty( $_POST["rcp_register_nonce"] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_POST['rcp_register_nonce'], 'rcp-register-nonce' )  ) {
			return;
		}

		$this->subscription_id = isset( $_POST['rcp_level'] ) ? absint( $_POST['rcp_level'] ) : false;
		$this->is_submission   = true;

		if( '3' == rcp_get_auto_renew_behavior() && isset( $_POST['rcp_auto_renew'] ) ) {
			$this->is_auto_renew = true;
		}

	}

	/**
	 * Get the prorate credit amount for the current user
	 *
	 * @return bool|int|string
	 */
	protected function get_prorate_amount() {

		if ( $this->prorate_amount ) {
			return $this->prorate_amount;
		}

		if ( ! $user_id = get_current_user_id() ) {
			return false;
		}

		$user = new RCP_Member( $user_id );

		$this->prorate_amount = $user->get_prorate_credit_amount();

		return $this->prorate_amount;

	}

	/**
	 * Calculate the time for which the user has paid.
	 * Based off of rcp_calc_member_expiration()
	 *
	 * @param $expiration_object
	 * @param $payment_date_str
	 *
	 * @return bool|string
	 */
	protected function calc_member_term( $expiration_object, $payment_date_str ) {

		// if this subscription has no duration, don't prorate it
		if ( $expiration_object->duration <= 0 ) {
			return false;
		}

		// remove time from date
		$payment_date      = strtotime( $payment_date_str  );
		$payment_date_str  = date( 'Y-m-d', $payment_date );
		$payment_date      = strtotime( $payment_date_str  );

		$last_day          = cal_days_in_month( CAL_GREGORIAN, date( 'n', $payment_date ), date( 'Y', $payment_date ) );
		$expiration_unit   = $expiration_object->duration_unit;
		$expiration_length = $expiration_object->duration;
		$member_term       = date( 'Y-m-d H:i:s', strtotime( $payment_date_str . ' +' . $expiration_length . ' ' . $expiration_unit . ' 23:59:59' ) );

		if( date( 'j', $payment_date ) == $last_day && 'day' != $expiration_unit ) {
			$member_term = date( 'Y-m-d H:i:s', strtotime( $payment_date_str . ' +2 days' ) );
		}

		return $member_term;

	}

	/**
	 * Filter the price field and return a prorated amount if applicable
	 *
	 * @param $value
	 * @param $level_id
	 * @param $field
	 *
	 * @return int|number|string
	 */
	public function maybe_prorate_price( $value, $level_id, $field ) {

		// we only care about the price field
		if ( ! in_array( $field, array( 'price', 'fee' ) ) ) {
			return $value;
		}

		// check for proration amount
		if ( ! $prorate_amount = $this->get_prorate_amount() ) {
			return $value;
		}

		// don't prorate renewals or free levels
		if ( rcp_get_subscription_id() == $level_id || empty( $value ) ) {
			return $value;
		}

		switch( $field ) {
			case 'fee' :

				// if this is a registration and the user is set to auto renew, set a negative
				// fee instead of adjusting the price.
				if ( $this->is_submission && $this->is_auto_renew ) {
					$value = '-' . $prorate_amount;
				}

				break;

			case 'price' :

				// if we are not submitting a auto renew registration, return prorated price
				if ( ! ( $this->is_submission && $this->is_auto_renew ) ) {
					$value = $this->get_prorated_price( $value, $prorate_amount );
				}

				break;

		}

		return $value;

	}

	/**
	 * Prorate level if applicable
	 *
	 * @param $level
	 *
	 * @return mixed
	 */
	public function maybe_prorate_level( $level ) {

		if ( ! $amount = $this->get_prorate_amount() ) {
			return $level;
		}

		if ( rcp_get_subscription_id() == $level->id || empty( $level->price ) ) {
			return $level;
		}

		// if this is an auto renewing item, prorate with a negative fee
		// so we don't mess up subsequent transactions
		if ( $this->is_submission && $this->is_auto_renew ) {

			// if the prorated credit is greater than the subscription price
			// only credit the amount of the subscription price minus a dollar
			if ( abs( $amount ) > abs( $level->price ) ) {
				$level->fee = '-' . number_format(  abs( $level->price ) - 1, 2 );
			} else {
				$level->fee = '-' . $amount;
			}

		} else {
			$level->price = $this->get_prorated_price( $level->price, $amount );
		}

		return $level;

	}

	/**
	 * Prorate levels as applicable
	 *
	 * @param $levels
	 *
	 * @return mixed
	 */
	public function maybe_prorate_levels( $levels ) {

		// do we have a credit?
		if ( ! $amount = $this->get_prorate_amount() ) {
			return $levels;
		}

		foreach( $levels as &$level ) {
			// don't alter renewals
			if ( rcp_get_subscription_id() == $level->id || empty( $level->price ) ) {
				continue;
			}

			// if this is an auto renewing item, prorate with a negative fee
			// so we don't mess up subsequent transactions
			if ( $this->is_submission && $this->is_auto_renew ) {
				$level->fee = '-' . $amount;
			} else {
				$level->price = $this->get_prorated_price( $level->price, $amount );
			}

		}

		return $levels;
	}

	/**
	 * Get the price for prorated subscription levels
	 *
	 * @param $price
	 * @param $prorate_amount
	 *
	 * @return int|number|string
	 */
	protected function get_prorated_price( $price, $prorate_amount ) {

		$price = abs( $price ) - $prorate_amount;

		// we have to charge something
		if ( $price < 0 ) {
			$price = 1;
		}

		$price = number_format( (float) $price, 2 );

		return $price;

	}

	/**
	 * If renewing an existing subscription, add more time to the end of the subscription
	 *
	 * @param $member_expires
	 * @param $expiration_object
	 *
	 * @return bool|string
	 */
	public function calc_renewal_expiration( $member_expires, $expiration_object ) {

		// make sure we are processing a registration
		if ( ! $this->subscription_id ) {
			return $member_expires;
		}

		// make sure this user has an active subscription
		if ( ! rcp_is_active() ) {
			return $member_expires;
		}

		// if we are not renewing a subscription, process normally
		if ( rcp_get_subscription_id() != $this->subscription_id ) {
			return $member_expires;
		}

		if ( 'none' == $member_expires ) {
			return $member_expires;
		}

		if ( ! $prev_expiration = rcp_get_expiration_date() ) {
			return $member_expires;
		}

		add_action( 'rcp_insert_payment', array( $this, 'set_member_expiration'));
		$this->member_expiration =  $this->calc_member_term( $expiration_object, $prev_expiration );

		return $prev_expiration;

	}

	/**
	 * The transaction was successful, set the new expiration date
	 */
	public function set_member_expiration() {
		rcp_set_expiration_date( get_current_user_id(), $this->member_expiration );
	}

}