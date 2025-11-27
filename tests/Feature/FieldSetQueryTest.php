<?php

use AuroraWebSoftware\FlexyField\Enums\FlexyFieldType;
use AuroraWebSoftware\FlexyField\Tests\Concerns\CreatesFieldSets;
use AuroraWebSoftware\FlexyField\Tests\Models\ExampleFlexyModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

uses(CreatesFieldSets::class);

beforeEach(function () {
    Artisan::call('migrate:fresh');

    Schema::create('ff_example_flexy_models', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('field_set_code')->nullable()->index();
        $table->timestamps();

// REMOVED FOR PGSQL:         $table->foreign('field_set_code')
// REMOVED FOR PGSQL:             ->references('set_code')
// REMOVED FOR PGSQL:             ->on('ff_field_sets')
// REMOVED FOR PGSQL:             ->onDelete('set null')
// REMOVED FOR PGSQL:             ->onUpdate('cascade');
    });
});

it('can filter by field set code using whereFieldSet', function () {
    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'footwear',
        fields: ['size' => ['type' => FlexyFieldType::STRING]]
    );

    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'books',
        fields: ['isbn' => ['type' => FlexyFieldType::STRING]]
    );

    $shoe1 = ExampleFlexyModel::create(['name' => 'Shoe 1']);
    $shoe1->assignToFieldSet('footwear');
    $shoe1->save();

    $shoe2 = ExampleFlexyModel::create(['name' => 'Shoe 2']);
    $shoe2->assignToFieldSet('footwear');
    $shoe2->save();

    $book = ExampleFlexyModel::create(['name' => 'Book']);
    $book->assignToFieldSet('books');
    $book->save();

    $footwearProducts = ExampleFlexyModel::whereFieldSet('footwear')->get();

    expect($footwearProducts)->toHaveCount(2)
        ->and($footwearProducts->pluck('name')->toArray())
        ->toContain('Shoe 1', 'Shoe 2')
        ->not->toContain('Book');
});

it('can filter by multiple field sets using whereFieldSetIn', function () {
    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'footwear',
        fields: ['size' => ['type' => FlexyFieldType::STRING]],
        isDefault: false
    );

    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'clothing',
        fields: ['color' => ['type' => FlexyFieldType::STRING]],
        isDefault: false
    );

    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'books',
        fields: ['isbn' => ['type' => FlexyFieldType::STRING]],
        isDefault: false
    );

    $shoe = ExampleFlexyModel::create(['name' => 'Shoe']);
    $shoe->field_set_code = 'footwear';
    $shoe->save();

    $shirt = ExampleFlexyModel::create(['name' => 'Shirt']);
    $shirt->field_set_code = 'clothing';
    $shirt->save();

    $book = ExampleFlexyModel::create(['name' => 'Book']);
    $book->field_set_code = 'books';
    $book->save();

    $products = ExampleFlexyModel::whereFieldSetIn(['footwear', 'clothing'])->get();

    expect($products)->toHaveCount(2)
        ->and($products->pluck('name')->toArray())
        ->toContain('Shoe', 'Shirt')
        ->not->toContain('Book');
});

it('can filter models without field set using whereFieldSetNull', function () {
    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'footwear',
        fields: ['size' => ['type' => FlexyFieldType::STRING]],
        isDefault: false
    );

    $assigned = ExampleFlexyModel::create(['name' => 'Assigned']);
    $assigned->field_set_code = 'footwear';
    $assigned->save();

    // Create models without default set (explicitly set to null)
    $unassigned1 = new ExampleFlexyModel(['name' => 'Unassigned 1']);
    $unassigned1->field_set_code = null;
    $unassigned1->save();

    $unassigned2 = new ExampleFlexyModel(['name' => 'Unassigned 2']);
    $unassigned2->field_set_code = null;
    $unassigned2->save();

    $unassigned = ExampleFlexyModel::whereFieldSetNull()->get();

    expect($unassigned)->toHaveCount(2)
        ->and($unassigned->pluck('name')->toArray())
        ->toContain('Unassigned 1', 'Unassigned 2')
        ->not->toContain('Assigned');
});

