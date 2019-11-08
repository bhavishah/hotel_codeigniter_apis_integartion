<?php

class Hotel_search_model extends CI_Model {
	
	function __construct() {
		
		parent::__construct();

		//Table Name
		$this->table_name_request_table = "hotelstone_hotesearch_request";
		$this->table_name_hotel_search_result = "hotelstone_hotel_search_result";
		$this->table_name_hotel_search_room = "hotelstone_hotel_search_room_result";
	}

	function hotelstone_get_hotel_detail($hotelid){
			
			$this->db->select('searchid');
        $this->db->from($this->table_name_hotel_search_result);
        $this->db->where('hotelid', $hotelid);
        $query = $this->db->get();
		//echo $this->db->last_query();
        if ($query->num_rows() > 0) {
            return $query->row();
        } else {
            return '';
        }
	}
	function add_hotel_search_request($request_data = array()){
		
		// Encrypt password
		$request_data['adults_per_room'] = json_encode($request_data['adults_per_room']);
		$request_data['children_per_room'] = json_encode($request_data['children_per_room']);
		$request_data['child_age'] = json_encode($request_data['child_age']);

		// Saving the user
		$insert_query = $this->db->insert($this->table_name_request_table, $request_data);
		$insert_id = $this->db->insert_id();
		if(!$insert_id){
			return false;
		}

		return $insert_id;
	}
	function update_hotel_room_search_response($value, $seqno, $room_id, $hotel_id)
	{
		$this->db->where('room_seqno', $seqno);
        $this->db->where('hotelid', $hotel_id);
        $this->db->where('roomid', $room_id);
        $this->db->update('hotelstone_hotel_search_room_result', $value);
	}
	function get_hotel_searchid($hotelid)
	{
		$this->db->select('searchid');
        $this->db->from($this->table_name_hotel_search_result);
        $this->db->where('hotelid', $hotelid);
        $query = $this->db->get();
		//echo $this->db->last_query();
        if ($query->num_rows() > 0) {
            return $query->row();
        } else {
            return '';
        }
	}
	public function update_cancellation_policy_to_temp_table($cancellation_policy, $hotel_id) {

        $result = array(
           
            'cancellation_policy' => $cancellation_policy,
           
            'api'=>'hotelston'
        );
        //$this->db->where('session_id',$_SESSION['hotel_search']['session_id']);

        $this->db->where('hotelid',$hotel_id); 
       // $this->db->where('id',$id); 
        $this->db->update($this->table_name_hotel_search_result, $result);
	}
	
	function add_hotel_search_response($data = array())
	{
		$insert  = $this->db->insert_batch($this->table_name_hotel_search_result, $data);

		if(!$insert){
			return false;
		}

		return true;
	}
	function add_hotel_search_rooms_response($data = array())
	{
		$insert  = $this->db->insert_batch($this->table_name_hotel_search_room, $data);

		if(!$insert){
			return false;
		}

		return true;
	}
	function update($id = null, $user_data = array()){

		if(!empty($user_data)){

			$user_data['modified_at'] = date('Y-m-d H:i:s');

			$this->db->reset_query();

			// Updating the user
			$update_query = $this->db->where('id', $id)->update($this->table_name, $user_data);
			
			if(!$update_query){
				return false;
			}
		}

		return true;
	}

	function login($data = array()){

		$this->load->library('form_validation');

		// Setup validation rules
		$rules = array(
			$this->validations['email'],
			$this->validations['password']
		);

		// Set validation rules
		$this->form_validation->set_required($rules, 'email', 'password');
		$this->form_validation->set_rules($rules);

		// Set data to validate
		$this->form_validation->set_data($data);

		//Run Validations
		if ($this->form_validation->run() == FALSE) {
			return get(REST_Controller::HTTP_BAD_REQUEST, $this->lang->line('text_invalid_params'), false);
		}

		// Check email and pass
		$count = $this->db->from($this->table_name)->where('id', $data['id'])->get()->num_rows();
		
		if(!$count){
			return get(REST_Controller::HTTP_NOT_FOUND, $this->lang->line('text_invalid_creds'), false);
		}

		

		return get(REST_Controller::HTTP_INTERNAL_SERVER_ERROR, $this->lang->line('text_user_updated'), true);
	}
}

?>