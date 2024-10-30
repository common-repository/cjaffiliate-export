<?php 
/**
 * Class with actions for export product in cj affilate system
 *
 * @package cj_affiliate_plugin
 * @author Cimpleo
 */


class cjaffiliate_product_export {

	public $currency;
	public $_wc;

	/**
	 * __construct
	 */
	function __construct() {
		$this->init_hooks();
	}

	/**
	 * init_hooks 
	 * 
	 */
	private function init_hooks(){
	// Ajax actions
		add_action( 'wp_ajax_plugin_cj_export', array( $this, 'ajax_exportRequest' ) );
		add_action('cron_cj_export', array( $this, 'cron_cj_export_action' ) );
	}

	/**
	 * [create_queryArgs description]
	 * @param  [type] $query_args [description]
	 * @return [type]             [description]
	 */
	public function create_queryArgs() {
		$query_args = array(
			'post_type' 	=> 'product',
			'numberposts'	=> -1,
			'date_query' => array(
				'after' => '2 weeks ago',
			)
		);	
	// Create hook-filter for 
		$query_args = apply_filters( 'filter_cjquery_args', $query_args );
		return $query_args;	
	}

	/**
	 * Ajax action work with $_POST
	 * @return [JSON] Error or success
	 */
	public function ajax_exportRequest() {
	// Check all 
		check_ajax_referer( 'exportCJ_plugin', 'nonce' );

		if ( !isset($_POST['file_type']) )
			wp_send_json_error( 'Please check export format' );

		if ( !isset( $_POST['transfer_options'] ) )
			wp_send_json_error( 'Please set transfer' );


	// Set var.	
		$this->_wc = new WC_Product_Factory();  
		$this->currency = get_woocommerce_currency();

	// Generate args
		$query_args = $this->create_queryArgs();

		switch ( $_POST['file_type'] ) {
			case 'xml':
				$workFile = $this->generateXML_file( $query_args );
			break;
			case 'csv':
				$workFile = $this->generateCSV_file( $query_args );
			break;
			default:
				$workFile = false;
			break;
		}

		if ( $workFile ) {
			switch ($_POST['transfer_options']) {
				case 'ftp':
					$ajaxResult = $this->sendFileVia_ftp( $workFile );
					break;
				case 'email':
					$ajaxResult = array(
						'type' => 'email',
						'process'	=> $this->sendFileVia_email( $workFile, $_POST['send_to_email'] )	
					);
					break;
				case 'download':
					$ajaxResult = array(
						'type' => 'file',
						'process'	=> CJAFFILIATE_UPLOADS_URL.'/'.basename( $workFile )
					);
					break;
				default:
					# code...
					break;
			}
		}

		if ( isset( $ajaxResult ) && $ajaxResult['process'] != false ) {			
			wp_send_json_success( $ajaxResult );	
		}

	// Return ajax-error
		wp_send_json_error();

	}

	/**
	 *  Send export File via ftp. 
	 * @param  $_FILE 
	 * @return Boolen  
	 */
	public function sendFileVia_ftp( $file ) {
		if ( !isset( $file ) )
			return false;

	// Get ftp options
		$ftp_options = get_option( 'CJAffiliate_plugin_exportTransfer' );
	// Ftp connect;
		if ( !$ftp_options && empty( $ftp_options['ftp_host'] )  )
			return false;

		$conn_id = ftp_connect( $ftp_options['ftp_host'] );

		if ( $conn_id ) {

			$login_result = ftp_login($conn_id, $ftp_options['ftp_login'] , $ftp_options['ftp_pwd']);

			if ( !$login_result )
				wp_send_json_error( 'Login failed' );

					
			$basename = basename( $file );
			if (ftp_put($conn_id, $basename, $file, FTP_ASCII)) {
				return true;
			} else {
				return false;
			}
			ftp_close($conn_id);
		}
	}

	/**
	 * Send export file via email 
	 * @param  [$_FILE] $file  Export file
	 * @param  [string] $email Correct email
	 * @return Boolen     
	 */
	public function sendFileVia_email( $file, $email ) {
		if ( !isset( $email ) )
			return false;

		$attachments = array( $file );
		$email_admin = get_bloginfo('admin_email');
		$headers = 'From: My Name <'. $email_admin .'>' . "\r\n";

		$result = wp_mail($email, 'File CJAffiliate', 'You received an export file for the system CJAffiliate', $headers, $attachments);

		return $result;
	}


