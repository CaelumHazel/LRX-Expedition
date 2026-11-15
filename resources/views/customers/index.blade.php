<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customers</title>
</head>

<body>

    <h1>Customer List</h1>

    @if (session('success'))
        <p>{{ session('success') }}</p>
    @endif

    <a href="{{ route('customers.create') }}">
        + Add Customer
    </a>

    <hr>

    @forelse ($customers as $customer)

        <div>
            <h3>{{ $customer->name }}</h3>

            <p>Phone: {{ $customer->phone }}</p>

            <p>Address: {{ $customer->address }}</p>

            <a href="{{ route('customers.show', $customer) }}">
                Detail
            </a>

            <a href="{{ route('customers.edit', $customer) }}">
                Edit
            </a>

            <form action="{{ route('customers.destroy', $customer) }}"
                  method="POST">

                @csrf
                @method('DELETE')

                <button type="submit">
                    Delete
                </button>

            </form>
        </div>

        <hr>

    @empty

        <p>No customers found.</p>

    @endforelse

</body>
</html>