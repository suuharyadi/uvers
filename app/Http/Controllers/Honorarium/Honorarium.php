<?php

namespace App\Http\Controllers\Honorarium;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Collection;

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
Use Exception;
use DateTime;

class Honorarium extends Controller
{   



    public function print_honorarium(){
  

    $a_last_print = DB::table('a_last_honorarium')
                ->join('a_berkas_scan_buff','a_berkas_scan_buff.id_berkas_buff','=','a_last_honorarium.id_berkas_buff')
                ->join('pegawai','pegawai.id_pegawai','=','a_last_honorarium.nama_dosen')
                ->select('a_last_honorarium.*','a_berkas_scan_buff.nama_lampiran','pegawai.nama_karyawan')
                ->orderBy('pegawai.nama_karyawan','ASC')
                ->get();

    $hasil_ta = DB::table('a_last_honorarium')
                ->join('pegawai','pegawai.id_pegawai','=','a_last_honorarium.nama_dosen')
                ->select('pegawai.nama_karyawan',DB::raw('sum(pembayaran) as hasil, a_last_honorarium.nama_dosen'))
                ->groupBy('a_last_honorarium.nama_dosen','pegawai.nama_karyawan')
                ->orderBy('pegawai.nama_karyawan','ASC')
                ->get();


        return view('admin.dashboard.honorarium.print_honorarium',
                [   
                    'data_print' => $a_last_print,
                    'hasil' => $hasil_ta
                    
                ]);

    }



    //detail honorarium/keranjang
    public function detail_honorarium(){

        $a_last = DB::table('a_last_honorarium')
                ->join('a_berkas_scan_buff','a_berkas_scan_buff.id_berkas_buff','=','a_last_honorarium.id_berkas_buff')
                ->join('pegawai','pegawai.id_pegawai','=','a_last_honorarium.nama_dosen')
                ->select('a_last_honorarium.*','a_berkas_scan_buff.nama_lampiran','pegawai.nama_karyawan')
                ->orderBy('id','ASC')
                ->get();

        /*$a_cek_group = DB::table('a_last_honorarium')
                        ->select('id_berkas_buff')
                        ->orderBy('id','DESC')
                        ->groupBy('id_berkas_buff')
                        ->get();

        dd($a_cek_group);*/

        return view('admin.dashboard.honorarium.detail_honorarium',['index' => $a_last]);


    }

    //detail honorarium/keranjang destroy
    public function destroy_detail_honorarium($id, $id_berkas_buff){

        $tes_c = DB::table('a_last_honorarium')->where('id_berkas_buff', '=', $id_berkas_buff)
                ->count();


        try {
            
            DB::table('a_last_honorarium')->where('id', '=', $id)->delete();
            
            if($tes_c == 1){

                DB::table('a_berkas_scan_buff')
                        ->where('id_berkas_buff', $id_berkas_buff)
                        ->update(['pilihan_print' => 0]);

            }

            return Redirect::back()->with('success', 'Berhasil Hapus Data');

        } catch (Exception $ex) {
            report($ex);

            return Redirect::back()->with('error', 'Terjadi Kesalahan');
        }



    }


    //SETTING HONORARIUM
    public function index_setting(){ 

        $dosencek = DB::table('pegawai')->select('id_pegawai','nama_karyawan')->get();

        return view('admin.dashboard.honorarium.index_setting',['dosen' => $dosencek]);

    }
    //embed HONORARIUM
    public function embed_honor(){ 

        //$dosencek = DB::table('pegawai')->select('id_pegawai','nama_karyawan')->get();

        return view('admin.dashboard.honorarium.untuk_embed');

    }

    //GET DATA SETTING HONORARIUM
    public function getdata_setting_honorarium(){


        return DataTables::of(DB::table('a_honor_pegawai')
        ->join('pegawai','pegawai.id_pegawai','=','a_honor_pegawai.id_pegawai_fk2')
        ->select(   'a_honor_pegawai.id_honor',
                    'a_honor_pegawai.id_pegawai_fk2',
                    'a_honor_pegawai.status_dosen',
                    'a_honor_pegawai.jabatan_fungsional',
                    'a_honor_pegawai.p_t_a_satu',
                    'a_honor_pegawai.p_t_a_dua',
                    'a_honor_pegawai.p_tugas_akhir',
                    'a_honor_pegawai.p_seminar_p_t_a',
                    'a_honor_pegawai.pkp',                    
                    'pegawai.nama_karyawan',
                    'pegawai.id_pegawai'
                )
        ->orderBy('a_honor_pegawai.id_honor','DESC')

        )
        ->addIndexColumn()
        
        ->addColumn('action', function($data){
                
            $button = '<a href="'.Route('edit_set_honor',['id' => $data->id_honor]).'" title="Edit Data">
                        <button type="button" class="btn btn-outline-primary btn-xs"><span class="fa fa-edit"> </span></button>
                        </a> | ';
            $button .= '<a href="'.Route('destroy_set_honor',['id' => $data->id_honor]).'" title="Hapus Data" onclick="return confirm(\'Apakah Anda Yakin Menghapus Data Ini ? \' ) ">
                        <button type="button" class="btn btn-outline-danger btn-xs"><span class="fa fa-trash"> </span></button>
                        </a>';

           
            return $button;
            
            })
        

        ->rawColumns(['action'])
        ->make(true);
                    
    }


