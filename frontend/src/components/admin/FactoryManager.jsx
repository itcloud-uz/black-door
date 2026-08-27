import React, { useState } from 'react';
import api from '../../services/api';

export default function FactoryManager({ factories, facForm, setFacForm, onCreateFac, onRefresh }) {
  const [editingFac, setEditingFac] = useState(null);
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const [showCreateModal, setShowCreateModal] = useState(false);

  const handleEditClick = (fac) => {
    setEditingFac({ ...fac });
    setError('');
  };

  const handleCreateSubmit = (e) => {
    e.preventDefault();
    onCreateFac(e);
    setShowCreateModal(false);
  };

  const handleUpdateFac = async (e) => {
    e.preventDefault();
    setError('');
    setLoading(true);
    try {
      await api.put(`/admin/factories/${editingFac.id}`, {
        name: editingFac.name,
        address: editingFac.address,
        phone: editingFac.phone,
        manager_name: editingFac.manager_name,
        equipment_type: editingFac.equipment_type,
        rental_rate_per_day: parseFloat(editingFac.rental_rate_per_day) || 0,
        production_commission_percent: parseFloat(editingFac.production_commission_percent) || 0,
        is_active: editingFac.is_active
      });
      setEditingFac(null);
      if (onRefresh) onRefresh();
    } catch (err) {
      setError(err.response?.data?.error || 'Zavodni yangilashda xatolik yuz berdi');
    } finally {
      setLoading(false);
    }
  };

  const handleDeleteClick = async (facId) => {
    if (!window.confirm("Haqiqatdan ham ushbu zavodni o'chirmoqchisiz? Bu amalni ortga qaytarib bo'lmaydi!")) {
      return;
    }
    try {
      await api.delete(`/admin/factories/${facId}`);
      if (onRefresh) onRefresh();
    } catch (err) {
      alert(err.response?.data?.error || "Zavodni o'chirishda xatolik yuz berdi");
    }
  };

  return (
    <div>
      <div className="flex justify-between items-center mb-8">
        <h2 className="text-2xl font-black text-slate-100">Zavodlar Boshqaruvi</h2>
        <button
          onClick={() => setShowCreateModal(true)}
          className="flex items-center gap-2 py-2.5 px-6 skeuo-btn text-xs font-bold text-indigo-400 hover:text-indigo-300"
        >
          ➕ Yangi zavod qo'shish
        </button>
      </div>

      {/* Create Factory Modal */}
      {showCreateModal && (
        <div className="fixed inset-0 bg-black/60 backdrop-blur-md flex items-center justify-center z-50 p-4">
          <div className="w-full max-w-lg p-8 skeuo-convex border border-white/10 shadow-2xl overflow-y-auto max-h-[90vh]">
            <h3 className="text-lg font-black text-slate-100 mb-6">➕ Yangi zavod qo'shish</h3>
            <form onSubmit={handleCreateSubmit} className="space-y-6">
              <div>
                <label className="block text-xs font-bold text-slate-400 uppercase mb-2">Zavod nomi</label>
                <input
                  type="text"
                  value={facForm.name}
                  onChange={(e) => setFacForm({ ...facForm, name: e.target.value })}
                  className="w-full skeuo-input"
                  placeholder="Toshkent Armatura Zavodi"
                  required
                />
              </div>

              <div>
                <label className="block text-xs font-bold text-slate-400 uppercase mb-2">Manzil</label>
                <input
                  type="text"
                  value={facForm.address}
                  onChange={(e) => setFacForm({ ...facForm, address: e.target.value })}
                  className="w-full skeuo-input"
                  placeholder="Sergeli 4-mavze"
                  required
                />
              </div>

              <div>
                <label className="block text-xs font-bold text-slate-400 uppercase mb-2">Telefon</label>
                <input
                  type="text"
                  value={facForm.phone}
                  onChange={(e) => setFacForm({ ...facForm, phone: e.target.value })}
                  className="w-full skeuo-input"
                  placeholder="+998712345678"
                  required
                />
              </div>

              <div>
                <label className="block text-xs font-bold text-slate-400 uppercase mb-2">Menejer (F.I.Sh)</label>
                <input
                  type="text"
                  value={facForm.manager_name}
                  onChange={(e) => setFacForm({ ...facForm, manager_name: e.target.value })}
                  className="w-full skeuo-input"
                  placeholder="Temur Olimov"
                  required
                />
              </div>

              <div>
                <label className="block text-xs font-bold text-slate-400 uppercase mb-2">Jihoz/Uskunalar turi</label>
                <input
                  type="text"
                  value={facForm.equipment_type}
                  onChange={(e) => setFacForm({ ...facForm, equipment_type: e.target.value })}
                  className="w-full skeuo-input"
                  placeholder="Qoliplash uskunalari"
                  required
                />
              </div>

              <div>
                <label className="block text-xs font-bold text-slate-400 uppercase mb-2">Ijara stavkasi (Kunlik, USD)</label>
                <input
                  type="number"
                  value={facForm.rental_rate_per_day}
                  onChange={(e) => setFacForm({ ...facForm, rental_rate_per_day: e.target.value })}
                  className="w-full skeuo-input"
                />
              </div>

              <div>
                <label className="block text-xs font-bold text-slate-400 uppercase mb-2">Komissiya foizi (%)</label>
                <input
                  type="number"
                  step="0.01"
                  value={facForm.production_commission_percent}
                  onChange={(e) => setFacForm({ ...facForm, production_commission_percent: e.target.value })}
                  className="w-full skeuo-input"
                />
              </div>

              <div className="flex gap-4 pt-4">
                <button type="submit" className="flex-1 py-3 skeuo-btn text-indigo-400 font-extrabold text-sm active:scale-95 duration-100">
                  💾 Saqlash
                </button>
                <button type="button" onClick={() => setShowCreateModal(false)} className="flex-1 py-3 skeuo-btn text-slate-400 font-extrabold text-sm active:scale-95 duration-100">
                  Bekor qilish
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Edit Factory Modal */}
      {editingFac && (
        <div className="fixed inset-0 bg-black/60 backdrop-blur-md flex items-center justify-center z-50 p-4">
          <div className="w-full max-w-lg p-8 skeuo-convex border border-white/10 shadow-2xl overflow-y-auto max-h-[90vh]">
            <h3 className="text-lg font-black text-slate-100 mb-6">📝 Zavod ma'lumotlarini tahrirlash</h3>
            {error && (
              <div className="mb-4 p-3 rounded bg-red-500/10 border border-red-500/20 text-red-400 text-xs font-bold">
                ⚠️ {error}
              </div>
            )}
            <form onSubmit={handleUpdateFac} className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label className="block text-xs font-bold text-slate-400 uppercase mb-2">Zavod nomi</label>
                <input
                  type="text"
                  value={editingFac.name}
                  onChange={(e) => setEditingFac({ ...editingFac, name: e.target.value })}
                  className="w-full skeuo-input"
                  required
                />
              </div>

              <div>
                <label className="block text-xs font-bold text-slate-400 uppercase mb-2">Manzil</label>
                <input
                  type="text"
                  value={editingFac.address}
                  onChange={(e) => setEditingFac({ ...editingFac, address: e.target.value })}
                  className="w-full skeuo-input"
                  required
                />
              </div>

              <div>
                <label className="block text-xs font-bold text-slate-400 uppercase mb-2">Telefon</label>
                <input
                  type="text"
                  value={editingFac.phone}
                  onChange={(e) => setEditingFac({ ...editingFac, phone: e.target.value })}
                  className="w-full skeuo-input"
                  required
                />
              </div>

              <div>
                <label className="block text-xs font-bold text-slate-400 uppercase mb-2">Menejer (F.I.Sh)</label>
                <input
                  type="text"
                  value={editingFac.manager_name}
                  onChange={(e) => setEditingFac({ ...editingFac, manager_name: e.target.value })}
                  className="w-full skeuo-input"
                  required
                />
              </div>

              <div>
                <label className="block text-xs font-bold text-slate-400 uppercase mb-2">Jihoz/Uskunalar</label>
                <input
                  type="text"
                  value={editingFac.equipment_type}
                  onChange={(e) => setEditingFac({ ...editingFac, equipment_type: e.target.value })}
                  className="w-full skeuo-input"
                  required
                />
              </div>

              <div>
                <label className="block text-xs font-bold text-slate-400 uppercase mb-2">Kunlik ijara ($)</label>
                <input
                  type="number"
                  value={editingFac.rental_rate_per_day}
                  onChange={(e) => setEditingFac({ ...editingFac, rental_rate_per_day: e.target.value })}
                  className="w-full skeuo-input"
                />
              </div>

              <div>
                <label className="block text-xs font-bold text-slate-400 uppercase mb-2">Komissiya foizi (%)</label>
                <input
                  type="number"
                  step="0.01"
                  value={editingFac.production_commission_percent}
                  onChange={(e) => setEditingFac({ ...editingFac, production_commission_percent: e.target.value })}
                  className="w-full skeuo-input"
                />
              </div>

              <div>
                <label className="block text-xs font-bold text-slate-400 uppercase mb-2">Status</label>
                <select
                  value={editingFac.is_active ? "true" : "false"}
                  onChange={(e) => setEditingFac({ ...editingFac, is_active: e.target.value === "true" })}
                  className="w-full skeuo-input bg-[#131b2e]"
                >
                  <option value="true">Faol (Ishlamoqda)</option>
                  <option value="false">Nofaol (To'xtagan)</option>
                </select>
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
                  onClick={() => setEditingFac(null)}
                  className="flex-1 py-3 skeuo-btn text-slate-400 font-bold active:scale-95 duration-100"
                >
                  Bekor qilish
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* List factories */}
      <div className="skeuo-convex p-6 border border-white/5 shadow-lg">
        <h3 className="font-bold text-slate-200 text-sm mb-4">Mavjud Zavodlar</h3>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          {factories.map(fac => (
            <div key={fac.id} className="p-6 skeuo-flat rounded-xl border border-white/5 flex flex-col justify-between">
              <div>
                <div className="flex justify-between items-center mb-4">
                  <h4 className="font-extrabold text-lg text-slate-100">{fac.name}</h4>
                  <span className={`px-3 py-1 rounded text-xs font-bold ${
                    fac.is_active ? 'bg-emerald-500/10 text-emerald-400' : 'bg-red-500/10 text-red-400'
                  }`}>{fac.is_active ? 'Ishlamoqda' : 'To\'xtagan'}</span>
                </div>

                <div className="grid grid-cols-2 gap-4 text-xs text-slate-400 mb-4">
                  <div>📍 Manzil: <strong className="text-slate-200">{fac.address}</strong></div>
                  <div>👤 Menejer: <strong className="text-slate-200">{fac.manager_name}</strong></div>
                  <div>📞 Tel: <strong className="text-slate-200">{fac.phone}</strong></div>
                  <div>⚙️ Uskunalar: <strong className="text-slate-200">{fac.equipment_type}</strong></div>
                </div>
              </div>

              <div className="pt-4 border-t border-dashed border-white/10 flex flex-col gap-4">
                <div className="flex justify-between text-xs font-bold text-slate-400">
                  <div>Kunlik ijara: <strong className="text-slate-200">${parseFloat(fac.rental_rate_per_day)}</strong></div>
                  <div>Komissiya: <strong className="text-slate-200">{parseFloat(fac.production_commission_percent)}%</strong></div>
                </div>

                <div className="flex gap-2 w-full mt-2">
                  <button
                    onClick={() => handleEditClick(fac)}
                    className="flex-1 py-1.5 text-xs font-bold text-indigo-400 hover:text-indigo-300 skeuo-btn active:scale-95 duration-100"
                  >
                    ✏️ Tahrirlash
                  </button>
                  <button
                    onClick={() => handleDeleteClick(fac.id)}
                    className="flex-1 py-1.5 text-xs font-bold text-red-400 hover:text-red-300 skeuo-btn active:scale-95 duration-100"
                  >
                    🗑️ O'chirish
                  </button>
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}
