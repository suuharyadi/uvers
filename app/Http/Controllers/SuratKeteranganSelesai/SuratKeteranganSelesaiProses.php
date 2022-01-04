<?php

namespace App\Http\Controllers\SuratKeteranganSelesai;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Encryption\DecryptException;

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


class SuratKeteranganSelesaiProses extends Controller
{	

	public function GetDoJson(Request $request){
		if ($request->jenis == 'mhs') {
			$mhs    = DB::table('a_tbl_mhs')->orderBy('nama','ASC')->get();
			return Response::json(array('mhs' =>$mhs), 200);
		}else if($request->jenis == 'dosen'){
			$dosen = DB::table('pegawai')->get();
			return Response::json(array('dosen' =>$dosen), 200);
		}else if($request->jenis == 'srttgs'){

			$surat_tugas = DB::table('a_srt_tgs_pembimbing')
                        ->join('a_tbl_mhs','a_tbl_mhs.id_mhs','=','a_srt_tgs_pembimbing.id_mhs')
                        ->join('a_nama_mk','a_nama_mk.id_mk','=','a_srt_tgs_pembimbing.id_mk')
                        ->select('a_srt_tgs_pembimbing.no_surat','a_srt_tgs_pembimbing.id','a_tbl_mhs.nama','a_tbl_mhs.nim','a_nama_mk.nama_mk')
                        ->where('a_nama_mk.jenis_mk','!=','Pembimbing')

                        ->orderBy('a_srt_tgs_pembimbing.no_surat','DESC')->get();

			return Response::json(array('srttgs' =>$surat_tugas), 200);
		}else{
			return Response::json(array('error' => true), 400);
		}
	}
	
	public function PrintExcelSks(Request $request){

		if ($request->jenis_mk == 'magang') {

			$CekSks = 	DB::table('a_surat_keterangan_selesai')
					->join('a_tbl_mhs','a_tbl_mhs.id_mhs','=','a_surat_keterangan_selesai.mahasiswa')
					->join('a_prodi','a_prodi.id_prodi','=','a_surat_keterangan_selesai.prodi')
					->join('a_nama_mk','a_nama_mk.id_mk','=','a_surat_keterangan_selesai.nama_mk')
					->leftJoin('a_sks_dp','a_sks_dp.id_sks_selesai','=','a_surat_keterangan_selesai.id_sks')
					->leftJoin('a_srt_tgs_pembimbing','a_srt_tgs_pembimbing.id','=','a_sks_dp.surat_tugas')
					->join('pegawai','a_sks_dp.id_dosen','=','pegawai.id_pegawai')
					->select(	'a_surat_keterangan_selesai.*',
								'a_tbl_mhs.nim',
								'a_tbl_mhs.nama',
								'a_prodi.nama_prodi',
								'a_nama_mk.nama_mk',
								'pegawai.id_pegawai',
								'pegawai.nama_karyawan',
								'pegawai.nidn',
								'a_srt_tgs_pembimbing.no_surat'
							)
					->where([['a_nama_mk.jenis_mk','=','Magang'],['a_surat_keterangan_selesai.tahun_ajar','=',$request->thn_ajar]])
					->orderBy('id_sks','DESC')
					->get();

        return view('admin.dashboard.suratketeranganselesai.ToExcel.CetakSks',['CekSks' => $CekSks,'jenis_mk' => $request->jenis_mk]);

		}else{

			
			$CekSks = 	DB::table('a_surat_keterangan_selesai')
					->join('a_tbl_mhs','a_tbl_mhs.id_mhs','=','a_surat_keterangan_selesai.mahasiswa')
					->join('a_prodi','a_prodi.id_prodi','=','a_surat_keterangan_selesai.prodi')
					->join('a_nama_mk','a_nama_mk.id_mk','=','a_surat_keterangan_selesai.nama_mk')
					->select(	'a_surat_keterangan_selesai.*',
								'a_tbl_mhs.nim',
								'a_tbl_mhs.nama',
								'a_prodi.nama_prodi',
								'a_nama_mk.nama_mk'
							)
					->where([['a_nama_mk.jenis_mk','!=','Magang'],['a_surat_keterangan_selesai.tahun_ajar','=',$request->thn_ajar]])
					->orderBy('id_sks','DESC')
					->get();

        return view('admin.dashboard.suratketeranganselesai.ToExcel.CetakSks',['CekSks' => $CekSks,'jenis_mk' => $request->jenis_mk]);

		}
	}

