import React, { useState, useEffect } from 'react';
import api from '../../services/api';

export default function UserManager() {
  const [users, setUsers] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [editingUser, setEditingUser] = useState(null);
  const [showCreateModal, setShowCreateModal] = useState(false);

  // Create User Form State
  const [form, setForm] = useState({
    email: '',
    full_name: '',
    role: 'employee',
    telegram_id: ''
  });

  const fetchUsers = async () => {
    setLoading(true);
    setError('');
    try {
      const res = await api.get('/admin/users');
      setUsers(res.data);
    } catch (err) {
      setError(err.response?.data?.error || 'Foydalanuvchilarni yuklashda xatolik yuz berdi');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchUsers();
  }, []);

  const handleCreateUser = async (e) => {
    e.preventDefault();
    setError('');
    setLoading(true);
    try {
      await api.post('/admin/users', form);
      setForm({
        email: '',
        full_name: '',
        role: 'employee',
        telegram_id: ''
      });
      setShowCreateModal(false);
      fetchUsers();
    } catch (err) {
      setError(err.response?.data?.error || 'Foydalanuvchini qo\'shishda xatolik yuz berdi');
    } finally {
      setLoading(false);
    }
  };

  const handleEditClick = (u) => {
    setEditingUser({ ...u });
    setError('');
  };

  const handleUpdateUser = async (e) => {
    e.preventDefault();
    setError('');
    setLoading(true);
    try {
      await api.put(`/admin/users/${editingUser.id}`, {
        email: editingUser.email,
        full_name: editingUser.full_name,
        role: editingUser.role,
        telegram_id: editingUser.telegram_id,
        is_telegram_verified: editingUser.is_telegram_verified
      });
      setEditingUser(null);
      fetchUsers();
    } catch (err) {
      setError(err.response?.data?.error || 'Foydalanuvchini tahrirlashda xatolik yuz berdi');
    } finally {
      setLoading(false);
    }
  };

  const handleDeleteClick = async (uId) => {
    if (!window.confirm("Haqiqatdan ham ushbu foydalanuvchini o'chirmoqchisiz?")) {
      return;
    }
    try {
      await api.delete(`/admin/users/${uId}`);
      fetchUsers();
    } catch (err) {
      alert(err.response?.data?.error || 'Foydalanuvchini o\'chirishda xatolik yuz berdi');
    }
  };

  return (
    <div>
      <div className="flex justify-between items-center mb-8">
        <h2 className="text-2xl font-black text-slate-100">Foydalanuvchilar Boshqaruvi</h2>
        <button
          onClick={() => {
            setShowCreateModal(true);
            setError('');
          }}
          className="flex items-center gap-2 py-2.5 px-6 skeuo-btn text-xs font-bold text-indigo-400 hover:text-indigo-300"
        >
          ➕ Yangi foydalanuvchi qo'shish
        </button>
      </div>

      {/* Create User Modal */}
      {showCreateModal && (
        <div className="fixed inset-0 bg-black/60 backdrop-blur-md flex items-center justify-center z-50 p-4">
          <div className="w-full max-w-lg p-8 skeuo-convex border border-white/10 shadow-2xl">
            <h3 className="text-lg font-black text-slate-100 mb-6">➕ Yangi foydalanuvchi qo'shish</h3>
            {error && !editingUser && (
              <div className="mb-4 p-3 rounded bg-red-500/10 border border-red-500/20 text-red-400 text-xs font-bold">
                ⚠️ {error}
              </div>
            )}
            <form onSubmit={handleCreateUser} className="space-y-6">
              <div>
                <label className="block text-xs font-bold text-slate-400 uppercase mb-2">Google Gmail adresi</label>
                <input
                  type="email"
                  value={form.email}
                  onChange={(e) => setForm({ ...form, email: e.target.value })}
                  className="w-full skeuo-input"
                  placeholder="user@gmail.com"
                  required
                />
              </div>

              <div>
                <label className="block text-xs font-bold text-slate-400 uppercase mb-2">Foydalanuvchi ismi</label>
                <input
                  type="text"
                  value={form.full_name}
                  onChange={(e) => setForm({ ...form, full_name: e.target.value })}
                  className="w-full skeuo-input"
                  placeholder="Ali Valiyev"
                  required
                />
              </div>

              <div>
                <label className="block text-xs font-bold text-slate-400 uppercase mb-2">Tizimdagi roli</label>
                <select
                  value={form.role}
                  onChange={(e) => setForm({ ...form, role: e.target.value })}
                  className="w-full skeuo-input bg-[#131b2e]"
                >
                  <option value="employee">Employee (Xodim/Omborchi)</option>
                  <option value="admin">Admin (Tizim boshqaruvchisi)</option>
                </select>
              </div>

              <div>
                <label className="block text-xs font-bold text-slate-400 uppercase mb-2">Telegram Chat ID (Ixtiyoriy)</label>
                <input
                  type="text"
                  value={form.telegram_id}
                  onChange={(e) => setForm({ ...form, telegram_id: e.target.value })}
                  className="w-full skeuo-input"
                  placeholder="Masalan: 1412501744"
                />
              </div>

              <div className="flex gap-4 pt-4">
                <button
                  type="submit"
                  disabled={loading}
                  className="flex-1 py-3 skeuo-btn text-indigo-400 font-bold active:scale-95 duration-100"
                >
                  {loading ? "Saqlanmoqda..." : "💾 Saqlash"}
                </button>
                <button
                  type="button"
                  onClick={() => setShowCreateModal(false)}
                  className="flex-1 py-3 skeuo-btn text-slate-400 font-bold active:scale-95 duration-100"
                >
                  Bekor qilish
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Edit User Modal */}
      {editingUser && (
        <div className="fixed inset-0 bg-black/60 backdrop-blur-md flex items-center justify-center z-50 p-4">
          <div className="w-full max-w-lg p-8 skeuo-convex border border-white/10 shadow-2xl">
            <h3 className="text-lg font-black text-slate-100 mb-6">📝 Foydalanuvchi ma'lumotlarini tahrirlash</h3>
            {error && (
              <div className="mb-4 p-3 rounded bg-red-500/10 border border-red-500/20 text-red-400 text-xs font-bold">
                ⚠️ {error}
              </div>
            )}
            <form onSubmit={handleUpdateUser} className="space-y-6">
              <div>
                <label className="block text-xs font-bold text-slate-400 uppercase mb-2">Email</label>
                <input
                  type="email"
                  value={editingUser.email}
                  onChange={(e) => setEditingUser({ ...editingUser, email: e.target.value })}
                  className="w-full skeuo-input"
                  required
                />
              </div>

              <div>
                <label className="block text-xs font-bold text-slate-400 uppercase mb-2">Foydalanuvchi ismi</label>
                <input
                  type="text"
                  value={editingUser.full_name}
                  onChange={(e) => setEditingUser({ ...editingUser, full_name: e.target.value })}
                  className="w-full skeuo-input"
                  required
                />
              </div>

              <div>
                <label className="block text-xs font-bold text-slate-400 uppercase mb-2">Roli</label>
                <select
                  value={editingUser.role}
                  onChange={(e) => setEditingUser({ ...editingUser, role: e.target.value })}
                  className="w-full skeuo-input bg-[#131b2e]"
                >
                  <option value="employee">Employee (Xodim/Omborchi)</option>
                  <option value="admin">Admin (Tizim boshqaruvchisi)</option>
                </select>
              </div>

              <div>
                <label className="block text-xs font-bold text-slate-400 uppercase mb-2">Telegram Chat ID</label>
                <input
                  type="text"
                  value={editingUser.telegram_id || ''}
                  onChange={(e) => setEditingUser({ ...editingUser, telegram_id: e.target.value })}
                  className="w-full skeuo-input"
                />
              </div>

              <div className="flex items-center gap-2">
                <input
                  type="checkbox"
                  id="edit_is_telegram_verified"
                  checked={editingUser.is_telegram_verified === true}
                  onChange={(e) => setEditingUser({ ...editingUser, is_telegram_verified: e.target.checked })}
                  className="w-4 h-4 rounded text-indigo-600 border-white/10 bg-white/5"
                />
                <label htmlFor="edit_is_telegram_verified" className="text-xs font-bold text-slate-300">
                  Telegram 2FA tasdiqlangan (Verified)
                </label>
              </div>

              <div className="flex gap-4 pt-4">
                <button
                  type="submit"
                  disabled={loading}
                  className="flex-1 py-3 skeuo-btn text-emerald-400 font-bold active:scale-95 duration-100"
                >
                  {loading ? "Saqlanmoqda..." : "💾 Saqlash"}
                </button>
                <button
                  type="button"
                  onClick={() => setEditingUser(null)}
                  className="flex-1 py-3 skeuo-btn text-slate-400 font-bold active:scale-95 duration-100"
                >
                  Bekor qilish
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Users List Table */}
      <div className="skeuo-convex p-6 border border-white/5 shadow-lg">
        <h3 className="font-bold text-slate-200 text-sm mb-4">Mavjud Foydalanuvchilar Ro'yxati</h3>
        <div className="overflow-x-auto">
          <table className="w-full text-left text-xs border-collapse">
            <thead>
              <tr className="border-b border-white/10 text-slate-400 uppercase font-extrabold tracking-wider">
                <th className="py-3 px-4">Foydalanuvchi</th>
                <th className="py-3 px-4">Tizimdagi Roli</th>
                <th className="py-3 px-4">Telegram Chat ID</th>
                <th className="py-3 px-4">Telegram 2FA</th>
                <th className="py-3 px-4">Oxirgi kirish</th>
                <th className="py-3 px-4 text-right">Amallar</th>
              </tr>
            </thead>
            <tbody>
              {users.map(u => (
                <tr key={u.id} className="border-b border-white/5 hover:bg-white/5">
                  <td className="py-3 px-4">
                    <div className="font-bold text-slate-100">{u.full_name}</div>
                    <div className="text-[10px] text-slate-400 font-mono">{u.email}</div>
                  </td>
                  <td className="py-3 px-4">
                    <span className={`px-2 py-0.5 rounded text-[10px] font-bold border ${
                      u.role === 'admin' ? 'bg-indigo-500/10 text-indigo-400 border-indigo-500/20' : 'bg-slate-500/10 text-slate-400 border-slate-500/20'
                    }`}>{u.role}</span>
                  </td>
                  <td className="py-3 px-4 font-mono text-slate-300">{u.telegram_id || '—'}</td>
                  <td className="py-3 px-4">
                    <span className={`px-2 py-0.5 rounded text-[9px] font-bold ${
                      u.is_telegram_verified ? 'bg-emerald-500/10 text-emerald-400' : 'bg-amber-500/10 text-amber-400'
                    }`}>{u.is_telegram_verified ? 'Tasdiqlangan' : 'Nofaol'}</span>
                  </td>
                  <td className="py-3 px-4 text-slate-400">
                    {u.last_login ? new Date(u.last_login).toLocaleString() : '—'}
                  </td>
                  <td className="py-3 px-4 text-right flex justify-end gap-2">
                    <button
                      onClick={() => handleEditClick(u)}
                      className="py-1 px-2.5 text-[10px] font-bold text-indigo-400 hover:text-indigo-300 skeuo-btn active:scale-95 duration-100"
                    >
                      ✏️ Tahrirlash
                    </button>
                    <button
                      onClick={() => handleDeleteClick(u.id)}
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
