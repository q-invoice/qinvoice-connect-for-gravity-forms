<?php

GFForms::include_feed_addon_framework();

class GFQinvoiceConnect extends GFFeedAddOn {

	protected $_version = GF_QINVOICECONNECT_VERSION;
	protected $_min_gravityforms_version = '1.9.1';
	protected $_slug = 'gravityforms-qinvoice-connect';
	protected $_path = 'gravityforms-qinvoice-connect/qinvoiceconnect.php';
	protected $_full_path = __FILE__;
	protected $_url = 'http://www.q-invoice.com';
	protected $_title = 'Gravity Forms Qinvoice Connect Add-On';
	protected $_short_title = 'Qinvoice';

	// Members plugin integration
	protected $_capabilities = array( 'gravityforms_qinvoiceconnect', 'gravityforms_qinvoiceconnect_uninstall' );

	// Permissions
	protected $_capabilities_settings_page = 'gravityforms_qinvoiceconnect';
	protected $_capabilities_form_settings = 'gravityforms_qinvoiceconnect';
	protected $_capabilities_uninstall = 'gravityforms_qinvoiceconnect_uninstall';
	protected $_enable_rg_autoupgrade = false;

	private static $_instance = null;

	public static function get_instance() {
		if ( self::$_instance == null ) {
			self::$_instance = new GFQinvoiceConnect();
		}

		return self::$_instance;
		
	}

	public function init() {
		parent::init();
	}

	public function init_ajax() {
		parent::init_ajax();
	}

	public function init_admin() {
		add_action( 'admin_init', array( $this, 'insert_version_data' ) );
		add_filter( 'plugin_row_meta', array( $this, 'add_support_links' ), 10, 2 );
		parent::init_admin();
	}

	function insert_version_data(){
		$update_info = get_transient( 'gform_update_info' );
		if( ! $update_info )
			return;
		if(is_object($update_info)){
			$body = json_decode( $update_info->body );
		}else{
			$body = json_decode( $update_info['body'] );
		}
		if( isset( $body->offerings->{$this->_slug} ) )
			return;
		// add qinvoice to the list
		$gfqinvoiceconnect = new stdClass();
		$gfqinvoiceconnect->is_available = true;
		$gfqinvoiceconnect->version = $this->_version;
		$gfqinvoiceconnect->url = $this->_url;
		$body->offerings->{$this->_slug} = $gfqinvoiceconnect;

		if(is_object($update_info)){
			$update_info->body = json_encode( $body );
		}else{
			$update_info['body'] = json_encode( $body );
		}		
		set_transient( 'gform_update_info', $update_info, DAY_IN_SECONDS );
	}

	/**
	 * Add various support links to plugin page
	 * after meta (version, authors, site)
	 */
	public function add_support_links( $links, $file ) {
		if ( !current_user_can( 'install_plugins' ) ) {
			return $links;
		}
	
		if ( $file == GF_QinvoiceConnect_Bootstrap::$_plugin_basename) {
			$links[] = '<a href="mailto:support@q-invoice.com" target="_blank" title="' . __( 'Get support', 'woocommerce-qinvoice-connect-pro' ) . '">' . __( 'Get support', 'gravityforms-qinvoice-connect' ) . '</a>';
			$links[] = '<a href="https://wordpress.org/support/view/plugin-reviews/qinvoice-connect-for-gravity-forms?filter=5#postform" target="_blank" title="' . __( 'Leave a review', 'gravityforms-qinvoice-connect' ) . '">' . __( 'Leave a review', 'woocommerce-qinvoice-connect-pro' ) . '</a>';
		}

		return $links;
	}

	function get_action_links() {
		$feed_id  = '_id_';
		$edit_url = add_query_arg( array( 'fid' => $feed_id ) );
		$duplicate_url = add_query_arg( array( 'duplicate_fid' => $feed_id, 'fid' => 0 ) );
		$links    = array(
			'edit'   => '<a title="' . esc_attr__( 'Edit this feed', 'gravityforms-qinvoice-connect' ) . '" href="' . esc_url( $edit_url ) . '">' . esc_html__( 'Edit', 'gravityforms-qinvoice-connect' ) . '</a>',
			'duplicate'   => '<a title="' . esc_attr__( 'Duplicate this feed', 'gravityforms-qinvoice-connect' ) . '" href="' . esc_url( $duplicate_url ) . '">' . esc_html__( 'Duplicate', 'gravityforms-qinvoice-connect' ) . '</a>',
			'delete' => '<a title="' . esc_attr__( 'Delete this feed', 'gravityforms-qinvoice-connect' ) . '" class="submitdelete" onclick="javascript: if(confirm(\'' . esc_js( __( 'WARNING: You are about to delete this item.', 'gravityforms-qinvoice-connect' ) ) . esc_js( __( "'Cancel' to stop, 'OK' to delete.", 'gravityforms-qinvoice-connect' ) ) . '\')){ gaddon.deleteFeed(\'' . esc_js( $feed_id ) . '\'); }" style="cursor:pointer;">' . esc_html__( 'Delete', 'gravityforms-qinvoice-connect' ) . '</a>'
		);

		return $links;
	}

