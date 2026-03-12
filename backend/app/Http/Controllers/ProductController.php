<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Section;
use App\Models\Catalog;
use App\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function getRestaurantsStats()
    {
        $user = Auth::user();
        
        if (!$user) {
            abort(401, 'No autenticado');
        }
        
        // Ensure role is loaded
        if (!$user->relationLoaded('role')) {
            $user->load('role');
        }

        // Get restaurants based on role
        if ($user->hasRole('superadmin')) {
            $restaurants = Restaurant::with(['catalogs.sections.products'])->get();
        } elseif ($user->hasAnyRole(['admin', 'staff'])) {
            $restaurantIds = $this->managedRestaurantIds($user);
            $restaurants = Restaurant::with(['catalogs.sections.products'])
                ->whereIn('id', $restaurantIds)
                ->get();
        } else {
            abort(403, 'No autorizado');
        }

        // Calculate stats for each restaurant
        $stats = $restaurants->map(function ($restaurant) {
            $catalogs = $restaurant->catalogs;
            $totalProducts = 0;
            $productsPerMenu = [];

            foreach ($catalogs as $catalog) {
                $catalogProducts = 0;
                foreach ($catalog->sections as $section) {
                    $catalogProducts += $section->products->count();
                }
                $productsPerMenu[] = [
                    'menu_name' => $catalog->name,
                    'products_count' => $catalogProducts
                ];
                $totalProducts += $catalogProducts;
            }

            return [
                'id' => $restaurant->id,
                'name' => $restaurant->name,
                'address' => $restaurant->address ?? '',
                'phone' => $restaurant->phone ?? '',
                'menus_count' => $catalogs->count(),
                'total_products' => $totalProducts,
                'products_per_menu' => $productsPerMenu
            ];
        });

        return response()->json($stats);
    }

    protected function authorizeRestaurant($restaurantId)
    {
        $user = Auth::user();
        
        if (!$user) {
            abort(401, 'No autenticado');
        }
        
        // Ensure role is loaded
        if (!$user->relationLoaded('role')) {
            $user->load('role');
        }
        
        $restaurant = Restaurant::find($restaurantId);

        if (!$restaurant) {
            abort(404, 'Restaurante no encontrado');
        }

        if ($user->hasRole('superadmin')) {
            return $restaurant;
        }

        if ($user->hasAnyRole(['admin', 'staff'])) {
            if (!$this->canAccessRestaurant($user, (int) $restaurantId)) {
                abort(403, 'No tienes permiso para acceder a este restaurante');
            }
            return $restaurant;
        }

        abort(403, 'No autorizado');
    }

    public function getCatalogsByRestaurant($restaurantId)
    {
        $user = auth('sanctum')->user();

        $restaurant = Restaurant::find($restaurantId);
        
        if (!$restaurant) {
            return response()->json(['error' => 'Restaurante no encontrado'], 404);
        }

        if ($user) {
            if (!$user->relationLoaded('role')) {
                $user->load('role');
            }

            if ($user->hasAnyRole(['admin', 'staff']) && !$this->canAccessRestaurant($user, (int) $restaurantId)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            if ($user->hasRole('cliente') && !$restaurant->active) {
                return response()->json(['message' => 'Restaurante no disponible'], 404);
            }
        } elseif (!$restaurant->active) {
            return response()->json(['message' => 'Restaurante no disponible'], 404);
        }
        
        $catalogs = $restaurant->catalogs()
            ->with(['sections' => function ($query) {
                $query->where('active', true)
                    ->orderBy('order')
                    ->with(['products' => function ($q) {
                        $q->where('active', true)->orderBy('name');
                    }]);
            }])
            ->where('active', true)
            ->orderBy('order')
            ->get();

        return response()->json($catalogs);
    }

    public function storeCatalog(Request $request, $restaurantId)
    {
        $restaurant = $this->authorizeRestaurant($restaurantId);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'active' => 'boolean',
            'order' => 'integer|min:0',
        ]);

        $catalog = $restaurant->catalogs()->create($validated);

        return response()->json($catalog, 201);
    }

    public function updateCatalog(Request $request, $restaurantId, $catalogId)
    {
        $restaurant = $this->authorizeRestaurant($restaurantId);
        
        $catalog = $restaurant->catalogs()->find($catalogId);
        if (!$catalog) {
            return response()->json(['error' => 'Catálogo no encontrado'], 404);
        }

        $validated = $request->validate([
            'name' => 'string|max:255',
            'description' => 'nullable|string',
            'active' => 'boolean',
            'order' => 'integer|min:0',
        ]);

        $catalog->update($validated);

        return response()->json($catalog);
    }

    public function deleteCatalog($restaurantId, $catalogId)
    {
        $restaurant = $this->authorizeRestaurant($restaurantId);
        
        $catalog = $restaurant->catalogs()->find($catalogId);
        if (!$catalog) {
            return response()->json(['error' => 'Catálogo no encontrado'], 404);
        }

        $catalog->delete();

        return response()->json(['message' => 'Catálogo eliminado']);
    }

    public function storeSection(Request $request, $restaurantId, $catalogId)
    {
        $restaurant = $this->authorizeRestaurant($restaurantId);
        
        $catalog = $restaurant->catalogs()->find($catalogId);
        if (!$catalog) {
            return response()->json(['error' => 'Catálogo no encontrado'], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'active' => 'boolean',
            'order' => 'integer|min:0',
        ]);

        $section = $catalog->sections()->create($validated);

        return response()->json($section, 201);
    }

    public function updateSection(Request $request, $restaurantId, $catalogId, $sectionId)
    {
        $restaurant = $this->authorizeRestaurant($restaurantId);
        
        $catalog = $restaurant->catalogs()->find($catalogId);
        if (!$catalog) {
            return response()->json(['error' => 'Catálogo no encontrado'], 404);
        }

        $section = $catalog->sections()->find($sectionId);
        if (!$section) {
            return response()->json(['error' => 'Sección no encontrada'], 404);
        }

        $validated = $request->validate([
            'name' => 'string|max:255',
            'description' => 'nullable|string',
            'active' => 'boolean',
            'order' => 'integer|min:0',
        ]);

        $section->update($validated);

        return response()->json($section);
    }

    public function deleteSection($restaurantId, $catalogId, $sectionId)
    {
        $restaurant = $this->authorizeRestaurant($restaurantId);
        
        $catalog = $restaurant->catalogs()->find($catalogId);
        if (!$catalog) {
            return response()->json(['error' => 'Catálogo no encontrado'], 404);
        }

        $section = $catalog->sections()->find($sectionId);
        if (!$section) {
            return response()->json(['error' => 'Sección no encontrada'], 404);
        }

        $section->delete();

        return response()->json(['message' => 'Sección eliminada']);
    }

    public function storeProduct(Request $request, $restaurantId, $catalogId, $sectionId)
    {
        $restaurant = $this->authorizeRestaurant($restaurantId);
        
        $catalog = $restaurant->catalogs()->find($catalogId);
        if (!$catalog) {
            return response()->json(['error' => 'Catálogo no encontrado'], 404);
        }

        $section = $catalog->sections()->find($sectionId);
        if (!$section) {
            return response()->json(['error' => 'Sección no encontrada'], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'active' => 'boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $productData = array_merge($validated, [
            'restaurant_id' => $restaurant->id,
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $image->storeAs('public/products', $imageName);
            $productData['image'] = 'products/' . $imageName;
        }

        $product = $section->products()->create($productData);

        return response()->json($product, 201);
    }

    public function updateProduct(Request $request, $restaurantId, $catalogId, $sectionId, $productId)
    {
        $restaurant = $this->authorizeRestaurant($restaurantId);
        
        $catalog = $restaurant->catalogs()->find($catalogId);
        if (!$catalog) {
            return response()->json(['error' => 'Catálogo no encontrado'], 404);
        }

        $section = $catalog->sections()->find($sectionId);
        if (!$section) {
            return response()->json(['error' => 'Sección no encontrada'], 404);
        }

        $product = $section->products()->find($productId);
        if (!$product) {
            return response()->json(['error' => 'Producto no encontrado'], 404);
        }

        $validated = $request->validate([
            'name' => 'string|max:255',
            'description' => 'nullable|string',
            'price' => 'numeric|min:0',
            'active' => 'boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'remove_image' => 'boolean',
        ]);

        // Handle image removal
        if ($request->input('remove_image') && $product->image) {
            Storage::delete('public/' . $product->image);
            $validated['image'] = null;
        }

        // Handle new image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($product->image) {
                Storage::delete('public/' . $product->image);
            }
            $image = $request->file('image');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $image->storeAs('public/products', $imageName);
            $validated['image'] = 'products/' . $imageName;
        }

        $product->update($validated);

        return response()->json($product);
    }

    public function deleteProduct($restaurantId, $catalogId, $sectionId, $productId)
    {
        $restaurant = $this->authorizeRestaurant($restaurantId);
        
        $catalog = $restaurant->catalogs()->find($catalogId);
        if (!$catalog) {
            return response()->json(['error' => 'Catálogo no encontrado'], 404);
        }

        $section = $catalog->sections()->find($sectionId);
        if (!$section) {
            return response()->json(['error' => 'Sección no encontrada'], 404);
        }

        $product = $section->products()->find($productId);
        if (!$product) {
            return response()->json(['error' => 'Producto no encontrado'], 404);
        }

        $product->delete();

        return response()->json(['message' => 'Producto eliminado']);
    }
}
