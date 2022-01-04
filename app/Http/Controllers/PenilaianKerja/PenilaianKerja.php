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


class PenilaianKerja extends Controller
{   


    //TAMPIL MODAL UNTUK INPUT NILAI PELAKSANAAN TUGAS LAIN
    public function TampilNilaiPTL(Request $request){

        $CekData = DB::table('b_tujuan')->select('id_user','id_user_tujuan','id_versi')->where([['id_tujuan','=',$request->id_tujuan],['id_versi','=',$request->versi]])->first();

        if ($CekData) {

             $CekNilai = DB::table('b_pelaksanaan_tugas_lain')->select('id','nilai')->where([['id_user','=',$CekData->id_user],['id_versi','=',$CekData->id_versi]])->first();

             if (!is_null($CekNilai->nilai)) {

                $ModalSet = $this->KontenModalPTL($request->versi);
                $nilaiSet = $CekNilai->nilai;
                $submitButton = 'Ubah Nilai !';

             }else{
                $ModalSet = $this->KontenModalPTL($request->versi);
                $nilaiSet = '0';
                $submitButton = 'Simpan Nilai !';
             }

        }

        return Response::json(array('ceks' => $ModalSet, 'SetNilai' => $nilaiSet, 'TextButton' => $submitButton ,'id_tujuuan' => $request->id_tujuan), 200);
    }

    protected function KontenModalPTL($id_versi){

        $tampil = '';
        $tampil = '  <div class="col-10  mx-auto">
                      <input type="text" class="js-range-slider" id="demo_0" name="nilai" value="" required="" />
                      <input type="hidden" name="id_tujuan" id="data_value_id_tujuan" required="" />
                      <input type="hidden" name="versi" value="'.$id_versi.'" required="" />
                    </div>

                    <hr>
                      <ul style="padding-left:16px; font-weight:bold;">
                      <li >Kategori penilaian terdiri dari 4 (empat) kategori sebagai berikut:
                        <ul style="padding-left:16px;">
                          <li>sangat baik, dengan nilai : 4</li>
                          <li>baik, dengan nilai : 3</li>
                          <li>cukup, dengan nilai : 2</li>
                          <li>kurang, dengan nilai : 1</li>
                        </ul>
                      </li>
                  </ul>';
        return $tampil;
    }
    //MODAL MENAMPILKAN MODAL BERISI BUTTON VARIFIKASI DAN LAINNYA
    public function DetailPenilaianKerja($id_versi){

        return DataTables::of(DB::table('b_data_diri')

            ->rightJoin('users','b_data_diri.id_user','=','users.id')
            ->join('b_kelompok_data_diri','b_kelompok_data_diri.id_user','=','b_data_diri.id_user')
            ->select(   'b_data_diri.id_user',
                        'b_data_diri.nama_lengkap',
                        'users.level',
                        'users.id',
                        'users.name'
                    )
            ->where([['users.id','=', Auth::user()->id],['b_kelompok_data_diri.id_versi','=',$id_versi]]))
            ->addIndexColumn()
            ->addColumn('tugas_lain', function($data) use($id_versi){
                $button = '';
                $cekStatuss = $this->CekStatusSubmit(Auth::user()->id, $id_versi);

                if ($cekStatuss > 0) {
              
                        $button .= '<button type="button" class="btn btn-info btn-sm btn-block" title="Status Sudah Selesai" onClick="alert(\'Anda telah mengajukan status selesai, harap hubungi admin!\')"> Tugas Lain <span class="fa fa-exclamation-circle"> </span></button> ';

                    }else{
                        if ($data->level == 11 || $data->level == 12 || $data->level == 13) {

                            $button = '<hr style="width:10%; border:2px solid grey;">';
                            return $button;

                        }else{

                        if ($this->CekSediaPTL(Auth::user()->id, $id_versi) == 'yes') {

                            $CekId = DB::table('b_pelaksanaan_tugas_lain')->select('id')->where([['id_user','=',Auth::user()->id],['id_versi','=',$id_versi]])->first();


                            if ($CekId) {  $idPTL = $CekId->id; }else{ $idPTL = 'no';}

                            $button .= '<button type="button" class="btn btn-success btn-sm" title="File Sudah Diupload, Hapus Jika Ingin Menggantinya" onClick="alert(\'Anda telah mengajukan status selesai, harap hubungi admin!\')"> Upload Tugas Lain <span class="fa fa-check-circle"> </span></button> ';

                            $button .= ' | <a href="'.Route('DownloadPTL',['id' => $idPTL]).'"><button type="button" class="btn btn-outline-info btn-sm" title="Download File Pelaksanaan Tugas Lain"><span class="fa fa-file-download"> </span></button> </a> |

                                   <button type="button" class="btn btn-outline-danger btn-sm HapusFilePTL" data-id="'.$idPTL.'" title="Hapus File Pelaksanaan Tugas Lain"><span class="fa fa-trash-alt"> </span></button>';
                        }else{

                            $button .= '<button type="button" class="btn btn-outline-primary btn-sm btn-block UploadTugasLain">
                                        <span class="setgrow fa fa-upload"> </span>&nbsp;
                                      Upload</button> ';
                            }

                        }
                       
                    }
                return $button;

            })
            ->addColumn('verif', function($data) use($id_versi){

                $button = '';
                if ($this->CekStatusSubmit(Auth::user()->id, $id_versi) > 0) {

                     $button .= '<button type="button" class="btn btn-info btn-sm btn-block" title="Status Sudah Selesai" onClick="alert(\'Anda telah mengajukan status selesai, harap hubungi admin!\')"> Verifikasi Form <span class="fa fa-exclamation-circle"> </span></button> ';

                }else{

                    if ($data->level == 10 || $data->level == 11 || $data->level == 12 ||  $data->level == 2 || $data->level == 14) {
                        $button .= '<a href="'.Route('VerifikasiForm',['id_user_tujuan' => Crypt::encryptString($data->id_user), 'id_versi' => $id_versi]).'" title="Verifikasi Form">
                            <button type="button" class="btn btn-outline-primary btn-sm btn-block" >
                              <span class="fa fa-list"> </span> Verifikasi Form </button>
                          </a>';  
                    }elseif($data->level == 13){

                        $button .= '<a href="'.Route('VerifikasiForm',['id_user_tujuan' => Crypt::encryptString($data->id),'id_versi' => $id_versi]).'" title="Verifikasi Form">
                            <button type="button" class="btn btn-outline-primary btn-sm btn-block" >
                              <span class="fa fa-list"> </span> Verifikasi Form </button>
                          </a>'; 

                    }else{

                        $button .= '<a href="#" title="Verifikasi Form">
                                    <button type="button" class="btn btn-danger btn-sm btn-block"  onClick="alert(\'Anda Tidak Perlu Verifikasi!\')">
                                      <span class="fa fa-list"> </span> Verifikasi Form</button>
                                  </a>'; 
                    }
                    
                }
                return $button;

            })
            ->addColumn('nilai_atasan', function($data) use($id_versi){

                $cekStatuss = $this->CekStatusSubmit(Auth::user()->id, $id_versi);

                $button = '';
                if ($data->level == 12 || $data->level == 13) {
                    $button = '<hr style="width:10%; border:2px solid grey;">';
                    return $button;
                }else{

                    if ($cekStatuss > 0) {
                        $button .= '<button type="button" class="btn btn-info btn-sm btn-block"  onClick="alert(\'Anda telah mengajukan status selesai, harap hubungi admin!\')">
                                      Nilai Atasan  <i class="fa fa-exclamation-circle"></i></button>';  
                    }else{
                        if ($this->CekJawabanMenilaiAtasan($data->id, $id_versi) > 0) {

                            $button .= '<button type="button" class="btn btn-success btn-sm btn-block"  onClick="alert(\'Sudah melakukan penilaian kepada atasan!\')">
                                      Nilai Atasan <i class="fa fa-check-circle"></i></button>';  
                        }else{
                            $button .= '<a href="'.Route('NilaiAtasan',['id_user' => Crypt::encryptString($data->id), 'id_versi' => $id_versi]).'" title="Menilai Atasan">
                                    <button type="button" class="btn btn-outline-primary btn-sm btn-block" >
                                      <span class="setgrow fa fa-book"> </span> Nilai Atasan</button>
                                  </a>';  
                        }
                    }
                }

                return $button;

            })
            ->addColumn('print_verif', function($data) use($id_versi){

                    $button = '<a href="'.Route('printverif',['id_user' => Crypt::encryptString($data->id_user), 'id_versi' => $id_versi]).'" title="Print Hasil Verifikasi" target="_blank">
                        <button type="button" class="btn btn-outline-info btn-sm" >
                          <span class="fa fa-print"> </span> Print Hasil Verifikasi </button>
                      </a>';  
            

                return $button;

            })
           

            ->rawColumns(['verif','print_verif','nilai_atasan','tugas_lain'])
            ->make(true);
    }


