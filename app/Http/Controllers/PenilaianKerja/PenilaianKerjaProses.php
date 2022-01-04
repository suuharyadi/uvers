<?php

namespace App\Http\Controllers\PenilaianKerja;


use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Encryption\DecryptException;


use Illuminate\Support\Facades\Crypt;


use App\level as Level;
use App\Pegawai as Pegawai; 
use App\SuratTugasPembimbing as srttgspembimbing; 
use DataTables;
use DB;
use Validator; 
use Response;
use Redirect;
use Auth;
use File;


class PenilaianKerjaProses extends Controller
{	

	public function UbahProfilDataDiri(Request $request){

		dd($request->all());

	} 


	//LIHAT BERKAS DATADIRI
	public function LihatBerkasDatdir($id_berkas){

		$Data = DB::table('b_berkas_data_diri')->select('*')->where('id','=',$id_berkas)->first();

		return response()->download($this->PathBerkasDataDiri($Data->id_user, $Data->nama_file), "$Data->nama_file",
            [
                'Content-Type' => 'application/octet-stream'
            ]);

	}

	//HAPUS BERKAS DATA DIRI 
	protected function PathBerkasDataDiri($id_user, $nama_file){
			$path = public_path().'/berkas_data_diri/'.$id_user.'/'.$nama_file;
			return $path;
	}
	public function HapusBerkasDataDiri($id_berkas){

		$Data = DB::table('b_berkas_data_diri')->select('*')->where('id','=',$id_berkas)->first();

		if (file_exists($this->PathBerkasDataDiri($Data->id_user, $Data->nama_file))) {
			$Cekks = File::delete($this->PathBerkasDataDiri($Data->id_user, $Data->nama_file));
			if ($Cekks) {
					DB::table('b_berkas_data_diri')->where('id', '=', $id_berkas)->delete();
					return Redirect::back()->with('success', 'Berhasil menghapus file');
			}else{
					return Redirect::back()->with('error', 'Gagal menghapus file #hg456h');
			}
		}else{
			return Redirect::back()->with('error', 'File tidak ada #lk5f4');
		}

	}


	//SIMPAN BERKAS DATA DIRI
	protected function CekSediaBerkasDataDiri($jenis_berkas, $id_user){
		$Data = DB::table('b_berkas_data_diri')->where([['id_user','=',$id_user],['jenis_berkas','=',$jenis_berkas]])->first();
		if (!is_null($Data)) {
			return 'yes';
		}else{
			return 'no';
		}
	}
	public function BerkasDataDiriPenKerja(Request $request){

				$ktp_upload = $request->file('ktp_upload');
				$npwp_upload = $request->file('npwp_upload');
				$kk_upload = $request->file('kk_upload');
				$bpjs_keten_upload = $request->file('bpjs_keten_upload');
				$bpjs_kesehatan_upload = $request->file('bpjs_kesehatan_upload');
				$sim_upload = $request->file('sim_upload');
				$ijazah_upload = $request->file('ijazah_upload');
				$transkrip_upload = $request->file('transkrip_upload');

				$ijazahs1_upload = $request->file('ijazahs1_upload');
				$ijazahs2_upload = $request->file('ijazahs2_upload');
				$ijazahs3_upload = $request->file('ijazahs3_upload');
				$transkrips1_upload = $request->file('transkrips1_upload');
				$transkrips2_upload = $request->file('transkrips2_upload');
				$transkrips3_upload = $request->file('transkrips3_upload');


				$UrutanNama = array('0' => 'ktp','1' => 'npwp','2' => 'kk', '3' => 'bpjs_ketenagakerjaan', '4' => 'bpjs_kesehatan','5' => 'sim','6' => 'ijazah','7' => 'transkrip','8' => 'ijazahs1','9' => 'transkrips1','10' => 'ijazahs2','11' => 'transkrips2','12' => 'ijazahs3','13' => 'transkrips3');


				$CekArrayFile = array($ktp_upload,$npwp_upload,$kk_upload,$bpjs_keten_upload,$bpjs_kesehatan_upload,$sim_upload,$ijazah_upload,$transkrip_upload,$ijazahs1_upload,$transkrips1_upload,$ijazahs2_upload,$transkrips2_upload,$ijazahs3_upload,$transkrips3_upload);


				if(empty(array_filter($CekArrayFile)) == true){
					return Redirect::back()->with('error', 'Pilih minimal 1 file');
				}else{

						for ($i=0; $i < count($CekArrayFile); $i++) { 

							if (!is_null($CekArrayFile[$i])) {
								$sizephoto = $CekArrayFile[$i]->getSize();
								$new_name = Auth::user()->id.'_'.$UrutanNama[$i] . '.' . $CekArrayFile[$i]->getClientOriginalExtension();
								$ext = $CekArrayFile[$i]->getClientOriginalExtension();
								$jenis_berkas = $UrutanNama[$i];

								if ($sizephoto > 20971520) {
									return Redirect::back()->with('error', 'File '.$UrutanNama[$i].' lebih dari 20 mb');
								}

								if ($this->CekSediaBerkasDataDiri($UrutanNama[$i], Auth::user()->id) == 'yes') {
									return Redirect::back()->with('error', ''.$UrutanNama[$i].' Sudah Ada');
								}


								if ($ext!== "PDF" && $ext!== "pdf" && $ext!== "zip" && $ext!== "ZIP" && $ext!== "rar" && $ext!== "RAR" && $ext!== "jpg" && $ext!== "jpeg" && $ext!== "RAR" && $ext!== "png" && $ext!== "PNG" ) {
						    		
						    		return Redirect::back()->with('error', 'Extention File Tidak Diizinkan');

						    	}else{

						    		$path = public_path().'/berkas_data_diri/'.Auth::user()->id;

										if(!File::isDirectory($path)){
										  Storage::makeDirectory($path, $mode = 0711, true, true);
										}
										if(File::isDirectory("$path/".$new_name)==false){
										    if (file_exists($path.'/'.$new_name)) {
										      return Redirect::back()->with('error', 'FILE SUDAH ADA');
										      die;
										    }
										  	$Check = $CekArrayFile[$i]->move($path, $new_name);
												if ($Check) { 	

							      			$dataInsert[] = ['id_user' => Auth::user()->id, 'jenis_berkas' => $jenis_berkas, 'nama_file' => $new_name, 'type_file' =>  $ext, 'size_file' => $sizephoto, 'created_at' => \Carbon\Carbon::now()];
													
													
							      		}

										}else{                
										   return Redirect::back()->with('error', 'Terjadi Kesalahan #34kh2sd');
										}
						    }

							}
							
						}
				}

				$insert = DB::table('b_berkas_data_diri')->insert($dataInsert);

				if ($insert) {
					 return Redirect::back()->with('success', 'Berhasil memproses berkas');
				}else{
					 return Redirect::back()->with('error', 'Terjadi kesalahan #lk34j3456');
				}
	}

