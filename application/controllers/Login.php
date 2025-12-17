<?php 
class Login extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->library('form_validation');
		$this->load->model('Item_model');
		$this->load->model('Admin_model');
	}
	public function index()
	{
		$this->form_validation->set_rules('username','username','trim|required');

		if ($this->form_validation->run() == FALSE) {
			$this->load->view('login');	
		}else{
			$this->_login($nomeja);
		}
		
	}
	
	public function log($nomeja = NULL)
	{
	    // VALIDASI
	    $this->form_validation->set_rules('username', 'Username', 'trim|required');

	    // DATA VIEW
	    $data['cn']   = $this->Admin_model->getColorCN();
	    $data['ch']   = $this->Admin_model->getColorHD();
	    $data['cb']   = $this->Admin_model->getColorBTN();
	    $data['logo'] = $this->Admin_model->getLogo();

	    // MEJA
	    if ($nomeja != NULL) {
	        $data['nomeja'] = $nomeja;
	        $this->session->set_userdata('nomeja', $nomeja);
	    } else {
	        $data['nomeja'] = $this->session->userdata('nomeja');
	        $nomeja = $data['nomeja'];
	    }

	    // TANGGAL (HARUS SAMA FORMAT)
	    $date = date('Y-m-d');

	    // CEK STATUS MEJA TERAKHIR
	    $session = $this->db
	        ->select('status')
	        ->from('sh_rel_table')
	        ->where('id_table', $nomeja)
	        ->where('LEFT(created_date,10) =', $date, FALSE)
	        ->order_by('id', 'DESC')
	        ->limit(1)
	        ->get()
	        ->row();

	    // JIKA MEJA CLEANING
	    if ($session && $session->status == 'Cleaning') {
	        $this->session->set_flashdata(
	            'error',
	            'Table status is Cleaning, cannot access menu page.'
	        );
	        $this->load->view('login', $data);
	        return;
	    }

	    // CEK LOG
	    $log = $this->Item_model->log($nomeja)->result();

	    if (!$log) {
	    	redirect('index.php/login/log/'.$nomeja);
	        // $this->load->view('login', $data);
	        return;
	    }

	    // FORM
	    if ($this->form_validation->run() == FALSE) {
	        $this->load->view('login', $data);
	    } else {
	        $this->_login($nomeja);
	    }
	}

	public function _login($nomeja)
	{
		// $user_order_id = $this->Item_model->kode($nomeja);
		$user_order_id = $this->input->ip_address();
		// var_dump($user_order_id);exit();
		$username = $this->input->post('username');
		$no_hp = $this->input->post('no_hp');
		$date = date('Y-m-d');
		$where = "sh_rel_table.id_table = '".$nomeja."' and left(created_date,10) ='".$date."' and status in('Order','Dining','Payment') and sh_m_customer.customer_name = '".$username."' and sh_m_customer.no_telp = '".$no_hp."'";
		$this->db->select('*');
		$this->db->from('sh_rel_table');
		$this->db->join('sh_m_customer', 'sh_m_customer.id = sh_rel_table.id_customer');
		$this->db->where($where);
		$log = $this->db->get()->row_array();

		// $user = $this->db->get_where('sh_m_customer',['passcode' => $passcode,'left(create_date,10) = ' => $date])->row_array();
		// // var_dump($user);exit();
		
		// $meja = $this->db->get_where('sh_rel_table',$where)->row_array();
		// var_dump($meja);die();
			if ($log) {
				$data = [
					'username' => $log['customer_name'],
					'no_telp' => $log['no_telp'],
					'id' => $log['id'],
					'nomeja' => $nomeja,
					'user_order_id' => $user_order_id
				];
				$a = $nomeja;
				$d = [
						'created_date' => date('Y-m-d'),
						'id_table' => $nomeja,
						'user_order_id' =>$user_order_id
					 ];
				$this->db->insert('sh_log_user',$d);
				$this->session->set_userdata($data);
				$id_customer = $this->session->userdata('id');
				$id_trans = $this->db->get_Where('sh_t_transactions', array('id_customer'=> $id_customer))->row();
				$cabang = $this->db->order_by('id',"desc")
			  			->limit(1)
			  			->get('sh_m_cabang')
			  			->row('id');
			  	$ip_address = $this->input->ip_address();
			  	$cust = $this->session->userdata('username');
				$dataevent = [
					'event_type' => 'Login SO',
					'cabang' => $cabang,
					'id_trans' => $id_trans->id,
					'id_customer' => $this->session->userdata('id'),
					'event_date' => date('Y-m-d H:i:s'),
					'user_by' => $this->session->userdata('username'),
					'description' => 'Melakukan Login dengan IP: '.$ip_address,
					'created_date' => date('Y-m-d'),
				];
				$result = $this->db->insert('sh_event_log',$dataevent);
				$this->session->set_flashdata('success','Login Successfully, Please Order !');
				redirect('index.php/selforder/landing/'.$a);
			}else{
				redirect('index.php/login/log/'.$nomeja);
			}
	}
	public function logout($nm=null,$cek=null)
	{

		
			// echo "<div class='container' style='margin-top:360px;margin-left:30px;margin-right:30px;font-size:50px;'><h3 style='text-align:center;background-color:#198754;color:white;padding-top:500px;padding-bottom:500px;border-radius:5%'>SELF ORDER TIDAK TERSEDIA !!</h3></div>";
		
		$cs = $this->session->userdata('id');
		$nomeja = $this->Item_model->nomeja($cs);
		 // $this->session->set_flashdata('error','Due to inactivity, you have been logout please kindly login again using your passcode');
		$id_customer = $this->session->userdata('id');
		$id_trans = $this->db->get_Where('sh_t_transactions', array('id_customer'=> $id_customer))->row();
		$ip_address = $this->input->ip_address();
		if ($id_trans) {
			$it = $id_trans->id;
			$ic = $id_customer;
			$usr = $this->session->userdata('username');
			$des = 'Melakukan Logout dengan IP: '.$ip_address;
		}else{
			$it = 0;
			$ic = 0;
			$usr = "System";
			$des = 'Logout oleh system timeout';
		}
		$cabang = $this->db->order_by('id',"desc")
			  	->limit(1)
			  	->get('sh_m_cabang')
			  	->row('id');
			 $cust = $this->session->userdata('username');
		$dataevent = [
			'event_type' => 'Logout SO',
			'cabang' => $cabang,
			'id_trans' => $it,
			'id_customer' => $ic,
			'event_date' => date('Y-m-d H:i:s'),
			'user_by' => $usr,
			'description' => $des,
			'created_date' => date('Y-m-d'),
		];
		$result = $this->db->insert('sh_event_log',$dataevent);
		$this->session->unset_userdata('username');
		$this->session->unset_userdata('id');
		$this->session->unset_userdata('no_telp');
		if ($cek == 'billing') {
			$this->session->set_flashdata('error','Your table is currently in Billing status. You cannot log in to Self Order');
		}elseif($cek == 'payment'){
			$this->session->set_flashdata('error','Your table is currently in Payment status. You cannot log in to Self Order');
		}elseif($cek == 'payment'){
			$this->session->set_flashdata('error','Your table is currently in Cleaning status. You cannot log in to Self Order');
		}else{
			$this->session->set_flashdata('error','Please Scan the QR Code to Login Again');
		}
		
		redirect('index.php/login/log/');
	}
	public function log_out($nm=null)
	{
		$id_customer = $this->session->userdata('id');
		$id_trans = $this->db->get_Where('sh_t_transactions', array('id_customer'=> $id_customer))->row();
		$cabang = $this->db->order_by('id',"desc")
			  	->limit(1)
			  	->get('sh_m_cabang')
			  	->row('id');
			 $ip_address = $this->input->ip_address();
			 $cust = $this->session->userdata('username');
		// $dataevent = [
		// 	'event_type' => 'Logout SO',
		// 	'cabang' => $cabang,
		// 	'id_trans' => $id_trans->id,
		// 	'id_customer' => $this->session->userdata('id'),
		// 	'event_date' => date('Y-m-d H:i:s'),
		// 	'user_by' => $this->session->userdata('username'),
		// 	'description' => 'Melakukan Logout dengan IP: '.$ip_address,
		// 	'created_date' => date('Y-m-d'),
		// ];
		// $result = $this->db->insert('sh_event_log',$dataevent);
		$this->session->unset_userdata('username');
		$this->session->unset_userdata('id');
		$this->session->unset_userdata('no_telp');
		$this->session->set_flashdata('error','You have logged out because you changed tables');
		
		redirect('login/log/'.$nm);
	}

	//ADMIN
	public function admin()
	{
		$this->form_validation->set_rules('passcode','passcode','trim|required');

		if ($this->form_validation->run() == FALSE) {
			$data['logo'] = $this->Admin_model->getLogo();
			$data['cn'] = $this->Admin_model->getColorCN();
			$this->load->view('admin/loginAdmin',$data);	
		}else{
			$this->loginadmin();
		}
		
	}
	public function loginadmin() {
	    $username = $this->input->post('username');
	    $password = $this->input->post('password');
	    $this->db->where('username', $username);
	    $user = $this->db->get('sh_user_so')->row();
	    
	    if ($user) {
	        if (md5($password) == $user->password) {
	            $data = [
	                'usernameadmin' => $user->username,
	                'role' => $user->role,
	                'id' => $user->id,
	            ];
	            $this->session->set_userdata($data);
	            $this->session->set_flashdata('success', 'Login Successfully');
	            redirect('Admin');
	        } else {
	        	
	            $this->session->set_flashdata('error', 'Username atau password salah');
	            $data['logo'] = $this->Admin_model->getLogo();
	            $data['cn'] = $this->Admin_model->getColorCN();
	            $this->load->view('admin/loginAdmin',$data);    
	        }
	    } else {
	        echo "Username tidak ditemukan!";
	    }
	}

	public function logoutAdmin($nm=null,$pm=NULL)
	{
		$cs = $this->session->userdata('id');
		$id_customer = $this->session->userdata('id');
		$ip_address = $this->input->ip_address();
		$this->session->unset_userdata('usernameadmin');
		$this->session->unset_userdata('role');
		$this->session->unset_userdata('id');
		$this->session->set_flashdata('success','Successfully Logged Out');
		redirect('login/admin');
	}
	public function changepw()
	{
		$po = $this->input->post('passwordOLD');
		$usr = $this->input->post('username');
		$cekpw = $this->Admin_model->cekpw($po,$usr);
		if ($cekpw) {
			$data = [
				'password' => md5($this->input->post('password')),
			];
			$this->db->where('username',$usr);
			$this->db->where('password',md5($po));
			$this->db->update('sh_user_so',$data);
			$this->session->set_flashdata('success','Password has been successfully changed');
		}else{
			$this->session->set_flashdata('error','The current password you entered is incorrect.');
		}
		redirect('admin');
	}
	public function loginremote($nomeja,$ip) {
	    $date = date('Y-m-d');
		$where = "sh_rel_table.id_table = '".$nomeja."' and left(created_date,10) ='".$date."' and status in('Order','Dining','Billing')";
		$this->db->select('*');
		$this->db->from('sh_rel_table');
		$this->db->join('sh_m_customer', 'sh_m_customer.id = sh_rel_table.id_customer');
		$this->db->where($where);
		$log = $this->db->get()->row_array();
			if ($log) {
				$data = [
					'username' => $log['customer_name'],
					'no_telp' => $log['no_telp'],
					'id' => $log['id'],
					'nomeja' => $nomeja,
					'user_order_id' => $ip
				];
				$a = $nomeja;
				$this->session->set_userdata($data);
				// $id_customer = $this->session->userdata('id');
				// $id_trans = $this->db->get_Where('sh_t_transactions', array('id_customer'=> $id_customer))->row();
				// $cabang = $this->db->order_by('id',"desc")
			 //  			->limit(1)
			 //  			->get('sh_m_cabang')
			 //  			->row('id');
			 //  	$ip_address = $ip;
			 //  	$cust = $this->session->userdata('username');
				// $dataevent = [
				// 	'event_type' => 'Login SO',
				// 	'cabang' => $cabang,
				// 	'id_trans' => $id_trans->id,
				// 	'id_customer' => $this->session->userdata('id'),
				// 	'event_date' => date('Y-m-d H:i:s'),
				// 	'user_by' => $this->session->userdata('username'),
				// 	'description' => 'Melakukan Login dengan IP: '.$ip_address,
				// 	'created_date' => date('Y-m-d'),
				// ];
				// $result = $this->db->insert('sh_event_log',$dataevent);
				// $this->session->set_flashdata('success','Login Successfully, Please Order !');
				redirect('selforder/landing/'.$a);
			}else{
				$a = $nomeja;
				// $this->session->set_flashdata('error','Wrong Passcode !');
				redirect('login/log/'.$a);
			}
	}
}
?>
