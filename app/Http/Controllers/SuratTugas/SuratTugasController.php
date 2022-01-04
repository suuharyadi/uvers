<?php

namespace App\Http\Controllers\SuratTugas;


use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Encryption\DecryptException;


use App\level as Level;
use App\Pegawai as Pegawai; 
use App\Jabatan as jabatan;
use App\kategori as Kategori; 
use App\Surattugas as SuratTugas;
use App\Berkas as Berkas;
use DataTables;
use DB;
use Validator; 
use Response;
use Redirect;
use Alert;
use Auth;
use File;

class SuratTugasController extends Controller
{	

    public function ExportExcelSrtTgs(){

        $CekData = DB::table('surat_tugas')->orderBy('surat_tugas.id_surattugas')
                    ->join('kategorisebagai','kategorisebagai.id_kategori','=','surat_tugas.kategori_fk')
                    ->select('surat_tugas.*','kategorisebagai.nama_kategori')
                    ->orderBy('surat_tugas.id_surattugas','DESC')
                    ->get();

        return view('admin.dashboard.surattugas.ExportExcel',['CekData' => $CekData]);
    }


    public function SetujuAdmin(Request $request, $id){

        $tanggal_hari_ini = date('Y-m-d'); 
        DB::table('surat_tugas')->where('id_surattugas', $id)->update(['status_acc' => 1, 'tanggal_acc' => $tanggal_hari_ini,]);
        return Redirect::back()->with('success', 'Berhasil Menyetujui Kegiatan');

    }

	///////////////////////////////////////////////index/////////////////////////////////////////////////////////////
	public function index(){
		$nosu = $this->nosurattugas();

    	return view('admin.dashboard.surattugas.index')->with('nosu',$nosu);
    }
    public function setupprint($id){

        if($this->cek_akses('45') == 'yes'){

             try {
                $id_surattugas = decrypt($id);
                } catch (DecryptException $e) {
                    //
                }  
                $surattugas     = SuratTugas::find($id_surattugas);

            return view('admin.dashboard.surattugas.setupprint',['id_surattugas' => $id_surattugas])->with('no_surat', $surattugas);

        }else{

            alert()->error('Tidak Ada Akses', ' Anda Tidak Memiliki Akses')->persistent('Close');
            return Redirect::action('SuratTugas\SuratTugasController@index');

        }

    }