    //tambah data setting honorarium
    public function add_set_honor(Request $request){

        $tes_c = DB::table('a_honor_pegawai')->where('id_pegawai_fk2', '=', $request->id_pegawai)
                ->count();

        if ($tes_c > 0) {
            return Response::json(array(
                                    'success' => false,
                                    'errors' => 'Terjadi kesalahan #ldjnrgoie',
                                ), 400);
        }

        try {

        DB::table('a_honor_pegawai')->insert([
                [   
                    'id_pegawai_fk2' => $request->id_pegawai,
                    'jabatan_fungsional' => $request->jabatan_fungsional,
                    'p_t_a_satu' => $request->pta1,
                    'p_t_a_dua' => $request->pta2,
                    'p_tugas_akhir' => $request->peng_ta,
                    'p_seminar_p_t_a' => $request->peng_s_ta,
                    'pkp' =>  $request->pkp,
                    'created_at' => \Carbon\Carbon::now()
                ],
            ]);

            return Response::json(array(
                                    'success' => 'Berhasil',
                                    'errors' => false,
                                ), 200);


        } catch (Exception $e) {

            return Response::json(array(
                                    'success' => false,
                                    'errors' => 'Terjadi kesalahan #ldjnrgoie',
                                ), 200);
        }
    }

    public function destroy_set_honor($id){

        if($this->cek_akses('91') == 'yes'){

            try {

                DB::table('a_honor_pegawai')->where('id_honor', '=', $id)->delete();
                return Redirect::back()->with('success', 'Berhasil');

                } catch (Exception $e) {
                    return Redirect::back()->with('error', 'Terjadi Kesalahan #oj3');
            }

        }else{ 
            return Redirect::back()->with('error', 'Tidak Ada Akses Untuk menghapus Data Ini');
        } 

      

    }


    public function edit_set_honor($id){

        $h_pegawai = DB::table('a_honor_pegawai')
                    ->select('a_honor_pegawai.*','pegawai.nama_karyawan')
                    ->join('pegawai','pegawai.id_pegawai','=','a_honor_pegawai.id_pegawai_fk2')
                    ->where('id_honor','=',$id)
                    ->first();
        //dd($h_pegawai);

        return view('admin.dashboard.honorarium.edit_setting',['honor' => $h_pegawai]);

    }

    
    public function put_set_honor(Request $request){

         try {

              DB::table('a_honor_pegawai')
                    ->where('id_honor', $request->id_honor)
                    ->update([

                            'jabatan_fungsional' => $request->jabatan_fungsional,
                            'p_t_a_satu' => $request->pta1,
                            'p_t_a_dua' => $request->pta2,
                            'p_tugas_akhir' => $request->peng_ta,
                            'p_seminar_p_t_a' => $request->peng_s_ta,
                            'pkp' => $request->pkp,
                            'updated_at' => \Carbon\Carbon::now()

                        ]);

            if(
                isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                strcasecmp($_SERVER['HTTP_X_REQUESTED_WITH'], 'xmlhttprequest') == 0
            ){
                  return Response::json(array('ceks' => 'Berhasil', 'errors' => false, ), 200);

            }else{
                return Redirect::back()->with('success', 'Berhasil Mengubah Data');
            }
        } catch (Exception $e) {
            return Redirect::back()->with('error', 'Terjadi Kesalahan #ove5j3');
        }

    }

