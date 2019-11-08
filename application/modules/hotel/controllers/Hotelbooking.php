<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH.'/libraries/MY_REST_Controller.php';
//require  'vendor/autoload.php';


class Hotelbooking extends MY_REST_Controller {

    private $urlHotelstone;
    private $urlTravelanda;
	public function __construct()
	{		
		parent::__construct();
		$this->urlHotelstone = "http://dev.hotelston.com/ws/HotelService.HotelServiceHttpSoap11Endpoint/";
        $this->load->model('Hotel_search_model');
        $this->urlTravelanda = 'http://xmldemo.travellanda.com/xmlv1';
        $this->load->model('Hotel_search_model');
    }

    public function hotel_booking_post() // common to all apis
	{
        //var_dump($this->post('api_name')); die;
        $request = array(
			'api_name' => $this->post('api_name'),
            'optionid' => $this->post('optionid'),
            'hotelid' => $this->post('hotelid'),
            'currency' => $this->post('currency'),
			'clientNationality' => $this->post('clientNationality'),          
		   'checkin_date' => $this->post('checkin_date'),
		   'checkout_date' => $this->post('checkout_date'),
           'room_count' => $this->post('room_count'),
           'per_room_details' => $this->post('per_room_details'),
		   'adults_per_room' => $this->post('adults_per_room'),
		   'children_per_room' => $this->post('children_per_room'),
		   'child_age_per_room' => $this->post('child_age_per_room')			
           );        

        if($this->post('api_name') == 'travellanda')
        {
            $this->hotelPolicies($this->post('optionid'));
        }
        if($this->post('api_name') == 'hotelstone')
        {
           $data =  $this->checkAvailability($request);
           return  $this->response($data, REST_Controller::HTTP_OK);
        }
    }
    public function hotelPolicies($option_id)
    {
        $xml = '<Request>
        <Head>
        <Username>c46c3f42ea63d41a</Username>
        <Password>FAdI1OFRXgV1</Password>
        <RequestType>HotelPolicies</RequestType>
        </Head>
        <Body>
        <OptionId>' . $option_id . '</OptionId>
        </Body>
        </Request>';
        $fields = array(
            'xml' => $xml
        );
       
        $result = $this->curl_travellanda($fields);
        $myFile1 = APPPATH."modules/hotel/travellanda_hotel_cancellation_policy_response.xml";
        $fh1 = fopen($myFile1, 'w', 'r');
        $stringData = $result;
        fwrite($fh1, $stringData);
        fclose($fh1);
    }