    public function suratcetak(request $request,$id){
        
        try {
            $id_surattugas = decrypt($id);
        } catch (DecryptException $e) {
            //
        }  

        $a = Input::get('no_surat');
        $b = Input::get('pengabdian');
        $c = Input::get('th_nomor_pegawai');
        $d = Input::get('ttd');
        $e = Input::get('jumlahloop');
        $f = Input::get('kategori_tambahan');
        $g = Input::get('posisi');


        $cekpeserta = DB::table('peserta')
        ->join('pegawai', 'pegawai.id_pegawai','=','peserta.id_pegawaip_fk' )
        ->select('peserta.id_peserta','peserta.id_surattugas_fk','id_pegawaip_fk','peserta.nipp','peserta.nidnp','peserta.nama_jabatanp','pegawai.nama_karyawan')
        ->where('peserta.id_surattugas_fk', $id_surattugas)
        ->get();

        $datasurattugas = SuratTugas::where('id_surattugas',$id_surattugas)->first();
        $nmkategori = $this->namakategori($datasurattugas->kategori_fk);

        if ($datasurattugas->tanggal_kegiatan_mulai == $datasurattugas->tanggal_kegiatan_selesai) {
            $satutanggal = $this->tanggal_indo($datasurattugas->tanggal_kegiatan_mulai);
            $tanggal_mulai = null;
            $tanggal_selesai = null;
            $tanggal_mulai_ing = null;
            $tanggal_selesai_ing = null;
            $hari = $this->namahari($datasurattugas->tanggal_kegiatan_mulai);
            $hari_selesai = null;
            $hari_ing = $this->namahari_inggris($datasurattugas->tanggal_kegiatan_mulai);
        }else{
            $satutanggal = null;
            $tanggal_mulai = $this->tanggal_indo($datasurattugas->tanggal_kegiatan_mulai);
            $tanggal_selesai = $this->tanggal_indo($datasurattugas->tanggal_kegiatan_selesai);
            $tanggal_mulai_ing = $this->tanggal_inggris($datasurattugas->tanggal_kegiatan_selesai);
            $tanggal_selesai_ing = $this->tanggal_inggris($datasurattugas->tanggal_kegiatan_selesai);
            $hari = $this->namahari($datasurattugas->tanggal_kegiatan_mulai);
            $hari_selesai = $this->namahari($datasurattugas->tanggal_kegiatan_selesai);
            $hari_ing = $this->namahari_inggris($datasurattugas->tanggal_kegiatan_mulai);
        }

        $penyelenggara = $datasurattugas->penyelengara;
        $lokasi = $datasurattugas->lokasi;
        //$jam_mulai = $datasurattugas->jam_kegiatan_mulai;
        //$jam_selesai = $datasurattugas->jam_kegiatan_selesai;

        if ($datasurattugas->jam_kegiatan_mulai == $datasurattugas->jam_kegiatan_selesai) {
            $jam_mulai   = null;
            $jam_selesai = null;

        }else{
             $jam_mulai   = $this->jam_tampil($datasurattugas->jam_kegiatan_mulai);
             $jam_selesai = $this->jam_tampil($datasurattugas->jam_kegiatan_selesai);
        }

        if ($datasurattugas->tanggal_acc == null || empty($datasurattugas->tanggal_acc)) {
             $tanggal_acc = null;
             $tanggal_acc_ing = null;
        }else{
            $tanggal_acc = $this->tanggal_indo($datasurattugas->tanggal_acc);
            $tanggal_acc_ing = $this->tanggal_inggris($datasurattugas->tanggal_acc);
        }
        
        return view('admin.dashboard.surattugas.cetak',['nama_kegiatan' => $datasurattugas->nama_kegiatan],['nama_kategori' => $nmkategori])->with('penyelengara',$penyelenggara)
                        ->with('tanggal_mulai', $tanggal_mulai)
                        ->with('tanggal_selesai', $tanggal_selesai)
                        ->with('tanggal_mulai_ing', $tanggal_mulai_ing)
                        ->with('tanggal_selesai_ing', $tanggal_selesai_ing)
                        ->with('hari',$hari)
                        ->with('hari_selesai',$hari_selesai)
                        ->with('jam_mulai',$jam_mulai)
                        ->with('jam_selesai',$jam_selesai)
                        ->with('satutanggal', $satutanggal)
                        ->with('peserta',$cekpeserta)
                        ->with('tanggal_acc',$tanggal_acc)
                        ->with('lokasi',$lokasi)
                        ->with('a',$a)
                        ->with('b',$b)
                        ->with('c',$c)
                        ->with('tanggal_acc_ing',$tanggal_acc_ing)
                        ->with('hari_ing',$hari_ing)
                        ->with('ttd',$d)
                        ->with('jumlahloop',$e)
                        ->with('posisi',$g)
                        ->with('kategori_tambahan',$f);
    }

