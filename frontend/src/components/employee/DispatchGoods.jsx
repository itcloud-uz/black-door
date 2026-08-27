import React from 'react';

export default function DispatchGoods({ dispatchForm, setDispatchForm, products, onDispatch }) {
  return (
    <div className="max-w-xl mx-auto p-8 skeuo-convex">
      <h2 className="text-xl font-black text-[#2D3748] mb-6 text-center">📤 Mahsulot Jo'natish</h2>
      <form onSubmit={onDispatch} className="flex flex-col gap-6">
        <div>
          <label className="block text-xs font-bold text-gray-400 uppercase mb-2">Mahsulot tanlang</label>
          <select
            value={dispatchForm.product_id}
            onChange={(e) => {
              const matched = products.find(p => p.id === e.target.value);
              setDispatchForm({ 
                ...dispatchForm, 
                product_id: e.target.value,
                from_location: matched ? matched.location : ''
              });
            }}
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
          <label className="block text-xs font-bold text-gray-400 uppercase mb-2">Jo'natilayotgan Miqdor</label>
          <input
            type="number"
            step="0.01"
            value={dispatchForm.quantity}
            onChange={(e) => setDispatchForm({ ...dispatchForm, quantity: e.target.value })}
            className="w-full skeuo-input"
            placeholder="Miqdorni kiriting"
            required
          />
        </div>

        <div>
          <label className="block text-xs font-bold text-gray-400 uppercase mb-2">Jo'natilayotgan joy (From location)</label>
          <input
            type="text"
            value={dispatchForm.from_location}
            onChange={(e) => setDispatchForm({ ...dispatchForm, from_location: e.target.value })}
            className="w-full skeuo-input"
            placeholder="Tokcha nomi"
            required
          />
        </div>

        <div>
          <label className="block text-xs font-bold text-gray-400 uppercase mb-2">Xaridor yoki buyurtmachi izohi</label>
          <textarea
            value={dispatchForm.notes}
            onChange={(e) => setDispatchForm({ ...dispatchForm, notes: e.target.value })}
            className="w-full skeuo-input min-h-[80px]"
            placeholder="Masalan: Barno LLC buyurtmasiga binoan"
          />
        </div>

        <button type="submit" className="py-3.5 skeuo-btn text-blue-600 font-extrabold text-sm active:scale-95 duration-100">
          📤 Jo'natishni Tasdiqlash
        </button>
      </form>
    </div>
  );
}