	/**
	 *
	 * Generate export XML file
	 * 
	 * @param  [array] $args
	 * @return [Boolen]    
	 */
	public function generateXML_file( $args ) {
	// Check args!
		if ( !isset( $args ) )
			return false;

	// Generate query
		$query = new WP_Query( $args );
		if ( $query->have_posts() ) {
		// Create new document
			$xmlDom = new DOMDocument( '1.0' );
			$xmlDom->preserveWhiteSpace = false;
       		$xmlDom->formatOutput = true;

		// Added !Doctype
			$implementation = new DOMImplementation();
			$xmlDom->appendChild($implementation->createDocumentType('product_catalog_data SYSTEM "http://www.jdoqocy.com/content/dtd/product_catalog_data_1_ 1.dtd"'));
		// Create new element		
			$xmlCatalog = $xmlDom->appendChild( $xmlDom->createElement( "product_catalog_data" ) );

		// Set CJ settings 
			if ( $settings = get_option( 'CJAffiliate_plugin_export' ) ) {
				$array_options = array( 'cid', 'subid', 'aid', 'processtype' );
				$xmlHeader = $xmlCatalog->appendChild( $xmlDom->createElement( "header" ) );
				foreach ($settings as $key => $value) {
					if ( in_array($key, $array_options ) && !empty( $value ) ) {
						$xmlHeader->appendChild(
   							$xmlDom->createElement( $key, $value ));
					}
				}
			}		
			while ( $query->have_posts() ) { $query->the_post();
			// Get wc product object for (cron)
				if ( empty( $this->_wc ) ) {
					$this->_wc = new WC_Product_Factory();  
					$this->currency = get_woocommerce_currency();
				}
				$post_id = get_the_ID();
				$productObject = $this->_wc->get_product( $post_id );
				$productType = $productObject->get_type();

			// Set product Item
				$productName = $this->clean_xml_string( get_the_title(), 160 );
				$productDesc = $this->clean_xml_string( get_the_content(), 3000 );
				$keywords_list =  wp_get_post_terms( $post_id , 'product_cat', array( 'fields' => 'names' ) );
				if ( $keywords_list ) {
					$keywords = implode( '>', $keywords_list );
				}

				if ( 'simple' === $productType )
					$simplePrice = $productObject->get_price();

				$productItem = array(
					'name'			=> $productName,	
					'keywords'		=> $productName,
					'description'	=> empty( $productDesc ) ? 'Description is missing' : $productDesc,
					'sku'			=> empty( $productObject->get_sku() ) ? $post_id : $productObject->get_sku(),
					'buyurl'		=> $this->clean_xml_string( get_the_permalink() ),
					'price'			=> isset( $simplePrice ) ? $simplePrice : 0,
					'currency'		=> $this->currency,
					'startdate'		=> get_the_date( 'n/j/Y' ),
					'instock'		=> $productObject->is_in_stock() ? 'Yes' : 'No',
					'available'		=> 'Yes'
				);
				isset( $imageurl ) ? $productItem['imageurl'] = $imageurl : '';	
				isset( $keywords ) ? $productItem['advertisercategory'] = $keywords : '';	

			// Set Node
				if ( $productItem ) {
					$xmlProduct = $xmlCatalog->appendChild( $xmlDom->createElement( 'product' ) );
					foreach ($productItem as $key => $value) {
						$xmlProduct->appendChild( $xmlDom->createElement( $key, $value ) );
					}
				}
				
		    // Check product for variable type 		
				if ( $productObject->is_type( 'variable' ) ) {
					$args = array(
						'post_type'      => 'product_variation',
						'post_status'   => array( 'private', 'publish' ),
						'numberposts'   => -1,
						'orderby'       => 'menu_order',
						'order'         => 'asc',
						'post_parent'   => $post_id
					);
					$variations = get_posts( $args ); 

					if ( !empty($variations) ) {	
					// Get variations_attr array										
						$variationsArray_attr = $this->get_attributesArray( $productObject );
						foreach ( $variations as $post ) {
							$this->add_variationsProduct_xml( $xmlDom, $xmlCatalog, $post, $variationsArray_attr );
						}
					}
				}

			} // EndWhile
		// Create file and return filepath
	        $formattedXML = $xmlDom->saveXML();
	        if ( $formattedXML ) {
				$filename = '/xmlExpot'. date( 'Ymd' ) .'.xml';
				$filepath = CJAFFILIATE_UPLOADS . $filename;
		        $fp = fopen( $filepath, 'w+' );
		        if ( $fp ) {
			        fwrite( $fp, $formattedXML );
			        fclose( $fp );
			        return $filepath;
		        }
	        }
		} // EndIF
		return false;
	}

	/**
	 * [get_attributesArray description]
	 * @param  [type] $productObject [description]
	 * @return [type]                [description]
	 */
	public function get_attributesArray( $productObject ) {
		if( !isset( $productObject ) )
			return false;

		if ( $variations_attr = $productObject->get_attributes() ) {
			foreach ($variations_attr as $key => $value) {
				$data = $value->get_data();
				if ( $data['variation'] && $data['visible'] ) {
					$variationsArray_attr[] = $key;
				}
			}
		}
		return $variationsArray_attr;
	}

