<?php

namespace App\Http\Controllers;

use App\Http\Requests\CsvImportRequest;
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

        $result = $this->syncWithKlaviyo($contact);
        if (isset($result['success']) && $result['success'] === false) {
            return redirect()->back()->withErrors($result['message'] ?? []);
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

        $result = $this->syncWithKlaviyo($contact);
        if (isset($result['success']) && $result['success'] === false) {
            return redirect()->back()->withErrors($result['message'] ?? []);
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

    public function parseImport(CsvImportRequest $request)
    {
        // For this project, we only care about the following columns
        // The import will ignore the rest, if any
        $requiredHeaders = ['email', 'first_name', 'phone'];
        $path = $request->file('csvfile')->getRealPath();
        $data = array_map('str_getcsv', file($path));
        if (empty($data) || !isset($data[1])) {
            return redirect()->back()->with('importErrors', [
                'Nothing to import'
            ]);
        }
        $importHeaders = $data[0] ?? null;
        if (!isset($importHeaders) || count($importHeaders) < 3 || empty(array_intersect($importHeaders, $requiredHeaders))) {
            return redirect()->back()->with('importErrors', [
                'Header row is required.',
                'Must contain the following columnns: ' . implode(', ', $requiredHeaders),
            ]);
        }

        // Get the header column index for the required fields
        $columnKeys = [];
        foreach ($requiredHeaders as $header) {
            $key = array_search($header, $importHeaders);
            if ($key === false) {
                return redirect()->back()->with('importErrors', [
                    "Unable to find {$header} column."
                ]);
            }
            $columnKeys[$header] = $key;
        }

        $rowErrors = [];
        foreach ($data as $index => $row) {
            if ($index == 0) {
                continue;
            }

            $email = trim($row[$columnKeys['email']]);
            if (empty($email)) {
                $rowErrors[] = 'Email is blank for row#' . ($index + 1) . '. Skipped.';
                continue;
            }
            $first_name = trim($row[$columnKeys['first_name']]);
            if (empty($first_name)) {
                $rowErrors[] = 'First name is blank for row#' . ($index + 1) . '. Skipped.';
                continue;
            }
            $phone = trim($row[$columnKeys['phone']]);
            if (empty($phone)) {
                $rowErrors[] = 'Phone is blank for row#' . ($index + 1) . '. Skipped.';
                continue;
            }

            $contact = Contact::firstOrCreate([
                'user_id'    => $request->get('user_id'),
                'first_name' => $first_name,
                'email'      => $email,
                'phone'      => $phone,
            ]);

            $syncResult = $this->syncWithKlaviyo($contact);
            if (isset($syncResult['success']) && $syncResult['success'] === false) {
                $rowErrors = array_merge($rowErrors, $syncResult['message'] ?? []);
            }
        }

        if (!empty($rowErrors)) {
            return redirect()->back()->with('importErrors', $rowErrors);
        }

        return redirect()->back()->with('importSuccessMsg', 'Import successful!');
    }

    /**
     * Method to sync with Klaviyo
     * @param Contact $contact
     * @return array
     */
    public function syncWithKlaviyo(Contact $contact): array
    {
        if (env('KLAVIYO_SYNC_ENABLED') == true && !empty(env('KLAVIYO_API_TOKEN'))) {
            // if KLAVIYO syncing is enabled
            $client = new Client([
                'base_uri' => env('KLAVIYO_API_ENDPOINT'),
            ]);

            try {
                $listId = $this->_getDefaultContactsListId($client);
                if (empty($listId)) {
                    return ['success' => false, 'message' => ['Unable to retrieve default contacts list ID from Klaviyo.']];
                }
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
                return ['success' => true];
            } catch (ClientException $e) {
                $code = $e->getCode();
                $msg = json_decode($e->getResponse()->getBody()->getContents());
                $errorMsg = ['Contact updated successfully, but could not be synced with Klaviyo.'];
                if (isset($msg->detail)) {
                    $errorMsg[] = $msg->detail;
                }
                return ['success' => false, 'message' => $errorMsg];
            }
        }
    }

    /**
     * Method to get the default contacts list ID from Klaviyo
     * If one doesn't exist yet, make one
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