it('can query flexy fields across different field sets', function () {
    // Both sets have 'color' field
    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'footwear',
        fields: ['color' => ['type' => FlexyFieldType::STRING]],
        isDefault: false
    );

    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'clothing',
        fields: ['color' => ['type' => FlexyFieldType::STRING]],
        isDefault: false
    );

    // Force view recreation to include color field
    \AuroraWebSoftware\FlexyField\FlexyField::forceRecreateView();

    $shoe = ExampleFlexyModel::create(['name' => 'Red Shoe']);
    $shoe->field_set_code = 'footwear';
    $shoe->save();
    $shoe->flexy->color = 'red';
    $shoe->save();

    $shirt = ExampleFlexyModel::create(['name' => 'Red Shirt']);
    $shirt->field_set_code = 'clothing';
    $shirt->save();
    $shirt->flexy->color = 'red';
    $shirt->save();

    $blueShoe = ExampleFlexyModel::create(['name' => 'Blue Shoe']);
    $blueShoe->field_set_code = 'footwear';
    $blueShoe->save();
    $blueShoe->flexy->color = 'blue';
    $blueShoe->save();

    // Force view recreation after all values are set
    \AuroraWebSoftware\FlexyField\FlexyField::forceRecreateView();

    // Query should return products from both sets
    $redProducts = ExampleFlexyModel::where('flexy_color', 'red')->get();

    expect($redProducts)->toHaveCount(2)
        ->and($redProducts->pluck('name')->toArray())
        ->toContain('Red Shoe', 'Red Shirt')
        ->not->toContain('Blue Shoe');
});

it('can order by flexy fields across different field sets', function () {
    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'footwear',
        fields: ['price' => ['type' => FlexyFieldType::DECIMAL]],
        isDefault: false
    );

    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'books',
        fields: ['price' => ['type' => FlexyFieldType::DECIMAL]],
        isDefault: false
    );

    // Force view recreation to include price field
    \AuroraWebSoftware\FlexyField\FlexyField::forceRecreateView();

    $expensiveShoe = ExampleFlexyModel::create(['name' => 'Expensive Shoe']);
    $expensiveShoe->field_set_code = 'footwear';
    $expensiveShoe->save();
    $expensiveShoe->flexy->price = 200.00;
    $expensiveShoe->save();

    $cheapBook = ExampleFlexyModel::create(['name' => 'Cheap Book']);
    $cheapBook->field_set_code = 'books';
    $cheapBook->save();
    $cheapBook->flexy->price = 10.00;
    $cheapBook->save();

    $mediumShoe = ExampleFlexyModel::create(['name' => 'Medium Shoe']);
    $mediumShoe->field_set_code = 'footwear';
    $mediumShoe->save();
    $mediumShoe->flexy->price = 100.00;
    $mediumShoe->save();

    // Force view recreation after all values are set
    \AuroraWebSoftware\FlexyField\FlexyField::forceRecreateView();

    $ordered = ExampleFlexyModel::orderBy('flexy_price', 'asc')->get();

    expect($ordered->pluck('name')->toArray())
        ->toBe(['Cheap Book', 'Medium Shoe', 'Expensive Shoe']);
});

