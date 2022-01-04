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
use URL;
use File;



class PenilaianKerjaAdmin extends Controller
{   
    //NILAI ATASAN
    public function CekJawabanBawahan(Request $request){

        $DataTujuan = DB::table('b_tujuan')->select('*')->where('id_tujuan','=',$request->data['tujuan'])->first();

        if ($DataTujuan) {
            $CekJawaban = DB::table('b_jawaban')
                            ->leftJoin('b_soal','b_jawaban.id_soal','=','b_soal.id_soal')
                            ->select('b_jawaban.id_user',
                                    'b_jawaban.jawaban',
                                    'b_jawaban.id_jawaban',
                                    'b_soal.kategori_soal',
                                    'b_jawaban.id_soal'
                                 
                            )
                            ->where([['b_jawaban.id_user','=',$DataTujuan->id_user],['b_jawaban.jenis_jawaban','=','nilai_atasan'],['b_soal.id_versi_fk','=',$request->data['versi']]])
                            //['b_verif_jawaban.id_user_verif','=',$id_user_tujuan]
                            ->orderBy('b_jawaban.id_soal', 'ASC')
                            ->get();
            $Hasil = $this->TabelNilaiAtasan($CekJawaban);
            return Response::json(array( 'ceks' => $Hasil), 200);
        }else{
            return Response::json(array( 'ceks' => '002'), 200);
        }

    }

    protected function TabelNilaiAtasan($CekJawaban){
        $table = ''; 
        $table .='<table style="border-collapse: collapse; width: 289pt;" border="0" ><colgroup><col style="width: 52pt;" /> <col style="width: 29pt;" span="4" /> <col style="width: 5pt;" /> <col style="width: 29pt;" span="4" /></colgroup>
              <tbody>
            
              <tr style="height: 16.0pt;">
              <td style="border-width: 1.5pt; border-style: solid; border-color: windowtext windowtext black; font-size: 12pt; font-weight: bold; font-family:  serif; text-align: center; vertical-align: middle; background: #b8cce4; padding: 0px; color: black; font-style: normal; border-image: initial; white-space: nowrap;" rowspan="2">No. Soal</td>
              <td style="font-size: 12pt; font-weight: bold; font-family:  serif; text-align: center; vertical-align: middle; border-top: 1.5pt solid windowtext; border-right: 1pt solid windowtext; border-bottom: 1pt solid windowtext; border-left: none; background: #b8cce4; padding: 0px; color: black; font-style: normal; border-image: initial; white-space: nowrap;" colspan="4">Menilai</td>
              <td style="font-size: 12pt; font-weight: bold; font-family:  serif; text-align: center; vertical-align: middle; border-width: 1.5pt 1.5pt 1pt; border-style: solid; border-color: windowtext; background: #b8cce4; padding: 0px; color: black; font-style: normal; border-image: initial; white-space: nowrap;">&nbsp;</td>
              </tr>
              <tr style="height: 15.5pt;">
              <td style="border-top: none; font-size: 12pt; font-weight: bold; font-family:  serif; text-align: center; vertical-align: middle; border-right: 1pt solid windowtext; border-bottom: 1.5pt solid windowtext; border-left: none; background: #b8cce4; padding: 0px; color: black; font-style: normal; border-image: initial; white-space: nowrap;">A</td>
              <td style="border-top: none; border-left: none; font-size: 12pt; font-weight: bold; font-family:  serif; text-align: center; vertical-align: middle; border-right: 1pt solid windowtext; border-bottom: 1.5pt solid windowtext; background: #b8cce4; padding: 0px; color: black; font-style: normal; border-image: initial; white-space: nowrap;">B</td>
              <td style="border-top: none; border-left: none; font-size: 12pt; font-weight: bold; font-family:  serif; text-align: center; vertical-align: middle; border-right: 1pt solid windowtext; border-bottom: 1.5pt solid windowtext; background: #b8cce4; padding: 0px; color: black; font-style: normal; border-image: initial; white-space: nowrap;">C</td>
              <td style="border-top: none; border-left: none; font-size: 12pt; font-weight: bold; font-family:  serif; text-align: center; vertical-align: middle; border-right: none; border-bottom: 1.5pt solid windowtext; background: #b8cce4; padding: 0px; color: black; font-style: normal; border-image: initial; white-space: nowrap;">D</td>
              <td style="border-top: none; font-size: 12pt; font-weight: bold; font-family:  serif; text-align: center; vertical-align: middle; border-right: 1.5pt solid windowtext; border-bottom: 1.5pt solid windowtext; border-left: 1.5pt solid windowtext; background: #b8cce4; padding: 0px; color: black; font-style: normal; border-image: initial; white-space: nowrap;">&nbsp;</td>
              </tr>';

            $noa = 1; 
            foreach($CekJawaban as $key => $S_CekJawaban){
            if($S_CekJawaban->kategori_soal == 'b'){

            $table .= '<tr style="height: 15.5pt;" style="vertical-align: top;">
              <td style="font-size: 12pt; font-weight: bold; font-family:  serif; text-align: center; vertical-align: middle; border-top: none; border-right: 1.5pt solid windowtext; border-bottom: 0.5pt solid windowtext; border-left: 1.5pt solid windowtext; padding: 0px; color: black; font-style: normal; border-image: initial; white-space: nowrap;">'. $noa .'</td>
              <td style="font-size: 12pt; font-weight: bold; font-family:  serif; text-align: center; vertical-align: middle; border-top: none; border-right: 0.5pt solid windowtext; border-bottom: 0.5pt solid windowtext; border-left: none; padding: 0px; color: black; font-style: normal; border-image: initial; white-space: nowrap;">';
            if($S_CekJawaban->jawaban == 'a'){
            $table .= '<span class="fa fa-check-square"></span>';
            }
            $table .=  '</td>';
            $table .=  '<td style="border-left: none; font-size: 12pt; font-weight: bold; font-family:  serif; text-align: center; vertical-align: middle; border-top: none; border-right: 0.5pt solid windowtext; border-bottom: 0.5pt solid windowtext; padding: 0px; color: black; font-style: normal; border-image: initial; white-space: nowrap;">';
            if($S_CekJawaban->jawaban == 'b'){
            $table .= '<span class="fa fa-check-square"></span>';
            }
            $table .=  '</td>';
            $table .=  '<td style="border-left: none; font-size: 12pt; font-weight: bold; font-family:  serif; text-align: center; vertical-align: middle; border-top: none; border-right: 0.5pt solid windowtext; border-bottom: 0.5pt solid windowtext; padding: 0px; color: black; font-style: normal; border-image: initial; white-space: nowrap;">';
            if($S_CekJawaban->jawaban == 'c'){
            $table .= '<span class="fa fa-check-square"></span>';
            }
            $table .=  '</td>';
            $table .=  '<td style="border-left: none; font-size: 12pt; font-weight: bold; font-family:  serif; text-align: center; vertical-align: middle; border-top: none; border-right: none; border-bottom: 0.5pt solid windowtext; padding: 0px; color: black; font-style: normal; border-image: initial; white-space: nowrap;">';
            if($S_CekJawaban->jawaban == 'd'){
            $table .= '<span class="fa fa-check-square"></span>';
            }
            $table .=  '</td>';
            $table .=  '<td style="font-size: 12pt; font-weight: bold; font-family:  serif; text-align: center; vertical-align: middle; border-top: none; border-right: 1.5pt solid windowtext; border-bottom: 0.5pt solid windowtext; border-left: 1.5pt solid windowtext; padding: 0px; color: black; font-style: normal; border-image: initial; white-space: nowrap;">&nbsp;</td></tr>';
              $noa++;
              }else{ }

              }
            $table .= '</tbody>
              </table>
            </body>';

        return $table;
    }