    //set cek dosen
    public function cek_dos_honorarium(Request $request){

        if(is_null($request->id_berkas_buff)){
            return Redirect::back()->with('error', 'Minimal pilih 1');
        }

        for ($i = 0; $i < count($request->id_berkas_buff); $i++) {

            $CekKategorixPilihanPrint = DB::table('a_berkas_scan_buff')->select('kategori_buff','pilihan_print')->where('id_berkas_buff','=',$request->id_berkas_buff[$i])->first();

             if ($CekKategorixPilihanPrint->kategori_buff == 'surat_keterangan_selesai') {

                 $sks[] = DB::table('a_berkas_scan_buff')

                            ->join('a_surat_keterangan_selesai','a_surat_keterangan_selesai.id_sks','=','a_berkas_scan_buff.id_data_buff')
                            ->join('a_sks_dp','a_sks_dp.id_sks_selesai','=','a_surat_keterangan_selesai.id_sks')
                            ->join('pegawai','pegawai.id_pegawai','=','a_sks_dp.id_dosen')
                            ->leftJoin('a_honor_pegawai','a_honor_pegawai.id_pegawai_fk2','=','pegawai.id_pegawai')
                            ->join('a_tbl_mhs','a_tbl_mhs.id_mhs','=','a_surat_keterangan_selesai.mahasiswa')
                            ->join('a_prodi','a_prodi.id_prodi','=','a_surat_keterangan_selesai.prodi')
                            ->join('a_nama_mk','a_nama_mk.id_mk','=','a_surat_keterangan_selesai.nama_mk')
                           
                            ->select('a_sks_dp.id_dosen',
                                    'pegawai.nama_karyawan',
                                    'pegawai.id_pegawai',
                                    'a_sks_dp.kategori_dosen',
                                    'a_honor_pegawai.jabatan_fungsional',
                                    'a_berkas_scan_buff.kategori_buff',
                                    'a_berkas_scan_buff.id_berkas_buff',

                                    'a_honor_pegawai.pkp',
                                    'a_honor_pegawai.p_t_a_satu',
                                    'a_honor_pegawai.p_t_a_dua',
                                    'a_honor_pegawai.id_honor',

                                    'a_surat_keterangan_selesai.mulai',
                                    'a_surat_keterangan_selesai.selesai',
                                    'a_surat_keterangan_selesai.judul',

                                    'a_tbl_mhs.nama',
                                    'a_tbl_mhs.id_mhs',
                                    'a_prodi.nama_prodi',
                                    'a_nama_mk.jenis_mk',
                                    'a_nama_mk.nama_mk')
                            ->where([
                                        ['a_berkas_scan_buff.id_berkas_buff','=',$request->id_berkas_buff[$i]],
                                        ['a_sks_dp.kategori_dosen','=','Pembimbing']
                                    ])
                            ->orderBy('a_sks_dp.id','ASC')
                            ->get();



        }elseif($CekKategorixPilihanPrint->kategori_buff == 'sidang tugas akhir'){

            $sta[] = DB::table('a_berkas_scan_buff')

                        ->join('a_srt_udg_penguji','a_srt_udg_penguji.id_undangan','=','a_berkas_scan_buff.id_data_buff')
                        ->join('a_udg_dp','a_udg_dp.id_udg','=','a_srt_udg_penguji.id_undangan')
                        ->join('pegawai','pegawai.id_pegawai','=','a_udg_dp.id_dosen')
                        ->leftJoin('a_honor_pegawai','a_honor_pegawai.id_pegawai_fk2','=','pegawai.id_pegawai')
                        ->join('a_berkas_adm','a_berkas_adm.id_jenisberkas','=','a_srt_udg_penguji.id_berkas_adm')
                        ->join('a_tbl_mhs','a_tbl_mhs.id_mhs','=','a_srt_udg_penguji.id_mhs')
                        ->join('a_prodi','a_prodi.id_prodi','=','a_srt_udg_penguji.id_prodi')
                        ->join('a_nama_mk','a_nama_mk.id_mk','=','a_srt_udg_penguji.id_nama_mk')
                       
                        ->select('a_udg_dp.id_dosen',
                                'pegawai.id_pegawai',
                                'pegawai.nama_karyawan',
                                'a_udg_dp.kategori_dosen',
                                'a_honor_pegawai.jabatan_fungsional',
                                'a_berkas_scan_buff.kategori_buff',
                                'a_berkas_scan_buff.id_berkas_buff',
                                'a_honor_pegawai.p_tugas_akhir',
                                'a_honor_pegawai.p_seminar_p_t_a',
                                'a_honor_pegawai.id_honor',

                                'a_srt_udg_penguji.tanggal_pelaksanaan',
                                'a_srt_udg_penguji.jam_mulai',
                                'a_srt_udg_penguji.judul',

                                'a_tbl_mhs.nama',
                                'a_tbl_mhs.id_mhs',
                                'a_prodi.nama_prodi',

                                'a_nama_mk.jenis_mk',
                                'a_nama_mk.nama_mk')
                        ->where([
                                    ['a_berkas_scan_buff.id_berkas_buff','=',$request->id_berkas_buff[$i]],
                                    ['a_udg_dp.kategori_dosen','=','Penguji']
                                ])
                        ->orderBy('a_udg_dp.id','ASC')
                        ->get();

        }elseif($CekKategorixPilihanPrint->kategori_buff == 'seminar proposal'){

                $sp[] = DB::table('a_berkas_scan_buff')

                            ->join('a_srt_udg_penguji','a_srt_udg_penguji.id_undangan','=','a_berkas_scan_buff.id_data_buff')
                            ->join('a_udg_dp','a_udg_dp.id_udg','=','a_srt_udg_penguji.id_undangan')
                            ->join('pegawai','pegawai.id_pegawai','=','a_udg_dp.id_dosen')
                            ->leftJoin('a_honor_pegawai','a_honor_pegawai.id_pegawai_fk2','=','pegawai.id_pegawai')
                            ->join('a_berkas_adm','a_berkas_adm.id_jenisberkas','=','a_srt_udg_penguji.id_berkas_adm')
                            ->join('a_tbl_mhs','a_tbl_mhs.id_mhs','=','a_srt_udg_penguji.id_mhs')
                            ->join('a_prodi','a_prodi.id_prodi','=','a_srt_udg_penguji.id_prodi')
                            ->join('a_nama_mk','a_nama_mk.id_mk','=','a_srt_udg_penguji.id_nama_mk')
                           
                            ->select('a_udg_dp.id_dosen',
                                    'pegawai.id_pegawai',
                                    'pegawai.nama_karyawan',
                                    'a_udg_dp.kategori_dosen',
                                    'a_honor_pegawai.jabatan_fungsional',
                                    'a_berkas_scan_buff.kategori_buff',
                                    'a_berkas_scan_buff.id_berkas_buff',
                                    'a_honor_pegawai.p_tugas_akhir',
                                    'a_honor_pegawai.p_seminar_p_t_a',
                                    'a_honor_pegawai.id_honor',
                                    'a_srt_udg_penguji.tanggal_pelaksanaan',
                                    'a_srt_udg_penguji.jam_mulai',
                                    'a_srt_udg_penguji.judul',
                                    'a_tbl_mhs.nama',
                                    'a_tbl_mhs.id_mhs',
                                    'a_prodi.nama_prodi',

                                    'a_nama_mk.jenis_mk',
                                    'a_nama_mk.nama_mk')
                            ->where([
                                        ['a_berkas_scan_buff.id_berkas_buff','=',$request->id_berkas_buff[$i]],
                                        ['a_udg_dp.kategori_dosen','=','Penguji']
                                    ])
                            ->orderBy('a_udg_dp.id','ASC')
                            ->get();
            }
        }

        if (empty($sks)) {
            $sks_cek = 1;
        }else{
            $sks_cek = $sks;

            /*foreach($sks_cek as $key => $showcek) {
                foreach ($showcek as $key2 => $value) {
                    if ($value->id_honor == null) {

                            return Redirect::back()->with('error', 'Pegawai "'.$value->nama_karyawan.'" belum memiliki harga honor');

                    }
                }
            }*/
        }
        if (empty($sta)) {
            $sta_cek = 1;
        }else{
            $sta_cek = $sta;
            /*foreach($sta_cek as $key => $showcek) {
                foreach ($showcek as $key2 => $value) {
                    if ($value->id_honor == null) {

                            return Redirect::back()->with('error', 'Pegawai "'.$value->nama_karyawan.'" belum memiliki harga honor');

                    }
                }
            }*/
        }
        if (empty($sp)) {
            $sp_cek = 1;
        }else{
            $sp_cek = $sp;
            /*foreach($sp_cek as $key => $showcek) {
                foreach ($showcek as $key2 => $value) {
                    if ($value->id_honor == null) {

                            return Redirect::back()->with('error', 'Pegawai "'.$value->nama_karyawan.'" belum memiliki harga honor');

                    }
                }
            }*/
        }

        //dd($sks);
        return view('admin.dashboard.honorarium.cek', ['sks' => $sks_cek,'sta' => $sta_cek,'sp' => $sp_cek]);

    }
    