	function show_entry_options($form, $lead){
		?>
		<script type="text/javascript">
			function ResendRequest() {
				jQuery('#please_wait_container_invoice').fadeIn();

				jQuery.post(ajaxurl, {
						action                 : "gf_resend_request",
						gf_resend_notifications: '<?php echo wp_create_nonce( 'gf_resend_request' ); ?>',
						leadId                : '<?php echo absint( $lead['id'] ); ?>',
						formId                 : '<?php echo absint( $form['id'] ); ?>'
					},
					function (response) {
						if (response) {
							displayMessage(response, "error", "#invoice_container");
						} else {
							displayMessage(<?php echo json_encode( esc_html__( 'Notifications were resent successfully.', 'gravityforms' ) ); ?>, "updated", "#notifications_container" );
						}

						jQuery('#please_wait_container_invoice').hide();
						setTimeout(function () {
							jQuery('#invoice_container').find('.message').slideUp();
						}, 5000);
					}
				);
			}
		</script>
		<?php
		echo '<div id="invoicediv" class="stuffbox">
			<h3 class="hndle" style="cursor:default;">
                <span>'. __('Q-invoice','gravityforms-qinvoice-connect') .'</span>
			</h3>

			<div class="inside">
				<div id="invoice_container" class="submitbox">
						<div id="gf_invoice_date" class="gf_invoice_detail">
							'. __('Created') .':
							<span id="gform_invoice_date">'. gform_get_meta($lead['id'],'invoice_created') .'</span>
						</div>	
						<br />
						<input type="button" name="request_resend" value="'. __('Resend request','gravityforms-qinvoice-connect') .'" class="button" style="" onclick="ResendRequest();">
						<span id="please_wait_container_invoice" style="margin-left: 5px; display:none;">
								<i class="gficon-gravityforms-spinner-icon gficon-spin"></i> '. __('Resending...','gravityforms-qinvoice-connect').'</span>
					</div>

			</div>
		</div>';
	}

	public function form_settings( $form ) {
		if ( ! $this->_multiple_feeds || $this->is_detail_page() ) {

			// feed edit page
			$feed_id = $this->_multiple_feeds ? $this->get_current_feed_id() : $this->get_default_feed_id( $form['id'] );
			if(!isset( $_GET['duplicate_fid']) || rgpost( 'gform-settings-save' )){
				$this->feed_edit_page( $form, $feed_id );
			}else{
				$feed_id = rgget( 'duplicate_fid' );
				$this->feed_copy_page( $form, $feed_id );
				rgempty( 'duplicate_fid' );
			}
		} else {
			// feed list UI
			$this->feed_list_page( $form );
		}
	}

	public function add_form_settings_menu( $tabs, $form_id ) {

		$tabs[] = array( 'name' => $this->_slug, 'label' => $this->get_short_title(), 'query' => array( 'fid' => null, 'duplicate_fid' => null ) );

		return $tabs;
	}

	protected function feed_copy_page( $form, $feed_id ) {

		$original_feed_id = $feed_id;
		$this->_current_feed_id = 0; //So that current feed functions work when creating a new feed

		?>
		<script type="text/javascript">
			<?php GFFormSettings::output_field_scripts() ?>
		</script>

		<h3><span><?php echo $this->feed_settings_title() ?></span></h3>

		<?php

		$feed = $this->get_feed( $original_feed_id );

		$this->set_settings( $feed['meta'] );

		GFCommon::display_admin_message();

		$this->render_settings( $this->get_feed_settings_fields( $form ) );

	}

	// ------- Plugin settings -------

	public function plugin_settings_fields() {
		return array(
			array(
				'title'       => __( 'Qinvoice Connect Settings', 'gravityforms-qinvoice-connect' ),
				'description' => '',
				'fields'      => array(
					array(
						'name'              => 'api_url',
						'label'             => __( 'API URL', 'gravityforms-qinvoice-connect' ),
						'type'              => 'text',
						'class'             => 'medium',
						'feedback_callback' => ''
					),
					array(
						'name'              => 'api_username',
						'label'             => __( 'API username', 'gravityforms-qinvoice-connect' ),
						'type'              => 'text',
						'class'             => 'small',
						'feedback_callback' => ''
					),
					array(
						'name'              => 'api_password',
						'label'             => __( 'API password', 'gravityforms-qinvoice-connect' ),
						'type'              => 'text',
						'class'             => 'small',
						'feedback_callback' => ''
					),
				)
			),
		);
	}

