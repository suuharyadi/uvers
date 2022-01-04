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
 

class SuratKeteranganSelesai extends Controller
{
   	
   	//index untuk menampilkan surat keterangan selesai
	public function IndexSuratKeteranganSelesai(){
            
        $ThnAjar = DB::table('a_surat_keterangan_selesai')->select('tahun_ajar')->groupBy('tahun_ajar')->orderBy('tahun_ajar','DESC')->get();
   
    	return view('admin.dashboard.suratketeranganselesai.index',['ThnAjar' => $ThnAjar]);

    }

    //get data untuk surat keterangan selesai yang ditampikan di index surat keterangan selesai
    public function getdataindexsks(){

        return DataTables::of(DB::table('a_surat_keterangan_selesai')
        ->join('a_prodi', 'a_prodi.id_prodi', '=', 'a_surat_keterangan_selesai.prodi') 
        ->join('a_nama_mk', 'a_nama_mk.id_mk', '=', 'a_surat_keterangan_selesai.nama_mk') 
        ->join('a_tbl_mhs', 'a_tbl_mhs.id_mhs', '=', 'a_surat_keterangan_selesai.mahasiswa')
        //->leftJoin('a_berkas_scan_buff', 'a_berkas_scan_buff.id_data_buff', '=', 'a_surat_keterangan_selesai.id_sks') 

        ->select(   
                    'a_surat_keterangan_selesai.id_sks',
                    'a_surat_keterangan_selesai.judul', 
                    'a_surat_keterangan_selesai.tahun_ajar', 
                    'a_surat_keterangan_selesai.mulai', 
                    'a_surat_keterangan_selesai.selesai',
                    'a_surat_keterangan_selesai.semester',
                    'a_surat_keterangan_selesai.lokasi',
                    'a_prodi.nama_prodi',
                    'a_nama_mk.nama_mk',
                    'a_nama_mk.jenis_mk',
                    'a_tbl_mhs.nama'
                    //'a_berkas_scan_buff.kategori_buff',
                    //'a_berkas_scan_buff.id_data_buff'
                )
        //kondisi dimana yang tampil adalah hanya surat keterangan selesai, 
        //karena untuk status upload, dia tergabung dengan surat undangan di table a_berkas_scan_upload
        //jika terdapat id sama di table tersebut, maka akan menampilkan kesemua data termaksud table undangan
        //makanya dibuat kondisi dibawah
        //->where('a_berkas_scan_buff.kategori_buff','=','surat_keterangan_selesai')
        //->orderBy('a_surat_keterangan_selesai.id_sks','DESC')
        )
        ->addIndexColumn()

         ->addColumn('pembimbing', function($data){
            
                        $cek = DB::table('a_sks_dp')->select('*')->where([['id_sks_selesai','=',$data->id_sks],['kategori_dosen', '=','Pembimbing']])->count();

                        if ($cek == 0) {
                        return '<a href="'.Route('index_dospem_sks',['id_sks' => $data->id_sks]).'"><span class="badge badge-danger">'.$cek.' pembimbing | <span class="fa fa-mouse-pointer"></span></span></a>';
                        }else{
                        return '<a href="'.Route('index_dospem_sks',['id_sks' => $data->id_sks]).'"><span class="badge badge-success">'.$cek.' pembimbing | <span class="fa fa-mouse-pointer"></span></span></a>';
                        }
                 })
           ->addColumn('action', function($data){

                        $button = '';
                        if (Auth::user()->id != '142') {
                        $button .= '<a href="'.Route('showformedit-sks',['id' => $data->id_sks]).'" title="Edit">
                                    <button type="button" class="btn btn-outline-primary btn-sm"><span class="fa fa-pencil-alt"> </span></button>
                                    </a> | ';

                        $button .= '<a href="'.Route('destroy-sks',['id' => $data->id_sks]).'" onclick="return confirm(\'Apakah Anda Yakin Menghapus Data Ini ? \' ) " title="Hapus">
                                    <button type="button" class="btn btn-outline-danger btn-sm"><span class="fa fa-trash"> </span></button>
                                    </a>';
                        }

                        return $button;

                    })
           ->addColumn('judul_con', function($data){

                        $judul_con = $data->judul;

                        return $judul_con;

                    })


           ->addColumn('preview_berkas', function($data){

                    if($this->cek_akses('64') == 'yes'){
                        $count_berkas_scan = DB::table('a_berkas_scan_buff')
                            ->where([['kategori_buff','=','surat_keterangan_selesai'],['id_data_buff','=',$data->id_sks]])
                            ->count();
                        if ($count_berkas_scan > 0) {
                            $button = '<a class="dropdown-item" target="_blank" href="'.Route('preview_berkas_scan_sks',['id' => $data->id_sks,'kategori' => 'surat_keterangan_selesai']).'"><span class="fa fa-eye"></span> Preview Berkas</a>';
                            return $button;
                        }else{
                            $button = '<a class="dropdown-item"><span class="fa fa-eye"></span> Preview Berkas</a>';
                            return $button;
                        }

                    }else{ 
                        $button = '<a class="dropdown-item"><span class="fa fa-eye"></span> Preview Berkas (Tidak Ada Akses)</a>';
                            return $button;
                    }  
                    })

        ->addColumn('upload_berkas_cek', function($data){

                if($this->cek_akses('64') == 'yes'){
                   $button = '<a class="upload_berkas dropdown-item" data-id="'.$data->id_sks.'" jenis_mk="'.$data->jenis_mk.'" nama_mhs="'.$data->nama.'" style="cursor: pointer;"><span class="fa fa-upload"></span> Upload Berkas</a>';
                   return $button;
                    }else{ 
                        $button = '<a class="dropdown-item"><span class="fa fa-upload"></span> Upload Berkas (Tidak Ada Akses)</a>';
                            return $button;
                    }  
            })

        ->addColumn('destroy_berkas', function($data){

                if($this->cek_akses('64') == 'yes'){


                      $count_berkas_scan = DB::table('a_berkas_scan_buff')
                        ->where([['kategori_buff','=','surat_keterangan_selesai'],['id_data_buff','=',$data->id_sks]])
                        ->count();

                        if ($count_berkas_scan > 0) {
                             $cek_pilihan_print = DB::table('a_berkas_scan_buff')
                            ->select('pilihan_print')
                            ->where([['kategori_buff','=','surat_keterangan_selesai'],['id_data_buff','=',$data->id_sks]])
                            ->first();

                            //untuk cek apakah berkas upload yang sudah, sedang di keranjang honorarium atau tidak (di proses)
                            if ($cek_pilihan_print->pilihan_print == '1') {
                                 $button = '';
                                if (Auth::user()->id != '142') {
                                $button = '';
                                $button .= '<a class="dropdown-item" href="#" onclick="return alert(\'Data ini sedang dalam proses pengerjaan admin KEPEGAWAIAN dan berkas ini tidak bisa dihapus, harap hubungi admin. \' ) "><span class="fa fa-trash"></span> Hapus Berkas</a>';
                                }
                                return $button;
                               
                            }else{
                                $button = '';
                                if (Auth::user()->id != '142') {
                                $button = '';
                                $button .= '<a class="dropdown-item" href="'.Route('destroy_file_scan_sks',['id' => $data->id_sks,'kategori' => 'surat_keterangan_selesai']).'" onclick="return confirm(\'Apakah Anda Yakin Menghapus Berkas Ini ? \' ) "><span class="fa fa-trash"></span> Hapus Berkas</a>';
                                }
                                return $button;

                            }
                        }else{
                            $button = '<a class="dropdown-item"><span class="fa fa-trash"></span> Hapus Berkas</a>';
                            return $button;
                        }
                        }else{
                             $button = '<a class="dropdown-item"><span class="fa fa-trash"></span> Hapus Berkas (Tidak Ada Akses)</a>';
                            return $button;
                        }
                    })

           ->addColumn('status_upload_scan', function($data){

                        $count_berkas_scan = DB::table('a_berkas_scan_buff')
                        ->where([['kategori_buff','=','surat_keterangan_selesai'],['id_data_buff','=',$data->id_sks]])
                        ->count();

                        if ($count_berkas_scan > 0) {
                            $status_upload_scan = '<span class="badge badge-success">Sudah</span>';
                        }elseif($count_berkas_scan <= 0){
                            $status_upload_scan = '<span class="badge badge-warning">Belum</span>';
                        }   

                        return $status_upload_scan;

                    })

           ->addColumn('ns_dosenpen', function($data){

                        $cekdospen_ns = DB::table('a_sks_dp')
                                        ->join('pegawai','pegawai.id_pegawai','=','a_sks_dp.id_dosen')
                                        ->where([['id_sks_selesai','=',$data->id_sks],['kategori_dosen', '=','Pembimbing']])
                                        ->select('no_surat','nama_karyawan')
                                        ->orderBy('a_sks_dp.no_surat','DESC')
                                        ->get();
                        $return = '';              
                        foreach ($cekdospen_ns as $key => $cekvalus) {
                            $return .= '<span class="badge badge-pill badge-warning">'.$cekvalus->no_surat.'</span> | '.$cekvalus->nama_karyawan.'<br>';
                        }
                        return $return;

                    })

        ->rawColumns(['action','pembimbing','judul_con','ns_dosenpen','status_upload_scan','preview_berkas','destroy_berkas','upload_berkas_cek'])
        ->make(true);
                    
    }