    //NILAI ATASAN
    public function NilaiAtasan($id_user, $id_versi){

        //TUJUAN MENILAI ATASAN
        $cekTujuan = DB::table('b_tujuan')
                    ->join('b_data_diri','b_data_diri.id_user','=','b_tujuan.id_user_tujuan')
                    ->select('b_data_diri.nama_lengkap')
                    ->where([['b_tujuan.id_user','=',Crypt::decryptString($id_user)],['b_tujuan.id_versi','=',$id_versi]])->first();

        if ($cekTujuan) {
            $namaLeng = $cekTujuan->nama_lengkap;
        }else{
            $namaLeng = 'Belum Ditentukan';
        }

        // MENILAI ATASAN MENGGUNAKAN FORM ATAU TIPE SOAL B
        $ceksoal = DB::table('b_soal')
            ->join('b_versi_soal','b_soal.id_versi_fk','=','b_versi_soal.id')  
            ->where([['kategori_soal','=','b'],['b_versi_soal.status_aktif','=','1']])
            ->orderBy('id_soal','ASC')
            ->get();

        return view('admin.dashboard.penilaiankerja.nilai_atasan',['form_cek' =>  $ceksoal,'nama_tujuan' => $namaLeng,'id_versi' => $id_versi]);
    }

    //EDIT FORM Penilaian kerja
    public function FormPenilaianEdit($type, $id_user,$id_versi){

        $ceksoal = DB::table('b_soal')
            ->join('b_versi_soal','b_versi_soal.id','=','b_soal.id_versi_fk')
            ->where([['kategori_soal','=',$type],['b_versi_soal.id','=',$id_versi]])
            ->orderBy('id_soal','ASC')
            ->get();

        return view('admin.dashboard.penilaiankerja.form_penilaian_edit',['form_cek' => $ceksoal,'type' => $type,'id_user' => $id_user]);

    }