	public function feed_settings_fields() {

		$default_fields = array(
			array(
				'name'     => 'name',
				'label'    => __( 'Name', 'gravityforms-qinvoice-connect' ),
				'type'     => 'text',
				'required' => true,
				'class'    => 'medium',
				'tooltip'  => '<h6>' . __( 'Name', 'gravityforms-qinvoice-connect' ) . '</h6>' . __( 'Enter a feed name to uniquely identify this setup.', 'gravityforms-qinvoice-connect' ),
			),
			array(
				'name'     => 'layout_code',
				'label'    => __( 'Layout code', 'gravityforms-qinvoice-connect' ),
				'type'     => 'text',
				'required' => true,
				'class'    => 'medium',
				'tooltip'  => '<h6>' . __( 'Layout code', 'gravityforms-qinvoice-connect' ) . '</h6>' . __( 'Enter a feed name to uniquely identify this setup.', 'gravityforms-qinvoice-connect' ),
			),

			array(
				'name'     => 'customer_email',
				'label'    => __( 'Email', 'gravityforms-qinvoice-connect' ),
				'type'     => 'select',
				'choices'  => $this->get_field_map_choices( rgget( 'id' ), 'email' ),
				'required' => true,
			),
			array(
				'name'     => 'organization',
				'label'    => __( 'Organization', 'gravityforms-qinvoice-connect' ),
				'type'     => 'select',
				'choices'  => $this->get_field_map_choices( rgget( 'id' ) ),
				'required' => false,
			),
			array(
				'name'     => 'firstname',
				'label'    => __( 'First Name', 'gravityforms-qinvoice-connect' ),
				'type'     => 'select',
				'choices'  => $this->get_field_map_choices( rgget( 'id' ) ),
				'required' => true,
			),
			array(
				'name'     => 'prefix',
				'label'    => __( 'Prefix', 'gravityforms-qinvoice-connect' ),
				'type'     => 'select',
				'choices'  => $this->get_field_map_choices( rgget( 'id' ) ),
				'required' => false,
			),
			array(
				'name'     => 'lastname',
				'label'    => __( 'Last Name', 'gravityforms-qinvoice-connect' ),
				'type'     => 'select',
				'choices'  => $this->get_field_map_choices( rgget( 'id' ) ),
				'required' => true,
			),
			array(
				'name'     => 'address',
				'label'    => __( 'Address', 'gravityforms-qinvoice-connect' ),
				'type'     => 'select',
				'choices'  => $this->get_field_map_choices( rgget( 'id' ) ),
				'required' => false,
			),
			array(
				'name'     => 'address2',
				'label'    => __( 'Address 2', 'gravityforms-qinvoice-connect' ),
				'type'     => 'select',
				'choices'  => $this->get_field_map_choices( rgget( 'id' ) ),
				'required' => false,
			),
			array(
				'name'     => 'zipcode',
				'label'    => __( 'Zipcode', 'gravityforms-qinvoice-connect' ),
				'type'     => 'select',
				'choices'  => $this->get_field_map_choices( rgget( 'id' ) ),
				'required' => false,
			),
			array(
				'name'     => 'city',
				'label'    => __( 'City', 'gravityforms-qinvoice-connect' ),
				'type'     => 'select',
				'choices'  => $this->get_field_map_choices( rgget( 'id' ) ),
				'required' => false,
			),
			array(
				'name'     => 'country',
				'label'    => __( 'Country', 'gravityforms-qinvoice-connect' ),
				'type'     => 'select',
				'choices'  => $this->get_field_map_choices( rgget( 'id' ) ),
				'required' => false,
			),
			array(
				'name'     => 'phone',
				'label'    => __( 'Phone', 'gravityforms-qinvoice-connect' ),
				'type'     => 'select',
				'choices'  => $this->get_field_map_choices( rgget( 'id' ) ),
				'required' => false,
			),
			array(
				'name'     => 'add_delivery',
				'label'    => __( 'Delivery address', 'gravityforms-qinvoice-connect' ),
				'type'     => 'select',
				'required' => false,
				'choices' => array(
                        array( 'id' => 'none', 'label' => __('None'), 'value'  => 'none'),
                        array( 'id' => 'invoice', 'label' => __('Use invoice/quote address'), 'value'  => 'invoice'),
                        array( 'id' => 'custom', 'label' => __('Use custom fields'), 'value' => 'custom' ),
                    ),
				'onchange'      => 'jQuery(this).parents("form").submit();',
			),

			array(
				'name'     => 'delivery_organization',
				'label'    => __( 'Organization (delivery)', 'gravityforms-qinvoice-connect' ),
				'type'     => 'select',
				'choices'  => $this->get_field_map_choices( rgget( 'id' ) ),
				'required' => true,
				'dependency' => array( 'field' => 'add_delivery', 'values' => array( 'custom' ) )
			),
			array(
				'name'     => 'delivery_firstname',
				'label'    => __( 'First Name (delivery)', 'gravityforms-qinvoice-connect' ),
				'type'     => 'select',
				'choices'  => $this->get_field_map_choices( rgget( 'id' ) ),
				'required' => true,
				'dependency' => array( 'field' => 'add_delivery', 'values' => array( 'custom' ) )
			),
			array(
				'name'     => 'delivery_lastname',
				'label'    => __( 'Last Name (delivery)', 'gravityforms-qinvoice-connect' ),
				'type'     => 'select',
				'choices'  => $this->get_field_map_choices( rgget( 'id' ) ),
				'required' => true,
				'dependency' => array( 'field' => 'add_delivery', 'values' => array( 'custom' ) )
			),
			
			array(
				'name'     => 'delivery_address',
				'label'    => __( 'Address (delivery)', 'gravityforms-qinvoice-connect' ),
				'type'     => 'select',
				'choices'  => $this->get_field_map_choices( rgget( 'id' ) ),
				'required' => false,
				'dependency' => array( 'field' => 'add_delivery', 'values' => array( 'custom' ) )
			),
			array(
				'name'     => 'delivery_address2',
				'label'    => __( 'Address 2 (delivery)', 'gravityforms-qinvoice-connect' ),
				'type'     => 'select',
				'choices'  => $this->get_field_map_choices( rgget( 'id' ) ),
				'required' => false,
				'dependency' => array( 'field' => 'add_delivery', 'values' => array( 'custom' ) )
			),
			array(
				'name'     => 'delivery_zipcode',
				'label'    => __( 'Zipcode (delivery)', 'gravityforms-qinvoice-connect' ),
				'type'     => 'select',
				'choices'  => $this->get_field_map_choices( rgget( 'id' ) ),
				'required' => false,
				'dependency' => array( 'field' => 'add_delivery', 'values' => array( 'custom' ) )
			),
			array(
				'name'     => 'delivery_city',
				'label'    => __( 'City (delivery)', 'gravityforms-qinvoice-connect' ),
				'type'     => 'select',
				'choices'  => $this->get_field_map_choices( rgget( 'id' ) ),
				'required' => false,
				'dependency' => array( 'field' => 'add_delivery', 'values' => array( 'custom' ) )
			),
			array(
				'name'     => 'delivery_country',
				'label'    => __( 'Country (delivery)', 'gravityforms-qinvoice-connect' ),
				'type'     => 'select',
				'choices'  => $this->get_field_map_choices( rgget( 'id' ) ),
				'required' => false,
				'dependency' => array( 'field' => 'add_delivery', 'values' => array( 'custom' ) )
			),
			array(
				'name'     => 'remark',
				'label'    => __( 'Remark', 'gravityforms-qinvoice-connect' ),
				'type'     => 'select',
				'choices'  => $this->get_field_map_choices( rgget( 'id' ) ),
				'required' => false,
			),
			array(
				'name'          => 'document_type',
				'label'         => __( 'Document type', 'gravityforms-qinvoice-connect' ),
				'type'          => 'select',
				'choices'       => array(
					array( 'id' => 'invoice', 'label' => __('Invoice','gravityforms-qinvoice-connect'), 'value' => 'invoice' ),
					array( 'id' => 'quote', 'label' => __('Quote','gravityforms-qinvoice-connect'), 'value' => 'quote' ),
					array( 'id' => 'proforma', 'label' => __('Proforma','gravityforms-qinvoice-connect'), 'value' => 'proforma' ),
				),
				'horizontal'    => true,
				'default_value' => 'invoice',
				'tooltip'       => '',
			),
			array(
				'name'     => 'document_date',
				'label'    => __( 'Document date', 'gravityforms-qinvoice-connect' ),
				'type'     => 'select',
				'choices'  => $this->get_field_map_choices( rgget( 'id' ) ),
				'required' => false,
			),
            array(
                'name'     => 'document_duedate',
                'label'    => __( 'Document duedate', 'gravityforms-qinvoice-connect' ),
                'type'     => 'select',
                'choices'  => $this->get_field_map_choices( rgget( 'id' ) ),
                'required' => false,
            ),

			array(
				'name'          => 'request_action',
				'label'         => __( 'Action', 'gravityforms-qinvoice-connect' ),
				'type'          => 'select',
				'choices'       => array(
					array( 'id' => '0', 'label' => __('Save as draft','gravityforms-qinvoice-connect'), 'value' => '0' ),
					array( 'id' => '1', 'label' => __('Save as PDF','gravityforms-qinvoice-connect'), 'value' => '1' ),
					array( 'id' => '2', 'label' => __('Save PDF and send','gravityforms-qinvoice-connect'), 'value' => '2' ),
				),
				'horizontal'    => true,
				'default_value' => '0',
				'tooltip'       => '',
			),
			array(
				'name'          => 'save_relation',
				'label'         => __( 'Save or update relation', 'gravityforms-qinvoice-connect' ),
				'type'          => 'select',
				'choices'       => array(
					array( 'id' => '0', 'label' => __('No','gravityforms-qinvoice-connect'), 'value' => '0' ),
					array( 'id' => '1', 'label' => __('Yes','gravityforms-qinvoice-connect'), 'value' => '1' ),
				),
				'horizontal'    => true,
				'default_value' => '0',
				'tooltip'       => '',
			),
			array(
				'name'          => 'calculation_method',
				'label'         => __( 'Calculation method', 'gravityforms-qinvoice-connect' ),
				'type'          => 'select',
				'choices'       => array(
					array( 'id' => 'no_vat', 'label' => __('No VAT applicable','gravityforms-qinvoice-connect'), 'value' => 'no_vat' ),
					array( 'id' => 'incl', 'label' => __('Including VAT','gravityforms-qinvoice-connect'), 'value' => 'incl' ),
					array( 'id' => 'excl', 'label' => __('Excluding VAT','gravityforms-qinvoice-connect'), 'value' => 'excl' ),
				),
				'horizontal'    => true,
				'default_value' => '0',
				'tooltip'       => '',
			),
			array(
				'name'       => 'vat_percentage',
				'label'      => __( 'Default VAT percentage', 'gravityforms-qinvoice-connect' ),
				'type'       => 'percentage',
				'tooltip'    => '<h6>' . __( 'Default VAT percentage', 'gravityforms-qinvoice-connect' ) . '</h6>' . __( 'Which VAT percentage to use. Depening on your selection at "Calculation method" this amount will be added or subtracted from the total amount.', 'gravityforms-qinvoice-connect' ),
				'validation_callback' => array( $this, 'validate_vat_percentage' )
			),

			array(
				'name'       => 'vat_rules',
				'label'      => __( 'VAT rules', 'gravityforms-qinvoice-connect' ),
				'type'       => 'textarea',
				'tooltip'    => '<h6>' . __( 'VAT rules', 'gravityforms-qinvoice-connect' ) . '</h6>' . __( 'On each line specify the VAT rate first, followed by the field id\'s comma seperated. A line could hold for example: "21: 6,8,19". 21 is the VAT rate, which applies to the fields 6, 8 and 19.', 'gravityforms-qinvoice-connect' ),
				'validation_callback' => array( $this, 'validate_vat_percentage' )
			),


			array(
				'name'       => 'discount',
				'label'      => __( 'Discount', 'gravityforms-qinvoice-connect' ),
				'type'       => 'percentage',
				'tooltip'    => '<h6>' . __( 'Discount', 'gravityforms-qinvoice-connect' ) . '</h6>' . __( 'When creating an invoice or estimate, this discount will be applied to the total invoice/estimate cost.', 'gravityforms-qinvoice-connect' ),
				'validation_callback' => array( $this, 'validate_discount' )
			),

			array(
				'name'       => 'ledgeraccount',
				'label'      => __( 'Ledger account', 'gravityforms-qinvoice-connect' ),
				'type'       => 'text',
				'tooltip'    => '<h6>' . __( 'Ledger account', 'gravityforms-qinvoice-connect' ) . '</h6>' . __( 'Optional. Set the ledger account here.', 'gravityforms-qinvoice-connect' ),
				'validation_callback' => array( $this, 'validate_vat_percentage' )
			),
			array(
				'name'     => 'tags',
				'label'    => __( 'Tags', 'gravityforms-qinvoice-connect' ),
				'type'     => 'text',
				'required' => false,
				'class'    => 'medium',
				'tooltip'  => '<h6>' . __( 'Tags', 'gravityforms-qinvoice-connect' ) . '</h6>' . __( 'Tags are used to easily recognize a document. Seperate multiple tags with commas.', 'gravityforms-qinvoice-connect' ),
			),
			array(
				'name'       => 'vat_number',
				'label'      => __( 'VAT Number', 'gravityforms-qinvoice-connect' ),
				'type'       => 'select',
				'choices'    => $this->get_field_map_choices( rgget( 'id' ) ),
				'dependency' => array( 'field' => 'documentType', 'values' => array( 'invoice', 'estimate' ) )
			),
			
			array(
				'name'    => 'feed_condition',
				'label'   => __( 'Export Condition', 'gravityforms-qinvoice-connect' ),
				'type'    => 'feed_condition',
				'tooltip' => '<h6>' . __( 'Export Condition', 'gravityforms-qinvoice-connect' ) . '</h6>' . __( 'When the export condition is enabled, form submissions will only be exported to Q-invoice.com when the condition is met. When disabled all form submissions will be exported.', 'gravityforms-qinvoice-connect' )
			),
			array(
				'name'          => 'order_items',
				'label'         => __( 'Order items', 'gravityforms-qinvoice-connect' ),
				'type'          => 'select',
				'choices'       => array(
					array( 'id' => 'form', 'label' => __('Use order items as defined in form','gravityforms-qinvoice-connect'), 'value' => 'form' ),
					array( 'id' => 'add', 'label' => __('Add items to order (specify below)','gravityforms-qinvoice-connect'), 'value' => 'add' ),
					array( 'id' => 'replace', 'label' => __('Replace form items (specify below)','gravityforms-qinvoice-connect'), 'value' => 'replace' ),
				),
				'horizontal'    => true,
				'default_value' => '0',
				'tooltip'       => '',
				'onchange'      => 'jQuery(this).parents("form").submit();',
			),

			array(
				'name'       => 'order_item_description',
				'label'      => __( 'Item description', 'gravityforms-qinvoice-connect' ),
				'type'       => 'text',
				'required'	=> true,
				'class'    => 'large',
				'dependency' => array( 'field' => 'order_items', 'values' => array( 'add', 'replace' ) )
			),
			array(
				'name'       => 'order_item_price',
				'label'      => __( 'Item price', 'gravityforms-qinvoice-connect' ),
				'type'       => 'text',
				'required'	=> true,
				'class'    => 'small',
				'dependency' => array( 'field' => 'order_items', 'values' => array( 'add', 'replace' ) )
			),

		);

		$extra_fields = array();

			$extra_fields = array(
				array(
					'name'    => 'payment',
					'label'   => __( 'Payment', 'gravityforms-qinvoice-connect' ),
					'type'    => 'checkbox',
					'tooltip' => '<h6>' . __( 'Delay for payment', 'gravityforms-qinvoice-connect' ) . '</h6>' . __( 'Enable this option if you want the invoice to be created only after a successful payment has been processed.', 'gravityforms-qinvoice-connect' ),
                    'choices' => array(
                        array(
                            'label' => __('Delay request until payment has been processed.','gravityforms-qinvoice-connect'),
                            'name'  => 'payment'
                        )
                    )
				)
			);


		$fields_array = @array_merge($default_fields,$extra_fields);

		return array(
			array(
				'title'       => __( 'Qinvoice Connect Feed', 'gravityforms-qinvoice-connect' ),
				'description' => '',
				'fields'		=> $fields_array
			),
		);

	}