    //untuk export excel
    /////////////////////////////////////////////MASIH BELOM SELESAI///////////////////////////////////////////////
    public function ToExcelDataPegawai(){

        $data_diri = DB::table('b_data_diri')->get();

        return view('admin.dashboard.penilaiankerja.ToExcel.data_kepegawaian');

    }

    public function DataPegawaiNilai(Request $request){

        $NamaPegawai = DB::table('b_data_diri')
        ->join('users','users.id','=','b_data_diri.id_user')
        ->join('b_jabatan','b_jabatan.id_user','=','b_data_diri.id_user')
        ->join('b_set_jabatan','b_set_jabatan.id_set_jabatan','=','b_jabatan.nama_jabatan')

        ->select('b_data_diri.nama_lengkap','b_set_jabatan.nama_jabatan','b_set_jabatan.kategori')

        ->where([['b_set_jabatan.kategori','=','Tenaga Kependidikan'],['users.level','=','1']])
        ->orWhere([['b_set_jabatan.kategori','=','Tenaga Kependidikan'],['users.level','=','3']])
        ->orWhere([['b_set_jabatan.kategori','=','Tenaga Kependidikan'],['users.level','=','4']])
        ->orderBy('b_data_diri.nama_lengkap','ASC')

        ->get();

        //dd($NamaPegawai);

        return view('admin.dashboard.penilaiankerja.ToExcel.ExportRangkumanNilai',['NamaPegawai' => $NamaPegawai]);

    }
    ////////////////////////////////////////////////MASIH BELOM SELESAI////////////////////////////////////////////

