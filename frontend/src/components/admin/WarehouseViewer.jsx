import React from 'react';

export default function WarehouseViewer({ warehouseInventory, warehouseOperations }) {
  return (
    <div>
      <h2 className="text-2xl font-black text-slate-100 mb-8">Omborxona Operatsiyalari Jurnali</h2>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
        {/* Warehouse stock placement */}
        <div className="skeuo-convex p-6 border border-white/5 shadow-lg">
          <h3 className="font-bold text-slate-200 text-sm mb-4">📦 Haqiqiy Tovar Joylashuvi (Inventory Map)</h3>
          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs border-collapse">
              <thead>
                <tr className="border-b border-white/10 text-slate-400 uppercase font-extrabold tracking-wider">
                  <th className="py-3 px-4">Mahsulot</th>
                  <th className="py-3 px-4">Location (Tokcha / Sektor)</th>
                  <th className="py-3 px-4">Miqdor</th>
                  <th className="py-3 px-4">Oxirgi tekshiruv</th>
                </tr>
              </thead>
              <tbody>
                {warehouseInventory.map(item => (
                  <tr key={item.id} className="border-b border-white/5 hover:bg-white/5">
                    <td className="py-3 px-4 font-bold text-slate-200">{item.product_name}</td>
                    <td className="py-3 px-4"><span className="px-2 py-1 bg-white/10 text-slate-300 rounded font-mono text-xs">{item.location}</span></td>
                    <td className="py-3 px-4 font-black text-slate-100">{parseFloat(item.quantity).toLocaleString()} {item.unit_type}</td>
                    <td className="py-3 px-4 text-slate-400">
                      {item.last_counted_at ? new Date(item.last_counted_at).toLocaleString() : '—'}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>

        {/* Operations logs history */}
        <div className="skeuo-convex p-6 border border-white/5 shadow-lg">
          <h3 className="font-bold text-slate-200 text-sm mb-4">📜 Oxirgi Kirim/Chiqim Operatsiyalari</h3>
          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs border-collapse">
              <thead>
                <tr className="border-b border-white/10 text-slate-400 uppercase font-extrabold tracking-wider">
                  <th className="py-3 px-4">Operatsiya</th>
                  <th className="py-3 px-4">Mahsulot</th>
                  <th className="py-3 px-4">Miqdor</th>
                  <th className="py-3 px-4">Xodim</th>
                  <th className="py-3 px-4">Sana</th>
                </tr>
              </thead>
              <tbody>
                {warehouseOperations.map(op => (
                  <tr key={op.id} className="border-b border-white/5 hover:bg-white/5">
                    <td className="py-3 px-4">
                      <span className={`px-2 py-0.5 rounded font-bold text-[10px] border ${
                        op.operation_type === 'receive' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' :
                        op.operation_type === 'dispatch' ? 'bg-indigo-500/10 text-indigo-400 border-indigo-500/20' : 'bg-amber-500/10 text-amber-400 border-amber-500/20'
                      }`}>{op.operation_type}</span>
                    </td>
                    <td className="py-3 px-4 font-bold text-slate-200">{op.product_name}</td>
                    <td className="py-3 px-4 font-black text-slate-100">{parseFloat(op.quantity).toLocaleString()} {op.unit_type}</td>
                    <td className="py-3 px-4 text-slate-300">{op.operator_name}</td>
                    <td className="py-3 px-4 text-slate-400">{new Date(op.created_at).toLocaleDateString()}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  );
}
