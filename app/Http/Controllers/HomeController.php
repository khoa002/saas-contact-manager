<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $user = Auth::user();
        return view('home', ['user' => $user]);
    }

    public function trackClick()
    {
        if (env('KLAVIYO_SYNC_ENABLED') == true && !empty(env('KLAVIYO_API_TOKEN'))) {
            $user = Auth::user();
            $service = new \Klaviyo(env('KLAVIYO_API_TOKEN'));
            $service->track(
                'Button clicked',
                ['$email' => $user->email],
                [],
                now()
            );
            return 'You clicked a button!';
        }
        return "You clicked a button! But tracking isn't set up.";
    }
}