    public function suratlist(Request $request){

        return DataTables::of(DB::table('surat_tugas')
        ->join('kategorisebagai', 'kategorisebagai.id_kategori','=','surat_tugas.kategori_fk' )
        //->join('peserta', 'peserta.id_surattugas_fk','=','surat_tugas.id_surattugas' )
        ->select(   'surat_tugas.id_surattugas',
                    'surat_tugas.nomor_surat',
                    'surat_tugas.kategori_fk',
                    'surat_tugas.nama_kegiatan',
                    'surat_tugas.penyelengara',
                    'surat_tugas.tanggal_kegiatan_mulai',
                    'surat_tugas.tanggal_kegiatan_selesai',
                    'surat_tugas.jam_kegiatan_mulai',
                    'surat_tugas.jam_kegiatan_selesai',
                    'surat_tugas.lokasi',
                    'surat_tugas.tanggal_acc',
                    'surat_tugas.status_acc',
                    'kategorisebagai.id_kategori',
                    'kategorisebagai.nama_kategori')
        )
        ->addColumn('action', function($data){

                if($this->cek_akses('43') == 'yes'){

                $button =   '<a href="edit/'.encrypt($data->id_surattugas).'/surattugas" title="edit">
                            <button type="button" class="btn btn-primary btn-xs"><span class="fa fa-edit"> </span></button>
                            </a> | ';

                }else{
                $button = '';
                }
                
                if($this->cek_akses('44') == 'yes'){

                $button .= '<a href="surattugas/'.encrypt($data->id_surattugas).'/destroy" title="hapus" onclick="return confirm(\'Apakah Anda Yakin Menghapus Data Pegawai '.$data->nama_kegiatan.' Ini ? \' ) "><button type="button" class="btn btn-danger btn-xs"><span class="fa fa-trash"> </span></button></a> | ';

                }else{
                $button .= '';
                }

                if ($data->status_acc == 0) {
                    
                }elseif($data->status_acc == 1){
                   
                    $button .= '<a href="setup/'.encrypt($data->id_surattugas).'/surat" title="cetak"><button type="button" class="btn btn-warning btn-xs"><span class="fa fa-print"> </span></button></a>';


                }elseif($data->status_acc == 2){

                }elseif($data->status_acc == 3){
                    $button .=   '<a href="'.Route('SetujuAdmin',['id' => $data->id_surattugas]).'" title="edit" onclick="return confirm(\'Yakin Untuk Menyetujui Kegiatan '.$data->nama_kegiatan.' Ini ? \' )">
                            <button type="button" class="btn btn-success btn-xs"><span class="fa fa-check-circle"> </span></button>
                            </a>';
                }else{
                    $button .= 'Terjadi kesalahan';
                }
                    
                

                return $button;
            })
        ->addColumn('tanggal_mulai', function($data){
                $button =   $this->tanggal_indo($data->tanggal_kegiatan_mulai);
                return $button;
            })
        ->addColumn('tanggal_selesai', function($data){
                $button =   $this->tanggal_indo($data->tanggal_kegiatan_selesai);
                return $button;
            })
     
        ->addColumn('status', function($data){
                if ($data->status_acc == 0) {
                    $button =   '<a href="#" title="Ubah Status"><button type="button" id="'.encrypt($data->id_surattugas).'" data-id="'.$data->nama_kegiatan.'" class="cekswal btn btn-info btn-xs"><span class="far fa fa-hand-pointer"> Diajukan</span></button></a>';

                    //onclick="return confirm(\'Apakah Anda Sudah Melakukan Cek Terhadap Kegiatan Ini, Yakin Untuk Melanjutkan Proses Kegiatan '.$data->nama_kegiatan.' Ini ? \' ) "
                }elseif ($data->status_acc == 1) {
                    $button =   '<small class="badge badge-success">Diterima</small>
                                ';
                }elseif ($data->status_acc == 2){
                    $button =   '<small class="badge badge-danger">Ditolak</small>
                                ';
                }elseif ($data->status_acc == 3){
                    $button =   '<small class="badge badge-warning">Proses</small>
                                ';
                }else{
                    $button = 'Terjadi kesalahan';
                }
                
                return $button;
            })
        ->addColumn('peserta', function($data){
             

                $button = '<div class="btn-group">
                  <button type="button" class="btn btn-outline-info btn-xs dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <span class="fa fa-cogs"></span>
                  </button>
                  <div class="dropdown-menu">
                    <a class="dropdown-item" href="lihat/'.encrypt($data->id_surattugas).'/peserta" title="Lihat Peserta">Lihat Peserta </a> 
                    <a class="dropdown-item" href="lihat/'.encrypt($data->id_surattugas).'/berkas" title="Lihat File">Lihat File</a>
                    '.$this->CekStatus($data->status_acc, $data->id_surattugas).'
                   
                  </div>
                </div>';
                return $button;
            })

        ->addColumn('jumlahorang', function($data){
            $cek = DB::table('peserta')
             ->where('id_surattugas_fk', '=', $data->id_surattugas)
             ->count();
            return $cek;
        })
        ->rawColumns(['action','tanggal_mulai','tanggal_selesai','peserta','status','jumlahorang'])
        ->make(true);
            
    }

    protected function CekStatus($sts, $id_surat){

        if ($sts == 1) {
            $button = '<a href="#" class="dropdown-item up_no" id='.$id_surat.'>Update Nomor Surat</a>';
            return $button;
        }else if($sts == 2){
            $button = '<a href="cek/'.$id_surat.'/alasan" class="dropdown-item bg-danger" title="Cek Pesan Error">Pengajuan Ditolak</a>';
            return $button;
        }else{

        }
        
    }

    ///////////////////////////////////////////////index/////////////////////////////////////////////////////////////
    public function alasantolak($id){
        $cekpeserta = DB::table('alasan')
         ->select('keterangan')
        ->where('surat_tugas_ket_fk', $id)
        ->get();

        return view('admin.dashboard.surattugas.indexalasan')->with('list_alasan', $cekpeserta);
    }

    public function updatenosurat(){

        $id = Input::get('id_surattugas');
        $ceknosu = Input::get('no_surat');
        $ceknosuformat = $this->nosurattugas();

        $no_surat = $ceknosu.$ceknosuformat;
        $cek = DB::table('surat_tugas')
         ->where('nomor_surat', '=', $no_surat)
         ->count();

        if ($cek > 0) {
            alert()->error('Surat Tugas', 'Nomor Surat Telah Digunakan')->persistent('Close');
            return Redirect::action('SuratTugas\SuratTugasController@index');
        }

        $surattugas = DB::table('surat_tugas')->where('id_surattugas', $id)->update([
            'nomor_surat' => $no_surat,
            
            ]);

        if ($surattugas) {
            alert()->success('Surat Tugas', 'Berhasil Menambah Nomor Surat Tugas')->persistent('Close');
            return Redirect::action('SuratTugas\SuratTugasController@index');

        }else{
            alert()->error('Surat Tugas', 'Gagal')->persistent('Close');
            return Redirect::action('SuratTugas\SuratTugasController@index');
        }
    }

