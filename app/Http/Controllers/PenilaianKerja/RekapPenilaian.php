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

class RekapPenilaian extends Controller
{   
    //ABSENSI KEHADIRAN -- UNTUK VERSI 2 (PELAKSANAAN TUGAS LAIN SUDAH DI PISAH SENDIRI)
    protected function DataAbsensiV2($id_userd, $id_versi){

        $DataAbsensi = DB::table('users')
                    ->join('b_final_absen','users.id','=','b_final_absen.id_user')
                    ->join('b_point_absen_kehadiran','b_point_absen_kehadiran.id_detail_indikator','=','b_final_absen.id_finalDetailIndikator')
                    ->join('b_subaspek_indikator','b_subaspek_indikator.id_subaspek','=','b_point_absen_kehadiran.id_aspek_fk')
                    ->join('b_aspek_indikator','b_aspek_indikator.id_aspek','=','b_subaspek_indikator.id_aspek_fk')
                    ->join('b_indikator','b_indikator.id_indikator','=','b_aspek_indikator.id_indikator_fk')

                    ->select(  'b_final_absen.id_final_absensi','b_final_absen.id_finalDetailIndikator','b_point_absen_kehadiran.point','b_indikator.nama_indikator','b_indikator.id_indikator')
                ->where([['users.id','=',$id_userd],['b_indikator.id_versi','=',$id_versi]])
                ->get();

        return $DataAbsensi;        
    }

    //HITUNG POINT ABSENSI
    protected function HitungPointAbsensi($id_userd,$id_indikator){

        $TotalPointAbsensi = DB::table('users')
            ->join('b_final_absen','users.id','=','b_final_absen.id_user')
            ->join('b_point_absen_kehadiran','b_point_absen_kehadiran.id_detail_indikator','=','b_final_absen.id_finalDetailIndikator')
            ->join('b_subaspek_indikator','b_subaspek_indikator.id_subaspek','=','b_point_absen_kehadiran.id_aspek_fk')
            ->join('b_aspek_indikator','b_aspek_indikator.id_aspek','=','b_subaspek_indikator.id_aspek_fk')
            ->join('b_indikator','b_indikator.id_indikator','=','b_aspek_indikator.id_indikator_fk')

            ->select(DB::raw('SUM(b_point_absen_kehadiran.point) as total_point'),'b_indikator.id_indikator')
            ->groupBy('b_indikator.id_indikator')
            ->where([['users.id','=',$id_userd],['b_indikator.id_indikator','=',$id_indikator]])
            ->first();
        return $TotalPointAbsensi;
    }

    //CEK TOTAL INDIKATOR
    protected function CekTotalIndikator($id_userd,$id_indikator){

        $Data = DB::table('users')
                ->join('b_final_absen','users.id','=','b_final_absen.id_user')
                ->join('b_point_absen_kehadiran','b_point_absen_kehadiran.id_detail_indikator','=','b_final_absen.id_finalDetailIndikator')
                ->join('b_subaspek_indikator','b_subaspek_indikator.id_subaspek','=','b_point_absen_kehadiran.id_aspek_fk')
                ->join('b_aspek_indikator','b_aspek_indikator.id_aspek','=','b_subaspek_indikator.id_aspek_fk')
                ->join('b_indikator','b_indikator.id_indikator','=','b_aspek_indikator.id_indikator_fk')
                ->select('b_indikator.id_indikator')
                ->where([['users.id','=',$id_userd],['b_indikator.id_indikator','=',$id_indikator]])
                ->count();
        return $Data;

    }

    //QUERY JAWABAN DIRISENDIRI DAN BERIVIKASI
    protected function CekJawabanDanVerif($id_userd,$id_user_tujuan,$id_versi){

        $DataRekap = DB::table('users')
                    ->join('b_jawaban','users.id','=','b_jawaban.id_user')
                    ->join('b_verif_jawaban','b_verif_jawaban.id_jawaban_fk','=','b_jawaban.id_jawaban')
                    ->join('b_soal','b_jawaban.id_soal','=','b_soal.id_soal')
                    ->join('b_data_diri','users.id','=','b_data_diri.id_user')
                    ->join('b_kelompok_data_diri','b_kelompok_data_diri.id_user','=','b_data_diri.id_user')
                    ->select('users.id','users.level', 'b_jawaban.jawaban','b_verif_jawaban.verif_jawaban','b_verif_jawaban.id_user_verif','b_soal.kategori_soal','b_data_diri.nama_lengkap','b_soal.id_versi_fk')
                    ->where([['users.id','=',$id_userd],['b_verif_jawaban.id_user_verif','=',$id_user_tujuan],['b_kelompok_data_diri.id_versi','=',$id_versi],['b_soal.id_versi_fk','=',$id_versi]])
                    ->get();
        return $DataRekap;

    }