    public function DataPegawaiExport(Request $request){
        
        $data_diri = DB::table('b_data_diri')
        ->join('provinsi','provinsi.id_prov','=','b_data_diri.provinsi_lahir')
        ->join('kabupaten','kabupaten.id_kab','=','b_data_diri.kota_lahir')
        ->select(  'b_data_diri.nama_lengkap',
                    'b_data_diri.nama_mandarin',
                    'b_data_diri.nomor_ktp',
                    'b_data_diri.durasi_ktp',
                    'b_data_diri.nomor_npwp',
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
                    'b_data_diri.suku',
                    'b_data_diri.qiudao',
                    'b_data_diri.jenis_qiudao',
                    'b_data_diri.vege',
                    'b_data_diri.ikrartahun_vege',

                    'provinsi.nama',
                    'kabupaten.nama_kab')
        ->orderBy('b_data_diri.nama_lengkap','ASC')
        ->get();
        
        $PerguruanTinggi = DB::table('b_data_diri')
        ->join('b_perguruan_tinggi','b_perguruan_tinggi.id_user','=','b_data_diri.id_user')
        ->select('b_data_diri.nama_lengkap','b_perguruan_tinggi.*')
        ->orderBy('b_data_diri.nama_lengkap','ASC')
        ->get();

        $SmaSederajat = DB::table('b_sma_sederajat')
        ->join('b_data_diri','b_sma_sederajat.id_user','=','b_data_diri.id_user')
        ->select('b_data_diri.nama_lengkap','b_sma_sederajat.*')
        ->orderBy('b_data_diri.nama_lengkap','ASC')
        ->get();

        $MaritalPasangan = DB::table('b_marital_pasangan')
        ->join('b_data_diri','b_marital_pasangan.id_user','=','b_data_diri.id_user')
        ->select('b_data_diri.nama_lengkap','b_marital_pasangan.*')
        ->orderBy('b_data_diri.nama_lengkap','ASC')
        ->get();

        $Marital = DB::table('b_marital')
        ->join('b_data_diri','b_marital.id_user','=','b_data_diri.id_user')
        ->select('b_data_diri.nama_lengkap','b_marital.*')
        ->orderBy('b_data_diri.nama_lengkap','ASC')
        ->get();

        $KontakDarurat = DB::table('b_kontak_darurat')
        ->join('b_data_diri','b_kontak_darurat.id_user','=','b_data_diri.id_user')
        ->select('b_data_diri.nama_lengkap','b_kontak_darurat.*')
        ->orderBy('b_data_diri.nama_lengkap','ASC')
        ->get();

        $Jabatan = DB::table('b_jabatan')
        ->join('b_data_diri','b_jabatan.id_user','=','b_data_diri.id_user')
        ->join('b_set_jabatan','b_set_jabatan.id_set_jabatan','=','b_jabatan.nama_jabatan')
        ->join('b_set_detail_jabatan','b_set_detail_jabatan.id_detail_jabatan','=','b_jabatan.sub_jabatan')
        ->select('b_data_diri.nama_lengkap','b_set_jabatan.nama_jabatan','b_set_detail_jabatan.nama_detail_jabatan')
        ->orderBy('b_data_diri.nama_lengkap','ASC')
        ->get();

        return view('admin.dashboard.penilaiankerja.ToExcel.ExportExcel',['DataDiri' => $data_diri, 'PerguruanTinggi' => $PerguruanTinggi,'SmaSederajat' => $SmaSederajat,'MaritalPasangan' => $MaritalPasangan,'Marital' => $Marital,'KontakDarurat' => $KontakDarurat,'Jabatan' => $Jabatan]);

    } 





    //Lihat Tujuan Individu
    public function getTujuanIndividu(Request $request, $id_user, $versi){

        $CekTujuanIndividu = DB::table('b_tujuan')
        ->join('users','users.id','=','b_tujuan.id_user_tujuan')
        ->select('users.name')
        ->where([['b_tujuan.id_user','=',$id_user],['id_versi','=',$versi]])
        ->get();

        if ($CekTujuanIndividu->isEmpty()) {
            return response()->json('Tujuan Belum Ditentukan !');
        }else{
            foreach ($CekTujuanIndividu as $key => $value) {
                $hasil[] = 'Nama Tujuan: <b>'.$value->name.'</b><br>';
            }
            return response()->json($hasil);
        }

    }

    ///////////ABSENSI/////////////


    //untuk edit absensi
    public function ViewEditAbsensi($id_user, $id_versi){

        $DataDiri =  DB::table('users')
                ->join('b_data_diri','b_data_diri.id_user','=','users.id')
                ->select('b_data_diri.nama_lengkap','users.level')
                ->where('users.id','=',$id_user)
                ->first();

        $indikator = DB::table('b_indikator')->select('id_indikator','nama_indikator')->where('id_versi','=',$id_versi)->get();
   
        return view('admin.dashboard.penilaiankerja.PenAdmin.EditAbsensiKehadiran',['id_user' => $id_user, 'DataDiri' => $DataDiri,'indikator' => $indikator,'id_versi' => $id_versi]);

    }

    //PROSES EDIT
    public function ProsesEditAbsensi(Request $request){

        for ($i = 0; $i < count($request->input('id_final_absen')); $i++) {

        DB::table('b_final_absen')
        ->where('id_final_absensi', $request->input('id_final_absen')[$i])
        ->update([
                'id_finalDetailIndikator' => $request->input('FinalIndikator')[$i],
                'updated_at' => \Carbon\Carbon::now()
            ]);

        }

        return Response::json(array( 'ceks' => 'berhasil'), 200);
    }

    //untuk view absen
    public function getViewAbsen(Request $request, $id_user, $id_versi){

        $CekAbsen = DB::table('b_final_absen')
        ->join('b_point_absen_kehadiran','b_point_absen_kehadiran.id_detail_indikator','=','b_final_absen.id_finalDetailIndikator')
        ->join('b_subaspek_indikator','b_subaspek_indikator.id_subaspek','=','b_point_absen_kehadiran.id_aspek_fk')
        ->join('b_aspek_indikator','b_aspek_indikator.id_aspek','=','b_subaspek_indikator.id_aspek_fk')
        ->select(
                    'b_final_absen.id_finalDetailIndikator',
                    'b_point_absen_kehadiran.detail_indikator',
                    'b_point_absen_kehadiran.point',
                    'b_subaspek_indikator.nama_subaspek',
                    'b_aspek_indikator.nama_aspek'
                )
        ->where([['b_final_absen.id_user','=',$id_user],['b_final_absen.id_versi','=',$request->id_versi]])
        ->orderBy('b_final_absen.id_final_absensi','ASC')
        ->get();

        if ($CekAbsen->isEmpty()) {
            return response()->json('Belum Diisi !');
        }else{
            foreach ($CekAbsen as $key => $value) {
               $hasil[] = '<span class="badge badge-pill badge-info">'.$value->nama_aspek.'</span> | <span class="badge badge-pill badge-success">' .$value->nama_subaspek.'</span> :<br> <b><u> <font size="4">'.$value->detail_indikator.'</font></u> | <font size="1">'.$value->point.' Point </font></b><br>';
            }
        }

        return response()->json($hasil);

    }
    