    //VERIFIKASI FORM
    public function VerifikasiForm($id_user_tujuan, $id_versi){

        $decrypted_id_user_tujuan = Crypt::decryptString($id_user_tujuan);

        $CekKelompok = DB::table('b_tujuan')
        ->join('b_data_diri','b_data_diri.id_user','=','b_tujuan.id_user')
        ->join('users','users.id','=','b_tujuan.id_user')
        ->select('b_tujuan.*','b_data_diri.nama_lengkap','users.level')
        ->where([['b_tujuan.id_user_tujuan','=',$decrypted_id_user_tujuan],['b_tujuan.id_versi','=',$id_versi]])->get();

        return view('admin.dashboard.penilaiankerja.verif_index',['cekkelompok' => $CekKelompok,'id_user_tujuan' => $id_user_tujuan, 'id_versi' => $id_versi]);

    }

    //Verif Soal dan isi soal untuk atasan 
    public function VerifPenilaianForm($type, $id_user, $id_u_tujuan, $id_versi){

        $id_user_d = Crypt::decryptString($id_user);
        $id_u_tujuan_d = Crypt::decryptString($id_u_tujuan);

        $cekjawaban = DB::table('b_jawaban')
          ->join('b_soal','b_soal.id_soal','=','b_jawaban.id_soal')
          ->select('b_soal.id_soal', 'b_jawaban.id_jawaban','b_jawaban.id_user','b_jawaban.id_soal','b_jawaban.jawaban')
          ->where([
            ['b_soal.kategori_soal','=',$type],
            ['b_jawaban.id_user','=',$id_user_d],
            ['b_soal.id_versi_fk','=',$id_versi],
            ['b_jawaban.jenis_jawaban','!=','nilai_atasan']
          ])
          ->get();
        
        if($cekjawaban->isEmpty()){
            return Redirect::back()->with('error', 'Soal tipe '.strtoupper($type).' ini belum diisi');
            }


        $ceksoal = DB::table('b_soal')->where([['kategori_soal','=',$type],['b_soal.id_versi_fk','=',$id_versi]])->orderBy('id_soal','ASC')->get();

        $nm_lengkap = DB::table('b_data_diri')->select('nama_lengkap','nama_mandarin')->where('id_user','=',$id_user_d)->first();

   
        return view('admin.dashboard.penilaiankerja.form_verif',['form_cek' => $ceksoal,'type' => $type,'id_user' => $id_user_d,'nm_lengkap' => $nm_lengkap->nama_lengkap,'id_u_tujuan' => $id_u_tujuan_d, 'nm_mandarin' => $nm_lengkap->nama_mandarin, 'id_versi' => $id_versi]);

    }
    //VERIFIKASI FORM


    //INDEX SOAL
    public function FormPenilaian($type, $id_versi){

        $ceksoal = DB::table('b_soal')
            ->join('b_versi_soal','b_soal.id_versi_fk','=','b_versi_soal.id')  
            ->where([['kategori_soal','=',$type],['b_versi_soal.id','=',$id_versi]])
            ->orderBy('id_soal','ASC')
            ->get();

        return view('admin.dashboard.penilaiankerja.form_a',['form_cek' => $ceksoal,'type' => $type,'versisoal' => $id_versi]);

    } 