	//dosen pembimbing untuk surat keterangan selesai view
	public function show_index_surat_sks_dosen_pembimbing($id){

        $cek =  DB::table('a_sks_dp')
                ->join('pegawai','pegawai.id_pegawai', '=', 'a_sks_dp.id_dosen')
                ->where([['id_sks_selesai','=',$id],['kategori_dosen', '=','Pembimbing']])
                ->orderBy('id', 'ASC')
                ->get();

        $listdoSen = DB::table('pegawai')->select('id_pegawai','nama_karyawan','nidn')->get();
        
        return view('admin.dashboard.suratketeranganselesai.dosen_pembimbing', ['dosen' => $cek, 'id_sks' => $id, 'list_pegawai'=> $listdoSen]);
    }


    //show form tambah surat keterangan selesai
    public function viewtambahsks(){

    	$dosen = DB::table('pegawai')->get();
        $prodi = DB::table('a_prodi')->get();
        $mk    = DB::table('a_nama_mk')->get();
        $mhs    = DB::table('a_tbl_mhs')->get();
        $ruangan    = DB::table('a_ruangan')->get();


		return view('admin.dashboard.suratketeranganselesai.tambah',['dosen' => $dosen, 'prodi' => $prodi, 'mhs' => $mhs ,'mk' => $mk, 'ruangan' => $ruangan]);

    }