    public function checkAvailability($request = array())
    {
        $room = '';
		//var_dump($request['room_count']); die;
	   for($i=1;$i<= $request['room_count']; $i++)  
	   {
			  $room .= '<xsd:room xsd:adults="'.$request['adults_per_room']['room-'.$i].'" xsd:children="'.$request['children_per_room']['room-'.$i].'">';
              $room .= '<xsd:roomId>' . $request['per_room_details']['roomid-'.$i] . '</xsd:roomId>
              <xsd:roomTypeId>' . $request['per_room_details']['roomtypeid-'.$i] . '</xsd:roomTypeId>
              <xsd:boardTypeId>' . $request['per_room_details']['borardtypeid-'.$i] . '</xsd:boardTypeId>';  
              if(isset($request['child_age_per_room']['room-'.$i]))
			  {
				  for($j=0;$j< count($request['child_age_per_room']['room-'.$i]);$j++)
				  {
					  $room .='<xsd:childAge>'.$request['child_age_per_room']['room-'.$i][$j].'</xsd:childAge>';
				  }
				  
			  }
			  $room .= '</xsd:room>';
       }
       
       $xml_new = '<?xml version="1.0" encoding="UTF-8"?>                            
            <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsd="http://request.ws.hotelston.com/xsd" xmlns:xsd1="http://types.ws.hotelston.com/xsd">
            <soapenv:Header/>
            <soapenv:Body>
                <xsd:CheckAvailabilityRequest>

                    <!--1 to 2 repetitions:-->
                    <xsd:locale>en</xsd:locale>
                    <xsd:loginDetails xsd1:email="info@ajwal.travel" xsd1:password="Ajwal@2019"/>
                    <xsd:currency>EUR</xsd:currency>
                    <!--Optional:-->
                    <xsd:netRates>true</xsd:netRates>
                    <!--Optional:-->
                
                    <xsd:criteria>
                        <xsd:checkIn>' . $request['checkin_date'] . '</xsd:checkIn>
                        <xsd:checkOut>' .$request['checkout_date'] . '</xsd:checkOut>
                        <!--Optional:-->
                        <xsd:hotelId>' . $request['hotelid'] . '</xsd:hotelId>

                    
                        <xsd:clientNationality>' . $request['clientNationality'] . '</xsd:clientNationality>
                        <!--Optional:-->
                    
                        <!--1 to 3 repetitions:-->
                    ' . $room . '
                        
                    </xsd:criteria>
                    </xsd:CheckAvailabilityRequest>

            </soapenv:Body>
                </soapenv:Envelope>';
        $data31 = $this->curl_hotelstone($xml_new);
        
        $myFile1 = APPPATH."modules/hotel/hotelstone_checkavalability.xml";
        $fh1 = fopen($myFile1, 'w', 'r');
        $stringData = $data31;
        fwrite($fh1, $stringData);
        fclose($fh1);
        
        $singlequote = '"';
        $data31 = str_replace("&lt;", "<", $data31);
        $data31 = str_replace("&gt;", ">", $data31);
        $data31 = str_replace("&quot;", $singlequote, $data31);


      /*  $myFile1 =  $_SESSION['file_num']."-check_availability_response.xml";
        $fh1 = fopen('xmllogs/' . $myFile1, 'w', 'r');
        $stringData = $data31;
        fwrite($fh1, $stringData);
        fclose($fh1);*/ //open wen actual implementation
        $data_checkavailability = new DOMDocument();
        $data_checkavailability->loadXML($data31);
        $data_checkavailability1 = $data_checkavailability->getElementsByTagName('CheckAvailabilityResponse')->item(0);
        $data_checkavailability2 = $data_checkavailability1->getElementsByTagName('hotel');
        if ($data_checkavailability2->length > 0) {
            $data_checkavailability2 = $data_checkavailability1->getElementsByTagName('hotel')->item(0);
            //echo "<pre>";print_r($data_checkavailability2);exit;
            $hotel_id = $data_checkavailability2->getAttribute('xsd:id');
            $hotel_name = $data_checkavailability2->getAttribute('xsd:name');
            $data_checkavailability3 = $data_checkavailability1->getElementsByTagName('room');
            foreach ($data_checkavailability3 as $data_checkavailability4) {
                $seqno = $data_checkavailability4->getAttribute('xsd:seqNo');
                $room_id = $data_checkavailability4->getAttribute('xsd:id');
                $room_specialOffer = $data_checkavailability4->getAttribute('xsd:specialOffer');
                $amount_val = $data_checkavailability4->getAttribute('xsd:price');
                $room_visaSupport = $data_checkavailability4->getAttribute('xsd:visaSupport');
                $data_checkavailability5 = $data_checkavailability4->getElementsByTagName('boardType')->item(0);
                $boardtype_groupId = $data_checkavailability5->getAttribute('xsd:groupId');
                $boardtype_groupName = $data_checkavailability5->getAttribute('xsd:groupName');
                $boardtype_id = $data_checkavailability5->getAttribute('xsd1:id');
                $boardtype_name = $data_checkavailability5->getAttribute('xsd1:name');
                $data_checkavailability6 = $data_checkavailability4->getElementsByTagName('roomType')->item(0);
                $roomtype_Id = $data_checkavailability6->getAttribute('xsd1:id');
                $roomtype_hotelstonName = $data_checkavailability6->getAttribute('xsd:hotelstonName');
                $roomtype_Name = $data_checkavailability6->getAttribute('xsd1:name');
               
               // $total_price = $room_price + ($room_price * $_SESSION['hotel_search']['b2c_hotel_markup'] / 100 );
			   
			   // $room_price = $amount_val;
				
				$totalAmount=$amount_val;

                /*  if(isset($_SESSION['agents']['id']) && $_SESSION['agents']['id'] !='' )
					 {
						$resultMarkup=$this->home->calculateMarkup($totalAmount,$_SESSION['country_hotel_type'],$_SESSION['country_hotel_markup'],$apiMarkupType,$apiMarkupAmount,$this->hotel_type,$this->hotel_markup,$this->agent_hotel_type,$this->agent_hotel_markup);
					 }
					 else 
					 {
						$resultMarkup=$this->home->calculateMarkup($totalAmount,$_SESSION['country_hotel_type'],$_SESSION['country_hotel_markup'],$apiMarkupType,$apiMarkupAmount,$this->hotel_type,$this->hotel_markup,$this->agent_hotel_type='',$this->agent_hotel_markup='');
					 }
					
					if($this->hotel_discount_type == 0)
					{
						 $discount=($totalAmount *$this->hotel_discount  )/100; 
					}
					else
					{
						 $discount=$this->hotel_discount;  
					}
					$total_price=$totalAmount+$resultMarkup-$discount; */
				    
                  /*   if((isset($_SESSION['to_currency_place']) && $_SESSION['to_currency_place'] != '') || (isset($_SESSION['to_currency_place']) && $_SESSION['to_currency_place'] != 'EUR'))
                    {
                        $total_price=ceil($total_price*$currency_converted);
                    }else{ */
                        $total_price=$totalAmount;
                   // }
				// echo "<pre>"; print_r($total_price);die;
                $value = array(
                    'room_seqno' => $seqno,
                    'roomid' => $room_id,
                    'room_specialoffer' => $room_specialOffer,
					'room_price' => $total_price,
                    //'room_price' => ($total_price * $_SESSION['hotel_search']['currency_converter']),
                    'room_visasupport' => $room_visaSupport,
                    'boardtype_groupid' => $boardtype_groupId,
                    'boardtype_id' => $boardtype_id,
                    'boardtype_groupname' => $boardtype_groupName,
                    'boardtype_name' => $boardtype_name,
                    'roomtype_id' => $roomtype_Id,
                    //'currency' => $_SESSION['to_currency_place'],
                    'roomtype_hotelstonname' => $roomtype_hotelstonName,
                    'roomtype_name' => $roomtype_Name,
                ); 
                $this->Hotel_search_model->update_hotel_room_search_response($value, $seqno, $room_id, $result[0]->hotelCode);
            }
            // echo "<pre>";print_r($this->db->last_query());exit;
            //end of check availability
            $hotel_search_id = $this->Hotel_search_model->get_hotel_searchid($hotel_id);
			$room_xml='';
            for ($i = 0; $i < $request['room_count']; $i++) {
                $room_xml .= '
            <xsd:room>
            <xsd:roomId>' . $request['per_room_details']['roomid-'.$i] . '</xsd:roomId>
            <xsd:roomTypeId>' . $request['per_room_details']['roomtypeid-'.$i]. '</xsd:roomTypeId>
            <xsd:boardTypeId>' . $request['per_room_details']['borardtypeid-'.$i]. '</xsd:boardTypeId>
            </xsd:room>';
            }

            $xml = '<?xml version="1.0" encoding="UTF-8"?>
            <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsd="http://request.ws.hotelston.com/xsd" xmlns:xsd1="http://types.ws.hotelston.com/xsd">
            <soapenv:Header/>
            <soapenv:Body>
                <xsd:BookingTermsRequest>
                    <!--1 to 2 repetitions:-->
                    <xsd:locale>en</xsd:locale>
                    <xsd:loginDetails xsd1:email="info@ajwal.travel" xsd1:password="Ajwal@2019"/>
                    <xsd:currency>EUR</xsd:currency>
                    <xsd:netRates>true</xsd:netRates>
                    <xsd:hotelId>' . $request['hotelid'] . '</xsd:hotelId>
                    <xsd:searchId>' . $hotel_search_id . '</xsd:searchId>

                    <!--1 to 3 repetitions:-->
                    ' . $room_xml . '

            </xsd:BookingTermsRequest>
            </soapenv:Body>
            </soapenv:Envelope>';


            //echo"<pre>"; print_r($xml);exit;
            $data31 = $this->curl_hotelstone($xml);
           /* $_SESSION['file_num'] = rand(10, 1000);
            $myFile = $_SESSION['file_num']."-get_booking_terms_request.xml";
            $fh = fopen('xmllogs/' . $myFile, 'w', 'r');
            $stringData = $xml;
            fwrite($fh, $stringData);
            fclose($fh);*/

            //$myFile1 = $_SESSION['file_num']."-get_booking_terms_response.xml";
            $myFile1 = APPPATH."modules/hotel/get_booking_terms_response.xml";
            //$fh1 = fopen('xmllogs/' . $myFile1, 'w', 'r');
            $fh1 = fopen($myFile1, 'w', 'r');
            $stringData = $data31;
            fwrite($fh1, $stringData);
            fclose($fh1);

            $data = new DOMDocument();
            $data->loadXML($data31);
            $data1 = $data->getElementsByTagName('BookingTermsResponse')->item(0);

            $data2 = $data1->getElementsByTagName('bookingTerms');
            //echo "<pre>";p
			$cancellation_policy='';
            foreach ($data2 as $data3) {
                $data4[] = $data3->getAttribute('xsd1:seqNo');
                $sqno_count = $data3->getAttribute('xsd1:seqNo');
                $cancellationPolicy_date = $data3->getElementsByTagName('cancellationPolicy')->item(0)->getAttribute('xsd1:cxlDate');
                $data6 = $data3->getElementsByTagName('cancellationRule')->item(0);
                $timeUnit = $data6->getElementsByTagName('cancellationDeadline')->item(0)->getAttribute('xsd1:timeUnit');
                $effectMoment = $data6->getElementsByTagName('cancellationDeadline')->item(0)->getAttribute('xsd1:effectMoment');
                $cancellationDeadline_day_count = $data6->getElementsByTagName('cancellationDeadline')->item(0)->getAttribute('xsd1:amount');
                if ($cancellationDeadline_day_count == 0) {
                    $cancellationDeadline_day_count = '';
                }
                $penaltyUnit = $data6->getElementsByTagName('cancellationPenalty')->item(0)->getAttribute('xsd1:penaltyUnit');
                //$penalty_amount = (($data6->getElementsByTagName('cancellationPenalty')->item(0)->getAttribute('xsd1:amount')) * ($_SESSION['hotel_search']['currency_converter']));
				
				$penalty_amount = ($data6->getElementsByTagName('cancellationPenalty')->item(0)->getAttribute('xsd1:amount'));
                
                if ($penaltyUnit == 'PERCENTAGE') {
                    $cancellation_policy[$sqno_count] = 'Cancellation made ' . $cancellationDeadline_day_count .' '. $timeUnit . '(s)&nbsp;' . $effectMoment . 'or later will be charged ' . $penalty_amount . '% of the booking value';
                    echo "<br>";
                }

                if ($penaltyUnit == 'NUMBER_OF_NIGHTS') {
                    $cancellation_policy[$sqno_count] .= 'Cancellation made ' . $cancellationDeadline_day_count . $timeUnit . '(s)&nbsp;' . $effectMoment . 'or later will be charged ' . $penalty_amount . 'number of nights of the booking value<br>';
                    echo "<br>";
                }
            }//echo "<pre>";print_r($cancellation_policy);
            //$this->db->truncate('hotel_booking_reports');
            for ($i = 0; $i < $request['room_count']; $i++) {

                // if ($data4[$i] == $result[$i]->room_sqno) {

                //$this->Hotelston_Model->update_cancellation_policy($cancellation_policy[$i], $result[$i]->room_code, $result[$i]->id, $result[$i]->hotelCode, $result[$i]->room_sqno);
                //}
                $this->Hotel_search_model->update_cancellation_policy_to_temp_table($cancellation_policy[$i], $request['hotelid']);
                
                
            }
            //$cancel_policy = $this->Hotelston_Model->get_cancel_policy($result[0]->hotel_id);
            // echo "<pre>"; print_r($data_value);die;
			/*if ($id) {
                $result[0] = $this->Hotelston_Model->get_booking_terms_board_details($id);
                // echo "<pre>";print_r($id1);exit;
            }
            if ($id1) {
                $result[1] = $this->Hotelston_Model->get_booking_terms_board_details($id1);
            }
            if ($id2) {
                $result[2] = $this->Hotelston_Model->get_booking_terms_board_details($id2);
            }*/
            $result = $this->Hotel_search_model->hotelstone_get_hotel_detail($hotel_id);
//echo "<pre>";print_r($result);exit;
            return json_encode($result);
           // $this->load->view('hotels/customer_login', $data_value);
        } else {
            return 'No hotel available';
        }
    }
    public function curl_hotelstone($xml) {
		 
		$URL3 = $this->urlHotelstone;
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
    
}