    public function GetDataPen($id_versi){

        return DataTables::of(DB::table('b_data_diri')

            ->rightJoin('users','b_data_diri.id_user','=','users.id')
            ->join('b_kelompok_data_diri','b_kelompok_data_diri.id_user','=','b_data_diri.id_user')
            ->select(   'b_data_diri.id_user',
                        'b_data_diri.nama_lengkap',
                        'b_data_diri.nama_mandarin',
                        'b_data_diri.nomor_ktp',
                        'b_data_diri.durasi_ktp',
                        'b_data_diri.nomor_npwp',
                        'b_data_diri.provinsi_lahir',
                        'b_data_diri.kota_lahir',
                        'b_data_diri.tanggal_lahir',
                        'b_data_diri.golongan_darah',
                        'b_data_diri.nomor_telepon',
                        'b_data_diri.nomor_telepon_2',
                        'b_data_diri.nomor_wa',
                        'b_data_diri.email',
                        'b_data_diri.alamat_sekarang',
                        'b_data_diri.status_tempat_tinggal',
                        'b_data_diri.jenis_kelamin',
                        'b_data_diri.status_marital',
                        'b_data_diri.agama',
                        'b_data_diri.status_dosen',
                        'b_data_diri.id_data_diri',
                        'users.level',
                        'users.id',
                        'users.name'
                    )
            ->where([['users.id','=', Auth::user()->id],['b_kelompok_data_diri.id_versi','=',$id_versi]]))
            ->addIndexColumn()
            
            ->addColumn('AksiLainnya', function($data) use($id_versi){

                if ($this->CekStatusSubmit(Auth::user()->id, $id_versi) > 0) {$setgrow = '';}else{ $setgrow = 'setgrow';}

                $button = '<button type="button" class="btn btn-outline-primary btn-sm DetailLainnya" data-id_data_diri="'.$data->id_data_diri.'" data-id_versi="'.$id_versi.'">
                            <span class="'.$setgrow.' fa fa-info-circle"> </span>&nbsp;
                          Nilai Atasan dan Lainnya</button>';

                return $button ;

            })
            ->addColumn('hasil_penilaian', function($data) use($id_versi){


               //untuk pak didi sama pak suryo dan PAK KETUA YAYASAN Tidak Ada rekap
              if (Auth::user()->id == 52 || Auth::user()->id == 124 || Auth::user()->level == 13) {
                
                $button = '<hr style="width:10%; border:2px solid grey;">';

                return $button;


              }else{

                    $cek_nama = DB::table('b_tujuan')
                    ->join('users','users.id','=','b_tujuan.id_user_tujuan')
                    ->select('id_user_tujuan','users.name','b_tujuan.id_user')
                    ->where([['id_user','=', $data->id_user],['id_versi','=',$id_versi]])
                    ->get();

                    $JumlahTujuan = count($cek_nama);
                 
                    if ($JumlahTujuan > 1) {
                       
                      $button = "<a class='btn btn-outline-success btn-sm btn-block' href='".Route('CekRekapPenilaianMulti',

                        ['id_user'=> Crypt::encryptString($data->id_user),'id_versi' => $id_versi])."'><span class='fa fa-clipboard-list'> </span> Rekap</a>";
                        /*$button = "<a class='btn btn-outline-success btn-sm' href='#'><span class='fa fa-clipboard-list'> </span> Belum Ada Akses</a>";*/

                    }else{
                    # isi nilai return
                    /*$button ="<div class='btn-group'>";
                      $button .="<button class='btn btn-outline-success btn-sm dropdown-toggle' type='button' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false'><span class='fa fa-clipboard-list'> </span> Formulir Rekap ";
                            
                      $button .="</button>";

                      $button .="<div class='dropdown-menu' aria-labelledby='dropdownMenuButton'>";
                      foreach($cek_nama as $key ) 
                       $button .= "<a class='dropdown-item' href='#'>Belum Bisa Diakses</a>";
                      $button .="</div>";
                    $button .="</div>";*/

                    # isi nilai return
                   $button ="<div class='btn-group btn-block'>";
                      $button .="<button class='btn btn-outline-success btn-sm dropdown-toggle btn-block' type='button' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false'><span class='fa fa-clipboard-list'> </span> Rekap ";
                            
                      $button .="</button>";

                      $button .="<div class='dropdown-menu' aria-labelledby='dropdownMenuButton'>";
                      foreach($cek_nama as $key ) 
                       $button .= "<a class='dropdown-item' href='".Route('CekRekapPenilaian',

                        ['id_user_tujuan' => Crypt::encryptString($key->id_user_tujuan),
                        'id_user'=> Crypt::encryptString($key->id_user),'id_versi' => $id_versi])."'>".$key->name."</a>";
                      $button .="</div>";
                    $button .="</div>"; 

                    }

                return $button;
              }
            })
            ->addColumn('action', function($data) use($id_versi){

               //untuk pak didi sama pak suryo dan KETUA YAYASAN tidak perlu mengisi form penilaian kerja
              if (Auth::user()->id == 52 || Auth::user()->id == 124 || Auth::user()->level == 13) {

                $button = '<hr style="width:10%; border:2px solid grey;">';
                return $button;

              }else{


                if($this->cek_akses('93') == 'yes'){
                    
                    if ($this->CekSediaJawaban(Auth::user()->id, 'a', $id_versi) > 0) {

                        if ($this->CekStatusSubmit(Auth::user()->id, $id_versi) > 0) {
                               $button =  '<a href="#" title="Form Penilaian Kerja ">
                                <button type="button" class="btn btn-success btn-sm" onClick="alert(\'Data jawaban sudah dikirim!\')">
                                   Form A <span class="fa fa-check-circle"> </span></button>
                              </a> | ';
                        }else{

                            $button = '<a href="'.Route('FormPenilaianEdit',['type' => 'a','id_user' => Auth::user()->id, 'versi_soal' => $id_versi]).'" title="Form Penilaian Kerja Tipe A">
                                <button type="button" class="btn btn-success btn-sm" >
                                   Form A <span class="fa fa-check-circle"> </span></button>
                              </a> | ';

                        }

                    }else{
                         $button = '<a href="'.Route('FormPenilaian',['type' => 'a', 'versi_soal' => $id_versi]).'" title="Form Penilaian Kerja Tipe A">
                            <button type="button" class="btn btn-outline-primary btn-sm" >
                              <span class="setgrow fa fa-book"> </span>&nbsp; Form A </button>
                          </a> | ';
                    }
                }else{
                    $button = '<a href="#" title="Form Penilaian Kerja ">
                            <button type="button" class="btn btn-warning btn-sm" onClick="alert(\'Tidak Ada Akses!\')">
                              <span class="fa fa-exclamation-circle"> </span> Form A </button>
                          </a> | ';
                }

                if($this->cek_akses('94') == 'yes'){     


                    if ($this->CekSediaJawaban(Auth::user()->id, 'b', $id_versi) > 0) {

                        if ($this->CekStatusSubmit(Auth::user()->id, $id_versi) > 0) {
                               $button .=  '<a href="#" title="Form Penilaian Kerja ">
                                <button type="button" class="btn btn-success btn-sm" onClick="alert(\'Data jawaban sudah dikirim!\')">
                                   Form B <span class="fa fa-check-circle"> </span></button>
                              </a> | ';
                        }else{

                            $button .= '<a href="'.Route('FormPenilaianEdit',['type' => 'b','id_user' => Auth::user()->id, 'versi_soal' => $id_versi]).'" title="Form Penilaian Kerja Tipe B">
                                <button type="button" class="btn btn-success btn-sm" >
                                   Form B <span class="fa fa-check-circle"> </span></button>
                              </a> | ';
                        }


                    }else{
                        $button .= '<a href="'.Route('FormPenilaian',['type' => 'b', 'versi_soal' => $id_versi]).'" title="Form Penilaian Kerja Tipe B">
                        <button type="button" class="btn btn-outline-primary btn-sm" >
                          <span class="setgrow fa fa-book"> </span>&nbsp; Form B </button>
                        </a> | ';
                    }
                }else{
                    $button .= '<a href="#" title="Form Penilaian Kerja">
                            <button type="button" class="btn btn-warning btn-sm" onClick="alert(\'Tidak ada akses atau mungkin hanya bisa diakses oleh bagian tertentu\')">
                              <span class="fa fa-exclamation-circle"> </span> Form B </button>
                          </a> | ';
                }


                if($this->cek_akses('95') == 'yes'){        
                    if ($this->CekSediaJawaban(Auth::user()->id, 'c', $id_versi) > 0) {

                        if ($this->CekStatusSubmit(Auth::user()->id, $id_versi) > 0) {
                           $button .=  '<a href="#" title="Form Penilaian Kerja ">
                            <button type="button" class="btn btn-success btn-sm" onClick="alert(\'Data jawaban sudah dikirim!\')">
                               Form C <span class="fa fa-check-circle"> </span></button>
                          </a> | ';

                        }else{
                            
                            $button .= '<a href="'.Route('FormPenilaianEdit',['type' => 'c','id_user' => Auth::user()->id, 'versi_soal' => $id_versi]).'" title="Form Penilaian Kerja Tipe C">
                                <button type="button" class="btn btn-success btn-sm" >
                                   Form C <span class="fa fa-check-circle"> </span></button>
                              </a> | ';

                        }

                    }else{
                        $button .= '<a href="'.Route('FormPenilaian',['type' => 'c', 'versi_soal' => $id_versi]).'" title="Form Penilaian Kerja Tipe C">
                            <button type="button" class="btn btn-outline-primary btn-sm" >
                              <span class="setgrow fa fa-book"> </span>&nbsp; Form C </button>
                          </a> | ';
                    }
                }else{
                    $button .= '<a href="#" title="Form Penilaian Kerja">
                            <button type="button" class="btn btn-warning btn-sm" onClick="alert(\'Tidak Ada Akses!\')">
                              <span class="fa fa-exclamation-circle"> </span> Form C </button>
                          </a> | ';
                }

                if($this->cek_akses('96') == 'yes'){

                    if ($this->CekSediaJawaban(Auth::user()->id, 'd', $id_versi) > 0) {

                        if ($this->CekStatusSubmit(Auth::user()->id, $id_versi) > 0) {
                           $button .=  '<a href="#" title="Form Penilaian Kerja ">
                            <button type="button" class="btn btn-success btn-sm" onClick="alert(\'Data jawaban sudah dikirim!\')">
                              Form D  <li class="fa fa-check-circle"> </li></button>
                          </a> ';

                        }else{
                            
                            $button .= '<a href="'.Route('FormPenilaianEdit',['type' => 'd','id_user' => Auth::user()->id, 'versi_soal' => $id_versi]).'" title="Form Penilaian Kerja Tipe D">
                                <button type="button" class="btn btn-success btn-sm" >
                                   Form D <span class="fa fa-check-circle"> </span></button>
                              </a>';
                        }

                    }else{
                        $button .= '<a href="'.Route('FormPenilaian',['type' => 'd', 'versi_soal' => $id_versi]).'" title="Form Penilaian Kerja Tipe D">
                            <button type="button" class="btn btn-outline-primary btn-sm" >
                              <span class="setgrow fa fa-book"> </span>&nbsp; Form D </button>
                          </a>';    
                    }
                }else{
                    $button .= '<a href="#" title="Form Penilaian Kerja Tipe D">
                            <button type="button" class="btn btn-warning btn-sm" onClick="alert(\'Tidak Ada Akses!\')">
                              <span class="fa fa-exclamation-circle"> </span> Form D </button>
                          </a>';
                }
                

                return $button;

              }

            })
            ->addColumn('status', function($data) use($id_versi){


               //untuk pak didi sama pak suryo dan Ketua YAYASAN tidak perlu mengisi form penilaian kerja
              if (Auth::user()->id == 52 || Auth::user()->id == 124 || Auth::user()->level == 13) {

                
                $button = '<hr style="width:10%; border:2px solid grey;">';

                return $button;


              }else{
                if ($data->level != 13) {

                        $cek_sedia = DB::table('b_status')->where([['id_user','=', Auth::user()->id],['id_versi','=',$id_versi]])->count();

                        if ($cek_sedia > 0) {
                            $button = '<a href="#" title="Status Selesai">
                                <button type="button" class="btn btn-success btn-sm btn-block" onClick="alert(\'Anda telah selesai\')">
                                   Selesai <span class="fa fa-check-circle"> </span></button>
                              </a>';  
                        }else{

                            $button = '<a href="#" title="Status Selesai">
                                <button type="button" class="ajukan btn btn-outline-primary btn-sm btn-block" >
                                  <span class="fa fa-cloud-upload-alt"> </span> Ajukan </button>
                              </a>';    
                        }
                    }else{
                        $button = '<a href="#" title="Ajukan Form">
                                    <button type="button" class="btn btn-warning btn-sm"  onClick="alert(\'user ini tidak perlu mengajukan apapun\')">
                                      <span class="fa fa-cloud-upload-alt"> </span> Ajukan</button>
                                  </a>'; 
                    }
                    

                    return $button;
                  }

            })
            ->addColumn('print_verif', function($data) use($id_versi){

                    $button = '<a href="'.Route('printverif',['id_user' => Crypt::encryptString($data->id_user), 'id_versi' => $id_versi]).'" title="Print Hasil Verifikasi" target="_blank">
                        <button type="button" class="btn btn-outline-info btn-sm" >
                          <span class="fa fa-print"> </span> Print Hasil Verifikasi </button>
                      </a>';  
            

                return $button;

            })
       
            ->rawColumns(['action','datadiri','status','hasil_penilaian','print_verif','nilai_atasan','AksiLainnya'])
            ->make(true);

           
    }

