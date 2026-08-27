import React from 'react';

export default function FactoryManager({ factories, facForm, setFacForm, onCreateFac }) {
  return (
    <div>
      <h2 className="text-2xl font-black text-[#2D3748] mb-8">Zavodlar va Ishlab Chiqarish Boshqaruvi</h2>

      {/* Create Factory Form */}
      <div className="p-6 skeuo-convex mb-8">
        <h3 className="font-bold text-[#2D3748] text-sm mb-6">➕ Yangi zavod qo'shish</h3>
        <form onSubmit={onCreateFac} className="grid grid-cols-1 md:grid-cols-4 gap-6">
          <div>
            <label className="block text-xs font-bold text-gray-400 uppercase mb-2">Zavod nomi</label>
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
            <label className="block text-xs font-bold text-gray-400 uppercase mb-2">Manzil</label>
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
            <label className="block text-xs font-bold text-gray-400 uppercase mb-2">Telefon</label>
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
            <label className="block text-xs font-bold text-gray-400 uppercase mb-2">Menejer (F.I.Sh)</label>
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
            <label className="block text-xs font-bold text-gray-400 uppercase mb-2">Jihoz/Uskunalar turi</label>
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
            <label className="block text-xs font-bold text-gray-400 uppercase mb-2">Ijara stavkasi (Kunlik, USD)</label>
            <input
              type="number"
              value={facForm.rental_rate_per_day}
              onChange={(e) => setFacForm({ ...facForm, rental_rate_per_day: e.target.value })}
              className="w-full skeuo-input"
            />
          </div>

          <div>
            <label className="block text-xs font-bold text-gray-400 uppercase mb-2">Komissiya foizi (%)</label>
            <input
              type="number"
              step="0.01"
              value={facForm.production_commission_percent}
              onChange={(e) => setFacForm({ ...facForm, production_commission_percent: e.target.value })}
              className="w-full skeuo-input"
            />
          </div>

          <div className="flex items-end justify-end">
            <button type="submit" className="py-3 px-8 skeuo-btn text-blue-600 font-extrabold text-sm active:scale-95 duration-100 w-full">
              💾 Zavodni Saqlash
            </button>
          </div>
        </form>
      </div>

      {/* List factories */}
      <div className="skeuo-convex p-6">
        <h3 className="font-bold text-gray-800 text-sm mb-4">Mavjud Zavodlar</h3>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          {factories.map(fac => (
            <div key={fac.id} className="p-6 skeuo-flat rounded-xl">
              <div className="flex justify-between items-center mb-4">
                <h4 className="font-extrabold text-lg text-[#2D3748]">{fac.name}</h4>
                <span className={`px-3 py-1 rounded text-xs font-bold ${
                  fac.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                }`}>{fac.is_active ? 'Ishlamoqda' : 'To\'xtagan'}</span>
              </div>

              <div className="grid grid-cols-2 gap-4 text-xs text-gray-500 mb-4">
                <div>📍 Manzil: <strong className="text-gray-700">{fac.address}</strong></div>
                <div>👤 Menejer: <strong className="text-gray-700">{fac.manager_name}</strong></div>
                <div>📞 Tel: <strong className="text-gray-700">{fac.phone}</strong></div>
                <div>⚙️ Uskunalar: <strong className="text-gray-700">{fac.equipment_type}</strong></div>
              </div>

              <div className="pt-4 border-t border-dashed border-gray-300 flex justify-between text-xs font-bold text-gray-600">
                <div>Kunlik ijara: <strong className="text-[#2D3748]">${parseFloat(fac.rental_rate_per_day)}</strong></div>
                <div>Komissiya: <strong className="text-[#2D3748]">{parseFloat(fac.production_commission_percent)}%</strong></div>
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}