    public function SimpanFinalAbsensi(Request $request){

        $cekCount = DB::table('b_final_absen')->where([['id_user','=',$request->input('id_user')],['id_versi','=',$request->id_versi]])->count();

        if ($cekCount > 0) {
            return Response::json(array('ceks' => 'sudah ada'), 200);
        }else{

            for ($i = 0; $i < count($request->input('FinalIndikator')); $i++) {

                $data[] =  [
                    'id_user' => $request->input('id_user'), 
                    'id_versi' => $request->input('id_versi'), 
                    'id_finalDetailIndikator' => $request->input('FinalIndikator')[$i],
                    'created_at' => \Carbon\Carbon::now()];
            }

            $InsertData = DB::table('b_final_absen')->insert($data);

            if ($InsertData) {
                return Response::json(array( 'ceks' => 'berhasil'  ), 200);
            }else{
                return Response::json(array(  'ceks' => 'gagal'), 200);
            }
        }
    }



    //ABSESNI KEHADIRAN DAN PELAKSAAN TUGAS LAIN
    public function CekAbsensi($id_user, $id_versi){

        $DataDiri =  DB::table('users')
                ->join('b_data_diri','b_data_diri.id_user','=','users.id')
                ->join('b_kelompok_data_diri','b_kelompok_data_diri.id_user','=','b_data_diri.id_user')
                ->select('b_data_diri.nama_lengkap','users.level')
                ->where([['users.id','=',$id_user],['b_kelompok_data_diri.id_versi','=',$id_versi]])
                ->first();

        $indikator = DB::table('b_indikator')->select('id_indikator','nama_indikator')->where('id_versi','=',$id_versi)->get();

        return view('admin.dashboard.penilaiankerja.PenAdmin.AbsensiKehadiran',['id_user' => $id_user,'indikator' => $indikator,'DataDiri' => $DataDiri, 'id_versi' => $id_versi]);

    }


    //CEK JAWABAN PEGAWAI 
    public function CekPrintJawaban($id_user_tujuan,$id_user, $id_versi){  

        $id_user_tujuan = Crypt::decryptString($id_user_tujuan);
        $id_userd = Crypt::decryptString($id_user);

        $CekJawaban = DB::table('b_jawaban')
                        ->leftJoin('b_soal','b_jawaban.id_soal','=','b_soal.id_soal')
                        ->select('b_jawaban.id_user',
                                'b_jawaban.jawaban',
                                'b_jawaban.id_jawaban',
                                'b_soal.kategori_soal',
                                'b_jawaban.id_soal'
                             
                        )
                        ->where([['b_jawaban.id_user','=',$id_userd],['b_jawaban.jenis_jawaban','!=','nilai_atasan'],['b_soal.id_versi_fk','=', $id_versi]])
                        //['b_verif_jawaban.id_user_verif','=',$id_user_tujuan]
                        ->orderBy('b_jawaban.id_soal', 'ASC')
                        ->get();


        $cek_tujuan = DB::table('b_tujuan')
        ->join('users','users.id','=','b_tujuan.id_user_tujuan')
        ->select('id_user_tujuan','users.name')
        ->where([['b_tujuan.id_user_tujuan','=',$id_user_tujuan],['b_tujuan.id_versi','=',$id_versi]])
        ->first();

        $cek_namasendiri = DB::table('b_tujuan')
        ->join('users','users.id','=','b_tujuan.id_user')
        ->select('id_user_tujuan','users.name')
        ->where([['b_tujuan.id_user','=',$id_userd],['b_tujuan.id_versi','=',$id_versi]])
        ->first();

        if ($cek_tujuan && $cek_namasendiri) {
             return view('admin.dashboard.penilaiankerja.PenAdmin.cekjawaban',['id_user' => $id_userd, 'CekJawaban' => $CekJawaban,
            'tujuan' => $cek_tujuan,'nama_sendiri' => $cek_namasendiri, 'id_versi'=> $id_versi]);
        }else{
            return Redirect::back()->with('error', 'Data Belum Lengkap, Mungkin Atasan Belum Melakukan verifikasi');
        }
    }

    //CEK JAWABAN SENDIRI TANPA VERIFIKASI ATASAN
    public function CekJawabanSendiri($id_user, $id_versi){


       $CekJawaban = DB::table('b_jawaban')
                        ->leftJoin('b_soal','b_jawaban.id_soal','=','b_soal.id_soal')
                        ->select('b_jawaban.id_user',
                                'b_jawaban.jawaban',
                                'b_jawaban.id_jawaban',
                                'b_soal.kategori_soal',
                                'b_jawaban.id_soal'
                             
                        )
                        ->where([['b_jawaban.id_user','=',$id_user],['b_jawaban.jenis_jawaban','!=','nilai_atasan'],['b_soal.id_versi_fk','=',$id_versi]])
                        //['b_verif_jawaban.id_user_verif','=',$id_user_tujuan]
                        ->orderBy('b_jawaban.id_soal', 'ASC')
                        ->get();

       return view('admin.dashboard.penilaiankerja.PenAdmin.CekJawabanSendiri',['id_user' => $id_user, 'CekJawaban' => $CekJawaban, 'id_versi' => $id_versi]);

    }

