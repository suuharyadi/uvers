<?php

namespace App\Http\Controllers\PenilaianKerja;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Encryption\DecryptException;

use Illuminate\Support\Facades\Crypt;


use App\level as Level;
use DB;
use Validator;  
use Redirect;
use Auth;

class RekapPenilaianMulti extends Controller
{
         ///////////////////////////////////CEK REKAP PENILAIAN KERJA////////////////////////////////////////////
    public function CekRekapPenilaianMulti( $id_user){

        $id_userd = Crypt::decryptString($id_user);

        $cek_nama_tujuan = DB::table('b_tujuan')
                    ->join('users','users.id','=','b_tujuan.id_user_tujuan')
                    ->select('id_user_tujuan','users.name','b_tujuan.id_user')
                    ->where('id_user','=', $id_userd)
                    ->get();
 
        foreach ($cek_nama_tujuan as $key => $value) {
        	   //QUERY JAWABAN DIRISENDIRI DAN BERIVIKASI
	        $DataRekap[] = DB::table('users')
	                    ->join('b_jawaban','users.id','=','b_jawaban.id_user')

	                    ->join('b_verif_jawaban','b_verif_jawaban.id_jawaban_fk','=','b_jawaban.id_jawaban')
	                    ->join('b_soal','b_jawaban.id_soal','=','b_soal.id_soal')
	                    ->join('b_data_diri','users.id','=','b_data_diri.id_user')
	                    ->select(
	                            'users.id','users.level',
	                            'b_jawaban.jawaban',
	                            'b_verif_jawaban.verif_jawaban',
	                            'b_verif_jawaban.id_user_verif',
	                            'b_soal.kategori_soal',
	                            'b_data_diri.nama_lengkap'
	                            )
	                    ->where([['users.id','=',$id_userd],['b_verif_jawaban.id_user_verif','=',$value->id_user_tujuan]])
	                    ->get();


	        $pesan[] = DB::table('users')
                    ->join('b_pesan','b_pesan.pesan_untuk','=','users.id')
                    ->select(
                            'b_pesan.pesan_isi'
                            )
                    ->where([['b_pesan.pesan_untuk','=',$id_userd],['b_pesan.pesan_dari','=',$value->id_user_tujuan]])
                    ->first();


        }

        //DAPATKAN LEVEL PEGAWAI
        $cek_datarekap 	= $DataRekap[0][0];
        //$nama_lengkap 	= $DataRekap[0][0];

        $level 			= $cek_datarekap->level;
        $nama_lengkap 	= $cek_datarekap->nama_lengkap;


        //DATA ABSENSI DAN PELAKSANAAN TUGAS LAIN
        $DataAbsensi = DB::table('users')
                        ->join('b_final_absen','users.id','=','b_final_absen.id_user')
                        ->join('b_point_absen_kehadiran','b_point_absen_kehadiran.id_detail_indikator','=','b_final_absen.id_finalDetailIndikator')
                        ->join('b_subaspek_indikator','b_subaspek_indikator.id_subaspek','=','b_point_absen_kehadiran.id_aspek_fk')
                        ->join('b_aspek_indikator','b_aspek_indikator.id_aspek','=','b_subaspek_indikator.id_aspek_fk')
                        ->join('b_indikator','b_indikator.id_indikator','=','b_aspek_indikator.id_indikator_fk')

                        ->select(
                                'b_final_absen.id_final_absensi','b_final_absen.id_finalDetailIndikator','b_point_absen_kehadiran.point','b_indikator.nama_indikator','b_indikator.id_indikator'
                                )
                    ->where('users.id','=',$id_userd)
                    ->get();

       
        ///////////////////////HITUNG TOTAL POINT BERDASARKAN PENGELOMPOKAN INDUK INDIKATOR///////////////////////////////
        $DataIndukIndikator = DB::table('b_indikator')->select('id_indikator','nama_indikator')->get();

        ///////----------------DI HITUNG BERDASARAKAN PEMISAH SETIAP INDIKATOR-------------///////////


        foreach ($DataIndukIndikator as $keyIn => $h) {

            //DATA ABSENSI DAN PELAKSANAAN TUGAS LAIN
            $TotalPointAbsensi = DB::table('users')
                                    ->join('b_final_absen','users.id','=','b_final_absen.id_user')
                                    ->join('b_point_absen_kehadiran','b_point_absen_kehadiran.id_detail_indikator','=','b_final_absen.id_finalDetailIndikator')
                                    ->join('b_subaspek_indikator','b_subaspek_indikator.id_subaspek','=','b_point_absen_kehadiran.id_aspek_fk')
                                    ->join('b_aspek_indikator','b_aspek_indikator.id_aspek','=','b_subaspek_indikator.id_aspek_fk')
                                    ->join('b_indikator','b_indikator.id_indikator','=','b_aspek_indikator.id_indikator_fk')

                                    ->select(DB::raw('SUM(b_point_absen_kehadiran.point) as total_point'),'b_indikator.id_indikator')
                                ->groupBy('b_indikator.id_indikator')
                            ->where([['users.id','=',$id_userd],['b_indikator.id_indikator','=',$h->id_indikator]])
                            ->first();
            
            $cekTotalSoal = DB::table('users')
                                ->join('b_final_absen','users.id','=','b_final_absen.id_user')
                                ->join('b_point_absen_kehadiran','b_point_absen_kehadiran.id_detail_indikator','=','b_final_absen.id_finalDetailIndikator')
                                ->join('b_subaspek_indikator','b_subaspek_indikator.id_subaspek','=','b_point_absen_kehadiran.id_aspek_fk')
                                ->join('b_aspek_indikator','b_aspek_indikator.id_aspek','=','b_subaspek_indikator.id_aspek_fk')
                                ->join('b_indikator','b_indikator.id_indikator','=','b_aspek_indikator.id_indikator_fk')
                                ->select('b_indikator.id_indikator')
                                ->where([['users.id','=',$id_userd],['b_indikator.id_indikator','=',$h->id_indikator]])
                                ->count();

            $HasilAbsen[] =  [
                    'nama_jenis' => $h->nama_indikator,

                    'finishpoint' => $this->hitungAbsen($TotalPointAbsensi->total_point,$cekTotalSoal,$level, $TotalPointAbsensi->id_indikator), 

                    'prepoint' => $this->HitungJumlahPoint($TotalPointAbsensi->total_point,$cekTotalSoal,$level, $TotalPointAbsensi->id_indikator

                    )];

            $HasilAbsen2[] =  ['finishpoint' => $TotalPointAbsensi->total_point];

        }


        //-----------------HITUNG KESULURUHAN TOTAL ABSEN DLL, TANPA PEMISAH INDIKATOR--------------//
        $tempAbsen = 0;
        foreach ($HasilAbsen as $cekDuloh) {

            $hga = $cekDuloh['finishpoint'];
            $cekTotal = $hga + $tempAbsen;

        $tempAbsen = $cekTotal;
        }
        //-----------------HITUNG KESULURUHAN TOTAL ABSEN DLL, TANPA PEMISAH INDIKATOR--------------//

        ///////----------------DI HITUNG BERDASARAKAN PEMISAH SETIAP INDIKATOR-------------///////////
   
        ///////////////////////HITUNG TOTAL POINT BERDASARKAN PENGELOMPOKAN INDUK INDIKATOR///////////////////////////////



        ////////////////////////////////KATEGORI SOAL A DIRI SENDIRI//////////////////////////////
        $j_aa = DB::table('users')
                    ->join('b_jawaban','users.id','=','b_jawaban.id_user')
                    ->join('b_soal','b_jawaban.id_soal','=','b_soal.id_soal')
                    ->select(
                            'b_jawaban.jawaban'
                            )
                    ->where([['users.id','=',$id_userd],['b_soal.kategori_soal','=','a'],['b_jawaban.jawaban','=','a']])
                    ->count();



        $j_ab  = DB::table('users')
                    ->join('b_jawaban','users.id','=','b_jawaban.id_user')
                    ->join('b_soal','b_jawaban.id_soal','=','b_soal.id_soal')
                    ->select(
                            'b_jawaban.jawaban'
                            )
                    ->where([['users.id','=',$id_userd],['b_soal.kategori_soal','=','a'],['b_jawaban.jawaban','=','b']])
                    ->count();
        $j_ac  = DB::table('users')
                    ->join('b_jawaban','users.id','=','b_jawaban.id_user')
                    ->join('b_soal','b_jawaban.id_soal','=','b_soal.id_soal')
                    ->select(
                            'b_jawaban.jawaban'
                            )
                    ->where([['users.id','=',$id_userd],['b_soal.kategori_soal','=','a'],['b_jawaban.jawaban','=','c']])
                    ->count();
        $j_ad  = DB::table('users')
                    ->join('b_jawaban','users.id','=','b_jawaban.id_user')
                    ->join('b_soal','b_jawaban.id_soal','=','b_soal.id_soal')
                    ->select(
                            'b_jawaban.jawaban'
                            )
                    ->where([['users.id','=',$id_userd],['b_soal.kategori_soal','=','a'],['b_jawaban.jawaban','=','d']])
                    ->count();
        ////////////////////////////////KATEGORI SOAL A DIRI SENDIRI//////////////////////////////

        foreach ($cek_nama_tujuan as $key => $value) {
        ////////////////////////////////KATEGORI SOAL A VERIFIKASI////////////////////////////////
        $j_aaV[] = DB::table('users')
                    ->join('b_jawaban','users.id','=','b_jawaban.id_user')
                    ->join('b_verif_jawaban','b_verif_jawaban.id_jawaban_fk','=','b_jawaban.id_jawaban')
                    ->join('b_soal','b_jawaban.id_soal','=','b_soal.id_soal')
                    ->select(
                            'b_jawaban.jawaban','b_jawaban.id_user'
                            )
                    ->where([['b_verif_jawaban.id_user_verif','=',$value->id_user_tujuan],['b_soal.kategori_soal','=','a'],['b_verif_jawaban.verif_jawaban','=','a'],['b_jawaban.id_user','=',$id_userd]])
                    ->count();


        
        $j_abV[]  = DB::table('users')
                    ->join('b_jawaban','users.id','=','b_jawaban.id_user')
                    ->join('b_verif_jawaban','b_verif_jawaban.id_jawaban_fk','=','b_jawaban.id_jawaban')
                    ->join('b_soal','b_jawaban.id_soal','=','b_soal.id_soal')
                    ->select(
                            'b_jawaban.jawaban'
                            )
                    ->where([['b_verif_jawaban.id_user_verif','=',$value->id_user_tujuan],['b_soal.kategori_soal','=','a'],['b_verif_jawaban.verif_jawaban','=','b'],['b_jawaban.id_user','=',$id_userd]])
                    ->count();



        $j_acV[]  = DB::table('users')
                    ->join('b_jawaban','users.id','=','b_jawaban.id_user')
                    ->join('b_verif_jawaban','b_verif_jawaban.id_jawaban_fk','=','b_jawaban.id_jawaban')
                    ->join('b_soal','b_jawaban.id_soal','=','b_soal.id_soal')
                    ->select(
                            'b_jawaban.jawaban'
                            )
                    ->where([['b_verif_jawaban.id_user_verif','=',$value->id_user_tujuan],['b_soal.kategori_soal','=','a'],['b_verif_jawaban.verif_jawaban','=','c'],['b_jawaban.id_user','=',$id_userd]])
                    ->count();

        $j_adV[]  = DB::table('users')
                    ->join('b_jawaban','users.id','=','b_jawaban.id_user')
                    ->join('b_verif_jawaban','b_verif_jawaban.id_jawaban_fk','=','b_jawaban.id_jawaban')
                    ->join('b_soal','b_jawaban.id_soal','=','b_soal.id_soal')
                    ->select(
                            'b_jawaban.jawaban'
                            )
                    ->where([['b_verif_jawaban.id_user_verif','=',$value->id_user_tujuan],['b_soal.kategori_soal','=','a'],['b_verif_jawaban.verif_jawaban','=','d'],['b_jawaban.id_user','=',$id_userd]])
                    ->count();
        ////////////////////////////////KATEGORI SOAL A VERIFIKASI//////////////////////////////
        }



        ////////////////////////////////KATEGORI SOAL C//////////////////////////////
        $j_ca = DB::table('users')
                    ->join('b_jawaban','users.id','=','b_jawaban.id_user')
                    ->join('b_soal','b_jawaban.id_soal','=','b_soal.id_soal')
                    ->select(
                            'b_jawaban.jawaban'
                            )
                    ->where([['users.id','=',$id_userd],['b_soal.kategori_soal','=','c'],['b_jawaban.jawaban','=','a']])
                    ->count();
        $j_cb  = DB::table('users')
                    ->join('b_jawaban','users.id','=','b_jawaban.id_user')
                    ->join('b_soal','b_jawaban.id_soal','=','b_soal.id_soal')
                    ->select(
                            'b_jawaban.jawaban'
                            )
                    ->where([['users.id','=',$id_userd],['b_soal.kategori_soal','=','c'],['b_jawaban.jawaban','=','b']])
                    ->count();
        $j_cc  = DB::table('users')
                    ->join('b_jawaban','users.id','=','b_jawaban.id_user')
                    ->join('b_soal','b_jawaban.id_soal','=','b_soal.id_soal')
                    ->select(
                            'b_jawaban.jawaban'
                            )
                    ->where([['users.id','=',$id_userd],['b_soal.kategori_soal','=','c'],['b_jawaban.jawaban','=','c']])
                    ->count();
        $j_cd  = DB::table('users')
                    ->join('b_jawaban','users.id','=','b_jawaban.id_user')
                    ->join('b_soal','b_jawaban.id_soal','=','b_soal.id_soal')
                    ->select(
                            'b_jawaban.jawaban'
                            )
                    ->where([['users.id','=',$id_userd],['b_soal.kategori_soal','=','c'],['b_jawaban.jawaban','=','d']])
                    ->count();             
        ////////////////////////////////KATEGORI SOAL C DIRI SENDIRI//////////////////////////////

        foreach ($cek_nama_tujuan as $key => $value) {
        ////////////////////////////////KATEGORI SOAL C VERIFIKASI////////////////////////////////
        $j_caV[] = DB::table('users')
                    ->join('b_jawaban','users.id','=','b_jawaban.id_user')
                    ->join('b_verif_jawaban','b_verif_jawaban.id_jawaban_fk','=','b_jawaban.id_jawaban')
                    ->join('b_soal','b_jawaban.id_soal','=','b_soal.id_soal')
                    ->select(
                            'b_jawaban.jawaban'
                            )
                    ->where([['b_verif_jawaban.id_user_verif','=',$value->id_user_tujuan],['b_soal.kategori_soal','=','c'],['b_verif_jawaban.verif_jawaban','=','a'],['b_jawaban.id_user','=',$id_userd]])
                    ->count();
        $j_cbV[]  = DB::table('users')
                    ->join('b_jawaban','users.id','=','b_jawaban.id_user')
                    ->join('b_verif_jawaban','b_verif_jawaban.id_jawaban_fk','=','b_jawaban.id_jawaban')
                    ->join('b_soal','b_jawaban.id_soal','=','b_soal.id_soal')
                    ->select(
                            'b_jawaban.jawaban'
                            )
                    ->where([['b_verif_jawaban.id_user_verif','=',$value->id_user_tujuan],['b_soal.kategori_soal','=','c'],['b_verif_jawaban.verif_jawaban','=','b'],['b_jawaban.id_user','=',$id_userd]])
                    ->count();

        $j_ccV[]  = DB::table('users')
                    ->join('b_jawaban','users.id','=','b_jawaban.id_user')
                    ->join('b_verif_jawaban','b_verif_jawaban.id_jawaban_fk','=','b_jawaban.id_jawaban')
                    ->join('b_soal','b_jawaban.id_soal','=','b_soal.id_soal')
                    ->select(
                            'b_jawaban.jawaban'
                            )
                    ->where([['b_verif_jawaban.id_user_verif','=',$value->id_user_tujuan],['b_soal.kategori_soal','=','c'],['b_verif_jawaban.verif_jawaban','=','c'],['b_jawaban.id_user','=',$id_userd]])
                    ->count();

        $j_cdV[]  = DB::table('users')
                    ->join('b_jawaban','users.id','=','b_jawaban.id_user')
                    ->join('b_verif_jawaban','b_verif_jawaban.id_jawaban_fk','=','b_jawaban.id_jawaban')
                    ->join('b_soal','b_jawaban.id_soal','=','b_soal.id_soal')
                    ->select(
                            'b_jawaban.jawaban'
                            )
                    ->where([['b_verif_jawaban.id_user_verif','=',$value->id_user_tujuan],['b_soal.kategori_soal','=','c'],['b_verif_jawaban.verif_jawaban','=','d'],['b_jawaban.id_user','=',$id_userd]])
                    ->count();
        ////////////////////////////////KATEGORI SOAL C VERIFIKASI//////////////////////////////
        }





        




        ////////////////////////////////KATEGORI SOAL D//////////////////////////////
        $j_da = DB::table('users')
                    ->join('b_jawaban','users.id','=','b_jawaban.id_user')
                    ->join('b_soal','b_jawaban.id_soal','=','b_soal.id_soal')
                    ->select(
                            'b_jawaban.jawaban'
                            )
                    ->where([['users.id','=',$id_userd],['b_soal.kategori_soal','=','d'],['b_jawaban.jawaban','=','a']])
                    ->count();
        $j_db  = DB::table('users')
                    ->join('b_jawaban','users.id','=','b_jawaban.id_user')
                    ->join('b_soal','b_jawaban.id_soal','=','b_soal.id_soal')
                    ->select(
                            'b_jawaban.jawaban'
                            )
                    ->where([['users.id','=',$id_userd],['b_soal.kategori_soal','=','d'],['b_jawaban.jawaban','=','b']])
                    ->count();
        $j_dc  = DB::table('users')
                    ->join('b_jawaban','users.id','=','b_jawaban.id_user')
                    ->join('b_soal','b_jawaban.id_soal','=','b_soal.id_soal')
                    ->select(
                            'b_jawaban.jawaban'
                            )
                    ->where([['users.id','=',$id_userd],['b_soal.kategori_soal','=','d'],['b_jawaban.jawaban','=','c']])
                    ->count();
        $j_dd  = DB::table('users')
                    ->join('b_jawaban','users.id','=','b_jawaban.id_user')
                    ->join('b_soal','b_jawaban.id_soal','=','b_soal.id_soal')
                    ->select(
                            'b_jawaban.jawaban'
                            )
                    ->where([['users.id','=',$id_userd],['b_soal.kategori_soal','=','d'],['b_jawaban.jawaban','=','d']])
                    ->count();             
        ////////////////////////////////KATEGORI SOAL D DIRI SENDIRI//////////////////////////////


        foreach ($cek_nama_tujuan as $key => $value) {
        ////////////////////////////////KATEGORI SOAL D VERIFIKASI////////////////////////////////
        $j_daV[] = DB::table('users')
                    ->join('b_jawaban','users.id','=','b_jawaban.id_user')
                    ->join('b_verif_jawaban','b_verif_jawaban.id_jawaban_fk','=','b_jawaban.id_jawaban')
                    ->join('b_soal','b_jawaban.id_soal','=','b_soal.id_soal')
                    ->select(
                            'b_jawaban.jawaban'
                            )
                    ->where([['b_verif_jawaban.id_user_verif','=',$value->id_user_tujuan],['b_soal.kategori_soal','=','d'],['b_verif_jawaban.verif_jawaban','=','a'],['b_jawaban.id_user','=',$id_userd]])
                    ->count();
        $j_dbV[]  = DB::table('users')
                    ->join('b_jawaban','users.id','=','b_jawaban.id_user')
                    ->join('b_verif_jawaban','b_verif_jawaban.id_jawaban_fk','=','b_jawaban.id_jawaban')
                    ->join('b_soal','b_jawaban.id_soal','=','b_soal.id_soal')
                    ->select(
                            'b_jawaban.jawaban'
                            )
                    ->where([['b_verif_jawaban.id_user_verif','=',$value->id_user_tujuan],['b_soal.kategori_soal','=','d'],['b_verif_jawaban.verif_jawaban','=','b'],['b_jawaban.id_user','=',$id_userd]])
                    ->count();

        $j_dcV[]  = DB::table('users')
                    ->join('b_jawaban','users.id','=','b_jawaban.id_user')
                    ->join('b_verif_jawaban','b_verif_jawaban.id_jawaban_fk','=','b_jawaban.id_jawaban')
                    ->join('b_soal','b_jawaban.id_soal','=','b_soal.id_soal')
                    ->select(
                            'b_jawaban.jawaban'
                            )
                    ->where([['b_verif_jawaban.id_user_verif','=',$value->id_user_tujuan],['b_soal.kategori_soal','=','d'],['b_verif_jawaban.verif_jawaban','=','c'],['b_jawaban.id_user','=',$id_userd]])
                    ->count();

        $j_ddV[]  = DB::table('users')
                    ->join('b_jawaban','users.id','=','b_jawaban.id_user')
                    ->join('b_verif_jawaban','b_verif_jawaban.id_jawaban_fk','=','b_jawaban.id_jawaban')
                    ->join('b_soal','b_jawaban.id_soal','=','b_soal.id_soal')
                    ->select(
                            'b_jawaban.jawaban'
                            )
                    ->where([['b_verif_jawaban.id_user_verif','=',$value->id_user_tujuan],['b_soal.kategori_soal','=','d'],['b_verif_jawaban.verif_jawaban','=','d'],['b_jawaban.id_user','=',$id_userd]])
                    ->count();
        ////////////////////////////////KATEGORI SOAL D VERIFIKASI//////////////////////////////
        }


       
        ////////////////////////// PERHITUNGAN FORM A//////////////////////////
        $jumlah_soal_a = DB::table('b_soal')
                    ->where('b_soal.kategori_soal','=','a')
                    ->count();

        $h_a = $j_aa * 4;
        $h_b = $j_ab * 3;
        $h_c = $j_ac * 2;
        $h_d = $j_ad * 1;

        $h_formA = $this->totalCek($h_a,$h_b,$h_c,$h_d, $jumlah_soal_a);
        //$h_formAV = $this->totalCek($h_aV,$h_bV,$h_cV,$h_dV, $jumlah_soal_a);
        

        //VERIFIKASI UNTUK BANYAK PEMVERIF
        for ($i=0; $i < count($j_aaV); $i++) { 
        	$a_katA = $j_aaV[$i] * 4;
        	$b_katA = $j_abV[$i] * 3;
        	$c_katA = $j_acV[$i] * 2;
        	$d_katA = $j_adV[$i] * 1;

        	$h_formAV = $this->totalCek($a_katA,$b_katA,$c_katA,$d_katA, $jumlah_soal_a);
        	$cek_bulat[] = round($h_formAV,2);
        }

       	$fix_A = array_sum($cek_bulat) / 2;
       	$FormA_V = round($fix_A * 0.15,2);

        //persentase akhir bawahan
        $cek_totalkahir = $this->total_akhir($h_formA, $fix_A, 'a', $level);
      

        ////////////////////////// PERHITUNGAN FORM A//////////////////////////

         ////////////////////////// PERHITUNGAN FORM C//////////////////////////

        $jumlah_soal_c = DB::table('b_soal')
                    ->where('b_soal.kategori_soal','=','c')
                    ->count();



        $h_aC = $j_ca * 4;
        $h_bC = $j_cb * 3;
        $h_cC = $j_cc * 2;
        $h_dC = $j_cd * 1;


        $h_formC = $this->totalCek($h_aC,$h_bC,$h_cC,$h_dC, $jumlah_soal_c);

       //$h_formCV = $this->totalCek($h_aVC,$h_bVC,$h_cVC,$h_dVC, $jumlah_soal_c);


        //VERIFIKASI UNTUK BANYAK PEMVERIF
        for ($z=0; $z < count($j_caV); $z++) { 
        	$a_katC = $j_caV[$z] * 4;
        	$b_katC = $j_cbV[$z] * 3;
        	$c_katC = $j_ccV[$z] * 2;
        	$d_katC = $j_cdV[$z] * 1;

        	$h_formCV = $this->totalCek($a_katC,$b_katC,$c_katC,$d_katC, $jumlah_soal_c);
        	$cek_bulatC[] = round($h_formCV,2);
        }

        $fix_C = array_sum($cek_bulatC) / 2;

       	$FormC_V = round($fix_C * 0.25,2);

        //persentase akhir bawahan
        $cek_totalkahirC = $this->total_akhir($h_formC, $fix_C ,'c', $level);

        ////////////////////////// PERHITUNGAN FORM C//////////////////////////

        ////////////////////////// PERHITUNGAN FORM D//////////////////////////
        $jumlah_soal_d = DB::table('b_soal')
                    ->where('b_soal.kategori_soal','=','d')
                    ->count();

        $h_aD = $j_da * 4;
        $h_bD = $j_db * 3;
        $h_cD = $j_dc * 2;
        $h_dD = $j_dd * 1;


        //VERIFIKASI UNTUK BANYAK PEMVERIF
        for ($i=0; $i < count($j_daV); $i++) { 
        	$a_katD = $j_daV[$i] * 4;
        	$b_katD = $j_dbV[$i] * 3;
        	$c_katD = $j_dcV[$i] * 2;
        	$d_katD = $j_ddV[$i] * 1;

        	$h_formDV = $this->totalCek($a_katD,$b_katD,$c_katD,$d_katD, $jumlah_soal_d);
        	$cek_bulat_D[] = round($h_formDV,2);
        }


       	$fix_D = array_sum($cek_bulat_D) / 2;
       	$FormD_V = round($fix_D * 0.25,2);
        

        $h_formD = $this->totalCek($h_aD,$h_bD,$h_cD,$h_dD, $jumlah_soal_d);
        //$h_formDV = $this->totalCek($h_aVD,$h_bVD,$h_cVD,$h_dVD, $jumlah_soal_d);


        //persentase akhir bawahan

        $cek_totalkahirD = $this->total_akhir($h_formD, $fix_D ,'d', $level);

        ////////////////////////// PERHITUNGAN FORM D//////////////////////////


        ///////////////////////////HITUNG TOTAL RATING KSELURUHAN///////////////////////////////

        //$level == '11' || $level == '12' || $level == '13'

        if ($level == '4' || $level == '1' || $level == '3') {
        $Rating =   array(  round($cek_totalkahir,2),
                            round($cek_totalkahirC,2),
                            round($cek_totalkahirD,2),
                            round($tempAbsen,2)
                        ); 
        $HasilRating =   array_sum($Rating);

        }else if($level == '10' || $level == '2'){
        $Rating =   array(  round($cek_totalkahir,2),
                            round($cek_totalkahirB,2),
                            round($cek_totalkahirC,2),
                            round($cek_totalkahirD,2),
                            round($tempAbsen,2)
                              ); 
        $HasilRating =   array_sum($Rating);

        //dd($HasilRating);

        }else if($level == '11' || $level == '12' || $level == '13'){

        $Rating =   array(  round($cek_totalkahir,2),
                            round($cek_totalkahirB,2),
                            round($cek_totalkahirC,2),
                            round($cek_totalkahirD,2),
                              ); 
        $HasilRating =   array_sum($Rating);

        }else{

        }

        ///////////////////////////HITUNG TOTAL RATING KSELURUHAN///////////////////////////////
        

        /////JABATAN PEGAWAI BAGIAN JABATAN/////

        $CekBagianPegawai = DB::table('b_jabatan')

        ->join('b_set_jabatan','b_jabatan.nama_jabatan','=','b_set_jabatan.id_set_jabatan')
        ->select('b_set_jabatan.lengkap')
        ->where([['id_user','=',$id_userd],['b_set_jabatan.kategori','=','Tenaga Kependidikan']])
        ->first();

        if (is_null($CekBagianPegawai)) {

            $CekHasilJabatan = 'Jabatan Tidak Ditemukan';

        }else{

            $CekHasilJabatan = $CekBagianPegawai->lengkap;

        }

        /////JABATAN PEGAWAI BAGIAN JABATAN////

        foreach ($cek_nama_tujuan as $key => $value) {
        ///////////////////////////PESAN DARI ATASAN/////////////////////////////
	        $pesan = DB::table('users')
	                    ->join('b_pesan','b_pesan.pesan_untuk','=','users.id')
	                    ->select(
	                            'b_pesan.pesan_isi'
	                            )
	                    ->where([['b_pesan.pesan_untuk','=',$id_userd],['b_pesan.pesan_dari','=',$value->id_user_tujuan]])
	                    ->first();
	        
	        if ($pesan != null) {
	            $isi_pesan[] = $pesan->pesan_isi;
	        }else{
	            $isi_pesan[] = null;
	        }
        ////////////////////////////PESAN DARI ATASAN////////////////////////////
    	}


        //dd($HasilAbsen);
        if ($level == '4' || $level == '1' || $level == '3') {
            return view('admin.dashboard.penilaiankerja.PenAdmin.RekapNilai',
                [
                    'id_user' => $id_userd,
                    'form_a' => round($h_formA,2),
                    'form_aV' => round($fix_A,2),
                    'total_akhirFormA' => round($cek_totalkahir,2),

                    'form_c' => round($h_formC,2),
                    'form_cV' => round($fix_C,2),
                    'total_akhirFormC' => round($cek_totalkahirC,2),

                    'form_d' => round($h_formD,2),
                    'form_dV' => round($fix_D,2),
                    'total_akhirFormD' => round($cek_totalkahirD,2),

                    'nama_lengkap' => $nama_lengkap,
                    'pesan' => $isi_pesan,
                    'Jabatan' => $CekHasilJabatan,

                    'hasilabsen' => $HasilAbsen,
                    'hasilrating' => round($HasilRating,2)

                ]);

        }elseif($level == '10' || $level == '2'){

            return view('admin.dashboard.penilaiankerja.PenAdmin.RekapNilaiAtasan',
                [
                    'id_user' => $id_userd,
                    'form_a' => round($h_formA,2),
                    'form_aV' => round($fix_A,2),
                    'total_akhirFormA' => round($cek_totalkahir,2),

                    'h_form_b' => round($h_formB,2),
                    'h_form_bV' => round($fix_B,2),
                    'total_akhirFormB' => round($cek_totalkahirB,2),

                    'form_c' => round($h_formC,2),
                    'form_cV' => round($fix_C,2),
                    'total_akhirFormC' => round($cek_totalkahirC,2),

                    'form_d' => round($h_formD,2),
                    'form_dV' => round($fix_D,2),
                    'total_akhirFormD' => round($cek_totalkahirD,2),

                    'nama_lengkap' => $nama_lengkap,
                    'pesan' => $isi_pesan,
                    'Jabatan' => $CekHasilJabatan,

                    'hasilabsen' => $HasilAbsen,
                    'hasilrating' => round($HasilRating,2)

                ]);

        }elseif($level == '11' || $level == '12' || $level == '13'){

            return view('admin.dashboard.penilaiankerja.PenAdmin.RekapNilaiNonAbsensi',
                [
                    'id_user' => $id_userd,
                    'form_a' => round($h_formA,2),
                    'form_aV' => round($fix_A,2),
                    'total_akhirFormA' => round($cek_totalkahir,2),

                    'h_form_b' => round($h_formB,2),
                    'h_form_bV' => round($fix_B,2),
                    'total_akhirFormB' => round($cek_totalkahirB,2),

                    'form_c' => round($h_formC,2),
                    'form_cV' => round($fix_C,2),
                    'total_akhirFormC' => round($cek_totalkahirC,2),

                    'form_d' => round($h_formD,2),
                    'form_dV' => round($fix_D,2),
                    'total_akhirFormD' => round($cek_totalkahirD,2),

                    'nama_lengkap' => $nama_lengkap,
                    'pesan' => $isi_pesan,
                    'Jabatan' => $CekHasilJabatan,

                    'hasilrating' => round($HasilRating,2)

                ]);
        }   
    }



