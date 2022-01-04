<?php

namespace App\Http\Controllers\SuratTugas;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Encryption\DecryptException;

use App\level as Level;
use App\Pegawai as Pegawai;
use App\jabatan as jabatan;
use App\kategori as kategori;
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



class SuratTugasControllerHead extends Controller
{
   ///////////////////////////////////////////////index/////////////////////////////////////////////////////////////
    public function index(){
        return view('admin.dashboard.surattugas.indexhead');
    }
 
    public function suratlist(Request $request){

        return DataTables::of(DB::table('surat_tugas')
        ->join('kategorisebagai', 'kategorisebagai.id_kategori','=','surat_tugas.kategori_fk' )
        ->select('surat_tugas.id_surattugas','surat_tugas.nomor_surat','surat_tugas.kategori_fk','surat_tugas.nama_kegiatan'
        ,'surat_tugas.penyelengara','surat_tugas.tanggal_kegiatan_mulai','surat_tugas.tanggal_kegiatan_selesai','surat_tugas.jam_kegiatan_mulai','surat_tugas.jam_kegiatan_selesai','surat_tugas.lokasi','surat_tugas.tanggal_acc','kategorisebagai.id_kategori','kategorisebagai.nama_kategori', 'surat_tugas.status_acc')
        
        ->where(function ($data) {
            $data->where('surat_tugas.status_acc','=', 3 )
            ->orWhere('surat_tugas.status_acc','=', 1)
            ->orWhere('surat_tugas.status_acc','=', 2);
                })
        
        )
        
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
                    $button =   '<button type="button" class="btn btn-info btn-xs">Diajukan</button>';
                }elseif ($data->status_acc == 1) {
                    $button =   '<a href="#" title="Ubah Status"><button type="button" id='.$data->id_surattugas.'  class="validasi btn btn-success btn-xs">Diterima</button></a>
                                ';
                }elseif ($data->status_acc == 2){
                    $button =   '<a href="#" title="Ubah Status"><button type="button" id='.$data->id_surattugas.'  class="validasi btn btn-danger btn-xs">Ditolak</button></a>
                                ';
                }elseif ($data->status_acc == 3){
                    $button =   '<a href="#" title="Ubah Status"><button type="button" id='.$data->id_surattugas.'  class="validasi btn btn-warning btn-xs">Proses</button></a>
                                ';
                }else{
                    $button = 'Terjadi kesalahan';
                }
                
                return $button;
            })
            ->rawColumns(['action','tanggal_mulai','tanggal_selesai','status'])
            ->make(true);
            
    }

    ///////////////////////////////////////////////index/////////////////////////////////////////////////////////////

    public function updateverifikasi(){

        $id_surattugas = Input::get('id_surattugas');
        $validasi = Input::get('validasi');

        if ($validasi == 2) {
        $alasan = Input::get('alasan');
        $status_alasan = DB::table('alasan')->insert([
            'keterangan' => $alasan,
            'surat_tugas_ket_fk' => $id_surattugas,
            ]);

        if ($status_alasan) {
            
            }else{
                abort(500);
            }
        }


        if ($validasi == 1) {

            $tanggal = Surattugas::where('id_surattugas',$id_surattugas)->first();
            $tanggal_hari_ini = date('Y-m-d'); 

            if ($tanggal->tanggal_acc == $tanggal_hari_ini) {
               
            }else{
                     $cek_tanggal = DB::table('surat_tugas')->where('id_surattugas', '=', $id_surattugas)->update([
                        'tanggal_acc' => $tanggal_hari_ini,
                        ]);

                      if ($cek_tanggal) {

                        }else{
                        abort(500);
                    }
                }
        }

        $cekstatus = Surattugas::where('id_surattugas',$id_surattugas)->first();

        if ($validasi == $cekstatus->status_acc) {
            alert()->success('Surat Tugas', 'Berhasil Memproses Data')->persistent('Close');
            return Redirect::action('SuratTugas\SuratTugasControllerHead@index'); 
            }else{
            $status_proses = DB::table('surat_tugas')->where('id_surattugas', $id_surattugas)->update([
                'status_acc' => $validasi,
                ]);

            if ($status_proses) {
                alert()->success('Surat Tugas', 'Berhasil Memproses Data')->persistent('Close');
                return Redirect::action('SuratTugas\SuratTugasControllerHead@index'); 
            }elseif(!$status_proses){
                alert()->error('Surat Tugas', 'Gagal Memproses Data')->persistent('Close');
                return Redirect::action('SuratTugas\SuratTugasControllerHead@index');
            }else{
                abort(500);
            }
        }
    }
 
    ///////////////////////////////////////////////Index Berkas//////////////////////////////////////////////////////
    public function indexberkas($id){


        $berkas = DB::table('berkas')
        ->select('*')
        ->where('id_srt_tgs_fk', '=', $id)
        ->get();


        return view('admin.dashboard.surattugas.indexfile', ['id_surattugas' => $id])->with('list_berkas', $berkas);
    
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


        $peserta = DB::table('peserta')
        ->join('pegawai', 'peserta.id_pegawaip_fk','=','pegawai.id_pegawai' )
        ->select('peserta.id_peserta','pegawai.nama_karyawan','peserta.nipp','peserta.nidnp','peserta.nama_jabatanp','peserta.id_pegawaip_fk', 'peserta.id_surattugas_fk')
        ->where('peserta.id_surattugas_fk', '=', $id)
        ->get();

        return view('admin.dashboard.surattugas.indexpeserta',['id_surattugas_fk' => $id])->with('list_peserta', $peserta);
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
  
    protected function namakategori($id){
        $datakategori = kategori::where('id_kategori',$id)->first();
        return $datakategori->nama_kategori;
    }
    protected function tanggal_indo($tanggal) {
        $bulan = array(1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember');
        $split = explode('-', $tanggal);
        return $split[2] . ' ' . $bulan[(int) $split[1]] . ' ' . $split[0];
    }



}