    //TERUSKAN PEGAWAI KE ATASAN
    public function teruskanpegawai(Request $request){
        
        for ($i = 0; $i < count($request->input('list_pegawai')); $i++) {

            $cek = DB::table('b_tujuan')
            ->where([['id_user','=',$request->input('list_pegawai')[$i]], ['id_versi','=',$request->input('versi')]])
            ->count();


            if ($cek > 0) {
                $pesan = 'Salah Satu Pegawai Ada Yang Ganda';
                return Response::json(array('ceks' => '1' , 'pesan' => $pesan), 200);
            }

            $cekQuery3[] = [            
                        'id_user' => $request->input('list_pegawai')[$i],
                        'id_user_tujuan' =>  $request->input('tujuan'),
                        'id_versi' => $request->input('versi'),
                        'created_at' => \Carbon\Carbon::now(),
                    ];
                    
        }
           
        $tes_insert = DB::table('b_tujuan')->insert($cekQuery3);

            if ($tes_insert) {
                return Response::json(array('ceks' => '2'), 200);
            }else{
                return Response::json(array('ceks' => '3'), 200);
            }

    }



	//////////////////////////////////////////////INDEX ADMIN///////////////////////////////////////////////

    public function AsalDanTujuan(Request $request){


        if ($request->data['level'] == '4') {
           $nextLevel1 = '1';
           $nextLevel2 = '3';
        }elseif($request->data['level'] == '10'){
            $nextLevel1 = '2';
            $nextLevel2 = null;
        }else{
            $nextLevel1 = null;
            $nextLevel2 = null;
        }

        //LIST TIDAK LAGI MENUNGGU SIAPA YANG TELAH SELESAI, KARENA UNTUK MENENTUKAN NILAI ATASAN
        $list =  DB::table('b_data_diri')
            ->join('users','users.id','=','b_data_diri.id_user')
            ->join('b_kelompok_data_diri','b_kelompok_data_diri.id_user','=','b_data_diri.id_user')
            ->select('b_data_diri.id_user',
                    'b_data_diri.nama_lengkap','users.level')
            ->where([['users.level','=',$request->data['level']],['b_kelompok_data_diri.id_versi','=',$request->data['versi']]])
            ->orWhere([['users.level','=',$nextLevel1],['b_kelompok_data_diri.id_versi','=',$request->data['versi']]])
            ->orWhere([['users.level','=',$nextLevel2],['b_kelompok_data_diri.id_versi','=',$request->data['versi']]])
            ->get();

        $list_tujuan =  DB::table('users')
            ->select('name','id','level')
            ->where('level','=','10')
            ->orWhere('level','=','2')
            ->orWhere('level','=','11')
            ->orWhere('level','=','12')
            ->orWhere('level','=','13')
            ->orWhere('level','=','14')
            ->get();
        //dd($list);

        $render = '';

        $render .= '<div class="col-md-12">
                    <div class="form-group">
                      <input type="hidden" name="versi" value="'.$request->data['versi'].'" required>
                      <label>Pilih pegawai yang akan diterukan ke atasan</label>
                      <select class="select form-control" name="list_pegawai[]" multiple="multiple" data-placeholder="Pilih Pegawai" style="width: 100%;" required="">';
                        foreach($list as $key => $showlist){
        $render .=          '<option value='.$showlist->id_user.'>'.$showlist->nama_lengkap.'</option>';
                        }
        $render .=    '</select>
                    </div>';

        $render .= '<div class="form-group">
                      <label>Yang Dituju</label>
                      <select class="select form-control" name="tujuan" style="width: 100%;" required="">
                        <option value="">Pilih atasan yang dituju</option>';
                        foreach($list_tujuan as $key => $showlist_tujuan){
        $render .=          '<option value='.$showlist_tujuan->id.'>'.$showlist_tujuan->name.'</option>';
                        }
        $render .=    '</select>
                    </div>
                  </div>';


        return Response::json(array('ceks' => $render), 200);



    }


    public function IndexAdminPen($level){

      return view('admin.dashboard.penilaiankerja.PenAdmin.IndexAdmin',['level' => $level, 'id_versi' => $this->VersiSoal()->id, 'tahun' => $this->VersiSoal()->tahun]);
    }


    //MEMILIH VERSI SOAL DENGAN STATUS AKTIF
    protected function VersiSoal(){
      $versi = DB::table('b_versi_soal')->select('status_aktif','tahun','id')->where('status_aktif','=','1')->first();
      return $versi;
    }

    //POST UNTUK MENDAPATKAN INFORMASI TAHUN
    public function TahunVersi(Request $request){

        $data = DB::table('b_versi_soal')->select('*')->where('id','=',$request->data_id)->first();

        if ($data) {
            return Response::json(array('ceks' => $data->tahun), 200);
        }else{
            return Response::json(array('ceks' => 'Terjadi Kesalahan'), 200);
        }
    }


