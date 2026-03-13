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
    private const STAFF_HIDE_PERMISSION = 'hide_products_from_menu';

    private function strictStaffRestaurantIds($user): array
    {
        return $user->restaurants()->pluck('restaurants.id')->map(fn ($id) => (int) $id)->all();
    }

    private function ensureStaffCanManageRestaurant($user, int $restaurantId): void
    {
        if (!$user->hasRole('staff')) {
            return;
        }

        $staffRestaurantIds = $this->strictStaffRestaurantIds($user);
        if (!in_array($restaurantId, $staffRestaurantIds, true)) {
            abort(403, 'Staff solo puede gestionar el menú de su restaurante asignado');
        }
    }

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
        } elseif ($user->hasRole('admin')) {
            $restaurantIds = $this->managedRestaurantIds($user);
            $restaurants = Restaurant::with(['catalogs.sections.products'])
                ->whereIn('id', $restaurantIds)
                ->get();
        } elseif ($user->hasRole('staff')) {
            $restaurantIds = $this->strictStaffRestaurantIds($user);
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

            $this->ensureStaffCanManageRestaurant($user, (int) $restaurantId);

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

            if ($user->hasRole('staff')) {
                $staffRestaurantIds = $this->strictStaffRestaurantIds($user);
                if (!in_array((int) $restaurantId, $staffRestaurantIds, true)) {
                    return response()->json(['message' => 'Staff solo puede gestionar el menú de su restaurante asignado'], 403);
                }
            }

            if ($user->hasRole('cliente') && !$restaurant->active) {
                return response()->json(['message' => 'Restaurante no disponible'], 404);
            }
        } elseif (!$restaurant->active) {
            return response()->json(['message' => 'Restaurante no disponible'], 404);
        }
        
        $isManagementView = $user && $user->hasAnyRole(['superadmin', 'admin', 'staff']);

        $catalogsQuery = $restaurant->catalogs()->orderBy('order');

        $catalogsQuery->with(['sections' => function ($query) use ($isManagementView) {
            if (!$isManagementView) {
                $query->where('active', true);
            }

            $query->orderBy('order')
                ->with(['products' => function ($q) use ($isManagementView) {
                    if (!$isManagementView) {
                        $q->where('active', true);
                    }

                    $q->orderBy('name');
                }]);
        }]);

        if (!$isManagementView) {
            $catalogsQuery->where('active', true);
        }

        $catalogs = $catalogsQuery->get();

        return response()->json($catalogs);
    }

    public function storeCatalog(Request $request, $restaurantId)
    {
        $user = Auth::user();
        if ($user && $user->hasRole('staff')) {
            return response()->json(['message' => 'Staff no puede crear catálogos'], 403);
        }

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
        $user = Auth::user();
        if ($user && $user->hasRole('staff')) {
            return response()->json(['message' => 'Staff no puede editar catálogos'], 403);
        }

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
        $user = Auth::user();
        if ($user && $user->hasRole('staff')) {
            return response()->json(['message' => 'Staff no puede eliminar catálogos'], 403);
        }

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
        $user = Auth::user();
        if ($user && $user->hasRole('staff')) {
            return response()->json(['message' => 'Staff no puede crear secciones'], 403);
        }

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
        $user = Auth::user();
        if ($user && $user->hasRole('staff')) {
            return response()->json(['message' => 'Staff no puede editar secciones'], 403);
        }

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
        $user = Auth::user();
        if ($user && $user->hasRole('staff')) {
            return response()->json(['message' => 'Staff no puede eliminar secciones'], 403);
        }

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
        $user = Auth::user();
        if ($user && $user->hasRole('staff')) {
            return response()->json(['message' => 'Staff no puede crear productos'], 403);
        }

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
        $user = Auth::user();
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

        if ($user && $user->hasRole('staff')) {
            if (!$user->hasPermission(self::STAFF_HIDE_PERMISSION)) {
                return response()->json(['message' => 'Staff no autorizado para ocultar productos'], 403);
            }

            $validated = $request->validate([
                'active' => 'required|boolean',
            ]);

            $product->update([
                'active' => (bool) $validated['active'],
            ]);

            return response()->json($product);
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
        $user = Auth::user();
        if ($user && $user->hasRole('staff')) {
            return response()->json(['message' => 'Staff no puede eliminar productos'], 403);
        }

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