    //show form tambah surat keterangan selesai Versi 2
    public function viewtambahsks_versi_2(){

        $dosen = DB::table('pegawai')->get();
        $prodi = DB::table('a_prodi')->get();
        $mk    = DB::table('a_nama_mk')->get();
        $mhs    = DB::table('a_tbl_mhs')->get();
        $ruangan    = DB::table('a_ruangan')->get();

 

        return view('admin.dashboard.suratketeranganselesai.tambah_2',['dosen' => $dosen, 'prodi' => $prodi, 'mhs' => $mhs ,'mk' => $mk, 'ruangan' => $ruangan]);

    }

    //show form tambah surat keterangan selesai Versi 3
    public function viewtambahsks_versi_3(){

        $dosen = DB::table('pegawai')->get();
        $prodi = DB::table('a_prodi')->get();
        $ruangan    = DB::table('a_ruangan')->get();
        $mk    = DB::table('a_nama_mk')->get();
        $mhs   = DB::table('a_tbl_mhs')
                //->where('status','=','aktif')
                //->orWhere('status','=','cuti')
                //->orWhere('status','=','tidak')
                ->orderBy('nama','ASC')
                ->get();
        //$thnajar =  DB::table('tahun_ajar')->select('tahun_ajar')->groupBy('tahun_ajar')->where('status','=','1')->get();
        //$semester =  DB::table('tahun_ajar')->select('semester')->groupBy('semester')->where('status','=','1')->get();
        
        $surat_tugas = DB::table('a_srt_tgs_pembimbing')
                        ->join('a_tbl_mhs','a_tbl_mhs.id_mhs','=','a_srt_tgs_pembimbing.id_mhs')
                        ->join('a_nama_mk','a_nama_mk.id_mk','=','a_srt_tgs_pembimbing.id_mk')
                        ->select('a_srt_tgs_pembimbing.no_surat','a_srt_tgs_pembimbing.id','a_tbl_mhs.nama','a_tbl_mhs.nim','a_nama_mk.nama_mk')
                        ->where('a_nama_mk.jenis_mk','!=','Pembimbing')

                        ->orderBy('a_srt_tgs_pembimbing.no_surat','DESC')->get();

        return view('admin.dashboard.suratketeranganselesai.tambah_3',['dosen' => $dosen, 'prodi' => $prodi, 'mhs' => $mhs ,'mk' => $mk, 'ruangan' => $ruangan,'NoSuratPembimbing' => $surat_tugas]);

    }