	//SIMPAN PELAKSANAAN TUGAS LAIN
	public function SimpanNilaiPTL(Request $request){

		$validatedData = $request->validate([
        'id_tujuan' => 'required',
        'nilai' => 'required',
        'versi' => 'required',
    ]);

		if ($request->nilai <= 0 || $request->nilai > 4) {
				return Response::json(array('nhg' => '003'), 200);
		}else{
	    $CekData = DB::table('b_tujuan')->select('id_user','id_user_tujuan','id_versi')->where([['id_tujuan','=',$request->id_tujuan],['id_versi','=',$request->versi]])->first();

	    if (!$CekData) {
	    	return Response::json(array('nhg' => '001'), 200);
	    }else{
	    	$updateData = DB::table('b_pelaksanaan_tugas_lain')->where([['id_user','=',$CekData->id_user],['id_versi','=',$CekData->id_versi]])->update(['nilai' => $request->nilai]);

	    	if ($updateData) {
	    		return Response::json(array('nhg' => '002'), 200);
	    	}
	    }
	  }

		
	}

	//SIMPAN HASIL MENILAI ATASAN 
	public function SimpanJawabanNilaiAtasan(Request $request){

		if ($this->CekJawabanMenilaiAtasan(Auth::user()->id,$request->id_versi) > 0) {
			return Response::json(array('nhg' => 'ganda','errors' => false, ), 200);
		}else{

			for ($i = 0; $i < count($request->input('jumlahdata')); $i++) {

			$cekQuery3[] = [		 	
			    			'id_soal' => $request->input('id_soal'.$i),
			    			'jawaban' =>  $request->input('jawaban'.$i),
			    			'jenis_jawaban' => 'nilai_atasan',
								'id_user' => Auth::user()->id,
								'created_at' => \Carbon\Carbon::now(),
			      	];
			    	
			}

			$CekInsert = DB::table('b_jawaban')->insert($cekQuery3);

			if ($CekInsert) {
				return Response::json(array('nhg' => 'berhasil','errors' => false), 200);
			}else{
				return Response::json(array('nhg' => 'gagal','errors' => false), 200);
			}
		}
	}

	//CEK KETESEDIAAN JAWABAN MENILAI ATASAN
	protected function CekJawabanMenilaiAtasan($id_user, $id_versi){

		// NOTE * VERSI SOAL YANG AKTIF, DIDAPAT DARI HASIL JOIN SOAL DAN JAWABAN
		$cekData = DB::table('b_jawaban')
							->join('b_soal','b_soal.id_soal','=','b_jawaban.id_soal')
							->where([['b_jawaban.id_user','=',$id_user],['b_soal.id_versi_fk','=',$id_versi],['b_jawaban.jenis_jawaban','=','nilai_atasan']])->count();

		return $cekData;

	}


	// UPLOAD PELAKSANAAN TUGAS LAIN 
	public function UploadTugasLain(Request $request){

				$filesTugasLain = $request->file('TugasLain');
				$sizephoto = $request->file('TugasLain')->getSize();
	      $new_name = Auth::user()->id.'_'.rand() . '.' . $filesTugasLain->getClientOriginalExtension();

				$ext = $filesTugasLain->getClientOriginalExtension();

				//CEK KETERSEDIAAN UPLOAD
				if ($this->CekSediaPTL(Auth::user()->id) == 'yes') {
					 return Redirect::back()->with('error', 'Pelaksanaan Tugas Lain Sudah Diupload');
				}else{

			    if ($ext!== "PDF" && $ext!== "pdf" && $ext!== "xls" && $ext!== "xlsx" && $ext!== "doc" && $ext!== "docx" && $ext!== "XLS" && $ext!== "XLSX" && $ext!== "DOC" && $ext!== "DOCX" && $ext!== "zip" && $ext!== "ZIP" && $ext!== "rar" && $ext!== "RAR") {
			    		
			    		return Redirect::back()->with('errorext', 'Extention File Tidak Diizinkan');

			    }else{

			    		$path = public_path().'/berkas_pelaksanaan_tugas_lain/'.Auth::user()->id;

							if(!File::isDirectory($path)){
							  Storage::makeDirectory($path, $mode = 0711, true, true);
							}
							if(File::isDirectory("$path/".$new_name)==false){
							    if (file_exists($path.'/'.$new_name)) {
							      return Redirect::back()->with('error', 'FILE SUDAH ADA');
							      die;
							    }
							  
				      		$Check = $filesTugasLain->move($path, $new_name);

				      		if ($Check) { 	
				      			$insert = DB::table('b_pelaksanaan_tugas_lain')->insert(
										    ['id_user' => Auth::user()->id, 'id_versi' => $this->VersiSoal()->id, 'nama_file' => $new_name, 'type_file' =>  $filesTugasLain->getClientOriginalExtension(), 'size_file' => $sizephoto, 'keterangan' => $request->Keterangan , 'created_at' => \Carbon\Carbon::now()]
										);
										if ($insert) {
												 return Redirect::back()->with('success', 'Berhasil Mengupload Pelaksanaan Tugas Lain');
										}
				      		}

							}else{                  // rename the file if another one exist
							   return Redirect::back()->with('error', 'Terjadi Kesalahan #3453sd');
							}
			    }
			  }

	}