    ///////////////////////////////////////////////tambah/////////////////////////////////////////////////////////////

    public function showtambah(){

        $nomor_suratfix = $this->nosurattugas();
        $pegawai        = Pegawai::orderBy('id_pegawai')->get();
        $kategori       = Kategori::orderBy('id_kategori')->get();
        $jabatan        = jabatan::orderBy('id_jabatan')->get(); 

        $jmlform = Input::get('loopingform');

        return view('admin.dashboard.surattugas.tambahsurattugas',['nomor_suratfix' => $nomor_suratfix],['jumlah_form' => $jmlform])->with('list_pegawai', $pegawai)->with('list_kategori', $kategori)->with('n_s',$nomor_suratfix)->with('list_jabatan',$jabatan);
    }



    public function autocomplete(Request $request)
    {
        $data1 = DB::table('ac_namakegiatan')
                ->select('nama_kegiatan_auto', DB::raw('count(*) as total'))
                ->groupBy('nama_kegiatan_auto')
                ->where("nama_kegiatan_auto","LIKE","%{$request->input('query')}%")
                ->get();
        $data2 = array();
        foreach ($data1 as $data)
            {
                $data2[] = $data->nama_kegiatan_auto;
            }
        return response()->json($data2);
    }


     public function autocomplete2(Request $request)
    {
        $data1 = DB::table('ac_namakegiatan')
                ->select('diselenggarakan_oleh', DB::raw('count(*) as total'))
                ->groupBy('diselenggarakan_oleh')
                ->where("diselenggarakan_oleh","LIKE","%{$request->input('query')}%")
                ->get();
        $data2 = array();
        foreach ($data1 as $data)
            {
                $data2[] = $data->diselenggarakan_oleh;
            }
        return response()->json($data2);
    }

    public function autocompletesurattugas(Request $request)
    {
        $data1 = DB::table('surat_tugas')
                ->select('nomor_surat', DB::raw('count(*) as total'))
                ->groupBy('nomor_surat')
                ->where("nomor_surat","LIKE","%{$request->input('query')}%")
                ->orderBy('nomor_surat', 'DESC')
                ->get();

        $data2 = array();
        foreach ($data1 as $data)
            {
                $data2[] = $data->nomor_surat;
            }
        return response()->json($data2);
    }

    public function postDropdown() {   
        # Tarik ID inputan
        $set = Input::get('id');

        # Inisialisasi variabel berdasarkan masing-masing tabel dari model
        # dimana ID target sama dengan ID inputan
        $jabatan = jabatan::where('id_pegawai_fk', $set)->get();

        # Buat pilihan "Switch Case" berdasarkan variabel "type" dari form
        switch(Input::get('type')):
            # untuk kasus "kabupaten"
            case 'jabatan':
                # buat nilai default
                $return = '<option value="">Pilih Jabatan...</option>';
                # lakukan perulangan untuk tabel kabupaten lalu kirim
                foreach($jabatan as $temp) 
                    # isi nilai return
                    $return .= "<option value='$temp->nm_jabatan'>$temp->nm_jabatan</option>";
                # kirim
                return $return;
            break;
        # pilihan berakhir
        endswitch;    
    }

    public function postDropdownnipnidn() {   
        # Tarik ID inputan
        $set = Input::get('id');

        # Inisialisasi variabel berdasarkan masing-masing tabel dari model
        # dimana ID target sama dengan ID inputan
        $ceknipnidn = DB::table('pegawai')
        ->select('id_pegawai','nip','nidn')
        ->where('id_pegawai', $set)
        ->get();

        # Buat pilihan "Switch Case" berdasarkan variabel "type" dari form
        switch(Input::get('type')):
            # untuk kasus "kabupaten"
            case 'nipnidn':
                # buat nilai default
                $return = '<option value="">Pilih NIP Atau NIDN...</option>';
                # lakukan perulangan untuk tabel kabupaten lalu kirim
                foreach($ceknipnidn as $key ) 
                    # isi nilai return
                    $return .= "<option value='$key->nip'>NIP : $key->nip</option>";
                    $return .= "<option value='$key->nidn'>NIDN : $key->nidn</option>";
                  # kirim
                return $return;
            break;
        # pilihan berakhir
        endswitch;    
    }

    ///////////////////////////////////////////////tambah/////////////////////////////////////////////////////////////