    //MENGEMBALIKAN NILAI 0 SAAT UNDEFINED INDEX/TIDAK ADA KATEGORI DI DALAM ARRAY
    protected function getIfSet(& $var) {
        if (isset($var)) {
            return $var;
        }
        return 0;
    }

    public function tambah_data_honorarium(Request $request){

                 for ($i = 0; $i < $request->totdat; $i++) {
                     if (is_null($request->input('jabatan_fungsionalsks'.$i.''))) {
                        $Ceknm = DB::table('pegawai')->select('nama_karyawan')->where('id_pegawai','=', $request->input('nama_karyawansks'.$i.''))->first();
                        if (empty($Ceknm)) {
                        }else{  
                            return Response::json(array('cek' => 'gagal','datacek' => ''.$Ceknm->nama_karyawan.' tidak memiliki harga honor | Lokasi : '.$request->input('katbuf'.$i.'').''), 200);
                        }
                    }else{}
                }

                for ($g = 0; $g < $request->totdatsta; $g++) {
                   if (is_null($request->input('jabatan_fungsionalsta'.$g.''))) {
                        $Ceknm = DB::table('pegawai')->select('nama_karyawan')->where('id_pegawai','=', $request->input('nama_karyawansta'.$g.''))->first();
                        if (empty($Ceknm)) {
                        }else{
                            return Response::json(array('cek' => 'gagal','datacek' => ''.$Ceknm->nama_karyawan.' tidak memiliki harga honor | Lokasi : '.$request->input('katbufsta'.$g.'').''), 200);
                        }
                    }else{}
                }

                for ($j = 0; $j < $request->totdatsp; $j++) {
                     if (is_null($request->input('jabatan_fungsionalsp'.$j.''))) {

                        $Ceknm = DB::table('pegawai')->select('nama_karyawan')->where('id_pegawai','=', $request->input('nama_karyawansp'.$j.''))->first();

                       if (empty($Ceknm)) {
                        }else{

                            return Response::json(array('cek' => 'gagal','datacek' => ''.$Ceknm->nama_karyawan.' tidak memiliki harga honor | Lokasi : '.$request->input('katbufsp1'.$j.'').''), 200);
                        } 
                    }else{}


                }


                for ($i = 0; $i < $request->totdat; $i++) {

                    if (is_null($request->input('nama_karyawansks'.$i.''))) {
                       continue;
                    }else{
                            DB::table('a_last_honorarium')->insert([
                                'nama_dosen' => $request->input('nama_karyawansks'.$i.''),
                                'id_berkas_buff' => $request->input('id_berkas_buffsks'.$i.''),
                                'jabatan_fungsional' => $request->input('jabatan_fungsionalsks'.$i.''),
                                'tugas_sebagai' => $request->input('tugas_sebagaisks'.$i.''),
                                'mulai' => $request->input('mulaisks'.$i.''),
                                'selesai' => $request->input('selesaisks'.$i.''),
                                'nama_mahasiswa' => $request->input('nama_mahasiswasks'.$i.''),
                                'prodi' => $request->input('nama_prodisks'.$i.''),
                                'pembayaran' => $request->input('pembayaransks'.$i.''),
                                'created_at' => \Carbon\Carbon::now()
                                ]);

                            DB::table('a_berkas_scan_buff')
                            ->where('id_berkas_buff', $request->input('id_berkas_buffsks'.$i.''))
                            ->update(['pilihan_print' => 2]);
                        }
                    }

                for ($g = 0; $g < $request->totdatsta; $g++) {

                    if (is_null($request->input('nama_karyawansta'.$g.''))) {
                        continue;
                    }else{

                        DB::table('a_last_honorarium')->insert([
                            'nama_dosen' => $request->input('nama_karyawansta'.$g.''),
                            'id_berkas_buff' => $request->input('id_berkas_buffsta'.$g.''),
                            'jabatan_fungsional' => $request->input('jabatan_fungsionalsta'.$g.''),
                            'tugas_sebagai' => $request->input('tugas_sebagaista'.$g.''),
                            'tanggal' => $request->input('tanggal_pelaksanaansta'.$g.''),
                            'waktu' => $request->input('jam_mulaista'.$g.''),
                            'nama_mahasiswa' => $request->input('nama_mahasiswasta'.$g.''),
                            'prodi' => $request->input('nama_prodista'.$g.''),
                            'pembayaran' => $request->input('pembayaransta'.$g.''),
                            'created_at' => \Carbon\Carbon::now()
                            ]);

                        DB::table('a_berkas_scan_buff')
                            ->where('id_berkas_buff', $request->input('id_berkas_buffsta'.$g.''))
                            ->update(['pilihan_print' => 2]);
                        }
                        
                }

                for ($j = 0; $j < $request->totdatsp; $j++) {
                        
                    if (is_null($request->input('nama_karyawansp'.$j.''))) {
                        continue;
                    }else{
                        DB::table('a_last_honorarium')->insert([
                            'nama_dosen' => $request->input('nama_karyawansp'.$j.''),
                            'id_berkas_buff' => $request->input('id_berkas_buffsp'.$j.''),
                            'jabatan_fungsional' => $request->input('jabatan_fungsionalsp'.$j.''),
                            'tugas_sebagai' => $request->input('tugas_sebagaisp'.$j.''),
                            'tanggal' => $request->input('tanggal_pelaksanaansp'.$j.''),
                            'waktu' => $request->input('jam_mulaisp'.$j.''),
                            'nama_mahasiswa' => $request->input('nama_mahasiswasp'.$j.''),
                            'prodi' => $request->input('nama_prodisp'.$j.''),
                            'pembayaran' => $request->input('pembayaransp'.$j.''),
                            'created_at' => \Carbon\Carbon::now()
                            ]);
                        
                        DB::table('a_berkas_scan_buff')
                            ->where('id_berkas_buff', $request->input('id_berkas_buffsp'.$j.''))
                            ->update(['pilihan_print' => 2]);
                        }
                    }
  

             return Response::json(array('cek' => 'berhasil'), 200);
    }

