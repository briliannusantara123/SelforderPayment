<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Selforder extends CI_Controller {
public function __construct() {
			parent::__construct();
			if($this->session->userdata('username') == ""){
           		$nomeja = $this->session->userdata('nomeja');
  				redirect('login/logout/'.$nomeja);
        	}
			$this->load->model('Item_model');
			$this->load->model('Admin_model');
			$this->load->model('cekstatus_model');
			$this->load->helper('cookie');
			$session = $this->cekstatus_model->cek();

  		if ($session['status'] == 'Payment') {
  			$nomeja = $this->session->userdata('nomeja');
  			redirect('login/logout/'.$nomeja.'/payment');
  		}else if($session['status'] == 'Cleaning'){
  			$nomeja = $this->session->userdata('nomeja');
  			redirect('login/logout/'.$nomeja.'/cleaning');
  		}else if($session['status'] == 'Billing'){
  			$nomeja = $this->session->userdata('nomeja');
  			redirect('login/logout/'.$nomeja.'/billing');
  		}
  		if($session['id_table'] != $this->session->userdata('nomeja')){
  			$nomeja = $this->session->userdata('nomeja');
  			redirect('login/log_out/'.$nomeja);
  		}
		}
	public function index()
	{
		$cs = $this->session->userdata('id');
		$data['no_meja'] = $this->Item_model->nomeja($cs);
		$this->load->view('self_index',$data);
	}
	public function homeOLD()
	{
		$nomeja = $this->session->userdata('nomeja');
		// $cek = $this->Item_model->sub_category_awal();
		// var_dump($cek);exit();
		$id_customer = $this->session->userdata('id');
		$cs = $this->session->userdata('id');
		$data['no_meja'] = $nomeja;
		$data['cart_count'] = $this->Item_model->hitungcart($nomeja);
		$data['sca'] = $this->Item_model->sub_category_awal();
		$data['scm'] = $this->Item_model->sub_category_minuman_awal();
		$data['sub_category'] = "ayam";
		$data['sub_category_minuman'] = "Cold Drink";
		$data['nomeja'] = $this->session->userdata('nomeja');
		$cart_count = $this->Item_model->cart_count($id_customer,$nomeja)->num_rows();
		if($cart_count > 0){
			$cart = $this->Item_model->cart_count($id_customer,$nomeja)->row();//tambahan	
			$cart_total = $cart->total_qty;
		}else{
			$cart_total = 0;
		}
		$data['total_qty'] = $cart_total;
		$data['icon'] = $this->Admin_model->getIcon('home');
		$data['iconfooter'] = $this->Admin_model->getIcon('footer');
		$data['cn'] = $this->Admin_model->getColorCN();
		$data['ch'] = $this->Admin_model->getColorHD();
		$data['cb'] = $this->Admin_model->getColorBTN();
		$data['logo'] = $this->Admin_model->getLogo();
		$data['cekSignature'] = $this->Item_model->cekSignature();
		$this->load->view('self_index',$data);
	}

	public function home()
	{
	    $nomeja = $this->session->userdata('nomeja');
	    $id_customer = $this->session->userdata('id');

	    $data['nomeja'] = $nomeja;

	    // Hitung total qty di cart
	    $cart_count_query = $this->Item_model->cart_count($id_customer, $nomeja);
	    $cart_count = $cart_count_query->num_rows();
	    $data['total_qty'] = $cart_count > 0 ? $cart_count_query->row()->total_qty : 0;

	    // Ambil satu data sub_category dari model
	    $subcategory = $this->Item_model->sub_category_awal_raw();

	    // Jika data ada, lakukan filter tanggal dan jam (karena model hanya 1 data)
	    $nowDate = date('Y-m-d');
	    $nowHour = date('H');

	    $valid = true;
	    if ($subcategory) {
	        // Filter tanggal
	        if (!empty($subcategory['date_from']) && !empty($subcategory['date_to'])) {
	            if ($nowDate < $subcategory['date_from'] || $nowDate > $subcategory['date_to']) {
	                $valid = false;
	            }
	        }
	        // Filter jam
	        if (!empty($subcategory['time_from']) && !empty($subcategory['time_to'])) {
	            if ($nowHour < $subcategory['time_from'] || $nowHour > $subcategory['time_to']) {
	                $valid = false;
	            }
	        }
	    } else {
	        $valid = false; // jika data kosong otomatis invalid
	    }

	    $data['sca'] = $valid ? $subcategory['sub_category'] : '';

	    // Data lain
	    $data['scm'] = $this->Item_model->sub_category_minuman_awal();
	    $data['sub_category'] = "ayam";
	    $data['sub_category_minuman'] = "Cold Drink";

	    // Icon, warna, logo, dan cek signature
	    $data['icon'] = $this->Admin_model->getIcon('home');
	    $data['iconfooter'] = $this->Admin_model->getIcon('footer');
	    $data['cn'] = $this->Admin_model->getColorCN();
	    $data['ch'] = $this->Admin_model->getColorHD();
	    $data['cb'] = $this->Admin_model->getColorBTN();
	    $data['logo'] = $this->Admin_model->getLogo();
	    $data['cekSignature'] = $this->Item_model->cekSignature();
	    $trans = $this->db->order_by('create_date', 'DESC')
                  ->get_where('sh_t_transactions', array('id_customer' => $id_customer))
                  ->row();
        if ($trans->parent_id != 0) {
        	$data['cekpay'] = $this->Item_model->getitem($trans->parent_id,'parent');
        }else{
        	$data['cekpay'] = $this->Item_model->getitem($trans->id,'notparent');
        }
        
	    $this->load->view('self_index', $data);
	}


	public function landing()
	{
		$data = [
			'username' => $this->session->userdata('username'),
			'nomeja' => $this->session->userdata('nomeja'),
			'logo' => $this->Admin_model->getLogo(),
			'cn' => $this->Admin_model->getColorCN(),
			'ch' => $this->Admin_model->getColorHD(),
			'cb' => $this->Admin_model->getColorBTN(),
		];
		$this->load->view('landing',$data);
	}
		function cekinternet()
	{
		$this->load->view('cekinternet');
	}
}
