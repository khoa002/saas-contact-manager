@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">Editing contact</div>

                    <div class="card-body">
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul>
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if (session()->has('successMsg'))
                            <div class="alert alert-success">
                                {{ session()->get('successMsg') }}
                            </div>
                        @endif

                        <form class="form" role="form" id="edit-contact" method="post"
                              action="/contacts/{{$contact->id}}">
                            {{ csrf_field() }}
                            @method('PUT')
                            <div class="row form-group">
                                <label class="col-md-4 control-label" for="name">First Name</label>
                                <div class="col-md-6">
                                    <input type="text" class="form-control" id="first_name"
                                           name="first_name"
                                           placeholder="Contact first name"
                                           value="{{$contact->first_name}}"
                                           required/>
                                </div>
                            </div>

                            <div class="row form-group">
                                <label class="col-md-4 control-label" for="email">Email</label>
                                <div class="col-md-6">
                                    <input type="email" class="form-control" id="email"
                                           name="email"
                                           placeholder="Email address"
                                           value="{{$contact->email}}"
                                           required/>
                                </div>
                            </div>

                            <div class="row form-group">
                                <label class="col-md-4 control-label" for="phone">Phone</label>
                                <div class="col-md-6">
                                    <input type="tel" class="form-control" id="phone"
                                           name="phone"
                                           placeholder="Phone number (numbers only)"
                                           value="{{$contact->phone}}"
                                           required/>
                                </div>
                            </div>

                            <div class="row form-group">
                                <div class="col-md-6 offset-md-4">
                                    <button type="submit" class="btn btn-outline-primary">Save Contact
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