    //MENGHITUNG TOTAL AKHIR
    protected function total_akhir($dirisendiri, $atasan, $tipesoal, $level){

        if ($level == '4' || $level == '1' || $level == '3') {

               switch ($tipesoal) {
                case 'a':
                    $DS = $dirisendiri * 0.05;
                    $AT = $atasan * 0.15;

                    return $DS + $AT;
                    break;
                case 'b':
                    $DS = $dirisendiri * 0.05;
                    $AT = $atasan * 0.15;

                    return $DS + $AT;
                    break;
                case 'c':
                    $DS = $dirisendiri * 0.05;
                    $AT = $atasan * 0.25;

                    return $DS + $AT;
                    break;
                case 'd':
                    $DS = $dirisendiri * 0.05;
                    $AT = $atasan * 0.25;

                    return $DS + $AT;
                    break;
                
                default:
                    # code...
                    break;
            }

        }elseif( $level == '10' || $level == '2' ){

             switch ($tipesoal) {
                case 'a':
                    $DS = $dirisendiri * 0.05;
                    $AT = $atasan * 0.1;

                    return $DS + $AT;
                    break;
                case 'b':
                    $DS = $dirisendiri * 0.05;
                    $AT = $atasan * 0.15;

                    return $DS + $AT;
                    break;
                case 'c':
                    $DS = $dirisendiri * 0.05;
                    $AT = $atasan * 0.22;

                    return $DS + $AT;
                    break;
                case 'd':
                    $DS = $dirisendiri * 0.05;
                    $AT = $atasan * 0.18;

                    return $DS + $AT;
                    break;
                
                default:
                    # code...
                    break;
            }

        }elseif( $level == '11' || $level == '12' || $level == '13'){

             switch ($tipesoal) {
                case 'a':
                    $DS = $dirisendiri * 0.05;
                    $AT = $atasan * 0.15;

                    return $DS + $AT;
                    break;
                case 'b':
                    $DS = $dirisendiri * 0.05;
                    $AT = $atasan * 0.3;

                    return $DS + $AT;
                    break;
                case 'c':
                    $DS = $dirisendiri * 0.05;
                    $AT = $atasan * 0.15;

                    return $DS + $AT;
                    break;
                case 'd':
                    $DS = $dirisendiri * 0.05;
                    $AT = $atasan * 0.2;

                    return $DS + $AT;
                    break;
                
                default:
                    # code...
                    break;
            }

        }else{

        }

        
        

    }
    //UNTUK PERHITUNGAN TOTAL CEK DENGAN JUMLAH SOAL BERDASARKAN TIPE FORM
    protected function totalCek($a, $b, $c, $d, $jmlsoal){   

        $cekj = $a + $b + $c + $d;
        $cekbagi = $cekj / $jmlsoal;

        return $cekbagi;

    }
  