    //HITUNG MENILAI ATASAN 
    protected function HitungNilaiAtasan($nilai, $level, $versi){

        $jml_soall_B = DB::table('b_soal')->where([['b_soal.kategori_soal','=','b'],['b_soal.id_versi_fk','=',$versi]])->count();

        //HITUNG PENILAIAN DARI BAWAHAN LALU DI TOTALKAN DAN DI BAGI JUMLAH BAWAHAN

        if ($level == '4' || $level == '3' || $level == '1') {//BAWAHAN
            return '0';
            $tipe_pangkat = 'bawahan';
            $SudahBulat = 0;
        }elseif( $level == '10' || $level == '2' || $level == '14'){//BAWAHAN MENENGAH 
            for ($i=0; $i < count($nilai); $i++) { 
                $hasila = $nilai[$i]['a'] * 4;    
                $hasilb = $nilai[$i]['b'] * 3;
                $hasilc = $nilai[$i]['c'] * 2;
                $hasild = $nilai[$i]['d'] * 1;
                $Oknr = array_sum([$hasila,$hasilb,$hasilc,$hasild]);
                $TotalAkhir = $Oknr / $jml_soall_B;
                $SudahBulat[] = round($TotalAkhir, 2);
            }
            
            $tipe_pangkat = 'bawahan_menengah';
        }elseif( $level == '11' || $level == '12' ){//ATASAN
             for ($i=0; $i < count($nilai); $i++) { 
                $hasila = $nilai[$i]['a'] * 4;    
                $hasilb = $nilai[$i]['b'] * 3;
                $hasilc = $nilai[$i]['c'] * 2;
                $hasild = $nilai[$i]['d'] * 1;
                $Oknr = array_sum([$hasila,$hasilb,$hasilc,$hasild]);
                $TotalAkhir = $Oknr / $jml_soall_B;
                $SudahBulat[] = round($TotalAkhir, 2);
            }
            $tipe_pangkat = 'atasan';
        }else{  }

        $HasilAkhirNilaiAtasan = round(array_sum($SudahBulat)/count($nilai),2);

        $data_bobot = DB::table('b_perhitungan_penilaian_kerja')->where('tipe','=',$tipe_pangkat)->first();
        if ($data_bobot) {
            $lkiy = $HasilAkhirNilaiAtasan * (float)$data_bobot->form_b_bawahan;//memisahkan setiap kategori nilai dari verif atau dirisendiri
        }else{
            $lkiy = false;
        }
        return ['bb_NilaiBawahan' => $HasilAkhirNilaiAtasan, 'sb_NilaiBawahan' => round($lkiy,2)];
        
    }

    //HITUNG JAWABAN BERDASARKAN KATEGORI DAN DARI MASING2 OBJEKTIF
    protected function HitunngHasilSoal($KumpulDs, $KumpulVerif, $jml_soall, $jenis, $level, $VerifOrDs){
        
        if ($jenis == 'ds') { //ds = dirisendiri
           
            $hasila = $KumpulDs['a'] * 4;    
            $hasilb = $KumpulDs['b'] * 3;
            $hasilc = $KumpulDs['c'] * 2;
            $hasild = $KumpulDs['d'] * 1;

        }elseif($jenis == 'verif'){//Jawaban Verifikasi

            $hasila = $KumpulVerif['a'] * 4;   
            $hasilb = $KumpulVerif['b'] * 3;
            $hasilc = $KumpulVerif['c'] * 2;
            $hasild = $KumpulVerif['d'] * 1;

        }else{
            return 'terjadi kesalahan #ortn';
        }

        $Oknr = array_sum([$hasila,$hasilb,$hasilc,$hasild]);
        $TotalAkhir = $Oknr / $jml_soall;
        $SudahBulat = round($TotalAkhir, 2);

        if ($level == '4' || $level == '3' || $level == '1') {//BAWAHAN
            $tipe_pangkat = 'bawahan';
        }elseif( $level == '10' || $level == '2' || $level == '14'){//BAWAHAN MENENGAH 
            $tipe_pangkat = 'bawahan_menengah';
        }elseif( $level == '11' || $level == '12' ){//ATASAN
            $tipe_pangkat = 'atasan';
        }else{ }

        $data_bobot = DB::table('b_perhitungan_penilaian_kerja')->where('tipe','=',$tipe_pangkat)->first();
        if ($data_bobot) {
            $lkiy = $SudahBulat * (float)$data_bobot->$VerifOrDs;//memisahkan setiap kategori nilai dari verif atau dirisendiri
        }else{
            $lkiy = false;
        }

        return ['belum_bobot' => $SudahBulat, 'sudah_bobot' => round($lkiy,2)];

    }
    protected function SumberNilai($level){
        if ($level == '4' || $level == '3' || $level == '1') {//BAWAHAN
            $tipe_pangkat = 'bawahan';
        }elseif( $level == '10' || $level == '2' || $level == '14'){//BAWAHAN MENENGAH 
            $tipe_pangkat = 'bawahan_menengah';
        }elseif( $level == '11' || $level == '12' ){//ATASAN
            $tipe_pangkat = 'atasan';
        }else{ }
        $Data = DB::table('b_perhitungan_penilaian_kerja')->select('*')->where('tipe','=',$tipe_pangkat)->first();
        return $Data;
    }

    protected function NilaiTugasLain($id_user, $id_versi, $level){
        
        $Data = DB::table('b_pelaksanaan_tugas_lain')->where([['id_user','=',$id_user],['id_versi','=',$id_versi]])->select('id','nilai')->first();


        if ($Data && ($Data->nilai != null)) { $CekData = $Data; }else{ $CekData = '2'; }
        
        if ($level == '4' || $level == '3' || $level == '1') {//BAWAHAN
            return $CekData;
        }elseif( $level == '10' || $level == '2' || $level == '14' ){//BAWAHAN MENENGAH 
            return $CekData;
        }elseif( $level == '11' || $level == '12' || $level == '13'){//ATASAN
            $kjto = 0;
            return $kjto;
        }else{ }
     
    }


    //CEK JAWABAN MENIILAI ATASAN
    protected function NilaiAtasanCek($id_user,$jawaban, $bawahan,$id_versi){

        $Data = DB::table('b_tujuan')
        ->join('b_jawaban','b_jawaban.id_user','=','b_tujuan.id_user')
        ->join('b_soal','b_jawaban.id_soal','=','b_soal.id_soal')
        ->select(
            'b_jawaban.jawaban','b_jawaban.id_jawaban'
            )
        ->where([['b_tujuan.id_user','=',$bawahan],['b_tujuan.id_user_tujuan','=',$id_user],['b_tujuan.id_versi','=',$id_versi],['b_jawaban.jenis_jawaban','=','nilai_atasan'],['b_jawaban.jawaban','=',$jawaban]])
        ->count();

        return $Data;
    }

    //CEK STATUS SELESAI
    protected function CekStatusSelesai($id_versi, $id_user){
        $CekStatuss = DB::table('b_status')->where([['id_versi','=',$id_versi],['id_user','=',$id_user],['status','=', '1']])->count();

        if ($CekStatuss > 0) {
        return 'yes';
        }else{
        return 'no';
        }
    }