	public function settings_percentage( $field, $echo = true ) {

		$field['type']  = 'text';
		$field['class'] = 'small';
		$html           = $this->settings_text( $field, false );

		if ( $echo ) {
			echo $html . '<span style="margin-left:10px">%</span>';
		}

		return $html . '<span style="margin-left:10px">%</span>';

	}

	
	public function enable_dynamic_costs(){
		$enable_dynamic = apply_filters( 'gform_freshbooks_enable_dynamic_field_mapping', false );
		return $enable_dynamic;
	}

	



	// ------- Plugin list page -------
	public function feed_list_columns() {
		return array(
			'name'			=> __( 'Name', 'gravityforms-qinvoice-connect' ),
			'document_type'		=> __( 'Document type', 'gravityforms-qinvoice-connect' ),
			'layout_code'		=> __( 'Layout code', 'gravityforms-qinvoice-connect' ),
			'request_action'	=> __( 'Action', 'gravityforms-qinvoice-connect' ),
			'payment'			=> __( 'Payment', 'gravityforms-qinvoice-connect' )
		);
	}

	public function get_column_value_request_action( $feed ) {
		switch($feed['meta']['requestAction']){
			case 0:
				return __('Save as draft','gravityforms-qinvoice-connect'); 
			break;
			case 1:
				return __('Save as PDF','gravityforms-qinvoice-connect'); 
			break;
			case 2:
				return __('Save PDF and send','gravityforms-qinvoice-connect'); 
			break;
		}
	}