    public function tambahsrttgs(Request $request){
      
        ############################request tabel peserta#############################
        $pegawai = Input::get('pegawai');
        $nipnidn = Input::get('nipnidn');
        $jabatan = Input::get('jabatan');
        $kategori_kegiatan = Input::get('kategori_kegiatan');
        ############################request tabel peserta#############################
        


        $simpansurattugas = $this->datasurattugas($request->all());
       
        $last_id = DB::getPDO()->lastInsertId($simpansurattugas);

        $files = $request->file('files');
        $berkas=array();

        foreach($files as $file){
            $name = $file->getClientOriginalName();
            $size = $file->getSize();
                $path = public_path().'/berkas/' . $last_id;
                  if(empty($errors)==true){
                      if(!File::isDirectory($path)){
                            Storage::makeDirectory($path, $mode = 0777, true, true);
                          }
                          if(File::isDirectory("$path/".$name)==false){
                               if (file_exists($path.'/'.$name)) {
                                alert()->error('Surat Tugas', 'File Sudah Ada')->persistent('Close');
                                die;
                              }
                              $file->move("$path/",$name);
                          }else{                  // rename the file if another one exist
                                alert()->error('Surat Tugas', 'Terjadi Kesalahan, Harap Coba Kembali')->persistent('Close');
                          }
                      }else{
                              print_r($errors);
                      }

                    $flight = new Berkas;

                    $flight->id_srt_tgs_fk = $last_id;
                    $flight->file_name = $name;
                    $flight->file_size = $size;
                   
                    $flight->save();

        }

        $dataSet = [];
            foreach ($nipnidn as $key => $nipnidnpecah) {

                if (strpos($nipnidnpecah, '.9.') == true) {
                    $nippecah = $nipnidnpecah;
                    $nidnpecah = null;
                }elseif (strpos($nipnidnpecah, '.6.') == true) {
                    $nippecah = $nipnidnpecah;
                    $nidnpecah = null;
                }else{
                    $nidnpecah = $nipnidnpecah;
                    $nippecah = null;
                }
      
        $dataSet[] = [
                'id_surattugas_fk'     => $last_id,
                'id_pegawaip_fk'  => $pegawai[$key],
                'nipp'  => $nippecah,
                'nidnp'  => $nidnpecah,
                'nama_jabatanp'  => $jabatan[$key]
            ];
        }

        $query2 = DB::table('peserta')->insert($dataSet);

    
        if ($simpansurattugas && $flight && $query2) {
            //return response()->json($request->all(),200);
            alert()->success('Surat Tugas', 'Berhasil Melakukan Pengajuan')->persistent('Close');
            
            return Redirect::action('SuratTugas\SuratTugasController@showtambah');
        }else{
            abort(500);
        }
    }
    ///////////////////////////////////////////////tambah/////////////////////////////////////////////////////////////

    ///////////////////////////////////////////////Edit/////////////////////////////////////////////////////////////
    public function showedit($id){

        try {
            $id_surattugas = decrypt($id);
        } catch (DecryptException $e) {
            //
        }        

        $pegawai        = Pegawai::orderBy('id_pegawai')->get();
        $kategori       = Kategori::orderBy('id_kategori')->get();
        $jabatan        = jabatan::orderBy('id_jabatan')->get(); 

        //$surattugas     = SuratTugas::find($id);
        $surattugas     = SuratTugas::find($id_surattugas);

        return view('admin.dashboard.surattugas.editsurattugas', $surattugas)->with('list_pegawai', $pegawai)->with('list_kategori', $kategori);
    }


    public function simpanedit($id){

        $input = Input::all();

        $pecah = explode(" ", $input['tanggal_kegiatan']);
        $tanggal_mulai  = $pecah[0];
        $tanggal_selesai  = $pecah[2];

        $surattugas = DB::table('surat_tugas')->where('id_surattugas', $id)->update([
            'kategori_fk' => $input['kategori_kegiatan'],
            'nama_kegiatan' => $input['nama_kegiatan'],
            'penyelengara' => $input['diselenggarakan_oleh'],
            'lokasi' => $input['lokasi'],
            'tanggal_kegiatan_mulai' => $tanggal_mulai,
            'tanggal_kegiatan_selesai' => $tanggal_selesai,
            'jam_kegiatan_mulai' => $input['jam_kegiatan_mulai'],
            'jam_kegiatan_selesai' => $input['jam_kegiatan_selesai'],
            ]);

        if ($surattugas) {
            alert()->success('Surat Tugas', 'Berhasil Mengubah Data')->persistent('Close');
            return Redirect::action('SuratTugas\SuratTugasController@index')
                          ->with('successMessage','Data Surat Tugas "'.Input::get('nama_kegiatan').'" telah berhasil diubah.'); 
        }elseif(!$surattugas){
            alert()->error('Surat Tugas', 'Gagal Mengubah Surat Tugas')->persistent('Close');
            return Redirect::action('SuratTugas\SuratTugasController@index');
        }else{
            abort(500);
        }

    }

