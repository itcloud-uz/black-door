import React from 'react';

export default function ReceiveGoods({ receiveForm, setReceiveForm, products, onReceive }) {
  return (
    <div className="max-w-xl mx-auto p-8 skeuo-convex">
      <h2 className="text-xl font-black text-[#2D3748] mb-6 text-center">📥 Mahsulot Qabul Qilish</h2>
      <form onSubmit={onReceive} className="flex flex-col gap-6">
        <div>
          <label className="block text-xs font-bold text-gray-400 uppercase mb-2">Mahsulot tanlang</label>
          <select
            value={receiveForm.product_id}
            onChange={(e) => setReceiveForm({ ...receiveForm, product_id: e.target.value })}
            className="w-full skeuo-input"
            required
          >
            <option value="">— Tanlang —</option>
            {products.map(p => (
              <option key={p.id} value={p.id}>{p.name}</option>
            ))}
          </select>
        </div>

        <div>
          <label className="block text-xs font-bold text-gray-400 uppercase mb-2">Miqdori</label>
          <input
            type="number"
            step="0.01"
            value={receiveForm.quantity}
            onChange={(e) => setReceiveForm({ ...receiveForm, quantity: e.target.value })}
            className="w-full skeuo-input"
            placeholder="Yozing..."
            required
          />
        </div>

        <div>
          <label className="block text-xs font-bold text-gray-400 uppercase mb-2">Ombordagi Joylashuv (Sektor, Tokcha)</label>
          <input
            type="text"
            value={receiveForm.to_location}
            onChange={(e) => setReceiveForm({ ...receiveForm, to_location: e.target.value })}
            className="w-full skeuo-input"
            placeholder="Masalan: A-sektor, 3-tokcha"
            required
          />
        </div>

        <div>
          <label className="block text-xs font-bold text-gray-400 uppercase mb-2">Qabul Izohi / Hujjat nomi</label>
          <textarea
            value={receiveForm.notes}
            onChange={(e) => setReceiveForm({ ...receiveForm, notes: e.target.value })}
            className="w-full skeuo-input min-h-[80px]"
            placeholder="Masalan: Toshkent zavoddan yuk keldi"
          />
        </div>

        <button type="submit" className="py-3.5 skeuo-btn text-blue-600 font-extrabold text-sm active:scale-95 duration-100">
          📥 Qabulni Tasdiqlash
        </button>
      </form>
    </div>
  );
}
