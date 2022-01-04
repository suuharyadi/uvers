@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Tes Vue Crud</div>

                <div class="card-body">
                      <!-- route outlet -->
                      <!-- component matched by the route will render here -->
                      <router-view></router-view>

                </div>
            </div>
        </div>
    </div>
</div>

@endsection