it('can combine field set filtering with flexy field queries', function () {
    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'footwear',
        fields: [
            'size' => ['type' => FlexyFieldType::STRING],
            'color' => ['type' => FlexyFieldType::STRING],
        ],
        isDefault: false
    );

    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'clothing',
        fields: ['color' => ['type' => FlexyFieldType::STRING]],
        isDefault: false
    );

    // Force view recreation to include fields
    \AuroraWebSoftware\FlexyField\FlexyField::forceRecreateView();

    $redShoe = ExampleFlexyModel::create(['name' => 'Red Shoe']);
    $redShoe->field_set_code = 'footwear';
    $redShoe->save();
    $redShoe->flexy->color = 'red';
    $redShoe->flexy->size = '42';
    $redShoe->save();

    $blueShoe = ExampleFlexyModel::create(['name' => 'Blue Shoe']);
    $blueShoe->field_set_code = 'footwear';
    $blueShoe->save();
    $blueShoe->flexy->color = 'blue';
    $blueShoe->flexy->size = '42';
    $blueShoe->save();

    $redShirt = ExampleFlexyModel::create(['name' => 'Red Shirt']);
    $redShirt->field_set_code = 'clothing';
    $redShirt->save();
    $redShirt->flexy->color = 'red';
    $redShirt->save();

    // Force view recreation after all values are set
    \AuroraWebSoftware\FlexyField\FlexyField::forceRecreateView();

    // Filter by field set AND flexy field
    $redFootwear = ExampleFlexyModel::whereFieldSet('footwear')
        ->where('flexy_color', 'red')
        ->get();

    expect($redFootwear)->toHaveCount(1)
        ->and($redFootwear->first()->name)->toBe('Red Shoe');
});

it('can use dynamic where methods on flexy fields', function () {
    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'products',
        fields: ['color' => ['type' => FlexyFieldType::STRING]],
        isDefault: false
    );

    // Force view recreation to include color field
    \AuroraWebSoftware\FlexyField\FlexyField::forceRecreateView();

    $red = ExampleFlexyModel::create(['name' => 'Red Product']);
    $red->field_set_code = 'products';
    $red->save();
    $red->flexy->color = 'red';
    $red->save();

    $blue = ExampleFlexyModel::create(['name' => 'Blue Product']);
    $blue->field_set_code = 'products';
    $blue->save();
    $blue->flexy->color = 'blue';
    $blue->save();

    // Force view recreation after values are set
    \AuroraWebSoftware\FlexyField\FlexyField::forceRecreateView();

    $redProducts = ExampleFlexyModel::whereFlexyColor('red')->get();

    expect($redProducts)->toHaveCount(1)
        ->and($redProducts->first()->name)->toBe('Red Product');
});

it('handles null flexy field values in queries', function () {
    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'products',
        fields: ['description' => ['type' => FlexyFieldType::STRING]],
        isDefault: false
    );

    // Force view recreation to include description field
    \AuroraWebSoftware\FlexyField\FlexyField::forceRecreateView();

    $withDesc = ExampleFlexyModel::create(['name' => 'With Description']);
    $withDesc->field_set_code = 'products';
    $withDesc->save();
    $withDesc->flexy->description = 'Some description';
    $withDesc->save();

    $withoutDesc = ExampleFlexyModel::create(['name' => 'Without Description']);
    $withoutDesc->field_set_code = 'products';
    $withoutDesc->save(); // No description set

    // Force view recreation after values are set
    \AuroraWebSoftware\FlexyField\FlexyField::forceRecreateView();

    // Query for null values
    $nullDesc = ExampleFlexyModel::whereNull('flexy_description')->get();

    expect($nullDesc)->toHaveCount(1)
        ->and($nullDesc->first()->name)->toBe('Without Description');
});

it('can eager load field set relationship', function () {
    $this->createFieldSetWithFields(
        modelClass: ExampleFlexyModel::class,
        setCode: 'footwear',
        fields: ['size' => ['type' => FlexyFieldType::STRING]]
    );

    $shoe1 = ExampleFlexyModel::create(['name' => 'Shoe 1']);
    $shoe1->assignToFieldSet('footwear');
    $shoe1->save();

    $shoe2 = ExampleFlexyModel::create(['name' => 'Shoe 2']);
    $shoe2->assignToFieldSet('footwear');
    $shoe2->save();

    // Eager load to avoid N+1 queries
    $products = ExampleFlexyModel::with('fieldSet')->get();

    expect($products)->toHaveCount(2)
        ->and($products->first()->relationLoaded('fieldSet'))->toBeTrue()
        ->and($products->first()->fieldSet->set_code)->toBe('footwear');
});
