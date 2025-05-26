<?php

namespace App\Http\Controllers;

use App\Models\ServicePackage;
use Illuminate\Http\Request;

class PublicServicePackageController extends Controller
{
    /**
     * Display a listing of published service packages with filtering.
     */
    public function index(Request $request)
    {
        // Validate filter inputs
        $validated = $request->validate([
            'q' => 'nullable|string|max:100', // Search term
            'price_min' => 'nullable|numeric|min:0',
            'price_max' => 'nullable|numeric|min:0',
            'delivery_time_max' => 'nullable|integer|min:1',
            'sort' => 'nullable|string|in:latest,price_asc,price_desc,delivery_asc',
            // Add category filter later if needed: 'category' => 'nullable|string|max:50'
        ]);

        $query = ServicePackage::published()->with('user');

        // Apply search filter
        if (!empty($validated['q'])) {
            $searchTerm = '%' . $validated['q'] . '%';
            $query->where(function($q) use ($searchTerm) {
                $q->where('title', 'like', $searchTerm)
                  ->orWhere('description', 'like', $searchTerm)
                  ->orWhereHas('user', function($userQuery) use ($searchTerm) {
                      $userQuery->where('name', 'like', $searchTerm);
                  });
                  // Add category search later if needed
            });
        }

        // Apply price filters
        if (!empty($validated['price_min'])) {
            $query->where('price', '>=', $validated['price_min']);
        }
        if (!empty($validated['price_max'])) {
            $query->where('price', '<=', $validated['price_max']);
        }

        // Apply delivery time filter
        if (!empty($validated['delivery_time_max'])) {
            $query->where('estimated_delivery_days', '<=', $validated['delivery_time_max']);
        }

        // Apply sorting
        $sort = $validated['sort'] ?? 'latest';
        match ($sort) {
            'price_asc' => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'delivery_asc' => $query->orderBy('estimated_delivery_days', 'asc'),
            default => $query->latest(), // Default to latest
        };

        $packages = $query->paginate(12)->withQueryString(); // Append query string to pagination links

        return view('public.services.index', [
            'packages' => $packages,
            'filters' => $validated // Pass filters back to the view
        ]);
    }

    /**
     * Display the specified service package.
     *
     * @param ServicePackage $package The service package resolved by route model binding (using slug).
     * @return \Illuminate\View\View
     */
    public function show(ServicePackage $package)
    {
        // Ensure the package is published before showing
        if (!$package->is_published) {
            abort(404);
        }
        
        // Eager load the producer/user relationship
        $package->load('user');
        
        // Optionally load related data like order count or ratings if needed later
        
        return view('public.services.show', compact('package'));
    }
}