    //CEK KETESEDIAAN JAWABAN MENILAI ATASAN
    protected function CekJawabanMenilaiAtasan($id_user, $id_versi){

        // NOTE * VERSI SOAL YANG AKTIF, DIDAPAT DARI HASIL JOIN SOAL DAN JAWABAN
        $cekData = DB::table('b_jawaban')
                            ->join('b_soal','b_soal.id_soal','=','b_jawaban.id_soal')
                            ->where([['b_jawaban.id_user','=',$id_user],['b_soal.id_versi_fk','=',$id_versi],['b_jawaban.jenis_jawaban','=','nilai_atasan']])->count();

        return $cekData;

    }

    //CEK KETERSEDIAAN FILE PELAKSANAAN TUGAS LAIN
    protected function CekSediaPTL($id_user, $versi){
        $CekSedia = DB::table('b_pelaksanaan_tugas_lain')->where([['id_user','=',$id_user],['id_versi','=',$versi]])->count();
        if ($CekSedia > 0) {
            return 'yes';
        }else{
            return 'no';
        }
    }
    //MEMILIH VERSI SOAL DENGAN STATUS AKTIF
    protected function VersiSoal(){
      $versi = DB::table('b_versi_soal')->select('status_aktif','tahun','id')->where('status_aktif','=','1')->first();
      return $versi;
    }

    
    //CEK STATUS SUBMIT JAWABAN
    protected function CekStatusSubmit($id_user,$versi){
      $cek_sedia = DB::table('b_status')->where([['id_user','=', $id_user],['id_versi','=',$versi]])->count();
      return $cek_sedia;
    }