    //GET DATATABEL DATA PENILAIAN KERJA
   	public function GetDataPenAdmin($level, $idversi){

        if ($level == 13) {

            return DataTables::of(DB::table('b_data_diri')
            ->RightJoin('users','b_data_diri.id_user','=','users.id')
            ->join('b_kelompok_data_diri','b_kelompok_data_diri.id_user','=','b_data_diri.id_user')
            ->select(   'b_data_diri.id_user',
                        'users.level',
                        'users.id',
                        'users.name'
                    )
            ->where([['users.level','=',$level],['b_kelompok_data_diri.id_versi','=',$idversi]])
            )
            ->addIndexColumn()
            ->addColumn('status', function($data)use($idversi){

        
                    $button = '<a href="#" title="Status Selesai" onClick="alert(\'User Ini Tidak Memiliki Status Apapun!\')">
                        <button type="button" class="btn btn-warning btn-xs" >
                          <span class="fa fa-exclamation-circle"> </span> </button>
                      </a>';    
            
                return $button;

            })
            ->addColumn('datadiri', function($data)use($idversi){

                             $button = '<a href="#" title="Cek Data Diri">
                                    <button type="button" class="btn btn-warning btn-xs"  onClick="alert(\'User Ini Tidak Memiliki Data Diri!\')">
                                      <span class="fa fa-user"> </span> Data Diri</button>
                                  </a>';

                            return $button;

                        })
            ->addColumn('kelompok', function($data)use($idversi){
                           
                            $button = '<a href="'.Route('Kelompok',['id_user' => $data->id,'id_versi' => $idversi]).'" title="Cek kelompok">
                                    <button type="button" class="btn btn-success btn-xs" >
                                     <span class="fa fa-users"> </span> </button>
                                  </a>';
                            return $button;

                        })
            ->addColumn('print_verif', function($data){

                $button = '<a href="#" title="Cek Verifikasi" onClick="alert(\'User Ini Tidak Memiliki Jawaban!\')">
                                    <button type="button" class="btn btn-warning btn-xs" >
                                      <span class="fa fa-exclamation-circle"> </span>  Cek Jawaban</button>
                                  </a>';
                return $button;
        

            })
            ->addColumn('rekap', function($data){

               $button = '<a href="#" title="Cek Rekap">
                                    <button type="button" class="btn btn-warning btn-xs" onClick="alert(\'User Ini Tidak Memiliki Rekapan!\')" >
                                      <span class="fa fa-clipboard-list"> </span> Cek Rekap</button>
                                  </a>';
      

                return $button;

            })

            ->addColumn('absensi', function($data){

            $button = '<a href="#" title="Cek Rekap">
                                    <button type="button" class="btn btn-warning btn-xs" onClick="alert(\'User Ini Tidak Memiliki Absensi!\')" >
                                      <span class="fa fa-clipboard-list"> </span> Cek Absensi</button>
                                  </a>';
      

            return $button;


            })

         

            ->rawColumns(['datadiri','status','kelompok', 'print_verif','rekap','absensi'])
            ->make(true);

            
        }else{

        //KELOMPOK LEVEL KARYAWAN
        if ($level == '4') {
           $nextLevel1 = '1';
           $nextLevel2 = '3';
           $nextLevel3 = '14';
        }elseif($level == '10'){
            $nextLevel1 = '2';
            $nextLevel2 = null;
        }else{
            $nextLevel1 = null;
            $nextLevel2 = null;
        }

        return DataTables::of(DB::table('b_data_diri')
            ->join('users','b_data_diri.id_user','=','users.id')
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
                        'users.level',
                        'users.id',
                        'users.name'
                    )
             ->where([['users.level','=',$level],['b_kelompok_data_diri.id_versi','=',$idversi]])
             ->orWhere([['users.level','=',$nextLevel1],['b_kelompok_data_diri.id_versi','=',$idversi]])
             ->orWhere([['users.level','=',$nextLevel2],['b_kelompok_data_diri.id_versi','=',$idversi]])

            )
            ->addIndexColumn()
            ->addColumn('status', function($data) use($idversi){

                $cek_sedia = DB::table('b_status')->where([['id_user','=', $data->id_user],['id_versi','=',$idversi]])->count();

                if ($cek_sedia > 0) {
                    $button = '<a href="#" title="Sudah Selesai">
                        <button type="button" class="btn btn-success btn-xs" >
                          <span class="fa fa-check-circle"> </span> </button>
                      </a>';  
                }else{

                    $button = '<a href="#" title="Belum Selesai">
                        <button type="button" class="btn btn-warning btn-xs" >
                          <span class="fa fa-exclamation-circle"> </span> </button>
                      </a>';    
                }
                

                return $button;

            })
            ->addColumn('datadiri', function($data){

                            $button = '<a href="'.Route('IndexPenilaianKerjaAdmin',['id_user' => $data->id_user]).'" title="Data Diri">
                                <button type="button" class="btn btn-success btn-xs" >
                                  <span class="fa fa-user"> </span> Data Diri</button>
                              </a>';

                            return $button;

                        })
            ->addColumn('kelompok', function($data) use($idversi){
                            if ($data->level == 10 || $data->level == 11 || $data->level == 12 || $data->level == 2 || $data->level == 14) {

                                $button = '<a href="'.Route('Kelompok',['id_user' => $data->id_user,'id_versi' => $idversi]).'" title="Cek kelompok">
                                    <button type="button" class="btn btn-success btn-xs" >
                                      <span class="fa fa-users"> </span></button>
                                  </a>';

                            }elseif($data->level == 13){

                                $button = '<a href="'.Route('Kelompok',['id_user' => $data->id, 'id_versi' => $idversi]).'" title="Cek kelompok">
                                    <button type="button" class="btn btn-success btn-xs" >
                                      <span class="fa fa-users"> </span></button>
                                  </a>';

                            }else{
                                $button = '<a href="#" title="Cek kelompok">
                                    <button type="button" class="btn btn-danger btn-xs"  onClick="alert(\'User Ini Tidak Memiliki Kelompok!\')">
                                      <span class="fa fa-users"> </span></button>
                                  </a>';
                            }
                            

                            $button .= '- <a href="#" title="Cek Tujuan">
                                    <button type="button" class="TujuanIndividu btn btn-info btn-xs" data_iduser="'.$data->id.'" data-versi="'.$idversi.'">
                                      <span class="fa fa-user-tie"> </span></button>
                                  </a>';

                            
                            return $button;

                        })

            ->addColumn('print_verif', function($data)  use($idversi){

              
                    $cek_nama = DB::table('b_tujuan')
                    ->join('users','users.id','=','b_tujuan.id_user_tujuan')
                    ->select('id_user_tujuan','users.name','b_tujuan.id_user')
                    ->where([['id_user','=', $data->id_user],['id_versi','=',$idversi]])
                    ->get();

                    $button = '';

                    $button .="<div class='btn-group'>";
                    $button .="<button class='btn btn-outline-info btn-xs dropdown-toggle' type='button'   data-toggle='dropdown' aria-haspopup='true' aria-expanded='false'><span class='fa fa-clipboard-list'> </span> Jawaban & Verif";
                            
                    $button .="</button> &nbsp; |  &nbsp;";
                      $button .="<div class='dropdown-menu' aria-labelledby='dropdownMenuButton'>";
                      $button .= "<a class='dropdown-item bg-success' href='".Route('CekJawabanSendiri',['id_user' => $data->id_user, 'id_versi' => $idversi])."'>Jawaban Sendiri</a>";
                      foreach($cek_nama as $key ) 
                       $button .= "<a class='dropdown-item' href='".Route('CekPrintJawaban',

                        ['id_user_tujuan' => Crypt::encryptString($key->id_user_tujuan),
                        'id_user'=> Crypt::encryptString($key->id_user), 'id_versi' => $idversi])."'>".$key->name."</a>";
                      $button .="</div>";
                    $button .="</div>";
                    //UNTUK CEK NILAI ATASAN

                    $cek_kelompok = DB::table('b_tujuan')
                    ->join('b_data_diri','b_data_diri.id_user','=','b_tujuan.id_user')
                    ->select('b_data_diri.nama_lengkap','b_tujuan.*')
                    ->where([['id_user_tujuan','=',$data->id],['id_versi','=',$idversi]])
                    ->get();

                    $button .="<div class='btn-group'>";
                    $button .="<button class='btn btn-outline-info btn-xs dropdown-toggle' type='button'   data-toggle='dropdown' aria-haspopup='true' aria-expanded='false'><span class='fa fa-clipboard-list'> </span> Jawaban Dari Bawahan";
                            
                    $button .="</button>";
                      $button .="<div class='dropdown-menu' aria-labelledby='dropdownMenuButton'>";
                      foreach($cek_kelompok as $keykel ) 
                       $button .= "<button class='dropdown-item popnilaiatasan' data-versi='".$idversi."' data-id_tujuan='".$keykel->id_tujuan."'>".$keykel->nama_lengkap."</button>";
                      $button .="</div>";
                    $button .="</div>";

                    if( $data->level == '11' || $data->level == '12' || $data->level == '13' ){
                    }else{
                    $button .= ' | <a href="'. Route('DownloadPTLForAdmin',['id' => $data->id_user, 'versi' => $idversi]) .'"><button type="button" class="btn btn-outline-info btn-xs" title="Download File Pelaksanaan Tugas Lain"><span class="fa fa-file-download"> </span> PTL</button> </a>';
                    }


                return $button;

            })
            ->addColumn('rekap', function($data) use($idversi){

                    $cek_nama = DB::table('b_tujuan')
                    ->join('users','users.id','=','b_tujuan.id_user_tujuan')
                    ->select('id_user_tujuan','users.name','b_tujuan.id_user')
                    ->where([['id_user','=', $data->id_user],['id_versi','=',$idversi]])
                    ->get();


                    $JumlahTujuan = count($cek_nama);
                        
                    # isi nilai return
                    $button ="<div class='btn-group'>";
                      $button .="<button class='btn btn-outline-success btn-xs dropdown-toggle' type='button'   data-toggle='dropdown' aria-haspopup='true' aria-expanded='false'><span class='fa fa-clipboard-list'> </span> Rekap ";
                            
                      $button .="</button>";

                      $button .="<div class='dropdown-menu' aria-labelledby='dropdownMenuButton'>";
                      foreach($cek_nama as $key ) 
                       $button .= "<a class='dropdown-item' href='".Route('CekRekapPenilaian',

                        ['id_user_tujuan' => Crypt::encryptString($key->id_user_tujuan),
                        'id_user'=> Crypt::encryptString($key->id_user), 'id_versi' => $idversi])."'>".$key->name."</a>";

                        if ($JumlahTujuan > 1) {
                            $button .= "<a class='dropdown-item' href='".Route('CekRekapPenilaianMulti',

                            ['id_user'=> Crypt::encryptString($data->id_user),'id_versi' => $idversi])."'> <b>Nilai Keseluruhan !</b></a>";
                        }

                      $button .="</div>";
                    $button .="</div>";

                return $button;
            })

            ->addColumn('absensi', function($data) use($idversi){


                $CekAbsenSudah = DB::table('b_final_absen')
                ->where([['id_user','=', $data->id_user],['id_versi','=',$idversi]])
                ->count();

                $button = '';

                if( $data->level == '11' || $data->level == '12' || $data->level == '13' ){
                    $button .= '<hr style="width:10%; margin-top:0px; margin-bottom:0px;border:2px solid grey;">';
                }else{


                    if ($CekAbsenSudah > 0) {
                         $button .= '<a href="#" title="ABSENSI KEHADIRAN & PELAKSANAAN TUGAS LAIN"><button type="button" class="btn btn-info btn-xs"> <span class="fa fa-clipboard-list"> </span> Absensi</button></a>';


                        $button .= ' | <a href="#" title="ABSENSI KEHADIRAN & PELAKSANAAN TUGAS LAIN"><button type="button" class="btn btn-outline-info btn-xs AbsenView" data_iduserAbsen="'.$data->id_user.'" idversi="'.$idversi.'"> <span class="fa fa-eye"> </span></button></a>';


                        $button .= ' | <a href="'.Route('ViewEditAbsensi',['id_user' => $data->id_user, 'id_versi' => $idversi]).'" target="_blank" title="EDIT ABSENSI KEHADIRAN & PELAKSANAAN TUGAS LAIN"><button type="button" class="btn btn-outline-info btn-xs"> <span class="fa fa-pencil-alt"> </span></button></a>';

                    }else{
                         $button = '<a href="'.Route('CekAbsensi',['id_user' => $data->id_user,'id_versi' => $idversi]).'" target="_blank" title="ABSENSI KEHADIRAN & PELAKSANAAN TUGAS LAIN"><button type="button" class="btn btn-outline-info btn-xs"> <span class="fa fa-clipboard-list"> </span> Absensi</button></a>';
                    }
                }

                return $button;

            })

            ->rawColumns(['datadiri','status','kelompok','print_verif','rekap','absensi'])
            ->make(true);

        }

    }

    public function Kelompok($id_user, $id_versi){

        $cek_kelompok = DB::table('b_tujuan')
                    ->join('b_data_diri','b_data_diri.id_user','=','b_tujuan.id_user')
                    ->where([['id_user_tujuan','=',$id_user],['id_versi','=',$id_versi]])
                    ->get();

        return view('admin.dashboard.penilaiankerja.PenAdmin.kelompok',['kelompok' => $cek_kelompok]);
    }

    //////////////////////////////////////////////INDEX ADMIN///////////////////////////////////////////////


    public function index_penilaian_kerja_admin($id_user){

        $Ddiri = DB::table('b_data_diri')
        ->join('users','users.id','=','b_data_diri.id_user')
        ->join('provinsi','b_data_diri.provinsi_lahir','=','provinsi.id_prov')
        ->join('kabupaten','b_data_diri.kota_lahir','=','kabupaten.id_kab')
        ->leftJoin('27_9_kecamatan','b_data_diri.kecamatan_domisili','=','27_9_kecamatan.dis_id')
        ->leftJoin('27_9_kelurahan','b_data_diri.kelurahan_domisili','=','27_9_kelurahan.subdis_id')
        ->join('b_kontak_darurat','b_data_diri.id_user','=','b_kontak_darurat.id_user')
        ->select('b_data_diri.*', 'b_kontak_darurat.*','provinsi.*','kabupaten.*','users.level')
        ->where('b_data_diri.id_user','=',$id_user)
        ->first();


        $identitas_lainnya =  DB::table('b_identitas_lainnya')
        ->select('*')
        ->where('id_user','=',$id_user)
        ->get();

        $marital =  DB::table('b_marital')
        ->select('*')
        ->where('id_user','=',$id_user)
        ->get();

        $maritalpasangan =  DB::table('b_marital_pasangan')
        ->select('*')
        ->where('id_user','=',$id_user)
        ->get();


        $Jabatan = DB::table('b_jabatan')
        ->join('b_set_jabatan','b_jabatan.nama_jabatan','=','b_set_jabatan.id_set_jabatan')
        ->join('b_set_detail_jabatan','b_jabatan.sub_jabatan','=','b_set_detail_jabatan.id_detail_jabatan')
        ->select('b_jabatan.*','b_set_jabatan.nama_jabatan','b_set_detail_jabatan.nama_detail_jabatan',DB::raw('b_jabatan.nama_jabatan AS b_jabatan_id'))
        ->where('b_jabatan.id_user','=',$id_user)
        ->get();

        $jab_akademik = DB::table('b_serdos')
        ->join('b_jabatan_akademik','b_serdos.jabatan_akademik','=','b_jabatan_akademik.id_jabatan_akademik')
        ->select('b_serdos.*','b_jabatan_akademik.nama_jab_akademik')
        ->where('b_serdos.id_user','=',$id_user)
        ->get();

        $sma = DB::table('b_sma_sederajat')
        ->select('*')
        ->where('b_sma_sederajat.id_user','=',$id_user)
        ->get();

        $perting = DB::table('b_perguruan_tinggi')
        ->select('*')
        ->where('b_perguruan_tinggi.id_user','=',$id_user)
        ->get();

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
        


        return view('admin.dashboard.penilaiankerja.PenAdmin.IndexProfil',['Ddiri' => $Ddiri, 'jabatan' => $Jabatan, 'iden' => $identitas_lainnya, 'marital' => $marital, 'maritalpasangan' => $maritalpasangan,'jabakademik' => $jab_akademik, 'sma' => $sma, 'perting' => $perting, 'list_provinsi' => $list_provinsi, 'jabatanlist' => $jabatanlist,'jab_aka' => $cek_jabatanakademik,'id_user' => $id_user]);

    }

    public function get_datapk_admin($id_user){

       return DataTables::of(DB::table('b_data_diri')->select('*')->where('id_user','=', $id_user)
        ->join('provinsi','b_data_diri.provinsi_lahir','=','provinsi.id_prov')
        ->join('kabupaten','b_data_diri.kota_lahir','=','kabupaten.id_kab')
        ->leftJoin('27_9_kecamatan','b_data_diri.kecamatan_domisili','=','27_9_kecamatan.dis_id')
        ->leftJoin('27_9_kelurahan','b_data_diri.kelurahan_domisili','=','27_9_kelurahan.subdis_id')
        ->select('b_data_diri.*','provinsi.*','kabupaten.*','27_9_kecamatan.*','27_9_kelurahan.*')
            )
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


}