	//HAPUS DATA DAN FILE PELAKSANAAN TUGAS LAIN
	public function HapusPTL(Request $request){

		$Data = DB::table('b_pelaksanaan_tugas_lain')->select('*')->where('id','=',$request->data_id)->first();

		if (file_exists($this->PathPTL(Auth::user()->id, $Data->nama_file))) {
			$Cekks = File::delete($this->PathPTL(Auth::user()->id, $Data->nama_file));
			if ($Cekks) {
					DB::table('b_pelaksanaan_tugas_lain')->where('id', '=', $request->data_id)->delete();
					return Response::json(array('Hasil' => 'berhasil'), 200);
			}else{
					return Response::json(array('Hasil' => 'gagal'), 200);
			}
		}else{
			return Response::json(array('Hasil' => 'tidakada'), 200);
		}
	}

	//DOWNLOAD FILE PELAKSANAAN TUGAS LAIN 
	public function DownloadPTL($id){

		if ($id == "no") {
			return Redirect::back()->with('error', 'Terjadi Kesalahan, File Mungkin Belum Diupload');
		}else{

			$Data = DB::table('b_pelaksanaan_tugas_lain')->select('*')->where('id','=',$id)->first();
			if($Data){
			$headers = [
			      'Content-Type' => 'application/pdf',
			      'Content-Type:' => 'image/png',
			      'Content-Type:' => 'image/jpg',
			   ];
			
			if (!file_exists($this->PathPTL($Data->id_user, $Data->nama_file))) {
				dd('File Tidak Ditemukan');
			}else{
				return Response::download($this->PathPTL($Data->id_user, $Data->nama_file), $Data->nama_file, $headers);
			}
			}else{
			 return Redirect::back()->with('error', 'Belum Upload Pelaksanaan Tugas Lain / Belum Dinilai');
			}
	
		}
	}
	//DOWNLOAD FILE PELAKSANAAN TUGAS LAIN UNTUK ADMIN
	public function DownloadPTLForAdmin($id_user,$versi){

		if ($id_user == "no" && $versi == null) {
			return Redirect::back()->with('error', 'Terjadi Kesalahan, File Mungkin Belum Diupload');
		}else{

			$Data = DB::table('b_pelaksanaan_tugas_lain')->select('*')->where([['id_user','=',$id_user],['id_versi','=',$versi]])->first();
			if($Data){
				$headers = [
				      'Content-Type' => 'application/pdf',
				      'Content-Type:' => 'image/png',
				      'Content-Type:' => 'image/jpg',
				   ];
				
				if (!file_exists($this->PathPTL($Data->id_user, $Data->nama_file))) {
					dd('File Tidak Ditemukan');
				}else{
					return Response::download($this->PathPTL($Data->id_user, $Data->nama_file), $Data->nama_file, $headers);
				}
			}else{
			 return Redirect::back()->with('error', 'Belum Upload Pelaksanaan Tugas Lain / Belum Dinilai');
			}
	
		}
	}

	//CEK PATH
	protected function PathPTL($id_user, $nama_file){

		$path = public_path().'/berkas_pelaksanaan_tugas_lain/'.$id_user.'/'.$nama_file;
		return $path;

	}

	//CEK KETERSEDIAAN FILE PELAKSANAAN TUGAS LAIN
	protected function CekSediaPTL($id_user){
		$CekSedia = DB::table('b_pelaksanaan_tugas_lain')->where('id_user','=',$id_user)->count();
		if ($CekSedia > 0) {
			return 'yes';
		}else{
			return 'no';
		}
	}

	//VERSI SOAL / TAHUN PELAKSANAAN
	protected function VersiSoal(){

		$versi = DB::table('b_versi_soal')->select('tahun','id')->where('status_aktif','=','1')->first();
		return $versi;

	}

	// DOWNLOAD PETUNJUK TEKNIS ATAU PANDUAN
	public function DownloadPetunjukTeknis(){
        $file= public_path(). "/BerkasPenilaianKerja/penilaian_kerja_tendik_2021.pdf";

        $headers = [
              'Content-Type' => 'application/pdf',
              'Content-Type:' => 'image/png',
              'Content-Type:' => 'image/jpg',
           ];

        return response()->download($file);

	}

	//simpan pesan motivasi
	public function pesanMotivasi(Request $request){

		$cek = DB::table('b_pesan')->select('pesan_dari','pesan_untuk')
			->where([['pesan_dari','=',$request->dari],['pesan_untuk','=', $request->untuk],['id_versi','=',$request->data_versi]])
			->count();

		if ($cek > 0) {
			return Response::json(array('ceks' => 'sudah ada'), 200);
		}else{
			try {

				DB::table('b_pesan')
			    ->Insert(
			        ['pesan_dari' => $request->dari,'id_versi' => $request->data_versi, 'pesan_untuk' => $request->untuk, 'pesan_isi' => $request->isi_pesan, 'created_at' => \Carbon\Carbon::now()]
			    );

				return Response::json(array('ceks' => 'berhasil'), 200);
			} catch (Exception $e) {
				return Response::json(array('ceks' => 'gagal'), 200);
			}
		}
	}

	//PRINT HASIL VERIFIKASI DARI ATASAN
	public function printverif($id_user){

		$id_userd = Crypt::decryptString($id_user);


		
		$CekJawaban = DB::table('b_jawaban')
						->leftJoin('b_soal','b_jawaban.id_soal','=','b_soal.id_soal')
						->select('b_jawaban.id_user',
							'b_jawaban.jawaban',
							'b_soal.kategori_soal',
							'b_jawaban.id_soal',
							'b_jawaban.id_user_verif',
							'b_jawaban.id_user_verif2',
							'b_jawaban.verif_jawaban',
							'b_jawaban.verif_jawaban2'
						)
						->where('b_jawaban.id_user','=',$id_userd)
						->orderBy('b_jawaban.id_soal', 'ASC')
						->get();

		$cek_tujuan = DB::table('b_tujuan')
		->join('users','users.id','=','b_tujuan.id_user_tujuan')
		->select('id_user_tujuan','users.name')
		->where('id_user','=',$id_userd)
		->get();
		

		return view('admin.dashboard.penilaiankerja.print_jawaban',['id_user' => $id_userd, 'CekJawaban' => $CekJawaban,'tujuan' => $cek_tujuan]);

	}


