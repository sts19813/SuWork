<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorageItemRequest;
use App\Models\StorageItem;
use App\Models\StorageItemLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StorageItemController extends Controller
{
    public function index()
    {
        $items = StorageItem::orderBy('created_at', 'desc')->paginate(20);
        return view('storage_items.index', compact('items'));
    }

    public function create()
    {
        return view('storage_items.create');
    }

    public function store(StorageItemRequest $request)
    {
        $data = $request->only(['product_type', 'name', 'description', 'brand', 'condition', 'quantity']);

        if ($request->hasFile('photo')) {
            $data['photo'] = $this->saveCompressedImage($request->file('photo'));
        }

        $item = StorageItem::create($data);

        StorageItemLog::create([
            'storage_item_id' => $item->id,
            'user_id' => auth()->id(),
            'action' => 'created',
            'note' => null,
            'changes' => $item->toArray(),
        ]);

        return redirect()->route('storage_items.index')->with('success', 'Item creado correctamente');
    }

    public function show(StorageItem $storage_item)
    {
        return view('storage_items.show', ['item' => $storage_item]);
    }

    public function edit(StorageItem $storage_item)
    {
        return view('storage_items.edit', ['item' => $storage_item]);
    }

    public function update(StorageItemRequest $request, StorageItem $storage_item)
    {
        $before = $storage_item->toArray();

        $data = $request->only(['product_type', 'name', 'description', 'brand', 'condition', 'quantity']);

        if ($request->hasFile('photo')) {
            if ($storage_item->photo) {
                Storage::disk('public')->delete($storage_item->photo);
            }
            $data['photo'] = $this->saveCompressedImage($request->file('photo'));
        }

        $storage_item->update($data);

        $changes = array_diff_assoc($storage_item->toArray(), $before);

        StorageItemLog::create([
            'storage_item_id' => $storage_item->id,
            'user_id' => auth()->id(),
            'action' => 'updated',
            'note' => null,
            'changes' => $changes ?: null,
        ]);

        return redirect()->route('storage_items.index')->with('success', 'Item actualizado');
    }

    public function destroy(StorageItem $storage_item)
    {
        // Soft delete - no elimina la foto
        $storage_item->delete();

        StorageItemLog::create([
            'storage_item_id' => $storage_item->id,
            'user_id' => auth()->id(),
            'action' => 'soft_deleted',
            'note' => null,
            'changes' => null,
        ]);

        return redirect()->route('storage_items.index')->with('success', 'Item marcado como eliminado');
    }

    // Extra endpoint to add a note / move action for traceability
    public function addNote(Request $request, StorageItem $storage_item)
    {
        $request->validate(['note' => 'required|string|max:2000']);

        StorageItemLog::create([
            'storage_item_id' => $storage_item->id,
            'user_id' => auth()->id(),
            'action' => 'note',
            'note' => $request->input('note'),
            'changes' => null,
        ]);

        return back()->with('success', 'Nota agregada');
    }

    // Ver items eliminados
    public function trashed()
    {
        $items = StorageItem::onlyTrashed()->orderBy('deleted_at', 'desc')->paginate(20);
        return view('storage_items.trashed', compact('items'));
    }

    // Restaurar item eliminado
    public function restore($id)
    {
        $item = StorageItem::withTrashed()->findOrFail($id);
        $item->restore();

        StorageItemLog::create([
            'storage_item_id' => $item->id,
            'user_id' => auth()->id(),
            'action' => 'restored',
            'note' => null,
            'changes' => null,
        ]);

        return redirect()->route('storage_items.index')->with('success', 'Item restaurado');
    }

    // Obtener la lista de eliminados en una modal (AJAX)
    public function deleteWithNote(Request $request, StorageItem $storage_item)
    {
        $request->validate(['delete_note' => 'required|string|max:2000']);

        $storage_item->delete();

        StorageItemLog::create([
            'storage_item_id' => $storage_item->id,
            'user_id' => auth()->id(),
            'action' => 'soft_deleted',
            'note' => $request->input('delete_note'),
            'changes' => null,
        ]);

        return redirect()->route('storage_items.index')->with('success', 'Item eliminado con nota');
    }

    protected function saveCompressedImage($file)
    {
        $filename = 'storage_items/'.time().'_'.Str::random(8).'.jpg';

        if (class_exists('\Intervention\Image\ImageManagerStatic')) {
            $img = \Intervention\Image\ImageManagerStatic::make($file)->orientate();
            $img->resize(1200, null, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
            Storage::disk('public')->put($filename, (string) $img->encode('jpg', 75));
            return $filename;
        }

        // Fallback using GD
        $raw = file_get_contents($file->getRealPath());
        $im = imagecreatefromstring($raw);
        if ($im === false) {
            return null;
        }

        // Resize if wider than 1200
        $width = imagesx($im);
        $height = imagesy($im);
        if ($width > 1200) {
            $newWidth = 1200;
            $newHeight = intval($height * ($newWidth / $width));
            $tmp = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresampled($tmp, $im, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($im);
            $im = $tmp;
        }

        ob_start();
        imagejpeg($im, null, 75);
        $data = ob_get_clean();
        imagedestroy($im);

        Storage::disk('public')->put($filename, $data);

        return $filename;
    }
}
