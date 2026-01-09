<!DOCTYPE html>
<html>
<head>
    <title>Properties</title>
</head>
<body>
    <h1>Properties</h1>
    <a href="{{ route('properties.create') }}">Create Property</a>
    @foreach($properties as $property)
        <div>
            <h2>{{ $property->name }}</h2>
            <p>{{ $property->description }}</p>
            <p>Location: {{ $property->country }}, {{ $property->city }}</p>
            <p>Price: ${{ $property->price }}</p>
            <a href="{{ route('properties.show', $property) }}">View</a>
            <a href="{{ route('properties.edit', $property) }}">Edit</a>
            <form action="{{ route('properties.destroy', $property) }}" method="POST" style="display:inline;">
                @csrf
                @method('DELETE')
                <button type="submit">Delete</button>
            </form>
        </div>
    @endforeach
</body>
</html>