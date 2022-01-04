
@extends('admin.layout.master')

@section('content')
@php

function cek_akses($aModul) {

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


  if ($Ddiri->level == 1 || $Ddiri->level == 3 || $Ddiri->level == 4) {
    $link = 4;
  }elseif($Ddiri->level == 10 || $Ddiri->level == 2 ){
    $link = 10;
  }else{
    $link = $Ddiri->level;
  }

@endphp
<br>
<div class="container-fluid"> 
       @if ($message = Session::get('success'))
           <div class="uk-alert uk-alert-success" data-uk-alert>
              <a href="" class="uk-alert-close uk-close"></a>
             
              <div class="alert alert-success alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <strong>{{ $message }}</strong>
              </div>
              
          </div>
        @endif

        @if ($message = Session::get('error'))
           <div class="uk-alert uk-alert-error" data-uk-alert>
              <a href="" class="uk-alert-close uk-close"></a>
             
              <div class="alert alert-error alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <strong>{{ $message }}</strong>
              </div>
              
          </div>
        @endif
    <!-- Main content -->
        <div class="row">
          <div class="col-md-3">

            <!-- Profile Image -->
            <div class="card card-primary card-outline">
              <div class="card-body box-profile">
                <div class="text-center">
                  @if($Ddiri->jenis_kelamin == 'Pria')
                  <img class="profile-user-img img-fluid img-circle"
                       src="{{ URL::asset('admin/dist/img/avatar5.png') }}"
                       alt="User profile picture">
                  @else
                  <img class="profile-user-img img-fluid img-circle"
                       src="{{ URL::asset('admin/dist/img/avatar3.png') }}"
                       alt="User profile picture">
                  @endif
                 
                </div>

                <h3 class="profile-username text-center">{{ $Ddiri->nama_lengkap }}</h3>
                <h4 class="profile-username text-center">{{ $Ddiri->nama_mandarin }}</h4>

                @foreach($jabatan as $keyf => $s)
                @if($keyf == 0)
                <p class="text-muted text-center">{{ $s->nama_jabatan }} - {{ $s->nama_detail_jabatan }}</p>
                @endif
                @endforeach

              </div>
              <!-- /.card-body -->
            </div>
            <!-- /.card -->

            <!-- About Me Box -->
            <div class="card card-primary">
              <div class="card-header">
                <h3 class="card-title">About Me</h3>
              </div>
              <!-- /.card-header -->
              <div class="card-body">
                <strong><i class="fas fa-heart"></i> Agama</strong>

                <p class="text-muted">
                 {{ $Ddiri->agama }}
                </p>

                <hr>
                <strong><i class="fas fa-map-marked-alt"></i> Tempat Lahir</strong>

                <p class="text-muted">
                 {{ $Ddiri->nama }} - {{ $Ddiri->nama_kab }}
                </p>

                <hr>

                <strong><i class="fas fa-map-marker-alt mr-1"></i> Alamat</strong>

                <p class="text-muted">{{ $Ddiri->alamat_sekarang }}</p>

                <hr>

                <strong><i class="fas fa-at"></i> E-mail</strong>

                <p class="text-muted">{{ $Ddiri->email }}</p>

                <hr>
                <strong><i class="fas fa-comments"></i> Kontak Whatsapp</strong>

                <p class="text-muted">{{ $Ddiri->nomor_wa }}</p>

                <hr>

                <strong><i class="fas fa-venus-mars"></i> Jenis Kelamin</strong>

                <p class="text-muted">{{ $Ddiri->jenis_kelamin }}</p>

               
              </div>
              <!-- /.card-body -->
            </div>
            <!-- /.card -->
          </div>
          <!-- /.col -->
          <div class="col-md-9">
            <div class="card">
              <div class="card-header p-2" id="myTab">
                <ul class="nav nav-pills">
                  <li class="nav-item"><a class="nav-link active" href="#activity" data-toggle="tab"><b>Umum</b></a></li>
                  {{-- <li class="nav-item"><a class="nav-link" href="#timeline" data-toggle="tab">Jabatan</a></li> --}}
                  <li class="nav-item"><a class="nav-link" href="#settings" data-toggle="tab"><b>Pendidikan</b></a></li>
                  <li class="nav-item"><a class="nav-link" href="#marital" data-toggle="tab"><b>Marital dll</b></a></li>
                  <li class="nav-item"><a class="nav-link" href="#berkas" data-toggle="tab"><b>Upload Berkas</b></a></li>&nbsp;&nbsp;
                  <li class="nav-item"><a class="nav-link bg-warning" href="{{ Route('IndexAdminPen',$link) }}"><span class="fa fa-arrow-left"></span><b> Back</b></a></li>
                </ul>
              </div><!-- /.card-header -->
            
            </div> 

              <div class="tab-content">
              <div class="active tab-pane" id="activity">    
                
              <div class="card">
                <div class="card-header bg-info">
                  <h3 class="card-title">Data Diri</h3>
                </div>
                  <div class="card-body">
                       
                    <div class="table-responsive">
                      <table id="cek_penilaian" class="table table-striped dt-responsive display">
                        <thead>
                        <tr>
                          <th><span class="fa fa-eye"></span></th>
                          <th nowrap="">Nomor KTP</th> 
                          <th nowrap="">Aktif KTP</th>
                          <th nowrap="">Nomor NPWP</th>
                          <th nowrap="">Tanggal Lahir</th>
                          <th nowrap="">Status Marital</th>
                          </tr>
                        </thead>
                      </table>
                    </div>
                    <!-- /.tab-content -->
                 </div><!-- /.card-body -->
              </div>


              <div class="card">
                <div class="card-header bg-info">
                  <h3 class="card-title">Identitas</h3>
                </div>
                <div class="card-body" style="padding-top: 5px;">
                  
                  <div class="table-responsive">
                    <table id="" class="table table-striped dt-responsive display">
                      <thead>
                        <tr>
                          <th nowrap="">No</th> 
                          <th nowrap="">Identitas yang dimiliki</th> 
                          
                        </tr>
                      </thead>
                      <tbody>
                        @forelse($iden as $keyiden => $showiden)
                        <tr>
                          <td>{{ $keyiden + 1 }}</td>
                          <td>{{ $showiden->jenis }}</td>
                        </tr>
                        @empty
                        <tr>
                          <td style="text-align: center;" colspan="10">Tidak Ada Data</td>
                        </tr>
                        @endforelse
                      </tbody>
                    </table>
                  </div>
                  <!-- /.tab-content -->
                </div><!-- /.card-body -->
              </div>



              </div><!--batas id-->


              <div class="tab-pane" id="timeline"> 
                <div class="card ">
                  <div class="card-header bg-info">
                    <h3 class="card-title">Jabatan</h3>
                  </div>
                  <div class="card-body">
                    <div class="table-responsive">
                      <table id="" class="table table-striped dt-responsive display">
                        <thead>
                          <tr>
                            <th nowrap="">No</th> 
                            <th nowrap="">Nama jabatan</th>
                            <th nowrap="">Sub jabatan</th>
                          </tr>
                        </thead>
                        <tbody>
                          @forelse($jabatan as $keyjab => $showjab)
                          <tr>
                            <td>{{ $keyjab + 1 }}</td>
                            <td>{{ $showjab->nama_jabatan }}</td>
                            <td>{{ $showjab->nama_detail_jabatan }}</td>
                          </tr>
                          @empty
                          <tr>
                            <td style="text-align: center;" colspan="10">Tidak Ada Data</td>
                          </tr>
                          @endforelse
                        </tbody>
                      </table>
                    </div>
                    <!-- /.tab-content -->
                  </div><!-- /.card-body -->
                </div>

                <div class="card ">
                  <div class="card-header bg-info">
                    <h3 class="card-title">Jabatan Akademik</h3>
                  </div>
                  <div class="card-body">
                    
                    <div class="table-responsive">
                      <table id="" class="table table-striped dt-responsive display">
                        <thead>
                          <tr>
                            <th nowrap="">No</th> 
                            <th nowrap="">Jabatan Akademik</th>
                            <th nowrap="">Status Serdos</th>
                            <th nowrap="">Nomor Serdos</th>
                          </tr>
                        </thead>
                        <tbody>
                          @forelse($jabakademik as $keyjabak => $showjabak)
                          <tr>
                            <td>{{ $keyjab + 1 }}</td>
                            <td>{{ $showjabak->nama_jab_akademik }}</td>
                           <td>
                              @if($showjabak->serdos == 'Sudah')
                              <span class="badge badge-pill badge-success">{{ $showjabak->serdos }}</span>
                              @elseif($showjabak->serdos == 'Proses')
                              <span class="badge badge-pill badge-warning">{{ $showjabak->serdos }}</span>
                              @elseif($showjabak->serdos == 'Belum')
                              <span class="badge badge-pill badge-danger">{{ $showjabak->serdos }}</span>
                              @else
                              Terjadi Kesalahan
                              @endif

                            </td>
                            
                            <td>{{ $showjabak->no_serdos }}</td>
                          
                          </tr>
                          @empty
                          <tr>
                            <td style="text-align: center;" colspan="10">Tidak Ada Data</td>
                          </tr>
                          @endforelse
                        </tbody>
                      </table>
                    </div>
                    <!-- /.tab-content -->
                  </div><!-- /.card-body -->
                </div>
              </div>   

              <div class="tab-pane" id="settings"> 
                <div class="card ">
                  <div class="card-header bg-info">
                    <h3 class="card-title">Sekolah Menengah Atas(Sederajat)</h3>
                  </div>
                  <div class="card-body">
                    
                    <div class="table-responsive">
                      <table id="" class="table table-striped dt-responsive display">
                        <thead>
                          <tr>
                            <th nowrap="">No</th> 
                            <th nowrap="">Nama Sekolah</th>
                            <th nowrap="">Jurusan</th>
                            <th nowrap="">Mulai Pendidikan</th>
                            <th nowrap="">Selesai Pendidikan</th>
                          </tr>
                        </thead>
                        <tbody>
                          @forelse($sma as $keysma => $showsma)
                          <tr>
                            <td>{{ $keysma + 1 }}</td>
                            <td>{{ $showsma->nama_sekolah }}</td>
                            <td>{{ $showsma->jurusan }}</td>
                            <td><span class="badge badge-pill badge-success">{{ $showsma->mulai_pendidikan }} </span></td>
                            <td><span class="badge badge-pill badge-danger">{{ $showsma->selesai_pendidikan }}</span></td>
                           
                          </tr>
                          @empty
                          <tr>
                            <td style="text-align: center;" colspan="10">Tidak Ada Data</td>
                          </tr>
                          @endforelse
                        </tbody>
                      </table>
                    </div>
                    <!-- /.tab-content -->
                  </div><!-- /.card-body -->
                </div>
                <div class="card ">
                  <div class="card-header bg-info">
                    <h3 class="card-title">Perguruan Tinggi</h3>
                  </div>
                  <div class="card-body">
                    
                    <div class="table-responsive">
                      <table id="" class="table table-striped dt-responsive display">
                        <thead>
                          <tr>
                            <th nowrap="">No</th> 
                            <th nowrap="">Nama Perguruan Tinggi</th>
                            <th nowrap="">Program Studi</th>
                            <th nowrap="">Tingkat</th>
                            <th nowrap="">IPK</th>
                            <th nowrap="">Mulai Pendidikan</th>
                            <th nowrap="">Selesai Pendidikan</th>
                          </tr>
                        </thead>
                        <tbody>
                          @forelse($perting as $keyperting => $showperting)
                          <tr>
                            <td>{{ $keyperting + 1 }}</td>
                            <td>{{ $showperting->nama_sekolah_perting }}</td>
                            <td>{{ $showperting->program_studi }}</td>
                            <td>{{ $showperting->tingkat }}</td>
                            <td>{{ $showperting->ipk }}</td>
                            <td><span class="badge badge-pill badge-success">{{ $showperting->mulai_pendidikan }} </span></td>
                            <td><span class="badge badge-pill badge-danger">{{ $showperting->selesai_pendidikan }}</span></td>
                          </tr>
                          @empty
                          <tr>
                            <td style="text-align: center;" colspan="10">Tidak Ada Data</td>
                          </tr>
                          @endforelse
                        </tbody>
                      </table>
                    </div>
                    <!-- /.tab-content -->
                  </div><!-- /.card-body -->
                </div>

            </div>

            <div class="tab-pane" id="marital"> 
            

              <div class="card ">
                <div class="card-header bg-info">
                  <h3 class="card-title">Marital</h3>
                </div>
                <div class="card-body">
                  
                  <div class="table-responsive">
                    <table id="" class="table table-striped dt-responsive display">
                      <thead>
                        <tr>
                          <th nowrap="">No</th> 
                          <th nowrap="">Nama Suami/Istri</th>
                          <th nowrap="">Pekerjaan Suami/Istri</th>
                          <th nowrap="">Nomor Telepon Suami/Istri</th>
                        </tr>
                      </thead>
                      <tbody>
                        @forelse($maritalpasangan as $keymari => $showmari)
                        @if($keymari == 0)
                        <tr>
                          <td>{{ $keymari + 1 }}</td>
                          <td>{{ $showmari->nama_pasangan }}</td>
                          <td>{{ $showmari->pekerjaan_pasangan }}</td>
                          <td>{{ $showmari->nomor_telepon_pasangan }}</td>
                        </tr>
                        @endif
                        @empty
                        <tr>
                          <td style="text-align: center;" colspan="10">Tidak Ada Data</td>
                        </tr>
                        @endforelse
                      </tbody>
                    </table>
                  </div>
                  <!-- /.tab-content -->
                </div><!-- /.card-body -->
              </div>

              <div class="card ">
                <div class="card-header bg-info">
                  <h3 class="card-title">Marital</h3>
                </div>
                <div class="card-body">
                  
                  <div class="table-responsive">
                    <table id="" class="table table-striped dt-responsive display">
                      <thead>
                        <tr>
                          <th nowrap="">No</th> 
                          <th nowrap="">Nama Anak</th>
                          <th nowrap="">Tanggal Lahir Anak</th>
                          <th nowrap="">Jenis Kelamin Anak</th>
                        </tr>
                      </thead>
                      <tbody>
                        @forelse($marital as $keymari => $showmari)
                        <tr>
                          <td>{{ $keymari + 1 }}</td>
                          <td>{{ $showmari->nama_anak }}</td>
                          <td>{{ $showmari->ttl_anak }}</td>
                          <td>{{ $showmari->jenis_kelamin_anak }}</td>
                        </tr>
                        @empty
                        <tr>
                          <td style="text-align: center;" colspan="10">Tidak Ada Data</td>
                        </tr>
                        @endforelse
                      </tbody>
                    </table>
                  </div>
                  <!-- /.tab-content -->
                </div><!-- /.card-body -->
              </div>

              <div class="card ">
                <div class="card-header bg-info">
                  <h3 class="card-title">Kontak Darurat</h3>
                </div>
                <div class="card-body">
                  
                  <div class="table-responsive">
                    <table id="" class="table table-striped dt-responsive display">
                      <thead>
                        <tr>
                          <th nowrap="">Nama</th>
                          <th nowrap="">Hubungan</th>
                          <th nowrap="">Nomor Telepon</th>
                          <th nowrap="">Kota</th>
                      </thead>
                      <tbody>
                        
                        <tr>
                          <td>{{ $Ddiri->nama_kd }}</td>
                          <td>{{ $Ddiri->hubungan_kd }}</td>
                          <td>{{ $Ddiri->nomor_telepon_kd }}</td>
                          <td>{{ $Ddiri->kota_kd }}</td>
                        </tr>
                    
                      
                      </tbody>
                    </table>
                  </div>
                  <!-- /.tab-content -->
                </div><!-- /.card-body -->
              </div>


            </div>


            <div class="tab-pane" id="berkas"> 
              
              <div class="card ">
                <div class="card-header bg-info">
                  <h3 class="card-title">Upload Berkas</h3>
                </div>
                <div class="card-body">
                  <form action="{{ route('BerkasDataDiriPenKerja') }}" role="form" method="POST" accept-charset="utf-8" enctype='multipart/form-data'>
                    @csrf
                   <div class="table-responsive">
                    <table class="table table-striped dt-responsive display">
                      <thead>
                        <tr>
                          <th nowrap="">File</th>
                          <th nowrap="">Aksi</th>
                          <th nowrap="">Status</th>
                        </tr>
                      </thead>
                      <tbody>
                          <tr>
                            <td>KTP</td>
                            <td>
                              @if(CekSediaBerkas('ktp','input',$id_user) == 'no')
                                
                              @else
                                  {!! CekSediaBerkas('ktp','input',$id_user)['valueNya'] !!}
                                  <a href="{{ Route('HapusBerkasDataDiri',['id_berkass' => CekSediaBerkas('ktp','input',$id_user)['id']]) }}" onclick="return confirm('Anda yakin menghapus data ini ?')"><button type="button" class="btn btn-outline-danger btn-flat btn-xs" style="float:right;"><span class="fa fa-trash" > </span> hapus</button></a>

                                  <a href="{{ Route('LihatBerkasDatdir',['id_berkass' => CekSediaBerkas('ktp','input',$id_user)['id']]) }}"><button type="button" class="btn btn-outline-info btn-flat btn-xs" style="float:right;"><span class="fa fa-download" > </span> Unduh</button></a>

                              @endif
                            </td>
                            <td>
                              {!! CekSediaBerkas('ktp','label',$id_user) !!}
                            </td>
                          </tr>
                          <tr>
                            <td>NPWP</td>
                            <td>
                              @if(CekSediaBerkas('npwp','input',$id_user) == 'no')
                                
                              @else
                                  {!! CekSediaBerkas('npwp','input',$id_user)['valueNya'] !!}
                                  <a href="{{ Route('HapusBerkasDataDiri',['id_berkass' => CekSediaBerkas('npwp','input',$id_user)['id']]) }}" onclick="return confirm('Anda yakin menghapus data ini ?')"><button type="button" class="btn btn-outline-danger btn-xs" style="float:right;"><span class="fa fa-trash" > </span> hapus</button></a>

                                  <a href="{{ Route('LihatBerkasDatdir',['id_berkass' => CekSediaBerkas('npwp','input',$id_user)['id']]) }}"><button type="button" class="btn btn-outline-info btn-flat btn-xs" style="float:right;"><span class="fa fa-download" > </span> Unduh</button></a>
                              @endif
                            </td>
                            <td>
                             {!! CekSediaBerkas('npwp','label',$id_user) !!}
                            </td>
                          </tr>
                          <tr>
                            <td>KK</td>
                            <td>
                              @if(CekSediaBerkas('kk','input',$id_user) == 'no')
                               
                              @else
                                  {!! CekSediaBerkas('kk','input',$id_user)['valueNya'] !!}
                                  <a href="{{ Route('HapusBerkasDataDiri',['id_berkass' => CekSediaBerkas('kk','input',$id_user)['id']]) }}" onclick="return confirm('Anda yakin menghapus data ini ?')"><button type="button" class="btn btn-outline-danger btn-xs" style="float:right;"><span class="fa fa-trash" > </span> hapus</button></a>

                                  <a href="{{ Route('LihatBerkasDatdir',['id_berkass' => CekSediaBerkas('kk','input',$id_user)['id']]) }}"><button type="button" class="btn btn-outline-info btn-flat btn-xs" style="float:right;"><span class="fa fa-download" > </span> Unduh</button></a>
                              @endif
                            </td>
                            <td>
                             {!! CekSediaBerkas('kk','label',$id_user) !!}
                            </td>
                          </tr>
                          <tr>
                            <td>BPJS Ketenagakerjaan</td>
                            <td>
                              @if(CekSediaBerkas('bpjs_ketenagakerjaan','input',$id_user) == 'no')
                               
                              @else
                                  {!! CekSediaBerkas('bpjs_ketenagakerjaan','input',$id_user)['valueNya'] !!}
                                  <a href="{{ Route('HapusBerkasDataDiri',['id_berkass' => CekSediaBerkas('bpjs_ketenagakerjaan','input',$id_user)['id']]) }}" onclick="return confirm('Anda yakin menghapus data ini ?')"><button type="button" class="btn btn-outline-danger btn-xs" style="float:right;"><span class="fa fa-trash" > </span> hapus</button></a>

                                  <a href="{{ Route('LihatBerkasDatdir',['id_berkass' => CekSediaBerkas('bpjs_ketenagakerjaan','input',$id_user)['id']]) }}"><button type="button" class="btn btn-outline-info btn-flat btn-xs" style="float:right;"><span class="fa fa-download" > </span> Unduh</button></a>
                              @endif
                            </td>
                            <td>
                             {!! CekSediaBerkas('bpjs_ketenagakerjaan','label',$id_user) !!}
                            </td>
                          </tr>
                          <tr>
                            <td>BPJS Kesehatan</td>
                            <td>
                              @if(CekSediaBerkas('bpjs_kesehatan','input',$id_user) == 'no')
                               
                              @else
                                  {!! CekSediaBerkas('bpjs_kesehatan','input',$id_user)['valueNya'] !!}
                                  <a href="{{ Route('HapusBerkasDataDiri',['id_berkass' => CekSediaBerkas('bpjs_kesehatan','input',$id_user)['id']]) }}" onclick="return confirm('Anda yakin menghapus data ini ?')"><button type="button" class="btn btn-outline-danger btn-xs" style="float:right;"><span class="fa fa-trash" > </span> hapus</button></a>

                                  <a href="{{ Route('LihatBerkasDatdir',['id_berkass' => CekSediaBerkas('bpjs_kesehatan','input',$id_user)['id']]) }}"><button type="button" class="btn btn-outline-info btn-flat btn-xs" style="float:right;"><span class="fa fa-download" > </span> Unduh</button></a>
                              @endif
                            </td>
                            <td>
                             {!! CekSediaBerkas('bpjs_kesehatan','label',$id_user) !!}
                            </td>
                          </tr>
                          <tr>
                            <td>SIM</td>
                            <td>
                              @if(CekSediaBerkas('sim','input',$id_user) == 'no')
                                
                              @else
                                  {!! CekSediaBerkas('sim','input',$id_user)['valueNya'] !!}
                                  <a href="{{ Route('HapusBerkasDataDiri',['id_berkass' => CekSediaBerkas('sim','input',$id_user)['id']]) }}" onclick="return confirm('Anda yakin menghapus data ini ?')"><button type="button" class="btn btn-outline-danger btn-xs" style="float:right;"><span class="fa fa-trash" > </span> hapus</button></a>

                                  <a href="{{ Route('LihatBerkasDatdir',['id_berkass' => CekSediaBerkas('sim','input',$id_user)['id']]) }}"><button type="button" class="btn btn-outline-info btn-flat btn-xs" style="float:right;"><span class="fa fa-download" > </span> Unduh</button></a>
                              @endif
                            </td>
                            <td>
                             {!! CekSediaBerkas('sim','label',$id_user) !!}
                            </td>
                          </tr>
                          
                          <tr>
                            <td colspan="10" class="bg-secondary" style="height: 0px; padding: 2px; text-align: center; font-weight:bold;">Akademik</td>
                          </tr>
                          <tr>
                            <td>Ijazah</td>
                            <td>
                              @if(CekSediaBerkas('ijazah','input',$id_user) == 'no')
                              
                              @else
                                  {!! CekSediaBerkas('ijazah','input',$id_user)['valueNya'] !!}
                                  <a href="{{ Route('HapusBerkasDataDiri',['id_berkass' => CekSediaBerkas('ijazah','input',$id_user)['id']]) }}" onclick="return confirm('Anda yakin menghapus data ini ?')"><button type="button" class="btn btn-outline-danger btn-xs" style="float:right;"><span class="fa fa-trash" > </span> hapus</button></a>

                                  <a href="{{ Route('LihatBerkasDatdir',['id_berkass' => CekSediaBerkas('ijazah','input',$id_user)['id']]) }}"><button type="button" class="btn btn-outline-info btn-flat btn-xs" style="float:right;"><span class="fa fa-download" > </span> Unduh</button></a>
                              @endif
                            </td>
                            <td>
                             {!! CekSediaBerkas('ijazah','label',$id_user) !!}
                            </td>
                          </tr>
                         
                          <tr>
                            <td>Transkip Akademik</td>
                            <td>
                               @if(CekSediaBerkas('transkrip','input',$id_user) == 'no')
                                
                              @else
                                  {!! CekSediaBerkas('transkrip','input',$id_user)['valueNya'] !!}
                                  <a href="{{ Route('HapusBerkasDataDiri',['id_berkass' => CekSediaBerkas('transkrip','input',$id_user)['id']]) }}" onclick="return confirm('Anda yakin menghapus data ini ?')"><button type="button" class="btn btn-outline-danger btn-xs" style="float:right;"><span class="fa fa-trash" > </span> hapus</button></a>

                                  <a href="{{ Route('LihatBerkasDatdir',['id_berkass' => CekSediaBerkas('transkrip','input',$id_user)['id']]) }}"><button type="button" class="btn btn-outline-info btn-flat btn-xs" style="float:right;"><span class="fa fa-download" > </span> unduh</button></a>
                              @endif
                            </td>
                            <td>
                             {!! CekSediaBerkas('transkrip','label',$id_user) !!}
                            </td>
                          </tr>

                          <tr>
                            <td>Ijazah S1</td>
                            <td>
                              @if(CekSediaBerkas('ijazahs1','input',$id_user) == 'no')
                                
                              @else
                                  {!! CekSediaBerkas('ijazahs1','input',$id_user)['valueNya'] !!}
                                  <a href="{{ Route('HapusBerkasDataDiri',['id_berkass' => CekSediaBerkas('ijazahs1','input',$id_user)['id']]) }}" onclick="return confirm('Anda yakin menghapus data ini ?')"><button type="button" class="btn btn-outline-danger btn-xs" style="float:right;"><span class="fa fa-trash" > </span> hapus</button></a>

                                  <a href="{{ Route('LihatBerkasDatdir',['id_berkass' => CekSediaBerkas('ijazahs1','input',$id_user)['id']]) }}"><button type="button" class="btn btn-outline-info btn-flat btn-xs" style="float:right;"><span class="fa fa-download" > </span> Unduh</button></a>
                              @endif
                            </td>
                            <td>
                             {!! CekSediaBerkas('ijazahs1','label',$id_user) !!}
                            </td>
                          </tr>
                          <tr>
                          <tr>
                            <td>Transkrip S1</td>
                            <td>
                              @if(CekSediaBerkas('transkrips1','input',$id_user) == 'no')
                                
                              @else
                                  {!! CekSediaBerkas('transkrips1','input',$id_user)['valueNya'] !!}
                                  <a href="{{ Route('HapusBerkasDataDiri',['id_berkass' => CekSediaBerkas('transkrips1','input',$id_user)['id']]) }}" onclick="return confirm('Anda yakin menghapus data ini ?')"><button type="button" class="btn btn-outline-danger btn-xs" style="float:right;"><span class="fa fa-trash" > </span> hapus</button></a>

                                  <a href="{{ Route('LihatBerkasDatdir',['id_berkass' => CekSediaBerkas('transkrips1','input',$id_user)['id']]) }}"><button type="button" class="btn btn-outline-info btn-flat btn-xs" style="float:right;"><span class="fa fa-download" > </span> Unduh</button></a>
                              @endif
                            </td>
                            <td>
                             {!! CekSediaBerkas('transkrips1','label',$id_user) !!}
                            </td>
                          </tr>
                          <tr>
                            <td>Ijazah S2</td>
                            <td>
                              @if(CekSediaBerkas('ijazahs2','input',$id_user) == 'no')
                                
                              @else
                                  {!! CekSediaBerkas('ijazahs2','input',$id_user)['valueNya'] !!}
                                  <a href="{{ Route('HapusBerkasDataDiri',['id_berkass' => CekSediaBerkas('ijazahs2','input',$id_user)['id']]) }}" onclick="return confirm('Anda yakin menghapus data ini ?')"><button type="button" class="btn btn-outline-danger btn-xs" style="float:right;"><span class="fa fa-trash" > </span> hapus</button></a>

                                  <a href="{{ Route('LihatBerkasDatdir',['id_berkass' => CekSediaBerkas('ijazahs2','input',$id_user)['id']]) }}"><button type="button" class="btn btn-outline-info btn-flat btn-xs" style="float:right;"><span class="fa fa-download" > </span> Unduh</button></a>
                              @endif
                            </td>
                            <td>
                             {!! CekSediaBerkas('ijazahs2','label',$id_user) !!}
                            </td>
                          </tr>
                          <tr>
                            <td>Transkrip S2</td>
                            <td>
                              @if(CekSediaBerkas('transkrips2','input',$id_user) == 'no')
                                
                              @else
                                  {!! CekSediaBerkas('transkrips2','input',$id_user)['valueNya'] !!}
                                  <a href="{{ Route('HapusBerkasDataDiri',['id_berkass' => CekSediaBerkas('transkrips2','input',$id_user)['id']]) }}" onclick="return confirm('Anda yakin menghapus data ini ?')"><button type="button" class="btn btn-outline-danger btn-xs" style="float:right;"><span class="fa fa-trash" > </span> hapus</button></a>

                                  <a href="{{ Route('LihatBerkasDatdir',['id_berkass' => CekSediaBerkas('transkrips2','input',$id_user)['id']]) }}"><button type="button" class="btn btn-outline-info btn-flat btn-xs" style="float:right;"><span class="fa fa-download" > </span> Unduh</button></a>
                              @endif
                            </td>
                            <td>
                             {!! CekSediaBerkas('transkrips2','label',$id_user) !!}
                            </td>
                          </tr>
                          <tr>
                            <td>Ijazah S3</td>
                            <td>
                              @if(CekSediaBerkas('ijazahs3','input',$id_user) == 'no')
                               
                              @else
                                  {!! CekSediaBerkas('ijazahs3','input',$id_user)['valueNya'] !!}
                                  <a href="{{ Route('HapusBerkasDataDiri',['id_berkass' => CekSediaBerkas('ijazahs3','input',$id_user)['id']]) }}" onclick="return confirm('Anda yakin menghapus data ini ?')"><button type="button" class="btn btn-outline-danger btn-xs" style="float:right;"><span class="fa fa-trash" > </span> hapus</button></a>

                                  <a href="{{ Route('LihatBerkasDatdir',['id_berkass' => CekSediaBerkas('ijazahs3','input',$id_user)['id']]) }}"><button type="button" class="btn btn-outline-info btn-flat btn-xs" style="float:right;"><span class="fa fa-download" > </span> Unduh</button></a>
                              @endif
                            </td>
                            <td>
                             {!! CekSediaBerkas('ijazahs3','label',$id_user) !!}
                            </td>
                          </tr>
                          <tr>
                            <td>Transkrip S3</td>
                            <td>
                              @if(CekSediaBerkas('transkrips3','input',$id_user) == 'no')
                               
                              @else
                                  {!! CekSediaBerkas('transkrips3','input',$id_user)['valueNya'] !!}
                                  <a href="{{ Route('HapusBerkasDataDiri',['id_berkass' => CekSediaBerkas('transkrips3','input',$id_user)['id']]) }}" onclick="return confirm('Anda yakin menghapus data ini ?')"><button type="button" class="btn btn-outline-danger btn-xs" style="float:right;"><span class="fa fa-trash" > </span> hapus</button></a>

                                  <a href="{{ Route('LihatBerkasDatdir',['id_berkass' => CekSediaBerkas('transkrips3','input',$id_user)['id']]) }}"><button type="button" class="btn btn-outline-info btn-flat btn-xs" style="float:right;"><span class="fa fa-download" > </span> Unduh</button></a>
                              @endif
                            </td>
                            <td>
                             {!! CekSediaBerkas('transkrips3','label',$id_user) !!}
                            </td>
                          </tr>
                      </tbody>
                     
                    </table>
                  </div>
                  <code style="float:right; font-weight:bold; ">.pdf, .jpg, .png, .zip (Maks 20 mb)</code>

                  </form>
                  <!-- /.tab-content -->
                </div><!-- /.card-body -->
              </div>

            </div>


          </div>

        </div>
      </div>

</div>

@php
/*
function tanggal_indo($tanggal) {
        $bulan = array(1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember');
        $split = explode('-', $tanggal);
        return $bulan[(int) $split[1]] . ' ' . $split[0];
    }
    */
function CekSediaBerkas($jenis_berkas, $label, $id_user){
  $Data = DB::table('b_berkas_data_diri')->select('nama_file','id')->where([['id_user','=',$id_user],['jenis_berkas','=',$jenis_berkas]])->first();

  if ($label == 'label') {
    if (!is_null($Data)) {
      return '<h7><span class="badge badge-success">sudah</span></h7>';
    }else{
      return '<h7><span class="badge badge-warning">belum</span></h7>';
    }
  }else{
    if (!is_null($Data)) {
      return ['tipe'=>'yes','valueNya' => $Data->nama_file, 'id' => $Data->id];
    }else{
      return 'no';
    }
  }
  
}

@endphp

@endsection
@section('script')
<script type="text/javascript">
function isNumberKey(evt)
    {
       var charCode = (evt.which) ? evt.which : event.keyCode
       if (charCode > 31 && (charCode < 48 || charCode > 57))
          return false;

       return true;
    }


function CekNull(data){

    if (data == null) {
      return '<hr style="width:10%; border:2px solid grey;">';
    }else{
      return data;
    }

}
function cekQiudao(data){

    if (data == null) {
      return '-';
    }else{
      return data;
    }

}

$.noConflict();
jQuery( document ).ready(function( $ ) {



//stay di tab jika di refresh
$(document).ready(function(){
  $('a[data-toggle="tab"]').on('show.bs.tab', function(e) {
    localStorage.setItem('activeTab', $(e.target).attr('href'));
  });
  var activeTab = localStorage.getItem('activeTab');
  if(activeTab){
    $('#myTab a[href="' + activeTab + '"]').tab('show');
  }
});


function format ( d ) {

     return '<div class="slider">'+  
                '<table id="#" class="table table-stripped" >'+
                     '<thead style="background-color: #08203c; color: white">'+
                      '<tr>'+
                        '<th >Status Tempat Tinggal</th>'+
                        '<th nowrap>Kecamatan Domisili</th>'+
                        '<th nowrap>Kelurahan Domisili</th>'+
                        '<th nowrap>RT</th>'+
                        '<th nowrap>RW</th>'+
                        '</tr>'+
                      '</thead>'+
                      '<tbody>'+
                            '<tr>'+
                                '<td>'+CekNull(d.status_tempat_tinggal)+'</td>'+
                                '<td>'+CekNull(d.dis_name)+'</td>'+
                                '<td>'+CekNull(d.subdis_name)+'</td>'+
                                '<td>'+CekNull(d.rt_domisili)+'</td>'+
                                '<td>'+CekNull(d.rw_domisili)+'</td>'+
                            '</tr>'+
                      '</tbody>'+
                '</table>'+
              '</div><div class="slider">'+  
                '<table id="#" class="table table-stripped">'+
                     '<thead style="background-color: #08203c; color: white">'+
                      '<tr>'+
                        '<th >Gologan Darah</th>'+
                        '<th >Qiudao</th>'+
                        '<th >Jenis Qiudao</th>'+
                        '<th >Ikrar Vege</th>'+
                        '<th >Tahun Ikrar</th>'+
                        '</tr>'+
                      '</thead>'+
                      '<tbody>'+
                            '<tr>'+
                                '<td><b>'+d.golongan_darah+'</b></td>'+
                                '<td>'+cekQiudao(d.qiudao)+'</td>'+
                                '<td>'+cekQiudao(d.jenis_qiudao)+'</td>'+
                                '<td>'+cekQiudao(d.vege)+'</td>'+
                                '<td>'+cekQiudao(d.ikrartahun_vege)+'</td>'+
                            '</tr>'+
                      '</tbody>'+
                '</table>'+
              '</div>'+
              '<div class="slider">'+  
                '<table id="#" class="table table-stripped">'+
                     '<thead style="background-color: #08203c; color: white">'+
                      '<tr>'+
                        '<th >No.BPJS Kesehatan</th>'+
                        '<th >No.BPJS Ketenagakerjaan</th>'+
                        '</tr>'+
                      '</thead>'+
                      '<tbody>'+
                            '<tr>'+
                                '<td><b>'+CekNull(d.no_bpjs_kesehatan)+'</b></td>'+
                                '<td><b>'+CekNull(d.no_bpjs_ketenagakerjaan)+'</b></td>'+
                            '</tr>'+
                      '</tbody>'+
                '</table>'+
              '</div>'
  
  }


 var dt =  $('#cek_penilaian').DataTable({
        processing: true,
        serverSide: true,
        scrollY : false,
        ajax: '{!! route('GetDataPkAdmin',['id_user' => $id_user]) !!}',
        order: [ [1, 'DESC'] ], 
        searching: false,
        lengthChange: false,
        paging : false,
        info : false,
        
        columns: [
            {
                "class":          "details-control",
                "orderable":      false,
                //"data" : 'id',
                "defaultContent": "",
            }, 
         
            { data: 'nomor_ktp', name: 'nomor_ktp' },
            { data: 'durasi_ktp', name: 'durasi_ktp' },
            { data: 'nomor_npwp', name: 'nomor_npwp' },
            { data: 'tanggal_lahir', name: 'tanggal_lahir' },
            { data: 'status_marital', name: 'status_marital' },
          
        ],



    });


    var detailRows = [];
   
        // Add event listener for opening and closing details
      $('#cek_penilaian tbody').on('click', 'td.details-control', function () {
          var tr = $(this).closest('tr');
          var row = dt.row( tr );
   
          if ( row.child.isShown() ) {
              // This row is already open - close it
              $('div.slider', row.child()).slideUp( function () {
                  row.child.hide();
                  tr.removeClass('shown');
              } );
          }
          else {
              // Open this row
              row.child( format(row.data()), 'no-padding' ).show();
              tr.addClass('shown');
   
              $('div.slider', row.child()).slideDown();
          }
      } );
       // On each draw, loop over the `detailRows` array and show any child rows
      dt.on( 'draw', function () {
          $.each( detailRows, function ( i, id ) {
              $('#'+id+' td.details-control').trigger( 'click' );
          } );
      } );



});

</script>

<script type="text/javascript">
  jQuery( document ).ready(function($){
    $('.select').select2({
      theme: 'bootstrap4'
    });

  });


</script>
<style>
td.details-control {
    background: url('https://raw.githubusercontent.com/DataTables/DataTables/1.10.7/examples/resources/details_open.png') no-repeat center center;
    cursor: pointer;
}
tr.shown td.details-control {
    background: url('https://raw.githubusercontent.com/DataTables/DataTables/1.10.7/examples/resources/details_close.png') no-repeat center center;
}
div.slider {
    display: none;
}
</style>

@include('sweet::alert')
@endsection