	//UPDATE JAWABAN UNTUK EDIT DARI FORM PENILAIAN YANG TELAH KITA ISI
	public function UpdateJawaban(Request $request){

		for ($i = 0; $i < count($request->input('jumlahdata')); $i++) {

			DB::table('b_jawaban')
			->where('id_jawaban','=', $request->input('id_jawaban'.$i))
            ->update([
            			'jawaban' =>  $request->input('jawaban'.$i),
						'updated_at' => \Carbon\Carbon::now()
					]);
		
	        	
		}

		return Response::json(array(
				                'success' => 'Berhasil',
				                'errors' => false,
				            ), 200);

	}


	//JAWABAN VERIFIKASI DARI ATASAN
	public function SimpanJawabanVerif(Request $request){

		for ($i = 0; $i < count($request->input('jumlahdata')); $i++) {
        	$cek_verif  = DB::table('b_verif_jawaban')
                    ->where([

                    			['id_user_verif','=',$request->id_u_tujuan],
                    			['id_jawaban_fk','=',$request->input('id_jawaban'.$i)]
                    	])
                    ->count();

         	if ($cek_verif > 0) {
	         	return Response::json(array(
					                'cekganda' => 'ganda',
					                'error' => false,
					            ), 200);
	         }
         }
		
		for ($i = 0; $i < count($request->input('jumlahdata')); $i++) {

		  	DB::table('b_verif_jawaban')
            ->insert([	'id_user_verif' => $request->id_u_tujuan,
            			'id_jawaban_fk' => $request->input('id_jawaban'.$i),
        			 	'verif_jawaban' =>  $request->input('jawaban'.$i),
        			 	'created_at' => \Carbon\Carbon::now(),
        			 ]);
		}
		return Response::json(array('cekganda' => 'Berhasil'), 200);

	}

	//CEK PELAKSANAAN TUGAS LAIN SUDAH DI UPLOAD ATAU BELUM
	protected function CekPTLUpload($versi, $id_user, $level){
		//CEK UPLOAD TUGAS LAIN
		$DataPTL = DB::table('b_pelaksanaan_tugas_lain')->where([['id_versi','=',$versi],['id_user','=',$id_user]])->get();

		if ($DataPTL->isEmpty()){$Hasil = '1';}else{$Hasil = '0';}

		if ($level == '4' || $level == '3' || $level == '1') {//BAWAHAN
		    $CekPTLl = $Hasil;
		}elseif( $level == '10' || $level == '2' || $level == '14'){//BAWAHAN MENENGAH 
		    $CekPTLl = $Hasil;
		}elseif( $level == '11' || $level == '12' ){//ATASAN
		    $CekPTLl = 'atasan';
		}else{ }
		return $CekPTLl;
	}

	//MENGUBAH STATUS / MENGAJUKAN TELAH SELESAI
	public function PostStatus(Request $request){


		//CEK KESUDAHAN PENGISIAN FORM PENILAIAN KERJA		
		if($this->cek_akses('93') == 'yes'){
			$a=$this->form_type('a',$request->versi);
		}
		if($this->cek_akses('94') == 'yes'){
			$b=$this->form_type('b',$request->versi);
		}
		if($this->cek_akses('95') == 'yes'){
			$c=$this->form_type('c',$request->versi);
		} 
		if($this->cek_akses('96') == 'yes'){
			$d=$this->form_type('d',$request->versi);
		}

		if($this->cek_akses('94') == 'yes'){
			$hasiljabatan = array($a,$b,$c,$d);
			if (in_array('0', $hasiljabatan)) {
				return response()->json(['gg' => '2'], 200);
			}
		}else{

			$hasilnonjabatan = array($a,$c,$d);
			if (in_array('0', $hasilnonjabatan)) {
				return Response::json(array('gg' => '2'), 200);
			}
		}


		if (Auth::user()->level == '4' || Auth::user()->level == '3' || Auth::user()->level == '1') {
				
		}else{

				//UNTUK CEK JAWABAN BAWAHAN SUDAH DIVERIFIKASI SEMUA ATAU BELUM
				$cek_tujuan = DB::table('b_tujuan')//CEK BAWAHANNYA SIAPA AJA
				->join('users','users.id','=','b_tujuan.id_user')
				->select('users.id')
				->where([['id_user_tujuan','=', Auth::user()->id],['b_tujuan.id_versi','=', $request->versi]])
				->get();
				

				if (!$cek_tujuan->isEmpty()) {

						$TotalTerjawab = 0;
						foreach ($cek_tujuan as $key => $value) {

							$JumlahJawaban[] = DB::table('b_jawaban')
							->join('b_soal','b_soal.id_soal','=','b_jawaban.id_soal')
							->where([['b_jawaban.id_user','=',$value->id],['b_soal.id_versi_fk','=',$request->versi],['b_jawaban.jenis_jawaban','!=','nilai_atasan']])
							->get();
							
						}
						//HITUNG TOTAL TERJAWAB
						$counTerjawab = 0; foreach ($JumlahJawaban as $type) {  $counTerjawab+= count($type);}

						$TotalJawabanVerif = DB::table('b_verif_jawaban')
						->join('b_jawaban','b_verif_jawaban.id_jawaban_fk','=','b_jawaban.id_jawaban')
						->join('b_soal','b_jawaban.id_soal','=','b_soal.id_soal')
						->where([['b_verif_jawaban.id_user_verif','=',Auth::user()->id],['b_soal.id_versi_fk','=',$request->versi],['b_jawaban.jenis_jawaban','!=','nilai_atasan']])
						->count();
						
						if ($TotalJawabanVerif == $counTerjawab) {
							
						}else{
							return Response::json(array( 'gg' => '300'), 200);
						}
				}else{
							// #kn345
							return Response::json(array( 'gg' => 'kly'), 200);
				}
			
		}

		//GOLONGAN REKTOR TIDAK PERLU MENILAI ATASAN YA ITU KETUA YAYASAN
		if (Auth::user()->level != '12') {
			//FORM TIPE YANG DIGUNAKAN UNTUK PENILAIAN ATASAN ADALAH FORM TIPE B
			$NilaiAtasanCek = $this->FormNilaiAtasan('b',$request->versi);

			if ($NilaiAtasanCek <= 0) {
				return response()->json(['gg' => 'NilaiAtasan0'], 200);
			}
		}

		
		//CEK PELAKSANAAN TUGAS LAIN SUDAH DIUPLOAD ATAU BELUM
		if ($this->CekPTLUpload($request->versi, Auth::user()->id, Auth::user()->level) == '1') {
				return Response::json(array( 'gg' => 'PTLKosong'), 200);
		}elseif($this->CekPTLUpload($request->versi, Auth::user()->id, Auth::user()->level) == 'atasan'){}else{}

		$cek_sedia = DB::table('b_status')->where([['id_user','=', Auth::user()->id],['id_versi','=',$request->versi]])->count();

		if ($cek_sedia > 0) {
			return Response::json(array( 'gg' => '3' ), 200);
		}else{
			if ($request->status == 'ubah') {
				DB::table('b_status')->insert(['id_user' => Auth::user()->id,'id_versi' => $request->versi, 'status' => 1,'created_at' => \Carbon\Carbon::now()]);
				return Response::json(array( 'gg' => '1' ), 200);
			}else{
				return Response::json(array(  'gg' => '4' ), 200);
			}
		}
	}

