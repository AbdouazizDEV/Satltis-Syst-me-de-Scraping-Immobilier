<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sources de Location - {{ config('app.name', 'Laravel') }}</title>
    
    <!-- Tailwind CSS via CDN pour une vue simple -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen py-8 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <!-- En-tête -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Sources de Location Immobilière</h1>
                <p class="text-gray-600">Liste des annonces scrapées depuis différentes sources</p>
            </div>

            <!-- Filtres -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <form method="GET" action="{{ route('rentals.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <!-- Filtre par ville -->
                    <div>
                        <label for="city" class="block text-sm font-medium text-gray-700 mb-2">Ville</label>
                        <select name="city" id="city" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Toutes les villes</option>
                            @foreach($cities as $city)
                                <option value="{{ $city }}" {{ request('city') === $city ? 'selected' : '' }}>
                                    {{ $city }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Filtre par type -->
                    <div>
                        <label for="source_type" class="block text-sm font-medium text-gray-700 mb-2">Type</label>
                        <select name="source_type" id="source_type" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Tous les types</option>
                            <option value="AGENCY" {{ request('source_type') === 'AGENCY' ? 'selected' : '' }}>Agence</option>
                            <option value="PRIVATE" {{ request('source_type') === 'PRIVATE' ? 'selected' : '' }}>Particulier</option>
                        </select>
                    </div>

                    <!-- Filtre par qualification -->
                    <div>
                        <label for="is_qualified" class="block text-sm font-medium text-gray-700 mb-2">Qualification</label>
                        <select name="is_qualified" id="is_qualified" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Toutes</option>
                            <option value="1" {{ request('is_qualified') === '1' ? 'selected' : '' }}>Qualifiées</option>
                            <option value="0" {{ request('is_qualified') === '0' ? 'selected' : '' }}>Non qualifiées</option>
                        </select>
                    </div>

                    <!-- Boutons -->
                    <div class="flex items-end gap-2">
                        <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition">
                            Filtrer
                        </button>
                        <a href="{{ route('rentals.index') }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition">
                            Réinitialiser
                        </a>
                    </div>
                </form>
            </div>

            <!-- Statistiques -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow-sm p-4">
                    <div class="text-sm text-gray-600">Total</div>
                    <div class="text-2xl font-bold text-gray-900">{{ $rentalSources->total() }}</div>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-4">
                    <div class="text-sm text-gray-600">Qualifiées</div>
                    <div class="text-2xl font-bold text-green-600">{{ $rentalSources->where('is_qualified', true)->count() }}</div>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-4">
                    <div class="text-sm text-gray-600">Non qualifiées</div>
                    <div class="text-2xl font-bold text-orange-600">{{ $rentalSources->where('is_qualified', false)->count() }}</div>
                </div>
            </div>

            <!-- Tableau des résultats -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                @if($rentalSources->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Titre</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Localisation</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($rentalSources as $rental)
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ $rental->name_or_title ?? 'Sans titre' }}
                                            </div>
                                            @if($rental->property_type)
                                                <div class="text-sm text-gray-500">{{ $rental->property_type }}</div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($rental->source_type === 'AGENCY')
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                    Agence
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    Particulier
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            @if($rental->phone_number)
                                                <div class="flex items-center gap-1">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                                    </svg>
                                                    {{ $rental->phone_number }}
                                                </div>
                                            @endif
                                            @if($rental->email)
                                                <div class="flex items-center gap-1 mt-1">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                                    </svg>
                                                    <a href="mailto:{{ $rental->email }}" class="text-blue-600 hover:underline">
                                                        {{ $rental->email }}
                                                    </a>
                                                </div>
                                            @endif
                                            @if(!$rental->phone_number && !$rental->email)
                                                <span class="text-gray-400">Aucun contact</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            @if($rental->city)
                                                <div class="font-medium">{{ $rental->city }}</div>
                                            @endif
                                            @if($rental->district)
                                                <div class="text-gray-400">{{ $rental->district }}</div>
                                            @endif
                                            @if(!$rental->city && !$rental->district)
                                                <span class="text-gray-400">Non spécifié</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($rental->is_qualified)
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    ✓ Qualifié
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                                    Non qualifié
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $rental->created_at->format('d/m/Y H:i') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
                        {{ $rentalSources->links() }}
                    </div>
                @else
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Aucune source trouvée</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            @if(request()->hasAny(['city', 'source_type', 'is_qualified']))
                                Aucune source ne correspond à vos critères de recherche.
                            @else
                                Commencez par scraper des annonces avec la commande: <code class="bg-gray-100 px-2 py-1 rounded">php artisan app:scrape-rentals</code>
                            @endif
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</body>
</html>