	 //GET DATA UNDANGAN 2.0
    public function GetDataUndangan(Request $request){

    	
    	if (is_null($request->id_surat)) {
    		return Response::json(array( 'kosong' => 'ininull' ), 200);

    	}else{
    	

			$cekSurat = DB::table('a_srt_tgs_pembimbing')
						->join('a_tbl_mhs','a_tbl_mhs.id_mhs','=','a_srt_tgs_pembimbing.id_mhs')
						->join('a_prodi','a_prodi.id_prodi','=','a_srt_tgs_pembimbing.id_prodi')
						->join('a_nama_mk','a_nama_mk.id_mk','=','a_srt_tgs_pembimbing.id_mk')
						->select('a_tbl_mhs.nama','a_tbl_mhs.nim','a_tbl_mhs.id_mhs',
								 'a_srt_tgs_pembimbing.no_surat',
								 'a_srt_tgs_pembimbing.id_udg',
								 'a_srt_tgs_pembimbing.id',
								 'a_srt_tgs_pembimbing.semester',
								 'a_srt_tgs_pembimbing.tahun_ajar',

								 'a_nama_mk.jenis_mk',
								 'a_nama_mk.id_mk',

								 'a_prodi.nama_prodi','a_prodi.id_prodi')
						->where('a_srt_tgs_pembimbing.id','=',$request->id_surat)
						->first();

			if (is_null($cekSurat)) {
				return Response::json(array( 'kosong' => '1' ), 200);
			}else{

				if ($cekSurat->jenis_mk == 'Magang') {

					$cek_dospem = DB::table('a_srt_tgs_pembimbing')
						->join('pegawai','pegawai.id_pegawai','=','a_srt_tgs_pembimbing.id_dosen')
						->select('a_srt_tgs_pembimbing.id_dosen')
						->where('a_srt_tgs_pembimbing.id','=',$cekSurat->id)
						->get();

					$button=[];	
					foreach($cek_dospem as $key ){
	        			$button[] = $key->id_dosen;
					}

					return Response::json(array('magang' => $cekSurat,'dospem' => $button), 200);


				}else if ($cekSurat->jenis_mk == 'Penguji') {
					
					$judul = DB::table('a_srt_udg_penguji')
						->select('a_srt_udg_penguji.judul')
						->where('a_srt_udg_penguji.id_undangan','=',$cekSurat->id_udg)
						->first();

					$cek_dospem = DB::table('a_srt_udg_penguji')
						->join('a_udg_dp','a_udg_dp.id_udg','=','a_srt_udg_penguji.id_undangan')
						->join('pegawai','pegawai.id_pegawai','=','a_udg_dp.id_dosen')
						->select('a_srt_udg_penguji.id_undangan','a_udg_dp.id_dosen')
						->where([['a_srt_udg_penguji.id_undangan','=',$cekSurat->id_udg],['a_udg_dp.kategori_dosen','=','Pembimbing']])
						->orderBy('a_udg_dp.id','ASC')
						->get();

					$button=[];	
					foreach($cek_dospem as $key )
	        			$button[] = $key->id_dosen;

					return Response::json(array('ta' => $cekSurat,'judul' => $judul->judul,'dospem' => $button), 200);
				}
	        }
    
	    }

    }
	
	//Preview Berkas Upload dari bagian Surat Keterangan Selesai
	public function preview_berkas_scan_sks($id, $kategori){

        $ceknmfile = DB::table('a_berkas_scan_buff')->select('*')
        ->where([['id_data_buff', '=', $id],['kategori_buff','=',$kategori]])
        ->first(); 

        if(is_null($ceknmfile)){

        	abort(404);

        }else{

	        $file= public_path(). "/berkas_scan/".$ceknmfile->kategori_buff."/".$ceknmfile->id_data_buff."/".$ceknmfile->file_name.'.'.$ceknmfile->file_type;

	        return response()->file($file);
	    }

	}

	//Destroy Berkas Upload dari bagian Surat Keterangan Selesai
	public function destroy_file_scan_sks($id, $kategori){


		$ceknmfile = DB::table('a_berkas_scan_buff')->select('*')
	        ->where([['id_data_buff', '=', $id],['kategori_buff','=',$kategori]])
	        ->first(); 
	        
	    if(is_null($ceknmfile)){

        	abort(404);

        }else{

		    $cek_berkas= public_path(). "/berkas_scan/".$ceknmfile->kategori_buff."/".$ceknmfile->id_data_buff."/".$ceknmfile->file_name.'.'.$ceknmfile->file_type;


		    if ($cek_berkas) {

				 try {

	        		File::delete($cek_berkas);

	        		$cek = DB::table('a_berkas_scan_buff')
	        		->where([['id_data_buff', '=', $id],['kategori_buff','=',$kategori]])
		        	->delete();

			       	return Redirect::back()->with('success', 'File Sudah Dihapus');

			    } catch (Exception $e) {

			       	return Redirect::back()->with('error', 'Terjadi Kesalahan #lkngo3');

			    }


		    }else{
		    	return Redirect::back()->with('error', 'Terjadi Kesalahan');
		    }
		}
    }    

