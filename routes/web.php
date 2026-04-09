<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\AddressController;
use Illuminate\Support\Facades\Route;
// use Inertia\Inertia; // evitamos usar diretamente antes de instalar a dependência


Route::get('/', function () {
    return \Inertia\Inertia::render('Home/Index');
})->name('home');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);

    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);
});

// Password reset (forgot password)
Route::middleware('guest')->group(function (): void {
    Route::get('/forgot-password', [\App\Http\Controllers\Auth\PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/forgot-password', [\App\Http\Controllers\Auth\PasswordResetController::class, 'email'])->name('password.email');
    Route::get('/reset-password/{token}', [\App\Http\Controllers\Auth\PasswordResetController::class, 'resetForm'])->name('password.reset');
    Route::post('/reset-password', [\App\Http\Controllers\Auth\PasswordResetController::class, 'reset'])->name('password.update');
});

// Email verification routes
Route::get('/email/verify', [\App\Http\Controllers\Auth\VerificationController::class, 'notice'])->name('verification.notice');
Route::post('/email/verification-notification', [\App\Http\Controllers\Auth\VerificationController::class, 'resend'])->name('verification.send');
Route::get('/email/verify/{id}/{hash}', [\App\Http\Controllers\Auth\VerificationController::class, 'verify'])->middleware('signed')->name('verification.verify');

Route::middleware(['auth', 'verified'])->prefix('admin')->group(function (): void {
    Route::get('/', function () {
        return redirect()->route('dashboard');
    });
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::prefix('users')->name('users.')->group(function (): void {
        Route::get('/', [UserManagementController::class, 'index'])->name('index');
        Route::get('create', [UserManagementController::class, 'create'])->name('create');
        Route::post('/', [UserManagementController::class, 'store'])->name('store');
        Route::get('{user}/modal', [UserManagementController::class, 'modal'])->name('modal');
        Route::get('{user}/edit', [UserManagementController::class, 'edit'])->name('edit');
        Route::patch('{user}', [UserManagementController::class, 'update'])->name('update');
        Route::delete('{user}', [UserManagementController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('leads')->name('leads.')->group(function (): void {
        Route::get('/', [\App\Http\Controllers\LeadController::class, 'index'])->name('index');
        Route::get('create', [\App\Http\Controllers\LeadController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\LeadController::class, 'store'])->name('store');
        Route::get('{lead}/modal', [\App\Http\Controllers\LeadController::class, 'modal'])->name('modal');
        Route::get('{lead}/edit', [\App\Http\Controllers\LeadController::class, 'edit'])->name('edit');
        Route::patch('{lead}', [\App\Http\Controllers\LeadController::class, 'update'])->name('update');
        Route::delete('{lead}', [\App\Http\Controllers\LeadController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('leads/{lead}/interactions')->name('leads.interactions.')->group(function (): void {
        Route::get('/', [App\Http\Controllers\LeadInteractionController::class, 'index'])->name('index');
        Route::post('/', [App\Http\Controllers\LeadInteractionController::class, 'store'])->name('store');
        Route::patch('{interactionId}', [App\Http\Controllers\LeadInteractionController::class, 'update'])->name('update');
        Route::delete('{interactionId}', [App\Http\Controllers\LeadInteractionController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('opportunities')->name('opportunities.')->group(function (): void {
        Route::get('/', [App\Http\Controllers\OpportunityController::class, 'index'])->name('index');
        Route::get('create', [App\Http\Controllers\OpportunityController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\OpportunityController::class, 'store'])->name('store');
        Route::get('{opportunity}/modal', [App\Http\Controllers\OpportunityController::class, 'modal'])->name('modal');
        Route::get('{opportunity}/edit', [App\Http\Controllers\OpportunityController::class, 'edit'])->name('edit');
        Route::patch('{opportunity}', [App\Http\Controllers\OpportunityController::class, 'update'])->name('update');
        Route::delete('{opportunity}', [App\Http\Controllers\OpportunityController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('clients')->name('clients.')->group(function (): void {
        Route::get('/', [ClientController::class, 'index'])->name('index');
        Route::get('create', [ClientController::class, 'create'])->name('create');
        Route::post('/', [ClientController::class, 'store'])->name('store');
        Route::get('{client}/modal', [ClientController::class, 'modal'])->name('modal');
        Route::get('{client}/edit', [ClientController::class, 'edit'])->name('edit');
        Route::patch('{client}', [ClientController::class, 'update'])->name('update');
        Route::delete('{client}', [ClientController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('products')->name('products.')->group(function (): void {
        Route::get('/', [\App\Http\Controllers\ProductController::class, 'index'])->name('index');
        Route::get('create', [\App\Http\Controllers\ProductController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\ProductController::class, 'store'])->name('store');
        Route::get('{product}/modal', [\App\Http\Controllers\ProductController::class, 'modal'])->name('modal');
        Route::get('{product}/edit', [\App\Http\Controllers\ProductController::class, 'edit'])->name('edit');
        Route::patch('{product}', [\App\Http\Controllers\ProductController::class, 'update'])->name('update');
        Route::delete('{product}', [\App\Http\Controllers\ProductController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('sectors')->name('sectors.')->group(function (): void {
        Route::get('/', [\App\Http\Controllers\SectorController::class, 'index'])->name('index');
        Route::get('create', [\App\Http\Controllers\SectorController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\SectorController::class, 'store'])->name('store');
        Route::get('{sector}/modal', [\App\Http\Controllers\SectorController::class, 'modal'])->name('modal');
        Route::get('{sector}/edit', [\App\Http\Controllers\SectorController::class, 'edit'])->name('edit');
        Route::patch('{sector}', [\App\Http\Controllers\SectorController::class, 'update'])->name('update');
        Route::delete('{sector}', [\App\Http\Controllers\SectorController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('raw-materials')->name('raw-materials.')->group(function (): void {
        Route::get('/', [\App\Http\Controllers\RawMaterialController::class, 'index'])->name('index');
        Route::get('create', [\App\Http\Controllers\RawMaterialController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\RawMaterialController::class, 'store'])->name('store');
        Route::get('{rawMaterial}/modal', [\App\Http\Controllers\RawMaterialController::class, 'modal'])->name('modal');
        Route::get('{rawMaterial}/edit', [\App\Http\Controllers\RawMaterialController::class, 'edit'])->name('edit');
        Route::patch('{rawMaterial}', [\App\Http\Controllers\RawMaterialController::class, 'update'])->name('update');
        Route::delete('{rawMaterial}', [\App\Http\Controllers\RawMaterialController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('production-pointings')->name('production-pointings.')->group(function (): void {
        Route::get('/', [\App\Http\Controllers\ProductionPointingController::class, 'index'])->name('index');
        Route::get('create', [\App\Http\Controllers\ProductionPointingController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\ProductionPointingController::class, 'store'])->name('store');
        Route::get('{productionPointing}/modal', [\App\Http\Controllers\ProductionPointingController::class, 'modal'])->name('modal');
        Route::get('{productionPointing}/edit', [\App\Http\Controllers\ProductionPointingController::class, 'edit'])->name('edit');
        Route::patch('{productionPointing}', [\App\Http\Controllers\ProductionPointingController::class, 'update'])->name('update');
        Route::delete('{productionPointing}', [\App\Http\Controllers\ProductionPointingController::class, 'destroy'])->name('destroy');

        // Block productions (entries) for a production pointing
        Route::get('{productionPointing}/block-productions', [\App\Http\Controllers\BlockProductionController::class, 'index'])->name('block-productions.index');
        Route::post('{productionPointing}/block-productions', [\App\Http\Controllers\BlockProductionController::class, 'store'])->name('block-productions.store');
        Route::patch('{productionPointing}/block-productions/{blockProduction}', [\App\Http\Controllers\BlockProductionController::class, 'update'])->name('block-productions.update');
        Route::delete('{productionPointing}/block-productions/{blockProduction}', [\App\Http\Controllers\BlockProductionController::class, 'destroy'])->name('block-productions.destroy');

        // Molded productions for a production pointing
        Route::get('{productionPointing}/molded-productions', [\App\Http\Controllers\MoldedProductionController::class, 'index'])->name('molded-productions.index');
        Route::post('{productionPointing}/molded-productions', [\App\Http\Controllers\MoldedProductionController::class, 'store'])->name('molded-productions.store');
        Route::patch('{productionPointing}/molded-productions/{moldedProduction}', [\App\Http\Controllers\MoldedProductionController::class, 'update'])->name('molded-productions.update');
        Route::delete('{productionPointing}/molded-productions/{moldedProduction}', [\App\Http\Controllers\MoldedProductionController::class, 'destroy'])->name('molded-productions.destroy');
    });

    Route::prefix('block-dispatches')->name('block-dispatches.')->group(function (): void {
        Route::get('/', [\App\Http\Controllers\BlockDispatchController::class, 'index'])->name('index');
        Route::get('create', [\App\Http\Controllers\BlockDispatchController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\BlockDispatchController::class, 'store'])->name('store');
        Route::get('{blockDispatch}/modal', [\App\Http\Controllers\BlockDispatchController::class, 'modal'])->name('modal');
        Route::get('{blockDispatch}/edit', [\App\Http\Controllers\BlockDispatchController::class, 'edit'])->name('edit');
        Route::patch('{blockDispatch}', [\App\Http\Controllers\BlockDispatchController::class, 'update'])->name('update');
        Route::delete('{blockDispatch}', [\App\Http\Controllers\BlockDispatchController::class, 'destroy'])->name('destroy');
        Route::get('available-blocks', [\App\Http\Controllers\BlockDispatchController::class, 'availableBlocks'])->name('available-blocks');
    });

    Route::prefix('molded-dispatches')->name('molded-dispatches.')->group(function (): void {
        Route::get('/', [\App\Http\Controllers\MoldedDispatchController::class, 'index'])->name('index');
        Route::get('create', [\App\Http\Controllers\MoldedDispatchController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\MoldedDispatchController::class, 'store'])->name('store');
        Route::get('{moldedDispatch}/modal', [\App\Http\Controllers\MoldedDispatchController::class, 'modal'])->name('modal');
        Route::get('{moldedDispatch}/edit', [\App\Http\Controllers\MoldedDispatchController::class, 'edit'])->name('edit');
        Route::patch('{moldedDispatch}', [\App\Http\Controllers\MoldedDispatchController::class, 'update'])->name('update');
        Route::delete('{moldedDispatch}', [\App\Http\Controllers\MoldedDispatchController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('silos')->name('silos.')->group(function (): void {
        Route::get('/', [\App\Http\Controllers\SiloController::class, 'index'])->name('index');
        Route::get('create', [\App\Http\Controllers\SiloController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\SiloController::class, 'store'])->name('store');
        Route::get('{silo}/modal', [\App\Http\Controllers\SiloController::class, 'modal'])->name('modal');
        Route::get('{silo}/edit', [\App\Http\Controllers\SiloController::class, 'edit'])->name('edit');
        Route::patch('{silo}', [\App\Http\Controllers\SiloController::class, 'update'])->name('update');
        Route::delete('{silo}', [\App\Http\Controllers\SiloController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('block-types')->name('block-types.')->group(function (): void {
        Route::get('/', [\App\Http\Controllers\BlockTypeController::class, 'index'])->name('index');
        Route::get('create', [\App\Http\Controllers\BlockTypeController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\BlockTypeController::class, 'store'])->name('store');
        Route::get('{blockType}/modal', [\App\Http\Controllers\BlockTypeController::class, 'modal'])->name('modal');
        Route::get('{blockType}/edit', [\App\Http\Controllers\BlockTypeController::class, 'edit'])->name('edit');
        Route::patch('{blockType}', [\App\Http\Controllers\BlockTypeController::class, 'update'])->name('update');
        Route::delete('{blockType}', [\App\Http\Controllers\BlockTypeController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('mold-types')->name('mold-types.')->group(function (): void {
        Route::get('/', [\App\Http\Controllers\MoldTypeController::class, 'index'])->name('index');
        Route::get('create', [\App\Http\Controllers\MoldTypeController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\MoldTypeController::class, 'store'])->name('store');
        Route::get('{moldType}/modal', [\App\Http\Controllers\MoldTypeController::class, 'modal'])->name('modal');
        Route::get('{moldType}/edit', [\App\Http\Controllers\MoldTypeController::class, 'edit'])->name('edit');
        Route::patch('{moldType}', [\App\Http\Controllers\MoldTypeController::class, 'update'])->name('update');
        Route::delete('{moldType}', [\App\Http\Controllers\MoldTypeController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('almoxarifados')->name('almoxarifados.')->group(function (): void {
        Route::get('/', [\App\Http\Controllers\AlmoxarifadoController::class, 'index'])->name('index');
        Route::get('create', [\App\Http\Controllers\AlmoxarifadoController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\AlmoxarifadoController::class, 'store'])->name('store');
        Route::get('{almoxarifado}/modal', [\App\Http\Controllers\AlmoxarifadoController::class, 'modal'])->name('modal');
        Route::get('{almoxarifado}/edit', [\App\Http\Controllers\AlmoxarifadoController::class, 'edit'])->name('edit');
        Route::patch('{almoxarifado}', [\App\Http\Controllers\AlmoxarifadoController::class, 'update'])->name('update');
        Route::delete('{almoxarifado}', [\App\Http\Controllers\AlmoxarifadoController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('machines')->name('machines.')->group(function (): void {
        Route::get('/', [\App\Http\Controllers\MachinesController::class, 'index'])->name('index');
        Route::get('create', [\App\Http\Controllers\MachinesController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\MachinesController::class, 'store'])->name('store');
        Route::get('{machine}/modal', [\App\Http\Controllers\MachinesController::class, 'modal'])->name('modal');
        Route::get('{machine}/edit', [\App\Http\Controllers\MachinesController::class, 'edit'])->name('edit');
        Route::patch('{machine}', [\App\Http\Controllers\MachinesController::class, 'update'])->name('update');
        Route::delete('{machine}', [\App\Http\Controllers\MachinesController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('operators')->name('operators.')->group(function (): void {
        Route::get('/', [\App\Http\Controllers\OperatorsController::class, 'index'])->name('index');
        Route::get('create', [\App\Http\Controllers\OperatorsController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\OperatorsController::class, 'store'])->name('store');
        Route::get('{operator}/modal', [\App\Http\Controllers\OperatorsController::class, 'modal'])->name('modal');
        Route::get('{operator}/edit', [\App\Http\Controllers\OperatorsController::class, 'edit'])->name('edit');
        Route::patch('{operator}', [\App\Http\Controllers\OperatorsController::class, 'update'])->name('update');
        Route::delete('{operator}', [\App\Http\Controllers\OperatorsController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('reason-types')->name('reason-types.')->group(function (): void {
        Route::get('/', [\App\Http\Controllers\ReasonTypesController::class, 'index'])->name('index');
        Route::get('create', [\App\Http\Controllers\ReasonTypesController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\ReasonTypesController::class, 'store'])->name('store');
        Route::get('{reasonType}/modal', [\App\Http\Controllers\ReasonTypesController::class, 'modal'])->name('modal');
        Route::get('{reasonType}/edit', [\App\Http\Controllers\ReasonTypesController::class, 'edit'])->name('edit');
        Route::patch('{reasonType}', [\App\Http\Controllers\ReasonTypesController::class, 'update'])->name('update');
        Route::delete('{reasonType}', [\App\Http\Controllers\ReasonTypesController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('reasons')->name('reasons.')->group(function (): void {
        Route::get('/', [\App\Http\Controllers\ReasonController::class, 'index'])->name('index');
        Route::get('create', [\App\Http\Controllers\ReasonController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\ReasonController::class, 'store'])->name('store');
        Route::get('all-active', [\App\Http\Controllers\ReasonController::class, 'allActive'])->name('all-active');
        Route::get('all', [\App\Http\Controllers\ReasonController::class, 'all'])->name('all');
        Route::get('{reason}/modal', [\App\Http\Controllers\ReasonController::class, 'modal'])->name('modal');
        Route::get('{reason}/edit', [\App\Http\Controllers\ReasonController::class, 'edit'])->name('edit');
        Route::patch('{reason}', [\App\Http\Controllers\ReasonController::class, 'update'])->name('update');
        Route::delete('{reason}', [\App\Http\Controllers\ReasonController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('machine-downtimes')->name('machine_downtimes.')->group(function (): void {
        Route::get('/', [\App\Http\Controllers\MachineDowntimeController::class, 'index'])->name('index');
        Route::get('create', [\App\Http\Controllers\MachineDowntimeController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\MachineDowntimeController::class, 'store'])->name('store');
        Route::get('{machineDowntime}/modal', [\App\Http\Controllers\MachineDowntimeController::class, 'modal'])->name('modal');
        Route::get('{machineDowntime}/edit', [\App\Http\Controllers\MachineDowntimeController::class, 'edit'])->name('edit');
        Route::patch('{machineDowntime}', [\App\Http\Controllers\MachineDowntimeController::class, 'update'])->name('update');
        Route::delete('{machineDowntime}', [\App\Http\Controllers\MachineDowntimeController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('orders')->name('orders.')->group(function (): void {
        Route::get('/', [\App\Http\Controllers\OrderController::class, 'index'])->name('index');
        Route::get('create', [\App\Http\Controllers\OrderController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\OrderController::class, 'store'])->name('store');
        Route::get('{order}/modal', [\App\Http\Controllers\OrderController::class, 'modal'])->name('modal');
        Route::get('{order}/edit', [\App\Http\Controllers\OrderController::class, 'edit'])->name('edit');
        Route::patch('{order}', [\App\Http\Controllers\OrderController::class, 'update'])->name('update');
        Route::delete('{order}', [\App\Http\Controllers\OrderController::class, 'destroy'])->name('destroy');
        Route::get('{order}/pdf', [\App\Http\Controllers\OrdersPdfController::class, 'show'])->name('pdf.show');
    });

    // Order items management routes
    Route::prefix('orders/{order}/items')->name('orders.items.')->group(function (): void {
        Route::post('/', [\App\Http\Controllers\OrderController::class, 'addItem'])->name('store');
        Route::patch('{item}', [\App\Http\Controllers\OrderController::class, 'updateItem'])->name('update');
        Route::delete('{item}', [\App\Http\Controllers\OrderController::class, 'removeItem'])->name('destroy');
    });

    // Inventory & Estoque
    Route::prefix('inventory')->name('inventory.')->group(function (): void {
        Route::get('production-kg-by-day', [\App\Http\Controllers\InventoryController::class, 'productionKgByDay'])->name('production.kg-by-day');
        Route::get('blocks-produced-by-day', [\App\Http\Controllers\InventoryController::class, 'blocksProducedByDay'])->name('blocks.produced-by-day');
        Route::get('block-production-by-type-and-dimensions', [\App\Http\Controllers\InventoryController::class, 'blockProductionByTypeAndDimensions'])->name('block.production-by-type-and-dimensions');
        Route::get('molded-production-and-scrap-by-day', [\App\Http\Controllers\InventoryController::class, 'moldedProductionAndScrapByDay'])->name('molded.production-and-scrap-by-day');
        // Páginas
        Route::get('/', [\App\Http\Controllers\InventoryController::class, 'dashboard'])->name('dashboard');
        // Estoque atual de matéria-prima
        Route::get('raw-material-stock', [\App\Http\Controllers\InventoryController::class, 'rawMaterialStock'])->name('raw-material-stock');
        // Estoque atual de blocos
        Route::get('block-stock', [\App\Http\Controllers\InventoryController::class, 'blockStock'])->name('block-stock');
        // Estoque atual de moldados
        Route::get('molded-stock', [\App\Http\Controllers\InventoryController::class, 'moldedStock'])->name('molded-stock');
        Route::get('movements', [\App\Http\Controllers\InventoryController::class, 'movementsPage'])->name('movements.index');
        Route::get('movements/{movement}/modal', [\App\Http\Controllers\InventoryController::class, 'modal'])->name('movements.modal');
        Route::get('movements/create', [\App\Http\Controllers\InventoryController::class, 'createMovement'])
            ->name('movements.create');
        Route::post('movements', [\App\Http\Controllers\InventoryController::class, 'storeMovement'])
            ->name('movements.store');
        Route::get('movements/{movement}/edit', [\App\Http\Controllers\InventoryController::class, 'editMovement'])
            ->name('movements.edit');
        Route::patch('movements/{movement}', [\App\Http\Controllers\InventoryController::class, 'updateMovement'])
            ->name('movements.update');
        Route::delete('movements/{movement}', [\App\Http\Controllers\InventoryController::class, 'destroyMovement'])
            ->name('movements.destroy');
        Route::post('movements', [\App\Http\Controllers\InventoryController::class, 'storeMovement'])
            ->name('movements.store');
        Route::get('movements/{movement}/edit', [\App\Http\Controllers\InventoryController::class, 'editMovement'])
            ->name('movements.edit');
        Route::patch('movements/{movement}', [\App\Http\Controllers\InventoryController::class, 'updateMovement'])
            ->name('movements.update');
        Route::delete('movements/{movement}', [\App\Http\Controllers\InventoryController::class, 'destroyMovement'])
            ->name('movements.destroy');
        Route::get('production-kg-by-material-type', [\App\Http\Controllers\InventoryController::class, 'productionKgByMaterialType'])->name('production.kg-by-material-type');

        // APIs
        Route::get('summary', [\App\Http\Controllers\InventoryController::class, 'summary'])->name('summary');
        Route::get('silos/load', [\App\Http\Controllers\InventoryController::class, 'siloLoads'])->name('silos.load');
        Route::get('movements/list', [\App\Http\Controllers\InventoryController::class, 'movements'])->name('movements.list');
        Route::get('reservations-by-raw-material', [\App\Http\Controllers\InventoryController::class, 'reservationsByRawMaterial'])->name('reservations.by-raw-material');
        Route::post('raw-materials/movements', [\App\Http\Controllers\InventoryController::class, 'storeRawMaterialMovement'])
            ->name('raw-materials.movements.store');
    });

    Route::prefix('products/{product}/components')->name('products.components.')->group(function (): void {
        Route::get('/', [\App\Http\Controllers\ProductComponentController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\ProductComponentController::class, 'store'])->name('store');
        Route::patch('{componentId}', [\App\Http\Controllers\ProductComponentController::class, 'update'])->name('update');
        Route::delete('{componentId}', [\App\Http\Controllers\ProductComponentController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('clients/{client}/addresses')->name('clients.addresses.')->group(function (): void {
        Route::get('/', [AddressController::class, 'index'])->name('index');
        Route::post('/', [AddressController::class, 'store'])->name('store');
        Route::patch('{addressId}', [AddressController::class, 'update'])->name('update');
        Route::delete('{addressId}', [AddressController::class, 'destroy'])->name('destroy');
    });
});

// Fallback para 404 dentro do grupo 'web', garantindo sessão e autenticação disponíveis
Route::fallback(function (\Illuminate\Http\Request $request) {
    // Se for requisição Inertia (X-Inertia), retorna uma resposta Inertia com status 404
    // Se for uma requisição HTML (navegador), responder com Inertia (HTML inicial)
    if (str_contains($request->header('Accept', ''), 'text/html')) {
        // Incluir a URL requisitada para que o cliente Inertia possa decidir o layout
        return \Inertia\Inertia::render('Errors/404', ['url' => $request->getRequestUri()])->toResponse($request)->setStatusCode(404);
    }

    // Para outras requisições (API/XHR sem Accept HTML), retornar 404 simples
    return response()->json(['message' => 'Not Found'], 404);
});