    ///////////////////////////////////CEK REKAP PENILAIAN KERJA////////////////////////////////////////////


    //HITUNG ABSENSI KEHADIRAN DAN PELAKSANAAN TUGAS LAIN
    protected function HitungJumlahPoint($TotalPoint, $TotalSoal, $level,  $id_indikator){

        if ($level == '4' || $level == '3' || $level == '1') {
            
            if ($id_indikator == '1') {
                
                $cekHasil = $TotalPoint / $TotalSoal;

                return $cekHasil;
            
            }elseif($id_indikator == '2'){

                $cekHasil = $TotalPoint / $TotalSoal;

                return $cekHasil;

            }else{

            }


        }elseif( $level == '10' || $level == '2' ){


            if ($id_indikator == '1') {
                
                $cekHasil = $TotalPoint / $TotalSoal;

                return $cekHasil;
            
            }elseif($id_indikator == '2'){

                $cekHasil = $TotalPoint / $TotalSoal;

                return $cekHasil;

            }else{

            }


        }elseif( $level == '11' || $level == '12' ){

        }else{

        }

    }



    protected function hitungAbsen($TotalPoint, $TotalSoal, $level,  $id_indikator){

        if ($level == '4' || $level == '3' || $level == '1') {
            
            if ($id_indikator == '1') {
                
                $cekHasil = $TotalPoint / $TotalSoal;

                $finish = $cekHasil * 0.15;
                return $finish;
            
            }elseif($id_indikator == '2'){

                $cekHasil = $TotalPoint / $TotalSoal;

                $finish = $cekHasil * 0.05;
                return $finish;

            }else{

            }


        }elseif( $level == '10' || $level == '2' ){

            if ($id_indikator == '1') {
                
                $cekHasil = $TotalPoint / $TotalSoal;

                $finish = $cekHasil * 0.1;
                return $finish;
            
            }elseif($id_indikator == '2'){

                $cekHasil = $TotalPoint / $TotalSoal;

                $finish = $cekHasil * 0.05;
                return $finish;

            }else{

            }


        }elseif( $level == '11' || $level == '12' ){

        }else{

        }

    }




}
