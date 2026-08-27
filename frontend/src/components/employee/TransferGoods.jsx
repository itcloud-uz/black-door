import React from 'react';

export default function TransferGoods({ transferForm, setTransferForm, products, onTransfer }) {
  return (
    <div className="max-w-xl mx-auto p-8 skeuo-convex">
      <h2 className="text-xl font-black text-[#2D3748] mb-6 text-center">🔄 Ombor Ichida Ko'chirish</h2>
      <form onSubmit={onTransfer} className="flex flex-col gap-6">
        <div>
          <label className="block text-xs font-bold text-gray-400 uppercase mb-2">Mahsulot tanlang</label>
          <select
            value={transferForm.product_id}
            onChange={(e) => {
              const matched = products.find(p => p.id === e.target.value);
              setTransferForm({ 
                ...transferForm, 
                product_id: e.target.value,
                from_location: matched ? matched.location : ''
              });
            }}
            className="w-full skeuo-input"
            required
          >
            <option value="">— Tanlang —</option>
            {products.map(p => (
              <option key={p.id} value={p.id}>{p.name} (Joylashuv: {p.location})</option>
            ))}
          </select>
        </div>

        <div>
          <label className="block text-xs font-bold text-gray-400 uppercase mb-2">Ko'chiriladigan miqdor</label>
          <input
            type="number"
            step="0.01"
            value={transferForm.quantity}
            onChange={(e) => setTransferForm({ ...transferForm, quantity: e.target.value })}
            className="w-full skeuo-input"
            placeholder="Soni..."
            required
          />
        </div>

        <div>
          <label className="block text-xs font-bold text-gray-400 uppercase mb-2">Eski joylashuv (From)</label>
          <input
            type="text"
            value={transferForm.from_location}
            onChange={(e) => setTransferForm({ ...transferForm, from_location: e.target.value })}
            className="w-full skeuo-input"
            disabled
          />
        </div>

        <div>
          <label className="block text-xs font-bold text-gray-400 uppercase mb-2">Yangi joylashuv (To Sektor / Shelf)</label>
          <input
            type="text"
            value={transferForm.to_location}
            onChange={(e) => setTransferForm({ ...transferForm, to_location: e.target.value })}
            className="w-full skeuo-input"
            placeholder="Masalan: B-sektor, 4-tokcha"
            required
          />
        </div>

        <button type="submit" className="py-3.5 skeuo-btn text-blue-600 font-extrabold text-sm active:scale-95 duration-100">
          🔄 Ko'chirishni Bajarish
        </button>
      </form>
    </div>
  );
}