	/**
	 * [add_variationsProduct_xml description]
	 * @param [type] $xmlCatalog [description]
	 * @param [type] $post       [description]
	 */
	private function add_variationsProduct_xml( $xmlDom = false, $xmlCatalog, $post, $variations_attr ) {
		if ( $xmlDom === false )
			return false;

	// Get wc product object
		$post_id = $post->ID;
		$variationObject = new WC_Product_Variation( $post_id ); 

		if ( $variations_attr ) {
			foreach ($variations_attr as $item ) {
				$variable_meta = get_post_meta($post_id, 'attribute_'.$item, true);
				$varibale_term = get_term_by('slug', $variable_meta, $item);
				$varibale_term_name[] = $varibale_term->name;
			}
			if ( !empty( $varibale_term_name ) )
				$varibale_term_name = implode('>', $varibale_term_name);
		}

		$variationName = $this->clean_xml_string( $post->post_title, 160 );
		$variationDesc = $this->clean_xml_string( $post->post_content, 3000 );
		$imageurl = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ) );
		$stock = $variationObject->is_in_stock() ? 'Yes' : 'No';

		$variableItem = array(
			'name'			=> $variationName,
			'keywords'		=> $variationName,
			'description'	=> empty( $variationDesc ) ? 'Description is missing' : $variationDesc,
			'sku'			=> empty( $variationObject->get_sku() ) ? $post->post_parent . '-'. $post_id  : $variationObject->get_sku(),
			'buyurl'		=> $this->clean_xml_string( $post->guid ),
			'price'			=> $variationObject->get_price(),
			'currency'		=> $this->currency,
			'startdate'		=> $post->post_date,
			'instock'		=> $stock,
			'available'		=> 'Yes'
		);
		isset( $imageurl ) ? $variableItem['imageurl'] = $imageurl : '';	
		isset( $varibale_term_name ) ? $variableItem['advertisercategory'] = $varibale_term_name : '';	

	// Set Node
		if ( $variableItem) {
			$xmlProduct = $xmlCatalog->appendChild( $xmlDom->createElement( "product" ) );
			foreach ($variableItem as $key => $value) {
				$xmlProduct->appendChild( $xmlDom->createElement( $key, $value ) );
			}
		}

		return $xmlDom;
	}


	/**
	 * Clean and cut string
	 * @param  string $string Requires
	 * @param  number $chars Lenght cut string
	 * @return [string]
	 */
	function clean_xml_string( $string, $chars = 0 ) {
		$string = htmlspecialchars( $string ); 
		if ( $chars != 0 )
			$string = substr( $string, 0, $chars );

		return $string;
	}


	/**
	 * [generate<CSV></CSV>_file description]
	 * @param  [type] $args [description]
	 * @return [type]       [description]
	 */
	public function generateCSV_file( $args ) {
	// Check args!
		if ( !isset( $args ))
			return false;


	// CSV parameters
		$parameters_array = array(
			'NAME', 'KEYWORDS', 'DESCRIPTION', 'SKU', 'BUYURL',
			'AVAILABLE', 'IMAGEURL', 'PRICE', 'CURRENCY',
			'INSTOCK', 'STARTDATE'
			);
		$parameters = implode( '|', $parameters_array );

		$query = new WP_Query( $args );
		if ( $query->have_posts() ) {
		// Create file
			$filename = 'csvExpot'. date('Ymd') .'.csv';
			$csvFile = CJAFFILIATE_UPLOADS. $filename;

		// Setings 
			$settings = get_option('option_export_name');
			if ( $settings ) {
				foreach ($settings as $key => $value) {
					if ( !empty( $value ) ) {
						$string = '&'. $key. '='. $value;
						file_put_contents( $csvFile , $string, FILE_APPEND );
					}
				}
			// Set  parameters in file
				if ( $parameters ) {
					file_put_contents( $csvFile , '&PARAMETERS=' . $parameters, FILE_APPEND );
				}
			}	

			
			while ( $query->have_posts() ) { $query->the_post();
			// Create object 
				$post_id = get_the_ID();
				$productObject = $this->_wc->get_product( $post_id );

			// Set productItem array 
				$name = $this->clean_xml_string( get_the_title(), 160 );
				$name = str_replace('"', '', $name);

				$desc = $this->clean_xml_string( get_the_content(), 3000 );
				if ( $desc == false ) {
					$desc = 'Description is missing,';
				}else {
					$desc = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $desc);
					$desc = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $desc);
				}

				$stock = $productObject->is_in_stock() ? 'Yes' : 'No';

				$keywords_list =  wp_get_post_terms( $post_id , 'product_cat', array("fields" => "names"));
				$keywords = implode(">", $keywords_list);

				$productItem = array(
					'name'			=> '"'.$name.'"',
					'keywords'		=> '"'.$name.'"',
					'description'	=> '"'.$desc.'"',
					'sku'			=> empty( $productObject->get_sku() ) ? $post_id : $productObject->get_sku(),
					'buyurl'		=> htmlspecialchars( get_the_permalink() ),
					'price'			=> 0,
					'currency'		=> $this->currency,
					'startdate'		=> get_the_date( 'n/j/Y' ),
					'instock'		=> $stock,
					'available'		=> 'Yes',
					'advertisercategory' => '"'.$keywords.'"'
				);

				isset( $imageurl ) ? $productItem['imageurl'] = $imageurl : '';	


			// String for put in file
				$resultItemString = '';

				if ( $productItem ) {
					foreach ( $productItem as $key => $value ) {
						$resultItemString .= $value . ',';
					}
				}

			// Put in file one item
				if ( !empty( $resultItemString ) ) {
					file_put_contents( $csvFile, $resultItemString, FILE_APPEND );
				}
	
		    // Check product for variable type 		
				if ( $productObject->is_type( 'variable' ) ) {
					$args = array(
						'post_type'      => 'product_variation',
						'post_status'   => array( 'private', 'publish' ),
						'numberposts'   => -1,
						'orderby'       => 'menu_order',
						'order'         => 'asc',
						'post_parent'   => $post_id
						);

					$variations = get_posts( $args ); 
					foreach ( $variations as $post ) {
						$this->add_variationsProduct_csv( $csvFile, $post );
					}
				}				

			} // EndWhile;

			if ( $csvFile )
				return $csvFile;

		} // EndIf;
		return false;
	}

	/**
	 * [add_variationsProduct_csv description]
	 * @param boolean $csvFile [description]
	 * @param [type]  $post    [description]
	 */
	private function add_variationsProduct_csv( $csvFile = false, $post ) {
		if ( $csvFile == false )
			return false;

		// Get wc product object
		$post_id = $post->ID;
		$productObject = new WC_Product_Variable( $post->post_parent ); 

		$name = $this->clean_xml_string( $post->post_title, 160 );
		$name = str_replace('"', '', $name);

		$desc = $this->clean_xml_string( get_the_content(), 3000 );
		if ( $desc == false ) {
			$desc = 'Description is missing,';
		}else {
			$desc = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $desc);
			$desc = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $desc);
		}

		$varibale_taxonomy = 'pa_seat-location';
		$variable_meta = get_post_meta($post_id, 'attribute_'.$varibale_taxonomy, true);
		$varibale_term = get_term_by('slug', $variable_meta, $varibale_taxonomy);
		$imageurl = wp_get_attachment_image_src( get_post_thumbnail_id( $post_id ) );
		$stock = $productObject->is_in_stock() ? 'Yes' : 'No';


		$variableItem = array(
			'name'			=> '"'.$name.'"',
			'keywords'		=> '"'.$name.'"',
			'description'	=> '"'.$desc.'"',
			'sku'			=> empty( $productObject->get_sku() ) ? $post_id : $productObject->get_sku(),
			'buyurl'		=> htmlspecialchars( $post->guid ),
			'price'			=> $productObject->get_price(),
			'currency'		=> $this->currency,
			'startdate'		=> get_the_date( 'n/j/Y', $post_id ),
			'instock'		=> $stock,
			'available'		=> 'Yes',
			'advertisercategory' => '"'.$varibale_term->name.'"'
		);

		isset( $imageurl ) ? $variableItem['imageurl'] = $imageurl : '';	


	// String for put in file
		$resultItemString = '';

		if ( $variableItem ) {
			foreach ( $variableItem as $key => $value ) {
				$resultItemString .= $value . ',';
			}
		}

	// Put in file one item
		if ( !empty( $resultItemString ) ) {
			file_put_contents( $csvFile, $resultItemString, FILE_APPEND );
		}
		return $csvFile;
	}



	/**
	 * [cron_cj_export_action description]
	 * @return [type] [description]
	 */
	public function cron_cj_export_action() {
		$query_args = array(
			'post_type' => 'product',
			'meta_query'=> array(
				'product_date' =>array(
					'key'	=> 'Date',
					'value'	=> date( 'd/m/Y' ),
					'compare'=> '>='
					)
				)
			);		


		if ( $query_args ) {
			$workFile = $this->generateXML_file( $query_args );
			error_log( 'Error: '.print_R( $workFile, true ) );
			if ( $workFile ) {
				$ajaxResult = $this->sendFileVia_ftp( $workFile );
				if ( $ajaxResult['process'] != false  )
					return true;
			}
		}
		return false;
	}


}

new cjaffiliate_product_export();