import React from 'react';

export default function AccountManager({ 
  accounts, accForm, setAccForm, onCreateAcc, selectedAccId, setSelectedAccId, 
  adjustAmount, setAdjustAmount, adjustDesc, setAdjustDesc, onAdjustBalance 
}) {
  return (
    <div>
      <h2 className="text-2xl font-black text-[#2D3748] mb-8">Shaxslar va Kassalar Boshqaruvi</h2>

      {/* Create Account Form */}
      <div className="p-6 skeuo-convex mb-8">
        <h3 className="font-bold text-[#2D3748] text-sm mb-6">➕ Yangi hisob varaq qo'shish</h3>
        <form onSubmit={onCreateAcc} className="grid grid-cols-1 md:grid-cols-4 gap-6">
          <div>
            <label className="block text-xs font-bold text-gray-400 uppercase mb-2">Hisob turi</label>
            <select
              value={accForm.account_type}
              onChange={(e) => setAccForm({ ...accForm, account_type: e.target.value })}
              className="w-full skeuo-input"
            >
              <option value="person">Hamkor / Shaxs (Person)</option>
              <option value="company">Tizim Kassasi (Company)</option>
              <option value="factory">Zavod Balansi (Factory)</option>
            </select>
          </div>

          <div>
            <label className="block text-xs font-bold text-gray-400 uppercase mb-2">Nom (Eshmatov, Kassa x.k.)</label>
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
            <label className="block text-xs font-bold text-gray-400 uppercase mb-2">Hisob Raqam (Noyob)</label>
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
            <label className="block text-xs font-bold text-gray-400 uppercase mb-2">Valyuta</label>
            <select
              value={accForm.currency}
              onChange={(e) => setAccForm({ ...accForm, currency: e.target.value })}
              className="w-full skeuo-input"
            >
              <option value="UZS">UZS</option>
              <option value="USD">USD</option>
            </select>
          </div>

          <div>
            <label className="block text-xs font-bold text-gray-400 uppercase mb-2">Telefon</label>
            <input
              type="text"
              value={accForm.phone}
              onChange={(e) => setAccForm({ ...accForm, phone: e.target.value })}
              className="w-full skeuo-input"
              placeholder="+998901234567"
            />
          </div>

          <div>
            <label className="block text-xs font-bold text-gray-400 uppercase mb-2">Email</label>
            <input
              type="email"
              value={accForm.email}
              onChange={(e) => setAccForm({ ...accForm, email: e.target.value })}
              className="w-full skeuo-input"
              placeholder="user@example.com"
            />
          </div>

          <div>
            <label className="block text-xs font-bold text-gray-400 uppercase mb-2">Boshlang'ich Balans</label>
            <input
              type="number"
              value={accForm.current_balance}
              onChange={(e) => setAccForm({ ...accForm, current_balance: e.target.value })}
              className="w-full skeuo-input"
            />
          </div>

          <div className="flex items-end justify-end">
            <button type="submit" className="py-3 px-8 skeuo-btn text-blue-600 font-extrabold text-sm active:scale-95 duration-100 w-full">
              💾 Hisobni Saqlash
            </button>
          </div>
        </form>
      </div>

      {/* Balances Adjust Modal Overlay */}
      {selectedAccId && (
        <div className="fixed inset-0 bg-black/35 backdrop-blur-sm flex items-center justify-center z-50">
          <div className="w-full max-w-md p-8 skeuo-convex">
            <h3 className="text-lg font-black text-gray-800 mb-4">Balansni Tuzatish (Qo'lda)</h3>
            <form onSubmit={onAdjustBalance}>
              <div className="mb-4">
                <label className="block text-xs font-bold text-gray-400 uppercase mb-2">Tuzatish Summasi (Farqi)</label>
                <input
                  type="number"
                  value={adjustAmount}
                  onChange={(e) => setAdjustAmount(e.target.value)}
                  className="w-full skeuo-input"
                  placeholder="Masalan: -500 (Deduct) yoki 1000 (Add)"
                  required
                />
              </div>
              <div className="mb-6">
                <label className="block text-xs font-bold text-gray-400 uppercase mb-2">Tuzatish Sababi / Izoh</label>
                <input
                  type="text"
                  value={adjustDesc}
                  onChange={(e) => setAdjustDesc(e.target.value)}
                  className="w-full skeuo-input"
                  placeholder="Masalan: Kassa kamomad hisobi"
                  required
                />
              </div>
              <div className="flex gap-4">
                <button type="submit" className="flex-1 py-3 skeuo-btn text-green-600 font-bold">O'zgartirish</button>
                <button type="button" onClick={() => setSelectedAccId(null)} className="flex-1 py-3 skeuo-btn text-gray-500">Bekor qilish</button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* List accounts */}
      <div className="skeuo-convex p-6">
        <h3 className="font-bold text-gray-800 text-sm mb-4">Barcha Hisoblar va Balanslar</h3>
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          {accounts.map(acc => (
            <div key={acc.id} className="p-5 skeuo-flat rounded-xl flex flex-col justify-between">
              <div>
                <div className="flex justify-between items-center mb-3">
                  <span className="text-[10px] font-bold text-gray-400 uppercase tracking-wider">{acc.account_type}</span>
                  <span className={`px-2 py-0.5 text-[9px] rounded font-bold ${
                    acc.account_status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                  }`}>{acc.account_status}</span>
                </div>
                <h4 className="font-extrabold text-sm text-[#2D3748] mb-1">{acc.account_holder_name}</h4>
                <p className="font-mono text-xs text-gray-400 mb-4">{acc.account_number}</p>
              </div>

              <div className="flex items-center justify-between pt-4 border-t border-dashed border-gray-300">
                <div>
                  <div className="text-[10px] font-bold text-gray-400 uppercase">Joriy Balans</div>
                  <div className={`text-lg font-black ${parseFloat(acc.current_balance) >= 0 ? 'text-green-600' : 'text-red-600'}`}>
                    {parseFloat(acc.current_balance).toLocaleString()} {acc.currency}
                  </div>
                </div>

                <button
                  onClick={() => setSelectedAccId(acc.id)}
                  className="py-1.5 px-3.5 skeuo-btn text-xs font-bold text-blue-600"
                >
                  ⚖️ Tuzatish
                </button>
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}