    public function KembaliRest(){
         try {
            DB::table('a_berkas_scan_buff')
                            ->where('pilihan_print','=','2')
                            ->update(['pilihan_print' => '0']);
            DB::table('a_last_honorarium')->delete();
             return redirect()->route('index_honorarium')->with('success', 'Data Berhasil Dikembalikan, Harap Cek Kembali');

            } catch (Exception $e) {
                dd($e);
            }
    }

    public function DaratFinish(){
          try {
            DB::table('a_berkas_scan_buff')
                            ->where('pilihan_print','=','2')
                            ->update(['pilihan_print' => '1']);
            DB::table('a_last_honorarium')->delete();
             return redirect()->route('index_honorarium')->with('success', 'Data Berhasil Dimuseumkan ke bagian Finish, Harap Cek Kembali');

            } catch (Exception $e) {
                dd($e);
            }
    }

    //input tutup buku dan buka buku
    public function post_buktup(Request $request){

        $data = explode(" - " , $request->buktup);
        $data2 = explode(" - " , $request->buktup_remake);

        //$a = date("Y-m-d",strtotime($data[0]));
        //$b = date("Y-m-d",strtotime($data[1]));
        //$c = date("Y-m-d",strtotime($data2[0]));
        //$d = date("Y-m-d",strtotime($data2[1]));

         try {

            DB::table('a_tanggal_bukabuku')->insert([
                [
                    'buka_buku_ori' => $data[0],
                    'tutup_buku_ori' => $data[1],
                    'buka_buku_kw' => $data2[0],
                    'tutup_buku_kw' => $data2[1],
                    'keterangan' => 'cek',
                    'created_at' => \Carbon\Carbon::now()
                ],
            ]);

             return Response::json(array(
                                    'success' => 'Berhasil Menyimpan Data',
                                    'errors' => false,
                                ), 200);

            } catch (Exception $e) {

                report($e);
                return Response::json(array(
                                    'success' => false,
                                    'errors' => 'Terjadi Kesalahan #s4fkl',
                                ), 400);
            }
    }
















    //update pilihan print
    public function update_pilihan_print(Request $request){

        if ($request->status_pilihan == '1') {

            try {

                DB::table('a_berkas_scan_buff')
                    ->where('id_berkas_buff','=', $request->id_berkas_buff)
                    ->update(['pilihan_print' => '0']);

                DB::table('a_last_honorarium')->where('id_berkas_buff', '=', $request->id_berkas_buff)->delete();
                //DB::table('a_last_honorarium')->where('id_berkas_buff', '=', $request->id_berkas_buff)->truncate();

                 return Response::json(array(
                    'success' => 'berhasil',
                    'errors' => false,
                ), 200);

            } catch (Exception $e) {
                report($e);
                return Response::json(array(
                                    'success' => false,
                                    'errors' => 'Terjadi Kesalahan #ljfkl',
                                ), 400);
            }

        }elseif($request->pilihan_print == '0'){

            try {

                DB::table('a_berkas_scan_buff')
                    ->where('id_berkas_buff','=', $request->id_berkas_buff)
                    ->update(['pilihan_print' => '1']);

                 return Response::json(array(
                    'success' => 'berhasil',
                    'errors' => false,
                ), 200);

            } catch (Exception $e) {
                report($e);
                return Response::json(array(
                                    'success' => false,
                                    'errors' => 'Terjadi Kesalahan #ljfkl',
                                ), 400);
            }

        }else{

            return redirect()->route('index_honorarium')->with('error', 'Terjadi Kesalahan Hubungi Admin');

        }

          
    }