	//SIMPAN JAWABAN DARI SOAL FORM
	public function SimpanJawaban(Request $request){

		for ($i = 0; $i < count($request->input('jumlahdata')); $i++) {

			$cekQuery3[] = [		 	
	        			'id_soal' => $request->input('id_soal'.$i),
	        			'jawaban' =>  $request->input('jawaban'.$i),
	        			'jenis_jawaban' => '1',
						'id_user' => Auth::user()->id,
						'created_at' => \Carbon\Carbon::now(),
		        	];
	        	
		}

		DB::table('b_jawaban')->insert($cekQuery3);
		return Response::json(array(
				                'success' => 'Berhasil',
				                'errors' => false,
				            ), 200);
	}


	//EDIT KONTAK DARURAT
	public function EditKontakDarurat(Request $request){

		try {
			DB::table('b_kontak_darurat')
				->where('id_kontak_darurat' ,'=', $request->id_kontak)
		      	->update([

						"nama_kd" => $request->nama_nodarurat,
						"hubungan_kd" => $request->hubungan_nodarurat,
						"nomor_telepon_kd" => $request->nomor_darurat,
						"kota_kd" =>$request->kota_nodarurat,
						'update_at' => \Carbon\Carbon::now()]);

		      	return Redirect::back()->with('success', 'Berhasil Mengubah Data');
		} catch (Exception $e) {
				return Redirect::back()->with('error', 'Gagal Mengubah Data');
		}
	}

	//TAMBAH ANAK MARITAL
	public function TambahAnakMarital(Request $request){


		try {
			$answer_anaknew[] = [	
									'id_user' => Auth::user()->id,
									'nama_anak' => $request->nama_anak,
									'ttl_anak' => $request->ttl_anak,
									'jenis_kelamin_anak' => $request->jenis_kelamin_anak,
								
									'created_at' => \Carbon\Carbon::now()
							    ];

			DB::table('b_marital')->insert($answer_anaknew);
			return Redirect::back()->with('success', 'Berhasil Menambah Data Anak');

		} catch (Exception $e) {
			return Redirect::back()->with('error', 'Gagal Menambah Data');
		}
	}


	//HAPUS MARITAL ANAK
	public function HapusMaritalAnak($id){
		try {

			DB::table('b_marital')->where('id_marital', '=', $id)->delete();
				return Redirect::back()->with('success', 'Berhasil Hapus Data');

			}catch (Exception $e) {
				return Redirect::back()->with('error', 'Gagal Hapus Data');
			}
	}

	//HAPUS MARITAL PASANGAN
	public function EditMaritalPasangan(Request $request){

		try {
			DB::table('b_marital_pasangan')
				->where('id_maritalpasangan' ,'=', $request->id_maritalpasangan)
		      	->update([
							'nama_pasangan' => $request->nama_pasangan,
	  						'pekerjaan_pasangan' => $request->pekerjaan_pasangan,
	  						'nomor_telepon_pasangan' => $request->nomor_telepon_pasangan,
							'updated_at' => \Carbon\Carbon::now()]);

		      	return Redirect::back()->with('success', 'Berhasil Mengubah Data');
		} catch (Exception $e) {
				return Redirect::back()->with('error', 'Gagal Mengubah Data');
		}
	}

	//HAPUS PERGURUAN TINGGI
	public function HapusPerting($id){

		try {
		DB::table('b_perguruan_tinggi')->where('id_perting', '=', $id)->delete();
			return Redirect::back()->with('success', 'Berhasil Hapus Data Perguruan Tinggi');
		}catch (Exception $e) {
			return Redirect::back()->with('error', 'Gagal Hapus Data');
		}
	}

	//TAMBAH PERGURUAN TINGGI
	public function TambahPerting(Request $request) {

		try {
			$answers_pertingnew[] = [	

									'id_user' => Auth::user()->id,
			        				'nama_sekolah_perting' => $request->nama_sekolah_perting,
			  						'tingkat' => $request->tingkat_perting,
			  						'program_studi' => $request->jurusan_perting,
			  						'ipk' => $request->ipk_perting,
			  						'mulai_pendidikan' => $request->mulai_pendidikan_perting,
			  						'selesai_pendidikan' => $request->selesai_pendidikan_perting,
								
									'created_at' => \Carbon\Carbon::now()
							    ];

			DB::table('b_perguruan_tinggi')->insert($answers_pertingnew);
			return Redirect::back()->with('success', 'Berhasil Menambah Data Perguruan Tinggi');

		} catch (Exception $e) {
			return Redirect::back()->with('error', 'Terjadi Kesalahan Dalam Memproses Data');
		}
	}