	public function get_column_value_document_type( $feed ) {
		switch($feed['meta']['document_type']){
			case 'invoice':
				$return = __('Invoice','gravityforms-qinvoice-connect'); 
			break;
			case 'quote':
				$return = __('Quote','gravityforms-qinvoice-connect'); 
			break;
			case 'proforma':
				$return = __('Proforma','gravityforms-qinvoice-connect'); 
			break;
		}
		$return .= '<br /><small><strong>'. __('Tags','gravityforms-qinvoice-connect') .'</strong>: '. $feed['meta']['tags'] .'</small>';
		return $return; 
	}


	public function get_column_value_payment( $feed ) {
		return $feed['meta']['payment'] == '1' ? "<img src='" . $this->get_base_url() . "/images/tick.png' />" : '';
	}


	public function process_feed( $feed, $entry, $form ) {

		if (  $feed['meta']['payment'] == 1 ) {
			return;
		}

		$this->export_feed( $entry, $form, $feed );

	}

	public function export_resend($entry){
		
		$form = GFAPI::get_form($entry['form_id']);
		$feeds = GFFeedAddOn::get_feeds( $entry['form_id'] );

		foreach ( $feeds as $feed ) {
			if ( $this->is_feed_condition_met( $feed, $form, $entry ) ) {
				$active_feed = $feed;
			}
		}
		
		$this->export_feed($entry, $form, $active_feed, false);
		
	}