    //index untuk menampilkan home honorarium
    public function indexhonorarium(){

        $tot_new = DB::table('a_berkas_scan_buff')->where('pilihan_print','=','0')->count();
        $tot_last = DB::table('a_last_honorarium')->count();

        $data_honor = DB::table('a_berkas_scan_buff')
        ->leftJoin('a_srt_udg_penguji','a_srt_udg_penguji.id_undangan','=','a_berkas_scan_buff.id_data_buff')
        ->leftJoin('a_surat_keterangan_selesai','a_surat_keterangan_selesai.id_sks','=','a_berkas_scan_buff.id_data_buff')
        ->leftJoin('a_nama_mk','a_surat_keterangan_selesai.nama_mk','=','a_nama_mk.id_mk')
        ->select(   'a_berkas_scan_buff.id_berkas_buff',
                    'a_berkas_scan_buff.id_data_buff',
                    'a_berkas_scan_buff.kategori_buff',
                    'a_berkas_scan_buff.file_name',
                    'a_berkas_scan_buff.file_size',
                    'a_berkas_scan_buff.file_type',
                    'a_berkas_scan_buff.created_at',
                    'a_berkas_scan_buff.pilihan_print',
                    'a_berkas_scan_buff.nama_lampiran',
                    'a_srt_udg_penguji.id_undangan',
                    'a_surat_keterangan_selesai.id_sks',
                    'a_srt_udg_penguji.tanggal_pelaksanaan',
                    'a_srt_udg_penguji.jam_mulai',
                    'a_surat_keterangan_selesai.mulai',
                    'a_surat_keterangan_selesai.selesai',
                    'a_nama_mk.jenis_mk',
                    'a_nama_mk.nama_mk'
                    /*'a_udg_dp.*'*/)
        ->where('a_berkas_scan_buff.pilihan_print','=','0')
        ->orderBy('a_berkas_scan_buff.created_at', 'DESC')
        ->get();

        $a_last = DB::table('a_last_honorarium')
                ->join('a_berkas_scan_buff','a_berkas_scan_buff.id_berkas_buff','=','a_last_honorarium.id_berkas_buff')
                ->join('pegawai','pegawai.id_pegawai','=','a_last_honorarium.nama_dosen')
                ->leftJoin('a_srt_udg_penguji','a_srt_udg_penguji.id_undangan','=','a_berkas_scan_buff.id_data_buff')
                ->leftJoin('a_surat_keterangan_selesai','a_surat_keterangan_selesai.id_sks','=','a_berkas_scan_buff.id_data_buff')
                ->select('a_last_honorarium.*','a_berkas_scan_buff.nama_lampiran','pegawai.nama_karyawan','a_berkas_scan_buff.kategori_buff','a_berkas_scan_buff.id_data_buff','a_srt_udg_penguji.id_undangan','a_surat_keterangan_selesai.id_sks')
                ->orderBy('id','ASC')
                ->get();

        //dd($a_last);

        return view('admin.dashboard.honorarium.index',['dataproses' => $data_honor,'tot_new' => $tot_new, 'a_last' => $a_last,'tot_last' => $tot_last]);
     
    }