	//EDIT PERGURUAN TINGGI
	public function EditPerting(Request $request){

		try {
		DB::table('b_perguruan_tinggi')
			->where('id_perting', $request->id_perting)
	      	->update([
						'nama_sekolah_perting' => $request->nama_sekolah_perting,
  						'tingkat' => $request->tingkat_perting,
  						'program_studi' => $request->jurusan_perting,
  						'ipk' => $request->ipk_perting,
  						'mulai_pendidikan' => $request->mulai_pendidikan_perting,
  						'selesai_pendidikan' => $request->selesai_pendidikan_perting,
					
						'updated_at' => \Carbon\Carbon::now()]);
		
			return Redirect::back()->with('success', 'Berhasil Mengubah Data');

		} catch (Exception $e) {

			return Redirect::back()->with('error', 'Terjadi Kesalahan Dalam Memproses Data');
		}
	} 


	//EDIT SMA SEDERAJAT
	public function EditSmaSederajat(Request $request){

		try {

			DB::table('b_sma_sederajat')
				->where('id_sekolah', $request->id_sma)
              	->update([

              			'nama_sekolah' => $request->nama_sekolah ,
						'jurusan' => $request->jurusan ,
						'mulai_pendidikan' => $request->mulai_pendidikan ,
						'selesai_pendidikan' => $request->selesai_pendidikan ,
						
						'updated_at' => \Carbon\Carbon::now()]);
			

			return Redirect::back()->with('success', 'Berhasil Mengubah Data');

		} catch (Exception $e) {

			return Redirect::back()->with('error', 'Terjadi Kesalahan Dalam Memproses Data');
		}

	}

	//TAMBAH JABATAN AKADEMIK
	public function TambahJabatanAka(Request $request){

		$cek_data = DB::table('b_serdos')->where('id_user' ,'=', Auth::user()->id)->count();
		
		if ($cek_data > 0) {
			return Redirect::back()->with('error', 'Jabatan Akadmik Sudah Ada Sebelumnya, Hapus Data Sebelumnya Terlebih Dahulu');
		}else{

			try {
			$answers_serdosnew[] = [
			        				'id_user' => Auth::user()->id,
			        				'jabatan_akademik' => $request->input('jabatanakademik'),
			        				'serdos' => $request->input('serdos'),
			        				'no_serdos' => $request->input('nomor_regis'),
								  	'created_at' => \Carbon\Carbon::now()
							    ];

			DB::table('b_serdos')->insert($answers_serdosnew);

				return Redirect::back()->with('success', 'Berhasil Menambah Data');

			}catch (Exception $e) {

				return Redirect::back()->with('error', 'Gagal Menambah Data');

			}
		}
	}




	//TAMBAH JABATAN DI PROFIL AKUN
	public function TambahJabatan(Request $request){

		$cek_data = DB::table('b_jabatan')->where([['nama_jabatan','=',$request->jabatanbaru],['id_user' ,'=', Auth::user()->id],['sub_jabatan','=',$request->sub_jabatanbaru]])->count();

		if ($cek_data > 0) {
			return Redirect::back()->with('error', 'Gagal Menambah Data, Jabatan Sudah Ada Sebelumnya');
		}else{

		try {

		
			DB::table('b_jabatan')->insert(['nama_jabatan' => $request->jabatanbaru, 'sub_jabatan' => $request->sub_jabatanbaru,'id_user' => Auth::user()->id]);
				return Redirect::back()->with('success', 'Berhasil Hapus Data');

			}catch (Exception $e) {
				return Redirect::back()->with('error', 'Gagal Hapus Data');
			}
		}

	}

	//HAPUS JBATAN AKADAMIK
	public function HapusJabatanAkademik($id){
		try {

				DB::table('b_serdos')->where('id_serdos', '=', $id)->delete();
					return Redirect::back()->with('success', 'Berhasil Hapus Data');

				}catch (Exception $e) {
					return Redirect::back()->with('error', 'Gagal Hapus Data');
				}

	}

	//HAPUS JABATAN UMUM
	public function hapus_jabatanan_pen($id){

			try {

				DB::table('b_jabatan')->where('id_jabatan', '=', $id)->delete();
				return Redirect::back()->with('success', 'Berhasil Hapus Data');

				}catch (Exception $e) {
					return Redirect::back()->with('error', 'Gagal Hapus Data');
				}

	}



	//EDIT IDENTITAS
	public function EditIdentitas(Request $request){

		try {
			if (is_null($request->iden) == false) {	
				for ($z = 0; $z < count($request->input('iden')); $z++) {

					if (is_null($request->iden[$z])) {
								continue;
						    }
				    $answers_newiden[] = [
							'jenis' => $request->input('iden')[$z],
							'id_user' => Auth::user()->id,
						  	'created_at' => \Carbon\Carbon::now()
					    ];
				    }
			}

				try {
					DB::table('b_identitas_lainnya')->where('id_user', '=', Auth::user()->id)->delete();
					} catch (Exception $e) {
					return Response::json(array(
				                'success' => false,
				                'errors' => 'gagal #lk54345',
				            ), 400);
				}

			if (is_null($request->iden) == false) {		
				DB::table('b_identitas_lainnya')->insert($answers_newiden);
			}
			return Response::json(array(
		                'success' => 'Berhasil',
		                'errors' => false,

		            ), 200);

		} catch (Exception $e) {

			return Response::json(array(
		                'success' => false,
		                'errors' => 'gagal #p34',

		            ), 400);
		}
	}