    ///////////////////////////edit surat tugas pembimbing////////////////////////////

    public function editsks($id){

        $cektabel = DB::table('a_surat_keterangan_selesai')

        ->join('a_prodi', 'a_prodi.id_prodi', '=', 'a_surat_keterangan_selesai.prodi') 
        //->join('a_nama_mk', 'a_nama_mk.id_mk', '=', 'a_surat_keterangan_selesai.nama_mk') 
        ->join('a_tbl_mhs', 'a_tbl_mhs.id_mhs', '=', 'a_surat_keterangan_selesai.mahasiswa') 

        ->select(   

                    'a_surat_keterangan_selesai.*', 
                    'a_prodi.nama_prodi',
                    //'a_nama_mk.nama_mk',
                    'a_tbl_mhs.nama'


                )

        ->where('id_sks', '=' , $id)->first();

        $dosen = DB::table('pegawai')->get();
        $prodi = DB::table('a_prodi')->get();
        $mk    = DB::table('a_nama_mk')->get();
        $mhs   = DB::table('a_tbl_mhs')->get();
        $ruangan    = DB::table('a_ruangan')->get();

        return view('admin.dashboard.suratketeranganselesai.edit', ['cektabel' => $cektabel,'dosen' => $dosen, 'prodi' => $prodi, 'mhs' => $mhs ,'mk' => $mk, 'ruangan' => $ruangan,'id_sks' => $id]);

    }
    ///////////////////////////edit surat tugas pembimbing////////////////////////////



    //////////////////////////////////setup untuk cetak surat///////////////////////
    public function setupcetak_sks(Request $request){


        for ($kit=0; $kit < count($request->id); $kit++) { 
            
              $cek[] =  DB::table('a_surat_keterangan_selesai')
                
                ->join('a_prodi', 'a_prodi.id_prodi', '=', 'a_surat_keterangan_selesai.prodi') 
                ->join('a_nama_mk', 'a_nama_mk.id_mk', '=', 'a_surat_keterangan_selesai.nama_mk') 
                ->join('a_tbl_mhs', 'a_tbl_mhs.id_mhs', '=', 'a_surat_keterangan_selesai.mahasiswa') 
                ->select(   
                    'a_surat_keterangan_selesai.id_sks',
                    'a_surat_keterangan_selesai.judul', 
                    'a_surat_keterangan_selesai.tahun_ajar', 
                    'a_surat_keterangan_selesai.mulai', 
                    'a_surat_keterangan_selesai.lokasi', 
                    'a_surat_keterangan_selesai.selesai',
                    'a_surat_keterangan_selesai.semester',
                    'a_surat_keterangan_selesai.created_at',
                    'a_prodi.nama_prodi',
                    'a_nama_mk.nama_mk',
                    'a_tbl_mhs.nama',
                    'a_tbl_mhs.nim'
                )
                ->where('id_sks','=',$request->id[$kit])
                ->first();


        }   
 
        return view('admin.dashboard.suratketeranganselesai.cetak_surat_keterangan_selesai', ['cek' => $cek,'ttd' => $request->ttd,'cap_uvers' => $request->cap_uvers,'TglInput'=> $request->TglInput, 'tgl_diinginkan' => $request->tgl_diinginkan
            ]);

    }

