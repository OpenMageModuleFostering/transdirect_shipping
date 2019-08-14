<?php
class Excellence_Ship_Model_Carrier_Excellence extends Mage_Shipping_Model_Carrier_Abstract
implements Mage_Shipping_Model_Carrier_Interface {
	protected $_code = 'excellence';

	public function collectRates(Mage_Shipping_Model_Rate_Request $request)
	{
		//echo '<pre>';	 print_r($request); die;
		if (!Mage::getStoreConfig('carriers/'.$this->_code.'/active')) {
			return false;
		}
		
		$price = $this->getConfigData('price'); // set a default shipping price maybe 0
		$price = 0;
		
		/* API Request Start */
		
		$account_email = Mage::getStoreConfig('mycustom_section/authentication/email');
		$account_password = Mage::getStoreConfig('mycustom_section/authentication/password');
		$warehouse_postcode = Mage::getStoreConfig('mycustom_section/warehouseaddress/postcode');
		$warehouse_address = Mage::getStoreConfig('mycustom_section/warehouseaddress/address');
		$dimension_width = Mage::getStoreConfig('mycustom_section/defaultitemsize/dimensionswidth');
		$dimension_height = Mage::getStoreConfig('mycustom_section/defaultitemsize/dimensionsheight');
		$dimension_dim = Mage::getStoreConfig('mycustom_section/defaultitemsize/dimensionsdim');
		$dimension_weight = Mage::getStoreConfig('mycustom_section/defaultitemsize/weight');
		$display_carriers = Mage::getStoreConfig('mycustom_section/displayoptions/availablecoriers');
		$display_quote = Mage::getStoreConfig('mycustom_section/displayoptions/quotedisplay');
		$display_fixedprice = Mage::getStoreConfig('mycustom_section/displayoptions/fixedpriceonerror');
		$display_fixedprice1 = Mage::getStoreConfig('mycustom_section/displayoptions/fixedpriceonerror1');
		$display_showcouriername = Mage::getStoreConfig('mycustom_section/displayoptions/showcouriernames');
		$display_surcharge = Mage::getStoreConfig('mycustom_section/displayoptions/handlingsurcharge');
		$display_surcharge1 = Mage::getStoreConfig('mycustom_section/displayoptions/handlingsurcharge1');
		$display_includesurchage = Mage::getStoreConfig('mycustom_section/displayoptions/includesurcharge');
	

		// Cart Total Weight Code Start by Nayan 
		
		$quote = Mage::getSingleton('checkout/session')->getQuote();
        $cartItems = $quote->getAllVisibleItems();
		$total_qty = Mage::helper('checkout/cart')->getSummaryCount(); 

		$weight = 0;
		$height = 0;
		$width = 0;
		$length = 0;
        foreach ($cartItems as $item)
        {
			//echo '<pre>'; print_r($item->getQty()); die('qty');
            $productId = $item->getProductId();
			$productQty = $item->getQty();

            $product = Mage::getModel('catalog/product')->load($productId);
            //echo $product->getItemWeight();
		 	 $weight += $product->getItemWeight() * $productQty;
		 	 $height += $product->getItemHeight() * $productQty;
		 	 $width += $product->getItemWidth() * $productQty;
		 	 $length += $product->getItemDim() * $productQty;
         }
		
		$cart_total_weight = $weight;  
		$cart_total_height = $height;  
		$cart_total_width = $width;
		$cart_total_length = $length; 
		
		// Cart Total Weight Code End by Nayan 

		
		if(!$cart_total_weight){ $cart_total_weight = $dimension_weight; }
		if(!$cart_total_height){ $cart_total_height = $dimension_height; }
		if(!$cart_total_width){ $cart_total_width = $dimension_width; }
		if(!$cart_total_length){ $cart_total_length = $dimension_dim; }

/*		echo 'width--'.$cart_total_weight; 
		echo 'height---'.$cart_total_height; 
		echo 'width---'.$cart_total_width; 
		echo 'length---'.$cart_total_length; 
		die('here');
*/

		// Getting Cart page Quote Address Details Code Start by Nayan

		$receiver_country    = (string) Mage::getSingleton('checkout/session')->getQuote()->getShippingAddress()->getCountryId();
		$receiver_postcode   = (string) Mage::getSingleton('checkout/session')->getQuote()->getShippingAddress()->getPostcode();
		$receiver_regionId   = (string) Mage::getSingleton('checkout/session')->getQuote()->getShippingAddress()->getRegionId();
		$receiver_region     = (string) Mage::getSingleton('checkout/session')->getQuote()->getShippingAddress()->getRegion();
 
		$region = Mage::getModel('directory/region')->load($receiver_regionId);
		$receiver_region_tmp = $region->getName();
		
		if($receiver_region == '') {
	        Mage::getSingleton('checkout/session')->addError("Please enter state/province.");
			session_write_close(); 
			//$this->_redirect('checkout/cart');	
			return false;
		}
		
		if(!is_numeric($receiver_postcode) || $receiver_postcode == "0" || $receiver_postcode == "00" || $receiver_postcode == "000" || $receiver_postcode == "0000" || $receiver_postcode == "00000" || $receiver_postcode == "000000"  || $receiver_postcode == "0000000") {
	        Mage::getSingleton('checkout/session')->addError("Please enter valid and correct postcode.");
			session_write_close(); 
			return false;
			//$this->_redirect('checkout/cart');	
		}
		
		if(!$receiver_regionId)	
		{ 
		  $receiver_suburb = $receiver_region; 
		}
		else 
		{ 
		  $receiver_suburb = $receiver_region_tmp; 
		}
	
		
	/*	echo Mage::getSingleton('checkout/session')->getQuote()->getShippingAddress()->getPostcode(); 
		echo Mage::getSingleton('checkout/session')->getQuote()->getShippingAddress()->getCountryId();
		echo Mage::getSingleton('checkout/session')->getQuote()->getShippingAddress()->getRegion();
		echo Mage::getSingleton('checkout/session')->getQuote()->getShippingAddress()->getRegionId();
	*/	
				
		
		
		
		//echo $receiver_suburb; die('suburb');
		
		// Getting Cart page Quote Address Details Code End by Nayan

		$quoteDetails = array(
						/*'email' => $account_email,
						'password' => $account_password, */
						'declared_value'=>10000, 
						'items' => array
						  (
						    array('width' => $cart_total_width, 'height' => $cart_total_height, 'weight'=> $cart_total_weight, 'length'=> $cart_total_length, 'quantity'=>$total_qty, 'description'=>'item description')
						  ),	
						  'sender' => array('country' => 'AU', 'suburb'=>'SYDNEY', 'postcode' => $warehouse_postcode, 'type'=> $warehouse_address),
						  'receiver' => array('country' => $receiver_country, 'suburb'=>$receiver_suburb, 'postcode' => $receiver_postcode, 'type'=> 'residential')
					  );

		$json_data = json_encode($quoteDetails);
		//echo '<pre>'; print_r($json_data); die('json');
				
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "https://www.staging.transdirect.com.au/api/bookings");
		//curl_setopt($ch, CURLOPT_URL, "https://www.transdirect.com.au/api/bookings");
		curl_setopt($ch, CURLOPT_USERPWD, "$account_email:$account_password");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$response = curl_exec($ch);
		
		$report = curl_getinfo($ch);
		//echo '<pre>'; print_r($report);
		
		if(curl_errno($ch)) {
		   echo 'Response error: ' . curl_error($ch);
		}
		
		curl_close($ch);
		
		if ($response) {
			//echo '<pre>'.$response;
			
			$json_decode_varible = json_decode($response, true);
			 
			$quotes_val = $json_decode_varible['quotes'];
				//echo "<pre>"; print_r($quotes_val); die('quotes');
			
			
			// Passing Data from Controller to Template file code Start 
				
				
			
			
			// End
			
			 
			 
		} else {
			echo "Failed";
		}			
		
		/* End */
		
		
		

		$handling = Mage::getStoreConfig('carriers/'.$this->_code.'/handling');
		$result = Mage::getModel('shipping/rate_result');
		$show = true;
		if($show){
			
		//die('before');
		//$quotes_val = Mage::getSingleton('core/session')->getSomeSessionVar();
		
		//$session = Mage::getSingleton('core/session', array('name' => 'frontend'));
		//echo $session->getSomeSessionVar();

		//echo '<pre>'; print_r($session->getSomeSessionVar());
		//die('after');
		
				if($quotes_val == ''){
				/*	Mage::getSingleton('checkout/session')->addError("Please Fill the information properly, There is some fields are missing or not proper.");
					session_write_close(); 
					return false;
				*/	
						$method = Mage::getModel('shipping/rate_result_method');
						$method->setCarrier($this->_code);
						$method->setMethod($this->_code);
						$method->setCarrierTitle($this->getConfigData('title'));
						$method->setMethodTitle('Fixed Price');
						
						if($display_fixedprice == '1'){
							$method->setPrice($display_fixedprice1);
							$method->setCost($display_fixedprice1);		
						}
						
						$result->append($method);
					
				} 
				else {
					
					 foreach($quotes_val as $key => $val) {
						$courier_title = $key;
						$courier_price = $quotes_val[$key]['total'];
						
						$method = Mage::getModel('shipping/rate_result_method');
						$method->setCarrier($this->_code);
						$method->setMethod($courier_title);
						$method->setCarrierTitle($this->getConfigData('title'));
						
						$method->setMethodTitle($courier_title);
			
						$method->setPrice($courier_price);
						$method->setCost($courier_price);
						
						$result->append($method);
			
					}
					
				}	

			//echo '<pre>'; print_r($result);  //die('model');

		}else{
			$error = Mage::getModel('shipping/rate_result_error');
			$error->setCarrier($this->_code);
			$error->setCarrierTitle($this->getConfigData('name'));
			$error->setErrorMessage($this->getConfigData('specificerrmsg'));
			$result->append($error);
		}
		return $result;
	}
	public function getAllowedMethods()
	{
		return array('excellence'=>$this->getConfigData('name'));
	}
}