    public function getdatahonorarium(){


        return DataTables::of(DB::table('a_berkas_scan_buff')
        ->leftJoin('a_srt_udg_penguji','a_srt_udg_penguji.id_undangan','=','a_berkas_scan_buff.id_data_buff')
        ->leftJoin('a_surat_keterangan_selesai','a_surat_keterangan_selesai.id_sks','=','a_berkas_scan_buff.id_data_buff')
        ->leftJoin('a_nama_mk','a_surat_keterangan_selesai.nama_mk','=','a_nama_mk.id_mk')
        ->select(   'a_berkas_scan_buff.id_berkas_buff',
                    'a_berkas_scan_buff.id_data_buff',
                    'a_berkas_scan_buff.kategori_buff',
                    'a_berkas_scan_buff.file_name',
                    'a_berkas_scan_buff.file_size',
                    'a_berkas_scan_buff.file_type',
                    'a_berkas_scan_buff.created_at',
                    'a_berkas_scan_buff.pilihan_print',
                    'a_berkas_scan_buff.nama_lampiran',
                    'a_srt_udg_penguji.id_undangan',
                    'a_surat_keterangan_selesai.id_sks',
                    'a_srt_udg_penguji.tanggal_pelaksanaan',
                    'a_srt_udg_penguji.jam_mulai',
                    'a_surat_keterangan_selesai.mulai',
                    'a_surat_keterangan_selesai.selesai',
                    'a_nama_mk.jenis_mk',
                    'a_nama_mk.nama_mk'
                    /*'a_udg_dp.*'*/)
        
        ->where('a_berkas_scan_buff.pilihan_print','=','1')
        ->orderBy('a_berkas_scan_buff.created_at','DESC')

        )
        ->addIndexColumn()



         //Untuk Tugas Akhir Dan Seminar
        ->addColumn('mhs_sm_ta', function($data){

                $ngences = $data->id_data_buff;
                $cek_mhs = DB::table('a_tbl_mhs')
                            ->join('a_srt_udg_penguji','a_srt_udg_penguji.id_mhs','=','a_tbl_mhs.id_mhs')
                            ->join('a_berkas_scan_buff','a_berkas_scan_buff.id_data_buff','=','a_srt_udg_penguji.id_undangan')
                            ->join('a_prodi','a_prodi.id_prodi','=','a_srt_udg_penguji.id_prodi')
                            ->where([['kategori_buff', '=','sidang tugas akhir'],['id_data_buff','=',$ngences]])
                            ->orWhere([['kategori_buff' ,'=','seminar proposal'],['id_data_buff','=',$ngences]])
                            ->select('a_tbl_mhs.nama','a_tbl_mhs.nim','a_prodi.nama_prodi')
                            ->get();
                $ceklastmhs = [];
                foreach ($cek_mhs as  $valuemhs) {
                    $ceklastmhs[] = $valuemhs->nama. ' - ('.$valuemhs->nim.') - ('.$valuemhs->nama_prodi.')';
                }
                return $ceklastmhs;

            })
        //Untuk Surat Keterangan Selesai
        ->addColumn('mhs_sks', function($data){

                $ngences2 = $data->id_data_buff;
                $cek_mhs2 = DB::table('a_tbl_mhs')
                            ->join('a_surat_keterangan_selesai','a_surat_keterangan_selesai.mahasiswa','=','a_tbl_mhs.id_mhs')
                            ->join('a_berkas_scan_buff','a_berkas_scan_buff.id_data_buff','=','a_surat_keterangan_selesai.id_sks')
                            ->join('a_prodi','a_prodi.id_prodi','=','a_surat_keterangan_selesai.prodi')
                            ->where([['kategori_buff', '=','surat_keterangan_selesai'],['id_data_buff','=',$ngences2]])
                            ->select('a_tbl_mhs.nama','a_tbl_mhs.nim','a_prodi.nama_prodi')
                            ->get();
                    
                $ceklastmhs2 = [];
                foreach ($cek_mhs2 as  $valuemhs2) {
                    $ceklastmhs2[] = $valuemhs2->nama. ' - ('.$valuemhs2->nim.') - ('.$valuemhs2->nama_prodi.')';
                }
                return $ceklastmhs2;
                
            })

        //Untuk Tugas Akhir Dan Seminar
        ->addColumn('dosen_penguji', function($data){
                $cekdos = DB::table('a_udg_dp')
                            ->join('pegawai','pegawai.id_pegawai','=','a_udg_dp.id_dosen')
                            ->where([['id_udg' ,'=',$data->id_undangan],['kategori_dosen','=','Penguji']])
                            ->select(   'a_udg_dp.id',
                                        'pegawai.nama_karyawan',
                                        'pegawai.nidn')
                            ->orderBy('a_udg_dp.id','ASC')
                            ->get();
                $ceklast = [];
                foreach ($cekdos as  $value) {
                    $ceklast[] = $value->nama_karyawan. '('.$value->nidn.')';
                }
                return $ceklast;
                    
            })

        //Untuk Tugas Akhir Dan Seminar //Penguji
        ->addColumn('jabatan_fungsional_penguji', function($data){
                $cekjabatan_fungsional = DB::table('a_udg_dp')
                            ->join('pegawai','pegawai.id_pegawai','=','a_udg_dp.id_dosen')
                            ->join('a_honor_pegawai','a_honor_pegawai.id_pegawai_fk2','=','pegawai.id_pegawai')
                            ->where([['id_udg' ,'=',$data->id_undangan],['kategori_dosen','=','Penguji']])
                            ->select(   'a_honor_pegawai.id_honor',
                                        'a_honor_pegawai.jabatan_fungsional',
                                        'a_udg_dp.id')
                            ->orderBy('a_udg_dp.id','ASC')
                            ->get();
                $cl_jabatan_fungsional = [];
                foreach ($cekjabatan_fungsional as  $valuecekjabatan_fungsional) {
                    $cl_jabatan_fungsional[] = $valuecekjabatan_fungsional->jabatan_fungsional;
                }
                return $cl_jabatan_fungsional;
                    
            })

         //Untuk Tugas Akhir Dan Seminar //Pembimbing
        ->addColumn('jabatan_fungsional_pembimbing', function($data){
                $cekjabatan_fungsional = DB::table('a_udg_dp')
                            ->join('pegawai','pegawai.id_pegawai','=','a_udg_dp.id_dosen')
                            ->join('a_honor_pegawai','a_honor_pegawai.id_pegawai_fk2','=','pegawai.id_pegawai')
                            ->where([['id_udg' ,'=',$data->id_undangan],['kategori_dosen','=','Pembimbing']])
                            ->select(   'a_honor_pegawai.id_honor',
                                        'a_honor_pegawai.jabatan_fungsional',
                                        'a_udg_dp.id')
                            ->orderBy('a_udg_dp.id','ASC')
                            ->get();
                $cl_jabatan_fungsional = [];
                foreach ($cekjabatan_fungsional as  $valuecekjabatan_fungsional) {
                    $cl_jabatan_fungsional[] = $valuecekjabatan_fungsional->jabatan_fungsional;
                }
                return $cl_jabatan_fungsional;
                    
            })

        //Untuk Surat Keterangan Selesai
        ->addColumn('jabatan_fungsional_sks', function($data){
                $jfs_pem_sks = DB::table('a_sks_dp')
                            ->join('pegawai','pegawai.id_pegawai','=','a_sks_dp.id_dosen')
                            ->join('a_honor_pegawai','a_honor_pegawai.id_pegawai_fk2','=','pegawai.id_pegawai')
                            ->where('id_sks_selesai' ,'=',$data->id_sks)
                            ->select(   'a_honor_pegawai.id_honor',
                                        'a_honor_pegawai.jabatan_fungsional',
                                        'a_sks_dp.id')
                            ->orderBy('a_sks_dp.id','ASC')
                            ->get();
                $l_jfs = [];
                foreach ($jfs_pem_sks as  $v_sks_jfs) {
                    $l_jfs[] = $v_sks_jfs->jabatan_fungsional;
                }
                return $l_jfs;
                    
            })
        
        //Untuk Tugas Akhir Dan Seminar
        /*->addColumn('dosen_pembimbing', function($data){
                $cekdospem = DB::table('a_udg_dp')
                            ->join('pegawai','pegawai.id_pegawai','=','a_udg_dp.id_dosen')
                            ->where([['id_udg' ,'=',$data->id_undangan],['kategori_dosen','=','Pembimbing']])
                            ->select(   'a_udg_dp.id',
                                        'pegawai.nama_karyawan',
                                        'pegawai.nidn')
                            ->orderBy('a_udg_dp.id','ASC')
                            ->get();
                $ceklast_pembimbing = [];
                foreach ($cekdospem as  $value_pembimbing) {
                    $ceklast_pembimbing[] = $value_pembimbing->nama_karyawan. '('.$value_pembimbing->nidn.')';
                }
                return $ceklast_pembimbing;
            
            })
        */

        //Untuk Dosen Pembimbing Surat keterangan Selesai
        ->addColumn('dosen_pembimbing_sks', function($data){
                $sks_cekdospem = DB::table('a_sks_dp')
                            ->join('pegawai','pegawai.id_pegawai','=','a_sks_dp.id_dosen')
                            ->where('id_sks_selesai' ,'=',$data->id_sks)
                            ->select(   'a_sks_dp.id',
                                        'pegawai.nama_karyawan',
                                        'pegawai.nidn')
                            ->orderBy('a_sks_dp.id','ASC')
                            ->get();
                $sks_pembimbing = [];
                foreach ($sks_cekdospem as  $sks_value_pembimbing) {
                    $sks_pembimbing[] = $sks_value_pembimbing->nama_karyawan. '('.$sks_value_pembimbing->nidn.')';
                }
                return $sks_pembimbing;
            
            })

        //Untuk Dosen Pembimbing Surat keterangan Selesai
        ->addColumn('no_surat_sks', function($data){
                $no_surat_sks = DB::table('a_sks_dp')
                            ->where('id_sks_selesai' ,'=',$data->id_sks)
                            ->select(   'a_sks_dp.no_surat')
                            ->orderBy('a_sks_dp.no_surat','ASC')
                            ->get();
                $no_surat_sks_pembimbing = [];
                foreach ($no_surat_sks as $no_su_sks) {
                    $no_surat_sks_pembimbing[] = $no_su_sks->no_surat;
                }
                return $no_surat_sks_pembimbing;
            })

        //Untuk Tugas Dan Seminar (Undangan)
        ->addColumn('no_surat_undangan', function($data){
                $ns_udg = DB::table('a_srt_udg_penguji')
                            ->where('id_undangan' ,'=',$data->id_undangan)
                            ->select('a_srt_udg_penguji.no_surat')
                            ->get();
                $no_surat_udg = [];
                foreach ($ns_udg as $no_su_udg) {
                    $no_surat_udg[] = $no_su_udg->no_surat;
                }
                return $no_surat_udg;
            })

        ->addColumn('action', function($data){
                
            $button = '<a href="'.Route('download_file_scan',['id' => $data->id_berkas_buff]).'" title="Download">
                        <button type="button" class="btn btn-outline-primary btn-xs"><span class="fa fa-download"> </span></button>
                        </a> | ';
            $button .= '<a href="'.Route('preview_file_scan',['id' => $data->id_berkas_buff]).'" title="Preview File" target="_blank">
                        <button type="button" class="btn btn-outline-info btn-xs"><span class="fa fa-eye"> </span></button>
                        </a>';

           
            return $button;
            
            })
        
        

        ->addColumn('cek_waktu', function($data){
                
            $v =   DB::table('a_tanggal_bukabuku')->select('tutup_buku_kw','buka_buku_kw')->latest()->first();
            if(is_null($v)){
            }else{
                $dt = new DateTime($data->created_at);
                $cekuy = $dt->format('Y-m-d');
                if (($cekuy >= $v->buka_buku_kw) && ($cekuy <= $v->tutup_buku_kw)) {
                    return true;
                }else{
                    return false;
                }
            }
            
        })

        ->addColumn('cek_waktu_ori', function($data){
                
            $v =   DB::table('a_tanggal_bukabuku')->select('tutup_buku_ori','buka_buku_ori')->latest()->first();
            if(is_null($v)){
            }else{
                $dt = new DateTime($data->created_at);
                $cekuy = $dt->format('Y-m-d');
                if (($cekuy >= $v->buka_buku_ori) && ($cekuy <= $v->tutup_buku_ori)) {
                    return true;
                }else{
                    return false;
                }
            }
            
        })

        ->addColumn('cek_waktu_nabrak', function($data){
                
            $v =   DB::table('a_tanggal_bukabuku')->select('tutup_buku_ori','buka_buku_kw')->latest()->first();
            if(is_null($v)){
            }else{
                $dt = new DateTime($data->created_at);
                $cekuy = $dt->format('Y-m-d');
                if (($cekuy >= $v->tutup_buku_ori) && ($cekuy <= $v->buka_buku_kw)) {
                    return true;
                }else{
                    return false;
                }
            }
            
        })
        

        ->rawColumns(['dosen_penguji','dosen_pembimbing', 'dosen_pembimbing_sks','action','mhs_sm_ta','mhs_sks','no_surat_sks','no_surat_undangan','jabatan_fungsional_penguji','jabatan_fungsional_sks','jabatan_fungsional_pembimbing','cek_waktu','cek_waktu_ori','cek_waktu_nabrak'])
        ->make(true);
                    
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
