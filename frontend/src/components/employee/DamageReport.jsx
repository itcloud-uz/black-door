import React from 'react';

export default function DamageReport({ damageForm, setDamageForm, products, onDamageReport }) {
  return (
    <div className="max-w-xl mx-auto p-8 skeuo-convex">
      <h2 className="text-xl font-black text-red-600 mb-6 text-center">⚠️ Tovar Zararlanishi / Kamomad Dalolatnomasi</h2>
      <form onSubmit={onDamageReport} className="flex flex-col gap-6">
        <div>
          <label className="block text-xs font-bold text-gray-400 uppercase mb-2">Mahsulot tanlang</label>
          <select
            value={damageForm.product_id}
            onChange={(e) => setDamageForm({ ...damageForm, product_id: e.target.value })}
            className="w-full skeuo-input"
            required
          >
            <option value="">— Tanlang —</option>
            {products.map(p => (
              <option key={p.id} value={p.id}>{p.name} (Qoldiq: {parseFloat(p.quantity)} {p.unit_type})</option>
            ))}
          </select>
        </div>

        <div>
          <label className="block text-xs font-bold text-gray-400 uppercase mb-2">Zararlangan / Yo'qolgan miqdor</label>
          <input
            type="number"
            step="0.01"
            value={damageForm.quantity}
            onChange={(e) => setDamageForm({ ...damageForm, quantity: e.target.value })}
            className="w-full skeuo-input"
            placeholder="Soni..."
            required
          />
        </div>

        <div>
          <label className="block text-xs font-bold text-gray-400 uppercase mb-2">Zararlanish sababi (Batafsil tavsifi)</label>
          <textarea
            value={damageForm.notes}
            onChange={(e) => setDamageForm({ ...damageForm, notes: e.target.value })}
            className="w-full skeuo-input min-h-[100px]"
            placeholder="Yaroqsiz bo'lishi sabablarini yozing..."
            required
          />
        </div>

        <button type="submit" className="py-3.5 skeuo-btn text-red-600 font-extrabold text-sm active:scale-95 duration-100">
          ⚠️ Dalolatnomani Rasmiylashtirish
        </button>
      </form>
    </div>
  );
}