    ///////////////////////////////////CEK REKAP PENILAIAN KERJA////////////////////////////////////////////
    
    public function CekRekapPenilaian($id_user_tujuan, $id_user, $id_versi){

        if ($id_versi == '2') {
            
            $id_user_tujuan = Crypt::decryptString($id_user_tujuan);
            $id_userd = Crypt::decryptString($id_user);

            //CEK STATUS SELESAI PENGERJAAN PENILAIAN KERJA
            $HasilStatus = $this->CekStatusSelesai($id_versi, $id_userd);
            if ($HasilStatus == 'no') {
                return Redirect::back()->with('error', 'Status pengerjaan belum selesai (belum melakukan finish)');
            }

            //PRESENSI KEHADIRAN DARI DKK
            //CEK DATA ABSENSI KOSONG / BELUM DIISI
            $DataAbsensi = $this->DataAbsensiV2($id_userd, $id_versi);

            //QUERY JAWABAN DIRISENDIRI DAN BERIVIKASI
            $DataRekap = $this->CekJawabanDanVerif($id_userd,$id_user_tujuan,$id_versi);

            //CEK REKAP BILA SUDAH DI VERIFIKASI ATASAN/ KALAU BELUM, TIDAK BISA DI TAMPILKAN
            foreach ($DataRekap as $key => $cek_ready) {
                $cek[] = $cek_ready->kategori_soal; 
                $level = $cek_ready->level; 
                $nama_lengkap = $cek_ready->nama_lengkap;
            }

            //VALIDASI JIKA DATA ABSENSI MAUPUN DATA REKAP JAWABAN FORM PENILAIAN KOSONG
            if ($DataRekap->isEmpty()) {
                return Redirect::back()->with('error', 'Form Penilaian Kerja Belum Diisi Atau Belum DiVerifikasi');
            }else{
                
                if ($level == '11' || $level == '12' || $level == '13') {
                    //ATASAN MENENGAH TIDAK MEMILIKI ABSENSI
                    $HasilAbsen = '0';
                }else{
                    if($DataAbsensi->isEmpty()){

                    return Redirect::back()->with('error', 'Data Absensi Kehadiran & Pelaksanaan Tugas Lain Belum Diproses Bagian Kepegawaian');
                    }
                    ///////////////////////HITUNG TOTAL POINT BERDASARKAN PENGELOMPOKAN INDUK INDIKATOR///////////////////////////////
                    $DataIndukIndikator = DB::table('b_indikator')->select('id_indikator','nama_indikator')->where('id_versi','=', $id_versi)->get();
                    foreach ($DataIndukIndikator as $Louyi => $klj) {

                        //DATA ABSENSI DAN PELAKSANAAN TUGAS LAIN
                        $TotalPointAbsensi = $this->HitungPointAbsensi($id_userd,$klj->id_indikator);

                        //TOTAL SOAL
                        $cekTotalIndikator = $this->CekTotalIndikator($id_userd,$klj->id_indikator);

                        $HasilAbsen =  [
                                //NAMA_JENIS INDIKATOR ABSENSI
                                'nama_jenis' => $klj->nama_indikator,
                                //FINISH POINT NILAI DARI DKK YANG SUDAH DI KALI BOBOT
                                'finishpoint' => $this->hitungAbsen($TotalPointAbsensi->total_point,$cekTotalIndikator,$level, $TotalPointAbsensi->id_indikator, $id_versi), 
                                //PREPOINT NILAI DARI DKK
                                'prepoint' => $this->HitungJumlahPoint($TotalPointAbsensi->total_point,$cekTotalIndikator,$level, $TotalPointAbsensi->id_indikator, $id_versi )];

                        $HasilAbsen2 =  ['finishpoint' => $TotalPointAbsensi->total_point];

                    }
                }
            }

            //CEK JIKA PELAKSANAAN TUGAS LAIN SUDAH DINILAI ATAU BELUM DARI ATASAN
            $CekPTL = $this->NilaiTugasLain($id_userd, $id_versi, $level);
           
            if ($CekPTL == '2') {
                return Redirect::back()->with('error', 'Pelaksanaan Tugas Lain Belum Diproses');
            }
            //CEK DATA PESAN 
            $Pessan = $this->PesanAtasan($id_userd, $id_user_tujuan, $id_versi);
           
            if (is_null($Pessan)) {
               $pesanCek = 'Tidak ada pesan';
            }else{
               $pesanCek = $Pessan->pesan_isi; 
            }

            //KATEGORI TIPE SOAL A
            $kat_tipe_a = 'a'; for ($t_a=0; $t_a < 4; $t_a++) { //loping kategori A
                $jawab_a[$kat_tipe_a]   = $this->CekJawabankl($id_userd,'a',$kat_tipe_a,$id_versi);//jawabandirisendiri
                $verif_a[$kat_tipe_a]   = $this->CekVerifkl($id_user_tujuan, 'a', $kat_tipe_a, $id_userd, $id_versi);//verifikasiatasan
            $kat_tipe_a++;  }
            //KATEGORI TIPE SOAL B
            $kat_tipe_b = 'a'; for ($t_b=0; $t_b < 4; $t_b++) { //loping kategori 
                $jawab_b[$kat_tipe_b]   = $this->CekJawabankl($id_userd,'b',$kat_tipe_b,$id_versi);//jawabandirisendiri
                $verif_b[$kat_tipe_b]   = $this->CekVerifkl($id_user_tujuan, 'b', $kat_tipe_b, $id_userd, $id_versi);//verifikasiatasan
            $kat_tipe_b++;  }
            //KATEGORI TIPE SOAL C
            $kat_tipe_c = 'a'; for ($t_c=0; $t_c < 4; $t_c++) { //loping kategori 
                $jawab_c[$kat_tipe_c]   = $this->CekJawabankl($id_userd,'c',$kat_tipe_c,$id_versi);//jawabandirisendiri
                $verif_c[$kat_tipe_c]   = $this->CekVerifkl($id_user_tujuan, 'c', $kat_tipe_c, $id_userd, $id_versi);//verifikasiatasan
            $kat_tipe_c++;  }
            //KATEGORI TIPE SOAL D
            $kat_tipe_d = 'a'; for ($t_d=0; $t_d < 4; $t_d++) { //loping kategori 
                $jawab_d[$kat_tipe_d]   = $this->CekJawabankl($id_userd,'d',$kat_tipe_d,$id_versi);//jawabandirisendiri
                $verif_d[$kat_tipe_d]   = $this->CekVerifkl($id_user_tujuan, 'd', $kat_tipe_d, $id_userd, $id_versi);//verifikasiatasan
            $kat_tipe_d++;  }


            if ($level == '4' || $level == '3' || $level == '1') {
                $MenilaiAtasanAkhir = '';
            }else{
                //CEK JAWABAN DARI BAWAHAN FORM TIPE YANG DI GUNAKAN IYALAH FORM B
                foreach($this->CekTujuanNilaiAtasan($id_userd,$id_versi) as $kkiyt => $valAtasan){

                    $kat_jaw = 'a'; for ($t_a=0; $t_a < 4; $t_a++) { //loping kategori A
                    $jawabAnAtas[$kkiyt][$kat_jaw] = $this->NilaiAtasanCek($id_userd,$kat_jaw,$valAtasan->Bawahan, $id_versi);//jawabandirisendiriverifikasiatasan
                    $kat_jaw++;  }
                    
                }

                //BAWAHAN YANG MENILAI ATASAN, BERDASARKAN BANYAKNYA JUMLAH BAWAHAN
             
                $MenilaiAtasanAkhir = $this->HitungNilaiAtasan($jawabAnAtas, $level, $id_versi);   
            }
            
            //MENGHITUNG TOTAL HASIL DARI JAWABAN BERDASARKAN KATEGORI(BELUM DI KALI BOBOT)
            $hurufakhir = 'a';
            for ($kjt=0; $kjt < 4; $kjt++) { 

                $jawaban = 'jawab_'.$hurufakhir.'';
                $verif = 'verif_'.$hurufakhir.'';

                $jml_soall = DB::table('b_soal')
                        ->where([['b_soal.kategori_soal','=',$hurufakhir],['b_soal.id_versi_fk','=',$id_versi]])
                        ->count();

                //HASIL NILAI JAWABAN KATEGORI/TIPE A, (BELUM DI KALI BOBOT)


                $kategorii_ds['tipe_'.$hurufakhir] = $this->HitunngHasilSoal($$jawaban ,$$verif ,$jml_soall,'ds',$level,'form_'.$hurufakhir.'_ds');
                $kategorii_verif['tipe_'.$hurufakhir] = $this->HitunngHasilSoal($$jawaban ,$$verif ,$jml_soall,'verif',$level,'form_'.$hurufakhir.'_atasan');

            $hurufakhir++;
            }

            $LangkahAkhir =  [
                               'nama_lengkap' => $nama_lengkap,
                               'BobotData' => $this->SumberNilai($level),
                               'NilaiDiriSendiri' =>  $kategorii_ds,
                               'NilaiVerif' =>  $kategorii_verif,
                               'Absen' => $HasilAbsen,
                               'TugasLain' => $CekPTL,
                               'Pesan' => $pesanCek,
                               'NilaiDariBawahan' => $MenilaiAtasanAkhir,
                            ];

            if ($level == '4' || $level == '1' || $level == '3') {
                return view('admin.dashboard.penilaiankerja.PenAdmin.rekap_nilai_next.rekap_bawahan',  $LangkahAkhir);
            }else if( $level == '10' || $level == '2' || $level == '14'){
                return view('admin.dashboard.penilaiankerja.PenAdmin.rekap_nilai_next.rekap_atasanmenengah',  $LangkahAkhir);
            }else if( $level == '11' || $level == '12' ){
                return view('admin.dashboard.penilaiankerja.PenAdmin.rekap_nilai_next.rekap_atasan',  $LangkahAkhir);
            }else{
                return 'tes';
            }


        ////////////////////////BATAS UNTUK VERSI SEBELUMNYA VERSI 1///////////////////////    
        }else if($id_versi == '1'){

            $id_user_tujuan = Crypt::decryptString($id_user_tujuan);
            $id_userd = Crypt::decryptString($id_user);

             //CEK STATUS SELESAI PENGERJAAN PENILAIAN KERJA
            $HasilStatus = $this->CekStatusSelesai($id_versi, $id_userd);
            if ($HasilStatus == 'no') {
                return Redirect::back()->with('error', 'Status pengerjaan belum selesai (belum melakukan finish)');
            }
            
            //CEK TUJUAN DARI USER YANG AKAN DITUJU
            $cek_nama_tujuan = $this->CekTujuan($id_userd, $id_versi);
            //QUERY JAWABAN DIRISENDIRI DAN BERIVIKASI
            $DataRekap = $this->CekJawabanDanVerif($id_userd,$id_user_tujuan,$id_versi);
            //DATA ABSENSI DAN PELAKSANAAN TUGAS LAIN
            $DataAbsensi = $this->DataAbsensiV2($id_userd, $id_versi);
           
            foreach ($cek_nama_tujuan as $key => $value) {
            ///////////////////////////PESAN DARI ATASAN/////////////////////////////
                $pesan = $this->PesanAtasan($id_userd, $value->id_user_tujuan, $id_versi);
                if ($pesan != null) {
                    $isi_pesan[] = $pesan->pesan_isi;
                }else{
                    $isi_pesan[] = null;
                }
            ////////////////////////////PESAN DARI ATASAN////////////////////////////
            }
          
            //CEK REKAP BILA SUDAH DI VERIFIKASI ATASAN/ KALAU BELUM, TIDAK BISA DI TAMPILKAN
            foreach ($DataRekap as $key => $cek_ready) {
                $cek[] = $cek_ready->kategori_soal; 
                $level = $cek_ready->level; 
                $nama_lengkap = $cek_ready->nama_lengkap;
            }

            //VALIDASI JIKA DATA ABSENSI MAUPUN DATA REKAP JAWABAN FORM PENILAIAN KOSONG
            if ($DataRekap->isEmpty()) {
                return Redirect::back()->with('error', 'Form Penilaian Kerja Belum Diisi Atau Belum DiVerifikasi');
            }else{
                
                if ($level == '11' || $level == '12' || $level == '13') {
                
                }else{
                    if($DataAbsensi->isEmpty()){
                    return Redirect::back()->with('error', 'Data Absensi Kehadiran & Pelaksanaan Tugas Lain Belum Diproses Bagian Kepegawaian');
                    }
                }
            }
          
            if ($level == '4' || $level == '1' || $level == '3') {
                if (in_array('a', $cek) && in_array('c', $cek) && in_array('d', $cek)) {
                
                }else{
                    return Redirect::back()->with('error', 'Belum Sepenuhnya Diverifikasi');
                }
            }else{
                if (in_array('a', $cek) && in_array('b', $cek) && in_array('c', $cek) && in_array('d', $cek)) {
                
                }else{
                    return Redirect::back()->with('error', 'Belum Sepenuhnya Diverifikasi');
                }
            }


           
            ///////////////////////HITUNG TOTAL POINT BERDASARKAN PENGELOMPOKAN INDUK INDIKATOR///////////////////////////////
            $DataIndukIndikator = DB::table('b_indikator')->select('id_indikator','nama_indikator')->where('id_versi','=', $id_versi)->get();

            ///////----------------DI HITUNG BERDASARAKAN PEMISAH SETIAP INDIKATOR-------------///////////

            if ($level == '11' || $level == '12' || $level == '13') {
                
            }else{
                foreach ($DataIndukIndikator as $keyIn => $h) {

                    //DATA ABSENSI DAN PELAKSANAAN TUGAS LAIN
                    $TotalPointAbsensi = $this->HitungPointAbsensi($id_userd,$h->id_indikator);

                    //TOTAL SOAL
                    $cekTotalSoal = $this->CekTotalIndikator($id_userd,$h->id_indikator);

                    $HasilAbsen[] =  [
                            'nama_jenis' => $h->nama_indikator,

                            'finishpoint' => $this->hitungAbsen($TotalPointAbsensi->total_point,$cekTotalSoal,$level, $TotalPointAbsensi->id_indikator, $id_versi), 

                            'prepoint' => $this->HitungJumlahPoint($TotalPointAbsensi->total_point,$cekTotalSoal,$level, $TotalPointAbsensi->id_indikator, $id_versi)];

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

            }
            ///////----------------DI HITUNG BERDASARAKAN PEMISAH SETIAP INDIKATOR-------------///////////
       
            ///////////////////////HITUNG TOTAL POINT BERDASARKAN PENGELOMPOKAN INDUK INDIKATOR///////////////////////////////



            ////////////////////////////////KATEGORI SOAL A DIRI SENDIRI//////////////////////////////
            $j_aa   = $this->CekJawabankl($id_userd,'a','a',$id_versi);
            $j_ab   = $this->CekJawabankl($id_userd,'a','b',$id_versi);
            $j_ac   = $this->CekJawabankl($id_userd,'a','c',$id_versi);
            $j_ad   = $this->CekJawabankl($id_userd,'a','d',$id_versi);
            ////////////////////////////////KATEGORI SOAL A DIRI SENDIRI//////////////////////////////


            ////////////////////////////////KATEGORI SOAL A VERIFIKASI////////////////////////////////
            $j_aaV  =   $this->CekVerifkl($id_user_tujuan, 'a', 'a', $id_userd, $id_versi);
            $j_abV  =   $this->CekVerifkl($id_user_tujuan, 'a', 'b', $id_userd, $id_versi);
            $j_acV  =   $this->CekVerifkl($id_user_tujuan, 'a', 'c', $id_userd, $id_versi);
            $j_adV  =   $this->CekVerifkl($id_user_tujuan, 'a', 'd', $id_userd, $id_versi);
            ////////////////////////////////KATEGORI SOAL A VERIFIKASI//////////////////////////////



            ////////////////////////////////KATEGORI SOAL B DIRI SENDIRI//////////////////////////////
            $j_ba   =    $this->CekJawabankl($id_userd,'b','a',$id_versi);
            $j_bb   =    $this->CekJawabankl($id_userd,'b','b',$id_versi);
            $j_bc   =    $this->CekJawabankl($id_userd,'b','c',$id_versi);
            $j_bd   =    $this->CekJawabankl($id_userd,'b','d',$id_versi);         
            ////////////////////////////////KATEGORI SOAL B DIRI SENDIRI//////////////////////////////
            ////////////////////////////////KATEGORI SOAL B VERIFIKASI////////////////////////////////
            $j_baV  = $this->CekVerifkl($id_user_tujuan, 'b', 'a', $id_userd, $id_versi);
            $j_bbV  = $this->CekVerifkl($id_user_tujuan, 'b', 'b', $id_userd, $id_versi);
            $j_bcV  = $this->CekVerifkl($id_user_tujuan, 'b', 'c', $id_userd, $id_versi);
            $j_bdV  = $this->CekVerifkl($id_user_tujuan, 'b', 'd', $id_userd, $id_versi);
            ////////////////////////////////KATEGORI SOAL B VERIFIKASI//////////////////////////////
            ////////////////////////////////KATEGORI SOAL C DIRI SENDIRI//////////////////////////////
            $j_ca   =   $this->CekJawabankl($id_userd,'c','a',$id_versi);
            $j_cb   =   $this->CekJawabankl($id_userd,'c','b',$id_versi);
            $j_cc   =   $this->CekJawabankl($id_userd,'c','c',$id_versi);
            $j_cd   =   $this->CekJawabankl($id_userd,'c','d',$id_versi);         
            ////////////////////////////////KATEGORI SOAL C DIRI SENDIRI//////////////////////////////
            ////////////////////////////////KATEGORI SOAL C VERIFIKASI////////////////////////////////
            $j_caV  =   $this->CekVerifkl($id_user_tujuan, 'c', 'a', $id_userd, $id_versi);
            $j_cbV  =   $this->CekVerifkl($id_user_tujuan, 'c', 'b', $id_userd, $id_versi);
            $j_ccV  =   $this->CekVerifkl($id_user_tujuan, 'c', 'c', $id_userd, $id_versi);
            $j_cdV  =   $this->CekVerifkl($id_user_tujuan, 'c', 'd', $id_userd, $id_versi);
            ////////////////////////////////KATEGORI SOAL C VERIFIKASI//////////////////////////////
            ////////////////////////////////KATEGORI SOAL D DIRI SENDIRI//////////////////////////////
            $j_da   =   $this->CekJawabankl($id_userd,'d','a',$id_versi);
            $j_db   =   $this->CekJawabankl($id_userd,'d','b',$id_versi);
            $j_dc   =   $this->CekJawabankl($id_userd,'d','c',$id_versi);
            $j_dd   =   $this->CekJawabankl($id_userd,'d','d',$id_versi);             
            ////////////////////////////////KATEGORI SOAL D DIRI SENDIRI//////////////////////////////
            ////////////////////////////////KATEGORI SOAL D VERIFIKASI////////////////////////////////
            $j_daV  =   $this->CekVerifkl($id_user_tujuan, 'd', 'a', $id_userd, $id_versi);
            $j_dbV  =   $this->CekVerifkl($id_user_tujuan, 'd', 'b', $id_userd, $id_versi);
            $j_dcV  =   $this->CekVerifkl($id_user_tujuan, 'd', 'c', $id_userd, $id_versi);
            $j_ddV  =   $this->CekVerifkl($id_user_tujuan, 'd', 'd', $id_userd, $id_versi);
            ////////////////////////////////KATEGORI SOAL D VERIFIKASI//////////////////////////////


           
            ////////////////////////// PERHITUNGAN FORM A//////////////////////////


            $jumlah_soal_a = DB::table('b_soal')
                        ->where([['b_soal.kategori_soal','=','a'],['b_soal.id_versi_fk','=','1']])
                        ->count();


            $h_a = $j_aa * 4;
            $h_b = $j_ab * 3;
            $h_c = $j_ac * 2;
            $h_d = $j_ad * 1;

            $h_aV = $j_aaV * 4;
            $h_bV = $j_abV * 3;
            $h_cV = $j_acV * 2;
            $h_dV = $j_adV * 1;

            $h_formA = $this->totalCek($h_a,$h_b,$h_c,$h_d, $jumlah_soal_a);
            $h_formAV = $this->totalCek($h_aV,$h_bV,$h_cV,$h_dV, $jumlah_soal_a);


            //persentase akhir bawahan
            $cek_totalkahir = $this->total_akhir($h_formA, $h_formAV, 'a', $level);

            ////////////////////////// PERHITUNGAN FORM A//////////////////////////


            ////////////////////////// PERHITUNGAN FORM B//////////////////////////


            if ($level != '4') {

                $jumlah_soal_b = DB::table('b_soal')
                        ->where([['b_soal.kategori_soal','=','b'],['b_soal.id_versi_fk','=','1']])
                        ->count();

                $h_aB = $j_ba * 4;
                $h_bB = $j_bb * 3;
                $h_cB = $j_bc * 2;
                $h_dB = $j_bd * 1;

                $h_aVB = $j_baV * 4;
                $h_bVB = $j_bbV * 3;
                $h_cVB = $j_bcV * 2;
                $h_dVB = $j_bdV * 1;
                
                $h_formB = $this->totalCek($h_aB,$h_bB,$h_cB,$h_dB, $jumlah_soal_b);
                $h_formBV = $this->totalCek($h_aVB,$h_bVB,$h_cVB,$h_dVB, $jumlah_soal_b);

                //persentase akhir bawahan
                $cek_totalkahirB = $this->total_akhir($h_formB, $h_formBV ,'b', $level);
            }
               
            
            ////////////////////////// PERHITUNGAN FORM B//////////////////////////

            ////////////////////////// PERHITUNGAN FORM C//////////////////////////

            $jumlah_soal_c = DB::table('b_soal')
                        ->where([['b_soal.kategori_soal','=','c'],['b_soal.id_versi_fk','=','1']])
                        ->count();

            $h_aC = $j_ca * 4;
            $h_bC = $j_cb * 3;
            $h_cC = $j_cc * 2;
            $h_dC = $j_cd * 1;

            $h_aVC = $j_caV * 4;
            $h_bVC = $j_cbV * 3;
            $h_cVC = $j_ccV * 2;
            $h_dVC = $j_cdV * 1;
            
            $h_formC = $this->totalCek($h_aC,$h_bC,$h_cC,$h_dC, $jumlah_soal_c);
            $h_formCV = $this->totalCek($h_aVC,$h_bVC,$h_cVC,$h_dVC, $jumlah_soal_c);

            //persentase akhir bawahan
            $cek_totalkahirC = $this->total_akhir($h_formC, $h_formCV ,'c', $level);

            ////////////////////////// PERHITUNGAN FORM C//////////////////////////

            ////////////////////////// PERHITUNGAN FORM D//////////////////////////
            $jumlah_soal_d = DB::table('b_soal')
                        ->where([['b_soal.kategori_soal','=','d'],['b_soal.id_versi_fk','=','1']])
                        ->count();

            $h_aD = $j_da * 4;
            $h_bD = $j_db * 3;
            $h_cD = $j_dc * 2;
            $h_dD = $j_dd * 1;

            $h_aVD = $j_daV * 4;
            $h_bVD = $j_dbV * 3;
            $h_cVD = $j_dcV * 2;
            $h_dVD = $j_ddV * 1;
            
            $h_formD = $this->totalCek($h_aD,$h_bD,$h_cD,$h_dD, $jumlah_soal_d);
            $h_formDV = $this->totalCek($h_aVD,$h_bVD,$h_cVD,$h_dVD, $jumlah_soal_d);

            //persentase akhir bawahan
            $cek_totalkahirD = $this->total_akhir($h_formD, $h_formDV ,'d', $level);
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

            }else if($level == '10' || $level == '2' || $level == '14'){
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


            //dd($HasilAbsen);
            if ($level == '4' || $level == '1' || $level == '3') {
                return view('admin.dashboard.penilaiankerja.PenAdmin.RekapNilai',
                    [
                        'id_user' => $id_userd,
                        'form_a' => round($h_formA,2),
                        'form_aV' => round($h_formAV,2),
                        'total_akhirFormA' => round($cek_totalkahir,2),

                        'form_c' => round($h_formC,2),
                        'form_cV' => round($h_formCV,2),
                        'total_akhirFormC' => round($cek_totalkahirC,2),

                        'form_d' => round($h_formD,2),
                        'form_dV' => round($h_formDV,2),
                        'total_akhirFormD' => round($cek_totalkahirD,2),

                        'nama_lengkap' => $nama_lengkap,
                        'pesan' => $isi_pesan,
                        'Jabatan' => $CekHasilJabatan,

                        'hasilabsen' => $HasilAbsen,
                        'hasilrating' => round($HasilRating,2)

                    ]);

            }elseif($level == '10' || $level == '2' ||  $level == '14'){

                return view('admin.dashboard.penilaiankerja.PenAdmin.RekapNilaiAtasan',
                    [
                        'id_user' => $id_userd,
                        'form_a' => round($h_formA,2),
                        'form_aV' => round($h_formAV,2),
                        'total_akhirFormA' => round($cek_totalkahir,2),

                        'h_form_b' => round($h_formB,2),
                        'h_form_bV' => round($h_formBV,2),
                        'total_akhirFormB' => round($cek_totalkahirB,2),

                        'form_c' => round($h_formC,2),
                        'form_cV' => round($h_formCV,2),
                        'total_akhirFormC' => round($cek_totalkahirC,2),

                        'form_d' => round($h_formD,2),
                        'form_dV' => round($h_formDV,2),
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
                        'form_aV' => round($h_formAV,2),
                        'total_akhirFormA' => round($cek_totalkahir,2),

                        'h_form_b' => round($h_formB,2),
                        'h_form_bV' => round($h_formBV,2),
                        'total_akhirFormB' => round($cek_totalkahirB,2),

                        'form_c' => round($h_formC,2),
                        'form_cV' => round($h_formCV,2),
                        'total_akhirFormC' => round($cek_totalkahirC,2),

                        'form_d' => round($h_formD,2),
                        'form_dV' => round($h_formDV,2),
                        'total_akhirFormD' => round($cek_totalkahirD,2),

                        'nama_lengkap' => $nama_lengkap,
                        'pesan' => $isi_pesan,
                        'Jabatan' => $CekHasilJabatan,

                        'hasilrating' => round($HasilRating,2)

                    ]);
            }   

        }else{

        }

    }




    //CEK TUJUAN 
    protected function CekTujuan($id_userd,$id_versi){
        $Data = DB::table('b_tujuan')
            ->join('users','users.id','=','b_tujuan.id_user_tujuan')
            ->select('id_user_tujuan','users.name','b_tujuan.id_user')
            ->where([['b_tujuan.id_user','=', $id_userd],['b_tujuan.id_versi','=',$id_versi]])
            ->get();
        return $Data;
    }
    //CEK TUJUAN UNTUK MENILAI ATASAN
    protected function CekTujuanNilaiAtasan($id_user_tujuan,$id_versi){
        $Data = DB::table('b_tujuan')
            ->join('users','users.id','=','b_tujuan.id_user_tujuan')
            ->select('id_user_tujuan','b_tujuan.id_user AS Bawahan')
            ->where([['b_tujuan.id_user_tujuan','=', $id_user_tujuan],['b_tujuan.id_versi','=',$id_versi]])
            ->get();
        return $Data;
    }

    //PESAN DARI ATASAN KE BAWAHAN
    protected function PesanAtasan($id_userd, $id_user_tujuan, $id_versi){
        $Data = DB::table('users')
                ->join('b_pesan','b_pesan.pesan_untuk','=','users.id')
                ->select(
                        'b_pesan.pesan_isi'
                        )
                ->where([['b_pesan.pesan_untuk','=',$id_userd],['b_pesan.pesan_dari','=',$id_user_tujuan],['b_pesan.id_versi','=',$id_versi]])
                ->first();
        return $Data;
    }



    //JAWABAN SENDIRI
    protected function CekJawabankl($id_userb, $tipe_kategori, $jawaban, $id_versib){

        $Data = DB::table('users')
        ->join('b_jawaban','users.id','=','b_jawaban.id_user')
        ->join('b_soal','b_jawaban.id_soal','=','b_soal.id_soal')
        ->select(
                'b_jawaban.jawaban'
                )
        ->where([['users.id','=',$id_userb],['b_soal.kategori_soal','=',$tipe_kategori],['b_jawaban.jawaban','=',$jawaban],['b_soal.id_versi_fk','=',$id_versib],['b_jawaban.jenis_jawaban','!=','nilai_atasan']])
        ->count();

        return $Data;

    }

    //JAWABAN VERIF
    protected function CekVerifkl($id_userTujuan, $tipe_kategori, $verif_jawaban, $id_userb, $id_versi){

        $Data = DB::table('users')
                ->join('b_jawaban','users.id','=','b_jawaban.id_user')
                ->join('b_verif_jawaban','b_verif_jawaban.id_jawaban_fk','=','b_jawaban.id_jawaban')
                ->join('b_soal','b_jawaban.id_soal','=','b_soal.id_soal')
                ->select(
                        'b_jawaban.jawaban'
                        )
                ->where([['b_verif_jawaban.id_user_verif','=',$id_userTujuan],['b_soal.kategori_soal','=',$tipe_kategori],['b_verif_jawaban.verif_jawaban','=',$verif_jawaban],['b_jawaban.id_user','=',$id_userb],['b_soal.id_versi_fk','=',$id_versi],['b_jawaban.jenis_jawaban','!=','nilai_atasan']])
                ->count();

        return $Data;
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

        }elseif( $level == '10' || $level == '2' || $level == '14' ){

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


    //UNTUK PERHITUNGAN TOTAL CEK DENGAN JUMLAH SOAL BERDASARKAN TIPE FORM (UNTUK VERSI 1 TAHUN 2020, DI 2021 TIDAK DI PAKAI)
    protected function totalCek($a, $b, $c, $d, $jmlsoal){   
        $cekj = $a + $b + $c + $d;
        $cekbagi = $cekj / $jmlsoal;

        return $cekbagi;
    }
  
    ///////////////////////////////////CEK REKAP PENILAIAN KERJA////////////////////////////////////////////
    //HITUNG ABSENSI KEHADIRAN DAN PELAKSANAAN TUGAS LAIN
    protected function HitungJumlahPoint($TotalPoint, $TotalSoal, $level,  $id_indikator, $versi){

        if ($versi == '1') {//VERSI 1 TAHUN 2020
             if ($level == '4' || $level == '3' || $level == '1') {
                if ($id_indikator == '1') {
                    $cekHasil = $TotalPoint / $TotalSoal;
                    return $cekHasil;        
                }elseif($id_indikator == '2'){
                    $cekHasil = $TotalPoint / $TotalSoal;
                    return $cekHasil;
                }else{
                }
            }elseif( $level == '10' || $level == '2' || $level == '14'){
                if ($id_indikator == '1') {
                    $cekHasil = $TotalPoint / $TotalSoal;
                    return $cekHasil;        
                }elseif($id_indikator == '2'){
                    $cekHasil = $TotalPoint / $TotalSoal;
                    return $cekHasil;
                }else{
                }
            }elseif( $level == '11' || $level == '12' ){
            }else{}

        }else{//VERSI SELAIN 1, MUNGKIN 2 ATAU SETERUSNYA JUGA MULAI DARI VERSI 2 TIDAK ADA PERUBAHAN

            $data = DB::table('b_indikator')->where('id_versi','=',$versi)->get();
            if ($level == '4' || $level == '3' || $level == '1') {     
                foreach($data as $vData){
                    if ($vData->nama_indikator == 'Absensi Kehadiran') {
                        $cekHasil = $TotalPoint / $TotalSoal;
                        return $cekHasil;
                    }
                }
            }elseif( $level == '10' || $level == '2' || $level == '14' ){ 
                foreach($data as $vData){
                    if ($vData->nama_indikator == 'Absensi Kehadiran') {
                        $cekHasil = $TotalPoint / $TotalSoal;
                        return $cekHasil;
                    }
                }
            //LEVEL 11, 12 TIDAK ADA ABSENSI
            }elseif( $level == '11' || $level == '12' ){}else{}
        }
    }

    protected function hitungAbsen($TotalPoint, $TotalSoal, $level,  $id_indikator, $versi){

        if ($versi == '1') {//VERSI LAMA 2020 VERSI 1
          if ($level == '4' || $level == '3' || $level == '1') {//BAWAHAN
                if ($id_indikator == '1') {
                    $cekHasil = $TotalPoint / $TotalSoal;
                    $finish = $cekHasil * 0.15;
                    return $finish;
                }elseif($id_indikator == '2'){//BAWAHAN MENENGAH
                    $cekHasil = $TotalPoint / $TotalSoal;
                    $finish = $cekHasil * 0.05;
                    return $finish;
                }else{ }
            }elseif( $level == '10' || $level == '2' || $level == '14'){

                if ($id_indikator == '1') {
                    $cekHasil = $TotalPoint / $TotalSoal;
                    $finish = $cekHasil * 0.1;
                    return $finish;
                }elseif($id_indikator == '2'){
                    $cekHasil = $TotalPoint / $TotalSoal;
                    $finish = $cekHasil * 0.05;
                    return $finish;
                }else{}
            }elseif( $level == '11' || $level == '12' ){ }else{ }

        }else{//VERSI SELAIN VERSI 1 JIKA TIDAK ADA PERUBAHAN ALUR LAGI
            $data = DB::table('b_indikator')->where('id_versi','=',$versi)->get();
            if ($level == '4' || $level == '3' || $level == '1') {//BAWAHAN      

                //MENGANBIL REFERENSI NILAI ABSENSI DARI DATABASE UNTUK VERSI 2 (TAHUN 2021)
                $SumberNilai = $this->tipeNilaiAbsensi($versi, 'bawahan');

                foreach($data as $vData){
                    if ($vData->nama_indikator == 'Absensi Kehadiran') { 
                        
                        $cekHasil = $TotalPoint / $TotalSoal;
                        $finish = $cekHasil * $SumberNilai->presensi_kehadiran;
                        return $finish;

                    }
                }
            }elseif( $level == '10' || $level == '2' || $level == '14' ){//BAWAHAN MENENGAH 
                $SumberNilai = $this->tipeNilaiAbsensi($versi, 'bawahan_menengah');
                foreach($data as $vData){
                    if ($vData->nama_indikator == 'Absensi Kehadiran') {
                        $cekHasil = $TotalPoint / $TotalSoal;
                        $finish = $cekHasil * $SumberNilai->presensi_kehadiran;
                        return $finish;
                    }
                }
            //LEVEL 11, 12 TIDAK ADA ABSENSI
            }elseif( $level == '11' || $level == '12' ){}else{}
        }
    }

    protected function tipeNilaiAbsensi($versi, $tipeJabataan){
        $SumberNilai = DB::table('b_perhitungan_penilaian_kerja')->where([['id_versi','=',$versi],['tipe','=',$tipeJabataan]])->first();
        if ($SumberNilai) {
            return $SumberNilai;
        }else{
            return '0';
        }
    }



}
