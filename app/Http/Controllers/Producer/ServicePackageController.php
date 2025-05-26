<?php

namespace App\Http\Controllers\Producer;

use App\Http\Controllers\Controller;
use App\Models\ServicePackage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Producer\StoreServicePackageRequest;
use App\Http\Requests\Producer\UpdateServicePackageRequest;

class ServicePackageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // TODO: Fetch and display producer's service packages
        $packages = Auth::user()->servicePackages()->latest()->paginate(15);
        return view('producer.services.packages.index', compact('packages'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // TODO: Show package creation form
        return view('producer.services.packages.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // TODO: Validate and store the new package
        // Temporary validation
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'deliverables' => 'nullable|string',
            'revisions_included' => 'required|integer|min:0',
            'estimated_delivery_days' => 'nullable|integer|min:1',
            'requirements_prompt' => 'nullable|string',
            'is_published' => 'nullable|boolean',
        ]);

        $validated['user_id'] = Auth::id();
        $validated['is_published'] = $request->boolean('is_published'); // Ensure boolean

        $package = ServicePackage::create($validated);

        return redirect()->route('producer.services.packages.index')->with('success', 'Service package created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(ServicePackage $package)
    {
        // TODO: Implement authorization (ensure producer owns package)
        $this->authorize('view', $package);
        return view('producer.services.packages.show', compact('package'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ServicePackage $package)
    {
        // TODO: Implement authorization (ensure producer owns package)
        $this->authorize('update', $package);
        return view('producer.services.packages.edit', compact('package'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ServicePackage $package)
    {
        // TODO: Implement authorization (ensure producer owns package)
        $this->authorize('update', $package);
        
        // TODO: Validate and update the package
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'deliverables' => 'nullable|string',
            'revisions_included' => 'required|integer|min:0',
            'estimated_delivery_days' => 'nullable|integer|min:1',
            'requirements_prompt' => 'nullable|string',
            'is_published' => 'nullable|boolean',
        ]);
        
        $validated['is_published'] = $request->boolean('is_published');

        $package->update($validated);

        return redirect()->route('producer.services.packages.index')->with('success', 'Service package updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ServicePackage $package)
    {
        // TODO: Implement authorization (ensure producer owns package)
        $this->authorize('delete', $package);
        
        $package->delete(); // Uses SoftDeletes

        return redirect()->route('producer.services.packages.index')->with('success', 'Service package deleted successfully.');
    }
} 