<?php

namespace App\Http\Controllers\BukaTutupBuku;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Collection;

use DataTables;
use DB;
use Validator; 
use Response;
use Redirect;
use Auth;
Use Exception;
use DateTime;


class BukaTutupBuku extends Controller
{	

	public function index_buktup(){

		$cek = DB::table('a_tanggal_bukabuku')->select('*')->orderBy('id','DESC')->get();

		return view('admin.dashboard.bukatutupbuku.index',['cek_data' => $cek]);

	

	} 

	public function destroy_buktup($id){

		  try {
                DB::table('a_tanggal_bukabuku')->where('id', '=', $id)->delete();
                return Redirect::back()->with('success', 'Berhasil Hapus Data');
            } catch (Exception $e) {
                report($e);
                return Redirect::back()->with('error', 'Gagal Menghapus Data');
            }


	}

	public function fecth_data_calender(){

		    $json = array();
		   
		    $sqlQuery = DB::table('a_tanggal_bukabuku_copy1')
		    			->orderBy('id')
		    			->get();

		    $eventArray = array();
		   
		   	foreach ($sqlQuery as $key => $cek) {
		   		$eventArray[] = ['title' => $cek->title, 'start' => $cek->start, 'end' => $cek->end];
		   		
		   	}
		   	return response()->json($eventArray);


	}

}