    // CEK KETERSEDIAAN JAWABAN
    protected function CekSediaJawaban($id_user, $type, $versi){  

        $cek_jawaban = DB::table('b_jawaban')
                    ->join('b_soal','b_jawaban.id_soal','=','b_soal.id_soal')
                    ->select('b_soal.kategori_soal','b_jawaban.id_user','b_jawaban.jawaban','b_jawaban.id_soal')
                    ->where([['b_jawaban.id_user','=', $id_user], ['b_soal.kategori_soal','=', $type],['b_soal.id_versi_fk','=', $versi],['b_jawaban.jenis_jawaban','!=','nilai_atasan']])->count();

        return $cek_jawaban;

    }


    public function index_penilaian_kerja_cek(){
             
        $cek_pantas = DB::table('b_data_diri')->where('id_user','=',Auth::user()->id)->count();

        if ($cek_pantas > 0 || Auth::user()->level == 13) {
            return view('admin.dashboard.penilaiankerja.cek_index',['id_versi' => $this->VersiSoal()->id, 'tahun' => $this->VersiSoal()->tahun]);
        }else{
            return redirect()->route('add_datadiri');
        }   
        

    }
       

    public function index_penilaian_kerja(){

        $Ddiri = DB::table('b_data_diri')
        ->join('provinsi','b_data_diri.provinsi_lahir','=','provinsi.id_prov')
        ->join('kabupaten','b_data_diri.kota_lahir','=','kabupaten.id_kab')
        ->leftJoin('27_9_kelurahan','27_9_kelurahan.subdis_id','=','b_data_diri.kelurahan_domisili')
        ->leftJoin('27_9_kecamatan','27_9_kecamatan.dis_id','=','b_data_diri.kecamatan_domisili')
        ->select('b_data_diri.*','provinsi.*','kabupaten.*','27_9_kelurahan.*','27_9_kecamatan.*')
        ->where('b_data_diri.id_user','=',Auth::user()->id)
        ->first();

        if (is_null($Ddiri)) {
             return redirect()->route('IndexPenilaianKerjaCek');
        }

        $identitas_lainnya =  DB::table('b_identitas_lainnya')
        ->select('*')
        ->where('id_user','=',Auth::user()->id)
        ->get();


        $KontakDarurat = DB::table('b_kontak_darurat')->where('id_user','=',Auth::user()->id)->get();

        $marital =  DB::table('b_marital')
        ->select('*')
        ->where('id_user','=',Auth::user()->id)
        ->get();

        $maritalpasangan =  DB::table('b_marital_pasangan')
        ->select('*')
        ->where('id_user','=',Auth::user()->id)
        ->get();

        // -- Jabatan OLD --//
        // $Jabatan = DB::table('b_jabatan')
        // ->join('b_set_jabatan','b_jabatan.nama_jabatan','=','b_set_jabatan.id_set_jabatan')
        // ->join('b_set_detail_jabatan','b_jabatan.sub_jabatan','=','b_set_detail_jabatan.id_detail_jabatan')
        // ->select('b_jabatan.*','b_set_jabatan.nama_jabatan','b_set_detail_jabatan.nama_detail_jabatan',DB::raw('b_jabatan.nama_jabatan AS b_jabatan_id'))
        // ->where('b_jabatan.id_user','=',Auth::user()->id)
        // ->get();

        // -- Jabatan Setup 2021 --//
        $Jabatan = DB::table('jabatan_pegawai')
        ->join('pegawai','pegawai.id_pegawai','=','jabatan_pegawai.id_pegawai_fk')
        ->join('b_set_detail_jabatan','b_set_detail_jabatan.id_detail_jabatan','=','jabatan_pegawai.detail_jabatan')
        ->Join('b_set_jabatan','b_set_jabatan.id_set_Jabatan','=','b_set_detail_jabatan.id_set_jabatan')
        ->select('jabatan_pegawai.nm_jabatan','jabatan_pegawai.id_jabatan','b_set_jabatan.nama_jabatan')
        ->where('pegawai.id_user','=',Auth::user()->id)
        ->get();

  
        $JabatanLainnya = DB::table('b_jabatan_tambahan')
        ->select('nama_tambahan_jabatan')
        ->where('b_jabatan_tambahan.id_user','=',Auth::user()->id)
        ->get();

        $jab_akademik = DB::table('b_serdos')
        ->join('b_jabatan_akademik','b_serdos.jabatan_akademik','=','b_jabatan_akademik.id_jabatan_akademik')
        ->select('b_serdos.*','b_jabatan_akademik.nama_jab_akademik')
        ->where('b_serdos.id_user','=',Auth::user()->id)
        ->get();

        $sma = DB::table('b_sma_sederajat')
        ->select('*')
        ->where('b_sma_sederajat.id_user','=',Auth::user()->id)
        ->get();

        $perting = DB::table('b_perguruan_tinggi')
        ->select('*')
        ->where('b_perguruan_tinggi.id_user','=',Auth::user()->id) 
        ->get();

        //PROVINSI DAN KABUPATEN MENGGUNAKAN YANG LAMA, MENGIKUTI DATA LAMA
        $list_provinsi = DB::table('provinsi')->select('*')->get();
        
        $list_kecamatan = DB::table('27_9_kecamatan')
            ->select('27_9_kecamatan.dis_id','27_9_kecamatan.dis_name')
            ->orderBy('27_9_kecamatan.dis_name','ASC')
            ->where('27_9_kecamatan.city_id','=','148')
            ->get();
        
        $jabatanlist = DB::table('b_set_jabatan')->select('id_set_jabatan','nama_jabatan')->get();

        $cek_jabatanakademik = DB::table('b_jabatan_akademik')
        ->select('id_jabatan_akademik','nama_jab_akademik')
        ->get();
        


        return view('admin.dashboard.penilaiankerja.index',['Ddiri' => $Ddiri, 'jabatan' => $Jabatan, 'iden' => $identitas_lainnya, 'marital' => $marital, 'maritalpasangan' => $maritalpasangan,'jabakademik' => $jab_akademik, 'sma' => $sma, 'perting' => $perting, 'list_provinsi' => $list_provinsi, 'jabatanlist' => $jabatanlist,'jab_aka' => $cek_jabatanakademik,'jabatanlainnya' => $JabatanLainnya, 'KontakDarurat' => $KontakDarurat,'kecamatan_domisili' => $list_kecamatan]);

    }

