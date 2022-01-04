<?php

namespace App\Http\Controllers\KategoriSebagai;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use App\User as User;
use App\level as Level;
use App\kategori as Kategori;
use DataTables;
use DB;
use Validator;
use Response;
use Redirect;
use Alert;
use Hash;
use Auth;

class KategoriController extends Controller
{	
	///////////////////////////////////////////////index/////////////////////////////////////////////////////////////
	public function index(){

		//$level = Level::orderBy('id_level')->get();
    	return view('admin.dashboard.kategorisebagai.index');//->with('level', $level);
    	
    }
    public function kategorilist(){

        return DataTables::of(DB::table('kategorisebagai')
        ->select('*'))
        ->addColumn('action', function($data){
                        //if(Auth::check() && Auth::user()->level == "1"){
                        $button = '&nbsp;&nbsp;';
                        $button .=   '<a href="kat/'.$data->id_kategori.'/edit">
                                    <button type="button" class="btn btn-primary btn-xs"><span class="fa fa-edit"> Edit Kategori</span></button>
                                    </a>';
                        $button .= '&nbsp;&nbsp;';
                        $button .= '<a href="kat/'.$data->id_kategori.'/destroy" title="hapus" onclick="return confirm(\'Apakah Anda Yakin Menghapus Data Kategori '.$data->nama_kategori.' Ini ? \' ) "><button type="button" class="btn btn-danger btn-xs"><span class="fa fa-trash"> Hapus Kategori</span></button></a>';
                        $button .= '&nbsp;&nbsp;';
                        
                        return $button;
                    })
                    ->rawColumns(['action'])
                    ->make(true);


    }
    ///////////////////////////////////////////////index/////////////////////////////////////////////////////////////

    ///////////////////////////////////////////////tambah/////////////////////////////////////////////////////////////
    public function showtambah(){

        return view('admin.dashboard.kategorisebagai.tambahkategori');
        
    }

    protected function validator(array $data){
            $messages = [
                'namakategori.required'    => 'Nama Kategori dibutuhkan.',
            ];
            return Validator::make($data, [
                'namakategori' => 'required|max:60',
            ], $messages);
    }

        /**
         * Create a new user instance after a valid registration.
         *
         * @param  array  $data
         * @return User
         */
    protected function tambah(array $data){

            $kategori = new Kategori();
            $kategori->nama_kategori  = $data['namakategori'];

            //melakukan save, jika gagal (return value false) lakukan harakiri
            //error kode 500 - internel server error
            if (! $kategori->save())
              abort(500);
    }

    public function tambahprodi(Request $request){

        //$validator = $this->validator($request->all());
        $validator = $this->validator($request->all(), 'kategorisebagai')->validate();
 
        $this->tambah($request->all());
            
        //return response()->json($request->all(),200);
        alert()->success('Kategori', 'Berhasil Simpan Data Kategori')->persistent('Close');

        return Redirect::action('KategoriSebagai\KategoriController@index')
                          ->with('successMessage','Data Kategori "'.Input::get('namakategori').'" telah berhasil ditambah.'); 
        
    }
    ///////////////////////////////////////////////tambah/////////////////////////////////////////////////////////////


    ///////////////////////////////////////////////Edit/////////////////////////////////////////////////////////////
   
        //section edit

    public function showedit($id){

            $data = Kategori::find($id);
            return view('admin.dashboard.kategorisebagai.editkategori',$data);

        }

    public function simpanedit($id){

        $input = Input::all();
     
        $messages = [
            'namakategori.required'    => 'Nama Kategori dibutuhkan.',            
        ];
        

        $validator = Validator::make($input, [
                          'namakategori' => 'required|max:60',
                      ], $messages);

        if($validator->fails()) {
            # Kembali kehalaman yang sama dengan pesan error
            return Redirect::back()->withErrors($validator)->withInput();
          # Bila validasi sukses
        }

        $kategoricon = DB::table('kategorisebagai')->where('id_kategori', $id)->update([
            'nama_kategori' => $input['namakategori'],
            ]);

        if ($kategoricon) {
            alert()->success('Kategori', 'Berhasil Mengubah Kategori')->persistent('Close');
            return Redirect::action('KategoriSebagai\KategoriController@index')
                          ->with('successMessage','Data Kategori "'.Input::get('namakategori').'" telah berhasil diubah.'); 
        }else{
            alert()->error('Kategori', 'Gagal Mengubah Kategori')->persistent('Close');
            return Redirect::route('kategori.show');
        }
    }


    ///////////////////////////////////////////////Edit/////////////////////////////////////////////////////////////


    ///////////////////////////////////////////////Hapus/////////////////////////////////////////////////////////////
    public function destroy($id){

        if($this->cek_akses('49') == 'yes'){

              $nrd = DB::delete("delete from kategorisebagai where id_kategori = '$id'");
            //Jurusan::where('jurKode','=', $id)->first();
            if (!$nrd) {
               
                alert()->error('Kategori', 'Gagal Menghapus Kategori')->persistent('Close');
                return Redirect::route('kategori.show');

            }else{
                alert()->success('Kategori', 'Berhasil Hapus Data Kategori')->persistent('Close');
                return Redirect::route('kategori.show');
                }

        }else{ 
            alert()->error('Akses', 'Anda Tidak Memiliki Akses Untuk Menghapus Data Ini')->persistent('Close');
            return Redirect::back()->with('error', 'Anda Tidak Memiliki Akses Untuk Menghapus Data Ini');

        } 

          
    }
   ///////////////////////////////////////////////Hapus/////////////////////////////////////////////////////////////


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
