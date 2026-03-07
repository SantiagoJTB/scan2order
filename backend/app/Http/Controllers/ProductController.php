<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Section;
use App\Models\Catalog;
use App\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    protected function authorizeRestaurant($restaurantId)
    {
        $user = Auth::user();
        
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

        if ($user->hasRole('admin')) {
            if (!$user->restaurants()->where('restaurant_id', $restaurantId)->exists()) {
                abort(403, 'No tienes permiso para acceder a este restaurante');
            }
            return $restaurant;
        }

        abort(403, 'No autorizado');
    }

    public function getCatalogsByRestaurant($restaurantId)
    {
        $restaurant = $this->authorizeRestaurant($restaurantId);
        
        $catalogs = $restaurant->catalogs()
            ->with(['sections' => function ($query) {
                $query->orderBy('order')
                    ->with(['products' => function ($q) {
                        $q->where('active', true)->orderBy('name');
                    }]);
            }])
            ->where('active', true)
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
        ]);

        $product = $section->products()->create(array_merge($validated, [
            'restaurant_id' => $restaurant->id,
        ]));

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
        ]);

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