    public function destroysurattugas($id){

        if($this->cek_akses('44') == 'yes'){

            try {
                $id_surattugas = decrypt($id);
            } catch (DecryptException $e) {
                //
            }

            $cek_berkas = File::deleteDirectory(public_path().'/berkas/'. $id_surattugas);

            if ($cek_berkas) {

                $cekdestorysurattugas= DB::delete('delete from surat_tugas where id_surattugas = ?',[$id_surattugas]);
                $cekdestoryberkas= DB::delete('delete from berkas where id_srt_tgs_fk = ?',[$id_surattugas]);
                $cekdestorypeserta= DB::delete('delete from peserta where id_surattugas_fk = ?',[$id_surattugas]);
                
                if ($cekdestorysurattugas && $cekdestoryberkas && $cekdestorypeserta) {
                    alert()->success('Surat Tugas', 'Berhasil Dihapus')->persistent('Close');
                    return Redirect::action('SuratTugas\SuratTugasController@index')
                                  ->with('successMessage','Data Surat Tugas "'.Input::get('nama_kegiatan').'" telah berhasil hapus.'); 
                }else{
                    abort(500);
                }

            }else{
                abort(500);
            }
                        
        }else{


            alert()->error('Tidak Ada Akses', ' Anda Tidak Memiliki Akses')->persistent('Close');
            return Redirect::action('SuratTugas\SuratTugasController@index');

        }

        
    }


    public function updateproses($id){

        try {
            $id_surattugas = decrypt($id);
        } catch (DecryptException $e) {
            //
        }

        $proses = '3';
 
        $status_proses = DB::table('surat_tugas')->where('id_surattugas', $id_surattugas)->update([
            'status_acc' => $proses,
            ]);

        if ($status_proses) {
            alert()->success('Surat Tugas', 'Berhasil Memproses Data')->persistent('Close');
            return Redirect::action('SuratTugas\SuratTugasController@index')
                          ->with('successMessage','Data Surat Tugas Telah Diproses.'); 
        }elseif(!$status_proses){
            alert()->error('Surat Tugas', 'Gagal Memproses Data')->persistent('Close');
            return Redirect::action('SuratTugas\SuratTugasController@index');
        }else{
            abort(500);
        }

    }
    ///////////////////////////////////////////////Edit/////////////////////////////////////////////////////////////



    ///////////////////////////////////////////////Hapus/////////////////////////////////////////////////////////////
    public function destroy($id){

      
    }
    ///////////////////////////////////////////////Hapus/////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////Hapus Jabatan/////////////////////////////////////////////////////
    public function destroyjabatan($id){

    }
    ///////////////////////////////////////////////Hapus Jabatan//////////////////////////////////////////////////////


    ///////////////////////////////////////////////Index Berkas//////////////////////////////////////////////////////
    public function indexberkas($id){

         try {
            $id_surattugas = decrypt($id);
        } catch (DecryptException $e) {
            //
        }

        $berkas = DB::table('berkas')
        ->select('*')
        ->where('id_srt_tgs_fk', '=', $id_surattugas)
        ->get();


        return view('admin.dashboard.surattugas.indexfile', ['id_surattugas' => $id_surattugas])->with('list_berkas', $berkas);
    
    }

    public function showtambahberkas($id){

        try {
            $id_surattugas_fk = decrypt($id);
        } catch (DecryptException $e) {
            //
        }

        return view('admin.dashboard.surattugas.tambahberkas',['id_surattugas_fk' => $id_surattugas_fk]);

    }

    public function simpanberkas(request $request,$id){

        try {
            $id_surattugas_fk = decrypt($id);
        } catch (DecryptException $e) {
            //
        }

        $files = $request->file('files');
        
        $berkas=array();

        foreach($files as $file){

            $name = $file->getClientOriginalName();
            $size = $file->getSize();
            $path = public_path().'/berkas/' . $id_surattugas_fk;

              if (file_exists($path.'/'.$name)) {
                        return redirect('tambah/'.encrypt($id_surattugas_fk).'/berkas/')->with('error','File Sudah Ada "'.$name.'" Gagal Ditambah'); 
                } else {
                        $file->move("$path/",$name);
              }
             
            $flight = new Berkas;

            $flight->id_srt_tgs_fk = $id_surattugas_fk;
            $flight->file_name = $name;
            $flight->file_size = $size;
           
            $flight->save();

        }

        return redirect('lihat/'.encrypt($id_surattugas_fk).'/berkas/')->with('successMessage','Berhasil Menambah File Baru'); 
    }