	//post dosen pembimbing 
    public function tammbahdospem(Request $request){

    	$cek =  DB::table('a_sks_dp')->latest('id', 'no_surat')->first();

		if (empty($cek)) {
			$rtz = '0';
		}else{
			$pisah = explode( '/', $cek->no_surat);
			$rtz = $pisah[0];
		}

    	$ceklama = DB::table('a_sks_dp')->where([['id_dosen','=', $request->id_penguji],['id_sks_selesai','=',$request->id_sks]])->select('id_penguji')->count();

  	
        if ($ceklama > 0) {
        	return Response::json(array(
	                'success' => false,
	                'errors' => 'Dosen Sudah Ada',

	            ), 400);

        }else{

    		$result = DB::table('a_sks_dp')
            ->insert(
            	[
            		'id_sks_selesai' => $request->id_sks,
	            	'no_surat'=> sprintf("%03s", $rtz + 1).$this->nosurattugas(),
	            	'kategori_dosen'=> 'Pembimbing', 
	            	'id_dosen' => $request->id_penguji, 
	            	'created_at' => \Carbon\Carbon::now()
            	]);

	        if ($result) {
        	 	return Response::json(array(
	                'success' => 'Berhasil',
	                'errors' => false,

	            ), 200);
	        }else{
	        	return Response::json(array(
		                'success' => false,
		                'errors' => 'Gagal Menyimpan Data',

		            ), 400);

	        }
	    }

    }

    //destroy dosen pembimbing surat keterangan selesai
	public function destroydospensks($idkey){

		$cek = DB::table('a_sks_dp')->where('id', '=', $idkey)->delete();

		if ($cek) {
			return Response::json(array( 
	                'success' => 'Berhasil',
	                'errors' => false,
	            ), 200);
		}else{
			
		}
	}

	//Tambah Surat Surat Keterangan Selesai pembimbing VERSI 3
	public function PostSksV3(Request $request){

		$cek =  DB::table('a_sks_dp')->latest('id', 'no_surat')->first();


		if (empty($cek)) {
			$rtz = '0';
		}else{
			$pisah = explode( '/', $cek->no_surat);
			$rtz = $pisah[0];
		}

		
		$result = 	DB::table('a_surat_keterangan_selesai')
	    			->insert(
	        			[	'nama_mk' => $request->id_mk,
	        				'mahasiswa' => $request->id_mhs, 
	        				'prodi' => $request->id_prodi,
	        				'judul' => $request->judul,
	        				'lokasi' => $request->lokasi,
	        				'mulai' => $request->tanggal_pelaksanaan_mulai,
	        				'selesai' => $request->tanggal_pelaksanaan_selesai,
	        				'tahun_ajar' => $request->tahun_ajar,
	        				'semester' => $request->ganjilgenap,

	        				'created_at' => \Carbon\Carbon::now()
	        			]);

	   	if ($result) {

	   		$Lastid = DB::getPdo()->lastInsertId();

	   		if ($request->input('id_pembimbing')) {

	   			for ($i = 0; $i < count($request->input('id_pembimbing')); $i++) {

	   			$i_ns = $i + 1;

		        $answers2[] = [
		        	'id_sks_selesai' => $Lastid,
		        	'surat_tugas' => $request->input('IdSuratTugas'),
		        	'no_surat' => sprintf("%03s", $rtz + $i_ns).$this->nosurattugas(), 
		        	'kategori_dosen' => 'Pembimbing',
		        	'id_dosen' => $request->input('id_pembimbing')[$i],
		        	'created_at' => \Carbon\Carbon::now()
				    ];
			    }
			    
			    try {
		              $cekQuery2 = DB::table('a_sks_dp')->insert($answers2);
		            } catch (Exception $e) {
		                report($e);
		                return Response::json(array(
		                                    'success' => false,
		                                    'errors' => 'Terjadi Kesalahan #ljfkl',
		                                ), 400);
		            }
	   		}
	   	}else{
	   		return Response::json(array(
		                'success' => false,
		                'errors' => 'Terjadi Kesalahan Saat Memproses Data',
		            ), 400);
	   	}
	}


