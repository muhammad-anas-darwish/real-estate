<!DOCTYPE html>
<html>
<head>
    <title>{{ $property->name }}</title>
</head>
<body>
    <h1>{{ $property->name }}</h1>
    <p>Description: {{ $property->description }}</p>
    <p>Location: {{ $property->country }}, {{ $property->city }}</p>
    <p>Coordinates: {{ $property->latitude }}, {{ $property->longitude }}</p>
    <p>Rooms: {{ $property->rooms }}, Bathrooms: {{ $property->bathrooms }}</p>
    <p>Area: {{ $property->total_area }} sqm</p>
    <p>Publisher: {{ $property->publisher->name }}</p>
    @if($property->approver)
        <p>Approved by: {{ $property->approver->name }}</p>
    @endif
    <p>Detailed Info: {{ $property->detailed_info }}</p>
    <p>Price: ${{ $property->price }}</p>
    <p>Main Image: <img src="{{ $property->main_image }}" alt="Main Image" width="200"></p>
    <p>Photos:</p>
    @foreach($property->photos as $photo)
        <img src="{{ $photo }}" alt="Photo" width="200">
    @endforeach
    <a href="{{ route('properties.index') }}">Back to List</a>
</body>
</html>