    public function destroyfile($id, $surat_tugas){

            try {
                $id_file = decrypt($id);
                } catch (DecryptException $e) {
                    //
            }
            try {
                $surat_tugascek = decrypt($surat_tugas);
                } catch (DecryptException $e) {
                    //
            }
            $gambar = Berkas::where('id_file',$id_file)->first();

            $cekfile = File::delete(public_path().'/berkas/'. $surat_tugascek.'/'.$gambar->file_name);

                if ($cekfile) {

                    $cekdestory= DB::delete('delete from berkas where id_file = ?',[$id_file]);

                    if ($cekdestory) {
                        alert()->success('Berkas', 'Berhasil Hapus Berkas '.$gambar->file_name.'')->persistent('Close');
                        return redirect('lihat/'.encrypt($surat_tugascek).'/berkas/');
                    }else{
                        alert()->error('Berkas', 'Gagal Menghapus Berkas')->persistent('Close');
                        return redirect('lihat/'.encrypt($surat_tugascek).'/berkas/');
                    }

                }else{
                    abort(500);
                }
           
    }

    public function getDownloadfile($id, $nm_file){

        try {
            $id_srt_tgs_fk = decrypt($id);
        } catch (DecryptException $e) {
            //
        }
        $file= public_path(). "/berkas/".$id_srt_tgs_fk."/".$nm_file;

        $headers = [
              'Content-Type' => 'application/pdf',
              'Content-Type:' => 'image/png',
              'Content-Type:' => 'image/jpg',
           ];

        return response()->download($file, $nm_file, $headers);
    }

    ///////////////////////////////////////////////Index Berkas//////////////////////////////////////////////////////

    ///////////////////////////////////////////////Index Peserta//////////////////////////////////////////////////////
    public function indexpeserta($id){

        try {
            $id_surattugas = decrypt($id);
        } catch (DecryptException $e) {
            //
        }

        $peserta = DB::table('peserta')
        ->join('pegawai', 'peserta.id_pegawaip_fk','=','pegawai.id_pegawai' )
        ->select('peserta.id_peserta','pegawai.nama_karyawan','peserta.nipp','peserta.nidnp','peserta.nama_jabatanp','peserta.id_pegawaip_fk', 'peserta.id_surattugas_fk')
        ->where('peserta.id_surattugas_fk', '=', $id_surattugas)
        ->get();

        return view('admin.dashboard.surattugas.indexpeserta',['id_surattugas_fk' => $id_surattugas])->with('list_peserta', $peserta);
    }

    public function tambahshowpeserta($id){

        try {
            $id_surattugas_fk = decrypt($id);
        } catch (DecryptException $e) {
            //
        }

        $jmlform = Input::get('loopingform');
        $pegawai = Pegawai::orderBy('id_pegawai')->get();

        return view('admin.dashboard.surattugas.tambahpeserta',['jumlah_form' => $jmlform],['id_surattugas_fk' => $id_surattugas_fk])->with('list_pegawai', $pegawai);

    }

    public function simpanpeserta($id){

        try {
            $id_surattugas_fk = decrypt($id);
        } catch (DecryptException $e) {
            //
        }

        $pegawai = Input::get('pegawai');
        $nipnidn = Input::get('nipnidn');
        $jabatan = Input::get('jabatan');

        $dataSet = [];
            foreach ($nipnidn as $key => $nipnidnpecah) {

                if (strpos($nipnidnpecah, '.9.') == true) {
                    $nippecah = $nipnidnpecah;
                    $nidnpecah = null;
                }elseif (strpos($nipnidnpecah, '.6.') == true) {
                    $nippecah = $nipnidnpecah;
                    $nidnpecah = null;
                }else{
                    $nidnpecah = $nipnidnpecah;
                    $nippecah = null;
                }
      
        $dataSet[] = [
                'id_surattugas_fk'     => $id_surattugas_fk,
                'id_pegawaip_fk'  => $pegawai[$key],
                'nipp'  => $nippecah,
                'nidnp'  => $nidnpecah,
                'nama_jabatanp'  => $jabatan[$key]
            ];
        }

        $query2 = DB::table('peserta')->insert($dataSet);

        if ($query2) {
            //return response()->json($request->all(),200);
            alert()->success('Peserta Surat Tugas', 'Tambah Peserta Berhasil')->persistent('Close');
            
            return redirect('lihat/'.encrypt($id_surattugas_fk).'/peserta/');
        }else{
            abort(500);
        }

    }

