<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WC_Invoice_Order_Email' ) ) :

/**
 * Customer Invoice.
 *
 * An email sent to the customer via admin.
 *
 * @class       WC_Invoice_Order_Email
 * @version     2.3.0
 * @package     WooCommerce/Classes/Emails
 * @author      WooThemes
 * @extends     WC_Email
 */
class WC_Invoice_Order_Email extends WC_Email {

	/**
	 * Strings to find in subjects/headings.
	 *
	 * @var array
	 */
	public $find;

	/**
	 * Strings to replace in subjects/headings.
	 *
	 * @var array
	 */
	public $replace;

	/**
	 * Constructor.
	 */
	function __construct() {

		$this->id             = 'customer_invoice_order';
		$this->title          = __( 'Checkout Invoice Gateway', 'woocommerce' );
		$this->description    = __( 'Customer invoice emails can be sent to customers containing their order information and payment links when create order with invoice payment gateway.', 'woocommerce' );

		$this->template_html  = 'emails/customer-invoice-order.php';
		$this->template_plain = 'emails/plain/customer-invoice-order.php';

		$this->subject        = __( 'Invoice for order {order_number} from {order_date}', 'woocommerce');
		$this->heading        = __( 'Invoice for order {order_number}', 'woocommerce');

		$this->subject_paid   = __( 'Your {site_title} order from {order_date}', 'woocommerce');
		$this->heading_paid   = __( 'Order {order_number} details', 'woocommerce');

		// Trigger on new paid orders
		add_action( 'order_create_by_invoice_gateway', array( $this, 'trigger' ) );


		// Call parent constructor
		parent::__construct();

		$this->template_base = IG_PLUGIN_PATH . '/templates/';

		$this->customer_email = true;
		// $this->manual         = true;
		$this->heading_paid   = $this->get_option( 'heading_paid', $this->heading_paid );
		$this->subject_paid   = $this->get_option( 'subject_paid', $this->subject_paid );
	}

	/**
	 * Trigger.
	 *
	 * @param int|WC_Order $order
	 */
	function trigger( $order ) {

		if ( ! is_object( $order ) ) {
			$order = wc_get_order( absint( $order ) );
		}

		if ( $order ) {
			$this->object                  = $order;
			$this->recipient               = $this->object->billing_email;

			$this->find['order-date']      = '{order_date}';
			$this->find['order-number']    = '{order_number}';

			$this->replace['order-date']   = date_i18n( wc_date_format(), strtotime( $this->object->order_date ) );
			$this->replace['order-number'] = $this->object->get_order_number();
		}

		if ( ! $this->get_recipient() ) {
			return;
		}

		$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
	}

	/**
	 * Get email subject.
	 *
	 * @access public
	 * @return string
	 */
	function get_subject() {
		if ( $this->object->has_status( array( 'completed' ) ) ) {
			return apply_filters( 'woocommerce_email_subject_customer_invoice_order_paid', $this->format_string( $this->subject_paid ), $this->object );
		} else {
			return apply_filters( 'woocommerce_email_subject_customer_invoice_order', $this->format_string( $this->subject ), $this->object );
		}
	}

	/**
	 * Get email heading.
	 *
	 * @access public
	 * @return string
	 */
	function get_heading() {
		if ( $this->object->has_status( array( 'completed' ) ) ) {
			return apply_filters( 'woocommerce_email_heading_customer_invoice_order_paid', $this->format_string( $this->heading_paid ), $this->object );
		} else {
			return apply_filters( 'woocommerce_email_heading_customer_invoice_order', $this->format_string( $this->heading ), $this->object );
		}
	}

	/**
	 * Get content html.
	 *
	 * @access public
	 * @return string
	 */
	function get_content_html() {
		return wc_get_template_html( $this->template_html, array(
			'order'         => $this->object,
			'email_heading' => $this->get_heading(),
			'sent_to_admin' => false,
			'plain_text'    => false,
			'email'			=> $this
		), WC()->template_path(), IG_PLUGIN_PATH . '/templates/' );
	}

	/**
	 * Get content plain.
	 *
	 * @access public
	 * @return string
	 */
	function get_content_plain() {
		return wc_get_template_html( $this->template_plain, array(
			'order'         => $this->object,
			'email_heading' => $this->get_heading(),
			'sent_to_admin' => false,
			'plain_text'    => true,
			'email'			=> $this
		), WC()->template_path(), IG_PLUGIN_PATH . '/templates/' );
	}

	/**
	 * Initialise settings form fields.
	 */
	function init_form_fields() {
		$this->form_fields = array(
			'enabled'    => array(
				'title'   => 'Enable/Disable',
				'type'    => 'checkbox',
				'label'   => 'Enable this email notification',
				'default' => 'yes'
			),
			'subject' => array(
				'title'         => __( 'Email Subject', 'woocommerce' ),
				'type'          => 'text',
				'description'   => sprintf( __( 'Defaults to <code>%s</code>', 'woocommerce' ), $this->subject ),
				'placeholder'   => '',
				'default'       => '',
				'desc_tip'      => true
			),
			'heading' => array(
				'title'         => __( 'Email Heading', 'woocommerce' ),
				'type'          => 'text',
				'description'   => sprintf( __( 'Defaults to <code>%s</code>', 'woocommerce' ), $this->heading ),
				'placeholder'   => '',
				'default'       => '',
				'desc_tip'      => true
			),
			'subject_paid' => array(
				'title'         => __( 'Email Subject (paid)', 'woocommerce' ),
				'type'          => 'text',
				'description'   => sprintf( __( 'Defaults to <code>%s</code>', 'woocommerce' ), $this->subject_paid ),
				'placeholder'   => '',
				'default'       => '',
				'desc_tip'      => true
			),
			'heading_paid' => array(
				'title'         => __( 'Email Heading (paid)', 'woocommerce' ),
				'type'          => 'text',
				'description'   => sprintf( __( 'Defaults to <code>%s</code>', 'woocommerce' ), $this->heading_paid ),
				'placeholder'   => '',
				'default'       => '',
				'desc_tip'      => true
			),
			'email_type' => array(
				'title'         => __( 'Email Type', 'woocommerce' ),
				'type'          => 'select',
				'description'   => __( 'Choose which format of email to send.', 'woocommerce' ),
				'default'       => 'html',
				'class'         => 'email_type wc-enhanced-select',
				'options'       => $this->get_email_type_options(),
				'desc_tip'      => true
			)
		);
	}
}

endif;

return new WC_Invoice_Order_Email();