    public function getdata_penilaiankerja(){


        return DataTables::of(DB::table('b_data_diri')->select('*')->where('id_user','=', Auth::user()->id)
        ->join('provinsi','b_data_diri.provinsi_lahir','=','provinsi.id_prov')
        ->join('kabupaten','b_data_diri.kota_lahir','=','kabupaten.id_kab')
        ->leftJoin('27_9_kecamatan','b_data_diri.kecamatan_domisili','=','27_9_kecamatan.dis_id')
        ->leftJoin('27_9_kelurahan','b_data_diri.kelurahan_domisili','=','27_9_kelurahan.subdis_id')
        ->select('b_data_diri.*','provinsi.*','kabupaten.*','27_9_kecamatan.*','27_9_kelurahan.*'))

        ->addIndexColumn()
        ->addColumn('action', function($data){

                        
                        $button = '<a href="#" title="Edit Data">
                                    <button type="button" class="edit_datadiri btn btn-outline-success btn-xs"

                                    data_namalengkap="'.$data->nama_lengkap.'"
                                    data_mandarin="'.$data->nama_mandarin.'"
                                    nomor_ktp="'.$data->nomor_ktp.'"
                                    durasi_ktp="'.$data->durasi_ktp.'"
                                    nomor_npwp="'.$data->nomor_npwp.'"
                                    provinsi_lahir="'.$data->provinsi_lahir.'"
                                    kota_lahir="'.$data->kota_lahir.'"
                                    tanggal_lahir="'.$data->tanggal_lahir.'"

                                    nomor_telepon="'.$data->nomor_telepon.'"
                                    nomor_telepon_2="'.$data->nomor_telepon_2.'"
                                    nomor_wa="'.$data->nomor_wa.'"

                                    ><span class="fa fa-pencil-alt"> </span></button>
                                    </a>';
                        
                      

                        return $button;

                    })


        ->rawColumns(['action'])
        ->make(true);
     


    }