    public function destroypeserta($id, $surat_tugas){
            try {
            $id_peserta = decrypt($id);
            } catch (DecryptException $e) {
                //
            }
            try {
            $surat_tugascek = decrypt($surat_tugas);
            } catch (DecryptException $e) {
                //
            }
            $cekdestory= DB::delete('delete from peserta where id_peserta = ?',[$id_peserta]);

            if ($cekdestory) {
                alert()->success('Peserta', 'Berhasil Hapus Peserta')->persistent('Close');
                return redirect('lihat/'.encrypt($surat_tugascek).'/peserta/');
            }else{
                alert()->error('Peserta', 'Gagal Menghapus Peserta')->persistent('Close');
                return redirect('lihat/'.encrypt($surat_tugascek).'/peserta/');
            }

    }

    ///////////////////////////////////////////////Index Peserta//////////////////////////////////////////////////////

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

    protected function nosurattugas(){

        $bulan = date('n');
        $tahun = date('Y');
        $endtahun = substr($tahun,-2);
        $nomor = "/ST/UVERS/".$this->getRomawi($bulan)."/".$endtahun;

        return $nomor;
    }

    protected function tanggal_indo($tanggal) {
        $bulan = array(1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember');
        $split = explode('-', $tanggal);
        return $split[2] . ' ' . $bulan[(int) $split[1]] . ' ' . $split[0];
    }

    protected function tanggal_inggris($tanggal) {
        $bulan = array(1 => 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
        $split = explode('-', $tanggal);
        return  $bulan[(int) $split[1]] . ' ' . $split[2] . '<sup>th</sup>, ' . $split[0];

    }


    ##################request tabel surat tugas ############################
    protected function datasurattugas(array $data){
        
        $kategori_kegiatan = Input::get('kategori_kegiatan');
        $nama_kegiatan = Input::get('nama_kegiatan');
        $diselenggarakan_oleh = Input::get('diselenggarakan_oleh');
        $lokasi = Input::get('lokasi');
        $waktu_kegiatan = Input::get('waktu_kegiatan');
        $status_acc = 0;

        $pecah = explode(" ", $waktu_kegiatan);
        $tanggal_mulai  = $pecah[0];
        $tanggal_selesai = $pecah[2];
        $jam_mulai = Input::get('jam_kegiatan_mulai');
        //$jam_selesai =  Input::get('jam_kegiatan_selesai');

        if (empty(Input::get('sdselesai')) == false) {
            $jam_selesai = '00:00:00';

        }else{
            $jam_selesai =  Input::get('jam_kegiatan_selesai');
        }

        if ((empty($jam_mulai) == true) && (empty($jamselesai) == true)) {
            $jam_mulai = "00:00:00";
            $jam_selesai = "00:00:00";
        }
       
       
        $query1 = DB::table('surat_tugas')->insert( 
            [   'kategori_fk'       => $kategori_kegiatan, 
                'nama_kegiatan'     => $nama_kegiatan,
                'penyelengara'      => $diselenggarakan_oleh,
                'lokasi'            => $lokasi,
                'status_acc'        => $status_acc,
                'tanggal_kegiatan_mulai'    => $tanggal_mulai,
                'tanggal_kegiatan_selesai'  => $tanggal_selesai,
                'jam_kegiatan_mulai'        => $jam_mulai,
                'jam_kegiatan_selesai'      => $jam_selesai
            ]
        );

        if ($query1) {
            return true;
        }else{
            alert()->error('Surat Tugas', 'Gagal Melakukan Pengajuan')->persistent('Close');
            return Redirect::action('SuratTugas\SuratTugasController@showtambah');
        }

        ############################ request tabel peserta#############################
    }


    protected function namakategori($id){
        $datakategori = kategori::where('id_kategori',$id)->first();
        return $datakategori->nama_kategori;
    }

    protected function namahari($tanggal){
        $tgl=substr($tanggal,8,2);
        $bln=substr($tanggal,5,2);
        $thn=substr($tanggal,0,4);
        $info=date('w', mktime(0,0,0,$bln,$tgl,$thn));
        switch($info){
            case '0': return "Minggu"; break;
            case '1': return "Senin"; break;
            case '2': return "Selasa"; break;
            case '3': return "Rabu"; break;
            case '4': return "Kamis"; break;
            case '5': return "Jumat"; break;
            case '6': return "Sabtu"; break;
        };
    }
    protected function namahari_inggris($tanggal){
        $tgl=substr($tanggal,8,2);
        $bln=substr($tanggal,5,2);
        $thn=substr($tanggal,0,4);
        $info=date('w', mktime(0,0,0,$bln,$tgl,$thn));
        switch($info){
            case '0': return "Sunday"; break;
            case '1': return "Monday"; break;
            case '2': return "Tuesday"; break;
            case '3': return "Wednesday"; break;
            case '4': return "Thursday"; break;
            case '5': return "Friday"; break;
            case '6': return "Saturday"; break;
        };
    }

    protected function jam_tampil($jam){
        $cekjam = substr($jam,0,-3);
        return $cekjam;
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