    public function SksMultiple(Request $request){

            if($request->ajax()){
 
                if ($request->id > 0) {
                
                    $NoSurMultiple = DB::table('a_surat_keterangan_selesai')
                    ->join('a_tbl_mhs', 'a_tbl_mhs.id_mhs', '=', 'a_surat_keterangan_selesai.mahasiswa')
                    ->join('a_nama_mk', 'a_nama_mk.id_mk', '=', 'a_surat_keterangan_selesai.nama_mk')
                    ->select('a_surat_keterangan_selesai.id_sks','a_tbl_mhs.nama','a_nama_mk.nama_mk')
                    ->orderBy('a_surat_keterangan_selesai.id_sks','DESC')
                    ->where('a_surat_keterangan_selesai.id_sks','<' ,$request->id)
                    ->paginate(5);

                }else{
                    $NoSurMultiple = DB::table('a_surat_keterangan_selesai')
                    ->join('a_tbl_mhs', 'a_tbl_mhs.id_mhs', '=', 'a_surat_keterangan_selesai.mahasiswa')
                    ->join('a_nama_mk', 'a_nama_mk.id_mk', '=', 'a_surat_keterangan_selesai.nama_mk')
                    ->select('a_surat_keterangan_selesai.id_sks','a_tbl_mhs.nama','a_nama_mk.nama_mk')
                    ->orderBy('a_surat_keterangan_selesai.id_sks','DESC')
                    ->paginate(5);
                }
           
              $output = '';
              $last_id = '';
              
                if(!$NoSurMultiple->isEmpty())
                {   
                    $output .= '
                    <table class="table table-striped table-bordered display table-dark table-hover">
                    <thead>';

                    if ($request->id > 0) {
                        # code...
                    }else{
                    $output .= '<tr>
                                    <th>Nomor Surat</th>
                                    <th>Mahasiswa</th>
                                    <th>Mk</th>
                                    <th class="p-2" style="vertical-align: middle;">
                                    <div class="icheck-primary d-inline"  style="cursor:pointer;">
                                      <input type="checkbox" id="checkboxPrimarysks" name="ceksemua" value="check" onclick="toggle(this)">
                                      <label for="checkboxPrimarysks">
                                        
                                      </label>
                                    </div>
                                    </th>
                                </tr>';
                    }
                    $output .= '</thead>
                    <tbody style="">';
                    foreach ($NoSurMultiple as $keyMult => $item_NoSurMultiple){
                    $output .=  '<tr>
                                    <td class="p-2" style="vertical-align: middle; width:40%;">';

                                        $sksDP = DB::table('a_sks_dp')
                                        ->where('a_sks_dp.id_sks_selesai','=',$item_NoSurMultiple->id_sks)
                                        ->select('a_sks_dp.no_surat')
                                        ->orderBy('a_sks_dp.no_surat','DESC')
                                        ->get();

                                        foreach ($sksDP as $ht => $htV) {
                                            $output .= $htV->no_surat.'<br>';
                                        }

                    $output .=     '</td>
                                    <td class="p-2" style="vertical-align: middle; width:60%;">'.$item_NoSurMultiple->nama.'</td>';

                                    if ($item_NoSurMultiple->nama_mk == 'Tugas Akhir') {

                    $output .=     '<td class="p-2" style="vertical-align: middle; " nowrap><span class="badge badge-pill badge-success" style="width: 80px;">
                                    Tugas Akhir</span></td>';  

                                    }else{

                    $output .=     '<td class="p-2" style="vertical-align: middle; " nowrap><span class="badge badge-pill badge-primary" style="width: 80px;">
                                    Magang</span></td>';  

                                    }

                    $output .=     '<td class="p-2" style="vertical-align: middle;">
                                        <div class="icheck-primary d-inline" style="cursor:pointer; text-align: center;">
                                            <input type="checkbox" id="Mult'.$item_NoSurMultiple->id_sks.'" name="id[]" value="'.$item_NoSurMultiple->id_sks.'">
                                            <label for="Mult'.$item_NoSurMultiple->id_sks.'"></label>
                                        </div>
                                    </td>
                                </tr>';

                        $last_id = $item_NoSurMultiple->id_sks;
                    }
                    '</tbody>
                    </table>';
                    $output .='<tr id="sisa"><td colspan="4" style="text-align:center;"><div id="load_more">
                    <button type="button" name="load_more_button" class="btn btn-primary btn-sm" data-id="'.$last_id.'" id="load_more_button">Lebih Banyak</button>
                    </div></td><tr>';

                }else{
               $output .= '
               <div id="load_more">
                <button type="button" name="load_more_button" class="btn btn-info form-control">No Data Found</button>
               </div>
               ';
              }
              echo $output;
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

}