	//Tambah Surat Keterangan Selesai Versi 2
	public function posttambahskscopy(Request $request){

		$cek =  DB::table('a_sks_dp')->latest('id', 'no_surat')->first();

		if (empty($cek)) {
			$rtz = '0';
		}else{
			$pisah = explode( '/', $cek->no_surat);
			$rtz = $pisah[0];
		}

        $nosurat = 1;
   		for ($i = 0; $i < count($request->input('jumlah')); $i++) {

   			for ($a = 0; $a < count($request->input('id_mhs'.$i)); $a++) {


   				$cekQuery3 = DB::table('a_surat_keterangan_selesai')->insert(
   					[		 	
		        			'mahasiswa' => $request->input('id_mhs'.$i)[$a],
		        			'judul' => $request->input('judul'.$i)[$a],
		        			'selesai' => $request->input('tanggal_pelaksanaan_selesai'.$i)[$a],
		        			'lokasi' => $request->input('lokasi'.$i)[$a],

		        			'nama_mk' => $request->id_mk,
							'prodi' => $request->id_prodi,
							'mulai' => $request->tanggal_pelaksanaan_mulai,
							'tahun_ajar' => $request->tahun_ajar,
							'semester' => $request->ganjilgenap,
							'created_at' => \Carbon\Carbon::now(),
			        ]
   				);


				$Lastid = DB::getPdo()->lastInsertId();


			   	for ($b = 0; $b < count($request->input('id_pembimbing'.$i)); $b++) {

			   		$cekQuery2 = DB::table('a_sks_dp')->insert([
			   						//'mahasiswa' => $request->input('id_mhs'.$i)[$a],
			   						'id_dosen' => $request->input('id_pembimbing'.$i)[$b],



			   						'id_sks_selesai' => $Lastid,
						        	'no_surat' => sprintf("%03s", $rtz + $nosurat).$this->nosurattugas(), 
						        	'kategori_dosen' => 'Pembimbing',
						        	'created_at' => \Carbon\Carbon::now()

			   					]);
			   	$nosurat++;

			   	}
   			}
	    }	
	    
			
	    

	    return Response::json(array(
	                                'success' => $nosurat,
	                                'errors' => false,
	                            ), 200);

	 
	}



	//Tambah Surat Surat Keterangan Selesai pembimbing
	public function posttambahsks(Request $request){

		$cek =  DB::table('a_sks_dp')->latest('id', 'no_surat')->first();


		if (empty($cek)) {
			$rtz = '0';
		}else{
			$pisah = explode( '/', $cek->no_surat);
			$rtz = $pisah[0];
		}

		
		$result = 	DB::table('a_surat_keterangan_selesai')
	    			->insert(
	        			[	'nama_mk' => $request->id_mk,
	        				'mahasiswa' => $request->id_mhs, 
	        				'prodi' => $request->id_prodi,
	        				'judul' => $request->judul,
	        				'lokasi' => $request->lokasi,
	        				'mulai' => $request->tanggal_pelaksanaan_mulai,
	        				'selesai' => $request->tanggal_pelaksanaan_selesai,
	        				'tahun_ajar' => $request->tahun_ajar,
	        				'semester' => $request->ganjilgenap,

	        				'created_at' => \Carbon\Carbon::now()
	        			]);

	   	if ($result) {

	   		$Lastid = DB::getPdo()->lastInsertId();

	   		if ($request->input('id_pembimbing')) {

	   			for ($i = 0; $i < count($request->input('id_pembimbing')); $i++) {

	   			$i_ns = $i + 1;

		        $answers2[] = [
		        	'id_sks_selesai' => $Lastid,
		        	'no_surat' => sprintf("%03s", $rtz + $i_ns).$this->nosurattugas(), 
		        	'kategori_dosen' => 'Pembimbing',
		        	'id_dosen' => $request->input('id_pembimbing')[$i],
		        	'created_at' => \Carbon\Carbon::now()
				    ];
			    }

			    
			    try {

		              $cekQuery2 = DB::table('a_sks_dp')->insert($answers2);

		            } catch (Exception $e) {
		                report($e);
		                return Response::json(array(
		                                    'success' => false,
		                                    'errors' => 'Terjadi Kesalahan #ljfkl',
		                                ), 400);
		            }

	   		}


	   	}else{

	   		return Response::json(array(
		                'success' => false,
		                'errors' => 'Terjadi Kesalahan Saat Memproses Data',
		            ), 400);

	   	}
	}


