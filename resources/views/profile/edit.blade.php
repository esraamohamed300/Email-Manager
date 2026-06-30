<x-app-layout>
    <x-slot name="header">
        <h2>Profile</h2>
    </x-slot>

    <div class="max-w-7xl mx-auto py-6 px-4">

        <!-- Update Profile Form -->
        <form method="POST" action="{{ route('profile.update') }}">
            @csrf
            @method('PATCH')

            <div>
                <label for="name">Name</label>
                <input id="name" type="text" name="name" value="{{ old('name', $user->name) }}" required />
                @error('name') <span>{{ $message }}</span> @enderror
            </div>

            <div>
                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" required />
                @error('email') <span>{{ $message }}</span> @enderror
            </div>

            <button type="submit">Save</button>
        </form>

        <!-- Delete Account Form -->
        <form method="POST" action="{{ route('profile.destroy') }}">
            @csrf
            @method('DELETE')

            <div>
                <label for="password">Password</label>
                <input id="password" type="password" name="password" required />
                @error('password', 'userDeletion') <span>{{ $message }}</span> @enderror
            </div>

            <button type="submit">Delete Account</button>
        </form>

    </div>
</x-app-layout>