	public function export_after_payment($entry, $method = ''){
		
		$form = GFAPI::get_form($entry['form_id']);
		$feeds = GFFeedAddOn::get_feeds( $entry['form_id'] );

		foreach ( $feeds as $feed ) {
			if ( $this->is_feed_condition_met( $feed, $form, $entry ) ) {
				$active_feed = $feed;
			}
		}

		if($entry['is_fulfilled'] == 1){
			$this->export_feed($entry, $form, $active_feed, array('result' => 'ok', 'method' => $method));
		}
	}

	public function export_feed( $entry, $form, $feed, $payment = false) {

		if ( ! class_exists( 'qinvoice' ) ) {
			require_once( 'api/qinvoice.class.php' );
		}


		//global qinvoice settings
		$api_settings = get_option( 'gravityformsaddon_gravityforms-qinvoice-connect_settings' );
		
		if ( ! empty( $api_settings['api_username'] ) && ! empty( $api_settings['api_password'] ) && ! empty( $api_settings['api_password'] ) ) {
			$document = new Qinvoice($api_settings['api_username'], $api_settings['api_password'],$api_settings['api_url'] );
		}

		$mapped_fields = array();
		foreach ( $form['fields'] as $field ) {
			if ( RGFormsModel::get_input_type( $field ) == 'name' ) {
				$mapped_fields[] = $field;
			}
		}

		$document->identifier =  'gfqc_'. $this->_version;
		$document->setDocumentType($feed['meta']['document_type']);

		$document->action = (int)$feed['meta']['request_action'];
		$document->saverelation = (int)$feed['meta']['save_relation'];
		$document->layout = $feed['meta']['layout_code'];
		$document->calculation_method = $feed['meta']['calculation_method'];
		$tags = explode(",",$feed['meta']['tags']);
		foreach($tags as $tag){
			if(strlen($tag) > 0){
				$document->addTag($tag);
			}
		}
		$date = $this->get_entry_value( $feed['meta']['document_date'], $entry, $mapped_fields );
		$date = explode(" ",$date);
		$document->date = $date[0];

        $duedate = $this->get_entry_value( $feed['meta']['document_duedate'], $entry, $mapped_fields );
        $duedate = explode(" ",$duedate);
        $document->duedate = $duedate[0];

		$document->companyname = $this->get_entry_value( $feed['meta']['organization'], $entry, $mapped_fields );

		$document->salutation = $this->get_entry_value( $feed['meta']['salutation'], $entry, $mapped_fields );

		$document->firstname = $this->get_entry_value( $feed['meta']['firstname'], $entry, $mapped_fields );
		$prefix = strlen( $this->get_entry_value( $feed['meta']['prefix'], $entry, $mapped_fields ) ) > 0 ? $this->get_entry_value( $feed['meta']['prefix'], $entry, $mapped_fields ) .' ' : '';
		$document->lastname = $prefix . $this->get_entry_value( $feed['meta']['lastname'], $entry, $mapped_fields );
		$document->email = $this->get_entry_value( $feed['meta']['customer_email'], $entry, $mapped_fields );
		$document->phone = $this->get_entry_value( $feed['meta']['phone'], $entry, $mapped_fields );
		$document->address = $this->get_entry_value( $feed['meta']['address'], $entry, $mapped_fields );
		$document->address2 = $this->get_entry_value( $feed['meta']['address2'], $entry, $mapped_fields );
		$document->zipcode = $this->get_entry_value( $feed['meta']['zipcode'], $entry, $mapped_fields );
		$document->city = $this->get_entry_value( $feed['meta']['city'], $entry, $mapped_fields );
		$document->country = $this->get_entry_value( $feed['meta']['country'], $entry, $mapped_fields );

		$document->remark = $this->get_entry_value( $feed['meta']['remark'], $entry, $mapped_fields );

		// Populate delivery address fields, or don't
		switch($feed['meta']['add_delivery']){
			case 'none':
				// do nothing
			break;
			case 'invoice':
				// use invoice/customer
				$document->delivery_salutation = $this->get_entry_value( $feed['meta']['salutation'], $entry, $mapped_fields );
				$document->delivery_companyname = $this->get_entry_value( $feed['meta']['companyname'], $entry, $mapped_fields );
				$document->delivery_firstname = $this->get_entry_value( $feed['meta']['firstname'], $entry, $mapped_fields );
				$document->delivery_lastname = $this->get_entry_value( $feed['meta']['lastname'], $entry, $mapped_fields );
				$document->delivery_email = $this->get_entry_value( $feed['meta']['email'], $entry, $mapped_fields );
				$document->delivery_phone = $this->get_entry_value( $feed['meta']['phone'], $entry, $mapped_fields );
				$document->delivery_address = $this->get_entry_value( $feed['meta']['address'], $entry, $mapped_fields );
				$document->delivery_address2 = $this->get_entry_value( $feed['meta']['address2'], $entry, $mapped_fields );
				$document->delivery_zipcode = $this->get_entry_value( $feed['meta']['zipcode'], $entry, $mapped_fields );
				$document->delivery_city = $this->get_entry_value( $feed['meta']['city'], $entry, $mapped_fields );
				$document->delivery_country = $this->get_entry_value( $feed['meta']['country'], $entry, $mapped_fields );
			break;
			case 'custom':
			case 'other':
				// use custom delivery fields
				$document->delivery_salutation = $this->get_entry_value( $feed['meta']['delivery_salutation'], $entry, $mapped_fields );
				$document->delivery_companyname = $this->get_entry_value( $feed['meta']['delivery_companyname'], $entry, $mapped_fields );
				$document->delivery_firstname = $this->get_entry_value( $feed['meta']['delivery_firstname'], $entry, $mapped_fields );
				$document->delivery_lastname = $this->get_entry_value( $feed['meta']['delivery_lastname'], $entry, $mapped_fields );
				$document->delivery_email = $this->get_entry_value( $feed['meta']['delivery_email'], $entry, $mapped_fields );
				$document->delivery_phone = $this->get_entry_value( $feed['meta']['delivery_phone'], $entry, $mapped_fields );
				$document->delivery_address = $this->get_entry_value( $feed['meta']['delivery_address'], $entry, $mapped_fields );
				$document->delivery_address2 = $this->get_entry_value( $feed['meta']['delivery_address2'], $entry, $mapped_fields );
				$document->delivery_zipcode = $this->get_entry_value( $feed['meta']['delivery_zipcode'], $entry, $mapped_fields );
				$document->delivery_city = $this->get_entry_value( $feed['meta']['delivery_city'], $entry, $mapped_fields );
				$document->delivery_country = $this->get_entry_value( $feed['meta']['delivery_country'], $entry, $mapped_fields );
			break;
		}
		if($payment['result'] == 'ok'){
			$document->paid = 1;
			$document->payment_method = $payment['method'];
		}
		$document->vat = $this->get_entry_value( $feed['meta']['vat_number'], $entry, $mapped_fields );

		$ledgeraccount = esc_html( $feed['meta']['ledgeraccount'] );
		
		$products = GFCommon::get_product_fields( $form, $entry, false, false );

		$discount = $feed['meta']['discount'];
		$vat_percentage = $feed['meta']['vat_percentage'];

		$vat_rules = $feed['meta']['vat_rules'];
		foreach(explode("\n",$vat_rules) as $rule){
			$rule_data = explode(":",$rule);
			$rule_vat = $rule_data[0];
			$rules[$rule_vat] = explode(",",$rule_data[1]);
		}

		//order_items
		if($feed['meta']['order_items'] != 'replace'){
			foreach ( $products['products'] as $fid => $product ) {
				
				if(is_numeric($product['price'])){
					$product['price'] = number_format($product['price'],2,".","");
				}
				$product_name = $product['name'];
				$price        = GFCommon::to_number( $product['price'] );

				if ( ! empty( $product['options'] ) ) {
					$product_name .= ' (';
					$options = array();
					foreach ( $product['options'] as $option ) {
						$price += GFCommon::to_number( $option['price'] );
						$options[] = $option['option_name'];
					}
					$product_name .= implode( ', ', $options ) . ')';
				}
				$subtotal = floatval( $product['quantity'] ) * $price;
				$total += $subtotal;

				$product_vat_percentage = $vat_percentage;
				foreach($rules as $vatp => $ids){
					if(in_array($fid, $ids)){
						$product_vat_percentage = $vatp;
					}
				}

				switch($feed['meta']['calculation_method']){
					default:
					case 'no_vat':
						$price_excl = $price;
						$price_incl = $price;
						$vat_percentage = 0;
					break;
					case 'excl':
						$price_excl = $price;
						$price_incl = $price_excl * ((100+$product_vat_percentage)/100);
					break;
					case 'incl':
						$price_incl = $price;
						$price_excl = ($price_incl/(100+$product_vat_percentage))*100;
					break;
				}
				$price_vat = $price_incl - $price_excl;

				
				
				$params = array(	'code' => esc_html( $product['name'] ),
										'description' => esc_html( $product_name ),	
										'price' => $price_excl*100,
										'price_incl' => $price_incl*100,
										'price_vat' => $price_vat*100,
										'vatpercentage' => $product_vat_percentage*100,
										'discount' => $discount*100,
										'quantity' => $product['quantity']*100,	
										'ledgeraccount' => $ledgeraccount
									);
				// echo '<pre>';
				// print_r($params);
				// echo '</pre>';
				$document->addItem($params);
				$products_total += $price;
			}
			
			
			if($feed['meta']['order_items'] == 'add'){
				
				// calculate price based on incl/excl setting
				$price = $feed['meta']['order_item_price'];
				
				
				switch($feed['meta']['calculation_method']){
					default:
					case 'no_vat':
						$price_excl = $price;
						$price_incl = $price;
						$vat_percentage = 0;
					break;
					case 'excl':
						$price_excl = $price;
						$price_incl = $price_excl * ((100+$vat_percentage)/100);
					break;
					case 'incl':
						$price_incl = $price;
						$price_excl = ($price_incl/(100+$vat_percentage))*100;
					break;
				}
				$price_vat = $price_incl - $price_excl;
				
				$params = array( 	
							'code' => '',
	     					'description' => $feed['meta']['order_item_description'],
	     					'price' => $price_excl*100,
	     					'price_incl' => $price_incl*100,
							'price_vat' => $price_vat*100,
	     					'vatpercentage' => $vat_percentage*100,
	     					'discount' => $discount*100,
	     					'quantity' => 100,
	     					'categories' => 'discount'
	             		);
				$document->addItem($params);
			}
		}else{
			// calculate price based on incl/excl setting
			$price = $feed['meta']['order_item_price'];
			
			
			switch($feed['meta']['calculation_method']){
				default:
				case 'no_vat':
					$price_excl = $price;
					$price_incl = $price;
					$vat_percentage = 0;
				break;
				case 'excl':
					$price_excl = $price;
					$price_incl = $price_excl * ((100+$vat_percentage)/100);
				break;
				case 'incl':
					$price_incl = $price;
					$price_excl = ($price_incl/(100+$vat_percentage))*100;
				break;
			}
			$price_vat = $price_incl - $price_excl;

			$params = array( 	
						'code' => '',
     					'description' => $feed['meta']['order_item_description'],
     					'price' => $price*100,
     					'price_incl' => $price_incl*100,
						'price_vat' => $price_vat*100,
     					'vatpercentage' => $vat_percentage*100,
     					'discount' => $discount*100,
     					'quantity' => 100,
     					'categories' => 'discount'
             		);
			$document->addItem($params);
		}

		
		$result = $document->sendRequest();

		if(trim($result) != 1){
			gform_update_meta($entry['id'],'qinvoice_response',$result);
			$this->log_error( 'Invalid response from API.' );
		}else{
			gform_update_meta($entry['id'],'invoice_created',Date('Y-m-d H:i:s'));
		}
	}	