    public function add_data_diri(){

        $cek_pantas = DB::table('b_data_diri')->where('id_user','=',Auth::user()->id)->count();

        if ($cek_pantas > 0 || Auth::user()->level == 13) {
             return redirect()->route('IndexPenilaianKerjaCek');
        }else{
    
        	//LIST PROVINSI

            $list_provinsi = DB::table('provinsi')->select('*')->get();
            //TABEL LAMA
        	//$list_provinsi = DB::table('provinsi')->select('*')->get();
        	$list_jab_pendidik = DB::table('b_set_jabatan')->select('id_set_jabatan','nama_jabatan')->where('kategori','=','Pendidik')->get();

        	$list_jab_t_kependidikan = DB::table('b_set_jabatan')->select('id_set_jabatan','nama_jabatan')->where('kategori','=','Tenaga Kependidikan')->get();

            $list_kecamatan = DB::table('27_9_kecamatan')
            ->select('27_9_kecamatan.dis_id','27_9_kecamatan.dis_name')
            ->orderBy('27_9_kecamatan.dis_name','ASC')
            ->where('27_9_kecamatan.city_id','=','148')
            ->get();

        	return view('admin.dashboard.penilaiankerja.data_pegawai',['list_provinsi' => $list_provinsi,'list_jab_pendidik' => $list_jab_pendidik,'list_jab_t_kependidikan' => $list_jab_t_kependidikan, 'kecamatan_domisili' => $list_kecamatan]);
    
        }

    }

     //CHAINED KECAMATAN UNTUK DOMISILI
    public function KecamatanDomisili(Request $request){

        # Inisialisasi variabel berdasarkan masing-masing tabel dari model
        # dimana ID target sama dengan ID inputan
        $CekKelurahan = DB::table('27_9_kelurahan')
        ->select('subdis_id','subdis_name')
        ->where('dis_id','=', $request->id)
        ->get();

        # Buat pilihan "Switch Case" berdasarkan variabel "type" dari form
        switch(Input::get('type')):
            # untuk kasus "kabupaten"
            case 'Kecamatan':
                # buat nilai default
                $return = '';
                # lakukan perulangan untuk tabel kabupaten lalu kirim
                foreach($CekKelurahan as $key ) 
                    # isi nilai return
                    $return .= "<option value='$key->subdis_id'>$key->subdis_name</option>";
                  # kirim
                return $return;
            break;
        # pilihan berakhir
        endswitch;    

    }


    public function kabupatenkota() {   
        # Tarik ID inputan
        $set = Input::get('id');

        # Inisialisasi variabel berdasarkan masing-masing tabel dari model
        # dimana ID target sama dengan ID inputan
        $cekkabupatenkota = DB::table('kabupaten')
        ->select('*')
        ->where('id_prov','=', $set)
        ->get();

        # Buat pilihan "Switch Case" berdasarkan variabel "type" dari form
        switch(Input::get('type')):
            # untuk kasus "kabupaten"
            case 'kabupaten':
                # buat nilai default
                $return = '';
                # lakukan perulangan untuk tabel kabupaten lalu kirim
                foreach($cekkabupatenkota as $key ) 
                    # isi nilai return
                    $return .= "<option value='$key->id_kab'>$key->nama_kab</option>";
                  # kirim
                return $return;
            break;
        # pilihan berakhir
        endswitch;    
    }

    public function sub_jabatanpendidik() {   
        # Tarik ID inputan
        $set = Input::get('id');

        # Inisialisasi variabel berdasarkan masing-masing tabel dari model
        # dimana ID target sama dengan ID inputan
        $cek_sub = DB::table('b_set_detail_jabatan')
        ->select('id_detail_jabatan','id_set_jabatan','nama_detail_jabatan')
        ->where('id_set_jabatan', $set)
        ->get();

        # Buat pilihan "Switch Case" berdasarkan variabel "type" dari form
        switch(Input::get('type')):
            # untuk kasus "kabupaten"
            case 'sub_jabatanpendidik':
                # buat nilai default
                $return = '<option value="">Silahkan Pilih Sub Jabatan...</option>';
                # lakukan perulangan untuk tabel kabupaten lalu kirim
                foreach($cek_sub as $key ) 
                    # isi nilai return
                    $return .= "<option value='$key->id_detail_jabatan'>$key->nama_detail_jabatan</option>";
                  # kirim
                return $return;
            break;
        # pilihan berakhir
        endswitch;    
    }

    public function jabatan_akademik() {   
        # Tarik ID inputan
        $set = Input::get('id');

        # Inisialisasi variabel berdasarkan masing-masing tabel dari model
        # dimana ID target sama dengan ID inputan
        $cek_sub = DB::table('b_jabatan_akademik')
        ->select('id_jabatan_akademik','nama_jab_akademik')
        //->where('id_set_jabatan', $set)
        ->get();

        # Buat pilihan "Switch Case" berdasarkan variabel "type" dari form
        switch(Input::get('type')):
            # untuk kasus "kabupaten"
            case 'sub_jabatanakademik':
                # buat nilai default
                $return = '<option value="">Silahkan Pilih Jabatan Akademik...</option>';
                # lakukan perulangan untuk tabel kabupaten lalu kirim
                foreach($cek_sub as $key ) 
                    # isi nilai return
                    $return .= "<option value='$key->id_jabatan_akademik'>$key->nama_jab_akademik</option>";
                  # kirim
                return $return;
            break;
        # pilihan berakhir
        endswitch;    
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
