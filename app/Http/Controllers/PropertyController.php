<?php

namespace App\Http\Controllers;

use App\Models\Property;
use Illuminate\Http\Request;

class PropertyController extends Controller
{
    public function index()
    {
        $properties = Property::all();
        return view('properties.index', compact('properties'));
    }

    public function create()
    {
        return view('properties.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'country' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'longitude' => 'required|numeric|between:-180,180',
            'latitude' => 'required|numeric|between:-90,90',
            'rooms' => 'required|integer|min:1',
            'bathrooms' => 'required|integer|min:1',
            'total_area' => 'required|numeric|min:0',
            'detailed_info' => 'required|string',
            'price' => 'required|numeric|min:0',
            'photos' => 'required|array',
            'photos.*' => 'url',
            'main_image' => 'required|url',
        ]);

        Property::create([
            'name' => $request->name,
            'description' => $request->description,
            'country' => $request->country,
            'city' => $request->city,
            'longitude' => $request->longitude,
            'latitude' => $request->latitude,
            'rooms' => $request->rooms,
            'bathrooms' => $request->bathrooms,
            'total_area' => $request->total_area,
            'publisher_id' => auth()->id(),
            'approved_by' => null,
            'detailed_info' => $request->detailed_info,
            'price' => $request->price,
            'photos' => $request->photos,
            'main_image' => $request->main_image,
        ]);

        return redirect()->route('properties.index')->with('success', 'Property created successfully.');
    }

    public function show(Property $property)
    {
        return view('properties.show', compact('property'));
    }

    public function edit(Property $property)
    {
        return view('properties.edit', compact('property'));
    }

    public function update(Request $request, Property $property)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'country' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'longitude' => 'required|numeric|between:-180,180',
            'latitude' => 'required|numeric|between:-90,90',
            'rooms' => 'required|integer|min:1',
            'bathrooms' => 'required|integer|min:1',
            'total_area' => 'required|numeric|min:0',
            'detailed_info' => 'required|string',
            'price' => 'required|numeric|min:0',
            'photos' => 'required|array',
            'photos.*' => 'url',
            'main_image' => 'required|url',
        ]);

        $property->update($request->all());

        return redirect()->route('properties.index')->with('success', 'Property updated successfully.');
    }

    public function destroy(Property $property)
    {
        $property->delete();
        return redirect()->route('properties.index')->with('success', 'Property deleted successfully.');
    }
}