	//edit proses surat keterangan selesai
	public function proseseditsks($id, Request $request){

		$cek =	DB::table('a_surat_keterangan_selesai')
	            ->where('id_sks','=', $id)
	            ->update(
	            	[
	            		'mahasiswa' => $request->id_mhs,
	            		'judul' => $request->judul,
	            		'tahun_ajar' => $request->tahun_ajar,
	            		'semester' => $request->ganjilgenap,
	            		'prodi' => $request->id_prodi,
	            		'lokasi' => $request->lokasi,
	            		'nama_mk' => $request->id_mk,
	            		'mulai' => $request->tanggal_pelaksanaan_mulai,
	            		'selesai' => $request->tanggal_pelaksanaan_selesai,
	            		'updated_at' =>  \Carbon\Carbon::now()
	            	]
	            );

	    if ($cek) {
	    	return Response::json(array(
			                'success' => 'Berhasil Mengubah Data',
				                'errors' => false,
				            ), 200);
		    }else{
		    	return Response::json(array(
				                'success' => false,
				                'errors' => 'Terjadi Kesalahan #l34h34',
				            ), 400);
		    }


	}

	//hapus surat ketrangan selesai dan dospen didalamnya
    public function destroy_sks($id){

    	if($this->cek_akses('62') == 'yes'){

	    	$cek = DB::table('a_berkas_scan_buff')->select('id_berkas_buff')
	        ->where([['id_data_buff', '=', $id],['kategori_buff','=','surat_keterangan_selesai']])
	        ->count();

		    if ($cek > 0) {
		    return Redirect::back()->with('error', 'Gagal Hapus File, Harap Hapus File Scan Yang Sudah Diupload Terlebih Dahulu');
		    }else{

			    $nrd = DB::delete("delete from a_surat_keterangan_selesai where id_sks = '$id'");

		        if ($nrd) {

		        	$nrd2 = DB::delete("delete from a_sks_dp where id_sks_selesai = '$id'");

		            return Redirect::back()->with('success', 'Berhasil Mengapus Data');

		        }else{

		            return Redirect::back()->with('error', 'Gagal Menghapus Data');
		        }

		    }

		}else{ 

			return Redirect::back()->with('error', 'Gagal Hapus File, Anda Tidak Memiliki Akses');

		}  

   
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

    //update data dosen pembimbing
    public function editdospemsks(Request $request){


		 if (is_null($request->tipe_surat)) {

		    	$cekdulu = DB::table('a_sks_dp')
		    	->select('id_dosen','id_sks_selesai')
		    	->where([
				    		['id_sks_selesai','=',$request->id_sks],
				    		['kategori_dosen', '=','Pembimbing'],
				    		['id_dosen','=',$request->id_pembimbing_ed]
				    	])
		    	->count();

		    	if ($cekdulu > 0) {
		    		return Response::json(array(
					                'success' => false,
						            'errors' => 'Dosen Sudah Ada !',
						            ), 400);
		    	}else{

		    		$cek =	DB::table('a_sks_dp')
			            ->where('id','=', $request->id_sks_eddos)
			            ->update(
			            	[
			            		'id_dosen' => $request->id_pembimbing_ed,
			            		'updated_at' =>  \Carbon\Carbon::now()
			            	]
			            );

				    if ($cek) {
				    	return Response::json(array(
						                'success' => 'Berhasil Merubah Data',
							            'errors' => false,
							            ), 200);
				    }else{
				    	return Response::json(array(
						                'success' => false,
							            'errors' => 'Terjadi Kesalahan #ogneo34',
							            ), 400);
				    }

		    	}


    	}else{

    			$cek2 =	DB::table('a_sks_dp')
		            ->where('id','=', $request->ideddosnosu)
		            ->update(
		            	[
		            		'no_surat' => $request->no_surat,
		            		'updated_at' =>  \Carbon\Carbon::now()
		            	]
		            );
		        return Response::json(array(
						                'success' => 'Berhasil Merubah Data',
							            'errors' => false,
							            ), 200);

			  
    		}



    }

    
	//setup No Surat keerangan selesai
	protected function nosurattugas(){

        $bulan = date('n');
        $endtahun = $tahun = date('Y');
        $nomor = "/AK/KETD/".$this->getRomawi($bulan)."/".$endtahun;

        return $nomor;
    }

    //Setup No Surat bagian konvert romawi
    protected function getRomawi($bln){
        switch ($bln){
        case 1: 
            return "I";
        break;
        case 2:
            return "II";
        break;
        case 3:
            return "III";
        break;
        case 4:
            return "IV";
        break;
        case 5:
            return "V";
        break;
        case 6:
            return "VI";
        break;
        case 7:
            return "VII";
        break;
        case 8:
            return "VIII";
        break;
        case 9:
            return "IX";
        break;
        case 10:
            return "X";
        break;
        case 11:
            return "XI";
        break;
        case 12:
            return "XII";
        break;
        }
    }


}