	//EDIT DATA DIRI
	public function EditDataDiri(Request $request, $id){

			//return Response::json(array('gg' => $request->all(),'errors' => false ), 200);

			if ($request->ikrarvege == 'Ikrar') {
				$ikrartahun_vege = $request->ikrartahun;
				$ikrarvege = $request->ikrarvege;
			}else{
				$ikrartahun_vege = null;
				$ikrarvege = $request->ikrarvege;
			}

			if ($request->QiuDao == 'Iya') {
				$QiuDao = $request->QiuDao;
				$detailqiudao = $request->detailqiudao;
			}else{
				$detailqiudao = null; 
				$QiuDao = $request->QiuDao;
			}

			$DataUpdate = ['agama' => $request->agama ,
						'alamat_sekarang' => $request->alamat_sekarang ,
						'durasi_ktp' => $request->durasi_ktp ,
						'email' => $request->email ,
						'golongan_darah' => $request->golongan_darah ,
						'jenis_kelamin' => $request->jenis_kelamin ,
						'kota_lahir' => $request->kota_lahir ,
						'nama_lengkap' => $request->nama_lengkap ,
						'nama_mandarin' => $request->nama_mandarin ,
						'nomor_ktp' => $request->nomor_ktp ,
						'nomor_npwp' => $request->nomor_npwp ,
						'nomor_telepon' => $request->nomor_telepon ,
						'nomor_telepon_2' => $request->nomor_telepon_2 ,
						'nomor_wa'=> $request->nomor_wa ,
						'provinsi_lahir' => $request->provinsi_lahir ,
						'status_tempat_tinggal' => $request->kepemilikan_tempat_tinggal ,
						'tanggal_lahir' => $request->tanggal_lahir, 
						'vege' => $ikrarvege, 
						'ikrartahun_vege' => $ikrartahun_vege, 
						'qiudao' => $QiuDao, 
						'jenis_qiudao' => $detailqiudao, 
						'suku' => $request->suku, 
						
						'kecamatan_domisili' => $request->kecamatan_domisili,
						'kelurahan_domisili' => $request->KelurahanDomisili,
						'rt_domisili' => $request->rt_domisili,
						'rw_domisili' => $request->rw_domisili,
						'no_bpjs_kesehatan' => $request->nomor_bpjs_kes,
						'no_bpjs_ketenagakerjaan' => $request->nomor_bpjs_tenker,
						'updated_at' => \Carbon\Carbon::now()];


			$UpdateCek = DB::table('b_data_diri')->where('id_data_diri', $id)->update($DataUpdate);
			
      if ($UpdateCek) {
      	return Response::json(array('gg' => 'berhasil','errors' => false ), 200);
      }else{
      	return Response::json(array('gg' => 'gagal','errors' => false ), 200);
      }

	}

