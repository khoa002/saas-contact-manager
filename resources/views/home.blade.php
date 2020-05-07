@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">Welcome, {{$user->name}}</div>

                    <div class="card-body">
                        @if (session('status'))
                            <div class="alert alert-success" role="alert">
                                {{ session('status') }}
                            </div>
                        @endif

                        <h4>Your contacts</h4>
                        @if ($user->contacts->count() == 0)
                            You currently do not have any contacts.
                        @else
                            <table class="table">
                                <thead>
                                <tr>
                                    <th>First Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($user->contacts as $contact)
                                    <tr>
                                        <td>{{$contact->first_name}}</td>
                                        <td>{{$contact->email}}</td>
                                        <td>{{$contact->phone}}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="row justify-content-center mt-3">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">Add a contact</div>

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

                        <form class="form" role="form" id="add-new-contact" method="post"
                              action="/contacts">
                            {{ csrf_field() }}
                            <input type="hidden" id="user_id" name="user_id" value="{{ $user->id }}"/>
                            <div class="row form-group">
                                <label class="col-md-4 control-label" for="name">First Name</label>
                                <div class="col-md-6">
                                    <input type="text" class="form-control" id="first_name"
                                           name="first_name"
                                           placeholder="Contact first name"
                                           required/>
                                </div>
                            </div>

                            <div class="row form-group">
                                <label class="col-md-4 control-label" for="email">Email</label>
                                <div class="col-md-6">
                                    <input type="email" class="form-control" id="email"
                                           name="email"
                                           placeholder="Email address"
                                           required/>
                                </div>
                            </div>

                            <div class="row form-group">
                                <label class="col-md-4 control-label" for="phone">Phone</label>
                                <div class="col-md-6">
                                    <input type="tel" class="form-control" id="phone"
                                           name="phone"
                                           placeholder="Phone number (numbers only)"
                                           required/>
                                </div>
                            </div>

                            <div class="row form-group">
                                <div class="col-md-6 offset-md-4">
                                    <button type="submit" class="btn btn-outline-primary">Add New Contact</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
