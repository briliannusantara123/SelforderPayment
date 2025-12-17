<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Ordermakanan extends CI_Controller {

	function __construct()
		{
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
	public function index($nomeja)
	{
		$notif = "";
		$id_customer = $this->session->userdata('id');
	    echo $nomeja;exit();
		//cek paket
		$paket = $this->Item_model->get_paket($nomeja);
		if($paket->tipe_paket == ''){
			$this->session->set_flashdata('error','Anda Belum Menentukan Paket,Silahkan hubungi Waitress Untuk Memilih Paket ');
			redirect('selforder');
		}

		//cek order paket
		$order_paket = $this->Item_model->get_order_paket($nomeja,$id_customer);
		if($order_paket->jml_paket == 0){
			$this->session->set_flashdata('error','Anda Belum Menentukan Paket,Silahkan hubungi Waitress Untuk Memilih Paket ');
			redirect('selforder');
		}

		//cek order kuah
		$order_kuah = $this->Item_model->get_order_kuah($nomeja,$id_customer);
		if(($paket->tipe_paket != '' && $paket->tipe_paket == 'Yakiniku Only') && ($order_kuah->jml_kuah == $order_paket->jml_paket)){
			
		}
		$data['item'] = $this->Item_model->getData();
			$this->load->view('ordermakanan',$data);
		
	}
	public function menu($tipe, $sub_category)
	{
	    $this->session->unset_userdata('notfound');

	    $id_customer = $this->session->userdata('id');
	    $nomeja      = $this->session->userdata('nomeja');

	    $hariIni      = date('N'); // 1=Senin, 7=Minggu
	    $current_hour = date('H');
	    $current_date = date('Y-m-d');

	    // Cek apakah hari ini libur
	    $this->db->where('holiday_date', $current_date);
	    $holiday = $this->db->get('sh_m_holiday')->num_rows() > 0;

	    // Tentukan tipe hari
	    $te = ($holiday || $hariIni >= 6) ? 'WEEKEND' : 'WEEKDAY';

	    // Ambil semua data dari model
	    $items_raw = $this->Item_model->getData($tipe, $sub_category);
	    // var_dump($items_raw);exit();
	    $items_filtered = [];
	    foreach ($items_raw as $item) {
	        $show = true;

	        if (!empty($item->event_item_code)) {
	            $itemType = strtoupper(trim($item->type));

	            // ✅ Cek apakah tanggal valid dulu
	            $tanggalBerlaku = (
	                !empty($item->date_from) &&
	                !empty($item->date_to) &&
	                $current_date >= $item->date_from &&
	                $current_date <= $item->date_to
	            );

	            if ($itemType === 'EVERYDAY') {
	                if ($tanggalBerlaku) {
	                    // Kalau tanggal valid → cek jam
	                    if (!empty($item->time_from) && !empty($item->time_to)) {
	                        if ($current_hour >= $item->time_from && $current_hour <= $item->time_to) {
	                            $show = true;  // tanggal & jam valid
	                        } else {
	                            $show = false; // tanggal valid tapi jam tidak valid
	                        }
	                    } else {
	                        $show = true; // tanggal valid tanpa batas jam
	                    }
	                } else {
	                    // Kalau tanggal tidak valid → tetap tampil
	                    $show = true;
	                }
	            } else {
	                // 🔹 Untuk WEEKDAY/WEEKEND
	                if ($tanggalBerlaku && $itemType === $te) {
				        if (!empty($item->time_from) && !empty($item->time_to)) {
				            if ($current_hour >= $item->time_from && $current_hour <= $item->time_to) {
				                $show = true; // semua valid
				            } else {
				                $show = false; // jam tidak valid
				            }
				        } else {
				            $show = true; // tanggal + type valid tanpa jam
				        }
				    } else {
				        // Jangan di-hide total → biarkan tampil default
				        $show = true;
				    }
	            }
	        }

	        if ($show) {
	            $items_filtered[] = $item;
	        }
	    }

	    // Ambil semua subcategory dari model
	    $subcategories_raw = $this->Item_model->sub_category();

	    // Filter subcategory berdasarkan weekday/weekend & jam
	    $subcategories_filtered = [];
	    foreach ($subcategories_raw as $sub) {
	        $show = true;

	        // Cek weekday/weekend
	        $today = date('l');
	        if (!empty($sub['weekday']) && stripos($sub['weekday'], $today) === false) {
	            if (!empty($sub['weekend']) && stripos($sub['weekend'], $today) === false) {
	                $show = false;
	            }
	        }

	        // Cek jam aktif
	        if (!empty($sub['time_from']) && !empty($sub['time_to'])) {
	            if ($current_hour < $sub['time_from'] || $current_hour > $sub['time_to']) {
	                $show = false;
	            }
	        }

	        if ($show) {
	            $subcategories_filtered[] = $sub;
	        }
	    }

	    // === Tambahkan Signature jika ada chef_recommended ===
	    $signature_exists = false;
	    foreach ($subcategories_filtered as $sf) {
	        if (strtolower($sf['sub_category']) === 'signature') {
	            $signature_exists = true;
	            break;
	        }
	    }

	    if (!$signature_exists) {
	        $has_signature_item = $this->db
	            ->where('is_active', 1)
	            ->where('chef_recommended', 1)
	            ->count_all_results('sh_m_item');

	        if ($has_signature_item > 0) {
	            $subcategories_filtered[] = [
	                'sub_category' => 'Signature',
	                'id'           => 0,
	                'weekday'      => '',
	                'weekend'      => '',
	                'time_from'    => '',
	                'time_to'      => ''
	            ];
	        }
	    }

	    // Pastikan Signature tetap di awal
	    usort($subcategories_filtered, function ($a, $b) {
	        if ($a['sub_category'] === 'Signature') return -1;
	        if ($b['sub_category'] === 'Signature') return 1;
	        if ($a['id'] == $b['id']) return 0;
	        return ($a['id'] < $b['id']) ? -1 : 1;
	    });

	    // Kirim data ke view
	    $data['item']       = $items_filtered;
	    $data['sub']        = $subcategories_filtered;
	    $data['sube']       = $this->Item_model->sub_category_event();
	    $data['s']          = $sub_category;
	    $data['ic']         = $id_customer;
	    $data['key']        = '';
	    $data['cart_count'] = $this->Item_model->hitungcart($nomeja);
	    $data['nomeja']     = $nomeja;

	    $cart_count = $this->Item_model->cart_count($id_customer, $nomeja)->num_rows();
	    $data['total_qty'] = $cart_count > 0 ? $this->Item_model->cart_count($id_customer, $nomeja)->row()->total_qty : 0;

	    $data['iconfooter'] = $this->Admin_model->getIcon('footer');
	    $data['cn']         = $this->Admin_model->getColorCN();
	    $data['ch']         = $this->Admin_model->getColorHD();
	    $data['cb']         = $this->Admin_model->getColorBTN();
	    $data['logo']       = $this->Admin_model->getLogo();
	    $trans = $this->db->order_by('create_date', 'DESC')
                  ->get_where('sh_t_transactions', array('id_customer' => $id_customer))
                  ->row();
        if ($trans->parent_id != 0) {
        	$data['cekpay'] = $this->Item_model->getitem($trans->parent_id,'parent');
        }else{
        	$data['cekpay'] = $this->Item_model->getitem($trans->id,'notparent');
        }

	    $this->load->view('ordermakanan', $data);
	}
	public function search()
	{
	    $id_customer = $this->session->userdata('id');
	    $nomeja      = $this->session->userdata('nomeja');
	    $keyword     = $this->input->post('keyword');

	    $data['s']   = 'Soup';
	    $data['key'] = $keyword;

	    $hariIni      = date('N'); // 1=Senin, 7=Minggu
	    $current_hour = date('H');
	    $current_date = date('Y-m-d');

	    // Cek apakah hari ini libur
	    $this->db->where('holiday_date', $current_date);
	    $holiday = $this->db->get('sh_m_holiday')->num_rows() > 0;

	    // Tentukan tipe hari
	    $te = ($holiday || $hariIni >= 6) ? 'WEEKEND' : 'WEEKDAY';

	    // Ambil data berdasarkan keyword
	    $items_raw = $this->Item_model->get_keyword($keyword);

	    // Filter item sesuai logika menu()
	    $items_filtered = [];
	    foreach ($items_raw as $item) {
	        $show = true;

	        if (!empty($item->event_item_code)) {
	            $itemType = strtoupper(trim($item->type));

	            // ✅ Cek apakah tanggal valid
	            $tanggalBerlaku = (
	                !empty($item->date_from) &&
	                !empty($item->date_to) &&
	                $current_date >= $item->date_from &&
	                $current_date <= $item->date_to
	            );

	            if ($itemType === 'EVERYDAY') {
	                if ($tanggalBerlaku) {
	                    if (!empty($item->time_from) && !empty($item->time_to)) {
	                        $show = ($current_hour >= $item->time_from && $current_hour <= $item->time_to);
	                    } else {
	                        $show = true;
	                    }
	                } else {
	                    $show = true; // tanggal tidak valid → tetap tampil
	                }
	            } else {
	                // 🔹 Untuk WEEKDAY/WEEKEND
	                if ($tanggalBerlaku && $itemType === $te) {
	                    if (!empty($item->time_from) && !empty($item->time_to)) {
	                        $show = ($current_hour >= $item->time_from && $current_hour <= $item->time_to);
	                    } else {
	                        $show = true; // tanggal + type valid tanpa jam
	                    }
	                } else {
	                    // Jangan di-hide total → fallback tampil default
	                    $show = true;
	                }
	            }
	        }

	        if ($show) {
	            $items_filtered[] = $item;
	        }
	    }

	    // Kirim data ke view
	    $data['item']       = $items_filtered;
	    $data['sub']        = $this->Item_model->sub_category();
	    $data['sube']       = $this->Item_model->sub_category_event();
	    $data['nomeja']     = $nomeja;
	    $data['logo']       = $this->Admin_model->getLogo();
	    $data['cart_count'] = $this->Item_model->hitungcart($nomeja);

	    $cart_count = $this->Item_model->cart_count($id_customer, $nomeja)->num_rows();
	    $data['total_qty'] = $cart_count > 0
	        ? $this->Item_model->cart_count($id_customer, $nomeja)->row()->total_qty
	        : 0;

	    if (empty($items_filtered)) {
	        $this->session->set_flashdata('notfound', 'Not Found');
	    }

	    $data['iconfooter'] = $this->Admin_model->getIcon('footer');
	    $data['cn']         = $this->Admin_model->getColorCN();
	    $data['ch']         = $this->Admin_model->getColorHD();
	    $data['cb']         = $this->Admin_model->getColorBTN();
	    $trans = $this->db->order_by('create_date', 'DESC')
                  ->get_where('sh_t_transactions', array('id_customer' => $id_customer))
                  ->row();
        if ($trans->parent_id != 0) {
        	$data['cekpay'] = $this->Item_model->getitem($trans->parent_id,'parent');
        }else{
        	$data['cekpay'] = $this->Item_model->getitem($trans->id,'notparent');
        }

	    $this->load->view('ordermakanan', $data);
	}


	
	public function detailmenu($id,$sub)
	{
		$sharp = str_replace("%20","_", $sub);
		$url = $sub.'#'.$sharp;
		$item = $this->Item_model->getDatabyID($id);
		$addon = $this->Item_model->getAddOn($item->no);
		$option = $this->Item_model->getOption($item->no);
		$nomeja = $this->session->userdata('nomeja');
		$link = 'index.php/ordermakanan/menu/Makanan/'.$url;
		$linkform = 'index.php/ordermakanan/add_cart/'.$url;
		$data = [
			'item' => $item,
			'url' => $url,
			'addon' => $addon,
			'option' => $option,
			'nomeja' => $nomeja,
			'link' => $link,
			'linkform' => $linkform,
			'cn' => $this->Admin_model->getColorCN(),
			'ch' => $this->Admin_model->getColorHD(),
			'cb' => $this->Admin_model->getColorBTN(),
			'logo' => $this->Admin_model->getLogo(),
		];
		$this->load->view('detailmenu',$data);
	}
	public function menumakanan($tipe,$sub_category)
	{
		
		$id_customer = $this->session->userdata('id');
		$nomeja = $this->session->userdata('nomeja');
		$data['item'] = $this->Item_model->getData($tipe,$sub_category);
		$data['sub'] = $this->Item_model->sub_category();
		//$data['option'] = $this->Item_model->option();
		$data['s'] = $sub_category;
		$data['ic'] = $id_customer;
		$data['cart_count'] = $this->Item_model->hitungcart($nomeja);
		$data['nomeja'] = $this->session->userdata('nomeja');
		$cart_count = $this->Item_model->cart_count($id_customer,$nomeja)->num_rows();
		if($cart_count > 0){
			$cart = $this->Item_model->cart_count($id_customer,$nomeja)->row();//tambahan	
			$cart_total = $cart->total_qty;
		}else{
			$cart_total = 0;
		}
		$data['total_qty'] = $cart_total;

			$this->load->view('menu/makanan',$data);
		
	}
	public function option_list($item_code) {
		
	$option = $this->Item_model->option($item_code);
	$html = "<select id='item_option' name='item_option'>";
	$html .= "<option value=''>--Option--</option>";
	foreach($option as $o){
		$html .= "<option value='".$o->description."'>".$o->description."</option>";
	}
	$html .= "</select>";
	return $html;
}
	public function subcreate($nomeja,$cek,$sub=NULL)
	{
		$uc = $this->session->userdata('username');
		$id_customer = $this->session->userdata('id');
		$cabang = $this->db->order_by('id',"desc")
  			->limit(1)
  			->get('sh_m_cabang')
  			->row('id');
  		$notrans = $this->db->order_by('id',"desc")->where('id_customer',$id_customer)
  			->limit(1)
  			->get('sh_t_transactions')
  			->row('id');
		$data['order_bill'] = $this->Item_model->order_bill_co($cabang,$notrans);
		$data['total'] = $this->Item_model->totalSubOrder($uc);
		$data['item'] = $this->Item_model->getDataSubOrder($uc);
		$data['no_meja'] = $this->session->userdata('nomeja');
		$data['cek'] = $cek;
		$data['sub'] = $sub;
		
		$this->load->view('ordermakanan_view',$data);

	}
	public function batal()
	{
		$ic = $this->session->userdata('id');
		$nomeja = $this->session->userdata('nomeja');
		$this->db->where('id_customer',$ic);
    	$this->db->delete('sh_t_sub_transactions');
    	redirect('index.php/ordermakanan/menu/Makanan/ayam/'.$nomeja);
	}
	public function searchOLD()
	{
		$id_customer = $this->session->userdata('id');
		$nomeja = $this->session->userdata('nomeja');
		$keyword = $this->input->post('keyword');
		$data['s'] = 'Soup';
		$data['key'] = $keyword;
		$data['item'] = $this->Item_model->get_keyword($keyword);
		$data['sub'] = $this->Item_model->sub_category();
		$data['sube'] = $this->Item_model->sub_category_event();
		$data['nomeja'] = $this->session->userdata('nomeja');
		$data['logo'] = $this->Admin_model->getLogo();
		$data['cart_count'] = $this->Item_model->hitungcart($nomeja);
		$cart_count = $this->Item_model->cart_count($id_customer,$nomeja)->num_rows();
		$data_count = $this->Item_model->get_keyword($keyword);
		if ($data_count == NULL) {
			$this->session->set_flashdata('notfound','Not Found');
		}
		
		if($cart_count > 0){
			$cart = $this->Item_model->cart_count($id_customer,$nomeja)->row();//tambahan	
			$cart_total = $cart->total_qty;
		}else{
			$cart_total = 0;
		}
		$data['total_qty'] = $cart_total;
		$data['iconfooter'] = $this->Admin_model->getIcon('footer');
		$data['cn'] = $this->Admin_model->getColorCN();
		$data['ch'] = $this->Admin_model->getColorHD();
		$data['cb'] = $this->Admin_model->getColorBTN();
		$this->load->view('ordermakanan',$data);
	}
	

	public function searchdata($query) {
        $results = $this->Item_model->get_keyword($keyword);
         header('Content-Type: application/json');
        echo json_encode($results);
    }
	public function create()
	{
		$uc = $this->session->userdata('username');
		$ic = $this->session->userdata('id');
		$qty = $this->input->post('qty');
		$nama = $this->input->post('nama');
		$pesan = $this->input->post('pesan');
		$harga = $this->input->post('harga');
		$item_code = $this->input->post('no');
		$nomeja = $this->session->userdata('nomeja');
		
		$nomer = 1;
		for ($i = 0; $i < count($qty); $i++) {
			if ($qty[$i] != 0) {
				$n = $nomer++ . "<br>"; 
				$data[] = [
				'qty' => $qty[$i],
				'harga' => $harga[$i],
				'nama' => $nama[$i],
				'pesan' => $pesan[$i],
				'entry_by' => $uc,
				'id_customer' => $ic,
				'item_code' => $item_code[$i],
			];
			}
    
	}
	$result = $this->db->insert_batch('sh_t_sub_transactions',$data);
			if ($result) {
				redirect('index.php/ordermakanan/subcreate/'.$nomeja);
				// $where = array('qty' => 0);
				// $this->Item_model->hapus_qty($where,'testing');
			}else{
				echo "gagal order";
			}

		
	}

	public function orderqty() 
	{
		$table = $this->session->userdata('nomeja');
		$uc = $this->session->userdata('username');
		$ic = $this->session->userdata('id');
		$post = $this->input->post();
		$trans = $this->db->get_Where('sh_t_transactions', array('id_customer'=> $ic))->row();
		if($post['tipe']=='plus' && $post['item_code'] != ''){
			$cek_count = $this->Item_model->get_cart($ic,$table,$post['item_code'],$post['uoi'])->num_rows();
			if($cek_count > 0){
				$cek_cart = $this->Item_model->get_cart($ic,$table,$post['item_code'],$post['uoi'])->row();
				if ($post['need_stock'] == 1) {
					if ($cek_cart->qty >= $post['stock']) {
						$aqty = $cek_cart->qty;
						$cek = True;
					}else{
						$aqty = $cek_cart->qty+1;
						$cek = False;
					}
				}else{
					$aqty = $cek_cart->qty+1;
					$cek = False;
				}
					
				$pesan = $post['extra_notes'];
				if($pesan != ''){
					$data = [
						'qty' => $aqty,
						'extra_notes' => $post['extra_notes'],
						'user_order_id' => $this->session->userdata('user_order_id'),
					];
				}else{
					$data = [
						'qty' => $aqty,
						'user_order_id' => $this->session->userdata('user_order_id'),
					];	
				}
				
				$this->Item_model->save('sh_cart',$data, ['id'=>$cek_cart->id]);
				$cart_count = $this->Item_model->hitungcart($table);
				$carts = $this->Item_model->cart_count($ic,$table)->num_rows();
				if($carts > 0){
					$cart = $this->Item_model->cart_count($ic,$table)->row();	
					$total_qty = $cart->total_qty;
				}else{
					$total_qty = 0;
				}

				$notif = "Food Stocks Are Not Fulfilled";

				
				echo json_encode(array('status'=> True,'new_qty'=> $aqty,'pesan'=>$pesan,'cart_count'=>(int)$cart_count,'total_qty'=>(int)$total_qty,'notif' => $notif,'cek'=>$cek));
			}else{

				$pesan = $post['extra_notes'];
				$data = [
					'item_code' => $post['item_code'],
					'id_trans' => $trans->id,
					'id_customer' => $ic,
					'qty' => 1,
					'cabang' => $trans->cabang,
					'unit_price' => $post['unit_price'],
					'description' => $post['description'],
					'entry_by' => $this->session->userdata('username'),
					'id_table' => $table,
					'extra_notes' => $post['extra_notes'],
					'entry_date' => date('Y-m-d H:i:s'),
					'user_order_id' => $this->session->userdata('user_order_id'),
				];
				
				$cart_id = $this->Item_model->save('sh_cart',$data);
				$cart_count = $this->Item_model->hitungcart($table);

				$carts = $this->Item_model->cart_count($ic,$table)->num_rows();
				if($carts > 0){
					$cart = $this->Item_model->cart_count($ic,$table)->row();	
					$total_qty = $cart->total_qty;
				}else{
					$total_qty = 0;
				}
				$id_customer = $this->session->userdata('id');
				$id_trans = $this->db->get_Where('sh_t_transactions', array('id_customer'=> $id_customer))->row();
				$cabang = $this->db->order_by('id',"desc")
			  			->limit(1)
			  			->get('sh_m_cabang')
			  			->row('id');
			  	$ip_address = $this->input->ip_address();
			  	$cust = $this->session->userdata('username');
				$dataevent = [
					'event_type' => 'Update cart SO',
					'cabang' => $cabang,
					'id_trans' => $id_trans->id,
					'id_customer' => $this->session->userdata('id'),
					'event_date' => date('Y-m-d H:i:s'),
					'user_by' => $this->session->userdata('username'),
					'description' => 'Menambahkan item: '.$post['description'].' dengan qty: 1',
					'created_date' => date('Y-m-d'),
				];
				$result = $this->db->insert('sh_event_log',$dataevent);
				
				if($cart_id){
					echo json_encode(array('status'=> True,'new_qty'=> 1,'pesan'=>$pesan,'cart_count'=>(int)$cart_count,'total_qty'=>(int)$total_qty));	
				}
			}
		}else if($post['tipe']=='minus' && $post['item_code'] != ''){
			$cek_count = $this->Item_model->get_cart($ic,$table,$post['item_code'],$post['uoi'])->num_rows();
			if($cek_count > 0){
				$cek_cart = $this->Item_model->get_cart($ic,$table,$post['item_code'],$post['uoi'])->row();
				if($cek_cart->qty == 1){
					$this->db->delete('sh_cart',['id'=>$cek_cart->id]);
					$cart_count = $this->Item_model->hitungcart($table);
					$carts = $this->Item_model->cart_count($ic,$table)->num_rows();
					if($carts > 0){
						$cart = $this->Item_model->cart_count($ic,$table)->row();	
						$total_qty = $cart->total_qty;
					}else{
						$total_qty = 0;
					}
					$id_customer = $this->session->userdata('id');
					$id_trans = $this->db->get_Where('sh_t_transactions', array('id_customer'=> $id_customer))->row();
					$cabang = $this->db->order_by('id',"desc")
				  			->limit(1)
				  			->get('sh_m_cabang')
				  			->row('id');
				  	$ip_address = $this->input->ip_address();
				  	$cust = $this->session->userdata('username');
					$dataevent = [
						'event_type' => 'Update cart SO',
						'cabang' => $cabang,
						'id_trans' => $id_trans->id,
						'id_customer' => $this->session->userdata('id'),
						'event_date' => date('Y-m-d H:i:s'),
						'user_by' => $this->session->userdata('username'),
						'description' => 'Mengurangi 1 qty item: '.$post['description'],
						'created_date' => date('Y-m-d'),
					];
					$result = $this->db->insert('sh_event_log',$dataevent);
					echo json_encode(array('status'=> True,'new_qty'=> 0,'pesan'=>'','cart_count'=>(int)$cart_count,'total_qty'=>(int)$total_qty));
				}else{
					$pesan = $post['extra_notes'];
					$data = [
						'qty' => ($cek_cart->qty-1),
					];
					$this->Item_model->save('sh_cart',$data, ['id'=>$cek_cart->id]);
					$cart_count = $this->Item_model->hitungcart($table);
					$carts = $this->Item_model->cart_count($ic,$table)->num_rows();
					if($carts > 0){
						$cart = $this->Item_model->cart_count($ic,$table)->row();	
						$total_qty = $cart->total_qty;
					}else{
						$total_qty = 0;
					}
					
					echo json_encode(array('status'=> True,'new_qty'=> ($cek_cart->qty-1),'pesan'=>$pesan,'cart_count'=>(int)$cart_count,'total_qty'=>(int)$total_qty));
				}
			}
		}
	}
	public function add_cartOLD($sub)
	{
	    $options = $_POST['options'];
	    foreach ($options as $option) {
	        $op = htmlspecialchars($option);
	    }
	    $addons = $this->input->post('addons');
	    $soldOut = false;

	    // ✅ Cek stok add-on
	    foreach ($addons as $item_code) {
	        $this->db->select('need_stock, stock');
	        $this->db->from('sh_m_item');
	        $this->db->where('no', $item_code);
	        $query = $this->db->get();

	        $item = $query->row();
	        if ($item->need_stock == 1 && $item->stock <= 0) {
	            $soldOut = true;
	            break;
	        }
	    }

	    if ($soldOut) {
	        $this->session->set_flashdata('error', 'The add-on stock has been sold out');
	        redirect($_SERVER['HTTP_REFERER']);
	    }

	    // Data input utama
	    $sharp = str_replace("%20", "_", $sub);
	    $url = $sub . '#' . $sharp;
	    $id = $this->input->post('id');
	    $no = $this->input->post('no');
	    $nama = $this->input->post('nama');
	    $harga = $this->input->post('unit_price');
	    $qty = $this->input->post('qty');
	    $up = $this->input->post('unit_price_disc');
	    $disc = $this->input->post('disc');
	    $pesan = $this->input->post('notes');
	    $uoi = $this->session->userdata('uoi');
	    $id_customer = $this->session->userdata('id');
	    $unit_price_disc = $up ?: 0;

	    // ✅ Ambil data sub_category item yang sedang ditambahkan
	    $itemData = $this->db->get_where('sh_m_item', ['no' => $no])->row();
	    $sub_category = $itemData ? $itemData->sub_category : '';

	    // ✅ Validasi khusus untuk Special Promo
	    if ($sub_category == 'Special Promo') {
		    $cekSPtrans   = $this->Item_model->cekSPtrans($no);   // true jika item SP ini sudah ada di transaksi
		    $cekSPchart   = $this->Item_model->cekSPchart($no);   // true jika item SP ini sudah ada di cart
		    $countSPchart = $this->Item_model->countSPchart();    // jumlah total SP di cart
		    $countSPtrans = $this->Item_model->countSPtrans();    // jumlah total SP di transaksi

		    // Cek apakah sudah ada Special Promo lain di sistem
		    $hasOtherSP = ($countSPchart > 0 || $countSPtrans > 0);

		    // Kasus 1: Belum ada SP di cart/transaksi -> boleh insert
		    if (!$hasOtherSP) {
		        // lanjut ke proses insert
		    }
		    // Kasus 2: Item sama dengan yang sudah ada di cart/transaksi -> boleh insert
		    else if ($cekSPchart || $cekSPtrans) {
		        // lanjut ke proses insert
		    }
		    // Kasus 3: Ada SP lain (beda item) -> tolak
		    else {
		        $this->session->set_flashdata('error', 'You can only add one Special Promo item.');
		        redirect($_SERVER['HTTP_REFERER']);
		    }
		}



	    // ✅ lanjutkan proses existing logic
	    $id_trans = $this->db->get_where('sh_t_transactions', ['id_customer' => $id_customer])->row();
	    $cekdatacart = $this->Item_model->cekdatacart($no, $uoi)->row();

	    $cabang = $this->db->order_by('id', "desc")->limit(1)->get('sh_m_cabang')->row('id');

	    $data = [
	        'item_code' => $no,
	        'id_trans' => $id_trans->id,
	        'id_customer' => $id_customer,
	        'qty' => $qty,
	        'cabang' => $cabang,
	        'unit_price' => $harga,
	        'description' => $nama,
	        'entry_by' => $this->session->userdata('username'),
	        'id_table' => $this->session->userdata('nomeja'),
	        'extra_notes' => $pesan,
	        'entry_date' => date('Y-m-d'),
	        'user_order_id' => $this->session->userdata('user_order_id'),
	        'options' => $op,
	        'unit_price_disc' => $unit_price_disc,
	        'disc' => $disc,
	        'addons' => 0,
	    ];

	    if ($qty == 0) {
	        $this->session->set_flashdata('error', 'Order Gagal! Tambahkan jumlah pesan!');
	        redirect($_SERVER['HTTP_REFERER']);
	    } else {
	        if ($cekdatacart) {
	            if ($cekdatacart->extra_notes == $pesan) {
	                $dataedit = ['qty' => $cekdatacart->qty + $qty];
	                $this->db->where([
	                    'item_code' => $no,
	                    'id_customer' => $id_customer,
	                    'user_order_id' => $this->session->userdata('user_order_id')
	                ]);
	                $result = $this->db->update('sh_cart', $dataedit);
	            } else {
	                $result = $this->db->insert('sh_cart', $data);
	            }
	        } else {
	            $result = $this->db->insert('sh_cart', $data);
	        }

	        if ($result) {
	            // (bagian add-on dan log event tetap sama seperti sebelumnya)
	            $this->session->set_flashdata('success', 'Menu Added to Cart');
	            redirect('index.php/ordermakanan/menu/Makanan/' . $url);
	        }
	    }
	}

	public function add_cart($sub)
	{
	    $optionsReq = $this->input->post('options');
	    $op = is_array($optionsReq) ? htmlspecialchars(implode(',', $optionsReq)) : htmlspecialchars($optionsReq);

	    $addons = $this->input->post('addons') ?? [];
	    $soldOut = false;

	    // CEK STOK ADDON
	    foreach ($addons as $item_code) {
	        $item = $this->db
	            ->select('need_stock, stock')
	            ->where('no', $item_code)
	            ->get('sh_m_item')
	            ->row();

	        if ($item && $item->need_stock == 1 && $item->stock <= 0) {
	            $soldOut = true;
	            break;
	        }
	    }

	    if ($soldOut) {
	        $this->session->set_flashdata('error', 'The add-on stock has been sold out');
	        redirect($_SERVER['HTTP_REFERER']);
	    }

	    $sharp = str_replace("%20", "_", $sub);
	    $url   = $sub . '#' . $sharp;

	    $no      = $this->input->post('no');
	    $nama    = $this->input->post('nama');
	    $harga   = $this->input->post('unit_price');
	    $qty     = (int)$this->input->post('qty');
	    $up      = $this->input->post('unit_price_disc');
	    $disc    = $this->input->post('disc');
	    $pesan   = $this->input->post('notes');
	    $id_customer = $this->session->userdata('id');

	    if ($qty <= 0) {
	        $this->session->set_flashdata('error', 'Order Gagal! Tambahkan jumlah pesan!');
	        redirect($_SERVER['HTTP_REFERER']);
	    }

	    $unit_price_disc = $up ?: 0;

	    $id_trans = $this->db
	        ->get_where('sh_t_transactions', ['id_customer' => $id_customer])
	        ->row();

	    $cabang = $this->db
	        ->order_by('id', 'desc')
	        ->limit(1)
	        ->get('sh_m_cabang')
	        ->row('id');

	    // CEK ITEM SAMA DI CART (BERDASARKAN item_code + options)
	    $cartSameOption = $this->db
	        ->where([
	            'item_code'    => $no,
	            'id_customer'  => $id_customer,
	            'user_order_id'=> $this->session->userdata('user_order_id'),
	            'options'      => $op
	        ])
	        ->get('sh_cart')
	        ->row();

	    $data = [
	        'item_code'        => $no,
	        'id_trans'         => $id_trans->id,
	        'id_customer'      => $id_customer,
	        'qty'              => $qty,
	        'cabang'           => $cabang,
	        'unit_price'       => $harga,
	        'description'      => $nama,
	        'entry_by'         => $this->session->userdata('username'),
	        'id_table'         => $this->session->userdata('nomeja'),
	        'extra_notes'      => $pesan,
	        'entry_date'       => date('Y-m-d'),
	        'user_order_id'    => $this->session->userdata('user_order_id'),
	        'options'          => $op,
	        'unit_price_disc'  => $unit_price_disc,
	        'disc'             => $disc,
	        'addons'           => 0
	    ];

	    if ($cartSameOption) {
	        // OPTIONS SAMA → UPDATE QTY
	        $this->db->where('id', $cartSameOption->id);
	        $result = $this->db->update('sh_cart', [
	            'qty' => $cartSameOption->qty + $qty
	        ]);
	    } else {
	        // OPTIONS BEDA → INSERT BARU
	        $result = $this->db->insert('sh_cart', $data);
	    }

	    if ($result) {
	        $this->session->set_flashdata('success', 'Menu Added to Cart');
	        redirect('index.php/ordermakanan/menu/Makanan/' . $url);
	    }
	}



	public function addcart()
	{
		$table = $this->session->userdata('nomeja');
		$uc = $this->session->userdata('username');
		$ic = $this->session->userdata('id');
		$qty = $this->input->post('qty');
		$ata = $this->input->post('cek');
		$qta = $this->input->post('qta');
		$nama = $this->input->post('nama');
		$pesan = $this->input->post('pesan');
		$harga = $this->input->post('harga');
		$item_code = $this->input->post('no');
		$id_customer = $this->session->userdata('id');
		$id_trans = $this->db->get_Where('sh_t_transactions', array('id_customer'=> $id_customer))->row();
		$cabang = $this->db->order_by('id',"desc")
  			->limit(1)
  			->get('sh_m_cabang')
  			->row('id');
		echo $qty;
		$nomer = 1;
		for ($i = 0; $i < count($qty); $i++) {
			
			if ($qty[$i] != 0) {
				$n = $nomer++ . "<br>"; 
				$data[] = [
				'item_code' => $item_code[$i],
				'id_trans' => $id_trans->id,
				'id_customer' => $id_customer,
				'qty' => $qty[$i],
				'cabang' => $cabang,
				'unit_price' => $harga[$i],
				'description' => $nama[$i],
				'entry_by' => $this->session->userdata('username'),
				'id_table' => $table,
				'extra_notes' => $pesan[$i],
				'entry_date' => date('Y-m-d'),
				'as_take_away' => $ata[$i],
				'qty_take_away' => $qta[$i],
				'user_order_id' => $this->session->userdata('user_order_id'),
			];
			}
			$query = "select R.* from sh_cart R where R.id_table = '$table' and where R.description = '$nama[$i]' and left(R.create_date,10) = left(sysdate(),10) limit 1";
			$sql = $this->db->query("SELECT * FROM sh_cart where description='$nama[$i]' and id_table='$table' and Left(entry_date, 10) = Left(SYSDATE(), 10) limit 1");
			$cek_data = $sql->num_rows();
			if ($cek_data > 0) {
			$this->db->update_batch('sh_cart',$data,'item_code')->where('id_table',$table)->where('id_customer',$id_customer);
			}else{
			$this->db->insert_batch('sh_cart',$data);
			}
    
	}

	if ($data == NULL) {
		$this->session->set_flashdata('error','Silahkan Pilih Makanan Yang Akan Di Pesan!');
				redirect($_SERVER['HTTP_REFERER']);
	}else{
	$result = $this->db->insert_batch('sh_cart',$data);
			if ($result) {
				$this->session->set_flashdata('success','Order Menu/Paket Berhasil Di Tambahkan Ke Dalam Cart');
				redirect($_SERVER['HTTP_REFERER']);
				// $where = array('qty' => 0);
				// $this->Item_model->hapus_qty($where,'testing');
			}else{
				echo "gagal order";
			}
	}
	}
	public function updatecart($id){
		$table = $this->session->userdata('nomeja');
		$uc = $this->session->userdata('username');
		$ic = $this->session->userdata('id');
		$qty = $this->input->post('qty');
		$ata = $this->input->post('cek');
		$qta = $this->input->post('qta');
		$nama = $this->input->post('nama');
		$pesan = $this->input->post('pesan');
		$harga = $this->input->post('harga');
		$item_code = $this->input->post('no');
		$id_customer = $this->session->userdata('id');
		$id_trans = $this->db->get_Where('sh_t_transactions', array('id_customer'=> $id_customer))->row();
		$cabang = $this->db->order_by('id',"desc")
  			->limit(1)
  			->get('sh_m_cabang')
  			->row('id');
	
		$nomer = 1;
		for ($i = 0; $i < count($qty); $i++) {
			
			if ($qty[$i] != 0) {
				$n = $nomer++ . "<br>"; 
				$data[] = [
				'item_code' => $item_code[$i],
				'qty' => $qty[$i]-1,
				
			];

			
			}
			if ($qty[$i] == 1) {
				$this->db->where('item_code', $item_code[$i]);
				$this->db->where('id_table',$table);
				$this->db->where('id_customer',$id_customer);
				$this->db->where('entry_date',date('Y-m-d'));
				$this->db->delete('sh_cart');
			}else{
			$this->db->update_batch('sh_cart',$data,'item_code')->where('id_table',$table)->where('id_customer',$id_customer)->where('entry_date',date('Y-m-d'));
    		}
	}
	
	}
	public function jmlcart(){
		$id_customer = $this->session->userdata('id');
		$nomeja = $this->session->userdata('nomeja');
		$cart_count = $this->Item_model->cart_count($id_customer,$nomeja)->num_rows();
		if($cart_count > 0){
			$cart = $this->Item_model->cart_count($id_customer,$nomeja)->row();//tambahan	
			$cart_total = $cart->total_qty;
		}else{
			$cart_total = 0;
		}
		$result['total'] = $cart_total;
		$result['msg'] = "Berhasil di refresh secara Realtime";
		echo json_encode($result);
	}
	public function order()
	{
		$table = $this->session->userdata('nomeja');
		$qty = $this->input->post('qty');
		$nama = $this->input->post('nama');
		$pesan = $this->input->post('pesan');
		$harga = $this->input->post('harga');
		$item_code = $this->input->post('no');
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
		if($check_promo > 0){
			$item_check = $this->Item_model->get_info_item($request['item_code'],$get_promo)->num_rows();
			if($item_check > 0){
				$item_data = $this->Item_model->get_info_item($request['item_code'],$get_promo)->row_array();
				if($get_promo["promo_type"] == 'Discount'){
					if($get_promo["promo_criteria"] == 'Weekday'){ //Weekday
						if($cekWeekEnd !== "Sat" || $cekWeekEnd !== "Sun" || $cekWeekEnd !== "Sab" || $cekWeekEnd !== "Min"){
							if($curTime[0] >= $get_promo["promo_from"] && $curTime[0] <= $get_promo["promo_to"]){
								$discount = $get_promo["promo_value"];		
							}else{
								$discount = 0;
							}
						}else{
							$discount = 0;
						}	
					}else if($get_promo["promo_criteria"] == 'Weekend'){ //Weekend
						if($cekWeekEnd === "Sat" || $cekWeekEnd === "Sun" || $cekWeekEnd === "Sab" || $cekWeekEnd === "Min"){
							if($curTime[0] >= $get_promo["promo_from"] && $curTime[0] <= $get_promo["promo_to"]){
								$discount = $get_promo["promo_value"];		
							}else{
								$discount = 0;
							}
						}else{
							$discount = 0;
						}	
					}else{ //Full Week
						if($curTime[0] >= $get_promo["promo_from"] && $curTime[0] <= $get_promo["promo_to"]){
							$discount = $get_promo["promo_value"];		
						}else{
							$discount = 0;
						}
					}
				}else{
					$discount = 0;	
				}
			}else{
				$discount = 0;
			}
		}
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
				'order_type' => $order_stat
			];
			 }
    
	}
	// var_dump($data);exit();
	$result = $this->db->insert_batch('sh_t_transaction_details',$data);
			if ($result) {
				$ic = $this->session->userdata('id');
				 $data = ['status' => 'Dining'];
				$this->db->where('id_customer',$ic);
    			$this->db->update('sh_rel_table',$data);
    			$this->db->where('id_customer',$ic);
    			$this->db->delete('sh_t_sub_transactions');
    			$this->session->set_flashdata('success','Order Menu/Paket Berhasil Di Tambahkan');
				redirect('selforder/home/'.$table);
				// $where = array('qty' => 0);
				// $this->Item_model->hapus_qty($where,'testing');
			}else{
				echo "gagal order";
			}
	}
	
}