	private function get_entry_value( $field_id, $entry, $name_fields ) {
		foreach ( $name_fields as $name_field ) {
			if ( $field_id == $name_field['id'] ) {
				$value = RGFormsModel::get_lead_field_value( $entry, $name_field );
				//error_log($value);
				return GFCommon::get_lead_field_display( $name_field, $value );
			}
		}

		return $entry[ $field_id ];
	}

	
	public function validate_discount( $field ) {

		$settings = $this->get_posted_settings();
		$discount = $settings['discount'];

		if ( $discount ) {
			if ( ! is_numeric( $discount ) || ( $discount < 0 || $discount > 100 ) ) {
				$this->set_field_error( array( 'name' => 'discount' ), __( 'Please enter a number between 0 and 100.', 'gravityforms-qinvoice-connect' ) );
			}
		}

	}

	public function validate_vat_percentage( $field ) {

		$settings = $this->get_posted_settings();
		$vat_percentage = $settings['vat_percentage'];

		if ( $vat_percentage ) {
			if ( ! is_numeric( $vat_percentage ) || ( $vat_percentage < 0 || $vat_percentage > 100 ) ) {
				$this->set_field_error( array( 'name' => 'vat_percentage' ), __( 'Please enter a number between 0 and 100.', 'gravityforms-qinvoice-connect' ) );
			}
		}

	}
}