	//SIMPAN DATA DIRI
	public function SimpanDataDiri(Request $request){

		//return Response::json(array( 'success' => $request->all() ), 200);

		$c_datadiri = DB::table('b_data_diri')->where('id_user','=',Auth::user()->id)->count();

		if ($c_datadiri > 0) {
				 return Response::json(array(
                'success' => false,
                'errors' => 'terjadi kesalahan #jnsof',

            ), 400);
		}
	

		try {

			if ($request->ikrarvege == 'Ikrar') {
				$ikrartahun_vege = $request->ikrartahun;
				$ikrarvege = $request->ikrarvege;
			}else{
				$ikrartahun_vege = null;
				$ikrarvege = $request->ikrarvege;
			}

			if ($request->QiuDao == 'Iya') {
				$QiuDao = $request->QiuDao;
				$detailqiudao = $request->detailqiudao;
			}else{
				$detailqiudao = null;
				$QiuDao = $request->QiuDao;
			}

		   	if (is_null($request->nama_lengkap) == false) {
			//INSERT DATADIRI	
			$answer_datadiri[]	=	['id_user' => Auth::user()->id,
														  'agama' => $request->agama ,
															'alamat_sekarang' => $request->alamat_sekarang ,
															'durasi_ktp' => $request->durasi_ktp ,
															'email' => $request->email ,
															'golongan_darah' => $request->golongan_darah ,
															'jenis_kelamin' => $request->jenis_kelamin ,
															'kota_lahir' => $request->kota_lahir ,
															'nama_lengkap' => $request->nama_lengkap ,
															'nama_mandarin' => $request->nama_mandarin ,
															'nomor_ktp' => $request->nomor_ktp ,
															'nomor_npwp' => $request->nomor_npwp ,
															'nomor_telepon' => $request->nomor_telepon ,
															'nomor_telepon_2' => $request->nomor_telepon_2 ,
															'nomor_wa'=> $request->nomor_wa ,
															'provinsi_lahir' => $request->provinsi_lahir ,
															'status_marital' => $request->status_marital ,
															'status_tempat_tinggal' => $request->status_tempat_tinggal ,
															'tanggal_lahir' => $request->tanggal_lahir, 
															'vege' => $ikrarvege, 
															'ikrartahun_vege' => $ikrartahun_vege, 
															'qiudao' => $QiuDao, 
															'jenis_qiudao' => $detailqiudao, 
															'suku' => $request->suku, 
															'kecamatan_domisili' => $request->kecamatan_domisili,
															'kelurahan_domisili' => $request->KelurahanDomisili,
															'no_bpjs_kesehatan' => $request->nomor_bpjs_kes,
															'no_bpjs_ketenagakerjaan' => $request->nomor_bpjs_tenker,
															'rt_domisili' => $request->rt_domisili,
															'rw_domisili' => $request->rw_domisili,
															'created_at' => \Carbon\Carbon::now()];
			}


			if (is_null($request->jenis_iden) == false) {										
			//INSERT JENIS IDENTITAS
			for ($i = 0; $i < count($request->input('jenis_iden')); $i++) {

						        $answers_iden[] = [
				        				'jenis' => $request->input('jenis_iden')[$i],
										'id_user' => Auth::user()->id,
									  	'created_at' => \Carbon\Carbon::now()
								    ];
							    }
			}



			if (is_null($request->nama_anak) == false) {

				for ($a = 0; $a < count($request->input('nama_anak')); $a++) {

			        $answers_marital[] = [
	        				'nama_anak' => $request->input('nama_anak')[$a],
	        				'ttl_anak' => $request->input('ttl_anak')[$a],
	        				'jenis_kelamin_anak' => $request->input('jenis_kelamin_anak')[$a],
							'id_user' => Auth::user()->id,
						  	'created_at' => \Carbon\Carbon::now()
					    ];
				    }
			}

			if ($request->status_marital == 'Menikah') {

		        $answers_maritalpasangan[] = [
        				'nama_pasangan' => $request->input('nama_pasangan'),
        				'pekerjaan_pasangan' => $request->input('pekerjaan_pasangan'),
        				'nomor_telepon_pasangan' => $request->input('nomor_telepon_pasangan'),
						'id_user' => Auth::user()->id,
					  	'created_at' => \Carbon\Carbon::now()
				    ];
			}

			if (is_null($request->nama_nodarurat) == false) {	
	
				for ($kd = 0; $kd < count($request->input('nama_nodarurat')); $kd++) {
						if (is_null($request->nama_nodarurat[$kd])) {
							continue;
					    }
				        $answer_kontakdarurat[] = [
		        				'id_user' => Auth::user()->id,
							   	'nama_kd' => $request->nama_nodarurat[$kd],
							   	'hubungan_kd' => $request->hubungan_nodarurat[$kd],
							   	'nomor_telepon_kd' => $request->nomor_darurat[$kd],
							   	'kota_kd' => $request->kota_nodarurat[$kd],
									
								'created_at' => \Carbon\Carbon::now()
						    ];
					    }
			}


			if (is_null($request->nama_sekolah) == false) {	
			//INSERT PENDIDIKAN SEKOLAH MEMENGAH ATAS(SEDERAJAT)
			$answer_sma[]	=	[
									'id_user' => Auth::user()->id,
								   	'nama_sekolah' => $request->nama_sekolah,
								   	'jurusan' => $request->jurusan,
								   	'mulai_pendidikan' => $request->mulai_pendidikan,
								   	'selesai_pendidikan' => $request->selesai_pendidikan,
										
									'created_at' => \Carbon\Carbon::now()
									];
			}

			if (is_null($request->nama_sekolah_perting) == false) {						
			//INSERT PENDIDIKAN PERGURUAN TINGGI
			for ($b = 0; $b < count($request->input('nama_sekolah_perting')); $b++) {

						        $answers_perting[] = [
				        				'id_user' => Auth::user()->id,
				        				'nama_sekolah_perting' => $request->input('nama_sekolah_perting')[$b],
				        				'tingkat' => $request->input('tingkat_perting')[$b],
				        				'program_studi' => $request->input('jurusan_perting')[$b],
				        				'ipk' => $request->input('ipk_perting')[$b],
				        				'mulai_pendidikan' => $request->input('mulai_pendidikan_perting')[$b],
										'selesai_pendidikan' => $request->input('selesai_pendidikan_perting')[$b],
									  	'created_at' => \Carbon\Carbon::now()
								    ];
							    }
			}



			//8 TABEL	
			if (is_null($request->nama_lengkap) == false) {  
				DB::table('b_data_diri')->insert($answer_datadiri);
			}
			if (is_null($request->nama_nodarurat) == false) {	
				DB::table('b_kontak_darurat')->insert($answer_kontakdarurat);
			}
			if (is_null($request->nama_sekolah) == false) {	
				DB::table('b_sma_sederajat')->insert($answer_sma);
			}
			if (is_null($request->jenis_iden) == false) {
				DB::table('b_identitas_lainnya')->insert($answers_iden);
			}
			if (is_null($request->nama_anak) == false) {
			  	DB::table('b_marital')->insert($answers_marital);
			}	
			if ($request->status_marital == 'Menikah') {
				DB::table('b_marital_pasangan')->insert($answers_maritalpasangan);
			}	
			if (is_null($request->nama_sekolah_perting) == false) {
				DB::table('b_perguruan_tinggi')->insert($answers_perting);
			}
			

	    } catch (Exception $e) {
	        return Response::json(array(
                'success' => false,
                'errors' => $e,

            ), 400);
	    }
	}

	//CEK JAWABAN UNTUK NILAI ATASAN
	protected function FormNilaiAtasan($tipe_form, $versi){
		 	$cek_jawaban = DB::table('b_jawaban')
        ->join('b_soal','b_jawaban.id_soal','=','b_soal.id_soal')
        ->select('b_soal.kategori_soal','b_jawaban.id_user','b_jawaban.jawaban','b_jawaban.id_soal')
        ->where([['id_user','=',Auth::user()->id], ['kategori_soal','=',$tipe_form],['b_soal.id_versi_fk','=',$versi],['b_jawaban.jenis_jawaban','=','nilai_atasan']])->count();
        return $cek_jawaban;
	}
	//CEK JAWABAN UNTUK DIRI SENDIRI
	protected function form_type($tipe_form, $versi){
        $cek_jawaban = DB::table('b_jawaban')
        ->join('b_soal','b_jawaban.id_soal','=','b_soal.id_soal')
        ->select('b_soal.kategori_soal','b_jawaban.id_user','b_jawaban.jawaban','b_jawaban.id_soal')
        ->where([['id_user','=',Auth::user()->id], ['kategori_soal','=',$tipe_form],['b_soal.id_versi_fk','=',$versi],['b_jawaban.jenis_jawaban','!=','nilai_atasan']])->count();
        return $cek_jawaban;
  }
   
    protected function cek_akses($aModul) {

        $level = Auth::user()->level;
        $username = Auth::user()->username;
        //query untuk mendapatkan iduser dari user           

        $quser = DB::table('users')->select('level')->where('username','=',$username)->first();
        $qry = DB::table('hak_akses')->select('id')->where([['usergroup','=',$quser->level],['modul','=',$aModul]])->count();

        if (1 > $qry) {
            return "no";
        } else {
            return "yes";
        }

    }

}
