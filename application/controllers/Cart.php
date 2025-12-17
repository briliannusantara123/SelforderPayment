<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use Xendit\Xendit;
use Xendit\VirtualAccounts;

class Cart extends CI_Controller {
function __construct()
		{
			parent::__construct();
			if($this->session->userdata('username') == ""){
           		$nomeja = $this->session->userdata('nomeja');
  				redirect('login/logout/'.$nomeja);
        	}
			$this->load->model('Item_model');
			$this->load->model('Admin_model');
			$this->load->model('Paket_model');
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

	public function testing()
	{
		$this->load->library('user_agent');

		if ($this->agent->is_mobile()) {
		    $device = $this->agent->mobile();
		   
		} else {
		    $device = "DEKSTOP";
		}
		$browser = $this->agent->browser();
		$version = $this->agent->version();
		$platform = $this->agent->platform();
		$robot = $this->agent->robot();
		$ip_address = $this->input->ip_address();
		echo "IP address pengguna adalah: " . $ip_address . "<br>";
		echo "Browser yang digunakan: " . $browser . "<br>";
		echo "Versi browser yang digunakan: " . $version . "<br>";
		echo "Platform yang digunakan: " . $platform . "<br>";
		echo "Device yang digunakan: " . $device . "<br>";
		echo "Apakah user agent adalah robot: " . ($robot ? 'Ya' : 'Tidak') . "<br>";
	}
	public function index()
	{
		$id_customer = $this->session->userdata('id');
		$data['item'] = $this->Item_model->getDataOrder($id_customer)->result();
		
		$this->load->view('ordersementara',$data);
	}
	public function home($nomeja,$cek=NULL,$sub=NULL,$no=NULL)
	{
		$uoi = $this->session->userdata('user_order_id');
		$sharp = str_replace("%20","_", $sub);
		$url = $sub.'#'.$sharp;
		$id_customer = $this->session->userdata('id');
		$id_trans = $this->db->get_Where('sh_t_transactions', array('id_customer'=> $id_customer))->row();
		$query = $this->db->where('id_table', $nomeja)
         ->where('id_customer', $id_customer)
         ->where('DATE(entry_date)', date('Y-m-d')) // Gunakan DATE() untuk hanya membandingkan tanggal
         ->where('user_order_id', $uoi)
         ->where('id_trans', $id_trans->id)
         ->get('sh_cart');
		$jumlahData = $query->num_rows();
		$queryadd = $this->db->where('id_table',$nomeja)->where('id_customer',$id_customer)->where('DATE(entry_date)', date('Y-m-d'))->where('user_order_id',$uoi)->where('id_trans',$id_trans->id)->where('addons',1)->get('sh_cart');
		$jumlahDataadd = $queryadd->num_rows();
		$data['total'] = $this->Item_model->totalSubOrder($nomeja,$id_customer,$uoi,$id_trans->id);
		$data['hitungbayar'] = $this->Item_model->totalbayar($id_trans->id);
		$data['item'] = $this->Item_model->cart($id_customer)->result();
		$data['nomeja'] = $nomeja;
		$data['jumlah'] = $jumlahData;
		$data['jumlahadd'] = $jumlahDataadd;
		$data['itempaket'] = [];
		$notrans = $this->db->order_by('id',"asc")->where('id_customer',$id_customer)
  			->limit(1)
  			->get('sh_t_transactions')
  			->row('id');
		$data['totalbayar'] = $this->Item_model->totalbayar($notrans);
		// var_dump($this->Item_model->sub_category_awal());exit();
		$data['sca'] = $this->Item_model->sub_category_awal();
		$data['scm'] = $this->Item_model->sub_category_minuman_awal();
		if ($cek == 'Makanan') {
			$log = 'index.php/ordermakanan/menu/Makanan/'.$sub.'#'.preg_replace('/%20/', '_', $sub);;
		}elseif ($cek == 'Minuman') {
			$log = 'index.php/orderminuman/menu/Minuman/'.$sub.'#'.preg_replace('/%20/', '_', $sub);
		}else{
			$log = 'index.php/selforder/home/'.$nomeja;
		}
		$cabang = $this->db->order_by('id',"desc")
			  	->limit(1)
			  	->get('sh_m_cabang')
			  	->row('id');
		$ip_address = $this->input->ip_address();
		$dataevent = [
			'event_type' => 'Akses cart SO',
			'cabang' => $cabang,
			'id_trans' => $id_trans->id,
			'id_customer' => $this->session->userdata('id'),
			'event_date' => date('Y-m-d H:i:s'),
			'user_by' => $this->session->userdata('username'),
			'description' => 'Membuka halaman cart dengan IP: '.$ip_address,
			'created_date' => date('Y-m-d'),
		];
		$result = $this->db->insert('sh_event_log',$dataevent);

		$data['log'] = $log;
		$data['cek'] = $cek;
		$data['sub'] = $sub;
		$data['url'] = $url;
		$data['no'] = $no;
		$data['cn'] = $this->Admin_model->getColorCN();
		$data['ch'] = $this->Admin_model->getColorHD();
		$data['cb'] = $this->Admin_model->getColorBTN();
		$data['icon'] = $this->Admin_model->getIcon('add');
		$data['logo'] = $this->Admin_model->getLogo();
		$this->load->view('cart',$data);
	}
	public function get_total() {
	    $uoi = $this->session->userdata('user_order_id');
		$id_customer = $this->session->userdata('id');
		$nomeja = $this->session->userdata('nomeja');
		$id_trans = $this->db->get_Where('sh_t_transactions', array('id_customer'=> $id_customer))->row();

	    $total = $this->Item_model->totalSubOrder($nomeja,$id_customer,$uoi,$id_trans->id);
	    $hitungbayar = $this->Item_model->totalbayar($id_trans->id);
	    $sc = $hitungbayar->sc;  // Contoh perhitungan SC 5%
	    $ppn = $hitungbayar->ppn; // Contoh perhitungan PPN 10%
	    $grand_total = $total + $sc + $ppn;

	    // Format hasilnya
	    $data = [
	        'success' => true,
	        'total' => $total,
	        'hitungbayar' => $hitungbayar,
	        'total_formatted' => number_format($total),
	        'sc_formatted' => number_format($sc),
	        'ppn_formatted' => number_format($ppn),
	        'grand_total_formatted' => number_format($grand_total),
	    ];

	    echo json_encode($data);
	}

	public function create($nomeja,$cek,$sub)
	{
		$ic = $this->session->userdata('id');
		$qty = $this->input->post('qty');
		$ata = $this->input->post('cek');
		$qta = $this->input->post('qta');
		$nama = $this->input->post('nama');
		$pesan = $this->input->post('pesan');
		$harga = $this->input->post('harga');
		$item_code = $this->input->post('no');
		$table = $this->session->userdata('nomeja');
		$id_customer = $this->session->userdata('id');
		$id_trans = $this->db->get_Where('sh_t_transactions', array('id_customer'=> $id_customer))->row();
		$id_table = $this->db->get_Where('sh_rel_table', array('id_customer'=> $id_customer))->row();
		$st = $id_table->status;
		if ($st == "Dining" || $st == "Order") {
			$order_stat = 1;
		}elseif ($st == "Billing") {
			$order_stat = 2;
		}
		$today = date('Y-m-d');
		$curTime = explode(':', date('H:i:s'));
		$cekWeekEnd = date('D', strtotime($today));
		$check_promo = $this->Item_model->get_promo($today)->num_rows();
		$get_promo = $this->Item_model->get_promo($today)->row_array();
		$discount = 0;
		// if($check_promo > 0){
		// 	$item_check = $this->Item_model->get_info_item($request['item_code'],$get_promo)->num_rows();
		// 	if($item_check > 0){
		// 		$item_data = $this->Item_model->get_info_item($request['item_code'],$get_promo)->row_array();
		// 		if($get_promo["promo_type"] == 'Discount'){
		// 			if($get_promo["promo_criteria"] == 'Weekday'){ //Weekday
		// 				if($cekWeekEnd !== "Sat" || $cekWeekEnd !== "Sun" || $cekWeekEnd !== "Sab" || $cekWeekEnd !== "Min"){
		// 					if($curTime[0] >= $get_promo["promo_from"] && $curTime[0] <= $get_promo["promo_to"]){
		// 						$discount = $get_promo["promo_value"];		
		// 					}else{
		// 						$discount = 0;
		// 					}
		// 				}else{
		// 					$discount = 0;
		// 				}	
		// 			}else if($get_promo["promo_criteria"] == 'Weekend'){ //Weekend
		// 				if($cekWeekEnd === "Sat" || $cekWeekEnd === "Sun" || $cekWeekEnd === "Sab" || $cekWeekEnd === "Min"){
		// 					if($curTime[0] >= $get_promo["promo_from"] && $curTime[0] <= $get_promo["promo_to"]){
		// 						$discount = $get_promo["promo_value"];		
		// 					}else{
		// 						$discount = 0;
		// 					}
		// 				}else{
		// 					$discount = 0;
		// 				}	
		// 			}else{ //Full Week
		// 				if($curTime[0] >= $get_promo["promo_from"] && $curTime[0] <= $get_promo["promo_to"]){
		// 					$discount = $get_promo["promo_value"];		
		// 				}else{
		// 					$discount = 0;
		// 				}
		// 			}
		// 		}else{
		// 			$discount = 0;	
		// 		}
		// 	}else{
		// 		$discount = 0;
		// 	}
		// }
		$cabang = $this->db->order_by('id',"desc")
  			->limit(1)
  			->get('sh_m_cabang')
  			->row('id');
		$nomer = 1;
		for ($i = 0; $i < count($qty); $i++) {
			if ($qty[$i] != 0) {
				$n = $nomer++ . "<br>"; 
				$data[] = [
				'id_trans' => $id_trans->id,
				'id_customer' => $ic,
				'item_code' => $item_code[$i],
				'qty' => $qty[$i],
				'cabang' => $cabang,
				'unit_price' => $harga[$i],
				'description' => $nama[$i],
				'start_time_order' => date('H:i:s'),
				'entry_by' => $this->session->userdata('username'),
				'disc' => $discount,
				'is_cancel' => 0,
				'session_item' => 0,
				'selected_table_no' => $table,
				'seat_id' => 0,
				'sort_id' => $n,
				'as_take_away' => 0,
				'qty_take_away' => 0,
				'extra_notes' => $pesan[$i],
				'checker_printed' => 1,
				'created_date' => date('Y-m-d'),
				'order_type' => $order_stat,
			];
			 }
    
	}
	// var_dump($data);exit();
	$result = $this->db->insert_batch('sh_t_sub_transactions',$data);
			if ($result) {
				
    			$this->session->set_flashdata('success','Order Menu/Paket Berhasil Di Tambahkan');
				redirect('ordermakanan/subcreate/'.$nomeja.'/'.$cek.'/'.$sub);
				// $where = array('qty' => 0);
				// $this->Item_model->hapus_qty($where,'testing');
			}else{
				echo "gagal order";
			}

		
	}
	public function batal($nomeja,$cek,$sub)
	{
		$ic = $this->session->userdata('id');
		$this->db->where('id_customer',$ic);
    	$this->db->delete('sh_t_sub_transactions');
    	redirect('index.php/cart/home/'.$nomeja.'/'.$cek.'/'.$sub);
	}

	public function validasi_order($table, $cek = NULL, $sub = NULL)
	{
	    // Ambil data dari session & input
	    $table          = $this->session->userdata('nomeja');
	    $qty            = $this->input->post('qty');
	    $qtyaddon       = $this->input->post('qtyaddon');
	    $qtyip          = $this->input->post('qtyip');
	    $options = (array) $this->input->post('options');
	    $item_codes     = (array) $this->input->post('no'); // array item utama
	    $item_codeaddon = $this->input->post('noaddon');
	    $item_codeip    = $this->input->post('noip');
	    $id_customer    = $this->session->userdata('id');
	    
	    // 🔹 1. Validasi awal: hitung jumlah item Special Promo yang dikirim
	    $specialPromoCount = 0;
	    foreach ($item_codes as $code) {
	        $item = $this->Item_model->getItemByCode($code);
	        if ($item && $item->sub_category == 'Special Promo') {
	            $specialPromoCount++;
	        }
	    }

	    // Jika ada 2 atau lebih Special Promo dalam satu order → tolak
	    if ($specialPromoCount >= 2) {
	        $this->session->set_flashdata('error', 'You can only order one Special Promo item');
	        redirect('index.php/cart/home/' . $table);
	        return;
	    }

	    // 🔹 2. Validasi per item
	    foreach ($item_codes as $item_code) {
	        $item = $this->Item_model->getItemByCode($item_code);
	        if (!$item) continue; // skip jika item tidak ditemukan

	        // Jika bukan Special Promo → langsung proses
	        if ($item->sub_category != 'Special Promo') {
	            $this->check_stock($item_code, $qty, $id_customer, $table, $cek);
	            continue;
	        }

	        // --- Jika item adalah Special Promo ---
	        $cekSPchart    = $this->Item_model->cekSPchart($item_code);  // item ini sudah di cart?
	        $cekSPtrans    = $this->Item_model->cekSPtrans($item_code);  // item ini sudah di transaksi?
	        $countSPchart  = $this->Item_model->countSPchart();          // total SP di cart
	        $countSPtrans  = $this->Item_model->countSPtrans();          // total SP di transaksi

	        // Jika item sama sudah ada → boleh lanjut
	        if ($cekSPchart || $cekSPtrans) {
	            $this->check_stock($item_code, $qty, $id_customer, $table, $cek);
	            continue;
	        }

	        // Jika belum ada satupun Special Promo di cart & trans → boleh lanjut
	        if ($countSPchart == 0 && $countSPtrans == 0) {
	            $this->check_stock($item_code, $qty, $id_customer, $table, $cek);
	            continue;
	        }

	        // Jika sudah ada Special Promo lain → tolak
	        $this->session->set_flashdata('error', 'You can only order one Special Promo item.');
	        redirect('index.php/cart/home/' . $table);
	        return;
	    }

	    // 🔹 3. Cek item tambahan (addon & IP)
	    if (!empty($item_codeaddon)) {
	        $this->check_stock($item_codeaddon, $qtyaddon, $id_customer, $table, $cek);
	    }

	    if (!empty($item_codeip)) {
	        $this->check_stock($item_codeip, $qtyip, $id_customer, $table, $cek);
	    }

	    // 🔹 4. Jika semua validasi lolos → lanjut order
	    $this->order($table, $cek, $sub);
	}
	private function check_stock($item_codes, $quantities, $id_customer, $table, $cek)
	{
	    // Paksa jadi array
	    if (!is_array($item_codes)) {
	        $item_codes = [$item_codes];
	        $quantities = [$quantities];
	    }

	    foreach ($item_codes as $index => $item_code) {

	        // Ambil data item
	        $item = $this->Item_model->getDataC($item_code);
	        if (!$item) {
	            $item = $this->Item_model->getDataCP($item_code);
	        }

	        if (!$item) {
	            continue;
	        }

	        // Skip jika item tidak pakai stock
	        if ((int)$item->need_stock !== 1) {
	            continue;
	        }

	        $qty_input = (int)$quantities[$index];
	        $stock     = (int)$item->stock;

	        // Ambil qty di cart (jika ada)
	        $cart = $this->db->where('id_customer', $id_customer)
	                         ->where('item_code', $item_code)
	                         ->get('sh_cart')
	                         ->row();

	        $qty_cart = $cart ? (int)$cart->qty : 0;

	        /*
	         |--------------------------------------------------------------------------
	         | LOGIKA BENAR
	         | - Saat ADD: qty_input adalah tambahan
	         | - Saat UPDATE: qty_input adalah qty akhir
	         |--------------------------------------------------------------------------
	         */
	        if ($cek === 'update') {
	            $total_qty = $qty_input; // qty final
	        } else {
	            $total_qty = $qty_cart + $qty_input; // qty tambahan
	        }

	        // CEK STOCK
	        if ($total_qty > $stock) {
	            $this->session->set_flashdata(
	                'error',
	                $item->description . ' stock not fulfilled. Available: ' . $stock
	            );
	            redirect('index.php/cart/home/' . $table);
	            exit;
	        }
	    }
	}
	public function order($table, $cek = NULL, $sub = NULL)
	{
	    $table = $this->session->userdata('nomeja');
	    $qty = $this->input->post('qty');
	    $item_code = $this->input->post('no');
	    $id_customer = $this->session->userdata('id');

	    // Cek apakah item sudah pernah diorder dalam rentang waktu yang ditentukan
	    $this->check_duplicate_order($table, $item_code, $qty);

	    // Jika tidak duplikat, lanjutkan ke proses order posting
	    $this->order_post($table, $cek, $sub);
	}
	private function check_duplicate_order($table, $item_codes, $qty)
	{
	    $date = date('Y-m-d');
	    $uoi  = $this->session->userdata('user_order_id');

	    // Ambil semua transaksi hari ini untuk item-item tersebut
	    $query = $this->db->select('item_code, timeout_order_so')
	                      ->from('sh_t_transaction_details')
	                      ->where('LEFT(created_date,10)', $date)
	                      ->where('selected_table_no', $table)
	                      ->where('user_order_id', $uoi)
	                      ->where('selforder', 1)
	                      ->where_in('item_code', $item_codes)
	                      ->order_by('id', 'DESC')
	                      ->get();

	    $results = $query->result();

	    // Kalau tidak ada satupun item ditemukan → tidak perlu lanjut
	    if (empty($results)) {
	        return;
	    }

	    // Ambil jam dan menit dari setiap timeout_order_so
	    $timeoutHM = [];
	    foreach ($results as $row) {
	        $timeoutHM[] = date('H:i', strtotime($row->timeout_order_so));
	    }

	    // Ambil nilai unik jam-menit
	    $uniqueTimeoutHM = array_unique($timeoutHM);

	    // Kalau semua item punya jam-menit timeout yang sama
	    if (count($uniqueTimeoutHM) === 1) {
	        $timeout = reset($uniqueTimeoutHM) . ':00'; // tambahkan detik agar bisa dibandingkan

	        // Cek apakah waktu sekarang masih di bawah atau sama dengan timeout
	        if (date('H:i') <= date('H:i', strtotime($timeout))) {
	            // ✅ Duplikat dalam rentang waktu (jam dan menit sama)
	            $this->log_duplicate_order($table, $qty, $item_codes);
	            $this->session->set_flashdata('error', 'Duplicate Order, Please Check Bill Preview');
	            redirect('selforder/home/' . $table);
	        }
	    }

	    // Kalau jam-menit timeout berbeda → tidak dianggap duplikat
	    return;
	}


	private function log_duplicate_order($table, $qty, $item_code)
	{
	    $data = [];
	    $id_customer = $this->session->userdata('id');
	    $id_trans = $this->db->get_where('sh_t_transactions', ['id_customer' => $id_customer])->row()->id;
	    $cabang = $this->db->order_by('id', 'DESC')->limit(1)->get('sh_m_cabang')->row('id');
	    $ip_address = $this->input->ip_address();

	    foreach ($qty as $index => $q) {
	        if ($q != 0) {
	            $data[] = [
	                'event_type' => 'Duplicate Order',
	                'cabang' => $cabang,
	                'id_trans' => $id_trans,
	                'id_customer' => $id_customer,
	                'event_date' => date('Y-m-d H:i:s'),
	                'user_by' => $this->session->userdata('username'),
	                'description' => $item_code[$index] . ' Qty: ' . $q . ' IP: ' . $ip_address,
	                'created_date' => date('Y-m-d'),
	            ];
	        }
	    }

	    $this->db->insert_batch('sh_event_log', $data);
	}
	public function order_post($table,$type, $cek = NULL, $sub = NULL)
	{
	    $table = $this->session->userdata('nomeja');
	    $qty = $this->input->post('qty');
	    $qtyaddon = $this->input->post('qtyaddon');
	    $qtyip = $this->input->post('qtyip');
	    $item_code = $this->input->post('no');
	    $item_codeaddon = $this->input->post('noaddon');
	    $item_codeip = $this->input->post('noip');
	    $nama = $this->input->post('nama');
	    $options = $this->input->post('options');
	    $namaaddon = $this->input->post('namaaddon');
	    $namaip = $this->input->post('namaip');
	    $harga = $this->input->post('harga');
	    $hargaaddon = $this->input->post('hargaaddon');
	    $pesan = $this->input->post('pesan');
	    $hargaip = $this->input->post('hargaip');
	    $id_customer = $this->session->userdata('id');
	    $uoi = $this->session->userdata('user_order_id');
	    $id_trans = $this->db->get_where('sh_t_transactions', ['id_customer' => $id_customer])->row()->id;
	    // var_dump($pesan);exit();
	    // Insert data transaksi untuk item dan addon
	    if ($type == "PN") {
    		$this->bayar($table,$cek,$sub);
    	}
	    if (!empty($item_code)) {
	        $id_details = $this->insert_transaction_details($id_trans, $item_code, $qty, $nama, $harga, $type);
	    }
	    if (!empty($item_codeaddon)) {
	        $this->insert_transaction_details($id_trans, $item_codeaddon, $qtyaddon, $namaaddon, $hargaaddon, $type);
	    }
	    if (!empty($item_codeip)) {
	        $this->insert_transaction_details_list($id_details,$id_trans, $item_codeip, $qtyip, $namaip, $hargaip, $type);
	    }

	    // Update stok item dan addon
	    if (!empty($item_code)) {
	        $this->update_stock($item_code, $qty, $this->input->post('need_stock'));
	    }
	    if (!empty($item_codeaddon)) {
	        $this->update_stock($item_codeaddon, $qtyaddon, $this->input->post('need_stockaddon'));
	    }
	    if (!empty($item_codeip)) {
	        $this->update_stock($item_codeip, $qtyip, $this->input->post('need_stockip'));
	    }

	    // Hapus data di sh_cart berdasarkan id_customer dan user_order_id
	    $this->db->where('id_customer', $id_customer)->where('user_order_id', $uoi)->delete('sh_cart');
	    $this->db->where('id_customer', $id_customer)->where('user_order_id', $uoi)->delete('sh_cart_details');

	    
	    // Log the event
	    $cabang = $this->db->order_by('id', "desc")->limit(1)->get('sh_m_cabang')->row('id');
	    $dataevent = [];
	    
	    // Handle event logging for items (both main items and addons)
	    if (!empty($item_code)) {
	        for ($i = 0; $i < count($item_code); $i++) {
	            $dataevent[] = [
	                'event_type' => 'Order SO',
	                'cabang' => $cabang,
	                'id_trans' => $id_trans,
	                'id_customer' => $id_customer,
	                'event_date' => date('Y-m-d H:i:s'),
	                'user_by' => $this->session->userdata('username'),
	                'description' => 'Melakukan Order item: ' . $this->input->post('nama')[$i] . ' qty: ' . $qty[$i],
	                'created_date' => date('Y-m-d'),
	            ];
	        }
	    }

	    if (!empty($item_codeaddon)) {
	        for ($i = 0; $i < count($item_codeaddon); $i++) {
	            $dataevent[] = [
	                'event_type' => 'Order SO - Addon',
	                'cabang' => $cabang,
	                'id_trans' => $id_trans,
	                'id_customer' => $id_customer,
	                'event_date' => date('Y-m-d H:i:s'),
	                'user_by' => $this->session->userdata('username'),
	                'description' => 'Melakukan Order Addon: ' . $this->input->post('namaaddon')[$i] . ' qty: ' . $qtyaddon[$i],
	                'created_date' => date('Y-m-d'),
	            ];
	        }
	    }

	    // Insert event log data into the database
	    if (!empty($dataevent)) {
	        $result = $this->db->insert_batch('sh_event_log', $dataevent);
	    }
	    
	    // Redirect after successful order
	    if ($result) {
	    	$updatetable = ['status' => 'Dining'];
	    	$this->db->where('id_table',$table);
	    	$this->db->where('id_customer',$id_customer);
	    	$this->db->where('is_close',0);
	    	$this->db->update('sh_rel_table', $updatetable);
	        $this->session->set_flashdata('successcart', 'Menu Sent to Kitchen');
	        redirect('index.php/selforder/home/' . $table);
	    }
	}
	public function testbayar()
    {
    	Xendit::setApiKey('xnd_development_MHFonfxW3xEdU1wQTfaMT8epmrJgdZqq0OSO47d91B1CO8LflPMc1cmF6KhphW');
	    // xnd_production_bC7oR4oXI2ERB5twAquSaHHg8BYdsZ76vOO3smUCWwUwet7CrPKRGWWGkpRODY7V
	    // xnd_development_MHFonfxW3xEdU1wQTfaMT8epmrJgdZqq0OSO47d91B1CO8LflPMc1cmF6KhphW

	    $params = [
	        "external_id"          => $extId,
	        "description"          => $description,
	        "amount"               => $amount,
	        "success_redirect_url" => $successUrl,
	        // redirect url if the payment is failed
	        "failure_redirect_url" => base_url() . "index.php/Cart/gagal/" . $notrans,
	    ];

	    // $invoice = \Xendit\Invoice::create($params);
	    // echo json_encode(['data' => $invoice['invoice_url']]);

	    try {
	        $invoice = \Xendit\Invoice::create($params);

	        // Redirect ke invoice Xendit
	        redirect($invoice['invoice_url']);

	    } catch (\Exception $e) {
	        // Tangani error
	        echo 'Error: ' . $e->getMessage();
	    }
    }
	public function bayar()
	{
	    $amount      = $this->input->post('totalbayar');
	    $id_customer = $this->session->userdata('id');
	    $nomeja      = $this->session->userdata('nomeja');
	    // Ambil cabang terakhir
	    $cabang = $this->db
	        ->order_by('id', "desc")
	        ->limit(1)
	        ->get('sh_m_cabang')
	        ->row('id');

	    // Ambil transaksi terakhir customer
	    $transRow = $this->db
	        ->order_by('id', "desc")
	        ->where('id_customer', $id_customer)
	        ->limit(1)
	        ->get('sh_t_transactions')
	        ->row();

	    if (!$transRow) {
	        echo "Transaksi tidak ditemukan";
	        return;
	    }

	    $notrans = $transRow->id;

	    // Generate external ID
	    $tgl  = date('ymd');
	    $kode = "SH".$cabang.$notrans.$tgl;
	    $extId = $kode;

	    $description = "Pembayaran SO";
	    $table = $this->session->userdata('nomeja');

	    // Ambil data POST
	    $qty         = $this->input->post('qty');
	    $sc_percent  = $this->input->post('sc_percent');
	    $sc_amount   = $this->input->post('sc_amount');
	    $tax_percent = $this->input->post('tax_percent');
	    $tax_amount  = $this->input->post('tax_amount');
	    $ata         = $this->input->post('cek');
	    $qta         = $this->input->post('qta');
	    $nama        = $this->input->post('nama');
	    $pesan       = $this->input->post('pesan');
	    $pesandua    = $this->input->post('pesandua');
	    $pesantiga   = $this->input->post('pesantiga');
	    $harga       = $this->input->post('harga');
	    $item_code   = $this->input->post('no');
	    $need_stock  = $this->input->post('need_stock');
	    $is_paket    = $this->input->post('is_paket');
	    // var_dump($pesan);exit();
	    // Mengambil relasi customer-table
	    $id_table = $this->db->get_where('sh_rel_table', array('id_customer' => $id_customer))->row();
	    $st = $id_table ? $id_table->status : 0;

	    // Data array untuk success redirect
	    $dataArray = array(
	        'table'       => $table,
	        'qty'         => $qty,
	        'ata'         => $ata,
	        'qta'         => $qta,
	        'nama'        => $nama,
	        'pesan'       => $pesan,
	        'pesandua'    => $pesandua,
	        'pesantiga'   => $pesantiga,
	        'harga'       => $harga,
	        'item_code'   => $item_code,
	        'need_stock'  => $need_stock,
	        'is_paket'    => $is_paket,
	        'id_customer' => $id_customer,
	        'amount'      => $amount,
	        'sc_percent'  => $sc_percent,
	        'sc_amount'   => $sc_amount,
	        'tax_percent' => $tax_percent,
	        'tax_amount'  => $tax_amount,
	    );

	    // URL redirect jika sukses
	    $successUrl = base_url() . "index.php/Cart/sukses/" . $nomeja . '?' . http_build_query($dataArray);

	    // $successUrl = base_url()."index.php/Cart/logpayment/";
	    $sukses = base_url() . "index.php/Cart/webhook";

	    // API key Xendit
	    Xendit::setApiKey('xnd_development_MHFonfxW3xEdU1wQTfaMT8epmrJgdZqq0OSO47d91B1CO8LflPMc1cmF6KhphW');
	    // xnd_production_bC7oR4oXI2ERB5twAquSaHHg8BYdsZ76vOO3smUCWwUwet7CrPKRGWWGkpRODY7V
	    // xnd_development_MHFonfxW3xEdU1wQTfaMT8epmrJgdZqq0OSO47d91B1CO8LflPMc1cmF6KhphW

	    $params = [
	        "external_id"          => $extId,
	        "description"          => $description,
	        "amount"               => $amount,
	        "success_redirect_url" => $successUrl,
	        // redirect url if the payment is failed
	        "failure_redirect_url" => base_url() . "index.php/Cart/gagal/" . $notrans,
	    ];

	    // $invoice = \Xendit\Invoice::create($params);
	    // echo json_encode(['data' => $invoice['invoice_url']]);

	    try {
	        $invoice = \Xendit\Invoice::create($params);

	        // Redirect ke invoice Xendit
	        redirect($invoice['invoice_url']);

	    } catch (\Exception $e) {
	        // Tangani error
	        echo 'Error: ' . $e->getMessage();
	    }
	}

	public function bayar_va()
	{
	    Xendit::setApiKey('xnd_development_MHFonfxW3xEdU1wQTfaMT8epmrJgdZqq0OSO47d91B1CO8LflPMc1cmF6KhphW');
	    // xnd_production_bC7oR4oXI2ERB5twAquSaHHg8BYdsZ76vOO3smUCWwUwet7CrPKRGWWGkpRODY7V
	    $amount      = 10000;
	    $id_customer = 2;
	    $nomeja      = 2;

	    $externalId = 'VA-' . time();

	    $params = [
	        'external_id' => $externalId,
	        'bank_code'   => 'BCA', // BCA, BNI, BRI, MANDIRI, dll
	        'name'        => 'MEJA ' . $nomeja,
	        'expected_amount' => $amount,
	        'is_closed'   => true,
	        'expiration_date' => date('c', strtotime('+1 day')),
	    ];

	    try {
	        $va = \Xendit\VirtualAccounts::create($params);

	        $data = [
	        	'external_id' => $externalId,
	            'va_number'   => $va['account_number'],
	            'bank'        => $va['bank_code'],
	            'amount'      => $amount,
	            'status'      => 'PENDING',
	            'id_customer' => $id_customer,
	        ];
	        var_dump($data);exit();
	        // SIMPAN KE DATABASE 
	        $date = new DateTime($va['expiration_date']);
			$expired_at = $date->format('Y-m-d H:i:s');
	        $this->db->insert('sh_payment_va', [
	            'external_id' => $externalId,
	            'va_number'   => $va['account_number'],
	            'bank'        => $va['bank_code'],
	            'amount'      => $amount,
	            'status'      => 'PENDING',
	            'id_customer' => $id_customer,
	            'expired_at'  => $expired_at,
	        ]);

	        // TAMPILKAN KE USER (TANPA REDIRECT)
	        echo json_encode([
	            'status'     => true,
	            'bank'       => $va['bank_code'],
	            'va_number'  => $va['account_number'],
	            'amount'     => $amount,
	            'expired_at' => $va['expiration_date'],
	        ]);

	    } catch (\Exception $e) {
	        echo json_encode([
	            'status' => false,
	            'error'  => $e->getMessage()
	        ]);
	    }
	}
	public function xendit_va_webhook()
	{
	    $payload = json_decode(file_get_contents('php://input'), true);
	    var_dump($payload);exit();
	    if ($payload['status'] === 'COMPLETED') {

	        $this->db->where('external_id', $payload['external_id'])
	                 ->update('sh_payment_va', [
	                     'status' => 'PAID',
	                     'paid_at'=> date('Y-m-d H:i:s')
	                 ]);

	        // PROSES ORDER
	        // - pindahkan cart ke transaksi
	        // - update stok
	        // - update status meja
	    }

	    http_response_code(200);
	}

	
	public function sukses($nomeja,$cek=NULL,$sub=NULL,$no=NULL)
    {

    	$table = $this->session->userdata('nomeja');
    	$external_id = $this->input->get('external_id');
		$descriptionpay = $this->input->get('descriptionpay');
		$amount = $this->input->get('amount');
		$id_trans = $this->input->get('id_trans');
		$json_data = json_encode(array('external_id' => $external_id, 'description' => $descriptionpay,'amount' => $amount,'Status' => 'Sukses'));
		
		$sc_percent = $this->input->get('sc_percent');
		$sc_amount = $this->input->get('sc_amount');
		$tax_percent = $this->input->get('tax_percent');
		$tax_amount = $this->input->get('tax_amount');
		$qty = $this->input->get('qty');
		$ata = $this->input->get('cek');
		$qta = $this->input->get('qta');
		$nama = $this->input->get('nama');
		$pesan = $this->input->get('pesan');
		$pesandua = $this->input->get('pesandua');
		$pesantiga = $this->input->get('pesantiga');
		$harga = $this->input->get('harga');
		$item_code = $this->input->get('item_code');
		$need_stock = $this->input->get('need_stock');
		$is_paket = $this->input->get('is_paket');
		$id_customer = $this->session->userdata('id');
		 // var_dump($pesan);exit();
		
 
        $this->logpayment();
    }
    public function logpayment()
	{
	    // AMBIL DATA DARI GET
	    $id_customer = $this->input->get('id_customer');
	    $item_code   = $this->input->get('item_code');
	    $qty         = $this->input->get('qty');
	    $nama        = $this->input->get('nama');
	    $pesan       = $this->input->get('pesan');
	    $harga       = $this->input->get('harga');
	    $need_stock  = $this->input->get('need_stock');
	    $amount      = $this->input->get('amount');
	    $table       = $this->input->get('table');
	    // var_dump($pesan);exit();
	    // VALIDASI
	    if (!$id_customer || !$item_code) {
	        echo json_encode([
	            'status' => false,
	            'msg'    => 'Missing required parameters'
	        ]);
	        return;
	    }

	    // AMBIL TRANSAKSI CUSTOMER TERAKHIR
	    $trans = $this->db
	        ->order_by('id', 'DESC')
	        ->get_where('sh_t_transactions', ['id_customer' => $id_customer], 1)
	        ->row();

	    if (!$trans) {
	        echo json_encode(['status' => false, 'msg' => 'Transaction not found']);
	        return;
	    }

	    // LOG PEMBAYARAN (RAW GET)
	    $this->db->insert('sh_sopayment_log', [
	        'id_trans'     => $trans->id,
	        'id_invoice'   => $trans->id,
	        'description'  => json_encode($_GET),
	        'created_date' => date('Y-m-d H:i:s')
	    ]);

	    // UPDATE TRANSAKSI → LUNAS
	    $this->db->where('id', $trans->id)->update('sh_t_transactions', [
	        'payment_amount' => $amount,
	        'total_amount'   => $amount,
	        'kembalian'      => 0,
	        'payment_date'   => date('Y-m-d H:i:s'),
	        'payment_type'   => 'Xendit Credit Card',
	    ]);

	    // INSERT DATA DETAIL TRANSAKSI
	    $detailInsert = [];
	    for ($i = 0; $i < count($qty); $i++) {
	        $detailInsert[] = [
	            'id_trans'          => $trans->id,
	            'item_code'         => $item_code[$i],
	            'qty'               => $qty[$i],
	            'unit_price'        => $harga[$i],
	            'description'       => $nama[$i],
	            'extra_notes'       => $pesan[$i],
	            'is_paid'           => 1,
	            'selected_table_no' => $table,
	            'created_date'      => date('Y-m-d H:i:s'),
	            'selforder'         => 1,
	        ];
	    }
	    $this->db->insert_batch('sh_t_transaction_details', $detailInsert);

	    // UPDATE STOCK ITEM
	    for ($i = 0; $i < count($qty); $i++) {
	        if ($need_stock[$i] == 1) {
	            $item = $this->db->get_where('sh_m_item', ['no' => $item_code[$i]])->row();
	            if ($item) {
	                $newStock = $item->stock - $qty[$i];
	                $this->db->where('no', $item_code[$i])->update('sh_m_item', [
	                    'stock'       => $newStock,
	                    'is_sold_out' => $newStock <= 0 ? 1 : 0
	                ]);
	            }
	        }
	    }

	    // UPDATE STATUS MEJA → DINING
	    $customerId = $this->session->userdata('id');
	    $this->db->where('id_customer', $customerId)->update('sh_rel_table', [
	        'status' => 'Dining'
	    ]);

	    // HAPUS ITEM CART
	    $userOrderId = $this->session->userdata('user_order_id');

	    $this->db
	        ->where('id_customer', $customerId)
	        ->where('user_order_id', $userOrderId)
	        ->where_in('item_code', $item_code)
	        ->delete('sh_cart');

	    // PESAN BERHASIL
	    $this->session->set_flashdata('success', 'Pesanan berhasil dibuat');

	    // REDIRECT
	    redirect('index.php/selforder/home/' . $table);
	}
	private function insert_transaction_details($id_trans, $item_codes, $quantities, $nama, $harga, $type)
{
    $table = $this->session->userdata('nomeja');
    $pesan = $this->input->post('pesan');
    $is_addon = $this->input->post('is_addon');
    $options = $this->input->post('options');
    $need_stock = $this->input->post('need_stock');
    $need_stockaddon = $this->input->post('need_stockaddon');
    $id_customer = $this->session->userdata('id');
    $qty = $this->input->post('qty');
    $uoi = $this->session->userdata('user_order_id');

    // Cek transaksi pelanggan berdasarkan ID
    $transaction = $this->db->get_where('sh_t_transactions', ['id_customer' => $id_customer])->row();
    $id_trans = $id_trans;

    // Cek status meja pelanggan
    $id_table = $this->db->get_where('sh_rel_table', ['id_customer' => $id_customer])->row();
    $st = $id_table->status;

    // Ambil waktu timeout dari pengaturan
    $timeout_data = $this->db->order_by('id', 'DESC')->limit(1)->get('sh_set_time_cekdata')->row('seconds');
    $seconds = "+" . ($timeout_data) . " seconds";

    // Cek jika ada duplikasi pesanan hari ini
    $date = date('Y-m-d');
    $ctime = date('H:i:s');
    $this->db->from('sh_t_transaction_details');
    $where = "LEFT(created_date, 10) = '$date' AND selected_table_no = '$table' AND selforder = 1 AND user_order_id = '$uoi'";
    $this->db->where($where);
    $this->db->where_in('item_code', $item_codes);
    $this->db->order_by('id', 'DESC');
    $this->db->limit(1);
    $q = $this->db->get()->row();
    $time = date('H:i:s', strtotime($ctime . $seconds));

    // Tetapkan status pesanan berdasarkan status meja
    $order_stat = ($st === "Dining" || $st === "Order") ? 1 : (($st === "Billing") ? 2 : 0);

    // Cek promo untuk diskon
    $today = date('Y-m-d');
    $curTime = explode(':', date('H:i:s'));
    $cekWeekEnd = date('D', strtotime($today));
    $discounts = [];

    foreach ($item_codes as $index => $item_code) {
        $check_promo = $this->Item_model->get_promo($item_code, $today)->row_array();
        $discounts[$index] = 0;

        if (!empty($check_promo)) {
            if ($check_promo['promo_type'] === 'Discount') {
                $is_weekend = $cekWeekEnd === "Sat" || $cekWeekEnd === "Sun";
                $within_time = $curTime[0] >= $check_promo['promo_from'] && $curTime[0] <= $check_promo['promo_to'];

                if (($check_promo['promo_criteria'] === 'Weekday' && !$is_weekend && $within_time) ||
                    ($check_promo['promo_criteria'] === 'Weekend' && $is_weekend && $within_time) ||
                    ($check_promo['promo_criteria'] === 'Everyday' && $within_time)) {
                    $discounts[$index] = $check_promo['promo_value'];
                }
            }
        }
    }
    // Ambil data cabang
    $cabang = $this->db->order_by('id', 'DESC')->limit(1)->get('sh_m_cabang')->row('id');

    // Cek cekdata transaksi
    $cekdata = $this->Item_model->cekdatatransdetail($id_trans)->row();
    $cd = ($is_addon == 1) ? $cekdata->cekdata : (($cekdata) ? $cekdata->cekdata + 1 : 1);

    // Siapkan data untuk dimasukkan
    $data = [];
	foreach ($item_codes as $index => $item_code) {
	    $cekpaket = $this->db->where('no', $item_code)
	                         ->order_by('id', "desc")
	                         ->limit(1)
	                         ->get('sh_m_item')
	                         ->row('is_paket_so');

	    if ($quantities[$index] > 0) {
	        $item_name = $nama[$index];
	        $item_price = $harga[$index];
	        $item_need_stock = $need_stock[$index];
	        if ($type == 'PN') {
	        	$paid = 1;
	        }else{
	        	$paid = 0;
	        }
	        // Data awal
	        $itemData = [
	            'id_trans' => $id_trans,
	            'item_code' => $item_code,
	            'qty' => $quantities[$index],
	            'cabang' => $cabang,
	            'unit_price' => $item_price,
	            'description' => $item_name,
	            'start_time_order' => $ctime,
	            'entry_by' => $this->session->userdata('username'),
	            'disc' => $discounts[$index],
	            'is_cancel' => 0,
	            'session_item' => 0,
	            'selected_table_no' => $table,
	            'seat_id' => 0,
	            'sort_id' => $index + 1,
	            'as_take_away' => 0,
	            'qty_take_away' => 0,
	            'extra_notes' => $options[$index],
	            'checker_printed' => 1,
	            'created_date' => date('Y-m-d H:i:s'),
	            'submit_time' => date('Y-m-d H:i:s'),
	            'order_type' => $order_stat,
	            'selforder' => 1,
	            'is_printed_so' => 0,
	            'cekdata' => $cd,
	            'user_order_id' => $uoi,
	            'timeout_order_so' => $time,
	            'is_paid'		=> $paid,
	        ];

	        // Jika item adalah paket, tambahkan atribut is_package
	        if ($cekpaket == 1) {
	            $itemData['is_package'] = 1;
	            $itemData['parent_id_package'] = 0;
	        }else{
	        	$itemData['is_package'] = 0;
	            $itemData['parent_id_package'] = 0;
	        }

	        // Tambahkan itemData ke dalam array $data
	        $data[] = $itemData;
	    }
	}
    // Masukkan data ke database jika ada
    if (!empty($data)) {
        $this->db->insert_batch('sh_t_transaction_details', $data);
        return $this->db->insert_id();
        $this->db->where('id', $id_trans)->update('sh_t_transactions', [
            'date_order_menu' => date('Y-m-d H:i:s'),
            'is_order_menu_active' => 1,
            'start_time_order' => $ctime,
        ]);
    }
}
	private function insert_transaction_details_list($id_details, $id_trans, $item_codes, $quantities, $nama, $harga)
{
    $pesan = $this->input->post('pesan');
    $options = $this->input->post('options');
    $need_stockip = $this->input->post('need_stockip');
    $id_customer = $this->session->userdata('id');
    $qty = $this->input->post('qty');

    $id_table = $this->session->userdata('nomeja');

    $idtable = $this->db->get_where('sh_rel_table', ['id_customer' => $id_customer])->row();
    $st = $idtable ? $idtable->status : null;

    // Ambil waktu timeout dari pengaturan
    $timeout_data = $this->db->order_by('id', 'DESC')->limit(1)->get('sh_set_time_cekdata')->row('seconds');
    $seconds = "+" . ($timeout_data) . " seconds";
    $cabang = $this->db->order_by('id', "desc")->limit(1)->get('sh_m_cabang')->row('id');

    // Tetapkan status pesanan berdasarkan status meja
    $order_stat = ($st === "Dining" || $st === "Order") ? 1 : (($st === "Billing") ? 2 : 0);

    // Cek promo untuk diskon
    $today = date('Y-m-d');
    $curTime = explode(':', date('H:i:s'));
    $cekWeekEnd = date('D', strtotime($today));
    $discounts = [];

    foreach ($item_codes as $index => $item_code) {
        $check_promo = $this->Item_model->get_promo($item_code, $today)->row_array();
        $discounts[$index] = 0;

        if (!empty($check_promo)) {
            if ($check_promo['promo_type'] === 'Discount') {
                $is_weekend = $cekWeekEnd === "Sat" || $cekWeekEnd === "Sun";
                $within_time = $curTime[0] >= $check_promo['promo_from'] && $curTime[0] <= $check_promo['promo_to'];

                if (
                    ($check_promo['promo_criteria'] === 'Weekday' && !$is_weekend && $within_time) ||
                    ($check_promo['promo_criteria'] === 'Weekend' && $is_weekend && $within_time) ||
                    ($check_promo['promo_criteria'] === 'Everyday' && $within_time)
                ) {
                    $discounts[$index] = $check_promo['promo_value'];
                }
            }
        }
    }

    // Siapkan data untuk dimasukkan
    $data = [];

    // Pastikan semua variabel berbentuk array agar tidak error
    if (!is_array($item_codes)) $item_codes = [$item_codes];
    if (!is_array($quantities)) $quantities = [$quantities];
    if (!is_array($nama)) $nama = [$nama];
    if (!is_array($harga)) $harga = [$harga];

    $ctime = date('H:i:s');

    foreach ($item_codes as $index => $item_code) {
        $qty = isset($quantities[$index]) ? $quantities[$index] : 0;
        $item_name = isset($nama[$index]) ? $nama[$index] : '';
        $item_price = isset($harga[$index]) ? $harga[$index] : 0;

        if ($qty > 0) {
            $en = '';
            $up = $item_price ?: 0;

            $data[] = [
                'id_trans' => $id_trans,
                'parent_id_package' => $id_details,
                'item_code' => $item_code,
                'qty' => $qty,

                'cabang' => $cabang,
                'start_time_order' => $ctime,
                'is_cancel' => 0,
                'session_item' => 0,
                'seat_id' => 0,
                'sort_id' => $index + 1,
                'as_take_away' => 0,
                'qty_take_away' => 0,
                'submit_time' => date('Y-m-d H:i:s'),
                'order_type' => $order_stat,
                'checker_printed' => 1,

                'unit_price' => $up,
                'description' => $item_name,
                'entry_by' => $this->session->userdata('username'),
                'selected_table_no' => $id_table,
                'disc' => isset($discounts[$index]) ? $discounts[$index] : 0,
                'extra_notes' => $en,
                'created_date' => date('Y-m-d H:i:s'),
            ];
        }
    }

    // Masukkan data ke database jika ada
    if (!empty($data)) {
        $this->db->insert_batch('sh_t_transaction_details', $data);
    } else {
        log_message('error', 'insert_transaction_details_list(): Tidak ada data yang dimasukkan. Cek item_codes/harga/qty.');
    }
}

	private function update_stock($item_codes, $quantities, $need_stock)
	{
	    $cabang = $this->db->order_by('id', "desc")->limit(1)->get('sh_m_cabang')->row('id');
	    foreach ($item_codes as $index => $item_code) {
	        $item = $this->db->where('no', $item_code)->get('sh_m_item')->row();
	            $new_stock = $item->stock - $quantities[$index];
	            $update_data = ['stock' => $new_stock];
	            if ($new_stock == 0) {
	                $update_data['is_sold_out'] = 1;
	            }
	            $this->db->where('no', $item_code)->update('sh_m_item', $update_data);
	            if ($need_stock[$index] != 0) {
	                $description = 'Stock Used ' . $quantities[$index];
	                if ($new_stock == 0) {
	                    $description .= ' and set status from Available to Sold Out';
	                }

	                $datastok[] = [
	                    'log_type' => 'Update Stock',
	                    'cabang' => $cabang,
	                    'item_code' => $item_code,
	                    'stock_before' => $item->stock,
	                    'stock_after' => $new_stock,
	                    'difference' => $quantities[$index],
	                    'stock_entry' => date('Y-m-d H:i:s'),
	                    'username' => $this->session->userdata('username'),
	                    'description' => $description,
	                ];
	            } else {
	                $datastok[] = [
	                    'log_type' => 'Update Stock',
	                    'cabang' => $cabang,
	                    'item_code' => $item_code,
	                    'stock_before' => $item->stock,
	                    'stock_after' => $item->stock,
	                    'difference' => 0,
	                    'stock_entry' => date('Y-m-d H:i:s'),
	                    'username' => $this->session->userdata('username'),
	                    'description' => 'Stock Used 0',
	                ];
	            }
	    }
	    if (!empty($datastok)) {
	        $this->db->insert_batch('sh_stok_logs', $datastok);
	    }
	}

	public function delete($id,$nomeja,$cekpaket=null,$cek=null,$sub=null)
	{
		$ic = $this->session->userdata('id');$it = $this->session->userdata('id_table');
		$uoi = $this->session->userdata('user_order_id');
		$date = date('Y-m-d');
		if ($cekpaket == 'paket') {
			$where ="id_customer ='".$ic."' and id_table ='".$nomeja."' and user_order_id ='".$uoi."' and left(entry_date,10) ='".$date."'";
			$this->db->where($where);
			$this->db->delete('sh_cart_details');
			$this->db->where('id',$id);
			$this->db->delete('sh_cart');
		}else{
			$query = $this->db->get_where('sh_cart', array('id' => $id))->row();
			if ($query) {
				$where ="id_customer ='".$ic."' and id_table ='".$nomeja."' and user_order_id ='".$uoi."' and left(entry_date,10) ='".$date."' and item_code_header = '".$query->item_code."'";
				$this->db->where($where);
				$this->db->delete('sh_cart');
			}
			$this->db->where('id',$id);
			$this->db->delete('sh_cart');
		}
    	$this->session->set_flashdata('success','Menu Has Been Removed');

    	if ($cek == 'Makanan') {
			$log = 'index.php/Cart/home/'.$nomeja.'/Makanan/'.$sub.'#'.preg_replace('/%20/', '_', $sub);
		}elseif ($cek == 'Minuman') {
			$log = 'index.php/Cart/home/'.$nomeja.'/Minuman/'.$sub.'#'.preg_replace('/%20/', '_', $sub);
		}elseif ($cek == 'CAKE%20DAN%20BAKERY') {
			$log = 'index.php/Cart/home/'.$nomeja.'/CAKE%20DAN%20BAKERY/'.$sub.'#'.preg_replace('/%20/', '_', $sub);
			// $log = 'index.php/Cart/home/CAKE%20DAN%20BAKERY/'.$sub.'#'.preg_replace('/%20/', '_', $sub);
		}else{
			$log = 'index.php/Cart/home/'.$nomeja;
		}
		redirect(base_url().$log);
	}
	
	public function cancel_order($nomeja, $cek, $sub = NULL, $add = NULL)
	{
	    $ic = $this->session->userdata('id');
	    $uoi = $this->session->userdata('user_order_id');
	    $date = date('Y-m-d'); // Memastikan tanggal digunakan dengan benar

	    // **Ambil Data Transaksi Pelanggan**
	    $id_trans = $this->db->get_where('sh_t_transactions', ['id_customer' => $ic])->row();

	    if (!$id_trans) {
	        $this->session->set_flashdata('error', 'Transaksi tidak ditemukan.');
	        redirect($_SERVER['HTTP_REFERER']); // Kembali ke halaman sebelumnya jika transaksi tidak ditemukan
	        return;
	    }

	    // **Hapus Data di `sh_cart`**
	    $this->db->where([
	        'id_customer' => $ic,
	        'id_table' => $nomeja,
	        'id_trans' => $id_trans->id,
	        'user_order_id' => $uoi
	    ]);

	    if ($add) {
	        $this->db->where('addons', 1);
	    }

	    $this->db->delete('sh_cart');

	    // **Hapus Data di `sh_cart_details`**
	    $this->db->where([
	        'id_customer' => $ic,
	        'id_table' => $nomeja,
	        'user_order_id' => $uoi,
	        'DATE(entry_date)' => $date // Perbaikan pengecekan tanggal
	    ]);
	    $this->db->delete('sh_cart_details');

	    // **Tentukan Redirect URL**
	    switch ($cek) {
	        case 'Makanan':
	            $log = "index.php/Cart/home/$nomeja/Makanan/$sub#" . str_replace(' ', '_', $sub);
	            break;
	        case 'Minuman':
	            $log = "index.php/Cart/home/$nomeja/Minuman/$sub#" . str_replace(' ', '_', $sub);
	            break;
	        case 'CAKE DAN BAKERY': // Perbaikan kategori tanpa %20
	            $log = "index.php/Cart/home/$nomeja/CAKE%20DAN%20BAKERY/$sub#" . str_replace(' ', '_', $sub);
	            break;
	        default:
	            $log = "index.php/Cart/home/$nomeja";
	    }

	    // **Set Pesan Keberhasilan & Redirect**
	    $this->session->set_flashdata('success', 'Successfully Canceled the Order');
	    redirect(base_url($log));
	}

	public function ubah($id,$nomeja,$cek,$sub)
	{
		// echo $id;exit();
		$qty = $this->input->post('qty');
		$extra_notes = $this->input->post('extra_notes');
		$data = [
			'qty' => $qty,
			'extra_notes' => $extra_notes,
		];
		$this->db->where('id',$id);
		if ($qty != 0) {
			$this->db->update('sh_cart',$data);
			$this->session->set_flashdata('success','Menu Has Been Updated');
		}else{
			$this->db->delete('sh_cart');
			$this->session->set_flashdata('error','Menu Has Been Removed');
		}
    	
    	if ($cek == 'Makanan') {
			$log = 'index.php/Cart/home/'.$nomeja.'/Makanan/'.$sub.'#'.preg_replace('/%20/', '_', $sub);
		}elseif ($cek == 'Minuman') {
			$log = 'index.php/Cart/home/'.$nomeja.'/Minuman/'.$sub.'#'.preg_replace('/%20/', '_', $sub);
		}elseif ($cek == 'CAKE%20DAN%20BAKERY') {
			$log = 'index.php/Cart/home/'.$nomeja.'/CAKE%20DAN%20BAKERY/'.$sub.'#'.preg_replace('/%20/', '_', $sub);
		}else{
			$log = 'index.php/Cart/home/'.$nomeja;
		}
		redirect(base_url().$log);
		
	}
	public function update_qty() {
	    // Mendapatkan data dari request (JSON input)
	    $data = json_decode(file_get_contents('php://input'), true);

	    // Mendapatkan id item dan qty
	    $id = $data['id'];
	    $qty = $data['qty'];
	    $nomeja = $this->session->userdata('nomeja');
	    $uoi = $this->session->userdata('user_order_id');

	    // Memperbarui qty item di keranjang
	    $this->db->where('id', $id);
	    $this->db->where('id_table', $nomeja);
	    $this->db->where('user_order_id', $uoi);
	    $this->db->update('sh_cart', array('qty' => $qty));

	    // Cek apakah ada baris yang diupdate
	    if ($this->db->affected_rows() > 0) {
	        echo json_encode(array('success' => true));
	    } else {
	        echo json_encode(array('success' => false, 'message' => 'Item not found or no change in qty'));
	    }
	}
	public function changeitem($ic,$no,$qty,$id_cart)
	{
		$id_customer = $this->session->userdata('id');
		$uoi = $this->session->userdata('user_order_id');
		$table = $this->session->userdata('nomeja');
		$id_trans = $this->db->order_by('id',"desc")->where('id_customer',$id_customer)
                    ->limit(1)
                    ->get('sh_t_transactions')
                    ->row('id');
                $this->db->select('i.*');
				$this->db->from('sh_cart_details i');
				$this->db->where('i.item_code',$ic);
				$this->db->where('i.id_cart',$id_cart);
				$this->db->where('i.user_order_id',$uoi);
		$itemdetails = $this->db->get()->row();
		        $this->db->select('i.*');
				$this->db->from('sh_m_item i');
				$this->db->where('i.no',$no);
		$item = $this->db->get()->row();

				$this->db->select('i.*');
				$this->db->from('sh_cart_details i');
				$this->db->where('i.item_code', $no);
				$this->db->where('i.id_cart', $id_cart);
				$ci = $this->db->get()->row();

				if ($ci) {
				    // Jika item sudah ada, update qty
				    $new_qty = $ci->qty + $qty; // Tambahkan qty baru ke qty lama

				    $this->db->where('id_cart', $id_cart);
				    $this->db->where('item_code', $no);
				    $update = $this->db->update('sh_cart_details', ['qty' => $new_qty]);

				    if ($update) {
				        $this->session->set_flashdata('success', 'Item quantity has been updated successfully.');
				    } else {
				        $this->session->set_flashdata('error', 'Failed to update item quantity.');
				    }
				} else {
				    // Jika item tidak ada, insert data baru
				    $data = [
				        'id_cart' => $id_cart,
				        'id_customer' => $id_customer,
				        'id_table' => $table,
				        'id_trans' => $id_trans,
				        'user_order_id' => $uoi,
				        'paket_code' => $itemdetails->paket_code,
				        'sub_category' => $itemdetails->sub_category,
				        'item_code' => $no,
				        'description' => $item->description,
				        'qty' => $qty,
				        'extra_notes' => $itemdetails->extra_notes,
				        'entry_date' => date('Y-m-d H:i:s'),
				    ];

				    $insert = $this->db->insert('sh_cart_details', $data);

				    if ($insert) {

				        $this->session->set_flashdata('success', 'Item has been successfully added.');
				    } else {
				        $this->session->set_flashdata('error', 'Failed to add item.');
				    }
				}

				$this->db->where('item_code',$ic);
				$this->db->where('id_cart',$id_cart);
				$this->db->where('user_order_id',$uoi);
				$this->db->where('id_trans',$id_trans);
				$this->db->where('id_customer',$id_customer);
	    		$delete = $this->db->delete('sh_cart_details');


				// Redirect kembali ke halaman sebelumnya
				redirect($_SERVER['HTTP_REFERER']);

        
}

	public function changeitemmodal() 
{
    $itemcodeOLD = $this->input->post('itemcodeOLD');
    $item_ids = $this->input->post('id');
    $item_codes = $this->input->post('no');
    $paket_codes = $this->input->post('paket_code');
    $id_cart = $this->input->post('id_cart');
    $quantities = $this->input->post('qty');
    $names = $this->input->post('nama');
    $subcategory = $this->input->post('subcategory');
    
    $id_customer = $this->session->userdata('id');
    $uoi = $this->session->userdata('user_order_id');
    $table = $this->session->userdata('nomeja');
    
    // Ambil transaksi terakhir pelanggan
    $id_trans = $this->db->order_by('id', "desc")
        ->where('id_customer', $id_customer)
        ->limit(1)
        ->get('sh_t_transactions')
        ->row('id');

    if (!empty($item_ids) && !empty($quantities)) {
        // Hapus item lama (itemcodeOLD) hanya jika ada perubahan
        $this->db->where('item_code', $itemcodeOLD);
        $this->db->where('id_cart', $id_cart);
        $this->db->where('user_order_id', $uoi);
        $this->db->where('id_trans', $id_trans);
        $this->db->where('id_customer', $id_customer);
        $this->db->delete('sh_cart_details');

        $batch_update = [];
        $batch_insert = [];

        foreach ($item_ids as $key => $id) {
            $qty = (int) $quantities[$key];

            // Abaikan jika qty = 0
            if ($qty <= 0) {
                continue;
            }

            $itemC = $item_codes[$key];

            // Cek apakah item sudah ada di sh_cart_details
            $existing_item = $this->Paket_model->get_cart_item($id_cart, $itemC, $id_customer, $table, $id_trans, $uoi, date('Y-m-d'));

            if ($existing_item) {
                // Jika item sudah ada, update qty sebelumnya + qty baru
                $new_qty = (int) $existing_item['qty'] + $qty;
                $batch_update[] = [
                    'id_cart' => $id_cart,
                    'item_code' => $itemC,
                    'qty' => $new_qty
                ];
            } else {
                // Jika belum ada, insert data baru
                $batch_insert[] = [
                    'id_cart' => $id_cart,
                    'item_code' => $itemC,
                    'paket_code' => $paket_codes[$key], 
                    'qty' => $qty,
                    'description' => $names[$key],
                    'id_customer' => $id_customer,
                    'id_table' => $table,
                    'id_trans' => $id_trans,
                    'user_order_id' => $uoi,
                    'sub_category' => $subcategory[$key],
                    'extra_notes' => '',
                    'entry_date' => date('Y-m-d H:i:s'),
                ];
            }
        }

        // Update data yang sudah ada
        if (!empty($batch_update)) {
            $this->Paket_model->update_batch_cart($batch_update);
        }

        // Insert data yang belum ada
        if (!empty($batch_insert)) {
            $this->Paket_model->insert_batch_cart($batch_insert);
        }

        $this->session->set_flashdata('success', 'Items updated successfully!');
    } else {
        $this->session->set_flashdata('error', 'No items selected.');
    }

    redirect($_SERVER['HTTP_REFERER']);
}








}


