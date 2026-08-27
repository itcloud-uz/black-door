import React from 'react';

export default function ProductCatalog({ products, prodForm, setProdForm, onCreateProd }) {
  return (
    <div>
      <h2 className="text-2xl font-black text-[#2D3748] mb-8">Mahsulotlar Katalogi</h2>

      {/* Create Product Form */}
      <div className="p-6 skeuo-convex mb-8">
        <h3 className="font-bold text-[#2D3748] text-sm mb-6">➕ Yangi mahsulot qo'shish</h3>
        <form onSubmit={onCreateProd} className="grid grid-cols-1 md:grid-cols-4 gap-6">
          <div>
            <label className="block text-xs font-bold text-gray-400 uppercase mb-2">Mahsulot nomi</label>
            <input
              type="text"
              value={prodForm.name}
              onChange={(e) => setProdForm({ ...prodForm, name: e.target.value })}
              className="w-full skeuo-input"
              placeholder="Sement M500, Oyna 4mm..."
              required
            />
          </div>

          <div>
            <label className="block text-xs font-bold text-gray-400 uppercase mb-2">O'lchov birligi</label>
            <select
              value={prodForm.unit_type}
              onChange={(e) => setProdForm({ ...prodForm, unit_type: e.target.value })}
              className="w-full skeuo-input"
            >
              <option value="kg">kilogram (kg)</option>
              <option value="dona">dona (piece)</option>
              <option value="meter">metr (meter)</option>
              <option value="liter">litr (liter)</option>
              <option value="box">quti (box)</option>
              <option value="ton">tonna (ton)</option>
            </select>
          </div>

          <div>
            <label className="block text-xs font-bold text-gray-400 uppercase mb-2">Asosiy narxi (Sotuv, USD)</label>
            <input
              type="number"
              value={prodForm.base_price}
              onChange={(e) => setProdForm({ ...prodForm, base_price: e.target.value })}
              className="w-full skeuo-input"
            />
          </div>

          <div>
            <label className="block text-xs font-bold text-gray-400 uppercase mb-2">Tannarxi (Xarid, USD)</label>
            <input
              type="number"
              value={prodForm.cost_price}
              onChange={(e) => setProdForm({ ...prodForm, cost_price: e.target.value })}
              className="w-full skeuo-input"
            />
          </div>

          <div>
            <label className="block text-xs font-bold text-gray-400 uppercase mb-2">Kategoriya</label>
            <input
              type="text"
              value={prodForm.category}
              onChange={(e) => setProdForm({ ...prodForm, category: e.target.value })}
              className="w-full skeuo-input"
              placeholder="Temir buyumlar, Shisha x.k."
              required
            />
          </div>

          <div>
            <label className="block text-xs font-bold text-gray-400 uppercase mb-2">Ishlab chiqaruvchi</label>
            <input
              type="text"
              value={prodForm.manufacturer}
              onChange={(e) => setProdForm({ ...prodForm, manufacturer: e.target.value })}
              className="w-full skeuo-input"
              placeholder="Qizilqum Sement..."
            />
          </div>

          <div>
            <label className="block text-xs font-bold text-gray-400 uppercase mb-2">Ombordagi miqdor</label>
            <input
              type="number"
              value={prodForm.quantity_in_stock}
              onChange={(e) => setProdForm({ ...prodForm, quantity_in_stock: e.target.value })}
              className="w-full skeuo-input"
            />
          </div>

          <div className="flex items-end justify-end">
            <button type="submit" className="py-3 px-8 skeuo-btn text-blue-600 font-extrabold text-sm active:scale-95 duration-100 w-full">
              💾 Mahsulotni Saqlash
            </button>
          </div>
        </form>
      </div>

      {/* List products */}
      <div className="skeuo-convex p-6">
        <h3 className="font-bold text-gray-800 text-sm mb-4">Zaxiradagi Mahsulotlar Katalogi</h3>
        <div className="overflow-x-auto">
          <table className="w-full text-left text-xs border-collapse">
            <thead>
              <tr className="border-b border-gray-300 text-gray-500 uppercase font-extrabold tracking-wider">
                <th className="py-3 px-4">Nomi</th>
                <th className="py-3 px-4">Kategoriya</th>
                <th className="py-3 px-4">Sotuv Narxi</th>
                <th className="py-3 px-4">Tannarxi</th>
                <th className="py-3 px-4">Qoldiq Miqdor</th>
                <th className="py-3 px-4">Ishlab chiqaruvchi</th>
              </tr>
            </thead>
            <tbody>
              {products.map(p => (
                <tr key={p.id} className="border-b border-gray-200 hover:bg-gray-50">
                  <td className="py-3 px-4 font-bold text-gray-800">{p.name}</td>
                  <td className="py-3 px-4 text-gray-500">{p.category}</td>
                  <td className="py-3 px-4 text-green-600 font-extrabold">${parseFloat(p.base_price).toFixed(2)}</td>
                  <td className="py-3 px-4 text-red-600 font-extrabold">${parseFloat(p.cost_price).toFixed(2)}</td>
                  <td className="py-3 px-4 font-black">
                    {parseFloat(p.quantity_in_stock).toLocaleString()} {p.unit_type}
                  </td>
                  <td className="py-3 px-4 text-gray-400">{p.manufacturer || '—'}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}
