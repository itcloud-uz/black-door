import React, { useState } from 'react';
import api from '../../services/api';

export default function AccountManager({ 
  accounts, accForm, setAccForm, onCreateAcc, onRefresh
}) {
  const [editingAcc, setEditingAcc] = useState(null);
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const [showCreateModal, setShowCreateModal] = useState(false);

  // Local Balance Adjustment States
  const [localSelectedAccId, setLocalSelectedAccId] = useState(null);
  const [localAdjustAmount, setLocalAdjustAmount] = useState('');
  const [localAdjustDesc, setLocalAdjustDesc] = useState('');
  const [adjustCurrency, setAdjustCurrency] = useState('UZS');
  const [adjustRate, setAdjustRate] = useState('12800');

  const handleEditClick = (acc) => {
    setEditingAcc({ ...acc });
    setError('');
  };

  const handleCreateSubmit = (e) => {
    e.preventDefault();
    onCreateAcc(e);
    setShowCreateModal(false);
  };

  const handleAdjustClick = (acc) => {
    setLocalSelectedAccId(acc.id);
    setAdjustCurrency(acc.currency);
    setAdjustRate('12800');
    setLocalAdjustAmount('');
    setLocalAdjustDesc('');
  };

  const handleAdjustSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    try {
      const activeAcc = accounts.find(a => a.id === localSelectedAccId);
      let finalAmt = parseFloat(localAdjustAmount) || 0;
      if (adjustCurrency !== activeAcc.currency) {
        const rate = parseFloat(adjustRate) || 12800;
        if (adjustCurrency === 'USD' && activeAcc.currency === 'UZS') {
          finalAmt = finalAmt * rate;
        } else if (adjustCurrency === 'UZS' && activeAcc.currency === 'USD') {
          finalAmt = finalAmt / rate;
        }
      }
      await api.post(`/admin/accounts/${localSelectedAccId}/adjust-balance`, {
        adjustmentAmount: finalAmt,
        description: localAdjustDesc
      });
      setLocalSelectedAccId(null);
      if (onRefresh) onRefresh();
    } catch (err) {
      alert(err.response?.data?.error || 'Balansni tuzatishda xatolik yuz berdi');
    } finally {
      setLoading(false);
    }
  };

  const handleUpdateAcc = async (e) => {
    e.preventDefault();
    setError('');
    setLoading(true);
    try {
      await api.put(`/admin/accounts/${editingAcc.id}`, {
        account_type: editingAcc.account_type,
        account_holder_name: editingAcc.account_holder_name,
        account_number: editingAcc.account_number,
        currency: editingAcc.currency,
        phone: editingAcc.phone,
        email: editingAcc.email,
        account_status: editingAcc.account_status
      });
      setEditingAcc(null);
      if (onRefresh) onRefresh();
    } catch (err) {
      setError(err.response?.data?.error || 'Hisobni yangilashda xatolik yuz berdi');
    } finally {
      setLoading(false);
    }
  };

  const handleDeleteClick = async (accId) => {
    if (!window.confirm("Haqiqatdan ham ushbu hisobni o'chirmoqchisiz? Bu amalni ortga qaytarib bo'lmaydi!")) {
      return;
    }
    try {
      await api.delete(`/admin/accounts/${accId}`);
      if (onRefresh) onRefresh();
    } catch (err) {
      alert(err.response?.data?.error || "Hisobni o'chirishda xatolik yuz berdi");
    }
  };

  const selectedAccountObj = accounts.find(a => a.id === localSelectedAccId);

  return (
    <div>
      <div className="flex justify-between items-center mb-8">
        <h2 className="text-2xl font-black text-slate-100">Shaxslar va Kassalar Boshqaruvi</h2>
        <button
          onClick={() => setShowCreateModal(true)}
          className="flex items-center gap-2 py-2.5 px-6 skeuo-btn text-xs font-bold text-indigo-400 hover:text-indigo-300"
        >
          ➕ Yangi hisob varaq qo'shish
        </button>
      </div>

      {/* Create Account Modal */}
      {showCreateModal && (
        <div className="fixed inset-0 bg-black/60 backdrop-blur-md flex items-center justify-center z-50 p-4">
          <div className="w-full max-w-lg p-8 skeuo-convex border border-white/10 shadow-2xl overflow-y-auto max-h-[90vh]">
            <h3 className="text-lg font-black text-slate-100 mb-6">➕ Yangi hisob varaq qo'shish</h3>
            <form onSubmit={handleCreateSubmit} className="space-y-6">
              <div>
                <label className="block text-xs font-bold text-slate-400 uppercase mb-2">Hisob turi</label>
                <select
                  value={accForm.account_type}
                  onChange={(e) => setAccForm({ ...accForm, account_type: e.target.value })}
                  className="w-full skeuo-input bg-[#131b2e]"
                >
                  <option value="person">Hamkor / Shaxs (Person)</option>
                  <option value="company">Tizim Kassasi (Company)</option>
                  <option value="factory">Zavod Balansi (Factory)</option>
                </select>
              </div>

              <div>
                <label className="block text-xs font-bold text-slate-400 uppercase mb-2">Nom (Eshmatov, Kassa x.k.)</label>
                <input
                  type="text"
                  value={accForm.account_holder_name}
                  onChange={(e) => setAccForm({ ...accForm, account_holder_name: e.target.value })}
                  className="w-full skeuo-input"
                  placeholder="FIO yoki kassa nomi"
                  required
                />
              </div>

              <div>
                <label className="block text-xs font-bold text-slate-400 uppercase mb-2">Hisob Raqam (Noyob)</label>
                <input
                  type="text"
                  value={accForm.account_number}
                  onChange={(e) => setAccForm({ ...accForm, account_number: e.target.value })}
                  className="w-full skeuo-input"
                  placeholder="ACC-UZS-100"
                  required
                />
              </div>

              <div>
                <label className="block text-xs font-bold text-slate-400 uppercase mb-2">Valyuta</label>
                <select
                  value={accForm.currency}
                  onChange={(e) => setAccForm({ ...accForm, currency: e.target.value })}
                  className="w-full skeuo-input bg-[#131b2e]"
                >
                  <option value="UZS">UZS</option>
                  <option value="USD">USD</option>
                </select>
              </div>

              <div>
                <label className="block text-xs font-bold text-slate-400 uppercase mb-2">Telefon</label>
                <input
                  type="text"
                  value={accForm.phone}
                  onChange={(e) => setAccForm({ ...accForm, phone: e.target.value })}
                  className="w-full skeuo-input"
                  placeholder="+998901234567"
                />
              </div>

              <div>
                <label className="block text-xs font-bold text-slate-400 uppercase mb-2">Email</label>
                <input
                  type="email"
                  value={accForm.email}
                  onChange={(e) => setAccForm({ ...accForm, email: e.target.value })}
                  className="w-full skeuo-input"
                  placeholder="user@example.com"
                />
              </div>

              <div>
                <label className="block text-xs font-bold text-slate-400 uppercase mb-2">Boshlang'ich Balans</label>
                <input
                  type="number"
                  value={accForm.current_balance}
                  onChange={(e) => setAccForm({ ...accForm, current_balance: e.target.value })}
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

      {/* Edit Account Modal */}
      {editingAcc && (
        <div className="fixed inset-0 bg-black/60 backdrop-blur-md flex items-center justify-center z-50 p-4">
          <div className="w-full max-w-lg p-8 skeuo-convex border border-white/10 shadow-2xl">
            <h3 className="text-lg font-black text-slate-100 mb-6">📝 Hisob ma'lumotlarini tahrirlash</h3>
            {error && (
              <div className="mb-4 p-3 rounded bg-red-500/10 border border-red-500/20 text-red-400 text-xs font-bold">
                ⚠️ {error}
              </div>
            )}
            <form onSubmit={handleUpdateAcc} className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label className="block text-xs font-bold text-slate-400 uppercase mb-2">Hisob turi</label>
                <select
                  value={editingAcc.account_type}
                  onChange={(e) => setEditingAcc({ ...editingAcc, account_type: e.target.value })}
                  className="w-full skeuo-input bg-[#131b2e]"
                >
                  <option value="person">Hamkor / Shaxs (Person)</option>
                  <option value="company">Tizim Kassasi (Company)</option>
                  <option value="factory">Zavod Balansi (Factory)</option>
                </select>
              </div>

              <div>
                <label className="block text-xs font-bold text-slate-400 uppercase mb-2">Nomi</label>
                <input
                  type="text"
                  value={editingAcc.account_holder_name}
                  onChange={(e) => setEditingAcc({ ...editingAcc, account_holder_name: e.target.value })}
                  className="w-full skeuo-input"
                  required
                />
              </div>

              <div>
                <label className="block text-xs font-bold text-slate-400 uppercase mb-2">Hisob Raqam</label>
                <input
                  type="text"
                  value={editingAcc.account_number}
                  onChange={(e) => setEditingAcc({ ...editingAcc, account_number: e.target.value })}
                  className="w-full skeuo-input"
                  required
                />
              </div>

              <div>
                <label className="block text-xs font-bold text-slate-400 uppercase mb-2">Valyuta</label>
                <select
                  value={editingAcc.currency}
                  onChange={(e) => setEditingAcc({ ...editingAcc, currency: e.target.value })}
                  className="w-full skeuo-input bg-[#131b2e]"
                >
                  <option value="UZS">UZS</option>
                  <option value="USD">USD</option>
                </select>
              </div>

              <div>
                <label className="block text-xs font-bold text-slate-400 uppercase mb-2">Telefon</label>
                <input
                  type="text"
                  value={editingAcc.phone || ''}
                  onChange={(e) => setEditingAcc({ ...editingAcc, phone: e.target.value })}
                  className="w-full skeuo-input"
                />
              </div>

              <div>
                <label className="block text-xs font-bold text-slate-400 uppercase mb-2">Email</label>
                <input
                  type="email"
                  value={editingAcc.email || ''}
                  onChange={(e) => setEditingAcc({ ...editingAcc, email: e.target.value })}
                  className="w-full skeuo-input"
                />
              </div>

              <div>
                <label className="block text-xs font-bold text-slate-400 uppercase mb-2">Status</label>
                <select
                  value={editingAcc.account_status}
                  onChange={(e) => setEditingAcc({ ...editingAcc, account_status: e.target.value })}
                  className="w-full skeuo-input bg-[#131b2e]"
                >
                  <option value="active">Faol (Active)</option>
                  <option value="inactive">Nofaol (Inactive)</option>
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
                  onClick={() => setEditingAcc(null)}
                  className="flex-1 py-3 skeuo-btn text-slate-400 font-bold active:scale-95 duration-100"
                >
                  Bekor qilish
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Balances Adjust Modal Overlay */}
      {localSelectedAccId && selectedAccountObj && (
        <div className="fixed inset-0 bg-black/60 backdrop-blur-md flex items-center justify-center z-50 p-4">
          <div className="w-full max-w-md p-8 skeuo-convex border border-white/10 shadow-2xl">
            <h3 className="text-lg font-black text-slate-100 mb-4">Balansni Tuzatish (Qo'lda)</h3>
            <form onSubmit={handleAdjustSubmit} className="space-y-4">
              <div>
                <label className="block text-xs font-bold text-slate-400 uppercase mb-2">Tuzatish Valyutasi</label>
                <select
                  value={adjustCurrency}
                  onChange={(e) => setAdjustCurrency(e.target.value)}
                  className="w-full skeuo-input bg-[#131b2e]"
                >
                  <option value="UZS">UZS</option>
                  <option value="USD">USD</option>
                </select>
              </div>

              {adjustCurrency !== selectedAccountObj.currency && (
                <div>
                  <label className="block text-xs font-bold text-slate-400 uppercase mb-2">Dollar Kursi (1 USD = ... UZS)</label>
                  <input
                    type="number"
                    value={adjustRate}
                    onChange={(e) => setAdjustRate(e.target.value)}
                    className="w-full skeuo-input"
                    placeholder="12800"
                    required
                  />
                </div>
              )}

              <div>
                <label className="block text-xs font-bold text-slate-400 uppercase mb-2">
                  Tuzatish Summasi (Farqi, {adjustCurrency})
                </label>
                <input
                  type="number"
                  value={localAdjustAmount}
                  onChange={(e) => setLocalAdjustAmount(e.target.value)}
                  className="w-full skeuo-input"
                  placeholder="Masalan: -500 yoki 1000"
                  required
                />
              </div>

              <div>
                <label className="block text-xs font-bold text-slate-400 uppercase mb-2">Tuzatish Sababi / Izoh</label>
                <input
                  type="text"
                  value={localAdjustDesc}
                  onChange={(e) => setLocalAdjustDesc(e.target.value)}
                  className="w-full skeuo-input"
                  placeholder="Masalan: Kassa kamomad hisobi"
                  required
                />
              </div>
              <div className="flex gap-4 pt-4">
                <button type="submit" disabled={loading} className="flex-1 py-3 skeuo-btn text-emerald-400 font-bold active:scale-95 duration-100">
                  {loading ? "Tuzatilmoqda..." : "O'zgartirish"}
                </button>
                <button type="button" onClick={() => setLocalSelectedAccId(null)} className="flex-1 py-3 skeuo-btn text-slate-400 active:scale-95 duration-100">Bekor qilish</button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* List accounts */}
      <div className="skeuo-convex p-6 border border-white/5 shadow-lg">
        <h3 className="font-bold text-slate-200 text-sm mb-4">Barcha Hisoblar va Balanslar</h3>
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          {accounts.map(acc => (
            <div key={acc.id} className="p-5 skeuo-flat rounded-xl flex flex-col justify-between border border-white/5">
              <div>
                <div className="flex justify-between items-center mb-3">
                  <span className="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{acc.account_type}</span>
                  <span className={`px-2 py-0.5 text-[9px] rounded font-bold ${
                    acc.account_status === 'active' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-red-500/10 text-red-400'
                  }`}>{acc.account_status}</span>
                </div>
                <h4 className="font-extrabold text-sm text-slate-100 mb-1">{acc.account_holder_name}</h4>
                <p className="font-mono text-xs text-slate-400 mb-4">{acc.account_number}</p>
              </div>

              <div className="pt-4 border-t border-dashed border-white/10 flex flex-col gap-4">
                <div className="flex justify-between items-end">
                  <div>
                    <div className="text-[10px] font-bold text-slate-400 uppercase">Joriy Balans</div>
                    <div className={`text-lg font-black ${parseFloat(acc.current_balance) >= 0 ? 'text-emerald-400' : 'text-red-400'}`}>
                      {parseFloat(acc.current_balance).toLocaleString()} {acc.currency}
                    </div>
                  </div>

                  <button
                    onClick={() => handleAdjustClick(acc)}
                    className="py-1.5 px-3 skeuo-btn text-xs font-bold text-amber-400 hover:text-amber-300"
                  >
                    ⚖️ Tuzatish
                  </button>
                </div>

                <div className="flex gap-2 w-full mt-2">
                  <button
                    onClick={() => handleEditClick(acc)}
                    className="flex-1 py-1.5 text-xs font-bold text-indigo-400 hover:text-indigo-300 skeuo-btn active:scale-95 duration-100"
                  >
                    ✏️ Tahrirlash
                  </button>
                  <button
                    onClick={() => handleDeleteClick(acc.id)}
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
