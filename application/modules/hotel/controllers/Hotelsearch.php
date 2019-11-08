<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH.'/libraries/MY_REST_Controller.php';
//require  'vendor/autoload.php';


class Hotelsearch extends MY_REST_Controller {

	private $url;
	private $urlTravelanda;
	public function __construct()
	{		
		parent::__construct();
		$this->url = "http://dev.hotelston.com/ws/HotelService.HotelServiceHttpSoap11Endpoint/";
		$this->urlTravelanda = 'http://xmldemo.travellanda.com/xmlv1';
		$this->load->model('Hotel_search_model');
	}

	public function hotelstone_api_call($request = array(),$requestid)
	{
		$room = '';
		//var_dump($request['room_count']); die;
	   for($i=1;$i<= $request['room_count']; $i++)  
	   {
			  $room .= '<xsd:room xsd:adults="'.$request['adults_per_room']['room-'.$i].'" xsd:children="'.$request['children_per_room']['room-'.$i].'">';
			  if(isset($request['child_age']['room-'.$i]))
			  {
				  for($j=0;$j< count($request['child_age']['room-'.$i]);$j++)
				  {
					  $room .='<xsd:childAge>'.$request['child_age']['room-'.$i][$j].'</xsd:childAge>';
				  }
				  
			  }
			  $room .= '</xsd:room>';
	   }
	   ////Header("Content-type: text/xml");
	   //Secho $room; die();
	  /* query to country and city for city id from name and country iso from country name */
	  $xml_new = '<?xml version="1.0" encoding="UTF-8"?>                            
				  <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsd="http://request.ws.hotelston.com/xsd" xmlns:xsd1="http://types.ws.hotelston.com/xsd">
			  <soapenv:Header/>
			  <soapenv:Body>
				  <xsd:SearchHotelsRequest>
					  <!--1 to 2 repetitions:-->
					  <xsd:locale>en</xsd:locale>
					  <xsd:loginDetails xsd1:email="info@ajwal.travel" xsd1:password="Ajwal@2019"/>
				  
					  <xsd:currency>'.$request['currency'].'</xsd:currency>
					  <!--Optional:-->
					  <xsd:netRates>true</xsd:netRates>
					  <!--Optional:-->
				  
					  <xsd:criteria>
						  <xsd:checkIn>'.$request['checkin_date'].'</xsd:checkIn>
						  <xsd:checkOut>'.$request['checkout_date'].'</xsd:checkOut>
						  <!--Optional:-->
						  <xsd:cityId>1870359</xsd:cityId>
					  
						  <xsd:clientNationality>DE</xsd:clientNationality>
						  <!--Optional:-->
					  
						  <!--1 to 3 repetitions:-->
					  ' . $room . '
						  
					  </xsd:criteria>
				  </xsd:SearchHotelsRequest>
					  </soapenv:Body>
					  </soapenv:Envelope>';
		   
		  $data31 = $this->getcurl($xml_new);        
		  $myFile = APPPATH."modules/hotel/hotelstone_hotellist.xml";
		  $fh = fopen( $myFile, 'w', 'r');
		  $stringData = $data31;
		  fwrite($fh, $stringData);
		  fclose($fh); 
		   //Header("Content-type: text/xml");
		   //echo $data31;die();
		   
		  $hotel_result = array();
		  $room_result = array();
		  if ($data31 != '') {
			  $k = 0;
			  $m = 0;
			  $data = new DOMDocument();
			  $data->loadXML($data31);
			  if($data->getElementsByTagName('SearchHotelsResponse')->item(0)->getElementsByTagName('success')->item(0)->nodeValue == 'true') {
			  $data1 = $data->getElementsByTagName('SearchHotelsResponse');
			  // echo "<pre>";print_r($data1);
			  foreach ($data1 as $data2) {//echo "<pre>";print_r($data2);
				  $data_val = $data2->getElementsByTagName('searchId')->item(0)->nodeValue;


				  $data3 = $data2->getElementsByTagName('hotel');
				  //echo'<pre>'; print_r($data3); die;
				  foreach ($data3 as $data4) {					
					  
					  $hotel_id = $data4->getAttribute('xsd:id');
					  //$hoteldata = $this->Hotel_search_model->hotelstone_get_hotel_detail($hotel_id);
					  $hotel_name = $data4->getAttribute('xsd:name');
					  $lastupdated =  $data4->getAttribute('xsd:lastUpdated');
					  //echo "<pre>";print_r($data4);
					  //echo "<pre>";print_r($data5_name);
					  
					  $hotel_result[$m] = array(
						  'searchid' => $data_val,
						  'hotelid' => $hotel_id,
						  'request_id' => $requestid,
						  'hotel_name' => $hotel_name,
						  'lastupdated' => $lastupdated
					  );

					  $data5 = $data4->getElementsByTagName('room');
					  foreach ($data5 as $data6) {
						  $room_seqNo = $data6->getAttribute('xsd:seqNo');
						  $room_id = $data6->getAttribute('xsd:id');
						  $room_specialOffer = $data6->getAttribute('xsd:specialOffer');
						  $amount_val = $data6->getAttribute('xsd:price');
						  $room_visaSupport = $data6->getAttribute('xsd:visaSupport');


						  /* echo "<pre>";print_r($data5_seqNo);
							echo "<pre>";print_r($data5_id);
							echo "<pre>";print_r($data5_specialOffer);
							echo "<pre>";print_r($data5_price);
							echo "<pre>";print_r($data5_visaSupport); */

						  $data7 = $data6->getElementsByTagName('boardType')->item(0);
						  $data8 = $data6->getElementsByTagName('roomType')->item(0);
						  //echo "<pre>";print_r($data8);


						  $boardType_group_Id = $data7->getAttribute('xsd:groupId');
						  $boardType_group_name = $data7->getAttribute('xsd:groupName');
						  $boardType_Id = $data7->getAttribute('xsd1:id');
						  $boardType_name = $data7->getAttribute('xsd1:name');


						  $roomtype_Id = $data8->getAttribute('xsd1:id');
						  $roomtype_hotelston_name = $data8->getAttribute('xsd:hotelstonName');

						  $roomtype_name = $data8->getAttribute('xsd1:name');
						 /* if ($_SESSION['agents']['username'] !== '') {
							  $data_markup = $this->Hotels_Model->get_b2b_markup($_SESSION['agents']['username']);
							  $total_price = $this->Hotels_Model->get_markup($room_price, $data_markup);
						  } else {*/
							  //$total_price = $room_price + ($room_price * $_SESSION['hotel_search']['b2c_hotel_markup'] / 100 );
						  //}
						 // $hotel_details = $this->Hotelston_Model->get_hotelston_images($hotel_id);
						 
						  $room_price = $amount_val;
						 
						  $totalAmount=$room_price;

						  
						  /*if($this->hotel_discount_type == 0)
						  {
							  $discount=($room_price *$this->hotel_discount  )/100; 
						  }
						  else
						  {
							  $discount=$this->hotel_discount;  
						  }  */
						//  $total_price=$room_price+$resultMarkup-$discount;

						/*  if((isset($_SESSION['to_currency_place']) && $_SESSION['to_currency_place'] != '') || (isset($_SESSION['to_currency_place']) && $_SESSION['to_currency_place'] != 'EUR'))
						  {
							  $total_price=ceil($total_price*$currency_converted);
						  }else{
							  $total_price=$total_price;
						  }  */						
						  

						 
						  //echo "<pre>";print_r($result);die;;
						  $room_result[$k] = array(
							  'roomid' => $room_id,
							  'hotelid' => $hotel_id,
							  'requestid' => $requestid,
							  'room_seqno' =>  $room_seqNo,
							  'room_specialoffer' => $room_specialOffer,
							  'room_price' => $amount_val,
							  'room_visasupport' => $room_visaSupport,
							  'boardtype_groupid' => $boardType_group_Id,
							  'boardtype_groupname' => $boardType_group_name,
							  'boardtype_id' => $boardType_Id,
							  'boardtype_name' => $boardType_name,
							  'roomtype_id' => $roomtype_Id,
							  'roomtype_hotelstonName' => $roomtype_hotelston_name,
							  'roomtype_name' => $roomtype_name
						  ); 
						  $k++;
					  }
					  $m++;
				  }
				  
			  }

		  } 
		  //print_r($hotel_result); exit;
		  if(!empty($hotel_result)) { 
			 // $this->db->insert_batch('hotel_search_result', $result);
			 $is_save_hotel = $this->Hotel_search_model->add_hotel_search_response($hotel_result);
			 if($is_save_hotel)
			 {
				$is_save_room = $this->Hotel_search_model->add_hotel_search_rooms_response($room_result);
				if($is_save_room)
				{
				  return $this->response('successfully saved', REST_Controller::HTTP_OK);
				}
				else{
				  return $this->response("failed saving hotelsearch room result", REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
				 }
			 }
			 else{
			  return $this->response("failed saving hotelsearch result", REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
			 }
		  }
		  else
		  {
			return  $this->response('no hotel found', REST_Controller::HTTP_OK);
		  }
	  }
	  
	}
	public function travellanda_api_call($request = array(),$requestid)
	{
		$room = '<Rooms>';
		for($i=1;$i<= $request['room_count']; $i++)  
	   {
			$room .= '<Room>';
			  $room .= '<NumAdults>"'.$request['adults_per_room']['room-'.$i].'"</NumAdults>';
			  
			if(isset($request['child_age']['room-'.$i]))
			  {
				  $room .= "<Children>" ;
				  for($j=0;$j< count($request['child_age']['room-'.$i]);$j++)
				  {
					  $room .='<ChildAge>'.$request['child_age']['room-'.$i][$j].'</ChildAge>';
				  }

				  $room .= "</Children>" ;				  
			  }
			  $room .= '</Room>';
	   }
	   $room = '</Rooms>';
		$xml = '<Request>
		<Head>
			<Username>c46c3f42ea63d41a</Username>
			<Password>FAdI1OFRXgV1</Password>
			<RequestType>HotelSearch</RequestType>
		</Head>
		<Body>
		<!--<CityId>117976</CityId>-->
		<CityId>117976</CityId>
		<CheckInDate>2019-12-18</CheckInDate>
		<CheckOutDate>2019-12-20</CheckOutDate>
		' . $room . '
		<Nationality>FR</Nationality>
		<Currency>USD</Currency>
		</Body>
		</Request>';
		$fields = array(
			'xml' => $xml
		);
		$result = $this->curl_travellanda($fields);
		$myFile = APPPATH."modules/hotelstone/travelaandahotellist.xml";
		$fh = fopen( $myFile, 'w', 'r');
		$stringData = $result;
		fwrite($fh, $stringData);
		fclose($fh); 
		echo 'done';
		die();
		$data = new DOMDocument();
            $data->loadXML($result);
            $city_id = $data->getElementsByTagName('CityId')->item(0)->nodeValue;
            $HotelsReturned = $data->getElementsByTagName('HotelsReturned')->item(0)->nodeValue;
            $DiscountApplied=0;
            if ($data->getElementsByTagName('Hotels')->length > 0) {
                $Hotels = $data->getElementsByTagName('Hotels')->item(0);
                $hotels = $Hotels->getElementsByTagName('Hotel');
				$hotel_result = array();
				$room_result = array();
				$m = 0;
				$k = 0;
                foreach ($hotels as $hotel) {
                    $HotelId = $hotel->getElementsByTagName('HotelId')->item(0)->nodeValue;
                    $HotelName = $hotel->getElementsByTagName('HotelName')->item(0)->nodeValue;
					$StarRating = $hotel->getElementsByTagName('StarRating')->item(0)->nodeValue;
					
					$hotel_result[$m] = array(
						'hotelid' => $HotelId,
						'request_id' => $requestid,
						'hotel_name' => $HotelName,
						'star_rating' => $StarRating
					);

                    $Options = $hotel->getElementsByTagName('Options')->item(0);
                    $Option = $Options->getElementsByTagName('Option');
                    foreach ($Option as $opt) {
                        $OptionId = $opt->getElementsByTagName('OptionId')->item(0)->nodeValue;
                        $OnRequest = $opt->getElementsByTagName('OnRequest')->item(0)->nodeValue;
                        $amount_val = $opt->getElementsByTagName('TotalPrice')->item(0)->nodeValue;
                        $BoardType = $opt->getElementsByTagName('BoardType')->item(0)->nodeValue;
                        if($opt->getElementsByTagName('DiscountApplied')->length>0)
                        $DiscountApplied = $opt->getElementsByTagName('DiscountApplied')->item(0)->nodeValue;

                        $Rooms = $opt->getElementsByTagName('Rooms')->item(0);
                        $Room = $Rooms->getElementsByTagName('Room');
                        $RoomId = '';
                        $RoomName = '';
                        $RoomPrice = '';
                        $DailyPrice = '';
                        $NumAdults = '';
                        $NumChildren = '';
                        foreach ($Room as $rm) {
                            $RoomId = $rm->getElementsByTagName('RoomId')->item(0)->nodeValue . "<br>";
                            $RoomName = $rm->getElementsByTagName('RoomName')->item(0)->nodeValue . "<br>";
                            $RoomPrice = $rm->getElementsByTagName('RoomPrice')->item(0)->nodeValue . "<br>";
                            $DailyPrices = $rm->getElementsByTagName('DailyPrices');
                            // echo "<pre>";print_r($DailyPrices);exit;
                            foreach ($DailyPrices as $dlp) {
                                $DailyPrice .= $dlp->getElementsByTagName('DailyPrice')->item(0)->nodeValue . "<br>";
                            }
                            //echo "<pre>";print_r($DailyPrice);exit;
                            $NumAdults .= $rm->getElementsByTagName('NumAdults')->item(0)->nodeValue . "<br>";
                            $NumChildren .= $rm->getElementsByTagName('NumChildren')->item(0)->nodeValue . "<br>";

                            //echo "<pre>";        print_r($DiscountApplied);        exit;
                        }

						$TotalPrice = $amount_val;

                        //$hotel_details = $this->Travellanda_Model->get_hotel_photos($HotelId, $city_id);

                        $room_result[$k] = array(
							  'roomid' => $RoomId,
							  'hotelid' => $HotelId,
							  'requestid' => $requestid,
							  'optionid' => $OptionId,
							  'room_specialoffer' => $room_specialOffer,
							  'room_price' => $amount_val ,
							  'room_visasupport' => $room_visaSupport,
							  'boardtype_groupid' => $boardType_group_Id,
							  'boardtype_groupname' => $boardType_group_name,
							  'boardtype_id' => $boardType_Id,
							  'boardtype_name' => $boardType_name,
							  'roomtype_id' => $roomtype_Id,
							  'roomtype_hotelstonName' => $roomtype_hotelston_name,
							  'roomtype_name' => $roomtype_name
						  ); 
						  $k++;
					}
					$m++;
                }
				
				if(!empty($data)) { 
					$this->db->insert_batch('hotel_search_result', $data);
				}
				
            }
	}
	function curl_travellanda($fields) {
		
        $URL = $this->urlTravelanda;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $URL);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); //timeout after 30 seconds
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);   //get status code
        $result = curl_exec($ch);
        curl_error($ch);
        curl_close($ch);
        return $result;
    }
	public function hotel_lookup_post()
	{
		$request = array(
			'api_name' => 'hotelstone',
			'city_name' => $this->post('city_name'),
			'country_name' => $this->post('country_name'),
           'currency' => $this->post('currency'),
		   'checkin_date' => $this->post('checkin_date'),
		   'checkout_date' => $this->post('checkout_date'),
		   'room_count' => $this->post('room_count'),
		   'adults_per_room' => $this->post('adults_per_room'),
		   'children_per_room' => $this->post('children_per_room'),
		   'child_age' => $this->post('child_age')
		   );
		   $is_save_request_id = 20;
		   
		$is_save_request_id = $this->Hotel_search_model->add_hotel_search_request($request);

		if(!$is_save_request_id)
		{
			
			return $this->response("failed to save request data", REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
		}
		else
		{
			//$this->hotelstone_api_call($request,$is_save_request_id);
		    $this->travellanda_api_call($request,$is_save_request_id);
			return $this->response("saved sccesfully", REST_Controller::HTTP_OK);
			
		}
			
	}
	function getcurl($xml) {
		 
		$URL3 = $this->url;
		//echo $xml; die;
		//$URL3 = 'http://dev.hotelston.com/ws/HotelService.wsdl'; //test url

        //$URL3 = 'https://www.hotelston.com/ws/HotelService?wsdl'; //live url

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $URL3);
        curl_setopt($ch, CURLOPT_TIMEOUT, 180);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, FALSE);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        $httpHeader2 = array("Content-Type: text/xml; charset=UTF-8",
            "Content-Encoding: UTF-8",
            "SOAPAction: {http://dev.hotelston.com/ws/HotelService.HotelServiceHttpSoap11Endpoint/}"
        );

        curl_setopt($ch, CURLOPT_ENCODING, "gzip,deflate");
        curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeader2);
        // Execute request, store response and HTTP response code
        $data31 = curl_exec($ch);
        $errno = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $data31;
    }
	public function index_post()
	{
		//Validate user
		$this->validate_token($this->input->get_request_header(X_AUTH_TOKEN));
		
		$this->load->library('form_validation');

		// Set validations
		$this->form_validation->set_rules('name', 'name', 'required|alpha|trim|min_length[1]|max_length[50]');
		$this->form_validation->set_rules('email', 'email', 'required|valid_email');
		$this->form_validation->set_rules('password', 'password', 'required|min_length[5]|max_length[20]');
		
		// Set data to validate
		$this->form_validation->set_data($this->post());
		
		// Run Validations
		if ($this->form_validation->run() == FALSE) {
			return $this->set_response(
				array(),
				$this->lang->line('text_invalid_params'),
				REST_Controller::HTTP_BAD_REQUEST
			);
		}

		$this->load->model('User_model');

		// Check email availability 
		$email_available = $this->User_model->check_email_availability($this->post('email'));

		if(!$email_available){
			return $this->set_response(
				array(), 
				$this->lang->line('text_duplicate_email'),
				REST_Controller::HTTP_CONFLICT
			);
		}
		
		// Get needed data of user
		$user_data = $this->form_validation->need_data_as($this->post(), array(
			'name' => null,
			'email' => null,
			'password' => null
		));

		// Finally save the user			
		$user_id = $this->User_model->add($user_data);

		if(!$user_id){
			return $this->set_response(
				array(),
				$this->lang->line('text_server_error'),
				REST_Controller::HTTP_INTERNAL_SERVER_ERROR
			);
		}
			
		return $this->set_response(
			array(),
			$this->lang->line('text_registration_success'),
			REST_Controller::HTTP_CREATED
		);
	}

	public function check_email_availability_get()
	{
		$this->load->library('form_validation');

		// Validating inputs
		$this->form_validation->set_rules('email', 'email', 'required|valid_email');
		$this->form_validation->set_data($this->get());
		
		if ($this->form_validation->run() == false) {
			return $this->set_response(
				array(),
				$this->lang->line('text_invalid_params'),
				REST_Controller::HTTP_BAD_REQUEST
			);
		}

		$this->load->model('User_model');

		// Check email availability 
		$email_available = $this->User_model->check_email_availability($this->get('email'));
		
		if(!$email_available){
			return $this->set_response(
				array(),
				$this->lang->line('text_duplicate_email'),
				REST_Controller::HTTP_CONFLICT
			);
		}

		return $this->set_response(
			array(),
			$this->lang->line('text_email_available'),
			REST_Controller::HTTP_OK
		);
	}

	public function update_put($id = null)
	{
		$this->load->library('form_validation');

		// Set validations
		$this->form_validation->set_rules('name', 'name', 'alpha|trim|min_length[1]|max_length[50]');
		$this->form_validation->set_rules('email', 'email', 'valid_email');

		// Set data to validate
		$this->form_validation->set_data($this->post());

		//Run Validations
		if ($this->form_validation->run() == FALSE) {
			return $this->set_response(
				array(),
				$this->lang->line('text_invalid_params'),
				REST_Controller::HTTP_BAD_REQUEST
			);
		}

		// Check email availability
		if(isset($data['email'])){
			$email_available = $this->check_email_availability($data['email'], $data['id']);

			if($email_available['status'] !== true){
				return $email_available;
			}
		}

		// Getting needed user data
		$user_data = $this->form_validation->need_data_as($data, array('name'=>null, 'email'=>null));


		$this->load->model('User_model');
		$data = $this->put();
		$data['id'] = $id;
		$res = $this->User_model->update_profile($data);
		$this->set_response($res, $res['statusCode']);
	}

	public function login_post(){
		
		$this->load->helper('date');
		
		$timestamp = now();

		$token = array(
			"iss" => "http://example.org",
			"aud" => "http://example.com",
			"userdetail"=>array("fname"=>"Devang","lname"=>"naghera"),
			"iat" => $timestamp
		);
		
		$jwt = JWT::encode($token, $this->config->item('jwt_key'));
		$this->set_response(array("token"=>$jwt),"Login SuccessFully.!",MY_REST_Controller::HTTP_OK);
	}

}
