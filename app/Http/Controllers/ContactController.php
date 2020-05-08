<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContactController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'user_id'    => ['required'],
            'first_name' => ['required'],
            'email'      => ['required', 'email'],
            'phone'      => ['numeric']
        ]);

        // Validated
        $contact = Contact::firstOrCreate([
            'user_id'    => $request->get('user_id'),
            'first_name' => $request->get('first_name'),
            'email'      => $request->get('email'),
            'phone'      => $request->get('phone'),
        ]);

        if (env('KLAVIYO_SYNC_ENABLED') == true && !empty(env('KLAVIYO_API_TOKEN'))) {
            // if KLAVIYO syncing is enabled
            $client = new Client([
                'base_uri' => env('KLAVIYO_API_ENDPOINT'),
            ]);

            try {
                $listId = $this->_getDefaultContactsListId($client);

                if (!empty($listId)) {
                    $profiles = [];
                    $profiles[] = [
                        'uuid'         => $contact->uuid,
                        'first_name'   => $contact->first_name,
                        'email'        => $contact->email,
                        'phone_number' => $contact->phone,
                    ];
                    $res = $client->post("/api/v2/list/{$listId}/members", ['json' => [
                        'api_key'  => env('KLAVIYO_API_TOKEN'),
                        'profiles' => $profiles,
                    ]]);
                    $result = collect(json_decode($res->getBody()->getContents()))->first();
                    // contact added, let's get the id from Klaviyo and save it
                    if ($result) {
                        $contact->klaviyo_id = $result->id;
                        $contact->save();
                    }
                }
            } catch (ClientException $e) {
                $code = $e->getCode();
                $msg = json_decode($e->getResponse()->getBody()->getContents());
                $errorMsg = ['Contact added successfully, but could not be synced with Klaviyo.'];
                if (isset($msg->detail)) {
                    $errorMsg[] = $msg->detail;
                }
                return redirect()->back()->withErrors($errorMsg);
            }
        }

        return redirect()->back()->with('successMsg', 'Contact added!');
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $user = Auth::user();
        $contact = Contact::find($id);
        abort_if(!$contact, 404);
        if ($contact) {
            return view('contacts.edit', [
                'user'    => $user,
                'contact' => $contact
            ]);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param Contact $contact
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Contact $contact)
    {
        $validatedData = $request->validate([
            'first_name' => ['required'],
            'email'      => ['required', 'email'],
            'phone'      => ['numeric']
        ]);

        // Validated
        $contact->update([
            'first_name' => $request->get('first_name'),
            'email'      => $request->get('email'),
            'phone'      => $request->get('phone'),
        ]);

        if (env('KLAVIYO_SYNC_ENABLED') == true && !empty(env('KLAVIYO_API_TOKEN'))) {
            // if KLAVIYO syncing is enabled
            $client = new Client([
                'base_uri' => env('KLAVIYO_API_ENDPOINT'),
            ]);

            try {
                $listId = $this->_getDefaultContactsListId($client);

                if (!empty($listId)) {
                    $profiles = [];
                    $profiles[] = [
                        'id'           => $contact->klaviyo_id,
                        'uuid'         => $contact->uuid,
                        'first_name'   => $contact->first_name,
                        'email'        => $contact->email,
                        'phone_number' => $contact->phone,
                    ];
                    $res = $client->post("/api/v2/list/{$listId}/members", ['json' => [
                        'api_key'  => env('KLAVIYO_API_TOKEN'),
                        'profiles' => $profiles,
                    ]]);
                    $result = collect(json_decode($res->getBody()->getContents()))->first();
                    // contact added, let's get the id from Klaviyo and save it
                    if ($result) {
                        $contact->klaviyo_id = $result->id;
                        $contact->save();
                    }
                }
            } catch (ClientException $e) {
                $code = $e->getCode();
                $msg = json_decode($e->getResponse()->getBody()->getContents());
                $errorMsg = ['Contact updated successfully, but could not be synced with Klaviyo.'];
                if (isset($msg->detail)) {
                    $errorMsg[] = $msg->detail;
                }
                return redirect()->back()->withErrors($errorMsg);
            }
        }

        return redirect()->back()->with('successMsg', 'Contact updated!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    /**
     * @param Client $client
     * @return string
     */
    private function _getDefaultContactsListId(Client $client): string
    {
        $user = Auth::user();
        $listName = env('KLAVIYO_DEFAULT_CONTACTS_LIST_NAME', 'Contacts') . " ({$user->uuid})";
        // Find the default contact list in Klaviyo by name
        $res = $client->get('/api/v2/lists', ['query' => ['api_key' => env('KLAVIYO_API_TOKEN')]]);
        $lists = collect(json_decode($res->getBody()->getContents()));
        $contactsList = $lists->first(function ($item) use ($listName) {
            // if it's found, return it
            return $item->list_name == $listName;
        });
        if (!$contactsList) {
            // We don't have a default contact list yet, let's make one
            $res = $client->post('/api/v2/lists', ['form_params' => [
                'api_key'   => env('KLAVIYO_API_TOKEN'),
                'list_name' => $listName,
            ]]);
            $result = json_decode($res->getBody()->getContents());
            if (isset($result->list_id)) {
                return $result->list_id;
            }
        } else {
            // if we have a default list already, use the existing listId
            return $contactsList->list_id;
        }
    }
}
