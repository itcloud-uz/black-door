import React, { useState } from 'react';
import api from '../../services/api';

export default function ProductCatalog({ products, onRefresh }) {
  const [editingProd, setEditingProd] = useState(null);
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const [showCreateModal, setShowCreateModal] = useState(false);

  // Local Form State for adding products
  const [createForm, setCreateForm] = useState({
    name: '',
    unit_type: 'kg',
    base_price: '0',
    cost_price: '0',
    category: 'Umumiy',
    manufacturer: '',
    quantity_in_stock: '0',
    currency: 'USD',
    exchange_rate: '12800'
  });

  // Local Form State for editing products
  const [editCurrency, setEditCurrency] = useState('USD');
  const [editExchangeRate, setEditExchangeRate] = useState('12800');

  const handleEditClick = (prod) => {
    setEditingProd({ ...prod });
    setEditCurrency('USD');
    setEditExchangeRate('12800');
    setError('');
  };

  const handleCreateSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setLoading(true);
    try {
      let baseUSD = parseFloat(createForm.base_price) || 0;
      let costUSD = parseFloat(createForm.cost_price) || 0;
      if (createForm.currency === 'UZS') {
        const rate = parseFloat(createForm.exchange_rate) || 12800;
        baseUSD = baseUSD / rate;
        costUSD = costUSD / rate;
      }
      await api.post('/admin/products', {
        name: createForm.name,
        unit_type: createForm.unit_type,
        base_price: baseUSD,
        cost_price: costUSD,
        category: createForm.category,
        manufacturer: createForm.manufacturer,
        quantity_in_stock: parseFloat(createForm.quantity_in_stock) || 0
      });
      setCreateForm({
        name: '',
        unit_type: 'kg',
        base_price: '0',
        cost_price: '0',
        category: 'Umumiy',
        manufacturer: '',
        quantity_in_stock: '0',
        currency: 'USD',
        exchange_rate: '12800'
      });
      setShowCreateModal(false);
      if (onRefresh) onRefresh();
    } catch (err) {
      setError(err.response?.data?.error || 'Mahsulot qo\'shishda xatolik yuz berdi');
    } finally {
      setLoading(false);
    }
  };

  const handleUpdateProd = async (e) => {
    e.preventDefault();
    setError('');
    setLoading(true);
    try {
      let baseUSD = parseFloat(editingProd.base_price) || 0;
      let costUSD = parseFloat(editingProd.cost_price) || 0;
      if (editCurrency === 'UZS') {
        const rate = parseFloat(editExchangeRate) || 12800;
        baseUSD = baseUSD / rate;
        costUSD = costUSD / rate;
      }
      await api.put(`/admin/products/${editingProd.id}`, {
        name: editingProd.name,
        unit_type: editingProd.unit_type,
        base_price: baseUSD,
        cost_price: costUSD,
        category: editingProd.category,
        manufacturer: editingProd.manufacturer,
        quantity_in_stock: parseFloat(editingProd.quantity_in_stock) || 0
      });
      setEditingProd(null);
      if (onRefresh) onRefresh();
    } catch (err) {
      setError(err.response?.data?.error || 'Mahsulotni yangilashda xatolik yuz berdi');
    } finally {
      setLoading(false);
    }
  };

  const handleDeleteClick = async (prodId) => {
    if (!window.confirm("Haqiqatdan ham ushbu mahsulotni o'chirmoqchisiz? Bu amalni ortga qaytarib bo'lmaydi!")) {
      return;
    }
    try {
      await api.delete(`/admin/products/${prodId}`);
      if (onRefresh) onRefresh();
    } catch (err) {
      alert(err.response?.data?.error || "Mahsulotni o'chirishda xatolik yuz berdi");
    }
  };

  return (
    <div>
      <div className="flex justify-between items-center mb-8">
        <h2 className="text-2xl font-black text-slate-100">Mahsulotlar Katalogi</h2>
        <button
          onClick={() => setShowCreateModal(true)}
          className="flex items-center gap-2 py-2.5 px-6 skeuo-btn text-xs font-bold text-indigo-400 hover:text-indigo-300"
        >
          ➕ Yangi mahsulot qo'shish
        </button>
      </div>

      {/* Create Product Modal */}
      {showCreateModal && (
        <div className="fixed inset-0 bg-black/60 backdrop-blur-md flex items-center justify-center z-50 p-4">
          <div className="w-full max-w-lg p-8 skeuo-convex border border-white/10 shadow-2xl overflow-y-auto max-h-[90vh]">
            <h3 className="text-lg font-black text-slate-100 mb-6">➕ Yangi mahsulot qo'shish</h3>
            {error && (
              <div className="mb-4 p-3 rounded bg-red-500/10 border border-red-500/20 text-red-400 text-xs font-bold">
                ⚠️ {error}
              </div>
            )}
            <form onSubmit={handleCreateSubmit} className="space-y-6">
              <div>
                <label className="block text-xs font-bold text-slate-400 uppercase mb-2">Mahsulot nomi</label>
                <input
                  type="text"
                  value={createForm.name}
                  onChange={(e) => setCreateForm({ ...createForm, name: e.target.value })}
                  className="w-full skeuo-input"
                  placeholder="Sement M500, Oyna 4mm..."
                  required
                />
              </div>

              <div>
                <label className="block text-xs font-bold text-slate-400 uppercase mb-2">O'lchov birligi</label>
                <select
                  value={createForm.unit_type}
                  onChange={(e) => setCreateForm({ ...createForm, unit_type: e.target.value })}
                  className="w-full skeuo-input bg-[#131b2e]"
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
                <label className="block text-xs font-bold text-slate-400 uppercase mb-2">Narx Valyutasi</label>
                <select
                  value={createForm.currency}
                  onChange={(e) => setCreateForm({ ...createForm, currency: e.target.value })}
                  className="w-full skeuo-input bg-[#131b2e]"
                >
                  <option value="USD">USD (AQSH Dollari)</option>
                  <option value="UZS">UZS (O'zbek So'mi)</option>
                </select>
              </div>

              {createForm.currency === 'UZS' && (
                <div>
                  <label className="block text-xs font-bold text-slate-400 uppercase mb-2">Dollar Kursi (1 USD = ... UZS)</label>
                  <input
                    type="number"
                    value={createForm.exchange_rate}
                    onChange={(e) => setCreateForm({ ...createForm, exchange_rate: e.target.value })}
                    className="w-full skeuo-input"
                    placeholder="12800"
                    required
                  />
                </div>
              )}

              <div>
                <label className="block text-xs font-bold text-slate-400 uppercase mb-2">
                  Asosiy narxi (Sotuv, {createForm.currency})
                </label>
                <input
                  type="number"
                  step="0.01"
                  value={createForm.base_price}
                  onChange={(e) => setCreateForm({ ...createForm, base_price: e.target.value })}
                  className="w-full skeuo-input"
                  required
                />
              </div>

              <div>
                <label className="block text-xs font-bold text-slate-400 uppercase mb-2">
                  Tannarxi (Xarid, {createForm.currency})
                </label>
                <input
                  type="number"
                  step="0.01"
                  value={createForm.cost_price}
                  onChange={(e) => setCreateForm({ ...createForm, cost_price: e.target.value })}
                  className="w-full skeuo-input"
                  required
                />
              </div>

              <div>
                <label className="block text-xs font-bold text-slate-400 uppercase mb-2">Kategoriya</label>
                <input
                  type="text"
                  value={createForm.category}
                  onChange={(e) => setCreateForm({ ...createForm, category: e.target.value })}
                  className="w-full skeuo-input"
                  placeholder="Temir buyumlar, Shisha x.k."
                  required
                />
              </div>

              <div>
                <label className="block text-xs font-bold text-slate-400 uppercase mb-2">Ishlab chiqaruvchi</label>
                <input
                  type="text"
                  value={createForm.manufacturer}
                  onChange={(e) => setCreateForm({ ...createForm, manufacturer: e.target.value })}
                  className="w-full skeuo-input"
                  placeholder="Qizilqum Sement..."
                />
              </div>

              <div>
                <label className="block text-xs font-bold text-slate-400 uppercase mb-2">Ombordagi miqdor</label>
                <input
                  type="number"
                  value={createForm.quantity_in_stock}
                  onChange={(e) => setCreateForm({ ...createForm, quantity_in_stock: e.target.value })}
                  className="w-full skeuo-input"
                />
              </div>

              <div className="flex gap-4 pt-4">
                <button type="submit" disabled={loading} className="flex-1 py-3 skeuo-btn text-indigo-400 font-extrabold text-sm active:scale-95 duration-100">
                  {loading ? "Saqlanmoqda..." : "💾 Saqlash"}
                </button>
                <button type="button" onClick={() => setShowCreateModal(false)} className="flex-1 py-3 skeuo-btn text-slate-400 font-extrabold text-sm active:scale-95 duration-100">
                  Bekor qilish
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Edit Product Modal */}
      {editingProd && (
        <div className="fixed inset-0 bg-black/60 backdrop-blur-md flex items-center justify-center z-50 p-4">
          <div className="w-full max-w-lg p-8 skeuo-convex border border-white/10 shadow-2xl overflow-y-auto max-h-[90vh]">
            <h3 className="text-lg font-black text-slate-100 mb-6">📝 Mahsulot ma'lumotlarini tahrirlash</h3>
            {error && (
              <div className="mb-4 p-3 rounded bg-red-500/10 border border-red-500/20 text-red-400 text-xs font-bold">
                ⚠️ {error}
              </div>
            )}
            <form onSubmit={handleUpdateProd} className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div className="md:col-span-2">
                <label className="block text-xs font-bold text-slate-400 uppercase mb-2">Mahsulot nomi</label>
                <input
                  type="text"
                  value={editingProd.name}
                  onChange={(e) => setEditingProd({ ...editingProd, name: e.target.value })}
                  className="w-full skeuo-input"
                  required
                />
              </div>

              <div>
                <label className="block text-xs font-bold text-slate-400 uppercase mb-2">O'lchov birligi</label>
                <select
                  value={editingProd.unit_type}
                  onChange={(e) => setEditingProd({ ...editingProd, unit_type: e.target.value })}
                  className="w-full skeuo-input bg-[#131b2e]"
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
                <label className="block text-xs font-bold text-slate-400 uppercase mb-2">Narx Valyutasi</label>
                <select
                  value={editCurrency}
                  onChange={(e) => setEditCurrency(e.target.value)}
                  className="w-full skeuo-input bg-[#131b2e]"
                >
                  <option value="USD">USD (AQSH Dollari)</option>
                  <option value="UZS">UZS (O'zbek So'mi)</option>
                </select>
              </div>

              {editCurrency === 'UZS' && (
                <div className="md:col-span-2">
                  <label className="block text-xs font-bold text-slate-400 uppercase mb-2">Dollar Kursi (1 USD = ... UZS)</label>
                  <input
                    type="number"
                    value={editExchangeRate}
                    onChange={(e) => setEditExchangeRate(e.target.value)}
                    className="w-full skeuo-input"
                    placeholder="12800"
                    required
                  />
                </div>
              )}

              <div>
                <label className="block text-xs font-bold text-slate-400 uppercase mb-2">
                  Asosiy narxi ({editCurrency})
                </label>
                <input
                  type="number"
                  step="0.01"
                  value={editingProd.base_price}
                  onChange={(e) => setEditingProd({ ...editingProd, base_price: e.target.value })}
                  className="w-full skeuo-input"
                  required
                />
              </div>

              <div>
                <label className="block text-xs font-bold text-slate-400 uppercase mb-2">
                  Tannarxi ({editCurrency})
                </label>
                <input
                  type="number"
                  step="0.01"
                  value={editingProd.cost_price}
                  onChange={(e) => setEditingProd({ ...editingProd, cost_price: e.target.value })}
                  className="w-full skeuo-input"
                  required
                />
              </div>

              <div>
                <label className="block text-xs font-bold text-slate-400 uppercase mb-2">Kategoriya</label>
                <input
                  type="text"
                  value={editingProd.category}
                  onChange={(e) => setEditingProd({ ...editingProd, category: e.target.value })}
                  className="w-full skeuo-input"
                  required
                />
              </div>

              <div>
                <label className="block text-xs font-bold text-slate-400 uppercase mb-2">Ishlab chiqaruvchi</label>
                <input
                  type="text"
                  value={editingProd.manufacturer || ''}
                  onChange={(e) => setEditingProd({ ...editingProd, manufacturer: e.target.value })}
                  className="w-full skeuo-input"
                />
              </div>

              <div className="md:col-span-2">
                <label className="block text-xs font-bold text-slate-400 uppercase mb-2">Ombordagi miqdor</label>
                <input
                  type="number"
                  value={editingProd.quantity_in_stock}
                  onChange={(e) => setEditingProd({ ...editingProd, quantity_in_stock: e.target.value })}
                  className="w-full skeuo-input"
                />
              </div>

              <div className="md:col-span-2 flex gap-4 mt-4">
                <button
                  type="submit"
                  disabled={loading}
                  className="flex-1 py-3 skeuo-btn text-emerald-400 font-bold active:scale-95 duration-100"
                >
                  {loading ? "Saqlanmoqda..." : "💾 Saqlash"}
                </button>
                <button
                  type="button"
                  onClick={() => setEditingProd(null)}
                  className="flex-1 py-3 skeuo-btn text-slate-400 font-bold active:scale-95 duration-100"
                >
                  Bekor qilish
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* List products */}
      <div className="skeuo-convex p-6 border border-white/5 shadow-lg">
        <h3 className="font-bold text-slate-200 text-sm mb-4">Zaxiradagi Mahsulotlar Katalogi</h3>
        <div className="overflow-x-auto">
          <table className="w-full text-left text-xs border-collapse">
            <thead>
              <tr className="border-b border-white/10 text-slate-400 uppercase font-extrabold tracking-wider">
                <th className="py-3 px-4">Nomi</th>
                <th className="py-3 px-4">Kategoriya</th>
                <th className="py-3 px-4">Sotuv Narxi</th>
                <th className="py-3 px-4">Tannarxi</th>
                <th className="py-3 px-4">Qoldiq Miqdor</th>
                <th className="py-3 px-4">Ishlab chiqaruvchi</th>
                <th className="py-3 px-4 text-right">Amallar</th>
              </tr>
            </thead>
            <tbody>
              {products.map(p => (
                <tr key={p.id} className="border-b border-white/5 hover:bg-white/5">
                  <td className="py-3 px-4 font-bold text-slate-100">{p.name}</td>
                  <td className="py-3 px-4 text-slate-400">{p.category}</td>
                  <td className="py-3 px-4 text-emerald-400 font-extrabold">${parseFloat(p.base_price).toFixed(2)}</td>
                  <td className="py-3 px-4 text-red-400 font-extrabold">${parseFloat(p.cost_price).toFixed(2)}</td>
                  <td className="py-3 px-4 font-black text-slate-200">
                    {parseFloat(p.quantity_in_stock).toLocaleString()} {p.unit_type}
                  </td>
                  <td className="py-3 px-4 text-slate-400">{p.manufacturer || '—'}</td>
                  <td className="py-3 px-4 text-right flex justify-end gap-2">
                    <button
                      onClick={() => handleEditClick(p)}
                      className="py-1 px-2.5 text-[10px] font-bold text-indigo-400 hover:text-indigo-300 skeuo-btn active:scale-95 duration-100"
                    >
                      ✏️ Tahrirlash
                    </button>
                    <button
                      onClick={() => handleDeleteClick(p.id)}
                      className="py-1 px-2.5 text-[10px] font-bold text-red-400 hover:text-red-300 skeuo-btn active:scale-95 duration-100"
                    >
                      🗑️ O'chirish
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}
