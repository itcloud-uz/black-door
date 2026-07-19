<?php

declare(strict_types=1);

namespace App\Http\Controllers\Object;

use App\Http\Controllers\Controller;
use App\Models\ObjectManager;
use App\Models\ObjectEmployee;
use App\Models\WarehouseStock;
use App\Models\WarehouseMovement;
use App\Models\InventoryCheck;
use App\Models\InventoryCheckItem;
use App\Models\Product;
use App\Models\Obj;
use App\Enums\WarehouseMovementType;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WarehouseController extends Controller
{
    protected function getObject()
    {
        $user = Auth::user();
        if ($user->role->value === 'manager') {
            $mgr = ObjectManager::where('user_id', $user->id)->first();
            return $mgr ? $mgr->object : null;
        }
        $emp = ObjectEmployee::where('user_id', $user->id)->first();
        return $emp ? $emp->object : null;
    }

    public function index()
    {
        $object = $this->getObject();
        if (! $object) {
            return redirect()->route('manager.dashboard')->withErrors(['error' => 'Obyekt biriktirilmagan.']);
        }

        $stocks = WarehouseStock::with('product')
            ->where('object_id', $object->id)
            ->get();

        $movements = WarehouseMovement::with(['product', 'fromObject', 'toObject'])
            ->where('object_id', $object->id)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $products = Product::where('is_active', true)->orderBy('name')->get();
        $otherObjects = Obj::where('id', '!=', $object->id)->where('is_active', true)->get();

        return view('manager.warehouse.index', compact(
            'object',
            'stocks',
            'movements',
            'products',
            'otherObjects'
        ));
    }

    public function movement(Request $request)
    {
        $object = $this->getObject();
        if (! $object) {
            return back()->withErrors(['error' => 'Obyekt topilmadi.']);
        }

        $request->validate([
            'product_id' => 'required|exists:products,id',
            'type' => 'required|string|in:incoming,outgoing,transfer',
            'quantity' => 'required|integer|min:1',
            'to_object_id' => 'nullable|required_if:type,transfer|exists:objects,id',
            'recipient_name' => 'nullable|string|max:255',
            'note' => 'nullable|string',
        ]);

        $productId = (int)$request->product_id;
        $type = $request->type;
        $qty = (int)$request->quantity;

        try {
            DB::transaction(function () use ($request, $object, $productId, $type, $qty) {
                $product = Product::findOrFail($productId);

                $stock = WarehouseStock::where('object_id', $object->id)
                    ->where('product_id', $productId)
                    ->firstOrCreate([
                        'object_id' => $object->id,
                        'product_id' => $productId,
                    ], ['quantity' => 0]);

                if ($type === 'incoming') {
                    $stock->quantity += $qty;
                    $stock->save();

                    $mvt = WarehouseMovement::create([
                        'object_id' => $object->id,
                        'product_id' => $productId,
                        'type' => WarehouseMovementType::Incoming->value,
                        'quantity' => $qty,
                        'note' => $request->note,
                        'recipient_name' => $request->recipient_name,
                        'created_by' => Auth::id(),
                        'movement_date' => now()->toDateString(),
                    ]);

                    AuditLogger::log('warehouse_incoming', $mvt, null, $mvt->toArray());

                } elseif ($type === 'outgoing') {
                    if ($stock->quantity < $qty) {
                        throw new \Exception('Omborda yetarli mahsulot mavjud emas.');
                    }

                    $stock->quantity -= $qty;
                    $stock->save();

                    $mvt = WarehouseMovement::create([
                        'object_id' => $object->id,
                        'product_id' => $productId,
                        'type' => WarehouseMovementType::Outgoing->value,
                        'quantity' => $qty,
                        'note' => $request->note,
                        'recipient_name' => $request->recipient_name,
                        'created_by' => Auth::id(),
                        'movement_date' => now()->toDateString(),
                    ]);

                    AuditLogger::log('warehouse_outgoing', $mvt, null, $mvt->toArray());

                    // Check for low stock alert
                    if ($stock->quantity < ($product->min_stock_level ?? 0)) {
                        try {
                            broadcast(new \App\Events\LowStockWarning([
                                'object_name' => $object->name,
                                'product_name' => $product->name,
                                'quantity' => $stock->quantity,
                                'min_limit' => $product->min_stock_level
                            ]))->toOthers();
                        } catch (\Throwable $e) {
                            // Ignore broadcast failures
                        }
                    }

                } elseif ($type === 'transfer') {
                    $toObjectId = (int)$request->to_object_id;
                    if ($toObjectId === $object->id) {
                        throw new \Exception('O\'tkazish uchun boshqa obyekt tanlanishi kerak.');
                    }

                    if ($stock->quantity < $qty) {
                        throw new \Exception('Omborda yetarli mahsulot mavjud emas.');
                    }

                    $toObject = Obj::findOrFail($toObjectId);
                    $destStock = WarehouseStock::where('object_id', $toObjectId)
                        ->where('product_id', $productId)
                        ->firstOrCreate([
                            'object_id' => $toObjectId,
                            'product_id' => $productId,
                        ], ['quantity' => 0]);

                    // Deduct source
                    $stock->quantity -= $qty;
                    $stock->save();

                    // Add destination
                    $destStock->quantity += $qty;
                    $destStock->save();

                    // Log movement for source object
                    $mvtOut = WarehouseMovement::create([
                        'object_id' => $object->id,
                        'product_id' => $productId,
                        'type' => WarehouseMovementType::Transfer->value,
                        'quantity' => $qty,
                        'from_object_id' => $object->id,
                        'to_object_id' => $toObjectId,
                        'note' => $request->note ?? "O'tkazildi: " . $toObject->name,
                        'recipient_name' => $request->recipient_name,
                        'created_by' => Auth::id(),
                        'movement_date' => now()->toDateString(),
                    ]);

                    // Log movement for destination object
                    $mvtIn = WarehouseMovement::create([
                        'object_id' => $toObjectId,
                        'product_id' => $productId,
                        'type' => WarehouseMovementType::Incoming->value,
                        'quantity' => $qty,
                        'from_object_id' => $object->id,
                        'to_object_id' => $toObjectId,
                        'note' => $request->note ?? "O'tkazib olindi: " . $object->name,
                        'recipient_name' => $request->recipient_name,
                        'created_by' => Auth::id(),
                        'movement_date' => now()->toDateString(),
                    ]);

                    AuditLogger::log('warehouse_transfer', $mvtOut, null, [
                        'source_movement' => $mvtOut->toArray(),
                        'destination_movement' => $mvtIn->toArray()
                    ]);

                    // Check for low stock alert
                    if ($stock->quantity < ($product->min_stock_level ?? 0)) {
                        try {
                            broadcast(new \App\Events\LowStockWarning([
                                'object_name' => $object->name,
                                'product_name' => $product->name,
                                'quantity' => $stock->quantity,
                                'min_limit' => $product->min_stock_level
                            ]))->toOthers();
                        } catch (\Throwable $e) {
                            // Ignore broadcast failures
                        }
                    }
                }
            });

            return redirect()->route('manager.warehouse.index')->with('success', 'Ombor harakati muvaffaqiyatli qayd etildi.');

        } catch (\Exception $e) {
            return back()->withErrors(['quantity' => $e->getMessage()])->withInput();
        }
    }

    public function check(Request $request)
    {
        $object = $this->getObject();
        if (! $object) {
            return back()->withErrors(['error' => 'Obyekt topilmadi.']);
        }

        $request->validate([
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.actual_qty' => 'required|integer|min:0',
        ]);

        try {
            DB::transaction(function () use ($request, $object) {
                $check = InventoryCheck::create([
                    'object_id' => $object->id,
                    'checked_by' => Auth::id(),
                    'checked_at' => now(),
                    'status' => 'approved',
                    'note' => $request->input('note', 'Tizimli inventarizatsiya.'),
                    'approved_by' => Auth::id(),
                    'approved_at' => now(),
                ]);

                foreach ($request->items as $itemData) {
                    $productId = (int)$itemData['product_id'];
                    $actualQty = (int)$itemData['actual_qty'];

                    $stock = WarehouseStock::where('object_id', $object->id)
                        ->where('product_id', $productId)
                        ->firstOrCreate([
                            'object_id' => $object->id,
                            'product_id' => $productId,
                        ], ['quantity' => 0]);

                    $expectedQty = $stock->quantity;
                    $diff = $actualQty - $expectedQty;

                    InventoryCheckItem::create([
                        'inventory_check_id' => $check->id,
                        'product_id' => $productId,
                        'expected_qty' => $expectedQty,
                        'actual_qty' => $actualQty,
                        'difference' => $diff,
                        'note' => $itemData['note'] ?? null,
                    ]);

                    // Adjust stock level
                    $stock->quantity = $actualQty;
                    $stock->save();

                    // Log adjustment movement
                    if ($diff !== 0) {
                        WarehouseMovement::create([
                            'object_id' => $object->id,
                            'product_id' => $productId,
                            'type' => WarehouseMovementType::InventoryAdjustment->value,
                            'quantity' => abs($diff),
                            'note' => "Inventarizatsiya farqi: " . ($diff > 0 ? "+{$diff}" : "{$diff}"),
                            'created_by' => Auth::id(),
                            'movement_date' => now()->toDateString(),
                        ]);
                    }
                }

                AuditLogger::log('inventory_check', $check, null, $check->toArray());
            });

            return redirect()->route('manager.warehouse.index')->with('success', 'Inventarizatsiya muvaffaqiyatli yakunlandi va qoldiqlar yangilandi.');

        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
