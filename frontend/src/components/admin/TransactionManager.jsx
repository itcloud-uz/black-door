import React, { useState } from 'react';
import { FileSpreadsheet, FileText, Trash2 } from 'lucide-react';

export default function TransactionManager({ 
  transactions, accounts, factories, products, txForm, setTxForm, onCreateTx, onDeleteTx, onExport 
}) {
  const [showCreateModal, setShowCreateModal] = useState(false);

  const handleSubmit = (e) => {
    e.preventDefault();
    onCreateTx(e);
    setShowCreateModal(false);
  };

  return (
    <div>
      <div className="flex justify-between items-center mb-8">
        <div className="flex items-center gap-4">
          <h2 className="text-2xl font-black text-slate-100">Moliyaviy Buxgalteriya</h2>
          <button
            onClick={() => setShowCreateModal(true)}
            className="flex items-center gap-2 py-2 px-4 skeuo-btn text-xs font-bold text-indigo-400 hover:text-indigo-300"
          >
            ➕ Yangi tranzaksiya kiritish
          </button>
        </div>
        <div className="flex gap-3">
          <button onClick={() => onExport('excel')} className="flex items-center gap-2 py-2 px-4 skeuo-btn text-xs font-bold text-emerald-400 hover:text-emerald-300">
            <FileSpreadsheet size={16} /> Excel
          </button>
          <button onClick={() => onExport('pdf')} className="flex items-center gap-2 py-2 px-4 skeuo-btn text-xs font-bold text-rose-400 hover:text-rose-300">
            <FileText size={16} /> PDF
          </button>
        </div>
      </div>

      {/* Create Transaction Modal */}
      {showCreateModal && (
        <div className="fixed inset-0 bg-black/60 backdrop-blur-md flex items-center justify-center z-50 p-4">
          <div className="w-full max-w-2xl p-8 skeuo-convex border border-white/10 shadow-2xl overflow-y-auto max-h-[90vh]">
            <h3 className="text-lg font-black text-slate-100 mb-6">➕ Yangi tranzaksiya kiritish</h3>
            <form onSubmit={handleSubmit} className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label className="block text-xs font-bold text-slate-400 uppercase mb-2">Tranzaksiya turi</label>
                <select
                  value={txForm.transaction_type}
                  onChange={(e) => setTxForm({ ...txForm, transaction_type: e.target.value })}
                  className="w-full skeuo-input bg-[#131b2e]"
                >
                  <option value="cash_deposit">Kassa Balans Ko'paytirish (Deposit)</option>
                  <option value="personal_withdrawal">Shaxs pul yechishi (Withdrawal)</option>
                  <option value="factory_rental">Zavod Ijara To'lovi</option>
                  <option value="factory_commission">Zavod Mahsulot Sotuv Komissiyasi</option>
                  <option value="foreign_payment">Chet El To'lov (Foreign Transaction)</option>
                  <option value="domestic_payment">Ichki Hamkor To'lovi</option>
                  <option value="product_sale">Tayyor Mahsulot Sotish (Sale)</option>
                  <option value="product_purchase">Xomashyo Xarid qilish (Purchase)</option>
                </select>
              </div>

              <div>
                <label className="block text-xs font-bold text-slate-400 uppercase mb-2">Summa</label>
                <input
                  type="number"
                  step="0.01"
                  value={txForm.amount}
                  onChange={(e) => setTxForm({ ...txForm, amount: e.target.value })}
                  className="w-full skeuo-input"
                  placeholder="1500.00"
                  required
                />
              </div>

              <div>
                <label className="block text-xs font-bold text-slate-400 uppercase mb-2">Valyuta</label>
                <select
                  value={txForm.currency}
                  onChange={(e) => setTxForm({ ...txForm, currency: e.target.value })}
                  className="w-full skeuo-input bg-[#131b2e]"
                >
                  <option value="UZS">UZS</option>
                  <option value="USD">USD</option>
                  <option value="EUR">EUR</option>
                  <option value="RUB">RUB</option>
                </select>
              </div>

              <div>
                <label className="block text-xs font-bold text-slate-400 uppercase mb-2">Kassadan/Kimdan (From)</label>
                <input
                  type="text"
                  value={txForm.from_account}
                  onChange={(e) => setTxForm({ ...txForm, from_account: e.target.value })}
                  className="w-full skeuo-input"
                  placeholder="Asosiy Kassa yoki Hamkor Nomi"
                />
              </div>

              <div>
                <label className="block text-xs font-bold text-slate-400 uppercase mb-2">Kassaga/Kimga (To)</label>
                <input
                  type="text"
                  value={txForm.to_account}
                  onChange={(e) => setTxForm({ ...txForm, to_account: e.target.value })}
                  className="w-full skeuo-input"
                  placeholder="To'lov Oluvchi Kassasi"
                />
              </div>

              <div>
                <label className="block text-xs font-bold text-slate-400 uppercase mb-2">Bog'liq Shaxs/Hisob</label>
                <select
                  value={txForm.person_id}
                  onChange={(e) => setTxForm({ ...txForm, person_id: e.target.value })}
                  className="w-full skeuo-input bg-[#131b2e]"
                >
                  <option value="">Hamkor tanlang (ixtiyoriy)</option>
                  {accounts.map(acc => (
                    <option key={acc.id} value={acc.id}>{acc.account_holder_name} ({acc.account_number})</option>
                  ))}
                </select>
              </div>

              <div>
                <label className="block text-xs font-bold text-slate-400 uppercase mb-2">Zavod (Ixtiyoriy)</label>
                <select
                  value={txForm.factory_id}
                  onChange={(e) => setTxForm({ ...txForm, factory_id: e.target.value })}
                  className="w-full skeuo-input bg-[#131b2e]"
                >
                  <option value="">Zavod tanlang</option>
                  {factories.map(f => (
                    <option key={f.id} value={f.id}>{f.name}</option>
                  ))}
                </select>
              </div>

              <div>
                <label className="block text-xs font-bold text-slate-400 uppercase mb-2">Mahsulot (Ixtiyoriy)</label>
                <select
                  value={txForm.product_id}
                  onChange={(e) => setTxForm({ ...txForm, product_id: e.target.value })}
                  className="w-full skeuo-input bg-[#131b2e]"
                >
                  <option value="">Mahsulot tanlang</option>
                  {products.map(p => (
                    <option key={p.id} value={p.id}>{p.name}</option>
                  ))}
                </select>
              </div>

              <div className="md:col-span-2">
                <label className="block text-xs font-bold text-slate-400 uppercase mb-2">Tranzaksiya Tavsifi</label>
                <input
                  type="text"
                  value={txForm.description}
                  onChange={(e) => setTxForm({ ...txForm, description: e.target.value })}
                  className="w-full skeuo-input"
                  placeholder="Masalan: Zavod ijara summasi to'lovi"
                  required
                />
              </div>

              <div className="md:col-span-2 flex gap-4 pt-4">
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

      {/* List Table */}
      <div className="skeuo-convex p-6 border border-white/5 shadow-lg">
        <h3 className="font-bold text-slate-200 text-sm mb-4">Barcha Tranzaksiyalar</h3>
        <div className="overflow-x-auto">
          <table className="w-full text-left text-xs border-collapse">
            <thead>
              <tr className="border-b border-white/10 text-slate-400 uppercase font-extrabold tracking-wider">
                <th className="py-3 px-4">Kvitansiya #</th>
                <th className="py-3 px-4">Turi</th>
                <th className="py-3 px-4">Summa</th>
                <th className="py-3 px-4">Bog'liq shaxs</th>
                <th className="py-3 px-4">Tavsif</th>
                <th className="py-3 px-4">Sana</th>
                <th className="py-3 px-4">Amallar</th>
              </tr>
            </thead>
            <tbody>
              {transactions.map(t => (
                <tr key={t.id} className="border-b border-white/5 hover:bg-white/5">
                  <td className="py-3 px-4 font-mono font-bold text-slate-300">{t.receipt_number}</td>
                  <td className="py-3 px-4">
                    <span className={`px-2.5 py-1 rounded-full text-[10px] font-bold border ${
                      ['cash_deposit', 'product_sale', 'factory_rental'].includes(t.transaction_type)
                        ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' 
                        : 'bg-rose-500/10 text-rose-400 border-rose-500/20'
                    }`}>
                      {t.transaction_type}
                    </span>
                  </td>
                  <td className="py-3 px-4 font-black text-slate-100">
                    {parseFloat(t.amount).toLocaleString()} {t.currency}
                  </td>
                  <td className="py-3 px-4 text-slate-300">{t.person_name || '—'}</td>
                  <td className="py-3 px-4 text-slate-400">{t.description}</td>
                  <td className="py-3 px-4 text-slate-300">{new Date(t.created_at).toLocaleDateString('uz-UZ')}</td>
                  <td className="py-3 px-4">
                    <button
                      onClick={() => onDeleteTx(t.id)}
                      className="p-2 skeuo-btn text-rose-400 hover:text-rose-300"
                    >
                      <Trash2 size={14} />
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
