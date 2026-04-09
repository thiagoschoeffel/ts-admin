<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $this->faker->unique(true);
        $locale = config('seeding.faker_locale', config('app.faker_locale'));
        $faker = $locale === 'pt_BR' ? fake('pt_BR') : $this->faker;

        // Catálogo base e seleção determinística
        $products = self::catalog();
        $product = $faker->randomElement($products);

        // Dimensões/peso coerentes com o produto
        $dimensions = self::getDimensionsForProduct($product['name'], $product['unit_of_measure']);

        $statusWeights = config('seeding.weights.status', ['active' => 70, 'inactive' => 30]);
        $status = $this->pickWeighted($statusWeights);

        return [
            'name' => $product['name'],
            'code' => $faker->unique()->regexify('[A-Z]{3}[0-9]{3}'),
            'description' => $product['description'],
            'price' => $product['price'],
            'unit_of_measure' => $product['unit_of_measure'],
            'length' => $dimensions['length'],
            'width' => $dimensions['width'],
            'height' => $dimensions['height'],
            'weight' => $dimensions['weight'],
            'status' => $status,
            'created_by' => $this->existingUserId(),
            'updated_by' => null,
            'deleted_by' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Product $product) {
            // Componentes disponíveis com preços
            $componentes = [
                'Arroz Branco' => ['qty' => 1, 'price' => 6.00],
                'Feijão Carioca' => ['qty' => 0.5, 'price' => 5.00],
                'Farofa' => ['qty' => 0.2, 'price' => 4.00],
                'Salada de Alface' => ['qty' => 0.3, 'price' => 7.00],
                'Bife Acebolado' => ['qty' => 0.5, 'price' => 15.00],
                'Frango Grelhado' => ['qty' => 0.5, 'price' => 12.00],
                'Batata Frita' => ['qty' => 0.3, 'price' => 8.00],
                'Batata Palha' => ['qty' => 0.2, 'price' => 5.00],
                'Purê de Batata' => ['qty' => 0.4, 'price' => 6.00],
                'Legumes Grelhados' => ['qty' => 0.3, 'price' => 9.00],
                'Quiabo Refogado' => ['qty' => 0.2, 'price' => 7.00],
                'Angu' => ['qty' => 0.3, 'price' => 6.00],
                'Pirão' => ['qty' => 0.2, 'price' => 5.00],
                'Couve Refogada' => ['qty' => 0.2, 'price' => 6.00],
                'Vinagrete' => ['qty' => 0.2, 'price' => 5.00],
                'Queijo Coalho' => ['qty' => 0.1, 'price' => 8.00],
                'Laranja' => ['qty' => 0.5, 'price' => 3.00],
                'Tutu de Feijão' => ['qty' => 0.3, 'price' => 6.00],
                'Caruru' => ['qty' => 0.2, 'price' => 7.00],
            ];

            // Componentes específicos para cada marmita
            $marmitaComponents = [
                'Feijoada Completa' => ['Arroz Branco' => 1, 'Feijão Carioca' => 0.5, 'Farofa' => 0.2, 'Couve Refogada' => 0.2, 'Laranja' => 0.5],
                'Arroz com Feijão' => ['Arroz Branco' => 1, 'Feijão Carioca' => 0.5, 'Bife Acebolado' => 0.5, 'Salada de Alface' => 0.3],
                'Moqueca de Peixe' => ['Pirão' => 0.2, 'Arroz Branco' => 1],
                'Strogonoff de Frango' => ['Frango Grelhado' => 0.5, 'Arroz Branco' => 1, 'Batata Palha' => 0.2],
                'Lasanha à Bolonhesa' => [],
                'Churrasco Misto' => ['Bife Acebolado' => 0.5, 'Arroz Branco' => 1, 'Farofa' => 0.2, 'Vinagrete' => 0.2],
                'Baião de Dois' => ['Queijo Coalho' => 0.1, 'Arroz Branco' => 1, 'Feijão Carioca' => 0.5],
                'Frango com Quiabo' => ['Frango Grelhado' => 0.5, 'Quiabo Refogado' => 0.2, 'Angu' => 0.3],
                'Carne de Panela' => ['Bife Acebolado' => 0.5, 'Legumes Grelhados' => 0.3, 'Batata Frita' => 0.3],
                'Peixe Assado' => ['Legumes Grelhados' => 0.3, 'Arroz Branco' => 1],
                'Bobó de Camarão' => ['Arroz Branco' => 1, 'Farofa' => 0.2],
                'Vatapá' => ['Arroz Branco' => 1, 'Caruru' => 0.2],
                'Sarapatel' => ['Arroz Branco' => 1, 'Feijão Carioca' => 0.5],
                'Picanha na Chapa' => ['Bife Acebolado' => 0.5, 'Batata Frita' => 0.3, 'Salada de Alface' => 0.3],
                'Costela Bovina' => ['Purê de Batata' => 0.4, 'Legumes Grelhados' => 0.3],
                'Frango Xadrez' => ['Frango Grelhado' => 0.5, 'Arroz Branco' => 1, 'Legumes Grelhados' => 0.3],
                'Escondidinho de Carne' => ['Queijo Coalho' => 0.1, 'Batata Frita' => 0.3],
                'Rabada' => ['Arroz Branco' => 1, 'Tutu de Feijão' => 0.3],
                'Buchada de Bode' => ['Arroz Branco' => 1, 'Farofa' => 0.2],
                'Moela de Frango' => ['Angu' => 0.3, 'Quiabo Refogado' => 0.2],
                'Dobradinha' => ['Arroz Branco' => 1, 'Feijão Carioca' => 0.5],
                'Tripas' => ['Arroz Branco' => 1, 'Vinagrete' => 0.2],
                'Carne Louca' => ['Bife Acebolado' => 0.5, 'Arroz Branco' => 1, 'Salada de Alface' => 0.3],
                'Galinhada' => ['Arroz Branco' => 1, 'Farofa' => 0.2],
                'Canja de Galinha' => ['Arroz Branco' => 1, 'Legumes Grelhados' => 0.3],
            ];

            // Anexa componentes se for uma marmita
            if (array_key_exists($product->name, $marmitaComponents)) {
                foreach ($marmitaComponents[$product->name] as $compName => $qty) {
                    $compPrice = $componentes[$compName]['price'] ?? 10.00;
                    $componentProduct = Product::where('name', $compName)->first() ?? Product::factory()->create([
                        'name' => $compName,
                        'description' => "Componente: {$compName}",
                        'price' => $compPrice,
                        'unit_of_measure' => $this->getUnitForComponent($compName),
                        'status' => 'active',
                        'created_by' => $product->created_by,
                    ]);

                    $product->components()->attach($componentProduct, [
                        'quantity' => $qty,
                    ]);
                }
            }
        });
    }

    public static function getDimensionsForProduct(string $productName, string $unitOfMeasure): array
    {
        $default = [
            'length' => null,
            'width' => null,
            'height' => null,
            'weight' => null,
        ];

        $dimensions = [
            // Marmitas
            'Feijoada Completa' => ['length' => 20.0, 'width' => 15.0, 'height' => 8.0, 'weight' => 1.2],
            'Arroz com Feijão' => ['length' => 18.0, 'width' => 14.0, 'height' => 7.0, 'weight' => 1.0],
            'Moqueca de Peixe' => ['length' => 20.0, 'width' => 15.0, 'height' => 8.0, 'weight' => 1.1],
            'Strogonoff de Frango' => ['length' => 18.0, 'width' => 14.0, 'height' => 7.0, 'weight' => 0.9],
            'Lasanha à Bolonhesa' => ['length' => 22.0, 'width' => 16.0, 'height' => 6.0, 'weight' => 1.3],
            'Churrasco Misto' => ['length' => 20.0, 'width' => 15.0, 'height' => 9.0, 'weight' => 1.4],
            'Baião de Dois' => ['length' => 18.0, 'width' => 14.0, 'height' => 7.0, 'weight' => 1.0],
            'Frango com Quiabo' => ['length' => 18.0, 'width' => 14.0, 'height' => 7.0, 'weight' => 0.9],
            'Carne de Panela' => ['length' => 20.0, 'width' => 15.0, 'height' => 8.0, 'weight' => 1.2],
            'Peixe Assado' => ['length' => 20.0, 'width' => 15.0, 'height' => 8.0, 'weight' => 1.1],
            'Bobó de Camarão' => ['length' => 20.0, 'width' => 15.0, 'height' => 8.0, 'weight' => 1.0],
            'Vatapá' => ['length' => 18.0, 'width' => 14.0, 'height' => 7.0, 'weight' => 0.9],
            'Sarapatel' => ['length' => 18.0, 'width' => 14.0, 'height' => 7.0, 'weight' => 1.0],
            'Picanha na Chapa' => ['length' => 20.0, 'width' => 15.0, 'height' => 8.0, 'weight' => 1.3],
            'Costela Bovina' => ['length' => 20.0, 'width' => 15.0, 'height' => 9.0, 'weight' => 1.4],
            'Frango Xadrez' => ['length' => 18.0, 'width' => 14.0, 'height' => 7.0, 'weight' => 0.9],
            'Escondidinho de Carne' => ['length' => 16.0, 'width' => 12.0, 'height' => 6.0, 'weight' => 0.8],
            'Rabada' => ['length' => 20.0, 'width' => 15.0, 'height' => 8.0, 'weight' => 1.2],
            'Buchada de Bode' => ['length' => 18.0, 'width' => 14.0, 'height' => 7.0, 'weight' => 1.1],
            'Moela de Frango' => ['length' => 16.0, 'width' => 12.0, 'height' => 6.0, 'weight' => 0.7],
            'Dobradinha' => ['length' => 18.0, 'width' => 14.0, 'height' => 7.0, 'weight' => 1.0],
            'Tripas' => ['length' => 18.0, 'width' => 14.0, 'height' => 7.0, 'weight' => 0.9],
            'Carne Louca' => ['length' => 18.0, 'width' => 14.0, 'height' => 7.0, 'weight' => 1.0],
            'Galinhada' => ['length' => 18.0, 'width' => 14.0, 'height' => 7.0, 'weight' => 0.9],
            'Canja de Galinha' => ['length' => 16.0, 'width' => 12.0, 'height' => 8.0, 'weight' => 0.8],
            // Componentes
            'Arroz Branco' => ['length' => 12.0, 'width' => 10.0, 'height' => 4.0, 'weight' => 0.3],
            'Feijão Carioca' => ['length' => 10.0, 'width' => 8.0, 'height' => 4.0, 'weight' => 0.4],
            'Farofa' => ['length' => 8.0, 'width' => 6.0, 'height' => 3.0, 'weight' => 0.1],
            'Salada de Alface' => ['length' => 12.0, 'width' => 10.0, 'height' => 3.0, 'weight' => 0.2],
            'Bife Acebolado' => ['length' => 15.0, 'width' => 12.0, 'height' => 2.0, 'weight' => 0.5],
            'Frango Grelhado' => ['length' => 15.0, 'width' => 12.0, 'height' => 2.0, 'weight' => 0.4],
            'Batata Frita' => ['length' => 12.0, 'width' => 10.0, 'height' => 3.0, 'weight' => 0.2],
            'Batata Palha' => ['length' => 10.0, 'width' => 8.0, 'height' => 2.0, 'weight' => 0.1],
            'Purê de Batata' => ['length' => 10.0, 'width' => 8.0, 'height' => 4.0, 'weight' => 0.3],
            'Legumes Grelhados' => ['length' => 12.0, 'width' => 10.0, 'height' => 3.0, 'weight' => 0.2],
            'Quiabo Refogado' => ['length' => 10.0, 'width' => 8.0, 'height' => 3.0, 'weight' => 0.2],
            'Angu' => ['length' => 10.0, 'width' => 8.0, 'height' => 4.0, 'weight' => 0.3],
            'Pirão' => ['length' => 8.0, 'width' => 6.0, 'height' => 4.0, 'weight' => 0.2],
            'Couve Refogada' => ['length' => 10.0, 'width' => 8.0, 'height' => 3.0, 'weight' => 0.2],
            'Vinagrete' => ['length' => 8.0, 'width' => 6.0, 'height' => 3.0, 'weight' => 0.1],
            'Queijo Coalho' => ['length' => 6.0, 'width' => 4.0, 'height' => 2.0, 'weight' => 0.05],
            'Laranja' => ['length' => 8.0, 'width' => 8.0, 'height' => 8.0, 'weight' => 0.2],
            'Tutu de Feijão' => ['length' => 10.0, 'width' => 8.0, 'height' => 4.0, 'weight' => 0.3],
            'Caruru' => ['length' => 10.0, 'width' => 8.0, 'height' => 3.0, 'weight' => 0.2],
            // Bebidas
            'Refrigerante 350ml' => ['length' => 6.5, 'width' => 6.5, 'height' => 12.0, 'weight' => 0.37],
            'Refrigerante 600ml' => ['length' => 7.0, 'width' => 7.0, 'height' => 20.0, 'weight' => 0.62],
            'Suco Natural de Laranja' => ['length' => 6.0, 'width' => 6.0, 'height' => 15.0, 'weight' => 0.35],
            'Suco Natural de Limão' => ['length' => 6.0, 'width' => 6.0, 'height' => 15.0, 'weight' => 0.35],
        ];

        return $dimensions[$productName] ?? $default;
    }

    public static function catalog(): array
    {
        return [
            // Marmitas completas
            ['name' => 'Feijoada Completa', 'description' => 'Marmita com feijoada, arroz, couve refogada, laranja e farofa. Prato típico brasileiro.', 'price' => 32.00, 'unit_of_measure' => 'UND'],
            ['name' => 'Arroz com Feijão', 'description' => 'Marmita com arroz branco, feijão carioca, bife acebolado e salada de alface.', 'price' => 22.00, 'unit_of_measure' => 'UND'],
            ['name' => 'Moqueca de Peixe', 'description' => 'Marmita com moqueca de peixe, arroz e pirão. Deliciosa opção baiana.', 'price' => 38.00, 'unit_of_measure' => 'UND'],
            ['name' => 'Strogonoff de Frango', 'description' => 'Marmita com strogonoff de frango, arroz e batata palha. Clássico brasileiro.', 'price' => 28.00, 'unit_of_measure' => 'UND'],
            ['name' => 'Lasanha à Bolonhesa', 'description' => 'Marmita com lasanha de carne moída, molho bolonhesa e queijo gratinado.', 'price' => 30.00, 'unit_of_measure' => 'UND'],
            ['name' => 'Churrasco Misto', 'description' => 'Marmita com picanha, linguiça, arroz, farofa e vinagrete.', 'price' => 42.00, 'unit_of_measure' => 'UND'],
            ['name' => 'Baião de Dois', 'description' => 'Marmita com baião de dois, queijo coalho e carne seca. Prato nordestino.', 'price' => 26.00, 'unit_of_measure' => 'UND'],
            ['name' => 'Frango com Quiabo', 'description' => 'Marmita com frango caipira, quiabo refogado e angu.', 'price' => 24.00, 'unit_of_measure' => 'UND'],
            ['name' => 'Carne de Panela', 'description' => 'Marmita com carne de panela, legumes e batatas.', 'price' => 34.00, 'unit_of_measure' => 'UND'],
            ['name' => 'Peixe Assado', 'description' => 'Marmita com peixe assado, legumes grelhados e arroz.', 'price' => 36.00, 'unit_of_measure' => 'UND'],
            ['name' => 'Bobó de Camarão', 'description' => 'Marmita com bobó de camarão, arroz e farofa. Especialidade baiana.', 'price' => 45.00, 'unit_of_measure' => 'UND'],
            ['name' => 'Vatapá', 'description' => 'Marmita com vatapá de camarão, arroz e caruru.', 'price' => 40.00, 'unit_of_measure' => 'UND'],
            ['name' => 'Sarapatel', 'description' => 'Marmita com sarapatel, arroz e feijão. Prato goiano.', 'price' => 29.00, 'unit_of_measure' => 'UND'],
            ['name' => 'Picanha na Chapa', 'description' => 'Marmita com picanha na chapa, batatas e salada.', 'price' => 44.00, 'unit_of_measure' => 'UND'],
            ['name' => 'Costela Bovina', 'description' => 'Marmita com costela bovina assada, purê e legumes.', 'price' => 39.00, 'unit_of_measure' => 'UND'],
            ['name' => 'Frango Xadrez', 'description' => 'Marmita com frango xadrez, arroz e legumes orientais.', 'price' => 27.00, 'unit_of_measure' => 'UND'],
            ['name' => 'Escondidinho de Carne', 'description' => 'Marmita com escondidinho de carne seca, queijo e batata.', 'price' => 31.00, 'unit_of_measure' => 'UND'],
            ['name' => 'Rabada', 'description' => 'Marmita com rabada cozida, arroz e tutu de feijão.', 'price' => 41.00, 'unit_of_measure' => 'UND'],
            ['name' => 'Buchada de Bode', 'description' => 'Marmita com buchada de bode, arroz e farofa. Prato nordestino.', 'price' => 43.00, 'unit_of_measure' => 'UND'],
            ['name' => 'Moela de Frango', 'description' => 'Marmita com moela de frango, angu e quiabo.', 'price' => 21.00, 'unit_of_measure' => 'UND'],
            ['name' => 'Dobradinha', 'description' => 'Marmita com dobradinha, arroz e feijão.', 'price' => 37.00, 'unit_of_measure' => 'UND'],
            ['name' => 'Tripas', 'description' => 'Marmita com tripas refogadas, arroz e vinagrete.', 'price' => 28.00, 'unit_of_measure' => 'UND'],
            ['name' => 'Carne Louca', 'description' => 'Marmita com carne louca, arroz e salada.', 'price' => 33.00, 'unit_of_measure' => 'UND'],
            ['name' => 'Galinhada', 'description' => 'Marmita com galinhada caipira, arroz e farofa.', 'price' => 25.00, 'unit_of_measure' => 'UND'],
            ['name' => 'Canja de Galinha', 'description' => 'Marmita com canja de galinha, arroz e legumes.', 'price' => 20.00, 'unit_of_measure' => 'UND'],
            // Componentes individuais
            ['name' => 'Arroz Branco', 'description' => 'Porção de arroz branco cozido.', 'price' => 6.00, 'unit_of_measure' => 'PCT'],
            ['name' => 'Feijão Carioca', 'description' => 'Porção de feijão carioca refogado.', 'price' => 5.00, 'unit_of_measure' => 'PCT'],
            ['name' => 'Farofa', 'description' => 'Farofa de mandioca crocante.', 'price' => 4.00, 'unit_of_measure' => 'PCT'],
            ['name' => 'Salada de Alface', 'description' => 'Salada fresca de alface, tomate e cebola.', 'price' => 7.00, 'unit_of_measure' => 'PCT'],
            ['name' => 'Bife Acebolado', 'description' => 'Bife de carne bovina acebolado.', 'price' => 15.00, 'unit_of_measure' => 'KG'],
            ['name' => 'Frango Grelhado', 'description' => 'Peito de frango grelhado.', 'price' => 12.00, 'unit_of_measure' => 'KG'],
            ['name' => 'Batata Frita', 'description' => 'Batatas fritas crocantes.', 'price' => 8.00, 'unit_of_measure' => 'PCT'],
            ['name' => 'Batata Palha', 'description' => 'Batata palha crocante.', 'price' => 5.00, 'unit_of_measure' => 'PCT'],
            ['name' => 'Purê de Batata', 'description' => 'Purê de batata cremoso.', 'price' => 6.00, 'unit_of_measure' => 'PCT'],
            ['name' => 'Legumes Grelhados', 'description' => 'Mix de legumes grelhados.', 'price' => 9.00, 'unit_of_measure' => 'PCT'],
            ['name' => 'Quiabo Refogado', 'description' => 'Quiabo refogado no óleo.', 'price' => 7.00, 'unit_of_measure' => 'PCT'],
            ['name' => 'Angu', 'description' => 'Angu de milho cremoso.', 'price' => 6.00, 'unit_of_measure' => 'PCT'],
            ['name' => 'Pirão', 'description' => 'Pirão de peixe.', 'price' => 5.00, 'unit_of_measure' => 'PCT'],
            ['name' => 'Couve Refogada', 'description' => 'Couve refogada com bacon.', 'price' => 6.00, 'unit_of_measure' => 'PCT'],
            ['name' => 'Vinagrete', 'description' => 'Vinagrete brasileiro.', 'price' => 5.00, 'unit_of_measure' => 'PCT'],
            ['name' => 'Queijo Coalho', 'description' => 'Queijo coalho grelhado.', 'price' => 8.00, 'unit_of_measure' => 'UND'],
            ['name' => 'Laranja', 'description' => 'Laranja para acompanhar.', 'price' => 3.00, 'unit_of_measure' => 'UND'],
            ['name' => 'Tutu de Feijão', 'description' => 'Tutu de feijão mole.', 'price' => 6.00, 'unit_of_measure' => 'PCT'],
            ['name' => 'Caruru', 'description' => 'Caruru baiano.', 'price' => 7.00, 'unit_of_measure' => 'PCT'],
            // Bebidas e sobremesas
            ['name' => 'Refrigerante 350ml', 'description' => 'Refrigerante de cola 350ml.', 'price' => 5.50, 'unit_of_measure' => 'UND'],
            ['name' => 'Refrigerante 600ml', 'description' => 'Refrigerante de cola 600ml.', 'price' => 7.50, 'unit_of_measure' => 'UND'],
            ['name' => 'Suco Natural de Laranja', 'description' => 'Suco de laranja natural 300ml.', 'price' => 6.50, 'unit_of_measure' => 'ML'],
            ['name' => 'Suco Natural de Limão', 'description' => 'Suco de limão natural 300ml.', 'price' => 6.50, 'unit_of_measure' => 'ML'],
            ['name' => 'Água Mineral', 'description' => 'Água mineral 500ml.', 'price' => 3.50, 'unit_of_measure' => 'UND'],
            ['name' => 'Cerveja 350ml', 'description' => 'Cerveja pilsen 350ml.', 'price' => 6.00, 'unit_of_measure' => 'UND'],
            ['name' => 'Chá Gelado', 'description' => 'Chá gelado de limão 300ml.', 'price' => 5.00, 'unit_of_measure' => 'ML'],
            ['name' => 'Pudim', 'description' => 'Pudim de leite caseiro.', 'price' => 7.00, 'unit_of_measure' => 'UND'],
            ['name' => 'Mousse de Maracujá', 'description' => 'Mousse leve de maracujá.', 'price' => 8.00, 'unit_of_measure' => 'UND'],
            ['name' => 'Brigadeiro', 'description' => 'Brigadeiro gourmet.', 'price' => 4.00, 'unit_of_measure' => 'UND'],
            ['name' => 'Doce de Leite', 'description' => 'Doce de leite caseiro.', 'price' => 5.00, 'unit_of_measure' => 'UND'],
            ['name' => 'Torta de Limão', 'description' => 'Torta de limão com merengue.', 'price' => 9.00, 'unit_of_measure' => 'UND'],
        ];
    }

    private function getUnitForComponent(string $componentName): string
    {
        $units = [
            'Arroz Branco' => 'PCT',
            'Feijão Carioca' => 'PCT',
            'Farofa' => 'PCT',
            'Salada de Alface' => 'PCT',
            'Bife Acebolado' => 'KG',
            'Frango Grelhado' => 'KG',
            'Batata Frita' => 'PCT',
            'Batata Palha' => 'PCT',
            'Purê de Batata' => 'PCT',
            'Legumes Grelhados' => 'PCT',
            'Quiabo Refogado' => 'PCT',
            'Angu' => 'PCT',
            'Pirão' => 'PCT',
            'Couve Refogada' => 'PCT',
            'Vinagrete' => 'PCT',
            'Queijo Coalho' => 'UND',
            'Laranja' => 'UND',
            'Tutu de Feijão' => 'PCT',
            'Caruru' => 'PCT',
        ];

        return $units[$componentName] ?? 'UND';
    }

    private function pickWeighted(array $weights): string
    {
        $total = array_sum($weights);
        if ($total <= 0) {
            return array_key_first($weights);
        }
        $rand = mt_rand(1, (int) $total);
        $running = 0;
        foreach ($weights as $key => $weight) {
            $running += (int) $weight;
            if ($rand <= $running) {
                return (string) $key;
            }
        }
        return (string) array_key_first($weights);
    }

    private function existingUserId(): int
    {
        $ids = User::query()
            ->whereIn('email', ['admin@example.com', 'user@example.com'])
            ->pluck('id')
            ->all();

        if (!empty($ids)) {
            return Arr::random($ids);
        }

        return (int) (User::query()->inRandomOrder()->value('id') ?? 1);
    }
}

