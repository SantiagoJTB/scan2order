<?php

namespace Database\Seeders;

use App\Models\Restaurant;
use App\Models\Catalog;
use App\Models\Section;
use App\Models\Product;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        $superadmin = User::where('email', 'superadmin@scan2order.local')->first();
        
        if (!$superadmin) {
            $this->command->error('Superadmin user not found. Please run RolePermissionSeeder first.');
            return;
        }

        // Restaurant 1: Trattoria Italiana
        $restaurant1 = Restaurant::create([
            'name' => 'Trattoria Italiana',
            'address' => 'Calle Principal 123',
            'phone' => '555-1234',
            'active' => true,
            'created_by' => $superadmin->id,
        ]);

        // Categories for Restaurant 1
        $cat1Pastas = Category::create(['restaurant_id' => $restaurant1->id, 'name' => 'Pastas']);
        $cat1Pizzas = Category::create(['restaurant_id' => $restaurant1->id, 'name' => 'Pizzas']);
        $cat1Bebidas = Category::create(['restaurant_id' => $restaurant1->id, 'name' => 'Bebidas']);
        $cat1Postres = Category::create(['restaurant_id' => $restaurant1->id, 'name' => 'Postres']);

        // Catalog 1: Menú Principal
        $catalog1 = Catalog::create([
            'restaurant_id' => $restaurant1->id,
            'name' => 'Menú Principal',
            'description' => 'Nuestros platos principales y especialidades',
            'active' => true,
            'order' => 1,
        ]);

        // Section 1.1: Pastas
        $section1_1 = Section::create([
            'catalog_id' => $catalog1->id,
            'name' => 'Pastas Artesanales',
            'description' => 'Pastas frescas hechas diariamente',
            'active' => true,
            'order' => 1,
        ]);

        Product::create(['restaurant_id' => $restaurant1->id, 'category_id' => $cat1Pastas->id, 'section_id' => $section1_1->id, 'name' => 'Spaghetti Carbonara', 'description' => 'Pasta con huevo, panceta y queso parmesano', 'price' => 12.50, 'active' => true]);
        Product::create(['restaurant_id' => $restaurant1->id, 'category_id' => $cat1Pastas->id, 'section_id' => $section1_1->id, 'name' => 'Fettuccine Alfredo', 'description' => 'Pasta con salsa cremosa de queso y mantequilla', 'price' => 11.00, 'active' => true]);
        Product::create(['restaurant_id' => $restaurant1->id, 'category_id' => $cat1Pastas->id, 'section_id' => $section1_1->id, 'name' => 'Lasagna Bolognesa', 'description' => 'Capas de pasta con carne, bechamel y queso', 'price' => 13.50, 'active' => true]);
        Product::create(['restaurant_id' => $restaurant1->id, 'category_id' => $cat1Pastas->id, 'section_id' => $section1_1->id, 'name' => 'Ravioli de Ricotta', 'description' => 'Ravioles rellenos de ricotta con salsa de tomate', 'price' => 12.00, 'active' => true]);

        // Section 1.2: Pizzas
        $section1_2 = Section::create([
            'catalog_id' => $catalog1->id,
            'name' => 'Pizzas al Horno',
            'description' => 'Pizzas elaboradas en horno de leña',
            'active' => true,
            'order' => 2,
        ]);

        Product::create(['restaurant_id' => $restaurant1->id, 'category_id' => $cat1Pizzas->id, 'section_id' => $section1_2->id, 'name' => 'Pizza Margherita', 'description' => 'Tomate, mozzarella y albahaca fresca', 'price' => 10.00, 'active' => true]);
        Product::create(['restaurant_id' => $restaurant1->id, 'category_id' => $cat1Pizzas->id, 'section_id' => $section1_2->id, 'name' => 'Pizza Quattro Formaggi', 'description' => 'Mezcla de cuatro quesos italianos', 'price' => 13.00, 'active' => true]);
        Product::create(['restaurant_id' => $restaurant1->id, 'category_id' => $cat1Pizzas->id, 'section_id' => $section1_2->id, 'name' => 'Pizza Prosciutto', 'description' => 'Jamón serrano, rúcula y parmesano', 'price' => 14.00, 'active' => true]);
        Product::create(['restaurant_id' => $restaurant1->id, 'category_id' => $cat1Pizzas->id, 'section_id' => $section1_2->id, 'name' => 'Pizza Diavola', 'description' => 'Salami picante y pimientos', 'price' => 12.50, 'active' => true]);

        // Section 1.3: Postres
        $section1_3 = Section::create([
            'catalog_id' => $catalog1->id,
            'name' => 'Dolci',
            'description' => 'Postres tradicionales italianos',
            'active' => true,
            'order' => 3,
        ]);

        Product::create(['restaurant_id' => $restaurant1->id, 'category_id' => $cat1Postres->id, 'section_id' => $section1_3->id, 'name' => 'Tiramisú', 'description' => 'Postre de café, mascarpone y cacao', 'price' => 6.00, 'active' => true]);
        Product::create(['restaurant_id' => $restaurant1->id, 'category_id' => $cat1Postres->id, 'section_id' => $section1_3->id, 'name' => 'Panna Cotta', 'description' => 'Crema italiana con salsa de frutos rojos', 'price' => 5.50, 'active' => true]);
        Product::create(['restaurant_id' => $restaurant1->id, 'category_id' => $cat1Postres->id, 'section_id' => $section1_3->id, 'name' => 'Gelato Artesanal', 'description' => 'Helado italiano (3 sabores)', 'price' => 4.50, 'active' => true]);

        // Catalog 2: Bebidas
        $catalog2 = Catalog::create([
            'restaurant_id' => $restaurant1->id,
            'name' => 'Carta de Bebidas',
            'description' => 'Bebidas, vinos y cócteles',
            'active' => true,
            'order' => 2,
        ]);

        // Section 2.1: Bebidas sin alcohol
        $section2_1 = Section::create([
            'catalog_id' => $catalog2->id,
            'name' => 'Bebidas sin Alcohol',
            'description' => 'Refrescos y jugos naturales',
            'active' => true,
            'order' => 1,
        ]);

        Product::create(['restaurant_id' => $restaurant1->id, 'category_id' => $cat1Bebidas->id, 'section_id' => $section2_1->id, 'name' => 'Agua Mineral', 'description' => 'Agua mineral con o sin gas', 'price' => 2.00, 'active' => true]);
        Product::create(['restaurant_id' => $restaurant1->id, 'category_id' => $cat1Bebidas->id, 'section_id' => $section2_1->id, 'name' => 'Limonada Natural', 'description' => 'Limonada recién exprimida', 'price' => 3.50, 'active' => true]);
        Product::create(['restaurant_id' => $restaurant1->id, 'category_id' => $cat1Bebidas->id, 'section_id' => $section2_1->id, 'name' => 'Coca Cola', 'description' => 'Refresco de cola (330ml)', 'price' => 2.50, 'active' => true]);

        // Section 2.2: Vinos
        $section2_2 = Section::create([
            'catalog_id' => $catalog2->id,
            'name' => 'Vinos',
            'description' => 'Selección de vinos tintos y blancos',
            'active' => true,
            'order' => 2,
        ]);

        Product::create(['restaurant_id' => $restaurant1->id, 'category_id' => $cat1Bebidas->id, 'section_id' => $section2_2->id, 'name' => 'Chianti Classico', 'description' => 'Vino tinto italiano DOC (botella)', 'price' => 22.00, 'active' => true]);
        Product::create(['restaurant_id' => $restaurant1->id, 'category_id' => $cat1Bebidas->id, 'section_id' => $section2_2->id, 'name' => 'Pinot Grigio', 'description' => 'Vino blanco italiano (botella)', 'price' => 18.00, 'active' => true]);
        Product::create(['restaurant_id' => $restaurant1->id, 'category_id' => $cat1Bebidas->id, 'section_id' => $section2_2->id, 'name' => 'Prosecco', 'description' => 'Vino espumoso italiano (botella)', 'price' => 20.00, 'active' => true]);

        // Restaurant 2: Cantina Mexicana
        $restaurant2 = Restaurant::create([
            'name' => 'Cantina Mexicana',
            'address' => 'Avenida del Sol 456',
            'phone' => '555-5678',
            'active' => true,
            'created_by' => $superadmin->id,
        ]);

        // Categories for Restaurant 2
        $cat2Tacos = Category::create(['restaurant_id' => $restaurant2->id, 'name' => 'Tacos']);
        $cat2Principales = Category::create(['restaurant_id' => $restaurant2->id, 'name' => 'Platos Principales']);
        $cat2Bebidas = Category::create(['restaurant_id' => $restaurant2->id, 'name' => 'Bebidas']);
        $cat2Antojitos = Category::create(['restaurant_id' => $restaurant2->id, 'name' => 'Antojitos']);

        // Catalog 3: Menú Mexicano
        $catalog3 = Catalog::create([
            'restaurant_id' => $restaurant2->id,
            'name' => 'Menú Mexicano',
            'description' => 'Auténtica comida mexicana',
            'active' => true,
            'order' => 1,
        ]);

        // Section 3.1: Tacos
        $section3_1 = Section::create([
            'catalog_id' => $catalog3->id,
            'name' => 'Tacos',
            'description' => 'Tacos con tortilla de maíz',
            'active' => true,
            'order' => 1,
        ]);

        Product::create(['restaurant_id' => $restaurant2->id, 'category_id' => $cat2Tacos->id, 'section_id' => $section3_1->id, 'name' => 'Tacos al Pastor', 'description' => 'Tacos con carne al pastor, piña y cilantro (3 pzas)', 'price' => 8.50, 'active' => true]);
        Product::create(['restaurant_id' => $restaurant2->id, 'category_id' => $cat2Tacos->id, 'section_id' => $section3_1->id, 'name' => 'Tacos de Carnitas', 'description' => 'Tacos de cerdo confitado con cebolla y cilantro (3 pzas)', 'price' => 8.00, 'active' => true]);
        Product::create(['restaurant_id' => $restaurant2->id, 'category_id' => $cat2Tacos->id, 'section_id' => $section3_1->id, 'name' => 'Tacos de Pescado', 'description' => 'Tacos de pescado empanizado con col y salsa (3 pzas)', 'price' => 9.50, 'active' => true]);
        Product::create(['restaurant_id' => $restaurant2->id, 'category_id' => $cat2Tacos->id, 'section_id' => $section3_1->id, 'name' => 'Tacos Vegetarianos', 'description' => 'Tacos con frijoles refritos y vegetales (3 pzas)', 'price' => 7.00, 'active' => true]);

        // Section 3.2: Platos Principales
        $section3_2 = Section::create([
            'catalog_id' => $catalog3->id,
            'name' => 'Platos Principales',
            'description' => 'Especialidades de la casa',
            'active' => true,
            'order' => 2,
        ]);

        Product::create(['restaurant_id' => $restaurant2->id, 'category_id' => $cat2Principales->id, 'section_id' => $section3_2->id, 'name' => 'Enchiladas Verdes', 'description' => 'Tortillas rellenas con pollo, salsa verde y crema', 'price' => 11.00, 'active' => true]);
        Product::create(['restaurant_id' => $restaurant2->id, 'category_id' => $cat2Principales->id, 'section_id' => $section3_2->id, 'name' => 'Burrito de Carne Asada', 'description' => 'Tortilla de harina con carne asada, frijoles y queso', 'price' => 10.50, 'active' => true]);
        Product::create(['restaurant_id' => $restaurant2->id, 'category_id' => $cat2Principales->id, 'section_id' => $section3_2->id, 'name' => 'Quesadilla Mixta', 'description' => 'Tortilla con queso, pollo y champiñones', 'price' => 9.00, 'active' => true]);
        Product::create(['restaurant_id' => $restaurant2->id, 'category_id' => $cat2Principales->id, 'section_id' => $section3_2->id, 'name' => 'Fajitas de Pollo', 'description' => 'Pollo marinado con pimientos y cebolla, tortillas incluidas', 'price' => 13.00, 'active' => true]);

        // Section 3.3: Antojitos
        $section3_3 = Section::create([
            'catalog_id' => $catalog3->id,
            'name' => 'Antojitos',
            'description' => 'Aperitivos y entradas',
            'active' => true,
            'order' => 3,
        ]);

        Product::create(['restaurant_id' => $restaurant2->id, 'category_id' => $cat2Antojitos->id, 'section_id' => $section3_3->id, 'name' => 'Guacamole con Totopos', 'description' => 'Guacamole fresco con chips de maíz', 'price' => 6.50, 'active' => true]);
        Product::create(['restaurant_id' => $restaurant2->id, 'category_id' => $cat2Antojitos->id, 'section_id' => $section3_3->id, 'name' => 'Nachos con Queso', 'description' => 'Nachos con queso fundido y jalapeños', 'price' => 7.00, 'active' => true]);
        Product::create(['restaurant_id' => $restaurant2->id, 'category_id' => $cat2Antojitos->id, 'section_id' => $section3_3->id, 'name' => 'Elote Asado', 'description' => 'Mazorca de maíz con mayonesa, queso y chile', 'price' => 4.50, 'active' => true]);

        // Catalog 4: Bebidas
        $catalog4 = Catalog::create([
            'restaurant_id' => $restaurant2->id,
            'name' => 'Bebidas',
            'description' => 'Bebidas tradicionales y cócteles',
            'active' => true,
            'order' => 2,
        ]);

        // Section 4.1: Refrescos
        $section4_1 = Section::create([
            'catalog_id' => $catalog4->id,
            'name' => 'Refrescos',
            'description' => 'Bebidas no alcohólicas',
            'active' => true,
            'order' => 1,
        ]);

        Product::create(['restaurant_id' => $restaurant2->id, 'category_id' => $cat2Bebidas->id, 'section_id' => $section4_1->id, 'name' => 'Agua de Horchata', 'description' => 'Bebida de arroz con canela', 'price' => 3.00, 'active' => true]);
        Product::create(['restaurant_id' => $restaurant2->id, 'category_id' => $cat2Bebidas->id, 'section_id' => $section4_1->id, 'name' => 'Jamaica', 'description' => 'Bebida de flor de jamaica', 'price' => 3.00, 'active' => true]);
        Product::create(['restaurant_id' => $restaurant2->id, 'category_id' => $cat2Bebidas->id, 'section_id' => $section4_1->id, 'name' => 'Coca Cola Mexicana', 'description' => 'Coca Cola de botella de vidrio', 'price' => 3.50, 'active' => true]);

        // Section 4.2: Cócteles
        $section4_2 = Section::create([
            'catalog_id' => $catalog4->id,
            'name' => 'Cócteles',
            'description' => 'Cócteles con tequila y mezcal',
            'active' => true,
            'order' => 2,
        ]);

        Product::create(['restaurant_id' => $restaurant2->id, 'category_id' => $cat2Bebidas->id, 'section_id' => $section4_2->id, 'name' => 'Margarita Clásica', 'description' => 'Tequila, triple sec y limón', 'price' => 8.00, 'active' => true]);
        Product::create(['restaurant_id' => $restaurant2->id, 'category_id' => $cat2Bebidas->id, 'section_id' => $section4_2->id, 'name' => 'Paloma', 'description' => 'Tequila con refresco de toronja', 'price' => 7.50, 'active' => true]);
        Product::create(['restaurant_id' => $restaurant2->id, 'category_id' => $cat2Bebidas->id, 'section_id' => $section4_2->id, 'name' => 'Michelada', 'description' => 'Cerveza con limón y salsas', 'price' => 6.00, 'active' => true]);

        // Restaurant 3: Casa Dana
        $restaurant3 = Restaurant::create([
            'name' => 'Casa Dana',
            'address' => 'Plaza Mayor 15',
            'phone' => '555-9876',
            'active' => true,
            'created_by' => $superadmin->id,
        ]);

        // Categories for Restaurant 3
        $cat3Entrantes = Category::create(['restaurant_id' => $restaurant3->id, 'name' => 'Entrantes']);
        $cat3Arroces = Category::create(['restaurant_id' => $restaurant3->id, 'name' => 'Arroces']);
        $cat3Carnes = Category::create(['restaurant_id' => $restaurant3->id, 'name' => 'Carnes']);
        $cat3Pescados = Category::create(['restaurant_id' => $restaurant3->id, 'name' => 'Pescados']);
        $cat3Postres = Category::create(['restaurant_id' => $restaurant3->id, 'name' => 'Postres']);
        $cat3Bebidas = Category::create(['restaurant_id' => $restaurant3->id, 'name' => 'Bebidas']);

        // Catalog 5: Carta Principal Casa Dana
        $catalog5 = Catalog::create([
            'restaurant_id' => $restaurant3->id,
            'name' => 'Carta Principal',
            'description' => 'Cocina mediterránea y española',
            'active' => true,
            'order' => 1,
        ]);

        // Section 5.1: Entrantes
        $section5_1 = Section::create([
            'catalog_id' => $catalog5->id,
            'name' => 'Entrantes',
            'description' => 'Para empezar y compartir',
            'active' => true,
            'order' => 1,
        ]);

        Product::create(['restaurant_id' => $restaurant3->id, 'category_id' => $cat3Entrantes->id, 'section_id' => $section5_1->id, 'name' => 'Jamón Ibérico', 'description' => 'Jamón ibérico de bellota con pan con tomate', 'price' => 18.50, 'active' => true]);
        Product::create(['restaurant_id' => $restaurant3->id, 'category_id' => $cat3Entrantes->id, 'section_id' => $section5_1->id, 'name' => 'Croquetas Caseras', 'description' => 'Croquetas de jamón ibérico (6 unidades)', 'price' => 9.00, 'active' => true]);
        Product::create(['restaurant_id' => $restaurant3->id, 'category_id' => $cat3Entrantes->id, 'section_id' => $section5_1->id, 'name' => 'Patatas Bravas', 'description' => 'Patatas fritas con salsa brava y alioli', 'price' => 7.50, 'active' => true]);
        Product::create(['restaurant_id' => $restaurant3->id, 'category_id' => $cat3Entrantes->id, 'section_id' => $section5_1->id, 'name' => 'Tabla de Quesos', 'description' => 'Selección de quesos artesanales con mermelada', 'price' => 14.00, 'active' => true]);
        Product::create(['restaurant_id' => $restaurant3->id, 'category_id' => $cat3Entrantes->id, 'section_id' => $section5_1->id, 'name' => 'Pulpo a la Gallega', 'description' => 'Pulpo cocido con pimentón y aceite de oliva', 'price' => 16.50, 'active' => true]);
        Product::create(['restaurant_id' => $restaurant3->id, 'category_id' => $cat3Entrantes->id, 'section_id' => $section5_1->id, 'name' => 'Ensalada Mediterránea', 'description' => 'Lechugas, tomate, pepino, aceitunas y queso feta', 'price' => 8.50, 'active' => true]);

        // Section 5.2: Arroces
        $section5_2 = Section::create([
            'catalog_id' => $catalog5->id,
            'name' => 'Arroces y Paellas',
            'description' => 'Arroces tradicionales (mínimo 2 personas)',
            'active' => true,
            'order' => 2,
        ]);

        Product::create(['restaurant_id' => $restaurant3->id, 'category_id' => $cat3Arroces->id, 'section_id' => $section5_2->id, 'name' => 'Paella Valenciana', 'description' => 'Arroz con pollo, conejo y verduras (2 pers)', 'price' => 32.00, 'active' => true]);
        Product::create(['restaurant_id' => $restaurant3->id, 'category_id' => $cat3Arroces->id, 'section_id' => $section5_2->id, 'name' => 'Paella de Marisco', 'description' => 'Arroz con gambas, mejillones y calamares (2 pers)', 'price' => 36.00, 'active' => true]);
        Product::create(['restaurant_id' => $restaurant3->id, 'category_id' => $cat3Arroces->id, 'section_id' => $section5_2->id, 'name' => 'Arroz Negro', 'description' => 'Arroz con tinta de calamar y alioli (2 pers)', 'price' => 34.00, 'active' => true]);
        Product::create(['restaurant_id' => $restaurant3->id, 'category_id' => $cat3Arroces->id, 'section_id' => $section5_2->id, 'name' => 'Arroz con Bogavante', 'description' => 'Arroz caldoso con bogavante (2 pers)', 'price' => 45.00, 'active' => true]);

        // Section 5.3: Carnes
        $section5_3 = Section::create([
            'catalog_id' => $catalog5->id,
            'name' => 'Carnes',
            'description' => 'Carnes a la brasa y guisados',
            'active' => true,
            'order' => 3,
        ]);

        Product::create(['restaurant_id' => $restaurant3->id, 'category_id' => $cat3Carnes->id, 'section_id' => $section5_3->id, 'name' => 'Chuletón de Buey', 'description' => 'Chuletón de buey madurado a la brasa (500g)', 'price' => 28.00, 'active' => true]);
        Product::create(['restaurant_id' => $restaurant3->id, 'category_id' => $cat3Carnes->id, 'section_id' => $section5_3->id, 'name' => 'Secreto Ibérico', 'description' => 'Secreto ibérico a la plancha con patatas', 'price' => 19.50, 'active' => true]);
        Product::create(['restaurant_id' => $restaurant3->id, 'category_id' => $cat3Carnes->id, 'section_id' => $section5_3->id, 'name' => 'Cordero Asado', 'description' => 'Paletilla de cordero asado al horno', 'price' => 22.00, 'active' => true]);
        Product::create(['restaurant_id' => $restaurant3->id, 'category_id' => $cat3Carnes->id, 'section_id' => $section5_3->id, 'name' => 'Rabo de Toro', 'description' => 'Rabo de toro estofado con patatas', 'price' => 18.50, 'active' => true]);
        Product::create(['restaurant_id' => $restaurant3->id, 'category_id' => $cat3Carnes->id, 'section_id' => $section5_3->id, 'name' => 'Pollo al Ajillo', 'description' => 'Pollo de corral salteado con ajo y perejil', 'price' => 15.00, 'active' => true]);

        // Section 5.4: Pescados
        $section5_4 = Section::create([
            'catalog_id' => $catalog5->id,
            'name' => 'Pescados',
            'description' => 'Pescados frescos del día',
            'active' => true,
            'order' => 4,
        ]);

        Product::create(['restaurant_id' => $restaurant3->id, 'category_id' => $cat3Pescados->id, 'section_id' => $section5_4->id, 'name' => 'Lubina a la Sal', 'description' => 'Lubina salvaje cocinada en costra de sal', 'price' => 24.00, 'active' => true]);
        Product::create(['restaurant_id' => $restaurant3->id, 'category_id' => $cat3Pescados->id, 'section_id' => $section5_4->id, 'name' => 'Bacalao al Pil Pil', 'description' => 'Bacalao confitado en salsa de ajo y aceite', 'price' => 21.50, 'active' => true]);
        Product::create(['restaurant_id' => $restaurant3->id, 'category_id' => $cat3Pescados->id, 'section_id' => $section5_4->id, 'name' => 'Dorada a la Espalda', 'description' => 'Dorada fresca a la plancha con guarnición', 'price' => 19.00, 'active' => true]);
        Product::create(['restaurant_id' => $restaurant3->id, 'category_id' => $cat3Pescados->id, 'section_id' => $section5_4->id, 'name' => 'Merluza a la Vasca', 'description' => 'Merluza en salsa verde con almejas', 'price' => 18.50, 'active' => true]);
        Product::create(['restaurant_id' => $restaurant3->id, 'category_id' => $cat3Pescados->id, 'section_id' => $section5_4->id, 'name' => 'Calamares a la Romana', 'description' => 'Calamares rebozados con limón', 'price' => 14.00, 'active' => true]);

        // Section 5.5: Postres
        $section5_5 = Section::create([
            'catalog_id' => $catalog5->id,
            'name' => 'Postres',
            'description' => 'Dulces tradicionales',
            'active' => true,
            'order' => 5,
        ]);

        Product::create(['restaurant_id' => $restaurant3->id, 'category_id' => $cat3Postres->id, 'section_id' => $section5_5->id, 'name' => 'Tarta de Santiago', 'description' => 'Tarta de almendra tradicional gallega', 'price' => 6.50, 'active' => true]);
        Product::create(['restaurant_id' => $restaurant3->id, 'category_id' => $cat3Postres->id, 'section_id' => $section5_5->id, 'name' => 'Crema Catalana', 'description' => 'Crema quemada con azúcar caramelizado', 'price' => 5.50, 'active' => true]);
        Product::create(['restaurant_id' => $restaurant3->id, 'category_id' => $cat3Postres->id, 'section_id' => $section5_5->id, 'name' => 'Flan Casero', 'description' => 'Flan de huevo con caramelo', 'price' => 5.00, 'active' => true]);
        Product::create(['restaurant_id' => $restaurant3->id, 'category_id' => $cat3Postres->id, 'section_id' => $section5_5->id, 'name' => 'Tarta de Queso', 'description' => 'Tarta de queso cremosa estilo San Sebastián', 'price' => 6.00, 'active' => true]);
        Product::create(['restaurant_id' => $restaurant3->id, 'category_id' => $cat3Postres->id, 'section_id' => $section5_5->id, 'name' => 'Torrijas', 'description' => 'Torrijas con miel y canela', 'price' => 5.50, 'active' => true]);

        // Catalog 6: Bebidas Casa Dana
        $catalog6 = Catalog::create([
            'restaurant_id' => $restaurant3->id,
            'name' => 'Carta de Bebidas',
            'description' => 'Vinos, cervezas y bebidas',
            'active' => true,
            'order' => 2,
        ]);

        // Section 6.1: Vinos Tintos
        $section6_1 = Section::create([
            'catalog_id' => $catalog6->id,
            'name' => 'Vinos Tintos',
            'description' => 'Selección de vinos tintos españoles',
            'active' => true,
            'order' => 1,
        ]);

        Product::create(['restaurant_id' => $restaurant3->id, 'category_id' => $cat3Bebidas->id, 'section_id' => $section6_1->id, 'name' => 'Ribera del Duero Crianza', 'description' => 'Vino tinto crianza D.O. Ribera (botella)', 'price' => 24.00, 'active' => true]);
        Product::create(['restaurant_id' => $restaurant3->id, 'category_id' => $cat3Bebidas->id, 'section_id' => $section6_1->id, 'name' => 'Rioja Reserva', 'description' => 'Vino tinto reserva D.O. Rioja (botella)', 'price' => 28.00, 'active' => true]);
        Product::create(['restaurant_id' => $restaurant3->id, 'category_id' => $cat3Bebidas->id, 'section_id' => $section6_1->id, 'name' => 'Priorat', 'description' => 'Vino tinto D.O. Priorat (botella)', 'price' => 32.00, 'active' => true]);

        // Section 6.2: Vinos Blancos
        $section6_2 = Section::create([
            'catalog_id' => $catalog6->id,
            'name' => 'Vinos Blancos',
            'description' => 'Vinos blancos y espumosos',
            'active' => true,
            'order' => 2,
        ]);

        Product::create(['restaurant_id' => $restaurant3->id, 'category_id' => $cat3Bebidas->id, 'section_id' => $section6_2->id, 'name' => 'Albariño', 'description' => 'Vino blanco D.O. Rías Baixas (botella)', 'price' => 22.00, 'active' => true]);
        Product::create(['restaurant_id' => $restaurant3->id, 'category_id' => $cat3Bebidas->id, 'section_id' => $section6_2->id, 'name' => 'Verdejo', 'description' => 'Vino blanco D.O. Rueda (botella)', 'price' => 18.00, 'active' => true]);
        Product::create(['restaurant_id' => $restaurant3->id, 'category_id' => $cat3Bebidas->id, 'section_id' => $section6_2->id, 'name' => 'Cava Brut Nature', 'description' => 'Cava brut nature D.O. Cava (botella)', 'price' => 20.00, 'active' => true]);

        // Section 6.3: Cervezas y Refrescos
        $section6_3 = Section::create([
            'catalog_id' => $catalog6->id,
            'name' => 'Cervezas y Refrescos',
            'description' => 'Bebidas sin alcohol y cervezas',
            'active' => true,
            'order' => 3,
        ]);

        Product::create(['restaurant_id' => $restaurant3->id, 'category_id' => $cat3Bebidas->id, 'section_id' => $section6_3->id, 'name' => 'Cerveza Mahou', 'description' => 'Cerveza rubia de barril (caña)', 'price' => 2.50, 'active' => true]);
        Product::create(['restaurant_id' => $restaurant3->id, 'category_id' => $cat3Bebidas->id, 'section_id' => $section6_3->id, 'name' => 'Estrella Galicia', 'description' => 'Cerveza especial (botella 33cl)', 'price' => 3.00, 'active' => true]);
        Product::create(['restaurant_id' => $restaurant3->id, 'category_id' => $cat3Bebidas->id, 'section_id' => $section6_3->id, 'name' => 'Agua Mineral', 'description' => 'Agua mineral natural (botella 500ml)', 'price' => 2.00, 'active' => true]);
        Product::create(['restaurant_id' => $restaurant3->id, 'category_id' => $cat3Bebidas->id, 'section_id' => $section6_3->id, 'name' => 'Coca Cola', 'description' => 'Refresco de cola (botella 33cl)', 'price' => 2.50, 'active' => true]);
        Product::create(['restaurant_id' => $restaurant3->id, 'category_id' => $cat3Bebidas->id, 'section_id' => $section6_3->id, 'name' => 'Zumo Natural', 'description' => 'Zumo de naranja recién exprimido', 'price' => 4.00, 'active' => true]);

        $this->command->info('Test data created successfully!');
        $this->command->info('- 3 Restaurants created');
        $this->command->info('- 6 Catalogs created');
        $this->command->info('- 16 Sections created');
        $this->command->info('- 82 Products created');
    }
}
