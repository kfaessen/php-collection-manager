<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nieuwe Rol - Collection Manager</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        <!-- Navigation -->
        <nav class="bg-white shadow-lg">
            <div class="max-w-7xl mx-auto px-4">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <div class="flex-shrink-0 flex items-center">
                            <h1 class="text-xl font-bold text-gray-800">Collection Manager Admin</h1>
                        </div>
                        <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                            <a href="{{ route('admin.dashboard') }}" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                                Dashboard
                            </a>
                            <a href="{{ route('admin.users.index') }}" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                                Gebruikers
                            </a>
                            <a href="{{ route('admin.roles.index') }}" class="border-indigo-500 text-gray-900 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                                Rollen
                            </a>
                            <a href="{{ route('admin.permissions.index') }}" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                                Permissies
                            </a>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <span class="text-gray-700">{{ auth()->user()->name }}</span>
                        <form method="POST" action="{{ route('logout') }}" class="ml-4">
                            @csrf
                            <button type="submit" class="text-gray-500 hover:text-gray-700">Uitloggen</button>
                        </form>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main content -->
        <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <div class="px-4 py-6 sm:px-0">
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-2xl font-semibold text-gray-900">Nieuwe Rol</h1>
                    <a href="{{ route('admin.roles.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                        Terug naar Overzicht
                    </a>
                </div>

                <div class="bg-white shadow rounded-lg">
                    <form method="POST" action="{{ route('admin.roles.store') }}" class="p-6">
                        @csrf

                        @if($errors->any())
                            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
                                <ul class="list-disc list-inside">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Name -->
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700">Rol Naam *</label>
                                <input type="text" name="name" id="name" value="{{ old('name') }}" required
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                       placeholder="bijv. editor">
                            </div>

                            <!-- Display Name -->
                            <div>
                                <label for="display_name" class="block text-sm font-medium text-gray-700">Weergavenaam *</label>
                                <input type="text" name="display_name" id="display_name" value="{{ old('display_name') }}" required
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                       placeholder="bijv. Editor">
                            </div>

                            <!-- Description -->
                            <div class="md:col-span-2">
                                <label for="description" class="block text-sm font-medium text-gray-700">Beschrijving</label>
                                <textarea name="description" id="description" rows="3"
                                          class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                          placeholder="Optionele beschrijving van deze rol">{{ old('description') }}</textarea>
                            </div>
                        </div>

                        <!-- Permissions -->
                        <div class="mt-8">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Permissies</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                @foreach($permissions as $module => $modulePermissions)
                                    <div class="border rounded-lg p-4">
                                        <h4 class="font-medium text-gray-900 mb-3">{{ ucfirst($module) }}</h4>
                                        <div class="space-y-2">
                                            @foreach($modulePermissions as $permission)
                                                <div class="flex items-center">
                                                    <input type="checkbox" name="permissions[]" id="permission_{{ $permission->id }}" 
                                                           value="{{ $permission->id }}" 
                                                           {{ in_array($permission->id, old('permissions', [])) ? 'checked' : '' }}
                                                           class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                                    <label for="permission_{{ $permission->id }}" class="ml-2 block text-sm text-gray-900">
                                                        {{ $permission->display_name }}
                                                        @if($permission->description)
                                                            <span class="text-gray-500 block text-xs">{{ $permission->description }}</span>
                                                        @endif
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end">
                            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">
                                Rol Aanmaken
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html> 