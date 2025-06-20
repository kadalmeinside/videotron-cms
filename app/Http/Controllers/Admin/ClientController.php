<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreClientRequest;
use App\Http\Requests\Admin\UpdateClientRequest;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class ClientController extends Controller
{
    /**
     * Helper function to get data for the edit form.
     */
    private function getClientDataForEdit(Client $client)
    {
        $client->load('user'); // Eager load the user relationship
        return [
            'id' => $client->id,
            'company_name' => $client->company_name,
            'contact_person' => $client->contact_person,
            'contact_email' => $client->contact_email,
            'user' => [
                'id' => $client->user?->id,
                'name' => $client->user?->name,
                'email' => $client->user?->email,
            ]
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Client::class);

        $query = Client::with('user')->orderBy('company_name');

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'LIKE', "%{$search}%")
                  ->orWhere('contact_person', 'LIKE', "%{$search}%")
                  ->orWhere('contact_email', 'LIKE', "%{$search}%");
            });
        }

        $clients = $query->paginate(10)->withQueryString();

        return Inertia::render('Admin/Clients/Index', [
            'clientList' => $clients->through(fn($client) => [
                'id' => $client->id,
                'company_name' => $client->company_name,
                'contact_person' => $client->contact_person,
                'email' => $client->contact_email,
                'created_at_formatted' => $client->created_at->isoFormat('D MMM YYYY'),
                'full_data_for_edit' => $this->getClientDataForEdit($client),
            ]),
            'filters' => $request->only(['search']),
            'can' => [
                'manage_clients' => $request->user()->can('manage_clients'),
            ]
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreClientRequest $request)
    {
        $validated = $request->validated();
        
        DB::transaction(function () use ($validated) {
            $clientRole = Role::firstOrCreate(['name' => 'client']);

            $user = User::create([
                'name' => $validated['contact_person'],
                'email' => $validated['contact_email'],
                'password' => Hash::make($validated['password']),
                'email_verified_at' => now(),
            ]);
            $user->assignRole($clientRole);

            $user->client()->create([
                'company_name' => $validated['company_name'],
                'contact_person' => $validated['contact_person'],
                'contact_email' => $validated['contact_email'],
            ]);
        });

        return Redirect::route('admin.clients.index')->with([
            'message' => 'Klien baru berhasil dibuat.',
            'type' => 'success'
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateClientRequest $request, Client $client)
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $client) {
            // Update User data
            if ($client->user) {
                $client->user->update([
                    'name' => $validated['contact_person'],
                    'email' => $validated['contact_email'],
                ]);
                if (!empty($validated['password'])) {
                    $client->user->update(['password' => Hash::make($validated['password'])]);
                }
            }

            // Update Client data
            $client->update([
                'company_name' => $validated['company_name'],
                'contact_person' => $validated['contact_person'],
                'contact_email' => $validated['contact_email'],
            ]);
        });

        return Redirect::route('admin.clients.index')->with([
            'message' => 'Data klien berhasil diperbarui.',
            'type' => 'success'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Client $client)
    {
        $this->authorize('delete', $client);

        $client->delete();

        return Redirect::route('admin.clients.index')->with([
            'message' => 'Klien berhasil dihapus.',
            'type' => 'success'
        ]);
    }
}
