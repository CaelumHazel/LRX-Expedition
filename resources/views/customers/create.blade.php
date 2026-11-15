<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Create Customer</title>
</head>

<body>

    <h1>Create Customer</h1>

    @if ($errors->any())

        <div>
            <ul>

                @foreach ($errors->all() as $error)

                    <li>
                        {{ $error }}
                    </li>

                @endforeach

            </ul>
        </div>

    @endif

    <form
        action="{{ route('customers.store') }}"
        method="POST"
    >

        @csrf

        <div>
            <label>
                Name
            </label>

            <input
                type="text"
                name="name"
                value="{{ old('name') }}"
            >
        </div>

        <div>
            <label>
                Phone
            </label>

            <input
                type="text"
                name="phone"
                value="{{ old('phone') }}"
            >
        </div>

        <div>
            <label>
                Address
            </label>

            <textarea name="address">{{ old('address') }}</textarea>
        </div>

        <button type="submit">
            Save
        </button>

    </form>

</body>

</html>